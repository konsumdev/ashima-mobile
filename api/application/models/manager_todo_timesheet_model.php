<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//manager_todo_timesheet_model
class Manager_todo_timesheet_model extends CI_Model
{
    public function get_timesheet_approval_lists($emp_id, $company_id) 
    {
        if (!$emp_id) return false;
        
        $filter = $this->not_in_search_timein($emp_id, $company_id);
        return false;
        $where = array(
            'ee.comp_id'        => $company_id,
            'ee.status'         => 'Active',
            'ee.corrected'      => 'Yes',
            'ag.emp_id'         => $emp_id,
            'ee.time_in_status' => 'pending',
            'ee.source !='      => 'mobile' 
        );
        
        $where2 = array(
            'at.level !='   => '',
            'ee.source !='  => 'mobile'
        );
        
        $select = array(
            'ee.employee_time_in_id',
            'ee.work_schedule_id',
            'ee.emp_id',
            'ee.comp_id',
            'ee.date',
            'ee.time_in',
            'ee.lunch_out',
            'ee.lunch_in',
            'ee.time_out',
            'ee.total_hours',
            'ee.total_hours_required',
            'ee.reason',
            'ee.time_in_status',
            'ee.tardiness_min',
            'ee.undertime_min',
            'ee.notes',
            'ee.source',
            'ee.last_source',
            'ee.change_log_date_filed',
            'ee.change_log_time_in',
            'ee.change_log_lunch_out',
            'ee.change_log_lunch_in',
            'ee.change_log_time_out',
            'ee.change_log_total_hours',
            'ee.change_log_total_hours_required',
            'ee.change_log_tardiness_min',
            'ee.change_log_undertime_min',
            'ee.location_1',
            'ee.location_2',
            'ee.location_3',
            'ee.location_4',
            'd.department_name',
            'at.level'
        );

        $edb_select = array(
            'a.payroll_cloud_id',
            'a.profile_image',
            'e.last_name',
            'e.first_name',
            'e.middle_name'
        );

        $this->db->select($select);
        $this->edb->select($edb_select);
        $this->edb->where($where);
        $this->db->where($where2);
        if($filter != FALSE){
            $this->db->where("ee.employee_time_in_id NOT IN ({$filter})");
        }
        
        $this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
        $this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
        $this->edb->join('accounts AS a','a.account_id = e.account_id','left');
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
        $this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
        $this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
        $this->edb->join("approval_time_in AS at","at.approval_time_in_id= ee.approval_time_in_id","LEFT");
        $this->edb->join("work_schedule AS ws","ee.work_schedule_id = ws.work_schedule_id","LEFT");
        $this->db->group_by("employee_time_in_id");
        
        $query = $this->edb->get('employee_time_in AS ee');
        $result = $query->result();
        $query->free_result();
        
        return ($result) ? $result : false;
    }
    
