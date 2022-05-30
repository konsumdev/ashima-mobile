<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Manager Employee Directory Model
 * Model for manager employee directory
 * @category model
 * @version 1.0
 * @author 47
 */
class Manager_employee_directory_model extends CI_Model
{
    public function get_active_eployees_lite($company_id, $parent_emp_id) {
        $this->db->_protect_identifiers=false;
        $select = array(
            'accounts.payroll_cloud_id',
            'employee.last_name',
            'employee.first_name',
            'employee.emp_id',
            'accounts.profile_image'
        );
        $where = array(
            'employee.company_id' => $company_id,
            'employee.status'	  => 'Active',
            'accounts.user_type_id' => '5',
            'accounts.deleted'=>'0',
            'employee.status'=>'Active',
            'employee.deleted'=>'0',
            'employee_payroll_information.employee_status' => 'Active',
            'edrt.parent_emp_id' => $parent_emp_id
        );

        $konsum_key = konsum_key();
        $this->edb->select($select);
        $this->db->where($where);
        
        $this->edb->join('accounts','accounts.account_id = employee.account_id','INNER');
        $this->db->join('employee_details_reports_to AS edrt', 'edrt.emp_id = employee.emp_id', 'LEFT');
        $this->edb->join('employee_payroll_information','employee_payroll_information.emp_id = employee.emp_id','left');        
        $this->db->order_by('CONVERT(UPPER(AES_DECRYPT(employee.last_name, "'.$konsum_key.'")) using latin1)', 'ASC');

        $q = $this->edb->get('employee');
        $result = $q->result();
        return $result;

    }

    public function get_active_employee_details($emp_id, $comp_id) {
        $select = array(
            'accounts.payroll_cloud_id',
            'employee.last_name',
            'employee.first_name',
            'employee.emp_id',
            'employee.account_id',
            'accounts.profile_image',
            'accounts.email',
            'accounts.login_mobile_number',
            'position.position_name',
            'employee.address',
            'accounts.login_mobile_number_2',
            'accounts.telephone_number',
            'employee.middle_name',
            'employee.nickname',
            'employee.dob',
            'employee.gender',
            'employee.marital_status',
            'employee.citizenship_status',
            'employee_payroll_information.employee_status',
            'rank.rank_name',
            'department.department_name',
            'cost_center.cost_center_code',
            'location_and_offices.name',
            'employee_payroll_information.date_hired',
            'employee_payroll_information.payroll_group_id'
        );
        
        $where = array(
            'employee.company_id' => $comp_id,
            'employee.status' => 'Active',
            'accounts.user_type_id' => '5',
            'accounts.deleted'=>'0',
            'employee.status'=>'Active',
            'employee.deleted'=>'0',
            'employee_payroll_information.employee_status' => 'Active',
            'employee.emp_id' => $emp_id
        );

        $konsum_key = konsum_key();
        $this->edb->select($select);
        $this->db->where($where);
        
        $this->edb->join('accounts','accounts.account_id = employee.account_id','INNER');
        // $this->db->join('employee_details_reports_to AS edrt', 'edrt.emp_id = employee.emp_id', 'LEFT');
        $this->edb->join('employee_payroll_information','employee_payroll_information.emp_id = employee.emp_id','left');
        $this->edb->join('department','department.dept_id = employee_payroll_information.department_id','left');
        $this->edb->join('position', 'position.position_id = employee_payroll_information.position', 'left');
        $this->edb->join('rank', 'rank.rank_id = employee_payroll_information.rank_id', 'left');
        $this->edb->join('cost_center', 'cost_center.cost_center_id = employee_payroll_information.cost_center', 'left');
        $this->edb->join('location_and_offices', 'location_and_offices.location_and_offices_id = employee_payroll_information.location_and_offices_id', 'left');
        
        $q = $this->edb->get('employee');
        $result = $q->row();
        return $result;
    }
    
