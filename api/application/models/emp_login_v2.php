 <?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Employee Login For Uniform Working Days & Workshift Model
 *
 * @category Model
 * @version 1.1
 * @author Aldrin Cantero <aldrin.cantero@gmail.com>
 * @author Barack <johnrobert.lego@outlook.com>
 *
 */
class Emp_login_v2 extends CI_Model {
    
    var $type;
    var $split_schedule_id;
    var $schedule_blocks_id;
    var $schedule_blocks_timein_id;
    var $work_schedule_id;

    public function __construct()
    {
        parent::__construct();
    }
    
    public function insert_time_in($date,$emp_no,$min_log,$work_schedule_id,$check_type="",$source="",$activate_no_schedule = false, $sync_date ="",$sync_employee_time_in_id ="",$comp_id = 0,$date_capture,$pre_shift=false,$cronjob=false, $location="", $capture_img=""){

        $locloc = $location;

        // ### NOTE BERFORE ANYTHING HAPPEN ###
        // remember ang field sa db total_hours ug total_hours_required kay nagkabaylo 
        // total_hours => mao ang shift hours, total_hours_required => mao ang giwork sa employee 

        /* added for mobile */
        $mobile_pending_field="";
        $location_field="";

        $ck_emp_info                                                                = $this->check_emp_no($emp_no,$comp_id);
        $emp_id                                                                     = $ck_emp_info->emp_id;

        $emp_ids                                                                    = array($emp_id);
        // check the correct shift here
        $check_type_o                                                               = $check_type;
        $number_of_breaks_per_day                                                   = 0;
        $barack_date_trap_exact_t_date                                              = ($date_capture) ? $date_capture : date('Y-m-d H:i:00');
        $barack_date_trap_exact_date                                                = ($date_capture) ? date('Y-m-d',strtotime($date_capture)) : date('Y-m-d');
        
        // CHECK LAST PUNCH WORK SCHEDULE
        $get_all_employee_timein                                                    = $this->get_all_employee_timein($comp_id,$emp_ids);
        $emp_work_schedule_ess                                                      = $this->emp_work_schedule_ess($comp_id,$emp_ids);
        $get_employee_payroll_information                                           = $this->get_employee_payroll_information($comp_id,$emp_ids);
        $emp_work_schedule_epi                                                      = $this->emp_work_schedule_epi($comp_id,$emp_ids);
        $get_work_schedule                                                          = $this->get_work_schedule($comp_id);
        $get_all_regular_schedule                                                   = $this->get_all_regular_schedule($comp_id);
        $comp_add                                                                   = $this->get_company_address($comp_id);
        $list_of_blocks                                                             = $this->list_of_blocks($comp_id,$emp_ids);
        $get_all_schedule_blocks_time_in                                            = $this->get_all_schedule_blocks_time_in($comp_id,$emp_ids);
        $get_work_schedule_flex                                                     = $this->get_work_schedule_flex($comp_id);
        $get_employee_leave_application                                             = $this->get_employee_leave_application($comp_id,$emp_ids);
        $get_tardiness_settings                                                     = $this->get_tardiness_settings($comp_id);
        $get_tardiness_settingsv2                                                   = $this->get_tardiness_settingsv2($comp_id);
        $check_rest_days                                                            = $this->check_rest_day($comp_id);
        $tardiness_rounding                                                         = $this->tardiness_rounding($comp_id);
        $get_all_schedule_blocks                                                    = $this->get_all_schedule_blocks($comp_id);
        $company_holiday                                                            = $this->company_holiday($comp_id);
        $attendance_hours                                                           = is_attendance_active($comp_id);

        $payroll_group                                                              = 0;
        $approver_id                                                                = '';
        $payroll_group1                                                             = in_array_custom("emp_id_{$emp_id}",$get_employee_payroll_information);
        if($payroll_group1){
            $payroll_group                                                          = $payroll_group1->payroll_group_id;
            $approver_id                                                            = $payroll_group1->location_base_login_approval_grp;
        }

        $last_timein                                                                = in_array_custom("emp_id_timeins_{$emp_id}",$get_all_employee_timein);

        if($last_timein){
            $date_last_time                                                         = $last_timein->date;
            $locloc = $last_timein->location;
        }

        /** added mobile **/
        
        $fullname = $ck_emp_info->first_name." ".$ck_emp_info->last_name;
        $employee_details = get_employee_details_by_empid($emp_id);
        $hours_notification = get_notify_settings($approver_id, $comp_id);
        $is_workflow_enabled = is_workflow_enabled($comp_id);
        $newtimein_approver = $this->agm->get_approver_name_timein_location($emp_id,$comp_id);

        $emp_work_schedule_id = $this->import->emp_work_schedule($emp_id,$comp_id,$date);
        if(!$emp_work_schedule_id){
            $emp_work_schedule_id = $this->elm->if_no_workschedule($emp_id,$comp_id);
        }

        $check_latest_timein_allowed = $this->elmf->check_lastest_timein_allowed($emp_work_schedule_id,$comp_id);
        
        /** added mobile **/

        // RE INITIALIZE CHECK TYPE (if source != desktop) HERE ADD NEW BREAK RULES
        // check if enable additional break and lunch break 
        
        if($last_timein){
            $last_t_worksched_id                                                        = $last_timein->work_schedule_id;
            $last_t_time_in                                                             = $last_timein->time_in;
            $last_t_lunch_in                                                            = $last_timein->lunch_in;
            $last_t_lunch_out                                                           = $last_timein->lunch_out;
            $last_t_time_out                                                            = $last_timein->time_out;
            $last_t_break1_in                                                           = $last_timein->break1_in;
            $last_t_break1_out                                                          = $last_timein->break1_out;
            $last_t_break2_in                                                           = $last_timein->break2_in;
            $last_t_break2_out                                                          = $last_timein->break2_out;
            $last_t_date                                                                = $last_timein->date;
            $last_t_reg_or_excess                                                       = $last_timein->flag_regular_or_excess;
            
            $last_t_day                                                                 = date("l",strtotime($last_t_date));
            
            $last_t_workscheds                                                          = in_array_custom("worksched_id_{$last_t_worksched_id}",$get_work_schedule);
            $last_regular_schedule                                                      = in_array_custom("rsched_id_{$last_t_worksched_id}_{$last_t_day}",$get_all_regular_schedule);

            $enable_lunch_break                                                         = "";
            $enable_additional_breaks                                                   = "";
            $track_break_1                                                              = "";
            $track_break_2                                                              = "";
            $num_of_additional_breaks                                                   = "";
            $break_schedule_1                                                           = "";
            $break_started_after                                                        = 0;
            $break_schedule_2                                                           = "";
            $additional_break_started_after_1                                           = 0;
            $additional_break_started_after_2                                           = 0;
            $tardiness_rule                                                             = "";

            $check_if_migrate                                                           = $this->check_if_migrate($last_t_worksched_id,$get_work_schedule);

            if($last_t_workscheds){
                $enable_lunch_break                                                     = $last_t_workscheds->enable_lunch_break;
                $enable_additional_breaks                                               = $last_t_workscheds->enable_additional_breaks;
                $track_break_1                                                          = $last_t_workscheds->track_break_1;
                $track_break_2                                                          = $last_t_workscheds->track_break_2;
                $num_of_additional_breaks                                               = $last_t_workscheds->num_of_additional_breaks;
                $break_schedule_1                                                       = $last_t_workscheds->break_schedule_1;
                $break_started_after                                                    = $last_t_workscheds->break_started_after;
                $break_schedule_2                                                       = $last_t_workscheds->break_schedule_2;
                $additional_break_started_after_1                                       = $last_t_workscheds->additional_break_started_after_1;
                $additional_break_started_after_2                                       = $last_t_workscheds->additional_break_started_after_2;
                $tardiness_rule                                                         = $last_t_workscheds->tardiness_rule;
            }
            

            // make sure it is regular sched not split
            if($last_regular_schedule){

                $last_regular_start_time                                                = $last_regular_schedule->work_start_time; 
                $last_regular_end_time                                                  = $last_regular_schedule->work_end_time;
                $last_tresh                                                             = $last_regular_schedule->latest_time_in_allowed;
                $lunch_min_b                                                            = $last_regular_schedule->break_in_min;
                $lunch_min_b                                                            = number_format($lunch_min_b,0);
                $break_1_min_b                                                          = $last_regular_schedule->break_1;
                $break_1_min_b                                                          = number_format($break_1_min_b,0);
                $break_2_min_b                                                          = $last_regular_schedule->break_2;
                $break_2_min_b                                                          = number_format($break_2_min_b,0);

                $last_et_date                                                           = $last_t_date;

                if($last_regular_start_time > $last_regular_end_time){

                    $last_et_date                                                       = date("Y-m-d",strtotime($last_et_date." +1 day"));
                }

                $time_break                                                             = $last_t_date." ".$last_regular_start_time;
                $time_e_break                                                           = $last_et_date." ".$last_regular_end_time;

                
                if($check_if_migrate){
                    if($source != "desktop" || $cronjob){
                        
                        if($last_t_time_out != "" || $last_t_time_out != NULL){

                            $check_type                                                 = "time in";
                        }
                        else{
                            if(($last_t_time_in != "" || $last_t_time_in != NULL) && $last_t_reg_or_excess == "regular"){
                                // check if enable additional break and lunch break 
                                if($enable_lunch_break == "yes" || $enable_additional_breaks == "yes"){

                                    $break_after_1                                      = 0;
                                    $break_after_2                                      = 0;
                                    $break_after_3                                      = 0;
                                    $num_s_breaks                                       = $num_of_additional_breaks;
                                    $lunch_track                                        = false;
                                    $break_track                                        = false;

                                    if($enable_lunch_break == "yes" && $track_break_1 == "yes"){

                                        $lunch_track                                    = true;

                                        if($break_schedule_1 == "fixed"){
                                            $break_after_1                              = $break_started_after;
                                        }
                                    }
                                    if($enable_additional_breaks == "yes" && $track_break_2 == "yes"){

                                        $break_track                                    = true;

                                        if($break_schedule_2 == "fixed"){

                                            $break_after_2                              = $additional_break_started_after_1;
                                            $break_after_3                              = $additional_break_started_after_2;
                                        }
                                    }
                                    
                                    $checker_if_fall                                    = false;

                                    // scene : if tanan naka enable
                                    if($break_track && $lunch_track){

                                        $if_fixed                                       = array();
                                        $if_assumed                                     = array();

                                        if($break_schedule_1 == "fixed"){

                                            $if_fixed['lunch']                          = $break_after_1;

                                        }else{

                                            $if_assumed['lunch']                        = $break_after_1;
                                        }

                                        if($break_schedule_2 == "fixed"){
                                            if($num_s_breaks > 1){
                                                $if_fixed['break1']                     = $break_after_2;
                                                $if_fixed['break2']                     = $break_after_3;
                                            }
                                            else{
                                                $if_fixed['break1']                     = $break_after_2;
                                            }
                                        }else{
                                            if($num_s_breaks > 1){

                                                $if_assumed['break1']                   = $break_after_2;
                                                $if_assumed['break2']                   = $break_after_3;
                                            }
                                            else{

                                                $if_assumed['break1']                   = $break_after_2;
                                            }
                                        }

                                        // if naa usa na fixed
                                        if($if_fixed){

                                            arsort($if_fixed);

                                            foreach ($if_fixed as $key => $value) {

                                                $value_min                              = $value * 60;
                                                $value_min                              = number_format($value_min,0);
                                                $time_breakx                            = date("Y-m-d H:i:00",strtotime($time_break." +".$value_min." minutes"));
                                                

                                                if($key == "lunch" && $value_min > 0){

                                                    if($time_breakx <= $barack_date_trap_exact_t_date){
                                                        
                                                        if(($last_t_lunch_in == "" || $last_t_lunch_in == NULL) && ($last_t_lunch_out != "" || $last_t_lunch_out != NULL)){

                                                            $checker_if_fall            = true;
                                                            $check_type                 = "lunch in";
                                                        }
                                                        else if(($last_t_lunch_out == "" || $last_t_lunch_out == NULL)){

                                                            if(!$checker_if_fall){

                                                                $time_out_end           = date("Y-m-d H:i:00",strtotime($time_e_break." -".$lunch_min_b." minutes"));

                                                                if($barack_date_trap_exact_t_date >= $time_out_end){
                                                                    $check_type         = "time out";
                                                                    $checker_if_fall    = true;
                                                                }
                                                                else{
                                                                    $time_break_end     = date("Y-m-d H:i:00",strtotime($time_breakx." +".$lunch_min_b." minutes"));

                                                                    if($barack_date_trap_exact_t_date < $time_break_end){
                                                                        $check_type     = "lunch out";
                                                                        $checker_if_fall= true;
                                                                    }
                                                                    else{
                                                                        $check_type     = "time out";
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                                
                                                if($key == "break1"){

                                                    if($time_breakx <= $barack_date_trap_exact_t_date){

                                                        if(($last_t_break1_in == "" || $last_t_break1_in == NULL) && ($last_t_break1_out != "" || $last_t_break1_out != NULL)){

                                                            $checker_if_fall            = true;
                                                            $check_type                 = "break1 in";
                                                        }
                                                        else if(($last_t_break1_out == "" || $last_t_break1_out == NULL)){

                                                            if(!$checker_if_fall){

                                                                $time_out_end           = date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_1_min_b." minutes"));

                                                                if($barack_date_trap_exact_t_date >= $time_out_end){

                                                                    $check_type         = "time out";
                                                                    $checker_if_fall    = true;
                                                                }else{
                                                                    $time_break_end     = date("Y-m-d H:i:00",strtotime($time_breakx." +".$break_1_min_b." minutes"));

                                                                    if($barack_date_trap_exact_t_date < $time_break_end){
                                                                        
                                                                        $check_type     = "break1 out"; 
                                                                        $checker_if_fall= true;
                                                                    }
                                                                    else{
                                                                        $check_type     = "time out";
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }

                                                if($key == "break2"){

                                                    if($time_breakx <= $barack_date_trap_exact_t_date){

                                                        if(($last_t_break2_in == "" || $last_t_break2_in == NULL)  && ($last_t_break2_out != "" || $last_t_break2_out != NULL)){

                                                            $checker_if_fall            = true;
                                                            $check_type                 = "break2 in";
                                                        }
                                                        else if(($last_t_break2_out == "" || $last_t_break2_out == NULL)){

                                                            if(!$checker_if_fall){

                                                                $time_out_end           = date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_2_min_b." minutes"));

                                                                if($barack_date_trap_exact_t_date >= $time_out_end){

                                                                    $check_type         = "time out";
                                                                    $checker_if_fall    = true;
                                                                }
                                                                else{

                                                                    $time_break_end     = date("Y-m-d H:i:00",strtotime($time_breakx." +".$break_2_min_b." minutes"));

                                                                    if($barack_date_trap_exact_t_date < $time_break_end){
                                                                            
                                                                        $check_type     = "break2 out";
                                                                        $checker_if_fall= true;
                                                                    }
                                                                    else{
                                                                        $check_type     = "time out";
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        
                                        if(!$checker_if_fall){

                                            // if naa usa sad na assumed
                                            if($if_assumed){


                                                // if two additional break flexi 
                                                // first break consume first - priority 

                                                if($break_schedule_2 == "flexi"){

                                                    if($num_s_breaks > 1){

                                                        if(($last_t_break1_out == "" || $last_t_break1_out == NULL)){

                                                            if(!$checker_if_fall){

                                                                $time_out_end           = date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_1_min_b." minutes"));

                                                                if($barack_date_trap_exact_t_date >= $time_out_end){

                                                                    $check_type         = "time out";
                                                                }
                                                                else{

                                                                    $check_type         = "break1 out";
                                                                }
                                                                $checker_if_fall        = true;
                                                            }
                                                        }
                                                        else if(($last_t_break1_in == "" || $last_t_break1_in == NULL) && ($last_t_break1_out != "" || $last_t_break1_out != NULL)){

                                                            $checker_if_fall            = true;
                                                            $check_type                 = "break1 in";
                                                        }
                                                    }
                                                }

                                                // if lunch break flexi 
                                                // first lunch break consume first before break if only 1 break

                                                if($break_schedule_1 == "flexi"){

                                                    if(($last_t_lunch_out == "" || $last_t_lunch_out == NULL)){

                                                        if(!$checker_if_fall){

                                                            $time_out_end               = date("Y-m-d H:i:00",strtotime($time_e_break." -".$lunch_min_b." minutes"));

                                                            if($barack_date_trap_exact_t_date >= $time_out_end){

                                                                $check_type             = "time out";
                                                            }
                                                            else{
                                                                if($lunch_min_b > 0){

                                                                    $check_type         = "lunch out";
                                                                }
                                                                else{
                                                                    $check_type         = "time out";
                                                                }
                                                            }
                                                            $checker_if_fall            = true;
                                                        }
                                                    }
                                                    else if(($last_t_lunch_in == "" || $last_t_lunch_in == NULL) && ($last_t_lunch_out != "" || $last_t_lunch_out != NULL)){

                                                        $checker_if_fall                = true;
                                                        $check_type                     = "lunch in";
                                                    }
                                                }


                                                // break flexi 
                                                if($break_schedule_2 == "flexi"){

                                                    if($num_s_breaks == 1){

                                                        if(($last_t_break1_out == "" || $last_t_break1_out == NULL)){

                                                            if(!$checker_if_fall){
                                                                $time_out_end           = date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_1_min_b." minutes"));

                                                                if($barack_date_trap_exact_t_date >= $time_out_end){

                                                                    $check_type         = "time out";
                                                                }
                                                                else{
                                                                    
                                                                    $check_type         = "break1 out";
                                                                }

                                                                $checker_if_fall        = true;
                                                            }
                                                        }
                                                        else if(($last_t_break1_in == "" || $last_t_break1_in == NULL) && ($last_t_break1_out != "" || $last_t_break1_out != NULL)){

                                                            $checker_if_fall            = true;
                                                            $check_type                 = "break1 in";
                                                        }
                                                    }

                                                    if($num_s_breaks > 1){

                                                        if(($last_t_break2_out == "" || $last_t_break2_out == NULL)){

                                                            if(!$checker_if_fall){

                                                                $time_out_end           = date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_2_min_b." minutes"));

                                                                if($barack_date_trap_exact_t_date >= $time_out_end){

                                                                    $check_type         = "time out";
                                                                }
                                                                else{

                                                                    $check_type         = "break2 out";
                                                                }

                                                                $checker_if_fall        = true;
                                                            }
                                                        }
                                                        else if(($last_t_break2_in == "" || $last_t_break2_in == NULL) && ($last_t_break2_out != "" || $last_t_break2_out != NULL)){

                                                            $checker_if_fall            = true;
                                                            $check_type                 = "break2 in";
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        // not in belong break schedule
                                        if(!$checker_if_fall){
                                            $check_type                                 = "time out";
                                        }
                                    }
                                    // scene : if lunch break naka enable
                                    else if($lunch_track){

                                        if($break_schedule_1 == "fixed"){
                                            
                                            if($break_after_1 > 0){
                                                $value                                      = $break_after_1 * 60;
                                                $value                                      = number_format($value,0);
                                                $time_break                                 = date("Y-m-d H:i:00",strtotime($time_break." +".$value." minutes"));

                                                if($time_break <= $barack_date_trap_exact_t_date){

                                                    if(($last_t_lunch_in == "" || $last_t_lunch_in == NULL) && ($last_t_lunch_out != "" || $last_t_lunch_out != NULL)){

                                                        $checker_if_fall                    = true;
                                                        $check_type                         = "lunch in";
                                                    }
                                                    else if(($last_t_lunch_out == "" || $last_t_lunch_out == NULL)){

                                                        if(!$checker_if_fall){

                                                            $time_out_end                   = date("Y-m-d H:i:00",strtotime($time_e_break." -".$lunch_min_b." minutes"));

                                                            if($barack_date_trap_exact_t_date >= $time_out_end){

                                                                $check_type                 = "time out";
                                                            }
                                                            else{
                                                                $time_break_end             = date("Y-m-d H:i:00",strtotime($time_break." +".$lunch_min_b." minutes"));

                                                                if($barack_date_trap_exact_t_date < $time_break_end){

                                                                    $check_type             = "lunch out";
                                                                }
                                                                else{
                                                                    $check_type             = "time out";
                                                                }
                                                            }
                                                            $checker_if_fall                = true;
                                                        }
                                                    }
                                                }
                                            }else{
                                                $check_type                                 = "time out";
                                                $checker_if_fall                            = true;
                                            }
                                        }
                                        else{

                                            if(($last_t_lunch_out == "" || $last_t_lunch_out == NULL)){

                                                if(!$checker_if_fall){
                                                    $time_out_end                           = date("Y-m-d H:i:00",strtotime($time_e_break." -".$lunch_min_b." minutes"));

                                                    if($barack_date_trap_exact_t_date >= $time_out_end){
                                                        $check_type                         = "time out";
                                                    }
                                                    else{
                                                        if($lunch_min_b > 0){
                                                            $check_type                     = "lunch out";
                                                        }
                                                        else{
                                                            $check_type                     = "time out";
                                                        }
                                                    }

                                                    $checker_if_fall                        = true;
                                                }
                                            }
                                            else if(($last_t_lunch_in == "" || $last_t_lunch_in == NULL) && ($last_t_lunch_out != "" || $last_t_lunch_out != NULL)){

                                                $checker_if_fall                            = true;
                                                $check_type                                 = "lunch in";
                                            }
                                        }

                                        if(!$checker_if_fall){
                                            $check_type                                     = "time out";
                                        }
                                    }
                                    // scene : if additional break ra naka enable
                                    else{

                                        if($break_schedule_2 == "fixed"){

                                            if($break_after_2 > $break_after_3){

                                                $value                                  = $break_after_2 * 60;
                                                $value                                  = number_format($value,0);
                                                $time_breakx                            = date("Y-m-d H:i:00",strtotime($time_break." +".$value." minutes"));
                                                
                                                if($time_breakx <= $barack_date_trap_exact_t_date){

                                                    if(($last_t_break1_in == "" || $last_t_break1_in == NULL) && ($last_t_break1_out != "" || $last_t_break1_out != NULL)){

                                                        $checker_if_fall                = true;
                                                        $check_type                     = "break1 in";
                                                    }
                                                    else if(($last_t_break1_out == "" || $last_t_break1_out == NULL)){

                                                        if(!$checker_if_fall){

                                                            $time_out_end               = date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_1_min_b." minutes"));

                                                            if($barack_date_trap_exact_t_date >= $time_out_end){

                                                                $check_type             = "time out";
                                                            }else{

                                                                $time_break_end         = date("Y-m-d H:i:00",strtotime($time_breakx." +".$break_1_min_b." minutes"));

                                                                if($barack_date_trap_exact_t_date < $time_break_end){
                                                                    
                                                                    $check_type         = "break1 out"; 
                                                                }
                                                                else{
                                                                    $check_type         = "time out";
                                                                }
                                                            }
                                                            $checker_if_fall            = true;
                                                        }
                                                    }
                                                }

                                                if($break_after_3 != "0" && $break_after_3 != NULL && $break_after_3 > 0){

                                                    $value                              = $break_after_3 * 60;
                                                    $value                              = number_format($value,0);
                                                    $time_breakx                        = date("Y-m-d H:i:00",strtotime($time_break." +".$value." minutes"));

                                                    if($time_breakx <= $barack_date_trap_exact_t_date){

                                                        if(($last_t_break2_in == "" || $last_t_break2_in == NULL)  && ($last_t_break2_out != "" || $last_t_break2_out != NULL)){

                                                            $checker_if_fall            = true;
                                                            $check_type                 = "break2 in";
                                                        }
                                                        else if(($last_t_break2_out == "" || $last_t_break2_out == NULL)){

                                                            if(!$checker_if_fall){

                                                                $time_out_end           = date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_2_min_b." minutes"));

                                                                if($barack_date_trap_exact_t_date >= $time_out_end){

                                                                    $check_type         = "time out";
                                                                }
                                                                else{
                                                                    $time_break_end     = date("Y-m-d H:i:00",strtotime($time_breakx." +".$break_2_min_b." minutes"));

                                                                    if($barack_date_trap_exact_t_date < $time_break_end){
                                                                        
                                                                        $check_type     = "break2 out";
                                                                    }
                                                                    else{
                                                                        $check_type     = "time out";
                                                                    }
                                                                }
                                                                $checker_if_fall        = true;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            else{

                                                $value                                  = $break_after_3 * 60;
                                                $value                                  = number_format($value,0);
                                                $time_breakx                            = date("Y-m-d H:i:00",strtotime($time_break." +".$value." minutes"));

                                                if($time_breakx <= $barack_date_trap_exact_t_date){
                                                    
                                                    if(($last_t_break2_in == "" || $last_t_break2_in == NULL)  && ($last_t_break2_out != "" || $last_t_break2_out != NULL)){

                                                        $checker_if_fall                = true;
                                                        $check_type                     = "break2 in";
                                                    }
                                                    else if(($last_t_break2_out == "" || $last_t_break2_out == NULL)){

                                                        if(!$checker_if_fall){

                                                            $time_out_end               = date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_2_min_b." minutes"));

                                                            if($barack_date_trap_exact_t_date >= $time_out_end){

                                                                $check_type             = "time out";
                                                            }
                                                            else{

                                                                $time_break_end         = date("Y-m-d H:i:00",strtotime($time_breakx." +".$break_2_min_b." minutes"));

                                                                if($barack_date_trap_exact_t_date < $time_break_end){
                                                                    
                                                                    $check_type         = "break2 out";
                                                                }
                                                                else{
                                                                    $check_type         = "time out";
                                                                }
                                                            }
                                                            $checker_if_fall            = true;
                                                        }
                                                    }
                                                }

                                                $value                                  = $break_after_2 * 60;
                                                $value                                  = number_format($value,0);
                                                $time_breakx                            = date("Y-m-d H:i:00",strtotime($time_break." +".$value." minutes"));
                                                
                                                if($time_breakx <= $barack_date_trap_exact_t_date){
                                                    
                                                    if(($last_t_break1_in == "" || $last_t_break1_in == NULL) && ($last_t_break1_out != "" || $last_t_break1_out != NULL)){

                                                        $checker_if_fall                = true;
                                                        $check_type                     = "break1 in";
                                                    }
                                                    else if(($last_t_break1_out == "" || $last_t_break1_out == NULL)){

                                                        if(!$checker_if_fall){

                                                            $time_out_end               = date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_1_min_b." minutes"));

                                                            if($barack_date_trap_exact_t_date >= $time_out_end){

                                                                $check_type             = "time out";
                                                            }else{
                                                                
                                                                $time_break_end         = date("Y-m-d H:i:00",strtotime($time_breakx." +".$break_1_min_b." minutes"));

                                                                if($barack_date_trap_exact_t_date < $time_break_end){
                                                                    
                                                                    $check_type         = "break1 out";
                                                                }
                                                                else{
                                                                    $check_type         = "time out";
                                                                }
                                                            }
                                                            $checker_if_fall            = true;
                                                        }
                                                    }
                                                }
                                            }                                       
                                        }
                                        else{
                                            if($break_schedule_2 == "flexi"){

                                                if($num_s_breaks == 1){

                                                    if(($last_t_break1_out == "" || $last_t_break1_out == NULL)){

                                                        if(!$checker_if_fall){

                                                            $time_out_end               = date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_1_min_b." minutes"));

                                                            if($barack_date_trap_exact_t_date >= $time_out_end){

                                                                $check_type             = "time out";
                                                            }
                                                            else{
                                                                
                                                                $check_type             = "break1 out";
                                                            }

                                                            $checker_if_fall            = true;
                                                        }
                                                    }
                                                    else if(($last_t_break1_in == "" || $last_t_break1_in == NULL) && ($last_t_break1_out != "" || $last_t_break1_out != NULL)){

                                                        $checker_if_fall                = true;
                                                        $check_type                     = "break1 in";
                                                    }
                                                }

                                                if($num_s_breaks > 1){

                                                    if(($last_t_break1_out == "" || $last_t_break1_out == NULL)){

                                                        if(!$checker_if_fall){
                                                            
                                                            $time_out_end               = date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_1_min_b." minutes"));

                                                            if($barack_date_trap_exact_t_date >= $time_out_end){

                                                                $check_type             = "time out";
                                                            }
                                                            else{
                                                                
                                                                $check_type             = "break1 out";
                                                            }

                                                            $checker_if_fall            = true;
                                                        }
                                                    }
                                                    else if(($last_t_break1_in == "" || $last_t_break1_in == NULL) && ($last_t_break1_out != "" || $last_t_break1_out != NULL)){

                                                        $checker_if_fall                = true;
                                                        $check_type                     = "break1 in";
                                                    }

                                                    if(($last_t_break2_out == "" || $last_t_break2_out == NULL)){

                                                        if(!$checker_if_fall){

                                                            $time_out_end               = date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_2_min_b." minutes"));

                                                            if($barack_date_trap_exact_t_date >= $time_out_end){

                                                                $check_type             = "time out";
                                                            }
                                                            else{

                                                                $check_type             = "break2 out";
                                                            }

                                                            $checker_if_fall            = true;
                                                        }
                                                    }
                                                    else if(($last_t_break2_in == "" || $last_t_break2_in == NULL) && ($last_t_break2_out != "" || $last_t_break2_out != NULL)){

                                                        $checker_if_fall                = true;
                                                        $check_type                     = "break2 in";
                                                    }
                                                }
                                            }
                                        }

                                        if(!$checker_if_fall){
                                            $check_type                                 = "time out";
                                        }
                                    }
                                }
                                else{
                                    // time out
                                    $check_type                                         = "time out";
                                }
                            }else{
                                $check_type                                             = "time out";
                            }
                        }
                    }
                }
            }
            else{
                // split here
            }
        }

        // CHECK IF CURRENT TIME IS ALREADY IN THE WORKSCHEDULE OF CURRENT DAY

        $current_date_start                                                         = $this->check_current_date_start($emp_id,$barack_date_trap_exact_date,$comp_id,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_work_schedule,$get_all_regular_schedule);
        
        if($current_date_start){

            $date_comp                                                              = $barack_date_trap_exact_date." ".$current_date_start->work_start_time;
            $date_comp                                                              = date('Y-m-d H:i:00    ',strtotime($date_comp ."-120 minutes"));
            
            // IF CURRENT TIME IS NOT BELONG TO THE CURRENT DATE SCHED THIS PUNCH IN IS FOR YESTERDAY SCHED
            if(strtotime($date_comp) > strtotime($barack_date_trap_exact_t_date)){

                $barack_date_trap_exact_date                                        = date('Y-m-d',strtotime($barack_date_trap_exact_date ."-1 day"));
            }
        }
        // IF THE CURRENT DATE DONT HAVE SCHEDULE MEANS THIS DATE IS REST DAY AND ALREADY TIMEIN THIS MEANS THIS PUNCHIN BELONG TO YESTERDAY SCHED
        else if($check_type != "time in" && $date_last_time != $barack_date_trap_exact_date){

            $barack_date_trap_exact_date                                            = date('Y-m-d',strtotime($barack_date_trap_exact_date ."-1 day"));
        }

        // FOR PRESHIFT (it requires that the check type should time in)
        if($pre_shift && $check_type == "time in"){
            $barack_date_trap_exact_date                                            = date('Y-m-d',strtotime($barack_date_trap_exact_date ."+1 day"));
        }

        $barack_date_trap_exact_n_date                                              = date('Y-m-d',strtotime($barack_date_trap_exact_date ."+1 day"));
        $global_rest_day                                                            = false;
        $global_rest_n_day                                                          = false;
        $rsched                                                                     = array();
        $rsched_next                                                                = array();
        $ws                                                                         = array(
                                                                                    'work_type_name',
                                                                                    );
            
        $current_date                                                               = $barack_date_trap_exact_t_date;
        $kkey                                                                       = konsum_key();
        $r                                                                          = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);

        if($r){

            $rsched                                                                 = $this->check_shift_correct_sched($work_schedule_id,$r->work_type_name,$barack_date_trap_exact_date,$get_all_regular_schedule);
            if($rsched){

                $add_oneday_timein                                                  = date("Y-m-d",strtotime($barack_date_trap_exact_date." +1 day"))." ".$rsched->work_start_time;
            }
        }

        // check current next day
        $rnxt                                                                       = in_array_custom("emp_id_{$emp_id}_{$barack_date_trap_exact_n_date}",$emp_work_schedule_ess);
        
        if($rnxt){
            $worksched                                                              = in_array_custom("worksched_id_{$rnxt->work_schedule_id}",$get_work_schedule);

            $rsched_next                                                            = $this->check_shift_correct_sched($rnxt->work_schedule_id,$worksched->work_type_name,$barack_date_trap_exact_n_date,$get_all_regular_schedule);
        }else{

            $rnxt                                                                   = in_array_custom("emp_id_{$emp_id}",$emp_work_schedule_epi);

            if($rnxt){

                $worksched                                                          = in_array_custom("worksched_id_{$rnxt->work_schedule_id}",$get_work_schedule);
                $rsched_next                                                        = $this->check_shift_correct_sched($rnxt->work_schedule_id,$worksched->work_type_name,$barack_date_trap_exact_date,$get_all_regular_schedule);
            }
        }

        //flex break // check timeout ///

        if(!$rsched){
            $global_rest_day                                                        = true;
        }
        
        if(!$rsched_next){
            
            $rsched_next                                                            = array();
            $global_rest_n_day                                                      = true;
        }
        
        if($rsched_next){

            $next_date                                                              = $barack_date_trap_exact_n_date." ".$rsched_next->work_start_time;
            $barack_date_trap_exact_nt_date                                         = date("Y-m-d H:i:00",strtotime($next_date ."-120 minutes"));

            if(strtotime($barack_date_trap_exact_t_date) >= strtotime($barack_date_trap_exact_nt_date)){

                $rsched                                                             = $rsched_next;
                $global_rest_day                                                    = $global_rest_n_day;
                $barack_date_trap_exact_date                                        = $barack_date_trap_exact_n_date;
                $work_schedule_id                                                   = $rnxt->work_schedule_id;
            }
            
            $add_oneday_timein                                                      = date("Y-m-d",strtotime($barack_date_trap_exact_date." +1 day"))." ".$rsched_next->work_start_time;
        }
        
        $date                                                                       = $barack_date_trap_exact_date;
    
        // regular schedule
        if($rsched){

            if($rsched->work_start_time){

                $number_of_breaks_per_day                                           = $rsched->break_in_min;
                $shift_name                                                         = "regular schedule";
                $payroll_sched_timein                                               = date('Y-m-d H:i:s',strtotime($date." ".$rsched->work_start_time));
                $payroll_sched_timein_orig                                          = date('Y-m-d H:i:s',strtotime($date." ".$rsched->work_start_time));
                
                if($rsched->latest_time_in_allowed != NULL || $rsched->latest_time_in_allowed != ""){

                    $val                                                            = $rsched->latest_time_in_allowed;
                    $threshold_min                                                  = $rsched->latest_time_in_allowed;
                    $payroll_sched_timein                                           = date('Y-m-d H:i:s',strtotime($payroll_sched_timein  ." +{$val} minutes" ));

                }

                $current_date                                                       = $barack_date_trap_exact_t_date;
                $time_in                                                            = date("H:i:00",strtotime($current_date));
                $day                                                                = date("l",strtotime($barack_date_trap_exact_date));
                $regular_schedule_all                                               = in_array_custom("rsched_id_{$work_schedule_id}_{$day}",$get_all_regular_schedule);
                
            }
            else{

                // SPLIT SCHEDULE SETTINGS
                $currentdate                                                        = date('Y-m-d');
                $r_ws                                                               = in_array_foreach_custom("list_{$emp_id}_{$currentdate}_{$work_schedule_id}",$list_of_blocks);

                if($r_ws){


                    $read_first_split                                               = false;
                    
                    if($last_timein){
                        $query_split                                                = in_array_custom("emp_id_sched_id4_{$last_timein->employee_time_in_id}",$get_all_schedule_blocks_time_in);
                    
                        if($currentdate == $last_timein->date){

                            $new_employee_timein                                    = false;
                        }
                        else{

                            $new_employee_timein                                    = true;
                        }
                    }
                    else{

                        $new_employee_timein                                        = true;
                        $query_split                                                = array();
                    }

                    

                    $split_total_activate                                           = false;
                    
                    //get the schedule of split;
                    $split                                                          = $this->new_get_splitinfo($comp_id, $work_schedule_id,$emp_id,"","",$get_all_employee_timein,$list_of_blocks,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);

                    $this->type                                                     = "";

                    if($split){

                        $this->type                                                 = "split";
                        $number_of_breaks_per_day                                   = $split['break_in_min'];
                        $this->schedule_blocks_id                                   = $split['schedule_blocks_id'];
                        $check_type                                                 = $split['clock_type'];
                        $first_block_start_time                                     = $split['first_block_start_time'];
                        $shift_name                                                 = "split schedule";

                        if($query_split){

                            if($split['last_block'] == $query_split->schedule_blocks_id && $check_type == "time out"){

                                $split_total_activate                               = true;
                            }
                        }

                        if($sync_employee_time_in_id){
                            $split_last_timein                                      = in_array_custom("emp_id_sched_id4_{$sync_employee_time_in_id}",$get_all_schedule_blocks_time_in);
                        }
                        else{
                            $split_last_timein                                      = in_array_custom("emp_id_sched_id5_{$emp_id}",$get_all_schedule_blocks_time_in);
                        }

                        if(!$split_last_timein){
                            
                            $split_last_timein = (object) array(
                                'schedule_blocks_time_in_id'    => "",
                                'time_in'                       => "",
                                'time_out'                      => "",                      
                                'lunch_in'                      => "",
                                'lunch_out'                     => "",
                                'source'                        => "",
                                'employee_time_in_id'           => "",
                                );
                        }
                    }
                }else{
                    // FLEXIBLE HOURS

                    $r_fh                                                           = in_array_custom("flex_{$work_schedule_id}",$get_work_schedule_flex);
                    if($r_fh){
                        $number_of_breaks_per_day                                   = $r_fh->number_of_breaks_per_day;
                        $shift_name                                                 = "flexible hours"; 
                    }
                }
            }
        }
        else{
            // REST DAY 
            
            $rtrn = $this->rest_day_here($comp_id,$date,$current_date,$min_log,$emp_id,$check_type,$source,$comp_add,$sync_employee_time_in_id=false,$get_all_employee_timein,$location);
            
            /* Added Mobile 
             * Send approval for mobile clockin
             */
            $etiid = ($rtrn) ? $rtrn->employee_time_in_id : '';
            $pd = $current_date;
            $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
            $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
            $check_type, $work_schedule_id, $pd, "", $capture_img);
            /* added mobile */

            return $rtrn;
        }

        if($global_rest_day){
            // REST DAY         
            $rtrn = $this->rest_day_here($comp_id,$date,$current_date,$min_log,$emp_id,$check_type,$source,$comp_add,$sync_employee_time_in_id=false,$get_all_employee_timein,$location);
            
            /* Added Mobile 
             * Send approval for mobile clockin
             */
            $etiid = ($rtrn) ? $rtrn->employee_time_in_id : '';
            $pd = $current_date;
            $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
            $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
            $check_type, $work_schedule_id, $pd, "", $capture_img);
            /* added mobile */

            return $rtrn;
        }

        //check employee on leave
        $ileave                                                                     = 'no';
        $onleave                                                                    = $this->check_leave_appliction($date,$emp_id,$comp_id,$get_employee_leave_application,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_work_schedule,$get_all_regular_schedule,$get_work_schedule_flex,$get_all_schedule_blocks);

        if($onleave){
            $ileave                                                                 = 'yes';
        }

        // trigger execess 
        $excess_var                                                                 = false;

        // this is the new era of aditional breaks
        // regular compress night shift
        // check if migrate
        if($check_type == "time in"){
            $check_if_migrate                                                       = $this->check_if_migrate($work_schedule_id,$get_work_schedule);
        }
        else{
            
            if($last_timein->date != $barack_date_trap_exact_date){
                $check_type                                                         = "time in";
                $check_if_migrate                                                   = $this->check_if_migrate($work_schedule_id,$get_work_schedule);
            }
        }
        
        if($check_if_migrate){
            
            if($this->type !="split"){

                $r_last_time_in                                                 = $last_timein;

                // this is where >>> sa query na where pagkuha sa data 

                if($sync_employee_time_in_id !=""){

                    $where_get_data                                             = array(
                                                                                "eti.emp_id"                => $emp_id,
                                                                                "eti.comp_id"               => $comp_id,
                                                                                "eti.employee_time_in_id"   => $sync_employee_time_in_id,
                                                                                "eti.status"                => "Active"
                                                                                );
                }
                else{
                    if($r_last_time_in){
                        $where_get_data                                         = array(
                                                                                "eti.emp_id"                => $emp_id,
                                                                                "eti.comp_id"               => $comp_id,
                                                                                "eti.employee_time_in_id"   => $r_last_time_in->employee_time_in_id,
                                                                                "eti.status"                => "Active"
                                                                                );
                        $last_timein_source                                     = $r_last_time_in->source;
                    }else{

                        $where_get_data                                         = array(
                                                                                "eti.emp_id"                => $emp_id,
                                                                                "eti.comp_id"               => $comp_id,
                                                                                //"eti.employee_time_in_id" => $r_last_time_in->employee_time_in_id,
                                                                                "eti.status"                => "Active"
                                                                                );
                    }
                }
                
                // all ins mao ni gamiton para makita ang prev na logs nya if nag 5 mins na ba
                $t_in_arr                                                       = array();

                if($r_last_time_in){
                    $t_in_arr[]                                                 = $r_last_time_in->time_in;
                    $t_in_arr[]                                                 = $r_last_time_in->time_out;
                    $t_in_arr[]                                                 = $r_last_time_in->lunch_out;
                    $t_in_arr[]                                                 = $r_last_time_in->lunch_in;
                    $t_in_arr[]                                                 = $r_last_time_in->break1_out;
                    $t_in_arr[]                                                 = $r_last_time_in->break1_in;
                    $t_in_arr[]                                                 = $r_last_time_in->break2_out;
                    $t_in_arr[]                                                 = $r_last_time_in->break2_in;

                    $last_tardiness_min                                         = $r_last_time_in->tardiness_min;
                    $last_overbreak_min                                         = $r_last_time_in->overbreak_min;
                    $last_time_in                                               = $r_last_time_in->time_in;
                    $last_lunch_out                                             = $r_last_time_in->lunch_out;
                    $last_lunch_in                                              = $r_last_time_in->lunch_in;
                    $last_break1_out                                            = $r_last_time_in->break1_out;
                    $last_break1_in                                             = $r_last_time_in->break1_in;
                    $last_break2_out                                            = $r_last_time_in->break2_out;
                    $last_break2_in                                             = $r_last_time_in->break2_in;
                }
                
                sort($t_in_arr);
                $arr_def                                                        = end($t_in_arr);
                
                // samoka ayaw mo ug puno kapoy baya magcgeh huna2x!!
                // time in
                // 
                // HERE CHECK IF THIS FILTERED DATE GOT SOME LOGS
                $last_timein                                                    = in_array_custom("emp_id_timeins_date_{$emp_id}_{$barack_date_trap_exact_date}",$get_all_employee_timein);

                // checker if current date dont have logs
                if(!$last_timein && $check_type != "time in"){
                    // If wala ni logs then change check_type to time_in
                    $check_type                                                 = "time in";
                }

                // HERE DOUBLE LOGIN OR  EXCESS HOURS
                if($last_timein && $check_type == "time in"){
                    // flag excess
                    if(!$pre_shift){
                        $excess_var                                             = true;
                    }
                }
                if($last_timein){
                    if($last_timein->flag_regular_or_excess == "excess"){
                        if(!$pre_shift){
                            $excess_var                                         = true;
                            $check_type                                         = "time out";
                        }
                    }
                }

                //check if holiday
                $holiday_this_current                                           = in_array_custom("holiday_{$barack_date_trap_exact_date}",$company_holiday);
                
                $check_if_worksched_holiday                                     = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);

                if($check_if_worksched_holiday){
                    
                    $time_keep_holiday                                          = ($check_if_worksched_holiday->enable_breaks_on_holiday == 'yes') ? true : false;

                    if($time_keep_holiday){
                        $holiday_this_current                                   = false;
                    }
                }

                
                //if holiday and check type is not time in then time out
                if($check_type != "time in" && $holiday_this_current){
                    $check_type                                                 = "time out";
                }

                // DETERMINE TRESHOLD
                $current_t_day                                                  = date("l",strtotime($barack_date_trap_exact_date));
                $current_regular_schedule                                       = in_array_custom("rsched_id_{$work_schedule_id}_{$current_t_day}",$get_all_regular_schedule);
                $current_workscheds                                             = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);

                $current_regular_start_time                                     = "";
                $current_regular_end_time                                       = "";
                $current_tresh                                                  = "";
                $current_total_hours                                            = "";
                $current_break_in_min                                           = "";
                $current_break_1                                                = "";
                $current_break_2                                                = "";

                if($current_regular_schedule){
                    $current_regular_start_time                                 = $current_regular_schedule->work_start_time; 
                    $current_regular_end_time                                   = $current_regular_schedule->work_end_time;
                    $current_tresh                                              = $current_regular_schedule->latest_time_in_allowed;
                    $current_total_hours                                        = $current_regular_schedule->total_work_hours;
                    $current_break_in_min                                       = $current_regular_schedule->break_in_min;
                    $current_break_in_min                                       = number_format($current_break_in_min);
                    $current_break_1                                            = $current_regular_schedule->break_1;
                    $current_break_1                                            = number_format($current_break_1);
                    $current_break_2                                            = $current_regular_schedule->break_2;
                    $current_break_2                                            = number_format($current_break_2);
                }

                if($current_workscheds){
                    $enable_lunch_break                                         = $current_workscheds->enable_lunch_break;
                    $enable_additional_breaks                                   = $current_workscheds->enable_additional_breaks;
                    $track_break_1                                              = $current_workscheds->track_break_1;
                    $track_break_2                                              = $current_workscheds->track_break_2;
                    $num_of_additional_breaks                                   = $current_workscheds->num_of_additional_breaks;
                    $break_schedule_1                                           = $current_workscheds->break_schedule_1;
                    $break_started_after                                        = $current_workscheds->break_started_after;
                    $break_schedule_2                                           = $current_workscheds->break_schedule_2;
                    $additional_break_started_after_1                           = $current_workscheds->additional_break_started_after_1;
                    $additional_break_started_after_2                           = $current_workscheds->additional_break_started_after_2;
                    $tardiness_rule                                             = $current_workscheds->tardiness_rule;
                }
                
                $break_started_after                                            = number_format($break_started_after,0);
                $additional_break_started_after_1                               = number_format($additional_break_started_after_1,0);
                $additional_break_started_after_2                               = number_format($additional_break_started_after_2,0);

                $current_date                                                   = date("Y-m-d H:i:00",strtotime($current_date));
                $current_date_str_b                                             = strtotime($current_date);
                $current_regular_start_date_time                                = date("Y-m-d H:i:00",strtotime($barack_date_trap_exact_date." ".$current_regular_start_time));
                $current_regular_start_date_time_org                            = $current_regular_start_date_time;
                $current_regular_start_date_time_str_b                          = strtotime($current_regular_start_date_time);

                if($current_regular_start_time > $current_regular_end_time){
                    $barack_date_trap_exact_date_x                              = date("Y-m-d",strtotime($barack_date_trap_exact_date. " +1 day"));
                }else{
                    $barack_date_trap_exact_date_x                              = $barack_date_trap_exact_date;
                }
                $current_regular_end_date_time                                  = date("Y-m-d H:i:00",strtotime($barack_date_trap_exact_date_x." ".$current_regular_end_time));
                $current_regular_end_date_time_str_b                            = strtotime($current_regular_end_date_time);

                
                if($check_type == "time in"){

                    // new tardiness rules affect here
                    $late_min                                                   = 0;
                    $tardiness_min                                              = 0;

                    // CHECK IF TIME IN IS GREATER THAN THE ACTUAL REQUIRED TIME
                    if(false){ // remove computation for new cronjob
                    // if($current_date_str_b > $current_regular_start_date_time_str_b){

                        // CHECK IF TRESHOLD EXIST
                        if($current_tresh > 0){

                            $current_regular_start_date_time                    = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time." +".$current_tresh."minutes"));
                            $current_regular_start_date_time_str_b              = strtotime($current_regular_start_date_time);

                            // CHECK IF TIME IN IS GREATER THAN THE ACTUAL REQUIRED TIME PLUS THE THRESHOLD
                            if($current_date_str_b > $current_regular_start_date_time_str_b){

                                $late_min                                       = $this->total_hours_worked($current_date,$current_regular_start_date_time);
                            }
                        }else{

                            $late_min                                           = $this->total_hours_worked($current_date,$current_regular_start_date_time);
                        }
                        
                    }

                    // check if there is late min
                    if($late_min > 0){

                        // check tardiness rule daily, accumulated, rounding
                        $tardy_type                                             = in_array_custom("tardi_type_{$tardiness_rule}",$get_tardiness_settingsv2);
                        
                        if($tardy_type){

                            $tarmin                                             = $tardy_type->tarmin;

                            if($tardiness_rule == "daily"){

                                if($tardy_type->starts_shift_start_time == "yes"){

                                    if($tarmin >= $late_min){
                                        $late_min                               = 0;
                                    }
                                }

                                if($tardy_type->starts_end_of_grace_period == "yes"){

                                    $late_min                                   = $late_min - $tarmin;
                                    $late_min                                   = ($late_min > 0) ? $late_min : 0;
                                }
                            }
                            else if($tardiness_rule == "accumulated"){
                                // late will still be late on paycheck side
                            }
                            else if($tardiness_rule == "rounding"){
                                // rounding
                                if($tardiness_rounding){

                                    foreach ($tardiness_rounding as $key => $value) {

                                        if($value->from <= $late_min && $value->to >= $late_min){

                                            $minutes_should                     = $value->minutes_should;

                                            if($minutes_should == "Round Down"){

                                                $late_min                       = $value->minutes;
                                                break;
                                            }
                                            else if($minutes_should == "Round Up"){

                                                $late_min                       = $value->minutes;
                                                break;
                                            }
                                            else if($minutes_should == "Nearest"){

                                                $middle_min                     = (($value->to - $value->from) / 2) + $value->from; 

                                                if($late_min <= $middle_min){

                                                    $late_min                   = $value->from;
                                                }
                                                else if($late_min > $middle_min){

                                                    $late_min                   = $value->to;
                                                }
                                                
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    // remove computation for new cronjob
                    // $late_min                                                   = (($current_total_hours * 60) < $late_min) ? ($current_total_hours * 60) : $late_min;
                    $tardiness_min                                              = $late_min;

                    // holiday timein
                    if($holiday_this_current){

                        $late_min                                               = 0;
                        $tardiness_min                                          = 0;
                    }

                    if($excess_var){
                        $data_insert                                            = array(
                                                                                "comp_id"                   => $comp_id,
                                                                                "emp_id"                    => $emp_id,
                                                                                "date"                      => $date ,
                                                                                //"source"                    => $source."-".$check_type,
                                                                                "source"                    => "mobile",
                                                                                "time_in"                   => $current_date,
                                                                                "late_min"                  => 0,
                                                                                "tardiness_min"             => 0,
                                                                                "work_schedule_id"          => "-2",
                                                                                "total_hours"               => 0,
                                                                                "flag_regular_or_excess"    => "excess",
                                                                                "time_in_status"            => 'pending',
                                                                                "mobile_clockin_status"     => 'pending',
                                                                                "corrected"                 => 'Yes',
                                                                                "location_1"                => $location,
                                                                                "location"                  => $locloc,
                                                                                "flag_new_time_keeping"     => "1" // flag new cronjob
                                                                                );
                    }
                    else{
                        $data_insert                                            = array(
                                                                                "comp_id"                   => $comp_id,
                                                                                "emp_id"                    => $emp_id,
                                                                                "date"                      => $date ,
                                                                                // "source"                    => $source."-".$check_type,
                                                                                "source"                    => "mobile",
                                                                                "time_in"                   => $current_date,
                                                                                "late_min"                  => $late_min,
                                                                                "tardiness_min"             => $tardiness_min,
                                                                                "work_schedule_id"          => $work_schedule_id,
                                                                                "total_hours"               => $current_total_hours,
                                                                                "time_in_status"            => 'pending',
                                                                                "mobile_clockin_status"     => 'pending',
                                                                                "corrected"                 => 'Yes',
                                                                                "location_1"                => $location,
                                                                                "location"                  => $locloc,
                                                                                "flag_new_time_keeping"     => "1" // flag new cronjob
                                                                                );
                    }
                    
                    $this->db->insert('employee_time_in', $data_insert);
                    $timein_id                                                  = $this->db->insert_id();
                    $where_get_data["eti.employee_time_in_id"]                  = $timein_id;

                    // after insert kuhaa na sad well that's life
                    $this->db->where($where_get_data);
                    $q2                                                         = $this->db->get("employee_time_in AS eti",1,0);

                    /* Added Mobile 
                     * Send approval for mobile clockin
                     */
                    $q_row = $q2->row();
                    $pd = ($q_row) ? ($q_row->time_in) : $current_date;
                    $this->send_approvals($emp_id, $comp_id, $timein_id, $fullname, $approver_id, 
                    $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                    "time_in", $work_schedule_id, $pd, "", $capture_img);
                    /* added mobile */

                    return ($q_row) ? $q_row : FALSE ;
                }
                // lunch out
                else if($check_type == "lunch out"){
                    
                    // update lunch out value ============ >>>> UPDATE LUNCH OUT VALUE
                    $get_diff                                                   = (strtotime($current_date) - strtotime($arr_def)) / 60;
                    
                    if($min_log < $get_diff){

                        $update_val                                             = array(
                                                                                "lunch_out"             => $current_date,
                                                                                // "source"                => $last_timein_source.",".$source."-".$check_type,
                                                                                "source"                => "mobile",
                                                                                "time_in_status"        => 'pending',
                                                                                "mobile_lunchout_status"=> 'pending',
                                                                                "corrected"             => 'Yes',
                                                                                "location_2"            => $location,
                                                                                "location"              => $locloc,
                                                                                "flag_new_time_keeping" => "1" // flag new cronjob
                                                                                );
                        $this->db->where($where_get_data);
                        $update                                                 = $this->db->update("employee_time_in AS eti",$update_val);
                    }else{
                        return FALSE ;
                    }

                    $this->db->where($where_get_data);
                    $q2                                                         = $this->db->get("employee_time_in AS eti",1,0);

                    /* Added Mobile 
                     * Send approval for mobile clockin
                     */
                    $qr = $q2->row();
                    $etiid = ($qr) ? $qr->employee_time_in_id : '';
                    $pd = ($qr) ? $qr->lunch_out : $current_date;
                    $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                    $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                    "lunch_out", $work_schedule_id, $pd, "", $capture_img);
                    /* added mobile */

                    return ($qr) ? $qr : FALSE ;
                }
                else if($check_type == "lunch in"){

                    // update lunch out value ============ >>>> UPDATE LUNCH OUT VALUE

                    $break_started_after_min                                    = $break_started_after * 60;
                    $break_started_after_min                                    = number_format($break_started_after_min,0);
                    $time_break                                                 = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time_org." +".$break_started_after_min." minutes"));
                    $time_break_end                                             = date("Y-m-d H:i:00",strtotime($time_break." +".$current_break_in_min." minutes"));


                    $get_diff                                                   = (strtotime($current_date) - strtotime($arr_def)) / 60;
                    
                    if($min_log < $get_diff){

                        // OVERBREAK MIN
                        // remove computation for new cronjob
                        // $break_min                                              = 0;
                        // if($break_schedule_1 == "fixed"){
                        //     if($time_break_end < $current_date){
                        //         $break_min                                          = $this->total_hours_worked($current_date,$time_break_end);
                        //     }
                        // }
                        // else{
                        //     $break_minx = $get_diff;
                        //     if($break_minx > $current_break_in_min){
                        //         $break_min = $break_minx - $current_break_in_min;
                        //     }
                        // }
                        
                        $over_break_min                                         = 0;
                        $tardiness_min                                          = 0;
                        // remove computation for new cronjob
                        $last_overbreak_min                                     = 0; // ($last_overbreak_min > 0) ? $last_overbreak_min : 0;
                        $last_tardiness_min                                     = 0; // ($last_tardiness_min > 0) ? $last_tardiness_min : 0;

                        $over_break_min                                         = 0; //$last_overbreak_min + $break_min;
                        $over_break_min                                         = 0; //($over_break_min > 0) ? $over_break_min : 0;
                        $tardiness_min                                          = 0; //$last_tardiness_min + $break_min;
                        $update_val                                             = array(
                                                                                "lunch_in"              => $current_date,
                                                                                "overbreak_min"         => $over_break_min,
                                                                                "tardiness_min"         => $tardiness_min,
                                                                                // "source"                => $last_timein_source.",".$source."-".$check_type,
                                                                                "source"                => "mobile",
                                                                                "time_in_status"        => 'pending',
                                                                                "mobile_lunchin_status" => 'pending',
                                                                                "corrected"             => 'Yes',
                                                                                "location_3"            => $location,
                                                                                "location"              => $locloc,
                                                                                "flag_new_time_keeping" => "1" // flag new cronjob
                                                                                );
                        
                        $this->db->where($where_get_data);
                        $update                                                 = $this->db->update("employee_time_in AS eti",$update_val);
                    }else{
                        return FALSE ; 
                    }

                    $this->db->where($where_get_data);
                    $q2                                                         = $this->db->get("employee_time_in AS eti",1,0);

                    /* Added Mobile 
                     * Send approval for mobile clockin
                     */
                    $qr = $q2->row();
                    $etiid = ($qr) ? $qr->employee_time_in_id : '';
                    $pd = ($qr) ? $qr->lunch_in : $current_date;
                    $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                    $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                    "lunch_in", $work_schedule_id, $pd, "", $capture_img);
                    /* added mobile */

                    return ($qr) ? $qr : FALSE ;
                }
                else if($check_type == "break1 out"){

                    // update break1 out value ============ >>>> 
                    $get_diff                                                   = (strtotime($current_date) - strtotime($arr_def)) / 60;
                    
                    if($min_log < $get_diff){

                        $update_val                                             = array(
                                                                                "break1_out"    => $current_date,
                                                                                // "source"        => $last_timein_source.",".$source."-".$check_type,
                                                                                "source"                    => "mobile",
                                                                                "time_in_status"            => 'pending',
                                                                                "mobile_break1_out_status"  => 'pending',
                                                                                "corrected"                 => 'Yes',
                                                                                "location_5"                => $location,
                                                                                "location"                  => $locloc,
                                                                                "flag_new_time_keeping"     => "1" // flag new cronjob
                                                                                );
                        $this->db->where($where_get_data);
                        $update                                                 = $this->db->update("employee_time_in AS eti",$update_val);
                    }else{
                        return FALSE ;
                    }

                    $this->db->where($where_get_data);
                    $q2                                                         = $this->db->get("employee_time_in AS eti",1,0);

                    /* Added Mobile 
                     * Send approval for mobile clockin
                     */
                    $qr = $q2->row();
                    $etiid = ($qr) ? $qr->employee_time_in_id : '';
                    $pd = ($qr) ? $qr->break1_out : $current_date;
                    $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                    $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                    "break1_out", $work_schedule_id, $pd, "", $capture_img);
                    /* added mobile */

                    return ($qr) ? $qr : FALSE ;
                }
                else if($check_type == "break1 in"){

                    // update break1 in value ============ >>>>

                    $additional_break_started_after_1_min                       = $additional_break_started_after_1 * 60;
                    $additional_break_started_after_1_min                       = number_format($additional_break_started_after_1_min,0);
                    $time_break                                                 = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time_org." +".$additional_break_started_after_1_min." minutes"));
                    $time_break_end                                             = date("Y-m-d H:i:00",strtotime($time_break." +".$current_break_1." minutes"));
                    
                    $get_diff                                                   = (strtotime($current_date) - strtotime($arr_def)) / 60;
                    
                    if($min_log < $get_diff){

                        // OVERBREAK MIN
                        // remove computation for new cronjob
                        $break_min                                              = 0;
                        // if($break_schedule_1 == "fixed"){

                        //     if($time_break_end < $current_date){
                        //         $break_min                                      = $this->total_hours_worked($current_date,$time_break_end);
                        //     }
                        // }
                        // else{
                        //     $break_minx = $get_diff;
                        //     if($break_minx > $current_break_1){
                        //         $break_min = $break_minx - $current_break_1;
                        //     }
                        // }
                        

                        $over_break_min                                         = 0;
                        $tardiness_min                                          = 0;
                        $last_overbreak_min                                     = 0; // ($last_overbreak_min > 0) ? $last_overbreak_min : 0;
                        $last_tardiness_min                                     = 0; // ($last_tardiness_min > 0) ? $last_tardiness_min : 0;

                        $over_break_min                                         = 0; // $last_overbreak_min + $break_min;
                        $over_break_min                                         = 0; // ($over_break_min > 0) ? $over_break_min : 0;
                        $tardiness_min                                          = 0; // $last_tardiness_min + $break_min;

                        $update_val                                             = array(
                                                                                "break1_in"             => $current_date,
                                                                                "overbreak_min"         => $over_break_min,
                                                                                "tardiness_min"         => $tardiness_min,
                                                                                // "source"                => $last_timein_source.",".$source."-".$check_type,
                                                                                "source"                    => "mobile",
                                                                                "time_in_status"            => 'pending',
                                                                                "mobile_break1_in_status"   => 'pending',
                                                                                "corrected"                 => 'Yes',
                                                                                "location_6"                => $location,
                                                                                "location"                  => $locloc,
                                                                                "flag_new_time_keeping"     => "1" // flag new cronjob
                                                                                );
                        
                        $this->db->where($where_get_data);
                        $update                                                 = $this->db->update("employee_time_in AS eti",$update_val);
                    }else{
                        return FALSE ;
                    }

                    $this->db->where($where_get_data);
                    $q2                                                         = $this->db->get("employee_time_in AS eti",1,0);

                    /* Added Mobile 
                     * Send approval for mobile clockin
                     */
                    $qr = $q2->row();
                    $etiid = ($qr) ? $qr->employee_time_in_id : '';
                    $pd = ($qr) ? $qr->break1_in : $current_date;
                    $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                    $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                    "break1_in", $work_schedule_id, $pd, "", $capture_img);
                    /* added mobile */

                    return ($qr) ? $qr : FALSE ;
                }
                else if($check_type == "break2 out"){

                    // update break2 out value ============ >>>>
                    $get_diff                                                   = (strtotime($current_date) - strtotime($arr_def)) / 60;
                    
                    if($min_log < $get_diff){

                        $update_val                                             = array(
                                                                                "break2_out"            => $current_date,
                                                                                // "source"                => $last_timein_source.",".$source."-".$check_type,
                                                                                "source"                    => "mobile",
                                                                                "time_in_status"            => 'pending',
                                                                                "mobile_break2_out_status"  => 'pending',
                                                                                "corrected"                 => 'Yes',
                                                                                "location_7"                => $location,
                                                                                "location"                  => $locloc,
                                                                                "flag_new_time_keeping"     => "1" // flag new cronjob
                                                                                );
                        $this->db->where($where_get_data);
                        $update                                                 = $this->db->update("employee_time_in AS eti",$update_val);
                    }else{
                        return FALSE ;
                    }

                    $this->db->where($where_get_data);
                    $q2                                                         = $this->db->get("employee_time_in AS eti",1,0);

                    /* Added Mobile 
                     * Send approval for mobile clockin
                     */
                    $qr = $q2->row();
                    $etiid = ($qr) ? $qr->employee_time_in_id : '';
                    $pd = ($qr) ? $qr->break2_out : $current_date;
                    $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                    $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                    "break2_out", $work_schedule_id, $pd, "", $capture_img);
                    /* added mobile */

