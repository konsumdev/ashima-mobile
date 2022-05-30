<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Employee Work Schedule Model
 *
 * @category Model
 * @version 1.0
 * @author Jonathan Bangga <jonathanbangga@gmail.com>
 */
	class Employee_work_schedule_model extends CI_Model {
		
		/**
		 * Check Employee Work Schedule
		 * @param unknown_type $flag_date
		 * @param unknown_type $emp_id
		 * @param unknown_type $company_id
		 */
		public function check_employee_work_schedule($flag_date,$emp_id,$company_id){

			$w = array(
					"emp_id"=>$emp_id,
					"company_id"=>$company_id,
					"valid_from"=>$flag_date,
					"until"=>$flag_date,
					"status"=>"Active",
					"payroll_group_id" => 0
			);
			$this->db->where($w);
			$q = $this->db->get("employee_shifts_schedule");
			$r = $q->row();
			if($r){
				return $r;
			}else{
				$w = array(
						'epi.emp_id'=> $emp_id
				);
				$this->db->where($w);
				$this->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','LEFT');
				$q_pg = $this->edb->get('employee_payroll_information AS epi');
				$r_pg = $q_pg->row();
				
				return ($r_pg) ? $r_pg: FALSE;
			}
		}
		
		/**
		 * Get rest day
		 * @param int $company_id
		 * @param int $work_schedule_id
		 * @param string $weekday
		 */
		public function get_rest_day($company_id,$work_schedule_id,$weekday)
		{
			$where = array(
					'company_id' 	   => $company_id,
					"work_schedule_id"=>$work_schedule_id,
					'rest_day' 		   => $weekday,
					'status'		   => 'Active'
			);
			$this->db->where($where);
			$q = $this->db->get('rest_day');
			$result = $q->row();
				
			return ($result) ? $result : false;
		}
		
		/**
		 * Work Schedule Information
		 * @param unknown_type $company_id
		 * @param unknown_type $work_schedule_id
		 * @param unknown_type $weekday
		 */
		public function work_schedule_info($company_id,$work_schedule_id,$weekday,$date="",$emp_id=""){ 
		    // g add nako ang param na $date para sa split, basin mahibung sa ra $weekday pro dli ky nka format na ang pgpaasa sa weekday
		    // and existing nsd ni na model mao ngadd nlng ko ug param, para safe.. (fritz)
			$wd = date("l",strtotime($weekday));
			$break_time = 0;
				
			$uww = array(
					"uw.days_of_work"=>$wd,
					"uw.company_id"=>$company_id,
					"uw.work_schedule_id"=>$work_schedule_id,
					"uw.status"=>"Active"
			);
			$s = array("*");
			$this->db->where($uww);
			$this->db->join("work_schedule AS ws","ws.work_schedule_id = uw.work_schedule_id","LEFT");
			$uwq = $this->db->get("regular_schedule AS uw");
			$uwr = $uwq->row();
				
			if($uwr){
				
				$start_time = ($uwr->latest_time_in_allowed != NULL || $uwr->latest_time_in_allowed != "") ? $uwr->work_start_time : $uwr->work_start_time;
				$end_time = $uwr->work_end_time;
				$shift_name = $uwr->name;
				$total_hours = $uwr->total_work_hours;
				$total_days_per_year = '312';
				$required = 1;
				$flexible = FALSE;
				$split = FALSE;
				$sched_blocks = FALSE;
				//echo $start_time;
				if(($uwr->break_in_min != NULL) || ($uwr->break_in_min != "" )){
					
					$break_time = $uwr->break_in_min;
				}
		
			}else{
				$fw = array(
						"f.company_id"=>$company_id,
						"f.work_schedule_id"=>$work_schedule_id
				);
				$this->db->where($fw);
				$this->db->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
				$fq = $this->db->get("flexible_hours AS f");
				$fr = $fq->row();
				
				if($fr){
					$break_time = $fr->duration_of_lunch_break_per_day;
					if($fr->latest_time_in_allowed != NULL || $fr->latest_time_in_allowed != ""){
						$start_time = $fr->latest_time_in_allowed;
						$end_time = "";
						$shift_name = $fr->name;
						$total_hours = $fr->total_hours_for_the_day;
						$total_days_per_year = '312';
						$required = $fr->not_required_login;
						$flexible = TRUE;
						$split = FALSE;
						$sched_blocks = FALSE;
					}else{
						$start_time = "";
						$end_time = "";
						$shift_name = $fr->name;
						$total_hours = $fr->total_hours_for_the_day;
						$total_days_per_year = '312';
						$required = $fr->not_required_login;
						$flexible = TRUE;
						$split = FALSE;
						$sched_blocks = FALSE;
					}
				}else{
				    // added by fritz
				    $sw = array(
				        "esb.company_id" => $company_id,
				        "esb.work_schedule_id" => $work_schedule_id,
				        "esb.valid_from" => $date,
				        "esb.until" => $date,
				        "esb.emp_id" => $emp_id
				    );
				    
				    $s = array(
				        "esb.valid_from",
				        "esb.until",
				        "ws.name",
				        "schedule_blocks_id"
				    );
				    
				    $this->db->select($s);
				    $this->db->where($sw);
				    $this->db->join("work_schedule AS ws","ws.work_schedule_id = esb.work_schedule_id","LEFT");
				    $sq = $this->db->get("employee_sched_block AS esb");
				    $sr = $sq->result();
				    
				    if($sr) {
				        // shift name rjud tuyo nako ani pra sa employee portal na missing logs.. (fritz)
				        $sched_blocks_arr = array();
				        foreach ($sr AS $r) {
				            array_push($sched_blocks_arr, $r->schedule_blocks_id);
				        }
				        
				        $start_time = "";
				        $end_time = "";
				        $shift_name = "Split Shift";
				        $total_hours = "";
				        $total_days_per_year = "";
				        $required = 0;
				        $flexible = FALSE;
				        $break_time = 0;
				        $split = TRUE;
				        $sched_blocks = $sched_blocks_arr;
				    } else {
				        $start_time = "";
				        $end_time = "";
				        $shift_name = "";
				        $total_hours = "";
				        $total_days_per_year = "";
				        $required = 0;
				        $flexible = FALSE;
				        $split = FALSE;
				        $sched_blocks = FALSE;
				    }
				}
				
			}
				
			$data["work_schedule"] = array(
				"start_time"=>$start_time,
				"end_time"=>$end_time,
				"shift_name"=>$shift_name,
				"total_hours"=>$total_hours,
				"total_days_per_year"=>$total_days_per_year,
				"break_time"=>$break_time,
				"login" => $required,
				"flexible" => $flexible,
				"required" => $required,
			    "split" => $split,
			    "sched_blocks" => $sched_blocks
			);
			return $data;
				
		}
		
		/**
		 * Check Employee Leave Application
		 * @param unknown_type $flag_date
		 * @param unknown_type $emp_id
		 */
		public function check_employee_leave_application($flag_date,$emp_id){
			$w = array(
				"DATE(ela.date_start) <= "=>$flag_date,
				"DATE(ela.date_end) >= "=>$flag_date,
				"ela.leave_application_status"=>"approve",
				"ela.status"=>"Active",
				"ela.emp_id"=>$emp_id
			);
			$this->db->where($w);
			$this->db->join("leave_type AS lt","lt.leave_type_id = ela.leave_type_id","LEFT");
			$q = $this->db->get("employee_leaves_application AS ela");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
		/**
		 * Check Employee Schedule Request
		 * @param unknown_type $flag_date
		 * @param unknown_type $emp_id
		 * @param unknown_type $company_id
		 */
		public function check_employee_schedule_request($flag_date,$emp_id,$company_id){
			$w = array(
					"emp_id"=>$emp_id,
					"company_id"=>$company_id,
					"date_from <= "=>$flag_date,
					"date_to >= "=>$flag_date,
					"status"=>"Active"
			);
			$this->db->where($w);
			$this->db->order_by("employee_work_schedule_application_id","DESC");
			$q = $this->db->get("employee_work_schedule_application");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
		/**
		 * Company Image for Approval Page
		 * @param unknown_type $comp_id
		 */
		public function company_image($comp_id){
			$w = array(
					"company_id" => $comp_id
			);
			$this->edb->where($w);
			$q = $this->edb->get("company");
			$row = $q->row();
				
			$no_image = "/assets/theme_2013/images/photo_not_available.png";
				
			if($q->num_rows() > 0){
				$image = $row->company_logo;
				$image_val = "./uploads/companies/";
				if($image != ""){
					return (file_exists($image_val.$comp_id."/".$image)) ? $image_val.$comp_id."/".$image : $no_image;
				}else{
					return $no_image;
				}
			}else{
				return $no_image;
			}
		}
		
		/**
		 * Check Work Schedule Token
		 * @param unknown_type $token
		 */
		public function work_schedule_token_info($token){
			$w = array(
					"aws.token"=>$token,
					"aws.status"=>"Active",
					"ewsa.status"=>"Active"
			);
			$this->db->where($w);
			$this->edb->join("approval_work_schedule AS aws","aws.employee_work_schedule_application_id = ewsa.employee_work_schedule_application_id","LEFT");
			$this->edb->join("employee AS e","e.emp_id = aws.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$q = $this->edb->get("employee_work_schedule_application AS ewsa");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
		/**
		 * Check Total Level for Work Schedule
		 * @param unknown_type $token_level
		 * @param unknown_type $employee_work_schedule_application_id
		 */
		public function check_token_work_schedule($token_level,$employee_work_schedule_application_id){
			$w = array(
					"token_level"=>$token_level,
					"employee_work_schedule_application_id"=>$employee_work_schedule_application_id,
					"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("approval_work_schedule");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
		/**
		 * Check Approval Groups
		 * @param unknown_type $employee_work_schedule_application_id
		 */
		public function check_approver_group($employee_work_schedule_application_id){
			$w = array(
					"ewsa.employee_work_schedule_application_id"=>$employee_work_schedule_application_id
			);
			$this->db->where($w);
			#$this->db->join("approval_process AS ap","ep.eBundy_approval_grp = ap.approval_process_id","LEFT");
			$this->db->join("approval_groups_via_groups AS ap","ep.eBundy_approval_grp = ap.approval_groups_via_groups_id","LEFT");
			$this->db->join("approval_groups AS ag","ap.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
			$this->db->join("employee_work_schedule_application AS ewsa","ewsa.emp_id = ep.emp_id","LEFT");
			$this->db->order_by("ag.level","DESC");
			$q = $this->db->get("employee_payroll_information AS ep","1");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
		/**
		 * Check HR Approver
		 * @param unknown_type $employee_work_schedule_application_id
		 */
		public function check_hr_approver($employee_work_schedule_application_id){
			$w = array(
					"ewsa.employee_work_schedule_application_id"=>$employee_work_schedule_application_id,
					"ag.include_hr_confirmation"=>1
			);
			$this->db->where($w);
			#$this->db->join("approval_process AS ap","ep.eBundy_approval_grp = ap.approval_process_id","LEFT");
			$this->db->join("approval_groups_via_groups AS ap","ep.eBundy_approval_grp = ap.approval_groups_via_groups_id","LEFT");
			$this->db->join("approval_groups AS ag","ap.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
			$this->db->join("employee_work_schedule_application AS ewsa","ewsa.emp_id = ep.emp_id","LEFT");
			$q = $this->db->get("employee_payroll_information AS ep");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
		/**
		 * Get Employee Payroll Group ID
		 * @param unknown_type $emp_id
		 * @param unknown_type $company_id
		 */
		public function employee_payroll_group_id($emp_id,$company_id){
			$w = array(
					"emp_id"=>$emp_id,
					"company_id"=>$company_id,
					"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("employee_payroll_information");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
		/**
		 * Get rest day by Payroll Group
		 * @param int $company_id
		 * @param int $payroll_group_id
		 * @param string $weekday
		 */
		public function get_rest_day_by_payroll_group($company_id,$payroll_group_id,$weekday)
		{
			$where = array(
					'company_id' 	   => $company_id,
					"payroll_group_id" =>$payroll_group_id,
					'rest_day' 		   => $weekday,
					'status'		   => 'Active'
			);
			$this->db->where($where);
			$q = $this->db->get('rest_day');
			$result = $q->row();
			
			return ($result) ? $result : false;
		}
		
		/**
		 * Work Schedule Information by Payroll Group
		 * @param unknown_type $company_id
		 * @param unknown_type $work_schedule_id
		 * @param unknown_type $weekday
		 */
		public function work_schedule_info_by_payroll_group($company_id,$payroll_group_id,$weekday){
			$wd = date("l",strtotime($weekday));
			$break_time = 0;
				
			$uww = array(
					"uw.days_of_work"=>$wd,
					"uw.company_id"=>$company_id,
					"uw.payroll_group_id"=>$payroll_group_id,
					"uw.status"=>"Active"
			);
			$s = array("*");
			$this->db->select($s);
			$this->db->where($uww);
			//$this->db->join("uniform_working_day_settings AS uwds","uwds.payroll_group_id = uw.payroll_group_id","LEFT");
			//$this->db->join("break_time AS bt","bt.payroll_group_id = uw.payroll_group_id","LEFT");
			$this->db->join("flexible_hours AS fh","fh.work_schedule_id = uw.work_schedule_id","LEFT");
			$uwq = $this->db->get("regular_schedule AS uw");
			$uwr = $uwq->row();
				
			if($uwr){
				$start_time = ($uwr->latest_time_in_allowed != NULL || $uwr->latest_time_in_allowed != "") ? $uwr->latest_time_in_allowed : $uwr->work_start_time;
				$end_time = $uwr->work_end_time;
				$shift_name = ""; //$uwr->name;
				$total_hours = $uwr->total_work_hours;
				//STATIC FOR DAYS FOR YEAR
				$total_days_per_year = '312';
		
				if(($uwr->number_of_breaks_per_day != NULL)  || ($uwr->number_of_breaks_per_day != "" )){
					$break_time = $uwr->number_of_breaks_per_day;
				}
		
			}else{
				$wsw = array(
						"w.company_id"=>$company_id,
						"w.payroll_group_id"=>$payroll_group_id
				);
				$s = array("*", "w.start_time AS work_start_time","w.end_time AS work_end_time");
				$this->db->select($s);
				$this->db->where($wsw);
				//$this->db->join("workshift_settings AS wss","wss.payroll_group_id = w.payroll_group_id","LEFT");
				//$this->db->join("break_time AS bt","bt.payroll_group_id = w.payroll_group_id","LEFT");
				$this->db->join("flexible_hours AS fh","fh.work_schedule_id = w.work_schedule_id","LEFT");
				$wsq = $this->db->get("workshift AS w");
				$wsr = $wsq->row();
		
				if($wsr){
					$start_time = $wsr->work_start_time;
					$end_time = $wsr->work_end_time;
					$shift_name = ""; // $wsr->name;
					$total_hours = $wsr->total_work_hours;
					$total_days_per_year = '312';
						
					if(($wsr->number_of_breaks_per_day != NULL) || ($wsr->number_of_breaks_per_day != "" )){
						
						$break_time = $wsr->number_of_breaks_per_day;
					}
						
				}else{
					$fw = array(
							"f.company_id"=>$company_id,
							"f.payroll_group_id"=>$payroll_group_id
					);
					$this->db->where($fw);
					$fq = $this->db->get("flexible_hours AS f");
					$fr = $fq->row();
						
					if($fr){
						if($fr->latest_time_in_allowed != NULL || $fr->latest_time_in_allowed != ""){
							$start_time = $fr->latest_time_in_allowed;
							$end_time = "";
							$shift_name = ""; // $fr->name;
							$total_hours = $fr->total_hours_for_the_day;
							$total_days_per_year = '312';
						}else{
							$start_time = "";
							$end_time = "";
							$shift_name = ""; // $fr->name;
							$total_hours = $fr->total_hours_for_the_day;
							$total_days_per_year = '312';
						}
					}else{
						$start_time = "";
						$end_time = "";
						$shift_name = "";
						$total_hours = "";
						$total_days_per_year = "";
					}
				}
			}
				
			$data["work_schedule"] = array(
					"start_time"=>$start_time,
					"end_time"=>$end_time,
					"shift_name"=>$shift_name,
					"total_hours"=>$total_hours,
					"total_days_per_year"=>$total_days_per_year,
					"break_time"=>$break_time
			);
			return $data;
				
		}
		
		/**
		 * Check Work Schedule Token
		 * @param unknown_type $token
		 */
		public function work_schedule_id_info($id){
			$w = array(
					"ewsa.employee_work_schedule_application_id"=>$id,
					"aws.status"=>"Active",
					"ewsa.status"=>"Active"
			);
			$this->db->where($w);
			$this->edb->join("approval_work_schedule AS aws","aws.employee_work_schedule_application_id = ewsa.employee_work_schedule_application_id","LEFT");
			$this->edb->join("employee AS e","e.emp_id = aws.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$q = $this->edb->get("employee_work_schedule_application AS ewsa");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
		public function get_work_schedule_cat($emp_id, $comp_id, $date){
			$sel = array(
					'ws.work_schedule_id',
					'ws.category_id'
			);
			
			$where = array(
					'ess.emp_id' => $emp_id,
					'ess.valid_from' => $date,
					'ess.until' => $date,
					'ws.comp_id' => $comp_id
			);
			
			$this->db->select($sel);
			$this->db->where($where);
			$this->db->join("employee_shifts_schedule AS ess", "ess.work_schedule_id = ws.work_schedule_id","LEFT");
			$this->db->group_by('ws.work_schedule_id');
			$query = $this->db->get("work_schedule AS ws");
			$res = $query->row();
			//last_query();
			return ($res) ? $res : FALSE;
		}
		
		public function get_work_schedule_all_cat($comp_id, $cat_id){		
			$sel = array(
					'ws.work_schedule_id',
					'ws.name'
			);
			$where = array(
					'ws.category_id' => $cat_id,
			    'ws.comp_id' => $comp_id,
			    'ws.status' => 'Active'
			);
			$this->db->select($sel);
			$this->db->where($where);
			$this->db->join("employee_shifts_schedule AS ess", "ess.work_schedule_id = ws.work_schedule_id","LEFT");
			$this->db->group_by('ws.work_schedule_id');
			$query = $this->db->get("work_schedule AS ws");
			$res = $query->result();
			//last_query();
			return ($res) ? $res : FALSE;
		}
		
		public function get_work_schedule_time($work_sched_id,$comp_id,$day){
			$sel = array(
					'rs.work_start_time',
					'rs.work_end_time'
			);
			$where = array(
					'rs.work_schedule_id' => $work_sched_id,
					'rs.company_id' => $comp_id,
					'rs.days_of_work' => $day
			);
			$this->db->select($sel);
			$this->db->where($where);
			$this->db->join("work_schedule AS ws", "ws.work_schedule_id = rs.work_schedule_id","LEFT");
			$this->db->group_by('ws.work_schedule_id');
			$query = $this->db->get("regular_schedule AS rs");
			$res = $query->row();
			//last_query();
			return ($res) ? $res : FALSE;
		}
		
		public function get_work_schedule_by_id($comp_id, $cat_id){		
			$sel = array(
					'ws.work_schedule_id',
					'ws.category_id'
			);
			$where = array(
					'ws.work_schedule_id' => $cat_id,
					'ws.comp_id' => $comp_id
			);
			$this->db->select($sel);
			$this->db->where($where);
			$this->db->join("employee_shifts_schedule AS ess", "ess.work_schedule_id = ws.work_schedule_id","LEFT");
			$this->db->group_by('ws.work_schedule_id');
			$query = $this->db->get("work_schedule AS ws");
			$res = $query->row();
			//last_query();
			return ($res) ? $res : FALSE;
		}
		
		/** added : fritz **/
		
		public function check_breaktime_split($company_id,$emp_id,$date,$work_schedule_id){
		    $s = array(
		          'sb.break_in_min'
		    );
		    
			$sbw = array(
					"esb.company_id"=>$company_id,
					"ess.valid_from" => $date,
					"ess.until" => $date,
					"esb.work_schedule_id"=>$work_schedule_id,
					"esb.emp_id" => $emp_id
			);
			$this->db->select($s);
			$this->db->where($sbw);
			$this->db->join("work_schedule AS ws","ws.work_schedule_id = ess.work_schedule_id","LEFT");
			$this->db->join("schedule_blocks AS sb","sb.work_schedule_id = ess.work_schedule_id","LEFT");
			$this->db->join("employee_sched_block AS esb","sb.schedule_blocks_id = esb.schedule_blocks_id","LEFT");
			$this->db->group_by('ess.emp_id');
			$sbq = $this->db->get("employee_shifts_schedule AS ess");
			$sbr = $sbq->row();
			//last_query();
			return ($sbr) ? $sbr : FALSE;
		}
		
		public function check_breaktime_split1($company_id,$emp_id,$date,$work_schedule_id,$schedule_blocks_id=""){
			
			if($schedule_blocks_id != "") {
				$sbw = array(
						"ess.company_id"=>$company_id,
						"ess.valid_from" => $date,
						"ess.until" => $date,
						"ess.work_schedule_id"=>$work_schedule_id,
						"ess.emp_id" => $emp_id,
						"sb.schedule_blocks_id" => $schedule_blocks_id
				);
			} else {
				$sbw = array(
						"ess.company_id"=>$company_id,
						"ess.valid_from" => $date,
						"ess.until" => $date,
						"ess.work_schedule_id"=>$work_schedule_id,
						"ess.emp_id" => $emp_id
				);
			}
			
			$this->db->where($sbw);
			$this->db->join("work_schedule AS ws","ws.work_schedule_id = ess.work_schedule_id","LEFT");
			$this->db->join("schedule_blocks AS sb","sb.work_schedule_id = ess.work_schedule_id","LEFT");
			$this->db->join("employee_sched_block AS esb","sb.schedule_blocks_id = esb.schedule_blocks_id","LEFT");
			$this->db->group_by('ess.emp_id');
			$sbq = $this->db->get("employee_shifts_schedule AS ess");
			$sbr = $sbq->row();
			//last_query();
			return ($sbr) ? $sbr : FALSE;
		}
		
		public function get_split_sched_timein_info($sched_blocks_id,$work_sched_id,$company_id) {
			$where = array(
					'schedule_blocks_id' => $sched_blocks_id,
					'work_schedule_id' => $work_sched_id,
					'company_id' => $company_id,
			);
			
			$this->db->where($where);
			$q = $this->db->get("schedule_blocks");
			$r = $q->row();
			
			return ($r) ? $r : FALSE;
		}
		
		public function work_schedule_info_split($company_id,$work_schedule_id,$weekday){
				
			$w = array(
				"esb.work_schedule_id" 	=> $work_schedule_id,
				"esb.company_id"		=> $company_id,
				"esb.valid_from" 		=> $weekday
			);
			
			$this->db->where($w);
			$this->db->join("employee_sched_block AS esb","esb.schedule_blocks_id = sb.schedule_blocks_id","LEFT");
			$q = $this->db->get("schedule_blocks AS sb");
			$res = $q->result();
			
			return ($res) ? $res : FALSE;
		
		}
		
		public function check_employee_leave_application_v2($flag_date,$emp_id){
		    $s = array(
		        "ela.date_start",
		        "ela.date_end",
		        "lt.leave_type",
		        "ela.shift_date",
		        "ela.flag_parent"
		    );
		    
			$w = array(
				"DATE(ela.date_start) <= "=> $flag_date,
				"DATE(ela.date_end) >= "=> $flag_date,
				"ela.leave_application_status" => "approve",
				"ela.status" => "Active",
				"ela.emp_id" => $emp_id
			);
			$this->db->select($s);
			$this->db->where($w);
			$this->db->join("leave_type AS lt","lt.leave_type_id = ela.leave_type_id","LEFT");
			$this->db->order_by("ela.date_start","asc");
			$q = $this->db->get("employee_leaves_application AS ela");
			$r = $q->result();
			
			$res_arr = array();
			if ($r) {
			    foreach ($r as $row) {
			        if ($row->flag_parent != "yes") {
			            $temp_arr = array(
			                "date_start" => $row->date_start,
			                "date_end" => $row->date_end,
			                "leave_type" => $row->leave_type,
			                "shift_date" => $row->shift_date
			            );
			            
			            array_push($res_arr,(object) $temp_arr);
			        }
			        
			    }
			}
			
			return $res_arr;
			#return ($r) ? $r : FALSE ;
		}
		
		public function default_shifts_except_split($comp_id, $name){
		    $sel = array(
		        'work_schedule_id',
		        'name',
		    );
		    $where = array(
		        'comp_id' => $comp_id,
		        'status' => 'Active',
		        'work_type_name !=' => 'Workshift' ,
		        'default' => '1',
		        'name' => $name
		        
		    );
		    $this->db->select($sel);
		    $this->db->where($where);
		    $this->db->group_by('work_schedule_id');
		    $query = $this->db->get("work_schedule");
		    $res = $query->row();
		    
		    $res_arr = array();
		    if ($res) {
		        $res_arr = array(
		            'work_schedule_id' => $res->work_schedule_id,
		            'name' => $res->name,
		        );
		    }
		    
		    return $res_arr;
        }
        
        public function all_shifts_except_split2($comp_id){
		    $sel = array(
		        'work_schedule_id',
		        'name',
		        'work_type_name',
		        'category_id',
		        'default'
		    );
		    $where = array(
		        'comp_id' => $comp_id,
		        'status' => 'Active',
		        'work_type_name !=' => 'Workshift' ,
		        'name !=' => 'Rest Day'
		    );
		    $this->db->select($sel);
		    $this->db->where($where);
		    $this->db->group_by('work_schedule_id');
		    $query = $this->db->get("work_schedule");
		    $res = $query->result();
		    
		    $res_arr = array();
		    if ($res) { 
		        $uws_work_schedule_id = $this->default_shifts_except_split($comp_id, "Regular Work Schedule")['work_schedule_id'];
		        $cws_work_schedule_id = $this->default_shifts_except_split($comp_id, "Compressed Work Schedule")['work_schedule_id'];
		        $nss_work_schedule_id = $this->default_shifts_except_split($comp_id, "Night Shift Schedule")['work_schedule_id'];
		        $fts_work_schedule_id = $this->default_shifts_except_split($comp_id, "Flexi Time Schedule")['work_schedule_id'];
	
		        $res_arr1 = array();
		        $res_arr2 = array();
		        $res_arr3 = array();
		        $res_arr4 = array();
		        foreach ($res as $r) {
		            #p($uws_work_schedule_id.' '.$r->category_id);
		            
		            if ($uws_work_schedule_id != "" || $uws_work_schedule_id != null) {
		                if ($uws_work_schedule_id == $r->work_schedule_id) {
		                    $uws_def = array(
		                        'work_schedule_id' => $uws_work_schedule_id,
		                        'name' => 'Regular Work Schedule (Default)',
		                        'work_type_name' => 'Uniform Working Days',
		                        'default' => $r->default
		                    );
		                    
		                    array_push($res_arr1,$uws_def);
		                }
		                
		                if ($uws_work_schedule_id == $r->category_id) {
		                    $uws = array(
		                        'work_schedule_id' => $r->work_schedule_id,
		                        'name' => ' - '.$r->name,
		                        'work_type_name' => $r->work_type_name,
		                        'default' => $r->default
		                    );
		                    array_push($res_arr1,$uws);
		                }
		            }
		            
		            if ($cws_work_schedule_id != "" || $cws_work_schedule_id != null) {
		                if ($cws_work_schedule_id == $r->work_schedule_id) {
		                    $cws_def = array(
		                        'work_schedule_id' => $cws_work_schedule_id,
		                        'name' => 'Compressed Work Schedule (Default)',
		                        'work_type_name' => 'Uniform Working Days',
		                        'default' => $r->default
		                    );
		                    
		                    array_push($res_arr2,$cws_def);
		                }
		                
		                if ($cws_work_schedule_id == $r->category_id) {
		                    $cws = array(
		                        'work_schedule_id' => $r->work_schedule_id,
		                        'name' => ' - '.$r->name,
		                        'work_type_name' => $r->work_type_name,
		                        'default' => $r->default
		                    );
		                    array_push($res_arr2,$cws);
		                }
		            }
		            
		            if ($nss_work_schedule_id != "" || $nss_work_schedule_id != null) {
		                if ($nss_work_schedule_id == $r->work_schedule_id) {
		                    $nss_def = array(
		                        'work_schedule_id' => $nss_work_schedule_id,
		                        'name' => 'Night Shift Schedule (Default)',
		                        'work_type_name' => 'Uniform Working Days',
		                        'default' => $r->default
		                    );
		                    
		                    array_push($res_arr3,$nss_def);
		                }
		                
		                if ($nss_work_schedule_id == $r->category_id) {
		                    $nss = array(
		                        'work_schedule_id' => $r->work_schedule_id,
		                        'name' => ' - '.$r->name,
		                        'work_type_name' => $r->work_type_name,
		                        'default' => $r->default
		                    );
		                    array_push($res_arr3,$nss);
		                }
		            }
		            
		            if ($fts_work_schedule_id != "" || $fts_work_schedule_id != null) {
		                if ($fts_work_schedule_id == $r->work_schedule_id) {
		                    $fts_def = array(
		                        'work_schedule_id' => $fts_work_schedule_id,
		                        'name' => 'Flexi Time Schedule (Default)',
		                        'work_type_name' => 'Flexible Hours',
		                        'default' => $r->default
		                    );
		                    
		                    array_push($res_arr4,$fts_def);
		                }
		                
		                if ($fts_work_schedule_id == $r->category_id) {
		                    $fts = array(
		                        'work_schedule_id' => $r->work_schedule_id,
		                        'name' => ' - '.$r->name,
		                        'work_type_name' => $r->work_type_name,
		                        'default' => $r->default
		                    );
		                    array_push($res_arr4,$fts);
		                }
		            }
		        }
		        
                $res_arr = array_merge($res_arr1, $res_arr2, $res_arr3, $res_arr4);		       
		    }
		    //last_query();
		    return ($res_arr) ? $res_arr : FALSE;
		}
		
		public function all_shifts_except_split($comp_id){
		    $sel = array(
		        'work_schedule_id',
		        'name',
		        'work_type_name',
		        'category_id',
		        'default'
		    );
		    $where = array(
		        'comp_id' => $comp_id,
		        'status' => 'Active',
		        'work_type_name !=' => 'Workshift' ,
		        'name !=' => 'Rest Day'
		    );
		    $this->db->select($sel);
		    $this->db->where($where);
		    $this->db->group_by('work_schedule_id');
		    $query = $this->db->get("work_schedule");
		    $res = $query->result();
		    
		    $res_arr = array();
		    if ($res) { 
		        $uws_work_schedule_id = $this->default_shifts_except_split($comp_id, "Regular Work Schedule")['work_schedule_id'];
		        $cws_work_schedule_id = $this->default_shifts_except_split($comp_id, "Compressed Work Schedule")['work_schedule_id'];
		        $nss_work_schedule_id = $this->default_shifts_except_split($comp_id, "Night Shift Schedule")['work_schedule_id'];
		        $fts_work_schedule_id = $this->default_shifts_except_split($comp_id, "Flexi Time Schedule")['work_schedule_id'];
	
		        $res_arr1 = array();
		        $res_arr2 = array();
		        $res_arr3 = array();
		        $res_arr4 = array();
		        foreach ($res as $r) {
		            #p($uws_work_schedule_id.' '.$r->category_id);
		            
		            if ($uws_work_schedule_id != "" || $uws_work_schedule_id != null) {
		                if ($uws_work_schedule_id == $r->work_schedule_id) {
		                    $uws_def = array(
		                        'work_schedule_id' => $uws_work_schedule_id,
		                        'name' => 'Regular Work Schedule (Default)',
		                        'work_type_name' => 'Uniform Working Days',
		                        'default' => $r->default
		                    );
		                    
		                    array_push($res_arr1,$uws_def);
		                }
		                
		                if ($uws_work_schedule_id == $r->category_id) {
		                    $uws = array(
		                        'work_schedule_id' => $r->work_schedule_id,
		                        'name' => ' - '.$r->name,
		                        'work_type_name' => $r->work_type_name,
		                        'default' => $r->default
		                    );
		                    array_push($res_arr1,$uws);
		                }
		            }
		            
		            if ($cws_work_schedule_id != "" || $cws_work_schedule_id != null) {
		                if ($cws_work_schedule_id == $r->work_schedule_id) {
		                    $cws_def = array(
		                        'work_schedule_id' => $cws_work_schedule_id,
		                        'name' => 'Compressed Work Schedule (Default)',
		                        'work_type_name' => 'Uniform Working Days',
		                        'default' => $r->default
		                    );
		                    
		                    array_push($res_arr2,$cws_def);
		                }
		                
		                if ($cws_work_schedule_id == $r->category_id) {
		                    $cws = array(
		                        'work_schedule_id' => $r->work_schedule_id,
		                        'name' => ' - '.$r->name,
		                        'work_type_name' => $r->work_type_name,
		                        'default' => $r->default
		                    );
		                    array_push($res_arr2,$cws);
		                }
		            }
		            
		            if ($nss_work_schedule_id != "" || $nss_work_schedule_id != null) {
		                if ($nss_work_schedule_id == $r->work_schedule_id) {
		                    $nss_def = array(
		                        'work_schedule_id' => $nss_work_schedule_id,
		                        'name' => 'Night Shift Schedule (Default)',
		                        'work_type_name' => 'Uniform Working Days',
		                        'default' => $r->default
		                    );
		                    
		                    array_push($res_arr3,$nss_def);
		                }
		                
		                if ($nss_work_schedule_id == $r->category_id) {
		                    $nss = array(
		                        'work_schedule_id' => $r->work_schedule_id,
		                        'name' => ' - '.$r->name,
		                        'work_type_name' => $r->work_type_name,
		                        'default' => $r->default
		                    );
		                    array_push($res_arr3,$nss);
		                }
		            }
		            
		            if ($fts_work_schedule_id != "" || $fts_work_schedule_id != null) {
		                if ($fts_work_schedule_id == $r->work_schedule_id) {
		                    $fts_def = array(
		                        'work_schedule_id' => $fts_work_schedule_id,
		                        'name' => 'Flexi Time Schedule (Default)',
		                        'work_type_name' => 'Flexible Hours',
		                        'default' => $r->default
		                    );
		                    
		                    array_push($res_arr4,$fts_def);
		                }
		                
		                if ($fts_work_schedule_id == $r->category_id) {
		                    $fts = array(
		                        'work_schedule_id' => $r->work_schedule_id,
		                        'name' => ' - '.$r->name,
		                        'work_type_name' => $r->work_type_name,
		                        'default' => $r->default
		                    );
		                    array_push($res_arr4,$fts);
		                }
		            }
		        }
		        
		        
		        $res_arr = array(
		            "Regular Work Schedule" => $res_arr1,
		            "Compressed Work Schedule" => $res_arr2,
		            "Night Shift Schedule" => $res_arr3,
		            "Flexi Time Schedule" => $res_arr4
		        );
		       # array_push($res_arr,$temp_arr);
		    }
		    //last_query();
		    return ($res_arr) ? $res_arr : FALSE;
		}
		
		public function get_work_schedule_info($work_schedule_id, $comp_id){
		    $s = array(
		        "total_hrs_per_pay_period",
		        "total_hrs_per_day"
		    );
		    
		    $w = array(
		        "work_schedule_id" => $work_schedule_id,
		        "comp_id"=> $comp_id
		    );
		    
		    $this->db->select($s);
		    $this->db->where($w);
		    $q = $this->db->get('work_schedule');
		    $r = $q->row();
		    
		    return ($r) ? $r : FALSE;
		}
		
		/** end **/
	}