    public function get_active_eployees($company_id, $parent_emp_id) 
    {
        $this->db->_protect_identifiers=false;
        $select = array(
            'accounts.payroll_cloud_id',
            'employee.last_name',
            'employee.first_name',
            'employee.emp_id',
            'employee.account_id',
            'accounts.profile_image',
            'accounts.email',
            'accounts.login_mobile_number',
            'position.position_name',
            'employee.address',
            'accounts.login_mobile_number_2',
            'accounts.telephone_number',
            'employee.middle_name',
            'employee.nickname',
            'employee.dob',
            'employee.gender',
            'employee.marital_status',
            'employee.citizenship_status',
            'employee_payroll_information.employee_status',
            'rank.rank_name',
            'department.department_name',
            'cost_center.cost_center_code',
            'location_and_offices.name',
            'employee_payroll_information.date_hired',
            'employee_payroll_information.payroll_group_id'
        );
        
        $where = array(
            'employee.company_id' => $company_id,
            'employee.status'	  => 'Active',
            'accounts.user_type_id' => '5',
            'accounts.deleted'=>'0',
            'employee.status'=>'Active',
            'employee.deleted'=>'0',
            'employee_payroll_information.employee_status' => 'Active',
            'edrt.parent_emp_id' => $parent_emp_id
        );
        
        $konsum_key = konsum_key();
        $this->edb->select($select);
        $this->db->where($where);
        
        $this->edb->join('accounts','accounts.account_id = employee.account_id','INNER');
        $this->db->join('employee_details_reports_to AS edrt', 'edrt.emp_id = employee.emp_id', 'LEFT');
        $this->edb->join('employee_payroll_information','employee_payroll_information.emp_id = employee.emp_id','left');
        $this->edb->join('department','department.dept_id = employee_payroll_information.department_id','left');
        $this->edb->join('position', 'position.position_id = employee_payroll_information.position', 'left');
        $this->edb->join('rank', 'rank.rank_id = employee_payroll_information.rank_id', 'left');
        $this->edb->join('cost_center', 'cost_center.cost_center_id = employee_payroll_information.cost_center', 'left');
        $this->edb->join('location_and_offices', 'location_and_offices.location_and_offices_id = employee_payroll_information.location_and_offices_id', 'left');
        
        $this->db->order_by('CONVERT(UPPER(AES_DECRYPT(employee.last_name, "'.$konsum_key.'")) using latin1)', 'ASC');
//         $this->edb->order_by('employee.last_name', 'asc', false);
        $q = $this->edb->get('employee');
        $result = $q->result();
        return $result;
    }
    
    /* Employee Shifts Part */
    
    public function employee_assigned_work_schedule($company_id, $year="", $emp_ids=""){
        
        $array_emp_schedule = array();
        
        if($year == false) {
            $year = date("Y");
        }
        
        $lastYear = date("Y", strtotime($year." -1 year" ));
        $NextYear =  date("Y", strtotime($year." +1 year"));
        
        $sel = array(
            "ess.emp_id",
            "ess.shifts_schedule_id",
            "ess.work_schedule_id",
            "ws.bg_color",
            "ess.valid_from",
            "ess.until",
            "ws.flag_custom",
            "ws.work_type_name",
            "ws.name",
            "ws.category_id"
        );
        $this->db->select($sel);
        
        $w = array(
            "ess.company_id" => $company_id,
            "ess.status" => "Active",
            "ess.payroll_group_id" => "0",
            "DATE_FORMAT(ess.valid_from,'%Y') >=" => $lastYear,
            "DATE_FORMAT(ess.until,'%Y') <=" => $NextYear
        );
        $this->db->where($w);

        if ($emp_ids) {
            $this->db->where_in('ess.emp_id', $emp_ids);
        }
        
        $this->db->join("work_schedule AS ws","ws.work_schedule_id = ess.work_schedule_id","LEFT");
        $q = $this->db->get("employee_shifts_schedule AS ess");
        $result = $q->result();
        $q->free_result();
        if($result) {
            foreach ($result as $row) {
                $w = array(
                    "shifts_emp_id" => $row->emp_id,
                    "work_schedule_id" => $row->work_schedule_id,
                    "work_shift_id" => $row->shifts_schedule_id,
                    "flag_custom" => $row->flag_custom,
                    "shift_name" => $row->name,
                    "shift_type" => $row->work_type_name,
                    "bg_color" => $row->bg_color,
                    "category_id" => $row->category_id,
                    "valid_from" => $row->valid_from,
                    "custom_search" => "{$row->emp_id}{$row->valid_from}"
                );
                array_push($array_emp_schedule, $w);
            }
        }
        return $array_emp_schedule;
    }
    public function employee_work_schedule_via_payroll_group($company_id, $emp_ids=''){
        
        $default_sched = array();
        $sel = array(
            "epi.emp_id",
            "ws.work_schedule_id",
            "pg.payroll_group_id",
            "ws.category_id",
            "ws.name",
            "ws.work_type_name"
        );
        $w = array(
            "epi.company_id" => $company_id,
            "ws.comp_id" => $company_id
        );
        
        $this->db->select($sel);
        $this->db->where($w);

        if ($emp_ids) {
            $this->db->where_in('epi.emp_id', $emp_ids);
        }

        //$this->db->where("ws.work_type_name != 'Workshift'");
        $this->db->join("payroll_group AS pg", "pg.payroll_group_id = epi.payroll_group_id", "LEFT");
        $this->db->join("work_schedule AS ws", "ws.work_schedule_id = pg.work_schedule_id", "INNER");
        #$q = $this->db->get("payroll_group AS pg");
        $q = $this->db->get("employee_payroll_information AS epi");
        $result = $q->result();
        $q->free_result();
        
        if($result) {
            foreach ($result as $row) {
                $w = array(
                    "payroll_group_id" => $row->payroll_group_id,
                    "work_schedule_id" => $row->work_schedule_id,
                    "work_schedule_custom" => $row->category_id,
                    "shift_name" => $row->name,
                    "shift_type" => $row->work_type_name,
                    "emp_id" => $row->emp_id,
                    "custom_search" => "{$row->emp_id}{$row->payroll_group_id}",
                );
                array_push($default_sched, $w);
            }
        }
        
        return $default_sched;
    }
    
