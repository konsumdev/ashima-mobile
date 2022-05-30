<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * change_shift_30751_2018-09-09
 * change_shift_30751_2018-09-09
 * Import_timesheets_model model
 * @author Christopher Cuizon
 *
 */
class Import_timesheets_model_v2 extends CI_Model {

    public function import_new($company_id,$emp_id,$time_in,$lunch_out,$lunch_in,$fbreak_out,$fbreak_in,$sbreak_out,$sbreak_in,$time_out,$source="",$emp_no = "",$gDate="",$more_setting = array(),$real_employee_time_in_id = 0,$last_source_checker = "",$get_employee_payroll_information,$emp_employe_account_info,$emp_work_schedule_ess,$emp_work_schedule_epi,$list_of_blocks,$get_all_schedule_blocks,$get_all_regular_schedule,$check_rest_days,$get_work_schedule_flex,$company_holiday,$get_work_schedule,$get_employee_leave_application,$get_tardiness_settings,$get_all_employee_timein,$get_all_schedule_blocks_time_in,$attendance_hours,$get_tardiness_settingsv2,$tardiness_rounding,$migrate_v3=false,$holiday_settings_appr = "no",$inclusion_hoursx=false,$rest_day_settings="no",$cronjob_time_recalculate=false,$get_all_employee_period_typex=array(),$get_all_payroll_calendarx=array(),$get_nigtdiff_rule=false,$get_shift_restriction_schedules=false) {
        
        // added for coaching
        $inclusion_hours = "0";

        if($inclusion_hoursx){
            $inclusion_hours = "1";
        }
                    
        $void = $this->edit_delete_void($emp_id,$company_id,$gDate);

        if($void == "Waiting for approval"){
            return false;
            exit();
        }
        if($last_source_checker == "importv2"){
            return false;
            exit();
        }
        
        if(!$time_out){
            return false;
            exit();
        }

        if($source == ""){
            $source = "import";
        } 
        
        if($more_setting){
            $work_schedule_id       = $more_setting['work_schedule_id'];    
            $this->work_schedule_id = $more_setting['work_schedule_id'];
            $work_schedule_id2      = $more_setting['work_schedule_id'];
        }
        
        $employee_timein_date       = date("Y-m-d",strtotime($time_in));
        $log_error                  = false;
        $rd_ra                      = "no";

        $new_time_in                = ($time_in == "" || $time_in == " ") ? NULL : date('Y-m-d H:i:00',strtotime($time_in));
        $new_lunch_out              = ($lunch_out == "" || $lunch_out == " ") ? NULL : date('Y-m-d H:i:00',strtotime($lunch_out));
        $new_lunch_in               = ($lunch_in =="" || $lunch_in == " ") ? NULL : date('Y-m-d H:i:00',strtotime($lunch_in));

        // breaks
        $new_fbreak_out             = ($fbreak_out == "" || $fbreak_out == " ") ? NULL : date('Y-m-d H:i:00',strtotime($fbreak_out));
        $new_fbreak_in              = ($fbreak_in == "" || $fbreak_in == " ") ? NULL : date('Y-m-d H:i:00',strtotime($fbreak_in));
        $new_sbreak_out             = ($sbreak_out == "" || $sbreak_out == " ") ? NULL : date('Y-m-d H:i:00',strtotime($sbreak_out));
        $new_sbreak_in              = ($sbreak_in == "" || $sbreak_in == " ") ? NULL : date('Y-m-d H:i:00',strtotime($sbreak_in));

        $new_time_out               = ($time_out=="" || $time_out == " ") ? NULL : date('Y-m-d H:i:00',strtotime($time_out));               

        if($new_time_out < $new_time_in || $new_time_out == "" ){

            $log_error              = true;
        }
        if(($new_lunch_out!="" && $new_lunch_in=="") || ($new_lunch_out=="" && $new_lunch_in!="")) {

            $log_error              = true;
        }
        
        if($new_time_in > $new_time_out){
            $new_time_out           = null;
            $log_error              = true;
        }
        
        $reason                     = '';
        $flag_halfday               = 0;
        
        if ($emp_id){

            $payroll_group          = "";
            $emp_no                 = "";

            $payroll_group1         = in_array_custom("emp_id_{$emp_id}",$get_employee_payroll_information);
            if($payroll_group1){

                $payroll_group      = $payroll_group1->payroll_group_id;
            }

            $emp_no1                = in_array_custom("emp_id_{$emp_id}",$emp_employe_account_info);
            if($emp_no1){

                $emp_no             = $emp_no1->payroll_cloud_id;
            }
            
            $currentdate            = date('Y-m-d',strtotime($gDate));
            $employee_timein_date   = $currentdate;
            $gDate                  = date('Y-m-d',strtotime($gDate));
            $currentdate_orig_date_from_input = $currentdate;
            $employee_timein_date2  = date('Y-m-d',strtotime($currentdate. " -1 day"));

            // more setting is empty
            if(!$more_setting){
                
                $this->work_schedule_id         = "";
                $work_schedule_id2              = "";

                $emp_work_schedule_ess1         = in_array_custom("emp_id_{$emp_id}_{$currentdate}",$emp_work_schedule_ess);
                if($emp_work_schedule_ess1){

                    $this->work_schedule_id     = $emp_work_schedule_ess1->work_schedule_id;
                }
                else{
                    $emp_work_schedule_epi1     = in_array_custom("emp_id_{$emp_id}",$emp_work_schedule_epi);

                    if($emp_work_schedule_epi1){
                        $this->work_schedule_id = $emp_work_schedule_epi1->work_schedule_id;
                    }
                }

                $emp_work_schedule_ess1         = in_array_custom("emp_id_{$emp_id}_{$employee_timein_date2}",$emp_work_schedule_ess);
                if($emp_work_schedule_ess1){

                    $work_schedule_id2          = $emp_work_schedule_ess1->work_schedule_id;
                }
                else{
                    $emp_work_schedule_epi1     = in_array_custom("emp_id_{$emp_id}",$emp_work_schedule_epi);
                    if($emp_work_schedule_epi1){

                        $work_schedule_id2      = $emp_work_schedule_epi1->work_schedule_id;
                    }
                }
                $work_schedule_id               = $this->work_schedule_id;
            }
            
            $previous_split_sched               = $this->yesterday_split_info($time_in,$emp_id,$work_schedule_id2,$company_id,false,$list_of_blocks,$get_all_schedule_blocks);
            if($previous_split_sched){

                $last_sched                     = max($previous_split_sched);

                if(date('Y-m-d H:i:s',strtotime($time_in)) <= $last_sched['end_time']){

                    $yesterday_m                = date('Y-m-d',strtotime($time_in));
                    $currentdate                = date('Y-m-d',strtotime($yesterday_m. " -1 day"));
                    
                    if(!$more_setting){
                        $this->work_schedule_id = $work_schedule_id;
                    }
                }
            }
            
            #check if splist schedule 
            $split                              = $this->check_if_split_schedule($emp_id,$company_id,$this->work_schedule_id, $currentdate,$time_in,$time_out,$get_all_schedule_blocks,$list_of_blocks,$get_all_regular_schedule);

            /** if workshedule is not split schedule ***/
            if(!$split){
                // CHECK IF UNIFORM WORKINGS DAYS/WORKSHIFT OR FLEXIBLE HOURS
                
                if($this->work_schedule_id != FALSE){
                    /* EMPLOYEE WORK SCHEDULE */
                    $check_rest_day             = in_array_custom("rest_day_{$this->work_schedule_id}_".date("l",strtotime($currentdate)),$check_rest_days);
                }else{
                    $check_rest_day             = in_array_custom("rest_dayv2_{$payroll_group}_".date("l",strtotime($currentdate)),$check_rest_days);
                }

                if($this->work_schedule_id != FALSE){
                    /* EMPLOYEE WORK SCHEDULE */
                    $check_work_schedule_flex   = in_array_custom("flex_{$this->work_schedule_id}",$get_work_schedule_flex);
                }
                else{
                    $check_work_schedule_flex   = in_array_custom("flex_v2_{$payroll_group}",$get_work_schedule_flex);
                }
                
            }
            else{
                $check_rest_day                 = false;
                $check_work_schedule_flex       = false;
            }
            // check holiday

            $holiday                            = in_array_custom("holiday_{$currentdate}",$company_holiday);
            $holiday_orig                       = $holiday;
            
            #check employee on leave

            $onleave                            = $this->check_leave_appliction($currentdate,$emp_id,$company_id,$get_employee_leave_application,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_work_schedule,$get_all_regular_schedule,$get_work_schedule_flex,$get_all_schedule_blocks);

            $ileave                             = 'no';
            if($onleave){

                $ileave                         = 'yes';
            }
            
            /// GET THE LAST EMPLOYEE TIME IN STORE IN -=> time_query_row
            /*
            if($real_employee_time_in_id){
                $row_time_query                 = in_array_custom("timeins_id_{$real_employee_time_in_id}",$get_all_employee_timein);
            }
            else{
                $date_here                      = date("Y-m-d",strtotime($employee_timein_date));
                $row_time_query                 = in_array_custom("emp_id_timeins_date_{$emp_id}_{$date_here}",$get_all_employee_timein);
            }

            $time_query_row                     = "";

            if($row_time_query){
                $time_query_row                 = $row_time_query;
            }
            */

            if($real_employee_time_in_id){

                $time_query_where               = array(
                                                'employee_time_in_id'       => $real_employee_time_in_id,
                                                "status"                    => "Active"
                                                );
            }
            else{

                $time_query_where               = array(
                                                "comp_id"    => $company_id,
                                                "emp_id"     => $emp_id,
                                                "date"       => date("Y-m-d",strtotime($employee_timein_date)),
                                                "status"     => "Active"
                                                );
            }

            $this->db->where($time_query_where);
            $this->db->where("(time_in_status = 'approved' OR time_in_status is NULL)",FALSE,FALSE);
            $time_query                         = $this->db->get('employee_time_in');
            $time_query_row                     = $time_query->row();


            
            // WHERE VALES USE IN UPDATE
            if($real_employee_time_in_id){
                $time_query_where               = array(
                                                'employee_time_in_id'   => $real_employee_time_in_id,
                                                "status"                => "Active"
                                                );
            }else{
                $time_query_where               = array(
                                                "comp_id"               => $company_id,
                                                "emp_id"                => $emp_id,
                                                "date"                  => date("Y-m-d",strtotime($employee_timein_date)),
                                                "status"                => "Active"
                                                );
            }
            
            if($check_work_schedule_flex){

                $assumed_breaks_flex_b          = false;
                $flex_rules                     = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);


                $time_keep_holiday              = false;
                if($flex_rules){

                    // this is to check if workschedule is rd ra
                    if($flex_rules->enable_working_on_restday == "yes"){
                        $rd_ra                  = "yes";
                    }

                    $time_keep_holiday          = ($flex_rules->enable_breaks_on_holiday == 'yes') ? true : false;

                    $break_rules                = $flex_rules->break_rules;
                    $assumed_breaks             = $flex_rules->assumed_breaks;
                    $assumed_breaks             = $assumed_breaks * 60;
                                
                    if($break_rules == "assumed"){
                        $predict_new_time_out   = strtotime($new_time_out);
                        
                        $number_of_breaks_b     = $check_work_schedule_flex->duration_of_lunch_break_per_day;
                        $predict_luch_outxb     = strtotime($new_time_in."+ ".$assumed_breaks." minutes");
                        $predict_luch_inxb      = strtotime($predict_luch_outxb."+ ".$number_of_breaks_b." minutes");
                        
                        $predict_new_time_out   = strtotime($new_time_out);
                        $predict_luch_outxb     = strtotime($new_time_in."+ ".$assumed_breaks." minutes");
                        $predict_luch_outxb     = date("Y-m-d H:i:s",$predict_luch_outxb);
                        $predict_luch_inxb      = strtotime($predict_luch_outxb."+ ".$number_of_breaks_b." minutes");
                        $predict_luch_inxb1     = date("Y-m-d H:i:s",$predict_luch_inxb);
                        
                        $new_lunch_out          = $predict_luch_outxb;
                        $new_lunch_in           = $predict_luch_inxb1;
                        
                        if($predict_new_time_out <= $predict_luch_inxb){

                            $new_lunch_out      = null;
                            $new_lunch_in       = null;
                        }
                        
                        $assumed_breaks_flex_b  = true;
                    }
                    else if($break_rules == "disable"){
                        
                    }
                }

                // check holiday settings on shift now
                if($time_keep_holiday){
                    $holiday                    = false;
                }
                
                $late_min                       = $this->late_min($company_id,$currentdate,$emp_id,$work_schedule_id,$new_time_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);

                $overbreak_min                  = $this->overbreak_min($company_id,$currentdate,$emp_id,$work_schedule_id,$new_time_in,$new_lunch_out,$new_lunch_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);
                
                if($assumed_breaks_flex_b){

                    $overbreak_min              = 0;
                }
                
                $tardiness                      = 0; 
                $undertime                      = 0;
                
                // ****** #### #### ******* //
                $get_total_hours                = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;

                // update total hours and total hours required rest day
                if($check_rest_day){
                    $this->add_rest_day($time_query_row,$time_query_where,$log_error,$company_id,$emp_id,$currentdate,$new_time_in,$new_time_out,$source,$rest_day_settings);
                }
                else if($holiday){
                    $this->add_holiday_logs($time_query_row,$time_query_where,$log_error,$company_id,$emp_id,$currentdate,$new_time_in,$new_time_out,$work_schedule_id,$source,$holiday_settings_appr);
                }
                else{

                    /* WHOLE DAY */
                    $half_day                           = $this->if_half_day($new_time_in, $work_schedule_id, $company_id,$emp_no,$new_time_out,0,$emp_id,$currentdate,$get_all_regular_schedule,$get_work_schedule_flex);
                    
                    $check_latest_timein_allowed        = false;
                    $get_hoursworked                    = 0;
                    $number_of_breaks_per_day           = 0;
                    $get_hoursworkeds                   = in_array_custom("flex_{$work_schedule_id}",$get_work_schedule_flex);

                    if($get_hoursworkeds){
                        $get_hoursworked                = $get_hoursworkeds->total_hours_for_the_day;
                        $check_latest_timein_allowed    = $get_hoursworkeds->latest_time_in_allowed;
                        $number_of_breaks_per_day       = $get_hoursworkeds->duration_of_lunch_break_per_day;

                    }
                    $get_hoursworked_mins               = $get_hoursworked * 60;

                    // check workday settings
                    $workday_settings_start_time        = date("H:i:s",strtotime($new_time_in));
                    $workday_settings_end_time          = date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked_mins} minutes"));
                    
                    if($this->work_schedule_id != FALSE){
                        
                        if($flex_rules){
                            $break_rules                = $flex_rules->break_rules;
                            $assumed_breaks             = $flex_rules->assumed_breaks;
                            $assumed_breaks             = $assumed_breaks * 60;

                            if($break_rules == "assumed"){

                                $new_time_in;
                                $predict_new_time_out   = strtotime($new_time_out);
                                $number_of_breaks_b     = $this->elmf->check_break_time_flex($this->work_schedule_id,$company_id,false);
                                $predict_luch_outxb     = strtotime($new_time_in."+ ".$assumed_breaks." minutes");
                                $predict_luch_inxb      = strtotime($predict_luch_outxb."+ ".$number_of_breaks_b." minutes");
                                
                                $predict_new_time_out   = strtotime($new_time_out);
                                $number_of_breaks_b     = $this->elmf->check_break_time_flex($this->work_schedule_id,$company_id,false);
                                $predict_luch_outxb     = strtotime($new_time_in."+ ".$assumed_breaks." minutes");
                                $predict_luch_outxb     =  date("Y-m-d H:i:s",$predict_luch_outxb);
                                $predict_luch_inxb      = strtotime($predict_luch_outxb."+ ".$number_of_breaks_b." minutes");
                                $predict_luch_inxb1     =  date("Y-m-d H:i:s",$predict_luch_inxb);
                                
                                $new_lunch_out          = $predict_luch_outxb;
                                $new_lunch_in           = $predict_luch_inxb1;
                                
                                if($predict_new_time_out <= $predict_luch_inxb){

                                    $new_lunch_out      = null;
                                    $new_lunch_in       = null;
                                }
                            }
                            else if($break_rules == "disable"){
                        
                            }
                        }   
                        // not required login
                        if(!$check_latest_timein_allowed){
                            
                            if($number_of_breaks_per_day == 0){
                                // update total hours and total hours required rest day
                                $get_total_hours            = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
    
                                // update total tardiness
                                $update_tardiness           = 0;

                                // update undertime
                                $update_undertime           = 0;
                                $minx                       = $get_hoursworked * 60;
                                $workday_settings_end_time  = date("Y-m-d H:i:s",strtotime($new_time_in." +{$minx} minutes"));
                                
                                if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
                                    $update_undertime       = $this->total_hours_worked($workday_settings_end_time,$new_time_out);
                                }
                                
                                // check tardiness value
                                $flag_tu                    = 0;
                                $hours_worked               = $get_hoursworked;
                                
                                // required hours worked only
                                $new_total_hours            = $this->total_hours_worked($new_time_out,$new_time_in);

                                if($time_query_row) {

                                    if($source == "updated" || $source == "recalculated" || $source == "import"){
                                        if($time_query_row->flag_regular_or_excess == "excess"){
                                            $get_total_hours    = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                                            $get_total_hours    = ($get_total_hours < 0) ? 0 : $get_total_hours;
                                            $tq_update_field    = array(
                                                                "comp_id"                   => $company_id,
                                                                "emp_id"                    => $emp_id,
                                                                "date"                      => $currentdate,
                                                                "time_in"                   => $new_time_in,
                                                                "time_out"                  => $new_time_out,
                                                                "undertime_min"             => 0,
                                                                "tardiness_min"             => 0,
                                                                "late_min"                  => 0,
                                                                "overbreak_min"             => 0,
                                                                "absent_min"                => 0,
                                                                "work_schedule_id"          => "-2",
                                                                "total_hours"               => $get_total_hours,
                                                                "total_hours_required"      => $get_total_hours,
                                                                "flag_regular_or_excess"    => "excess",
                                                                );
                                            // added for coaching
                                            if($inclusion_hours == "1"){
                                                $tq_update_field["inclusion_hours"]         = $inclusion_hours;
                                            }

                                            // if source is from cronjob dont change last source
                                            if(!$cronjob_time_recalculate){

                                                if($time_query_row->source!=""){
                                                    $tq_update_field['last_source'] = $source;
                                                    $tq_update_field['change_log_date_filed'] = date('Y-m-d H:i:s');
                                                }
                                                else{
                                                    $tq_update_field['last_source'] = 'dashboard';
                                                }

                                            }
                                        }else{
                                            if($log_error){
                                                $tq_update_field    = array(
                                                                    "time_in"               =>$new_time_in,
                                                                    "time_out"              =>$new_time_out,
                                                                    "undertime_min"         =>0,
                                                                    "tardiness_min"         => 0,
                                                                    "work_schedule_id"      => $work_schedule_id,
                                                                    "total_hours"           =>0,
                                                                    "absent_min"            => 0,
                                                                    "lunch_in"              => null,
                                                                    "lunch_out"             => null,
                                                                    "total_hours_required"  =>0
                                                                    );
                                                
                                            }else{

                                                $tq_update_field = array(
                                                                "time_in"               => $new_time_in,
                                                                "time_out"              => $new_time_out,
                                                                "undertime_min"         => $update_undertime,
                                                                "tardiness_min"         => $update_tardiness,
                                                                "work_schedule_id"      => $work_schedule_id,
                                                                "total_hours"           => $get_hoursworked,
                                                                "absent_min"            => 0,
                                                                "lunch_in"              => null,
                                                                "lunch_out"             => null,
                                                                "overbreak_min"         => null,
                                                                "late_min"              => 0,
                                                                "total_hours_required"  => $get_total_hours,
                                                                "flag_on_leave"         => $ileave
                                                                );
                                                
                                                // added for coaching
                                                if($inclusion_hours == "1"){
                                                    $tq_update_field["inclusion_hours"] = $inclusion_hours;
                                                }
                                            }
                                            
                                            // if source is from cronjob dont change last source
                                            if(!$cronjob_time_recalculate){
                                                if($time_query_row->source!=""){
                                                    $tq_update_field['last_source'] = $source;
                                                    $tq_update_field['change_log_date_filed'] = date('Y-m-d H:i:s');
                                                }
                                            }

                                            //attendance settings
                                            $att = $this->calculate_attendance($company_id,$new_time_in,$new_time_out,$attendance_hours);

                                            if($att){
                                                $total_hours_worked                         = $this->total_hours_worked($new_time_out, $new_time_in);
                                                $total_hours_worked                         = $this->convert_to_hours($total_hours_worked);
                                                $tq_update_field['lunch_in']                = null;
                                                $tq_update_field['lunch_out']               = null;
                                                $tq_update_field['total_hours_required']    = $total_hours_worked;
                                                $tq_update_field['absent_min']              = ($get_hoursworked - $total_hours_worked) * 60;
                                                $tq_update_field['late_min']                = 0;
                                                $tq_update_field['tardiness_min']           = 0;
                                                $tq_update_field['undertime_min']           = 0;
                                            }
                                        }

                                        // this is for rd ra
                                        if($rd_ra == "yes"){
                                            $tq_update_field['rest_day_r_a']                = "yes";

                                            // if need approval
                                            if($rest_day_settings == "yes"){
                                                $tq_update_field['flag_rd_include']         = "no";
                                            }
                                        }else{
                                            $tq_update_field['rest_day_r_a']                = "no";

                                            // if need approval
                                            if($rest_day_settings == "yes"){
                                                $tq_update_field['flag_rd_include']         = "yes";
                                            }
                                        }

                                        // if holiday needs approval based on settings
                                        if($holiday_orig){
                                            if($holiday_settings_appr == "yes"){
                                                $tq_update_field['holiday_approve']         = "yes";
                                                $tq_update_field['flag_holiday_include']    = "no";
                                            }else{
                                                $tq_update_field['holiday_approve']         = "no";
                                                $tq_update_field['flag_holiday_include']    = "yes";
                                            }
                                        }else{
                                            $tq_update_field['holiday_approve']             = "no";
                                            $tq_update_field['flag_holiday_include']        = "yes";
                                        }

                                        // exclude pmax
                                        // total hours should not exceeds the required hours
                                        if($company_id == "316"){
                                            if($tq_update_field["total_hours_required"] > $tq_update_field["total_hours"]){
                                                //$tq_update_field["total_hours_required"] = $tq_update_field["total_hours"];
                                            }
                                        }

                                        $this->db->where($time_query_where);
                                        $this->db->update('employee_time_in',$tq_update_field);

                                        // athan helper
                                        if($currentdate){
                                            payroll_cronjob_helper($type='timesheet',$currentdate,$emp_id,$company_id);
                                        }
                                    }else{

                                        if($source == "recalculated"){
                                            return false;
                                        }

                                        $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                                        $get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
                                        
                                        $date_insert    = array(
                                                        "source"                    => 'dashboard',
                                                        "comp_id"                   => $company_id,
                                                        "emp_id"                    => $emp_id,
                                                        "date"                      => $currentdate,
                                                        "time_in"                   => $new_time_in,
                                                        "time_out"                  => $new_time_out,
                                                        "undertime_min"             => 0,
                                                        "tardiness_min"             => 0,
                                                        "late_min"                  => 0,
                                                        "overbreak_min"             => 0,
                                                        "work_schedule_id"          => "-2",
                                                        "total_hours"               => $get_total_hours,
                                                        "total_hours_required"      => $get_total_hours,
                                                        "flag_regular_or_excess"    => "excess",
                                                        );

                                        $add_logs       = $this->db->insert('employee_time_in', $date_insert);

                                        // athan helper
                                        if($currentdate){
                                            payroll_cronjob_helper($type='timesheet',$currentdate,$emp_id,$company_id);
                                        }
                                    }
                                }
                                else{
                                    
                                    if($source == "recalculated"){
                                        return false;
                                    }

                                    if($log_error){
                                        $date_insert    = array(
                                                        "comp_id"               =>$company_id,
                                                        "emp_id"                =>$emp_id,
                                                        "date"                  => $currentdate,
                                                        "time_in"               => $new_time_in,
                                                        "time_out"              => $new_time_out,
                                                        "tardiness_min"         => 0,
                                                        "undertime_min"         => 0,
                                                        "total_hours"           => 0,
                                                        "absent_min"            => 0,
                                                        "total_hours_required"  =>  0
                                                        );
                                    }
                                    else{
                                        $date_insert    = array(
                                                        "comp_id"               => $company_id,
                                                        "emp_id"                => $emp_id,
                                                        "source"                => $source,
                                                        "date"                  => $currentdate,
                                                        "time_in"               => $new_time_in,
                                                        "time_out"              => $new_time_out,
                                                        "undertime_min"         => $update_undertime,
                                                        "tardiness_min"         => $update_tardiness,
                                                        "work_schedule_id"      => $work_schedule_id,
                                                        "total_hours"           => $get_hoursworked,
                                                        "total_hours_required"  => $get_total_hours,
                                                        "flag_on_leave"         => $ileave
                                                        );
                                        
                                        //attendance settings
                                        $att = $this->calculate_attendance($company_id,$new_time_in,$new_time_out,$attendance_hours);
                                        
                                        if($att){
                                            $total_hours_worked                     = $this->total_hours_worked($new_time_out, $new_time_in);
                                            $total_hours_worked                     = $this->convert_to_hours($total_hours_worked);
                                            $date_insert['lunch_in']                = null;
                                            $date_insert['lunch_out']               = null;
                                            $date_insert['total_hours_required']    = $total_hours_worked;
                                            $date_insert['absent_min']              = ($get_hoursworked - $total_hours_worked) * 60;
                                            $date_insert['late_min']                = 0;
                                            $date_insert['tardiness_min']           = 0;
                                            $date_insert['undertime_min']           = 0;
                                        }
                                    }

                                    // this is for rd ra
                                    if($rd_ra == "yes"){
                                        $date_insert['rest_day_r_a']                = "yes";
                                        $date_insert['flag_rd_include']             = "no";

                                        // if need approval
                                        if($rest_day_settings == "yes"){
                                            $date_insert['flag_rd_include']         = "no";
                                        }
                                    }
                                    else{
                                        $date_insert['rest_day_r_a']                = "no";

                                        // if need approval
                                        if($rest_day_settings == "yes"){
                                            $date_insert['flag_rd_include']         = "yes";
                                        }
                                    }

                                    // if holiday needs approval based on settings
                                    if($holiday_orig){
                                        if($holiday_settings_appr == "yes"){
                                            $date_insert['holiday_approve']         = "yes";
                                            $date_insert['flag_holiday_include']    = "no";
                                        }else{
                                            $date_insert['holiday_approve']         = "no";
                                            $date_insert['flag_holiday_include']    = "yes";
                                        }
                                    }else{
                                        $date_insert['holiday_approve']             = "no";
                                        $date_insert['flag_holiday_include']        = "yes";
                                    }

                                    // exclude pmax
                                    // total hours should not exceeds the required hours
                                    if($company_id == "316"){
                                        if($date_insert["total_hours_required"] > $date_insert["total_hours"]){
                                            //$date_insert["total_hours_required"] = $date_insert["total_hours"];
                                        }
                                    }

                                    $add_logs = $this->db->insert('employee_time_in', $date_insert);

                                    // athan helper
                                    if($currentdate){
                                        payroll_cronjob_helper($type='timesheet',$currentdate,$emp_id,$company_id);
                                    }
                                }

                            }
                            else{

                                $update_absent_min_x    = 0;

                                // update tardiness for timein
                                $tardiness_timein       = 0;
                                
                                // update tardiness for break time
                                $update_tardiness_break_time        = 0;
                                $duration_of_lunch_break_per_day    = $number_of_breaks_per_day;
                                $lunch_break                        = $duration_of_lunch_break_per_day;
                                $lunch_break_min                    = $duration_of_lunch_break_per_day / 60;
                                $tardiness_a                        = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
                                
                                if($duration_of_lunch_break_per_day < $tardiness_a){
                                    $update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
                                }
                                
                                $break =0;
                                if($half_day==0){
                                    $break = $lunch_break_min;
                                    $workday_settings_end_time = date("H:i:s",strtotime($workday_settings_end_time." +{$duration_of_lunch_break_per_day} minutes"));
                                }
                                
                                $get_hoursworked    = $get_hoursworked - $lunch_break_min;
                                // this to correct total hours but i dont know if its correct :(
                                $date_a             = new DateTime($new_time_in);
                                $date_b             = new DateTime($new_time_out);
                                $interval           = date_diff($date_a,$date_b);
                                $hour_bx            = $interval->format('%h');
                                $min_bx             = $interval->format('%i');
                                $min_bx             = $min_bx / 60;
                                $hours_worked_new   = $hour_bx + $min_bx;
                                
                                // trap if halfday
                                // update total hours
                                $hours_worked       = $get_hoursworked - $lunch_break_min;
                                $update_total_hours = $hours_worked_new - $lunch_break_min;
                                
                                // update total tardiness
                                $update_tardiness = $tardiness_timein + $update_tardiness_break_time;
                                
                                // update undertime
                                $update_undertime = 0;
                                if($update_total_hours < $hours_worked){
                                    $update_undertime = ($hours_worked - $update_total_hours) * 60;
                                }
                                
                                if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){

                                    $hours_worked               = $get_hoursworked;
                                    $hours_worked_mins          = $hours_worked * 60;
                                    $workday_settings_end_time  = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
                                }
                                
                                if($hours_worked_new < $hours_worked){
                                    // ATTENDANCE SETTINGS
                                    if($attendance_hours){
                                        $as_sethour = $attendance_hours;
                                        if(is_numeric($as_sethour)){
                                            if($hours_worked_new < $hours_worked){
                                                $update_undertime = 0;
                                                $update_absent_min_x =  ($hours_worked - $as_sethour) * 60;
                                                
                                                $update_undertime    =  ($hours_worked - ($hours_worked_new + $as_sethour)) * 60;
                                                if($update_undertime < 0){
                                                    $update_undertime = 0;
                                                }
                                            }
                                        }
                                    }
                                    else{
                                        if($hours_worked_new < $hours_worked){
                                            $update_undertime = ($hours_worked - $hours_worked_new) * 60;
                                        }
                                    }
                                    
                                    $new_break  = 0;
                                    $get_half   = $get_hoursworked / 2;
                                    if($lunch_break_min > 0){
                                        $new_break = $lunch_break_min;
                                    }
                                    $get_half = $get_half + $new_break;
                                    
                                    if(!$assumed_breaks_flex_b){
                                        if($hours_worked_new <= $get_half){
                                            $new_lunch_out      = NULL;
                                            $new_lunch_in       = NULL;
                                            $lunch_break_min    = 0;
                                        }
                                    }
                                }
                                
                                // minus break
                                /** --BARACK FLEX CORRECTION-- **/
                                
                                $tox                = date('Y-m-d H:i',strtotime($new_time_out));
                                $fromx              = date('Y-m-d H:i',strtotime($new_time_in));
                                $totalx             = strtotime($tox) - strtotime($fromx);
                                $hoursx             = floor($totalx / 60 / 60);
                                $minutesx           = floor(($totalx - ($hoursx * 60 * 60)) / 60);
                                $hours_worked_new1  = (($hoursx * 60) + $minutesx)/60;
                                
                                $update_total_hours = $hours_worked_new1 - $lunch_break_min;
                                
                                //check undertime
                                if($get_hoursworked > $update_total_hours){
                                    $update_undertime = ($get_hoursworked - $update_total_hours) * 60;
                                }
                                
                                // calculate total hours break if assume
                                if($flex_rules){
                                    $break_rules    = $flex_rules->break_rules;
                                    $assumed_breaks = $flex_rules->assumed_breaks;
                                    $assumed_breaks = $assumed_breaks * 60;
                                
                                    if($break_rules == "assumed"){
                                        $predict_new_time_out   = strtotime($new_time_out);
                                        $number_of_breaks_b     = $number_of_breaks_per_day;
                                        $predict_luch_outxb     = strtotime($new_time_in."+ ".$assumed_breaks." minutes");
                                        $predict_luch_outxb1    = date("Y-m-d H:i:s",$predict_luch_outxb);
                                        $predict_luch_inxb      = strtotime($predict_luch_outxb1."+ ".$number_of_breaks_b." minutes");
                                        $predict_luch_inxb1     = date("Y-m-d H:i:s",$predict_luch_inxb);
                                        
                                        if($predict_new_time_out <= $predict_luch_inxb){
                                            
                                            if($predict_new_time_out <= $predict_luch_outxb){
                                                $tox = $new_time_out;
                                            }else{
                                                $tox = $predict_luch_outxb1;
                                            }
                                            $fromx              = date('Y-m-d H:i',strtotime($new_time_in));
                                            $totalx             = strtotime($tox) - strtotime($fromx);
                                            $hoursx             = floor($totalx / 60 / 60);
                                            $minutesx           = floor(($totalx - ($hoursx * 60 * 60)) / 60);
                                            $hours_worked_new1  = (($hoursx * 60) + $minutesx)/60;
                                            $update_total_hours = $hours_worked_new1;
                                            
                                            if($get_hoursworked > $update_total_hours){
                                                $update_undertime = ($get_hoursworked - $update_total_hours) * 60;
                                            }
                                        }
                                    }
                                    else if($break_rules == "capture"){
                                        $time_in                        = date('Y-m-d H:i',strtotime($time_in));
                                        $lunch_out                      = date('Y-m-d H:i',strtotime($lunch_out));
                                        $lunch_in                       = date('Y-m-d H:i',strtotime($lunch_in));
                                        $time_out                       = date('Y-m-d H:i',strtotime($time_out));
                                        
                                        $shift_hour_worked              = $get_hoursworked;
                                        $shift_half_worked              = $shift_hour_worked/2;
                                        $shift_half_worked_min          = $shift_half_worked * 60;
                                        $update_total_hours_required    = "";
                                        $late_min                       = 0;
                                        $overbreak_min                  = 0;
                                        $break_for_break                = 0;
                                        $update_undertime               = 0;
                                    
                                        $fromx                          = date('Y-m-d H:i',strtotime($time_in));
                                        $tox                            = date('Y-m-d H:i',strtotime($time_out));
                                        $totalx                         = strtotime($tox) - strtotime($fromx);
                                        $hoursx                         = floor($totalx / 60 / 60);
                                        $minutesx                       = floor(($totalx - ($hoursx * 60 * 60)) / 60);
                                        $update_total_hours             = (($hoursx * 60) + $minutesx)/60;
                                        
                                        $assumed_capture_lunch_out      = date("Y-m-d H:i:s",strtotime($time_in ."+ ".$shift_half_worked_min." minutes"));
                                        $assumed_capture_lunch_in       = date("Y-m-d H:i:s",strtotime($assumed_capture_lunch_out ."+ ".$duration_of_lunch_break_per_day." minutes"));
                                        
                                        if($lunch_in > $lunch_out){
                                            $break_for_break    = $duration_of_lunch_break_per_day;
                                            $fromo              = date('Y-m-d H:i',strtotime($lunch_out));
                                            $too                = date('Y-m-d H:i',strtotime($lunch_in));
                                            $totalo             = strtotime($too) - strtotime($fromo);
                                            $hourso             = floor($totalo / 60 / 60);
                                            $minuteso           = floor(($totalo - ($hourso * 60 * 60)) / 60);
                                            $actual_break       = (($hourso * 60) + $minuteso);

                                            if($actual_break > $break_for_break){
                                                $overbreak_min  = $actual_break - $break_for_break;
                                            }
                                        }
                                        else{
                                            // if timeOut before assumed capture break
                                            if(strtotime($assumed_capture_lunch_out) >= strtotime($time_out)){
                                                $break_for_break = 0;
                                            }
                                            // if timeOut between break
                                            else if(strtotime($assumed_capture_lunch_out) < strtotime($time_out) && strtotime($assumed_capture_lunch_in) >= strtotime($time_out)){
                                                $fromb                          = date('Y-m-d H:i',strtotime($time_out));
                                                $tob                            = date('Y-m-d H:i',strtotime($assumed_capture_lunch_in));
                                                $totalb                         = strtotime($tob) - strtotime($fromb);
                                                $hoursb                         = floor($totalb / 60 / 60);
                                                $minutesb                       = floor(($totalb - ($hoursb * 60 * 60)) / 60);
                                                $minus_for_break                = (($hoursb * 60) + $minutesb);
                                                $break_for_break                = $duration_of_lunch_break_per_day;
                                            }
                                            // timein after assumed capture break
                                            else if(strtotime($assumed_capture_lunch_in) <= strtotime($time_out)){
                                                $break_for_break = 0;
                                            }
                                            // if timeOut after assumed capture break
                                            else{
                                                $break_for_break = $duration_of_lunch_break_per_day;
                                            }
                                        }

                                        // TOTAL HOURS WORKED
                                        $update_total_hours = $update_total_hours - ($break_for_break/60);

                                        // TARDINESS
                                        $update_tardiness   = $late_min + $overbreak_min;

                                        // UNDERTIME
                                        if(($shift_hour_worked * 60) > (($update_total_hours_required * 60) + $update_tardiness)){
                                            $update_undertime = (($shift_hour_worked * 60) - (($update_total_hours * 60) + $update_tardiness));
                                        }
                                    }
                                }
                                
                                // *** CHECKER
                                /**
                                p($break_for_break);
                                p($assumed_capture_lunch_out);
                                p($assumed_capture_lunch_in);
                                p($shift_half_worked);
                                p($shift_hour_worked);
                                p($update_total_hours);
                                p($update_undertime);
                                p($update_tardiness);
                                exit("here");
                                **/
                                 // *** END CHECKER **/
                                
                                
                                /** -- END BARACK FLEX CORRECTION-- **/
                                
                                // check tardiness value
                                $get_total_hours_worked = ($hours_worked / 2) + .5;

                                if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0 && $half_day == 0){

                                    $update_tardiness   = 0;
                                    $flag_tu            = 1;
                                }                               
                                
                                // update total hours required
                                $update_total_hours_required = ((strtotime($new_time_out) - strtotime($new_time_in)) / 3600) - ($update_tardiness / 60) - $break;
                                
                                // if value is less then 0 then set value to 0
                                if($update_tardiness < 0) $update_tardiness                         = 0;
                                if($update_undertime < 0) $update_undertime                         = 0;
                                if($update_total_hours < 0) $update_total_hours                     = 0;
                                if($update_total_hours_required < 0) $update_total_hours_required   = 0;

                                // night diff additionall rule
                                $current_date_nsd           = 0;
                                $next_date_nsd              = 0;
                                $total_hours_deduct         =  (strtotime($new_lunch_in) - strtotime($new_lunch_out))/60;
                                $total_hours_deduct         = ($total_hours_deduct < $number_of_breaks_per_day) ? $number_of_breaks_per_day : $total_hours_deduct;
                                $total_hours_deduct         = ($total_hours_deduct <= 0) ? 0 : $total_hours_deduct;

                                if($get_nigtdiff_rule){
                                    $epi_nsd                = in_array_custom("emp_id_{$emp_id}",$get_employee_payroll_information);
                                    
                                    if($epi_nsd){
                                        $nsd_lunch          = false;
                                        $epi_nsd_x          = $epi_nsd->nightshift_differential_rule_name;

                                        if($epi_nsd_x == "time_bound"){
                                            if($get_nigtdiff_rule->exclude_entitlement_nsd_lunch_break == "yes"){
                                                $nsd_lunch      = true;
                                            }
                                        }
                                        else if($epi_nsd_x == "shift_restriction"){
                                            if($get_nigtdiff_rule->exclude_entitlement_nsd_sr_lunch_break == "yes"){
                                                $nsd_lunch      = true;
                                            }
                                        }
                                        if($nsd_lunch){
                                            $from_time              = $get_nigtdiff_rule->from_time;
                                            $to_time                = $get_nigtdiff_rule->to_time;
                                            $to_time_currentdate    = $currentdate;

                                            if($from_time > $to_time){
                                                $to_time_currentdate = date("Y-m-d",strtotime($currentdate ."+ 1 day"));
                                            }

                                            $from_time              = $currentdate." ".$from_time;
                                            $to_time                = $to_time_currentdate." ".$to_time;

                                            $total_n_diff           = 0;
                                            if($lunch_out > $from_time && $lunch_in < $to_time){
                                                if(date("Y-m-d",strtotime($lunch_out)) == $currentdate){
                                                    $current_date_nsd   = $total_hours_deduct;
                                                }else{
                                                    $next_date_nsd      = $total_hours_deduct;
                                                }
                                            }
                                            else if($lunch_out < $from_time && $lunch_in > $from_time){
                                                $total_n_diff       = $this->total_hours_worked($lunch_in,$from_time);

                                                if(date("Y-m-d",strtotime($lunch_out)) == $currentdate){
                                                    $current_date_nsd   = $total_n_diff;
                                                }else{
                                                    $next_date_nsd      = $total_n_diff;
                                                }
                                            }
                                            else if($lunch_out < $to_time && $lunch_in > $to_time){
                                                $total_n_diff       = $this->total_hours_worked($to_time,$lunch_out);
                                                if(date("Y-m-d",strtotime($lunch_out)) == $currentdate){
                                                    $current_date_nsd   = $total_n_diff;
                                                }else{
                                                    $next_date_nsd      = $total_n_diff;
                                                }
                                            }
                                        }
                                    }
                                }
                                
                                if($time_query_row) {

                                    if($source == "updated" || $source == "recalculated" || $source == "import"){
                                        if($time_query_row->flag_regular_or_excess == "excess"){

                                            $get_total_hours    = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                                            $get_total_hours    = ($get_total_hours < 0) ? 0 : $get_total_hours;
                                            $tq_update_field    = array(
                                                                "comp_id"                   => $company_id,
                                                                "emp_id"                    => $emp_id,
                                                                "date"                      => $currentdate,
                                                                "time_in"                   => $new_time_in,
                                                                "time_out"                  => $new_time_out,
                                                                "undertime_min"             => 0,
                                                                "tardiness_min"             => 0,
                                                                "late_min"                  => 0,
                                                                "overbreak_min"             => 0,
                                                                "absent_min"                => 0,
                                                                "work_schedule_id"          => "-2",
                                                                "total_hours"               => $get_total_hours,
                                                                "total_hours_required"      => $get_total_hours,
                                                                "flag_regular_or_excess"    => "excess",
                                                                );

                                            // added for coaching
                                            if($inclusion_hours == "1"){
                                                $tq_update_field["inclusion_hours"]         = $inclusion_hours;
                                            }

                                            // if source is from cronjob dont change last source
                                            if(!$cronjob_time_recalculate){
                                                if($time_query_row->source!=""){
                                                    $tq_update_field['last_source'] = $source;
                                                    $tq_update_field['change_log_date_filed'] = date('Y-m-d H:i:s');
                                                }
                                                else{
                                                    $tq_update_field['last_source'] = 'Dashboard';
                                                }
                                            }
                                        }
                                        else{
                                            if($log_error){
                                                $tq_update_field    = array(
                                                                    "time_in"               => $new_time_in,
                                                                    "lunch_out"             => $new_lunch_out,
                                                                    "lunch_in"              => $new_lunch_in,
                                                                    "time_out"              => $new_time_out,
                                                                    "undertime_min"         => 0,
                                                                    "tardiness_min"         => 0,
                                                                    "work_schedule_id"      => $work_schedule_id,
                                                                    "total_hours"           => 0,
                                                                    "late_min"              => 0,
                                                                    "overbreak_min"         => 0,
                                                                    "total_hours_required"  => 0
                                                                    );
                                            }
                                            else{
                                                $tq_update_field    = array(
                                                                    "time_in"               => $new_time_in,
                                                                    "lunch_out"             => $new_lunch_out,
                                                                    "lunch_in"              => $new_lunch_in,
                                                                    "time_out"              => $new_time_out,
                                                                    "undertime_min"         => $update_undertime,
                                                                    "tardiness_min"         => $update_tardiness,
                                                                    "absent_min"            => 0,
                                                                    "work_schedule_id"      => $work_schedule_id,
                                                                    "late_min"              => $late_min,
                                                                    "overbreak_min"         => $overbreak_min,
                                                                    "total_hours"           => $get_hoursworked,
                                                                    "total_hours_required"  => $update_total_hours,
                                                                    "current_date_nsd"      => $current_date_nsd,
                                                                    "next_date_nsd"         => $next_date_nsd,
                                                                    "flag_on_leave"         => $ileave
                                                                    );

                                                // added for coaching
                                                if($inclusion_hours == "1"){
                                                    $tq_update_field["inclusion_hours"]     = $inclusion_hours;
                                                }
                                                if($assumed_breaks_flex_b){
                                                    $tq_update_field['flag_insert_assume_break'] = '1';
                                                }else{
                                                    $tq_update_field['flag_insert_assume_break'] = '0';
                                                }

                                            }
                                            
                                            // if source is from cronjob dont change last source
                                            if(!$cronjob_time_recalculate){
                                                if($time_query_row->source!=""){
                                                    $tq_update_field['last_source'] = $source;
                                                    $tq_update_field['change_log_date_filed'] = date('Y-m-d H:i:s');
                                                }
                                            }
                                            
                                            // ***** NEW COMPUTATION FOR ATTENDANCE SETTINGS
                                            if($attendance_hours){
                                                if($update_total_hours <= $att){
                                                    if($time_in >= $lunch_out){
                                                        $tq_update_field['lunch_out']   = null;
                                                        $tq_update_field['lunch_in']    = null;
                                                    }
                                                    elseif($time_out <= $lunch_in){
                                                        $tq_update_field['lunch_out']   = null;
                                                        $tq_update_field['lunch_in']    = null;
                                                    }
                                            
                                                    $half_day_h = ($hours_worked / 2) * 60;
                                                    if($update_undertime > $late_min){
                                                        $tq_update_field['late_min']        = $late_min;
                                                        $tq_update_field['tardiness_min']   = $update_tardiness;
                                                        $tq_update_field['undertime_min']   = 0;
                                                        $tq_update_field['absent_min']      = (($get_hoursworked - $update_total_hours) * 60) - $update_tardiness;
                                                    }
                                                    else{
                                                        $tq_update_field['late_min']        = 0;
                                                        $tq_update_field['tardiness_min']   = 0;
                                                        $tq_update_field['undertime_min']   = $update_undertime;
                                                        $tq_update_field['absent_min']      = (($get_hoursworked - $update_total_hours) * 60) - $update_undertime;
                                                    }
                                                    $date_insert['total_hours_required']    = $update_total_hours;
                                                }
                                            }
                                        }
                                        
                                        // this is for rd ra
                                        if($rd_ra == "yes"){
                                            $tq_update_field['rest_day_r_a']                = "yes";

                                            // if need approval
                                            if($rest_day_settings == "yes"){
                                                $tq_update_field['flag_rd_include']         = "no";
                                            }
                                        }
                                        else{
                                            $tq_update_field['rest_day_r_a']                = "no";

                                            // if need approval
                                            if($rest_day_settings == "yes"){
                                                $tq_update_field['flag_rd_include']         = "yes";
                                            }
                                        }

                                        // if holiday needs approval based on settings
                                        if($holiday_orig){
                                            if($holiday_settings_appr == "yes"){
                                                $tq_update_field['holiday_approve']         = "yes";
                                                $tq_update_field['flag_holiday_include']    = "no";
                                            }
                                            else{
                                                $tq_update_field['holiday_approve']         = "no";
                                                $tq_update_field['flag_holiday_include']    = "yes";
                                            }
                                        }else{
                                            $tq_update_field['holiday_approve']             = "no";
                                            $tq_update_field['flag_holiday_include']        = "yes";
                                        }

                                        // insert if lunch deducted on holiday
                                        // $time_keep_holiday 
                                        // break_in_min_h           = $get_hoursworkeds->duration_of_lunch_break_per_day;
                                        // $start_time_sched_h      = $gDate." ".$reg_sched->work_start_time;
                                        // $hours_worked_h          = $get_hoursworkeds->total_hours_for_the_day - ($break_in_min_h/60);
                                        // $hours_worked_half       = ($hours_worked_h/2 + ($break_in_min_h/60)) * 60;
                                        // if($lunch_out != "" && $lunch_in != ""){

                                        //$check_if_worksched_holiday       = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);
                                        //if($check_if_worksched_holiday){
                                        //  $time_keep_holiday          = ($check_if_worksched_holiday->enable_breaks_on_holiday == 'yes') ? true : false;
                                        //}

                                        if($time_keep_holiday){
                                            $break_in_min_h             = 0;
                                            $start_time_sched_h         = "";
                                            $hours_worked_h             = 0;
                                            $hours_worked_half          = 0;

                                            if($get_hoursworkeds){
                                                $break_in_min_h         = $get_hoursworkeds->duration_of_lunch_break_per_day;
                                                $start_time_sched_h     = $new_time_in;
                                                $hours_worked_h         = $get_hoursworkeds->total_hours_for_the_day - ($break_in_min_h/60);
                                                $hours_worked_half      = ($hours_worked_h/2) * 60;
                                            }

                                            if($break_in_min_h > 0){
                                                if(($new_lunch_out != "" || $new_lunch_out != NULL) && ($new_lunch_in != "" || $new_lunch_in != NULL)){
                                                    $total_break_emp    = (strtotime($new_lunch_in) - strtotime($new_lunch_out))/ 60;
                                                    $break_in_min_h     = ($break_in_min_h < $total_break_emp) ? $total_break_emp : $break_in_min_h;
                                                    $lunch_out_h_d      = date("Y-m-d",strtotime($new_lunch_out));
                                                    $lunch_in_h_d       = date("Y-m-d",strtotime($new_lunch_in));

                                                    if($lunch_out_h_d == $currentdate && $lunch_in_h_d == $currentdate){
                                                        $tq_update_field['current_date_holiday']    = $break_in_min_h;
                                                        $tq_update_field['next_date_holiday']       = 0;
                                                    }
                                                    else if($lunch_out_h_d == $currentdate && $lunch_in_h_d != $currentdate){
                                                        $gDate_n_h          = date("Y-m-d",strtotime($currentdate." + 1day"));
                                                        $gDate_c_h          = date("Y-m-d 23:59:59",strtotime($currentdate));
                                                        $gDate_n_h          = date("Y-m-d H:i:00",strtotime($gDate_n_h));
                                                        $break_in_min_h_b_c = (strtotime($gDate_c_h) - strtotime($new_lunch_out))/ 60;
                                                        $break_in_min_h_b_n = (strtotime($new_lunch_in) - strtotime($gDate_n_h))/ 60;

                                                        $tq_update_field['current_date_holiday']    = $break_in_min_h_b_c;
                                                        $tq_update_field['next_date_holiday']       = $break_in_min_h_b_n;
                                                    }
                                                    else{
                                                        $tq_update_field['current_date_holiday']    = 0;
                                                        $tq_update_field['next_date_holiday']       = $break_in_min_h;
                                                    }
                                                }
                                                else{

                                                    $lunch_out_a        = date("Y-m-d H:i:00",strtotime($start_time_sched_h." +" .$hours_worked_half." minutes")); 
                                                    $lunch_in_a         = date("Y-m-d H:i:00",strtotime($lunch_out_a." +" .$break_in_min_h." minutes")); 
                                                    
                                                    $lunch_out_h_d      = date("Y-m-d",strtotime($lunch_out_a));
                                                    $lunch_in_h_d       = date("Y-m-d",strtotime($lunch_in_a));

                                                    if($lunch_out_h_d == $currentdate && $lunch_in_h_d == $currentdate){
                                                        $tq_update_field['current_date_holiday']    = $break_in_min_h;
                                                        $tq_update_field['next_date_holiday']       = 0;
                                                    }
                                                    else if($lunch_out_h_d == $currentdate && $lunch_in_h_d != $currentdate){
                                                        $gDate_n_h          = date("Y-m-d",strtotime($currentdate." + 1 day"));
                                                        $gDate_c_h          = date("Y-m-d 23:59:59",strtotime($currentdate));
                                                        $gDate_n_h          = date("Y-m-d H:i:00",strtotime($gDate_n_h));
                                                        $break_in_min_h_b_c = (strtotime($gDate_c_h) - strtotime($lunch_out_a))/ 60;
                                                        $break_in_min_h_b_n = (strtotime($lunch_in_a) - strtotime($gDate_n_h))/ 60;

                                                        $tq_update_field['current_date_holiday']    = $break_in_min_h_b_c;
                                                        $tq_update_field['next_date_holiday']       = $break_in_min_h_b_n;
                                                    }
                                                    else{
                                                        $tq_update_field['current_date_holiday']    = 0;
                                                        $tq_update_field['next_date_holiday']       = $break_in_min_h;
                                                    }
                                                }
                                            }else{
                                                $tq_update_field['current_date_holiday']    = 0;
                                                $tq_update_field['next_date_holiday']       = 0;
                                            }
                                        }

                                        // exclude pmax
                                        // total hours should not exceeds the required hours
                                        if($company_id == "316"){
                                            if($tq_update_field["total_hours_required"] > $tq_update_field["total_hours"]){
                                                //$tq_update_field["total_hours_required"] = $tq_update_field["total_hours"];
                                            }
                                        }

                                        $this->db->where($time_query_where);
                                        $this->db->update('employee_time_in',$tq_update_field);

                                        // athan helper
                                        if($currentdate){
                                            $date = $currentdate;
                                            payroll_cronjob_helper($type='timesheet',$currentdate,$emp_id,$company_id);
                                        }
                                    }
                                    else{

                                        if($source == "recalculated"){
                                            return false;
                                        }

                                        $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                                        $get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
                                        
                                        $date_insert = array(
                                                "source"                    => 'dashboard',
                                                "comp_id"                   => $company_id,
                                                "emp_id"                    => $emp_id,
                                                "date"                      => $currentdate,
                                                "time_in"                   => $new_time_in,
                                                "time_out"                  => $new_time_out,
                                                "undertime_min"             => 0,
                                                "tardiness_min"             => 0,
                                                "late_min"                  => 0,
                                                "overbreak_min"             => 0,
                                                "work_schedule_id"          => "-2",
                                                "total_hours"               => $get_total_hours,
                                                "total_hours_required"      => $get_total_hours,
                                                "flag_regular_or_excess"    => "excess",
                                        );
                                        if($assumed_breaks_flex_b){
                                            $date_insert['flag_insert_assume_break'] = '1';
                                        }else{
                                            $date_insert['flag_insert_assume_break'] = '0';
                                        }

                                        // this is for rd ra
                                        if($rd_ra == "yes"){
                                            $date_insert['rest_day_r_a']                    = "yes";
                                            
                                            // if need approval
                                            if($rest_day_settings == "yes"){
                                                $date_insert['flag_rd_include']             = "no";
                                            }
                                        }
                                        else{
                                            $date_insert['rest_day_r_a']                    = "no";

                                            // if need approval
                                            if($rest_day_settings == "yes"){
                                                $date_insert['flag_rd_include']             = "yes";
                                            }
                                        }

                                        // if holiday needs approval based on settings
                                        if($holiday_orig){
                                            if($holiday_settings_appr == "yes"){
                                                $date_insert['holiday_approve']             = "yes";
                                                $date_insert['flag_holiday_include']        = "no";
                                            }else{
                                                $date_insert['holiday_approve']             = "no";
                                                $date_insert['flag_holiday_include']        = "yes";
                                            }
                                        }else{
                                            $date_insert['holiday_approve']             = "no";
                                            $date_insert['flag_holiday_include']        = "yes";
                                        }

                                        $add_logs = $this->db->insert('employee_time_in', $date_insert);

                                        // athan helper
                                        if($currentdate){
                                            $date = $currentdate;
                                            payroll_cronjob_helper($type='timesheet',$currentdate,$emp_id,$company_id);
                                        }
                                    }
                                }else{

                                    if($source == "recalculated"){
                                        return false;
                                    }

                                    if($log_error){
                                        $date_insert    = array(
                                                        "source"                => $source,
                                                        "comp_id"               => $company_id,
                                                        "emp_id"                => $emp_id,
                                                        "date"                  => $currentdate_orig_date_from_input,
                                                        "time_in"               => $new_time_in,
                                                        "lunch_out"             => $new_lunch_out,
                                                        "lunch_in"              => $new_lunch_in,
                                                        "time_out"              => $new_time_out,
                                                        "tardiness_min"         => 0,
                                                        "undertime_min"         => 0,
                                                        "absent_min"            => 0,
                                                        "late_min"              => 0,
                                                        "overbreak_min"         => 0,
                                                        "total_hours"           => $hours_worked,
                                                        "total_hours_required"  => 0
                                                        );
                                    }
                                    else{
                                        $date_insert    = array(
                                                        "source"                => $source,
                                                        "comp_id"               => $company_id,
                                                        "emp_id"                => $emp_id,
                                                        "date"                  => $currentdate_orig_date_from_input,
                                                        "time_in"               => $new_time_in,
                                                        "lunch_out"             => $new_lunch_out,
                                                        "lunch_in"              => $new_lunch_in,
                                                        "time_out"              => $new_time_out,
                                                        "undertime_min"         => $update_undertime,
                                                        "tardiness_min"         => $update_tardiness,
                                                        "absent_min"            => 0,
                                                        "work_schedule_id"      => $work_schedule_id,
                                                        "late_min"              => $late_min,
                                                        "overbreak_min"         => $overbreak_min,
                                                        "total_hours"           => $get_hoursworked,
                                                        "total_hours_required"  => $update_total_hours,
                                                        "current_date_nsd"      => $current_date_nsd,
                                                        "next_date_nsd"         => $next_date_nsd,
                                                        "flag_on_leave"         => $ileave
                                                        );
                                        
                                        if($assumed_breaks_flex_b){
                                            $date_insert['flag_insert_assume_break'] = '1';
                                        }
                                        else{
                                            $date_insert['flag_insert_assume_break'] = '0';
                                        }

                                        // ***** NEW COMPUTATION FOR ATTENDANCE SETTINGS
                                        
                                        if($attendance_hours){
                                            if($update_total_hours <= $attendance_hours){
                                                if($time_in >= $lunch_out){
                                                    $date_insert['lunch_out']   = null;
                                                    $date_insert['lunch_in']    = null;
                                                }
                                                elseif($time_out <= $lunch_in){
                                                    $date_insert['lunch_out']   = null;
                                                    $date_insert['lunch_in']    = null;
                                                }
                                                    
                                                $half_day_h = ($hours_worked / 2) * 60;
                                                if($update_undertime > $late_min){
                                                    $date_insert['late_min']        = $late_min;
                                                    $date_insert['tardiness_min']   = $update_tardiness;
                                                    $date_insert['undertime_min']   = 0;
                                                    $date_insert['absent_min']      = (($hours_worked - $update_total_hours) * 60) - $update_tardiness;
                                                }
                                                else{
                                                    $date_insert['late_min']            = 0;
                                                    $tq_update_field['tardiness_min']   = 0;
                                                    $date_insert['undertime_min']       = $update_undertime;
                                                    $date_insert['absent_min']          = (($hours_worked - $update_total_hours) * 60) - $update_undertime;
                                                }

                                                $date_insert['total_hours_required']    = $update_total_hours;
                                            }
                                        }
                                        
                                    }
                                    // this is for rd ra
                                    if($rd_ra == "yes"){
                                        $date_insert['rest_day_r_a']                    = "yes";

                                        // if need approval
                                        if($rest_day_settings == "yes"){
                                            $date_insert['flag_rd_include']             = "no";
                                        }
                                    }
                                    else{
                                        $date_insert['rest_day_r_a']                    = "no";

                                        // if need approval
                                        if($rest_day_settings == "yes"){
                                            $date_insert['flag_rd_include']             = "yes";
                                        }
                                    }

                                    // if holiday needs approval based on settings
                                    if($holiday_orig){
                                        if($holiday_settings_appr == "yes"){
                                            $date_insert['holiday_approve']             = "yes";
                                            $date_insert['flag_holiday_include']        = "no";
                                        }else{
                                            $date_insert['holiday_approve']             = "no";
                                            $date_insert['flag_holiday_include']        = "yes";
                                        }
                                    }else{
                                        $date_insert['holiday_approve']             = "no";
                                        $date_insert['flag_holiday_include']        = "yes";
                                    }

                                    // insert if lunch deducted on holiday
                                    // $time_keep_holiday 
                                    // break_in_min_h           = $get_hoursworkeds->duration_of_lunch_break_per_day;
                                    // $start_time_sched_h      = $gDate." ".$reg_sched->work_start_time;
                                    // $hours_worked_h          = $get_hoursworkeds->total_hours_for_the_day - ($break_in_min_h/60);
                                    // $hours_worked_half       = ($hours_worked_h/2 + ($break_in_min_h/60)) * 60;
                                    // if($lunch_out != "" && $lunch_in != ""){

                                    //$check_if_worksched_holiday       = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);
                                    //if($check_if_worksched_holiday){
                                    //  $time_keep_holiday          = ($check_if_worksched_holiday->enable_breaks_on_holiday == 'yes') ? true : false;
                                    //}

                                    if($time_keep_holiday){
                                        $break_in_min_h             = 0;
                                        $start_time_sched_h         = "";
                                        $hours_worked_h             = 0;
                                        $hours_worked_half          = 0;

                                        if($get_hoursworkeds){
                                            $break_in_min_h         = $get_hoursworkeds->duration_of_lunch_break_per_day;
                                            $start_time_sched_h     = $new_time_in;
                                            $hours_worked_h         = $get_hoursworkeds->total_hours_for_the_day - ($break_in_min_h/60);
                                            $hours_worked_half      = ($hours_worked_h/2) * 60;
                                        }

                                        if($break_in_min_h > 0){
                                            if(($new_lunch_out != "" || $new_lunch_out != NULL) && ($new_lunch_in != "" || $new_lunch_in != NULL)){
                                                $total_break_emp    = (strtotime($new_lunch_in) - strtotime($new_lunch_out))/ 60;
                                                $break_in_min_h     = ($break_in_min_h < $total_break_emp) ? $total_break_emp : $break_in_min_h;
                                                $lunch_out_h_d      = date("Y-m-d",strtotime($new_lunch_out));
                                                $lunch_in_h_d       = date("Y-m-d",strtotime($new_lunch_in));

                                                if($lunch_out_h_d == $currentdate && $lunch_in_h_d == $currentdate){
                                                    $date_insert['current_date_holiday'] = $break_in_min_h;
                                                }
                                                else if($lunch_out_h_d == $currentdate && $lunch_in_h_d != $currentdate){
                                                    $gDate_n_h          = date("Y-m-d",strtotime($currentdate." + 1day"));
                                                    $gDate_c_h          = date("Y-m-d 23:59:59",strtotime($currentdate));
                                                    $gDate_n_h          = date("Y-m-d H:i:00",strtotime($gDate_n_h));
                                                    $break_in_min_h_b_c = (strtotime($gDate_c_h) - strtotime($new_lunch_out))/ 60;
                                                    $break_in_min_h_b_n = (strtotime($new_lunch_in) - strtotime($gDate_n_h))/ 60;

                                                    $date_insert['current_date_holiday'] = $break_in_min_h_b_c;
                                                    $date_insert['next_date_holiday'] = $break_in_min_h_b_n;
                                                }
                                                else{
                                                    $date_insert['next_date_holiday'] = $break_in_min_h;
                                                }
                                            }
                                            else{

                                                $lunch_out_a        = date("Y-m-d H:i:00",strtotime($start_time_sched_h." -" .$hours_worked_half." minutes")); 
                                                $lunch_in_a         = date("Y-m-d H:i:00",strtotime($lunch_out_a." +" .$break_in_min_h." minutes")); 
                                                
                                                $lunch_out_h_d      = date("Y-m-d",strtotime($lunch_out_a));
                                                $lunch_in_h_d       = date("Y-m-d",strtotime($lunch_in_a));

                                                if($lunch_out_h_d == $currentdate && $lunch_in_h_d == $currentdate){
                                                    $date_insert['current_date_holiday'] = $break_in_min_h;
                                                }
                                                else if($lunch_out_h_d == $currentdate && $lunch_in_h_d != $currentdate){
                                                    $gDate_n_h          = date("Y-m-d",strtotime($currentdate." + 1 day"));
                                                    $gDate_c_h          = date("Y-m-d 23:59:59",strtotime($currentdate));
                                                    $gDate_n_h          = date("Y-m-d H:i:00",strtotime($gDate_n_h));
                                                    $break_in_min_h_b_c = (strtotime($gDate_c_h) - strtotime($lunch_out_a))/ 60;
                                                    $break_in_min_h_b_n = (strtotime($lunch_in_a) - strtotime($gDate_n_h))/ 60;

                                                    $date_insert['current_date_holiday'] = $break_in_min_h_b_c;
                                                    $date_insert['next_date_holiday'] = $break_in_min_h_b_n;
                                                }
                                                else{
                                                    $date_insert['next_date_holiday'] = $break_in_min_h;
                                                }
                                            }
                                        }else{
                                            $date_insert['current_date_holiday']    = 0;
                                            $date_insert['next_date_holiday']       = 0;
                                        }
                                    }

                                    // exclude pmax
                                    // total hours should not exceeds the required hours
                                    if($company_id == "316"){
                                        if($date_insert["total_hours_required"] > $date_insert["total_hours"]){
                                            //$date_insert["total_hours_required"] = $date_insert["total_hours"];
                                        }
                                    }

                                    $add_logs = $this->db->insert('employee_time_in', $date_insert);

                                    // athan helper
                                    if($currentdate_orig_date_from_input){
                                        payroll_cronjob_helper($type='timesheet',$currentdate_orig_date_from_input,$emp_id,$company_id);
                                    }
                                }
                            }
                        }
                        else{

                            # with latest timein
                            $number_of_breaks_per_day           = $number_of_breaks_per_day;
                            
                            $workday_settings_start_time        = date("H:i:s",strtotime($check_latest_timein_allowed));
                            $g                                  = ($get_hoursworked * 60);
                            $workday_settings_end_time          = date("H:i:s",strtotime($workday_settings_start_time." +{$g} minute"));
                            $workday_settings_start_time2       = date("Y-m-d H:i:s",strtotime($currentdate." ".$check_latest_timein_allowed));

                            if($new_time_in < $workday_settings_start_time2){
                                $workday_settings_start_time2   = $new_time_in;
                            }
                            
                            $workday_settings_end_time2 = date("Y-m-d H:i:s",strtotime($workday_settings_start_time2." +{$g} minute"));
                            
                            if($number_of_breaks_per_day == 0){
                                // update total hours and total hours required rest day
                                $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                                
                                if($new_time_in !="" && $new_lunch_out=="" && $new_lunch_in=="" && $new_time_out!=""){
                                    $log_error = false;
                                }
                                // update tardiness for timein
                                $tardiness_timein = 0;

                                // TARDINESS SETTINGS
                                $tardiness_set      = false;//$this->tardiness_settings($emp_id, $company_id,$get_employee_payroll_information,$get_tardiness_settings);

                                if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
                                    if(date("A",strtotime($new_time_in)) == "AM"){

                                        // add one day for time in log
                                        $new_start_timein   = date("Y-m-d",strtotime($new_time_in." -1 day"));
                                        $new_start_timein   = $new_start_timein." ".$workday_settings_start_time;
                                        
                                        if($tardiness_set){

                                            $new_start_timein = date('Y-m-d H:i:s',strtotime($new_start_timein." +{$tardiness_set} minutes"));
                                        }
                                        
                                        if(strtotime($new_start_timein) < strtotime($new_time_in)){

                                            $tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;          
                                        }
                                    }
                                }
                                else{

                                    $new_start_timein   = $currentdate." ".$workday_settings_start_time;
                                        
                                    if($tardiness_set){

                                        $new_start_timein = date('Y-m-d H:i:s',strtotime($new_start_timein." +{$tardiness_set} minutes"));
                                    }
                                    
                                    if($new_time_in > $new_start_timein ){

                                        $tardiness_timein = $this->total_hours_worked($new_time_in,$new_start_timein);
                                    }
                                }

                                // update total tardiness
                                $update_tardiness = $late_min + $overbreak_min;//$tardiness_timein;

                                // update undertime
                                $update_undertime = 0;
                                if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){

                                    $hours_worked               = $get_hoursworked;
                                    $workday_settings_end_time  = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
                                }
                            
                                if( $new_time_out < $workday_settings_end_time2){

                                    $update_undertime = $this->total_hours_worked($workday_settings_end_time2, $new_time_out);
                                }
                                
                                // check tardiness value
                                $flag_tu                = 0;
                                $hours_worked           = $get_hoursworked;
                                $get_total_hours_worked = ($hours_worked / 2) + .5;
                                
                                // required hours worked only
                                $new_total_hours = $this->total_hours_worked($new_time_out,$new_time_in);

                                // if value is less than 0 then set value to 0
                                if($update_tardiness < 0)   $update_tardiness   = 0;
                                if($update_undertime < 0)   $update_undertime   = 0;
                                if($new_total_hours < 0)    $new_total_hours    = 0;
                                if($get_total_hours < 0)    $get_total_hours    = 0;                                        
                                
                                if($time_query_row){
                                    if($source == "updated" || $source == "recalculated" || $source == "import"){

                                        if($time_query_row->flag_regular_or_excess == "excess"){
                                            $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                                            $get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
                                            $tq_update_field = array(
                                                    "comp_id"                   => $company_id,
                                                    "emp_id"                    => $emp_id,
                                                    "date"                      => $currentdate,
                                                    "time_in"                   => $new_time_in,
                                                    "time_out"                  => $new_time_out,
                                                    "undertime_min"             => 0,
                                                    "tardiness_min"             => 0,
                                                    "late_min"                  => 0,
                                                    "overbreak_min"             => 0,
                                                    "absent_min"                => 0,
                                                    "work_schedule_id"          => "-2",
                                                    "total_hours"               => $get_total_hours,
                                                    "total_hours_required"      => $get_total_hours,
                                                    "flag_regular_or_excess"    => "excess",
                                            );

                                            // added for coaching
                                            if($inclusion_hours == "1"){
                                                $tq_update_field["inclusion_hours"]     = $inclusion_hours;
                                            }

                                            // if source is from cronjob dont change last source
                                            if(!$cronjob_time_recalculate){
                                                if($time_query_row->source!=""){
                                                    $tq_update_field['last_source'] = $source;
                                                    $tq_update_field['change_log_date_filed'] = date('Y-m-d H:i:s');
                                                }
                                                else{
                                                    $tq_update_field['last_source'] = 'dashboard';
                                                }
                                            }

                                        }else{
                                            if($log_error){
                                                $tq_update_field    = array(
                                                                    "time_in"               => $new_time_in,
                                                                    "time_out"              => $new_time_out,
                                                                    "undertime_min"         => 0,
                                                                    "tardiness_min"         => 0,
                                                                    "work_schedule_id"      => $work_schedule_id,
                                                                    "late_min"              => 0,
                                                                    "overbreak_min"         => 0,
                                                                    "total_hours"           => 0,
                                                                    "total_hours_required"  => 0
                                                                    );
                                            }else{
                                                $tq_update_field = array(
                                                    "time_in"               => $new_time_in,
                                                    "time_out"              => $new_time_out,
                                                    "lunch_in"              => null,
                                                    "lunch_out"             => null,
                                                    "undertime_min"         => $update_undertime,
                                                    "tardiness_min"         => $update_tardiness,
                                                    "work_schedule_id"      => $work_schedule_id,
                                                    "late_min"              => $late_min,
                                                    "overbreak_min"         => $overbreak_min,                                  
                                                    "total_hours"           => $get_hoursworked,
                                                    "total_hours_required"  => $get_total_hours,
                                                    "flag_on_leave"         => $ileave
                                                );

                                                // added for coaching
                                                if($inclusion_hours == "1"){
                                                    $tq_update_field["inclusion_hours"]     = $inclusion_hours;
                                                }
                                            }
                                            
                                            // if source is from cronjob dont change last source
                                            if(!$cronjob_time_recalculate){
                                                if($time_query_row->source!=""){
                                                    $tq_update_field['last_source'] = $source;
                                                    $tq_update_field['change_log_date_filed'] = date('Y-m-d H:i:s');
                                                }
                                            }
                                            
                                            //attendance settings
                                            
                                            $att = $this->calculate_attendance($company_id,$new_time_in,$new_time_out,$attendance_hours);
                                            
                                            if($att){
                                                $total_hours_worked                         = $this->total_hours_worked($new_time_out, $new_time_in);
                                                $total_hours_worked                         = $this->convert_to_hours($total_hours_worked);
                                                $tq_update_field['lunch_in']                = null;
                                                $tq_update_field['lunch_out']               = null;
                                                $tq_update_field['total_hours_required']    = $total_hours_worked;
                                                $tq_update_field['absent_min']              = ($get_hoursworked - $total_hours_worked) * 60;
                                                $tq_update_field['late_min']                = 0;
                                                $tq_update_field['tardiness_min']           = 0;
                                                $tq_update_field['undertime_min']           = 0;
                                            }
                                        }
                                        
                                        // this is for rd ra
                                        if($rd_ra == "yes"){
                                            $tq_update_field['rest_day_r_a']                = "yes";

                                            // if need approval
                                            if($rest_day_settings == "yes"){
                                                $tq_update_field['flag_rd_include']         = "no";
                                            }
                                        }
                                        else{
                                            $tq_update_field['rest_day_r_a']                = "no";

                                            // if need approval
                                            if($rest_day_settings == "yes"){
                                                $tq_update_field['flag_rd_include']         = "yes";
                                            }
                                        }

                                        // if holiday needs approval based on settings
                                        if($holiday_orig){
                                            if($holiday_settings_appr == "yes"){
                                                $tq_update_field['holiday_approve']         = "yes";
                                                $tq_update_field['flag_holiday_include']    = "no";
                                            }else{
                                                $tq_update_field['holiday_approve']         = "no";
                                                $tq_update_field['flag_holiday_include']    = "yes";
                                            }
                                        }else{
                                            $tq_update_field['holiday_approve']             = "no";
                                            $tq_update_field['flag_holiday_include']        = "yes";
                                        }

                                        // exclude pmax
                                        // total hours should not exceeds the required hours
                                        if($company_id == "316"){
                                            if($tq_update_field["total_hours_required"] > $tq_update_field["total_hours"]){
                                                //$tq_update_field["total_hours_required"] = $tq_update_field["total_hours"];
                                            }
                                        }

                                        $this->db->where($time_query_where);
                                        $this->db->update('employee_time_in',$tq_update_field);

                                        // athan helper
                                        if($currentdate){
                                            
                                            $date = $currentdate;
                                            payroll_cronjob_helper($type='timesheet',$date,$emp_id,$company_id);
                                        }
                                        
                                    }
                                    else{

                                        if($source == "recalculated"){
                                            return false;
                                        }

                                        $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                                        $get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
                                        
                                        $date_insert = array(
                                            "source"                    => 'dashboard',
                                            "comp_id"                   => $company_id,
                                            "emp_id"                    => $emp_id,
                                            "date"                      => $currentdate,
                                            "time_in"                   => $new_time_in,
                                            "time_out"                  => $new_time_out,
                                            "undertime_min"             => 0,
                                            "tardiness_min"             => 0,
                                            "late_min"                  => 0,
                                            "overbreak_min"             => 0,
                                            "work_schedule_id"          => "-2",
                                            "total_hours"               => $get_total_hours,
                                            "total_hours_required"      => $get_total_hours,
                                            "flag_regular_or_excess"    => "excess",
                                        );

                                        // this is for rd ra
                                        if($rd_ra == "yes"){
                                            $date_insert['rest_day_r_a']                = "yes";
                                            
                                            // if need approval
                                            if($rest_day_settings == "yes"){
                                                $date_insert['flag_rd_include']         = "no";
                                            }
                                        }
                                        else{
                                            $date_insert['rest_day_r_a']                = "no";

                                            // if need approval
                                            if($rest_day_settings == "yes"){
                                                $date_insert['flag_rd_include']         = "yes";
                                            }
                                        }

                                        // if holiday needs approval based on settings
                                        if($holiday_orig){
                                            if($holiday_settings_appr == "yes"){
                                                $date_insert['holiday_approve']         = "yes";
                                                $date_insert['flag_holiday_include']    = "no";
                                            }else{
                                                $date_insert['holiday_approve']         = "no";
                                                $date_insert['flag_holiday_include']    = "yes";
                                            }
                                        }else{
                                            $date_insert['holiday_approve']             = "no";
                                            $date_insert['flag_holiday_include']        = "yes";
                                        }

                                        $add_logs = $this->db->insert('employee_time_in', $date_insert);
                                    }
                                }
                                else{

                                    if($source == "recalculated"){
                                        return false;
                                    }

                                    if($log_error){
                                        $date_insert    = array(
                                                        "source"                => $source,
                                                        "comp_id"               => $company_id,
                                                        "emp_id"                => $emp_id,
                                                        "date"                  => $currentdate,
                                                        "time_in"               => $new_time_in,
                                                        "time_out"              => $new_time_out,
                                                        "tardiness_min"         => 0,
                                                        "undertime_min"         => 0,
                                                        "late_min"              => 0,
                                                        "overbreak_min"         => 0,
                                                        "total_hours"           => 0,
                                                        "total_hours_required"  => 0
                                                        );
                                    }
                                    else{
                                        $date_insert    = array(
                                                        "source"                => $source,
                                                        "comp_id"               => $company_id,
                                                        "emp_id"                => $emp_id,
                                                        "date"                  => $currentdate,
                                                        "time_in"               => $new_time_in,
                                                        "time_out"              => $new_time_out,
                                                        "undertime_min"         => $update_undertime,
                                                        "tardiness_min"         => $update_tardiness,
                                                        "late_min"              => $late_min,
                                                        "overbreak_min"         => $overbreak_min,
                                                        "work_schedule_id"      => $work_schedule_id,
                                                        "total_hours"           => $get_hoursworked,
                                                        "total_hours_required"  => $get_total_hours,
                                                        "flag_on_leave"         => $ileave
                                                        );
                                        
                                        //attendance settings
                                        $att = $this->calculate_attendance($company_id,$new_time_in,$new_time_out,$attendance_hours);
                                        
                                        if($att){
                                            $total_hours_worked                     = $this->total_hours_worked($new_time_out, $new_time_in);
                                            $total_hours_worked                     = $this->convert_to_hours($total_hours_worked);
                                            $date_insert['lunch_in']                = null;
                                            $date_insert['lunch_out']               = null;
                                            $date_insert['total_hours_required']    = $total_hours_worked;
                                            $date_insert['absent_min']              = ($get_hoursworked - $total_hours_worked) * 60;
                                            $date_insert['late_min']                = 0;
                                            $date_insert['tardiness_min']           = 0;
                                            $date_insert['undertime_min']           = 0;
                                        }
                                    }

                                    // athan helper
                                    if($currentdate){
                                        
                                        $date = $currentdate;
                                        payroll_cronjob_helper($type='timesheet',$date,$emp_id,$company_id);
                                    }

                                    // this is for rd ra
                                    if($rd_ra == "yes"){
                                        $date_insert['rest_day_r_a']                = "yes";
                                        
                                        // if need approval
                                        if($rest_day_settings == "yes"){
                                            $date_insert['flag_rd_include']         = "no";
                                        }
                                    }
                                    else{
                                        $date_insert['rest_day_r_a']                = "no";

                                        // if need approval
                                        if($rest_day_settings == "yes"){
                                            $date_insert['flag_rd_include']         = "yes";
                                        }
                                    }

                                    // if holiday needs approval based on settings
                                    if($holiday_orig){
                                        if($holiday_settings_appr == "yes"){
                                            $date_insert['holiday_approve']         = "yes";
                                            $date_insert['flag_holiday_include']    = "no";
                                        }else{
                                            $date_insert['holiday_approve']         = "no";
                                            $date_insert['flag_holiday_include']    = "yes";
                                        }
                                    }else{
                                            $date_insert['holiday_approve']         = "no";
                                            $date_insert['flag_holiday_include']    = "yes";
                                    }

                                    // exclude pmax
                                    // total hours should not exceeds the required hours
                                    if($company_id == "316"){
                                        if($date_insert["total_hours_required"] > $date_insert["total_hours"]){
                                            //$date_insert["total_hours_required"] = $date_insert["total_hours"];
                                        }
                                    }

                                    $add_logs = $this->db->insert('employee_time_in', $date_insert);
                                }
                                
                                // end no break;        
                            }else{
                                // update tardiness for timein
                                
                                $half_day = 0;

                                $end_time = date('Y-m-d H:i:s',strtotime($currentdate." ".$workday_settings_end_time));
                                
                                $tardiness_timein = 0;
                                
                                // TARDINESS SETTINGS
                                $tardiness_set      = false;//$this->tardiness_settings($emp_id, $company_id,$get_employee_payroll_information,$get_tardiness_settings);

                                if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
                                    if(date("A",strtotime($new_time_in)) == "AM"){
                                        
                                        // add one day for time in log
                                        $new_start_timein = date("Y-m-d",strtotime($new_time_in." -1 day"));
                                        $new_start_timein = $new_start_timein." ".$workday_settings_start_time;
                                        
                                        if($tardiness_set){
                                            $new_start_timein = date('Y-m-d H:i:s',strtotime($new_start_timein." +{$tardiness_set} minutes"));
                                        }
                                        if(strtotime($new_start_timein) < strtotime($new_time_in)){
                                            $tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;          
                                        }
                                    }else{
                                        
                                        $end_time = date('Y-m-d H:i:s',strtotime($currentdate." ".$workday_settings_end_time));
                                        $end_time = date('Y-m-d H:i:s',strtotime($end_time." +1 day"));
                                    }
                                }
                                    
                                $new_start_timein       = date("Y-m-d",strtotime($currentdate));
                                $new_start_timein       = $new_start_timein." ".$workday_settings_start_time;
                                $new_start_timein_orig  = $new_start_timein;
                                
                                if($tardiness_set){
                                    // grace period is here ---> create new latest timein ---> current latest + grace period as what we discuss
                                    $new_start_timein = date('Y-m-d H:i:s',strtotime($new_start_timein." +{$tardiness_set} minutes"));
                                }
                                
                                // compute tardiness here --> late
                                if($new_time_in > $new_start_timein ){
                                    $tardiness_timein = $this->total_hours_worked($new_time_in, $new_start_timein);     
                                    $late_min         = $tardiness_timein;
                                }
                                
                                // update tardiness for break time
                                $update_tardiness_break_time        = 0;
                                // get break time
                                $duration_of_lunch_break_per_day    = $number_of_breaks_per_day;
                                // compute total breaktime
                                $tardiness_a                        = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
                                
                                // if assume break
                                if($assumed_breaks_flex_b){
                                    $tardiness_a = $duration_of_lunch_break_per_day;
                                }
                                
                                // if break hour allowed is less than '<' total break time
                                if($duration_of_lunch_break_per_day < $tardiness_a){
                                    // compute for over break
                                    $update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
                                }
                                
                                // update total tardiness initial late '+' over break
                                $update_tardiness = $tardiness_timein + $update_tardiness_break_time;
                                
                                // update undertime
                                $update_undertime = 0;

                                if($new_time_out < $end_time){
                                    //echo $end_time . "xxxx".$new_time_out;
                                    $start_timez =  date('Y-m-d H:i:s',strtotime($currentdate. " ". $workday_settings_start_time));
                                    if($new_time_in < $start_timez){
                                        $end_time = date('Y-m-d H:i:s',strtotime($new_time_in. " +{$g} minute"));                                       
                                    }
                                    $update_undertime = $this->total_hours_worked($end_time, $new_time_out);
                                    
                                }   
                            
                                // update total hours
                                $hours_worked = $get_hoursworked;
                                
                                // convert break min to hour
                                $break = $duration_of_lunch_break_per_day / 60;
                                // update total hour --> total hours worked minus '-' tardiness, undertime and break
                                $update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - $break;
                                
                                // check tardiness value
                                $get_total_hours_worked = ($hours_worked / 2) + .5;
                                
                                // update total hours required
                                
                                $total_hours_worked1            = ($this->total_hours_worked($time_out, $time_in) - ($break * 60) ) - $update_tardiness_break_time;
                                $update_total_hours_required    = $this->convert_to_hours($total_hours_worked1);
                                
                                // if value is less than 0 then set value to 0
                                if($update_tardiness < 0) $update_tardiness = 0;
                                if($update_undertime < 0) $update_undertime = 0;
                                if($update_total_hours < 0) $update_total_hours = 0;
                                if($update_total_hours_required < 0) $update_total_hours_required = 0;
                                
                                /** --BARACK FLEX CORRECTION-- **/
                                
                                //check undertime --> overwrite current undertime
                                $get_hoursworked1z = $get_hoursworked - ($duration_of_lunch_break_per_day/60);
                                $update_total_hours_required1z = $update_total_hours_required + ($update_tardiness/60);
                                
                                // if required hours worked is less than hours worked plus '+' tardiness
                                if($get_hoursworked1z > $update_total_hours_required1z){
                                    $update_undertime = ($get_hoursworked1z - $update_total_hours_required1z) * 60;
                                }
                                // if flex sched break is assume disregard all ur prev computation 
                                if($flex_rules){
                                    $break_rules    = $flex_rules->break_rules;
                                    $assumed_breaks = $flex_rules->assumed_breaks;
                                    $assumed_breaks = $assumed_breaks * 60;
                                    
                                    //check grace period
                                    $grace_period = 0;
                                    if($tardiness_set){
                                        $grace_period = $tardiness_set/60;
                                    }
                                    
                                    if($break_rules == "assumed"){
                                        $new_start_timeinb      = strtotime($new_start_timein_orig);
                                        $new_time_inb           = strtotime($new_time_in);
                                        
                                        $predict_new_time_out   = strtotime($new_time_out);
                                        $number_of_breaks_b     = $number_of_breaks_per_day;
                                        
                                        if($new_time_inb <= $new_start_timeinb){    // ---> timein before latest time
                                            $predict_luch_outxb     = strtotime($new_time_in."+ ".$assumed_breaks." minutes");
                                        }
                                        else{                                       // ---> timein after latest time
                                            $predict_luch_outxb     = strtotime($new_start_timein_orig."+ ".$assumed_breaks." minutes");
                                        }
                                        $predict_luch_outxb1    = date("Y-m-d H:i:s",$predict_luch_outxb);
                                        $predict_luch_inxb      = strtotime($predict_luch_outxb1."+ ".$number_of_breaks_b." minutes");
                                        $predict_luch_inxb1     = date("Y-m-d H:i:s",$predict_luch_inxb);
                                        
                                        if($new_time_inb < $predict_luch_outxb){ // ---> assume timein before break

                                            $new_lunch_out  = $predict_luch_outxb1;
                                            $new_lunch_in   = $predict_luch_inxb1;

                                            if($predict_new_time_out <= $predict_luch_inxb){ // ---> assume timeout before break
                                                
                                                $new_lunch_out  = null;
                                                $new_lunch_in   = null;                                             
                                                if($predict_new_time_out <= $predict_luch_outxb){
                                                    $tox = $new_time_out;
                                                }else{
                                                    $tox = $predict_luch_outxb1;
                                                }
                                                
                                                $fromx                          = date('Y-m-d H:i',strtotime($new_time_in));
                                                $totalx                         = strtotime($tox) - strtotime($fromx);
                                                $hoursx                         = floor($totalx / 60 / 60);
                                                $minutesx                       = floor(($totalx - ($hoursx * 60 * 60)) / 60);
                                                $hours_worked_new1              = (($hoursx * 60) + $minutesx)/60;
                                                $update_total_hours_required    = $hours_worked_new1;
                                                
                                                // check tardiness --> if timein before latest timein tardiness should is zero
                                                if($new_time_inb <= $new_start_timeinb){
                                                    $late_min           = 0;
                                                    $update_tardiness   = 0;
                                                    $grace_period       = 0;
                                                }
                                                else{
                                                    $late_min           = $this->total_hours_worked($new_time_in, $new_start_timein);
                                                    $update_tardiness   = $late_min;
                                                }
                                                
                                                //check undertime
                                                $get_hoursworked1z              = $get_hoursworked - ($duration_of_lunch_break_per_day/60);
                                                $update_total_hours_required1z  = $update_total_hours_required + ($update_tardiness/60) + $grace_period;

                                                if($get_hoursworked1z > $update_total_hours_required1z){
                                                    $update_undertime = ($get_hoursworked1z - $update_total_hours_required1z) * 60;
                                                }else{
                                                    $update_undertime = 0;
                                                }
                                            }else{
                                                //check undertime
                                                $get_hoursworked1z              = $get_hoursworked - ($duration_of_lunch_break_per_day/60);
                                                $update_total_hours_required1z  = $update_total_hours_required + ($update_tardiness/60) + $grace_period;

                                                if($get_hoursworked1z > $update_total_hours_required1z){
                                                    $update_undertime = ($get_hoursworked1z - $update_total_hours_required1z) * 60;
                                                }else{
                                                    $update_undertime = 0;
                                                }

                                                $update_tardiness     = $late_min;
                                            }
                                        }else{                                  // ---> assume timein after or between break
                                            
                                            $new_lunch_out  = null;
                                            $new_lunch_in   = null;

                                            if($new_time_inb <= $predict_luch_inxb){ // ----> timein before break end

                                                $fromx                      = date('Y-m-d H:i',strtotime($predict_luch_inxb1));
                                                $late_min                   = $this->total_hours_worked($predict_luch_inxb1, $new_start_timein);    
                                            }
                                            else{                                   // ----> timein after break end

                                                $fromx                      = date('Y-m-d H:i',strtotime($new_time_in));
                                                $late_min                   = $this->total_hours_worked($new_time_in, $new_start_timein);
                                            }
                                            
                                            $tox                            = date('Y-m-d H:i',strtotime($new_time_out));
                                            $totalx                         = strtotime($tox) - strtotime($fromx);
                                            $hoursx                         = floor($totalx / 60 / 60);
                                            $minutesx                       = floor(($totalx - ($hoursx * 60 * 60)) / 60);
                                            $hours_worked_new1              = (($hoursx * 60) + $minutesx)/60;
                                            $update_total_hours_required    = $hours_worked_new1;

                                            // update tardiness halfday noon
                                            $late_min                       = $late_min - $duration_of_lunch_break_per_day;
                                            $update_tardiness               = $late_min;
                                            
                                            //check undertime
                                            $get_hoursworked1z              = $get_hoursworked - ($duration_of_lunch_break_per_day/60);
                                            $update_total_hours_required1z  = $update_total_hours_required + ($update_tardiness/60) + $grace_period;

                                            if($get_hoursworked1z > $update_total_hours_required1z){
                                                $update_undertime = ($get_hoursworked1z - $update_total_hours_required1z) * 60;
                                            }else{
                                                $update_undertime = 0;
                                            }
                                        }
                                    }else if($break_rules == "capture"){

                                        $shift_hour_worked              = $get_hoursworked - ($duration_of_lunch_break_per_day/60);
                                        $shift_half_worked              = $shift_hour_worked/2;
                                        $shift_half_worked_min          = $shift_half_worked * 60;
                                        $update_total_hours_required    = "";
                                        $update_tardiness               = 0;
                                        $late_min                       = 0;
                                        $overbreak_min                  = 0;
                                        $break_for_break                = 0;
                                        $update_undertime               = 0;
                                        
                                        $fromx                          = date('Y-m-d H:i',strtotime($time_in));
                                        $tox                            = date('Y-m-d H:i',strtotime($time_out));
                                        $totalx                         = strtotime($tox) - strtotime($fromx);
                                        $hoursx                         = floor($totalx / 60 / 60);
                                        $minutesx                       = floor(($totalx - ($hoursx * 60 * 60)) / 60);
                                        $update_total_hours_required    = (($hoursx * 60) + $minutesx)/60;
                                        
                                        // if timein before latest timeIn 
                                        if(strtotime($time_in) < strtotime($new_start_timein_orig)){
                                            $assumed_capture_lunch_out  = date("Y-m-d H:i:s",strtotime($time_in ."+ ".$shift_half_worked_min." minutes"));
                                            $assumed_capture_lunch_in   = date("Y-m-d H:i:s",strtotime($assumed_capture_lunch_out ."+ ".$duration_of_lunch_break_per_day." minutes"));
                                        }
                                        // if timein on or after latest timeIn
                                        else if (strtotime($time_in) >= strtotime($new_start_timein_orig)){
                                            $assumed_capture_lunch_out  = date("Y-m-d H:i:s",strtotime($new_start_timein_orig ."+ ".$shift_half_worked_min." minutes"));
                                            $assumed_capture_lunch_in   = date("Y-m-d H:i:s",strtotime($assumed_capture_lunch_out ."+ ".$duration_of_lunch_break_per_day." minutes"));

                                            if(strtotime($time_in) > strtotime($new_start_timein_orig)){
                                                $froml                  = date('Y-m-d H:i',strtotime($new_start_timein_orig));
                                                $tol                    = date('Y-m-d H:i',strtotime($time_in));
                                                $totall                 = strtotime($tol) - strtotime($froml);
                                                $hoursl                 = floor($totall / 60 / 60);
                                                $minutesl               = floor(($totall - ($hoursl * 60 * 60)) / 60);
                                                $late_min               = (($hoursl * 60) + $minutesl);
                                            }
                                        }
                                        
                                        if($lunch_in > $lunch_out){
                                            $break_for_break            = $duration_of_lunch_break_per_day;
                                            $fromo                      = date('Y-m-d H:i',strtotime($lunch_out));
                                            $too                        = date('Y-m-d H:i',strtotime($lunch_in));
                                            $totalo                     = strtotime($too) - strtotime($fromo);
                                            $hourso                     = floor($totalo / 60 / 60);
                                            $minuteso                   = floor(($totalo - ($hourso * 60 * 60)) / 60);
                                            $actual_break               = (($hourso * 60) + $minuteso);

                                            if($actual_break > $break_for_break){
                                                $overbreak_min          = $actual_break - $break_for_break;
                                            }
                                        }
                                        else{
                                            // if timeOut before assumed capture break
                                            if(strtotime($assumed_capture_lunch_out) >= strtotime($time_out)){
                                                $break_for_break        = 0;
                                            }
                                            // if timeOut between break
                                            else if(strtotime($assumed_capture_lunch_out) < strtotime($time_out) && strtotime($assumed_capture_lunch_in) >= strtotime($time_out)){
                                                $fromb                  = date('Y-m-d H:i',strtotime($time_out));
                                                $tob                    = date('Y-m-d H:i',strtotime($assumed_capture_lunch_in));
                                                $totalb                 = strtotime($tob) - strtotime($fromb);
                                                $hoursb                 = floor($totalb / 60 / 60);
                                                $minutesb               = floor(($totalb - ($hoursb * 60 * 60)) / 60);
                                                $minus_for_break        = (($hoursb * 60) + $minutesb);
                                                $break_for_break        = $duration_of_lunch_break_per_day;
                                            }
                                            // timein after assumed capture break
                                            else if(strtotime($assumed_capture_lunch_in) <= strtotime($time_out) && strtotime($assumed_capture_lunch_out) > strtotime($time_out)){
                                                $break_for_break        = 0;
                                            }
                                            // if timeOut after assumed capture break
                                            else{
                                                $break_for_break        = $duration_of_lunch_break_per_day;
                                            }
                                        }
                                        // total hours worked
                                        $update_total_hours_required    = $update_total_hours_required - (($break_for_break/60) + ($overbreak_min/60));
                                        // TARDINESS
                                        $update_tardiness               = $late_min + $overbreak_min;
                                        // UNDERTIME
                                        if(($shift_hour_worked * 60) > (($update_total_hours_required * 60) + $update_tardiness)){
                                            $update_undertime           = (($shift_hour_worked * 60) - (($update_total_hours_required * 60) + $update_tardiness));
                                        }
                                    }
                                }
                                /** -- END BARACK FLEX CORRECTION-- **/
                                
                                // *** CHECKER
                                /**
                                p($break_for_break);
                                p($new_start_timein_orig);
                                p($assumed_capture_lunch_out);
                                p($shift_half_worked);
                                p($shift_hour_worked);
                                p($update_total_hours_required);
                                p($update_undertime);
                                p($update_tardiness);
                                p($get_hoursworked - ($duration_of_lunch_break_per_day/60));
                                exit("here");
                                **/
                                // *** END CHECKER **/
                                
                                if($time_query_row) {
                                    if($source == "updated" || $source == "recalculated" || $source == "import"){
                                        if($time_query_row->flag_regular_or_excess == "excess"){

                                            $get_total_hours    = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                                            $get_total_hours    = ($get_total_hours < 0) ? 0 : $get_total_hours;
                                            $tq_update_field    = array(
                                                                "comp_id"                   => $company_id,
                                                                "emp_id"                    => $emp_id,
                                                                "date"                      => $currentdate,
                                                                "time_in"                   => $new_time_in,
                                                                "time_out"                  => $new_time_out,
                                                                "undertime_min"             => 0,
                                                                "tardiness_min"             => 0,
                                                                "late_min"                  => 0,
                                                                "overbreak_min"             => 0,
                                                                "absent_min"                => 0,
                                                                "work_schedule_id"          => "-2",
                                                                "total_hours"               => $get_total_hours,
                                                                "total_hours_required"      => $get_total_hours,
                                                                "flag_regular_or_excess"    => "excess",
                                                                );

                                            // added for coaching
                                            if($inclusion_hours == "1"){
                                                $tq_update_field["inclusion_hours"]     = $inclusion_hours;
                                            }

                                            // if source is from cronjob dont change last source
                                            if(!$cronjob_time_recalculate){
                                                if($time_query_row->source!=""){
                                                    $tq_update_field['last_source'] = $source;
                                                    $tq_update_field['change_log_date_filed'] = date('Y-m-d H:i:s');
                                                }
                                                else{
                                                    $tq_update_field['last_source'] = 'dashboard';
                                                }
                                            }
                                        }else{
                                            if($log_error){
                                                $tq_update_field    = array(
                                                                    "time_in"               => $new_time_in,
                                                                    "lunch_out"             => $new_lunch_out,
                                                                    "lunch_in"              => $new_lunch_in,
                                                                    "time_out"              => $new_time_out,
                                                                    "undertime_min"         => 0,
                                                                    "date"                  => $currentdate,
                                                                    "tardiness_min"         => 0,
                                                                    "late_min"              => 0,
                                                                    "overbreak_min"         => 0,
                                                                    "work_schedule_id"      => $work_schedule_id,
                                                                    "total_hours"           => 0,
                                                                    "total_hours_required"  => 0
                                                                    );
                                            }else{
                                                $tq_update_field    = array(
                                                                    "time_in"               => $new_time_in,
                                                                    "lunch_out"             => $new_lunch_out,
                                                                    "lunch_in"              => $new_lunch_in,
                                                                    "time_out"              => $new_time_out,
                                                                    "undertime_min"         => $update_undertime,
                                                                    "date"                  => $currentdate,
                                                                    "tardiness_min"         => $update_tardiness,
                                                                    "late_min"              => $late_min,
                                                                    "absent_min"            => "0",
                                                                    "overbreak_min"         => $overbreak_min,
                                                                    "work_schedule_id"      => $work_schedule_id,
                                                                    "total_hours"           => $get_hoursworked - ($duration_of_lunch_break_per_day/60),
                                                                    "total_hours_required"  => $update_total_hours_required,
                                                                    "flag_on_leave"         => $ileave
                                                                    );

                                                if($assumed_breaks_flex_b){
                                                    $tq_update_field['flag_insert_assume_break'] = '1';
                                                }
                                                else{
                                                    $tq_update_field['flag_insert_assume_break'] = '0';
                                                }

                                                // added for coaching
                                                if($inclusion_hours == "1"){
                                                    $tq_update_field["inclusion_hours"]     = $inclusion_hours;
                                                }
                                                // ***** NEW COMPUTATION FOR ATTENDANCE SETTINGS
                                                $att = $attendance_hours;
                                                if(false){
                                                    if($update_total_hours_required <= $att){
                                                        if($time_in >= $lunch_out){
                                                            $tq_update_field['lunch_out']       = null;
                                                            $tq_update_field['lunch_in']        = null;
                                                        }
                                                        elseif($time_out <= $lunch_in){
                                                            $tq_update_field['lunch_out']       = null;
                                                            $tq_update_field['lunch_in']        = null;
                                                        }
                                                        
                                                        $half_day_h = ($hours_worked / 2) * 60;
                                                        if($update_undertime > $late_min){
                                                            $tq_update_field['late_min']        = $late_min;
                                                            $tq_update_field['tardiness_min']   = $update_tardiness;
                                                            $tq_update_field['undertime_min']   = 0;
                                                            $tq_update_field['absent_min']      = ((($get_hoursworked - ($duration_of_lunch_break_per_day/60)) - $update_total_hours_required) * 60) - $update_tardiness;
                                                        }
                                                        else{
                                                            $tq_update_field['late_min']        = 0;
                                                            $tq_update_field['tardiness_min']   = 0;
                                                            $tq_update_field['undertime_min']   = $update_undertime;
                                                            $tq_update_field['absent_min']      = ((($get_hoursworked - ($duration_of_lunch_break_per_day/60)) - $update_total_hours_required) * 60) - $update_undertime;
                                                        }
                                                        $date_insert['total_hours_required']    = $update_total_hours_required;
                                                    }
                                                }
                                            }
                                            

                                            // if source is from cronjob dont change last source
                                            if(!$cronjob_time_recalculate){
                                                if($time_query_row->source!=""){
                                                    $tq_update_field['last_source'] = $source;
                                                    $tq_update_field['change_log_date_filed'] = date('Y-m-d H:i:s');
                                                }
                                            }
                                        }
                                        
                                        // this is for rd ra
                                        if($rd_ra == "yes"){
                                            $tq_update_field['rest_day_r_a']                = "yes";
                                            
                                            // if need approval
                                            if($rest_day_settings == "yes"){
                                                $tq_update_field['flag_rd_include']         = "no";
                                            }
                                        }
                                        else{
                                            $tq_update_field['rest_day_r_a']                = "no";

                                            // if need approval
                                            if($rest_day_settings == "yes"){
                                                $tq_update_field['flag_rd_include']         = "yes";
                                            }
                                        }

                                        // if holiday needs approval based on settings
                                        if($holiday_orig){
                                            if($holiday_settings_appr == "yes"){
                                                $tq_update_field['holiday_approve']             = "yes";
                                                $tq_update_field['flag_holiday_include']    = "no";
                                            }
                                            else{
                                                $tq_update_field['holiday_approve']             = "no";
                                                $tq_update_field['flag_holiday_include']        = "yes";
                                            }
                                        }else{
                                            $tq_update_field['holiday_approve']             = "no";
                                            $tq_update_field['flag_holiday_include']        = "yes";
                                        }

                                        // insert if lunch deducted on holiday
                                        // $time_keep_holiday 
                                        // break_in_min_h           = $get_hoursworkeds->duration_of_lunch_break_per_day;
                                        // $start_time_sched_h      = $gDate." ".$reg_sched->work_start_time;
                                        // $hours_worked_h          = $get_hoursworkeds->total_hours_for_the_day - ($break_in_min_h/60);
                                        // $hours_worked_half       = ($hours_worked_h/2 + ($break_in_min_h/60)) * 60;
                                        // if($lunch_out != "" && $lunch_in != ""){

                                        //$check_if_worksched_holiday       = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);
                                        //if($check_if_worksched_holiday){
                                        //  $time_keep_holiday          = ($check_if_worksched_holiday->enable_breaks_on_holiday == 'yes') ? true : false;
                                        //}

                                        if($time_keep_holiday){
                                            $break_in_min_h             = 0;
                                            $start_time_sched_h         = "";
                                            $hours_worked_h             = 0;
                                            $hours_worked_half          = 0;

                                            if($get_hoursworkeds){
                                                $break_in_min_h         = $get_hoursworkeds->duration_of_lunch_break_per_day;
                                                $check_latest_timein_h  = $get_hoursworkeds->latest_time_in_allowed;

                                                $start_time_sched_h     = date("Y-m-d H:i:s",strtotime($currentdate." ".$check_latest_timein_h));
                                                $start_time_sched_h     = ($new_time_in < $start_time_sched_h) ? $new_time_in : $start_time_sched_h;
                                                $hours_worked_h         = $get_hoursworkeds->total_hours_for_the_day - ($break_in_min_h/60);
                                                $hours_worked_half      = ($hours_worked_h/2) * 60;
                                            }

                                            if($break_in_min_h > 0){
                                                if(($new_lunch_out != "" || $new_lunch_out != NULL) && ($new_lunch_in != "" || $new_lunch_in != NULL)){
                                                    $total_break_emp    = (strtotime($new_lunch_in) - strtotime($new_lunch_out))/ 60;
                                                    $break_in_min_h     = ($break_in_min_h < $total_break_emp) ? $total_break_emp : $break_in_min_h;
                                                    $lunch_out_h_d      = date("Y-m-d",strtotime($new_lunch_out));
                                                    $lunch_in_h_d       = date("Y-m-d",strtotime($new_lunch_in));

                                                    if($lunch_out_h_d == $currentdate && $lunch_in_h_d == $currentdate){
                                                        $tq_update_field['current_date_holiday']    = $break_in_min_h;
                                                        $tq_update_field['next_date_holiday']       = 0;
                                                    }
                                                    else if($lunch_out_h_d == $currentdate && $lunch_in_h_d != $currentdate){
                                                        $gDate_n_h          = date("Y-m-d",strtotime($currentdate." + 1day"));
                                                        $gDate_c_h          = date("Y-m-d 23:59:59",strtotime($currentdate));
                                                        $gDate_n_h          = date("Y-m-d H:i:00",strtotime($gDate_n_h));
                                                        $break_in_min_h_b_c = (strtotime($gDate_c_h) - strtotime($new_lunch_out))/ 60;
                                                        $break_in_min_h_b_n = (strtotime($new_lunch_in) - strtotime($gDate_n_h))/ 60;

                                                        $tq_update_field['current_date_holiday']    = $break_in_min_h_b_c;
                                                        $tq_update_field['next_date_holiday']       = $break_in_min_h_b_n;
                                                    }
                                                    else{
                                                        $tq_update_field['current_date_holiday']    = 0;
                                                        $tq_update_field['next_date_holiday']       = $break_in_min_h;
                                                    }
                                                }
                                                else{

                                                    $lunch_out_a        = date("Y-m-d H:i:00",strtotime($start_time_sched_h." +" .$hours_worked_half." minutes")); 
                                                    $lunch_in_a         = date("Y-m-d H:i:00",strtotime($lunch_out_a." +" .$break_in_min_h." minutes")); 
                                                    
                                                    $lunch_out_h_d      = date("Y-m-d",strtotime($lunch_out_a));
                                                    $lunch_in_h_d       = date("Y-m-d",strtotime($lunch_in_a));

                                                    if($lunch_out_h_d == $currentdate && $lunch_in_h_d == $currentdate){
                                                        $tq_update_field['current_date_holiday']    = $break_in_min_h;
                                                        $tq_update_field['next_date_holiday']       = 0;
                                                    }
                                                    else if($lunch_out_h_d == $currentdate && $lunch_in_h_d != $currentdate){
                                                        $gDate_n_h          = date("Y-m-d",strtotime($currentdate." + 1 day"));
                                                        $gDate_c_h          = date("Y-m-d 23:59:59",strtotime($currentdate));
                                                        $gDate_n_h          = date("Y-m-d H:i:00",strtotime($gDate_n_h));
                                                        $break_in_min_h_b_c = (strtotime($gDate_c_h) - strtotime($lunch_out_a))/ 60;
                                                        $break_in_min_h_b_n = (strtotime($lunch_in_a) - strtotime($gDate_n_h))/ 60;

                                                        $tq_update_field['current_date_holiday']    = $break_in_min_h_b_c;
                                                        $tq_update_field['next_date_holiday']       = $break_in_min_h_b_n;
                                                    }
                                                    else{
                                                        $tq_update_field['current_date_holiday']    = 0;
                                                        $tq_update_field['next_date_holiday']       = $break_in_min_h;
                                                    }
                                                }
                                            }else{
                                                $tq_update_field['current_date_holiday']    = 0;
                                                $tq_update_field['next_date_holiday']       = 0;
                                            }
                                        }

                                        // exclude pmax
                                        // total hours should not exceeds the required hours


                                        $this->db->where($time_query_where);
                                        $this->db->update('employee_time_in',$tq_update_field);

                                        // athan helper
                                        if($currentdate){
                                            $date = $currentdate;
                                            payroll_cronjob_helper($type='timesheet',$date,$emp_id,$company_id);
                                        }

                                    }else{

                                        if($source == "recalculated"){
                                            return false;
                                        }

                                        $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                                        $get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
                                    
                                        $date_insert    = array(
                                                        "source"                    => 'dashboard',
                                                        "comp_id"                   => $company_id,
                                                        "emp_id"                    => $emp_id,
                                                        "date"                      => $currentdate,
                                                        "time_in"                   => $new_time_in,
                                                        "time_out"                  => $new_time_out,
                                                        "undertime_min"             => 0,
                                                        "tardiness_min"             => 0,
                                                        "late_min"                  => 0,
                                                        "overbreak_min"             => 0,
                                                        "work_schedule_id"          => "-2",
                                                        "total_hours"               => $get_total_hours,
                                                        "total_hours_required"      => $get_total_hours,
                                                        "flag_regular_or_excess"    => "excess",
                                                        );

                                        if($assumed_breaks_flex_b){
                                            $date_insert['flag_insert_assume_break'] = '1';
                                        }
                                        else{
                                            $date_insert['flag_insert_assume_break'] = '0';
                                        }

                                        // this is for rd ra
                                        if($rd_ra == "yes"){
                                            $date_insert['rest_day_r_a']                = "yes";
                                            
                                            // if need approval
                                            if($rest_day_settings == "yes"){
                                                $date_insert['flag_rd_include']         = "no";
                                            }
                                        }
                                        else{
                                            $date_insert['rest_day_r_a']                = "no";

                                            // if need approval
                                            if($rest_day_settings == "yes"){
                                                $date_insert['flag_rd_include']         = "yes";
                                            }
                                        }

                                        // if holiday needs approval based on settings
                                        if($holiday_orig){
                                            if($holiday_settings_appr == "yes"){
                                                $date_insert['holiday_approve']         = "yes";
                                                $date_insert['flag_holiday_include']    = "no";
                                            }else{
                                                $date_insert['holiday_approve']         = "no";
                                                $date_insert['flag_holiday_include']    = "yes";
                                            }
                                        }else{
                                            $date_insert['holiday_approve']             = "no";
                                            $date_insert['flag_holiday_include']        = "yes";
                                        }

                                        $add_logs = $this->db->insert('employee_time_in', $date_insert);

                                        // athan helper
                                        if($currentdate){
                                            $date = $currentdate;
                                            payroll_cronjob_helper($type='timesheet',$date,$emp_id,$company_id);
                                        }
                                    }
                                    
                                }else{

                                    if($source == "recalculated"){
                                        return false;
                                    }

                                    if($log_error){
                                        $date_insert    = array(
                                                        "source"                => $source,
                                                        "comp_id"               => $company_id,
                                                        "emp_id"                => $emp_id,
                                                        "date"                  => $currentdate,
                                                        "time_in"               => $new_time_in,
                                                        "lunch_out"             => $new_lunch_out,
                                                        "lunch_in"              => $new_lunch_in,
                                                        "time_out"              => $new_time_out,
                                                        "tardiness_min"         => 0,
                                                        "undertime_min"         => 0,
                                                        "late_min"              => 0,
                                                        "overbreak_min"         => 0,
                                                        "total_hours"           => 0,
                                                        "total_hours_required"  => 0
                                                        );
                                    }else{
                                        
                                        $date_insert    = array(
                                                        "source"                => $source,
                                                        "comp_id"               => $company_id,
                                                        "emp_id"                => $emp_id,
                                                        "date"                  => $currentdate,
                                                        "time_in"               => $new_time_in,
                                                        "lunch_out"             => $new_lunch_out,
                                                        "lunch_in"              => $new_lunch_in,
                                                        "time_out"              => $new_time_out,
                                                        "undertime_min"         => $update_undertime,
                                                        "tardiness_min"         => $update_tardiness,
                                                        "late_min"              => $late_min,
                                                        "absent_min"            => "0",
                                                        "overbreak_min"         => $overbreak_min,
                                                        "work_schedule_id"      => $work_schedule_id,
                                                        "total_hours"           => $get_hoursworked - ($duration_of_lunch_break_per_day/60),
                                                        "total_hours_required"  => $update_total_hours_required,
                                                        "flag_on_leave"         => $ileave
                                                        );
                                        
                                        if($assumed_breaks_flex_b){
                                            $date_insert['flag_insert_assume_break'] = '1';
                                        }
                                        else{
                                            $date_insert['flag_insert_assume_break'] = '0';
                                        }
                                        
                                        // ***** NEW COMPUTATION FOR ATTENDANCE SETTINGS
                                        $att = $attendance_hours;

                                        if($att){

                                            if($update_total_hours_required <= $att){

                                                if($time_in >= $lunch_out){
                                                    $date_insert['lunch_out']           = null;
                                                    $date_insert['lunch_in']            = null;
                                                }
                                                elseif($time_out <= $lunch_in){
                                                    $date_insert['lunch_out']           = null;
                                                    $date_insert['lunch_in']            = null;
                                                }
                                                
                                                $half_day_h = ($hours_worked / 2) * 60;
                                                if($update_undertime > $late_min){
                                                    $date_insert['late_min']            = $late_min;
                                                    $date_insert['tardiness_min']       = $update_tardiness;
                                                    $date_insert['undertime_min']       = 0;
                                                    $date_insert['absent_min']          = ((($get_hoursworked - ($duration_of_lunch_break_per_day/60)) - $update_total_hours_required) * 60) - $update_tardiness;
                                                }
                                                else{
                                                    $date_insert['late_min']            = 0;
                                                    $date_insert['tardiness_min']       = 0;
                                                    $date_insert['undertime_min']       = $update_undertime;
                                                    $date_insert['absent_min']          = ((($get_hoursworked - ($duration_of_lunch_break_per_day/60)) - $update_total_hours_required) * 60) - $update_undertime;
                                                }
                                                $date_insert['total_hours_required']    = $update_total_hours_required;
                                            }
                                        }
                                    }
                                    
                                    // athan helper
                                    if($currentdate){
                                        
                                        $date = $currentdate;
                                        payroll_cronjob_helper($type='timesheet',$date,$emp_id,$company_id);
                                    }
                                    
                                    // this is for rd ra
                                    if($rd_ra == "yes"){
                                        $date_insert['rest_day_r_a']                = "yes";
                                        
                                        // if need approval
                                        if($rest_day_settings == "yes"){
                                            $date_insert['flag_rd_include']         = "no";
                                        }
                                    }
                                    else{
                                        $date_insert['rest_day_r_a']                = "no";

                                        // if need approval
                                        if($rest_day_settings == "yes"){
                                            $date_insert['flag_rd_include']         = "yes";
                                        }
                                    }

                                    // if holiday needs approval based on settings
                                    if($holiday_orig){
                                        if($holiday_settings_appr == "yes"){
                                            $date_insert['holiday_approve']         = "yes";
                                            $date_insert['flag_holiday_include']    = "no";
                                        }else{
                                            $date_insert['holiday_approve']         = "no";
                                            $date_insert['flag_holiday_include']    = "yes";
                                        }
                                    }else{
                                        $date_insert['holiday_approve']             = "no";
                                        $date_insert['flag_holiday_include']        = "yes";
                                    }

                                    // insert if lunch deducted on holiday
                                        // $time_keep_holiday 
                                        // break_in_min_h           = $get_hoursworkeds->duration_of_lunch_break_per_day;
                                        // $start_time_sched_h      = $gDate." ".$reg_sched->work_start_time;
                                        // $hours_worked_h          = $get_hoursworkeds->total_hours_for_the_day - ($break_in_min_h/60);
                                        // $hours_worked_half       = ($hours_worked_h/2 + ($break_in_min_h/60)) * 60;
                                        // if($lunch_out != "" && $lunch_in != ""){

                                        //$check_if_worksched_holiday       = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);
                                        //if($check_if_worksched_holiday){
                                        //  $time_keep_holiday          = ($check_if_worksched_holiday->enable_breaks_on_holiday == 'yes') ? true : false;
                                        //}

                                        if($time_keep_holiday){
                                        $break_in_min_h             = 0;
                                        $start_time_sched_h         = "";
                                        $hours_worked_h             = 0;
                                        $hours_worked_half          = 0;

                                        if($get_hoursworkeds){
                                            $break_in_min_h         = $get_hoursworkeds->duration_of_lunch_break_per_day;
                                            $check_latest_timein_h  = $get_hoursworkeds->latest_time_in_allowed;

                                            $start_time_sched_h     = date("Y-m-d H:i:s",strtotime($currentdate." ".$check_latest_timein_h));
                                            $start_time_sched_h     = ($new_time_in < $start_time_sched_h) ? $new_time_in : $start_time_sched_h;
                                            $hours_worked_h         = $get_hoursworkeds->total_hours_for_the_day - ($break_in_min_h/60);
                                            $hours_worked_half      = ($hours_worked_h/2) * 60;
                                        }

                                        if($break_in_min_h > 0){
                                            if(($new_lunch_out != "" || $new_lunch_out != NULL) && ($new_lunch_in != "" || $new_lunch_in != NULL)){
                                                $total_break_emp    = (strtotime($new_lunch_in) - strtotime($new_lunch_out))/ 60;
                                                $break_in_min_h     = ($break_in_min_h < $total_break_emp) ? $total_break_emp : $break_in_min_h;
                                                $lunch_out_h_d      = date("Y-m-d",strtotime($new_lunch_out));
                                                $lunch_in_h_d       = date("Y-m-d",strtotime($new_lunch_in));

                                                if($lunch_out_h_d == $currentdate && $lunch_in_h_d == $currentdate){
                                                    $date_insert['current_date_holiday']    = $break_in_min_h;
                                                    $date_insert['next_date_holiday']       = 0;
                                                }
                                                else if($lunch_out_h_d == $currentdate && $lunch_in_h_d != $currentdate){
                                                    $gDate_n_h          = date("Y-m-d",strtotime($currentdate." + 1day"));
                                                    $gDate_c_h          = date("Y-m-d 23:59:59",strtotime($currentdate));
                                                    $gDate_n_h          = date("Y-m-d H:i:00",strtotime($gDate_n_h));
                                                    $break_in_min_h_b_c = (strtotime($gDate_c_h) - strtotime($new_lunch_out))/ 60;
                                                    $break_in_min_h_b_n = (strtotime($new_lunch_in) - strtotime($gDate_n_h))/ 60;

                                                    $date_insert['current_date_holiday']    = $break_in_min_h_b_c;
                                                    $date_insert['next_date_holiday']       = $break_in_min_h_b_n;
                                                }
                                                else{
                                                    $date_insert['current_date_holiday']    = 0;
                                                    $date_insert['next_date_holiday']       = $break_in_min_h;
                                                }
                                            }
                                            else{

                                                $lunch_out_a        = date("Y-m-d H:i:00",strtotime($start_time_sched_h." +" .$hours_worked_half." minutes")); 
                                                $lunch_in_a         = date("Y-m-d H:i:00",strtotime($lunch_out_a." +" .$break_in_min_h." minutes")); 
                                                
                                                $lunch_out_h_d      = date("Y-m-d",strtotime($lunch_out_a));
                                                $lunch_in_h_d       = date("Y-m-d",strtotime($lunch_in_a));

                                                if($lunch_out_h_d == $currentdate && $lunch_in_h_d == $currentdate){
                                                    $date_insert['current_date_holiday']    = $break_in_min_h;
                                                    $date_insert['next_date_holiday']       = 0;
                                                }
                                                else if($lunch_out_h_d == $currentdate && $lunch_in_h_d != $currentdate){
                                                    $gDate_n_h          = date("Y-m-d",strtotime($currentdate." + 1 day"));
                                                    $gDate_c_h          = date("Y-m-d 23:59:59",strtotime($currentdate));
                                                    $gDate_n_h          = date("Y-m-d H:i:00",strtotime($gDate_n_h));
                                                    $break_in_min_h_b_c = (strtotime($gDate_c_h) - strtotime($lunch_out_a))/ 60;
                                                    $break_in_min_h_b_n = (strtotime($lunch_in_a) - strtotime($gDate_n_h))/ 60;

                                                    $date_insert['current_date_holiday']    = $break_in_min_h_b_c;
                                                    $date_insert['next_date_holiday']       = $break_in_min_h_b_n;
                                                }
                                                else{
                                                    $date_insert['current_date_holiday']    = 0;
                                                    $date_insert['next_date_holiday']       = $break_in_min_h;
                                                }
                                            }
                                        }else{
                                            $date_insert['current_date_holiday']    = 0;
                                            $date_insert['next_date_holiday']       = 0;
                                        }
                                    }

                                    // exclude pmax
                                    // total hours should not exceeds the required hours
                                    if($company_id == "316"){
                                        if($date_insert["total_hours_required"] > $date_insert["total_hours"]){
                                            //$date_insert["total_hours_required"] = $date_insert["total_hours"];
                                        }
                                    }

                                    // flex add timesheet w/ latest
                                    $add_logs = $this->db->insert('employee_time_in', $date_insert);
                                }
                            }
                        }
                    }
                    else{
                        // this don't exist anymore 
                        // since workschedule won't be false anymore
                    }
                }
                #end flex schedule
            }
            else{
                
                
                $openshift_sched_flag           = false;

                $check_if_worksched_holiday     = in_array_custom("worksched_id_{$this->work_schedule_id}",$get_work_schedule);

                if($check_if_worksched_holiday){

                    $time_keep_holiday          = ($check_if_worksched_holiday->enable_breaks_on_holiday == 'yes') ? true : false;

                    // this is to check if workschedule is timekeep normally on holiday (capture tardiness, undertime, etc)
                    if($time_keep_holiday){
                        $holiday                = false;
                    }

                    // this is to check if workschedule is rd ra
                    if($check_if_worksched_holiday->enable_working_on_restday == "yes"){
                        $rd_ra                  = "yes";
                    }

                    if($check_if_worksched_holiday->workday_type == "Open Shift"){
                        $openshift_sched_flag   = true;
                    }

                }
                
                // OPEN SHIFT HERE
                if($openshift_sched_flag){
                    $this->open_shift($company_id, $emp_id, $new_time_in,$new_time_out, $this->work_schedule_id,$source,$gDate,$real_employee_time_in_id,$get_employee_payroll_information,$get_tardiness_settings,$company_holiday,$time_query_row,$time_query_where,$get_all_regular_schedule,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$attendance_hours,$get_work_schedule,$get_employee_leave_application,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_tardiness_settingsv2,$tardiness_rounding,$migrate_v3,$rd_ra,$holiday_orig,$holiday_settings_appr,$inclusion_hours,$rest_day_settings,$get_all_employee_period_typex,$get_all_payroll_calendarx);

                    return false;
                }

                /* Regular Schedule */
                if($check_rest_day){
                    $this->add_rest_day($time_query_row,$time_query_where,$log_error,$company_id,$emp_id,$currentdate,$new_time_in,$new_time_out,$source,$rest_day_settings,$holiday,$holiday_settings_appr);
                }
                else if($holiday){
                    $this->add_holiday_logs($time_query_row,$time_query_where,$log_error,$company_id,$emp_id,$currentdate,$new_time_in,$new_time_out,$this->work_schedule_id,$source,$holiday_settings_appr);
                }
                else{

                    //SPLIT AREA
                    // split already declare
                    
                    $day                                                                = date('l',strtotime($currentdate));
                    $split_break_time                                                   = false;
                    $reg_sched                                                          = "";
                    $check_break_time                                                   = true;
                    if(!$split){
                        // check break time
                        if($this->work_schedule_id != FALSE){
                            $reg_sched                                                  = in_array_custom("rsched_id_{$this->work_schedule_id}_{$day}",$get_all_regular_schedule);
                            if($reg_sched){
                                $check_break_time                                       = ($reg_sched->break_in_min > 0) ? $reg_sched->break_in_min : 0;
                                $hours_worked                                           = ($reg_sched->total_work_hours > 0) ? $reg_sched->total_work_hours: 0;
                            }
                        }
                    }
                    else{

                        if($new_lunch_in == "" && $new_lunch_out == ""){

                            $split_break_time                                           = true;
                        }
                    }

                    // reinit the migrate base on worksched type
                    $migrate_v3                                                         = $this->check_if_migrate($this->work_schedule_id,$get_work_schedule);

                    # no break in workschedule in regular schedule
                    if(!$check_break_time && !$migrate_v3 && !$split){ // ZERO VALUE FOR BREAK TIME

                        // updabte total hours and total hours required rest day
                        $get_total_hours                                                = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                        
                        if($this->work_schedule_id != FALSE){
                            /* EMPLOYEE WORK SCHEDULE */
                            // tardiness and undertime value
                            $update_tardiness                                           = $this->late_min($company_id,$currentdate,$emp_id,$this->work_schedule_id,$new_time_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);

                            /// compute regular undertime
                            /// if timeout is less than the out 
                            $update_undertime                                           = 0;
                            $update_undertime                                           = $this->reg_sched_calc_under($get_all_regular_schedule,$get_work_schedule,$this->work_schedule_id,$currentdate,$new_time_in,$new_time_out,$new_lunch_out);
                        }
                        
                        $late_min                                                       = $update_tardiness;
                        $tMinutes                                                       = $this->total_hours_worked($new_time_out, $new_time_in);
                        $tHours                                                         = $this->convert_to_hours($tMinutes);
                        // check tardiness value
                        $flag_tu                                                        = 0;
                        
                        /**
                         * absent min work after admin enable attendance settings
                         */
                        $att = $this->calculate_attendance($company_id,$new_time_in,$new_time_out,$attendance_hours);

                        #check early tardiness
                        
                        if($this->work_schedule_id != FALSE){
                            if($time_query_row) {
                                if($log_error){
                                    $tq_update_field                                    = array(
                                                                                        "time_in"               => $new_time_in,
                                                                                        "time_out"              => $new_time_out,
                                                                                        "tardiness_min"         => $update_tardiness,
                                                                                        "undertime_min"         => $update_undertime,
                                                                                        "total_hours"           => 0,
                                                                                        "work_schedule_id"      => 0,
                                                                                        "total_hours_required"  => 0,
                                                                                        "overbreak_min"         => 0,
                                                                                        "flag_halfday"          => 1,
                                                                                        "date"                  => $currentdate,
                                                                                        "late_min"              => 0
                                                                                        );
                                }else{
                                    $tq_update_field                                    = array(
                                                                                        "time_in"               => $new_time_in,
                                                                                        "time_out"              => $new_time_out,
                                                                                        "lunch_out"             => null,
                                                                                        "lunch_in"              => null,
                                                                                        "tardiness_min"         => $update_tardiness,
                                                                                        "undertime_min"         => $update_undertime,
                                                                                        "total_hours"           => $hours_worked,
                                                                                        "overbreak_min"         => 0,
                                                                                        "work_schedule_id"      => $work_schedule_id,
                                                                                        "total_hours_required"  => $tHours,
                                                                                        "flag_halfday"          =>1,
                                                                                        "date"                  => $currentdate,
                                                                                        "late_min"              => $late_min,
                                                                                        "flag_on_leave"         => $ileave
                                                                                        );

                                    // added for coaching
                                    if($inclusion_hours == "1"){
                                        $tq_update_field["inclusion_hours"]             = $inclusion_hours;
                                    }
                                }
                                
                                // if source is from cronjob dont change last source
                                if(!$cronjob_time_recalculate){
                                    if($time_query_row->source!=""){

                                        $tq_update_field['last_source']                     = $source;
                                        $tq_update_field['change_log_date_filed']           = date('Y-m-d H:i:s');
                                    }
                                }
                                // attendance settings
                                // absent min work after attendance
                                if($att){
                                    $total_hours_worked                                 = $tHours;
                                    $tq_update_field['lunch_in']                        = null;
                                    $tq_update_field['lunch_out']                       = null;
                                    $tq_update_field['total_hours_required']            = $total_hours_worked;
                                    $tq_update_field['absent_min']                      = ($hours_worked - $total_hours_worked) * 60;
                                    $tq_update_field['late_min']                        = 0;
                                    $tq_update_field['tardiness_min']                   = 0;
                                    $tq_update_field['undertime_min']                   = 0;
                                }
                                
                                // exclude pmax
                                // total hours should not exceeds the required hours
                                if($company_id == "316"){
                                    if($tq_update_field["total_hours_required"] > $tq_update_field["total_hours"]){
                                        $tq_update_field["total_hours_required"] = $tq_update_field["total_hours"];
                                    }
                                }

                                $this->db->where($time_query_where);
                                $this->db->update('employee_time_in',$tq_update_field);
                                // athan helper
                                if($currentdate){
                                    $date = $currentdate;
                                    payroll_cronjob_helper($type='timesheet',$date,$emp_id,$company_id);
                                }
                                
                            }else{
                            
                                if($log_error){
                                    $date_insert                                        = array(
                                                                                        "source"                    => $source,
                                                                                        "comp_id"                   => $company_id,
                                                                                        "emp_id"                    => $emp_id,
                                                                                        "date"                      => $currentdate,
                                                                                        "time_in"                   => $new_time_in,
                                                                                        "time_out"                  => $new_time_out,
                                                                                        "tardiness_min"             => 0,
                                                                                        "undertime_min"             => 0,
                                                                                        "work_schedule_id"          => 0,
                                                                                        "late_min"                  => 0,
                                                                                        "total_hours"               => 0,
                                                                                        "total_hours_required"      => 0,
                                                                                        "flag_tardiness_undertime"  => 0,
                                                                                        "flag_halfday"              => 1
                                                                                        );
                                }else{
                                    
                                    $date_insert                                        = array(
                                                                                        "source"                    => $source, 
                                                                                        "comp_id"                   => $company_id,
                                                                                        "emp_id"                    => $emp_id,
                                                                                        "date"                      => $currentdate,
                                                                                        "time_in"                   => $new_time_in,
                                                                                        "time_out"                  => $new_time_out,
                                                                                        "tardiness_min"             => $update_tardiness,
                                                                                        "undertime_min"             => $update_undertime,
                                                                                        "late_min"                  => $late_min,
                                                                                        "total_hours"               => $hours_worked,
                                                                                        "total_hours_required"      => $tHours,
                                                                                        "work_schedule_id"          => $work_schedule_id,
                                                                                        "flag_tardiness_undertime"  => $flag_tu,
                                                                                        "flag_halfday"              => 1,
                                                                                        "flag_on_leave"             => $ileave
                                                                                        );
                                    
                                    //attendance settings
                                    if($att){
                                        $total_hours_worked                             = $tHours;
                                        $date_insert['lunch_in']                        = null;
                                        $date_insert['lunch_out']                       = null;
                                        $date_insert['total_hours_required']            = $total_hours_worked;
                                        $date_insert['absent_min']                      = ($hours_worked - $total_hours_worked) * 60;
                                        $date_insert['late_min']                        = 0;
                                        $date_insert['tardiness_min']                   = 0;
                                        $date_insert['undertime_min']                   = 0;
                                    }
                                }

                                // exclude pmax
                                // total hours should not exceeds the required hours
                                if($company_id == "316"){
                                    if($date_insert["total_hours_required"] > $date_insert["total_hours"]){
                                        //$date_insert["total_hours_required"] = $date_insert["total_hours"];
                                    }
                                }

                                $add_logs = $this->db->insert('employee_time_in', $date_insert);
                                // athan helper
                                if($currentdate){
                                    $date = $currentdate;
                                    payroll_cronjob_helper($type='timesheet',$date,$emp_id,$company_id);
                                }
                            }
                        }
                    }
                    else{

                        $hours_worked = 0;

                        if($this->work_schedule_id != FALSE){
                            
                            $add_logs = $this->import_add_logs($company_id, $emp_id, $reason, $new_time_in,$new_lunch_out,$new_lunch_in,$new_fbreak_out,$new_fbreak_in,$new_sbreak_out,$new_sbreak_in,$new_time_out, $hours_worked, $this->work_schedule_id,$check_break_time,$split,$log_error,$source,$emp_no,$gDate,$real_employee_time_in_id,$get_employee_payroll_information,$get_tardiness_settings,$company_holiday,$time_query_row,$time_query_where,$get_all_regular_schedule,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$attendance_hours,$get_work_schedule,$get_employee_leave_application,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_tardiness_settingsv2,$tardiness_rounding,$migrate_v3,$rd_ra,$holiday_orig,$holiday_settings_appr,$inclusion_hours,$rest_day_settings,$cronjob_time_recalculate,$get_nigtdiff_rule,$get_shift_restriction_schedules);
                        }
                    }
                }
            }
        }
    }

    public function edit_delete_void($emp_id,$comp_id,$gDate){
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

    // night diff additionall rule
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
                        "entitled_to_night_differential"    => $r1->entitled_to_night_differential,
                        "nightshift_differential_rule_name" => $r1->nightshift_differential_rule_name,
                        "custom_search"                     => "emp_id_{$r1->emp_id}"
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }

    // night diff additionall rule
    public function get_nigtdiff_rule($company_id){
        $row_array  = array();
        $w = array(
            "company_id" => $company_id
        );
        $this->db->where($w);

        $q = $this->db->get("nightshift_differential_settings");

        $r = $q->row();
        return $r;
        /*
        if($r){
            foreach ($r as $r1){
                $wd     = array(
                        "from_time"     => $r1->from_time,
                        "to_time"                               => $r1->to_time,
                        "exclude_entitlement_nsd_lunch_break"   => $r1->exclude_entitlement_nsd_lunch_break,
                        "exclude_entitlement_nsd_sr_lunch_break"=> $r1->exclude_entitlement_nsd_sr_lunch_break,
                        "custom_search"                         => "emp_id_{$r1->company_id}"
                );
                array_push($row_array,$wd);
            }
        }
        */
    }

    // night diff additionall rule
    public function get_shift_restriction_schedules($company_id){
        $row_array  = array();
        $w = array(
            "company_id" => $company_id
        );
        $this->db->where($w);

        $q = $this->db->get("nightshift_differential_settings_inclusion");

        $r = $q->result();

        if($r){
            foreach ($r as $r1){
                $wd     = array(
                        "work_schedule_ids"     => $r1->work_schedule_ids,
                        "custom_search"         => "wsid_{$r1->work_schedule_ids}"
                );
                array_push($row_array,$wd);
            }
        }
        return $row_array;
    }
    

    public function emp_employe_account_info($company_id,$emp_ids=""){

        $row_array  = array();
        $this->db->where('e.company_id',$company_id);
        if($emp_ids){
            $this->db->where_in("e.emp_id",$emp_ids);
        }
        $this->edb->join('accounts AS a','a.account_id = e.account_id',"INNER");
        $query  = $this->edb->get('employee AS e');
        $result = $query->result();

        if($result){
            foreach ($result as $r1){
                $wd     = array(
                        "payroll_cloud_id"  => $r1->payroll_cloud_id,
                        "custom_search"     => "emp_id_{$r1->emp_id}"
                );
                array_push($row_array,$wd);
            }
        }

        return $row_array;
    }

    public function identify_split_info($time_in,$time_out,$emp_id,$comp_id,$gDate=""){
        
        $data = array();
        
        $yesterday_m        = $gDate;
        $yesterday          = date('Y-m-d',strtotime($yesterday_m. " -1 day"));
        $work_schedule_id   = $this->emp_work_schedule($emp_id,$comp_id,$yesterday);
            
        $split              = $this->check_if_split_schedule($emp_id,$comp_id,$work_schedule_id, $yesterday ,$time_in,$time_out);
        
        if($split){
            
            $yest_list = $this->elm->list_of_blocks($yesterday,$emp_id,$work_schedule_id,$comp_id);
            $mid_night = date('Y-m-d H:i:s',strtotime($yesterday." 24:00:00"));
        
            if($yest_list){
                $first_sched = reset($yest_list);
                $last_sched = max($yest_list);
                $ystart = $this->elm->get_starttime($first_sched->schedule_blocks_id,$time_in); 
                $yend = $this->elm->get_endtime($last_sched->schedule_blocks_id,$time_in);
                
                foreach($yest_list  as $rx){
                                    
                    $row = $this->elm->get_blocks_list($rx->schedule_blocks_id);                        
                    $start_time = date('Y-m-d H:i:s',strtotime($yesterday." ".$row->start_time));
                    $end_time = date('Y-m-d H:i:s', strtotime($yesterday." ".$row->end_time));
                    if($ystart >= $yend && $time_in >= $mid_night){ // nightshift schedule                  
                        $new = date('H:i:s',strtotime($ystart));
                        $firstx = date('Y-m-d H:i:s',strtotime($yesterday." ".$new));
                        
                        if($start_time >= $firstx && $start_time <=$mid_night){                     
                            
                            $data['work_schedule_id'] = $work_schedule_id;
                            $data['current_date'] = $yesterday;
                            $data['time_in'] = $time_in;        
                        }
                    }               
                }
                                
            }
    
        }
        return $data;
    }

    public function emp_work_schedule($emp_id,$check_company_id,$currentdate){
        // employee group id
        $s = array(
            "ess.work_schedule_id"
        );
        $w_date = array(
            "ess.valid_from <="     =>  $currentdate,
            "ess.until >="          =>  $currentdate
        );
        $this->db->where($w_date);
        
        $w_emp = array(
            "e.emp_id"              => $emp_id,
            "ess.company_id"        => $check_company_id,
            "e.status"              =>"Active",
            "ess.status"            =>"Active",
            "ess.payroll_group_id"  => 0
        );
        $this->edb->select($s);
        $this->edb->where($w_emp);
        $this->edb->join("employee AS e","e.emp_id = ess.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $q_emp = $this->edb->get("employee_shifts_schedule AS ess");
        $r_emp = $q_emp->row();
        if($r_emp){
            return $r_emp->work_schedule_id;
        }
        else{
            $w = array(
                    'epi.emp_id'=> $emp_id
            );
            $this->db->where($w);
            $this->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','LEFT');
            $q_pg = $this->edb->get('employee_payroll_information AS epi');
            $r_pg = $q_pg->row();
            
            return ($r_pg) ? $r_pg->work_schedule_id : FALSE;
            
        }
    }

    public function emp_work_schedule_ess($check_company_id,$emp_ids = ""){
        $row_array  = array();
        $s          = array(
            "ess.work_schedule_id",
            "ess.valid_from",
            "ess.until",
            "e.emp_id"
        );

        $w_emp = array(
            "ess.company_id"        => $check_company_id,
            "e.status"              =>"Active",
            "ess.status"            =>"Active",
            "ess.payroll_group_id"  => 0
        );
        if($emp_ids){
            $this->db->where_in("ess.emp_id",$emp_ids);
        }
        $this->edb->select($s);
        $this->edb->where($w_emp);
        $this->edb->join("employee AS e","e.emp_id = ess.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
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

    public function check_if_split_schedule($emp_id,$comp_id,$work_schedule_id,$currentdate,$time_in ="",$time_out="",$get_all_schedule_blocks,$list_of_blocks,$get_all_regular_schedule){
        
        $data                   = array();
        $new_timein             = false;
        $block_completed        = 0;
        $time_in                = date('Y-m-d H:i:s',strtotime($time_in));
        $block_not_completed    = 0;
        $r_ws = in_array_foreach_custom("list_{$emp_id}_{$currentdate}_{$work_schedule_id}",$list_of_blocks);
        
        if($r_ws){
            $split = $this->get_splitschedule_info_new($time_in,$work_schedule_id,$emp_id,$comp_id,$get_all_schedule_blocks,$list_of_blocks,$currentdate);
            if($split){
                $data = $split;
            }
        }
        $day        = date('l',strtotime($time_in));
        $reg_sched  = in_array_custom("rsched_id_{$work_schedule_id}_{$day}",$get_all_regular_schedule);

        if($reg_sched){
            return false;
        }
        return $data;
    }

    public function get_splitschedule_info_new($time_in,$work_schedule_id,$emp_id,$comp_id,$get_all_schedule_blocks,$list_of_blocks,$currentdatex){
        $currentdate = date('Y-m-d',strtotime($currentdatex." -1 day"));
        $arr = array();
        $row_list = array();
        $ls = in_array_foreach_custom("list_{$emp_id}_{$currentdate}_{$work_schedule_id}",$list_of_blocks);
        $today = true;
        
        if($ls){
            
            $first = reset($ls);
            $last  = end($ls);

            $firstx =   $this->get_starttime($first->schedule_blocks_id,$currentdate,$first,$get_all_schedule_blocks);
            $last   =   $this->get_endtime($last->schedule_blocks_id,$currentdate,$last,$get_all_schedule_blocks);
            
            $first      = date('Y-m-d H:i:s',strtotime($firstx. ' midnight'));
            $mid_night  = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));

            if($firstx >= $last){
                $last = date('Y-m-d H:i:s',strtotime($last." +1 day"));
            }
            
            if($time_in>=$first && $time_in<=$last){
                $today= false;
                foreach($ls as $rx){
                    $row        = in_array_custom("sched_id_{$rx->schedule_blocks_id}",$get_all_schedule_blocks);
                    $start_time = date('Y-m-d H:i:s',strtotime($currentdate." ".$row->start_time));
                    $end_time   = date('Y-m-d H:i:s', strtotime($currentdate." ".$row->end_time));
                    
                    $refuse = true;
                    if($start_time>= $end_time){
                        $end_time = date('Y-m-d H:i:s',strtotime($end_time." +1 day"));
                        $refuse = false;
                    }

                    if($time_in >= $mid_night && $refuse){
                        $start_time = date('Y-m-d H:i:s',strtotime($start_time." +1 day"));
                        $end_time = date('Y-m-d H:i:s', strtotime($end_time." +1 day"));
                    }
                    
                    $row_list['break_in_min'] = $row->break_in_min;
                    $row_list['start_time'] = $start_time;
                    $row_list['end_time'] = $end_time;
                    $row_list['total_hours_work_per_block'] = $row->total_hours_work_per_block;
                    $row_list['block_name'] = $row->block_name;
                    $row_list['schedule_blocks_id'] = $row->schedule_blocks_id;

                    if($time_in >= $start_time && $time_in <= $end_time){
                
                        return $row_list;
                                                
                    }else{                  
                        $arr[] = $row_list;                 
                    }   
                }
            }
        }

        if($today){
            
            $currentdate    = date('Y-m-d',strtotime($currentdatex));
            $arr            = array();
            $row_list       = array();

            $ls = in_array_foreach_custom("list_{$emp_id}_{$currentdate}_{$work_schedule_id}",$list_of_blocks);
            
            if(!empty($ls)){
                $first  = reset($ls);
                $last   = end($ls);
    
                $firstx = $this->get_starttime($first->schedule_blocks_id,$currentdate,$first,$get_all_schedule_blocks);
                $last   = $this->get_endtime($last->schedule_blocks_id,$currentdate,$last,$get_all_schedule_blocks);
    
                //$first = date('Y-m-d H:i:s',strtotime($firstx. " -200 minutes"));
                //$first = date('Y-m-d H:i:s',strtotime($firstx. ' midnight'));
                $first = date('Y-m-d H:i:s',strtotime($firstx. ' -120 minutes'));
                
                $mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
                if($firstx >= $last){
                    $last = date('Y-m-d H:i:s',strtotime($last." +1 day"));
                }

                if($time_in>=$first && $time_in<=$last){
                    
                    foreach($ls as $rx){
        
                        $row        = in_array_custom("sched_id_{$rx->schedule_blocks_id}",$get_all_schedule_blocks);               
                        $start_time = date('Y-m-d H:i:s',strtotime($currentdate." ".$row->start_time));
                        $end_time   = date('Y-m-d H:i:s', strtotime($currentdate." ".$row->end_time));
                        
                        if($start_time >= $end_time){
                            $end_time = date('Y-m-d H:i:s',strtotime($end_time." +1 day"));                     
                        }
                        
                        $row_list['break_in_min']               = $row->break_in_min;
                        $row_list['start_time']                 = $start_time;
                        $row_list['end_time']                   = $end_time;
                        $row_list['total_hours_work_per_block'] = $row->total_hours_work_per_block;
                        $row_list['block_name']                 = $row->block_name;
                        $row_list['schedule_blocks_id']         = $row->schedule_blocks_id;
        
                        if($time_in >= $start_time && $time_in <= $end_time){
                    
                            return $row_list;
                                                    
                        }else{                  
                            $arr[] = $row_list;                 
                        }   
                    }
                }
                // if usa ra ka block sa usa ka split
                else if(count($ls) == 1){

                    foreach($ls as $rx){
        
                        $row        = in_array_custom("sched_id_{$rx->schedule_blocks_id}",$get_all_schedule_blocks);               
                        $start_time = date('Y-m-d H:i:s',strtotime($currentdate." ".$row->start_time));
                        $end_time   = date('Y-m-d H:i:s', strtotime($currentdate." ".$row->end_time));
                        
                        if($start_time >= $end_time){
                            $end_time = date('Y-m-d H:i:s',strtotime($end_time." +1 day"));                     
                        }
                        
                        $row_list['break_in_min']               = $row->break_in_min;
                        $row_list['start_time']                 = $start_time;
                        $row_list['end_time']                   = $end_time;
                        $row_list['total_hours_work_per_block'] = $row->total_hours_work_per_block;
                        $row_list['block_name']                 = $row->block_name;
                        $row_list['schedule_blocks_id']         = $row->schedule_blocks_id;
        
                        return $row_list;
                        
                    }
                }
            }
        }
        foreach($arr as $key => $row2):
            if($time_in <= $row2['start_time']){
                return $arr[$key];
                break;
            }
        endforeach;
        
        return $arr;
    }

    public function yesterday_split_info($time_in,$emp_id,$work_schedule_id,$company_id,$activate_today =false,$list_of_blocks,$get_all_schedule_blocks){
        
        $arr = array();
        //for night schedule;
        $yesterday_m = date('Y-m-d',strtotime($time_in));
        if($activate_today){
            $yesterday = date('Y-m-d',strtotime($yesterday_m));
        }
        else{
            $yesterday = date('Y-m-d',strtotime($yesterday_m. " -1 day"));
        }

        $mid_night = date('Y-m-d H:i:s',strtotime($yesterday." 24:00:00"));

        $yest_list = in_array_foreach_custom("list_{$emp_id}_{$yesterday}_{$work_schedule_id}",$list_of_blocks);

        if($yest_list){
            $first_sched = reset($yest_list);
            $last_sched = end($yest_list);
            
            $ystart     = $this->get_starttime($first_sched->schedule_blocks_id,$yesterday,$first_sched,$get_all_schedule_blocks);
            $yend       = $this->get_endtime($last_sched->schedule_blocks_id,$yesterday,$last_sched,$get_all_schedule_blocks);
            $check_end  = $this->get_starttime($last_sched->schedule_blocks_id,$yesterday,$last_sched,$get_all_schedule_blocks);
            
            foreach ($yest_list as $row){

                $block      = in_array_custom("sched_id_{$row->schedule_blocks_id}",$get_all_schedule_blocks);
                $start_f    = date('Y-m-d H:i:s',strtotime($yesterday." ".$block->start_time));
                $end_f      = date('Y-m-d H:i:s',strtotime($yesterday." ".$block->end_time));
                
                if($end_f <=$start_f){
                    $end_f = date('Y-m-d H:i:s',strtotime($end_f. " +1 day"));
                }
                if($start_f >= $ystart && $start_f <= $mid_night ){
                    $arr[] = array(
                            'start_time'        => $start_f,
                            'end_time'          => $end_f,
                            'work_schedule_id'  => $work_schedule_id,
                            'schedule_block_id' => $row->schedule_blocks_id
                    );
                }
                else{
                    $arr[] = array(
                            'start_time'        => date('Y-m-d H:i:s',strtotime($start_f." +1 day")),
                            'end_time'          => date('Y-m-d H:i:s',strtotime($end_f." +1 day")),
                            'work_schedule_id'  => $work_schedule_id,
                            'schedule_block_id' => $row->schedule_blocks_id
                    );
                }
            }
        }
        return $arr;
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

    public function get_starttime($schedule_blocks_id,$time_in = null,$start = array(),$get_all_schedule_blocks){
        
        $result_schedule_blocks = in_array_custom("sched_id_{$schedule_blocks_id}",$get_all_schedule_blocks);

        $date_time  = date('Y-m-d',strtotime($time_in));
        $arr        = array();
        $row_list   = array();
        
        if($result_schedule_blocks){
            //foreach($result_schedule_blocks as $row){
            $start_time = date('Y-m-d H:i:s',strtotime($date_time." ".$result_schedule_blocks->start_time));
            $end_time   = date('Y-m-d H:i:s', strtotime($date_time." ".$result_schedule_blocks->end_time));

            if($result_schedule_blocks->end_time <= $result_schedule_blocks->start_time){
                $end_time = date('Y-m-d H:i:s',strtotime($date_time." ".$result_schedule_blocks->end_time . " +1 day"));
            }
            if($time_in >= $start_time && $time_in <= $end_time){
                return $start_time;
            }
            else{
                $arr[] =  $start_time;
            }
            //}
        }
        
        if($arr){
            foreach($arr as $key => $row2){
                if($time_in <= $row2){
                    return $row2;
                }
            }
        }
        
        if($start){
            $list           = in_array_custom("sched_id_{$start->schedule_blocks_id}",$get_all_schedule_blocks);
            $block_start    = date('Y-m-d H:i:s',strtotime($date_time." ". $list->start_time)); 
            return $block_start;
        }

        return false;
    }

    public function get_endtime($schedule_blocks_id,$time_in,$last = array(),$get_all_schedule_blocks){

        $result_schedule_blocks = in_array_custom("sched_id_{$schedule_blocks_id}",$get_all_schedule_blocks);
        $date_time = date('Y-m-d',strtotime($time_in));
        $arr = array();

        if($result_schedule_blocks){
            //foreach($result_schedule_blocks as $row){
                $start_time = date('Y-m-d H:i:s',strtotime($date_time." ".$result_schedule_blocks->start_time));
                $end_time = date('Y-m-d H:i:s', strtotime($date_time." ".$result_schedule_blocks->end_time));
                
                if($result_schedule_blocks->end_time == "00:00:00"){
                    $end_time = date('Y-m-d H:i:s',strtotime($date_time." ".$result_schedule_blocks->end_time));
                }
                
                if($result_schedule_blocks->end_time <= $result_schedule_blocks->start_time){                   
                    $end_time = date('Y-m-d H:i:s',strtotime($date_time." ".$result_schedule_blocks->end_time . " +1 day"));                
                }
                
                if($time_in >= $start_time && $time_in <= $end_time){
                    return $end_time;
                }
                else{
                    $arr[] = $end_time;     
                }
            //}
        }

        if($arr){
            foreach($arr as $key => $row2){
                $time = date('H:i:s',strtotime($row2));
                if($time_in <= $row2 || $time == "00:00:00:"){          
                    return $row2;
                }
            }
        }
        
        if($last){
            $list       = in_array_custom("sched_id_{$last->schedule_blocks_id}",$get_all_schedule_blocks);
            $block_end  = date('Y-m-d H:i:s',strtotime($date_time." ". $list->end_time)); 
            return $block_end;
        }

        return false;
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
                    'break_2'
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
                $year       = $year_min;
                if($r1->repeat_type == "yes" && $r1->date_type != "movable"){
                    
                    for($x = 0;$year <= $y_gap;$x++){
                        
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

    function get_work_schedule($company_id){
        $row_array  = array();
        
        $s = array(
            "work_schedule_id",
            "work_type_name AS workday_type",
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
            "enable_working_on_restday",
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
                        "workday_type"                      => $r1->workday_type,
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
                        "enable_working_on_restday"         => $r1->enable_working_on_restday,
                        "custom_search"                     => "worksched_id_{$r1->work_schedule_id}",
                        "custom_searchv2"                   => "worksched_migrate_{$r1->default}_{$r1->flag_migrate}",
                        );
                array_push($row_array,$wd);
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
            
        }else{
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
            //"ela.existing_leave_used_to_date",
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
                        //"existing_leave_used_to_date"     => $r1->existing_leave_used_to_date,
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

    public function late_min($comp_id,$date,$emp_id,$work_schedule_id,$time_in= "",$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in){
        # use in upload
        if($time_in){
            $current_time = $time_in;
        }else{
            $current_time = date('Y-m-d H:i:s');
        }

        $day    = date('l',strtotime($date));
        $r_uwd  = in_array_custom("rsched_id_{$work_schedule_id}_{$day}",$get_all_regular_schedule);

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
                $end_date                       = $current_time;
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

            if($new_time_in > $start_date_time){

                $min                            = $this->total_hours_worked($new_time_in, $start_date_time);
            }

            if($break > 0){
                if($start_date_time_half < $new_time_in){
                    $min                        = $min - $break;
                }
            }
            
            $min                                = 0;
            $tardiness_set                      = $this->tardiness_settings($emp_id, $comp_id,$get_employee_payroll_information,$get_tardiness_settings);
            $tardiness_set                      = ($tardiness_set > 0) ? $tardiness_set : 0;
            $min                                = $min - $tardiness_set;

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

    public function overbreak_min($comp_id,$date,$emp_id,$work_schedule_id,$time_in,$lunch_out,$lunc_in ="",$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in){

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
                    'tardiness_settings_id',
                    'tardiness_rule_name',
                    'tarmin',
                    'rank_id',
                    'rank_ids',
                    'payroll_group_ids',
                    'grace_period_type',
                    'default',
                    'starts_shift_start_time',
                    'starts_end_of_grace_period',
                    'enable_option',
                    );

        $w_uwd      = array(
                    "comp_id"   => $company_id,
                    "status"    => 'Active'
                    );

        $this->db->where($w_uwd);
        $this->db->select($s);
        
        $tard = $this->db->get("tardiness_settings");
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

        return $row_array;
    }

    public function new_get_splitinfo($comp_id,$work_schedule_id,$emp_id="",$gDate="",$time_in="",$get_all_employee_timein,$list_of_blocks,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$emp_timeins=false){
        
        //*** BARACK NEW LOGIC GET SPLIT BLOCK SCHEDUL DEPENDING CURRENT TIME ***//
        //** First (current datetime) specify which block belong
        //** if block is specify, check if (cdt) [timein or timeOut] [lunchOut or lunchIn],
        //** if (cdt) is not belong to block find the nearest block,
        //** if block is specify, check if (cdt) timein or timeOut,
        
        $current_date               = ($gDate) ? $gDate : date("Y-m-d");
        $current_date_str           = strtotime($current_date);
        $prev_date                  = ($gDate) ? $gDate : date("Y-m-d");
        $prev_date_str              = strtotime($prev_date);
        $current_time               = ($time_in) ? date("H:i:s", strtotime($time_in)) : date("H:i:s");
        $current_datetime           = ($time_in) ? $time_in : date("Y-m-d H:i:s");
        $current_datetime_str       = strtotime($current_datetime);
        $get_sched                  = false;
        $schedule_blocks_id         = "";
        $break                      = 0;
        $total_hour_block_sched     = 0;
        $r                          = "";
        $enable_breaks_on_holiday   = "";
        $total_h_p_block            = "";
        $block_name                 = "";
        $last_block                 = "";
        $clock_type                 = "";

        if($emp_timeins){
            $r                  = $emp_timeins;
        }

        if(!$emp_timeins){
            $emp_timeins        = in_array_foreach_custom("emp_id_timeins_{$emp_id}",$get_all_employee_timein); 

            if($emp_timeins){
                $r              = reset($emp_timeins);
            }
        }
        
        if($r){
            // if wala pa sa db na save ang current date
            $prev_date          = $r->date;
            $prev_date_str      = strtotime($prev_date);
        }
        if($prev_date_str >= $current_date_str){
            $prev_date          = $current_date;
            $prev_date_str      = strtotime($prev_date);
        }
        if($prev_date_str == $current_date_str){
            // if naa na sa db ang current date
            $prev_date          = date('Y-m-d', strtotime($prev_date." -1 day"));
        }
        
        //*** DISPLAY ALL SPLIT BLOCK SCHEDULE ***//

        # get_all_schedule_blocks
        # list_of_blocks
        $r_ws                   = array();

        $list_blocks            = in_array_foreach_custom("list_{$emp_id}_{$current_date}_{$work_schedule_id}",$list_of_blocks);
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
            $total                          = strtotime($first_block_start_datetime) - strtotime($last_block_end_datetime);
            $hours                          = floor($total / 60 / 60);
            $minutes                        = floor(($total - ($hours * 60 * 60)) / 60);
            $ret                            = ($hours * 60) + $minutes;
            $total_gap                      = ($ret < 0) ? '0' : $ret;
            $total_gap                      = $total_gap/2;
            if($total_gap > 120){
                $total_gap                  = 120;
            }
            $first_block_boundary_datetime      = date('Y-m-d H:i:s',strtotime($first_block_start_datetime . "-".$total_gap." minutes"));
            $first_block_boundary_datetime_str  = strtotime($first_block_boundary_datetime);
            
            if($first_block_boundary_datetime_str > $current_datetime_str){
                $current_date = $prev_date;
            }
            
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

                    $current_date       = date('Y-m-d', strtotime($current_date." +1 day"));
                }
                
                $start_datetime         = date('Y-m-d H:i:s', strtotime($current_date." ".$start_time));
                
                if($start_time_str > $end_time_str){
                    $end_datetime       = date('Y-m-d H:i:s', strtotime($current_date." ".$end_time." +1 day"));
                }else{
                    $end_datetime       = date('Y-m-d H:i:s', strtotime($current_date." ".$end_time));
                }
                
                $start_datetime_str     = strtotime($start_datetime);
                $end_datetime_str       = strtotime($end_datetime);
                
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
                    
                    //
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
                    $clock_type         = $this->check_clock_type($time_is_check,$emp_id,$comp_id,$eti,$schedule_blocks_id,$current_date,$break,$get_all_schedule_blocks_time_in);
                    $start_time         = $rws->start_time;
                    $end_time           = $rws->end_time;
                    $total_h_p_block    = $rws->total_hours_work_per_block;
                    $block_name         = $rws->block_name;
                    $last_block         = $last_block_id;
                    break;
                }
                // if usa ra na block sa usa ka shift
                else if(count($r_ws) == 1){
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

    public function check_clock_type($time_is_check,$emp_id,$comp_id,$eti,$schedule_blocks_id,$current_date,$break,$get_all_schedule_blocks_time_in){
        if($time_is_check){

            $sched_block = in_array_custom("emp_id_sched_id_{$emp_id}_{$schedule_blocks_id}_{$eti}_{$current_date}",$get_all_schedule_blocks_time_in);
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
                    "date <="   => $max_range,
            );
            $this->db->where($w1);
        }

        $time_in_status = array(
                        "NULL",
                        'approved'
                        );
        //$this->db->where_in("time_in_status",$time_in_status);
        $this->db->where("(time_in_status = 'approved' OR time_in_status is NULL)",FALSE,FALSE);
        $this->db->where($w);
        $this->db->order_by("date","DESC");
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
                "comp_id"               => $company_id,
                "time_in_status !="     => 'reject'
                );
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
                        );
                array_push($row_array, $wd);
            }
        }
        return $row_array;
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

    public function add_rest_day($time_query_row,$time_query_where,$log_error="",$company_id,$emp_id,$currentdate,$new_time_in,$new_time_out,$source,$rest_day_settings="no",$holiday=false,$holiday_settings_appr="no"){
        //we check the time in if have value if not well  just update
        $h                  = $this->total_hours_worked($new_time_out,$new_time_in);
        $get_total_hours    = $this->convert_to_hours($h);
        if($time_query_row) {
            if($log_error){
                $date_insert = array(
                        "comp_id"               => $company_id,
                        "emp_id"                => $emp_id,
                        "date"                  => $currentdate ,
                        "time_in"               => $new_time_in,
                        "time_out"              => $new_time_out,
                        "work_schedule_id"      => -1,
                        "total_hours"           => 0 ,
                        "total_hours_required"  => 0,
                        "undertime_min"         => 0,
                        "tardiness_min"         => 0,
                        "overbreak_min"         => 0,
                );
            }
            else{
                $tq_update_field = array(
                                    "work_schedule_id"      => -1,
                                    "time_in"               => $new_time_in,
                                    "time_out"              => $new_time_out,
                                    "late_min"              => 0,
                                    "tardiness_min"         => 0,
                                    "undertime_min"         => 0,
                                    "overbreak_min"         => 0,
                                    "absent_min"            => 0,
                                    "total_hours"           => $get_total_hours,
                                    "total_hours_required"  => $get_total_hours,
                                    "change_log_date_filed" => date('Y-m-d H:i:s')
                                );
            }

            // this is for rd ra
            // if need approval
            if($rest_day_settings == "yes"){
                $tq_update_field['rest_day_r_a']            = "yes";
                //$tq_update_field['flag_rd_include']       = "no";
                // if need approval
                if($time_query_row->time_in_status != 'approved'){
                    $tq_update_field['flag_rd_include']         = "no";
                }else{
                    $tq_update_field['flag_rd_include']         = "yes";
                }
            }
            else{
                $tq_update_field['rest_day_r_a']            = "no";
                $tq_update_field['flag_rd_include']         = "yes";
            }

            // rd holiday
            if($holiday){
                // if holiday needs approval based on settings
                if($holiday_settings_appr == 'yes'){
                    $tq_update_field['holiday_approve']             = "yes";
                    $tq_update_field['flag_holiday_include']        = "no";
                }
                else{
                    $tq_update_field['holiday_approve']             = "no";
                    $tq_update_field['flag_holiday_include']        = "yes";
                }
            } 
            else{
                $tq_update_field['holiday_approve']             = "no";
                $tq_update_field['flag_holiday_include']        = "yes";
            }

            if($time_query_row->source!=""){
                $tq_update_field['last_source'] = $source;
            }
            $this->db->where($time_query_where);
            $this->db->update('employee_time_in',$tq_update_field);
            
        }else{

            if($source == "recalculated"){
                return false;
            }
            
            if($log_error){
                $date_insert    = array(
                                "source"                => $source,
                                "comp_id"               => $company_id,
                                "emp_id"                => $emp_id,
                                "date"                  => $currentdate,
                                "time_in"               => $new_time_in,
                                "time_out"              => $new_time_out,
                                "tardiness_min"         => 0,
                                "overbreak_min"         => 0,
                                "undertime_min"         => 0,
                                "work_schedule_id"      => -1,
                                "total_hours"           => 0,
                                "flag_time_in"          => 0,
                                "total_hours_required"  => 0
                                );
            }
            else{
                $date_insert    = array(
                                "source"                => $source,
                                "comp_id"               => $company_id,
                                "date"                  => $currentdate,
                                "emp_id"                => $emp_id,
                                "time_in"               => $new_time_in,
                                "time_out"              => $new_time_out,
                                "late_min"              => 0,
                                "tardiness_min"         => 0,
                                "undertime_min"         => 0,
                                "overbreak_min"         => 0,
                                "absent_min"            => 0,
                                "work_schedule_id"      => -1,
                                "total_hours"           => $get_total_hours,
                                "total_hours_required"  => $get_total_hours,
                                "flag_time_in"          => 0,
                                );
            }

            // this is for rd ra
            // if need approval
            if($rest_day_settings == "yes"){
                $date_insert['rest_day_r_a']            = "yes";
                $date_insert['flag_rd_include']         = "no";
            }
            else{
                $date_insert['rest_day_r_a']            = "no";
                $date_insert['flag_rd_include']         = "yes";
            }

            // rd holiday
            if($holiday){
                // if holiday needs approval based on settings
                if($holiday_settings_appr == 'yes'){
                    $tq_update_field['holiday_approve']             = "yes";
                    $tq_update_field['flag_holiday_include']        = "yes";
                }
                else{
                    $tq_update_field['holiday_approve']             = "no";
                    $tq_update_field['flag_holiday_include']        = "yes";
                }
            }
            else{
                $tq_update_field['holiday_approve']             = "no";
                $tq_update_field['flag_holiday_include']        = "yes";
            }

            $add_logs = $this->db->insert('employee_time_in', $date_insert);
        }

        // athan helper
        if($currentdate){
            $date = $currentdate;
            payroll_cronjob_helper($type='timesheet',$date,$emp_id,$company_id);
        }
    }

    public function add_holiday_logs($time_query_row,$time_query_where,$log_error="",$company_id,$emp_id,$currentdate,$new_time_in,$new_time_out,$work_schedule_id,$source,$holiday_settings_appr="no"){
        $h           = $this->total_hours_worked($new_time_out,$new_time_in);
        $holday_hour = $this->convert_to_hours($h);
        
        if($time_query_row) {
            if($log_error){
                $date_insert        = array(
                                    "comp_id"               => $company_id,
                                    "emp_id"                => $emp_id,
                                    "date"                  => $currentdate ,
                                    "time_in"               => $new_time_in,
                                    "time_out"              => $new_time_out,
                                    "work_schedule_id"      => $work_schedule_id,
                                    "total_hours"           => 0,
                                    "total_hours_required"  => 0,
                                    "late_min"              => 0,
                                    "tardiness_min"         => 0,
                                    "overbreak_min"         => 0,
                                    "undertime_min"         => 0
                                    );
            }else{
                $tq_update_field    = array(
                                    "time_in"               => $new_time_in,
                                    "time_out"              => $new_time_out,
                                    "work_schedule_id"      => $work_schedule_id,
                                    "total_hours"           => $holday_hour,
                                    "total_hours_required"  => $holday_hour,
                                    "change_log_date_filed" => date('Y-m-d H:i:s'),
                                    "late_min"              => 0,
                                    "tardiness_min"         => 0,
                                    "overbreak_min"         => 0,
                                    "undertime_min"         => 0
                                    );
            }
            
            if($time_query_row->source!=""){
                $tq_update_field['last_source']                 = $source;
            }

            // if holiday needs approval based on settings
            if($holiday_settings_appr == 'yes'){
                $tq_update_field['holiday_approve']             = "yes";
                $tq_update_field['flag_holiday_include']        = "no";
            }
            else{
 
            }

            $this->db->where($time_query_where);
            $this->db->update('employee_time_in',$tq_update_field);

        }
        else{
            
            if($log_error){
                $date_insert = array(
                            "comp_id"               => $company_id,
                            "emp_id"                => $emp_id,
                            "date"                  => $currentdate ,
                            "source"                => $source,
                            "time_in"               => $new_time_in,
                            "time_out"              => $new_time_out,
                            "work_schedule_id"      => $work_schedule_id,
                            "total_hours"           => 0 ,
                            "total_hours_required"  => 0,
                            "late_min"              => 0,
                            "tardiness_min"         => 0,
                            "undertime_min"         => 0,
                            "overbreak_min"         => 0,
                            "absent_min"            => 0,
                            );
            }else{
                $date_insert = array(
                            "comp_id"               => $company_id,
                            "emp_id"                => $emp_id,
                            "date"                  => $currentdate ,
                            "source"                => $source,
                            "time_in"               => $new_time_in,
                            "time_out"              => $new_time_out,
                            "work_schedule_id"      => $work_schedule_id,
                            "total_hours"           => $holday_hour ,
                            "total_hours_required"  => $holday_hour,
                            "late_min"              => 0,
                            "tardiness_min"         => 0,
                            "overbreak_min"         => 0,
                            "undertime_min"         => 0,
                            "absent_min"            => 0,
                            );
            }

            // if holiday needs approval based on settings
            if($holiday_settings_appr == 'yes'){
                $date_insert['holiday_approve']             = "yes";
                $date_insert['flag_holiday_include']    = "no";
            }
            else{
                $date_insert['holiday_approve']             = "no";
            }

            $add_logs = $this->db->insert('employee_time_in', $date_insert);
        }

        // athan helper
        if($currentdate){
            $date = $currentdate;
            payroll_cronjob_helper($type='timesheet',$date,$emp_id,$company_id);
        }
    }

    public function open_shift($comp_id, $emp_id, $time_in,$time_out, $work_schedule_id,$source ="",$gDate ="",$employee_time_in_id ="",$get_employee_payroll_information,$get_tardiness_settings,$company_holiday,$time_query_row,$time_query_where,$get_all_regular_schedule,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$attendance_hours,$get_work_schedule,$get_employee_leave_application,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_tardiness_settingsv2,$tardiness_rounding,$migrate_v3=false,$rd_ra="no",$holiday_orig=false,$holiday_settings_appr="no",$inclusion_hours = "0",$rest_day_settings="no",$get_all_employee_period_typex,$get_all_payroll_calendarx){

        if($time_in != NULL && $work_schedule_id){

            if($gDate){
                $currentdate                    = $gDate;
            }
            else{
                $currentdate                    = date('Y-m-d',strtotime($time_in));
            }

            $total_hours_worked = $this->total_hours_worked($time_out,$time_in);
            $total_hours_worked = $this->convert_to_hours($total_hours_worked);

            if($source != 'Dashboard' && $time_query_row){

                if($source == "updated" || $source == "recalculated" || $source == "import"){

                    $tq_update_field = array(
                                    "time_in"                   => $time_in,
                                    "time_out"                  => $time_out,
                                    "date"                      => $currentdate,
                                    "work_schedule_id"          => $work_schedule_id,
                                    "undertime_min"             => 0,
                                    "tardiness_min"             => 0 ,
                                    "total_hours"               => $total_hours_worked,
                                    "late_min"                  => 0,
                                    "overbreak_min"             => 0,
                                    "absent_min"                => 0,
                                    "total_hours_required"      => $total_hours_worked,
                                    "change_log_date_filed"     => date('Y-m-d H:i:s'),
                                    "flag_open_shift"           => "1"
                                    );

                    $emp_pg_detail                                      = in_array_custom2("custom_searchv3","emp_{$value->emp_id}",$get_all_employee_period_typex);
                                
                    if($emp_pg_detail){
                        $emp_check_cut_off                              = in_array_custom2("custom_search","pay_sched_{$emp_pg_detail->period_type}_{$value->comp_id}_{$currentdate}",$get_all_payroll_calendarx);
                        
                        // if cut off
                        if($emp_check_cut_off){
                            // check if time out is ni labang sa current date na cut off

                            $cut_end_time                               = $emp_check_cut_off->cut_off_to." 23:59:00";
                            // if nilapas putlon ang hours worked then create balik for next day
                            if(strtotime($cut_end_time) < strtotime($time_out)){
                                $currentdatex                                   = date("Y-m-d",strtotime($currentdate." +1 days"));
                                $time_outx                                      = $time_out;
                                $time_inx                                       = date("Y-m-d H:i:s",strtotime($cut_end_time." +1 minutes"));
                                
                                $time_out                                       = $cut_end_time;
                                $tq_update_field['time_out']                    = $time_out;

                                $total_hours_workedx                            = (strtotime($time_outx) - strtotime($time_inx))/60/60;
                                $insert_new_period['emp_id']                    = $emp_id;
                                $insert_new_period['date']                      = $currentdatex;
                                $insert_new_period['comp_id']                   = $comp_id;
                                $insert_new_period['time_in']                   = $time_inx;
                                $insert_new_period['time_out']                  = $time_outx;
                                $insert_new_period['date']                      = $currentdatex;
                                $insert_new_period['work_schedule_id']          = $work_schedule_id;
                                $insert_new_period['total_hours']               = $total_hours_workedx;
                                $insert_new_period['total_hours_required']      = $total_hours_workedx;

                                $this->db->insert("employee_time_in",$insert_new_period);
                                // athan helper
                                if($currentdatex){
                                    payroll_cronjob_helper($type='timesheet',$currentdatex,$emp_id,$comp_id);
                                }
                            }
                        }
                    }

                    $total_hours_worked                             = (strtotime($time_out) - strtotime($time_in))/60/60;

                    $tq_update_field['total_hours']                     = $total_hours_worked;
                    $tq_update_field['total_hours_required']            = $total_hours_worked;

                    // added for coaching
                    if($inclusion_hours == "1"){
                        $tq_update_field["inclusion_hours"]     = $inclusion_hours;
                    }

                    if($time_query_row->source!=""){
                        $tq_update_field['last_source'] = $source;
                    }
                    
                    // if holiday needs approval based on settings
                    if($holiday_orig){
                        if($holiday_settings_appr == "yes"){
                            $tq_update_field['holiday_approve']         = "yes";
                            $tq_update_field['flag_holiday_include']    = "no";
                        }
                    }
                    
                    $this->db->where($time_query_where);
                    $this->db->update('employee_time_in',$tq_update_field);

                    // athan helper
                    if($currentdate){
                        $date = $currentdate;
                        payroll_cronjob_helper($type ='timesheet',$date,$emp_id,$comp_id);
                    }
                }
            }
            else{

                $date_insert                                        = array(
                                                                    "comp_id"                   => $comp_id,
                                                                    "emp_id"                    => $emp_id,
                                                                    "date"                      => $currentdate,
                                                                    "source"                    => $source,
                                                                    "time_in"                   => $time_in,
                                                                    "time_out"                  => $time_out,
                                                                    "work_schedule_id"          => $work_schedule_id,
                                                                    "undertime_min"             => 0,
                                                                    "tardiness_min"             => 0,
                                                                    "late_min"                  => 0,
                                                                    "overbreak_min"             => 0,
                                                                    "total_hours"               => $total_hours_worked ,
                                                                    "total_hours_required"      => $total_hours_worked,
                                                                    "flag_open_shift"           => "1"
                                                                    );

                $emp_pg_detail                                      = in_array_custom2("custom_searchv3","emp_{$emp_id}",$get_all_employee_period_typex);
                                
                if($emp_pg_detail){
                    $emp_check_cut_off                              = in_array_custom2("custom_search","pay_sched_{$emp_pg_detail->period_type}_{$comp_id}_{$currentdate}",$get_all_payroll_calendarx);
                    
                    // if cut off
                    
                    if($emp_check_cut_off){
                        // check if time out is ni labang sa current date na cut off

                        $cut_end_time                               = $emp_check_cut_off->cut_off_to." 23:59:00";
                        // if nilapas putlon ang hours worked then create balik for next day
                        if(strtotime($cut_end_time) < strtotime($time_out)){
                            $currentdatex                                   = date("Y-m-d",strtotime($currentdate." +1 days"));
                            $time_outx                                      = $time_out;
                            $time_inx                                       = date("Y-m-d H:i:s",strtotime($cut_end_time." +1 minutes"));
                            
                            $time_out                                       = $cut_end_time;
                            $date_insert['time_out']                        = $time_out;

                            $total_hours_workedx                            = (strtotime($time_outx) - strtotime($time_inx))/60/60;
                            $insert_new_period['emp_id']                    = $emp_id;
                            $insert_new_period['date']                      = $currentdatex;
                            $insert_new_period['comp_id']                   = $comp_id;
                            $insert_new_period['time_in']                   = $time_inx;
                            $insert_new_period['time_out']                  = $time_outx;
                            $insert_new_period['date']                      = $currentdatex;
                            $insert_new_period['work_schedule_id']          = $work_schedule_id;
                            $insert_new_period['total_hours']               = $total_hours_workedx;
                            $insert_new_period['total_hours_required']      = $total_hours_workedx;

                            $this->db->insert("employee_time_in",$insert_new_period);
                            // athan helper
                            if($currentdatex){
                                payroll_cronjob_helper($type='timesheet',$currentdatex,$emp_id,$comp_id);
                            }
                        }
                    }
                }

                $total_hours_worked                             = (strtotime($time_out) - strtotime($time_in))/60/60;

                $date_insert['total_hours']                     = $total_hours_worked;
                $date_insert['total_hours_required']            = $total_hours_worked;
                
                // if holiday needs approval based on settings
                if($holiday_orig){
                    if($holiday_settings_appr == "yes"){
                        $date_insert['holiday_approve']             = "yes";
                        $date_insert['flag_holiday_include']        = "no";
                    }
                }

                $this->db->insert('employee_time_in', $date_insert);

                // athan helper
                if($currentdate){
                    $date = $currentdate;
                    payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
                }
            }
        }
    }

    public function import_add_logs($comp_id, $emp_id, $reason, $time_in,$lunch_out,$lunch_in,$fbreak_out,$fbreak_in,$sbreak_out,$sbreak_in,$time_out, $hours_worked, $work_schedule_id,$break=0,$split=array(),$log_error = false,$source ="",$emp_no = "",$gDate ="",$employee_time_in_id ="",$get_employee_payroll_information,$get_tardiness_settings,$company_holiday,$time_query_row,$time_query_where,$get_all_regular_schedule,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$attendance_hours,$get_work_schedule,$get_employee_leave_application,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_tardiness_settingsv2,$tardiness_rounding,$migrate_v3=false,$rd_ra="no",$holiday_orig=false,$holiday_settings_appr="no",$inclusion_hours = "0",$rest_day_settings="no",$cronjob_time_recalculate=false,$get_nigtdiff_rule=false,$get_shift_restriction_schedules=false){
        //$time_in = "2018-08-16 7:00:00";
        //$time_out = "2018-08-16 11:00:00";
        /*  
        // For test 
        $this->import_add_logsv2($comp_id, $emp_id, $reason, $time_in,$lunch_out,$lunch_in,$fbreak_out,$fbreak_in,$sbreak_out,$sbreak_in,$time_out, $hours_worked, $work_schedule_id,$break,$split,$log_error,$source,$emp_no,$gDate,$employee_time_in_id,$get_employee_payroll_information,$get_tardiness_settings,$company_holiday,$time_query_row,$time_query_where,$get_all_regular_schedule,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$attendance_hours,$get_work_schedule,$get_employee_leave_application,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_tardiness_settingsv2,$tardiness_rounding,$migrate_v3);
        exit();
        // */

        $total_hours_workedv3 = 0;
        $current_date_nsd     = 0;
        $next_date_nsd        = 0;
        $flag_deduct_break    = false;
        
        if($time_in != NULL && $work_schedule_id){

            // if schedule is splitz
            if($split){
                $split_here                         = $this->import_split_sched($time_in,$time_out,$work_schedule_id,$comp_id,$emp_id,$lunch_in,$lunch_out,$log_error,"",$emp_no,$gDate,$source,$list_of_blocks,$get_all_schedule_blocks,$time_query_where,$time_query_row,$get_all_employee_timein,$get_all_schedule_blocks_time_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$company_holiday);
            }
            else{
                // if regular, nighshift and compress
                if($gDate){
                    $currentdate                    = $gDate;
                }
                else{
                    $currentdate                    = date('Y-m-d',strtotime($time_in));
                }

                if($lunch_out == $lunch_in){

                    $lunch_out                      = NULL;
                    $lunch_in                       = NULL;
                }
                if($fbreak_out == $fbreak_in){
                    $fbreak_out                     = NULL;
                    $fbreak_in                      = NULL;
                }
                if($sbreak_out == $sbreak_in){
                    $sbreak_out                     = NULL;
                    $sbreak_in                      = NULL;
                }

                // store the orig break
                $break_orig                         = $break;
                // grace period

                $tardiness_set                      = $this->tardiness_settings($emp_id, $comp_id,$get_employee_payroll_information,$get_tardiness_settings);
                $grace_period                       = ($tardiness_set) ? $tardiness_set : 0;

                // holiday now
                $holiday                            = in_array_custom("holiday_{$currentdate}",$company_holiday);
                
                if($holiday){
                    
                    $check_if_worksched_holiday     = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);

                    if($check_if_worksched_holiday){

                        $time_keep_holiday          = ($check_if_worksched_holiday->enable_breaks_on_holiday == 'yes') ? true : false;

                        if($time_keep_holiday){
                            $holiday                = false;
                        }
                    }
                    if($holiday){
                        $this->add_holiday_logs($time_query_row,$time_query_where,$log_error="",$comp_id,$emp_id,$currentdate,$time_in,$time_out,$work_schedule_id,$source,$holiday_settings_appr);
                        return true;
                    }
                }

                $day                                = date('l',strtotime($currentdate));
                $reg_sched                          = in_array_custom("rsched_id_{$this->work_schedule_id}_{$day}",$get_all_regular_schedule);
                $undertime                          = 0;
                $absent_min                         = 0;
                $update_total_hours                 = 0;
                $tardiness                          = 0;

                if($reg_sched){

                    $hours_worked                   = $reg_sched->total_work_hours;
                    $w_start                        = $reg_sched->work_start_time;
                    $w_end                          = $reg_sched->work_end_time;
                    $tresh                          = $reg_sched->latest_time_in_allowed;
                    $end_date                       = $currentdate;
                    $start_date                     = $currentdate;

                    if($w_start > $w_end){

                        $end_date                   = date('Y-m-d',strtotime($end_date. " +1 day"));
                        $end_date                   = $currentdate;
                    }

                    $start_date_time                = date("Y-m-d H:i:00", strtotime($start_date." ".$w_start));
                    $end_date_time                  = date("Y-m-d H:i:00", strtotime($end_date." ".$w_end));
                    $init_start_date_time           = $start_date_time;

                    if($time_in > $start_date_time){

                        if($tresh){

                            $start_date_time        = date('Y-m-d H:i:00',strtotime($init_start_date_time. " +".$tresh." minutes"));

                            if($time_in > $start_date_time){

                                $end_date_time      = date('Y-m-d H:i:00',strtotime($end_date. " +".$tresh." minutes"));
                            }
                            else{

                                $min_tresh          = $this->total_hours_worked($time_in,$init_start_date_time);
                                $start_date_time    = date('Y-m-d H:i:00',strtotime($init_start_date_time. " +".$min_tresh." minutes"));
                                $end_date_time      = date('Y-m-d H:i:00',strtotime($end_date. " +".$min_tresh." minutes"));
                            }
                        }
                    }
                    // UNDERTIME
                    
                    if($end_date_time > $time_out){
                        $undertime                  = ($this->total_hours_worked($time_out,$end_date_time) > 0) ? $this->total_hours_worked($time_out,$end_date_time) : 0;
                    }
                    // TARDINESS
                    if($time_in > $start_date_time){
                        $tardiness                  = ($this->total_hours_worked($time_in,$start_date_time) > 0) ? $this->total_hours_worked($time_in,$start_date_time) : 0;
                    }
                }
                
                $half_day                           = 0;
                $late_min                           = 0;//$this->late_min($comp_id,$currentdate,$emp_id,$work_schedule_id,$time_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);
                $overbreak_min                      = $this->overbreak_min($comp_id,$currentdate,$emp_id,$work_schedule_id,$time_in,$lunch_out,$lunch_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);
                
                $disable_absent                     = true;
                $tardiness_absent                   = false;
                $undertime_absent                   = false;
                $breakx                             = 0;
                
                // check break assumed
                $is_work                            = false;
                $is_work1                           = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);

                if($is_work1){
                    $break_rules                    = $is_work1->break_rules;
                    if($break_rules == "assumed"){
                        $is_work                    = $is_work1;
                    }
                }

                if($migrate_v3){
                    $is_work                        = false;
                }

                if($is_work){
                }
                else {
                    $get_tardiness                  = $tardiness;
                }
                
                $hours_worked_half                  = ($hours_worked/2 + ($break_orig/60)) * 60;
                
                $total_hours_worked                 = $this->total_hours_worked($time_out, $time_in);
                $breakz                             = $break;

                if(!$is_work){

                    if($hours_worked_half > $total_hours_worked){
                        if(($lunch_out < $lunch_in) && ($lunch_out != Null  &&  $lunch_in != Null)){}
                        else {
                            $breakz                 = 0;
                        }
                    }
                    if($reg_sched){

                        if($migrate_v3){
                            // lunch break
                            $enable_lunch           = $is_work1->enable_lunch_break;
                            $track_break_1          = $is_work1->track_break_1;
                            $break_type_1           = $is_work1->break_type_1;
                            $break_schedule_1       = $is_work1->break_schedule_1;
                            $break_started_after    = $is_work1->break_started_after;
                            $break_in_min           = $reg_sched->break_in_min;
                            $current_total_hours    = $reg_sched->total_work_hours;

                            // add break
                            $enable_add_breaks      = $is_work1->enable_additional_breaks;
                            $num_of_add_breaks      = $is_work1->num_of_additional_breaks;
                            $track_break_2          = $is_work1->track_break_2;
                            $break_type_2           = $is_work1->break_type_2;
                            $break_schedule_2       = $is_work1->break_schedule_2;
                            $add_break_s_after_1    = $is_work1->additional_break_started_after_1;
                            $add_break_s_after_2    = $is_work1->additional_break_started_after_2;
                            $break_1                = $reg_sched->break_1;
                            $break_2                = $reg_sched->break_2;

                            // tardiness rule
                            $tardiness_rule         = $is_work1->tardiness_rule;
                        }

                        $row                        = $reg_sched;

                        if($row){

                            $threshold              = $row->latest_time_in_allowed;
                            $threshold_ded          = 0;
                            $end_time_sched         = $gDate." ".$row->work_end_time;
                            $start_time_sched       = $gDate." ".$row->work_start_time;
                            
                            if($row->work_start_time > $row->work_end_time){
                                $gDatenxt           = date("Y-m-d",strtotime($gDate. " +1 day"));
                                $end_time_sched     = $gDatenxt." ".$row->work_end_time;
                            }
                            // TIMEIN HERE FOR LATE_MIN ->> 
                            $current_date_o                                                 = $time_out;
                            $current_date_t                                                 = $time_in;
                            $current_date_str_b                                             = strtotime($current_date_t);
                            $current_regular_start_date_time                                = $start_time_sched;
                            $current_regular_start_date_time_str_b                          = strtotime($current_regular_start_date_time);

                            // here start x

                            $hours_worked_half_real                                         = $hours_worked_half-$break_orig;
                            $current_regular_start_date_time_half                           = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time." +".$hours_worked_half_real."minutes"));
                            $current_regular_start_date_time_half_end                       = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time." +".$hours_worked_half."minutes"));

                            $current_tresh                                                  = 0;

                            if($threshold){
                                if($threshold > 0){
                                    
                                    $current_regular_start_date_timex1                      = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time." +".$threshold."minutes"));
                                    if(strtotime($current_date_t) <= strtotime($current_regular_start_date_time_half)){
                                        if(strtotime($current_date_t) >= strtotime($current_regular_start_date_timex1)){
                                            $current_tresh = $threshold;
                                        }
                                        else if(strtotime($current_date_t) > strtotime($current_regular_start_date_time) && strtotime($current_date_t) < strtotime($current_regular_start_date_timex1)){
                                            $current_tresh = (strtotime($current_date_t) - strtotime($current_regular_start_date_time))/60;
                                        }
                                    }
                                }

                            }
                            
                            // CHECK IF TIME IN IS GREATER THAN THE ACTUAL REQUIRED TIME
                            if($current_date_str_b > $current_regular_start_date_time_str_b){

                                // CHECK IF TRESHOLD EXIST
                                if($current_tresh > 0){

                                    $current_regular_start_date_time                        = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time." +".$current_tresh."minutes"));
                                    $current_regular_start_date_time_str_b                  = strtotime($current_regular_start_date_time);
                                    
                                    // CHECK IF TIME IN IS GREATER THAN THE ACTUAL REQUIRED TIME PLUS THE THRESHOLD

                                    if($current_date_str_b > $current_regular_start_date_time_str_b){
                                        $late_min                                           = $this->total_hours_worked($current_date_t,$current_regular_start_date_time);
                                    }
                                }
                                else{
                                    $late_min                                               = $this->total_hours_worked($current_date_t,$current_regular_start_date_time);

                                    if(strtotime($current_date_t) > strtotime($current_regular_start_date_time_half) && strtotime($current_date_t) < strtotime($current_regular_start_date_time_half_end)){
                                        $late_min = $late_min - ((strtotime($current_date_t)-strtotime($current_regular_start_date_time_half))/60);
                                    }
                                    else if(strtotime($current_date_t) >= strtotime($current_regular_start_date_time_half_end)){
                                        $late_min = $late_min - $break_orig;
                                    }
                                }

                            }
                            // here end x
                            $tardiness                      = $late_min + $overbreak_min;

                            if($migrate_v3){
                                // break injections
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

                                $flag_deduct_break                                          = false;
                                $late_min                                                   = (($current_total_hours * 60) < $late_min) ? ($current_total_hours * 60) : $late_min;
                                $tardiness_min                                              = $late_min;
                                
                                // lunch break
                                /*
                                $enable_lunch           = $is_work1->enable_lunch_break;
                                $track_break_1          = $is_work1->track_break_1;
                                $break_type_1           = $is_work1->break_type_1;
                                $break_schedule_1       = $is_work1->break_schedule_1;
                                $break_started_after    = $is_work1->break_started_after;
                                $break_in_min           = $reg_sched->break_in_min;
                                $lunch_out,$lunch_in

                                */
                                $overbreak_min                          = 0;
                                $total_hours_deduct_overall             = 0;
                                $current_date_nsd                       = 0;
                                $next_date_nsd                          = 0;

                                if($enable_lunch == "yes"){
                                    if($track_break_1 == "yes"){

                                        $total_hours_deduct             = 0;

                                        if($lunch_out != "" && $lunch_in != ""){

                                            if($break_schedule_1 == "fixed"){

                                                $break_started_after_min    = $break_started_after * 60;
                                                $break_started_after_min    = number_format($break_started_after_min,0);
                                                $lunch_out_start            =  date("Y-m-d H:i:00",strtotime($start_time_sched. " +".$break_started_after_min." minutes"));
                                                $lunch_out_end              =  date("Y-m-d H:i:00",strtotime($lunch_out_start. " +".$break_in_min." minutes"));

                                                // if lunch out coincide in the shift schedule
                                                if($lunch_out >= $lunch_out_start && $lunch_out < $lunch_out_end){

                                                    if($lunch_in > $lunch_out_end){
                                                        $overbreak_min  = $overbreak_min + ($this->total_hours_worked($lunch_in,$lunch_out_end));
                                                        $overbreak_min  = ($overbreak_min > 0) ? $overbreak_min : 0;
                                                    }

                                                    $lbreak_total_min  = $this->total_hours_worked($lunch_in,$lunch_out_start);
                                                    // if break is less than the break time then set the break to the break time
                                                    $lbreak_total_min   = ($lbreak_total_min > $overbreak_min) ? $lbreak_total_min : $overbreak_min;
                                                    $total_hours_deduct = $total_hours_deduct + $lbreak_total_min;

                                                }
                                                else{
                                                    $lunch_out          = Null;
                                                    $lunch_in           = Null;
                                                }
                                            }
                                            else{
                                                
                                                $total_break_min        = $this->total_hours_worked($lunch_in,$lunch_out);
                                                // if break is less than the break time then set the break to the break time
                                                $total_break_minx       = ($total_break_min > $overbreak_min) ? $total_break_min : $overbreak_min;
                                                $total_break_min        = ($total_break_min > 0) ? $total_break_min : 0;

                                                if($total_break_min > $break_in_min){
                                                    $overbreak_min      = $overbreak_min + ($total_break_min - $break_in_min);
                                                    $overbreak_min      = ($overbreak_min > 0) ? $overbreak_min : 0;
                                                }
                                                $total_break_minx       = ($total_break_minx < $break_in_min) ? $break_in_min : $total_break_minx;
                                                $total_hours_deduct     = $total_hours_deduct + $total_break_minx;
                                            }
                                        }
                                        else{
                                            $lunch_out  = Null;
                                            $lunch_in   = Null;
                                            $total_hours_deduct         = $break_in_min;
                                        }

                                        if($break_type_1 == "unpaid"){
                                            // kaltas sa total hours

                                            $total_hours_deduct_overall = $total_hours_deduct_overall + $total_hours_deduct;


                                            /// night diff rule here

                                            if($get_nigtdiff_rule){
                                                $epi_nsd                = in_array_custom("emp_id_{$emp_id}",$get_employee_payroll_information);
                                                
                                                if($epi_nsd){
                                                    $nsd_lunch          = false;
                                                    $epi_nsd_x          = $epi_nsd->nightshift_differential_rule_name;
                                                    if($epi_nsd_x == "time_bound"){
                                                        if($get_nigtdiff_rule->exclude_entitlement_nsd_lunch_break == "yes"){
                                                            $nsd_lunch      = true;
                                                        }
                                                    }else if($epi_nsd_x == "shift_restriction"){
                                                        if($get_nigtdiff_rule->exclude_entitlement_nsd_sr_lunch_break == "yes"){
                                                            $nsd_lunch      = true;
                                                        }
                                                    }
                                                    if($nsd_lunch){
                                                        $from_time              = $get_nigtdiff_rule->from_time;
                                                        $to_time                = $get_nigtdiff_rule->to_time;
                                                        $to_time_currentdate    = $currentdate;

                                                        if($from_time > $to_time){
                                                            $to_time_currentdate = date("Y-m-d",strtotime($currentdate ."+ 1 day"));
                                                        }

                                                        $from_time              = $currentdate." ".$from_time;
                                                        $to_time                = $to_time_currentdate." ".$to_time;

                                                        $total_n_diff           = 0;
                                                        if($lunch_out >= $from_time && $lunch_in < $to_time){
                                                            if(date("Y-m-d",strtotime($lunch_out)) == $currentdate){
                                                                $current_date_nsd   = $total_hours_deduct;
                                                            }else{
                                                                $next_date_nsd      = $total_hours_deduct;
                                                            }
                                                        }
                                                        else if($lunch_out <= $from_time && $lunch_in > $from_time){
                                                            $total_n_diff       = $this->total_hours_worked($lunch_in,$from_time);

                                                            if(date("Y-m-d",strtotime($lunch_out)) == $currentdate){
                                                                $current_date_nsd   = $total_n_diff;
                                                            }else{
                                                                $next_date_nsd      = $total_n_diff;
                                                            }
                                                        }
                                                        else if($lunch_out < $to_time && $lunch_in > $to_time){
                                                            $total_n_diff       = $this->total_hours_worked($to_time,$lunch_out);
                                                            if(date("Y-m-d",strtotime($lunch_out)) == $currentdate){
                                                                $current_date_nsd   = $total_n_diff;
                                                            }else{
                                                                $next_date_nsd      = $total_n_diff;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        else{
                                            // walay kaltas sa total hours
                                            $total_hours_deduct_overall = $total_hours_deduct_overall + 0;
                                        }
                                    }
                                    else{

                                        $lunch_out                      = Null;
                                        $lunch_in                       = Null;

                                        if($break_type_1 == "unpaid"){
                                            // kaltas sa total hours
                                            $total_hours_deduct_overall = $total_hours_deduct_overall + $break_in_min;
                                            // here // add totol hours bug
                                            $break_orig                 = 0;
                                        }
                                    }
                                }
                                else{

                                    $lunch_out                          = Null;
                                    $lunch_in                           = Null;
                                }
                            
                                /*
                                // add break
                                $enable_add_breaks      = $is_work1->enable_additional_breaks;
                                $num_of_add_breaks      = $is_work1->num_of_additional_breaks;
                                $track_break_2          = $is_work1->track_break_2;
                                $break_type_2           = $is_work1->break_type_2;
                                $break_schedule_2       = $is_work1->break_schedule_2;
                                $add_break_s_after_1    = $is_work1->additional_break_started_after_1;
                                $add_break_s_after_2    = $is_work1->additional_break_started_after_2;
                                $break_1                = $is_work1->break_1;
                                $break_2                = $is_work1->break_2;
                                $fbreak_out,$fbreak_in,$sbreak_out,$sbreak_in
                                */

                                if($enable_add_breaks == "yes"){
                                    if($track_break_2 == "yes"){
                                        $break_1                        = number_format($break_1,0);
                                        $break_2                        = number_format($break_2,0);
                                        $total_hours_deduct             = 0;

                                        // first break here
                                        if($fbreak_out != "" && $fbreak_in != ""){

                                            if($break_schedule_2 == "fixed"){

                                                $break_started_after_min= $add_break_s_after_1 * 60;
                                                $break_started_after_min= number_format($break_started_after_min,0);
                                                $fbreak_out_start       =  date("Y-m-d H:i:00",strtotime($start_time_sched. " +".$break_started_after_min." minutes"));
                                                $fbreak_out_end         =  date("Y-m-d H:i:00",strtotime($fbreak_out_start. " +".$break_1." minutes"));
                                                
                                                // if lunch out coincide in the shift schedule
                                                if($fbreak_out >= $fbreak_out_start && $fbreak_out < $fbreak_out_end){
                                                    
                                                    if($fbreak_in > $fbreak_out_end){
                                                        $overbreak_min  = $overbreak_min + ($this->total_hours_worked($fbreak_in,$fbreak_out_end));
                                                        $overbreak_min  = ($overbreak_min > 0) ? $overbreak_min : 0;
                                                    }

                                                    $break_1_total_min  = $this->total_hours_worked($fbreak_in,$fbreak_out_start);
                                                    // if break is less than the break time then set the break to the break time
                                                    $break_1_total_min  = ($break_1_total_min > $break_1) ? $break_1_total_min : $break_1;
                                                    $total_hours_deduct = $total_hours_deduct + $break_1_total_min;

                                                }
                                                else{
                                                    $fbreak_out         = Null;
                                                    $fbreak_in          = Null;
                                                }
                                            }
                                            else{
                                                $total_break_min        = $this->total_hours_worked($fbreak_in,$fbreak_out);
                                                // if break is less than the break time then set the break to the break time
                                                $total_break_minx       = ($total_break_min > $break_1) ? $total_break_min : $break_1;
                                                $total_break_min        = ($total_break_min > 0) ? $total_break_min : 0;

                                                if($total_break_min > $break_1){
                                                    $overbreak_min      = $overbreak_min + ($total_break_min - $break_1);
                                                    $overbreak_min      = ($overbreak_min > 0) ? $overbreak_min : 0;
                                                }

                                                $total_hours_deduct     = $total_hours_deduct + $total_break_minx;
                                            }
                                        }
                                        else{

                                            $fbreak_out                 = Null;
                                            $fbreak_in                  = Null;
                                        }


                                        if($num_of_add_breaks > 1){

                                            // second break here
                                            if($sbreak_out != "" && $sbreak_in != ""){

                                                if($break_schedule_2 == "fixed"){

                                                    $break_started_after_min= $add_break_s_after_2 * 60;
                                                    $break_started_after_min= number_format($break_started_after_min,0);
                                                    $sbreak_out_start       =  date("Y-m-d H:i:00",strtotime($start_time_sched. " +".$break_started_after_min." minutes"));
                                                    $sbreak_out_end         =  date("Y-m-d H:i:00",strtotime($sbreak_out_start. " +".$break_2." minutes"));

                                                    // if lunch out coincide in the shift schedule
                                                    if($sbreak_out >= $sbreak_out_start && $sbreak_out < $sbreak_out_end){

                                                        if($sbreak_in > $sbreak_out_end){
                                                            $overbreak_min  = $overbreak_min + ($this->total_hours_worked($sbreak_in,$sbreak_out_end));
                                                            $overbreak_min  = ($overbreak_min > 0) ? $overbreak_min : 0;
                                                        }

                                                        $break_2_total_min  = $this->total_hours_worked($sbreak_in,$sbreak_out_start);
                                                        // if break is less than the break time then set the break to the break time
                                                        $break_2_total_min  = ($break_2_total_min > $break_2) ? $break_2_total_min : $break_2;
                                                        $total_hours_deduct = $total_hours_deduct + $break_2_total_min;

                                                    }
                                                    else{
                                                        $sbreak_out         = Null;
                                                        $sbreak_in          = Null;
                                                    }
                                                }
                                                else{
                                                    $total_break_min        = $this->total_hours_worked($sbreak_in,$sbreak_out);
                                                    // if break is less than the break time then set the break to the break time
                                                    $total_break_minx       = ($total_break_min > $break_2) ? $total_break_min : $break_2;
                                                    $total_break_min        = ($total_break_min > 0) ? $total_break_min : 0;

                                                    if($total_break_min > $break_2){
                                                        $overbreak_min      = $overbreak_min + ($total_break_min - $break_2);
                                                        $overbreak_min      = ($overbreak_min > 0) ? $overbreak_min : 0;
                                                    }

                                                    $total_hours_deduct     = $total_hours_deduct + $total_break_minx;
                                                }
                                            }
                                            else{

                                                $sbreak_out                 = Null;
                                                $sbreak_in                  = Null;
                                            }
                                        }

                                        if($break_type_2 == "unpaid"){
                                            // kaltas sa total hours
                                            
                                            $total_hours_deduct_overall     = $total_hours_deduct_overall + $total_hours_deduct;
                                            
                                        }
                                        else{
                                            // walay kaltas sa total hours
                                            $total_hours_deduct_overall     = $total_hours_deduct_overall + 0;
                                        }

                                    }else{
                                        $fbreak_out                         = Null;
                                        $fbreak_in                          = Null;
                                        $sbreak_out                         = Null;
                                        $sbreak_in                          = Null;

                                        if($break_type_2 == "unpaid"){
                                            // kaltas sa total hours
                                            $total_hours_deduct_x           = $break_1 + $break_2;
                                            $total_hours_deduct_overall     = $total_hours_deduct_overall + $total_hours_deduct_x;
                                        }
                                    }
                                }
                                else{
                                    $fbreak_out                             = Null;
                                    $fbreak_in                              = Null;
                                    $sbreak_out                             = Null;
                                    $sbreak_in                              = Null;
                                }
                                // tardines_min 

                                $tardiness_min                              = $tardiness_min + $overbreak_min;

                                // end time
                                $current_regular_end_date_time              = $end_time_sched;

                                // TO GET UNDERTIME
                                $undertime_min                              = 0;
                                // CHECK IF TIME IN IS GREATER THAN THE ACTUAL REQUIRED TIME

                                // last timein
                                // init end time
                                $current_reg_end_date_time                  = date("Y-m-d H:i:00",strtotime($current_regular_end_date_time));
                                // last timein str
                                $last_time_in_str                           = strtotime($time_in);
                                $current_regular_start_date_time_str_b      = strtotime($current_regular_start_date_time);
                                
                                // adjust here
                                $start_time_sched_str_b                     = strtotime($start_time_sched);

                                if($last_time_in_str > $start_time_sched_str_b){
                                    // CHECK IF TRESHOLD EXIST
                                    if($current_tresh > 0){

                                        // start time with tresh
                                        $current_reg_start_date_time        = date("Y-m-d H:i:00",strtotime($start_time_sched." +".$current_tresh."minutes"));
                                        $current_reg_start_date_time_str_b  = strtotime($current_reg_start_date_time);

                                        // end time with tresh
                                        $current_reg_end_date_time          = date("Y-m-d H:i:00",strtotime($current_regular_end_date_time." +".$current_tresh."minutes"));
                                        
                                        // CHECK IF TIME IN IS LESS THAN THE ACTUAL REQUIRED TIME PLUS THE THRESHOLD
                                        
                                        if($last_time_in_str < $current_reg_start_date_time_str_b){

                                            // GET THE DIFFERENCE BETWEEN THE ORIGINAL START TIME AND THE TIME IN TO GET THE THRESHOLD USED
                                            $current_timein_tresh           = $this->total_hours_worked($time_in,$start_time_sched);
                                            $current_reg_end_date_time      = date("Y-m-d H:i:00",strtotime($current_regular_end_date_time." +".$current_timein_tresh."minutes"));
                                        }
                                    }
                                }

                                $current_reg_end_date_time_str_b            = strtotime($current_reg_end_date_time);
                                $current_date_str_o_b                       = strtotime($time_out);

                                // CHECK IF THE WORKSCHED END TIME IS GREATER THAN THE TIME OUT TIME
                                if($current_reg_end_date_time_str_b > $current_date_str_o_b){

                                    // GET UNDERTIME MIN
                                    $undertime_min                          = $this->total_hours_worked($current_reg_end_date_time,$time_out);
                                    $undertime_min                          = ($undertime_min > 0) ? $undertime_min : 0;
                                }

                                $total_hours_required                       = $this->total_hours_worked($time_out,$time_in);
                                $total_hours_worked                         = $this->convert_to_hours($total_hours_required);
                                $half                                       = (($hours_worked * 60)/2);
                                $median_th                                  = $half + $total_hours_deduct_overall;
                                $total_hours_requiredx                      = $total_hours_required;
                                $total_hours_requiredx                      = $total_hours_required;

                                    
                                if($late_min < $half){
                                    $total_hours_requiredx                  = $total_hours_requiredx + $late_min;
                                }

                                if($total_hours_requiredx >= $hours_worked_half_real ){

                                    $total_hours_requiredv3                 = $total_hours_required;

                                    // if halfday
                                    if($threshold){
                                        if($current_tresh > 0){
                                            $current_regular_start_date_time_half = date("Y-m-d H:i:s",strtotime($current_regular_start_date_time_half ." +".$current_tresh." minutes"));
                                            $current_regular_start_date_time_half_end = date("Y-m-d H:i:s",strtotime($current_regular_start_date_time_half_end ." +".$current_tresh." minutes"));
                                        }
                                    }

                                    if(strtotime($current_date_o) > strtotime($current_regular_start_date_time_half) && strtotime($current_date_o) < strtotime($current_regular_start_date_time_half_end)){
                                        
                                        $total_hours_requiredv3 = $total_hours_requiredv3 - ((strtotime($current_date_o)-strtotime($current_regular_start_date_time_half))/60);
                                        $undertime_min          = $undertime_min - ((strtotime($current_date_o)-strtotime($current_regular_start_date_time_half)));
                                        
                                    }
                                    else if(strtotime($current_date_o) < strtotime($current_regular_start_date_time_half)){
                                        $total_hours_requiredv3 = $total_hours_requiredv3 - $break_orig;
                                        
                                    }
                                    if(strtotime($current_date_o) <= strtotime($current_regular_start_date_time_half)){
                                        $undertime_min          = $undertime_min - $break_orig;
                                    }

                                    if(strtotime($current_date_o) >= strtotime($current_regular_start_date_time_half_end)){
                                        $total_hours_requiredv3 = $total_hours_requiredv3 - $total_hours_deduct_overall;
                                    }
                                }
                                else{
                                    $total_break_undertime                  = $median_th - $total_hours_requiredx;

                                    if($total_break_undertime > $total_hours_deduct_overall){
                                        $total_break_undertime              = $total_break_undertime - ($total_break_undertime - $total_hours_deduct_overall);
                                    }
                                    $total_break_undertime                  = ($total_break_undertime > 0) ? $total_break_undertime : 0;

                                    $total_hours_requiredv3                 = $total_hours_required;


                                    $total_hours_deduct_overall_u           = $total_break_undertime;

                                    $total_hours_deduct_overall_u           = ($total_hours_deduct_overall_u > 0) ? $total_hours_deduct_overall_u : 0;
                                    
                                    $undertime_min                          = $undertime_min - $total_hours_deduct_overall_u;

                                    //$undertime_min                            = $undertime_min + $total_hours_deduct_overall_u; -- still dont know what is this
                                    if($total_break_undertime < $total_hours_deduct_overall){
                                        $undertime_min                      = $undertime_min + $total_break_undertime;
                                    }

                                    $undertime_min                          = ($undertime_min > 0) ? $undertime_min : 0;

                                    if($late_min >= $half && $late_min <= $median_th){
                                        $tardiness_min                      = $tardiness_min - $late_min;
                                        $tardiness_min                      = $tardiness_min + $late_min;
                                    }
                                    else if($late_min > $median_th){
                                        $tardiness_min                      = $tardiness_min - $late_min;           
                                        $tardiness_min                      = $tardiness_min + $late_min;
                                        $flag_deduct_break                  = true;
                                    }
                                    
                                }

                                // new undertime get
                                $undertime_min                          = 0;
                                $less_thresh                            = 0;

                                $latest_work_start_timex                = $currentdate." ".$reg_sched->work_start_time;
                                $currentdate_end                        = $currentdate;
                                
                                if($reg_sched->work_start_time > $reg_sched->work_end_time){
                                    $currentdate_end                    = date("Y-m-d",strtotime($currentdate. "+ 1 day"));
                                }

                                $latest_work_end_time                   = $currentdate_end." ".$reg_sched->work_end_time;
                                
                                $halfday_hour                           = ($reg_sched->total_work_hours/2)*60;
                                $halfday_min_time                       = date("Y-m-d H:i:s",strtotime($latest_work_start_timex." +".$halfday_hour." minutes"));
                                $halfday_max_time                       = $halfday_min_time;

                                if($reg_sched->break_in_min > 0){
                                    $halfday_max_time                   = date("Y-m-d H:i:s",strtotime($halfday_max_time." +".$reg_sched->break_in_min." minutes"));
                                }

                                if($time_in > $latest_work_start_timex){
                                    if($is_work1->enable_shift_threshold == "yes"){
                                        $tresh                          = $reg_sched->latest_time_in_allowed;
                                        $less_thresh                    = $tresh;
                                        
                                        
                                        $latest_work_start_timex        = date("Y-m-d H:i:s",strtotime($latest_work_start_timex."+".$tresh." minutes"));

                                        if($latest_work_start_timex > $time_in){
                                            $less_thresh                = (strtotime($latest_work_start_timex) - strtotime($time_in))/60;
                                            $less_thresh                = $tresh - $less_thresh;
                                        }
                                        $less_thresh                    = $current_tresh;
                                    }
                                    $latest_work_end_time               = date("Y-m-d H:i:s",strtotime($latest_work_end_time." +".$less_thresh." minutes"));
                                }

                                // check under time
                                if($time_out < $latest_work_end_time){
                                    $undertime = (strtotime($latest_work_end_time) - strtotime($time_out))/60;
                                }

                                $total_hours_worked                     = (strtotime($time_out) - strtotime($time_in))/60;


                                // if time in between halfday time or greater than the half max time
                                $half_latex                             = 0;
                                if($time_in >= $halfday_min_time){

                                    if($time_in > $halfday_max_time){
                                        $half_latex                     = $reg_sched->break_in_min;
                                    }
                                    else{
                                        if($halfday_min_time != $halfday_max_time){
                                            $half_latex                 = (strtotime($halfday_max_time) - strtotime($time_in)) / 60;
                                        }
                                    }
                                }

                                // if time out between halfday time or less than the half min time
                                $half_latey                             = 0;
                                if($time_out <= $halfday_max_time){

                                    if($time_out <= $halfday_min_time){
                                        $half_latey                     = $reg_sched->break_in_min;
                                        $partial_log_ded_break          = "yes";
                                    }
                                    else{
                                        if($halfday_min_time != $halfday_max_time){
                                            $half_latey                 = (strtotime($halfday_max_time) - strtotime($time_out)) / 60;
                                            $half_latex                 = (strtotime($time_out) - strtotime($halfday_min_time)) / 60;
                                            $partial_log_ded_break      = "yes";
                                        }
                                    }
                                }else if($time_out > $halfday_max_time && $time_in < $halfday_min_time){
                                    $half_latex                         = $reg_sched->break_in_min;
                                }
                                $undertime_min                              = $undertime - $half_latey;
                                // end here
                                
                
                                $total_hours_workedv3                       = $this->convert_to_hours($total_hours_requiredv3);
                                $undertime                                  = $undertime_min;
                                $tardiness                                  = $tardiness_min;

                                // if halfday
                                if($time_out <= $halfday_max_time){
                                    if($threshold){
                                        if($current_tresh > 0){
                                            $undertime = $undertime - $current_tresh;
                                        }
                                    }
                                }

                                // end of migrate
                            }
                            else{}
                        }
                    }
                }

                // check employee on leave
                $ileave                                 = 'no';
                $onleave                                = $this->check_leave_appliction($currentdate,$emp_id,$comp_id,$get_employee_leave_application,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_work_schedule,$get_all_regular_schedule,$get_work_schedule_flex,$get_all_schedule_blocks);

                if($onleave){

                    $ileave                             = 'yes';
                }
                
                $total_hours_worked                 = $total_hours_workedv3;

                /***total hours ***/
                
                /*** BARACK NEW TOTAL HOURS WORKED  For Assumed***/
                
                if($is_work && !$migrate_v3){}

                /*** end BARACK NEW TOTAL HOURS WORKED  ***/
                
                // enable capture from shift

                $late_min   = ($late_min  > 0) ? $late_min : 0;
                $tardiness  = ($tardiness > 0) ? $tardiness : 0;
                $undertime  = ($undertime > 0) ? $undertime : 0;

                if($current_date_nsd > 0){
                    $current_date_nsd = ($current_date_nsd <= $break_in_min) ? $break_in_min : $current_date_nsd;
                }
                if($next_date_nsd > 0){
                    $next_date_nsd = ($next_date_nsd <= $break_in_min) ? $break_in_min : $next_date_nsd;
                }

                
                if($time_query_row){

                    if($source == "updated" || $source == "recalculated" || $source == "import"){

                        if($time_query_row->flag_regular_or_excess == "excess"){

                            $get_total_hours    = (strtotime($time_out) - strtotime($time_in)) / 3600;
                            $get_total_hours    = ($get_total_hours < 0) ? 0 : $get_total_hours;
                            $tq_update_field    = array(
                                                "source"                    => 'dashboard',
                                                "emp_id"                    => $emp_id,
                                                "date"                      => $currentdate,
                                                "time_in"                   => $time_in,
                                                "time_out"                  => $time_out,
                                                "undertime_min"             => 0,
                                                "tardiness_min"             => 0,
                                                "late_min"                  => 0,
                                                "overbreak_min"             => 0,
                                                "absent_min"                => 0,
                                                "work_schedule_id"          => "-2",
                                                "total_hours"               => $get_total_hours,
                                                "total_hours_required"      => $get_total_hours,
                                                "change_log_date_filed"     => date('Y-m-d H:i:s'),
                                                "flag_regular_or_excess"    => "excess",
                                                );

                            // added for coaching
                            if($inclusion_hours == "1"){
                                $tq_update_field["inclusion_hours"]         = $inclusion_hours;
                            }

                            // added partial log ded break
                            if($flag_deduct_break){
                                $tq_update_field["partial_log_ded_break"]   = "yes";
                            }
                            else{
                                $tq_update_field["partial_log_ded_break"]   = "no";
                            }
                        }
                        else{

                            if($log_error){
                                $tq_update_field = array(
                                                "time_in"                   => $time_in,
                                                "lunch_out"                 => $lunch_out,
                                                "lunch_in"                  => $lunch_in,
                                                "time_out"                  => $time_out,
                                                "date"                      => $currentdate,
                                                "work_schedule_id"          => $work_schedule_id,
                                                "undertime_min"             => 0,
                                                "tardiness_min"             => 0,
                                                "total_hours"               => $hours_worked,
                                                "total_hours_required"      => 0,
                                                "absent_min"                => 0,
                                                "overbreak_min"             => 0,
                                                "change_log_date_filed"     => date('Y-m-d H:i:s'),
                                                "late_min"                  => $late_min
                                                );
                            }
                            else{

                                $tq_update_field = array(
                                                "time_in"                   => $time_in,
                                                "lunch_out"                 => $lunch_out,
                                                "lunch_in"                  => $lunch_in,
                                                "break1_out"                => $fbreak_out,
                                                "break1_in"                 => $fbreak_in,
                                                "break2_out"                => $sbreak_out,
                                                "break2_in"                 => $sbreak_in,
                                                "time_out"                  => $time_out,
                                                "date"                      => $currentdate,
                                                "work_schedule_id"          => $work_schedule_id,
                                                "undertime_min"             => $undertime,
                                                "tardiness_min"             => $tardiness ,
                                                "total_hours"               => $hours_worked,
                                                "late_min"                  => $late_min,
                                                "overbreak_min"             => $overbreak_min,
                                                "absent_min"                => 0,
                                                "total_hours_required"      => $total_hours_worked,
                                                "current_date_nsd"          => $current_date_nsd,
                                                "next_date_nsd"             => $next_date_nsd,
                                                "flag_on_leave"             => $ileave
                                                );

                                // added for coaching
                                if($inclusion_hours == "1"){
                                    $tq_update_field["inclusion_hours"]     = $inclusion_hours;
                                }

                                // added partial log ded break
                                if($flag_deduct_break){
                                    $tq_update_field["partial_log_ded_break"]   = "yes";
                                }
                                else{
                                    $tq_update_field["partial_log_ded_break"]   = "no";
                                }

                                if(!$is_work){

                                    if($tardiness_absent){
                                        $tq_update_field["tardiness_min"]   = $tardiness - $breakz;
                                        $tq_update_field["absent_min"]      = 0;
                                        $tq_update_field["late_min"]        = $tardiness - $breakz;
                                    }
                                    elseif($undertime_absent){

                                        $tq_update_field["absent_min"]      = 0;
                                        $tq_update_field["undertime_min"]   =  $undertime - $breakz; 
                                    }
                                    else{

                                        $tq_update_field["absent_min"]      = 0;
                                    }
                                }
                            }
                            // if source is from cronjob dont change last source
                            if(!$cronjob_time_recalculate){
                                if($time_query_row->source!=""){
                                    $tq_update_field['last_source'] = $source;
                                    $tq_update_field['change_log_date_filed'] = date('Y-m-d H:i:s');
                                }
                            }
                        }

                        // this is for rd ra
                        if($rd_ra == "yes"){
                            $tq_update_field['rest_day_r_a']                = "yes";
                            
                            // if need approval
                            if($rest_day_settings == "yes"){
                                if($time_query_row->time_in_status != 'approved'){
                                    $tq_update_field['flag_rd_include']         = "no";
                                }else{
                                    $tq_update_field['flag_rd_include']         = "yes";
                                }
                            }
                            else{
                                $tq_update_field['flag_rd_include']         = "yes";
                            }
                        }
                        else{
                            $tq_update_field['rest_day_r_a']                = "no";

                            // if need approval
                            if($rest_day_settings == "yes"){
                                $tq_update_field['flag_rd_include']         = "yes";
                            }
                        }

                        // if holiday needs approval based on settings

                        if($holiday_orig){ 

                            if($holiday_settings_appr == "yes"){
                                $tq_update_field['holiday_approve']         = "yes";
                                $tq_update_field['flag_holiday_include']    = "no";
                            }
                            else{
                                $tq_update_field['holiday_approve']         = "no";
                                $tq_update_field['flag_holiday_include']    = "yes";
                            }
                        }else{
                            $tq_update_field['holiday_approve']             = "no";
                            $tq_update_field['flag_holiday_include']        = "yes";
                        }


                        // insert if lunch deducted on holiday
                        // $time_keep_holiday 
                        // break_in_min_h           = $reg_sched->break_in_min;
                        // $start_time_sched_h      = $gDate." ".$reg_sched->work_start_time;
                        // $hours_worked_h          = $reg_sched->total_work_hours;
                        // $hours_worked_half       = ($hours_worked_h/2 + ($break_in_min_h/60)) * 60;
                        // if($lunch_out != "" && $lunch_in != ""){

                        $check_if_worksched_holiday     = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);
                        if($check_if_worksched_holiday){
                            $time_keep_holiday          = ($check_if_worksched_holiday->enable_breaks_on_holiday == 'yes') ? true : false;
                        }

                        if($time_keep_holiday){
                            $break_in_min_h             = 0;
                            $start_time_sched_h         = "";
                            $hours_worked_h             = 0;
                            $hours_worked_half          = 0;

                            if($reg_sched){
                                $break_in_min_h         = $reg_sched->break_in_min;
                                $start_time_sched_h     = $gDate." ".$reg_sched->work_start_time;
                                $hours_worked_h         = $reg_sched->total_work_hours;
                                $hours_worked_half      = ($hours_worked_h/2) * 60;
                            }

                            if($break_in_min_h > 0){
                                if(($lunch_out != "" || $lunch_out != NULL) && ($lunch_in != "" || $lunch_in != NULL)){
                                    $total_break_emp    = (strtotime($lunch_in) - strtotime($lunch_out))/ 60;
                                    $break_in_min_h     = ($break_in_min_h < $total_break_emp) ? $total_break_emp : $break_in_min_h;
                                    $lunch_out_h_d      = date("Y-m-d",strtotime($lunch_out));
                                    $lunch_in_h_d       = date("Y-m-d",strtotime($lunch_in));

                                    if($lunch_out_h_d == $gDate && $lunch_in_h_d == $gDate){
                                        $tq_update_field['current_date_holiday']    = $break_in_min_h;
                                        $tq_update_field['next_date_holiday']       = 0;
                                    }
                                    else if($lunch_out_h_d == $gDate && $lunch_in_h_d != $gDate){
                                        $gDate_n_h          = date("Y-m-d",strtotime($gDate." + 1day"));
                                        $gDate_c_h          = date("Y-m-d 23:59:59",strtotime($gDate));
                                        $gDate_n_h          = date("Y-m-d H:i:00",strtotime($gDate_n_h));
                                        $break_in_min_h_b_c = (strtotime($gDate_c_h) - strtotime($lunch_out))/ 60;
                                        $break_in_min_h_b_n = (strtotime($lunch_in) - strtotime($gDate_n_h))/ 60;
                                        
                                        $tq_update_field['current_date_holiday']    = $break_in_min_h_b_c;
                                        $tq_update_field['next_date_holiday']       = $break_in_min_h_b_n;
                                    }
                                    else{
                                        $tq_update_field['current_date_holiday']    = 0;
                                        $tq_update_field['next_date_holiday']       = $break_in_min_h;
                                    }
                                }
                                else{

                                    $lunch_out_a        = date("Y-m-d H:i:00",strtotime($start_time_sched_h." +" .$hours_worked_half." minutes")); 
                                    $lunch_in_a         = date("Y-m-d H:i:00",strtotime($lunch_out_a." +" .$break_in_min_h." minutes")); 

                                    $lunch_out_h_d      = date("Y-m-d",strtotime($lunch_out_a));
                                    $lunch_in_h_d       = date("Y-m-d",strtotime($lunch_in_a));
                                    
                                    if($lunch_out_h_d == $gDate && $lunch_in_h_d == $gDate){
                                        $tq_update_field['current_date_holiday'] = $break_in_min_h;
                                    }
                                    else if($lunch_out_h_d == $gDate && $lunch_in_h_d != $gDate){
                                        $gDate_n_h          = date("Y-m-d",strtotime($gDate." + 1 day"));
                                        $gDate_c_h          = date("Y-m-d 23:59:59",strtotime($gDate));
                                        $gDate_n_h          = date("Y-m-d H:i:00",strtotime($gDate_n_h));
                                        $break_in_min_h_b_c = (strtotime($gDate_c_h) - strtotime($lunch_out_a))/ 60;
                                        $break_in_min_h_b_n = (strtotime($lunch_in_a) - strtotime($gDate_n_h))/ 60;

                                        $tq_update_field['current_date_holiday'] = $break_in_min_h_b_c;
                                        $tq_update_field['next_date_holiday'] = $break_in_min_h_b_n;
                                    }
                                    else{
                                        $tq_update_field['next_date_holiday'] = $break_in_min_h;
                                    }
                                }
                            }else{
                                $tq_update_field['current_date_holiday']    = 0;
                                $tq_update_field['next_date_holiday']       = 0;
                            }
                        }
                        // exclude pmax
                        // total hours should not exceeds the required hours
                        if($comp_id == "316"){
                            if($tq_update_field["total_hours_required"] > $tq_update_field["total_hours"]){
                                //$tq_update_field["total_hours_required"] = $tq_update_field["total_hours"];
                            }
                        }
                        $this->db->where($time_query_where);
                        $this->db->update('employee_time_in',$tq_update_field);

                        // athan helper
                        if($currentdate){
                            $date = $currentdate;
                            payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
                        }
                    }
                    else{

                        if($source == "recalculated"){
                            return false;
                        }

                        $get_total_hours                                    = (strtotime($time_out) - strtotime($time_in)) / 3600;
                        $get_total_hours                                    = ($get_total_hours < 0) ? 0 : $get_total_hours;
                        
                        $date_insert                                        = array(
                                                                            "source"                    => 'Dashboard',
                                                                            "comp_id"                   => $comp_id,
                                                                            "emp_id"                    => $emp_id,
                                                                            "date"                      => $currentdate,
                                                                            "time_in"                   => $time_in,
                                                                            "time_out"                  => $time_out,
                                                                            "undertime_min"             => 0,
                                                                            "tardiness_min"             => 0,
                                                                            "late_min"                  => 0,
                                                                            "overbreak_min"             => 0,
                                                                            "work_schedule_id"          => "-2",
                                                                            "total_hours"               => $get_total_hours,
                                                                            "total_hours_required"      => $get_total_hours,
                                                                            "flag_regular_or_excess"    => "excess",
                                                                            "current_date_nsd"          => $current_date_nsd,
                                                                            "next_date_nsd"             => $next_date_nsd,
                                                                            );

                        // this is for rd ra
                        if($rd_ra == "yes"){
                            $date_insert['rest_day_r_a']                    = "yes";
                            
                            // if need approval
                            if($rest_day_settings == "yes"){
                                if($time_query_row->time_in_status != 'approved'){
                                    $date_insert['flag_rd_include']         = "no";
                                }else{
                                    $date_insert['flag_rd_include']         = "yes";
                                }
                            }
                        }
                        else{
                            $date_insert['rest_day_r_a']                    = "no";

                            // if need approval
                            if($rest_day_settings == "yes"){
                                $date_insert['flag_rd_include']             = "yes";
                            }
                        }

                        // if holiday needs approval based on settings
                        if($holiday_orig){
                            if($holiday_settings_appr == "yes"){
                                $date_insert['holiday_approve']             = "yes";
                                $date_insert['flag_holiday_include']        = "no";
                            }else{
                                $date_insert['holiday_approve']             = "no";
                                $date_insert['flag_holiday_include']        = "yes";
                            }
                        }
                        else{
                            $date_insert['holiday_approve']                 = "no";
                            $date_insert['flag_holiday_include']            = "yes";
                        }

                        // insert if lunch deducted on holiday
                        // $time_keep_holiday 
                        // break_in_min_h           = $reg_sched->break_in_min;
                        // $start_time_sched_h      = $gDate." ".$reg_sched->work_start_time;
                        // $hours_worked_h          = $reg_sched->total_work_hours;
                        // $hours_worked_half       = ($hours_worked_h/2 + ($break_in_min_h/60)) * 60;
                        // if($lunch_out != "" && $lunch_in != ""){

                        $check_if_worksched_holiday     = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);
                        if($check_if_worksched_holiday){
                            $time_keep_holiday          = ($check_if_worksched_holiday->enable_breaks_on_holiday == 'yes') ? true : false;
                        }

                        if($time_keep_holiday){
                            $break_in_min_h             = 0;
                            $start_time_sched_h         = "";
                            $hours_worked_h             = 0;
                            $hours_worked_half          = 0;

                            if($reg_sched){
                                $break_in_min_h         = $reg_sched->break_in_min;
                                $start_time_sched_h     = $gDate." ".$reg_sched->work_start_time;
                                $hours_worked_h         = $reg_sched->total_work_hours;
                                $hours_worked_half      = ($hours_worked_h/2) * 60;
                            }

                            if($break_in_min_h > 0){
                                if(($lunch_out != "" || $lunch_out != NULL) && ($lunch_in != "" || $lunch_in != NULL)){
                                    $total_break_emp    = (strtotime($lunch_in) - strtotime($lunch_out))/ 60;
                                    $break_in_min_h     = ($break_in_min_h < $total_break_emp) ? $total_break_emp : $break_in_min_h;
                                    $lunch_out_h_d      = date("Y-m-d",strtotime($lunch_out));
                                    $lunch_in_h_d       = date("Y-m-d",strtotime($lunch_in));

                                    if($lunch_out_h_d == $gDate && $lunch_in_h_d == $gDate){
                                        $date_insert['current_date_holiday']    = $break_in_min_h;
                                        $date_insert['next_date_holiday']       = 0;
                                    }
                                    else if($lunch_out_h_d == $gDate && $lunch_in_h_d != $gDate){
                                        $gDate_n_h          = date("Y-m-d",strtotime($gDate." + 1day"));
                                        $gDate_c_h          = date("Y-m-d 23:59:59",strtotime($gDate));
                                        $gDate_n_h          = date("Y-m-d H:i:00",strtotime($gDate_n_h));
                                        $break_in_min_h_b_c = (strtotime($gDate_c_h) - strtotime($lunch_out))/ 60;
                                        $break_in_min_h_b_n = (strtotime($lunch_in) - strtotime($gDate_n_h))/ 60;

                                        $date_insert['current_date_holiday']    = $break_in_min_h_b_c;
                                        $date_insert['next_date_holiday']       = $break_in_min_h_b_n;
                                    }
                                    else{
                                        $date_insert['current_date_holiday']    = 0;
                                        $date_insert['next_date_holiday']       = $break_in_min_h;
                                    }
                                }
                                else{

                                    $lunch_out_a        = date("Y-m-d H:i:00",strtotime($start_time_sched_h." +" .$hours_worked_half." minutes")); 
                                    $lunch_in_a         = date("Y-m-d H:i:00",strtotime($lunch_out_a." +" .$break_in_min_h." minutes")); 
                                    
                                    $lunch_out_h_d      = date("Y-m-d",strtotime($lunch_out_a));
                                    $lunch_in_h_d       = date("Y-m-d",strtotime($lunch_in_a));

                                    if($lunch_out_h_d == $gDate && $lunch_in_h_d == $gDate){
                                        $date_insert['current_date_holiday']    = $break_in_min_h;
                                        $date_insert['next_date_holiday']       = 0;
                                    }
                                    else if($lunch_out_h_d == $gDate && $lunch_in_h_d != $gDate){
                                        $gDate_n_h          = date("Y-m-d",strtotime($gDate." + 1 day"));
                                        $gDate_c_h          = date("Y-m-d 23:59:59",strtotime($gDate));
                                        $gDate_n_h          = date("Y-m-d H:i:00",strtotime($gDate_n_h));
                                        $break_in_min_h_b_c = (strtotime($gDate_c_h) - strtotime($lunch_out_a))/ 60;
                                        $break_in_min_h_b_n = (strtotime($lunch_in_a) - strtotime($gDate_n_h))/ 60;

                                        $date_insert['current_date_holiday']    = $break_in_min_h_b_c;
                                        $date_insert['next_date_holiday']       = $break_in_min_h_b_n;
                                    }
                                    else{
                                        $date_insert['current_date_holiday']    = 0;
                                        $date_insert['next_date_holiday']       = $break_in_min_h;
                                    }
                                }
                            }
                            else{
                                $date_insert['current_date_holiday']    = 0;
                                $date_insert['next_date_holiday']       = 0;
                            }
                        }
                        

                        // added partial log ded break
                        if($flag_deduct_break){
                            $date_insert["partial_log_ded_break"]   = "yes";
                        }
                        else{
                            $date_insert["partial_log_ded_break"]   = "no";
                        }
                        

                        $add_logs = $this->db->insert('employee_time_in', $date_insert);


                        // athan helper
                        if($currentdate){
                            $date = $currentdate;
                            payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
                        }
                    }
                }
                else{

                    
                    if($source == "recalculated"){
                        return false;
                    }

                    if($log_error){

                        $date_insert                                        = array(
                                                                            "comp_id"                   => $comp_id,
                                                                            "emp_id"                    => $emp_id,
                                                                            "date"                      => $currentdate ,
                                                                            "source"                    => $source,
                                                                            "time_in"                   => $time_in,
                                                                            "lunch_out"                 => $lunch_out,
                                                                            "lunch_in"                  => $lunch_in,
                                                                            "time_out"                  => $time_out,
                                                                            "tardiness_min"             => 0,
                                                                            "undertime_min"             => 0,
                                                                            "late_min"                  => 0,
                                                                            "overbreak_min"             => 0,
                                                                            "total_hours"               => 0,
                                                                            "total_hours_required"      => 0
                                                                            );
                    }
                    else{

                        $date_insert                                        = array(
                                                                            "comp_id"                   => $comp_id,
                                                                            "emp_id"                    => $emp_id,
                                                                            "date"                      => $currentdate,
                                                                            "source"                    => $source,
                                                                            "time_in"                   => $time_in,
                                                                            "lunch_out"                 => $lunch_out,
                                                                            "lunch_in"                  => $lunch_in,

                                                                            "break1_out"                => $fbreak_out,
                                                                            "break1_in"                 => $fbreak_in,
                                                                            "break2_out"                => $sbreak_out,
                                                                            "break2_in"                 => $sbreak_in,

                                                                            "time_out"                  => $time_out,
                                                                            "work_schedule_id"          => $work_schedule_id,
                                                                            "undertime_min"             => $undertime,
                                                                            "tardiness_min"             => $tardiness,
                                                                            "late_min"                  => $late_min,
                                                                            "overbreak_min"             => $overbreak_min,
                                                                            "total_hours"               => $hours_worked ,
                                                                            "total_hours_required"      => $total_hours_worked,
                                                                            "current_date_nsd"          => $current_date_nsd,
                                                                            "next_date_nsd"             => $next_date_nsd,
                                                                            "flag_on_leave"             => $ileave
                                                                            );
                        
                        if(!$is_work){
                            if($tardiness_absent){

                                $date_insert["tardiness_min"]               = $tardiness - $breakz;
                                $date_insert["absent_min"]                  = 0;
                                $date_insert["late_min"]                    = $tardiness - $breakz;
                            }
                            elseif($undertime_absent){

                                $date_insert["undertime_min"]               = $undertime - $breakz; 
                                $date_insert["absent_min"]                  = 0;
                            }
                            else{

                                $date_insert["absent_min"]                  = 0;
                            }

                        }
                    }
                    
                    // this is for rd ra
                    if($rd_ra == "yes"){
                        $date_insert['rest_day_r_a']                    = "yes";
                        
                        // if need approval
                        if($rest_day_settings == "yes"){
                            //$date_insert['flag_rd_include']           = "no";
                        }
                    }
                    else{
                        $date_insert['rest_day_r_a']                    = "no";

                        // if need approval
                        if($rest_day_settings == "yes"){
                            $date_insert['flag_rd_include']             = "yes";
                        }
                    }

                    // if holiday needs approval based on settings
                    if($holiday_orig){
                        if($holiday_settings_appr == "yes"){
                            $date_insert['holiday_approve']             = "yes";
                            $date_insert['flag_holiday_include']        = "no";
                        }else{
                            $date_insert['holiday_approve']             = "no";
                            $date_insert['flag_holiday_include']        = "yes";
                        }
                    }
                    else{
                        $date_insert['holiday_approve']             = "no";
                        $date_insert['flag_holiday_include']        = "yes";
                    }

                    // insert if lunch deducted on holiday
                    // $time_keep_holiday 
                    // break_in_min_h           = $reg_sched->break_in_min;
                    // $start_time_sched_h      = $gDate." ".$reg_sched->work_start_time;
                    // $hours_worked_h          = $reg_sched->total_work_hours;
                    // $hours_worked_half       = ($hours_worked_h/2 + ($break_in_min_h/60)) * 60;
                    // if($lunch_out != "" && $lunch_in != ""){

                    $check_if_worksched_holiday     = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);
                    if($check_if_worksched_holiday){
                        $time_keep_holiday          = ($check_if_worksched_holiday->enable_breaks_on_holiday == 'yes') ? true : false;
                    }

                    if($time_keep_holiday){
                        $break_in_min_h             = 0;
                        $start_time_sched_h         = "";
                        $hours_worked_h             = 0;
                        $hours_worked_half          = 0;

                        if($reg_sched){
                            $break_in_min_h         = $reg_sched->break_in_min;
                            $start_time_sched_h     = $gDate." ".$reg_sched->work_start_time;
                            $hours_worked_h         = $reg_sched->total_work_hours;
                            $hours_worked_half      = ($hours_worked_h/2) * 60;
                        }

                        if($break_in_min_h > 0){
                            if(($lunch_out != "" || $lunch_out != NULL) && ($lunch_in != "" || $lunch_in != NULL)){
                                $total_break_emp    = (strtotime($lunch_in) - strtotime($lunch_out))/ 60;
                                $break_in_min_h     = ($break_in_min_h < $total_break_emp) ? $total_break_emp : $break_in_min_h;
                                $lunch_out_h_d      = date("Y-m-d",strtotime($lunch_out));
                                $lunch_in_h_d       = date("Y-m-d",strtotime($lunch_in));

                                if($lunch_out_h_d == $gDate && $lunch_in_h_d == $gDate){
                                    $date_insert['current_date_holiday']    = $break_in_min_h;
                                    $date_insert['next_date_holiday']       = 0;
                                }
                                else if($lunch_out_h_d == $gDate && $lunch_in_h_d != $gDate){
                                    $gDate_n_h          = date("Y-m-d",strtotime($gDate." + 1day"));
                                    $gDate_c_h          = date("Y-m-d 23:59:59",strtotime($gDate));
                                    $gDate_n_h          = date("Y-m-d H:i:00",strtotime($gDate_n_h));
                                    $break_in_min_h_b_c = (strtotime($gDate_c_h) - strtotime($lunch_out))/ 60;
                                    $break_in_min_h_b_n = (strtotime($lunch_in) - strtotime($gDate_n_h))/ 60;

                                    $date_insert['current_date_holiday']    = $break_in_min_h_b_c;
                                    $date_insert['next_date_holiday']       = $break_in_min_h_b_n;
                                }
                                else{
                                    $date_insert['current_date_holiday']    = 0;
                                    $date_insert['next_date_holiday']       = $break_in_min_h;
                                }
                            }
                            else{

                                $lunch_out_a        = date("Y-m-d H:i:00",strtotime($start_time_sched_h." +" .$hours_worked_half." minutes")); 
                                $lunch_in_a         = date("Y-m-d H:i:00",strtotime($lunch_out_a." +" .$break_in_min_h." minutes")); 
                                
                                $lunch_out_h_d      = date("Y-m-d",strtotime($lunch_out_a));
                                $lunch_in_h_d       = date("Y-m-d",strtotime($lunch_in_a));

                                if($lunch_out_h_d == $gDate && $lunch_in_h_d == $gDate){
                                    $date_insert['current_date_holiday']    = $break_in_min_h;
                                    $date_insert['next_date_holiday']       = 0;
                                }
                                else if($lunch_out_h_d == $gDate && $lunch_in_h_d != $gDate){
                                    $gDate_n_h          = date("Y-m-d",strtotime($gDate." + 1 day"));
                                    $gDate_c_h          = date("Y-m-d 23:59:59",strtotime($gDate));
                                    $gDate_n_h          = date("Y-m-d H:i:00",strtotime($gDate_n_h));
                                    $break_in_min_h_b_c = (strtotime($gDate_c_h) - strtotime($lunch_out_a))/ 60;
                                    $break_in_min_h_b_n = (strtotime($lunch_in_a) - strtotime($gDate_n_h))/ 60;

                                    $date_insert['current_date_holiday']    = $break_in_min_h_b_c;
                                    $date_insert['next_date_holiday']       = $break_in_min_h_b_n;
                                }
                                else{
                                    $date_insert['current_date_holiday']    = 0;
                                    $date_insert['next_date_holiday']       = $break_in_min_h;
                                }
                            }
                        }
                        else{
                            $date_insert['current_date_holiday']    = 0;
                            $date_insert['next_date_holiday']       = 0;
                        }
                    }

                    // added partial log ded break
                    if($flag_deduct_break){
                        $date_insert["partial_log_ded_break"]   = "yes";
                    }
                    else{
                        $date_insert["partial_log_ded_break"]   = "no";
                    }
                    
                    // exclude pmax
                    // total hours should not exceeds the required hours
                    if($comp_id == "316"){
                        if($date_insert["total_hours_required"] > $date_insert["total_hours"]){
                            //$date_insert["total_hours_required"] = $date_insert["total_hours"];
                        }
                    }
                    
                    $this->db->insert('employee_time_in', $date_insert);

                    // athan helper
                    if($currentdate){
                        $date = $currentdate;
                        payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
                    }
                }
            }
        }
        return TRUE;
    }

    public function import_add_logsv2($comp_id, $emp_id, $reason, $time_in,$lunch_out,$lunch_in,$fbreak_out,$fbreak_in,$sbreak_out,$sbreak_in,$time_out, $hours_worked, $work_schedule_id,$break=0,$split=array(),$log_error = false,$source ="",$emp_no = "",$gDate ="",$employee_time_in_id ="",$get_employee_payroll_information,$get_tardiness_settings,$company_holiday,$time_query_row,$time_query_where,$get_all_regular_schedule,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$attendance_hours,$get_work_schedule,$get_employee_leave_application,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_tardiness_settingsv2,$tardiness_rounding,$migrate_v3=true,$rd_ra="no",$holiday_orig=false,$holiday_settings_appr="no",$inclusion_hours = "0",$rest_day_settings="no",$cronjob_time_recalculate=false,$get_nigtdiff_rule=false,$get_shift_restriction_schedules=false){

        $total_hours_workedv3 = 0;
        $current_date_nsd     = 0;
        $next_date_nsd        = 0;
        $flag_deduct_break    = false;
        // if regular, nighshift and compress
        if($gDate){
            $currentdate                    = $gDate;
        }
        else{
            $currentdate                    = date('Y-m-d',strtotime($time_in));
        }

        if($lunch_out == $lunch_in){

            $lunch_out                      = NULL;
            $lunch_in                       = NULL;
        }
        if($fbreak_out == $fbreak_in){
            $fbreak_out                     = NULL;
            $fbreak_in                      = NULL; 
        }
        if($sbreak_out == $sbreak_in){
            $sbreak_out                     = NULL;
            $sbreak_in                      = NULL;
        }

        // store the orig break
        $break_orig                         = $break;
        // grace period

        $tardiness_set                      = $this->tardiness_settings($emp_id, $comp_id,$get_employee_payroll_information,$get_tardiness_settings);
        $grace_period                       = ($tardiness_set) ? $tardiness_set : 0;

        // holiday now
        $holiday                            = in_array_custom("holiday_{$currentdate}",$company_holiday);
        
        if($holiday){
            
            $check_if_worksched_holiday     = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);

            if($check_if_worksched_holiday){

                $time_keep_holiday          = ($check_if_worksched_holiday->enable_breaks_on_holiday == 'yes') ? true : false;

                if($time_keep_holiday){
                    $holiday                = false;
                }
            }
            if($holiday){
                $company_id                     = $comp_id;
                $new_time_in                    = $time_in;
                $new_time_out                   = $time_out;
                $h                              = $this->total_hours_worked($new_time_out,$new_time_in);
                $holday_hour                    = $this->convert_to_hours($h);
                
                if($time_query_row) {
                    if($log_error){
                        $date_insert        = array(
                                            "comp_id"               => $company_id,
                                            "emp_id"                => $emp_id,
                                            "date"                  => $currentdate ,
                                            "time_in"               => $new_time_in,
                                            "time_out"              => $new_time_out,
                                            "work_schedule_id"      => $work_schedule_id,
                                            "total_hours"           => 0,
                                            "total_hours_required"  => 0,
                                            "late_min"              => 0,
                                            "tardiness_min"         => 0,
                                            "undertime_min"         => 0,
                                            "overbreak_min"         => 0
                                            );
                    }else{
                        $tq_update_field    = array(
                                            "time_in"               => $new_time_in,
                                            "time_out"              => $new_time_out,
                                            "work_schedule_id"      => $work_schedule_id,
                                            "total_hours"           => $holday_hour,
                                            "total_hours_required"  => $holday_hour,
                                            "change_log_date_filed" => date('Y-m-d H:i:s'),
                                            "late_min"              => 0,
                                            "tardiness_min"         => 0,
                                            "undertime_min"         => 0,
                                            "overbreak_min"         => 0
                                            );
                    }
                    
                    if($time_query_row->source!=""){
                        $tq_update_field['last_source'] = $source;
                    }

                    $array_update['status']                             = "update";
                    $array_update['where']                              = $time_query_where;
                    $array_update['fields']                             = $tq_update_field;

                    return $array_update;
                }
                else{
                            
                    if($log_error){
                        $date_insert = array(
                                    "comp_id"               => $company_id,
                                    "emp_id"                => $emp_id,
                                    "date"                  => $currentdate ,
                                    "source"                => $source,
                                    "time_in"               => $new_time_in,
                                    "time_out"              => $new_time_out,
                                    "work_schedule_id"      => $work_schedule_id,
                                    "total_hours"           => 0 ,
                                    "total_hours_required"  => 0,
                                    "late_min"              => 0,
                                    "tardiness_min"         => 0,
                                    "undertime_min"         => 0,
                                    "absent_min"            => 0,
                                    "overbreak_min"         => 0
                                    );
                    }else{
                        $date_insert = array(
                                    "comp_id"               => $company_id,
                                    "emp_id"                => $emp_id,
                                    "date"                  => $currentdate ,
                                    "source"                => $source,
                                    "time_in"               => $new_time_in,
                                    "time_out"              => $new_time_out,
                                    "work_schedule_id"      => $work_schedule_id,
                                    "total_hours"           => $holday_hour ,
                                    "total_hours_required"  => $holday_hour,
                                    "late_min"              => 0,
                                    "tardiness_min"         => 0,
                                    "undertime_min"         => 0,
                                    "absent_min"            => 0,
                                    "overbreak_min"         => 0
                                    );
                    }

                    $array_update['status']                             = "add";
                    $array_update['where']                              = array();
                    $array_update['fields']                             = $date_insert;

                    return $array_update;
                    //$add_logs = $this->db->insert('employee_time_in', $date_insert);
                }
            }
        }

        $day                                = date('l',strtotime($currentdate));
        $reg_sched                          = in_array_custom("rsched_id_{$this->work_schedule_id}_{$day}",$get_all_regular_schedule);
        $undertime                          = 0;
        $absent_min                         = 0;
        $update_total_hours                 = 0;
        $tardiness                          = 0;

        if($reg_sched){

            $hours_worked                   = $reg_sched->total_work_hours;
            $w_start                        = $reg_sched->work_start_time;
            $w_end                          = $reg_sched->work_end_time;
            $tresh                          = $reg_sched->latest_time_in_allowed;
            $end_date                       = $currentdate;
            $start_date                     = $currentdate;

            if($w_start > $w_end){

                $end_date                   = date('Y-m-d',strtotime($end_date. " +1 day"));
                $end_date                   = $currentdate;
            }

            $start_date_time                = date("Y-m-d H:i:00", strtotime($start_date." ".$w_start));
            $end_date_time                  = date("Y-m-d H:i:00", strtotime($end_date." ".$w_end));
            $init_start_date_time           = $start_date_time;

            if($time_in > $start_date_time){

                if($tresh){

                    $start_date_time        = date('Y-m-d H:i:00',strtotime($init_start_date_time. " +".$tresh." minutes"));

                    if($time_in > $start_date_time){

                        $end_date_time      = date('Y-m-d H:i:00',strtotime($end_date. " +".$tresh." minutes"));
                    }
                    else{

                        $min_tresh          = $this->total_hours_worked($time_in,$init_start_date_time);
                        $start_date_time    = date('Y-m-d H:i:00',strtotime($init_start_date_time. " +".$min_tresh." minutes"));
                        $end_date_time      = date('Y-m-d H:i:00',strtotime($end_date. " +".$min_tresh." minutes"));
                    }
                }
            }
            // UNDERTIME
            
            if($end_date_time > $time_out){
                $undertime                  = ($this->total_hours_worked($time_out,$end_date_time) > 0) ? $this->total_hours_worked($time_out,$end_date_time) : 0;
            }
            // TARDINESS
            if($time_in > $start_date_time){
                $tardiness                  = ($this->total_hours_worked($time_in,$start_date_time) > 0) ? $this->total_hours_worked($time_in,$start_date_time) : 0;
            }
        }
    
        $half_day                           = 0;
        $late_min                           = 0;//$this->late_min($comp_id,$currentdate,$emp_id,$work_schedule_id,$time_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);
        $overbreak_min                      = $this->overbreak_min($comp_id,$currentdate,$emp_id,$work_schedule_id,$time_in,$lunch_out,$lunch_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in);
        
        $disable_absent                     = true;
        $tardiness_absent                   = false;
        $undertime_absent                   = false;
        $breakx                             = 0;
        
        // check break assumed
        $is_work                            = false;
        $is_work1                           = in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);

        if($is_work1){
            $break_rules                    = $is_work1->break_rules;
            if($break_rules == "assumed"){
                $is_work                    = $is_work1;
            }
        }

        if($migrate_v3){
            $is_work                        = false;
        }

        if($is_work){
        }
        else {
            $get_tardiness                  = $tardiness;
        }
        
        $hours_worked_half                  = ($hours_worked/2 + ($break_orig/60)) * 60;
        
        $total_hours_worked                 = $this->total_hours_worked($time_out, $time_in);
        $breakz                             = $break;

        if(!$is_work){

            if($hours_worked_half > $total_hours_worked){
                if(($lunch_out < $lunch_in) && ($lunch_out != Null  &&  $lunch_in != Null)){}
                else {
                    $breakz                 = 0;
                }
            }
            if($reg_sched){

                if($migrate_v3){
                    // lunch break
                    $enable_lunch           = $is_work1->enable_lunch_break;
                    $track_break_1          = $is_work1->track_break_1;
                    $break_type_1           = $is_work1->break_type_1;
                    $break_schedule_1       = $is_work1->break_schedule_1;
                    $break_started_after    = $is_work1->break_started_after;
                    $break_in_min           = $reg_sched->break_in_min;
                    $current_total_hours    = $reg_sched->total_work_hours;

                    // add break
                    $enable_add_breaks      = $is_work1->enable_additional_breaks;
                    $num_of_add_breaks      = $is_work1->num_of_additional_breaks;
                    $track_break_2          = $is_work1->track_break_2;
                    $break_type_2           = $is_work1->break_type_2;
                    $break_schedule_2       = $is_work1->break_schedule_2;
                    $add_break_s_after_1    = $is_work1->additional_break_started_after_1;
                    $add_break_s_after_2    = $is_work1->additional_break_started_after_2;
                    $break_1                = $reg_sched->break_1;
                    $break_2                = $reg_sched->break_2;

                    // tardiness rule
                    $tardiness_rule         = $is_work1->tardiness_rule;
                }

                $row                        = $reg_sched;

                if($row){

                    $threshold              = $row->latest_time_in_allowed;
                    $threshold_ded          = 0;
                    $end_time_sched         = $gDate." ".$row->work_end_time;
                    $start_time_sched       = $gDate." ".$row->work_start_time;
                    
                    if($row->work_start_time > $row->work_end_time){
                        $gDatenxt           = date("Y-m-d",strtotime($gDate. " +1 day"));
                        $end_time_sched     = $gDatenxt." ".$row->work_end_time;
                    }
                    // TIMEIN HERE FOR LATE_MIN ->> 
                    $current_date_o                                                 = $time_out;
                    $current_date_t                                                 = $time_in;
                    $current_date_str_b                                             = strtotime($current_date_t);
                    $current_regular_start_date_time                                = $start_time_sched;
                    $current_regular_start_date_time_str_b                          = strtotime($current_regular_start_date_time);

                    // here start x

                    $hours_worked_half_real                                         = $hours_worked_half-$break_orig;
                    $current_regular_start_date_time_half                           = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time." +".$hours_worked_half_real."minutes"));
                    $current_regular_start_date_time_half_end                       = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time." +".$hours_worked_half."minutes"));

                    $current_tresh                                                  = 0;

                    if($threshold){
                        if($threshold > 0){
                            
                            $current_regular_start_date_timex1                      = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time." +".$threshold."minutes"));
                            if(strtotime($current_date_t) <= strtotime($current_regular_start_date_time_half)){
                                if(strtotime($current_date_t) >= strtotime($current_regular_start_date_timex1)){
                                    $current_tresh = $threshold;
                                }
                                else if(strtotime($current_date_t) > strtotime($current_regular_start_date_time) && strtotime($current_date_t) < strtotime($current_regular_start_date_timex1)){
                                    $current_tresh = (strtotime($current_date_t) - strtotime($current_regular_start_date_time))/60;
                                }
                            }
                        }

                    }
                    
                    // CHECK IF TIME IN IS GREATER THAN THE ACTUAL REQUIRED TIME
                    if($current_date_str_b > $current_regular_start_date_time_str_b){

                        // CHECK IF TRESHOLD EXIST
                        if($current_tresh > 0){

                            $current_regular_start_date_time                        = date("Y-m-d H:i:00",strtotime($current_regular_start_date_time." +".$current_tresh."minutes"));
                            $current_regular_start_date_time_str_b                  = strtotime($current_regular_start_date_time);
                            
                            // CHECK IF TIME IN IS GREATER THAN THE ACTUAL REQUIRED TIME PLUS THE THRESHOLD

                            if($current_date_str_b > $current_regular_start_date_time_str_b){
                                $late_min                                           = $this->total_hours_worked($current_date_t,$current_regular_start_date_time);
                            }
                        }
                        else{
                            $late_min                                               = $this->total_hours_worked($current_date_t,$current_regular_start_date_time);

                            if(strtotime($current_date_t) > strtotime($current_regular_start_date_time_half) && strtotime($current_date_t) < strtotime($current_regular_start_date_time_half_end)){
                                $late_min = $late_min - ((strtotime($current_date_t)-strtotime($current_regular_start_date_time_half))/60);
                            }
                            else if(strtotime($current_date_t) >= strtotime($current_regular_start_date_time_half_end)){
                                $late_min = $late_min - $break_orig;
                            }
                        }

                    }
                    // here end x
                    $tardiness                      = $late_min + $overbreak_min;

                    if($migrate_v3){
                        // break injections
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

                        $flag_deduct_break                                          = false;
                        $late_min                                                   = (($current_total_hours * 60) < $late_min) ? ($current_total_hours * 60) : $late_min;
                        $tardiness_min                                              = $late_min;
                        
                        // lunch break
                        /*
                        $enable_lunch           = $is_work1->enable_lunch_break;
                        $track_break_1          = $is_work1->track_break_1;
                        $break_type_1           = $is_work1->break_type_1;
                        $break_schedule_1       = $is_work1->break_schedule_1;
                        $break_started_after    = $is_work1->break_started_after;
                        $break_in_min           = $reg_sched->break_in_min;
                        $lunch_out,$lunch_in

                        */
                        $overbreak_min                          = 0;
                        $total_hours_deduct_overall             = 0;
                        $current_date_nsd                       = 0;
                        $next_date_nsd                          = 0;

                        if($enable_lunch == "yes"){
                            if($track_break_1 == "yes"){

                                $total_hours_deduct             = 0;

                                if($lunch_out != "" && $lunch_in != ""){

                                    if($break_schedule_1 == "fixed"){

                                        $break_started_after_min    = $break_started_after * 60;
                                        $break_started_after_min    = number_format($break_started_after_min,0);
                                        $lunch_out_start            =  date("Y-m-d H:i:00",strtotime($start_time_sched. " +".$break_started_after_min." minutes"));
                                        $lunch_out_end              =  date("Y-m-d H:i:00",strtotime($lunch_out_start. " +".$break_in_min." minutes"));

                                        // if lunch out coincide in the shift schedule
                                        if($lunch_out >= $lunch_out_start && $lunch_out < $lunch_out_end){

                                            if($lunch_in > $lunch_out_end){
                                                $overbreak_min  = $overbreak_min + ($this->total_hours_worked($lunch_in,$lunch_out_end));
                                                $overbreak_min  = ($overbreak_min > 0) ? $overbreak_min : 0;
                                            }

                                            $lbreak_total_min  = $this->total_hours_worked($lunch_in,$lunch_out_start);
                                            // if break is less than the break time then set the break to the break time
                                            $lbreak_total_min   = ($lbreak_total_min > $overbreak_min) ? $lbreak_total_min : $overbreak_min;
                                            $total_hours_deduct = $total_hours_deduct + $lbreak_total_min;

                                        }
                                        else{
                                            $lunch_out          = Null;
                                            $lunch_in           = Null;
                                        }
                                    }
                                    else{
                                        
                                        $total_break_min        = $this->total_hours_worked($lunch_in,$lunch_out);
                                        // if break is less than the break time then set the break to the break time
                                        $total_break_minx       = ($total_break_min > $overbreak_min) ? $total_break_min : $overbreak_min;
                                        $total_break_min        = ($total_break_min > 0) ? $total_break_min : 0;

                                        if($total_break_min > $break_in_min){
                                            $overbreak_min      = $overbreak_min + ($total_break_min - $break_in_min);
                                            $overbreak_min      = ($overbreak_min > 0) ? $overbreak_min : 0;
                                        }
                                        $total_break_minx       = ($total_break_minx < $break_in_min) ? $break_in_min : $total_break_minx;
                                        $total_hours_deduct     = $total_hours_deduct + $total_break_minx;
                                    }
                                }
                                else{
                                    $lunch_out  = Null;
                                    $lunch_in   = Null;
                                    $total_hours_deduct         = $break_in_min;
                                }

                                if($break_type_1 == "unpaid"){
                                    // kaltas sa total hours

                                    $total_hours_deduct_overall = $total_hours_deduct_overall + $total_hours_deduct;


                                    /// night diff rule here

                                    if($get_nigtdiff_rule){
                                        $epi_nsd                = in_array_custom("emp_id_{$emp_id}",$get_employee_payroll_information);
                                        
                                        if($epi_nsd){
                                            $nsd_lunch          = false;
                                            $epi_nsd_x          = $epi_nsd->nightshift_differential_rule_name;
                                            if($epi_nsd_x == "time_bound"){
                                                if($get_nigtdiff_rule->exclude_entitlement_nsd_lunch_break == "yes"){
                                                    $nsd_lunch      = true;
                                                }
                                            }else if($epi_nsd_x == "shift_restriction"){
                                                if($get_nigtdiff_rule->exclude_entitlement_nsd_sr_lunch_break == "yes"){
                                                    $nsd_lunch      = true;
                                                }
                                            }
                                            if($nsd_lunch){
                                                $from_time              = $get_nigtdiff_rule->from_time;
                                                $to_time                = $get_nigtdiff_rule->to_time;
                                                $to_time_currentdate    = $currentdate;

                                                if($from_time > $to_time){
                                                    $to_time_currentdate = date("Y-m-d",strtotime($currentdate ."+ 1 day"));
                                                }

                                                $from_time              = $currentdate." ".$from_time;
                                                $to_time                = $to_time_currentdate." ".$to_time;

                                                $total_n_diff           = 0;
                                                if($lunch_out >= $from_time && $lunch_in < $to_time){
                                                    if(date("Y-m-d",strtotime($lunch_out)) == $currentdate){
                                                        $current_date_nsd   = $total_hours_deduct;
                                                    }else{
                                                        $next_date_nsd      = $total_hours_deduct;
                                                    }
                                                }
                                                else if($lunch_out <= $from_time && $lunch_in > $from_time){
                                                    $total_n_diff       = $this->total_hours_worked($lunch_in,$from_time);

                                                    if(date("Y-m-d",strtotime($lunch_out)) == $currentdate){
                                                        $current_date_nsd   = $total_n_diff;
                                                    }else{
                                                        $next_date_nsd      = $total_n_diff;
                                                    }
                                                }
                                                else if($lunch_out < $to_time && $lunch_in > $to_time){
                                                    $total_n_diff       = $this->total_hours_worked($to_time,$lunch_out);
                                                    if(date("Y-m-d",strtotime($lunch_out)) == $currentdate){
                                                        $current_date_nsd   = $total_n_diff;
                                                    }else{
                                                        $next_date_nsd      = $total_n_diff;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                else{
                                    // walay kaltas sa total hours
                                    $total_hours_deduct_overall = $total_hours_deduct_overall + 0;
                                }
                            }
                            else{

                                $lunch_out                      = Null;
                                $lunch_in                       = Null;

                                if($break_type_1 == "unpaid"){
                                    // kaltas sa total hours
                                    $total_hours_deduct_overall = $total_hours_deduct_overall + $break_in_min;
                                    // here // add totol hours bug
                                    $break_orig                 = 0;
                                }
                            }
                        }
                        else{

                            $lunch_out                          = Null;
                            $lunch_in                           = Null;
                        }
                    
                        /*
                        // add break
                        $enable_add_breaks      = $is_work1->enable_additional_breaks;
                        $num_of_add_breaks      = $is_work1->num_of_additional_breaks;
                        $track_break_2          = $is_work1->track_break_2;
                        $break_type_2           = $is_work1->break_type_2;
                        $break_schedule_2       = $is_work1->break_schedule_2;
                        $add_break_s_after_1    = $is_work1->additional_break_started_after_1;
                        $add_break_s_after_2    = $is_work1->additional_break_started_after_2;
                        $break_1                = $is_work1->break_1;
                        $break_2                = $is_work1->break_2;
                        $fbreak_out,$fbreak_in,$sbreak_out,$sbreak_in
                        */

                        if($enable_add_breaks == "yes"){
                            if($track_break_2 == "yes"){
                                $break_1                        = number_format($break_1,0);
                                $break_2                        = number_format($break_2,0);
                                $total_hours_deduct             = 0;

                                // first break here
                                if($fbreak_out != "" && $fbreak_in != ""){

                                    if($break_schedule_2 == "fixed"){

                                        $break_started_after_min= $add_break_s_after_1 * 60;
                                        $break_started_after_min= number_format($break_started_after_min,0);
                                        $fbreak_out_start       =  date("Y-m-d H:i:00",strtotime($start_time_sched. " +".$break_started_after_min." minutes"));
                                        $fbreak_out_end         =  date("Y-m-d H:i:00",strtotime($fbreak_out_start. " +".$break_1." minutes"));
                                        
                                        // if lunch out coincide in the shift schedule
                                        if($fbreak_out >= $fbreak_out_start && $fbreak_out < $fbreak_out_end){
                                            
                                            if($fbreak_in > $fbreak_out_end){
                                                $overbreak_min  = $overbreak_min + ($this->total_hours_worked($fbreak_in,$fbreak_out_end));
                                                $overbreak_min  = ($overbreak_min > 0) ? $overbreak_min : 0;
                                            }

                                            $break_1_total_min  = $this->total_hours_worked($fbreak_in,$fbreak_out_start);
                                            // if break is less than the break time then set the break to the break time
                                            $break_1_total_min  = ($break_1_total_min > $break_1) ? $break_1_total_min : $break_1;
                                            $total_hours_deduct = $total_hours_deduct + $break_1_total_min;

                                        }
                                        else{
                                            $fbreak_out         = Null;
                                            $fbreak_in          = Null;
                                        }
                                    }
                                    else{
                                        $total_break_min        = $this->total_hours_worked($fbreak_in,$fbreak_out);
                                        // if break is less than the break time then set the break to the break time
                                        $total_break_minx       = ($total_break_min > $break_1) ? $total_break_min : $break_1;
                                        $total_break_min        = ($total_break_min > 0) ? $total_break_min : 0;

                                        if($total_break_min > $break_1){
                                            $overbreak_min      = $overbreak_min + ($total_break_min - $break_1);
                                            $overbreak_min      = ($overbreak_min > 0) ? $overbreak_min : 0;
                                        }

                                        $total_hours_deduct     = $total_hours_deduct + $total_break_minx;
                                    }
                                }
                                else{

                                    $fbreak_out                 = Null;
                                    $fbreak_in                  = Null;
                                }


                                if($num_of_add_breaks > 1){

                                    // second break here
                                    if($sbreak_out != "" && $sbreak_in != ""){

                                        if($break_schedule_2 == "fixed"){

                                            $break_started_after_min= $add_break_s_after_2 * 60;
                                            $break_started_after_min= number_format($break_started_after_min,0);
                                            $sbreak_out_start       =  date("Y-m-d H:i:00",strtotime($start_time_sched. " +".$break_started_after_min." minutes"));
                                            $sbreak_out_end         =  date("Y-m-d H:i:00",strtotime($sbreak_out_start. " +".$break_2." minutes"));

                                            // if lunch out coincide in the shift schedule
                                            if($sbreak_out >= $sbreak_out_start && $sbreak_out < $sbreak_out_end){

                                                if($sbreak_in > $sbreak_out_end){
                                                    $overbreak_min  = $overbreak_min + ($this->total_hours_worked($sbreak_in,$sbreak_out_end));
                                                    $overbreak_min  = ($overbreak_min > 0) ? $overbreak_min : 0;
                                                }

                                                $break_2_total_min  = $this->total_hours_worked($sbreak_in,$sbreak_out_start);
                                                // if break is less than the break time then set the break to the break time
                                                $break_2_total_min  = ($break_2_total_min > $break_2) ? $break_2_total_min : $break_2;
                                                $total_hours_deduct = $total_hours_deduct + $break_2_total_min;

                                            }
                                            else{
                                                $sbreak_out         = Null;
                                                $sbreak_in          = Null;
                                            }
                                        }
                                        else{
                                            $total_break_min        = $this->total_hours_worked($sbreak_in,$sbreak_out);
                                            // if break is less than the break time then set the break to the break time
                                            $total_break_minx       = ($total_break_min > $break_2) ? $total_break_min : $break_2;
                                            $total_break_min        = ($total_break_min > 0) ? $total_break_min : 0;

                                            if($total_break_min > $break_2){
                                                $overbreak_min      = $overbreak_min + ($total_break_min - $break_2);
                                                $overbreak_min      = ($overbreak_min > 0) ? $overbreak_min : 0;
                                            }

                                            $total_hours_deduct     = $total_hours_deduct + $total_break_minx;
                                        }
                                    }
                                    else{

                                        $sbreak_out                 = Null;
                                        $sbreak_in                  = Null;
                                    }
                                }

                                if($break_type_2 == "unpaid"){
                                    // kaltas sa total hours
                                    
                                    $total_hours_deduct_overall     = $total_hours_deduct_overall + $total_hours_deduct;
                                    
                                }
                                else{
                                    // walay kaltas sa total hours
                                    $total_hours_deduct_overall     = $total_hours_deduct_overall + 0;
                                }

                            }else{
                                $fbreak_out                         = Null;
                                $fbreak_in                          = Null;
                                $sbreak_out                         = Null;
                                $sbreak_in                          = Null;

                                if($break_type_2 == "unpaid"){
                                    // kaltas sa total hours
                                    $total_hours_deduct_x           = $break_1 + $break_2;
                                    $total_hours_deduct_overall     = $total_hours_deduct_overall + $total_hours_deduct_x;
                                }
                            }
                        }
                        else{
                            $fbreak_out                             = Null;
                            $fbreak_in                              = Null;
                            $sbreak_out                             = Null;
                            $sbreak_in                              = Null;
                        }
                        // tardines_min 

                        $tardiness_min                              = $tardiness_min + $overbreak_min;

                        // end time
                        $current_regular_end_date_time              = $end_time_sched;

                        // TO GET UNDERTIME
                        $undertime_min                              = 0;
                        // CHECK IF TIME IN IS GREATER THAN THE ACTUAL REQUIRED TIME

                        // last timein
                        // init end time
                        $current_reg_end_date_time                  = date("Y-m-d H:i:00",strtotime($current_regular_end_date_time));
                        // last timein str
                        $last_time_in_str                           = strtotime($time_in);
                        $current_regular_start_date_time_str_b      = strtotime($current_regular_start_date_time);
                        
                        // adjust here
                        $start_time_sched_str_b                     = strtotime($start_time_sched);

                        if($last_time_in_str > $start_time_sched_str_b){
                            // CHECK IF TRESHOLD EXIST
                            if($current_tresh > 0){

                                // start time with tresh
                                $current_reg_start_date_time        = date("Y-m-d H:i:00",strtotime($start_time_sched." +".$current_tresh."minutes"));
                                $current_reg_start_date_time_str_b  = strtotime($current_reg_start_date_time);

                                // end time with tresh
                                $current_reg_end_date_time          = date("Y-m-d H:i:00",strtotime($current_regular_end_date_time." +".$current_tresh."minutes"));
                                
                                // CHECK IF TIME IN IS LESS THAN THE ACTUAL REQUIRED TIME PLUS THE THRESHOLD
                                
                                if($last_time_in_str < $current_reg_start_date_time_str_b){

                                    // GET THE DIFFERENCE BETWEEN THE ORIGINAL START TIME AND THE TIME IN TO GET THE THRESHOLD USED
                                    $current_timein_tresh           = $this->total_hours_worked($time_in,$start_time_sched);
                                    $current_reg_end_date_time      = date("Y-m-d H:i:00",strtotime($current_regular_end_date_time." +".$current_timein_tresh."minutes"));
                                }
                            }
                        }

                        $current_reg_end_date_time_str_b            = strtotime($current_reg_end_date_time);
                        $current_date_str_o_b                       = strtotime($time_out);

                        // CHECK IF THE WORKSCHED END TIME IS GREATER THAN THE TIME OUT TIME
                        if($current_reg_end_date_time_str_b > $current_date_str_o_b){

                            // GET UNDERTIME MIN
                            $undertime_min                          = $this->total_hours_worked($current_reg_end_date_time,$time_out);
                            $undertime_min                          = ($undertime_min > 0) ? $undertime_min : 0;
                        }

                        $total_hours_required                       = $this->total_hours_worked($time_out,$time_in);
                        $total_hours_worked                         = $this->convert_to_hours($total_hours_required);
                        $half                                       = (($hours_worked * 60)/2);
                        $median_th                                  = $half + $total_hours_deduct_overall;
                        $total_hours_requiredx                      = $total_hours_required;
                        $total_hours_requiredx                      = $total_hours_required;

                            
                        if($late_min < $half){
                            $total_hours_requiredx                  = $total_hours_requiredx + $late_min;
                        }

                        if($total_hours_requiredx >= $hours_worked_half_real ){

                            $total_hours_requiredv3                 = $total_hours_required;

                            // if halfday
                            if($threshold){
                                if($current_tresh > 0){
                                    $current_regular_start_date_time_half = date("Y-m-d H:i:s",strtotime($current_regular_start_date_time_half ." +".$current_tresh." minutes"));
                                    $current_regular_start_date_time_half_end = date("Y-m-d H:i:s",strtotime($current_regular_start_date_time_half_end ." +".$current_tresh." minutes"));
                                }
                            }

                            if(strtotime($current_date_o) > strtotime($current_regular_start_date_time_half) && strtotime($current_date_o) < strtotime($current_regular_start_date_time_half_end)){
                                
                                $total_hours_requiredv3 = $total_hours_requiredv3 - ((strtotime($current_date_o)-strtotime($current_regular_start_date_time_half))/60);
                                $undertime_min          = $undertime_min - ((strtotime($current_date_o)-strtotime($current_regular_start_date_time_half)));
                                
                            }
                            else if(strtotime($current_date_o) < strtotime($current_regular_start_date_time_half)){
                                $total_hours_requiredv3 = $total_hours_requiredv3 - $break_orig;
                                
                            }
                            if(strtotime($current_date_o) <= strtotime($current_regular_start_date_time_half)){
                                $undertime_min          = $undertime_min - $break_orig;
                            }

                            if(strtotime($current_date_o) >= strtotime($current_regular_start_date_time_half_end)){
                                $total_hours_requiredv3 = $total_hours_requiredv3 - $total_hours_deduct_overall;
                            }
                        }
                        else{
                            $total_break_undertime                  = $median_th - $total_hours_requiredx;

                            if($total_break_undertime > $total_hours_deduct_overall){
                                $total_break_undertime              = $total_break_undertime - ($total_break_undertime - $total_hours_deduct_overall);
                            }
                            $total_break_undertime                  = ($total_break_undertime > 0) ? $total_break_undertime : 0;

                            $total_hours_requiredv3                 = $total_hours_required;


                            $total_hours_deduct_overall_u           = $total_break_undertime;

                            $total_hours_deduct_overall_u           = ($total_hours_deduct_overall_u > 0) ? $total_hours_deduct_overall_u : 0;
                            
                            $undertime_min                          = $undertime_min - $total_hours_deduct_overall_u;

                            //$undertime_min                            = $undertime_min + $total_hours_deduct_overall_u; -- still dont know what is this
                            if($total_break_undertime < $total_hours_deduct_overall){
                                $undertime_min                      = $undertime_min + $total_break_undertime;
                            }

                            $undertime_min                          = ($undertime_min > 0) ? $undertime_min : 0;

                            if($late_min >= $half && $late_min <= $median_th){
                                $tardiness_min                      = $tardiness_min - $late_min;
                                $tardiness_min                      = $tardiness_min + $late_min;
                            }
                            else if($late_min > $median_th){
                                $tardiness_min                      = $tardiness_min - $late_min;           
                                $tardiness_min                      = $tardiness_min + $late_min;
                                $flag_deduct_break                  = true;
                            }
                            
                        }

                        // new undertime get
                        $undertime_min                          = 0;
                        $less_thresh                            = 0;

                        $latest_work_start_timex                = $currentdate." ".$reg_sched->work_start_time;
                        $currentdate_end                        = $currentdate;
                        
                        if($reg_sched->work_start_time > $reg_sched->work_end_time){
                            $currentdate_end                    = date("Y-m-d",strtotime($currentdate. "+ 1 day"));
                        }

                        $latest_work_end_time                   = $currentdate_end." ".$reg_sched->work_end_time;
                        
                        $halfday_hour                           = ($reg_sched->total_work_hours/2)*60;
                        $halfday_min_time                       = date("Y-m-d H:i:s",strtotime($latest_work_start_timex." +".$halfday_hour." minutes"));
                        $halfday_max_time                       = $halfday_min_time;

                        if($reg_sched->break_in_min > 0){
                            $halfday_max_time                   = date("Y-m-d H:i:s",strtotime($halfday_max_time." +".$reg_sched->break_in_min." minutes"));
                        }

                        if($time_in > $latest_work_start_timex){
                            if($is_work1->enable_shift_threshold == "yes"){
                                $tresh                          = $reg_sched->latest_time_in_allowed;
                                $less_thresh                    = $tresh;
                                
                                
                                $latest_work_start_timex        = date("Y-m-d H:i:s",strtotime($latest_work_start_timex."+".$tresh." minutes"));

                                if($latest_work_start_timex > $time_in){
                                    $less_thresh                = (strtotime($latest_work_start_timex) - strtotime($time_in))/60;
                                    $less_thresh                = $tresh - $less_thresh;
                                }
                                $less_thresh                    = $current_tresh;
                            }
                            $latest_work_end_time               = date("Y-m-d H:i:s",strtotime($latest_work_end_time." +".$less_thresh." minutes"));
                        }

                        // check under time
                        if($time_out < $latest_work_end_time){
                            $undertime = (strtotime($latest_work_end_time) - strtotime($time_out))/60;
                        }

                        $total_hours_worked                     = (strtotime($time_out) - strtotime($time_in))/60;


                        // if time in between halfday time or greater than the half max time
                        $half_latex                             = 0;
                        if($time_in >= $halfday_min_time){

                            if($time_in > $halfday_max_time){
                                $half_latex                     = $reg_sched->break_in_min;
                            }
                            else{
                                if($halfday_min_time != $halfday_max_time){
                                    $half_latex                 = (strtotime($halfday_max_time) - strtotime($time_in)) / 60;
                                }
                            }
                        }

                        // if time out between halfday time or less than the half min time
                        $half_latey                             = 0;
                        if($time_out <= $halfday_max_time){

                            if($time_out <= $halfday_min_time){
                                $half_latey                     = $reg_sched->break_in_min;
                                $partial_log_ded_break          = "yes";
                            }
                            else{
                                if($halfday_min_time != $halfday_max_time){
                                    $half_latey                 = (strtotime($halfday_max_time) - strtotime($time_out)) / 60;
                                    $half_latex                 = (strtotime($time_out) - strtotime($halfday_min_time)) / 60;
                                    $partial_log_ded_break      = "yes";
                                }
                            }
                        }else if($time_out > $halfday_max_time && $time_in < $halfday_min_time){
                            $half_latex                         = $reg_sched->break_in_min;
                        }
                        $undertime_min                              = $undertime - $half_latey;
                        // end here
                        
        
                        $total_hours_workedv3                       = $this->convert_to_hours($total_hours_requiredv3);
                        $undertime                                  = $undertime_min;
                        $tardiness                                  = $tardiness_min;

                        // if halfday
                        if($time_out <= $halfday_max_time){
                            if($threshold){
                                if($current_tresh > 0){
                                    $undertime = $undertime - $current_tresh;
                                }
                            }
                        }

                        // end of migrate
                    }
                    else{}
                }
            }
        }

        // check employee on leave
        $ileave                                 = 'no';
        $onleave                                = $this->check_leave_appliction($currentdate,$emp_id,$comp_id,$get_employee_leave_application,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_work_schedule,$get_all_regular_schedule,$get_work_schedule_flex,$get_all_schedule_blocks);

        if($onleave){

            $ileave                             = 'yes';
        }
        
        $total_hours_worked                 = $total_hours_workedv3;

        /***total hours ***/
        
        /*** BARACK NEW TOTAL HOURS WORKED  For Assumed***/
        
        if($is_work && !$migrate_v3){}

        /*** end BARACK NEW TOTAL HOURS WORKED  ***/
        
        // enable capture from shift

        $late_min   = ($late_min  > 0) ? $late_min : 0;
        $tardiness  = ($tardiness > 0) ? $tardiness : 0;
        $undertime  = ($undertime > 0) ? $undertime : 0;

        if($current_date_nsd > 0){
            $current_date_nsd = ($current_date_nsd <= $break_in_min) ? $break_in_min : $current_date_nsd;
        }

        if($next_date_nsd > 0){
            $next_date_nsd = ($next_date_nsd <= $break_in_min) ? $break_in_min : $next_date_nsd;
        }

        
        if($time_query_row){

            if($source == "updated" || $source == "recalculated" || $source == "import"){

                if($time_query_row->flag_regular_or_excess == "excess"){

                    $get_total_hours    = (strtotime($time_out) - strtotime($time_in)) / 3600;
                    $get_total_hours    = ($get_total_hours < 0) ? 0 : $get_total_hours;
                    $tq_update_field    = array(
                                        "source"                    => 'dashboard',
                                        "emp_id"                    => $emp_id,
                                        "date"                      => $currentdate,
                                        "time_in"                   => $time_in,
                                        "time_out"                  => $time_out,
                                        "undertime_min"             => 0,
                                        "tardiness_min"             => 0,
                                        "late_min"                  => 0,
                                        "overbreak_min"             => 0,
                                        "absent_min"                => 0,
                                        "work_schedule_id"          => "-2",
                                        "total_hours"               => $get_total_hours,
                                        "total_hours_required"      => $get_total_hours,
                                        "change_log_date_filed"     => date('Y-m-d H:i:s'),
                                        "flag_regular_or_excess"    => "excess",
                                        );

                    // added for coaching
                    if($inclusion_hours == "1"){
                        $tq_update_field["inclusion_hours"]         = $inclusion_hours;
                    }

                    // added partial log ded break
                    if($flag_deduct_break){
                        $tq_update_field["partial_log_ded_break"]   = "yes";
                    }
                    else{
                        $tq_update_field["partial_log_ded_break"]   = "no";
                    }
                }
                else{

                    if($log_error){
                        $tq_update_field = array(
                                        "time_in"                   => $time_in,
                                        "lunch_out"                 => $lunch_out,
                                        "lunch_in"                  => $lunch_in,
                                        "time_out"                  => $time_out,
                                        "date"                      => $currentdate,
                                        "work_schedule_id"          => $work_schedule_id,
                                        "undertime_min"             => 0,
                                        "tardiness_min"             => 0,
                                        "total_hours"               => $hours_worked,
                                        "total_hours_required"      => 0,
                                        "absent_min"                => 0,
                                        "overbreak_min"             => 0,
                                        "change_log_date_filed"     => date('Y-m-d H:i:s'),
                                        "late_min"                  => $late_min
                                        );
                    }
                    else{

                        $tq_update_field = array(
                                        "time_in"                   => $time_in,
                                        "lunch_out"                 => $lunch_out,
                                        "lunch_in"                  => $lunch_in,
                                        "break1_out"                => $fbreak_out,
                                        "break1_in"                 => $fbreak_in,
                                        "break2_out"                => $sbreak_out,
                                        "break2_in"                 => $sbreak_in,
                                        "time_out"                  => $time_out,
                                        "date"                      => $currentdate,
                                        "work_schedule_id"          => $work_schedule_id,
                                        "undertime_min"             => $undertime,
                                        "tardiness_min"             => $tardiness ,
                                        "total_hours"               => $hours_worked,
                                        "late_min"                  => $late_min,
                                        "overbreak_min"             => $overbreak_min,
                                        "absent_min"                => 0,
                                        "total_hours_required"      => $total_hours_worked,
                                        "current_date_nsd"          => $current_date_nsd,
                                        "next_date_nsd"             => $next_date_nsd,
                                        "flag_on_leave"             => $ileave
                                        );

                        // added for coaching
                        if($inclusion_hours == "1"){
                            $tq_update_field["inclusion_hours"]     = $inclusion_hours;
                        }

                        // added partial log ded break
                        if($flag_deduct_break){
                            $tq_update_field["partial_log_ded_break"]   = "yes";
                        }
                        else{
                            $tq_update_field["partial_log_ded_break"]   = "no";
                        }

                        if(!$is_work){

                            if($tardiness_absent){
                                $tq_update_field["tardiness_min"]   = $tardiness - $breakz;
                                $tq_update_field["absent_min"]      = 0;
                                $tq_update_field["late_min"]        = $tardiness - $breakz;
                            }
                            elseif($undertime_absent){

                                $tq_update_field["absent_min"]      = 0;
                                $tq_update_field["undertime_min"]   =  $undertime - $breakz; 
                            }
                            else{

                                $tq_update_field["absent_min"]      = 0;
                            }
                        }
                    }
                    // if source is from cronjob dont change last source
                    if(!$cronjob_time_recalculate){
                        if($time_query_row->source!=""){
                            $tq_update_field['last_source'] = $source;
                            $tq_update_field['change_log_date_filed'] = date('Y-m-d H:i:s');
                        }
                    }
                }

                // this is for rd ra
                if($rd_ra == "yes"){
                    $tq_update_field['rest_day_r_a']                = "yes";
                    
                    // if need approval
                    if($rest_day_settings == "yes"){
                        if($time_query_row->time_in_status != 'approved'){
                            $tq_update_field['flag_rd_include']         = "no";
                        }else{
                            $tq_update_field['flag_rd_include']         = "yes";
                        }
                    }
                    else{
                        $tq_update_field['flag_rd_include']         = "yes";
                    }
                }
                else{
                    $tq_update_field['rest_day_r_a']                = "no";

                    // if need approval
                    if($rest_day_settings == "yes"){
                        $tq_update_field['flag_rd_include']         = "yes";
                    }
                }

                // if holiday needs approval based on settings
                if($holiday_orig){ 
                    if($holiday_settings_appr == "yes"){
                        $tq_update_field['holiday_approve']         = "yes";
                        $tq_update_field['flag_holiday_include']    = "no";
                    }
                    else{
                        $tq_update_field['holiday_approve']         = "no";
                        $tq_update_field['flag_holiday_include']    = "yes";
                    }
                }else{
                    $tq_update_field['holiday_approve']             = "no";
                    $tq_update_field['flag_holiday_include']        = "yes";
                }

                $array_update['status'] = "update";
                $array_update['where'] = $time_query_where;
                $array_update['fields'] = $tq_update_field;
                
                return $array_update;
            }
            else{

                if($source == "recalculated"){
                    return false;
                }

                $get_total_hours                                    = (strtotime($time_out) - strtotime($time_in)) / 3600;
                $get_total_hours                                    = ($get_total_hours < 0) ? 0 : $get_total_hours;
                
                $date_insert                                        = array(
                                                                    "source"                    => 'Dashboard',
                                                                    "comp_id"                   => $comp_id,
                                                                    "emp_id"                    => $emp_id,
                                                                    "date"                      => $currentdate,
                                                                    "time_in"                   => $time_in,
                                                                    "time_out"                  => $time_out,
                                                                    "undertime_min"             => 0,
                                                                    "tardiness_min"             => 0,
                                                                    "late_min"                  => 0,
                                                                    "overbreak_min"             => 0,
                                                                    "work_schedule_id"          => "-2",
                                                                    "total_hours"               => $get_total_hours,
                                                                    "total_hours_required"      => $get_total_hours,
                                                                    "flag_regular_or_excess"    => "excess",
                                                                    "current_date_nsd"          => $current_date_nsd,
                                                                    "next_date_nsd"             => $next_date_nsd,
                                                                    );

                // this is for rd ra
                if($rd_ra == "yes"){
                    $date_insert['rest_day_r_a']                    = "yes";
                    
                    // if need approval
                    if($rest_day_settings == "yes"){
                        if($time_query_row->time_in_status != 'approved'){
                            $date_insert['flag_rd_include']         = "no";
                        }else{
                            $date_insert['flag_rd_include']         = "yes";
                        }
                    }
                }
                else{
                    $date_insert['rest_day_r_a']                    = "no";

                    // if need approval
                    if($rest_day_settings == "yes"){
                        $date_insert['flag_rd_include']             = "yes";
                    }
                }

                // if holiday needs approval based on settings
                if($holiday_orig){
                    if($holiday_settings_appr == "yes"){
                        $date_insert['holiday_approve']             = "yes";
                        $date_insert['flag_holiday_include']        = "no";
                    }else{
                        $date_insert['holiday_approve']             = "no";
                        $date_insert['flag_holiday_include']        = "yes";
                    }
                }
                else{
                    $date_insert['holiday_approve']                 = "no";
                    $date_insert['flag_holiday_include']            = "yes";
                }

                // added partial log ded break
                if($flag_deduct_break){
                    $date_insert["partial_log_ded_break"]   = "yes";
                }
                else{
                    $date_insert["partial_log_ded_break"]   = "no";
                }
                
                $array_update['status']                                 = "add";
                $array_update['where']                                  = array();
                $array_update['fields']                                 = $date_insert;

                return $array_update;
            }
        }
        else{

            
            if($source == "recalculated"){
                return false;
            }

            if($log_error){

                $date_insert                                        = array(
                                                                    "comp_id"                   => $comp_id,
                                                                    "emp_id"                    => $emp_id,
                                                                    "date"                      => $currentdate ,
                                                                    "source"                    => $source,
                                                                    "time_in"                   => $time_in,
                                                                    "lunch_out"                 => $lunch_out,
                                                                    "lunch_in"                  => $lunch_in,
                                                                    "time_out"                  => $time_out,
                                                                    "tardiness_min"             => 0,
                                                                    "undertime_min"             => 0,
                                                                    "late_min"                  => 0,
                                                                    "overbreak_min"             => 0,
                                                                    "total_hours"               => 0,
                                                                    "total_hours_required"      => 0
                                                                    );
            }
            else{

                $date_insert                                        = array(
                                                                    "comp_id"                   => $comp_id,
                                                                    "emp_id"                    => $emp_id,
                                                                    "date"                      => $currentdate,
                                                                    "source"                    => $source,
                                                                    "time_in"                   => $time_in,
                                                                    "lunch_out"                 => $lunch_out,
                                                                    "lunch_in"                  => $lunch_in,

                                                                    "break1_out"                => $fbreak_out,
                                                                    "break1_in"                 => $fbreak_in,
                                                                    "break2_out"                => $sbreak_out,
                                                                    "break2_in"                 => $sbreak_in,

                                                                    "time_out"                  => $time_out,
                                                                    "work_schedule_id"          => $work_schedule_id,
                                                                    "undertime_min"             => $undertime,
                                                                    "tardiness_min"             => $tardiness,
                                                                    "late_min"                  => $late_min,
                                                                    "overbreak_min"             => $overbreak_min,
                                                                    "total_hours"               => $hours_worked ,
                                                                    "total_hours_required"      => $total_hours_worked,
                                                                    "current_date_nsd"          => $current_date_nsd,
                                                                    "next_date_nsd"             => $next_date_nsd,
                                                                    "flag_on_leave"             => $ileave
                                                                    );
                
                if(!$is_work){
                    if($tardiness_absent){

                        $date_insert["tardiness_min"]               = $tardiness - $breakz;
                        $date_insert["absent_min"]                  = 0;
                        $date_insert["late_min"]                    = $tardiness - $breakz;
                    }
                    elseif($undertime_absent){

                        $date_insert["undertime_min"]               = $undertime - $breakz; 
                        $date_insert["absent_min"]                  = 0;
                    }
                    else{

                        $date_insert["absent_min"]                  = 0;
                    }

                }
            }
            
            // this is for rd ra
            if($rd_ra == "yes"){
                $date_insert['rest_day_r_a']                    = "yes";
                
                // if need approval
                if($rest_day_settings == "yes"){
                    //$date_insert['flag_rd_include']           = "no";
                }
            }
            else{
                $date_insert['rest_day_r_a']                    = "no";

                // if need approval
                if($rest_day_settings == "yes"){
                    $date_insert['flag_rd_include']             = "yes";
                }
            }

            // if holiday needs approval based on settings
            if($holiday_orig){
                if($holiday_settings_appr == "yes"){
                    $date_insert['holiday_approve']             = "yes";
                    $date_insert['flag_holiday_include']        = "no";
                }else{
                    $date_insert['holiday_approve']             = "no";
                    $date_insert['flag_holiday_include']        = "yes";
                }
            }
            else{
                $date_insert['holiday_approve']             = "no";
                $date_insert['flag_holiday_include']        = "yes";
            }

            // added partial log ded break
            if($flag_deduct_break){
                $date_insert["partial_log_ded_break"]   = "yes";
            }
            else{
                $date_insert["partial_log_ded_break"]   = "no";
            }
            
            // exclude pmax
            // total hours should not exceeds the required hours
            if($comp_id == "316"){
                if($date_insert["total_hours_required"] > $date_insert["total_hours"]){
                    //$date_insert["total_hours_required"] = $date_insert["total_hours"];
                }
            }
            
            $array_update['status']                                 = "add";
            $array_update['where']                                  = array();
            $array_update['fields']                                 = $date_insert;

            return $array_update;
        }
        return TRUE;
    
    }

    public function import_split_sched($time_in,$time_out,$work_schedule_id,$comp_id,$emp_id,$lunch_in,$lunch_out,$log_screen = false,$half_day = false,$emp_no="",$gDate="",$source="",$list_of_blocks,$get_all_schedule_blocks,$time_query_where,$time_query_row,$get_all_employee_timein,$get_all_schedule_blocks_time_in,$get_all_regular_schedule,$get_employee_payroll_information,$get_tardiness_settings,$get_work_schedule_flex,$company_holiday){return false;}

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
                $total_total_hours      = $total_total_hours + $total_hours;
                
                $if_reghrs_custom       = in_array_custom($r->schedule_blocks_id,$row_array);
                if($if_reghrs_custom){
                    $absent_block_total_h = $absent_block_total_h - $if_reghrs_custom->total_hours_work_per_block;
                }
            }
        }
        $absent_block_total_h                       = $absent_block_total_h * 60;
        $update_timein_logs                         = array(
                                                    "time_in"               => $time_in,
                                                    "time_out"              => $time_out,
                                                    "tardiness_min"         => $total_tardy,
                                                    "late_min"              => $total_late,
                                                    "overbreak_min"         => $total_over_break,
                                                    "undertime_min"         => $total_under_time,
                                                    "total_hours_required"  => $total_total_hours_req,
                                                    "total_hours"           => $total_total_hours,
                                                    "absent_min"            => $absent_block_total_h
                                                    );

        if($holiday){
            $update_timein_logs["late_min"]         = 0;
            $update_timein_logs["tardiness_min"]    = 0;
            $update_timein_logs["overbreak_min"]    = 0;
            $update_timein_logs["undertime_min"]    = 0;
            $update_timein_logs["absent_min"]       = 0;
        }

        if($source == "recalculated"){
            $update_timein_logs['last_source'] = "recalculated";
            $update_timein_logs['change_log_date_filed'] = date('Y-m-d H:i:s');
        }
        if($source == "updated"){
            $update_timein_logs['last_source'] = "updated";
            $update_timein_logs['change_log_date_filed'] = date('Y-m-d H:i:s');
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

    public function get_employee_credentials_arr_v2($company_id){
        if(is_numeric($company_id)){
            $row_array = array();
            $select = array(
                    'emp_id'
            );
            $select1 = array(
                    'e.first_name',
                    'e.last_name',
                    'a.payroll_cloud_id'
            );
            
            $where1 = array(
                    'e.company_id'       => $company_id,
                    'a.user_type_id'     => '5',
                    'e.status'           => 'Active'
            );
    
            $this->db->select($select);
            $this->edb->select($select1);
            $this->db->where($where1);
            $this->edb->join('accounts AS a','a.account_id = e.account_id','left');
            $query = $this->edb->get('employee AS e');
            $row = $query->result();
            $query->free_result();
            
            if($row){
                foreach ($row as $r1){
                    $wd = array(
                            "emp_id"            => $r1->emp_id,
                            "first_name"        => $r1->first_name,
                            "last_name"         => $r1->last_name,
                            "payroll_cloud_id"  => $r1->payroll_cloud_id,
                            "custom_search"     => "pci-{$r1->payroll_cloud_id}",
                    );
                    array_push($row_array,$wd);
                }
            }
            
            return $row_array;
        }else{
            return false;
        }
    }

    public function emp_work_schedulev2a($company_id,$min="",$max="",$emp_ids=""){
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
        }
        
        if($emp_ids){
            $this->db->where_in("ess.emp_id",$emp_ids);
        }
        $w_emp = array(
                "ess.company_id"=>$company_id,
                "ess.status"=>"Active",
                "ess.payroll_group_id" => 0
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

    public function emp_work_schedulev2b($company_id,$emp_ids=""){
        $row_array  = array();
        $s          = array(
                    'epi.emp_id',
                    'pg.work_schedule_id',
                    );
        $this->db->select($s);
        
        $w          = array(
                    'epi.company_id'=> $company_id
                    );
        $this->db->where($w);
        
        if($emp_ids){
            $this->db->where_in("epi.emp_id",$emp_ids);
        }
        $this->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','LEFT');
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

    public function check_is_date_holidayv2($company_id,$min="",$max=""){
        
        $row_array  = array();
        $year_min   = date("Y",strtotime($min));
        $year_max   = date("Y",strtotime($max));
        
        $y_gap      = ($year_max - $year_min) + 1;
        $s      = array(
                'h.repeat_type',
                'ht.hour_type_name',
                'h.holiday_name',
                'h.date',
                'h.date_type',
        );
        $this->db->select($s);
        $w      = array(
                "h.company_id" => $company_id
        );
        $this->db->where($w);
        //$this->db->where("(MONTH(h.date) = '{$month}' && DAY(h.date) = '{$day}')");
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
                                "repeat_type"       => $r1->repeat_type,
                                "hour_type_name"    => $r1->hour_type_name,
                                "holiday_name"      => $r1->holiday_name,
                                "date"              => $hol_date,
                                "custom_search"     => "date-{$hol_date}",
                        );
                        array_push($row_array,$wd);
                    }
                }else{
                    $wd     = array(
                            "repeat_type"       => $r1->repeat_type,
                            "hour_type_name"    => $r1->hour_type_name,
                            "holiday_name"      => $r1->holiday_name,
                            "date"              => $r1->date,
                            "custom_search"     => "date-{$r1->date}",
                    );
                    array_push($row_array,$wd);
                }
            }
        }
        return $row_array;
    }

    public function edit_delete_void2($emp_id,$comp_id,$gDate){
        $gDate = date("Y-m-d",strtotime($gDate));
        $return_void = false;
        $return_r = "";
        $w = array(
            'prc.emp_id'            => $emp_id,
            'prc.company_id'        => $comp_id,
            'prc.period_from <='    => $gDate,
            'prc.period_to >='      => $gDate, 
            'prc.status'            => 'Active'
        );
        $s = array(
            'dpr.view_status',
            'prc.period_from',
            'prc.period_to'
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
                    $return_r    = $r;
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
                'dpr.view_status',
                'dpr.view_status',
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
                        $return_r    = $r1;
                    }
                }
            }
        }
        
        if($return_void){
            return $return_r;
        }else{
            return false;
        }
    }

    public function check_employee_timeinsv2($company_id,$emp_id,$date) {
        if(is_numeric($company_id)) {
            $query = $this->db->query("SELECT  * FROM employee_time_in WHERE (time_in_status ='pending' OR time_in_status ='approved' OR time_in_status IS NULL) && status = 'Active' && flag_regular_or_excess = 'regular' && comp_id = '{$this->db->escape_str($company_id)}' AND date='{$this->db->escape_str($date)}' AND emp_id='{$this->db->escape_str($emp_id)}' AND status = 'Active'");
            $row = $query->row();
            $query->free_result();
            return $row;
        } else {
            return false;
        }
    }

    /// validation -- import add logs update

    public function create_new_checkv2($company_id,$data_list = array(),$source=""){
        
        $error      = array();
        $row_start  = 1;
        $conflict   = array();
        $list_arr   = array();
        $date_mix   = array();
        $emp_ids    = array();
        if($data_list) {
            foreach($data_list as $key=>$val){
                $emp_ids[]      = $val[0];
            }
        }

        $all_emp_id                     = $this->get_employee_credentials_arr_v2($company_id);
        $all_wsid                       = $this->emp_work_schedulev2a($company_id,"","",$emp_ids);
        $all_pwsid                      = $this->emp_work_schedulev2b($company_id,$emp_ids);
        $all_holiday                    = $this->check_is_date_holidayv2($company_id,"","");
        $list_of_blocks                 = $this->list_of_blocks($company_id,$emp_ids);
        $get_all_schedule_blocks        = $this->get_all_schedule_blocks($company_id);
        $all_worksched                  = $this->get_work_schedule($company_id);
        $all_reg_sched                  = $this->get_all_regular_schedule($company_id);
        
        if($data_list) { 
            foreach($data_list as $key=>$val):  
            
                $allow                  = true;     
                $emp_id                 = $val[0];          
                $time_in                = $val[1];
                $lunch_out              = $val[2];
                $lunch_in               = $val[3];

                $fbreak_out             = $val[4];
                $fbreak_in              = $val[5];
                $sbreak_out             = $val[6];
                $sbreak_in              = $val[7];

                $time_out               = $val[8];
                $first_name             = $val[9];
                $last_name              = $val[10];
                $emp_no                 = $val[11];
                $gDate                  = isset($val[12]) ? $val[12] : "";
                $eti                    = $val[13];

                $middle_name            = "";
                $row_where              = $row_start++;
                
                if(trim($emp_id,",") ==""){
                    $error[]            = "The employee does not have an ID in Row [$row_where]";
                    $list_arr[]         = $key;
                }

                if($first_name ==""){
                    //$error[]              = "The employee does not have a first name in row [$row_where]";
                    //$list_arr[]       = $key;
                }

                if($time_in ==""){
                    $error[]            = "The employee does not have a time-in in row [$row_where]";
                    $list_arr[]         = $key;
                }
                if($time_out ==""){
                    $error[]            = "The employee does not have a time-out in row [$row_where]";
                    $list_arr[]         = $key;
                }
                
                    
                    
                $time_in2               = date('Y-m-d H:i:s',strtotime($time_in));
                $time_out2              = date('Y-m-d H:i:s',strtotime($time_out));

                $lunch_out2             = date('Y-m-d H:i:s',strtotime($lunch_out));
                $lunch_in2              = date('Y-m-d H:i:s',strtotime($lunch_in));

                $fbreak_out2            = date('Y-m-d H:i:s',strtotime($fbreak_out));
                $fbreak_in2             = date('Y-m-d H:i:s',strtotime($fbreak_in));

                $sbreak_out2            = date('Y-m-d H:i:s',strtotime($sbreak_out));
                $sbreak_in2             = date('Y-m-d H:i:s',strtotime($sbreak_in));


                $time_in2d              = date('Y-m-d',strtotime($time_in));
                $time_out2d             = date('Y-m-d',strtotime($time_out));

                $lunch_out2d            = date('Y-m-d',strtotime($lunch_out));
                $lunch_in2d             = date('Y-m-d',strtotime($lunch_in));

                $fbreak_out2d           = date('Y-m-d',strtotime($fbreak_out));
                $fbreak_in2d            = date('Y-m-d',strtotime($fbreak_in));

                $sbreak_out2d           = date('Y-m-d',strtotime($sbreak_out));
                $sbreak_in2d            = date('Y-m-d',strtotime($sbreak_in));

                // here to here new trapping 03-28-18

                $gDated                 = date('Y-m-d',strtotime($gDate));
                $gDate_min              = date("Y-m-d",strtotime($gDated ."-1 day"));
                $gDate_max              = date("Y-m-d",strtotime($gDated ."+2 day"));

                
                if($time_in){
                    if(($time_in2d < date('Y-m-d H:i:s',strtotime($gDate_min. " -1 minutes"))) || ($time_in2d > $gDate_max)){
                        $error[]        = "Time In does not coincide with your shift date In Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }
                }   

                if($lunch_out !="" && $lunch_in !="") {
                    if(($lunch_out2d < $gDate_min) || ($lunch_out2d > $gDate_max)){
                        $error[]        = "Lunch Out does not coincide with your shift date In Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }
                    if(($lunch_in2d < $gDate_min) || ($lunch_in2d > $gDate_max)){
                        $error[]        = "Lunch In does not coincide with your shift date In Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }
                }

                if($fbreak_out !="" && $fbreak_in !="") {
                    if(($fbreak_out2d < $gDate_min) || ($fbreak_out2d > $gDate_max)){
                        $error[]        = "First Break Out does not coincide with your shift date In Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }

                    if(($fbreak_in2d < $gDate_min) || ($fbreak_in2d > $gDate_max)){
                        $error[]        = "First Break In does not coincide with your shift date In Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }
                }

                if($sbreak_out !="" && $sbreak_in !="") {
                    if(($sbreak_out2d < $gDate_min) || ($sbreak_out2d > $gDate_max)){
                        $error[]        = "Second Break Out does not coincide with your shift date In Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }

                    if(($sbreak_in2d < $gDate_min) || ($sbreak_in2d > $gDate_max)){
                        $error[]        = "Second Break In does not coincide with your shift date In Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }
                }

                if($time_out){
                    if(($time_out2d < $gDate_min) || ($time_out2d > $gDate_max)){
                        $error[]        = "Time Out does not coincide with your shift date In Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }
                }

                // end here

                
                if($lunch_out !="" && $lunch_in !="") {

                    if($lunch_in2 < $lunch_out2){

                        $error[]        = "Lunch Out Is Greater than Lunch In Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }   
                    if($lunch_in2 > $time_out2){

                        $error[]        = "Lunch In Is Greater than Time Out in Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }   
                    
                    if($lunch_out2 >$time_out2){

                        $error[]        = "Lunch Out Is Greater than Time Out in Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }
                }

                if($fbreak_out !="" && $fbreak_in !="") {

                    if($fbreak_in2 < $fbreak_out2){

                        $error[]        = "Break1 Out Is Greater than Break1 In Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }   
                    if($fbreak_in2 > $time_out2){

                        $error[]        = "Break1 In Is Greater than Time Out in Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }   
                    
                    if($fbreak_out >$time_out2){

                        $error[]        = "Break1 Out Is Greater than Time Out in Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }
                }

                if($sbreak_out !="" && $sbreak_in !="") {

                    if($sbreak_in2 < $sbreak_out2){

                        $error[]        = "Break2 Out Is Greater than Break2 In Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }   
                    if($sbreak_in2 > $time_out2){
                        $error[] = "Break2 In Is Greater than Time Out in Row [".$row_where."] ";
                        $list_arr[] = $key;
                    }   
                    
                    if($sbreak_out2 >$time_out2){

                        $error[]        = "Break2 Out Is Greater than Time Out in Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }
                }

                if($time_in !="" && $time_out !="") {

                    if($time_out =="") {

                        $conflict[]     = "The employee does not have a time out in row [$row_where]";
                        $list_arr[]     = $key;
                        $allow          = false;
                    }

                    if($time_in2 > $time_out2){ 
                        
                        $error[]        = "Time In Is Greater than Time Out in Row [".$row_where."] ";
                        $list_arr[]     = $key;
                    }
                }
                        
                        
                if($time_in && $time_out && $lunch_out =="" && $lunch_in =="") {
                    if(idate_convert($time_in) >idate_convert($time_out)){

                        $error[]        = "Time In Is Greater than Time Out in Rowx [".$row_where."]";
                        $list_arr[]     = $key;
                        $allow          = false;
                    }
                    
                    $time_in2           = date('Y-m-d H:i:s',strtotime($time_in));
                    $time_out2          = date('Y-m-d H:i:s',strtotime($time_out));

                    if($time_in2 > $time_out2){

                        $error[]        = "Time In Is Greater than Time Out in Row [".$row_where."] ";
                        $list_arr[]     = $key;
                        $allow          = false;
                    }
                }
                
                
                if($emp_id !="" && $first_name !="" && $time_in !="") {

                    $date               = idate_convert($time_in);
                    $check              = in_array_custom("pci-{$emp_no}",$all_emp_id);
                    
                    // kani siya kung ang employee ga exists /// gamit most sa import
                    if($check) {
                        // kuha ang emp id
                        $get_emp_id     = $check->emp_id;

                        //  put void here gamit ni if human na ang payroll
                        $void           = $this->edit_delete_void($get_emp_id,$company_id,$gDate);
                        if($void){
                            if($void == "Waiting for approval"){
                                $error[]    = "Payroll is Locked, can't add timesheet for this date '".idates($gDate)."'. Row [$row_where]";
                            }
                            else if($void == "Closed"){
                                $error[]    = "Payroll is already closed, can't add timesheet for this date '".idates($gDate)."'. Row [$row_where]";
                            }
                            $list_arr[] = $key;
                            $allow      = false;
                        }

                        $employee_timein_date       = date('Y-m-d',strtotime($gDate));

                        // previous day
                        $employee_timein_date2      = date('Y-m-d',strtotime($date. " -1 day"));
                        
                        $work_schedule_idw          = in_array_custom("emp_id-{$get_emp_id}-{$employee_timein_date2}",$all_wsid);
                        if($work_schedule_idw){
                            $work_schedule_id       = $work_schedule_idw->work_schedule_id;
                        }
                        else{
                            $work_schedule_idp      = in_array_custom("emp_id-{$get_emp_id}",$all_pwsid);
                            if($work_schedule_idp){
                                $work_schedule_id   = $work_schedule_idp->work_schedule_id;
                            }
                        }
                        // previous split
                        $previous_split_sched       = $this->yesterday_split_info($time_in,$get_emp_id,$work_schedule_id,$company_id,false,$list_of_blocks,$get_all_schedule_blocks);

                        $child                      = true;

                        if($previous_split_sched){
                            $last_sched             = max($previous_split_sched);
                            $time_inx               = date('Y-m-d H:i:s',strtotime($time_in));
                            
                            if( $time_inx <= $last_sched['end_time']){

                                $yesterday_m            = date('Y-m-d',strtotime($time_in));
                                $employee_timein_date   = date('Y-m-d',strtotime($yesterday_m. " -1 day")); 
                                $date                   = date('Y-m-d',strtotime($yesterday_m. " -1 day"));
                                $child                  = false;
                            }
                        }
                        
                        $work_schedule_idw              = in_array_custom("emp_id-{$get_emp_id}-{$employee_timein_date}",$all_wsid);
                        if($work_schedule_idw){
                            $workschedule               = $work_schedule_idw->work_schedule_id;
                        }
                        else{
                            $work_schedule_idp          = in_array_custom("emp_id-{$get_emp_id}",$all_pwsid);
                            if($work_schedule_idp){
                                $workschedule           = $work_schedule_idp->work_schedule_id;
                            }
                        }

                        $split                          = $this->check_if_split_schedule($get_emp_id,$company_id,$workschedule, $date,$time_in,$time_out,$get_all_schedule_blocks,$list_of_blocks,$all_reg_sched);
                        
                        $void                           = $this->edit_delete_void2($get_emp_id,$company_id,$gDate);

                        // kung holiday
                        $is_holiday                     = in_array_custom("date-{$gDate}",$all_holiday);
                        $holiday                        = false;
                        
                        if($is_holiday){
                            if($is_holiday->repeat_type == "no"){

                                $cur_year               = date("Y");
                                $hol_year               = date("Y",strtotime($date));
                                
                                if($cur_year == $hol_year){

                                    $holiday            = true;
                                }
                                else{
                                    $holiday            = false;
                                }
                            }
                            else{

                                $holiday                = true;
                            }
                        }

                        //$checker                      = $this->check_work_sched_time_in($workschedule,$gDate);

                        $checker                        = false;
                        $work_schedule_type             = in_array_custom("worksched_id_{$workschedule}",$all_worksched);

                        if($work_schedule_type){

                            if($work_schedule_type->workday_type == "Uniform Working Days"){
                                $l                      = date("l",strtotime($gDate));
                                $checker                = in_array_custom("rsched_id_{$workschedule}_{$l}",$all_reg_sched);
                            }
                            else if($work_schedule_type->workday_type == "Flexible Hours"){
                                // wala man ni sud copy ra nako ug gi optimize
                            }
                            else if($work_schedule_type->workday_type == "Workshift"){
                                // wala man ni sud copy ra nako ug gi optimize
                            }
                        }
                        
                        $error_date_checker = false;

                        // according to code sa regular ra ni 
                        if($checker){
                            $start_time_ws              = $checker->work_start_time;
                            $date_time_ws1              = date("Y-m-d H:i:00",strtotime($gDate." ".$start_time_ws));
                            $date_time_ws               = date("Y-m-d H:i:00",strtotime($date_time_ws1 ."-120 minutes"));
                            $time_in_emp                = date("Y-m-d H:i:00",strtotime($time_in));

                            // scene :  if timein is less than the shift start time minus two hours
                            if(!$holiday){
                                $date_time_wsx          = date('Y-m-d H:i:s',strtotime($date_time_ws. " -1 minutes"));
                                if(strtotime($time_in_emp) <= strtotime($date_time_wsx)){
                                    $error[]            = "Employee timein does not coincide your employee shift. Row [$row_where]";
                                    $allow              = false;
                                    $error_date_checker = true;
                                }
                            }
                        }

                        if(!$error_date_checker){

                            if(!$split){

                                $check_timeins          = $this->check_employee_timeinsv2($company_id,$get_emp_id,$employee_timein_date);

                                // checker if there is a logs on the date filled
                                if($check_timeins){
                                    
                                    $time_in            = date("Y-m-d H:i:s",strtotime($time_in));
                                    $time_out           = date("Y-m-d H:i:s",strtotime($time_out));
                                    $t_status           = $check_timeins->time_in_status;
                                    $t_hours            = $check_timeins->total_hours_required;

                                    if($source == "Dashboard"){
                                        if($t_status == "pending"){
                                            $error[]    = "This employee has pending timesheet on this date. This needs to be approve first before adding a new one. Row [$row_where]";
                                            
                                            $allow      = false;
                                        }
                                        else if($t_hours <= 0){
                                            $error[]    = "The timesheet of this employee for this date has zero hours worked. This needs to be adjusted first before adding a new timesheet. Row [$row_where]";
                                            //$list_arr[] = $key;
                                            $allow      = false;
                                        }
                                        else{
                                            if((strtotime($check_timeins->time_in) <= strtotime($time_in) && strtotime($time_in) <= strtotime($check_timeins->time_out)) || (strtotime($check_timeins->time_in) <= strtotime($time_out) && strtotime($time_out) <= strtotime($check_timeins->time_out)) ){
                                                $error[]    = "This timesheet has overlapped an existing timesheet. Please check you hours and try again. Row [$row_where]";
                                                //$list_arr[] = $key;
                                                $allow      = false;
                                            }
                                            else{
                                                if(((strtotime($check_timeins->time_in) >= strtotime($time_in) && strtotime($time_out) >= strtotime($check_timeins->time_in)) && (strtotime($check_timeins->time_out) >= strtotime($time_in) && strtotime($time_out) >= strtotime($check_timeins->time_out)))){
                                                    $error[]    = "This timesheet has overlapped an existing timesheet. Please check you hours and try again. Row [$row_where]";
                                                    //$list_arr[] = $key;
                                                    $allow      = false;
                                                }
                                                else{
                                                    if(!$void){
                                                        $conflict[] = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($check_timeins->date))." already exists in the database on row [$row_where]";
                                                        //$date_mix[] = $key;
                                                        $allow = false;
                                                    }else{
                                                        foreach ($void AS $v){
                                                            $stat_v = $v->view_status;
                                                            if($stat_v == "Waiting for approval"){
                                                                $f = $v->period_from;
                                                                $t = $v->period_to;
                                                                $error[] = "Row [$row_where]. Timesheets for ".date("d-M-Y",strtotime($f))." to ".date("d-M-Y",strtotime($t))." are locked for payroll processing.
                                                                            When timesheets are locked it is not possible to initiate any time related process affecting the payroll period.
                                                                            Therefore if you need to submit any absence or attendance adjustments it is recommended to seek approval from your HR
                                                                            or manager for this transaction online.";
                                                                //$list_arr[] = $key;
                                                                $allow      = false;
                                                            }else if($stat_v == "Closed"){
                                                                $error[] =  "Row [$row_where]. When payroll is closed it is not possible to initiate any time related process affecting this
                                                                            payroll . Therefore if you need to submit any absence or attendance adjustments it is recommended to seek approval from
                                                                            your HR or manager for this transaction offline.";
                                                                //$list_arr[] = $key;
                                                                $allow      = false;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }else{
                                        if(!$void){
                                            $conflict[] = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($check_timeins->date))." already exists in the database on row [$row_where]";
                                            //$date_mix[] = $key;
                                            $allow = false;
                                        }else{
                                            foreach ($void AS $v){
                                                $stat_v = $v->view_status;
                                                if($stat_v == "Waiting for approval"){
                                                    $f = $v->period_from;
                                                    $t = $v->period_to;
                                                    $error[] = "Row [$row_where]. Timesheets for ".date("d-M-Y",strtotime($f))." to ".date("d-M-Y",strtotime($t))." are locked for payroll processing.
                                                                        When timesheets are locked it is not possible to initiate any time related process affecting the payroll period.
                                                                        Therefore if you need to submit any absence or attendance adjustments it is recommended to seek approval from your HR
                                                                        or manager for this transaction online.";
                                                }else if($stat_v == "Closed"){
                                                    $error[] =  "Row [$row_where]. When payroll is closed it is not possible to initiate any time related process affecting this
                                                                payroll . Therefore if you need to submit any absence or attendance adjustments it is recommended to seek approval from
                                                                your HR or manager for this transaction offline.";
                                                    $list_arr[] = $key;
                                                    $allow      = false;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            else{
                                $split_timeins = $this->check_employee_timeins_split($company_id,$get_emp_id,$date);
                                
                                if($split_timeins && $child){
                                    if(!$void){
                                        $conflict[] = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($split_timeins->date))." already exists in the database on row [$row_where]";
                                    }
                                }
                            }
                        }
                    
                        if($workschedule==false && $allow){
                            //$w = $this->if_no_workschedule($get_emp_id,$company_id);
                            $w = $this->emp_work_schedule($get_emp_id,$company_id,$employee_timein_date2);
                            
                            if($w == 0 || $w == NULL || $w == ""){
                                $error[]    = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($date))." is not assigned to a Payroll group row [$row_where]. Please assign a Payroll Group on Workforce";
                                $list_arr[] = $key;
                            }else{
                            
                                $conflict[] = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($date))." doesn't have workschedule in row [$row_where]. Please assign a workschedule on Shifts";
                                $date_mix[] = $key;
                            }
                        }
                    }
                    else {
                        $error[] = "Employee doesn't exist (".$emp_no.$company_id ." ".strtoupper($first_name).' '.strtoupper($last_name).") in row [$row_where]";
                        $list_arr[] = $key;
                    }
                }
                
                endforeach;
                
                $list_arr = array_unique($list_arr);

                $this->session->set_userdata('list_conflict2',$list_arr);
                $this->session->set_userdata('date_mix',$date_mix);

                return array("error"=>$error,"overwrites"=>$conflict,"valid_csv"=>true);
        }
        else{
            return array("error"=>"","overwrites"=>"","valid_csv"=>false);
        }
    }

    /**
    *   Checks employee timesins if conflicts in split schedule
    *   @param int $company_id
    *   @param int $emp_id
    *   @param date $date   
    *   @return boolean
    */
    public function check_employee_timeins_split($company_id,$emp_id,$date) {
        if(is_numeric($company_id)) {
            $query = $this->db->query("SELECT  * FROM schedule_blocks_time_in WHERE comp_id = '{$this->db->escape_str($company_id)}' AND status = 'Active' AND date='{$this->db->escape_str($date)}' AND emp_id='{$this->db->escape_str($emp_id)}' AND status = 'Active'");
            $row = $query->row();
            $query->free_result();
            return $row;
        } else {
            return false;
        }
    }

    public function save_create_new($company_id,$data_entry= array(),$source="",$migrate_v3=false){
        $con        = $this->session->userdata('list_conflict2');
        $date_mix   = $this->session->userdata('date_mix');

        if($company_id) {
            // lock update
            $this->session->set_userdata('ts_lock_settings', "true");
            $fieldts = array('ts_recalc'=>"true",'ts_recalc_time'=>date("Y-m-d H:i:s"));
            $wherets = array('company_id'=>$company_id);
            eupdate("lock_payroll_process_settings",$fieldts,$wherets);

            $range                      = array();
            $emp_ids                    = array();
            $min_range                  = "";
            $max_range                  = "";
            $num                        = 0;
            if($data_entry){

                foreach ($data_entry AS $app){

                    $good               = true;
                    if($con){
                        foreach($con as $rows){
                            if($rows == $num){
                                $good   = false;                                
                            }
                        }
                    }
                    
                    if($date_mix){
                        foreach($date_mix as $allow){
                            if($allow == $num){
                                $good = true;
                                
                            }
                        }
                    }

                    if($good){
                        $range[]    = $app[8];
                        $emp_ids[]  = $app[0];
                    }
                    $num++; 
                }
            }
            sort($range);
            $range_count            = count($range);

            if($range_count){
                $min_range          = $range[0];
                $max_range          = $range[$range_count-1];

                $min_range          = date("Y-m-d",strtotime($min_range. " -1 day"));
                $max_range          = date("Y-m-d",strtotime($max_range. " +1 day"));
            }
            
            // parent functions to be use
            $get_employee_payroll_information   = $this->get_employee_payroll_information($company_id,$emp_ids);
            $emp_employe_account_info           = $this->emp_employe_account_info($company_id,$emp_ids);
            $emp_work_schedule_ess              = $this->emp_work_schedule_ess($company_id,$emp_ids);
            $emp_work_schedule_epi              = $this->emp_work_schedule_epi($company_id,$emp_ids);
            $list_of_blocks                     = $this->list_of_blocks($company_id,$emp_ids);
            $get_all_schedule_blocks            = $this->get_all_schedule_blocks($company_id);
            $get_all_regular_schedule           = $this->get_all_regular_schedule($company_id);
            $check_rest_days                    = $this->check_rest_day($company_id);
            $get_work_schedule_flex             = $this->get_work_schedule_flex($company_id);
            $company_holiday                    = $this->company_holiday($company_id);
            $get_work_schedule                  = $this->get_work_schedule($company_id);
            $get_employee_leave_application     = $this->get_employee_leave_application($company_id,$emp_ids);
            $get_tardiness_settings             = $this->get_tardiness_settings($company_id);
            $get_all_employee_timein            = $this->get_all_employee_timein($company_id,$emp_ids,$min_range,$max_range);
            $get_all_schedule_blocks_time_in    = $this->get_all_schedule_blocks_time_in($company_id,$emp_ids,$min_range,$max_range);
            $attendance_hours                   = is_attendance_active($company_id);
            $get_tardiness_settingsv2           = $this->get_tardiness_settingsv2($company_id);
            $tardiness_rounding                 = $this->tardiness_rounding($company_id);
            $holiday_settings                   = $this->get_holiday_pay_settings($company_id);
            $rest_day_settings                  = $this->get_rest_day_settings($company_id);
            $get_nigtdiff_rule                  = $this->get_nigtdiff_rule($company_id);
            $get_shift_restriction_schedules    = $this->get_shift_restriction_schedules($company_id);
            

            // for open shift
            $period_types                       = array();
            $get_all_employee_period_typex      = array();
            $get_all_payroll_calendarx          = array();
            $comp_ids                           = array($company_id);

            $get_all_employee_period_typex      = $this->get_all_employee_period_type($emp_ids);

            if($get_all_employee_period_typex){
                foreach ($get_all_employee_period_typex as $key => $period_typex) {
                    $period_types[]                                 = $period_typex['period_type'];
                }
            }

            if($period_types){
                $get_all_payroll_calendarx      = $this->get_all_payroll_calendar($period_types,$comp_ids);
            }
            // end for open shift

            $holiday_settings_appr              = "no";
            if($holiday_settings){
                if($holiday_settings->holiday_pay_approval_settings == "yes"){
                    $holiday_settings_appr      = "yes";
                }
            }

            $row_start                          = 0;
            $conflict                           = array();
            $toboy                              = array();
            $num                                = 0;

            if($data_entry) {

                foreach($data_entry as $row) {

                    $payroll_cloud_id           = $this->db->escape_str($row[7]);
                    $time_in                    = $row[1];
                    $lunch_out                  = $row[2];
                    $lunch_in                   = $row[3];

                    if($migrate_v3){

                        $fbreak_out             = $row[4];
                        $fbreak_in              = $row[5];
                        $sbreak_out             = $row[6];
                        $sbreak_in              = $row[7];

                        $time_out               = $row[8];
                        $first_name             = $row[9];
                        $last_name              = $row[10];

                        $gDate                  = isset($row[12]) ? $row[12] : "" ;
                        $employee_time_in_id    = isset($row[13]) ? $row[13] : "";
                        $last_source            = isset($row[14]) ? $row[14] : "";
                        $inclusion_hours        = isset($row[15]) ? $row[15] : "";

                    }else{

                        $fbreak_out             = "";
                        $fbreak_in              = "";
                        $sbreak_out             = "";
                        $sbreak_in              = "";

                        $time_out               = $row[4];
                        $first_name             = $row[5];
                        $last_name              = $row[6];

                        $gDate                  = isset($row[8]) ? $row[8] : "" ;
                        $employee_time_in_id    = isset($row[9]) ? $row[9] : "";
                        $last_source            = isset($row[10]) ? $row[10] : "";
                    }

                    $good                       = true;

                    if($con){
                        foreach($con as $rows){
                            if($rows == $num){
                                $good = false;                              
                            }
                        }
                    }
                    
                    if($date_mix){
                        foreach($date_mix as $allow){
                            if($allow == $num){
                                $good = true;
                                
                            }
                        }
                    }
                    
                    if($good){

                        if($time_in ==""){
                            $time_in = null;
                        }
                        if($lunch_out == "" || $lunch_out == ""){
                            $lunch_out = null;
                        }
                        if($lunch_in == "" || $lunch_in == ""){
                            $lunch_in = null;
                        }
                        if($time_out == ""){
                            $time_out = null;
                        }

                        //p(array($row[0],$time_in,$lunch_out,$lunch_in,$fbreak_out,$fbreak_in,$sbreak_out,$sbreak_in,$time_out,$source,"",$gDate,"",$employee_time_in_id));
                         
                        $this->import_new($company_id,$row[0],$time_in,$lunch_out,$lunch_in,$fbreak_out,$fbreak_in,$sbreak_out,$sbreak_in,$time_out,$source,"",$gDate,"",$employee_time_in_id,$last_source,$get_employee_payroll_information,$emp_employe_account_info,$emp_work_schedule_ess,$emp_work_schedule_epi,$list_of_blocks,$get_all_schedule_blocks,$get_all_regular_schedule,$check_rest_days,$get_work_schedule_flex,$company_holiday,$get_work_schedule,$get_employee_leave_application,$get_tardiness_settings,$get_all_employee_timein,$get_all_schedule_blocks_time_in,$attendance_hours,$get_tardiness_settingsv2,$tardiness_rounding,$migrate_v3,$holiday_settings_appr,$inclusion_hours,$rest_day_settings,false,$get_all_employee_period_typex,$get_all_payroll_calendarx,$get_nigtdiff_rule,$get_shift_restriction_schedules);
                        
                        $toboy[] = $this->db->insert_id();
                    }
                    else{
                        $toboy = array();
                    }
                    $num++;
                }
            }
            // lock update
            $this->session->set_userdata('ts_lock_settings', "");
            $fieldts = array('ts_recalc'=>"false",'ts_recalc_time'=>date("Y-m-d H:i:s"));
            $wherets = array('company_id'=>$company_id);
            eupdate("lock_payroll_process_settings",$fieldts,$wherets);

            $this->session->set_userdata('list_conflict2','');
            $this->session->set_userdata('date_mix','');
            return $toboy;
        }
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
                    "tms.flag_v3_migrate"   => '1',
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

    public function get_holiday_pay_settings($company_id){

        $row_array  = array();

        $s          = array(
                    'holiday_pay_settings_id',
                    'regular_holiday_approved_leave',
                    'successive_holiday',
                    'company_id',
                    'holiday_pay_approval_settings',
                    );

        $w_uwd      = array(
                    "company_id"        => $company_id,
                    "status"            => 'Active',
                    );

        $this->db->where($w_uwd);
        $this->db->select($s);
        $tard = $this->db->get("holiday_pay_settings");
        $result = $tard->row();

        return $result;
    }

    public function get_rest_day_settings($company_id){

        $row_array  = array();

        $s          = array(
                    'enable_approval',
                    );

        $w_uwd      = array(
                    "company_id"        => $company_id,
                    "status"            => 'Active',
                    );

        $this->db->where($w_uwd);
        $this->db->select($s);
        $tard = $this->db->get("rest_day_settings");
        $result = $tard->row();

        $return = "no";
        if($result){
            $return = ($result->enable_approval == "yes") ? "yes" : "no";
        }

        return $return;
    }

    public function read_csv_contents($csv_file){
        $file_path =  $csv_file;
        $arr=array();
        $c = array();
        $row = 0;
        if (($handle = fopen($file_path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $num = count($data);
                for ($c = 0; $c < $num; $c++) {
                    if($row !=0) {
                        $arr[$row][$c]= trim($data[$c]);    
                    }
                }
                $row++;
            }
            fclose($handle);
            return $arr;
        }else{
            return false;
        }
    }

    public function error_checking_read_csv($company_id,$csv_file_or_input,$all_emp_id){

        $error      = array();
        $errorx     = array();
        $read_csv   = $this->read_csv_contents($csv_file_or_input);
        $row_start  = 2;
        $date_mix   = array();
        $conflict   = array();
        $list_arr   = array();
        $cloud      = $all_emp_id;
        $errorz_template = false;
        
        $validate_column    = iread_csv_parents($csv_file_or_input);
        $column_error   = 0;

        if ($validate_column) {
            foreach ($validate_column as $key=>$vc_val) {
                # insert code for validating entry per column of the csv file >>>> 19
                $col_num = 0;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "Employee Number"    ? '' : $column_error++);
                $col_num++;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "Date"   ? '' : $column_error++);
                $col_num++;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "Time In Date"   ? '' : $column_error++);
                $col_num++;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "Time-in Time"   ? '' : $column_error++);
                $col_num++;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "Lunch Out Date" ? '' : $column_error++);
                $col_num++;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "Lunch-out Time" ? '' : $column_error++);
                $col_num++;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "Lunch In Date"  ? '' : $column_error++);
                $col_num++;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "Lunch-in Time"  ? '' : $column_error++);
                $col_num++;

                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "First Break Out Date"   ? '' : $column_error++);
                $col_num++;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "First Break-out Time"   ? '' : $column_error++);
                $col_num++;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "First Break In Date"    ? '' : $column_error++);
                $col_num++;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "First Break-in Time"    ? '' : $column_error++);
                $col_num++;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "Second Break Out Date"  ? '' : $column_error++);
                $col_num++;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "Second Break-out Time"  ? '' : $column_error++);
                $col_num++;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "Second Break In Date"   ? '' : $column_error++);
                $col_num++;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "Second Break-in Time"   ? '' : $column_error++);
                $col_num++;

                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "Time Out Date"  ? '' : $column_error++);
                $col_num++;
                (isset($vc_val[$col_num])   && strlen($vc_val[$col_num]) > 0 && trim($vc_val[$col_num]) == "Time-out Time"  ? '' : $column_error++);
                $col_num++;
            }
        }
        
        if($column_error > 0){
            $error[] = "Important: Please note that we have a new format for uploading timesheets.";
            return array("error"=>$error,"overwrites"=>$conflict,"valid_csv"=>true,"continue" => "");
            exit();
        }

        if($read_csv) {
            $num    =  count($read_csv);
            $trimmedArray = array();
        
            if($num > 1){
                foreach($read_csv as $key=>$val){                       
                    $emp_id         = str_replace("'", "", $val[0]);
                    $gDate          = $val[1];
                    $time_in        = $val[2];
                    $time_in_time   = $val[3];

                    if($key == 0){
                        $time_out   = isset($val[17]) ? $val[17] : "";
                        $errorz_template = true;
                    }else{
                        if(trim($emp_id) != "" || $gDate !="" || $time_in !="" || $time_in_time !=""){
                        $trimmedArray[] = $val;
                        }
                    }
                }
            }
            
            if(empty($emp_id)){
                //$error[] = "Important: Please note that we have a new format for uploading timesheets.";
            }
            
            $keyx       = 0;
            $ego        = true;
            
            $emp_ids    = array();
            $range      = array();
            $min_range  = "";
            $max_range  = "";

            foreach($read_csv as $key=>$val){
                $emp_id                 = $this->replace_comma_singlequote($val[0]);
                $check                  = in_array_custom("pci-{$emp_id}",$cloud);
                if($check) {
                    $emp_ids[]          = $check->emp_id;
                }
                $date_this              = $this->replace_comma_singlequote($val[1]);
                $range[]                    = ($date_this) ? date('Y-m-d',strtotime($date_this)) : "";

                sort($range);
                $range_count            = count($range);

                if($range_count){
                    $min_range          = $range[0];
                    $max_range          = $range[$range_count-1];

                    $min_range          = date("Y-m-d",strtotime($min_range. " -1 day"));
                    $max_range          = date("Y-m-d",strtotime($max_range. " +1 day"));
                }
            }
            
            $get_all_employee_timein = $this->get_all_employee_timein($company_id,$emp_ids,$min_range,$max_range);

            foreach($read_csv as $key=>$val){   
                
                $allow                  = true;
                $emp_id                 = $this->replace_comma_singlequote($val[0]);
                $keyx++;
                $date_this              = $this->replace_comma_singlequote($val[1]);
                $gDate                  = ($date_this) ? date('Y-m-d',strtotime($date_this)) : "";

                $time_inx               = $this->replace_comma_singlequote($val[2]);
                $time_in_timex          = $this->replace_comma_singlequote($val[3]);

                $lunch_outx             = $this->replace_comma_singlequote($val[4]);
                $lunch_out_timex        = $this->replace_comma_singlequote($val[5]);
                $lunch_inx              = $this->replace_comma_singlequote($val[6]);
                $lunch_in_timex         = $this->replace_comma_singlequote($val[7]);

                $fbreak_outx            = $this->replace_comma_singlequote($val[8]);
                $fbreak_out_timex       = $this->replace_comma_singlequote($val[9]);
                $fbreak_inx             = $this->replace_comma_singlequote(isset($val[10]) ? $val[10] : "");
                $fbreak_in_timex        = $this->replace_comma_singlequote(isset($val[11]) ? $val[11] : "");

                $sbreak_outx            = $this->replace_comma_singlequote(isset($val[12]) ? $val[12] : "");
                $sbreak_out_timex       = $this->replace_comma_singlequote(isset($val[13]) ? $val[13] : "");
                $sbreak_inx             = $this->replace_comma_singlequote(isset($val[14]) ? $val[14] : "");
                $sbreak_in_timex        = $this->replace_comma_singlequote(isset($val[15]) ? $val[15] : "");

                $time_outx              = $this->replace_comma_singlequote(isset($val[16]) ? $val[16] : "");
                $time_out_timex         = $this->replace_comma_singlequote(isset($val[17]) ? $val[17] : "");

                $row_where              = $row_start++;
                
                if(empty($emp_id)){
                    $ego = false;
                }
                else{

                    $time_in            = date('Y-m-d H:i:s',strtotime($time_inx." ". $time_in_timex));

                    $lunch_out          = date('Y-m-d H:i:s',strtotime($lunch_outx." ".$lunch_out_timex));
                    $lunch_in           = date('Y-m-d H:i:s',strtotime($lunch_inx." ".$lunch_in_timex));

                    $fbreak_out         = date('Y-m-d H:i:s',strtotime($fbreak_outx." ".$fbreak_out_timex));
                    $fbreak_in          = date('Y-m-d H:i:s',strtotime($fbreak_inx." ".$fbreak_in_timex));

                    $sbreak_out         = date('Y-m-d H:i:s',strtotime($sbreak_outx." ".$sbreak_out_timex));
                    $sbreak_in          = date('Y-m-d H:i:s',strtotime($sbreak_inx." ".$sbreak_in_timex));

                    $time_out           = date('Y-m-d H:i:s',strtotime($time_outx." ".$time_out_timex));
                    
                    if($time_inx == "" || $time_in_timex == ""){

                        $time_in        = null;
                    }

                    if($lunch_outx  == "" || $lunch_out_timex == ""){

                        $lunch_out      = null;
                    }
                    if($lunch_inx == "" || $lunch_in_timex == ""){

                        $lunch_in       = null;
                    }

                    if($fbreak_outx  == "" || $fbreak_out_timex == ""){

                        $fbreak_out     = null;
                    }
                    if($fbreak_inx == "" || $fbreak_in_timex == ""){

                        $fbreak_in      = null;
                    }

                    if($sbreak_outx  == "" || $sbreak_out_timex == ""){

                        $sbreak_out     = null;
                    }
                    if($sbreak_inx == "" || $sbreak_in_timex == ""){

                        $sbreak_in      = null;
                    }

                    if($time_outx == "" || $time_out_timex == ""){

                        $time_out       = null;
                    }

                    if($lunch_out == "" || $lunch_in == ""){

                        $lunch_out      = null;
                        $lunch_in       = null;
                    }
                    if($fbreak_out == "" || $fbreak_in == ""){

                        $fbreak_out     = null;
                        $fbreak_in      = null;
                    }
                    if($sbreak_out == "" || $sbreak_in == ""){

                        $sbreak_out     = null;
                        $sbreak_in      = null;
                    }
                    
                    if(trim($emp_id,",") == ""){

                        $conflict[]     = "The employee does not have an ID in Row [$row_where]";
                        $list_arr[]     = $keyx;
                        $ego            = false;
                    }
                    
                    if($time_in ==""){

                        $error[]        = $emp_id." ".$time_out." has empty cell on row [$row_where]";
                        $list_arr[]     = $keyx;
                        $ego            = false;
                    }
                    
                    if($gDate == ""){

                        $error[]        = "Date has empty cell on row [$row_where]";
                        $list_arr[]     = $keyx;
                        $ego            = false;
                    }
                    else{
                        if($gDate == "1970-01-01"){
                            $error[]        = "Invalid Date Format on row [$row_where]";
                            $list_arr[]     = $keyx;
                            $ego            = false;
                        }
                    }
                    
                    // lunch break
                    if($time_in != "" && $lunch_out != "" && $lunch_in != "" && $time_out != "") {
                        
                        $time_in2       = $time_in;
                        $lunch_out2     = $lunch_out;
                        $lunch_in2      = $lunch_in;
                        $time_out2      = $time_out;
                        
                        if($lunch_in2 < $lunch_out2){

                            $error[]    = "Lunch Out Is Greater than Lunch In Row xxxx [".$row_where."] ";
                            $list_arr[] = $keyx;
                            $ego        = false;
                        }
                        
                        if($lunch_in2 > $time_out2){

                            $error[]    = "Lunch In Is Greater than Time Out in Row [".$row_where."] ";
                            $list_arr[] = $keyx;
                            $ego        = false;
                        }   
                        
                        if($lunch_out2 > $time_out2){

                            $error[]    = "Lunch Out Is Greater than Time Out in Row [".$row_where."] ";
                            $list_arr[] = $keyx;
                            $ego        = false;
                        }
                    }

                    // first break
                    if($time_in != "" && $fbreak_out != "" && $fbreak_in != "" && $time_out != "") {
                        
                        $time_in2       = $time_in;
                        $fbreak_out2    = $fbreak_out;
                        $fbreak_in2     = $fbreak_in;
                        $time_out2      = $time_out;
                        
                        if($fbreak_in2 < $fbreak_out2){

                            $error[]    = "First Break Out Is Greater than First Break In Row xxxx [".$row_where."] ";
                            $list_arr[] = $keyx;
                            $ego        = false;
                        }
                        
                        if($fbreak_in2 > $time_out2){

                            $error[]    = "First Break In Is Greater than Time Out in Row [".$row_where."] ";
                            $list_arr[] = $keyx;
                            $ego        = false;
                        }   
                        
                        if($fbreak_out2 > $time_out2){

                            $error[]    = "First Break Out Is Greater than Time Out in Row [".$row_where."] ";
                            $list_arr[] = $keyx;
                            $ego        = false;
                        }
                    }

                    // Second break
                    if($time_in != "" && $sbreak_out != "" && $sbreak_in != "" && $time_out != "") {
                        
                        $time_in2       = $time_in;
                        $sbreak_out2    = $sbreak_out;
                        $sbreak_in2     = $sbreak_in;
                        $time_out2      = $time_out;
                        
                        if($sbreak_in2 < $sbreak_out2){
                            $error[]    = "Second Break Out Is Greater than Second Break In Row xxxx [".$row_where."] ";
                            $list_arr[] = $keyx;
                            $ego        = false;
                        }
                        
                        if($fbreak_in2 > $time_out2){
                            $error[]    = "Second Break In Is Greater than Time Out in Row [".$row_where."] ";
                            $list_arr[] = $keyx;
                            $ego        = false;
                        }   
                        
                        if($fbreak_out2 > $time_out2){
                            $error[]    = "Second Break Out Is Greater than Time Out in Row [".$row_where."] ";
                            $list_arr[] = $keyx;
                            $ego        = false;
                        }
                    }

                    if($time_in && $time_out) {
                        if(idate_convert($time_in) > idate_convert($time_out)){
                            $error[]    = "Time In Is Greater than Time Out in Rowx [".$row_where."]";
                            $list_arr[] = $keyx;
                            $allow      = false;
                            $ego        = false;
                        }
                    }
                    
                    $date               = idate_convert($time_in);
                    $check              = in_array_custom("pci-{$emp_id}",$cloud);
                    
                    if($check) {

                        $get_emp_id             = $check->emp_id;
                        $employee_timein_date   = date("Y-m-d",strtotime($date));

                        //adde aldrin here
                        $employee_timein_date2  = date('Y-m-d',strtotime($date. " -1 day"));
                        $work_schedule_id       = $this->emp_work_schedule($get_emp_id,$company_id,$employee_timein_date2);
                        $previous_split_sched   = $this->elm->yesterday_split_info($time_in,$get_emp_id,$work_schedule_id,$company_id);
                        $child                  = true;

                        if($previous_split_sched){

                            $last_sched         = max($previous_split_sched);
                            $time_inx           = date('Y-m-d H:i:s',strtotime($time_in));
                            
                            if( $time_inx <= $last_sched['end_time']){
                                $yesterday_m            = date('Y-m-d',strtotime($time_in));
                                $employee_timein_date   = date('Y-m-d',strtotime($yesterday_m. " -1 day")); 
                                $date                   = date('Y-m-d',strtotime($yesterday_m. " -1 day"));
                                $child                  = false;
                            }
                        }
                        
                        $date                   = $gDate;
                        $employee_timein_date   = $gDate;
                        $check_timeins          = in_array_custom("emp_id_timeins_date_{$get_emp_id}_{$date}",$get_all_employee_timein);
                        $workschedule           = $this->emp_work_schedule($get_emp_id,$company_id,$employee_timein_date);
                        $split                  = false;

                        if(!$split){
                            if($check_timeins){
                                $conflict[]     = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($check_timeins->date))." already exists in the database on row [$row_where]";                                    
                                $allow          = false;
                                $date_mix[]     = $keyx;
                            }
                        }
                        else{

                            $split_timeins      = $this->check_employee_timeins_split($this->company_info->company_id,$get_emp_id,$date);

                            if($check_timeins && $child){  
                                $conflict[]     = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($check_timeins->date))." already exists in the database on row [$row_where]";
                                $ego            = false;
                            }
                        }

                        if($workschedule == false && $allow){
    
                            $w                  = $this->if_no_workschedule($get_emp_id,$company_id);
                            //last_query();
                            if($w == 0|| $w == NULL || $w == ""){
                                $error[]        = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($date))." is not assigned to a Payroll group row [$row_where]. Please assign a Payroll Group on Workforce";
                                $list_arr[]     = $keyx;
                                $ego            = false;
                            }
                        }
                    }
                    else {
                        $error[]                = "Employee doesn't exist (".$emp_id.") in row [$row_where]";
                        $list_arr[]             = $keyx;
                        $ego                    = false;
                    }
                } 
            
                if($ego){

                    $errorx[]                   = $keyx;
                }
                else{
                    $ego                        = true;
                }
            }
            
            $list_arr = array_unique($list_arr);
            
            $list_err = array_unique($errorx);
            $d =0;
            
            $num_csv = count($read_csv);
            $continue = false;
            for($x = 1; $x <= $num_csv;$x++){
                foreach($list_err as $rox){
                    if($rox == $x){
                        $d = 1;
                    }
                }
            }
            
            if($d == 1){
                $continue = true;
            }
            
            $this->session->set_userdata('date_mix',$date_mix);
            $this->session->set_userdata('list_conflict',$list_arr);
            return array("error"=>$error,"overwrites"=>$conflict,"valid_csv"=>true,"continue" => $continue);
        }else{
            return array("error"=>"","overwrites"=>"","valid_csv"=>false);
        }
    }

    public function save_import($company_id,$csv,$all_emp_id){
        $con                = $this->session->userdata('list_conflict');
        $date_mix           = $this->session->userdata('date_mix');

        if($company_id) {
            
            $cloud          = $all_emp_id;
            
            $file           = fopen($csv,"r") or die("Exit") ;
            $row_start      = 0;
            $conflict       = array();
            $toboy          = array();
            $num            = 1;
            
            $range          = array();
            $emp_ids        = array();
            $min_range      = "";
            $max_range      = "";

            while(!feof($file)):
                $read_csv   = fgetcsv($file);

                if($row_start > 0) {

                    $payroll_cloud_id       = $this->replace_comma_singlequote($read_csv[0]);
                    $date_this              = $this->replace_comma_singlequote($read_csv[1]);
                    $gDate                  = ($date_this) ? date('Y-m-d',strtotime($date_this)) : "";
                    $check                  = in_array_custom("pci-{$payroll_cloud_id}",$cloud);


                    $good                   = true;

                    

                    if(isset($con)){
                        if(is_array($con)){
                            foreach($con as $rows){                                 
                                if($rows == $num){
                                    $good = false;                                      
                                }
                            }
                        }
                    }

                    if($date_mix){
                        foreach($date_mix as $allow){
                            if($allow == $num){
                                $good = true;
                            }
                        }
                    }

                    if($check && $good){
                        $emp_id             = $check->emp_id;
                        $range[]            = $gDate;
                        $emp_ids[]          = $emp_id;
                    }
                    $num++;
                }
                $row_start++;
            endwhile;
            fclose($file);

            sort($range);
            $range_count            = count($range);

            if($range_count){
                $min_range          = $range[0];
                $max_range          = $range[$range_count-1];

                $min_range          = date("Y-m-d",strtotime($min_range. " -1 day"));
                $max_range          = date("Y-m-d",strtotime($max_range. " +1 day"));
            }

            $get_employee_payroll_information   = $this->get_employee_payroll_information($company_id,$emp_ids);
            $emp_employe_account_info           = $this->emp_employe_account_info($company_id,$emp_ids);
            $emp_work_schedule_ess              = $this->emp_work_schedule_ess($company_id,$emp_ids);
            $emp_work_schedule_epi              = $this->emp_work_schedule_epi($company_id,$emp_ids);
            $list_of_blocks                     = $this->list_of_blocks($company_id,$emp_ids);
            $get_all_schedule_blocks            = $this->get_all_schedule_blocks($company_id);
            $get_all_regular_schedule           = $this->get_all_regular_schedule($company_id);
            $check_rest_days                    = $this->check_rest_day($company_id);
            $get_work_schedule_flex             = $this->get_work_schedule_flex($company_id);
            $company_holiday                    = $this->company_holiday($company_id);
            $get_work_schedule                  = $this->get_work_schedule($company_id);
            $get_employee_leave_application     = $this->get_employee_leave_application($company_id,$emp_ids);
            $get_tardiness_settings             = $this->get_tardiness_settings($company_id);
            $get_all_employee_timein            = $this->get_all_employee_timein($company_id,$emp_ids,$min_range,$max_range);
            $get_all_schedule_blocks_time_in    = $this->get_all_schedule_blocks_time_in($company_id,$emp_ids,$min_range,$max_range);
            $attendance_hours                   = is_attendance_active($company_id);
            $get_tardiness_settingsv2           = $this->get_tardiness_settingsv2($company_id);
            $tardiness_rounding                 = $this->tardiness_rounding($company_id);
            $get_nigtdiff_rule                  = $this->get_nigtdiff_rule($company_id);
            $get_shift_restriction_schedules    = $this->get_shift_restriction_schedules($company_id);

            // for open shift
            $period_types                       = array();
            $get_all_employee_period_typex      = array();
            $get_all_payroll_calendarx          = array();
            $comp_ids                           = array($company_id);

            $get_all_employee_period_typex      = $this->get_all_employee_period_type($emp_ids);

            if($get_all_employee_period_typex){
                foreach ($get_all_employee_period_typex as $key => $period_typex) {
                    $period_types[]                                 = $period_typex['period_type'];
                }
            }

            if($period_types){
                $get_all_payroll_calendarx      = $this->get_all_payroll_calendar($period_types,$comp_ids);
            }
            // end for open shift

            // holiday settings
            $holiday_settings                   = $this->get_holiday_pay_settings($company_id);
            $holiday_settings_appr              = "no";
            if($holiday_settings){
                if($holiday_settings->holiday_pay_approval_settings == "yes"){
                    $holiday_settings_appr      = "yes";
                }
            }

            // restday settings
            $rest_day_settings                  = $this->get_rest_day_settings($company_id);

            $filex                              = fopen($csv,"r") or die("Exit") ;

            while(!feof($filex)):

                $read_csv   = fgetcsv($filex);
                if($row_start > 0) {

                    $payroll_cloud_id       = $this->replace_comma_singlequote($read_csv[0]);
                    $date_this              = $this->replace_comma_singlequote($read_csv[1]);
                    $gDate                  = ($date_this) ? date('Y-m-d',strtotime($date_this)) : "";

                    $time_inx               = $this->replace_comma_singlequote($read_csv[2]);
                    $time_in_timex          = $this->replace_comma_singlequote($read_csv[3]);

                    $lunch_outx             = $this->replace_comma_singlequote($read_csv[4]);
                    $lunch_out_timex        = $this->replace_comma_singlequote($read_csv[5]);
                    $lunch_inx              = $this->replace_comma_singlequote($read_csv[6]);
                    $lunch_in_timex         = $this->replace_comma_singlequote($read_csv[7]);

                    $fbreak_outx            = $this->replace_comma_singlequote($read_csv[8]);
                    $fbreak_out_timex       = $this->replace_comma_singlequote($read_csv[9]);
                    $fbreak_inx             = $this->replace_comma_singlequote($read_csv[10]);
                    $fbreak_in_timex        = $this->replace_comma_singlequote($read_csv[11]);

                    $sbreak_outx            = $this->replace_comma_singlequote($read_csv[12]);
                    $sbreak_out_timex       = $this->replace_comma_singlequote($read_csv[13]);
                    $sbreak_inx             = $this->replace_comma_singlequote($read_csv[14]);
                    $sbreak_in_timex        = $this->replace_comma_singlequote($read_csv[15]);

                    $time_outx              = $this->replace_comma_singlequote($read_csv[16]);
                    $time_out_timex         = $this->replace_comma_singlequote($read_csv[17]);

                    $check                  = in_array_custom("pci-{$payroll_cloud_id}",$cloud);
                    $good                   = true;

                    //dont import if the schedule has an error
                    //except for no schedule, it has a default workschedule
                    if(isset($con)){
                        if(is_array($con)){
                            foreach($con as $rows){                                 
                                if($rows == $num){
                                    $good = false;                                      
                                }
                            }
                        }
                    }
                    
                    if($date_mix){
                        foreach($date_mix as $allow){
                            if($allow == $num){
                                $good = true;
                            }
                        }
                    }
                    
                    if($check && $good){
                        $emp_id             = $check->emp_id;
                        $time_in            = date('Y-m-d H:i:s',strtotime($time_inx." ". $time_in_timex));

                        $lunch_out          = date('Y-m-d H:i:s',strtotime($lunch_outx." ".$lunch_out_timex));
                        $lunch_in           = date('Y-m-d H:i:s',strtotime($lunch_inx." ".$lunch_in_timex));

                        $fbreak_out         = date('Y-m-d H:i:s',strtotime($fbreak_outx." ".$fbreak_out_timex));
                        $fbreak_in          = date('Y-m-d H:i:s',strtotime($fbreak_inx." ".$fbreak_in_timex));

                        $sbreak_out         = date('Y-m-d H:i:s',strtotime($sbreak_outx." ".$sbreak_out_timex));
                        $sbreak_in          = date('Y-m-d H:i:s',strtotime($sbreak_inx." ".$sbreak_in_timex));

                        $time_out           = date('Y-m-d H:i:s',strtotime($time_outx." ".$time_out_timex));
                        
                        if($time_inx == "" || $time_in_timex == ""){

                            $time_in        = null;
                        }

                        if($lunch_outx  == "" || $lunch_out_timex == ""){

                            $lunch_out      = null;
                        }
                        if($lunch_inx == "" || $lunch_in_timex == ""){

                            $lunch_in       = null;
                        }

                        if($fbreak_outx  == "" || $fbreak_out_timex == ""){

                            $fbreak_out     = null;
                        }
                        if($fbreak_inx == "" || $fbreak_in_timex == ""){

                            $fbreak_in      = null;
                        }

                        if($sbreak_outx  == "" || $sbreak_out_timex == ""){

                            $sbreak_out     = null;
                        }
                        if($sbreak_inx == "" || $sbreak_in_timex == ""){

                            $sbreak_in      = null;
                        }

                        if($time_outx == "" || $time_out_timex == ""){

                            $time_out       = null;
                        }

                        if($lunch_out == "" || $lunch_in == ""){

                            $lunch_out      = null;
                            $lunch_in       = null;
                        }

                        if($fbreak_out == "" || $fbreak_in == ""){

                            $fbreak_out     = null;
                            $fbreak_in      = null;
                        }

                        if($sbreak_out == "" || $sbreak_in == ""){

                            $sbreak_out     = null;
                            $sbreak_in      = null;
                        }

                        $check_timeins       = in_array_custom2ustom("emp_id_timeins_date_{$emp_id}_{$gDate}",$get_all_employee_timein);
                        $employee_time_in_id = 0;
                        $last_source         = "";

                        if($check_timeins){
                            $employee_time_in_id    = $check_timeins->employee_time_in_id;
                            $last_source            = $check_timeins->last_source;
                            $last_source            = ($last_source) ? $last_source : $check_timeins->source;
                        }
                        
                        //$this->import_new($company_id,$emp_id,$time_in,$lunch_out,$lunch_in,$time_out,"import",$payroll_cloud_id,$gDate,"",$employee_time_in_id);
                        $this->import_new($company_id,$emp_id,$time_in,$lunch_out,$lunch_in,$fbreak_out,$fbreak_in,$sbreak_out,$sbreak_in,$time_out,"import",$payroll_cloud_id,$gDate,"",$employee_time_in_id,$last_source,$get_employee_payroll_information,$emp_employe_account_info,$emp_work_schedule_ess,$emp_work_schedule_epi,$list_of_blocks,$get_all_schedule_blocks,$get_all_regular_schedule,$check_rest_days,$get_work_schedule_flex,$company_holiday,$get_work_schedule,$get_employee_leave_application,$get_tardiness_settings,$get_all_employee_timein,$get_all_schedule_blocks_time_in,$attendance_hours,$get_tardiness_settingsv2,$tardiness_rounding,true,$holiday_settings_appr,false,$rest_day_settings,false,$get_all_employee_period_typex,$get_all_payroll_calendarx,$get_nigtdiff_rule,$get_shift_restriction_schedules);

                        $toboy[] = $this->db->insert_id();
                    }
                    $num++;
                }
                $row_start++;
            endwhile;

            fclose($file);
            $con = $this->session->set_userdata('list_conflict','');
            $this->session->set_userdata('date_mix','');
            return $toboy;
        }
    }

    public function if_no_workschedule($emp_id,$comp_id){
        $w = array(
                "epi.emp_id"=>$emp_id,
                "epi.company_id"=>$comp_id
        );
        $this->db->where($w);
        
        $arrx = array(
                'work_schedule_id' => 'pg.work_schedule_id'
        );
        $this->db->select($arrx);
        $this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $q = $this->db->get("employee_payroll_information AS epi");
        $r = $q->row();
        
        return ($r) ?  $r->work_schedule_id : 0;
    }

    public function replace_comma_singlequote($data){
        $text = "";
        $data = isset($data) ? $data : "";
        if($data !="") {
            $text = trim(str_replace(array('"', "'"),"",$data));
        }
        
        return $this->db->escape_str($text);
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

    public function calculate_attendance($company_id,$time_in,$time_out,$attendance_hours){

        if($attendance_hours){
            $h      = $attendance_hours * 60;
            $calc   = $this->total_hours_worked($time_out, $time_in);
    
            if($calc <= $h){
                return true;
            }
        }
        return false;
    }

    public function update_here(){
        $w = array(
            'comp_id' => "316",
            'total_hours_required >' => 8,
            'status' => 'Active'
            );
        $field = array(
            'total_hours_required' => '8'
            );
        $this->db->where($w);
        $this->db->update('employee_time_in',$field);
        last_query();
    }

    //safety start

    public function get_all_employee_pgid($comp_id,$payroll_group_id){
        $s  = array('e.emp_id');
        $w  = array(
            'e.status'              => 'Active',
            'e.company_id'          => $comp_id,
            'epi.payroll_group_id'  => $payroll_group_id,
            'epi.employee_status'   => 'Active',
            );
        $this->db->select($s);
        $this->db->where($w);
        $this->db->join('employee_payroll_information AS epi','epi.emp_id = e.emp_id');
        $q  = $this->db->get('employee AS e'); 
        $r  = $q->result();

        $emp_id = array();
        if($r){
            foreach ($r as $key => $value) {
                $emp_id[] = $value->emp_id;
            }
        }
        return $emp_id;
    }

    public function safety($company_id,$date_from,$date_to,$payroll_group=false,$emp_idsx=array()){
        
        //// **** RULES FOR CRONJOB TIMESHEET RECALCULATION
        // get all employee changes their shifts 'change_shift_schedule_ar'
        // check if this dates employee has timesheet 'employe'
        // recalculate ...
        // done!!!
        $emp_ids_pg = false;
        if(!$emp_idsx){
            if($payroll_group){
                $emp_ids_pg = $this->get_all_employee_pgid($company_id,$payroll_group);
            }
        }else{
            $emp_ids_pg = $emp_idsx;
        }

        $step1      = true;
        $step2      = false;
        $step3      = true;
        $step4      = true;
        $tardy      = 1;

        $emp_ids    = array();
        $dates      = array();

        $s = array(
                'change_shift_schedule_ar_id',
                'emp_id',
                'company_id',
                'date_from',
                'date_to'
            );
        $w = array(
                'status' => 'Active',
                'company_id' => $company_id,
            );
        $gb = array(
                'emp_id',
                'company_id',
                'date_from'
            );

        $this->db->select($s);
        $this->db->where($w);
        if($emp_ids_pg){
            $this->db->where_in("emp_id",$emp_ids_pg);
        }
        $this->db->where(' (date_from BETWEEN "'.$date_from.'" AND "'.$date_to.'")',NULL,FALSE);
        $this->db->group_by($gb);
        $this->db->order_by('company_id', 'ASC');
        $q = $this->db->get('change_shift_schedule_ar');
        $r = $q->result();

        
        if($r){
            foreach ($r as $key => $value) {
                if (!in_array($value->emp_id, $emp_ids)) {
                    array_push($emp_ids, $value->emp_id);
                }

                if (!in_array($value->date_from, $dates)) {
                    array_push($dates, $value->date_from);
                }
            }
        }else{
            $step1 = false;
        }

        if($step1){
            $row_array      = array();
            $timein_where   = array(
                                "flag_regular_or_excess" => "regular",
                                "status"                 => "Active"
                            );

            $this->db->where(' (date BETWEEN "'.$date_from.'" AND "'.$date_to.'")',NULL,FALSE);
            $this->db->where($timein_where);
            $this->db->where_in("emp_id",$emp_ids);
            $q2     = $this->db->get("employee_time_in");
            $r_pg   = $q2->result();
            
            if($r_pg){
                if($r_pg){
                    foreach ($r_pg as $r1){
                        $wd     = array(
                                "emp_id"                => $r1->emp_id,
                                "comp_id"               => $r1->comp_id,
                                "time_in"               => $r1->time_in,
                                "lunch_out"             => $r1->lunch_out,
                                "lunch_in"              => $r1->lunch_in,

                                "break1_out"            => $r1->break1_out,
                                "break1_in"             => $r1->break1_in,
                                "break2_out"            => $r1->break2_out,
                                "break2_in"             => $r1->break2_in,

                                "time_out"              => $r1->time_out,
                                "employee_time_in_id"   => $r1->employee_time_in_id,
                                "last_source"           => $r1->last_source,
                                "source"                => $r1->source,
                                "date"                  => $r1->date,
                                "custom_search"         => "eti-{$r1->emp_id}-{$r1->date}-{$r1->comp_id}"
                        );
                        array_push($row_array,$wd);
                    }
                }
            }
            if($row_array){
                $step2 = true;
            }
        }

        $s  = array(
            'emp_id',
            'employee_time_in_id',
            'time_in',
            'lunch_out',
            'lunch_in',
            'break1_out',
            'break1_in',
            'break2_out',
            'time_in',
            'break2_in',
            'time_out',
            'date',
            );
        $w  = array(
            'flag_regular_or_excess'    => 'regular',
            'status'                    => 'Active',
            //'work_schedule_id !='     => '-1',
            );

        if($company_id){
            $w['comp_id'] = $company_id;
        }

        if($date_from && $date_to){
            $from   = date('Y-m-d',strtotime($date_from));
            $to     = date('Y-m-d',strtotime($date_to));
            $this->db->where("date BETWEEN '{$from}' AND '{$to}'");
        }
        else if($from){
            $w['date'] = $from;
        }else{
            $cur_time = date("H:i:s");
            $cur_date = date('Y-m-d');
            if(strtotime($cur_time) < strtotime("12:00:00")){
                $cur_date = date("Y-m-d",strtotime($cur_date));
            }
            
            $w['date'] = $cur_date;
        }

        if($tardy){
            $this->db->where('(total_hours_required <= 0 or tardiness_min >= '.$tardy.' or undertime_min >= '.$tardy.')');
        }
        else{
            $this->db->where('(total_hours_required <= 0 or tardiness_min >= 0 or undertime_min >= 0)');
        }
        
        if($emp_ids_pg){
            $this->db->where_in("emp_id",$emp_ids_pg);
        }

        $this->db->select($s);
        $this->db->where($w);
        
        $this->db->order_by('emp_id ASC, date ASC, employee_time_in_id ASC');
        $q = $this->db->get('employee_time_in');
        $rzz = $q->result();
        $rxx = $q->num_rows();

        $emp_id                     = array();
        $employee_time_in_id_arr    = array();
        $data_insert                = array();
        if($rzz){

            foreach ($rzz as $key => $value) {
                $emp_id[] = $value->emp_id;
                $employee_time_in_id_arr[] = $value->employee_time_in_id;
            }
        }

        if($rzz){
            foreach($rzz as $r1){
                if($r1->time_out){
                    $data_insert[]      = array(
                                        $r1->emp_id,
                                        $r1->time_in,
                                        $r1->lunch_out,
                                        $r1->lunch_in,
                                        $r1->break1_out,
                                        $r1->break1_in,
                                        $r1->break2_out,
                                        $r1->break2_in,
                                        $r1->time_out,
                                        "",
                                        "",
                                        "",
                                        $r1->date,
                                        $r1->employee_time_in_id
                                        );
                }
            }
        }

        if($data_insert){
            //$step2 = true;
        }


        if($step2){

            if($r){
                foreach ($r as $key => $value1) {
                    
                    $recalculate_this           = in_array_custom("eti-{$value1->emp_id}-{$value1->date_from}-{$value1->company_id}",$row_array);
                    
                    if($recalculate_this){
                        if($recalculate_this->time_out){
                            $data_insert[]      = array(
                                                $recalculate_this->emp_id,
                                                $recalculate_this->time_in,
                                                $recalculate_this->lunch_out,
                                                $recalculate_this->lunch_in,
                                                $recalculate_this->break1_out,
                                                $recalculate_this->break1_in,
                                                $recalculate_this->break2_out,
                                                $recalculate_this->break2_in,
                                                $recalculate_this->time_out,
                                                "",
                                                "",
                                                "",
                                                $recalculate_this->date,
                                                $recalculate_this->employee_time_in_id
                                                );
                        }
                    }

                    $arrupdate = array('change_shift_schedule_ar_id' => $value1->change_shift_schedule_ar_id);
                    $arrfield  = array('status' => "Inactive");
                    eupdate('change_shift_schedule_ar',$arrfield,$arrupdate);
                }
            }
        }

        if($data_insert){
            $return_this = $this->save_create_new_safe($company_id,$data_insert,"recalculated",$migrate_v3="1",true);
        }
    }

    public function save_create_new_safe($company_id,$data_entry= array(),$source="",$migrate_v3 = false,$cronjob_time_recalculate=false){

        $con            = "";
        $date_mix       = "";
        $total_count    = 0;
        $process        = 0;

        if($company_id) {

            $range                      = array();
            $emp_ids                    = array();
            $min_range                  = "";
            $max_range                  = "";
            $num                        = 0;
            if($data_entry){

                foreach ($data_entry AS $app){ 

                    $good               = true;
                    if($con){
                        foreach($con as $rows){
                            if($rows == $num){
                                $good   = false;                                
                            }
                        }
                    }
                    
                    if($date_mix){
                        foreach($date_mix as $allow){
                            if($allow == $num){
                                $good = true;
                                
                            }
                        }
                    }

                    if($good){
                        $range[]    = $app[8];
                        $emp_ids[]  = $app[0];
                        $total_count++;
                    }
                    $num++; 
                }
            }
            sort($range);
            $range_count            = count($range);

            if($range_count){
                $min_range          = $range[0];
                $max_range          = $range[$range_count-1];

                $min_range          = date("Y-m-d",strtotime($min_range. " -1 day"));
                $max_range          = date("Y-m-d",strtotime($max_range. " +1 day"));
            }
            
            // parent functions to be use
            $get_employee_payroll_information   = $this->get_employee_payroll_information($company_id,$emp_ids);
            $emp_employe_account_info           = $this->emp_employe_account_info($company_id,$emp_ids);
            $emp_work_schedule_ess              = $this->emp_work_schedule_ess($company_id,$emp_ids);
            $emp_work_schedule_epi              = $this->emp_work_schedule_epi($company_id,$emp_ids);
            $list_of_blocks                     = $this->list_of_blocks($company_id,$emp_ids);
            $get_all_schedule_blocks            = $this->get_all_schedule_blocks($company_id);
            $get_all_regular_schedule           = $this->get_all_regular_schedule($company_id);
            $check_rest_days                    = $this->check_rest_day($company_id);
            $get_work_schedule_flex             = $this->get_work_schedule_flex($company_id);
            $company_holiday                    = $this->company_holiday($company_id);
            $get_work_schedule                  = $this->get_work_schedule($company_id);
            $get_employee_leave_application     = $this->get_employee_leave_application($company_id,$emp_ids);
            $get_tardiness_settings             = $this->get_tardiness_settings($company_id);
            $get_all_employee_timein            = $this->get_all_employee_timein($company_id,$emp_ids,$min_range,$max_range);
            $get_all_schedule_blocks_time_in    = $this->get_all_schedule_blocks_time_in($company_id,$emp_ids,$min_range,$max_range);
            $attendance_hours                   = false;//is_attendance_active($company_id);
            $get_tardiness_settingsv2           = $this->get_tardiness_settingsv2($company_id);
            $tardiness_rounding                 = $this->tardiness_rounding($company_id);
            $holiday_settings                   = $this->get_holiday_pay_settings($company_id);
            $rest_day_settings                  = $this->get_rest_day_settings($company_id);
            $get_nigtdiff_rule                  = $this->get_nigtdiff_rule($company_id);
            $get_shift_restriction_schedules    = $this->get_shift_restriction_schedules($company_id);

            $holiday_settings_appr              = "no";
            if($holiday_settings){
                if($holiday_settings->holiday_pay_approval_settings == "yes"){
                    $holiday_settings_appr      = "yes";
                }
            }

            $row_start                          = 0;
            $conflict                           = array();
            $toboy                              = array();
            $num                                = 0;

            if($data_entry) {

                foreach($data_entry as $row) {

                    $payroll_cloud_id           = $this->db->escape_str($row[7]);
                    $time_in                    = $row[1];
                    $lunch_out                  = $row[2];
                    $lunch_in                   = $row[3];

                    if($migrate_v3){

                        $fbreak_out             = $row[4];
                        $fbreak_in              = $row[5];
                        $sbreak_out             = $row[6];
                        $sbreak_in              = $row[7];

                        $time_out               = $row[8];
                        $first_name             = $row[9];
                        $last_name              = $row[10];

                        $gDate                  = isset($row[12]) ? $row[12] : "" ;
                        $employee_time_in_id    = isset($row[13]) ? $row[13] : "";
                        $last_source            = isset($row[14]) ? $row[14] : "";
                        $inclusion_hours        = isset($row[15]) ? $row[15] : "";

                    }else{

                        $fbreak_out             = "";
                        $fbreak_in              = "";
                        $sbreak_out             = "";
                        $sbreak_in              = "";

                        $time_out               = $row[4];
                        $first_name             = $row[5];
                        $last_name              = $row[6];

                        $gDate                  = isset($row[8]) ? $row[8] : "" ;
                        $employee_time_in_id    = isset($row[9]) ? $row[9] : "";
                        $last_source            = isset($row[10]) ? $row[10] : "";
                    }

                    $good                       = true;

                    if($con){
                        foreach($con as $rows){
                            if($rows == $num){
                                $good = false;                              
                            }
                        }
                    }
                    
                    if($date_mix){
                        foreach($date_mix as $allow){
                            if($allow == $num){
                                $good = true;
                                
                            }
                        }
                    }
                    
                    if($good){
                        
                        $process++;

                        if($time_in ==""){
                            $time_in = null;
                        }
                        if($lunch_out == "" || $lunch_out == ""){
                            $lunch_out = null;
                        }
                        if($lunch_in == "" || $lunch_in == ""){
                            $lunch_in = null;
                        }
                        if($time_out == ""){
                            $time_out = null;
                        }

                        $this->import_new($company_id,$row[0],$time_in,$lunch_out,$lunch_in,$fbreak_out,$fbreak_in,$sbreak_out,$sbreak_in,$time_out,$source,"",$gDate,"",$employee_time_in_id,$last_source,$get_employee_payroll_information,$emp_employe_account_info,$emp_work_schedule_ess,$emp_work_schedule_epi,$list_of_blocks,$get_all_schedule_blocks,$get_all_regular_schedule,$check_rest_days,$get_work_schedule_flex,$company_holiday,$get_work_schedule,$get_employee_leave_application,$get_tardiness_settings,$get_all_employee_timein,$get_all_schedule_blocks_time_in,$attendance_hours,$get_tardiness_settingsv2,$tardiness_rounding,$migrate_v3,$holiday_settings_appr,$inclusion_hours,$rest_day_settings,$cronjob_time_recalculate,false,false,$get_nigtdiff_rule,$get_shift_restriction_schedules);


                        $recal_filename = "timesheet_recalc";
                        $counter_emp = 10;
                        $date_recal_folder = "safty_recalc_logs";
                        $dir = "uploads/companies/{$this->company_id}/timesheet_recal_cnt_folder/";

                        $percent = ($process/$total_count) * 100;
                        $message = "recomputing absences, tardiness and undertime against schedule for: {$process} out of {$total_count}";
                        if($date_recal_folder !=""){
                            if(!is_dir($dir.$date_recal_folder)) {
                                
                                $folder = array("folder"=>$date_recal_folder);
                                foreach($folder as $key){
                                    $old = umask(0);
                                    mkdir($dir.$key,0777,true);
                                    umask($old);
                                }
                                
                                $dir_date = "uploads/companies/{$this->company_id}/timesheet_recal_cnt_folder/{$date_recal_folder}/";
                                $myfile = fopen("{$dir_date}{$recal_filename}.json", "w") or die("Unable to open file!");
                                $recal_val_array = array("percent"=>$percent,"message"=>$message);
                                fwrite($myfile, json_encode($recal_val_array));
                                fclose($myfile);
                                
                            }
                            else{
                       
                                $dir_date = "uploads/companies/{$this->company_id}/timesheet_recal_cnt_folder/{$date_recal_folder}/";
                                $myfile = fopen("{$dir_date}{$recal_filename}.json", "w") or die("Unable to open file!");
                                $recal_val_array = array("percent"=>$percent,"message"=>$message);
                                fwrite($myfile, json_encode($recal_val_array));
                                fclose($myfile);
                                
                            }
                        }
                    }
                    else{
                        $toboy = array();
                    }
                    $num++;
                }
            }
        }
    }

    public function get_all_employee_period_type($emp_ids){
        
        $row_array  = array();
        
        $s          = array(
                    'epi.emp_id',
                    'epi.payroll_group_id',
                    'pg.name',
                    'pg.period_type',
                    'pg.company_id',
                    );
        $w = array(
            "pg.status"     => "Active"
        );
        if($emp_ids){
            $this->db->where_in("epi.emp_id",$emp_ids);
        }
        $this->db->select($s);
        $this->db->where($w);
        $this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id");
        $q = $this->db->get("employee_payroll_information AS epi");
        $r = $q->result();

        if($r){
            foreach ($r as $r1){
                $wd     = array(
                        "emp_id"                => $r1->emp_id,
                        "payroll_group_id"      => $r1->payroll_group_id,
                        "name"                  => $r1->name,
                        "period_type"           => $r1->period_type,
                        "custom_search"         => "pg_id_{$r1->payroll_group_id}",
                        "custom_searchv2"       => "comp_{$r1->company_id}",
                        "custom_searchv3"       => "emp_{$r1->emp_id}",
                        );
                        array_push($row_array,$wd);
            }
        }

        return $row_array;
    }

    public function get_all_payroll_calendar($period_types,$comp_ids){

        $row_array  = array();
        $curr_date  = date("Y-m-d");
        $curr_date  = date("Y-m-d",strtotime($curr_date."+ 30 days"));
        $min_date   = date("Y-m-d",strtotime($curr_date." - 60 days"));
        $s          = array(
                    'pay_schedule',
                    'first_payroll_date',
                    'cut_off_from',
                    'cut_off_to',
                    'company_id',
                    );

        $w_uwd      = array(
                    "status"            => 'Active',
                    "cut_off_to <="     => $curr_date,
                    "cut_off_to >"      => $min_date,
                    );
        if($period_types){
            $this->db->where_in("pay_schedule",$period_types);
            $this->db->where_in("company_id",$comp_ids);
        }
        $this->db->where($w_uwd);
        $this->db->select($s);
        $calendar   = $this->db->get("payroll_calendar");
        $result     = $calendar->result();
        
        if($result){
            foreach ($result as $key =>$r1){
                $wd     = array(
                        "pay_schedule"          => $r1->pay_schedule,
                        "first_payroll_date"    => $r1->first_payroll_date,
                        "cut_off_from"          => $r1->cut_off_from,
                        "cut_off_to"            => $r1->cut_off_to,
                        "company_id"            => $r1->company_id,
                        "custom_search"         => "pay_sched_{$r1->pay_schedule}_{$r1->company_id}_{$r1->cut_off_to}",
                        );

                array_push($row_array, $wd);
            }
        }

        return $row_array;
    }

    public function payroll_lock_checker($comp_ids){
        // lock update
        $s          = array(
                    'ts_recalc',
                    'ts_recalc_time',
                    'py_recalc',
                    'py_recalc_time',
                    'suspend_all_application',
                    'updated_date',
                    );

        $w_uwd      = array(
                    "company_id"            => $comp_ids,
                    );
    
        $this->db->where($w_uwd);
        $this->db->select($s);
        $ps     = $this->db->get("lock_payroll_process_settings");
        $result     = $ps->row();

        return $result;
    }

    public function payroll_lock_checkerv2($comp_ids){
        // lock update
        $s          = array(
                    'ts_recalc',
                    'ts_recalc_time',
                    'py_recalc',
                    'py_recalc_time',
                    'suspend_all_application',
                    'updated_date',
                    );

        $w_uwd      = array(
                    "company_id"            => $comp_ids,
                    );
    
        $this->db->where($w_uwd);
        $this->db->select($s);
        $ps         = $this->db->get("lock_payroll_process_settings");
        $result     = $ps->row();
        $lock       = false;
        if($result){
            
            $curr_date          = date("Y-m-d H:i:s");
            $gap_time           = (strtotime($curr_date) - strtotime($result->ts_recalc_time))/60;

            // for time sheet recalc
            //$this->session->set_userdata('ts_lock_settings', "false");
            $ts_lock_settings   = $this->session->userdata("ts_lock_settings");
            //p($gap_time);
            //p($curr_date);
            //p($lock_settings->ts_recalc_time);

            if(($result->ts_recalc == "true" && $gap_time < 10) && $ts_lock_settings != "true"){
                $lock           = true;
            }
            
            $gap_time_py            = (strtotime($curr_date) - strtotime($result->py_recalc_time))/60;
            if($result->py_recalc == "true" && $gap_time_py < 10){
                $lock           = true;
            }
            
            //p($ts_lock_settings);
            //p($lock);
        }
        return $lock;
    }
}