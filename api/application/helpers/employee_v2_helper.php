<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
*   Helper : Employee helper version 2
*   Author : John Fritz Marquez <fritzified.gamer@gmail.com>
*   Usage  : Employee/Manager Portal Only
*/

function todo_pending_timein_counter($emp_id, $company_id, $rest_day_r_a = "no", $holiday_approve = "no"){
    $_CI =& get_instance();
    
    $where = array(
        'ee.comp_id'        => $company_id,
        'ee.status'         => 'Active',
        'ee.corrected'      => 'Yes',
        "ag.emp_id"         => $emp_id,
        'ee.time_in_status' => 'pending',
        //  'ee.source !='      => 'mobile',
        'ee.flag_payroll_correction' => 'no',
        "at.level !="       => "",
        "ee.rest_day_r_a"   => $rest_day_r_a,
        "ee.holiday_approve" => $holiday_approve
    );
    
    $s = array(
        "ee.employee_time_in_id"
    );
    
    $_CI->db->select($s);
    $_CI->db->where($where);
    $_CI->db->where(" (ee.source ='EP' OR ee.last_source='Adjusted') ",NULL,FALSE);
    $_CI->db->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
    $_CI->db->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    // $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
    $_CI->db->join("approval_time_in AS at","at.approval_time_in_id= ee.approval_time_in_id","LEFT");
    $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND at.level = ag.level","LEFT");
    $_CI->db->group_by("ee.employee_time_in_id");
    $query = $_CI->db->get('employee_time_in AS ee',11);
    
    return ($query->num_rows > 0) ? $query->num_rows : 0;
}

function todo_pending_split_timein_counter($emp_id, $company_id){
    $_CI =& get_instance();
    
    $where = array(
        'sbti.comp_id'          => $company_id,
        'sbti.status'           => 'Active',
        'sbti.corrected'        => 'Yes',
        "ag.emp_id"             => $emp_id,
        'sbti.time_in_status'   => 'pending',
        'sbti.source !='        => 'mobile',
        "at.level !="           => "",
    );
    
    $s = array(
        "sbti.schedule_blocks_time_in_id"
    );
    
    $_CI->db->select($s);
    $_CI->db->where($where);
    
    $_CI->db->join('employee_payroll_information AS epi','epi.emp_id = sbti.emp_id','left');
    $_CI->db->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    // $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
    $_CI->db->join("approval_time_in AS at","at.approval_time_in_id= sbti.approval_time_in_id","LEFT");
    $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND at.level = ag.level","LEFT");
    $_CI->db->group_by("sbti.schedule_blocks_time_in_id");
    $query = $_CI->db->get('schedule_blocks_time_in AS sbti',11);
    
    return ($query->num_rows > 0) ? $query->num_rows : 0;
}

function get_agvg_of_approver($company_id, $emp_id) {
    $_CI =& get_instance();

    $where = array(
        'emp_id' => $emp_id,
        'ag.company_id' => $company_id,
        'name' => 'Mobile Clock-in'
    );
    $_CI->db->where($where);
    $_CI->db->join("approval_process AS ap", "ap.approval_process_id = ag.approval_process_id", "INNER");
    $q = $_CI->db->get('approval_groups AS ag');
    $r = $q->result();

    if ($r) {
        $agvgs = array();
        foreach ($r as $row) {
            array_push($agvgs, $row->approval_groups_via_groups_id);
        }
        return $agvgs;
    }
    return false;
}

function timein_approval_level_mb($emp_id){
    $_CI =& get_instance();

    if(is_numeric($emp_id)){
        $_CI->db->or_where('ap.name','Mobile Clock-in');
        $_CI->db->where('ag.emp_id',$emp_id);
        $_CI->db->select('ag.level AS level');
        $_CI->db->join('approval_process AS ap','ap.approval_process_id = ag.approval_process_id','LEFT');
        $q = $_CI->db->get('approval_groups AS ag');
        $r = $q->row_array();

        return ($r) ? $r : FALSE;
    }else{
        return false;
    }
}

function check_assigned_hours_mb($hours_appr_grp, $level){
    $_CI =& get_instance();
    
    $where = array(
            "emp_id" => $_CI->session->userdata("emp_id"),
            "level " => $level,
            "approval_groups_via_groups_id" => $hours_appr_grp
    );
    $_CI->db->where($where);
    $query = $_CI->db->get("approval_groups");
    $row = $query->row();

    return ($row) ? true : false;
}

function exclude_mobile_clockin($emp_id, $company_id,$search="") {
    $_CI =& get_instance();
    if(is_numeric($company_id)){
        $konsum_key = konsum_key();
        $level = timein_approval_level_mb($emp_id);
    
        $where = array(
                'ee.comp_id'   => $company_id,
                'ee.status'   => 'Active',
                'ee.corrected' => 'Yes',
                "ag.emp_id" => $emp_id,
                'ee.time_in_status' => 'pending',
                'ee.source' => 'mobile', 

        );
        $where2 = array(
                "at.level !=" => "",
                'ee.source' => 'mobile' 
        );
            
        $_CI->edb->where($where);
        $_CI->db->where($where2);
        $_CI->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
        $_CI->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
        $_CI->edb->join('accounts AS a','a.account_id = e.account_id','left');
        $_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $_CI->edb->join("approval_groups_via_groups AS agg","epi.location_base_login_approval_grp = agg.approval_groups_via_groups_id","LEFT");
        $_CI->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
        $_CI->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
        $_CI->edb->join("approval_time_in AS at","at.approval_time_in_id= ee.approval_time_in_id","LEFT");
        $_CI->db->group_by("employee_time_in_id");
        $query = $_CI->edb->get('employee_time_in AS ee');
        $result = $query->result();
        
        $arrs = array();
        if($result){
            $is_assigned = TRUE;
            $hours_notification = get_hours_notification_settings($company_id);
            foreach($result as $key => $approvers){
                /*if($approvers->flag_add_logs == 0){
                    $appr_grp = $approvers->attendance_adjustment_approval_grp;
                }elseif($approvers->flag_add_logs == 1){
                    $appr_grp = $approvers->add_logs_approval_grp;
                }elseif($approvers->flag_add_logs == 2){
                    $appr_grp = $approvers->location_base_login_approval_grp;
                }*/
                
                $appr_grp = $approvers->location_base_login_approval_grp;
                    
                $level = $approvers->level;
                    
                $check = check_assigned_hours_mb($appr_grp, $level);
                //  echo $emp->employee_time_in_id.' - '. $emp->approval_time_in_id.' - '.$check.'</br>';
                /*if($hours_notification->option == "choose level notification"){
                    $is_assigned = check_if_is_level_hours($level, $hours_notification->hours_alerts_notification_id);
                }*/
                if($check && $is_assigned){
                
                }else{
                    array_push($arrs, $approvers->employee_time_in_id);
                }
            }
            
        }
        $string = implode(",", $arrs);
        return $string;
    }else{
        return false;
    }
}