    public function employee_work_schedule_regular($company_id){
        
        $regular_sched = array();
        
        $sel = array(
            "rs.work_start_time",
            "rs.work_end_time",
            "rs.total_work_hours",
            "rs.break_in_min",
            "rs.work_schedule_id",
            "rs.days_of_work"
        );
        $w = array(
            "rs.status" => "Active",
            "rs.company_id" => $company_id,
        );
        $this->db->select($sel);
        $this->db->where($w);
        $q = $this->db->get("regular_schedule AS rs");
        $result = $q->result();
        $q->free_result();
        if($result) {
            foreach ($result as $row) {
                $wr = array(
                    "work_schedule_id" => $row->work_schedule_id,
                    "days_of_work" => $row->days_of_work,
                    "work_start_time" => $row->work_start_time,
                    "work_end_time" => $row->work_end_time,
                    "total_work_hours" => $row->total_work_hours,
                    "break_in_min" => $row->break_in_min,
                    "custom_search" => "{$row->work_schedule_id}{$row->days_of_work}"
                );
                array_push($regular_sched, $wr);
            }
        }
        
        return $regular_sched;
    }
    
    public function employee_flex_work_schedule($company_id){
        $flex_schedule_array = array();
        $sel = array(
            "fh.total_hours_for_the_day",
            "fh.latest_time_in_allowed",
            "fh.not_required_login",
            "fh.duration_of_lunch_break_per_day",
            "fh.work_schedule_id"
        );
        
        $w = array(
            "fh.company_id" => $company_id
        );
        $this->db->select($sel);
        $this->db->where($w);
        $q = $this->db->get("flexible_hours AS fh");
        $result = $q->result();
        $q->free_result();
        if($result) {
            foreach ($result as $row) {
                $wr = array(
                    
                    "latest_time_in_allowed" => $row->latest_time_in_allowed,
                    "total_hours_for_the_day" => $row->total_hours_for_the_day,
                    "not_required_login" => $row->not_required_login,
                    "duration_of_lunch_break_per_day" => $row->duration_of_lunch_break_per_day,
                    "custom_search" => "{$row->work_schedule_id}"
                );
                array_push($flex_schedule_array, $wr);
            }
        }
        
        return $flex_schedule_array;
        
    }
    
    public function employee_schedule_blocks_v2($comp_id, $emp_ids=''){
        
        $sched_block_array = array();
        
        $w = array(
            "esb.company_id" => $comp_id
        );
        $this->db->where($w);

        if ($emp_ids) {
            $this->db->where_in('esb.emp_id', $emp_ids);
        }

        $this->db->join("schedule_blocks AS sb", "sb.schedule_blocks_id = esb.schedule_blocks_id", "INNER");
        $q = $this->db->get("employee_sched_block AS esb");
        $result = $q->result();
        $q->free_result();
        if($result) {
            
            foreach ($result as $row) {
                
                $wr = array(
                    "emp_id" => $row->emp_id,
                    "work_schedule_id" => $row->work_schedule_id,
                    "valid_from" => $row->valid_from,
                    "total_hours_work_per_block" => $row->total_hours_work_per_block,
                    "block_name" => $row->block_name,
                    "schedule_blocks_id" => $row->schedule_blocks_id,
                    "bg_color" => $row->bg_color,
                    "start_time" => $row->start_time,
                    "end_time" => $row->end_time,
                    "break_in_min" => $row->break_in_min,
                    "custom_search" => "{$row->emp_id}{$row->valid_from}{$row->work_schedule_id}"
                );
                
                array_push($sched_block_array, $wr);
                
            }
            
        }
        return $sched_block_array;
    }
    
    public function work_schedule_details($company_id) {
        
        $work_sched = array();
        
        $wr = array(
            "comp_id" => $company_id,
            "flag_custom" => "0"
        );
        $this->db->where($wr);
        $q = $this->db->get("work_schedule");
        $res = $q->result();
        $q->free_result();
        if($res) {
            foreach ($res as $row) {
                $w = array(
                    "work_schedule_id" => $row->work_schedule_id,
                    "name" => $row->name,
                    "work_type_name" => $row->work_type_name,
                    "custom_search" => "{$row->work_schedule_id}"
                );
                array_push($work_sched, $w);
            }
        }
        return $work_sched;
        
    }
    
    public function get_rest_day($company_id,$work_schedule_id,$weekday)
    {
        $where = array(
            'company_id' 	   => $company_id,
            "work_schedule_id" =>$work_schedule_id,
            'rest_day' 		   => $weekday,
            'status'		   => 'Active'
        );
        $this->db->where($where);
        $q = $this->db->get('rest_day');
        $result = $q->row();
        
        return ($result) ? $result : false;
    }
}