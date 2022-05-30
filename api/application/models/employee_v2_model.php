<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Employee V2 Model
 *
 * @category Model
 * @version 2.0
 * @author John Fritz Marquez <fritzified.gamer@gmail.com>
 */

class Employee_v2_model extends CI_Model {
    public function time_in_list_counter($comp_id, $emp_id,$from="",$to=""){
        
        $sel = array(
            'employee_time_in_id'
        );
        
        if($from!="" && $from!="none" && $to!="" && $to!="none"){
            $this->db->where('date BETWEEN "'.$from.'" AND "'.$to.'"',NULL,FALSE);
        }
        
        $where = array(
            'comp_id' => $comp_id,
            'emp_id' => $emp_id,
            'status' => 'Active',
            'flag_open_shift' => '0'
        );
        
        $this->db->select($sel);
        $this->db->where($where);
        $q = $this->db->get('employee_time_in', 100);
        $r = $q->result();
        
        return ($r) ? count($r) : false;
    }
    
    public function time_in_list($limit, $start, $comp_id, $emp_id, $sort="DESC", $sort_by,$date_from="",$date_to=""){
        $sort_array = array(
            "date",
            "time_in_status"
        );
        
        $sel = array(
            "time_in_status",
            "source",
            "last_source",
            "time_in",
            "time_out",
            "lunch_out",
            "lunch_in",
            "break1_out",
            "break1_in",
            "break2_out",
            "break2_in",
            "change_log_date_filed",
            "change_log_time_in",
            "change_log_time_out",
            "change_log_lunch_out",
            "change_log_lunch_in",
            "change_log_break1_out",
            "change_log_break1_in",
            "change_log_break2_out",
            "change_log_break2_in",
            "emp_id",
            "date",
            "undertime_min",
            "tardiness_min",
            "work_schedule_id",
            "employee_time_in_id",
            "comp_id",
            "late_min",
            "overbreak_min",
            "change_log_tardiness_min",
            "change_log_undertime_min",
            "total_hours_required",
            "total_hours",
            "absent_min",
            "change_log_total_hours",
            "change_log_total_hours_required",
            "notes",
            "reason",
            "split_status"
        );
        
        $w = array(
            'et.emp_id'=>$emp_id,
            'et.comp_id'=> $comp_id,
            'et.status'=> 'Active'
        );
        
        $this->db->select($sel);
        $this->db->where($w);
        
        if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
            $this->db->where('date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
        }
        
        
        if($sort_by != ""){
            if(in_array($sort_by, $sort_array)){
                $this->db->order_by($sort_by,$sort);
            }
        }else{
            $this->db->order_by('et.date','DESC');
        }
        
        if($start==0){
            $sql = $this->db->get('employee_time_in AS et',$limit);
        }else{
            $sql = $this->db->get('employee_time_in AS et',$start,$limit);
        }
        
        if($sql->num_rows() > 0){
            $results = $sql->result();
            $sql->free_result();
            
            return $results;
        }else{
            return FALSE;
        }
    }
    
    public function check_employee_leave_applicationv2($comp_id,$parent_emp_id,$emp_ids="",$min_range="",$max_rang=""){
        $row_array  = array();
        $leaves     = $this->employee_leaves_app($comp_id,$parent_emp_id,$emp_ids,$min_range,$max_rang);
        if($leaves){
            $s = array(
                "eti.emp_id",
                "eti.date",
                "eti.undertime_min",
                "eti.tardiness_min",
                "eti.absent_min",
            );
            
            $w = array(
                "eti.comp_id"           => $comp_id,
                "eti.status"            => "Active"
            );
            
            $this->db->where($w);
            
            if($emp_ids){
                $this->db->where_in("eti.emp_id",$emp_ids);
            }
            
            $this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
            $q = $this->db->get("employee_time_in AS eti");
            $r1 = $q->result();
            
            if($r1){
                $credited = 0;
                foreach ($r1 AS $r){
                    $date   = $r->date;
                    $emp_id = $r->emp_id;
                    
                    $data = array(
                        "tardiness"     => "",
                        "undertime"     => "",
                        "absent_min"    => "",
                        "credited"      => ""
                    );
                    
                    if($r->time_in != NULL && $r->time_out != NULL){
                        $r2 = in_array_custom("emps-{$r->emp_id}-{$r->date}",$leaves);
                        
                        if($r2){
                            $credited = 0;
                            $credited = $r2->credited;
                            
                            if($r->absent_min > 0){
                                $data = array(
                                    "tardiness"     => "",
                                    "undertime"     => "",
                                    "absent_min"    => "1",
                                    "credited"      => $credited
                                );
                            } else {
                                if($r->tardiness_min > 0 || $r->undertime_min > 0 ){
                                    if($r->tardiness_min > $r->undertime_min){
                                        $data = array(
                                            "tardiness"     => "1",
                                            "undertime"     => "",
                                            "absent_min"    => "",
                                            "credited"      => $credited
                                        );
                                    }else{
                                        $data = array(
                                            "tardiness"     => "",
                                            "undertime"     => "1",
                                            "absent_min"    => "",
                                            "credited"      => $credited
                                        );
                                    }
                                }
                            }
                        }
                    }
                    
                    $wd = array(
                        "date"          => $date,
                        "emp_id"        => $emp_id,
                        "tardiness"     => $data['tardiness'],
                        "undertime"     => $data['undertime'],
                        "absent_min"    => $data['absent_min'],
                        "credited"      => $data['credited'],
                        "custom_search" => "{$date}-{$emp_id}",
                    );
                    array_push($row_array,$wd);
                }
            }
        }
        
        return $row_array;
    }
    
