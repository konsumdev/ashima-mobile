<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed');

class Todo extends CI_Controller
{

    var $verify;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('download');

        $this->load->model("approve_timeins_model", "timeins");
        $this->load->model('konsumglobal_jmodel', 'jmodel');
        $this->load->model('employee_model', 'employee');
        $this->load->model('approval_model', 'approval');
        $this->load->model('approval_group_model', 'agm');
        $this->load->model('todo_leave_model', 'todo_leave');
        $this->load->model('approve_leave_model', 'leave');
        $this->load->model('todo_overtime_model', 'todo_ot');
        $this->load->model('todo_timein_model', 'todo_timein');
        $this->load->model('todo_shifts_model', 'todo_shifts');
        $this->load->model("approve_overtime_model", "overtime");
        $this->load->model("employee_work_schedule_model", "ews");
        $this->load->model("approve_shift_model", "shifts");
        $this->load->model('emp_login_model', 'elm');
        $this->load->model('todo_mobile_model', 'todo_m');
        $this->load->model('employee_v2_model','employee_v2');
        
        // $this->company_info = whose_company();
        $this->account_id = $this->session->userdata("account_id");
        
        $this->emp_id = $this->session->userdata('emp_id');
        $this->company_id = $this->employee->check_company_id($this->emp_id);
        