function todo_pending_mobile_clockin_counter($emp_id, $company_id){
    $_CI =& get_instance();
    
    $where = array(
        'ee.comp_id'    => $company_id,
        'ee.status'     => 'Active',
        "at.level !="   => "",
        'ee.time_in_status' => 'pending',
        "ag.emp_id"         => $emp_id,
    );
    
    $s = array(
        'ee.mobile_clockin_status',
        'ee.mobile_lunchout_status',
        'ee.mobile_lunchin_status',
        'ee.mobile_clockout_status',
        'ee.mobile_break1_out_status',
        'ee.mobile_break1_in_status',
        'ee.mobile_break2_out_status',
        'ee.mobile_break2_in_status'
    );

    $_CI->db->like('ee.source', 'mobile');
    
    $_CI->db->select($s);
    $_CI->db->where($where);
    
    $_CI->db->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
    $_CI->db->join("approval_groups_via_groups AS agg","epi.location_base_login_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    $_CI->db->join("approval_time_in AS at","at.approval_time_in_id = ee.approval_time_in_id","LEFT");
    $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND at.level = ag.level","LEFT");
    $_CI->db->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
    $_CI->db->group_by("ee.employee_time_in_id");
    $query = $_CI->db->get('employee_time_in AS ee');
    // last_query();
    $row = $query->result();
    
    $x = 0;
    $x1 = 0;
    $x2 = 0;
    $x3 = 0;
    $x4 = 0;
    
    if($row) {
        foreach ($row as $r) {
            if($r->mobile_clockin_status == 'pending') {
                // $x1 += 1;
                $x++;
            }
            
            if($r->mobile_lunchout_status == 'pending') {
                // $x2 += 1;
                $x++;
            }
            
            if($r->mobile_lunchin_status == 'pending') {
                // $x3 += 1;
                $x++;
            }
            
            if($r->mobile_clockout_status == 'pending') {
                // $x4 += 1;
                $x++;
            }

            if($r->mobile_break1_out_status == 'pending') {
                // $x5 += 1;
                $x++;
            }

            if($r->mobile_break1_in_status == 'pending') {
                // $x5 += 1;
                $x++;
            }

            if($r->mobile_break2_out_status == 'pending') {
                // $x5 += 1;
                $x++;
            }

            if($r->mobile_break2_in_status == 'pending') {
                // $x5 += 1;
                $x++;
            }
            
            // $x = $x1 + $x2 + $x3 + $x4;
        }
    }
    
    return $x;
}

function todo_pending_leave_counter($emp_id, $company_id,$leave="all"){
    $_CI =& get_instance();
    
    if($leave == "normal") {
        $where = array(
            "el.company_id"                 => $company_id,
            "el.deleted"                    => '0',
            "ag.emp_id"                     => $emp_id,
            "el.leave_application_status"   =>"pending",
            "al.level !="                   => "",
            "epi.employee_status"           => "Active",
            "empl.status"                   =>  'Active',
            "el.work_schedule_id !="        => "-3"
        );
    } else if ($leave == "maternity") {
        $where = array(
            "el.company_id"                 => $company_id,
            "el.deleted"                    => '0',
            "ag.emp_id"                     => $emp_id,
            "el.leave_application_status"   =>"pending",
            "al.level !="                   => "",
            "epi.employee_status"           => "Active",
            "empl.status"                   =>  'Active',
            "el.work_schedule_id"           => "-3"
        );
    } else {
        $where = array(
            "el.company_id"                 => $company_id,
            "el.deleted"                    => '0',
            "ag.emp_id"                     => $emp_id,
            "el.leave_application_status"   =>"pending",
            "al.level !="                   => "",
            "epi.employee_status"           => "Active",
            "empl.status"                   =>  'Active'
        );
    }
    
    
    $s = array(
        "el.employee_leaves_application_id"
    );
    
    $_CI->db->select($s);
    $_CI->db->where($where);
    
    $_CI->db->join("employee_payroll_information AS epi","epi.emp_id = el.emp_id","LEFT");
    $_CI->db->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    // $_CI->db->join("approval_groups AS ag","agg.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
    $_CI->db->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
    $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND al.level = ag.level","LEFT");
    $_CI->db->join("employee_leaves AS empl","empl.leave_type_id = el.leave_type_id and empl.emp_id = el.emp_id","LEFT");
    $_CI->db->group_by('el.employee_leaves_application_id');
    $query = $_CI->db->get("employee_leaves_application AS el",11);
    
    return ($query->num_rows > 0) ? $query->num_rows : 0;
}

function todo_pending_overtime_counter($emp_id, $company_id){
    $_CI =& get_instance();
    
    $where = array(
        'o.company_id'          => $company_id,
        'o.deleted'             => '0',
        "ag.emp_id"             => $emp_id,
        "o.overtime_status"     => "pending",
        "ao.level !="           => "",
        "epi.employee_status"   => "Active"
    );
    
    $s = array(
        "o.overtime_id"
    );
    
    $_CI->db->select($s);
    $_CI->db->where($where);
    
    $_CI->db->join("employee_payroll_information AS epi","epi.emp_id = o.emp_id","LEFT");
    $_CI->db->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    // $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
    $_CI->db->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
    $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND ao.level = ag.level","LEFT");
    $_CI->db->group_by("o.overtime_id");
    $query = $_CI->db->get('employee_overtime_application AS o',11);
    
    return ($query->num_rows > 0) ? $query->num_rows : 0;
}

function todo_pending_shifts_counter($emp_id, $company_id){
    $_CI =& get_instance();
    
    $where = array(
        'ewsa.company_id'                       => $company_id,
        "ag.emp_id"                             => $emp_id,
        "ewsa.employee_work_schedule_status"    => "pending",
        "aws.level !="                          => ""
    );
    
    $s = array(
        "ewsa.employee_work_schedule_application_id"
    );
    
    $_CI->db->select($s);
    $_CI->db->where($where);
    
    $_CI->db->join("employee_payroll_information AS epi","epi.emp_id = ewsa.emp_id","LEFT");
    $_CI->db->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    // $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
    // $_CI->db->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
    $_CI->db->join("approval_work_schedule AS aws","aws.employee_work_schedule_application_id = ewsa.employee_work_schedule_application_id","LEFT");
    $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND aws.level = ag.level","LEFT");
    $_CI->db->group_by("ewsa.employee_work_schedule_application_id");
    $query = $_CI->db->get('employee_work_schedule_application AS ewsa');
    
    return ($query->num_rows > 0) ? $query->num_rows : 0;
}

function todo_pending_timein_current_late_counter($emp_id, $company_id, $flag_payroll_correction){
    $_CI =& get_instance();
    
    if($flag_payroll_correction == "current") {
        $payroll_correction = "no";
    } elseif ($flag_payroll_correction == "late") {
        $payroll_correction = "yes";
    } else {
        $payroll_correction = "no";
    }
    
    $where = array(
        'ee.comp_id'        => $company_id,
        'ee.status'         => 'Active',
        'ee.corrected'      => 'Yes',
        "ag.emp_id"         => $emp_id,
        'ee.time_in_status' => 'pending',
        //'ee.source !='      => 'mobile',
        "at.level !="       => "",
        'ee.flag_payroll_correction' => $payroll_correction
    );
    
    $s = array(
        "ee.employee_time_in_id"
    );
    
    $_CI->db->select($s);
    $_CI->db->where($where);
    $_CI->db->where(" (ee.source ='EP' OR ee.last_source='Adjusted') ",NULL,FALSE);
    
    $_CI->db->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
    $_CI->db->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    $_CI->db->join("approval_time_in AS at","at.approval_time_in_id= ee.approval_time_in_id","LEFT");
    $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND at.level = ag.level","LEFT");
    $_CI->db->group_by("ee.employee_time_in_id");
    $query = $_CI->db->get('employee_time_in AS ee',16);
    
    return ($query->num_rows > 0) ? $query->num_rows : 0;
}

function todo_pending_split_timein_current_late_counter($emp_id, $company_id,$flag_payroll_correction){
    $_CI =& get_instance();
    
    if($flag_payroll_correction == "current") {
        $payroll_correction = "no";
    } elseif ($flag_payroll_correction == "late") {
        $payroll_correction = "yes";
    } else {
        $payroll_correction = "no";
    }
    
    $where = array(
        'sbti.comp_id'          => $company_id,
        'sbti.status'           => 'Active',
        'sbti.corrected'        => 'Yes',
        "ag.emp_id"             => $emp_id,
        'sbti.time_in_status'   => 'pending',
        'sbti.source !='        => 'mobile',
        "at.level !="           => "",
        'sbti.flag_payroll_correction' => $payroll_correction
    );
    
    $s = array(
        "sbti.schedule_blocks_time_in_id"
    );
    
    $_CI->db->select($s);
    $_CI->db->where($where);
    
    $_CI->db->join('employee_payroll_information AS epi','epi.emp_id = sbti.emp_id','left');
    $_CI->db->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    $_CI->db->join("approval_time_in AS at","at.approval_time_in_id= sbti.approval_time_in_id","LEFT");
    $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND at.level = ag.level","LEFT");
    $_CI->db->group_by("sbti.schedule_blocks_time_in_id");
    $query = $_CI->db->get('schedule_blocks_time_in AS sbti',16);
    
    return ($query->num_rows > 0) ? $query->num_rows : 0;
}

function todo_pending_leave_current_late_counter($emp_id, $company_id,$flag_payroll_correction){
    $_CI =& get_instance();
    
    if($flag_payroll_correction == "current") {
        $payroll_correction = "no";
    } elseif ($flag_payroll_correction == "late") {
        $payroll_correction = "yes";
    } else {
        $payroll_correction = "no";
    }
    
    $where = array(
        "el.company_id"                 => $company_id,
        "el.deleted"                    => '0',
        "ag.emp_id"                     => $emp_id,
        "el.leave_application_status"   =>"pending",
        "al.level >="                   => "",
        "epi.employee_status"           => "Active",
        'el.flag_payroll_correction'    => $payroll_correction
    );
    
    $s = array(
        "el.employee_leaves_application_id"
    );
    
    $_CI->db->select($s);
    $_CI->db->where($where);
    
    $_CI->db->join("employee_payroll_information AS epi","epi.emp_id = el.emp_id","LEFT");
    $_CI->db->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
   # $_CI->db->join("approval_groups AS ag","agg.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
    $_CI->db->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
    $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND al.level = ag.level","LEFT");
    $_CI->db->group_by('el.employee_leaves_application_id');
    $query = $_CI->db->get("employee_leaves_application AS el",16);
    
    return ($query->num_rows > 0) ? $query->num_rows : 0;
}

function todo_pending_overtime_current_late_counter($emp_id, $company_id, $flag_payroll_correction){
    $_CI =& get_instance();
    
    if($flag_payroll_correction == "current") {
        $payroll_correction = "no";
    } elseif ($flag_payroll_correction == "late") {
        $payroll_correction = "yes";
    } else {
        $payroll_correction = "no";
    }
    
    $where = array(
        'o.company_id'                  => $company_id,
        'o.deleted'                     => '0',
        "ag.emp_id"                     => $emp_id,
        "o.overtime_status"             => "pending",
        "ao.level !="                   => "",
        "epi.employee_status"           => "Active",
        'o.flag_payroll_correction'     => $payroll_correction
    );
    
    $s = array(
        "o.overtime_id"
    );
    
    $_CI->db->select($s);
    $_CI->db->where($where);
    
    $_CI->db->join("employee_payroll_information AS epi","epi.emp_id = o.emp_id","LEFT");
    $_CI->db->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
   # $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
    $_CI->db->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
    $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND ao.level = ag.level","LEFT");
    $_CI->db->group_by("o.overtime_id");
    $query = $_CI->db->get('employee_overtime_application AS o');
    
    return ($query->num_rows > 0) ? $query->num_rows : 0;
}

function get_app_default_approver($company_id, $name) {
    $_CI =& get_instance();
    
    $w = array(
        "ag.company_id" => $company_id,
        "ap.name" => $name
    );
    
    $_CI->db->where($w);
    $_CI->db->where("(ag.emp_id = '-99{$company_id}' OR flag_default = 'yes')");
    $_CI->db->join("approval_process AS ap", "ap.approval_process_id = ag.approval_process_id", "LEFT");
    $q = $_CI->db->get("approval_groups AS ag");
    $r = $q->row();
    
    return ($r) ? $r : false;
    
}


function check_if_have_break_v2($emp_id, $comp_id,$work_sched_id,$date =""){
    if(!$date){
        $date = date('Y-m-d');
    }
    $CI =& get_instance();
    
    #$rest_day = get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
    if($work_sched_id == -1){
        return FALSE;
    }else{
        $work_schedule_info = work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
        
        if($work_schedule_info['work_schedule']['break_time']!=0){
            return TRUE;
        }else{
            return FALSE;
        }
    }
}

function work_schedule_info($company_id,$work_schedule_id,$weekday){
    $CI =& get_instance();
    
    $wd = date("l",strtotime($weekday));
    $break_time = 0;
    
    $uww = array(
        "uw.days_of_work"       => $wd,
        "uw.company_id"         => $company_id,
        "uw.work_schedule_id"   => $work_schedule_id,
        "uw.status"             => "Active"
    );
    
    $s = array("*");
    $CI->db->where($uww);
    $CI->db->join("work_schedule AS ws","ws.work_schedule_id = uw.work_schedule_id","LEFT");
    $uwq = $CI->db->get("regular_schedule AS uw");
    $uwr = $uwq->row();
    
    if($uwr){
        $start_time             = ($uwr->latest_time_in_allowed != NULL || $uwr->latest_time_in_allowed != "") ? $uwr->work_start_time : $uwr->work_start_time;
        $end_time               = $uwr->work_end_time;
        $shift_name             = $uwr->name;
        $total_hours            = $uwr->total_work_hours;
        $total_days_per_year    = '312';
        $required               = 1;
        $flexible               = FALSE;
        
        if(($uwr->break_in_min != NULL) || ($uwr->break_in_min != "" )){
            $break_time = $uwr->break_in_min;
        }
    }else{
        $fw = array(
            "f.company_id"=>$company_id,
            "f.work_schedule_id"=>$work_schedule_id
        );
        
        $CI->db->where($fw);
        $CI->db->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
        $fq = $CI->db->get("flexible_hours AS f");
        $fr = $fq->row();
        
        if($fr){
            $break_time = $fr->duration_of_lunch_break_per_day;
            if($fr->latest_time_in_allowed != NULL || $fr->latest_time_in_allowed != ""){
                $start_time             = $fr->latest_time_in_allowed;
                $end_time               = "";
                $shift_name             = $fr->name;
                $total_hours            = $fr->total_hours_for_the_day;
                $total_days_per_year    = '312';
                $required               = $fr->not_required_login;
                $flexible               = TRUE;
            }else{
                $start_time             = "";
                $end_time               = "";
                $shift_name             = $fr->name;
                $total_hours            = $fr->total_hours_for_the_day;
                $total_days_per_year    = '312';
                $required               = $fr->not_required_login;
                $flexible               = TRUE;
            }
        }else{
            $start_time             = "";
            $end_time               = "";
            $shift_name             = "";
            $total_hours            = "";
            $total_days_per_year    = "";
            $required               = 0;
            $flexible               = FALSE;
        }
    }
    
    $data["work_schedule"] = array(
        "start_time"            => $start_time,
        "end_time"              => $end_time,
        "shift_name"            => $shift_name,
        "total_hours"           => $total_hours,
        "total_days_per_year"   => $total_days_per_year,
        "break_time"            => $break_time,
        "login"                 => $required,
        "flexible"              => $flexible,
        "required"              => $required
    );
    
    return $data;   
}

function get_work_schedule_migrated($company_id){
    $CI =& get_instance();
    
    $row_array  = array();
    
    $s = array(
        "work_schedule_id",
        "work_type_name",
        "name",
        "flag_custom",
        "status",
        "default",
        "category_id",
        "employees_required",
        "notes",
        "bg_color",
        "break_rules",
        "assumed_breaks",
        "advanced_settings",
        "account_id",
        "enable_lunch_break",
        "break_type_1",
        "track_break_1",
        "break_schedule_1",
        "break_started_after",
        #"subtract_breaks_1",
        "enable_additional_breaks",
        "num_of_additional_breaks",
        "break_type_2",
        "track_break_2",
        "break_schedule_2",
        "additional_break_started_after_1",
        "additional_break_started_after_2",
        #"subtract_breaks_2",
        "enable_shift_threshold",
        "enable_grace_period",
        "tardiness_rule",
        "flag_migrate",
    );
    
    $CI->db->select($s);
    $where = array(
        'comp_id'      => $company_id,
    );
    
    $CI->db->where($where);
    $q = $CI->db->get('work_schedule');
    $result = $q->result();
    
    if($result){
        foreach ($result as $r1){
            $wd     = array(
                "work_schedule_id"                  => $r1->work_schedule_id,
                "work_type_name"                    => $r1->work_type_name,
                "name"                              => $r1->name,
                "flag_custom"                       => $r1->flag_custom,
                "status"                            => $r1->status,
                "default"                           => $r1->default,
                "category_id"                       => $r1->category_id,
                "employees_required"                => $r1->employees_required,
                "notes"                             => $r1->notes,
                "bg_color"                          => $r1->bg_color,
                "break_rules"                       => $r1->break_rules,
                "assumed_breaks"                    => $r1->assumed_breaks,
                "advanced_settings"                 => $r1->advanced_settings,
                "account_id"                        => $r1->account_id,
                "enable_lunch_break"                => $r1->enable_lunch_break,
                "break_type_1"                      => $r1->break_type_1,
                "track_break_1"                     => $r1->track_break_1,
                "break_schedule_1"                  => $r1->break_schedule_1,
                "break_started_after"               => $r1->break_started_after,
                #"subtract_breaks_1"                 => $r1->subtract_breaks_1,
                "enable_additional_breaks"          => $r1->enable_additional_breaks,
                "num_of_additional_breaks"          => $r1->num_of_additional_breaks,
                "break_type_2"                      => $r1->break_type_2,
                "track_break_2"                     => $r1->track_break_2,
                "break_schedule_2"                  => $r1->break_schedule_2,
                "additional_break_started_after_1"  => $r1->additional_break_started_after_1,
                "additional_break_started_after_2"  => $r1->additional_break_started_after_2,
                #"subtract_breaks_2"                 => $r1->subtract_breaks_2,
                "enable_shift_threshold"            => $r1->enable_shift_threshold,
                "enable_grace_period"               => $r1->enable_grace_period,
                "tardiness_rule"                    => $r1->tardiness_rule,
                "flag_migrate"                      => $r1->flag_migrate,
                "custom_search"                     => "worksched_id_{$r1->work_schedule_id}",
                "custom_searchv2"                   => "worksched_migrate_{$r1->default}_{$r1->flag_migrate}",
            );
            
            array_push($row_array,$wd);
        }
    }
    return $row_array;
}

function tardiness_rule_migrated_v3($company_id,$last_t_worksched_id){
    $get_work_schedule = get_work_schedule_migrated($company_id);
    
    $arr    = false;
    $r      = in_array_foreach_custom("worksched_migrate_1_1",$get_work_schedule);
    $r1     = in_array_custom("worksched_id_{$last_t_worksched_id}",$get_work_schedule);
    $cat_id = "";
    
    if($r1){
        $cat_id = $r1->category_id;
    }
    if(!$cat_id){
        $cat_id = $last_t_worksched_id;
    }
    
    if($r){
        foreach ($r as $key => $value) {
            if($value->work_schedule_id == $cat_id){
                $arr = true;
                break;
            }
        }
    }
    return $arr;
}

function for_list_tardiness_rule_migrated_v3($company_id){
    $get_work_schedule = get_work_schedule_migrated($company_id);
    
    $r = in_array_foreach_custom("worksched_migrate_1_1",$get_work_schedule);
    
    if($r){
        $arr = true;
    }
    return ($r) ? true : false;
}

function get_schedule_settings_by_workschedule_id($workschedule_id,$company_id,$workday){
    $CI =& get_instance();
    
    $sel = array(
        "*",
        "rs.break_1 AS break_1_in_min",
        "rs.break_2 AS break_2_in_min"
    );
    
    $where = array(
        "ws.comp_id"           => $company_id,
        "ws.work_schedule_id"  => $workschedule_id,
        "ws.status"            => "Active",
        "rs.days_of_work"      => $workday
    );
    
    $CI->db->select($sel);
    $CI->db->where($where);
    $CI->db->join("regular_schedule AS rs","rs.work_schedule_id = ws.work_schedule_id","LEFT");
    $q = $CI->db->get("work_schedule AS ws");
    $r = $q->row();
    
    return ($r) ? $r : false;
}

function check_employee_work_schedule_by_emp_id($emp_id,$company_id,$min, $max){
    $CI =& get_instance();
    $res = array();
    $s = array(
        "work_schedule_id",
        "valid_from",
        "until"
    );
    
    $w = array(
        "emp_id"=>$emp_id,
        "company_id"=>$company_id,
        "valid_from >="=>$min,
        "until <="=>$max,
        "status"=>"Active",
        "payroll_group_id" => 0
    );
    
    $CI->db->select($s);
    $CI->db->where($w);
    $q = $CI->db->get("employee_shifts_schedule");
    $r = $q->result();
    
    if($r){
        foreach ($r as $row) {
            $temp = array(
                "work_schedule_id" => $row->work_schedule_id,
                "valid_from" => $row->valid_from,
                "until" => $row->until,
                "filter_query" => "date-{$row->valid_from}"
            );
            
            array_push($res, $temp);
        }
        return $res;
    }
}

function count_emp_missed_punches($emp_id, $comp_id,$check_employee_work_schedule_v2,$work_schedule_id_pg,$next_pay_period,$emp_check_rest_day,$get_holidays){
    $CI =& get_instance();
    $CI->load->model('employee/employee_work_schedule_model','ews');
    
    $period = $next_pay_period;
    $count = 0;
    $count_fritz = 0;
    $count_split = 0;
    if($period){
        $date = date('Y-m-d', strtotime($period->cut_off_from));
        $today = date('Y-m-d', strtotime('-1 day'));
        
        if(strtotime($today) > strtotime($period->first_payroll_date)){
            $today = date('Y-m-d', strtotime($period->first_payroll_date));
        }
        
        $cut_off_date = dateRange($date, $today);
        $check_emp_time_in = check_emp_time_in($emp_id,$date,$today);
        $check_emp_time_in_split = check_emp_time_in_split($emp_id,$today,$date);
        
        foreach ($cut_off_date as $all_date) {
            $date = $all_date;
            $work_sched_id1 = in_array_custom("date-{$date}", $check_employee_work_schedule_v2);
            if($work_sched_id1) {
                $work_sched_id = $work_sched_id1->work_schedule_id;
            } else {
                $work_sched_id = $work_schedule_id_pg;
            }
            
            $is_break_assumed = is_break_assumed($work_sched_id);
            
            if(!$work_sched_id){
                return false; break;
            }
            
            $rest_date = date('l',strtotime($date));
            $rest_day = in_array_custom("work_schedule_id-{$work_sched_id}{$rest_date}", $emp_check_rest_day);
            $check_holiday = in_array_custom("date-{$date}", $get_holidays);
            $work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)),$date,$emp_id);
            
            if($check_holiday) {
                if($check_holiday->hour_type_name == "Regular Holiday") {
                    $valid_holiday = true;
                    $check_unwork_holiday_pay = check_unwork_holiday_pay($emp_id, $comp_id, "regular");
                    if($check_unwork_holiday_pay) {
                        $valid_holiday = false;
                    }
                } elseif($check_holiday->hour_type_name == "Special Holiday") {
                    $valid_holiday = true;
                    $check_unwork_holiday_pay = check_unwork_holiday_pay($emp_id, $comp_id, "special");
                    if($check_unwork_holiday_pay) {
                        $valid_holiday = false;
                    }
                } else {
                    $valid_holiday = false;
                }
            } else {
                $valid_holiday = false;
            }
            
            if(!$work_schedule_info) break;
            $leave = $CI->ews->check_employee_leave_application($date, $emp_id);
            $leave_check = ($leave) ? TRUE: FALSE;
            
            $tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($comp_id,$work_sched_id);
            $get_schedule_settings = get_schedule_settings_by_workschedule_id($work_sched_id,$comp_id,date("l", strtotime($date)));
            
            if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
                $time_in = in_array_custom("date-{$date}", $check_emp_time_in);
                
                if($time_in){
                    if(!$rest_day && !$valid_holiday){                            
                        if($time_in->time_in != NULL && ($time_in->lunch_out == NULL || $time_in->lunch_in == NULL || $time_in->time_out == NULL)) {
                            if($time_in->time_out == NULL) {
                                if($work_schedule_info['work_schedule']['split'] == true) {
                                    $blocks = $work_schedule_info['work_schedule']['sched_blocks'];
                                    if($blocks) {
                                        foreach ($blocks as $key=>$r) {
                                            $time_in_split = in_array_custom("date-{$date}{$r}", $check_emp_time_in_split);
                                            $get_schedule_block = get_schedule_block($r,$work_sched_id,$comp_id);
                                            $lunch = ($get_schedule_block->break_in_min > 0) ? TRUE : FALSE;
                                            
                                            if($time_in_split) {
                                                if($lunch) {
                                                    $count_split = $count_split + 1;
                                                }
                                            }
                                        }
                                    }
                                   
                                } else {
                                    if($work_schedule_info['work_schedule']['break_time'] != 0){
                                        if($tardiness_rule_migrated_v3) {
                                            if($get_schedule_settings) {
                                                if($get_schedule_settings->enable_lunch_break == "yes") {
                                                    if($get_schedule_settings->track_break_1 == "yes") {
                                                        if($time_in->lunch_out == NULL)  $count_fritz = $count_fritz + 1;
                                                        if($time_in->lunch_in == NULL)  $count_fritz = $count_fritz + 1;
                                                    }
                                                }
                                                
                                                if($get_schedule_settings->enable_additional_breaks == "yes") {
                                                    if($get_schedule_settings->track_break_2 == "yes") {
                                                        if($get_schedule_settings->num_of_additional_breaks == 2) {
                                                            if($time_in->break1_out == NULL)  $count_fritz = $count_fritz + 1;
                                                            if($time_in->break1_in == NULL)  $count_fritz = $count_fritz + 1;
                                                            
                                                            if($time_in->break2_out == NULL)  $count_fritz = $count_fritz + 1;
                                                            if($time_in->break2_in == NULL)  $count_fritz = $count_fritz + 1;
                                                        }
                                                        
                                                        if($get_schedule_settings->num_of_additional_breaks == 1) {
                                                            if($time_in->break1_out == NULL)  $count_fritz = $count_fritz + 1;
                                                            if($time_in->break1_in == NULL)  $count_fritz = $count_fritz + 1;
                                                        }
                                                        
                                                    }
                                                }
                                            }
                                        } else {
                                            if($is_break_assumed) {
                                                if($is_break_assumed->break_rules != "assumed") {
                                                    if($time_in->lunch_out == NULL) {
                                                        $count_fritz = $count_fritz + 1;
                                                    }
                                                    
                                                    if($time_in->lunch_in == NULL){
                                                        $count_fritz = $count_fritz + 1;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
                                    if($time_in->time_out == NULL) {
                                        $count_fritz = $count_fritz + 1;
                                    }
                                    
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    return $count_fritz + $count_split;
}

function get_emp_payslips($comp_id,$date){
    $CI =& get_instance();
    $hw_array = array();
    
    $where = array(
        "company_id"=>$comp_id,
        "payroll_date >" => $date
    );
    
    $CI->db->where($where);
    $CI->db->order_by('payroll_date','DESC');
    $q = $CI->db->get("payroll_payslip");
    $r = $q->result();
    
    if($r) {
        foreach($r as $row){
            $wd = array(
                "payroll_date" => $row->payroll_date,
                "period_from" => $row->period_from,
                "period_to" => $row->period_to,
                "company_id" => $row->company_id,
                "emp_id" => $row->emp_id,
                "payroll_payslip_id" => $row->payroll_payslip_id,
                "filter_query" => "emp_id-{$row->emp_id}"
            );
            
            array_push($hw_array,$wd);
        }
        
        return $hw_array;
    } else {
        return false;
    }
}

function emp_next_pay_period($comp_id,$emp_id,$get_payslips){
    $CI =& get_instance();
    $today = date('Y-m-d');
    
    $sel = array(
        "cut_off_from",
        "first_payroll_date",
        "cut_off_to"
    );
    
    $where = array(
        'cut_off_from <='=> $today,
        'first_payroll_date >='=> $today,
        'company_id' => $comp_id
    );
    
    $CI->db->select($sel);
    $CI->db->where($where);
    $q = $CI->db->get('payroll_calendar');
    $r = $q->row();
    
    if($r){
        $date = $r->first_payroll_date;
        $payslips = in_array_foreach_custom("emp_id-{$emp_id}",$get_payslips);
        
        $flag = false;
        if($payslips){
            $count = 0;
            foreach($payslips as $pay){
                $payroll_date = $pay->payroll_date;
                $period_from = $pay->period_from;
                $period_to = $pay->period_to;
                
                $check_approval_date = check_approval_date($payroll_date, $period_from, $period_to, $comp_id);
                
                if($check_approval_date){
                    if(date('Y-m-d', strtotime($pay->payroll_date)) == date('Y-m-d', strtotime($date))){
                        $flag = true;
                        $today = date('Y-m-d', strtotime($pay->payroll_date.' +1 day'));
                        break;
                    }
                }
            }
        }
        
        
        if($flag){
            $where1 = array(
                'cut_off_from <='=> $today,
                'first_payroll_date >='=> $today,
                'company_id' => $comp_id
            );
            $CI->db->where($where1);
            $q1 = $CI->db->get('payroll_calendar');
            $r1 = $q1->row();
            
            return ($r1) ? $r1 : FALSE;
        }else{
            
            return $r;
        }
    }else{
        return FALSE;
    }
}

function emp_pending_timesheet($emp_id, $comp_id,$next_pay_period,$check_employee_work_schedule_v2,$work_schedule_id_pg){
    $CI =& get_instance();
    $CI->load->model('employee/employee_work_schedule_model','ews');
    $count = 0;
    $count_split = 0;
    $period = $next_pay_period;
    
    if($period){
        $date = date('Y-m-d', strtotime($period->cut_off_from));
        $today = date('Y-m-d');
        if(strtotime($today) > strtotime($period->first_payroll_date)){
            $today = date('Y-m-d', strtotime($period->first_payroll_date));
        }
        
        $cut_off_date = dateRange($date, $today);
        $check_emp_time_in = check_emp_time_in($emp_id,$date,$today,"pending");
        $check_emp_time_in_split = check_emp_time_in_split($emp_id,$date,$today,"pending");
        
        foreach ($cut_off_date as $all_date) {
            $date = $all_date;
            
            $work_sched_id1 = in_array_custom("date-{$date}", $check_employee_work_schedule_v2);
            if($work_sched_id1) {
                $work_sched_id = $work_sched_id1->work_schedule_id;
            } else {
                $work_sched_id = $work_schedule_id_pg;
            }
            
            if(!$work_sched_id){
                return false; break;
            }
            
            $work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)),$date,$emp_id);
            
            if(!$work_schedule_info) break;
            
            if($work_schedule_info['work_schedule']['shift_name']!= ""){
                $r = in_array_custom("date-{$date}", $check_emp_time_in);
                
                if($r){
                    if($work_schedule_info['work_schedule']['split'] == true) {
                        $blocks = $work_schedule_info['work_schedule']['sched_blocks'];
                        if($blocks) {
                            foreach ($blocks as $key=>$r) {
                                $time_in_split = in_array_custom("date-{$date}{$r}", $check_emp_time_in_split);
                                if($time_in_split) {
                                    $count_split = $count_split + 1;
                                }
                            }
                        }
                    } else {
                        $count = $count + 1;
                    }
                }
            }
        }
    }
    return $count + $count_split;
}

function emp_check_rest_day($comp_id) {
    $CI =& get_instance();
    
    $res = array();
    $w = array(
        #"work_schedule_id"=>$work_schedule_id,
        "company_id"=>$comp_id,
        "status" => "Active"
        #"rest_day"=>$workday
    );
    $CI->db->where($w);
    $q = $CI->db->get("rest_day");
    $r = $q->result();
    
    if($r){
        foreach ($r as $row) {
            $temp = array(
                "company_id" => $row->company_id,
                "rest_day" => $row->rest_day,
                "work_schedule_id" => $row->work_schedule_id,
                "filter_query" => "work_schedule_id-{$row->work_schedule_id}{$row->rest_day}"
            );
            
            array_push($res, $temp);
        }
        return $res;
    }
}

function emp_rejected_timesheet($emp_id, $comp_id,$check_employee_work_schedule_v2,$work_schedule_id_pg,$next_pay_period,$emp_check_rest_day){
    $CI =& get_instance();
    $CI->load->model('employee/employee_work_schedule_model','ews');
    
    $period = $next_pay_period;
    $count = 0;
    $count_split = 0;
    if($period){
        $date = date('Y-m-d', strtotime($period->cut_off_from));
        $today = date('Y-m-d');
        if(strtotime($today) > strtotime($period->first_payroll_date)){
            $today = date('Y-m-d', strtotime($period->first_payroll_date));
        }
        
        $cut_off_date = dateRange($date, $today);
        $check_emp_time_in = check_emp_time_in($emp_id,$date,$today,"reject");
        $check_emp_time_in_split = check_emp_time_in_split($emp_id,$date,$today,"reject");
        
        foreach ($cut_off_date as $all_date) {
            $date = $all_date;
            $work_sched_id1 = in_array_custom("date-{$date}", $check_employee_work_schedule_v2);
            if($work_sched_id1) {
                $work_sched_id = $work_sched_id1->work_schedule_id;
            } else {
                $work_sched_id = $work_schedule_id_pg;
            }
            if(!$work_sched_id){
                return false; break;
            }
            
            $rest_date = date('l',strtotime($date));
            $rest_day = in_array_custom("work_schedule_id-{$work_sched_id}{$rest_date}", $emp_check_rest_day);
            if(!$rest_day){
                $work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)),$date,$emp_id);
                if(!$work_schedule_info) break;
                
                if($work_schedule_info['work_schedule']['shift_name']!= ""){
                    $r = in_array_custom("date-{$date}", $check_emp_time_in);
                    
                    if($work_schedule_info['work_schedule']['split'] == true) {
                        $blocks = $work_schedule_info['work_schedule']['sched_blocks'];
                        
                        if($blocks) {
                            foreach ($blocks as $key=>$row) {
                                $time_in_split = in_array_custom("date-{$date}{$row}", $check_emp_time_in_split);
                                
                                if($time_in_split) {
                                    $count_split = $count_split + 1;
                                }
                            }
                        }
                    } else {
                        if($r){
                            $count = $count + 1;
                        }
                        
                    }
                }
            }
        }
    }
    return $count + $count_split;
}

function get_holidays($comp_id){
    $CI =& get_instance();
    $res = array();
    
    $sel = array(
        "hol.date",
        "hol.company_id",
        "hol.holiday_name",
        "ht.hour_type_name",
        "hol.repeat_type"
    );
    
    $w = array(
        'hol.company_id'=> $comp_id,
        'hol.status' => 'Active'
    );
    
    $CI->db->select($sel);
    $CI->db->where($w);
    $CI->db->join("hours_type AS ht","ht.hour_type_id = hol.hour_type_id","LEFT");
    $q = $CI->db->get('holiday AS hol');
    $r = $q->result();
    
    if($r){
        foreach ($r as $row) {
            $date_h = $row->date;
            
            if($row->repeat_type == "yes") {
                $date_h = date("Y").'-'.date("m-d", strtotime($row->date));
            }
            
            $temp = array(
                "date" => $date_h,
                "company_id" => $row->company_id,
                "holiday_name" => $row->holiday_name,
                "hour_type_name" => $row->hour_type_name,
                "repeat_type" => $row->repeat_type,
                "filter_query" => "date-{$date_h}"
            );
            
            array_push($res, $temp);
        }
        return $res;
    } else {
        return FALSE;
    }
    
    #return ($r) ? TRUE: FALSE;
}

function emp_missing_timesheet($emp_id, $comp_id,$check_employee_work_schedule_v2,$work_schedule_id_pg,$next_period,$emp_check_rest_day,$get_holidays){
    $CI =& get_instance();
    $CI->load->model('employee/employee_work_schedule_model','ews');
    
    $period = $next_period;
    $count = 0;
    $count_split = 0;
    $get_holiday_date_v2 = $get_holidays;
    $check_unwork_holiday_pay = check_unwork_holiday_pay($emp_id, $comp_id, "regular");
    
    if($period){
        $today = date('Y-m-d', strtotime($period->cut_off_from));
        $date = date('Y-m-d', strtotime('-1 day'));
        if(strtotime($date) > strtotime($period->first_payroll_date)){
            $date = date('Y-m-d', strtotime($period->first_payroll_date));
        }
        
        $cut_off_date = dateRange($today,$date);
        $check_emp_time_in = check_emp_time_in($emp_id,$today,$date);
        $check_emp_time_in_split = check_emp_time_in_split($emp_id,$today,$date);
        
        foreach ($cut_off_date as $all_date) {
            $date = $all_date;
            
            $work_sched_id1 = in_array_custom("date-{$date}", $check_employee_work_schedule_v2);
            if($work_sched_id1) {
                $work_sched_id = $work_sched_id1->work_schedule_id;
            } else {
                $work_sched_id = $work_schedule_id_pg;
            }
            
            if(!$work_sched_id){
                return false; break;
            }
            $rest_date = date('l',strtotime($date));
            $rest_day = in_array_custom("work_schedule_id-{$work_sched_id}{$rest_date}", $emp_check_rest_day);
            
            $for_holiday_date_m_d = date("m-d", strtotime($date));
            $check_holiday = in_array_custom("date-{$for_holiday_date_m_d}", $get_holiday_date_v2);
            
            if($check_holiday){
                #if($is_holiday_q->repeat_type == "no"){
                if($check_holiday->date_type == "fixed") {
                    #$cur_year = date("Y");
                    #$hol_year = date("Y",strtotime($for_holiday_date));
                    
                    $app_m_d = date("m-d",strtotime($date));
                    $hol_m_d = date("m-d",strtotime($check_holiday->date));
                    
                    if($app_m_d == $app_m_d){
                        #if($cur_year == $hol_year){
                        $is_hol = true;
                    } else {
                        $is_hol = false;
                    }
                } else {
                    $is_hol = true;
                }
            } else {
                $is_hol = false;
            }
            
            $valid_holiday = false;
            
            if($is_hol) {
                if($check_holiday) {
                    if($check_holiday->hour_type_name == "Regular Holiday") {
                        $valid_holiday = true;
                        $check_unwork_holiday_pay = check_unwork_holiday_pay($emp_id, $comp_id, "regular");
                        if($check_unwork_holiday_pay) {
                            $valid_holiday = false;
                        }
                    } elseif($check_holiday->hour_type_name == "Special Holiday") {
                        $valid_holiday = true;
                        $check_unwork_holiday_pay = check_unwork_holiday_pay($emp_id, $comp_id, "special");
                        if($check_unwork_holiday_pay) {
                            $valid_holiday = false;
                        }
                    } else {
                        $valid_holiday = false;
                    }
                }
            }
            
            if(!$rest_day && !$valid_holiday){
                $work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)),$date,$emp_id);
                $leave = $CI->ews->check_employee_leave_application($date, $emp_id);
                $leave_check = ($leave) ? TRUE: FALSE;
                
                if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
                    if($work_schedule_info['work_schedule']['split'] == true) {
                        $time_in = false;
                    } else {
                        $time_in = in_array_custom("date-{$date}", $check_emp_time_in);
                    }
                    
                    if(!$time_in){
                        if(!$rest_day && !$valid_holiday){
                            if($work_schedule_info['work_schedule']['split'] == true) {
                                $blocks = $work_schedule_info['work_schedule']['sched_blocks'];
                                if($blocks) {
                                    foreach ($blocks as $key=>$r) {
                                        $time_in_split = in_array_custom("date-{$date}{$r}", $check_emp_time_in_split);
                                        
                                        if(!$time_in_split) {
                                            $count_split = 0;//$count_split + 1;
                                        }
                                    }
                                }
                            } else {
                                if($work_schedule_info['work_schedule']['split'] != true) {
                                    $count += 1;

                                    #p($date.' '.$count);
                                }
                                
                            }
                        }
                    }
                }
            }
        }
    }
    
    return $count + $count_split;
}