    public function employee_leaves_app($comp_id,$emp_ids="",$min="",$max=""){
        $row_array  = array();
        $s  = array(
            "ela.credited",
            "ela.shift_date",
            "DATE(ela.date_start) AS date_start",
            "DATE(ela.date_end) AS date_end",
            "ela.emp_id"
        );
        $w2 = array(
            "ela.company_id"                => $comp_id,
            "ela.status"                    => "Active",
            "ela.leave_application_status"  => "approve"
        );
        
        $this->db->select($s);
        if($emp_ids){
            $this->db->where_in("ela.emp_id",$emp_ids);
        }
        $this->db->where($w2);
        if($min && $max){
            $this->db->where("CAST(DATE(ela.date_start) AS date) >= '".$min."' AND CAST(DATE(ela.date_end) AS date) <= '".$max."'");
        }
        
        $q2 = $this->db->get("employee_leaves_application AS ela");
        
        $r2 = $q2->result();
        
        if($r2){
            foreach ($r2 AS $r){
                $wd = array(
                    "emp_id"        => $r->emp_id,
                    "date_start"    => $r->date_start,
                    "date_end"      => $r->date_end,
                    "credited"      => $r->credited,
                    "custom_search" => "emps-{$r->emp_id}-{$r->shift_date}",
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }
    
    public function assigned_work_schedule($company_id,$min="",$max="",$emp_ids=""){
        $row_array  = array();
        $sel = array(
            "ess.emp_id",
            "ess.shifts_schedule_id",
            "ess.work_schedule_id",
            "ws.bg_color",
            "ws.flag_custom",
            "ws.work_type_name",
            "ws.name",
            "ws.category_id",
            "ess.valid_from",
            "ess.until"
        );
        
        if($min != "" && $max != ""){
            $w1 = array(
                "ess.valid_from >="     => $min,
                "ess.valid_from <="     => $max,
            );
            $this->db->where($w1);
        } else {
            $w1 = array(
                "ess.valid_from >="     => date("Y-m-d"),
                "ess.valid_from <="     => date("Y-m-d"),
            );
            $this->db->where($w1);
        }
        
        $w = array(
            "ess.company_id"        => $company_id,
            "ess.status"            => "Active",
            "ess.payroll_group_id"  => "0"
        );
        
        $this->db->select($sel);
        $this->db->where($w);
        
        if($emp_ids){
            $this->db->where_in("ess.emp_id",$emp_ids);
        }
        
        $this->db->join("work_schedule AS ws","ws.work_schedule_id = ess.work_schedule_id","LEFT");
        $q = $this->db->get("employee_shifts_schedule AS ess");
        $r = $q->result();
        
        if($r){
            foreach ($r AS $r1){
                $wd = array(
                    "emp_id"                => $r1->emp_id,
                    "shifts_schedule_id"    => $r1->shifts_schedule_id,
                    "work_schedule_id"      => $r1->work_schedule_id,
                    "bg_color"              => $r1->bg_color,
                    "flag_custom"           => $r1->flag_custom,
                    "work_type_name"        => $r1->work_type_name,
                    "name"                  => $r1->name,
                    "category_id"           => $r1->category_id,
                    "valid_from"            => $r1->valid_from,
                    "until"                 => $r1->until,
                    "custom_search"         => "{$r1->emp_id}-{$r1->valid_from}",
                );
                
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }
    
    public function all_work_schedule($company_id){
        $row_array  = array();
        $s          = array(
            'name',
            'work_schedule_id',
            'work_type_name',
            'break_rules',
            'assumed_breaks',
            'category_id',
            'total_hrs_per_pay_period',
            'total_hrs_per_day'
        );
        $this->db->select($s);
        
        $w          = array(
            'comp_id'=> $company_id,
            'status' => "Active"
        );
        $this->db->where($w);
        $q_pg = $this->db->get('work_schedule');
        $r_pg = $q_pg->result();
        
        if($r_pg){
            foreach ($r_pg as $r1){
                $wd     = array(
                    "name"              => $r1->name,
                    "work_type_name"    => $r1->work_type_name,
                    "break_rules"       => $r1->break_rules,
                    "assumed_breaks"    => $r1->assumed_breaks,
                    'total_hrs_per_pay_period'  => $r1->total_hrs_per_pay_period,
                    'total_hrs_per_day' => $r1->total_hrs_per_day,
                    "custom_search"     => "wsid-{$r1->work_schedule_id}",
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }
    
    public function work_schedule_info2($company_id,$work_schedule_id,$weekday,$currentdate = NULL,$emp_id =null,$employee_time_in_id = 0,$check_sched_split_flex=array(),$all_sched_block_time_in=array(),$all_sched_flex=array()){
        //return array();
        $wd = date("l",strtotime($weekday));
        $break_time = 0;
        $start_time = "";
        $end_time   = "";
        $shift_name = "";
        $total_hours = 0;
        $uww = array(
            "uw.days_of_work"=>$wd,
            "uw.company_id"=>$company_id,
            "uw.work_schedule_id"=>$work_schedule_id,
            "uw.status"=>"Active"
        );
        
        $this->db->where($uww);
        $arr2 = array(
            'work_start_time'           => 'uw.work_start_time',
            'work_end_time'             => 'uw.work_end_time',
            'work_schedule_name'        => 'uw.work_schedule_name',
            'total_work_hours'          => 'uw.total_work_hours',
            'break_in_min'              => 'uw.break_in_min',
            'latest_time_in_allowed'    => 'uw.latest_time_in_allowed'
        );
        $this->edb->select($arr2);
        $this->edb->join("work_schedule AS ws","ws.work_schedule_id = uw.work_schedule_id","LEFT");
        $uwq    = $this->edb->get("regular_schedule AS uw");
        $uwr    = $uwq->row();
        $data   = array();
        $latest_time_in_allowed = 0;
        if($uwr){
            if ($uwr->latest_time_in_allowed != 0 || $uwr->latest_time_in_allowed != null) {
                $latest_time_in_allowed = $uwr->latest_time_in_allowed;
            }
            
            $start_time     = $uwr->work_start_time;
            $end_time       = $uwr->work_end_time;
            $shift_name     = $uwr->work_schedule_name;
            $total_hours    = $uwr->total_work_hours;
            $break_time     = $uwr->break_in_min;
        }else{
            $sched_split = in_array_foreach_custom("wsidate-{$work_schedule_id}-{$currentdate}",$check_sched_split_flex);
            if($sched_split){
                foreach ($sched_split as $split){
                    $split_data = in_array_custom("etid-{$employee_time_in_id}-{$split->schedule_blocks_id}",$all_sched_block_time_in);
                    
                    if($split_data){
                        $data[] = array(
                            "start_time"    => $split_data->start_time,
                            "end_time"      => $split_data->end_time,
                            "shift_name"    => $split_data->shift_name,
                            "break"         => $split_data->break,
                            "total"         => $split_data->total
                        );
                    }
                }
                return $data;
            }
            else{
                $flex_data  = in_array_custom("wsi-".$work_schedule_id,$all_sched_flex);
                if($flex_data){
                    $start_time = $flex_data->start_time;
                    $end_time   = $flex_data->end_time;
                    $shift_name = $flex_data->shift_name;
                }
            }
        }
        
        $data["work_schedule"] = array(
            "start_time"        => $start_time,
            "end_time"          => $end_time,
            "shift_name"        => $shift_name,
            "break_time"        => $break_time,
            "total_hours"       => $total_hours,
            "threshold_mins"    => $latest_time_in_allowed
        );
        return $data;
    }
    
    public function check_this_sched($company_id,$min="",$max="",$emp_ids=""){
        $row_array = array();
        $arrx = array(
            'em.work_schedule_id',
            'em.schedule_blocks_id',
            'em.emp_id',
            'es.valid_from',
        );
        
        $this->db->select($arrx);
        
        if($min != "" && $max != ""){
            $w1 = array(
                "es.valid_from >="      => $min,
                "es.until <="           => $max,
            );
            $this->db->where($w1);
        } else {
            $w1 = array(
                "es.valid_from >="      => date("Y-m-d"),
                "es.until <="           => date("Y-m-d"),
            );
            $this->db->where($w1);
        }
        
        if($emp_ids){
            $this->db->where_in("em.emp_id",$emp_ids);
        }
        
        $w_ws = array(
            "em.company_id"         => $company_id
        );
        
        $this->db->where($w_ws);
        $this->db->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
        $q_ws = $this->db->get("employee_sched_block AS em");
        $r_ws = $q_ws->result();
        
        if($r_ws){
            foreach ($r_ws as $r1){
                $wd     = array(
                    "schedule_blocks_id"    => $r1->schedule_blocks_id,
                    "custom_search"         => "wsidate-{$r1->work_schedule_id}-{$r1->valid_from}",
                );
                
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }
    
    public function all_sched_block_time_in($company_id,$min="",$max="",$emp_ids=""){
        $row_array = array();
        $s = array(
            "sbti.employee_time_in_id",
            "sbti.emp_id",
            "sbti.date",
            "sb.start_time",
            "sb.end_time",
            "sb.block_name",
            "sb.break_in_min",
            "sb.schedule_blocks_id",
            "sb.total_hours_work_per_block",
        );
        
        $this->db->select($s);
        
        $w = array(
            "sbti.comp_id"          => $company_id,
            "sbti.status"           => "Active"
        );
        
        $this->db->where($w);
        
        if($min != "" && $max != ""){
            $w1 = array(
                "sbti.date >="      => $min,
                "sbti.date <="      => $max,
            );
            $this->db->where($w1);
        } else {
            $w1 = array(
                "sbti.date >="      => date("Y-m-d"),
                "sbti.date <="      => date("Y-m-d"),
            );
            $this->db->where($w1);
        }
        
        if($emp_ids){
            $this->db->where_in("sbti.emp_id",$emp_ids);
        }
        
        $this->db->order_by("sbti.time_in","ASC");
        $this->db->join("schedule_blocks AS sb","sb.schedule_blocks_id = sbti.schedule_blocks_id");
        $split_q = $this->db->get("schedule_blocks_time_in AS sbti");
        $query_split = $split_q->result();
        
        if($query_split){
            foreach ($query_split as $r1){
                $wd     = array(
                    "start_time"           => $r1->start_time,
                    "end_time"          => $r1->end_time,
                    "shift_name"        => $r1->block_name,
                    "break"             => $r1->break_in_min,
                    "total"             => $r1->total_hours_work_per_block,
                    "schedule_blocks"   => $r1->schedule_blocks_id,
                    "custom_search"     => "etid-{$r1->employee_time_in_id}-{$r1->schedule_blocks_id}",
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }
    
    public function all_sched_flex_in($company_id){
        $row_array = array();
        $s = array(
            "f.work_schedule_id",
            "f.latest_time_in_allowed",
            "ws.name",
            "f.total_hours_for_the_day",
            "f.duration_of_lunch_break_per_day",
        );
        
        $this->db->select($s);
        $w = array(
            "f.company_id" => $company_id
        );
        
        $this->db->where($w);
        $this->db->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
        $fq = $this->db->get("flexible_hours AS f");
        $fr = $fq->result();
        
        if($fr){
            foreach ($fr as $r1){
                if($r1->latest_time_in_allowed != NULL || $r1->latest_time_in_allowed != ""){
                    $start_time = $r1->latest_time_in_allowed;
                    $end_time   = "";
                    $shift_name = $r1->name;
                }else{
                    $start_time = "";
                    $end_time   = "";
                    $shift_name = $r1->name;
                }
                $wd = array(
                    "start_time"                => $start_time,
                    "end_time"                  => $end_time,
                    "total_hours"               => $r1->total_hours_for_the_day,
                    "latest_time_in_allowed"    => $r1->latest_time_in_allowed,
                    "shift_name"                => $shift_name,
                    "break"                     => $r1->duration_of_lunch_break_per_day,
                    "custom_search"             => "wsi-{$r1->work_schedule_id}",
                );
                array_push($row_array,$wd);
            }
        }
        
        return $row_array;
    }
    
    public function get_workschedule_info_for_no_workschedule($company_id,$date,$work_schedule_id = "",$activate = false,$all_sched_flex=array(),$all_sched_reg=array()){
        $data = "";
        $day = date('l',strtotime($date));
        $reg_data   = in_array_custom("rwis-{$work_schedule_id}-{$day}",$all_sched_reg);
        if($reg_data){
            if($activate){
                $arr = array(
                    'start_time'        => $reg_data->work_start_time,
                    'end_time'          => $reg_data->work_end_time,
                    'break'             => $reg_data->break_in_min,
                    'total_hours'       => $reg_data->total_work_hours,
                    'name'              => $reg_data->work_schedule_name,
                    'type'              => 1,
                    'threshold_mins'    => $reg_data->latest_time_in_allowed,
                );
                return $arr;
            }
            else{
                $data  = time12hrs($reg_data->work_start_time)."-".time12hrs($reg_data->work_end_time)."<br>";
                $data .= "break: ".$reg_data->break_in_min." mins";
                $data .= "<br> Total Hours: ".$reg_data->total_work_hours;
            }
        }
        else{
            $flex_data  = in_array_custom("wsi-".$work_schedule_id,$all_sched_flex);
            if($flex_data){
                $total_h = $flex_data->total_hours - ($flex_data->break / 60);
                $total_h = number_format($total_h,2);
                if($activate){
                    $arr = array(
                        'start_time'    => $flex_data->latest_time_in_allowed,
                        'end_time'      => "",
                        'break'         => $flex_data->break,
                        'total_hours'   => $flex_data->total_hours,
                        'name'          => '',
                        'type'          => 2,
                        'threshold_mins'    => 0,
                    );
                    
                    return $arr;
                }else{
                    if($flex_data->latest_time_in_allowed != NULL || $flex_data->latest_time_in_allowed != ""){
                        $data  = "Latest Timein: ".time12hrs($flex_data->latest_time_in_allowed) . " <br> ";
                        $data .= "break: ".$flex_data->break. " mins";
                        $data .= "<br> Total hours: ". $total_h;
                    }else{
                        $data = "break: ".$flex_data->break." mins";
                        $data .= "<br> Total hours: ". $total_h;
                    }
                }
            }
        }
        return $data;
    }
    
    public function get_comp_reg_sched($company_id){
        $row_array = array();
        $w_uwd = array(
            "company_id"    => $company_id,
            "status"        => 'Active'
        );
        
        $this->db->where($w_uwd);
        
        $arr4 = array(
            'work_schedule_name',
            'work_end_time',
            'work_start_time',
            'break_in_min',
            'total_work_hours',
            'days_of_work',
            'work_schedule_id',
            'break_in_min',
            'latest_time_in_allowed'
        );
        
        $this->db->select($arr4);
        $q_uwd = $this->db->get("regular_schedule");
        $r_uwd = $q_uwd->result();
        
        if($r_uwd){
            foreach ($r_uwd as $r1){
                $wd     = array(
                    "latest_time_in_allowed" => $r1->latest_time_in_allowed,
                    "work_schedule_name" => $r1->work_schedule_id,
                    "work_end_time"     => $r1->work_end_time,
                    "work_start_time"   => $r1->work_start_time,
                    "break_in_min"      => $r1->break_in_min,
                    "total_work_hours"  => $r1->total_work_hours,
                    "days_of_work"      => $r1->days_of_work,
                    "break_in_min"      => $r1->break_in_min,
                    "custom_search"     => "rwis-{$r1->work_schedule_id}-{$r1->days_of_work}",
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }
    
    public function check_is_date_holidayv2($company_id,$min="",$max=""){
        $row_array = array();
        
        $s = array(
            'h.repeat_type',
            'ht.hour_type_name',
            'h.holiday_name',
            'h.date',
            'h.date_type',
            'h.scope',
            'h.locations'
        );
        
        $this->db->select($s);
        
        if($min == "" && $max == "") {
            $static_date = "2013-01-01";
            $year_min = date("Y",strtotime($static_date));
            $year_max = date("Y",strtotime($static_date));
            
            $y_gap = ($year_max - $year_min) + 1;
            $w = array(
                "h.company_id" => $company_id,
                "h.status" => "Active"
            );
        } else {
            $year_min = date("Y",strtotime($min));
            $year_max = date("Y",strtotime($max));
            
            $y_gap = ($year_max - $year_min) + 1;
            $w = array(
                "h.company_id" => $company_id,
                "h.date >=" => $min,
                "h.date <=" => $max,
                "h.status" => "Active"
            );
        }
        
        
        $this->db->where($w);
        $this->db->join("hours_type AS ht","ht.hour_type_id = h.hour_type_id");
        $q = $this->db->get("holiday AS h");
        $r = $q->result();
        
        if($r){
            foreach ($r as $r1){
                $date       = date("m-d", strtotime($r1->date));
                
                #if($r1->repeat_type == "yes" && $r1->date_type != "movable"){
                /*if($r1->date_type == "fixed"){
                    for($x = 0;$x < $y_gap;$x++){
                        
                        $month      = date("m",strtotime($date));
                        $day        = date("d",strtotime($date));
                        $year       = $year_min + $x;
                        #p($year);
                        $hol_date   = $year."-".$month."-".$day;
                        
                        $wd     = array(
                            "date_type"     => $r1->date_type,
                            "repeat_type"       => $r1->repeat_type,
                            "hour_type_name"    => $r1->hour_type_name,
                            "holiday_name"      => $r1->holiday_name,
                            "date"              => $hol_date,
                            "custom_search"     => "date-{$hol_date}",
                        );
                        array_push($row_array,$wd);
                    }
                }else{*/
                    $wd     = array(
                        "date_type"         => $r1->date_type,
                        "repeat_type"       => $r1->repeat_type,
                        "hour_type_name"    => $r1->hour_type_name,
                        "holiday_name"      => $r1->holiday_name,
                        #"date"             => $r1->date,
                        "date"              => $r1->date,
                        'scope'             => $r1->scope,
                        'locations'         => $r1->locations,
                        "custom_search"     => "date-{$date}",
                    );
                    array_push($row_array,$wd);
               # }
            }
        }
        return $row_array;
    }
    
    public function get_payroll_group_ids($company_id,$emp_ids=""){
        $row_array = array();
        
        $s = array(
            "epi.payroll_group_id",
            "epi.emp_id"
        );
        
        $w = array(
            'epi.company_id' => $company_id
        );
        
        $this->db->select($s);
        $this->db->where($w);
        
        if($emp_ids){
            $this->db->where_in("epi.emp_id",$emp_ids);
        }
        
        $q = $this->db->get('employee_payroll_information AS epi');
        $r = $q->result();
        
        if($r){
            foreach ($r AS $r1){
                $wd = array(
                    "payroll_group_id"  => $r1->payroll_group_id,
                    "emp_id"            => $r1->emp_id,
                    "custom_search"     => "emp_id-{$r1->emp_id}",
                );
                
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }
    
    public function assigned_work_schedule_via_payroll_group($company_id){
        $row_array = array();
        $sel = array(
            "pg.work_schedule_id",
            "ws.category_id",
            "ws.name",
            "ws.work_type_name",
            "pg.payroll_group_id"
        );
        
        $w = array(
            "pg.company_id" => $company_id
        );
        
        $this->db->select($sel);
        $this->db->where($w);
        $this->db->join("work_schedule AS ws", "ws.work_schedule_id = pg.work_schedule_id", "INNER");
        $q = $this->db->get("payroll_group AS pg");
        $r = $q->result();
        
        if($r){
            foreach ($r AS $r1){
                $wd = array(
                    "work_schedule_id"  => $r1->work_schedule_id,
                    "category_id"       => $r1->category_id,
                    "name"              => $r1->name,
                    "work_type_name"    => $r1->work_type_name,
                    "payroll_group_id"  => $r1->payroll_group_id,
                    "custom_search"     => "pgid-{$r1->payroll_group_id}",
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }
    
    public function emp_payroll_info_wsid($company_id,$emp_ids=""){
        $row_array = array();
        $s = array(
            'epi.emp_id',
            'pg.work_schedule_id',
        );
        
        $this->db->select($s);
        
        $w = array(
            'epi.company_id'        => $company_id
        );
        
        $this->db->where($w);
        
        if($emp_ids){
            $this->db->where_in("epi.emp_id",$emp_ids);
        }
        
        $this->db->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','LEFT');
        $q_pg = $this->db->get('employee_payroll_information AS epi');
        $r_pg = $q_pg->result();
        
        if($r_pg){
            foreach ($r_pg as $r1){
                $wd     = array(
                    "work_schedule_id"  => $r1->work_schedule_id,
                    "custom_search"     => "emp_id-{$r1->emp_id}",
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }
    
    public function emp_work_schedule($company_id,$min="",$max="",$emp_ids=""){
        // employee group id
        $row_array = array();
        $s = array(
            "ess.work_schedule_id",
            "ess.emp_id",
            "ess.valid_from",
        );
        $this->edb->select($s);
        
        if($min != "" && $max != ""){
            $w1 = array(
                "ess.valid_from >=" => $min,
                "ess.valid_from <=" => $max
            );
            $this->db->where($w1);
        } else {
            $w1 = array(
                "ess.valid_from >=" => date("Y-m-d"),
                "ess.valid_from <=" => date("Y-m-d"),
            );
            $this->db->where($w1);
        }
        
        if($emp_ids){
            $this->db->where_in("ess.emp_id",$emp_ids);
        }
        $w_emp = array(
            "ess.company_id"        => $company_id,
            "ess.status"            => "Active",
            "ess.payroll_group_id"  => 0
        );
        
        $this->edb->where($w_emp);
        $q_emp = $this->edb->get("employee_shifts_schedule AS ess");
        $r_emp = $q_emp->result();
        
        if($r_emp){
            foreach ($r_emp as $r1){
                $wd     = array(
                    "work_schedule_id"  => $r1->work_schedule_id,
                    "custom_search"     => "emp_id-{$r1->emp_id}-{$r1->valid_from}",
                );
                array_push($row_array,$wd);
            }
        }
        
        return $row_array;
    }
    
    public function generate_list_of_blocks($employee_time_in){
        $w_uwd = array(
            "eti.employee_time_in_id"=>$employee_time_in,
            "eti.status" => 'Active'
        );
        $this->db->where($w_uwd);
        
        $arr = array('time_in' => 'eti.time_in',
            'lunch_out' => 'eti.lunch_out',
            'lunch_in' => 'eti.lunch_in',
            'time_out' => 'eti.time_out',
            'total_hours_required' => 'eti.total_hours_required',
            'total_hours' => 'eti.total_hours',
            'tardiness_min'  => 'eti.tardiness_min',
            'late_min' => 'eti.late_min',
            'overbreak_min' => 'eti.overbreak_min',
            'undertime_min' => 'eti.undertime_min',
            'absent_min' => 'eti.absent_min',
            'source' => 'eti.source',
            'date' => 'eti.date',
            'payroll_cloud_id' => 'a.payroll_cloud_id',
            'employee_time_in_id' => 'eti.employee_time_in_id',
            'comp_id' => 'eti.comp_id',
            'first_name' => 'e.first_name',
            'last_name' => 'e.last_name',
            'department_id' => 'epi.department_id',
            'schedule_blocks_id' => 'eti.schedule_blocks_id'
        );
        $this->edb->select($arr);
        $arr2 = array(
            'emp_id' => 'eti.emp_id',
            'account_id' => 'a.account_id'
        );
        $this->edb->select($arr2);
        /*$this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.
         $this->edb->decrypt('e.last_name').') as full_name',FALSE);*/
        $this->edb->join('employee AS e','e.emp_id = eti.emp_id',"INNER");
        $this->edb->join('accounts AS a','a.account_id = e.account_id',"INNER");
        $this->edb->join('employee_payroll_information AS epi','epi.emp_id = eti.emp_id','left');
        $this->edb->order_by("e.last_name","ASC");
        $q = $this->edb->get('schedule_blocks_time_in AS eti');
        
        $r= $q->result();
        
        
        return ($q->num_rows() > 0) ? $r : false;
    }
    
    public function generate_list_of_blocksv2($comp_id,$min="",$max="",$emp_ids=""){
        $row_array  = array();
        $arr        = array(
            'payroll_cloud_id',
            'first_name',
            'last_name',
        );
        $this->edb->select($arr);
        $arr2 = array(
            'emp_id'                => 'eti.emp_id',
            'account_id'            => 'a.account_id',
            'time_in'               => 'eti.time_in',
            'lunch_out'             => 'eti.lunch_out',
            'lunch_in'              => 'eti.lunch_in',
            'time_out'              => 'eti.time_out',
            'total_hours_required'  => 'eti.total_hours_required',
            'total_hours'           => 'eti.total_hours',
            'tardiness_min'         => 'eti.tardiness_min',
            'late_min'              => 'eti.late_min',
            'overbreak_min'         => 'eti.overbreak_min',
            'undertime_min'         => 'eti.undertime_min',
            'absent_min'            => 'eti.absent_min',
            'source'                => 'eti.source',
            'date'                  => 'eti.date',
            'employee_time_in_id'   => 'eti.employee_time_in_id',
            'schedule_blocks_id'    => 'eti.schedule_blocks_id'
        );
        $this->db->select($arr2);
        
        if($min != "" && $max != ""){
            $w1 = array(
                "eti.date >="   => $min,
                "eti.date <="   => $max
            );
            $this->db->where($w1);
        } else {
            $w1 = array(
                "eti.date >="   => date("Y-m-d"),
                "eti.date <="   => date("Y-m-d"),
            );
            $this->db->where($w1);
        }
        
        if($emp_ids){
            $this->db->where_in("eti.emp_id",$emp_ids);
        }
        
        $w_uwd = array(
            "eti.comp_id"   => $comp_id,
            "eti.status"    => 'Active'
        );
        $this->db->where($w_uwd);
        $this->db->join('employee AS e','e.emp_id = eti.emp_id',"INNER");
        $this->db->join('accounts AS a','a.account_id = e.account_id',"INNER");
        $this->db->join('employee_payroll_information AS epi','epi.emp_id = eti.emp_id','left');
        $this->edb->order_by("e.last_name","ASC");
        $q = $this->edb->get('schedule_blocks_time_in AS eti');
        $r= $q->result();
        
        if($r){
            foreach ($r as $r1){
                $wd     = array(
                    "emp_id"                => $r1->emp_id,
                    "account_id"            => $r1->account_id,
                    "time_in"               => $r1->time_in,
                    "lunch_out"             => $r1->lunch_out,
                    "lunch_in"              => $r1->lunch_in,
                    "time_out"              => $r1->time_out,
                    "total_hours_required"  => $r1->total_hours_required,
                    "total_hours"           => $r1->total_hours,
                    "tardiness_min"         => $r1->tardiness_min,
                    "late_min"              => $r1->late_min,
                    "overbreak_min"         => $r1->overbreak_min,
                    "undertime_min"         => $r1->undertime_min,
                    "absent_min"            => $r1->absent_min,
                    "source"                => $r1->source,
                    "date"                  => $r1->date,
                    "source"                => $r1->source,
                    "schedule_blocks_id"    => $r1->schedule_blocks_id,
                    "employee_time_in_id"   => $r1->employee_time_in_id,
                    "custom_search"         => "eti-{$r1->employee_time_in_id}",
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }
    
    public function check_workday($company_id){
        $row_array  = array();
        $w = array(
            "comp_id"=> $company_id
        );
        $this->db->where($w);
        $arrx = array(
            'work_type_name',
            'work_schedule_id'
        );
        $this->db->select($arrx);
        $q = $this->db->get("work_schedule");
        $r = $q->result();
        
        if($r){
            foreach ($r as $r1){
                $wd     = array(
                    "work_type_name"        => $r1->work_type_name,
                    "work_schedule_id"      => $r1->work_schedule_id,
                    "custom_search"     => "wsi-{$r1->work_schedule_id}",
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }
    
    public function get_work_schedule_id_by_date($emp_id,$check_company_id){
        $row_array_ess = array();
        $row_array_epi = array();
        
        $w_emp = array(
            "ess.emp_id"=>$emp_id,
            "ess.company_id"=>$check_company_id,
            "ess.status"=>"Active",
            "ess.payroll_group_id" => 0
        );
        
        $this->db->where($w_emp);
        $q_emp = $this->db->get("employee_shifts_schedule AS ess");
        $r_emp = $q_emp->result();
        
        if ($r_emp) {
            foreach ($r_emp as $r1){
                $wd     = array(
                    "work_schedule_id" => $r1->work_schedule_id,
                    "custom_search" => "date-{$r1->valid_from}",
                );
                array_push($row_array_ess,$wd);
            }
        }
        
        return $row_array_ess;
    }
    
    public function get_work_schedule_id_default($emp_id,$check_company_id){
        $s = array(
            "pg.work_schedule_id"
        );
        
        $w = array(
            'epi.emp_id'=> $emp_id,
            "epi.company_id"=>$check_company_id
        );
        
        $this->db->select($s);
        $this->db->where($w);
        $this->db->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','LEFT');
        $q_pg = $this->db->get('employee_payroll_information AS epi');
        $r_pg = $q_pg->row();
        
        return ($r_pg) ? $r_pg->work_schedule_id : FALSE;
    }
    
    public function get_leave_breaktime($emp_id,$comp_id,$workday,$work_schedule, $start_time, $end_time, $flag_start = true){
        // for regular schedules and workshift
        $where_workday = array(
            "work_schedule_id"=>$work_schedule,
            "company_id"=>$comp_id,
            "days_of_work"=>$workday
        );
        $this->db->where($where_workday);
        $sql_workday = $this->db->get("regular_schedule");
        $row_workday = $sql_workday->row();
        
        if($row_workday){
            if($row_workday->break_in_min != 0){
                $new_st = date("Y-m-d", strtotime($start_time))." ".$row_workday->work_start_time;
                $total_work_hours = number_format($row_workday->total_work_hours,0);
                $break_in_min = $row_workday->break_in_min;
                #$new_latest_timein_allowed = date('Y-m-d H:i:s', strtotime($new_st.' +'.$row_workday->latest_time_in_allowed.' minutes'));
                $new_latest_timein_allowed = date('Y-m-d H:i:s', strtotime($new_st));
                
                if($new_latest_timein_allowed){ // if latest time in is true
                    if(strtotime($start_time) < strtotime($new_st)){
                        $new_work_start_time = $new_st;
                    }elseif(strtotime($new_st) <= strtotime($start_time) && strtotime($start_time) <= strtotime($new_latest_timein_allowed)){
                        $new_work_start_time = $start_time;
                    }elseif(strtotime($start_time) > strtotime($new_latest_timein_allowed)){
                        $new_work_start_time = $new_latest_timein_allowed;
                    }
                }else{
                    $new_work_start_time = $row_workday->work_start_time;
                }
                
                $end_time = date("Y-m-d", strtotime($end_time))." ".$row_workday->work_end_time; //date('H:i:s', strtotime($new_work_start_time.' +'.$total_work_hours_break.' hours'));
                
                $check_time = ((abs(strtotime($end_time) - strtotime($new_work_start_time))) / 3600) - ($break_in_min / 60);
                $check2 = ($check_time / 2) * 60;
                #p($check_time.' '.$check2);
                #$check2 = round($check2, 0, PHP_ROUND_HALF_DOWN);
                
                $hd_check_start = date('H:i:s', strtotime($new_work_start_time.' +'.$check2.' minutes'));
                $hd_check_end = date('H:i:s', strtotime($new_work_start_time.' +'.$check2.' minutes +'.$break_in_min.' minutes'));
                #sp($hd_check_start.' '.$hd_check_end);
                if($flag_start) {
                    return $hd_check_start;
                } else {
                    return $hd_check_end;
                }
                
            }else{
                return '12:00:00';
            }
        }
    }
    
    public function get_tot_hours_ws_v2($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$time_out,$hours_worked,$work_schedule, $all_break_out = 0,$new_break_rules = false, $shift_date = false){
        #echo $time_out
        // check if rest day
        
        $rest_day = $this->check_holiday_val_ws($time_in,$emp_id,$comp_id,$work_schedule);
        if($rest_day){
            $total = (strtotime($time_out) - strtotime($time_in)) / 3600;
        }else{
            $total_hours_worked = 0;
            // check time out for regular schedules
            $where_uw = array(
                "company_id"=>$comp_id,
                "work_schedule_id"=>$work_schedule,
                "days_of_work"=>date("l",strtotime($time_in))
            );
            $this->db->where($where_uw);
            $sql_uw = $this->db->get("regular_schedule");
            
            $row_uw = $sql_uw->row();
            
            if($sql_uw->num_rows() > 0){
                // FOR CALLCENTER
                $time_in_sec = date("H:i:s",strtotime($time_in));
                $time_out_sec = date("H:i:s",strtotime($time_out));
                $new_time_out = date('Y-m-d H:i:s', strtotime($time_out));
                $new_time_in = date('Y-m-d H:i:s', strtotime($time_in));
                $shift_date_plus_1 = date("Y-m-d", strtotime($shift_date.' +1 days'));
                
                if(strtotime($shift_date_plus_1) == strtotime(date("Y-m-d", strtotime($time_in)))) {
                    if(strtotime($lunch_out) <= strtotime($time_in_sec) && strtotime($lunch_in) >= strtotime($time_in_sec)) {
                        $new_time_in = date("Y-m-d", strtotime($time_in)).' '.$lunch_out;
                    }
                } else {
                    if(strtotime($lunch_out) <= strtotime($time_in_sec) && strtotime($lunch_in) >= strtotime($time_in_sec)) {
                        $new_time_in = date("Y-m-d", strtotime($time_in)).' '.$lunch_in;
                    }
                }
                
                if(strtotime($lunch_out) <= strtotime($time_out_sec) && strtotime($lunch_in) >= strtotime($time_out_sec)) {
                    $new_time_out = date("Y-m-d", strtotime($new_time_out)).' '.$lunch_in;
                }
                #elseif(strtotime($lunch_out) >= strtotime($time_out_sec)) {
                 #   $new_time_out = date("Y-m-d", strtotime($new_time_out)).' '.$lunch_in;
               # }
                
                $total_hours_worked = abs((strtotime($new_time_out) - strtotime($new_time_in)) / 3600);
                #p($new_time_out.' '.$new_time_in);
                #p($total_hours_worked);
            }
            
            $breaktime_hours1 = $this->add_breaktime_ws_toper($comp_id,$work_schedule,$time_in);
            $breaktime_hours = 0;
            $time_in_A = date("A",strtotime($time_in));
            $time_out_A = date("A",strtotime($time_out));
            
            if($breaktime_hours1) {
                $my_date = ($shift_date) ? $shift_date : date("Y-m-d", strtotime($time_in));
                
                //temp code for labang sa rest day
                $rest_day1 = $this->check_holiday_val_ws($my_date,$emp_id,$comp_id,$work_schedule);
                if ($rest_day1) {
                    $my_date = date("Y-m-d", strtotime($time_in));
                }
                
                /*if($breaktime_hours1->assumed_breaks != null || $breaktime_hours1->assumed_breaks != "" || $breaktime_hours1->assumed_breaks != 0) {
                    $assumed_breaks = $breaktime_hours1->assumed_breaks;
                } else {
                    $assumed_breaks = $hours_worked / 2;
                }*/
                
                $assumed_breaks = $hours_worked / 2;
                $assumed_breaks_to_mins = $assumed_breaks * 60;
                $threshold = $breaktime_hours1->latest_time_in_allowed;
                
                $end_break_tot = $assumed_breaks + ($breaktime_hours1->break_in_min / 60);
                
                $start_break = date("Y-m-d H:i:s", strtotime($my_date." ".$breaktime_hours1->work_start_time." +".$assumed_breaks_to_mins." minutes"));
                $end_break = date("Y-m-d H:i:s", strtotime($start_break." +".$breaktime_hours1->break_in_min." minutes"));
                #echo $time_in.' '.$start_break.' '.$time_out.' '.$end_break.'<br>';
                if(strtotime($time_out) >= strtotime($start_break) && strtotime($time_out) <= strtotime($end_break)) {
                    if($new_break_rules) {
                        $breaktime_hours = $all_break_out / 60;
                    } else {
                        $breaktime_hours = $breaktime_hours1->break_in_min / 60;
                    }
                } elseif (strtotime($time_in) < strtotime($start_break) && strtotime($time_out) > strtotime($end_break)) {
                    $breaktime_hours = $breaktime_hours1->break_in_min / 60;
                #} elseif (strtotime($time_in) > strtotime($start_break) && strtotime($time_in) < strtotime($end_break)) {
                 #   $breaktime_hours = $breaktime_hours1->break_in_min / 60;
                }
                else {
                    $breaktime_hours = 0;
                }
                
                
                /*if($time_in_A == "PM" && $time_out_A == "AM") {
                    
                    if(strtotime($time_out) >= strtotime($start_break) && strtotime($time_out) <= strtotime($end_break)) {
                        if($new_break_rules) {
                            $breaktime_hours = $all_break_out / 60;
                        } else {
                            $breaktime_hours = $breaktime_hours1->break_in_min / 60;
                        }
                        
                    } elseif (strtotime($time_out)) {
                        $breaktime_hours = $breaktime_hours1->break_in_min / 60;
                    } else {
                        $breaktime_hours = 0;
                    }
                } else {
                    if(strtotime($start_break) > strtotime($end_break)) {
                        if($new_break_rules) {
                            $breaktime_hours = $all_break_out / 60;
                        } else {
                            $breaktime_hours = $breaktime_hours1->break_in_min / 60;
                        }
                        
                    } else {
                        $breaktime_hours = 0;
                    }
                }*/
            } else {
                $breaktime_hours = $this->add_breaktime_ws($comp_id,$work_schedule,$time_in);
            }
            
            $total = $total_hours_worked - $breaktime_hours;
            #p($total_hours_worked." ".$breaktime_hours);
            if($total > $hours_worked){
                $total = $hours_worked;
            }
            
        }
        
        return ($total < 0) ? round(0,2) : round($total,2) ;
    }
    
    public function check_holiday_val_ws($day,$emp_id,$comp_id,$work_schedule){
        $w = array(
            "rest_day"=>date("l",strtotime($day)),
            "company_id"=>$comp_id,
            "work_schedule_id"=>$work_schedule,
            "status"=>"Active"
        );
        $this->db->where($w);
        $q = $this->db->get('rest_day');
        return ($q->num_rows() > 0) ? TRUE : FALSE ;
    }
    
    public function add_breaktime_ws_toper($comp_id,$work_schedule,$workday ){        
        $day = date("l",strtotime($workday));
        
        $where = array(
            "rs.days_of_work" => $day,
            "rs.company_id" => $comp_id,
            "rs.work_schedule_id"=>$work_schedule,
        );
        $this->db->where($where);
        $this->db->join("work_schedule AS ws", "ws.work_schedule_id = rs.work_schedule_id", "LEFT");
        $sql = $this->db->get("regular_schedule AS rs");
        $r = $sql->row();
        
        return ($r) ? $r : false;
    }
    
    public function get_breakdown_pay_details($comp_id,$emp_id,$period_from,$period_to){
        $w = array(
            "company_id" => $comp_id,
            "emp_id" => $emp_id,
            "period_from" => $period_from,
            "period_to" => $period_to,
            "status" => "Active"
        );
        
        $this->db->where($w);
        $q = $this->db->get("payroll_cronjob");
        $r = $q->row();
        
        return ($r) ? $r : false;
    }
    
    public function change_shift_valid_time($work_sched_id,$comp_id,$day){
        $sel = array(
            'rs.work_start_time',
            'rs.work_end_time'
        );
        
        $where = array(
            'rs.work_schedule_id' => $work_sched_id,
            'rs.company_id' => $comp_id,
            'rs.days_of_work' => $day
        );
        $this->db->select($sel);
        $this->db->where($where);
        $this->db->join("work_schedule AS ws", "ws.work_schedule_id = rs.work_schedule_id","LEFT");
        $this->db->group_by('ws.work_schedule_id');
        $query = $this->db->get("regular_schedule AS rs");
        $res = $query->row();
        
        if ($res) {
            $regular_res = array(
                "type" => "Uniform Working Days",
                "work_start_time" => $res->work_start_time,
                "work_end_time" => $res->work_end_time
            );
            
            return $regular_res;
        } else {
            $fsel = array(
                'fh.latest_time_in_allowed',
                "fh.not_required_login"
                
            );
            $fwhere = array(
                'fh.work_schedule_id' => $work_sched_id,
                'fh.company_id' => $comp_id
            );
            $this->db->select($fsel);
            $this->db->where($fwhere);
            $this->db->join("work_schedule AS ws", "ws.work_schedule_id = fh.work_schedule_id","LEFT");
            $this->db->group_by('ws.work_schedule_id');
            $query = $this->db->get("flexible_hours AS fh");
            $res = $query->row();
            
            if ($res) {
                $flexi_res = array(
                    "type" => "Flexible Hours",
                    "work_start_time" => "Latest Time In",
                    "work_end_time" => $res->latest_time_in_allowed,
                    "not_required_login" => $res->not_required_login
                );
                
                return $flexi_res;
            } else {
                return FALSE;
            }
        }
    }
    
    public function check_payroll_lock_closed($emp_id,$comp_id,$gDate){
        $gDate          = date("Y-m-d",strtotime($gDate));
        $return_void    = false;
        $stat_v1        = "";
        $w = array(
                'prc.emp_id'            => $emp_id,
                'prc.company_id'        => $comp_id,
                'prc.period_from <='    => $gDate,
                'prc.period_to >='      => $gDate,
                'prc.status'            => 'Active'
        );
        $s = array(
                'dpr.view_status'
        );
        $this->db->select($s);
        $this->db->where($w);
        $this->db->join("draft_pay_runs as dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id");
        $q = $this->db->get('payroll_run_custom as prc');
        $r = $q->result();
        if($r){
            foreach($r as $r1){
                $stat_v = $r1->view_status;
                if($stat_v == "Waiting for approval" || $stat_v == "Closed"){
                    $return_void = true;
                }
            }
        }else{
            $w1 = array(
                    'epi.emp_id'            => $emp_id,
                    'dpr.company_id'        => $comp_id,
                    'dpr.period_from <='    => $gDate,
                    'dpr.period_to >='      => $gDate,
                    'dpr.status'            => 'Active'
            );
            $s1 = array(
                    'dpr.view_status'
            );
            $this->db->select($s1);
            $this->db->where($w1);
            $this->db->join("draft_pay_runs as dpr","dpr.payroll_group_id = epi.payroll_group_id");
            $q1 = $this->db->get('employee_payroll_information as epi');
            $r1 = $q1->result();
            if($r1){
                foreach($r1 as $r1x){
                    $stat_v = $r1x->view_status;
                    if($stat_v == "Waiting for approval" || $stat_v == "Closed"){
                        $return_void = true;
                    }
                }
            }
        }
    
        if($return_void){
            return $stat_v;
        }else{
            return false;
        }
    }
    
    public function add_breaktime_ws($comp_id,$work_schedule,$workday ){
        $CI =& get_instance();
        
        $day = date("l",strtotime($workday));
        
        $where = array(
            "days_of_work" => $day,
            "company_id" => $comp_id,
            "work_schedule_id"=>$work_schedule,
        );
        $this->db->where($where);
        $sql = $this->db->get("regular_schedule");
        $r = $sql->row();
        
        // FOR REGULAR SCHEDULES
        if($r){
            $breaktime = $r->break_in_min / 60;
        }else{
            $w_f = array(
                'company_id'=> $comp_id,
                'work_schedule_id'=> $work_schedule
            );
            $this->db->where($w_f);
            $q_f = $this->db->get("flexible_hours");
            $r_f = $q_f->row();
            
            if($r_f){
                $breaktime = $r_f->duration_of_lunch_break_per_day / 60;
            }else{
                $breaktime = 0;
            }
        }
        
        return ($breaktime < 0) ? 0 : $breaktime ;
    }
    
    public function get_tot_hours_ws_v3($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$time_out,$hours_worked,$work_schedule, $shift_date,$lunch_hr_required){
        $total_hours_worked = 0;
        // check time out for regular schedules
        $where_uw = array(
            "company_id" => $comp_id,
            "work_schedule_id" => $work_schedule,
            #"days_of_work" => date("l",strtotime($time_in))
            "days_of_work" => date("l",strtotime($shift_date))
        );
        
        $this->db->where($where_uw);
        $sql_uw = $this->db->get("regular_schedule");
        #p(date("l",strtotime($time_in)).' '.$work_schedule);
        $row_uw = $sql_uw->row();
        
        if($sql_uw->num_rows() > 0){
            // FOR CALLCENTER
            $time_in_sec = date("H:i:s",strtotime($time_in));
            $time_out_sec = date("H:i:s",strtotime($time_out));
            $new_time_out = date('Y-m-d H:i:s', strtotime($time_out));
            $new_time_in = date('Y-m-d H:i:s', strtotime($time_in));
            $shift_date_plus_1 = date("Y-m-d", strtotime($shift_date.' +1 days'));
            
            /*if(strtotime($shift_date_plus_1) == strtotime(date("Y-m-d", strtotime($time_in)))) {
                if(strtotime($lunch_out) <= strtotime($time_in_sec) && strtotime($lunch_in) >= strtotime($time_in_sec)) {
                    $new_time_in = date("Y-m-d", strtotime($time_in)).' '.$lunch_out;
                }
            } else {
                if(strtotime($lunch_out) <= strtotime($time_in_sec) && strtotime($lunch_in) >= strtotime($time_in_sec)) {
                    $new_time_in = date("Y-m-d", strtotime($time_in)).' '.$lunch_in;
                }
            }*/
            
            /*if(strtotime($lunch_out) <= strtotime($time_out_sec) && strtotime($lunch_in) >= strtotime($time_out_sec)) {
                $new_time_out = date("Y-m-d", strtotime($new_time_out)).' '.$lunch_in;
            }*/
            #p($new_time_out.' '.$new_time_in);
            $total_hours_worked = abs((strtotime($new_time_out) - strtotime($new_time_in)) / 3600);
        }
        
        $breaktime_hours1 = $this->add_breaktime_ws_toper($comp_id,$work_schedule,$time_in);
        $breaktime_hours = 0;
        $time_in_A = date("A",strtotime($time_in));
        $time_out_A = date("A",strtotime($time_out));
        
        if ($lunch_hr_required == "1") {
            if($breaktime_hours1) {
                $breaktime_hours = $breaktime_hours1->break_in_min / 60;
            } else {
                #$breaktime_hours = $this->add_breaktime_ws($comp_id,$work_schedule,$time_in);
                $breaktime_hours = $this->add_breaktime_ws($comp_id,$work_schedule,$shift_date);
            }
        } else {
            $breaktime_hours = 0;
        }
        #p($total_hours_worked);
        $total = $total_hours_worked - $breaktime_hours;
        
        if($total > $hours_worked){
            $total = $hours_worked;
        }
        
        return ($total < 0) ? round(0,2) : round($total,2) ;
    }
    
    public function get_all_change_shifts($check_company_id,$emp_ids="",$max_date="",$min_date=""){
        // employee group id
        $row_array  = array();
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
                $wd     = array(
                    "emp_id"    => $r1->emp_id,
                    "shift_date"                => $r1->shift_date,
                    "updated_date"              => $r1->updated_date,
                    "custom_search"     => "change_shift_{$r1->emp_id}_{$r1->shift_date}"
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }
    
    public function check_existing_timein_for_changelog($emp_id, $company_id, $date, $time_in, $time_out,$employee_time_in_id) {
        $new_date = date("Y-m-d", strtotime($date));
        $time_in = date('Y-m-d H:i:s', strtotime($time_in));
        $time_out = date('Y-m-d H:i:s', strtotime($time_out));
        $where = array(
            "emp_id"            => $emp_id,
            "comp_id"           => $company_id,
            "date"              => $new_date,
            "status"            => "Active",
            "employee_time_in_id != "=>$employee_time_in_id
        );
        
        $this->db->where($where);
        // pasensya na sa manual query mao naabut sako utok ug sa deadline LoL
        $this->db->where("((time_in >= '{$time_in}' AND time_in <= '{$time_out}' AND emp_id = {$emp_id} AND status = 'Active')
                        OR (time_out >= '{$time_in}' AND time_out <= '{$time_out}' AND emp_id = {$emp_id} AND status = 'Active')
                        OR (time_in <= '{$time_in}' AND time_out >= '{$time_out}' AND emp_id = {$emp_id} AND status = 'Active'))");
        $this->db->where("(time_in_status != 'reject' OR time_in_status is null)");
        $query = $this->db->get("employee_time_in");
        $res = $query->row();
        
        // u can change the "true" result to array data as result
        return ($res) ? true : false;
    }
    
    public function get_timein_log_info($comp_id, $emp_id, $timein_id){
        $s = array(
            'date',
            'time_in',
            'lunch_out',
            'lunch_in',
            'time_out',
            
            'break1_out',
            'break1_in',
            'break2_out',
            'break2_in',
            
            'work_schedule_id',
            'employee_time_in_id',
            'reason',
            'total_hours_required',
            
            'holiday_approve',
            'rest_day_r_a'
        );
        
        $where = array(
            "emp_id"            => $emp_id,
            "comp_id"           => $comp_id,
            "status"            => "Active",
            "employee_time_in_id"=>$timein_id
        );
        $this->db->where($where);
        
        $q = $this->db->get('employee_time_in');
        $r = $q->row();
        
        return ($r) ? $r : FALSE;
    }
    
    public function get_open_shift_leave($wsid, $company_id) {
        $w = array(
            'comp_id'=> $company_id,
            'work_schedule_id' => $wsid,
            'work_type_name' => "Open Shift",
            'status' => "Active",
        );
        
        $this->db->where($w);
        $q_pg = $this->db->get('work_schedule');
        $r_pg = $q_pg->row();
        
        return ($r_pg) ? $r_pg : false;
    }
    
    public function get_workschedule_break($company_id,$date,$work_schedule_id = "",$activate = false,$all_sched_flex=array(),$all_sched_reg=array()){
        $data = "";
        $day = date('l',strtotime($date));
        $reg_data   = in_array_custom("rwis-{$work_schedule_id}-{$day}",$all_sched_reg);
        if($reg_data){
            if($activate){
                $arr = array(
                    'start_time'    => $reg_data->work_start_time,
                    'end_time'      => $reg_data->work_end_time,
                    'break'         => $reg_data->break_in_min,
                    'total_hours'   => $reg_data->total_work_hours,
                    'name'          => $reg_data->work_schedule_name,
                    'type'          => 1
                );
                return $arr['break'];
            }
            else{
                $data  = $reg_data->break_in_min;
            }
        }
        else{
            $flex_data  = in_array_custom("wsi-".$work_schedule_id,$all_sched_flex);
            if($flex_data){
                $total_h = $flex_data->total_hours - ($flex_data->break / 60);
                $total_h = number_format($total_h,2);
                if($activate){
                    $arr = array(
                        'start_time'    => $flex_data->latest_time_in_allowed,
                        'end_time'      => "",
                        'break'         => $flex_data->break,
                        'total_hours'   => $flex_data->total_hours,
                        'name'          => '',
                        'type'          => 2
                    );
                    
                    return $arr['break'];
                }else{
                    if($flex_data->latest_time_in_allowed != NULL || $flex_data->latest_time_in_allowed != ""){
                        $data = $flex_data->break;
                    }else{
                        $data = $flex_data->break;
                    }
                }
            }
        }
        return $data;
    }
    
    public function get_leave_balance_on_ledger($company_id, $emp_id, $leave_type_id) {
        $w = array(
            "company_id" => $company_id,
            "emp_id" => $emp_id,
            "leave_type_id" => $leave_type_id
        );
        
        $this->db->where($w);
        $this->db->order_by('employee_leave_ledger_id','DESC');
        $q = $this->db->get("employee_leave_ledger", 1);
        $r = $q->row();
        
        return ($r) ? $r : false;
    }
    
    public function resend_timesheet_data($emp_id,$comp_id,$employee_time_in_id) {
        $w = array(
            "emp_id" => $emp_id,
            "comp_id" => $comp_id,
            "employee_time_in_id" => $employee_time_in_id,
            "status" => "Active",
        );
        
        $this->db->where($w);
        $q = $this->db->get("timesheet_close_payroll");
        $r = $q->row();
        
        return ($r) ? $r : false;
    }
    
    public function get_overtime_data_to_resend($overtime_id){
        $w = array(
            "overtime_id"=>$overtime_id
        );
        
        $this->db->where($w);
        $q = $this->db->get("employee_overtime_application");
        return ($q->num_rows() > 0) ? $q->row() : FALSE ;
    }
    
    public function get_emp_ledger($leave_type_id,$emp_id,$date){
        #$date = date("Y", strtotime($date));
        $w = array(
            "leave_type_id"=>$leave_type_id,
            "emp_id" => $emp_id,
            "status" => "Active",
            "YEAR(date)" => $date
        );
        
        $this->db->where($w);
        $q = $this->db->get("employee_leave_ledger");
        $r = $q->result();
        
        return ($r) ? $r : FALSE ;
    }
    
    public function employee_allowances_avail($emp_id, $comp_id, $app=true,$limit = 10000, $start = 0) {
        $sort_array = array(
            "name",
            "efa.applicable_daily_rates",
            "efa.hourly_rate",
            "efa.daily_rate",
            "efa.allowance_amount"
        );
        $s = array("*","efa.pay_out_schedule AS pay_out_schedule");
        $this->db->select($s);
        
        
        /*
            Edit by 47: 
            -added status = Active
            -added filter for allowance plus with eligibility
            -added filter for custom allowance plus
        */
        if($app) {
            $w = array(
                "efa.emp_id"=>$emp_id,
                "efa.company_id"=>$comp_id,
                "efa.status"=>"Active",
                "als.frequency" => "one time",
                "als.flag_application" => "yes",
                "efa.application_status" => "approved",
                "als.status" => "Active",
            );
            $this->db->where("(als.eligibility_type IS NULL OR als.eligibility_type = '' )");
            $this->db->where($w);
            
            $this->db->where("(efa.date_filed IS NULL)");
           
        } else {
            $w = array(
                "efa.emp_id"=>$emp_id,
                "efa.company_id"=>$comp_id,
                "efa.status"=>"Active",
                "als.frequency" => "one time",
                "als.flag_application" => "yes",
            );
            
            $this->db->where($w);
            
            $this->db->where("(efa.application_status IS NOT NULL)");
            $this->db->where("(efa.application_date IS NOT NULL)");
        }
        
       
        $this->db->join("allowance_settings AS als","als.allowance_settings_id = efa.allowance_settings_id","LEFT");
        $this->db->order_by("efa.date_filed","DESC");
        $q = $this->db->get("employee_allowances AS efa",$limit,$start);
        $r = $q->result();
        
        return ($q->num_rows() > 0) ? $r : FALSE ;
    }
    
    public function get_allowance_type($allowance_settings_id) {
        $w = array(
            "allowance_settings_id" => $allowance_settings_id,
            "status"=>"Active",
        );
        
        $this->db->where($w);
        $q = $this->db->get("allowance_settings");
        $r = $q->row();
        return ($r) ? $r : FALSE ;
    }
    
    public function get_allowance_last_level($emp_id, $company_id){
        $this->db->where("emp_id",$emp_id);
        $sql = $this->db->get("employee_payroll_information");
        $row = $sql->row();
        if($row){
            $allowance_approval_grp = $row->allowance_approval_grp;
            $w = array(
                "ag.company_id"=>$company_id,
                "ag.approval_groups_via_groups_id"=>$allowance_approval_grp
            );
            $this->db->where($w);
            $this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
            $this->db->order_by("ag.level","DESC");
            $q = $this->edb->get("approval_groups AS ag",1);
            $r = $q->row();
            return ($r) ? $r->level : FALSE ;
        }else{
            return FALSE;
        }
    }
    
    public function get_approver_name_allowance($emp_id,$company_id){
        $this->db->where("emp_id",$emp_id);
        $sql = $this->db->get("employee_payroll_information");
        $row = $sql->row();
        if($row){
            $allowance_approval_grp = $row->allowance_approval_grp;
            
            if($allowance_approval_grp == "" || $allowance_approval_grp == 0) {
                // Employee with no approver will use default workflow approval
                $allowance_approval_grp = get_app_default_approver($company_id,"Allowance")->approval_groups_via_groups_id;
            }
            
            $w = array(
                "ag.company_id"=>$company_id,
                "ag.approval_groups_via_groups_id"=>$allowance_approval_grp,
                "ap.name"=> "Allowance"
                
                
            );
            $this->db->where($w);
            $this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
            $this->edb->join("approval_process AS ap","ag.approval_process_id = ap.approval_process_id","LEFT");
            $this->db->order_by("ag.level","ASC");
            $q = $this->edb->get("approval_groups AS ag");
            $r = $q->result();
            return ($r) ? $r : FALSE ;
        }else{
            return FALSE;
        }
    }
    
    public function employee_allowances_app($emp_id, $comp_id, $limit = 10, $start = 0, $search = "", $sort_by = "", $sort = "ASC") {
        if(is_numeric($comp_id)){
            $konsum_key = konsum_key();
            $start = intval($start);
            $limit = intval($limit);
            
            $sort_array = array(
                "first_name",
                "application_date",
                "date_filed",
                "frequency",
                "name",
                "allowance_amount",
            );
            
            #$s = array("*","efa.pay_out_schedule AS pay_out_schedule");
            #$this->db->select($s);
            $w = array(
                "ag.emp_id" => $emp_id,
                "ea.company_id"=>$comp_id,
                "ea.status"=>"Active",
                "ea.frequency" => "one time",
                "ea.application_status" => "pending"
                
            );
            
            if($search != "" && $search != "all"){
                $this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
            }
            
            $select = array(
                #"*",
                #"pg.name AS pg_name",
                "epi.allowance_approval_grp",
                "aa.level",
                "ea.employee_allowances_id",
                "ea.application_status",
                "aa.approve_by_head",
                "ea.emp_id",
                "ea.company_id",
                "ea.application_date",
                "ea.date_filed",
                "ea.allowance_settings_id",
                "ea.allowance_amount",
                "ea.frequency",
                "ea.pay_out_schedule",
                "ea.reason",
                "ea.remarks",
                "als.name",
                "ea.allowance_amount",
                "als.payroll_item",
                "als.payroll_on_demand"
            );
            
            $select1 = array(
                "a.payroll_cloud_id",
                "e.first_name",
                "e.last_name",
            );
            
            $this->db->select($select);
            $this->edb->select($select1);
            
            $this->db->where($w);
            $this->edb->join("employee AS e","e.emp_id = ea.emp_id","LEFT");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
            $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
            $this->edb->join("approval_groups_via_groups AS agg","epi.allowance_approval_grp = agg.approval_groups_via_groups_id","LEFT");
            
            $this->edb->join("approval_allowance AS aa","aa.employee_allowances_id = ea.employee_allowances_id","LEFT");
            $this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND aa.level = ag.level","LEFT");
            $this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
            $this->edb->join("allowance_settings AS als","als.allowance_settings_id = ea.allowance_settings_id","LEFT");
            $this->db->group_by('ea.employee_allowances_id');
            
            if($sort_by != ""){
                if(in_array($sort_by, $sort_array)){
                    $this->db->order_by($sort_by,$sort);
                }
            } else {
                $this->db->order_by("date_filed","DESC");
            }
            
            $q = $this->edb->get("employee_allowances AS ea",$limit,$start);
            $r = $q->result();
            
            return ($q->num_rows() > 0) ? $r : FALSE ;
        }
        
    }
    
    public function allowance_information($val){
        $select = array(
            "ea.employee_allowances_id",
            "ea.emp_id",
            "ea.company_id",
            "ea.allowance_settings_id",
            "ea.allowance_amount",
            "ea.frequency",
            "ea.pay_out_schedule",
            "ea.status",
            "ea.date_filed",
            "ea.application_date",
            "ea.application_status",
            "ea.reason",
            "ea.remarks",
            "aa.level",
            "als.name",
            "ea.application_date",
            "ea.date_filed",
            "aa.approve_by_head"
        );
        
        $select1 = array(
            "e.last_name",
            "e.first_name"
        );
        
        $w = array(
            "ea.employee_allowances_id" => $val,
            "ea.status" => "Active"
        );
        
        $this->db->select($select);
        $this->edb->select($select1);
        $this->db->where($w);
        $this->db->join("allowance_settings AS als","als.allowance_settings_id = ea.allowance_settings_id","LEFT");
        $this->db->join("approval_allowance AS aa","ea.employee_allowances_id = aa.employee_allowances_id","LEFT");
        $this->edb->join("employee AS e","e.emp_id = ea.emp_id","LEFT");
        $q = $this->edb->get("employee_allowances AS ea");
        $r = $q->row();
        
        return ($r) ? $r : FALSE ;
    }
    
    public function check_employee_allowance_application($company_id,$employee_allowances_id) {
        if(is_numeric($company_id) && is_numeric($employee_allowances_id)) {
            $where = array(
                "company_id"    => $company_id,
                "employee_allowances_id" => $this->db->escape_str($employee_allowances_id),
                "status" => "Active"
            );
            
            $this->db->select("employee_allowances_id,emp_id,allowance_settings_id,allowance_amount,application_date,application_status");
            $query = $this->db->get_where("employee_allowances",$where);
            $row = $query->row();
            $query->free_result();
            return $row;
        } else {
            return false;
        }
    }
    
    public function update_field($database,$field,$where){
        $this->db->where($where);
        $this->db->update($database,$field);
        return $this->db->affected_rows();
    }
    
    public function generate_allowance_level_token($new_level, $employee_allowances_id){
        $str2 = 'ABCDEFG1234567890';
        $shuffled2 = str_shuffle($str2);
        
        $update = array(
            "level" => $new_level,
            "token_level" => $shuffled2
        );
        $where = array(
            "employee_allowances_id" => $employee_allowances_id
        );
        
        $this->db->where($where);
        $update_approval_allowance_token = $this->db->update("approval_allowance",$update);
        
        return ($update_approval_allowance_token) ? $shuffled2 : false;
    }
    
    public function get_token($employee_allowances_id,$comp_id,$emp_id){
        $w = array(
            "employee_allowances_id" => $employee_allowances_id,
            "company_id" => $comp_id,
            "emp_id" => $emp_id,
            "status" => "Active"
        );
        
        $this->db->where($w);
        $q = $this->db->get("approval_allowance");
        $row = $q->row();
        return ($q->num_rows() > 0) ? $row->token : "" ;
    }
    
    public function check_existing_allowance($date, $allowance_settings_id){
        $w = array(
            "application_date" => $date,
            "allowance_settings_id" => $allowance_settings_id,
            "status" => "Active"
        );
        
        $this->db->where($w);
        $q = $this->edb->get("employee_allowances");
        $r = $q->result();
        
        return ($r) ? $r : FALSE ;
    }
    
    public function check_required_login($work_schedule_id,$check_company_id){
        $w = array(
            "work_schedule_id"=>$work_schedule_id,
            "company_id"=>$check_company_id
        );
        $this->db->where($w);
        $q = $this->db->get("flexible_hours");
        $r = $q->row();
        if($r){
            return ($r->not_required_login != NULL || $r->not_required_login != 0) ? TRUE : FALSE ;
        }else{
            return FALSE;
        }
    }
    
    public function get_pending_timesheet_to_ping($company_id,$emp_id,$approver_emp_id){
        if(is_numeric($company_id)){
            
            $s = array(
                "ee.employee_time_in_id",
                "ee.emp_id",
                "ee.date",
                "ee.time_in_status",
                "ee.source",
                "ee.last_source",
                "ati.level",
                "ati.flag_add_logs",
                "ag.emp_id as approver_emp_id",
                "epi.add_logs_approval_grp",
                "epi.attendance_adjustment_approval_grp"
            );
            
            if($approver_emp_id == "-99".$company_id) {
                $where = array(
                    'ee.emp_id' => $emp_id,
                    'ee.comp_id'   => $company_id,
                    'ee.status'   => 'Active',
                    'ee.corrected' => 'Yes',
                    #"ag.emp_id" => $approver_emp_id,
                    'ee.time_in_status'=> 'pending',
                    'ee.source !=' => 'mobile',
                    'ee.flag_payroll_correction' => 'no',
                    "ati.level !=" => ""
                );
            } else {
                $where = array(
                    'ee.emp_id' => $emp_id,
                    'ee.comp_id'   => $company_id,
                    'ee.status'   => 'Active',
                    'ee.corrected' => 'Yes',
                    "ag.emp_id" => $approver_emp_id,
                    'ee.time_in_status'=> 'pending',
                    'ee.source !=' => 'mobile',
                    'ee.flag_payroll_correction' => 'no',
                    "ati.level !=" => ""
                );
            }
            
            
            $this->db->select($s);
            $this->db->order_by('ee.date','DESC');
            $this->db->where($where);
            $this->db->where(" (ee.time_in_status ='pending') ",NULL,FALSE);
            #$this->db->where("(ee.source != 'system' AND ee.source != 'mobile')");
            
            $this->db->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','LEFT');
            $this->db->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id OR epi.add_logs_approval_grp = agg.approval_groups_via_groups_id","LEFT");
            $this->db->join("approval_time_in AS ati","ati.approval_time_in_id= ee.approval_time_in_id","LEFT");
            $this->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND ati.level = ag.level","LEFT");
            $this->db->group_by("ee.employee_time_in_id");
            $query = $this->db->get('employee_time_in AS ee');
            
            $result = $query->result();
            $query->free_result();
            
            #last_query();
            return $result;
            
        }else{
            return false;
        }
    }
    
    public function get_pending_leave_to_ping($company_id,$emp_id,$approver_emp_id){
        if(is_numeric($company_id)){
            
            if($approver_emp_id == "-99".$company_id) {
                $where = array(
                    'el.emp_id' => $emp_id,
                    "el.company_id" => $company_id,
                    "el.deleted" => '0',
                    #"ag.emp_id" => $approver_emp_id,
                    "el.leave_application_status"=>"pending",
                    "el.flag_payroll_correction" => "no",
                    "al.level !=" => ""
                );
            } else {
                $where = array(
                    'el.emp_id' => $emp_id,
                    "el.company_id" => $company_id,
                    "el.deleted" => '0',
                    "ag.emp_id" => $approver_emp_id,
                    "el.leave_application_status"=>"pending",
                    "el.flag_payroll_correction" => "no",
                    "al.level !=" => ""
                );
            }
            
            $select = array(
                "epi.leave_approval_grp",
                "al.level",
                "el.employee_leaves_application_id",
                "el.leave_application_status",
                "el.emp_id",
                "el.company_id",
                "el.shift_date",
                "el.date_filed",
                "lt.leave_type_id",
                "el.date_start",
                "el.date_end",
                "ag.emp_id as approver_emp_id"
            );
            
            
            $this->db->select($select);
            $this->db->where($where);
            
            $this->db->join("employee_payroll_information AS epi","epi.emp_id = el.emp_id","LEFT");
            $this->db->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
            
            $this->db->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
            $this->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND al.level = ag.level","LEFT");
            $this->db->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
            $this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
            $this->db->join("employee_leaves AS empl","empl.leave_type_id = el.leave_type_id and empl.emp_id = el.emp_id","LEFT");
            $this->db->group_by('el.employee_leaves_application_id');
            
            $this->db->order_by("date_filed","DESC");
            
            $query = $this->db->get("employee_leaves_application AS el");
            
            $result = $query->result();
            $query->free_result();
            
            return ($result) ? $result : false;
        }else{
            return false;
        }
    }
    
    public function get_pending_overtime_to_ping($company_id,$emp_id,$approver_emp_id){
        if(is_numeric($company_id)){
            
            if($approver_emp_id == "-99".$company_id) {
                $where = array(
                    'o.emp_id'                   => $emp_id,
                    'o.company_id'               => $company_id,
                    'o.deleted'                  => '0',
                    #"ag.emp_id"                  => $approver_emp_id,
                    "o.overtime_status"          => "pending" ,
                    "o.flag_payroll_correction"  => "no",
                    "ao.level !="                => ""
                );
            } else {
                $where = array(
                    'o.emp_id'                   => $emp_id,
                    'o.company_id'               => $company_id,
                    'o.deleted'                  => '0',
                    "ag.emp_id"                  => $approver_emp_id,
                    "o.overtime_status"          => "pending" ,
                    "o.flag_payroll_correction"  => "no",
                    "ao.level !="                => ""
                );
            }
            
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
                "o.emp_id",
                "o.company_id",
                "ag.emp_id as approver_emp_id"
            );
            
            $this->db->select($select);
            $this->db->where($where);
            
            $this->db->join("employee_payroll_information AS epi","epi.emp_id = o.emp_id","LEFT");
            $this->db->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
            $this->db->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
            $this->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND ao.level = ag.level","LEFT");
            $this->db->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
            
            $this->db->group_by("o.overtime_id");
            $this->db->order_by("o.overtime_date_applied","DESC");
            $this->db->order_by("o.overtime_id","DESC");
            
            $query = $this->db->get('employee_overtime_application AS o');
            $result = $query->result();
            
            $query->free_result();
            return $result;
        }else{
            return false;
        }
    }
    
    public function get_pending_shift_request_to_ping($company_id,$emp_id,$approver_emp_id){
        if(is_numeric($company_id)){
            if($approver_emp_id == "-99".$company_id) {
                $where = array(
                    'ewsa.emp_id' => $emp_id,
                    'ewsa.company_id' => $company_id,
                    #"ag.emp_id" => $approver_emp_id,
                    "ewsa.employee_work_schedule_status" => "pending",
                    "aws.level !=" => ""
                );
            } else {
                $where = array(
                    'ewsa.emp_id' => $emp_id,
                    'ewsa.company_id' => $company_id,
                    "ag.emp_id" => $approver_emp_id,
                    "ewsa.employee_work_schedule_status" => "pending",
                    "aws.level !=" => ""
                );
            }
            
            $sel = array(
                'ewsa.emp_id',
                "ewsa.employee_work_schedule_application_id",
                "aws.level",
                "ewsa.company_id",
                'ewsa.date_from',
                'ewsa.date_to',
                "ag.emp_id as approver_emp_id"
            );
            
            $this->db->select($sel);
            $this->db->where($where);
            
            $this->db->join("employee_payroll_information AS epi","epi.emp_id = ewsa.emp_id","LEFT");
            $this->db->join("approval_groups_via_groups AS agg","epi.shedule_request_approval_grp = agg.approval_groups_via_groups_id","LEFT");
            
            $this->db->join("approval_work_schedule AS aws","aws.employee_work_schedule_application_id = ewsa.employee_work_schedule_application_id","LEFT");
            $this->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND aws.level = ag.level","LEFT");
            $this->db->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
            
            $this->db->group_by("ewsa.employee_work_schedule_application_id");
            $this->db->order_by("ewsa.date_filed","DESC");
            $this->db->order_by("ewsa.employee_work_schedule_application_id","DESC");
            
            $query = $this->edb->get('employee_work_schedule_application AS ewsa');
            $result = $query->result();
            
            $query->free_result();
            return $result;
        }else{
            return false;
        }
        
    }
    
    public function get_location_of_emp($company_id,$emp_id) {
        $s = array(
            'location_and_offices_id'
        );
        
        $w1 = array(
            'company_id'=> $company_id,
            'emp_id'=> $emp_id,
            'status' => 'Active'
        );
        
        $this->db->select($s);
        $this->db->where($w1);
        $q1 = $this->db->get('employee_payroll_information');
        $r1 = $q1->row();
        
        return ($r1) ? $r1 : false;
    }
    
    public function get_approver_name_allowance_grp($emp_id,$comp_id){
        $w = array(
            'epi.emp_id'=>$emp_id,
            'epi.company_id'=>$comp_id
        );
        $this->edb->where($w);
        $this->edb->join("employee AS e","epi.allowance_approval_grp = e.emp_id","left");
        $this->edb->join("accounts AS a","e.account_id = a.account_id","left");
        $q = $this->edb->get("employee_payroll_information AS epi");
        return ($q) ? $q->row() : FALSE ;
    }
}