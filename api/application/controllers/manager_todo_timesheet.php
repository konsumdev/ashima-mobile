<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Manager Todo Timesheet
 * Controller for manager timesheet approval
 * @category controller
 * @version 1.0
 * @author 47
 */
class Manager_todo_timesheet extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        $this->load->model('employee_model','employee');
        $this->load->model('manager_todo_timesheet_model','mttm');
        
        $this->company_info = whose_company();
        $this->emp_id       = $this->session->userdata('emp_id');
        $this->company_id   = $this->employee->check_company_id($this->emp_id);
        $this->account_id   = $this->session->userdata('account_id');
    }
    
    public function index() 
    {p($this->account_id);
        $viewMonth  = $this->input->post('viewMonth');
        $viewYear   = $this->input->post('viewYear');
        $page       = $this->input->post('page');
        $limit      = $this->input->post('limit');
        $status     = $this->input->post('status');
        
        $this->per_page = 10;
        
        $approval_list = $this->get_approval_list($this->emp_id, $this->company_id);
    }
    
    public function get_approval_list($emp_id, $company_id)
    {
        $list1 = $this->mttm->get_timesheet_approval_lists($emp_id, $company_id);
        $split_list = $this->mttm->get_split_timeinlist($emp_id, $company_id);
        
        /*if( ! $list1) {
            $list1 = array();
        }
        
        if( ! $split_list) {
            $split_list = array();
        }
        
        $merge_timein_list = array_merge($list1, $split_list);
        
        $approve_level = $this->mttm->timein_approval_level($emp_id);
        $return_array = array();
        if ($merge_timein_list)
        {
            $flag = false;
            $is_assigned = true;
            
            foreach ($merge_timein_list as $key=>$approvers)
            {
                $check_work_type = $this->mttm->work_schedule_type($approvers->work_schedule_id, $company_id);
                
                if($approvers->flag_add_logs == 0){
                    $appr_grp = $approvers->attendance_adjustment_approval_grp;
                }elseif($approvers->flag_add_logs == 1){
                    $appr_grp = $approvers->add_logs_approval_grp;
                }elseif($approvers->flag_add_logs == 2){
                    $appr_grp = $approvers->location_base_login_approval_grp;
                }
                $level = $approvers->level;
                $check = $this->mttm->check_assigned_hours($appr_grp, $level);
            }
        } */
    }
}