<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Manager Employee Directory
 * Controller for employee directory of manager
 * @category controller
 * @version 1.0
 * @author 47
 */
class Manager_employee_directory extends CI_Controller
{
    
    public function __construct()
    {
        parent::__construct();
        
        $this->load->model('employee_model','employee');
        $this->load->model('manager_employee_directory_model','medm');
        
        $this->company_info = whose_company();
        $this->emp_id       = $this->session->userdata('emp_id');
        $this->company_id   = $this->employee->check_company_id($this->emp_id);
        $this->account_id   = $this->session->userdata('account_id');
    }

    public function employee_list_lite() {
        $get_emp_lists = $this->medm->get_active_eployees_lite($this->company_id, $this->emp_id);

        $directory_list = array();
        if ($get_emp_lists) {
            foreach ($get_emp_lists as $row) {
                $fullname = $row->first_name.' '.$row->last_name;
                $fullname = ($fullname) ? ucwords($fullname) : '';
                $t = array(
                    'full_name'     => $fullname,
                    'last_name'     => ($row->last_name) ? ucfirst($row->last_name) : '',
                    'first_name'    => $row->first_name,
                    'emp_id'        => $row->emp_id,
                    'profile_pic'   => $row->profile_image,
                    'id'            => $row->payroll_cloud_id,
                    'company_id'    => $this->company_id
                );
                array_push($directory_list, $t);
            }
        }
        echo json_encode($directory_list);
        return false;
    }

    public function get_emp_details() {
        $empid = $this->input->post('emp_id');
        $row = $this->medm->get_active_employee_details($empid, $this->company_id);

        $directory_list = array();
        if ($row) {
            $employee_shift_schedule        = $this->medm->employee_assigned_work_schedule($this->company_id);
            $get_work_schedule_default      = $this->medm->employee_work_schedule_via_payroll_group($this->company_id);
            $employee_shift_schedule_regular= $this->medm->employee_work_schedule_regular($this->company_id);
            $employee_shift_schedule_flex   = $this->medm->employee_flex_work_schedule($this->company_id);
            $employee_schedule_blocks       = $this->medm->employee_schedule_blocks_v2($this->company_id);

            $middle_name = ($row->middle_name) ? $row->middle_name.' ' : '';
                $fullname = $row->first_name.' '.$row->last_name;
                
                $emp_shifts = $this->get_employee_shifts($this->company_id, $row->emp_id, $employee_shift_schedule, $get_work_schedule_default,
                    $employee_shift_schedule_regular, $employee_shift_schedule_flex, $employee_schedule_blocks,
                    $row->payroll_group_id);
                
                $temp_array = array(
                    'id'            => $row->payroll_cloud_id,
                    'first_name'    => $row->first_name,
                    'last_name'     => $row->last_name,
                    'emp_id'        => $row->emp_id,
                    'account_id'    => $row->account_id,
                    'profile_pic'   => $row->profile_image,
                    'company_id'    => $this->company_id,
                     //'base_url'      => 'http://payrollv3.konsum.local/',
                    'base_url'      => base_url(),
                    'email'         => $row->email,
                    'position'      => $row->position_name,
                    'mobile'        => $row->login_mobile_number,
                    'second_mob'    => $row->login_mobile_number_2,
                    'address'       => $row->address,
                    'telephone'     => $row->telephone_number,
                    'middle_name'   => $row->middle_name,
                    'nickname'      => $row->nickname,
                    'birthday'      => $row->dob,
                    'gender'        => $row->gender,
                    'marital_stat'  => $row->marital_status,
                    'citizenship'   => $row->citizenship_status,
                    'full_name'     => $fullname,
                    'employee_stat' => $row->employee_status,
                    'rank'          => $row->rank_name,
                    'department'    => $row->department_name,
                    'cost_center'   => $row->cost_center_code,
                    'location_asgnd'=> $row->name,
                    'hire_date'     => $row->date_hired,
                    'shifts'        => $emp_shifts
                );
                $directory_list = $temp_array;
        }
        echo json_encode($directory_list);
        return false;
    }
    
