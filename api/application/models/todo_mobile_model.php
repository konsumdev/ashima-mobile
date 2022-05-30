<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Todo_mobile_model extends CI_Model {
    

    public function mobile_clockin_list($company_id, $emp_id, $search = "")
    {
        if ( ! is_numeric($emp_id)) {
            return false;
        }

        $agvg_ids = $this->get_agvg_of_approver($company_id, $emp_id);
        if (!$agvg_ids) {
            return FALSE;
        }

        $konsum_key = konsum_key();
        $filter = $this->not_in_search_mobile_clockin($emp_id, $company_id);

        $where = array(
            'ee.comp_id'    => $company_id,
            'ee.status'     => 'Active',
            'ee.corrected'  => 'Yes',
            'ee.source'     => 'mobile',
            'at.level !='   => '',
            'ee.time_in_status' => 'pending'
        );

        $select = array(
            'ee.employee_time_in_id',
            'ee.work_schedule_id',
            'e.emp_id',
            'ee.date',
            'ee.time_in_status',
            'ee.mobile_clockin_status',
            'ee.mobile_lunchout_status',
            'ee.mobile_lunchin_status',
            'ee.mobile_clockout_status',
            'e.account_id',
            'a.profile_image',
            'ee.time_in',
            'ee.lunch_out',
            'ee.lunch_in',
            'ee.time_out',
            'ee.location_1',
            'ee.location_2',
            'ee.location_3',
            'ee.location_4',
            'at.level',
            'at.approval_time_in_id',
            'at.flag_add_logs',
            'ee.mobile_break1_out_status',
            'ee.mobile_break1_in_status',
            'ee.mobile_break2_out_status',
            'ee.mobile_break2_in_status',
            'ee.break1_out',
            'ee.break1_in',
            'ee.break2_out',
            'ee.break2_in',
            'ee.location_5',
            'ee.location_6',
            'ee.location_7',
            'ee.location_8',
            'ws.work_type_name'
        );
        $edb_select = array(
            'e.last_name',
            'e.first_name',
            'a.payroll_cloud_id'
        );
        
        $this->db->select($select);
        $this->edb->select($edb_select);
        $this->db->where($where);

        if($filter != FALSE){
            $this->db->where("ee.employee_time_in_id NOT IN ({$filter})");
        }

        $this->db->where_in('location_base_login_approval_grp', $agvg_ids);

        $this->db->join("employee_time_in AS ee", "at.approval_time_in_id = ee.approval_time_in_id", "INNER");
        $this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
        $this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
        $this->edb->join('accounts AS a','a.account_id = e.account_id','left');
        // $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        // $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        // $this->edb->join("approval_groups_via_groups AS agg","epi.location_base_login_approval_grp = agg.approval_groups_via_groups_id","LEFT");
        // $this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
        // $this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
        // $this->edb->join("approval_time_in AS at","at.approval_time_in_id = ee.approval_time_in_id","LEFT");
        $this->edb->join("work_schedule AS ws","ee.work_schedule_id = ws.work_schedule_id","LEFT");
        // $this->db->group_by("employee_time_in_id");
        $this->db->order_by("date","DESC");

        $query = $this->edb->get('approval_time_in AS at');
        $result = $query->result();
        $query->free_result();
        
        return ($result) ? $result : false;
    }

    public function get_agvg_of_approver($company_id, $emp_id)
    {
        $where = array(
            'emp_id' => $emp_id,
            'ag.company_id' => $company_id,
            'name' => 'Mobile Clock-in'
        );
        $this->db->where($where);
        $this->db->join("approval_process AS ap", "ap.approval_process_id = ag.approval_process_id", "INNER");
        $q = $this->db->get('approval_groups AS ag');
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

    public function mobile_split_clockin_list($company_id, $emp_id)
    {
        if ( ! is_numeric($emp_id)) {
            return false;
        }

        $where = array(            
            'comp_id' => $company_id,
            'source' => 'mobile',
            'time_in_status' => 'pending'
        );

        $this->db->where($where);
        $query = $this->db->get('schedule_blocks_time_in');
        $result = $query->result();
        $query->free_result();

        $rtrn = array();
        if ($result) {
            foreach ($result as $row) {
                $t = array(
                    'schedule_blocks_time_in_id' => $row->schedule_blocks_time_in_id,
                    'employee_time_in_id' => $row->employee_time_in_id,
                    'date' => $row->date,
                    'schedule_blocks_id' => $row->schedule_blocks_id,
                    'time_in' => $row->time_in,
                    'lunch_out' => $row->lunch_out,
                    'lunch_in' => $row->lunch_in,
                    'time_out' => $row->time_out,
                    'time_in_status' => $row->time_in_status,
                    'source' => $row->source,
                    'approval_time_in_id' => $row->approval_time_in_id,
                    'location_1' => $row->location_1,
                    'location_2' => $row->location_2,
                    'location_3' => $row->location_3,
                    'location_4' => $row->location_4,
                    'location' => $row->location,
                    'flag_payroll_correction' => $row->flag_payroll_correction,
                    'q' => $row->employee_time_in_id.'-'.$row->approval_time_in_id
                );
                array_push($rtrn, $t);
            }
        }
        
        return ($rtrn) ? $rtrn : false;
    }

    public function not_in_search_mobile_clockin($emp_id, $company_id)
    {
        if ( ! is_numeric($emp_id)) {
            return false;
        }

        $konsum_key = konsum_key();
        $level = $this->timein_approval_level($this->session->userdata('emp_id'));
    
        $where = array(
            'ee.comp_id'        => $company_id,
            'ee.status'         => 'Active',
            'ee.corrected'      => 'Yes',
            'ag.emp_id'         => $emp_id,
            'ee.time_in_status' => 'pending',
            'ee.source'         => 'mobile',
            'at.level !='       => '' 

        );

        $this->db->where($where);
        $this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
        $this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
        $this->edb->join('accounts AS a','a.account_id = e.account_id','left');
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
        $this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
        $this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
        $this->edb->join("approval_time_in AS at","at.approval_time_in_id= ee.approval_time_in_id","LEFT");
        $this->db->group_by("employee_time_in_id");
        $query = $this->edb->get('employee_time_in AS ee');
        $result = $query->result();
        
        $arrs = array();
        if ($result) {
            $is_assigned = TRUE;
            $hours_notification = get_hours_notification_settings($this->company_id);
            foreach ($result as $key => $approvers) {
                
                $appr_grp = $approvers->location_base_login_approval_grp;                    
                $level = $approvers->level;                    
                $check = $this->check_assigned_hours($appr_grp, $level, $emp_id);                
                if ($check && $is_assigned) {                
                } else {
                    array_push($arrs, $approvers->employee_time_in_id);
                }
            }
            
        }
        $string = implode(",", $arrs);
        return $string;
        
    }

    public function timein_approval_level($emp_id)
    {
        if ( ! is_numeric($emp_id)) {
            return false;
        }
        
        $this->db->or_where('ap.name','Mobile Clock-in');        
        $this->db->where('ag.emp_id',$emp_id);
        $this->db->select('ag.level AS level');
        $this->db->join('approval_process AS ap','ap.approval_process_id = ag.approval_process_id','LEFT');
        $q = $this->db->get('approval_groups AS ag');
        $r = $q->row_array();

        return ($r) ? $r : FALSE;
    }

    public function check_assigned_hours($hours_appr_grp, $level, $emp_id)
    {
        $where = array(
            "emp_id" => $emp_id,
            "level " => $level,
            "approval_groups_via_groups_id" => $hours_appr_grp
        );
        $this->db->where($where);
        $query = $this->db->get("approval_groups");
        $row = $query->row();
    
        return ($row) ? true : false;
    }

    public function get_employee_time_in($val)
    {
        if(is_numeric($val)){
                
            $where = array(
                'ee.employee_time_in_id'   => $val
            );
                
            $this->db->where($where);
            $this->edb->join('company AS c','c.company_id = ee.comp_id','left');
            $this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
            $this->edb->join("approval_time_in AS ti","ti.approval_time_in_id = ee.approval_time_in_id","LEFT");
            $this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
            $this->edb->join('accounts AS a','a.account_id = e.account_id','left');
            $this->edb->join('work_schedule AS ws', 'ee.work_schedule_id = ws.work_schedule_id', 'LEFT');
            $query = $this->edb->get('employee_time_in AS ee');
            
            $result = $query->row();
            $query->free_result();
            return $result;
        }else{
            return false;
        }
    }

    /**
     * gets the token of the time in adjustment
     * @param unknown $leave_ids
     * @param unknown $comp_id
     * @param unknown $emp_id
     * @param unknown $appr_timein_id
     * @return string
     */
    public function get_token($leave_ids,$comp_id,$emp_id, $appr_timein_id){
        $w = array(
                "time_in_id"=>$leave_ids,
                "comp_id"=>$comp_id,
                "emp_id"=>$emp_id,
                "status"=>"Active",
                "approval_time_in_id"=>$appr_timein_id
        );
        $this->db->where($w);
        $q = $this->db->get("approval_time_in");
        $row = $q->row();
        return ($q->num_rows() > 0) ? $row->token : "" ;
    }

}