        $this->psa_id = $this->session->userdata('psa_id');
    }

    public function index()
    {
        $this->edb->where('e.emp_id', $this->emp_id);
        $this->edb->join('leave_type AS l', 'e.leave_type_id = l.leave_type_id', 'LEFT');
        $q = $this->edb->get('employee_leaves_application AS e');
        $r = $q->result();
        
        echo json_encode($r);
    }

    public function check_locks() {
        $lock_this_timesheet = false;
        $recal_timesheet = false;
        $recal_payroll = false;
        $gen_lock = false;
        // check if the application is lock for filing
        $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id);
        if($get_lock_payroll_process_settings) {
            if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                $lock_this_timesheet = true;
                $recal_timesheet = true;
                $recal_payroll = true;
                $gen_lock = true;
            } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                $lock_this_timesheet = true;
                $recal_timesheet = true;
                $recal_payroll = true;
                $gen_lock = true;
            } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                $lock_this_timesheet = true;
                $recal_timesheet = true;
                $recal_payroll = true;
                $gen_lock = true;
            }
        }

        echo json_encode(array(
            "error" => false,
            "lock_this_timesheet" => $lock_this_timesheet,
            "recal_timesheet" => $recal_timesheet,
            "recal_payroll" => $recal_payroll,
            "is_lock" => $gen_lock,
            "application_error" => "Approval is currently suspended by your admin."
        ));
        return false;
    }

    public function check_todo_approver()
    {
        $approver = check_if_approver($this->session->userdata('emp_id'));
        $time_in = check_if_approver($this->session->userdata('emp_id'), 'timein');
        $overtime = check_if_approver($this->session->userdata('emp_id'), 'overtime');
        $leave = check_if_approver($this->session->userdata('emp_id'), 'leave');
        
        $array = array(
            'approver' => ($approver) ? "1" : "0",
            'timein' => ($time_in) ? "1" : "0",
            'overtime' => ($overtime) ? "1" : "0",
            'leave' => ($leave) ? "1" : "0"
        );
        echo json_encode($array);
    }

    public function todo_timein_list()
    {
        $search = $this->input->post('search');
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        $this->per_page = 10;
        $temp = $this->todo_timein->timein_list_new($this->emp_id, $this->company_id, $search);      
        $split = false; //$this->todo_timein->split_timein_list1($this->emp_id, $this->company_id, $search);
        
        if($temp == false) {
			$temp = array();
		}

		if($split == false) {
			$split = array();
        }
        
        $temp = $this->trim_timein_list($temp, false);
        $split = $this->trim_timein_list($split, true);
        
        $merge = array_merge($temp, $split);

        $new_page = ($page - 1) * $this->per_page;
        if ($new_page < 0) {
            $new_page = 0;
        }

        $total_pages = count($merge);
        
        if ($merge) {
            $test1 = array_slice($merge, $new_page, $this->per_page);
            $result = array(
                "result" => 1,
                "page" => $page,
                "total" => ($total_pages) ? ceil($total_pages/10) : 1,
                "list" => $test1
            );
            echo json_encode($result);
        } else {
            echo json_encode(array());
        }
        return false;
    }

    public function late_timein_lists()
    {
        $search = $this->input->post('search');

        $temp = $this->todo_timein->timein_late($this->emp_id, $this->company_id, $search);
        $split = $this->todo_timein->split_timein_late($this->emp_id, $this->company_id, $search);

        $temp = $this->trim_timein_list($temp, false);
        $split = $this->trim_timein_list($split, true);
        
        $merge = array_merge($temp, $split);
        
        if ($merge) {
            echo json_encode($merge);
        } else {
            echo json_encode(array());            
        }
        return false;
    }

    public function trim_timein_list($param, $is_split = false)
    {
        $return_arr = array();
        if ($param) {
            
            $is_migrated = for_list_tardiness_rule_migrated_v3($this->company_id);
            foreach ($param as $row) {

                $void = $this->employee_v2->check_payroll_lock_closed($row->emp_id,$row->comp_id,$row->date);

                $payroll_closed = false;
                if ($void == "Closed" && $approvers->for_resend_auto_rejected_id == null) {
                    $payroll_closed = true;
                }

                $temp = array(
                    'change_log_date_filed' => $row->change_log_date_filed,
                    // 'base_url' => 'http://payrollv3.konsum.local/',
                    'base_url' => base_url(),
                    'first_name' => $row->first_name,
                    'last_name' => $row->last_name,
                    'full_name' => $row->first_name . ' ' . $row->last_name,
                    'payroll_cloud_id' => $row->payroll_cloud_id,
                    'time_in_status' => $row->time_in_status,
                    'date' => $row->date,
                    'time_in' => ($row->source == 'EP') ? '' : $row->time_in,
                    'location_1' => $row->location_1,
                    'lunch_out' => ($row->source == 'EP') ? '' : $row->lunch_out,
                    'location_2' => $row->location_2,
                    'lunch_in' => ($row->source == 'EP') ? '' : $row->lunch_in,
                    'location_3' => $row->location_3,
                    'time_out' => ($row->source == 'EP') ? '' : $row->time_out,
                    'location_4' => $row->location_4,
                    'flag_add_logs' => $row->flag_add_logs,
                    'change_log_time_in' => $row->change_log_time_in,
                    'change_log_lunch_out' => $row->change_log_lunch_out,
                    'change_log_lunch_in' => $row->change_log_lunch_in,
                    'change_log_time_out' => $row->change_log_time_out,
                    'reason' => $row->reason,
                    //'profile_image' => $row->profile_image,
                    'company_id' => $this->company_id,
                    'source' => $row->source,
                    'employee_time_in_id' => $row->employee_time_in_id,
                    'is_split' => ($is_split) ? 1 : 0,
                    'schedule_blocks_time_in_id' => ($is_split) ? $row->schedule_blocks_time_in_id : '',
                    'break1_out' => (!$is_split) ? $row->break1_out : '',
                    'break1_in' => (!$is_split) ? $row->break1_in : '',
                    'break2_out' => (!$is_split) ? $row->break2_out : '',
                    'break2_in' => (!$is_split) ? $row->break2_in : '',
                    'change_log_break1_out' => (!$is_split) ? $row->change_log_break1_out : '',
                    'change_log_break1_in' => (!$is_split) ? $row->change_log_break1_in : '',
                    'change_log_break2_out' => (!$is_split) ? $row->change_log_break2_out : '',
                    'change_log_break2_in' => (!$is_split) ? $row->change_log_break2_in : '',
                    'location_5' => $row->location_5,
                    'location_6' => $row->location_6,
                    'location_7' => $row->location_7,
                    'location_8' => $row->location_8,
                    'is_migrated' => $is_migrated,
                    'payroll_closed' => $payroll_closed,
                    'emp_id' => $row->emp_id,
                    'void' => $void,
                    'for_resend_auto_rejected_id' => ($row->for_resend_auto_rejected_id) ? $row->for_resend_auto_rejected_id : "",
                    'profile_image' => $row->profile_image
                );
                array_push($return_arr, $temp);
            }
        }
        
        return $return_arr;
    }

    public function todo_leave_list()
    {
        $search = $this->input->post('search');
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        $this->per_page = 10;
        $temp = $this->todo_leave->leave_list($this->emp_id, $this->company_id);
        #p($temp);
        $temp = $this->trim_todo_leave($temp);
        
        echo json_encode($temp);
    }

    public function fd($files = ''){
        if($files !==''){
            
            $data = file_get_contents(base_url()."uploads/companies/64/".$files); // Read the file's contents
            $name = $files;
            force_download($name, $data);
            return false;
        }else{
            return false;
        }
    }

    public function late_leave_list()
    {
        // @$search = $this->input->post('search');
        $temp = $this->todo_leave->leave_list($this->emp_id, $this->company_id, '', "late");
        
        $temp = $this->trim_todo_leave($temp);
        
        echo json_encode($temp);
    }

    public function trim_todo_leave($param)
    {
        $return_arr = array();
        if (! $param)
            return $return_arr;
        
        foreach ($param as $row) {
            $str_date = date("Y-m-d", strtotime($row->date_start));
            $end_date = date("Y-m-d", strtotime($row->date_end));
            if (strtotime($str_date) == strtotime($end_date)) {
                $date_request = date("M d, Y", strtotime($row->date_start));
            } else {
                $date_request = date("M d", strtotime($row->date_start)) . '-' . date("d, Y", strtotime($row->date_end));
            }

            $attachm = $row->required_file_documents;
            $attachment_loc = array();
            $req = "";
            if ($attachm) {
                $attachm = explode(";", $attachm);
                foreach ($attachm as $akey=>$aval) {
                    $base64_comp_id = base64_encode($this->company_id);
                    $base64_comp_id = str_replace("=", "", $base64_comp_id);
                    $req = $aval."/".$base64_comp_id;                        
                    array_push($attachment_loc, $req);
                }
            }


            $temp = array(
                'employee_leaves_application_id' => $row->employee_leaves_application_id,
                'leave_type_id' => $row->leave_type_id,
                'reasons' => $row->reasons,
                'date_start' => ($row->date_start),
                'date_end' => ($row->date_end),
                //'date_return' => ($row->date_return),
                'date_filed' => ($row->date_filed),
                'date_leave' => $date_request,
                'remaining_c' => $row->remaining_c,
                'leave_credits' => $row->leave_credits,
                'total_leave_requested' => $row->total_leave_requested,
                'leave_type' => $row->leave_type,
                'first_name' => $row->first_name,
                'last_name' => $row->last_name,
                'full_name' => $row->first_name . ' ' . $row->last_name,
                'payroll_cloud_id' => $row->payroll_cloud_id,
                'leave_units' => $row->leave_units,
                'company_id' => $this->company_id,
                'profile_image' => $row->profile_image,
                'base_url' => base_url(),
                'leave_application_status' => $row->leave_application_status,
                "required_file_documents" => $row->required_file_documents,
                'attachment' => $attachment_loc
            );
            array_push($return_arr, $temp);
        }
        
        return $return_arr;
    }

    public function todo_overtime_list()
    {
        // @$search = $this->input->post('search');
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        $this->per_page = 10;
        $temp = $this->todo_ot->overtime_list($this->company_id, $this->emp_id, $this->per_page, (($page-1) * $this->per_page));
        
        $temp = $this->trim_overtime_list($temp);
        
        $new_page = ($page - 1) * $this->per_page;
        if ($new_page < 0) {
            $new_page = 0;
        }
        
        if ($temp) {
            $test1 = array_slice($temp, $new_page, $this->per_page);
            echo json_encode($test1);
        } else {
            echo json_encode(array());
        }

        /* if ($temp) {
            echo json_encode($temp);
        } else {
            echo json_encode(array());
            return false;
        } */
        return false;
    }

    public function late_overtime_list()
    {
        $temp = $this->todo_ot->overtime_list($this->company_id, $this->emp_id);
        
        $temp = $this->trim_overtime_list($temp);
        
        if ($temp) {
            echo json_encode($temp);
        } else {
            echo json_encode(array());
            return false;
        }
    }

    public function trim_overtime_list($param)
    {
        $return_arr = array();
        if (! $param)
            return false;
        
        foreach ($param as $row) {

            $payroll_closed = false;
            $void = $this->employee_v2->check_payroll_lock_closed($row->emp_id,$row->company_id,$row->overtime_from);

            if ($void == "Closed" && $row->for_resend_auto_rejected_id == null) {
                $payroll_closed = true;
            }

            $temp = array(
                'overtime_date_applied' => $row->overtime_date_applied,
                'base_url' => base_url(),
                'overtime_id' => $row->overtime_id,
                'company_id' => $this->company_id,
                'profile_image' => $row->profile_image,
                'first_name' => $row->first_name,
                'last_name' => $row->last_name,
                'full_name' => $row->first_name . ' ' . $row->last_name,
                'payroll_cloud_id' => $row->payroll_cloud_id,
                'no_of_hours' => $row->no_of_hours,
                'overtime_from' => $row->overtime_from,
                'overtime_to' => $row->overtime_to,
                'start_time' => $row->start_time,
                'end_time' => $row->end_time,
                'reason' => $row->reason,
                'overtime_status' => $row->overtime_status,
                'void' => $void,
                'payroll_closed' => $payroll_closed
            );
            array_push($return_arr, $temp);
        }
        
        return $return_arr;
    }

    public function todo_shifts_list()
    {
        $search = $this->input->post('search');
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        $this->per_page = 10;
        
        $temp = $this->todo_shifts->shifts_list($this->company_id, $this->emp_id);
         #p($temp);exit;
        $main_array = array();
        if ($temp) {
            
            foreach ($temp as $t) {
                $temp1 = get_schedule($t->date_to, $t->emp_id, $this->company_id);
                $temp2 = get_schedule($t->date_to, $t->emp_id, $this->company_id);
                
                $check_payroll_lock_closed = $this->employee_v2->check_payroll_lock_closed($t->emp_id,$this->company_id,date("Y-m-d",strtotime($t->date_from)));
                $for_lock = false;
                if($check_payroll_lock_closed == "Waiting for approval" || $check_payroll_lock_closed == "Closed"){
                    $for_lock = true;
                }
                $new_array = array(
                    'employee_work_schedule_application_id' => $t->employee_work_schedule_application_id,
                    'last_name' => $t->last_name,
                    'first_name' => $t->first_name,
                    'date_from' => $t->date_from,
                    'date_to' => $t->date_to,
                    'start_time' => $t->start_time,
                    'end_time' => $t->end_time,
                    'date_filed' => ($t->date_filed) ? date("Y-m-d", strtotime($t->date_filed)) : '',
                    'orig_start_time' => date('h:i A', strtotime($temp1['start_time'])),
                    'orig_end_time' => date('h:i A', strtotime($temp1['end_time'])),
                    'shift_name' => $temp1['shift_name'],
                    'reason' => $t->reason,
                    'full_name' => $t->first_name . ' ' . $t->last_name,
                    'base_url' => base_url(),
                    'company_id' => $this->company_id,
                    'payroll_cloud_id' => $t->payroll_cloud_id,
                    'profile_image' => $t->profile_image,
                    'employee_work_schedule_status' => $t->employee_work_schedule_status,
                    'new_shift_name' => $t->name,
                    'check_payroll_lock_closed' => $for_lock
                );
                
                array_push($main_array, $new_array);
            }
            echo json_encode($main_array);
        } else {
            echo json_encode($main_array);
        }
    }

    public function todo_mobile_list()
    {
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        $this->per_page = 10;
        $temp = $this->todo_m->mobile_clockin_list($this->company_id, $this->emp_id, $this->per_page, (($page-1) * $this->per_page));
        // last_query();
        
        $split = $this->todo_m->mobile_split_clockin_list($this->company_id, $this->emp_id);
        // p($temp);

        if ( ! $temp) {
            echo json_encode(array());
            return false;
        }

        $list = array();
        foreach ($temp as $row) {

            $hold = array(
                'company_id' => $this->company_id,
                'employee_time_in_id' => $row->employee_time_in_id,
                'work_schedule_id' => $row->work_schedule_id,
                'emp_id' => $row->emp_id,
                'date' => $row->date,
                'time_in_status' => $row->time_in_status,
                'mobile_clockin_status' => $row->mobile_clockin_status,
                'mobile_lunchout_status' => $row->mobile_lunchout_status,
                'mobile_lunchin_status' => $row->mobile_lunchin_status,
                'mobile_clockout_status' => $row->mobile_clockout_status,
                'mobile_break1_out_status' => $row->mobile_break1_out_status,
                'mobile_break1_in_status' => $row->mobile_break1_in_status,
                'mobile_break2_out_status' => $row->mobile_break2_out_status,
                'mobile_break2_in_status' => $row->mobile_break2_in_status,                
                'account_id' => $row->account_id,
                'profile_image' => $row->profile_image,
                'time_in' => $row->time_in,
                'lunch_out' => $row->lunch_out,
                'lunch_in' => $row->lunch_in,
                'time_out' => $row->time_out,
                'break1_out' => $row->break1_out,
                'break1_in' => $row->break1_in,
                'break2_out' => $row->break2_out,
                'break2_in' => $row->break2_in,
                'last_name' => $row->last_name,
                'first_name' => $row->first_name,
                'payroll_cloud_id' => $row->payroll_cloud_id,
                'base_url' => base_url(),
                'full_name' => $row->first_name . ' ' . $row->last_name,
                'location_1' => $row->location_1,
                'location_2' => $row->location_2,
                'location_3' => $row->location_3,
                'location_4' => $row->location_4,
                'location_5' => $row->location_5,
                'location_6' => $row->location_6,
                'location_7' => $row->location_7,
                'location_8' => $row->location_8,
                'level' => $row->level,
                'flag_add_logs' => $row->flag_add_logs,
                'approval_time_in_id' => $row->approval_time_in_id,
                'work_type_name' => $row->work_type_name
            );

            if ($row->work_type_name == 'Workshift') {
                if ($split) {
                    $this_split = in_array_custom("{$row->employee_time_in_id}-{$row->approval_time_in_id}", $split);
                    if ($this_split) {
                        $hold['date'] = $this_split->date;
                        $hold['time_in'] = $this_split->time_in;
                        $hold['lunch_out'] = $this_split->lunch_out;
                        $hold['lunch_in'] = $this_split->lunch_in;
                        $hold['time_out'] = $this_split->time_out;
                    }
                }
            }

            if ($row->mobile_clockin_status == 'pending') {
                $hold['clockin_type'] = 'time in';
                array_push($list, $hold);
            }
            if ($row->mobile_lunchout_status == 'pending') {
                $hold['clockin_type'] = 'lunch in';
                array_push($list, $hold);
            }
            if ($row->mobile_lunchin_status == 'pending') {
                $hold['clockin_type'] = 'lunch out';
                array_push($list, $hold);
            }
            if ($row->mobile_clockout_status == 'pending') {
                $hold['clockin_type'] = 'time out';
                array_push($list, $hold);
            }
            if ($row->mobile_break1_out_status == 'pending') {
                $hold['clockin_type'] = 'break1 out';
                array_push($list, $hold);
            }
            if ($row->mobile_break1_in_status == 'pending') {
                $hold['clockin_type'] = 'break1 in';
                array_push($list, $hold);
            }
            if ($row->mobile_break2_out_status == 'pending') {
                $hold['clockin_type'] = 'break2 out';
                array_push($list, $hold);
            }
            if ($row->mobile_break2_in_status == 'pending') {
                $hold['clockin_type'] = 'break2 in';
                array_push($list, $hold);
            }
        }

        $new_page = ($page - 1) * $this->per_page;
        
        if ($new_page < 0) {
            $new_page = 0;
        }

        if ($list) {
            $test1 = array_slice($list, $new_page, $this->per_page);
            echo json_encode($list);
        } else {
            echo json_encode(array());
        }        
        return false;
    }

    function mobile_application()
    {
        /**
         * Approve Application
         */
        if ($this->input->post('approve_clockin')) {

            // check if the application is lock for filing
            $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id);
            if($get_lock_payroll_process_settings) {
                if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->approval_error
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->ts_approval_recalculation_err_msg
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->py_approval_recalculation_err_msg
                    ));
                    return false;
                }
            }

            $timesheet_ids = $this->input->post('mobile_id');
            $timesheets_id = array();
            array_push($timesheets_id, $timesheet_ids);

            $clock_type = $this->input->post('clock_type');

            $mobile_clock_status = '';

            if (! $timesheets_id) {
                echo json_encode(array(
                    "success" => false,
                    "error" => "Could not find the clockin application."
                ));
                return true;
            }

            $mobile_clock_status = '';
            if ($clock_type == 'time in') {
                $mobile_clock_status = 'mobile_clockin_status';
            } else if ($clock_type == 'lunch out') {
                $mobile_clock_status = 'mobile_lunchout_status';
            } else if ($clock_type == 'lunch in') {
                $mobile_clock_status = 'mobile_lunchin_status';
            } else if ($clock_type == 'time out') {
                $mobile_clock_status = 'mobile_clockout_status';
            }
             else if ($clock_type == 'break1 out') {
                $mobile_clock_status = 'mobile_break1_out_status';
            }
             else if ($clock_type == 'break1 in') {
                $mobile_clock_status = 'mobile_break1_in_status';
            }
             else if ($clock_type == 'break2 out') {
                $mobile_clock_status = 'mobile_break2_out_status';
            }
             else if ($clock_type == 'break2 in') {
                $mobile_clock_status = 'mobile_break2_in_status';
            }

            // Ge sulod ra nig foreach kay array ang gipasa
            // pero actually isa ra ni ka loop kay isa ra ka ID nga ge sulod ug array ang gipasa
            foreach ($timesheets_id as $key => $val) {
                $emp_timein = $this->todo_m->get_employee_time_in($val);

                $employee_details = get_employee_details_by_empid($emp_timein->emp_id);
                $mobile_status = $mobile_clock_status;

                if ($emp_timein) {
                    $curr_approver = "";
                    $curr_approver_account_id = "";
                    $curr_level = $emp_timein->level;
                    
                    $this->db->where("emp_id",$emp_timein->emp_id);
                    $sql = $this->db->get("employee_payroll_information");
                    $row = $sql->row();
                    if($row){
                        $leave_approval_grp = $row->location_base_login_approval_grp;
                    }
                    // xc
                    $this->psa_id = $this->session->userdata('psa_id');
                    // get workforce notification settings                      
                    $approval_group_notification = get_notify_settings($leave_approval_grp, $this->company_id);
                    $hours_approver = $this->timeins->get_approver_name_hours($emp_timein->emp_id,$emp_timein->comp_id,"2");
                    
                    if($hours_approver){
                        foreach ($hours_approver as $la){
                            if($la->level == $curr_level){
                                $curr_approver = ucwords($la->first_name." ".$la->last_name);
                                $curr_approver_account_id = $la->account_id;
                            }
                        }
                    }

                    // get approver's current level
                    $approver_emp_id = get_approver_emp_id_via_account_id($curr_approver_account_id);                                                        
                    $approver_level = get_current_approver_level($leave_approval_grp,$approver_emp_id,$this->company_id);
                    
                    if ($approval_group_notification && $approver_level == $curr_level) {
                        
                        $last_level = $this->timeins->get_timein_last_hours($emp_timein->emp_id, $emp_timein->company_id,$emp_timein->flag_add_logs);
                                        
                        $emp_name = ucwords($employee_details->first_name." ".$employee_details->last_name);
                        $emp_email = $employee_details->email;
                        $emp_account_id = $employee_details->account_id;
                        
                        ################################ APPROVE STARTS HERE ################################
                        $company_id = $this->company_id;
                        $flaggers = 0;
                        
                        $hours_category="";
                        $workflow_type = "";
                        $flag="";
                        if($emp_timein->flag_add_logs==0){
                            $hours_category = "attendance adjustment";
                            $workflow_type = $hours_category;
                            $flag = 0;
                        }elseif($emp_timein->flag_add_logs==1){
                            $hours_category = "add logs";
                            $workflow_type = "add timesheet";
                            $flag = 1;
                        }elseif($emp_timein->flag_add_logs==2){
                            $hours_category = "location base login";
                            $workflow_type = "mobile clock in";
                            $flag = 2;
                        }
                        $workflow_approved_by_data = array(
                                    
                                'application_id' => $val,
                                'approver_id'   => $approver_emp_id,
                                'workflow_level'=> $curr_level,
                                'workflow_type' => $workflow_type
        
                        );
                        $this->db->insert('workflow_approved_by',$workflow_approved_by_data);
                            
                        $approver_emp_id_check = ($approver_emp_id == "-99{$this->company_id}") ? "" : $approver_emp_id;
                        $time_info = $this->timeins->timeins_info($val,$emp_timein->comp_id);

                        foreach ($hours_approver as $samelvlapr){
                            if($samelvlapr->level == $approver_level && $samelvlapr->emp_id != $approver_emp_id_check){
                                $same_level_name = ucwords($samelvlapr->first_name." ".$samelvlapr->last_name);
                                $same_level_account_id = $samelvlapr->account_id;
                                $same_level_email = $samelvlapr->email;
                                $same_level_emp_id = $samelvlapr->emp_id;
                                    
                                if($samelvlapr->emp_id == ""){
                                    $owner_approver = get_approver_owner_info($this->company_id);
                                    $same_level_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                                    $same_level_account_id = $owner_approver->account_id;
                                    $same_level_email = $owner_approver->email;
                                    $same_level_emp_id = "";
                                }
                        
                                // notify same level approver via email
                                $this->send_hours_notifcation_mobile($val, $this->company_id, $time_info->emp_id, $same_level_email, $same_level_name, $curr_approver, "last", "", "", "",$mobile_status);
                                
                                // notify same level approver via sms
                                if($approval_group_notification->sms_notification == "yes"){
                                    $sms_message =  "A ".$hours_category." application has been approved by {$curr_approver}.";
                                    send_this_sms_global($this->company_id, $same_level_account_id, $sms_message, $this->psa_id, false);
                                }
                        
                                // notify same level approver via message board
                                if($approval_group_notification->message_board_notification == "yes"){
                                    $same_level_appr_notif_message = "A ".$hours_category." application has been approved by {$curr_approver}.";
                                    send_to_message_board($this->psa_id, $same_level_emp_id, $curr_approver_account_id, $this->company_id, $same_level_appr_notif_message, "system");
                                }
                            }
                        }

                        if ($approver_level == $last_level) {
                            // -------------------------------  APPROVE START ------------------------------- //
                            if($mobile_clock_status == 'mobile_clockin_status') {
                                $approve_loc = 'location_1';
                            } elseif($mobile_clock_status == 'mobile_lunchout_status') {
                                $approve_loc = 'location_2';
                            } elseif($mobile_clock_status == 'mobile_lunchin_status') {
                                $approve_loc = 'location_3';
                            } elseif($mobile_clock_status == 'mobile_clockout_status') {
                                $approve_loc = 'location_4';
                            } elseif($mobile_clock_status == 'mobile_break1_out_status') {
                                $approve_loc = 'location_5';
                            } elseif($mobile_clock_status == 'mobile_break1_in_status') {
                                $approve_loc = 'location_6';
                            } elseif($mobile_clock_status == 'mobile_break2_out_status') {
                                $approve_loc = 'location_7';
                            } elseif($mobile_clock_status == 'mobile_break2_in_status') {
                                $approve_loc = 'location_8';
                            } else {
                                $approve_loc = '';
                            }
                            
                            $value1 = array(
                                    "approve_by_hr" => "Yes",
                                    "approve_by_head" => "Yes"
                            );
                            $w1 = array(
                                    "time_in_id" => $val,
                                    "comp_id" => $emp_timein->comp_id,
                                    "approve_by_hr" => "No",
                                    "approve_by_head" => "No",
                                    "approval_time_in_id" => $emp_timein->approval_time_in_id,
                                    'location' => $approve_loc
                            );
                            $this->db->where($w1);
                            
                            $update = $this->db->update("approval_time_in",$value1);

                            $time_info = $this->timeins->timeins_info($val,$emp_timein->comp_id);

                            if ($time_info) {
                                $real_date = ($time_info->date!=NULL) ? date("Y-m-d",strtotime($time_info->date)) : date("Y-m-d");

                                if($flag==0){
                                    $concat_time_in = ($time_info->change_log_time_in!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->change_log_time_in)) : NULL;
                                    $concat_lunch_in =  ($time_info->change_log_lunch_in!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->change_log_lunch_in)) : NULL;
                                    $concat_lunch_out =  ($time_info->change_log_lunch_out!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->change_log_lunch_out)) : NULL;
                                    $concat_time_out =  ($time_info->change_log_time_out!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->change_log_time_out)): NULL;
                                    $hours_cat = "Attendance Adjustment";
                                }elseif($flag==1){
                                    $concat_time_in = ($time_info->time_in!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->time_in)) : NULL;
                                    $concat_lunch_in =  ($time_info->lunch_in!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->lunch_in)) : NULL;
                                    $concat_lunch_out =  ($time_info->lunch_out!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->lunch_out)) : NULL;
                                    $concat_time_out =  ($time_info->time_out!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->time_out)): NULL;
                                    $hours_cat = "Add Logs";
                                }elseif($flag==2){
                                    $concat_time_in = ($time_info->time_in!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->time_in)) : NULL;
                                    $concat_lunch_in =  ($time_info->lunch_in!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->lunch_in)) : NULL;
                                    $concat_lunch_out =  ($time_info->lunch_out!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->lunch_out)) : NULL;
                                    $concat_time_out =  ($time_info->time_out!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->time_out)): NULL;
                                    $hours_cat = "Location Base Login";
                                }
                                
                                $fields = array(
                                        #"time_in_status"           => "approved",
                                        $mobile_clock_status        => "approved",
                                        "corrected"                 => "Yes",
                                        // "time_in"                   => $concat_time_in,
                                        // "lunch_out"                 => $concat_lunch_out,
                                        // "lunch_in"                  => $concat_lunch_in,
                                        // "time_out"                  => $concat_time_out,
                                        // "total_hours"               => $time_info->change_log_total_hours,
                                        // "total_hours_required"      => $time_info->change_log_total_hours_required,
                                        // "tardiness_min"             => $time_info->change_log_tardiness_min,
                                        // "undertime_min"             => $time_info->change_log_undertime_min,
                                        // "date"                      => date("Y-m-d",strtotime($concat_time_in))
                                );
                                $where = array(
                                        "employee_time_in_id"=>$val,
                                        "comp_id"=>$emp_timein->comp_id
                                );
                                $this->timeins->update_field("employee_time_in",$fields,$where);
                                payroll_cronjob_helper('timesheet',date("Y-m-d",strtotime($real_date)),$time_info->emp_id,$time_info->comp_id);

                                $timein_info = $this->agm->timein_information($val);

                                if($timein_info) {
                                    $time_in_status = 'approved';

                                    if ($timein_info->mobile_clockin_status == "pending" || $timein_info->mobile_lunchout_status == "pending" 
                                        || $timein_info->mobile_lunchin_status == "pending" || $timein_info->mobile_clockout_status == "pending"
                                        || $timein_info->mobile_break1_out_status == "pending" || $timein_info->mobile_break1_in_status == "pending"
                                        || $timein_info->mobile_break2_out_status == "pending" || $timein_info->mobile_break2_in_status == "pending"
                                    ) {
                                        $time_in_status = "pending";
                                    }
                                    
                                } else {
                                    $time_in_status = "";
                                }
                                
                                $fields1 = array(
                                        "time_in_status" => $time_in_status,
                                );
                                $where1 = array(
                                        "employee_time_in_id"=>$val,
                                        "comp_id"=>$emp_timein->comp_id
                                );
                                $this->timeins->update_field("employee_time_in",$fields1,$where1);

                                if ($emp_timein->work_type_name == 'Workshift') {
                                    $this->timeins->update_field("schedule_blocks_time_in",$fields1,$where1);
                                }
                                
                                # activity logs
                                iactivity_logs($emp_timein->comp_id,' has approved on employee mobile clock in of ( '.$time_info->last_name.",".$time_info->first_name.')','approval');
                                # end activity logs
                            }

                            ################################ notify staff start ################################
                            if($approval_group_notification->notify_staff == "yes"){ 
                                // notify staff via email
                                
                                $this->send_hours_notifcation_mobile($val, $company_id, $emp_timein->emp_id, $emp_email, $emp_name, $curr_approver, "last", "", "","",$mobile_status);
                                // notify next via sms
                                if($approval_group_notification->sms_notification == "yes"){
                                    $sms_message = "Your ".$hours_category." has been approved by {$curr_approver}.";
                                    send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $this->psa_id, false);
                                }
                                                        
                                // notify staff via message board
                                if($approval_group_notification->message_board_notification == "yes"){
                                    $emp_notif_message = "Your ".$hours_category." has been approved by {$curr_approver}.";
                                    send_to_message_board($this->psa_id, $emp_timein->emp_id, $curr_approver_account_id, $company_id, $emp_notif_message, "system");
                                }
                            }
                            ################################ notify staff end ################################
                        } else {
                            $next_level = $approver_level + 1;
                            $new_token = $this->timeins->generate_leave_level_token($next_level, $val);
                                
                            foreach ($hours_approver as $nextapr){
                                if($nextapr->level == $next_level){
                                    $next_appr_name = ucwords($nextapr->first_name." ".$nextapr->last_name);
                                    $next_appr_account_id = $nextapr->account_id;
                                    $next_appr_email = $nextapr->email;
                                    $next_appr_emp_id = $nextapr->emp_id;
                                        
                                    $token = $this->todo_m->get_token($val, $company_id, $emp_timein->emp_id, $emp_timein->approval_time_in_id);
                                    $url = base_url()."approval/employee_time_in/index/".$token."/".$new_token."/1".$next_appr_emp_id."0";
                                    
                                    // notify next approver via email
                                    $this->send_hours_notifcation_mobile($val, $emp_timein->company_id, $emp_timein->emp_id, $next_appr_email, $next_appr_name, $curr_approver, "Approver", "Yes", $new_token,$next_appr_emp_id,$mobile_status);
                                        
                                    // notify next approver via sms
                                    if($approval_group_notification->sms_notification == "yes"){
                                        $sms_message = "Click {$url} to approve {$emp_name}'s ".$hours_category.".";
                                        send_this_sms_global($this->company_id, $next_appr_account_id, $sms_message, $this->psa_id, false);
                                    }
                                                                        
                                    // notify next approver via message board
                                    if($approval_group_notification->message_board_notification == "yes"){
                                        $next_appr_notif_message = "A ".$hours_category." application has been approved by {$curr_approver} and is now waiting for your approval. Click this link {$url} to approve";
                                        send_to_message_board($this->psa_id, $next_appr_emp_id, $curr_approver_account_id, $company_id, $next_appr_notif_message, "mobile");
                                    }
                                }
                            }

                            ################################ notify staff start ################################
                            if($approval_group_notification->notify_staff == "yes"){
                                // notify staff via email
                                $this->send_hours_notifcation_mobile($val, $company_id, $emp_timein->emp_id, $emp_email, $emp_name.'/'.$next_appr_name, $curr_approver, "", "", "",$mobile_status);
                                // notify next via sms
                                if($approval_group_notification->sms_notification == "yes"){
                                    $sms_message = "Your ".$hours_category." has been approved by {$curr_approver}.";
                                    send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $this->psa_id, false);
                                }
                                                        
                                // notify staff via message board
                                if($approval_group_notification->message_board_notification == "yes"){
                                    $emp_notif_message = "Your ".$hours_category." has been approved by {$curr_approver}.";
                                    send_to_message_board($this->psa_id, $emp_timein->emp_id, $curr_approver_account_id, $company_id, $emp_notif_message, "mobile");
                                }
                                                                                                                    
                            }
                            ################################ notify staff end ################################
                        }
                        ################################ APPROVE ################################
                    }
                } else {
                    echo json_encode(array(
                            "success" => false,
                            "error" => "Invalid Attendance Details"
                    ));
                    return false;
                }
            }

            echo json_encode(array(
                "success" => true,
                "error" => "The mobile clock in has been approved."
            ));
            return true;
        }

        /**
         * Reject Application
         */
        if ($this->input->post('reject_clockin')) {
            $timesheet_ids = $this->input->post('mobile_id');
            $timesheets_id = array();
            array_push($timesheets_id, $timesheet_ids);

            $clock_type = $this->input->post('clock_type');

            $mobile_clock_status = '';

            if (! $timesheets_id) {
                echo json_encode(array(
                    "success" => false,
                    "error" => "Could not find the clockin application."
                ));
                return true;
            }

            if ($clock_type == 'time in') {
                $mobile_clock_status = 'mobile_clockin_status';
            } else if ($clock_type == 'lunch out') {
                $mobile_clock_status = 'mobile_lunchout_status';
            } else if ($clock_type == 'lunch in') {
                $mobile_clock_status = 'mobile_lunchin_status';
            } else if ($clock_type == 'time out') {
                $mobile_clock_status = 'mobile_clockout_status';
            }

            $psa_id = $this->session->userdata('psa_id');

            foreach ($timesheets_id as $key=>$val) {
                $emp_timein = $this->todo_m->get_employee_time_in($val);
                $employee_details = get_employee_details_by_empid($emp_timein->emp_id);
                $mobile_status = $mobile_clock_status;

                if($emp_timein){
                    $curr_approver = "";
                    $curr_approver_account_id = "";
                    $curr_level = $emp_timein->level;

                    $this->db->where("emp_id",$emp_timein->emp_id);
                    $sql = $this->db->get("employee_payroll_information");
                    $row = $sql->row();
                    if($row){
                        $leave_approval_grp = $row->location_base_login_approval_grp;
                    }
                    // xc
                    $this->psa_id = $this->session->userdata('psa_id');
                    // get workforce notification settings
                    $approval_group_notification = get_notify_settings($leave_approval_grp, $this->company_id);
                    $hours_approver = $this->timeins->get_approver_name_hours($emp_timein->emp_id,$emp_timein->comp_id,"2");

                    // get approver's current level
                    $approver_emp_id = get_approver_emp_id_via_account_id($this->account_id);

                    $approver_level = get_current_approver_level($leave_approval_grp ,$approver_emp_id,$this->company_id);

                    $is_emp_timein_pending = $this->check_pending($val,$this->company_id);

                    if($approval_group_notification && $is_emp_timein_pending){ // start [if has approval group notification and leave application is pending]

                        $hours_category="";
                        $workflow_type = "";
                        $flag="";
                        if($emp_timein->flag_add_logs==0){
                            $hours_category = "attendance adjustment";
                            $workflow_type = $hours_category;
                            $flag = 0;
                        }elseif($emp_timein->flag_add_logs==1){
                            $hours_category = "add logs";
                            $workflow_type = "add timesheet";
                            $flag = 1;
                        }elseif($emp_timein->flag_add_logs==2){
                            $hours_category = "location base login";
                            $workflow_type = "mobile clock in";
                            $flag = 2;
                        }

                        $workflow_approved_by_data = array(

                            'application_id' => $val,
                            'approver_id'   => $approver_emp_id,
                            'workflow_level'=> $curr_level,
                            'workflow_type' => $workflow_type

                        );
                        $this->db->insert('workflow_approved_by',$workflow_approved_by_data);

                        $hours_approver = $this->timeins->get_approver_name_hours($emp_timein->emp_id,$emp_timein->comp_id,"2");

                        $emp_name = ucwords($employee_details->first_name." ".$employee_details->last_name);
                        $emp_email = $employee_details->email;
                        $emp_account_id = $employee_details->account_id;

                        if($this->session->userdata("user_type_id") == 2){
                            $owner_approver = get_approver_owner_info($this->company_id);
                            $curr_approver = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                            $curr_approver_account_id = $this->account_id;
                        }
                        else{
                            foreach ($hours_approver as $sa){
                                if($sa->level == $approver_level && $sa->emp_id == $approver_emp_id){
                                    $curr_approver = ucwords($sa->first_name." ".$sa->last_name);
                                    $curr_approver_account_id = $sa->account_id;
                                }
                            }
                        }

                        // REJECT LEAVE CODE HERE!
                        // ------------------------------- Time in REJECT START ------------------------------- //

                        #$timein_information = $this->approval->get_employee_time_in($val, $this->uri->segment(4));
                        $timein_information = $this->timeins->timeins_info($val,$emp_timein->company_id);

                        if($emp_timein->location != null) {
                            if($emp_timein->location == 'location_1') {
                                $for_time_in_field = 'time_in';
                                $my_location = 'location_1';
                                $cl_time_in_field = 'change_log_time_in';
                                $cl_time_in_data = $timein_information->time_in;
                            } elseif ($emp_timein->location == 'location_2') {
                                $for_time_in_field = 'lunch_out';
                                $my_location = 'location_2';
                                $cl_time_in_field = 'change_log_lunch_out';
                                $cl_time_in_data = $timein_information->lunch_out;
                            } elseif ($emp_timein->location == 'location_3') {
                                $for_time_in_field = 'lunch_in';
                                $my_location = 'location_3';
                                $cl_time_in_field = 'change_log_lunch_in';
                                $cl_time_in_data = $timein_information->lunch_in;
                            } elseif ($emp_timein->location == 'location_4') {
                                $for_time_in_field = 'time_out';
                                $my_location = 'location_4';
                                $cl_time_in_field = 'change_log_time_out';
                                $cl_time_in_data = $timein_information->time_out;
                            }
                        }


                        $fields = array(
                            //"note"                        =>$note[$key],
                            "time_in_status"            => "reject",
                            $mobile_clock_status  => "reject",
                            $cl_time_in_field           => $cl_time_in_data
                        );
                        $where = array(
                            "employee_time_in_id" => $val,
                            "comp_id" => $this->company_id
                        );
                        $rejected = $this->timeins->update_field("employee_time_in",$fields,$where);
                        // ------------------------------- Time in REJECT START ------------------------------- //

                        if($rejected){
                            $approver_emp_id_check = ($approver_emp_id == "-99{$this->company_id}") ? "" : $approver_emp_id;

                            foreach ($hours_approver as $nextapr){
                                if($nextapr->level >= $approver_level && $nextapr->emp_id != $approver_emp_id_check){
                                    $next_appr_name = ucwords($nextapr->first_name." ".$nextapr->last_name);
                                    $next_appr_account_id = $nextapr->account_id;
                                    $next_appr_email = $nextapr->email;
                                    $next_appr_emp_id = $nextapr->emp_id;

                                    if($nextapr->emp_id == ""){
                                        $owner_approver = get_approver_owner_info($this->company_id);
                                        $next_appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                                        $next_appr_account_id = $owner_approver->account_id;
                                        $next_appr_email = $owner_approver->email;
                                        $next_appr_emp_id = "";
                                    }

                                    // notify next approver via email
                                    $this->send_hours_reject_notifcation_mobile($val, $this->company_id, $emp_timein->emp_id, $next_appr_email, $next_appr_name, $curr_approver, "", "", "", $mobile_status);
                                    // notify next approver via sms
                                    if($approval_group_notification->sms_notification == "yes"){
                                        #$sms_message = "Your ".$hours_category." application has been rejected by {$curr_approver}";
                                        $sms_message = "".$hours_category." application filed by {$emp_name} has been rejected by {$curr_approver}.";
                                        send_this_sms_global($this->company_id, $next_appr_account_id,$sms_message,$psa_id,false);
                                    }

                                    // notify next approver via twitter
                                    if($approval_group_notification->twitter_notification == "yes"){
                                        $check_twitter_acount = $this->agm->check_twitter_acount($next_appr_account_id);
                                        if($check_twitter_acount){
                                            $message = "Your ".$hours_category." application has been rejected by {$curr_approver}.";
                                            $recipient_account = $check_twitter_acount->twitter;
                                            $this->tweetontwitter($curr_approver_account_id,$message,$recipient_account);
                                        }
                                    }

                                    // notify next approver via facebook
                                    if($approval_group_notification->facebook_notification == "yes"){
                                        // not applicable
                                    }

                                    // notify next approver via message board
                                    if($approval_group_notification->message_board_notification == "yes"){
                                        $next_appr_notif_message = "Your ".$hours_category." application has been rejected by {$curr_approver}.";
                                        send_to_message_board($psa_id, $next_appr_emp_id, $curr_approver_account_id,$emp_timein->company_id, $next_appr_notif_message, "system");
                                    }
                                }
                            }

                            ################################ notify staff start ################################
                            if($approval_group_notification->notify_staff == "yes"){
                                // notify staff via email
                                $this->send_hours_reject_notifcation_mobile($val, $this->company_id, $emp_timein->emp_id, $emp_email, $emp_name, $curr_approver, "last", "", "", $mobile_status);
                                // notify next via sms
                                if($approval_group_notification->sms_notification == "yes"){
                                    $sms_message = "Your {$hours_category} application has been rejected by {$curr_approver}.";
                                    send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                }

                                // notify staff via twitter
                                if($approval_group_notification->twitter_notification == "yes"){
                                    $check_twitter_acount = $this->agm->check_twitter_acount($emp_account_id);
                                    if($check_twitter_acount){
                                        $message = "Your {$hours_category} application has been rejected by {$curr_approver}.";
                                        $recipient_account = $check_twitter_acount->twitter;
                                        $this->tweetontwitter($curr_approver_account_id,$message,$recipient_account);
                                    }
                                }

                                // notify staff via facebook
                                if($approval_group_notification->facebook_notification == "yes"){
                                    // not applicable
                                }

                                // notify staff via message board
                                if($approval_group_notification->message_board_notification == "yes"){
                                    $emp_notif_message = "Your {$hours_category} application has been rejected by {$curr_approver}.";
                                    send_to_message_board($psa_id, $emp_timein->emp_id, $curr_approver_account_id, $this->company_id, $emp_notif_message, "system");
                                }
                            }
                            ################################ notify staff end ################################
                        }
                    }// end [if has approval group notification and leave application is pending]
                }else{
                    echo json_encode(array(
                        "success" => false,
                        "error" => "Invalid Attendance Details"
                    ));
                    return false;
                }
            }

            echo json_encode(array(
                "success" => true,
                "error" => "The mobile clock in has been rejected."
            ));
            return true;
        }
    }

    function get_hours_notification_settings($company_id)
    {
        $CI = & get_instance();
        if (is_numeric($company_id)) {
            $where = array(
                // 'psa_id' => $CI->session->userdata("psa_id"),
                'hns.company_id' => $company_id,
                'hns.via' => 'default',
                'hns.status' => 'Active'
            );
            $CI->edb->join('hours_alerts_notification AS han', 'hns.hours_alerts_notification_id = han.hours_alerts_notification_id', 'LEFT');
            $CI->edb->where($where);
            $query = $CI->edb->get('hours_notification_settings AS hns');
            $row = $query->row();
            return ($row) ? $row : FALSE;
        }
    }

    function get_hours_alert_staff($company_id)
    {
        $CI = & get_instance();
        if (is_numeric($company_id)) {
            $where = array(
                // 'psa_id' => $CI->session->userdata("psa_id"),
                'hns.company_id' => $company_id,
                'hns.via' => 'notify staff after application',
                'hns.status' => 'Active'
            );
            $CI->edb->join('hours_alerts_notification AS han', 'hns.hours_alerts_notification_id = han.hours_alerts_notification_id', 'LEFT');
            $CI->edb->where($where);
            $query = $CI->edb->get('hours_notification_settings AS hns');
            $row = $query->row();
            return $row;
        }
    }

    public function approve_timein()
    {
        if ($this->input->post('approve_timein')) {

            // check if the application is lock for filing
            $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id);
            if($get_lock_payroll_process_settings) {
                if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->approval_error
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->ts_approval_recalculation_err_msg
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->py_approval_recalculation_err_msg
                    ));
                    return false;               

                }
            }
            
            $timesheet_ids = $this->input->post('employee_time_in_id');
            $is_split = $this->input->post('is_split');
            $is_split = ($is_split == "false") ? "" : $is_split;
            $is_split = ($is_split == "undefined") ? "" : $is_split;
            $timesheets_id = array();
            array_push($timesheets_id, $timesheet_ids); 
            if (! $is_split) {
                
                foreach ($timesheets_id as $key => $val) {
                    // $val = $timesheets_id;
                    
                    $emp_timein       = $this->todo_timein->get_employee_time_in($val);
                    $void_v2          = $this->employee_v2->check_payroll_lock_closed($emp_timein->emp_id,$emp_timein->company_id,date("Y-m-d", strtotime($emp_timein->date)));
                    $employee_details = get_employee_details_by_empid($emp_timein->emp_id);

                    // for new todo structure param
                    $check_holiday = $this->employee->get_holiday_date($emp_timein->date,$emp_timein->emp_id,$emp_timein->company_id);
                    if ($check_holiday) {
                        $check_if_holiday_approval = holiday_approval_settings($this->company_id);
                    } else {
                        $check_if_holiday_approval = false;
                    }

                    $check_if_enable_working_on_restday = check_if_enable_working_on_restday($emp_timein->company_id,$emp_timein->work_schedule_id);

                    if ($check_if_enable_working_on_restday || $emp_timein->work_schedule_id == "-1") {
                        $module_for_new_todo = "rd_ra";
                    } elseif ($check_if_holiday_approval) {
                        $module_for_new_todo = "holiday";
                    } else {
                        $module_for_new_todo = "hours";
                    }
                    
                    if ($emp_timein) { // start [if leave information]
                        $curr_approver = "";
                        $curr_approver_account_id = "";
                        $curr_level = $emp_timein->level;
                        
                        $this->db->where("emp_id", $emp_timein->emp_id);
                        $sql = $this->db->get("employee_payroll_information");
                        $row = $sql->row();
                        if ($row) {
                            if ($emp_timein->flag_add_logs == 0)
                                $leave_approval_grp = $row->attendance_adjustment_approval_grp;
                            elseif ($emp_timein->flag_add_logs == 1)
                                $leave_approval_grp = $row->add_logs_approval_grp;
                            elseif ($emp_timein->flag_add_logs == 2)
                                $leave_approval_grp = $row->location_base_login_approval_grp;
                        }
                        
                        $psa_id = $this->session->userdata('psa_id');
                        // get workforce notification settings
                        $approval_group_notification = get_notify_settings($leave_approval_grp, $this->company_id);
                        $hours_approver = $this->timeins->get_approver_name_hours($emp_timein->emp_id, $emp_timein->comp_id, $emp_timein->flag_add_logs);
                        
                        if ($hours_approver) {
                            
                            foreach ($hours_approver as $la) {
                                if ($la->level == $curr_level) {
                                    $curr_approver = ucwords($la->first_name . " " . $la->last_name);
                                    $curr_approver_account_id = $la->account_id;
                                }
                            }
                        }
                        // get approver's current level
                        $approver_emp_id = get_approver_emp_id_via_account_id($this->account_id);
                        $approver_emp_id = ($this->session->userdata("user_type_id") == 2) ? "-99{$this->company_id}" : $approver_emp_id;
                        $approver_level = get_current_approver_level($leave_approval_grp, $approver_emp_id, $this->company_id);
                        $time_info = $this->todo_timein->timeins_info($val,$emp_timein->comp_id);
                        
                        $hours_category="";
                        $workflow_type = "";
                        $flag="";

                        if($emp_timein->flag_add_logs==0){
                            $hours_category = "attendance adjustment";
                            $workflow_type = $hours_category;
                            $flag = 0;
                        }elseif($emp_timein->flag_add_logs==1){
                            $hours_category = "add logs";
                            $workflow_type = "add timesheet";
                            $flag = 1;
                        }elseif($emp_timein->flag_add_logs==2){
                            $hours_category = "location base login";
                            $workflow_type = "mobile clock in";
                            $flag = 2;
                        }

                        $approver_emp_id_check = ($approver_emp_id == "-99{$this->company_id}") ? "" : $approver_emp_id;
                        $tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($this->company_id,$time_info->work_schedule_id);

                        $emp_name = ucwords($employee_details->first_name." ".$employee_details->last_name);
                        $emp_email = $employee_details->email;
                        $emp_account_id = $employee_details->account_id;

                        $last_level = $this->timeins->get_timein_last_hours($emp_timein->emp_id, $emp_timein->company_id,$emp_timein->flag_add_logs);
                        // auto rejected if part of a lock or close payroll
                        if($void_v2 == "Closed"){
                            $rejected = false;
                            if($time_info){
                                $auto_remarks = "Auto-rejected due to approval timelapse.";
                                $auto_approval_date = date("Y-m-d H:i:s");
                                $auto_time_in_status = "reject";
                                $auto_date = ($time_info->change_log_date!=NULL) ? date("Y-m-d",strtotime($time_info->change_log_date)) : date("Y-m-d",strtotime($time_info->date));
                                $auto_ws_id = ($time_info->change_log_work_schedule_id != NULL) ? $time_info->change_log_work_schedule_id : $time_info->work_schedule_id;
                                
                                $auto_source = "";
                                if($hours_category == "attendance adjustment") {
                                    $auto_source = "Adjusted";
                                } elseif($hours_category == "add timesheet") {
                                    $auto_source = "EP";
                                }
                                
                                $date_insert = array(
                                    "employee_time_in_id" => $val,
                                    "work_schedule_id" => $auto_ws_id,
                                    "emp_id" => $time_info->emp_id,
                                    "comp_id" => $time_info->comp_id,
                                    "date_filed" => $time_info->change_log_date_filed,
                                    "date" => $auto_date,
                                    "time_in" => $time_info->change_log_time_in,
                                    "lunch_out" => $time_info->change_log_lunch_out,
                                    "lunch_in" => $time_info->change_log_lunch_in,
                                    "break1_out" => $time_info->change_log_break1_out,
                                    "break1_in" => $time_info->change_log_break1_in,
                                    "break2_out" => $time_info->change_log_break2_out,
                                    "break2_in" => $time_info->change_log_break2_in,
                                    "time_out" => $time_info->change_log_time_out,
                                    "total_hours" => $time_info->change_log_total_hours,
                                    "total_hours_required" => $time_info->change_log_total_hours_required,
                                    "reason" => $time_info->reason,
                                    "time_in_status" => $auto_time_in_status,
                                    "overbreak_min" => $time_info->change_log_overbreak_min,
                                    "late_min" => $time_info->change_log_late_min,
                                    "tardiness_min" => $time_info->change_log_tardiness_min,
                                    "undertime_min" => $time_info->change_log_undertime_min,
                                    "absent_min" => $time_info->change_log_absent_min,
                                    "notes" => $auto_remarks,
                                    "source" => $auto_source,
                                    "status" => "Active",
                                    "approval_time_in_id" => $time_info->approval_time_in_id,
                                    "flag_regular_or_excess" => $time_info->flag_regular_or_excess,
                                    "rest_day_r_a" => $time_info->rest_day_r_a,
                                    "flag_rd_include" => $time_info->flag_rd_include,
                                    "flag_holiday_include" => $time_info->flag_holiday_include,
                                    "timesheet_not_req_flag" => $time_info->timesheet_not_req_flag,
                                    "partial_log_ded_break" => $time_info->partial_log_ded_break,
                                    "flag_open_shift" => $time_info->flag_open_shift,
                                    "os_approval_time_in_id" => $time_info->os_approval_time_in_id
                                );
                                
                                $this->db->insert('timesheet_close_payroll', $date_insert);
                                
                                $fields = array(
                                    "time_in_status" => "reject",
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
                                    "employee_time_in_id"=>$val,
                                    "comp_id"=>$emp_timein->company_id
                                );

                                $this->timeins->update_field("employee_time_in",$fields,$where);
                                $rejected = true;

                                # activity logs
                                iactivity_logs($emp_timein->company_id,' has rejected on employee adjustments of ('.$emp_name.')','to do');
                                # end activity logs
                            }

                            if($rejected){
                                ################################ notify staff start ################################
                                if($approval_group_notification->notify_staff == "yes"){
                                    // notify staff via email
                                    timesheet_auto_reject($val, $this->company_id, $emp_email, $tardiness_rule_migrated_v3);

                                }
                                ################################ notify staff end ################################

                                inactive_todo_data($this->company_id,$emp_timein->emp_id,$emp_timein->approval_time_in_id,$module_for_new_todo);
                            }

                        } else {
                            if ($approval_group_notification && $approver_level == $curr_level) { // start [if has notification and approvers turn to approve]
                                $last_level = $this->timeins->get_timein_last_hours($emp_timein->emp_id, $emp_timein->company_id, $emp_timein->flag_add_logs);
                                                                
                                // ############################### APPROVE STARTS HERE ################################
                                $company_id = $this->company_id;
                                $flaggers = 0;

                                $workflow_approved_by_data = array(                                    
                                    'application_id' => $val,
                                    'approver_id' => $approver_emp_id,
                                    'workflow_level' => $curr_level,
                                    'workflow_type' => $workflow_type                                
                                );
                                $this->db->insert('workflow_approved_by', $workflow_approved_by_data);
                                // $approver_emp_id_check = ($approver_emp_id == "-99{$this->company_id}") ? "" : $approver_emp_id;
                                // $time_info = $this->timeins->timeins_info($val, $emp_timein->comp_id);
                                foreach ($hours_approver as $samelvlapr) {
                                    if ($samelvlapr->level == $approver_level && $samelvlapr->emp_id != $approver_emp_id_check) {
                                        $same_level_name = ucwords($samelvlapr->first_name . " " . $samelvlapr->last_name);
                                        $same_level_account_id = $samelvlapr->account_id;
                                        $same_level_email = $samelvlapr->email;
                                        $same_level_emp_id = $samelvlapr->emp_id;
                                        
                                        if ($samelvlapr->emp_id == "") {
                                            $owner_approver = get_approver_owner_info($this->company_id);
                                            $same_level_name = ucwords($owner_approver->first_name . " " . $owner_approver->last_name);
                                            $same_level_account_id = $owner_approver->account_id;
                                            $same_level_email = $owner_approver->email;
                                            $same_level_emp_id = "";
                                        }
                                                                                
                                        ###check email settings if enabled###
                                        if($samelvlapr->ns_timesheet_adj_email_flag == "yes"){
                                            // notify same level approver via email
                                            $this->send_hours_notifcation($val, $this->company_id, $time_info->emp_id, $same_level_email, $same_level_name, $curr_approver, "last", "", "", "", "", $tardiness_rule_migrated_v3, "No");
                                        }
                                        ###end checking email settings if enabled###

                                        // notify same level approver via sms
                                        if($approval_group_notification->sms_notification == "yes"){
                                            $sms_message =  "A ".$hours_category." application has been approved by {$curr_approver}.";
                                            send_this_sms_global($this->company_id, $same_level_account_id, $sms_message, $this->psa_id, false);
                                        }

                                        // notify same level approver via message board
                                        if($approval_group_notification->message_board_notification == "yes"){
                                            $same_level_appr_notif_message = "A ".$hours_category." application has been approved by {$curr_approver}.";
                                            send_to_message_board($this->psa_id, $same_level_emp_id, $curr_approver_account_id, $this->company_id, $same_level_appr_notif_message, "system");
                                        }
                                    }
                                }
                                if ($approver_level == $last_level) {
                                    // ------------------------------- APPROVE START ------------------------------- //
                                    if($emp_timein->for_resend_auto_rejected_id != null || $emp_timein->for_resend_auto_rejected_id != "") {
                                        $v_atp = array(
                                            "time_in_status" => "approved",
                                            "error_log" => "approved",
                                            "flag_rd_include" => "yes",
                                            "flag_holiday_include" => "yes",
                                        );
                                        
                                        $w_atp = array(
                                            "timesheet_close_payroll_id" => $emp_timein->for_resend_auto_rejected_id,
                                            "comp_id" => $emp_timein->comp_id,
                                            "employee_time_in_id" => $val
                                        );
                                        
                                        $this->db->where($w_atp);
                                        $update = $this->db->update("timesheet_close_payroll",$v_atp);
                                    }
                                            
                                    $value1 = array(
                                        "approve_by_hr" => "Yes",
                                        "approve_by_head" => "Yes"
                                    );
                                    $w1 = array(
                                        "time_in_id" => $val,
                                        "comp_id" => $emp_timein->comp_id,
                                        "approve_by_hr" => "No",
                                        "approve_by_head" => "No",
                                        "approval_time_in_id" => $emp_timein->approval_time_in_id
                                    );
                                    $this->db->where($w1);
                                    
                                    $update = $this->db->update("approval_time_in", $value1);
                                    
                                    // $time_info = $this->timeins->timeins_info($val, $emp_timein->comp_id);
                                    
                                    if ($time_info) {

                                        $real_date = ($time_info->date!=NULL) ? date("Y-m-d",strtotime($time_info->date)) : date("Y-m-d");
                                        $concat_break_1_out = '';
                                        $concat_break_1_in = '';
                                        $concat_break_2_out = '';
                                        $concat_break_2_in = '';

                                        if ($flag == 0) {
                                            $concat_time_in = ($time_info->change_log_time_in != NULL) ? date("Y-m-d H:i:s", strtotime($time_info->change_log_time_in)) : NULL;
                                            $concat_lunch_in = ($time_info->change_log_lunch_in != NULL) ? date("Y-m-d H:i:s", strtotime($time_info->change_log_lunch_in)) : NULL;
                                            $concat_lunch_out = ($time_info->change_log_lunch_out != NULL) ? date("Y-m-d H:i:s", strtotime($time_info->change_log_lunch_out)) : NULL;
                                            $concat_time_out = ($time_info->change_log_time_out != NULL) ? date("Y-m-d H:i:s", strtotime($time_info->change_log_time_out)) : NULL;
                                            
                                            $concat_break_1_out = ($time_info->change_log_break1_out!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->change_log_break1_out)) : NULL;
                                            $concat_break_1_in = ($time_info->change_log_break1_in!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->change_log_break1_in)) : NULL;
                                            $concat_break_2_out = ($time_info->change_log_break2_out!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->change_log_break2_out)) : NULL;
                                            $concat_break_2_in = ($time_info->change_log_break2_in!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->change_log_break2_in)): NULL;
                                            $hours_cat = "Attendance Adjustment";
                                        } elseif ($flag == 1) {
                                            $concat_time_in = ($time_info->time_in != NULL) ? date("Y-m-d H:i:s", strtotime($time_info->time_in)) : NULL;
                                            $concat_lunch_in = ($time_info->lunch_in != NULL) ? date("Y-m-d H:i:s", strtotime($time_info->lunch_in)) : NULL;
                                            $concat_lunch_out = ($time_info->lunch_out != NULL) ? date("Y-m-d H:i:s", strtotime($time_info->lunch_out)) : NULL;
                                            $concat_time_out = ($time_info->time_out != NULL) ? date("Y-m-d H:i:s", strtotime($time_info->time_out)) : NULL;
                                            
                                            $concat_break_1_out = ($time_info->change_log_break1_out!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->change_log_break1_out)) : NULL;
                                            $concat_break_1_in = ($time_info->change_log_break1_in!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->change_log_break1_in)) : NULL;
                                            $concat_break_2_out = ($time_info->change_log_break2_out!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->change_log_break2_out)) : NULL;
                                            $concat_break_2_in = ($time_info->change_log_break2_in!=NULL)? date("Y-m-d H:i:s",strtotime($time_info->change_log_break2_in)): NULL;
                                            $hours_cat = "Add Logs";
                                        } elseif ($flag == 2) {
                                            $concat_time_in = ($time_info->time_in != NULL) ? date("Y-m-d H:i:s", strtotime($time_info->time_in)) : NULL;
                                            $concat_lunch_in = ($time_info->lunch_in != NULL) ? date("Y-m-d H:i:s", strtotime($time_info->lunch_in)) : NULL;
                                            $concat_lunch_out = ($time_info->lunch_out != NULL) ? date("Y-m-d H:i:s", strtotime($time_info->lunch_out)) : NULL;
                                            $concat_time_out = ($time_info->time_out != NULL) ? date("Y-m-d H:i:s", strtotime($time_info->time_out)) : NULL;
                                            $hours_cat = "Location Base Login";
                                        }
                                        
                                        $ws_id = ($time_info->change_log_work_schedule_id != NULL) ? $time_info->change_log_work_schedule_id : $time_info->work_schedule_id;
                                        $fields = array(
                                            "time_in_status"        => "approved",
                                            "corrected"             => "Yes",
                                            "time_in"               => $concat_time_in,
                                            "lunch_out"             => $concat_lunch_out,
                                            "lunch_in"              => $concat_lunch_in,
                                            "time_out"              => $concat_time_out,

                                            "break1_out"            => $concat_break_1_out,
                                            "break1_in"             => $concat_break_1_in,
                                            "break2_out"            => $concat_break_2_out,
                                            "break2_in"             => $concat_break_2_in,

                                            "total_hours"           => $time_info->change_log_total_hours,
                                            "total_hours_required"  => $time_info->change_log_total_hours_required,
                                            "tardiness_min"         => $time_info->change_log_tardiness_min,
                                            "undertime_min"         => $time_info->change_log_undertime_min,

                                            "late_min"               => $time_info->change_log_late_min,
                                            "overbreak_min"          => $time_info->change_log_overbreak_min,
                                            "absent_min"             => $time_info->change_log_absent_min,
                                                        
                                            "date"                  => date("Y-m-d", strtotime($concat_time_in)),
                                            "work_schedule_id"       => $ws_id,
                                            "flag_rd_include"        => "yes",
                                            "flag_holiday_include"   => "yes",
                                            "approval_date"          => date("Y-m-d H:i:s")
                                        );
                                        $where = array(
                                            "employee_time_in_id" => $val,
                                            "comp_id" => $emp_timein->comp_id
                                        );
                                        $this->timeins->update_field("employee_time_in", $fields, $where);
                                        payroll_cronjob_helper('timesheet',date("Y-m-d",strtotime($real_date)),$time_info->emp_id,$time_info->comp_id);
                                        // activity logs
                                        iactivity_logs($emp_timein->comp_id, ' has approved the employee adjustments of ( ' . $time_info->last_name . "," . $time_info->first_name . ')', 'to do');
                                        // end activity logs
                                    }
                                    
                                    // ############################### notify staff start ################################
                                    if ($approval_group_notification->notify_staff == "yes") {
                                        // notify staff via email
                                        
                                        $this->send_hours_notifcation($val, $company_id, $emp_timein->emp_id, $emp_email, $emp_name, $curr_approver, "last", "", "", "", "", $tardiness_rule_migrated_v3);
                                        // notify next via sms
                                        if ($approval_group_notification->sms_notification == "yes") {
                                            $sms_message = "Your " . $hours_category . " has been approved by {$curr_approver}.";
                                            send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                        }
                                                                            
                                        // notify staff via message board
                                        if ($approval_group_notification->message_board_notification == "yes") {
                                            $emp_notif_message = "Your " . $hours_category . " has been approved by {$curr_approver}.";
                                            send_to_message_board($psa_id, $emp_timein->emp_id, $curr_approver_account_id, $company_id, $emp_notif_message, "system");
                                        }
                                    }
                                    // ############################### notify staff end ################################
                                    ################################ notify payroll admin start ################################
                                    if($approval_group_notification->notify_payroll_admin == "yes"){
                                        // HRs
                                        $payroll_admin_hr = $this->employee->get_payroll_admin_hr($psa_id);
                                        if($payroll_admin_hr){
                                            foreach ($payroll_admin_hr as $pahr){
                                                $pahr_email = $pahr->email;
                                                $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);
                                                
                                                $this->send_hours_notifcation($val, $company_id, $emp_timein->emp_id, $pahr_email, $pahr_name, $curr_approver, "last", "", "", "", "", $tardiness_rule_migrated_v3, "Yes");
                                            }
                                        }
                                        
                                        // Owner
                                        $pa_owner = get_approver_owner_info($this->company_id);
                                        if($pa_owner){
                                            $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
                                            $pa_owner_email = $pa_owner->email;
                                            $pa_owner_account_id = $pa_owner->account_id;
                                            
                                            ###check email settings if enabled###                                            
                                            if($pa_owner->ns_timesheet_adj_email_flag == "yes"){
                                                $this->send_hours_notifcation($val, $company_id, $emp_timein->emp_id, $pa_owner_email, $pa_owner_name, $curr_approver, "last", "", "", "", "", $tardiness_rule_migrated_v3, "Yes");
                                            }                                            
                                            ###end checking email settings if enabled###
                                        }
                                    }
                                    ################################ notify payroll admin end ################################

                                    inactive_todo_data($company_id,$emp_timein->emp_id,$emp_timein->approval_time_in_id,$module_for_new_todo);
                                } else {
                                    $next_level = $approver_level + 1;
                                    $new_token = $this->timeins->generate_leave_level_token($next_level, $val);
                                    
                                    $new_todo_appr_id = "";
                                    foreach ($hours_approver as $nextapr) {
                                        if ($nextapr->level == $next_level) {
                                            $next_appr_name = ucwords($nextapr->first_name . " " . $nextapr->last_name);
                                            $next_appr_account_id = $nextapr->account_id;
                                            $next_appr_email = $nextapr->email;
                                            $next_appr_emp_id = $nextapr->emp_id;
                                            
                                            $new_todo_appr_id .= $nextapr->emp_id.',';
                                            $token = $this->todo_timein->get_token($val, $company_id, $emp_timein->emp_id, $emp_timein->approval_time_in_id);
                                            $url = base_url() . "approval/timein/index/" . $token . "/" . $new_token . "/1" . $next_appr_emp_id . "0";
                                            
                                            ###check email settings if enabled###                                            
                                            if($nextapr->ns_timesheet_adj_email_flag == "yes"){
                                                // notify next approver via email
                                                $this->send_hours_notifcation($val, $emp_timein->company_id, $emp_timein->emp_id, $next_appr_email, $next_appr_name, $curr_approver, "Approver", "Yes", $new_token, $next_appr_emp_id, "", $tardiness_rule_migrated_v3, "No");
                                            }
                                            ###end checking email settings if enabled###
                                            
                                            // notify next approver via sms
                                            if ($approval_group_notification->sms_notification == "yes") {
                                                $sms_message = "Click {$url} to approve {$emp_name}'s " . $hours_category . ".";
                                                send_this_sms_global($this->company_id, $next_appr_account_id, $sms_message, $psa_id, false);
                                            }
                                                                                    
                                            // notify next approver via message board
                                            if ($approval_group_notification->message_board_notification == "yes") {
                                                
                                                $next_appr_notif_message = "A " . $hours_category . " application has been approved by {$curr_approver} and is now waiting for your approval.";
                                                send_to_message_board($psa_id, $next_appr_emp_id, $curr_approver_account_id, $company_id, $next_appr_notif_message, "system");
                                            }
                                        }
                                    }

                                    insert_todo_data($this->company_id,date("Y-m-d",strtotime($emp_timein->date)),$emp_timein->emp_id,$emp_timein->approval_time_in_id,$next_level,$new_todo_appr_id,$emp_timein->approver_id,$emp_timein->work_schedule_id,$module_for_new_todo);
                                    
                                    // ############################### notify staff start ################################
                                    if ($approval_group_notification->notify_staff == "yes") {
                                        // notify staff via email
                                        $this->send_hours_notifcation($val, $company_id, $emp_timein->emp_id, $emp_email, $emp_name . '/' . $next_appr_name, $curr_approver, "", "", "", "", "", $tardiness_rule_migrated_v3);
                                        // notify next via sms
                                        if ($approval_group_notification->sms_notification == "yes") {
                                            $sms_message = "Your " . $hours_category . " has been approved by {$curr_approver}.";
                                            send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                        }
                                                                            
                                        // notify staff via message board
                                        if ($approval_group_notification->message_board_notification == "yes") {
                                            $emp_notif_message = "Your " . $hours_category . " has been approved by {$curr_approver}.";
                                            send_to_message_board($psa_id, $emp_timein->emp_id, $curr_approver_account_id, $company_id, $emp_notif_message, "system");
                                        }
                                    }
                                    // ############################### notify staff end ################################

                                    ################################ notify payroll admin start ################################
                                    if($approval_group_notification->notify_payroll_admin == "yes"){
                                        // HRs
                                        $payroll_admin_hr = $this->employee->get_payroll_admin_hr($psa_id);
                                        if($payroll_admin_hr){
                                            foreach ($payroll_admin_hr as $pahr){
                                                $pahr_email = $pahr->email;
                                                $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);
                                                
                                                ###check email settings if enabled###
                                                if($pahr->ns_timesheet_adj_email_flag == "yes"){
                                                    $this->send_hours_notifcation($val, $company_id, $emp_timein->emp_id, $pahr_email, $pahr_name.'/'.$next_appr_name, $curr_approver, "", "", "", "", "", $tardiness_rule_migrated_v3, "Yes");
                                                }
                                                ###end checking email settings if enabled###
                                            }
                                        }
                                        
                                        // Owner
                                        $pa_owner = get_approver_owner_info($this->company_id);
                                        if($pa_owner){
                                            $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
                                            $pa_owner_email = $pa_owner->email;
                                            $pa_owner_account_id = $pa_owner->account_id;
                                            
                                            ###check email settings if enabled###
                                            if($pa_owner->ns_timesheet_adj_email_flag == "yes"){
                                                $this->send_hours_notifcation($val, $company_id, $emp_timein->emp_id, $pa_owner_email, $pa_owner_name.'/'.$next_appr_name, $curr_approver, "", "", "", "", "", $tardiness_rule_migrated_v3, "Yes");
                                            }
                                            ###end checking email settings if enabled###
                                        }
                                    }
                                    ################################ notify payroll admin end ################################
                                }
                                // ############################### APPROVE ################################
                            } // end [if has notification and approvers turn to approve]
                        }
                    } else {
                        echo json_encode(array(
                            "success" => false,
                            "error" => "Invalid Attendance Details"
                        ));
                        return false;
                    }
                }
                
                $company_subdomain = $this->todo_timein->get_company_subdomain();
                if ($company_subdomain) {
                    notify_approver_timesheet_pending_emp($this->company_id, $this->emp_id, $this->psa_id, $company_subdomain);
                }
                
                echo json_encode(array(
                    "success" => true,
                    "error" => "The timesheet has been approved"
                ));
                return true;
            }
            
            // SPLIT SCHED IS NOT MAINTAINED !!!!
            // if ($schedule_blocks_time_in_id) {
            $is_split = false;
            if ($is_split) {
                foreach ($timesheets_id as $key => $val) {
                    // for split approval
                    $split_emp_timein = $this->todo_timein->get_employee_split_time_in($val);                    
                    $split_employee_details = get_employee_details_by_empid($split_emp_timein->emp_id);
                    
                    // for split appproval
                    if ($split_emp_timein) {
                        $curr_approver = "";
                        $curr_approver_account_id = "";
                        $curr_level = $split_emp_timein->level;
                        
                        // get the first and last blocks
                        $work_schedule_id = $this->employee->emp_work_schedule($split_emp_timein->emp_id, $this->company_id, $split_emp_timein->date);
                        $yest_list = $this->elm->list_of_blocks_todo($split_emp_timein->date, $split_emp_timein->emp_id, $work_schedule_id, $this->company_id);
                        $first_sched = reset($yest_list);
                        $last_sched = max($yest_list);
                        
                        $this->db->where("emp_id", $split_emp_timein->emp_id);
                        $sql = $this->db->get("employee_payroll_information");
                        $row = $sql->row();

                        if ($row) {
                            if ($split_emp_timein->flag_add_logs == 0)
                                $leave_approval_grp = $row->attendance_adjustment_approval_grp;
                            elseif ($split_emp_timein->flag_add_logs == 1)
                                $leave_approval_grp = $row->add_logs_approval_grp;
                            elseif ($split_emp_timein->flag_add_logs == 2)
                                $leave_approval_grp = $row->location_base_login_approval_grp;
                        }
                        // xc
                        $psa_id = $this->session->userdata('psa_id');
                        // get workforce notification settings
                        $approval_group_notification = get_notify_settings($leave_approval_grp, $this->company_id);
                        $hours_approver = $this->timeins->get_approver_name_hours($split_emp_timein->emp_id, $split_emp_timein->comp_id, $split_emp_timein->flag_add_logs);
                        
                        if ($hours_approver) {
                            foreach ($hours_approver as $la) {
                                if ($la->level == $curr_level) {
                                    $curr_approver = ucwords($la->first_name . " " . $la->last_name);
                                    $curr_approver_account_id = $la->account_id;
                                }
                            }
                        }
                        
                        // get approver's current level
                        $approver_emp_id = get_approver_emp_id_via_account_id($curr_approver_account_id);
                        $approver_level = get_current_approver_level($leave_approval_grp, $approver_emp_id, $this->company_id);
                        
                        if ($approval_group_notification && $approver_level == $curr_level) { // start [if has notification and approvers turn to approve]
                            $last_level = $this->timeins->get_timein_last_hours($split_emp_timein->emp_id, $split_emp_timein->company_id, $split_emp_timein->flag_add_logs);
                            
                            $emp_name = ucwords($split_employee_details->first_name . " " . $split_employee_details->last_name);
                            $emp_email = $split_employee_details->email;
                            $emp_account_id = $split_employee_details->account_id;
                            
                            // ############################### APPROVE STARTS HERE ################################
                            $company_id = $this->company_id;
                            $flaggers = 0;
                            $hours_category = "";
                            $workflow_type = "";
                            $flag = "";

                            if ($split_emp_timein->flag_add_logs == 0) {
                                $hours_category = "attendance adjustment";
                                $workflow_type = $hours_category;
                                $flag = 0;
                            } elseif ($split_emp_timein->flag_add_logs == 1) {
                                $hours_category = "add logs";
                                $workflow_type = "add timesheet";
                                $flag = 1;
                            } elseif ($split_emp_timein->flag_add_logs == 2) {
                                $hours_category = "location base login";
                                $workflow_type = "mobile clock in";
                                $flag = 2;
                            }
                            
                            $workflow_approved_by_data = array(
                                'application_id' => $val,
                                'approver_id' => $approver_emp_id,
                                'workflow_level' => $curr_level,
                                'workflow_type' => $workflow_type
                            
                            );
                            
                            $this->db->insert('workflow_approved_by', $workflow_approved_by_data);
                            $approver_emp_id_check = ($approver_emp_id == "-99{$this->company_id}") ? "" : $approver_emp_id;
                            $split_time_info = $this->timeins->split_timeins_info($val, $split_emp_timein->comp_id);
                            
                            foreach ($hours_approver as $samelvlapr) {
                                if ($samelvlapr->level == $approver_level && $samelvlapr->emp_id != $approver_emp_id_check) {
                                    $same_level_name = ucwords($samelvlapr->first_name . " " . $samelvlapr->last_name);
                                    $same_level_account_id = $samelvlapr->account_id;
                                    $same_level_email = $samelvlapr->email;
                                    $same_level_emp_id = $samelvlapr->emp_id;
                                    
                                    if ($samelvlapr->emp_id == "") {
                                        $owner_approver = get_approver_owner_info($this->company_id);
                                        $same_level_name = ucwords($owner_approver->first_name . " " . $owner_approver->last_name);
                                        $same_level_account_id = $owner_approver->account_id;
                                        $same_level_email = $owner_approver->email;
                                        $same_level_emp_id = "";
                                    }
                                    
                                    // notify same level approver via email
                                    $this->send_hours_notifcation($val, $this->company_id, $split_time_info->emp_id, $same_level_email, $same_level_name, $curr_approver, "last", "", "", '', true);
                                    
                                    // notify same level approver via sms
                                    if ($approval_group_notification->sms_notification == "yes") {
                                        $sms_message = "A " . $hours_category . " application has been approved by {$curr_approver}.";
                                        send_this_sms_global($this->company_id, $same_level_account_id, $sms_message, $this->psa_id, false);
                                    }
                                    
                                    // notify same level approver via message board
                                    if ($approval_group_notification->message_board_notification == "yes") {
                                        $same_level_appr_notif_message = "A " . $hours_category . " application has been approved by {$curr_approver}.";
                                        send_to_message_board($this->psa_id, $same_level_emp_id, $curr_approver_account_id, $this->company_id, $same_level_appr_notif_message, "system");
                                    }
                                }
                            }
                            
                            if ($approver_level == $last_level) {
                                // ------------------------------- APPROVE START ------------------------------- //
                                
                                $value1 = array(
                                    "approve_by_hr" => "Yes",
                                    "approve_by_head" => "Yes"
                                );

                                $w1 = array(
                                    "split_time_in_id" => $val,
                                    "comp_id" => $split_emp_timein->comp_id,
                                    "approve_by_hr" => "No",
                                    "approve_by_head" => "No",
                                    "approval_time_in_id" => $split_emp_timein->approval_time_in_id
                                );
                                $this->db->where($w1);

                                $update = $this->db->update("approval_time_in",$value1);
                                 
                                
                                $split_time_info = $this->timeins->split_timeins_info($val, $split_emp_timein->comp_id);
                                
                                if ($split_time_info) {
                                    $real_date = ($time_info->date!=NULL) ? date("Y-m-d",strtotime($time_info->date)) : date("Y-m-d");
                                    $void = $this->employee->edit_delete_void($split_time_info->emp_id, $split_time_info->comp_id,$split_time_info->date);
                                    $get_all_child_block = $this->employee->get_valid_split_logs($split_time_info->emp_id,$split_time_info->comp_id,$split_time_info->employee_time_in_id);
                                           
                                    if ($flag == 0) {
                                        $concat_time_in = ($split_time_info->change_log_time_in != NULL) ? date("Y-m-d H:i:s", strtotime($split_time_info->change_log_time_in)) : NULL;
                                        $concat_lunch_in = ($split_time_info->change_log_lunch_in != NULL) ? date("Y-m-d H:i:s", strtotime($split_time_info->change_log_lunch_in)) : NULL;
                                        $concat_lunch_out = ($split_time_info->change_log_lunch_out != NULL) ? date("Y-m-d H:i:s", strtotime($split_time_info->change_log_lunch_out)) : NULL;
                                        $concat_time_out = ($split_time_info->change_log_time_out != NULL) ? date("Y-m-d H:i:s", strtotime($split_time_info->change_log_time_out)) : NULL;
                                        $hours_cat = "Attendance Adjustment";
                                        $get_split_timein_approved = $this->employee->get_split_timein_approved($split_emp_timein->emp_id, $split_emp_timein->comp_id, $split_time_info->employee_time_in_id, true);
                                    } elseif ($flag == 1) {
                                        $concat_time_in = ($split_time_info->time_in != NULL) ? date("Y-m-d H:i:s", strtotime($split_time_info->time_in)) : NULL;
                                        $concat_lunch_in = ($split_time_info->lunch_in != NULL) ? date("Y-m-d H:i:s", strtotime($split_time_info->lunch_in)) : NULL;
                                        $concat_lunch_out = ($split_time_info->lunch_out != NULL) ? date("Y-m-d H:i:s", strtotime($split_time_info->lunch_out)) : NULL;
                                        $concat_time_out = ($split_time_info->time_out != NULL) ? date("Y-m-d H:i:s", strtotime($split_time_info->time_out)) : NULL;
                                        $hours_cat = "Add Logs";
                                        $get_split_timein_approved = $this->employee->get_split_timein_approved($split_emp_timein->emp_id, $split_emp_timein->comp_id, $split_time_info->employee_time_in_id);
                                    } elseif ($flag == 2) {
                                        $concat_time_in = ($split_time_info->time_in != NULL) ? date("Y-m-d H:i:s", strtotime($split_time_info->time_in)) : NULL;
                                        $concat_lunch_in = ($split_time_info->lunch_in != NULL) ? date("Y-m-d H:i:s", strtotime($split_time_info->lunch_in)) : NULL;
                                        $concat_lunch_out = ($split_time_info->lunch_out != NULL) ? date("Y-m-d H:i:s", strtotime($split_time_info->lunch_out)) : NULL;
                                        $concat_time_out = ($split_time_info->time_out != NULL) ? date("Y-m-d H:i:s", strtotime($split_time_info->time_out)) : NULL;
                                        $hours_cat = "Location Base Login";
                                    }

                                    $change_log_total_hours = 0;
                                    $change_log_total_hours_required = 0;
                                    $change_log_tardiness_min = 0;
                                    $change_log_undertime_min = 0;
                                    
                                    $change_log_late_min = 0;
                                    $change_log_overbreak_min = 0;
                                    $change_log_absent_min = 0;
                                    
                                    if($get_all_child_block) {
                                        foreach ($get_all_child_block as $split_logs) {
                                            $change_log_total_hours += $split_logs->total_hours;
                                            $change_log_total_hours_required += $split_logs->total_hours_required;
                                            $change_log_tardiness_min += $split_logs->tardiness_min;
                                            $change_log_undertime_min += $split_logs->undertime_min;
                                            
                                            $change_log_late_min += $split_logs->late_min;
                                            $change_log_overbreak_min += $split_logs->overbreak_min;
                                            $change_log_absent_min += $split_logs->absent_min;
                                        }
                                    }
                                    
                                    $timein_total_hours = 0;
                                    $timein_total_hours_required = 0;
                                    $timein_tardiness = 0;
                                    $timein_undertime = 0;
                                    
                                    $timein_late_min = 0;
                                    $timein_overbreak_min = 0;
                                    $timein_absent_min = 0;
                                    
                                    $timein_total_hours_required = $change_log_total_hours_required + $split_time_info->change_log_total_hours_required;
                                    $timein_tardiness = $change_log_tardiness_min + $split_time_info->change_log_tardiness_min;
                                    $timein_undertime = $change_log_undertime_min + $split_time_info->change_log_undertime_min;
                                    
                                    $timein_late_min = $change_log_late_min + $split_time_info->late_min;
                                    $timein_overbreak_min = $change_log_overbreak_min + $split_time_info->overbreak_min;
                                    $timein_absent_min = $change_log_absent_min + $split_time_info->absent_min;

                                    $get_blocks_list = $this->elm->get_blocks_list($split_time_info->schedule_blocks_id);
                                    $total_hours_split = $get_blocks_list->total_hours_work_per_block;
                                    
                                    // get info list of blocks for this day
                                    #$get_split_block_name = $this->employee->list_of_blocks($real_date,$split_time_info->emp_id,$split_time_info->work_schedule_id,$split_time_info->comp_id);
                                    $get_split_block_name = $this->employee->get_all_child_block($split_time_info->emp_id,$this->company_id,$split_time_info->employee_time_in_id);
                                    $total_hours_work_all_block = 0;
                                    $valid_total_hours = $total_hours_split;
                                    
                                    if($get_split_block_name) {
                                        foreach ($get_split_block_name as $row) {
                                            if($split_time_info->schedule_blocks_id == $row->schedule_blocks_id) {
                                                $total_hours_work_all_block += $row->total_hours;
                                            }
                                        }
                                    }
                                    
                                    $total_hours_work_all_block_mins = $total_hours_work_all_block * 60;
                                    $for_absent_min = $total_hours_work_all_block_mins - ($valid_total_hours * 60);
                                    $for_absent_min = ($for_absent_min > 0) ? number_format($for_absent_min,2) : 0;

                                    $fields = array(
                                        "time_in_status"        => "approved",
                                        "corrected"             => "Yes",
                                        "time_in"               => $concat_time_in,
                                        "lunch_out"             => $concat_lunch_out,
                                        "lunch_in"              => $concat_lunch_in,
                                        "time_out"              => $concat_time_out,
                                        "total_hours"           => $split_time_info->change_log_total_hours,
                                        "total_hours_required"  => $split_time_info->change_log_total_hours_required,
                                        "tardiness_min"         => $split_time_info->change_log_tardiness_min,
                                        "undertime_min"         => $split_time_info->change_log_undertime_min,
                                        "date"                  => date("Y-m-d", strtotime($concat_time_in))
                                    );
                                    
                                    $where = array(
                                        "schedule_blocks_time_in_id" => $val,
                                        "comp_id" => $split_emp_timein->comp_id
                                    );
                                    
                                    $this->timeins->update_field("schedule_blocks_time_in", $fields, $where);

                                    payroll_cronjob_helper('timesheet',date("Y-m-d",strtotime($real_date)),$split_time_info->emp_id,$split_time_info->comp_id);

                                    // if blocks is last or first
                                    if($split_time_info->schedule_blocks_id == $first_sched->schedule_blocks_id){
                                        if($void == "Waiting for approval" || $void == "Closed"){
                                            $fields = array(
                                                "time_in"    => $concat_time_in,
                                                "status"     => "Inactive"
                                            );
                                            
                                            $where = array(
                                                "employee_time_in_id"    => $split_time_info->employee_time_in_id,
                                                "comp_id"                => $split_emp_timein->comp_id
                                            );
                                            
                                            $this->timeins->update_field("employee_time_in",$fields,$where);
                                            
                                            $date_insert = array(
                                                "employee_time_in_id"           => $split_time_info->employee_time_in_id,
                                                "emp_id"                        => $split_time_info->emp_id,
                                                "company_id"                    => $split_time_info->comp_id,
                                                "date"                          => date("Y-m-d",strtotime($real_date)),
                                                "time_in"                       => $concat_time_in,
                                                "lunch_out"                     => $concat_lunch_out,
                                                "lunch_in"                      => $concat_lunch_in,
                                                "time_out"                      => $concat_time_out,
                                                "total_hours"                   => $split_time_info->change_log_total_hours,
                                                "total_hours_required"          => $split_time_info->change_log_total_hours_required,
                                                "late_min"                      => $split_time_info->late_min,
                                                "overbreak_min"                 => $split_time_info->overbreak_min,
                                                "tardiness_min"                 => $split_time_info->change_log_tardiness_min,
                                                "undertime_min"                 => $split_time_info->change_log_undertime_min,
                                                "absent_min"                    => $split_time_info->absent_min,
                                                "date_approved"                 => date('Y-m-d'),
                                                "source"                        => $split_time_info->source,
                                                "status"                        => "Active",
                                                "workschedule_id"               => $split_time_info->work_schedule_id
                                            );
                                            
                                            $this->db->insert('employee_time_in_correction', $date_insert);
                                        } else {
                                            if(!$get_all_child_block) {
                                                $fields = array(
                                                    "time_in" => $concat_time_in,
                                                    "time_out" => $concat_time_out,
                                                    "total_hours_required" => $split_time_info->change_log_total_hours_required,
                                                    "late_min" => $split_time_info->late_min,
                                                    "overbreak_min" => $split_time_info->overbreak_min,
                                                    "tardiness_min" => $split_time_info->change_log_tardiness_min,
                                                    "undertime_min" => $split_time_info->change_log_undertime_min,
                                                    "absent_min" => $split_time_info->absent_min,
                                                );
                                                
                                                $where = array(
                                                    "employee_time_in_id" => $split_time_info->employee_time_in_id,
                                                    "comp_id" => $split_emp_timein->comp_id
                                                );
                                                
                                                $this->timeins->update_field("employee_time_in",$fields,$where);
                                            } else {
                                                $fields = array(
                                                    "time_in" => $concat_time_in,
                                                    "total_hours_required" => $timein_total_hours_required,
                                                    "late_min" => $timein_late_min,
                                                    "overbreak_min" => $timein_overbreak_min,
                                                    "tardiness_min" => $timein_tardiness,
                                                    "undertime_min" => $timein_undertime,
                                                    "absent_min" => $timein_absent_min,
                                                );
                                                
                                                $where = array(
                                                    "employee_time_in_id"=>$split_time_info->employee_time_in_id,
                                                    "comp_id"=>$split_emp_timein->comp_id
                                                );
                                                
                                                $this->timeins->update_field("employee_time_in",$fields,$where);
                                            }
                                        }
                                    
                                    } elseif($split_time_info->schedule_blocks_id == $last_sched->schedule_blocks_id) {
                                        if($void == "Waiting for approval" || $void == "Closed"){
                                            $fields = array(
                                                "time_out"   => $concat_time_out,
                                                "status"     => "Inactive"
                                            );
                                            
                                            $where = array(
                                                "employee_time_in_id"    => $split_time_info->employee_time_in_id,
                                                "comp_id"                => $split_emp_timein->comp_id
                                            );
                                            
                                            $this->timeins->update_field("employee_time_in",$fields,$where);
                                            
                                            $date_insert = array(
                                                "employee_time_in_id"           => $split_time_info->employee_time_in_id,
                                                "emp_id"                        => $split_time_info->emp_id,
                                                "company_id"                    => $split_time_info->comp_id,
                                                "date"                          => date("Y-m-d",strtotime($real_date)),
                                                "time_in"                       => $concat_time_in,
                                                "lunch_out"                     => $concat_lunch_out,
                                                "lunch_in"                      => $concat_lunch_in,
                                                "time_out"                      => $concat_time_out,
                                                "total_hours"                   => $split_time_info->change_log_total_hours,
                                                "total_hours_required"          => $split_time_info->change_log_total_hours_required,
                                                "late_min"                      => $split_time_info->late_min,
                                                "overbreak_min"                 => $split_time_info->overbreak_min,
                                                "tardiness_min"                 => $split_time_info->change_log_tardiness_min,
                                                "undertime_min"                 => $split_time_info->change_log_undertime_min,
                                                "absent_min"                    => $split_time_info->absent_min,
                                                "date_approved"                 => date('Y-m-d'),
                                                "source"                        => $split_time_info->source,
                                                "status"                        => "Active",
                                                "workschedule_id"               => $split_time_info->work_schedule_id
                                            );
                                            
                                            $this->db->insert('employee_time_in_correction', $date_insert);
                                        } else {
                                            if(!$get_all_child_block) {
                                                $fields = array(
                                                    "time_in" => $concat_time_in,
                                                    "time_out" => $concat_time_out,
                                                    "total_hours_required" => $timein_total_hours_required,
                                                    "late_min" => $split_time_info->late_min,
                                                    "overbreak_min" => $split_time_info->overbreak_min,
                                                    "tardiness_min" => $split_time_info->change_log_tardiness_min,
                                                    "undertime_min" => $split_time_info->change_log_undertime_min,
                                                    "absent_min" => $split_time_info->absent_min,
                                                );
                                                
                                                $where = array(
                                                    "employee_time_in_id" => $split_time_info->employee_time_in_id,
                                                    "comp_id" => $split_emp_timein->comp_id
                                                );
                                                
                                                $this->timeins->update_field("employee_time_in",$fields,$where);
                                            } else {
                                                $fields = array(
                                                    "time_out" => $concat_time_out,
                                                    "total_hours_required" => $timein_total_hours_required,
                                                    "late_min" => $timein_late_min,
                                                    "overbreak_min" => $timein_overbreak_min,
                                                    "tardiness_min" => $timein_tardiness,
                                                    "undertime_min" => $timein_undertime,
                                                    "absent_min" => $timein_absent_min,
                                                );
                                                
                                                $where = array(
                                                    "employee_time_in_id"=>$split_time_info->employee_time_in_id,
                                                    "comp_id"=>$split_emp_timein->comp_id
                                                );
                                                
                                                $this->timeins->update_field("employee_time_in",$fields,$where);
                                            }
                                        }
                                    } else {
                                        if(!$get_all_child_block) {
                                            $fields = array(
                                                "time_in" => $concat_time_in,
                                                "time_out" => $concat_time_out,
                                                "total_hours_required" => $split_time_info->change_log_total_hours_required,
                                                "late_min" => $split_time_info->late_min,
                                                "overbreak_min" => $split_time_info->overbreak_min,
                                                "tardiness_min" => $split_time_info->change_log_tardiness_min,
                                                "undertime_min" => $split_time_info->change_log_undertime_min,
                                                "absent_min" => $split_time_info->absent_min,
                                            );
                                            
                                            $where = array(
                                                "employee_time_in_id" => $split_time_info->employee_time_in_id,
                                                "comp_id" => $split_emp_timein->comp_id
                                            );
                                            
                                            $this->timeins->update_field("employee_time_in",$fields,$where);
                                        } else { 
                                            $block_count = count($get_all_child_block);
                                            
                                            foreach ($get_all_child_block as $row) {
                                                if($row->schedule_blocks_id == $first_sched->schedule_blocks_id && $block_count < 2) {
                                                    if($row->schedule_blocks_id != $val) {
                                                        $fields = array(
                                                            "time_out" => $concat_time_out,
                                                            "total_hours_required" => $timein_total_hours_required,
                                                            "late_min" => $timein_late_min,
                                                            "overbreak_min" => $timein_overbreak_min,
                                                            "tardiness_min" => $timein_tardiness,
                                                            "undertime_min" => $timein_undertime,
                                                            "absent_min" => $timein_absent_min,
                                                        );
                                                        
                                                        $where = array(
                                                            "employee_time_in_id"=>$split_time_info->employee_time_in_id,
                                                            "comp_id"=>$split_emp_timein->comp_id
                                                        );
                                                        
                                                        $this->timeins->update_field("employee_time_in",$fields,$where);
                                                        break;
                                                    }
                                                } elseif ($row->schedule_blocks_id == $last_sched->schedule_blocks_id && $block_count < 2) {
                                                    if($row->schedule_blocks_id != $val) {
                                                        $fields = array(
                                                            "time_in" => $concat_time_in,
                                                        );
                                                        
                                                        $where = array(
                                                            "employee_time_in_id"=>$split_time_info->employee_time_in_id,
                                                            "comp_id"=>$split_emp_timein->comp_id
                                                        );
                                                        
                                                        $this->timeins->update_field("employee_time_in",$fields,$where);
                                                        break;
                                                    }
                                                } else {
                                                    if($row->schedule_blocks_id != $val) {
                                                        $fields = array(
                                                            "total_hours_required" => $timein_total_hours_required,
                                                            "late_min" => $split_time_info->late_min,
                                                            "overbreak_min" => $split_time_info->overbreak_min,
                                                            "tardiness_min" => $timein_tardiness,
                                                            "undertime_min" => $timein_undertime,
                                                            "absent_min" => $split_time_info->absent_min,
                                                        );
                                                        
                                                        $where = array(
                                                            "employee_time_in_id"=>$split_time_info->employee_time_in_id,
                                                            "comp_id"=>$split_emp_timein->comp_id
                                                        );
                                                        
                                                        $this->timeins->update_field("employee_time_in",$fields,$where);
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
                                    $check_if_all_child_not_pending = $this->employee->check_if_all_child_pending($split_time_info->emp_id,$split_time_info->comp_id,$split_time_info->employee_time_in_id);
                                    if(!$check_if_all_child_not_pending) {
                                        $fields = array(
                                            "time_in_status" => "approved",
                                            "split_status" => "approved"
                                        );
                                        
                                        $where = array(
                                            "employee_time_in_id"=>$split_time_info->employee_time_in_id,
                                            "comp_id"=>$split_emp_timein->comp_id
                                        );
                                        
                                        $this->timeins->update_field("employee_time_in",$fields,$where);
                                    }
                                    
                                    // activity logs
                                    iactivity_logs($split_emp_timein->comp_id, ' has approved the employee adjustments of ( ' . $split_time_info->last_name . "," . $split_time_info->first_name . ')', 'to do');
                                    // end activity logs
                                }
                                
                                // ############################### notify staff start ################################
                                if ($approval_group_notification->notify_staff == "yes") {
                                    // notify staff via email
                                    
                                    $this->send_hours_notifcation($val, $company_id, $split_emp_timein->emp_id, $emp_email, $emp_name, $curr_approver, "last", "", "", "", true);
                                    // notify next via sms
                                    if ($approval_group_notification->sms_notification == "yes") {
                                        $sms_message = "Your " . $hours_category . " has been approved by {$curr_approver}.";
                                        send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                    }
                                                                        
                                    // notify staff via message board
                                    if ($approval_group_notification->message_board_notification == "yes") {
                                        $emp_notif_message = "Your " . $hours_category . " has been approved by {$curr_approver}.";
                                        send_to_message_board($psa_id, $split_emp_timein->emp_id, $curr_approver_account_id, $company_id, $emp_notif_message, "system");
                                    }
                                }
                                // ############################### notify staff end ################################

                                ################################ notify payroll admin start ################################
                                if($approval_group_notification->notify_payroll_admin == "yes"){
                                    // HRs
                                    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($psa_id);
                                    if($payroll_admin_hr){
                                        foreach ($payroll_admin_hr as $pahr){
                                            $pahr_email = $pahr->email;
                                            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);
                                            
                                            $this->send_hours_notifcation($val, $company_id, $split_emp_timein->emp_id, $pahr_email, $pahr_name, $curr_approver, "last", "", "", "", true);
                                        }
                                    }
                                    
                                    // Owner
                                    $pa_owner = get_approver_owner_info($this->company_id);
                                    if($pa_owner){
                                        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
                                        $pa_owner_email = $pa_owner->email;
                                        $pa_owner_account_id = $pa_owner->account_id;

                                        $this->send_hours_notifcation($val, $company_id, $split_emp_timein->emp_id, $pa_owner_email, $pa_owner_name, $curr_approver, "last", "", "", "", true);
                                    }
                                }
                                ################################ notify payroll admin end ################################
                            } else {
                                $next_level = $approver_level + 1;
                                $new_token = $this->timeins->generate_leave_level_token_split($next_level, $val);
                                
                                foreach ($hours_approver as $nextapr) {
                                    if ($nextapr->level == $next_level) {
                                        $next_appr_name = ucwords($nextapr->first_name . " " . $nextapr->last_name);
                                        $next_appr_account_id = $nextapr->account_id;
                                        $next_appr_email = $nextapr->email;
                                        $next_appr_emp_id = $nextapr->emp_id;
                                        
                                        $token = $this->todo_timein->get_token($val, $company_id, $split_emp_timein->emp_id, $split_emp_timein->approval_time_in_id);
                                        $url = base_url() . "approval/timein/index/" . $token . "/" . $new_token . "/1" . $next_appr_emp_id . "0";
                                        
                                        // notify next approver via email
                                        $this->send_hours_notifcation($val, $split_emp_timein->company_id, $split_emp_timein->emp_id, $next_appr_email, $next_appr_name, $curr_approver, "Approver", "Yes", $new_token, $next_appr_emp_id, true);
                                        // notify next approver via sms
                                        if ($approval_group_notification->sms_notification == "yes") {
                                            $sms_message = "Click {$url} to approve {$emp_name}'s " . $hours_category . ".";
                                            send_this_sms_global($this->company_id, $next_appr_account_id, $sms_message, $psa_id, false);
                                        }
                                                                                
                                        // notify next approver via message board
                                        if ($approval_group_notification->message_board_notification == "yes") {
                                            
                                            $next_appr_notif_message = "A " . $hours_category . " application has been approved by {$curr_approver} and is now waiting for your approval.";
                                            send_to_message_board($psa_id, $next_appr_emp_id, $curr_approver_account_id, $company_id, $next_appr_notif_message, "system");
                                        }
                                    }
                                }
                                
                                // ############################### notify staff start ################################
                                if ($approval_group_notification->notify_staff == "yes") {
                                    // notify staff via email
                                    
                                    $this->send_hours_notifcation($val, $company_id, $split_emp_timein->emp_id, $emp_email, $emp_name . '/' . $next_appr_name, $curr_approver, "", "", "", "", true);
                                    // notify next via sms
                                    if ($approval_group_notification->sms_notification == "yes") {
                                        $sms_message = "Your " . $hours_category . " has been approved by {$curr_approver}.";
                                        send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                    }
                                                                        
                                    // notify staff via message board
                                    if ($approval_group_notification->message_board_notification == "yes") {
                                        $emp_notif_message = "Your " . $hours_category . " has been approved by {$curr_approver}.";
                                        send_to_message_board($psa_id, $split_emp_timein->emp_id, $curr_approver_account_id, $company_id, $emp_notif_message, "system");
                                    }
                                }
                                // ############################### notify staff end ################################

                                ################################ notify payroll admin start ################################
                                if($approval_group_notification->notify_payroll_admin == "yes"){
                                    // HRs
                                    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($psa_id);
                                    if($payroll_admin_hr){
                                        foreach ($payroll_admin_hr as $pahr){
                                            $pahr_email = $pahr->email;
                                            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);
                                            
                                            $this->send_hours_notifcation($val, $company_id, $split_emp_timein->emp_id, $pahr_email, $pahr_name.'/'.$next_appr_name, $curr_approver, "", "", "", "", true);
                                        }
                                    }
                                    
                                    // Owner
                                    $pa_owner = get_approver_owner_info($this->company_id);
                                    if($pa_owner){
                                        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
                                        $pa_owner_email = $pa_owner->email;
                                        $pa_owner_account_id = $pa_owner->account_id;
                                        
                                        $this->send_hours_notifcation($val, $company_id, $split_emp_timein->emp_id, $pa_owner_email, $pa_owner_name.'/'.$next_appr_name, $curr_approver, "", "", "", "", true);
                                    }
                                }
                                ################################ notify payroll admin end ################################
                            }
                            // ############################### APPROVE ################################
                        } // end [if has notification and approvers turn to approve]
                    } else {
                        echo json_encode(array(
                            "success" => false,
                            "error" => "Invalid Attendance Details"
                        ));
                        return false;
                    }
                }
                
                $company_subdomain = $this->todo_timein->get_company_subdomain();
                if ($company_subdomain) {
                    notify_approver_timesheet_pending_emp($this->company_id, $this->emp_id, $this->psa_id, $company_subdomain);
                }
                
                echo json_encode(array(
                    "success" => true,
                    "error" => "The timesheet has been approved",
                    "split" => 'split'
                ));
                return true;
            }
            
            /*
             * $company_subdomain = $this->todo_timein->get_company_subdomain();
             * if ($company_subdomain) {
             * notify_approver_timesheet_pending_emp($this->company_id , $this->emp_id, $this->psa_id ,$company_subdomain);
             * }
             *
             *
             * echo json_encode(array(
             * "success" => true,
             * "error" => ""
             * ));
             * return true;
             */
        }
    }

    public function reject_timein()
    {
        if ($this->input->post('reject_timein')) {

            // check if the application is lock for filing
            $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id);
            if($get_lock_payroll_process_settings) {
                if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->approval_error
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->ts_approval_recalculation_err_msg
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->py_approval_recalculation_err_msg
                    ));
                    return false;
                }
            }


            $timesheet_ids = $this->input->post('employee_time_in_id');
            $is_split = $this->input->post('is_split');
            $timesheets_id = array();
            array_push($timesheets_id, $timesheet_ids);
            if (! $timesheets_id) {
                echo json_encode(array(
                    "success" => false,
                    "error" => "Could not find application."
                ));
                return true;
            }


            if ( ! $is_split) {
                foreach ($timesheets_id as $key => $val) {

                    $emp_timein = $this->todo_timein->get_employee_time_in($val);
                    $employee_details = get_employee_details_by_empid($emp_timein->emp_id);

                    // for new todo structure param
                    $check_holiday = $this->employee->get_holiday_date($emp_timein->date,$emp_timein->emp_id,$emp_timein->company_id);
                    if ($check_holiday) {
                        $check_if_holiday_approval = holiday_approval_settings($this->company_id);
                    } else {
                        $check_if_holiday_approval = false;
                    }

                    $check_if_enable_working_on_restday = check_if_enable_working_on_restday($emp_timein->company_id,$emp_timein->work_schedule_id);
                    if ($check_if_enable_working_on_restday || $emp_timein->work_schedule_id == "-1") {
                        $module_for_new_todo = "rd_ra";
                    } elseif ($check_if_holiday_approval) {
                        $module_for_new_todo = "holiday";
                    } else {
                        $module_for_new_todo = "hours";
                    }

                    if ($emp_timein) {
                        $curr_approver = "";
                        $curr_approver_account_id = "";
                        $curr_level = $emp_timein->level;

                        $this->db->where("emp_id",$emp_timein->emp_id);
                        $sql = $this->db->get("employee_payroll_information");
                        $row = $sql->row();
                        if($row){
                            if($emp_timein->flag_add_logs == 0)
                                $leave_approval_grp = $row->attendance_adjustment_approval_grp;
                            elseif($emp_timein->flag_add_logs==1)
                                $leave_approval_grp = $row->add_logs_approval_grp;
                            elseif ($emp_timein->flag_add_logs==2)
                                $leave_approval_grp = $row->location_base_login_approval_grp;
                        }

                        // get workforce notification settings
                        $approval_group_notification = get_notify_settings($leave_approval_grp, $this->company_id);

                        // get approver's current level
                        $approver_emp_id = get_approver_emp_id_via_account_id($this->account_id);
                        $approver_emp_id = ($this->session->userdata("user_type_id") == 2) ? "-99{$this->company_id}" : $approver_emp_id;
                        $approver_level = get_current_approver_level($leave_approval_grp ,$approver_emp_id,$this->company_id);
                        // check leave is pending

                        $psa_id = $this->session->userdata('psa_id');
                        $is_emp_timein_pending = $this->check_pending($val,$this->company_id);

                        if($approval_group_notification && $is_emp_timein_pending){
                            $hours_category="";
                            $workflow_type = "";
                            $flag="";
                            if($emp_timein->flag_add_logs==0){
                                $hours_category = "attendance adjustment";
                                $workflow_type = $hours_category;
                                $flag = 0;
                            }elseif($emp_timein->flag_add_logs==1){
                                $hours_category = "add logs";
                                $workflow_type = "add timesheet";
                                $flag = 1;
                            }elseif($emp_timein->flag_add_logs==2){
                                $hours_category = "location base login";
                                $workflow_type = "mobile clock in";
                                $flag = 2;
                            }

                            $workflow_approved_by_data = array(
                                'application_id' => $val,
                                'approver_id'   => $approver_emp_id,
                                'workflow_level'=> $curr_level,
                                'workflow_type' => $workflow_type
                            );
                            
                            $this->db->insert('workflow_approved_by',$workflow_approved_by_data);

                            $hours_approver = $this->timeins->get_approver_name_hours($emp_timein->emp_id,$emp_timein->comp_id,$emp_timein->flag_add_logs);

                            $emp_name = ucwords($employee_details->first_name." ".$employee_details->last_name);
                            $emp_email = $employee_details->email;
                            $emp_account_id = $employee_details->account_id;

                            if($this->session->userdata("user_type_id") == 2){
                                $owner_approver = get_approver_owner_info($this->company_id);
                                $curr_approver = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                                $curr_approver_account_id = $this->account_id;
                            } else {
                                foreach ($hours_approver as $sa){
                                    if($sa->level == $approver_level && $sa->emp_id == $approver_emp_id){
                                        $curr_approver = ucwords($sa->first_name." ".$sa->last_name);
                                        $curr_approver_account_id = $sa->account_id;
                                    }
                                }
                            }

                            // REJECT LEAVE CODE HERE!
                            $rejected = false;
                            $tardiness_rule_migrated_v3 = false;
                            // ------------------------------- Time in REJECT START ------------------------------- //
                            $time_info = $this->timeins->timeins_info($val,$emp_timein->company_id);
                            
                            if($flag == 0) {
                                $time_in_status = null;
                            } else {
                                $time_in_status = "reject";
                            }

                            if($time_info){     
                                $tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($this->company_id,$time_info->work_schedule_id);
                                
                                if($flag == 0) {
                                    $fields = array(
                                        "time_in_status" => $time_in_status,
                                        "last_source" => null,
                                        "approval_date" => date("Y-m-d H:i:s")
                                    );
                                } else {
                                    $fields = array(
                                        "time_in_status" => $time_in_status,
                                        "approval_date" => date("Y-m-d H:i:s")
                                    );
                                }

                                $where = array(
                                    "employee_time_in_id"=>$val,
                                    "comp_id"=>$emp_timein->company_id
                                );
                                
                                $this->timeins->update_field("employee_time_in",$fields,$where);
                                $rejected = true;
                                
                                # activity logs
                                iactivity_logs($emp_timein->company_id,' has rejected on employee adjustments of ('.$time_info->last_name.",".$time_info->first_name.')','to do');
                                # end activity logs
                            }

                            if($rejected){
                                $approver_emp_id_check = ($approver_emp_id == "-99{$this->company_id}") ? "" : $approver_emp_id;

                                foreach ($hours_approver as $nextapr){
                                    if($nextapr->level >= $approver_level && $nextapr->emp_id != $approver_emp_id_check){
                                        $next_appr_name = ucwords($nextapr->first_name." ".$nextapr->last_name);
                                        $next_appr_account_id = $nextapr->account_id;
                                        $next_appr_email = $nextapr->email;
                                        $next_appr_emp_id = $nextapr->emp_id;

                                        if($nextapr->emp_id == ""){
                                            $owner_approver = get_approver_owner_info($this->company_id);
                                            $next_appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                                            $next_appr_account_id = $owner_approver->account_id;
                                            $next_appr_email = $owner_approver->email;
                                            $next_appr_emp_id = "";
                                        }

                                        
                                        ###check email settings if enabled###
                                        if($nextapr->ns_timesheet_adj_email_flag == "yes"){
                                            // notify next approver via email
                                            $this->send_hours_reject_notifcation($val, $this->company_id, $emp_timein->emp_id, $next_appr_email, $next_appr_name, $curr_approver, "", "", "", $is_split, $tardiness_rule_migrated_v3);
                                        }
                                        ###end checking email settings if enabled###

                                        // notify next approver via sms
                                        if($approval_group_notification->sms_notification == "yes"){
                                            $sms_message = "".$hours_category." application filed by {$emp_name} has been rejected by {$curr_approver}.";
                                            send_this_sms_global($this->company_id, $next_appr_account_id,$sms_message,$psa_id,false);
                                        }

                                        // notify next approver via message board
                                        if($approval_group_notification->message_board_notification == "yes"){
                                            $next_appr_notif_message = "Your ".$hours_category." application has been rejected by {$curr_approver}.";
                                            send_to_message_board($psa_id, $next_appr_emp_id, $curr_approver_account_id,$emp_timein->company_id, $next_appr_notif_message, "system");
                                        }
                                    }
                                }

                                inactive_todo_data($this->company_id,$emp_timein->emp_id,$emp_timein->approval_time_in_id,$module_for_new_todo);

                                ################################ notify staff start ################################
                                if($approval_group_notification->notify_staff == "yes"){
                                    // notify staff via email
                                    $this->send_hours_reject_notifcation($val, $this->company_id, $emp_timein->emp_id, $emp_email, $emp_name, $curr_approver, "last", "", "", $is_split, $tardiness_rule_migrated_v3, "Yes");
                                    // notify next via sms
                                    if($approval_group_notification->sms_notification == "yes"){
                                        $sms_message = "Your {$hours_category} application has been rejected by {$curr_approver}.";
                                        send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                    }

                                    // notify staff via message board
                                    if($approval_group_notification->message_board_notification == "yes"){
                                        $emp_notif_message = "Your {$hours_category} application has been rejected by {$curr_approver}.";
                                        send_to_message_board($psa_id, $emp_timein->emp_id, $curr_approver_account_id, $this->company_id, $emp_notif_message, "system");
                                    }
                                }
                                ################################ notify staff end ################################


                                ################################ notify payroll admin start ################################
                                if($approval_group_notification->notify_payroll_admin == "yes"){
                                    // HRs
                                    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($psa_id);
                                    if($payroll_admin_hr){
                                        foreach ($payroll_admin_hr as $pahr){
                                            $pahr_email = $pahr->email;
                                            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);
                                            
                                            ###check email settings if enabled###
                                            if($pahr->ns_timesheet_adj_email_flag == "yes"){
                                                $this->send_hours_reject_notifcation($val, $this->company_id, $emp_timein->emp_id, $pahr_email, $pahr_name, $curr_approver, "last", "", "", $is_split, $tardiness_rule_migrated_v3);
                                            }
                                            ###end checking email settings if enabled###
                                        }
                                    }
                                    
                                    // Owner
                                    $pa_owner = get_approver_owner_info($this->company_id);
                                    if($pa_owner){
                                        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
                                        $pa_owner_email = $pa_owner->email;
                                        $pa_owner_account_id = $pa_owner->account_id;
                                        
                                        ###check email settings if enabled###
                                        if($pa_owner->ns_timesheet_adj_email_flag == "yes"){
                                            $this->send_hours_reject_notifcation($val, $this->company_id, $emp_timein->emp_id, $pa_owner_email, $pa_owner_name, $curr_approver, "last", "", "", $is_split, $tardiness_rule_migrated_v3);
                                        }
                                        ###end checking email settings if enabled###                                    
                                    }
                                }
                                ################################ notify payroll admin end ################################
                            }


                        }// end [if has approval group notification and leave application is pending]
                    }else{
                        echo json_encode(array(
                            "success" => false,
                            "error" => "Invalid Attendance Details"
                        ));
                        return false;
                    }

                }
            } // end $is_split

            // SPLIT SCHEDULE IS NOT MAINTAINED!!!
            if ($is_split) {

                foreach ($timesheets_id as $key => $val) {

                    $split_emp_timein = $this->todo_timein->get_employee_split_time_in($val);
                    $employee_details = get_employee_details_by_empid($split_emp_timein->emp_id);

                    if($split_emp_timein){

                        $curr_approver = "";
                        $curr_approver_account_id = "";
                        $curr_level = $split_emp_timein->level;
                        
                        $yest_list = $this->elm->list_of_blocks($split_emp_timein->date,$split_emp_timein->emp_id,$split_emp_timein->work_schedule_id,$this->company_id);
                        $first_sched = reset($yest_list);
                        $last_sched = max($yest_list);

                        $this->db->where("emp_id",$split_emp_timein->emp_id);
                        $sql = $this->db->get("employee_payroll_information");
                        $row = $sql->row();
                        
                        if($row){
                            if($split_emp_timein->flag_add_logs == 0)
                                $leave_approval_grp = $row->attendance_adjustment_approval_grp;
                            elseif($split_emp_timein->flag_add_logs==1)
                                $leave_approval_grp = $row->add_logs_approval_grp;
                            elseif ($split_emp_timein->flag_add_logs==2)
                                $leave_approval_grp = $row->location_base_login_approval_grp;
                        }

                        // get workforce notification settings
                        $approval_group_notification = get_notify_settings($leave_approval_grp, $this->company_id);

                        // get approver's current level
                        $approver_emp_id = get_approver_emp_id_via_account_id($this->account_id);
                        $approver_emp_id = ($this->session->userdata("user_type_id") == 2) ? "-99{$this->company_id}" : $approver_emp_id;
                        $approver_level = get_current_approver_level($leave_approval_grp ,$approver_emp_id,$this->company_id);
                        // check leave is pending

                        $psa_id = $this->session->userdata('psa_id');

                        $is_emp_timein_pending = $this->check_pending_for_split($val,$this->company_id);

                        if($approval_group_notification && $is_emp_timein_pending){

                            $hours_category="";
                            $workflow_type = "";
                            $flag="";
                            if($split_emp_timein->flag_add_logs==0){
                                $hours_category = "attendance adjustment";
                                $workflow_type = $hours_category;
                                $flag = 0;
                            }elseif($split_emp_timein->flag_add_logs==1){
                                $hours_category = "add logs";
                                $workflow_type = "add timesheet";
                                $flag = 1;
                            }elseif($split_emp_timein->flag_add_logs==2){
                                $hours_category = "location base login";
                                $workflow_type = "mobile clock in";
                                $flag = 2;
                            }

                            $workflow_approved_by_data = array(
                                'application_id' => $val,
                                'approver_id'   => $approver_emp_id,
                                'workflow_level'=> $curr_level,
                                'workflow_type' => $workflow_type
                            );
                            
                            $this->db->insert('workflow_approved_by',$workflow_approved_by_data);
                            
                            $hours_approver = $this->timeins->get_approver_name_hours($split_emp_timein->emp_id,$split_emp_timein->comp_id,$split_emp_timein->flag_add_logs);

                            $emp_name = ucwords($employee_details->first_name." ".$employee_details->last_name);
                            $emp_email = $employee_details->email;
                            $emp_account_id = $employee_details->account_id;

                            if($this->session->userdata("user_type_id") == 2){
                                $owner_approver = get_approver_owner_info($this->company_id);
                                $curr_approver = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                                $curr_approver_account_id = $this->account_id;
                            }
                            else{
                                foreach ($hours_approver as $sa){
                                    if($sa->level == $approver_level && $sa->emp_id == $approver_emp_id){
                                        $curr_approver = ucwords($sa->first_name." ".$sa->last_name);
                                        $curr_approver_account_id = $sa->account_id;
                                    }
                                }
                            }

                            // REJECT LEAVE CODE HERE!
                            $rejected = false;

                            // ------------------------------- Time in REJECT START ------------------------------- //
                            $split_timeins_info = $this->timeins->split_timeins_info($val,$split_emp_timein->company_id);

                            if($flag == 0) {
                                $time_in_status = null;
                            } else {
                                $time_in_status = "reject";
                            }

                            if($split_timeins_info){

                                if($split_emp_timein->flag_add_logs == 1) {

                                    $get_all_child_block = $this->employee->get_all_split_logs_already_exist($split_timeins_info->emp_id,$split_timeins_info->comp_id,$split_timeins_info->employee_time_in_id);
                                    if($get_all_child_block) {
                                        $block_count = count($get_all_child_block);
                                        foreach ($get_all_child_block as $row_b) {
                                            if($split_timeins_info->schedule_blocks_id == $row_b->schedule_blocks_id) {
                                                if($split_timeins_info->schedule_blocks_id == $first_sched->schedule_blocks_id){
                                                    if($block_count == 3) {
                                                        $fields = array(
                                                            "time_in" => $get_all_child_block[1]->time_in,
                                                            "time_out" => $get_all_child_block[2]->time_out,
                                                            "total_hours_required" => $get_all_child_block[1]->total_hours_required + $get_all_child_block[2]->total_hours_required,
                                                            "late_min" => $get_all_child_block[1]->late_min + $get_all_child_block[2]->late_min,
                                                            "overbreak_min" => $get_all_child_block[1]->overbreak_min + $get_all_child_block[2]->overbreak_min,
                                                            "tardiness_min" => $get_all_child_block[1]->tardiness_min + $get_all_child_block[2]->tardiness_min,
                                                            "undertime_min" => $get_all_child_block[1]->undertime_min + $get_all_child_block[2]->undertime_min,
                                                            "absent_min" => $get_all_child_block[1]->absent_min + $get_all_child_block[2]->absent_min,
                                                            "change_log_time_in" => $get_all_child_block[1]->time_in,
                                                            "change_log_time_out" => $get_all_child_block[2]->time_out,
                                                            "change_log_total_hours_required" => $get_all_child_block[1]->total_hours_required + $get_all_child_block[2]->total_hours_required,
                                                            "change_log_tardiness_min" => $get_all_child_block[1]->tardiness_min + $get_all_child_block[2]->tardiness_min,
                                                            "change_log_undertime_min" => $get_all_child_block[1]->undertime_min + $get_all_child_block[2]->undertime_min,
                                                        );
                                                    } elseif($block_count == 2) {
                                                        $fields = array(
                                                            "time_in" => $get_all_child_block[1]->time_in,
                                                            "time_out" => $get_all_child_block[1]->time_out,
                                                            "total_hours_required" => $get_all_child_block[1]->total_hours_required,
                                                            "late_min" => $get_all_child_block[1]->late_min,
                                                            "overbreak_min" => $get_all_child_block[1]->overbreak_min,
                                                            "tardiness_min" => $get_all_child_block[1]->tardiness_min,
                                                            "undertime_min" => $get_all_child_block[1]->undertime_min,
                                                            "absent_min" => $get_all_child_block[1]->absent_min,
                                                            "change_log_time_in" => $get_all_child_block[1]->time_in,
                                                            "change_log_time_out" => $get_all_child_block[1]->time_out,
                                                            "change_log_total_hours_required" => $get_all_child_block[1]->total_hours_required,
                                                            "change_log_tardiness_min" => $get_all_child_block[1]->tardiness_min,
                                                            "change_log_undertime_min" => $get_all_child_block[1]->undertime_min,
                                                        );
                                                    } else {
                                                        $fields = array(
                                                            "time_in_status" => "reject"
                                                        );
                                                    }
                                                    
                                                    $where = array(
                                                        "employee_time_in_id" => $split_timeins_info->employee_time_in_id,
                                                        "comp_id" => $split_timeins_info->comp_id
                                                    );
                                                    
                                                    $this->timeins->update_field("employee_time_in",$fields,$where);
                                                    break;
                                                } elseif($split_timeins_info->schedule_blocks_id == $last_sched->schedule_blocks_id) {
                                                    if($block_count == 3) {
                                                        $fields = array(
                                                            "time_in" => $get_all_child_block[0]->time_in,
                                                            "time_out" => $get_all_child_block[1]->time_out,
                                                            "total_hours_required" => $get_all_child_block[0]->total_hours_required + $get_all_child_block[1]->total_hours_required,
                                                            "late_min" => $get_all_child_block[0]->late_min + $get_all_child_block[1]->late_min,
                                                            "overbreak_min" => $get_all_child_block[0]->overbreak_min + $get_all_child_block[1]->overbreak_min,
                                                            "tardiness_min" => $get_all_child_block[0]->tardiness_min + $get_all_child_block[1]->tardiness_min,
                                                            "undertime_min" => $get_all_child_block[0]->undertime_min + $get_all_child_block[1]->undertime_min,
                                                            "absent_min" => $get_all_child_block[0]->absent_min + $get_all_child_block[1]->absent_min,
                                                            "change_log_time_in" => $get_all_child_block[0]->time_in,
                                                            "change_log_time_out" => $get_all_child_block[1]->time_out,
                                                            "change_log_total_hours_required" => $get_all_child_block[0]->total_hours_required + $get_all_child_block[1]->total_hours_required,
                                                            "change_log_tardiness_min" => $get_all_child_block[0]->tardiness_min + $get_all_child_block[1]->tardiness_min,
                                                            "change_log_undertime_min" => $get_all_child_block[0]->undertime_min + $get_all_child_block[1]->undertime_min,
                                                        );
                                                    } elseif($block_count == 2) {
                                                        $fields = array(
                                                            "time_in" => $get_all_child_block[0]->time_in,
                                                            "time_out" => $get_all_child_block[0]->time_out,
                                                            "total_hours_required" => $get_all_child_block[0]->total_hours_required,
                                                            "late_min" => $get_all_child_block[0]->late_min,
                                                            "overbreak_min" => $get_all_child_block[0]->overbreak_min,
                                                            "tardiness_min" => $get_all_child_block[0]->tardiness_min,
                                                            "undertime_min" => $get_all_child_block[0]->undertime_min,
                                                            "absent_min" => $get_all_child_block[0]->absent_min,
                                                            "change_log_time_in" => $get_all_child_block[0]->time_in,
                                                            "change_log_time_out" => $get_all_child_block[0]->time_out,
                                                            "change_log_total_hours_required" => $get_all_child_block[0]->total_hours_required,
                                                            "change_log_tardiness_min" => $get_all_child_block[0]->tardiness_min,
                                                            "change_log_undertime_min" => $get_all_child_block[0]->undertime_min
                                                        );
                                                    } else {
                                                        $fields = array(
                                                            "time_in_status" => "reject"
                                                        );
                                                    }
                                                    
                                                    $where = array(
                                                        "employee_time_in_id" => $split_timeins_info->employee_time_in_id,
                                                        "comp_id" => $split_timeins_info->comp_id
                                                    );
                                                    
                                                    $this->timeins->update_field("employee_time_in",$fields,$where);
                                                    break;
                                                } else {
                                                    if($block_count == 3) {
                                                        $fields = array(
                                                            "time_in" => $get_all_child_block[0]->time_in,
                                                            "time_out" => $get_all_child_block[2]->time_out,
                                                            "total_hours_required" => $get_all_child_block[0]->total_hours_required + $get_all_child_block[2]->total_hours_required,
                                                            "late_min" => $get_all_child_block[0]->late_min + $get_all_child_block[2]->late_min,
                                                            "overbreak_min" => $get_all_child_block[0]->overbreak_min + $get_all_child_block[2]->overbreak_min,
                                                            "tardiness_min" => $get_all_child_block[0]->tardiness_min + $get_all_child_block[2]->tardiness_min,
                                                            "undertime_min" => $get_all_child_block[0]->undertime_min + $get_all_child_block[2]->undertime_min,
                                                            "absent_min" => $get_all_child_block[0]->absent_min + $get_all_child_block[2]->absent_min,
                                                            "change_log_time_in" => $get_all_child_block[0]->time_in,
                                                            "change_log_time_out" => $get_all_child_block[2]->time_out,
                                                            "change_log_total_hours_required" => $get_all_child_block[0]->total_hours_required + $get_all_child_block[2]->total_hours_required,
                                                            "change_log_tardiness_min" => $get_all_child_block[0]->tardiness_min + $get_all_child_block[2]->tardiness_min,
                                                            "change_log_undertime_min" => $get_all_child_block[0]->undertime_min + $get_all_child_block[2]->undertime_min
                                                        );
                                                    } elseif($block_count == 2) {
                                                        $fields = array(
                                                            "time_in" => $get_all_child_block[0]->time_in,
                                                            "time_out" => $get_all_child_block[0]->time_out,
                                                            "total_hours_required" => $get_all_child_block[0]->total_hours_required,
                                                            "late_min" => $get_all_child_block[0]->late_min,
                                                            "overbreak_min" => $get_all_child_block[0]->overbreak_min,
                                                            "tardiness_min" => $get_all_child_block[0]->tardiness_min,
                                                            "undertime_min" => $get_all_child_block[0]->undertime_min,
                                                            "absent_min" => $get_all_child_block[0]->absent_min,
                                                            "change_log_time_in" => $get_all_child_block[0]->time_in,
                                                            "change_log_time_out" => $get_all_child_block[0]->time_out,
                                                            "change_log_total_hours_required" => $get_all_child_block[0]->total_hours_required,
                                                            "change_log_tardiness_min" => $get_all_child_block[0]->tardiness_min,
                                                            "change_log_undertime_min" => $get_all_child_block[0]->undertime_min
                                                        );
                                                    } else {
                                                        $fields = array(
                                                            "time_in_status" => "reject"
                                                        );
                                                    }
                                                    
                                                    $where = array(
                                                        "employee_time_in_id" => $split_timeins_info->employee_time_in_id,
                                                        "comp_id" => $split_timeins_info->comp_id
                                                    );
                                                    
                                                    $this->timeins->update_field("employee_time_in",$fields,$where);
                                                    break;
                                                }
                                            }
                                        }
                                    }

                                }

                                if($flag == 0) {
                                    $fields = array(
                                        "time_in_status" => null
                                    );
                                } else {
                                    $fields = array(
                                        "time_in_status" => $time_in_status
                                    );
                                }

                                $where = array(
                                    "schedule_blocks_time_in_id"=>$val,
                                    "comp_id"=>$split_emp_timein->company_id
                                );
                                $this->timeins->update_field("schedule_blocks_time_in",$fields,$where);


                                $get_all_child_block = $this->employee->get_all_child_block($split_timeins_info->emp_id,$split_timeins_info->comp_id,$split_timeins_info->employee_time_in_id);
                                if(!$get_all_child_block) {
                                    if($flag == 0) {
                                        $fields = array(
                                            "time_in_status" => null,
                                            "split_status" => "reject"
                                        );
                                    } else {
                                        $fields = array(
                                            "time_in_status" => $time_in_status,
                                            "split_status" => "reject"
                                        );
                                    }
                                    
                                    $where = array(
                                        "employee_time_in_id"=>$split_timeins_info->employee_time_in_id,
                                        "comp_id"=>$split_timeins_info->comp_id
                                    );
                                    
                                    $this->timeins->update_field("employee_time_in",$fields,$where);
                                }
                                
                                $rejected = true;
                                # activity logs
                                iactivity_logs($split_emp_timein->company_id,' has rejected on employee adjustments of ('.$split_timeins_info->last_name.",".$split_timeins_info->first_name.')','to do');
                                # end activity logs


                            } // end $split_timeins_info

                            if($rejected){

                                $approver_emp_id_check = ($approver_emp_id == "-99{$this->company_id}") ? "" : $approver_emp_id;
                                foreach ($hours_approver as $nextapr){
                                    if($nextapr->level >= $approver_level && $nextapr->emp_id != $approver_emp_id_check){

                                        $next_appr_name = ucwords($nextapr->first_name." ".$nextapr->last_name);
                                        $next_appr_account_id = $nextapr->account_id;
                                        $next_appr_email = $nextapr->email;
                                        $next_appr_emp_id = $nextapr->emp_id;

                                        if($nextapr->emp_id == ""){
                                            $owner_approver = get_approver_owner_info($this->company_id);
                                            $next_appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                                            $next_appr_account_id = $owner_approver->account_id;
                                            $next_appr_email = $owner_approver->email;
                                            $next_appr_emp_id = "";
                                        }

                                        // notify next approver via email
                                        $this->send_hours_reject_notifcation($val, $this->company_id, $split_emp_timein->emp_id, $next_appr_email, $next_appr_name, $curr_approver, "", "", "", $split_emp_timein->work_schedule_id);

                                        // notify next approver via sms
                                        if($approval_group_notification->sms_notification == "yes"){
                                            #$sms_message = "Your ".$hours_category." application has been rejected by {$curr_approver}";
                                            $sms_message = "".$hours_category." application filed by {$emp_name} has been rejected by {$curr_approver}.";
                                            send_this_sms_global($this->company_id, $next_appr_account_id,$sms_message,$psa_id,false);
                                        }

                                        // notify next approver via message board
                                        if($approval_group_notification->message_board_notification == "yes"){
                                            $next_appr_notif_message = "Your ".$hours_category." application has been rejected by {$curr_approver}.";
                                            send_to_message_board($psa_id, $next_appr_emp_id, $curr_approver_account_id,$this->company_id, $next_appr_notif_message, "system");
                                        }

                                    }
                                }

                                ################################ notify staff start ################################
                                if($approval_group_notification->notify_staff == "yes"){
                                    // notify staff via email
                                    $this->send_hours_reject_notifcation($val, $this->company_id, $split_emp_timein->emp_id, $emp_email, $emp_name, $curr_approver, "last", "", "", $split_emp_timein->work_schedule_id, false, "Yes");
                                    // notify next via sms
                                    if($approval_group_notification->sms_notification == "yes"){
                                        $sms_message = "Your {$hours_category} application has been rejected by {$curr_approver}.";
                                        send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                    }

                                    // notify staff via message board
                                    if($approval_group_notification->message_board_notification == "yes"){
                                        $emp_notif_message = "Your {$hours_category} application has been rejected by {$curr_approver}.";
                                        send_to_message_board($psa_id, $split_emp_timein->emp_id, $curr_approver_account_id, $this->company_id, $emp_notif_message, "system");
                                    }
                                }
                                ################################ notify staff end ################################
                                
                                ################################ notify payroll admin start ################################
                                if($approval_group_notification->notify_payroll_admin == "yes"){
                                    // HRs
                                    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($psa_id);
                                    if($payroll_admin_hr){
                                        foreach ($payroll_admin_hr as $pahr){
                                            $pahr_email = $pahr->email;
                                            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);
                                            
                                            $this->send_hours_reject_notifcation($val, $this->company_id, $split_emp_timein->emp_id, $pahr_email, $pahr_name, $curr_approver, "last", "", "", $split_emp_timein->work_schedule_id);
                                        }
                                    }
                                    
                                    // Owner
                                    $pa_owner = get_approver_owner_info($this->company_id);
                                    if($pa_owner){
                                        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
                                        $pa_owner_email = $pa_owner->email;
                                        $pa_owner_account_id = $pa_owner->account_id;
                                        
                                        $this->send_hours_reject_notifcation($val, $this->company_id, $split_emp_timein->emp_id, $pa_owner_email, $pa_owner_name, $curr_approver, "last", "", "", $split_emp_timein->work_schedule_id);
                                    }
                                }
                                ################################ notify payroll admin end ################################

                            }

                        } // end if $approval_group_notification && $is_emp_timein_pending

                    }else{
                        echo json_encode(array(
                            "success" => false,
                            "error" => "Invalid Attendance Details"
                        ));
                        return false;
                    }

                } // foreach
            }

            // notify if still have pending application..
            $company_subdomain = $this->todo_timein->get_company_subdomain();
            if ($company_subdomain) {
                notify_approver_timesheet_pending_emp($this->company_id, $this->emp_id, $this->psa_id, $company_subdomain);
            }
            
            echo json_encode(array(
                "success" => true,
                "error" => "The timesheet has been rejected."
            ));
            return true;
        }
    }

    public function reject_timein_OLD()
    {
        if ($this->input->post('reject_timein')) {
            
            $timesheets_id = $this->input->post('employee_time_in_id');
            $is_split = $this->input->post('is_split');
            
            if (! $is_split) {
                foreach ($timesheets_id as $key => $val) {
                    
                    $emp_timein = $this->todo_timein->get_employee_time_in($val);
                    $employee_details = get_employee_details_by_empid($emp_timein->emp_id);
                    $value = array(
                        "time_in_status" => "reject"
                    );
                    $w = array(
                        "employee_time_in_id" => $val,
                        "comp_id" => $emp_timein->comp_id
                    );
                    $this->db->where($w);
                    $update = $this->db->update("employee_time_in", $value);
                    
                    $value2 = array(
                        'approve_by_head' => "Yes",
                        'approve_by_hr' => "Yes"
                    );
                    $w2 = array(
                        'time_in_id' => $val
                    
                    );
                    // $this->db->where($w2);
                    // $this->db->update('approval_time_in', $value2);
                    
                    if ($emp_timein) {
                        $hours_notification = $this->get_hours_notification_settings($emp_timein->comp_id);
                        
                        if ($hours_notification) {
                            $staff_notification = $this->get_hours_alert_staff($emp_timein->comp_id);
                            $hours_approver = $this->timeins->get_approver_name_hours($emp_timein->emp_id, $emp_timein->comp_id);
                            $company_id = $emp_timein->company_id;
                            $curr_approver = "";
                            $curr_approver_account_id = "";
                            $curr_level = $emp_timein->level;
                            
                            if ($hours_approver) {
                                $psa_id = $this->session->userdata('psa_id');
                                
                                $emp_name = ucwords($employee_details->first_name . " " . $employee_details->last_name);
                                $emp_email = $employee_details->email;
                                $emp_account_id = $employee_details->account_id;
                                
                                foreach ($hours_approver as $la) {
                                    if ($la->level == $curr_level) {
                                        $curr_approver = ucwords($la->first_name . " " . $la->last_name);
                                        $curr_approver_account_id = $la->account_id;
                                    }
                                }
                                // xx
                                // REJECT LEAVE CODE HERE!
                                
                                // ------------------------------- Time in REJECT START ------------------------------- //
                                $time_info = $this->timeins->timeins_info($val, $emp_timein->company_id);
                                if ($time_info) {
                                    $fields = array(
                                        "time_in_status" => "reject"
                                    );
                                    $where = array(
                                        "employee_time_in_id" => $val,
                                        "comp_id" => $emp_timein->company_id
                                    );
                                    $this->timeins->update_field("employee_time_in", $fields, $where);
                                    // activity logs
                                    iactivity_logs($emp_timein->company_id, ' has rejected the employee adjustments of (' . $time_info->last_name . "," . $time_info->first_name . ')', 'to do');
                                    // end activity logs
                                }
                                // ------------------------------- Time in REJECT END ------------------------------- //
                                
                                foreach ($hours_approver as $la) {
                                    $appr_name = ucwords($la->first_name . " " . $la->last_name);
                                    $appr_account_id = $la->account_id;
                                    $appr_email = $la->email;
                                    $appr_id = $la->emp_id;
                                    
                                    if ($la->level > $curr_level) {
                                        // SEND NOTIFICATION WITHOUT LINK TO THE NEXT APPROVER
                                        $this->send_hours_reject_notifcation($val, $company_id, $appr_id, $appr_email, $appr_name, $curr_approver, "last", "", "");
                                        if ($hours_notification->sms == "yes") {}
                                        if ($hours_notification->twitter == "yes") {
                                            $check_twitter_acount = $this->agm->check_twitter_acount($appr_account_id);
                                            if ($check_twitter_acount) {
                                                $message = "Your application has been rejected by {$curr_approver}.";
                                                $recipient_account = $check_twitter_acount->twitter;
                                                $this->tweetontwitter($curr_approver_account_id, $message, $recipient_account);
                                            }
                                        }
                                        if ($hours_notification->facebook == "yes") {
                                            // coming soon
                                        }
                                        if ($hours_notification->message_board == "yes") {
                                            $next_appr_notif_message = "Your application has been rejected by {$curr_approver}.";
                                            send_to_message_board($psa_id, $appr_id, $curr_approver_account_id, $emp_timein->company_id, $next_appr_notif_message, "system");
                                        }
                                        /*
                                         * echo json_encode(array(
                                         * "reject" => true,
                                         * "level" => $la->level,
                                         * "function" => "send notifications to next approver",
                                         * "other_approver" => $appr_name,
                                         * "current_approver" => $curr_approver
                                         * ));
                                         * echo "<br>";
                                         */
                                    }
                                }
                                
                                if ($staff_notification) {
                                    // email to staff
                                    $this->send_hours_reject_notifcation($val, $company_id, $emp_timein->emp_id, $emp_email, $emp_name, $curr_approver, "last", "", "");
                                    if ($staff_notification->sms == "yes") {}
                                    if ($staff_notification->twitter == "yes") {
                                        $check_twitter_acount = $this->agm->check_twitter_acount($emp_account_id);
                                        if ($check_twitter_acount) {
                                            $message = "Your application has been rejected by {$curr_approver}.";
                                            $recipient_account = $check_twitter_acount->twitter;
                                            $this->tweetontwitter($curr_approver_account_id, $message, $recipient_account);
                                        }
                                    }
                                    if ($staff_notification->facebook == "yes") {
                                        // coming soon
                                    }
                                    if ($staff_notification->message_board == "yes") {
                                        $emp_notif_message = "Your application has been rejected by {$curr_approver}.";
                                        send_to_message_board($psa_id, $emp_timein->emp_id, $curr_approver_account_id, $emp_timein->company_id, $emp_notif_message, "system");
                                    }
                                }
                            } else {
                                echo json_encode(array(
                                    "success" => false,
                                    "error" => "No Approvers Found"
                                ));
                                return false;
                            }
                        } else {
                            echo json_encode(array(
                                "success" => false,
                                "error" => "No Hours Notification Settings Found"
                            ));
                            return false;
                        }
                    } else {
                        echo json_encode(array(
                            "success" => false,
                            "error" => "Invalid Attendance Details"
                        ));
                        return false;
                    }
                }
                echo json_encode(array(
                    "success" => true,
                    "error" => "The timesheet has been rejected"
                ));
                return true;
            }
            
            if ($is_split) {
                
                if ($timesheets_id) {
                    foreach ($timesheets_id as $key => $val) {
                        $split_emp_timein = $this->todo_timein->get_employee_split_time_in($val);
                        $employee_details = get_employee_details_by_empid($split_emp_timein->emp_id);
                        
                        // for split rejection
                        if ($split_emp_timein) { // start [leave information]
                            $curr_approver = "";
                            $curr_approver_account_id = "";
                            $curr_level = $split_emp_timein->level;
                            
                            $this->db->where("emp_id", $split_emp_timein->emp_id);
                            $sql = $this->db->get("employee_payroll_information");
                            $row = $sql->row();
                            if ($row) {
                                if ($split_emp_timein->flag_add_logs == 0)
                                    $leave_approval_grp = $row->attendance_adjustment_approval_grp;
                                elseif ($split_emp_timein->flag_add_logs == 1)
                                    $leave_approval_grp = $row->add_logs_approval_grp;
                                elseif ($split_emp_timein->flag_add_logs == 2)
                                    $leave_approval_grp = $row->location_base_login_approval_grp;
                            }
                            // get workforce notification settings
                            $approval_group_notification = get_notify_settings($leave_approval_grp, $this->company_id);
                            
                            // get approver's current level
                            $approver_emp_id = get_approver_emp_id_via_account_id($this->account_id);
                            $approver_emp_id = ($this->session->userdata("user_type_id") == 2) ? "-99{$this->company_id}" : $approver_emp_id;
                            $approver_level = get_current_approver_level($leave_approval_grp, $approver_emp_id, $this->company_id);
                            // check leave is pending
                            
                            $psa_id = $this->session->userdata('psa_id');
                            
                            $is_emp_timein_pending = $this->check_pending_for_split($val, $this->company_id);
                            
                            if ($approval_group_notification && $is_emp_timein_pending) { // start [if has approval group notification and leave application is pending]
                                
                                $hours_category = "";
                                $workflow_type = "";
                                $flag = "";
                                if ($split_emp_timein->flag_add_logs == 0) {
                                    $hours_category = "attendance adjustment";
                                    $workflow_type = $hours_category;
                                    $flag = 0;
                                } elseif ($split_emp_timein->flag_add_logs == 1) {
                                    $hours_category = "add logs";
                                    $workflow_type = "add timesheet";
                                    $flag = 1;
                                } elseif ($split_emp_timein->flag_add_logs == 2) {
                                    $hours_category = "location base login";
                                    $workflow_type = "mobile clock in";
                                    $flag = 2;
                                }
                                
                                $workflow_approved_by_data = array(
                                    'application_id' => $val,
                                    'approver_id' => $approver_emp_id,
                                    'workflow_level' => $curr_level,
                                    'workflow_type' => $workflow_type
                                );
                                
                                $this->db->insert('workflow_approved_by', $workflow_approved_by_data);
                                
                                $hours_approver = $this->timeins->get_approver_name_hours($split_emp_timein->emp_id, $split_emp_timein->comp_id, $split_emp_timein->flag_add_logs);
                                
                                $emp_name = ucwords($employee_details->first_name . " " . $employee_details->last_name);
                                $emp_email = $employee_details->email;
                                $emp_account_id = $employee_details->account_id;
                                
                                if ($this->session->userdata("user_type_id") == 2) {
                                    $owner_approver = get_approver_owner_info($this->company_id);
                                    $curr_approver = ucwords($owner_approver->first_name . " " . $owner_approver->last_name);
                                    $curr_approver_account_id = $this->account_id;
                                } else {
                                    foreach ($hours_approver as $sa) {
                                        if ($sa->level == $approver_level && $sa->emp_id == $approver_emp_id) {
                                            $curr_approver = ucwords($sa->first_name . " " . $sa->last_name);
                                            $curr_approver_account_id = $sa->account_id;
                                        }
                                    }
                                }
                                
                                // REJECT LEAVE CODE HERE!
                                $rejected = false;
                                
                                // ------------------------------- Time in REJECT START ------------------------------- //
                                $split_timeins_info = $this->timeins->split_timeins_info($val, $split_emp_timein->company_id);
                                
                                if ($flag == 0) {
                                    $time_in_status = null;
                                } else {
                                    $time_in_status = "reject";
                                }
                                
                                if ($split_timeins_info) {
                                    
                                    $fields = array(
                                        "time_in_status" => "reject"
                                    );
                                    
                                    $where = array(
                                        "schedule_blocks_time_in_id" => $val,
                                        "comp_id" => $split_emp_timein->company_id
                                    );
                                    
                                    $this->timeins->update_field("schedule_blocks_time_in", $fields, $where);
                                    
                                    // change status in employee time in
                                    $split_time_info = $this->timeins->split_timeins_info($val, $split_emp_timein->comp_id);
                                    $get_split_timein_except = $this->employee->get_split_timein_except($split_emp_timein->emp_id, $split_emp_timein->comp_id, $split_time_info->employee_time_in_id, $val);
                                    
                                    $if_approve = false;
                                    $if_pending = false;
                                    $if_default = false;
                                    if ($get_split_timein_except) {
                                        foreach ($get_split_timein_except as $gste) {
                                            if ($gste->time_in_status == "approved") {
                                                $if_approve = true;
                                            } elseif ($gste->time_in_status == "pending") {
                                                $if_pending = true;
                                            } elseif ($gste->time_in_status == null) {
                                                $if_default = true;
                                            }
                                        }
                                    }
                                    
                                    if ($flag == 0) {
                                        // if($approval_status == "reject") {
                                        if (! $if_approve && ! $if_pending && ! $if_default) {
                                            $fields1 = array(
                                                "time_in_status" => null,
                                                "split_status" => null,
                                                "last_source" => null
                                            );
                                            
                                            $where1 = array(
                                                "employee_time_in_id" => $split_time_info->employee_time_in_id,
                                                "comp_id" => $split_time_info->comp_id
                                            );
                                            
                                            $this->timeins->update_field("employee_time_in", $fields1, $where1);
                                        } else {
                                            if ($if_pending) {
                                                $fields1 = array(
                                                    "time_in_status" => "pending",
                                                    "split_status" => "pending"
                                                );
                                                
                                                $where1 = array(
                                                    "employee_time_in_id" => $split_time_info->employee_time_in_id,
                                                    "comp_id" => $split_time_info->comp_id
                                                );
                                                
                                                $this->timeins->update_field("employee_time_in", $fields1, $where1);
                                            } else {
                                                if ($if_approve) {
                                                    $fields1 = array(
                                                        "time_in_status" => "approved",
                                                        "split_status" => "approved"
                                                    );
                                                    
                                                    $where1 = array(
                                                        "employee_time_in_id" => $split_time_info->employee_time_in_id,
                                                        "comp_id" => $split_time_info->comp_id
                                                    );
                                                    
                                                    $this->timeins->update_field("employee_time_in", $fields1, $where1);
                                                } else {
                                                    $fields1 = array(
                                                        "time_in_status" => null,
                                                        "split_status" => null
                                                    );
                                                    
                                                    $where1 = array(
                                                        "employee_time_in_id" => $split_time_info->employee_time_in_id,
                                                        "comp_id" => $split_time_info->comp_id
                                                    );
                                                    
                                                    $this->timeins->update_field("employee_time_in", $fields1, $where1);
                                                }
                                            }
                                        }
                                    } else {
                                        if ($time_in_status == "reject") {
                                            $fields1 = array(
                                                "time_in_status" => "reject",
                                                "split_status" => "reject"
                                            );
                                            
                                            $where1 = array(
                                                "employee_time_in_id" => $val,
                                                "comp_id" => $split_time_info->comp_id
                                            );
                                            
                                            $this->timeins->update_field("employee_time_in", $fields1, $where1);
                                        }
                                    }
                                    
                                    $rejected = true;
                                    
                                    // activity logs
                                    $split_owner_fname = ($split_timeins_info->first_name) ? $split_timeins_info->first_name : '';
                                    $split_owner_lname = ($split_timeins_info->last_name) ? $split_timeins_info->last_name : ''; // $split_timeins_info->last_name.', '.$split_timeins_info->first_name;
                                    iactivity_logs($split_emp_timein->company_id, " has rejected the employee adjustments of ({$split_owner_lname}, {$split_owner_fname})", 'to do');
                                    // end activity logs
                                }
                                
                                if ($rejected) {
                                    $approver_emp_id_check = ($approver_emp_id == "-99{$this->company_id}") ? "" : $approver_emp_id;
                                    
                                    foreach ($hours_approver as $nextapr) {
                                        if ($nextapr->level >= $approver_level && $nextapr->emp_id != $approver_emp_id_check) {
                                            $next_appr_name = ucwords($nextapr->first_name . " " . $nextapr->last_name);
                                            $next_appr_account_id = $nextapr->account_id;
                                            $next_appr_email = $nextapr->email;
                                            $next_appr_emp_id = $nextapr->emp_id;
                                            
                                            if ($nextapr->emp_id == "") {
                                                $owner_approver = get_approver_owner_info($this->company_id);
                                                $next_appr_name = ucwords($owner_approver->first_name . " " . $owner_approver->last_name);
                                                $next_appr_account_id = $owner_approver->account_id;
                                                $next_appr_email = $owner_approver->email;
                                                $next_appr_emp_id = "";
                                            }
                                            
                                            // notify next approver via email
                                            $this->send_hours_reject_notifcation($val, $this->company_id, $split_emp_timein->emp_id, $next_appr_email, $next_appr_name, $curr_approver, "", "", "");
                                            
                                            // notify next approver via sms
                                            if ($approval_group_notification->sms_notification == "yes") {
                                                // $sms_message = "Your ".$hours_category." application has been rejected by {$curr_approver}";
                                                $sms_message = "" . $hours_category . " application filed by {$emp_name} has been rejected by {$curr_approver}.";
                                                send_this_sms_global($this->company_id, $next_appr_account_id, $sms_message, $psa_id, false);
                                            }
                                            
                                            // notify next approver via twitter
                                            if ($approval_group_notification->twitter_notification == "yes") {}
                                            
                                            // notify next approver via facebook
                                            if ($approval_group_notification->facebook_notification == "yes") {
                                                // not applicable
                                            }
                                            
                                            // notify next approver via message board
                                            if ($approval_group_notification->message_board_notification == "yes") {
                                                $next_appr_notif_message = "Your " . $hours_category . " application has been rejected by {$curr_approver}.";
                                                send_to_message_board($psa_id, $next_appr_emp_id, $curr_approver_account_id, $this->company_id, $next_appr_notif_message, "system");
                                            }
                                        }
                                    }
                                    
                                    // ############################### notify staff start ################################
                                    if ($approval_group_notification->notify_staff == "yes") {
                                        // notify staff via email
                                        $this->send_hours_reject_notifcation($val, $this->company_id, $split_emp_timein->emp_id, $emp_email, $emp_name, $curr_approver, "last", "", "");
                                        // notify next via sms
                                        if ($approval_group_notification->sms_notification == "yes") {
                                            $sms_message = "Your {$hours_category} application has been rejected by {$curr_approver}.";
                                            send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                        }
                                        
                                        // notify staff via twitter
                                        if ($approval_group_notification->twitter_notification == "yes") {
                                            $check_twitter_acount = $this->agm->check_twitter_acount($emp_account_id);
                                            if ($check_twitter_acount) {
                                                $message = "Your {$hours_category} application has been rejected by {$curr_approver}.";
                                                $recipient_account = $check_twitter_acount->twitter;
                                                $this->tweetontwitter($curr_approver_account_id, $message, $recipient_account);
                                            }
                                        }
                                        
                                        // notify staff via facebook
                                        if ($approval_group_notification->facebook_notification == "yes") {
                                            // not applicable
                                        }
                                        
                                        // notify staff via message board
                                        if ($approval_group_notification->message_board_notification == "yes") {
                                            $emp_notif_message = "Your {$hours_category} application has been rejected by {$curr_approver}.";
                                            send_to_message_board($psa_id, $split_emp_timein->emp_id, $curr_approver_account_id, $this->company_id, $emp_notif_message, "system");
                                        }
                                    }
                                    // ############################### notify staff end ################################
                                }
                            } // end [if has approval group notification and leave application is pending]
                        } else {
                            echo json_encode(array(
                                "success" => false,
                                "error" => "Invalid Attendance Details"
                            ));
                            return false;
                        }
                    }
                }
                
                // notify if still have pending application..
                notify_approver_timesheet_pending_emp($this->company_id, $this->emp_id, $this->psa_id, $this->subdomain);
                
                echo json_encode(array(
                    "success" => true,
                    "error" => "The timesheet has been rejected."
                ));
                return true;
            }
        }
    }

    public function check_pending_for_split($id, $company_id)
    {
        $where = array(
            "schedule_blocks_time_in_id" => $id,
            "comp_id" => $company_id,
            "time_in_status" => "pending"
        );
        $this->db->where($where);
        $query = $this->db->get("schedule_blocks_time_in");
        $row = $query->row();
        
        return ($row) ? true : false;
    }

    public function check_pending($id, $company_id){
        $where = array(
            "employee_time_in_id" => $id,
            "comp_id" => $company_id,
            "time_in_status" => "pending"
        );
        $this->db->where($where);
        $query = $this->db->get("employee_time_in");
        $row = $query->row();

        return ($row) ? true : false;
    }

    public function approve_leave()
    {
        if ($this->input->post('approve_leave')) {

            // check if the application is lock for filing
            $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id);
            if($get_lock_payroll_process_settings) {
                if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->approval_error
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->ts_approval_recalculation_err_msg
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->py_approval_recalculation_err_msg
                    ));
                    return false;
                }
            }

            $leave_id = $this->input->post('leaves_id');

            $leave_ids = array();
            array_push($leave_ids, $leave_id); 

            if (! $leave_ids) {
                echo json_encode(array(
                    "success" => false,
                    "error" => "Could not find the leave application."
                ));
                return true;
            }

            foreach ($leave_ids as $key => $val) {
                
                $leave_info             = $this->todo_leave->leave_information($val);
                
                $psa_id = $this->session->userdata('psa_id');

                if ($leave_info) { // start [if leave information]

                    $void_v2               = $this->employee_v2->check_payroll_lock_closed($leave_info->emp_id,$leave_info->company_id,date("Y-m-d", strtotime($leave_info->shift_date)));
                    $employee_details       = get_employee_details_by_empid($leave_info->emp_id);
                    $employee_leave_info    = $this->leave->checkleave_employee_leaves_application($leave_info->company_id, $val);

                    $void = $this->employee->edit_delete_void($leave_info->emp_id, $leave_info->company_id,$leave_info->shift_date);

                    $curr_approver = "";
                    $curr_approver_account_id = "";
                    $curr_level = $leave_info->level;
                    
                    // get workforce notification settings
                    $approval_group_notification = get_notify_settings($employee_details->leave_approval_grp, $this->company_id);
                    
                    // get approver's current level
                    $approver_emp_id = get_approver_emp_id_via_account_id($this->account_id);
                    $approver_emp_id = ($this->session->userdata("user_type_id") == 2) ? "-99{$this->company_id}" : $approver_emp_id;
                    $approver_level = get_current_approver_level($employee_details->leave_approval_grp, $approver_emp_id, $this->company_id);
                    
                    // check if employee leave application is pending
                    $is_emp_leave_pending = $this->leave->is_emp_leave_pending($val, $this->company_id);
                    $last_level = $this->leave->get_leave_last_level($leave_info->emp_id, $leave_info->company_id);

                    if($void_v2 == "Closed" && ($leave_info->for_resend_auto_rejected_id == null || $leave_info->for_resend_auto_rejected_id == "")){
                        #$rejected = false;
                        
                        $auto_remarks = "Auto-rejected due to approval timelapse.";
                        $auto_approval_date = date("Y-m-d H:i:s");
                        $auto_time_in_status = "reject";
                        
                        $fields = array(
                            "leave_application_status" => $auto_time_in_status,
                            "note" => $auto_remarks,
                            "approval_date" => $auto_approval_date
                        );
                        
                        $where = array(
                            "employee_leaves_application_id"=>$val,
                            "company_id"=>$leave_info->company_id
                        );
                        
                        $this->db->where($where);
                        $this->db->update("employee_leaves_application",$fields);
                        #$rejected = true;
                        
                    } else {
                        if ($approval_group_notification && $approver_level == $curr_level && $is_emp_leave_pending) { // start [if has notification and approvers turn to approve and is pending]
                            $workflow_approved_by_data = array(                                
                                'application_id' => $val,
                                'approver_id' => $approver_emp_id,
                                'workflow_level' => $curr_level,
                                'workflow_type' => 'leave'                            
                            );
                            $this->db->insert('workflow_approved_by', $workflow_approved_by_data);
                            
                            $leave_approver = $this->agm->get_approver_name_leave($leave_info->emp_id, $leave_info->company_id);
                            // $last_level = $this->leave->get_leave_last_level($leave_info->emp_id, $leave_info->company_id);
                            
                            $emp_name = ucwords($employee_details->first_name . " " . $employee_details->last_name);
                            $emp_email = $employee_details->email;
                            $emp_account_id = $employee_details->account_id;
                            
                            if ($this->session->userdata("user_type_id") == 2) {
                                $owner_approver = get_approver_owner_info($this->company_id);
                                $curr_approver = ucwords($owner_approver->first_name . " " . $owner_approver->last_name);
                                $curr_approver_account_id = $this->account_id;
                            } else {
                                foreach ($leave_approver as $la) {
                                    if ($la->level == $approver_level && $la->emp_id == $approver_emp_id) {
                                        $curr_approver = ucwords($la->first_name . " " . $la->last_name);
                                        $curr_approver_account_id = $la->account_id;
                                    }
                                }
                            }
                            
                            // ############################### APPROVE STARTS HERE ################################
                                                    
                            // ############################### notify same level nolink start ################################
                            
                            if($approver_level == $last_level){
                                // ------------------------------- LEAVE APPROVE START ------------------------------- //
                                if($leave_info->for_resend_auto_rejected_id != null || $leave_info->for_resend_auto_rejected_id != "") {
                                    $v_atp = array(
                                        "leave_application_status" => "approve",
                                        "error_log" => "approved"
                                    );
                                    
                                    $w_atp = array(
                                        "leaves_close_payroll_id" => $leave_info->for_resend_auto_rejected_id,
                                        "company_id" => $leave_info->company_id,
                                        "employee_leaves_application_id" => $val
                                    );
                                    
                                    $this->db->where($w_atp);
                                    $update = $this->db->update("leaves_close_payroll",$v_atp);
                                    
                                    
                                    // --------------------- APPROVE CHILD START ---------------------
                                    $where_child = array(
                                        "company_id"   => $leave_info->company_id,
                                        "leaves_id"    => $val
                                    );
                                    
                                    $update_child = array(
                                        "leave_application_status" => "approve",
                                        "error_log" => "approved"
                                    );
                                    
                                    $this->db->where($where_child);
                                    $this->db->update("leaves_close_payroll",$update_child);
                                    // --------------------- APPROVE CHILD END ---------------------
                                }

                                $value1 = array(
                                    "approve_by_hr" => "Yes",
                                    "approve_by_head" => "Yes"
                                );

                                $w1 = array(
                                    "leave_id" => $val,
                                    "comp_id" => $leave_info->company_id
                                );

                                $this->db->where($w1);
                                $update = $this->db->update("approval_leave",$value1);
                                
                                if($void == "Waiting for approval" || $void == "Closed"){
                                    $this->leave->update_employee_leaves($this->company_id, $leave_info->emp_id, $leave_info->leave_type_id, floatval($leave_info->total_leave_requested), $val,"late");
                                    
                                    $this->leave->save_correction($this->company_id, $val);
                                } else {
                                    $this->leave->new_update_employee_leaves($this->company_id, $leave_info->emp_id, $leave_info->leave_type_id, floatval($leave_info->total_leave_requested), $val);
                                    payroll_cronjob_helper('leave_application',$leave_info->shift_date,$leave_info->emp_id,$this->company_id);
                                }
                                
                                // ------------------------------- LEAVE APPROVE END ------------------------------- //

                                ################################ notify staff start ################################
                                if($approval_group_notification->notify_staff == "yes"){
                                    // notify staff via email
                                    emp_leave_notify_staff($val, $this->company_id, $leave_info->emp_id, $emp_email, $emp_name, $curr_approver, "last" , "", "");

                                    // notify next via sms
                                    if($approval_group_notification->sms_notification == "yes"){
                                        $sms_message = "Your leave application has been approved by {$curr_approver}.";
                                        send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                    }

                                    // notify staff via message board
                                    if($approval_group_notification->message_board_notification == "yes"){
                                        $emp_notif_message = "Your leave application has been approved by {$curr_approver}.";
                                        send_to_message_board($psa_id, $leave_info->emp_id, $curr_approver_account_id, $this->company_id, $emp_notif_message, "system");
                                    }
                                }
                                ################################ notify staff end ################################	

                                ################################ notify payroll admin start ################################
                                if($approval_group_notification->notify_payroll_admin == "yes"){
                                    // HRs
                                    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($psa_id);
                                    if($payroll_admin_hr){
                                        foreach ($payroll_admin_hr as $pahr){
                                            $pahr_email = $pahr->email;
                                            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);

                                            ###check email settings if enabled###
                                            if($this->company_id == "1"){
                                                if($pahr->ns_leave_email_flag == "yes"){
                                                    emp_leave_notify_staff($val, $this->company_id, $leave_info->emp_id, $pahr_email, $pahr_name, $curr_approver, "last", "", "", "", "Approved", "Yes");
                                                }
                                            }else{
                                                emp_leave_notify_staff($val, $this->company_id, $leave_info->emp_id, $pahr_email, $pahr_name, $curr_approver, "last", "", "", "", "Approved", "Yes");
                                            }
                                            ###end checking email settings if enabled###
                                        }
                                    }

                                    // Owner
                                    $pa_owner = get_approver_owner_info($this->company_id);
                                    if($pa_owner){
                                        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
                                        $pa_owner_email = $pa_owner->email;
                                        $pa_owner_account_id = $pa_owner->account_id;

                                        ###check email settings if enabled###
                                        if($this->company_id == "1"){
                                            if($pa_owner->ns_leave_email_flag == "yes"){
                                                emp_leave_notify_staff($val, $this->company_id, $leave_info->emp_id, $pa_owner_email, $pa_owner_name, $curr_approver, "last", "", "", "", "Approved","Yes");
                                            }
                                        }else{
                                            emp_leave_notify_staff($val, $this->company_id, $leave_info->emp_id, $pa_owner_email, $pa_owner_name, $curr_approver, "last", "", "", "", "Approved","Yes");
                                        }
                                        ###end checking email settings if enabled###
                                    }
                                }
                                ################################ notify payroll admin end ################################
                            } else {
                                $next_level = $approver_level + 1;
                                $new_token = $this->leave->generate_leave_level_token($next_level, $val);

                                foreach ($leave_approver as $nextapr){
                                    if($nextapr->level == $next_level){
                                        $next_appr_name = ucwords($nextapr->first_name." ".$nextapr->last_name);
                                        $next_appr_account_id = $nextapr->account_id;
                                        $next_appr_email = $nextapr->email;
                                        $next_appr_emp_id = $nextapr->emp_id;

                                        $token = $this->leave->get_token($val, $this->company_id, $leave_info->emp_id);
                                        $url = base_url()."approval/leave/index/".$token."/".$new_token."/1".$next_appr_emp_id."0";

                                        ###check email settings if enabled###
                                        if($this->company_id == "1"){
                                            if($nextapr->ns_leave_email_flag == "yes"){
                                                // notify next approver via email
                                                emp_leave_app_notification($token, $val, $this->company_id, $leave_info->emp_id, $next_appr_email, $next_appr_name, $curr_approver, "Approver" , "Yes", $new_token,$next_appr_emp_id);
                                            }
                                        }else{
                                            emp_leave_app_notification($token, $val, $this->company_id, $leave_info->emp_id, $next_appr_email, $next_appr_name, $curr_approver, "Approver" , "Yes", $new_token,$next_appr_emp_id);
                                        }
                                        ###end checking email settings if enabled###

                                        // notify next approver via sms
                                        if($approval_group_notification->sms_notification == "yes"){
                                            $sms_message = "Click {$url} to approve {$emp_name}'s leave.";
                                            send_this_sms_global($this->company_id, $next_appr_account_id, $sms_message, $psa_id, false);
                                        }

                                        // notify next approver via message board
                                        if($approval_group_notification->message_board_notification == "yes"){
                                            $next_appr_notif_message = "A leave application filed by {$emp_name} has been approved by {$curr_approver} and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
                                            send_to_message_board($psa_id, $next_appr_emp_id, $curr_approver_account_id, $this->company_id, $next_appr_notif_message, "system");
                                        }
                                    }
                                }
                            }
                            // ############################### APPROVE ################################
                        } // end [if has notification and approvers turn to approve and is pending]
                    }
                } else {
                    echo json_encode(array(
                        "success" => FALSE,
                        "error" => "Invalid Leave Details"
                    ));
                    return false;
                }
            }
            echo json_encode(array(
                "success" => TRUE,
                "error" => "Leave has been approved"
            ));
            return false;
        }
    }

    public function reject_leave()
    {
        if ($this->input->post('reject_leave')) {
            // check if the application is lock for filing
            $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id);
            if($get_lock_payroll_process_settings) {
                if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->approval_error
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->ts_approval_recalculation_err_msg
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->py_approval_recalculation_err_msg
                    ));
                    return false;
                }
            }
                
            $leave_ids = $this->input->post('leaves_id');
            if (! $leave_ids) {
                echo json_encode(array(
                    "success" => false,
                    "error" => "Could not find the leave application"
                ));
                return true;
            }
            
            foreach($leave_ids as $key => $val){
                $leave_info = $this->agm->leave_information($val);
                $employee_details = get_employee_details_by_empid($leave_info->emp_id);
                $psa_id = $this->session->userdata('psa_id');

                if($leave_info){ // start [leave information]
                    $curr_approver = "";
                    $curr_approver_account_id = "";
                    $curr_level = $leave_info->level;

                    // get workforce notification settings
                    $approval_group_notification = get_notify_settings($employee_details->leave_approval_grp, $this->company_id);

                    // get approver's current level
                    $approver_emp_id = get_approver_emp_id_via_account_id($this->account_id);
                    $approver_emp_id = ($this->session->userdata("user_type_id") == 2) ? "-99{$this->company_id}" : $approver_emp_id;
                    $approver_level = get_current_approver_level($employee_details->leave_approval_grp,$approver_emp_id,$this->company_id);

                    // check leave is pending
                    $is_emp_leave_pending = $this->leave->is_emp_leave_pending($val,$this->company_id);

                    if($approval_group_notification && $is_emp_leave_pending){ // start [if has approval group notification and leave application is pending]
                        $workflow_approved_by_data = array(
                            'application_id' => $val,
                            'approver_id'	=> $approver_emp_id,
                            'workflow_level'=> $curr_level,
                            'workflow_type'	=> 'leave'
                        );
                        $this->db->insert('workflow_approved_by',$workflow_approved_by_data);

                        $leave_approver = $this->agm->get_approver_name_leave($leave_info->emp_id,$leave_info->company_id);

                        $emp_name = ucwords($employee_details->first_name." ".$employee_details->last_name);
                        $emp_email = $employee_details->email;
                        $emp_account_id = $employee_details->account_id;

                        if($this->session->userdata("user_type_id") == 2){
                            $owner_approver = get_approver_owner_info($this->company_id);
                            $curr_approver = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                            $curr_approver_account_id = $this->account_id;
                        } else {
                            foreach ($leave_approver as $la){
                                if($la->level == $approver_level && $la->emp_id == $approver_emp_id){
                                    $curr_approver = ucwords($la->first_name." ".$la->last_name);
                                    $curr_approver_account_id = $la->account_id;
                                }
                            }
                        }

                        // ------------------------------- LEAVE REJECT START ------------------------------- //
                        $fields = array(
                            "leave_application_status" => "reject",
                            "approver_account_id" => $curr_approver_account_id
                        );
                        
                        $where = array(
                            "employee_leaves_application_id" => $val,
                            "company_id" => $this->company_info->company_id
                        );
                        
                        $rejected = $this->leave->update_field("employee_leaves_application",$fields,$where);
                        // ------------------------------- LEAVE REJECT END ------------------------------- //
                        if($rejected){
                            ################################ notify staff start ################################
                            if($approval_group_notification->notify_staff == "yes"){
                                // notify staff via email
                                emp_leave_notify_staff($val, $this->company_id, $leave_info->emp_id, $emp_email, $emp_name, $curr_approver, "last" , "", "", "", "Rejected");

                                // notify next via sms
                                if($approval_group_notification->sms_notification == "yes"){
                                    $sms_message = "Your leave application has been rejected by {$curr_approver}.";
                                    send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                }

                                // notify staff via message board
                                if($approval_group_notification->message_board_notification == "yes"){
                                    $emp_notif_message = "Your leave application has been rejected by {$curr_approver}.";
                                    send_to_message_board($psa_id, $leave_info->emp_id, $curr_approver_account_id, $this->company_id, $emp_notif_message, "system");
                                }
                            }
                            ################################ notify staff end ################################
                        }
                    }// end [if has approval group notification and leave application is pending]
                }
            }
            echo json_encode(array(
                "success" => TRUE,
                "error" => "Leave has been rejected"
            ));
            return true;
        }
    }

    public function approve_shifts()
    {
        if ($this->input->post("approve_shifts")) {

            // check if the application is lock for filing
            $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id);
            if($get_lock_payroll_process_settings) {
                if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->approval_error
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->ts_approval_recalculation_err_msg
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->py_approval_recalculation_err_msg
                    ));
                    return false;
                }
            }
            
            $employee_work_schedule_application_ids = $this->input->post('employee_work_schedule_application_id');
            $payroll_lock_close = $this->input->post('payroll_lock_close');

            if (!$employee_work_schedule_application_ids) {
                echo json_encode(array(
                    "success" => false,
                    "error" => "Could not find the work schedule."
                ));
                return false;
            }

            $employee_work_schedule_application_id = array();
            $employee_work_schedule_application_id1 = array();

            if ($payroll_lock_close) {
                array_push($employee_work_schedule_application_id1, $employee_work_schedule_application_ids);
            } else {
                array_push($employee_work_schedule_application_id, $employee_work_schedule_application_ids);
            }
            
            if ($employee_work_schedule_application_id) {
                foreach ($employee_work_schedule_application_id as $key => $val) {
                    $shifts_info = $this->shifts->shifts_information($this->company_id, $val);
                    
                    $employee_details = get_employee_details_by_empid($shifts_info->emp_id);
                    $psa_id = $this->session->userdata('psa_id');
                    
                    if ($shifts_info) { // start [if overtime information]
                        $curr_approver = "";
                        $curr_approver_account_id = "";
                        $curr_level = $shifts_info->level;
                        
                        // get workforce notification settings
                        $approval_group_notification = get_notify_settings($employee_details->shedule_request_approval_grp, $this->company_id);
                        
                        // get approver's current level
                        $approver_emp_id = get_approver_emp_id_via_account_id($this->account_id);
                        $approver_emp_id = ($this->session->userdata("user_type_id") == 2) ? "-99{$this->company_id}" : $approver_emp_id;
                        $approver_level = get_current_approver_level($employee_details->shedule_request_approval_grp, $approver_emp_id, $this->company_id);
                        
                        // check if employee schedule request application is pending
                        $is_emp_shifts_pending = $this->shifts->is_emp_shifts_pending($val, $this->company_id);
                        
                        if ($approval_group_notification && $approver_level == $curr_level && $is_emp_shifts_pending) { // start [if has notification and approvers turn to approve and is pending]
                            
                            $shifts_approvers = $this->agm->get_approver_name_shifts($shifts_info->emp_id, $shifts_info->company_id);
                            $last_level = $this->shifts->get_shifts_last_level($shifts_info->emp_id, $shifts_info->company_id);
                            
                            $emp_name = ucwords($employee_details->first_name . " " . $employee_details->last_name);
                            $emp_email = $employee_details->email;
                            $emp_account_id = $employee_details->account_id;
                            
                            if ($this->session->userdata("user_type_id") == 2) {
                                $owner_approver = get_approver_owner_info($this->company_id);
                                $curr_approver = ucwords($owner_approver->first_name . " " . $owner_approver->last_name);
                                $curr_approver_account_id = $this->account_id;
                            } else {
                                foreach ($shifts_approvers as $sa) {
                                    if ($sa->level == $approver_level && $sa->emp_id == $approver_emp_id) {
                                        $curr_approver = ucwords($sa->first_name . " " . $sa->last_name);
                                        $curr_approver_account_id = $sa->account_id;
                                    }
                                }
                            }
                                                    
                            // ############################### APPROVE STARTS HERE ################################
                            
                            // ############################### notify same level nolink start ################################
                            $approver_emp_id_check = ($approver_emp_id == "-99{$this->company_id}") ? "" : $approver_emp_id;
                            
                            foreach ($shifts_approvers as $samelvlapr) {
                                if ($samelvlapr->level == $approver_level && $samelvlapr->emp_id != $approver_emp_id_check) {
                                    $same_level_name = ucwords($samelvlapr->first_name . " " . $samelvlapr->last_name);
                                    $same_level_account_id = $samelvlapr->account_id;
                                    $same_level_email = $samelvlapr->email;
                                    $same_level_emp_id = $samelvlapr->emp_id;
                                    
                                    if ($samelvlapr->emp_id == "") {
                                        $owner_approver = get_approver_owner_info($this->company_id);
                                        $same_level_name = ucwords($owner_approver->first_name . " " . $owner_approver->last_name);
                                        $same_level_account_id = $owner_approver->account_id;
                                        $same_level_email = $owner_approver->email;
                                        $same_level_emp_id = "";
                                    }
                                                                    
                                    // notify same level approver via email
                                    $this->send_shifts_notifcation($val, $this->company_id, $shifts_info->emp_id, $same_level_email, $same_level_name, $curr_approver, "last", "", "", "", "No");
                                    
                                    // notify same level approver via sms
                                    if ($approval_group_notification->sms_notification == "yes") {
                                        $sms_message = "A Change Schedule Request filed by {$emp_name} has been approved by {$curr_approver}.";
                                        send_this_sms_global($this->company_id, $same_level_account_id, $sms_message, $psa_id, false);
                                    }

                                    // notify same level approver via message board
                                    if ($approval_group_notification->message_board_notification == "yes") {
                                        $same_level_appr_notif_message = "A Change Schedule Request filed by {$emp_name} has been approved by {$curr_approver}.";
                                        send_to_message_board($psa_id, $same_level_emp_id, $curr_approver_account_id, $this->company_id, $same_level_appr_notif_message, "system");
                                    }
                                }
                            }
                            
                            // ############################### notify same level nolink end ################################
                            
                            if ($approver_level == $last_level) {
                                // ------------------------------- SHIFT APPROVE START ------------------------------- //
                                $check_payroll_period = $this->shifts->check_payroll_period($this->company_id);
                                $idates_now = idates_now();
                                
                                if ($check_payroll_period != FALSE) {
                                    $period_to = $check_payroll_period->period_to;
                                    $idates_now = $period_to . " " . date("H:i:s");
                                }
                                $fields = array(
                                    "employee_work_schedule_status" => "approved",
                                    "approval_date" => date("Y-m-d H:i:s")
                                );
                                $where = array(
                                    "employee_work_schedule_application_id" => $val,
                                    "company_id" => $this->company_id
                                );
                                $this->shifts->update_field("employee_work_schedule_application", $fields, $where);
                                
                                // ------------------------------- SAVE to employee shift schedule and delete the old one (start) ------------------------------- //
                                $get_work_sched_app_id = $this->shifts->get_emp_work_sched_app($val);

                                /* START AUDIT TRAIL - SHIFTS */
                                $params_shifts = array(
                                    "emp_ids" => $get_work_sched_app_id->emp_id,
                                    "valid_from"=> date("Y-m-d", strtotime($get_work_sched_app_id->date_from)),
                                    "until" => date("Y-m-d", strtotime($get_work_sched_app_id->date_to)),
                                    "company_id" => $this->company_id,
                                    "account_id" => $this->account_id,
                                    "flag_action" => "Approve Change Request",
                                    "prev_work_schedule_id" => "",
                                    "current_work_schedule_id" => $get_work_sched_app_id->work_schedule_id,
                                    "details" => $get_work_sched_app_id->note,
                                    "approver_level" => $approver_level
                                );
                                
                                $get_assigned_work_schedule = assigned_employee_shifts_schedule($params_shifts);

                                $flag_save = false;
                                            
                                if($get_assigned_work_schedule) {
                                    $save_prev_work_schedule = shifts_audit_trail($get_assigned_work_schedule,$params_shifts);
                                    if($save_prev_work_schedule) {
                                        $flag_save = true;
                                    }
                                }else{
                                    $flag_save = true;
                                }
                                
                                /* END AUDIT TRAIL */
                                if ($flag_save) {
                                    $application_dates = dateRange($get_work_sched_app_id->date_from, $get_work_sched_app_id->date_to);
                                    $res_to_approved = array();
                                    $res_to_approved_shift = array();
                                    
                                    if ($application_dates) {
                                        foreach ($application_dates as $ad) {
                                            $delete_old_shift = array(
                                                'company_id' => $get_work_sched_app_id->company_id,
                                                'emp_id' => $get_work_sched_app_id->emp_id,
                                                'valid_from' => $ad,
                                                'until' => $ad,
                                            );
                                            
                                            $this->db->delete('employee_shifts_schedule',$delete_old_shift);
                                            
                                            $sched_app_data = array(
                                                'company_id' => $get_work_sched_app_id->company_id,
                                                'emp_id' => $get_work_sched_app_id->emp_id,
                                                'valid_from' => $ad,
                                                'until' => $ad,
                                                'work_schedule_id' => $get_work_sched_app_id->work_schedule_id
                                            );
                                            
                                            $sched_app_data_shift = array(
                                                'company_id' => $get_work_sched_app_id->company_id,
                                                'emp_id' => $get_work_sched_app_id->emp_id,
                                                'date_from' => $ad,
                                                'date_to' => $ad,
                                            );
                                            
                                            array_push($res_to_approved,$sched_app_data);
                                            array_push($res_to_approved_shift,$sched_app_data_shift);
                                        }

                                        if(count($res_to_approved) > 0) {
                                            $this->db->insert_batch('employee_shifts_schedule', $res_to_approved);
                                        }
                                        
                                        if(count($res_to_approved_shift) > 0) {
                                            $this->db->insert_batch('change_shift_schedule_ar', $res_to_approved_shift);
                                        }
                                    }
                                }

                                // ------------------------------- SAVE to employee shift schedule and delete the old on (end) --------------------------------- //
                                
                                // this checks the shift list if valid $val is for the work schedule id
                                // $shifts_list = $this->shifts->shifts_get_data($this->company_id,$val);
                                // if($shifts_list) {
                                // once valid overtime then we compare overtime log dates to employee time in
                                // $time_in_check = $this->overtime->employee_time_in_against($this->company_id,$shifts_list->emp_id,$shifts_list->overtime_to);
                                // if($time_in_check) {
                                // # if value is true then we transfer the total_hours_Required to total so this will be valid
                                // $time_in_field = array(
                                // "total_hours" => $time_in_check->total_hours_required
                                // );
                                // # will just ave to make sure if company_id is the right on then employee time id and employee id is valid
                                // $time_in_where = array(
                                // "comp_id" => $this->company_info->company_id,
                                // "employee_time_in_id" => $time_in_check->employee_time_in_id,
                                // "emp_id" => $shifts_list->emp_id
                                // );
                                // $this->overtime->update_field("employee_time_in",$time_in_field,$time_in_where);
                                // }
                                // }
                                // ------------------------------- OVERTIME APPROVE START ------------------------------- //
                                // ############################### notify staff start ################################
                                if ($approval_group_notification->notify_staff == "yes") {
                                    // notify staff via email
                                    $this->send_shifts_notifcation($val, $this->company_id, $shifts_info->emp_id, $emp_email, $emp_name, $curr_approver, "last", "", "");
                                    
                                    // notify next via sms
                                    if ($approval_group_notification->sms_notification == "yes") {
                                        $sms_message = "Your Change Schedule Request has been approved by {$curr_approver}.";
                                        send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                    }

                                    // notify staff via message board
                                    if ($approval_group_notification->message_board_notification == "yes") {
                                        $emp_notif_message = "Your Change Schedule Request has been approved by {$curr_approver}.";
                                        send_to_message_board($psa_id, $shifts_info->emp_id, $curr_approver_account_id, $this->company_id, $emp_notif_message, "system");
                                    }
                                }
                                
                                // ############################### notify staff end ################################

                                ################################ notify payroll admin start ################################
                                if($approval_group_notification->notify_payroll_admin == "yes"){
                                    // HRs
                                    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($this->psa_id);
                                    if($payroll_admin_hr){
                                        foreach ($payroll_admin_hr as $pahr){
                                            $pahr_email = $pahr->email;
                                            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);
                                            $this->send_shifts_notifcation($val, $this->company_id, $shifts_info->emp_id, $pahr_email, $pahr_name, $curr_approver, "last", "", "", "", "Yes");
                                        }
                                    }
                                    
                                    // Owner
                                    $pa_owner = get_approver_owner_info($this->company_id);
                                    if($pa_owner){
                                        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
                                        $pa_owner_email = $pa_owner->email;
                                        $pa_owner_account_id = $pa_owner->account_id;
                                        $this->send_shifts_notifcation($val, $this->company_id, $shifts_info->emp_id, $pa_owner_email, $pa_owner_name, $curr_approver, "last", "", "", "", "Yes");
                                    }
                                }
                                ################################ notify payroll admin end ################################

                                $cron_app_data = array(
                                    'company_id' => $this->company_id,
                                    'emp_id' => $shifts_info->emp_id,
                                    'date_from' => $shifts_info->date_to,
                                    'date_to' => $shifts_info->date_to
                                );
                                $this->db->insert('change_shift_schedule_ar', $cron_app_data);
                            } else {
                                $next_level = $approver_level + 1;
                                $new_token = $this->shifts->generate_shifts_level_token($next_level, $val);
                                
                                foreach ($shifts_approvers as $nextapr) {
                                    if ($nextapr->level == $next_level) {
                                        $next_appr_name = ucwords($nextapr->first_name . " " . $nextapr->last_name);
                                        $next_appr_account_id = $nextapr->account_id;
                                        $next_appr_email = $nextapr->email;
                                        $next_appr_emp_id = $nextapr->emp_id;
                                        
                                        $token = $this->shifts->get_token($val, $this->company_id, $shifts_info->emp_id);
                                        $url = base_url() . "approval/work_schedule/index/" . $token . "/" . $new_token . "/1" . $next_appr_emp_id . "0";
                                        
                                        // notify next approver via email
                                        $this->send_shifts_notifcation($val, $this->company_id, $shifts_info->emp_id, $next_appr_email, $next_appr_name, $curr_approver, "Approver", "Yes", $new_token, $next_appr_emp_id, "No");
                                        
                                        // notify next approver via sms
                                        if ($approval_group_notification->sms_notification == "yes") {
                                            $sms_message = "{$emp_name} has filed a Schedule Change Request.";
                                            send_this_sms_global($this->company_id, $next_appr_account_id, $sms_message, $psa_id, false);
                                        }
                                                                            
                                        // notify next approver via message board
                                        if ($approval_group_notification->message_board_notification == "yes") {
                                            $next_appr_notif_message = "A Change Schedule Request filed by {$emp_name} has been approved by {$curr_approver} and is now waiting for your approval.
                                            Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
                                            send_to_message_board($psa_id, $next_appr_emp_id, $curr_approver_account_id, $this->company_id, $next_appr_notif_message, "system");
                                        }                                    
                                    }
                                }
                                
                                // ############################### notify staff start ################################
                                if ($approval_group_notification->notify_staff == "yes") {
                                    // notify staff via email
                                    $this->send_shifts_notifcation($val, $this->company_id, $shifts_info->emp_id, $emp_email, $emp_name . "/next level", $curr_approver, "", "", "");
                                    
                                    // notify next via sms
                                    if ($approval_group_notification->sms_notification == "yes") {
                                        $sms_message = "Your Change Schedule Request has been approved by {$curr_approver} and is waiting for next level's approval.";
                                        send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                    }
                                    
                                    // notify staff via message board
                                    if ($approval_group_notification->message_board_notification == "yes") {
                                        $emp_notif_message = "Your Change Schedule Request has been approved by {$curr_approver} and is waiting for next level's approval.";
                                        send_to_message_board($psa_id, $shifts_info->emp_id, $curr_approver_account_id, $this->company_id, $emp_notif_message, "system");
                                    }
                                }
                                // ############################### notify staff end ################################

                                ################################ notify payroll admin start ################################
                                if($approval_group_notification->notify_payroll_admin == "yes"){
                                    // HRs
                                    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($this->psa_id);
                                    if($payroll_admin_hr){
                                        foreach ($payroll_admin_hr as $pahr){
                                            $pahr_email = $pahr->email;
                                            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);
                                            
                                            $this->send_shifts_notifcation($val, $this->company_id, $shifts_info->emp_id, $pahr_email, $pahr_name."/next level", $curr_approver, "", "", "", "", "Yes");
                                        }
                                    }
                                    
                                    // Owner
                                    $pa_owner = get_approver_owner_info($this->company_id);
                                    if($pa_owner){
                                        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
                                        $pa_owner_email = $pa_owner->email;
                                        $pa_owner_account_id = $pa_owner->account_id;
                                        
                                        $this->send_shifts_notifcation($val, $this->company_id, $shifts_info->emp_id, $pa_owner_email, $pa_owner_name."/next level", $curr_approver, "", "", "", "", "Yes");
                                    }
                                }
                                ################################ notify payroll admin end ################################
                            }
                            // ############################### APPROVE ENDS HERE ################################
                        } // end [if has notification and approvers turn to approve and is pending]
                    }
                }
            }

            if ($employee_work_schedule_application_id1) {
                // reject this time locked payroll
                foreach($employee_work_schedule_application_id1 as $key => $val){
                    $shifts_info = $this->shifts->shifts_information($this->company_id, $val);
                    $employee_details = get_employee_details_by_empid($shifts_info->emp_id);
                    
                    if($shifts_info){ // start [overtime information]
                        $curr_approver = "";
                        $curr_approver_account_id = "";
                        $curr_level = $shifts_info->level;
                        
                        // get workforce notification settings
                        $approval_group_notification = get_notify_settings($employee_details->shedule_request_approval_grp, $this->company_id);
                        
                        // get approver's current level
                        $approver_emp_id = get_approver_emp_id_via_account_id($this->account_id);
                        $approver_emp_id = ($this->session->userdata("user_type_id") == 2) ? "-99{$this->company_id}" : $approver_emp_id;
                        $approver_level = get_current_approver_level($employee_details->shedule_request_approval_grp, $approver_emp_id, $this->company_id);
                        
                        // check if employee overtime application is pending
                        $is_emp_shifts_pending = $this->shifts->is_emp_shifts_pending($val, $this->company_id);
                        
                        if($approval_group_notification && $is_emp_shifts_pending){ // start [if has approval group notification and leave application is pending]
                            $shifts_approvers = $this->agm->get_approver_name_shifts($shifts_info->emp_id,$shifts_info->company_id);
                            
                            $emp_name = ucwords($employee_details->first_name." ".$employee_details->last_name);
                            $emp_email = $employee_details->email;
                            $emp_account_id = $employee_details->account_id;
                            
                            if($this->session->userdata("user_type_id") == 2){
                                $owner_approver = get_approver_owner_info($this->company_id);
                                $curr_approver = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                                $curr_approver_account_id = $this->account_id;
                            }
                            else{
                                foreach ($shifts_approvers as $sa){
                                    if($sa->level == $approver_level && $sa->emp_id == $approver_emp_id){
                                        $curr_approver = ucwords($sa->first_name." ".$sa->last_name);
                                        $curr_approver_account_id = $sa->account_id;
                                    }
                                }
                            }
                            
                            // ------------------------------- OVERTIME REJECT START ------------------------------- //
                            $fields = array(
                                "employee_work_schedule_status" => "rejected"
                            );
                            $where = array(
                                "employee_work_schedule_application_id" => $val,
                                "company_id" => $this->company_id
                            );
                            $rejected = $this->shifts->update_field("employee_work_schedule_application",$fields,$where);
                            // ------------------------------- OVERTIME REJECT START ------------------------------- //
                            if($rejected){
                                $approver_emp_id_check = ($approver_emp_id == "-99{$this->company_id}") ? "" : $approver_emp_id;
                                
                                foreach ($shifts_approvers as $nextapr){
                                    if($nextapr->level >= $approver_level && $nextapr->emp_id != $approver_emp_id_check){
                                        $next_appr_name = ucwords($nextapr->first_name." ".$nextapr->last_name);
                                        $next_appr_account_id = $nextapr->account_id;
                                        $next_appr_email = $nextapr->email;
                                        $next_appr_emp_id = $nextapr->emp_id;
                                        
                                        if($nextapr->emp_id == ""){
                                            $owner_approver = get_approver_owner_info($this->company_id);
                                            $next_appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                                            $next_appr_account_id = $owner_approver->account_id;
                                            $next_appr_email = $owner_approver->email;
                                            $next_appr_emp_id = "";
                                        }

                                        if($this->company_id == "1"){
                                            if($nextapr->ns_change_shift_email_flag == "yes"){
                                                // notify next approver via email
                                                $this->send_shifts_reject_notifcation($val, $this->company_id, $shifts_info->emp_id, $next_appr_email, $next_appr_name, $curr_approver, "", "", "");
                                            }
                                        }else{
                                            $this->send_shifts_reject_notifcation($val, $this->company_id, $shifts_info->emp_id, $next_appr_email, $next_appr_name, $curr_approver, "", "", "");
                                        }

                                        // notify next approver via sms
                                        if($approval_group_notification->sms_notification == "yes"){
                                            $sms_message = "A Change Schedule Request filed by {$emp_name} has been rejected by {$curr_approver}.";
                                            send_this_sms_global($this->company_id, $next_appr_account_id,$sms_message,$this->psa_id,false);
                                        }

                                        // notify next approver via message board
                                        if($approval_group_notification->message_board_notification == "yes"){
                                            $next_appr_notif_message = "A Change Schedule Request filed by {$emp_name} has been rejected by {$curr_approver}.";
                                            send_to_message_board($this->psa_id, $next_appr_emp_id, $curr_approver_account_id, $this->company_id, $next_appr_notif_message, "system");
                                        }
                                    }
                                }
                                
                                ################################ notify staff start ################################
                                if($approval_group_notification->notify_staff == "yes"){

                                    // notify staff via email
                                    $this->send_shifts_reject_notifcation($val, $this->company_id, $shifts_info->emp_id, $emp_email, $emp_name, $curr_approver, "last", "", "", "Yes");

                                    // notify next via sms
                                    if($approval_group_notification->sms_notification == "yes"){
                                        $sms_message = "Your Change Schedule Request has been rejected by {$curr_approver}.";
                                        send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $this->psa_id, false);
                                    }

                                    // notify staff via message board
                                    if($approval_group_notification->message_board_notification == "yes"){
                                        $emp_notif_message = "Your Change Schedule Request has been rejected by {$curr_approver}.";
                                        send_to_message_board($this->psa_id, $shifts_info->emp_id, $curr_approver_account_id, $this->company_id, $emp_notif_message, "system");
                                    }
                                }
                                ################################ notify staff end ################################
                                
                                ################################ notify payroll admin start ################################
                                if($approval_group_notification->notify_payroll_admin == "yes"){
                                    // HRs
                                    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($this->psa_id);
                                    if($payroll_admin_hr){
                                        foreach ($payroll_admin_hr as $pahr){
                                            $pahr_email = $pahr->email;
                                            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);

                                            if($this->company_id == "1"){
                                                if($pahr->ns_change_shift_email_flag == "yes"){
                                                    $this->send_shifts_reject_notifcation($val, $this->company_id, $shifts_info->emp_id, $pahr_email, $pahr_name, $curr_approver, "last", "", "");
                                                }
                                            }else{
                                                $this->send_shifts_reject_notifcation($val, $this->company_id, $shifts_info->emp_id, $pahr_email, $pahr_name, $curr_approver, "last", "", "");
                                            }
                                        }
                                    }
                                    
                                    // Owner
                                    $pa_owner = get_approver_owner_info($this->company_id);
                                    if($pa_owner){
                                        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
                                        $pa_owner_email = $pa_owner->email;
                                        $pa_owner_account_id = $pa_owner->account_id;

                                        if($this->company_id == "1"){
                                            if($pa_owner->ns_change_shift_email_flag == "yes"){
                                                $this->send_shifts_reject_notifcation($val, $this->company_id, $shifts_info->emp_id, $pa_owner_email, $pa_owner_name, $curr_approver, "last", "", "");
                                            }
                                        }else{
                                            $this->send_shifts_reject_notifcation($val, $this->company_id, $shifts_info->emp_id, $pa_owner_email, $pa_owner_name, $curr_approver, "last", "", "");
                                        }
                                    }
                                }
                                ################################ notify payroll admin end ################################
                            }
                        } // end [if has approval group notification and leave application is pending]
                    }
                }
                $this->session->set_userdata('ids', $employee_work_schedule_application_id1);
                //redirect($this->uri->segment(1)."/employee/emp_todo_shift_request/affected_requests");
            }
            
            echo json_encode(array(
                "success" => TRUE,
                "error" => "Schedule request has been approved"
            ));
            return true;
        }
    }

    public function reject_shifts()
    {
        if ($this->input->post("reject_shifts")) {
            // check if the application is lock for filing and approval
            $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id);
            if($get_lock_payroll_process_settings) {
                if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->approval_error
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->ts_approval_recalculation_err_msg
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->py_approval_recalculation_err_msg
                    ));
                    return false;
                }
            }

            $employee_work_schedule_application_ids = $this->input->post('employee_work_schedule_application_id');
            if (!$employee_work_schedule_application_ids) {
                echo json_encode(array(
                    "success" => false,
                    "error" => "Could not find the schedule."
                ));
                return false;
            }

            $psa_id = $this->session->userdata('psa_id');
            $employee_work_schedule_application_id = array();
            array_push($employee_work_schedule_application_id, $employee_work_schedule_application_ids);

            if ($employee_work_schedule_application_id) {}
                foreach ($employee_work_schedule_application_id as $key => $val) {
                    $shifts_info = $this->shifts->shifts_information($this->company_id, $val);
                    $employee_details = get_employee_details_by_empid($shifts_info->emp_id);
                    
                    if ($shifts_info) { // start [overtime information]
                        $curr_approver = "";
                        $curr_approver_account_id = "";
                        $curr_level = $shifts_info->level;
                        
                        // get workforce notification settings
                        $approval_group_notification = get_notify_settings($employee_details->shedule_request_approval_grp, $this->company_id);
                        
                        // get approver's current level
                        $approver_emp_id = get_approver_emp_id_via_account_id($this->account_id);
                        $approver_emp_id = ($this->session->userdata("user_type_id") == 2) ? "-99{$this->company_id}" : $approver_emp_id;
                        $approver_level = get_current_approver_level($employee_details->shedule_request_approval_grp, $approver_emp_id, $this->company_id);
                        
                        // check if employee overtime application is pending
                        $is_emp_shifts_pending = $this->shifts->is_emp_shifts_pending($val, $this->company_id);
                        
                        if ($approval_group_notification && $is_emp_shifts_pending) { // start [if has approval group notification and leave application is pending]
                            $shifts_approvers = $this->agm->get_approver_name_shifts($shifts_info->emp_id, $shifts_info->company_id);
                            
                            $emp_name = ucwords($employee_details->first_name . " " . $employee_details->last_name);
                            $emp_email = $employee_details->email;
                            $emp_account_id = $employee_details->account_id;
                            
                            if ($this->session->userdata("user_type_id") == 2) {
                                $owner_approver = get_approver_owner_info($this->company_id);
                                $curr_approver = ucwords($owner_approver->first_name . " " . $owner_approver->last_name);
                                $curr_approver_account_id = $this->account_id;
                            } else {
                                foreach ($shifts_approvers as $sa) {
                                    if ($sa->level == $approver_level && $sa->emp_id == $approver_emp_id) {
                                        $curr_approver = ucwords($sa->first_name . " " . $sa->last_name);
                                        $curr_approver_account_id = $sa->account_id;
                                    }
                                }
                            }
                                                        
                            // ------------------------------- OVERTIME REJECT START ------------------------------- //
                            $fields = array(
                                "employee_work_schedule_status" => "rejected"
                            );
                            $where = array(
                                "employee_work_schedule_application_id" => $val,
                                "company_id" => $this->company_id
                            );
                            $rejected = $this->shifts->update_field("employee_work_schedule_application", $fields, $where);
                            // ------------------------------- OVERTIME REJECT START ------------------------------- //
                            if ($rejected) {
                                $approver_emp_id_check = ($approver_emp_id == "-99{$this->company_id}") ? "" : $approver_emp_id;
                                
                                foreach ($shifts_approvers as $nextapr) {
                                    if ($nextapr->level >= $approver_level && $nextapr->emp_id != $approver_emp_id_check) {
                                        $next_appr_name = ucwords($nextapr->first_name . " " . $nextapr->last_name);
                                        $next_appr_account_id = $nextapr->account_id;
                                        $next_appr_email = $nextapr->email;
                                        $next_appr_emp_id = $nextapr->emp_id;
                                        
                                        if ($nextapr->emp_id == "") {
                                            $owner_approver = get_approver_owner_info($this->company_id);
                                            $next_appr_name = ucwords($owner_approver->first_name . " " . $owner_approver->last_name);
                                            $next_appr_account_id = $owner_approver->account_id;
                                            $next_appr_email = $owner_approver->email;
                                            $next_appr_emp_id = "";
                                        }
                                        
                                        // notify next approver via email
                                        $this->send_shifts_reject_notifcation($val, $this->company_id, $shifts_info->emp_id, $next_appr_email, $next_appr_name, $curr_approver, "", "", "");
                                        ;
                                        // notify next approver via sms
                                        if ($approval_group_notification->sms_notification == "yes") {
                                            $sms_message = "A Change Schedule Request filed by {$emp_name} has been rejected by {$curr_approver}.";
                                            send_this_sms_global($this->company_id, $next_appr_account_id, $sms_message, $psa_id, false);
                                        }
                                                                                
                                        // notify next approver via message board
                                        if ($approval_group_notification->message_board_notification == "yes") {
                                            $next_appr_notif_message = "A Change Schedule Request filed by {$emp_name} has been rejected by {$curr_approver}.";
                                            send_to_message_board($psa_id, $next_appr_emp_id, $curr_approver_account_id, $this->company_id, $next_appr_notif_message, "system");
                                        }
                                        
                                    }
                                }
                                
                                // ############################### notify staff start ################################
                                if ($approval_group_notification->notify_staff == "yes") {
                                    // notify staff via email
                                    $this->send_shifts_reject_notifcation($val, $this->company_id, $shifts_info->emp_id, $emp_email, $emp_name, $curr_approver, "last", "", "", "Yes");
                                    
                                    // notify next via sms
                                    if ($approval_group_notification->sms_notification == "yes") {
                                        $sms_message = "Your Change Schedule Request has been rejected by {$curr_approver}.";
                                        send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                    }
                                                                        
                                    // notify staff via message board
                                    if ($approval_group_notification->message_board_notification == "yes") {
                                        $emp_notif_message = "Your Change Schedule Request has been rejected by {$curr_approver}.";
                                        send_to_message_board($psa_id, $shifts_info->emp_id, $curr_approver_account_id, $this->company_id, $emp_notif_message, "system");
                                    }
                                }
                                // ############################### notify staff end ################################

                                ################################ notify payroll admin start ################################
                                if($approval_group_notification->notify_payroll_admin == "yes"){
                                    // HRs
                                    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($this->psa_id);
                                    if($payroll_admin_hr){
                                        foreach ($payroll_admin_hr as $pahr){
                                            $pahr_email = $pahr->email;
                                            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);

                                            if($this->company_id == "1"){
                                                if($pahr->ns_change_shift_email_flag == "yes"){
                                                    $this->send_shifts_reject_notifcation($val, $this->company_id, $shifts_info->emp_id, $pahr_email, $pahr_name, $curr_approver, "last", "", "");
                                                }
                                            }else{
                                                $this->send_shifts_reject_notifcation($val, $this->company_id, $shifts_info->emp_id, $pahr_email, $pahr_name, $curr_approver, "last", "", "");
                                            }
                                        }
                                    }
                                    
                                    // Owner
                                    $pa_owner = get_approver_owner_info($this->company_id);
                                    if($pa_owner){
                                        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
                                        $pa_owner_email = $pa_owner->email;
                                        $pa_owner_account_id = $pa_owner->account_id;

                                        if($this->company_id == "1"){
                                            if($pa_owner->ns_change_shift_email_flag == "yes"){
                                                $this->send_shifts_reject_notifcation($val, $this->company_id, $shifts_info->emp_id, $pa_owner_email, $pa_owner_name, $curr_approver, "last", "", "");
                                            }
                                        }else{
                                            $this->send_shifts_reject_notifcation($val, $this->company_id, $shifts_info->emp_id, $pa_owner_email, $pa_owner_name, $curr_approver, "last", "", "");
                                        }
                                    }
                                }
                                ################################ notify payroll admin end ################################
                            }
                        } // end [if has approval group notification and leave application is pending]
                    }
                }
            echo json_encode(array(
                "success" => TRUE,
                "error" => "Schedule request has been rejected"
            ));
            return true;
        }
    }

    public function approve_overtime()
    {
        if ($this->input->post('approve_overtime')) {

            // check if the application is lock for filing
            $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id);
            if($get_lock_payroll_process_settings) {
                if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->approval_error
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->ts_approval_recalculation_err_msg
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->py_approval_recalculation_err_msg
                    ));
                    return false;
                }
            }

            $overtime_ids = $this->input->post('overtime_id');
            $psa_id = $this->session->userdata('psa_id');
            $overtime_id = array();
            array_push($overtime_id, $overtime_ids);
            foreach ($overtime_id as $key => $val) {
                $ot_info = $this->overtime->overtime_information($this->company_id, $val);
                $employee_details = get_employee_details_by_empid($ot_info->emp_id);

                $void_v2 = $this->employee_v2->check_payroll_lock_closed($ot_info->emp_id,$ot_info->company_id,date("Y-m-d", strtotime($ot_info->overtime_from)));
                
                if ($ot_info) { // start [if overtime information]
                    // $void = $this->employee->edit_delete_void($ot_info->emp_id, $ot_info->company_id,$ot_info->overtime_from);
                    $curr_approver = "";
                    $curr_approver_account_id = "";
                    $curr_level = $ot_info->level;
                    
                    // get workforce notification settings
                    $approval_group_notification = get_notify_settings($employee_details->overtime_approval_grp, $this->company_id);
                    
                    // get approver's current level
                    $approver_emp_id = get_approver_emp_id_via_account_id($this->account_id);
                    $approver_emp_id = ($this->session->userdata("user_type_id") == 2) ? "-99{$this->company_id}" : $approver_emp_id;
                    $approver_level = get_current_approver_level($employee_details->overtime_approval_grp, $approver_emp_id, $this->company_id);
                    
                    // check if employee overtime application is pending
                    $is_emp_overtime_pending = $this->overtime->is_emp_overtime_pending($val, $this->company_id);
                    $last_level = $this->overtime->get_overtime_last_level($ot_info->emp_id, $ot_info->company_id);

                    if($void_v2 == "Closed" && ($ot_info->for_resend_auto_rejected_id == null || $ot_info->for_resend_auto_rejected_id == "")){
                        $rejected = false;
                        
                        $auto_remarks = "Auto-rejected due to approval timelapse.";
                        $auto_approval_date = date("Y-m-d H:i:s");
                        $auto_time_in_status = "reject";
                        
                        $date_insert = array(
                            "overtime_id" => $val,
                            "emp_id" => $ot_info->emp_id,
                            "overtime_date_applied" => $ot_info->overtime_date_applied,
                            "overtime_from" => $ot_info->overtime_from,
                            "overtime_to" => $ot_info->overtime_to,
                            "start_time" => $ot_info->start_time,
                            "end_time" => $ot_info->end_time,
                            "no_of_hours" => $ot_info->no_of_hours,
                            "company_id" => $ot_info->company_id,
                            "reason" => $ot_info->reason,
                            "notes" => $auto_remarks,
                            "approval_date" => $ot_info->approval_date,
                            "overtime_status" => $auto_time_in_status,
                            "approval_date" => $auto_approval_date,
                            "status" => $ot_info->status,
                            "period_from" => $ot_info->period_from,
                            "period_to" => $ot_info->period_to,
                            "flag_open_shift" => $ot_info->flag_open_shift
                        );
                        
                        $this->db->insert('overtimes_close_payroll', $date_insert);
                        
                        $fields = array(
                            "overtime_status" => $auto_time_in_status,
                            "notes" => $auto_remarks,
                            "approval_date" => $auto_approval_date
                        );
                        
                        $where = array(
                            "overtime_id"=>$val,
                            "company_id"=>$ot_info->company_id
                        );
                        
                        $this->db->where($where);
                        $this->db->update("employee_overtime_application",$fields);
                        $rejected = true;
                        
                    } else {
                        if ($approval_group_notification && $approver_level == $curr_level && $is_emp_overtime_pending) { // start [if has notification and approvers turn to approve and is pending]
                            $workflow_approved_by_data = array(
                                
                                'application_id' => $val,
                                'approver_id' => $approver_emp_id,
                                'workflow_level' => $curr_level,
                                'workflow_type' => 'overtime'
                            
                            );
                            $this->db->insert('workflow_approved_by', $workflow_approved_by_data);
                            
                            $ot_approvers = $this->agm->get_approver_name_overtime($ot_info->emp_id, $ot_info->company_id);
                            // $last_level = $this->overtime->get_overtime_last_level($ot_info->emp_id, $ot_info->company_id);
                            
                            $emp_name = ucwords($employee_details->first_name . " " . $employee_details->last_name);
                            $emp_email = $employee_details->email;
                            $emp_account_id = $employee_details->account_id;
                            
                            if ($this->session->userdata("user_type_id") == 2) {
                                $owner_approver = get_approver_owner_info($this->company_id);
                                $curr_approver = ucwords($owner_approver->first_name . " " . $owner_approver->last_name);
                                $curr_approver_account_id = $this->account_id;
                            } else {
                                foreach ($ot_approvers as $oa) {
                                    if ($oa->level == $approver_level && $oa->emp_id == $approver_emp_id) {
                                        $curr_approver = ucwords($oa->first_name . " " . $oa->last_name);
                                        $curr_approver_account_id = $oa->account_id;
                                    }
                                }
                            }
                                                    
                            // ############################### APPROVE STARTS HERE ################################
                            
                            // ############################### notify same level nolink start ################################
                            $approver_emp_id_check = ($approver_emp_id == "-99{$this->company_id}") ? "" : $approver_emp_id;
                            
                            foreach ($ot_approvers as $samelvlapr) {
                                if ($samelvlapr->level == $approver_level && $samelvlapr->emp_id != $approver_emp_id_check) {
                                    $same_level_name = ucwords($samelvlapr->first_name . " " . $samelvlapr->last_name);
                                    $same_level_account_id = $samelvlapr->account_id;
                                    $same_level_email = $samelvlapr->email;
                                    $same_level_emp_id = $samelvlapr->emp_id;
                                    
                                    if ($samelvlapr->emp_id == "") {
                                        $owner_approver = get_approver_owner_info($this->company_id);
                                        $same_level_name = ucwords($owner_approver->first_name . " " . $owner_approver->last_name);
                                        $same_level_account_id = $owner_approver->account_id;
                                        $same_level_email = $owner_approver->email;
                                        $same_level_emp_id = "";
                                    }
                                                   
                                    ###check email settings if enabled###
                                    if($samelvlapr->ns_overtime_email_flag == "yes"){
                                        // notify same level approver via email
                                        $this->send_overtime_notifcation($val, $this->company_id, $ot_info->emp_id, $same_level_email, $same_level_name, $curr_approver, "last", "", "");
                                    }
                                    ###end checking email settings if enabled###

                                    // notify same level approver via sms
                                    if ($approval_group_notification->sms_notification == "yes") {
                                        $sms_message = "An overtime application filed by {$emp_name} has been approved by {$curr_approver}.";
                                        send_this_sms_global($this->company_id, $same_level_account_id, $sms_message, $psa_id, false);
                                    }
                                                                    
                                    // notify same level approver via message board
                                    if ($approval_group_notification->message_board_notification == "yes") {
                                        $same_level_appr_notif_message = "An overtime application filed by {$emp_name} has been approved by {$curr_approver}.";
                                        send_to_message_board($psa_id, $same_level_emp_id, $curr_approver_account_id, $this->company_id, $same_level_appr_notif_message, "system");
                                    }
                                }
                            }
                            
                            // ############################### notify same level nolink end ################################
                            
                            if ($approver_level == $last_level) {
                                // ------------------------------- OVERTIME APPROVE START ------------------------------- //
                                if($ot_info->for_resend_auto_rejected_id != null || $ot_info->for_resend_auto_rejected_id != "") {
                                    $v_atp = array(
                                        "overtime_status" => "approved",
                                        "error_log" => "approved"
                                    );
                                    
                                    $w_atp = array(
                                        "overtimes_close_payroll_id" => $ot_info->for_resend_auto_rejected_id,
                                        "company_id"   => $this->company_id,
                                        "overtime_id" => $val
                                    );
                                    
                                    $this->db->where($w_atp);
                                    $update = $this->db->update("overtimes_close_payroll",$v_atp);
                                }
                                $check_payroll_period = $this->overtime->check_payroll_period($this->company_id);
                                $idates_now = idates_now();
                                
                                if ($check_payroll_period != FALSE) {
                                    $period_to = $check_payroll_period->period_to;
                                    $idates_now = $period_to . " " . date("H:i:s");
                                }

                                $fields = array(
                                    "overtime_status"  => "approved",
                                    "approval_date"    => date("Y-m-d H:i:s")
                                );
                                
                                $where = array(
                                    "overtime_id"  => $val,
                                    "company_id"   => $this->company_id
                                );
                                
                                $this->overtime->update_field("employee_overtime_application",$fields,$where);
                                payroll_cronjob_helper('overtime',$ot_info->overtime_from,$ot_info->emp_id,$this->company_id);

                                // this checks the overtime list if valid $val is for the overtime id
                                $overtime_list = $this->overtime->overtime_get_data($this->company_id, $val);
                                if ($overtime_list) {
                                    // once valid overtime then we compare overtime log dates to employee time in
                                    $time_in_check = $this->overtime->employee_time_in_against($this->company_id, $overtime_list->emp_id, $overtime_list->overtime_to);
                                    if ($time_in_check) {
                                        // if value is true then we transfer the total_hours_Required to total so this will be valid
                                        $time_in_field = array(
                                            "total_hours" => $time_in_check->total_hours_required
                                        );
                                        // will just ave to make sure if company_id is the right on then employee time id and employee id is valid
                                        $time_in_where = array(
                                            "comp_id" => $this->company_id,
                                            "employee_time_in_id" => $time_in_check->employee_time_in_id,
                                            "emp_id" => $overtime_list->emp_id
                                        );
                                        // $this->overtime->update_field("employee_time_in", $time_in_field, $time_in_where);
                                    }
                                }
                                // ------------------------------- OVERTIME APPROVE START ------------------------------- //
                                // ############################### notify staff start ################################
                                if ($approval_group_notification->notify_staff == "yes") {
                                    // notify staff via email
                                    $this->send_overtime_notifcation($val, $this->company_id, $ot_info->emp_id, $emp_email, $emp_name, $curr_approver, "last", "", "");
                                    
                                    // notify next via sms
                                    if ($approval_group_notification->sms_notification == "yes") {
                                        $sms_message = "Your overtime application has been approved by {$curr_approver}.";
                                        send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                    }
                                                                    
                                    // notify staff via message board
                                    if ($approval_group_notification->message_board_notification == "yes") {
                                        $emp_notif_message = "Your overtime application has been approved by {$curr_approver}.";
                                        send_to_message_board($psa_id, $ot_info->emp_id, $curr_approver_account_id, $this->company_id, $emp_notif_message, "system");
                                    }
                                }
                                
                                // ############################### notify staff end ################################
                                ################################ notify payroll admin start ################################
                                if($approval_group_notification->notify_payroll_admin == "yes"){
                                    // HRs
                                    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($this->psa_id);
                                    if($payroll_admin_hr){
                                        foreach ($payroll_admin_hr as $pahr){
                                            $pahr_email = $pahr->email;
                                            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);

                                            ###check email settings if enabled###
                                            if($pahr->ns_overtime_email_flag == "yes"){
                                                $this->send_overtime_notifcation($val, $this->company_id, $ot_info->emp_id, $pahr_email, $pahr_name, $curr_approver, "last", "", "", "", "Yes");
                                            }
                                            ###check email settings if enabled###
                                        }
                                    }

                                    // Owner
                                    $pa_owner = get_approver_owner_info($this->company_id);
                                    if($pa_owner){
                                        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
                                        $pa_owner_email = $pa_owner->email;
                                        $pa_owner_account_id = $pa_owner->account_id;

                                        $this->send_overtime_notifcation($val, $this->company_id, $ot_info->emp_id, $pa_owner_email, $pa_owner_name, $curr_approver, "last", "", "", "", "Yes");
                                    }
                                }
                                ################################ notify payroll admin end ################################
                            } else {
                                $next_level = $approver_level + 1;
                                $new_token = $this->overtime->generate_overtime_level_token($next_level, $val);
                                
                                foreach ($ot_approvers as $nextapr) {
                                    if ($nextapr->level == $next_level) {
                                        $next_appr_name = ucwords($nextapr->first_name . " " . $nextapr->last_name);
                                        $next_appr_account_id = $nextapr->account_id;
                                        $next_appr_email = $nextapr->email;
                                        $next_appr_emp_id = $nextapr->emp_id;
                                        
                                        $token = $this->overtime->get_token($val, $this->company_id, $ot_info->emp_id);
                                        $url = base_url() . "approval/overtime/index/" . $token . "/" . $new_token . "/1" . $next_appr_emp_id . "0";
                                        
                                        ###check email settings if enabled###
                                        if($nextapr->ns_overtime_email_flag == "yes"){
                                            // notify next approver via email
                                            $this->send_overtime_notifcation($val, $this->company_id, $ot_info->emp_id, $next_appr_email, $next_appr_name, $curr_approver, "Approver", "Yes", $new_token, $next_appr_emp_id);
                                        }
                                        ###end checking email settings if enabled###

                                        // notify next approver via sms
                                        if ($approval_group_notification->sms_notification == "yes") {
                                            $sms_message = "Click {$url} to approve {$emp_name}'s overtime.";
                                            send_this_sms_global($this->company_id, $next_appr_account_id, $sms_message, $psa_id, false);
                                        }
                                                                            
                                        // notify next approver via message board
                                        if ($approval_group_notification->message_board_notification == "yes") {
                                            $next_appr_notif_message = "An overtime application filed by {$emp_name} has been approved by {$curr_approver} and is now waiting for your approval. 
    										Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
                                            send_to_message_board($psa_id, $next_appr_emp_id, $curr_approver_account_id, $this->company_id, $next_appr_notif_message, "system");
                                        }
                                    }
                                }
                                
                                // ############################### notify staff start ################################
                                if ($approval_group_notification->notify_staff == "yes") {
                                    // notify staff via email
                                    $this->send_overtime_notifcation($val, $this->company_id, $ot_info->emp_id, $emp_email, $emp_name . "/next level", $curr_approver, "", "", "");
                                    
                                    // notify next via sms
                                    if ($approval_group_notification->sms_notification == "yes") {
                                        $sms_message = "Your overtime application has been approved by {$curr_approver} and is waiting for next level's approval.";
                                        send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                    }
                                                                    
                                    // notify staff via message board
                                    if ($approval_group_notification->message_board_notification == "yes") {
                                        $emp_notif_message = "Your overtime application has been approved by {$curr_approver} and is waiting for next level's approval.";
                                        send_to_message_board($psa_id, $ot_info->emp_id, $curr_approver_account_id, $this->company_id, $emp_notif_message, "system");
                                    }
                                }
                                // ############################### notify staff end ################################

                                ################################ notify payroll admin start ################################
                                if($approval_group_notification->notify_payroll_admin == "yes"){
                                    // HRs
                                    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($this->psa_id);
                                    if($payroll_admin_hr){
                                        foreach ($payroll_admin_hr as $pahr){
                                            $pahr_email = $pahr->email;
                                            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);

                                            ###check email settings if enabled###
                                            if($pahr->ns_overtime_email_flag == "yes"){
                                                $this->send_overtime_notifcation($val, $this->company_id, $ot_info->emp_id, $pahr_email, $pahr_name."/next level", $curr_approver, "", "", "", "", "Yes");
                                            }
                                            ###end checking email settings if enabled###
                                        }
                                    }

                                    // Owner
                                    $pa_owner = get_approver_owner_info($this->company_id);
                                    if($pa_owner){
                                        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
                                        $pa_owner_email = $pa_owner->email;
                                        $pa_owner_account_id = $pa_owner->account_id;

                                        ###check email settings if enabled###
                                        if($pa_owner->ns_overtime_email_flag == "yes"){
                                            $this->send_overtime_notifcation($val, $this->company_id, $ot_info->emp_id, $pa_owner_email, $pa_owner_name."/next level", $curr_approver, "", "", "", "", "Yes");
                                        }
                                        ###end checking email settings if enabled###
                                    }
                                }
                                ################################ notify payroll admin end ################################
                            }
                            // ############################### APPROVE ENDS HERE ################################
                        } // end [if has notification and approvers turn to approve and is pending]
                    }
                }
            }
            echo json_encode(array(
                "success" => TRUE,
                "error" => "Overtime has been approved"
            ));
            return false;
        }
    }

    public function reject_overtime()
    {
        if ($this->input->post('reject_overtime')) {
            $overtime_id = $this->input->post('overtime_id');

            // check if the application is lock for filing
            $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id);
            if($get_lock_payroll_process_settings) {
                if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->approval_error
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->ts_approval_recalculation_err_msg
                    ));
                    return false;
                } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                    echo json_encode(array(
                        "success" => false,
                        "error" => $get_lock_payroll_process_settings->py_approval_recalculation_err_msg
                    ));
                    return false;
                }
            }
            
            $overtime_ids = $this->input->post('overtime_id');
            $overtime_id = array();
            array_push($overtime_id, $overtime_ids);
            foreach ($overtime_id as $key => $val) {
                
                $ot_info = $this->overtime->overtime_information($this->company_id, $val);
                $employee_details = get_employee_details_by_empid($ot_info->emp_id);
                
                if ($ot_info) { // start [overtime information]
                    $curr_approver = "";
                    $curr_approver_account_id = "";
                    $curr_level = $ot_info->level;
                    
                    // get workforce notification settings
                    $approval_group_notification = get_notify_settings($employee_details->overtime_approval_grp, $this->company_id);
                    
                    // get approver's current level
                    $approver_emp_id = get_approver_emp_id_via_account_id($this->account_id);
                    $approver_emp_id = ($this->session->userdata("user_type_id") == 2) ? "-99{$this->company_id}" : $approver_emp_id;
                    $approver_level = get_current_approver_level($employee_details->overtime_approval_grp, $approver_emp_id, $this->company_id);
                    
                    // check if employee overtime application is pending
                    $is_emp_overtime_pending = $this->overtime->is_emp_overtime_pending($val, $this->company_id);
                    
                    $psa_id = $this->session->userdata('psa_id');
                    
                    if ($approval_group_notification && $is_emp_overtime_pending) { // start [if has approval group notification and leave application is pending]
                        $workflow_approved_by_data = array(
                            
                            'application_id' => $val,
                            'approver_id' => $approver_emp_id,
                            'workflow_level' => $curr_level,
                            'workflow_type' => 'overtime'
                        
                        );
                        $this->db->insert('workflow_approved_by', $workflow_approved_by_data);
                        
                        $ot_approvers = $this->agm->get_approver_name_overtime($ot_info->emp_id, $ot_info->company_id);
                        
                        $emp_name = ucwords($employee_details->first_name . " " . $employee_details->last_name);
                        $emp_email = $employee_details->email;
                        $emp_account_id = $employee_details->account_id;
                        
                        if ($this->session->userdata("user_type_id") == 2) {
                            $owner_approver = get_approver_owner_info($this->company_id);
                            $curr_approver = ucwords($owner_approver->first_name . " " . $owner_approver->last_name);
                            $curr_approver_account_id = $this->account_id;
                        } else {
                            foreach ($ot_approvers as $oa) {
                                if ($oa->level == $approver_level && $oa->emp_id == $approver_emp_id) {
                                    $curr_approver = ucwords($oa->first_name . " " . $oa->last_name);
                                    $curr_approver_account_id = $oa->account_id;
                                }
                            }
                        }
                                                
                        // ------------------------------- OVERTIME REJECT START ------------------------------- //
                        $fields = array(
                            "overtime_status" => "reject"
                        );
                        $where = array(
                            "overtime_id" => $val,
                            "company_id" => $this->company_id
                        );
                        $rejected = $this->overtime->update_field("employee_overtime_application", $fields, $where);
                        // ------------------------------- OVERTIME REJECT START ------------------------------- //
                        if ($rejected) {
                            $approver_emp_id_check = ($approver_emp_id == "-99{$this->company_id}") ? "" : $approver_emp_id;
                            
                            foreach ($ot_approvers as $nextapr) {
                                if ($nextapr->level >= $approver_level && $nextapr->emp_id != $approver_emp_id_check) {
                                    $next_appr_name = ucwords($nextapr->first_name . " " . $nextapr->last_name);
                                    $next_appr_account_id = $nextapr->account_id;
                                    $next_appr_email = $nextapr->email;
                                    $next_appr_emp_id = $nextapr->emp_id;
                                    
                                    if ($nextapr->emp_id == "") {
                                        $owner_approver = get_approver_owner_info($this->company_id);
                                        $next_appr_name = ucwords($owner_approver->first_name . " " . $owner_approver->last_name);
                                        $next_appr_account_id = $owner_approver->account_id;
                                        $next_appr_email = $owner_approver->email;
                                        $next_appr_emp_id = "";
                                    }
                                    
                                    // notify next approver via email
                                    $this->send_overtime_reject_notifcation($val, $this->company_id, $ot_info->emp_id, $next_appr_email, $next_appr_name, $curr_approver, "", "", "");
                                    ;
                                    // notify next approver via sms
                                    if ($approval_group_notification->sms_notification == "yes") {
                                        $sms_message = "An overtime application filed by {$emp_name} has been rejected by {$curr_approver}.";
                                        send_this_sms_global($this->company_id, $next_appr_account_id, $sms_message, $psa_id, false);
                                    }
                                                                        
                                    // notify next approver via message board
                                    if ($approval_group_notification->message_board_notification == "yes") {
                                        $next_appr_notif_message = "An overtime application filed by {$emp_name} has been rejected by {$curr_approver}.";
                                        send_to_message_board($psa_id, $next_appr_emp_id, $curr_approver_account_id, $this->company_id, $next_appr_notif_message, "system");
                                    }                                    
                                }
                            }
                            
                            // ############################### notify staff start ################################
                            if ($approval_group_notification->notify_staff == "yes") {
                                // notify staff via email
                                $this->send_overtime_reject_notifcation($val, $this->company_id, $ot_info->emp_id, $emp_email, $emp_name, $curr_approver, "last", "", "");
                                
                                // notify next via sms
                                if ($approval_group_notification->sms_notification == "yes") {
                                    $sms_message = "Your overtime application has been rejected by {$curr_approver}.";
                                    send_this_sms_global($this->company_id, $emp_account_id, $sms_message, $psa_id, false);
                                }
                                
                                // notify staff via message board
                                if ($approval_group_notification->message_board_notification == "yes") {
                                    $emp_notif_message = "Your overtime application has been rejected by {$curr_approver}.";
                                    send_to_message_board($psa_id, $ot_info->emp_id, $curr_approver_account_id, $this->company_id, $emp_notif_message, "system");
                                }
                            }
                            // ############################### notify staff end ################################

                            if($approval_group_notification->notify_payroll_admin == "yes"){
                                // HRs
                                $payroll_admin_hr = $this->employee->get_payroll_admin_hr($this->psa_id);
                                if($payroll_admin_hr){
                                    foreach ($payroll_admin_hr as $pahr){
                                        $pahr_email = $pahr->email;
                                        $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);

                                        ###check email settings if enabled###
                                        if($this->company_id == "1"){
                                            if($pahr->ns_overtime_email_flag == "yes"){
                                                $this->send_overtime_reject_notifcation($val, $this->company_id, $ot_info->emp_id, $pahr_email, $pahr_name, $curr_approver, "last", "", "");
                                            }
                                        }else{
                                            $this->send_overtime_reject_notifcation($val, $this->company_id, $ot_info->emp_id, $pahr_email, $pahr_name, $curr_approver, "last", "", "");
                                        }
                                        ###end checking email settings if enabled###
                                    }
                                }

                                // Owner
                                $pa_owner = get_approver_owner_info($this->company_id);
                                if($pa_owner){
                                    $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
                                    $pa_owner_email = $pa_owner->email;
                                    $pa_owner_account_id = $pa_owner->account_id;

                                    ###check email settings if enabled###
                                    if($this->company_id == "1"){
                                        if($pa_owner->ns_overtime_email_flag == "yes"){
                                            $this->send_overtime_reject_notifcation($val, $this->company_id, $ot_info->emp_id, $pa_owner_email, $pa_owner_name, $curr_approver, "last", "", "");
                                        }
                                    }else{
                                        $this->send_overtime_reject_notifcation($val, $this->company_id, $ot_info->emp_id, $pa_owner_email, $pa_owner_name, $curr_approver, "last", "", "");
                                    }
                                    ###end checking email settings if enabled###
                                }
                            }
                        }
                    } // end [if has approval group notification and leave application is pending]
                }
            }
            echo json_encode(array(
                "success" => TRUE,
                "error" => "Overtime has been rejected"
            ));
            return true;
        }
    }

    public function tweetontwitter($account_id, $message2, $pm_hr_twitter)
    {
        if ($account_id) {
            $message2 = str_replace("-", " ", strip_tags($message2));
            $profile = $this->social->tweet_account($account_id);
            if ($profile) {
                $consumer_key = $profile->consumer_key; // "AMwFOney54o2jnU5Cek8RDpHJ";
                $consumer_secret = $profile->consumer_secret; // "MEL5Ztfh241p6dgd9T7Yhjqbqsfu3MhthZxY6CMQ8wfAJDtncB";
                $oauth_access_token = $profile->oauth_access_token; // "265681837-JfQ7iYxJopy88K1h4ec5nHvbldOsNGwwTJsc6WkS";
                $oauth_access_token_secret = $profile->oauth_access_token_secret; // "2k1sqmSwzSvkiSsEL41nNknJwVI3qtDKaHGtG2U6vtIjw";
                $this->connection = $this->twitteroauth->create($consumer_key, $consumer_secret, $oauth_access_token, $oauth_access_token_secret);
                $data['verify'] = $this->connection->get('account/verify_credentials');
                if ($this->connection) {
                    /**
                     * disable lang ni mao ning katong twitter post sa imong wall
                     * $message = array(
                     * 'status' => urldecode($message2),
                     * 'possibly_sensitive'=>false
                     * );
                     * $result = $this->connection->post('statuses/update', $message);
                     */
                    if ($pm_hr_twitter) {
                        $options = array(
                            "screen_name" => $pm_hr_twitter,
                            "text" => urldecode($message2)
                        );
                        $direct = $this->connection->post('direct_messages/new', $options);
                        return true;
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
    }

    public function send_leave_notifcation($leave_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "", $withlink = "No", $level_token = "", $appr_id = "")
    {
        $leave_information = $this->agm->leave_information($leave_ids);
        
        if ($leave_information != FALSE) {
            $fullname = ucfirst($leave_information->first_name) . " " . ucfirst($leave_information->last_name);
            $date_applied = date("F d, Y", strtotime($leave_information->date_filed));
            $leave_type = $leave_information->leave_type;
            $concat_start_date = date("F d, Y | h:i A", strtotime($leave_information->date_start));
            $concat_end_date = date("F d, Y | h:i A", strtotime($leave_information->date_end));
            $concat_return_date = date("F d, Y | h:i A", strtotime($leave_information->date_return));
            $total_leave_request = $leave_information->total_leave_requested;
            $reason = $leave_information->reasons;
            
            $token = $this->leave->get_token($leave_ids, $comp_id, $emp_id);
            
            $link = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="' . base_url() . 'approval/leave/index/' . $token . '/' . $level_token . '/1' . $appr_id . '0">View Leave Application</a>';
            if ($who == "Approver") {
                $waiting = " and is waiting for Your Approval";
                if ($withlink == "No") {
                    $link = '';
                    $waiting = "";
                }
            } else {
                $pieces = explode("/", $approver_full_name);
                $approver_full_name = $pieces[0];
                if ($who == "last") {
                    $waiting = "";
                } elseif ($who != "not") {
                    if ($pieces[1]) {
                        $current = $pieces[1];
                        $waiting = "and is waiting for {$current}'s approval";
                    }
                }
                $link = '';
            }
            $font_name = "'Open Sans'";
            
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            
            $this->load->library('email', $config);
            $this->email->initialize($config);
            $this->email->set_newline("\r\n");
            $this->email->from('notifications@ashima.ph', 'Ashima');
            $this->email->to($email);
            $this->email->subject('Leave Application - ' . $fullname);
            
            $font_name = "'Open Sans'";
            $this->email->message('
		<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
		<html lang="en">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
				<meta name="format-detection" content="telephone=no">
				<title>Leave Application</title>
				<style type="text/css">
					.ReadMsgBody {width: 100%; background-color: #ebebeb;}
					.ExternalClass {width: 100%; background-color: #ebebeb;}
					.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
					body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
					body {margin:0;padding:0;}
					table {border-spacing:0;}
					table td {border-collapse:collapse;}
					.yshortcuts a {border-bottom: none !important;}
				</style>
			</head>
			<body>
				<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td style="padding:30px 0 50px;" valign="top" align="center">
							<table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
								<tr>
						        	<td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="' . (newsletter_logo($comp_id)) . '" height="62" alt=" "></td>
						        </tr>
								<tr>
									<td valign="top" align="center">
										<table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
											<tr>
												<td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
													<table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
														<tr>
															<td valign="top">
																<h2 style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi ' . $approver_full_name . ',</h2>
																<p style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Leave Application below has been approved by ' . $last_approver . ' ' . $waiting . '.</p>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>
												<td valign="top" style="padding-top:25px;">
													<table width="100%" border="0" cellspacing="0" cellpadding="0">
														<tr>
															<td style="width:140px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $date_applied . '</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Leave Type:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $leave_type . '</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Reason:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $reason . '</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Start Date:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $concat_start_date . '</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">End Date</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $concat_end_date . '</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Return Date:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $concat_return_date . '</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Status:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">Approved by ' . $last_approver . '</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Total Requested Leave:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $total_leave_request . ' Day(s)</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:40px;"></td>
														</tr>
														<tr>
															<td>&nbsp;</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">
																' . $link . '
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
							<table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; ' . date('Y') . ' Konsum Technologies. All Rights Reserved.</td>
									<td valign="top"><img src="' . base_url() . 'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</body>
		</html>
		');
            if ($this->email->send()) {
                return true;
            } else {
                return false;
            }
        } else {
            show_error("Invalid token");
        }
    }

    public function send_leave_reject_notifcation($leave_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "", $withlink = "No", $level_token = "")
    {
        $leave_information = $this->agm->leave_information($leave_ids);
        
        if ($leave_information != FALSE) {
            $fullname = ucfirst($leave_information->first_name) . " " . ucfirst($leave_information->last_name);
            $date_applied = date("F d, Y", strtotime($leave_information->date_filed));
            $leave_type = $leave_information->leave_type;
            $concat_start_date = date("F d, Y | h:i A", strtotime($leave_information->date_start));
            $concat_end_date = date("F d, Y | h:i A", strtotime($leave_information->date_end));
            $concat_return_date = date("F d, Y | h:i A", strtotime($leave_information->date_return));
            $total_leave_request = $leave_information->total_leave_requested;
            $reason = $leave_information->reasons;
            
            $token = $this->leave->get_token($leave_ids, $comp_id, $emp_id);
            $link = '';
            
            $font_name = "'Open Sans'";
            
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            
            $this->load->library('email', $config);
            $this->email->initialize($config);
            $this->email->set_newline("\r\n");
            $this->email->from(notifications_ashima_email(), 'Ashima');
            $this->email->to($email);
            $this->email->subject('Leave Application - ' . $fullname);
            $font_name = "'Open Sans'";
            $this->email->message('
		<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
		<html lang="en">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
				<meta name="format-detection" content="telephone=no">
				<title>Leave Application</title>
				<style type="text/css">
					.ReadMsgBody {width: 100%; background-color: #ebebeb;}
					.ExternalClass {width: 100%; background-color: #ebebeb;}
					.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
					body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
					body {margin:0;padding:0;}
					table {border-spacing:0;}
					table td {border-collapse:collapse;}
					.yshortcuts a {border-bottom: none !important;}
				</style>
			</head>
			<body>
				<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td style="padding:30px 0 50px;" valign="top" align="center">
							<table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
								<tr>
						        	<td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="' . (newsletter_logo($comp_id)) . '" height="62" alt=" "></td>
						        </tr>
								<tr>
									<td valign="top" align="center">
										<table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
											<tr>
												<td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
													<table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
														<tr>
															<td valign="top">
																<h2 style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi ' . $approver_full_name . ',</h2>
																<p style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Leave application below has been rejected.</p>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>
												<td valign="top" style="padding-top:25px;">
													<table width="100%" border="0" cellspacing="0" cellpadding="0">
														<tr>
															<td style="width:140px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $date_applied . '</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Leave Type:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $leave_type . '</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Reason:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $reason . '</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Start Date:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $concat_start_date . '</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">End Date</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $concat_end_date . '</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Return Date:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $concat_return_date . '</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Status:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">Rejected by ' . $last_approver . '</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Total Requested Leave:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $total_leave_request . ' Day(s)</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
															<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:40px;"></td>
														</tr>
														<tr>
															<td>&nbsp;</td>
															<td valign="top">
																' . $link . '
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
							<table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; ' . date('Y') . ' Konsum Technologies. All Rights Reserved.</td>
									<td valign="top"><img src="' . base_url() . 'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</body>
		</html>
		');
            if ($this->email->send()) {
                return true;
            } else {
                return false;
            }
        } else {
            show_error("Invalid token");
        }
    }

    public function send_hours_notifcation($leave_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "", $withlink = "No", $level_token = "", $appr_id = "", $is_split = false, $tardiness_rule_migrated_v3 = false, $notify_admin = "")
    {
        if ($is_split) {
            $timein_information = $this->todo_timein->get_employee_split_time_in($leave_ids);
        } else {
            $timein_information = $this->todo_timein->get_employee_time_in($leave_ids);
        }
        
        $flag = $timein_information->flag_add_logs;
        $hours_cat = "";
        $waiting = '';
        if ($timein_information != FALSE) {
            $get_schedule_settings = get_schedule_settings_by_workschedule_id($timein_information->work_schedule_id,$this->company_id,date("l", strtotime($timein_information->date)));
            $check_break_1_in_min = false;
            $check_break_2_in_min = false;
            
            if($tardiness_rule_migrated_v3) {
                if($get_schedule_settings->enable_additional_breaks == "yes") {
                    if($get_schedule_settings->track_break_2 == "yes") {
                        $break_1_in_min = $get_schedule_settings->break_1_in_min;
                        $break_2_in_min = $get_schedule_settings->break_2_in_min;
                        if($break_1_in_min > 0) {
                            $check_break_1_in_min = true;
                        }
                        
                        if($break_2_in_min > 0) {
                            $check_break_2_in_min = true;
                        }
                    }
                }
            }

            $fullname = ucfirst($timein_information->first_name) . " " . ucfirst($timein_information->last_name);
            $shift_date  = $timein_information->date == NULL ? "none" : date("F d, Y",strtotime($timein_information->date));

            $break_1_start_date_time = "none";
            $break_1_end_date_time   = "none";
            $break_2_start_date_time = "none";
            $break_2_end_date_time   = "none";
            
            $new_break_1_start_date_time = "none";
            $new_break_1_end_date_time   = "none";
            $new_break_2_start_date_time = "none";
            $new_break_2_end_date_time   = "none";
            
            if(!$is_split) {
                $break_1_start_date_time = ($timein_information->break1_out == NULL || $timein_information->break1_out == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->break1_out));
                $break_1_end_date_time = ($timein_information->break1_in == NULL || $timein_information->break1_in == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->break1_in));
                $break_2_start_date_time = ($timein_information->break2_out == NULL || $timein_information->break2_out == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->break2_out));
                $break_2_end_date_time = ($timein_information->break2_in == NULL || $timein_information->break2_in == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->break2_in));
            }

            if ($flag == 0) {
                $date_applied = date("F d, Y", strtotime($timein_information->change_log_date_filed));
                $time_in = date("F d, Y h:i A", strtotime($timein_information->time_in));
                $lunch_out = date("F d, Y h:i A", strtotime($timein_information->lunch_out));
                $lunch_in = date("F d, Y h:i A", strtotime($timein_information->lunch_in));
                $time_out = date("F d, Y h:i A", strtotime($timein_information->time_out));
                
                $new_time_in = ($timein_information->change_log_time_in != NULL) ? date("F d, Y h:i A", strtotime($timein_information->change_log_time_in)) : "none";
                $new_lunch_out = ($timein_information->change_log_lunch_out != NULL) ? date("F d, Y h:i A", strtotime($timein_information->change_log_lunch_in)) : "none";
                $new_lunch_in = ($timein_information->change_log_lunch_in != NULL) ? date("F d, Y h:i A", strtotime($timein_information->change_log_lunch_out)) : "none";
                $new_time_out = ($timein_information->change_log_time_out != NULL) ? date("F d, Y h:i A", strtotime($timein_information->change_log_time_out)) : "none";
                
                if(!$is_split) {
                    $new_break_1_start_date_time = ($timein_information->change_log_break1_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_break1_out)) : "none";
                    $new_break_1_end_date_time =  ($timein_information->change_log_break1_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_break1_in)) : "none";
                    $new_break_2_start_date_time =  ($timein_information->change_log_break2_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_break2_out)) : "none";
                    $new_break_2_end_date_time =  ($timein_information->change_log_break2_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_break2_in)) : "none";
                }

                $subject = "Action Required. {$fullname}'s Attendance Adjustment is awaiting your approval.";
				$hours_cat = "Adjustment";
				$title_line = "Attendance Adjustment";
            } elseif ($flag == 1) {
                $date_applied = date("F d, Y",strtotime($timein_information->change_log_date_filed));
                $time_in = ($timein_information->time_in == NULL || $timein_information->time_in == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->time_in));
                $lunch_out = ($timein_information->lunch_out == NULL || $timein_information->lunch_out == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->lunch_out));
                $lunch_in = ($timein_information->lunch_in == NULL || $timein_information->lunch_in == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->lunch_in));
                $time_out = ($timein_information->time_out == NULL || $timein_information->time_out == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->time_out));
                
                $hours_cat = "Logs";
				$subject = "Action Required. {$fullname}'s New Timesheet submitted is awaiting your approval.";
				$title_line = "New Timesheet";
            } elseif ($flag == 2) {
                $date_applied = date("F d, Y", strtotime($timein_information->date));
                $time_in = ($timein_information->time_in != NULL) ? date("F d, Y h:i A", strtotime($timein_information->time_in)) : "none";
                $lunch_out = ($timein_information->lunch_out != NULL) ? date("F d, Y h:i A", strtotime($timein_information->lunch_out)) : "none";
                $lunch_in = ($timein_information->lunch_in != NULL) ? date("F d, Y h:i A", strtotime($timein_information->lunch_in)) : "none";
                $time_out = ($timein_information->time_out != NULL) ? date("F d, Y h:i A", strtotime($timein_information->time_out)) : "none";
                
                $time_in_loc = ($timein_information->location_1 != NULL) ? $timein_information->location_1 : "none";
                $lunch_out_loc = ($timein_information->location_2 != NULL) ? $timein_information->location_2 : "none";
                $lunch_in_loc = ($timein_information->location_3 != NULL) ? $timein_information->location_3 : "none";
                $time_out_loc = ($timein_information->location_4 != NULL) ? $timein_information->location_4 : "none";
                
                $hours_cat = "Location Base Login";
				$subject = $hours_cat. ' Application - '.$fullname;
				$title_line = "Location Base Login";
            }
            
            $total_hours = $timein_information->total_hours;
            $total_hours_required = $timein_information->total_hours_required;
            $tardiness_min = $timein_information->tardiness_min;
            $undertime_min = $timein_information->undertime_min;
            $reason = $timein_information->reason;
            
            // $token = $this->timeins->get_token($leave_ids, $comp_id, $emp_id);
            $token = $this->todo_timein->get_token($leave_ids, $comp_id, $emp_id, $timein_information->approval_time_in_id);
            $link = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="' . base_url() . 'approval/time_in/index/' . $token . '/' . $level_token . '/1' . $appr_id . '0">View Attendance Adjustment</a>';
            $font_name = "'Open Sans'";
            $message_body = "";
            if ($who == "Approver") {
                $waiting = " and is waiting for Your Approval";
                if ($withlink == "No") {
                    $link = '';
                    $waiting = '';
                    $subject = "You're Next In Line, {$fullname}'s {$title_line} has been submitted.";
                }
            } else {
                $pieces = explode("/", $approver_full_name);
                $approver_full_name = $pieces[0];
                
                if ($who == "last") {
                    $waiting = "";
                    $subject = "Yay! Your {$title_line} request was approved.";

					if($notify_admin == "Yes" || $notify_admin == "No") {
					    $subject = "Attention Needed. {$title_line} by {$fullname}'s has been approved.";
					}
                } elseif ($who != "not") {
                    if ($pieces[1]) {
                        $current = $pieces[1];
                        $waiting = "and is waiting for {$current}'s approval";
                        $subject = "Your {$title_line} request is awaiting for approval.";

						if($notify_admin == "Yes") {
						    $subject = "{$fullname}'s {$title_line} is routed for approval.";
						}
                    }
                }
                $link = '';
            }

            $message_body_additional_break_add = "";
            $message_body_additional_break_change = "";
            
            if($tardiness_rule_migrated_v3 && !$is_split) {
                $message_body_additional_break_add1 = "";
                $message_body_additional_break_add2 = "";
                
                if ($check_break_1_in_min) {
                    $message_body_additional_break_add1 = '
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">ADDITIONAL BREAKS</td>
                                <td>
                                <hr style="color: #ccc !important;margin-top: -10px !important;">
                                </td>
                            </tr>
                                
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">First Break Out:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$break_1_start_date_time.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">First Break In:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$break_1_end_date_time.'</td>
                        </tr>
                    ';
                } 
                
                if ($check_break_2_in_min) {
                    $message_body_additional_break_add2 = '
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Second Break Out:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$break_2_start_date_time.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Second Break In:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$break_2_end_date_time.'</td>
                        </tr>
                    ';
                }
                
                $message_body_additional_break_add = $message_body_additional_break_add1.' '.$message_body_additional_break_add2;
                
                $message_body_additional_break_change1 = "";
                $message_body_additional_break_change2 = "";
                
                if ($check_break_1_in_min) {
                    $message_body_additional_break_change1 = '
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">ADDITIONAL BREAKS</td>
                                <td>
                                <hr style="color: #ccc !important;margin-top: -10px !important;">
                                </td>
                            </tr>
                                
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">First Break Out:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_break_1_start_date_time.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">First Break In:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_break_1_end_date_time.'</td>
                        </tr>
                    ';
                }
                
                if ($check_break_2_in_min) {
                    
                    $message_body_additional_break_change2 = '
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Second Break Out:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_break_2_start_date_time.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Second Break In:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_break_2_end_date_time.'</td>
                        </tr>
                    ';
                }
                
                $message_body_additional_break_change = $message_body_additional_break_change1.' '.$message_body_additional_break_change2;
            }

            if($flag == 0){
                $message_body = '
                            <tr>
                                <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"> Applicant:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$fullname.'</td>
                            </tr>
                            <tr>
                                <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date_applied.'</td>
                            </tr>

                            <tr>
                                    <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Shift Date:</td>
                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$shift_date.'</td>
                                </tr>

                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">&nbsp;</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;"><strong>CHANGE FROM:</strong></td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_in.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_out.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_in.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_out.'</td>
                            </tr>

                            '.$message_body_additional_break_add.'

                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">&nbsp;</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;"><strong>CHANGE TO:</strong></td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_time_in.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_lunch_out.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_lunch_in.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_time_out.'</td>
                            </tr>

                            '.$message_body_additional_break_change.'

                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Reason:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$timein_information->reason.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$timein_information->notes.'</td>
                            </tr>
                    ';
            }else{
                $message_body = '
                        <tr>
                            <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"> Applicant:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$fullname.'</td>
                        </tr>
                        <tr>
                            <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date_applied.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_in.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_out.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_in.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_out.'</td>
                        </tr>

                        '.$message_body_additional_break_add.'

                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$timein_information->notes.'</td>
                        </tr>
                ';

            }
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            
            $this->load->library('email', $config);
            $this->email->initialize($config);
            $this->email->set_newline("\r\n");
            $this->email->from(notifications_ashima_email(), 'Ashima');
            $this->email->to($email);
            $this->email->subject($subject);
            $font_name = "'Open Sans'";
            $this->email->message('
			<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
			<html lang="en">
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
					<meta name="format-detection" content="telephone=no">
					<title>Attendance ' . $hours_cat . '</title>
					<style type="text/css">
						.ReadMsgBody {width: 100%; background-color: #ebebeb;}
						.ExternalClass {width: 100%; background-color: #ebebeb;}
						.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
						body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
						body {margin:0;padding:0;}
						table {border-spacing:0;}
						table td {border-collapse:collapse;}
						.yshortcuts a {border-bottom: none !important;}
					</style>
				</head>
				<body>
					<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td style="padding:30px 0 50px;" valign="top" align="center">
								<table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
									<tr>
							        	<td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="' . (newsletter_logo($comp_id)) . '" height="62" alt=" "></td>
							        </tr>
									<tr>
										<td valign="top" align="center">
											<table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
												<tr>
													<td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
														<table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
															<tr>
																<td valign="top">
																	<h2 style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi ' . $approver_full_name . ',</h2>
																	<p style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0; text-trasnform:capitalized">' . $hours_cat . ' below has been approved by ' . $last_approver . '' . $waiting . '.</p>
																</td>
															</tr>
														</table>
													</td>
												</tr>
												<tr>
													<td valign="top" style="padding-top:25px;">
														<table width="100%" border="0" cellspacing="0" cellpadding="0">
											 				' . $message_body . '
															<tr>
																<td>&nbsp;</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">
																	' . $link . '
																</td>
															</tr>
														</table>
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
								<table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; ' . date('Y') . ' Konsum Technologies. All Rights Reserved.</td>
										<td valign="top"><img src="' . base_url() . 'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</body>
			</html>
			');
            if ($this->email->send()) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function send_hours_reject_notifcation($leave_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "", $withlink = "No", $level_token = "", $is_split = "", $tardiness_rule_migrated_v3 = false, $notify_staff = "")
    {
        if ($is_split) {
            $timein_information = $this->todo_timein->get_employee_split_time_in($leave_ids);
        } else {
            $timein_information = $this->todo_timein->get_employee_time_in($leave_ids);
        }

        $flag = $timein_information->flag_add_logs;
        $hours_cat = "";

        if ($timein_information != FALSE) {
            
            $check_break_1_in_min = false;
            $check_break_2_in_min = false;
            
            if( ! $is_split) {
                $get_schedule_settings = get_schedule_settings_by_workschedule_id($timein_information->work_schedule_id,$this->company_id,date("l", strtotime($timein_information->date)));
                if($tardiness_rule_migrated_v3) {
                    if($get_schedule_settings->enable_additional_breaks == "yes") {
                        if($get_schedule_settings->track_break_2 == "yes") {
                            $break_1_in_min = $get_schedule_settings->break_1_in_min;
                            $break_2_in_min = $get_schedule_settings->break_2_in_min;
                            if($break_1_in_min > 0) {
                                $check_break_1_in_min = true;
                            }
                            
                            if($break_2_in_min > 0) {
                                $check_break_2_in_min = true;
                            }
                        }
                    }
                }
            }
            
            $fullname = ucfirst($timein_information->first_name)." ".ucfirst($timein_information->last_name);

            $break_1_start_date_time = "none";
            $break_1_end_date_time   = "none";
            $break_2_start_date_time = "none";
            $break_2_end_date_time   = "none";
            
            $new_break_1_start_date_time = "none";
            $new_break_1_end_date_time   = "none";
            $new_break_2_start_date_time = "none";
            $new_break_2_end_date_time   = "none";
            
            if( ! $is_split) {
                $break_1_start_date_time = ($timein_information->break1_out == NULL || $timein_information->break1_out == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->break1_out));
                $break_1_end_date_time = ($timein_information->break1_in == NULL || $timein_information->break1_in == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->break1_in));
                $break_2_start_date_time = ($timein_information->break2_out == NULL || $timein_information->break2_out == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->break2_out));
                $break_2_end_date_time = ($timein_information->break2_in == NULL || $timein_information->break2_in == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->break2_in));
            }

            if($flag==0){
                $date_applied = date("F d, Y",strtotime($timein_information->change_log_date_filed));
                $time_in = ($timein_information->time_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->time_in)) : "none";
                $lunch_out = ($timein_information->lunch_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->lunch_out)) : "none";
                $lunch_in = ($timein_information->lunch_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->lunch_in)) : "none";
                $time_out = ($timein_information->time_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->time_out)) : "none";

                $new_time_in = ($timein_information->change_log_time_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_time_in)) : "none";
                $new_lunch_out = ($timein_information->change_log_lunch_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_lunch_in)) : "none";
                $new_lunch_in = ($timein_information->change_log_lunch_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_lunch_out)) : "none";
                $new_time_out = ($timein_information->change_log_time_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_time_out)) : "none";
                
                if( ! $is_split) {
                    $new_break_1_start_date_time = ($timein_information->change_log_break1_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_break1_out)) : "none";
                    $new_break_1_end_date_time =  ($timein_information->change_log_break1_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_break1_in)) : "none";
                    $new_break_2_start_date_time =  ($timein_information->change_log_break2_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_break2_out)) : "none";
                    $new_break_2_end_date_time =  ($timein_information->change_log_break2_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_break2_in)) : "none";
                }
                
                $hours_cat = "Attendance Adjustment";
                $title_line = "Attendance Adjustment";
				$subject = "Ooops! {$title_line} by {$fullname}'s has been rejected.";
            }elseif($flag==1){
                $date_applied = date("F d, Y",strtotime($timein_information->change_log_date_filed));
                $time_in = ($timein_information->time_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->time_in)) : "none";
                $lunch_out = ($timein_information->lunch_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->lunch_out)) : "none";
                $lunch_in = ($timein_information->lunch_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->lunch_in)) : "none";
                $time_out = ($timein_information->time_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->time_out)) : "none";

                $hours_cat = "Attendance Logs";
                $title_line = "New Timesheet";
				$subject = "Ooops! {$title_line} by {$fullname}'s has been rejected.";
            }elseif($flag==2){
                $date_applied = date("F d, Y",strtotime($timein_information->change_log_date_filed));
                $time_in = ($timein_information->time_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->time_in)) : "none";
                $lunch_out = ($timein_information->lunch_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->lunch_in)) : "none";
                $lunch_in = ($timein_information->lunch_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->lunch_out)) : "none";
                $time_out = ($timein_information->time_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->time_out)) : "none";
                
                $hours_cat = "Location Base Login";
                $title_line = "Location Base Login";
				$subject = "Ooops! {$title_line} by {$fullname}'s has been rejected.";
            }

            $total_hours = $timein_information->total_hours;
            $total_hours_required = $timein_information->total_hours_required;
            $tardiness_min = $timein_information->tardiness_min;
            $undertime_min = $timein_information->undertime_min;
            $reason = $timein_information->reason;
            $font_name = "'Open Sans'";
            
            $message_body_additional_break_add = "";
            $message_body_additional_break_change = "";

            if($notify_staff == "Yes") {
			    $subject = "Oh no! Your {$title_line} was rejected.";
			}

            if($tardiness_rule_migrated_v3 && (!$is_split)) {
                $message_body_additional_break_add1 = "";
                $message_body_additional_break_add2 = "";
                
                if ($check_break_1_in_min) {
                    $message_body_additional_break_add1 = '
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">ADDITIONAL BREAKS</td>
                                    <td>
                                    <hr style="color: #ccc !important;margin-top: -10px !important;">
                                    </td>
                                </tr>
                                    
                                <tr>
                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">First Break Out:</td>
                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$break_1_start_date_time.'</td>
                                </tr>
                                <tr>
                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">First Break In:</td>
                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$break_1_end_date_time.'</td>
                                </tr>
                            ';
                }
                
                if ($check_break_2_in_min) {
                    $message_body_additional_break_add2 = '
                                <tr>
                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Second Break Out:</td>
                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$break_2_start_date_time.'</td>
                                </tr>
                                <tr>
                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Second Break In:</td>
                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$break_2_end_date_time.'</td>
                                </tr>
                            ';
                }
                
                $message_body_additional_break_add = $message_body_additional_break_add1.' '.$message_body_additional_break_add2;
                
                $message_body_additional_break_change1 = "";
                $message_body_additional_break_change2 = "";
                
                if ($check_break_1_in_min) {
                    $message_body_additional_break_change1 = '
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">ADDITIONAL BREAKS</td>
                                    <td>
                                    <hr style="color: #ccc !important;margin-top: -10px !important;">
                                    </td>
                                </tr>
                                    
                                <tr>
                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">First Break Out:</td>
                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_break_1_start_date_time.'</td>
                                </tr>
                                <tr>
                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">First Break In:</td>
                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_break_1_end_date_time.'</td>
                                </tr>
                            ';
                }
                
                if ($check_break_2_in_min) {
                    $message_body_additional_break_change2 = '
                                <tr>
                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Second Break Out:</td>
                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_break_2_start_date_time.'</td>
                                </tr>
                                <tr>
                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Second Break In:</td>
                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_break_2_end_date_time.'</td>
                                </tr>
                            ';
                }
                
                $message_body_additional_break_change = $message_body_additional_break_change1.' '.$message_body_additional_break_change2;
            }

            if($flag == 0){
                $message_body = '
                        <tr>
                            <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"> Applicant:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$fullname.'</td>
                        </tr>
                        <tr>
                            <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date_applied.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">&nbsp;</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;"><strong>CHANGE FROM:</strong></td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_in.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_out.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_in.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_out.'</td>
                        </tr>

                        '.$message_body_additional_break_add.'

                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">&nbsp;</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;"><strong>CHANGE TO:</strong></td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_time_in.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_lunch_out.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_lunch_in.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_time_out.'</td>
                        </tr>

                        '.$message_body_additional_break_change.'

                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Reason:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$timein_information->reason.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$timein_information->notes.'</td>
                        </tr>
                ';
            }else{
                $message_body = '
                        <tr>
                            <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"> Applicant:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$fullname.'</td>
                        </tr>
                        <tr>
                            <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date_applied.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_in.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_out.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_in.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_out.'</td>
                        </tr>

                        '.$message_body_additional_break_add.'

                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$timein_information->notes.'</td>
                        </tr>
                    ';

            }

            $link = '';

            $font_name = "'Open Sans'";

            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';

            $this->load->library('email',$config);
            $this->email->initialize($config);
            $this->email->set_newline("\r\n");
            $this->email->from(notifications_ashima_email(),'Ashima');
            $this->email->to($email);
            $this->email->subject($subject);
            $font_name = "'Open Sans'";
            $this->email->message('
                <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
                <html lang="en">
                    <head>
                        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                        <meta name="format-detection" content="telephone=no">
                        <title>'.$hours_cat.'</title>
                        <style type="text/css">
                            .ReadMsgBody {width: 100%; background-color: #ebebeb;}
                            .ExternalClass {width: 100%; background-color: #ebebeb;}
                            .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
                            body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
                            body {margin:0;padding:0;}
                            table {border-spacing:0;}
                            table td {border-collapse:collapse;}
                            .yshortcuts a {border-bottom: none !important;}
                        </style>
                    </head>
                    <body>
                        <table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td style="padding:30px 0 50px;" valign="top" align="center">
                                    <table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="'.(newsletter_logo($comp_id)).'" height="62" alt=" "></td>
                                        </tr>
                                        <tr>
                                            <td valign="top" align="center">
                                                <table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
                                                    <tr>
                                                        <td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
                                                            <table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
                                                                <tr>
                                                                    <td valign="top">
                                                                        <h2 style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi '.$approver_full_name.',</h2>
                                                                        <p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0; text-trasnform:capitalized">'.$hours_cat.' below has been rejected by '.$last_approver.'.</p>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" style="padding-top:25px;">
                                                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                                '.$message_body.'
                                                                <tr>
                                                                    <td>&nbsp;</td>
                                                                    <td valign="top">
                                                                        '.$link.'
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
                                    <table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; '.date('Y').' Konsum Technologies. All Rights Reserved.</td>
                                            <td valign="top"><img src="'.base_url().'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </body>
                </html>
            ');
            if($this->email->send()){
                return true;
            }else{
                return false;
            }
        }
        return false;
    }

    public function send_hours_reject_notifcation_OLD($leave_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "", $withlink = "No", $level_token = "")
    {
        // $leave_information = $this->agm->leave_information($leave_ids);
        $timein_information = $this->todo_timein->get_employee_time_in($leave_ids);
        $flag = $timein_information->flag_add_logs;
        $hours_cat = "";
        
        if ($timein_information != FALSE) {
            $fullname = ucfirst($timein_information->first_name) . " " . ucfirst($timein_information->last_name);
            if ($flag == 0) {
                $date_applied = date("F d, Y", strtotime($timein_information->change_log_date_filed));
                $time_in = date("F d, Y h:i A", strtotime($timein_information->time_in));
                $lunch_out = date("F d, Y h:i A", strtotime($timein_information->lunch_out));
                $lunch_in = date("F d, Y h:i A", strtotime($timein_information->lunch_in));
                $time_out = date("F d, Y h:i A", strtotime($timein_information->time_out));
                
                $new_time_in = ($timein_information->change_log_time_in != NULL) ? date("F d, Y h:i A", strtotime($timein_information->change_log_time_in)) : "none";
                $new_lunch_out = ($timein_information->change_log_lunch_out != NULL) ? date("F d, Y h:i A", strtotime($timein_information->change_log_lunch_in)) : "none";
                $new_lunch_in = ($timein_information->change_log_lunch_in != NULL) ? date("F d, Y h:i A", strtotime($timein_information->change_log_lunch_out)) : "none";
                $new_time_out = ($timein_information->change_log_time_out != NULL) ? date("F d, Y h:i A", strtotime($timein_information->change_log_time_out)) : "none";
                $hours_cat = "Attendance Adjustment";
            } elseif ($flag == 1) {
                $date_applied = date("F d, Y", strtotime($timein_information->date));
                $time_in = date("F d, Y h:i A", strtotime($timein_information->time_in));
                $lunch_out = date("F d, Y h:i A", strtotime($timein_information->lunch_out));
                $lunch_in = date("F d, Y h:i A", strtotime($timein_information->lunch_in));
                $time_out = date("F d, Y h:i A", strtotime($timein_information->time_out));
                
                $hours_cat = "Add Logs";
            } elseif ($flag == 2) {
                $date_applied = date("F d, Y", strtotime($timein_information->date));
                $time_in = ($timein_information->time_in != NULL) ? date("F d, Y h:i A", strtotime($timein_information->time_in)) : "none";
                $lunch_out = ($timein_information->lunch_in != NULL) ? date("F d, Y h:i A", strtotime($timein_information->lunch_in)) : "none";
                $lunch_in = ($timein_information->lunch_out != NULL) ? date("F d, Y h:i A", strtotime($timein_information->lunch_out)) : "none";
                $time_out = ($timein_information->time_out != NULL) ? date("F d, Y h:i A", strtotime($timein_information->time_out)) : "none";
                
                $time_in_loc = ($timein_information->location_1 != NULL) ? $timein_information->location_1 : "none";
                $lunch_out_loc = ($timein_information->location_2 != NULL) ? $timein_information->location_2 : "none";
                $lunch_in_loc = ($timein_information->location_3 != NULL) ? $timein_information->location_3 : "none";
                $time_out_loc = ($timein_information->location_4 != NULL) ? $timein_information->location_4 : "none";
                
                $hours_cat = "Mobile Clock In";
            }
            $total_hours = $timein_information->total_hours;
            $total_hours_required = $timein_information->total_hours_required;
            $tardiness_min = $timein_information->tardiness_min;
            $undertime_min = $timein_information->undertime_min;
            $reason = $timein_information->reason;
            $font_name = "'Open Sans'";
            if ($flag == 0) {
                $message_body = '
							<tr>
								<td style="width:140px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"> Applicant:</td>
								<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $fullname . '</td>
							</tr>
							<tr>
								<td style="width:140px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
								<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $date_applied . '</td>
							</tr>
							<tr>
								<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">&nbsp;</td>
								<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;"><strong>CHANGE FROM:</strong></td>
							</tr>
							<tr>
								<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
								<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $time_in . '</td>
							</tr>
							<tr>
								<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
								<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $lunch_out . '</td>
							</tr>
							<tr>
								<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
								<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $lunch_in . '</td>
							</tr>
							<tr>
								<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
								<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $time_out . '</td>
							</tr>
							<tr>
								<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">&nbsp;</td>
								<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;"><strong>CHANGE TO:</strong></td>
							</tr>
							<tr>
								<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
								<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $new_time_in . '</td>
							</tr>
							<tr>
								<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
								<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $new_lunch_out . '</td>
							</tr>
							<tr>
								<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
								<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $new_lunch_in . '</td>
							</tr>
							<tr>
								<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
								<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $new_time_out . '</td>
							</tr>
							<tr>
								<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Reason:</td>
								<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $timein_information->reason . '</td>
							</tr>
							<tr>
								<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
								<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $timein_information->notes . '</td>
							</tr>
		 
		
					';
            } else if ($flag == 2) {
                $message_body = '
						<tr>
							<td style="width:140px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"> Applicant:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $fullname . '</td>
						</tr>
						<tr>
							<td style="width:140px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $date_applied . '</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $time_in . '</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In Location:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $time_in_loc . '</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $lunch_out . '</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out Location:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $lunch_out_loc . '</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $lunch_in . '</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In Location:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $lunch_in_loc . '</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $time_out . '</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out Location:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $time_out_loc . '</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $timein_information->notes . '</td>
						</tr>
					';
            } 
            else {
                $message_body = '
						<tr>
							<td style="width:140px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"> Applicant:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $fullname . '</td>
						</tr>
						<tr>
							<td style="width:140px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $date_applied . '</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $time_in . '</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $lunch_out . '</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $lunch_in . '</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $time_out . '</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
							<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $timein_information->notes . '</td>
						</tr>
					';
            }
            // $token = $this->timeins->get_token($leave_ids, $comp_id, $emp_id);
            $link = '';
            
            $font_name = "'Open Sans'";
            
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            
            $this->load->library('email', $config);
            $this->email->initialize($config);
            $this->email->set_newline("\r\n");
            $this->email->from(notifications_ashima_email(), 'Ashima');
            $this->email->to($email);
            $this->email->subject($hours_cat . ' Application - ' . $fullname);
            $font_name = "'Open Sans'";
            $this->email->message('
				<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
				<html lang="en">
					<head>
						<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
						<meta name="format-detection" content="telephone=no">
						<title>' . $hours_cat . '</title>
						<style type="text/css">
							.ReadMsgBody {width: 100%; background-color: #ebebeb;}
							.ExternalClass {width: 100%; background-color: #ebebeb;}
							.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
							body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
							body {margin:0;padding:0;}
							table {border-spacing:0;}
							table td {border-collapse:collapse;}
							.yshortcuts a {border-bottom: none !important;}
						</style>
					</head>
					<body>
						<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
							<tr>
								<td style="padding:30px 0 50px;" valign="top" align="center">
									<table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
										<tr>
								        	<td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="' . (newsletter_logo($comp_id)) . '" height="62" alt=" "></td>
								        </tr>
										<tr>
											<td valign="top" align="center">
												<table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
													<tr>
														<td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
															<table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
																<tr>
																	<td valign="top">
																		<h2 style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi ' . $approver_full_name . ',</h2>
																		<p style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0; text-trasnform:capitalized">' . $hours_cat . ' below has been rejected by ' . $last_approver . '.</p>
																	</td>
																</tr>
															</table>
														</td>
													</tr>
													<tr>
														<td valign="top" style="padding-top:25px;">
															<table width="100%" border="0" cellspacing="0" cellpadding="0">
																' . $message_body . '
																<tr>
																	<td>&nbsp;</td>
																	<td valign="top">
																		' . $link . '
																	</td>
																</tr>
															</table>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
									<table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
										<tr>
											<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; ' . date('Y') . ' Konsum Technologies. All Rights Reserved.</td>
											<td valign="top"><img src="' . base_url() . 'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</body>
				</html>
			');
            if ($this->email->send()) {
                return true;
            } else {
                return false;
            }
        } else {
            show_error("Invalid token");
        }
    }

    public function send_overtime_notifcation($overtime_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $receipient_full_name = "", $last_approver = "", $who = "", $withlink = "No", $level_token = "", $appr_id = "", $notify_admin = "")
    {
        $ot_info = $this->overtime->overtime_information($this->company_id, $overtime_ids);
        
        if ($ot_info != FALSE) {
            $fullname = ucfirst($ot_info->first_name) . " " . ucfirst($ot_info->last_name);
            $date_applied = date("F d, Y", strtotime($ot_info->overtime_date_applied));
            $start_time = date("F d, Y | h:i A", strtotime($ot_info->overtime_from . ' ' . $ot_info->start_time));
            $end_time = date("F d, Y | h:i A", strtotime($ot_info->overtime_to . ' ' . $ot_info->end_time));
            $total_hours = $ot_info->no_of_hours;
            $reason = $ot_info->reason;
            
            $subject = "Action Required. {$fullname}'s Overtime request is awaiting your approval.";
            $token = $this->overtime->get_token($overtime_ids, $comp_id, $emp_id);
            $waiting = '';
            $link = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="' . base_url() . 'approval/overtime/index/' . $token . '/' . $level_token . '/1' . $appr_id . '0">View Overtime Application</a>';
            if ($who == "Approver") {
                $waiting = " and is waiting for Your Approval";
                if ($withlink == "No") {
                    $link = '';
                    $waiting = '';
                    $subject = "You're Next In Line, {$fullname}'s Overtime request has been approved.";
                }
            } else {
                $pieces = explode("/", $receipient_full_name);
                $receipient_full_name = $pieces[0];
                if ($who == "last") {
                    $waiting = "";
                    $subject = "Yay! Your Overtime request was approved.";
					
					if($notify_admin == "Yes") {
					    $subject = "Attention Needed. Overtime request by {$fullname}'s has been approved.";
					}
                } elseif ($who != "not") {
                    if ($pieces[1]) {
                        $current = $pieces[1];
                        $waiting = "and is waiting for {$current}'s approval";
                        $subject = "Your Overtime request is awaiting for approval.";
						
						if($notify_admin == "Yes") {
						    $subject = "{$fullname}'s Overtime request is routed for approval.";
						}
                    }
                }
                $link = '';
            }
            $font_name = "'Open Sans'";
            
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            
            $this->load->library('email', $config);
            $this->email->initialize($config);
            $this->email->set_newline("\r\n");
            $this->email->from(notifications_ashima_email(), 'Ashima');
            $this->email->to($email);
            $this->email->subject($subject);
            $font_name = "'Open Sans'";
            $this->email->message('
			<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
			<html lang="en">
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
					<meta name="format-detection" content="telephone=no">
					<title>Overtime Application</title>
					<style type="text/css">
						.ReadMsgBody {width: 100%; background-color: #ebebeb;}
						.ExternalClass {width: 100%; background-color: #ebebeb;}
						.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
						body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
						body {margin:0;padding:0;}
						table {border-spacing:0;}
						table td {border-collapse:collapse;}
						.yshortcuts a {border-bottom: none !important;}
					</style>
				</head>
				<body>
					<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td style="padding:30px 0 50px;" valign="top" align="center">
								<table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
									<tr>
							        	<td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="' . (newsletter_logo($comp_id)) . '" height="62" alt=" "></td>
							        </tr>
									<tr>
										<td valign="top" align="center">
											<table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
												<tr>
													<td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
														<table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
															<tr>
																<td valign="top">
																	<h2 style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi ' . $receipient_full_name . ',</h2>
																	<p style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Overtime application below has been approved by ' . $last_approver . ' ' . $waiting . '.</p>
																</td>
															</tr>
														</table>
													</td>
												</tr>
												<tr>
													<td valign="top" style="padding-top:25px;">
														<table width="100%" border="0" cellspacing="0" cellpadding="0">
															<tr>
																<td style="width:140px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $date_applied . '</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Purpose of Overtime:</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $reason . '</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Start Date:</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $start_time . '</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">End Date</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $end_time . '</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Status:</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">Approved by ' . $last_approver . '</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Total Overtime Filed:</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $total_hours . ' Hour(s)</td>
															</tr>
															<tr>
																				<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:40px;"></td>
															</tr>
															<tr>
																<td>&nbsp;</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">
																	' . $link . '
																</td>
															</tr>
														</table>
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
								<table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; ' . date('Y') . ' Konsum Technologies. All Rights Reserved.</td>
										<td valign="top"><img src="' . base_url() . 'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</body>
			</html>
			');
            if ($this->email->send()) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function send_overtime_reject_notifcation($overtime_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $receipient_full_name = "", $last_approver = "", $who = "", $withlink = "No", $level_token = "")
    {
        $ot_info = $this->overtime->overtime_information($this->company_id, $overtime_ids);
        
        if ($ot_info != FALSE) {
            $fullname = ucfirst($ot_info->first_name) . " " . ucfirst($ot_info->last_name);
            $date_applied = date("F d, Y", strtotime($ot_info->overtime_date_applied));
            $start_time = date("F d, Y | h:i A", strtotime($ot_info->overtime_from . ' ' . $ot_info->start_time));
            $end_time = date("F d, Y | h:i A", strtotime($ot_info->overtime_to . ' ' . $ot_info->end_time));
            $total_hours = $ot_info->no_of_hours;
            $reason = $ot_info->reason;
            
            $token = $this->overtime->get_token($overtime_ids, $comp_id, $emp_id);
            
            $link = '<a href="' . base_url() . 'approval/leave/index/' . $token . '/' . $level_token . '"><img src="' . base_url() . 'assets/theme_2015/images/images-emailer/btn-view-leave-application.jpg" width="228" height="42" alt=" "></a>';
            if ($who == "Approver") {
                if ($withlink == "No") {
                    $link = '';
                }
            } else {
                $link = '';
            }
            $font_name = "'Open Sans'";
            
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            
            $this->load->library('email', $config);
            $this->email->initialize($config);
            $this->email->set_newline("\r\n");
            $this->email->from(notifications_ashima_email(), 'Ashima');
            $this->email->to($email);
            $this->email->subject('Overtime Application - ' . $fullname);
            $font_name = "'Open Sans'";
            $this->email->message('
									<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
							<html lang="en">
							<head>
						<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
						<meta name="format-detection" content="telephone=no">
						<title>Overtime Application</title>
						<style type="text/css">
							.ReadMsgBody {width: 100%; background-color: #ebebeb;}
							.ExternalClass {width: 100%; background-color: #ebebeb;}
							.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
							body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
							body {margin:0;padding:0;}
							table {border-spacing:0;}
							table td {border-collapse:collapse;}
							.yshortcuts a {border-bottom: none !important;}
						</style>
					</head>
					<body>
						<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
							<tr>
								<td style="padding:30px 0 50px;" valign="top" align="center">
									<table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
										<tr>
								        	<td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="' . (newsletter_logo($comp_id)) . '" height="62" alt=" "></td>
								        </tr>
									<tr>
											<td valign="top" align="center">
												<table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
													<tr>
														<td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
															<table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
																<tr>
																	<td valign="top">
																		<h2 style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi ' . $receipient_full_name . ',</h2>
																		<p style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Overtime application below has been rejected.</p>
																	</td>
																</tr>
															</table>
														</td>
													</tr>
													<tr>
														<td valign="top" style="padding-top:25px;">
															<table width="100%" border="0" cellspacing="0" cellpadding="0">
																<tr>
																	<td style="width:140px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
																	<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $date_applied . '</td>
																</tr>
																					<tr>
																					<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Purpose of Overtime:</td>
																	<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $reason . '</td>
																</tr>
																<tr>
																					<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Start Date:</td>
																	<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $start_time . '</td>
																</tr>
																					<tr>
																					<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">End Date</td>
																	<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $end_time . '</td>
																</tr>
																					<tr>
																	<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Status:</td>
																	<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">Rejected by ' . $last_approver . '</td>
																					</tr>
																					<tr>
																	<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Total Overtime Filed:</td>
																					<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $total_hours . ' Hour(s)</td>
																							</tr>
																<tr>
																	<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
																					<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:40px;"></td>
																</tr>
																							<tr>
																	<td>&nbsp;</td>
																	<td valign="top">
																		' . $link . '
																	</td>
																</tr>
															</table>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
									<table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
										<tr>
											<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; ' . date('Y') . ' Konsum Technologies. All Rights Reserved.</td>
											<td valign="top"><img src="' . base_url() . 'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
															</tr>
									</table>
								</td>
							</tr>
						</table>
					</body>
				</html>
				');
            if ($this->email->send()) {
                return true;
            } else {
                return false;
            }
        } else {
            show_error("Invalid token");
        }
    }

    public function send_shifts_notifcation($employee_work_schedule_application_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $receipient_full_name = "", $last_approver = "", $who = "", $withlink = "No", $level_token = "", $appr_id = "")
    {
        $shifts_info = $this->shifts->shifts_information($this->company_id, $employee_work_schedule_application_ids);
        
        if ($shifts_info != FALSE) {
            $fullname = ucfirst($shifts_info->first_name) . " " . ucfirst($shifts_info->last_name);
            $date_applied = date("F d, Y", strtotime($shifts_info->date_filed));
            $change_date_from = date("F d, Y", strtotime($shifts_info->date_from));
            $change_date_to = date("F d, Y", strtotime($shifts_info->date_to));
            $schedule_date_from = date("h:i A", strtotime($shifts_info->start_time));
            $schedule_date_to = date("h:i A", strtotime($shifts_info->end_time));
            $reason = $shifts_info->reason;
            
            $temp1 = get_schedule($change_date_from, $shifts_info->emp_id, $shifts_info->company_id);
            
            // $current_schedule = "No Schedule";
            // $flag_date = $shifts_info->date_filed;
            // $check_employee_work_schedule = $this->ews->check_employee_work_schedule($flag_date,$shifts_info->emp_id,$shifts_info->company_id);
            // if($check_employee_work_schedule != FALSE){
            // $work_schedule_id = $check_employee_work_schedule->work_schedule_id;
            
            // $weekday = date('l',strtotime($flag_date));
            // $rest_day = FALSE;
            
            // // check rest day
            // if($work_schedule_id!=FALSE){
            // /* EMPLOYEE WORK SCHEDULE */
            // $rest_day = $this->ews->get_rest_day($shifts_info->company_id,$work_schedule_id,$weekday);
            // }
            
            // if($rest_day){
            // $current_schedule = "RD";
            // }else{
            // $emp_work_schedule_info = $this->ews->work_schedule_info($shifts_info->company_id,$work_schedule_id,$weekday);
            // if($work_schedule_id && $emp_work_schedule_info){
            // $st = ($emp_work_schedule_info["work_schedule"]["start_time"] == "") ? "" : date("h:i a",strtotime($emp_work_schedule_info["work_schedule"]["start_time"]));
            // $et = ($emp_work_schedule_info["work_schedule"]["end_time"] == "") ? "" : date("h:i a",strtotime($emp_work_schedule_info["work_schedule"]["end_time"]));
            // $shift_name = $emp_work_schedule_info["work_schedule"]["shift_name"];
            // if($st != "" && $et != ""){
            // $str = "{$st} - {$et}";
            // }elseif($st != "" && $et == ""){
            // $str = "{$st}";
            // }else{
            // $str = "Flexible Hours";
            // }
            
            // $current_schedule = "{$str}";
            // }
            // }
            // }
            
            $token = $this->shifts->get_token($employee_work_schedule_application_ids, $comp_id, $emp_id);
            $waiting = '';
            $link = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="' . base_url() . 'approval/work_schedule/index/' . $token . '/' . $level_token . '/1' . $appr_id . '0">View Change Schedule Application</a>';
            if ($who == "Approver") {
                $waiting = " and is waiting for Your Approval";
                if ($withlink == "No") {
                    $link = '';
                    $waiting = '';
                }
            } else {
                $pieces = explode("/", $receipient_full_name);
                $receipient_full_name = $pieces[0];
                if ($who == "last") {
                    $waiting = "";
                } elseif ($who != "not") {
                    if ($pieces[1]) {
                        $current = $pieces[1];
                        $waiting = "and is waiting for {$current}'s approval";
                    }
                }
                $link = '';
            }
            $font_name = "'Open Sans'";
            
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            
            $this->load->library('email', $config);
            $this->email->initialize($config);
            $this->email->set_newline("\r\n");
            $this->email->from(notifications_ashima_email(), 'Ashima');
            $this->email->to($email);
            $this->email->subject('Change Schedule Request - ' . $fullname);
            $font_name = "'Open Sans'";
            $this->email->message('
				<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
				<html lang="en">
					<head>
						<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
						<meta name="format-detection" content="telephone=no">
						<title>Change Schedule Request</title>
						<style type="text/css">
							.ReadMsgBody {width: 100%; background-color: #ebebeb;}
							.ExternalClass {width: 100%; background-color: #ebebeb;}
							.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
							body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
							body {margin:0;padding:0;}
							table {border-spacing:0;}
							table td {border-collapse:collapse;}
							.yshortcuts a {border-bottom: none !important;}
						</style>
					</head>
					<body>
						<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
							<tr>
								<td style="padding:30px 0 50px;" valign="top" align="center">
									<table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
										<tr>
								        	<td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="' . (newsletter_logo($comp_id)) . '" height="62" alt=" "></td>
								        </tr>
										<tr>
											<td valign="top" align="center">
												<table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
													<tr>
														<td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
															<table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
																<tr>
																	<td valign="top">
																		<h2 style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi ' . $receipient_full_name . ',</h2>
																		<p style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Schedule Request application below has been approved by ' . $last_approver . ' ' . $waiting . '.</p>
																	</td>
																</tr>
															</table>
														</td>
													</tr>
													<tr>
														<td valign="top" style="padding-top:25px;">
															<table width="100%" border="0" cellspacing="0" cellpadding="0">
																<tr>
																	<td style="width:140px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
																	<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $date_applied . '</td>
																</tr>
																<tr>
																	<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Current Schedule:</td>
																	<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . date('h:i A', strtotime($temp1['start_time'])) . ' - ' . date('h:i A', strtotime($temp1['end_time'])) . '</td>
																</tr>
																<tr>
																	<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Change Date From:</td>
																	<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $change_date_from . '</td>
																</tr>
																<tr>
																	<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Change Date To: </td>
																	<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $change_date_to . '</td>
																</tr>
																<tr>
																	<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Schedule From:</td>
																	<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $schedule_date_from . '</td>
																</tr>
																<tr>
																	<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Schedule To: </td>
																	<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $schedule_date_to . '</td>
																</tr>
																<tr>
																	<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Reason:</td>
																	<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $reason . '</td>
																</tr>
																<tr>
																	<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Status:</td>
																	<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">Approved by ' . $last_approver . '</td>
																</tr>
																<tr>
																<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
																	<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:40px;"></td>
																</tr>
																<tr>
																	<td>&nbsp;</td>
																	<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">
																		' . $link . '
																	</td>
																</tr>
															</table>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
									<table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
										<tr>
											<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; ' . date('Y') . ' Konsum Technologies. All Rights Reserved.</td>
											<td valign="top"><img src="' . base_url() . 'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</body>
				</html>
			');
            if ($this->email->send()) {
                return true;
            } else {
                return false;
            }
        } else {
            show_error("Invalid token");
        }
    }

    public function send_shifts_reject_notifcation($employee_work_schedule_application_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $receipient_full_name = "", $last_approver = "", $who = "", $withlink = "No", $level_token = "")
    {
        $shifts_info = $this->shifts->shifts_information($this->company_id, $employee_work_schedule_application_ids);
        
        if ($shifts_info != FALSE) {
            $fullname = ucfirst($shifts_info->first_name) . " " . ucfirst($shifts_info->last_name);
            $date_applied = date("F d, Y", strtotime($shifts_info->date_filed));
            $change_date_from = date("Y-m-d", strtotime($shifts_info->date_from));
            $change_date_to = date("Y-m-d", strtotime($shifts_info->date_to));
            $schedule_date_from = date("h:i A", strtotime($shifts_info->start_time));
            $schedule_date_to = date("h:i A", strtotime($shifts_info->end_time));
            $reason = $shifts_info->reason;
            
            $temp1 = get_schedule($change_date_from, $shifts_info->emp_id, $shifts_info->company_id);
            
            // $current_schedule = "No Schedule";
            // $flag_date = $shifts_info->date_filed;
            // $check_employee_work_schedule = $this->ews->check_employee_work_schedule($flag_date,$shifts_info->emp_id,$shifts_info->company_id);
            // if($check_employee_work_schedule != FALSE){
            // $work_schedule_id = $check_employee_work_schedule->work_schedule_id;
            
            // $weekday = date('l',strtotime($flag_date));
            // $rest_day = FALSE;
            
            // // check rest day
            // if($work_schedule_id!=FALSE){
            // /* EMPLOYEE WORK SCHEDULE */
            // $rest_day = $this->ews->get_rest_day($shifts_info->company_id,$work_schedule_id,$weekday);
            // }
            
            // if($rest_day){
            // $current_schedule = "RD";
            // }else{
            // $emp_work_schedule_info = $this->ews->work_schedule_info($shifts_info->company_id,$work_schedule_id,$weekday);
            // if($work_schedule_id && $emp_work_schedule_info){
            // $st = ($emp_work_schedule_info["work_schedule"]["start_time"] == "") ? "" : date("h:i a",strtotime($emp_work_schedule_info["work_schedule"]["start_time"]));
            // $et = ($emp_work_schedule_info["work_schedule"]["end_time"] == "") ? "" : date("h:i a",strtotime($emp_work_schedule_info["work_schedule"]["end_time"]));
            // $shift_name = $emp_work_schedule_info["work_schedule"]["shift_name"];
            // if($st != "" && $et != ""){
            // $str = "{$st} - {$et}";
            // }elseif($st != "" && $et == ""){
            // $str = "{$st}";
            // }else{
            // $str = "Flexible Hours";
            // }
            
            // $current_schedule = "{$str}";
            // }
            // }
            // }
            
            $token = $this->shifts->get_token($employee_work_schedule_application_ids, $comp_id, $emp_id);
            
            $link = '<a href="' . base_url() . 'approval/work_schedule/index/' . $token . '/' . $level_token . '"><img src="' . base_url() . 'assets/theme_2015/images/images-emailer/btn-view-schedule-request.png" width="274" height="42" alt=" "></a>';
            if ($who == "Approver") {
                if ($withlink == "No") {
                    $link = '';
                }
            } else {
                $link = '';
            }
            $font_name = "'Open Sans'";
            
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            
            $this->load->library('email', $config);
            $this->email->initialize($config);
            $this->email->set_newline("\r\n");
            $this->email->from(notifications_ashima_email(), 'Ashima');
            $this->email->to($email);
            $this->email->subject('Change Schedule Request - ' . $fullname);
            $font_name = "'Open Sans'";
            $this->email->message('
				<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
						<html lang="en">
						<head>
					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
					<meta name="format-detection" content="telephone=no">
					<title>Change Schedule Request</title>
					<style type="text/css">
						.ReadMsgBody {width: 100%; background-color: #ebebeb;}
						.ExternalClass {width: 100%; background-color: #ebebeb;}
						.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
						body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
						body {margin:0;padding:0;}
						table {border-spacing:0;}
						table td {border-collapse:collapse;}
						.yshortcuts a {border-bottom: none !important;}
					</style>
				</head>
				<body>
					<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td style="padding:30px 0 50px;" valign="top" align="center">
								<table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
									<tr>
							        	<td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="' . (newsletter_logo($comp_id)) . '" height="62" alt=" "></td>
							        </tr>
								<tr>
										<td valign="top" align="center">
											<table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
												<tr>
													<td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
														<table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
															<tr>
																<td valign="top">
																	<h2 style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi ' . $receipient_full_name . ',</h2>
																	<p style="font-size:16px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Overtime application below has been rejected.</p>
																</td>
															</tr>
														</table>
													</td>
												</tr>
												<tr>
													<td valign="top" style="padding-top:25px;">
														<table width="100%" border="0" cellspacing="0" cellpadding="0">
															<tr>
																<td style="width:140px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $date_applied . '</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Current Schedule:</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . date('h:i A', strtotime($temp1['start_time'])) . ' - ' . date('h:i A', strtotime($temp1['end_time'])) . '</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Change Date Form:</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $change_date_from . '</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Change Date To:</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $change_date_to . '</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Schedule Date From:</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $schedule_date_from . '</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Schedule Date To:</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $schedule_date_to . '</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Reason:</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">' . $reason . '</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Status:</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">Rejected by ' . $last_approver . '</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
																<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:40px;"></td>
															</tr>
															<tr>
																<td>&nbsp;</td>
																<td valign="top">
																	' . $link . '
																</td>
															</tr>
														</table>
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
								<table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td valign="top" style="font-size:12px; font-family:' . $font_name . ', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; ' . date('Y') . ' Konsum Technologies. All Rights Reserved.</td>
										<td valign="top"><img src="' . base_url() . 'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
														</tr>
								</table>
							</td>
						</tr>
					</table>
				</body>
			</html>
			');
            if ($this->email->send()) {
                return true;
            } else {
                return false;
            }
        } else {
            show_error("Invalid token");
        }
    }

    public function send_hours_notifcation_timein($leave_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "" , $withlink = "No", $level_token = "", $appr_id = ""){
        $timein_information = $this->todo_timein->get_employee_time_in($leave_ids);
        $flag = $timein_information->flag_add_logs;
        $hours_cat = "";
        $waiting = '';
        if($timein_information != FALSE){
            $fullname = ucfirst($timein_information->first_name)." ".ucfirst($timein_information->last_name);

            if($flag==0){
                $date_applied = date("F d, Y",strtotime($timein_information->change_log_date_filed));
                $time_in = ($timein_information->time_in == NULL || $timein_information->time_in == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->time_in));
                $lunch_out = ($timein_information->lunch_out == NULL || $timein_information->lunch_out == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->lunch_out));
                $lunch_in = ($timein_information->lunch_in == NULL || $timein_information->lunch_in == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->lunch_in));
                $time_out = ($timein_information->time_out == NULL || $timein_information->time_out == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->time_out));

                $new_time_in = ($timein_information->change_log_time_in!=NULL)? date("F d, Y h:i A",strtotime($timein_information->change_log_time_in)) : "none";
                $new_lunch_out =  ($timein_information->change_log_lunch_out!=NULL)? date("F d, Y h:i A",strtotime($timein_information->change_log_lunch_out)) : "none";
                $new_lunch_in =  ($timein_information->change_log_lunch_in!=NULL)? date("F d, Y h:i A",strtotime($timein_information->change_log_lunch_in)) : "none";
                $new_time_out =  ($timein_information->change_log_time_out!=NULL)? date("F d, Y h:i A",strtotime($timein_information->change_log_time_out)): "none";
                $hours_cat = "Adjustment";
            }elseif($flag==1){
                $date_applied = date("F d, Y",strtotime($timein_information->date));
                $time_in = ($timein_information->time_in == NULL || $timein_information->time_in == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->time_in));
                $lunch_out = ($timein_information->lunch_out == NULL || $timein_information->lunch_out == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->lunch_out));
                $lunch_in = ($timein_information->lunch_in == NULL || $timein_information->lunch_in == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->lunch_in));
                $time_out = ($timein_information->time_out == NULL || $timein_information->time_out == '1970-01-01 08:00:00') ? "none" : date("F d, Y h:i A",strtotime($timein_information->time_out));


                $hours_cat = "Logs";
            }elseif($flag==2){
                $date_applied = date("F d, Y",strtotime($timein_information->date));
                $time_in = ($timein_information->time_in!=NULL)? date("F d, Y h:i A",strtotime($timein_information->time_in)) : "none";
                $lunch_out =  ($timein_information->lunch_in!=NULL)? date("F d, Y h:i A",strtotime($timein_information->lunch_in)) : "none";
                $lunch_in =  ($timein_information->lunch_out!=NULL)? date("F d, Y h:i A",strtotime($timein_information->lunch_out)) : "none";
                $time_out =  ($timein_information->time_out!=NULL)? date("F d, Y h:i A",strtotime($timein_information->time_out)): "none";
                $hours_cat = "Log";
            }

            $total_hours = $timein_information->total_hours;
            $total_hours_required = $timein_information->total_hours_required;
            $tardiness_min = $timein_information->tardiness_min;
            $undertime_min = $timein_information->undertime_min;
            $reason = $timein_information->reason;

            //$token = $this->timeins->get_token($leave_ids, $comp_id, $emp_id);
            $token = $this->todo_timein->get_token($leave_ids, $comp_id, $emp_id, $timein_information->approval_time_in_id);
            #$link = '<a href="'.base_url().'approval/time_in/index/'.$token.'/'.$level_token.'/1'.$appr_id.'0"><img src="'.base_url().'assets/theme_2015/images/images-emailer/btn-view-adjustment-attendance.jpg" width="206" height="42" alt=" "></a>';
            $link = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="'.base_url().'approval/time_in/index/'.$token.'/'.$level_token.'/1'.$appr_id.'0">View Attendance Adjustment Application</a>';

            if($who == "Approver"){
                $waiting = " and is waiting for Your Approval";
                if($withlink == "No"){
                    $link = '';
                    $waiting = '';
                }
            }
            else{
                $pieces = explode("/", $approver_full_name);
                $approver_full_name = $pieces[0];

                if($who =="last"){
                    $waiting = "";
                }elseif($who != "not"){
                    if($pieces[1]){
                        $current = $pieces[1];
                        $waiting = "and is waiting for {$current}'s approval";
                    }

                }
                $link = '';
            }
            $font_name = "'Open Sans'";
            $message_body = "";
            if($flag == 0){

                $message_body = '
                            <tr>
                                <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"> Applicant:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$fullname.'</td>
                            </tr>
                            <tr>
                                <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date_applied.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">&nbsp;</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;"><strong>CHANGE FROM:</strong></td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_in.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_out.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_in.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_out.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">&nbsp;</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;"><strong>CHANGE TO:</strong></td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_time_in.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_lunch_out.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_lunch_in.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_time_out.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Reason:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$timein_information->reason.'</td>
                            </tr>
                            <tr>
                                <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$timein_information->notes.'</td>
                            </tr>
                    ';
            }else{
                $message_body = '
                        <tr>
                            <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"> Applicant:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$fullname.'</td>
                        </tr>
                        <tr>
                            <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date_applied.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_in.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_out.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_in.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_out.'</td>
                        </tr>
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$timein_information->notes.'</td>
                        </tr>
                    ';

            }
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';

            $this->load->library('email',$config);
            $this->email->initialize($config);
            $this->email->set_newline("\r\n");
            $this->email->from(notifications_ashima_email(),'Ashima');
            $this->email->to($email);
            $this->email->subject('Attendance '.$hours_cat.' - '.$fullname);
            $font_name = "'Open Sans'";
            $this->email->message('
            <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
            <html lang="en">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                    <meta name="format-detection" content="telephone=no">
                    <title>Attendance '.$hours_cat.'</title>
                    <style type="text/css">
                        .ReadMsgBody {width: 100%; background-color: #ebebeb;}
                        .ExternalClass {width: 100%; background-color: #ebebeb;}
                        .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
                        body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
                        body {margin:0;padding:0;}
                        table {border-spacing:0;}
                        table td {border-collapse:collapse;}
                        .yshortcuts a {border-bottom: none !important;}
                    </style>
                </head>
                <body>
                    <table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="padding:30px 0 50px;" valign="top" align="center">
                                <table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="border-bottom:6px solid #ccc;" valign="top"><h1 style="font-size:20px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 15px;">Attendance '.$hours_cat.'</h1></td>
                                    </tr>
                                    <tr>
                                        <td valign="top" align="center">
                                            <table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
                                                        <table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
                                                            <tr>
                                                                <td valign="top">
                                                                    <h2 style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi '.$approver_full_name.',</h2>
                                                                    <p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0; text-trasnform:capitalized">Attendance '.$hours_cat.' below has been approved by '.$last_approver.''.$waiting.'.</p>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td valign="top" style="padding-top:25px;">
                                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                            '.$message_body.'
                                                            <tr>
                                                                <td>&nbsp;</td>
                                                                <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">
                                                                    '.$link.'
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
                                <table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; '.date('Y').' Konsum Technologies. All Rights Reserved.</td>
                                        <td valign="top"><img src="'.base_url().'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </body>
            </html>
            ');
            if($this->email->send()){
                return true;
            }else{
                return false;
            }
        }
        else{
            show_error("Invalid token");
        }
    }

    public function send_hours_notifcation_mobile($leave_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "" , $withlink = "No", $level_token = "", $appr_id="",$mobile_status=""){
        $timein_information = $this->todo_m->get_employee_time_in($leave_ids);
        
        $flag = $timein_information->flag_add_logs;
        $hours_cat = "";
        $waiting = '';
        
        if($timein_information != FALSE){
            $fullname = ucfirst($timein_information->first_name)." ".ucfirst($timein_information->last_name);
                            
            if($timein_information->location == "location_1"){
                $location = $timein_information->location_1;
                $date = date("F d, Y",strtotime($timein_information->time_in));
                $time = date("H:i:s",strtotime($timein_information->time_in));
                $time_type = 'Time In';
            }elseif($timein_information->location == "location_2"){
                $location = $timein_information->location_2;
                $date = date("F d, Y",strtotime($timein_information->lunch_out));
                $time = date("H:i:s",strtotime($timein_information->lunch_out));
                $time_type = 'Lunch Out';
            }elseif($timein_information->location == "location_3"){
                $location = $timein_information->location_3;
                $date = date("F d, Y",strtotime($timein_information->lunch_in));
                $time = date("H:i:s",strtotime($timein_information->lunch_in));
                $time_type = 'Lunch In';
            }elseif($timein_information->location == "location_4"){
                $location = $timein_information->location_4;
                $date = date("F d, Y",strtotime($timein_information->time_out));
                $time = date("H:i:s",strtotime($timein_information->time_out));
                $time_type = 'Time Out';
            }elseif($timein_information->location == "location_5"){
                $location = $timein_information->location_5;
                $date = date("F d, Y",strtotime($timein_information->break1_out));
                $time = date("H:i:s",strtotime($timein_information->break1_out));
                $time_type = '1st Break Out';
            }elseif($timein_information->location == "location_6"){
                $location = $timein_information->location_6;
                $date = date("F d, Y",strtotime($timein_information->break1_in));
                $time = date("H:i:s",strtotime($timein_information->break1_in));
                $time_type = '1st Break In';
            }elseif($timein_information->location == "location_7"){
                $location = $timein_information->location_7;
                $date = date("F d, Y",strtotime($timein_information->break2_out));
                $time = date("H:i:s",strtotime($timein_information->break2_out));
                $time_type = '2nd Break Out';
            }elseif($timein_information->location == "location_8"){
                $location = $timein_information->location_8;
                $date = date("F d, Y",strtotime($timein_information->break2_in));
                $time = date("H:i:s",strtotime($timein_information->break2_in));
                $time_type = '2nd Break In';
            }

            $hours_cat = "Mobile Clock In";
            
            $total_hours = $timein_information->total_hours;
            $total_hours_required = $timein_information->total_hours_required;
            $tardiness_min = $timein_information->tardiness_min;
            $undertime_min = $timein_information->undertime_min;
            $reason = $timein_information->reason;
                

            //$token = $this->timeins->get_token($leave_ids, $comp_id, $emp_id);
            $token = $this->todo_m->get_token($leave_ids, $comp_id, $emp_id, $timein_information->approval_time_in_id);
            
            $link = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="'.base_url().'approval/employee_time_in/index/'.$token.'/'.$level_token.'/1'.$appr_id.'0/">View Mobile Login</a>';

            if($who == "Approver"){
                $waiting = " and is waiting for Your Approval";
                if($withlink == "No"){
                    $link = '';
                    $waiting = '';
                }
            }
            else{
                $pieces = explode("/", $approver_full_name);
                $approver_full_name = $pieces[0];
                
                if($who =="last"){
                    $waiting = "";
                }elseif($who != "not"){
                    if($pieces[1]){
                        $current = $pieces[1];
                        $waiting = "and is waiting for {$current}'s approval";
                    }
                    
                }
                $link = '';
            }
            $font_name = "'Open Sans'";
            
            $location_1 = ($timein_information->location_1) ? $timein_information->location_1 : "";
            $location_2 = ($timein_information->location_2) ? $timein_information->location_2 : "";
            $location_3 = ($timein_information->location_3) ? $timein_information->location_3 : "";
            $location_4 = ($timein_information->location_4) ? $timein_information->location_4 : "";
            $location_5 = ($timein_information->location_5) ? $timein_information->location_5 : "";
            $location_6 = ($timein_information->location_6) ? $timein_information->location_6 : "";
            $location_7 = ($timein_information->location_7) ? $timein_information->location_7 : "";
            $location_8 = ($timein_information->location_8) ? $timein_information->location_8 : "";
                
            if($mobile_status == 'mobile_clockin_status') {
                $clock_in_label = 'Clock In';
                $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->time_in));
                $clock_location = $location_1;
            } elseif ($mobile_status == 'mobile_lunchout_status') {
                $clock_in_label = 'Lunch Out';
                $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->lunch_out));
                $clock_location = $location_2;
            } elseif ($mobile_status == 'mobile_lunchin_status') {
                $clock_in_label = 'Lunch In ';
                $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->lunch_in));
                $clock_location = $location_3;
            } elseif ($mobile_status == 'mobile_clockout_status') {
                $clock_in_label = 'Clock Out';
                $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->time_out));
                $clock_location = $location_4;
            } elseif ($mobile_status == 'mobile_clockout_status') {
                $clock_in_label = 'Clock Out';
                $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->time_out));
                $clock_location = $location_4;
            } elseif ($mobile_status == 'mobile_break1_out_status') {
                $clock_in_label = '1st Break Out';
                $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->break1_out));
                $clock_location = $location_5;
            } elseif ($mobile_status == 'mobile_break1_in_status') {
                $clock_in_label = '1st Break In';
                $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->break1_in));
                $clock_location = $location_6;
            } elseif ($mobile_status == 'mobile_break2_out_status') {
                $clock_in_label = '2nd Break Out';
                $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->break2_out));
                $clock_location = $location_7;
            } elseif ($mobile_status == 'mobile_break2_in_status') {
                $clock_in_label = '2nd Break In';
                $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->break2_in));
                $clock_location = $location_8;
            }

             else {
                $clock_in_label = '';
                $clock_in_date = '';
                $clock_location = '';
            }
            
            $message_body = "";
            
            $message_body = '
                <tr>
                    <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"> Applicant:</td>
                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$fullname.'</td>
                </tr>
                <tr>
                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date: </td>
                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date.'</td>
                </tr>
                <tr>
                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">'.$clock_in_label.':</td>
                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$clock_in_date.'</td>
                </tr>
                <tr>
                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Location:</td>
                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$clock_location.'</td>
                </tr>
                ';
            
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
     
            $this->load->library('email',$config);
            $this->email->initialize($config);
            $this->email->set_newline("\r\n");
            $this->email->from(notifications_ashima_email(),'Ashima');
            $this->email->to($email);
            $this->email->subject( $hours_cat. ' Application - '.$fullname);
            $font_name = "'Open Sans'";
            $this->email->message('
        <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
        <html lang="en">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                <meta name="format-detection" content="telephone=no">
                <title>'.$hours_cat.'</title>
                <style type="text/css">
                    .ReadMsgBody {width: 100%; background-color: #ebebeb;}
                    .ExternalClass {width: 100%; background-color: #ebebeb;}
                    .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
                    body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
                    body {margin:0;padding:0;}
                    table {border-spacing:0;}
                    table td {border-collapse:collapse;}
                    .yshortcuts a {border-bottom: none !important;}
                </style>
            </head>
            <body>
                <table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="padding:30px 0 50px;" valign="top" align="center">
                            <table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="'.(newsletter_logo($comp_id)).'" height="62" alt=" "></td>
                                </tr>
                                <tr>
                                    <td valign="top" align="center">
                                        <table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
                                                    <table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td valign="top">
                                                                <h2 style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi '.$approver_full_name.',</h2>
                                                                <p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0; text-trasnform:capitalized">'.$hours_cat.' below has been approved by '.$last_approver.' '.$waiting.'.</p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td valign="top" style="padding-top:25px;">
                                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                        '.$message_body.'
                                                        <tr>
                                                            <td>&nbsp;</td>
                                                            <td valign="top">
                                                                '.$link.'
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
                            <table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; '.date('Y').' Konsum Technologies. All Rights Reserved.</td>
                                    <td valign="top"><img src="'.base_url().'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
        </html>
        ');
            if($this->email->send()){
                return true;
            }else{
                return false;
            }
        }
        else{
            show_error("Invalid token");
        }
    }

    public function send_hours_reject_notifcation_mobile($leave_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "" , $withlink = "No", $level_token = "", $mobile_status = "")
    {
     
        #$timein_information = $this->approval->get_employee_time_in($leave_ids, $this->uri->segment(4));
        $timein_information = $this->todo_m->get_employee_time_in($leave_ids);
        $flag = $timein_information->flag_add_logs;
        $hours_cat = "";
         
        if($timein_information != FALSE){
            $fullname = ucfirst($timein_information->first_name)." ".ucfirst($timein_information->last_name);
            
            if($timein_information->location == "location_1"){
                $location = $timein_information->location_1;
                $date = date("F d, Y",strtotime($timein_information->time_in));
                $time = date("H:i:s",strtotime($timein_information->time_in));
                $time_type = 'Time In';
            }elseif($timein_information->location == "location_2"){
                $location = $timein_information->location_2;
                $date = date("F d, Y",strtotime($timein_information->lunch_out));
                $time = date("H:i:s",strtotime($timein_information->lunch_out));
                $time_type = 'Lunch Out';
            }elseif($timein_information->location == "location_3"){
                $location = $timein_information->location_3;
                $date = date("F d, Y",strtotime($timein_information->lunch_in));
                $time = date("H:i:s",strtotime($timein_information->lunch_in));
                $time_type = 'Lunch In';
            }elseif($timein_information->location == "location_4"){
                $location = $timein_information->location_4;
                $date = date("F d, Y",strtotime($timein_information->time_out));
                $time = date("H:i:s",strtotime($timein_information->time_out));
                $time_type = 'Time Out';
            }elseif($timein_information->location == "location_5"){
                $location = $timein_information->location_5;
                $date = date("F d, Y",strtotime($timein_information->break1_out));
                $time = date("H:i:s",strtotime($timein_information->break1_out));
                $time_type = '1st Break Out';
            }elseif($timein_information->location == "location_6"){
                $location = $timein_information->location_6;
                $date = date("F d, Y",strtotime($timein_information->break1_in));
                $time = date("H:i:s",strtotime($timein_information->break1_in));
                $time_type = '1st Break In';
            }elseif($timein_information->location == "location_7"){
                $location = $timein_information->location_7;
                $date = date("F d, Y",strtotime($timein_information->break2_out));
                $time = date("H:i:s",strtotime($timein_information->break2_out));
                $time_type = '2nd Break Out';
            }elseif($timein_information->location == "location_8"){
                $location = $timein_information->location_8;
                $date = date("F d, Y",strtotime($timein_information->break2_in));
                $time = date("H:i:s",strtotime($timein_information->break2_in));
                $time_type = '2nd Break In';
            }
            
            $hours_cat = "Mobile Clock In";
            
            $total_hours = $timein_information->total_hours;
            $total_hours_required = $timein_information->total_hours_required;
            $tardiness_min = $timein_information->tardiness_min;
            $undertime_min = $timein_information->undertime_min;
            $reason = $timein_information->reason;
            
            //$token = $this->timeins->get_token($leave_ids, $comp_id, $emp_id);
            $token = $this->todo_m->get_token($leave_ids, $comp_id, $emp_id, $timein_information->approval_time_in_id);
            
            $link = '';
            
            $font_name = "'Open Sans'";

            $location_1 = ($timein_information->location_1) ? $timein_information->location_1 : "";
            $location_2 = ($timein_information->location_2) ? $timein_information->location_2 : "";
            $location_3 = ($timein_information->location_3) ? $timein_information->location_3 : "";
            $location_4 = ($timein_information->location_4) ? $timein_information->location_4 : "";
            $location_5 = ($timein_information->location_5) ? $timein_information->location_5 : "";
            $location_6 = ($timein_information->location_6) ? $timein_information->location_6 : "";
            $location_7 = ($timein_information->location_7) ? $timein_information->location_7 : "";
            $location_8 = ($timein_information->location_8) ? $timein_information->location_8 : "";
                  
            if($mobile_status == 'mobile_clockin_status') {
                  $clock_in_label = 'Clock In';
                  $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->time_in));
                  $clock_location = $location_1;
            } elseif ($mobile_status == 'mobile_lunchout_status') {
                  $clock_in_label = 'Lunch Out';
                  $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->lunch_out));
                  $clock_location = $location_2;
            } elseif ($mobile_status == 'mobile_lunchin_status') {
                  $clock_in_label = 'Lunch In   ';
                  $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->lunch_in));
                  $clock_location = $location_3;
            } elseif ($mobile_status == 'mobile_clockout_status') {
                  $clock_in_label = 'Clock Out';
                  $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->time_out));
                  $clock_location = $location_4;
            } elseif ($mobile_status == 'mobile_break1_out_status') {
                $clock_in_label = '1st Break Out';
                $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->break1_out));
                $clock_location = $location_5;
            } elseif ($mobile_status == 'mobile_break1_in_status') {
                $clock_in_label = '1st Break In';
                $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->break1_in));
                $clock_location = $location_6;
            } elseif ($mobile_status == 'mobile_break2_out_status') {
                $clock_in_label = '2nd Break Out';
                $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->break2_out));
                $clock_location = $location_7;
            } elseif ($mobile_status == 'mobile_break2_in_status') {
                $clock_in_label = '2nd Break In';
                $clock_in_date = date('F d, Y h:i A', strtotime($timein_information->break2_in));
                $clock_location = $location_8;
            }
             else {
                  $clock_in_label = '';
                  $clock_in_date = '';
                  $clock_location = '';
            }
            
            $message_body = "";
            
            $message_body = '
                  <tr>
                        <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"> Applicant:</td>
                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$fullname.'</td>
                  </tr>
                  <tr>
                        <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date: </td>
                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date.'</td>
                  </tr>
                  <tr>
                        <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">'.$clock_in_label.':</td>
                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$clock_in_date.'</td>
                  </tr>
                  <tr>
                        <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Location:</td>
                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$clock_location.'</td>
                  </tr>
                  ';
     
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
                
            $this->load->library('email',$config);
            $this->email->initialize($config);
            $this->email->set_newline("\r\n");
            $this->email->from(notifications_ashima_email(),'Ashima');
            $this->email->to($email);
            $this->email->subject( $hours_cat. ' Application - '.$fullname);
            $font_name = "'Open Sans'";
            $this->email->message('
            <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
            <html lang="en">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                    <meta name="format-detection" content="telephone=no">
                    <title>'.$hours_cat.'</title>
                    <style type="text/css">
                        .ReadMsgBody {width: 100%; background-color: #ebebeb;}
                        .ExternalClass {width: 100%; background-color: #ebebeb;}
                        .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
                        body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
                        body {margin:0;padding:0;}
                        table {border-spacing:0;}
                        table td {border-collapse:collapse;}
                        .yshortcuts a {border-bottom: none !important;}
                    </style>
                </head>
                <body>
                    <table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="padding:30px 0 50px;" valign="top" align="center">
                                <table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="'.(newsletter_logo($comp_id)).'" height="62" alt=" "></td>
                                    </tr>
                                    <tr>
                                        <td valign="top" align="center">
                                            <table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
                                                        <table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
                                                            <tr>
                                                                <td valign="top">
                                                                    <h2 style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi '.$approver_full_name.',</h2>
                                                                    <p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0; text-trasnform:capitalized">'.$hours_cat.' below has been rejected by '.$last_approver.'.</p>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td valign="top" style="padding-top:25px;">
                                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                            '.$message_body.'
                                                            <tr>
                                                                <td>&nbsp;</td>
                                                                <td valign="top">
                                                                    '.$link.'
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
                                <table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; '.date('Y').' Konsum Technologies. All Rights Reserved.</td>
                                        <td valign="top"><img src="'.base_url().'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </body>
            </html>
        ');
            if($this->email->send()){
                return true;
            }else{
                return false;
            }
        }
        else{
            show_error("Invalid token");
        }
    }

    public function todo_restday_list() {
        $search = $this->input->post('search');
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        $this->per_page = 10;
        $temp = $this->todo_timein->timein_list1($this->emp_id, $this->company_id,urldecode($search), "", "", "yes");
        $temp1 = array();

        if($temp == false) {
            $temp = array();
        }
        
        if($temp1 == false) {
            $temp1 = array();
        }
        
        $test = array_merge($temp, $temp1);
        
        $new_page = ($page - 1)  * $this->per_page;
        if($new_page < 0) {
            $new_page = 0;
        }
        
        if ($test) {
            $test1 = array_slice($test,$new_page,$this->per_page);
            echo json_encode($test1);
        } else {
            echo json_encode(array());
        }
        return false;
    }

    function todo_rest_day_list() {
        $search_id = $this->input->post('search');
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        $this->per_page = 10;

        $list = $this->todo_timein->timein_list_new($this->emp_id, $this->company_id, "", "yes");
        if (!$list) {
            $res = array(
                "result" => false,
                "error" => false,
                "list" => false,
                "page" => 1,
                "total" => 0
            );
            echo json_encode($res);
            return false;
        }

        $count_total_rows = count($list);
    
        $final_list = $this->trim_timein_list($list, false);

        echo json_encode(array(
            "result" => true,
            "error" => false,
            "list" => $final_list,
            "page" => $page,
            "total" => ($count_total_rows) ? ceil(count($count_total_rows)) : ""
        ));
    }

    function todo_holiday_list() {
        $search_id = $this->input->post('search');
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        $this->per_page = 10;
        
        $list = $this->todo_timein->timein_list_new($this->emp_id, $this->company_id, "", "no", "yes");
        if (!$list) {
            $res = array(
                "result" => false,
                "error" => false,
                "list" => false,
                "page" => 1,
                "total" => 0
            );
            echo json_encode($res);
            return false;
        }

        $count_total_rows = count($list);
    
        $final_list = $this->trim_timein_list($list, false);

        echo json_encode(array(
            "result" => true,
            "error" => false,
            "list" => $final_list,
            "page" => $page,
            "total" => ($count_total_rows) ? ceil(count($count_total_rows)) : ""
        ));
    }
}