function check_if_enable_breaks_on_holiday($comp_id,$work_schedule_id,$split=false,$schedule_block_id=null){
    $CI =& get_instance();
    
    if($split) {
        $w = array(
            "work_schedule_id" => $work_schedule_id,
            "company_id" => $comp_id,
            "schedule_blocks_id" => $schedule_block_id
        );
        
        $CI->db->where($w);
        $q = $CI->db->get('schedule_blocks');
        $r = $q->row();
        
        if($r) {
            if($r->enable_breaks_on_holiday == "yes") {
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    } else {
        $w = array(
            "work_schedule_id" => $work_schedule_id,
            "comp_id" => $comp_id
        );
        
        $CI->db->where($w);
        $q = $CI->db->get('work_schedule');
        $r = $q->row();
        
        if($r) {
            if($r->enable_breaks_on_holiday == "yes") {
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }
    
}

function check_emp_time_in($emp_id,$min,$max,$time_in_status = ""){
    $CI =& get_instance();
    
    if($time_in_status == "pending") {
        $where = array(
            'emp_id' =>$emp_id,
            'status' => 'Active',
            'time_in_status' =>'pending'
        );
    } elseif ($time_in_status == "reject") {
        $CI->db->where("((time_in_status IS NULL and last_source IS NULL and corrected = 'Yes') 
                        OR (time_in_status = 'reject'))");
        $where = array(
            'emp_id' =>$emp_id,
            'status' => 'Active',
            #'time_in_status' =>'reject'
        );
    } elseif ($time_in_status == "tardiness") {
        $where = array(
            'tardiness_min >' => '0',
            'emp_id' =>$emp_id,
            'status' => 'Active'
        );
        
        $CI->db->where("(time_in_status != 'reject' OR time_in_status IS NULL)");
    } elseif ($time_in_status == "undertime") {
        $where = array(
            'undertime_min >' => '0',
            'emp_id' =>$emp_id,
            'status' => 'Active'
        );
        
        $CI->db->where("(time_in_status != 'reject' OR time_in_status IS NULL)");
    } else {
        $where = array(
            'emp_id' =>$emp_id,
            'status' => 'Active'
        );
        
        $CI->db->where("(time_in_status != 'reject' OR time_in_status IS NULL)");
    }
    
    $CI->db->where($where);
    
    if($min && $max){
        $CI->db->where("date >= '".$min."' AND date <= '".$max."'");
    }
    
    $q = $CI->db->get('employee_time_in');
    $r = $q->result();
    
    $res = array();
    
    if($r){
        foreach ($r as $row) {
            $temp = array(
                "date" => $row->date,
                "comp_id" => $row->comp_id,
                "emp_id" => $row->emp_id,
                "time_in" => $row->time_in,
                "lunch_out" => $row->lunch_out,
                "lunch_in" => $row->lunch_in,
                "time_out" => $row->time_out,
                "tardiness_min" => $row->tardiness_min,
                "undertime_min" => $row->undertime_min,
                "late_min" => $row->late_min,
                "absent_min" => $row->absent_min,
                "overbreak_min" => $row->overbreak_min,
                "total_hours_required" => $row->total_hours_required,
                "time_in_status" => $row->time_in_status,
                "employee_time_in_id" => $row->employee_time_in_id,
                
                "break1_out" => $row->break1_out,
                "break1_in" => $row->break1_in,
                "break2_out" => $row->break2_out,
                "break2_in" => $row->break2_in,
                
                "filter_query" => "date-{$row->date}"
            );
            
            array_push($res, $temp);
        }
        return $res;
    } else {
        return FALSE;
    }
}

function count_emp_absences($emp_id, $comp_id,$check_employee_work_schedule_v2,$work_schedule_id_pg,$next_period,$emp_check_rest_day,$get_holidays){
    $CI =& get_instance();
    $CI->load->model('employee/employee_work_schedule_model','ews');
    
    $period = $next_period;
    $count = 0;
    $absent_min = 0;
    if($period){
        $date = date('Y-m-d', strtotime($period->cut_off_from));
        $today1 = date('Y-m-d');
        $today = date('Y-m-d', strtotime($today1." -1 day"));
        if(strtotime($today) > strtotime($period->first_payroll_date)){
            $today = date('Y-m-d', strtotime($period->first_payroll_date));
        }
        
        $cut_off_date = dateRange($date, $today);
        $check_emp_time_in = check_emp_time_in($emp_id,$date,$today);
        
        foreach ($cut_off_date as $all_date) {
            $date = $all_date;
            $work_sched_id1 = in_array_custom("date-{$date}", $check_employee_work_schedule_v2);
            
            if($work_sched_id1) {
                $work_sched_id = $work_sched_id1->work_schedule_id;
            } else {
                $work_sched_id = $work_schedule_id_pg;
            }
            
            if(!$work_sched_id){
                return false; break;
            }
            $rest_date = date('l',strtotime($date));
            $rest_day = in_array_custom("work_schedule_id-{$work_sched_id}{$rest_date}", $emp_check_rest_day);
            #$check_holiday = in_array_custom("date-{$date}", $get_holidays);
            
            
            $for_holiday_date_m_d = date("m-d", strtotime($date));
            $check_holiday = in_array_custom("date-{$for_holiday_date_m_d}", $get_holidays);
            
            if($check_holiday){
                #if($is_holiday_q->repeat_type == "no"){
                if($check_holiday->date_type == "fixed") {
                    #$cur_year = date("Y");
                    #$hol_year = date("Y",strtotime($for_holiday_date));
                    
                    $app_m_d = date("m-d",strtotime($date));
                    $hol_m_d = date("m-d",strtotime($check_holiday->date));
                    
                    if($app_m_d == $app_m_d){
                        #if($cur_year == $hol_year){
                        $is_hol = true;
                    } else {
                        $is_hol = false;
                    }
                } else {
                    $is_hol = true;
                }
            } else {
                $is_hol = false;
            }
            
            $valid_holiday = false;
            
            if($is_hol) {
                if($check_holiday) {
                    if($check_holiday->hour_type_name == "Regular Holiday") {
                        $valid_holiday = true;
                        $check_unwork_holiday_pay = check_unwork_holiday_pay($emp_id, $comp_id, "regular");
                        if($check_unwork_holiday_pay) {
                            $valid_holiday = false;
                        }
                    } elseif($check_holiday->hour_type_name == "Special Holiday") {
                        $valid_holiday = true;
                        $check_unwork_holiday_pay = check_unwork_holiday_pay($emp_id, $comp_id, "special");
                        if($check_unwork_holiday_pay) {
                            $valid_holiday = false;
                        }
                    } else {
                        $valid_holiday = false;
                    }
                }
            }
            
            
            
            #if(!$rest_day && !$check_holiday){
            if(!$rest_day && !$valid_holiday){
                $work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)),$date,$emp_id);
                
                $leave = $CI->ews->check_employee_leave_application($date, $emp_id);
                $leave_check = ($leave) ? TRUE: FALSE;
                
                if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
                    $time_in = in_array_custom("date-{$date}", $check_emp_time_in);
                    $absent_min += ($time_in) ? $time_in->absent_min / 60 : 0;
                    
                    if(!$time_in){
                        $count = $count + $work_schedule_info['work_schedule']['total_hours'];
                    }
                    
                }
            }
        }
    }
    return ($count == 0) ? $count : number_format(($count + $absent_min),2,'.',',');
}

function count_emp_tardiness($emp_id, $comp_id,$next_period){
    $CI =& get_instance();
    $CI->load->model('employee/employee_model','employee');
    
    $period = $next_period;
    $count = 0;
    if($period){
        $date = date('Y-m-d', strtotime($period->cut_off_from));
        $today = date('Y-m-d');
        if(strtotime($today) > strtotime($period->first_payroll_date)){
            $today = date('Y-m-d', strtotime($period->first_payroll_date));
        }
        
        $cut_off_date = dateRange($date, $today);
        $check_emp_time_in = check_emp_time_in($emp_id,$date,$today);
        
        foreach ($cut_off_date as $all_date) {
            $date = $all_date;
            $r = in_array_custom("date-{$date}", $check_emp_time_in);
            
            if($r){
                $check_employee_leave_application = $CI->employee->check_employee_leave_application($r->emp_id,$date);
                
                if($check_employee_leave_application) {
                    if($check_employee_leave_application["info"]["tardiness"] != "") {
                        $tardi_with_leave = ($r->tardiness_min) - ($check_employee_leave_application["info"]["credited"] * 8) * 60;
                    } else {
                        $tardi_with_leave = $r->tardiness_min;
                    }
                } else {
                    $tardi_with_leave = $r->tardiness_min;
                }
                
                $count = $count + $tardi_with_leave;
            }
        }
    }
    return ($count == 0) ? $count : number_format(($count/60),2,'.',',');
}

function count_emp_undertime($emp_id, $comp_id, $next_period){
    $CI =& get_instance();
    
    $CI->load->model('employee/employee_model','employee');
    
    $period = $next_period;
    $count = 0;
    if($period){
        $date = date('Y-m-d', strtotime($period->cut_off_from));
        $today = date('Y-m-d');
        
        if(strtotime($today) > strtotime($period->first_payroll_date)){
            $today = date('Y-m-d', strtotime($period->first_payroll_date));
        }
        
        $cut_off_date = dateRange($date, $today);
        $check_emp_time_in = check_emp_time_in($emp_id,$date,$today);
        
        foreach ($cut_off_date as $all_date) {
            $date = $all_date;
            $r = in_array_custom("date-{$date}", $check_emp_time_in);
            
            if($r){
                $check_employee_leave_application = $CI->employee->check_employee_leave_application($r->emp_id,$date);
                if($check_employee_leave_application) {
                    if($check_employee_leave_application["info"]["undertime"] != "") {
                        $under_with_leave = ($r->undertime_min) - ($check_employee_leave_application["info"]["credited"] * 8) * 60;
                    } else {
                        $under_with_leave = $r->undertime_min;
                    }
                } else {
                    $under_with_leave = $r->undertime_min;
                }
                
                $count = $count + $under_with_leave;
            }
        }
    }
    return ($count == 0) ? $count : number_format(($count / 60),2,'.',',');
}

function get_emp_payslips_dash($emp_id, $comp_id){
    $CI =& get_instance();
    $s = array(
        "period_from",
        "period_to",
        "emp_id",
        "company_id",
        "payroll_payslip_id",
        "payroll_date"
    );
    
    $where = array(
        "emp_id"=>$emp_id,
        "company_id"=>$comp_id
    );
    
    $CI->db->select($s);
    $CI->db->where($where);
    $CI->db->order_by('payroll_date','DESC');
    $q = $CI->db->get("payroll_payslip",4);
    $r = $q->result();
    
    return ($r) ? $r : FALSE;
}

function enable_desktop_fast_clock_in($company_id){
    $_CI =& get_instance();
    $_CI->db->where("company_id",$company_id);
    $query = $_CI->db->get("clock_guard_settings");
    $row = $query->row();
    if($row){
        $check = $row->desktop_wt_full_context;
        return ($check =="disable") ? true : false;
    }
    
    return false;
}

function if_not_required_to_Login_v2($emp_id,$company_id) {
    $_CI =& get_instance();
    
    $_CI->load->model('employee/employee_model','employee');
    $_CI->load->model('employee_login_model/emp_login_model','elm');
    
    
    if(check_if_timein_is_required($emp_id,$company_id) == "no") {
        return true;
    } else {
        $emp_no = $_CI->employee->check_emp_no_v2($emp_id,$company_id);
        
        $currentdate = date('Y-m-d');
        $vx = $_CI->elm->get_current_date_work_schedule($company_id,$emp_no,$emp_id);
        
        if($vx){
            $currentdate = $vx['currentdate'];
        }
        
        $work_schedule_id = $_CI->employee->emp_work_schedule($emp_id,$company_id,$currentdate);
        
        $w = array(
            "ess.emp_id" => $emp_id,
            "ess.company_id" => $company_id,
            "fh.work_schedule_id" => $work_schedule_id,
            "ess.valid_from" => $currentdate,
            "ess.until" => $currentdate,
            "fh.payroll_group_id" => 0,
            "fh.not_required_login" => 1
        );
        
        $_CI->db->where($w);
        $_CI->db->join('employee_shifts_schedule AS ess','ess.work_schedule_id = fh.work_schedule_id','LEFT');
        $q = $_CI->db->get("flexible_hours AS fh");
        $r = $q->row();
        
        if($r) {
            return ($r) ? $r : FALSE ;
        } else {
            $w1 = array(
                "epi.emp_id" => $emp_id,
                "pg.company_id" => $company_id,
                "fh.work_schedule_id" => $work_schedule_id,
                "pg.status" => "Active",
                "fh.not_required_login" => 1
            );
            
            $_CI->db->where($w1);
            $_CI->db->join('flexible_hours AS fh','fh.work_schedule_id = pg.work_schedule_id','LEFT');
            $_CI->db->join('employee_payroll_information AS epi','epi.payroll_group_id = pg.payroll_group_id','LEFT');
            $q1 = $_CI->db->get("payroll_group AS pg");
            $r1 = $q1->row();
            
            return ($r1) ? $r1 : FALSE ;
        }
    }
    
}

function counter_emp_tardiness($emp_id, $comp_id,$next_period,$check_employee_work_schedule_v2,$work_schedule_id_pg){
    $CI =& get_instance();
    $count_split = 0;
    $count = 0;
    $period = $next_period;
    $CI->load->model('employee/employee_model','employee');
    
    if($period){
        $date = date('Y-m-d', strtotime($period->cut_off_from));
        $today = date('Y-m-d');
        if(strtotime($today) > strtotime($period->first_payroll_date)){
            $today = date('Y-m-d', strtotime($period->first_payroll_date));
        }
        
        $cut_off_date = dateRange($date, $today);
        $check_emp_time_in = check_emp_time_in($emp_id,$date,$today,"tardiness");
        $check_emp_time_in_split = check_emp_time_in_split($emp_id,$date,$today,"tardiness");
        
        foreach ($cut_off_date as $all_date) {
            $date = $all_date;
            
            $work_sched_id1 = in_array_custom("date-{$date}", $check_employee_work_schedule_v2);
            if($work_sched_id1) {
                $work_sched_id = $work_sched_id1->work_schedule_id;
            } else {
                $work_sched_id = $work_schedule_id_pg;
            }
            
            if(!$work_sched_id){
                return false; break;
            }
            
            $work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)),$date,$emp_id);
            
            if(!$work_schedule_info) break;
            
            if($work_schedule_info['work_schedule']['shift_name']!= ""){
                $r = in_array_custom("date-{$date}", $check_emp_time_in);
                
                if($r){
                    if($work_schedule_info['work_schedule']['split'] == true) {
                        $blocks = $work_schedule_info['work_schedule']['sched_blocks'];
                        if($blocks) {
                            foreach ($blocks as $key=>$r) {
                                $time_in_split = in_array_custom("date-{$date}{$r}", $check_emp_time_in_split);
                                if($time_in_split) {
                                    if($time_in_split->time_in_status == NULL || $time_in_split->time_in_status == "approved") {
                                        $count_split = $count_split + 1;
                                    }
                                }
                            }
                        }
                    } else {
                        $check_employee_leave_application = $CI->employee->check_employee_leave_application($r->emp_id,$r->date);
                        
                        if($check_employee_leave_application["info"]["tardiness"] != "") {
                            $tardi_with_leave = ($check_employee_leave_application["info"]["credited"] * 8) * 60;
                        } else {
                            $tardi_with_leave = 0;
                        }
                        
                        $new_tardi1 = $r->tardiness_min - $tardi_with_leave;
                        
                        if($new_tardi1 < 0) {
                            $new_tardi = false;
                        } else {
                            $new_tardi = true;
                        }
                        
                        if($new_tardi) {
                            $count = $count + 1;
                        }
                    }
                    
                }
            }
        }
    }
    
    return $count + $count_split;
}

function counter_emp_undertime($emp_id, $comp_id,$next_period,$check_employee_work_schedule_v2,$work_schedule_id_pg){
    $CI =& get_instance();
    
    $count_split = 0;
    $count = 0;
    $period = $next_period;
    $under_with_leave = "";
    
    $CI->load->model('employee/employee_model','employee');
    
    if($period){
        
        $date = date('Y-m-d', strtotime($period->cut_off_from));
        $today = date('Y-m-d');
        if(strtotime($today) > strtotime($period->first_payroll_date)){
            $today = date('Y-m-d', strtotime($period->first_payroll_date));
        }
        
        $cut_off_date = dateRange($date, $today);
        $check_emp_time_in = check_emp_time_in($emp_id,$date,$today,"undertime");
        $check_emp_time_in_split = check_emp_time_in_split($emp_id,$date,$today,"undertime");
        
        foreach ($cut_off_date as $all_date) {
            $date = $all_date;
            
            $work_sched_id1 = in_array_custom("date-{$date}", $check_employee_work_schedule_v2);
            if($work_sched_id1) {
                $work_sched_id = $work_sched_id1->work_schedule_id;
            } else {
                $work_sched_id = $work_schedule_id_pg;
            }
            
            if(!$work_sched_id){
                return false; break;
            }
            
            $work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)),$date,$emp_id);
            
            if(!$work_schedule_info) break;
            
            if($work_schedule_info['work_schedule']['shift_name']!= ""){
                $r = in_array_custom("date-{$date}", $check_emp_time_in);
                
                if($r){
                    if($work_schedule_info['work_schedule']['split'] == true) {
                        $blocks = $work_schedule_info['work_schedule']['sched_blocks'];
                        if($blocks) {
                            foreach ($blocks as $key=>$r) {
                                $time_in_split = in_array_custom("date-{$date}{$r}", $check_emp_time_in_split);
                                
                                if($time_in_split) {
                                    if($time_in_split->time_in_status == NULL || $time_in_split->time_in_status == "approved" || $under_with_leave) {
                                        $count_split = $count_split + 1;
                                    }
                                }
                            }
                        }
                    } else {
                        $check_employee_leave_application = $CI->employee->check_employee_leave_application($r->emp_id,$r->date);
                        
                        if($check_employee_leave_application["info"]["undertime"] != "") {
                            $under_with_leave1 = ($r->undertime_min) - ($check_employee_leave_application["info"]["credited"] * 8) * 60;
                            #p($under_with_leave1);
                            if($under_with_leave1 <= 0) {
                                $under_with_leave = false;
                            } else {
                                $under_with_leave = true;
                            }
                        } else {
                            #print (($time_in_split->undertime_min != "") || $time_in_split->undertime_min!= NULL) ? number_format($time_in_split->undertime_min,2,'.',',') : '0.00';
                            $under_with_leave = (($r->undertime_min != "") || $r->undertime_min!= NULL || ($r->undertime_min > 0)) ? true : false;
                        }
                        
                        if($under_with_leave) {
                            $count = $count + 1;
                        }
                    }
                }
            }
        }
    }
    
    return $count + $count_split;
}