    public function employee_list() 
    {
        $get_emp_lists = $this->medm->get_active_eployees($this->company_id, $this->emp_id);
        
        // for shifts
        $employee_shift_schedule        = $this->medm->employee_assigned_work_schedule($this->company_id);
        $get_work_schedule_default      = $this->medm->employee_work_schedule_via_payroll_group($this->company_id);
        $employee_shift_schedule_regular= $this->medm->employee_work_schedule_regular($this->company_id);
        $employee_shift_schedule_flex   = $this->medm->employee_flex_work_schedule($this->company_id);
        $employee_schedule_blocks       = $this->medm->employee_schedule_blocks_v2($this->company_id);
//         $work_schedule_details          = $this->medm->work_schedule_details($this->company_id);
        
//         p($work_schedule_details);
        
        $directory_list = array();
        if ($get_emp_lists) {
            foreach ($get_emp_lists as $row) {

                $middle_name = ($row->middle_name) ? $row->middle_name.' ' : '';
                $fullname = $row->first_name.' '.$row->last_name;
                
                $emp_shifts = $this->get_employee_shifts($this->company_id, $row->emp_id, $employee_shift_schedule, $get_work_schedule_default,
                    $employee_shift_schedule_regular, $employee_shift_schedule_flex, $employee_schedule_blocks,
                    $row->payroll_group_id);
                
                $temp_array = array(
                    'id'            => $row->payroll_cloud_id,
                    'first_name'    => $row->first_name,
                    'last_name'     => $row->last_name,
                    'emp_id'        => $row->emp_id,
                    'account_id'    => $row->account_id,
                    'profile_pic'   => $row->profile_image,
                    'company_id'    => $this->company_id,
                     //'base_url'      => 'http://payrollv3.konsum.local/',
                    'base_url'      => base_url(),
                    'email'         => $row->email,
                    'position'      => $row->position_name,
                    'mobile'        => $row->login_mobile_number,
                    'second_mob'    => $row->login_mobile_number_2,
                    'address'       => $row->address,
                    'telephone'     => $row->telephone_number,
                    'middle_name'   => $row->middle_name,
                    'nickname'      => $row->nickname,
                    'birthday'      => $row->dob,
                    'gender'        => $row->gender,
                    'marital_stat'  => $row->marital_status,
                    'citizenship'   => $row->citizenship_status,
                    'full_name'     => $fullname,
                    'employee_stat' => $row->employee_status,
                    'rank'          => $row->rank_name,
                    'department'    => $row->department_name,
                    'cost_center'   => $row->cost_center_code,
                    'location_asgnd'=> $row->name,
                    'hire_date'     => $row->date_hired,
                    'shifts'        => $emp_shifts
                );
                array_push($directory_list, $temp_array);
            }
        }
        // $list = $this->sort_employee_list($directory_list);
        // p($list);
        echo json_encode($directory_list);
        return false;
    }

    public function sort_employee_list($list) {
        if (!$list) {
            return false;
        }

        $return_ar = array();
        $ar_len = count($list);
        foreach ($list as $row) {
            
            $letter = ($row['first_name']) ? strtoupper($row['first_name']) : '';
            $letter = $letter[0];
            $return_ar[$letter][] = $row;
        }
        return $return_ar;
    }
    
    public function get_employee_shifts(
        $company_id, $emp_id, $employee_shift_schedule, $get_work_schedule_default, $employee_shift_schedule_regular, $employee_shift_schedule_flex,
        $employee_schedule_blocks, $payroll_group_id
    )
    {
        $emp_one_week_sched = array();
        
        $payroll_group_id = ($payroll_group_id > 0) ? $payroll_group_id : 0;

        $current_monday = date("Y-m-d",strtotime(" monday this week")-1);
        
        
        for($c=0;$c<7;$c++)
        {
            $date_calendar = date("M-d D",strtotime($current_monday." +{$c} day"));
            $date_counter = date("Y-m-d",strtotime($current_monday." +{$c} day"));
            
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
            
            if($emp_sched) {
                
                $flag = 1;
                
                $shifts_emp_id = $emp_sched->shifts_emp_id;
                $work_schedule_id = $emp_sched->work_schedule_id;
                $work_shift_id = $emp_sched->work_shift_id;
                $shift_type = $emp_sched->shift_type;
                
            }else{
                /* DEFAULT WORK SCHEDULE VIA PAYROLL GROUP */
                $default_custom_sched = in_array_custom("{$emp_id}{$payroll_group_id}",$get_work_schedule_default);
                $def_sched = ($default_custom_sched != FALSE) ? $default_custom_sched : FALSE;
                
                if($def_sched) {
                    $flag = 2;
                    $shifts_emp_id = $emp_id;
                    $work_schedule_id = $def_sched->work_schedule_id;
                    $shift_type = $def_sched->shift_type;
                }
                
            }
            
            if($flag != NULL) {
                
                /*	REGULAR SCHEDULE */
                if($shift_type == "Uniform Working Days") {
                    
                    $reg_date = date("l",strtotime($date_counter));
                    $regular_custom_sched = in_array_custom("{$work_schedule_id}{$reg_date}",$employee_shift_schedule_regular);
                    $reg_sched = ($regular_custom_sched != FALSE) ? $regular_custom_sched : FALSE ;
                    
                    if($reg_sched) {
                        
                        $st = date("h:i A",strtotime($reg_sched->work_start_time));
                        $et = date("h:i A",strtotime($reg_sched->work_end_time));
                        
                        $reg_block_holder = $st." - ".$et;
                        $emp_one_week_sched[$c] = $reg_block_holder;
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
                    $emp_one_week_sched[$c] = 'Rest Day';
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
                            
                            foreach( $get_emp_blocks as $row_block ) {
                                
                                $st = date("h:i A",strtotime($row_block->start_time));
                                $et = date("h:i A",strtotime($row_block->end_time));
                                
                                $block_range = $st." - ".$et;
                                array_push($shift_blocks_holder, $block_range);
                            }
                            $emp_one_week_sched[$c] = $shift_blocks_holder;
                        }
                        
                    }// else workshift
                    else
                    {
                        $emp_one_week_sched[$c] = $str;
                    } // end workshift
                    
                } //end else $rest_day
            } // end  if != null
            
        }// end for loop
        
        return $emp_one_week_sched;
    }
}