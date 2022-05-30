<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Attendance extends CI_Controller{
	var $verify;
		
	public function __construct(){
	   parent::__construct();
	  $this->load->model('login_screen_model');
	  $this->load->model('konsumglobal_jmodel','jmodel');
	  $this->load->model('employee_model','employee');
	  $this->load->model('approval_model','approval');
	  $this->load->model('approval_group_model','agm');
	  $this->load->model("approve_timeins_model","timeins");
	  $this->load->model('import_timesheets_model','import');
	  $this->load->model('login_model','emp_login');
	  $this->load->model('login_screen_model','lsm');
	  $this->load->model('employee_mobile_model','mobile');
	  $this->load->model('employee_work_schedule_model','ews');
	  $this->load->model('import_timesheets_model_v2','importv2');
      $this->load->model('emp_login_v2','emp_loginv2');
      $this->load->model('employee_v2_model','employee_v2');
	  
	  $this->load->model('emp_login_model','elm'); // REGULAR SCHEDULE & WORKSHIFT LOGIN MODEL
	  $this->load->model('emp_login_flexible_model','elmf');
	  
      $this->emp_id = $this->session->userdata('emp_id');
	  $this->company_id =$this->employee->check_company_id($this->emp_id);
	  
	  $this->psa_id = $this->session->userdata('psa_id');
	  $this->zero_time = NULL;
	  $this->min_log = 5;
	  $this->payroll_group_id = $this->employee->get_payroll_group($this->emp_id);
	  $this->emp_no = $this->employee->check_emp_no($this->emp_id,$this->company_id);
	  $currentdate = date("Y-m-d");
	  $this->work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$currentdate);
	  $this->hours_worked = $this->employee->new_hoursworked(date("Y-m-d"),$this->emp_id);
	  $this->work_day_timeout = $this->employee->work_day_timeout(date("l"),$this->company_id);
	  $this->account_id = $this->session->userdata('account_id');
	}	
	
	public function index(){
		$viewMonth = $this->input->post('viewMonth');
		$viewYear = $this->input->post('viewYear');
		$page = $this->input->post('page');
		$limit = $this->input->post('limit');
		$status = $this->input->post('status');

		$this->per_page = 10;
		
		$get_timesheet_list_mobile = $this->mobile->get_timesheet_list_mobile($this->emp_id,$this->company_id,false,$status,(($page-1) * $this->per_page),$limit);
		$total = ceil($this->mobile->get_timesheet_list_mobile($this->emp_id,$this->company_id,true,$status) / 10);

		if($get_timesheet_list_mobile){
			echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "total" => $total, "list" => $get_timesheet_list_mobile));
			
			return false;
		}else{
			echo json_encode(array("result" => "0"));
			return false;
		}
	}
	
	public function get_timesheet_correction(){
	    $page = $this->input->post('page');
	    $limit = $this->input->post('limit');
	    
	    $this->per_page = 10;
	    
	    $get_timesheet_list_mobile = $this->mobile->time_in_list_correction($this->emp_id,$this->company_id,false,(($page-1) * $this->per_page),$limit);
	    $total = ceil($this->mobile->time_in_list_correction($this->emp_id,$this->company_id,true) / 10);
	    
	    if($get_timesheet_list_mobile){
	        echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "total" => $total, "list" => $get_timesheet_list_mobile));
	        
	        return false;
	    }else{
	        echo json_encode(array("result" => "0"));
	        return false;
	    }
	}
	
	public function get_split_shift_block() {
		$employee_timein_date = date("Y-m-d",strtotime($this->input->post('employee_timein_date')));
		
		// get the first and last blocks
		$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$employee_timein_date);
		$get_split_block_name = $this->employee->list_of_blocks($employee_timein_date,$this->emp_id,$work_schedule_id,$this->company_id);
		
		$result_arr = array();
		
		if($get_split_block_name) {
			foreach ($get_split_block_name as $sb) {
				if($sb != FALSE){
					$temp_arr = array(
							'schedule_blocks_id'	=> $sb->schedule_blocks_id,
							'block_name'			=> $sb->block_name
					);
					
					array_push($result_arr, $temp_arr);
				}
			}
			
			echo json_encode($result_arr);
			return false;
		}
		
	}
	
	public function remove_break_split() {
		$schedule_blocks_id = $this->input->post('schedule_blocks_id');
			
		$check_break_by_sched_blocks = $this->employee->check_break_by_sched_blocks($schedule_blocks_id);
		
		if($check_break_by_sched_blocks){
			echo json_encode(array(
					"error" => false,
					"block_name" => $check_break_by_sched_blocks->block_name,
					"break_in_min" => $check_break_by_sched_blocks->break_in_min
			));
			return false;
		} else {
			echo json_encode(array(
					"error" => true,
					"block_name" => "",
					"break_in_min" => ""
			));
			return false;
		}
		
	}
	
	// murag e.delete na taka
	public function approverDetails(){
		@$company_id = $this->input->post('company_id');
		@$timeIn_id = $this->input->post('timeIn_id');
		@$addLog_id = $this->input->post('addLog_id');
		@$changeLog_id = $this->input->post('changeLog_id');
		@$locLog_id = $this->input->post('locaLog_id');
		
		$where = array(
				'ati.time_in_id' => $timeIn_id,
				'ati.comp_id' => $company_id
		);
		
		$select = array(
				'*'
		);
		
		$this->db->select($select);
		$this->db->where($where);
		$q1 = $this->db->get('approval_time_in AS ati');
		$r1 = $q1->result();
		
		foreach($r1 as $r2):
			$flagAddLog = $r2->flag_add_logs;
		endforeach;
		
		if($flagAddLog == '0'){
			
			//echo $process_type;
			$where = array(
					'agvg.approval_groups_via_groups_id' => $changeLog_id,
					'ag.company_id' => $company_id
			);
			$select = array(
					'*'
			);
			$this->db->select($select);
			$this->db->where($where);
			$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agvg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("employee AS emp","emp.emp_id = ag.emp_id","LEFT");
			$q = $this->edb->get('approval_groups_via_groups AS agvg');
			$r = $q->result();
			
			echo json_encode($r);
			
		}
		elseif($flagAddLog == '1'){
			$where = array(
					'agvg.approval_groups_via_groups_id' => $addLog_id,
					'ag.company_id' => $company_id
			);
			$select = array(
					'*'
			);
			$this->db->select($select);
			$this->db->where($where);
			$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agvg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("employee AS emp","emp.emp_id = ag.emp_id","LEFT");
			$q = $this->edb->get('approval_groups_via_groups AS agvg');
			$r = $q->result();
				
			echo json_encode($r);
		}elseif($flagAddLog == '2'){
			$where = array(
					'agvg.approval_groups_via_groups_id' => $locLog_id,
					'ag.company_id' => $company_id
			);
			$select = array(
					'*'
			);
			$this->db->select($select);
			$this->db->where($where);
			$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agvg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("employee AS emp","emp.emp_id = ag.emp_id","LEFT");
			$q = $this->edb->get('approval_groups_via_groups AS agvg');
			$r = $q->result();
		
			echo json_encode($r);
		}
	}
	public function daily_view(){
		$post = $this->input->post();
		$date = date('Y-m-d',strtotime($post['requested_date']));
		
		$where = array(
			'emp_id'=>$this->emp_id,
			'comp_id'=>$this->company_id,
			'date'	=> $date
		);
		
		$this->db->where($where);
		$this->db->order_by('date','desc');
		$q = $this->db->get('employee_time_in');
		$r = $q->result();
		
		echo json_encode($r);
    }
    
    public function clock_in_no_foto() {
        $this->clock_in_v2();
    }
    
    function get_emp_lvl_restriction($emp_id, $comp_id) {
        $s_emp 	= array(
            "mobile_geolocation",
            "mobile_geolocation_radius",
            "mobile_geolocation_lat",
            "mobile_geolocation_long",
            "mobile_geolocation_add",
            "mobile_employee_level_restrict"
        );
        $w_emp 	= array(
            "emp_id" => $emp_id,
            "company_id" => $comp_id,
        );
        $this->db->select($s_emp);
        $this->db->where($w_emp);
        $q_emp = $this->db->get("employee_payroll_information");
        $r = $q_emp->row();
        return ($r) ? $r : FALSE ;
    }

    /**
	 * Calculates the great-circle distance between two points, with
	 * the Haversine formula.
	 * @param float $latitudeFrom Latitude of start point in [deg decimal]
	 * @param float $longitudeFrom Longitude of start point in [deg decimal]
	 * @param float $latitudeTo Latitude of target point in [deg decimal]
	 * @param float $longitudeTo Longitude of target point in [deg decimal]
	 * @param float $earthRadius Mean earth radius in [m]
	 * @return float Distance between points in [m] (same as earthRadius)
	 */
	function haversineGreatCircleDistance(
	  	$latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
	{
		// convert from degrees to radians
		$latFrom = deg2rad($latitudeFrom);
		$lonFrom = deg2rad($longitudeFrom);
		$latTo = deg2rad($latitudeTo);
		$lonTo = deg2rad($longitudeTo);

		$latDelta = $latTo - $latFrom;
		$lonDelta = $lonTo - $lonFrom;

		$angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
	    	cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
	  	return $angle * $earthRadius;
	}

    function get_distance_from_save_point($lat1, $lng1, $lat2, $lng2) {
        $dist = 0;

        $dist = $this->haversineGreatCircleDistance($lat1, $lng1, $lat2, $lng2);
        
        return $dist;
    }

	// new_time_keeping_v5
	public function clock_in_v2() {

		$this->emp_id = $this->input->post('emp_id');
        $this->account_id = $this->input->post('account_id');
        $this->company_id = $this->input->post('company_id');
        $this->psa_id = $this->input->post('psa_id');
        $this->emp_no = $this->input->post('cloud_id');

        // add coordinates
        $lat = $this->input->post('latitude');
        $lng = $this->input->post('longitude');

        $get_mobile_time_tracking_setting = $this->mobile->get_mobile_time_tracking_setting($this->company_id);
        $valid_location = false;
        $allow_approval = true;

        $kiosk_min_log = 5;

		$failed 										= true;		
		$emp_no 										= $this->input->post('cloud_id');
		$check_company_id 								= $this->input->post('company_id');
		$show_list 										= false;
		$preshift 										= false;

		$psa_id = $this->psa_id;        
        $employee_id = $this->emp_id;
        			
		$check_company_id = $this->company_id;

		$company_id = $check_company_id;
		
		$emp_id = $this->emp_id;
		$location = $this->input->post('location');
		$emp_no = $this->emp_no;
		
		$rowx 											= explode('...',$emp_no);

		if(isset($rowx[1])){
			$show_list 									= true;				
			$emp_no 									= $rowx[1];

		}

		// FLAG PRE SHIFT
		$prerowx 										= explode('*',$emp_no);
		
		if(isset($prerowx[1])){

			$preshift 									= true;						
			$emp_no 									= $prerowx[1];
		}
		$currentdates 									= date('Y-m-d H:i:s');//'2018-08-26 14:45:00';//
		$currentdatesv2									= date('Y-m-d_H-i-s',strtotime($currentdates));
		$capture_img 									= "LOGIN_".$emp_no."_".$currentdatesv2.'.jpg';
		$snap_picture 									= ($_FILES) ? $_FILES['file']['tmp_name'] : "";
		$check_emp_no 									= $this->emp_loginv2->new_check_emp_info($emp_no,$check_company_id);
		
		if($check_emp_no){
			$position 									= ""; // $this->emp_loginv2->get_position($check_emp_no->position);
		}

		$date_capture 									= date("Y-m-d H:i:00",strtotime($currentdates));
		$currentdate 									= date("Y-m-d",strtotime($currentdates));
		$snap 											= false;

		if($snap_picture != ""){

			$explode_img 								= explode("_", $capture_img);
			$date_explode 								= $explode_img[1];
			$timesub_explode 							= explode(".", $explode_img[2]);
			$time_explode 								= str_replace("-", ":", $timesub_explode[0]);
			$snap 										= true;
		}

		if($check_emp_no){
			if($check_emp_no->employee_status == "Inactive"){

				$clock_data 							= array(
														"success"	=> 0,
														"error_msg" => "Employee ID number ({$emp_no}) has been deactivated. Please contact your Admin/HR personnel for reactivation."
														);
				$failed 								= false;
				echo json_encode($clock_data);
				return false;
			}
			else if($check_emp_no->timesheet_required == "no"){
				$clock_data 							= array(
														"result"	=> 0,
														"error_msg"	=> "({$emp_no})".' - Please enable the timesheet of this employee. The timesheet of this employee is set to "Not required"'
														);
				$failed 								= false;
				echo json_encode($clock_data);
				return false;
            }
            
            // TRAPPINGS FOR GEO FENCING and APPROVAL
            if ($get_mobile_time_tracking_setting) {
                $min_rad = 0;

                //global check if company allow approval
                if (property_exists($get_mobile_time_tracking_setting, "enable_mobile_approval")) {
                    if ($get_mobile_time_tracking_setting->enable_mobile_approval == 'enable') { // bali kay sayop ang pag default sa field sa db
                        $allow_approval = false;
                    } else {
                        $allow_approval = true;
                    }
                }

                $emp_geo = $this->get_emp_lvl_restriction($employee_id, $company_id);
                if (!$emp_geo) {
                    $clock_data 			= array(
                                            "result"	=> 0,
                                            "error_msg"	=> "Your time entry settings was not set correctly. Please contact your admin."
                                            );
                    $failed 				= false;
                    echo json_encode($clock_data);
                    return false;
                }

                // check employee level
                $emp_level_restrict = $emp_geo->mobile_employee_level_restrict;
                
                if ($emp_level_restrict == 'enable') {

                    // if enabled employee level
                    if ($emp_geo->mobile_geolocation == 'enable') {
                        $min_rad = $emp_geo->mobile_geolocation_radius;
                        $set_lat = $emp_geo->mobile_geolocation_lat;
                        $set_lng = $emp_geo->mobile_geolocation_long;
                        $set_add = $emp_geo->mobile_geolocation_add;
                        
                        if (!$lat || !$lng) {
                            $clock_data 			= array(
                                                    "result"	=> 0,
                                                    "error_msg"	=> "We could not determine your exact coordinates. Please try again later."
                                                    );
                            $failed 				= false;
                            echo json_encode($clock_data);
                            return false;
                        }
                        
                        $distance_from_save_point = $this->get_distance_from_save_point($set_lat, $set_lng, $lat, $lng);

                        if ($distance_from_save_point > $min_rad) {
                            $clock_data 			= array(
                                                    "result"	=> 0,
                                                    "error_msg"	=> "You can only punch in/out within {$min_rad} meters from {$set_add}."
                                                    );
                            $failed 				= false;
                            echo json_encode($clock_data);
                            return false;
                        }
                    }
                } else {

                    //global
                    if ($get_mobile_time_tracking_setting->mobile_geolocation == 'enable') {
                        $min_rad = $get_mobile_time_tracking_setting->mobile_geolocation_radius;
                        $set_lat = $get_mobile_time_tracking_setting->mobile_geolocation_lat;
                        $set_lng = $get_mobile_time_tracking_setting->mobile_geolocation_long;
                        $set_add = $get_mobile_time_tracking_setting->mobile_geolocation_add;

                        if (!$lat || !$lng) {
                            $clock_data 			= array(
                                                    "result"	=> 0,
                                                    "error_msg"	=> "We could not determine your exact coordinates. Please try again later."
                                                    );
                            $failed 				= false;
                            echo json_encode($clock_data);
                            return false;
                        }

                        $distance_from_save_point = $this->get_distance_from_save_point($set_lat, $set_lng, $lat, $lng);
                        if ($distance_from_save_point > $min_rad) {
                            $clock_data 			= array(
                                                    "result"	=> 0,
                                                    "error_msg"	=> "You can only punch in/out within {$min_rad} meters from {$set_add}."
                                                    );
                            $failed 				= false;
                            echo json_encode($clock_data);
                            return false;
                        }
                    }
                }
                
            }
		}
		else if($emp_no == "" || !$check_emp_no){

			$clock_data 								= array(
														"result"	=> 0,
														"error_msg"	=> "({$emp_no}) - Oops! I can't seem to find this id. Please try again."
														);
			$failed 									= false;
			echo json_encode($clock_data);
			return false;
        }
        

		/**IDENTIFY THE PREVIOUS TIME IN
		 * This will help the workschedule of previous time in 
		 * GET THE DATE
		 ***/

		$w 												= array(
														"a.payroll_cloud_id" 	=> $emp_no,
														"a.user_type_id" 		=> "5",
														"eti.status" 			=> "Active",
														"eti.comp_id" 			=> $check_company_id
														);
		
		$arrp 											= array(
														'time_in' 				=> 'eti.time_in',
														'time_out' 				=> 'eti.time_out',
														'work_schedule_id' 		=> 'eti.work_schedule_id',
														'employee_time_in_id' 	=> 'eti.employee_time_in_id'
														);

		$day 											= date('l');
		$time_in 										= date('Y-m-d H:i:s');
		$work_schedule_id_check 						= 0;	
		$employee_time_in_id 							= 0;
		$val 											= false;
		$vx 											= false;

		$emp_work_schedule_id 							= false;

		if($check_emp_no){

			$emp_work_schedule_id 						= $this->import->emp_work_schedule($check_emp_no->emp_id,$check_company_id,$currentdate);
		}

		if($failed){
			$correct_sched 								= false;
			$emp_id 									= $check_emp_no->emp_id;
			$emp_ids[] 									= $emp_id;
			$get_employee_payroll_information			= $this->emp_loginv2->get_employee_payroll_information($check_company_id,$emp_ids);
			$get_work_schedule							= $this->emp_loginv2->get_work_schedule($check_company_id);
			$get_all_regular_schedule 					= $this->emp_loginv2->get_all_regular_schedule($check_company_id);
			$emp_work_schedule_ess 						= $this->emp_loginv2->emp_work_schedule_ess($check_company_id,$emp_ids);
			$emp_work_schedule_epi 						= $this->emp_loginv2->emp_work_schedule_epi($check_company_id,$emp_ids);
			$list_of_blocks 							= $this->emp_loginv2->list_of_blocks($check_company_id,$emp_ids);
			$get_all_schedule_blocks 					= $this->emp_loginv2->get_all_schedule_blocks($check_company_id);
			$get_all_schedule_blocks_time_in			= $this->emp_loginv2->get_all_schedule_blocks_time_in($check_company_id,$emp_ids);
			$get_work_schedule_flex						= $this->emp_loginv2->get_work_schedule_flex($check_company_id);
			$get_all_employee_timein 					= $this->emp_loginv2->get_all_employee_timein($check_company_id,$emp_ids);
			$company_holiday 							= $this->emp_loginv2->company_holiday($check_company_id);
			$rest_days 									= $this->get_all_rest_day($check_company_id);


			$payroll_group1                     = in_array_custom("emp_id_{$emp_id}",$get_employee_payroll_information);
			if($payroll_group1){
	            $payroll_group                  = $payroll_group1->payroll_group_id;
	            $approver_id                    = $payroll_group1->location_base_login_approval_grp;
	        }

			$newtimein_approver = $this->agm->get_approver_name_timein_location($emp_id,$check_company_id);
			$hours_notification = get_notify_settings($approver_id, $company_id);
			$is_workflow_enabled = is_workflow_enabled($company_id);


			// addittional checker here!! this will trap if the schedule get in this day coincide the the actual schedule
			// current date
			$current_date_hours = date("H:i:s",strtotime($date_capture));
			if($current_date_hours >= "22:00:00"){
				$currentdate = date("Y-m-d",strtotime($currentdate. "+1 day"));
			}
			$correct_sched 								= $this->check_id_schedule_coincide("0",$check_emp_no,$get_all_regular_schedule,$get_work_schedule_flex,$get_work_schedule,$date_capture,$currentdate,$check_company_id,$get_all_employee_timein,$rest_days);

			if(!$correct_sched['verify']){
				// check previous date
				$currentdate 							= date("Y-m-d",strtotime($currentdate."- 1 day"));
				$correct_sched 							= $this->check_id_schedule_coincide("1",$check_emp_no,$get_all_regular_schedule,$get_work_schedule_flex,$get_work_schedule,$date_capture,$currentdate,$check_company_id,$get_all_employee_timein,$rest_days);
							
			}

				
			$emp_work_schedule_id 						= $correct_sched['workschedule_id'];
			$currentdate 								= $correct_sched['currentdate'];
			$check_workday 								= $correct_sched['sched_type'];
			$verify 									= $correct_sched['verify'];
			$worksched_data 							= $correct_sched['worksched_data'];				
			$excess_var 								= false;
			$openshift 									= false;

			if($verify){
				$check_type 							= "";
				$save_db 								= false;
				$schedule_data 							= $correct_sched['sched_data'];
				$current_log 							= in_array_custom2("custom_searchv3","emp_id_timeins_date_{$check_emp_no->emp_id}_{$currentdate}",$get_all_employee_timein);
				
				if($current_log){
					$last_t_worksched_id				= $current_log->work_schedule_id;
					$last_id 							= $current_log->employee_time_in_id;
					$prev_kiosk_location 				= $current_log->kiosk_location;
					$prev_source 						= $current_log->source;
					$last_t_time_in 					= $current_log->time_in;
					$last_t_lunch_in 					= $current_log->lunch_in;
					$last_t_lunch_out 					= $current_log->lunch_out;
					$last_t_break1_out 					= $current_log->break1_out;
					$last_t_break1_in 					= $current_log->break1_in;
					$last_t_break2_out 					= $current_log->break2_out;
					$last_t_break2_in 					= $current_log->break2_in;
					$last_t_time_out 					= $current_log->time_out;
					$last_t_date 	 					= $current_log->date;
					$last_t_reg_or_excess 	 			= $current_log->flag_regular_or_excess;

					$t_in_arr[] 						= $last_t_time_in;
					$t_in_arr[] 						= $last_t_lunch_in;
					$t_in_arr[] 						= $last_t_lunch_out;
					$t_in_arr[] 						= $last_t_break1_out;
					$t_in_arr[] 						= $last_t_break1_in;
					$t_in_arr[] 						= $last_t_break2_out;
					$t_in_arr[] 						= $last_t_break2_in;
					$t_in_arr[] 						= $last_t_time_out;

					sort($t_in_arr);
					$arr_def 							= end($t_in_arr);
						
					$arr_defx 							= (strtotime($date_capture) - strtotime($arr_def))/60;

					if($arr_defx < $kiosk_min_log){
						$clock_data 					= array(
														"result"	=> 0,
														"error_msg"	=> "Oops! A minimum of ".$kiosk_min_log." minutes interval between clockin is required."
														);
						$failed 						= false;

						echo json_encode($clock_data);
						return false;
					}
				}

				if($failed){
					//check if holiday
					$holiday_this_current 				= in_array_custom("holiday_{$currentdate}",$company_holiday);
					if($holiday_this_current){

						$location_and_offices_id 		= "";
						$get_location_and_offices_id	= in_array_custom("emp_id_{$emp_id}",$get_employee_payroll_information);

						if($get_location_and_offices_id){
							$location_and_offices_id 	= $payroll_group1->location_and_offices_id;
						}

						if($holiday_this_current->locations){
							$holiday_loc				= false;
							if($location_and_offices_id){
								$loc_holiday = explode(",",$holiday_this_current->locations);
								foreach ($loc_holiday as $r_loc){
									if($r_loc == $location_and_offices_id){
										// yeah holiday
										$holiday_loc	= $holiday_this_current;
									}
								}
							}
							$holiday_this_current  		= $holiday_loc;
						}
					}
					
					$holiday_orig 						= $holiday_this_current;

					if($check_workday != "rest day"){
						$time_keep_holiday					= ($worksched_data->enable_breaks_on_holiday == 'yes') ? true : false;
						if($time_keep_holiday){
							$holiday_this_current 			= false;
						}
					}

					if($check_workday == "Flexible Hours"){
							
						if($current_log){

							if(($last_t_time_out == "" || $last_t_time_out == NULL)){

								if($last_t_reg_or_excess == "regular"){

									$flex_lunch 			= $schedule_data->duration_of_lunch_break_per_day;

									if($flex_lunch > 0 && $worksched_data->break_rules == "capture"){

										if(($last_t_lunch_out == "" || $last_t_lunch_out == NULL)){
											$check_type 	= "lunch out";
										}
										else if(($last_t_lunch_in == "" || $last_t_lunch_in == NULL)){
											$check_type 	= "lunch in";
										}
										else{
											$check_type 	= "time out";
										}
									}
									else{
										$check_type 		= "time out";
									}
								}
								else{
									$check_type 			= "time out";
								}
							}
							else{
								$check_type 				= "time in";
								$excess_var 				= true;
							}
						}
						else{
							$check_type = "time in";
						}

						$save_db 							= true;
						
					}
					else if($check_workday == "Uniform Working Days"){
						if($current_log){
								
							$last_regular_start_time 										= $schedule_data->work_start_time; 
							$last_regular_end_time 											= $schedule_data->work_end_time;
							$last_tresh 													= $schedule_data->latest_time_in_allowed;
							$lunch_min_b													= $schedule_data->break_in_min;
							$lunch_min_b 													= number_format($lunch_min_b,0);
							$break_1_min_b 													= $schedule_data->break_1;
							$break_1_min_b 													= number_format($break_1_min_b,0);
							$break_2_min_b 													= $schedule_data->break_2;
							$break_2_min_b 													= number_format($break_2_min_b,0);

							$enable_lunch_break 											= $worksched_data->enable_lunch_break;
							$num_of_additional_breaks 										= $worksched_data->num_of_additional_breaks;
							$track_break_1 													= $worksched_data->track_break_1;
							$break_schedule_1 												= $worksched_data->break_schedule_1;
							$track_break_2 													= $worksched_data->track_break_2;
							$break_schedule_2 												= $worksched_data->break_schedule_2;
							$break_started_after 											= $worksched_data->break_started_after;

							$enable_additional_breaks 										= $worksched_data->enable_additional_breaks;

							$barack_date_trap_exact_t_date 									= $date_capture;

							$last_et_date 													= $last_t_date;

							if($last_regular_start_time > $last_regular_end_time){

								$last_et_date 												= date("Y-m-d",strtotime($last_et_date." +1 day"));
							}

							$time_break 													= $last_t_date." ".$last_regular_start_time;
							$time_e_break 													= $last_et_date." ".$last_regular_end_time;

							if($last_tresh > 0){
								$time_e_break 												= date("Y-m-d H:i:00",strtotime($time_e_break." +".$last_tresh." minutes"));
							}

							if($last_t_time_out != "" || $last_t_time_out != NULL){

								$check_type 												= "time in";
								$excess_var 												= true;
							}
							else{
								if(($last_t_time_in != "" || $last_t_time_in != NULL) && $last_t_reg_or_excess == "regular"){
									// check if enable additional break and lunch break 
									if($enable_lunch_break == "yes" || $enable_additional_breaks == "yes"){

										$break_after_1 										= 0;
										$break_after_2 										= 0;
										$break_after_3 										= 0;
										$num_s_breaks 										= $num_of_additional_breaks;
										$lunch_track 										= false;
										$break_track 										= false;

										if($enable_lunch_break == "yes" && $track_break_1 == "yes"){

											$lunch_track 									= true;

											if($break_schedule_1 == "fixed"){
												$break_after_1 								= $break_started_after;
											}
										}
										if($enable_additional_breaks == "yes" && $track_break_2 == "yes"){

											$break_track 									= true;

											if($break_schedule_2 == "fixed"){

												$break_after_2 								= $additional_break_started_after_1;
												$break_after_3 								= $additional_break_started_after_2;
											}
										}
										
										$checker_if_fall 									= false;

										// scene : if tanan naka enable
										if($break_track && $lunch_track){

											$if_fixed 										= array();
											$if_assumed 									= array();

											if($break_schedule_1 == "fixed"){

												$if_fixed['lunch'] 							= $break_after_1;

											}else{

												$if_assumed['lunch'] 						= $break_after_1;
											}

											if($break_schedule_2 == "fixed"){
												if($num_s_breaks > 1){
													$if_fixed['break1'] 					= $break_after_2;
													$if_fixed['break2'] 					= $break_after_3;
												}
												else{
													$if_fixed['break1'] 					= $break_after_2;
												}
											}else{
												if($num_s_breaks > 1){

													$if_assumed['break1'] 					= $break_after_2;
													$if_assumed['break2'] 					= $break_after_3;
												}
												else{

													$if_assumed['break1'] 					= $break_after_2;
												}
											}

											// if naa usa na fixed
											if($if_fixed){

												arsort($if_fixed);

												foreach ($if_fixed as $key => $value) {

													$value_min								= $value * 60;
													$value_min 								= number_format($value_min,0);
													$time_breakx 							= date("Y-m-d H:i:00",strtotime($time_break." +".$value_min." minutes"));
													

													if($key == "lunch" && $value_min > 0){

														if($time_breakx <= $barack_date_trap_exact_t_date){
															
															if(($last_t_lunch_in == "" || $last_t_lunch_in == NULL) && ($last_t_lunch_out != "" || $last_t_lunch_out != NULL)){

																$checker_if_fall 			= true;
																$check_type 				= "lunch in";
															}
															else if(($last_t_lunch_out == "" || $last_t_lunch_out == NULL)){

																if(!$checker_if_fall){

																	$time_out_end 			= date("Y-m-d H:i:00",strtotime($time_e_break." -".$lunch_min_b." minutes"));

																	if($barack_date_trap_exact_t_date >= $time_out_end){
																		$check_type 		= "time out";
																		$checker_if_fall 	= true;
																	}
																	else{
																		$time_break_end 	= date("Y-m-d H:i:00",strtotime($time_breakx." +".$lunch_min_b." minutes"));

																		if($barack_date_trap_exact_t_date < $time_break_end){
																			$check_type 	= "lunch out";
																			$checker_if_fall= true;
																		}
																		else{
																			$check_type 	= "time out";
																		}
																	}
																}
															}
														}
													}
													
													if($key == "break1"){

														if($time_breakx <= $barack_date_trap_exact_t_date){

															if(($last_t_break1_in == "" || $last_t_break1_in == NULL) && ($last_t_break1_out != "" || $last_t_break1_out != NULL)){

																$checker_if_fall 			= true;
																$check_type					= "break1 in";
															}
															else if(($last_t_break1_out == "" || $last_t_break1_out == NULL)){

																if(!$checker_if_fall){

																	$time_out_end 			= date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_1_min_b." minutes"));

																	if($barack_date_trap_exact_t_date >= $time_out_end){

																		$check_type 		= "time out";
																		$checker_if_fall 	= true;
																	}else{
																		$time_break_end 	= date("Y-m-d H:i:00",strtotime($time_breakx." +".$break_1_min_b." minutes"));

																		if($barack_date_trap_exact_t_date < $time_break_end){
																			
																			$check_type 	= "break1 out";	
																			$checker_if_fall= true;
																		}
																		else{
																			$check_type 	= "time out";
																		}
																	}
																}
															}
														}
													}

													if($key == "break2"){

														if($time_breakx <= $barack_date_trap_exact_t_date){

															if(($last_t_break2_in == "" || $last_t_break2_in == NULL)  && ($last_t_break2_out != "" || $last_t_break2_out != NULL)){

																$checker_if_fall 			= true;
																$check_type					= "break2 in";
															}
															else if(($last_t_break2_out == "" || $last_t_break2_out == NULL)){

																if(!$checker_if_fall){

																	$time_out_end 			= date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_2_min_b." minutes"));

																	if($barack_date_trap_exact_t_date >= $time_out_end){

																		$check_type 		= "time out";
																		$checker_if_fall 	= true;
																	}
																	else{

																		$time_break_end 	= date("Y-m-d H:i:00",strtotime($time_breakx." +".$break_2_min_b." minutes"));

																		if($barack_date_trap_exact_t_date < $time_break_end){
																				
																			$check_type 	= "break2 out";
																			$checker_if_fall= true;
																		}
																		else{
																			$check_type 	= "time out";
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

																	$time_out_end 			= date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_1_min_b." minutes"));

																	if($barack_date_trap_exact_t_date >= $time_out_end){

																		$check_type 		= "time out";
																	}
																	else{

																		$check_type 		= "break1 out";
																	}
																	$checker_if_fall 		= true;
																}
															}
															else if(($last_t_break1_in == "" || $last_t_break1_in == NULL) && ($last_t_break1_out != "" || $last_t_break1_out != NULL)){

																$checker_if_fall 			= true;
																$check_type					= "break1 in";
															}
														}
													}

													// if lunch break flexi 
													// first lunch break consume first before break if only 1 break

													if($break_schedule_1 == "flexi"){

														if(($last_t_lunch_out == "" || $last_t_lunch_out == NULL)){

															if(!$checker_if_fall){

																$time_out_end 				= date("Y-m-d H:i:00",strtotime($time_e_break." -".$lunch_min_b." minutes"));

																if($barack_date_trap_exact_t_date >= $time_out_end){

																	$check_type 			= "time out";
																}
																else{
																	if($lunch_min_b > 0){

																		$check_type 		= "lunch out";
																	}
																	else{
																		$check_type 		= "time out";
																	}
																}
																$checker_if_fall 			= true;
															}
														}
														else if(($last_t_lunch_in == "" || $last_t_lunch_in == NULL) && ($last_t_lunch_out != "" || $last_t_lunch_out != NULL)){

															$checker_if_fall 				= true;
															$check_type 					= "lunch in";
														}
													}


													// break flexi 
													if($break_schedule_2 == "flexi"){

														if($num_s_breaks == 1){

															if(($last_t_break1_out == "" || $last_t_break1_out == NULL)){

																if(!$checker_if_fall){
																	$time_out_end 			= date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_1_min_b." minutes"));

																	if($barack_date_trap_exact_t_date >= $time_out_end){

																		$check_type 		= "time out";
																	}
																	else{
																		
																		$check_type 		= "break1 out";
																	}

																	$checker_if_fall 		= true;
																}
															}
															else if(($last_t_break1_in == "" || $last_t_break1_in == NULL) && ($last_t_break1_out != "" || $last_t_break1_out != NULL)){

																$checker_if_fall 			= true;
																$check_type					= "break1 in";
															}
														}

														if($num_s_breaks > 1){

															if(($last_t_break2_out == "" || $last_t_break2_out == NULL)){

																if(!$checker_if_fall){

																	$time_out_end 			= date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_2_min_b." minutes"));

																	if($barack_date_trap_exact_t_date >= $time_out_end){

																		$check_type 		= "time out";
																	}
																	else{

																		$check_type 		= "break2 out";
																	}

																	$checker_if_fall 		= true;
																}
															}
															else if(($last_t_break2_in == "" || $last_t_break2_in == NULL) && ($last_t_break2_out != "" || $last_t_break2_out != NULL)){

																$checker_if_fall 			= true;
																$check_type					= "break2 in";
															}
														}
													}
												}
											}
											// not in belong break schedule
											if(!$checker_if_fall){
												$check_type 								= "time out";
											}
										}
										// scene : if lunch break naka enable
										else if($lunch_track){

											if($break_schedule_1 == "fixed"){
												
												if($break_after_1 > 0){
													$value										= $break_after_1 * 60;
													$value 										= number_format($value,0);
													$time_break 								= date("Y-m-d H:i:00",strtotime($time_break." +".$value." minutes"));

													if($time_break <= $barack_date_trap_exact_t_date){

														if(($last_t_lunch_in == "" || $last_t_lunch_in == NULL) && ($last_t_lunch_out != "" || $last_t_lunch_out != NULL)){

															$checker_if_fall 					= true;
															$check_type 						= "lunch in";
														}
														else if(($last_t_lunch_out == "" || $last_t_lunch_out == NULL)){

															if(!$checker_if_fall){

																$time_out_end 					= date("Y-m-d H:i:00",strtotime($time_e_break." -".$lunch_min_b." minutes"));

																if($barack_date_trap_exact_t_date >= $time_out_end){

																	$check_type 				= "time out";
																}
																else{
																	$time_break_end 			= date("Y-m-d H:i:00",strtotime($time_break." +".$lunch_min_b." minutes"));

																	if($barack_date_trap_exact_t_date < $time_break_end){

																		$check_type 			= "lunch out";
																	}
																	else{
																		$check_type 			= "time out";
																	}
																}
																$checker_if_fall 				= true;
															}
														}
													}
												}else{
													$check_type 								= "time out";
													$checker_if_fall 							= true;
												}
											}
											else{

												if(($last_t_lunch_out == "" || $last_t_lunch_out == NULL)){

													if(!$checker_if_fall){
														$time_out_end 							= date("Y-m-d H:i:00",strtotime($time_e_break." -".$lunch_min_b." minutes"));

														if($barack_date_trap_exact_t_date >= $time_out_end){
															$check_type 						= "time out";
														}
														else{
															if($lunch_min_b > 0){
																$check_type 					= "lunch out";
															}
															else{
																$check_type 					= "time out";
															}
														}

														$checker_if_fall 						= true;
													}
												}
												else if(($last_t_lunch_in == "" || $last_t_lunch_in == NULL) && ($last_t_lunch_out != "" || $last_t_lunch_out != NULL)){

													$checker_if_fall 							= true;
													$check_type 								= "lunch in";
												}
											}

											if(!$checker_if_fall){
												$check_type 									= "time out";
											}
										}
										// scene : if additional break ra naka enable
										else{

											if($break_schedule_2 == "fixed"){

												if($break_after_2 > $break_after_3){

													$value									= $break_after_2 * 60;
													$value 									= number_format($value,0);
													$time_breakx 							= date("Y-m-d H:i:00",strtotime($time_break." +".$value." minutes"));
													
													if($time_breakx <= $barack_date_trap_exact_t_date){

														if(($last_t_break1_in == "" || $last_t_break1_in == NULL) && ($last_t_break1_out != "" || $last_t_break1_out != NULL)){

															$checker_if_fall 				= true;
															$check_type						= "break1 in";
														}
														else if(($last_t_break1_out == "" || $last_t_break1_out == NULL)){

															if(!$checker_if_fall){

																$time_out_end 				= date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_1_min_b." minutes"));

																if($barack_date_trap_exact_t_date >= $time_out_end){

																	$check_type 			= "time out";
																}else{

																	$time_break_end 		= date("Y-m-d H:i:00",strtotime($time_breakx." +".$break_1_min_b." minutes"));

																	if($barack_date_trap_exact_t_date < $time_break_end){
																		
																		$check_type 		= "break1 out";	
																	}
																	else{
																		$check_type 		= "time out";
																	}
																}
																$checker_if_fall 			= true;
															}
														}
													}

													if($break_after_3 != "0" && $break_after_3 != NULL && $break_after_3 > 0){

														$value								= $break_after_3 * 60;
														$value 								= number_format($value,0);
														$time_breakx 						= date("Y-m-d H:i:00",strtotime($time_break." +".$value." minutes"));

														if($time_breakx <= $barack_date_trap_exact_t_date){

															if(($last_t_break2_in == "" || $last_t_break2_in == NULL)  && ($last_t_break2_out != "" || $last_t_break2_out != NULL)){

																$checker_if_fall 			= true;
																$check_type					= "break2 in";
															}
															else if(($last_t_break2_out == "" || $last_t_break2_out == NULL)){

																if(!$checker_if_fall){

																	$time_out_end 			= date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_2_min_b." minutes"));

																	if($barack_date_trap_exact_t_date >= $time_out_end){

																		$check_type 		= "time out";
																	}
																	else{
																		$time_break_end 	= date("Y-m-d H:i:00",strtotime($time_breakx." +".$break_2_min_b." minutes"));

																		if($barack_date_trap_exact_t_date < $time_break_end){
																			
																			$check_type 	= "break2 out";
																		}
																		else{
																			$check_type 	= "time out";
																		}
																	}
																	$checker_if_fall 		= true;
																}
															}
														}
													}
												}
												else{

													$value									= $break_after_3 * 60;
													$value 									= number_format($value,0);
													$time_breakx 							= date("Y-m-d H:i:00",strtotime($time_break." +".$value." minutes"));

													if($time_breakx <= $barack_date_trap_exact_t_date){
														
														if(($last_t_break2_in == "" || $last_t_break2_in == NULL)  && ($last_t_break2_out != "" || $last_t_break2_out != NULL)){

															$checker_if_fall 				= true;
															$check_type						= "break2 in";
														}
														else if(($last_t_break2_out == "" || $last_t_break2_out == NULL)){

															if(!$checker_if_fall){

																$time_out_end 				= date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_2_min_b." minutes"));

																if($barack_date_trap_exact_t_date >= $time_out_end){

																	$check_type 			= "time out";
																}
																else{

																	$time_break_end 		= date("Y-m-d H:i:00",strtotime($time_breakx." +".$break_2_min_b." minutes"));

																	if($barack_date_trap_exact_t_date < $time_break_end){
																		
																		$check_type 		= "break2 out";
																	}
																	else{
																		$check_type 		= "time out";
																	}
																}
																$checker_if_fall 			= true;
															}
														}
													}

													$value									= $break_after_2 * 60;
													$value 									= number_format($value,0);
													$time_breakx 							= date("Y-m-d H:i:00",strtotime($time_break." +".$value." minutes"));
													
													if($time_breakx <= $barack_date_trap_exact_t_date){
														
														if(($last_t_break1_in == "" || $last_t_break1_in == NULL) && ($last_t_break1_out != "" || $last_t_break1_out != NULL)){

															$checker_if_fall 				= true;
															$check_type						= "break1 in";
														}
														else if(($last_t_break1_out == "" || $last_t_break1_out == NULL)){

															if(!$checker_if_fall){

																$time_out_end 				= date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_1_min_b." minutes"));

																if($barack_date_trap_exact_t_date >= $time_out_end){

																	$check_type 			= "time out";
																}else{
																	
																	$time_break_end 		= date("Y-m-d H:i:00",strtotime($time_breakx." +".$break_1_min_b." minutes"));

																	if($barack_date_trap_exact_t_date < $time_break_end){
																		
																		$check_type 		= "break1 out";
																	}
																	else{
																		$check_type 		= "time out";
																	}
																}
																$checker_if_fall 			= true;
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

																$time_out_end 				= date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_1_min_b." minutes"));

																if($barack_date_trap_exact_t_date >= $time_out_end){

																	$check_type 			= "time out";
																}
																else{
																	
																	$check_type 			= "break1 out";
																}

																$checker_if_fall 			= true;
															}
														}
														else if(($last_t_break1_in == "" || $last_t_break1_in == NULL) && ($last_t_break1_out != "" || $last_t_break1_out != NULL)){

															$checker_if_fall 				= true;
															$check_type						= "break1 in";
														}
													}

													if($num_s_breaks > 1){

														if(($last_t_break1_out == "" || $last_t_break1_out == NULL)){

															if(!$checker_if_fall){
																
																$time_out_end 				= date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_1_min_b." minutes"));

																if($barack_date_trap_exact_t_date >= $time_out_end){

																	$check_type 			= "time out";
																}
																else{
																	
																	$check_type 			= "break1 out";
																}

																$checker_if_fall 			= true;
															}
														}
														else if(($last_t_break1_in == "" || $last_t_break1_in == NULL) && ($last_t_break1_out != "" || $last_t_break1_out != NULL)){

															$checker_if_fall 				= true;
															$check_type						= "break1 in";
														}

														if(($last_t_break2_out == "" || $last_t_break2_out == NULL)){

															if(!$checker_if_fall){

																$time_out_end 				= date("Y-m-d H:i:00",strtotime($time_e_break." -".$break_2_min_b." minutes"));

																if($barack_date_trap_exact_t_date >= $time_out_end){

																	$check_type 			= "time out";
																}
																else{

																	$check_type 			= "break2 out";
																}

																$checker_if_fall 			= true;
															}
														}
														else if(($last_t_break2_in == "" || $last_t_break2_in == NULL) && ($last_t_break2_out != "" || $last_t_break2_out != NULL)){

															$checker_if_fall 				= true;
															$check_type						= "break2 in";
														}
													}
												}
											}

											if(!$checker_if_fall){
												$check_type 								= "time out";
											}
										}    
									}
									else{
										// time out
										$check_type 										= "time out";
									}
								}else{
									$check_type 											= "time out";
								}
							}
						}else{
							$check_type 													= "time in";
						}
						
						$save_db 															= true;
					}
					else if($check_workday == "Open Shift"){
						$openshift 															= true;
						
						if($current_log){
							if(($last_t_time_out == "" || $last_t_time_out == NULL)){
								$check_type 												= "time out";
							}
							else{
								$check_type 												= "time in";
							}
						}
						else{
							$check_type = "time in";
						}
						$save_db 															= true;
					}
					else if($check_workday == "rest day"){
						
						if($current_log){
							
							if(($last_t_time_out == "" || $last_t_time_out == NULL)){
								$check_type 			= "time out";
							}
							else{
								$check_type 				= "time in";
							}
						}
						else{
							$check_type = "time in";
						}
						$save_db 															= true;
						$emp_work_schedule_id 				= "-1";
					}

					// if holiday
					if($holiday_this_current){
						if($check_type != "time in"){
							$check_type == "time_out";
						}
					}

				}

				$image_saved = false;
				$insert_logs = false;

				if($save_db && !$show_list){
					if($snap_picture != ""){
							
						$val 							= array(
														"emp_id"			=> $emp_id,
														"date_capture"		=> $date_capture,
														"image"				=> $capture_img
														);

						$save_image = $this->db->insert("employee_image_logs",$val);
		
						if($save_image){
							// $result = file_put_contents('../uploads/companies/'.$this->company_id.'/'.$capture_img, file_get_contents($_FILES['file']['tmp_name']));
							$result = file_put_contents('./uploads/companies/'.$check_company_id.'/'.$capture_img, file_get_contents($snap_picture));
							if (!$result) {
								$target_path = './uploads/companies/'.$check_company_id.'/';
								$target_path = $target_path . basename( $_FILES['file']['name']);
								$result = move_uploaded_file($_FILES['file']['tmp_name'], $target_path);
							}

							if (!$result) {
								$data = $_FILES['file']['tmp_name'];

								list($type, $data) = explode(';', $data);
								list(, $data)      = explode(',', $data);
								$data = base64_decode($data);

								$result = file_put_contents('./uploads/companies/'.$check_company_id.'/'.$capture_img, $data);
							}

							$result = file_put_contents('../uploads/companies/'.$check_company_id.'/'.$capture_img, file_get_contents($snap_picture));
							if (!$result) {
								$target_path = '../uploads/companies/'.$check_company_id.'/';
								$target_path = $target_path . basename( $_FILES['file']['name']);
								$result = move_uploaded_file($_FILES['file']['tmp_name'], $target_path);
							}

							if (!$result) {
								$data = $_FILES['file']['tmp_name'];

								list($type, $data) = explode(';', $data);
								list(, $data)      = explode(',', $data);
								$data = base64_decode($data);

								$result = file_put_contents('../uploads/companies/'.$check_company_id.'/'.$capture_img, $data);
							}

						
							if(!$result) {
								echo json_encode(array("result"=>0,"error_msg"=>"- ERROR: Failed to write data to {$capture_img}, check permissions\n.","capture_img"=>$snap_picture));
								return FALSE;
							}
							else{
								$image_saved = true;
							}
						}
					}
					else{
						$image_saved = true;
					}	
					if($image_saved){
						$kiosk_location = ""; // $check_company_loc."-".$check_type;

						if($check_type == "time in"){

							if($excess_var){
								$data_insert 		= array(
													"comp_id"					=> $check_company_id,
													"emp_id"					=> $emp_id,
													"date"						=> $currentdate ,
													"source"					=> "mobile",
													"time_in"					=> $date_capture,
													"work_schedule_id"			=> "-2",
													"location" 					=> $location,
													"location_1"				=> $location,
													"flag_new_time_keeping" 	=> "1",
													"flag_regular_or_excess" 	=> "excess",
                                                    );
                                if ($allow_approval) {
                                    $data_insert["time_in_status"]              = "pending";
                                    $data_insert["mobile_clockin_status"]       = "pending";
                                    $data_insert["corrected"]                   = "Yes";
                                }
							}
							else{
								$data_insert 		= array(
													"comp_id"					=> $check_company_id,
													"emp_id"					=> $emp_id,
													"date"						=> $currentdate ,
													"source"					=> "mobile",
													"time_in"					=> $date_capture,
													"work_schedule_id"			=> $emp_work_schedule_id,
													"location" 					=> $location,
													"location_1"				=> $location,
													"flag_new_time_keeping" 	=> "1",
                                                    );
                                if ($allow_approval) {
                                    $data_insert["time_in_status"]              = "pending";
                                    $data_insert["mobile_clockin_status"]       = "pending";
                                    $data_insert["corrected"]                   = "Yes";
                                }
							}
							if($openshift){
								$data_insert['flag_open_shift'] 				= "1";
							}
							$insert 				= $this->db->insert('employee_time_in', $data_insert);
							$last_id 				= $this->db->insert_id();

							if($insert){
								$insert_logs 		= true;
							}
						} else {
							$prev_source 			= $prev_source.",mobile-".$check_type;
							$kiosk_location 		= $prev_kiosk_location.",".$kiosk_location;

							$data_insert 			= array(
													"location" 					=> $location,
													"source" 					=> $prev_source,
													"flag_new_time_keeping" 	=> "1",
                                                    );
                            
							if($check_type == "lunch out"){
								$data_insert["lunch_out"] = $date_capture;
								$data_insert["location_2"] = $location;
								// $data_insert["mobile_lunchout_status"] = "pending";
							}
							if($check_type == "lunch in"){
								$data_insert["lunch_in"] = $date_capture;
								$data_insert["location_3"] = $location;
								// $data_insert["mobile_lunchin_status"] = "pending";
							}

							if($check_type == "break1 out"){
								$data_insert["break1_out"] = $date_capture;
								$data_insert["location_5"] = $location;
								// $data_insert["mobile_break1_out_status"] = "pending";
							}
							if($check_type == "break1 in"){
								$data_insert["break1_in"] = $date_capture;
								$data_insert["location_6"] = $location;
								// $data_insert["mobile_break1_in_status"] = "pending";
							}

							if($check_type == "break2 out"){
								$data_insert["break2_out"] = $date_capture;
								$data_insert["location_7"] = $location;
								// $data_insert["mobile_break2_out_status"] = "pending";
							}
							if($check_type == "break2 in"){
								$data_insert["break2_in"] = $date_capture;
								$data_insert["location_8"] = $location;
								// $data_insert["mobile_break2_in_status"] = "pending";
							}

							if($check_type == "time out"){
								$data_insert["time_out"] = $date_capture;
								$data_insert["location_4"] = $location;
								// $data_insert["mobile_clockout_status"] = "pending";
                            }
                            
                            if ($allow_approval) {
                                $data_insert["time_in_status"]              = "pending";
                                $data_insert["mobile_clockin_status"]       = "pending";
                                $data_insert["corrected"]                   = "Yes";
                            }

							$where_id 	= array(
								'employee_time_in_id' => $last_id
								);
							$this->db->where($where_id);

							$update 				= $this->db->update("employee_time_in",$data_insert);

							if($update){
								$insert_logs 		= true;
							}
						}
					}

					if($insert_logs){

						$w 	= array(
							'employee_time_in_id' => $last_id
							);

						$this->db->where($w);
						$q = $this->db->get('employee_time_in');
						$last_timein = $q->row();

						$name 				= "";
						$positionx 			= "";
						$time_clock 		= "";

						$name 				= ($check_emp_no) ? $check_emp_no->first_name." ".$check_emp_no->last_name : "";
						$positionx 			= ($position) ? $position->position_name : "";
						
						if($last_timein){
							$last_t_worksched_idx		= $last_timein->work_schedule_id;
							$last_t_time_inx 			= $last_timein->time_in;
							$last_t_lunch_inx 			= $last_timein->lunch_in;
							$last_t_lunch_outx 			= $last_timein->lunch_out;
							$last_t_time_outx 			= $last_timein->time_out;
							$last_t_break1_inx 			= $last_timein->break1_in;
							$last_t_break1_outx 		= $last_timein->break1_out;
							$last_t_break2_inx 			= $last_timein->break2_in;
							$last_t_break2_outx 		= $last_timein->break2_out;
							$last_t_datex 	 			= $last_timein->date;

							if($last_t_time_inx){
								$time_clock 	= $time_clock."<p><span>Time In:</span>".date('h:i A',strtotime($last_t_time_inx))." | " .date('F d, Y',strtotime($last_t_time_inx))."</p>";
							}
							if($last_t_lunch_outx){
								$time_clock 	= $time_clock."<p><span>Lunch Out:</span>".date('h:i A',strtotime($last_t_lunch_outx))." | " .date('F d, Y',strtotime($last_t_lunch_outx))."</p>";
							}
							if($last_t_lunch_inx){
								$time_clock 	= $time_clock."<p><span>Lunch In:</span>".date('h:i A',strtotime($last_t_lunch_inx))." | " .date('F d, Y',strtotime($last_t_lunch_inx))."</p>";
							}
							if($last_t_break1_outx){
								$time_clock 	= $time_clock."<p><span>1st Break Out:</span>".date('h:i A',strtotime($last_t_break1_outx))." | " .date('F d, Y',strtotime($last_t_break1_outx))."</p>";
							}
							if($last_t_break1_inx){
								$time_clock 	= $time_clock."<p><span>1st Break In:</span>".date('h:i A',strtotime($last_t_break1_inx))." | " .date('F d, Y',strtotime($last_t_break1_inx))."</p>";
							}
							if($last_t_break2_outx){
								$time_clock 	= $time_clock."<p><span>2nd Break Out:</span>".date('h:i A',strtotime($last_t_break2_outx))." | " .date('F d, Y',strtotime($last_t_break2_outx))."</p>";
							}
							if($last_t_break2_inx){
								$time_clock 	= $time_clock."<p><span>2nd Break In:</span>".date('h:i A',strtotime($last_t_break2_inx))." | " .date('F d, Y',strtotime($last_t_break2_inx))."</p>";
							}
							if($last_t_time_outx){
								$time_clock 	= $time_clock."<p><span>Time Out:</span>".date('h:i A',strtotime($last_t_time_outx))." | " .date('F d, Y',strtotime($last_t_time_outx))."</p>";
							}
						}

						/* Added Mobile 
			             * Send approval for mobile clockin
			             */
			            $etiid = $last_id;
			            if ($allow_approval) {

                            $fullname = emp_name($emp_id);
                            
                            if($approver_id == "" || $approver_id == 0) {
                                // Employee with no approver will use default workflow approval
                                // add_workflow_approval_default_group($this->company_id,$this->account_id); // create default if dont have any
                                $approver_id = get_app_default_approver($this->company_id,"Mobile Clock-in")->approval_groups_via_groups_id;
                            }
                            //
                            if($snap_picture != ""){
                                $captured_pic = $capture_img;
                            } else {
                                $captured_pic = "";
                            }
                            $this->send_approvals($captured_pic, $emp_id, $company_id, $last_id, $fullname, $approver_id, 
                            $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                            $check_type, $emp_work_schedule_id, date($currentdates, strtotime("Y-m-d")));

                        }
			            /* added mobile */

						echo json_encode(array("result"=>1, "error_msg"=>"Time log captured."));
						return false;
					}

				}
				else {
					$w 	= array(
						'emp_id' => $check_emp_no->emp_id,
						'status' => 'Active',
					);

					$this->db->where($w);
					$this->db->order_by('date','desc');
					$q = $this->db->get('employee_time_in');
					$last_timein = $q->row();

					$name 				= "";
					$positionx 			= "";
					$time_clock 		= "";

					$name 				= ($check_emp_no) ? $check_emp_no->first_name." ".$check_emp_no->last_name : "";
					$positionx 			= ($position) ? $position->position_name : "";
					$etiid = $last_id;
					if($last_timein){
						$last_t_worksched_idx		= $last_timein->work_schedule_id;
						$last_t_time_inx 			= $last_timein->time_in;
						$last_t_lunch_inx 			= $last_timein->lunch_in;
						$last_t_lunch_outx 			= $last_timein->lunch_out;
						$last_t_time_outx 			= $last_timein->time_out;
						$last_t_break1_inx 			= $last_timein->break1_in;
						$last_t_break1_outx 		= $last_timein->break1_out;
						$last_t_break2_inx 			= $last_timein->break2_in;
						$last_t_break2_outx 		= $last_timein->break2_out;
						$last_t_datex 	 			= $last_timein->date;

						$etiid = $last_timein->employee_time_in_id;

						if($last_t_time_inx){
							$time_clock 	= $time_clock."<p><span>Time In:</span>".date('h:i A',strtotime($last_t_time_inx))." | " .date('F d, Y',strtotime($last_t_time_inx))."</p>";
						}
						if($last_t_lunch_outx){
							$time_clock 	= $time_clock."<p><span>Lunch Out:</span>".date('h:i A',strtotime($last_t_lunch_outx))." | " .date('F d, Y',strtotime($last_t_lunch_outx))."</p>";
						}
						if($last_t_lunch_inx){
							$time_clock 	= $time_clock."<p><span>Lunch In:</span>".date('h:i A',strtotime($last_t_lunch_inx))." | " .date('F d, Y',strtotime($last_t_lunch_inx))."</p>";
						}
						if($last_t_break1_outx){
							$time_clock 	= $time_clock."<p><span>1st Break Out:</span>".date('h:i A',strtotime($last_t_break1_outx))." | " .date('F d, Y',strtotime($last_t_break1_outx))."</p>";
						}
						if($last_t_break1_inx){
							$time_clock 	= $time_clock."<p><span>1st Break In:</span>".date('h:i A',strtotime($last_t_break1_inx))." | " .date('F d, Y',strtotime($last_t_break1_inx))."</p>";
						}
						if($last_t_break2_outx){
							$time_clock 	= $time_clock."<p><span>2nd Break Out:</span>".date('h:i A',strtotime($last_t_break2_outx))." | " .date('F d, Y',strtotime($last_t_break2_outx))."</p>";
						}
						if($last_t_break2_inx){
							$time_clock 	= $time_clock."<p><span>2nd Break In:</span>".date('h:i A',strtotime($last_t_break2_inx))." | " .date('F d, Y',strtotime($last_t_break2_inx))."</p>";
						}
						if($last_t_time_outx){
							$time_clock 	= $time_clock."<p><span>Time Out:</span>".date('h:i A',strtotime($last_t_time_outx))." | " .date('F d, Y',strtotime($last_t_time_outx))."</p>";
						}
					}
					if($failed){
						/* Added Mobile 
			             * Send approval for mobile clockin
			             */
			            $etiid = $last_timein->employee_time_in_id;
                        if ($allow_approval) {

                            $fullname = emp_name($emp_id);
                            
                            if($approver_id == "" || $approver_id == 0) {
                                // Employee with no approver will use default workflow approval
                                // add_workflow_approval_default_group($this->company_id,$this->account_id); // create default if dont have any
                                $approver_id = get_app_default_approver($this->company_id,"Mobile Clock-in")->approval_groups_via_groups_id;
                            }
                            
                            if($snap_picture != ""){
                                $captured_pic = $capture_img;
                            } else {
                                $captured_pic = "";
                            }
                            $this->send_approvals($captured_pic, $emp_id, $company_id, $last_id, $fullname, $approver_id, 
                            $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
                            $check_type, $emp_work_schedule_id, date($currentdates, strtotime("Y-m-d")));

                        }
			            /* added mobile */

						echo json_encode(array("result"=>1, "error_msg"=>"Time log captured."));
						return false;
					}
				}

			}
		} 

        $res_data = array(
            "result"	=> 0,
            "error_msg"	=> "Sorry, we cannot process this request at the moment. Please try again later."
        );
        echo json_encode($res_data);
		return false;
	}


	public function check_id_schedule_coincide($prev="0",$check_emp_no,$get_all_regular_schedule,$get_work_schedule_flex,$get_work_schedule,$date_capture,$currentdate,$check_company_id,$get_all_employee_timein,$rest_days){

		$correct_sched 									= array(
														'verify' => false,
														'workschedule_id' => '',
														'currentdate' => '',
														'sched_data' => '',
														'sched_type' => '',
														'worksched_data' => '',
														);
		$emp_work_schedule_id 							= false;
		$currentdate_l 									= date("l",strtotime($currentdate));

		if($check_emp_no){

			$emp_work_schedule_id 						= $this->import->emp_work_schedule($check_emp_no->emp_id,$check_company_id,$currentdate);
		}
		
		if($emp_work_schedule_id != FALSE){

			// check work day
			//$check_workday 								= $this->elm->check_workday($emp_work_schedule_id,$check_company_id);
			$check_workday 								= in_array_custom2("custom_search","worksched_id_{$emp_work_schedule_id}",$get_work_schedule);
			
			if($check_workday){

				// addittional checker here this will trap if the schedule get in this day coincide the the actual schedule
				// if uniform sched
				if($check_workday->work_type_name == "Uniform Working Days"){
					
					$last_t_workscheds 					= in_array_custom2("custom_search","rsched_id_{$emp_work_schedule_id}_{$currentdate_l}",$get_all_regular_schedule);
					
					if($last_t_workscheds){

						$latest_work_start_time 		= $currentdate." ".$last_t_workscheds->work_start_time;
						$latest_work_start_time 		= date("Y-m-d H:i:s",strtotime($latest_work_start_time. "- 120 minutes"));
						$currentdate_end 				= $currentdate;

						if($last_t_workscheds->work_start_time > $last_t_workscheds->work_end_time){

							$currentdate_end 			= date("Y-m-d",strtotime($currentdate. "+ 1 day"));
						}

						$latest_work_end_time 			= $currentdate_end." ".$last_t_workscheds->work_end_time;

						if($date_capture >= $latest_work_start_time){

							$correct_sched['verify'] 			= true;
							$correct_sched['workschedule_id'] 	= $emp_work_schedule_id;
							$correct_sched['currentdate'] 		= $currentdate;
							$correct_sched['sched_data']		= $last_t_workscheds;
							$correct_sched['worksched_data'] 	= $check_workday;
							$correct_sched['sched_type'] 		= "Uniform Working Days";
						}
					}
				}

				// if flexible hours
				else if($check_workday->work_type_name == "Flexible Hours"){

					$last_t_workscheds 					= in_array_custom2("custom_search","flex_{$emp_work_schedule_id}",$get_work_schedule_flex);
					
					if($last_t_workscheds){
						// if flex then and current date
						if($prev=="0"){
							// check the previous logs if already end or no logs at all // if 

							$prev_date 					= date("Y-m-d",strtotime($currentdate." - 1 day"));
							$prev_logs 					= in_array_custom2("custom_searchv3","emp_id_timeins_date_{$check_emp_no->emp_id}_{$prev_date}",$get_all_employee_timein);
							
							if($prev_logs){
								if($prev_logs->time_out){
									$correct_sched['verify'] 			= true;
									$correct_sched['workschedule_id'] 	= $emp_work_schedule_id;
									$correct_sched['currentdate'] 		= $currentdate;
									$correct_sched['sched_data']		= $last_t_workscheds;
									$correct_sched['worksched_data'] 	= $check_workday;
									$correct_sched['sched_type'] 		= "Flexible Hours";
								}else{
									$gap = (strtotime($date_capture) - strtotime($prev_logs->time_in))/60/60;

									$prev_plus_1day = date("Y-m-d H:i:s",strtotime($prev_logs->time_in. "+ 1day"));

									if($gap >= 22){
										$correct_sched['verify'] 			= true;
										$correct_sched['workschedule_id'] 	= $emp_work_schedule_id;
										$correct_sched['currentdate'] 		= $currentdate;
										$correct_sched['sched_data']		= $last_t_workscheds;
										$correct_sched['worksched_data'] 	= $check_workday;
										$correct_sched['sched_type'] 		= "Flexible Hours";
									}
									else{
										if($last_t_workscheds->latest_time_in_allowed){
											$latest_work_start_time 		= $currentdate." ".$last_t_workscheds->latest_time_in_allowed;
											$latest_work_start_time 		= date("Y-m-d H:i:s",strtotime($latest_work_start_time. "- 120 minutes"));
											
											if($date_capture >= $latest_work_start_time){

												$correct_sched['verify'] 			= true;
												$correct_sched['workschedule_id'] 	= $emp_work_schedule_id;
												$correct_sched['currentdate'] 		= $currentdate;
												$correct_sched['sched_data']		= $last_t_workscheds;
												$correct_sched['worksched_data'] 	= $check_workday;
												$correct_sched['sched_type'] 		= "Flexible Hours";
											}
										}
									}
								}
							}
							else{
								$current_date_hoursx = date("H:i:s",strtotime($date_capture));
								if($current_date_hoursx >= "22:00:00"){
									
								}
								else{
									$correct_sched['verify'] 			= true;
									$correct_sched['workschedule_id'] 	= $emp_work_schedule_id;
									$correct_sched['currentdate'] 		= $currentdate;
									$correct_sched['sched_data']		= $last_t_workscheds;
									$correct_sched['worksched_data'] 	= $check_workday;
									$correct_sched['sched_type'] 		= "Flexible Hours";
								}
							}
						}else{

							$correct_sched['verify'] 			= true;
							$correct_sched['workschedule_id'] 	= $emp_work_schedule_id;
							$correct_sched['currentdate'] 		= $currentdate;
							$correct_sched['worksched_data'] 	= $check_workday;
							$correct_sched['sched_type'] 		= "Flexible Hours";
						}
					}
				}

				// if open shift
				else if($check_workday->work_type_name == "Open Shift"){

					// if flex then and current date
					if($prev=="0"){
						// check the previous logs if already end or no logs at all // if 

						$prev_date 					= date("Y-m-d",strtotime($currentdate." - 1 day"));
						$prev_logs 					= in_array_custom2("custom_searchv3","emp_id_timeins_date_{$check_emp_no->emp_id}_{$prev_date}",$get_all_employee_timein);
						
						if($prev_logs){
							if($prev_logs->time_out){
								$correct_sched['verify'] 			= true;
								$correct_sched['workschedule_id'] 	= $emp_work_schedule_id;
								$correct_sched['currentdate'] 		= $currentdate;
								$correct_sched['worksched_data'] 	= $check_workday;
								$correct_sched['sched_type'] 		= "Open Shift";
							}else{
								$gap = (strtotime($date_capture) - strtotime($prev_logs->time_in))/60/60;

								$prev_plus_1day = date("Y-m-d H:i:s",strtotime($prev_logs->time_in. "+ 1day"));

								if($gap >= 22){
									$correct_sched['verify'] 			= true;
									$correct_sched['workschedule_id'] 	= $emp_work_schedule_id;
									$correct_sched['currentdate'] 		= $currentdate;
									$correct_sched['worksched_data'] 	= $check_workday;
									$correct_sched['sched_type'] 		= "Open Shift";
								}
							}
						}
						else{
							$correct_sched['verify'] 			= true;
							$correct_sched['workschedule_id'] 	= $emp_work_schedule_id;
							$correct_sched['currentdate'] 		= $currentdate;
							$correct_sched['worksched_data'] 	= $check_workday;
							$correct_sched['sched_type'] 		= "Open Shift";
						}
					}else{

						$correct_sched['verify'] 			= true;
						$correct_sched['workschedule_id'] 	= $emp_work_schedule_id;
						$correct_sched['currentdate'] 		= $currentdate;
						$correct_sched['worksched_data'] 	= $check_workday;
						$correct_sched['sched_type'] 		= "Open Shift";
					}
				}

				// check if rest days 
				
				if($rest_days){

					$check_rest_day 						= in_array_custom2("custom_search","rest_day_{$emp_work_schedule_id}_{$currentdate_l}",$rest_days);
					
					if($check_rest_day){
						// check the previous logs if already end or no logs at all 
						$prev_date 					= date("Y-m-d",strtotime($currentdate." - 1 day"));
						$prev_logs 					= in_array_custom2("custom_searchv3","emp_id_timeins_date_{$check_emp_no->emp_id}_{$prev_date}",$get_all_employee_timein);

						if($prev_logs){
							if($prev_logs->time_out){
								$correct_sched['verify'] 			= true;
								$correct_sched['workschedule_id'] 	= "";
								$correct_sched['currentdate'] 		= $currentdate;
								$correct_sched['worksched_data'] 	= "";
								$correct_sched['sched_type'] 		= "rest day";
							}else{
								$gap = (strtotime($date_capture) - strtotime($prev_logs->time_in))/60/60;

								$prev_plus_1day = date("Y-m-d H:i:s",strtotime($prev_logs->time_in. "+ 1day"));

								if($gap >= 22){
									$correct_sched['verify'] 			= true;
									$correct_sched['workschedule_id'] 	= "";
									$correct_sched['currentdate'] 		= $currentdate;
									$correct_sched['worksched_data'] 	= "";
									$correct_sched['sched_type'] 		= "rest day";
								}
							}
						}
						else{
							
							// if restday check if yesterday date or current time still belong to yesterdays shift
                            if($check_workday->work_type_name == "Uniform Working Days"){
                                $prev_date                          = date("Y-m-d",strtotime($currentdate." - 1 day"));
                                $prev_date_l                        = date("l",strtotime($prev_date));
                                $last_t_workscheds                  = in_array_custom2("custom_search","rsched_id_{$emp_work_schedule_id}_{$prev_date_l}",$get_all_regular_schedule);
                                
                                if($last_t_workscheds){

                                    $latest_work_start_time         = $prev_date." ".$last_t_workscheds->work_start_time;
                                    $latest_work_start_time         = date("Y-m-d H:i:s",strtotime($latest_work_start_time. "- 120 minutes"));
                                    $currentdate_end                = $prev_date;

                                    if($last_t_workscheds->work_start_time > $last_t_workscheds->work_end_time){

                                        $currentdate_end            = date("Y-m-d",strtotime($prev_date. "+ 1 day"));
                                    }

                                    $latest_work_end_time           = $currentdate_end." ".$last_t_workscheds->work_end_time;
                                    $date_time_karon                = date("Y-m-d H:i:s");
                                    
                                    if(strtotime($latest_work_end_time) < strtotime($date_time_karon)){
                                        $correct_sched['verify']            = true;
                                        $correct_sched['workschedule_id']   = "";
                                        $correct_sched['currentdate']       = $currentdate;
                                        $correct_sched['worksched_data']    = "";
                                        $correct_sched['sched_type']        = "rest day";
                                    }
                                    else{
                                        // this is not rest day
                                    }
                                }
                            }
                            // if flexible hours
							else if($check_workday->work_type_name == "Flexible Hours"){
								// this is not rest day
								if($prev=="0"){

								}else{
									$correct_sched['verify']            = true;
                                    $correct_sched['workschedule_id']   = "";
                                    $correct_sched['currentdate']       = $currentdate;
                                    $correct_sched['worksched_data']    = "";
                                    $correct_sched['sched_type']        = "rest day";
								}
							}
                            else{
                                $correct_sched['verify']            = true;
                                $correct_sched['workschedule_id']   = "";
                                $correct_sched['currentdate']       = $currentdate;
                                $correct_sched['worksched_data']    = "";
                                $correct_sched['sched_type']        = "rest day";
                            }
						}
					}
				}
			}
		}
		return $correct_sched;
	}

	public function get_all_rest_day($company_id){
		$row_array	= array();
		$s_uwd 		= array(
					'rest_day_id',
					'payroll_group_id',
					'rest_day',
					'company_id',
					'work_schedule_id',
					);
		$w_uwd 		= array(
					"company_id"	=> $company_id,
					'status'		=> 'Active'
					);
		$this->db->select($s_uwd);
		$this->db->where($w_uwd);
		$q_uwd = $this->db->get("rest_day");
		$result = $q_uwd->result();
		if($result){
			foreach ($result as $r1){
				$wd 	= array(
						"rest_day_id"				=> $r1->rest_day_id,
						"payroll_group_id"			=> $r1->payroll_group_id,
						"rest_day"					=> $r1->rest_day,
						"rest_day"					=> $r1->rest_day,
						"company_id"				=> $r1->company_id,
						"work_schedule_id"			=> $r1->work_schedule_id,
						"custom_search"				=> "rest_day_{$r1->work_schedule_id}_{$r1->rest_day}"
						);
				array_push($row_array,$wd);
			}
		}
		return $row_array;
	}

	// New Clockin based on fast clockin of barack with photo
	public function clock_in_v2_OLD() 
	{	
        $this->emp_id = $this->input->post('emp_id');
        $this->account_id = $this->input->post('account_id');
        $this->company_id = $this->input->post('company_id');
        $this->psa_id = $this->input->post('psa_id');
        $this->emp_no = $this->input->post('cloud_id');
        
        $psa_id = $this->psa_id;        
        $employee_id = $this->emp_id;
        			
		$check_company_id = $this->company_id;
		
		$emp_id = $this->emp_id;
		$location = $this->input->post('location');
		$emp_no = $this->emp_no;

		if($location ==  "" || $location ==  null) {
			echo json_encode(array("result"=>0,"error_msg"=>"hmm, we could not determine your location."));
			return false;
		}
		
		if($this->emp_id == "") {
			echo json_encode(array("result"=>0,"sess"=>$this->session->all_userdata(),"error_msg"=>"Hmmn, we could not find your ID please try logging in back."));
			return false;
        }
        
        // In here, it is assumed that clock guard settings is enabled and employee timesheet is required.
        // kay ge check naman previously

		$failed 						= true;
		$preshift 						= false;

		$currentdates 					= date('Y-m-d H:i:s');
		$currentdatesv2					= date('Y-m-d_H-i-s',strtotime($currentdates));
		$capture_img 					= "LOGIN_".$emp_no."_".$currentdatesv2.'.jpg';
		$snap_picture 					= $_FILES['file']['tmp_name']; // upload foto

		$date_capture 					= date("Y-m-d H:i:00",strtotime($currentdates));
		//$currentdate 					= date("Y-m-d",strtotime($currentdates));
		$snap 							= false;

		if($snap_picture != ""){

			$explode_img 				= explode("_", $capture_img);
			$date_explode 				= $explode_img[1];
			$timesub_explode 			= explode(".", $explode_img[2]);
			$time_explode 				= str_replace("-", ":", $timesub_explode[0]);
            $snap 						= true;
            
            $val 					= array(
                "emp_id"			=> $this->emp_id,
                "date_capture"		=> $date_capture,
                "image"				=> $capture_img
            );

            $save_image = $this->db->insert("employee_image_logs",$val);
        }
        
        // Move photo to directory
        $result = file_put_contents('../uploads/companies/'.$this->company_id.'/'.$capture_img, file_get_contents($_FILES['file']['tmp_name']));
        if(!$result) {
            echo json_encode(array("result"=>0,"success"=>$result,"error_msg"=>"Opps! we are not able to save your image this time.","capture_img"=>$snap_picture));
            return FALSE;
        }

		// This part copies logic from timesheet cronjob - fast no context version
		// Approval logic is added

		$emp_id 		= $this->emp_id;
		$source 		= 'mobile';
		$cloud_id 		= $this->emp_no;
		$company_id 	= $check_company_id;
		$currentdate 	= $currentdates;
		$c_date 	 	= date("Y-m-d",strtotime($currentdate));
		$approver_id 	= '';

		$emp_ids[] 		= $emp_id;

		$emp_work_schedule_ess 				= $this->importv2->emp_work_schedule_ess($company_id,$emp_ids);
		$emp_work_schedule_epi 				= $this->importv2->emp_work_schedule_epi($company_id,$emp_ids);
		
		$get_work_schedule_flex 			= $this->importv2->get_work_schedule_flex($company_id);
		$check_rest_days 					= $this->importv2->check_rest_day($company_id);
		$get_employee_payroll_information	= $this->importv2->get_employee_payroll_information($company_id,$emp_ids);
		$get_all_regular_schedule			= $this->importv2->get_all_regular_schedule($company_id);
		$get_all_schedule_blocks			= $this->importv2->get_all_schedule_blocks($company_id);
		$list_of_blocks 					= $this->importv2->list_of_blocks($company_id,$emp_ids);
		$get_work_schedule					= $this->importv2->get_work_schedule($company_id);

		$payroll_group1                     = in_array_custom("emp_id_{$emp_id}",$get_employee_payroll_information);

        if($payroll_group1){
            $payroll_group                  = $payroll_group1->payroll_group_id;
            $approver_id                    = $payroll_group1->location_base_login_approval_grp;
        }

		$emp_work_schedule_ess1 				= in_array_custom("emp_id_{$emp_id}_{$c_date}",$emp_work_schedule_ess);

		if($emp_work_schedule_ess1){

			$work_schedule_id 					= $emp_work_schedule_ess1->work_schedule_id;
		}
		else{
			$emp_work_schedule_epi1 			= in_array_custom("emp_id_{$emp_id}",$emp_work_schedule_epi);

			if($emp_work_schedule_epi1){

				$work_schedule_id 				= $emp_work_schedule_epi1->work_schedule_id;
			}
		}

		$newtimein_approver = $this->agm->get_approver_name_timein_location($emp_id,$company_id);
		$hours_notification = get_notify_settings($approver_id, $company_id);
		$is_workflow_enabled = is_workflow_enabled($company_id);

		if($work_schedule_id){
			// check work type
			$check_workday 						= in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);
			
			if($check_workday){
				if($check_workday->workday_type == "Flexible Hours"){
					// check if flexible hours
					// check previous logs

					$w 														= array(
																			"emp_id"	=> $emp_id,
																			"status" 	=> "Active"
																			);
					// $this->db->where("(time_in_status = 'approved' OR time_in_status is NULL)",FALSE,FALSE);
					$this->db->where("(time_in_status != 'reject' OR time_in_status is NULL)",FALSE,FALSE);
					$this->db->where($w);
					$this->db->order_by("time_in","DESC");
					$q 														= $this->db->get("employee_time_in",1,0);
					$r 														= $q->row();

					$double_time 											= false;
					$flex_r_time_out 										= "";
					$flex_r_date 											= "";
					$flex_r_time_in 										= "";
					$flex_r_lunch_out 										= "";
					$work_schedule_id_new 									= $work_schedule_id;

					if($r){

						// check if prev logs + 5 mins is greater than than the current logs
						$check_time_exist 									= date('Y-m-d H:i:00',strtotime($r->time_in.'+5 minutes'));
						if($currentdate <= $check_time_exist){
							$double_time 									= true;	
						}

						$flex_r_date 										= $r->date;
						$flex_r_time_in 									= $r->time_in;
						$flex_r_time_out 									= $r->time_out;
						$flex_r_lunch_out 									= $r->lunch_out;
						$flex_r_lunch_in 									= $r->lunch_in;
						$work_schedule_id_new 								= $r->work_schedule_id;
						$employee_time_in_id 								= $r->employee_time_in_id;
					}

					if(!$double_time){

						$check_flex 										= in_array_custom("flex_{$work_schedule_id}",$get_work_schedule_flex);


						// check if required to login
						$check_required_login 								= FALSE;

						if($check_flex){

							$check_required_login 							= ($check_flex->not_required_login != NULL || $check_flex->not_required_login != 0) ? TRUE : FALSE ;
							$check_latest_timein_allowed 					= $check_flex->latest_time_in_allowed;

							if($flex_r_time_out != "" || $flex_r_time_out != NULL ){

								$check_type 								= "time in";
							}
							else if(!$r){

								$check_type 								= "time in";
							}
							else{
								$flex_r_date_pls_1 							= date("Y-m-d",strtotime($flex_r_date. " +1 day"));
								$flex_r_time_in_pls_1						= date("Y-m-d H:i:00",strtotime($flex_r_time_in. " +1 day"));
								$flex_r_time_in_pls_1 						= date("Y-m-d H:i:00",strtotime($flex_r_time_in_pls_1. " -120 minutes"));

								if($flex_r_date_pls_1 >= $c_date){

									if($flex_r_time_in_pls_1 > $currentdate){

										$check_flex_prev					= in_array_custom("flex_{$work_schedule_id_new}",$get_work_schedule_flex);
										$check_workday 						= in_array_custom("worksched_id_{$work_schedule_id_new}",$get_work_schedule);
										
										if($check_flex_prev){

											$breaks_flex 					= $check_workday->break_rules;
											$check_latest_timein_allowed 	= $check_flex_prev->latest_time_in_allowed;

											if($breaks_flex == "capture"){

												if($flex_r_lunch_out == "" || $flex_r_lunch_out == NULL ){

													$check_type 			= "lunch out";
													$work_schedule_id 		= $work_schedule_id_new;
													$c_date 				= $flex_r_date;
												}
												else if($flex_r_lunch_in == "" || $flex_r_lunch_in == NULL ){

													$check_type 			= "lunch in";
													$work_schedule_id 		= $work_schedule_id_new;
													$c_date 				= $flex_r_date;
												}
												else{

													$check_type 			= "time out";
													$work_schedule_id 		= $work_schedule_id_new;
													$c_date 				= $flex_r_date;
												}
											}
											else{

												$check_type 				= "time out";
												$work_schedule_id 			= $work_schedule_id_new;
												$c_date 					= $flex_r_date;
											}
										}
										else{
											$check_type 					= "time in";
										}
									}
									else{
										$check_type 						= "time in";
									}
								}
								else{
									$check_type 							= "time in";
								}
							}
						}

						if($check_required_login){
		
							if(!$check_latest_timein_allowed){
								$ret = $this->elmf->insert_time_in_for_lastest_timein_not_allowed($c_date,$cloud_id,$this->min_log,$work_schedule_id,$check_type,$company_id,$source,$currentdate,$location);
								
								if ($ret) {

									/* Added Mobile 
						             * Send approval for mobile clockin
						             */
						            $etiid = ($ret->employee_time_in_id) ? $ret->employee_time_in_id : '';
						            $pd = $c_date;
                                    $fullname = $ret->first_name." ".$ret->last_name;
                                    
                                    if($approver_id == "" || $approver_id == 0) {
                                        // Employee with no approver will use default workflow approval
                                        // add_workflow_approval_default_group($this->company_id,$this->account_id); // create default if dont have any
                                        $approver_id = get_app_default_approver($this->company_id,"Mobile Clock-in")->approval_groups_via_groups_id;
                                    }
						            
						            $this->send_approvals($capture_img, $emp_id, $company_id, $etiid, $fullname, $approver_id, 
						            $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
						            $check_type, $work_schedule_id, $pd);
						            /* added mobile */

								}

								echo json_encode(array("result"=>1, "approver"=>$approver_id, "date2"=>date("F d, Y",strtotime($currentdate)),"time"=>date("H:i:s A",strtotime($currentdate))));
								
							}
							else{
								$ret = $this->elmf->insert_time_in($c_date,$cloud_id,$this->min_log,$work_schedule_id,$check_type,$company_id,$source,$currentdate,$location);
								
								if ($ret) {

									/* Added Mobile 
						             * Send approval for mobile clockin
						             */
						            $etiid = ($ret->employee_time_in_id) ? $ret->employee_time_in_id : '';
						            $pd = $c_date;
                                    $fullname = $ret->first_name." ".$ret->last_name;
                                    
                                    if($approver_id == "" || $approver_id == 0) {
                                        // Employee with no approver will use default workflow approval
                                        // add_workflow_approval_default_group($this->company_id,$this->account_id); // create default if dont have any
                                        $approver_id = get_app_default_approver($this->company_id,"Mobile Clock-in")->approval_groups_via_groups_id;
                                    }
						            
						            $this->send_approvals($capture_img, $emp_id, $company_id, $etiid, $fullname, $approver_id, 
						            $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
						            $check_type, $work_schedule_id, $pd);
						            /* added mobile */

								}
								echo json_encode(array("result"=>1, "approver"=>$approver_id, "date2"=>date("F d, Y",strtotime($currentdate)),"time"=>date("H:i:s A",strtotime($currentdate))));
							}
						}
						else{
							echo json_encode(array("result"=>0,"error_msg"=>"flex sched not required to login"));
						}
					}
					else{
						echo json_encode(array("result"=>0,"error_msg"=>"Double clock-in detected. Please clock-in after 5 minutes"));
					}
				} // end flex
				else {
					// not uniform or split
					$check_type 											= "time in";

					$w 														= array(
																			"emp_id"		=> $emp_id,
																			"status" 		=> "Active"
																			);

					$this->db->where("(time_in_status != 'reject' OR time_in_status is NULL)",FALSE,FALSE);
					$this->db->where($w);
					$this->db->order_by("time_in","DESC");
					$q 														= $this->db->get("employee_time_in",1,0);
					$r 														= $q->row();

					$double_time 											= false;

					if ($r) {

						$reg_r_date 										= $r->date;
						$reg_r_time_in 										= $r->time_in;
						$reg_r_time_out 									= $r->time_out;
						$reg_r_lunch_out 									= $r->lunch_out;
						$reg_r_lunch_in 									= $r->lunch_in;
						$reg_r_break1_out									= $r->break1_out;
						$reg_r_break1_in 									= $r->break1_in;
						$reg_r_break2_out									= $r->break2_out;
						$reg_r_break2_in 									= $r->break2_in;


						$work_schedule_id_new 								= $r->work_schedule_id;
						$employee_time_in_id 								= $r->employee_time_in_id;

						$latest_punch 										= array(
																			$reg_r_time_in,
																			$reg_r_time_out,
																			$reg_r_lunch_out,
																			$reg_r_lunch_in,
																			$reg_r_break1_out,
																			$reg_r_break1_in,
																			$reg_r_break2_out,
																			$reg_r_break2_in,
																			);
						sort($latest_punch);
						$last_punch 										= end($latest_punch);
						// check if prev logs + 5 mins is greater than than the current logs
						$check_time_exist 									= date('Y-m-d H:i:00',strtotime($last_punch.'+ 5 minutes'));
					
						if($currentdate <= $check_time_exist){
							$double_time 									= true;	
						}

						if(!$double_time){
							if($reg_r_time_out != "" || $reg_r_time_out != NULL ){
								$check_type 								= "time in";
							} else {
								$reg_r_date_pls_1 							= date("Y-m-d",strtotime($reg_r_date. " +1 day"));
								$reg_r_time_in_pls_1						= date("Y-m-d H:i:00",strtotime($reg_r_time_in. " +1 day"));
								$reg_r_time_in_pls_1 						= date("Y-m-d H:i:00",strtotime($reg_r_time_in. " -120 minutes"));

								if($reg_r_date_pls_1 >= $c_date){
									$day 									= date("l",strtotime($c_date));
									$check_reg_sched						= in_array_custom("rsched_id_{$work_schedule_id}_{$day}",$get_all_regular_schedule);
									$start_time 							= "";

									if($check_reg_sched){
										$start_time 						= $c_date." ".$check_reg_sched->work_start_time;
										$break_in_min 						= $check_reg_sched->break_in_min;
										$break_in_min 						= number_format($check_reg_sched->break_in_min,0);
										$start_time 						= date("Y-m-d H:i:00",strtotime($start_time." -120 minutes"));

										if($currentdate >= $start_time){

											if($break_in_min > 0){
												if($reg_r_lunch_out == "" || $reg_r_lunch_out == NULL ){

													$check_type 			= "lunch out";
													$work_schedule_id 		= $work_schedule_id_new;
													$c_date 				= $reg_r_date;
												}
												else if($reg_r_lunch_in == "" || $reg_r_lunch_in == NULL ){

													$check_type 			= "lunch in";
													$work_schedule_id 		= $work_schedule_id_new;
													$c_date 				= $reg_r_date;
												}
												else{

													$check_type 			= "time out";
													$work_schedule_id 		= $work_schedule_id_new;
													$c_date 				= $reg_r_date;
												}
											}
											else{

												$check_type 				= "time out";
												$work_schedule_id 			= $work_schedule_id_new;
												$c_date 					= $reg_r_date;
											}
											
										}else{
											$check_type 					= "time in";
										}
									}
									else{
										$check_type 						= "time out";
									}
								}
								else{
									$check_type 							= "time in";
								}
							}

							$this->emp_loginv2->insert_time_in($c_date,$cloud_id,$this->min_log,$work_schedule_id,$check_type,$source,false,"","",$company_id,$currentdate,"",false, $location, $capture_img);
							echo json_encode(array("result"=>"1","date2"=>date("F d, Y",strtotime($currentdate)),"time"=>date("H:i:s A",strtotime($currentdate))));
						} else {
							echo json_encode(array("result"=>0,"error_msg"=>"Double clock-in detected. Please clock-in after 5 minutes"));
						}

					} else {
						$check_type 										= "time in";
						$this->emp_loginv2->insert_time_in($c_date,$cloud_id,$this->min_log,$work_schedule_id,$check_type,$source,false,"","",$company_id,$currentdate,"",false, $location, $capture_img);
						echo json_encode(array("result"=>"1","date2"=>date("F d, Y",strtotime($currentdate)),"time"=>date("H:i:s A",strtotime($currentdate))));
					}
				}
			} // end check_workday
		} // end work_schedule_id
		else {
			echo json_encode(array("result"=>0,"error_msg"=>"You don't have any workschedule added in your payroll group. Please report to your HR person immediately."));
		}

		return false;
	}

	public function clock_in() 
	{	
        $this->emp_id = $this->input->post('emp_id');
        $this->account_id = $this->input->post('account_id');
        $this->company_id = $this->input->post('company_id');
        $this->psa_id = $this->input->post('psa_id');
        $this->emp_no = $this->input->post('cloud_id');
        
        $psa_id = $this->psa_id;        
        $employee_id = $this->emp_id;
        			
		$check_company_id = $this->company_id;
		
		$emp_id = $this->emp_id;
		$location = $this->input->post('location');
		$emp_no = $this->emp_no;

		if($location ==  "" || $location ==  null) {
			echo json_encode(array("result"=>0,"error_msg"=>"hmm, we could not determine your location."));
			return false;
		}
		
		if($this->emp_id == "") {
			echo json_encode(array("result"=>0,"sess"=>$this->session->all_userdata(),"error_msg"=>"Hmmn, we could not find your ID please try logging in back."));
			return false;
        }
        
        // In here, it is assumed that clock guard settings is enabled and employee timesheet is required.
        // kay ge check naman previously

		$failed 						= true;
		$preshift 						= false;

		$currentdates 					= date('Y-m-d H:i:s');
		$currentdatesv2					= date('Y-m-d_H-i-s',strtotime($currentdates));
		$capture_img 					= "LOGIN_".$emp_no."_".$currentdatesv2.'.jpg';
		$snap_picture 					= $_FILES['file']['tmp_name']; // upload foto

		$date_capture 					= date("Y-m-d H:i:00",strtotime($currentdates));
		//$currentdate 					= date("Y-m-d",strtotime($currentdates));
		$snap 							= false;

		if($snap_picture != ""){

			$explode_img 				= explode("_", $capture_img);
			$date_explode 				= $explode_img[1];
			$timesub_explode 			= explode(".", $explode_img[2]);
			$time_explode 				= str_replace("-", ":", $timesub_explode[0]);
            $snap 						= true;
            
            $val 					= array(
                "emp_id"			=> $this->emp_id,
                "date_capture"		=> $date_capture,
                "image"				=> $capture_img
            );

            $save_image = $this->db->insert("employee_image_logs",$val);
        }
        
        // Move photo to directory
        $result = file_put_contents('../uploads/companies/'.$this->company_id.'/'.$capture_img, file_get_contents($_FILES['file']['tmp_name']));
        if(!$result) {
            echo json_encode(array("result"=>0,"success"=>$result,"error_msg"=>"Opps! we are not able to save your image this time.","capture_img"=>$snap_picture));
            return FALSE;
        }

		// This part copies logic from timesheet cronjob - fast no context version
		// Approval logic is added

		$emp_id 		= $this->emp_id;
		$source 		= 'mobile';
		$cloud_id 		= $this->emp_no;
		$company_id 	= $check_company_id;
		$currentdate 	= $currentdates;
		$c_date 	 	= date("Y-m-d",strtotime($currentdate));
		$approver_id 	= '';

		$emp_ids[] 		= $emp_id;

		$emp_work_schedule_ess 				= $this->importv2->emp_work_schedule_ess($company_id,$emp_ids);
		$emp_work_schedule_epi 				= $this->importv2->emp_work_schedule_epi($company_id,$emp_ids);
		
		$get_work_schedule_flex 			= $this->importv2->get_work_schedule_flex($company_id);
		$check_rest_days 					= $this->importv2->check_rest_day($company_id);
		$get_employee_payroll_information	= $this->importv2->get_employee_payroll_information($company_id,$emp_ids);
		$get_all_regular_schedule			= $this->importv2->get_all_regular_schedule($company_id);
		$get_all_schedule_blocks			= $this->importv2->get_all_schedule_blocks($company_id);
		$list_of_blocks 					= $this->importv2->list_of_blocks($company_id,$emp_ids);
		$get_work_schedule					= $this->importv2->get_work_schedule($company_id);

		$payroll_group1                     = in_array_custom("emp_id_{$emp_id}",$get_employee_payroll_information);

        if($payroll_group1){
            $payroll_group                  = $payroll_group1->payroll_group_id;
            $approver_id                    = $payroll_group1->location_base_login_approval_grp;
        }

		$emp_work_schedule_ess1 				= in_array_custom("emp_id_{$emp_id}_{$c_date}",$emp_work_schedule_ess);

		if($emp_work_schedule_ess1){

			$work_schedule_id 					= $emp_work_schedule_ess1->work_schedule_id;
		}
		else{
			$emp_work_schedule_epi1 			= in_array_custom("emp_id_{$emp_id}",$emp_work_schedule_epi);

			if($emp_work_schedule_epi1){

				$work_schedule_id 				= $emp_work_schedule_epi1->work_schedule_id;
			}
		}

		$newtimein_approver = $this->agm->get_approver_name_timein_location($emp_id,$company_id);
		$hours_notification = get_notify_settings($approver_id, $company_id);
		$is_workflow_enabled = is_workflow_enabled($company_id);

		if($work_schedule_id){
			// check work type
			$check_workday 						= in_array_custom("worksched_id_{$work_schedule_id}",$get_work_schedule);
			
			if($check_workday){
				if($check_workday->workday_type == "Flexible Hours"){
					// check if flexible hours
					// check previous logs

					$w 														= array(
																			"emp_id"	=> $emp_id,
																			"status" 	=> "Active"
																			);
					// $this->db->where("(time_in_status = 'approved' OR time_in_status is NULL)",FALSE,FALSE);
					$this->db->where("(time_in_status != 'reject' OR time_in_status is NULL)",FALSE,FALSE);
					$this->db->where($w);
					$this->db->order_by("time_in","DESC");
					$q 														= $this->db->get("employee_time_in",1,0);
					$r 														= $q->row();

					$double_time 											= false;
					$flex_r_time_out 										= "";
					$flex_r_date 											= "";
					$flex_r_time_in 										= "";
					$flex_r_lunch_out 										= "";
					$work_schedule_id_new 									= $work_schedule_id;

					if($r){

						// check if prev logs + 5 mins is greater than than the current logs
						$check_time_exist 									= date('Y-m-d H:i:00',strtotime($r->time_in.'+5 minutes'));
						if($currentdate <= $check_time_exist){
							$double_time 									= true;	
						}

						$flex_r_date 										= $r->date;
						$flex_r_time_in 									= $r->time_in;
						$flex_r_time_out 									= $r->time_out;
						$flex_r_lunch_out 									= $r->lunch_out;
						$flex_r_lunch_in 									= $r->lunch_in;
						$work_schedule_id_new 								= $r->work_schedule_id;
						$employee_time_in_id 								= $r->employee_time_in_id;
					}

					if(!$double_time){

						$check_flex 										= in_array_custom("flex_{$work_schedule_id}",$get_work_schedule_flex);


						// check if required to login
						$check_required_login 								= FALSE;

						if($check_flex){

							$check_required_login 							= ($check_flex->not_required_login != NULL || $check_flex->not_required_login != 0) ? TRUE : FALSE ;
							$check_latest_timein_allowed 					= $check_flex->latest_time_in_allowed;

							if($flex_r_time_out != "" || $flex_r_time_out != NULL ){

								$check_type 								= "time in";
							}
							else if(!$r){

								$check_type 								= "time in";
							}
							else{
								$flex_r_date_pls_1 							= date("Y-m-d",strtotime($flex_r_date. " +1 day"));
								$flex_r_time_in_pls_1						= date("Y-m-d H:i:00",strtotime($flex_r_time_in. " +1 day"));
								$flex_r_time_in_pls_1 						= date("Y-m-d H:i:00",strtotime($flex_r_time_in_pls_1. " -120 minutes"));

								if($flex_r_date_pls_1 >= $c_date){

									if($flex_r_time_in_pls_1 > $currentdate){

										$check_flex_prev					= in_array_custom("flex_{$work_schedule_id_new}",$get_work_schedule_flex);
										$check_workday 						= in_array_custom("worksched_id_{$work_schedule_id_new}",$get_work_schedule);
										
										if($check_flex_prev){

											$breaks_flex 					= $check_workday->break_rules;
											$check_latest_timein_allowed 	= $check_flex_prev->latest_time_in_allowed;

											if($breaks_flex == "capture"){

												if($flex_r_lunch_out == "" || $flex_r_lunch_out == NULL ){

													$check_type 			= "lunch out";
													$work_schedule_id 		= $work_schedule_id_new;
													$c_date 				= $flex_r_date;
												}
												else if($flex_r_lunch_in == "" || $flex_r_lunch_in == NULL ){

													$check_type 			= "lunch in";
													$work_schedule_id 		= $work_schedule_id_new;
													$c_date 				= $flex_r_date;
												}
												else{

													$check_type 			= "time out";
													$work_schedule_id 		= $work_schedule_id_new;
													$c_date 				= $flex_r_date;
												}
											}
											else{

												$check_type 				= "time out";
												$work_schedule_id 			= $work_schedule_id_new;
												$c_date 					= $flex_r_date;
											}
										}
										else{
											$check_type 					= "time in";
										}
									}
									else{
										$check_type 						= "time in";
									}
								}
								else{
									$check_type 							= "time in";
								}
							}
						}

						if($check_required_login){
		
							if(!$check_latest_timein_allowed){
								$ret = $this->elmf->insert_time_in_for_lastest_timein_not_allowed($c_date,$cloud_id,$this->min_log,$work_schedule_id,$check_type,$company_id,$source,$currentdate,$location);
								
								if ($ret) {

									/* Added Mobile 
						             * Send approval for mobile clockin
						             */
						            $etiid = ($ret->employee_time_in_id) ? $ret->employee_time_in_id : '';
						            $pd = $c_date;
                                    $fullname = $ret->first_name." ".$ret->last_name;
                                    
                                    if($approver_id == "" || $approver_id == 0) {
                                        // Employee with no approver will use default workflow approval
                                        // add_workflow_approval_default_group($this->company_id,$this->account_id); // create default if dont have any
                                        $approver_id = get_app_default_approver($this->company_id,"Mobile Clock-in")->approval_groups_via_groups_id;
                                    }
						            
						            $this->send_approvals($capture_img, $emp_id, $company_id, $etiid, $fullname, $approver_id, 
						            $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
						            $check_type, $work_schedule_id, $pd);
						            /* added mobile */

								}

								echo json_encode(array("result"=>1, "approver"=>$approver_id, "date2"=>date("F d, Y",strtotime($currentdate)),"time"=>date("H:i:s A",strtotime($currentdate))));
								
							}
							else{
								$ret = $this->elmf->insert_time_in($c_date,$cloud_id,$this->min_log,$work_schedule_id,$check_type,$company_id,$source,$currentdate,$location);
								
								if ($ret) {

									/* Added Mobile 
						             * Send approval for mobile clockin
						             */
						            $etiid = ($ret->employee_time_in_id) ? $ret->employee_time_in_id : '';
						            $pd = $c_date;
                                    $fullname = $ret->first_name." ".$ret->last_name;
                                    
                                    if($approver_id == "" || $approver_id == 0) {
                                        // Employee with no approver will use default workflow approval
                                        // add_workflow_approval_default_group($this->company_id,$this->account_id); // create default if dont have any
                                        $approver_id = get_app_default_approver($this->company_id,"Mobile Clock-in")->approval_groups_via_groups_id;
                                    }
						            
						            $this->send_approvals($capture_img, $emp_id, $company_id, $etiid, $fullname, $approver_id, 
						            $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
						            $check_type, $work_schedule_id, $pd);
						            /* added mobile */

								}
								echo json_encode(array("result"=>1, "approver"=>$approver_id, "date2"=>date("F d, Y",strtotime($currentdate)),"time"=>date("H:i:s A",strtotime($currentdate))));
							}
						}
						else{
							echo json_encode(array("result"=>0,"error_msg"=>"flex sched not required to login"));
						}
					}
					else{
						echo json_encode(array("result"=>0,"error_msg"=>"Double clock-in detected. Please clock-in after 5 minutes"));
					}
				} // end flex
				else {
					// not uniform or split
					$check_type 											= "time in";

					$w 														= array(
																			"emp_id"		=> $emp_id,
																			"status" 		=> "Active"
																			);

					$this->db->where("(time_in_status != 'reject' OR time_in_status is NULL)",FALSE,FALSE);
					$this->db->where($w);
					$this->db->order_by("time_in","DESC");
					$q 														= $this->db->get("employee_time_in",1,0);
					$r 														= $q->row();

					$double_time 											= false;

					if ($r) {

						$reg_r_date 										= $r->date;
						$reg_r_time_in 										= $r->time_in;
						$reg_r_time_out 									= $r->time_out;
						$reg_r_lunch_out 									= $r->lunch_out;
						$reg_r_lunch_in 									= $r->lunch_in;
						$reg_r_break1_out									= $r->break1_out;
						$reg_r_break1_in 									= $r->break1_in;
						$reg_r_break2_out									= $r->break2_out;
						$reg_r_break2_in 									= $r->break2_in;


						$work_schedule_id_new 								= $r->work_schedule_id;
						$employee_time_in_id 								= $r->employee_time_in_id;

						$latest_punch 										= array(
																			$reg_r_time_in,
																			$reg_r_time_out,
																			$reg_r_lunch_out,
																			$reg_r_lunch_in,
																			$reg_r_break1_out,
																			$reg_r_break1_in,
																			$reg_r_break2_out,
																			$reg_r_break2_in,
																			);
						sort($latest_punch);
						$last_punch 										= end($latest_punch);
						// check if prev logs + 5 mins is greater than than the current logs
						$check_time_exist 									= date('Y-m-d H:i:00',strtotime($last_punch.'+ 5 minutes'));
					
						if($currentdate <= $check_time_exist){
							$double_time 									= true;	
						}

						if(!$double_time){
							if($reg_r_time_out != "" || $reg_r_time_out != NULL ){
								$check_type 								= "time in";
							} else {
								$reg_r_date_pls_1 							= date("Y-m-d",strtotime($reg_r_date. " +1 day"));
								$reg_r_time_in_pls_1						= date("Y-m-d H:i:00",strtotime($reg_r_time_in. " +1 day"));
								$reg_r_time_in_pls_1 						= date("Y-m-d H:i:00",strtotime($reg_r_time_in. " -120 minutes"));

								if($reg_r_date_pls_1 >= $c_date){
									$day 									= date("l",strtotime($c_date));
									$check_reg_sched						= in_array_custom("rsched_id_{$work_schedule_id}_{$day}",$get_all_regular_schedule);
									$start_time 							= "";

									if($check_reg_sched){
										$start_time 						= $c_date." ".$check_reg_sched->work_start_time;
										$break_in_min 						= $check_reg_sched->break_in_min;
										$break_in_min 						= number_format($check_reg_sched->break_in_min,0);
										$start_time 						= date("Y-m-d H:i:00",strtotime($start_time." -120 minutes"));

										if($currentdate >= $start_time){

											if($break_in_min > 0){
												if($reg_r_lunch_out == "" || $reg_r_lunch_out == NULL ){

													$check_type 			= "lunch out";
													$work_schedule_id 		= $work_schedule_id_new;
													$c_date 				= $reg_r_date;
												}
												else if($reg_r_lunch_in == "" || $reg_r_lunch_in == NULL ){

													$check_type 			= "lunch in";
													$work_schedule_id 		= $work_schedule_id_new;
													$c_date 				= $reg_r_date;
												}
												else{

													$check_type 			= "time out";
													$work_schedule_id 		= $work_schedule_id_new;
													$c_date 				= $reg_r_date;
												}
											}
											else{

												$check_type 				= "time out";
												$work_schedule_id 			= $work_schedule_id_new;
												$c_date 					= $reg_r_date;
											}
											
										}else{
											$check_type 					= "time in";
										}
									}
									else{
										$check_type 						= "time out";
									}
								}
								else{
									$check_type 							= "time in";
								}
							}

							$this->emp_loginv2->insert_time_in($c_date,$cloud_id,$this->min_log,$work_schedule_id,$check_type,$source,false,"","",$company_id,$currentdate,"",false, $location);
							echo json_encode(array("result"=>"1","date2"=>date("F d, Y",strtotime($currentdate)),"time"=>date("H:i:s A",strtotime($currentdate))));
						} else {
							echo json_encode(array("result"=>0,"error_msg"=>"Double clock-in detected. Please clock-in after 5 minutes"));
						}

					} else {
						$check_type 										= "time in";
						$this->emp_loginv2->insert_time_in($c_date,$cloud_id,$this->min_log,$work_schedule_id,$check_type,$source,false,"","",$company_id,$currentdate,"",false, $location);
						echo json_encode(array("result"=>"1","date2"=>date("F d, Y",strtotime($currentdate)),"time"=>date("H:i:s A",strtotime($currentdate))));
					}
				}
			} // end check_workday
		} // end work_schedule_id
		else {
			echo json_encode(array("result"=>0,"error_msg"=>"You don't have any workschedule added in your payroll group. Please report to your HR person immediately."));
		}

		return false;
	}
	
	public function clock_in_karaan() {
		$check_company_id = $this->login_screen_model->check_company_id($this->session->userdata('emp_id'));
		$check_company_id = $this->company_id;
		$psa_id = $this->session->userdata('psa_id');
		
		// EMPLOYEE ID
		$emp_id = $this->emp_id;
		$location = $this->input->post('location');
		$clockin_type = $this->input->post('type');

		// $check_clock_guard_settings

		$location = str_replace("<div>"," ",$location);
		$location = str_replace("</div>"," ", $location);
		$location = trim($location," ");
		$emp_no = $this->emp_no;
		
		if($location ==  "" || $location ==  null) {
			echo json_encode(array("result"=>3,"error_msg"=>"No Location Found"));
			return false;
		}
		
		if($emp_id == "") {
			echo json_encode(array("result"=>3,"error_msg"=>"- Invalid employee ID please try again."));
			return false;
		}
		
		if($clockin_type){
			#$emp_no = $this->input->post('emp_number');
			
			if($clockin_type['label'] == "Clock In"){
				$check_type = "time in";
				$snap_picture = $this->input->post('snap_picture');
				#$clock_data = $this->clock_in1($emp_no, $check_type, $snap_picture,$check_company_id);
			} elseif ($clockin_type['label'] == "Lunch Out") {
				$check_type = "lunch out";
				$snap_picture = $this->input->post('snap_picture');
				#$clock_data = $this->clock_in1($emp_no, $check_type, $snap_picture,$check_company_id);
			} elseif ($clockin_type['label'] == "Lunch In") {
				$check_type = "lunch in";
				$snap_picture = $this->input->post('snap_picture');
				#$clock_data = $this->clock_in1($emp_no, $check_type, $snap_picture,$check_company_id);
			} elseif ($clockin_type['label'] == "Clock Out") {
				$check_type = "time out";
				$snap_picture = $this->input->post('snap_picture');
				#$clock_data = $this->clock_in1($emp_no, $check_type, $snap_picture,$check_company_id);
			}
		
		}
		
		// CHECK EMPLOYEE ID
		if($emp_id != ""){
			// CHECK EMPLOYEE NUMBER OR PAYROLL CLOUD ID
			$w = array(
					"e.company_id" => $check_company_id,
					"e.emp_id"	=> $emp_id,
					"e.status" 	=> "Active"
			);
			$this->edb->where($w);
			$this->edb->join("employee AS e","e.account_id = a.account_id","LEFT");
			$query_emp_no = $this->edb->get("accounts AS a");
			$row_emp_no = $query_emp_no->row();
				
			// EMPLOYEE NUMBER
			$emp_no = ($row_emp_no) ? $row_emp_no->payroll_cloud_id : "" ;
			$check_company_id = ($row_emp_no) ? $row_emp_no->company_id : "" ;
			$capture_img = "LOGIN_".date('Y-m-d_H-i-s').'.jpg';
			$snap_picture = "";
				
			if($capture_img == ""){
				$date_capture = "";
			}else{
				$explode_img = explode("_", $capture_img);
				$date_explode = $explode_img[1];
				$timesub_explode = explode(".", $explode_img[2]);
				$time_explode = str_replace("-", ":", $timesub_explode[0]);
				$date_capture = "{$date_explode} {$time_explode}";
			}
				
			// check employee number
			if($emp_no == ""){
				/* UNLINK FILENAME */
				echo json_encode(array("result"=>3,"error_msg"=>"- Invalid employee number please try again."));
				return false;
			}
				
			/* version 1.1 */
			
			// check employee work schedule
			/**IDENTIFY THE PREVIOUS TIME IN
			 * This will help the workschedule of previous time in
			 * GET THE DATE
			 ***/
			 $w = array(
			 		"a.payroll_cloud_id"	=> $emp_no,
			 		"a.user_type_id"		=> "5",
			 		"eti.status" 			=> "Active",
			 		"eti.comp_id"			=> $this->company_id
			 );
			 
			 $this->edb->where($w);
			 
			 $arr = array(
			 		'time_in' 				=> 'eti.time_in',
			 		'time_out' 				=> 'eti.time_out',
			 		'work_schedule_id' 		=> 'eti.work_schedule_id',
			 		'employee_time_in_id' 	=> 'eti.employee_time_in_id'
			);
					
			 $this->edb->select($arr);
			 $this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			 $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			 $this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
			 $this->db->order_by("eti.time_in","DESC");
			 $q = $this->edb->get("employee_time_in AS eti",1,0);
			 $r = $q->row();
			 
			 /*if(!$r){
			 	$r = (object) array(
			 			'time_in' 				=> "",
			 			'time_out' 				=> "",
			 			'date' 					=> date('Y-m-d'),
			 			'employee_time_in_id' 	=> 0
				);								
			}*/
				
			$day = date('l');
			$double_time = false;
			$time_in = date('Y-m-d H:i:s');
			$work_schedule_id_new = 0;
			$employee_time_in_id = 0;
			$currentdate = date("Y-m-d");
			$check_time_exist = date('Y-m-d H:i:s',strtotime('+5 minutes',strtotime($r->time_in)));
			
			if($time_in <= $check_time_exist){
				$double_time = true;
			}
				
			if($q->num_rows() > 0){
				$r_date = date('l',strtotime($r->time_in));
				if($r->time_in != "" && $r->time_out == "" ){
					if($r_date != $day){
						$currentdate = date('Y-m-d',strtotime($r->time_in));
					}
				}
				$work_schedule_id_new = $r->work_schedule_id;
				$employee_time_in_id = $r->employee_time_in_id;
			}
			/**END NOW**/
			
			/* version 1.1 */
			$check_yesterday =  $this->elm->filter_date_tim_in($emp_no,$check_company_id);
			
			if($this->elm->if_splitschedule($work_schedule_id_new)){
				$val = $this->elm->check_endtime_notempty($emp_no,$check_company_id,$work_schedule_id_new,$employee_time_in_id);
			}else{
				$val = false;
			}
			
			if($check_yesterday || $val){
				$currentdate = date("Y-m-d");
			}
			
			$vx = $this->elm->activate_nightmare_trap($check_company_id,$emp_no);
			
			if($vx){
				$currentdate = $vx['currentdate'];
			}
			
			$check_employee_id = $this->login_screen_model->new_check_employee_id($emp_no,$check_company_id);
			$emp_work_schedule_id = false;
			if($check_employee_id){
				$emp_work_schedule_id = $this->import->emp_work_schedule($check_employee_id->emp_id,$check_company_id,$currentdate);
			
				if(!$emp_work_schedule_id){
					/* check employee id */
					$emp_work_schedule_id = $this->elm->if_no_workschedule($check_employee_id->emp_id,$check_company_id);
				}
			}
			
			$get_approval_settings_disable_status = $this->employee->get_approval_settings_disable_status($this->company_id);
			
			if($emp_work_schedule_id != FALSE){
				
				/* VERSION 1.1 EMPLOYEE LOGIN */
				
				// check valid from and valid to employee shift schedule
				/*$currentdate = date("Y-m-d");
				$check_vf_vt = $this->elm->valid_from_to($emp_no,$check_company_id,$currentdate);
				if(!$check_vf_vt){
					echo json_encode(array("success"=>0,"error_msg"=>"- Invalid employee number please try again.","hide_error_msg"=>"valid_from valid_to"));
					return false;
				}*/
	
				// check work day
				$check_workday = $this->elm->check_workday($emp_work_schedule_id,$check_company_id);
				if($check_workday){
				
					// check if flexible hours
					if($check_workday->work_type_name == "Flexible Hours"){ // check nelo 
						
						/* LOGIN EMPLOYEE FLEXIBLE HOURS */
							
						// check if required to login	
						$check_required_login = $this->elmf->check_required_login($emp_work_schedule_id,$check_company_id);
						if($check_required_login){
							
							/* LOGIN EMPLOYEE FLEXIBLE HOURS */
		
							// check employee number
							$check_emp_no = $this->login_screen_model->check_emp_no($emp_no);
		
							// check if employee exist within the company login
							$c = $this->login_screen_model->check_emp_compid($emp_no);
		
							if($c != FALSE){
								if($check_emp_no != FALSE){
									$check_emp_no = $this->login_screen_model->check_emp_info($emp_no);
									$position = $this->login_screen_model->get_position($check_emp_no->position);
									$time_list_box = $this->elm->get_time_list($emp_no,$emp_work_schedule_id,$check_company_id,$currentdate);
									$check_time_in = $this->elm->check_time_log($currentdate,$emp_no,5,$emp_work_schedule_id,false,$this->company_id);
									
									//check if no breaktime
									$gero = $this->elm->emp_login_lock($emp_work_schedule_id,$check_company_id,$emp_no,$currentdate);
									
									if($time_list_box && $check_time_in){
										if(($time_list_box->lunch_out == "" || $time_list_box->lunch_out == NULL) && $gero == 1){
											$check_type = "lunch out";
										}elseif(($time_list_box->lunch_in == "" || $time_list_box->lunch_in == NULL) && $gero == 1){
											$check_type = "lunch in";
										}elseif($time_list_box->time_out == "" || $time_list_box->time_out == NULL){
											$check_type = "time out";
										}
									}else{
										$check_type = "time in";
										$snap_picture = "";
									}
									// end
										
									// insert time in log
									$date = date("Y-m-d");
									
									$check_latest_timein_allowed = $this->elmf->check_lastest_timein_allowed($emp_work_schedule_id,$check_company_id);
										
									if(!$double_time){
										
										if(!$check_latest_timein_allowed){
											$insert_time_in = $this->elmf->insert_time_in_for_lastest_timein_not_allowed($date,$emp_no,$this->min_log,$emp_work_schedule_id,$check_type,$check_company_id,"mobile");							
										}else{
											$insert_time_in = $this->elmf->insert_time_in($date,$emp_no,$this->min_log,$emp_work_schedule_id,$check_type,$check_company_id,"mobile");												
										}
			
										if($insert_time_in != FALSE){
		
											/* DATES SUCCESS RETURN */
											$location_field = "";
		
											if( $insert_time_in->time_in != "" && $insert_time_in->lunch_out == ""
													&& $insert_time_in->lunch_in == "" && $insert_time_in->time_out == ""
											){
												// DATE TIMEIN
												$ret_date = $insert_time_in->time_in;
												$location_field = 'location_1';
												$mobile_status = 'mobile_clockin_status';
													
											}elseif( $insert_time_in->time_in != "" && $insert_time_in->lunch_out != ""
													&& $insert_time_in->lunch_in == "" && $insert_time_in->time_out == ""
											){
												// DATE LUNCHOUT
												$ret_date = $insert_time_in->lunch_out;
												$location_field = 'location_2';
												$mobile_status = 'mobile_lunchout_status';
												
											}elseif( $insert_time_in->time_in != "" && $insert_time_in->lunch_out != ""
													&& $insert_time_in->lunch_in != "" && $insert_time_in->time_out == ""
											){
												// DATE LUNCHIN
												$ret_date = $insert_time_in->lunch_in;
												$location_field = 'location_3';
												$mobile_status = 'mobile_lunchin_status';
												
											}elseif( $insert_time_in->time_in != "" && $insert_time_in->lunch_out != ""
													&& $insert_time_in->lunch_in != "" && $insert_time_in->time_out != ""
											){
												// DATE TIMEOUT
												$ret_date = $insert_time_in->time_out;
												$location_field = 'location_4';
												$mobile_status = 'mobile_clockout_status';
	
											}else{
												// DATE TIMEOUT
												$ret_date = $insert_time_in->time_out;
												$location_field = 'location_4';
												$mobile_status = 'mobile_clockout_status';
												
											}
											
											$work_sched_id = check_employee_work_schedule(date("Y-m-d",strtotime($insert_time_in->time_in)), $this->emp_id, $this->company_id)->work_schedule_id;
		
											// check for work schedule type
											$check_work_type_form = $this->employee->work_schedule_type($work_sched_id, $this->company_id);
											
											if($check_work_type_form == "Workshift"){
												// UPDATE EMPLOYEE TIME IN TO TIME IN STATUS PENDING
												$split_time_in_id = $insert_time_in->schedule_blocks_time_in_id;
												$non_split_time_in_id = $insert_time_in->employee_time_in_id;
												$this->db->where("schedule_blocks_time_in_id",$split_time_in_id);
												$update_val = array(
														"time_in_status"	=> "pending",
														"source" 			=> 'mobile',
														$location_field 	=> $location,
														"corrected" 		=> 'Yes'
												);
												$this->db->update("schedule_blocks_time_in",$update_val);
											} else {
												// UPDATE EMPLOYEE TIME IN TO TIME IN STATUS PENDING
												$time_in_id = $insert_time_in->employee_time_in_id;
												$this->db->where("employee_time_in_id",$time_in_id);
												$update_val = array(
														"time_in_status"	=> "pending",
														"source" 			=> 'mobile',
														$location_field 	=> $location,
														"corrected" 		=> 'Yes',
														$mobile_status		=> "pending"
												);
												$this->db->update("employee_time_in",$update_val);
											}
											
		
											/* SEND NOTIFICATIONS */
		
											// save approval token
											if($check_work_type_form == "Workshift"){
												$emp_time_id = $split_time_in_id;
												$employee_timein = $split_time_in_id;
												$non_split_timein_id = $non_split_time_in_id;
											} else {
												$emp_time_id = $time_in_id;
												$employee_timein = $time_in_id;
											}
											
											$str = 'abcdefghijk123456789';
											$shuffled = str_shuffle($str);
		
											// generate token level
											$str2 = 'ABCDEFG1234567890';
											$shuffled2 = str_shuffle($str2);
											$new_logs = array('ret_date'=> $ret_date);
											#$psa_id = $this->session->userdata('psa_id');
											$approver_id = $this->employee->get_approver_name_timein($this->emp_id,$this->company_id)->location_base_login_approval_grp;
											$newtimein_approver = $this->agm->get_approver_name_timein_location($this->emp_id,$this->company_id);
											
											if($check_work_type_form == "Workshift"){
												$timein_info = $this->agm->split_timein_information($emp_time_id);
											} else {
												$timein_info = $this->agm->timein_information($emp_time_id);
											}
											
											$fullname = ucfirst($timein_info->first_name)." ".ucfirst($timein_info->last_name);
											$employee_details = get_employee_details_by_empid($this->emp_id);
											$hours_notification = get_notify_settings($employee_details->location_base_login_approval_grp, $this->company_id);
											
											if($employee_details->location_base_login_approval_grp) {
												if(is_workflow_enabled($this->company_id)){
													if($newtimein_approver != FALSE){	
														if($hours_notification){
															$newtimein_approver = $this->agm->get_approver_name_timein_location($this->emp_id,$this->company_id);
															$new_level = 1;// 1   ////1 5  2345
															$lflag = 0;
													
															// with leveling
															if($hours_notification){
																foreach ($newtimein_approver as $cla){
																	$appr_name = ucwords($cla->first_name." ".$cla->last_name);
																	$appr_account_id = $cla->account_id;
																	$appr_email = $cla->email;
																	$appr_id = $cla->emp_id;
																	
																	if($cla->level == $new_level){
																		// send with link
																			
																		$new_level = $cla->level;
																		$this->send_location_notification($location, $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2, $appr_id, $mobile_status);

																		if($hours_notification->message_board_notification == "yes"){
																			#$token = $this->timeins->get_token($timein_info->approval_time_in_id, $this->company_id, $timein_info->emp_id);
																			$url = base_url()."approval/employee_time_in/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
																			$next_appr_notif_message = "{$fullname} used app for clock-in and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
																			send_to_message_board($this->psa_id, $appr_id, $this->emp_id, $this->company_id, $next_appr_notif_message, "system");
																		}
																		
																		$lflag = 1;
																		
																	}else{
																		// send without link
																		$this->send_location_notification($location, $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "", "", "", "", $mobile_status);
		
																		if($hours_notification->message_board_notification == "yes"){
																			$next_appr_notif_message = "{$fullname} used app for clock-in.";
																			send_to_message_board($psa_id, $appr_id, $this->emp_id,$this->company_id, $next_appr_notif_message, "system");
																		}
																			
																	}
																}
															}
															
															if($check_work_type_form == "Workshift"){
																$save_token = array(
																		"time_in_id"		=> $non_split_time_in_id,
																		"split_time_in_id"	=> $emp_time_id,
																		"token"				=> $shuffled,
																		"comp_id"			=> $this->company_id,
																		"emp_id"			=> $this->emp_id,
																		"approver_id"		=> $approver_id,
																		"level"				=> $new_level,
																		"token_level"		=> $shuffled2,
																		"location"			=> $location_field,
																		"flag_add_logs"		=> 2
																);
																$save_token_q = $this->db->insert("approval_time_in",$save_token);
																$id = $this->db->insert_id();
																$timein_update = array('approval_time_in_id'=>$id);
																$this->db->where('schedule_blocks_time_in_id', $emp_time_id);
																$this->db->update('schedule_blocks_time_in',$timein_update);
																$appr_err="";
															} else {
																$save_token = array(
																		"time_in_id"	=> $emp_time_id,
																		"token"			=> $shuffled,
																		"comp_id"		=> $this->company_id,
																		"emp_id"		=> $this->emp_id,
																		"approver_id"	=> $approver_id,
																		"level"			=> $new_level,
																		"token_level"	=> $shuffled2,
																		"location"		=> $location_field,
																		"flag_add_logs" => 2
																);
																$save_token_q = $this->db->insert("approval_time_in",$save_token);
																$id = $this->db->insert_id();
																$timein_update = array('approval_time_in_id'=>$id);
																$this->db->where('employee_time_in_id', $emp_time_id);
																$this->db->update('employee_time_in',$timein_update);
																$appr_err="";
															}
														}else{
															if($check_work_type_form == "Workshift"){
																$save_token = array(
																		"time_in_id"		=> $non_split_time_in_id,
																		"split_time_in_id"	=> $emp_time_id,
																		"token"				=> $shuffled,
																		"comp_id"			=> $this->company_id,
																		"emp_id"			=> $this->emp_id,
																		"approver_id"		=> $approver_id,
																		"level"				=> 1,
																		"token_level"		=> $shuffled2,
																		"location"			=> $location_field,
																		"flag_add_logs" 	=> 2
																);
																$save_token_q = $this->db->insert("approval_time_in",$save_token);
																$id = $this->db->insert_id();
																$timein_update = array('approval_time_in_id' => $id);
																$this->db->where('schedule_blocks_time_in_id', $emp_time_id);
																$this->db->update('schedule_blocks_time_in', $timein_update);
																$appr_err = "No Hours Notification";
															} else {
																$save_token = array(
																		"time_in_id"	=> $emp_time_id,
																		"token"			=> $shuffled,
																		"comp_id"		=> $this->company_id,
																		"emp_id"		=> $this->emp_id,
																		"approver_id"	=> $approver_id,
																		"level"			=> 1,
																		"token_level"	=> $shuffled2,
																		"location"		=> $location_field,
																		"flag_add_logs" => 2
																);
																$save_token_q = $this->db->insert("approval_time_in",$save_token);
																$id = $this->db->insert_id();
																$timein_update = array('approval_time_in_id'=>$id);
																$this->db->where('employee_time_in_id', $emp_time_id);
																$this->db->update('employee_time_in',$timein_update);
																$appr_err = "No Hours Notification";
															}
														}	
														
														echo json_encode(array("result"=>"1","date"=>date("F d, Y",strtotime($ret_date)),"time"=>date("H:i:s A",strtotime($ret_date))));
														return false;
													
													}else{
														echo json_encode(array("result"=>'1',"error_msg"=>""));
														return false;
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
																"time_in_status"	=>"pending",
																"source" 			=> 'mobile',
																$location_field		=> $location,
																"corrected" 		=> 'Yes',
																$mobile_status		=> $status
														);
														$this->db->update("employee_time_in",$update_val);
													
													
														echo json_encode(array("result"=>"1","error_msg"=>""));
														return false;
													}
												}
											} else {
												$time_in_id = $insert_time_in->employee_time_in_id;
												$this->db->where("employee_time_in_id",$time_in_id);
												$update_val = array(
														"time_in_status"	=> 'pending',
														"source" 			=> 'mobile',
														$location_field		=> $location,
														"corrected" 		=> 'Yes',
														$mobile_status		=> 'approved'
												);
												$this->db->update("employee_time_in",$update_val);
												
												echo json_encode(array("result"=>"1","error_msg"=>""));
												return false;
											}
											/* END SEND NOTIFICATIONS */
		
											echo json_encode(array("result"=>"1","date"=>date("F d, Y",strtotime($ret_date)),"time"=>date("H:i:s A",strtotime($ret_date))));
											return false;
										}else{
											/* UNLINK FILENAME */
											echo json_encode(array("result"=>3,"error_msg"=>"- Error found."));
											return false;
										}
									}else{
										echo json_encode(array("result"=>4,"error_msg"=>"Double clock-in detected. Please clock-in after 5 minutes"));
										
									}
								}else{
									/* UNLINK FILENAME */
									echo json_encode(array("result"=>3,"error_msg"=>"- Invalid employee number please try again."));
									return false;
								}
							}else{
								/* UNLINK FILENAME */
								echo json_encode(array("result"=>3,"error_msg"=>"- Invalid employee number please try again."));
								return false;
							}
						}else{
							/* UNLINK FILENAME */
							echo json_encode(array("result"=>3,"error_msg"=>"- Invalid employee number please try again."));
							return false;
						}
							
					}else{
						/* LOGIN EMPLOYEE REGULAR SCHEDULE OR WORKSHIFT */
							
						// check employee number
						$check_emp_no = $this->elm->check_emp_no($emp_no);
							
						// check if employee exist within the company login
						$c = $this->elm->check_emp_compid($emp_no);
							
						if($c != FALSE){
							if($check_emp_no != FALSE){
								// insert time in log
								$date = date("Y-m-d");
								$check_type = "time in";
								$insert_time_in = $this->elm->insert_time_in_mobile($date,$emp_no,$this->min_log,$emp_work_schedule_id,$check_type,"mobile",false, "","",$this->company_id);
								
								if(!$double_time){
									if($insert_time_in != FALSE){
		
										/* DATES SUCCESS RETURN */
											
										if( $insert_time_in->time_in != "" && $insert_time_in->lunch_out == ""
												&& $insert_time_in->lunch_in == "" && $insert_time_in->time_out == ""
										){
											// DATE TIMEIN
											$ret_date = $insert_time_in->time_in;
											$location_field = 'location_1';
											$mobile_status = 'mobile_clockin_status';
		
										}elseif( $insert_time_in->time_in != "" && $insert_time_in->lunch_out != ""
												&& $insert_time_in->lunch_in == "" && $insert_time_in->time_out == ""
										){
											// DATE LUNCHOUT
											$ret_date = $insert_time_in->lunch_out;
											$location_field = 'location_2';
											$mobile_status = 'mobile_lunchout_status';
											
										}elseif( $insert_time_in->time_in != "" && $insert_time_in->lunch_out != ""
												&& $insert_time_in->lunch_in != "" && $insert_time_in->time_out == ""
										){
											// DATE LUNCHIN
											$ret_date = $insert_time_in->lunch_in;
											$location_field = 'location_3';
											$mobile_status = 'mobile_lunchin_status';
											
										}elseif( $insert_time_in->time_in != "" && $insert_time_in->lunch_out != ""
												&& $insert_time_in->lunch_in != "" && $insert_time_in->time_out != ""
										){
											// DATE TIMEOUT
											$ret_date = $insert_time_in->time_out;
											$location_field = 'location_4';
											$mobile_status = 'mobile_clockout_status';
											
										}else{
											// DATE TIMEOUT
											$ret_date = $insert_time_in->time_out;
											$location_field = 'location_4';
											$mobile_status = 'mobile_clockout_status';
										}
										
										$work_sched_id = check_employee_work_schedule(date("Y-m-d",strtotime($insert_time_in->time_in)), $this->emp_id, $this->company_id)->work_schedule_id;
										
										// check for work schedule type
										$check_work_type_form = $this->employee->work_schedule_type($work_sched_id, $this->company_id);
												
										// UPDATE EMPLOYEE TIME IN TO TIME IN STATUS PENDING
										if($check_work_type_form == "Workshift"){
											$split_time_in_id = $insert_time_in->schedule_blocks_time_in_id;
											$non_split_time_in_id = $insert_time_in->employee_time_in_id;
											$this->db->where("schedule_blocks_time_in_id",$split_time_in_id);
											$update_val = array(
													"time_in_status"	=> "pending",
													"source" 			=> 'mobile',
													$location_field 	=> $location,
													"corrected" 		=> 'Yes'
											);
											$this->db->update("schedule_blocks_time_in",$update_val);
										} else {
											$time_in_id = $insert_time_in->employee_time_in_id;
											$this->db->where("employee_time_in_id",$time_in_id);
											$update_val = array(
													"time_in_status"	=>"pending",
													"source" 			=> 'mobile',
													$location_field		=> $location,
													"corrected" 		=> 'Yes',
													$mobile_status		=> 'pending'
											);
											$this->db->update("employee_time_in",$update_val);
										}
										
										/* SEND NOTIFICATIONS */
		
										// save approval token
										if($check_work_type_form == "Workshift"){
											$emp_time_id = $split_time_in_id;
											$employee_timein = $split_time_in_id;
											$non_split_timein_id = $non_split_time_in_id;
										} else {
											$emp_time_id = $time_in_id;
											$employee_timein = $time_in_id;
										}
										
										$str = 'abcdefghijk123456789';
										$shuffled = str_shuffle($str);
										
										$new_logs = array('ret_date'=> $ret_date);
										// generate token level
										$str2 = 'ABCDEFG1234567890';
										$shuffled2 = str_shuffle($str2);
											
										$employee_details = get_employee_details_by_empid($this->emp_id);
										$approver_id = $this->employee->get_approver_name_timein($this->emp_id,$this->company_id)->location_base_login_approval_grp;
										$newtimein_approver = $this->agm->get_approver_name_timein_location($this->emp_id,$this->company_id);
										
										if($check_work_type_form == "Workshift"){
											$timein_info = $this->agm->split_timein_information($emp_time_id);
										} else {
											$timein_info = $this->agm->timein_information($emp_time_id);
										}
										
										$fullname = ucfirst($timein_info->first_name)." ".ucfirst($timein_info->last_name);
										$hours_notification = get_notify_settings($employee_details->location_base_login_approval_grp, $this->company_id);
										
										if($employee_details->location_base_login_approval_grp) {
											if(is_workflow_enabled($this->company_id)){
												if($newtimein_approver != FALSE){
													if($hours_notification){
														#$newtimein_approver = $this->agm->get_approver_name_timein_location($this->emp_id,$this->company_id);
														$new_level = 1;// 1   ////1 5  2345
														$lflag = 0;
													
														// with leveling
														#if($check_type == "time out") {
														if($hours_notification){
															foreach ($newtimein_approver as $cla){
																$appovers_id = ($cla->emp_id) ? $cla->emp_id : "-99{$this->company_id}";
																$get_approval_group_via_groups_owner = $this->agm->get_approval_group_via_groups_owner($cla->approval_process_id, $cla->company_id, $cla->approval_groups_via_groups_id, $appovers_id);
																
																if($get_approval_group_via_groups_owner->emp_id == "-99{$this->company_id}"){
																	$owner_approver = get_approver_owner_info($this->company_id);
																	$appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
																	$appr_account_id = $owner_approver->account_id;
																	$appr_email = $owner_approver->email;
																	$appr_id = "-99{$this->company_id}";
																} else {
																	$appr_name = ucwords($cla->first_name." ".$cla->last_name);
																	$appr_account_id = $cla->account_id;
																	$appr_email = $cla->email;
																	$appr_id = $cla->emp_id;
																}
																
																if($cla->level == $new_level){
																	// send with link
																			
																	$new_level = $cla->level;
																	$this->send_location_notification($location, $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2, $appr_id, $mobile_status);

																	if($hours_notification->message_board_notification == "yes"){
																		#$token = $this->timeins->get_token($timein_info->approval_time_in_id, $this->company_id, $timein_info->emp_id);
																		$url = base_url()."approval/employee_time_in/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
																		$next_appr_notif_message = "{$fullname} used app for clock-in and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
																		#send_to_message_board($this->psa_id, $appr_id, $this->emp_id, $this->company_id, $next_appr_notif_message, "mobile");
																	}
																	
																	$lflag = 1;
																		
																}else{
																	// send without link
																	$this->send_location_notification($location, $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "" , "", "", "", $mobile_status);
																		
																	if($hours_notification->message_board_notification == "yes"){
																		$next_appr_notif_message = "{$fullname} used app for clock-in.";
																		#send_to_message_board($psa_id, $appr_id, $this->emp_id,$this->company_id, $next_appr_notif_message, "mobile");
																	}
																			
																}
															}
														}
														#}
														if($check_work_type_form == "Workshift"){
															$save_token = array(
																	"time_in_id"		=> $non_split_time_in_id,
																	"split_time_in_id"	=> $emp_time_id,
																	"token"				=> $shuffled,
																	"comp_id"			=> $this->company_id,
																	"emp_id"			=> $this->emp_id,
																	"approver_id"		=> $approver_id,
																	"level"				=> $new_level,
																	"token_level"		=> $shuffled2,
																	"location"			=> $location_field,
																	"flag_add_logs" 	=> 2
															);
																
															$save_token_q = $this->db->insert("approval_time_in",$save_token);
															$id = $this->db->insert_id();
															$timein_update = array('approval_time_in_id'=>$id);
															$this->db->where('schedule_blocks_time_in_id', $emp_time_id);
															$this->db->update('schedule_blocks_time_in',$timein_update);
															$appr_err="";
														} else {
															$save_token = array(
																	"time_in_id"	=> $emp_time_id,
																	"token"			=> $shuffled,
																	"comp_id"		=> $this->company_id,
																	"emp_id"		=> $this->emp_id,
																	"approver_id"	=> $approver_id,
																	"level"			=> $new_level,
																	"token_level"	=> $shuffled2,
																	"location"		=> $location_field,
																	"flag_add_logs" => 2
															);
																
															$save_token_q = $this->db->insert("approval_time_in",$save_token);
															$id = $this->db->insert_id();
															$timein_update = array('approval_time_in_id'=>$id);
															$this->db->where('employee_time_in_id', $emp_time_id);
															$this->db->update('employee_time_in',$timein_update);
															$appr_err="";
														}
													}else{
														if($check_work_type_form == "Workshift"){
															$save_token = array(
																	"time_in_id"		=> $non_split_time_in_id,
																	"split_time_in_id"	=> $emp_time_id,
																	"token"				=> $shuffled,
																	"comp_id"			=> $this->company_id,
																	"emp_id"			=> $this->emp_id,
																	"approver_id"		=> $approver_id,
																	"level"				=> 1,
																	"token_level"		=> $shuffled2,
																	"location"			=> $location_field,
																	"flag_add_logs" 	=> 2
															);
															$save_token_q = $this->db->insert("approval_time_in",$save_token);
															$id = $this->db->insert_id();
															$timein_update = array('approval_time_in_id'=>$id);
															$this->db->where('schedule_blocks_time_in_id', $emp_time_id);
															$this->db->update('schedule_blocks_time_in',$timein_update);
															$appr_err = "No Hours Notification";
														} else {
															$save_token = array(
																	"time_in_id"	=> $emp_time_id,
																	"token"			=> $shuffled,
																	"comp_id"		=> $this->company_id,
																	"emp_id"		=> $this->emp_id,
																	"approver_id"	=> $approver_id,
																	"level"			=> 1,
																	"token_level"	=> $shuffled2,
																	"location"		=> $location_field,
																	"flag_add_logs"	=> 2
															);
															$save_token_q = $this->db->insert("approval_time_in",$save_token);
															$id = $this->db->insert_id();
															$timein_update = array('approval_time_in_id'=>$id);
															$this->db->where('employee_time_in_id', $emp_time_id);
															$this->db->update('employee_time_in',$timein_update);
															$appr_err = "No Hours Notification";
														}
														
													}
			
													echo json_encode(array("result"=>"1","date2"=>date("F d, Y",strtotime($ret_date)),"time"=>date("H:i:s A",strtotime($ret_date))));
													return false;
													
												}else{
													echo json_encode(array("result"=>"1","error_msg"=>""));
													return false;
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
															"time_in_status"	=>"pending",
															"source" 			=> 'mobile',
															$location_field		=> $location,
															"corrected" 		=> 'Yes',
															$mobile_status		=> $status
													);
													$this->db->update("employee_time_in",$update_val);
												
												
													echo json_encode(array("result"=>"1","error_msg"=>""));
													return false;
												}
											}
										} else {
												
											$time_in_id = $insert_time_in->employee_time_in_id;
											$this->db->where("employee_time_in_id",$time_in_id);
											$update_val = array(
													"time_in_status"	=>"pending",
													"source" 			=> 'mobile',
													$location_field		=> $location,
													"corrected" 		=> 'Yes',
													$mobile_status		=> 'approved'
											);
											$this->db->update("employee_time_in",$update_val);
											
											
											echo json_encode(array("result"=>"1","error_msg"=>""));
											return false;
											
										}
										/* END SEND NOTIFICATIONS */
											
										echo json_encode(array("result"=>"1","date"=>date("F d, Y",strtotime($ret_date)),"time"=>date("H:i:s A",strtotime($ret_date))));
										return false;
									}else{
										/* UNLINK FILENAME */
										echo json_encode(array("result"=>3,"error_msg"=>"- Error found."));
										return false;
									}
								}else{
								 	echo json_encode(array("result"=>4,"error_msg"=>"- Double clock-in detected. Please clock-in after 5 minutes"));
							 	}
							}else{
								/* UNLINK FILENAME */
								echo json_encode(array("result"=>3,"error_msg"=>"- Invalid employee number please try again. 5"));
								return false;
							}
						}else{
							/* UNLINK FILENAME */
							echo json_encode(array("result"=>3,"error_msg"=>"- Invalid employee number please try again. 7"));
							return false;
						}
					}
				} else {
					echo json_encode(array("result"=>3,"error_msg"=>"-You don't have any workschedule added in your payroll group. Please report to your HR person immediately <span style='display:none'>error C0x</span>"));
						
				}
			}else{
				/* VERSION 1.0 EMPLOYEE LOGIN */
				
				/**IDENTIFY THE PREVIOUS TIME IN
				 * This will help the workschedule of previous time in
				 * GET THE DATE
				 ***/
				$w = array(
						"a.payroll_cloud_id"=>$emp_no,
						"a.user_type_id"=>"5",
						"eti.status" => "Active"
				);
				$this->edb->where($w);
				$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
				$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
				$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
				$this->db->order_by("eti.time_in","DESC");
				$q = $this->edb->get("employee_time_in AS eti",1,0);
				$r = $q->row();
				$day = date('l');
				$time_in = date('Y-m-d H:i:s');
				$work_schedule_id_new = 0;
				
				if($q->num_rows() > 0){
					if($q->num_rows() > 0){
						$r_date = date('l',strtotime($r->time_in));
						if($r->time_in != "" && $r->time_out == "" ){
							if($r_date != $day){
								$currentdate = date('Y-m-d',strtotime($r->time_in));
				
							}
						}
					}
				}
					
				
	
				// employee payroll group id
				$payroll_group_info = $this->emp_login->payroll_group_info($emp_no,$check_company_id);
				$payroll_group_id = $this->emp_login->payroll_group_id($emp_no,$check_company_id);
				//p($payroll_group_info);
				if($payroll_group_info)
					$emp_work_schedule_id = $payroll_group_info->work_schedule_id;
				else
					$emp_work_schedule_id = 0;
				// check if employee exist within the company login
				$c = $this->elm->check_emp_compid($emp_no);
				// check work day
				#$check_workday = $this->emp_login->check_workday($payroll_group_id,$check_company_id);
				$check_workday = $this->elm->check_workday($emp_work_schedule_id,$check_company_id);
				
				$vx = $this->elm->activate_nightmare_trap($check_company_id,$emp_no,$emp_work_schedule_id,true);
				
				if($vx){
					$currentdate = $vx['currentdate'];
				}
	
				// check workday
				if($check_workday == FALSE){
					/* UNLINK FILENAME */
					echo json_encode(array("result"=>3,"error_msg"=>"- Invalid employee number please try again."));
					return false;
				}
	
				// check if flexible hours
				if($check_workday->workday_type == "Flexible Hours"){
					
					// check if required to login
					$check_required_login = $this->emp_login->check_required_login($payroll_group_id,$check_company_id);
					if($check_required_login){
	
						/* LOGIN EMPLOYEE FLEXIBLE HOURS */
	
						// check employee number
						$check_emp_no = $this->login_screen_model->check_emp_no($emp_no);
	
						// check if employee exist within the company login
						$c = $this->login_screen_model->check_emp_compid($emp_no);
	
						if($c != FALSE){
							#if($c == $check_company_id){
								if($check_emp_no != FALSE){
									// insert time in log
									$date = date("Y-m-d");
									
									/*$check_latest_timein_allowed = $this->elmf->check_lastest_timein_allowed($emp_work_schedule_id,$check_company_id);	
									if(!$check_latest_timein_allowed){
										$insert_time_in = $this->elmf->insert_time_in_for_lastest_timein_not_allowed($date,$emp_no,$this->min_log,$emp_work_schedule_id,$check_type,$check_company_id);
									}else{
										$insert_time_in = $this->elmf->insert_time_in($date,$emp_no,$this->min_log,$emp_work_schedule_id,$check_type,$check_company_id);
									}*/
									
									$check_latest_timein_allowed = $this->elmf->check_lastest_timein_allowed($emp_work_schedule_id,$check_company_id);
										
									if(!$check_latest_timein_allowed){
										$insert_time_in = $this->elmf->insert_time_in_for_lastest_timein_not_allowed($date,$emp_no,$this->min_log,$emp_work_schedule_id,$check_type,$check_company_id);
									}else{
										$insert_time_in = $this->elmf->insert_time_in($date,$emp_no,$this->min_log,$emp_work_schedule_id,$check_type,$check_company_id);
									}
									
									if(!$double_time){
										if($insert_time_in != FALSE){
		
											/* DATES SUCCESS RETURN */
		
											if( $insert_time_in->time_in != "" && $insert_time_in->lunch_out == ""
													&& $insert_time_in->lunch_in == "" && $insert_time_in->time_out == ""
											){
												// DATE TIMEIN
												$ret_date = $insert_time_in->time_in;
												$location_field = 'location_1';
												$mobile_status = 'mobile_clockin_status';
												
											}elseif( $insert_time_in->time_in != "" && $insert_time_in->lunch_out != ""
													&& $insert_time_in->lunch_in == "" && $insert_time_in->time_out == ""
											){
												// DATE LUNCHOUT
												$ret_date = $insert_time_in->lunch_out;
												$location_field = 'location_2';
												$mobile_status = 'mobile_lunchout_status';
												
											}elseif( $insert_time_in->time_in != "" && $insert_time_in->lunch_out != ""
													&& $insert_time_in->lunch_in != "" && $insert_time_in->time_out == ""
											){
												// DATE LUNCHIN
												$ret_date = $insert_time_in->lunch_in;
												$location_field = 'location_3';
												$mobile_status = 'mobile_lunchin_status';
												
											}elseif( $insert_time_in->time_in != "" && $insert_time_in->lunch_out != ""
													&& $insert_time_in->lunch_in != "" && $insert_time_in->time_out != ""
											){
												// DATE TIMEOUT
												$ret_date = $insert_time_in->time_out;
												$location_field = 'location_4';
												$mobile_status = 'mobile_clockout_status';
												
											}else{
												// DATE TIMEOUT
												$ret_date = $insert_time_in->time_out;
												$location_field = 'location_4';
												$mobile_status = 'mobile_clockout_status';
												
											}
											
											$work_sched_id = check_employee_work_schedule(date("Y-m-d",strtotime($insert_time_in->time_in)), $this->emp_id, $this->company_id)->work_schedule_id;
											
											// check for work schedule type
											$check_work_type_form = $this->employee->work_schedule_type($work_sched_id, $this->company_id);
		
											// UPDATE EMPLOYEE TIME IN TO TIME IN STATUS PENDING
											if($check_work_type_form == "Workshift"){
												$split_time_in_id = $insert_time_in->schedule_blocks_time_in_id;
												$non_split_time_in_id = $insert_time_in->employee_time_in_id;
												
												$this->db->where("schedule_blocks_time_in_id",$time_in_id);
												$update_val = array(
														"time_in_status"	=> "pending",
														"source" 			=> 'mobile',
														$location_field 	=> $location,
														"corrected" 		=> 'Yes'
												);
												$this->db->update("schedule_blocks_time_in",$update_val);
											} else {
												$time_in_id = $insert_time_in->employee_time_in_id;
												$this->db->where("employee_time_in_id",$time_in_id);
												$update_val = array(
														"time_in_status"	=> "pending",
														"source" 			=> 'mobile',
														$location_field 	=> $location,
														"corrected" 		=> 'Yes',
														$mobile_status		=> 'pending'
												);
												$this->db->update("employee_time_in",$update_val);
											}
											
											/* SEND NOTIFICATIONS */
		
											// save approval token
											if($check_work_type_form == "Workshift"){
												$emp_time_id = $split_time_in_id;
												$employee_timein = $split_time_in_id;
												$non_split_timein_id = $non_split_time_in_id;
											} else {
												$emp_time_id = $time_in_id;
												$employee_timein = $time_in_id;
											}
											
											$str = 'abcdefghijk123456789';
											$shuffled = str_shuffle($str);
		
											// generate token level
											$emp_time_id = $employee_timein;
											$str2 = 'ABCDEFG1234567890';
											$shuffled2 = str_shuffle($str2);
											
											$new_logs = array('ret_date'=> $ret_date);
											
											$psa_id = $this->session->userdata('psa_id');
											$approver_id = $this->employee->get_approver_name_timein($this->emp_id,$this->company_id)->eBundy_approval_grp;
											$newtimein_approver = $this->agm->get_approver_name_timein_location($this->emp_id,$this->company_id);
											
											if($check_work_type_form == "Workshift"){
												$timein_info = $this->agm->split_timein_information($emp_time_id);
											} else {
												$timein_info = $this->agm->timein_information($emp_time_id);
											}
											
											$fullname = ucfirst($timein_info->first_name)." ".ucfirst($timein_info->last_name);
											$hours_notification = get_notify_settings($employee_details->location_base_login_approval_grp, $this->company_id);
											$employee_details = get_employee_details_by_empid($this->emp_id);
											
											if(is_workflow_enabled($this->company_id)){
												if($newtimein_approver != FALSE){
													if($hours_notification){
														#$newtimein_approver = $this->agm->get_approver_name_timein_location($this->emp_id,$this->company_id);
														$new_level = 1;// 1   ////1 5  2345
														$lflag = 0;
												
														// with leveling
														#if($check_type == "time out") {
														if($hours_notification){
															foreach ($newtimein_approver as $cla){
																$appr_name = ucwords($cla->first_name." ".$cla->last_name);
																$appr_account_id = $cla->account_id;
																$appr_email = $cla->email;
																$appr_id = $cla->emp_id;
																
																if($cla->level == $new_level){
																	// send with link
																		
																	$new_level = $cla->level;
																	$this->send_location_notification($location, $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2, $appr_id, $mobile_status);
	 																#if($hours_notification->sms == "yes"){
											
	 																#}
	
																	if($hours_notification->twitter_notification == "yes"){
																		
																	}
																	
																	if($hours_notification->message_board_notification == "yes"){
																		#$token = $this->timeins->get_token($timein_info->approval_time_in_id, $this->company_id, $timein_info->emp_id);
																		$url = base_url()."approval/employee_time_in/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
																		$next_appr_notif_message = "{$fullname} used app for clock-in and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
																		send_to_message_board($this->psa_id, $appr_id, $this->emp_id, $this->company_id, $next_appr_notif_message, "system");
																	}
																	
																	$lflag = 1;
																
																}else{
																	// send without link
																	$this->send_location_notification($location, $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "" , "", "", "", $mobile_status);
		 															if($hours_notification->sms == "yes"){
												
		 															}
																	if($hours_notification->twitter_notification == "yes"){
																		
																	}
																	if($hours_notification->message_board_notification == "yes"){
																		#$next_appr_notif_message = "A New Attendance Logs has been filed by {$fullname}.";
																		$next_appr_notif_message = "{$fullname} used app for clock-in.";
																		send_to_message_board($psa_id, $appr_id, $this->emp_id,$this->company_id, $next_appr_notif_message, "system");
																	}
												
																}
															}
														}
														#}
														if($check_work_type_form == "Workshift"){
															$save_token = array(
																	"time_in_id"		=> $non_split_time_in_id,
																	"split_time_in_id"	=> $emp_time_id,
																	"token"				=> $shuffled,
																	"comp_id"			=> $this->company_id,
																	"emp_id"			=> $this->emp_id,
																	"approver_id"		=> $approver_id,
																	"level"				=> $new_level,
																	"token_level"		=> $shuffled2,
																	"location"			=> $location_field,
																	"flag_add_logs" 	=> 2
															);
															$save_token_q = $this->db->insert("approval_time_in",$save_token);
															$id = $this->db->insert_id();
															$timein_update = array('approval_time_in_id'=>$id);
															$this->db->where('schedule_blocks_time_in_id', $emp_time_id);
															$this->db->update('schedule_blocks_time_in',$timein_update);
															$appr_err="";
														} else {
															$save_token = array(
																	"time_in_id"	=> $emp_time_id,
																	"token"			=> $shuffled,
																	"comp_id"		=> $this->company_id,
																	"emp_id"		=> $this->emp_id,
																	"approver_id"	=> $approver_id,
																	"level"			=> $new_level,
																	"token_level"	=> $shuffled2,
																	"location"		=> $location_field,
																	"flag_add_logs" => 2
															);
															$save_token_q = $this->db->insert("approval_time_in",$save_token);
															$id = $this->db->insert_id();
															$timein_update = array('approval_time_in_id'=>$id);
															$this->db->where('employee_time_in_id', $emp_time_id);
															$this->db->update('employee_time_in',$timein_update);
															$appr_err="";
														}
														
													}else{
														if($check_work_type_form == "Workshift"){
															$save_token = array(
																	"time_in_id"		=> $non_split_time_in_id,
																	"split_time_in_id"	=> $emp_time_id,
																	"token"				=> $shuffled,
																	"comp_id"			=> $this->company_id,
																	"emp_id"			=> $this->emp_id,
																	"approver_id"		=> $approver_id,
																	"level"				=> 1,
																	"token_level"		=> $shuffled2,
																	"location"			=> $location_field,
																	"flag_add_logs" 	=> 2
															);
															$save_token_q = $this->db->insert("approval_time_in",$save_token);
															$id = $this->db->insert_id();
															$timein_update = array('approval_time_in_id'=>$id);
															$this->db->where('schedule_blocks_time_in_id', $emp_time_id);
															$this->db->update('schedule_blocks_time_in',$timein_update);
															$appr_err = "No Hours Notification";
														} else {
															$save_token = array(
																	"time_in_id"	=> $emp_time_id,
																	"token"			=> $shuffled,
																	"comp_id"		=> $this->company_id,
																	"emp_id"		=> $this->emp_id,
																	"approver_id"	=> $approver_id,
																	"level"			=> 1,
																	"token_level"	=> $shuffled2,
																	"location"		=> $location_field,
																	"flag_add_logs" => 2
															);
															$save_token_q = $this->db->insert("approval_time_in",$save_token);
															$id = $this->db->insert_id();
															$timein_update = array('approval_time_in_id'=>$id);
															$this->db->where('employee_time_in_id', $emp_time_id);
															$this->db->update('employee_time_in',$timein_update);
															$appr_err = "No Hours Notification";
														}
														
														
													}
													echo json_encode(array("result"=>"1","date3"=>date("F d, Y",strtotime($ret_date)),"time"=>date("H:i:s A",strtotime($ret_date))));
													return false;
												
												}else{
													#echo json_encode(array("result"=>0,"error_msg"=>"No approvers found. 3"));
													echo json_encode(array("result"=>"1","error_msg"=>""));
													return false;
												}
											}else{
											
											}
		
											/* END SEND NOTIFICATIONS */
		
											echo json_encode(array("result"=>"1","date"=>date("F d, Y",strtotime($ret_date)),"time"=>date("H:i:s A",strtotime($ret_date))));
											return false;
										}else{
											/* UNLINK FILENAME */
											echo json_encode(array("result"=>3,"error_msg"=>"- Error found."));
											return false;
										}
									} else {
										echo json_encode(array("result"=>4,"error_msg"=>"- Double clock-in detected. Please clock-in after 5 minutes"));
									}
								}else{
									/* UNLINK FILENAME */
									echo json_encode(array("result"=>3,"error_msg"=>"- Invalid employee number please try again."));
									return false;
								}
							/*}else{
								/* UNLINK FILENAME
								echo json_encode(array("result"=>3,"error_msg"=>"- Invalid employee number please try again."));
								return false;
							}*/
						}else{
							/* UNLINK FILENAME */
							echo json_encode(array("result"=>3,"error_msg"=>"- Invalid employee number please try again."));
							return false;
						}
					}else{
						/* UNLINK FILENAME */
						echo json_encode(array("result"=>3,"error_msg"=>"- Invalid employee number please try again."));
						return false;
					}
				}else{
						
					/* LOGIN EMPLOYEE REGULAR SCHEDULE OR WORKSHIFT */
						
					// check employee number
					$check_emp_no = $this->login_screen_model->check_emp_no($emp_no);
						
					// check if employee exist within the company login
					$c = $this->login_screen_model->check_emp_compid($emp_no);
						
					if($c != FALSE){
						#if($c == $check_company_id){
						if($check_emp_no != FALSE){
							// insert time in log
							$date = date("Y-m-d");
							$arr = array('work_schedule_id' => $emp_work_schedule_id,
									'currentdate' => $currentdate
							);
							#$insert_time_in = $this->login_screen_model->insert_time_in($date,$emp_no,$this->min_log);
							$insert_time_in = $this->elm->insert_time_in($currentdate,$emp_no,$this->min_log,$emp_work_schedule_id,$check_type,"",true, "","",$check_company_id);
							if(!$double_time) {
								if($insert_time_in != FALSE){
										
									/* DATES SUCCESS RETURN */
										
									if( $insert_time_in->time_in != "" && $insert_time_in->lunch_out == ""
											&& $insert_time_in->lunch_in == "" && $insert_time_in->time_out == ""
									){
										// DATE TIMEIN
										$ret_date = $insert_time_in->time_in;
										$location_field = 'location_1';
										$mobile_status = 'mobile_clockin_status';
										
									}elseif( $insert_time_in->time_in != "" && $insert_time_in->lunch_out != ""
											&& $insert_time_in->lunch_in == "" && $insert_time_in->time_out == ""
									){
										// DATE LUNCHOUT
										$ret_date = $insert_time_in->lunch_out;
										$location_field = 'location_2';
										$mobile_status = 'mobile_lunchout_status';
										
									}elseif( $insert_time_in->time_in != "" && $insert_time_in->lunch_out != ""
											&& $insert_time_in->lunch_in != "" && $insert_time_in->time_out == ""
									){
										// DATE LUNCHIN
										$ret_date = $insert_time_in->lunch_in;
										$location_field = 'location_3';
										$mobile_status = 'mobile_lunchin_status';
										
									}elseif( $insert_time_in->time_in != "" && $insert_time_in->lunch_out != ""
											&& $insert_time_in->lunch_in != "" && $insert_time_in->time_out != ""
									){
										// DATE TIMEOUT
										$ret_date = $insert_time_in->time_out;
										$location_field = 'location_4';
										$mobile_status = 'mobile_clockout_status';
										
									}else{
										// DATE TIMEOUT
										$ret_date = $insert_time_in->time_out;
										$location_field = 'location_4';
										$mobile_status = 'mobile_clockout_status';
									}
									
									$work_sched_id = check_employee_work_schedule(date("Y-m-d",strtotime($insert_time_in->time_in)), $this->emp_id, $this->company_id)->work_schedule_id;
									
									// check for work schedule type
									$check_work_type_form = $this->employee->work_schedule_type($work_sched_id, $this->company_id);
	
									// UPDATE EMPLOYEE TIME IN TO TIME IN STATUS PENDING
									if($check_work_type_form == "Workshift"){
										$split_time_in_id = $insert_time_in->schedule_blocks_time_in_id;
										$non_split_time_in_id = $insert_time_in->employee_time_in_id;
										
										$this->db->where("schedule_blocks_time_in_id",$time_in_id);
										$update_val = array(
												"time_in_status"	=> "pending",
												"source"			=> 'mobile',
												$location_field		=> $location,
												"corrected" 		=> 'Yes'
										);
										$this->db->update("schedule_blocks_time_in",$update_val);
									} else {
										$time_in_id = $insert_time_in->employee_time_in_id;
										$this->db->where("employee_time_in_id",$time_in_id);
										$update_val = array(
												"time_in_status"	=> "pending",
												"source" 			=> 'mobile',
												$location_field 	=> $location,
												"corrected" 		=> 'Yes',
												$mobile_status		=> 'pending'
										);
										$this->db->update("employee_time_in",$update_val);
									}
									
										
									/* SEND NOTIFICATIONS */
	
									// save approval token
									if($check_work_type_form == "Workshift"){
										$emp_time_id = $split_time_in_id;
										$employee_timein = $split_time_in_id;
										$non_split_timein_id = $non_split_time_in_id;
									} else {
										$emp_time_id = $time_in_id;
										$employee_timein = $time_in_id;
									}
																		
									$str = 'abcdefghijk123456789';
									$shuffled = str_shuffle($str);
										
									// generate token level
									$str2 = 'ABCDEFG1234567890';
									$shuffled2 = str_shuffle($str2);
										
									$new_logs = array('ret_date'=> $ret_date);
									$psa_id = $this->session->userdata('psa_id');
									$approver_id = $this->employee->get_approver_name_timein($this->emp_id,$this->company_id)->eBundy_approval_grp;
									$newtimein_approver = $this->agm->get_approver_name_timein_location($this->emp_id,$this->company_id);
									
									if($check_work_type_form == "Workshift"){
										$timein_info = $this->agm->split_timein_information($emp_time_id);
									} else {
										$timein_info = $this->agm->timein_information($emp_time_id);
									}
									
									$fullname = ucfirst($timein_info->first_name)." ".ucfirst($timein_info->last_name);
									$hours_notification = get_notify_settings($employee_details->location_base_login_approval_grp, $this->company_id);
									$employee_details = get_employee_details_by_empid($this->emp_id);
									
									if(is_workflow_enabled($this->company_id)){
										if($newtimein_approver != FALSE){
											if($hours_notification){
												$newtimein_approver = $this->agm->get_approver_name_timein_location($this->emp_id,$this->company_id);
												$new_level = 1;// 1   ////1 5  2345
												$lflag = 0;
										
												// with leveling
												#if($check_type == "time out") {
												if($hours_notification){
													foreach ($newtimein_approver as $cla){
														$appr_name = ucwords($cla->first_name." ".$cla->last_name);
														$appr_account_id = $cla->account_id;
														$appr_email = $cla->email;
														$appr_id = $cla->emp_id;
														
														if($cla->level == $new_level){
															// send with link
																
															$new_level = $cla->level;
															$this->send_location_notification($location, $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2, $appr_id, $mobile_status);
															if($hours_notification->sms_notification == "yes"){
									
															}
															if($hours_notification->twitter_notification == "yes"){
																/*$check_twitter_acount = $this->agm->check_twitter_acount($appr_account_id);
																if($check_twitter_acount){
																	$token = $this->timeins->get_token($timein_info->approval_time_in_id, $this->company_id, $timein_info->emp_id);
																	$url = base_url()."approval/employee_time_in/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
																	$message = "{$fullname} used app for clock-in and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
																	$recipient_account = $check_twitter_acount->twitter;
																	$this->tweetontwitter($this->emp_id,$message,$recipient_account);
																}*/
															}
															#if($hours_notification->facebook_notification == "yes"){
																// coming soon
															#}
															if($hours_notification->message_board_notification == "yes"){
																#$token = $this->timeins->get_token($timein_info->approval_time_in_id, $this->company_id, $timein_info->emp_id);
																$url = base_url()."approval/employee_time_in/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
																$next_appr_notif_message = "{$fullname} used app for clock-in and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
																send_to_message_board($this->psa_id, $appr_id, $this->emp_id, $this->company_id, $next_appr_notif_message, "system");
															}
															
															$lflag = 1;
														
														}else{
															// send without link
															$this->send_location_notification($location, $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "" , "", "", "", $mobile_status);
															if($hours_notification->sms_notification == "yes"){
										
															}
															if($hours_notification->twitter_notification == "yes"){
																/*$check_twitter_acount = $this->agm->check_twitter_acount($appr_account_id);
																if($check_twitter_acount){
																	$message = "A New Attendance Log has been filed by {$fullname}.";
																	$recipient_account = $check_twitter_acount->twitter;
																	$this->tweetontwitter($this->emp_id,$message,$recipient_account);
																}*/
															}
															#if($hours_notification->facebook_notification == "yes"){
																// coming soon
															#}
															if($hours_notification->message_board_notification == "yes"){
																$next_appr_notif_message = "{$fullname} used app for clock-in.";
																send_to_message_board($psa_id, $appr_id, $this->emp_id,$this->company_id, $next_appr_notif_message, "system");
															}
										
														}
													}
												}
												#}
												if($check_work_type_form == "Workshift"){
													$save_token = array(
															"time_in_id"		=> $non_split_time_in_id,
															"split_time_in_id"	=> $emp_time_id,
															"token"				=> $shuffled,
															"comp_id"			=> $this->company_id,
															"emp_id"			=> $this->emp_id,
															"approver_id"		=> $approver_id,
															"level"				=> $new_level,
															"token_level"		=> $shuffled2,
															"location"			=> $location_field,
															"flag_add_logs" 	=> 2
													);
													$save_token_q = $this->db->insert("approval_time_in",$save_token);
													$id = $this->db->insert_id();
													$timein_update = array('approval_time_in_id'=>$id);
													$this->db->where('schedule_blocks_time_in_id', $emp_time_id);
													$this->db->update('schedule_blocks_time_in',$timein_update);
													$appr_err="";
												} else {
													$save_token = array(
															"time_in_id"	=> $emp_time_id,
															"token"			=> $shuffled,
															"comp_id"		=> $this->company_id,
															"emp_id"		=> $this->emp_id,
															"approver_id"	=> $approver_id,
															"level"			=> $new_level,
															"token_level"	=> $shuffled2,
															"location"		=> $location_field,
															"flag_add_logs" => 2
													);
													$save_token_q = $this->db->insert("approval_time_in",$save_token);
													$id = $this->db->insert_id();
													$timein_update = array('approval_time_in_id'=>$id);
													$this->db->where('employee_time_in_id', $emp_time_id);
													$this->db->update('employee_time_in',$timein_update);
													$appr_err="";
												}
											}else{
												if($check_work_type_form == "Workshift"){
													$save_token = array(
															"time_in_id"		=> $non_split_time_in_id,
															"split_time_in_id"	=> $emp_time_id,
															"token"				=> $shuffled,
															"comp_id"			=> $this->company_id,
															"emp_id"			=> $this->emp_id,
															"approver_id"		=> $approver_id,
															"level"				=> 1,
															"token_level"		=> $shuffled2,
															"location"			=> $location_field,
															"flag_add_logs" 	=> 2
													);
													$save_token_q = $this->db->insert("approval_time_in",$save_token);
													$id = $this->db->insert_id();
													$timein_update = array('approval_time_in_id'=>$id);
													$this->db->where('schedule_blocks_time_in_id', $emp_time_id);
													$this->db->update('schedule_blocks_time_in',$timein_update);
													#$appr_err = "No Hours Notification";
												} else {
													$save_token = array(
															"time_in_id"	=> $emp_time_id,
															"token"			=> $shuffled,
															"comp_id"		=> $this->company_id,
															"emp_id"		=> $this->emp_id,
															"approver_id"	=> $approver_id,
															"level"			=> 1,
															"token_level"	=> $shuffled2,
															"location"		=> $location_field,
															"flag_add_logs" => 2
													);
													$save_token_q = $this->db->insert("approval_time_in",$save_token);
													$id = $this->db->insert_id();
													$timein_update = array('approval_time_in_id'=>$id);
													$this->db->where('employee_time_in_id', $emp_time_id);
													$this->db->update('employee_time_in',$timein_update);
													#$appr_err = "No Hours Notification";
												}
											}
											
											echo json_encode(array("result"=>"1","date4"=>date("F d, Y",strtotime($ret_date)),"time"=>date("H:i:s A",strtotime($ret_date))));
											return false;
										
										}else{
											echo json_encode(array("result"=>"1","error_msg"=>""));
											return false;
										}
									}else{
										
										
									}
										
									/* END SEND NOTIFICATIONS */
										
									echo json_encode(array("result"=>"1","date"=>date("F d, Y",strtotime($ret_date)),"time"=>date("H:i:s A",strtotime($ret_date))));
									return false;
								}else{
									/* UNLINK FILENAME */
									echo json_encode(array("result"=>3,"error_msg"=>"- Error found."));
									return false;
								}
							} else {
								echo json_encode(array("result"=>4,"error_msg"=>"- Double clock-in detected. Please clock-in after 5 minutes"));
							}
						}else{
							/* UNLINK FILENAME */
							echo json_encode(array("result"=>3,"error_msg"=>"- Invalid employee number please try again."));
							return false;
						}
						/*}else{
							/* UNLINK FILENAME
							echo json_encode(array("result"=>3,"error_msg"=>"- Invalid employee number please try again."));
							return false;
						}*/
					}else{
						/* UNLINK FILENAME */
						echo json_encode(array("result"=>3,"error_msg"=>"- Invalid employee number please try again."));
						return false;
					}
				}
			}
		}
	}
	
	public function add_logs(){

        $netd = $this->input->post('new_employee_timein_date');

		$result = array(
            'error'=>true,
			'ereason'=> "Application is temporarily unavailable on mobile app. You may file your application on the browser instead.",
			'error_msg'=> "Application is temporarily unavailable on mobile app. You may file your application on the browser instead.",
			'time_error' => ""
		);
        echo json_encode($result);
        return false;

        // check if the application is lock for filing
        $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id, "add log",$this->emp_id, date("Y-m-d", strtotime($netd)));
        // $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id,"add log");
        if($get_lock_payroll_process_settings) {
            if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                $result = array(
                    'error'=>true,
                    'ereason'=> $get_lock_payroll_process_settings->application_error,
                    'error_msg'=> $get_lock_payroll_process_settings->application_error,
                    'time_error' => ""
                );
                echo json_encode($result);
                return false;
            } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                $result = array(
                    'error'=>true,
                    'ereason'=> $get_lock_payroll_process_settings->ts_app_recalculation_err_msg,
                    'error_msg'=> $get_lock_payroll_process_settings->ts_app_recalculation_err_msg,
                    'time_error' => ""
                );
                echo json_encode($result);
                return false;
            } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                $result = array(
                    'error'=>true,
                    'ereason'=> $get_lock_payroll_process_settings->py_app_recalculation_err_msg,
                    'error_msg'=> $get_lock_payroll_process_settings->py_app_recalculation_err_msg,
                    'time_error' => ""
                );
                echo json_encode($result);
                return false;
            }
        }
        
        $etd = $this->input->post('employee_timein_date');
        $time_in_inp = $this->input->post('time_in');
        $time_out_inp = $this->input->post('time_out');
        $lunch_in_inp = $this->input->post('lunch_in');
        $lunch_out_inp = $this->input->post('lunch_out');
        $break1_in_inp = $this->input->post('first_break_in');
        $break1_out_inp = $this->input->post('first_break_out');
        $break2_in_inp = $this->input->post('second_break_in');
        $break2_out_inp = $this->input->post('second_break_out');

		$sched_blocks_id = $this->input->post('schedule_blocks_id');
		$new_employee_timein_date = (!$netd) ? NULL : date("Y-m-d",strtotime($netd));
		$employee_timein_date = (!$etd) ? NULL : date("Y-m-d",strtotime($etd));
		$lunch_out_date = (!$lunch_out_inp) ? NULL : date("Y-m-d",strtotime($lunch_out_inp));
		$lunch_in_date = (!$lunch_in_inp) ? NULL : date("Y-m-d",strtotime($lunch_in_inp));

		$time_out_date = ($time_out_inp) ? date("Y-m-d",strtotime($time_out_inp)) : NULL;
		
		$lunch_out = (!$lunch_out_inp) ? NULL : date("H:i:s",strtotime($lunch_out_inp));
		$lunch_in = (!$lunch_in_inp) ? NULL : date("H:i:s",strtotime($lunch_in_inp));
		$time_out = (!$time_out_inp) ? NULL : date("H:i:s",strtotime($time_out_inp));
		        
        $new_time_in = $time_in = ($time_in_inp) ? date('Y-m-d H:i:s', strtotime($time_in_inp)) : NULL;
        $new_lunch_out = ($lunch_out_inp) ? date('Y-m-d H:i:s', strtotime($lunch_out_inp)) : NULL;
        $new_lunch_in = ($lunch_in_inp) ? date('Y-m-d H:i:s', strtotime($lunch_in_inp)) : NULL;
        $new_time_out = ($time_out_inp) ? date('Y-m-d H:i:s', strtotime($time_out_inp)) : NULL;
        
        $break_1_start_date_time = ($break1_in_inp) ? date('Y-m-d H:i:s', strtotime($break1_in_inp)) : NULL;
        $break_2_start_date_time = ($break2_in_inp) ? date('Y-m-d H:i:s', strtotime($break2_in_inp)) : NULL;

        $break_1_end_date_time = ($break1_out_inp) ? date('Y-m-d H:i:s', strtotime($break1_out_inp)) : NULL;
        $break_2_end_date_time = ($break2_out_inp) ? date('Y-m-d H:i:s', strtotime($break2_out_inp)) : NULL;
		
		$reason = $this->input->post('reason');

		$flaghalfday = $this->input->post("flag_halfday");
		
		if($flaghalfday == 1){
			$new_lunch_out = NULL; 
			$new_lunch_in = NULL;
		}
		
		$t_lunch_out = strtotime($lunch_out_date.' '.$lunch_out) - strtotime($time_in);
		$t_lunch_out_min = (strtotime($lunch_out_date.' '.$lunch_out) - strtotime($time_in)) /60;
		$t_lunch_in= strtotime($lunch_in_date.' '.$lunch_in) - strtotime($employee_timein_date.' '.$lunch_out);
		$t_lunch_in_min = (strtotime($lunch_in_date.' '.$lunch_in) - strtotime($employee_timein_date.' '.$lunch_out)) / 60;
		$t_time_out= strtotime($time_out_date.' '.$time_out) - strtotime($employee_timein_date.' '.$lunch_in);
        $t_time_out_min = (strtotime($time_out_date.' '.$time_out) - strtotime($employee_timein_date.' '.$lunch_in)) / 60;
        
		$work_sched_id = check_employee_work_schedule(date("Y-m-d",strtotime($employee_timein_date)), $this->emp_id, $this->company_id)->work_schedule_id;
		$is_holiday = $this->employee->get_holiday_date(date("Y-m-d",strtotime($employee_timein_date)),$this->emp_id,$this->company_id);
		$rest_day = $this->ews->get_rest_day($this->company_id,$work_sched_id,date("l",strtotime($employee_timein_date)));
		$emp_no = $this->employee->check_emp_no($this->emp_id,$this->company_id);
		
		$check_break_by_sched_blocks = $this->employee->check_break_by_sched_blocks($sched_blocks_id);
		
		// check for work schedule type
		$check_work_type_form = $this->employee->work_schedule_type($work_sched_id, $this->company_id);
		
		// check the break for non-split schedule
		$check_break_time_nonsplit = $this->employee->check_break_time($work_sched_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($employee_timein_date)));
		$your_total_hours = $this->elm->get_attendance_total_work_hours($this->emp_id,$this->company_id,$employee_timein_date,$work_sched_id,false,true);
		$your_total_hours_req = $this->elm->get_total_hours_logs($this->company_id, $this->emp_id,"", "", $time_in,$lunch_out,$lunch_in,$time_out, "", $work_sched_id);
		
		//check existing logs
		$check_existing_timein = $this->employee->check_existing_timein($this->emp_id,$this->company_id,$new_employee_timein_date,$new_time_in,$new_time_out);
		$check_existing_timein_date = $this->employee->check_existing_timein_date($this->emp_id,$this->company_id,$new_employee_timein_date);
		
		$tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($this->company_id,$work_sched_id);
		$get_schedule_settings = get_schedule_settings_by_workschedule_id($work_sched_id,$this->company_id,date("l", strtotime($employee_timein_date)));
		
		if($tardiness_rule_migrated_v3) {
		    $check_break_time_nonsplit = false;
		    if($get_schedule_settings) {
		        if($get_schedule_settings->enable_lunch_break == "yes" || $get_schedule_settings->enable_additional_breaks == "yes") {
		            if($get_schedule_settings->track_break_1 == "yes" || $get_schedule_settings->track_break_2 == "yes") {
		                $break_in_min = $get_schedule_settings->break_in_min + $get_schedule_settings->break_1_in_min + $get_schedule_settings->break_2_in_min;
		                if($break_in_min > 0) {
		                    $check_break_time_nonsplit = true;
		                }
		            }
		        }
		    }
		}
		
		$existing_logs = false;
		if($check_existing_timein_date) {
			$existing_logs = true;
		}
		
		if(!$check_break_time_nonsplit){
			$non_split_break = FALSE;
		} else {
			$non_split_break = TRUE;
		}
		
		if($check_work_type_form == "Workshift"){
			if($sched_blocks_id == NULL){
				$result = array(
						'error'=>true,
						'esched_blocks_id_err'=>'Schedule block is required',
						'error_msg'=>'Schedule block is required',
						'time_error' => ""
				);
				echo json_encode($result);
				return false;
			}
		} else {
			if($existing_logs == false) {
				if($flaghalfday != 1){
					if(!$is_holiday && !$rest_day){
						if($new_lunch_out == NULL){
							if($check_break_by_sched_blocks){
								if($check_break_by_sched_blocks->break_in_min != 0 || $check_break_by_sched_blocks->break_in_min != ""){
									$result = array(
											'error'=>true,
											'elunch_out_date'=>'Invalid lunch out time value',											
											'error_msg'=>'Invalid lunch out time value',											
											'time_error' => ""
									);
									echo json_encode($result);
									return false;
								}
							} else {
								if ($non_split_break == TRUE) {
									$result = array(
											'error'=>true,
											'elunch_out_date'=>'Invalid lunch out time value',
											'error_msg'=>'Invalid lunch out time value',
											'time_error' => ""
									);
									echo json_encode($result);
									return false;
								}
							}
						}
						
						if($new_lunch_in == NULL){
							if($check_break_by_sched_blocks){
								if($check_break_by_sched_blocks->break_in_min != 0 || $check_break_by_sched_blocks->break_in_min != ""){
									$result = array(
											'error'=>true,
											'elunch_in_date'=>'Invalid lunch in time value',
											'error_msg'=>'Invalid lunch in time value',
											'time_error' => ""
									);
									echo json_encode($result);
									return false;
								}
							} else {
								if ($non_split_break == TRUE) {
									$result = array(
											'error'=>true,
											'elunch_in_date'=>'Invalid lunch in time value',
											'error_msg'=>'Invalid lunch in time value',
											'time_error' => ""
									);
									echo json_encode($result);
									return false;
								}
								
							}
						}
					}
				}
			}
			
			if($new_time_out == NULL){
				$result = array(
						'error'=>true,
						'etime_out_date'=>'Invalid time out value',
						'error_msg'=>'Invalid time out value',
						'time_error' => ""
				);
				echo json_encode($result);
				return false;
			}
			
			if($existing_logs == false) {
				if(!$is_holiday && !$rest_day){
					if($flaghalfday != 1){
						if($t_lunch_out < 0){
							if($check_break_by_sched_blocks){
								if($check_break_by_sched_blocks->break_in_min != 0 || $check_break_by_sched_blocks->break_in_min != ""){
									$result = array(
											'error'=>true,
											'elunch_out_date'=>'Invalid lunch out time value',
											'error_msg'=>'Invalid lunch out time value',
											'time_error' => ""
									);
									echo json_encode($result);
									return false;
								}
							} else {
								if ($non_split_break == TRUE) {
									$result = array(
											'error'=>true,
											'elunch_out_date'=>'Invalid lunch out time value',
											'error_msg'=>'Invalid lunch out time value',
											'time_error' => ""
									);
									echo json_encode($result);
									return false;
								}
							}
								
						}elseif($t_lunch_out_min < $this->min_log){
							$result = array(
									'error'=>true,
									'elunch_out_date'=>'Lunch out value must be greater than '.$this->min_log,
									'error_msg'=>'Lunch out value must be greater than '.$this->min_log,
									'time_error' => ""
							);
							echo json_encode($result);
							return false;
						}
					}
					
					if($flaghalfday != 1){
						if($t_lunch_in < 0){
							if($check_break_by_sched_blocks){
								if($check_break_by_sched_blocks->break_in_min != 0 || $check_break_by_sched_blocks->break_in_min != ""){
									$result = array(
											'error'=>true,
											'elunch_in_date'=>'Invalid lunch in time value',
											'error_msg'=>'Invalid lunch in time value',
											'time_error' => ""
									);
									echo json_encode($result);
									return false;
								}
							} else {
								if ($non_split_break == TRUE) {
									$result = array(
											'error'=>true,
											'elunch_in_date'=>'Invalid lunch in time value',
											'error_msg'=>'Invalid lunch in time value',
											'time_error' => ""
									);
									echo json_encode($result);
									return false;
								}
							}
						}elseif($t_lunch_in_min < $this->min_log){
							if(check_if_have_break($this->emp_id, $this->company_id)){
								$result = array(
										'error'=>true,
										'elunch_in_date'=>'Lunch in value must be greater than '.$this->min_log,
										'error_msg'=>'Lunch in value must be greater than '.$this->min_log,
										'time_error' => ""
								);
								echo json_encode($result);
								return false;
							}
						}
						
					}
				}
			}
			if($t_time_out < 0){
				if($flaghalfday != 1){
					$result = array(
							'error'=>true,
							'etime_out_date'=>'Invalid time out value',
							'error_msg'=>'Invalid time out value',
							'time_error' => ""
					);
					echo json_encode($result);
					return false;
				}
			}elseif($t_time_out_min < $this->min_log){
				$result = array(
						'error'=>true,
						'etime_out_date'=>'Time out value must be greater than '.$this->min_log,
						'error_msg'=>'Time out value must be greater than '.$this->min_log,
						'time_error' => ""
				);
				echo json_encode($result);
				return false;
			}
			
			$check_date = date('Y-m-d');
				
			if($employee_timein_date > $check_date){
					
				$result = array(
						'error' => true,
						'etime_out_date' => "Date must not be in the future",
						'error_msg' => "Date must not be in the future",
						'time_error' => ""
				);
				echo json_encode($result);
				return false;
			}
		}
		
		//GETS WORK_SCHEDULE_ID
		$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$employee_timein_date);
		$emp_no = $this->employee->check_emp_no($this->emp_id,$this->company_id);
		$split = $this->elm->new_get_splitinfo_fritz($emp_no, $this->company_id, $work_schedule_id,$this->emp_id,$employee_timein_date,$new_time_in);
		$your_shifts_now = $this->employee->get_current_shift($work_schedule_id, $this->company_id,$employee_timein_date);
		$total_hours_for_the_day = 0;
		$latest_time_in_allowed = "";
		$shift_end = "";
		$work_type_name = "";
		if ($your_shifts_now) {
			foreach ($your_shifts_now as $zz) {
				$shift_end = date('H:i:s', strtotime($zz->end));
				$work_type_name = $zz->work_type_name;
				$total_hours_for_the_day = $zz->total_hours_for_the_day;
				$latest_time_in_allowed = $zz->latest_time_in_allowed;
			}
		} else {
			if($split){
				
			} else {
				if(!$rest_day) {
					$result = array(
							'error'=>true,
							'etime_out_date' => 'You have no schedule for this date.',
							'error_msg' => 'You have no schedule for this date.',
							'time_error' => ""
					);
					echo json_encode($result);
					return false;
				}
			}
		}
		
		if($work_type_name == "Flexible Hours") {
			if($latest_time_in_allowed != "") {
				$new_total_hours_for_the_day = number_format($total_hours_for_the_day);
				$lastest_time_in = date('Y-m-d', strtotime($employee_timein_date)).' '.date('H:i:s', strtotime($latest_time_in_allowed));
				$get_allowed_end_shift = date('Y-m-d H:i:s', strtotime($lastest_time_in.' +'.$new_total_hours_for_the_day.' hours'));
				
				if(strtotime($get_allowed_end_shift) > strtotime(date('Y-m-d H:i:s'))) {
					$result = array(
							'error'=>true,
							'etime_out_date' => 'Your shift for today has not yet ended.',
							'error_msg' => 'Your shift for today has not yet ended.',
							'time_error' => ""
					);
					echo json_encode($result);
					return false;
				}
			}
		} elseif ($work_type_name == "Uniform Working Days") {
			$time_in_date = date('Y-m-d', strtotime($employee_timein_date)).' '.$shift_end;
			
			if($time_in_date > date('Y-m-d H:i:s')) {
				$result = array(
						'error'=>true,
						'etime_out_date' => 'Your shift for today has not yet ended.',
						'error_msg' => 'Your shift for today has not yet ended.',
						'time_error' => ""
				);
				echo json_encode($result);
				return false;
			}
		}
		
		$check_pending_timesheet_for_double_entry = $this->employee->check_pending_timesheet_for_double_entry($this->emp_id,$this->company_id,$new_employee_timein_date);
		
		if($check_pending_timesheet_for_double_entry && $check_work_type_form != "Workshift") {
			$result = array(
					'error'=>true,
					'etime_out_date' => 'You have pending timesheet for this date. Please have your approver approve your timesheet first before applying for a new one.',
					'error_msg' => 'You have pending timesheet for this date. Please have your approver approve your timesheet first before applying for a new one.',
					'time_error' => ""
			);
			echo json_encode($result);
			return false;
		}
		
		$check_if_timeout_is_null = $this->employee->check_if_timeout_is_null($this->emp_id,$this->company_id,$new_employee_timein_date);
		$excess_logs = false;
		
		if($split['schedule_blocks_id'] == "" || $split['schedule_blocks_id'] == null) {
			if($check_if_timeout_is_null) {
				$result = array(
						'error'=>true,
						'etime_out_date' => 'Your timesheet for this date has zero hours worked. Please adjust and have your approver approve before adding a new timesheet.',
						'error_msg' => 'Your timesheet for this date has zero hours worked. Please adjust and have your approver approve before adding a new timesheet.',
						'time_error' => ""
				);
				echo json_encode($result);
				return false;
			}
			
			if($check_existing_timein) {
				$result = array(
					'error'=>true,
					'etime_out_date' => 'This timesheet has overlapped an existing timesheet. Please check you hours and try again.',
					'error_msg' => 'This timesheet has overlapped an existing timesheet. Please check you hours and try again.',
					'time_error' => ""
				);
				echo json_encode($result);
				return false;
				
				$excess_logs = true;
			}
			
		}
		$this->form_validation->set_rules("time_in", 'Time In', 'trim|required|xss_clean');
		$this->form_validation->set_rules("time_out", 'Time Out', 'trim|required|xss_clean');
		$this->form_validation->set_rules("new_employee_timein_date", 'Add Logs Date', 'trim|required|xss_clean');
        $this->form_validation->set_rules("reason", 'Reason', 'trim|required|xss_clean');
        if($check_work_type_form == "Workshift"){
            $this->form_validation->set_rules("schedule_blocks_id", 'Schedule Blocks', 'trim|required|xss_clean');
        }
        if($existing_logs == false) {
            if(check_if_have_break($this->emp_id, $this->company_id)){
                if($check_break_by_sched_blocks){
                    if($check_break_by_sched_blocks->break_in_min != 0){
                        $this->form_validation->set_rules("lunch_out", 'Lunch Out ', 'trim|required|xss_clean');
                        $this->form_validation->set_rules("lunch_in", 'Lunch In ', 'trim|required|xss_clean');
                    }
                } else {
                    if ($non_split_break == TRUE) {
                        $this->form_validation->set_rules("lunch_out", 'Lunch Out', 'trim|required|xss_clean');
                        $this->form_validation->set_rules("lunch_in", 'Lunch In', 'trim|required|xss_clean');
                    }
                }
            }
        }
        $this->form_validation->set_error_delimiters('', '');
		if ($this->form_validation->run()==true){
			$check_is_date_holidayv2 = $this->employee_v2->check_is_date_holidayv2($this->company_id);

			// if one of the approver is inactive the approver group will automatically change to default (owner)
			change_approver_to_default($this->emp_id,$this->company_id,"add_logs_approval_grp",$this->account_id);
									
			//GETS WORK_SCHEDULE_ID
			$this->work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$employee_timein_date);
			$check_rest_day = $this->elm->check_rest_day(date("l",strtotime($new_time_in)),$this->work_schedule_id,$this->company_id);
			$check_work_type = $this->employee->work_schedule_type($this->work_schedule_id, $this->company_id);
			
			$date_m_d = date("m-d", strtotime($employee_timein_date));
            $is_holiday_q = in_array_custom("date-{$date_m_d}",$check_is_date_holidayv2);

			// $check_holiday = $this->employee->get_holiday_date($employee_timein_date,$this->emp_id,$this->company_id);
            
            if($is_holiday_q){
                #if($is_holiday_q->repeat_type == "no"){
                if($is_holiday_q->date_type == "fixed") {
                    #$cur_year = date("Y");
                    #$hol_year = date("Y",strtotime($date));
                    
                    $app_m_d = date("m-d",strtotime($employee_timein_date));
                    $hol_m_d = date("m-d",strtotime($is_holiday_q->date));
                    
                    if($app_m_d == $app_m_d){
                        #if($cur_year == $hol_year){
                        $check_holiday = true;
                    } else {
                        $check_holiday = false;
                    }
                } else {
                    $check_holiday = true;
                }
            } else {
                $check_holiday = false;
            }

            // this is for RD/RA
            $flag_rd_include = "yes"; // field : flag_rd_include
            $rest_day_r_a = "no";
            $check_if_enable_working_on_restday = check_if_enable_working_on_restday($this->company_id,$this->work_schedule_id);
            $get_rest_day_settings = "no";
            if ($check_rest_day) {
                $get_rest_day_settings = get_rest_day_settings($this->company_id);
            }
            
            if($check_if_enable_working_on_restday || $get_rest_day_settings == "yes") {
                $flag_rd_include = "no";
                $rest_day_r_a = "yes";
            }
            
            // this is for holiday approval
            $flag_holiday_include = "yes";
            $holiday_approve = "no";
            
            if ($check_holiday) {
                $check_if_holiday_approval = holiday_approval_settings($this->company_id);
            } else {
                $check_if_holiday_approval = false;
            }
            
            if($check_if_holiday_approval) {
                $flag_holiday_include = "no";
                $holiday_approve = "yes";
            }

			if(check_if_enable_breaks_on_holiday($this->company_id,$this->work_schedule_id)) {
			    $check_holiday = false;
			}
			
			$late_min = $this->elm->late_min($this->company_id,$new_employee_timein_date,$this->emp_id,$this->work_schedule_id,$emp_no,$new_time_in);
			$overbreak_min = $this->elm->overbreak_min_fritz($this->company_id,$new_employee_timein_date,$this->emp_id,$this->work_schedule_id,$new_lunch_out,$emp_no,$new_lunch_in);
			//CHECK IF WORK SCHEDULE IS UNIFORM WORKING DAY / FLEXIBLE / SPLIT SCHEDULE
			$tardiness_settings = $this->elm->tardiness_settings($this->emp_id,$this->company_id);
			
			$fritz_tardiness = $late_min + $overbreak_min;
			
			$absent_min = $this->elm->calculate_attendance($this->company_id,$new_time_in,$new_time_out);
			
			$check_break_time_fritz = $this->employee->check_break_time_for_assumed($this->work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($new_employee_timein_date)));
			$is_break_assumed = is_break_assumed($this->work_schedule_id);
			
			$void = $this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($new_employee_timein_date)));
			#$void = "Waiting for approval";
			
			if($tardiness_rule_migrated_v3) {
			    // barrack code -- so need this param to calculate everything :D
			    $emp_ids                           = array($this->emp_id); // emp id
			    $min_range                         = date('Y-m-d', strtotime($new_employee_timein_date)); //date
			    $max_range                         = date('Y-m-d', strtotime($new_employee_timein_date)); //date
			    $min_range                         = date("Y-m-d",strtotime($min_range. " -1 day"));
			    $max_range                         = date("Y-m-d",strtotime($max_range. " +1 day"));
			    
			    // parent functions to be use for param
			    $split_arr                         = array();
			    $get_employee_payroll_information  = $this->importv2->get_employee_payroll_information($this->company_id,$emp_ids);
			    $emp_work_schedule_ess             = $this->importv2->emp_work_schedule_ess($this->company_id,$emp_ids);
			    $emp_work_schedule_epi             = $this->importv2->emp_work_schedule_epi($this->company_id,$emp_ids);
			    $list_of_blocks                    = $this->importv2->list_of_blocks($this->company_id,$emp_ids);
			    $get_all_schedule_blocks           = $this->importv2->get_all_schedule_blocks($this->company_id);
			    $get_all_regular_schedule          = $this->importv2->get_all_regular_schedule($this->company_id);
			    $get_work_schedule_flex            = $this->importv2->get_work_schedule_flex($this->company_id);
			    $company_holiday                   = $this->importv2->company_holiday($this->company_id);
			    $get_work_schedule                 = $this->importv2->get_work_schedule($this->company_id);
			    $get_employee_leave_application    = $this->importv2->get_employee_leave_application($this->company_id,$emp_ids);
			    $get_tardiness_settings            = $this->importv2->get_tardiness_settings($this->company_id);
			    $get_all_employee_timein           = $this->importv2->get_all_employee_timein($this->company_id,$emp_ids,$min_range,$max_range);
			    $get_all_schedule_blocks_time_in   = $this->importv2->get_all_schedule_blocks_time_in($this->company_id,$emp_ids,$min_range,$max_range);
			    $attendance_hours                  = is_attendance_active($this->company_id);
			    $get_tardiness_settingsv2          = $this->importv2->get_tardiness_settingsv2($this->company_id);
			    $tardiness_rounding                = $this->importv2->tardiness_rounding($this->company_id);
			    
			    $import_add_logsv2 = $this->importv2->import_add_logsv2($this->company_id, $this->emp_id, $reason, $new_time_in,$new_lunch_out,$new_lunch_in,date("Y-m-d H:i:s", strtotime($break_1_start_date_time)),date("Y-m-d H:i:s", strtotime($break_1_end_date_time)),date("Y-m-d H:i:s", strtotime($break_2_start_date_time)),date("Y-m-d H:i:s", strtotime($break_2_end_date_time)),$new_time_out, 0, $this->work_schedule_id,0,$split_arr,false,"mobile","",$new_employee_timein_date,"",$get_employee_payroll_information,$get_tardiness_settings,$company_holiday,"","",$get_all_regular_schedule,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$attendance_hours,$get_work_schedule,$get_employee_leave_application,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_tardiness_settingsv2,$tardiness_rounding);
			    $calculated_data = $import_add_logsv2['fields'];
			} else {
			    $calculated_data = false;
			}
			
			if($void == "Waiting for approval"){
			    $flag_payroll_correction = "yes";
			    $disabled_btn = true;
			} elseif ($void == "Closed") {
			    $flag_payroll_correction = "yes";
			} else {
			    $flag_payroll_correction = "no";
			}
			
			if($check_work_type == "Uniform Working Days"){
				$tardiness = 0;
				$undertime = 0;
				
				$get_uniform_sched_time = $this->employee->get_uniform_sched_time($this->work_schedule_id, $this->emp_id, $this->company_id, date("Y-m-d",strtotime($new_time_in)));
				if($get_uniform_sched_time) {
					// get total tardiness
					if($get_uniform_sched_time->latest_time_in_allowed !== "0" && $get_uniform_sched_time->latest_time_in_allowed !== null && $get_uniform_sched_time->latest_time_in_allowed !== "") {
						$sched_start = date("Y-m-d", strtotime($new_time_in)).' '.$get_uniform_sched_time->work_start_time;
						
						$grace_period = date("Y-m-d H:i:s", strtotime($sched_start." +".$get_uniform_sched_time->latest_time_in_allowed." minutes"));
						
						if(strtotime($new_time_in) > strtotime($grace_period)) {
							if($get_uniform_sched_time->break_in_min != null || $get_uniform_sched_time->break_in_min != "" || $get_uniform_sched_time->break_in_min != 0) {
								if($get_uniform_sched_time->break_in_min != 0){
									$minus_break = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / $get_uniform_sched_time->break_in_min;
								} else {
									$minus_break = (strtotime($new_lunch_in) - strtotime($new_lunch_out));
								}
								
								if($minus_break > $get_uniform_sched_time->break_in_min) {
									$tardiness_1 = $minus_break - $get_uniform_sched_time->break_in_min;
								} else {
									$tardiness_1 = 0;
								}
							} else {
								$tardiness_1 = 0;
							}
							
							$tardiness_2 = (strtotime($new_time_in) - strtotime($grace_period)) / $get_uniform_sched_time->latest_time_in_allowed;
							$tardiness = $tardiness_1 + $tardiness_2;
						} else {
							$tardiness = 0;
						}
					} else {
						$sched_start = date("Y-m-d", strtotime($new_time_in)).' '.$get_uniform_sched_time->work_start_time;
						$grace_period = date("Y-m-d H:i:s", strtotime($sched_start));
						
						if(strtotime($new_time_in) > strtotime($grace_period)) {
							if($get_uniform_sched_time->break_in_min != null || $get_uniform_sched_time->break_in_min != "" || $get_uniform_sched_time->break_in_min != 0) {
								$minus_break = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
								if($minus_break > $get_uniform_sched_time->break_in_min) {
									$tardiness_1 = $minus_break - $get_uniform_sched_time->break_in_min;
								} else {
									$tardiness_1 = 0;
								}
							} else {
								$tardiness_1 = 0;
							}
							
							$tardiness_2 = (strtotime($new_time_in) - strtotime($grace_period)) / 60;
							$tardiness = $tardiness_1 + $tardiness_2;
						} else {
							$tardiness = 0;
						}
					}
					
					// get total undertime
					$sched_end = date("Y-m-d", strtotime($new_time_out)).' '.$get_uniform_sched_time->work_end_time;
					
					if(strtotime($sched_end) > strtotime($new_time_out)) {
						$undertime = (strtotime($sched_end) - strtotime($new_time_out)) / 60;
					} else {
						$undertime = 0;
					}
				}
										
				//CHECKS IF RESTDAY OR NOT
				if($check_rest_day || $check_holiday || $existing_logs == true){
					// update total hours and total hours required rest day
					$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
					
					$flag_regular_or_excess = "regular";
					// $new_work_schedule_id = $this->work_schedule_id;
					
					if($existing_logs == true) {
						$flag_regular_or_excess = "excess";
						$this->work_schedule_id = "-2";
					}

					$tardiness_min = 0;
                    $undertime_min = 0;
                    $late_min = 0;
                    $overbreak_min = 0;
                    $absent_min = 0;
                    $current_date_nsd = 0;
                    $next_date_nsd = 0;
					
					if($tardiness_rule_migrated_v3) {
					    $get_total_hours = $calculated_data["total_hours_required"];
					    $tardiness_min = $calculated_data["tardiness_min"];
					    $undertime_min = $calculated_data["undertime_min"];
					    $late_min = $calculated_data["late_min"];
					    $overbreak_min = $calculated_data["overbreak_min"];
					    $absent_min = $calculated_data["absent_min"];
					    $new_work_schedule_id = $calculated_data["work_schedule_id"];
					    $current_date_nsd = $calculated_data["current_date_nsd"];
                        $next_date_nsd = $calculated_data["next_date_nsd"];
					} else {
					    $tardiness_min = 0;
					    $undertime_min = 0;
					    $late_min = 0;
					    $overbreak_min = 0;
					    $absent_min = 0;
                    }
                    
                    if ($this->work_schedule_id == "-1") {
                        $flag_rd_include = "no";
                        $rest_day_r_a = "yes";
                    }
						
					// UPDATE TIME INS
					$date_insert = array(
						"comp_id"                           => $this->company_id,
						"emp_id"                            => $this->emp_id,
						"work_schedule_id"                  => $new_work_schedule_id,
						"date"                              => date("Y-m-d",strtotime($new_employee_timein_date)),
						"time_in_status"                    => 'pending',
						"corrected"                         => 'Yes',
						"reason"                            => $reason,
						"time_in"                           => $new_time_in,
						"time_out"                          => $new_time_out,
					    "tardiness_min"                     => $tardiness_min,
					    "undertime_min"                     => $undertime_min,
						"total_hours"                       => $get_total_hours,
						"total_hours_required"              => $get_total_hours,
						"change_log_date_filed"             => date("Y-m-d H:i:s"),
					    "change_log_tardiness_min"          => $tardiness_min,
					    "change_log_undertime_min"          => $undertime_min,
						"change_log_time_in"                => $new_time_in,
						"change_log_time_out"               => $new_time_out,
						"change_log_total_hours"            => $get_total_hours,
						"change_log_total_hours_required"   => $get_total_hours,
						"source"                            => "EP",
					    "late_min"                          => $late_min,
					    "overbreak_min"                     => $overbreak_min,
					    "absent_min"                        => $absent_min,
					    "change_log_late_min"               => $late_min,
					    "change_log_overbreak_min"          => $overbreak_min,
					    "change_log_absent_min"             => $absent_min,
						"flag_regular_or_excess"            => $flag_regular_or_excess,
                        "flag_payroll_correction"           => $flag_payroll_correction,
                        "rest_day_r_a"                      => $rest_day_r_a,
                        "flag_rd_include"                   => $flag_rd_include,
                        "holiday_approve"                   => $holiday_approve,
                        "flag_holiday_include"              => $flag_holiday_include,
                        "current_date_nsd"                  => $current_date_nsd,
                        "next_date_nsd"                     => $next_date_nsd
					);
					
					$add_logs = $this->db->insert('employee_time_in', $date_insert);
						
				}elseif($new_lunch_out == NULL && $new_lunch_in == NULL && $flaghalfday == 1){
					
					//IF HALFDAY
					$flag_undertime = 0;
					
					$total_hours = 0;
					$total_hours_required = 0;
						
					$check_hours_flexible = $this->elm->check_hours_flex($this->company_id,$this->work_schedule_id);
					$check_breaktime = $this->elm->check_breaktime($this->company_id,$this->work_schedule_id);
			
					$workday = date("l",strtotime($new_time_in));
						
					$workday_settings_start_time = $this->elm->check_workday_settings_start_time($workday,$this->work_schedule_id,$this->company_id);
					$workday_settings_end_time = $this->elm->check_workday_settings_end_time($workday,$this->work_schedule_id,$this->company_id);
					//checks if latest timein is allowed
						
					if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
						// for night shift time in and time out value for working day
						$check_bet_timein = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
						$check_bet_timeout = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$workday_settings_end_time;
					}else{
							
						// for day shift time in and time out value for working day
						$check_bet_timein = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
						$check_bet_timeout = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_end_time;
					}
					// check between date time in to date time out
					$add_oneday_timein = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$workday_settings_start_time;
			
						
					/* EMPLOYEE WORK SCHEDULE */
					$wd_start = $this->elm->get_workday_sched_start($this->company_id,$this->work_schedule_id);
					$wd_end = $this->elm->get_end_time($this->company_id,$this->work_schedule_id);
					
					$total_work_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($time_in)),$this->work_schedule_id);
					$new_latest_timein_allowed = $this->employee->new_latest_timein_allowed($this->emp_id,$this->company_id,$this->work_schedule_id,"work_schedule");
					$new_st = $this->employee->for_leave_hoursworked_work_start_time_ws($this->emp_id,$this->company_id,$this->work_schedule_id);
					if($new_latest_timein_allowed){ // if latest time in is true
						if(strtotime($time_in) < strtotime($new_st)){
							$new_work_start_time = $new_st;
						}elseif(strtotime($new_st) <= strtotime($time_in) && strtotime($time_in) <= strtotime($new_latest_timein_allowed)){
							$new_work_start_time = $concat_start_datetime;
						}elseif(strtotime($time_in) > strtotime($new_latest_timein_allowed)){
							$new_work_start_time = $new_latest_timein_allowed;
						}
					}
					$end_time = date('H:i:s', strtotime($new_work_start_time.' +'.$total_work_hours.' hours'));
					$check_time = (strtotime($end_time) - strtotime($new_work_start_time) ) / 3600;
					$check2 = $check_time / 2;
					$check2 = round($check2, 0, PHP_ROUND_HALF_DOWN);
					
					$hd_check = date('H:i:s', strtotime($new_work_start_time.' +'.$check2.' hours'));
					$hd_check = strtotime($wd_start) + $check_time;
					
					#$b_st = $check_breaktime->work_start_time;
					#$b_et = $check_breaktime->work_end_time;
					$now_date = date("Y-m-d H:i:s",strtotime($new_time_out));
					$now_time = date("H:i:s",strtotime($new_time_out));
						
							
					if($check_break_time_fritz) { // for captured and assumed
						if($check_break_time_fritz->break_in_min > 0) {
							$shift_grace = ($check_break_time_fritz->latest_time_in_allowed) ? $check_break_time_fritz->latest_time_in_allowed : 0;
							$time_start_w_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($check_break_time_fritz->work_start_time." +".$shift_grace." minutes"));
							$time_start_wo_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($check_break_time_fritz->work_start_time));
							
							if($is_break_assumed) {
								$assumed_breaks = $is_break_assumed->assumed_breaks;
								$h = $assumed_breaks * 60;
							} else {
								$work_hrs = $check_break_time_fritz->total_work_hours;
								$h = ($work_hrs / 2) * 60;		
							}
						}
					} else {
						$shift_grace = ($get_uniform_sched_time->latest_time_in_allowed) ? $get_uniform_sched_time->latest_time_in_allowed : 0;
						$time_start_w_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($get_uniform_sched_time->work_start_time." +".$shift_grace." minutes"));
						$time_start_wo_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($get_uniform_sched_time->work_start_time));
						
						$work_hrs = $get_uniform_sched_time->total_work_hours;
						$h = ($work_hrs / 2) * 60;
						
					}
					
					// get the border of start time in
					if(strtotime($time_start_w_grace) > strtotime($new_time_in)) {
						//get the start and end of break time
						$time_in_break = date('Y-m-d H:i:s', strtotime($new_time_in. " +{$h} minutes"));
						$time_start_w_grace = $new_time_in;
					} else {
						$time_in_break = date('Y-m-d H:i:s',strtotime($time_start_w_grace. " +{$h} minutes"));
						$time_start_w_grace = $time_start_w_grace;
					}
							
							
					if(strtotime($new_time_in) < strtotime($time_in_break)) {
						//get the diff the time in input and the start break time
						$diff_timein_and_break = (strtotime($time_in_break) - strtotime($time_start_w_grace));
						$diff_timein_and_break = $diff_timein_and_break / 2;
						$boundary_of_halfday = date('Y-m-d H:i:s', strtotime($time_start_w_grace.' +'.$diff_timein_and_break.' seconds'));
							
						if(strtotime($boundary_of_halfday) <= strtotime($new_time_in)) {
							$flag_halfday = 2; // second half
						} else {
							$flag_halfday = 1; // first half
						}
					} else {
						$flag_halfday = 2; // second half
					}

					if($check_break_time_fritz) {
						$shift_grace = ($check_break_time_fritz->latest_time_in_allowed) ? $check_break_time_fritz->latest_time_in_allowed : 0;
						$b_st = $check_break_time_fritz->work_start_time;
						$b_et = $check_break_time_fritz->work_end_time;
					} else {
						$shift_grace = ($get_uniform_sched_time->latest_time_in_allowed) ? $get_uniform_sched_time->latest_time_in_allowed : 0;
						$b_st = $get_uniform_sched_time->work_start_time;
						$b_et = $get_uniform_sched_time->work_end_time;
					}
					
					if($flag_halfday == 1){
						
						// FOR TARDINESS 										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						$b_st_1 = date('Y-m-d', strtotime($new_employee_timein_date)).' '.$b_st;
						$b_st_new = date('Y-m-d H:i:s', strtotime($b_st_1." +{$shift_grace} minutes"));
						if(strtotime($new_time_in) > strtotime($b_st_new)) {
							$calc_tardiness = (strtotime($new_time_in) - strtotime($b_st_new)) / 60;
						} else {
							$calc_tardiness = 0;
						}
						 
						//$tardiness = $tardiness_a + $tardiness_b;
						$tardiness = $calc_tardiness;
						
						// GET END TIME FOR TIME OUT
						$get_end_time = $this->elm->get_end_time($this->company_id,$this->work_schedule_id);
						
						// FOR UNDERTIME 											>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						if($get_end_time != FALSE){
							if(strtotime($now_time) < strtotime($get_end_time)) {
								if($check_break_time_fritz) {
									if($check_break_time_fritz->break_in_min > 0) {
										$time_start_w_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($check_break_time_fritz->work_start_time." +".$shift_grace." minutes"));
										$time_start_wo_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($check_break_time_fritz->work_start_time));
										
										if($is_break_assumed) { // assumed break
											// get the diff of time start without using the threshold and the input time in
											$minutes_for_time_in = (strtotime($new_time_in) - strtotime($time_start_wo_grace)) / 60;
											$time_end_wo_grace = date('Y-m-d', strtotime($new_time_out)).' '.date('H:i:s', strtotime($check_break_time_fritz->work_end_time.' +'.$minutes_for_time_in.' minutes'));
											
											// get the border of start time in
											if(strtotime($new_time_in) > strtotime($time_start_w_grace)) {
												$real_time_in_start = $time_start_w_grace;
												$real_time_out_start = $new_employee_timein_date.' '.date('H:i:s', strtotime($check_break_time_fritz->work_end_time." +".$shift_grace." minutes"));
											} elseif (strtotime($time_start_wo_grace) > strtotime($new_time_in)) {
												$real_time_in_start = $time_start_wo_grace;
												$real_time_out_start = $time_end_wo_grace;
											} else {
												$real_time_in_start = $new_time_in;
												$real_time_out_start = $time_end_wo_grace;
											}
											
											$assumed_breaks = $is_break_assumed->assumed_breaks;
											$h = $assumed_breaks * 60;
											
											if(strtotime($time_start_w_grace) > strtotime($new_time_in)) {
												$time_in_break = date('Y-m-d H:i:s',strtotime($new_time_in. " +{$h} minutes"));
											} else {
												$time_in_break = date('Y-m-d H:i:s',strtotime($time_start_w_grace. " +{$h} minutes"));
											}
											
											$time_out_break = date('Y-m-d H:i:s',strtotime($time_in_break. " +{$check_break_time_fritz->break_in_min} minutes"));
											
											if(strtotime($new_time_out) <= strtotime($time_in_break)) {
												$minutes_of_break = 0;
												#$minutes_of_break = $check_break_time_fritz->break_in_min;
											} else {
												if(strtotime($new_time_out) >= strtotime($time_in_break) && strtotime($new_time_out) <= strtotime($time_out_break)) {
													$diff_breaks_input_and_break = (strtotime($new_time_out) - strtotime($time_in_break)) / 60;
													$minutes_of_break = $diff_breaks_input_and_break;
												} else {
													$minutes_of_break = $check_break_time_fritz->break_in_min;
												}
											}
										} else { // captured
											$time_end_wo_grace = date('Y-m-d', strtotime($new_time_out)).' '.date('H:i:s', strtotime($check_break_time_fritz->work_end_time));
											$time_end_w_grace = date('Y-m-d', strtotime($new_time_out)).' '.date('H:i:s', strtotime($check_break_time_fritz->work_end_time." +".$shift_grace." minutes"));
											// get the border of start time in
											if(strtotime($new_time_in) > strtotime($time_start_w_grace)) {
												$real_time_in_start = $time_start_w_grace;
												$real_time_out_start = $time_end_w_grace;
											} elseif (strtotime($time_start_wo_grace) > strtotime($new_time_in)) {
												$real_time_in_start = $time_start_wo_grace;
												$real_time_out_start = $time_end_wo_grace;
											} else {
												$real_time_in_start = $new_time_in;
													
												// get the diff of time start without using the threshold and the input time in
												$minutes_for_time_out = (strtotime($new_time_in) - strtotime($time_start_wo_grace)) / 60;
												$real_time_out_start = date('Y-m-d H:i:s', strtotime($time_end_wo_grace.' -'.$minutes_for_time_out.' minutes'));
											}
											
											$work_hrs = $check_break_time_fritz->total_work_hours;
											$work_hrs_halfday = ($work_hrs / 2) * 60;
											
											//get the start and end of break time
											$time_in_break = date('Y-m-d H:i:s', strtotime($real_time_in_start.' +'.$work_hrs_halfday.' minutes'));
											$time_out_break = date('Y-m-d H:i:s',strtotime($time_in_break. " +{$check_break_time_fritz->break_in_min} minutes"));
											
											if(strtotime($new_time_out) <= strtotime($time_in_break)) {
												$minutes_of_break = 0;
											} else {
												if(strtotime($new_time_out) >= strtotime($time_in_break) && strtotime($new_time_out) <= strtotime($time_out_break)) {
													$diff_breaks_input_and_break = (strtotime($new_time_out) - strtotime($time_in_break)) / 60;
													$minutes_of_break = $diff_breaks_input_and_break;
												} else {
													$minutes_of_break = $check_break_time_fritz->break_in_min;
												}
											}
										}
									} else {
										$minutes_of_break = 0;
									}
								} else { // disabled break
									$time_start_w_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($get_uniform_sched_time->work_start_time." +".$shift_grace." minutes"));
									$time_start_wo_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($get_uniform_sched_time->work_start_time));
									$time_end_wo_grace = date('Y-m-d', strtotime($new_time_out)).' '.date('H:i:s', strtotime($get_uniform_sched_time->work_end_time));
									
									$time_in_break = "";
									$time_out_break = "";
									
									$minutes_of_break = 0;
									
									// get the border of start time in
									if(strtotime($new_time_in) > strtotime($time_start_w_grace)) {
										$real_time_in_start = $time_start_w_grace;
										$real_time_out_start = $time_end_wo_grace;
									} else {
										$real_time_in_start = $new_time_in;
									
										// get the diff of time start without using the threshold and the input time in
										$minutes_for_time_out = (strtotime($new_time_in) - strtotime($time_start_wo_grace)) / 60;
										$real_time_out_start = date('Y-m-d H:i:s', strtotime($time_end_wo_grace.' +'.$minutes_for_time_out.' minutes'));
									}
								}
								
								// check if the application is covered the break
								$get_end_time_new = $real_time_out_start;
								$now_time_new = $new_employee_timein_date.' '.$now_time;
								
								if(strtotime($new_time_out) <= strtotime($time_in_break)) {
									$undertime = ((strtotime($get_end_time_new) - strtotime($now_time_new)) / 60) - $minutes_of_break;
								} else {
									if(strtotime($new_time_out) >= strtotime($time_in_break) && strtotime($new_time_out) <= strtotime($time_out_break)) {
										// get the diff of input time out and the break in required
										$diff_of_breaks = (strtotime($new_time_out) - strtotime($time_in_break)) / 60;
										$minutes_of_break1 = $minutes_of_break - $diff_of_breaks;
										
										$undertime = ((strtotime($get_end_time_new) - strtotime($now_time_new)) / 60) - $minutes_of_break1;
									} else {
										$undertime = ((strtotime($get_end_time_new) - strtotime($now_time_new)) / 60);
									}
								}
								
								// FOR TOTAL HOURS REQUIRED								>>>>>>>>>>>>>>>>>>>>>>>>>>>>
								$now_time = date('Y-m-d', strtotime($new_time_out)).' '.$now_time;
								$total_hours_required = ((strtotime($now_time) - strtotime($new_time_in)) / 3600) - ($minutes_of_break / 60);
							}
						} else {
							show_error("Payroll set up for break time is empty.");
						}
						
					}elseif($flag_halfday == 2){
						// get tardiness
						if($check_break_time_fritz) {
							if($check_break_time_fritz->break_in_min > 0) {
								$time_start_wo_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($check_break_time_fritz->work_start_time));
								
								if($is_break_assumed) { // assumed break
									$assumed_breaks = $is_break_assumed->assumed_breaks;
									$h = $assumed_breaks * 60;
										
									$time_in_break = date('Y-m-d H:i:s',strtotime($time_start_wo_grace. " +{$h} minutes"));
									$time_out_break = date('Y-m-d H:i:s',strtotime($time_in_break. " +{$check_break_time_fritz->break_in_min} minutes"));
									
									// break for tardiness
									if(strtotime($new_time_in) >= strtotime($time_out_break)) {
										$minus_break = $check_break_time_fritz->break_in_min;
									} else {
										if(strtotime($new_time_in) >= strtotime($time_in_break)) {
											$diff_breaks_input_and_break = (strtotime($new_time_in) - strtotime($time_in_break)) / 60;
											$minus_break = $diff_breaks_input_and_break;
										} else {
											$minus_break = 0;
										}
									}
									
									// break for total hours
									if(strtotime($time_in_break) <= strtotime($new_time_in) && strtotime($time_out_break) >= strtotime($new_time_in)) {
										$diff_breaks_input_and_break = (strtotime($time_out_break) - strtotime($new_time_in)) / 60;
										$minus_break_th = $diff_breaks_input_and_break;
									} elseif (strtotime($new_time_in) > strtotime($time_out_break)) {
										$minus_break_th = 0;
									} else {
										$minus_break_th = $check_break_time_fritz->break_in_min;
									}
								} else { // captured
									$work_hrs = $check_break_time_fritz->total_work_hours;
									$work_hrs_halfday = ($work_hrs / 2) * 60;
									
									//get the start and end of break time
									$time_in_break = date('Y-m-d H:i:s', strtotime($time_start_wo_grace.' +'.$work_hrs_halfday.' minutes'));
									$time_out_break = date('Y-m-d H:i:s',strtotime($time_in_break. " +{$check_break_time_fritz->break_in_min} minutes"));
									
									// break for tardiness
									if(strtotime($new_time_in) >= strtotime($time_out_break)) {
										$minus_break = $check_break_time_fritz->break_in_min;
									} else {
										if(strtotime($new_time_in) >= strtotime($time_in_break)) { 
											$diff_breaks_input_and_break = (strtotime($new_time_in) - strtotime($time_in_break)) / 60;
											$minus_break = $diff_breaks_input_and_break;
										} else {
											$minus_break = 0;
										}
									}
									
									// break for total hours
									if(strtotime($time_in_break) <= strtotime($new_time_in) && strtotime($time_out_break) >= strtotime($new_time_in)) { 
										$diff_breaks_input_and_break = (strtotime($time_out_break) - strtotime($new_time_in)) / 60;
										$minus_break_th = $diff_breaks_input_and_break;
									} elseif (strtotime($new_time_in) > strtotime($time_out_break)) {
										$minus_break_th = 0;
									} else {
										$minus_break_th = $check_break_time_fritz->break_in_min;
									}
								}	
								
								if(strtotime($new_time_in) >= strtotime($time_start_wo_grace)) {
									$tardiness = ((strtotime($new_time_in) - strtotime($time_start_wo_grace)) / 60) - $minus_break;
								} else {
									$tardiness = 0;
								}
							}
						} else {
							$time_start_wo_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($get_uniform_sched_time->work_start_time));
							$tardiness = (strtotime($new_time_in) - strtotime($time_start_wo_grace)) / 60;
							$minus_break = 0;
							$minus_break_th = 0;
						}
						
						// get undertime
						if($check_break_time_fritz) {
							$time_end_wo_grace = date('Y-m-d', strtotime($new_time_out)).' '.date('H:i:s', strtotime($check_break_time_fritz->work_end_time));
								
							if(strtotime($time_end_wo_grace) > strtotime($new_time_out)) {
								$undertime = (strtotime($time_end_wo_grace) - strtotime($new_time_out)) / 60;
							} else {
								$undertime = 0;
							}
						} else {
							$time_end_wo_grace = date('Y-m-d', strtotime($new_time_out)).' '.date('H:i:s', strtotime($get_uniform_sched_time->work_end_time));
							
							if(strtotime($time_end_wo_grace) > strtotime($new_time_out)) {
								$undertime = (strtotime($time_end_wo_grace) - strtotime($new_time_out)) / 60;
							} else {
								$undertime = 0;
							}
							
						}
						
						// FOR TOTAL HOURS										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						if($undertime == 0){
							$u_add_oneday_timein = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$b_st;
							
							$total_hours = 	(strtotime($new_time_out) - ($check_bet_timein > $new_time_in ? strtotime($check_bet_timein): strtotime($new_time_in))) / 3600;
						}else{
							$total_hours = 	(strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
						}
						
						// FOR TOTAL HOURS REQUIRED								>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						$total_hours_required = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600 - ($minus_break_th / 60);
					}
					
					$new_date = date("Y-m-d",strtotime($new_time_in." -1 day"));
					$check_workday = $this->elm->halfday_check_workday($this->work_schedule_id,$this->company_id,$new_date);
					
					$payroll_period = $this->employee->get_payroll_period($this->company_id);
					if($check_workday){
						// minus 1 day
						/*$period_to = $payroll_period->period_to;
						$date_halfday = (strtotime($period_to) == strtotime($new_date)) ? $period_to : NULL ;*/
						$date_halfday = (strtotime($new_date)) ? $new_date : NULL ;
					}else{
						$date_halfday = NULL;
					}
					
					if($absent_min){
						$total_absent_min = ($your_total_hours - $total_hours_required) * 60;
					} else {
						$total_absent_min = 0;
					}
					
					if($tardiness_rule_migrated_v3) {
					    $your_total_hours = $calculated_data["total_hours"];
					    $total_hours_required = $calculated_data["total_hours_required"];
					    $tardiness = $calculated_data["tardiness_min"];
					    $undertime = $calculated_data["undertime_min"];
					    $late_min = $calculated_data["late_min"];
					    $overbreak_min = $calculated_data["overbreak_min"];
					    $total_absent_min = $calculated_data["absent_min"];
					    $current_date_nsd = $calculated_data["current_date_nsd"];
                        $next_date_nsd = $calculated_data["next_date_nsd"];
					} else {
					    $late_min = 0;
					}

					$break_1_start_date_time = ($break_1_start_date_time) ? ($break_1_start_date_time) : NULL;
					$break_1_end_date_time = ($break_1_end_date_time) ? ($break_1_end_date_time) : NULL;
					$break_2_start_date_time = ($break_2_start_date_time) ? ($break_2_start_date_time) : NULL;
					$break_2_end_date_time = ($break_2_start_date_time) ? ($break_2_end_date_time) : NULL;
					
					$break_1_start_date_time = ($break_1_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_start_date_time));
					$break_1_end_date_time = ($break_1_end_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_end_date_time));
					$break_2_start_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_start_date_time));
					$break_2_end_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_end_date_time));
					
					// UPDATE TIME INS
					// note: tardiness min and late min is the same, because there is no overbreak in half day
					$date_insert = array(
						"comp_id"                           => $this->company_id,
						"emp_id"                            => $this->emp_id,
						"work_schedule_id"                  => $this->work_schedule_id,
						"date"                              => date("Y-m-d",strtotime($new_employee_timein_date)),
						"time_in_status"                    => 'pending',
						"corrected"                         => 'Yes',
						"reason"                            => $reason,
						"time_in"                           => $new_time_in,
						"time_out"                          => $new_time_out,
					    "break1_out"                        => $break_1_start_date_time,
					    "break1_in"                         => $break_1_end_date_time,
					    "break2_out"                        => $break_2_start_date_time,
					    "break2_in"                         => $break_2_end_date_time,
						"tardiness_min"                     => $tardiness,
						"undertime_min"                     => $undertime,
						"total_hours"                       => $your_total_hours,
						"total_hours_required"              => $total_hours_required,
						"change_log_date_filed"             => date("Y-m-d H:i:s"),
						"change_log_tardiness_min"          => $tardiness,
						"change_log_undertime_min"          => $undertime,
						"change_log_time_in"                => $new_time_in,
						"change_log_time_out"               => $new_time_out,
					    "change_log_break1_out"            	=> $break_1_start_date_time,
					    "change_log_break1_in"             	=> $break_1_end_date_time,
					    "change_log_break2_out"            	=> $break_2_start_date_time,
					    "change_log_break2_in"             	=> $break_2_end_date_time,
						"change_log_total_hours"            => $your_total_hours,
						"change_log_total_hours_required"   => $total_hours_required,
						"flag_halfday"                      => 1,
						"source"                            => "EP",
					    "late_min"                          => $late_min,
						"overbreak_min"                     => $overbreak_min,
					    "absent_min"                        => $total_absent_min,
					    "change_log_late_min"               => $late_min,
					    "change_log_overbreak_min"          => $overbreak_min,
					    "change_log_absent_min"             => $total_absent_min,
                        "flag_payroll_correction"           => $flag_payroll_correction,
                        "rest_day_r_a"                      => $rest_day_r_a,
					    "flag_rd_include"                   => $flag_rd_include,
					    "holiday_approve"                   => $holiday_approve,
					    "flag_holiday_include"              => $flag_holiday_include,
					    "current_date_nsd"                  => $current_date_nsd,
                        "next_date_nsd"                     => $next_date_nsd
					);
					
					$add_logs = $this->db->insert('employee_time_in', $date_insert);
						
				}else{
					// check break time					
				    if(!$check_break_time_nonsplit){ // ZERO VALUE FOR BREAK TIME
						// update total hours and total hours required rest day
						$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
						
						/* EMPLOYEE WORK SCHEDULE */
						$number_of_breaks_per_day = $this->elmf->check_break_time_flex($this->work_schedule_id,$this->company_id);
			
						// tardiness and undertime value
						$update_tardiness = $this->elm->get_tardiness_val($this->emp_id,$this->company_id,$new_time_in,$this->work_schedule_id,$number_of_breaks_per_day);
						$update_undertime = $this->elm->get_undertime_val($this->emp_id,$this->company_id,$new_time_in,$new_time_out,$this->work_schedule_id,$number_of_breaks_per_day);
			
						// hours worked
						$hours_worked = $this->elm->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
						
						// check tardiness value
						$flag_tu = 0;
			
						$get_total_hours_worked = ($hours_worked / 2) + .5;
						if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
							$update_tardiness = 0;
							$update_undertime = 0;
							$flag_tu = 1;
						}
			
						// required hours worked only
						$new_total_hours = $this->elm->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out,$hours_worked,$this->work_schedule_id);
						
						/** added: fritz - START **/
						$undertime_to_hrs = $undertime / 60;
						$new_fritz_tardiness = $fritz_tardiness / 60;
						
						$fritz_total_hours = $your_total_hours_req - $new_fritz_tardiness - $undertime_to_hrs;
						
						// get total of absent minutes
						if($absent_min){
							$total_absent_min = ($your_total_hours - $fritz_total_hours) * 60;
						} else {
							$total_absent_min = 0;
						}
						
						$current_date_nsd = 0;
                        $next_date_nsd = 0;
						
						if($tardiness_rule_migrated_v3) {
						    $your_total_hours = $calculated_data["total_hours"];
						    $fritz_total_hours = $calculated_data["total_hours_required"];
						    $tardiness = $calculated_data["tardiness_min"];
						    $undertime = $calculated_data["undertime_min"];
						    $late_min = $calculated_data["late_min"];
						    $overbreak_min = $calculated_data["overbreak_min"];
						    $total_absent_min = $calculated_data["absent_min"];
						    $current_date_nsd = $calculated_data["current_date_nsd"];
                            $next_date_nsd = $calculated_data["next_date_nsd"];
						}

						$break_1_start_date_time = ($break_1_start_date_time) ? ($break_1_start_date_time) : NULL;
						$break_1_end_date_time = ($break_1_end_date_time) ? ($break_1_end_date_time) : NULL;
						$break_2_start_date_time = ($break_2_start_date_time) ? ($break_2_start_date_time) : NULL;
						$break_2_end_date_time = ($break_2_start_date_time) ? ($break_2_end_date_time) : NULL;
						
						$break_1_start_date_time = ($break_1_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_start_date_time));
						$break_1_end_date_time = ($break_1_end_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_end_date_time));
						$break_2_start_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_start_date_time));
						$break_2_end_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_end_date_time));
						
						// UPDATE TIME INS
						$date_insert = array(
								"comp_id"                          => $this->company_id,
								"emp_id"                           => $this->emp_id,
								"work_schedule_id"                 => $this->work_schedule_id,
								"date"                             => date("Y-m-d",strtotime($new_employee_timein_date)),
								"time_in_status"                   => 'pending',
								"corrected"                        => 'Yes',
								"reason"                           => $reason,
								"time_in"                          => $new_time_in,
								"time_out"                         => $new_time_out,
						        "break1_out"                       => $break_1_start_date_time,
						        "break1_in"                        => $break_1_end_date_time,
						        "break2_out"                       => $break_2_start_date_time,
						        "break2_in"                        => $break_2_end_date_time,
								"tardiness_min"                    => $tardiness,
								"undertime_min"                    => $undertime,
								"total_hours"                      => $your_total_hours,
								"total_hours_required"             => $fritz_total_hours,
								"flag_tardiness_undertime"         => $flag_tu,
								"change_log_date_filed"            => date("Y-m-d H:i:s"),
					        	"change_log_tardiness_min"         => $tardiness,
								"change_log_undertime_min"         => $undertime,
								"change_log_time_in"               => $new_time_in,
								"change_log_time_out"              => $new_time_out,
							    "change_log_break1_out"            => $break_1_start_date_time,
							    "change_log_break1_in"             => $break_1_end_date_time,
							    "change_log_break2_out"            => $break_2_start_date_time,
							    "change_log_break2_in"             => $break_2_end_date_time,
								"change_log_total_hours"           => $your_total_hours,
								"change_log_total_hours_required"  => $fritz_total_hours,
								"flag_halfday"                     => 1,
								"source"                           => "EP",
								"late_min"                         => $late_min,
								"overbreak_min"                    => $overbreak_min,
							    "absent_min"                       => $total_absent_min,
							    "change_log_late_min"              => $late_min,
							    "change_log_overbreak_min"         => $overbreak_min,
							    "change_log_absent_min"            => $total_absent_min,
							    "flag_payroll_correction"          => $flag_payroll_correction,
							    "rest_day_r_a"                     => $rest_day_r_a,
                                "flag_rd_include"                  => $flag_rd_include,
                                "holiday_approve"                  => $holiday_approve,
                                "flag_holiday_include"             => $flag_holiday_include,
                                "current_date_nsd"                  => $current_date_nsd,
                                "next_date_nsd"                     => $next_date_nsd
						);
						
						$add_logs = $this->db->insert('employee_time_in', $date_insert);
						
					}else if($new_time_in != "" && $new_time_out != "" && $new_lunch_in != "" && $new_lunch_out != ""){ // same2 rani sa else ani.. if naa ky changes, apila lng sd ang else, ang nkalahi ra ani ky pwd wala ang lunch sa else
						// WHOLEDAY								
						$hours_worked = $this->elm->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
						
						/* EMPLOYEE WORK SCHEDULE */
						$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
						
						// required hours worked only
						$new_total_hours = $this->elm->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out,$hours_worked,$this->work_schedule_id);
						
						/** added: fritz - START **/
						$undertime_to_hrs = $undertime / 60;
						$new_fritz_tardiness = $fritz_tardiness / 60;
						$fritz_total_hours = $your_total_hours_req;
						
						// get total of absent minutes
						if($absent_min){
							$total_absent_min = ($your_total_hours - $fritz_total_hours) * 60;
						} else {
							$total_absent_min = 0;
						}
						
						$current_date_nsd = 0;
                        $next_date_nsd = 0;
						
						if($tardiness_rule_migrated_v3) {
						    $your_total_hours = $calculated_data["total_hours"];
						    $fritz_total_hours = $calculated_data["total_hours_required"];
						    $fritz_tardiness = $calculated_data["tardiness_min"];
						    $undertime = $calculated_data["undertime_min"];
						    $late_min = $calculated_data["late_min"];
						    $overbreak_min = $calculated_data["overbreak_min"];
						    $total_absent_min = $calculated_data["absent_min"];
						    $current_date_nsd = $calculated_data["current_date_nsd"];
                            $next_date_nsd = $calculated_data["next_date_nsd"];
						}

						$break_1_start_date_time = ($break_1_start_date_time) ? ($break_1_start_date_time) : NULL;
						$break_1_end_date_time = ($break_1_end_date_time) ? ($break_1_end_date_time) : NULL;
						$break_2_start_date_time = ($break_2_start_date_time) ? ($break_2_start_date_time) : NULL;
						$break_2_end_date_time = ($break_2_start_date_time) ? ($break_2_end_date_time) : NULL;
						
						$break_1_start_date_time = ($break_1_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_start_date_time));
						$break_1_end_date_time = ($break_1_end_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_end_date_time));
						$break_2_start_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_start_date_time));
						$break_2_end_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_end_date_time));
						
						// UPDATE TIME INS
						$date_insert = array(
								"comp_id"                         => $this->company_id,
								"emp_id"                          => $this->emp_id,
								"work_schedule_id"                => $this->work_schedule_id,
								"date"                            => date("Y-m-d",strtotime($new_employee_timein_date)),
								"time_in_status"                  => 'pending',
								"corrected"                       => 'Yes',
								"reason"                          => $reason,
								"time_in"                         => $new_time_in,
								"lunch_out"                       => $new_lunch_out,
								"lunch_in"                        => $new_lunch_in,
								"time_out"                        => $new_time_out,
							    "break1_out"                      => $break_1_start_date_time,
							    "break1_in"                       => $break_1_end_date_time,
							    "break2_out"                      => $break_2_start_date_time,
							    "break2_in"                       => $break_2_end_date_time,
								"tardiness_min"                   => $fritz_tardiness,
								"undertime_min"                   => $undertime,
								"total_hours"                     => $your_total_hours,
								"total_hours_required"            => $fritz_total_hours,
								"change_log_date_filed"           => date("Y-m-d H:i:s"),
								"change_log_tardiness_min"        => $fritz_tardiness,
								"change_log_undertime_min"        => $undertime,
								"change_log_time_in"              => $new_time_in,
								"change_log_lunch_out"            => $new_lunch_out,
								"change_log_lunch_in"             => $new_lunch_in,
								"change_log_time_out"             => $new_time_out,
							    "change_log_break1_out"           => $break_1_start_date_time,
							    "change_log_break1_in"            => $break_1_end_date_time,
							    "change_log_break2_out"           => $break_2_start_date_time,
							    "change_log_break2_in"            => $break_2_end_date_time,
								"change_log_total_hours"          => $your_total_hours,
								"change_log_total_hours_required" => $fritz_total_hours,
								"source"                          => "EP",
								"late_min"                        => $late_min,
							    "overbreak_min"                   => $overbreak_min,
							    "change_log_late_min"             => $late_min,
							    "change_log_overbreak_min"        => $overbreak_min,
                                "flag_payroll_correction"         => $flag_payroll_correction,
                                "rest_day_r_a"                    => $rest_day_r_a,
                                "flag_rd_include"                 => $flag_rd_include,
                                "holiday_approve"                 => $holiday_approve,
                                "flag_holiday_include"            => $flag_holiday_include,
                                "current_date_nsd"                => $current_date_nsd,
                                "next_date_nsd"                   => $next_date_nsd
						);
						
						$add_logs = $this->db->insert('employee_time_in', $date_insert);
					} else { // g,add ra ni nako ang else ky wala else gud, wala butangi ni feel if wala sd ni email rai ma send pro wala na save.. and ang email sad na ma send ky guba wrong data e.g jan 01 1970 
					    // WHOLEDAY
					    $hours_worked = $this->elm->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
					    
					    /* EMPLOYEE WORK SCHEDULE */
					    $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
					    
					    $new_lunch_out = ($new_lunch_out == "") ? NULL : date("Y-m-d H:i:s", strtotime($new_lunch_out));
					    $new_lunch_in = ($new_lunch_in == "") ? NULL : date("Y-m-d H:i:s", strtotime($new_lunch_in));
					    
					    // required hours worked only
					    $new_total_hours = $this->elm->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out,$hours_worked,$this->work_schedule_id);
					    
					    /** added: fritz - START **/
					    $undertime_to_hrs = $undertime / 60;
					    $new_fritz_tardiness = $fritz_tardiness / 60;
					    $fritz_total_hours = $your_total_hours_req;
					    
					    // get total of absent minutes
					    if($absent_min){
					        $total_absent_min = ($your_total_hours - $fritz_total_hours) * 60;
					    } else {
					        $total_absent_min = 0;
					    }
					    
					    $current_date_nsd = 0;
                        $next_date_nsd = 0;
					    
					    if($tardiness_rule_migrated_v3) {
					        $your_total_hours = $calculated_data["total_hours"];
					        $fritz_total_hours = $calculated_data["total_hours_required"];
					        $fritz_tardiness = $calculated_data["tardiness_min"];
					        $undertime = $calculated_data["undertime_min"];
					        $late_min = $calculated_data["late_min"];
					        $overbreak_min = $calculated_data["overbreak_min"];
					        $total_absent_min = $calculated_data["absent_min"];
					        $current_date_nsd = $calculated_data["current_date_nsd"];
                            $next_date_nsd = $calculated_data["next_date_nsd"];
					    }

					    $break_1_start_date_time = ($break_1_start_date_time) ? ($break_1_start_date_time) : NULL;
						$break_1_end_date_time = ($break_1_end_date_time) ? ($break_1_end_date_time) : NULL;
						$break_2_start_date_time = ($break_2_start_date_time) ? ($break_2_start_date_time) : NULL;
						$break_2_end_date_time = ($break_2_start_date_time) ? ($break_2_end_date_time) : NULL;
					    
					    $break_1_start_date_time = ($break_1_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_start_date_time));
					    $break_1_end_date_time = ($break_1_end_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_end_date_time));
					    $break_2_start_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_start_date_time));
					    $break_2_end_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_end_date_time));
					    
					    // UPDATE TIME INS
					    $date_insert = array(
					        "comp_id"                         => $this->company_id,
					        "emp_id"                          => $this->emp_id,
					        "work_schedule_id"                => $this->work_schedule_id,
					        "date"                            => date("Y-m-d",strtotime($new_employee_timein_date)),
					        "time_in_status"                  => 'pending',
					        "corrected"                       => 'Yes',
					        "reason"                          => $reason,
					        "time_in"                         => $new_time_in,
					        "lunch_out"                       => $new_lunch_out,
					        "lunch_in"                        => $new_lunch_in,
					        "time_out"                        => $new_time_out,
					        "break1_out"                      => $break_1_start_date_time,
					        "break1_in"                       => $break_1_end_date_time,
					        "break2_out"                      => $break_2_start_date_time,
					        "break2_in"                       => $break_2_end_date_time,
					        "tardiness_min"                   => $fritz_tardiness,
					        "undertime_min"                   => $undertime,
					        "total_hours"                     => $your_total_hours,
					        "total_hours_required"            => $fritz_total_hours,
					        "change_log_date_filed"           => date("Y-m-d H:i:s"),
					        "change_log_tardiness_min"        => $fritz_tardiness,
					        "change_log_undertime_min"        => $undertime,
					        "change_log_time_in"              => $new_time_in,
					        "change_log_lunch_out"            => $new_lunch_out,
					        "change_log_lunch_in"             => $new_lunch_in,
					        "change_log_time_out"             => $new_time_out,
					        "change_log_break1_out"           => $break_1_start_date_time,
					        "change_log_break1_in"            => $break_1_end_date_time,
					        "change_log_break2_out"           => $break_2_start_date_time,
					        "change_log_break2_in"            => $break_2_end_date_time,
					        "change_log_total_hours"          => $your_total_hours,
					        "change_log_total_hours_required" => $fritz_total_hours,
					        "source"                          => "EP",
					        "late_min"                        => $late_min,
					        "overbreak_min"                   => $overbreak_min,
					        "change_log_late_min"             => $late_min,
					        "change_log_overbreak_min"        => $overbreak_min,
                            "flag_payroll_correction"         => $flag_payroll_correction,
                            "rest_day_r_a"                    => $rest_day_r_a,
                            "flag_rd_include"                 => $flag_rd_include,
                            "holiday_approve"                 => $holiday_approve,
                            "flag_holiday_include"            => $flag_holiday_include,
                            "current_date_nsd"                => $current_date_nsd,
                            "next_date_nsd"                   => $next_date_nsd
					    );
					    $add_logs = $this->db->insert('employee_time_in', $date_insert);
					}
				}
					
			}else if($check_work_type == "Flexible Hours"){
				if($check_rest_day || $check_holiday || $existing_logs == true){
					
					$flag_regular_or_excess = "regular";
					$new_work_schedule_id = $this->work_schedule_id;
					if($existing_logs == true) {
						$flag_regular_or_excess = "excess";
						$new_work_schedule_id = "-2";
					}
					
					$tardiness = 0;
					$undertime = 0;

					// update total hours and total hours required rest day
                    $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                    
                    if ($this->work_schedule_id == "-1") {
                            $flag_rd_include = "no";
                            $rest_day_r_a = "yes";
                        }
						
					// UPDATE TIME INS
					$date_insert = array(
					    "comp_id"                           => $this->company_id,
					    "emp_id"                            => $this->emp_id,
					    "work_schedule_id"                  => $new_work_schedule_id,
					    "date"                              => date("Y-m-d",strtotime($new_employee_timein_date)),
					    "time_in_status"                    => 'pending',
					    "corrected"                         => 'Yes',
					    "reason"                            => $reason,
					    "time_in"                           => $new_time_in,
					    "time_out"                          => $new_time_out,
					    "tardiness_min"                     => $tardiness,
					    "undertime_min"                     => $undertime,
					    "total_hours"                       => $get_total_hours,
					    "total_hours_required"              => $get_total_hours,
					    "change_log_date_filed"             => date("Y-m-d H:i:s"),
					    "change_log_tardiness_min"          => $tardiness,
					    "change_log_undertime_min"          => $undertime,
					    "change_log_time_in"                => $new_time_in,
					    "change_log_time_out"               => $new_time_out,
					    "change_log_total_hours"            => $get_total_hours,
					    "change_log_total_hours_required"   => $get_total_hours,
					    "source"                            => "EP",
					    "late_min"                          => 0,
					    "overbreak_min"                     => 0,
					    "change_log_late_min"               => 0,
					    "change_log_overbreak_min"          => 0,
					    "flag_regular_or_excess"            => $flag_regular_or_excess,
					    "flag_payroll_correction"           => $flag_payroll_correction,
                        "rest_day_r_a"                      => $rest_day_r_a,
                        "flag_rd_include"                   => $flag_rd_include,
                        "holiday_approve"                   => $holiday_approve,
                        "flag_holiday_include"              => $flag_holiday_include,
					);
					
					$add_logs = $this->db->insert('employee_time_in', $date_insert);
				
				}else if($new_lunch_out == NULL && $new_lunch_in == NULL && $flaghalfday == 1){
					$check_latest_timein_allowed = $this->elmf->check_lastest_timein_allowed($this->work_schedule_id,$this->company_id);
					
					if($check_latest_timein_allowed) {
						if($check_break_time_fritz) {
							if($check_break_time_fritz->duration_of_lunch_break_per_day > 0) {
								$time_start_wo_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($check_break_time_fritz->latest_time_in_allowed));
								$time_end_wo_grace = date('Y-m-d H:i:s', strtotime($time_start_wo_grace." +".$check_break_time_fritz->total_hours_for_the_day." hours"));
									
								if($is_break_assumed) { // assumed break
									$real_time_in_start = $time_start_wo_grace;
									$real_time_out_start = $time_end_wo_grace;
						
									$assumed_breaks = $is_break_assumed->assumed_breaks;
									$h = $assumed_breaks * 60;
						
									$time_in_break = date('Y-m-d H:i:s',strtotime($time_start_wo_grace. " +{$h} minutes"));
									$time_out_break = date('Y-m-d H:i:s',strtotime($time_in_break. " +{$check_break_time_fritz->duration_of_lunch_break_per_day} minutes"));
									
									if(strtotime($new_time_in) <= strtotime($time_out_break)) {
										$minutes_of_break = $check_break_time_fritz->duration_of_lunch_break_per_day;
									} else {
										if(strtotime($new_time_in) <= strtotime($time_in_break)) {
											$diff_breaks_input_and_break = (strtotime($time_in_break) - strtotime($new_time_in)) / 60;
											$minutes_of_break = $diff_breaks_input_and_break;
										} else {
											$minutes_of_break = 0;
										}
									}
								} else { // captured
									$real_time_in_start = $time_start_wo_grace;
									$real_time_out_start = $time_end_wo_grace;
						
									$work_hrs = $check_break_time_fritz->total_hours_for_the_day - ($check_break_time_fritz->duration_of_lunch_break_per_day / 60);
									$work_hrs_halfday = ($work_hrs / 2) * 60;
						
									//get the start and end of break time
									$time_in_break = date('Y-m-d H:i:s', strtotime($time_start_wo_grace.' +'.$work_hrs_halfday.' minutes'));
									$time_out_break = date('Y-m-d H:i:s',strtotime($time_in_break. " +{$check_break_time_fritz->duration_of_lunch_break_per_day} minutes"));
										
									if(strtotime($new_time_in) >= strtotime($time_out_break)) {
										$minutes_of_break = $check_break_time_fritz->duration_of_lunch_break_per_day;
									} else {
										if(strtotime($new_time_in) >= strtotime($time_in_break)) {
											$diff_breaks_input_and_break = (strtotime($new_time_in) - strtotime($time_in_break)) / 60;
											$minutes_of_break = $diff_breaks_input_and_break;
										} else {
											$minutes_of_break = 0;
										}
									}
								}
							}
						} else { // disabled break
							$time_start_wo_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($check_latest_timein_allowed->latest_time_in_allowed));
							$time_end_wo_grace = date('Y-m-d H:i:s', strtotime($time_start_wo_grace." +".$check_latest_timein_allowed->total_hours_for_the_day." hours"));
						
							$minutes_of_break = 0;
						
							$real_time_in_start = $time_start_wo_grace;
							$real_time_out_start = $time_end_wo_grace;
						
							$time_in_break = "";
							$time_out_break = "";
						}
					} else {
						$flexi_no_latest = $this->elmf->get_hoursworked($this->work_schedule_id,$this->company_id);
						
						if($flexi_no_latest) {
							if($flexi_no_latest->duration_of_lunch_break_per_day > 0) {
								$time_start_wo_grace = $new_time_in;
								$time_end_wo_grace = date('Y-m-d H:i:s', strtotime($time_start_wo_grace." +".$flexi_no_latest->total_hours_for_the_day." hours"));
							
								if($is_break_assumed) { // assumed break
									$real_time_in_start = $time_start_wo_grace;
									$real_time_out_start = $time_end_wo_grace;
							
									$assumed_breaks = $is_break_assumed->assumed_breaks;
									$h = $assumed_breaks * 60;
							
									$time_in_break = date('Y-m-d H:i:s',strtotime($time_start_wo_grace. " +{$h} minutes"));
									$time_out_break = date('Y-m-d H:i:s',strtotime($time_in_break. " +{$flexi_no_latest->duration_of_lunch_break_per_day} minutes"));
									
									if(strtotime($new_time_in) <= strtotime($time_out_break)) {
										$minutes_of_break = $flexi_no_latest->duration_of_lunch_break_per_day;
									} else {
										if(strtotime($new_time_in) <= strtotime($time_in_break)) {
											$diff_breaks_input_and_break = (strtotime($time_in_break) - strtotime($new_time_in)) / 60;
											$minutes_of_break = $diff_breaks_input_and_break;
										} else {
											$minutes_of_break = 0;
										}
									}
								} else { // captured
									$real_time_in_start = $time_start_wo_grace;
									$real_time_out_start = $time_end_wo_grace;
							
									$work_hrs = $flexi_no_latest->total_hours_for_the_day - ($flexi_no_latest->duration_of_lunch_break_per_day / 60);
									$work_hrs_halfday = ($work_hrs / 2) * 60;
							
									//get the start and end of break time
									$time_in_break = date('Y-m-d H:i:s', strtotime($time_start_wo_grace.' +'.$work_hrs_halfday.' minutes'));
									$time_out_break = date('Y-m-d H:i:s',strtotime($time_in_break. " +{$flexi_no_latest->duration_of_lunch_break_per_day} minutes"));
							
									if(strtotime($new_time_in) <= strtotime($time_out_break)) {
										$minutes_of_break = $flexi_no_latest->duration_of_lunch_break_per_day;
									} else {
										if(strtotime($new_time_in) <= strtotime($time_in_break)) {
											$diff_breaks_input_and_break = (strtotime($time_in_break) - strtotime($new_time_in)) / 60;
											$minutes_of_break = $diff_breaks_input_and_break;
										} else {
											$minutes_of_break = 0;
										}
									}
								}
							} else {
								$time_start_wo_grace = $new_time_in;
								$time_end_wo_grace = date('Y-m-d H:i:s', strtotime($time_start_wo_grace." +".$flexi_no_latest->total_hours_for_the_day." hours"));
								
								$minutes_of_break = 0;
								
								$real_time_in_start = $time_start_wo_grace;
								$real_time_out_start = $time_end_wo_grace;
								
								$time_in_break = "";
								$time_out_break = "";
							}
						}
						
					}
					
					
					if(strtotime($new_time_in) < strtotime($time_in_break)) {
						//get the diff the time in input and the start break time
						$diff_timein_and_break = (strtotime($time_in_break) - strtotime($real_time_in_start));
						$diff_timein_and_break = $diff_timein_and_break / 2;
						$boundary_of_halfday = date('Y-m-d H:i:s', strtotime($real_time_in_start.' +'.$diff_timein_and_break.' seconds'));
							
						if(strtotime($boundary_of_halfday) <= strtotime($new_time_in)) {
							$flag_halfday = 2; // second half
						} else {
							$flag_halfday = 1; // first half
						}
					} else {
						$flag_halfday = 2; // second half
					}
					
					// check if the application is covered the break
					$get_end_time_new = $real_time_out_start;
					$now_time_new = $new_time_out;
						
					if(strtotime($new_time_out) <= strtotime($time_in_break)) {
						$undertime = ((strtotime($get_end_time_new) - strtotime($now_time_new)) / 60) - $minutes_of_break;
					} else {
						if(strtotime($new_time_out) >= strtotime($time_in_break) && strtotime($new_time_out) <= strtotime($time_out_break)) {
							// get the diff of input time out and the break in required
							$diff_of_breaks = (strtotime($new_time_out) - strtotime($time_in_break)) / 60;
							$minutes_of_break1 = $minutes_of_break - $diff_of_breaks;
							$undertime = ((strtotime($get_end_time_new) - strtotime($now_time_new)) / 60) - $minutes_of_break1;
						} else {
							if(strtotime($get_end_time_new) > strtotime($now_time_new)) {
								$undertime = ((strtotime($get_end_time_new) - strtotime($now_time_new)) / 60);
							} else {
								$undertime = 0;
							}
						}
					}
					
					if(!$check_latest_timein_allowed) {
						$tardiness = 0;
					} else {
						if($flag_halfday == 1) {
							if(strtotime($new_time_in) > strtotime($real_time_in_start)) {
								$calc_tardiness = (strtotime($new_time_in) - strtotime($real_time_in_start)) / 60;
							} else {
								$calc_tardiness = 0;
							}
							
							$tardiness = $calc_tardiness;
							
							if(strtotime($new_time_out) <= strtotime($time_in_break)) {
								$minutes_of_break_th = 0;
							} else {
								if(strtotime($new_time_out) >= strtotime($time_in_break) && strtotime($new_time_out) <= strtotime($time_out_break)) {
									$diff_breaks_input_and_break = (strtotime($new_time_out) - strtotime($time_in_break)) / 60;
									$minutes_of_break_th = $diff_breaks_input_and_break;
								} else {
									$minutes_of_break_th = $check_break_time_fritz->duration_of_lunch_break_per_day;
								}
							}
						} elseif ($flag_halfday == 2) {
							if($minutes_of_break > 0) {
								if(strtotime($new_time_in) >= strtotime($real_time_in_start)) {
									$tardiness = ((strtotime($new_time_in) - strtotime($real_time_in_start)) / 60) - $minutes_of_break;
								} else {
									$tardiness = 0;
								}
							} else {
								$tardiness = (strtotime($new_time_in) - strtotime($real_time_in_start)) / 60;
							}
							
							// break for total hours
							if(strtotime($time_in_break) <= strtotime($new_time_in) && strtotime($time_out_break) >= strtotime($new_time_in)) {
								$diff_breaks_input_and_break = (strtotime($time_out_break) - strtotime($new_time_in)) / 60;
								$minutes_of_break_th = $diff_breaks_input_and_break;
							} elseif (strtotime($new_time_in) > strtotime($time_out_break)) {
								$minutes_of_break_th = 0;
							} else {
								$minutes_of_break_th = $check_break_time_fritz->duration_of_lunch_break_per_day;
							}
						}
					}
					
					$total_hours_required = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600 - ($minutes_of_break_th / 60);
					
					// UPDATE TIME INS
					$date_insert = array(
					    "comp_id"                           => $this->company_id,
					    "emp_id"                            => $this->emp_id,
					    "work_schedule_id"                  => $this->work_schedule_id,
					    "date"                              => date("Y-m-d",strtotime($new_employee_timein_date)),
					    "time_in_status"                    => 'pending',
					    "corrected"                         => 'Yes',
					    "reason"                            => $reason,
					    "time_in"                           => $new_time_in,
					    "time_out"                          => $new_time_out,
					    "tardiness_min"                     => $tardiness,
					    "undertime_min"                     => $undertime,
					    "total_hours"                       => $your_total_hours,
					    "total_hours_required"              => $total_hours_required,
					    "change_log_date_filed"             => date("Y-m-d H:i:s"),
					    "change_log_tardiness_min"          => $tardiness,
					    "change_log_undertime_min"          => $undertime,
					    "change_log_time_in"                => $new_time_in,
					    "change_log_time_out"               => $new_time_out,
					    "change_log_total_hours"            => $your_total_hours,
					    "change_log_total_hours_required"   => $total_hours_required,
					    "flag_halfday"                      => 1,
					    #"date_halfday"                     => $date_halfday,
					    "source"                            => "EP",
					    "late_min"                          => $tardiness,
					    "overbreak_min"                     => 0,
					    "change_log_late_min"               => $tardiness,
					    "change_log_overbreak_min"          => 0,
                        "flag_payroll_correction"           => $flag_payroll_correction,
                        "rest_day_r_a"                      => $rest_day_r_a,
                        "flag_rd_include"                   => $flag_rd_include,
                        "holiday_approve"                   => $holiday_approve,
                        "flag_holiday_include"              => $flag_holiday_include,
					);
					
					$add_logs = $this->db->insert('employee_time_in', $date_insert);
						
				}else{
					/* WHOLE DAY */
					$get_hoursworked = $this->elmf->get_hoursworked($this->work_schedule_id,$this->company_id)->total_hours_for_the_day;
					
					// check workday settings
					$workday_settings_start_time = date("H:i:s",strtotime($new_time_in));
					$workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
					$check_latest_timein_allowed = $this->elmf->check_lastest_timein_allowed($this->work_schedule_id,$this->company_id);
					
					$workday_settings_end_time_fritz = date("Y-m-d", strtotime($new_time_in)).' '.date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
					
					if(!$check_latest_timein_allowed){
						$number_of_breaks_per_day = $this->elmf->check_break_time_flex($this->work_schedule_id,$this->company_id);
						if($number_of_breaks_per_day == 0){
			
							// update total hours and total hours required rest day
							$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
			
							// update total tardiness
							$update_tardiness = 0;
			
							// update undertime
							$update_undertime = 0;
							if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
								$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
							}
							if(strtotime(date("Y-m-d H:i:s",strtotime($new_time_out))) < strtotime(date("Y-m-d H:i:s",strtotime($workday_settings_end_time_fritz)))){
								$new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
								$update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
							}
							
							// check tardiness value
							$flag_tu = 0;
			
							$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
							$get_total_hours_worked = ($hours_worked / 2) + .5;
							if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
								$update_tardiness = 0;
								$update_undertime = 0;
								$flag_tu = 1;
							}
			
							// required hours worked only
							$new_total_hours = $this->elmf->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_time_out,$hours_worked,$this->work_schedule_id,$number_of_breaks_per_day);
			
							// if value is less then 0 then set value to 0
							if($update_tardiness < 0) $update_tardiness = 0;
							if($update_undertime < 0) $update_undertime = 0;
							if($new_total_hours < 0) $new_total_hours = 0;
							if($get_total_hours < 0) $get_total_hours = 0;
			
							// UPDATE TIME INS
							$date_insert = array(
							    "comp_id"                           => $this->company_id,
							    "emp_id"                            => $this->emp_id,
							    "work_schedule_id"                  => $this->work_schedule_id,
							    "date"                              => date("Y-m-d",strtotime($new_employee_timein_date)),
							    "time_in_status"                    => 'pending',
							    "corrected"                         => 'Yes',
							    "reason"                            => $reason,
							    "time_in"                           => $new_time_in,
							    "time_out"                          => $new_time_out,
							    "tardiness_min"                     => $update_tardiness,
							    "undertime_min"                     => $update_undertime,
							    "total_hours"                       => $your_total_hours,
							    "total_hours_required"              => $get_total_hours,
							    "change_log_date_filed"             => date("Y-m-d H:i:s"),
							    "change_log_tardiness_min"          => $update_tardiness,
							    "change_log_undertime_min"          => $update_undertime,
							    "change_log_time_in"                => $new_time_in,
							    "change_log_time_out"               => $new_time_out,
							    "change_log_total_hours"            => $your_total_hours,
							    "change_log_total_hours_required"   => $get_total_hours,
							    "source"                            => "EP",
							    "late_min"                          => $late_min,
							    "overbreak_min"                     => $overbreak_min,
							    "change_log_late_min"               => $late_min,
							    "change_log_overbreak_min"          => $overbreak_min,
                                "flag_payroll_correction"           => $flag_payroll_correction,
                                "rest_day_r_a"                      => $rest_day_r_a,
                                "flag_rd_include"                   => $flag_rd_include,
                                "holiday_approve"                   => $holiday_approve,
                                "flag_holiday_include"              => $flag_holiday_include,
							);
								
							$add_logs = $this->db->insert('employee_time_in', $date_insert);
								
						}else{
							// update tardiness for timein
							$tardiness_timein = 0;
			
							// update tardiness for break time
							$update_tardiness_break_time = 0;
							$duration_of_lunch_break_per_day = $this->elmf->duration_of_lunch_break_per_day($this->emp_id, $this->company_id, $this->work_schedule_id);
							$tardiness_a = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
							if($duration_of_lunch_break_per_day < $tardiness_a){
								$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
							}
			
							// update total tardiness
							$tardiness_global = ($tardiness_settings) ? $tardiness_settings : 0;
							$update_tardiness = $tardiness_timein + $update_tardiness_break_time - $tardiness_global;
			
							// update undertime
							$update_undertime = 0;
							if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
								$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
							}
							if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
								$new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
								$update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
							}
			
							// update total hours
							$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
							$update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - ($duration_of_lunch_break_per_day / 60);
			
							// check tardiness value
							$get_total_hours_worked = ($hours_worked / 2) + .5;
							if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
								$update_tardiness = 0;
								$update_undertime = 0;
								$flag_tu = 1;
							}
			
							// update total hours required
							$update_total_hours_required = ((strtotime($new_time_out) - strtotime($new_time_in)) / 3600) - ($update_tardiness / 60) - ($duration_of_lunch_break_per_day / 60);
			
							// if value is less then 0 then set value to 0
							if($update_tardiness < 0) $update_tardiness = 0;
							if($update_undertime < 0) $update_undertime = 0;
							if($update_total_hours < 0) $update_total_hours = 0;
							if($update_total_hours_required < 0) $update_total_hours_required = 0;
							
							// late with tardiness settings
							if($late_min <= 0) {
								$new_late_min = $late_min - 0;
							} else {
								$new_late_min = $late_min - $tardiness_global;
							}
			
							// UPDATE TIME INS
							$date_insert = array(
							    "comp_id"                          => $this->company_id,
							    "emp_id"                           => $this->emp_id,
							    "work_schedule_id"                 => $this->work_schedule_id,
							    "date"                             => date("Y-m-d",strtotime($new_employee_timein_date)),
							    "time_in_status"                   => 'pending',
							    "corrected"                        => 'Yes',
							    "reason"                           => $reason,
							    "time_in"                          => $new_time_in,
							    "lunch_out"                        => $new_lunch_out,
							    "lunch_in"                         => $new_lunch_in,
							    "time_out"                         => $new_time_out,
							    "tardiness_min"                    => $update_tardiness,
							    "undertime_min"                    => $update_undertime,
							    "total_hours"                      => $your_total_hours,
							    "total_hours_required"             => $update_total_hours_required,
							    "change_log_date_filed"            => date("Y-m-d H:i:s"),
							    "change_log_tardiness_min"         => $update_tardiness,
							    "change_log_undertime_min"         => $update_undertime,
							    "change_log_time_in"               => $new_time_in,
							    "change_log_lunch_out"             => $new_lunch_out,
							    "change_log_lunch_in"              => $new_lunch_in,
							    "change_log_time_out"              => $new_time_out,
							    "change_log_total_hours"           => $your_total_hours,
							    "change_log_total_hours_required"  => $update_total_hours_required,
							    "source"                           => "EP",
							    "late_min"                         => $new_late_min,
							    "overbreak_min"                    => $overbreak_min,
							    "change_log_late_min"              => $new_late_min,
							    "change_log_overbreak_min"         => $overbreak_min,
                                "flag_payroll_correction"          => $flag_payroll_correction,
                                "rest_day_r_a"                     => $rest_day_r_a,
                                "flag_rd_include"                  => $flag_rd_include,
                                "holiday_approve"                  => $holiday_approve,
                                "flag_holiday_include"             => $flag_holiday_include,
							);
							
							$add_logs = $this->db->insert('employee_time_in', $date_insert);
						}
					} else {
						$number_of_breaks_per_day = $this->elmf->check_break_time_flex($this->work_schedule_id,$this->company_id);
						
						if($number_of_breaks_per_day == 0){
							// update total hours and total hours required rest day
							$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
			
							// update tardiness for timein
							$tardiness_timein = 0;
							if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
								if(date("A",strtotime($new_time_in)) == "AM"){
									// add one day for time in log
									$new_start_timein = date("Y-m-d",strtotime($new_time_in." -1 day"));
									$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
			
									if(strtotime($new_start_timein) < strtotime($new_time_in)){
										$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;
									}
								}
							}else{
								$new_start_timein = date("Y-m-d",strtotime($new_time_in));
								$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
									
								if(strtotime($new_start_timein) < strtotime($new_time_in)){
									$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;
								}
							}
								
							// update total tardiness
							$tardiness_global = ($tardiness_settings) ? $tardiness_settings : 0;
							$update_tardiness = $tardiness_timein - $tardiness_global;
			
							// update undertime
							$update_undertime = 0;
							if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
								$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
							}
							if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
								$new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
								$update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
							}
			
							// check tardiness value
							$flag_tu = 0;
			
							$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
							$get_total_hours_worked = ($hours_worked / 2) + .5;
							if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
								$update_tardiness = 0;
								$update_undertime = 0;
								$flag_tu = 1;
							}
			
							// required hours worked only
							$new_total_hours = $this->elmf->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_time_out,$hours_worked,$this->work_schedule_id,$number_of_breaks_per_day);
			
							// if value is less than 0 then set value to 0
							if($update_tardiness < 0) $update_tardiness = 0;
							if($update_undertime < 0) $update_undertime = 0;
							if($new_total_hours < 0) $new_total_hours = 0;
							if($get_total_hours < 0) $get_total_hours = 0;
							
							// late with tardiness settings
							if($late_min <= 0) {
								$new_late_min = $late_min - 0;
							} else {
								$new_late_min = $late_min - $tardiness_global;
							}
							
							// UPDATE TIME INS
							$date_insert = array(
								"comp_id"							=> $this->company_id,
								"emp_id"                          	=> $this->emp_id,
								"work_schedule_id"					=> $this->work_schedule_id,
								"date"								=> date("Y-m-d",strtotime($new_employee_timein_date)),
								"time_in_status"					=> 'pending',
								"corrected"							=> 'Yes',
								"reason"							=> $reason,
								"time_in"							=> $new_time_in,
								"time_out"							=> $new_time_out,
								"tardiness_min"						=> $update_tardiness,
								"undertime_min"						=> $update_undertime,
								"total_hours"						=> $your_total_hours,
								"total_hours_required"				=> $get_total_hours,
								"change_log_date_filed"				=> date("Y-m-d H:i:s"),
								"change_log_tardiness_min"			=> $update_tardiness,
								"change_log_undertime_min"			=> $update_undertime,
								"change_log_time_in"				=> $new_time_in,
								"change_log_time_out"				=> $new_time_out,
								"change_log_total_hours"			=> $your_total_hours,
								"change_log_total_hours_required"   => $get_total_hours,
								"source"							=> "EP",
								"late_min"							=> $new_late_min,
							    "overbreak_min"						=> $overbreak_min,
							    "change_log_late_min"				=> $new_late_min,
							    "change_log_overbreak_min"			=> $overbreak_min,
                                "flag_payroll_correction"          	=> $flag_payroll_correction,
                                "rest_day_r_a"                      => $rest_day_r_a,
                                "flag_rd_include"                   => $flag_rd_include,
                                "holiday_approve"                   => $holiday_approve,
                                "flag_holiday_include"              => $flag_holiday_include,
							);
							
							$add_logs = $this->db->insert('employee_time_in', $date_insert);
							
						}else{
							// update tardiness for timein
							$tardiness_timein = 0;
							if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
								if(date("A",strtotime($new_time_in)) == "AM"){
									// add one day for time in log
									$new_start_timein = date("Y-m-d",strtotime($new_time_in." -1 day"));
									$new_start_timein = $new_start_timein." ".$check_latest_timein_allowed->latest_time_in_allowed;
			
									if(strtotime($new_start_timein) < strtotime($new_time_in)){
										$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;
									}
								}
							}else{
								$new_start_timein = date("Y-m-d",strtotime($new_time_in));
								$new_start_timein = $new_start_timein." ".$check_latest_timein_allowed->latest_time_in_allowed;
								
								if(strtotime($new_start_timein) < strtotime($new_time_in)){
									$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;
								}
							}
							// update tardiness for break time
							$update_tardiness_break_time = 0;
							$duration_of_lunch_break_per_day = $this->elmf->duration_of_lunch_break_per_day($this->emp_id, $this->company_id, $this->work_schedule_id);
							$tardiness_a = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
							if($duration_of_lunch_break_per_day < $tardiness_a){
								$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
							}
			
							// update total tardiness
							$tardiness_global = ($tardiness_settings) ? $tardiness_settings : 0;
							$update_tardiness = $tardiness_timein + $update_tardiness_break_time - $tardiness_global;
							
							// update undertime
							$update_undertime = 0;
							if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
								$workday_settings_end_time = $check_latest_timein_allowed->latest_time_in_allowed;#date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
							}
							if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
								#$h_per_d = $your_total_hours * 60;
							    $h_per_d = $check_latest_timein_allowed->total_hours_for_the_day * 60;
								$latest_time_in_allowed_start = date("Y-m-d",strtotime($new_employee_timein_date))." ".$check_latest_timein_allowed->latest_time_in_allowed;
								#$new_end_time = date("Y-m-d",strtotime($new_employee_timein_date))." ".$check_latest_timein_allowed->latest_time_in_allowed;
								#$new_end_time = date("Y-m-d H:i:s",strtotime($new_end_time." +{$h_per_d} minutes"));
								
								if(strtotime($new_time_in) >= strtotime($latest_time_in_allowed_start)) {
								    $new_end_time = date("Y-m-d H:i:s",strtotime($latest_time_in_allowed_start." +{$h_per_d} minutes"));
								} else {
								    $new_end_time = date("Y-m-d H:i:s",strtotime($new_time_in." +{$h_per_d} minutes"));
								}
								
								if(strtotime($new_end_time) > strtotime($new_time_out)) {
									$update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
								} else {
									$update_undertime = 0;
								}
							}
							
							// update total hours
							$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
							$update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - ($duration_of_lunch_break_per_day / 60);
			
							// check tardiness value
							$get_total_hours_worked = ($hours_worked / 2) + .5;
							if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
								$update_tardiness = 0;
								$update_undertime = 0;
								$flag_tu = 1;
							}
			
							// update total hours required
							$update_total_hours_required = ((strtotime($new_time_out) - strtotime($new_time_in)) / 3600) - ($update_tardiness / 60) - ($duration_of_lunch_break_per_day / 60);
			
							// if value is less than 0 then set value to 0
							if($update_tardiness < 0) $update_tardiness = 0;
							if($update_undertime < 0) $update_undertime = 0;
							if($update_total_hours < 0) $update_total_hours = 0;
							if($update_total_hours_required < 0) $update_total_hours_required = 0;
							
							// late with tardiness settings
							$new_late_min = $late_min -  $tardiness_global;
							// UPDATE TIME INS
							$date_insert = array(
								"comp_id"							=> $this->company_id,
								"emp_id"							=> $this->emp_id,
								"work_schedule_id"					=> $this->work_schedule_id,
								"date"								=> date("Y-m-d",strtotime($new_employee_timein_date)),
								"time_in_status"					=> 'pending',
								"corrected"							=> 'Yes',
								"reason"							=> $reason,
								"time_in"							=> $new_time_in,
								"lunch_out"							=> $new_lunch_out,
								"lunch_in"							=> $new_lunch_in,
								"time_out"							=> $new_time_out,
								"tardiness_min"						=> $update_tardiness,
								"undertime_min"						=> $update_undertime,
								"total_hours"						=> $your_total_hours,
								"total_hours_required"				=> $update_total_hours_required,
								"change_log_date_filed"				=> date("Y-m-d H:i:s"),
								"change_log_tardiness_min"			=> $update_tardiness,
								"change_log_undertime_min"			=> $update_undertime,
								"change_log_time_in"				=> $new_time_in,
								"change_log_lunch_out"				=> $new_lunch_out,
								"change_log_lunch_in"				=> $new_lunch_in,
								"change_log_time_out"				=> $new_time_out,
								"change_log_total_hours"			=> $your_total_hours,
								"change_log_total_hours_required"	=> $update_total_hours_required,
								"source"							=> "EP",
								"late_min"							=> $new_late_min,
							    "overbreak_min"						=> $overbreak_min,
							    "change_log_late_min"				=> $new_late_min,
							    "change_log_overbreak_min"			=> $overbreak_min,
                                "flag_payroll_correction"          	=> $flag_payroll_correction,
                                "rest_day_r_a"                      => $rest_day_r_a,
                                "flag_rd_include"                   => $flag_rd_include,
                                "holiday_approve"                   => $holiday_approve,
                                "flag_holiday_include"              => $flag_holiday_include,
							);
							
							$add_logs = $this->db->insert('employee_time_in', $date_insert);
							$emp_time_in_id = $this->db->insert_id();
						}
					}
				}
			
			}elseif($check_work_type == "Workshift"){
			    if($sched_blocks_id) {
			        $check_holiday = $this->employee->get_holiday_date($employee_timein_date,$this->emp_id,$this->company_id);
			        if(check_if_enable_breaks_on_holiday($this->company_id,$this->work_schedule_id,true,$sched_blocks_id)) {
			            $check_holiday = false;
			        }
			        
					$get_blocks_list = $this->elm->get_blocks_list($sched_blocks_id);
					$total_hours_split = $get_blocks_list->total_hours_work_per_block;
					
					// get info list of blocks for this day 
					$get_split_block_name = $this->employee->list_of_blocks($new_employee_timein_date,$this->emp_id,$this->work_schedule_id,$this->company_id);
					$total_hours_work_all_block = 0;
					$split_last_sched = false;
					
					if($get_split_block_name) {
						foreach ($get_split_block_name as $row) {
							$total_hours_work_all_block += $row->total_hours_work_per_block;
						}
						
						$split_last_sched = max($get_split_block_name);
					}
					
					$date_now = date("Y-m-d H:i:s");
					$date_shift_end = $new_employee_timein_date.' '.$split_last_sched->end_time;
					
					if($split_last_sched) {
					    if(strtotime($date_now) < strtotime($date_shift_end)) {
					        $result = array(
					            'error' => true,
					            'etime_out_date' => "Your shift for today has not yet ended.",
					            'error_msg' => "Your shift for today has not yet ended.",
					            'time_error' => ""
					        );
					        echo json_encode($result);
					        return false;
					    }
					}
					
					$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($new_time_in)));
					$this->work_schedule_id = $work_schedule_id;
					// get the first and last blocks
					$yest_list = $this->elm->list_of_blocks(date("Y-m-d",strtotime($new_time_in)),$this->emp_id,$work_schedule_id,$this->company_id);
					$first_sched = reset($yest_list);
					$last_sched = max($yest_list);
					
					//CHECKS IF RESTDAY OR NOT
					#if($check_rest_day || $check_holiday){
						/*$tardiness = 0;
						$undertime = 0;
						
						// update total hours and total hours required rest day
						$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
					
						// UPDATE TIME INS								
						$add_logs = $this->employee->add_split_to_sched_blocks_timein_table($employee_time_in_id,$sched_blocks_id,$this->company_id,$this->emp_id,$work_schedule_id,date("Y-m-d",strtotime($new_employee_timein_date)),
						    $reason,$new_time_in,null,null,$new_time_out,0,0,$total_hours_split,$get_total_hours,"",$get_total_hours,$get_total_hours,"",0,0,$flag_payroll_correction);*/
					#}else{
						// check employee work schedule
						// check break time
						$check_break_time = $this->ews->check_breaktime_split1($this->company_id, $this->emp_id, date("Y-m-d",strtotime($new_time_in)), $this->work_schedule_id,$sched_blocks_id);
						
						if($check_break_time->break_in_min == 0 || $check_break_time->break_in_min == null || $check_holiday){ // ZERO VALUE FOR BREAK TIME
							$sched_start = date("Y-m-d", strtotime($new_time_in)).' '.$check_break_time->start_time;
							$tardiness = 0;
								
							if(strtotime($new_time_in) > strtotime($sched_start)) {
								$tardiness = (strtotime($new_time_in) - strtotime($sched_start)) / 60;
							} else {
								$tardiness = 0;
							}
							
							// get total undertime
							$sched_end = date("Y-m-d", strtotime($new_time_out)).' '.$check_break_time->end_time;
							
							if(strtotime($sched_end) > strtotime($new_time_out)) {
								$undertime = (strtotime($sched_end) - strtotime($new_time_out)) / 60;
							} else {
								$undertime = 0;
							}
							
							// update total hours and total hours required rest day
							$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
							
							/* EMPLOYEE WORK SCHEDULE */
							$number_of_breaks_per_day = $check_break_time->break_in_min;
							
							/*$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($new_time_in)));
							
							// get the first and last blocks
							$yest_list = $this->elm->list_of_blocks(date("Y-m-d",strtotime($new_time_in)),$this->emp_id,$work_schedule_id,$this->company_id);
							$first_sched = reset($yest_list);
							$last_sched = max($yest_list);*/
								
							// hours worked
							$hours_worked = $this->elm->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $work_schedule_id);
							
							// check tardiness value
							$flag_tu = 0;
								
							$get_total_hours_worked = ($hours_worked / 2) + .5;
							$update_tardiness = $tardiness;
							$update_undertime = $undertime;
							$flag_tu = 1;
								
							// required hours worked only
							$new_total_hours = $this->elm->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out,$hours_worked,$work_schedule_id);
							
							// check employee_time_in table if the parent time in exist
							$where_chck = array(
							    'date' => date("Y-m-d",strtotime($new_employee_timein_date)),
							    'emp_id'	 => $this->emp_id,
							    'comp_id' => $this->company_id,
							    'work_schedule_id' => $work_schedule_id,
							    'status' => 'Active'
							);
							
							$this->db->where("(time_in_status != 'reject' OR time_in_status IS NULL)");
							$time_in_chck = get_table_info('employee_time_in',$where_chck);
							
							$valid_total_hours = $total_hours_split;
							if($time_in_chck) {
							    $get_all_child_block = $this->employee->get_all_child_block($this->emp_id,$this->company_id,$time_in_chck->employee_time_in_id);
							    
							    if($get_all_child_block) {
							        foreach ($get_all_child_block as $s) {
							            $valid_total_hours += $s->total_hours;
							        }
							    }
							}
							
							
							$total_hours_work_all_block_mins = $total_hours_work_all_block * 60;
							$for_absent_min = $total_hours_work_all_block_mins - ($valid_total_hours * 60);
							$for_absent_min = ($for_absent_min > 0) ? number_format($for_absent_min,2) : 0;
							
							if($check_holiday) {
							    $update_tardiness = 0;
							    $update_undertime = 0;
							    $for_absent_min = 0;
							}
							
							if ($first_sched->schedule_blocks_id == $last_sched->schedule_blocks_id) {
								$esave_time = array(
									"comp_id" => $this->company_id,
									"emp_id"	 => $this->emp_id,
									"work_schedule_id" => $work_schedule_id,
									"date" => date("Y-m-d",strtotime($new_employee_timein_date)),
									"time_in" => $new_time_in,
									"time_out" => $new_time_out,
									"corrected" => 'Yes',
									"total_hours" => $total_hours_work_all_block,
									"total_hours_required" => $get_total_hours,
									"tardiness_min" => $update_tardiness,
									"undertime_min" => $update_undertime,
									"flag_tardiness_undertime" => $flag_tu,
									"change_log_date_filed" => date("Y-m-d H:i:s"),
									"change_log_tardiness_min" => $update_tardiness,
									"change_log_undertime_min" => $update_undertime,
									"change_log_time_in" => $new_time_in,
									"change_log_time_out" => $new_time_out,
									"change_log_total_hours" => $total_hours_work_all_block,
									"change_log_total_hours_required" => $get_total_hours,
									"flag_halfday" => 1,
									"source"	 => "EP",
									"late_min" => $update_tardiness,
									"overbreak_min" => 0,
								    "absent_min" => $for_absent_min,
								    "change_log_late_min" => $update_tardiness,
								    "change_log_overbreak_min" => 0,
								    "change_log_absent_min" => $for_absent_min,
									"split_status" => 'pending',
									"time_in_status" => 'pending',
								    "flag_payroll_correction" => $flag_payroll_correction
								);
							
								$this->db->insert('employee_time_in', $esave_time);
							
								$employee_time_in_id = $this->db->insert_id();
							
								// INSERT TIME INS										
								$add_logs = $this->employee->add_split_to_sched_blocks_timein_table($employee_time_in_id,$sched_blocks_id,$this->company_id,$this->emp_id,$work_schedule_id,
										date("Y-m-d",strtotime($new_employee_timein_date)), $reason, $new_time_in, $new_lunch_out, $new_lunch_in, $new_time_out, $update_tardiness, $undertime, $total_hours_split, 
								    $get_total_hours, $flag_tu, $total_hours_split, $get_total_hours, "1", 0, 0,$flag_payroll_correction);
								
								$split_time_in_id = $this->db->insert_id();
							
								if($split_time_in_id) {
									$split_timein_information = $this->timeins->split_timeins_info($split_time_in_id,$this->company_id);
										
									$emp_split_time_in_id = $split_timein_information->schedule_blocks_time_in_id;
								} else {
									$emp_split_time_in_id = "";
								}
									
							} else {
							    if($time_in_chck){
							        $get_all_child_block = $this->employee->get_all_child_block($this->emp_id,$this->company_id,$time_in_chck->employee_time_in_id);
									$get_split_timein_logs = $this->employee->get_split_timein_logs($time_in_chck->employee_time_in_id, $this->emp_id, $this->company_id, date("Y-m-d",strtotime($new_time_in)));
									$get_valid_split_logs = $this->employee->get_valid_split_logs($this->emp_id, $this->company_id,$time_in_chck->employee_time_in_id);

									$change_log_total_hours = 0;
									$change_log_total_hours_required = 0;
									$change_log_tardiness_min = 0;
									$change_log_undertime_min = 0;
									
									$change_log_late_min = 0;
									$change_log_overbreak_min = 0;
									$change_log_absent_min = 0;
									
									if($get_split_timein_logs) {
										foreach ($get_split_timein_logs as $split_logs) {
											$change_log_total_hours += $split_logs->change_log_total_hours;
											$change_log_total_hours_required += $split_logs->change_log_total_hours_required;
											$change_log_tardiness_min += $split_logs->change_log_tardiness_min;
											$change_log_undertime_min += $split_logs->change_log_undertime_min;
											
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
									
									$timein_total_hours = $change_log_total_hours + $new_total_hours;
									$timein_total_hours_required = $change_log_total_hours_required + $get_total_hours;
									$timein_tardiness = $change_log_tardiness_min + $update_tardiness;
									$timein_undertime = $change_log_undertime_min + $update_undertime;
									
									$timein_late_min = $change_log_late_min + $tardiness;
									$timein_overbreak_min = $change_log_overbreak_min + $overbreak_min;
									
									if($check_holiday) {
									    $timein_tardiness = 0;
									    $timein_undertime = 0;
									    $for_absent_min = 0;
									    $timein_late_min = 0;
									}
									
									$block_chck = array();
									if($sched_blocks_id == $first_sched->schedule_blocks_id) {
										$block_chck = array(
											'date' => date("Y-m-d",strtotime($new_time_in)),
											'emp_id' => $this->emp_id,
											'comp_id' => $this->company_id,
										    'schedule_blocks_id' => $first_sched->schedule_blocks_id,
										    'status' => 'Active'
										);
									} elseif ($sched_blocks_id == $last_sched->schedule_blocks_id){
										$block_chck = array(
											'date' => date("Y-m-d",strtotime($new_time_in)),
											'emp_id' => $this->emp_id,
											'comp_id' => $this->company_id,
										    'schedule_blocks_id' => $last_sched->schedule_blocks_id,
										    'status' => 'Active'
										);
									}
									
									$sched_block_chck = get_table_info('schedule_blocks_time_in',$block_chck);
									
									if(!$sched_block_chck) {
										if($sched_blocks_id == $last_sched->schedule_blocks_id) {
										    if(!$get_valid_split_logs) {
										        $where = array(
										            "employee_time_in_id" => $time_in_chck->employee_time_in_id
										        );
										        
										        $esave_time = array(
										            "comp_id" => $this->company_id,
										            "emp_id" => $this->emp_id,
										            "work_schedule_id" => $work_schedule_id,
										            "date" => date("Y-m-d",strtotime($new_employee_timein_date)),
										            "time_out" => $new_time_out,
										            "corrected" => 'Yes',
										            "total_hours" => $total_hours_work_all_block,
										            "total_hours_required" => $timein_total_hours_required,
										            "tardiness_min" => $timein_tardiness,
										            "undertime_min" => $timein_undertime,
										            "flag_tardiness_undertime" => $flag_tu,
										            "change_log_date_filed" => date("Y-m-d H:i:s"),
										            "change_log_tardiness_min" => $timein_tardiness,
										            "change_log_undertime_min" => $timein_undertime,
										            "change_log_time_out" => $new_time_out,
										            "change_log_total_hours" => $total_hours_work_all_block,
										            "change_log_total_hours_required" => $timein_total_hours_required,
										            "flag_halfday" => 1,
										            "source" => "EP",
										            "late_min" => $timein_late_min,
										            "overbreak_min" => 0,
										            "absent_min" => $for_absent_min,
										            "change_log_late_min" => $timein_late_min,
										            "change_log_overbreak_min" => 0,
										            "change_log_absent_min" => $for_absent_min,
										            "split_status" => 'pending',
										            "time_in_status" => 'pending',
										            "flag_payroll_correction" => $flag_payroll_correction
										        );
										        
										        eupdate('employee_time_in',$esave_time,$where);
										    }
											
											// INSERT TIME INS													
											$add_logs = $this->employee->add_split_to_sched_blocks_timein_table($time_in_chck->employee_time_in_id,$sched_blocks_id,$this->company_id,$this->emp_id,$work_schedule_id,
													date("Y-m-d",strtotime($new_employee_timein_date)), $reason, $new_time_in, $new_lunch_out, $new_lunch_in, $new_time_out, $update_tardiness, $update_undertime, $total_hours_split,
											    $get_total_hours, $flag_tu, $total_hours_split, $get_total_hours, "1", $update_tardiness, 0,$flag_payroll_correction);
											
											$split_time_in_id = $this->db->insert_id();
											
											if($split_time_in_id) {
												$split_timein_information = $this->timeins->split_timeins_info($split_time_in_id,$this->company_id);
													
												$emp_split_time_in_id = $split_timein_information->schedule_blocks_time_in_id;
											} else {
												$emp_split_time_in_id = "";
											}
										} elseif ($sched_blocks_id == $first_sched->schedule_blocks_id){
										    if(!$get_valid_split_logs) {
										        $where = array(
										            "employee_time_in_id" => $time_in_chck->employee_time_in_id,
										        );
										        
										        $esave_time = array(
										            "comp_id" => $this->company_id,
										            "emp_id" => $this->emp_id,
										            "work_schedule_id" => $work_schedule_id,
										            "date" => date("Y-m-d",strtotime($new_employee_timein_date)),
										            "time_in" => $new_time_in,
										            "corrected" => 'Yes',
										            "total_hours" => $total_hours_work_all_block,
										            "total_hours_required" => $timein_total_hours_required,
										            "tardiness_min" => $timein_tardiness,
										            "undertime_min" => $timein_undertime,
										            "flag_tardiness_undertime" => $flag_tu,
										            "change_log_date_filed" => date("Y-m-d H:i:s"),
										            "change_log_tardiness_min" => $timein_tardiness,
										            "change_log_undertime_min" => $timein_undertime,
										            "change_log_time_in" => $new_time_in,
										            "change_log_total_hours" => $total_hours_work_all_block,
										            "change_log_total_hours_required" => $timein_total_hours_required,
										            "flag_halfday" => 1,
										            "source" => "EP",
										            "late_min" => $timein_late_min,
										            "overbreak_min" => 0,
										            "absent_min" => $for_absent_min,
										            "change_log_late_min" => $timein_late_min,
										            "change_log_overbreak_min" => 0,
										            "change_log_absent_min" => $for_absent_min,
										            "split_status" => 'pending',
										            "time_in_status" => 'pending',
										            "flag_payroll_correction" => $flag_payroll_correction
										        );
										        
										        eupdate('employee_time_in',$esave_time,$where);
										    }
												
											// INSERT TIME INS														
											$add_logs = $this->employee->add_split_to_sched_blocks_timein_table($time_in_chck->employee_time_in_id,$sched_blocks_id,$this->company_id,$this->emp_id,$work_schedule_id,
													date("Y-m-d",strtotime($new_employee_timein_date)), $reason, $new_time_in, $new_lunch_out, $new_lunch_in, $new_time_out, $update_tardiness, $update_undertime, $total_hours_split,
											    $get_total_hours, $flag_tu, $total_hours_split, $get_total_hours, "1", $update_tardiness, 0,$flag_payroll_correction);
											
											$split_time_in_id = $this->db->insert_id();
												
											if($split_time_in_id) {
												$split_timein_information = $this->timeins->split_timeins_info($split_time_in_id,$this->company_id);
													
												$emp_split_time_in_id = $split_timein_information->schedule_blocks_time_in_id;
											} else {
												$emp_split_time_in_id = "";
											}
										}
										
									} else {
										// INSERT TIME INS
										/** aldrin request - start **/
									    if($get_all_child_block) {
									        $block_count = count($get_all_child_block);
									        foreach ($get_all_child_block as $row) {
									            if($row->schedule_blocks_id == $first_sched->schedule_blocks_id && $block_count < 2) {
									                if($row->schedule_blocks_id != $sched_blocks_id) {
									                    if(!$get_valid_split_logs) {
									                        $where = array(
									                            "employee_time_in_id" => $time_in_chck->employee_time_in_id,
									                        );
									                        
									                        $esave_time = array(
									                            "time_out" => $new_time_out,
									                            "corrected" => 'Yes',
									                            "total_hours_required" => $timein_total_hours_required,
									                            "tardiness_min" => $timein_tardiness,
									                            "undertime_min" => $timein_undertime,
									                            "flag_tardiness_undertime" => $flag_tu,
									                            "change_log_date_filed" => date("Y-m-d H:i:s"),
									                            "change_log_tardiness_min" => $timein_tardiness,
									                            "change_log_undertime_min" => $timein_undertime,
									                            "change_log_time_out" => $new_time_out,
									                            "change_log_total_hours_required" => $timein_total_hours_required,
									                            "late_min" => $timein_late_min,
									                            "overbreak_min" => 0,
									                            "absent_min" => $for_absent_min,
									                            "change_log_late_min" => $timein_late_min,
									                            "change_log_overbreak_min" => 0,
									                            "change_log_absent_min" => $for_absent_min,
									                            "split_status" => 'pending',
									                            "time_in_status" => 'pending'
									                        );
									                        
									                        eupdate('employee_time_in',$esave_time,$where);
									                        break;
									                    }
									                }
									            } elseif ($row->schedule_blocks_id == $last_sched->schedule_blocks_id && $block_count < 2) {
									                if($row->schedule_blocks_id != $sched_blocks_id) {
									                    if(!$get_valid_split_logs) {
									                        $where = array(
									                            "employee_time_in_id" => $time_in_chck->employee_time_in_id,
									                        );
									                        
									                        $esave_time = array(
									                            "time_in" => $new_time_in,
									                            "corrected" => 'Yes',
									                            "total_hours_required" => $timein_total_hours_required,
									                            "tardiness_min" => $timein_tardiness,
									                            "undertime_min" => $timein_undertime,
									                            "flag_tardiness_undertime" => $flag_tu,
									                            "change_log_date_filed" => date("Y-m-d H:i:s"),
									                            "change_log_tardiness_min" => $timein_tardiness,
									                            "change_log_undertime_min" => $timein_undertime,
									                            "change_log_time_in" => $new_time_in,
									                            "change_log_total_hours_required" => $timein_total_hours_required,
									                            "late_min" => $timein_late_min,
									                            "overbreak_min" => 0,
									                            "absent_min" => $for_absent_min,
									                            "change_log_late_min" => $timein_late_min,
									                            "change_log_overbreak_min" => 0,
									                            "change_log_absent_min" => $for_absent_min,
									                            "split_status" => 'pending',
									                            "time_in_status" => 'pending'
									                        );
									                        
									                        eupdate('employee_time_in',$esave_time,$where);
									                        break;
									                    }
									                }
									            } else {
									                if($row->schedule_blocks_id != $sched_blocks_id) {
									                    if(!$get_valid_split_logs) {
									                        $where = array(
									                            "employee_time_in_id" => $time_in_chck->employee_time_in_id,
									                        );
									                        
									                        $esave_time = array(
									                            "corrected" => 'Yes',
									                            "total_hours_required" => $timein_total_hours_required,
									                            "tardiness_min" => $timein_tardiness,
									                            "undertime_min" => $timein_undertime,
									                            "flag_tardiness_undertime" => $flag_tu,
									                            "change_log_date_filed" => date("Y-m-d H:i:s"),
									                            "change_log_tardiness_min" => $timein_tardiness,
									                            "change_log_undertime_min" => $timein_undertime,
									                            "change_log_time_out" => $new_time_out,
									                            "change_log_total_hours_required" => $timein_total_hours_required,
									                            "late_min" => $timein_late_min,
									                            "overbreak_min" => 0,
									                            "absent_min" => $for_absent_min,
									                            "change_log_late_min" => $timein_late_min,
									                            "change_log_overbreak_min" => 0,
									                            "change_log_absent_min" => $for_absent_min,
									                            "split_status" => 'pending',
									                            "time_in_status" => 'pending',
									                        );
									                        
									                        eupdate('employee_time_in',$esave_time,$where);
									                        break;
									                    }
									                }
									            }
									        }
									    }
										
										/** aldrin request - end **/
																					
										$add_logs = $this->employee->add_split_to_sched_blocks_timein_table($time_in_chck->employee_time_in_id,$sched_blocks_id,$this->company_id,$this->emp_id,$work_schedule_id,
												date("Y-m-d",strtotime($new_employee_timein_date)), $reason, $new_time_in, null,null, $new_time_out, $update_tardiness, $update_undertime, $total_hours_split,
										    $get_total_hours, $flag_tu, $total_hours_split, $get_total_hours, "1", $update_tardiness, 0,$flag_payroll_correction);
										
										$split_time_in_id = $this->db->insert_id();
											
										if($split_time_in_id) {
											$split_timein_information = $this->timeins->split_timeins_info($split_time_in_id,$this->company_id);
												
											$emp_split_time_in_id = $split_timein_information->schedule_blocks_time_in_id;
										} else {
											$emp_split_time_in_id = "";
										}
									}
									
							    }else{					
							        if($check_holiday) {
							            $update_tardiness = 0;
							            $update_undertime = 0;
							            $for_absent_min = 0;
							            $timein_late_min = 0;
							        }
							        
							        if($sched_blocks_id == $first_sched->schedule_blocks_id) {
										$esave_time = array(
											"comp_id" => $this->company_id,
											"emp_id"	 => $this->emp_id,
											"work_schedule_id" => $work_schedule_id,
											"date" => date("Y-m-d",strtotime($new_employee_timein_date)),
											"time_in" => $new_time_in,
									        "time_out" => $new_time_out,
											"corrected" => 'Yes',
											"total_hours" => $total_hours_work_all_block,
											"total_hours_required" => $get_total_hours,
											"tardiness_min" => $update_tardiness,
											"undertime_min" => $update_undertime,
											"flag_tardiness_undertime" => $flag_tu,
											"change_log_date_filed" => date("Y-m-d H:i:s"),
											"change_log_tardiness_min" => $update_tardiness,
											"change_log_undertime_min" => $update_undertime,
											"change_log_time_in" => $new_time_in,
									        "change_log_time_out" => $new_time_out,
											"change_log_total_hours" => $total_hours_work_all_block,
											"change_log_total_hours_required" => $get_total_hours,
											"flag_halfday" => 1,
											"source" => "EP",
											"late_min" => $update_tardiness,
											"overbreak_min" => 0,
										    "absent_min" => $for_absent_min,
										    "change_log_late_min" => $update_tardiness,
										    "change_log_overbreak_min" => 0,
										    "change_log_absent_min" => $for_absent_min,
											"split_status" => 'pending',
										    "time_in_status" => 'pending',
										    "flag_payroll_correction" => $flag_payroll_correction
										);
										
										$this->db->insert('employee_time_in', $esave_time);
										
										$employee_time_in_id = $this->db->insert_id();
										
										// INSERT TIME INS												
										$add_logs = $this->employee->add_split_to_sched_blocks_timein_table($employee_time_in_id,$sched_blocks_id,$this->company_id,$this->emp_id,$work_schedule_id,
												date("Y-m-d",strtotime($new_employee_timein_date)), $reason, $new_time_in, null,null, $new_time_out, $update_tardiness, $update_undertime, $total_hours_split,
										    $get_total_hours, $flag_tu, $total_hours_split, $get_total_hours, "1", $update_tardiness, 0,$flag_payroll_correction);
										
										$split_time_in_id = $this->db->insert_id();
										
										if($employee_time_in_id) {
											$split_timein_information = $this->timeins->split_timeins_info($split_time_in_id,$this->company_id);
										
											$emp_split_time_in_id = $split_timein_information->schedule_blocks_time_in_id;
										} else {
											$emp_split_time_in_id = "";
										}
							        } elseif ($sched_blocks_id == $last_sched->schedule_blocks_id){
										$esave_time = array(
											"comp_id" => $this->company_id,
											"emp_id"	 => $this->emp_id,
											"work_schedule_id" => $work_schedule_id,
											"date" => date("Y-m-d",strtotime($new_employee_timein_date)),
											"time_out" => $new_time_out,
											"corrected" => 'Yes',
											"total_hours" => $total_hours_work_all_block,
											"total_hours_required" => $get_total_hours,
											"tardiness_min" => $timein_tardiness,
											"undertime_min" => $timein_undertime,
											"flag_tardiness_undertime" => $flag_tu,
											"change_log_date_filed" => date("Y-m-d H:i:s"),
											"change_log_tardiness_min" => $update_tardiness,
											"change_log_undertime_min" => $update_undertime,
											"change_log_time_out" => $new_time_out,
											"change_log_total_hours" => $total_hours_work_all_block,
											"change_log_total_hours_required" => $get_total_hours,
											"flag_halfday" => 1,
											"source"	 => "EP",
											"late_min" => $update_tardiness,
											"overbreak_min" => 0,
										    "absent_min" => $for_absent_min,
										    "change_log_late_min" => $update_tardiness,
										    "change_log_overbreak_min" => 0,
										    "change_log_absent_min" => $for_absent_min,
											"split_status" => 'pending',
										    "time_in_status" => 'pending',
										    "flag_payroll_correction" => $flag_payroll_correction
										);
										
										$this->db->insert('employee_time_in', $esave_time);
										
										$employee_time_in_id = $this->db->insert_id();
										
										// INSERT TIME INS												
										$add_logs = $this->employee->add_split_to_sched_blocks_timein_table($employee_time_in_id,$sched_blocks_id,$this->company_id,$this->emp_id,$work_schedule_id,
												date("Y-m-d",strtotime($new_employee_timein_date)), $reason, $new_time_in, null,null, $new_time_out, $update_tardiness, $update_undertime, $total_hours_split,
										    $get_total_hours, $flag_tu, $total_hours_split, $get_total_hours, "1", $update_tardiness, 0,$flag_payroll_correction);
										
										$split_time_in_id = $this->db->insert_id();
										
										if($employee_time_in_id) {
											$split_timein_information = $this->timeins->split_timeins_info($split_time_in_id,$this->company_id);
										
											$emp_split_time_in_id = $split_timein_information->schedule_blocks_time_in_id;
										} else {
											$emp_split_time_in_id = "";
										}
							        } else {
							            if($check_holiday) {
							                $timein_tardiness = 0;
							                $timein_undertime = 0;
							                $for_absent_min = 0;
							            }
							            
							            $esave_time = array(
							                "comp_id" => $this->company_id,
							                "emp_id" => $this->emp_id,
							                "work_schedule_id" => $work_schedule_id,
							                "date" => date("Y-m-d",strtotime($new_employee_timein_date)),
							                "time_out" => $new_time_out,
							                "corrected" => 'Yes',
							                "total_hours" => $total_hours_work_all_block,
							                "total_hours_required" => $get_total_hours,
							                "tardiness_min" => $timein_tardiness,
							                "undertime_min" => $timein_undertime,
							                "flag_tardiness_undertime" => $flag_tu,
							                "change_log_date_filed" => date("Y-m-d H:i:s"),
							                "change_log_tardiness_min" => $update_tardiness,
							                "change_log_undertime_min" => $update_undertime,
							                "change_log_time_out" => $new_time_out,
							                "change_log_total_hours" => $total_hours_work_all_block,
							                "change_log_total_hours_required" => $get_total_hours,
							                "flag_halfday" => 1,
							                "source" => "EP",
							                "late_min" => $update_tardiness,
							                "overbreak_min" => 0,
							                "absent_min" => $for_absent_min,
							                "change_log_late_min" => $update_tardiness,
							                "change_log_overbreak_min" => 0,
							                "change_log_absent_min" => $for_absent_min,
							                "split_status" => 'pending',
							                "time_in_status" => 'pending',
							                "flag_payroll_correction" => $flag_payroll_correction
							            );
							            
							            $this->db->insert('employee_time_in', $esave_time);
							            
							            $employee_time_in_id = $this->db->insert_id();
							            
							            // INSERT TIME INS
							            $add_logs = $this->employee->add_split_to_sched_blocks_timein_table($employee_time_in_id,$sched_blocks_id,$this->company_id,$this->emp_id,$work_schedule_id,
							                date("Y-m-d",strtotime($new_employee_timein_date)), $reason, $new_time_in, null,null, $new_time_out, $update_tardiness, $update_undertime, $total_hours_split,
							                $get_total_hours, $flag_tu, $total_hours_split, $get_total_hours, "1", $update_tardiness, 0,$flag_payroll_correction);
							            
							            $split_time_in_id = $this->db->insert_id();
							            
							            if($employee_time_in_id) {
							                $split_timein_information = $this->timeins->split_timeins_info($split_time_in_id,$this->company_id);
							                
							                $emp_split_time_in_id = $split_timein_information->schedule_blocks_time_in_id;
							            } else {
							                $emp_split_time_in_id = "";
							            }
							        }
								}
							}
						}else if($new_time_in != "" && $new_time_out != "" && $new_lunch_in != "" && $new_lunch_out != ""){
							// WHOLEDAY
						    $sched_start = $employee_timein_date.' '.$check_break_time->start_time;
						    $shift_total_hours_per_block = ($check_break_time->total_hours_work_per_block * 60 ) + $check_break_time->break_in_min;
						    $sched_end = date("Y-m-d H:i:s", strtotime($sched_start.' +'.$shift_total_hours_per_block.' minutes'));
						    
							#$sched_start = date("Y-m-d", strtotime($new_time_in)).' '.$check_break_time->start_time;
							$tardiness = 0;
							
							if(strtotime($new_time_in) > strtotime($sched_start)) {
								$tardiness_1 = (strtotime($new_time_in) - strtotime($sched_start)) / 60;
							} else {
								$tardiness_1 = 0;
							}
							
							/*if($check_break_time->break_in_min != null || $check_break_time->break_in_min != "" || $check_break_time->break_in_min != 0) {
								$minus_break = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
								if($minus_break > $check_break_time->break_in_min) {
									$tardiness_2 = $minus_break - $check_break_time->break_in_min;
								} else {
									$tardiness_2 = 0;
								}
							} else {
								$tardiness_2 = 0;
							} */
							
							#$tardiness = $tardiness_1 + $tardiness_2;
							$tardiness = $tardiness_1;
							
							// get total undertime
							#$sched_end = date("Y-m-d", strtotime($new_time_out)).' '.$check_break_time->end_time;
								
							if(strtotime($sched_end) > strtotime($new_time_out)) {
								$undertime = (strtotime($sched_end) - strtotime($new_time_out)) / 60;
							} else {
								$undertime = 0;
							}
							
							$hours_worked = $check_break_time->total_hours_work_per_block;
							
							// total hours worked
							$total_hours_worked = $this->elm->get_tot_hours_limit($this->emp_id, $this->company_id, $new_time_in, $new_lunch_out, $new_lunch_in, $new_time_out, $this->work_schedule_id, $check_break_time->break_in_min);

							// check tardiness value
							$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
							$flag_tu = 0;
							
							$get_total_hours_worked = ($hours_worked / 2) + .5;
							
							if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
								$update_tardiness = $tardiness;
								$update_undertime = $undertime;
								$flag_tu = 1;
							}
							
							/*$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($new_time_in)));
							// get the first and last blocks
							$yest_list = $this->elm->list_of_blocks(date("Y-m-d",strtotime($new_time_in)),$this->emp_id,$work_schedule_id,$this->company_id);
							$first_sched = reset($yest_list);
							$last_sched = max($yest_list);*/
							
							// check employee_time_in table if the parent time in exist
							$where_chck = array(
								'date' => date("Y-m-d",strtotime($new_time_in)),
								'emp_id' => $this->emp_id,
								'comp_id' => $this->company_id,
							    'work_schedule_id' => $work_schedule_id,
							    'status' => 'Active'
							);
							
							$this->db->where("(time_in_status != 'reject' OR time_in_status IS NULL)");
							$time_in_chck = get_table_info('employee_time_in',$where_chck);

							$valid_total_hours = $total_hours_split;
							if($time_in_chck) {
							    $get_all_child_block = $this->employee->get_all_child_block($this->emp_id,$this->company_id,$time_in_chck->employee_time_in_id);
							    
							    if($get_all_child_block) {
							        foreach ($get_all_child_block as $s) {
							            $valid_total_hours += $s->total_hours;
							        }
							    }
							}
							
							$total_hours_work_all_block_mins = $total_hours_work_all_block * 60;
							$for_absent_min = $total_hours_work_all_block_mins - ($valid_total_hours * 60);
							$for_absent_min = ($for_absent_min > 0) ? number_format($for_absent_min,2) : 0;
							
							if($check_holiday) {
							    $tardiness = 0;
							    $undertime = 0;
							    $for_absent_min = 0;
							}
							
							if ($first_sched->schedule_blocks_id == $last_sched->schedule_blocks_id) {
								$esave_time = array(
								    "comp_id" => $this->company_id,
								    "emp_id" => $this->emp_id,
								    "work_schedule_id" => $work_schedule_id,
								    "date" => date("Y-m-d",strtotime($new_employee_timein_date)),
								    "time_in" => $new_time_in,
								    "time_out" => $new_time_out,
								    "corrected" => 'Yes',
								    "total_hours" => $total_hours_work_all_block,
								    "total_hours_required" => $total_hours_worked,
								    "tardiness_min" => $tardiness + $overbreak_min,
								    "undertime_min" => $undertime,
								    "flag_tardiness_undertime" => $flag_tu,
								    "change_log_date_filed" => date("Y-m-d H:i:s"),
								    "change_log_tardiness_min" => $tardiness + $overbreak_min,
								    "change_log_undertime_min" => $undertime,
								    "change_log_time_in" => $new_time_in,
									"change_log_time_out" => $new_time_out,
									"change_log_total_hours" => $total_hours_work_all_block,
									"change_log_total_hours_required" => $total_hours_worked,
									"flag_halfday" => 1,
									"source" => "EP",
									"late_min" => $tardiness,
									"overbreak_min" => $overbreak_min,
								    "absent_min" => $for_absent_min,
								    "change_log_late_min" => $tardiness,
								    "change_log_overbreak_min" => $overbreak_min,
								    "change_log_absent_min" => $for_absent_min,
									"split_status" => 'pending',
								    "time_in_status" => 'pending',
								    "flag_payroll_correction" => $flag_payroll_correction
								);
							
								$this->db->insert('employee_time_in', $esave_time);
							
								$employee_time_in_id = $this->db->insert_id();
							
								// INSERT TIME INS
								$over_tardiness = $tardiness + $overbreak_min;
								
								$add_logs = $this->employee->add_split_to_sched_blocks_timein_table($employee_time_in_id,$sched_blocks_id,$this->company_id,$this->emp_id,$work_schedule_id,
										date("Y-m-d",strtotime($new_employee_timein_date)), $reason, $new_time_in, $new_lunch_out, $new_lunch_in, $new_time_out, $over_tardiness, $undertime, $total_hours_split,
								    $get_total_hours, $flag_tu, $total_hours_split, $total_hours_worked, "1", $tardiness, $overbreak_min,$flag_payroll_correction);
							
								$split_time_in_id = $this->db->insert_id();
							
								if($split_time_in_id) {
									$split_timein_information = $this->timeins->split_timeins_info($split_time_in_id,$this->company_id);
									
									$emp_split_time_in_id = $split_timein_information->schedule_blocks_time_in_id;
								} else {
									$emp_split_time_in_id = "";
								}
								
							} else {
							    if($time_in_chck){
								    $get_all_child_block = $this->employee->get_all_child_block($this->emp_id,$this->company_id,$time_in_chck->employee_time_in_id);
									/* calculation for total hours, tardiness and undertime */
									$get_split_timein_logs = $this->employee->get_split_timein_logs($time_in_chck->employee_time_in_id, $this->emp_id, $this->company_id, date("Y-m-d",strtotime($new_time_in)));
									$get_valid_split_logs = $this->employee->get_valid_split_logs($this->emp_id, $this->company_id,$time_in_chck->employee_time_in_id);
									
									$change_log_total_hours = 0;
									$change_log_total_hours_required = 0;
									$change_log_tardiness_min = 0;
									$change_log_undertime_min = 0;
									
									$change_log_late_min = 0;
									$change_log_overbreak_min = 0;
									$change_log_absent_min = 0;
										
									if($get_split_timein_logs) {
										foreach ($get_split_timein_logs as $split_logs) {
											$change_log_total_hours += $split_logs->change_log_total_hours;
											$change_log_total_hours_required += $split_logs->change_log_total_hours_required;
											$change_log_tardiness_min += $split_logs->change_log_tardiness_min;
											$change_log_undertime_min += $split_logs->change_log_undertime_min;
											
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
										
									$timein_total_hours = $change_log_total_hours + $total_hours_worked;
									$timein_total_hours_required = $change_log_total_hours_required + $total_hours_worked;
									$timein_tardiness = $change_log_tardiness_min + $tardiness + $overbreak_min;
									$timein_undertime = $change_log_undertime_min + $undertime;
									
									$timein_late_min = $change_log_late_min + $tardiness;
									$timein_overbreak_min = $change_log_overbreak_min + $overbreak_min;
									
									$block_chck = array();
									if($sched_blocks_id == $first_sched->schedule_blocks_id) {
										$block_chck = array(
												'date' => date("Y-m-d",strtotime($new_employee_timein_date)),
												'emp_id' => $this->emp_id,
												'comp_id' => $this->company_id,
												'schedule_blocks_id' => $first_sched->schedule_blocks_id
										);
									} elseif ($sched_blocks_id == $last_sched->schedule_blocks_id){
										$block_chck = array(
												'date' => date("Y-m-d",strtotime($new_time_in)),
												'emp_id' => $this->emp_id,
												'comp_id' => $this->company_id,
												'schedule_blocks_id' => $last_sched->schedule_blocks_id
										);
									}
																				
									$sched_block_chck = get_table_info('schedule_blocks_time_in',$block_chck);
									
									if($check_holiday) {
									    $timein_tardiness = 0;
									    $timein_undertime = 0;
									    $for_absent_min = 0;
									    $timein_late_min = 0;
									}
									
									if(!$sched_block_chck) {
									    if($sched_blocks_id == $last_sched->schedule_blocks_id) {
											    if(!$get_valid_split_logs) {
											        $where = array(
											            "employee_time_in_id" => $time_in_chck->employee_time_in_id,
											        );
											        
											        $esave_time = array(
											            "comp_id" => $this->company_id,
											            "emp_id" => $this->emp_id,
											            "work_schedule_id" => $work_schedule_id,
											            "date" => date("Y-m-d",strtotime($new_employee_timein_date)),
											            "time_out" => $new_time_out,
											            "corrected" => 'Yes',
											            "total_hours" => $total_hours_work_all_block,
											            "total_hours_required" => $timein_total_hours_required,
											            "tardiness_min" => $timein_tardiness + $overbreak_min,
											            "undertime_min" => $timein_undertime,
											            "flag_tardiness_undertime" => $flag_tu,
											            "change_log_date_filed" => date("Y-m-d H:i:s"),
											            "change_log_tardiness_min" => $timein_tardiness + $overbreak_min,
											            "change_log_undertime_min" => $timein_undertime,
											            "change_log_time_out" => $new_time_out,
											            "change_log_total_hours" => $total_hours_work_all_block,
											            "change_log_total_hours_required" => $timein_total_hours_required,
											            "flag_halfday" => 1,
											            "source" => "EP",
											            "late_min" => $timein_tardiness,
											            "overbreak_min" => $overbreak_min,
											            "absent_min" => $for_absent_min,
											            "change_log_late_min" => $timein_tardiness,
											            "change_log_overbreak_min" => $overbreak_min,
											            "change_log_absent_min" => $for_absent_min,
											            "split_status" => 'pending',
											            "time_in_status" => 'pending',
											            "flag_payroll_correction" => $flag_payroll_correction
											        );
											        
											        eupdate('employee_time_in',$esave_time,$where);
											    }
											
											// INSERT TIME INS													
											$over_tardiness = $tardiness + $overbreak_min;
											
											$add_logs = $this->employee->add_split_to_sched_blocks_timein_table($time_in_chck->employee_time_in_id,$sched_blocks_id,$this->company_id,$this->emp_id,$work_schedule_id,
													date("Y-m-d",strtotime($new_employee_timein_date)), $reason, $new_time_in, $new_lunch_out, $new_lunch_in, $new_time_out, $over_tardiness, $undertime, $total_hours_split,
											    $get_total_hours, $flag_tu, $total_hours_split, $total_hours_worked, "1", $tardiness, $overbreak_min,$flag_payroll_correction);
											
											$split_time_in_id = $this->db->insert_id();
											
											if($split_time_in_id) {
												$split_timein_information = $this->timeins->split_timeins_info($split_time_in_id,$this->company_id);
													
												$emp_split_time_in_id = $split_timein_information->schedule_blocks_time_in_id;
											} else {
												$emp_split_time_in_id = "";
											}
									    } elseif ($sched_blocks_id == $first_sched->schedule_blocks_id){
											    if(!$get_valid_split_logs) {
											        $where = array(
											            "employee_time_in_id" => $time_in_chck->employee_time_in_id,
											        );
											        
											        $esave_time = array(
											            "comp_id" => $this->company_id,
											            "emp_id" => $this->emp_id,
											            "work_schedule_id" => $work_schedule_id,
											            "date" => date("Y-m-d",strtotime($new_employee_timein_date)),
											            "time_in" => $new_time_in,
											            "corrected" => 'Yes',
											            "total_hours" => $total_hours_work_all_block,
											            "total_hours_required" => $timein_total_hours_required,
											            "tardiness_min" => $timein_tardiness + $overbreak_min,
											            "undertime_min" => $timein_undertime,
											            "flag_tardiness_undertime" => $flag_tu,
											            "change_log_date_filed" => date("Y-m-d H:i:s"),
											            "change_log_tardiness_min" => $timein_tardiness + $overbreak_min,
											            "change_log_undertime_min" => $timein_undertime,
											            "change_log_time_in" => $new_time_in,
											            "change_log_total_hours" => $total_hours_work_all_block,
											            "change_log_total_hours_required" => $timein_total_hours_required,
											            "flag_halfday" => 1,
											            "source" => "EP",
											            "late_min" => $timein_tardiness,
											            "overbreak_min" => $overbreak_min,
											            "absent_min" => $for_absent_min,
											            "change_log_late_min" => $timein_tardiness,
											            "change_log_overbreak_min" => $overbreak_min,
											            "change_log_absent_min" => $for_absent_min,
											            "split_status" => 'pending',
											            "time_in_status" => 'pending',
											            "flag_payroll_correction" => $flag_payroll_correction
											        );
											        
											        eupdate('employee_time_in',$esave_time,$where);
											    }
											    
											    // INSERT TIME INS													
											$over_tardiness = $tardiness + $overbreak_min;
											
											$add_logs = $this->employee->add_split_to_sched_blocks_timein_table($time_in_chck->employee_time_in_id,$sched_blocks_id,$this->company_id,$this->emp_id,$work_schedule_id,
													date("Y-m-d",strtotime($new_employee_timein_date)), $reason, $new_time_in, $new_lunch_out, $new_lunch_in, $new_time_out, $over_tardiness, $undertime, $total_hours_split,
											    $get_total_hours, $flag_tu, $total_hours_split, $total_hours_worked, "1", $tardiness, $overbreak_min,$flag_payroll_correction);
												
											$split_time_in_id = $this->db->insert_id();
												
											if($split_time_in_id) {
												$split_timein_information = $this->timeins->split_timeins_info($split_time_in_id,$this->company_id);
													
												$emp_split_time_in_id = $split_timein_information->schedule_blocks_time_in_id;
											} else {
												$emp_split_time_in_id = "";
											}
										}
									} else {
									    // INSERT TIME INS 
										/** aldrin request - start **/
									    if($get_all_child_block) {
									        $block_count = count($get_all_child_block);
									        foreach ($get_all_child_block as $row) {
									            if($row->schedule_blocks_id == $first_sched->schedule_blocks_id && $block_count < 2) {
									                if($row->schedule_blocks_id != $sched_blocks_id) {
									                    if(!$get_valid_split_logs) {
									                        $where = array(
									                            "employee_time_in_id" => $time_in_chck->employee_time_in_id,
									                        );
									                        
									                        $esave_time = array(
									                            "time_out" => $new_time_out,
									                            "corrected" => 'Yes',
									                            "total_hours_required" => $timein_total_hours_required,
									                            "tardiness_min" => $timein_tardiness,
									                            "undertime_min" => $timein_undertime,
									                            "flag_tardiness_undertime" => $flag_tu,
									                            "change_log_date_filed" => date("Y-m-d H:i:s"),
									                            "change_log_tardiness_min" => $timein_tardiness,
									                            "change_log_undertime_min" => $timein_undertime,
									                            "change_log_time_out" => $new_time_out,
									                            "change_log_total_hours_required" => $timein_total_hours_required,
									                            "late_min" => $timein_late_min,
									                            "overbreak_min" => $timein_overbreak_min,
									                            "absent_min" => $for_absent_min,
									                            "change_log_late_min" => $timein_late_min,
									                            "change_log_overbreak_min" => $timein_overbreak_min,
									                            "change_log_absent_min" => $for_absent_min,
									                            "split_status" => 'pending',
									                            "time_in_status" => 'pending'
									                        );
									                        
									                        eupdate('employee_time_in',$esave_time,$where);
									                        break;
									                    }
									                }
									            } elseif ($row->schedule_blocks_id == $last_sched->schedule_blocks_id && $block_count < 2) {
									                if($row->schedule_blocks_id != $sched_blocks_id) {
									                    if(!$get_valid_split_logs) {
									                        $where = array(
									                            "employee_time_in_id" => $time_in_chck->employee_time_in_id,
									                        );
									                        
									                        $esave_time = array(
									                            "time_in" => $new_time_in,
									                            "corrected" => 'Yes',
									                            "total_hours_required" => $timein_total_hours_required,
									                            "tardiness_min" => $timein_tardiness,
									                            "undertime_min" => $timein_undertime,
									                            "flag_tardiness_undertime" => $flag_tu,
									                            "change_log_date_filed" => date("Y-m-d H:i:s"),
									                            "change_log_tardiness_min" => $timein_tardiness,
									                            "change_log_undertime_min" => $timein_undertime,
									                            "change_log_time_in" => $new_time_in,
									                            "change_log_total_hours_required" => $timein_total_hours_required,
									                            "late_min" => $timein_late_min,
									                            "overbreak_min" => $timein_overbreak_min,
									                            "absent_min" => $for_absent_min,
									                            "change_log_late_min" => $timein_late_min,
									                            "change_log_overbreak_min" => $timein_overbreak_min,
									                            "change_log_absent_min" => $for_absent_min,
									                            "split_status" => 'pending',
									                            "time_in_status" => 'pending'
									                        );
									                        eupdate('employee_time_in',$esave_time,$where);
									                        break;
									                    }
									                }
									            } else {
									                if($row->schedule_blocks_id != $sched_blocks_id) {
									                    if(!$get_valid_split_logs) {
									                        $where = array(
									                            "employee_time_in_id" => $time_in_chck->employee_time_in_id,
									                        );
									                        
									                        $esave_time = array(
									                            "corrected" => 'Yes',
									                            "total_hours_required" => $timein_total_hours_required,
									                            "tardiness_min" => $timein_tardiness,
									                            "undertime_min" => $timein_undertime,
									                            "flag_tardiness_undertime" => $flag_tu,
									                            "change_log_date_filed" => date("Y-m-d H:i:s"),
									                            "change_log_tardiness_min" => $timein_tardiness,
									                            "change_log_undertime_min" => $timein_undertime,
									                            "change_log_time_out" => $new_time_out,
									                            "change_log_total_hours_required" => $timein_total_hours_required,
									                            "late_min" => $timein_late_min,
									                            "overbreak_min" => $timein_overbreak_min,
									                            "absent_min" => $for_absent_min,
									                            "change_log_late_min" => $timein_late_min,
									                            "change_log_overbreak_min" => $timein_overbreak_min,
									                            "change_log_absent_min" => $for_absent_min,
									                            "split_status" => 'pending',
									                            "time_in_status" => 'pending',
									                        );
									                        
									                        eupdate('employee_time_in',$esave_time,$where);
									                        break;
									                    }
									                }
									            }
									        }
									    }
										
										/** aldrin request - end **/
										
										$over_tardiness = $tardiness + $overbreak_min;
											
										$add_logs = $this->employee->add_split_to_sched_blocks_timein_table($time_in_chck->employee_time_in_id,$sched_blocks_id,$this->company_id,$this->emp_id,$work_schedule_id,
												date("Y-m-d",strtotime($new_employee_timein_date)), $reason, $new_time_in, $new_lunch_out, $new_lunch_in, $new_time_out, $over_tardiness, $undertime, $total_hours_split,
										    $get_total_hours, $flag_tu, $total_hours_split, $total_hours_worked, "1", $tardiness, $overbreak_min,$flag_payroll_correction);
										
										$split_time_in_id = $this->db->insert_id();
											
										if($split_time_in_id) {
											$split_timein_information = $this->timeins->split_timeins_info($split_time_in_id,$this->company_id);
												
											$emp_split_time_in_id = $split_timein_information->schedule_blocks_time_in_id;
										} else {
											$emp_split_time_in_id = "";
										}
									}
							    } else {
									$timein_total_hours = 0;
									$timein_total_hours_required = 0;
									$timein_tardiness = 0;
									$timein_undertime = 0;
									
									if($check_holiday) {
									    $tardiness = 0;
									    $undertime = 0;
									    $for_absent_min = 0;
									}
									
									$block_chck = array();
									if($sched_blocks_id == $first_sched->schedule_blocks_id) {
										$esave_time = array(
											"comp_id" => $this->company_id,
											"emp_id" => $this->emp_id,
											"work_schedule_id" => $work_schedule_id,
											"date" => date("Y-m-d",strtotime($new_employee_timein_date)),
											"time_in" => $new_time_in,
											"corrected" => 'Yes',
											"total_hours" => $total_hours_work_all_block,
											"total_hours_required" => $timein_total_hours_required,
											"tardiness_min" => $tardiness + $overbreak_min,
											"undertime_min" => $undertime,
											"flag_tardiness_undertime" => $flag_tu,
											"change_log_date_filed" => date("Y-m-d H:i:s"),
											"change_log_tardiness_min" => $tardiness + $overbreak_min,
											"change_log_undertime_min" => $undertime,
											"change_log_time_in" => $new_time_in,
											"change_log_total_hours" => $total_hours_work_all_block,
											"change_log_total_hours_required" => $timein_total_hours_required,
											"flag_halfday" => 1,
											"source" => "EP",
											"late_min" => $tardiness,
										    "overbreak_min" => $overbreak_min,
										    "absent_min" => $for_absent_min,
										    "change_log_late_min" => $tardiness,
										    "change_log_overbreak_min" => $overbreak_min,
										    "change_log_absent_min" => $for_absent_min,
											"split_status" => 'pending',
										    "time_in_status"	=> 'pending',
										    "flag_payroll_correction" => $flag_payroll_correction
										);
										
										$this->db->insert('employee_time_in', $esave_time);
										
										$employee_time_in_id = $this->db->insert_id();
										
										/* calculation for total hours, tardiness and undertime */
										$get_split_timein_logs = $this->employee->get_split_timein_logs($employee_time_in_id, $this->emp_id, $this->company_id, date("Y-m-d",strtotime($new_time_in)));
											
										$change_log_total_hours = 0;
										$change_log_total_hours_required = 0;
										$change_log_tardiness_min = 0;
										$change_log_undertime_min = 0;
											
										if($get_split_timein_logs) {
											foreach ($get_split_timein_logs as $split_logs) {
												$change_log_total_hours += $split_logs->change_log_total_hours;
												$change_log_total_hours_required += $split_logs->change_log_total_hours_required;
												$change_log_tardiness_min += $split_logs->change_log_tardiness_min;
												$change_log_undertime_min += $split_logs->change_log_undertime_min;
											}
										}
											
										$timein_total_hours = $change_log_total_hours + $total_hours_worked;
										$timein_total_hours_required = $change_log_total_hours_required + $total_hours_worked;
										$timein_tardiness = $change_log_tardiness_min + $tardiness;
										$timein_undertime = $change_log_undertime_min + $undertime;
										/* end  */
										
										// INSERT TIME INS												
										$over_tardiness = $tardiness + $overbreak_min;
										
										$add_logs = $this->employee->add_split_to_sched_blocks_timein_table($employee_time_in_id,$sched_blocks_id,$this->company_id,$this->emp_id,$work_schedule_id,
												date("Y-m-d",strtotime($new_employee_timein_date)), $reason, $new_time_in, $new_lunch_out, $new_lunch_in, $new_time_out, $over_tardiness, $undertime, $total_hours_split,
										    $get_total_hours, $flag_tu, $total_hours_split, $total_hours_worked, "1", $tardiness, $overbreak_min,$flag_payroll_correction);
										
										$split_time_in_id = $this->db->insert_id();
										
										if($split_time_in_id) {
											$split_timein_information = $this->timeins->split_timeins_info($split_time_in_id,$this->company_id);
										
											$emp_split_time_in_id = $split_timein_information->schedule_blocks_time_in_id;
										} else {
											$emp_split_time_in_id = "";
										}
									} elseif ($sched_blocks_id == $last_sched->schedule_blocks_id){											    
									    $esave_time = array(
									        "comp_id" => $this->company_id,
									        "emp_id" => $this->emp_id,
									        "work_schedule_id" => $work_schedule_id,
									        "date" => date("Y-m-d",strtotime($new_employee_timein_date)),
									        "time_in" => $new_time_in,
									        "time_out" => $new_time_out,
									        "corrected" => 'Yes',
									        "total_hours" => $total_hours_work_all_block,
									        "total_hours_required" => $total_hours_worked, //$timein_total_hours_required,
									        "tardiness_min" => $tardiness + $overbreak_min,
									        "undertime_min" => $undertime,
									        "flag_tardiness_undertime" => $flag_tu,
									        "change_log_date_filed" => date("Y-m-d H:i:s"),
									        "change_log_tardiness_min" => $tardiness + $overbreak_min,
									        "change_log_undertime_min" => $undertime,
									        "change_log_time_in" => $new_time_in,
									        "change_log_time_out" => $new_time_out,
									        "change_log_total_hours" => $total_hours_work_all_block,
									        "change_log_total_hours_required" => $total_hours_worked, //$timein_total_hours_required,
									        "flag_halfday" => 1,
									        "source"	 => "EP",
									        "late_min" => $tardiness,
									        "overbreak_min" => $overbreak_min,
									        "absent_min" => $for_absent_min,
									        "change_log_late_min" => $tardiness,
									        "change_log_overbreak_min" => $overbreak_min,
									        "change_log_absent_min" => $for_absent_min,
									        "split_status" => 'pending',
									        "time_in_status" => 'pending',
									        "flag_payroll_correction" => $flag_payroll_correction
									    );
									    
									    $this->db->insert('employee_time_in', $esave_time);
									    
										$employee_time_in_id = $this->db->insert_id();
										
										// INSERT TIME INS												
										$over_tardiness = $tardiness + $overbreak_min;
											
										$add_logs = $this->employee->add_split_to_sched_blocks_timein_table($employee_time_in_id,$sched_blocks_id,$this->company_id,$this->emp_id,$work_schedule_id,
												date("Y-m-d",strtotime($new_employee_timein_date)), $reason, $new_time_in, $new_lunch_out, $new_lunch_in, $new_time_out, $over_tardiness, $undertime, $total_hours_split,
										    $get_total_hours, $flag_tu, $total_hours_split, $total_hours_worked, "1", $tardiness, $overbreak_min,$flag_payroll_correction);
										
										$split_time_in_id = $this->db->insert_id();
										
										if($split_time_in_id) {
											$split_timein_information = $this->timeins->split_timeins_info($split_time_in_id,$this->company_id);
											$emp_split_time_in_id = $split_timein_information->schedule_blocks_time_in_id;
										} else {
											$emp_split_time_in_id = "";
										}
									} else {											    
									    $esave_time = array(
									        "comp_id" => $this->company_id,
									        "emp_id" => $this->emp_id,
									        "work_schedule_id" => $work_schedule_id,
									        "date" => date("Y-m-d",strtotime($new_employee_timein_date)),
									        "time_in" => $new_time_in,
									        "time_out" => $new_time_out,
									        "corrected" => 'Yes',
									        "total_hours" => $total_hours_work_all_block,
									        "total_hours_required" => $total_hours_worked, //$timein_total_hours_required,
									        "tardiness_min" => $tardiness + $overbreak_min,
									        "undertime_min" => $undertime,
									        "flag_tardiness_undertime" => $flag_tu,
									        "change_log_date_filed" => date("Y-m-d H:i:s"),
									        "change_log_tardiness_min" => $tardiness + $overbreak_min,
									        "change_log_undertime_min" => $undertime,
									        "change_log_time_in" => $new_time_in,
									        "change_log_time_out" => $new_time_out,
									        "change_log_total_hours" => $total_hours_work_all_block,
									        "change_log_total_hours_required" => $total_hours_worked, //$timein_total_hours_required,
									        "flag_halfday" => 1,
									        "source" => "EP",
									        "late_min" => $tardiness,
									        "overbreak_min" => $overbreak_min,
									        "absent_min" => $for_absent_min,
									        "change_log_late_min" => $tardiness,
									        "change_log_overbreak_min" => $overbreak_min,
									        "change_log_absent_min" => $for_absent_min,
									        "split_status" => 'pending',
									        "time_in_status" => 'pending',
									        "flag_payroll_correction" => $flag_payroll_correction
									    );
									    
									    $this->db->insert('employee_time_in', $esave_time);
									    
									    $employee_time_in_id = $this->db->insert_id();
									    
									    // INSERT TIME INS
									    $over_tardiness = $tardiness + $overbreak_min;
									    
									    $add_logs = $this->employee->add_split_to_sched_blocks_timein_table($employee_time_in_id,$sched_blocks_id,$this->company_id,$this->emp_id,$work_schedule_id,
									        date("Y-m-d",strtotime($new_employee_timein_date)), $reason, $new_time_in, $new_lunch_out, $new_lunch_in, $new_time_out, $over_tardiness, $undertime, $total_hours_split,
									        $get_total_hours, $flag_tu, $total_hours_split, $total_hours_worked, "1", $tardiness, $overbreak_min,$flag_payroll_correction);
									    
									    $split_time_in_id = $this->db->insert_id();
									    
									    if($split_time_in_id) {
									        $split_timein_information = $this->timeins->split_timeins_info($split_time_in_id,$this->company_id);
									        
									        $emp_split_time_in_id = $split_timein_information->schedule_blocks_time_in_id;
									    } else {
									        $emp_split_time_in_id = "";
									    }
									}
								}
							}
						}
					#}
				}
			} elseif($check_work_type == "Open Shift"){
                $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                // UPDATE TIME INS
                $date_insert = array(
                    "comp_id"                           => $this->company_id,
                    "emp_id"                            => $this->emp_id,
                    "work_schedule_id"                  => $this->work_schedule_id,
                    "date"                              => date("Y-m-d",strtotime($new_employee_timein_date)),
                    "time_in_status"                    => 'pending',
                    "corrected"                         => 'Yes',
                    "reason"                            => $reason,
                    "time_in"                           => $new_time_in,
                    "time_out"                          => $new_time_out,
                    "tardiness_min"                     => 0,
                    "undertime_min"                     => 0,
                    "total_hours"                       => $get_total_hours,
                    "total_hours_required"              => $get_total_hours,
                    "change_log_date_filed"             => date("Y-m-d H:i:s"),
                    "change_log_tardiness_min"          => 0,
                    "change_log_undertime_min"          => 0,
                    "change_log_time_in"                => $new_time_in,
                    "change_log_time_out"               => $new_time_out,
                    "change_log_total_hours"            => $get_total_hours,
                    "change_log_total_hours_required"   => $get_total_hours,
                    "source"                            => "EP",
                    "late_min"                          => 0,
                    "overbreak_min"                     => 0,
                    "absent_min"                        => 0,
                    "change_log_late_min"               => 0,
                    "change_log_overbreak_min"          => 0,
                    "change_log_absent_min"             => 0,
                    "flag_open_shift"                   => "1",
                );
                
                $add_logs = $this->db->insert('employee_time_in', $date_insert);
                
            }
			
			$employee_timein = $this->employee->employee_timein($this->company_id, $this->emp_id);
			
			// save approval token
			$emp_time_id = $employee_timein;


			$void_v2 = $this->employee_v2->check_payroll_lock_closed($this->emp_id,$this->company_id,date("Y-m-d", strtotime($new_employee_timein_date)));
            $timein_info = $this->agm->timein_information($emp_time_id);
            
            if($void_v2 == "Closed" && $timein_info) {
                $this->save_to_timesheet_close_payroll($emp_time_id,$timein_info,"add log");
            }



			$str = 'abcdefghijk123456789';
			$shuffled = str_shuffle($str);
			
			$break_1_start_date_time = ($break_1_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_start_date_time));
			$break_1_end_date_time = ($break_1_end_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_end_date_time));
			$break_2_start_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_start_date_time));
			$break_2_end_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_end_date_time));
			
			$new_logs = array(
					'new_time_in' 	=> $new_time_in,
					'new_lunch_out'	=> $new_lunch_out,
					'new_lunch_in' 	=> $new_lunch_in,
					'new_time_out' 	=> $new_time_out,
			    
				    "break1_out"   	=> $break_1_start_date_time,
				    "break1_in"    	=> $break_1_end_date_time,
				    "break2_out"   	=> $break_2_start_date_time,
				    "break2_in"    	=> $break_2_end_date_time,
			    
					'reason'		=> $reason
			);
			
			// generate token level
			$str2 = 'ABCDEFG1234567890';
			$shuffled2 = str_shuffle($str2);
			$psa_id = $this->session->userdata('psa_id');
			
			#$approver_id = $this->employee->get_approver_name_timein($this->emp_id,$this->company_id)->add_logs_approval_grp;
			$approver_id = $this->employee->get_approver_name_timesheet($this->emp_id,$this->company_id,"Add Timesheet")->add_logs_approval_grp;
			if($approver_id == "" || $approver_id == 0) {
			    // Employee with no approver will use default workflow approval
			    // add_workflow_approval_default_group($this->company_id,$this->account_id); // create default if dont have any
			    $approver_id = get_app_default_approver($this->company_id,"Add Timesheet")->approval_groups_via_groups_id;
			}
			
			if($check_work_type_form == "Workshift"){
			    $timein_info = $this->agm->split_timein_information($emp_split_time_in_id);
			} else {
				$timein_info = $this->agm->timein_information($emp_time_id);
			}
			
			$fullname = ucfirst($timein_info->first_name)." ".ucfirst($timein_info->last_name);
			$hours_notification = get_notify_settings($approver_id, $this->company_id);
			$get_approval_settings_disable_status = $this->employee->get_approval_settings_disable_status($this->company_id);
			
			if($approver_id) {
				if(is_workflow_enabled($this->company_id)){
				    $add_logs_approver = $this->agm->get_approver_name_timein_add_logs($this->emp_id,$this->company_id);
				    
					if($add_logs_approver){
						if($hours_notification){
							$new_level = 1;
							$lflag = 0;
								
                            // with leveling
                            $new_todo_appr_id = "";
							foreach ($add_logs_approver as $ala){
								$appovers_id = ($ala->emp_id) ? $ala->emp_id : "-99{$this->company_id}";
								$get_approval_group_via_groups_owner = $this->agm->get_approval_group_via_groups_owner($ala->approval_process_id, $ala->company_id, $ala->approval_groups_via_groups_id, $appovers_id);
								
								if($get_approval_group_via_groups_owner->emp_id == "-99{$this->company_id}"){
									$owner_approver = get_approver_owner_info($this->company_id);
									$appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
									$appr_account_id = $owner_approver->account_id;
									$appr_email = $owner_approver->email;
									$appr_id = "-99{$this->company_id}";
								} else {
									$appr_name = ucwords($ala->first_name." ".$ala->last_name);
									$appr_account_id = $ala->account_id;
									$appr_email = $ala->email;
									$appr_id = $ala->emp_id;
								}
								
								if($ala->level == $new_level){
                                    $new_todo_appr_id .= $appr_id.',';
									// send with link
									if($check_work_type_form == "Workshift"){
									    $this->send_change_add_logss_notifcation("add", $new_logs, $shuffled, $emp_split_time_in_id, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2, $appr_id, $tardiness_rule_migrated_v3);
									} else {
									    $this->send_change_add_logss_notifcation("add", $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2, $appr_id, $tardiness_rule_migrated_v3);
									}
									
									if($hours_notification->sms_notification == "yes"){
										$url = base_url()."approval/time_in/index/".$shuffled."/".$shuffled2."/1".$appr_id."0/".$employee_timein;
										$sms_message = "Click {$url} to approve {$fullname}'s attendance log.";
										send_this_sms_global($this->company_id, $appr_account_id,$sms_message,$psa_id,false);
									}
									
									if($hours_notification->message_board_notification == "yes"){
										$token = $this->timeins->get_token($timein_info->approval_time_in_id, $this->company_id, $timein_info->emp_id);
										$url = base_url()."approval/time_in/index/".$shuffled."/".$shuffled2."/1".$appr_id."0/".$employee_timein;
										$next_appr_notif_message = "A New Attendance Log has been filed by {$fullname} and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
										send_to_message_board($psa_id, $appr_id, $this->emp_id, $this->company_id, $next_appr_notif_message, "system", "warning");
									}
									
									$lflag = 1;
								
								}else{
									// send without link
									if($check_work_type_form == "Workshift"){
									    $this->send_change_add_logss_notifcation("add", $new_logs, $shuffled, $split_time_in_id, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "" , "", "","", $tardiness_rule_migrated_v3);
									} else {
									    $this->send_change_add_logss_notifcation("add", $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "" , "", "","",$tardiness_rule_migrated_v3);
									}
									
									if($hours_notification->sms_notification == "yes"){
										$sms_message = "A New Attendance Log has been filed by {$fullname}.";
										send_this_sms_global($this->company_id, $appr_account_id,$sms_message,$psa_id,false);
									}
									
									if($hours_notification->message_board_notification == "yes"){
										$next_appr_notif_message = "A New Attendance Log has been filed by {$fullname}.";
										send_to_message_board($psa_id, $appr_id, $this->emp_id,$this->company_id, $next_appr_notif_message, "system", "warning");
									}
									//$this->send_leave_notifcation($emp_time_id, $this->company_id, $leave_info->emp_id, $appr_email, $appr_name, $current_approver, "", "", "");
										
								}
							}
								
							################################ notify payroll admin start ################################
							if($hours_notification->notify_payroll_admin == "yes"){
							    // HRs
							    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($psa_id);
							    if($payroll_admin_hr){
							        foreach ($payroll_admin_hr as $pahr){
							            $pahr_email = $pahr->email;
							            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);
							            
							            if($check_work_type_form == "Workshift"){
							                $this->send_change_add_logss_notifcation("add", $new_logs, $shuffled, $split_time_in_id, $this->company_id,$this->emp_id, $pahr_email, $pahr_name, "", "" , "", "", "", $tardiness_rule_migrated_v3, "Yes");
							            } else {
							                $this->send_change_add_logss_notifcation("add", $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $pahr_email, $pahr_name, "", "" , "", "", "", $tardiness_rule_migrated_v3, "Yes");
							            }
							        }
							    }
							    
							    // Owner
							    $pa_owner = get_approver_owner_info($this->company_id);
							    if($pa_owner){
							        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
							        $pa_owner_email = $pa_owner->email;
							        $pa_owner_account_id = $pa_owner->account_id;
							        
							        if($check_work_type_form == "Workshift"){
							            $this->send_change_add_logss_notifcation("add", $new_logs, $shuffled, $split_time_in_id, $this->company_id,$this->emp_id, $pa_owner_email, $pa_owner_name, "", "" , "", "", "", $tardiness_rule_migrated_v3, "Yes");
							        } else {
							            $this->send_change_add_logss_notifcation("add", $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $pa_owner_email, $pa_owner_name, "", "" , "", "", "", $tardiness_rule_migrated_v3, "Yes");
							        }
							    }
							}
							################################ notify payroll admin end ################################
							
							if($check_work_type == "Workshift"){
								$save_token = array(
										"time_in_id"		=> $emp_time_id,
										"split_time_in_id"	=> $emp_split_time_in_id,
										"token"				=> $shuffled,
										"comp_id"			=> $this->company_id,
										"emp_id"			=> $this->emp_id,
										"approver_id"		=> $approver_id,
										"flag_add_logs" 	=> 1,
										"level"				=> $new_level,
										"token_level"		=> $shuffled2
								);
								
								$save_token_q = $this->db->insert("approval_time_in",$save_token);
								$id = $this->db->insert_id();
								$timein_update = array('approval_time_in_id' => $id);
								$this->db->where('schedule_blocks_time_in_id', $emp_split_time_in_id);
								$this->db->update('schedule_blocks_time_in',$timein_update);
								$appr_err="";
							} else {
								$save_token = array(
										"time_in_id"	=> $emp_time_id,
										"token"			=> $shuffled,
										"comp_id"		=> $this->company_id,
										"emp_id"		=> $this->emp_id,
										"approver_id"	=> $approver_id,
										"flag_add_logs" => 1,
										"level"			=> $new_level,
										"token_level"	=> $shuffled2
								);
								
								$save_token_q = $this->db->insert("approval_time_in",$save_token);
								$id = $this->db->insert_id();
								$timein_update = array('approval_time_in_id' => $id);
								$this->db->where('employee_time_in_id', $emp_time_id);
								$this->db->update('employee_time_in',$timein_update);
								$appr_err="";
                            }
                            
                            if ($check_if_enable_working_on_restday || $get_rest_day_settings == "yes") {
                                $module_for_new_todo = "rd_ra";
                            } elseif ($check_if_holiday_approval) {
                                $module_for_new_todo = "holiday";
                            } else {
                                $module_for_new_todo = "hours";
                            }
                            
                            insert_todo_data($this->company_id,date("Y-m-d",strtotime($new_employee_timein_date)),$this->emp_id,$id,1,$new_todo_appr_id,$approver_id,$this->work_schedule_id,$module_for_new_todo);
						}else{
							if($check_work_type == "Workshift"){
								$save_token = array(
										"time_in_id"			=> $emp_time_id,
										"split_time_in_id"		=> $emp_split_time_in_id,
										"token"					=> $shuffled,
										"comp_id"				=> $this->company_id,
										"emp_id"				=> $this->emp_id,
										"approver_id"			=> $approver_id,
										"flag_add_logs" 		=> 1,
										"level"					=> 1,
										"token_level"			=> $shuffled2
											
								);
								
								$save_token_q = $this->db->insert("approval_time_in",$save_token);
								$id = $this->db->insert_id();
								
								$timein_update = array('approval_time_in_id' => $id);
								$this->db->where('schedule_blocks_time_in_id', $emp_split_time_in_id);
								$this->db->update('schedule_blocks_time_in',$timein_update);
							} else {
								$save_token = array(
										"time_in_id"			=> $emp_time_id,
										"split_time_in_id"		=> $emp_split_time_in_id,
										"token"					=> $shuffled,
										"comp_id"				=> $this->company_id,
										"emp_id"				=> $this->emp_id,
										"approver_id"			=> $approver_id,
										"flag_add_logs" 		=> 1,
										"level"					=> 1,
										"token_level"			=> $shuffled2
								);
								
								$save_token_q = $this->db->insert("approval_time_in",$save_token);
								$id = $this->db->insert_id();
								$timein_update = array('approval_time_in_id' => $id);
								$this->db->where('employee_time_in_id', $emp_time_id);
								$this->db->update('employee_time_in', $timein_update);
							}
							
						}
							
						$result = array(
								'error'=>false,
								'hours_error'=>$appr_err,
								'approver_error'=> ""
						);
						echo json_encode($result);
						return false;
						
					}else{
						$new_level = 1;
							
						if($check_work_type == "Workshift"){
							$save_token = array(
									"time_in_id"		=> $emp_time_id,
									"split_time_in_id"	=> $emp_split_time_in_id,
									"token"				=> $shuffled,
									"comp_id"			=> $this->company_id,
									"emp_id"			=> $this->emp_id,
									"approver_id"		=> $approver_id,
									"flag_add_logs" 	=> 1,
									"level"				=> $new_level,
									"token_level"		=> $shuffled2
									
							);
							$save_token_q = $this->db->insert("approval_time_in",$save_token);
							$id = $this->db->insert_id();
							$timein_update = array('approval_time_in_id' => $id);
							$this->db->where('schedule_blocks_time_in_id', $emp_split_time_in_id);
							$this->db->update('schedule_blocks_time_in',$timein_update);
							
							$result = array(
								'error'=>false,
								'approver_error' => ""
							);
						} else {
							$save_token = array(
									"time_in_id"	=> $emp_time_id,
									"token"			=> $shuffled,
									"comp_id"		=> $this->company_id,
									"emp_id"		=> $this->emp_id,
									"approver_id"	=> $approver_id,
									"flag_add_logs" => 1,
									"level"			=> $new_level,
									"token_level"	=> $shuffled2
							);
								
							$save_token_q = $this->db->insert("approval_time_in",$save_token);
							$id = $this->db->insert_id();
							$timein_update = array('approval_time_in_id'=>$id);
							$this->db->where('employee_time_in_id', $emp_time_id);
							$this->db->update('employee_time_in',$timein_update);
							
							$result = array(
								'error'=>false,
								'approver_error' => ""
							);
                        }
                        
                        if ($check_if_enable_working_on_restday || $get_rest_day_settings == "yes") {
                            $module_for_new_todo = "rd_ra";
                        } elseif ($check_if_holiday_approval) {
                            $module_for_new_todo = "holiday";
                        } else {
                            $module_for_new_todo = "hours";
                        }
                        
                        insert_todo_data($this->company_id,date("Y-m-d",strtotime($new_employee_timein_date)),$this->emp_id,$id,1,"-99".$this->company_id,$approver_id,$this->work_schedule_id,$module_for_new_todo);
						
						echo json_encode($result);
						return false;
					}
				} else {
					if($get_approval_settings_disable_status->status == "Inactive") {
						if($get_approval_settings_disable_status->disabled_application_status == 'approve') {
							$status = "approved";
						} else {
							$status = $get_approval_settings_disable_status->disabled_application_status;
						}
						
						if($check_work_type == "Workshift"){
							$fields = array(
									"time_in_status" => $status
							);
							$where = array(
									"schedule_blocks_time_in_id" => $emp_split_time_in_id,
									"comp_id" => $this->company_id,
									"emp_id" => $this->emp_id
							);
								
							eupdate("schedule_blocks_time_in",$fields,$where);
								
							$result = array(
									'error'				=> false,
									'approver_error'	=> ""
							);
								
							echo json_encode($result);
							return false;
						} else {								
							$fields = array(
									"time_in_status" => $status
							);
							$where = array(
									"employee_time_in_id" => $emp_time_id,
									"comp_id" => $this->company_id,
									"emp_id" => $this->emp_id
							);
								
							eupdate("employee_time_in",$fields,$where);
								
							$result = array(
									'error'				=> false,
									'approver_error'	=> ""
							);
								
							echo json_encode($result);
							return false;
						}
					}
				}
			
			}else{
			    // gi delete ni ky g.pausab nsd ni donna, wala na dapat auto approve.. (Employee with no approver will use default workflow approval)
				/*if($check_work_type == "Workshift"){							
					$fields = array(
							"time_in_status" 	=> "pending",
							"corrected"			=> "Yes",
					
					);
					$where = array(
							"approval_time_in_id"	=> $emp_split_time_in_id,
							"comp_id"				=> $this->company_id
					);
					$this->timeins->update_field("schedule_blocks_time_in",$fields,$where);
				} else {
					$fields = array(
							"time_in_status" => "approved"
					);
					$where = array(
							"employee_time_in_id" => $emp_time_id,
							"comp_id" => $this->company_id,
							"emp_id" => $this->emp_id
					);
						
					eupdate("employee_time_in",$fields,$where);
				}
				
				$result = array(
						'error'=>false,
						'approver_error' =>"",
						'hours_error'=>""
				);
				
				echo json_encode($result);
				return false;*/
				
			}
		}else{

            $result = array(
                'error' => true,
                'etime_out_date' => "",
                'error_msg' => validation_errors(),
                'time_error' => ""
            );
            echo json_encode($result);
            return false;
		}
	
	}

	public function change_log()
	{        

        $change_log_date = $this->input->post('emp_schedule_date');

		$result = array(
            'error'=>true,
			'ereason'=> "Application is temporarily unavailable on mobile app. You may file your application on the browser instead.",
			'error_msg'=> "Application is temporarily unavailable on mobile app. You may file your application on the browser instead.",
			'time_error' => ""
		);
        echo json_encode($result);
        return false;

        // check if the application is lock for filing
        $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id, "change log",$this->emp_id, date("Y-m-d", strtotime($change_log_date)));
        // $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id);
        if($get_lock_payroll_process_settings) {
            if($get_lock_payroll_process_settings->suspend_all_application == "yes") {                
                $result = array(
                    'error'=>true,
                    'ereason'=> $get_lock_payroll_process_settings->application_error,
                    'error_msg'=> "Filing of attendance adjustment is currently suspended by your admin.  You may try again at a different time.",
                    'time_error' => ""
                );
                echo json_encode($result);
                return false;
            } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                $result = array(
                    'error'=>true,
                    'ereason'=> $get_lock_payroll_process_settings->application_error,
                    'error_msg'=> "Filing of attendance adjustment is currently suspended by your admin.  You may try again at a different time.",
                    'time_error' => ""
                );
                echo json_encode($result);
                return false;
            } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                $result = array(
                    'error'=>true,
                    'ereason'=> $get_lock_payroll_process_settings->application_error,
                    'error_msg'=> "Filing of attendance adjustment is currently suspended by your admin.  You may try again at a different time.",
                    'time_error' => ""
                );
                echo json_encode($result);
                return false;
            }
        }

        $employee_timein = $this->input->post('employee_timein');
        
        $time_in_inp = $this->input->post('time_in');
        $time_out_inp = $this->input->post('time_out');
        $lunch_in_inp = $this->input->post('lunch_in');
        $lunch_out_inp = $this->input->post('lunch_out');
        $break1_in_inp = $this->input->post('first_break_in');
        $break1_out_inp = $this->input->post('first_break_out');
        $break2_in_inp = $this->input->post('second_break_in');
        $break2_out_inp = $this->input->post('second_break_out');

        $schedule_blocks_id = $this->input->post('schedule_blocks_id');
        $shift_date = date('Y-m-d', strtotime($this->input->post('shift_date')));
        $reason = $this->input->post('reason');
		
		$this->form_validation->set_rules("employee_timein", 'Employee Time In ID', 'trim|required|xss_clean');
		$this->form_validation->set_rules("time_in", 'Time In', 'trim|required|xss_clean');
		$this->form_validation->set_rules("time_out", 'Time Out', 'trim|xss_clean');		
		$this->form_validation->set_rules("emp_schedule_date", 'Change Log Date', 'trim|required|xss_clean');		
		$this->form_validation->set_rules("reason", 'Reason', 'trim|required|xss_clean');
		
		if ($this->form_validation->run()==true){

            $emp_schedule_date = ($change_log_date) ? date('Y-m-d', strtotime($change_log_date)) : NULL;
            $employee_timein_date = ($time_in_inp) ? date('Y-m-d', strtotime($time_in_inp)) : NULL;
            
            $new_time_in = $time_in = ($time_in_inp) ? date('Y-m-d H:i:s', strtotime($time_in_inp)) : NULL;
            $new_lunch_out = ($lunch_out_inp) ? date('Y-m-d H:i:s', strtotime($lunch_out_inp)) : NULL;
            $new_lunch_in = ($lunch_in_inp) ? date('Y-m-d H:i:s', strtotime($lunch_in_inp)) : NULL;
            $new_time_out = ($time_out_inp) ? date('Y-m-d H:i:s', strtotime($time_out_inp)) : NULL;

            $break_1_start_date_time = ($break1_in_inp) ? date('Y-m-d H:i:s', strtotime($break1_in_inp)) : NULL;
            $break_2_start_date_time = ($break2_in_inp) ? date('Y-m-d H:i:s', strtotime($break2_in_inp)) : NULL;

            $break_1_end_date_time = ($break1_out_inp) ? date('Y-m-d H:i:s', strtotime($break1_out_inp)) : NULL;
            $break_2_end_date_time = ($break2_out_inp) ? date('Y-m-d H:i:s', strtotime($break2_out_inp)) : NULL;

            if (!$employee_timein || !$emp_schedule_date || !$reason || !$new_time_in || !$new_time_out) {                
                echo json_encode(array("error" => 1,"result" => 1,"error_msg"=>"Invalid time log details.", "msgs" => 'missing input'));
			    return false;
            }

            
            $flag_halfday = $this->input->post("flag_halfday");
            if($flag_halfday == 1){
                $new_lunch_out = NULL; 
                $new_lunch_in = NULL;
            }
            
            delete_approve_timein($employee_timein);
                    
            $check_if_excess_logs = $this->employee->check_if_excess_logs($this->emp_id,$this->company_id,$employee_timein);
                
            $excess_logs = false;
            if($check_if_excess_logs) {
                $excess_logs = true;
            }


			// if one of the approver is inactive the approver group will automatically change to default (owner)
			change_approver_to_default($this->emp_id,$this->company_id,"attendance_adjustment_approval_grp",$this->account_id);
			
            $emp_no = $this->employee->check_emp_no($this->emp_id,$this->company_id);
			
			//UPDATE EMPLOYEE LOGS
			//GETS WORK_SCHEDULE_ID
			$shift_date = $employee_timein_date;
			$check_timein = $this->employee->check_timein($this->emp_id,$this->company_id,$employee_timein);
            $check_rest_day = $this->elm->check_rest_day(date("l",strtotime($emp_schedule_date)),$this->work_schedule_id,$this->company_id);
            
            if ($check_timein) {
                $this->work_schedule_id = $check_timein->work_schedule_id;
            } else {
                $this->work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$emp_schedule_date);
            }
            
            $check_work_type = $this->employee->work_schedule_type($this->work_schedule_id, $this->company_id);
			$check_holiday = $this->employee->get_holiday_date($emp_schedule_date,$this->emp_id,$this->company_id);
            
            // this is for RD/RA
            $flag_rd_include = "yes"; // field : flag_rd_include
            $rest_day_r_a = "no";
            $check_if_enable_working_on_restday = check_if_enable_working_on_restday($this->company_id,$this->work_schedule_id);
            
			$get_rest_day_settings = "no";
            if ($check_rest_day) {
                $get_rest_day_settings = get_rest_day_settings($this->company_id);
            }
            
            if($check_if_enable_working_on_restday || $get_rest_day_settings == "yes") {
                $flag_rd_include = "no";
                $rest_day_r_a = "yes";
            }
            
            // this is for holiday approval
            $flag_holiday_include = "yes";
            $holiday_approve = "no";
            
            if ($check_holiday) {
                $check_if_holiday_approval = holiday_approval_settings($this->company_id);
            } else {
                $check_if_holiday_approval = false;
            }
            
            if($check_if_holiday_approval) {
                $flag_holiday_include = "no";
                $holiday_approve = "yes";
            }

			if(check_if_enable_breaks_on_holiday($this->company_id,$this->work_schedule_id)) {
			    $check_holiday = false;
			}
			
			$late_min = $this->elm->late_min($this->company_id,$emp_schedule_date,$this->emp_id,$this->work_schedule_id,$emp_no,$new_time_in);
			$overbreak_min = $this->elm->overbreak_min($this->company_id,$employee_timein_date,$this->emp_id,$this->work_schedule_id,$new_lunch_out,$emp_no,$new_lunch_in);
			$get_employee_time_in = $this->employee->get_employee_time_in($employee_timein,$this->emp_id,$this->company_id);
			$absent_min = $this->elm->calculate_attendance($this->company_id,$new_time_in,$new_time_out);
			$your_total_hours = $this->elm->get_attendance_total_work_hours($this->emp_id,$this->company_id,$emp_schedule_date,$this->work_schedule_id);
			$fritz_tardiness = $late_min + $overbreak_min;
			
			$new_total_hours_worked = 0;
			$new_tardiness = 0;
			$new_undertime = 0;
			$your_new_total_hours = 0;
			
			if($get_employee_time_in) {
				if($get_employee_time_in->source != null) {
					$new_source = "last_source";
				} else {
					$new_source = "source";
				}
			} else {
				$new_source = "source";
			}
			
			$tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($this->company_id,$this->work_schedule_id);
			$get_schedule_settings = get_schedule_settings_by_workschedule_id($this->work_schedule_id,$this->company_id,date("l", strtotime($emp_schedule_date)));
			
			if($tardiness_rule_migrated_v3) {
			    // barrack code -- so need this param to calculate everything :D
			    $emp_ids                           = array($this->emp_id); // emp id
			    $min_range                         = date('Y-m-d', strtotime($emp_schedule_date)); //date
			    $max_range                         = date('Y-m-d', strtotime($emp_schedule_date)); //date
			    $min_range                         = date("Y-m-d",strtotime($min_range. " -1 day"));
			    $max_range                         = date("Y-m-d",strtotime($max_range. " +1 day"));
			    
			    // parent functions to be use for param
			    $split_arr                         = array();
			    $get_employee_payroll_information  = $this->importv2->get_employee_payroll_information($this->company_id,$emp_ids);
			    $emp_work_schedule_ess             = $this->importv2->emp_work_schedule_ess($this->company_id,$emp_ids);
			    $emp_work_schedule_epi             = $this->importv2->emp_work_schedule_epi($this->company_id,$emp_ids);
			    $list_of_blocks                    = $this->importv2->list_of_blocks($this->company_id,$emp_ids);
			    $get_all_schedule_blocks           = $this->importv2->get_all_schedule_blocks($this->company_id);
			    $get_all_regular_schedule          = $this->importv2->get_all_regular_schedule($this->company_id);
			    $get_work_schedule_flex            = $this->importv2->get_work_schedule_flex($this->company_id);
			    $company_holiday                   = $this->importv2->company_holiday($this->company_id);
			    $get_work_schedule                 = $this->importv2->get_work_schedule($this->company_id);
			    $get_employee_leave_application    = $this->importv2->get_employee_leave_application($this->company_id,$emp_ids);
			    $get_tardiness_settings            = $this->importv2->get_tardiness_settings($this->company_id);
			    $get_all_employee_timein           = $this->employee->get_all_employee_timein($this->company_id,$emp_ids,$min_range,$max_range);
			    $get_all_schedule_blocks_time_in   = $this->importv2->get_all_schedule_blocks_time_in($this->company_id,$emp_ids,$min_range,$max_range);
			    $attendance_hours                  = is_attendance_active($this->company_id);
			    $get_tardiness_settingsv2          = $this->importv2->get_tardiness_settingsv2($this->company_id);
			    $tardiness_rounding                = $this->importv2->tardiness_rounding($this->company_id);
			    
			    $get_shift_restriction_schedules   = $this->importv2->get_shift_restriction_schedules($this->company_id);
                $get_nigtdiff_rule                 = $this->importv2->get_nigtdiff_rule($this->company_id);
                    
			    $import_add_logsv2 = $this->importv2->import_add_logsv2($this->company_id, $this->emp_id, $reason, $new_time_in,$new_lunch_out,$new_lunch_in,date("Y-m-d H:i:s", strtotime($break_1_start_date_time)),date("Y-m-d H:i:s", strtotime($break_1_end_date_time)),date("Y-m-d H:i:s", strtotime($break_2_start_date_time)),date("Y-m-d H:i:s", strtotime($break_2_end_date_time)),$new_time_out, 0, $this->work_schedule_id,0,$split_arr,false,"updated","",$emp_schedule_date,$employee_timein,$get_employee_payroll_information,$get_tardiness_settings,$company_holiday,"","",$get_all_regular_schedule,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$attendance_hours,$get_work_schedule,$get_employee_leave_application,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_tardiness_settingsv2,$tardiness_rounding);
			    $calculated_data = $import_add_logsv2['fields'];
			    
			} else {
			    $calculated_data = false;
			}
			
			$void = $this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($emp_schedule_date)));
			$void_v2 = $this->employee_v2->check_payroll_lock_closed($this->emp_id,$this->company_id,date("Y-m-d", strtotime($emp_schedule_date)));
			
			if($void == "Waiting for approval"){
			    $flag_payroll_correction = "yes";
			    $disabled_btn = true;
			} elseif ($void == "Closed") {
			    $flag_payroll_correction = "yes";
			} else {
			    $flag_payroll_correction = "no";
			}
		
			//CHECK IF WORK SCHEDULE IS UNIFORM WORKING DAY / FLEXIBLE / SPLIT SCHEDULE
			if($check_work_type == "Uniform Working Days"){
				
				//CHECKS IF RESTDAY OR NOT
				if($check_rest_day || $check_holiday || $excess_logs == true){
					$tardiness_min = 0;
                    $undertime_min = 0;

                    // update total hours and total hours required rest day
                    $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                        

                    $late_min = 0;
                    $overbreak_min = 0;
                    $absent_min = 0;
                    $current_date_nsd = 0;
                    $next_date_nsd = 0;
						
					
					if($tardiness_rule_migrated_v3) {
					    $get_total_hours = $calculated_data["total_hours_required"];
					    $tardiness_min = $calculated_data["tardiness_min"];
					    $undertime_min = $calculated_data["undertime_min"];
					    $late_min = $calculated_data["late_min"];
					    $overbreak_min = $calculated_data["overbreak_min"];
					    $absent_min = $calculated_data["absent_min"];
					    $current_date_nsd = $calculated_data["current_date_nsd"];
                        $next_date_nsd = $calculated_data["next_date_nsd"];
					}

					if($check_rest_day) {
                        $tardiness_rule_migrated_v3 = false;
                        $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                    }
                    
                    if ($this->work_schedule_id == "-1") {
                        $flag_rd_include = "no";
                        $rest_day_r_a = "yes";
                    }
					
					// UPDATE TIME INS
					$where_tot = array(
                        "comp_id" => $this->company_id,
                        "emp_id" => $this->emp_id,
                        "employee_time_in_id" => $employee_timein
					);
					$this->db->where($where_tot);
						
					$data_update = array(
						"work_schedule_id"                 => $this->work_schedule_id,
						"comp_id"                          => $this->company_id,
						"emp_id"                           => $this->emp_id,
						"date"                             => $emp_schedule_date,
						"time_in_status"                   => 'pending',
						"corrected"                        => 'Yes',
						"reason"                           => $reason,
					    "change_log_date_filed"            => date("Y-m-d H:i:s"),
					    "change_log_tardiness_min"         => $tardiness_min,
					    "change_log_undertime_min"         => $undertime_min,
						"change_log_time_in"               => $new_time_in,
						"change_log_time_out"              => $new_time_out,
						"change_log_total_hours"           => $get_total_hours,
						"change_log_total_hours_required"  => $get_total_hours,
						$new_source                        => "Adjusted",
					    "change_log_late_min"              => $late_min,
					    "change_log_overbreak_min"         => $overbreak_min,
					    "change_log_absent_min"            => $absent_min,
                        "flag_payroll_correction"          => $flag_payroll_correction,
                        "rest_day_r_a"                     => $rest_day_r_a,
                        "flag_rd_include"                  => $flag_rd_include,
                        "holiday_approve"                  => $holiday_approve,
                        "flag_holiday_include"             => $flag_holiday_include,
                        "current_date_nsd"                 => $current_date_nsd,
                        "next_date_nsd"                    => $next_date_nsd
					);
						
					$update_change_logs = $this->db->update('employee_time_in', $data_update);
					
					if($update_change_logs) {
						$new_total_hours_worked = $get_total_hours;
						$new_tardiness = $tardiness;
						$new_undertime = $undertime;
						$your_new_total_hours = $your_total_hours;
					} else {
						$new_total_hours_worked = 0;
						$new_tardiness = 0;
						$new_undertime = 0;
					}
					
				}elseif($new_lunch_out == NULL && $new_lunch_in == NULL && $flag_halfday == 1){
					//IF HALFDAY 
					
					#$flag_halfday = 0;
					$flag_undertime = 0;
					$tardiness = 0;
					$undertime = 0;
					$total_hours = 0;
					$total_hours_required = 0;
					
					$check_hours_flexible = $this->elm->check_hours_flex($this->company_id,$this->work_schedule_id);
					$check_breaktime = $this->elm->check_breaktime($this->company_id,$this->work_schedule_id);
				
					$workday = date("l",strtotime($shift_date));
					
					$workday_settings_start_time = $this->elm->check_workday_settings_start_time($workday,$this->work_schedule_id,$this->company_id);
					$workday_settings_end_time = $this->elm->check_workday_settings_end_time($workday,$this->work_schedule_id,$this->company_id);
					//checks if latest timein is allowed
					
					if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
					
						// for night shift time in and time out value for working day
						$check_bet_timein = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
						$check_bet_timeout = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$workday_settings_end_time;
					}else{
					
						// for day shift time in and time out value for working day
						$check_bet_timein = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
						$check_bet_timeout = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_end_time;
					}
					// check between date time in to date time out
					$add_oneday_timein = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$workday_settings_start_time;
						
					
					/* EMPLOYEE WORK SCHEDULE */
					$wd_start = $this->elm->get_workday_sched_start($this->company_id,$this->work_schedule_id);
					$wd_end = $this->elm->get_end_time($this->company_id,$this->work_schedule_id);
					
					if($check_breaktime != FALSE){
						$total_work_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($time_in)),$this->work_schedule_id);
						$new_latest_timein_allowed = $this->employee->new_latest_timein_allowed($this->emp_id,$this->company_id,$this->work_schedule_id,"work_schedule");
						$new_st = $this->employee->for_leave_hoursworked_work_start_time_ws($this->emp_id,$this->company_id,$this->work_schedule_id);
						if($new_latest_timein_allowed){ // if latest time in is true
							if(strtotime($time_in) < strtotime($new_st)){
								$new_work_start_time = $new_st;
							}elseif(strtotime($new_st) <= strtotime($time_in) && strtotime($time_in) <= strtotime($new_latest_timein_allowed)){
								$new_work_start_time = $concat_start_datetime;
							}elseif(strtotime($time_in) > strtotime($new_latest_timein_allowed)){
								$new_work_start_time = $new_latest_timein_allowed;
							}
						}
						$end_time = date('H:i:s', strtotime($new_work_start_time.' +'.$total_work_hours.' hours'));
						$check_time = (strtotime($end_time) - strtotime($new_work_start_time) ) / 3600;
						$check2 = $check_time / 2;
						$check2 = round($check2, 0, PHP_ROUND_HALF_DOWN);
						
						//$hd_check = strtotime($wd_start) + $check_time;
						$hd_check = date('H:i:s', strtotime($new_work_start_time.' +'.$check2.' hours'));
						$hd_check = strtotime($wd_start) + $check_time;
						
						$b_st = $check_breaktime->work_start_time;
						$b_et = $check_breaktime->work_end_time;
						$now_date = date("Y-m-d H:i:s",strtotime($new_time_out));
						$now_time = date("H:i:s",strtotime($new_time_out));
						
						// FLAG
						if((strtotime($check_bet_timeout) >= strtotime($new_time_in) && strtotime($new_time_in) <= strtotime($add_oneday_timein)) && (date("A",strtotime($wd_start)) != "PM" && date("A",strtotime($wd_end)) != "AM")){ 
							// FOR DAY SHIFT
						
							if(strtotime($hd_check) <= strtotime(date("H:i:s",strtotime($new_time_in)))){
								$flag_halfday = 1; // FOR HALFDAY AFTERNOON
								
							}elseif(strtotime(date("H:i:s",strtotime($new_time_out))) <= strtotime($hd_check)){
								$flag_halfday = 2; // FOR HALFDAY MORNING
								
							}
						}else{
							// FOR NIGHT SHIFT
							$new_date_timein = date("Y-m-d H:i:s",strtotime($check_bet_timein." -1 day"));
							$new_date_timeout = date("Y-m-d",strtotime($new_time_in))." ".date("H:i:s",strtotime($add_oneday_timein));
							//if(strtotime($new_date_timein) <= strtotime($new_time_in) && strtotime($new_time_in) <= strtotime($new_date_timeout)) $flag_halfday = 1;
							
							if(date("A",strtotime($new_time_in)) == "AM"){
								if(strtotime(date("Y-m-d",strtotime($new_time_in))." ".$hd_check) <= strtotime($new_date_timeout) && strtotime(date("Y-m-d",strtotime($new_time_in))) <= strtotime($new_date_timeout)){
									$flag_halfday = 1;	
								}
							}else{
								if(strtotime(date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$hd_check) >= strtotime($new_time_in) && strtotime(date("Y-m-d",strtotime($new_time_in))) <= strtotime($new_date_timeout)){
									$flag_halfday = 2;
								}
							}
						}
					}else{
						/* FOR UNIFORM WORKING DAYS AND  ZERO BREAK TIME */
						$flag_halfday = 3;
					}
					
					if($flag_halfday == 1){

						if(date("A",strtotime($wd_start)) != "PM" && date("A",strtotime($wd_end)) != "AM"){ // DAY SHIFT TRAPPING
								
							$tardiness_b = (strtotime($b_et) < strtotime(date("H:i:s",strtotime($new_time_in)))) ? (strtotime(date("H:i:s",strtotime($new_time_in))) - strtotime($b_et)) / 60 : 0 ; // tardiness for time in breaktime
						
							$tardiness = $tardiness_b;
							$tardiness = 0;
							// GET END TIME FOR TIME OUT
							$get_end_time = $this->elm->get_end_time($this->company_id,$this->work_schedule_id);
							
								
							// FOR UNDERTIME
							if($get_end_time != FALSE){
								if(strtotime($now_time) < strtotime($get_end_time)) $undertime = (strtotime($get_end_time) - strtotime($now_time)) / 60;
							}else{
								$result = array(
									'error' => true,
									'error_msg' => "Payroll set up for break time is empty."
								);

								echo json_encode($result);
								return false;
							}
								
						}else{ // NIGHT SHIFT TRAPPING
							$new_end_date = date("Y-m-d",strtotime(date("Y-m-d",strtotime($new_time_out))." -1 day"))." ".$b_et;
							$now_date = date("Y-m-d H:i:s",strtotime($new_time_out));
								
							$new_breaktime_start = date("Y-m-d",strtotime($new_time_out))." ".$b_st;
								
							if($check_hours_flexible != FALSE){
								$tardiness_a = (strtotime($new_breaktime_start) - strtotime(date("Y-m-d",strtotime($new_date_timein))." ".$check_hours_flexible)) / 60; // time start - breaktime end time (tardiness for start time)
							}else{
								$tardiness_a = (strtotime($new_breaktime_start) - strtotime($new_date_timein)) / 60; // time start - breaktime end time (tardiness for start time)
							}
								
							$tardiness_b = (strtotime($b_et) < strtotime(date("H:i:s",strtotime($new_time_in)))) ? (strtotime(date("H:i:s",strtotime($new_time_in))) - strtotime($b_et)) / 60 : 0 ; // tardiness for time in breaktime
								
							$tardiness = $tardiness_b;
							$tardiness = 0;
							// GET END TIME FOR TIME OUT
								$get_end_time = $this->elm->get_end_time($this->company_id,$this->work_schedule_id);
							
								
							// FOR UNDERTIME
							if($get_end_time != FALSE){
								if(strtotime($now_time) < strtotime($get_end_time)) $undertime = (strtotime($get_end_time) - strtotime($now_time)) / 60;
							}else{
								// show_error("Payroll set up for break time is empty.");
								$result = array(
									'error' => true,
									'error_msg' => "Payroll set up for break time is empty."
								);

								echo json_encode($result);
								return false;
							}
						}
						
						// FOR TOTAL HOURS
						if($undertime == 0){
							$total_hours = 	(strtotime($get_end_time) - strtotime(date("H:i:s",strtotime($new_time_in)))) / 3600;
						}else{
							$total_hours = 	(strtotime($now_time) - strtotime(date("H:i:s",strtotime($new_time_in)))) / 3600;
						}
						
						// FOR TOTAL HOURS REQUIRED
						
						$total_hours_required = (strtotime($now_time) - strtotime(date("H:i:s",strtotime($new_time_in)))) / 3600;
							
					}elseif($flag_halfday == 2){
						$undertime_a = 0;
						$undertime_b = 0;
						
						if(date("A",strtotime($wd_start)) != "PM" && date("A",strtotime($wd_end)) != "AM"){ // DAY SHIFT TRAPPING
								
							// FOR UNDERTIME
							$undertime_a = (strtotime($workday_settings_end_time) - strtotime($b_et)) / 60; // time start - breaktime end time (tardiness for start time)
								
							// FOR UNDERTIME
							if(strtotime($now_time) < strtotime($b_st)) $undertime_b = (strtotime($b_st) - strtotime($now_time)) / 60;
						
							$undertime = $undertime_a + $undertime_b;
															
							// GET END TIME FOR TIME OUT
							$get_end_time = $this->elm->get_end_time($this->company_id,$this->work_schedule_id);
					
							// GET LATEST TIME IN ALLOWED VAL
							$get_latest_timein_allowed_val = $this->employee->get_latest_timein_allowed_val($this->company_id,$this->work_schedule_id,"work_schedule_id");
														
							$st_for_tardiness = (!$get_latest_timein_allowed_val) ? $workday_settings_start_time : date('H:i:s',strtotime($workday_settings_start_time.' +'.$get_latest_timein_allowed_val.' minutes')) ;
						
							// FOR TARDINESS 											>>>>>>>>>>>>>>>>>>>>>>>>>>>>
							if($get_end_time != FALSE){
								if(strtotime($st_for_tardiness) < strtotime(date("H:i:s",strtotime($new_time_in)))) $tardiness = (strtotime(date("H:i:s",strtotime($new_time_in))) - strtotime($st_for_tardiness)) / 60;
							}else{
								// show_error("Payroll set up for break time is empty.");

								$result = array(
									'error' => true,
									'error_msg' => "Payroll set up for break time is empty."
								);

								echo json_encode($result);
								return false;
							}
						
							// calculate total hours
							$th = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
							$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id);
						
							// new undertime calculation
							$undertime = (($hw - $th) * 60) - $tardiness;
							$undertime = 0;
								
						}else{ // NIGHT SHIFT TRAPPING
								
							// FOR TARDINESS										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
							if($check_hours_flexible != FALSE){
								//$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$check_hours_flexible;
								$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$check_hours_flexible;
								$tardiness_b = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
							}else{
								//$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$workday_settings_start_time;
								$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
								$tardiness_b = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
							}
								
							$tardiness = $tardiness_b;
								
							// GET END TIME FOR TIME OUT
							
							$get_end_time = $this->elm->get_end_time($this->company_id,$this->work_schedule_id);
							
								
							/* version 1.0 */
								
							// FOR UNDERTIME
							if($get_end_time != FALSE){
								$u_add_oneday_timein = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$b_st;
								if(strtotime($new_time_out) < strtotime($u_add_oneday_timein)) $undertime = (strtotime($u_add_oneday_timein) - strtotime($new_time_out)) / 60;
							}else{
								// show_error("Payroll set up for break time is empty.");

								$result = array(
									'error' => true,
									'error_msg' => "Payroll set up for break time is empty."
								);

								echo json_encode($result);
								return false;
							}
								
							/* version 1.1 */
								
							// calculate total hours
							$th = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
							$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id);
								
							// new undertime calculation
							$undertime = (($hw - $th) * 60) - $tardiness;
							$undertime = 0;
						}
						
						// FOR TOTAL HOURS
						if($undertime == 0){
							$u_add_oneday_timein = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$b_st;
							
							$total_hours = 	(strtotime($new_time_out) - ($check_bet_timein > $new_time_in ? strtotime($check_bet_timein): strtotime($new_time_in))) / 3600;
						}else{
							$total_hours = 	(strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
						}
						
						// FOR TOTAL HOURS REQUIRED
						$total_hours_required = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
						
							
					}elseif($flag_halfday == 3){
						$get_different = ($this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id)) / 2;
						$get_between = date("Y-m-d H:i:s",strtotime(date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time." + {$get_different} hour"));
						
						/* DAY SHIFT TRAPPING */
						
						if(strtotime($get_between) < strtotime($new_time_in)){
							/* for afternoon */
							// get undertime
							if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
								$undertime = (strtotime($workday_settings_end_time) - strtotime($new_time_out)) / 60;
							}
								
							// calculate total hours
							$newtimein = (strtotime($new_time_in) < strtotime($get_between)) ? $get_between : $new_time_in ;
							$th = (strtotime($new_time_out) - strtotime($newtimein)) / 3600;
							$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id);
								
							// new tardiness calculation
							$tardiness = (($hw - $th) * 60) - $undertime;
							$tardiness = 0;
							// total hours
							$total_hours = 	(strtotime($new_time_out) - strtotime($newtimein)) / 3600;
							$total_hours_required = (strtotime($new_time_out) - strtotime($newtimein)) / 3600;
								
						}else{
							/* for morning */
							// get tardiness
							if(strtotime($workday_settings_start_time) < strtotime($new_time_in)){
								$tardiness = (strtotime($new_time_in) - strtotime($workday_settings_start_time)) / 60;
							}
								
							if($check_hours_flexible != FALSE){
								$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$check_hours_flexible;
								$tardiness = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
							}else{
								$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
								$tardiness = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
							}
								
							// calculate total hours
							$newtimeout = (strtotime($get_between) < strtotime($new_time_out)) ? $get_between : $new_time_out ;
							$th = (strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
							$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id);
								
							// new tardiness calculation
							$undertime = (($hw - $th) * 60) - $tardiness;
							$undertime = 0;
							// total hours
							$total_hours = 	(strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
							$total_hours_required = (strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
								
						}
						
					}
					
					$new_date = date("Y-m-d",strtotime($new_time_in." -1 day"));
					$check_workday = $this->elm->halfday_check_workday($this->work_schedule_id,$this->company_id,$new_date);
					$hours_worked = $this->elm->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id,$this->work_schedule_id);
					$payroll_period = $this->employee->get_payroll_period($this->company_id);
					
					if($check_workday){
						// minus 1 day
						$period_to = $payroll_period->period_to;
						$date_halfday = (strtotime($period_to) == strtotime($new_date)) ? $period_to : NULL ;
					}else{
						$date_halfday = NULL;
					}
					
					$get_total_hours = $total_hours;
					
					if($absent_min){
						$total_absent_min = ($your_total_hours - $total_hours_required) * 60;
					} else {
						$total_absent_min = 0;
					}
						
					if($tardiness_rule_migrated_v3) {
					    $your_total_hours = $calculated_data["total_hours"];
					    $total_hours_required = $calculated_data["total_hours_required"];
					    $fritz_tardiness = $calculated_data["tardiness_min"];
					    $undertime = $calculated_data["undertime_min"];
					    $late_min = $calculated_data["late_min"];
					    $overbreak_min = $calculated_data["overbreak_min"];
					    $total_absent_min = $calculated_data["absent_min"];
					    $current_date_nsd = $calculated_data["current_date_nsd"];
                        $next_date_nsd = $calculated_data["next_date_nsd"];
					} else {
					    $late_min = 0;
					    $overbreak_min = 0;
					}

					$break_1_start_date_time = ($break_1_start_date_time) ? ($break_1_start_date_time) : NULL;
					$break_1_end_date_time = ($break_1_end_date_time) ? ($break_1_end_date_time) : NULL;
					$break_2_start_date_time = ($break_2_start_date_time) ? ($break_2_start_date_time) : NULL;
					$break_2_end_date_time = ($break_2_start_date_time) ? ($break_2_end_date_time) : NULL;
					
					$break_1_start_date_time = ($break_1_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_start_date_time));
					$break_1_end_date_time = ($break_1_end_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_end_date_time));
					$break_2_start_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_start_date_time));
					$break_2_end_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_end_date_time));
					
					// TOTAL HOURS
					// UPDATE TIME INS
					$where_tot = array(
							"comp_id" => $this->company_id,
							"emp_id" => $this->emp_id,
							"employee_time_in_id" => $employee_timein
					);
					$this->db->where($where_tot);
						
					$data_update = array(
						"change_log_work_schedule_id" => $this->work_schedule_id,
						"comp_id" => $this->company_id,
						"emp_id" => $this->emp_id,
						"change_log_date" => $emp_schedule_date,
						"time_in_status" => 'pending',
						"corrected" => 'Yes',
						"reason"	 => $reason,
						"change_log_date_filed" => date("Y-m-d H:i:s"),
						"change_log_tardiness_min" => $fritz_tardiness,
						"change_log_undertime_min" => $undertime,
						"change_log_time_in" => $new_time_in,
						"change_log_lunch_out" => null,
						"change_log_lunch_in" => null,
						"change_log_time_out" => $new_time_out,
				        "change_log_break1_out" => $break_1_start_date_time,
                        "change_log_break1_in" => $break_1_end_date_time,
                        "change_log_break2_out" => $break_2_start_date_time,
                        "change_log_break2_in" => $break_2_end_date_time,
						"change_log_total_hours" => $your_total_hours,
						"change_log_total_hours_required" => $total_hours_required,
						"flag_halfday" => 1,
						$new_source => "Adjusted",
					    "change_log_late_min" => $late_min,
					    "change_log_overbreak_min" => $overbreak_min,
					    "change_log_absent_min" => $total_absent_min,
                        "flag_payroll_correction" => $flag_payroll_correction,
                        "rest_day_r_a"  => $rest_day_r_a,
                        "flag_rd_include" => $flag_rd_include,
                        "holiday_approve" => $holiday_approve,
                        "flag_holiday_include" => $flag_holiday_include,
                        "current_date_nsd" => $current_date_nsd,
                        "next_date_nsd" => $next_date_nsd
					);
						
					$update_change_logs = $this->db->update('employee_time_in', $data_update);
					
					if($update_change_logs) {
						$new_total_hours_worked = $total_hours_required;
						$new_tardiness = $fritz_tardiness;
						$new_undertime = $undertime;
						$your_new_total_hours = $your_total_hours;
					} else {
						$new_total_hours_worked = 0;
						$new_tardiness = 0;
						$new_undertime = 0;
						$your_new_total_hours = 0;
					}
					
				}else{
					// check employee work schedule
					// check break time
				    $check_break_time = $this->employee->check_break_time($this->work_schedule_id,$this->company_id,"work_schedule_id", $emp_schedule_date);
				    
				    if($tardiness_rule_migrated_v3) {
						    $check_break_time = false;
						    // || $get_schedule_settings->enable_additional_breaks == "yes"
						    if($get_schedule_settings->enable_lunch_break == "yes" || $get_schedule_settings->enable_additional_breaks == "yes") {
						        if($get_schedule_settings->track_break_1 == "yes" || $get_schedule_settings->track_break_2 == "yes") {
						            $break_in_min = $get_schedule_settings->break_in_min + $get_schedule_settings->break_1_in_min + $get_schedule_settings->break_2_in_min;
						            if($break_in_min > 0) {
						                $check_break_time = true;
						            }
						        }
						    }
						}
						
					if(!$check_break_time){ // ZERO VALUE FOR BREAK TIME
						// update total hours and total hours required rest day
						$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
						
						/* EMPLOYEE WORK SCHEDULE */
						$number_of_breaks_per_day = $this->elmf->check_break_time_flex($this->work_schedule_id,$this->company_id);
						
						// tardiness and undertime value
						$update_tardiness = $this->elm->get_tardiness_val($this->emp_id,$this->company_id,$new_time_in,$this->work_schedule_id,$number_of_breaks_per_day);
						$update_undertime = $this->elm->get_undertime_val($this->emp_id,$this->company_id,$new_time_in,$new_time_out,$this->work_schedule_id,$number_of_breaks_per_day);
						
						// hours worked
						$hours_worked = $this->elm->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
						
						// check tardiness value
						$flag_tu = 0;
						
						$get_total_hours_worked = ($hours_worked / 2) + .5;
						if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
							$update_tardiness = 0;
							$update_undertime = 0;
							$flag_tu = 1;
						}
						
						// required hours worked only
						$new_total_hours = $this->elm->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out,$hours_worked,$this->work_schedule_id);
						
						if($absent_min){
							$total_absent_min = ($your_total_hours - $get_total_hours) * 60;
						} else {
							$total_absent_min = 0;
						}

						$late_min = 0;
                        $overbreak_min = 0;
                        $current_date_nsd = 0;
                        $next_date_nsd = 0;
						
						if($tardiness_rule_migrated_v3) {
						    $your_total_hours = $calculated_data["total_hours"];
						    $get_total_hours = $calculated_data["total_hours_required"];
						    $fritz_tardiness = $calculated_data["tardiness_min"];
						    $update_undertime = $calculated_data["undertime_min"];
						    $late_min = $calculated_data["late_min"];
						    $overbreak_min = $calculated_data["overbreak_min"];
						    $total_absent_min = $calculated_data["absent_min"];
						    $current_date_nsd = $calculated_data["current_date_nsd"];
                            $next_date_nsd = $calculated_data["next_date_nsd"];
						}

						$break_1_start_date_time = ($break_1_start_date_time) ? ($break_1_start_date_time) : NULL;
						$break_1_end_date_time = ($break_1_end_date_time) ? ($break_1_end_date_time) : NULL;
						$break_2_start_date_time = ($break_2_start_date_time) ? ($break_2_start_date_time) : NULL;
						$break_2_end_date_time = ($break_2_start_date_time) ? ($break_2_end_date_time) : NULL;
												
						$break_1_start_date_time = ($break_1_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_start_date_time));
						$break_1_end_date_time = ($break_1_end_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_end_date_time));
						$break_2_start_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_start_date_time));
						$break_2_end_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_end_date_time));
						
						// UPDATE TIME INS
						$where_tot = array(
							"comp_id" => $this->company_id,
							"emp_id" => $this->emp_id,
							"employee_time_in_id" => $employee_timein
						);
						$this->db->where($where_tot);
						
						$data_update = array(
							"change_log_work_schedule_id" => $this->work_schedule_id,
							"comp_id" => $this->company_id,
							"emp_id"	 => $this->emp_id,
							"change_log_date" => $emp_schedule_date,
							"time_in_status" => 'pending',
							"corrected" => 'Yes',
							"reason" => $reason,
							"flag_tardiness_undertime" => $flag_tu,
							"absent_min" => 0,
							"change_log_date_filed" => date("Y-m-d H:i:s"),
							"change_log_tardiness_min" => $fritz_tardiness,
							"change_log_undertime_min" => $update_undertime,
							"change_log_time_in" => $new_time_in,
							"change_log_time_out" => $new_time_out,
                            "change_log_break1_out" => $break_1_start_date_time,
                            "change_log_break1_in" => $break_1_end_date_time,
                            "change_log_break2_out" => $break_2_start_date_time,
                            "change_log_break2_in" => $break_2_end_date_time,
							"change_log_total_hours" => $your_total_hours,
							"change_log_total_hours_required" => $get_total_hours,
							"flag_halfday" => 1,
							$new_source => "Adjusted",
						    "change_log_late_min" => $late_min,
						    "change_log_overbreak_min" => $overbreak_min,
						    "change_log_absent_min" => $total_absent_min,
                            "flag_payroll_correction" => $flag_payroll_correction,
                            "rest_day_r_a" => $rest_day_r_a,
                            "flag_rd_include" => $flag_rd_include,
                            "holiday_approve" => $holiday_approve,
                            "flag_holiday_include" => $flag_holiday_include,
                            "current_date_nsd" => $current_date_nsd,
                            "next_date_nsd" => $next_date_nsd
						);
						
						$update_change_logs = $this->db->update('employee_time_in', $data_update);
						
						if($update_change_logs) {
							$new_total_hours_worked = $get_total_hours;
							$new_tardiness = $fritz_tardiness;
							$new_undertime = $update_undertime;
							$your_new_total_hours = $your_total_hours;
						} else {
							$new_total_hours_worked = 0;
							$new_tardiness = 0;
							$new_undertime = 0;
							$your_new_total_hours = 0;
						}
						
					}else if($new_time_in != "" && $new_time_out != "" && $new_lunch_in != "" && $new_lunch_out != ""){ // same2 rani sa else ani.. if naa ky changes, apila lng sd ang else, ang nkalahi ra ani ky pwd wala ang lunch sa else
						// WHOLEDAY
						$hours_worked = $this->elm->get_hours_worked(date("Y-m-d",strtotime($emp_schedule_date)), $this->emp_id, $this->work_schedule_id);
						
						$break_1_start_date_time = ($break_1_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_start_date_time));
						$break_1_end_date_time = ($break_1_end_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_end_date_time));
						$break_2_start_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_start_date_time));
						$break_2_end_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_end_date_time));
						
						$your_total_hours = 0;
                        $get_total_hours = 0;
                        $fritz_tardiness = 0;
                        $update_undertime = 0;
                        $current_date_nsd = 0;
                        $next_date_nsd = 0;

						if($tardiness_rule_migrated_v3) {
						    $your_total_hours = $calculated_data["total_hours"];
						    $get_total_hours = $calculated_data["total_hours_required"];
						    $fritz_tardiness = $calculated_data["tardiness_min"];
						    $update_undertime = $calculated_data["undertime_min"];
						    $late_min = $calculated_data["late_min"];
						    $overbreak_min = $calculated_data["overbreak_min"];
						    $total_absent_min = $calculated_data["absent_min"];
						    $current_date_nsd = $calculated_data["current_date_nsd"];
                            $next_date_nsd = $calculated_data["next_date_nsd"];
						}
						
						/* EMPLOYEE WORK SCHEDULE */
						$update_change_logs = $this->elm->update_change_logs($this->company_id, $this->emp_id,$employee_timein, $reason, $new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out, $hours_worked,$this->work_schedule_id,$new_source,$late_min,$overbreak_min,$emp_schedule_date,$flag_payroll_correction,$fritz_tardiness,$update_undertime,$your_total_hours,$get_total_hours,$break_1_start_date_time,$break_1_end_date_time,$break_2_start_date_time,$break_2_end_date_time,$tardiness_rule_migrated_v3);
						
						if($update_change_logs) {
							$new_total_hours_worked = $update_change_logs->change_log_total_hours_required;
							$new_tardiness = $update_change_logs->change_log_tardiness_min;
							$new_undertime = $update_change_logs->change_log_undertime_min;
							$your_new_total_hours = $update_change_logs->change_log_total_hours;
						} else {
							$new_total_hours_worked = 0;
							$new_tardiness = 0;
							$new_undertime = 0;
							$your_new_total_hours = 0;
						}
						
					} else { // g,add ra ni nako ang else ky wala else gud, wala butangi ni feel if wala sd ni email rai ma send pro wala na save.. and ang email sad na ma send ky guba wrong data e.g jan 01 1970 
					    // WHOLEDAY
					    $hours_worked = $this->elm->get_hours_worked(date("Y-m-d",strtotime($emp_schedule_date)), $this->emp_id, $this->work_schedule_id);
					    
					    $break_1_start_date_time = ($break_1_start_date_time) ? ($break_1_start_date_time) : NULL;
						$break_1_end_date_time = ($break_1_end_date_time) ? ($break_1_end_date_time) : NULL;
						$break_2_start_date_time = ($break_2_start_date_time) ? ($break_2_start_date_time) : NULL;
						$break_2_end_date_time = ($break_2_start_date_time) ? ($break_2_end_date_time) : NULL;

					    $break_1_start_date_time = ($break_1_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_start_date_time));
					    $break_1_end_date_time = ($break_1_end_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_end_date_time));
					    $break_2_start_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_start_date_time));
					    $break_2_end_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_end_date_time));
					    
					    $new_lunch_out = ($new_lunch_out == "") ? NULL : date("Y-m-d H:i:s", strtotime($new_lunch_out));
					    $new_lunch_in = ($new_lunch_in == "") ? NULL : date("Y-m-d H:i:s", strtotime($new_lunch_in));
					    
					    $your_total_hours = 0;
                        $get_total_hours = 0;
                        $fritz_tardiness = 0;
                        $update_undertime = 0;
                        $current_date_nsd = 0;
                        $next_date_nsd = 0;

					    if($tardiness_rule_migrated_v3) {
					        $your_total_hours = $calculated_data["total_hours"];
					        $get_total_hours = $calculated_data["total_hours_required"];
					        $fritz_tardiness = $calculated_data["tardiness_min"];
					        $update_undertime = $calculated_data["undertime_min"];
					        $late_min = $calculated_data["late_min"];
					        $overbreak_min = $calculated_data["overbreak_min"];
					        $total_absent_min = $calculated_data["absent_min"];
					        $current_date_nsd = $calculated_data["current_date_nsd"];
                            $next_date_nsd = $calculated_data["next_date_nsd"];
					    }
					    
					    /* EMPLOYEE WORK SCHEDULE */
					    $update_change_logs = $this->elm->update_change_logs($this->company_id, $this->emp_id,$employee_timein, $reason, $new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out, $hours_worked,$this->work_schedule_id,$new_source,$late_min,$overbreak_min,$emp_schedule_date,$flag_payroll_correction,$fritz_tardiness,$update_undertime,$your_total_hours,$get_total_hours,$break_1_start_date_time,$break_1_end_date_time,$break_2_start_date_time,$break_2_end_date_time,$tardiness_rule_migrated_v3);
					    
					    if($update_change_logs) {
					        $new_total_hours_worked = $update_change_logs->change_log_total_hours_required;
					        $new_tardiness = $update_change_logs->change_log_tardiness_min;
					        $new_undertime = $update_change_logs->change_log_undertime_min;
					        $your_new_total_hours = $update_change_logs->change_log_total_hours;
					    } else {
					        $new_total_hours_worked = 0;
					        $new_tardiness = 0;
					        $new_undertime = 0;
					        $your_new_total_hours = 0;
					    }
					    
					    
					}
				}
			
			}elseif($check_work_type == "Flexible Hours"){
				if($check_rest_day || $check_holiday || $excess_logs == true){
					$tardiness = 0; 
					$undertime = 0;
					
					// update total hours and total hours required rest day
					$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
					
					if ($this->work_schedule_id == "-1") {
                        $flag_rd_include = "no";
                        $rest_day_r_a = "yes";
                    }
                        
					// UPDATE TIME INS
					$where_tot = array(
						"comp_id"				=> $this->company_id,
						"emp_id"				=> $this->emp_id,
						"employee_time_in_id"	=> $employee_timein
					);
					$this->db->where($where_tot);
					
					$data_update = array(
						"change_log_work_schedule_id" 		=> $this->work_schedule_id,
						"comp_id"							=> $this->company_id,
						"emp_id"							=> $this->emp_id,
						"change_log_date"					=> $emp_schedule_date,
						"time_in_status"					=> 'pending',
						"corrected"							=> 'Yes',
						"reason"							=> $reason,
						"change_log_date_filed"				=> date("Y-m-d H:i:s"),
						"change_log_tardiness_min"			=> $tardiness,
						"change_log_undertime_min"			=> $undertime,
						"change_log_time_in"				=> $new_time_in,
						"change_log_time_out"				=> $new_time_out,
						"change_log_total_hours"			=> $get_total_hours,
						"change_log_total_hours_required"	=> $get_total_hours,
						$new_source							=> "Adjusted",
					    "change_log_late_min"               => 0,
					    "change_log_overbreak_min"			=> 0,
					    "change_log_absent_min"             => 0,
                        "flag_payroll_correction"           => $flag_payroll_correction,
                        "rest_day_r_a"                      => $rest_day_r_a,
                        "flag_rd_include"                   => $flag_rd_include,
                        "holiday_approve"                   => $holiday_approve,
                        "flag_holiday_include"              => $flag_holiday_include,
					);
					
					$update_change_logs = $this->db->update('employee_time_in', $data_update);
					
					if($update_change_logs) {
						$new_total_hours_worked = $get_total_hours;
						$new_tardiness = $tardiness;
						$new_undertime = $undertime;
						$your_new_total_hours = $your_total_hours;
					} else {
						$new_total_hours_worked = 0;
						$new_tardiness = 0;
						$new_undertime = 0;
						$your_new_total_hours = 0;
					}
					
				}else if($new_lunch_out == NULL && $new_lunch_in == NULL && $flag_halfday == 1){
					// EMPLOYEE WORK SCHEDULE
					$check_hours_flexible = $this->elm->check_hours_flex($this->company_id,$this->work_schedule_id);
					//$check_breaktime = $this->elm->check_breaktime($this->company_id,$this->work_schedule_id);
					
					$get_hoursworked = $this->elmf->get_hoursworked($this->work_schedule_id,$this->company_id)->total_hours_for_the_day;
					$check_latest_timein_allowed = $this->elmf->check_lastest_timein_allowed($this->work_schedule_id,$this->company_id);
					
					if(!$check_latest_timein_allowed){
						// check workday settings
						$workday_settings_start_time = date("H:i:s",strtotime($new_time_in));
						$workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
					
					}else{
						// check workday settings
						$workday_settings_start_time = date("H:i:s",strtotime($check_latest_timein_allowed->latest_time_in_allowed));
						$workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
					
					}
					
					$get_different = ($this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id)) / 2;
					$get_between = date("Y-m-d H:i:s",strtotime(date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time." + {$get_different} hour"));
					
					if(strtotime($get_between) < strtotime($new_time_in)){
					
						/* for afternoon */
						// get undertime
						if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
							$undertime = (strtotime($workday_settings_end_time) - strtotime($new_time_out)) / 60;
						}
					
						// calculate total hours
						$newtimein = (strtotime($new_time_in) < strtotime($get_between)) ? $get_between : $new_time_in ;
						$th = (strtotime($new_time_out) - strtotime($newtimein)) / 3600;
						$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id);
					
						// new tardiness calculation
						$tardiness = (($hw - $th) * 60) - $undertime;
					
						// total hours
						$total_hours = 	(strtotime($new_time_out) - strtotime($newtimein)) / 3600;
						$total_hours_required = (strtotime($new_time_out) - strtotime($newtimein)) / 3600;
					
					}else{
						/* for morning */
						// get tardiness
						if(strtotime($workday_settings_start_time) < strtotime($new_time_in)){
							$tardiness = (strtotime($new_time_in) - strtotime($workday_settings_start_time)) / 60;
						}
					
						if($check_hours_flexible != FALSE){
							$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$check_hours_flexible;
							$tardiness = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
						}else{
							$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
							$tardiness = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
						}
					
						// calculate total hours
						$newtimeout = (strtotime($get_between) < strtotime($new_time_out)) ? $get_between : $new_time_out ;
						$th = (strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
						$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id);
						
						// new tardiness calculation
						$undertime = (($hw - $th) * 60) - $tardiness;
					
						// total hours
						$total_hours = 	(strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
						$total_hours_required = (strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
					
					}
					// UPDATE TIME INS
					$where_tot = array(
							"comp_id"				=> $this->company_id,
							"emp_id"				=> $this->emp_id,
							"employee_time_in_id"	=> $employee_timein
					);
					$this->db->where($where_tot);
						
					$data_update = array(
						"change_log_work_schedule_id" 		=> $this->work_schedule_id,
						"comp_id"							=> $this->company_id,
						"emp_id"							=> $this->emp_id,
						"change_log_date"					=> $emp_schedule_date,
						"time_in_status"					=> 'pending',
						"corrected"							=> 'Yes',
						"reason"							=> $reason,
						"change_log_date_filed"				=> date("Y-m-d H:i:s"),
						//"change_log_tardiness_min"		=> $tardiness,
						"change_log_tardiness_min"			=> 0,
						"change_log_undertime_min"			=> $undertime,
						"change_log_time_in"				=> $new_time_in,
						"change_log_time_out"				=> $new_time_out,
						"change_log_total_hours"			=> $your_total_hours,
						"change_log_total_hours_required"	=> $total_hours_required,
						"flag_halfday"						=> 1,
						$new_source							=> "Adjusted",
					    "change_log_late_min"				=> $late_min,
					    "change_log_overbreak_min"			=> $overbreak_min,
					    "change_log_absent_min"             => 0,
                        "flag_payroll_correction"           => $flag_payroll_correction,
                        "rest_day_r_a"                      => $rest_day_r_a,
                        "flag_rd_include"                   => $flag_rd_include,
                        "holiday_approve"                   => $holiday_approve,
                        "flag_holiday_include"              => $flag_holiday_include,
					);
						
					$update_change_logs = $this->db->update('employee_time_in', $data_update);
					
					if($update_change_logs) {
						$new_total_hours_worked = $total_hours_required;
						$new_tardiness = 0;
						$new_undertime = $undertime;
						$your_new_total_hours = $your_total_hours;
					} else {
						$new_total_hours_worked = 0;
						$new_tardiness = 0;
						$new_undertime = 0;
						$your_new_total_hours = 0;
					}
					
				}else{
					$get_flex_sched = $this->elmf->get_hoursworked($this->work_schedule_id,$this->company_id);
					if($get_flex_sched) {
						/* WHOLE DAY */
						$get_hoursworked = $get_flex_sched->total_hours_for_the_day;
						// check workday settings
						$workday_settings_start_time = date("Y-m-d", strtotime($emp_schedule_date)).' '.date("H:i:s",strtotime($get_flex_sched->latest_time_in_allowed));
						$workday_settings_end_time = date("Y-m-d H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
						$check_latest_timein_allowed = $this->elmf->check_lastest_timein_allowed($this->work_schedule_id,$this->company_id);
						
						$workday_settings_end_time_fritz = $workday_settings_end_time;
						if(!$check_latest_timein_allowed){
							$number_of_breaks_per_day = $this->elmf->check_break_time_flex($this->work_schedule_id,$this->company_id);
							if($number_of_breaks_per_day == 0){ 
								// update total hours and total hours required rest day
								$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
									
								// update total tardiness
								$update_tardiness = 0;
							
								// update undertime
								$update_undertime = 0;
								if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime(date("H:i:s",strtotime($new_time_in)))){
									$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
									$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
								}
								
								if(strtotime(date("Y-m-d H:i:s",strtotime($new_time_out))) < strtotime(date("Y-m-d H:i:s",strtotime($workday_settings_end_time_fritz)))){
									$new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
									$update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
								}
							
								// check tardiness value
								$flag_tu = 0;
							
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
								$get_total_hours_worked = ($hours_worked / 2) + .5;
								if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
									$update_tardiness = 0;
									$update_undertime = 0;
									$flag_tu = 1;
								}
							
								// required hours worked only
								$new_total_hours = $this->elmf->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_time_out,$hours_worked,$this->work_schedule_id,$number_of_breaks_per_day);
							
								// if value is less then 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($new_total_hours < 0) $new_total_hours = 0;
								if($get_total_hours < 0) $get_total_hours = 0;
							
								// UPDATE TIME INS
								$where_tot = array(
										"comp_id"=>$this->company_id,
										"emp_id"=>$this->emp_id,
										"employee_time_in_id"=>$employee_timein
								);
								$this->db->where($where_tot);
							
								$data_update = array(
									"change_log_work_schedule_id" 		=> $this->work_schedule_id,
									"comp_id"							=> $this->company_id,
									"emp_id"							=> $this->emp_id,
									"change_log_date"					=> $emp_schedule_date,
									"time_in_status"					=> 'pending',
									"corrected"							=> 'Yes',
									"reason"							=> $reason,
									"change_log_date_filed"				=> date("Y-m-d H:i:s"),
									"change_log_tardiness_min"			=> $update_tardiness,
									"change_log_undertime_min"			=> $update_undertime,
									"change_log_time_in"				=> $new_time_in,
									"change_log_time_out"				=> $new_time_out,
									"change_log_total_hours"			=> $your_total_hours,
									"change_log_total_hours_required"	=> $get_total_hours,
									$new_source							=> "Adjusted",
									"late_min"							=> $late_min,
								    "overbreak_min"						=> $overbreak_min,
								    "change_log_late_min"				=> $late_min,
								    "change_log_overbreak_min"			=> $overbreak_min,
                                    "flag_payroll_correction"           => $flag_payroll_correction,
                                    "rest_day_r_a"                      => $rest_day_r_a,
                                    "flag_rd_include"                   => $flag_rd_include,
                                    "holiday_approve"                   => $holiday_approve,
                                    "flag_holiday_include"              => $flag_holiday_include,
								);
							
								$update_change_logs = $this->db->update('employee_time_in', $data_update);
								
								if($update_change_logs) {
									$new_total_hours_worked = $get_total_hours;
									$new_tardiness = $update_tardiness;
									$new_undertime = $update_undertime;
									$your_new_total_hours = $your_total_hours;
								} else {
									$new_total_hours_worked = 0;
									$new_tardiness = 0;
									$new_undertime = 0;
									$your_new_total_hours = 0;
								}
								
							}else{
								// update tardiness for timein
								$tardiness_timein = 0;
							
								// update tardiness for break time
								$update_tardiness_break_time = 0;
								$duration_of_lunch_break_per_day = $this->elmf->duration_of_lunch_break_per_day($this->emp_id, $this->company_id, $this->work_schedule_id);
								$tardiness_a = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
								if($duration_of_lunch_break_per_day < $tardiness_a){
									$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
								}
							
								// update total tardiness
								$update_tardiness = $tardiness_timein + $update_tardiness_break_time;
							
								// update undertime
								$update_undertime = 0;
								if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
									$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
									$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
								}
								if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
									$new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
									$update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
								}
							
								// update total hours
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
								$update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - ($duration_of_lunch_break_per_day / 60);
							
								// check tardiness value
								$get_total_hours_worked = ($hours_worked / 2) + .5;
								if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
									$update_tardiness = 0;
									$update_undertime = 0;
									$flag_tu = 1;
								}
							
								// update total hours required
								$update_total_hours_required = ((strtotime($new_time_out) - strtotime($new_time_in)) / 3600) - ($update_tardiness / 60) - ($duration_of_lunch_break_per_day / 60);
							
								// if value is less then 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($update_total_hours < 0) $update_total_hours = 0;
								if($update_total_hours_required < 0) $update_total_hours_required = 0;
							
								// UPDATE TIME INS
								$where_tot = array(
										"comp_id"				=> $this->company_id,
										"emp_id"				=> $this->emp_id,
										"employee_time_in_id"	=> $employee_timein
								);
								$this->db->where($where_tot);
								
								$data_update = array(
									"change_log_work_schedule_id" => $this->work_schedule_id,
									"comp_id" => $this->company_id,
									"emp_id" => $this->emp_id,
									"change_log_date" => $emp_schedule_date,
									"time_in_status" => 'pending',
									"corrected" => 'Yes',
									"reason" => $reason,
									"change_log_date_filed" => date("Y-m-d H:i:s"),
									"change_log_tardiness_min" => $update_tardiness,
									"change_log_undertime_min" => $update_undertime,
									"change_log_time_in" => $new_time_in,
									"change_log_lunch_out" => $new_lunch_out,
									"change_log_lunch_in" => $new_lunch_in,
									"change_log_time_out" => $new_time_out,
									"change_log_total_hours" => $your_total_hours,
									"change_log_total_hours_required" => $update_total_hours_required,
									$new_source => "Adjusted",
								    "change_log_late_min" => $late_min,
								    "change_log_overbreak_min" => $overbreak_min,
                                    "flag_payroll_correction" => $flag_payroll_correction,
                                    "rest_day_r_a" => $rest_day_r_a,
                                    "flag_rd_include" => $flag_rd_include,
                                    "holiday_approve" => $holiday_approve,
                                    "flag_holiday_include" => $flag_holiday_include,
								);
								$update_change_logs = $this->db->update('employee_time_in', $data_update);
								
								if($update_change_logs) {
									$new_total_hours_worked = $update_total_hours_required;
									$new_tardiness = $update_tardiness;
									$new_undertime = $update_undertime;
									$your_new_total_hours = $your_total_hours;
								} else {
									$new_total_hours_worked = 0;
									$new_tardiness = 0;
									$new_undertime = 0;
									$your_new_total_hours = 0;
								}
							}
						}else{
							#$insert_time_in = $this->elmf->insert_time_in($date,$emp_no,$this->min_log,$emp_work_schedule_id);
							$number_of_breaks_per_day = $this->elmf->check_break_time_flex($this->work_schedule_id,$this->company_id);
							if($number_of_breaks_per_day == 0){
								
								// update total hours and total hours required rest day
								$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
								
								// update tardiness for timein
								$tardiness_timein = 0;
								if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
									if(date("A",strtotime($new_time_in)) == "AM"){
										// add one day for time in log
										$new_start_timein = date("Y-m-d",strtotime($new_time_in." -1 day"));
										$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
										
										if(strtotime($new_start_timein) < strtotime($new_time_in)){
											$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;			
										}
									}
								}else{
									$new_start_timein = date("Y-m-d",strtotime($new_time_in));
									#$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
									$new_start_timein = $workday_settings_start_time;
									
									if(strtotime($new_start_timein) < strtotime($new_time_in)){
										$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;
									}
								}
	
								// update total tardiness
								$update_tardiness = $tardiness_timein;
								
								// update undertime
								$update_undertime = 0;
								#if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
								if(strtotime(date("H:i:s",strtotime($new_time_in))) > strtotime($workday_settings_start_time)){
									$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($emp_schedule_date)), $this->emp_id, $this->work_schedule_id);
									#$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
									$workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$hours_worked} hour"));
								}
								
								if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
									$new_end_time = date("Y-m-d",strtotime($emp_schedule_date))." ".$workday_settings_end_time;
									#$update_undertime = (strtotime($workday_settings_end_time) - strtotime($new_time_out)) / 60;
									$update_undertime = ((strtotime($new_end_time) - strtotime($new_time_out)) / 60);
								}
								
								// check tardiness value
								$flag_tu = 0;
								
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
								$get_total_hours_worked = ($hours_worked / 2) + .5;
								if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
									$flag_tu = 1;
								}
								
								// required hours worked only
								$new_total_hours = $this->elmf->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_time_out,$hours_worked,$this->work_schedule_id,$number_of_breaks_per_day);
								
								// if value is less than 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($new_total_hours < 0) $new_total_hours = 0;
								if($get_total_hours < 0) $get_total_hours = 0;
								
								// UPDATE TIME INS
								$where_tot = array(
									"comp_id" => $this->company_id,
									"emp_id" => $this->emp_id,
									"employee_time_in_id" => $employee_timein
								);
								$this->db->where($where_tot);
								
								$data_update = array(
									"change_log_work_schedule_id" => $this->work_schedule_id,
									"comp_id" => $this->company_id,
									"emp_id" => $this->emp_id,
									"change_log_date" => $emp_schedule_date,
									"time_in_status" => 'pending',
									"corrected" => 'Yes',
									"reason" => $reason,
									"change_log_date_filed" => date("Y-m-d H:i:s"),
									"change_log_tardiness_min" => $update_tardiness,
									"change_log_undertime_min" => $update_undertime,
									"change_log_time_in" => $new_time_in,
									"change_log_time_out" => $new_time_out,
									"change_log_total_hours" => $your_total_hours,
									"change_log_total_hours_required" => $get_total_hours,
									$new_source => "Adjusted",
								    "change_log_late_min" => $late_min,
								    "change_log_overbreak_min" => $overbreak_min,
                                    "flag_payroll_correction" => $flag_payroll_correction,
                                    "rest_day_r_a" => $rest_day_r_a,
                                    "flag_rd_include" => $flag_rd_include,
                                    "holiday_approve" => $holiday_approve,
                                    "flag_holiday_include" => $flag_holiday_include,
								);
								
								$update_change_logs = $this->db->update('employee_time_in', $data_update);
								
								if($update_change_logs) {
									$new_total_hours_worked = $get_total_hours;
									$new_tardiness = $update_tardiness;
									$new_undertime = $update_undertime;
									$your_new_total_hours = $your_total_hours;
								} else {
									$new_total_hours_worked = 0;
									$new_tardiness = 0;
									$new_undertime = 0;
									$your_new_total_hours = 0;
								}
								
							}else{
								// update tardiness for timein
								$tardiness_timein = 0;
								if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
									if(date("A",strtotime($new_time_in)) == "AM"){
										// add one day for time in log
										#$new_start_timein = date("Y-m-d",strtotime($new_time_in." -1 day"));
										#$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
										$new_start_timein = $workday_settings_start_time;
										
										if(strtotime($new_start_timein) < strtotime($new_time_in)){
											$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;			
										}
									}
								}else{
									#$new_start_timein = date("Y-m-d",strtotime($new_time_in));
									#$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
									$new_start_timein = $workday_settings_start_time;
									
									if(strtotime($new_start_timein) < strtotime($new_time_in)){
										$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;			
									}
								}
								
								// update tardiness for break time
								$update_tardiness_break_time = 0;
								$duration_of_lunch_break_per_day = $this->elmf->duration_of_lunch_break_per_day($this->emp_id, $this->company_id, $this->work_schedule_id);
								$tardiness_a = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
								if($duration_of_lunch_break_per_day < $tardiness_a){
									$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
								}
								
								// update total tardiness
								$update_tardiness = $tardiness_timein + $update_tardiness_break_time;
								
								// update undertime
								$update_undertime = 0;
								if(strtotime(date("H:i:s",strtotime($new_time_in))) > strtotime($workday_settings_start_time)){
									$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
									#$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
									$workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$hours_worked} hour"));
								}
								
								$new_time_out_time = date("H:i:s",strtotime($new_time_out));
								if(strtotime($new_time_out_time) < strtotime($workday_settings_end_time)){
									if(strtotime($new_time_in) >= strtotime($workday_settings_start_time)) {
								        $new_end_time = $workday_settings_end_time;
								    } else {
								        $new_end_time = date("H:i:s", strtotime($new_time_in." +{$hours_worked} hour"));
								    }
								    
								    $update_undertime = (strtotime($new_end_time) - strtotime($new_time_out_time)) / 60;
								}
								
								// update total hours
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
								$update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - ($duration_of_lunch_break_per_day / 60);
								
								// check tardiness value
								$get_total_hours_worked = ($hours_worked / 2) + .5;
								if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
									$update_tardiness = 0;
									#$update_undertime = 0;
									$flag_tu = 1;
								}
								
								// update total hours required
								$calc_tot_break = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 3600;
								$actual_break = ($duration_of_lunch_break_per_day / 60);
								
								if($calc_tot_break > $actual_break) {
									$excess_break = $calc_tot_break - $actual_break;
								} else {
									$excess_break = 0;
								}
								
								$update_total_hours_required = (((strtotime($new_time_out) - strtotime($new_time_in)) / 3600) - ($update_tardiness / 60) - ($duration_of_lunch_break_per_day / 60)) - $excess_break;
								
								// if value is less than 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($update_total_hours < 0) $update_total_hours = 0;
								if($update_total_hours_required < 0) $update_total_hours_required = 0;
								
								$your_total_hours = $your_total_hours - ($duration_of_lunch_break_per_day / 60);
								
								// UPDATE TIME INS
								$where_tot = array(
									"comp_id" => $this->company_id,
									"emp_id"	 => $this->emp_id,
									"employee_time_in_id" => $employee_timein
								);
								
								$this->db->where($where_tot);
								
								$data_update = array(
									"change_log_work_schedule_id" => $this->work_schedule_id,
									"comp_id" => $this->company_id,
									"emp_id"	 => $this->emp_id,
									"change_log_date" => $emp_schedule_date,
									"time_in_status" => 'pending',
									"corrected" => 'Yes',
									"reason"	 => $reason,
									"change_log_date_filed" => date("Y-m-d H:i:s"),
									"change_log_tardiness_min" => $update_tardiness,
									"change_log_undertime_min" => $update_undertime,
									"change_log_time_in" => $new_time_in,
									"change_log_lunch_out" => $new_lunch_out,
									"change_log_lunch_in" => $new_lunch_in,
									"change_log_time_out" => $new_time_out,
									"change_log_total_hours" => $your_total_hours,
									"change_log_total_hours_required" => $update_total_hours_required,
									$new_source => "Adjusted",
								    "change_log_late_min" => $late_min,
								    "change_log_overbreak_min" => $overbreak_min,
                                    "flag_payroll_correction" => $flag_payroll_correction,
                                    "rest_day_r_a" => $rest_day_r_a,
                                    "flag_rd_include" => $flag_rd_include,
                                    "holiday_approve" => $holiday_approve,
                                    "flag_holiday_include" => $flag_holiday_include,
								);
								
								$update_change_logs = $this->db->update('employee_time_in', $data_update);
								
								if($update_change_logs) {
									$new_total_hours_worked = $update_total_hours_required;
									$new_tardiness = $update_tardiness;
									$new_undertime = $update_undertime;
									$your_new_total_hours = $your_total_hours;
								} else {
									$new_total_hours_worked = 0;
									$new_tardiness = 0;
									$new_undertime = 0;
									$your_new_total_hours = 0;
								}
							}
						}
					}
				}
				
			}elseif($check_work_type == "Workshift"){
			    $check_holiday = $this->employee->get_holiday_date($emp_schedule_date,$this->emp_id,$this->company_id);
			    if(check_if_enable_breaks_on_holiday($this->company_id,$this->work_schedule_id, true,$schedule_blocks_id)) {
			        $check_holiday = false;
			    }
			    
			    $get_schedule_blocks_time_in = $this->employee->get_schedule_blocks_time_in($employee_timein,$this->company_id,$this->emp_id);
			    
				if($get_schedule_blocks_time_in) {
					if($get_schedule_blocks_time_in->source != null) {
						$new_source_s = "last_source";
					} else {
						$new_source_s = "source";
					}
				} else {
					$new_source_s = "source";
				}
				
					// check break time
					$check_break_time = $this->ews->check_breaktime_split1($this->company_id, $this->emp_id, date("Y-m-d",strtotime($new_time_in)), $this->work_schedule_id,$schedule_blocks_id);
					
					if($check_break_time->break_in_min == 0 || $check_break_time->break_in_min == null || $check_holiday){ // ZERO VALUE FOR BREAK TIME
					    
						// update total hours and total hours required rest day
						$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
						
						/* EMPLOYEE WORK SCHEDULE */
						$number_of_breaks_per_day = $check_break_time->break_in_min;
						
						// tardiness and undertime value
						$sched_start = date("Y-m-d", strtotime($new_time_in)).' '.$check_break_time->start_time;
						$update_tardiness = 0;
						
						if(strtotime($new_time_in) > strtotime($sched_start)) {
							$update_tardiness = (strtotime($new_time_in) - strtotime($sched_start)) / 60;
						}
						
						// get total undertime
						$sched_end = date("Y-m-d", strtotime($new_time_out)).' '.$check_break_time->end_time;
							
						if(strtotime($sched_end) > strtotime($new_time_out)) {
							$update_undertime = (strtotime($sched_end) - strtotime($new_time_out)) / 60;
						} else {
							$update_undertime = 0;
						}
						
						// hours worked
						$hours_worked = $check_break_time->total_hours_work_per_block;
						// check tardiness value
						$flag_tu = 0;
						
						$get_total_hours_worked = ($hours_worked / 2) + .5;
						if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
							$flag_tu = 1;
						}
						
						// required hours worked only
						$new_total_hours = $this->elm->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out,$hours_worked,$this->work_schedule_id);
						
						if($check_holiday) {
						    $update_tardiness = 0;
						    $update_undertime = 0;
						}
						// UPDATE SPLIT TIME INS
						$where_tot = array(
							"comp_id" => $this->company_id,
							"emp_id"	 => $this->emp_id,
							"schedule_blocks_time_in_id" => $employee_timein
						);
						
						$this->db->where($where_tot);
						
						$data_update = array(
							"comp_id" => $this->company_id,
							"emp_id" => $this->emp_id,
							"date" => date("Y-m-d",strtotime($new_time_in)),
							"time_in_status" => 'pending',
							"corrected" => 'Yes',
							"reason" => $reason,
							"flag_tardiness_undertime" => $flag_tu,
							"change_log_date_filed" => date("Y-m-d H:i:s"),
							"change_log_tardiness_min" => $update_tardiness,
							"change_log_undertime_min" => $update_undertime,
							"change_log_time_in" => $new_time_in,
							"change_log_time_out" => $new_time_out,
							"change_log_total_hours" => $hours_worked,
							"change_log_total_hours_required" => $get_total_hours,
							"flag_halfday" => 1,
							$new_source_s => "Adjusted",
						    "change_log_late_min" => $update_tardiness,
						    "change_log_overbreak_min" => 0,
						    "change_log_absent_min" => 0,
						    "flag_payroll_correction" => $flag_payroll_correction
						);
						
						$update_change_logs = $this->db->update('schedule_blocks_time_in', $data_update);
						
						if($update_change_logs) {
							$split_timein_information = $this->timeins->split_timeins_info($employee_timein,$this->company_id);
							$emp_time_in_id = $split_timein_information->employee_time_in_id;
							$new_total_hours_worked = $get_total_hours;
							$new_tardiness = $update_tardiness;
							$new_undertime = $update_undertime;
							$your_new_total_hours = $hours_worked;
						} else {
							$emp_time_in_id = "";
							$new_total_hours_worked = 0;
							$new_tardiness = 0;
							$new_undertime = 0;
							$your_new_total_hours = 0;
						}
														
						// UPDATE TIME INS
						$where_timein = array(
							"comp_id" => $this->company_id,
							"emp_id" => $this->emp_id,
							"employee_time_in_id" => $emp_time_in_id,
							"status" => "Active"
						);
						
						$this->db->where($where_timein);
						
						$data_update_timein = array(
							"time_in_status" => 'pending',
							"split_status" => 'pending',
							"corrected" => 'Yes',
						    "last_source" => "Adjusted",
						    "flag_payroll_correction" => $flag_payroll_correction
						);
						
						$this->db->update('employee_time_in', $data_update_timein);
						
					}else if($new_time_in != "" && $new_time_out != "" && $new_lunch_in != "" && $new_lunch_out != ""){
						
						// WHOLEDAY
						$hours_worked = $check_break_time->total_hours_work_per_block;
						
						// tardiness
						$tardiness = $this->elm->get_tardiness_import($this->emp_id, $this->company_id, $new_time_in, $new_lunch_out, $new_lunch_in, $this->work_schedule_id, $check_break_time->break_in_min,"",false,$schedule_blocks_id);
						// get total undertime
						$sched_end = date("Y-m-d", strtotime($new_time_out)).' '.$check_break_time->end_time;
						
						if(strtotime($sched_end) > strtotime($new_time_out)) {
							$undertime = (strtotime($sched_end) - strtotime($new_time_out)) / 60;
						} else {
							$undertime = 0;
						}
						
						$change_log_late_min = $tardiness - $overbreak_min;
						
						// total hours worked
						$total_hours_worked = $this->elm->get_tot_hours_limit($this->emp_id, $this->company_id, $new_time_in, $new_lunch_out, $new_lunch_in, $new_time_out, $this->work_schedule_id, $check_break_time->break_in_min);
													
						if($check_holiday) {
						    $tardiness = 0;
						    $undertime = 0;
						    $change_log_late_min = 0;
						    $overbreak_min = 0;
						}
						
						$where_tot = array(
							"comp_id" => $this->company_id,
							"emp_id" => $this->emp_id,
							"schedule_blocks_time_in_id" => $employee_timein
						);
						
						$this->db->where($where_tot);
						
						$data = array(
						    "time_in_status" => 'pending',
						    "corrected" => 'Yes',
						    "reason" => $reason,
						    "change_log_date_filed" => date("Y-m-d H:i:s"),
						    "change_log_time_in" => date("Y-m-d H:i:s", strtotime($new_time_in)),
						    "change_log_lunch_out" => date("Y-m-d H:i:s", strtotime($new_lunch_out)),
						    "change_log_lunch_in" => date("Y-m-d H:i:s", strtotime($new_lunch_in)),
						    "change_log_time_out" => date("Y-m-d H:i:s", strtotime($new_time_out)),
						    "change_log_tardiness_min" => $tardiness,
						    "change_log_undertime_min" => $undertime,
						    "change_log_total_hours_required" => $total_hours_worked,
						    "change_log_total_hours" => $hours_worked,
						    $new_source_s => "Adjusted",
						    "change_log_late_min" => ($change_log_late_min > 0) ? $change_log_late_min : 0,
						    "change_log_overbreak_min" => $overbreak_min,
						    "change_log_absent_min" => 0,
						    "flag_payroll_correction" => $flag_payroll_correction
						);
						
						$update_change_logs =  $this->db->update('schedule_blocks_time_in', $data);
						
						if($update_change_logs) {
							$split_timein_information = $this->timeins->split_timeins_info($employee_timein,$this->company_id);
							$emp_time_in_id = $split_timein_information->employee_time_in_id;
							$new_total_hours_worked = $total_hours_worked;
							$new_tardiness = $tardiness;
							$new_undertime = $undertime;
							$your_new_total_hours = $hours_worked;
						} else {
							$emp_time_in_id = "";
							$new_total_hours_worked = 0;
							$new_tardiness = 0;
							$new_undertime = 0;
							$your_new_total_hours = 0;
						}
						
						// UPDATE TIME INS
						$where_timein = array(
							"comp_id" => $this->company_id,
							"emp_id"	 => $this->emp_id,
							"employee_time_in_id" => $emp_time_in_id,
							"status" => "Active"
						);
						
						$this->db->where($where_timein);
						
						$data_update_timein = array(
							"time_in_status"	=> 'pending',
							"split_status"		=> 'pending',
							"corrected"			=> 'Yes',
						    "last_source"		=> "Adjusted",
						    "flag_payroll_correction"  => $flag_payroll_correction
						);
						
						$this->db->update('employee_time_in', $data_update_timein);
					}
				#}
			} else { // for open shift and rest day
                if($check_work_type == "Open Shift"){
                    $flag_open_shift = "1";
                } else {
                    $flag_open_shift = "0";
                }
                
                $tardiness = 0;
                $undertime = 0;
                
                // update total hours and total hours required rest day
                $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                
                if($tardiness_rule_migrated_v3) {
                    $get_total_hours = $calculated_data["total_hours_required"];
                    $tardiness_min = $calculated_data["tardiness_min"];
                    $undertime_min = $calculated_data["undertime_min"];
                    $late_min = $calculated_data["late_min"];
                    $overbreak_min = $calculated_data["overbreak_min"];
                    $absent_min = $calculated_data["absent_min"];
                } else {
                    $tardiness_min = 0;
                    $undertime_min = 0;
                    $late_min = 0;
                    $overbreak_min = 0;
                    $absent_min = 0;
                }
                
                if ($this->work_schedule_id == "-1") {
                    $flag_rd_include = "no";
                    $rest_day_r_a = "yes";
                }
                
                // UPDATE TIME INS
                $where_tot = array(
                    "comp_id" => $this->company_id,
                    "emp_id"	 => $this->emp_id,
                    "employee_time_in_id" => $employee_timein
                );
                $this->db->where($where_tot);
                
                $data_update = array(
                    "change_log_work_schedule_id"      => $this->work_schedule_id,
                    "comp_id"                          => $this->company_id,
                    "emp_id"                           => $this->emp_id,
                    "change_log_date"                  => $emp_schedule_date,
                    "time_in_status"                   => 'pending',
                    "corrected"                        => 'Yes',
                    "reason"                           => $reason,
                    "change_log_date_filed"            => date("Y-m-d H:i:s"),
                    "change_log_tardiness_min"         => $tardiness_min,
                    "change_log_undertime_min"         => $undertime_min,
                    "change_log_time_in"               => $new_time_in,
                    "change_log_time_out"              => $new_time_out,
                    "change_log_total_hours"           => $get_total_hours,
                    "change_log_total_hours_required"  => $get_total_hours,
                    $new_source                        => "Adjusted",
                    "change_log_late_min"              => $late_min,
                    "change_log_overbreak_min"         => $overbreak_min,
                    "change_log_absent_min"            => $absent_min,
                    "flag_payroll_correction"          => $flag_payroll_correction,
                    "rest_day_r_a"                     => $rest_day_r_a,
                    "flag_rd_include"                  => $flag_rd_include,
                    "holiday_approve"                  => $holiday_approve,
                    "flag_holiday_include"             => $flag_holiday_include,
                    "flag_open_shift"                  => $flag_open_shift,
                );
                
                $update_change_logs = $this->db->update('employee_time_in', $data_update);
                
                if($update_change_logs) {
                    $new_total_hours_worked = $get_total_hours;
                    $new_tardiness = $tardiness;
                    $new_undertime = $undertime;
                    $your_new_total_hours = $your_total_hours;
                } else {
                    $new_total_hours_worked = 0;
                    $new_tardiness = 0;
                    $new_undertime = 0;
                }
            }
			/* END UPDATE EMPLOYEE LOGS */
				
			// save approval token
			$emp_time_id = $employee_timein;
			$nw_total_hours_worked = $new_total_hours_worked;
			$nw_tardiness = $new_tardiness;
			$nw_undertime = $new_undertime;
			$nw_total_hours = $your_new_total_hours;
			$str = 'abcdefghijk123456789';
			$shuffled = str_shuffle($str);

			$break_1_start_date_time = ($break_1_start_date_time) ? ($break_1_start_date_time) : NULL;
			$break_1_end_date_time = ($break_1_end_date_time) ? ($break_1_end_date_time) : NULL;
			$break_2_start_date_time = ($break_2_start_date_time) ? ($break_2_start_date_time) : NULL;
			$break_2_end_date_time = ($break_2_start_date_time) ? ($break_2_end_date_time) : NULL;
			
			$break_1_start_date_time = ($break_1_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_start_date_time));
			$break_1_end_date_time = ($break_1_end_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_1_end_date_time));
			$break_2_start_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_start_date_time));
			$break_2_end_date_time = ($break_2_start_date_time == "") ? NULL : date("Y-m-d H:i:s", strtotime($break_2_end_date_time));
			
			$new_logs = array(
					'new_time_in' 	=> $new_time_in,
					'new_lunch_out' => $new_lunch_out,
					'new_lunch_in' 	=> $new_lunch_in,
					'new_time_out'	=> $new_time_out,
			    
                    "break1_out"   => $break_1_start_date_time,
                    "break1_in"    => $break_1_end_date_time,
                    "break2_out"   => $break_2_start_date_time,
                    "break2_in"    => $break_2_end_date_time,
					    
					'reason'		=> $reason
			);
			// generate token level
			//$employee_details = get_employee_details_by_empid($this->emp_id);
			$str2 = 'ABCDEFG1234567890';
			$shuffled2 = str_shuffle($str2);
			$psa_id = $this->session->userdata('psa_id');
			
			#$approver_id = $this->employee->get_approver_name_timein($this->emp_id,$this->company_id)->attendance_adjustment_approval_grp;
			$approver_id = $this->employee->get_approver_name_timesheet($this->emp_id,$this->company_id,"Timesheet Adjustment")->attendance_adjustment_approval_grp;
			if($approver_id == "" || $approver_id == 0) {
			    // Employee with no approver will use default workflow approval
			    // add_workflow_approval_default_group($this->company_id,$this->account_id); // create default if dont have any
			    $approver_id = get_app_default_approver($this->company_id,"Timesheet Adjustment")->approval_groups_via_groups_id;
			}
			
			if($check_work_type == "Workshift") {
			    $timein_info = $this->agm->split_timein_information($emp_time_id);
			} else {
			    $timein_info = $this->agm->timein_information($emp_time_id);
			}
			
			$fullname = ucfirst($timein_info->first_name)." ".ucfirst($timein_info->last_name);
			$hours_notification = get_notify_settings($approver_id, $this->company_id);
			$get_approval_settings_disable_status = $this->employee->get_approval_settings_disable_status($this->company_id);
			
			// elimate January 01, 1970
			$fritz_time_in = ($new_time_in == NULL || $new_time_in == "") ? NULL : date("Y-m-d H:i:s", strtotime($new_time_in));
			$fritz_lunch_out = ($new_lunch_out == NULL || $new_lunch_out == "") ? NULL : date("Y-m-d H:i:s", strtotime($new_lunch_out));
			$fritz_lunch_in = ($new_lunch_in == NULL || $new_lunch_in == "") ? NULL : date("Y-m-d H:i:s", strtotime($new_lunch_in));
			$fritz_time_out = ($new_time_out == NULL || $new_time_out == "") ? NULL : date("Y-m-d H:i:s", strtotime($new_time_out));
			
			if($approver_id) {
				if(is_workflow_enabled($this->company_id)){
				    $change_logs_approver = $this->agm->get_approver_name_timein_change_logs($this->emp_id,$this->company_id);
				    
					if($change_logs_approver){
						if($hours_notification){
							$new_level = 1;// 1   ////1 5  2345
							$lflag = 0;
					
                            // with leveling
                            $new_todo_appr_id = "";
							foreach ($change_logs_approver as $cla){
								$appovers_id = ($cla->emp_id) ? $cla->emp_id : "-99{$this->company_id}";
								$get_approval_group_via_groups_owner = $this->agm->get_approval_group_via_groups_owner($cla->approval_process_id, $cla->company_id, $cla->approval_groups_via_groups_id, $appovers_id);
								
								if($get_approval_group_via_groups_owner->emp_id == "-99{$this->company_id}"){
									$owner_approver = get_approver_owner_info($this->company_id);
									$appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
									$appr_account_id = $owner_approver->account_id;
									$appr_email = $owner_approver->email;
									$appr_id = "-99{$this->company_id}";
								} else {
									$appr_name = ucwords($cla->first_name." ".$cla->last_name);
									$appr_account_id = $cla->account_id;
									$appr_email = $cla->email;
									$appr_id = $cla->emp_id;
								}
								
								if($cla->level == $new_level){
                                    $new_todo_appr_id .= $appr_id.',';
									
								    $this->send_change_add_logss_notifcation("change", $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2,$appr_id,$tardiness_rule_migrated_v3);
									if($hours_notification->sms_notification == "yes"){
										$url = base_url()."approval/time_in/index/".$shuffled."/".$shuffled2."/1".$appr_id."0/".$employee_timein;
										$sms_message = "Click {$url} to approve {$fullname}'s attendance adjustment.";
										send_this_sms_global($this->company_id, $appr_account_id,$sms_message,$psa_id,false);
									}
									
									if($hours_notification->message_board_notification == "yes"){
										$token = $this->timeins->get_token($timein_info->approval_time_in_id, $this->company_id, $timein_info->emp_id);
										$url = base_url()."approval/time_in/index/".$shuffled."/".$shuffled2."/1".$appr_id."0/".$employee_timein;
										$next_appr_notif_message = "A New Attendance Adjustment has been filed by {$fullname} and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
										send_to_message_board($psa_id, $appr_id, $this->emp_id, $this->company_id, $next_appr_notif_message, "system", 'warning');
									}
									
									$lflag = 1;
								
								}else{
									// send without link
								    $this->send_change_add_logss_notifcation("change", $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "" , "", "","",$tardiness_rule_migrated_v3);
									if($hours_notification->sms_notification == "yes"){
										$sms_message = "A New Attendance Adjustment has been filed by {$fullname}.";
										send_this_sms_global($this->company_id, $appr_account_id,$sms_message,$psa_id,false);
									}
									
									if($hours_notification->message_board_notification == "yes"){
										$next_appr_notif_message = "A New Attendance Adjustment has been filed by {$fullname}.";
										send_to_message_board($psa_id, $appr_id, $this->emp_id,$this->company_id, $next_appr_notif_message, "system", "warning");
									}
										
								}
							}
								
							################################ notify payroll admin start ################################
							if($hours_notification->notify_payroll_admin == "yes"){
							    // HRs
							    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($psa_id);
							    if($payroll_admin_hr){
							        foreach ($payroll_admin_hr as $pahr){
							            $pahr_email = $pahr->email;
							            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);
							            
							            $this->send_change_add_logss_notifcation("change", $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $pahr_email, $pahr_name, "", "" , "", "","",$tardiness_rule_migrated_v3);
							        }
							    }
							    
							    // Owner
							    $pa_owner = get_approver_owner_info($this->company_id);
							    if($pa_owner){
							        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
							        $pa_owner_email = $pa_owner->email;
							        $pa_owner_account_id = $pa_owner->account_id;
							        
							        $this->send_change_add_logss_notifcation("change", $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $pa_owner_email, $pa_owner_name, "", "" , "", "","",$tardiness_rule_migrated_v3);
							    }
							}
							################################ notify payroll admin end ################################
							
							if($check_work_type == "Workshift"){
								$save_token = array(
										"time_in_id"		=> $emp_time_in_id,
										"split_time_in_id"	=> $emp_time_id,
										"token"				=> $shuffled,
										"comp_id"			=> $this->company_id,
										"emp_id"			=> $this->emp_id,
										"approver_id"		=> $approver_id,
										"level"				=> $new_level,
										"token_level"		=> $shuffled2
								);
								$save_token_q = $this->db->insert("approval_time_in",$save_token);
								$id = $this->db->insert_id();
								$timein_update = array('approval_time_in_id'=>$id);
								$this->db->where('schedule_blocks_time_in_id', $emp_time_id);
								$this->db->update('schedule_blocks_time_in',$timein_update);
								$appr_err="";
							} else {
								$save_token = array(
										"time_in_id"	=> $emp_time_id,
										"token"			=> $shuffled,
										"comp_id"		=> $this->company_id,
										"emp_id"		=> $this->emp_id,
										"approver_id"	=> $approver_id,
										"level"			=> $new_level,
										"token_level"	=> $shuffled2
								);
								$save_token_q = $this->db->insert("approval_time_in",$save_token);
								$id = $this->db->insert_id();
								$timein_update = array('approval_time_in_id'=>$id);
								$this->db->where('employee_time_in_id', $emp_time_id);
								$this->db->update('employee_time_in',$timein_update);
								$appr_err="";
                            }
                            
                            if ($check_if_enable_working_on_restday || $get_rest_day_settings == "yes") {
                                $module_for_new_todo = "rd_ra";
                            } elseif ($check_if_holiday_approval) {
                                $module_for_new_todo = "holiday";
                            } else {
                                $module_for_new_todo = "hours";
                            }

                            insert_todo_data($this->company_id,date("Y-m-d",strtotime($emp_schedule_date)),$this->emp_id,$id,1,$new_todo_appr_id,$approver_id,$this->work_schedule_id,$module_for_new_todo);
						}else{
							if($check_work_type == "Workshift"){
								$save_token = array(
										"time_in_id"		=> $emp_time_in_id,
										"split_time_in_id"	=> $emp_time_id,
										"token"				=> $shuffled,
										"comp_id"			=> $this->company_id,
										"emp_id"			=> $this->emp_id,
										"approver_id"		=> $approver_id,
										"level"				=> $new_level,
										"token_level"		=> $shuffled2
								);
								$save_token_q = $this->db->insert("approval_time_in",$save_token);
								$id = $this->db->insert_id();
								$timein_update = array('approval_time_in_id'=>$id);
								$this->db->where('schedule_blocks_time_in_id', $emp_time_id);
								$this->db->update('schedule_blocks_time_in',$timein_update);
								$appr_err="";
							} else {
								$save_token = array(
										"time_in_id"	=> $emp_time_id,
										"token"			=> $shuffled,
										"comp_id"		=> $this->company_id,
										"emp_id"		=> $this->emp_id,
										"approver_id"	=> $approver_id,
										"level"			=> 1,
										"token_level"	=> $shuffled2
								);
								$save_token_q = $this->db->insert("approval_time_in",$save_token);
								$id = $this->db->insert_id();
								$timein_update = array('approval_time_in_id'=>$id);
								$this->db->where('employee_time_in_id', $emp_time_id);
								$this->db->update('employee_time_in',$timein_update);									
							}
						}
						
						$result = array(
								'error'=>false,
								'approver_error'=> ""
						);
						
					}else{
						$new_level = 1;
						
						if($check_work_type == "Workshift"){
							$save_token = array(
									"time_in_id"		=> $emp_time_in_id,
									"split_time_in_id"	=> $emp_time_id,
									"token"				=> $shuffled,
									"comp_id"			=> $this->company_id,
									"emp_id"			=> $this->emp_id,
									"approver_id"	 	=> $approver_id,
									"level"				=> $new_level,
									"token_level"		=> $shuffled2
							);
							$save_token_q = $this->db->insert("approval_time_in",$save_token);
							$id = $this->db->insert_id();
							$timein_update = array('approval_time_in_id'=>$id);
							$this->db->where('schedule_blocks_time_in_id', $emp_time_id);
							$this->db->update('schedule_blocks_time_in',$timein_update);
							#$approver_error = "No Approvers found";
							$result = array(
									'error'=>false,
									'approver_error' => ""
							);
						} else {
							$save_token = array(
									"time_in_id"	=> $emp_time_id,
									"token"			=> $shuffled,
									"comp_id"		=> $this->company_id,
									"emp_id"		=> $this->emp_id,
									"approver_id"	=> $approver_id,
									"level"			=> $new_level,
									"token_level"	=> $shuffled2
							);
							$save_token_q = $this->db->insert("approval_time_in",$save_token);
							$id = $this->db->insert_id();
							$timein_update = array('approval_time_in_id'=>$id);
							$this->db->where('employee_time_in_id', $emp_time_id);
							$this->db->update('employee_time_in',$timein_update);
							#$approver_error = "No Approvers found";
							$result = array(
									'error'=>false,
									'approver_error' => ""
							);
							//show_error($approver_error);
						}
                    }
                    
                    if ($check_if_enable_working_on_restday || $get_rest_day_settings == "yes") {
                        $module_for_new_todo = "rd_ra";
                    } elseif ($check_if_holiday_approval) {
                        $module_for_new_todo = "holiday";
                    } else {
                        $module_for_new_todo = "hours";
                    }
                    
                    insert_todo_data($this->company_id,date("Y-m-d",strtotime($emp_schedule_date)),$this->emp_id,$id,1,"-99".$this->company_id,$approver_id,$this->work_schedule_id,$module_for_new_todo);
				} else {
					if($get_approval_settings_disable_status->status == "Inactive") {
						if($get_approval_settings_disable_status->disabled_application_status == 'approve') {
							$status = "approved";
						} else {
							$status = $get_approval_settings_disable_status->disabled_application_status;
						}
						
						if($check_work_type == "Workshift"){
							$fields = array(
									"date"					=> $emp_schedule_date,
									"time_in_status" 		=> $status,
									"corrected"				=> "Yes",
									"time_in"				=> $fritz_time_in,
									"lunch_out"				=> $fritz_lunch_out,
									"lunch_in"				=> $fritz_lunch_in,
									"time_out"				=> $fritz_time_out,
									"total_hours"			=> $nw_total_hours,
									"total_hours_required" 	=> $nw_total_hours_worked,
									"tardiness_min"			=> $nw_tardiness + $overbreak_min,
									"undertime_min"			=> $nw_undertime,
									'last_source'			=> "Adjusted",
									"late_min"				=> $nw_tardiness,
									"overbreak_min"			=> $overbreak_min
							);
								
							$where = array(
									"comp_id"						=> $this->company_id,
									"emp_id"						=> $this->emp_id,
									"schedule_blocks_time_in_id"	=> $employee_timein
							);
								
							$this->timeins->update_field("schedule_blocks_time_in",$fields,$where);
							
							// get the first and last blocks
							$yest_list = $this->elm->list_of_blocks(date("Y-m-d",strtotime($emp_schedule_date)),$this->emp_id,$this->work_schedule_id,$this->company_id);
							$first_sched = reset($yest_list);
							$last_sched = max($yest_list);
							
							// get all (employee_time_in_id) split info
							$get_split_timein_info_res = $this->employee->get_split_timein_info_res($this->emp_id,$this->company_id,$emp_time_in_id);
							$res_total_hrs_req = 0;
							$res_total_hrs = 0;
							$res_tardiness_min = 0;
							$res_undertime_min = 0;
							$res_overbreak_min = 0;
							$res_late_min = 0;
							
							if($get_split_timein_info_res) {
								foreach ($get_split_timein_info_res as $res) {
									$res_total_hrs_req += $res->change_log_total_hours_required;
									$res_total_hrs += $res->change_log_total_hours;
									$res_tardiness_min += $res->change_log_tardiness_min;
									$res_undertime_min += $res->change_log_undertime_min;
									$res_overbreak_min += $res->overbreak_min;
									$res_late_min += $res->late_min;
								}
							}
							
							if($schedule_blocks_id == $first_sched->schedule_blocks_id) {
								$fields = array(
										"date"					=> $emp_schedule_date,
										"corrected"				=> "Yes",
										"time_in"				=> $fritz_time_in,
										"total_hours"			=> $res_total_hrs,
										"total_hours_required"	=> $res_total_hrs_req,
										"tardiness_min"			=> $res_tardiness_min,
										"undertime_min"			=> $res_undertime_min,
										"overbreak_min"			=> $res_overbreak_min,
										"late_min"				=> $res_late_min,
								);
							
								$where = array(
										"employee_time_in_id"	=> $emp_time_in_id,
										"comp_id"				=> $this->company_id
								);
							
								$this->timeins->update_field("employee_time_in",$fields,$where);
							}
								
							if($schedule_blocks_id == $last_sched->schedule_blocks_id) {
								$fields = array(
										"date"					=> $emp_schedule_date,
										"corrected"				=> "Yes",
										"time_out"				=> $fritz_time_out,
										"total_hours"			=> $res_total_hrs,
										"total_hours_required"	=> $res_total_hrs_req,
										"tardiness_min"			=> $res_tardiness_min,
										"undertime_min"			=> $res_undertime_min,
										"overbreak_min"			=> $res_overbreak_min,
										"late_min"				=> $res_late_min,
								);
									
								$where = array(
										"employee_time_in_id"	=> $emp_time_in_id,
										"comp_id"				=> $this->company_id
								);
									
								$this->timeins->update_field("employee_time_in",$fields,$where);
							}
							
						} else {
							$fields = array(
									"date"					=> $emp_schedule_date,
									"time_in_status" 		=> $status,
									"corrected"				=> "Yes",
									"time_in"				=> $fritz_time_in,
									"lunch_out"				=> $fritz_lunch_out,
									"lunch_in"				=> $fritz_lunch_in,
									"time_out"				=> $fritz_time_out,
									"total_hours"			=> $nw_total_hours,
									"total_hours_required" 	=> $nw_total_hours_worked,
									"tardiness_min"			=> $nw_tardiness + $overbreak_min,
									"undertime_min"			=> $nw_undertime,
									'last_source'			=> "Adjusted",
									"late_min"				=> $nw_tardiness,
									"overbreak_min"			=> $overbreak_min
							);
								
							$where = array(
									"employee_time_in_id"	=> $emp_time_id,
									"comp_id"				=> $this->company_id
							);
								
							$this->timeins->update_field("employee_time_in",$fields,$where);
						}
					}
				}
			}else{
			    
			}
			//$save_token_q = $this->db->insert("approval_time_in",$save_token);
			// redirect(base_url().$this->uri->segment(1).'/employee/emp_time_in');

			echo json_encode(array("error" => 0,"result" => 0,"error_msg"=>"", "msgs" => ''));
			return false;
		}else{
			#print validation_errors();
			#return false;
			// redirect(base_url().$this->uri->segment(1).'/employee/emp_time_in');
			
			echo json_encode(array("error" => 1,"result" => 1,"error_msg"=>"Invalid time log details.", "msgs" => validation_errors()));
			return false;
		}
	
	}
	
	public function change_log_OLD(){
			
		$employee_timein = $this->input->post('employee_timein');
		$schedule_blocks_id = $this->input->post('schedule_blocks_id');
		
		$shift_date = date('Y-m-d', strtotime($this->input->post('shift_date')));
		$emp_schedule_date = date('Y-m-d', strtotime($this->input->post('emp_schedule_date')));
		$employee_timein_date = date("Y-m-d",strtotime($this->input->post('employee_timein_date')));
		$lunch_out_date = ($this->input->post('lunch_out_date') == "") ? "" : date("Y-m-d",strtotime($this->input->post('lunch_out_date')));
		$lunch_in_date = ($this->input->post('lunch_in_date') == "") ? "" : date("Y-m-d",strtotime($this->input->post('lunch_in_date')));
		$time_out_date = ($this->input->post('time_out_date') == "") ? "" : date("Y-m-d",strtotime($this->input->post('time_out_date')));
		
		$break_1_start_date = ($this->input->post('break_1_start_date') == "") ? "" : date("Y-m-d",strtotime($this->input->post('break_1_start_date')));
		$break_1_end_date = ($this->input->post('break_1_end_date') == "") ? "" : date("Y-m-d",strtotime($this->input->post('break_1_end_date')));
		$break_2_start_date = ($this->input->post('break_2_start_date') == "") ? "" : date("Y-m-d",strtotime($this->input->post('break_2_start_date')));
		$break_2_end_date = ($this->input->post('break_2_end_date') == "") ? "" : date("Y-m-d",strtotime($this->input->post('break_2_end_date')));
		
		$break_1_start_time = $this->input->post('break_1_start_time');
		$break_1_end_time = $this->input->post('break_1_end_time');
		$break_2_start_time = $this->input->post('break_2_start_time');
		$break_2_end_time = $this->input->post('break_2_end_time');

		$time_in_hr = $this->input->post('time_in_hr');
		$time_in_min = $this->input->post('time_in_min');
		$time_in_ampm = $this->input->post('time_in_ampm');
		
		$lunch_out_hr = $this->input->post('lunch_out_hr');
		$lunch_out_min = $this->input->post('lunch_out_min');
		$lunch_out_ampm = $this->input->post('lunch_out_ampm');
		
		$lunch_in_hr = $this->input->post('lunch_in_hr');
		$lunch_in_min = $this->input->post('lunch_in_min');
		$lunch_in_ampm = $this->input->post('lunch_in_ampm');
		
		$time_out_hr = $this->input->post('time_out_hr');
		$time_out_min = $this->input->post('time_out_min');
		$time_out_ampm = $this->input->post('time_out_ampm');
		
		$time_in = $employee_timein_date." ".date("H:i:s",strtotime($time_in_hr.":".$time_in_min." ".$time_in_ampm));
		$lunch_out = ($lunch_out_hr=="" || $lunch_out_min=="" || $lunch_out_ampm=="") ? "" : date("H:i:s",strtotime($lunch_out_hr.":".$lunch_out_min." ".$lunch_out_ampm));
		$lunch_in = ($lunch_in_hr=="" || $lunch_in_min=="" || $lunch_in_ampm=="") ? "" : date("H:i:s",strtotime($lunch_in_hr.":".$lunch_in_min." ".$lunch_in_ampm));
		$time_out = ($time_out_hr=="" || $time_out_min=="" || $time_out_ampm=="") ? "" : date("H:i:s",strtotime($time_out_hr.":".$time_out_min." ".$time_out_ampm));
		
		$new_time_in = $time_in;
		$new_lunch_out = ($lunch_out_date == "" || $lunch_out == "") ? NULL : $lunch_out_date." ".$lunch_out;
		$new_lunch_in = ($lunch_in_date == "" || $lunch_in == "") ? NULL : $lunch_in_date." ".$lunch_in;
		$new_time_out = ($time_out_date == "" || $time_out == "") ? NULL : $time_out_date." ".$time_out;

		$break_1_start_date_time = ($break_1_start_date == "" || $break_1_start_time == "") ? NULL : $break_1_start_date." ".$break_1_start_time;
		$break_2_start_date_time = ($break_2_start_date == "" || $break_2_start_time == "") ? NULL : $break_2_start_date." ".$break_2_start_time;
		
		$break_1_end_date_time = ($break_1_end_date == "" || $break_1_end_time == "") ? NULL : $break_1_end_date." ".$break_1_end_time;
		$break_2_end_date_time = ($break_2_end_date == "" || $break_2_end_time == "") ? NULL : $break_2_end_date." ".$break_2_end_time;
				
		$reason = $this->input->post('reason');
		
		$emp_no = $this->employee->check_emp_no($this->emp_id,$this->company_id);
		
		$flag_halfday = $this->input->post("flag_halfday");
		if($flag_halfday == 1){
			$new_lunch_out = NULL; 
			$new_lunch_in = NULL;
		}
		
		$check_if_excess_logs = $this->employee->check_if_excess_logs($this->emp_id,$this->company_id,$employee_timein);
		
		$excess_logs = false;
		if($check_if_excess_logs) {
			$excess_logs = true;
		}
		
		/*if($emp_schedule_date != null && $new_time_in != null && $new_time_out != null) {
			$check_overlapping_logs = $this->employee->check_overlapping_logs($this->emp_id,$this->company_id,$emp_schedule_date,$new_time_in,$new_time_out);
			if($check_overlapping_logs) {
				echo json_encode(array("result" => 0,"error_msg"=>"This timesheet has overlapped an existing timesheet. Please check you hours and try again."));
				return false;
			}
		}*/
		
		$employee_details = get_employee_details_by_empid($this->emp_id);
		
		$no_approver_msg_locked = "Payroll for the period affected is locked. No new timesheet requests can be accepted. Please reach out to your HR or payroll manager to discuss options for this request.";
		$no_approver_msg_closed = "Payroll for the period affected is closed. No new timesheet requests can be accepted. Please reach out to your HR or payroll manager to discuss options for this request.";
		$void = $this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($emp_schedule_date)));
		$locked = "";
		
		if(!$employee_details->overtime_approval_grp || !is_workflow_enabled($this->company_id)) {
		    if($void == "Waiting for approval"){
		        $locked = $no_approver_msg_locked;
		    } elseif ($void == "Closed") {
		        $locked = $no_approver_msg_closed;
		        
		    }
		    
		    if($locked != "") {
		        $result = array(
		            'result' => 0,
		            'error' => true,
		            'error_msg' => $locked,
		        );
		        
		        echo json_encode($result);
		        return false;
		    }
		}
		
		delete_approve_timein($employee_timein);
			
		// if one of the approver is inactive the approver group will automatically change to default (owner)
		change_approver_to_default($this->emp_id,$this->company_id,"attendance_adjustment_approval_grp",$this->account_id);
		
		//UPDATE EMPLOYEE LOGS
		
		//GETS WORK_SCHEDULE_ID
		$shift_date = $employee_timein_date;
		$this->work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$emp_schedule_date);
		$check_rest_day = $this->elm->check_rest_day(date("l",strtotime($emp_schedule_date)),$this->work_schedule_id,$this->company_id);
		$check_work_type = $this->employee->work_schedule_type($this->work_schedule_id, $this->company_id);
		$check_holiday = $this->employee->get_holiday_date($emp_schedule_date,$this->emp_id,$this->company_id);
		
		$late_min = $this->elm->late_min($this->company_id,$emp_schedule_date,$this->emp_id,$this->work_schedule_id,$emp_no,$new_time_in);
		$overbreak_min = $this->elm->overbreak_min($this->company_id,$employee_timein_date,$this->emp_id,$this->work_schedule_id,$new_lunch_out,$emp_no,$new_lunch_in);
		$get_employee_time_in = $this->employee->get_employee_time_in($employee_timein,$this->emp_id,$this->company_id);
		
		$absent_min = $this->elm->calculate_attendance($this->company_id,$new_time_in,$new_time_out);
		
		$your_total_hours = $this->elm->get_attendance_total_work_hours($this->emp_id,$this->company_id,$emp_schedule_date,$this->work_schedule_id);
			
		$fritz_tardiness = $late_min + $overbreak_min;
			
		$new_total_hours_worked = 0;
		$new_tardiness = 0;
		$new_undertime = 0;
		$your_new_total_hours = 0;
		
		if($get_employee_time_in) {
			if($get_employee_time_in->source != null) {
				$new_source = "last_source";
			} else {
				$new_source = "source";
			}
		} else {
			$new_source = "source";
		}
		
		//CHECK IF WORK SCHEDULE IS UNIFORM WORKING DAY / FLEXIBLE / SPLIT SCHEDULE
		if($check_work_type == "Uniform Working Days"){
			
			//CHECKS IF RESTDAY OR NOT
			#if($check_rest_day || $check_holiday){
			if($check_rest_day || $check_holiday || $excess_logs == true){
				$tardiness = 0;
				$undertime = 0;
					
				// update total hours and total hours required rest day
				$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
				
				// UPDATE TIME INS
				$where_tot = array(
						"comp_id"				=> $this->company_id,
						"emp_id"				=> $this->emp_id,
						"employee_time_in_id"	=> $employee_timein
				);
				$this->db->where($where_tot);
					
				$data_update = array(
        				    "work_schedule_id" 					=> $this->work_schedule_id,
        				    "comp_id"							=> $this->company_id,
        				    "emp_id"							=> $this->emp_id,
        				    "date"								=> $emp_schedule_date,
        				    "time_in_status"					=> 'pending',
        				    "corrected"							=> 'Yes',
        				    "reason"							=> $reason,
        				    "change_log_date_filed"				=> date("Y-m-d H:i:s"),
        				    "change_log_tardiness_min"			=> 0,
        				    "change_log_undertime_min"			=> 0,
        				    "change_log_time_in"				=> $new_time_in,
        				    "change_log_time_out"				=> $new_time_out,
        				    "change_log_total_hours"			=> $get_total_hours,
        				    "change_log_total_hours_required"	=> $get_total_hours,
        				    $new_source							=> "Adjusted",
        				    "tardiness_min"						=> 0,
        				    "late_min"							=> 0,
        				    "overbreak_min"						=> 0,
        				    "absent_min"						=> 0
				);
					
				$update_change_logs = $this->db->update('employee_time_in', $data_update);
				
				if($update_change_logs) {
					$new_total_hours_worked = $get_total_hours;
					$new_tardiness = $tardiness;
					$new_undertime = $undertime;
					$your_new_total_hours = $your_total_hours;
				} else {
					$new_total_hours_worked = 0;
					$new_tardiness = 0;
					$new_undertime = 0;
				}
					
			}elseif($new_lunch_out == NULL && $new_lunch_in == NULL && $flag_halfday == 1){
				//IF HALFDAY
				
				#$flag_halfday = 0;
				$flag_undertime = 0;
				$tardiness = 0;
				$undertime = 0;
				$total_hours = 0;
				$total_hours_required = 0;
				
				$check_hours_flexible = $this->elm->check_hours_flex($this->company_id,$this->work_schedule_id);
				$check_breaktime = $this->elm->check_breaktime($this->company_id,$this->work_schedule_id);
			
				$workday = date("l",strtotime($shift_date));
				
				$workday_settings_start_time = $this->elm->check_workday_settings_start_time($workday,$this->work_schedule_id,$this->company_id);
				$workday_settings_end_time = $this->elm->check_workday_settings_end_time($workday,$this->work_schedule_id,$this->company_id);
				//checks if latest timein is allowed
				
				if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
				
					// for night shift time in and time out value for working day
					$check_bet_timein = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
					$check_bet_timeout = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$workday_settings_end_time;
				}else{
				
					// for day shift time in and time out value for working day
					$check_bet_timein = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
					$check_bet_timeout = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_end_time;
				}
				// check between date time in to date time out
				$add_oneday_timein = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$workday_settings_start_time;
					
				
				/* EMPLOYEE WORK SCHEDULE */
				$wd_start = $this->elm->get_workday_sched_start($this->company_id,$this->work_schedule_id);
				$wd_end = $this->elm->get_end_time($this->company_id,$this->work_schedule_id);
						
				if($check_breaktime != FALSE){
					$total_work_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($time_in)),$this->work_schedule_id);
					$new_latest_timein_allowed = $this->employee->new_latest_timein_allowed($this->emp_id,$this->company_id,$this->work_schedule_id,"work_schedule");
					$new_st = $this->employee->for_leave_hoursworked_work_start_time_ws($this->emp_id,$this->company_id,$this->work_schedule_id);
					if($new_latest_timein_allowed){ // if latest time in is true
						if(strtotime($time_in) < strtotime($new_st)){
							$new_work_start_time = $new_st;
						}elseif(strtotime($new_st) <= strtotime($time_in) && strtotime($time_in) <= strtotime($new_latest_timein_allowed)){
							$new_work_start_time = $concat_start_datetime;
						}elseif(strtotime($time_in) > strtotime($new_latest_timein_allowed)){
							$new_work_start_time = $new_latest_timein_allowed;
						}
					}
					$end_time = date('H:i:s', strtotime($new_work_start_time.' +'.$total_work_hours.' hours'));
					$check_time = (strtotime($end_time) - strtotime($new_work_start_time) ) / 3600;
					$check2 = $check_time / 2;
					$check2 = round($check2, 0, PHP_ROUND_HALF_DOWN);
					
					//$hd_check = strtotime($wd_start) + $check_time;
					$hd_check = date('H:i:s', strtotime($new_work_start_time.' +'.$check2.' hours'));
					$hd_check = strtotime($wd_start) + $check_time;
					
					$b_st = $check_breaktime->work_start_time;
					$b_et = $check_breaktime->work_end_time;
					$now_date = date("Y-m-d H:i:s",strtotime($new_time_out));
					$now_time = date("H:i:s",strtotime($new_time_out));
							
					// FLAG
					if((strtotime($check_bet_timeout) >= strtotime($new_time_in) && strtotime($new_time_in) <= strtotime($add_oneday_timein)) && (date("A",strtotime($wd_start)) != "PM" && date("A",strtotime($wd_end)) != "AM")){ 
						// FOR DAY SHIFT
					
						if(strtotime($hd_check) <= strtotime(date("H:i:s",strtotime($new_time_in)))){
							$flag_halfday = 1; // FOR HALFDAY AFTERNOON
							
						}elseif(strtotime(date("H:i:s",strtotime($new_time_out))) <= strtotime($hd_check)){
							$flag_halfday = 2; // FOR HALFDAY MORNING
							
						}
								

						//print "{$new_time_out} - {$b_st}";
					}else{
						// FOR NIGHT SHIFT
						$new_date_timein = date("Y-m-d H:i:s",strtotime($check_bet_timein." -1 day"));
						$new_date_timeout = date("Y-m-d",strtotime($new_time_in))." ".date("H:i:s",strtotime($add_oneday_timein));
						//if(strtotime($new_date_timein) <= strtotime($new_time_in) && strtotime($new_time_in) <= strtotime($new_date_timeout)) $flag_halfday = 1;
						
						if(date("A",strtotime($new_time_in)) == "AM"){
							if(strtotime(date("Y-m-d",strtotime($new_time_in))." ".$hd_check) <= strtotime($new_date_timeout) && strtotime(date("Y-m-d",strtotime($new_time_in))) <= strtotime($new_date_timeout)){
								$flag_halfday = 1;	
							}
						}else{
							if(strtotime(date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$hd_check) >= strtotime($new_time_in) && strtotime(date("Y-m-d",strtotime($new_time_in))) <= strtotime($new_date_timeout)){
								$flag_halfday = 2;
							}
						}
					}
				}else{
					// show_error("Payroll set up for break time is empty.");
					
					/* FOR UNIFORM WORKING DAYS AND  ZERO BREAK TIME */
					$flag_halfday = 3;
				}
						
				if($flag_halfday == 1){

					if(date("A",strtotime($wd_start)) != "PM" && date("A",strtotime($wd_end)) != "AM"){ // DAY SHIFT TRAPPING
									
						$tardiness_b = (strtotime($b_et) < strtotime(date("H:i:s",strtotime($new_time_in)))) ? (strtotime(date("H:i:s",strtotime($new_time_in))) - strtotime($b_et)) / 60 : 0 ; // tardiness for time in breaktime
					
						$tardiness = $tardiness_b;
						$tardiness = 0;
						// GET END TIME FOR TIME OUT
						$get_end_time = $this->elm->get_end_time($this->company_id,$this->work_schedule_id);
						
							
						// FOR UNDERTIME 											>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						if($get_end_time != FALSE){
							if(strtotime($now_time) < strtotime($get_end_time)) $undertime = (strtotime($get_end_time) - strtotime($now_time)) / 60;
						}else{
							echo json_encode(array("result"=>2,"error_msg"=>"- Payroll set up for break time is empty."));
							return false;
						}
							
					}else{ // NIGHT SHIFT TRAPPING
						$new_end_date = date("Y-m-d",strtotime(date("Y-m-d",strtotime($new_time_out))." -1 day"))." ".$b_et;
						$now_date = date("Y-m-d H:i:s",strtotime($new_time_out));
							
						$new_breaktime_start = date("Y-m-d",strtotime($new_time_out))." ".$b_st;
							
						if($check_hours_flexible != FALSE){
							$tardiness_a = (strtotime($new_breaktime_start) - strtotime(date("Y-m-d",strtotime($new_date_timein))." ".$check_hours_flexible)) / 60; // time start - breaktime end time (tardiness for start time)
						}else{
							$tardiness_a = (strtotime($new_breaktime_start) - strtotime($new_date_timein)) / 60; // time start - breaktime end time (tardiness for start time)
						}
							
						$tardiness_b = (strtotime($b_et) < strtotime(date("H:i:s",strtotime($new_time_in)))) ? (strtotime(date("H:i:s",strtotime($new_time_in))) - strtotime($b_et)) / 60 : 0 ; // tardiness for time in breaktime
							
						$tardiness = $tardiness_b;
						$tardiness = 0;
						// GET END TIME FOR TIME OUT
							$get_end_time = $this->elm->get_end_time($this->company_id,$this->work_schedule_id);
						
							
						// FOR UNDERTIME 											>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						if($get_end_time != FALSE){
							if(strtotime($now_time) < strtotime($get_end_time)) $undertime = (strtotime($get_end_time) - strtotime($now_time)) / 60;
						}else{
							echo json_encode(array("result"=>2,"error_msg"=>"- Payroll set up for break time is empty."));
							return false;
						}
					}
					
					// FOR TOTAL HOURS										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
					if($undertime == 0){
						$total_hours = 	(strtotime($get_end_time) - strtotime(date("H:i:s",strtotime($new_time_in)))) / 3600;
					}else{
						$total_hours = 	(strtotime($now_time) - strtotime(date("H:i:s",strtotime($new_time_in)))) / 3600;
					}
					
					// FOR TOTAL HOURS REQUIRED								>>>>>>>>>>>>>>>>>>>>>>>>>>>>
					
					$total_hours_required = (strtotime($now_time) - strtotime(date("H:i:s",strtotime($new_time_in)))) / 3600;
						
				}elseif($flag_halfday == 2){
					$undertime_a = 0;
					$undertime_b = 0;
							
					if(date("A",strtotime($wd_start)) != "PM" && date("A",strtotime($wd_end)) != "AM"){ // DAY SHIFT TRAPPING
							
						// FOR UNDERTIME 										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						$undertime_a = (strtotime($workday_settings_end_time) - strtotime($b_et)) / 60; // time start - breaktime end time (tardiness for start time)
							
						// FOR UNDERTIME										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						if(strtotime($now_time) < strtotime($b_st)) $undertime_b = (strtotime($b_st) - strtotime($now_time)) / 60;
					
						$undertime = $undertime_a + $undertime_b;
							
						
						// GET END TIME FOR TIME OUT
						$get_end_time = $this->elm->get_end_time($this->company_id,$this->work_schedule_id);
				
						// GET LATEST TIME IN ALLOWED VAL
						$get_latest_timein_allowed_val = $this->employee->get_latest_timein_allowed_val($this->company_id,$this->work_schedule_id,"work_schedule_id");
					
							
						$st_for_tardiness = (!$get_latest_timein_allowed_val) ? $workday_settings_start_time : date('H:i:s',strtotime($workday_settings_start_time.' +'.$get_latest_timein_allowed_val.' minutes')) ;
							
						// FOR TARDINESS 											>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						if($get_end_time != FALSE){
						    if(strtotime($st_for_tardiness) < strtotime(date("H:i:s",strtotime($new_time_in)))) $tardiness = (strtotime(date("H:i:s",strtotime($new_time_in))) - strtotime($st_for_tardiness)) / 60;
						}else{
							//show_error("Payroll set up for break time is empty.");
							echo json_encode(array("result"=>2,"error_msg"=>"- Payroll set up for break time is empty."));
							return false;
						}
							
						// calculate total hours
						$th = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
						$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id);
							
						// new undertime calculation
						$undertime = (($hw - $th) * 60) - $tardiness;
						$undertime = 0;
						
					}else{ // NIGHT SHIFT TRAPPING
									
						// FOR TARDINESS										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						if($check_hours_flexible != FALSE){
							//$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$check_hours_flexible;
							$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$check_hours_flexible;
							$tardiness_b = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
						}else{
							//$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$workday_settings_start_time;
							$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
							$tardiness_b = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
						}
							
						$tardiness = $tardiness_b;
							
						// GET END TIME FOR TIME OUT
						
						$get_end_time = $this->elm->get_end_time($this->company_id,$this->work_schedule_id);
						
							
						/* version 1.0 */
							
						// FOR UNDERTIME 											>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						if($get_end_time != FALSE){
							$u_add_oneday_timein = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$b_st;
							if(strtotime($new_time_out) < strtotime($u_add_oneday_timein)) $undertime = (strtotime($u_add_oneday_timein) - strtotime($new_time_out)) / 60;
						}else{
							//show_error("Payroll set up for break time is empty.");
							echo json_encode(array("result"=>2,"error_msg"=>"- Payroll set up for break time is empty."));
							return false;
						}
							
						/* version 1.1 */
							
						// calculate total hours
						$th = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
						$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id);
							
						// new undertime calculation
						$undertime = (($hw - $th) * 60) - $tardiness;
						$undertime = 0;
					}
							
					// FOR TOTAL HOURS										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
					if($undertime == 0){
						$u_add_oneday_timein = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$b_st;
						
						$total_hours = 	(strtotime($new_time_out) - ($check_bet_timein > $new_time_in ? strtotime($check_bet_timein): strtotime($new_time_in))) / 3600;
					}else{
						$total_hours = 	(strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
					}
							
					// FOR TOTAL HOURS REQUIRED								>>>>>>>>>>>>>>>>>>>>>>>>>>>>
					$total_hours_required = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
							
								
				}elseif($flag_halfday == 3){
					$get_different = ($this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id)) / 2;
					$get_between = date("Y-m-d H:i:s",strtotime(date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time." + {$get_different} hour"));
							
					/* DAY SHIFT TRAPPING */
					
					if(strtotime($get_between) < strtotime($new_time_in)){
							
						/* for afternoon */
							
						// get undertime
						if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
							$undertime = (strtotime($workday_settings_end_time) - strtotime($new_time_out)) / 60;
						}
							
						// calculate total hours
						$newtimein = (strtotime($new_time_in) < strtotime($get_between)) ? $get_between : $new_time_in ;
						$th = (strtotime($new_time_out) - strtotime($newtimein)) / 3600;
						$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id);
							
						// new tardiness calculation
						$tardiness = (($hw - $th) * 60) - $undertime;
						$tardiness = 0;
						// total hours
						$total_hours = 	(strtotime($new_time_out) - strtotime($newtimein)) / 3600;
						$total_hours_required = (strtotime($new_time_out) - strtotime($newtimein)) / 3600;
							
					}else{
						/* for morning */
						// get tardiness
						if(strtotime($workday_settings_start_time) < strtotime($new_time_in)){
							$tardiness = (strtotime($new_time_in) - strtotime($workday_settings_start_time)) / 60;
						}
							
						if($check_hours_flexible != FALSE){
							$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$check_hours_flexible;
							$tardiness = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
						}else{
							$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
							$tardiness = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
						}
							
						// calculate total hours
						$newtimeout = (strtotime($get_between) < strtotime($new_time_out)) ? $get_between : $new_time_out ;
						$th = (strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
						$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id);
									
						// new tardiness calculation
						$undertime = (($hw - $th) * 60) - $tardiness;
						$undertime = 0;
						// total hours
						$total_hours = 	(strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
						$total_hours_required = (strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
							
					}
						
				}
				
				$new_date = date("Y-m-d",strtotime($new_time_in." -1 day"));
				$check_workday = $this->elm->halfday_check_workday($this->work_schedule_id,$this->company_id,$new_date);
				$hours_worked = $this->elm->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id,$this->work_schedule_id);
				$payroll_period = $this->employee->get_payroll_period($this->company_id);
				
				if($check_workday){
					// minus 1 day
					$period_to = $payroll_period->period_to;
					$date_halfday = (strtotime($period_to) == strtotime($new_date)) ? $period_to : NULL ;
				}else{
					$date_halfday = NULL;
				}
				
				$get_total_hours = $total_hours;
				
				if($absent_min){
					$total_absent_min = ($your_total_hours - $total_hours_required) * 60;
				} else {
					$total_absent_min = 0;
				}
						
				// TOTAL HOURS
				// UPDATE TIME INS
				$where_tot = array(
						"comp_id"				=> $this->company_id,
						"emp_id"				=> $this->emp_id,
						"employee_time_in_id"	=> $employee_timein
				);
				$this->db->where($where_tot);
							
    				$data_update = array(
        				    "work_schedule_id" 					=> $this->work_schedule_id,
        				    "comp_id"							=> $this->company_id,
        				    "emp_id"							=> $this->emp_id,
        				    "date"								=> $emp_schedule_date,
        				    "time_in_status"					=> 'pending',
        				    "corrected"							=> 'Yes',
        				    "reason"							=> $reason,
        				    "change_log_date_filed"				=> date("Y-m-d H:i:s"),
        				    "change_log_tardiness_min"			=> $fritz_tardiness,
        				    "change_log_undertime_min"			=> $undertime,
        				    "change_log_time_in"				=> $new_time_in,
        				    "change_log_lunch_out"				=> null,
        				    "change_log_lunch_in"				=> null,
        				    "change_log_time_out"				=> $new_time_out,
        				    "change_log_total_hours"			=> $your_total_hours,
        				    "change_log_total_hours_required"	=> $total_hours_required,
        				    "flag_halfday"						=> 1,
        				    #"date_halfday"						=> $date_halfday,
        				    $new_source							=> "Adjusted",
        				    "late_min"							=> $late_min,
        				    "overbreak_min"						=> $overbreak_min,
        				    "absent_min"						=> $total_absent_min
				);
							
				$update_change_logs = $this->db->update('employee_time_in', $data_update);
				
				if($update_change_logs) {
					$new_total_hours_worked = $total_hours_required;
					$new_tardiness = $fritz_tardiness;
					$new_undertime = $undertime;
					$your_new_total_hours = $your_total_hours;
				} else {
					$new_total_hours_worked = 0;
					$new_tardiness = 0;
					$new_undertime = 0;
					$your_new_total_hours = 0;
				}
						
			}else{
				// check employee work schedule
				// check break time
				$check_break_time = $this->employee->check_break_time($this->work_schedule_id,$this->company_id,"work_schedule_id", $emp_schedule_date);	
						
				if(!$check_break_time){ // ZERO VALUE FOR BREAK TIME
					// update total hours and total hours required rest day
					$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
											
					/* EMPLOYEE WORK SCHEDULE */
					$number_of_breaks_per_day = $this->elmf->check_break_time_flex($this->work_schedule_id,$this->company_id);
					
					// tardiness and undertime value
					$update_tardiness = $this->elm->get_tardiness_val($this->emp_id,$this->company_id,$new_time_in,$this->work_schedule_id,$number_of_breaks_per_day);
					$update_undertime = $this->elm->get_undertime_val($this->emp_id,$this->company_id,$new_time_in,$new_time_out,$this->work_schedule_id,$number_of_breaks_per_day);
					
					// hours worked
					$hours_worked = $this->elm->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
					
					// check tardiness value
					$flag_tu = 0;
					
					$get_total_hours_worked = ($hours_worked / 2) + .5;
					if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
						$update_tardiness = 0;
						$update_undertime = 0;
						$flag_tu = 1;
					}
					
					// required hours worked only
					$new_total_hours = $this->elm->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out,$hours_worked,$this->work_schedule_id);
					
					if($absent_min){
						$total_absent_min = ($your_total_hours - $get_total_hours) * 60;
					} else {
						$total_absent_min = 0;
					}
					
					// UPDATE TIME INS
					$where_tot = array(
						"comp_id"				=> $this->company_id,
						"emp_id"				=> $this->emp_id,
						"employee_time_in_id"	=> $employee_timein
					);
					$this->db->where($where_tot);
					
					$data_update = array(
        					    "work_schedule_id" 					=> $this->work_schedule_id,
        					    "comp_id"							=> $this->company_id,
        					    "emp_id"							=> $this->emp_id,
        					    "date"								=> $emp_schedule_date,
        					    "time_in_status"					=> 'pending',
        					    "corrected"							=> 'Yes',
        					    "reason"							=> $reason,
        					    "flag_tardiness_undertime"			=> $flag_tu,
        					    "absent_min" 						=> 0,
        					    "change_log_date_filed"				=> date("Y-m-d H:i:s"),
        					    "change_log_tardiness_min"			=> $fritz_tardiness,
        					    "change_log_undertime_min"			=> $update_undertime,
        					    "change_log_time_in"				=> $new_time_in,
        					    "change_log_time_out"				=> $new_time_out,
        					    "change_log_total_hours"			=> $your_total_hours,
        					    "change_log_total_hours_required"	=> $get_total_hours,
        					    "flag_halfday"						=> 1,
        					    $new_source							=> "Adjusted",
        					    "late_min"							=> $late_min,
        					    "overbreak_min"						=> $overbreak_min,
        					    "absent_min"						=> $total_absent_min
        					    // "date_halfday"=>$date_halfday
					);
					
					$update_change_logs = $this->db->update('employee_time_in', $data_update);
					
					if($update_change_logs) {
						$new_total_hours_worked = $get_total_hours;
						$new_tardiness = $fritz_tardiness;
						$new_undertime = $update_undertime;
						$your_new_total_hours = $your_total_hours;
					} else {
						$new_total_hours_worked = 0;
						$new_tardiness = 0;
						$new_undertime = 0;
						$your_new_total_hours = 0;
					}
					
				}else if($new_time_in != "" && $new_time_out != "" && $new_lunch_in != "" && $new_lunch_out != ""){
					// WHOLEDAY
					$hours_worked = $this->elm->get_hours_worked(date("Y-m-d",strtotime($emp_schedule_date)), $this->emp_id, $this->work_schedule_id);
													
					/* EMPLOYEE WORK SCHEDULE */
					$update_change_logs = $this->elm->update_change_logs($this->company_id, $this->emp_id,$employee_timein, $reason, $new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out, $hours_worked,$this->work_schedule_id,$new_source,$late_min,$overbreak_min,$emp_schedule_date);
					
					if($update_change_logs) {
						$new_total_hours_worked = $update_change_logs->change_log_total_hours_required;
						$new_tardiness = $update_change_logs->change_log_tardiness_min;
						$new_undertime = $update_change_logs->change_log_undertime_min;
						$your_new_total_hours = $update_change_logs->change_log_total_hours;
					} else {
						$new_total_hours_worked = 0;
						$new_tardiness = 0;
						$new_undertime = 0;
						$your_new_total_hours = 0;
					}
					
				}
			}
				
		}elseif($check_work_type == "Flexible Hours"){
			if($check_rest_day || $check_holiday || $excess_logs == true){
				$tardiness = 0; 
				$undertime = 0;
				
				// update total hours and total hours required rest day
				$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
				
				// UPDATE TIME INS
				$where_tot = array(
					"comp_id"				=> $this->company_id,
					"emp_id"				=> $this->emp_id,
					"employee_time_in_id"	=> $employee_timein
				);
				$this->db->where($where_tot);
				
				$data_update = array(
        				    "work_schedule_id" 					=> $this->work_schedule_id,
        				    "comp_id"							=> $this->company_id,
        				    "emp_id"							=> $this->emp_id,
        				    "date"								=> $emp_schedule_date,
        				    "time_in_status"					=> 'pending',
        				    "corrected"							=> 'Yes',
        				    "reason"							=> $reason,
        				    "change_log_date_filed"				=> date("Y-m-d H:i:s"),
        				    "change_log_tardiness_min"			=> $tardiness,
        				    "change_log_undertime_min"			=> $undertime,
        				    "change_log_time_in"				=> $new_time_in,
        				    "change_log_time_out"				=> $new_time_out,
        				    "change_log_total_hours"			=> $get_total_hours,
        				    "change_log_total_hours_required"	=> $get_total_hours,
        				    $new_source							=> "Adjusted",
        				    "late_min"							=> 0,
        				    "overbreak_min"						=> 0
				);
				
				$update_change_logs = $this->db->update('employee_time_in', $data_update);
				
				if($update_change_logs) {
					$new_total_hours_worked = $get_total_hours;
					$new_tardiness = $tardiness;
					$new_undertime = $undertime;
					$your_new_total_hours = $your_total_hours;
				} else {
					$new_total_hours_worked = 0;
					$new_tardiness = 0;
					$new_undertime = 0;
					$your_new_total_hours = 0;
				}
				
			}else if($new_lunch_out == NULL && $new_lunch_in == NULL && $flag_halfday == 1){
				// EMPLOYEE WORK SCHEDULE
				$check_hours_flexible = $this->elm->check_hours_flex($this->company_id,$this->work_schedule_id);
				//$check_breaktime = $this->elm->check_breaktime($this->company_id,$this->work_schedule_id);
						
				$get_hoursworked = $this->elmf->get_hoursworked($this->work_schedule_id,$this->company_id)->total_hours_for_the_day;
				$check_latest_timein_allowed = $this->elmf->check_lastest_timein_allowed($this->work_schedule_id,$this->company_id);
				
				if(!$check_latest_timein_allowed){
					// check workday settings
					$workday_settings_start_time = date("H:i:s",strtotime($new_time_in));
					$workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
				
				}else{
					// check workday settings
					$workday_settings_start_time = date("H:i:s",strtotime($check_latest_timein_allowed->latest_time_in_allowed));
					$workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
				
				}
						
				$get_different = ($this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id)) / 2;
				$get_between = date("Y-m-d H:i:s",strtotime(date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time." + {$get_different} hour"));
						
				if(strtotime($get_between) < strtotime($new_time_in)){
				
					/* for afternoon */
					// get undertime
					if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
						$undertime = (strtotime($workday_settings_end_time) - strtotime($new_time_out)) / 60;
					}
				
					// calculate total hours
					$newtimein = (strtotime($new_time_in) < strtotime($get_between)) ? $get_between : $new_time_in ;
					$th = (strtotime($new_time_out) - strtotime($newtimein)) / 3600;
					$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id);
				
					// new tardiness calculation
					$tardiness = (($hw - $th) * 60) - $undertime;
				
					// total hours
					$total_hours = 	(strtotime($new_time_out) - strtotime($newtimein)) / 3600;
					$total_hours_required = (strtotime($new_time_out) - strtotime($newtimein)) / 3600;
				
				}else{
					/* for morning */
					// get tardiness
					if(strtotime($workday_settings_start_time) < strtotime($new_time_in)){
						$tardiness = (strtotime($new_time_in) - strtotime($workday_settings_start_time)) / 60;
					}
				
					if($check_hours_flexible != FALSE){
						$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$check_hours_flexible;
						$tardiness = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
					}else{
						$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
						$tardiness = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
					}
				
					// calculate total hours
					$newtimeout = (strtotime($get_between) < strtotime($new_time_out)) ? $get_between : $new_time_out ;
					$th = (strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
					$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id);
				
					// new tardiness calculation
					$undertime = (($hw - $th) * 60) - $tardiness;
				
					// total hours
					$total_hours = 	(strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
					$total_hours_required = (strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
				
				}
				// UPDATE TIME INS
				$where_tot = array(
						"comp_id"				=> $this->company_id,
						"emp_id"				=> $this->emp_id,
						"employee_time_in_id"	=> $employee_timein
				);
				$this->db->where($where_tot);
					
				$data_update = array(
        				    "work_schedule_id" 					=> $this->work_schedule_id,
        				    "comp_id"							=> $this->company_id,
        				    "emp_id"							=> $this->emp_id,
        				    "date"								=> $emp_schedule_date,
        				    "time_in_status"					=> 'pending',
        				    "corrected"							=> 'Yes',
        				    "reason"							=> $reason,
        				    "change_log_date_filed"				=> date("Y-m-d H:i:s"),
        				    //"change_log_tardiness_min"		=> $tardiness,
        				    "change_log_tardiness_min"			=> 0,
        				    "change_log_undertime_min"			=> $undertime,
        				    "change_log_time_in"				=> $new_time_in,
        				    "change_log_time_out"				=> $new_time_out,
        				    "change_log_total_hours"			=> $your_total_hours,
        				    "change_log_total_hours_required"	=> $total_hours_required,
        				    "flag_halfday"						=> 1,
        				    $new_source							=> "Adjusted",
        				    "late_min"							=> $late_min,
        				    "overbreak_min"						=> $overbreak_min
				);
					
				$update_change_logs = $this->db->update('employee_time_in', $data_update);
				
				if($update_change_logs) {
					$new_total_hours_worked = $total_hours_required;
					$new_tardiness = 0;
					$new_undertime = $undertime;
					$your_new_total_hours = $your_total_hours;
				} else {
					$new_total_hours_worked = 0;
					$new_tardiness = 0;
					$new_undertime = 0;
					$your_new_total_hours = 0;
				}
				
			}else{
				$get_flex_sched = $this->elmf->get_hoursworked($this->work_schedule_id,$this->company_id);
				if($get_flex_sched) {
					/* WHOLE DAY */
					$get_hoursworked = $get_flex_sched->total_hours_for_the_day;
					// check workday settings
					$workday_settings_start_time = date("Y-m-d", strtotime($emp_schedule_date)).' '.date("H:i:s",strtotime($get_flex_sched->latest_time_in_allowed));
					$workday_settings_end_time = date("Y-m-d H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
					$check_latest_timein_allowed = $this->elmf->check_lastest_timein_allowed($this->work_schedule_id,$this->company_id);
					
					$workday_settings_end_time_fritz = $workday_settings_end_time;
					if(!$check_latest_timein_allowed){
						$number_of_breaks_per_day = $this->elmf->check_break_time_flex($this->work_schedule_id,$this->company_id);
						if($number_of_breaks_per_day == 0){
							// update total hours and total hours required rest day
							$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
								
							// update total tardiness
							$update_tardiness = 0;
						
							// update undertime
							$update_undertime = 0;
							if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime(date("H:i:s",strtotime($new_time_in)))){
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
								$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
							}
							
							if(strtotime(date("Y-m-d H:i:s",strtotime($new_time_out))) < strtotime(date("Y-m-d H:i:s",strtotime($workday_settings_end_time_fritz)))){
								$new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
								$update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
							}
						
							// check tardiness value
							$flag_tu = 0;
						
							$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
							$get_total_hours_worked = ($hours_worked / 2) + .5;
							if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
								$update_tardiness = 0;
								$update_undertime = 0;
								$flag_tu = 1;
							}
						
							// required hours worked only
							$new_total_hours = $this->elmf->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_time_out,$hours_worked,$this->work_schedule_id,$number_of_breaks_per_day);
						
							// if value is less then 0 then set value to 0
							if($update_tardiness < 0) $update_tardiness = 0;
							if($update_undertime < 0) $update_undertime = 0;
							if($new_total_hours < 0) $new_total_hours = 0;
							if($get_total_hours < 0) $get_total_hours = 0;
						
							// UPDATE TIME INS
							$where_tot = array(
        							    "comp_id"=>$this->company_id,
        							    "emp_id"=>$this->emp_id,
        							    "employee_time_in_id"=>$employee_timein
							);
							$this->db->where($where_tot);
						
							$data_update = array(
        							    "work_schedule_id" 					=> $this->work_schedule_id,
        							    "comp_id"							=> $this->company_id,
        							    "emp_id"							=> $this->emp_id,
        							    "date"								=> $emp_schedule_date,
        							    "time_in_status"					=> 'pending',
        							    "corrected"							=> 'Yes',
        							    "reason"							=> $reason,
        							    "change_log_date_filed"				=> date("Y-m-d H:i:s"),
        							    "change_log_tardiness_min"			=> $update_tardiness,
        							    "change_log_undertime_min"			=> $update_undertime,
        							    "change_log_time_in"				=> $new_time_in,
        							    "change_log_time_out"				=> $new_time_out,
        							    "change_log_total_hours"			=> $your_total_hours,
        							    "change_log_total_hours_required"	=> $get_total_hours,
        							    $new_source							=> "Adjusted",
        							    "late_min"							=> $late_min,
        							    "overbreak_min"						=> $overbreak_min
							);
						
							$update_change_logs = $this->db->update('employee_time_in', $data_update);
							
							if($update_change_logs) {
								$new_total_hours_worked = $get_total_hours;
								$new_tardiness = $update_tardiness;
								$new_undertime = $update_undertime;
								$your_new_total_hours = $your_total_hours;
							} else {
								$new_total_hours_worked = 0;
								$new_tardiness = 0;
								$new_undertime = 0;
								$your_new_total_hours = 0;
							}
							
						}else{
							// update tardiness for timein
							$tardiness_timein = 0;
						
							// update tardiness for break time
							$update_tardiness_break_time = 0;
							$duration_of_lunch_break_per_day = $this->elmf->duration_of_lunch_break_per_day($this->emp_id, $this->company_id, $this->work_schedule_id);
							$tardiness_a = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
							if($duration_of_lunch_break_per_day < $tardiness_a){
								$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
							}
						
							// update total tardiness
							$update_tardiness = $tardiness_timein + $update_tardiness_break_time;
						
							// update undertime
							$update_undertime = 0;
							if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
								$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
							}
							if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
								$new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
								$update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
							}
						
							// update total hours
							$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
							$update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - ($duration_of_lunch_break_per_day / 60);
						
							// check tardiness value
							$get_total_hours_worked = ($hours_worked / 2) + .5;
							if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
								$update_tardiness = 0;
								$update_undertime = 0;
								$flag_tu = 1;
							}
						
							// update total hours required
							$update_total_hours_required = ((strtotime($new_time_out) - strtotime($new_time_in)) / 3600) - ($update_tardiness / 60) - ($duration_of_lunch_break_per_day / 60);
						
							// if value is less then 0 then set value to 0
							if($update_tardiness < 0) $update_tardiness = 0;
							if($update_undertime < 0) $update_undertime = 0;
							if($update_total_hours < 0) $update_total_hours = 0;
							if($update_total_hours_required < 0) $update_total_hours_required = 0;
						
							// UPDATE TIME INS
							$where_tot = array(
									"comp_id"				=> $this->company_id,
									"emp_id"				=> $this->emp_id,
									"employee_time_in_id"	=> $employee_timein
							);
							$this->db->where($where_tot);
						
							$data_update = array(
        							    "work_schedule_id" 					=> $this->work_schedule_id,
        							    "comp_id"							=> $this->company_id,
        							    "emp_id"							=> $this->emp_id,
        							    "date"								=> $emp_schedule_date,
        							    "time_in_status"					=> 'pending',
        							    "corrected"							=> 'Yes',
        							    "reason"							=> $reason,
        							    "change_log_date_filed"				=> date("Y-m-d H:i:s"),
        							    "change_log_tardiness_min"			=> $update_tardiness,
        							    "change_log_undertime_min"			=> $update_undertime,
        							    "change_log_time_in"				=> $new_time_in,
        							    "change_log_lunch_out"				=> $new_lunch_out,
        							    "change_log_lunch_in"				=> $new_lunch_in,
        							    "change_log_time_out"				=> $new_time_out,
        							    "change_log_total_hours"			=> $your_total_hours,
        							    "change_log_total_hours_required"	=> $update_total_hours_required,
        							    $new_source							=> "Adjusted",
        							    "late_min"							=> $late_min,
        							    "overbreak_min"						=> $overbreak_min
							);
							$update_change_logs = $this->db->update('employee_time_in', $data_update);
							
							if($update_change_logs) {
								$new_total_hours_worked = $update_total_hours_required;
								$new_tardiness = $update_tardiness;
								$new_undertime = $update_undertime;
								$your_new_total_hours = $your_total_hours;
							} else {
								$new_total_hours_worked = 0;
								$new_tardiness = 0;
								$new_undertime = 0;
								$your_new_total_hours = 0;
							}
						}
					}else{
						#$insert_time_in = $this->elmf->insert_time_in($date,$emp_no,$this->min_log,$emp_work_schedule_id);
						$number_of_breaks_per_day = $this->elmf->check_break_time_flex($this->work_schedule_id,$this->company_id);
						if($number_of_breaks_per_day == 0){
							
							// update total hours and total hours required rest day
							$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
							
							// update tardiness for timein
							$tardiness_timein = 0;
							if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
								if(date("A",strtotime($new_time_in)) == "AM"){
									// add one day for time in log
									$new_start_timein = date("Y-m-d",strtotime($new_time_in." -1 day"));
									$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
									
									if(strtotime($new_start_timein) < strtotime($new_time_in)){
										$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;			
									}
								}
							}else{
								$new_start_timein = date("Y-m-d",strtotime($new_time_in));
								$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
								
								if(strtotime($new_start_timein) < strtotime($new_time_in)){
									$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;			
								}
							}
	
							// update total tardiness
							$update_tardiness = $tardiness_timein;
							
							// update undertime
							$update_undertime = 0;
							if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($emp_schedule_date)), $this->emp_id, $this->work_schedule_id);
								$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
							}
							
							
							if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
								$new_end_time = date("Y-m-d",strtotime($emp_schedule_date))." ".$workday_settings_end_time;
								$update_undertime = (strtotime($workday_settings_end_time) - strtotime($new_time_out)) / 60;
							}
							
							// check tardiness value
							$flag_tu = 0;
							
							$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
							$get_total_hours_worked = ($hours_worked / 2) + .5;
							if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
								$update_tardiness = 0;
								$update_undertime = 0;
								$flag_tu = 1;
							}
							
							// required hours worked only
							$new_total_hours = $this->elmf->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_time_out,$hours_worked,$this->work_schedule_id,$number_of_breaks_per_day);
							
							// if value is less than 0 then set value to 0
							if($update_tardiness < 0) $update_tardiness = 0;
							if($update_undertime < 0) $update_undertime = 0;
							if($new_total_hours < 0) $new_total_hours = 0;
							if($get_total_hours < 0) $get_total_hours = 0;										
							
							// UPDATE TIME INS
							$where_tot = array(
								"comp_id"				=> $this->company_id,
								"emp_id"				=> $this->emp_id,
								"employee_time_in_id"	=> $employee_timein
							);
							$this->db->where($where_tot);
							
							$data_update = array(
        							    "work_schedule_id" 					=> $this->work_schedule_id,
        							    "comp_id"							=> $this->company_id,
        							    "emp_id"							=> $this->emp_id,
        							    "date"								=> $emp_schedule_date,
        							    "time_in_status"					=> 'pending',
        							    "corrected"							=> 'Yes',
        							    "reason"							=> $reason,
        							    "change_log_date_filed"				=> date("Y-m-d H:i:s"),
        							    "change_log_tardiness_min"			=> $update_tardiness,
        							    "change_log_undertime_min"			=> $update_undertime,
        							    "change_log_time_in"				=> $new_time_in,
        							    "change_log_time_out"				=> $new_time_out,
        							    "change_log_total_hours"			=> $your_total_hours,
        							    "change_log_total_hours_required"	=> $get_total_hours,
        							    $new_source							=> "Adjusted",
        							    "late_min"							=> $late_min,
        							    "overbreak_min"						=> $overbreak_min
							);
							
							$update_change_logs = $this->db->update('employee_time_in', $data_update);
							
							if($update_change_logs) {
								$new_total_hours_worked = $get_total_hours;
								$new_tardiness = $update_tardiness;
								$new_undertime = $update_undertime;
								$your_new_total_hours = $your_total_hours;
							} else {
								$new_total_hours_worked = 0;
								$new_tardiness = 0;
								$new_undertime = 0;
								$your_new_total_hours = 0;
							}
							
						}else{
							// update tardiness for timein
							$tardiness_timein = 0;
							if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
								if(date("A",strtotime($new_time_in)) == "AM"){
									// add one day for time in log
									#$new_start_timein = date("Y-m-d",strtotime($new_time_in." -1 day"));
									#$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
									$new_start_timein = $workday_settings_start_time;
									
									if(strtotime($new_start_timein) < strtotime($new_time_in)){
										$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;			
									}
								}
							}else{
								#$new_start_timein = date("Y-m-d",strtotime($new_time_in));
								#$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
								$new_start_timein = $workday_settings_start_time;
								
								if(strtotime($new_start_timein) < strtotime($new_time_in)){
									$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;			
								}
							}
							
							// update tardiness for break time
							$update_tardiness_break_time = 0;
							$duration_of_lunch_break_per_day = $this->elmf->duration_of_lunch_break_per_day($this->emp_id, $this->company_id, $this->work_schedule_id);
							$tardiness_a = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
							if($duration_of_lunch_break_per_day < $tardiness_a){
								$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
							}
	
							// update total tardiness
							$update_tardiness = $tardiness_timein + $update_tardiness_break_time;
							
							// update undertime
							$update_undertime = 0;
							if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
								$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
							}
							if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
								#$new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
								$new_end_time = $workday_settings_end_time;
								$update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
							}
							
							// update total hours
							$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
							$update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - ($duration_of_lunch_break_per_day / 60);
							
							// check tardiness value
							$get_total_hours_worked = ($hours_worked / 2) + .5;
							if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
								$update_tardiness = 0;
								$update_undertime = 0;
								$flag_tu = 1;
							}
							
							// update total hours required
							$calc_tot_break = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 3600;
							$actual_break = ($duration_of_lunch_break_per_day / 60);
							
							if($calc_tot_break > $actual_break) {
								$excess_break = $calc_tot_break - $actual_break;
							} else {
								$excess_break = 0;
							}
							
							$update_total_hours_required = (((strtotime($new_time_out) - strtotime($new_time_in)) / 3600) - ($update_tardiness / 60) - ($duration_of_lunch_break_per_day / 60)) - $excess_break;
							
							// if value is less than 0 then set value to 0
							if($update_tardiness < 0) $update_tardiness = 0;
							if($update_undertime < 0) $update_undertime = 0;
							if($update_total_hours < 0) $update_total_hours = 0;
							if($update_total_hours_required < 0) $update_total_hours_required = 0;
							
							$your_total_hours = $your_total_hours - ($duration_of_lunch_break_per_day / 60);
							
							// UPDATE TIME INS
							$where_tot = array(
								"comp_id"				=> $this->company_id,
								"emp_id"				=> $this->emp_id,
								"employee_time_in_id"	=> $employee_timein
							);
							
							$this->db->where($where_tot);
							
							$data_update = array(
        							    "work_schedule_id" 					=> $this->work_schedule_id,
        							    "comp_id"							=> $this->company_id,
        							    "emp_id"							=> $this->emp_id,
        							    "date"								=> $emp_schedule_date,
        							    "time_in_status"					=> 'pending',
        							    "corrected"							=> 'Yes',
        							    "reason"							=> $reason,
        							    "change_log_date_filed"				=> date("Y-m-d H:i:s"),
        							    "change_log_tardiness_min"			=> $update_tardiness,
        							    "change_log_undertime_min"			=> $update_undertime,
        							    "change_log_time_in"				=> $new_time_in,
        							    "change_log_lunch_out"				=> $new_lunch_out,
        							    "change_log_lunch_in"				=> $new_lunch_in,
        							    "change_log_time_out"				=> $new_time_out,
        							    "change_log_total_hours"			=> $your_total_hours,
        							    "change_log_total_hours_required"	=> $update_total_hours_required,
        							    $new_source							=> "Adjusted",
        							    "late_min"							=> $late_min,
        							    "overbreak_min"						=> $overbreak_min
							);
							
							$update_change_logs = $this->db->update('employee_time_in', $data_update);
							
							if($update_change_logs) {
								$new_total_hours_worked = $update_total_hours_required;
								$new_tardiness = $update_tardiness;
								$new_undertime = $update_undertime;
								$your_new_total_hours = $your_total_hours;
							} else {
								$new_total_hours_worked = 0;
								$new_tardiness = 0;
								$new_undertime = 0;
								$your_new_total_hours = 0;
							}
						}
					}
				}
			}	
			
		}elseif($check_work_type == "Workshift"){
			$get_schedule_blocks_time_in = $this->employee->get_schedule_blocks_time_in($employee_timein,$this->emp_id,$this->company_id);
			
			if($get_schedule_blocks_time_in) {
				if($get_schedule_blocks_time_in->source != null) {
					$new_source_s = "last_source";
				} else {
					$new_source_s = "source";
				}
			} else {
				$new_source_s = "source";
			}
			
			//CHECKS IF RESTDAY OR NOT
			if($check_rest_day || $check_holiday){
				$tardiness = 0;
				$undertime = 0;
					
				// update total hours and total hours required rest day
				$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
				
				// UPDATE TIME INS
				$where_tot = array(
						"comp_id"				=> $this->company_id,
						"emp_id"				=> $this->emp_id,
						"employee_time_in_id"	=> $employee_timein
				);
				$this->db->where($where_tot);
					
				$data_update = array(
						"comp_id"							=> $this->company_id,
						"emp_id"							=> $this->emp_id,
						"date"								=> date("Y-m-d",strtotime($new_time_in)),
						"time_in_status" 					=> 'pending',
						"corrected"							=> 'Yes',
						"reason"							=> $reason,
						"change_log_date_filed"				=> date("Y-m-d H:i:s"),
						"change_log_tardiness_min"			=> $tardiness,
						"change_log_undertime_min"			=> $undertime,
						"change_log_time_in"				=> $new_time_in,
						"change_log_time_out"				=> $new_time_out,
						"change_log_total_hours"			=> $get_total_hours,
						"change_log_total_hours_required"	=> $get_total_hours,
						$new_source_s						=> "Adjusted",
						"late_min"							=> 0,
						"overbreak_min"						=> 0
				);
					
				$update_change_logs = $this->db->update('schedule_blocks_time_in', $data_update);
				
				if($update_change_logs) {
				    $split_timein_information = $this->timeins->split_timeins_info($employee_timein,$this->company_id);
				    $emp_time_in_id = $split_timein_information->employee_time_in_id;
				    $new_total_hours_worked = $get_total_hours;
				    $new_tardiness = $tardiness;
				    $new_undertime = $undertime;
				    $your_new_total_hours = $your_total_hours;
				    
				} else {
				    $emp_time_in_id = "";
				    $new_total_hours_worked = 0;
				    $new_tardiness = 0;
				    $new_undertime = 0;
				    $your_new_total_hours = 0;
				}
				
				// UPDATE TIME INS
				$where_timein = array(
				    "comp_id"				=> $this->company_id,
				    "emp_id"				=> $this->emp_id,
				    "employee_time_in_id"	=> $emp_time_in_id,
				    "status"				=> "Active"
				);
				$this->db->where($where_timein);
				
				$data_update_timein = array(
				    "time_in_status"	=> 'pending',
				    "split_status"		=> 'pending',
				    "corrected"			=> 'Yes',
				    "last_source"		=> "Adjusted",
				);
				
				$this->db->update('employee_time_in', $data_update_timein);
			}else{
				// check break time
				$check_break_time = $this->ews->check_breaktime_split1($this->company_id, $this->emp_id, date("Y-m-d",strtotime($new_time_in)), $this->work_schedule_id,$schedule_blocks_id);

				if($check_break_time->break_in_min == 0 || $check_break_time->break_in_min == null){ // ZERO VALUE FOR BREAK TIME
					// update total hours and total hours required rest day
					$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
					
					/* EMPLOYEE WORK SCHEDULE */
					$number_of_breaks_per_day = $check_break_time->break_in_min;
					
					// tardiness and undertime value
					$sched_start = date("Y-m-d", strtotime($new_time_in)).' '.$check_break_time->start_time;
					$update_tardiness = 0;
					
					if(strtotime($new_time_in) > strtotime($sched_start)) {
					    $update_tardiness = (strtotime($new_time_in) - strtotime($sched_start)) / 60;
					}
					
					// get total undertime
					$sched_end = date("Y-m-d", strtotime($new_time_out)).' '.$check_break_time->end_time;
					
					if(strtotime($sched_end) > strtotime($new_time_out)) {
					    $update_undertime = (strtotime($sched_end) - strtotime($new_time_out)) / 60;
					} else {
					    $update_undertime = 0;
					}
					
					#$update_undertime = $this->elm->get_undertime_val($this->emp_id,$this->company_id,$new_time_in,$new_time_out,$this->work_schedule_id,$number_of_breaks_per_day);
					// hours worked
					#$hours_worked = $this->elm->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
					$hours_worked = $check_break_time->total_hours_work_per_block;
					// check tardiness value
					$flag_tu = 0;
					
					$get_total_hours_worked = ($hours_worked / 2) + .5;
					if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
						#$update_tardiness = 0;
						#$update_undertime = 0;
						$flag_tu = 1;
					}
					
					// required hours worked only
					$new_total_hours = $this->elm->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out,$hours_worked,$this->work_schedule_id);
					
					// UPDATE SPLIT TIME INS
					$where_tot = array(
						"comp_id"						=> $this->company_id,
						"emp_id"						=> $this->emp_id,
						"schedule_blocks_time_in_id"	=> $employee_timein
					);
					$this->db->where($where_tot);
					
					$data_update = array(
					    "comp_id"							=> $this->company_id,
					    "emp_id"							=> $this->emp_id,
					    "date"								=> date("Y-m-d",strtotime($new_time_in)),
					    "time_in_status"					=> 'pending',
					    "corrected"							=> 'Yes',
					    "reason"							=> $reason,
					    "flag_tardiness_undertime"			=> $flag_tu,
					    "absent_min" 						=> 0,
					    "change_log_date_filed"				=> date("Y-m-d H:i:s"),
					    "change_log_tardiness_min"			=> $update_tardiness,
					    "change_log_undertime_min"			=> $update_undertime,
					    "change_log_time_in"				=> $new_time_in,
					    "change_log_time_out"				=> $new_time_out,
					    "change_log_total_hours"			=> $hours_worked,
					    "change_log_total_hours_required"	=> $get_total_hours,
					    "flag_halfday"						=> 1,
					    $new_source_s						=> "Adjusted",
					    "late_min"							=> $update_tardiness,
					    "overbreak_min"						=> 0
					    // "date_halfday"=>$date_halfday
					);
					
					$update_change_logs = $this->db->update('schedule_blocks_time_in', $data_update);
					
					if($update_change_logs) {
					    $split_timein_information = $this->timeins->split_timeins_info($employee_timein,$this->company_id);
					    $emp_time_in_id = $split_timein_information->employee_time_in_id;
					    $new_total_hours_worked = $get_total_hours;
					    $new_tardiness = $update_tardiness;
					    $new_undertime = $update_undertime;
					    $your_new_total_hours = $hours_worked;
					} else {
						$emp_time_in_id = "";
						$new_total_hours_worked = 0;
						$new_tardiness = 0;
						$new_undertime = 0;
						$your_new_total_hours = 0;
					}
					
					// UPDATE TIME INS
					$where_timein = array(
					    "comp_id"				=> $this->company_id,
					    "emp_id"				=> $this->emp_id,
					    "employee_time_in_id"	=> $emp_time_in_id,
					    "status"				=> "Active"
					);
					$this->db->where($where_timein);
					
					$data_update_timein = array(
					    "time_in_status"	=> 'pending',
					    "split_status"		=> 'pending',
					    "corrected"			=> 'Yes',
					    "last_source"		=> "Adjusted",
					);
					
					$this->db->update('employee_time_in', $data_update_timein);
					
				}else if($new_time_in != "" && $new_time_out != "" && $new_lunch_in != "" && $new_lunch_out != ""){
					
					// WHOLEDAY
					$hours_worked = $check_break_time->total_hours_work_per_block;
												
					/* EMPLOYEE WORK SCHEDULE */
					// tardiness
					$tardiness = $this->elm->get_tardiness_import($this->emp_id, $this->company_id, $new_time_in, $new_lunch_out, $new_lunch_in, $this->work_schedule_id, $check_break_time->break_in_min);
						
					// undertime
					#$undertime = $this->elm->get_undertime_import($this->emp_id, $this->company_id, $new_time_in, $new_lunch_out, $new_lunch_in, $this->work_schedule_id, $check_break_time->break_in_min);
						
					// get total undertime
					$sched_end = date("Y-m-d", strtotime($new_time_out)).' '.$check_break_time->end_time;
					
					if(strtotime($sched_end) > strtotime($new_time_out)) {
					    $undertime = (strtotime($sched_end) - strtotime($new_time_out)) / 60;
					} else {
					    $undertime = 0;
					}
					
					// total hours worked
					$total_hours_worked = $this->elm->get_tot_hours_limit($this->emp_id, $this->company_id, $new_time_in, $new_lunch_out, $new_lunch_in, $new_time_out, $this->work_schedule_id, $check_break_time->break_in_min);
														
					$where_tot = array(
							"comp_id"						=> $this->company_id,
							"emp_id"						=> $this->emp_id,
							"schedule_blocks_time_in_id"	=> $employee_timein
					);
					$this->db->where($where_tot);
					
					$data = array(
        					    "time_in_status"					=> 'pending',
        					    "corrected"							=> 'Yes',
        					    "reason"							=> $reason,
        					    "absent_min" 						=> 0,
        					    "change_log_date_filed"				=> date("Y-m-d H:i:s"),
        					    "change_log_time_in"				=> date("Y-m-d H:i:s", strtotime($new_time_in)),
        					    "change_log_lunch_out"				=> date("Y-m-d H:i:s", strtotime($new_lunch_out)),
        					    "change_log_lunch_in"				=> date("Y-m-d H:i:s", strtotime($new_lunch_in)),
        					    "change_log_time_out"				=> date("Y-m-d H:i:s", strtotime($new_time_out)),
        					    "change_log_tardiness_min"			=> $tardiness + $overbreak_min,
        					    "change_log_undertime_min"			=> $undertime,
        					    "change_log_total_hours_required"	=> $total_hours_worked,
        					    "change_log_total_hours"			=> $hours_worked,
        					    $new_source_s						=> "Adjusted",
        					    "late_min"							=> $tardiness,
        					    "overbreak_min"						=> $overbreak_min
					);
					$update_change_logs =  $this->db->update('schedule_blocks_time_in', $data);
					
					if($update_change_logs) {
					    $split_timein_information = $this->timeins->split_timeins_info($employee_timein,$this->company_id);
					    $emp_time_in_id = $split_timein_information->employee_time_in_id;
					    $new_total_hours_worked = $total_hours_worked;
					    $new_tardiness = $tardiness;
					    $new_undertime = $undertime;
					    $your_new_total_hours = $hours_worked;
					} else {
						$emp_time_in_id = "";
						$new_total_hours_worked = 0;
						$new_tardiness = 0;
						$new_undertime = 0;
						$your_new_total_hours = 0;
					}
					
					// UPDATE TIME INS
					$where_timein = array(
        					    "comp_id"				=> $this->company_id,
        					    "emp_id"				=> $this->emp_id,
        					    "employee_time_in_id"	=> $emp_time_in_id,
        					    "status"				=> "Active"
					);
					$this->db->where($where_timein);
					
					$data_update_timein = array(
        					    "time_in_status"	=> 'pending',
        					    "split_status"		=> 'pending',
        					    "corrected"			=> 'Yes',
        					    "last_source"		=> "Adjusted",
					);
					
					$this->db->update('employee_time_in', $data_update_timein);
				}
			
			}
		}
		/* END UPDATE EMPLOYEE LOGS */
					
		// save approval token
		$emp_time_id = $employee_timein;
		$nw_total_hours_worked = $new_total_hours_worked;
		$nw_tardiness = $new_tardiness;
		$nw_undertime = $new_undertime;
		$nw_total_hours = $your_new_total_hours;
		$str = 'abcdefghijk123456789';
		$shuffled = str_shuffle($str);
		$new_logs = array(
				'new_time_in' 	=> $new_time_in,
				'new_lunch_out' => $new_lunch_out,
				'new_lunch_in' 	=> $new_lunch_in,
				'new_time_out'	=> $new_time_out,
				'reason'		=> $reason
		);
		// generate token level
		$employee_details = get_employee_details_by_empid($this->emp_id);
		$str2 = 'ABCDEFG1234567890';
		$shuffled2 = str_shuffle($str2);
		$psa_id = $this->session->userdata('psa_id');
		$approver_id = $this->employee->get_approver_name_timein($this->emp_id,$this->company_id)->attendance_adjustment_approval_grp;
		$change_logs_approver = $this->agm->get_approver_name_timein_change_logs($this->emp_id,$this->company_id);
		$timein_info = $this->agm->timein_information($emp_time_id);
		$fullname = ucfirst($timein_info->first_name)." ".ucfirst($timein_info->last_name);
		$hours_notification = get_notify_settings($employee_details->attendance_adjustment_approval_grp, $this->company_id);
		$get_approval_settings_disable_status = $this->employee->get_approval_settings_disable_status($this->company_id);
		
		// elimate January 01, 1970
		$fritz_time_in = ($new_time_in == NULL || $new_time_in == "") ? NULL : date("Y-m-d H:i:s", strtotime($new_time_in));
		$fritz_lunch_out = ($new_lunch_out == NULL || $new_lunch_out == "") ? NULL : date("Y-m-d H:i:s", strtotime($new_lunch_out));
		$fritz_lunch_in = ($new_lunch_in == NULL || $new_lunch_in == "") ? NULL : date("Y-m-d H:i:s", strtotime($new_lunch_in));
		$fritz_time_out = ($new_time_out == NULL || $new_time_out == "") ? NULL : date("Y-m-d H:i:s", strtotime($new_time_out));
				
		if($approver_id) {
			if(is_workflow_enabled($this->company_id)){
				if($change_logs_approver){
					if($hours_notification){
						$change_logs_approver = $this->agm->get_approver_name_timein_change_logs($this->emp_id,$this->company_id);
						
						$new_level = 1;
						$lflag = 0;
				
						// with leveling
						if($hours_notification){
							foreach ($change_logs_approver as $cla){
								$appovers_id = ($cla->emp_id) ? $cla->emp_id : "-99{$this->company_id}";
								$get_approval_group_via_groups_owner = $this->agm->get_approval_group_via_groups_owner($cla->approval_process_id, $cla->company_id, $cla->approval_groups_via_groups_id, $appovers_id);
								
								if($get_approval_group_via_groups_owner->emp_id == "-99{$this->company_id}"){
									$owner_approver = get_approver_owner_info($this->company_id);
									$appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
									$appr_account_id = $owner_approver->account_id;
									$appr_email = $owner_approver->email;
									$appr_id = "-99{$this->company_id}";
								} else {
									$appr_name = ucwords($cla->first_name." ".$cla->last_name);
									$appr_account_id = $cla->account_id;
									$appr_email = $cla->email;
									$appr_id = $cla->emp_id;
								}
								
								if($cla->level == $new_level){

									$this->send_change_add_logss_notifcation("change", $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2,$appr_id);
									if($hours_notification->sms_notification == "yes"){
										$url = base_url()."approval/time_in/index/".$shuffled."/".$shuffled2."/1".$appr_id."0/".$employee_timein;
										$sms_message = "Click {$url} to approve {$fullname}'s attendance adjustment.";
										send_this_sms_global($this->company_id, $appr_account_id,$sms_message,$psa_id,false);
									}
									
									if($hours_notification->message_board_notification == "yes"){
										$token = $this->timeins->get_token($timein_info->approval_time_in_id, $this->company_id, $timein_info->emp_id);
										$url = base_url()."approval/time_in/index/".$shuffled."/".$shuffled2."/1".$appr_id."0/".$employee_timein;
										$next_appr_notif_message = "A New Attendance Adjustment has been filed by {$fullname} and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
										send_to_message_board($psa_id, $appr_id, $this->emp_id, $this->company_id, $next_appr_notif_message, "system", 'warning');
									}
										
									$lflag = 1;
									
								}else{
									// send without link
									$this->send_change_add_logss_notifcation("change", $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "" , "", "");
									if($hours_notification->sms_notification == "yes"){
										$sms_message = "A New Attendance Adjustment has been filed by {$fullname}.";
										send_this_sms_global($this->company_id, $appr_account_id,$sms_message,$psa_id,false);
									}
									
									if($hours_notification->message_board_notification == "yes"){
										$next_appr_notif_message = "A New Attendance Adjustment has been filed by {$fullname}.";
										send_to_message_board($psa_id, $appr_id, $this->emp_id,$this->company_id, $next_appr_notif_message, "system", "warning");
									}
										
								}
							}
							
							################################ notify payroll admin start ################################
							if($hours_notification->notify_payroll_admin == "yes"){
							    // HRs
							    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($psa_id);
							    if($payroll_admin_hr){
							        foreach ($payroll_admin_hr as $pahr){
							            $pahr_email = $pahr->email;
							            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);
							            
							            $this->send_change_add_logss_notifcation("change", $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $pahr_email, $pahr_name, "", "" , "", "");
							        }
							    }
							    
							    // Owner
							    $pa_owner = get_approver_owner_info($this->company_id);
							    if($pa_owner){
							        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
							        $pa_owner_email = $pa_owner->email;
							        $pa_owner_account_id = $pa_owner->account_id;
							        
							        $this->send_change_add_logss_notifcation("change", $new_logs, $shuffled, $employee_timein, $this->company_id,$this->emp_id, $pa_owner_email, $pa_owner_name, "", "" , "", "");
							    }
							}
							################################ notify payroll admin end ################################
						}
							
						if($check_work_type == "Workshift"){
							$save_token = array(
									"time_in_id"		=> $emp_time_in_id,
									"split_time_in_id"	=> $emp_time_id,
									"token"				=> $shuffled,
									"comp_id"			=> $this->company_id,
									"emp_id"			=> $this->emp_id,
									"approver_id"		=> $approver_id,
									"level"				=> $new_level,
									"token_level"		=> $shuffled2
							);
							$save_token_q = $this->db->insert("approval_time_in",$save_token);
							$id = $this->db->insert_id();
							$timein_update = array('approval_time_in_id'=>$id);
							$this->db->where('schedule_blocks_time_in_id', $emp_time_id);
							$this->db->update('schedule_blocks_time_in',$timein_update);
							$appr_err="";
						} else {
							$save_token = array(
									"time_in_id"	=> $emp_time_id,
									"token"			=> $shuffled,
									"comp_id"		=> $this->company_id,
									"emp_id"		=> $this->emp_id,
									"approver_id"	=> $approver_id,
									"level"			=> $new_level,
									"token_level"	=> $shuffled2
							);
							$save_token_q = $this->db->insert("approval_time_in",$save_token);
							$id = $this->db->insert_id();
							$timein_update = array('approval_time_in_id'=>$id);
							$this->db->where('employee_time_in_id', $emp_time_id);
							$this->db->update('employee_time_in',$timein_update);
							$appr_err="";
						}
						
							
					}else{
						if($check_work_type == "Workshift"){
							$save_token = array(
									"time_in_id"		=> $emp_time_in_id,
									"split_time_in_id"	=> $emp_time_id,
									"token"				=> $shuffled,
									"comp_id"			=> $this->company_id,
									"emp_id"			=> $this->emp_id,
									"approver_id"		=> $approver_id,
									"level"				=> $new_level,
									"token_level"		=> $shuffled2
							);
							$save_token_q = $this->db->insert("approval_time_in",$save_token);
							$id = $this->db->insert_id();
							$timein_update = array('approval_time_in_id'=>$id);
							$this->db->where('schedule_blocks_time_in_id', $emp_time_id);
							$this->db->update('schedule_blocks_time_in',$timein_update);
							$appr_err="";
						} else {
							$save_token = array(
									"time_in_id"	=> $emp_time_id,
									"token"			=> $shuffled,
									"comp_id"		=> $this->company_id,
									"emp_id"		=> $this->emp_id,
									"approver_id"	=> $approver_id,
									"level"			=> 1,
									"token_level"	=> $shuffled2
							);
							$save_token_q = $this->db->insert("approval_time_in",$save_token);
							$id = $this->db->insert_id();
							$timein_update = array('approval_time_in_id'=>$id);
							$this->db->where('employee_time_in_id', $emp_time_id);
							$this->db->update('employee_time_in',$timein_update);
						}
					}
													
					$result = array(
							'result' 			=> 1,
							'error'				=> false,
							'approver_error'	=> ""
					);
				
					echo json_encode($result);
					return false;
				
				}else{	
					$new_level = 1;
					

					if($check_work_type == "Workshift"){
						$save_token = array(
								"time_in_id"		=> $emp_time_in_id,
								"split_time_in_id"	=> $emp_time_id,
								"token"				=> $shuffled,
								"comp_id"			=> $this->company_id,
								"emp_id"			=> $this->emp_id,
								"approver_id"	 	=> $approver_id,
								"level"				=> $new_level,
								"token_level"		=> $shuffled2
						);
						$save_token_q = $this->db->insert("approval_time_in",$save_token);
						$id = $this->db->insert_id();
						$timein_update = array('approval_time_in_id'=>$id);
						$this->db->where('schedule_blocks_time_in_id', $emp_time_id);
						$this->db->update('schedule_blocks_time_in',$timein_update);
						#$approver_error = "No Approvers found";
						$result = array(
								'error'=>false,
								'approver_error' => ""
						);
					} else {
						$save_token = array(
								"time_in_id"	=> $emp_time_id,
								"token"			=> $shuffled,
								"comp_id"		=> $this->company_id,
								"emp_id"		=> $this->emp_id,
								"approver_id"	=> $approver_id,
								"level"			=> $new_level,
								"token_level"	=> $shuffled2
						);
						$save_token_q = $this->db->insert("approval_time_in",$save_token);
						$id = $this->db->insert_id();
						$timein_update = array('approval_time_in_id'=>$id);
						$this->db->where('employee_time_in_id', $emp_time_id);
						$this->db->update('employee_time_in',$timein_update);
						#$approver_error = "No Approvers found";
						$result = array(
								'result' 			=> 3, 
								'error'				=> false,
								'approver_error'	=> ""
						);
						//show_error($approver_error);
					}
					echo json_encode(array("result" => 1,"error_msg"=>"No approvers found."));
					return false;
				}
			}else{
				if($get_approval_settings_disable_status->status == "Inactive") {
					if($get_approval_settings_disable_status->disabled_application_status == 'approve') {
						$status = "approved";
					} else {
						$status = $get_approval_settings_disable_status->disabled_application_status;
					}
					
					if($check_work_type == "Workshift"){
					    $fields = array(
        					        "date"					=> $emp_schedule_date,
        					        "time_in_status" 		=> $status,
        					        "corrected"				=> "Yes",
        					        "time_in"				=> $fritz_time_in,
        					        "lunch_out"				=> $fritz_lunch_out,
        					        "lunch_in"				=> $fritz_lunch_in,
        					        "time_out"				=> $fritz_time_out,
        					        "total_hours"			=> $nw_total_hours,
        					        "total_hours_required" 	=> $nw_total_hours_worked,
        					        "tardiness_min"			=> $nw_tardiness + $overbreak_min,
        					        "undertime_min"			=> $nw_undertime,
        					        'last_source'			=> "Adjusted",
        					        "late_min"				=> $nw_tardiness,
        					        "overbreak_min"			=> $overbreak_min
					    );
							
						$where = array(
								"comp_id"						=> $this->company_id,
								"emp_id"						=> $this->emp_id,
								"schedule_blocks_time_in_id"	=> $employee_timein
						);
							
						$this->timeins->update_field("schedule_blocks_time_in",$fields,$where);
						
						// get the first and last blocks
						$yest_list = $this->elm->list_of_blocks(date("Y-m-d",strtotime($emp_schedule_date)),$this->emp_id,$this->work_schedule_id,$this->company_id);
						$first_sched = reset($yest_list);
						$last_sched = max($yest_list);
						
						// get all (employee_time_in_id) split info
						$get_split_timein_info_res = $this->employee->get_split_timein_info_res($this->emp_id,$this->company_id,$emp_time_in_id);
						$res_total_hrs_req = 0;
						$res_total_hrs = 0;
						$res_tardiness_min = 0;
						$res_undertime_min = 0;
						$res_overbreak_min = 0;
						$res_late_min = 0;
						
						if($get_split_timein_info_res) {
						    foreach ($get_split_timein_info_res as $res) {
						        $res_total_hrs_req += $res->change_log_total_hours_required;
						        $res_total_hrs += $res->change_log_total_hours;
						        $res_tardiness_min += $res->change_log_tardiness_min;
						        $res_undertime_min += $res->change_log_undertime_min;
						        $res_overbreak_min += $res->overbreak_min;
						        $res_late_min += $res->late_min;
						    }
						}
						
						if($schedule_blocks_id == $first_sched->schedule_blocks_id) {
						    $fields = array(
						        "date"					=> $emp_schedule_date,
						        "corrected"				=> "Yes",
						        "time_in"				=> $fritz_time_in,
						        "total_hours"			=> $res_total_hrs,
						        "total_hours_required"	=> $res_total_hrs_req,
						        "tardiness_min"			=> $res_tardiness_min,
						        "undertime_min"			=> $res_undertime_min,
						        "overbreak_min"			=> $res_overbreak_min,
						        "late_min"				=> $res_late_min,
						    );
						    
						    $where = array(
						        "employee_time_in_id"	=> $emp_time_in_id,
						        "comp_id"				=> $this->company_id
						    );
						    
						    $this->timeins->update_field("employee_time_in",$fields,$where);
						}
						
						if($schedule_blocks_id == $last_sched->schedule_blocks_id) {
						    $fields = array(
						        "date"					=> $emp_schedule_date,
						        "corrected"				=> "Yes",
						        "time_out"				=> $fritz_time_out,
						        "total_hours"			=> $res_total_hrs,
						        "total_hours_required"	=> $res_total_hrs_req,
						        "tardiness_min"			=> $res_tardiness_min,
						        "undertime_min"			=> $res_undertime_min,
						        "overbreak_min"			=> $res_overbreak_min,
						        "late_min"				=> $res_late_min,
						    );
						    
						    $where = array(
						        "employee_time_in_id"	=> $emp_time_in_id,
						        "comp_id"				=> $this->company_id
						    );
						    
						    $this->timeins->update_field("employee_time_in",$fields,$where);
						}
						
					} else {
					    $fields = array(
        					        "date"					=> $emp_schedule_date,
        					        "time_in_status" 		=> $status,
        					        "corrected"				=> "Yes",
        					        "time_in"				=> $fritz_time_in,
        					        "lunch_out"				=> $fritz_lunch_out,
        					        "lunch_in"				=> $fritz_lunch_in,
        					        "time_out"				=> $fritz_time_out,
        					        "total_hours"			=> $nw_total_hours,
        					        "total_hours_required" 	=> $nw_total_hours_worked,
        					        "tardiness_min"			=> $nw_tardiness + $overbreak_min,
        					        "undertime_min"			=> $nw_undertime,
        					        'last_source'			=> "Adjusted",
        					        "late_min"				=> $nw_tardiness,
        					        "overbreak_min"			=> $overbreak_min
					    );
							
						$where = array(
								"employee_time_in_id"	=> $emp_time_id,
								"comp_id"				=> $this->company_id
						);
							
						$this->timeins->update_field("employee_time_in",$fields,$where);
					}
				}
			}
		}else{
			if($check_work_type == "Workshift"){
			    $fields = array(
        			        "date"					=> $emp_schedule_date,
        			        "time_in_status" 		=> "approved",
        			        "corrected"				=> "Yes",
        			        "time_in"				=> $fritz_time_in,
        			        "lunch_out"				=> $fritz_lunch_out,
        			        "lunch_in"				=> $fritz_lunch_in,
        			        "time_out"				=> $fritz_time_out,
        			        "total_hours"			=> $nw_total_hours,
        			        "total_hours_required" 	=> $nw_total_hours_worked,
        			        "tardiness_min"			=> $nw_tardiness + $overbreak_min,
        			        "undertime_min"			=> $nw_undertime,
        			        'last_source'			=> "Adjusted",
        			        "late_min"				=> $nw_tardiness,
        			        "overbreak_min"			=> $overbreak_min
			    );
					
				$where = array(
						"comp_id"						=> $this->company_id,
						"emp_id"						=> $this->emp_id,
						"schedule_blocks_time_in_id"	=> $employee_timein
				);
					
				$this->timeins->update_field("schedule_blocks_time_in",$fields,$where);
				
			} else {
			    $fields = array(
        			        "date"					=> $emp_schedule_date,
        			        "time_in_status" 		=> "approved",
        			        "corrected"				=> "Yes",
        			        "time_in"				=> $fritz_time_in,
        			        "lunch_out"				=> $fritz_lunch_out,
        			        "lunch_in"				=> $fritz_lunch_in,
        			        "time_out"				=> $fritz_time_out,
        			        "total_hours"			=> $nw_total_hours,
        			        "total_hours_required" 	=> $nw_total_hours_worked,
        			        "tardiness_min"			=> $nw_tardiness + $overbreak_min,
        			        "undertime_min"			=> $nw_undertime,
        			        'last_source'			=> "Adjusted",
        			        "late_min"				=> $nw_tardiness,
        			        "overbreak_min"			=> $overbreak_min
			    );
					
				$where = array(
						"employee_time_in_id"	=> $emp_time_id,
						"comp_id"				=> $this->company_id
				);
					
				$this->timeins->update_field("employee_time_in",$fields,$where);
			}
		}
	}
			
	public function check_total_hours(){

        $employee_timein = $this->input->post('employee_timein');
        $change_log_date = $this->input->post('emp_schedule_date');
        $time_in_inp = $this->input->post('time_in');
        $time_out_inp = $this->input->post('time_out');
        $lunch_in_inp = $this->input->post('lunch_in');
        $lunch_out_inp = $this->input->post('lunch_out');
        $break1_in_inp = $this->input->post('first_break_in');
        $break1_out_inp = $this->input->post('first_break_out');
        $break2_in_inp = $this->input->post('second_break_in');
        $break2_out_inp = $this->input->post('second_break_out');

        
        $schedule_blocks_id = $this->input->post('schedule_blocks_id');
					
        $new_employee_timein_date = ($change_log_date) ? date("Y-m-d",strtotime($change_log_date)) : NULL;
        $employee_timein_date = ($time_in_inp) ? date('Y-m-d', strtotime($time_in_inp)) : NULL;
                    
        $new_time_in = $time_in = ($time_in_inp) ? date('Y-m-d H:i:s', strtotime($time_in_inp)) : NULL;
        $new_lunch_out = ($lunch_out_inp) ? date('Y-m-d H:i:s', strtotime($lunch_out_inp)) : NULL;
        $new_lunch_in = ($lunch_in_inp) ? date('Y-m-d H:i:s', strtotime($lunch_in_inp)) : NULL;
        $new_time_out = ($time_out_inp) ? date('Y-m-d H:i:s', strtotime($time_out_inp)) : NULL;

        $break_1_start_date_time = ($break1_in_inp) ? date('Y-m-d H:i:s', strtotime($break1_in_inp)) : NULL;
        $break_2_start_date_time = ($break2_in_inp) ? date('Y-m-d H:i:s', strtotime($break2_in_inp)) : NULL;

        $break_1_end_date_time = ($break1_out_inp) ? date('Y-m-d H:i:s', strtotime($break1_out_inp)) : NULL;
        $break_2_end_date_time = ($break2_out_inp) ? date('Y-m-d H:i:s', strtotime($break2_out_inp)) : NULL;

        $lunch_out_date = ($lunch_out_inp) ? date('Y-m-d', strtotime($lunch_out_inp)) : NULL;
        $lunch_in_date = ($lunch_in_inp) ? date('Y-m-d', strtotime($lunch_in_inp)) : NULL;
        $time_out_date = ($time_out_inp) ? date('Y-m-d', strtotime($time_out_inp)) : NULL;
					
        $reason = $this->input->post('reason');
    
        $flaghalfday = $this->input->post("flag_halfday");
        if($flaghalfday == 1){
            $new_lunch_out = NULL; 
            $new_lunch_in = NULL;
        }
            
        // trap user error..
        $ue_emp_schedule_date = date('Y-m-d', strtotime($new_employee_timein_date. ' + 2 days'));
        $for_UE = ""; 
        
        if(strtotime($employee_timein_date) > strtotime($ue_emp_schedule_date) || strtotime($lunch_out_date) > strtotime($ue_emp_schedule_date) ||
            strtotime($lunch_in_date) > strtotime($ue_emp_schedule_date) || strtotime($time_out_date) > strtotime($ue_emp_schedule_date)) {
            $for_UE = "hmmm, the date pattern seems unusual please double check the dates.";
        }
        
        /* UPDATE EMPLOYEE LOGS */
        //GETS WORK_SCHEDULE_ID
        $this->work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$new_employee_timein_date);
        //check existing logs
        $check_existing_timein = $this->employee_v2->check_existing_timein_for_changelog($this->emp_id,$this->company_id,$new_employee_timein_date,$new_time_in,$new_time_out,$employee_timein);
        
        $check_rest_day = $this->ews->get_rest_day($this->company_id,$this->work_schedule_id,date("l",strtotime($new_employee_timein_date)));
        $check_work_type = $this->employee->work_schedule_type($this->work_schedule_id, $this->company_id);
        
        $check_holiday = $this->employee->get_holiday_date($new_employee_timein_date,$this->emp_id,$this->company_id);
        if(check_if_enable_breaks_on_holiday($this->company_id,$this->work_schedule_id)) {
            $check_holiday = false;
        }
        //CHECK IF WORK SCHEDULE IS UNIFORM WORKING DAY / FLEXIBLE / SPLIT SCHEDULE
        $check_break_time_fritz = $this->employee->check_break_time_for_assumed($this->work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($new_employee_timein_date)));
        $is_break_assumed = is_break_assumed($this->work_schedule_id);
        
        $check_if_excess_logs = $this->employee->check_if_excess_logs($this->emp_id,$this->company_id,$employee_timein);
                
        $excess_logs = false;
        if($check_if_excess_logs) {	                              
            $excess_logs = true;
        }
        
        $overlaps = "";
        $error_flag = false;
        if($new_employee_timein_date != null && $new_time_in != null && $new_time_out != null) {
            $check_overlapping_logs = $this->employee->check_overlapping_logs($this->emp_id,$this->company_id,$new_employee_timein_date,$new_time_in,$new_time_out);
            if($check_overlapping_logs) {
                $overlaps = 'oops! this timesheet has overlapped an existing timesheet. please check you hours and try again.';
                $error_flag = true;
            }
        }

        if (($new_lunch_out != null || $new_lunch_out != "") && ($new_lunch_in != null || $new_lunch_in != "")) {
            if (strtotime($new_lunch_out) > strtotime($new_lunch_in)) {
                $overlaps = 'hmmn, the lunch out does not seem right.';
                $error_flag = true;
            }
            
            if (strtotime($new_lunch_in) > strtotime($new_time_out)) {
                $overlaps = 'hmmn, the lunch in does not seem right.';
                $error_flag = true;
            }
        }
            
        if (($break_1_start_date_time != null || $break_1_start_date_time != "") && ($break_1_end_date_time != null || $break_1_end_date_time != "")) {
            if (strtotime($break_1_start_date_time) > strtotime($break_1_end_date_time)) {
                $overlaps = 'hmmn, the first break does not seem right.';
                $error_flag = true;
            }
        }
        
        if (($break_2_start_date_time != null || $break_2_start_date_time != "") && ($break_2_end_date_time != null || $break_2_end_date_time != "")) {
            if (strtotime($break_2_start_date_time) > strtotime($break_2_end_date_time)) {
                $overlaps = 'hmmn, the second break does not seem right.';
                $error_flag = true;
            }
        }

        if($check_existing_timein) {
            $overlaps = 'oops! this timesheet has overlapped an existing timesheet, you may already have a time log for this date.';
            $error_flag = true;
        }
        
        $break_1 = false;
        $break_2 = false;
        $lunch_break_hours_started = "";
        $lunch_break_hours_ended = "";
        $break_1_start = "";
        $break_1_end = "";
        $break_2_start = "";
        $break_2_end = "";
        
        $can_lunch_out = NULL;
        $can_lunch_in = NULL;
        $can_break_1_out = NULL;
        $can_break_1_in = NULL;
        $can_break_2_out = NULL;
        $can_break_2_in = NULL;
		
        if($check_work_type == "Uniform Working Days"){
            $tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($this->company_id,$this->work_schedule_id);
            $get_schedule_settings = get_schedule_settings_by_workschedule_id($this->work_schedule_id,$this->company_id,date('l',strtotime($new_employee_timein_date)));
        
            if($tardiness_rule_migrated_v3) {
                // barrack code -- so need this param to calculate everything :D
                $emp_ids                           = array($this->emp_id); // emp id
                $min_range                         = date('Y-m-d', strtotime($new_employee_timein_date)); //date
                $max_range                         = date('Y-m-d', strtotime($new_employee_timein_date)); //date
                $min_range                         = date("Y-m-d",strtotime($min_range. " -1 day"));
                $max_range                         = date("Y-m-d",strtotime($max_range. " +1 day"));
                
                // parent functions to be use for param
                $split_arr                         = array();
                $get_employee_payroll_information  = $this->importv2->get_employee_payroll_information($this->company_id,$emp_ids);
                $emp_work_schedule_ess             = $this->importv2->emp_work_schedule_ess($this->company_id,$emp_ids);
                $emp_work_schedule_epi             = $this->importv2->emp_work_schedule_epi($this->company_id,$emp_ids);
                $list_of_blocks                    = $this->importv2->list_of_blocks($this->company_id,$emp_ids);
                $get_all_schedule_blocks           = $this->importv2->get_all_schedule_blocks($this->company_id);
                $get_all_regular_schedule          = $this->importv2->get_all_regular_schedule($this->company_id);
                $get_work_schedule_flex            = $this->importv2->get_work_schedule_flex($this->company_id);
                $company_holiday                   = $this->importv2->company_holiday($this->company_id);
                $get_work_schedule                 = $this->importv2->get_work_schedule($this->company_id);
                $get_employee_leave_application    = $this->importv2->get_employee_leave_application($this->company_id,$emp_ids);
                $get_tardiness_settings            = $this->importv2->get_tardiness_settings($this->company_id);
                $get_all_schedule_blocks_time_in   = $this->importv2->get_all_schedule_blocks_time_in($this->company_id,$emp_ids,$min_range,$max_range);
                $attendance_hours                  = is_attendance_active($this->company_id);
                $get_tardiness_settingsv2          = $this->importv2->get_tardiness_settingsv2($this->company_id);
                $tardiness_rounding                = $this->importv2->tardiness_rounding($this->company_id);
                
                if($employee_timein) {
                    $get_all_employee_timein           = $this->employee->get_all_employee_timein($this->company_id,$emp_ids,$min_range,$max_range);
                    $param_source = "updated";
                } else {
                    $get_all_employee_timein           = $this->importv2->get_all_employee_timein($this->company_id,$emp_ids,$min_range,$max_range);
                    $param_source = "EP";
                }
                
                $import_add_logsv2 = $this->importv2->import_add_logsv2($this->company_id, $this->emp_id, $reason, $new_time_in,$new_lunch_out,$new_lunch_in,date("Y-m-d H:i:s", strtotime($break_1_start_date_time)),date("Y-m-d H:i:s", strtotime($break_1_end_date_time)),date("Y-m-d H:i:s", strtotime($break_2_start_date_time)),date("Y-m-d H:i:s", strtotime($break_2_end_date_time)),$new_time_out, 0, $this->work_schedule_id,0,$split_arr,false,$param_source,$employee_timein,$new_employee_timein_date,"",$get_employee_payroll_information,$get_tardiness_settings,$company_holiday,"","",$get_all_regular_schedule,$get_work_schedule_flex,$list_of_blocks,$get_all_employee_timein,$get_all_schedule_blocks,$get_all_schedule_blocks_time_in,$attendance_hours,$get_work_schedule,$get_employee_leave_application,$emp_work_schedule_ess,$emp_work_schedule_epi,$get_tardiness_settingsv2,$tardiness_rounding);
                
                $calculated_data = $import_add_logsv2['fields'];
                
                if($get_schedule_settings) {
                    if($get_schedule_settings->enable_lunch_break == "yes") {
                        if($get_schedule_settings->track_break_1 == "yes") {
                            if($get_schedule_settings->break_schedule_1 == "fixed") {
                                $h = $get_schedule_settings->break_started_after * 60;
                                $lunch_break_datehours_started = date('Y-m-d', strtotime($new_employee_timein_date)).' '.$get_schedule_settings->work_start_time;
                                $lunch_break_hours_started = date("Y-m-d H:i:s", strtotime($lunch_break_datehours_started." +".$h." minutes"));
                                $lunch_break_hours_ended = date("Y-m-d H:i:s", strtotime($lunch_break_hours_started." +".$get_schedule_settings->break_in_min." minutes"));
                            }
                            
                            $Lunch_break = true;
                        }
                    }
                    
                    if($get_schedule_settings->enable_additional_breaks == "yes") {
                        if($get_schedule_settings->track_break_2 == "yes") {
                            if($get_schedule_settings->num_of_additional_breaks > 0) {
                                if($get_schedule_settings->break_schedule_2 == "fixed") {
                                    if($get_schedule_settings->additional_break_started_after_1 != "") {
                                        $h = $get_schedule_settings->additional_break_started_after_1 * 60;
                                        $lunch_break_datehours_started = date('Y-m-d', strtotime($new_employee_timein_date)).' '.$get_schedule_settings->work_start_time;
                                        $break_1_start = date("Y-m-d H:i:s", strtotime($lunch_break_datehours_started." +".$h." minutes"));
                                        $break_1_end = date("Y-m-d H:i:s", strtotime($break_1_start." +".$get_schedule_settings->break_1_in_min." minutes"));
                                    }
                                    
                                    if($get_schedule_settings->additional_break_started_after_2 != "") {
                                        $h = $get_schedule_settings->additional_break_started_after_2 * 60;
                                        $lunch_break_datehours_started = date('Y-m-d', strtotime($new_employee_timein_date)).' '.$get_schedule_settings->work_start_time;
                                        $break_2_start = date("Y-m-d H:i:s", strtotime($lunch_break_datehours_started." +".$h." minutes"));
                                        $break_2_end = date("Y-m-d H:i:s", strtotime($break_2_start." +".$get_schedule_settings->break_2_in_min." minutes"));
                                    }
                                }
                                
                                if($get_schedule_settings->num_of_additional_breaks == 2) {
                                    $break_1 = true;
                                    $break_2 = true;
                                }
                                
                                if($get_schedule_settings->num_of_additional_breaks == 1) {
                                    $break_1 = true;
                                }
                            }
                        }
                    }
                }
                
                $lunch_break_hours_started = ($lunch_break_hours_started != "") ? $lunch_break_hours_started : NULL;
                $lunch_break_hours_ended = ($lunch_break_hours_ended != "") ? $lunch_break_hours_ended : NULL;
                $break_1_start = ($break_1_start != "") ? $break_1_start : NULL;
                $break_1_end = ($break_1_end != "") ? $break_1_end : NULL;
                $break_2_start = ($break_2_start != "") ? $break_2_start : NULL;
                $break_2_end = ($break_2_end != "") ? $break_2_end : NULL;
                
                $lunch_break_hours_started_time = date("h:i a", strtotime($lunch_break_hours_started));
                $lunch_break_hours_ended_time = date("h:i a", strtotime($lunch_break_hours_ended));
                if(strtotime($lunch_break_hours_started) <= strtotime($new_lunch_out) && strtotime($new_lunch_out) >= strtotime($lunch_break_hours_ended)) {
                    if($lunch_break_hours_started != NULL && $lunch_break_hours_ended != NULL) {
                        $can_lunch_out = "Invalid lunch out time value. Lunch Break Time : ({$lunch_break_hours_started_time} - {$lunch_break_hours_ended_time}).";
                        $error_flag = true;
                    }
                } elseif (strtotime($lunch_break_hours_started) > strtotime($new_lunch_out)) {
                    if($lunch_break_hours_started != NULL && $lunch_break_hours_ended != NULL) {
                        $can_lunch_out = "Invalid lunch out time value. Lunch Break Time : ({$lunch_break_hours_started_time} - {$lunch_break_hours_ended_time}).";
                        $error_flag = true;
                    }
                } elseif (strtotime($new_lunch_out) >= strtotime($new_lunch_in)) {
                    $can_lunch_in = "Invalid lunch in time value.";
                    $error_flag = true;
                }
                
                $break_1_hours_started_time = date("h:i a", strtotime($break_1_start));
                $break_1_hours_ended_time = date("h:i a", strtotime($break_1_end));
                if(strtotime($break_1_start) <= strtotime($break_1_start_date_time) && strtotime($break_1_start_date_time) >= strtotime($break_1_end)) {
                    if($break_1_start != NULL && $break_1_end != NULL) {
                        $can_break_1_out = "Invalid first break out time value. First Break Time : ({$break_1_hours_started_time} - {$break_1_hours_ended_time}).";
                        $error_flag = true;
                    }
                } elseif (strtotime($break_1_start) > strtotime($break_1_start_date_time)) {
                    if($break_1_start != NULL && $break_1_end != NULL) {
                        $can_break_1_out = "Invalid first break out time value. First Break Time : ({$break_1_hours_started_time} - {$break_1_hours_ended_time}).";
                        $error_flag = true;
                    }
                } elseif (strtotime($break_1_start_date_time) >= strtotime($break_1_end_date_time)) {
                    $can_break_1_in = "Invalid first break in time value.";
                    $error_flag = true;
                }
                
                $break_2_hours_started_time = date("h:i a", strtotime($break_2_start));
                $break_2_hours_ended_time = date("h:i a", strtotime($break_2_end));
                if(strtotime($break_2_start) <= strtotime($break_2_start_date_time) && strtotime($break_2_start_date_time) >= strtotime($break_2_end)) {
                    if($break_2_start != NULL && $break_2_end != NULL) {
                        $can_break_2_out = "Invalid second break out time value. Second Break Time : ({$break_2_hours_started_time} - {$break_2_hours_ended_time}).";
                        $error_flag = true;
                    }
                } elseif (strtotime($break_2_start) > strtotime($break_2_start_date_time)) {
                    if($break_2_start != NULL && $break_2_end != NULL) {
                        $can_break_2_out = "Invalid second break out time value. Second Break Time : ({$break_2_hours_started_time} - {$break_2_hours_ended_time}).";
                        $error_flag = true;
                    }
                } elseif (strtotime($break_2_start_date_time) >= strtotime($break_2_end_date_time)) {
                    $can_break_2_in = "Invalid second break in time value.";
                    $error_flag = true;
                }
                
            } else {
                $calculated_data = false;
            }
            
            $hours_worked = $this->elm->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
            $get_uniform_sched_time = $this->employee->get_uniform_sched_time($this->work_schedule_id, $this->emp_id, $this->company_id, date("Y-m-d",strtotime($new_time_in)));
            
            // required hours worked only
            $new_total_hours = $this->elm->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out,$hours_worked,$this->work_schedule_id);
                    
            //CHECKS IF RESTDAY OR NOT
            if($check_rest_day || $check_holiday || $excess_logs == true){
                $tardiness = 0;
                $undertime = 0;
        
                // update total hours and total hours required rest day
                $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                    
                // UPDATE TIME INS
                $where_tot = array(
                        "comp_id" => $this->company_id,
                        "emp_id"	 => $this->emp_id,
                        "employee_time_in_id" => $employee_timein
                );
                $get_total_hours = $get_total_hours;
                
                // additional break (new setting for shift)
                // if($tardiness_rule_migrated_v3) {
                //     $get_total_hours = $calculated_data["total_hours_required"];
                // }
                    
                // TOTAL HOURS
                echo json_encode(array(
                        "success"=>"1",
                        "error"=>$error_flag,
                        "etime_out_date" => $overlaps,
                        "ecreason" => $for_UE,
                        "total_hours"=>($get_total_hours < 0) ? "0.00" : number_format($get_total_hours,2),
                        "ealunch_out_date" => $can_lunch_out,
                        "ealunch_in_date" => $can_lunch_in,
                        "ebreak_1_start" => $can_break_1_out,
                        "ebreak_1_end" => $can_break_1_in,
                        "ebreak_2_start" => $can_break_2_out,
                        "ebreak_2_end" => $can_break_2_in,
                    
                ));
                
                return false;
            }elseif($new_lunch_out == NULL && $new_lunch_in == NULL && $flaghalfday == 1){
                //IF HALFDAY 
                $flag_halfday = 0;
                $flag_undertime = 0;
                $tardiness = 0;
                $undertime = 0;
                $total_hours = 0;
                $total_hours_required = 0;
                
                $check_hours_flexible = $this->elm->check_hours_flex($this->company_id,$this->work_schedule_id);
                $check_breaktime = $this->elm->check_breaktime($this->company_id,$this->work_schedule_id);
            
                $workday = date("l",strtotime($new_time_in));
                
                $workday_settings_start_time = $this->elm->check_workday_settings_start_time($workday,$this->work_schedule_id,$this->company_id);
                $workday_settings_end_time = $this->elm->check_workday_settings_end_time($workday,$this->work_schedule_id,$this->company_id);
                //checks if latest timein is allowed
                
                if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
                    // for night shift time in and time out value for working day
                    $check_bet_timein = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
                    $check_bet_timeout = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$workday_settings_end_time;
                }else{
                    // for day shift time in and time out value for working day
                    $check_bet_timein = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
                    $check_bet_timeout = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_end_time;
                }
                // check between date time in to date time out
                $add_oneday_timein = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$workday_settings_start_time;
                
                /* EMPLOYEE WORK SCHEDULE */
                $wd_start = $this->elm->get_workday_sched_start($this->company_id,$this->work_schedule_id);
                $wd_end = $this->elm->get_end_time($this->company_id,$this->work_schedule_id);
                
                $total_work_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($time_in)),$this->work_schedule_id);
                $new_latest_timein_allowed = $this->employee->new_latest_timein_allowed($this->emp_id,$this->company_id,$this->work_schedule_id,"work_schedule");
                $new_st = $this->employee->for_leave_hoursworked_work_start_time_ws($this->emp_id,$this->company_id,$this->work_schedule_id);
                if($new_latest_timein_allowed){ // if latest time in is true
                    if(strtotime($time_in) < strtotime($new_st)){
                        $new_work_start_time = $new_st;
                    }elseif(strtotime($new_st) <= strtotime($time_in) && strtotime($time_in) <= strtotime($new_latest_timein_allowed)){
                        #$new_work_start_time = $concat_start_datetime;
                        $new_work_start_time = $new_time_in;
                    }elseif(strtotime($time_in) > strtotime($new_latest_timein_allowed)){
                        $new_work_start_time = $new_latest_timein_allowed;
                    }
                }
                $end_time = date('H:i:s', strtotime($new_work_start_time.' +'.$total_work_hours.' hours'));
                $check_time = (strtotime($end_time) - strtotime($new_work_start_time) ) / 3600;
                $check2 = $check_time / 2;
                $check2 = round($check2, 0, PHP_ROUND_HALF_DOWN);
                
                //$hd_check = strtotime($wd_start) + $check_time;
                $hd_check = date('H:i:s', strtotime($new_work_start_time.' +'.$check2.' hours'));
                $hd_check = strtotime($wd_start) + $check_time;
                
                $now_date = date("Y-m-d H:i:s",strtotime($new_time_out));
                $now_time = date("H:i:s",strtotime($new_time_out));
                
                if($check_break_time_fritz) { // for captured and assumed
                    if($check_break_time_fritz->break_in_min > 0) {
                        $shift_grace = ($check_break_time_fritz->latest_time_in_allowed) ? $check_break_time_fritz->latest_time_in_allowed : 0;
                        $time_start_w_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($check_break_time_fritz->work_start_time." +".$shift_grace." minutes"));
                        $time_start_wo_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($check_break_time_fritz->work_start_time));
                        
                        if($is_break_assumed) {
                            $assumed_breaks = $is_break_assumed->assumed_breaks;
                            $h = $assumed_breaks * 60;
                        } else {
                            $work_hrs = $check_break_time_fritz->total_work_hours;
                            $h = ($work_hrs / 2) * 60;		
                        }
                    }
                } else {
                    $shift_grace = ($get_uniform_sched_time->latest_time_in_allowed) ? $get_uniform_sched_time->latest_time_in_allowed : 0;
                    $time_start_w_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($get_uniform_sched_time->work_start_time." +".$shift_grace." minutes"));
                    $time_start_wo_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($get_uniform_sched_time->work_start_time));
                    
                    $work_hrs = $get_uniform_sched_time->total_work_hours;
                    $h = ($work_hrs / 2) * 60;
                }
                
                // get the border of start time in
                if(strtotime($time_start_w_grace) > strtotime($new_time_in)) {
                    //get the start and end of break time
                    $time_in_break = date('Y-m-d H:i:s', strtotime($new_time_in. " +{$h} minutes"));
                    $time_start_w_grace = $new_time_in;
                } else {
                    $time_in_break = date('Y-m-d H:i:s',strtotime($time_start_w_grace. " +{$h} minutes"));
                    $time_start_w_grace = $time_start_w_grace;
                }
                
                if(strtotime($new_time_in) < strtotime($time_in_break)) {
                    //get the diff the time in input and the start break time
                    $diff_timein_and_break = (strtotime($time_in_break) - strtotime($time_start_w_grace));
                    $diff_timein_and_break = $diff_timein_and_break / 2;
                    $boundary_of_halfday = date('Y-m-d H:i:s', strtotime($time_start_w_grace.' +'.$diff_timein_and_break.' seconds'));
                        
                    if(strtotime($boundary_of_halfday) <= strtotime($new_time_in)) {
                        $flag_halfday = 2; // second half
                    } else {
                        $flag_halfday = 1; // first half
                    }
                } else {
                    $flag_halfday = 2; // second half
                }
                
                if($check_break_time_fritz) {
                    $shift_grace = ($check_break_time_fritz->latest_time_in_allowed) ? $check_break_time_fritz->latest_time_in_allowed : 0;
                    $b_st = $check_break_time_fritz->work_start_time;
                    $b_et = $check_break_time_fritz->work_end_time;
                } else {
                    $shift_grace = ($get_uniform_sched_time->latest_time_in_allowed) ? $get_uniform_sched_time->latest_time_in_allowed : 0;
                    $b_st = $get_uniform_sched_time->work_start_time;
                    $b_et = $get_uniform_sched_time->work_end_time;
                }
                
                if($flag_halfday == 1){
                    // FOR TARDINESS 										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
                    $b_st_1 = date('Y-m-d', strtotime($new_time_in)).' '.$b_st;
                    $b_st_new = date('Y-m-d H:i:s', strtotime($b_st_1." +{$shift_grace} minutes"));
                    if(strtotime($new_time_in) > strtotime($b_st_new)) {
                        $calc_tardiness = (strtotime($new_time_in) - strtotime($b_st_new)) / 60;
                    } else {
                        $calc_tardiness = 0;
                    }
                    
                    $tardiness = $calc_tardiness;
                    
                    // GET END TIME FOR TIME OUT
                    $get_end_time = $this->elm->get_end_time($this->company_id,$this->work_schedule_id);
                    
                    // FOR UNDERTIME 											>>>>>>>>>>>>>>>>>>>>>>>>>>>>
                    if($get_end_time != FALSE){
                        if(strtotime($now_time) < strtotime($get_end_time)) {
                            if($check_break_time_fritz) {
                                if($check_break_time_fritz->break_in_min > 0) {
                                    $time_start_w_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($check_break_time_fritz->work_start_time." +".$shift_grace." minutes"));
                                    $time_start_wo_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($check_break_time_fritz->work_start_time));
                    
                                    if($is_break_assumed) { // assumed break
                                        // get the diff of time start without using the threshold and the input time in
                                        $minutes_for_time_in = (strtotime($new_time_in) - strtotime($time_start_wo_grace)) / 60;
                                        $time_end_wo_grace = date('Y-m-d', strtotime($new_time_out)).' '.date('H:i:s', strtotime($check_break_time_fritz->work_end_time.' +'.$minutes_for_time_in.' minutes'));
                                            
                                        // get the border of start time in
                                        if(strtotime($new_time_in) > strtotime($time_start_w_grace)) {
                                            $real_time_in_start = $time_start_w_grace;
                                            $real_time_out_start = $new_employee_timein_date.' '.date('H:i:s', strtotime($check_break_time_fritz->work_end_time." +".$shift_grace." minutes"));
                                        } elseif (strtotime($time_start_wo_grace) > strtotime($new_time_in)) {
                                            $real_time_in_start = $time_start_wo_grace;
                                            $real_time_out_start = $time_end_wo_grace;
                                        } else {
                                            $real_time_in_start = $new_time_in;
                                            $real_time_out_start = $time_end_wo_grace;
                                        }
                                            
                                        $assumed_breaks = $is_break_assumed->assumed_breaks;
                                        $h = $assumed_breaks * 60;
                                            
                                        if(strtotime($time_start_w_grace) > strtotime($new_time_in)) {
                                            $time_in_break = date('Y-m-d H:i:s',strtotime($new_time_in. " +{$h} minutes"));
                                        } else {
                                            $time_in_break = date('Y-m-d H:i:s',strtotime($time_start_w_grace. " +{$h} minutes"));
                                        }
                                            
                                        $time_out_break = date('Y-m-d H:i:s',strtotime($time_in_break. " +{$check_break_time_fritz->break_in_min} minutes"));
                                            
                                        if(strtotime($new_time_out) <= strtotime($time_in_break)) {
                                            $minutes_of_break = 0;
                                        } else {
                                            if(strtotime($new_time_out) >= strtotime($time_in_break) && strtotime($new_time_out) <= strtotime($time_out_break)) {
                                                $diff_breaks_input_and_break = (strtotime($new_time_out) - strtotime($time_in_break)) / 60;
                                                $minutes_of_break = $diff_breaks_input_and_break;
                                            } else {
                                                $minutes_of_break = $check_break_time_fritz->break_in_min;
                                            }
                                        }
                                    } else { // captured
                                        $time_end_wo_grace = date('Y-m-d', strtotime($new_time_out)).' '.date('H:i:s', strtotime($check_break_time_fritz->work_end_time));
                                        $time_end_w_grace = date('Y-m-d', strtotime($new_time_out)).' '.date('H:i:s', strtotime($check_break_time_fritz->work_end_time." +".$shift_grace." minutes"));
                                        // get the border of start time in
                                        if(strtotime($new_time_in) > strtotime($time_start_w_grace)) {
                                            $real_time_in_start = $time_start_w_grace;
                                            $real_time_out_start = $time_end_w_grace;
                                        } elseif (strtotime($time_start_wo_grace) > strtotime($new_time_in)) {
                                            $real_time_in_start = $time_start_wo_grace;
                                            $real_time_out_start = $time_end_wo_grace;
                                        } else {
                                            $real_time_in_start = $new_time_in;
                                                
                                            // get the diff of time start without using the threshold and the input time in
                                            $minutes_for_time_out = (strtotime($new_time_in) - strtotime($time_start_wo_grace)) / 60;
                                            $real_time_out_start = date('Y-m-d H:i:s', strtotime($time_end_wo_grace.' -'.$minutes_for_time_out.' minutes'));
                                        }
                                            
                                        $work_hrs = $check_break_time_fritz->total_work_hours;
                                        $work_hrs_halfday = ($work_hrs / 2) * 60;
                                            
                                        //get the start and end of break time
                                        $time_in_break = date('Y-m-d H:i:s', strtotime($real_time_in_start.' +'.$work_hrs_halfday.' minutes'));
                                        $time_out_break = date('Y-m-d H:i:s',strtotime($time_in_break. " +{$check_break_time_fritz->break_in_min} minutes"));
                                        
                                        if(strtotime($new_time_out) <= strtotime($time_in_break)) {
                                            $minutes_of_break = 0;
                                        } else {
                                            if(strtotime($new_time_out) >= strtotime($time_in_break) && strtotime($new_time_out) <= strtotime($time_out_break)) {
                                                $diff_breaks_input_and_break = (strtotime($new_time_out) - strtotime($time_in_break)) / 60;
                                                $minutes_of_break = $diff_breaks_input_and_break;
                                            } else {
                                                $minutes_of_break = $check_break_time_fritz->break_in_min;
                                            }
                                        }
                                    }
                                } else {
                                    $minutes_of_break = 0;
                                }
                            } else { // disabled break												
                                $minutes_of_break = 0;
                                $real_time_in_start = "";
                                $real_time_out_start = "";
                            }
                    
                            // check if the application is covered the break
                            $get_end_time_new = $real_time_out_start;
                            $now_time_new = $new_employee_timein_date.' '.$now_time;
                            
                            // FOR TOTAL HOURS REQUIRED								>>>>>>>>>>>>>>>>>>>>>>>>>>>>
                            $now_time = date('Y-m-d', strtotime($new_time_out)).' '.$now_time;
                            $total_hours_required = ((strtotime($now_time) - strtotime($new_time_in)) / 3600) - ($minutes_of_break / 60);
                        }
                    } else {
                        show_error("Payroll set up for break time is empty.");
                    }
                }elseif($flag_halfday == 2){
                    // get tardiness
                    if($check_break_time_fritz) {
                        if($check_break_time_fritz->break_in_min > 0) {
                            $time_start_wo_grace = $new_employee_timein_date.' '.date('H:i:s', strtotime($check_break_time_fritz->work_start_time));
                    
                            if($is_break_assumed) { // assumed break
                                $assumed_breaks = $is_break_assumed->assumed_breaks;
                                $h = $assumed_breaks * 60;
                    
                                $time_in_break = date('Y-m-d H:i:s',strtotime($time_start_wo_grace. " +{$h} minutes"));
                                $time_out_break = date('Y-m-d H:i:s',strtotime($time_in_break. " +{$check_break_time_fritz->break_in_min} minutes"));
                                    
                                // break for tardiness
                                if(strtotime($new_time_in) >= strtotime($time_out_break)) {
                                    $minus_break = $check_break_time_fritz->break_in_min;
                                } else {
                                    if(strtotime($new_time_in) >= strtotime($time_in_break)) {
                                        $diff_breaks_input_and_break = (strtotime($new_time_in) - strtotime($time_in_break)) / 60;
                                        $minus_break = $diff_breaks_input_and_break;
                                    } else {
                                        $minus_break = 0;
                                    }
                                }
                                    
                                // break for total hours
                                if(strtotime($time_in_break) <= strtotime($new_time_in) && strtotime($time_out_break) >= strtotime($new_time_in)) {
                                    $diff_breaks_input_and_break = (strtotime($time_out_break) - strtotime($new_time_in)) / 60;
                                    $minus_break_th = $diff_breaks_input_and_break;
                                } elseif (strtotime($new_time_in) > strtotime($time_out_break)) {
                                    $minus_break_th = 0;
                                } else {
                                    $minus_break_th = $check_break_time_fritz->break_in_min;
                                }
                            } else { // captured
                                $work_hrs = $check_break_time_fritz->total_work_hours;
                                $work_hrs_halfday = ($work_hrs / 2) * 60;
                                    
                                //get the start and end of break time
                                $time_in_break = date('Y-m-d H:i:s', strtotime($time_start_wo_grace.' +'.$work_hrs_halfday.' minutes'));
                                $time_out_break = date('Y-m-d H:i:s',strtotime($time_in_break. " +{$check_break_time_fritz->break_in_min} minutes"));
                                                                                
                                // break for total hours
                                if(strtotime($time_in_break) <= strtotime($new_time_in) && strtotime($time_out_break) >= strtotime($new_time_in)) {
                                    $diff_breaks_input_and_break = (strtotime($time_out_break) - strtotime($new_time_in)) / 60;
                                    $minus_break_th = $diff_breaks_input_and_break;
                                } elseif (strtotime($new_time_in) > strtotime($time_out_break)) {
                                    $minus_break_th = 0;
                                } else {
                                    $minus_break_th = $check_break_time_fritz->break_in_min;
                                }
                            }
                        }
                    } else {
                        $minus_break_th = 0;
                    }
                    
                    // FOR TOTAL HOURS REQUIRED
                    $total_hours_required = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600 - ($minus_break_th / 60);
                }
                
                $new_date = date("Y-m-d",strtotime($new_time_in." -1 day"));                    
                $check_workday = $this->elm->halfday_check_workday($this->work_schedule_id,$this->company_id,$new_date);
                                    
                if($check_workday){
                    // minus 1 day
                    $date_halfday = strtotime($new_date) ? $new_date : NULL ;
                }else{
                    $date_halfday = NULL;
                }
                $get_total_hours = $total_hours_required;
                
                // additional break (new setting for shift)
                if($tardiness_rule_migrated_v3) {
                    $get_total_hours = $calculated_data["total_hours_required"];
                }
                    
                // TOTAL HOURS
                echo json_encode(array(
                        "success"=>"1",
                        "error"=>$error_flag,
                        "etime_out_date" => "",
                        "etime_out_date" => $overlaps,
                        "ecreason" => $for_UE,
                        "total_hours"=>($get_total_hours < 0) ? "0.00" : number_format($get_total_hours,2),
                        "ealunch_out_date" => $can_lunch_out,
                        "ealunch_in_date" => $can_lunch_in,
                        "ebreak_1_start" => $can_break_1_out,
                        "ebreak_1_end" => $can_break_1_in,
                        "ebreak_2_start" => $can_break_2_out,
                        "ebreak_2_end" => $can_break_2_in,
                        
                ));
                return false;
            }else{
                // check employee work schedule
                    
                // check break time
                $check_break_time = $this->employee->check_break_time($this->work_schedule_id,$this->company_id,"work_schedule_id", $new_employee_timein_date);
                
                if(!$check_break_time){ // ZERO VALUE FOR BREAK TIME
                    
                    // update total hours and total hours required rest day
                    $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                    
                    /* EMPLOYEE WORK SCHEDULE */
                    $number_of_breaks_per_day = $this->elmf->check_break_time_flex($this->work_schedule_id,$this->company_id);
        
                    // tardiness and undertime value
                    $update_tardiness = $this->elm->get_tardiness_val($this->emp_id,$this->company_id,$new_time_in,$this->work_schedule_id,$number_of_breaks_per_day);
                    $update_undertime = $this->elm->get_undertime_val($this->emp_id,$this->company_id,$new_time_in,$new_time_out,$this->work_schedule_id,$number_of_breaks_per_day);
                            
                    // check tardiness value
                    $flag_tu = 0;
        
                    $get_total_hours_worked = ($hours_worked / 2) + .5;
                    if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
                        $update_tardiness = 0;
                        $update_undertime = 0;
                        $flag_tu = 1;
                    }
        
                    $get_total_hours = $get_total_hours;
                    
                    // additional break (new setting for shift)
                    if($tardiness_rule_migrated_v3) {
                        $get_total_hours = $calculated_data["total_hours_required"];
                    }
                    
                    // TOTAL HOURS
                    echo json_encode(array(
                        "success"=>"1",
                        "error"=>$error_flag,
                        "etime_out_date" => $overlaps,
                        "ecreason" => $for_UE,
                        "total_hours"=>($get_total_hours < 0) ? "0.00" : number_format($get_total_hours,2),
                        "ealunch_out_date" => $can_lunch_out,
                        "ealunch_in_date" => $can_lunch_in,
                        "ebreak_1_start" => $can_break_1_out,
                        "ebreak_1_end" => $can_break_1_in,
                        "ebreak_2_start" => $can_break_2_out,
                        "ebreak_2_end" => $can_break_2_in,
                            
                    ));
                    return false;                                    
                }else if($new_time_in != "" && $new_time_out != "" && $new_lunch_in != "" && $new_lunch_out != ""){
                    // WHOLEDAY
                    /* EMPLOYEE WORK SCHEDULE */
                    $update_change_logs = $this->elm->get_total_hours_logs($this->company_id, $this->emp_id,$employee_timein, $reason, $new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out, $hours_worked,$this->work_schedule_id,$new_employee_timein_date);
                    $get_total_hours = $update_change_logs;
                    
                    // additional break (new setting for shift)
                    if($tardiness_rule_migrated_v3) {
                        $get_total_hours = $calculated_data["total_hours_required"];
                    }
                    
                    // TOTAL HOURS
                    echo json_encode(array(
                        "success"=>"1",
                        "error"=>$error_flag,
                        "etime_out_date" => $overlaps,
                        "ecreason" => $for_UE,
                        "total_hours"=>($get_total_hours < 0) ? "0.00" : number_format($get_total_hours,2),
                        "ealunch_out_date" => $can_lunch_out,
                        "ealunch_in_date" => $can_lunch_in,
                        "ebreak_1_start" => $can_break_1_out,
                        "ebreak_1_end" => $can_break_1_in,
                        "ebreak_2_start" => $can_break_2_out,
                        "ebreak_2_end" => $can_break_2_in,
                            
                    ));
                    return false;
                } else {
                    // update total hours and total hours required rest day
                    $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                    
                    /* EMPLOYEE WORK SCHEDULE */
                    $number_of_breaks_per_day = $this->elmf->check_break_time_flex($this->work_schedule_id,$this->company_id);
        
                    // tardiness and undertime value
                    $update_tardiness = $this->elm->get_tardiness_val($this->emp_id,$this->company_id,$new_time_in,$this->work_schedule_id,$number_of_breaks_per_day);
                    $update_undertime = $this->elm->get_undertime_val($this->emp_id,$this->company_id,$new_time_in,$new_time_out,$this->work_schedule_id,$number_of_breaks_per_day);
                            
                    // check tardiness value
                    $flag_tu = 0;
        
                    $get_total_hours_worked = ($hours_worked / 2) + .5;
                    if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
                        $update_tardiness = 0;
                        $update_undertime = 0;
                        $flag_tu = 1;
                    }
        
                    // required hours worked only
                    $new_lunch_out = null;
                    $new_lunch_in = null;
        
                    $get_total_hours = $get_total_hours;
                    
                    // additional break (new setting for shift)
                    if($tardiness_rule_migrated_v3) {
                        $get_total_hours = $calculated_data["total_hours_required"];
                    }

                    
                    // TOTAL HOURS
                    echo json_encode(array(
                        "success"=>"1",
                        "error"=>$error_flag,
                        "etime_out_date" => $overlaps,
                        "ecreason" => $for_UE,
                        "total_hours"=>($get_total_hours < 0) ? "0.00" : number_format($get_total_hours,2),
                        "ealunch_out_date" => $can_lunch_out,
                        "ealunch_in_date" => $can_lunch_in,
                        "ebreak_1_start" => $can_break_1_out,
                        "ebreak_1_end" => $can_break_1_in,
                        "ebreak_2_start" => $can_break_2_out,
                        "ebreak_2_end" => $can_break_2_in,
                            
                    ));
                    return false;
                }
            }	
        }else if($check_work_type == "Flexible Hours"){
            
            #if($check_rest_day || $check_holiday){
            if($check_rest_day || $check_holiday || $excess_logs == true){
                $tardiness = 0;
                $undertime = 0;
                    
                // update total hours and total hours required rest day
                $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                $get_total_hours = $get_total_hours;
                    
                // TOTAL HOURS
                echo json_encode(array(
                    "success"=>"1",
                    "error"=>$error_flag,
                    "etime_out_date" => $overlaps,
                    "ecreason" => $for_UE,
                    "total_hours"=>($get_total_hours < 0) ? "0.00" : number_format($get_total_hours,2)
                        
                ));
                return false;
                    
            }else if($new_lunch_out == NULL && $new_lunch_in == NULL && $flaghalfday == 1){
                    
                // EMPLOYEE WORK SCHEDULE
                $check_hours_flexible = $this->elm->check_hours_flex($this->company_id,$this->work_schedule_id);
                    
                $get_hoursworked = $this->elmf->get_hoursworked($this->work_schedule_id,$this->company_id)->total_hours_for_the_day;
                $check_latest_timein_allowed = $this->elmf->check_lastest_timein_allowed($this->work_schedule_id,$this->company_id);
                    
                if(!$check_latest_timein_allowed){
                    // check workday settings
                    $workday_settings_start_time = date("H:i:s",strtotime($new_time_in));
                    $workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
                        
                }else{
                    // check workday settings
                    $workday_settings_start_time = date("H:i:s",strtotime($check_latest_timein_allowed->latest_time_in_allowed));
                    $workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
                        
                }
                    
                $get_different = ($this->employee->new_hoursworked(date("Y-m-d",strtotime($new_employee_timein_date)),$this->emp_id)) / 2;
                $get_between = date("Y-m-d H:i:s",strtotime(date("Y-m-d",strtotime($new_employee_timein_date))." ".$workday_settings_start_time." + {$get_different} hour"));
                
                if(strtotime($get_between) < strtotime($new_time_in)){
                    /* for afternoon */
                    // get undertime
                    if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
                        $undertime = (strtotime($workday_settings_end_time) - strtotime($new_time_out)) / 60;
                    }
                        
                    // calculate total hours
                    $newtimein = (strtotime($new_time_in) < strtotime($get_between)) ? $get_between : $new_time_in ;
                    $th = (strtotime($new_time_out) - strtotime($newtimein)) / 3600;
                    $hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$this->emp_id);
                        
                    // new tardiness calculation
                    $tardiness = (($hw - $th) * 60) - $undertime;
                        
                    // total hours
                    $total_hours = 	(strtotime($new_time_out) - strtotime($newtimein)) / 3600;
                    $total_hours_required = (strtotime($new_time_out) - strtotime($newtimein)) / 3600;
                        
                }else{
                    /* for morning */
                    // get tardiness
                    if(strtotime($workday_settings_start_time) < strtotime($new_time_in)){
                        $tardiness = (strtotime($new_time_in) - strtotime($workday_settings_start_time)) / 60;
                    }
                        
                    if($check_hours_flexible != FALSE){
                        $f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$check_hours_flexible;
                        $tardiness = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
                    }else{
                        $f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
                        $tardiness = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
                    }
                        
                    // calculate total hours
                    $newtimeout = (strtotime($get_between) > strtotime($new_time_out)) ? $get_between : $new_time_out ; // fritz here
                    //$newtimeout = (strtotime($get_between) < strtotime($new_time_out)) ? $get_between : $new_time_out ; // original
                    $th = (strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
                    $hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_employee_timein_date)),$this->emp_id);
                        
                    // new tardiness calculation
                    $undertime = (($hw - $th) * 60) - $tardiness;
                        
                    // total hours
                    $total_hours = 	(strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
                    $total_hours_required = (strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
                        
                }
                // UPDATE TIME INS
                $get_total_hours = $total_hours;
                
                // TOTAL HOURS
                echo json_encode(array(
                    "success"=>"1",
                    "error"=>$error_flag,
                    "etime_out_date" => $overlaps,
                    "ecreason" => $for_UE,
                    "total_hours"=>($get_total_hours < 0) ? "0.00" : number_format($get_total_hours,2)
                        
                ));
                return false;
                    
            }else{
                /* WHOLE DAY */
                $get_hoursworked = $this->elmf->get_hoursworked($this->work_schedule_id,$this->company_id)->total_hours_for_the_day;
                
                // check workday settings
                $workday_settings_start_time = date("H:i:s",strtotime($new_time_in));
                $workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
                $check_latest_timein_allowed = $this->elmf->check_lastest_timein_allowed($this->work_schedule_id,$this->company_id);
                if(!$check_latest_timein_allowed){
                    
                    $number_of_breaks_per_day = $this->elmf->check_break_time_flex($this->work_schedule_id,$this->company_id);
                    if($number_of_breaks_per_day == 0){
        
                        // update total hours and total hours required rest day
                        $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
        
                        // update total tardiness
                        $update_tardiness = 0;
        
                        // update undertime
                        $update_undertime = 0;
                        if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
                            $hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
                            $workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
                        }
                        if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
                            $new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
                            $update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
                        }
        
                        // check tardiness value
                        $flag_tu = 0;
        
                        $hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
                        $get_total_hours_worked = ($hours_worked / 2) + .5;
                        if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
                            $update_tardiness = 0;
                            $update_undertime = 0;
                            $flag_tu = 1;
                        }
        
                        // required hours worked only
                        $new_total_hours = $this->elmf->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_time_out,$hours_worked,$this->work_schedule_id,$number_of_breaks_per_day);
        
                        // if value is less then 0 then set value to 0
                        if($update_tardiness < 0) $update_tardiness = 0;
                        if($update_undertime < 0) $update_undertime = 0;
                        if($new_total_hours < 0) $new_total_hours = 0;
                        if($get_total_hours < 0) $get_total_hours = 0;
                        $get_total_hours = $new_total_hours;
                            
                        // TOTAL HOURS
                        echo json_encode(array(
                            "success"=>"1",
                            "error"=>$error_flag,
                            "etime_out_date" => $overlaps,
                            "ecreason" => $for_UE,
                            "total_hours"=>($get_total_hours < 0) ? "0.00" : number_format($get_total_hours,2)
                                
                        ));
                        return false;
                            
                    }else{
                        // update tardiness for timein
                        $tardiness_timein = 0;
        
                        // update tardiness for break time
                        $update_tardiness_break_time = 0;
                        $duration_of_lunch_break_per_day = $this->elmf->duration_of_lunch_break_per_day($this->emp_id, $this->company_id, $this->work_schedule_id);
                        $tardiness_a = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
                        if($duration_of_lunch_break_per_day < $tardiness_a){
                            $update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
                        }
        
                        // update total tardiness
                        $update_tardiness = $tardiness_timein + $update_tardiness_break_time;
        
                        // update undertime
                        $update_undertime = 0;
                        if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
                            $hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
                            $workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
                        }
                        if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
                            $new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
                            $update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
                        }
        
                        // update total hours
                        $hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
                        $update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - ($duration_of_lunch_break_per_day / 60);
        
                        // check tardiness value
                        $get_total_hours_worked = ($hours_worked / 2) + .5;
                        if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
                            $update_tardiness = 0;
                            $update_undertime = 0;
                            $flag_tu = 1;
                        }
        
                        // update total hours required
                        $update_total_hours_required = ((strtotime($new_time_out) - strtotime($new_time_in)) / 3600) - ($update_tardiness / 60) - ($duration_of_lunch_break_per_day / 60);
        
                        // if value is less then 0 then set value to 0
                        if($update_tardiness < 0) $update_tardiness = 0;
                        if($update_undertime < 0) $update_undertime = 0;
                        if($update_total_hours < 0) $update_total_hours = 0;
                        if($update_total_hours_required < 0) $update_total_hours_required = 0;
                        
                        $get_total_hours = $update_total_hours;
                        
                        // TOTAL HOURS
                        echo json_encode(array(
                            "success"=>"1",
                            "error"=>$error_flag,
                            "etime_out_date" => $overlaps,
                            "ecreason" => $for_UE,
                            "total_hours"=>($get_total_hours < 0) ? "0.00" : number_format($get_total_hours,2)
                                
                        ));
                        return false;
                    }
                }else{
                    $number_of_breaks_per_day = $this->elmf->check_break_time_flex($this->work_schedule_id,$this->company_id);
                    if($number_of_breaks_per_day == 0){
        
                        // update total hours and total hours required rest day
                        $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
        
                        // update tardiness for timein
                        $tardiness_timein = 0;
                        if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
                            if(date("A",strtotime($new_time_in)) == "AM"){
                                // add one day for time in log
                                $new_start_timein = date("Y-m-d",strtotime($new_time_in." -1 day"));
                                $new_start_timein = $new_start_timein." ".$workday_settings_start_time;
        
                                if(strtotime($new_start_timein) < strtotime($new_time_in)){
                                    $tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;
                                }
                            }
                        }else{
                            $new_start_timein = date("Y-m-d",strtotime($new_time_in));
                            $new_start_timein = $new_start_timein." ".$workday_settings_start_time;
                                
                            if(strtotime($new_start_timein) < strtotime($new_time_in)){
                                $tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;
                            }
                        }
                            
                        // update total tardiness
                        $update_tardiness = $tardiness_timein;
        
                        // update undertime
                        $update_undertime = 0;
                        if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
                            $hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
                            $workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
                        }
                        if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
                            $new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
                            $update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
                        }
        
                        // check tardiness value
                        $flag_tu = 0;
        
                        $hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
                        $get_total_hours_worked = ($hours_worked / 2) + .5;
                        if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
                            $update_tardiness = 0;
                            $update_undertime = 0;
                            $flag_tu = 1;
                        }
        
                        // required hours worked only
                        $new_total_hours = $this->elmf->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_time_out,$hours_worked,$this->work_schedule_id,$number_of_breaks_per_day,$new_employee_timein_date);
                        
                        // if value is less than 0 then set value to 0
                        if($update_tardiness < 0) $update_tardiness = 0;
                        if($update_undertime < 0) $update_undertime = 0;
                        if($new_total_hours < 0) $new_total_hours = 0;
                        if($get_total_hours < 0) $get_total_hours = 0;
        
                        // UPDATE TIME INS
                        $get_total_hours = $new_total_hours;
                            
                        // TOTAL HOURS
                        echo json_encode(array(
                            "success"=>"1",
                            "error"=>$error_flag,
                            "etime_out_date" => $overlaps,
                            "ecreason" => $for_UE,
                            "total_hours"=>($get_total_hours < 0) ? "0.00" : number_format($get_total_hours,2)
                                
                        ));
                        return false;
                    }else{
        
                        // update tardiness for timein
                        $tardiness_timein = 0;
                        if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
                            if(date("A",strtotime($new_time_in)) == "AM"){
                                // add one day for time in log
                                $new_start_timein = date("Y-m-d",strtotime($new_time_in." -1 day"));
                                $new_start_timein = $new_start_timein." ".$workday_settings_start_time;
        
                                if(strtotime($new_start_timein) < strtotime($new_time_in)){
                                    $tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;
                                }
                            }
                        }else{
                            $new_start_timein = date("Y-m-d",strtotime($new_time_in));
                            $new_start_timein = $new_start_timein." ".$workday_settings_start_time;
                                
                            if(strtotime($new_start_timein) < strtotime($new_time_in)){
                                $tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;
                            }
                        }
        
                        // update tardiness for break time
                        $update_tardiness_break_time = 0;
                        $duration_of_lunch_break_per_day = $this->elmf->duration_of_lunch_break_per_day($this->emp_id, $this->company_id, $this->work_schedule_id);
                        $tardiness_a = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
                        if($duration_of_lunch_break_per_day < $tardiness_a){
                            $update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
                        }
        
                        // update total tardiness
                        $update_tardiness = $tardiness_timein + $update_tardiness_break_time;
        
                        // update undertime
                        $update_undertime = 0;
                        if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
                            $hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
                            $workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
                        }
                        if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
                            $new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
                            $update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
                        }
        
                        // update total hours
                        $hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
                        $update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - ($duration_of_lunch_break_per_day / 60);
        
                        // check tardiness value
                        $get_total_hours_worked = ($hours_worked / 2) + .5;
                        if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
                            $update_tardiness = 0;
                            $update_undertime = 0;
                            $flag_tu = 1;
                        }
        
                        // update total hours required
                        $calc_tot_break = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 3600;
                        $actual_break = ($duration_of_lunch_break_per_day / 60);
                        
                        if($calc_tot_break > $actual_break) {
                            $excess_break = $calc_tot_break - $actual_break;
                        } else {
                            $excess_break = 0;
                        }
        
                        // update total hours required
                        $update_total_hours_required = (((strtotime($new_time_out) - strtotime($new_time_in)) / 3600) - ($update_tardiness / 60) - ($duration_of_lunch_break_per_day / 60)) - $excess_break;
        
                        // if value is less than 0 then set value to 0
                        if($update_tardiness < 0) $update_tardiness = 0;
                        if($update_undertime < 0) $update_undertime = 0;
                        if($update_total_hours < 0) $update_total_hours = 0;
                        if($update_total_hours_required < 0) $update_total_hours_required = 0;
        
                        $get_total_hours = $update_total_hours_required;
                        
                        // TOTAL HOURS
                        echo json_encode(array(
                            "success"=>"1",
                            "error"=>$error_flag,
                            "etime_out_date" => $overlaps,
                            "ecreason" => $for_UE,
                            "total_hours"=>($get_total_hours < 0) ? "0.00" : number_format($get_total_hours,2)
                                
                        ));
                        return false;
                    }
                }
            }
        
        }elseif($check_work_type == "Workshift"){
            //CHECKS IF RESTDAY OR NOT
            #if($check_rest_day || $check_holiday){
            if($check_rest_day){
                $tardiness = 0;
                $undertime = 0;
        
                // update total hours and total hours required rest day
                $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                    
                // UPDATE TIME INS
                $where_tot = array(
                        "comp_id"=>$this->company_id,
                        "emp_id"=>$this->emp_id,
                        "schedule_blocks_time_in_id"=>$employee_timein
                );
                $get_total_hours = $get_total_hours;
                    
                // TOTAL HOURS
                echo json_encode(array(
                        "success"=>"1",
                        "error"=>$error_flag,
                        "etime_out_date" => $overlaps,
                        "total_hours"=>($get_total_hours < 0) ? "0.00" : number_format($get_total_hours,2)
                        
                ));
                return false;
                    
            }else{
                // check employee work schedule
                // check break time
                $check_break_time = $this->ews->check_breaktime_split1($this->company_id, $this->emp_id, date("Y-m-d",strtotime($new_employee_timein_date)), $this->work_schedule_id);
                
                if($check_break_time->break_in_min == 0 || $check_break_time->break_in_min == null){ // ZERO VALUE FOR BREAK TIME
                
                    // update total hours and total hours required rest day
                    $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
                        
                    /* EMPLOYEE WORK SCHEDULE */
                    $number_of_breaks_per_day = $check_break_time->break_in_min;; // $this->elmf->check_break_time_flex($this->work_schedule_id,$this->company_id);
        
                    // tardiness and undertime value
                    $update_tardiness = $this->elm->get_tardiness_val($this->emp_id,$this->company_id,$new_time_in,$this->work_schedule_id,$number_of_breaks_per_day);
                    $update_undertime = $this->elm->get_undertime_val($this->emp_id,$this->company_id,$new_time_in,$new_time_out,$this->work_schedule_id,$number_of_breaks_per_day);
        
                    // hours worked
                    $hours_worked = $this->elm->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
        
                    // check tardiness value
                    $flag_tu = 0;
        
                    $get_total_hours_worked = ($hours_worked / 2) + .5;
                    if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
                        $update_tardiness = 0;
                        $update_undertime = 0;
                        $flag_tu = 1;
                    }
        
                    // required hours worked only
        
                    $new_total_hours = $this->elm->get_tot_hours($this->emp_id,$this->company_id,$new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out,$hours_worked,$this->work_schedule_id);
        
                    $get_total_hours = $get_total_hours;
                    
                    // TOTAL HOURS
                    echo json_encode(array(
                        "success"=>"1",
                        "error"=>$error_flag,
                        "etime_out_date" => $overlaps,
                        "ecreason" => $for_UE,
                        "total_hours"=>($get_total_hours < 0) ? "0.00" : number_format($get_total_hours,2)
                            
                    ));
                    return false;				
                }else if($new_time_in != "" && $new_time_out != "" && $new_lunch_in != "" && $new_lunch_out != ""){
                    // WHOLEDAY
                    $hours_worked = $this->elm->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $this->emp_id, $this->work_schedule_id);
                    
                    /* EMPLOYEE WORK SCHEDULE */
                    $update_change_logs = $this->elm->get_total_hours_logs($this->company_id, $this->emp_id,$employee_timein, $reason, $new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out, $hours_worked,$this->work_schedule_id);
                    $get_total_hours = $update_change_logs;
                    
                    // TOTAL HOURS
                    echo json_encode(array(
                        "success"=>"1",
                        "error"=>$error_flag,
                        "etime_out_date" => $overlaps,
                        "ecreason" => $for_UE,
                        "total_hours"=>($get_total_hours < 0) ? "0.00" : number_format($get_total_hours,2)
                            
                    ));
                    return false;
                }
            }
        } elseif($check_work_type == "Open Shift"){
            $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
            
            // TOTAL HOURS
            echo json_encode(array(
                "success"=>"1",
                "error"=>$error_flag,
                "etime_out_date" => $overlaps,
                "total_hours"=>($get_total_hours < 0) ? "0.00" : number_format($get_total_hours,2)
                
            ));
            return false;
        }
            
        echo json_encode(array("success"=>"0"));
        return false;
    }
		
    public function if_payroll_is_locked() {
        // check if payroll is locked or closed
        $start_date = $this->input->post('start_date');
        $locked = "";
        
        if($start_date) {
        	$void = $this->employee_v2->check_payroll_lock_closed($this->emp_id,$this->company_id,date("Y-m-d", strtotime($start_date)));
            
            if($void == "Waiting for approval"){
                $locked = "Timesheet locked for payroll processing.";
            } elseif ($void == "Closed") {
                $locked = "The timesheet you are submitting is part of a closed payroll. Your request will be routed to the appropriate approvers for consideration and approval.";
            }
            
            if($locked != "") {
                $result = array(
                    'result' => 1,
                    'error' => true,
                    'err_msg' => $locked
                );
                
                echo json_encode($result);
                return false;
            } else {                    
                return false;
            }
        }
    }
	
	/**
	 * Send Email Notification to Group Approvers - Add Logs (LEVEL 1)
	 */
	public function send_change_add_logss_notifcation($flag = NULL, $new_logs = NULL, $token = NULL, $employee_timein = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "" , $withlink = "No", $level_token = "", $appr_id = "", $tardiness_rule_migrated_v3 = false, $notify_admin = ""){
		
		$check_work_type = $this->employee->work_schedule_type($this->work_schedule_id, $this->company_id);
		if($check_work_type == 'Workshift') {
			$q = $this->employee->check_split_timein($emp_id,$comp_id,$employee_timein);
		} else {
			$q = $this->employee->check_timein($emp_id,$comp_id,$employee_timein);
		}
		
		if($q != FALSE){
		    $get_schedule_settings = get_schedule_settings_by_workschedule_id($q->work_schedule_id,$this->company_id,date("l", strtotime($q->date)));
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
		    
			$fullname = $this->employee->get_employee_fullname($emp_id,$this->company_id);
			$date_applied = date('F d, Y', strtotime($q->change_log_date_filed));
			$shift_date  = $q->date == NULL ? "none" : date("F d, Y",strtotime($q->date));
			$time_in     = $q->time_in == NULL ? "none" : date("F d, Y h:i A",strtotime($q->time_in));
			$lunch_out   = $q->lunch_out == NULL ? "none" : date("F d, Y h:i A",strtotime($q->lunch_out));
			$lunch_in    = $q->lunch_in == NULL ? "none" : date("F d, Y h:i A",strtotime($q->lunch_in));
			$time_out    = $q->time_out == NULL ? "none" : date("F d, Y h:i A",strtotime($q->time_out));
			
			$new_time_in     = $new_logs['new_time_in'] == NULL ? "none" : date("F d, Y h:i A",strtotime($new_logs['new_time_in']));
			$new_lunch_out   = $new_logs['new_lunch_out'] == NULL ? "none" : date("F d, Y h:i A",strtotime($new_logs['new_lunch_out']));
			$new_lunch_in    = $new_logs['new_lunch_in'] == NULL ? "none" : date("F d, Y h:i A",strtotime($new_logs['new_lunch_in']));
			$new_time_out    = $new_logs['new_time_out'] == NULL ? "none" : date("F d, Y h:i A",strtotime($new_logs['new_time_out']));
            
            $color1 = "";
            $color2 = "";
            $color3 = "";
            $color4 = "";
            $color5 = "";
            $color6 = "";
            $color7 = "";
            $color8 = "";
            $default_color = "color:#000;";
            
            if($flag == "change") {
                if($q->time_in != $q->change_log_time_in) {
                    $color1 = 'color:red !important;';
                    $color1 = ($color1 != "") ? $color1 : $default_color;
                }
                
                if($q->lunch_out != $q->change_log_lunch_out) {
                    $color2 = 'color:red !important;';
                    $color2 = ($color2 != "") ? $color2 : $default_color;
                }
                
                if($q->lunch_in != $q->change_log_lunch_in) {
                    $color3 = 'color:red !important;';
                    $color3 = ($color3 != "") ? $color3 : $default_color;
                }
                
                if($q->break1_out != $q->change_log_break1_out) {
                    $color4 = 'color:red !important;';
                    $color4 = ($color4 != "") ? $color4 : $default_color;
                }
                
                if($q->break1_in != $q->change_log_break1_in) {
                    $color5 = 'color:red !important;';
                    $color5 = ($color5 != "") ? $color5 : $default_color;
                }
                
                if($q->break2_out != $q->change_log_break2_out) {
                    $color6 = 'color:red !important;';
                    $color6 = ($color6 != "") ? $color6 : $default_color;
                }
                
                if($q->break2_in != $q->change_log_break2_in) {
                    $color7 = 'color:red !important;';
                    $color7 = ($color7 != "") ? $color7 : $default_color;
                }
                
                if($q->time_out != $q->change_log_time_out) {
                    $color8 = 'color:red !important;';
                    $color8 = ($color8 != "") ? $color8 : $default_color;
                }
            }
            
			if($check_work_type != 'Workshift') {
			    $break_1_start_date_time = $q->break1_out == NULL ? "none" : date("F d, Y h:i A",strtotime($q->break1_out));
			    $break_1_end_date_time   = $q->break1_in == NULL ? "none" : date("F d, Y h:i A",strtotime($q->break1_in));
			    $break_2_start_date_time = $q->break2_out == NULL ? "none" : date("F d, Y h:i A",strtotime($q->break2_out));
			    $break_2_end_date_time   = $q->break2_in == NULL ? "none" : date("F d, Y h:i A",strtotime($q->break2_in));
			    
			    $new_break_1_start_date_time = $q->change_log_break1_out == NULL ? "none" : date("F d, Y h:i A",strtotime($q->change_log_break1_out));
			    $new_break_1_end_date_time   = $q->change_log_break1_in == NULL ? "none" : date("F d, Y h:i A",strtotime($q->change_log_break1_in));
			    $new_break_2_start_date_time = $q->change_log_break2_out == NULL ? "none" : date("F d, Y h:i A",strtotime($q->change_log_break2_out));
			    $new_break_2_end_date_time   = $q->change_log_break2_in == NULL ? "none" : date("F d, Y h:i A",strtotime($q->change_log_break2_in));
			}
			
			$font_name = "'Open Sans'";
            if($check_work_type == 'Workshift') {
                $link = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="'.base_url().'approval/time_in/index/'.$token.'/'.$level_token.'/1'.$appr_id.'0/'.$employee_timein.'">View Attendance Adjustment</a>';
            } else {
                $link = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="'.base_url().'approval/time_in/index/'.$token.'/'.$level_token.'/1'.$appr_id.'0">View Attendance Adjustment</a>';
            }
            
            $subject = "";
            if($flag == "change"){
                $subject = 'Adjustment';
                $subject_line = "Action Required: {$fullname}'s Attendance Adjustment is awaiting your approval.";
                $title_line = "Attendance Adjustment";
            }else{
                $subject = 'Logs';
                $subject_line = "Action Required: {$fullname}'s New Timesheet submitted is awaiting your approval.";
                $title_line = "New Timesheet";
            }
            
            if($who == "Approver"){
                if($withlink == "No"){
                    $link = '';
                    $subject_line = "Coming your way, {$fullname}'s {$title_line} has been submitted.";
                }
            }else{
                $link = "";
                $subject_line = "Coming your way, {$fullname}'s {$title_line} has been submitted.";
                
                if($notify_admin == "Yes") {
                    $subject_line = "Heads up! {$fullname}'s {$title_line} has been submitted.";
                }
                
            }
			
			$message_body_additional_break_add = "";
			$message_body_additional_break_change = "";
			
			if($tardiness_rule_migrated_v3 && $check_work_type != 'Workshift') {
			    $message_body_additional_break_add1 = "";
			    $message_body_additional_break_add2 = "";
			    
			    if($check_break_1_in_min) {
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
			    
			    if($check_break_2_in_min) {
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
			    
			    if($check_break_1_in_min) {
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
			    
			    if($check_break_2_in_min) {
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
			
			$message_body = "";
			if($flag == "change"){
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
							<td valign="top" style="font-size:15px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;"><strong>CHANGE FROM:</strong></td>
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
							<td valign="top" style="font-size:15px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;"><strong>CHANGE TO:</strong></td>
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
							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_logs['reason'].'</td>
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
        					<td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Shift Date:</td>
        					<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$shift_date.'</td>
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
						<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Reason:</td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_logs['reason'].'</td>
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
			$this->email->subject('Attendance '.$subject.' Application - '.$fullname);
				
			$this->email->message('
		<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
		<html lang="en">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
				<meta name="format-detection" content="telephone=no">
				<title>Attendance '.$subject.'</title>
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
																<p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">New Attendance '.$subject.' has been filed. Details below:</p>
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
            return false;
			// show_error("Invalid token");
		}
	}

	/**
	 * Send Email Notification to Group Approvers - Add Logs (LEVEL 1)
	 */

	public function send_location_notification($img_name = NULL, $location = NULL, $new_logs = NULL, $token = NULL, $employee_timein = NULL, 
		$comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "" , $withlink = "No", 
		$level_token = "", $appr_id = "", $mobile_status = "", $work_sched_id = "", $split_timein_id="", $check_work_type_form=""){
		
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
																	<p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">'.$fullname.' used app for clock-in. Details below:</p>
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
	
	public function send_location_notification_karaan($location = NULL, $new_logs = NULL, $token = NULL, $employee_timein = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "" , $withlink = "No", $level_token = "", $appr_id = "", $mobile_status = ""){
		
		$check_work_type = $this->employee->work_schedule_type($this->work_schedule_id, $this->company_id);
		if($check_work_type == 'Workshift') {
			$q = $this->employee->check_split_timein($emp_id,$comp_id,$employee_timein);
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
			
			if($mobile_status == 'mobile_clockin_status') {
				$clock_in_label = 'Clock In';
				$clock_in_date = date('F d, Y h:i A', strtotime($q->time_in));
				$clock_location = $location_1;
			} elseif ($mobile_status == 'mobile_lunchout_status') {
				$clock_in_label = 'Lunch Out';
				$clock_in_date = date('F d, Y h:i A', strtotime($q->lunch_out));
				$clock_location = $location_2;
			} elseif ($mobile_status == 'mobile_lunchin_status') {
				$clock_in_label = 'Lunch In	';
				$clock_in_date = date('F d, Y h:i A', strtotime($q->lunch_in));
				$clock_location = $location_3;
			} elseif ($mobile_status == 'mobile_clockout_status') {
				$clock_in_label = 'Clock Out';
				$clock_in_date = date('F d, Y h:i A', strtotime($q->time_out));
				$clock_location = $location_4;
			} else {
				$clock_in_label = '';
				$clock_in_date = '';
				$clock_location = '';
			}
			
			$message_body = "";
		
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
																	<p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">'.$fullname.' used app for clock-in. Details below:</p>
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

	public function send_approvals($img = NULL, $emp_id, $company_id, $timein_id, $employee_name, $approver_id, 
        $newtimein_approver, $hours_notification, $is_workflow_enabled, $location, 
        $log_type="time_in", $work_sched_id, $punch_date, $split_timein_id="")
    {

        $check_work_type_form = $this->employee->work_schedule_type($work_sched_id, $company_id);

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
                                $this->send_location_notification_new($img, $location, $new_logs, $shuffled, $employee_timein, 
                                    $company_id, $emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2, 
                                    $appr_id, $mobile_status, $work_sched_id, $split_timein_id, $check_work_type_form);

                                if($hours_notification->message_board_notification == "yes"){
                                    #$token = $this->timeins->get_token($timein_info->approval_time_in_id, $this->company_id, $timein_info->emp_id);
                                    $url = base_url()."approval/employee_time_in/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
                                    $next_appr_notif_message = "{$employee_name} used app for clock-in and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
                                    send_to_message_board($this->psa_id, $appr_id, $this->account_id, $company_id, $next_appr_notif_message, "system", "warning");
                                }
                                
                                $lflag = 1;
                            } else {
                                // send notification without link
                                $this->send_location_notification_new($img, $location, $new_logs, $shuffled, $employee_timein, 
                                    $company_id, $emp_id, $appr_email, $appr_name, "", "", "", "", "", 
                                    $mobile_status, $work_sched_id, $split_timein_id, $check_work_type_form);

                                if ($hours_notification->message_board_notification == "yes") {
                                    $next_appr_notif_message = "{$employee_name} used app for clock-in.";
                                    send_to_message_board($this->psa_id, $appr_id, $this->account_id, $company_id, $next_appr_notif_message, "system", "warning");
                                }
                            }
                        }
                    }

                    if ($check_work_type_form == "Workshift") {
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
                        $timein_update = array('approval_time_in_id'=>$id);
                        $this->db->where('schedule_blocks_time_in_id', $emp_time_id);
                        $this->db->update('schedule_blocks_time_in',$timein_update);
                        $appr_err="";
                    } else {
                        $save_token = array(
                                "time_in_id"    => $emp_time_id,
                                "token"         => $shuffled,
                                "comp_id"       => $company_id,
                                "emp_id"        => $emp_id,
                                "approver_id"   => $approver_id,
                                "level"         => $new_level,
                                "token_level"   => $shuffled2,
                                "location"      => $location_field,
                                "flag_add_logs" => 2
                        );
                        $save_token_q = $this->db->insert("approval_time_in",$save_token);
                        $id = $this->db->insert_id();
                        $timein_update = array('approval_time_in_id'=>$id);
                        $this->db->where('employee_time_in_id', $emp_time_id);
                        $this->db->update('employee_time_in',$timein_update);
                        $appr_err="";
                    }

                } else {
                    if($check_work_type_form == "Workshift"){
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
                        $timein_update = array('approval_time_in_id' => $id);
                        $this->db->where('schedule_blocks_time_in_id', $emp_time_id);
                        $this->db->update('schedule_blocks_time_in', $timein_update);
                        $appr_err = "No Hours Notification";
                    } else {
                        $save_token = array(
                                "time_in_id"    => $emp_time_id,
                                "token"         => $shuffled,
                                "comp_id"       => $this->company_id,
                                "emp_id"        => $this->emp_id,
                                "approver_id"   => $approver_id,
                                "level"         => 1,
                                "token_level"   => $shuffled2,
                                "location"      => $location_field,
                                "flag_add_logs" => 2
                        );
                        $save_token_q = $this->db->insert("approval_time_in",$save_token);
                        $id = $this->db->insert_id();
                        $timein_update = array('approval_time_in_id'=>$id);
                        $this->db->where('employee_time_in_id', $emp_time_id);
                        $this->db->update('employee_time_in',$timein_update);
                        $appr_err = "No Hours Notification";
                    }
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

    public function send_location_notification_new($img_name = NULL, $location = NULL, $new_logs = NULL, $token = NULL, $employee_timein = NULL, 
        $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "" , $withlink = "No", 
        $level_token = "", $appr_id = "", $mobile_status = "", $work_sched_id = "", $split_timein_id="", $check_work_type_form=""){
        
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


	function test_email() {
		$approver_full_name = "Jane Doe";
		$fullname = "John Doe";
		$comp_id = "62";
		$appr_id = "1234";
		$level_token = "1";
		$token = "123456789";
		$font_name = "'Open Sans'";
		$new_logs['ret_date'] = "2019-10-01";
		$clock_in_label = "Clock in";
		$clock_in_date = "2019-10-01";
		$clock_location = "3 Forest Hills, Banawa, Cebu City";
		$link = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="'.base_url().'approval/employee_time_in/index/'.$token.'/'.$level_token.'/1'.$appr_id.'0/">View Mobile Login</a>';
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
		
		$img_dis = ''; // '<img style="position: absolute; top: -80px; right: -105px; border: 1px solid; padding: 5px; max-width: 100%; height: auto;" src="https://ashima.ph/uploads/companies/28/LOGIN_11001_2019-09-23_15-19-47.jpg"  alt="clock in pic">';
		
		$eh_meyl = '
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
																<p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">'.$fullname.' used app for clock-in. Details below:</p>
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
									<td valign="top"><img src="http://payrollv3.konsum.local/assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</body>
		</html>
		';
		print($eh_meyl);
	}
    /**
     * Get Tardy logs
     */
	public function get_all_tardy_logs()
	{
		$page = $this->input->post('page');
		$limit = $this->input->post('limit');

		$this->per_page = 10;

		$tardi_list = $this->employee->get_tardi_list($this->company_id, $this->emp_id, false, (($page-1) * $this->per_page), $limit);
		
		// $is_migrated = for_list_tardiness_rule_migrated_v3($this->company_id);
		// p($tardi_list);

		// $get_timesheet_list_mobile = $this->mobile->get_timesheet_list_mobile($this->emp_id,$this->company_id,false,$status,(($page-1) * $this->per_page),$limit);
		$total = $this->employee->get_tardi_list($this->company_id, $this->emp_id,true);

		if($tardi_list){
			echo json_encode(array(
				"result" => "1", 
				"page" => $page, 
				"numPages" => $limit, 
				"total" => $total, 
				"list" => $tardi_list));
			
			return false;
		}else{
			echo json_encode(array("result" => "0"));
			return false;
		}
    }
    
    public function check_locks() {
        $lock_this_timesheet = false;
        $recal_timesheet = false;
        $recal_payroll = false;
        // check if the application is lock for filing

        // $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id, "change log",$this->emp_id, date("Y-m-d", strtotime($change_log_date)));
        $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id);
        if($get_lock_payroll_process_settings) {
            if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                $lock_this_timesheet = true;
                $recal_timesheet = true;
                $recal_payroll = true;
            } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                $lock_this_timesheet = true;
                $recal_timesheet = true;
                $recal_payroll = true;
            } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                $lock_this_timesheet = true;
                $recal_timesheet = true;
                $recal_payroll = true;
            }
        }

        echo json_encode(array(
            "error" => false,
            "lock_this_timesheet" => $lock_this_timesheet,
            "recal_timesheet" => $recal_timesheet,
            "recal_payroll" => $recal_payroll

        ));
        return false;
    }

	public function check_breaks()
	{
		$employee_timein_date = $this->input->post('datetime');
		$employee_timein_date = ($employee_timein_date) ? date("Y-m-d", strtotime($employee_timein_date)) : '';
		$employee_timein_date1 = '';
		$new_employee_timein_time = '01:00 AM';
		$flag = '';
		$flag_halfday = '';

		$lock_this_timesheet = false;

		$get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id);
        if($get_lock_payroll_process_settings) {
            if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                $lock_this_timesheet = true;
            } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                $lock_this_timesheet = true;
            } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                $lock_this_timesheet = true;
            }
        }

        if ($lock_this_timesheet) {

	        echo json_encode(array(
					"error" => true,
					"break_in_min" => '',
				    "lunch_break_hours_started" => '',
				    "lunch_break_hours_ended" => '',
				    "break_1" => '',
				    "break_1_hours_started" => '',
				    "break_1_hours_ended" => '',
				    "break_2" => '',
				    "break_2_hours_started" => '',
				    "break_2_hours_ended" => '',
					"existing_log" => '',
					"admin_lock" => 'Filing of approval of attendance adjustment is currently suspended by your admin.'
			));
			return false;
		}

		if ($this->input->post('get_breaks')) {

			$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date('Y-m-d', strtotime($employee_timein_date)));
			$check_break_time = $this->employee->check_break_time($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($employee_timein_date)));
			$check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($employee_timein_date)));
				
			$check_work_type = $this->employee->work_schedule_type($work_schedule_id, $this->company_id);

			$check_existing_timein = $this->employee->check_existing_timein_date($this->emp_id,$this->company_id,$employee_timein_date);
			$emp_no = $this->employee->check_emp_no($this->emp_id,$this->company_id);
			$split = $this->elm->new_get_splitinfo_fritz($emp_no, $this->company_id, $work_schedule_id,$this->emp_id,$employee_timein_date);
			$tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($this->company_id,$work_schedule_id);
			$get_schedule_settings = get_schedule_settings_by_workschedule_id($work_schedule_id,$this->company_id,date("l", strtotime($employee_timein_date)));
			
			if($tardiness_rule_migrated_v3) {
                $check_break_time = false;
                // || $get_schedule_settings->enable_additional_breaks == "yes"
                
                if($get_schedule_settings->enable_lunch_break == "yes" || $get_schedule_settings->enable_additional_breaks == "yes") {
                    if($get_schedule_settings->track_break_1 == "yes" || $get_schedule_settings->track_break_2 == "yes") {
                        
                        $break_in_min = $get_schedule_settings->break_in_min + $get_schedule_settings->break_1_in_min + $get_schedule_settings->break_2_in_min;
                        if($break_in_min > 0) {
                            
                            $check_break_time = true;
                        }
                    }
                }
            }

			$check_holiday = $this->employee->get_holiday_date(date('Y-m-d', strtotime($employee_timein_date)),$this->emp_id,$this->company_id);
			if(check_if_enable_breaks_on_holiday($this->company_id,$work_schedule_id)) {
			    $check_holiday = false;
			}

			$Lunch_break = false;
			$break_1 = false;
			$break_2 = false;
			$lunch_break_hours_started = "";
			$lunch_break_hours_ended = "";
			$break_1_start = "";
			$break_1_end = "";
			$break_2_start = "";
			$break_2_end = "";
			
			$existing_log = false;
			if($flag != '1') {
				if($check_existing_timein) {
					$existing_log = true;
				}
			}
			
			if(!$check_break_time || $existing_log || $flag_halfday == 1 || $check_holiday){
				// if there is no break ("0")
				$number_of_breaks_per_day = 0;
			} else {
				// if there is a break ("1")
				$number_of_breaks_per_day = 1;
			}

			$this->form_validation->set_rules("datetime", 'Employee Time in Date', 'trim|required|xss_clean');
			
			if ($this->form_validation->run() == true) {
				if($split['start_time'] == "") {
					if($number_of_breaks_per_day == 0){
						
						echo json_encode(array(
								"error" => false,
								"break_in_min" => false,
							    "lunch_break_hours_started" => ($lunch_break_hours_started != "") ? $lunch_break_hours_started : NULL,
							    "lunch_break_hours_ended" => ($lunch_break_hours_ended != "") ? $lunch_break_hours_ended : NULL,
							    "break_1" => $break_1,
							    "break_1_hours_started" => ($break_1_start != "") ? $break_1_start : NULL,
							    "break_1_hours_ended" => ($break_1_end != "") ? $break_1_end : NULL,
							    "break_2" => $break_2,
							    "break_2_hours_started" => ($break_2_start != "") ? $break_2_start : NULL,
							    "break_2_hours_ended" => ($break_2_end != "") ? $break_2_end : NULL,
								"existing_log" => $existing_log,
								"admin_lock" => false
						));
						return false;
					} else {
						$is_work = is_break_assumed($work_schedule_id);

						if($is_work) {
							if($check_break_time_for_assumed) {
								$input_timein = date('Y-m-d', strtotime($employee_timein_date1)).' '.$new_employee_timein_time;
								$input_timein = date('Y-m-d H:i:s', strtotime($input_timein));
								if($check_work_type == "Uniform Working Days"){
									$grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
									$h = $is_work->assumed_breaks * 60;
									$start_wo_thres = date('Y-m-d', strtotime($employee_timein_date)).' '.$check_break_time_for_assumed->work_start_time;
									$start_w_thres = date('Y-m-d H:i:s', strtotime($start_wo_thres.' +'.$grace.' minutes'));
										
									if(strtotime($start_w_thres) >= strtotime($input_timein) && strtotime($start_wo_thres) <= strtotime($input_timein)) {
										$add_date = $input_timein;
									} elseif(strtotime($start_wo_thres) >= strtotime($input_timein)) {
										$add_date = $start_wo_thres;
									} else {
										$add_date = $start_w_thres;
									}
										
									$lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
									$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_break_time_for_assumed->break_in_min} minutes"));
								}else if($check_work_type == "Flexible Hours"){
									//$grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
									if($check_break_time_for_assumed->latest_time_in_allowed == "" || $check_break_time_for_assumed->latest_time_in_allowed == null) {
										$add_date = $employee_timein_date.' '.$new_employee_timein_time;
									} else {
										$allowed_time_in = $employee_timein_date.' '.$check_break_time_for_assumed->latest_time_in_allowed;
										$latest_input = $employee_timein_date.' '.$new_employee_timein_time;
										if(strtotime($allowed_time_in) < strtotime($latest_input)) {
											$add_date = $allowed_time_in;
										} else {
											$add_date = $latest_input;
										}
									}
										
									$h = $is_work->assumed_breaks * 60;
									#$add_date = $employee_timein_date.' '.$new_employee_timein_time;
									$lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
									$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_break_time_for_assumed->duration_of_lunch_break_per_day} minutes"));
								}

								echo json_encode(array(
								    "error" => false,
								    "break_in_min" => true,
								    "lunch_break_hours_started" => ($lunch_break_hours_started != "") ? $lunch_break_hours_started : NULL,
								    "lunch_break_hours_ended" => ($lunch_break_hours_ended != "") ? $lunch_break_hours_ended : NULL,
								    "break_1" => $break_1,
								    "break_1_hours_started" => ($break_1_start != "") ? $break_1_start : NULL,
								    "break_1_hours_ended" => ($break_1_end != "") ? $break_1_end : NULL,
								    "break_2" => $break_2,
								    "break_2_hours_started" => ($break_2_start != "") ? $break_2_start : NULL,
								    "break_2_hours_ended" => ($break_2_end != "") ? $break_2_end : NULL,
								    "assumed" => true,
								    "lunch_out_date" => date('m/d/Y', strtotime($lunch_out)),
								    "lunch_out_time" => date('h:i A', strtotime($lunch_out)),
									"lunch_in_date" => date('m/d/Y', strtotime($lunch_in)),
									"lunch_in_time" => date('h:i A', strtotime($lunch_in)),
									"lunch_out_hr" => date('h', strtotime($lunch_in)),
									"lunch_out_min" => date('i', strtotime($lunch_in)),
									"lunch_out_ampm" => date('A', strtotime($lunch_in)),
									"lunch_in_hr" => date('h', strtotime($lunch_in)),
									"lunch_in_min" => date('i', strtotime($lunch_in)),
									"lunch_in_ampm" => date('A', strtotime($lunch_in)),
									"existing_log" => $existing_log,
									"admin_lock" => false					
								));
								return false;
							}
						} else {
							if($tardiness_rule_migrated_v3) {
								if($get_schedule_settings) {

									if($get_schedule_settings->enable_lunch_break == "yes") {
						                if($get_schedule_settings->track_break_1 == "yes") {
						                    if($get_schedule_settings->break_schedule_1 == "fixed") {
						                        $h = $get_schedule_settings->break_started_after * 60;
						                        $lunch_break_datehours_started = date('Y-m-d', strtotime($employee_timein_date)).' '.$get_schedule_settings->work_start_time;
						                        $lunch_break_hours_started = date("Y-m-d H:i:s", strtotime($lunch_break_datehours_started." +".$h." minutes"));
						                        $lunch_break_hours_ended = date("Y-m-d H:i:s", strtotime($lunch_break_hours_started." +".$get_schedule_settings->break_in_min." minutes"));
						                    }
						                    
						                    $Lunch_break = true;
						                }
						            }

						            if($get_schedule_settings->enable_additional_breaks == "yes") {
						                if($get_schedule_settings->track_break_2 == "yes") {
						                    if($get_schedule_settings->num_of_additional_breaks > 0) {
						                        if($get_schedule_settings->break_schedule_2 == "fixed") {
						                            if($get_schedule_settings->additional_break_started_after_1 != "") {
						                                $h = $get_schedule_settings->additional_break_started_after_1 * 60;
						                                $lunch_break_datehours_started = date('Y-m-d', strtotime($employee_timein_date)).' '.$get_schedule_settings->work_start_time;
						                                $break_1_start = date("Y-m-d H:i:s", strtotime($lunch_break_datehours_started." +".$h." minutes"));
						                                $break_1_end = date("Y-m-d H:i:s", strtotime($break_1_start." +".$get_schedule_settings->break_1_in_min." minutes"));
						                            }
						                            
						                            if($get_schedule_settings->additional_break_started_after_2 != "") {
						                                $h = $get_schedule_settings->additional_break_started_after_2 * 60;
						                                $lunch_break_datehours_started = date('Y-m-d', strtotime($employee_timein_date)).' '.$get_schedule_settings->work_start_time;
						                                $break_2_start = date("Y-m-d H:i:s", strtotime($lunch_break_datehours_started." +".$h." minutes"));
						                                $break_2_end = date("Y-m-d H:i:s", strtotime($break_2_start." +".$get_schedule_settings->break_2_in_min." minutes"));
						                            }
						                        }
						                        if($get_schedule_settings->num_of_additional_breaks == 2) {
						                            $break_1 = true;
						                            $break_2 = true;
						                        }
						                        
						                        if($get_schedule_settings->num_of_additional_breaks == 1) {
						                            $break_1 = true;
						                        }
					                            
						                        
						                    }
						                }
						            }

								} // if get_schedule_settings
								
								echo json_encode(array(
						            "error" => false,
						            "break_in_min" => $Lunch_break, // for lunch break 
						            "lunch_break_hours_started" => ($lunch_break_hours_started != "") ? $lunch_break_hours_started : NULL,
						            "lunch_break_hours_ended" => ($lunch_break_hours_ended != "") ? $lunch_break_hours_ended : NULL,
						            "break_1" => $break_1,
						            "break_1_hours_started" => ($break_1_start != "") ? $break_1_start : NULL,
						            "break_1_hours_ended" => ($break_1_end != "") ? $break_1_end : NULL,
						            "break_2" => $break_2,
						            "break_2_hours_started" => ($break_2_start != "") ? $break_2_start : NULL,
						            "break_2_hours_ended" => ($break_2_end != "") ? $break_2_end : NULL,
						            "existing_log" => $existing_log,
						            "admin_lock" => false
						        ));
						        return false;

							} // if $tardiness_rule_migrated_v3
							 else {
						        echo json_encode(array(
						            "error" => false,
						            "break_in_min" => true,
						            "lunch_break_hours_started" => ($lunch_break_hours_started != "") ? $lunch_break_hours_started : NULL,
						            "lunch_break_hours_ended" => ($lunch_break_hours_ended != "") ? $lunch_break_hours_ended : NULL,
						            "break_1" => $break_1,
						            "break_1_hours_started" => ($break_1_start != "") ? $break_1_start : NULL,
						            "break_1_hours_ended" => ($break_1_end != "") ? $break_1_end : NULL,
						            "break_2" => $break_2,
						            "break_2_hours_started" => ($break_2_start != "") ? $break_2_start : NULL,
						            "break_2_hours_ended" => ($break_2_end != "") ? $break_2_end : NULL,
						            "existing_log" => $existing_log,
						            "admin_lock" => false
						        ));  
						        return false;
						    }
						}
					}
				} else {
					return false;
				}
			} else {
				
			    echo json_encode(array(
			        "error" => true,
			        "break_in_min" => false,
			        "lunch_break_hours_started" => ($lunch_break_hours_started != "") ? $lunch_break_hours_started : NULL,
			        "lunch_break_hours_ended" => ($lunch_break_hours_ended != "") ? $lunch_break_hours_ended : NULL,
			        "break_1" => $break_1,
			        "break_1_hours_started" => ($break_1_start != "") ? $break_1_start : NULL,
			        "break_1_hours_ended" => ($break_1_end != "") ? $break_1_end : NULL,
			        "break_2" => $break_2,
			        "break_2_hours_started" => ($break_2_start != "") ? $break_2_start : NULL,
			        "break_2_hours_ended" => ($break_2_end != "") ? $break_2_end : NULL,
			        "existing_log" => $existing_log,
			        "admin_lock" => false
			    ));
			    return false;
			}
		}
	} // check_breaks

	public function check_split()
	{
		$employee_timein_date = $this->input->post('datetime');
		$employee_timein_date = ($employee_timein_date) ? date("Y-m-d", strtotime($employee_timein_date)) : '';
		$is_change_log = $this->input->post('is_change_log');

		// $employee_timein_date = date("2018-01-16");

		// $is_change_log = true;

		if ($this->input->post('check_split')) {
			
			// get the first and last blocks
			$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$employee_timein_date);
			$get_split_block_name = $this->employee->list_of_blocks($employee_timein_date,$this->emp_id,$work_schedule_id,$this->company_id);
			$approver_id = $this->employee->get_approver_name_timein($this->emp_id,$this->company_id)->add_logs_approval_grp;
			#$get_valid_split_logs = $this->employee->get_valid_split_logs($this->emp_id,$this->company_id,$id);
				
			$this->form_validation->set_rules("datetime", 'Time In Date', 'trim|required|xss_clean');
			
			if ($this->form_validation->run()==true){
				$html = "";
				$html1 = "";

				$sel_opts = array();
				
				$locked = "&nbsp;"; 
				$void = $this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($employee_timein_date)));
				$disabled_btn = false;
				$no_approver_msg_locked = "Payroll for the period affected is locked. No new requests, adjustments or changes can be accepted. Please reach out to your HR or payroll manager to discuss options for this request.";
				$no_approver_msg_closed = "Payroll for the period affected is closed. No new requests, adjustments or changes can be accepted. Please reach out to your HR or payroll manager to discuss options for this request.";
				
				if($approver_id) {
				    if(is_workflow_enabled($this->company_id)) {
				        if($void == "Waiting for approval"){
				            $locked = "Warning : Timesheets locked for payroll processing.";
				        } elseif ($void == "Closed") {
				            $locked = "Warning : The timesheet you are submitting is part of a closed payroll. Your request will be routed to the appropriate approvers for consideration and approval.";
				        }
				    } else {
				        if($void == "Waiting for approval"){
				            $locked = $no_approver_msg_locked;
				            $disabled_btn = true;
				        } elseif ($void == "Closed") {
				            $locked = $no_approver_msg_closed;
				            $disabled_btn = true;
				        }
				    }
				} else {
				    if($void == "Waiting for approval"){
				        $locked = $no_approver_msg_locked;
				        $disabled_btn = true;
				    } elseif ($void == "Closed") {
				        $locked = $no_approver_msg_closed;
				        $disabled_btn = true;
				    }
				}

				if($get_split_block_name) {
					foreach ($get_split_block_name as $sb) {
					    
						if($sb != FALSE){
						    $get_split_logs_already_exist = ($is_change_log) ? '' : $this->employee->get_split_logs_already_exist($this->emp_id,$this->company_id,$sb->schedule_blocks_id,$employee_timein_date);
						    $disabled = "";
						    if($get_split_logs_already_exist) {
						        $disabled = "ihide";
						    } else {
						    	$t = array(
						    		'id' => $sb->schedule_blocks_id,
						    		'name' => $sb->block_name
					    		);
						    	array_push($sel_opts, $t);
						    }
						    
							// $html1 .= "
							// 	<option value='".$sb->schedule_blocks_id."' name='schedule_blocks_id' class='".$disabled."'>".$sb->block_name."</option>
							// ";
						}
					}
					
					// $html .= "
					// <tr class='sched_blocks_appended'>
					// 	<td>
					// 		<label class='margin-top-9'>Schedule Block</label>
					// 	</td>
					// 	<td colspan='2'>
					// 		<div class='select-bungot' style='margin-bottom: 15px;'>
					// 			<select name='schedule_blocks_id' class='select-custom schedule_blocks_id'>
					// 				<option value='' name='schedule_blocks_id'></option>
					// 				{$html1}
					// 			</select>
					// 		</div>
					// 	</td>
					// </tr>
						
					// <tr class='sched_blocks_appended'>
					// 	<td colspan='3'><span class='form-error' id='esched_blocks_id_err'></span></td>
					// </tr>
					// ";
						
					echo json_encode(array(
						"error" => false,
						"sched_blocks" => $sel_opts,
					    "etime_out_date" => $locked,
					    "submit_btn" => $disabled_btn,
					    "is_split" => true
					));
					
					return false;
				} else {
					echo json_encode(array(
						"error" => true,
						"e_msg" => 'no sched block name',
						"is_split" => false
					));
					
					return false;
				}

			}
		}
				
		
	}

	public function save_to_timesheet_close_payroll($emp_time_id,$timein_info, $type="change log") {
        if($type == "add log") {
            $undertime_min  = $timein_info->undertime_min;
            $tardiness_min  = $timein_info->tardiness_min;
            $workday_hr     = $timein_info->total_hours_required;
            
            if($workday_hr > $timein_info->total_hours) {
                $workday_min    = $timein_info->total_hours * 60;
            } else {
                $workday_min    = $workday_hr * 60;
            }
            
            $source = "EP";
        } else {
            $source = "Adjusted";
            
            $undertime_min  = $timein_info->undertime_min - $timein_info->change_log_undertime_min;
            $tardiness_min  = $timein_info->tardiness_min - $timein_info->change_log_tardiness_min;
            #$workday_hr     = $timein_info->change_log_total_hours_required - $timein_info->total_hours_required;
            #$workday_min    = $workday_hr * 60;
            $workday_min    = 0;
        }
        
        $auto_date = ($timein_info->change_log_date!=NULL) ? date("Y-m-d",strtotime($timein_info->change_log_date)) : date("Y-m-d",strtotime($timein_info->date));
        $auto_ws_id = ($timein_info->change_log_work_schedule_id != NULL) ? $timein_info->change_log_work_schedule_id : $timein_info->work_schedule_id;
        
        
        $date_insert = array(
            "employee_time_in_id" => $emp_time_id,
            "work_schedule_id" => $auto_ws_id,
            "emp_id" => $timein_info->emp_id,
            "comp_id" => $timein_info->company_id,
            "date_filed" => $timein_info->change_log_date_filed,
            "date" => $auto_date,
            "time_in" => $timein_info->change_log_time_in,
            "lunch_out" => $timein_info->change_log_lunch_out,
            "lunch_in" => $timein_info->change_log_lunch_in,
            "break1_out" => $timein_info->change_log_break1_out,
            "break1_in" => $timein_info->change_log_break1_in,
            "break2_out" => $timein_info->change_log_break2_out,
            "break2_in" => $timein_info->change_log_break2_in,
            "time_out" => $timein_info->change_log_time_out,
            "total_hours" => $timein_info->change_log_total_hours,
            "total_hours_required" => $timein_info->change_log_total_hours_required,
            "reason" => $timein_info->reason,
            "time_in_status" => "pending",
            "overbreak_min" => $timein_info->change_log_overbreak_min,
            "late_min" => $timein_info->change_log_late_min,
            "tardiness_min" => $timein_info->change_log_tardiness_min,
            "undertime_min" => $timein_info->change_log_undertime_min,
            "absent_min" => $timein_info->change_log_absent_min,
            #"notes" => $auto_remarks,
            "source" => $source,
            "status" => "Active",
            "approval_time_in_id" => $timein_info->approval_time_in_id,
            "flag_regular_or_excess" => $timein_info->flag_regular_or_excess,
            "rest_day_r_a" => $timein_info->rest_day_r_a,
            "flag_rd_include" => $timein_info->flag_rd_include,
            "flag_holiday_include" => $timein_info->flag_holiday_include,
            "timesheet_not_req_flag" => $timein_info->timesheet_not_req_flag,
            "partial_log_ded_break" => $timein_info->partial_log_ded_break,
            "flag_open_shift" => $timein_info->flag_open_shift,
            "os_approval_time_in_id" => $timein_info->os_approval_time_in_id,
            "time_in_status" => "pending",
            "auto_takeup_tardi_min" => $tardiness_min,
            "auto_takeup_undertime_min" => $undertime_min,
            "auto_takeup_workday_min" => $workday_min
        );
        
        $this->db->insert('timesheet_close_payroll', $date_insert);
        $timesheet_close_payroll_id = $this->db->insert_id();
        
        $field_atp = array(
            "for_resend_auto_rejected_id" => $timesheet_close_payroll_id,
        );
        
        $where_atp = array(
            "employee_time_in_id"=>$emp_time_id,
            "comp_id"=>$this->company_id
        );
        
        $this->timeins->update_field("employee_time_in",$field_atp,$where_atp);
    }
}