function check_unwork_holiday_pay($emp_id, $comp_id, $type) {
    $CI =& get_instance();
    
    if($type == "regular") {
        $where = array(
            'emp_id' => $emp_id,
            'company_id' => $comp_id,
            'entitled_to_unwork_regular_holiday' => 'no'
        );
    } else {
        $where = array(
            'emp_id' => $emp_id,
            'company_id' => $comp_id,
            'entitled_to_unwork_special_holiday' => 'no'
        );
    }
    
    $CI->db->where($where);
    
    $q = $CI->db->get('employee_payroll_information');
    $r = $q->row();
    
    return ($r) ? TRUE : FALSE;
}

function check_emp_time_in_split($emp_id,$min,$max,$time_in_status = ""){
    $CI =& get_instance();
    
    if($time_in_status == "pending") {
        $where = array(
            'emp_id' =>$emp_id,
            'status' => 'Active',
            'time_in_status' =>'pending'
        );
    } elseif ($time_in_status == "reject") {
        $where = array(
            'emp_id' =>$emp_id,
            'status' => 'Active',
            'time_in_status' =>'reject'
        );
    } elseif ($time_in_status == "tardiness") {
        $where = array(
            'tardiness_min >' => '0',
            'emp_id' =>$emp_id,
            'status' => 'Active'
        );
        
    } elseif ($time_in_status == "undertime") {
        $where = array(
            'undertime_min >' => '0',
            'emp_id' =>$emp_id,
            'status' => 'Active'
        );
        
    } else {
        $where = array(
            'emp_id' =>$emp_id,
            'status' => 'Active'
        );
        
        $CI->db->where("(time_in_status != 'reject' OR time_in_status IS NULL)");
    }
    
    $CI->db->where($where);
    
    $q = $CI->db->get('schedule_blocks_time_in');
    $r = $q->result();
    
    $res = array();
    
    if($r){
        foreach ($r as $row) {
            $temp = array(
                "date" => $row->date,
                "comp_id" => $row->comp_id,
                "emp_id" => $row->emp_id,
                "time_in" => $row->time_in,
                "lunch_out" => $row->lunch_out,
                "lunch_in" => $row->lunch_in,
                "time_out" => $row->time_out,
                "tardiness_min" => $row->tardiness_min,
                "undertime_min" => $row->undertime_min,
                "late_min" => $row->late_min,
                "absent_min" => $row->absent_min,
                "overbreak_min" => $row->overbreak_min,
                "total_hours_required" => $row->total_hours_required,
                "time_in_status" => $row->time_in_status,
                "employee_time_in_id" => $row->employee_time_in_id,
                "schedule_blocks_id" => $row->schedule_blocks_id,
                "schedule_blocks_time_in_id" => $row->schedule_blocks_time_in_id,
                
                "filter_query" => "date-{$row->date}{$row->schedule_blocks_id}"
            );
            
            array_push($res, $temp);
        }
        return $res;
    } else {
        return FALSE;
    }
}

function get_schedule_block($schedule_blocks_id,$work_schedule_id,$company_id) {
    $CI =& get_instance();
    
    $where = array(
        "schedule_blocks_id" => $schedule_blocks_id,
        "work_schedule_id" => $work_schedule_id,
        "company_id" => $company_id,
        "status" => "Active"
    );
    
    $CI->db->where($where);
    $q = $CI->db->get("schedule_blocks");
    $r = $q->row();
    
    return ($r) ? $r : FALSE;
}

function ip_in_range($ip,$range) {
    if ( strpos( $range, '/' ) == false ) {
        $range .= '/28';
    }
    // $range is in IP/CIDR format eg 127.0.0.1/24
    list( $range, $netmask ) = explode( '/', $range, 2 );
    $range_decimal = ip2long( $range );
    $ip_decimal = ip2long( $ip );
    $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
}

function get_ip_employee($company_id, $emp_id = ""){
    $CI =& get_instance();
    
    //if($company_id == "1") { // for ionos
    if(false) {
        $ipx = get_external_ip_new($CI->uri->segment(1));
        $ip_get = $ipx;
        //$ip_no = $row->ip_address;

        $range = "92.119.177.20/28";
        $in_range = ip_in_range($ip_get,$range);

        if($in_range) {
            return true;
        } else {
            return false;
        }
    } else {
        if ($emp_id == "") {
            $w = array(
                "company_id"=>$company_id,
                "category" => 2
            );
        } else {
            $w = array(
                "company_id"=>$company_id,
                "category" => 2,
                "emp_id"=>$emp_id,
            );
        }
        
        $CI->db->where($w);
        $q = $CI->db->get("employee_ip_address");
        $r = $q->result();
        
        
        if($r){
            $count = 0;
            $count2 = 0;
            $ipx = get_external_ip_new($CI->uri->segment(1));
            foreach($r as $d){
                
                $ip_get = $ipx;
                $ip_no = $d->ip_address;
                if($ip_no == $ip_get){
                    
                    $count++;
                }else{
                    
                }
            }
            
            foreach($r as $row){
                
                $ip_get = $ipx;
                $ip_no = $row->ip_address;
                if($ip_no == $ip_get)
                    return true;
                else{
                    $ip_get = (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) ? explode('.',$_SERVER['HTTP_X_FORWARDED_FOR']) : "00.00";
                    if($ip_no == $ip_get){
                        return true;
                    }else{
                        
                        $ip_get = (!empty($_SERVER['REMOTE_ADDR'])) ? explode('.',$_SERVER['REMOTE_ADDR']) : "00.00";
                        if(($ip_no == $ip_get) || $count > $count2){
                            
                            return true;
                        }else{
                            
                        }
                    }
                }
            }
            
            return false;
           
        }
    }
    
}

function get_external_ip_new($company_domain){
    $CI =& get_instance();
    #$url2 = 'https://ashima.ph/getip';
    $url2 = base_url("getip");//'https://ashima.ph/getip';
    
    
    $r = false;
    if(!$CI->session->userdata('myip')){
        
        $r = (isset($_GET['ex'])) ? $_GET['ex'] : false;
    }
    if($r){
        $CI->session->set_userdata('myip', $r);
    }else{
        
        if(!$CI->session->userdata('myip')){
            $http = "https";
            if(get_domain(base_url()) == "ashima.ph") {
                $http = "https";
            }
            
            $http = stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https' : 'http';
            $http = "https";
            $actual_link = $http."://$_SERVER[HTTP_HOST]/{$company_domain}/employee/emp_clock_in_v4";
          
            redirect($url2."?er=1&urlx=".$actual_link."&solid=1");
        }
    }
    
    return $CI->session->userdata('myip');
}

function workflow_approved_by_level_v2($application_id, $workflow_type, $level="", $approve = ""){
    $CI =& get_instance();
    if($level != "") {
        $w = array(
            'wab.application_id' => $application_id,
            'wab.workflow_type' =>  $workflow_type,
            'wab.workflow_level' => $level
        );
    } else {
        $w = array(
            'wab.application_id' => $application_id,
            'wab.workflow_type' =>  $workflow_type
        );
    }
    
    $s = array(
        'wab.workflow_approved_by_id',
        'wab.application_id',
        'wab.approver_id',
        'wab.workflow_level',
        'wab.workflow_type',
        'e.last_name',
        'e.first_name'
    );
    
    $CI->edb->select($s);
    $CI->db->where($w);
    
    $CI->db->order_by('wab.workflow_level','DESC');
    $CI->edb->join("employee AS e","e.emp_id = wab.approver_id","LEFT");
    $CI->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
    $q = $CI->edb->get('workflow_approved_by AS wab');
    $r = $q->row();
    
    return ($r) ? $r : FALSE;
}

function check_if_employee_manager_v2($emp_id, $company_id) {
    $CI =& get_instance();
    $CI->load->model("settings/manager_devolvement_model",'mdm');

    if($emp_id) {
        $account_id = $CI->session->userdata('account_id');
        $psa_id = $CI->session->userdata('psa_id');

        $check_enable = $CI->mdm->get_accounts_infos($account_id,$psa_id);

        if($check_enable){
            if($check_enable->enable_generic_manager_portal == 'Active'){
                $where = array(
                    "edrt.parent_emp_id" => $emp_id,
                    "edrt.company_id" => $company_id,
                    "epi.employee_status" => "Active"
                );
                
                $CI->db->where($where);
                $CI->db->join("employee_payroll_information AS epi","epi.emp_id = edrt.emp_id","LEFT");
                $q = $CI->db->get('employee_details_reports_to AS edrt');
                $row = $q->result();
                $q->free_result();
                return ($row) ? true : false;
            } else {
                return false;
            }
        } else {
            return false;
        }
        
    } else {
        return false;
    }
}

function get_all_employee_direct_reports_v2($emp_id, $company_id, $limit = 100) {
    $CI =& get_instance();
    
    if($emp_id) {
        $sel = array(
            'a.profile_image',
            'a.account_id'
        );
        
        $where = array(
            "edrt.parent_emp_id" => $emp_id,
            "edrt.company_id" => $company_id,
            "epi.employee_status" => "Active"
        );
        
        $CI->db->select($sel);
        $CI->db->where($where);
        $CI->db->join("employee AS e","e.emp_id = edrt.emp_id","LEFT");
        $CI->db->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $CI->db->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $q = $CI->db->get('employee_details_reports_to AS edrt', $limit);
        
        $row = $q->result();
        
        return ($row) ? $row : false;
        
    } else {
        return false;
    }
}