    public function not_in_search_timein($emp_id, $company_id)
    {
        if (is_numeric($company_id))
        {
            
            $level = $this->timein_approval_level($emp_id);
            
            $where = array(
                'ee.comp_id'        => $company_id,
                'ee.status'         => 'Active',
                'ee.corrected'      => 'Yes',
                'ag.emp_id'         => $emp_id,
                'ee.time_in_status' => 'pending'
            );
            $where2 = array(
                "at.level !=" => ""
            );
            
            $select = array(
                'at.flag_add_logs',
                'epi.attendance_adjustment_approval_grp',
                'at.level',
                'ee.employee_time_in_id',
                'epi.location_base_login_approval_grp',
                'epi.attendance_adjustment_approval_grp',
                'epi.add_logs_approval_grp'
            );
            
            $this->edb->select($select);
            $this->edb->where($where);
            $this->db->where($where2);
            $this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
            $this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
            $this->edb->join('accounts AS a','a.account_id = e.account_id','left');
            $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
            $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
            $this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
            $this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
            $this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
            $this->edb->join('approval_time_in AS at',"at.approval_time_in_id= ee.approval_time_in_id","LEFT");
            $this->db->group_by("employee_time_in_id");
            $query = $this->edb->get('employee_time_in AS ee');
            $result = $query->result();
            last_query();
            $arrs = array();
            if($result){
                $is_assigned = TRUE;
                $hours_notification = get_hours_notification_settings($this->company_id);
                foreach($result as $key => $approvers){
                    if($approvers->flag_add_logs == 0){
                        $appr_grp = $approvers->attendance_adjustment_approval_grp;
                    }elseif($approvers->flag_add_logs == 1){
                        $appr_grp = $approvers->add_logs_approval_grp;
                    }elseif($approvers->flag_add_logs == 2){
                        $appr_grp = $approvers->location_base_login_approval_grp;
                    }
                    
                    $level = $approvers->level;
                    
                    $check = $this->check_assigned_hours($appr_grp, $level);
                    
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
    
    public function timein_approval_level($emp_id)
    {
        if (!is_numeric($emp_id)) return false;
        
        $this->db->or_where('ap.name','Timesheet Adjustment');
        $this->db->or_where('ap.name','Mobile Clock-in');
        $this->db->or_where('ap.name','Add Timesheet');
        $this->db->where('ag.emp_id',$emp_id);
        $this->db->select('ag.level AS level');
        $this->db->join('approval_process AS ap','ap.approval_process_id = ag.approval_process_id','LEFT');
        $q = $this->db->get('approval_groups AS ag');
        $r = $q->row_array();
        
        return ($r) ? $r : FALSE;
    }
    
    public function check_assigned_hours($hours_appr_grp, $level)
    {
        $where = array(
            "emp_id" => $this->session->userdata("emp_id"),
            "level " => $level,
            "approval_groups_via_groups_id" => $hours_appr_grp
        );
        $this->db->where($where);
        $query = $this->db->get("approval_groups");
        $row = $query->row();
        
        return ($row) ? true : false;
    }
    
    public function not_in_search_split_timein($emp_id, $company_id)
    {
        if (!$emp_id) return false;
        if (!$company_id) return false;
        
        $level = $this->timein_approval_level($emp_id);
        
        $where = array(
            'sbti.comp_id'   => $company_id,
            'sbti.status'   => 'Active',
            'sbti.corrected' => 'Yes',
            'sbti.time_in_status' => 'pending'
            
        );
        $where2 = array(
            "at.level !=" => ""
        );
        
        $select = array(
            'at.flag_add_logs',
            'epi.attendance_adjustment_approval_grp',
            'at.level',
            'sbti.schedule_blocks_time_in_id',
            'epi.location_base_login_approval_grp',
            'epi.attendance_adjustment_approval_grp',
            'epi.add_logs_approval_grp'
        );
        
        $this->edb->select($select);
        
        $this->edb->where($where);
        $this->db->where($where2);
        $this->edb->join('employee AS e','e.emp_id = sbti.emp_id','left');
        $this->edb->join('employee_payroll_information AS epi','epi.emp_id = sbti.emp_id','left');
        $this->edb->join('accounts AS a','a.account_id = e.account_id','left');
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("approval_time_in AS at","at.approval_time_in_id= sbti.approval_time_in_id","LEFT");
        $this->db->group_by("schedule_blocks_time_in_id");
        $query = $this->edb->get('schedule_blocks_time_in AS sbti');
        $result = $query->result();
        
        $arrs = array();
        if ($result)
        {
            $is_assigned = TRUE;
            $hours_notification = get_hours_notification_settings($this->company_id);
            
            foreach ($result as $key => $approvers)
            {
                if($approvers->flag_add_logs == 0){
                    $appr_grp = $approvers->attendance_adjustment_approval_grp;
                }elseif($approvers->flag_add_logs == 1){
                    $appr_grp = $approvers->add_logs_approval_grp;
                }elseif($approvers->flag_add_logs == 2){
                    $appr_grp = $approvers->location_base_login_approval_grp;
                }
                
                $level = $approvers->level;
                
                $check = $this->check_assigned_hours($appr_grp, $level);
                
                if($check && $is_assigned){
                    
                }else{
                    array_push($arrs, $approvers->schedule_blocks_time_in_id);
                }
            }
        }
        $string = implode(",", $arrs);
        return $string;
    }
    
    public function get_split_timeinlist($emp_id, $company_id) 
    {
        if (!$emp_id) return false;
        if (!$company_id) return false;
        
        $filter = $this->not_in_search_split_timein($emp_id, $company_id);
        
        $where = array(
            'sbti.comp_id'   	=> $company_id,
            'sbti.status'   		=> 'Active',
            'sbti.corrected' 	=> 'Yes',
            "ag.emp_id" 			=> $emp_id,
            'sbti.time_in_status'=> 'pending'
            
        );
        $where2 = array(
            "at.level !=" => ""
        );
        
        $select = array(
            'sbti.schedule_blocks_time_in_id',
            'sbti.employee_time_in_id',
            'sbti.work_schedule_id',
            'sbti.split_schedule_id',
            'sbti.emp_id',
            'sbti.comp_id',
            'sbti.date',
            'sbti.time_in',
            'sbti.lunch_out',
            'sbti.lunch_in',
            'sbti.time_out',
            'sbti.total_hours',
            'sbti.total_hours_required',
            'sbti.reason',
            'sbti.time_in_status',
            'sbti.tardiness_min',
            'sbti.undertime_min',
            'sbti.notes',
            'sbti.source',
            'sbti.change_log_date_filed',
            'sbti.change_log_time_in',
            'sbti.change_log_lunch_out',
            'sbti.change_log_lunch_in',
            'sbti.change_log_time_out',
            'sbti.change_log_total_hours',
            'sbti.change_log_total_hours_required',
            'sbti.change_log_tardiness_min',
            'sbti.change_log_undertime_min',
            'sbti.location_1',
            'sbti.location_2',
            'sbti.location_3',
            'sbti.location_4',
            'sbti.location',
            'd.department_name',            
            'at.level'
        );
        
        $edb_select = array(
            'a.payroll_cloud_id',
            'a.profile_image',
            'e.last_name',
            'e.first_name',
            'e.middle_name'
        );
        
        $this->db->select($select);
        $this->edb->select($edb_select);
        
        $this->edb->where($where);
        $this->db->where($where2);
        if($filter != FALSE){
            $this->db->where("sbti.schedule_blocks_time_in_id NOT IN ({$filter})");
        }
        $this->edb->join('employee AS e','e.emp_id = sbti.emp_id','left');
        $this->edb->join('employee_payroll_information AS epi','epi.emp_id = sbti.emp_id','left');
        $this->edb->join('accounts AS a','a.account_id = e.account_id','left');
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("approval_time_in AS at","at.approval_time_in_id= sbti.approval_time_in_id","LEFT");
        $this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
        $this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
        $this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
        $this->db->join("employee_sched_block AS esb","sbti.schedule_blocks_id = esb.schedule_blocks_id","LEFT");
        $this->db->join("schedule_blocks AS sb","sb.work_schedule_id = esb.work_schedule_id","LEFT");
        $this->edb->join("work_schedule AS ws","sbti.work_schedule_id = ws.work_schedule_id","LEFT");
        $this->db->group_by("schedule_blocks_time_in_id");
        
        $query = $this->edb->get('schedule_blocks_time_in AS sbti');
        $result = $query->result();
        $query->free_result();
        last_query();
        return ($result) ? $result : false;
    }
    
    public function get_all_employees($company_id)
    {
        if (!$company_id) return false;
        
        $where = array(
            "e.company_id" =>$company_id,
            "e.status" => "Active",
            "a.user_type_id" => 5,
            "a.deleted" => "0"
        );
        $this->edb->where($where);
        $this->edb->join('accounts AS a','a.account_id = e.account_id','left');
        $query = $this->edb->get('employee AS e');
        $result = $query->result();
        return ($result) ? $result : false;
    }
        
    public function work_schedule_type($work_schedule_id, $comp_id)
    {
        $w = array(
            "work_schedule_id" => $work_schedule_id,
            "comp_id"=> $comp_id
        );
        $this->db->where($w);
        $q = $this->db->get('work_schedule');
        $r = $q->row();
        
        return ($r) ? $r->work_type_name : FALSE;
    }
    
}