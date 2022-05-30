<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Manager Shifts
 * Controller for manager shifts
 * @category controller
 * @version 1.0
 * @author 47
 */
class Manager_shifts extends CI_Controller
{
    
    public function __construct()
    {
        parent::__construct();
        
        $this->load->model('employee_model','employee');
        $this->load->model('manager_employee_directory_model','medm');
        $this->load->model('manager_shifts_model','msm');
        
        $this->company_info = whose_company();
        $this->emp_id       = $this->session->userdata('emp_id');
        $this->company_id   = $this->employee->check_company_id($this->emp_id);
        $this->account_id   = $this->session->userdata('account_id');
        $this->psa_id       = $this->session->userdata('psa_id');
    }

    public function shifts_list()
    {
        $reqdate = $this->input->post('reqDate');
        $get_emp_lists = $this->msm->get_employee_list($this->company_id, $this->emp_id);

        $emp_ids = $this->emp_ids($get_emp_lists);

        $sched_date = '';
        if ($reqdate) {
            $sched_date = date("Y-m-d", strtotime($reqdate));
        }

        // for shifts
        $employee_shift_schedule        = $this->medm->employee_assigned_work_schedule($this->company_id, '', $emp_ids);
        $get_work_schedule_default      = $this->medm->employee_work_schedule_via_payroll_group($this->company_id);
        $employee_shift_schedule_regular= $this->medm->employee_work_schedule_regular($this->company_id, $emp_ids);
        $employee_shift_schedule_flex   = $this->medm->employee_flex_work_schedule($this->company_id);
        $employee_schedule_blocks       = $this->medm->employee_schedule_blocks_v2($this->company_id, $emp_ids);
        $work_schedule_details          = $this->medm->work_schedule_details($this->company_id);

        //p($get_work_schedule_default);

        $directory_list = array();
        //if (0) {
        if ($get_emp_lists) {
            foreach ($get_emp_lists as $row) {

                $fullname = $row->first_name.' '.$row->last_name;
                
                $emp_shifts = $this->get_employee_shifts($this->company_id, $row->emp_id, $employee_shift_schedule, $get_work_schedule_default,
                    $employee_shift_schedule_regular, $employee_shift_schedule_flex, $employee_schedule_blocks,
                    $row->payroll_group_id, $work_schedule_details, $sched_date);
                
                $temp_array = array(
                    'id'            => $row->payroll_cloud_id,
                    'first_name'    => $row->first_name,
                    'last_name'     => $row->last_name,
                    'emp_id'        => $row->emp_id,
                    'account_id'    => $row->account_id,
                    'profile_pic'   => $row->profile_image,
                    'company_id'    => $this->company_id,
                    'base_url'      => base_url(),
                    'full_name'     => $fullname,
                    'employee_stat' => $row->employee_status,
                    'payroll_group_id' => $row->payroll_group_id,
                    'shifts'        => $emp_shifts,
                    'base_url'      => base_url()
                );
                array_push($directory_list, $temp_array);
            }
        }
        echo json_encode($directory_list);
        return false;
    }

    public function emp_ids($emp_list)
    {
        if (!$emp_list) {
            return false;
        }

        $ids = array();
        foreach ($emp_list as $row) {
            array_push($ids, $row->emp_id);
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
}