function todo_pending_shifts_request_counter($emp_id, $company_id){
    $_CI =& get_instance();
    
    $where = array(
        'ewsa.company_id' => $company_id,
        "ag.emp_id" => $emp_id,
        "ewsa.employee_work_schedule_status" => "pending",
        "aws.level >=" => "",
        "epi.employee_status" => "Active"
    );
    
    $s = array(
        "ewsa.employee_work_schedule_application_id"
    );
    
    $_CI->db->select($s);
    $_CI->db->where($where);
    
    $_CI->db->join("employee_payroll_information AS epi","epi.emp_id = ewsa.emp_id","LEFT");
    $_CI->db->join("approval_groups_via_groups AS agg","epi.shedule_request_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    // $_CI->db->join("approval_groups AS ag","agg.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
    $_CI->db->join("approval_work_schedule AS aws","aws.employee_work_schedule_application_id = ewsa.employee_work_schedule_application_id","LEFT");
    $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND aws.level = ag.level","LEFT");
    $_CI->db->group_by("ewsa.employee_work_schedule_application_id");
    $query = $_CI->db->get("employee_work_schedule_application AS ewsa",11);
    
    return ($query->num_rows > 0) ? $query->num_rows : 0;
}

function manager_count_missed_punches_v3($emp_id, $comp_id,$next_pay_period, $check_employee_work_schedule_v2,$work_schedule_id_pg,$emp_check_rest_day,$get_holidays){
    $CI =& get_instance();
    $CI->load->model('employee/employee_work_schedule_model','ews');
    
    $period = $next_pay_period;
    $count = 0;
    $count_fritz = 0;
    $count_split = 0;
    
    if($period){
        $date = date('Y-m-d', strtotime('-1 day'));
        $today = date('Y-m-d', strtotime('-1 day'));
        
        if(date('l', strtotime($today)) == 'Sunday') {
            $today = date('Y-m-d', strtotime('-2 day'));
        }
        
        $check_emp_time_in = check_emp_time_in($emp_id,$date,$today);
        $check_emp_time_in_split = check_emp_time_in_split($emp_id,$today,$date);
        
        $work_sched_id1 = in_array_custom("date-{$date}", $check_employee_work_schedule_v2);
        if($work_sched_id1) {
            $work_sched_id = $work_sched_id1->work_schedule_id;
        } else {
            $work_sched_id = $work_schedule_id_pg;
        }
        
        $is_break_assumed = is_break_assumed($work_sched_id);
        
        if(!$work_sched_id){
            return false; break;
        }
        
        $rest_dat = date('l',strtotime($date));
        $rest_day = in_array_custom("work_schedule_id-{$work_sched_id}{$rest_date}", $emp_check_rest_day);
        $check_holiday = in_array_custom("date-{$date}", $get_holidays);
        
        $work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)),$date,$emp_id);
        
        if($check_holiday) {
            if($check_holiday->hour_type_name == "Regular Holiday") {
                $valid_holiday = true;
                $check_unwork_holiday_pay = check_unwork_holiday_pay($emp_id, $comp_id, "regular");
                if($check_unwork_holiday_pay) {
                    $valid_holiday = false;
                }
            } elseif($check_holiday->hour_type_name == "Special Holiday") {
                $valid_holiday = true;
                $check_unwork_holiday_pay = check_unwork_holiday_pay($emp_id, $comp_id, "special");
                if($check_unwork_holiday_pay) {
                    $valid_holiday = false;
                }
            } else {
                $valid_holiday = false;
            }
        } else {
            $valid_holiday = false;
        }
        
        if(!$work_schedule_info) break;
        $leave = $CI->ews->check_employee_leave_application($date, $emp_id);
        $leave_check = ($leave) ? TRUE: FALSE;
        
        $tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($comp_id,$work_sched_id);
        $get_schedule_settings = get_schedule_settings_by_workschedule_id($work_sched_id,$comp_id,date("l", strtotime($date)));
        
        if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
            $time_in = in_array_custom("date-{$date}", $check_emp_time_in);
            
            if($time_in){
                if(!$rest_day && !$valid_holiday){
                    if($time_in->time_in != NULL && $time_in->lunch_out == NULL && $time_in->lunch_in == NULL && $time_in->time_out == NULL) {
                        if(!$rest_day && !$valid_holiday){
                            if($work_schedule_info['work_schedule']['split'] == true) {
                                $blocks = $work_schedule_info['work_schedule']['sched_blocks'];
                                if($blocks) {
                                    foreach ($blocks as $key=>$r) {
                                        $time_in_split = in_array_custom("date-{$date}{$r}", $check_emp_time_in_split);
                                        $get_schedule_block = get_schedule_block($r,$work_sched_id,$comp_id);
                                        $lunch = ($get_schedule_block->break_in_min > 0) ? TRUE : FALSE;
                                        
                                        if($time_in_split) {
                                            if($lunch) {
                                                $count_split = $count_split + 1;
                                            }
                                        }
                                    }
                                }
                            } else {
                                if($work_schedule_info['work_schedule']['break_time'] != 0){
                                    if($tardiness_rule_migrated_v3) {
                                        if($get_schedule_settings) {
                                            if($get_schedule_settings->enable_lunch_break == "yes") {
                                                if($get_schedule_settings->track_break_1 == "yes") {
                                                    if($time_in->lunch_out == NULL)  $count_fritz = $count_fritz + 1;
                                                    if($time_in->lunch_in == NULL)  $count_fritz = $count_fritz + 1;
                                                }
                                            }
                                            
                                            if($get_schedule_settings->enable_additional_breaks == "yes") {
                                                if($get_schedule_settings->track_break_2 == "yes") {
                                                    if($get_schedule_settings->num_of_additional_breaks == 2) {
                                                        if($time_in->break1_out == NULL)  $count_fritz = $count_fritz + 1;
                                                        if($time_in->break1_in == NULL)  $count_fritz = $count_fritz + 1;
                                                        
                                                        if($time_in->break2_out == NULL)  $count_fritz = $count_fritz + 1;
                                                        if($time_in->break2_in == NULL)  $count_fritz = $count_fritz + 1;
                                                    }
                                                    
                                                    if($get_schedule_settings->num_of_additional_breaks == 1) {
                                                        if($time_in->break1_out == NULL)  $count_fritz = $count_fritz + 1;
                                                        if($time_in->break1_in == NULL)  $count_fritz = $count_fritz + 1;
                                                    }
                                                    
                                                }
                                            }
                                        }
                                    } else {
                                        if($is_break_assumed) {
                                            if($is_break_assumed->break_rules != "assumed") {
                                                if($time_in->lunch_out == NULL) {
                                                    $count_fritz = $count_fritz + 1;
                                                }
                                                
                                                if($time_in->lunch_in == NULL){
                                                    $count_fritz = $count_fritz + 1;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        if($time_in->time_out == NULL) {
                            $count_fritz = $count_fritz + 1;
                        }
                    }
                }
            }
        }
    }
    return $count_fritz + $count_split;
}

function ordinal_suffix($number) {
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number%100) <= 13)) {
        return $number. 'th';
    } else {
        return $number. $ends[$number % 10];
    }
}

function get_who_approve_n_reject($application_id, $workflow_type, $level="", $approve = ""){
    $CI =& get_instance();
    if($level != "") {
        $w = array(
            'wab.application_id' => $application_id,
            'wab.workflow_type' =>  $workflow_type,
            'wab.workflow_level' => $level
        );
    } else {
        $w = array(
            'wab.application_id' => $application_id,
            'wab.workflow_type' =>  $workflow_type
        );
    }
    
    $s = array(
        'wab.workflow_approved_by_id',
        'wab.application_id',
        'wab.approver_id',
        'wab.workflow_level',
        'wab.workflow_type',
        'e.last_name',
        'e.first_name'
    );
    
    $CI->edb->select($s);
    $CI->db->where($w);
    
    $CI->db->order_by('wab.workflow_level','DESC');
    $CI->edb->join("employee AS e","e.emp_id = wab.approver_id","LEFT");
    $CI->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
    $q = $CI->edb->get('workflow_approved_by AS wab');
    $r = $q->result();
    
    return ($r) ? $r : FALSE;
}

function time_entry_option_settings($company_id, $emp_id, $type = "") {
    $_CI =& get_instance();
    
    $_CI->db->where("company_id",$company_id);
    $query = $_CI->db->get("clock_guard_settings");
    $row = $query->row();
   
    if($row){
        if($type == "desktop") {
            if ($row->desktop_clockin_with_photo == "enable") {
                $individual_time_entry_option_settings = individual_time_entry_option_settings($company_id, $emp_id);
                
                $setting_arr = array(
                    "type" => "global setting", 
                    "time_entry" => $row->desktop_wt_full_context,
                    "photo_capture" => $row->desktop_photo,
                    "standard_ip_restriction" => $row->desktop_standard_ip,
                    "individual_ip_restriction" => ""
                );
                
                if ($row->desktop_employee_restriction == "enable") {
                    if ($individual_time_entry_option_settings) {
                        if ($individual_time_entry_option_settings->employee_desktop_clockin_with_photo == "enable") {
                            $setting_arr = array(
                                "type" => "individual setting",
                                "time_entry" => $individual_time_entry_option_settings->employee_desktop_with_full_context,
                                "photo_capture" => $individual_time_entry_option_settings->employee_desktop_photo_capture,
                                "standard_ip_restriction" => $row->desktop_standard_ip,
                                "individual_ip_restriction" => $individual_time_entry_option_settings->employee_desktop_individual_ip
                            );
                            
                            if ($individual_time_entry_option_settings->employee_desktop_individual_ip == "enable") {
                                $get_ip_employee = get_ip_employee($company_id, $emp_id);
                                
                                if (!$get_ip_employee) {
                                    $setting_arr = array(
                                        "type" => "individual setting",
                                        "time_entry" => $individual_time_entry_option_settings->employee_desktop_with_full_context,
                                        "photo_capture" => $individual_time_entry_option_settings->employee_desktop_photo_capture,
                                        "standard_ip_restriction" => $row->desktop_standard_ip,
                                        "individual_ip_restriction" => $individual_time_entry_option_settings->employee_desktop_individual_ip
                                    );
                                }
                            }
                        }
                    }
                }
                
                return $setting_arr;
            } else {
                return false;
            }
        } elseif ($type == "mobile") {
            $setting_arr = array(
                "mobile_clockin" => $row->mobile_clockin,
                "mobile_face_id" => $row->mobile_face_id
            );
            
            return $setting_arr;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function individual_time_entry_option_settings($company_id, $emp_id) {
    $_CI =& get_instance();
    
    $w = array(
        "company_id" => $company_id,
        "emp_id" => $emp_id,
    );
    
    $s = array(
        "employee_desktop_clockin_with_photo",
        "employee_desktop_with_full_context",
        "employee_desktop_photo_capture",
        "employee_desktop_standard_ip",
        "employee_desktop_individual_ip"
    );
    
    $_CI->db->select($s);
    $_CI->db->where($w);
    $query = $_CI->db->get("employee_payroll_information");
    $row = $query->row();
    
    if($row){
        return $row;
    } else {
        return false;
    }
}

function validate_emp_ip_setting($company_id, $emp_id, $time_entry_option_settings) {
    $get_ip_employee = get_ip_employee($company_id);
    
    if ($time_entry_option_settings['type'] == "individual setting") {
        if ($time_entry_option_settings['individual_ip_restriction'] == "enable") {
            $get_ip_employee = get_ip_employee($company_id, $emp_id);
            
            if (!$get_ip_employee) {
                if ($time_entry_option_settings['standard_ip_restriction'] == "disable") {
                    $get_ip_employee = get_ip_employee($company_id);
                }
            }
        } else {
            $get_ip_employee = true;
            
        }
    } elseif ($time_entry_option_settings['type'] == "global setting") {
        if ($time_entry_option_settings['individual_ip_restriction'] == "enable") {
            $get_ip_employee = get_ip_employee($company_id, $emp_id);
            
            if (!$get_ip_employee) {
                if ($time_entry_option_settings['standard_ip_restriction'] == "disable") {
                    $get_ip_employee = get_ip_employee($company_id);
                }
            }
        }
    }
    
    return $get_ip_employee;
}

function insert_todo_data($company_id,$shift_date,$emp_id,$approval_id,$level,$approvers_id,$agvg,$work_schedule_id="",$module="hours"){
    $_CI =& get_instance();
    
    if($approval_id){
        
        $insert = array(
            'company_id'                    => $company_id,
            "date"                          => $shift_date,
            "emp_id"                        => $emp_id,
            "approval_id"                   => $approval_id,
            "level"                         => $level,
            "approvers_id"                  => $approvers_id,
            "approval_goups_via_groups_id"  => $agvg,
            "work_schedule_id"              => $work_schedule_id,
            "module"                        => $module,
        );
        
        $w = array(
            'approval_id'       => $approval_id,
            'company_id'        => $company_id,
            'status'            => "Active",
        );
        
        $_CI->db->where($w);
        $q = $_CI->db->get("to_do_list");
        $r = $q->row();
        if($r){
            // if exist update
            $wupdate    = array(
                'to_do_list_id' => $r->to_do_list_id
            );
            $_CI->db->where($wupdate);
            $_CI->db->update("to_do_list",$insert);
        }
        else{
            // insert
            $_CI->db->insert('to_do_list', $insert);
        }
    }
}

function inactive_todo_data($company_id,$emp_id,$approval_id,$module="hours"){
    $_CI =& get_instance();
    
    if($approval_id){
        
        $w = array(
            'company_id'        => $company_id,
            "emp_id"            => $emp_id,
            "approval_id"       => $approval_id,
            "module"            => $module,
            'status'            => "Active",
        );
        
        $_CI->db->where($w);
        $q = $_CI->db->get("to_do_list");
        $r = $q->row();
        if($r){
            // if exist update
            $i = array(
                "status" => "Inactive"
            );
            
            $wupdate    = array(
                'to_do_list_id' => $r->to_do_list_id
            );
            $_CI->db->where($wupdate);
            $_CI->db->update("to_do_list",$i);
        }
    }
}

function schedule_request_settings($comp_id){
    $CI =& get_instance();
    $CI->db->where('company_id',$comp_id);
    $q = $CI->db->get('schedule_request_settings');
    $r = $q->row();

    return ($r) ? $r : false;
}

function check_if_enable_working_on_restday($comp_id,$work_schedule_id){
    $CI =& get_instance();

    $w = array(
        "work_schedule_id" => $work_schedule_id,
        "comp_id" => $comp_id
    );
    
    $CI->db->where($w);
    $q = $CI->db->get('work_schedule');
    $r = $q->row();
    
    if($r) {
        if($r->enable_working_on_restday == "yes") {
            return TRUE;
        } else {
            return FALSE;
        }
    } else {
        return FALSE;
    }
}

function holiday_approval_settings($comp_id) {
    $CI =& get_instance();
    
    $w = array(
        "company_id" => $comp_id
    );
    
    $CI->db->where($w);
    $q = $CI->db->get('holiday_pay_settings');
    $r = $q->row();
    
    if($r) {
        if($r->holiday_pay_approval_settings == "yes") {
            return TRUE;
        } else {
            return FALSE;
        }
    } else {
        return FALSE;
    }
}

function get_rest_day_settings($company_id){
    $CI =& get_instance();
    #$row_array = array();
    
    $s          = array(
        'enable_approval',
    );
    
    $w_uwd      = array(
        "company_id"        => $company_id,
        "status"            => 'Active',
    );
    
    $CI->db->where($w_uwd);
    $CI->db->select($s);
    $tard = $CI->db->get("rest_day_settings");
    $result = $tard->row();
    
    $return = "no";
    if($result){
        $return = ($result->enable_approval == "yes") ? "yes" : "no";
    }
    
    return $return;
}

function get_lock_payroll_process_settings($company_id,$type="application",$emp_id="",$application_date="") {
    $CI =& get_instance();

    if($type == "add logs") {
        $type = "new attendance log";
    } elseif ($type == "change log") {
        $type = "attendance adjustment";
    } elseif ($type == "leave") {
        $type = "Leave Application";
    } elseif ($type == "overtime") {
        $type = "Overtime Application";
    } elseif ($type == "shift request") {
        $type = "Shift assignment";
    }
    
    $CI->db->where('company_id',$company_id);
    $query = $CI->db->get('lock_payroll_process_settings');
    $result = $query->row();
    
    if($result) {

        /** gi add ni ky gi Limit to affected Pay Period nlng **/
        
        $date_now = date("Y-m-d");
        $suspend_all_application = "no";//$result->suspend_all_application;
        $ts_recalc = $result->ts_recalc;
        $py_recalc = $result->py_recalc;

        #if($suspend_all_application == "yes") {
        if($emp_id != "" && $application_date != "") {
            $get_current_period = get_current_period_of_employee($emp_id, $company_id, $date_now);
            #p($get_current_period );exit();
            if($get_current_period) {
                if(strtotime($get_current_period->cut_off_from) <= strtotime($application_date) && strtotime($get_current_period->first_payroll_date) >= strtotime($application_date)) {
                    $suspend_all_application = $result->suspend_all_application;
                }
            }
        }
        #}

        /** gi add ni ky gi Limit to affected Pay Period nlng - end **/            
        $res = array(
            "suspend_all_application" => $suspend_all_application,
            "ts_recalc" => $result->ts_recalc,
            "ts_recalc_time" => $result->ts_recalc_time,
            "py_recalc" => $result->py_recalc,
            "py_recalc_time" => $result->py_recalc_time,
            
            "application_error" => "One or more of your {$type} requested date/s fall under a pay run that is currently being finalized. Your payroll admin has enabled the pay run freeze function temporarily suspending any updates that will affect the upcoming payroll.",
            "ts_app_recalculation_err_msg" => "This application can't be completed because another process is accessing the same data set. Please try again later. ",
            "py_app_recalculation_err_msg" => "This application can't be completed because another process is accessing the same data set. Please try again later. ",
            
            "manager_application_error" => "{$type} is temporarily suspended by your admin.",
            "app_delete_error_msg" => "Your payroll admin has enabled the pay run freeze function temporarily suspending any deletion of {$type} that will affect the pay run that is currently being finalized.",
            "ts_app_delete_error_msg" => "This action can't be completed because this data set is temporarily locked.",
            "py_app_delete_error_msg" => "This action can't be completed because this data set is temporarily locked.",
            
            "approval_error" => "NOTE : To Do approval has temporarily been suspended by your admin as the 'freeze pay run updates' function has been enabled while they are finalizing payroll.",
            "ts_approval_recalculation_err_msg" => "NOTE : This data cannot be approved/rejected because this data set is temporarily being locked.",
            "py_approval_recalculation_err_msg" => "NOTE : This data cannot be approved/rejected because this data set is temporarily being locked."
        );
        
        return (object) $res ;
    } else {
        return false;
    }
}

function todo_pending_allowances_counter($emp_id, $company_id){
    $_CI =& get_instance();
    
    $where = array(
        'ea.company_id'         => $company_id,
        "ag.emp_id"             => $emp_id,
        "ea.application_status" => "pending",
        "aa.level !="           => "",
        "epi.employee_status"   => "Active"
    );
    
    $s = array(
        "ea.employee_allowances_id"
    );
    
    $_CI->db->select($s);
    $_CI->db->where($where);
    
    $_CI->db->join("employee_payroll_information AS epi","epi.emp_id = ea.emp_id","LEFT");
    $_CI->db->join("approval_groups_via_groups AS agg","epi.allowance_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    $_CI->db->join("approval_allowance AS aa","aa.employee_allowances_id = ea.employee_allowances_id","LEFT");
    $_CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND aa.level = ag.level","LEFT");
    $_CI->db->group_by("ea.employee_allowances_id");
    $query = $_CI->db->get('employee_allowances AS ea',11);
    
    return ($query->num_rows > 0) ? $query->num_rows : 0;
}

function save_to_timesheet_close_payroll($emp_time_id, $type="updated") {
    $_CI =& get_instance();
    $_CI->load->model('employee/approval_group_model','agm');
    $_CI->load->model("workforce/approve_timeins_model","timeins");
    
    $timein_info = $_CI->agm->timein_information($emp_time_id);
    
    $auto_date = ($timein_info->change_log_date!=NULL) ? date("Y-m-d",strtotime($timein_info->change_log_date)) : date("Y-m-d",strtotime($timein_info->date));
    $auto_ws_id = ($timein_info->change_log_work_schedule_id != NULL) ? $timein_info->change_log_work_schedule_id : $timein_info->work_schedule_id;
    $save_me = false;

    if($type == "dashboard") {
        
        $undertime_min  = $timein_info->undertime_min;
        $tardiness_min  = $timein_info->tardiness_min;
        $workday_hr     = $timein_info->total_hours_required;
        
        $under_tardi    = $undertime_min + $tardiness_min;
        $workday_hr_min = $timein_info->total_hours * 60;
        $workday_min = $workday_hr_min - $under_tardi;

        if($tardiness_min != 0 || $undertime_min != 0 || $workday_min != 0) {
            $save_me = true;
            #echo "string";
            $date_insert = array(
                "employee_time_in_id" => $emp_time_id,
                "work_schedule_id" => $auto_ws_id,
                "emp_id" => $timein_info->emp_id,
                "comp_id" => $timein_info->company_id,
                "date" => $auto_date,
                "time_in" => $timein_info->time_in,
                "lunch_out" => $timein_info->lunch_out,
                "lunch_in" => $timein_info->lunch_in,
                "break1_out" => $timein_info->break1_out,
                "break1_in" => $timein_info->break1_in,
                "break2_out" => $timein_info->break2_out,
                "break2_in" => $timein_info->break2_in,
                "time_out" => $timein_info->time_out,
                "total_hours" => $timein_info->total_hours,
                "total_hours_required" => $timein_info->total_hours_required,
                //"reason" => $timein_info->reason,
                "time_in_status" => "approved",
                "overbreak_min" => $timein_info->overbreak_min,
                "late_min" => $timein_info->late_min,
                "tardiness_min" => $timein_info->tardiness_min,
                "undertime_min" => $timein_info->undertime_min,
                "absent_min" => $timein_info->absent_min,
                #"notes" => $auto_remarks,
                "source" => "dashboard",
                "status" => "Active",
                "approval_time_in_id" => $timein_info->approval_time_in_id,
                "flag_regular_or_excess" => $timein_info->flag_regular_or_excess,
                "rest_day_r_a" => $timein_info->rest_day_r_a,
                "flag_rd_include" => $timein_info->flag_rd_include,
                "flag_holiday_include" => $timein_info->flag_holiday_include,
                "timesheet_not_req_flag" => $timein_info->timesheet_not_req_flag,
                "partial_log_ded_break" => $timein_info->partial_log_ded_break,
                "flag_open_shift" => $timein_info->flag_open_shift,
                "os_approval_time_in_id" => $timein_info->os_approval_time_in_id,
                "auto_takeup_tardi_min" => $tardiness_min,
                "auto_takeup_undertime_min" => $undertime_min,
                "auto_takeup_workday_min" => $workday_min,
                "current_date_nsd" => $timein_info->current_date_nsd,
                "next_date_nsd" => $timein_info->next_date_nsd,
            );
        }
    } else {
        $workday_min    = 0;
        if($timein_info->change_log_time_out == NULL || $timein_info->change_log_time_out == "") { // bali sa EP
            #$tardiness_min  = $timein_info->tardiness_min - $timein_info->change_log_tardiness_min;
            /*$tardiness_min  = $timein_info->change_log_tardiness_min - $timein_info->tardiness_min;
            #$undertime_temp = $timein_info->total_hours * 60;
            $undertime_temp = $timein_info->change_log_total_hours * 60;
            $undertime_min  = $undertime_temp - $tardiness_min;*/
            
            $tardiness_min  = 0;//$timein_info->change_log_tardiness_min - $timein_info->tardiness_min;
            $undertime_min  = 0;//$timein_info->change_log_undertime_min - $timein_info->undertime_min;
            
            #$workday_min    = $timein_info->change_log_total_hours * 60;
            $workday_min    = $timein_info->total_hours * 60;
        } else {
            if($timein_info->work_schedule_id == "-1") {
                $tardiness_min  = 0;
                $undertime_min  = 0;
                
                $workday_min    = $timein_info->change_log_total_hours * 60;
            } else {
                #$undertime_min  = $timein_info->undertime_min - $timein_info->change_log_undertime_min;
                #$tardiness_min  = $timein_info->tardiness_min - $timein_info->change_log_tardiness_min;
                $undertime_min  = $timein_info->change_log_undertime_min - $timein_info->undertime_min;
                $tardiness_min  = $timein_info->change_log_tardiness_min - $timein_info->tardiness_min;
            }
            
        }
        

        if($tardiness_min != 0 && $undertime_min != 0 && $workday_min != 0) {
            $save_me = true;

            $date_insert = array(
                "employee_time_in_id" => $emp_time_id,
                "work_schedule_id" => $auto_ws_id,
                "emp_id" => $timein_info->emp_id,
                "comp_id" => $timein_info->company_id,
                "date" => $auto_date,
                "time_in" => $timein_info->change_log_time_in,
                "lunch_out" => $timein_info->change_log_lunch_out,
                "lunch_in" => $timein_info->change_log_lunch_in,
                "break1_out" => $timein_info->change_log_break1_out,
                "break1_in" => $timein_info->change_log_break1_in,
                "break2_out" => $timein_info->change_log_break2_out,
                "break2_in" => $timein_info->change_log_break2_in,
                "time_out" => $timein_info->change_log_time_out,
                "total_hours" => $timein_info->change_log_total_hours,
                "total_hours_required" => $timein_info->change_log_total_hours_required,
                //"reason" => $timein_info->reason,
                "time_in_status" => "approved",
                "overbreak_min" => $timein_info->change_log_overbreak_min,
                "late_min" => $timein_info->change_log_late_min,
                "tardiness_min" => $timein_info->change_log_tardiness_min,
                "undertime_min" => $timein_info->change_log_undertime_min,
                "absent_min" => $timein_info->change_log_absent_min,
                #"notes" => $auto_remarks,
                "source" => "updated",
                "status" => "Active",
                "approval_time_in_id" => $timein_info->approval_time_in_id,
                "flag_regular_or_excess" => $timein_info->flag_regular_or_excess,
                "rest_day_r_a" => $timein_info->rest_day_r_a,
                "flag_rd_include" => $timein_info->flag_rd_include,
                "flag_holiday_include" => $timein_info->flag_holiday_include,
                "timesheet_not_req_flag" => $timein_info->timesheet_not_req_flag,
                "partial_log_ded_break" => $timein_info->partial_log_ded_break,
                "flag_open_shift" => $timein_info->flag_open_shift,
                "os_approval_time_in_id" => $timein_info->os_approval_time_in_id,
                "auto_takeup_tardi_min" => $tardiness_min,
                "auto_takeup_undertime_min" => $undertime_min,
                "auto_takeup_workday_min" => $workday_min,
                "current_date_nsd" => $timein_info->current_date_nsd,
                "next_date_nsd" => $timein_info->next_date_nsd,
            );
        }
        
    }

    if($save_me) {
        $_CI->db->insert('timesheet_close_payroll', $date_insert);
        $timesheet_close_payroll_id = $_CI->db->insert_id();
        
        $field_atp = array(
            "for_resend_auto_rejected_id" => $timesheet_close_payroll_id,
        );
        
        $where_atp = array(
            "employee_time_in_id"=>$emp_time_id,
            "comp_id"=>$timein_info->company_id
        );
        #echo "stringxxx";exit();
        $_CI->timeins->update_field("employee_time_in",$field_atp,$where_atp);
    }
}


function for_ppa_transfer_data_hours($emp_time_id) {
    $_CI =& get_instance();
    $_CI->load->model('employee/approval_group_model','agm');
    
    $timein_info = $_CI->agm->timein_information($emp_time_id);
    
    if($timein_info) {
        $where_tot = array(
            "comp_id" => $timein_info->company_id,
            "employee_time_in_id" => $emp_time_id
        );
        
        $_CI->db->where($where_tot);
        
        $data_update = array(
            "change_log_date"                  => $timein_info->date,
            "change_log_tardiness_min"         => $timein_info->tardiness_min,
            "change_log_undertime_min"         => $timein_info->undertime_min,
            "change_log_time_in"               => $timein_info->time_in,
            "change_log_lunch_out"             => $timein_info->lunch_out,
            "change_log_lunch_in"              => $timein_info->lunch_in,
            "change_log_break1_out"            => $timein_info->break1_out,
            "change_log_break1_in"             => $timein_info->break1_in,
            "change_log_break2_out"            => $timein_info->break2_out,
            "change_log_break2_in"             => $timein_info->break2_in,
            "change_log_time_out"              => $timein_info->time_out,
            "change_log_total_hours"           => $timein_info->total_hours,
            "change_log_total_hours_required"  => $timein_info->total_hours_required,
            "change_log_late_min"              => $timein_info->late_min,
            "change_log_overbreak_min"         => $timein_info->overbreak_min,
        );
        
        $update_change_logs = $_CI->db->update('employee_time_in', $data_update);
        
        return true;
    } else {
        return false;
    }
}

function get_approvers_name_and_status_v2($comp_id, $emp_id, $application_id, $application_type) {
    $CI =& get_instance();
    
    if($application_type == "leave") {
        $name = "Leave";
        $where = array(
            "comp_id" => $comp_id,
            "emp_id" => $emp_id,
            "leave_id" => $application_id
        );
        
        $CI->db->where($where);
        $q = $CI->edb->get("approval_leave");
        
        $r = $q->row();
    } elseif($application_type == "attendance_adjustment" || $application_type == "add_logs" || $application_type == "mobile_clock_in") {
        if($application_type == "add_logs") {
            $name = "Add Timesheet";
        } elseif($application_type == "attendance_adjustment") {
            $name = "Timesheet Adjustment";
        } elseif($application_type == "mobile_clock_in") {
            $name = "Mobile Clock-in";
        }
        
        $where = array(
            "comp_id" => $comp_id,
            "emp_id" => $emp_id,
            "time_in_id" => $application_id
        );
        
        $CI->db->where($where);
        $q = $CI->edb->get("approval_time_in");
        
        $r = $q->row();
    } elseif($application_type == "overtime") {
        $name = "Overtime";
        $where = array(
            "comp_id" => $comp_id,
            "emp_id" => $emp_id,
            "overtime_id" => $application_id
        );
        
        $CI->db->where($where);
        $q = $CI->edb->get("approval_overtime");
        
        $r = $q->row();
    }
    
    if($r) {
        $sel = array(
            "e.last_name",
            "e.first_name"
        );
        
        $s = array(
            "ag.emp_id",
            "ag.company_id",
            "ag.level"
        );
        
        $w = array(
            "ag.company_id"=>$comp_id,
            "ag.approval_groups_via_groups_id"=>$r->approver_id,
            "ap.name"=> $name
        );
        
        $CI->db->select($s);
        $CI->edb->select($sel);
        $CI->db->where($w);
        $CI->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
        $CI->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $CI->edb->join("approval_process AS ap","ag.approval_process_id = ap.approval_process_id","LEFT");
        $CI->db->order_by("ag.level","ASC");
        $CI->db->group_by("ag.level");
        $q1 = $CI->edb->get("approval_groups AS ag");
        $r1 = $q1->result();
        
        return ($r1) ? $r1 : FALSE ;
    } else {
        return false;
    }
}

function holiday_leave_approval_settings($comp_id) {
    $CI =& get_instance();
    
    $w = array(
        "company_id" => $comp_id
    );
    
    $CI->db->where($w);
    $q = $CI->db->get('holiday_pay_settings');
    $r = $q->row();
    
    if($r) {
        if($r->regular_holiday_approved_leave == "yes") {
            return TRUE;
        } else {
            return FALSE;
        }
    } else {
        return FALSE;
    }
}

function data_privacy_positive_list($account_id) {
    $CI =& get_instance();
    $w = array(
        "account_id" => $account_id
    );
    
    $CI->db->where($w);
    $q = $CI->db->get('accounts_data_privacy');
    $r = $q->row();
    
    if($r) {
        return true;
    } else {
        return false;
    }
}

function assign_cancel_credits_to_unpaid_leave($emp_id,$company_id,$leave_type_id,$employee_leave_application_id,$my_credits_now) {
    $CI =& get_instance();
    $CI->load->model('employee/employee_v2_model','employee_v2');
    $CI->load->model('employee/employee_model','employee');

    $aa = $CI->employee_v2->get_current_period_for_leave($emp_id,$company_id, date("Y-m-d"));
    $credited_approve_balance = $my_credits_now;
    #p($credited_approve_balance);
    if($aa && $credited_approve_balance > 0) {
        $approved_leave_for_cancel_assign = $CI->employee_v2->approved_leave_for_cancel_assign($aa->cut_off_from,$emp_id, $company_id,$employee_leave_application_id,$leave_type_id);
        $leave_units = $CI->leave->get_leave_restriction($leave_type_id,'leave_units');
        #p("initial credits ".$credited_approve_balance);
        #p($approved_leave_for_cancel_assign);
        if ($approved_leave_for_cancel_assign) {
            foreach ($approved_leave_for_cancel_assign as $r) {
               
                $check_for_working_hours = $CI->employee->for_leave_hoursworked_ws($emp_id,$company_id,date("l",strtotime($r->shift_date)),$r->work_schedule_id);

                if($leave_units == "days") {
                    $credited_this_day = $check_for_working_hours / $check_for_working_hours;
                } else {
                    $credited_this_day = $check_for_working_hours / 8;
                }
                
                if($credited_this_day != $r->credited) {

                    if($credited_this_day > $r->credited && $r->credited > 0) {
                        $update_credited = ($credited_this_day - $r->credited) + $credited_approve_balance;
                        $xxx = $credited_this_day - $r->credited;
                        $xx = $credited_approve_balance;
                        $credited_approve_balance = $xx - $xxx;
                    } else {
                        $update_credited = $credited_approve_balance;
                        $credited_approve_balance = $r->credited - $update_credited;
                    }

                    $credited_approve_balance = $credited_approve_balance;

                    $upt_data = array(
                        "credited" => round($update_credited,3),
                    );

                    #p("credited=".$update_credited);
                    
                    #p("remaining=".$credited_approve_balance);
                    $upt_where = array(
                        "emp_id" => $emp_id,
                        "employee_leaves_application_id" => $r->employee_leaves_application_id
                    );

                    eupdate('employee_leaves_application', $upt_data, $upt_where);

                }
            }
        }
    }
}

function maternity_disbursement($data_param) {
    $CI =& get_instance();
    if($data_param) {
        $full_data = array();
        
        $full_data_temp = array(
            "employee_leaves_application_id" => $data_param['employee_leaves_application_id'],
            "company_id" => $data_param['company_id'],
            "emp_id" => $data_param['emp_id'],
            "classification" => $data_param['classification'],
            "amount" => $data_param['amount'],
            "cost_center_code" => "",
            "cost_center_id" => "",
            "include_to_payroll" => $data_param['include_to_payroll'],
            "tax_exemption" => $data_param['tax_exemption'],
            "allowance_app_id" => "",
            "total_maternity_leave_amount" => $data_param['total_maternity_leave_amount'],
            "mat_total_sss_maternity_benefit" => $data_param['mat_total_sss_maternity_benefit'],
            "mat_total_sss_maternity_benefit_employer" => $data_param['mat_total_sss_maternity_benefit_employer'],
            "mat_get_employee_sss" => $data_param['mat_get_employee_sss'],
            "mat_get_employee_hdmf" => $data_param['mat_get_employee_hdmf'],
            "mat_get_employee_philhealth" => $data_param['mat_get_employee_philhealth']
        );
        
        // array_push($full_data, $full_data_temp);
        array_push($full_data, $data_param);
        
        $full_data = json_encode($full_data);
        
        $val = array(
            "payroll_group_id"=>0,
            "department_id"=>0,
            "disbursement_date" => date("Y-m-d"),
            "disbursement_voucher_number" => null,
            "company_id" => $data_param['company_id'],
            "custom_emp_ids" => $data_param['emp_id'],
            #"amount"=>$disbursement_amnt,
            "amount"=> 0,  // gi zero na ang amount ky wala nmn ni gigamit but d man delete ang field ky naa man sud, ang v1 na disbursement
            "disbursement_classification_id" => null,  // gi zero na ang amount ky wala nmn ni gigamit but d man delete ang field ky naa man sud, ang v1 na disbursement
            "disbursement_classification" => null,  // gi zero na ang amount ky wala nmn ni gigamit but d man delete ang field ky naa man sud, ang v1 na disbursement
            "initiated_request_accnt_id" => "",
            "date_initiated" => date("Y-m-d H:i:s"),
            "date_approved" => date("Y-m-d H:i:s"),
            "disbursement_status" => "Closed",
            "full_data" => $full_data
        );
        #p($val);exit();
        $CI->db->insert("disbursement_application",$val);
    }
}

/*function auto_assign_acl_type($data_param) {
    $CI =& get_instance();
    if($data_param) {
        $acl_type_w = array(
            'status'=>'Active',
            'company_id'=>$data_param['company_id'],
            'flag_is_ml'=>'2'
        );

        $leave_type_settings = get_table_info('leave_type', $acl_type_w);
        
        if ($leave_type_settings) {
            $val_save = array(
                "company_id"=>$CI->db->escape_str($data_param['company_id']),
                "emp_id"=>$CI->db->escape_str($data_param['emp_id']),
                "leave_type_id"=>$CI->db->escape_str($leave_type_settings->leave_type_id),
                #"leave_type_id"=>'448',
                'paid_leave' => $CI->db->escape_str($leave_type_settings->paid_leave),    
                'num_days_before_leave_application' => $CI->db->escape_str($leave_type_settings->num_days_before_leave_application),
                "leave_credits" => $CI->db->escape_str($leave_type_settings->leave_credits_per_year),
                "leave_accrue" => $CI->db->escape_str($leave_type_settings->leave_credits_accrual),
                "effective_start_date"=>$CI->db->escape_str($leave_type_settings->effective_start_date),
                "effective_start_date_by"=>$CI->db->escape_str($leave_type_settings->effective_start_date_by),
                "as_of"=>date("Y-m-d H:i:s"),
                "created_date"=>date("Y-m-d H:i:s"),
                "created_by_account_id"=>$CI->session->userdata("account_id"),
                "status"=>"Active",
                'as_of_date_created'=>date("Y-m-d"),
                "start_of_accrual"=>$CI->db->escape_str($leave_type_settings->start_of_accrual),
                "start_of_accrual_day"=> $CI->db->escape_str($leave_type_settings->start_of_accrual_day),
                "existing_leave_used_to_date"=>0,
                "remaining_leave_credits"=>0
            );
           
            $effective_date = '';
            $where_epi = array(
                'emp_id'=>$data_param['emp_id'],
                'status'=>'Active'
            );

            $get_employee_info = get_table_info('employee_payroll_information', $where_epi);
            if ($get_employee_info) {
                $val_save['rank_id'] = $get_employee_info->rank_id;
                $num_effect = $leave_type_settings->effective_start_date;
                $by_effect = $leave_type_settings->effective_start_date_by;
                $effective_date_by = ($num_effect != "" || $num_effect == "0") ? " +{$num_effect} {$by_effect}" : "";
                $effective_date = date("Y-m-d",strtotime($get_employee_info->date_hired."{$effective_date_by}"));
                $val_save['effective_date'] = $effective_date;
            }
            $insert = $CI->db->insert("employee_leaves",$val_save);
           
        }
    }
    
}*/

/** helper for drill down - recode [start] **/
function get_employee_report_to_manager($emp_id,$company_id){
    $CI =& get_instance();
    $konsum_key = konsum_key();
    $where = array(
            'e.status'              =>'Active',
            'e.deleted'             =>'0',
            'a.deleted'             =>'0',
            'a.user_type_id'        =>'5',
            'epi.employee_status'   => 'Active',
            'edrt.parent_emp_id'    => $emp_id
    );
    $s = array(
            'a.account_id','e.emp_id','e.first_name',
            'e.middle_name','e.last_name',
            'a.payroll_cloud_id'
    );
    $CI->edb->select($s);
    $w = array(
            "e.company_id"=>$company_id
    );
    $CI->db->where($w);
    $CI->edb->where($where);
    $CI->edb->join('employee AS e','e.account_id=a.account_id','INNER');
    $CI->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","LEFT");
    $CI->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
    $q = $CI->edb->get('accounts AS a');
    $res = $q->result();

    return $res;
}

function get_first_level_direct_reports_to_v2($company_id,$emp_id,$first_level_emp_id){
    $CI =& get_instance();

    $s = array(
        "e.emp_id",
        "e.last_name",
        "e.first_name",
    );

    $where_this_id = array(
        #'edr.parent_emp_id'=>$emp_id,
        'edr.company_id'=>$company_id
    );

    if($first_level_emp_id !== ""){
        $where_this_id["edr.parent_emp_id"] = $first_level_emp_id;
    }else{
        $where_this_id["edr.parent_emp_id"] = $emp_id;
    }

    $CI->edb->select($s);
    $CI->db->where($where_this_id);
    $CI->db->order_by("edr.emp_reports_to_id","ASC");

    $CI->edb->join("employee AS e","edr.emp_id = e.emp_id","LEFT");
    $CI->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
    $CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
    #$CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
    #$CI->edb->join("position AS p","epi.position = p.position_id","LEFT");
    $query = $CI->edb->get('employee_details_reports_to AS edr');
    $r = $query->result();
    
    if($r) {
        $result_arr = array();
        foreach ($r as $key => $row) {
            $w = array(
                'employee.company_id'   => $company_id,
                'employee.status'       => 'Active',
                'employee.deleted'      => '0',
                'accounts.user_type_id' => '5',
                'accounts.deleted'      => '0',
                'employee_payroll_information.employee_status'  => 'Active',
                'edrt.parent_emp_id'    => $row->emp_id
            );

            $CI->db->where($w);

            $CI->edb->join('accounts','accounts.account_id = employee.account_id','INNER');
            $CI->edb->join('employee_payroll_information','employee_payroll_information.emp_id = employee.emp_id','left');
            $CI->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = employee.emp_id","LEFT");
            $q = $CI->edb->get('employee');
            $res = $q->row();

            if($res) {
                $temp = array(
                    "last_name" => $row->last_name,
                    "first_name" => $row->first_name,
                    "emp_id" => $row->emp_id,
                    "main_manager_id" => $first_level_emp_id,
                );

                array_push($result_arr, (object) $temp);
            }
        }
        return ($result_arr) ? $result_arr : FALSE ;
    }
}

function get_parent_employee_details_v2($company_id,$first_level_emp_id){
    $CI =& get_instance();

    $where_this_id = array(
        'e.emp_id'=>$first_level_emp_id,
        'e.company_id'=>$company_id
    );

    $CI->db->where($where_this_id);
    $CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
    $query = $CI->edb->get('employee AS e');
    $result = $query->row();
    return ($result) ? $result : FALSE ;
}

function mp_search_employee($company_id,$not_your_emp_id,$emp_id) {
    if($not_your_emp_id != ""){
        $x = get_employee_report_to_manager($not_your_emp_id,$company_id);
    } else {
        $x = get_employee_report_to_manager($emp_id,$company_id);
    }
    
    $employee_list = array();

    if($x){
        foreach($x as $k=>$v){
            $employee_list[] = $v->first_name." ".$v->last_name;
            $employee_list[] = $v->payroll_cloud_id;
            $employee_list[] = $v->emp_id;
        }
    }
    
    return $employee_list;
}

function mp_search_employee_tsheet($company_id,$not_your_emp_id,$emp_id) {
    if($not_your_emp_id != ""){
        $x = get_employee_report_to_manager($not_your_emp_id,$company_id);
    } else {
        $x = get_employee_report_to_manager($emp_id,$company_id);
    }
    
    $employee_list = array();

    if($x){
        foreach($x as $k=>$v){
            $employee_list[] = $v->first_name." ".$v->last_name;
            $employee_list[] = $v->payroll_cloud_id;
            $employee_list[] = $v->emp_id;
        }
    }
    
    return $employee_list;
}


/** helper for drill down - recode [end] **/

function count_pending_disbursement_application($emp_id, $company_id) {
    $CI =& get_instance();
    $CI->load->model("workforce/approve_disbursement_model","disburse");

    if($emp_id == "-99{$company_id}") {
        $count = count($CI->disburse->get_disbursement_application($company_id));
    } else {
        $count = 0;
    }
    
    /*$array_disbursement_app = array();
    
    $wr = array(
        "company_id" => $company_id,
        "disbursement_status" => "Waiting for approval",
        "status" => "Active"
    );
    $CI->db->where($wr);
    $q = $CI->db->get("disbursement_application");
    $result = $q->result();
    $q->free_result();
    
    if($result) {
        foreach ($result as $r) {
            $wr_db = array(
                "disbursement_classification" => $r->disbursement_classification,
                "custom_emp_ids" => $r->custom_emp_ids,
                "payroll_group_id" => $r->payroll_group_id,
                "amount" => $r->amount,
                "disbursement_date" => $r->disbursement_date,
                "custom_search" => "{$r->disbursement_application_id}"
            );
            array_push($array_disbursement_app,$wr_db);
            
        }
    }*/
    
    return $count;

}

function get_ppa_option_settings($company_id, $emp_id, $shiftdate, $portal="admin") {
    $CI =& get_instance();
    $CI->load->model('employee/employee_v2_model','employee_v2');

    $check_payroll_lock_closed = $CI->employee_v2->check_payroll_lock_closed($emp_id,$company_id,$shiftdate);
    $flag_error = false;
    $err_msg = "";
    $err_msg_limiting = "";

    if($check_payroll_lock_closed == "Closed") {
        $res = array();
        $wr = array(
            "company_id" => $company_id,
        );

        $CI->db->where($wr);
        $q = $CI->db->get("ppa_option_settings");
        $result = $q->row();

        if($result) {
            $get_ppa_limit_period = get_ppa_limit_period($company_id, $emp_id, $shiftdate, $result->limiting_period_date);

            if($portal == "employee") {
                if($result->enable_ppa_option == "yes" && $result->enable_employee_ppa == "no") {
                    $flag_error = true;
                    $err_msg = "You cannot apply for Post Payroll Adjustment.";
                }
            } else {
                if($result->enable_ppa_option == "no") {
                    $flag_error = true;
                    $err_msg = "You cannot apply for Post Payroll Adjustment.";
                }
            }

            if(!$get_ppa_limit_period) {
                $flag_error = true;
                $err_msg_limiting = "You cannot apply for Post Payroll Adjustment for this period.";
               
            }

            $res = array(
                "error" => $flag_error,
                "err_msg" => $err_msg,
                "err_msg_limiting" => $err_msg_limiting
            );

            return $res;

        } else { // default : enable all ppa option
            $get_ppa_limit_period = get_ppa_limit_period($company_id, $emp_id, $shiftdate, 6);
            $flag_error = false;

            if(!$get_ppa_limit_period) {
                $flag_error = true;
                $err_msg_limiting = "You cannot apply for Post Payroll Adjustment for this period.";
               
            }

            $res = array(
                "error" => $flag_error,
                "err_msg" => "",
                "err_msg_limiting" => $err_msg_limiting
            );
            return $res;
        }
    } else {
        $res = array(
            "error" => false,
            "err_msg" => "",
            "err_msg_limiting" => ""
        );
        return $res;
    }

    
}

function get_ppa_limit_period($company_id, $emp_id, $shiftdate, $limit=6) {
    $CI =& get_instance();
    $res = false;

    $s = array(
        "pg.period_type",
    );
    
    $w = array(
        'epi.company_id'    => $company_id,
        "epi.emp_id"        => $emp_id
    );
    
    $CI->db->select($s);
    $CI->db->where($w);
    $CI->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
    $q = $CI->db->get('employee_payroll_information AS epi');
    $r = $q->row();
    
    if($r){
        $w = array(
            'pay_schedule' => $r->period_type,
            'company_id'    => $company_id,
            'cut_off_to <' => date("Y-m-d")
        );

        $CI->db->where($w);
        $CI->db->order_by('first_payroll_date','DESC');
        $qs = $CI->db->get('payroll_calendar',$limit);
        $rs = $qs->result();
        
        if($rs) {
            $total_row = count($rs);

            $count = 0;

            $cut_off_to = date("Y-m-d");
            foreach ($rs as $key => $row) {
                $count++;

                if($total_row == $count) {
                    $cut_off_from = $row->cut_off_from;
                    #p($cut_off_from.' '.$cut_off_to.'=='.$shiftdate);exit();
                    if(strtotime($cut_off_from) <= strtotime($shiftdate) && strtotime($cut_off_to) >= strtotime($shiftdate)) {
                       
                        $res = true;
                    }
                }
            }
        }

        return $res;

    } else {
        return false;
    }
}

function get_ppa_option_settings_OS($company_id, $shiftdate, $emp_id, $portal="admin") {
    $CI =& get_instance();
    $CI->load->model('employee/employee_v2_model','employee_v2');

    $check_payroll_lock_closed = $CI->employee_v2->check_payroll_lock_closed($emp_id,$company_id,$shiftdate);

    $flag_error = false;
    $err_msg = "";
    $err_msg_limiting = "";

    $res = array();
    $wr = array(
        "company_id" => $company_id,
    );

    $CI->db->where($wr);
    $q = $CI->db->get("ppa_option_settings");
    $result = $q->row();

    if($check_payroll_lock_closed == "Closed") {
        if($result) {
            if($portal == "employee") {
                if($result->enable_ppa_option == "yes" && $result->enable_employee_ppa == "no") {
                    $flag_error = true;
                    $err_msg = "You cannot apply for Post Payroll Adjustment.";
                }
            } else {
                if($result->enable_ppa_option == "no") {
                    $flag_error = true;
                    $err_msg = "You cannot apply for Post Payroll Adjustment.";
                }
            }

            $res = array(
                "error" => $flag_error,
                "err_msg" => $err_msg,
                "err_msg_limiting" => $err_msg_limiting
            );

            return $res;

        } else { // default : enable all ppa option
            $flag_error = false;

            $res = array(
                "error" => $flag_error,
                "err_msg" => "",
                "err_msg_limiting" => ""
            );
            return $res;
        }
    } else {
         $flag_error = false;

        $res = array(
            "error" => $flag_error,
            "err_msg" => "",
            "err_msg_limiting" => ""
        );
        return $res;
    }
}

function open_shift_todo_counter($emp_id, $company_id){
    $CI =& get_instance();

    $where = array(
        'ee.comp_id' => $company_id,
        'ee.status' => 'Active',
        'ee.corrected' => 'Yes',
        "ag.emp_id" => $emp_id,
        'ee.time_in_status'=> 'pending',
        'ee.source !=' => 'mobile',
        "aost.level !=" => "",
        'ee.flag_open_shift' => '1',
        "aost.application_status" => "pending",
        
    );
    
    $select = array(
        "ee.emp_id",
    );

    $CI->db->select($select);

    
    $CI->db->where_in("app.name", array("Add Timesheet", "Timesheet Adjustment"));
    $CI->db->where($where);
 
    
    $CI->db->join('employee AS e','e.emp_id = ee.emp_id','LEFT');
    $CI->db->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','LEFT');
    $CI->db->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id OR epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    
    $CI->db->join("approval_open_shift_timein AS aost","aost.approval_open_shift_timein_id= ee.os_approval_time_in_id","LEFT");
    $CI->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND aost.level = ag.level","LEFT");
    $CI->db->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
    
    $CI->db->group_by("ee.employee_time_in_id");
    
    $CI->db->order_by("ee.date","DESC");
    
    $query = $CI->db->get('employee_time_in AS ee');
    $result = $query->result();
    $query->free_result();

    return $result;
    
}

function open_shift_todo_list_ppa_counter($emp_id, $company_id){ // PPAOS01
    $CI =& get_instance();

    $where = array(
        'ee.comp_id' => $company_id,
        'ee.status' => 'Active',
        "ag.emp_id" => $emp_id,
        'ee.time_in_status'=> 'pending',
        'ee.source !=' => 'mobile',
        "aost.level !=" => "",
        'ee.flag_open_shift' => '1',
        "aost.application_status" => "pending",
        
    );
    
    $select = array(
        "ee.emp_id",
    );
    
    $CI->db->select($select);
    
    $CI->db->where_in("app.name", array("Timesheet Adjustment"));
    $CI->db->where($where);
 
    
    $CI->edb->join('employee AS e','e.emp_id = ee.emp_id','LEFT');
    $CI->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','LEFT');
    $CI->edb->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    
    $CI->edb->join("approval_open_shift_timein AS aost","aost.approval_open_shift_timein_id= ee.os_approval_time_in_id","LEFT");
    $CI->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND aost.level = ag.level","LEFT");
    $CI->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");

    
    $CI->db->order_by("ee.date","DESC");
    
    $query = $CI->edb->get('timesheet_close_payroll AS ee');

    $result = $query->result();
    $query->free_result();
    
    return $result;
    
}

function auto_reject_ts_application($company_id,$account_id) {
    $CI =& get_instance();
    $CI->load->model('employee/employee_v2_model','employee_v2');
    $CI->load->model("workforce/approve_timeins_model","timeins");
    $CI->load->model('employee/employee_model','employee');

    $where = array(
        'ee.comp_id'   => $company_id,
        'ee.status'   => 'Active',
        'ee.corrected' => 'Yes',
        'ee.time_in_status'=> 'pending',
        'ee.flag_payroll_correction' => 'no',
        "at.level !=" => "",
        //"ee.rest_day_r_a" => $rest_day_r_a,
        //"ee.holiday_approve" => $holiday_approve

    );
    

    $select = array(
        "ee.work_schedule_id",
        "ee.comp_id",
        "at.flag_add_logs",
        "epi.attendance_adjustment_approval_grp",
        "epi.add_logs_approval_grp",
        "epi.location_base_login_approval_grp",
        "ee.employee_time_in_id",
        "at.level",
        "ee.time_in_status",
        "at.approve_by_hr",
        "ee.emp_id",
        "ee.date",
        "ee.source",
        "ee.time_in",
        "ee.lunch_out",
        "ee.lunch_in",
        "ee.time_out",
        "ee.total_hours",
        "ee.total_hours_required",
        "ee.tardiness_min",
        "ee.undertime_min",
        "ee.change_log_time_in",
        "ee.change_log_lunch_out",
        "ee.change_log_lunch_in",
        "ee.change_log_time_out",
        "ee.change_log_break1_out",
        "ee.change_log_break1_in",
        "ee.change_log_break2_out",
        "ee.change_log_break2_in",
        "ee.change_log_total_hours",
        "ee.change_log_total_hours_required",
        "ee.change_log_tardiness_min",
        "ee.change_log_undertime_min",
        "ee.change_log_date_filed",
        "ee.last_source",
        "ee.reason",
        "e.company_id",
        "ee.rest_day_r_a",
        "ee.holiday_approve",
        "ee.flag_rd_include",
        "ee.flag_holiday_include",
        "ee.for_resend_auto_rejected_id",
        "ee.change_log_work_schedule_id",
        "ee.approval_time_in_id",
        "ee.flag_regular_or_excess",
        "ee.timesheet_not_req_flag",
        "ee.partial_log_ded_break",
        "ee.flag_open_shift",
        "ee.os_approval_time_in_id",
        "ee.change_log_overbreak_min",
        "ee.change_log_late_min",
        "ee.change_log_date"
    );

    $CI->edb->select($select);
        
    $CI->db->where_in("app.name", array("Add Timesheet", "Timesheet Adjustment"));
    $CI->db->where($where);
    $CI->db->where(" (ee.source ='EP' OR ee.last_source='Adjusted') ",NULL,FALSE);

        
    $CI->edb->join('employee AS e','e.emp_id = ee.emp_id','LEFT');
    $CI->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','LEFT');
    $CI->edb->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id OR epi.add_logs_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    
    $CI->edb->join("approval_time_in AS at","at.approval_time_in_id= ee.approval_time_in_id","LEFT");
    $CI->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND at.level = ag.level","LEFT");
    $CI->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
    $CI->edb->join("work_schedule AS ws","ee.work_schedule_id = ws.work_schedule_id","LEFT");
    $CI->db->group_by("ee.employee_time_in_id");
   
    $CI->db->order_by("ee.date","DESC");

    $query = $CI->edb->get('employee_time_in AS ee');
    $result = $query->result();
    $query->free_result();

    if($result) {
        foreach ($result as $key => $row) {
            $void_v2 = $CI->employee_v2->check_payroll_lock_closed($row->emp_id,$row->company_id,date("Y-m-d", strtotime($row->date)));

            if($void_v2 == "Closed" && ($row->for_resend_auto_rejected_id == null || $row->for_resend_auto_rejected_id == "")){
                $rejected = false;
                $auto_remarks = "Auto-rejected due to approval timelapse.";
                $auto_approval_date = date("Y-m-d H:i:s");
                $auto_time_in_status = "reject";
                $auto_date = ($row->change_log_date!=NULL) ? date("Y-m-d",strtotime($row->change_log_date)) : date("Y-m-d",strtotime($row->date));
                $auto_ws_id = ($row->change_log_work_schedule_id != NULL) ? $row->change_log_work_schedule_id : $row->work_schedule_id;


                // for new todo structure param
                $check_holiday = $CI->employee->get_holiday_date($row->date,$row->emp_id,$company_id);
                if ($check_holiday) {
                    $check_if_holiday_approval = holiday_approval_settings($company_id);
                } else {
                    $check_if_holiday_approval = false;
                }

                $check_if_enable_working_on_restday = check_if_enable_working_on_restday($company_id,$auto_ws_id);

                if ($check_if_enable_working_on_restday || $auto_ws_id == "-1") {
                    $module_for_new_todo = "rd_ra";
                } elseif ($check_if_holiday_approval) {
                    $module_for_new_todo = "holiday";
                } else {
                    $module_for_new_todo = "hours";
                }

                $hours_category = "";
                $flag = "";
                $approval_grp = "";

                if($row->flag_add_logs==0){
                    $hours_category = "attendance adjustment";
                    $flag = 0;
                    $approval_grp = $row->attendance_adjustment_approval_grp;
                }elseif($row->flag_add_logs==1){
                    $hours_category = "add logs";
                    $flag = 1;
                    $approval_grp = $row->add_logs_approval_grp;
                }

                $auto_source = "";
                if($hours_category == "attendance adjustment") {
                    $auto_source = "Adjusted";
                } elseif($hours_category == "add logs") {
                    $auto_source = "EP";
                }
                
                $fields = array(
                    "time_in_status" => $auto_time_in_status,
                    "notes" => $auto_remarks,
                    "approval_date" => $auto_approval_date
                );
                
                if($flag == 0) {
                    $fields = array(
                        "time_in_status" => null,
                        "last_source" => null,
                        "notes" => $auto_remarks,
                        "approval_date" => $auto_approval_date
                    );
                }
                
                $where = array(
                    "employee_time_in_id" => $row->employee_time_in_id,
                    "comp_id" => $company_id
                );
                
                $CI->timeins->update_field("employee_time_in",$fields,$where);
                $rejected = true;
                
                
                if($rejected){
                    $employee_details = get_employee_details_by_empid($row->emp_id);
                    $emp_name = ucwords($employee_details->first_name." ".$employee_details->last_name);
                    $emp_email = $employee_details->email;
                    $emp_account_id = $employee_details->account_id;

                    $audit_trail_from = array(
                        "ref_number" => $row->employee_time_in_id,
                        "application_type" => $hours_category." (".$module_for_new_todo.")",
                        "applied_by" => $emp_name,
                        "next_approver" => "",
                        "shift_date" => $auto_date,
                        "notes" => $auto_remarks,
                        "application_status" => $auto_time_in_status,
                        "system_message" => $auto_time_in_status
                    );
                    
                    settings_audit_trail($company_id, $account_id, "employee", "approval", $audit_trail_from, array(), $row->emp_id);
                    
                    ################################ notify staff start ###############################
                    if($approval_grp != "") {
                        $approval_group_notification = get_notify_settings($approval_grp, $company_id);

                        if($approval_group_notification) {
                            if($approval_group_notification->notify_staff == "yes"){
                                // notify staff via email
                                timesheet_auto_reject($row->employee_time_in_id, $company_id, $emp_email, true);
                                
                            }
                        }
                        
                    }
                    ################################ notify staff end ################################
                    
                    inactive_todo_data($company_id,$row->emp_id,$row->approval_time_in_id,$module_for_new_todo);
                }
                
            }
        }
    }
    
}

function auto_reject_ot_application($company_id,$account_id){
    $CI =& get_instance();
    $CI->load->model('employee/employee_v2_model','employee_v2');

    $where = array(
        'o.company_id'               => $company_id,
        'o.deleted'                  => '0',
        //"ag.emp_id"                  => $emp_id,
        'epi.employee_status'        => 'Active',
        "o.overtime_status"          => "pending" ,
        "ao.level !="                => ""
        //"o.flag_payroll_correction"  => $payroll_correction
    );

    $select = array(
        "epi.overtime_approval_grp",
        "ao.level",
        "o.overtime_id",
        "o.overtime_status",
        "o.overtime_date_applied",
        "o.overtime_from",
        "o.start_time",
        "o.end_time",
        "o.no_of_hours",
        "o.reason",
        "o.notes",
        "o.emp_id",
        "o.company_id",
        "o.for_resend_auto_rejected_id",
        "o.flag_open_shift",
        "o.period_from",
        "o.period_to"
        #"pg.name AS pg_name"
    );
    
    $CI->edb->select($select);
    $CI->edb->where($where);

    $CI->edb->join('employee AS e','e.emp_id = o.emp_id','left');
    $CI->edb->join("employee_payroll_information AS epi","epi.emp_id = o.emp_id","LEFT");
    $CI->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    $CI->edb->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
    $CI->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND ao.level = ag.level","LEFT");
    $CI->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
    
    $CI->db->group_by("o.overtime_id");
    
    $query = $CI->edb->get('employee_overtime_application AS o');
    $result = $query->result();

    if($result) {
        foreach ($result as $key => $row) {
            $void_v2 = $CI->employee_v2->check_payroll_lock_closed($row->emp_id,$company_id,date("Y-m-d", strtotime($row->overtime_from)));

            if($void_v2 == "Closed" && ($row->for_resend_auto_rejected_id == null || $row->for_resend_auto_rejected_id == "")){

                $auto_remarks = "Auto-rejected due to approval timelapse.";
                $auto_approval_date = date("Y-m-d H:i:s");
                $auto_time_in_status = "reject";

                 $fields = array(
                    "overtime_status" => $auto_time_in_status,
                    "notes" => $auto_remarks,
                    "approval_date" => $auto_approval_date
                );
                
                $where = array(
                    "overtime_id" => $row->overtime_id,
                    "company_id" => $company_id
                );
                
                $CI->db->where($where);
                $CI->db->update("employee_overtime_application",$fields);

                
                $employee_details = get_employee_details_by_empid($row->emp_id);
                $emp_name = ucwords($employee_details->first_name." ".$employee_details->last_name);

                $audit_trail_from = array(
                    "ref_number" => $row->overtime_id,
                    "application_type" => "Overtime",
                    "applied_by" => $emp_name,
                    "next_approver" => "",
                    "shift_date" => $row->overtime_from,
                    "notes" => $auto_remarks,
                    "application_status" => $auto_time_in_status
                );
                
                settings_audit_trail($company_id, $account_id, "employee", "approval", $audit_trail_from, array(), $row->emp_id);
            }
        }
    }
}

function auto_reject_lv_application($company_id,$account_id){
    $CI =& get_instance();
    $CI->load->model('employee/employee_v2_model','employee_v2');

    $where = array(
        "el.company_id" =>$company_id,
        "el.deleted" => '0',
        'epi.employee_status' => 'Active',
        "el.leave_application_status"=>"pending",
        "al.level !=" => "",
        "empl.status" =>  'Active',
        "el.work_schedule_id !=" => "-3"
    );

    $select = array(
        #"*",
        #"pg.name AS pg_name",
        "empl.remaining_leave_credits AS remaining_c",
        "epi.leave_approval_grp",
        "al.level",
        "el.employee_leaves_application_id",
        "el.leave_application_status",
        "al.approve_by_hr",
        "el.emp_id",
        "el.company_id",
        "el.shift_date",
        "el.date_filed",
        "lt.leave_type_id",
        "el.date_start",
        "el.date_end",
        "el.total_leave_requested",
        "el.leave_cedits_for_untitled",
        "el.reasons",
        "el.required_file_documents",
        "el.for_resend_auto_rejected_id",
        "el.cancellable",
        "el.previous_credits",
        "el.work_schedule_id",
        "el.allocate_days",
        "el.alternate_caregiver_name",
        "el.maternity_leave_type",
        "el.child_birthdate",
        "el.govt_contribution_sss",
        "el.govt_contribution_phic",
        "el.govt_contribution_pagibig"
    );

    $CI->edb->select($select);
    $CI->db->where($where);

    $CI->edb->join("employee AS e","e.emp_id = el.emp_id","LEFT");
    $CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
    $CI->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    
    $CI->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
    $CI->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND al.level = ag.level","LEFT");
    $CI->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
    $CI->edb->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
    $CI->edb->join("employee_leaves AS empl","empl.leave_type_id = el.leave_type_id and empl.emp_id = e.emp_id","LEFT");
    $CI->db->group_by('el.employee_leaves_application_id');
    
    $CI->db->order_by("date_filed","DESC");
    
    $query = $CI->edb->get("employee_leaves_application AS el");

    $result = $query->result();

    if($result) {
        foreach ($result as $key => $row) {

            $void_v2 = $CI->employee_v2->check_payroll_lock_closed($row->emp_id,$company_id,date("Y-m-d", strtotime($row->shift_date)));

            if($void_v2 == "Closed" && ($row->for_resend_auto_rejected_id == null || $row->for_resend_auto_rejected_id == "")){
                $auto_remarks = "Auto-rejected due to approval timelapse.";
                $auto_approval_date = date("Y-m-d H:i:s");
                $auto_time_in_status = "reject";
                
                $fields = array(
                    "leave_application_status" => $auto_time_in_status,
                    "note" => $auto_remarks,
                    "approval_date" => $auto_approval_date
                );
                
                $where = array(
                    "employee_leaves_application_id"=>$row->employee_leaves_application_id,
                    "company_id"=>$company_id
                );
                
                $CI->db->where($where);
                $CI->db->update("employee_leaves_application",$fields);
                #$rejected = true;
                
                $employee_details = get_employee_details_by_empid($row->emp_id);
                $emp_name = ucwords($employee_details->first_name." ".$employee_details->last_name);
                $audit_trail_from = array(
                    "ref_number" => $row->employee_leaves_application_id,
                    "application_type" => "Leave",
                    "applied_by" => $emp_name,
                    "shift_date" => $row->shift_date,
                    "notes" => $auto_remarks,
                    "application_status" => $auto_time_in_status
                );
                
                settings_audit_trail($company_id, $account_id, "employee", "approval", $audit_trail_from, array(), $row->emp_id);
            }
        }
    }
}

function check_emp_gender($emp_id,$company_id) {
    $CI =& get_instance();

    $s = array(
        "gender"
    );

    $w = array(
        "emp_id" => $emp_id,
        "company_id" => $company_id
    );

    $CI->db->select($s);
    $CI->db->where($w);
    $query = $CI->db->get("employee");

    $result = $query->row();

    if($result) {
        return $result->gender;
    } else {
        return false;
    }
}

function save_todo_acion_history($date_insert) {
    $CI =& get_instance();

    if($date_insert) {
        $CI->db->insert('todo_action_history', $date_insert);
    }
    
}

function show_shift_schedule_on_appform($all_param) {
    $CI =& get_instance();
    $CI->load->model('employee/employee_v2_model','employee_v2');
    $CI->load->model('employee/employee_model','employee');
    $CI->load->model('employee/employee_work_schedule_model','ews');

    if($all_param) {
        $app_date = $all_param['app_date'];
        $emp_id = $all_param['emp_id'];
        $company_id = $all_param['company_id'];
        $data['shift_schedules'] = $all_param['shift_schedules'];
        $data['check_workday'] = $all_param['check_workday'];
        $data['check_sched_split_flex'] = $all_param['check_sched_split_flex'];
        $data['all_sched_block_time_in'] = $all_param['all_sched_block_time_in'];
        $data['all_sched_flex'] = $all_param['all_sched_flex'];
        $data['holidays'] = $all_param['holidays'];
        $data['all_sched_reg'] = $all_param['all_sched_reg'];
        $my_location = $CI->employee_v2->get_location_of_emp($company_id,$emp_id)->location_and_offices_id;

        $for_start = date("Y-m-d", strtotime($app_date));
        $holiday_name = "";

        $emp_ids = array($emp_id);
        $data['shift_schedules'] = $CI->employee_v2->assigned_work_schedule($company_id,$for_start,$for_start,$emp_ids);
        $work_schedule_id = $CI->employee->emp_work_schedule($emp_id,$company_id,$for_start);
        
        $employee_shift_schedule = in_array_custom($emp_id."-".$for_start,$data['shift_schedules']);
        $work_schedule_custom_id = "";
        $rest_day = $CI->ews->get_rest_day($company_id,$work_schedule_id,date("l",strtotime($for_start)));
        $get_open_shift_leave = $CI->employee_v2->get_open_shift_leave($work_schedule_id, $company_id);
        $check_work_type = $CI->employee->work_schedule_type($work_schedule_id, $company_id);
        
        $date = $for_start;
        /*if ($rest_day) {
            $work_schedule_id = $CI->employee->emp_work_schedule($emp_id,$company_id,date("Y-m-d", strtotime($for_start)));
            $rest_day = $CI->ews->get_rest_day($company_id,$work_schedule_id,date("l",strtotime($for_start.' -1 day')));
            $for_start = date("Y-m-d",strtotime($for_start.' -1 day'));
            $date = $for_start;
        }*/
        
        if($employee_shift_schedule) {
            $work_schedule_custom_id = $employee_shift_schedule->work_schedule_id;
        }
        
        $current_monday     = date("Y-m-d",strtotime($date." monday this week"));
        $time_name          = "~";
        $shift              = "";
        $starttime          = "";
        $endtime            = "";
        $totalhours         = 0;
        $full_flex          = false;
        $valid_flexi_date   = "";
        $eendtime           = "";
        $estarttime         = "";
        $is_hol             = false;
        
        $split = in_array_custom("wsi-{$work_schedule_id}",$data['check_workday']);
        if($split){
            if($split->work_type_name == "Workshift"){
                $time_name = "Split Shift";
            } elseif ($get_open_shift_leave) {
                $time_name = "Your shift on this day is open shift";
                $totalhours_mins = $get_open_shift_leave->total_hrs_per_day * 60;
                $totalhours = $get_open_shift_leave->total_hrs_per_day;
                $starttime  = $for_start.' 08:00:00';
                $endtime    = $for_start.' '.date("H:i:s", strtotime($starttime.' +'.$totalhours_mins.' hours'));
                $full_flex = true;
                
                //$valid_flexi_date = date("Y-m-d", strtotime($for_start));
            } else {
                $work_schedule_id1   = in_array_custom($emp_id."-".$date,$data['shift_schedules']);
                if($work_schedule_id1){
                    if($date == $work_schedule_id1->valid_from){
                        $weekday = date('l',strtotime($date));
                        
                        if($work_schedule_id){
                            $fritz_work_schedule_id = $work_schedule_id;
                        } else {
                            $fritz_work_schedule_id = $work_schedule_id1->work_schedule_id;
                        }
                        
                        $work_schedule_info = $CI->employee_v2->work_schedule_info2($company_id,$fritz_work_schedule_id,$weekday,$for_start,$emp_id,0,$data['check_sched_split_flex'],$data['all_sched_block_time_in'],$data['all_sched_flex']);
                        #p($work_schedule_info);
                        if($work_schedule_info){                                        
                            $sstarttime = date("A",strtotime(time12hrs($work_schedule_info["work_schedule"]["start_time"])));
                            $eendtime = date("A",strtotime(time12hrs($work_schedule_info["work_schedule"]["end_time"])));
                            
                            $efor_start = $for_start;
                            if($sstarttime == "PM" && $eendtime == "AM") {
                                $efor_start = date("Y-m-d", strtotime($for_start." +1 day"));
                            }
                            
                            $starttime_new = $for_start.' '.time12hrs($work_schedule_info["work_schedule"]["start_time"]);
                            $endtime_new =  $efor_start.' '.time12hrs($work_schedule_info["work_schedule"]["end_time"]);
                            $total_hours_half_min = ($work_schedule_info["work_schedule"]["total_hours"] / 2) * 60;

                                
                            $starttime1 = time12hrs($work_schedule_info["work_schedule"]["start_time"]);
                            $starttime  = $starttime_new;
                            $endtime1   = time12hrs($work_schedule_info["work_schedule"]["end_time"]);
                            $endtime    = $endtime_new;
                            $totalhours = $work_schedule_info["work_schedule"]["total_hours"];
                            $time_name  = "Your shift on this day starts at: {$starttime1} and ends at {$endtime1}";
                            $threshold_mins = $work_schedule_info["work_schedule"]["threshold_mins"];
                            #p($starttime);
                        }
                    }
                }
                
                $sure = false;
                if($work_schedule_id == 0 || $work_schedule_id == ""){
                    $work_schedule_idz = in_array_custom("emp_id-{$emp_id}-{$for_start}",$data['emp_work_schedule']);
                    if($work_schedule_idz){
                        $work_schedule_idx = $work_schedule_idz->work_schedule_id;
                    } else {
                        $work_schedule_idy = in_array_custom("emp_id-{$emp_id}",$data['emp_payroll_info_wsid']);
                        if($work_schedule_idy){
                            $work_schedule_idx = $work_schedule_idy->work_schedule_id;
                        }
                    }
                } else {
                    $work_schedule_idx = $work_schedule_id;
                }
                
                $work_schedule_idx  = ($work_schedule_custom_id != "") ? $work_schedule_custom_id : $work_schedule_idx;
                $no_schedule        = $CI->employee_v2->get_workschedule_info_for_no_workschedule($company_id,$for_start,$work_schedule_idx,true,$data['all_sched_flex'],$data['all_sched_reg']);
                
                $for_start_m_d = date("m-d", strtotime($for_start));
                $is_holiday         = in_array_custom("date-{$for_start_m_d}",$data['holidays']);
                
                if($is_holiday){
                    $holidate = $is_holiday->date;
                    if ($is_holiday->date_type == "fixed") {
                        $year = date('Y');
                        $myMonthDay = date('m-d', strtotime($holidate));
                        $new_date = $year . '-' . $myMonthDay;
                        $holidate = date("Y-m-d", strtotime($new_date));
                    }

                    if($holidate == $for_start){
                        $is_hol = true;
                    } else {
                        $is_hol = false;
                    }

                    if($is_hol) {
                        $proceed1 = false;
                        
                        if($my_location != 0 || $my_location != null) {
                            if($is_holiday->locations != "" || $is_holiday->locations != null) {
                                $x = explode(",", $is_holiday->locations);
                                foreach ($x as $loc) {
                                    if($loc == $my_location) {
                                        $proceed1 = true;
                                    }
                                }
                            }
                        }
                        
                        if($is_holiday->scope == "local" && !$proceed1) {
                            $is_hol = FALSE;
                        } else {
                            $is_hol = TRUE;
                        }
                        
                    }
                }
                
                if($is_hol){
                    $holiday_name = $is_holiday->holiday_name.' ('.$is_holiday->hour_type_name.')';
                }
                
                if($shift == "" || $no_schedule ){
                    if($no_schedule){
                        if($no_schedule["end_time"] == "") {
                            if($no_schedule["start_time"] == "" || $no_schedule["start_time"] == null) {
                                $full_flex = true;
                            }
                            
                            $starttime  = $for_start.' '.$no_schedule["start_time"];
                            $br_hr      = $no_schedule["break"] / 60;
                            $hr_to_min  = ($no_schedule["total_hours"] * 60) - $no_schedule["break"];
                            $totalhours = $no_schedule["total_hours"] - $br_hr;
                            $endtime    = date("Y-m-d", strtotime($starttime)).' '.date("h:i A", strtotime($starttime." -{$hr_to_min} minutes"));
                            
                            $get_flexi_end_date = date("Y-m-d", strtotime($for_start));
                            
                            #while($rest_n_holiday){
                                $rest_day = $CI->ews->get_rest_day($company_id,$work_schedule_id,date("l",strtotime($get_flexi_end_date)));

                                $holiday_date = date('Y-m-d', strtotime($get_flexi_end_date));
                                $holiday_date_m_d = date("m-d", strtotime($holiday_date));
                                $is_holiday_q = in_array_custom("date-{$holiday_date_m_d}",$data['holidays']);
                                $is_holiday = false;
                          
                                // exclude holiday
                                if($is_holiday_q){
                                    $holidate = $is_holiday_q->date;
                                    if ($is_holiday_q->date_type == "fixed") {
                                        $year = date('Y');
                                        $myMonthDay = date('m-d', strtotime($holidate));
                                        $new_date = $year . '-' . $myMonthDay;
                                        $holidate = date("Y-m-d", strtotime($new_date));
                                    }

                                    if($holidate == $holiday_date){
                                        $is_hol = true;
                                    } else {
                                        $is_hol = false;
                                    }

                                    if($is_hol) {
                                        $proceed1 = false;
                                        
                                        if($my_location != 0 || $my_location != null) {
                                            if($is_holiday_q->locations != "" || $is_holiday_q->locations != null) {
                                                $x = explode(",", $is_holiday_q->locations);
                                                foreach ($x as $loc) {
                                                    if($loc == $my_location) {
                                                        $proceed1 = true;
                                                    }
                                                }
                                            }
                                        }
                                        
                                        if($is_holiday_q->scope == "local" && !$proceed1) {
                                            $is_hol = FALSE;
                                        } else {
                                            $is_hol = TRUE;
                                        }
                                        
                                    }
                                } else {
                                    $is_hol = false;
                                }

                                if($rest_day || $is_hol){
                                    $get_flexi_end_date = date('Y-m-d', strtotime($get_flexi_end_date.' +1 day'));
                                } 
                                #else {
                                 #   $rest_n_holiday = false;
                                #}
                            #}
                                  
                            $valid_flexi_date = $get_flexi_end_date;
                            
                            
                            $time_name = "Your shift on this day requires {$totalhours} hours of work";
                            
                            if($full_flex) {
                                $time_name = "Your shift on this day is flex";
                            }

                            
                        } else {

                            $sstarttime = date("A",strtotime(time12hrs($no_schedule["start_time"])));
                            $eendtime = date("A",strtotime(time12hrs($no_schedule["end_time"])));
                            
                            $efor_start = $for_start;
                            if($sstarttime == "PM" && $eendtime == "AM") {
                                $efor_start = date("Y-m-d", strtotime($for_start." +1 day"));
                            }
                            
                            $starttime_new = $for_start.' '.time12hrs($no_schedule["start_time"]);
                            $endtime_new =  $efor_start.' '.time12hrs($no_schedule["end_time"]);
                            $total_hours_half_min = ($no_schedule["total_hours"] / 2) * 60;

                            
                            $starttime1 = time12hrs($no_schedule["start_time"]);
                            $starttime = $starttime_new;
                            $endtime1 = time12hrs($no_schedule["end_time"]);
                            $endtime = $endtime_new;
                            $totalhours = $no_schedule["total_hours"];
                            $time_name = "Your shift on this day starts at: {$starttime1} and ends at {$endtime1}";
                            $threshold_mins = $no_schedule["threshold_mins"];
                        }
                    }
                } else {
                    $time_name = $time_name;
                }
            }
        } elseif ($get_open_shift_leave) {
            $time_name = "Your shift on this day is open shift";
            $totalhours_mins = $get_open_shift_leave->total_hrs_per_day * 60;
            $totalhours = $get_open_shift_leave->total_hrs_per_day;
            $starttime  = $for_start.' 08:00:00';
            $endtime    = $for_start.' '.date("H:i:s", strtotime($starttime.' +'.$totalhours_mins.' hours'));
            $full_flex = true;

        } else {
            $work_schedule_id1   = in_array_custom($emp_id."-".$for_start,$data['shift_schedules']);
            if($work_schedule_id1){
                if($date == $work_schedule_id1->valid_from){
                    $weekday = date('l',strtotime($for_start));
                    
                    if($work_schedule_id){
                        $work_schedule_info = $CI->employee_v2->work_schedule_info2($company_id,$work_schedule_id,$weekday,$for_start,$emp_id,0,$data['check_sched_split_flex'],$data['all_sched_block_time_in'],$data['all_sched_flex']);
                    } else {
                        $work_schedule_info = $CI->employee_v2->work_schedule_info2($company_id,$work_schedule_id1->work_schedule_id,$weekday,$for_start,$emp_id,0,$data['check_sched_split_flex'],$data['all_sched_block_time_in'],$data['all_sched_flex']);
                    }
                    
                    if($work_schedule_info){
                        $sstarttime = date("A",strtotime(time12hrs($work_schedule_info["work_schedule"]["start_time"])));
                        $eendtime = date("A",strtotime(time12hrs($work_schedule_info["work_schedule"]["end_time"])));
                        
                        $efor_start = $for_start;
                        if($sstarttime == "PM" && $eendtime == "AM") {
                            $efor_start = date("Y-m-d", strtotime($for_start." +1 day"));
                        }
                        
                        $starttime_new = $for_start.' '.time12hrs($work_schedule_info["work_schedule"]["start_time"]);
                        $endtime_new =  $efor_start.' '.time12hrs($work_schedule_info["work_schedule"]["end_time"]);
                        $total_hours_half_min = ($work_schedule_info["work_schedule"]["total_hours"] / 2) * 60;
                        
                        $starttime1 = time12hrs($work_schedule_info["work_schedule"]["start_time"]);
                        $starttime  = $starttime_new;
                        $endtime1   = time12hrs($work_schedule_info["work_schedule"]["end_time"]);
                        $endtime    = $endtime_new;
                        $totalhours = $work_schedule_info["work_schedule"]["total_hours"];
                        $time_name  = "Your shift on this day starts at: {$starttime1} and ends at {$endtime1}";
                        $threshold_mins = $work_schedule_info["work_schedule"]["threshold_mins"];
                    }
                }
            }
            
            $sure = false;
            $work_schedule_idx = $work_schedule_id;
            
            $work_schedule_idx  = ($work_schedule_custom_id != "") ? $work_schedule_custom_id : $work_schedule_idx;
            $no_schedule        = $CI->employee_v2->get_workschedule_info_for_no_workschedule($company_id,$for_start,$work_schedule_idx,true,$data['all_sched_flex'],$data['all_sched_reg']);
            
            $for_start_m_d = date("m-d", strtotime($for_start));
            $is_holiday         = in_array_custom("date-{$for_start_m_d}",$data['holidays']);
            
            if($is_holiday){
                $holidate = $is_holiday->date;
                if ($is_holiday->date_type == "fixed") {
                    $year = date('Y');
                    $myMonthDay = date('m-d', strtotime($holidate));
                    $new_date = $year . '-' . $myMonthDay;
                    $holidate = date("Y-m-d", strtotime($new_date));
                }

                if($holidate == $for_start){
                    $is_hol = true;
                } else {
                    $is_hol = false;
                }

                if($is_hol) {
                    $proceed1 = false;
                    
                    if($my_location != 0 || $my_location != null) {
                        if($is_holiday->locations != "" || $is_holiday->locations != null) {
                            $x = explode(",", $is_holiday->locations);
                            foreach ($x as $loc) {
                                if($loc == $my_location) {
                                    $proceed1 = true;
                                }
                            }
                        }
                    }
                    
                    if($is_holiday->scope == "local" && !$proceed1) {
                        $is_hol = FALSE;
                    } else {
                        $is_hol = TRUE;
                    }
                    
                }
            }
            
            if($is_hol){
                $holiday_name = $is_holiday->holiday_name.'('.$is_holiday->hour_type_name.')';
            }
            #else {
            if($shift == "" || $no_schedule ){
                if($no_schedule){
                    if($no_schedule["end_time"] == "") {
                        if($no_schedule["start_time"] == "") {
                            $full_flex = true;
                        }
                        
                        $starttime_temp = $for_start.' '.$no_schedule["start_time"];
                        #$starttime      = date("Y-m-d h:i A", strtotime($starttime_temp));
                        $br_hr          = $no_schedule["break"] / 60;
                        $hr_to_min      = ($no_schedule["total_hours"] * 60) - $no_schedule["break"];
                        $totalhours     = $no_schedule["total_hours"] - $br_hr;
                        $endtime      = date("Y-m-d h:i A", strtotime($starttime_temp));
                        $starttime        = date("Y-m-d", strtotime($starttime_temp)).' '.date("h:i A", strtotime($starttime_temp." -{$hr_to_min} minutes"));

                        $get_flexi_end_date = date("Y-m-d", strtotime($for_start));
                        #$rest_n_holiday = true;
                        
                        #while($rest_n_holiday){
                            $rest_day = $CI->ews->get_rest_day($company_id,$work_schedule_id,date("l",strtotime($get_flexi_end_date)));
                            $holiday_date = date('Y-m-d', strtotime($get_flexi_end_date));
                            
                            $holiday_date_m_d = date("m-d", strtotime($holiday_date));
                            $is_holiday_q = in_array_custom("date-{$holiday_date_m_d}",$data['holidays']);
                            $is_holiday = false;
                            
                            // exclude holiday
                            if($is_holiday_q){
                                $holidate = $is_holiday_q->date;
                                if ($is_holiday_q->date_type == "fixed") {
                                    $year = date('Y');
                                    $myMonthDay = date('m-d', strtotime($holidate));
                                    $new_date = $year . '-' . $myMonthDay;
                                    $holidate = date("Y-m-d", strtotime($new_date));
                                }

                                if($holidate == $holiday_date){
                                    $is_hol = true;
                                } else {
                                    $is_hol = false;
                                }

                                // added : dependi if asa ka na location na assign
                                if($is_hol) {
                                    $proceed1 = false;
                                    
                                    if($my_location != 0 || $my_location != null) {
                                        if($is_holiday_q->locations != "" || $is_holiday_q->locations != null) {
                                            $x = explode(",", $is_holiday_q->locations);
                                            foreach ($x as $loc) {
                                                if($loc == $my_location) {
                                                    $proceed1 = true;
                                                }
                                            }
                                        }
                                    }
                                    
                                    if($is_holiday_q->scope == "local" && !$proceed1) {
                                        $is_hol = FALSE;
                                    } else {
                                        $is_hol = TRUE;
                                    }
                                    
                                }


                            } else {
                                $is_hol = false;
                            }
                            
                            if($rest_day || $is_hol){
                                $get_flexi_end_date = date('Y-m-d', strtotime($get_flexi_end_date.' +1 day'));
                            } 
                            #else {
                            #    $rest_n_holiday = false;
                            #}
                        #}
                        
                        $valid_flexi_date = $get_flexi_end_date;
                        
                        
                        #$time_name = "Your shift on this day requires {$no_schedule["total_hours"]} hours of work";
                        $time_name = "Your shift on this day requires {$totalhours} hours of work";
                        
                        if($full_flex) {
                            $time_name = "Your shift on this day is flex";
                        }
                    } else {
                        $sstarttime = date("A",strtotime(time12hrs($no_schedule["start_time"])));
                        $eendtime = date("A",strtotime(time12hrs($no_schedule["end_time"])));
                        
                        $efor_start = $for_start;
                        if($sstarttime == "PM" && $eendtime == "AM") {
                            $efor_start = date("Y-m-d", strtotime($for_start." +1 day"));
                        }
                        
                        $starttime_new = $for_start.' '.time12hrs($no_schedule["start_time"]);
                        $endtime_new =  $efor_start.' '.time12hrs($no_schedule["end_time"]);
                        $total_hours_half_min = ($no_schedule["total_hours"] / 2) * 60;
                        
                        $starttime1 = time12hrs($no_schedule["start_time"]);
                        $starttime  = $starttime_new;
                        $endtime1   = time12hrs($no_schedule["end_time"]);
                        $endtime    = $endtime_new;
                        $totalhours = $no_schedule["total_hours"];
                        $time_name  = "Your shift on this day starts at: {$starttime1} and ends at {$endtime1}";
                        $threshold_mins = $no_schedule["threshold_mins"];
                    }
                }
            } else {
                $time_name = $time_name;
            }
            #}
        }
        
        if ($rest_day) {
            $eendtime = date("Y-m-d", strtotime($for_start)).' '.$eendtime;
            $time_name = "Rest Day";
        }
        
        
        $NS_start_time = ($starttime) ? date("m/d/Y h:i A", strtotime($starttime)) : date("m/d/Y h:i A", strtotime($for_start));
        $NS_end_time = date("m/d/Y h:i A", strtotime($endtime));
        
        $if_NS_ranging = false;
        if ((date("A", strtotime($NS_end_time)) == "AM" && date("A", strtotime($NS_start_time)) == "PM")) {
            /*$if_NS_ranging = true;
            if ($if_partial == "yes") {
                $if_NS = true;
            }*/
        }

        /*if($is_hol) {
            if($is_holiday) {
                if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "yes"){
                    if ($if_partial == "yes") {
                        $disabled_btn = false;
                    } else {
                        $disabled_btn = true;
                        $locked = "You can not apply for a leave on a Holiday.";
                    }
                } else {
                    if($is_holiday->hour_type_name == "Special Holiday") {
                        // exclude regular holiday only
                        if($exclude_holidays == 'yes' && $exclude_regular_holidays == "no" && $exclude_special_holidays == "yes"){
                            if ($if_partial == "yes") {
                                $disabled_btn = false;
                            } else {
                                $disabled_btn = true;
                                $locked = "You can not apply for a leave on a Special Holiday.";
                            }
                        }
                    }
                    
                    if($is_holiday->hour_type_name == "Regular Holiday") {
                        // exclude regular holiday only
                        if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "no"){
                            if ($if_partial == "yes") {
                                $disabled_btn = false;
                            } else {
                                $disabled_btn = true;
                                $locked = "You can not apply for a leave on a Regular Holiday.";
                            }
                        }
                    }
                }
                
            }
        }*/
        
        if ($time_name == "Rest Day") {
            $time_name = "Your shift on this day is Rest Day";
        }

        $ret = array(
            "work_sched_type" => ($check_work_type) ? $check_work_type : "",
            "start_time" => ($starttime) ? date("m/d/Y h:i A", strtotime($starttime)) : date("m/d/Y h:i A", strtotime($for_start)),
            "end_time" => date("m/d/Y h:i A", strtotime($endtime)),
            "total_hrs" => $totalhours,
            "your_shift" => ($holiday_name != "") ? $holiday_name : $time_name,
        );
                        
        return $ret;
    } else {
        return false;
    }
}

function get_todo_action_history($company_id, $application_id, $application_type) {
    $CI =& get_instance();

    if($application_type) {
        if($application_type == "timesheet") {
            $w = array(
                "company_id" => $company_id,
                "employee_time_in_id" => $application_id
            );
        } elseif($application_type == "leave") {
            $w = array(
                "company_id" => $company_id,
                "employee_leaves_application_id" => $application_id
            );
        } elseif($application_type == "overtime") {
            $w = array(
                "company_id" => $company_id,
                "employee_overtime_application_id" => $application_id
            );
        } elseif($application_type == "shift") {
            $w = array(
                "company_id" => $company_id,
                "employee_work_schedule_application_id" => $application_id
            );
        }

        $CI->db->where($w);

        $query = $CI->edb->get('todo_action_history');
        $result = $query->result();

        return ($result) ? $result : false;
    } else {
        return false;
    }
   
}

function get_current_period_of_employee_old($emp_id, $comp_id, $date)
{
    $CI =& get_instance();

    $today = date('Y-m-d');
    $select = array(
        'employee_payroll_information.employee_payroll_information_id',
        'employee_payroll_information.emp_id',
        'employee_payroll_information.payroll_group_id',
        'payroll_group.name',
        'payroll_group.period_type',
        'payroll_group.pay_rate_type',
        'payroll_calendar.first_payroll_date',
        'payroll_calendar.cut_off_from',
        'payroll_calendar.cut_off_to'
    );
    $where = array(
        'emp_id'=> $emp_id,
        'employee_payroll_information.company_id' => $comp_id,
        'payroll_calendar.cut_off_from <=' => $date,
        'payroll_calendar.cut_off_to >=' => $date, 
    );
    $CI->db->select($select);
    $CI->db->where($where);
    $CI->db->join('payroll_group', 'payroll_group.payroll_group_id = employee_payroll_information.payroll_group_id', 'LEFT');
    $CI->db->join('payroll_calendar', 'payroll_calendar.pay_schedule = payroll_group.period_type AND payroll_calendar.company_id = employee_payroll_information.company_id', 'INNER');
    $q = $CI->db->get('employee_payroll_information');
    $r = $q->row();
    return ($r) ? $r : false;
}

function get_current_period_of_employee($emp_id,$comp_id,$date){
    $CI =& get_instance();

    $w = array(
        'prc.emp_id'            => $emp_id,
        'prc.company_id'        => $comp_id,
        'prc.status'            => 'Active',
        'dpr.view_status'       => 'Open',
        "prc.period_from <="    => $date,
        "prc.period_to >="      => $date
        #prc.payroll_period >=" => $date
    );

    $s = array(
        "prc.payroll_period AS first_payroll_date",
        "prc.period_from AS cut_off_from",
        "prc.period_to AS cut_off_to",
        //"dpr.open_shift_total_hours_cutoff"
    );
    $CI->db->select($s);
    $CI->db->where($w);
    $CI->db->join("draft_pay_runs as dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id");
    $CI->db->order_by('prc.payroll_period','DESC');
    $q = $CI->db->get('payroll_run_custom as prc');
    $r = $q->row();

    if($r){
        return $r;
    }else{
        $w1 = array(
            'epi.emp_id'            => $emp_id,
            'dpr.company_id'        => $comp_id,
            'dpr.status'            => 'Active',
            'dpr.view_status'       => 'Open',
            "epi.period_from <="    => $date,
            "epi.period_to >="      => $date
           # "epi.payroll_date >="   => $date
        );

        $s = array(
            "epi.payroll_date AS first_payroll_date",
            "epi.period_from AS cut_off_from",
            "epi.period_to AS cut_off_to",
           // "dpr.open_shift_total_hours_cutoff"
        );
        $CI->db->select($s);

        $CI->db->where($w1);
        $CI->db->join("draft_pay_runs as dpr","dpr.payroll_group_id = epi.payroll_group_id AND dpr.pay_period = epi.payroll_date");
        $CI->db->order_by('epi.payroll_date','DESC');

        $q1 = $CI->db->get('payroll_payslip as epi');
        $r1 = $q1->row();

        if($r1){
            return $r1;
        } else {
            return false;
        }
    }
}

/** NEW workflow - start **/
function get_workflow_approvers($process_type,$company_id,$emp_id,$leave_type_id=""){
    $CI =& get_instance();
    
    $select = array(
        "wea.emp_id",
        "wea.company_id",
        "wb.leave_type_id",
        "wb.process_type",
        "wb.parent_workflow_breakdown_id",
        "wb.assigned_account_id",
        "wb.level",
        "wb.notify_staff",
        "wb.notify_payroll_admin",
        "wb.status",
        "wb.parent",
        "wb.workflow_grp_name",
    );
    $where = array(
        "wea.company_id" => $company_id,
        "wea.emp_id" => $emp_id,
        "wb.parent" => "no",
        "wb.status" => "Active",
        "wb.process_type" => $process_type,
    );
    
    if($leave_type_id != ""){
        $where["wb.leave_type_id"] = $leave_type_id;
    }
    
    $CI->db->select($select);
    $CI->db->where($where);
    $CI->db->join("workflow_emp_assign AS wea", "wea.workflow_breakdown_id = wb.parent_workflow_breakdown_id", "LEFT");
    $query = $CI->db->get("workflow_breakdown AS wb");
    $result = $query->result();
    
    return ($result) ?  $result : false;

    /*$w_emp = array(
        "emp_id" => $emp_id,
        "process_type" => $process_type,
        "company_id" => $company_id,
        "status" => "Active",
    );

    $CI->db->where($w_emp);
    $sql_emp = $CI->db->get("workflow_emp_assign");
    $r_emp = $sql_emp->row();
    p($r_emp);
    if($r_emp) {
        if($leave_type_id != "") {
            $w = array(
                "company_id" => $company_id,
                "leave_type_id" => $leave_type_id,
                "status" => "Active",
                "process_type" => $process_type,
                "parent" => "no",
                "parent_workflow_breakdown_id" => $r_emp->workflow_breakdown_id
            );
        } else {
            $w = array(
                "company_id" => $company_id,
                "status" => "Active",
                "process_type" => $process_type,
                "parent" => "no",
                "parent_workflow_breakdown_id" => $r_emp->workflow_breakdown_id
            );
        }

        $CI->db->where($w);
        $sql = $CI->db->get("workflow_breakdown");
        $r = $sql->result();
        last_query();
        return ($r) ? $r : FALSE ;
    } else {
        // default approver here
        return false;
    }


    */
}

function get_workflow_approvers_parent($workflow_breakdown_id){
    $CI =& get_instance();
    $w = array(
        #"parent_workflow_breakdown_id" => $parent_workflow_breakdown_id,
        "workflow_breakdown_id" => $workflow_breakdown_id,
        "status" => "Active",
        "parent" => "yes"
    );

    $CI->db->where($w);
    $sql = $CI->db->get("workflow_breakdown");
    $r = $sql->row();
    
    return ($r) ? $r : FALSE ;
}

function employee_info_by_accnt_id($account_id,$company_id){
    if(is_numeric($account_id)){
        $CI =& get_instance();
        $where = array(
            'a.account_id' => $account_id,
            'e.company_id'=>$company_id,
            'a.deleted'=>'0',
            'e.status'=>'Active',
            'a.user_type_id'=>'5'
        );
        $CI->edb->where($where);
        $CI->edb->join('employee AS e','e.account_id=a.account_id','INNER');
        $q = $CI->edb->get('accounts AS a');
        $r = $q->row();
        
        if($r) {
            $q->free_result();
            return $r;
        } else {
            $where1 = array(
                'a.account_id' => $account_id,
                'a.deleted'=>'0',
                'a.user_type_id'=>'2'
            );

            $CI->edb->where($where1);
            $CI->edb->join('company_owner AS e','e.account_id=a.account_id','INNER');
            $q = $CI->edb->get('accounts AS a');

            $r = $q->row();
            $q->free_result();
            return $r;
        }
    }else{
        return false;
    }
}

function current_approver_grp_level($workflow_breakdown_id,$approver_account_id,$company_id){
    $_CI =& get_instance();
    $where = array(
        "company_id" => $company_id,
        "status" => "Active",
        "assigned_account_id" => $approver_account_id,
        "parent" => "no",
        "parent_workflow_breakdown_id" => $workflow_breakdown_id
    );
    $_CI->db->where($where);
    $query = $_CI->db->get("workflow_breakdown");
    $row = $query->row();

    return ($row) ? $row->level : 0;
}
/** NEW workflow - end **/

function get_lock_payroll_process_settings_v1($company_id,$type="application") {
    $CI =& get_instance();
    
    if($type == "add log") {
        $type = "new attendance log";
    } elseif ($type == "add logs") {
        $type = "attendance adjustment";
    } elseif ($type == "leave") {
        $type = "leave";
    } elseif ($type == "overtime") {
        $type = "overtime";
    } elseif ($type == "shift request") {
        $type = "change schedule";
    }
    
    $CI->db->where('company_id',$company_id);
    $query = $CI->db->get('lock_payroll_process_settings');
    $result = $query->row();
    
    if($result) {
        $res = array(
            "suspend_all_application" => $result->suspend_all_application,
            "ts_recalc" => $result->ts_recalc,
            "ts_recalc_time" => $result->ts_recalc_time,
            "py_recalc" => $result->py_recalc,
            "py_recalc_time" => $result->py_recalc_time,
            
            "application_error" => "Filing of approval of {$type} is temporarily suspended by your admin.",
            "ts_app_recalculation_err_msg" => "This application can't be completed because another process is accessing the same data set. Please try again later. ",
            "py_app_recalculation_err_msg" => "This application can't be completed because another process is accessing the same data set. Please try again later. ",
            
            "manager_application_error" => "{$type} is temporarily suspended by your admin.",
            "app_delete_error_msg" => "NOTE : Deletion is temporarily suspended by your admin",
            "ts_app_delete_error_msg" => "This action can't be completed because this data set is temporarily locked.",
            "py_app_delete_error_msg" => "This action can't be completed because this data set is temporarily locked.",
            
            "approval_error" => "NOTE : Approval is temporarily suspended by your admin.",
            "ts_approval_recalculation_err_msg" => "NOTE : This data cannot be approved/rejected because this data set is temporarily being locked.",
            "py_approval_recalculation_err_msg" => "NOTE : This data cannot be approved/rejected because this data set is temporarily being locked."
        );
        
        return (object) $res ;
    } else {
        return false;
    }
}