                    return ($qr) ? $qr : FALSE ;
                }
                else if($check_type == "break2 in"){    

                    // update break2 in value ============ >>>>

                    $additional_break_started_after_2_min                       = $additional_break_started_after_2 * 60;
                    $additional_break_started_after_2_min                       = number_format($additional_break_started_after_2_min,0);
                    $time_break                                                 = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time_org." +".$additional_break_started_after_2_min." minutes"));
                    $time_break_end                                             = date("Y-m-d H:i:00",strtotime($time_break." +".$current_break_2." minutes"));

                    $get_diff                                                   = (strtotime($current_date) - strtotime($arr_def)) / 60;
                    
                    if($min_log < $get_diff){

                        // OVERBREAK MIN
                        // remove computation for new cronjob
                        $break_min                                              = 0;

                        // if($break_schedule_1 == "fixed"){

                        //     if($time_break_end < $current_date){
                        //         $break_min                                          = $this->total_hours_worked($current_date,$time_break_end);
                        //     }
                        // }
                        // else{
                        //     $break_minx = $get_diff;
                        //     if($break_minx > $current_break_2){
                        //         $break_min = $break_minx - $current_break_2;
                        //     }
                        // }

                        $over_break_min                                         = 0;
                        $tardiness_min                                          = 0;
                        $last_overbreak_min                                     = 0; // ($last_overbreak_min > 0) ? $last_overbreak_min : 0;
                        $last_tardiness_min                                     = 0; // ($last_tardiness_min > 0) ? $last_tardiness_min : 0;

                        $over_break_min                                         = 0; // $last_overbreak_min + $break_min;
                        $over_break_min                                         = 0; // ($over_break_min > 0) ? $over_break_min : 0;
                        $tardiness_min                                          = 0; // $last_tardiness_min + $break_min;

                        $update_val                                             = array(
                                                                                "break2_in"             => $current_date,
                                                                                "overbreak_min"         => $over_break_min,
                                                                                "tardiness_min"         => $tardiness_min,
                                                                                // "source"                => $last_timein_source.",".$source."-".$check_type,
                                                                                "source"                    => "mobile",
                                                                                "time_in_status"            => 'pending',
                                                                                "mobile_break2_in_status"   => 'pending',
                                                                                "corrected"                 => 'Yes',
                                                                                "location_8"                => $location,
                                                                                "location"                  => $locloc,
                                                                                "flag_new_time_keeping"     => "1" // flag new cronjob
                                                                                );

                        $this->db->where($where_get_data);
                        $update                                                 = $this->db->update("employee_time_in AS eti",$update_val);
                    }else{
                        return FALSE ;
                    }

                    $this->db->where($where_get_data);
                    $q2                                                         = $this->db->get("employee_time_in AS eti",1,0);

                    /* Added Mobile 
                     * Send approval for mobile clockin
                     */
                    $qr = $q2->row();
                    $etiid = ($qr) ? $qr->employee_time_in_id : '';
                    $pd = ($qr) ? $qr->break2_in : $current_date;
                    $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                    $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                    "break2_in", $work_schedule_id, $pd, "", $capture_img);
                    /* added mobile */

                    return ($qr) ? $qr : FALSE ;
                }
                else if($check_type == "time out"){

                    // update time out value ============ >>>> 
                    $get_diff                                                   = (strtotime($current_date) - strtotime($arr_def)) / 60;
                    
                    if($min_log < $get_diff){

                        // TO GET UNDERTIME
                        $undertime_min                                          = 0;
                        // CHECK IF TIME IN IS GREATER THAN THE ACTUAL REQUIRED TIME

                        // last timein
                        // init end time
                        $current_reg_end_date_time                              = date("Y-m-d H:i:00",strtotime($current_regular_end_date_time));
                        // last timein str
                        $last_time_in_str                                       = strtotime($last_time_in);

                        // remove computation for new cronjob
                        /*if($last_time_in_str > $current_regular_start_date_time_str_b){

                            // CHECK IF TRESHOLD EXIST
                            if($current_tresh > 0){

                                // start time with tresh
                                $current_reg_start_date_time                    = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time." +".$current_tresh."minutes"));
                                $current_reg_start_date_time_str_b              = strtotime($current_reg_start_date_time);

                                // end time with tresh
                                $current_reg_end_date_time                      = date("Y-m-d H:i:00",strtotime($current_regular_end_date_time." +".$current_tresh."minutes"));
                                
                                // CHECK IF TIME IN IS LESS THAN THE ACTUAL REQUIRED TIME PLUS THE THRESHOLD
                                if($last_time_in_str < $current_reg_start_date_time_str_b){

                                    // GET THE DIFFERENCE BETWEEN THE ORIGINAL START TIME AND THE TIME IN TO GET THE THRESHOLD USED
                                    $current_timein_tresh                       = $this->total_hours_worked($last_time_in,$current_regular_start_date_time);
                                    $current_reg_end_date_time                  = date("Y-m-d H:i:00",strtotime($current_regular_end_date_time." +".$current_timein_tresh."minutes"));
                                }
                            }
                        }*/

                        $current_reg_end_date_time_str_b                        = strtotime($current_reg_end_date_time);

                        // CHECK IF THE WORKSCHED END TIME IS GREATER THAN THE TIME OUT TIME
                        // remove computation for new cronjob
                        /*if($current_reg_end_date_time_str_b > $current_date_str_b){

                            // GET UNDERTIME MIN
                            $undertime_min                                      = $this->total_hours_worked($current_reg_end_date_time,$current_date);
                            $undertime_min                                      = ($undertime_min > 0) ? $undertime_min : 0;
                        }*/

                        // GET THE TOTAL HOURS WORKED BY THE EMPLOYEE
                        $total_hours_required                                   = $this->total_hours_worked($current_date,$last_time_in);

                        $t_break_type_1                                         = "";
                        $t_break_type_2                                         = "";
                        $enable_lunch                                           = "";
                        $enable_add_break                                       = "";
                        $lunch_track_break                                      = "";
                        $add_track_break                                        = "";
                        $lunch_break_after                                      = "";
                        $add_break_after_1                                      = "";
                        $add_break_after_2                                      = "";

                        if($current_workscheds){
                            $t_break_type_1                                     = $current_workscheds->break_type_1;
                            $t_break_type_2                                     = $current_workscheds->break_type_2;
                            $enable_lunch                                       = $current_workscheds->enable_lunch_break;
                            $enable_add_break                                   = $current_workscheds->enable_additional_breaks;
                            $lunch_track_break                                  = $current_workscheds->track_break_1;
                            $add_track_break                                    = $current_workscheds->track_break_2;
                            $lunch_break_after                                  = $current_workscheds->break_started_after;
                            $add_break_after_1                                  = $current_workscheds->additional_break_started_after_1;
                            $add_break_after_2                                  = $current_workscheds->additional_break_started_after_2;
                            $lunch_break_sched                                  = $current_workscheds->break_schedule_1;
                            $add_break_sched                                    = $current_workscheds->break_schedule_2;
                        }
                        // lunch break
                        $deduct_flex_break_track                                = 0;
                        $flag_track_no                                          = false;

                        if($enable_lunch == "yes"){

                            // CHECK IF UNPAID
                            if($t_break_type_1 == "unpaid"){
                                if($current_break_in_min > 0){
                                    if($lunch_track_break == "yes"){
                                        // CHECK IF LUNCH IS EMPTY
                                        if(($last_lunch_out == "" || $last_lunch_out == NULL) && ($last_lunch_in == "" || $last_lunch_in == NULL)){

                                            if($lunch_break_sched == "fixed"){
                                                // FIXED BREAK
                                                $lunch_break_after_min          = $lunch_break_after * 60;
                                                $lunch_break_after_min          = number_format($lunch_break_after_min,0);
                                                $lunch_start_end                = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time." +".$lunch_break_after_min."minutes"));

                                                if($lunch_start_end <= $current_date){

                                                    $total_hours_required       = $total_hours_required - $current_break_in_min;
                                                }
                                            }
                                            else{
                                                // FLEXI BREAK
                                                $deduct_flex_break_track        = $deduct_flex_break_track + $current_break_in_min;
                                            }
                                        }else{

                                            $total_hours_required               = $total_hours_required - $current_break_in_min;
                                        }
                                    }else{
                                        // TRACK BREAK NO
                                        $deduct_flex_break_track                = $deduct_flex_break_track + $current_break_in_min;
                                        $flag_track_no                          = true;
                                    }
                                }
                            }
                        }

                        if($enable_add_break == "yes"){
                            // CHECK IF UNPAID
                            if($t_break_type_2 == "unpaid"){
                                if($add_track_break == "yes"){
                                    if($lunch_break_sched == "fixed"){
                                        // FIXED BREAK
                                        // CHECK IF BREAK1 IS EMPTY
                                        if(($last_break1_out == "" || $last_break1_out == NULL) && ($last_break1_in == "" || $last_break1_in == NULL)){
                                            if($add_break_after_1 > 0){

                                                $add_break_after_1_min          = $add_break_after_1 * 60;
                                                $add_break_after_1_min          = number_format($add_break_after_1_min,0);
                                                $add1_start_end                 = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time." +".$add_break_after_1_min."hour"));

                                                if($add1_start_end <= $current_date){

                                                    $total_hours_required       = $total_hours_required - $current_break_1;
                                                }
                                            }
                                        }
                                        else{

                                            $total_hours_required               = $total_hours_required - $current_break_1;
                                        }

                                        // CHECK IF BREAK2 IS EMPTY
                                        if(($last_break2_out == "" || $last_break2_out == NULL) && ($last_break2_in == "" || $last_break2_in == NULL)){
                                            if($add_break_after_2 > 0){

                                                $add_break_after_2_min          = $add_break_after_2 * 60;
                                                $add_break_after_2_min          = number_format($add_break_after_2_min,0);
                                                $add2_start_end                 = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time." +".$add_break_after_2_min."minutes"));

                                                if($add2_start_end <= $current_date){

                                                    $total_hours_required       = $total_hours_required - $current_break_2;
                                                }
                                            }
                                        }
                                        else{

                                            $total_hours_required               = $total_hours_required - $current_break_2;
                                        }
                                    }
                                    else{
                                        // FLEXI BREAK
                                        $deduct_flex_break_track                = $deduct_flex_break_track + $current_break_1;
                                        $deduct_flex_break_track                = $deduct_flex_break_track + $current_break_2;
                                    }
                                }else{
                                    // TRACK BREAK NO
                                    $deduct_flex_break_track                    = $deduct_flex_break_track + $current_break_1;
                                    $deduct_flex_break_track                    = $deduct_flex_break_track + $current_break_2;
                                    $flag_track_no                              = true;
                                }
                            }
                        }

                        $median_th                                              = (($current_total_hours * 60)/2) + $deduct_flex_break_track;
                        
                        if($total_hours_required > $median_th){
                            
                            $total_hours_required                               = $total_hours_required - $deduct_flex_break_track;
                        }
                        else{
                            // remove computation for new cronjob
                            $undertime_min                                      = 0; // $undertime_min - $deduct_flex_break_track;
                            $undertime_min                                      = 0; // ($undertime_min > 0) ? $undertime_min : 0;
                        }

                        // DEDUCT OVERBREAK
                        if($last_overbreak_min > 0){
                            $total_hours_required                               = $total_hours_required - $last_overbreak_min;
                        }

                        $total_hours_required                                   = ($total_hours_required > 0) ? $total_hours_required : 0;
                        $total_hours_required                                   = $this->convert_to_hours($total_hours_required);

                        // if holiday time out
                        if($holiday_this_current){
                            $undertime_min                                      = 0;
                            $total_hours_required                               = $this->total_hours_worked($current_date,$last_time_in);
                            $total_hours_required                               = ($total_hours_required > 0) ? $total_hours_required : 0;
                            $total_hours_required                               = $this->convert_to_hours($total_hours_required);
                            $current_total_hours                                = $total_hours_required;
                        }

                        if($excess_var){
                            // GET THE TOTAL HOURS WORKED BY THE EMPLOYEE
                            $total_hours_required                               = $this->total_hours_worked($current_date,$last_time_in);
                            $total_hours_required                               = ($total_hours_required > 0) ? $total_hours_required : 0;
                            $total_hours_required                               = $this->convert_to_hours($total_hours_required);

                            $update_val                                         = array(
                                                                                "time_out"              => $current_date,
                                                                                "undertime_min"         => 0,
                                                                                "total_hours_required"  => $total_hours_required,
                                                                                "total_hours"           => $total_hours_required,
                                                                                // "source"                => $last_timein_source.",".$source."-".$check_type,
                                                                                "source"                    => "mobile",
                                                                                "time_in_status"            => 'pending',
                                                                                "mobile_clockout_status"    => 'pending',
                                                                                "corrected"                 => 'Yes',
                                                                                "location_4"                => $location,
                                                                                "location"                  => $locloc,
                                                                                "flag_new_time_keeping"     => "1" // flag new cronjob
                                                                                );
                        }
                        else{
                            $update_val                                         = array(
                                                                                "time_out"              => $current_date,
                                                                                "undertime_min"         => $undertime_min,
                                                                                "total_hours_required"  => $total_hours_required,
                                                                                "total_hours"           => $current_total_hours,
                                                                                // "source"                => $last_timein_source.",".$source."-".$check_type,
                                                                                "source"                    => "mobile",
                                                                                "time_in_status"            => 'pending',
                                                                                "mobile_clockout_status"    => 'pending',
                                                                                "corrected"                 => 'Yes',
                                                                                "location_4"                => $location,
                                                                                "location"                  => $locloc,
                                                                                "flag_new_time_keeping"     => "1" // flag new cronjob
                                                                                );
                        }

                        $this->db->where($where_get_data);
                        $update                                                 = $this->db->update("employee_time_in AS eti",$update_val);

                    }else{
                        return FALSE ;
                    }

                    $this->db->where($where_get_data);
                    $q2                                                         = $this->db->get("employee_time_in AS eti",1,0);
                    
                    // athan helper
                    if($r_last_time_in){
                        $date = $r_last_time_in->date;
                        // payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
                    }

                    /* Added Mobile 
                     * Send approval for mobile clockin
                     */
                    $qr = $q2->row();
                    $etiid = ($qr) ? $qr->employee_time_in_id : '';
                    $pd = ($qr) ? $qr->time_out : $current_date;
                    $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                    $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                    "time_out", $work_schedule_id, $pd, "", $capture_img);
                    /* added mobile */

                    return ($qr) ? $qr : FALSE ;
                }
            }
            else{

                /**
                 * SPLIT SCHEDULE GENERATE
                 * EVERY DAY LOGIN
                 * SCHEDULE IS SPLIT INIT HERE THIS IS THE FIRST TIMEIN OCCUR AND SPLIT SCHED IS ALREADY IN DB
                 */
                
                if($this->type == 'split'){
                    
                    $get_diff                                                   = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
                    $arr                                                        = array(
                                                                                'emp_no'                => $emp_no,
                                                                                'current_date'          => $current_date,
                                                                                'emp_id'                => $emp_id,
                                                                                'comp_id'               => $comp_id,
                                                                                'breaks'                => $number_of_breaks_per_day,
                                                                                'check_type'            => $check_type,
                                                                                'min_log'               => $min_log,
                                                                                'get_diff'              => $get_diff,
                                                                                'employee_time_in_id'   => $split_last_timein->employee_time_in_id,
                                                                                'work_schedule_id'      => $work_schedule_id,
                                                                                'block_id'              => $split_last_timein->schedule_blocks_time_in_id,
                                                                                'schedule_blocks_id'    => $this->schedule_blocks_id,
                                                                                'time_in'               => $split_last_timein->time_in,
                                                                                'time_out'              => $split_last_timein->time_out,
                                                                                'lunch_in'              => $split_last_timein->lunch_in,
                                                                                'lunch_out'             => $split_last_timein->lunch_out,
                                                                                'l_source'              => $split_last_timein->source,
                                                                                'new_timein'            => "",
                                                                                'timein_id'             => $split_last_timein->employee_time_in_id,
                                                                                'new_employee_timein'   => $new_employee_timein,
                                                                                'enable_breaks_on_holiday'  => ($split) ? $split['enable_breaks_on_holiday'] : "",
                                                                                );

                    $rs = $this->split_schedule_time_in($arr,$split_total_activate,"",$date,$source,$first_block_start_time,$check_type_o,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$company_holiday,$get_work_schedule);
                    
                    /* Added Mobile 
                     * Send approval for mobile clockin
                     */
                    $etiid = ($rs) ? $rs->employee_time_in_id : '';
                    $etiid2 = ($rs) ? $rs->schedule_blocks_time_in_id : '';
                    $pd = $current_date;
                    $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                    $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                    $check_type, $work_schedule_id, $pd, $etiid2, $capture_img);
                    /* added mobile */

                    return $rs;
                }
            }
        }
        //// PREVIOUS CODE DOWN HERE, not maintained anymore
        else{
            // we are not maintaining the not migrated anymore
            return false;
            /* IF NOT MIGRATED */

            // if workschedule has no break
            if(($number_of_breaks_per_day == 0 || $number_of_breaks_per_day == NULL)){

                $r                                          = $last_timein;
                // if wala pa sa db maskiusa ka logs
                if(!$r){
                    /* CHECK TIME IN START */
                    $time_in                                = date('H:i:s');
                    $wst                                    = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$get_all_regular_schedule);

                    if($this->type !="split"){

                        if($wst != FALSE){
                            // new start time
                            $nwst                           = date("Y-m-d {$wst}");
                            $check_diff_total_hours         = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
                        }
                        
                        //late min for early tardiness
                        $late_min                           = $this->late_min($comp_id,$date,$emp_id,$work_schedule_id,"",$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);

                        // insert time in log
                        $val                                = array(
                                                            "emp_id"            => $emp_id,
                                                            "comp_id"           => $comp_id,
                                                            "date"              => $date,
                                                            "work_schedule_id"  => $work_schedule_id,
                                                            "time_in"           => $current_date,
                                                            "late_min"          => $late_min,
                                                            // "source"            => $source."-".$check_type,
                                                            "source"            => "mobile",
                                                            "flag_on_leave"     => $ileave,
                                                            "mobile_clockin_status" => 'pending',
                                                            "location_1"        => $location,
                                                            "location"          => $locloc,
                                                            "time_in_status"    => 'pending',
                                                            "corrected"         => 'Yes',
                                                            );

                        $insert                             = $this->db->insert("employee_time_in",$val);
                        
                        if($insert){
                            $w2                             = array(
                                                            "a.payroll_cloud_id"=> $emp_no,
                                                            "eti.date"          => $date,
                                                            "eti.status"        => "Active"
                                                            );
                            $this->edb->where($w2);
                            $this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
                            $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
                            $q2                             = $this->edb->get("employee_time_in AS eti",1,0);
            
                            return ($q2) ? $q2->row() : FALSE;
                        }
                    }
                    else{

                        $get_diff                           = 10;
                        $eti                                = "";
                        $rto                                = "";
                        $ro                                 = "";
                        $rl                                 = "";
                        $rt                                 = "";
                        $sbi                                = "";
                        $lsource                            = "";

                        if($r){
                            $get_diff                       = (strtotime($current_date) - strtotime($split_last_timein->lunch_in)) / 60;
                            $eti                            = $split_last_timein->employee_time_in_id;
                            $sbi                            = $split_last_timein->schedule_blocks_time_in_id;
                            $rt                             = $split_last_timein->time_in;
                            $rl                             = $split_last_timein->lunch_out;
                            $ro                             = $split_last_timein->lunch_in;
                            $rto                            = $split_last_timein->time_out;
                            $lsource                        = $split_last_timein->source;
                        }

                        $arr                                = array(
                                                            'emp_no'                => $emp_no,
                                                            'current_date'          => $current_date,
                                                            'emp_id'                => $emp_id,
                                                            'comp_id'               => $comp_id,
                                                            'breaks'                => $number_of_breaks_per_day,
                                                            'check_type'            => "time in",
                                                            'min_log'               => $min_log,
                                                            'get_diff'              => $get_diff,
                                                            'employee_time_in_id'   => $eti,
                                                            'work_schedule_id'      => $work_schedule_id,
                                                            'block_id'              => $sbi,
                                                            'schedule_blocks_id'    => $this->schedule_blocks_id,
                                                            'time_in'               => $rt,
                                                            'time_out'              => $rto,
                                                            'lunch_in'              => $ro,
                                                            'lunch_out'             => $rl,
                                                            'new_timein'            => "",
                                                            'l_source'              => $lsource,
                                                            'timein_id'             => $last_timein->employee_time_in_id,
                                                            'new_employee_timein'   => $new_employee_timein,
                                                            'enable_breaks_on_holiday'  => ($split) ? $split['enable_breaks_on_holiday'] : "",
                                                            );

                        $rt_sp = $this->split_schedule_time_in($arr,$split_total_activate,"",$date,$source,$first_block_start_time,$check_type_o,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$company_holiday,$get_work_schedule,$location);
                        
                        /* Added Mobile 
                         * Send approval for mobile clockin
                         */
                        $etiid = ($rt_sp) ? $rt_sp->employee_time_in_id : '';
                        $etiid2 = ($rt_sp) ? $rt_sp->schedule_blocks_time_in_id : '';
                        $pd = $current_date;
                        $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                        $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                        "time_in", $work_schedule_id, $pd, $etiid2);
                        /* added mobile */

                        return $rt_sp;

                    }
                }
                // if naa na sa db ang prev logs
                else{

                    $last_timein_source                     = $last_timein->source;

                    // get date time in to date time out
                    $workday                                = date("l",strtotime($date));
                    $payroll_group_id                       = 0;
        
                    // check rest day
                    $check_rest_day                         = in_array_custom("rest_day_{$work_schedule_id}_".$workday,$check_rest_days);

                    if($check_rest_day){
                        // global where update data
                        $where_update                       = array(
                                                            "eti.emp_id"                => $emp_id,
                                                            "eti.comp_id"               => $comp_id,
                                                            "eti.employee_time_in_id"   => $r->employee_time_in_id,
                                                            "eti.status"                => "Active"
                                                            );
                        
                        if($check_type == "time out"){
                            // update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE FOR REST DAY

                            $get_diff                       = (strtotime($current_date) - strtotime($r->time_in)) / 60;

                            if($min_log < $get_diff){

                                $update_val                 = array(
                                                                "time_out"=>$current_date,
                                                                "mobile_clockout_status" => 'pending',
                                                                "location_4" => $location,
                                                                "location"          => $locloc,
                                                                "time_in_status"    => 'pending',
                                                                "corrected"         => 'Yes',
                                                            );
                                $this->db->where($where_update);
                                $update                     = $this->db->update("employee_time_in AS eti",$update_val);
                            }
                            
                            $this->edb->where($where_update);
                            $this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
                            $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
                            $q2                             = $this->edb->get("employee_time_in AS eti",1,0);
                            
                            // update total hours and total hours required rest day
                            $get_total_hours                = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
                            $update_timein_logs             = array(
                                                            "tardiness_min"         => 0,
                                                            "undertime_min"         => 0,
                                                            "total_hours"           => $get_total_hours,
                                                            "total_hours_required"  => $get_total_hours,
                                                            // "source"                => $last_timein_source.",".$source."-".$check_type,
                                                            "source"                => "mobile",
                                                            );

                            $this->db->where($where_update);
                            $sql_update_timein_logs         = $this->db->update("employee_time_in AS eti",$update_timein_logs);
                            

                            // athan helper
                            if($last_timein){
                                $date = $last_timein->date;
                                // payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
                            }
                            return ($q2) ? $q2->row() : FALSE ;
                        }
                        else if($check_type == "time in"){
                            
                            /* CHECK TIME IN START */
                            $wst                            = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$get_all_regular_schedule);
                            if($wst != FALSE){
                                // new start time
                                $nwst                       = date("Y-m-d {$wst}");
                                $check_diff_total_hours     = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
                            }

                            // insert time in value for rest day ============================================== >>> INSERT TIME IN VALUE FOR REST DAY
                            $insert                         = FALSE;
                            $get_diff                       = (strtotime($current_date) - strtotime($r->time_out)) / 60;

                            if($min_log < $get_diff){
                                $val                        = array(
                                                            "emp_id"            => $emp_id,
                                                            "comp_id"           => $comp_id,
                                                            "date"              => $date,
                                                            "time_in"           => $current_date,
                                                            "work_schedule_id"  => -1,
                                                            // "source"            => $source,
                                                            "source"            => "mobile",
                                                            "location"          => $locloc,
                                                            "flag_on_leave"     => $ileave,
                                                            "mobile_clockin_status" => 'pending',
                                                            "location_1"        => $location,
                                                            "time_in_status"    => 'pending',
                                                            "corrected"         => 'Yes',
                                                            );
                                $insert                     = $this->db->insert("employee_time_in",$val);   
                            }
                            
                            if($insert){
                                $w2                         = array(
                                                            "a.payroll_cloud_id"=> $emp_no,
                                                            "eti.date"          => $date,
                                                            "eti.status"        => "Active"
                                                            );

                                $this->edb->where($w2);
                                $this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
                                $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
                                $this->db->order_by("eti.time_in","DESC");

                                $q2                         = $this->edb->get("employee_time_in AS eti",1,0);
                
                                return ($q2) ? $q2->row() : FALSE ;
                                exit;
                            }
                            else{

                                $this->edb->where($where_update);
                                $this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
                                $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");

                                $q2                         = $this->edb->get("employee_time_in AS eti",1,0);
                
                                return ($q2) ? $q2->row() : FALSE ;
                                exit;
                            }
                        }
                    }
                    

                    /**
                     * SPLIT SCHEDULE GENERATE
                     * EVERY DAY LOGIN
                     * SCHEDULE IS SPLIT INIT HERE THIS IS THE FIRST TIMEIN OCCUR AND SPLIT SCHED IS ALREADY IN DB
                     */
                    
                    if($this->type == 'split'){

                        $get_diff                           = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;

                        if(!$this->schedule_blocks_id && $split_last_timein){
                            $this->schedule_blocks_id       = $split_last_timein->schedule_blocks_id;
                        }

                        $arr                                = array(
                                                            'emp_no'                => $emp_no,
                                                            'current_date'          => $current_date,
                                                            'emp_id'                => $emp_id,
                                                            'comp_id'               => $comp_id,
                                                            'breaks'                => $number_of_breaks_per_day,
                                                            'check_type'            => ($split) ? $split['clock_type'] : $check_type,
                                                            'min_log'               => $min_log,
                                                            'get_diff'              => $get_diff,
                                                            'employee_time_in_id'   => $split_last_timein->employee_time_in_id,
                                                            'work_schedule_id'      => $work_schedule_id,
                                                            'block_id'              => $split_last_timein->schedule_blocks_time_in_id,
                                                            'schedule_blocks_id'    => $this->schedule_blocks_id,
                                                            'time_in'               => $split_last_timein->time_in,
                                                            'time_out'              => $split_last_timein->time_out,
                                                            'lunch_in'              => $split_last_timein->lunch_in,
                                                            'lunch_out'             => $split_last_timein->lunch_out,
                                                            'new_timein'            => "",//$new_timein,
                                                            'l_source'              => $split_last_timein->source,
                                                            'timein_id'             => $split_last_timein->employee_time_in_id,//$timein_id,
                                                            'new_employee_timein'   => $new_employee_timein,
                                                            'enable_breaks_on_holiday'  => ($split) ? $split['enable_breaks_on_holiday'] : "",
                                                            );
                        
                        $r_sp = $this->split_schedule_time_in($arr,$split_total_activate,"",$date,$source,$first_block_start_time,$check_type_o,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$company_holiday,$get_work_schedule, $location);
                        /* Added Mobile 
                         * Send approval for mobile clockin
                         */

                        $etiid = '';
                        $etiid2 = '';
                        if ($r_sp) {
                            $etiid = $r_sp->employee_time_in_id;
                            $etiid2 = $r_sp->schedule_blocks_time_in_id;
                        }

                        $pd = $current_date;
                        $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                        $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                        $check_type, $work_schedule_id, $pd, $etiid2);
                        /* added mobile */

                        return $r_sp;
                    }

                    // ## REGULAR SCHED SETTINGS ## // 

                    if($this->type != "split"){

                        $workday_settings_start_time        = $this->check_workday_settings_start_time($workday,$work_schedule_id,$get_all_regular_schedule);
                        $workday_settings_end_time          = $this->check_workday_settings_end_time($workday,$work_schedule_id,$get_all_regular_schedule);
                        
                        if(strtotime($workday_settings_start_time) > strtotime($workday_settings_end_time)){

                            // for night shift time in and time out value for working day
                            $check_bet_timein               = date("Y-m-d")." ".$workday_settings_start_time;
                            $check_bet_timeout              = date("Y-m-d",strtotime("+1 day"))." ".$workday_settings_end_time;
                        }
                        else{

                            // for day shift time in and time out value for working day
                            $check_bet_timein               = date("Y-m-d")." ".$workday_settings_start_time;
                            $check_bet_timeout              = date("Y-m-d")." ".$workday_settings_end_time;
                        }
                        
                        // check between date time in to date time out
                        $add_oneday_timein                  = date("Y-m-d",strtotime($r->time_in." +1 day"))." ".$workday_settings_start_time;
                    }

                    // ## REGULAR SCHED SETTINGS ## // 
                    $hours_worked                           = 0;
                    $break                                  = 0;
                    $tresh                                  = 0;

                    if($regular_schedule_all){
                        $hours_worked                       = $regular_schedule_all->total_work_hours;
                        $break                              = $regular_schedule_all->break_in_min;
                        $tresh                              = $regular_schedule_all->latest_time_in_allowed;
                    }

                    if(strtotime($check_bet_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($add_oneday_timein." -2 hours")){
                        
                        // global where update data
                        $where_update                       = array(
                                                            "eti.emp_id"                => $emp_id,
                                                            "e.comp_id"               => $comp_id,
                                                            "eti.employee_time_in_id"   => $r->employee_time_in_id,
                                                            "eti.status"                => "Active"
                                                            );
                        
                        if($check_type == "time out"){
                            
                            // update time out value for rest day =============== >>> UPDATE TIME OUT VALUE
                            $get_diff                       = (strtotime($current_date) - strtotime($r->time_in)) / 60;
                            
                            if($min_log < $get_diff){

                                $update_val                 = array(
                                                                "time_out"  => $current_date,
                                                                "location"  => $locloc,
                                                                "source"    => "mobile",
                                                            );
                                $this->db->where($where_update);
                                $update                     = $this->db->update("employee_time_in AS eti",$update_val);
                            }
                            
                            $this->edb->where($where_update);
                            $q2                             = $this->edb->get("employee_time_in AS eti",1,0);

                            // check tardiness value
                            $flag_tu                        = 0;
                            
                            
                            $get_total_hours_worked         = ($hours_worked / 2) + .5;
                            
                            // ## HOLIDAY HERE ## // 
                            $holiday                        = in_array_custom("holiday_{$date}",$company_holiday);

                            $check_if_worksched_holiday     = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);

                            if($check_if_worksched_holiday){
                                
                                $time_keep_holiday          = ($check_if_worksched_holiday->enable_breaks_on_holiday == 'yes') ? true : false;

                                if($time_keep_holiday){
                                    $holiday                = false;
                                }
                            }

                            if($holiday){
                                $get_diff                   = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
                                $arr                        = array(
                                                            'emp_no'                => $emp_no,
                                                            'breaks'                => $number_of_breaks_per_day,
                                                            'current_date'          => $current_date,
                                                            'date'                  => $date,
                                                            'emp_id'                => $emp_id,
                                                            'comp_id'               => $comp_id,
                                                            'check_type'            => $check_type,
                                                            // 'source'                => $source,
                                                            "source"                => "mobile",
                                                            'min_log'               => $min_log,
                                                            'get_diff'              => $get_diff,
                                                            'employee_time_in_id'   => $r->employee_time_in_id,
                                                            'work_schedule_id'      => $work_schedule_id,
                                                            'time_in'               => $r->time_in,
                                                            'time_out'              => $r->time_out,
                                                            'lunch_in'              => $r->lunch_in,
                                                            'lunch_out'             => $r->lunch_out,
                                                            'new_timein'            => $new_timein,
                                                            'timein_id'             => $timein_id,
                                                            'hours_worked'          => $hours_worked,
                                                            
                                                            );

                                return $this->holiday_time_in($arr);
                            }

                            // ## HOLIDAY END HERE ## // 
                            
                            $hw                             = $this->convert_to_min($hours_worked);
                            $new_total_hours                = $this->total_hours_worked($current_date, $r->time_in);

                            if($number_of_breaks_per_day > 0 && $r->lunch_out != null){
                                $new_total_hours            = $new_total_hours - $number_of_breaks_per_day;
                            }

                            $total_hours_worked             = $this->convert_to_hours($new_total_hours);

                            

                            // tardiness and undertime value
                            $update_tardiness               = $this->late_min($comp_id,$date,$emp_id,$work_schedule_id,$r->time_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);

                            $update_undertime               = $this->reg_sched_calc_under($get_all_regular_schedule,$get_work_schedule,$work_schedule_id,$date,$r->time_in,$current_date,$r->lunch_out);
                            
                            $update_timein_logs             = array(
                                                            "late_min"                  => $update_tardiness,
                                                            "tardiness_min"             => $update_tardiness,
                                                            "undertime_min"             => $update_undertime,                       
                                                            "total_hours"               => $hours_worked,
                                                            "total_hours_required"      => $total_hours_worked,
                                                            // "source"                    => $last_timein_source.",".$source."-".$check_type,
                                                            "source"                    => "mobile",
                                                            );

                            $att                                                = $this->calculate_attendance($comp_id,$r->time_in,$current_date,$attendance_hours);

                            if($att){
                                $total_hours_worked                             = $this->total_hours_worked($new_time_out, $new_time_in);
                                $total_hours_worked                             = $this->convert_to_hours($total_hours_worked);
                                $update_timein_logs['lunch_in']                 = null;
                                $update_timein_logs['lunch_out']                = null;
                                $update_timein_logs['total_hours_required']     = $total_hours_worked;
                                $update_timein_logs['absent_min']               = ($get_hoursworked - $total_hours_worked) * 60;
                                $update_timein_logs['late_min']                 = 0;
                                $update_timein_logs['tardiness_min']            = 0;
                                $update_timein_logs['undertime_min']            = 0;
                            }

                            $this->db->where($where_update);
                            $sql_update_timein_logs                             = $this->db->update("employee_time_in AS eti",$update_timein_logs);
                            

                            // athan helper
                            if($last_timein){
                                $date = $last_timein->date;
                                // payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
                            }
                            return ($q2) ? $q2->row() : FALSE ;
                            
                        }
                        else if($check_type == "time in"){
                            
                            $late_min                                           = $this->late_min($comp_id,$date,$emp_id,$work_schedule_id,"",$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);

                            // insert time in log ============================================= >>>> INSERT NEW TIME IN LOG SAME DATE
                            $insert                                             = FALSE;
                            $get_diff                                           = (strtotime($current_date) - strtotime($r->time_out)) / 60;
                            if($min_log < $get_diff){
                                $val                                            = array(
                                                                                "emp_id"            => $emp_id,
                                                                                "comp_id"           => $comp_id,
                                                                                "date"              => $date,
                                                                                "time_in"           => $current_date,
                                                                                "work_schedule_id"  => $work_schedule_id,
                                                                                // "source"            => $source."-".$check_type,
                                                                                "source"            => "mobile",
                                                                                'late_min'          => $late_min,
                                                                                "location"          => $locloc,
                                                                                "flag_on_leave"     => $ileave,
                                                                                "mobile_clockin_status" => 'pending',
                                                                                "location_1"        => $location,
                                                                                "time_in_status"    => 'pending',
                                                                                "corrected"         => 'Yes'
                                                                                );

                                $insert                                         = $this->db->insert("employee_time_in",$val);   
                            }
                            
                            if($insert){
                                $w2                                             = array(
                                                                                "a.payroll_cloud_id"=> $emp_no,
                                                                                "eti.date"          => $date,
                                                                                "eti.status"        => "Active"
                                                                                );
                                $w3                                             = array(
                                                                                "eti.emp_id"        => $emp_id,
                                                                                "eti.date"          => $date,
                                                                                "eti.status"        => "Active"
                                                                                );
                                $this->edb->where($w3);
                                $this->db->order_by("eti.time_in","DESC");

                                $q2                                             = $this->edb->get("employee_time_in AS eti",1,0);
                 
                                return ($q2) ? $q2->row() : FALSE ;
                            }else{
                                $this->edb->where($where_update);
                                $this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
                                $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");

                                $q2                                             = $this->edb->get("employee_time_in AS eti",1,0);
                                return ($q2) ? $q2->row() : FALSE ;
                            }
                        }
                    }
                    else{

                        // global where update data
                        $where_update                                           = array(
                                                                                "eti.emp_id"                => $emp_id,
                                                                                "eti.comp_id"               => $comp_id,
                                                                                "eti.employee_time_in_id"   => $r->employee_time_in_id
                                                                                );
                        
                        
                        if($check_type == "time out"){
                            // update time out value ============================================== >>> UPDATE TIME OUT VALUE
                            $get_diff                                           = (strtotime($current_date) - strtotime($r->time_in)) / 60;

                            if($min_log < $get_diff){

                                $update_val                                     = array(
                                                                                "time_out"  => $current_date,
                                                                                // "source"    => $last_timein_source.",".$source."-".$check_type,
                                                                                "source"    => "mobile",
                                                                                );
                                $this->db->where($where_update);
                                $update                                         = $this->db->update("employee_time_in AS eti",$update_val);
                            }
                            
                            $this->edb->where($where_update);
                            $q2                                                 = $this->edb->get("employee_time_in AS eti",1,0);
            
                            // update total hours and total hours required rest day
                            $get_total_hours                                    = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
                            
                            // tardiness and undertime value
                        
                            $update_tardiness                                   = 0;//$this->late_min($comp_id,$r->date,$emp_id,$work_schedule_id,"",$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);
                            $update_undertime                                   = $this->reg_sched_calc_under($get_all_regular_schedule,$get_work_schedule,$work_schedule_id,$date,$r->time_in,$current_date,$r->lunch_out);
                            
                            // check tardiness value
                            $flag_tu                                            = 0;
                            
                            $get_total_hours_worked                             = ($hours_worked / 2) + .5;
                            
                            // ## HOLIDAY HERE ## // 
                            $holiday                                            = in_array_custom("holiday_{$date}",$company_holiday);

                            $check_if_worksched_holiday                         = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);

                            if($check_if_worksched_holiday){
                                
                                $time_keep_holiday                              = ($check_if_worksched_holiday->enable_breaks_on_holiday == 'yes') ? true : false;

                                if($time_keep_holiday){
                                    $holiday                                    = false;
                                }
                            }
                            
                            if($holiday){
                                $get_diff                                       = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
                                $arr                                            = array(
                                                                                'emp_no'                => $emp_no,
                                                                                'breaks'                => $number_of_breaks_per_day,
                                                                                'current_date'          => $current_date,
                                                                                'date'                  => $date,
                                                                                'emp_id'                => $emp_id,
                                                                                'comp_id'               => $comp_id,
                                                                                'check_type'            => $check_type,
                                                                                // 'source'                => $source,
                                                                                "source"                => "mobile",
                                                                                'min_log'               => $min_log,
                                                                                'get_diff'              => $get_diff,
                                                                                'employee_time_in_id'   => $r->employee_time_in_id,
                                                                                'work_schedule_id'      => $work_schedule_id,
                                                                                'time_in'               => $r->time_in,
                                                                                'time_out'              => $r->time_out,
                                                                                'lunch_in'              => $r->lunch_in,
                                                                                'lunch_out'             => $r->lunch_out,
                                                                                'new_timein'            => $new_timein,
                                                                                'timein_id'             => $timein_id,
                                                                                'hours_worked'          => $hours_worked
                                                                                );

                                return $this->holiday_time_in($arr);
                            }

                            // ## HOLIDAY END HERE ## // 
                                // required hours worked only
                            
                            $new_total_hours                                    = $this->total_hours_worked($current_date, $r->time_in);

                            if($number_of_breaks_per_day > 0 && $r->lunch_out != null){

                                $new_total_hours                                = $new_total_hours - $number_of_breaks_per_day;
                            }

                            $total_hours_worked                                 = $this->convert_to_hours($new_total_hours);
                            $update_timein_logs                                 = array(
                                                                                "undertime_min"             => $update_undertime,
                                                                                "total_hours"               => $hours_worked,
                                                                                "total_hours_required"      => $get_total_hours,
                                                                                "flag_tardiness_undertime"  => $flag_tu,
                                                                                // "source"                    => $last_timein_source.",".$source."-".$check_type,
                                                                                "source"                    => "mobile",
                                                                                );
                            
                            //attendance settings
                            $att                                                = $this->calculate_attendance($comp_id,$r->time_in,$current_date,$attendance_hours);

                            if($att){
                                $total_hours_worked                             = $this->total_hours_worked($new_time_out, $new_time_in);
                                $total_hours_worked                             = $this->convert_to_hours($total_hours_worked);
                                $update_timein_logs['lunch_in']                 = null;
                                $update_timein_logs['lunch_out']                = null;
                                $update_timein_logs['total_hours_required']     = $total_hours_worked;
                                $update_timein_logs['absent_min']               = ($get_hoursworked - $total_hours_worked) * 60;
                                $update_timein_logs['late_min']                 = 0;
                                $update_timein_logs['tardiness_min']            = 0;
                                $update_timein_logs['undertime_min']            = 0;
                            }

                            $this->db->where($where_update);
                            $sql_update_timein_logs                             = $this->db->update("employee_time_in AS eti",$update_timein_logs);
                            

                            // athan helper
                            if($last_timein){
                                $date = $last_timein->date;
                                // payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
                            }

                            return ($q2) ? $q2->row() : FALSE ;
                                
                        }
                        else if ($check_type == "time in"){
                            
                            $late_min                                           = $this->late_min($comp_id,$date,$emp_id,$work_schedule_id,"",$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);
                            
                            // insert time in log ================================================================ >>>> INSERT NEW TIME IN LOG SAME DATE
                            $insert                                             = FALSE;
                            $get_diff                                           = (strtotime($current_date) - strtotime($r->time_out)) / 60;
                            
                            if($min_log < $get_diff){
                                $val                                            = array(
                                                                                "emp_id"            => $emp_id,
                                                                                "comp_id"           => $comp_id,
                                                                                "date"              => $date,
                                                                                "time_in"           => $current_date,
                                                                                "work_schedule_id"  => $work_schedule_id,
                                                                                // "source"            => $source."-".$check_type,
                                                                                "source"            => "mobile",
                                                                                "late_min"          => $late_min,
                                                                                "location"          => $comp_add,
                                                                                "flag_on_leave"     => $ileave,
                                                                                "mobile_clockin_status" => 'pending',
                                                                                "location_1"        => $location,
                                                                                "time_in_status"    => 'pending',
                                                                                "corrected"         => 'Yes'
                                                                                );

                                $insert                                         = $this->db->insert("employee_time_in",$val);   
                            }
                            if($insert){
                                $w2                                             = array(
                                                                                "a.payroll_cloud_id"=> $emp_no,
                                                                                "eti.date"          => $date,
                                                                                "eti.status"        => "Active"
                                                                                );
                                $w3                                             = array(
                                                                                "eti.emp_id"        => $emp_id,
                                                                                "eti.date"          => $date,
                                                                                "eti.status"        => "Active"
                                                                                );
                                $this->edb->where($w3);
                                $this->db->order_by("eti.time_in","DESC");
                                $q2                                             = $this->edb->get("employee_time_in AS eti",1,0);
                                
                                return ($q2) ? $q2->row() : FALSE ;
                            }
                            else{
                                $this->edb->where($where_update);
                                $this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
                                $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
                                $q2                                             = $this->edb->get("employee_time_in AS eti",1,0);
                
                                return ($q2) ? $q2->row() : FALSE ;
                            }
                        }
                    }
                }
            }
            // if naa break
            else{
                            
                $last_timein_source                                             = "";

                if($last_timein){
                    $last_timein_source                                         = $last_timein->source;
                }

                if($sync_date != ""){
                    $current_date                                               = $sync_date;
                }
                else{
                    $current_date                                               = $barack_date_trap_exact_t_date;
                }
                
                $r                                                              = $last_timein;
        
                // wala pa sa db ang prev logs 
                if(!$r ){

                    if($sync_date !=""){
                        $time_in                                                = date('H:i:s',strtotime($sync_date));
                    }
                    else{
                        $time_in                                                = date('H:i:s');                
                    }
                    
                    if($this->type!='split'){
                        if($check_type == "time in"){

                            $timeIn                                             = $this->only_for_timeIn_reg_sched($emp_id,$emp_no,$work_schedule_id,$comp_id,$current_date,$date,$time_in,$source,$comp_add,$ileave,0,$get_all_regular_schedule,$get_work_schedule,$get_employee_payroll_information,$get_tardiness_settings,$location);
                            
                            /* Added Mobile 
                             * Send approval for mobile clockin
                             */
                            $etiid = ($timeIn) ? $timeIn->employee_time_in_id : '';
                            $pd = $current_date;
                            $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                            $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                            "time_in", $work_schedule_id, $pd);
                            /* added mobile */

                            return $timeIn;
                        }
                    }else{
                        
                        $get_diff                                               = 10;
                        
                        $arr                                                    = array(
                                                                                'emp_no'                => $emp_no,
                                                                                'current_date'          => $current_date, 
                                                                                'emp_id'                => $emp_id, 
                                                                                'comp_id'               => $comp_id,
                                                                                'breaks'                => $number_of_breaks_per_day,
                                                                                'check_type'            => "time in",
                                                                                'min_log'               => $min_log,
                                                                                'get_diff'              => $get_diff,
                                                                                'employee_time_in_id'   => "",
                                                                                'work_schedule_id'      => $work_schedule_id,
                                                                                'block_id'              => $split['schedule_blocks_id'],
                                                                                'schedule_blocks_id'    => $split['schedule_blocks_id'],
                                                                                'time_in'               => "",  
                                                                                'time_out'              => "",                      
                                                                                'lunch_in'              => "",
                                                                                'lunch_out'             => "",
                                                                                'new_timein'            => "",//$new_timein,
                                                                                'timein_id'             => "",
                                                                                'l_source'              => "",
                                                                                'new_employee_timein'   => $new_employee_timein,
                                                                                'enable_breaks_on_holiday'  => ($split) ? $split['enable_breaks_on_holiday'] : "",
                                                                                );  

                        $rtrn_split = $this->split_schedule_time_in($arr,$split_total_activate,"",$date,$source,$first_block_start_time,$check_type_o,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$company_holiday,$get_work_schedule);

                        /* Added Mobile 
                         * Send approval for mobile clockin
                         */
                        $etiid = ($rtrn_split) ? $rtrn_split->employee_time_in_id : '';
                        $etiid2 = ($rtrn_split) ? $rtrn_split->schedule_blocks_time_in_id : '';
                        $pd = $current_date;
                        $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                        $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                        "time_in", $work_schedule_id, $pd, $etiid2);
                        /* added mobile */

                        return $rtrn_split;
                    }
                }

                // if naa na sa db ang prev logs
                else{

                    // get date time in to date time out
                    $workday                                                    = date("l",strtotime($date));
                    $payroll_group_id                                           = $payroll_group;
                    
                    // check rest day
                    //$check_rest_day   = $this->check_rest_day($workday,$work_schedule_id,$comp_id);
                    // this is improvized checking of rest day
                    //change code here - aldrin

                    $flag_halfday = 0;
                    
                    /**
                     * SPLIT SCHEDULE MODIFICATIOIN HERE
                     * TAKE NOTE YOU aRe NOW ENTERING MY OWN PREMISES
                     * GOOD LUCK, HAVE FUN CODING
                     * 
                     * LOGS HERE IF SCHEDULE IS SPLIT, IF FIRST TIMEIN IN FIRST BLOCK IS ALREADY DONE THE REST OF PROCESS IS ALREADY HERE
                     */

                    if($this->type == 'split'){
                        $get_diff                                               = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
                        
                        if(!$this->schedule_blocks_id && $split_last_timein){
                            $this->schedule_blocks_id                           = $split_last_timein->schedule_blocks_id;
                        }

                        $arr                                                    = array(
                                                                                'emp_no'                => $emp_no,
                                                                                'current_date'          => $current_date, 
                                                                                'emp_id'                => $emp_id, 
                                                                                'comp_id'               => $comp_id,
                                                                                'breaks'                => $number_of_breaks_per_day,
                                                                                'check_type'            => $check_type,
                                                                                'min_log'               => $min_log,
                                                                                'get_diff'              => $get_diff,
                                                                                'employee_time_in_id'   => $split_last_timein->employee_time_in_id,
                                                                                'work_schedule_id'      => $work_schedule_id,
                                                                                'block_id'              => $split_last_timein->schedule_blocks_time_in_id,
                                                                                'schedule_blocks_id'    => $this->schedule_blocks_id,
                                                                                'time_in'               => $split_last_timein->time_in, 
                                                                                'time_out'              => $split_last_timein->time_out,                        
                                                                                'lunch_in'              => $split_last_timein->lunch_in,
                                                                                'lunch_out'             => $split_last_timein->lunch_out,
                                                                                'new_timein'            => "",//$new_timein,
                                                                                'l_source'              => $split_last_timein->source,
                                                                                'timein_id'             => $last_timein->employee_time_in_id,//$timein_id,
                                                                                'new_employee_timein'   => $new_employee_timein,
                                                                                'enable_breaks_on_holiday'  => ($split) ? $split['enable_breaks_on_holiday'] : $check_type,
                                                                                );  

                        $rt_splt = $this->split_schedule_time_in($arr,$split_total_activate,"",$date,$source,$first_block_start_time,$check_type_o,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$company_holiday,$get_work_schedule);

                        /* Added Mobile 
                         * Send approval for mobile clockin
                         */
                        $etiid = ($rt_splt) ? $rt_splt->employee_time_in_id : '';
                        $etiid2 = ($rt_splt) ? $rt_splt->schedule_blocks_time_in_id : '';
                        $pd = $current_date;
                        $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                        $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                        $check_type, $work_schedule_id, $pd, $etiid2);
                        /* added mobile */

                        return $rt_splt;
                    }
                    
                    
                    // IF WHOLEDAY --- ==> lunch Out ===> lunch In ==> Time Out ===> IF CLOCKIN BEFORE THE NEXT SHIFT COME
                    // check for double login

                    // ## REGULAR SCHED SETTINGS ## // 
                    $hours_worked                                               = 0;
                    $break                                                      = 0;
                    $tresh                                                      = 0;
                    if($regular_schedule_all){
                        $hours_worked                                           = $regular_schedule_all->total_work_hours;
                        $break                                                  = $regular_schedule_all->break_in_min;
                        $tresh                                                  = $regular_schedule_all->latest_time_in_allowed;
                    }
                    
                    $sc                                                         = array(
                                                                                'date',
                                                                                'time_out',
                                                                                'source',
                                                                                );

                    $wc                                                         = array(
                                                                                'date'                  => $date,
                                                                                'emp_id'                => $emp_id, 
                                                                                'comp_id'               => $comp_id, 
                                                                                'status'                => 'Active'
                                                                                );
                    $this->db->select($sc);
                    $this->db->where($wc);
                    $q                                                          = $this->db->get('employee_time_in');
                    $rx1d                                                       = $q->row();
                    $rows                                                       = $q->num_rows();

                    if($rx1d){
                        $last_timein_source                                     = $rx1d->source;
                    }

                    /// here trap if assumed 
                    $is_works                                                   = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);
                    $is_work                                                    = false;

                    if($is_works){

                        if($is_works->break_rules == "assumed"){

                            $is_work = $is_works;
                        }
                    }

                    if($is_work && $check_type != "time in"){
                        $check_type = "time out";
                    }

                    // IF HOLIDAY
                    $holiday                                                    = in_array_custom("holiday_{$date}",$company_holiday);


                    $check_if_worksched_holiday                                 = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);

                    if($check_if_worksched_holiday){
                        
                        $time_keep_holiday                                      = ($check_if_worksched_holiday->enable_breaks_on_holiday == 'yes') ? true : false;

                        if($time_keep_holiday){
                            $holiday                                            = false;
                        }
                    }

                    if($holiday){

                        if($check_type == "time in"){
                            $update_val                                         = array("time_in" => $current_date);
                            $date_insert                                        = array(
                                                                                "comp_id"               => $comp_id,
                                                                                "emp_id"                => $emp_id,
                                                                                "date"                  => $date ,
                                                                                // "source"                => $source."-time in",
                                                                                "source"                => "mobile",
                                                                                "time_in"               => $current_date,
                                                                                "work_schedule_id"      => $work_schedule_id,
                                                                                "mobile_clockin_status" => 'pending',
                                                                                "location_1"        => $location,
                                                                                "time_in_status"    => 'pending',
                                                                                "corrected"         => 'Yes'
                                                                                );
                            $this->db->insert('employee_time_in', $date_insert);

                            /* Added Mobile 
                             * Send approval for mobile clockin
                             */
                            $etiid = $this->db->insert_id();
                            $pd = $current_date;
                            $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                            $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                            "time_in", $work_schedule_id, $pd);
                            /* added mobile */

                        }
                        else{

                            $get_diff                                           = (strtotime($current_date) - strtotime($r->time_in)) / 60;
                            if($min_log < $get_diff){
                                if($sync_employee_time_in_id !=""){
                                    $where_update                               = array(
                                                                                "emp_id"                => $emp_id,
                                                                                "comp_id"               => $comp_id,
                                                                                "employee_time_in_id"   => $sync_employee_time_in_id,
                                                                                "status"                => "Active"
                                                                                );
                                }
                                else{
                                    $where_update                               = array(
                                                                                "emp_id"                => $emp_id,
                                                                                "comp_id"               => $comp_id,
                                                                                "employee_time_in_id"   => $r->employee_time_in_id,
                                                                                "status"                => "Active"
                                                                                );
                                }

                                $time_in_time                                   = $r->time_in;
                                $h                                              = $this->total_hours_worked($current_date,$time_in_time);
                                $holday_hour                                    = $this->convert_to_hours($h);
                                $tq_update_field                                = array(
                                                                                "time_out"                  => $current_date,
                                                                                "total_hours"               => $holday_hour,
                                                                                "total_hours_required"      => $holday_hour,
                                                                                // "source"                    => $last_timein_source.",".$source."-".$check_type,
                                                                                "source"                    => "mobile",
                                                                                "mobile_clockout_status" => 'pending',
                                                                                "location_4"        => $location,
                                                                                "time_in_status"    => 'pending',
                                                                                "corrected"         => 'Yes'
                                                                                );
                            
                                $this->db->where($where_update);
                                $this->db->update('employee_time_in',$tq_update_field);

                                // athan helper
                                if($rx1d){
                                    $date = $last_timein->date;
                                    // payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
                                }

                                /* Added Mobile 
                                 * Send approval for mobile clockin
                                 */
                                $etiid = $r->employee_time_in_id;
                                $pd = $current_date;
                                $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                                $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                                "time_out", $work_schedule_id, $pd);
                                /* added mobile */
                            }
                        return true;
                        }
                    }
                    // END HOLIDAY

                    if(strtotime($current_date) <= strtotime($add_oneday_timein." -120 minutes") && $rows == 1){
                        
                        if($check_type == "time in" && $rx1d->time_out == Null){

                            $check_type                                         = "time out";
                        }
                        
                        $hours_worked                                           = $hours_worked;
                        // global where update data
                        if($sync_employee_time_in_id !=""){

                            $where_update                                       = array(
                                                                                "eti.emp_id"                => $emp_id,
                                                                                "eti.comp_id"               => $comp_id,
                                                                                "eti.employee_time_in_id"   => $sync_employee_time_in_id,
                                                                                "eti.status"                => "Active"
                                                                                );
                        }
                        else{
                            $where_update                                       = array(
                                                                                "eti.emp_id"                => $emp_id,
                                                                                "eti.comp_id"               => $comp_id,
                                                                                "eti.employee_time_in_id"   => $r->employee_time_in_id,
                                                                                "eti.status"                => "Active"
                                                                                );
                        }

                        if($check_type == "lunch out"){

                            // update lunch out value ============ >>>> UPDATE LUNCH OUT VALUE
                            if($this->type!="split"){
                                $get_diff                                       = (strtotime($current_date) - strtotime($r->time_in)) / 60;
                                
                                if($min_log < $get_diff){

                                    $update_val                                 = array(
                                                                                "lunch_out"                 => $current_date,
                                                                                // "source"                    => $last_timein_source.",".$source."-".$check_type,
                                                                                "source"                    => "mobile",
                                                                                "mobile_lunchout_status" => 'pending',
                                                                                "location_2"        => $location,
                                                                                "time_in_status"    => 'pending',
                                                                                "corrected"         => 'Yes'
                                                                                );
                                    $this->db->where($where_update);
                                    $update                                     = $this->db->update("employee_time_in AS eti",$update_val);

                                    /* Added Mobile 
                                     * Send approval for mobile clockin
                                     */
                                    $etiid = $r->employee_time_in_id;
                                    $pd = $current_date;
                                    $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                                    $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                                    "lunch_out", $work_schedule_id, $pd);
                                    /* added mobile */
                                }

                                $this->edb->where($where_update);
                                $q2                                             = $this->edb->get("employee_time_in AS eti",1,0);
                                return ($q2) ? $q2->row() : FALSE ;
                            }
                        }
                        else if($check_type == "lunch in"){

                            // update lunch in value =========== >>>> UPDATE LUNCH IN VALUE
                            if($this->type!="split"){
                                $get_diff                                       = (strtotime($current_date) - strtotime($r->lunch_out)) / 60;
                                $overbreak_min                                  = $this->overbreak_min($comp_id,$date,$emp_id,$work_schedule_id,$r->lunch_out,"",$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);

                                $late_min                                       = ($r) ? $r->late_min : 0;
                                $tardiness_min                                  = $late_min + $overbreak_min;

                                if($min_log < $get_diff){

                                    $update_val                                 = array(
                                                                                "lunch_in"      => $current_date,
                                                                                "overbreak_min" => $overbreak_min,
                                                                                "tardiness_min" => $tardiness_min,
                                                                                // "source"        => $last_timein_source.",".$source."-".$check_type,
                                                                                "source"        => "mobile",
                                                                                "mobile_lunchin_status" => 'pending',
                                                                                "location_3"        => $location,
                                                                                "time_in_status"    => 'pending',
                                                                                "corrected"         => 'Yes'
                                                                                );

                                    $this->db->where($where_update);

                                    $update                                     = $this->db->update("employee_time_in AS eti",$update_val);

                                    /* Added Mobile 
                                     * Send approval for mobile clockin
                                     */
                                    $etiid = $r->employee_time_in_id;
                                    $pd = $current_date;
                                    $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                                    $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                                    "lunch_in", $work_schedule_id, $pd);
                                    /* added mobile */
                                }
                                
                                $this->edb->where($where_update);
                                $q2                                             = $this->edb->get("employee_time_in AS eti",1,0);
                
                                return ($q2) ? $q2->row() : FALSE ;
                            }
                        }
                        else if($check_type == "time out"){
                            
                            // update time out value ======================== >>>> UPDATE TIME OUT VALUE
                            $work_sched                                         = $regular_schedule_all;
                            $update                                             = FALSE;
                            $continue                                           = false;
                            $time_in_time                                       = $r->time_in;
                            $lunch_out_time_punch                               = $r->lunch_out;
                            $lunch_in_time_punch                                = $r->lunch_in;
                            $new_time_out_cur                                   = $current_date;
                            $new_time_out_cur_orig                              = $current_date;
                            $new_time_out_cur_orig_str                          = strtotime($new_time_out_cur_orig);
                            $tardiness_min                                      = $r->tardiness_min;
                            $late_min                                           = $r->late_min;
                            $overbreak_min                                      = $r->overbreak_min;
                            $new_time_in_start_assumed                          = $time_in_time;
                            $payroll_sched_timein_orig_str                      = strtotime($payroll_sched_timein_orig);
                            $time_in_time_str                                   = strtotime($time_in_time);
                            $payroll_sched_timein_str                           = strtotime($payroll_sched_timein);
                            $current_date_str                                   = strtotime($current_date);
                            $total_hours_work_required                          = 0;
                            $undertime_break                                    = 0;
                            $new_break                                          = $number_of_breaks_per_day;
                            $new_undertime                                      = 0;
                            $work_end_time                                      = "";
                            $work_end_time_str                                  = 0;
                            
                            if($work_sched){
                                $total_hours_work_required                      = $work_sched->total_work_hours;
                                $work_end_time                                  = $date." ".$work_sched->work_end_time;
                                $work_start_time                                = $date." ".$work_sched->work_start_time;
                                $work_end_time_str                              = strtotime($work_end_time);
                                $work_start_time_str                            = strtotime($work_start_time);

                                if($work_end_time_str < $work_start_time_str){

                                    $work_end_time                              = date("Y-m-d H:i:s",strtotime($work_end_time ."+ 1 day"));
                                }
                            }
                            
                            //*** SET A GLOBAL BOUNDARY FOR LUNCHOUT AND LUNCHIN ==> this work for regular schedule that capture break***/
                            //**  get half of total hours worked required per schedule

                            $total_hours_work_required_half                     =  $total_hours_work_required / 2;
                            $total_hours_work_required_half                     =  ($total_hours_work_required_half >= 1) ? $total_hours_work_required_half : 0;
                            $total_hours_work_required_half_min                 =  ($total_hours_work_required_half >= 1) ? ($total_hours_work_required_half*60) : 0;
                            
                            //** set assumed break without threshold for Init **/
                            $lunch_out                                          = date('Y-m-d H:i:s',strtotime($payroll_sched_timein_orig. " +{$total_hours_work_required_half_min} minutes"));
                            $lunch_in                                           = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$number_of_breaks_per_day} minutes"));
                            $lunch_out_str                                      = strtotime($lunch_out);
                            $lunch_in_str                                       = strtotime($lunch_in);
                            $lunch_in_init                                      = $lunch_in;
                            
                            if($is_work){

                                $h                                              = $is_work->assumed_breaks * 60;
                                
                                //** set assumed break without threshold for Init **/
                                $lunch_out                                      = date('Y-m-d H:i:s',strtotime($payroll_sched_timein_orig. " +{$h} minutes"));
                                $lunch_in                                       = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$number_of_breaks_per_day} minutes"));
                                $lunch_out_str                                  = strtotime($lunch_out);
                                $lunch_in_str                                   = strtotime($lunch_in);
                                
                                //*** UPDATE ALDRENs ALGO ON GETTING LUNCHOUT AND LUNCHIN ***//
                                //** assumed break (LIn and LOut) affects when employee timeIn and when threshold is set**//
                                //** if timeIn before the startTime **/
                                if($payroll_sched_timein_orig_str > $time_in_time_str){
                                    // set to init break
                                }
                                //** if timeIn after the startTime w/out thresHold but less than the (init) break **/
                                if($threshold_min <= 0){
                                    if(($lunch_out_str > $time_in_time_str) && ($payroll_sched_timein_orig_str < $time_in_time_str)){
                                        // set to init break
                                    }
                                }
                                //** if timeIn between the startTime And the thresHold **/
                                if($threshold_min > 0){
                                    if(($payroll_sched_timein_orig_str <= $time_in_time_str) && ($payroll_sched_timein_str >= $time_in_time_str)) {

                                        // LOut and LIn depend on timeIn
                                        $lunch_out                              = date('Y-m-d H:i:s',strtotime($time_in_time. " +{$h} minutes"));
                                        $lunch_in                               = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$number_of_breaks_per_day} minutes"));
                                        
                                        $work_end_calculate                     = $this->total_hours_worked($time_in_time, $payroll_sched_timein_orig);
                                        $work_end_time                          = date('Y-m-d H:i:s',strtotime($work_end_time. " +{$work_end_calculate} minutes"));
                                    }
                                }
                                
                                //** if timeIn after the startTime And ThresHold but less than the (init) break **/
                                if($threshold_min > 0){

                                    if(($lunch_out_str > $time_in_time_str) && ($payroll_sched_timein_str < $time_in_time_str)) {

                                        // LOut and LIn start Time plus ThresHold 
                                        $lunch_out                              = date('Y-m-d H:i:s',strtotime($time_in_time. " +{$h} minutes"));
                                        $lunch_in                               = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$number_of_breaks_per_day} minutes"));
                                        
                                        $work_end_time                          =  date('Y-m-d H:i:s',strtotime($work_end_time. " +{$threshold_min} minutes"));
                                    }
                                }
                                
                                //** if HALFDAY timeIn, thresHold dont effect anymore, Halfday time is set using the half of the totalHours (+) plus the startTime (as lunchOut assumed) plus break (as lunchIN assumed)**/
                                //** timeIn between And after this assumed time will set that the employee is doing Halfday **/
                                //** timeIn between

                                if(($lunch_out_str <= $time_in_time_str) && ($lunch_in_str >= $time_in_time_str)) {
                                    // set the LOut and LIn to null and the timeiN start count on the assumed lunchIn since its assumed here to be break ==> amo ni amo gisabutan
                                    $new_time_in_start_assumed                  = $lunch_in;
                                    $lunch_out                                  = null;
                                    $lunch_in                                   = null;
                                }
                                //** timeIn after break
                                if($lunch_in_str < $time_in_time_str) {
                                    // set the LOut and LIn to null and the timeiN
                                    $lunch_out                                  = null;
                                    $lunch_in                                   = null;
                                    $new_break                                  = 0;
                                }
                                
                                //*** if assumed breaks scenario regular schedule timeout ***//
                                //**  init LOut & LIn
                                if(($lunch_out != null) && ($lunch_in != null)){

                                    $lunch_out_new_str                          = strtotime($lunch_out);
                                    $lunch_in_new_str                           = strtotime($lunch_in);
                                }
                                else{
                                    $lunch_out_new_str                          = $lunch_out_str;
                                    $lunch_in_new_str                           = $lunch_in_str;
                                }
                                
                                //** if timeout before break **//
                                if($current_date_str < $lunch_out_new_str){
                                    $new_break                                  = 0;
                                    $undertime_break                            = $number_of_breaks_per_day;
                                    $new_time_out_cur                           = $current_date;
                                    $lunch_out                                  = null;
                                    $lunch_in                                   = null;
                                }
                                //** if timeout between break **//
                                if(($current_date_str >= $lunch_out_new_str) && ($current_date_str <= $lunch_in_new_str)){

                                    $new_break                                  = 0;
                                    $new_time_out_cur                           = $lunch_out;
                                    $undertime_break                            = $number_of_breaks_per_day;
                                    $lunch_out                                  = null;
                                    $lunch_in                                   = null;
                                }
                                //** if timeout before the init lunchin this means he/she is doing halfday as we discus 2/17/17 5:00 PM **//
                                if($new_time_out_cur_orig_str < $lunch_in_new_str){

                                    $new_break                                  = $this->total_hours_worked($lunch_in_time_punch, $lunch_out_time_punch);
                                }

                                //** if timeout after break **//
                                if($current_date_str > $lunch_in_new_str){

                                    $new_break                                  = $number_of_breaks_per_day;

                                    //** timeIn after break
                                    if($lunch_in_str < $time_in_time_str) {

                                        $new_break                              = 0;
                                    }

                                    //** timeIn between break
                                    if($lunch_in_str >= $time_in_time_str && $lunch_out_str <= $time_in_time_str) {

                                        $new_break                              = 0;
                                    }
                                    $new_time_out_cur                           = $current_date;
                                }
                                
                                $total_hours_new                                = $this->total_hours_worked($new_time_out_cur, $new_time_in_start_assumed);
                                $total_hours_new_m                              = $total_hours_new - $new_break;
                                $total_hours_new_h                              = $total_hours_new_m/60;
                                
                                $work_end_time_str                              = strtotime($work_end_time);
                                
                                if($work_end_time_str > $current_date_str){
                                    $new_undertime                              = $this->total_hours_worked($work_end_time, $new_time_out_cur);
                                    $new_undertime                              = $new_undertime - $undertime_break;
                                }
                                
                                if($current_date <= $lunch_in){
                                    $continue                                   = true;
                                }
                            }
                            
                            if($r->lunch_in){
                                $get_diff                                       = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
                            }else{
                                $get_diff                                       = (strtotime($current_date) - strtotime($r->time_in)) / 60;
                            }

                            if($this->type!="split"){

                                if($min_log < $get_diff || $continue){
                                    
                                    $update_val                                 = array(
                                                                                "time_out"      => $new_time_out_cur_orig,
                                                                                // "source"        => $last_timein_source.",".$source."-".$check_type,
                                                                                "source"        => "mobile",
                                                                                "mobile_clockout_status" => 'pending',
                                                                                "location_4"        => $location,
                                                                                "time_in_status"    => 'pending',
                                                                                "corrected"         => 'Yes'
                                                                                );
                                    $this->db->where($where_update);
                                    $update                                     = $this->db->update("employee_time_in AS eti",$update_val);

                                    /* Added Mobile 
                                     * Send approval for mobile clockin
                                     */
                                    $etiid = $r->employee_time_in_id;
                                    $pd = $current_date;
                                    $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                                    $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                                    "time_out", $work_schedule_id, $pd);
                                    /* added mobile */

                                    // athan helper
                                    if($rx1d){
                                        $date = $rx1d->date;
                                        // payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
                                    }
                                }
                                $this->edb->where($where_update);
                                $q2                                             = $this->edb->get("employee_time_in AS eti",1,0);
                                $r2                                             = $q2->row();

                            }
                            else{

                                $get_diff                                       = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;

                                if($min_log < $get_diff){
                                    $update_val                                 = array(
                                                                                "time_out"      => $current_date,
                                                                                // "source"        => $last_timein_source.",".$source."-".$check_type,
                                                                                "source"        => "mobile",
                                                                                "time_in_status"    =>"pending",
                                                                                "corrected"         => 'Yes'
                                                                                );
                                    $this->db->where($where_update);
                                    $update                                     = $this->db->update("schedule_blocks_time_in AS eti",$update_val);
                                }

                                $this->edb->where($where_update);
                                $q2                                             = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
                                $r2                                             = $q2->row();
                            }
                            
                            $half_day                                           = $this->if_half_day($r2->time_in,$work_schedule_id,$comp_id,$emp_no,$current_date,$r2->employee_time_in_id,$emp_id,$date,$get_all_regular_schedule,$get_work_schedule_flex);

                            //holiday now
                            $holiday                                            = in_array_custom("holiday_{$date}",$company_holiday);

                            $check_if_worksched_holiday                         = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);

                            if($check_if_worksched_holiday){
                                
                                $time_keep_holiday                              = ($check_if_worksched_holiday->enable_breaks_on_holiday == 'yes') ? true : false;

                                if($time_keep_holiday){
                                    $holiday                                    = false;
                                }
                            }

                            if($holiday){
                                $get_diff                                       = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
                                $arr                                            = array(
                                                                                'emp_no'                => $emp_no,
                                                                                'current_date'          => $current_date,
                                                                                'break'                 => $number_of_breaks_per_day,
                                                                                'date'                  => $date,
                                                                                'emp_id'                => $emp_id,
                                                                                'comp_id'               => $comp_id,
                                                                                'check_type'            => $check_type,
                                                                                // 'source'                => $source,
                                                                                "source"                => "mobile",
                                                                                'min_log'               => $min_log,
                                                                                'get_diff'              => $get_diff,
                                                                                'employee_time_in_id'   => $r->employee_time_in_id,
                                                                                'work_schedule_id'      => $work_schedule_id,
                                                                                'time_in'               => $r->time_in,
                                                                                'time_out'              => $r->time_out,
                                                                                'lunch_in'              => $r->lunch_in,
                                                                                'lunch_out'             => $r->lunch_out,
                                                                                'new_timein'            => $new_timein,
                                                                                'timein_id'             => $timein_id
                                                                                );
                            
                                $ret_hol = $this->holiday_time_in($arr);

                                /* Added Mobile 
                                 * Send approval for mobile clockin
                                 */
                                $etiid = ($ret_hol) ? $ret_hol->employee_time_in_id : '';
                                $pd = $current_date;
                                $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                                $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                                $check_type, $work_schedule_id, $pd);
                                /* added mobile */

                                return $ret_hol;
                            }

                            if($update){
                                // update flag tardiness and undertime
                                $flag_tu = 0;
                                
                                //*** TO CALCULATE FOR UNDERTIME ***//
                                //** FIND WORK END if timeIn between the startTime And the thresHold **/
                                if($threshold_min > 0){
                                    if(($payroll_sched_timein_orig_str <= $time_in_time_str) && ($payroll_sched_timein_str >= $time_in_time_str)) {
                                        // update workend start                                     
                                        $work_end_calculate                     = $this->total_hours_worked($time_in_time, $payroll_sched_timein_orig);
                                        $work_end_time                          =  date('Y-m-d H:i:s',strtotime($work_end_time. " +{$work_end_calculate} minutes"));
                                    }
                                }
                                
                                //** FIND WORK END  if timeIn after the startTime And ThresHold but less than the (init) break **/
                                if($threshold_min > 0){
                                    if(($lunch_out_str > $time_in_time_str) && ($payroll_sched_timein_str < $time_in_time_str)) {
                                        // update workend start
                                        $work_end_time                          =  date('Y-m-d H:i:s',strtotime($work_end_time. " +{$threshold_min} minutes"));
                                        
                                    }
                                }
                                //** UNDER TIME **//
                                $work_end_time_str                              = strtotime($work_end_time);
                                
                                if($work_end_time_str > $current_date_str){
                                    // if early clock
                                    if($lunch_in_time_punch == null || $lunch_out_time_punch == null){
                                        
                                        // time out during assumed break
                                        if($current_date_str < $lunch_in_str && $current_date_str >= $lunch_out_str){

                                            $new_undertime                      = $this->total_hours_worked($work_end_time, $lunch_in_init);
                                        }
                                        else if($current_date_str <= $lunch_out_str){

                                            $new_undertime                      = $this->total_hours_worked($work_end_time, $new_time_out_cur);
                                            $new_undertime                      = $new_undertime - $number_of_breaks_per_day;
                                        }
                                        else{

                                            $new_undertime                      = $this->total_hours_worked($work_end_time, $new_time_out_cur);
                                        }
                                    }else{

                                        $new_undertime                          = $this->total_hours_worked($work_end_time, $new_time_out_cur);
                                        $new_undertime                          = $new_undertime - $undertime_break;
                                    }
                                    
                                }
                                
                                //** if timeout before the init lunchin this means he/she is doing halfday as we discus 2/17/17 5:00 PM **//
                                if($new_time_out_cur_orig_str < $lunch_in_str){

                                    $new_break                                  = $this->total_hours_worked($lunch_in_time_punch, $lunch_out_time_punch);
                                }
                                //** UPDATE TOTAL HOURS WORK**//
                                $update_total_hours_required                    = $this->total_hours_worked($new_time_out_cur, $time_in_time);
                                $update_total_hours_required                    = $update_total_hours_required - ($new_break + $overbreak_min);
                                
                                //** TO HOURS **/
                                $update_total_hours_required                    = $update_total_hours_required/60;
                                
                                // update employee time in logs
                                $update_timein_logs                             = array(
                                                                                "undertime_min"             => $new_undertime,
                                                                                "tardiness_min"             => $tardiness_min,
                                                                                "total_hours"               => $hours_worked,
                                                                                "total_hours_required"      => $update_total_hours_required,
                                                                                "flag_tardiness_undertime"  => $flag_tu
                                                                                );
                                
                                //**** IF ASSUME BREAK OVERWIRTE EVERYTHING****//
                                if($is_work){
                                    $update_timein_logs['lunch_in']             = $lunch_in;
                                    $update_timein_logs['lunch_out']            = $lunch_out;
                                    $update_timein_logs["total_hours_required"] = $total_hours_new_h;
                                    $update_timein_logs['absent_min']           = 0;
                                    $update_timein_logs['tardiness_min']        = $tardiness_min;
                                    $update_timein_logs['undertime_min']        = $new_undertime;
                                }
                                
                                // ***** NEW COMPUTATION FOR ATTENDANCE SETTINGS
                                
                                if($attendance_hours){
                                    if($update_total_hours_required <= $att){
                                        
                                        if($r2->time_in >= $r2->lunch_out){
                                            $update_timein_logs['lunch_out']    = null;
                                            $update_timein_logs['lunch_in']     = null;
                                        }
                                        elseif($current_date <= $r2->lunch_in){
                                            $update_timein_logs['lunch_out']    = null;
                                            $update_timein_logs['lunch_in']     = null;
                                        }
                                            
                                        $half_day_h = ($hours_worked / 2) * 60;

                                        if($late_min < $half_day_h){

                                            $update_timein_logs['late_min']     = $tardiness_min;
                                            $update_timein_logs['tardiness_min']= $tardiness_min;
                                            $update_timein_logs['undertime_min']= 0;
                                            $update_timein_logs['absent_min']   = (($hours_worked - $update_total_hours_required) * 60) - $tardiness_min;
                                        }
                                        else{

                                            $update_timein_logs['late_min']     = 0;
                                            $update_timein_logs['tardiness_min']= 0;
                                            $update_timein_logs['undertime_min']= $new_undertime;
                                            $update_timein_logs['absent_min']   = (($hours_worked - $update_total_hours_required) * 60) - $new_undertime;
                                        }

                                        $update_timein_logs['total_hours_required']     = $update_total_hours_required;
                                    }
                                }
                                
                                //**** UPDATE HERE THEN END ***/
                                $this->db->where($where_update);
                                $sql_update_timein_logs                         = $this->db->update("employee_time_in AS eti",$update_timein_logs);
                            }
                            
                            return ($q2) ? $q2->row() : FALSE ;
                        }
                        else if($check_type == "time in"){

                            $get_diff                                           = (strtotime($current_date) - strtotime($r->time_out)) / 60;

                            if($min_log < $get_diff){
                                
                                $date_insert                                    = array(
                                                                                "comp_id"                   => $comp_id,
                                                                                "emp_id"                    => $emp_id,
                                                                                "date"                      => $date,
                                                                                "time_in"                   => $current_date,
                                                                                "undertime_min"             => 0,
                                                                                "tardiness_min"             => 0,
                                                                                "late_min"                  => 0,
                                                                                "overbreak_min"             => 0,
                                                                                "work_schedule_id"          => "-2",
                                                                                // "source"                    => $source."-time in",
                                                                                "source"                    => "mobile",
                                                                                "location"                  => $comp_add,
                                                                                "flag_regular_or_excess"    => "excess",
                                                                                "source"        => "mobile",
                                                                                "time_in_status"    =>"pending",
                                                                                "corrected"         => 'Yes',
                                                                                "mobile_clockin_status" => "pending",
                                                                                "location_1" => $location
                                                                                );
                                
                                $add_logs                                       = $this->db->insert('employee_time_in', $date_insert);
                                
                                if($add_logs){
                                    $w2                                         = array(
                                                                                "eti.emp_id"                => $emp_id,
                                                                                "eti.date"                  => $date,
                                                                                "eti.status"                => "Active"
                                                                                );
                                    $this->edb->where($w2);
                                    $this->db->order_by("eti.time_in","DESC");
                                    $q2                                         = $this->edb->get("employee_time_in AS eti",1,0);

                                    $rrow = $q2->row();
                                    /* Added Mobile 
                                     * Send approval for mobile clockin
                                     */
                                    $etiid = ($rrow) ? $rrow->employee_time_in_id : '';
                                    $pd = $current_date;
                                    $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                                    $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                                    "time_in", $work_schedule_id, $pd);
                                    /* added mobile */
                                
                                    return ($rrow) ? $rrow : FALSE ;
                                    exit;
                                }else{
                                    $this->edb->where($where_update);
                                    $q2 = $this->edb->get("employee_time_in AS eti",1,0);
                                
                                    return ($q2) ? $q2->row() : FALSE ;
                                    exit;
                                }
                            }
                        }
                    }

                    else if($rows == 0){
                        $timeIn = $this->only_for_timeIn_reg_sched($emp_id,$emp_no,$work_schedule_id,$comp_id,$current_date,$date,$time_in,$source,$comp_add,$ileave,0,$get_all_regular_schedule,$get_work_schedule,$get_employee_payroll_information,$get_tardiness_settings,$location);
                        
                         /* Added Mobile 
                         * Send approval for mobile clockin
                         */
                        $etiid = ($timeIn) ? $timeIn->employee_time_in_id : '';
                        $pd = $current_date;
                        $this->send_approvals($emp_id, $comp_id, $etiid, $fullname, $approver_id, 
                        $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                        "time_in", $work_schedule_id, $pd);
                        /* added mobile */

                        return $timeIn;
                    }

                    //*** for new timeIn under the same shift (double timeIn) OR clockIn after the prev shift (miss timeOut)***//
                    else{
                        $where_update                                           = array(
                                                                                "eti.emp_id"                => $emp_id,
                                                                                "eti.comp_id"               => $comp_id,
                                                                                "eti.employee_time_in_id"   => $r->employee_time_in_id,
                                                                                "eti.status"                => "Active"
                                                                                );

                        if($check_type == "time out" || $check_type == 'lunch out' || $check_type == "lunch in"){
                            
                            $get_diff                                           = (strtotime($current_date) - strtotime($r->time_in)) / 60;
                            if($min_log < $get_diff){

                                $total_h_r                                      = ($this->total_hours_worked($current_date,$r->time_in) / 60);
                                $update_val                                     = array(
                                                                                "time_out"              => $current_date,
                                                                                "total_hours_required"  => $total_h_r,
                                                                                "total_hours"           => $total_h_r,
                                                                                // "source"                => $last_timein_source.",".$source."-time out",
                                                                                "source"                => "mobile",
                                                                                "mobile_clockout_status"        => "pending",
                                                                                "time_in_status"    =>"pending",
                                                                                "corrected"         => 'Yes',
                                                                                "location_4" => $location
                                                                                );

                                $this->db->where($where_update);
                                $update                                         = $this->db->update("employee_time_in AS eti",$update_val);

                                // athan helper
                                if($rx1d){
                                    $date = $rx1d->date;
                                    // payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
                                }
                            }
                        
                            $this->edb->where($where_update);
                            $q2                                                 = $this->edb->get("employee_time_in AS eti",1,0);
                        
                            return ($q2) ? $q2->row() : FALSE ;
                            exit;
                        
                        }else if($check_type == "time in"){
                            $get_diff   = (strtotime($current_date) - strtotime($r->time_out)) / 60;
                            if($min_log < $get_diff){
                                
                                $date_insert                                    = array(
                                                                                "comp_id"                   => $comp_id,
                                                                                "emp_id"                    => $emp_id,
                                                                                "date"                      => $date,
                                                                                "time_in"                   => $current_date,
                                                                                "undertime_min"             => 0,
                                                                                "tardiness_min"             => 0,
                                                                                "late_min"                  => 0,
                                                                                "overbreak_min"             => 0,
                                                                                "work_schedule_id"          => "-2",
                                                                                // "source"                    => $source."-time in",
                                                                                "source"                    => "mobile",
                                                                                "location"                  => $comp_add,
                                                                                "flag_regular_or_excess"    => "excess",
                                                                                "mobile_clockin_status"        => "pending",
                                                                                "time_in_status"    =>"pending",
                                                                                "corrected"         => 'Yes',
                                                                                "location_4" => $location
                                                                                );
                                
                                $add_logs                                       = $this->db->insert('employee_time_in', $date_insert);
                                if($insert){
                                    $w2                                         = array(
                                                                                "eti.emp_id"                => $emp_id,
                                                                                "eti.date"                  => $date,
                                                                                "eti.status"                => "Active"
                                                                                );

                                    $this->edb->where($w2);
                                    $this->db->order_by("eti.time_in","DESC");
                                    $q2 = $this->edb->get("employee_time_in AS eti",1,0);
                                        
                                    return ($q2) ? $q2->row() : FALSE ;
                                    exit;

                                }else{

                                    $this->edb->where($where_update);
                                    $q2                                         = $this->edb->get("employee_time_in AS eti",1,0);
                                
                                    return ($q2) ? $q2->row() : FALSE ;
                                    exit;
                                }
                            }
                            else{

                                $this->edb->where($where_update);
                                $q2                                             = $this->edb->get("employee_time_in AS eti",1,0);
                            
                                return ($q2) ? $q2->row() : FALSE ;
                                exit;
                            }
                        }
                    }
                }
            }
        }
    }

    public function check_last_sched_time_in($emp_no,$sync_employee_time_in_id,$comp_id,$type=""){
        
        $arrt = array(
                'time_in'               => 'eti.time_in',
                'lunch_out'             => 'eti.lunch_out',
                'lunch_in'              => 'eti.lunch_in',
                'time_out'              => 'eti.time_out',
                'late'                  => 'eti.late_min',
                'overbreak'             => 'eti.overbreak_min',
                'tardiness'             => 'eti.tardiness_min',
                'employee_time_in_id'   => 'eti.employee_time_in_id',
                'date'                  => 'eti.date',
                'payroll_group_id'      => 'epi.payroll_group_id'
        );
        
        if($type == "split"){
            $arrt['schedule_blocks_time_in_id'] = 'eti.schedule_blocks_time_in_id';
        }
        $this->edb->select($arrt);
        
        if($sync_employee_time_in_id!=""){
            $w  = array(
                    "a.payroll_cloud_id"        => $emp_no,
                    "a.user_type_id"            => "5",
                    "eti.employee_time_in_id"   => $sync_employee_time_in_id,
                    "eti.status"                => "Active",
                    "eti.comp_id"               => $comp_id
            );
        }
        else{
            $w  = array(
                    "a.payroll_cloud_id"        => $emp_no,
                    "a.user_type_id"            => "5",
                    "eti.status"                => "Active",
                    "eti.comp_id"               => $comp_id
            );
        }
        
        $this->edb->where($w);
        
        $this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
        $this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
        $this->db->order_by("eti.time_in","DESC");
        
        if($type == "split"){
            $q = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
        }
        else{
            $q = $this->edb->get("employee_time_in AS eti",1,0);
        }
        $r = $q->row();
        return ($r) ? $r : false;
    }

    public function get_all_employee_timein($company_id,$emp_ids="",$min_range="",$max_range=""){
        $row_array  = array();
        $arrx       = array(
                        'employee_time_in_id',
                        'work_schedule_id',
                        "emp_id",
                        "date",
                        "time_in",
                        "lunch_out",
                        "lunch_in",
                        "break1_in",
                        "break1_out",
                        "break2_in",
                        "break2_out",
                        "time_out",
                        "total_hours",
                        "total_hours_required",
                        "corrected",
                        "reason",
                        "time_in_status",
                        "overbreak_min",
                        "late_min",
                        "tardiness_min",
                        "undertime_min",
                        "absent_min",
                        "source",
                        "last_source",
                        "flag_time_in",
                        "flag_halfday",
                        "location",
                        "split_status",
                        "flag_on_leave",
                        "flag_delete_on_hours",
                        "ip_address",
                        "kiosk_location",
                        "flag_regular_or_excess"
                    );
        
        $this->db->select($arrx);

        $w      = array(
                "status"            => "Active",
                "comp_id"           => $company_id
                );

        if($emp_ids){
            $this->db->where_in("emp_id",$emp_ids);
        }

        if($min_range != "" && $max_range != ""){
            $w1 = array(
                    "date >="   => $min_range,
                    "date <="   => $max_range
            );
            $this->db->where($w1);
        }

        // $this->db->where("(time_in_status = 'approved' OR time_in_status is NULL)",FALSE,FALSE);
        $this->db->where("(time_in_status != 'reject' OR time_in_status is NULL)",FALSE,FALSE);
        $this->db->where($w);
        $this->db->order_by("date DESC,time_in DESC");
        $q      = $this->db->get("employee_time_in AS eti");
        $result = $q->result();
        
        if($result){
            foreach ($result as $key =>$r1){
                $wd     = array(
                        "employee_time_in_id"       => $r1->employee_time_in_id,
                        "work_schedule_id"          => $r1->work_schedule_id,
                        "emp_id"                    => $r1->emp_id,
                        "date"                      => $r1->date,
                        "time_in"                   => $r1->time_in,
                        "lunch_out"                 => $r1->lunch_out,
                        "lunch_in"                  => $r1->lunch_in,
                        "break1_in"                 => $r1->break1_in,
                        "break1_out"                => $r1->break1_out,
                        "break2_in"                 => $r1->break2_in,
                        "break2_out"                => $r1->break2_out,
                        "time_out"                  => $r1->time_out,
                        "total_hours"               => $r1->total_hours,
                        "total_hours_required"      => $r1->total_hours_required,
                        "corrected"                 => $r1->corrected,
                        "reason"                    => $r1->reason,
                        "time_in_status"            => $r1->time_in_status,
                        "overbreak_min"             => $r1->overbreak_min,
                        "late_min"                  => $r1->late_min,
                        "tardiness_min"             => $r1->tardiness_min,
                        "undertime_min"             => $r1->undertime_min,
                        "absent_min"                => $r1->absent_min,
                        "source"                    => $r1->source,
                        "last_source"               => $r1->last_source,
                        "flag_time_in"              => $r1->flag_time_in,
                        "flag_halfday"              => $r1->flag_halfday,
                        "location"                  => $r1->location,
                        "split_status"              => $r1->split_status,
                        "flag_on_leave"             => $r1->flag_on_leave,
                        "flag_delete_on_hours"      => $r1->flag_delete_on_hours,
                        "ip_address"                => $r1->ip_address,
                        "kiosk_location"            => $r1->kiosk_location,
                        "flag_regular_or_excess"    => $r1->flag_regular_or_excess,
                        "custom_search"             => "emp_id_timeins_{$r1->emp_id}",
                        "custom_searchv2"           => "timeins_id_{$r1->employee_time_in_id}",
                        "custom_searchv3"           => "emp_id_timeins_date_{$r1->emp_id}_{$r1->date}"
                        );
                array_push($row_array, $wd);
            }
        }

        return $row_array;
    }

    public function check_emp_no($emp_no,$company_id=""){
        if($company_id){
            $w = array(
                'a.payroll_cloud_id'=>$emp_no,
                'a.user_type_id'=>'5',
                'e.company_id'=>$company_id
            );
        }else{
            $w = array(
                'a.payroll_cloud_id'=>$emp_no,
                'a.user_type_id'=>'5',
            );
        }

        $this->edb->where($w);
        $this->edb->join("employee AS e","e.account_id = a.account_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","LEFT");
        $q = $this->edb->get('accounts AS a');
        
        return ($q->num_rows() > 0) ? $q->row() : FALSE ;
    }

    public function check_current_date_start($emp_id,$barack_date_trap_exact_date,$comp_id,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_work_schedule,$get_all_regular_schedule){
        $rsched_next            = array();
        $kkey                   = konsum_key();

        $rnxt                   = in_array_custom("emp_id_{$emp_id}_{$barack_date_trap_exact_date}",$emp_work_schedule_ess);

        if($rnxt){
            $worksched          = in_array_custom("worksched_id_{$rnxt->work_schedule_id}",$get_work_schedule);
            $rsched_next        = $this->check_shift_correct_sched($rnxt->work_schedule_id,$worksched->work_type_name,$barack_date_trap_exact_date,$get_all_regular_schedule);
        }else{
            $rnxt               = in_array_custom("emp_id_{$emp_id}",$emp_work_schedule_epi);
            if($rnxt){
                $worksched      = in_array_custom("worksched_id_{$rnxt->work_schedule_id}",$get_work_schedule);
                $rsched_next    = $this->check_shift_correct_sched($rnxt->work_schedule_id,$worksched->work_type_name,$barack_date_trap_exact_date,$get_all_regular_schedule);
            }
        }
        return $rsched_next;
    }

    public function check_shift_correct_sched($work_schedule_id,$work_type_name,$barack_day_trap_exact_date,$get_all_regular_schedule){

        $rsched                 = (object)array(
                                'work_start_time'           => '',
                                'work_end_time'             => '',
                                'total_work_hours'          => '',
                                'break_in_min'              => '',
                                'latest_time_in_allowed'    => ''
                                );
        $day                    = date('l',strtotime($barack_day_trap_exact_date));
        if($work_type_name == "Uniform Working Days"){
            $rsched             = in_array_custom("rsched_id_{$work_schedule_id}_{$day}",$get_all_regular_schedule);
        }
        else if($work_type_name == "Flexible Hours"){
            //wala
        }
        else if($work_type_name == "Workshift"){
            //wala
        }
        return $rsched;
    }

    public function emp_work_schedule_ess($check_company_id,$emp_ids = ""){
        $row_array  = array();
        $s          = array(
            "ess.work_schedule_id",
            "ess.valid_from",
            "ess.until",
            "ess.emp_id"
        );

        $w_emp = array(
            "ess.company_id"        => $check_company_id,
            "ess.status"            =>"Active",
            "ess.payroll_group_id"  => 0
        );
        if($emp_ids){
            $this->db->where_in("ess.emp_id",$emp_ids);
        }
        $this->edb->select($s);
        $this->edb->where($w_emp);
        $q_emp = $this->edb->get("employee_shifts_schedule AS ess");
        $r_emp = $q_emp->result();

        if($r_emp){
            foreach ($r_emp as $r1){
                $wd     = array(
                        "work_schedule_id"  => $r1->work_schedule_id,
                        "valid_from"        => $r1->valid_from,
                        "until"             => $r1->until,
                        "emp_id"            => $r1->emp_id,
                        "custom_search"     => "emp_id_{$r1->emp_id}_{$r1->valid_from}"
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }

    public function get_employee_payroll_information($company_id,$emp_ids=""){
        $row_array  = array();
        $w = array(
            //"emp_id"=>$emp_id
            "company_id" => $company_id
        );
        $this->db->where("status","Active");
        $this->db->where($w);
        if($emp_ids){
            $this->db->where_in("emp_id",$emp_ids);
        }
        $q = $this->db->get("employee_payroll_information");
        $r = $q->result();
        if($r){
            foreach ($r as $r1){
                $wd     = array(
                        "employee_payroll_information_id"   => $r1->employee_payroll_information_id,
                        "emp_id"                            => $r1->emp_id,
                        "department_id"                     => $r1->department_id,
                        "sub_department_id"                 => $r1->sub_department_id,
                        "rank_id"                           => $r1->rank_id,
                        "payroll_group_id"                  => $r1->payroll_group_id,
                        "employment_type"                   => $r1->employment_type,
                        "position"                          => $r1->position,
                        "date_hired"                        => $r1->date_hired,
                        "last_date"                         => $r1->last_date,
                        "tax_status"                        => $r1->tax_status,
                        "payment_method"                    => $r1->payment_method,
                        "bank_route"                        => $r1->bank_route,
                        "bank_account"                      => $r1->bank_account,
                        "account_type"                      => $r1->account_type,
                        "default_project"                   => $r1->default_project,
                        "sss_contribution_amount"           => $r1->sss_contribution_amount,
                        "hdmf_contribution_amount"          => $r1->hdmf_contribution_amount,
                        "philhealth_contribution_amount"    => $r1->philhealth_contribution_amount,
                        "witholding_tax"                    => $r1->witholding_tax,
                        "cost_center"                       => $r1->cost_center,
                        "project_id"                        => $r1->project_id,
                        "location_base_login_approval_grp"  => $r1->location_base_login_approval_grp,
                        "custom_search"                     => "emp_id_{$r1->emp_id}"
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }

    public function emp_work_schedule_epi($check_company_id,$emp_ids = ""){
        $row_array  = array();
        $s          = array(
                    "pg.work_schedule_id",
                    "epi.emp_id"
                    );
        $w = array(
            'epi.company_id'=> $check_company_id
        );
        $this->db->where($w);
        if($emp_ids){
            $this->db->where_in("epi.emp_id",$emp_ids);
        }
        $this->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','LEFT');
        $q_pg = $this->edb->get('employee_payroll_information AS epi');
        $r_pg = $q_pg->result();
        if($r_pg){
            foreach ($r_pg as $r1){
                $wd     = array(
                        "work_schedule_id"  => $r1->work_schedule_id,
                        "emp_id"            => $r1->emp_id,
                        "custom_search"     => "emp_id_{$r1->emp_id}"
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }

    function get_work_schedule($company_id){
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
            "enable_additional_breaks",
            "num_of_additional_breaks",
            "break_type_2",
            "track_break_2",
            "break_schedule_2",
            "additional_break_started_after_1",
            "additional_break_started_after_2",
            "enable_shift_threshold",
            "enable_grace_period",
            "tardiness_rule",
            "enable_breaks_on_holiday",
            "flag_migrate",
            );
        $this->db->select($s);
        $where = array(
                'comp_id'      => $company_id,
        );
        $this->db->where($where);
        $q = $this->db->get('work_schedule');
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
                        "enable_additional_breaks"          => $r1->enable_additional_breaks,
                        "num_of_additional_breaks"          => $r1->num_of_additional_breaks,
                        "break_type_2"                      => $r1->break_type_2,
                        "track_break_2"                     => $r1->track_break_2,
                        "break_schedule_2"                  => $r1->break_schedule_2,
                        "additional_break_started_after_1"  => $r1->additional_break_started_after_1,
                        "additional_break_started_after_2"  => $r1->additional_break_started_after_2,
                        "enable_shift_threshold"            => $r1->enable_shift_threshold,
                        "enable_grace_period"               => $r1->enable_grace_period,
                        "tardiness_rule"                    => $r1->tardiness_rule,
                        "enable_breaks_on_holiday"          => $r1->enable_breaks_on_holiday,
                        "flag_migrate"                      => $r1->flag_migrate,
                        "custom_search"                     => "worksched_id_{$r1->work_schedule_id}",
                        "custom_searchv2"                   => "worksched_migrate_{$r1->default}_{$r1->flag_migrate}",
                        );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }

    public function get_all_regular_schedule($company_id){
        $row_array  = array();
        $s_uwd      = array(
                    'reg_work_sched_id',
                    'payroll_group_id',
                    'work_schedule_id',
                    'work_schedule_name',
                    'days_of_work',
                    'work_start_time',
                    'work_end_time',
                    'total_work_hours',
                    'break_in_min',
                    'allow_flexible_workhours',
                    'latest_time_in_allowed',
                    'flag_custom',
                    'break_1',
                    'break_2',
                    'flag_half_day',
                    );
        $w_uwd      = array(
                    "company_id"    => $company_id,
                    'status'        => 'Active'
                    );
        $this->db->select($s_uwd);
        $this->db->where($w_uwd);
        $q_uwd = $this->db->get("regular_schedule");
        $result = $q_uwd->result();
        if($result){
            foreach ($result as $r1){
                $wd     = array(
                        "reg_work_sched_id"         => $r1->reg_work_sched_id,
                        "payroll_group_id"          => $r1->payroll_group_id,
                        "work_schedule_id"          => $r1->work_schedule_id,
                        "work_schedule_name"        => $r1->work_schedule_name,
                        "days_of_work"              => $r1->days_of_work,
                        "work_start_time"           => $r1->work_start_time,
                        "work_end_time"             => $r1->work_end_time,
                        "total_work_hours"          => $r1->total_work_hours,
                        "break_in_min"              => $r1->break_in_min,
                        "allow_flexible_workhours"  => $r1->allow_flexible_workhours,
                        "latest_time_in_allowed"    => $r1->latest_time_in_allowed,
                        "flag_custom"               => $r1->flag_custom,
                        "break_1"                   => $r1->break_1,
                        "break_2"                   => $r1->break_2,
                        "custom_search"             => "rsched_id_{$r1->work_schedule_id}_{$r1->days_of_work}"
                        );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }

    public function get_company_address($comp_id){              
        $ip     = get_ip();
        $w_emp  = array(
                'name' => 'lo.name'
        );
        
        $this->edb->select($w_emp);
        
        $this->edb->where('eid.company_id',$comp_id);
        $this->edb->where('ip_address',$ip);
        $this->edb->join("location_and_offices AS lo","lo.location_and_offices_id = eid.location_and_offices_id","LEFT");
        $q = $this->edb->get('employee_ip_address AS eid');
        $result = $q->row();
        
        if($result){
            return $result->name;
        }
        else{
            $w_emp = array(
                    'business_address'
            );
            $this->edb->select($w_emp);
            
            $this->edb->where('company_id',$comp_id);
            $q = $this->edb->get('company');
            $result = $q->row();
            return ($result) ? $result->business_address : "";
        }
    }

    public function list_of_blocks($company_id,$emp_ids = ""){
        // revised new split way of saving -- 06/05/17
        $row_array  = array();
        $select     = array(
                    "emp_schedule_block_id",
                    "shifts_schedule_id",
                    "work_schedule_id",
                    "schedule_blocks_id",
                    "valid_from",
                    "until",
                    "emp_id",
                    "company_id"
                    );

        $w_ws       = array(
                    "em.company_id" => $company_id,
                    );
        $this->db->select($select);
        $this->db->where($w_ws);
        if($emp_ids){
            $this->db->where_in("em.emp_id",$emp_ids);
        }
        $q_ws = $this->db->get("employee_sched_block AS em");
        $r_ws = $q_ws->result();
        if($r_ws){
            foreach ($r_ws as $r1){
                $wd     = array(
                        "emp_schedule_block_id" => $r1->emp_schedule_block_id,
                        "shifts_schedule_id"    => $r1->shifts_schedule_id,
                        "work_schedule_id"      => $r1->work_schedule_id,
                        "schedule_blocks_id"    => $r1->schedule_blocks_id,
                        "valid_from"            => $r1->valid_from,
                        "until"                 => $r1->until,
                        "emp_id"                => $r1->emp_id,
                        "company_id"            => $r1->company_id,
                        "custom_search"         => "list_{$r1->emp_id}_{$r1->valid_from}_{$r1->work_schedule_id}"
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }

    public function get_all_schedule_blocks_time_in($company_id,$emp_ids="",$min_range="",$max_range=""){
        $row_array  = array();
        $arrx   = array(
                'schedule_blocks_time_in_id',
                'employee_time_in_id',
                'work_schedule_id',
                'split_schedule_id',
                'schedule_blocks_id',
                'emp_id',
                'date',
                'time_in',
                'lunch_out',
                'lunch_in',
                'time_out',
                'total_hours',
                'total_hours_required',
                'corrected',
                'reason',
                'time_in_status',
                'overbreak_min',
                'late_min',
                'tardiness_min',
                'undertime_min',
                'source',
                'absent_min',
                'flag_time_in',
                'flag_tardiness_undertime',
                'flag_halfday',
                'date_halfday',
                'approval_time_in_id',
                );
        
        $this->db->select($arrx);
        $w      = array(
                "status"                => "Active",
                "comp_id"               => $company_id
                );

        $this->db->where("(time_in_status = 'approved' OR time_in_status is NULL)",FALSE,FALSE);
        $this->db->where($w);

        if($emp_ids){
            $this->db->where_in("emp_id",$emp_ids);
        }

        if($min_range != "" && $max_range != ""){
            $w1 = array(
                    "date >="   => $min_range,
                    "date <="   => $max_range
            );
            $this->db->where($w1);
        }
        $this->db->order_by("time_in","DESC");
        $q = $this->db->get("schedule_blocks_time_in");
        $result = $q->result();

        if($result){
            foreach ($result as $key =>$r1){
                $wd     = array(
                        "schedule_blocks_time_in_id"    => $r1->schedule_blocks_time_in_id,
                        "employee_time_in_id"           => $r1->employee_time_in_id,
                        "work_schedule_id"              => $r1->work_schedule_id,
                        "split_schedule_id"             => $r1->split_schedule_id,
                        "schedule_blocks_id"            => $r1->schedule_blocks_id,
                        "emp_id"                        => $r1->emp_id,
                        "date"                          => $r1->date,
                        "time_in"                       => $r1->time_in,
                        "lunch_out"                     => $r1->lunch_out,
                        "lunch_in"                      => $r1->lunch_in,
                        "time_out"                      => $r1->time_out,
                        "reason"                        => $r1->reason,
                        "total_hours"                   => $r1->total_hours,
                        "total_hours_required"          => $r1->total_hours_required,
                        "corrected"                     => $r1->corrected,
                        "reason"                        => $r1->reason,
                        "time_in_status"                => $r1->time_in_status,
                        "overbreak_min"                 => $r1->overbreak_min,
                        "late_min"                      => $r1->late_min,
                        "tardiness_min"                 => $r1->tardiness_min,
                        "undertime_min"                 => $r1->undertime_min,
                        "source"                        => $r1->source,
                        "absent_min"                    => $r1->absent_min,
                        "flag_time_in"                  => $r1->flag_time_in,
                        "flag_tardiness_undertime"      => $r1->flag_tardiness_undertime,
                        "flag_halfday"                  => $r1->flag_halfday,
                        "date_halfday"                  => $r1->date_halfday,
                        "approval_time_in_id"           => $r1->approval_time_in_id,
                        "custom_search"                 => "emp_id_sched_id_{$r1->emp_id}_{$r1->schedule_blocks_id}_{$r1->employee_time_in_id}_{$r1->date}",
                        "custom_searchv2"               => "sched_id_{$r1->schedule_blocks_time_in_id}",
                        "custom_searchv3"               => "emp_id_sched_id3_{$r1->emp_id}_{$r1->employee_time_in_id}_{$r1->date}",
                        "custom_searchv4"               => "emp_id_sched_id4_{$r1->employee_time_in_id}",
                        "custom_searchv5"               => "emp_id_sched_id5_{$r1->emp_id}",
                        "custom_searchv6"               => "emp_id_sched_id6_{$r1->emp_id}_{$r1->date}",
                        );
                array_push($row_array, $wd);
            }
        }
        return $row_array;
    }

    public function new_get_splitinfo($comp_id,$work_schedule_id,$emp_id="",$gDate="",$time_in="",$get_all_employee_timein,$list_of_blocks,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in){

        
        //*** BARACK NEW LOGIC GET SPLIT BLOCK SCHEDUL DEPENDING CURRENT TIME ***//
        //** First (current datetime) specify which block belong
        //** if block is specify, check if (cdt) [timein or timeOut] [lunchOut or lunchIn],
        //** if (cdt) is not belong to block find the nearest block,
        //** if block is specify, check if (cdt) timein or timeOut,
        
        $current_date           = ($gDate) ? $gDate : date("Y-m-d");
        $current_date_str       = strtotime($current_date);
        $prev_date              = ($gDate) ? $gDate : date("Y-m-d");
        $prev_date_str          = strtotime($prev_date);
        $prev_out               = true;
        $hoursxz                = 0;
        $current_time           = ($time_in) ? date("H:i:s", strtotime($time_in)) : date("H:i:s");
        $current_datetime       = ($time_in) ? $time_in : date("Y-m-d H:i:s");
        $current_datetime_str   = strtotime($current_datetime);
        $get_sched              = false;
        $schedule_blocks_id     = "";
        $break                  = 0;
        $total_hour_block_sched = 0;
        $r                      = "";

        $start_time = "";
        $end_time = "";
        $block_name = "";
        $last_block = "";
        $clock_type = "";
        $total_h_p_block = "";

        $emp_timeins    = in_array_foreach_custom("emp_id_timeins_{$emp_id}",$get_all_employee_timein); 
        if($emp_timeins){
            $r          = reset($emp_timeins);
        }

        if($r){
            // if wala pa sa db na save ang current date
            $prev_date      = $r->date;
            $prev_out       = $r->time_out;
            $prev_date_str  = strtotime($prev_date);

            $totalxz        = strtotime($current_date) - strtotime($prev_date);
            $hoursxz        = floor($totalxz / 60 / 60);
        }
        if($prev_date_str >= $current_date_str){
            $prev_date      = $current_date;
            $prev_date_str  = strtotime($prev_date);
        }
        if($prev_date_str == $current_date_str){
            // if naa na sa db ang current date
            $prev_date  = date('Y-m-d', strtotime($prev_date." -1 day"));
        }


        $r_ws = array();


        if(!$prev_out && ($hoursxz <= 24)){
            // kung wala pa na ka out sa last check prev day
            $current_date_prev = date("Y-m-d",strtotime($current_date. "-1 day"));
            $r_ws = array();

            $list_blocks_prev = in_array_foreach_custom("list_{$emp_id}_{$current_date_prev}_{$work_schedule_id}",$list_of_blocks);

            if($list_blocks_prev){
                foreach ($list_blocks_prev as $key => $value) {
                    $schedule_blocks_id = $value->schedule_blocks_id;
                    $result_schedule_blocks_prev = in_array_custom("sched_id_{$schedule_blocks_id}",$get_all_schedule_blocks);
                    if($result_schedule_blocks_prev){

                        $haystack   = array(
                                    'emp_schedule_block_id'         => $value->emp_schedule_block_id,
                                    'schedule_blocks_id'            => $result_schedule_blocks_prev->schedule_blocks_id,
                                    'emp_id'                        => $value->emp_id,
                                    'valid_from'                    => $value->valid_from,
                                    'until'                         => $value->until,
                                    'block_name'                    => $result_schedule_blocks_prev->block_name,
                                    'start_time'                    => $result_schedule_blocks_prev->start_time,
                                    'end_time'                      => $result_schedule_blocks_prev->end_time,
                                    'break_in_min'                  => $result_schedule_blocks_prev->break_in_min,
                                    'total_hours_work_per_block'    => $result_schedule_blocks_prev->total_hours_work_per_block,
                                    'location_and_offices_id'       => $result_schedule_blocks_prev->location_and_offices_id,
                                    'enable_breaks_on_holiday'      => $result_schedule_blocks_prev->enable_breaks_on_holiday,
                                    );

                        array_push($r_ws, (object) $haystack);
                    }
                }
            }

            if($r_ws){

                //*** THIS IS TO CALCULATE FOR THE BOUNDARY OF THE SPLIT SCHED --> CREATE NEW LOGS
                $first  = reset($r_ws);
                $last   = end($r_ws);
            
                $first_block_start_time         = $first->start_time;
                $first_block_start_time_str     = strtotime($first->start_time);
                $last_block_end_time            = $last->end_time;
                $last_block_id                  = $last->schedule_blocks_id;
                $last_block_end_time_str        = strtotime($last->end_time);
                $first_block_start_datetime     = date('Y-m-d H:i:s', strtotime($prev_date." ".$first_block_start_time. "+1 day"));

                if($first_block_start_time_str > $last_block_end_time_str){

                    $last_block_end_datetime    = date('Y-m-d H:i:s', strtotime($prev_date." ".$last_block_end_time." +1 day"));
                }
                else{
                    $last_block_end_datetime    = date('Y-m-d H:i:s', strtotime($prev_date." ".$last_block_end_time));
                }
                
                //*** get the difference between the first start time and the last end time
                
                $total                          = strtotime($first_block_start_datetime) - strtotime($last_block_end_datetime);
                $hours                          = floor($total / 60 / 60);
                $minutes                        = floor(($total - ($hours * 60 * 60)) / 60);
                $ret                            = ($hours * 60) + $minutes;
                $total_gap                      = ($ret < 0) ? '0' : $ret;
                $total_gap                      = $total_gap/2;
                if($total_gap > 120){
                    $total_gap                  = 120;
                }
                
                $first_block_start_datetimex        = date('Y-m-d H:i:s',strtotime($first_block_start_datetime));
                $first_block_boundary_datetime      = date('Y-m-d H:i:s',strtotime($first_block_start_datetimex . "-".$total_gap." minutes"));
                $first_block_boundary_datetime_str  = strtotime($first_block_boundary_datetime);
                
                if($current_datetime < $first_block_boundary_datetime){
                    $current_date = $current_date_prev;
                }
            }   
        }
        
        //*** DISPLAY ALL SPLIT BLOCK SCHEDULE ***//

        # get_all_schedule_blocks
        # list_of_blocks

        $list_blocks = in_array_foreach_custom("list_{$emp_id}_{$current_date}_{$work_schedule_id}",$list_of_blocks);

        if($list_blocks){
            foreach ($list_blocks as $key => $value) {
                $schedule_blocks_id = $value->schedule_blocks_id;
                $result_schedule_blocks = in_array_custom("sched_id_{$schedule_blocks_id}",$get_all_schedule_blocks);
                if($result_schedule_blocks){

                    $haystack   = array(
                                'emp_schedule_block_id'         => $value->emp_schedule_block_id,
                                'schedule_blocks_id'            => $result_schedule_blocks->schedule_blocks_id,
                                'emp_id'                        => $value->emp_id,
                                'valid_from'                    => $value->valid_from,
                                'until'                         => $value->until,
                                'block_name'                    => $result_schedule_blocks->block_name,
                                'start_time'                    => $result_schedule_blocks->start_time,
                                'end_time'                      => $result_schedule_blocks->end_time,
                                'break_in_min'                  => $result_schedule_blocks->break_in_min,
                                'total_hours_work_per_block'    => $result_schedule_blocks->total_hours_work_per_block,
                                'location_and_offices_id'       => $result_schedule_blocks->location_and_offices_id,
                                'enable_breaks_on_holiday'      => $result_schedule_blocks->enable_breaks_on_holiday,
                                );

                    array_push($r_ws, (object) $haystack);
                }
            }
        }


        if($r_ws){
            //*** THIS IS TO CALCULATE FOR THE BOUNDARY OF THE SPLIT SCHED --> CREATE NEW LOGS
            $first  = reset($r_ws);
            $last   = end($r_ws);
        
            $first_block_start_time         = $first->start_time;
            $first_block_start_time_str     = strtotime($first->start_time);
            $last_block_end_time            = $last->end_time;
            $last_block_id                  = $last->schedule_blocks_id;
            $last_block_end_time_str        = strtotime($last->end_time);
            $first_block_start_datetime     = date('Y-m-d H:i:s', strtotime($prev_date." ".$first_block_start_time." +1 day"));

            if($first_block_start_time_str > $last_block_end_time_str){

                $last_block_end_datetime    = date('Y-m-d H:i:s', strtotime($prev_date." ".$last_block_end_time." +1 day"));
            }
            else{
                $last_block_end_datetime    = date('Y-m-d H:i:s', strtotime($prev_date." ".$last_block_end_time));
            }
            
            //*** get the difference between the first start time and the last end time
            $total      = strtotime($first_block_start_datetime) - strtotime($last_block_end_datetime);
            $hours      = floor($total / 60 / 60);
            $minutes    = floor(($total - ($hours * 60 * 60)) / 60);
            $ret        = ($hours * 60) + $minutes;
            $total_gap  = ($ret < 0) ? '0' : $ret;
            $total_gap  = $total_gap/2;
            if($total_gap > 120){
                $total_gap = 120;
            }
            $first_block_start_datetimex        = date('Y-m-d H:i:s',strtotime($first_block_start_datetime . "+1 day"));
            $first_block_boundary_datetime      = date('Y-m-d H:i:s',strtotime($first_block_start_datetimex . "-".$total_gap." minutes"));
            $first_block_boundary_datetime_str  = strtotime($first_block_boundary_datetime);
            
            // if($first_block_boundary_datetime_str > $current_datetime_str){
            //     $current_date = $prev_date;
            // }
            
            $current_date_str   = strtotime($current_date);
            $prev_end_time      = $last_block_end_datetime;
            
            // init -> the last timesheet of the employee
            $time_is_check              = $r;
            $eti                        = ($time_is_check) ? $time_is_check->employee_time_in_id : "";
            $schedule_blocks_id_prev    = "";
            $break_prev                 = "";
            $eti_prev                   = "";
            $clock_type_prev            = "";
            $start_time_prev            = "";
            $end_time_prev              = "";
            $total_h_p_block_prev       = "";
            $block_name_prev            = "";
            $last_block_prev            = "";
            $enable_breaks_on_holiday   = "";
            
            // get total hours in split blocks
            foreach ($r_ws AS $rws){
                $total_hour_block_sched = $total_hour_block_sched + $rws->total_hours_work_per_block;
            }
            
            foreach ($r_ws AS $rws){
                
                $prev_end_time_str          = strtotime($prev_end_time);
                $start_time                 = $rws->start_time;
                $end_time                   = $rws->end_time;
                $start_time_str             = strtotime($start_time);
                $end_time_str               = strtotime($end_time);
                $enable_breaks_on_holiday   = $rws->enable_breaks_on_holiday;
                
                if($first_block_start_time_str > $start_time_str){
                    $current_date   = date('Y-m-d', strtotime($current_date." +1 day"));
                }
                
                $start_datetime = date('Y-m-d H:i:s', strtotime($current_date." ".$start_time));
                    
                if($start_time_str > $end_time_str){
                    $end_datetime   = date('Y-m-d H:i:s', strtotime($current_date." ".$end_time." +1 day"));
                }else{
                    $end_datetime   = date('Y-m-d H:i:s', strtotime($current_date." ".$end_time));
                }
                
                $start_datetime_str = strtotime($start_datetime);
                $end_datetime_str   = strtotime($end_datetime);
                
                if(($current_datetime_str >= $start_datetime_str) && ($current_datetime_str <= $end_datetime_str)){
                    
                    $schedule_blocks_id = $rws->schedule_blocks_id;
                    $break              = $rws->break_in_min;
                    $clock_type         = $this->check_clock_type($time_is_check,$emp_id,$comp_id,$eti,$schedule_blocks_id,$current_date,$break,$get_all_schedule_blocks_time_in);
                    $start_time         = $rws->start_time;
                    $end_time           = $rws->end_time;
                    $total_h_p_block    = $rws->total_hours_work_per_block;
                    $block_name         = $rws->block_name;
                    $last_block         = $last_block_id;
                    break;
                }
                
                else if(($current_datetime_str >= $prev_end_time_str) && ($current_datetime_str <= $start_datetime_str)){
                    
                    $tx         = strtotime($start_datetime) - strtotime($prev_end_time);
                    $hx         = floor($tx / 60 / 60);
                    $mx         = floor(($tx - ($hx * 60 * 60)) / 60);
                    $retx       = ($hx * 60) + $mx;
                    $tgx        = ($retx < 0) ? '0' : $retx;
                    $tgx        = $tgx/2;
                    if($tgx > 120){
                        $tgx = 120;
                    }
                    $between_block_boundary     = date('Y-m-d H:i:s',strtotime($start_datetime. "-".$tgx." minutes"));
                    $between_block_boundary_str = strtotime($between_block_boundary);
                    
                    if($between_block_boundary_str <= $current_datetime_str){
                        
                        $schedule_blocks_id     = $rws->schedule_blocks_id;
                        $break                  = $rws->break_in_min;
                        $clock_type             = $this->check_clock_type($time_is_check,$emp_id,$comp_id,$eti,$schedule_blocks_id,$current_date,$break,$get_all_schedule_blocks_time_in);
                        $start_time             = $rws->start_time;
                        $end_time               = $rws->end_time;
                        $total_h_p_block        = $rws->total_hours_work_per_block;
                        $block_name             = $rws->block_name;
                        $last_block             = $last_block_id;
                    }
                    else{
                        
                        $schedule_blocks_id     = $schedule_blocks_id_prev;
                        $break                  = $break_prev;
                        $eti                    = $eti_prev;
                        $clock_type             = $this->check_clock_type($time_is_check,$emp_id,$comp_id,$eti_prev,$schedule_blocks_id_prev,$current_date,$break_prev,$get_all_schedule_blocks_time_in);
                        $start_time             = $start_time_prev;
                        $end_time               = $end_time_prev;
                        $total_h_p_block        = $total_h_p_block_prev;
                        $block_name             = $block_name_prev;
                        $last_block             = $last_block_prev;
                    }
                    break;
                }
                else if(($current_datetime_str < $first_block_boundary_datetime_str) && ($last_block_id == $rws->schedule_blocks_id)){
                    
                    $schedule_blocks_id = $last_block_id;
                    $break              = $rws->break_in_min;
                    $clock_type         = $this->check_clock_type($time_is_check,$emp_id,$comp_id,$eti,$rws->schedule_blocks_id,$current_date,$break,$get_all_schedule_blocks_time_in);
                    $start_time         = $rws->start_time;
                    $end_time           = $rws->end_time;
                    $total_h_p_block    = $rws->total_hours_work_per_block;
                    $block_name         = $rws->block_name;
                    $last_block         = $last_block_id;
                    break;
                }
                
                $schedule_blocks_id_prev    = $rws->schedule_blocks_id;
                $break_prev                 = $rws->break_in_min;
                $eti_prev                   = $eti;
                $start_time_prev            = $rws->start_time;
                $end_time_prev              = $rws->end_time;
                $total_h_p_block_prev       = $rws->total_hours_work_per_block;
                $block_name_prev            = $rws->block_name;
                $last_block_prev            = $last_block_id;
                $prev_end_time              = $end_datetime;
                
            }
        }


        $return = array(
                'break_in_min'                  => $break,
                'start_time'                    => $start_time,
                'end_time'                      => $end_time,
                'total_hours_work_per_block'    => $total_h_p_block,
                'block_name'                    => $block_name,
                'schedule_blocks_id'            => $schedule_blocks_id,
                'last_block'                    => $last_block,
                'clock_type'                    => $clock_type,
                'first_block_start_time'        => $first_block_start_time,
                'total_hour_block_sched'        => $total_hour_block_sched,
                'enable_breaks_on_holiday'      => $enable_breaks_on_holiday,
                );

        
        return $return;
    
    }

    public function get_work_schedule_flex($company_id){
        $row_array  = array();
        $s = array(
            'workday_settings_id',
            'payroll_group_id',
            'not_required_login',
            'total_hours_for_the_day',
            'total_hours_for_the_week',
            'total_days_per_year',
            'latest_time_in_allowed',
            'number_of_breaks_per_day',
            'duration_of_lunch_break_per_day',
            'break1',
            'break2',
            'break3',
            'break4',
            'duration_of_short_break_per_day',
            'work_schedule_id',

            );
        $w = array(
                "company_id"=>$company_id
            );
        $this->db->select($s);
        $this->db->where($w);
        $q      = $this->db->get("flexible_hours");
        $result = $q->result();
        if($result){
            foreach ($result as $r1){
                $wd     = array(
                        "workday_settings_id"               => $r1->workday_settings_id,
                        "payroll_group_id"                  => $r1->payroll_group_id,
                        "not_required_login"                => $r1->not_required_login,
                        "total_hours_for_the_day"           => $r1->total_hours_for_the_day,
                        "total_hours_for_the_week"          => $r1->total_hours_for_the_week,
                        "total_days_per_year"               => $r1->total_days_per_year,
                        "latest_time_in_allowed"            => $r1->latest_time_in_allowed,
                        "number_of_breaks_per_day"          => $r1->number_of_breaks_per_day,
                        "duration_of_lunch_break_per_day"   => $r1->duration_of_lunch_break_per_day,
                        "break1"                            => $r1->break1,
                        "break2"                            => $r1->break2,
                        "break3"                            => $r1->break3,
                        "break4"                            => $r1->break4,
                        "duration_of_short_break_per_day"   => $r1->duration_of_short_break_per_day,
                        "work_schedule_id"                  => $r1->work_schedule_id,
                        "custom_search"                     => "flex_{$r1->work_schedule_id}",
                        "custom_searchv2"                   => "flex_v2_{$r1->payroll_group_id}"
                        );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }

    function check_leave_appliction($date,$emp_id,$company_id,$get_employee_leave_application,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_work_schedule,$get_all_regular_schedule,$get_work_schedule_flex,$get_all_schedule_blocks){
        $datex = date("Y-m-d",strtotime($date));
        
        $r = in_array_custom("leave_app_{$emp_id}_{$datex}",$get_employee_leave_application);
        if($r){
            
            $credits            = $r->credited * 8;
            $work_schedule_id   = "";

            $emp_work_schedule_ess1 = in_array_custom("emp_id_{$emp_id}_{$date}",$emp_work_schedule_ess);
            if($emp_work_schedule_ess1){
                $work_schedule_id = $emp_work_schedule_ess1->work_schedule_id;
            }
            else{
                $emp_work_schedule_epi1 = in_array_custom("emp_id_{$emp_id}",$emp_work_schedule_epi);
                if($emp_work_schedule_epi1){
                    $work_schedule_id = $emp_work_schedule_epi1->work_schedule_id;
                }
            }

            $workday = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);

            if($workday != FALSE){
                
                $weekday = date('l',strtotime($date));
                switch ($workday->workday_type) {
                
                    case 'Uniform Working Days':

                        $w                  = in_array_custom("rsched_id_{$work_schedule_id}_{$weekday}",$get_all_regular_schedule);
                        $hp_total_hours     = ($w) ? $w->total_work_hours : 0 ;
                        return ($credits >= $hp_total_hours) ? TRUE : FALSE ;
                        break;
                
                    case 'Flexible Hours':
                
                        $w                  = in_array_custom("flex_{$work_schedule_id}",$get_work_schedule_flex);
                        $hp_total_hours     = ($w) ? $w->total_hours_for_the_day : 0 ;
                        return ($credits >= $hp_total_hours) ? TRUE : FALSE ;
                        break;
                
                    case 'Workshift':

                        $w                  = in_array_foreach_custom("worksched_id_{$work_schedule_id}",$get_all_schedule_blocks);
                        $total_work_hours   = 0;
                        if($w){
                            foreach ($w as $key => $w1) {
                                $total_work_hours = $total_work_hours + $w1->total_hours_work_per_block;
                            }
                        }
                        $hp_total_hours = ($total_work_hours) ? $total_work_hours : 0 ;
                        return ($credits >= $hp_total_hours) ? TRUE : FALSE ;
                        break;
                }
            }

        }
        else{
            return FALSE;
        }
    }

    function get_employee_leave_application($company_id,$emp_ids=""){
        $row_array  = array();
        $s  = array(
            "ela.employee_leaves_application_id",
            "ela.emp_id",
            "ela.work_schedule_id",
            "ela.leave_type_id",
            "ela.reasons",
            "ela.shift_date",
            "ela.date_start",
            "ela.date_end",
            "ela.date_return",
            "ela.date_filed",
            "ela.duration",
            "ela.total_leave_requested",
            "ela.leave_cedits_for_untitled",
            "ela.leave_application_status",
            "ela.attachments",
            "ela.approval_date",
            "ela.leaves_id",
            "ela.flag_parent",
            "ela.credited",
            "ela.non_credited",
            "ela.remaining_credits",
            "ela.timestamp_paid_leave",
            "ela.required_file_documents",
            "ela.approver_account_id",
            "ela.existing_leave_used_to_date",
            "ela.previous_credits",
            "lt.leave_type",
            "lt.description",
            "lt.payable",
            "lt.days_lead_time_prior_to_leave",
            "lt.accrued_every_period",
            "lt.what_happen_to_unused_leave",
            "lt.leave_conversion_run_every",
            "lt.paid_leave",
            "lt.leave_units"
            );
        $w  = array(
            "ela.status"                    => "Active",
            //"ela.emp_id"                  => $emp_id,
            "ela.company_id"                => $company_id,
            "ela.leave_application_status"  => "approve",
            //"DATE(ela.date_start)"        => date("Y-m-d",strtotime($date))
        );
        if($emp_ids){
            $this->db->where_in("ela.emp_id",$emp_ids);
        }
        $this->db->where($w);
        $this->db->where("(ela.flag_parent != 'yes' OR ela.flag_parent = 'no' OR ela.flag_parent IS NULL)");
        $this->db->join("leave_type AS lt","lt.leave_type_id = ela.leave_type_id","LEFT");
        $q = $this->db->get("employee_leaves_application AS ela");
        $result = $q->result();
        if($result){
            foreach ($result as $r1){
                $date_start = date("Y-m-d",strtotime($r1->shift_date));
                $wd     = array(
                        "employee_leaves_application_id"    => $r1->employee_leaves_application_id,
                        "emp_id"                            => $r1->emp_id,
                        "work_schedule_id"                  => $r1->work_schedule_id,
                        "leave_type_id"                     => $r1->leave_type_id,
                        "reasons"                           => $r1->reasons,
                        "shift_date"                        => $r1->shift_date,
                        "date_start"                        => $r1->date_start,
                        "date_end"                          => $r1->date_end,
                        "date_return"                       => $r1->date_return,
                        "date_filed"                        => $r1->date_filed,
                        "duration"                          => $r1->duration,
                        "total_leave_requested"             => $r1->total_leave_requested,
                        "leave_cedits_for_untitled"         => $r1->leave_cedits_for_untitled,
                        "leave_application_status"          => $r1->leave_application_status,
                        "attachments"                       => $r1->attachments,
                        "approval_date"                     => $r1->approval_date,
                        "leaves_id"                         => $r1->leaves_id,
                        "flag_parent"                       => $r1->flag_parent,
                        "credited"                          => $r1->credited,
                        "non_credited"                      => $r1->non_credited,
                        "remaining_credits"                 => $r1->remaining_credits,
                        "timestamp_paid_leave"              => $r1->timestamp_paid_leave,
                        "required_file_documents"           => $r1->required_file_documents,
                        "approver_account_id"               => $r1->approver_account_id,
                        "existing_leave_used_to_date"       => $r1->existing_leave_used_to_date,
                        "previous_credits"                  => $r1->previous_credits,
                        "leave_type"                        => $r1->leave_type,
                        "description"                       => $r1->description,
                        "payable"                           => $r1->payable,
                        "days_lead_time_prior_to_leave"     => $r1->days_lead_time_prior_to_leave,
                        "accrued_every_period"              => $r1->accrued_every_period,
                        "what_happen_to_unused_leave"       => $r1->what_happen_to_unused_leave,
                        "leave_conversion_run_every"        => $r1->leave_conversion_run_every,
                        "paid_leave"                        => $r1->paid_leave,
                        "leave_units"                       => $r1->leave_units,
                        "custom_search"                     => "leave_app_{$r1->emp_id}_{$date_start}"
                        );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }

    public function check_workday_settings_start_time($workday,$work_schedule_id,$get_all_regular_schedule){

        $row            = in_array_custom("rsched_id_{$work_schedule_id}_{$workday}",$get_all_regular_schedule);
        if($row){
            return $row->work_start_time;
        }else{
            return FALSE;
        }
    }

    public function check_workday_settings_end_time($workday,$work_schedule_id,$get_all_regular_schedule){

        $row            = in_array_custom("rsched_id_{$work_schedule_id}_{$workday}",$get_all_regular_schedule);
        if($row){
            return $row->work_end_time;
        }else{
            return FALSE;
        }
    }

    public function late_min($comp_id,$date,$emp_id,$work_schedule_id,$time_in = "",$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in){

        # use in upload
        if($time_in){
            $current_time                       = $time_in;
        }
        else{
            $current_time                       = date('Y-m-d H:i');
        }

        $day                                    = date('l',strtotime($date));
        $r_uwd                                  = in_array_custom("rsched_id_{$work_schedule_id}_{$day}",$get_all_regular_schedule);

        if($r_uwd){
            $new_time_in                        = $current_time;
            $w_start                            = $r_uwd->work_start_time;
            $w_end                              = $r_uwd->work_end_time;
            $tresh                              = $r_uwd->latest_time_in_allowed;
            $break                              = $r_uwd->break_in_min;
            $total_work_hours                   = $r_uwd->total_work_hours;
            $start_time                         = date("Y-m-d H:i:s",strtotime($date." ".$r_uwd->work_start_time));
            $end_date                           = $date;
            $start_date                         = $date;
            $half_day                           = $total_work_hours/2;

            if($w_start > $w_end){

                $end_date                       = date('Y-m-d',strtotime($end_date. " +1 day"));
                $end_date                       = $end_date;
            }

            $start_date_time                    = date("Y-m-d H:i", strtotime($start_date." ".$w_start));
            $end_date_time                      = date("Y-m-d H:i", strtotime($end_date." ".$w_end));
            $init_start_date_time               = $start_date_time;
            $start_date_time_half               = date('Y-m-d H:i',strtotime($start_date_time. " +".$half_day." hours"));

            if($break > 0){
                $break_half                     = $break/2;
                $start_date_time_half           = date('Y-m-d H:i',strtotime($start_date_time_half. " +".$break_half." minutes"));
            }

            if($new_time_in > $start_date_time){
                if($tresh){
                    // treshold dont exist if halfday
                    if($start_date_time_half > $new_time_in){

                        $start_date_timex       = date('Y-m-d H:i',strtotime($start_date_time. " +".$tresh." minutes"));

                        if($new_time_in > $start_date_timex){

                            $start_date_time    = date('Y-m-d H:i',strtotime($start_date_time. " +".$tresh." minutes"));
                        }
                        else{
                            $min_tresh          = $this->total_hours_worked($new_time_in,$init_start_date_time);
                            $min_tresh          = ($min_tresh > 0) ? $min_tresh : 0;
                            $end_date_time      = date('Y-m-d H:i',strtotime($end_date_time. " +".$min_tresh." minutes"));
                            $start_date_time    = date('Y-m-d H:i',strtotime($start_date_time. " +".$min_tresh." minutes"));
                        }
                    }
                }
            }
            $min                                = 0;

            if($new_time_in > $start_date_time){

                $min                            = $this->total_hours_worked($new_time_in, $start_date_time);
            }

            $tardiness_set                      = $this->tardiness_settings($emp_id, $comp_id,$get_employee_payroll_information,$get_tardiness_settings);
            $tardiness_set                      = ($tardiness_set > 0) ? $tardiness_set : 0;
            $min                                = $min - $tardiness_set;

            if($break > 0){
                if($start_date_time_half < $new_time_in){
                    $min                        = $min - $break;
                }
            }

            if(($min/60) > $total_work_hours){
                $min                            = ($total_work_hours * 60);
            }

            return ($min > 0) ? $min : 0;
        }
        else{
            
            $check_work_schedule_flex = in_array_custom("flex_{$work_schedule_id}",$get_work_schedule_flex);

            if($check_work_schedule_flex){
                
                if($check_work_schedule_flex->latest_time_in_allowed){
                    $start_time = date("Y-m-d H:i:s",strtotime($date." ".$check_work_schedule_flex->latest_time_in_allowed));
                    if($current_time > $start_time){
                        $min = $this->total_hours_worked($current_time, $start_time);
                        return ($min > 0) ? $min : 0;
                    }
                }
            }else{
                    
                $r_ws = in_array_foreach_custom("list_{$emp_id}_{$date}_{$work_schedule_id}",$list_of_blocks);

                if($r_ws){
                    $split = $this->new_get_splitinfo($comp_id,$work_schedule_id,$emp_id,$date,$time_in,$get_all_employee_timein,$list_of_blocks,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);
                    if($split){
                        if($current_time > $split['start_time']){
                            $min = $this->total_hours_worked($current_time, $split['start_time']);
                            return ($min > 0) ? $min : 0;
                        }
                    }
                }
            }
        }
        return 0;
    }

    public function tardiness_settings($emp_id,$comp_id,$get_employee_payroll_information,$get_tardiness_settings){

        $r_uwd = in_array_custom("emp_id_{$emp_id}",$get_employee_payroll_information);
        if($r_uwd){
            
            $tard_qx = $get_tardiness_settings;

            if($tard_qx){
                
                foreach($tard_qx as $tard_q):
                    if($tard_q->grace_period_type == "daily"){
                        $payroll_group = explode(",",$tard_q->payroll_group_ids);
                        $rank = explode(",",$tard_q->rank_ids);

                        if(($tard_q->payroll_group_ids != "" && $tard_q->payroll_group_ids != 0) && $tard_q->rank_ids != ""){
                            
                            foreach($rank as $r){
                                foreach($payroll_group as $p){
                                    
                                    if($r == $r_uwd->rank_id && $p == $r_uwd->payroll_group_id){
                                        return $tard_q->tarmin;
                                    }
                                }
                            }
                            
                        }else{
                            
                            if($tard_q->rank_ids){
                                foreach ($rank as $r){
                                
                                    if($r == $r_uwd->rank_id){
                                        
                                        return $tard_q->tarmin;
                                    }
                                }
                            }
                        
                            if($tard_q->payroll_group_ids){
                                foreach ($payroll_group as $pg){
                                    
                                    if($pg == $r_uwd->payroll_group_id){
                                        return $tard_q->tarmin;
                                    }
                                }
                            }
                        }
                    }
                endforeach;
            }
        }
        
        return false;
    }

    public function get_tardiness_settings($company_id){

        $row_array  = array();

        $s          = array(
                    'ts.tardiness_settings_id',
                    'ts.tardiness_rule_name',
                    'ts.tarmin',
                    'ts.rank_id',
                    'ts.rank_ids',
                    'ts.payroll_group_ids',
                    'ts.grace_period_type',
                    'ts.default',
                    'ts.starts_shift_start_time',
                    'ts.starts_end_of_grace_period',
                    'ts.enable_option',
                    );

        $w_uwd      = array(
                    "ts.comp_id"            => $company_id,
                    "ts.status"             => 'Active'
                    );

        $w_uwdz     = array(
                    "tms.enable"            => "yes",
                    "tms.flag_v3_migrate"   => '1'
                    );


        $this->db->where($w_uwd);
        $this->db->where($w_uwdz);
        $this->db->select($s);
        $this->db->join("tardiness_main_settings AS tms","tms.company_id = ts.comp_id");
        $tard = $this->db->get("tardiness_settings AS ts");

        $result = $tard->result();
        if($result){
            
            foreach ($result as $key =>$r1){
                $wd     = array(
                        "tardiness_settings_id"         => $r1->tardiness_settings_id,
                        "tardiness_rule_name"           => $r1->tardiness_rule_name,
                        "tarmin"                        => $r1->tarmin,
                        "rank_id"                       => $r1->rank_id,
                        "rank_ids"                      => $r1->rank_ids,
                        "payroll_group_ids"             => $r1->payroll_group_ids,
                        "grace_period_type"             => $r1->grace_period_type,
                        "default"                       => $r1->default,
                        "starts_shift_start_time"       => $r1->starts_shift_start_time,
                        "starts_end_of_grace_period"    => $r1->starts_end_of_grace_period,
                        "enable_option"                 => $r1->enable_option,
                        "custom_search"                 => "tardi_{$r1->tardiness_settings_id}",
                        "custom_searchv2"               => "tardi_type_{$r1->grace_period_type}"
                        );

                array_push($row_array, (object) $wd);
            }
        }
        else{

            $this->db->where($w_uwd);
            $this->db->select($s);
            $tard = $this->db->get("tardiness_settings AS ts");

            $result = $tard->result();
            if($result){
                
                foreach ($result as $key =>$r1){
                    $wd     = array(
                            "tardiness_settings_id"         => $r1->tardiness_settings_id,
                            "tardiness_rule_name"           => $r1->tardiness_rule_name,
                            "tarmin"                        => $r1->tarmin,
                            "rank_id"                       => $r1->rank_id,
                            "rank_ids"                      => $r1->rank_ids,
                            "payroll_group_ids"             => $r1->payroll_group_ids,
                            "grace_period_type"             => $r1->grace_period_type,
                            "default"                       => $r1->default,
                            "starts_shift_start_time"       => $r1->starts_shift_start_time,
                            "starts_end_of_grace_period"    => $r1->starts_end_of_grace_period,
                            "enable_option"                 => $r1->enable_option,
                            "custom_search"                 => "tardi_{$r1->tardiness_settings_id}"
                            );

                    array_push($row_array, (object) $wd);
                }
            }
        }

        return $row_array;
    }

    public function check_if_migrate($last_t_worksched_id,$get_work_schedule){

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

    public function get_tardiness_settingsv2($company_id){

        $row_array  = array();

        $s          = array(
                    'ts.tardiness_settings_id',
                    'ts.tardiness_rule_name',
                    'ts.tarmin',
                    'ts.rank_id',
                    'ts.rank_ids',
                    'ts.payroll_group_ids',
                    'ts.grace_period_type',
                    'ts.default',
                    'ts.starts_shift_start_time',
                    'ts.starts_end_of_grace_period',
                    'ts.enable_option',
                    );
        $s2         = array(
                    "tms.flag_v3_migrate"
                    );

        $w_uwd      = array(
                    "ts.comp_id"            => $company_id,
                    "ts.status"             => 'Active'
                    );

        $w_uwdz     = array(
                    "tms.enable"            => "yes",
                    "tms.flag_v3_migrate"   => '1'
                    );


        $this->db->where($w_uwd);
        $this->db->where($w_uwdz);
        $this->db->where("ts.default IS NULL");
        $this->db->select($s);
        $this->db->select($s2);
        $this->db->join("tardiness_main_settings AS tms","tms.company_id = ts.comp_id");
        $tard = $this->db->get("tardiness_settings AS ts");

        $result = $tard->result();
        if($result){
            
            foreach ($result as $key =>$r1){
                $wd     = array(
                        "tardiness_settings_id"         => $r1->tardiness_settings_id,
                        "tardiness_rule_name"           => $r1->tardiness_rule_name,
                        "tarmin"                        => $r1->tarmin,
                        "rank_id"                       => $r1->rank_id,
                        "rank_ids"                      => $r1->rank_ids,
                        "payroll_group_ids"             => $r1->payroll_group_ids,
                        "grace_period_type"             => $r1->grace_period_type,
                        "default"                       => $r1->default,
                        "starts_shift_start_time"       => $r1->starts_shift_start_time,
                        "starts_end_of_grace_period"    => $r1->starts_end_of_grace_period,
                        "enable_option"                 => $r1->enable_option,
                        "flag_v3_migrate"               => $r1->flag_v3_migrate,
                        "custom_search"                 => "tardi_{$r1->tardiness_settings_id}",
                        "custom_searchv2"               => "tardi_type_{$r1->grace_period_type}"
                        );

                array_push($row_array, $wd);
            }
        }
        else{

            $this->db->where($w_uwd);
            $this->db->select($s);
            $tard = $this->db->get("tardiness_settings AS ts");

            $result = $tard->result();
            if($result){
                
                foreach ($result as $key =>$r1){
                    $wd     = array(
                            "tardiness_settings_id"         => $r1->tardiness_settings_id,
                            "tardiness_rule_name"           => $r1->tardiness_rule_name,
                            "tarmin"                        => $r1->tarmin,
                            "rank_id"                       => $r1->rank_id,
                            "rank_ids"                      => $r1->rank_ids,
                            "payroll_group_ids"             => $r1->payroll_group_ids,
                            "grace_period_type"             => $r1->grace_period_type,
                            "default"                       => $r1->default,
                            "starts_shift_start_time"       => $r1->starts_shift_start_time,
                            "starts_end_of_grace_period"    => $r1->starts_end_of_grace_period,
                            "enable_option"                 => $r1->enable_option,
                            "custom_search"                 => "tardi_{$r1->tardiness_settings_id}"
                            );

                    array_push($row_array, $wd);
                }
            }
        }

        return $row_array;
    }

    public function tardiness_rounding($company_id){

        $row_array  = array();

        $s          = array(
                    'ts.tardiness_interval_range_rounding_id',
                    'ts.from',
                    'ts.to',
                    'ts.minutes_should',
                    'ts.minutes',
                    'ts.company_id',
                    );

        $w_uwd      = array(
                    "ts.company_id"             => $company_id,
                    );

        $this->db->where($w_uwd);
        $this->db->select($s);
        $tard = $this->db->get("tardiness_interval_range_rounding AS ts");

        $result = $tard->result();
        
        if($result){

            foreach ($result as $key =>$r1){
                $wd     = array(
                        "from"                  => $r1->tardiness_interval_range_rounding_id,
                        "from"                  => $r1->from,
                        "to"                    => $r1->to,
                        "minutes_should"        => $r1->minutes_should,
                        "minutes"               => $r1->minutes,
                        "company_id"            => $r1->company_id,
                        "custom_search"             => "rounding_{$r1->tardiness_interval_range_rounding_id}"
                        );

                array_push($row_array, (object) $wd);
            }
        }

        return $row_array;
    }

    public function split_schedule_time_in($arr = array(),$split_total_activate = false,$sync_date="",$date ="",$source="kiosk",$first_block_start_time="",$check_type_o="",$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$company_holiday,$get_work_schedule, $location=""){
        
        // Set data here
        $emp_no                     = $arr['emp_no'];
        $current_date               = $arr['current_date'];
        $emp_id                     = $arr['emp_id'];
        $comp_id                    = $arr['comp_id'];
        $check_type                 = $arr['check_type'];
        $min_log                    = $arr['min_log'];
        $breaks                     = $arr['breaks'];
        $get_diff                   = $arr['get_diff'];
        $employee_time_in_id        = $arr['employee_time_in_id'];
        $work_schedule_id           = $arr['work_schedule_id'];
        $block_id                   = $arr['block_id'];
        $schedule_blocks_id         = $arr['schedule_blocks_id'];
        $time_in                    = $arr['time_in'];
        $time_out                   = $arr['time_out'];
        $lunch_in                   = $arr['lunch_in'];
        $lunch_out                  = $arr['lunch_out'];
        $new_timein                 = $arr['new_timein'];
        $timein_id                  = $arr['timein_id'];
        $l_source                   = $arr['l_source'];
        $new_employee_timein        = $arr['new_employee_timein'];
        $break                      = $arr['breaks'];
        $enable_breaks_on_holiday   = $arr['enable_breaks_on_holiday'];
        
        $split                      = in_array_custom("sched_id_{$schedule_blocks_id}",$get_all_schedule_blocks);
        $block_tardy                = 0;
        $r1                         = in_array_custom("sched_id_{$block_id}",$get_all_schedule_blocks_time_in);
        
        if($r1){
            $block_tardy            = $r1->tardiness_min;
        }


        // holiday now
        if(!$date){
            $date                   = date('Y-m-d',strtotime($current_date));
        }

        $holiday                    = in_array_custom("holiday_{$date}",$company_holiday);

        if($enable_breaks_on_holiday == "yes"){

            $holiday                = false;
        }

        if($holiday && $check_type != "time in"){
            $check_type = "time out";
        }
        
        // global where update data
        $where_update               = array(
                                    "eti.emp_id"                        => $emp_id,
                                    "eti.comp_id"                       => $comp_id,
                                    "eti.employee_time_in_id"           => $employee_time_in_id,
                                    "eti.schedule_blocks_time_in_id"    => $block_id
                                    );

        // overwrite check type 
        if($check_type_o == "time out" && $source == "desktop"){

            $check_type         = "time out";
        }


        if($check_type == "lunch out"){

            // update lunch out value ================================================================ >>>> UPDATE LUNCH OUT VALUE
            $get_diff = (strtotime($current_date) - strtotime($time_in)) / 60;

            if($min_log < $get_diff){

                // $update_val     = array("lunch_out"=>$current_date,"source"=>$l_source.",".$source."-".$check_type);
                $update_val     = array(
                    "lunch_out"=>$current_date,
                    "time_in_status"    => "pending",
                    "source"            => 'mobile',
                    "location_2"        => $location,
                    "corrected"         => 'Yes'
                );
                
                $this->db->where($where_update);
                $update         = $this->db->update("schedule_blocks_time_in AS eti",$update_val);
            }

            $this->db->where($where_update);
            $q2                 = $this->db->get("schedule_blocks_time_in AS eti",1,0);
                
            return ($q2) ? $q2->row() : FALSE ;
        }
        else if($check_type == "lunch in"){

            $overbreak_min          
            = $this->overbreak_min($comp_id,$date,$emp_id,$work_schedule_id,$lunch_out,"",$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);

            // update lunch in value ================================================================ >>>> UPDATE LUNCH IN VALUE
            $get_diff               = (strtotime($current_date) - strtotime($lunch_out)) / 60;

            if($min_log < $get_diff){

                $block_tardy        = $block_tardy + $overbreak_min;
                $update_val         = array(
                                    "lunch_in"      => $current_date,
                                    "overbreak_min" => $overbreak_min,
                                    "tardiness_min" => $block_tardy,
                                    // "source"        =>$l_source.",".$source."-".$check_type
                                    // "source"        => "mobile",
                                    "time_in_status"    => "pending",
                                    "source"            => 'mobile',
                                    "location_3"        => $location,
                                    "corrected"         => 'Yes'
                                    );

                $this->db->where($where_update);
                $update = $this->db->update("schedule_blocks_time_in AS eti",$update_val);
            }
                
            $this->db->where($where_update);
            $q2                     = $this->db->get("schedule_blocks_time_in AS eti",1,0);
            
            $this->up_date_current_time_in($employee_time_in_id,$comp_id,$source,$get_all_employee_timein,$list_of_blocks,$get_all_schedule_blocks_time_in,$holiday);

            return ($q2) ? $q2->row() : FALSE ;
        }
        else if($check_type == "time out"){

            $undertime                  = 0;
            $total_hours                = 0;
            $update                     = false;
            $get_diff                   = (strtotime($current_date) - strtotime($lunch_in)) / 60;

            if($holiday){
                $get_diff                   = (strtotime($current_date) - strtotime($time_in)) / 60;
            }
            
            if($min_log < $get_diff){

                $update_val             = array(
                                            "time_out"=>$current_date,
                                            // "source"=>$l_source.",".$source."-".$check_type
                                            "time_in_status"    => "pending",
                                            "source"            => 'mobile',
                                            "location_4"        => $location,
                                            "corrected"         => 'Yes',
                                        );
                $this->db->where($where_update);
                $update                 = $this->db->update("schedule_blocks_time_in AS eti",$update_val);


                $where_updatex          = array(
                                        "etix.emp_id"                       => $emp_id,
                                        "etix.comp_id"                      => $comp_id,
                                        "etix.employee_time_in_id"          => $employee_time_in_id,
                                        );

                $update_val_ti             = array(
                                            "time_out"=>$current_date,
                                            // "source"=>$l_source.",".$source."-".$check_type
                                            "time_in_status"    => "pending",
                                            "source"            => 'mobile',
                                            "location_4"        => $location,
                                            "corrected"         => 'Yes',
                                            "mobile_clockout_status" => "pending"
                                        );
                $this->db->where($where_updatex);
                $this->db->update("employee_time_in AS etix",$update_val_ti);
            }

            $arrx                       = array(
                                        'time_in'   => 'eti.time_in',
                                        'lunch_out' => 'eti.lunch_out',
                                        'lunch_in'  => 'eti.lunch_in',
                                        'time_out'  => 'eti.time_out',
                                        'schedule_blocks_time_in_id' => 'eti.schedule_blocks_time_in_id',
                                        'employee_time_in_id' => 'eti.employee_time_in_id',
                                        );

            // $this->db->select($arrx);
            $this->db->where($where_update);
            $this->db->order_by("eti.time_in","DESC");

            $q2                         = $this->db->get("schedule_blocks_time_in AS eti",1,0);
            $r                          = $q2->row();

            $tardiness          = $this->get_tardiness($block_id,$split);
            $undertime          = $this->get_undertime($block_id,$split,$current_date);

            $hours              = $this->total_hours_worked($current_date, $r->time_in);
            $total_hours        = $this->convert_to_hours($hours - $break);
            
            $update_timein_logs = array(
                                "tardiness_min"         => $tardiness,
                                "undertime_min"         => $undertime,
                                "total_hours"           => $split->total_hours_work_per_block,
                                "total_hours_required"  => $total_hours,
                                "time_out"              => $current_date,
                                "time_in_status"        => "pending",
                                "source"                => 'mobile',
                                "corrected"             => 'Yes'
                                );

            if($holiday){
                $update_timein_logs['tardiness_min'] = 0;
                $update_timein_logs['undertime_min'] = 0;
            }
            
            $this->db->where($where_update);
            $sql_update_timein_logs = $this->db->update("schedule_blocks_time_in AS eti",$update_timein_logs);
            
            // athan helper
            // if($date){
                // payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
            // }
            
            $this->up_date_current_time_in($employee_time_in_id,$comp_id,$source,$get_all_employee_timein,$list_of_blocks,$get_all_schedule_blocks_time_in,$holiday);

            return ($r) ? $r : FALSE ;
        }
        else{
            // FOR TIMEIN A NEW SCHEDULE BLOCK AFTER THE FIRST BLOCK

            $slist                          = $split;
            $late_min                       = 0;
            $date_start                     = $date;
            $first_block_trap               = true;
            $first_block_start_time_str     = strtotime($first_block_start_time);
            
            if($slist){
                $start_time                 = $slist->start_time;
                $start_time_str             = strtotime($start_time);

                if($first_block_start_time_str > $start_time_str){
                    $date_start             = date("Y-m-d",strtotime($date."+ 1 day"));
                }

                $start_time_date            = $date_start." ".$start_time;
                $start_time_date_str        = strtotime($start_time_date);
                $current_date_str           = strtotime($current_date);
                
                if($start_time_str < $current_date_str){

                    $total                  = strtotime($current_date) - strtotime($start_time);
                    $hours                  = floor($total / 60 / 60);
                    $minutes                = floor(($total - ($hours * 60 * 60)) / 60);
                    $ret                    = ($hours * 60) + $minutes;
                    $late_min               = ($ret < 0) ? '0' : $ret;
                }
            }

            $late_min                       = ($late_min > 0) ? $late_min : 0;
            $timein_id                      = $employee_time_in_id;

            if($holiday){
                $late_min                   = 0;
            }
            
            // insert time in log               
            if($new_employee_timein){
                $first_block_trap           = false;
                $val                        = array(
                                            "emp_id"            => $emp_id,
                                            "comp_id"           => $comp_id,
                                            "date"              => $date,
                                            "late_min"          => $late_min,
                                            "tardiness_min"     => $late_min,
                                            "time_in"           => $current_date,
                                            "total_hours"       => $split->total_hours_work_per_block,
                                            // "source"            => $source."-time in",
                                            "source"            => "mobile",
                                            "work_schedule_id"  => $work_schedule_id,
                                            "mobile_clockin_status" => 'pending',
                                            "location_1"        => $location,
                                            "time_in_status"    => 'pending',
                                            "corrected"         => 'Yes'
                                            );
                $insert                     = $this->db->insert("employee_time_in",$val);
                $timein_id                  = $this->db->insert_id();
            } else {
                $wr = array(
                    "employee_time_in_id"   => $timein_id,
                );
                $upd = array(
                    "mobile_clockin_status" => 'pending',
                    "location_1"        => $location,
                    "time_in_status"    => 'pending',
                    "corrected"         => 'Yes'
                );
                $this->db->where($wr);
                $this->db->update("employee_time_in AS eti",$upd);
            }
 
            $val2                           = array(
                                            "employee_time_in_id"   => $timein_id,
                                            "date"                  => $date,
                                            "time_in"               => $current_date,
                                            "emp_id"                => $emp_id,
                                            "comp_id"               => $comp_id,
                                            "late_min"              => $late_min,
                                            "tardiness_min"         => $late_min,
                                            "total_hours"           => $split->total_hours_work_per_block,
                                            "schedule_blocks_id"    => $this->schedule_blocks_id,
                                            "work_schedule_id"      => $work_schedule_id,
                                            // "source"                => $source."-time in",
                                            "location"              => "location_1",
                                            "source"                => "mobile",
                                            "time_in_status"        => 'pending',
                                            "location_1"            => $location,
                                            );

            $insert2                        = $this->db->insert("schedule_blocks_time_in",$val2);
            $insert2_id = $this->db->insert_id();
            
            $this->up_date_current_time_in($timein_id,$comp_id,$source,$get_all_employee_timein,$list_of_blocks,$get_all_schedule_blocks_time_in,$holiday);
            
            if($insert2){
                $w2                         = array(
                                            "eti.schedule_blocks_time_in_id"    => $insert2_id
                                            );

                $this->edb->where($w2);
                $this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
                $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
                $q2                         = $this->edb->get("schedule_blocks_time_in AS eti",1,0);

                return ($q2) ? $q2->row() : FALSE ;
            }
        }
    }

    public function get_all_schedule_blocks($company_id){
        $row_array  = array();
        $select     = array(
                        'schedule_blocks_id',
                        'work_schedule_id',
                        'company_id',
                        'block_name',
                        'start_time',
                        'end_time',
                        'break_in_min',
                        'total_hours_work_per_block',
                        'notes',
                        'bg_color',
                        'employees_required',
                        'location_and_offices_id',
                        'project_id',
                        'enable_breaks_on_holiday',
                    );
        $this->db->where('company_id',$company_id);
        $this->db->select($select);
        $q3     = $this->db->get("schedule_blocks");
        $result = $q3->result();

        if($result){
            foreach ($result as $r1){
                $wd     = array(
                        "schedule_blocks_id"        => $r1->schedule_blocks_id,
                        "work_schedule_id"          => $r1->work_schedule_id,
                        "company_id"                => $r1->company_id,
                        "block_name"                => $r1->block_name,
                        "start_time"                => $r1->start_time,
                        "end_time"                  => $r1->end_time,
                        "break_in_min"              => $r1->break_in_min,
                        "total_hours_work_per_block"=> $r1->total_hours_work_per_block,
                        "notes"                     => $r1->notes,
                        "bg_color"                  => $r1->bg_color,
                        "employees_required"        => $r1->employees_required,
                        "location_and_offices_id"   => $r1->location_and_offices_id,
                        "project_id"                => $r1->project_id,
                        "enable_breaks_on_holiday"  => $r1->enable_breaks_on_holiday,
                        "custom_search"             => "sched_id_{$r1->schedule_blocks_id}",
                        "custom_searchv2"           => "worksched_id_{$r1->work_schedule_id}"
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }

    public function overbreak_minv2($comp_id,$date,$emp_id,$work_schedule_id,$lunch_out,$lunc_in ="",$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in){
        if(!$lunch_out){
            return 0;
        }
        
        if($lunc_in){
            $current_time = $lunc_in;
        }else{
            $current_time = date('Y-m-d H:i:s');
        }
        
        $day    = date('l',strtotime($date));
        $r_uwd  = in_array_custom("rsched_id_{$work_schedule_id}_{$day}",$get_all_regular_schedule);

        if($r_uwd){
            if($r_uwd->break_in_min != 0){
                
                $lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$r_uwd->break_in_min} minutes"));
                
                if($current_time > $lunch_in){
                    $min = $this->total_hours_worked($current_time, $lunch_in);
                    return $min;
                }
            }
        }else{

            $check_work_schedule_flex = in_array_custom("flex_{$work_schedule_id}",$get_work_schedule_flex);

            if($check_work_schedule_flex){
    
                if($check_work_schedule_flex->duration_of_lunch_break_per_day){
                    $lunch_in = date("Y-m-d H:i:s",strtotime($lunch_out." +{$check_work_schedule_flex->duration_of_lunch_break_per_day} minutes"));
                    if($current_time > $lunch_in){
                        $min = $this->total_hours_worked($current_time, $lunch_in);
                        return $min;
                    }
                }
                
            }else{

                $r_ws = in_array_foreach_custom("list_{$emp_id}_{$date}_{$work_schedule_id}",$list_of_blocks);

                if($r_ws){
                    $split = $this->new_get_splitinfo($comp_id,$work_schedule_id,$emp_id,$date,$time_in,$get_all_employee_timein,$list_of_blocks,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);
                    if($split){
                        if($split['break_in_min']){
                            $lunch_in = date("Y-m-d H:i:s",strtotime($lunch_out." +{$split['break_in_min']} minutes"));
                            if($current_time > $lunch_in){
                                $min = $this->total_hours_worked($current_time, $lunch_in);
                                return $min;
                            }
                        }
                    }
                }
            }
        }
        return 0;
    }
    
    public function up_date_current_time_in($timein_id,$comp_id,$source="",$get_all_employee_timein,$list_of_blocks,$get_all_schedule_blocks_time_in,$holiday=false){
        
        $total_tardy            = 0;
        $total_late             = 0;
        $total_over_break       = 0;
        $total_under_time       = 0;
        $total_total_hours      = 0;
        $total_total_hours_req  = 0;
        
        $tardy                  = 0;
        $late_min               = 0;
        $undertime              = 0;
        $overbreak              = 0;
        $total_hours            = 0;
        $total_req              = 0;
        $absent_min             = 0;
        $time_out               = "";
        $time_in                = "";
        
        $data_blocks            = $this->employee_schedule_blocks_ids_h($timein_id,$comp_id,$get_all_employee_timein,$list_of_blocks);
        
        $row_array              = $data_blocks['row_array'];
        $absent_block_total_h   = $data_blocks['absent_block_total_h'];
        $w                      = array("employee_time_in_id" => $timein_id,"status" => "Active","comp_id" => $comp_id);
        $s                      = array("schedule_blocks_id","late_min","tardiness_min","overbreak_min","undertime_min","total_hours","total_hours_required","time_in","time_out","absent_min");
        $this->db->select($s);
        $this->db->where($w);
        $this->db->order_by("time_in","ASC");
        $q                      = $this->db->get("schedule_blocks_time_in");
        $r1                     = $q->result();

        if($r1){
            $first              = reset($r1);
            $last               = end($r1);
            if($first){
                $time_in        = $first->time_in;
            }
            if($last){
                $time_out       = $last->time_out;
            }
            
            foreach ($r1 AS $r){
                $tardy                  = ($r->tardiness_min) ? $r->tardiness_min : 0;
                $late_min               = ($r->late_min) ? $r->late_min : 0;
                $overbreak              = ($r->overbreak_min) ? $r->overbreak_min : 0;
                $undertime              = ($r->undertime_min) ? $r->undertime_min : 0;
                $total_hours            = ($r->total_hours) ? $r->total_hours : 0;
                $total_req              = ($r->total_hours_required) ? $r->total_hours_required : 0;
                
                $total_late             = $total_late + $late_min;
                $total_tardy            = $total_tardy + $tardy;
                $total_under_time       = $total_under_time + $undertime;
                $total_over_break       = $total_over_break + $overbreak;
                $total_total_hours_req  = $total_total_hours_req + $total_req;
                
                $if_reghrs_custom       = in_array_custom($r->schedule_blocks_id,$row_array);
                if($if_reghrs_custom){
                    $absent_block_total_h = $absent_block_total_h - $if_reghrs_custom->total_hours_work_per_block;
                }
            }
        }
        $absent_block_total_h   = $absent_block_total_h * 60;
        $update_timein_logs     = array(
                            "time_in"               => $time_in,
                            "time_out"              => $time_out,
                            "tardiness_min"         => $total_tardy,
                            "late_min"              => $total_late,
                            "overbreak_min"         => $total_over_break,
                            "undertime_min"         => $total_under_time,
                            "total_hours_required"  => $total_total_hours_req,
                            "absent_min"            => $absent_block_total_h,
                            );

        if($holiday){
            $update_timein_logs["late_min"]         = 0;
            $update_timein_logs["overbreak_min"]    = 0;
            $update_timein_logs["undertime_min"]    = 0;
            $update_timein_logs["absent_min"]       = 0;
        }

        if($source == "recalculated"){
            $update_timein_logs['last_source'] = "recalculated";
        }
        $this->db->where($w);
        
        $update = $this->db->update("employee_time_in AS eti",$update_timein_logs);
    }

    public function employee_schedule_blocks_ids_h($timein_id,$comp_id,$get_all_employee_timein,$list_of_blocks){
        
        $row_array              = array();
        $absent_block_total_h   = 0;
        $current_date           = "";
        $work_schedule_id       = "";
        $emp_id                 = "";
        $data                   = array();

        //$r2   = in_array_custom("timeins_id_{$timein_id}",$get_all_employee_timein);
        $s1 = array("emp_id","work_schedule_id","date");
        $w  = array("employee_time_in_id" => $timein_id,"status" => "Active","comp_id" => $comp_id);
        $this->db->select($s1);
        $this->db->where($w);
        $q2 = $this->db->get("employee_time_in");
        $r2 = $q2->row();

        if($r2){
            $current_date       = $r2->date;
            $work_schedule_id   = $r2->work_schedule_id;
            $emp_id             = $r2->emp_id;
        }
        
        //$r_ws                     = in_array_foreach_custom("list_{$emp_id}_{$schedule_blocks_id}_{$work_schedule_id}",$list_of_blocks);
        $arrSelect  = array(
                "sb.schedule_blocks_id",
                "total_hours_work_per_block",
        );
        $w_date = array(
                "em.valid_from <="      =>  $current_date,
                "em.until >="           =>  $current_date
        );
        $w_ws   = array(
                "em.work_schedule_id"   => $work_schedule_id,
                "em.company_id"         => $comp_id,
                "em.emp_id"             => $emp_id
        );
        $this->db->select($arrSelect);
        $this->db->where($w_date);
        $this->db->where($w_ws);
        $this->db->join("schedule_blocks AS sb","sb.schedule_blocks_id = em.schedule_blocks_id","LEFT");
        $q_ws = $this->db->get("employee_sched_block AS em");
        $r_ws = $q_ws->result();

        if($r_ws){
            foreach($r_ws as $row){
                $wd = array(
                        "schedule_blocks_id"            => $row->schedule_blocks_id,
                        "total_hours_work_per_block"    => $row->total_hours_work_per_block
                );
                array_push($row_array,$wd);
                $absent_block_total_h   = $absent_block_total_h + $row->total_hours_work_per_block;
            }
        }

        $data['row_array']              = ($row_array) ? $row_array : false;
        $data['absent_block_total_h']   = $absent_block_total_h;
        
        return $data;
    }

    public function company_holidayvzz($company_id){
        $row_array  = array();
        
        $s = array(
            'h.holiday_id',
            'h.holiday_name',
            'h.hour_type_id',
            'h.date',
            'h.repeat_on',
            'h.repeat_type',
            'h.date_type',
            'h.created_by_account_id',
            'h.created_date',
            'h.updated_by_account_id',
            'h.updated_date',
            'h.holiday_type',
            'h.flag_default',
            'ht.hour_type_name',
            'ht.description',
            'ht.rest_day',
            'ht.pay_rate',
            'ht.working',
            'ht.working_daily',
            'ht.hourly',
            'ht.daily',
            'ht.default',
            );
        $w = array(
            "h.company_id"  => $company_id,
            "h.status"      => "Active"
        );
        $this->db->where($w);
        $this->db->join("hours_type as ht","ht.hour_type_id = h.hour_type_id","LEFT");
        $q      = $this->db->get("holiday as h");
        $result = $q->result();

        if($result){
            foreach ($result as $r1){
                $wd     = array(
                        "holiday_id"            => $r1->holiday_id,
                        "holiday_name"          => $r1->holiday_name,
                        "hour_type_id"          => $r1->hour_type_id,
                        "date"                  => $r1->date,
                        "repeat_on"             => $r1->repeat_on,
                        "repeat_type"           => $r1->repeat_type,
                        "date_type"             => $r1->date_type,
                        "created_by_account_id" => $r1->holiday_id,
                        "created_date"          => $r1->created_date,
                        "updated_by_account_id" => $r1->updated_by_account_id,
                        "updated_date"          => $r1->updated_date,
                        "holiday_type"          => $r1->holiday_type,
                        "flag_default"          => $r1->flag_default,
                        "hour_type_name"        => $r1->hour_type_name,
                        "description"           => $r1->description,
                        "rest_day"              => $r1->rest_day,
                        "pay_rate"              => $r1->pay_rate,
                        "working"               => $r1->working,
                        "working_daily"         => $r1->working_daily,
                        "hourly"                => $r1->hourly,
                        "default"               => $r1->default,
                        "custom_search"         => "holiday_{$r1->date}"
                        );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }

    public function company_holiday($company_id,$min="",$max=""){
        

        $row_array  = array();
        if($min!=""&&$max!=""){
            $year_min   = date("Y",strtotime($min));
            $year_max   = date("Y",strtotime($max));

            ($year_max - $year_min) + 1;
        }else{
            $year_min   = date("Y");
            $year_max   = date("Y");
            $y_gap      = date("Y");
        }

        $s          = array(
                    'h.holiday_id',
                    'h.holiday_name',
                    'h.hour_type_id',
                    'h.date',
                    'h.repeat_on',
                    'h.repeat_type',
                    'h.date_type',
                    'h.created_by_account_id',
                    'h.created_date',
                    'h.updated_by_account_id',
                    'h.updated_date',
                    'h.holiday_type',
                    'h.flag_default',
                    'ht.hour_type_name',
                    'ht.description',
                    'ht.rest_day',
                    'ht.pay_rate',
                    'ht.working',
                    'ht.working_daily',
                    'ht.hourly',
                    'ht.daily',
                    'ht.default',
                    );
        $w = array(
            "h.company_id"  => $company_id,
            "h.status"      => "Active"
        );
        $this->db->select($s);
        $this->db->where($w);
        $this->db->join("hours_type AS ht","ht.hour_type_id = h.hour_type_id");
        $q = $this->db->get("holiday AS h");
        $r = $q->result();
        
        if($r){
            foreach ($r as $r1){
                $date       = $r1->date;
                
                if($r1->repeat_type == "yes" && $r1->date_type != "movable"){
                    
                    for($x = 0;$x < $y_gap;$x++){
                        
                        $month      = date("m",strtotime($date));
                        $day        = date("d",strtotime($date));
                        $year       = $year_min + $x;
                        
                        $hol_date   = $year."-".$month."-".$day;
                        
                        $wd     = array(
                                "holiday_id"            => $r1->holiday_id,
                                "holiday_name"          => $r1->holiday_name,
                                "hour_type_id"          => $r1->hour_type_id,
                                "repeat_on"             => $r1->repeat_on,
                                "repeat_type"           => $r1->repeat_type,
                                "date_type"             => $r1->date_type,
                                "created_by_account_id" => $r1->holiday_id,
                                "created_date"          => $r1->created_date,
                                "updated_by_account_id" => $r1->updated_by_account_id,
                                "updated_date"          => $r1->updated_date,
                                "holiday_type"          => $r1->holiday_type,
                                "flag_default"          => $r1->flag_default,
                                "hour_type_name"        => $r1->hour_type_name,
                                "description"           => $r1->description,
                                "rest_day"              => $r1->rest_day,
                                "pay_rate"              => $r1->pay_rate,
                                "working"               => $r1->working,
                                "working_daily"         => $r1->working_daily,
                                "hourly"                => $r1->hourly,
                                "default"               => $r1->default,
                                "date"                  => $hol_date,
                                "custom_search"         => "holiday_{$hol_date}",
                        );
                        array_push($row_array,$wd);
                    }
                }else{
                    $wd     = array(
                            "holiday_id"            => $r1->holiday_id,
                            "holiday_name"          => $r1->holiday_name,
                            "hour_type_id"          => $r1->hour_type_id,
                            "repeat_on"             => $r1->repeat_on,
                            "repeat_type"           => $r1->repeat_type,
                            "date_type"             => $r1->date_type,
                            "created_by_account_id" => $r1->holiday_id,
                            "created_date"          => $r1->created_date,
                            "updated_by_account_id" => $r1->updated_by_account_id,
                            "updated_date"          => $r1->updated_date,
                            "holiday_type"          => $r1->holiday_type,
                            "flag_default"          => $r1->flag_default,
                            "hour_type_name"        => $r1->hour_type_name,
                            "description"           => $r1->description,
                            "rest_day"              => $r1->rest_day,
                            "pay_rate"              => $r1->pay_rate,
                            "working"               => $r1->working,
                            "working_daily"         => $r1->working_daily,
                            "hourly"                => $r1->hourly,
                            "default"               => $r1->default,
                            "date"                  => $r1->date,
                            "custom_search"         => "holiday_{$r1->date}"
                    );
                    array_push($row_array,$wd);
                }
            }
        }
        return $row_array;
    }

    public function check_rest_day($company_id){
        $row_array  = array();
        $s = array(
            'rest_day_id',
            'payroll_group_id',
            'work_schedule_id',
            'rest_day'
            );
        $w = array(
            "company_id"        => $company_id,
            "status"            => 'Active'
        );
        $this->db->select($s);
        $this->db->where($w);
        $q = $this->db->get("rest_day");
        $result = $q->result();
        if($result){
            foreach ($result as $r1){
                $wd     = array(
                        "rest_day_id"       => $r1->rest_day_id,
                        "payroll_group_id"  => $r1->payroll_group_id,
                        "work_schedule_id"  => $r1->work_schedule_id,
                        "rest_day"          => $r1->rest_day,
                        "custom_search"     => "rest_day_{$r1->work_schedule_id}_{$r1->rest_day}",
                        "custom_searchv2"   => "rest_dayv2_{$r1->payroll_group_id}_{$r1->rest_day}"
                        );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }

    public function holiday_time_in($arr = array()){    
        // Set data here
        $date                   = $arr['date'];
        $emp_no                 = $arr['emp_no'];
        $current_date           = $arr['current_date'];
        $emp_id                 = $arr['emp_id'];
        $comp_id                = $arr['comp_id'];
        $check_type             = $arr['check_type'];
        $source                 = $arr['source'];
        $min_log                = $arr['min_log'];
        $get_diff               = $arr['get_diff'];
        $employee_time_in_id    = $arr['employee_time_in_id'];
        $work_schedule_id       = $arr['work_schedule_id'];
        $time_in                = $arr['time_in'];
        $time_out               = $arr['time_out'];
        $lunch_in               = $arr['lunch_in'];
        $lunch_out              = $arr['lunch_out'];
        $new_timein             = $arr['new_timein'];
        $timein_id              = $arr['timein_id'];
        $break                  = $arr['break'];
        $hour_worked            = $arr['hours_worked'];
    
        // global where update data
        $where_update           = array(
                                "eti.emp_id"                => $emp_id,
                                "eti.comp_id"               => $comp_id,
                                "eti.employee_time_in_id"   => $employee_time_in_id
                                );
    
        $day                    = date('l',strtotime($date));
        
        $this->edb->where($where_update);
        $this->db->order_by("eti.time_in","DESC");
        $q2                     = $this->edb->get("employee_time_in AS eti",1,0);
        $r                      = $q2->row();
        
        $hours                  = 0;
        $get_h                  = 0;
        $tardiness              = 0;
        $l_source               = "";

        if($r){
            $l_source           = $r->source.",".$source."-".$check_type;       

            if($r->lunch_in !="" && $r->lunch_out != ""){
                $break_min          = $this->total_hours_worked($r->lunch_in, $r->lunch_out);
                
                if($break_min > $break){
                    $break_tard     = $break_min - $break;                      
                    $tardiness      = $break_tard;
                    $break          = $break_min;
                }
            }
            
            $hours                  = $this->total_hours_worked($r->time_out, $r->time_in);
            $get_h                  = $this->convert_to_hours($hours);
        }

        $update_timein_logs     = array(
                                "tardiness_min"         => 0,
                                "undertime_min"         => 0,
                                "total_hours"           => $get_h,
                                "late_min"              => 0,
                                "absent_min"            => 0,
                                // "source"                => $l_source,
                                "source"                => "mobile",
                                "total_hours_required"  => $get_h,
                                "time_in_status"    =>"pending",
                                "corrected"         => 'Yes',
                                );

        $this->db->where($where_update);

        $sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
    
        return ($q2) ? $q2->row() : FALSE ;
    }

    public function reg_sched_calc_under($get_all_regular_schedule,$get_work_schedule,$work_schedule_id,$currentdate,$new_time_in,$new_time_out,$lunch_out = null){
        /// compute regular undertime
        /// if timeout is less than the out 
        // get the supposed time_out
        $day                            = date('l',strtotime($currentdate));
        $reg_sched                      = in_array_custom("rsched_id_{$work_schedule_id}_{$day}",$get_all_regular_schedule);
        $update_undertime               = 0;
        if($reg_sched){
            $w_start                    = $reg_sched->work_start_time;
            $w_end                      = $reg_sched->work_end_time;
            $tresh                      = $reg_sched->latest_time_in_allowed;
            $break                      = $reg_sched->break_in_min;
            $total_work_hours           = $reg_sched->total_work_hours;

            $end_date                   = $currentdate;
            $start_date                 = $currentdate;

            $half_day                   = $total_work_hours/2;

            if($w_start > $w_end){
                $end_date               = date('Y-m-d',strtotime($end_date. " +1 day"));
                $end_date               = $currentdate;
            }

            $start_date_time            = date("Y-m-d H:i", strtotime($start_date." ".$w_start));
            $end_date_time              = date("Y-m-d H:i", strtotime($end_date." ".$w_end));
            $init_start_date_time       = $start_date_time;
            $start_date_time_half       = date('Y-m-d H:i',strtotime($start_date_time. " +".$half_day." hours"));



            if($new_time_in > $start_date_time){
                if($tresh){
                    // treshold dont exist if halfday
                    if($start_date_time_half > $new_time_in){
                        $start_date_time    = date('Y-m-d H:i',strtotime($start_date_time. " +".$tresh." minutes"));
                        if($new_time_in > $start_date_time){
                            $end_date_time  = date('Y-m-d H:i',strtotime($end_date_time. " +".$tresh." minutes"));
                        }else{
                            $min_tresh      = $this->total_hours_worked($new_time_in,$init_start_date_time);
                            $min_tresh      = ($min_tresh > 0) ? $min_tresh : 0;
                            $end_date_time  = date('Y-m-d H:i',strtotime($end_date_time. " +".$min_tresh." minutes"));
                        }
                    }
                }
            }
            
            if($end_date_time > $new_time_out){
                $update_undertime       = $this->total_hours_worked($end_date_time,$new_time_out);
                // if employee dont break before timeout
                if($break > 0 && $lunch_out == null){

                    // check rules
                    $rules              = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);
                    $break_rules        = "";

                    if($rules){

                        $break_rules    = $rules->break_rules;
                    }

                    if($break_rules != 'assumed'){
                        $update_undertime   = $update_undertime - $break;
                    }
                }

                if(($update_undertime/60) > $total_work_hours){
                    $update_undertime = ($total_work_hours * 60);
                }

                $update_undertime = ($update_undertime > 0) ? $update_undertime : 0;
            }

        }
        return $update_undertime;
    }

    public function only_for_timeIn_reg_sched($emp_id,$emp_no,$work_schedule_id,$comp_id,$current_date,$date,$time_in,$source="kiosk",$comp_add,$ileave,$hours_worked = 0,$get_all_regular_schedule,$get_work_schedule,$get_employee_payroll_information,$get_tardiness_settings, $location=""){
        
        // CHECK TIME IN START /
        $day                                = date("l", strtotime($date));
        $nwst                               = "";
        $wst                                = $this->check_workday_settings_start_time($day,$work_schedule_id,$get_all_regular_schedule);

        if($wst != FALSE){
            // new start time
            $nwst                           = $date." ".$wst;
            $check_diff_total_hours         = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
        }
        //$late_min     = $this->late_min($comp_id, $date, $emp_id, $this->work_schedule_id);
        
        $late_min                           = $this->total_hours_worked($current_date, $nwst);
        
        /** BARACK CODE TRESS PASS HERE >> for timein**/
        // NEW RULE /
        // compress schedule with break,
        // total hours req / 2 + break - when employee is doing halfday,
        // e.g sched 7:00 am - 4:00 pm
        //     break 60 mins
        //     total required hours worked 8
        // senario halfday
        // if time in 7:00 am & timeout 11:30 am    - hours worked - 4.0 h - end count from 11:00 am
        // if time in 11:45 am & timeout 4:00 am    - hours worked - 4.0 h - start count from 12:00 pm
        
        $threshold                          = 0;
        $hours_worked_req                   = 0;
        $grace_period                       = 0;
        $break_min                          = 0;
        $is_works                           = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);
        $is_work                            = ($is_works->break_rules == "assumed") ? $is_works : false;
        $work_sched                         = in_array_custom("rsched_id_{$work_schedule_id}_{$day}",$get_all_regular_schedule);
        $tardiness_set                      = $this->tardiness_settings($emp_id, $comp_id,$get_employee_payroll_information,$get_tardiness_settings);
            
        // adjust for threshold
        if($work_sched){
            $threshold                      = ($work_sched->latest_time_in_allowed) ? $work_sched->latest_time_in_allowed : 0;
            $hours_worked_req               = ($work_sched->total_work_hours) ? $work_sched->total_work_hours : 0;
            $break_min                      = ($work_sched->break_in_min) ? $work_sched->break_in_min : 0;
        }
            
        // adjust for tardiness settings grace period
        if($tardiness_set){
            $grace_period                   = $tardiness_set;
        }
            
        // start time is blank or empty late is zer0 
        
        if($nwst == ""){
            $late_min                       = 0;
        }
        else{

            $start_time_org                 = $nwst;
            $half_total_hours               = $hours_worked_req/2;
            $half_total_min                 = $half_total_hours * 60;
            $start_time_org_str             = strtotime($nwst);
            $current_date_str               = strtotime($current_date);
            $start_time_wt_threshold        = strtotime($nwst."+ ".$threshold." minutes");
            $start_time_wt_threshold_date   = date("Y-m-d H:i:s",$start_time_wt_threshold);
            $predict_lunch_out_for_halfday  = strtotime($start_time_org."+ ".$half_total_min." minutes");
        
            if($threshold == 0){
                // grace period effect
                $late_min                   = $late_min - $grace_period;
            }
            else{
                if(($current_date_str > $start_time_org_str) && ($current_date_str <= $start_time_wt_threshold)){
                    $start_time_org         = $current_date;
                    $late_min               = 0;
                }
                else if($current_date_str < $start_time_org_str){
                    $start_time_org         = $nwst;
                    $late_min               = 0;
                }
                else if($current_date_str > $start_time_wt_threshold){
                    $late_min               = $this->total_hours_worked($current_date,$start_time_wt_threshold_date);
        
                    if($predict_lunch_out_for_halfday > $current_date_str){
                        $start_time_org     = $start_time_wt_threshold_date;
                    }
                    else{
                        $start_time_org     = $nwst;
                    }
                    // grace period effect
                    $late_min               = $late_min - $grace_period;
                }
            }
            
            // assume break here
            if($is_work){
                $half_total_min             = $is_work->assumed_breaks * 60;
                // assume break cant affect timein
            }
            $half_total_min                 = number_format($half_total_min,0);
            // predict lunchout
            $predict_lunch_out_for_halfday  = strtotime($start_time_org."+ ".$half_total_min." minutes");
            $predict_lunch_out_for_halfday2 = date("Y-m-d H:i:s",$predict_lunch_out_for_halfday);
        
            // predict lunchin
            $predict_lunch_in_for_halfday   = strtotime($predict_lunch_out_for_halfday2."+ ".$break_min." minutes");
            $predict_lunch_in_for_halfday2  = date("Y-m-d H:i:s",$predict_lunch_in_for_halfday);
                
            if(($predict_lunch_out_for_halfday <= $current_date_str) && ($predict_lunch_in_for_halfday >= $current_date_str)){

                $late_min                   = $half_total_min;
                    
                // grace period effect
                $late_min                   = $late_min;
            }
                
            if($predict_lunch_in_for_halfday < $current_date_str){

                $late_min                   = total_min_between($current_date,$nwst);
                $late_min                   = $late_min - $break_min;
        
                // grace period effect
                $late_min                   = $late_min - $grace_period;
            }

            
            $late_min                       = ($late_min > 0) ? $late_min : 0;
            
            $late_min                       = ($late_min > ($hours_worked_req * 60)) ? ($hours_worked_req * 60) : $late_min;
            
        }
        
        //* END BARACK CODE TRESS PASS HERE /
        // insert time in log
        
        $val                                = array(
                                            "emp_id"            => $emp_id,
                                            "comp_id"           => $comp_id,
                                            "date"              => $date,
                                            "work_schedule_id"  => $work_schedule_id,
                                            "time_in"           => $current_date,
                                            "late_min"          => $late_min,
                                            "tardiness_min"     => $late_min,
                                            "location"          => $comp_add,
                                            "flag_on_leave"     => $ileave,
                                            "total_hours"       => $hours_worked,
                                            // "source"            => $source."-time in",
                                            "source"            => "mobile",
                                            "mobile_clockin_status" => 'pending',
                                            "location_1"        => $location,
                                            "time_in_status"    => 'pending',
                                            "corrected"         => 'Yes'
                                            );
        
        $insert                             = $this->db->insert("employee_time_in",$val);
            
        if($insert){
            $w2                             = array(
                                            "a.payroll_cloud_id"    => $emp_no,
                                            "eti.date"              => $date,
                                            "eti.status"            => "Active"
                                            );
        
            $this->edb->where($w2);
            $this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");

            $q2                             = $this->edb->get("employee_time_in AS eti",1,0);
        
            return ($q2) ? $q2->row() : FALSE ;
        }
    }

    public function rest_day_here($comp_id,$date,$current_date,$min_log,$emp_id,$check_type,$source,$comp_add,$sync_employee_time_in_id=false,$get_all_employee_timein, $location=""){
        // REST DAY 
        $locloc = $location;

        /* added for mobile */
        $mobile_pending_field="";
        $location_field="";
            
        if($sync_employee_time_in_id){
            $r1                                     = in_array_custom("timeins_id_{$sync_employee_time_in_id}",$get_all_employee_timein); 
        }
        else{
            $emp_timeins                            = in_array_foreach_custom("emp_id_timeins_{$emp_id}",$get_all_employee_timein); 
            if($emp_timeins){
                $r1                                 = reset($emp_timeins);
            }
        }
        
        if($r1){
            $get_diff = (strtotime($current_date) - strtotime($r1->time_in)) / 60;
            $locloc = $r1->location . " | " . $location;
        }
        
        if($check_type != "time in"){
            $l_source                               = $r1->source;
            $where_update                           = array(
                                                    "eti.emp_id"                => $emp_id,
                                                    "eti.comp_id"               => $comp_id,
                                                    "eti.employee_time_in_id"   => $r1->employee_time_in_id,
                                                    "eti.status"                => "Active"
                                                    );
        
            if($min_log < $get_diff){

                $total_h_r                          = (total_min_between($current_date,$r1->time_in) / 60);
                $update_val                         = array(
                                                    "time_out"                  => $current_date,
                                                    "total_hours_required"      => $total_h_r,
                                                    "total_hours"               => $total_h_r,
                                                    // "source"                    => $l_source.",".$source."-".$check_type,
                                                    "source"                    => "mobile",
                                                    "mobile_clockout_status"    => 'pending',
                                                    "location_4"                => $location,
                                                    "location"                  => $locloc,
                                                    "time_in_status"            => 'pending',
                                                    "corrected"                 => 'Yes',
                                                    "flag_new_time_keeping"     => "1" // flag new cronjob
                                                    );
                $this->db->where($where_update);
                $update = $this->db->update("employee_time_in AS eti",$update_val);
            }
            
            
            $this->edb->where($where_update);
            $this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
            $q2 = $this->edb->get("employee_time_in AS eti",1,0);
            

            // athan helper
            if($date){
                // payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
            }

            return ($q2) ? $q2->row() : FALSE ;
            exit;
                
        }
        else if($check_type == "time in"){

            if($min_log < $get_diff){
                $val                                = array(
                                                    "emp_id"                    => $emp_id,
                                                    "comp_id"                   => $comp_id,
                                                    "date"                      => $date,
                                                    "time_in"                   => $current_date,
                                                    "work_schedule_id"          => "-1",
                                                    "source"                    => $source."-".$check_type,
                                                    "location"                  => $locloc,
                                                    "mobile_clockin_status"     => 'pending',
                                                    "location_1"                => $location,
                                                    "time_in_status"            => 'pending',
                                                    "corrected"                 => 'Yes',
                                                    "flag_new_time_keeping"     => "1" // flag new cronjob
                                                    );

                $insert                             = $this->db->insert("employee_time_in",$val);
                
                if($insert){
                    $w2                             = array(
                                                    "eti.emp_id"                => $emp_id,
                                                    "eti.date"                  => $date,
                                                    "eti.status"                => "Active"
                                                    );

                    $this->edb->where($w2);
                    $this->db->order_by("eti.time_in","DESC");
                    $q2 = $this->edb->get("employee_time_in AS eti",1,0);
                        
                    return ($q2) ? $q2->row() : FALSE ;
                    exit;
                }
                else{
                    $this->edb->where($where_update);
                    $q2 = $this->edb->get("employee_time_in AS eti",1,0);
                        
                    return ($q2) ? $q2->row() : FALSE ;
                    exit;
                }
            }
        }
    }

    public function overbreak_min($comp_id,$date,$emp_id,$work_schedule_id,$lunch_out,$lunc_in ="",$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in){

        if(!$lunch_out){
            return 0;
        }
        
        if($lunc_in){
            $current_time = $lunc_in;
        }else{
            $current_time = date('Y-m-d H:i:s');
        }
        
        $day    = date('l',strtotime($date));
        $r_uwd  = in_array_custom("rsched_id_{$work_schedule_id}_{$day}",$get_all_regular_schedule);

        if($r_uwd){
            if($r_uwd->break_in_min != 0){
                
                $lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$r_uwd->break_in_min} minutes"));
                
                if($current_time > $lunch_in){
                    $min = $this->total_hours_worked($current_time, $lunch_in);
                    return $min;
                }
            }
        }else{

            $check_work_schedule_flex = in_array_custom("flex_{$work_schedule_id}",$get_work_schedule_flex);

            if($check_work_schedule_flex){
    
                if($check_work_schedule_flex->duration_of_lunch_break_per_day){
                    $lunch_in = date("Y-m-d H:i:s",strtotime($lunch_out." +{$check_work_schedule_flex->duration_of_lunch_break_per_day} minutes"));
                    if($current_time > $lunch_in){
                        $min = $this->total_hours_worked($current_time, $lunch_in);
                        return $min;
                    }
                }
                
            }else{

                $r_ws = in_array_foreach_custom("list_{$emp_id}_{$date}_{$work_schedule_id}",$list_of_blocks);

                if($r_ws){
                    $split = $this->new_get_splitinfo($comp_id,$work_schedule_id,$emp_id,$date,"",$get_all_employee_timein,$list_of_blocks,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);
                    if($split){
                        if($split['break_in_min']){
                            $lunch_in = date("Y-m-d H:i:s",strtotime($lunch_out." +{$split['break_in_min']} minutes"));
                            if($current_time > $lunch_in){
                                $min = $this->total_hours_worked($current_time, $lunch_in);
                                return $min;
                            }
                        }
                    }
                }
            }
        }
        return 0;
    }

    public function if_half_day($row_time_in,$work_schedule_id,$comp_id,$emp_no,$row_time_out = "",$employee_time_in_id = "",$emp_id="",$datex ="",$get_all_regular_schedule,$get_work_schedule_flex){
        
        $currentdate = date('Y-m-d');
        if($datex){
            $day    = date("l",strtotime($datex));
        }else{
            $day    = date("l",strtotime($row_time_in));
            $datex  = date('Y-m-d',strtotime($row_time_in));
        }

        $row    = in_array_custom("rsched_id_{$work_schedule_id}_{$day}",$get_all_regular_schedule);

        if($row){ // REG

            if($row->latest_time_in_allowed != NULL){
                $payroll_sched_timein   = date('H:i:s',strtotime($row->work_start_time) + ($row->latest_time_in_allowed * 60)) ;                
                $start_time             = date('Y-m-d H:i:s',strtotime($datex. " ".$payroll_sched_timein));
            }
            else{
                $start_time             = date('Y-m-d H:i:s',strtotime($datex. " ".$row->work_start_time));             
            }
            
            $end_time   = date('Y-m-d H:i:s',strtotime($datex. " ".$row->work_end_time));
            $mid_night  = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
                
            if(strtotime($row->work_start_time) > strtotime($row->work_end_time)){  
                $start_time = date('Y-m-d H:i:s', strtotime($currentdate." ".$row->work_start_time));
                $end_time   = date('Y-m-d H:i:s', strtotime($currentdate." ".$row->work_end_time." +1 day"));
            }                   
            
            $total      = strtotime($end_time) - strtotime($start_time);
            $hours      = floor($total / 60 / 60);
            $minutes    = round(($total - ($hours * 60 * 60)) / 60);
            $x          = (($hours * 60) + $minutes)/2 ;
            
            $half_date = date('Y-m-d H:i:s',strtotime($start_time. " +".$x. " minutes"));
    
            if($row_time_in >= $half_date){
                return 1;
            }
            else if($row_time_out <= $half_date){
                return 2;
            }
            else{
                return 0;
            }
            
        }else{ // FLEX

            $r_fh = in_array_custom("flex_{$work_schedule_id}",$get_work_schedule_flex);
            if($r_fh){
                $number_of_breaks_per_day   = $r_fh->duration_of_lunch_break_per_day;
                $total_hours_of_the_day     = ($r_fh->total_hours_for_the_day * 60) + $number_of_breaks_per_day;
                
                $x =  $total_hours_of_the_day / 2 ;
                
                if($r_fh->latest_time_in_allowed != NULL){
                    $start_time = date('Y-m-d H:i:s',strtotime($datex." ".$r_fh->latest_time_in_allowed));
                    $end_time   = date('Y-m-d H:i:s',strtotime($start_time .' +'.$total_hours_of_the_day.' minutes'));
                }else{
                    $start_time = date('Y-m-d H:i:s',strtotime($row_time_out .' -'.$total_hours_of_the_day.' minutes'));
                    $end_time = $row_time_out;
                }
                    
                $currentdate =$datex;

                $half_date= date('Y-m-d H:i:s',strtotime($start_time. " +".$x. " minutes"));
                
                if($row_time_in >= $half_date){
                    return 1;
                }else if($row_time_out <= $half_date){
                    return 2;
                }else{
                    return 0;
                }
            }else{
                // SPLIT DONT HAVE HALFDAY -> HARD TO DETERMINE
                return 0;
            }
        }
        return false;
    }

    public function display_time_list($emp_no,$work_schedule_id,$comp_id,$currentdate= null){
    
        $type           = "";
        $check_emp_no   = $this->login_screen_model->new_check_emp_info($emp_no,$comp_id);
        
        $w_date = array(
                "em.valid_from <="  => $currentdate,
                "em.until >="       => $currentdate
        );
        $this->db->where($w_date);
    
        $w_ws = array(
                "em.work_schedule_id"   => $work_schedule_id,
                "em.company_id"         => $comp_id,
                "em.emp_id"             => $check_emp_no->emp_id
        );
    
        $this->db->where($w_ws);
        $q_ws = $this->db->get("employee_sched_block AS em");
        $r_ws = $q_ws->result();
    
        if($q_ws->num_rows() > 0){

            $type   = "split";
            $arrx   = array(
                    'time_in'               => 'eti.time_in',
                    'lunch_out'             => 'eti.lunch_out',
                    'lunch_in'              => 'eti.lunch_in',
                    'time_out'              => 'eti.time_out',
                    'employee_time_in_id'   => 'eti.employee_time_in_id',
                    'emp_id'                => 'eti.emp_id'
                    );
        }
        else{
        
            $arrx   = array(
                    'time_in'               => 'eti.time_in',
                    'lunch_out'             => 'eti.lunch_out',
                    'lunch_in'              => 'eti.lunch_in',
                    'break1_out'            => 'eti.break1_out',
                    'break1_in'             => 'eti.break1_in',
                    'break2_out'            => 'eti.break2_out',
                    'break2_in'             => 'eti.break2_in',
                    'time_out'              => 'eti.time_out',
                    'employee_time_in_id'   => 'eti.employee_time_in_id',
                    'emp_id'                => 'eti.emp_id'
                    );
        }
        $this->edb->select($arrx);
        $w = array(
                "a.payroll_cloud_id"    => $emp_no,
                "a.user_type_id"        => "5",
                "eti.status"            => "Active",
                "eti.comp_id"           => $comp_id
        );
        $this->edb->where($w);
        $this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
        $this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
        $this->db->order_by("eti.time_in","DESC");
        
        if($type == "split"){
            $q = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
        }else{
            $q = $this->edb->get("employee_time_in AS eti",1,0);
        }
        $r = $q->row();
        
        $data['type'] = $type;
        $data['results'] = ($q->num_rows() > 0 )? $q->row() : false;
        return $data;
    }

    public function get_position($position_id){
        $w_emp = array(
                "position_id"=> $position_id,
        );
        $this->db->where($w_emp);
        $q_emp = $this->db->get("position");
        $q = $q_emp->row();
        
        return ($q_emp->num_rows() > 0) ? $q_emp->row() : FALSE ;
    }

    public function new_check_emp_info($emp_no,$comp_id){

        $s_emp  = array(
                "pi.position",
                "pi.employee_status",
                "pi.timesheet_required",
                "e.first_name",
                "e.last_name",
                "e.emp_id",
                );
        $w_emp  = array(
                "a.payroll_cloud_id"=>$emp_no,
                "a.user_type_id"=>"5",
                "e.company_id" => $comp_id,
                );
        $this->edb->select($s_emp);
        $this->edb->where($w_emp);
        $this->edb->join("employee_payroll_information AS pi","pi.emp_id = e.emp_id","INNER");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
        $q_emp = $this->edb->get("employee AS e");
        $q = $q_emp->row();
        return ($q_emp->num_rows() > 0) ? $q_emp->row() : FALSE ;
    }

    public function check_clock_type($time_is_check,$emp_id,$comp_id,$eti,$schedule_blocks_id,$current_date,$break){
        if($time_is_check){
            $sched_block = $this->check_if_date_is_sched_block($emp_id,$eti,$schedule_blocks_id,$comp_id,$current_date);
            
            if($sched_block){
                $time_in_val    = $sched_block->time_in;
                $lunch_out_val  = $sched_block->lunch_out;
                $lunch_in_val   = $sched_block->lunch_in;
                $time_out_val   = $sched_block->time_out;
                if($break > 0){
                    if(!$lunch_out_val){
                        $clock_type     = "lunch out";
                    }
                    else if(!$lunch_in_val){
                        $clock_type     = "lunch in";
                    }
                    else if(!$time_out_val){
                        $clock_type     = "time out";
                    }
                    else{
                        $clock_type = "";
                    }
                }else{
                    $clock_type     = "time out";
                }
            }
            else{
                $clock_type     = "time in";
            }
        }else{
            $clock_type     = "time in";
        }
        return $clock_type;
    }

    public function check_if_date_is_sched_block($emp_id,$eti,$sbi,$comp_id,$date){
        $arrx   = array(
                'time_in',
                'lunch_out',
                'lunch_in',
                'time_out'
                );
        
        $this->db->select($arrx);
        $w      = array(
                "emp_id"                => $emp_id,
                "status"                => "Active",
                "schedule_blocks_id"    => $sbi,
                "employee_time_in_id"   => $eti,
                "date"                  => $date,
                "comp_id"               => $comp_id
                );
        $this->db->where($w);
        $q = $this->db->get("schedule_blocks_time_in");
        $r = $q->row();
        return ($r) ? $r : false;
    }

    public function get_tardiness($schedule_block_id,$split_schedule_row){
        
        $grace_period=0;
        $w1 = array(
                "schedule_blocks_time_in_id"=>$schedule_block_id
        );
        $this->db->where($w1);
        $q1 = $this->db->get("schedule_blocks_time_in");
        $r1 = $q1->row();
        $minutes = 0;
        $break = $split_schedule_row->break_in_min;
        $min_break = 0;
        
        if($r1){
            if($split_schedule_row->break_in_min !=0 && $split_schedule_row->break_in_min !=NULL){
                $break_total = $this->total_hours_worked($r1->lunch_in, $r1->lunch_out);
                
                if($break_total > $break){
                     $min_break = $break_total - $break;
                }
            }
        
            $to_time    =  strtotime($r1->time_in);
            $time_inx   = date('Y-m-d H:i:s',strtotime($this->get_starttime($r1->schedule_blocks_id, $r1->time_in)));               
            
            if(strtotime($r1->time_in) > strtotime($time_inx)){ 
                $minutes = $this->total_hours_worked($r1->time_in, $time_inx);
            }else{              
                $minutes = 0;
            }
        }else{
            return false;
        }
        
        return $minutes + $min_break;
    }

    public function get_starttime($schedule_blocks_id,$time_in = null,$start = array()){
        
        $this->db->where('schedule_blocks_id',$schedule_blocks_id);
        $arrx = array(
                'start_time',
                'end_time',
        );
        $this->db->select($arrx);
        $q3 = $this->db->get("schedule_blocks");
        $result = $q3->result();
        
        
        $date_time = date('Y-m-d',strtotime($time_in));
        $arr = array();
        $row_list = array();
                        
        foreach($result as $row):
        $start_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->start_time));
        $end_time = date('Y-m-d H:i:s', strtotime($date_time." ".$row->end_time));
        if($row->end_time <= $row->start_time){
            $end_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->end_time . " +1 day"));
        
        }
        
        if($time_in >= $start_time && $time_in <= $end_time):
            return $start_time;
        else:
            $arr[] =  $start_time;
        endif;
        endforeach;
         
        
        foreach($arr as $key => $row2):
            
        if($time_in <= $row2){
            return $row2;
        }
        endforeach;
        
        if($start){
            $list = $this->get_blocks_list($start->schedule_blocks_id);
            $block_start = date('Y-m-d H:i:s',strtotime($date_time." ". $list->start_time)); 
            return $block_start;
        }
        
        return false;
    }

    public function get_blocks_list($schedule_blocks_id){

        $this->db->where('schedule_blocks_id',$schedule_blocks_id);
        $arr = array(
            'schedule_blocks_id',
            'work_schedule_id',
            'company_id',
            'block_name',
            'start_time',
            'end_time',
            'break_in_min',
            'total_hours_work_per_block'    
        );
        $this->db->select($arr);
        $q3 = $this->db->get("schedule_blocks");
        $result = $q3->row();
        
        return $result;
    }

    public function get_undertime($schedule_block_id,$split= array(),$current_date=""){
        $w1 = array(
                "schedule_blocks_time_in_id"=>$schedule_block_id
        );
        $this->db->where($w1);
        $q1 = $this->db->get("schedule_blocks_time_in");
        $r1 = $q1->row();
        $minutes = 0;

        if($r1){
            $end        = date('Y-m-d H:i:s',strtotime($this->get_endtime($r1->schedule_blocks_id, $r1->time_in)));
            $d_time_out = strtotime($end);
            
            if($current_date){
                $t_time_out = strtotime($current_date);
                $time_out_time = $current_date;
            }
            else{
                $t_time_out = strtotime($r1->time_out);
                $time_out_time = $r1->time_out;
            }
                        
            if($t_time_out < $d_time_out){
                $total_hours = $this->get_total_hours($schedule_block_id);
                $total_hours_req = $this->get_total_hours_req($schedule_block_id);
                
                $minutes = $this->total_hours_worked($end, $time_out_time);
            }
            else{
                $minutes = 0;
            }
        }
        else{
            return false;
        }
        return $minutes;
    }

    public function get_endtime($schedule_blocks_id,$time_in,$last = array()){
        $this->db->where('schedule_blocks_id',$schedule_blocks_id);
        $arrx = array(
                'start_time',
                'end_time'
        );
        $this->db->select($arrx);
        $q2 = $this->db->get("schedule_blocks");
        $result = $q2->result();
        
        $date_time = date('Y-m-d',strtotime($time_in));
                
        $arr = array();
        foreach($result as $row):
            $start_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->start_time));
            $end_time = date('Y-m-d H:i:s', strtotime($date_time." ".$row->end_time));
            
            if($row->end_time == "00:00:00"){
                $end_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->end_time));
            }
            
            if($row->end_time <= $row->start_time){                 
                $end_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->end_time . " +1 day"));               
            }
            
            if($time_in >= $start_time && $time_in <= $end_time):               
                return $end_time;
            else:
                $arr[] = $end_time;             
            endif;
        endforeach;

        
        foreach($arr as $key => $row2): 
            $time = date('H:i:s',strtotime($row2));
            if($time_in <= $row2 || $time == "00:00:00:"){          
                return $row2;
            }
        endforeach;
        
        if($last){
            $list = $this->get_blocks_list($last->schedule_blocks_id);
            $block_end = date('Y-m-d H:i:s',strtotime($date_time." ". $list->end_time)); 
            return $block_end;
        }
        return false;
    }

    public function get_total_hours($schedule_block_id){
        $w1 = array(
                "schedule_blocks_time_in_id"=>$schedule_block_id
        );
        $this->db->where($w1);
        $q1 = $this->db->get("schedule_blocks_time_in");
        $r1 = $q1->row();
        
        if($r1){
            $from       = $r1->time_in;
            $to         = $r1->time_out;
            $lunch_in   = $r1->lunch_in;
            $lunch_out  = $r1->lunch_out;
            $break_time = 0;
            
            if($lunch_in != "" && $lunch_out !=""){
                $b_cal      = $this->total_hours_worked($lunch_in, $lunch_out);
                $break_time = $this->convert_to_hours($b_cal);
            }
            
            $hours          = $this->total_hours_worked($to, $from);
            $hours_render   = $this->convert_to_hours($hours);
            
            return $hours_render - $break_time;
            
        }else{
            return false;
        }
    }
    
    public function get_total_hours_req($schedule_block_id,$time = false){
        $w1 = array(
                "schedule_blocks_time_in_id"=>$schedule_block_id
        );
        $this->db->where($w1);
        $q1         = $this->db->get("schedule_blocks_time_in");
        $r1         = $q1->row();
        $hours      = 0;
        $w          = false;
        $minutes    = 0;
        
        if($r1){
            $start      = $this->get_starttime($r1->schedule_blocks_id,$r1->time_in);
            $end        = $this->get_endtime($r1->schedule_blocks_id,$r1->time_in);
            $total      = strtotime($end) - strtotime($start);
            $hours      = floor($total / 60 / 60);
            $minutes    = floor(($total - ($hours * 60 * 60)) / 60);
        }else{
            return false;
        }
        
        if($time){
            return $hours.':'.$minutes.":00";
        }
        else{
            if(strlen($minutes)==1){
                $minutes="0".$minutes;
            }
            return $hours.'.'.$minutes;
        }
    }

    public function total_hours_worked($to,$from){
        $to         = date('Y-m-d H:i',strtotime($to));
        $from       = date('Y-m-d H:i',strtotime($from));
        $total      = strtotime($to) - strtotime($from);
        $hours      = floor($total / 60 / 60);
        $minutes    = floor(($total - ($hours * 60 * 60)) / 60);
        return  ($hours * 60) + $minutes;
    }

    public function convert_to_hours($min){
        $h  = date('H', mktime(0,$min));
        $m  = date('i', mktime(0,$min));
        $m2 = ($m /60) * 100;
        $m2 = sprintf("%02d", $m2);
        $t  = $h.".".$m2;
        return $t;
    }

    public function convert_to_min($min){
        $m = $min * 60;
        return $m;
    }

    public function calculate_attendance($company_id,$time_in,$time_out,$attendance_hours){

        if($attendance_hours){
            $h      = $hours * 60;
            $calc   = $this->total_hours_worked($time_out, $time_in);
    
            if($calc <= $h){
                return true;
            }
        }
        return false;
    }

    public function send_approvals($emp_id, $company_id, $timein_id, $employee_name, $approver_id, 
        $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
        $log_type="time_in", $work_sched_id, $punch_date, $split_timein_id="", $capture_img="")
    {

        $check_work_type_form = $this->employee->work_schedule_type($work_sched_id, $company_id);

        if($approver_id == "" || $approver_id == 0) {
            // Employee with no approver will use default workflow approval
            // add_workflow_approval_default_group($this->company_id,$this->account_id); // create default if dont have any
            $approver_id = get_app_default_approver($this->company_id,"Mobile Clock-in")->approval_groups_via_groups_id;
        }

        $employee_timein = $timein_id;
        $appr_id = $approver_id;

        $ret_date = $punch_date;
        $str = 'abcdefghijk123456789';
        $shuffled = str_shuffle($str);

        // generate token level
        $str2 = 'ABCDEFG1234567890';
        $shuffled2 = str_shuffle($str2);
        $new_logs = array('ret_date'=> $ret_date);

        $work_schedule_id   = $work_sched_id;
        // $check_work_type_form = "";
        $split_time_in_id = $split_timein_id;
        $non_split_time_in_id = $timein_id;
        $mobile_status = "";
        $location_field = "";
        $emp_time_id = $timein_id;


        if ($log_type == 'time_in' || $log_type == 'time in') {

            $mobile_status = "mobile_clockin_status";
            $location_field = "location_1";

        } else if ($log_type == 'lunch_out' || $log_type == 'lunch out') {

            $mobile_status = "mobile_lunchout_status";
            $location_field = "location_2";

        } else if ($log_type == 'lunch_in' || $log_type == 'lunch in') {

            $mobile_status = "mobile_lunchin_status";
            $location_field = "location_3";

        } else if ($log_type == 'time_out' || $log_type == 'time out') {

            $mobile_status = "mobile_clockout_status";
            $location_field = "location_4";

        } else if ($log_type == 'break1_out' || $log_type == 'break1 out') {

            $mobile_status = "mobile_break1_out_status";
            $location_field = "location_5";

        } else if ($log_type == 'break1_in' || $log_type == 'break1 in') {

            $mobile_status = "mobile_break1_in_status";
            $location_field = "location_6";

        } else if ($log_type == 'break2_out' || $log_type == 'break2 out') {

            $mobile_status = "mobile_break2_out_status";
            $location_field = "location_7";

        } else if ($log_type == 'break2_in' || $log_type == 'break2 in') {

            $mobile_status = "mobile_break2_in_status";
            $location_field = "location_8";

        }

        // if($check_work_type_form == "Workshift"){
        //     $emp_time_id = $timein_id;
        //     $employee_timein = $timein_id;
        //     $non_split_timein_id = $timein_id;
        // } else {
        //     $emp_time_id = $timein_id;
        //     $employee_timein = $timein_id;
        // }

        // workschedule

        if ($approver_id) {
            if ($is_workflow_enabled) {
                if ($hours_notification) {
                    $new_level = 1;
                    $lflag = 0;

                    if ($newtimein_approver) {
                        $employee_name = ($employee_name) ? ucwords($employee_name) : '';
                        foreach ($newtimein_approver as $cla) {
                            $appr_name = ucwords($cla->first_name." ".$cla->last_name);
                            $appr_account_id = $cla->account_id;
                            $appr_email = $cla->email;
                            $appr_id = $cla->emp_id;

                            if($cla->level == $new_level){
                                // send with link
                                $new_level = $cla->level;
                                $this->send_location_notification($location, $new_logs, $shuffled, $employee_timein, 
                                    $company_id, $emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2, 
                                    $appr_id, $mobile_status, $work_sched_id, $split_timein_id, $check_work_type_form, $capture_img);

                                if($hours_notification->message_board_notification == "yes"){
                                    #$token = $this->timeins->get_token($timein_info->approval_time_in_id, $this->company_id, $timein_info->emp_id);
                                    $url = base_url()."approval/employee_time_in/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
                                    $next_appr_notif_message = "{$employee_name} used app for clock-in and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
                                    send_to_message_board($this->psa_id, $appr_id, $this->account_id, $company_id, $next_appr_notif_message, "system", "warning");
                                }
                                
                                $lflag = 1;
                            } else {
                                // send notification without link
                                $this->send_location_notification($location, $new_logs, $shuffled, $employee_timein, 
                                    $company_id, $emp_id, $appr_email, $appr_name, "", "", "", "", "", 
                                    $mobile_status, $work_sched_id, $split_timein_id, $check_work_type_form, $capture_img);

                                if ($hours_notification->message_board_notification == "yes") {
                                    $next_appr_notif_message = "{$employee_name} used app for clock-in.";
                                    send_to_message_board($this->psa_id, $appr_id, $this->account_id, $company_id, $next_appr_notif_message, "system", "warning");
                                }
                            }
                        }
                    }

                        $save_token = array(
                                "time_in_id"        => $non_split_time_in_id,
                                "split_time_in_id"  => $split_time_in_id,
                                "token"             => $shuffled,
                                "comp_id"           => $company_id,
                                "emp_id"            => $emp_id,
                                "approver_id"       => $approver_id,
                                "level"             => $new_level,
                                "token_level"       => $shuffled2,
                                "location"          => $location_field,
                                "flag_add_logs"     => 2
                        );
                        $save_token_q = $this->db->insert("approval_time_in",$save_token);
                        $id = $this->db->insert_id();
                        

                    if ($check_work_type_form == "Workshift") {
                        $timein_update = array('approval_time_in_id'=>$id);
                        $this->db->where('schedule_blocks_time_in_id', $split_time_in_id);
                        $this->db->update('schedule_blocks_time_in',$timein_update);
                        $appr_err="";
                    }
                        $timein_update = array('approval_time_in_id'=>$id);
                        $this->db->where('employee_time_in_id', $emp_time_id);
                        $this->db->update('employee_time_in',$timein_update);
                        $appr_err="";
                    

                } else {

                        $save_token = array(
                                "time_in_id"        => $non_split_time_in_id,
                                "split_time_in_id"  => $split_time_in_id,
                                "token"             => $shuffled,
                                "comp_id"           => $this->company_id,
                                "emp_id"            => $this->emp_id,
                                "approver_id"       => $approver_id,
                                "level"             => 1,
                                "token_level"       => $shuffled2,
                                "location"          => $location_field,
                                "flag_add_logs"     => 2
                        );
                        $save_token_q = $this->db->insert("approval_time_in",$save_token);
                        $id = $this->db->insert_id();

                    if($check_work_type_form == "Workshift"){
                        
                        $timein_update = array('approval_time_in_id' => $id);
                        $this->db->where('schedule_blocks_time_in_id', $split_time_in_id);
                        $this->db->update('schedule_blocks_time_in', $timein_update);
                        $appr_err = "No Hours Notification";
                    }
                        
                        $timein_update = array('approval_time_in_id'=>$id);
                        $this->db->where('employee_time_in_id', $emp_time_id);
                        $this->db->update('employee_time_in',$timein_update);
                        $appr_err = "No Hours Notification";
                    
                }
            } else {
                if($get_approval_settings_disable_status->status == "Inactive") {
                                                    
                    if($get_approval_settings_disable_status->disabled_application_status == 'approve') {
                        $status = "approved";
                    } else {
                        $status = $get_approval_settings_disable_status->disabled_application_status;
                    }
                
                    $time_in_id = $insert_time_in->employee_time_in_id;
                    $this->db->where("employee_time_in_id",$time_in_id);
                    $update_val = array(
                            "time_in_status"    =>"approved",
                            "source"            => 'mobile',
                            $location_field     => $location,
                            "corrected"         => 'No',
                            $mobile_status      => $status
                    );
                    $this->db->update("employee_time_in",$update_val);
                
                
                    // echo json_encode(array("result"=>"1","error_msg"=>""));
                    // return false;
                }
            }

        } else {
            $time_in_id = $timein_id;
            $this->db->where("employee_time_in_id",$time_in_id);
            $update_val = array(
                "time_in_status"    => 'approved',
                "source"            => 'mobile',
                $location_field     => $location,
                "corrected"         => 'No',
                $mobile_status      => 'approved'
            );
            $this->db->update("employee_time_in",$update_val);
        }
    }

    public function send_location_notification($location = NULL, $new_logs = NULL, $token = NULL, $employee_timein = NULL, 
        $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "" , $withlink = "No", 
        $level_token = "", $appr_id = "", $mobile_status = "", $work_sched_id = "", $split_timein_id="", $check_work_type_form="", $img_name=""){
        
        if ($check_work_type_form) {
            $check_work_type = $check_work_type_form;
        } else {
            $check_work_type = $this->employee->work_schedule_type($work_sched_id, $comp_id);
        }

        if($check_work_type == 'Workshift') {
            $q = $this->employee->check_split_timein($emp_id,$comp_id,$split_timein_id);
        } else {
            $q = $this->employee->check_timein($emp_id,$comp_id,$employee_timein);
        }

        #p($new_logs);
        #echo $location.'/'.$token.'/'.$employee_timein.'/'.$comp_id.'/'.$emp_id.'/'.$email.'/'.$approver_full_name.'/'.$last_approver.'/'.$who.'/'.$withlink.'/'.$level_token.'/'.$appr_id.'/'.$mobile_status.'<br>';
        if($q != FALSE){
            $fullname = $this->employee->get_employee_fullname($emp_id,$this->company_id);
            $date_applied = date('F d, Y', strtotime($q->change_log_date_filed));
                    
            $font_name = "'Open Sans'";
            $link = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="'.base_url().'approval/employee_time_in/index/'.$token.'/'.$level_token.'/1'.$appr_id.'0/">View Mobile Login</a>';
            if($who == "Approver"){
                if($withlink == "No"){
                    $link = '';
                }
            }else{
                $link = "";
            }
            
            $location_1 = ($q->location_1) ? $q->location_1 : "";
            $location_2 = ($q->location_2) ? $q->location_2 : "";
            $location_3 = ($q->location_3) ? $q->location_3 : "";
            $location_4 = ($q->location_4) ? $q->location_4 : "";
            $location_5 = (isset($q->location_5)) ? $q->location_5 : "";
            $location_6 = (isset($q->location_6)) ? $q->location_6 : "";
            $location_7 = (isset($q->location_7)) ? $q->location_7 : "";
            $location_8 = (isset($q->location_8)) ? $q->location_8 : "";
            
            if($mobile_status == 'mobile_clockin_status') {
                $clock_in_label = 'Clock In';
                $clock_in_date = date('F d, Y h:i A', strtotime($q->time_in));
                $clock_location = $location_1;
            } elseif ($mobile_status == 'mobile_lunchout_status') {
                $clock_in_label = 'Lunch Out';
                $clock_in_date = date('F d, Y h:i A', strtotime($q->lunch_out));
                $clock_location = $location_2;
            } elseif ($mobile_status == 'mobile_lunchin_status') {
                $clock_in_label = 'Lunch In ';
                $clock_in_date = date('F d, Y h:i A', strtotime($q->lunch_in));
                $clock_location = $location_3;
            } elseif ($mobile_status == 'mobile_clockout_status') {
                $clock_in_label = 'Clock Out';
                $clock_in_date = date('F d, Y h:i A', strtotime($q->time_out));
                $clock_location = $location_4;
            } elseif ($mobile_status == 'mobile_break1_out_status') {
                $clock_in_label = 'First Break Out';
                $clock_in_date = date('F d, Y h:i A', strtotime($q->break1_out));
                $clock_location = $location_5;
            } elseif ($mobile_status == 'mobile_break1_in_status') {
                $clock_in_label = 'First Break In';
                $clock_in_date = date('F d, Y h:i A', strtotime($q->break1_in));
                $clock_location = $location_6;
            } elseif ($mobile_status == 'mobile_break2_out_status') {
                $clock_in_label = 'Second Break Out';
                $clock_in_date = date('F d, Y h:i A', strtotime($q->break2_out));
                $clock_location = $location_7;
            } elseif ($mobile_status == 'mobile_break2_in_status') {
                $clock_in_label = 'Second Break In';
                $clock_in_date = date('F d, Y h:i A', strtotime($q->break2_in));
                $clock_location = $location_8;
            } else {
                $clock_in_label = '';
                $clock_in_date = '';
                $clock_location = '';
            }
            
            $message_body = "";

            $img_dis = '';
			if ($img_name) {
				$img_dis = '<img style="position: absolute; top: -80px; right: -105px; border: 1px solid; padding: 5px; max-width: 100%; height: auto;" src="'.base_url().'/uploads/companies/'.$this->company_id.'/'.$img_name.'"  alt="clock in pic">';
			}
        
            $message_body = '
                    <tr>
                        <td style="width:150px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"> Applicant:</td>
                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$fullname.'</td>
                    </tr>
                                
                    <tr>
                        <td style="width:150px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date: </td>
                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.date('F d, Y', strtotime($new_logs['ret_date'])).'</td>
                    </tr>
                                
                    <tr>
                        <td style="width:150px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">'.$clock_in_label.':</td>
                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$clock_in_date.'</td>
                    </tr>
                                
                    <tr>
                        <td style="width:150px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Location:</td>
                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$clock_location.'</td>
                    </tr>
                                
                    <tr>
                        <td style="width:150px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Status:</td>
                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">Pending</td>
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
            $this->email->subject('Mobile Clock In Application - '.$fullname);
                
            $this->email->message('
            <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
            <html lang="en">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                    <meta name="format-detection" content="telephone=no">
                    <title>Mobile Clock In</title>
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
                                                                    <p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">'.$fullname.' used mobile app for clock-in. Details below:</p>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
													<td>
														<table width="100%" border="0" cellspacing="0" cellpadding="0">
															<tr>
																<td valign="top" style="padding-top:25px; width: 60%;">
																	<table width="100%" border="0" cellspacing="0" cellpadding="0">
																		'.$message_body.'
																		
																	</table>
																</td>
																<td style="width: 40%;">
																	<div style="position: relative; width: 119px;">
																		'.$img_dis.'
																	</div>
																</td>
															</tr>
														</table>
													</td>
													
												</tr>
												<tr>
													<td valign="top" style="font-size:12px; text-align: center; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">
														'.$link.'
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
            return true;
            // show_error("Invalid token");
        }
    }
}