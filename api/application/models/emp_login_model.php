 <?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Employee Login For Uniform Working Days & Workshift Model
 *
 * @category Model
 * @version 1.1
 * @author Jonathan Bangga <jonathanbangga@gmail.com>
 *
 */
class Emp_login_model extends CI_Model {
	
	var $type;
	var $split_schedule_id;
	var $schedule_blocks_id;
	var $schedule_blocks_timein_id;
	var $work_schedule_id;
	/**
	 * Check Employee Number
	 * @param unknown_type $emp_no
	 */
	public function check_emp_no($emp_no){
		$w = array(
			'a.payroll_cloud_id'=>$emp_no,
			'a.user_type_id'=>'5'
		);

		$this->edb->where($w);
		$this->edb->join("employee AS e","e.account_id = a.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","LEFT");
		$q = $this->edb->get('accounts AS a');
		
		return ($q->num_rows() > 0) ? $q->row() : FALSE ;
	}
	
	public function convert_emp_no($emp_no){
		$w = array(
				'payroll_cloud_id'=>$emp_no,
				'user_type_id'=>'5'
		);
		$this->edb->where($w);
		$this->edb->join("employee AS e","e.account_id = a.account_id","LEFT");
		$q = $this->edb->get('accounts AS a');
		return ($q->num_rows() > 0) ? $q->row() : FALSE ;
	}
	
	/**
	 * Check Employee Work Schedule ID
	 * @param unknown_type $emp_no
	 * @param unknown_type $check_company_id
	 */
	public function emp_work_schedule($emp_no,$check_company_id){
		// employee group id
		$s = array(
			"ess.work_schedule_id"
		);
		$w_emp = array(
			"a.payroll_cloud_id"=>$emp_no,
			"ess.company_id"=>$check_company_id,
			"e.status"=>"Active",
			"ess.status"=>"Active"
		);
		$this->edb->select($s);
		$this->edb->where($w_emp);
		$this->edb->join("employee AS e","e.emp_id = ess.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$q_emp = $this->edb->get("employee_shifts_schedule AS ess");
		$r_emp = $q_emp->row();
		return ($r_emp) ? $r_emp : FALSE ;
	}
	
	/**
	 * ipakita ni sa clock in kng naa ba cya lunch in og lunch out
	 * kng wla! aw unsa pman d i time out og time in ipakita
	 */
	public function emp_login_lock($work_schedule_id,$comp_id,$emp_no,$current_date = null,$no_work_schedule = false){
		
		$split_now = false;
		$check_emp_no = $this->login_screen_model->new_check_emp_info($emp_no,$comp_id);
	
		$arrx = array(
				'work_schedule_id' => 'eti.work_schedule_id',
				'employee_time_in_id' => 'eti.employee_time_in_id'
		);
		$this->edb->select($arrx);
		$w = array(
				"a.payroll_cloud_id"=>$emp_no,
				"a.user_type_id"=>"5",
				"eti.status" => "Active",
				"eti.comp_id" => $comp_id
		);
		$this->edb->where($w);
		$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
		$this->db->order_by("eti.time_in","DESC");
		$q = $this->edb->get("employee_time_in AS eti",1,0);
		$r = $q->row();
		$day = date('l',strtotime($current_date));
		
		$time_in = date('Y-m-d H:i:s');
		
		if(!$r){
			if(!$no_work_schedule){
				$work_schedule_id = $this->work_schedule_id($comp_id, $check_emp_no->emp_id,date('Y-m-d'));
			}
			$r = (object) array(
				'work_schedule_id' => $work_schedule_id,
				'employee_time_in_id' => 0
			);			
		}
		
		if($this->if_splitschedule($r->work_schedule_id)){	
			$split_now = true;
		}
		
		$type = "";
		$check = false;
		$number_of_breaks_per_day = 0;
		$block_completed = 0;
		$block_not_completed = 0;
		$new_timein = false;
		$cinderella_time = false;
		
		
		$arrx2 = array(
				'break_in_min'
		);
		$this->edb->select($arrx2);
		$w_uwd = array(
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id,
				"days_of_work" => $day,
				"status" => 'Active'
		);
		$this->edb->where($w_uwd);
		$q_uwd = $this->edb->get("regular_schedule");
		$r_uwd = $q_uwd->row();
		
		if($q_uwd->num_rows() > 0){
			$number_of_breaks_per_day = $r_uwd->break_in_min;
		}else{
			# SPLIT SCHEDULE SETTINGS 						
			$val = $this->check_endtime_notempty($emp_no,$comp_id,$work_schedule_id,$r->employee_time_in_id);
		
			$last_timein_sched = $this->filter_date_tim_in($emp_no, $comp_id,true); // xz
 			if($last_timein_sched === true || $val){			
 				$currentdate = date('Y-m-d');				
 			}else{
 				$currentdate = date('Y-m-d',strtotime($last_timein_sched));
 			}
			
 			$night = $this->activate_nightmare_trap($comp_id, $emp_no);
 			if($night){
 				$currentdate = $night['currentdate']; 
 			
 			}
			
			$w_date = array(
					"es.valid_from <="		=>	$currentdate,
 					"es.until >="			=>	$currentdate
 			);
			$this->db->where($w_date);
			
 			$w_ws = array(
 					"em.work_schedule_id"=>$work_schedule_id,
 					"em.company_id"=>$comp_id,
 					"em.emp_id" => $check_emp_no->emp_id
 			);
 			$this->db->where($w_ws);
 			$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
 			$q_ws = $this->edb->get("employee_sched_block AS em");
			$r_ws = $q_ws->result();
			
			if($q_ws->num_rows() > 0){
				$split = $this->new_get_splitinfo($emp_no,$comp_id,$work_schedule_id);
			
				if($split){				
					$number_of_breaks_per_day = $split['break_in_min'];
					$schedule="split";
				}
			}else{
				# FLEXIBLE HOURS
				$arrx4 = array(
						'duration_of_lunch_break_per_day'
				);
				$this->edb->select($arrx4);
				$w_fh = array(
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id
				);
				$this->db->where($w_fh);
				$q_fh = $this->edb->get("flexible_hours");
				$r_fh = $q_fh->row();
				if($q_fh->num_rows() > 0){
					$number_of_breaks_per_day = $r_fh->duration_of_lunch_break_per_day;
					$schedule="flex";
				}
			}
		}
		
		
		$arrx8 = array(
				'time_out' => 'eti.time_out',
				'time_in' => 'eti.time_in'
		);
		$this->edb->select($arrx8);
		$w = array(
				"a.payroll_cloud_id"=>$emp_no,
				"a.user_type_id"=>"5",
				"eti.comp_id" => $comp_id
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
		
		if($q->num_rows() >0){
			if(($r->time_out!="" || $r->time_out!==NULL) && $type=="split"){
				$workday = date('l');
				$date = date('Y-m-d');
				
			}
			else{
				$workday = date("l",strtotime($current_date));
				$date = $current_date;
			}
		
			$check_rest_day = $this->check_rest_day($workday,$work_schedule_id,$comp_id);
			
			///holiday now
			$holiday = $this->company_holiday($date, $comp_id);
			
			if($check_rest_day || $holiday){
				return false;
			}
		}		
		
		if($number_of_breaks_per_day==0 || $number_of_breaks_per_day ==NULL){
			return false;
		}else{
			
			//if workschedu is break assumed
			if(is_break_assumed($work_schedule_id)){				
				return false;
			}else
				return true;
		}
		
		
	}
	
	/**
	 * Check Work Schedule 
	 * @param unknown_type $work_schedule_id
	 * @param unknown_type $check_company_id
	 */
	public function check_workday($work_schedule_id,$check_company_id){
		$w = array(
			"work_schedule_id"=>$work_schedule_id,
			"comp_id"=> $check_company_id
		);	
		$this->edb->where($w);
		
		$arrx = array(
				'work_type_name'
		);
		$this->edb->select($arrx);
		$q = $this->edb->get("work_schedule");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Company ID
	 * @param unknown_type $emp_no
	 */
	public function check_emp_compid($emp_no){
		$w = array(
			"a.payroll_cloud_id"=>$emp_no,
		);
		$this->edb->where($w);
		$this->db->where("e.status","Active");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		return ($r) ? $r->company_id : FALSE ;
	}
	
	/**
	 * Work Schedule ID
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $date
	 */
	public function work_schedule_id($company_id,$emp_id,$date=NULL){
	
		$w = array(
				"emp_id"=>$emp_id,
				"company_id"=>$company_id,
				"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_shifts_schedule");
		$r = $q->result();
		
		foreach($r as $row):			
		  if($date >= $row->valid_from && $date <= $row->until ){
			 	return $row->work_schedule_id;
			}
		endforeach;
		
		return false;
	}
	public function check_if_work_sched_lunch($work_schedule_id,$currentdate2){
		$break 		= false;
		$ws 							= array(
				'work_type_name',
		);
		$wb 		= array('work_schedule_id' => $work_schedule_id, 'status' => 'Active');
		$this->db->select($ws);
		$this->db->where($wb);
		$q = $this->db->get('work_schedule');
		$r = $q->row();
		
		if($r){
			if($r->work_type_name == "Uniform Working Days"){
			
				$wb = array('work_schedule_id' => $work_schedule_id, 'status' => 'Active');
				$wb['days_of_work'] = date("l",strtotime($currentdate2));
				$su = array(
						'work_start_time',
						'work_end_time',
						'total_work_hours',
						'break_in_min',
						'latest_time_in_allowed'
				);
				$this->db->select($su);
				$this->db->where($wb);
				$qu = $this->db->get('regular_schedule');
				$rsched = $qu->row();
				if($rsched){
					$break = $rsched->break_in_min;
				}
				
			}else if($r->work_type_name == "Flexible Hours"){
				$wb = array('work_schedule_id' => $work_schedule_id);
				$su = array(
						'total_hours_for_the_day',
						'number_of_breaks_per_day',
						'latest_time_in_allowed'
				);
				$this->db->select($su);
				$this->db->where($wb);
				$qu = $this->db->get('flexible_hours');
				$rsched = $qu->row();
				if($rsched){
					$break = $rsched->number_of_breaks_per_day;
				}
			}else if($r->work_type_name == "Workshift"){
				
			}
			return $break;
		}
	}
	public function check_shift_correct_sched($work_schedule_id,$work_type_name,$barack_day_trap_exact_date){
		$rsched = (object)array(
				'work_start_time' 			=> '',
				'work_end_time' 			=> '',
				'total_work_hours' 			=> '',
				'break_in_min' 				=> '',
				'latest_time_in_allowed' 	=> ''
		);
		if($work_type_name == "Uniform Working Days"){
				
			$wb = array('work_schedule_id' => $work_schedule_id, 'status' => 'Active');
			$wb['days_of_work'] = date("l",strtotime($barack_day_trap_exact_date));
			$su = array(
					'work_start_time',
					'work_end_time',
					'total_work_hours',
					'break_in_min',
					'latest_time_in_allowed'
			);
			$this->db->select($su);
			$this->db->where($wb);
			$qu = $this->db->get('regular_schedule');
			$rsched = $qu->row();
		}else if($work_type_name == "Flexible Hours"){
			$rsched = array();
		}else if($work_type_name == "Workshift"){
				
		}
		return $rsched;
	}
	
	public function check_current_date_start($emp_no,$barack_date_trap_exact_date,$comp_id){
		$rsched_next = array();
		$kkey 		 = konsum_key();
		// check current next day
		$s = array(
				'ws.work_schedule_id',
				'ws.work_type_name',
				'e.emp_id',
		);
		$w = array(
				'a.payroll_cloud_id' => $emp_no,
		);
		$w2 = array(
				'e.company_id' 		=> $comp_id,
				'ess.valid_from' 	=> $barack_date_trap_exact_date,
		);
	
		$this->db->select($s);
		$this->db->where("AES_DECRYPT(`payroll_cloud_id`,'".$kkey."') = '".$emp_no."' ");
		$this->db->where($w2);
		$this->db->join('employee as e','e.emp_id = ess.emp_id');
		$this->db->join('accounts as a','a.account_id = e.account_id');
		$this->db->join('work_schedule as ws','ws.work_schedule_id = ess.work_schedule_id');
		$nxt = $this->db->get('employee_shifts_schedule as ess');
		$rnxt = $nxt->row();
	
		if($rnxt){
			$rsched_next = $this->check_shift_correct_sched($rnxt->work_schedule_id,$rnxt->work_type_name,$barack_date_trap_exact_date);
		}else{
				
			// if not found in employee_shifts_schedule get it from payroll_group
			$spg 	= array(
					'ws.work_schedule_id',
					'ws.work_type_name',
					'e.emp_id',
			);
			$wpg 	= array(
					'a.payroll_cloud_id' => $emp_no,
			);
			$wpg2 	= array(
					'e.company_id' 		=> $comp_id,
			);
			$this->db->select($spg);
			$this->db->where("AES_DECRYPT(`payroll_cloud_id`,'".$kkey."') = '".$emp_no."' ");
			$this->db->where($wpg2);
			$this->db->join('employee as e','e.emp_id = epi.emp_id');
			$this->db->join('accounts as a','a.account_id = e.account_id');
			$this->db->join('payroll_group as pg','pg.payroll_group_id = epi.payroll_group_id');
			$this->db->join('work_schedule as ws','ws.work_schedule_id = pg.work_schedule_id');
			$nxt_pg = $this->db->get('employee_payroll_information as epi');
			$rnxt = $nxt_pg->row();
			if($rnxt){
				$rsched_next = $this->check_shift_correct_sched($rnxt->work_schedule_id,$rnxt->work_type_name,$barack_date_trap_exact_date);
			}
		}
		return $rsched_next;
	}
	
	public function check_last_sched_time_in($emp_no,$sync_employee_time_in_id,$comp_id){
	
		$arrt = array(
				'time_in'				=> 'eti.time_in',
				'lunch_out' 			=> 'eti.lunch_out',
				'lunch_in' 				=> 'eti.lunch_in',
				'time_out' 				=> 'eti.time_out',
				'late' 					=> 'eti.late_min',
				'overbreak' 			=> 'eti.overbreak_min',
				'tardiness' 			=> 'eti.tardiness_min',
				'employee_time_in_id' 	=> 'eti.employee_time_in_id',
				'date' 					=> 'eti.date',
				'payroll_group_id' 		=> 'epi.payroll_group_id'
		);
	
		if($this->type == "split"){
			$arrt['schedule_blocks_time_in_id'] = 'eti.schedule_blocks_time_in_id';
		}
		$this->edb->select($arrt);
	
		if($sync_employee_time_in_id!=""){
			$w 	= array(
					"a.payroll_cloud_id"		=> $emp_no,
					"a.user_type_id"			=> "5",
					"eti.employee_time_in_id" 	=> $sync_employee_time_in_id,
					"eti.status" 				=> "Active",
					"eti.comp_id" 				=> $comp_id
			);
		}
		else{
			$w 	= array(
					"a.payroll_cloud_id"		=> $emp_no,
					"a.user_type_id"			=> "5",
					"eti.status" 				=> "Active",
					"eti.comp_id" 				=> $comp_id
			);
		}
	
		$this->edb->where($w);
	
		$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
		$this->db->order_by("eti.time_in","DESC");
	
		if($this->type == "split"){
			$q = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
		}
		else{
			$q = $this->edb->get("employee_time_in AS eti",1,0);
		}
		$r = $q->row();
		return ($r) ? $r : false;
	}
	
	
	/**
	 * Insert Time In Log
	 * @param unknown_type $date
	 * @param unknown_type $emp_no
	 * @param unknown_type $min_log
	 * @param unknown_type $work_schedule_id
	 */
	public function insert_time_in($date,$emp_no,$min_log,$work_schedule_id,$check_type="",$source="",$activate_no_schedule = false, $sync_date ="",$sync_employee_time_in_id ="",$comp_id = 0){
		
		// check the correct shift here
		$number_of_breaks_per_day		= 0;
		$barack_date_trap_exact_t_date 	= date('Y-m-d H:i:00');
		$barack_date_trap_exact_date 	= date('Y-m-d');
		
		// CHECK LAST PUNCH WORK SCHEDULE
		$last_timein = $this->check_last_sched_time_in($emp_no,$sync_employee_time_in_id,$comp_id);
		if($last_timein){
			$date_last_time = $last_timein->date;
		}
		// CHECK IF CURRENT TIME IS ALREADY IN THE WORKSCHEDULE OF CURRENT DAY
		$current_date_start = $this->check_current_date_start($emp_no,$barack_date_trap_exact_date,$comp_id);
		if($current_date_start){
			$date_comp = $barack_date_trap_exact_date." ".$current_date_start->work_start_time;
			$date_comp = date('Y-m-d H:i:00	',strtotime($date_comp ."-120 minutes"));
			
			// IF CURRENT TIME IS NOT BELONG TO THE CURRENT DATE SCHED THIS PUNCH IN IS FOR YESTERDAY SCHED
			if(strtotime($date_comp) > strtotime($barack_date_trap_exact_t_date)){
				$barack_date_trap_exact_date 	= date('Y-m-d',strtotime($barack_date_trap_exact_date ."-1 day"));
			}
		}
		// IF THE CURRENT DATE DONT HAVE SCHEDULE MEANS THIS DATE IS REST DAY AND ALREADY TIMEIN THIS MEANS THIS PUNCHIN BELONG TO YESTERDAY SCHED
		else if($check_type != "time in" && $date_last_time != $barack_date_trap_exact_date){
			$barack_date_trap_exact_date 	= date('Y-m-d',strtotime($barack_date_trap_exact_date ."-1 day"));
		}
		$barack_date_trap_exact_n_date 	= date('Y-m-d',strtotime($barack_date_trap_exact_date ."+1 day"));
		$global_rest_day				= false;
		$global_rest_n_day				= false;
		$rsched			 				= array();
		$rsched_next			 		= array();
		$ws 							= array(
												'work_type_name',
										);
		
		$current_date					= $barack_date_trap_exact_t_date;
		$kkey = konsum_key();
		// check current day
		$wb = array('work_schedule_id' => $work_schedule_id, 'status' => 'Active');
		$this->db->select($ws);
		$this->db->where($wb);
		$q = $this->db->get('work_schedule');
		$r = $q->row();
		
		if($r){
			$rsched = $this->check_shift_correct_sched($work_schedule_id,$r->work_type_name,$barack_date_trap_exact_date);
			if($rsched){
				$add_oneday_timein 	= date("Y-m-d",strtotime($barack_date_trap_exact_date." +1 day"))." ".$rsched->work_start_time;
			}
		}

		// check current next day
		$s = array(
				'ws.work_schedule_id',
				'ws.work_type_name',
				'e.emp_id',
			);
		$w = array( 
				'a.payroll_cloud_id' => $emp_no,
			);
		$w2 = array( 
				'e.company_id' 		=> $comp_id,
				'ess.valid_from' 	=> $barack_date_trap_exact_n_date,
			);
		
		$this->db->select($s);
		$this->db->where("AES_DECRYPT(`payroll_cloud_id`,'".$kkey."') = '".$emp_no."' ");
		$this->db->where($w2);
		$this->db->join('employee as e','e.emp_id = ess.emp_id');
		$this->db->join('accounts as a','a.account_id = e.account_id');
		$this->db->join('work_schedule as ws','ws.work_schedule_id = ess.work_schedule_id');
		$nxt = $this->db->get('employee_shifts_schedule as ess');
		$rnxt = $nxt->row();
		
		if($rnxt){
			$rsched_next = $this->check_shift_correct_sched($rnxt->work_schedule_id,$rnxt->work_type_name,$barack_date_trap_exact_n_date);
			$emp_id 	 = $rnxt->emp_id;
		}else{
			
			// if not found in employee_shifts_schedule get it from payroll_group
			$spg 	= array( 
					'ws.work_schedule_id',
					'ws.work_type_name',
					'e.emp_id',
					);
			$wpg 	= array(
					'a.payroll_cloud_id' => $emp_no,
					);
			$wpg2 	= array(
					'e.company_id' 		=> $comp_id,
					);
			$this->db->select($spg);
			$this->db->where("AES_DECRYPT(`payroll_cloud_id`,'".$kkey."') = '".$emp_no."' ");
			$this->db->where($wpg2);
			$this->db->join('employee as e','e.emp_id = epi.emp_id');
			$this->db->join('accounts as a','a.account_id = e.account_id');
			$this->db->join('payroll_group as pg','pg.payroll_group_id = epi.payroll_group_id');
			$this->db->join('work_schedule as ws','ws.work_schedule_id = pg.work_schedule_id');
			$nxt_pg = $this->db->get('employee_payroll_information as epi');
			$rnxt = $nxt_pg->row();
			
			if($rnxt){
				$rsched_next = $this->check_shift_correct_sched($rnxt->work_schedule_id,$rnxt->work_type_name,$barack_day_trap_exact_n_date);
				$emp_id 	 = $rnxt->emp_id;
			}
		}
		
		if(!$rsched){
			$global_rest_day = true;
		}
		
		if(!$rsched_next){
			
			$rsched_next	 = array();
			$global_rest_n_day = true;
		}
		
		if($rsched_next){
			$next_date 						= $barack_date_trap_exact_n_date." ".$rsched_next->work_start_time;
			$barack_date_trap_exact_nt_date = date("Y-m-d H:i:00",strtotime($next_date ."-120 minutes"));
			
			if(strtotime($barack_date_trap_exact_t_date) >= strtotime($barack_date_trap_exact_nt_date)){
				$rsched 						= $rsched_next;
				$global_rest_day 				= $global_rest_n_day;
				$barack_date_trap_exact_date 	= $barack_date_trap_exact_n_date;
				$work_schedule_id				= $rnxt->work_schedule_id;
			}
			
			$add_oneday_timein 	= date("Y-m-d",strtotime($barack_date_trap_exact_date." +1 day"))." ".$rsched_next->work_start_time;
		}
		
		$date 		= $barack_date_trap_exact_date;
		$comp_add 	= $this->get_company_address($comp_id);
		// regular schedule
		if($rsched){
			if($rsched->work_start_time){
				$number_of_breaks_per_day 	= $rsched->break_in_min;
				$shift_name 				= "regular schedule";
				$payroll_sched_timein 		= date('Y-m-d H:i:s',strtotime($date." ".$rsched->work_start_time)) ;
				$payroll_sched_timein_orig	= date('Y-m-d H:i:s',strtotime($date." ".$rsched->work_start_time)) ;
				
				if($rsched->latest_time_in_allowed != NULL || $rsched->latest_time_in_allowed != ""){
					$val 					= $rsched->latest_time_in_allowed;
					$threshold_min			= $rsched->latest_time_in_allowed;
					$payroll_sched_timein 	= date('Y-m-d H:i:s',strtotime($payroll_sched_timein  ." +{$val} minutes" )) ;
				}
				$current_date 				= $barack_date_trap_exact_t_date;
				$time_in 					= date("H:i:00",strtotime($current_date));
			}
			else{
				// SPLIT SCHEDULE SETTINGS
				$check_emp_no 		= $this->login_screen_model->new_check_emp_info($emp_no,$comp_id);
				$last_timein_sched 	= $this->filter_date_tim_in($emp_no, $comp_id,true);
	
				//this to identify if the last end time of split is not skipping to time in			
				$val = $this->check_endtime_notempty($emp_no,$comp_id,$work_schedule_id,$r->employee_time_in_id,$r->time_out);
				
				if($last_timein_sched === true || $val){
					$currentdate 	= date('Y-m-d');
					$current_date 	= date('Y-m-d H:i:s');
				}
				else{
					
					$currentdate 	= date('Y-m-d',strtotime($last_timein_sched));
					$current_date 	= date('Y-m-d H:i:s',strtotime($last_timein_sched));
				}
				
				$night = $this->activate_nightmare_trap($comp_id, $emp_no);
				if($night){
					$currentdate = $night['currentdate'];
				}
			 	
				$w_date = array(
						"es.valid_from <="		=>	$currentdate,
						"es.until >="			=>	$currentdate
				);
				$this->db->where($w_date);
				
				$w_ws = array(
						"em.work_schedule_id"	=> $work_schedule_id,
						"em.company_id"			=> $comp_id,
						"em.emp_id" 			=> $check_emp_no->emp_id
				);
				$this->db->where($w_ws);
				$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
				$q_ws = $this->edb->get("employee_sched_block AS em");
				$r_ws = $q_ws->result();
				
			  	if($q_ws->num_rows() > 0){
					$read_first_split =false;
					$w = array(
							"employee_time_in_id"	=> $r->employee_time_in_id,
							"eti.status" 			=> "Active"
					);
					$this->edb->where($w);
					$this->db->order_by("eti.time_in","DESC");
					$split_q 		= $this->edb->get("schedule_blocks_time_in AS eti",1,0);
					$query_split 	= $split_q->row();
					
					$night_date 	= $this->activate_nightmare_trap($comp_id, $emp_no);
					$currentdate 	= date('Y-m-d');
					if($night_date){
						$currentdate = $night_date['currentdate'];
					}
					
					if($currentdate == $r->date){
						$new_employee_timein = false;
					}
					else{
						$new_employee_timein = true;
					}
					$split_total_activate = false;
					
					//get the schedule of split;
					$split = $this->new_get_splitinfo($emp_no, $comp_id, $work_schedule_id);
					
					$this->type = "";
					if($split){
						$this->type = "split";
						$number_of_breaks_per_day 	= $split['break_in_min'];
						$this->schedule_blocks_id 	= $split['schedule_blocks_id'];
						$check_type 				= $split['clock_type'];
						$first_block_start_time 	= $split['first_block_start_time'];
						$shift_name 				= "split schedule";
						if($query_split){
							if($split['last_block'] == $query_split->schedule_blocks_id && $check_type == "time out"){
								$split_total_activate = true;
							}
						}
					}
				}else{
					// FLEXIBLE HOURS
					$w_fh 	= array(
							"work_schedule_id"	=> $work_schedule_id,
							"company_id"		=> $comp_id
							);
					$this->db->where($w_fh);
					$q_fh = $this->db->get("flexible_hours");
					$r_fh = $q_fh->row();
					if($q_fh->num_rows() > 0){
						$number_of_breaks_per_day 	= $r_fh->number_of_breaks_per_day;
						$shift_name 				= "flexible hours";	
					}
				}
			}
		}
		else{
			$arrt = array(
					'time_in'				=> 'eti.time_in',
					'lunch_out' 			=> 'eti.lunch_out',
					'lunch_in' 				=> 'eti.lunch_in',
					'time_out' 				=> 'eti.time_out',
					'late' 					=> 'eti.late_min',
					'overbreak' 			=> 'eti.overbreak_min',
					'tardiness' 			=> 'eti.tardiness_min',
					'employee_time_in_id' 	=> 'eti.employee_time_in_id',
					'date' 					=> 'eti.date',
					'payroll_group_id' 		=> 'epi.payroll_group_id'
			);
			
			$this->edb->select($arrt);
				
			if($sync_employee_time_in_id!=""){
				$w 	= array(
						"a.payroll_cloud_id"		=> $emp_no,
						"a.user_type_id"			=> "5",
						"eti.employee_time_in_id" 	=> $sync_employee_time_in_id,
						"eti.status" 				=> "Active",
						"eti.comp_id" 				=> $comp_id
				);
			}
			else{
				$w 	= array(
						"a.payroll_cloud_id"		=> $emp_no,
						"a.user_type_id"			=> "5",
						"eti.status" 				=> "Active",
						"eti.comp_id" 				=> $comp_id
				);
			}
				
			$this->edb->where($w);
				
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
			$this->db->order_by("eti.time_in","DESC");
				
			$q = $this->edb->get("employee_time_in AS eti",1,0);
			
			$r1 = $q->row();
			
			if($check_type == "time out" || $check_type == 'lunch out' || $check_type == "lunch in"){
				$where_update = array(
						"eti.emp_id"				=> $emp_id,
						"eti.comp_id"				=> $comp_id,
						"eti.employee_time_in_id"	=> $r1->employee_time_in_id,
						"eti.status"				=> "Active"
				);
			
				$get_diff = (strtotime($current_date) - strtotime($r1->time_in)) / 60;
				if($min_log < $get_diff){
					$total_h_r	= (total_min_between($current_date,$r1->time_in) / 60);
					$update_val = array("time_out"=>$current_date,"total_hours_required"=>$total_h_r,"total_hours"=>$total_h_r);
					$this->db->where($where_update);
					
					$update = $this->db->update("employee_time_in AS eti",$update_val);
				}
				
				
				$this->edb->where($where_update);
				$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
				$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
				$q2 = $this->edb->get("employee_time_in AS eti",1,0);
					
				return ($q2) ? $q2->row() : FALSE ;
				exit;
					
			}else if($check_type == "time in"){
				
				$get_diff 	= (strtotime($current_date) - strtotime($r1->time_out)) / 60;
				if($min_log < $get_diff){
					$val 	= array(
							"emp_id"			=> $emp_id,
							"comp_id"			=> $comp_id,
							"date"				=> $date,
							"time_in"			=> $current_date,
							"work_schedule_id" 	=> "-1",
							"source" 			=> $source,
							"location" 			=> $comp_add,
					);
					$insert = $this->db->insert("employee_time_in",$val);
					
					if($insert){
						$w2 	= array(
								"a.payroll_cloud_id"	=> $emp_no,
								"eti.date"				=> $date,
								"eti.status" 			=> "Active"
						);
						$this->edb->where($w2);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$this->db->order_by("eti.time_in","DESC");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							
						return ($q2) ? $q2->row() : FALSE ;
						exit;
					}else{
						$this->edb->where($where_update);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							
						return ($q2) ? $q2->row() : FALSE ;
						exit;
					}
				}
			}
		}
		//check employee on leave
		$onleave 	= check_leave_appliction($date,$emp_id,$comp_id);
		$ileave 	= 'no';
		if($onleave)
			$ileave = 'yes';
		
		
		// if workschedule has no break
		if(($number_of_breaks_per_day == 0 || $number_of_breaks_per_day == NULL)){
			
			// check employee time in
			/*
			$current_date 	= $barack_date_trap_exact_t_date;
			
			$arrz 			= array(
									'time_in'				=> 'eti.time_in',
									'lunch_out' 			=> 'eti.lunch_out',
									'lunch_in' 				=> 'eti.lunch_in',
									'time_out' 				=> 'eti.time_out',
									'late' 					=> 'eti.late_min',
									'overbreak' 			=> 'eti.overbreak_min',
									'tardiness' 			=> 'eti.tardiness_min',
									'employee_time_in_id' 	=> 'eti.employee_time_in_id',
									'date' 					=> 'eti.date',
									'payroll_group_id' 		=> 'epi.payroll_group_id'
							);
			
			if($this->type == "split"){
				$arrz['schedule_blocks_time_in_id'] = "eti.schedule_blocks_time_in_id";
			}
			$this->edb->select($arrz);
			
			if($sync_employee_time_in_id!=""){
				$w 	= array(
						"a.payroll_cloud_id"		=> $emp_no,
						"a.user_type_id"			=> "5",
						"eti.employee_time_in_id" 	=> $sync_employee_time_in_id,
						"eti.status" 				=> "Active",
						"eti.comp_id" 				=> $comp_id
				);
			}
			else{
				$w 	= array(
						"a.payroll_cloud_id"		=> $emp_no,
						"a.user_type_id"			=> "5",
						"eti.status" 				=> "Active",
						"eti.comp_id" 				=> $comp_id
				);
			}
			
			$this->edb->where($w);
			
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
			$this->db->order_by("eti.time_in","DESC");			
			
			if($this->type == "split"){
				$q = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
			}
			else{
				$q = $this->edb->get("employee_time_in AS eti",1,0);
			}
			*/
			$r = $last_timein;
			
			if($q->num_rows() == 0){

				/* CHECK TIME IN START */
				$time_in = date('H:i:s');
				$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id,$time_in,$this->schedule_blocks_id);
				
				if($this->type !="split"){

					if($wst != FALSE){
						// new start time
						$nwst = date("Y-m-d {$wst}");
						$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
					}
					
					//late min for early tardiness
					$late_min = $this->late_min($comp_id, $date, $emp_id, $work_schedule_id);
					
					// insert time in log
					$val = array(
						"emp_id"			=> $emp_id,
						"comp_id"			=> $comp_id,
						"date"				=> $date,
						"work_schedule_id" 	=> $this->work_schedule_id,
						"time_in"			=> $current_date,
						"late_min" 			=> $late_min,
						"source" 			=> $source,
						"flag_on_leave" 	=> $ileave
					);
					$insert = $this->db->insert("employee_time_in",$val);
					
					if($insert){
						$w2 = array(
							"a.payroll_cloud_id"=> $emp_no,
							"eti.date"			=> $date,
							"eti.status" 		=> "Active"
						);
						$this->edb->where($w2);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
		
						return ($q2) ? $q2->row() : FALSE ;
					}
				}
				else{
					$get_diff 		= 0;
					$eti		  	= "";
					$rto		  	= "";
					$ro		 		= "";
					$rl		 	 	= "";
					$rt		  		= "";
					$sbi	  		= "";
					if($r){
						$get_diff 	= (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
						$eti		= $r->employee_time_in_id;
						$sbi		= $r->schedule_blocks_time_in_id;
						$rt			= $r->time_in;
						$rl			= $r->lunch_out;
						$ro			= $r->lunch_in;
						$rto		= $r->time_out;
					}
					$arr = array(
							'emp_no' 				=> $emp_no,
							'current_date' 			=> $current_date,
							'emp_id'				=> $emp_id,
							'comp_id' 				=> $comp_id,
							'breaks' 				=> $number_of_breaks_per_day,
							'check_type' 			=> "time in",
							'min_log' 				=> $min_log,
							'get_diff' 				=> $get_diff,
							'employee_time_in_id' 	=> $eti,
							'work_schedule_id' 		=> $work_schedule_id,
							'block_id' 				=> $sbi,
							'schedule_blocks_id' 	=> $this->schedule_blocks_id,
							'time_in' 				=> $rt,
							'time_out' 				=> $rto,
							'lunch_in' 				=> $ro,
							'lunch_out' 			=> $rl,
							'new_timein' 			=> $new_timein,
							'timein_id' 			=> $timein_id,
							'new_employee_timein' 	=> $new_employee_timein
					);
					return $this->split_schedule_time_in($arr,$split_total_activate,"",$date,$source,$first_block_start_time);
				}
			}
			else{
				// get date time in to date time out
				$workday 			= date("l",strtotime($date));
				$payroll_group_id 	= 0;
	
				// check rest day
				$check_rest_day 	= $this->check_rest_day($workday,$work_schedule_id,$comp_id);
				if($check_rest_day){
					// global where update data
					$where_update = array(
									"eti.emp_id"				=> $emp_id,
									"eti.comp_id"				=> $comp_id,
									"eti.employee_time_in_id"	=> $r->employee_time_in_id,
									"eti.status" 				=> "Active"
									);
					
					if($check_type == "time out"){
						// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE FOR REST DAY
						$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
						if($min_log < $get_diff){
							$update_val = array("time_out"=>$current_date);
							$this->db->where($where_update);
							$update = $this->db->update("employee_time_in AS eti",$update_val);
						}
						
						$this->edb->where($where_update);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
						
						//holiday now
						$holiday = $this->company_holiday($date, $comp_id);
						
						if($holiday){
							$get_diff 	= (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
							$arr 		= array(
										'emp_no' 				=> $emp_no,
										'breaks' 				=> $number_of_breaks_per_day,
										'current_date' 			=> $current_date,
										'date' 					=> $date,
										'emp_id' 				=> $emp_id,
										'comp_id' 				=> $comp_id,
										'check_type' 			=> $check_type,
										'min_log' 				=> $min_log,
										'get_diff' 				=> $get_diff,
										'employee_time_in_id' 	=> $r->employee_time_in_id,
										'work_schedule_id' 		=> $work_schedule_id,
										'time_in' 				=> $r->time_in,
										'time_out' 				=> $r->time_out,
										'lunch_in' 				=> $r->lunch_in,
										'lunch_out' 			=> $r->lunch_out,
										'new_timein' 			=> $new_timein,
										'timein_id' 			=> $timein_id
										);
								
							return $this->holiday_time_in($arr);
						}
						
						// update total hours and total hours required rest day
						$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
						$update_timein_logs = array(
												"tardiness_min"			=> 0,
												"undertime_min"			=> 0,
												"total_hours"			=> $get_total_hours,
												"total_hours_required"	=> $get_total_hours
											);
						$this->db->where($where_update);
						$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
						
						return ($q2) ? $q2->row() : FALSE ;
						exit;
						
					}else if($check_type == "time in"){
						
						/* CHECK TIME IN START */
						$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id,$r->time_in);
						
						if($wst != FALSE){
							// new start time
							$nwst 					= date("Y-m-d {$wst}");
							$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
						}

						// insert time in value for rest day ============================================== >>> INSERT TIME IN VALUE FOR REST DAY
						$insert 	= FALSE;
						$get_diff 	= (strtotime($current_date) - strtotime($r->time_out)) / 60;
						if($min_log < $get_diff){
							$val 	= array(
										"emp_id"			=> $emp_id,
										"comp_id"			=> $comp_id,
										"date"				=> $date,
										"time_in"			=> $current_date,
										"work_schedule_id" 	=> -1,
										"source" 			=> $source,
										"location" 			=> $comp_add,
										"flag_on_leave" 	=> $ileave
									);
							$insert = $this->db->insert("employee_time_in",$val);	
						}
						
						if($insert){
							$w2 	= array(
										"a.payroll_cloud_id"=>$emp_no,
										"eti.date"			=>$date,
										"eti.status" 		=> "Active"
									);
							$this->edb->where($w2);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$this->db->order_by("eti.time_in","DESC");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
							exit;
						}else{
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
							exit;
						}
					}
				}
				
				if($this->type != "split"){
					$workday_settings_start_time = $this->check_workday_settings_start_time($workday,$work_schedule_id,$comp_id,$r->time_in); 
					$workday_settings_end_time = $this->check_workday_settings_end_time($workday,$work_schedule_id,$comp_id,$r->time_in);
					
					if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
						// for night shift time in and time out value for working day
						$check_bet_timein 	= date("Y-m-d")." ".$workday_settings_start_time;
						$check_bet_timeout 	= date("Y-m-d",strtotime("+1 day"))." ".$workday_settings_end_time;
					}else{
						// for day shift time in and time out value for working day
						$check_bet_timein 	= date("Y-m-d")." ".$workday_settings_start_time;
						$check_bet_timeout 	= date("Y-m-d")." ".$workday_settings_end_time;
					}
					
					// check between date time in to date time out
					$add_oneday_timein = date("Y-m-d",strtotime($r->time_in." +1 day"))." ".$workday_settings_start_time;
				}
				
				
				/**
				 * SPLIT SCHEDULE GENERATE
				 * EVERY DAY LOGIN
				 * SCHEDULE IS SPLIT INIT HERE THIS IS THE FIRST TIMEIN OCCUR AND SPLIT SCHED IS ALREADY IN DB
				 */
				
				if($this->type == 'split'){
					
					$get_diff = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
					$arr = array(
							'emp_no' 				=> $emp_no,
							'current_date' 			=> $current_date,
							'emp_id'				=> $emp_id,
							'comp_id' 				=> $comp_id,
							'breaks' 				=> $number_of_breaks_per_day,
							'check_type' 			=> $check_type,
							'min_log' 				=> $min_log,
							'get_diff' 				=> $get_diff,
							'employee_time_in_id' 	=> $r->employee_time_in_id,
							'work_schedule_id' 		=> $work_schedule_id,
							'block_id' 				=> $r->schedule_blocks_time_in_id,
							'schedule_blocks_id' 	=> $this->schedule_blocks_id,
							'time_in' 				=> $r->time_in,
							'time_out' 				=> $r->time_out,
							'lunch_in' 				=> $r->lunch_in,
							'lunch_out' 			=> $r->lunch_out,
							'new_timein' 			=> $new_timein,
							'timein_id' 			=> $timein_id,
							'new_employee_timein' 	=> $new_employee_timein
					
					);
					return $this->split_schedule_time_in($arr,$split_total_activate,"",$date,$source,$first_block_start_time);
				}
				
				if(strtotime($check_bet_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($add_oneday_timein." -2 hours")){
					
					
					// global where update data
					$where_update = array(
									"eti.emp_id"				=> $emp_id,
									"eti.comp_id"				=> $comp_id,
									"eti.employee_time_in_id"	=> $r->employee_time_in_id,
									"eti.status"				=> "Active"
									);
					
					if($check_type == "time out"){
						// update time out value for rest day =============== >>> UPDATE TIME OUT VALUE
						$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
						
						if($min_log < $get_diff){
							$update_val = array("time_out"=>$current_date);
							$this->db->where($where_update);
							$update = $this->db->update("employee_time_in AS eti",$update_val);
						}
						
						$this->edb->where($where_update);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
		
						// update total hours and total hours required rest day
						$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
						
						// tardiness and undertime value
						$update_tardiness = $this->get_tardiness_val($emp_id,$comp_id,$r->time_in,$work_schedule_id,$r->date);  //rrr
						$update_undertime = $this->get_undertime_val($emp_id,$comp_id,$r->time_in,$current_date,$work_schedule_id,$number_of_breaks_per_day,$date); 
						
						// check tardiness value
						$flag_tu = 0;
						
						$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r->time_in)), $emp_id,$work_schedule_id);
						$get_total_hours_worked = ($hours_worked / 2) + .5;
						
						//holiday now
						$holiday = $this->company_holiday($date, $comp_id);
						
						if($holiday){
							$get_diff = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
								
							$arr = array(
									'emp_no' 				=> $emp_no,
									'breaks' 				=> $number_of_breaks_per_day,
									'current_date' 			=> $current_date,
									'date' 					=> $date,
									'emp_id' 				=> $emp_id,
									'comp_id' 				=> $comp_id,
									'check_type' 			=> $check_type,
									'min_log' 				=> $min_log,
									'get_diff' 				=> $get_diff,
									'employee_time_in_id' 	=> $r->employee_time_in_id,
									'work_schedule_id' 		=> $work_schedule_id,
									'time_in' 				=> $r->time_in,
									'time_out' 				=> $r->time_out,
									'lunch_in' 				=> $r->lunch_in,
									'lunch_out' 			=> $r->lunch_out,
									'new_timein' 			=> $new_timein,
									'timein_id' 			=> $timein_id
							);
						
							return $this->holiday_time_in($arr);
						}
						
						$hw 				= $this->convert_to_min($hours_worked);
						$new_total_hours 	= $this->get_tot_hours($emp_id,$comp_id,$r->time_in,$r->lunch_out,$r->lunch_in,$current_date,$hours_worked,$work_schedule_id,$number_of_breaks_per_day); 

						$update_timein_logs = array(
												"tardiness_min"				=> $update_tardiness,
												"undertime_min"				=> $update_undertime,						
												"total_hours"				=> $hours_worked,
												"total_hours_required"		=> $get_total_hours,
												"flag_tardiness_undertime"	=> $flag_tu
											);
						
						$att = $this->calculate_attendance($comp_id,$r->time_in,$current_date);
						
						if($att){
							$total_hours_worked = $this->total_hours_worked($currentdate, $r->time_in);
							$total_hours_worked = $this->convert_to_hours($total_hours_worked);
							
							if($r->time_in >= $r->lunch_out){
								$update_timein_logs['lunch_in'] 	= null;	
								$update_timein_logs['lunch_out'] 	= null;
							}
							elseif($current_date <= $r->lunch_in){
								$update_timein_logs['lunch_in'] 	= null;
								$update_timein_logs['lunch_out'] 	= null;
							}
	
							$update_timein_logs['total_hours_required'] = $total_hours_worked;
							$update_timein_logs['absent_min'] 			= ($hours_worked - $total_hours_worked) * 60;
							$update_timein_logs['late_min'] 			= 0;
							$update_timein_logs['tardiness_min'] 		= 0;
							$update_timein_logs['undertime_min'] 		= 0;
						}
						
						$this->db->where($where_update);
						$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
						
						return ($q2) ? $q2->row() : FALSE ;
						
					}
					else if($check_type == "time in"){
						
						/* CHECK TIME IN START */
						$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id,$r->time_in);
						if($wst != FALSE){
							// new start time
							$nwst = date("Y-m-d {$wst}");
							$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
						}
						
						$late_min = $this->late_min($comp_id, $date, $emp_id, $work_schedule_id);
						
						// insert time in log ============================================= >>>> INSERT NEW TIME IN LOG SAME DATE
						$insert 	= FALSE;
						$get_diff 	= (strtotime($current_date) - strtotime($r->time_out)) / 60;
						if($min_log < $get_diff){
							$val 	= array(
										"emp_id"			=> $emp_id,
										"comp_id"			=> $comp_id,
										"date"				=> $date,
										"time_in"			=> $current_date,
										"work_schedule_id" 	=> $this->work_schedule_id,
										"source" 			=> $source,
										'late_min' 			=> $late_min,
										"location" 			=> $comp_add,
										"flag_on_leave" 	=> $ileave
									);
							$insert = $this->db->insert("employee_time_in",$val);	
						}
						
						if($insert){
							$w2 	= array(
										"a.payroll_cloud_id"=> $emp_no,
										"eti.date"			=> $date,
										"eti.status" 		=> "Active"
									);
							$this->edb->where($w2);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$this->db->order_by("eti.time_in","DESC");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			 
							return ($q2) ? $q2->row() : FALSE ;
						}else{
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
						}
					}
				}else{
					
					if(false){
						/* CHECK TIME IN START */
						$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id,$r->time_in);
						if($wst != FALSE){
							// new start time
							$nwst 					= date("Y-m-d {$wst}");
							$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
						}
						
						// insert time in log
						$val 	= array(
									"emp_id"			=> $r_emp->emp_id,
									"comp_id"			=> $r_emp->company_id,
									"date"				=> $date,
									"work_schedule_id" 	=> $this->work_schedule_id,
									"time_in"			=> $current_date
								);
						$insert = $this->db->insert("employee_time_in",$val);
						
						if($insert){
							$w2 	= array(
										"a.payroll_cloud_id"	=> $emp_no,
										"eti.date"				=> $date,
										"eti.status" 			=> "Active"
									);
							$this->edb->where($w2);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$this->db->order_by("eti.time_in","DESC");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
						}
					}else{
						// global where update data
						$where_update = array(
							"eti.emp_id"				=> $emp_id,
							"eti.comp_id"				=> $comp_id,
							"eti.employee_time_in_id"	=> $r->employee_time_in_id
						);
						
						// comment out by aldrin
						if($check_type == "time out"){
						  
							// update time out value ============================================== >>> UPDATE TIME OUT VALUE
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							if($min_log < $get_diff){
								$update_val = array("time_out"=>$current_date);
								$this->db->where($where_update);
								$update 	= $this->db->update("employee_time_in AS eti",$update_val);
							}
							
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							// update total hours and total hours required rest day
							$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
							
							// tardiness and undertime value
						
							$update_tardiness 	= $this->get_tardiness_val($emp_id,$comp_id,$r->time_in,$work_schedule_id,$r->date);  //zzz
							$update_undertime 	= $this->get_undertime_val($emp_id,$comp_id,$r->date,$current_date,$work_schedule_id,$number_of_breaks_per_day,$date);
						
							// check tardiness value
							$flag_tu 			= 0;
							$hours_worked 		= $this->get_hours_worked(date("Y-m-d",strtotime($r->date)), $emp_id,$work_schedule_id);
							
							$get_total_hours_worked = ($hours_worked / 2) + .5;
							
							//holiday now
							$holiday = $this->company_holiday($date, $comp_id);
							
							if($holiday){
								$get_diff 	= (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
								$arr 		= array(
												'emp_no' 				=>	$emp_no,
												'breaks' 				=> $number_of_breaks_per_day,
												'current_date' 			=> $current_date,
												'date' 					=>$date,
												'emp_id' 				=> $emp_id,
												'comp_id' 				=> $comp_id,
												'check_type' 			=> $check_type,
												'min_log' 				=> $min_log,
												'get_diff' 				=> $get_diff,
												'employee_time_in_id' 	=> $r->employee_time_in_id,
												'work_schedule_id' 		=> $work_schedule_id,
												'time_in' 				=> $r->time_in,
												'time_out' 				=> $r->time_out,
												'lunch_in' 				=> $r->lunch_in,
												'lunch_out' 			=> $r->lunch_out,
												'new_timein' 			=> $new_timein,
												'timein_id' 			=> $timein_id
											);
								return $this->holiday_time_in($arr);
							
							}
							// required hours worked only
							$new_total_hours 	= $this->get_tot_hours($emp_id,$comp_id,$r->time_in,$r->lunch_out,$r->lunch_in,$current_date,$hours_worked,$work_schedule_id,$number_of_breaks_per_day); //endx
							$update_timein_logs = array(
													"tardiness_min"				=> $update_tardiness,
													"undertime_min"				=> $update_undertime,
													"total_hours"				=> $hours_worked,
													"total_hours_required"		=> $get_total_hours,
													"flag_tardiness_undertime"	=> $flag_tu
												);
							
							//attendance settings
							$att = $this->calculate_attendance($comp_id,$r->time_in,$current_date);
							
							if($att){
								$total_hours_worked = $this->total_hours_worked($current_date, $r->time_in);
								$total_hours_worked = $this->convert_to_hours($total_hours_worked);
								if($r->time_in >= $r->lunch_out){
									$update_timein_logs['lunch_in'] 	= null;	
									$update_timein_logs['lunch_out'] 	= null;
								}
								elseif($current_date <= $r->lunch_in){
									$update_timein_logs['lunch_in'] 	= null;
									$update_timein_logs['lunch_out'] 	= null;
								}
								$update_timein_logs['total_hours_required'] = $total_hours_worked;
								$update_timein_logs['absent_min'] 			= ($hours_worked - $total_hours_worked) * 60;
								$update_timein_logs['late_min'] 			= 0;
								$update_timein_logs['tardiness_min'] 		= 0;
								$update_timein_logs['undertime_min'] 		= 0;
							}
							
							$this->db->where($where_update);
							$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
							
							return ($q2) ? $q2->row() : FALSE ;
							
						}
						else if ($check_type == "time in"){
							
							check_leave_appliction($date,$emp_id,$comp_id);
							
							/* CHECK TIME IN START */
							$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id,$r->time_in);
							
							if($wst != FALSE){
								// new start time
								$nwst = date("Y-m-d {$wst}");
								$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
							}
							
							$late_min 	= $this->late_min($comp_id, $date, $emp_id, $this->work_schedule_id);
							
							// insert time in log ================================================================ >>>> INSERT NEW TIME IN LOG SAME DATE
							$insert 	= FALSE;
							$get_diff 	= (strtotime($current_date) - strtotime($r->time_out)) / 60;
							
							if($min_log < $get_diff){
								$val = array(
									"emp_id"			=> $emp_id,
									"comp_id"			=> $comp_id,
									"date"				=> $date,
									"time_in"			=> $current_date,
									"work_schedule_id" 	=> $this->work_schedule_id,
									"source" 			=> $source,
									"late_min" 			=> $late_min,
									"location" 			=> $comp_add,
									"flag_on_leave" 	=> $ileave
								);
								$insert = $this->db->insert("employee_time_in",$val);	
							}
							
							if($insert){
								$w2 = array(
									"a.payroll_cloud_id"=> $emp_no,
									"eti.date"			=> $date,
									"eti.status" 		=> "Active"
								);
								$this->edb->where($w2);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$this->db->order_by("eti.time_in","DESC");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
				
								return ($q2) ? $q2->row() : FALSE ;
							}else{
								$this->edb->where($where_update);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
				
								return ($q2) ? $q2->row() : FALSE ;
							}
							
						}
					}
				}
			}
		}else{
			
			if($sync_date != ""){
				$current_date = $sync_date;
			}else{
			// check employee time in
				$current_date = $barack_date_trap_exact_t_date;
			}
			
			// CHECK WORK SCHEDULE
			/*
			$arrt = array(
					'time_in'				=> 'eti.time_in',
					'lunch_out' 			=> 'eti.lunch_out',
					'lunch_in' 				=> 'eti.lunch_in',
					'time_out' 				=> 'eti.time_out',
					'late' 					=> 'eti.late_min',
					'overbreak' 			=> 'eti.overbreak_min',
					'tardiness' 			=> 'eti.tardiness_min',
					'employee_time_in_id' 	=> 'eti.employee_time_in_id',
					'date' 					=> 'eti.date',
					'payroll_group_id' 		=> 'epi.payroll_group_id'
			);
			
			if($this->type == "split"){
				$arrt['schedule_blocks_time_in_id'] = 'eti.schedule_blocks_time_in_id';
			}
			$this->edb->select($arrt);
			
			if($sync_employee_time_in_id!=""){
				$w 	= array(
						"a.payroll_cloud_id"		=> $emp_no,
						"a.user_type_id"			=> "5",
						"eti.employee_time_in_id" 	=> $sync_employee_time_in_id,
						"eti.status" 				=> "Active",
						"eti.comp_id" 				=> $comp_id
					);
			}
			else{
				$w 	= array(
						"a.payroll_cloud_id"		=> $emp_no,
						"a.user_type_id"			=> "5",
						"eti.status" 				=> "Active",
						"eti.comp_id" 				=> $comp_id
					);
			}
			
			$this->edb->where($w);
			
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
			$this->db->order_by("eti.time_in","DESC");
			
			if($this->type == "split"){
				$q = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
			}
			else{
				$q = $this->edb->get("employee_time_in AS eti",1,0);
			}
			*/
			$r = $last_timein;
			
			if($q->num_rows() == 0){
				
				if($sync_date !=""){
					$time_in = date('H:i:s',strtotime($sync_date));
				}
				else{
					$time_in = date('H:i:s');				
				}
				
				if($this->type!='split'){
					if($check_type == "time in"){
						
						$timeIn = $this->only_for_timeIn_reg_sched($emp_id,$emp_no,$work_schedule_id,$comp_id,$current_date,$date,$time_in,$source,$comp_add,$ileave);
						return $timeIn;
					}
				}else{
					if($r){
						$timein_id = $r->employee_time_in_id;
					}
					// insert time in log
					if($new_employee_timein){
						$val = array(
								"emp_id"			=> $emp_id,
								"comp_id"			=> $comp_id,
								"date"				=> $date,
								"time_in"			=> $current_date,
								"work_schedule_id" 	=> $work_schedule_id,
								"source" 			=> $source,
								"location" 			=> $comp_add,
								"flag_on_leave" 	=> $ileave
						);
						$insert = $this->db->insert("employee_time_in",$val);
						$timein_id = $this->db->insert_id();
					}
						
					$val2 = array(
							"employee_time_in_id" 	=> $timein_id,
							"date"					=> $date,
							"time_in"				=> $current_date,
							"emp_id"				=> $emp_id,
							"comp_id"				=> $comp_id,
							"schedule_blocks_id" 	=> $this->schedule_blocks_id
					);
					$insert2 = $this->db->insert("schedule_blocks_time_in",$val2);
					
					if($insert2){
						$w2 = array(
								"a.payroll_cloud_id"=>$emp_no,
								"eti.date"=>$date
						);
						$this->edb->where($w2);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
					
						return ($q2) ? $q2->row() : FALSE ;
					}
				}
			}
			else{
				
				// get date time in to date time out
				$workday 			= date("l",strtotime($date));
				
				$payroll_group_id 	= $r->payroll_group_id;
				
				// check rest day
				//$check_rest_day 	= $this->check_rest_day($workday,$work_schedule_id,$comp_id);
				
				// this is improvized checking of rest day
				$check_rest_day		= $global_rest_day;
				
				//change code here - aldrin
				if($check_rest_day){
					// global where update data
					if($sync_employee_time_in_id!=""){
						$where_update 	= array(
											"eti.emp_id"				=> $emp_id,
											"eti.comp_id"				=> $comp_id,
											"eti.employee_time_in_id"	=> $sync_employee_time_in_id
										);
					}
					else{				
						$where_update 	= array(
											"eti.emp_id"				=> $emp_id,
											"eti.comp_id"				=> $comp_id,
											"eti.employee_time_in_id"	=> $r->employee_time_in_id
										);
					}
					
					if($check_type == "time out"){
						// not split shift
						if($this->type!='split'){
							// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE FOR REST DAY
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							if($min_log < $get_diff){
								$update_val = array("time_out"=>$current_date);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);
							}
							
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							
							// update total hours and total hours required rest day
							$get_total_hours 	= (strtotime($current_date) - strtotime($r->time_in)) / 3600;
							$update_timein_logs = array(
								"tardiness_min"			=> 0,
								"undertime_min"			=> 0,
								"total_hours"			=> $get_total_hours,
								"total_hours_required"	=> $get_total_hours
							);
							$this->db->where($where_update);
							$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
							
							return ($q2) ? $q2->row() : FALSE ;
							exit;
						}
						else{
							$where_update2 = array(
									"eti.emp_id"=>$emp_id,
									"eti.schedule_blocks_time_in_id"=>$r->schedule_blocks_time_in_id
							);
							
							// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE FOR REST DAY
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							
							if($min_log < $get_diff){
								$update_val = array("time_out"=>$current_date);
								$this->db->where($where_update2);
								$update = $this->db->update("schedule_blocks_time_in AS eti",$update_val);
							}
								
							$this->edb->where($where_update2);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("schedule_blocks_time_in AS eti",1,0);

							$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
							
							// get total hours in schedule blocks time in
							$final_total = $this->generate_total_hours($r->schedule_blocks_time_in_id, $get_total_hours, $r->employee_time_in_id);
							
							// update total hours and total hours required rest day
							$update_timein_logs = array(
									"tardiness_min"	=> 0,
									"undertime_min"	=> 0,
									"total_hours"	=> $final_total
							);
							$this->db->where($where_update);
							$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
								
							return ($q2) ? $q2->row() : FALSE ;
							exit;
						}
						
					}else if($check_type == "time in"){
						
						/* CHECK TIME IN START */
						$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id,$r->time_in);
						if($wst != FALSE){
							// new start time
							$nwst 					= date("Y-m-d {$wst}");
							$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
						}
						
						// insert time in value for rest day ============================================== >>> INSERT TIME IN VALUE FOR REST DAY
						$insert 	= FALSE;
						$get_diff 	= (strtotime($current_date) - strtotime($r->time_out)) / 60;

						// adding schedule block here
						if($this->type!='split'){
							
							if($min_log < $get_diff){
								$val 	= array(
											"emp_id"			=> $emp_id,
											"comp_id"			=> $comp_id,
											"date"				=> $date,
											"time_in"			=> $current_date,
											"work_schedule_id" 	=> -1,
											"source" 			=> $source,
											"location" 			=> $comp_add,
											"flag_on_leave" 	=> $ileave
										);
								$insert = $this->db->insert("employee_time_in",$val);	
							}
								
							if($insert){
								$w2 	= array(
											"a.payroll_cloud_id"	=> $emp_no,
											"eti.date"				=> $date,
											"eti.status" 			=> "Active"
										);
								$this->edb->where($w2);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$this->db->order_by("eti.time_in","DESC");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
				
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}else{
								$this->edb->where($where_update);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
				
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}
						}
						else{
							// split schedule here
							if($min_log < $get_diff){
								
								// indentify if time is split in the database
								$time_in = date('H:i:s');
								$split = $this->split_schedule_time($this->split_schedule_id, $time_in);
								 
								if(!$split){
									$val = array(
											"emp_id"			=> $emp_id,
											"comp_id"			=> $comp_id,
											"work_schedule_id" 	=> $this->work_schedule_id,
											"date"				=> $date
									);
									$insert = $this->db->insert("employee_time_in",$val);
									$timein_id = $this->db->insert_id();
								}else{
									$timein_id = $this->get_employee_timein_id($this->split_schedule_id, $current_date);
								}
								
								$val2 = array(
										"employee_time_in_id" => $timein_id,
										"date"=>$date,
										"time_in"=>$current_date,
										"emp_id"=>$emp_id,
										"comp_id"=>$comp_id,
										"split_schedule_id" => $this->split_schedule_id
								);
								$insert2 = $this->db->insert("schedule_blocks_time_in",$val2);
								
							}
								
							if($insert2){
								$w2 = array(
										"a.payroll_cloud_id"=>$emp_no,
										"sbti.date"=>$date
								);
								$this->edb->where($w2);
								$this->edb->select('sbti.time_in AS time_in,*');
								$this->edb->join("employee AS e","sbti.emp_id = e.emp_id","left");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","left");
								$this->db->order_by("sbti.time_in","DESC");
								$q2 = $this->edb->get("schedule_blocks_time_in AS sbti",1,0);
							
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}else{

								$this->edb->where($where_update);
								$this->edb->join("employee AS e","sbti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$q2 = $this->edb->get("schedule_blocks_time_in AS sbti",1,0);
							
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}
						}
					}
				}
				
				$flag_halfday = 0;
				
				/**
				 * SPLIT SCHEDULE MODIFICATIOIN HERE
				 * TAKE NOTE YOU aRe NOW ENTERING MY OWN PREMISES
				 * GOOD LUCK, HAVE FUN CODING
				 * 
				 * LOGS HERE IF SCHEDULE IS SPLIT, IF FIRST TIMEIN IN FIRST BLOCK IS ALREADY DONE THE REST OF PROCESS IS ALREADY HERE
				 */
				if($this->type == 'split'){
					$get_diff = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
					
					$arr = array(
						'emp_no' 				=> $emp_no,
						'current_date' 			=> $current_date, 
						'emp_id'				=> $emp_id, 
						'comp_id' 				=> $comp_id,
						'breaks' 				=> $number_of_breaks_per_day,
						'check_type' 			=> $check_type,
						'min_log' 				=> $min_log,
						'get_diff' 				=> $get_diff,
						'employee_time_in_id' 	=> $r->employee_time_in_id,
						'work_schedule_id' 		=> $work_schedule_id,
						'block_id' 				=> $r->schedule_blocks_time_in_id,
						'schedule_blocks_id' 	=> $this->schedule_blocks_id,
						'time_in' 				=> $r->time_in,	
						'time_out' 				=> $r->time_out,						
						'lunch_in' 				=> $r->lunch_in,
						'lunch_out' 			=> $r->lunch_out,
						'new_timein' 			=> $new_timein,
						'timein_id' 			=> $timein_id,
						'new_employee_timein' 	=> $new_employee_timein

					);			
					return $this->split_schedule_time_in($arr,$split_total_activate,"",$date,$source,$first_block_start_time);
				}
				
				
				// IF WHOLEDAY --- ==> lunch Out ===> lunch In ==> Time Out ===> IF CLOCKIN BEFORE THE NEXT SHIFT COME
				// check for double login
				
				$sc = array(
						'date'
					);
				$wc  = array(
						'date' 				=> $date,
						'emp_id' 			=> $emp_id, 
						'comp_id'			=> $comp_id, 
						'status'			=> 'Active'
				);
				$this->db->select($sc);
				$this->db->where($wc);
				$q = $this->db->get('employee_time_in');
				$rows = $q->num_rows();
				
				/// here trap if assumed
				$is_work = is_break_assumed($work_schedule_id);
				if($is_work && $check_type != "time in"){
					$check_type = "time out"; 
				}
				
				if(strtotime($current_date) <= strtotime($add_oneday_timein." -120 minutes") && $rows == 1){
					
					$hours_worked 		= $this->get_hours_worked($date, $emp_id, $work_schedule_id);
					// global where update data
					if($sync_employee_time_in_id !=""){
						$where_update 	= array(
										"eti.emp_id"				=> $emp_id,
										"eti.comp_id"				=> $comp_id,
										"eti.employee_time_in_id"	=> $sync_employee_time_in_id,
										"eti.status" 				=> "Active"
										);
					}
					else{
						$where_update 	= array(
										"eti.emp_id"				=> $emp_id,
										"eti.comp_id"				=> $comp_id,
										"eti.employee_time_in_id"	=> $r->employee_time_in_id,
										"eti.status"				=> "Active"
										);
					}
					
					if($check_type == "lunch out"){
						// update lunch out value ============ >>>> UPDATE LUNCH OUT VALUE
						if($this->type!="split"){
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							
							if($min_log < $get_diff){
								$update_val = array("lunch_out"=>$current_date);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);
							}
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
						
							return ($q2) ? $q2->row() : FALSE ;
						}
					}
					else if($check_type == "lunch in"){
						// update lunch in value =========== >>>> UPDATE LUNCH IN VALUE
						if($this->type!="split"){
							$get_diff 		= (strtotime($current_date) - strtotime($r->lunch_out)) / 60;
							$overbreak_min	= $this->overbreak_min($comp_id,$date,$emp_id,$work_schedule_id,$r->lunch_out);
							$late_min		= ($r) ? $r->late_min : 0;
							$tardiness_min	= $late_min + $overbreak_min;
							if($min_log < $get_diff){
								$update_val = array("lunch_in"=>$current_date,"overbreak_min" => $overbreak_min,"tardiness_min"=>$tardiness_min);
								$this->db->where($where_update);
								$update 	= $this->db->update("employee_time_in AS eti",$update_val);
							}
							
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
						}
					}
					else if($check_type == "time out"){
						
						// update time out value ======================== >>>> UPDATE TIME OUT VALUE
						$work_sched 					= get_workschedule_in_regular_sched($work_schedule_id,$date,$comp_id);
						$update 						= FALSE;
						$continue 						= false;
						$time_in_time 					= $r->time_in;
						$lunch_out_time_punch			= $r->lunch_out;
						$lunch_in_time_punch			= $r->lunch_in;
						$new_time_out_cur 				= $current_date;
						$new_time_out_cur_orig			= $current_date;
						$new_time_out_cur_orig_str		= strtotime($new_time_out_cur_orig);
						$tardiness_min					= $r->tardiness_min;
						$late_min						= $r->late_min;
						$overbreak_min					= $r->overbreak_min;
						$new_time_in_start_assumed		= $time_in_time;
						$payroll_sched_timein_orig_str 	= strtotime($payroll_sched_timein_orig);
						$time_in_time_str 				= strtotime($time_in_time);
						$payroll_sched_timein_str		= strtotime($payroll_sched_timein);
						$current_date_str				= strtotime($current_date);
						$total_hours_work_required		= 0;
						$undertime_break				= 0;
						$new_break						= $number_of_breaks_per_day;
						$new_undertime					= 0;
						$work_end_time					= "";
						$work_end_time_str				= 0;
						
						if($work_sched){
							//$work_sched 				= $work_sched->work_start_time;
							$total_hours_work_required 	= $work_sched->total_work_hours;
							$work_end_time 				= $date." ".$work_sched->work_end_time;
							$work_start_time 			= $date." ".$work_sched->work_start_time;
							$work_end_time_str			= strtotime($work_end_time);
							$work_start_time_str		= strtotime($work_start_time);
							if($work_end_time_str < $work_start_time_str){
								$work_end_time			= date("Y-m-d H:i:s",strtotime($work_end_time ."+ 1 day"));
							}
						}
						
						//*** SET A GLOBAL BOUNDARY FOR LUNCHOUT AND LUNCHIN ==> this work for regular schedule that capture break***/
						//**  get half of total hours worked required per schedule
						$total_hours_work_required_half 	=  $total_hours_work_required / 2;
						$total_hours_work_required_half 	=  ($total_hours_work_required_half >= 1) ? $total_hours_work_required_half : 0;
						$total_hours_work_required_half_min =  ($total_hours_work_required_half >= 1) ? ($total_hours_work_required_half*60) : 0;
						
						//** set assumed break without threshold for Init **/
						$lunch_out 		= date('Y-m-d H:i:s',strtotime($payroll_sched_timein_orig. " +{$total_hours_work_required_half_min} minutes"));
						$lunch_in		= date('Y-m-d H:i:s',strtotime($lunch_out. " +{$number_of_breaks_per_day} minutes"));
						$lunch_out_str 	= strtotime($lunch_out);
						$lunch_in_str 	= strtotime($lunch_in);
						$lunch_in_init 	= $lunch_in;
						
						if($is_work){
							$h 			= $is_work->assumed_breaks * 60;
							
							//** set assumed break without threshold for Init **/
							$lunch_out 		= date('Y-m-d H:i:s',strtotime($payroll_sched_timein_orig. " +{$h} minutes"));
							$lunch_in		= date('Y-m-d H:i:s',strtotime($lunch_out. " +{$number_of_breaks_per_day} minutes"));
							$lunch_out_str 	= strtotime($lunch_out);
							$lunch_in_str 	= strtotime($lunch_in);
							
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
									$lunch_out 			= date('Y-m-d H:i:s',strtotime($time_in_time. " +{$h} minutes"));
									$lunch_in			= date('Y-m-d H:i:s',strtotime($lunch_out. " +{$number_of_breaks_per_day} minutes"));
									
									$work_end_calculate = total_min_between($time_in_time, $payroll_sched_timein_orig);
									$work_end_time		=  date('Y-m-d H:i:s',strtotime($work_end_time. " +{$work_end_calculate} minutes"));
								}
							}
							
							//** if timeIn after the startTime And ThresHold but less than the (init) break **/
							if($threshold_min > 0){
								if(($lunch_out_str > $time_in_time_str) && ($payroll_sched_timein_str < $time_in_time_str)) {
									// LOut and LIn start Time plus ThresHold 
									$lunch_out 	= date('Y-m-d H:i:s',strtotime($time_in_time. " +{$h} minutes"));
									$lunch_in	= date('Y-m-d H:i:s',strtotime($lunch_out. " +{$number_of_breaks_per_day} minutes"));
									
									$work_end_time		=  date('Y-m-d H:i:s',strtotime($work_end_time. " +{$threshold_min} minutes"));
								}
							}
							
							//** if HALFDAY timeIn, thresHold dont effect anymore, Halfday time is set using the half of the totalHours (+) plus the startTime (as lunchOut assumed) plus break (as lunchIN assumed)**/
							//** timeIn between And after this assumed time will set that the employee is doing Halfday **/
							//** timeIn between
							if(($lunch_out_str <= $time_in_time_str) && ($lunch_in_str >= $time_in_time_str)) {
								// set the LOut and LIn to null and the timeiN start count on the assumed lunchIn since its assumed here to be break ==> amo ni amo gisabutan
								$new_time_in_start_assumed 	= $lunch_in;
								$lunch_out 					= null;
								$lunch_in 					= null;
							}
							//** timeIn after break
							if($lunch_in_str < $time_in_time_str) {
								// set the LOut and LIn to null and the timeiN
								$lunch_out 					= null;
								$lunch_in 					= null;
								$new_break 					= 0;
							}
							
							//*** if assumed breaks scenario regular schedule timeout ***//
							//**  init LOut & LIn
							if(($lunch_out != null) && ($lunch_in != null)){
								$lunch_out_new_str	= strtotime($lunch_out);
								$lunch_in_new_str	= strtotime($lunch_in);
							}else{
								$lunch_out_new_str	= $lunch_out_str;
								$lunch_in_new_str	= $lunch_in_str;
							}
							
							//** if timeout before break **//
							if($current_date_str < $lunch_out_new_str){
								$new_break 			= 0;
								$undertime_break	= $number_of_breaks_per_day;
								$new_time_out_cur	= $current_date;
								$lunch_out 			= null;
								$lunch_in 			= null;
							}
							//** if timeout between break **//
							if(($current_date_str >= $lunch_out_new_str) && ($current_date_str <= $lunch_in_new_str)){
								$new_break 			= 0;
								$new_time_out_cur	= $lunch_out;
								$undertime_break	= $number_of_breaks_per_day;
								$lunch_out 			= null;
								$lunch_in 			= null;
							}
							//** if timeout before the init lunchin this means he/she is doing halfday as we discus 2/17/17 5:00 PM **//
							if($new_time_out_cur_orig_str < $lunch_in_new_str){
								$new_break			= total_min_between($lunch_in_time_punch, $lunch_out_time_punch);
							}
							//** if timeout after break **//
							if($current_date_str > $lunch_in_new_str){
								$new_break 			= $number_of_breaks_per_day;
								//** timeIn after break
								if($lunch_in_str < $time_in_time_str) {
									$new_break 		= 0;
								}
								//** timeIn between break
								if($lunch_in_str >= $time_in_time_str && $lunch_out_str <= $time_in_time_str) {
									$new_break 		= 0;
								}
								$new_time_out_cur	= $current_date;
							}
							
							$total_hours_new 	= total_min_between($new_time_out_cur, $new_time_in_start_assumed);
							$total_hours_new_m	= $total_hours_new - $new_break;
							$total_hours_new_h	= $total_hours_new_m/60;
							
							$work_end_time_str	= strtotime($work_end_time);
							
							if($work_end_time_str > $current_date_str){
								$new_undertime	= total_min_between($work_end_time, $new_time_out_cur);
								$new_undertime	= $new_undertime - $undertime_break;
							}
							
							if($current_date <= $lunch_in){
								$continue 	= true;
							}
						}
						$get_diff = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;						
						if($this->type!="split"){
							if($min_log < $get_diff || $continue){
								$update_val = array("time_out"=>$new_time_out_cur_orig);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);
							}
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							$r2 = $q2->row();
						}
						else{
							$get_diff = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
							if($min_log < $get_diff){
								$update_val = array("time_out"=>$current_date);
								$this->db->where($where_update);
								$update = $this->db->update("schedule_blocks_time_in AS eti",$update_val);
							}
								
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
							$r2 = $q2->row();
						}
						
						$half_day 	= $this->if_half_day($r2->time_in, $work_schedule_id, $comp_id,$emp_no,$current_date,$r2->employee_time_in_id,$emp_id);
						
						//holiday now
						$holiday 	= $this->company_holiday($date, $comp_id);
						
						if($holiday){
							$get_diff 	= (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
							$arr 		= array(
										'emp_no' 				=> $emp_no,
										'current_date' 			=> $current_date,
										'break' 				=> $number_of_breaks_per_day,
										'date' 					=> $date,
										'emp_id' 				=> $emp_id,
										'comp_id' 				=> $comp_id,
										'check_type' 			=> $check_type,
										'min_log' 				=> $min_log,
										'get_diff' 				=> $get_diff,
										'employee_time_in_id' 	=> $r->employee_time_in_id,
										'work_schedule_id' 		=> $work_schedule_id,
										'time_in' 				=> $r->time_in,
										'time_out' 				=> $r->time_out,
										'lunch_in' 				=> $r->lunch_in,
										'lunch_out' 			=> $r->lunch_out,
										'new_timein' 			=> $new_timein,
										'timein_id' 			=> $timein_id
										);
						
							return $this->holiday_time_in($arr);
						}
						if($update){
							// update flag tardiness and undertime
							$flag_tu = 0;
							
							// check no. of timein row
							if($this->type != 'split'){
								$check_timein_row = $this->check_timein_row($emp_id, $comp_id, $current_date);
							}
							else{
								$check_timein_row = $this->split_check_timein_row($emp_id, $comp_id, $current_date);  // xx
							}
							
							if($check_timein_row){
								// update tardiness
								$update_tardiness 	= 0;
								
								// update undertime
								$update_undertime 	= 0;
								
								// update total hours
								$update_total_hours = 0;
							}
							else{								
								// update tardiness
								$update_tardiness 	= $this->get_tardiness_import($emp_id, $comp_id, $r2->time_in, $r2->lunch_out, $r2->lunch_in,$work_schedule_id,$number_of_breaks_per_day,$date,$half_day); //xxx
		
								// update undertime
								$update_undertime 	= $this->get_undertime_import($emp_id, $comp_id, $r2->time_in, $r2->time_out, $r2->lunch_out, $r2->lunch_in,$work_schedule_id,$number_of_breaks_per_day,$date);
							
								// update total hours 
								$hours_worked 		= $this->get_hours_worked($date, $emp_id, $work_schedule_id);
								$update_total_hours = $this->get_tot_hours_complete_logs($emp_id, $comp_id, $r2->time_in, $r2->lunch_out, $r2->lunch_in, $r2->time_out, $hours_worked,$work_schedule_id,$number_of_breaks_per_day,$date);
								
								// check tardiness value
								if(($r2->lunch_out == null && $r2->lunch_in == null) && !is_break_assumed($work_schedule_id)){
        							$update_tardiness = 0;
        						}
							}
							
							// update total hours required
							// $update_total_hours_required = $this->get_tot_hours_limit($emp_id, $comp_id, $r2->time_in, $r2->lunch_out, $r2->lunch_in, $r2->time_out,$work_schedule_id,$number_of_breaks_per_day);
							
							//*** TO CALCULATE FOR UNDERTIME ***//
							//** FIND WORK END if timeIn between the startTime And the thresHold **/
							if($threshold_min > 0){
								if(($payroll_sched_timein_orig_str <= $time_in_time_str) && ($payroll_sched_timein_str >= $time_in_time_str)) {
									// update workend start										
									$work_end_calculate = total_min_between($time_in_time, $payroll_sched_timein_orig);
									$work_end_time		=  date('Y-m-d H:i:s',strtotime($work_end_time. " +{$work_end_calculate} minutes"));
								}
							}
							
							//** FIND WORK END  if timeIn after the startTime And ThresHold but less than the (init) break **/
							if($threshold_min > 0){
								if(($lunch_out_str > $time_in_time_str) && ($payroll_sched_timein_str < $time_in_time_str)) {
									// update workend start
									$work_end_time		=  date('Y-m-d H:i:s',strtotime($work_end_time. " +{$threshold_min} minutes"));
									
								}
							}
							//** UNDER TIME **//
							$work_end_time_str 	= strtotime($work_end_time);
							
							if($work_end_time_str > $current_date_str){
								// if early clock
								if($lunch_in_time_punch == null || $lunch_out_time_punch == null){
									
									// time out during assumed break
									if($current_date_str < $lunch_in_str && $current_date_str >= $lunch_out_str){
										$new_undertime	= total_min_between($work_end_time, $lunch_in_init);
									}
									else if($current_date_str <= $lunch_out_str){
										$new_undertime	= total_min_between($work_end_time, $new_time_out_cur);
										$new_undertime	= $new_undertime - $number_of_breaks_per_day;
									}
									else{
										$new_undertime	= total_min_between($work_end_time, $new_time_out_cur);
									}
								}else{
									$new_undertime	= total_min_between($work_end_time, $new_time_out_cur);
									$new_undertime	= $new_undertime - $undertime_break;
								}
								
							}
							
							//** if timeout before the init lunchin this means he/she is doing halfday as we discus 2/17/17 5:00 PM **//
							if($new_time_out_cur_orig_str < $lunch_in_str){
								$new_break			= total_min_between($lunch_in_time_punch, $lunch_out_time_punch);
							}
							//** UPDATE TOTAL HOURS WORK**//
							$update_total_hours_required = total_min_between($new_time_out_cur, $time_in_time);
							$update_total_hours_required = $update_total_hours_required - ($new_break + $overbreak_min);
							
							//** TO HOURS **/
							$update_total_hours_required = $update_total_hours_required/60;
							
							// update employee time in logs
							$update_timein_logs = array(
												"undertime_min"				=> $new_undertime,
												"tardiness_min" 			=> $tardiness_min,
												"total_hours"				=> $hours_worked,
												"total_hours_required"		=> $update_total_hours_required,
												"flag_tardiness_undertime"	=> $flag_tu
												);
							
							//**** IF ASSUME BREAK OVERWIRTE EVERYTHING****//
							if($is_work){
								$update_timein_logs['lunch_in'] 			= $lunch_in;
								$update_timein_logs['lunch_out'] 			= $lunch_out;
								$update_timein_logs["total_hours_required"] = $total_hours_new_h;
								$update_timein_logs['absent_min'] 			= 0;
								$update_timein_logs['tardiness_min'] 		= $tardiness_min;
								$update_timein_logs['undertime_min'] 		= $new_undertime;
							}
							
							// ***** NEW COMPUTATION FOR ATTENDANCE SETTINGS
							$att = is_attendance_active($comp_id);
								
							if($att){
								if($update_total_hours_required <= $att){
									
									if($r2->time_in >= $r2->lunch_out){
										$update_timein_logs['lunch_out'] 	= null;
										$update_timein_logs['lunch_in'] 	= null;
									}
									elseif($current_date <= $r2->lunch_in){
										$update_timein_logs['lunch_out'] 	= null;
										$update_timein_logs['lunch_in'] 	= null;
									}
										
									$half_day_h = ($hours_worked / 2) * 60;
									if($late_min < $half_day_h){
										$update_timein_logs['late_min'] 		= $tardiness_min;
										$update_timein_logs['tardiness_min'] 	= $tardiness_min;
										$update_timein_logs['undertime_min'] 	= 0;
										$update_timein_logs['absent_min'] 		= (($hours_worked - $update_total_hours_required) * 60) - $tardiness_min;
									}
									else{
										$update_timein_logs['late_min'] 		= 0;
										$update_timein_logs['tardiness_min'] 	= 0;
										$update_timein_logs['undertime_min'] 	= $new_undertime;
										$update_timein_logs['absent_min'] 		= (($hours_worked - $update_total_hours_required) * 60) - $new_undertime;
									}
									$update_timein_logs['total_hours_required'] 	= $update_total_hours_required;
								}
							}
							
							//**** UPDATE HERE THEN END ***/
							
							$this->db->where($where_update);
							$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
						}
						
						return ($q2) ? $q2->row() : FALSE ;
						
					}else if($check_type == "time in"){
						$get_diff 	= (strtotime($current_date) - strtotime($r->time_out)) / 60;
						if($min_log < $get_diff){
							
							// $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
							// $get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
							
							$date_insert = array(
									"source"					=> 'dashboard',
									"comp_id"					=> $comp_id,
									"emp_id"					=> $emp_id,
									"date"						=> $date,
									"time_in"					=> $current_date,
									"undertime_min"				=> 0,
									"tardiness_min" 			=> 0,
									"late_min" 					=> 0,
									"overbreak_min" 			=> 0,
									"work_schedule_id" 			=> "-2",
									"source" 					=> $source,
									"location" 					=> $comp_add,
									"flag_regular_or_excess" 	=> "excess",
							);
							
							$add_logs = $this->db->insert('employee_time_in', $date_insert);
							
							if($add_logs){
								$w2 	= array(
										"a.payroll_cloud_id"	=> $emp_no,
										"eti.date"				=> $date,
										"eti.status" 			=> "Active"
								);
								$this->edb->where($w2);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$this->db->order_by("eti.time_in","DESC");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}else{
								$this->edb->where($where_update);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}
						}
					}
				}
				else if($check_type == "time in" && $rows == 0){
					
					$timeIn = $this->only_for_timeIn_reg_sched($emp_id,$emp_no,$work_schedule_id,$comp_id,$current_date,$date,$time_in,$source,$comp_add,$ileave);
					return $timeIn;
				}
				//*** for new timeIn under the same shift (double timeIn) OR clockIn after the prev shift (miss timeOut)***//
				else{
					$where_update = array(
							"eti.emp_id"				=> $emp_id,
							"eti.comp_id"				=> $comp_id,
							"eti.employee_time_in_id"	=> $r->employee_time_in_id,
							"eti.status"				=> "Active"
					);
					if($check_type == "time out" || $check_type == 'lunch out' || $check_type == "lunch in"){
						
						$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
						if($min_log < $get_diff){
							$total_h_r	= (total_min_between($current_date,$r->time_in) / 60);
							$update_val = array("time_out"=>$current_date,"total_hours_required"=>$total_h_r,"total_hours"=>$total_h_r);
							$this->db->where($where_update);
							$update = $this->db->update("employee_time_in AS eti",$update_val);
						}
					
						$this->edb->where($where_update);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
					
						return ($q2) ? $q2->row() : FALSE ;
						exit;
					
					}else if($check_type == "time in"){
						$get_diff 	= (strtotime($current_date) - strtotime($r->time_out)) / 60;
						if($min_log < $get_diff){
							
							// $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
							// $get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
							
							$date_insert = array(
									"source"					=> 'dashboard',
									"comp_id"					=> $comp_id,
									"emp_id"					=> $emp_id,
									"date"						=> $date,
									"time_in"					=> $current_date,
									"undertime_min"				=> 0,
									"tardiness_min" 			=> 0,
									"late_min" 					=> 0,
									"overbreak_min" 			=> 0,
									"work_schedule_id" 			=> "-2",
									"source" 					=> $source,
									"location" 					=> $comp_add,
									"flag_regular_or_excess" 	=> "excess",
							);
							
							$add_logs = $this->db->insert('employee_time_in', $date_insert);
							if($insert){
								$w2 	= array(
										"a.payroll_cloud_id"	=> $emp_no,
										"eti.date"				=> $date,
										"eti.status" 			=> "Active"
								);
								$this->edb->where($w2);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$this->db->order_by("eti.time_in","DESC");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
									
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}else{
								$this->edb->where($where_update);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}
						}
						else{
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
						
							return ($q2) ? $q2->row() : FALSE ;
							exit;
						}
					}
				}
			}
		}
	}
	
	public function only_for_timeIn_reg_sched($emp_id,$emp_no,$work_schedule_id,$comp_id,$current_date,$date,$time_in,$source="kiosk",$comp_add,$ileave,$hours_worked = 0){

		// CHECK TIME IN START /
		$day = date("l", strtotime($date));
		$nwst	= "";
		$wst 	= $this->check_workday_settings_start_time($day,$work_schedule_id,$comp_id,$current_date,$time_in);
		
		if($wst != FALSE){
			// new start time
			$nwst 					= $date." ".$wst;
			$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
		}
			
		//$late_min 	= $this->late_min($comp_id, $date, $emp_id, $this->work_schedule_id);
		
		$late_min	= total_min_between($current_date, $nwst);
		
		/** BARACK CODE TRESS PASS HERE >> for timein**/
		// NEW RULE /
		// compress schedule with break,
		// total hours req / 2 + break - when employee is doing halfday,
		// e.g sched 7:00 am - 4:00 pm
		//	   break 60 mins
		//	   total required hours worked 8
		// senario halfday
		// if time in 7:00 am & timeout 11:30 am	- hours worked - 4.0 h - end count from 11:00 am
		// if time in 11:45 am & timeout 4:00 am	- hours worked - 4.0 h - start count from 12:00 pm
		
			
		$threshold			= 0;
		$hours_worked_req	= 0;
		$grace_period		= 0;
		$break_min			= 0;
		$is_work 			= is_break_assumed($work_schedule_id);
		$work_sched 		= get_workschedule_in_regular_sched($work_schedule_id,$date,$comp_id);
		$tardiness_set 		= $this->tardiness_settings($emp_id, $comp_id);
			
		// adjust for threshold
		if($work_sched){
			$threshold 			= ($work_sched->latest_time_in_allowed) ? $work_sched->latest_time_in_allowed : 0;
			$hours_worked_req 	= ($work_sched->total_work_hours) ? $work_sched->total_work_hours : 0;
			$break_min	 		= ($work_sched->break_in_min) ? $work_sched->break_in_min : 0;
		}
			
		// adjust for tardiness settings grace period
		if($tardiness_set){
			$grace_period = $tardiness_set;
		}
			
		// start time is blank or empty late is zerp
		
		if($nwst == ""){
			$late_min	= 0;
		}
		else{
			$start_time_org					= $nwst;
			$half_total_hours				= $hours_worked_req/2;
			$half_total_min					= $half_total_hours * 60;
			$start_time_org_str				= strtotime($nwst);
			$current_date_str				= strtotime($current_date);
			$start_time_wt_threshold 		= strtotime($nwst."+ ".$threshold." minutes");
			$start_time_wt_threshold_date 	= date("Y-m-d H:i:s",$start_time_wt_threshold);
			$predict_lunch_out_for_halfday	= strtotime($start_time_org."+ ".$half_total_min." minutes");
		
			if($threshold == 0){
				// grace period effect
				$late_min = $late_min - $grace_period;
			}
			else{
				if(($current_date_str > $start_time_org_str) && ($current_date_str <= $start_time_wt_threshold)){
					$start_time_org	= $current_date;
					$late_min		= 0;
				}
				else if($current_date_str < $start_time_org_str){
					$start_time_org	= $nwst;
					$late_min		= 0;
				}
				else if($current_date_str > $start_time_wt_threshold){
					$late_min 		= total_min_between($current_date,$start_time_wt_threshold_date);
		
					if($predict_lunch_out_for_halfday > $current_date_str){
						$start_time_org	= $start_time_wt_threshold_date;
					}else{
						$start_time_org	= $nwst;
					}
					// grace period effect
					$late_min = $late_min - $grace_period;
				}
			}
			
		
			// assume break here
			if($is_work){
				$half_total_min	= $is_work->assumed_breaks * 60;
				// assume break cant affect timein
			}
			$half_total_min = number_format($half_total_min,0);
			// predict lunchout
			$predict_lunch_out_for_halfday	= strtotime($start_time_org."+ ".$half_total_min." minutes");
			$predict_lunch_out_for_halfday2	= date("Y-m-d H:i:s",$predict_lunch_out_for_halfday);
		
			// predict lunchin
			$predict_lunch_in_for_halfday	= strtotime($predict_lunch_out_for_halfday2."+ ".$break_min." minutes");
			$predict_lunch_in_for_halfday2	= date("Y-m-d H:i:s",$predict_lunch_in_for_halfday);
				
			if(($predict_lunch_out_for_halfday <= $current_date_str) && ($predict_lunch_in_for_halfday >= $current_date_str)){
				$late_min = $half_total_min;
					
				// grace period effect
				$late_min = $late_min;
			}
				
			if($predict_lunch_in_for_halfday < $current_date_str){
				$late_min 	= total_min_between($current_date,$nwst);
				$late_min	= $late_min - $break_min;
		
				// grace period effect
				$late_min = $late_min - $grace_period;
			}

			
			$late_min = ($late_min > 0) ? $late_min : 0;
			
			$late_min = ($late_min > ($hours_worked_req * 60)) ? ($hours_worked_req * 60) : $late_min;
			
		}
		
		//* END BARACK CODE TRESS PASS HERE /
		// insert time in log
		
		$val	= array(
				"emp_id"			=> $emp_id,
				"comp_id"			=> $comp_id,
				"date"				=> $date,
				"work_schedule_id" 	=> $work_schedule_id,
				"time_in"			=> $current_date,
				"late_min" 			=> $late_min,
				"tardiness_min"		=> $late_min,
				"location" 			=> $comp_add,
				"flag_on_leave" 	=> $ileave,
				"total_hours" 		=> $hours_worked,
				"source"		 	=> $source,
		);
		
		$insert = $this->db->insert("employee_time_in",$val);
			
		if($insert){
			$w2 = array(
					"a.payroll_cloud_id"	=> $emp_no,
					"eti.date"				=> $date,
					"eti.status" 			=> "Active"
			);
		
			$this->edb->where($w2);
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$q2 = $this->edb->get("employee_time_in AS eti",1,0);
		
			return ($q2) ? $q2->row() : FALSE ;
		}
		
	}
	
	public function calculate_attendance($company_id,$time_in,$time_out){
	
		$hours = is_attendance_active($company_id);
		if($hours){
			$h 		= $hours * 60;
			$calc 	= $this->total_hours_worked($time_out, $time_in);
	
			if($calc <= $h){
				return true;
			}
		}
	
		return false;
	}
	
	public function sharingan($msg1,$msg2){
		echo json_encode(array($msg1 => $msg2));
		return false;
	}
	
	
	public function holiday_time_in($arr = array()){	
		// Set data here
		$date = $arr['date'];
		$emp_no = $arr['emp_no'];
		$current_date = $arr['current_date'];
		$emp_id = $arr['emp_id'];
		$comp_id = $arr['comp_id'];
		$check_type = $arr['check_type'];
		$min_log = $arr['min_log'];
		$get_diff = $arr['get_diff'];
		$employee_time_in_id = $arr['employee_time_in_id'];
		$work_schedule_id = $arr['work_schedule_id'];
		$time_in = $arr['time_in'];
		$time_out = $arr['time_out'];
		$lunch_in = $arr['lunch_in'];
		$lunch_out = $arr['lunch_out'];
		#$new_timein = $arr['new_timein'];
		#$timein_id = $arr['timein_id'];
		$break = $arr['break'];	
	
		// global where update data
		$where_update = array(
				"eti.emp_id"=>$emp_id,
				"eti.comp_id"=>$comp_id,
				"eti.employee_time_in_id"=>$employee_time_in_id
		);
	
		$day = date('l',strtotime($date));
		$hour_worked = $this->get_hours_worked($day, $emp_id,$work_schedule_id);
	
		
		$this->edb->where($where_update);
		$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$this->db->order_by("eti.time_in","DESC");
		$q2 = $this->edb->get("employee_time_in AS eti",1,0);
		$r = $q2->row();
			

			$tardiness = 0;
			if($r->lunch_in !="" && $r->lunch_out != ""){
				$break_min = $this->total_hours_worked($r->lunch_in, $r->lunch_out);
				
				if($break_min > $break){
					$break_tard= $break_min - $break;						
					$tardiness = $break_tard;
					$break = $break_min;
				}
			}
			
			
			$hours = $this->total_hours_worked($r->time_out, $r->time_in);
			$get_h = $this->convert_to_hours($hours);
			
			
			
			$update_timein_logs = array(
					"tardiness_min"=>0,
					"undertime_min"=>0,
					"total_hours"=>$get_h,
					"late_min" => 0,
					"absent_min" => 0,
					"total_hours_required"=>$get_h
			);
			$this->db->where($where_update);
			$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
	
			
		
		return ($q2) ? $q2->row() : FALSE ;			
		
	}
	
	public function split_schedule_time_in($arr = array(),$split_total_activate = false,$sync_date="",$date ="",$source="kiosk",$first_block_start_time=""){
		// Set data here
		$emp_no 				= $arr['emp_no'];
		$current_date 			= $arr['current_date'];
		$emp_id 				= $arr['emp_id'];
		$comp_id 				= $arr['comp_id'];
		$check_type 			= $arr['check_type'];
		$min_log 				= $arr['min_log'];
		$breaks 				= $arr['breaks'];
		$get_diff 				= $arr['get_diff'];
		$employee_time_in_id 	= $arr['employee_time_in_id'];
		$work_schedule_id 		= $arr['work_schedule_id'];
		$block_id 				= $arr['block_id'];
		$schedule_blocks_id 	= $arr['schedule_blocks_id'];
		$time_in 				= $arr['time_in'];
		$time_out 				= $arr['time_out'];
		$lunch_in 				= $arr['lunch_in'];
		$lunch_out 				= $arr['lunch_out'];
		$new_timein 			= $arr['new_timein'];
		$timein_id 				= $arr['timein_id'];
		$new_employee_timein 	= $arr['new_employee_timein'];
		$break 					= $arr['breaks'];
		
		$split = $this->get_blocks_list($schedule_blocks_id);
		
		$block_tardy = 0;
		
		$s1 	= array("tardiness_min");
		$w1 	= array("schedule_blocks_time_in_id" => $block_id,"status" => "Active","comp_id" => $comp_id);
		$this->db->select($s1);
		$this->db->where($w1);
		$q1	= $this->db->get("schedule_blocks_time_in");
		$r1	= $q1->row();
		
		if($r1){
			$block_tardy = $r1->tardiness_min;
		}
		
		// global where update data
		$where_update 	= array(
						"eti.emp_id"						=> $emp_id,
						"eti.comp_id"						=> $comp_id,
						"eti.employee_time_in_id"			=> $employee_time_in_id,
						"eti.schedule_blocks_time_in_id" 	=> $block_id
						);
		
		if($check_type == "lunch out"){
			// update lunch out value ================================================================ >>>> UPDATE LUNCH OUT VALUE
			$get_diff = (strtotime($current_date) - strtotime($time_in)) / 60;
			if($min_log < $get_diff){
				$update_val = array("lunch_out"=>$current_date);
				$this->db->where($where_update);
				$update = $this->db->update("schedule_blocks_time_in AS eti",$update_val);
			}
			$this->edb->where($where_update);
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$q2 = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
				
			return ($q2) ? $q2->row() : FALSE ;
		}
		else if($check_type == "lunch in"){
			
			$overbreak_min = $this->overbreak_min($comp_id,$date,$emp_id,$work_schedule_id,$lunch_out,$emp_no);
			// update lunch in value ================================================================ >>>> UPDATE LUNCH IN VALUE
			$get_diff = (strtotime($current_date) - strtotime($lunch_out)) / 60;
			if($min_log < $get_diff){
				$block_tardy	= $block_tardy + $overbreak_min;
				$update_val 	= array(
								"lunch_in"		=> $current_date,
								"overbreak_min" => $overbreak_min,
								"tardiness_min" => $block_tardy
								);
				$this->db->where($where_update);
				$update = $this->db->update("schedule_blocks_time_in AS eti",$update_val);
			}
				
			$this->edb->where($where_update);
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$q2 = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
			
			$this->up_date_current_time_in($employee_time_in_id,$comp_id);
			
			return ($q2) ? $q2->row() : FALSE ;
		}
		else if($check_type == "time out"){
			$undertime 		= 0;
			$total_hours 	= 0;
			$update 		= false;
			$get_diff 		= (strtotime($current_date) - strtotime($lunch_in)) / 60;
			if($min_log < $get_diff){
				$update_val = array("time_out"=>$current_date);
				$this->db->where($where_update);
				$update = $this->db->update("schedule_blocks_time_in AS eti",$update_val);
			}
			$arrx = array(
					'time_in'	=> 'eti.time_in',
					'lunch_out' => 'eti.lunch_out',
					'lunch_in' 	=> 'eti.lunch_in',
					'time_out' 	=> 'eti.time_out'
			);
			$this->edb->select($arrx);
			
			$this->edb->where($where_update);
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->db->order_by("eti.time_in","DESC");
			$q2 = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
			$r 	= $q2->row();
			
			$date = date('Y-m-d',strtotime($current_date));
			
			#holiday now
			
			$holiday = $this->company_holiday($date, $comp_id);			
			if($holiday){
				
				$get_diff = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
				
				if($r->lunch_in !="" && $r->lunch_out != ""){
					$break_min = $this->total_hours_worked($r->lunch_in, $r->lunch_out);
				
					if($break_min > $break){
						$break_tard	= $break_min - $break;
						$tardiness 	= $break_tard;
						$break 		= $break_min;
					}
				}
					
				$hours = $this->total_hours_worked($r->time_out, $r->time_in);
				$get_h = $this->convert_to_hours($hours - $break);
					
				$update_timein_logs = array(
						"tardiness_min"			=> 0,
						"late_min"				=> 0,
						"undertime_min"			=> 0,
						"total_hours"			=> $split->total_hours_work_per_block,
						"total_hours_required"	=> $get_h
				);
				
				$this->db->where($where_update);
				$sql_update_timein_logs = $this->db->update("schedule_blocks_time_in AS eti",$update_timein_logs);
			
			}else{
				if($update){
					$tardiness 		= $this->get_tardiness($block_id,$split);
					$undertime 		= $this->get_undertime($block_id,$split);
					$total_hours 	= $this->get_total_hours($block_id);
					$total_req 		= $this->get_total_hours_req($block_id);
					
					$update_timein_logs = array(
							"tardiness_min"			=> $tardiness,
							"undertime_min"			=> $undertime,
							"total_hours"			=> $split->total_hours_work_per_block,
							"total_hours_required"	=> $total_hours
					);
					
					$this->db->where($where_update);
					$sql_update_timein_logs = $this->db->update("schedule_blocks_time_in AS eti",$update_timein_logs);
					
				}
			}
			
			$this->up_date_current_time_in($employee_time_in_id,$comp_id);
			
			return ($q2) ? $q2->row() : FALSE ;
		}
		else{
			// FOR TIMEIN A NEW SCHEDULE BLOCK AFTER THE FIRST BLOCK
			$slist = $this->get_blocks_list($schedule_blocks_id);
			$late_min 						= 0;
			$date_start						= $date;
			$first_block_trap				= true;
			$first_block_start_time_str		= strtotime($first_block_start_time);
			
			if($slist){
				$start_time		= $slist->start_time;
				$start_time_str = strtotime($start_time);
				if($first_block_start_time_str > $start_time_str){
					$date_start = date("Y-m-d",strtotime($date."+ 1 day"));
				}
				$start_time_date		= $date_start." ".$start_time;
				$start_time_date_str 	= strtotime($start_time_date);
				$current_date_str 		= strtotime($current_date);
				
				if($start_time_str < $current_date_str){
					$total      = strtotime($current_date) - strtotime($start_time);
					$hours      = floor($total / 60 / 60);
					$minutes    = floor(($total - ($hours * 60 * 60)) / 60);
					$ret 		= ($hours * 60) + $minutes;
					$late_min	= ($ret < 0) ? '0' : $ret;
				}
			}
			$late_min = ($late_min > 0) ? $late_min : 0;
			$timein_id = $employee_time_in_id;
			
			// insert time in log				
			if($new_employee_timein){
				$first_block_trap = false;
				$val 				= array(
									"emp_id"			=> $emp_id,
									"comp_id"			=> $comp_id,
									"date"				=> $date,
									"late_min" 			=> $late_min,
									"tardiness_min" 	=> $late_min,
									"time_in" 			=> $current_date,
									"total_hours"		=> $split_info['total_hour_block_sched'],
									"source"			=> $source,
									"work_schedule_id" 	=> $this->work_schedule_id
									);
				$insert 			= $this->db->insert("employee_time_in",$val);
				$timein_id 			= $this->db->insert_id();
			}
 
			$val2 		= array(
						"employee_time_in_id" 	=> $timein_id,
						"date"					=> $date,
						"time_in"				=> $current_date,
						"emp_id"				=> $emp_id,
						"comp_id"				=> $comp_id,
						"late_min" 				=> $late_min,
						"tardiness_min"			=> $late_min,
						"total_hours"			=> $split->total_hours_work_per_block,
						"schedule_blocks_id" 	=> $this->schedule_blocks_id,
						"source"				=> $source
						);
			$insert2 	= $this->db->insert("schedule_blocks_time_in",$val2);
			
			$this->up_date_current_time_in($timein_id,$comp_id);
			
			if($insert2){
				$w2 = array(
						"a.payroll_cloud_id" => $emp_no,
						"eti.date"			 => $date
				);
				$this->edb->where($w2);
				$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
				$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
				$q2 = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
					
				return ($q2) ? $q2->row() : FALSE ;
			}
		}
	}
	
	public function employee_schedule_blocks_ids_h($timein_id,$comp_id){
		$s1 = array("emp_id","work_schedule_id","date");
		$w 	= array("employee_time_in_id" => $timein_id,"status" => "Active","comp_id" => $comp_id);
		
		$row_array				= array();
		$absent_block_total_h  	= 0;
		$current_date			= "";
		$work_schedule_id		= "";
		$emp_id					= "";
		$data					= array();
		$this->db->select($s1);
		$this->db->where($w);
		$q2	= $this->db->get("employee_time_in");
		$r2	= $q2->row();
		if($r2){
			$current_date 		= $r2->date;
			$work_schedule_id 	= $r2->work_schedule_id;
			$emp_id 			= $r2->emp_id;
		}
		
		$arrSelect 	= array(
				"sb.schedule_blocks_id",
				"total_hours_work_per_block",
		);
		$w_date = array(
				"es.valid_from <="		=>	$current_date,
				"es.until >="			=>	$current_date
		);
		$w_ws 	= array(
				"em.work_schedule_id"	=> $work_schedule_id,
				"em.company_id"			=> $comp_id,
				"em.emp_id" 			=> $emp_id
		);
		$this->db->select($arrSelect);
		$this->db->where($w_date);
		$this->db->where($w_ws);
		$this->db->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
		$this->db->join("schedule_blocks AS sb","sb.schedule_blocks_id = em.schedule_blocks_id","LEFT");
		$q_ws = $this->db->get("employee_sched_block AS em");
		$r_ws = $q_ws->result();
		if($r_ws){
			foreach($r_ws as $row){
				$wd = array(
						"schedule_blocks_id"			=> $row->schedule_blocks_id,
						"total_hours_work_per_block"	=> $row->total_hours_work_per_block
				);
				array_push($row_array,$wd);
				$absent_block_total_h 					= $absent_block_total_h + $row->total_hours_work_per_block;
			}
		}
		$data['row_array'] 				= ($row_array) ? $row_array : false;
		$data['absent_block_total_h'] 	= $absent_block_total_h;
		
		return $data;
	}
	public function up_date_current_time_in($timein_id,$comp_id){
		
		$total_tardy 			= 0;
		$total_late 			= 0;
		$total_over_break 		= 0;
		$total_under_time 		= 0;
		$total_total_hours 		= 0;
		$total_total_hours_req 	= 0;
		
		$tardy	 				= 0;
		$late_min 				= 0;
		$undertime 				= 0;
		$overbreak 				= 0;
		$total_hours 			= 0;
		$total_req 				= 0;
		$absent_min				= 0;
		$time_out				= "";
		$time_in				= "";
		
		$data_blocks 			= $this->employee_schedule_blocks_ids_h($timein_id,$comp_id);
		
		$row_array				= $data_blocks['row_array'];
		$absent_block_total_h 	= $data_blocks['absent_block_total_h'];
		
		$w 	= array("employee_time_in_id" => $timein_id,"status" => "Active","comp_id" => $comp_id);
		$s 	= array("schedule_blocks_id","late_min","tardiness_min","overbreak_min","undertime_min","total_hours","total_hours_required","time_in","time_out","absent_min");
		$this->db->select($s);
		$this->db->where($w);
		$this->db->order_by("time_in","ASC");
		$q	= $this->db->get("schedule_blocks_time_in");
		$r1	= $q->result();
		
		if($r1){
			$first 	= reset($r1);
			$last 	= end($r1);
			if($first){
				$time_in = $first->time_in;
			}
			if($last){
				$time_out = $last->time_out;
			}
			
			foreach ($r1 AS $r){
				$tardy					= ($r->tardiness_min) ? $r->tardiness_min : 0;
				$late_min				= ($r->late_min) ? $r->late_min : 0;
				$overbreak				= ($r->overbreak_min) ? $r->overbreak_min : 0;
				$undertime				= ($r->undertime_min) ? $r->undertime_min : 0;
				$total_hours			= ($r->total_hours) ? $r->total_hours : 0;
				$total_req				= ($r->total_hours_required) ? $r->total_hours_required : 0;
				
				$total_late				= $total_late + $late_min;
				$total_tardy			= $total_tardy + $tardy;
				$total_under_time		= $total_under_time + $undertime;
				$total_over_break		= $total_over_break + $overbreak;
				$total_total_hours_req	= $total_total_hours_req + $total_req;
				
				$if_reghrs_custom 		= in_array_custom($r->schedule_blocks_id,$row_array);
				if($if_reghrs_custom){
					$absent_block_total_h = $absent_block_total_h - $if_reghrs_custom->total_hours_work_per_block;
				}
			}
		}
		$absent_block_total_h = $absent_block_total_h * 60;
		$update_timein_logs = array(
							"time_in"				=> $time_in,
							"time_out"				=> $time_out,
							"tardiness_min"			=> $total_tardy,
							"late_min"				=> $total_late,
							"overbreak_min"			=> $total_over_break,
							"undertime_min"			=> $total_under_time,
							"total_hours_required"	=> $total_total_hours_req,
							"absent_min"			=> $absent_block_total_h
							);
		
		$this->db->where($w);
		$update = $this->db->update("employee_time_in AS eti",$update_timein_logs);
	}
	
	/**
	 * calculate the split schedule block of the day
	 * @param unknown $employee_timein_id
	 * @return boolean
	 */
	public function insert_into_employee_time_in($employee_timein_id,$current_date,$workschedule_id = 0,$last_block = false){
		
		$this->edb->where("employee_time_in_id",$employee_timein_id);
		$query = $this->edb->get("schedule_blocks_time_in");
		$result = $query->result();
		$total_hours = 0;
		$total_hours_req = 0;
		$tardiness = 0;
		$undertime = 0;
		$absentmin = 0;
		$late_min = 0;
		$overbreak_min = 0;
		
		if($result){
			foreach($result as $row):
				$total_hours = $total_hours + $row->total_hours;
				$total_hours_req = $total_hours_req + $row->total_hours_required;
				$tardiness = $tardiness + $row->tardiness_min;
				$undertime = $undertime + $row->undertime_min; 
				$absentmin = $absentmin + $row->absent_min;
				$late_min = $late_min + $row->late_min;
				$overbreak_min = $overbreak_min + $row->overbreak_min;
			endforeach;
		}
		
		$update_val = array(				
				"total_hours" => $total_hours,
				"total_hours_required" => $total_hours_req,
				"tardiness_min" => $tardiness,
				"undertime_min" => $undertime,
				"absent_min" => $absentmin,
				"late_min" => $late_min,
				"overbreak_min" => $overbreak_min
		);
		
		if($last_block){
			$update_val["time_out"] = $current_date;
		}
		
		$this->db->where("employee_time_in_id",$employee_timein_id);
		$update = $this->db->update("employee_time_in",$update_val);
		
	}
	
	public function update_employee_timein_data($emp_timein_id){
			
		$update_val = array("time_out"=>$current_date);
		$this->db->where($where_update);
		$update = $this->db->update("schedule_blocks_time_in AS eti",$update_val);
	}
	
	/**
	 * calculate total hours of each schedule blocks
	 * @param unknown $id
	 */
	public function generate_total_hours($id,$totalhours,$employee_time_in_id){
		$total = "";
		
		$data = array(
				'total_hours' => $totalhours
		);
		$this->db->where("schedule_blocks_time_in_id",$id);
		$q = $this->db->update("schedule_blocks_time_in",$data);
		
		$this->edb->where("employee_time_in_id",$employee_time_in_id);
		$query = $this->edb->get("schedule_blocks_time_in");
		$result = $query->result();
		
		foreach($result as $row):
			$total += $row->total_hours;
		endforeach;
		
		return $total;
	}
	
	
	/**
	 * Check Rest Day
	 * @param unknown_type $workday
	 * @param unknown_type $work_schedule_id
	 * @param unknown_type $comp_id
	 */
	public function check_rest_day($workday,$work_schedule_id,$comp_id){
		$w = array(
			"work_schedule_id"=>$work_schedule_id,
			"company_id"=>$comp_id,
			"rest_day"=>$workday
		);
		$this->db->where($w);
		$q = $this->db->get("rest_day");
		return ($q->num_rows() > 0) ? TRUE : FALSE ;
	}
	
	/**
	 * Check Workday Settings for start time
	 * @param unknown_type $workday
	 * @param unknown_type $work_schedule_id
	 * @param unknown_type $company_id
	 */
	public function check_workday_settings_start_time($workday,$work_schedule_id,$company_id,$time_in = "",$schedule_blocks_id=0){
		// check uniform working days
		$w = array(
			"work_schedule_id"=>$work_schedule_id,
			"company_id"=>$company_id,
			"days_of_work"=>$workday,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("regular_schedule");
		$row = $q->row();
		if($row){
			return $row->work_start_time;
		}else{
			return FALSE;
		}
	}
	
	// adding start time - aldrin
	/**
	 * Get the nearest time for split schedule_time_in
	 * @return unknown
	 */
	public function get_starttime($schedule_blocks_id,$time_in = null,$start = array()){
		
		$this->edb->where('schedule_blocks_id',$schedule_blocks_id);
		$arrx = array(
				'start_time',
				'end_time',
		);
		$this->edb->select($arrx);
		$q3 = $this->edb->get("schedule_blocks");
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
	
	/**
	 * Identify the blocks and take all the info of blocks added to schedule
	 * @return unknown
	 */
	public function get_blocks_list($schedule_blocks_id){

		$this->edb->where('schedule_blocks_id',$schedule_blocks_id);
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
		$this->edb->select($arr);
		$q3 = $this->edb->get("schedule_blocks");
		$result = $q3->row();
		
		return $result;
	}

	public function get_splitschedule_info_new($time_in,$work_schedule_id,$emp_id,$comp_id){

		$currentdate = date('Y-m-d',strtotime($time_in." -1 day"));
		$arr = array();
		$row_list = array();
		$ls = $this->list_of_blocks($currentdate, $emp_id, $work_schedule_id, $comp_id);
		$today = true;
		if($ls){
			
			$first = reset($ls);
			$last = max($ls);

			$firstx = $this->get_starttime($first->schedule_blocks_id,$currentdate,$first);
			$last = $this->get_endtime($last->schedule_blocks_id,$currentdate,$last);

			$first = date('Y-m-d H:i:s',strtotime($firstx. ' midnight'));
			$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
			if($firstx >= $last){
				$last = date('Y-m-d H:i:s',strtotime($last." +1 day"));
			}
			
			if($time_in>=$first && $time_in<=$last){
				$today= false;
				foreach($ls as $rx){

					$row = $this->get_blocks_list($rx->schedule_blocks_id);						
					$start_time = date('Y-m-d H:i:s',strtotime($currentdate." ".$row->start_time));
					$end_time = date('Y-m-d H:i:s', strtotime($currentdate." ".$row->end_time));
					
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
			
			$currentdate = date('Y-m-d',strtotime($time_in));
			$arr = array();
			$row_list = array();
			$ls = $this->list_of_blocks($currentdate, $emp_id, $work_schedule_id, $comp_id);
			
			if(!empty($ls)){
				$first = reset($ls);
				$last = max($ls);
	
				$firstx = $this->get_starttime($first->schedule_blocks_id,$currentdate,$first);
				$last = $this->get_endtime($last->schedule_blocks_id,$currentdate,$last);
	
				//$first = date('Y-m-d H:i:s',strtotime($firstx. " -200 minutes"));
				$first = date('Y-m-d H:i:s',strtotime($firstx. ' midnight'));
				$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
				if($firstx >= $last){
					$last = date('Y-m-d H:i:s',strtotime($last." +1 day"));
				}
	
				if($time_in>=$first && $time_in<=$last){
				
				foreach($ls as $rx){
	
					$row = $this->get_blocks_list($rx->schedule_blocks_id);						
					$start_time = date('Y-m-d H:i:s',strtotime($currentdate." ".$row->start_time));
					$end_time = date('Y-m-d H:i:s', strtotime($currentdate." ".$row->end_time));
					
					if($start_time >= $end_time){
						$end_time = date('Y-m-d H:i:s',strtotime($end_time." +1 day"));						
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
		}


		foreach($arr as $key => $row2):
			if($time_in <= $row2['start_time']){
				return $arr[$key];
				break;
			}
		endforeach;
		
		return $arr;
	}
	
	public function get_splitschedule_info($result,$time_in = null,$nightshift = array(),$block_completed = 0,$block_not_completed = 0,$new_timein = false,$val = false,$display_time_log =false){
	
		if($nightshift){
			$date_time = $nightshift['currentdate'];	
		}else{ 
			$date_time = date('Y-m-d',strtotime($time_in));		
		}
		
		
		$arr = array();
		$row_list = array();
		$today_arr = array();
		$first = reset($result);
		$last = max($result);
		
		$first = $this->get_starttime($first->schedule_blocks_id,$time_in,$first);
		$last = $this->get_endtime($last->schedule_blocks_id,$time_in,$last);
		$if_first = false;
		// check if block is still no timeout
		if($block_not_completed !=0){			
			$row = $this->get_blocks_list($block_not_completed); 
		
			$start_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->start_time));
			$end_time = date('Y-m-d H:i:s', strtotime($date_time." ".$row->end_time));
			
			if($row->end_time <= $row->start_time){
				$end_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->end_time . " +1 day"));
			}
			if($first >=$last){
				$end_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->end_time . " +1 day"));
			}
			
			$row_list['break_in_min'] = $row->break_in_min;
			$row_list['start_time'] = $start_time;
			$row_list['end_time'] = $end_time;
			$row_list['total_hours_work_per_block'] = $row->total_hours_work_per_block;
			$row_list['block_name'] = $row->block_name;
			$row_list['schedule_blocks_id'] = $row->schedule_blocks_id;
			if($start_time == $first)
				$if_first = true;
			
			$row_list['first'] = $if_first;
			return $row_list;
		}

	

		foreach($result as $rx):
	
			//if($rx->schedule_blocks_id != $block_completed || $val){
		
				$row = $this->get_blocks_list($rx->schedule_blocks_id);						
				$start_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->start_time));
				$end_time = date('Y-m-d H:i:s', strtotime($date_time." ".$row->end_time));
				
				if($row->end_time <= $row->start_time){									
					$end_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->end_time . " +1 day"));				
				}
				
				$mid_night = date('Y-m-d H:i:s',strtotime($date_time." 24:00:00"));

				
				
				if($first >= $last && $time_in >= $mid_night){ // nightshift schedule					
					$new = date('H:i:s',strtotime($first));
					$firstx = date('Y-m-d H:i:s',strtotime($date_time." ".$new));
					
					
					if(!($start_time >= $firstx && $start_time <=$mid_night)){						
						$start_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->start_time." +1 day"));
						$end_time = date('Y-m-d H:i:s', strtotime($date_time." ".$row->end_time." +1 day"));
						
					}
				}
				
				$row_list['break_in_min'] = $row->break_in_min;
				$row_list['start_time'] = $start_time;
				$row_list['end_time'] = $end_time;
				$row_list['total_hours_work_per_block'] = $row->total_hours_work_per_block;
				$row_list['block_name'] = $row->block_name;
				$row_list['schedule_blocks_id'] = $row->schedule_blocks_id;
				if($start_time == $first)
					$if_first = true;
				

				$row_list['first'] = $if_first;
				
				if($time_in >= $start_time && $time_in <= $end_time){
			
					return $row_list;
											
				}else{					
					$arr[] = $row_list;					
				}							
			//}
		endforeach;
	
		foreach($arr as $key => $row2):
		
			if($time_in <= $row2['start_time']){	
				
				return $arr[$key];
				break;
			}
		endforeach;
		
		return false;
	}
	
	/**
	 * get the next split to time in
	 * @param unknown $result
	 * @param string $time_in
	 * @param number $block_completed
	 * @param number $block_not_completed
	 * @param string $nightshift
	 */
	public function get_next_splitschedule($result,$time_in = null,$block_completed = 0,$block_not_completed = 0,$first_split = false,$nightshift = array()){
		
		if($nightshift)
			$date_time = $nightshift['currentdate'];
		else
			$date_time = date('Y-m-d',strtotime($time_in));
		
		$arr = array();
		$row_list = array();
		$first = reset($result);
		$last = max($result);
		$first = $this->get_starttime($first->schedule_blocks_id,$time_in,$first);
		$last = $this->get_endtime($last->schedule_blocks_id,$time_in,$last);
		$x=0;
		$if_first = false;
		
		if($first_split){			
			$get_info = $this->get_splitschedule_info($result,date('Y-m-d H:i:s'),$nightshift);						
			$block_not_completed = $get_info['schedule_blocks_id'];
			$if_first = true;
		}
		
		// check if block is still no timeout
		if($block_not_completed !=0 ){
			
			$row = $this->get_blocks_list($block_not_completed);
		
			$start_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->start_time));
			$end_time = date('Y-m-d H:i:s', strtotime($date_time." ".$row->end_time));
			if($row->end_time <= $row->start_time){
				$end_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->end_time . " +1 day"));
			}					
			
			$row_list['break_in_min'] = $row->break_in_min;
			$row_list['start_time'] = $start_time;
			$row_list['end_time'] = $end_time;
			$row_list['total_hours_work_per_block'] = $row->total_hours_work_per_block;
			$row_list['block_name'] = $row->block_name;
			$row_list['schedule_blocks_id'] = $row->schedule_blocks_id;
			
			$row_list['first'] = $if_first;
			
			return $row_list;
		}
		
		$res = count($result);
		
		while($x < $res){
			
			//if($result[$x]->schedule_blocks_id ==$block_completed  ){
				
				if($res == 1 ){
					$schedule_block_id = $result[$x]->schedule_blocks_id;
				}else{
					
						$schedule_block_id = $result[$x + 1]->schedule_blocks_id;
					
				}
				
				$row = $this->get_blocks_list($schedule_block_id);
					
				$start_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->start_time));
				$end_time = date('Y-m-d H:i:s', strtotime($date_time." ".$row->end_time));
				if($row->end_time <= $row->start_time){
					$end_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->end_time . " +1 day"));
				}
				
				$mid_night = date('Y-m-d H:i:s',strtotime($date_time." 24:00:00"));
				
				if($first >= $last && $time_in >= $mid_night){ // nightshift schedule
					$new = date('H:i:s',strtotime($first));
					$firstx = date('Y-m-d H:i:s',strtotime($date_time." ".$new));
						
					if(!($start_time >= $firstx && $start_time <=$mid_night)){
						
						$start_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->start_time." +1 day"));
						$end_time = date('Y-m-d H:i:s', strtotime($date_time." ".$row->end_time." +1 day"));
					}
				}
				
				
				$row_list['break_in_min'] = $row->break_in_min;
				$row_list['start_time'] = $start_time;
				$row_list['end_time'] = $end_time;
				$row_list['total_hours_work_per_block'] = $row->total_hours_work_per_block;
				$row_list['block_name'] = $row->block_name;
				$row_list['schedule_blocks_id'] = $row->schedule_blocks_id;
				$row_list['first'] = $if_first;
				
				if($time_in >= $start_time && $time_in <= $end_time){					
					return $row_list;		
				}else{
					$arr[] = $row_list;
				}	
			//}
				
			$x++;
			}
		
		foreach($arr as $key => $row2):
		
			if($time_in <= $row2['start_time']){
				return $arr[$key];
				break;
			}
		endforeach;
		
		return false;
	}
	
	
	/**
	 * get the next split to time in
	 * @param unknown $result
	 * @param string $time_in
	 * @param number $block_completed
	 * @param number $block_not_completed
	 * @param string $nightshift
	 * use by time in
	 */
	public function get_next_splitschedule2($result,$time_in = null,$block_completed = 0,$block_not_completed = 0,$first_split = false,$nightshift = array()){
	
		if($nightshift)
			$date_time = $nightshift['currentdate'];
		else
			$date_time = date('Y-m-d',strtotime($time_in));
	
		$arr = array();
		$row_list = array();
		$first = reset($result);
		$last = max($result);
		$first = $this->get_starttime($first->schedule_blocks_id,$time_in,$first);
		$last = $this->get_endtime($last->schedule_blocks_id,$time_in,$last);
		
	
		
	
		return false;
	}
	
	public function split_schedule_time($split_schedule_id,$time_in){

		$this->db->where('split_schedule_id',$split_schedule_id);
		$q2 = $this->db->get("schedule_blocks");
		$result = $q2->result();
		$time_in = date('H:i:s',strtotime($time_in));
		$arr = array();
		
		foreach($result as $row):
		$start_time = date('H:i:s',strtotime($row->start_time));
		$end_time = date('H:i:s', strtotime($row->end_time));
		if($time_in >= $start_time && $time_in <= $end_time):
			$date = date('Y-m-d');
			return $this->get_employee_timein_id($split_schedule_id, $date);
		else:
			$arr[] = $start_time;
		endif;
		endforeach;
		
		foreach($arr as $key => $row2):
		if($time_in <= $row2){
			$date = date('Y-m-d');
			return $this->get_employee_timein_id($split_schedule_id, $date);
		}
		endforeach;
		
		return false;
	}
	
	/***
	 * get the employee time in id
	 * @param unknown $split_schedule_id
	 * @param unknown $date
	 */
	public function get_employee_timein_id($split_schedule_id,$date){
		$date = date('Y-m-d',strtotime($date));
		$this->edb->where('split_schedule_id',$split_schedule_id);
		$this->edb->where('date',$date);
		$q2 = $this->edb->get("schedule_blocks_time_in");
		$result = $q2->row();
		
		if($result){
			return $result->employee_time_in_id;
		}else{
			return false;
		}
	}
	
	/**
	 * Check Workday Settings for end time
	 * @param unknown_type $workday
	 * @param unknown_type $work_schedule_id
	 * @param unknown_type $company_id
	 */
	public function check_workday_settings_end_time($workday,$work_schedule_id,$company_id,$time_out = ""){
		// check uniform working days
		$w = array(
			"work_schedule_id"=>$work_schedule_id,
			"company_id"=>$company_id,
			"days_of_work"=>$workday,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("regular_schedule");
		$row = $q->row();
		if($row){
			return $row->work_end_time;
		}else{
			
			// workshift
			//$w2 = array(
			//	"work_schedule_id"=>$work_schedule_id,
			//	"company_id"=>$company_id
		//	);
			//$this->db->where($w2);
			//$q2 = $this->db->get("split_schedule");
			//$row2 = $q2->row();
			//if($row2){
				
				
			//	return $this->get_endtime($row2->split_schedule_id, $time_out);
			//}else{
				return false;
		//	}
			
		}
	}
	
	// adding end time - aldrin
	public function get_endtime($schedule_blocks_id,$time_in,$last = array()){
		$this->edb->where('schedule_blocks_id',$schedule_blocks_id);
		$arrx = array(
				'start_time',
				'end_time'
		);
		$this->edb->select($arrx);
		$q2 = $this->edb->get("schedule_blocks");
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
	
	/**
	 * Get Tardiness
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $date
	 * @param unknown_type $time_in
	 */
	public function get_tardiness_val($emp_id,$comp_id,$time_in_import,$work_schedule_id,$date = null){
		if($date !=null){
			$day = date("l",strtotime($date));
		}else{
			$day = date("l",strtotime($time_in_import));
		}
		
		
		$where = array(
			"emp_id" => $emp_id
		);
		$this->db->where($where);
		$sql_payroll_info = $this->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		//$payroll_group_id = $row_payroll_info->payroll_group_id;
		$payroll_group_id = 0;
		
		// check if rest day
		if($date !=null)
			$rest_day = $this->check_holiday_val($date,$emp_id,$comp_id,$work_schedule_id);
		else
			$rest_day = $this->check_holiday_val($time_in_import,$emp_id,$comp_id,$work_schedule_id);

		if($rest_day){

			$min_late = 0;
			$min_late_breaktime = 0;
		}else{
			// rest day
			$rd_where = array(
				"company_id"=>$comp_id,
				"rest_day"=>$day,
				"work_schedule_id"=>$work_schedule_id
			);
			$this->db->where($rd_where);
			$rest_day = $this->db->get("rest_day");
			
			if($rest_day->num_rows() == 0){
				// uniform working days settings
				$uni_where = array(
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id,
					"days_of_work"=>$day,
					"status" => "Active"
				);
				$this->db->where($uni_where);
				$sql = $this->db->get("regular_schedule");
				$row = $sql->row();
				
				if($row && ($row->latest_time_in_allowed != NULL || $row->lastest_time_in_allowed !=0)){
					$payroll_sched_timein = date('H:i:s',strtotime($row->work_start_time) + $row->latest_time_in_allowed * 60 ) ;
			
					$tardiness_set = $this->tardiness_settings($emp_id, $comp_id);
					
					if($tardiness_set){
						$payroll_sched_timein = date('H:i:s',strtotime($payroll_sched_timein." +{$tardiness_set} minutes"));
					}
					
				}else{				
					// uniform working days
					$uw_where = array(
						"work_schedule_id"=>$work_schedule_id,
						"company_id"=>$comp_id,
						"days_of_work"=>$day,
						"status" => 'Active'
					);
					$this->db->where($uw_where);
					$sql_uniform_working_days = $this->db->get("regular_schedule");
					$row_uniform_working_days = $sql_uniform_working_days->row();
					
					if($row_uniform_working_days){
						$payroll_sched_timein = $row_uniform_working_days->work_start_time;
						
						if($tardiness_set){
							$payroll_sched_timein = date('H:i:s',strtotime($payroll_sched_timein." +{$tardiness_set} minutes"));
						}
						
					}else{
						// flexible working days
						$fl_where = array(
							"work_schedule_id"=>$work_schedule_id,
							"company_id"=>$comp_id
						);
						$this->db->where($fl_where);
						$sql_flexible_days = $this->db->get("flexible_hours");
						$row_flexible_days = $sql_flexible_days->row();
						
						if($row_flexible_days){
							$payroll_sched_timein = $row_flexible_days->latest_time_in_allowed;
							
							if($tardiness_set){
								$payroll_sched_timein = date('H:i:s',strtotime($payroll_sched_timein." +{$tardiness_set} minutes"));
							}
							
						}else{
							
 								$payroll_sched_timein = "00:00:00";
						}
					}
				}
			}else{
				$payroll_sched_timein = "00:00:00";
			}
			
			if($payroll_sched_timein != "00:00:00" || $payroll_sched_timein != "" || $payroll_sched_timein != NULL){
				
				$time_in_original = $time_in_import;
				$time_in_import = date("H:i:s",strtotime($time_in_import));
			
				// for tardiness time in
				$time_x=(strtotime($time_in_import) - strtotime($payroll_sched_timein)) / 3600;

				// check time in and allowed time in
				$ti_val = date("A",strtotime($time_in_import));
				$at_val = date("A",strtotime($payroll_sched_timein));
				
				if($ti_val == "PM" && $at_val == "AM"){
					// for tardiness time in
					$time_date = date("Y-m-d",strtotime($time_in_original));
					$add_oneday_timein = date("Y-m-d",strtotime($time_date."+ 1 day"));
					$new_allowed_time = $add_oneday_timein." ".$payroll_sched_timein;
					
					$time_x=(strtotime($time_in_original) - strtotime($new_allowed_time)) / 3600;
				}
				
				if($time_x<0){
					if(abs($time_x) >= 12){
						// print $time_z= (24-(abs($time_x))) * 60 . " late ";
						$min_late = round((24-(abs($time_x))) * 60);
					}else{
						$min_late = 0;
					}
				}else{
					// print $time_x * 60 . " late ";
					$min_late = round($time_x * 60);
				}
			}else{
				$min_late = 0;
			}
		}
		
		$min_late = ($min_late < 0) ? 0 : $min_late ;
		
		return $min_late;
	}
	
	/**
	 * Get Undertime for import
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $date
	 * @param unknown_type $time_in
	 * @param unknown_type $work_schedule_id
	 */
	public function get_undertime_val($emp_id,$comp_id,$date_timein,$date_timeout,$work_schedule_id,$break=0,$currentdate = ""){

		if($currentdate){
			$day = date("l",strtotime($currentdate));
		}
		else{
			$day = date("l",strtotime($date_timein));
			$currentdate = date('Y-m-d',strtotime($date_timein));
		}
		
		
		$start_time = "";
		
		$where = array(
			"emp_id" => $emp_id
		);
		$this->db->where($where);
		$sql_payroll_info = $this->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		// $payroll_group_id = $row_payroll_info->payroll_group_id;
		$payroll_group_id = 0;

		// check rest day
		$rest_day = $this->check_holiday_val($date_timein,$emp_id,$comp_id,$work_schedule_id);
		if($rest_day){
			$under_min_val = 0;
		}else{
			// rest day
			$rd_where = array(
				"company_id"=>$comp_id,
				"rest_day"=>$day,
				"work_schedule_id"=>$work_schedule_id
			);
			$this->db->where($rd_where);
			$rest_day = $this->db->get("rest_day");
			
			if($rest_day->num_rows() == 0){
				// uniform working days
				$uw_where = array(
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id,
					"days_of_work"=>$day,
					"status" => 'Active'
				);
				$this->db->where($uw_where);
				$sql_uniform_working_days = $this->db->get("regular_schedule");
				$row_uniform_working_days = $sql_uniform_working_days->row();
				
				if($row_uniform_working_days){
					$start_timex = $row_uniform_working_days->work_start_time;
					$undertime_min = $row_uniform_working_days->work_end_time;
					if($row_uniform_working_days->latest_time_in_allowed != NULL){
							$val = $row_uniform_working_days->latest_time_in_allowed;
							$start_time = date('H:i:s',strtotime($start_timex." +{$val} minutes"));
							
							//if($date_timein < $start_timex)
							
							$start_time_ex = date('Y-m-d H:i:s',strtotime($currentdate." ".$row_uniform_working_days->work_start_time));
							$start = date('H:i:s',strtotime($row_uniform_working_days->work_start_time." +{$row_uniform_working_days->latest_time_in_allowed} minutes"));
							$start_time = date('Y-m-d H:i:s',strtotime($currentdate." ".$start));
							
							//login within grace period
							if($date_timein>= $start_time_ex && $date_timein <= $start_time){
								$work = floor(($row_uniform_working_days->total_work_hours * 60) + $row_uniform_working_days->break_in_min );
								$undertime_min = date('H:i:s',strtotime($date_timein." +{$work} minutes"));
					
							}else if($date_timein < $start_time_ex){
								$work = floor(($row_uniform_working_days->total_work_hours * 60) + $row_uniform_working_days->break_in_min );
								$undertime_min = date('H:i:s',strtotime($start_time_ex." +{$work} minutes"));
			
							}
					}
					
					$working_hours = $row_uniform_working_days->total_work_hours;
				}else{
					// flexible working days
					$fl_where = array(
						"work_schedule_id"=>$work_schedule_id,
						"company_id"=>$comp_id
					);
					$this->db->where($fl_where);
					$sql_flexible_days = $this->db->get("flexible_hours");
					$row_flexible_days = $sql_flexible_days->row();
					
					if($row_flexible_days){
						$this->db->where("emp_id",$emp_id);
						$this->db->order_by("date", "DESC");
						$this->db->limit(1);
						$flexible_compute_time = $this->db->get("employee_time_in");
						$row_flexible_compute_time = $flexible_compute_time->row();
						
						if($row_flexible_compute_time){
							$time_in = explode(" ", $row_flexible_compute_time->time_in);;
							$flexible_work_end = $time_in[1];
							
							// flexible total hours per day
							$flx_where = array(
								"work_schedule_id"=>$work_schedule_id,
								"company_id"=>$comp_id
							);
							$this->db->where($flx_where);
							$sql_flexible_working_days = $this->db->get("flexible_hours");
							$row_flexible_working_days = $sql_flexible_working_days->row();
							
							if($row_flexible_working_days){
								$total_hours_for_the_day = $row_flexible_working_days->total_hours_for_the_day;
								$end_time = date("H:i:s",strtotime($flexible_work_end) + 60 * 60 * $total_hours_for_the_day);
								
								$start_time = $row_flexible_working_days->latest_time_in_allowed;
								$undertime_min =  $end_time;
								$working_hours = $row_flexible_working_days->total_hours_for_the_day;
							}else{
								$undertime_min =  "00:00:00";
							}
						}else{
							$undertime_min =  "00:00:00";
						}
						
					}else{
						// workshift working days
						$ws_where = array(
							"work_schedule_id"=>$work_schedule_id,
							"company_id"=>$comp_id
						);
						$this->db->where($ws_where);
						$sql_workshift = $this->db->get("split_schedule"); //stop here
						$row_workshift = $sql_workshift->row();
						
						if($row_workshift){
							$start_time = $this->get_starttime($row_workshift->split_schedule_id, $date_timein);
							$undertime_min =  $this->get_endtime($row_workshift->split_schedule_id, $date_timein);
							$working_hours = $row_workshift->total_work_hours;
						}else{
							$undertime_min =  "00:00:00";
						}
					}
				}
			}else{
				$undertime_min = "00:00:00";
				$working_hours = 0;
			}
			
			$date_timeout_sec = date("H:i:s",strtotime($date_timeout));
			
			/* if($start_time == ""){
				return 0;
			} */
			
			// check PM and AM
			$check_endtime = date("A",strtotime($undertime_min));
			$check_timein = date("A",strtotime($date_timein));
			$check_timout = date("A",strtotime($date_timeout_sec));
			
			// callcenter trapping
			if($check_endtime == "AM" && $check_timein == "PM"){
				if($currentdate)
					$time_out_date = date("Y-m-d",strtotime($currentdate."+1 day"));
				else 
					$time_out_date = date("Y-m-d",strtotime($date_timeout_sec."+1 day"));
				
				
				$new_undertime_min = date('Y-m-d H:i:s',strtotime($time_out_date." ".$undertime_min));
				
				$under_min_val = (strtotime($new_undertime_min) - strtotime($date_timeout)) / 60;
				
			}else{
				
				if(strtotime($date_timeout_sec) <= strtotime($undertime_min)){
					$check_tardiness_import = $this->check_tardiness_import($emp_id,$comp_id,$date_timein,$work_schedule_id);
					
					if($check_tardiness_import == 0){
						if(strtotime($undertime_min) <= strtotime($date_timeout_sec)){							
							$under_min_val = (strtotime($undertime_min) - strtotime($date_timeout_sec)) / 60;
						}else{
							$breaktime_hours = $this->add_breaktime($comp_id,$work_schedule_id,$date_timein,$break);
							$working_hours = $working_hours + $breaktime_hours;
							$date_timin_sec = date('H:i:s', strtotime($date_timein));
							
							$new_date_timein = (strtotime($start_time) <= strtotime($date_timin_sec)) ? $date_timein : $start_time ;
							$new_timeout_sec = date('H:i:s', strtotime($new_date_timein . ' + '.$working_hours.' hour'));
							$under_min_val = (strtotime($new_timeout_sec) - strtotime($date_timeout_sec)) / 60;
						}
					}else{
						
						//$under_min_val = (strtotime($undertime_min) - strtotime($date_timeout_sec)) / 60;
						$under_min_val= $this->total_hours_worked($undertime_min, $date_timeout_sec);
					}
				}else{
					$under_min_val = 0;
				}
			}
		}
		
		// check total hours for workday
		$get_hours_worked_to_mins = $this->get_hours_worked($day, $emp_id,$work_schedule_id) * 60;
		
		if($get_hours_worked_to_mins < $under_min_val) return 0;
		
		return ($under_min_val < 0) ? 0 : $under_min_val ;	
	}
	
	/**
	 * Check Holiday Value
	 * @param unknown_type $day
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $work_schedule_id
	 */
	public function check_holiday_val($day,$emp_id,$comp_id,$work_schedule_id){
		$w = array(
			"rest_day"=>date("l",strtotime($day)),
			"company_id"=>$comp_id,
			"work_schedule_id"=>$work_schedule_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get('rest_day');
		return ($q->num_rows() > 0) ? TRUE : FALSE ;
	}
	
	/**
	 * Check Tardiness for undertime only
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $date
	 * @param unknown_type $time_in
	 * @param unknown_type $work_schedule_id
	 */
	public function check_tardiness_import($emp_id,$comp_id,$time_in_import,$work_schedule_id){
		$day = date("l",strtotime($time_in_import));
		
		$where = array(
			"emp_id" => $emp_id
		);
		$this->db->where($where);
		$sql_payroll_info = $this->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		// $payroll_group_id = $row_payroll_info->payroll_group_id;
		$payroll_group_id = 0;
		
		// check rest day
		$rest_day = $this->check_holiday_val($time_in_import,$emp_id,$comp_id,$work_schedule_id);
		if($rest_day){
			$min_late = 0;
		}else{
			// rest day
			$rd_where = array(
				"company_id"=>$comp_id,
				"rest_day"=>$day,
				"work_schedule_id"=>$work_schedule_id
			);
			$this->db->where($rd_where);
			$rest_day = $this->db->get("rest_day");
			
			if($rest_day->num_rows() == 0){
				// uniform working days settings
				$uni_where = array(
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id,
					"days_of_work"=>$day
				);
				$this->db->where($uni_where);
				$sql = $this->db->get("regular_schedule");
				$row = $sql->row();
				
				if($row && ($row->latest_time_in_allowed != NULL)){
					$payroll_sched_timein = date('H:i:s',strtotime($row->work_start_time) + $row->latest_time_in_allowed * 60 ) ;
				}else{				
					// uniform working days
					$uw_where = array(
						"work_schedule_id"=>$work_schedule_id,
						"company_id"=>$comp_id,
						"days_of_work"=>$day
					);
					$this->db->where($uw_where);
					$sql_uniform_working_days = $this->db->get("regular_schedule");
					$row_uniform_working_days = $sql_uniform_working_days->row();
					
					if($row_uniform_working_days && ($row->allow_flexible_workhours != 0 && $row->allow_flexible_workhours != NULL)){
						$payroll_sched_timein = $row_uniform_working_days->work_start_time;
						
					}else{
						// flexible working days
						$fl_where = array(
							"work_schedule_id"=>$work_schedule_id,
							"company_id"=>$comp_id
						);
						$this->db->where($fl_where);
						$sql_flexible_days = $this->db->get("flexible_hours");
						$row_flexible_days = $sql_flexible_days->row();
						
						if($row_flexible_days){
							$payroll_sched_timein = $row_flexible_days->latest_time_in_allowed;
						}else{
							// workshift working days
							$ws_where = array(
								"work_schedule_id"=>$work_schedule_id,
								"company_id"=>$comp_id
							);
							$this->db->where($ws_where);
							$sql_workshift = $this->db->get("split_schedule");
							$row_workshift = $sql_workshift->row();
							
							if($row_workshift){
								
								$payroll_sched_timein =$this->get_starttime($row_workshift->split_schedule_id, $time_in_import);
							
							}else{
								$payroll_sched_timein = "00:00:00";
							}
						}
					}
				}
			}else{
				$payroll_sched_timein = "00:00:00";
			}
			
			$time_in_import = date("H:i:s",strtotime($time_in_import));
			
			// for tardiness time in
			$time_x=(strtotime($time_in_import) - strtotime($payroll_sched_timein)) / 3600;	
		   
			if($payroll_sched_timein != "00:00:00" || $payroll_sched_timein != "" || $payroll_sched_timein != NULL){
				if($time_x<0){
					if(abs($time_x) >= 12){
						// print $time_z= (24-(abs($time_x))) * 60 . " late ";
						$min_late = round((24-(abs($time_x))) * 60);
					}else{
						$min_late = 0;
					}
				}else{
					// print $time_x * 60 . " late ";
					$min_late = round($time_x * 60);
				}
			}else{
				$min_late = 0;
			}	
		}
		

		return ($min_late < 0) ? 0 : $min_late;
	}
	
	/**
	 * Add Breaktime for undertime
	 * @param unknown_type $comp
	 * @param unknown_type $work_schedule_id
	 * @param unknown_type $workday
	 */
	public function add_breaktime($comp_id,$work_schedule_id,$workday,$break = 0){
		$flag_workshift = 0;
		
		// workshift working days
		$workshift_where = array(
			"work_schedule_id" => $work_schedule_id,
			"company_id"=>$comp_id
		);
		$this->db->where($workshift_where);
		$workshift_query = $this->db->get("split_schedule");
		$workshift_row = $workshift_query->row();
		if($workshift_row) $flag_workshift = 1;
		
		$day = date("l",strtotime($workday));
		$where = array(
			"company_id" => $comp_id,
			"work_schedule_id" => $work_schedule_id
		);
		$this->db->where($where);
		if($flag_workshift == 0) $this->db->where("workday",$day);
		$sql = $this->db->get("break_time");
		
		// FOR UNIFORM WORKING DAYS
		
		if($sql->num_rows() > 0){
			$row_uniform = $sql->row();
			
		}else{
			$breaktime = 0;
		}
			
		$h = date('H', mktime(0,$break)) * 60;
		$m = date('i', mktime(0,$break));
		$break = $h + $m;
		$breaktime = $break;
		
		return ($breaktime < 0) ? 0 : $breaktime ;
		
	}
	
	/**
	 * Get Hours Worked for workday
	 * @param unknown_type $workday
	 * @param unknown_type $emp_id
	 * @param unknown_type $work_schedule_id
	 */
	public function get_hours_worked($workday, $emp_id,$work_schedule_id){
		$workday_val = date("l",strtotime($workday));
		
		// get employee payroll information
		$w = array("emp_id"=>$emp_id);
		$this->edb->where($w);
		$this->db->where("status","Active");
		$q = $this->edb->get("employee_payroll_information");
		$r = $q->row();
		
		if($q->num_rows() > 0){
			// $payroll_group_id = $r->payroll_group_id;
			$payroll_group_id = 0;
			$comp_id = $r->company_id;
			
			// get hours worked
			$w2 = array(
				"work_schedule_id"=>$work_schedule_id,
				"days_of_work"=>$workday_val,
				"company_id"=>$comp_id,
				"status"=>"Active"
			);
			$this->db->where($w2);
			$q2 = $this->db->get("regular_schedule");
			$r2 = $q2->row();
		
			if($r2){
				// for uniform working days table
				return $r2->total_work_hours;
			}else{
				$wf = array(
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id
				);
				$this->db->where($wf);
				$qf = $this->db->get("flexible_hours");
				$rf = $qf->row();
				if($rf){
					// for flexible hours table
					return $rf->total_hours_for_the_day;
				}else{
					//CHANGED BY FIL BECAUSE OF SPLIT SCHEDULE
// 					$ww = array(
// 						"work_schedule_id"=>$work_schedule_id,
// 						"company_id"=>$comp_id
// 					);
// 					$this->db->where($ww);
// 					$qw = $this->db->get("split_schedule");
// 					$rq = $qw->row();
// 					if($rq){
// 						// for workshift table
// 						return $rq->total_work_hours;
// 					}else{
 						return 0;
// 					}
				}
			}
		}else{
			return 0;
		}
	}

	/**
	 * Get Total Hours Worked
	 * @param unknown_type $time_in
	 * @param unknown_type $time_out
	 * @param unknown_type $hours_worked
	 * @param unknown_type $work_schedule_id
	 */
	public function get_tot_hours($emp_id,$comp_id,$time_in, $lunch_out, $lunch_in, $time_out,$hours_worked,$work_schedule_id,$break=0){
		$where = array(
			"emp_id" => $emp_id
		);
		$this->db->where($where);
		$sql_payroll_info = $this->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		// $payroll_group_id = $row_payroll_info->payroll_group_id;
		$payroll_group_id = 0;
		
		// check if rest day
		$rest_day = $this->check_holiday_val($time_in,$emp_id,$comp_id,$work_schedule_id);
		if($rest_day){
			$total = (strtotime($time_out) - strtotime($time_in)) / 3600;
		}else{
			
			// check time out for uniform working days
			$where_uw = array(
				"company_id"=>$comp_id,
				"work_schedule_id"=>$work_schedule_id,
				"days_of_work"=>date("l",strtotime($time_in))	
			);
			$this->db->where($where_uw);
			$sql_uw = $this->db->get("regular_schedule");
			
			$row_uw = $sql_uw->row();
			if($sql_uw->num_rows() > 0){
				
				$time_out_sec = date("H:i:s",strtotime($time_out));
				$time_out_date = date("Y-m-d",strtotime($time_out));
				$new_work_end_time = $time_out_date." ".$row_uw->work_end_time;
				if(strtotime($new_work_end_time) <= strtotime($time_out)){
					
					$time_in_sec = date("H:i:s",strtotime($time_in));
					$total_hours_worked = (strtotime($new_work_end_time) - strtotime($time_in)) / 3600;
				}else{
					$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
				}	
				
			}else{
				//CHANGED BY FIL BECAUSE OF SPLIT SCHEDULE
				// check time out for workshift
// 				$where_w = array(
// 					"company_id"=>$comp_id,
// 					"work_schedule_id"=>$work_schedule_id,
// 				);
// 				$this->db->where($where_w);
// 				$sql_w = $this->db->get("split_schedule");
// 				$row_w = $sql_w->row();
// 				if($row_w){
// 					$time_out_sec = date("H:i:s",strtotime($time_out));
// 					$time_out_date = date("Y-m-d",strtotime($time_out));
// 					$time_in = date('H:i:s');
// 					$new_work_end_time = $time_out_date." ".$this->get_endtime($row_w->split_schedule_id, $time_in);
					
// 					if(strtotime($new_work_end_time) <= strtotime($time_out)){
									
// 						$time_in_sec = date("H:i:s",strtotime($time_in));
// 						$total_hours_worked = (strtotime($new_work_end_time) - strtotime($time_in)) / 3600;
// 					}else{
// 						$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
// 					}

					
// 				}else{
					// check time out for flexible hours
					$where_f = array(
						"company_id"=>$comp_id,
						"work_schedule_id"=>$work_schedule_id,
					);
					$this->db->where($where_f);
					$sql_f = $this->db->get("flexible_hours");
					$row_f = $sql_f->row();
					if($sql_f->num_rows() > 0){
						$total_hours_worked = (strtotime($time_in . ' + '.$row_f->total_hours_for_the_day.' hour') - strtotime($time_in)) / 3600;
					}else{
						$total_hours_worked = 0;
					}
				//}
			}
			
			$get_tardiness = ($this->get_tardiness_breaktime($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$work_schedule_id, $break)) / 60;
			$breaktime_hours = $this->add_breaktime($comp_id,$work_schedule_id,$time_in,$break);
			$total = $total_hours_worked - $get_tardiness - $breaktime_hours;
	
			if($total > $hours_worked) $total = $hours_worked;
		}

		return ($total < 0) ? round(0,2) : round($total,2) ;
	}
	
	/**
	 * Check Breaktime
	 * @param unknown_type $comp_id
	 * @param unknown_type $work_schedule_id
	 */
	public function check_breaktime2($comp_id,$work_schedule_id,$status = ""){
		
		$flag_workshift = 0;
		// workshift working days
		$workshift_where = array(
			"work_schedule_id"=>$work_schedule_id,
			"company_id"=>$comp_id
		);
		$this->db->where($workshift_where);
		$workshift_query = $this->db->get("split_schedule");
		$workshift_row = $workshift_query->row();
		if($workshift_row) $flag_workshift = 1;
		

		$w = array(
			"work_schedule_id"=>$work_schedule_id,
			"company_id"=>$comp_id
		);
		$this->db->where($w);
		if($flag_workshift == 0) $this->db->where("workday",date("l"));
		$q = $this->db->get("break_time");
		return ($q->num_rows() > 0) ? $q->row() : FALSE ;
	}
	
	public function check_breaktime($comp_id,$work_schedule_id){
	
// 		$flag_workshift = 0;
// 		// workshift working days
// 		$workshift_where = array(
// 				"work_schedule_id"=>$work_schedule_id,
// 				"company_id"=>$comp_id
// 		);
// 		$this->db->where($workshift_where);
// 		$workshift_query = $this->db->get("split_schedule");
// 		$workshift_row = $workshift_query->row();
// 		if($workshift_row) $flag_workshift = 1;
	
	
// 		$w = array(
// 				"split_schedule_id"=>$workshift_row->split_schedule_id
// 		);
// 		$this->db->where($w);
// 		//if($flag_workshift == 0) $this->db->where("workday",date("l"));
// 		$q = $this->db->get("schedule_blocks");
// 		return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		$w = array(
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id
		);
		$this->db->where($w);
		$q = $this->db->get("regular_schedule");
		if($q->num_rows() > 0){
		
			return $q->row();
		}else{
			
			$this->db->where($w);
			$qf = $this->db->get("flexible_hours");
			if($qf->num_rows() > 0){
					
				return $qf->row();
			}else{
	
				return FALSE;
			}
			
		}
	}
	
	/**
	 * Check Hours Flex
	 * @param unknown_type $comp_id
	 * @param unknown_type $work_schedule_id
	 */
	public function check_hours_flex($comp_id,$work_schedule_id){
		$w = array(
			"work_schedule_id"=>$work_schedule_id,
			"company_id"=>$comp_id
		);		
		$this->db->where($w);
		$q = $this->db->get("regular_schedule");
		$r = $q->row();
		if($q->num_rows() > 0){
			return ($r->latest_time_in_allowed != NULL) ? $r->latest_time_in_allowed : FALSE ;
		}else{
			$this->db->where($w);
			$q2 = $this->db->get('flexible_hours');
			$r2 = $q2->row();
			return  ($r2->latest_time_in_allowed != NULL) ? $r2->latest_time_in_allowed : FALSE ;
		}
	}
	
	/**
	 * Get Workday Sched Start Time
	 * @param unknown_type $comp_id
	 * @param unknown_type $work_schedule_id
	 */
	public function get_workday_sched_start($comp_id,$work_schedule_id, $time_in = ""){
		$w = array(
			"work_schedule_id"=>$work_schedule_id,
			"company_id"=>$comp_id,
			"days_of_work"=>date("l"),
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("regular_schedule");
		$r = $q->row();
		if($q->num_rows() > 0){
			return $r->work_start_time;
		 }else{
// 			$w2 = array(
// 				"work_schedule_id"=>$work_schedule_id,
// 				"company_id"=>$comp_id
// 			);
// 			$this->db->where($w2);
// 			$q2 = $this->db->get("split_schedule");
// 			$r2 = $q2->row();
// 			if($q2->num_rows() > 0){
				
// 				return $this->get_starttime($r2->split_schedule_id, $time_in);
// 			}else{
// 				return FALSE;
// 			}
			return false;

		}
	}
	
	/**
	 * Get End Time
	 * @param unknown_type $comp_id
	 * @param unknown_type $work_schedule_id
	 */
	public function get_end_time($comp_id,$work_schedule_id){
		$w = array(
			"work_schedule_id"=>$work_schedule_id,
			"company_id"=>$comp_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("regular_schedule");
		$r = $q->row();
		if($q->num_rows() > 0){
			return $r->work_end_time;
		}else{
// 			$w2 = array(
// 				"work_schedule_id"=>$work_schedule_id,
// 				"company_id"=>$comp_id
// 			);
// 			$this->db->where($w2);
// 			$q2 = $this->db->get("split_schedule");
// 			$r2 = $q2->row();
// 			if($q2->num_rows() > 0){

// 				$time_in = date('H:i:s');
// 				return $this->get_endtime($r2->split_schedule_id, $time_in);
// 			}else{
 				return FALSE;
// 			}
		}
	}
	
	public function get_end_time2($comp_id,$work_schedule_id,$time_out=""){
		$w = array(
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id,
				"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("regular_schedule");
		$r = $q->row();
		if($q->num_rows() > 0){
			return $r->work_end_time;
		}else{
			$w2 = array(
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id
			);
			$this->db->where($w2);
			$q2 = $this->db->get("split_schedule");
			$r2 = $q2->row();
			if($q2->num_rows() > 0){
	
				
				return $this->get_endtime($r2->split_schedule_id, $time_out);
			}else{
				return FALSE;
			}
		}
	}
	
	/**
	 * Check TimeIn Row
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $time_in
	 */
	public function check_timein_row($emp_id, $comp_id, $time_in){
		$w = array(
			"date"=>date("Y-m-d",strtotime($time_in)),
			"emp_id"=>$emp_id,
			"comp_id"=>$comp_id
		);
		$this->db->where($w);
		$q = $this->db->get("employee_time_in");
		return ($q->num_rows() >= 2) ? TRUE : FALSE ;
	}
	
	public function split_check_timein_row($emp_id, $comp_id, $time_in){
		$w = array(
				"date"=>date("Y-m-d",strtotime($time_in)),
				"emp_id"=>$emp_id,
				"comp_id"=>$comp_id
		);
		$this->db->where($w);
		$q = $this->db->get("schedule_blocks_time_in");
		return ($q->num_rows() >= 2) ? TRUE : FALSE ;
	}
	
	/**
	 * Get Tardiness
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $date
	 * @param unknown_type $time_in
	 * @param unknown_type $work_schedule_id 
	 */
	public function get_tardiness_import($emp_id,$comp_id,$time_in_import,$lunch_out,$lunch_in,$work_schedule_id,$break=0,$date="",$half_day = FALSE){
		
		if($date){
			$day = date("l",strtotime($date));
			$date_now = $date; 
		}else{
			$day = date("l",strtotime($time_in_import));
			$date_now = date('Y-m-d',strtotime($time_in_import));
		}
		
		$where = array(
			"emp_id" => $emp_id
		);
		$this->db->where($where);
		$sql_payroll_info = $this->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		// $payroll_group_id = $row_payroll_info->payroll_group_id;
		$payroll_group_id = 0;
		$flag_workshift = 1;
		
		// check if holiday
		//holiday now
		$check_holiday = $this->company_holiday($time_in_import,$comp_id);
		if($check_holiday) return 0;
		
		// check if rest day
		$rest_day = $this->check_holiday_val($date_now,$emp_id,$comp_id,$work_schedule_id);
		if($rest_day){
			$min_late = 0;
			$min_late_breaktime = 0;
		}else{
			// rest day
			$rd_where = array(
				"company_id"=>$comp_id,
				"rest_day"=>$day,
				"work_schedule_id"=>$work_schedule_id
			);
			$this->db->where($rd_where);
			$rest_day = $this->db->get("rest_day");
			
			if($rest_day->num_rows() == 0){
				// uniform working days settings
				$uni_where = array(
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id,
					"days_of_work"=>$day,
					"status" => 'Active'
				);
				$this->db->where($uni_where);
				$sql = $this->db->get("regular_schedule");
				$row = $sql->row();
				
				if($row){
					
					$payroll_sched_timein = date('H:i:s',strtotime($row->work_start_time)) ;
					if($row->latest_time_in_allowed != NULL || $row->latest_time_in_allowed != ""){
						$val = $row->latest_time_in_allowed;
						
						$payroll_sched_timein = date('Y-m-d H:i:s',strtotime($date_now." ".$row->work_start_time ." +{$val} minutes" )) ;
						
					}
					
					$tardiness_set = $this->tardiness_settings($emp_id, $comp_id);		
			
					if($tardiness_set){
						$payroll_sched_timein = date('Y-m-d H:i:s',strtotime($payroll_sched_timein." +{$tardiness_set} minutes"));
						
					}

				}else{				
					// uniform working days
					$uw_where = array(
						"work_schedule_id"=>$work_schedule_id,
						"company_id"=>$comp_id,
						"days_of_work"=>$day,
						"status" => 'Active'
					);
					$this->db->where($uw_where);
					$sql_uniform_working_days = $this->db->get("regular_schedule");
					$row_uniform_working_days = $sql_uniform_working_days->row();
					
					if($row_uniform_working_days){
						$payroll_sched_timein = date('Y-m-d H:i:s',strtotime($date_now." ".$row->work_start_time)) ;
						
						$tardiness_set = $this->tardiness_settings($emp_id, $comp_id);
							
						if($tardiness_set){
							$payroll_sched_timein = date('Y-m-d H:i:s',strtotime($payroll_sched_timein." +{$tardiness_set} minutes"));
						}
					}else{
						// flexible working days
						$fl_where = array(
							"work_schedule_id"=>$work_schedule_id,
							"company_id"=>$comp_id
						);
						$this->db->where($fl_where);
						$sql_flexible_days = $this->db->get("flexible_hours");
						$row_flexible_days = $sql_flexible_days->row();
						
						if($row_flexible_days){					
							$payroll_sched_timein = date('Y-m-d H:i:s',strtotime($date_now." ".$row_flexible_days->latest_time_in_allowed)) ;
							
							$tardiness_set = $this->tardiness_settings($emp_id, $comp_id);
								
							if($tardiness_set){
								$payroll_sched_timein = date('Y-m-d H:i:s',strtotime($payroll_sched_timein." +{$tardiness_set} minutes"));
							}
						}else{
							
							// workshift working days
							$ws_where = array(
								"work_schedule_id"=>$work_schedule_id,
								"company_id"=>$comp_id
							);
							$this->db->where($ws_where);
							$sql_workshift = $this->db->get("split_schedule");
							$row_workshift = $sql_workshift->row();
							
							if($row_workshift){
								$time_in = date('H:i:s');
								$payroll_sched_timein = $this->get_starttime($row_workshift->split_schedule_id, $time_in_import);
								$flag_workshift = 1;
								
							}else{
								$payroll_sched_timein = "00:00:00";
								
							}
						}
					}
				}
			}else{
				$payroll_sched_timein = "00:00:00";
			}
			
			if( $payroll_sched_timein != "" || $payroll_sched_timein != NULL){								

				if($time_in_import > $payroll_sched_timein){
					$min_late = $this->total_hours_worked($time_in_import, $payroll_sched_timein);
					
				}else{
					$min_late = 0;
				}
				
			}else{
				$min_late = 0;
			}
			
		
			$min_late1 = $this->total_hours_worked($lunch_in, $lunch_out);
			
			if($lunch_in != "" && $lunch_out !=""){
			
				if($min_late1 > $break){
					
					$min_late_breaktime = $min_late1 - $break;
				}else{
					
					$min_late_breaktime = 0;
				}
			}else{
				//assumed break
				if(is_break_assumed($work_schedule_id)){
					$min_late_breaktime = 0;
				}else{
					$min_late_breaktime = $break;
				}
			}

		}
						
		
		$min_late_breaktime = ($min_late_breaktime < 0) ? 0 : $min_late_breaktime;
		$min_late = ($min_late < 0) ? (0 + $min_late_breaktime) : ($min_late + $min_late_breaktime) ;

		if($min_late > 0)
			return $min_late ;
		else 
			return 0;
	}
	
	/**
	 * Company Holiday
	 * @param unknown_type $time_in_import
	 * @param unknown_type $comp_id
	 */
	public function company_holiday($time_in_import,$comp_id){
	
		$date = date("Y-m-d",strtotime($time_in_import));
		$w = array(
			"h.date"=>$date,
			"h.company_id"=>$comp_id,
			"h.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("hours_type as ht","ht.hour_type_id = h.hour_type_id","LEFT");
		$q = $this->db->get("holiday as h");
		$r = $q->row();
		
		if($r){
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Get Undertime for import
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $date
	 * @param unknown_type $time_in
	 * @param unknown_type $work_schedule_id
	 */
	public function get_undertime_import($emp_id,$comp_id,$date_timein,$date_timeout,$lunch_out,$lunch_in,$work_schedule_id,$break=0,$currentdate = ""){ 
		
		if($currentdate != ""){
			$day = date("l",strtotime($currentdate));
			$date = $currentdate;
		}else{ 
			$day = date("l",strtotime($date_timein));
			$date = date("Y-m-d H:i:s",strtotime($date_timein));
		}
		
		$start_time = "";
		
		$where = array(
			"emp_id" => $emp_id
		);
		$this->db->where($where);
		$sql_payroll_info = $this->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		// $payroll_group_id = $row_payroll_info->payroll_group_id;
		$payroll_group_id = 0;
		$for_reg = false;
		// check if holiday
		// holiday now
		$check_holiday = $this->company_holiday($currentdate,$comp_id);
		if($check_holiday) return 0;
		
		// check rest day
		$rest_day = $this->check_holiday_val($currentdate,$emp_id,$comp_id,$work_schedule_id);
		if($rest_day){
			$under_min_val = 0;
		}else{
			// rest day
			$rd_where = array(
				"company_id"=>$comp_id,
				"rest_day"=>$day,
				"work_schedule_id"=>$work_schedule_id
			);
			$this->db->where($rd_where);
			$rest_day = $this->db->get("rest_day");
			
			if($rest_day->num_rows() == 0){
				// uniform working days
				$uw_where = array(
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id,
					"days_of_work"=>$day
				);
				$this->db->where($uw_where);
				$sql_uniform_working_days = $this->db->get("regular_schedule");
				$row_uniform_working_days = $sql_uniform_working_days->row();
				
				if($row_uniform_working_days){
					$start_time = date('Y-m-d H:i:s',strtotime($date." " .$row_uniform_working_days->work_start_time));
					$undertime_min = date('Y-m-d H:i:s',strtotime($date." " .$row_uniform_working_days->work_end_time));
					$working_hours = $row_uniform_working_days->total_work_hours;
					
					$latest = $row_uniform_working_days->latest_time_in_allowed;
					if($latest){
						$start_time_ex 	= date('Y-m-d H:i:s',strtotime($date." ".$row_uniform_working_days->work_start_time));
						$start 			= date('H:i:s',strtotime($row_uniform_working_days->work_start_time." +{$latest} minutes"));
						$start_time 	= date('Y-m-d H:i:s',strtotime($date." ".$start));
												
						// login within grace period
						if($date_timein>= $start_time_ex && $date_timein <= $start_time){
							$work 			= floor(($row_uniform_working_days->total_work_hours * 60) + $row_uniform_working_days->break_in_min );
							$undertime_min 	= date('Y-m-d H:i:s',strtotime($date_timein." +{$work} minutes"));
							$for_reg 		= true;
						}
						else if($date_timein < $start_time_ex){
							$work 			= floor(($row_uniform_working_days->total_work_hours * 60) + $row_uniform_working_days->break_in_min );
							$undertime_min 	= date('Y-m-d H:i:s',strtotime($start_time_ex." +{$work} minutes"));
							$for_reg 		= true;
						}
					}
				}else{
					// flexible working days
					$fl_where = array(
								"work_schedule_id"	=> $work_schedule_id,
								"company_id"		=> $comp_id
								);
					$this->db->where($fl_where);
					$sql_flexible_days = $this->db->get("flexible_hours");
					$row_flexible_days = $sql_flexible_days->row();
					
					if($row_flexible_days){
						$this->db->where("emp_id",$emp_id);
						$this->db->order_by("date", "DESC");
						$this->db->limit(1);
						$flexible_compute_time 		= $this->db->get("employee_time_in");
						$row_flexible_compute_time 	= $flexible_compute_time->row();
					
						if($row_flexible_compute_time){
							$row_flexible_compute_time 	= $flexible_compute_time->row();
							$time_in 					= explode(" ", $row_flexible_compute_time->time_in);;
							$flexible_work_end 			= $time_in[1];
							$flexible_compute_time->free_result();
							// flexible total hours per day
							$flx_where = array(
								"work_schedule_id"=>$work_schedule_id,
								"company_id"=>$comp_id
							);
							$this->db->where($flx_where);
							$sql_flexible_working_days = $this->db->get("flexible_hours");
							$row_flexible_working_days = $sql_flexible_working_days->row();
							
							if($row_flexible_working_days){
								$total_hours_for_the_day = $row_flexible_working_days->total_hours_for_the_day;
								$end_time = date("H:i:s",strtotime($flexible_work_end) + 60 * 60 * $total_hours_for_the_day);
								
								$start_time = $row_flexible_working_days->latest_time_in_allowed;
								$undertime_min =  $end_time;
								$working_hours = $row_flexible_working_days->total_hours_for_the_day;
							}else{
								$undertime_min =  "00:00:00";
							}
						}else{
							$undertime_min =  "00:00:00";
						}
						
					}else{
						// workshift working days
						$ws_where = array(
							"work_schedule_id"=>$work_schedule_id,
							"company_id"=>$comp_id
						);
						$this->db->where($ws_where);
						$sql_workshift = $this->db->get("split_schedule");
						$row_workshift = $sql_workshift->row();
						
						if($row_workshift){
							
							$start_time = $this->get_starttime($row_workshift->split_schedule_id, $date_timein);
							$undertime_min = $this->get_endtime($row_workshift->split_schedule_id, $date_timeout);
							$working_hours = $row_workshift->total_work_hours;
							
							
						}else{
							$undertime_min =  "00:00:00";
						}
					}
				}
			}else{
				$undertime_min = "00:00:00";
				$working_hours = 0;
			}
			
			$date_timeout_sec = date("Y-m-d H:i:s",strtotime($date_timeout));
			
			if($date_timein == "" && $date_timeout == "" && $lunch_out == "" && $lunch_in == ""){
				return 0;
			}
			
			if($start_time == ""){
				return 0;
			}
			
			// check PM and AM
			$check_endtime = date("A",strtotime($undertime_min));
			$check_timout = date("A",strtotime($date_timeout_sec));
			$check_starttime = date("A",strtotime($start_time));
			// callcenter trapping
			// if($check_endtime == "AM" && $check_timout == "PM" && $check_timein == "PM"){
			if($check_endtime == "AM" && $check_starttime == "PM"){
				
			//	$time_out_date = date("Y-m-d H:i:s",strtotime($date." ".$undertime_min));
				$new_undertime_min = date("Y-m-d H:i:s",strtotime($undertime_min." +1 day"));				
				$under_min_val = (strtotime($new_undertime_min) - strtotime($date_timeout)) / 60;
		
			}else{
				
				if(strtotime($date_timeout_sec) <= strtotime($undertime_min)){
					$check_tardiness_import = $this->check_tardiness_import($emp_id,$comp_id,$date_timein,$work_schedule_id);
					
					if($check_tardiness_import == 0){
						if(strtotime($undertime_min) <= strtotime($date_timeout_sec)){		
				
							//$under_min_val = (strtotime($undertime_min) - strtotime($date_timeout_sec)) / 60;
							$under_min_val  = $this->total_hours_worked($undertime_min, $date_timeout_sec);
						}else{
							
							$breaktime_hours = $this->add_breaktime($comp_id,$work_schedule_id,$date_timein,$break);
							
							$working_hours = floor(($working_hours + $this->convert_to_hours($breaktime_hours)) * 60);
							$date_timin_sec = date('Y-m-d H:i:s', strtotime($date_timein));
							
							$new_date_timein = (strtotime($start_time) <= strtotime($date_timin_sec)) ? $date_timein : $start_time ;
					
							$new_timeout_sec = date('Y-m-d H:i:s', strtotime($new_date_timein . ' +'.$working_hours.' minutes'));
							
							
							$under_min_val  = $this->total_hours_worked($new_timeout_sec, $date_timeout_sec);
							
							if($for_reg){
								
								
								$under_date = date("Y-m-d H:i:s",strtotime($currentdate." ".$undertime_min));
								
								$under_min_val  = $this->total_hours_worked($under_date, $date_timeout_sec);
							}
							
						}
					}else{
					
						//$under_min_val = (strtotime($undertime_min) - strtotime($date_timeout_sec)) / 60;
						$under_min_val  = $this->total_hours_worked($undertime_min, $date_timeout_sec);
					}
					
				}else{
					
					$under_min_val = 0;
				}
			}
		}
		
		$get_hours_worked_to_mins = $this->get_hours_worked($day, $emp_id, $work_schedule_id) * 60;

		if($get_hours_worked_to_mins < $under_min_val) return 0;
		
		
		
		return ($under_min_val < 0) ? 0 : $under_min_val ;	
	}
	
	/**
	 * Get Total Hours Worked Complete Logs
	 * @param unknown_type $time_in
	 * @param unknown_type $lunch_in
	 * @param unknown_type $lunch_out	
	 * @param unknown_type $time_out
	 * @param unknown_type $work_schedule_id
	 */
	public function get_tot_hours_complete_logs($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$time_out,$hours_worked,$work_schedule_id,$break=0,$date=""){
		$where = array(
			"emp_id" => $emp_id
		);
		$this->db->where($where);
		$sql_payroll_info = $this->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		// $payroll_group_id = $row_payroll_info->payroll_group_id;
		
		if($date!="")
			$date = date("l",strtotime($date));
		else 
			$date = date("l",strtotime($time_in));
		
		// check if rest day
		$rest_day = $this->check_holiday_val($time_in,$emp_id,$comp_id,$work_schedule_id);
		if($rest_day){
			$total = (strtotime($time_out) - strtotime($time_in)) / 3600;
			
		}else{
			
			// check time out for uniform working days
			$where_uw = array(
				"company_id"=>$comp_id,
				"work_schedule_id"=>$work_schedule_id,
				"days_of_work"=>$date
			);
			$this->db->where($where_uw);
			$sql_uw = $this->db->get("regular_schedule");
			
			$row_uw = $sql_uw->row();
			if($sql_uw->num_rows() > 0){
				
				$time_out_sec = date("H:i:s",strtotime($time_out));
				$time_out_date = date("Y-m-d",strtotime($time_out));
				$new_work_end_time = $time_out_date." ".$row_uw->work_end_time;
				if(strtotime($new_work_end_time) <= strtotime($time_out)){
					// FOR CALLCENTER
					$time_in_sec = date("H:i:s",strtotime($time_in));
					//$total_hours_worked = (strtotime($new_work_end_time) - strtotime($time_in)) / 3600;
					$total_hours_worked = $this->total_hours_worked($time_out, $time_in);
					///$total_hours_worked =  $row_uw->total_work_hours;
				}else{
					// FOR TGG ABOVE
					//$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
					$total_hours_worked = $this->total_hours_worked($time_out, $time_in);
					//$total_hours_worked =  $row_uw->total_work_hours
				}
				
				
				
			}else{
				
				// check time out for workshift
				$where_w = array(
					"company_id"=>$comp_id,
					"work_schedule_id"=>$work_schedule_id
				);
				$this->db->where($where_w);
				$sql_w = $this->db->get("split_schedule");
				$row_w = $sql_w->row();
				if($row_w){
					
					$time_out_sec = date("H:i:s",strtotime($time_out));
					$time_out_date = date("Y-m-d",strtotime($time_out));
					
					$new_work_end_time = $time_out_date." ".$this->get_endtime($row_w->split_schedule_id, $time_out);
					
					if(strtotime($new_work_end_time) <= strtotime($time_out)){
									
						$time_in_sec = date("H:i:s",strtotime($time_in));
						//$total_hours_worked = (strtotime($new_work_end_time) - strtotime($time_in)) / 3600;
						//$total_hours_worked = $this->total_hours_worked($new_work_end_time, $time_in);
						
						$total_hours_worked =  $row_uw->total_work_hours;
					}else{
						//$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
						//$total_hours_worked = $this->total_hours_worked($time_out, $time_in);
						$total_hours_worked =  $row_uw->total_work_hours;
					}
					
				}else{
					// check time out for flexible hours
					$where_f = array(
						"company_id"=>$comp_id,
						"work_schedule_id"=>$work_schedule_id
					);
					$this->db->where($where_f);
					$sql_f = $this->db->get("flexible_hours");
					$row_f = $sql_f->row();
					if($sql_f->num_rows() > 0){
						//$total_hours_worked = (strtotime($time_in . ' + '.$row_f->total_hours_for_the_day.' hour') - strtotime($time_in)) / 3600;
						$total_hours_worked = $this->total_hours_worked($time_in . ' + '.$row_f->total_hours_for_the_day.' hour', $time_in);
						//$total_hours_worked =  $row_f->total_hours_for_the_day;
					}else{
						$total_hours_worked = 0;
						//$total_hours_worked =  $row_f->total_hours_for_the_day;
					}
				}
			}
			
			
			$get_tardiness  = ($this->get_tardiness_breaktime($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$work_schedule_id,$break));
			$breaktime_hours = $this->add_breaktime($comp_id,$work_schedule_id,$time_in,$break);
			
			$total_all = $total_hours_worked - $get_tardiness - $breaktime_hours;
			$total = $this->convert_to_hours($total_all);			
		
			// check if rest day
			$rest_day = $this->check_holiday_val($time_in,$emp_id,$comp_id,$work_schedule_id);
			if($rest_day){
				$total = (strtotime($time_out) - strtotime($time_in)) / 3600;
			}
			
			if($total > $hours_worked){			
				$total = $hours_worked;				
			}
			
		}

		return ($total < 0) ? round(0,2) : round($total,2) ;
	}
	
	/**
	 * Get Tardiness for breaktime
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $date
	 * @param unknown_type $time_in
	 * @param unknown_type $work_schedule_id
	 */
	public function get_tardiness_breaktime($emp_id,$comp_id,$time_in_import,$lunch_out,$lunch_in,$work_schedule_id,$breaks = 0,$gDate=null ){
		$day = date("l",strtotime($time_in_import));
		$currentdate = date('Y-m-d',strtotime($time_in_import));
		if($gDate){
			$day = date("l",strtotime($gDate));
			$currentdate = $gDate;
		}
		
		$where = array(
			"emp_id" => $emp_id
		);
		$this->db->where($where);
		$sql_payroll_info = $this->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		// $payroll_group_id = $row_payroll_info->payroll_group_id;
		$payroll_group_id = 0;
		$flag_workshift = 0;
		
		// check rest day
		$rest_day = $this->check_holiday_val($currentdate,$emp_id,$comp_id,$work_schedule_id);
		if($rest_day){
			$min_late_breaktime = 0;
		}else{
			// rest day
			$rd_where = array(
				"company_id"=>$comp_id,
				"rest_day"=>$day,
				"work_schedule_id"=>$work_schedule_id
			);
			$this->db->where($rd_where);
			$rest_day = $this->db->get("rest_day");
			
			if($rest_day->num_rows() == 0){
				// uniform working days settings
				$uni_where = array(
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id
				);
				$this->db->where($uni_where);
				$sql = $this->db->get("regular_schedule");
				$row = $sql->row();
				
				if($row && ($row->latest_time_in_allowed != NULL)){
					$payroll_sched_timein = date('H:i:s',strtotime($row->work_start_time) + $row->latest_time_in_allowed * 60 ) ;
					
				}else{				
					// uniform working days
					$uw_where = array(
						"work_schedule_id"=>$work_schedule_id,
						"company_id"=>$comp_id,
						"days_of_work"=>$day
					);
					$this->db->where($uw_where);
					$sql_uniform_working_days = $this->db->get("regular_schedule");
					$row_uniform_working_days = $sql_uniform_working_days->row();
					
					if($row_uniform_working_days){
						$payroll_sched_timein = $row_uniform_working_days->work_start_time;
					}else{
						// flexible working days
						$fl_where = array(
							"work_schedule_id"=>$work_schedule_id,
							"company_id"=>$comp_id
						);
						$this->db->where($fl_where);
						$sql_flexible_days = $this->db->get("flexible_hours");
						$row_flexible_days = $sql_flexible_days->row();
						
						if($row_flexible_days){
							$payroll_sched_timein = $row_flexible_days->latest_time_in_allowed;
						}else{
							// workshift working days
							$ws_where = array(
								"work_schedule_id"=>$work_schedule_id,
								"company_id"=>$comp_id
							);
							$this->db->where($ws_where);
							$sql_workshift = $this->db->get("split_schedule");
							$row_workshift = $sql_workshift->row();
							
							if($row_workshift){
								$payroll_sched_timein = $this->get_starttime($row_workshift->split_schedule_id, $time_in_import);
								$flag_workshift = 1;
							}else{
								$payroll_sched_timein = "00:00:00";
							}
						}
					}
				}
			}else{
				$payroll_sched_timein = "00:00:00";
			}
			
			$time_in = $time_in_import;
			$time_in_import = date("H:i:s",strtotime($time_in_import));
			
			// for tardiness time in
			$time_x=(strtotime($time_in_import) - strtotime($payroll_sched_timein)) / 3600;	
		
			if($payroll_sched_timein != "00:00:00" || $payroll_sched_timein != "" || $payroll_sched_timein != NULL){
				if($time_x<0){
					if(abs($time_x) >= 12){
						// print $time_z= (24-(abs($time_x))) * 60 . " late ";
						$min_late = floor((24-(abs($time_x))) * 60);
					}else{
						$min_late = 0;
					}
				}else{
					// print $time_x * 60 . " late ";
					$min_late = floor($time_x * 60);
				}
			}else{
				$min_late = 0;
			}
			
			// for tardiness break
			
			// get uniform working days and workshift settings for break time
			$where_break = array(
				"company_id" => $comp_id,
				"work_schedule_id" => $work_schedule_id
			);
			$this->db->where($where_break);
			if($flag_workshift == 0) $this->db->where("workday",$day);
			$sql_break = $this->db->get("break_time");
			$row_break = $sql_break->row();
			
			if($sql_break->num_rows() > 0){
				$today = date('Y-m-d',strtotime($time_in));
				$start = date('Y-m-d H:i:s',strtotime($today." ".$row_break->start_time));
				$end = date('Y-m-d H:i:s',strtotime($today." ".$row_break->end_time." +1 day"));
				if(strtotime($row_break->start_time) > strtotime($row_break->end_time)){
					$total      = strtotime($end) - strtotime($start);
					$hours      = floor($total / 60 / 60);
					$minutes    = round(($total - ($hours * 60 * 60)) / 60);
					
					if(strlen($minutes)==1)
						$minutes = "0".$minutes;
				
					$breaktime_settings = strtotime($hours.":".$minutes.":00");
				}else{
					 $breaktime_settings = strtotime($row_break->end_time) - strtotime($row_break->start_time);
				}
				
			}else{
				// get flexible hours for break time
				$where_break_flex = array(
					"company_id" => $comp_id,
					"work_schedule_id" => $work_schedule_id
				);
				$this->db->where($where_break_flex);
				$sql_break_flex = $this->db->get("flexible_hours");
				$row_break_flex = $sql_break_flex->row();
				
				if($sql_break_flex->num_rows() > 0){
					$breaktime_settings = $row_break_flex->duration_of_lunch_break_per_day * 60; // convert to seconds
				}else{
					$breaktime_settings = 0;
				}
			}
			
			$total_break = $this->total_hours_worked($lunch_in, $lunch_out);
			
			$breaktime_settings = $total_break- $breaks;
		
			if($lunch_in!="" && $lunch_out !=""){
				
				if($total_break > $breaks)
					$min_late_breaktime = $breaktime_settings;
				else 
					$min_late_breaktime = 0;
			}else 
				$min_late_breaktime = $breaks;
			
			
		}
		
		return ($min_late_breaktime < 0) ? 0 : $min_late_breaktime ;
	}
	
	/**
	 * Get Total Hours Worked
	 * @param unknown_type $time_in
	 * @param unknown_type $lunch_in
	 * @param unknown_type $lunch_out
	 * @param unknown_type $time_out
	 * @param unknown_type $work_schedule_id
	 */
	public function get_tot_hours_limit($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$time_out,$work_schedule_id,$break=0,$emp_schedule_date=null){
		
		//$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
		$total_hours_worked = $this->total_hours_worked($time_out, $time_in);
		//$get_tardiness = ($this->get_tardiness_breaktime($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$work_schedule_id,$break)) / 60;
		$get_tardiness  = ($this->get_tardiness_breaktime($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$work_schedule_id,$break));
		$breaktime_hours = $this->add_breaktime($comp_id,$work_schedule_id,$time_in,$break);
		
		if(is_break_assumed($work_schedule_id)){
			$get_tardiness = 0;
		}
		
		$total = $total_hours_worked - $get_tardiness - $break;	
	
	
		// check if rest day
		if($emp_schedule_date == null) {
			$rest_day = $this->check_holiday_val($time_in,$emp_id,$comp_id,$work_schedule_id);
		} else {
			$rest_day = $this->check_holiday_val($emp_schedule_date,$emp_id,$comp_id,$work_schedule_id);
		}
		
		if($rest_day){
			$total = (strtotime($time_out) - strtotime($time_in)) / 3600;
		}
		
		//return ($total < 0) ? round(0,2) : round($total,2);
		$total_all = $this->convert_to_hours($total); 
		return $total_all;
	}
	
	public function total_hours_worked($to,$from){
		
		$to = date('Y-m-d H:i',strtotime($to));
		$from = date('Y-m-d H:i',strtotime($from));
		$total      = strtotime($to) - strtotime($from);
		$hours      = floor($total / 60 / 60);
		$minutes    = floor(($total - ($hours * 60 * 60)) / 60);
		return  ($hours * 60) + $minutes;
	}
	
	/**
	 * Check Valid From to Valid To Employee Shift Schedule
	 * @param unknown_type $emp_no
	 * @param unknown_type $check_company_id
	 * @param unknown_type $currentdate
	 */
	public function valid_from_to($emp_no,$check_company_id,$currentdate){
		// encrypted
		$w = array("a.payroll_cloud_id"=>$emp_no);
		$this->edb->where($w);
		
		// string
		$w_string = array(
			"e.status"				=> 	"Active",
			"ess.valid_from <="		=>	$currentdate,
			"ess.until >="			=>	$currentdate,
			"ess.company_id"		=>	$check_company_id,
			"ess.status"			=>	"Active"
		); 
		$this->db->where($w_string);
		
		$this->edb->join("employee AS e","ess.emp_id = e.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$q = $this->edb->get("employee_shifts_schedule AS ess");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Add Employee Time In Logs
	 * @param unknown_type $emp_id
	 */
	public function add_logs($comp_id, $emp_id, $reason, $time_in,$lunch_out,$lunch_in,$time_out, $hours_worked, $work_schedule_id){			
		
		if($time_in != NULL){
			$day = date("l",strtotime($time_in));
			$break = 0;
			$w_uwd = array(
					//"payroll_group_id"=>$payroll_group,
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id,
					"days_of_work"=> $day,
					"status" => 'Active'
			);
			$this->db->where($w_uwd);
			$q_uwd = $this->db->get("regular_schedule");
			$r_uwd = $q_uwd->row();
			if($q_uwd->num_rows > 0){
				$break = $r_uwd->break_in_min;
			}else{
				$w_fh = array(
						//"payroll_group_id"=>$payroll_group,
						"work_schedule_id"=>$work_schedule_id,
						"company_id"=>$comp_id
				);
				$this->db->where($w_fh);
				$q_fh = $this->db->get("flexible_hours");
				$r_fh = $q_fh->row();
				if($q_fh->num_rows() > 0){
					$break = $r_fh->duration_of_lunch_break_per_day;
			
				}
			}
			// tardiness
			$tardiness = $this->get_tardiness_import($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $work_schedule_id, $break);
				
			// undertime
			$undertime = $this->get_undertime_import($emp_id, $comp_id, $time_in, $time_out, $lunch_out, $lunch_in, $work_schedule_id, $break);
				
			// total hours worked
			$total_hours_worked = $this->get_tot_hours_limit($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out, $work_schedule_id, $break);
				
			// total hours worked view
			$total_hours_worked_view = $this->get_tot_hours($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out, $hours_worked, $work_schedule_id);
	
			$date_insert = array(
				"comp_id"=>$comp_id,
				"emp_id"=>$emp_id,
				"date"=>date("Y-m-d",strtotime($time_in)),
				"time_in_status"=>'pending',
				"corrected"=>'Yes',
				"reason"=>$reason,
				"time_in"=>$time_in,
				"lunch_out"=>$lunch_out,
				"lunch_in"=>$lunch_in,
				"time_out"=>$time_out,
				"tardiness_min"=>$tardiness,
				"undertime_min"=>$undertime,
				"total_hours"=>0,
				"total_hours_required"=>$total_hours_worked,
				"change_log_date_filed"=>date("Y-m-d H:i:s"),
				"change_log_time_in"=>$time_in,
				"change_log_lunch_out"=>$lunch_out,
				"change_log_lunch_in"=>$lunch_in,
				"change_log_time_out"=>$time_out,
				"change_log_tardiness_min"=>$tardiness,
				"change_log_undertime_min"=>$undertime,
				"change_log_total_hours_required"=>$total_hours_worked,
				"change_log_total_hours"=>$total_hours_worked
			
			);
			$this->db->insert('employee_time_in', $date_insert);
		}
		
		return TRUE;
	}
	
	/**
	 * Add Employee Time In Logs - IMPORT
	 * @param unknown_type $emp_id
	 * @param gDate came from edit timesheets
	 */
	//import timesheet and add timesheet regular scheds and split scheds
	public function import_add_logs($comp_id, $emp_id, $reason, $time_in,$lunch_out,$lunch_in,$time_out, $hours_worked, $work_schedule_id,$break=0,$split=array(),$log_error = false,$source ="",$emp_no = "",$gDate ="",$employee_time_in_id =""){			
		
		if($time_in != NULL && $work_schedule_id){
			// if schedule is split
			if($split){
				$currentdate 	= date('Y-m-d',strtotime($time_in));
				$cur_date 		= array();
				$new_add		= true;
				$yest 			= false;
				$calculate		= false;
				$parent 		= true;
				//for night schedule;
				$yesterday_m 	= date('Y-m-d',strtotime($time_in));
				$yesterday 		= date('Y-m-d',strtotime($yesterday_m. " -1 day"));
				$yest_list 		= $this->list_of_blocks($yesterday,$emp_id,$work_schedule_id,$comp_id);
				$half_day 		= false;
				$check 			= $this->import_split_sched($time_in,$time_out,$work_schedule_id,$comp_id,$emp_id,$lunch_in,$lunch_out,$log_error,$half_day,$emp_no,$gDate,$source);
			}
			else{
				// if regular, nighshift and compress
				if($gDate){
					$currentdate 	= $gDate;
				}
				else{
					$currentdate 	= date('Y-m-d',strtotime($time_in));
					$vx 			= $this->activate_nightmare_trap_upload($comp_id,$emp_no,$time_in,$time_out);
					if($vx){
						$currentdate = $vx['current_date'];
					}
				}
				
				// store the orig break
				$break_orig 	= $break;
				// grace period
				$tardiness_set 	= $this->tardiness_settings($emp_id, $comp_id);
				$grace_period	= ($tardiness_set) ? $tardiness_set : 0;
				// holiday now
				$holiday = $this->company_holiday($currentdate, $comp_id);		
						
				if($holiday){
					$h 					= $this->total_hours_worked($time_out,$time_in);
					$holday_hour		= $this->convert_to_hours($h);
					$time_query_where	= array(
										"comp_id"	=> $comp_id,
										"emp_id"	=> $emp_id,
										"date"		=> $currentdate,
										"status"	=> 'Active'
										);
					
					$this->db->where($time_query_where);
					$time_query 	= $this->edb->get('employee_time_in');
					$time_query_row = $time_query->row();
					$time_query->free_result();
					
					if($time_query_row) {
						$tq_update_field = array(
								"source"				=> $source,
								"time_in"				=> $time_in,
								"time_out"				=> $time_out,
								"work_schedule_id" 		=> $work_schedule_id,
								"total_hours"			=> $holday_hour,
								"total_hours_required"	=> $holday_hour
								);
					
						$this->db->where($time_query_where);
						$this->db->update('employee_time_in',$tq_update_field);
					}else{
						$date_insert = array(
								"comp_id"				=> $comp_id,
								"emp_id"				=> $emp_id,
								"date"					=> $currentdate ,
								"source"				=> $source,
								"time_in"				=> $time_in,
								"time_out"				=> $time_out,
								"work_schedule_id"		=> $work_schedule_id,
								"total_hours"			=> $holday_hour ,
								"total_hours_required"	=> $holday_hour
								);
						$this->db->insert('employee_time_in', $date_insert);
					}
					return true;
				}
				
				// tardiness
				$tardiness 			= $this->get_tardiness_import($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $work_schedule_id,$break,$currentdate);
				
				// undertime
				$undertime 			= $this->get_undertime_import($emp_id, $comp_id, $time_in, $time_out, $lunch_out, $lunch_in, $work_schedule_id,$break,$currentdate);
				// total hours worked view
				$update_total_hours = $this->get_tot_hours_complete_logs($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out, $hours_worked, $work_schedule_id,$break);
				// total absent min
				$absent_min 		= $this->absent_minutes_calc($work_schedule_id,$comp_id,$time_in,$time_out,$emp_no,$undertime,$tardiness,true,$gDate);
		
				// total hours worked
				// this will use for import timesheet and add timesheet
				$half_day = 0;
				
				//$total_hours_worked = $this->get_tot_hours_limit($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out, $work_schedule_id,$break);
				$late_min 		= $this->late_min($comp_id,$currentdate,$emp_id,$work_schedule_id,"",$time_in);
				$overbreak_min 	= $this->overbreak_min($comp_id,$currentdate,$emp_id,$work_schedule_id,$lunch_out,"",$lunch_in);
				
				/***total hours ***/
				$disable_absent 	= true;
				$tardiness_absent 	= false;
				$undertime_absent 	= false;
				$breakx 			= 0;
				
				// check break assumed
				$is_work = is_break_assumed($work_schedule_id);
				if($is_work){
					$lunch_out 	= NULL;				
					$lunch_in 	= NULL;
					$day 		= date('l',strtotime($gDate));
					$uni_where = array(
							"work_schedule_id"	=> $work_schedule_id,
							"company_id"		=> $comp_id,
							"days_of_work"		=> $day,
							"status" 			=> 'Active'
					);
					
					$this->db->where($uni_where);
					$sql 	= $this->db->get("regular_schedule");
					$row 	= $sql->row();
					if($row){
						
						$payroll_sched_timein = date('Y-m-d H:i:s',strtotime($gDate." ".$row->work_start_time)) ;
						
						if($row->latest_time_in_allowed != NULL || $row->latest_time_in_allowed != ""){
							$val 					= $row->latest_time_in_allowed;
							$payroll_sched_timein 	= date('Y-m-d H:i:s',strtotime($payroll_sched_timein  ." +{$val} minutes" ));
						}
						
						$h 			= $is_work->assumed_breaks * 60;
						$lunch_out 	= date('Y-m-d H:i:s',strtotime($payroll_sched_timein. " +{$h} minutes"));
						$lunch_in	= date('Y-m-d H:i:s',strtotime($lunch_out. " +{$row->break_in_min} minutes"));
						
						$breakx = $break;
						if(!is_attendance_active($comp_id)){
							if($time_in >= $lunch_out){
								$lunch_out 			= null;
								$lunch_in 			= null;
								$late_min 			= 0;
								$break				= 0;
								$tardiness_absent 	= true;
							
							}elseif($time_out <= $lunch_in){
								$lunch_out 			= null;
								$lunch_in 			= null;
								$undertime_absent 	= true;	
								$break				= 0;
							}
						}
					}
					
					$get_tardiness 	= 0;
					$disable_absent = false;
					$tardiness 		= $tardiness - $overbreak_min;
					$overbreak_min 	= 0;
					// end break assume
				}
				else {
					$get_tardiness  = ($this->get_tardiness_breaktime($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$work_schedule_id,$break,$currentdate));
				}
				
				$hours_worked_half	= ($hours_worked/2 + ($break_orig/60)) * 60;
				
				$total_hours_worked = $this->total_hours_worked($time_out, $time_in);
				$breakz = $break;
				if(!$is_work){
					$tardiness = $late_min + $overbreak_min;
					if($hours_worked_half > $total_hours_worked){
						if(($lunch_out < $lunch_in) && ($lunch_out != Null  &&  $lunch_in != Null)){
							
						}else {
							$breakz = 0;
						}
					}
					if($work_schedule_id){
						
						$day 		= date('l',strtotime($gDate));
						$uni_where = array(
								"work_schedule_id"	=> $work_schedule_id,
								"company_id"		=> $comp_id,
								"days_of_work"		=> $day,
								"status" 			=> 'Active'
						);
							
						$this->db->where($uni_where);
						$sql 	= $this->db->get("regular_schedule");
						$row 	= $sql->row();
						
						if($row){
							$threshold 			= $row->latest_time_in_allowed;
							$threshold_ded 		= 0;
							$end_time_sched 	= $gDate." ".$row->work_end_time;
							$start_time_sched 	= $gDate." ".$row->work_start_time;
							
							$assumed_start_thres = date('Y-m-d H:i',strtotime($start_time_sched ."+ {$threshold} minutes"));
							
							$hwh 				= $hours_worked_half - $break_orig;
							$assumed_lunch_out 	= date('Y-m-d H:i',strtotime($start_time_sched ."+ {$hwh} minutes"));
							$assumed_lunch_in 	= date('Y-m-d H:i',strtotime($assumed_lunch_out ."+ {$break_orig} minutes"));
							
							// threshold effect when timein is greater than work_start_time < assumed lunchout
							if((strtotime($time_in) > strtotime($start_time_sched)) && (strtotime($time_in) < strtotime($assumed_lunch_out))){
								if(strtotime($time_in) < strtotime($assumed_start_thres)){
									// compute for thresh
									$fromxth			= date('Y-m-d H:i',strtotime($start_time_sched));
									$toxth 				= date('Y-m-d H:i',strtotime($time_in));
									$totalxth 			= strtotime($toxth) - strtotime($fromxth);
									$hoursxth			= floor($totalxth / 60 / 60);
									$minutesxth   	 	= floor(($totalxth - ($hoursxth * 60 * 60)) / 60);
									$threshold_ded 		= (($hoursxth * 60) + $minutesxth);
								}else{
									$threshold_ded = $threshold;
								}
							}
							
							// check tardiness
							if($threshold){
								$start_time_here = $assumed_start_thres;
							}else{
								$start_time_here = $start_time_sched;
							}
							
							if(strtotime($time_in) > strtotime($start_time_here)){
								$froml		= date('Y-m-d H:i',strtotime($start_time_here));
								$tol 		= date('Y-m-d H:i',strtotime($time_in));
								$totall 	= strtotime($tol) - strtotime($froml);
								$hoursl		= floor($totall / 60 / 60);
								$minutesl   = floor(($totall - ($hoursl * 60 * 60)) / 60);
								$late 		= (($hoursl * 60) + $minutesl);
							}
							// if lunchout and lunchin is null and timein during assumed
							if(($lunch_out == NULL && $lunch_in == NULL) || strtotime($lunch_out) == strtotime($lunch_in)){
								if((strtotime($time_in) >= strtotime($assumed_lunch_out)) && (strtotime($time_in) < strtotime($assumed_lunch_in))){
									$late_min = $hwh;
								}
								else if(strtotime($time_in) >= strtotime($assumed_lunch_in)){
									$late_min = $late_min - $break_orig;
								}
							}
							
							$tardiness = $late_min + $overbreak_min;
							
							$end_time_sched 	= date('Y-m-d H:i',strtotime($end_time_sched ."+ {$threshold_ded} minutes"));
							if(strtotime($time_out) < strtotime($end_time_sched)){
								// compute for late min and tardiness
								$fromxu				= date('Y-m-d H:i',strtotime($time_out));
								$toxu 				= date('Y-m-d H:i',strtotime($end_time_sched));
								$totalxu 			= strtotime($toxu) - strtotime($fromxu);
								$hoursxu			= floor($totalxu / 60 / 60);
								$minutesxu   	 	= floor(($totalxu - ($hoursxu * 60 * 60)) / 60);
								$undertime 			= (($hoursxu * 60) + $minutesxu);
								
								if($hours_worked_half <= $undertime){
									$undertime = $undertime - $break_orig;
								}
								if(strtotime($time_out) < strtotime($assumed_lunch_in) && strtotime($time_out) > strtotime($assumed_lunch_out)){
									$fromxu1			= date('Y-m-d H:i',strtotime($time_out));
									$toxu1 				= date('Y-m-d H:i',strtotime($assumed_lunch_in));
									$totalxu1 			= strtotime($toxu1) - strtotime($fromxu1);
									$hoursxu1			= floor($totalxu1 / 60 / 60);
									$minutesxu1   	 	= floor(($totalxu1 - ($hoursxu1 * 60 * 60)) / 60);
									$break_bet 			= (($hoursxu1 * 60) + $minutesxu1);
									$undertime 			= $undertime - $break_bet;
								}
							}
						}
					}
				}
				
				$total 				= $total_hours_worked - $get_tardiness - $breakz;
				// check if rest day
				$rest_day = $this->check_holiday_val($currentdate,$emp_id,$comp_id,$work_schedule_id);
				
				
				if($rest_day){
					$total = (strtotime($time_out) - strtotime($time_in)) / 3600;
				}
				
				// check employee on leave
				$onleave 	= check_leave_appliction($currentdate,$emp_id,$comp_id);
				$ileave 	= 'no';
				
				if($onleave){
					$ileave = 'yes';
				}
				
				$total_hours_worked = $this->convert_to_hours($total);
				
				/***total hours ***/
				
				/*** BARACK NEW TOTAL HOURS WORKED  For Assumed***/
				
				if($is_work){
					
					$day 		= date('l',strtotime($gDate));
					$uni_where 	= array(
							"work_schedule_id"	=> $work_schedule_id,
							"company_id"		=> $comp_id,
							"days_of_work"		=> $day,
							"status" 			=> 'Active'
					);
				
					$this->db->where($uni_where);
					$sql 	= $this->db->get("regular_schedule");
					$row 	= $sql->row();
						
					if($row){
						$h 						= $is_work->assumed_breaks * 60;
						$payroll_sched_timein 	= date('Y-m-d H:i:s',strtotime($gDate." ".$row->work_start_time)) ;
						$payroll_sched_endtime 	= date('Y-m-d H:i:s',strtotime($gDate." ".$row->work_end_time)) ;
						$orig_lunch_outb 		= date('Y-m-d H:i:s',strtotime($payroll_sched_timein. " +{$h} minutes"));
						$orig_lunch_inb			= date('Y-m-d H:i:s',strtotime($orig_lunch_outb. " +{$break_orig} minutes"));
						$orig_lunch_outb2		= strtotime($orig_lunch_outb);
						$orig_lunch_inb2		= strtotime($orig_lunch_inb);
						$payroll_sched_endtime2	= strtotime($payroll_sched_endtime);
						if($row->latest_time_in_allowed != NULL || $row->latest_time_in_allowed != ""){								
							$val 					= $row->latest_time_in_allowed;
							$payroll_sched_timeinb 	= date('Y-m-d H:i:s',strtotime($payroll_sched_timein  ." +{$val} minutes" ));
							$payroll_sched_endtimeb = date('Y-m-d H:i:s',strtotime($payroll_sched_endtime  ." +{$val} minutes" ));
							$payroll_sched_timeinb2	= strtotime($payroll_sched_timeinb);
							$payroll_sched_timeinb1	= strtotime($payroll_sched_timein);
							$time_inb2				= strtotime($time_in);
								
							// overbreak will always zero in assume
							$overbreak_min 		= 0;
							
							// if timein after the latest timein plus(+) threshold mins >>
							$late_min 			= 0;
							$tardiness 			= 0;
							if($time_inb2 >= $payroll_sched_timeinb2 && $time_inb2 < $orig_lunch_outb2){
								
								$payroll_sched_timein 	= $payroll_sched_timeinb;
								$payroll_sched_endtime 	= $payroll_sched_endtimeb;
				
								// compute for late min and tardiness
								$fromx				= date('Y-m-d H:i',strtotime($payroll_sched_timein));
								$tox 				= date('Y-m-d H:i',strtotime($time_in));
								$totalx 			= strtotime($tox) - strtotime($fromx);
								$hoursx				= floor($totalx / 60 / 60);
								$minutesx   	 	= floor(($totalx - ($hoursx * 60 * 60)) / 60);
								$late_min 			= (($hoursx * 60) + $minutesx) - $grace_period;
								$tardiness 			= $late_min + $overbreak_min;
							}
							// if timein between the latest timein && latest timein plus(+) threshold mins >>
							else if($time_inb2 >= $payroll_sched_timeinb1 && $time_inb2 < $payroll_sched_timeinb2){
								$payroll_sched_timein 	= $time_in;
								$payroll_sched_endtime 	= $payroll_sched_endtime2 + ($time_inb2 - $payroll_sched_timeinb1);
								$payroll_sched_endtime 	=  date('Y-m-d H:i:s',$payroll_sched_endtime);
							}
							
							// if timein between the original break to original end break
							else if($time_inb2 >= $orig_lunch_outb2 && $time_inb2 <= $orig_lunch_inb2){
								$payroll_sched_timein = $payroll_sched_timein;
								
								// compute for late min and tardiness
								$fromx				= date('Y-m-d H:i',strtotime($payroll_sched_timein));
								$tox 				= date('Y-m-d H:i',strtotime($orig_lunch_outb));
								$totalx 			= strtotime($tox) - strtotime($fromx);
								$hoursx				= floor($totalx / 60 / 60);
								$minutesx   	 	= floor(($totalx - ($hoursx * 60 * 60)) / 60);
								$late_min 			= (($hoursx * 60) + $minutesx);
								$tardiness 			= $late_min + $overbreak_min;
							}
							else if($time_inb2 > $orig_lunch_inb2){
								$payroll_sched_timein = $payroll_sched_timein;
								// compute for late min and tardiness
								$fromx				= date('Y-m-d H:i',strtotime($payroll_sched_timein));
								$tox 				= date('Y-m-d H:i',strtotime($time_in));
								$totalx 			= strtotime($tox) - strtotime($fromx);
								$hoursx				= floor($totalx / 60 / 60);
								$minutesx   	 	= floor(($totalx - ($hoursx * 60 * 60)) / 60);
								$late_min 			= (($hoursx * 60) + $minutesx) - $break_orig;
								$tardiness 			= $late_min + $overbreak_min;
							}
						}
						
						$lunch_outb 	= date('Y-m-d H:i:s',strtotime($payroll_sched_timein. " +{$h} minutes"));
						$lunch_inb		= date('Y-m-d H:i:s',strtotime($lunch_outb. " +{$break_orig} minutes"));
						$lunch_out		= $lunch_outb;
						$lunch_in		= $lunch_inb;
						$new_time_in	= $time_in;
						$new_time_out	= $time_out;
						$breakb 		= $break_orig;
						
						$lunch_outb2	= strtotime($lunch_outb);
						$lunch_inb2		= strtotime($lunch_inb);
						$time_in2		= strtotime($time_in);
						$time_out2		= strtotime($time_out);
						
						// if timein between break, break to end break
						if($time_in2 >= $lunch_outb2 && $time_in2 <= $lunch_inb2){
							$new_time_in 	= $lunch_inb;
							$lunch_out 		= null;
							$lunch_in 		= null;
							$breakb 		= 0;
						}
						// if timeout between break, break to end break
						if(($time_out2 <= $lunch_inb2) && ($time_out2 >= $lunch_outb2)){
							$new_time_out 	= $lunch_outb;
							$lunch_out 		= null;
							$lunch_in 		= null;
							$breakb 		= 0;
						}
						// if timein after break
						if($time_in2 > $lunch_inb2){
							$lunch_out 		= null;
							$lunch_in 		= null;
							$breakb 		= 0;
						}
						// if timeout before break
						if($time_out2 < $lunch_outb2){
							$lunch_out 		= null;
							$lunch_in 		= null;
							$breakb 		= 0;
						}
					}
					
					$fromx				= date('Y-m-d H:i',strtotime($new_time_in));
					$tox 				= date('Y-m-d H:i',strtotime($new_time_out));
					$totalx 			= strtotime($tox) - strtotime($fromx);
					$hoursx				= floor($totalx / 60 / 60);
					$minutesx   	 	= floor(($totalx - ($hoursx * 60 * 60)) / 60);
					$hours_worked_new1 	= ((($hoursx * 60) + $minutesx) - $breakb)/60;
						
					$total_hours_worked = $hours_worked_new1;
					
					/*** compute for undertime ***/

					//*** >> work end time is '>' greater than time out
					if($payroll_sched_endtime2 > $time_out2){
						
						$fromx				= date('Y-m-d H:i',strtotime($new_time_out));
						$tox 				= date('Y-m-d H:i',strtotime($payroll_sched_endtime));
						$totalx 			= strtotime($tox) - strtotime($fromx);
						$hoursx				= floor($totalx / 60 / 60);
						$minutesx   	 	= floor(($totalx - ($hoursx * 60 * 60)) / 60);
						$undertime1 		= ($hoursx * 60) + $minutesx;
						
						//***>> if timeout is less than '<' lunchin break
						if($time_out2 <= $lunch_inb2){
							$undertime1		= $undertime1 - $break_orig;
						}
						$undertime = $undertime1;
					}else{
						
						if($total_hours_worked < $hours_worked){
							if($time_inb2 >= $payroll_sched_timeinb2 && $time_inb2 < $orig_lunch_outb2){
								$total_tard_base_undertime = ($total_hours_worked + (($tardiness + $grace_period)/60));
							}else{
								$total_tard_base_undertime = ($total_hours_worked + ($tardiness/60));
							}
							
							$undertime = ($hours_worked - $total_tard_base_undertime) * 60;
							$undertime = ($undertime) ? $undertime : 0;
						}
					}
				}
				
				/*** end BARACK NEW TOTAL HOURS WORKED  ***/
				
				if($employee_time_in_id){
					$time_query_where = array(
							'employee_time_in_id' 	=> $employee_time_in_id,
							'status' 				=> "Active"
					);
				}
				else{
					$time_query_where = array(
							"comp_id"	=> $comp_id,
							"emp_id"	=> $emp_id,
							"date"		=> $currentdate,
							'status' 	=> "Active"
					);
				}
				
				$this->db->where(" (time_in_status ='approved' OR time_in_status IS NULL) ",NULL,FALSE);
				$this->db->where($time_query_where);
				$time_query 	= $this->edb->get('employee_time_in');
				$time_query_row = $time_query->row();
				$time_query->free_result();
				
				// enable capture from shift
				$capture = $this->enable_capture($work_schedule_id);
				
				$late_min 	= ($late_min  > 0) ? $late_min : 0;
				$tardiness 	= ($tardiness > 0) ? $tardiness : 0;
				$undertime 	= ($undertime > 0) ? $undertime : 0;
				
				if($time_query_row){
					if($source == "updated" || $source == "recalculated" || $source == "import"){
						if($time_query_row->flag_regular_or_excess == "excess"){
							$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
							$get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
							$tq_update_field = array(
									"source"					=> 'dashboard',
									"comp_id"					=> $company_id,
									"emp_id"					=> $emp_id,
									"date"						=> $currentdate,
									"time_in"					=> $new_time_in,
									"time_out"					=> $new_time_out,
									"undertime_min"				=> 0,
									"tardiness_min" 			=> 0,
									"late_min" 					=> 0,
									"overbreak_min" 			=> 0,
									"absent_min" 				=> 0,
									"work_schedule_id" 			=> "-2",
									"total_hours"				=> $get_total_hours,
									"total_hours_required"		=> $get_total_hours,
									"flag_regular_or_excess" 	=> "excess",
							);
						}else{
							if($log_error){
								$tq_update_field = array(
										"time_in"				=> $time_in,
										"lunch_out"				=> $lunch_out,
										"lunch_in"				=> $lunch_in,
										"time_out"				=> $time_out,
										"date" 					=> $currentdate,
										"work_schedule_id"		=> $work_schedule_id,
										"undertime_min"			=> 0,
										"tardiness_min" 		=> 0,
										"total_hours"			=> 0,
										"total_hours_required"	=> 0,
										"absent_min" 			=> 0,
										"overbreak_min" 		=> 0,
										"late_min" 				=> 0
								);
							}
							else{
								$tq_update_field = array(
										"time_in"				=> $time_in,
										"lunch_out"				=> $lunch_out,
										"lunch_in"				=> $lunch_in,
										"time_out"				=> $time_out,
										"date" 					=> $currentdate,
										"work_schedule_id"		=> $work_schedule_id,
										"undertime_min"			=> $undertime,
										"tardiness_min" 		=> $tardiness ,
										"total_hours"			=> $hours_worked,
										"late_min" 				=> $late_min,
										"overbreak_min" 		=> $overbreak_min,
										"total_hours_required"	=> $total_hours_worked,
										"change_log_date_filed" => date('Y-m-d H:i:s'),
										"flag_on_leave" 		=> $ileave
								);
						
								if(!$is_work){
									if($tardiness_absent){
										$tq_update_field["tardiness_min"] 	= $tardiness - $breakz;
										$tq_update_field["absent_min"] 		= 0;
										$tq_update_field["late_min"] 		= $tardiness - $breakz;
									}
									elseif($undertime_absent){
										$tq_update_field["absent_min"] 		= 0;
										$tq_update_field["undertime_min"]  	=  $undertime - $breakz; 
									}
									else{
										$tq_update_field["absent_min"] 		= 0;
									}
								}
							}
							
							if($time_query_row->source!=""){
								$tq_update_field['last_source'] = $source;
							}
							
							// ***** NEW COMPUTATION FOR ATTENDANCE SETTINGS
							$att = is_attendance_active($comp_id);
							
							if($att){
								if($total_hours_worked <= $att){
									if($time_in >= $lunch_out){
										$tq_update_field['lunch_out'] 	= null;
										$tq_update_field['lunch_in'] 	= null;
									}
									elseif($time_out <= $lunch_in){
										$tq_update_field['lunch_out'] 	= null;
										$tq_update_field['lunch_in'] 	= null;
									}
									
									$half_day_h = ($hours_worked / 2) * 60;
									if($late_min < $half_day_h){
										$tq_update_field['late_min'] 		= $late_min;
										$tq_update_field['tardiness_min'] 	= $tardiness;
										$tq_update_field['undertime_min'] 	= 0;
										$tq_update_field['absent_min'] 		= $undertime; // (($hours_worked - $total_hours_worked) * 60) - $tardiness;
									}
									else{
										$tq_update_field['late_min'] 		= 0;
										$tq_update_field['tardiness_min'] 	= 0;
										$tq_update_field['undertime_min'] 	= $undertime;
										$tq_update_field['absent_min'] 		= $tardiness; // (($hours_worked - $total_hours_worked) * 60) - $undertime;
									}
									$tq_update_field['total_hours_required'] 	= $total_hours_worked;
								}
							}
						}
						
						
						$this->db->where($time_query_where);
						$this->db->update('employee_time_in',$tq_update_field);
					}else{
						$get_total_hours = (strtotime($time_out) - strtotime($time_in)) / 3600;
						$get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
						
						$date_insert = array(
								"source"					=> 'Dashboard',
								"comp_id"					=> $comp_id,
								"emp_id"					=> $emp_id,
								"date"						=> $currentdate,
								"time_in"					=> $time_in,
								"time_out"					=> $time_out,
								"undertime_min"				=> 0,
								"tardiness_min" 			=> 0,
								"late_min" 					=> 0,
								"overbreak_min" 			=> 0,
								"work_schedule_id" 			=> "-2",
								"total_hours"				=> $get_total_hours,
								"total_hours_required"		=> $get_total_hours,
								"flag_regular_or_excess" 	=> "excess",
						);
						$add_logs = $this->db->insert('employee_time_in', $date_insert);
					}
				}
				else{
					if($log_error){
						$date_insert = array(
								"comp_id"				=> $comp_id,
								"emp_id"				=> $emp_id,
								"date"					=> $currentdate ,
								"source"				=> $source,
								"time_in"				=> $time_in,
								"lunch_out"				=> $lunch_out,
								"lunch_in"				=> $lunch_in,
								"time_out"				=> $time_out,
								"tardiness_min"			=> 0,
								"undertime_min"			=> 0,
								"late_min" 				=> 0,
								"overbreak_min" 		=> 0,
								"total_hours"			=> 0,
								"total_hours_required"	=> 0
						);
					}
					else{
						$date_insert = array(
							"comp_id"					=> $comp_id,
							"emp_id"					=> $emp_id,
							"date"						=> $currentdate,
							"source"					=> $source,
							"time_in"					=> $time_in,
							"lunch_out"					=> $lunch_out,
							"lunch_in"					=> $lunch_in,
							"time_out"					=> $time_out,
							"work_schedule_id"			=> $work_schedule_id,
							"undertime_min"				=> $undertime,
							"tardiness_min" 			=> $tardiness,
							"late_min" 					=> $late_min,
							"overbreak_min" 			=> $overbreak_min,
							"total_hours"				=> $hours_worked ,
							"total_hours_required"		=> $total_hours_worked,
							"flag_on_leave" 			=> $ileave
						);
						
						if(!$is_work){
							
							if($tardiness_absent){
								$date_insert["tardiness_min"] 	= $tardiness - $breakz;
								$date_insert["absent_min"] 		= 0;
								$date_insert["late_min"] 		= $tardiness - $breakz;
							}
							elseif($undertime_absent){
								$date_insert["undertime_min"]  	= $undertime - $breakz; 
								$date_insert["absent_min"] 		= 0;
							}
							else{
								$date_insert["absent_min"] 		= 0;
							}
						}
						
						// ***** NEW COMPUTATION FOR ATTENDANCE SETTINGS
						$att = is_attendance_active($comp_id);
						/****/
						if($att){
							if($total_hours_worked <= $att){
								if($time_in >= $lunch_out){
									$date_insert['lunch_out'] 	= null;
									$data_insert['lunch_in'] 	= null;
								}
								elseif($time_out <= $lunch_in){
									$date_insert['lunch_out'] 	= null;
									$date_insert['lunch_in'] 	= null;
								}
								
								$half_day_h = ($hours_worked / 2) * 60;
								if($late_min < $half_day_h){
									$date_insert['late_min'] 		= $late_min;
									$date_insert['tardiness_min'] 	= $tardiness;
									$date_insert['undertime_min'] 	= 0;
									$date_insert['absent_min'] 		= (($hours_worked - $total_hours_worked) * 60) - $tardiness;
								}
								else{
									$date_insert['late_min'] 		= 0;
									$date_insert['tardiness_min'] 	= 0;
									$date_insert['undertime_min'] 	= $undertime;
									$date_insert['absent_min'] 		= (($hours_worked - $total_hours_worked) * 60) - $undertime;
								}
								$date_insert['total_hours_required'] 	= $total_hours_worked;
							}
						}
					}
					$this->db->insert('employee_time_in', $date_insert);
				}
			}
		}
		return TRUE;
	}
	
	/**
	 * Check if user is half day in work
	 * @param unknown $work_schedule_id
	 * @param unknown $comp_id
	 * @param unknown $row_time_in
	 * @param unknown $row_time_out
	 * @param unknown $emp_no
	 * @param unknown $undertime
	 * @param unknown $tardiness
	 * @param unknown $clockin - pass data from clockin
	 * @return StdClass
	 */
	public function absent_minutes_calc($work_schedule_id,$comp_id,$row_time_in,$row_time_out,$emp_no,$undertime,$tardiness,$clockin = true,$gDate = "",$assumed_breaks_flex_b = false){
			
		$data 				=  array();
		$currentdate 		= date('Y-m-d',strtotime($row_time_in));
		$data['half_day']	= 0;
		if($clockin){
			$vx = $this->activate_nightmare_trap_upload($comp_id,$emp_no,$row_time_in,$row_time_out);
			if($vx){
				$currentdate = $vx['current_date'];
			}
		}
		else{ 
			$vx = $this->activate_nightmare_trap($comp_id, $emp_no);
			if($vx){
				$currentdate = $vx['currentdate'];
			}
		}
		
		if($gDate){
			$currentdate = $gDate;
		}
		
		$day 		= date("l",strtotime($currentdate));
		$uni_where 	= array(
					"work_schedule_id"	=> $work_schedule_id,
					"company_id"		=> $comp_id,
					"days_of_work"		=> $day,
					"status" 			=> 'Active'
					);
		$this->db->where($uni_where);
		$sql = $this->db->get("regular_schedule");
		$row = $sql->row();
		
		
		if($row_time_out !=""){
			if($row){
				$break = $row->break_in_min;
				if($row->latest_time_in_allowed != NULL){
					$val 					= $row->latest_time_in_allowed;
					$payroll_sched_timein 	= date('H:i:s',strtotime($row->work_start_time . " +{$val} minutes" )) ;
					$start_time 			= date('Y-m-d H:i:s',strtotime($currentdate. " ".$payroll_sched_timein));
				}
				else{
					$start_time = date('Y-m-d H:i:s',strtotime($currentdate. " ".$row->work_start_time));
				}
			
				$th = round(($row->total_work_hours / 2) * 60);
				$end_time = date('Y-m-d H:i:s',strtotime($currentdate. " ".$row->work_end_time));
				
				//night shift
				$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
				
				if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($row->work_end_time)) == "AM"){
					$start_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$row->work_start_time));
					$end_time 	=  date('Y-m-d H:i:s', strtotime($currentdate." ".$row->work_end_time." +1 day"));
				}
					
				$total      = strtotime($end_time) - strtotime($start_time);
				$hours      = floor($total / 60 / 60);
				$minutes    = floor(($total - ($hours * 60 * 60)) / 60);
				$x 			=  (($hours * 60) + $minutes)/2 ;
				
				//detect the halfday with no breaks
				$half_date 		= date('Y-m-d H:i:s',strtotime($start_time. " +".$th." minutes"));
				$half_date_end 	= date('Y-m-d H:i:s',strtotime($half_date. " +".$break." minutes"));
				
				//detect the halfday with breaks
				$break_deduct = false;
				
				//for tardiness
				$half_break_start 	= date('Y-m-d H:i:s',strtotime($start_time. " +".$x." minutes"));
				$half_break_end 	= date('Y-m-d H:i:s',strtotime($half_break_start. "+".$break ." minutes"));
				
				//for undertime
				$half_break_start2 	= date('Y-m-d H:i:s',strtotime($start_time. " -".$x." minutes"));
				$half_break_end2	= date('Y-m-d H:i:s',strtotime($half_break_start2. " -".$break ." minutes"));
				$break_render 		= 0;
				$half_break_total 	= $this->total_hours_worked($half_date, $start_time);
	
				if($row_time_in > $half_date && $row_time_in <= $half_date_end){
					$break_deduct = true;
					$break_render = $this->total_hours_worked($row_time_in, $half_date);
				}
				
				if($row_time_out >= $half_break_start2 && $row_time_out <= $half_break_end2){
					$break_deduct = true;
				}

				if($row_time_in > $half_date){
					// tardiness	
					$data['half_day'] = 1;
			
					// calculate the half day of absence
					// if the row_time_in is within the break time
					if($break_deduct){				
						$data['tardiness'] = $half_break_total;
					}else{
						// calculate the row_time_in is greater than the break time
						$data['tardiness'] = ($tardiness == 0) ? 0 :  $tardiness - $break ;
					}
				}else if(($row_time_out <= $half_date_end) && $clockin){
					// undertime
					$data['half_day'] = 2;
					
					if($break_deduct){
						$data['undertime'] = $half_break_total;
					}
					else{
						$data['undertime'] = ($undertime == 0) ? 0 : $undertime - $break;
					}					
				}else{
					$data['half_day']=0;
				}
				
			}else{
				$arrx4 = array(
						'duration_of_lunch_break_per_day',
						'total_hours_for_the_day',
						'latest_time_in_allowed'
				);
				$this->edb->select($arrx4);
				$w_fh = array(
						//"payroll_group_id"=>$payroll_group,
						"work_schedule_id"=>$work_schedule_id,
						"company_id"=>$comp_id
				);
				$this->db->where($w_fh);
				$q_fh = $this->edb->get("flexible_hours");
				$r_fh = $q_fh->row();
				
				if($q_fh->num_rows() > 0){
					$number_of_breaks_per_day = $r_fh->duration_of_lunch_break_per_day;
					$total_hours_of_the_day = ($r_fh->total_hours_for_the_day * 60) - $number_of_breaks_per_day;
						
					$x =  $total_hours_of_the_day / 2 ;
				
					if($r_fh->latest_time_in_allowed != NULL){
				
						$start_time = date('Y-m-d H:i:s',strtotime($currentdate." ".$r_fh->latest_time_in_allowed));
						$end_time = date('Y-m-d H:i:s',strtotime($start_time .' +'.$total_hours_of_the_day.' minutes'));
						$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
						
						$xcheck = false;
						if($row_time_in < $start_time){
							$end_time = date('Y-m-d H:i:s',strtotime($row_time_in. " +{$total_hours_of_the_day} minute"));							
							$xcheck = true;
						}
						
						$th = (($r_fh->total_hours_for_the_day / 2 ) * 60) - ($number_of_breaks_per_day/2);
						if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM"  && $row_time_in >= $mid_night){							
							$end_time =  date('Y-m-d H:i:s', strtotime($end_time ." +1 day"));							
						}

						// detect the halfday with no breaks
						if($xcheck)
							$half_date = date('Y-m-d H:i:s',strtotime($row_time_in . " +".$th." minutes"));
						else
							$half_date = date('Y-m-d H:i:s',strtotime($start_time. " +".$th." minutes"));
						
						
						$half_date_end = date('Y-m-d H:i:s',strtotime($half_date. " +".$number_of_breaks_per_day." minutes"));
						
						$break_deduct = false;
						$half_break_total = $this->total_hours_worked($half_date, $start_time);
						
						if($row_time_in > $half_date && $row_time_in <= $half_date_end){
							$break_deduct = true;
							$break_render = $this->total_hours_worked($row_time_in, $half_date);
						}
							
						if($row_time_out >= $half_date && $row_time_out <= $half_date_end){
							$break_deduct = true;
						}
						
						if($row_time_in > $half_date){
							// tardiness
							$data['half_day'] = 1;
							if($break_deduct){					
								$data['tardiness'] = $half_break_total;
							}else
								$data['tardiness'] = $tardiness - $number_of_breaks_per_day ;
						
						}else if((($row_time_out <= $half_date_end) && $clockin) && !$assumed_breaks_flex_b){
							// undertime
							$data['half_day'] = 2;
							if($break_deduct){
								$data['undertime'] = $half_break_total;
							}else
								$data['undertime'] = $undertime;
						}else{
							$data['half_day']=0;
						
						}
					}
					else{
						$start_time = $row_time_in;
						$end_time = date('Y-m-d H:i:s',strtotime($start_time .' +'.$total_hours_of_the_day.' minutes'));
						$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
						
						$xcheck = false;
						if($row_time_in < $start_time){
							$end_time = date('Y-m-d H:i:s',strtotime($row_time_in. " +{$total_hours_of_the_day} minute"));
							$xcheck = true;
						}
						
						$th = (($r_fh->total_hours_for_the_day / 2 ) * 60) - ($number_of_breaks_per_day/2);
						if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM"  && $row_time_in >= $mid_night){
							$end_time =  date('Y-m-d H:i:s', strtotime($end_time ." +1 day"));
						}
						
						// detect the halfday with no breaks
						if($xcheck){
							$half_date = date('Y-m-d H:i:s',strtotime($row_time_in . " +".$th." minutes"));
						}else{
							$half_date = date('Y-m-d H:i:s',strtotime($start_time. " +".$th." minutes"));
						}
					
						$half_date_end = date('Y-m-d H:i:s',strtotime($half_date. " +".$number_of_breaks_per_day." minutes"));
				
						$break_deduct = false;
						$half_break_total = $this->total_hours_worked($half_date, $start_time);
				
						if($row_time_in > $half_date && $row_time_in <= $half_date_end){
							$break_deduct = true;
							$break_render = $this->total_hours_worked($row_time_in, $half_date);
						}
							
						if($row_time_out >= $half_date && $row_time_out <= $half_date_end){
							$break_deduct = true;
						}
				
						if($row_time_in > $half_date){
							// tardiness
							$data['half_day'] = 1;
							if($break_deduct){
								$data['tardiness'] = $half_break_total;
							}else
								$data['tardiness'] = $tardiness - $number_of_breaks_per_day ;
				
						}
						else if(($row_time_out <= $half_date_end) && $clockin){
							// undertime
							$data['half_day'] = 2;
							if($break_deduct){
								$data['undertime'] = $half_break_total;
							}
							else{
								$data['undertime'] = $undertime;
							}
						}
						else{
							$data['half_day'] = 0;
						}			
					}
				}
			}
		}
		
		$active = is_attendance_active($comp_id);
		
		return (object) $data;
	}

	/**
	 * Halfday Check Workday
	 * @param unknown_type $work_schedule_id
	 * @param unknown_type $company_id
	 * @param unknown_type $new_date
	 */
	public function halfday_check_workday($work_schedule_id,$company_id,$new_date){
		// uniform_working_day
		$w = array(
			"work_schedule_id"=>$work_schedule_id,
			"company_id"=>$company_id,
			"days_of_work"=>date("l",strtotime($new_date))
		);
		$this->db->where($w);
		$q = $this->db->get("regular_schedule");
		$r = $q->row();
		
		if($r){
			$ds = date("A",strtotime($r->work_start_time));
			$de = date("A",strtotime($r->work_end_time));
			return ($ds == "PM" && $de == "AM") ? TRUE : FALSE ;
		}else{
 			return FALSE;
		}
	}
	
	/**
	 * Update Employee Time In Logs
	 * @param unknown_type $emp_id
	 * @param unknown_type $employee_time_in_id
	 */
	public function update_change_logs($comp_id, $emp_id, $employee_time_in_id, $reason, $time_in,$lunch_out,$lunch_in,$time_out, $hours_worked,$work_schedule_id,$new_source="",$late_min=0,$overbreak_min=0,$emp_schedule_date = null){			
		if($time_in != NULL){
			if($emp_schedule_date == null) {
				$day = date("l",strtotime($time_in));
			} else {
				$day = date("l",strtotime($emp_schedule_date));
			}
			
			$break = 0;
			$w_uwd = array(
					//"payroll_group_id"=>$payroll_group,
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id,
					"days_of_work"=> $day,
					"status" => 'Active'
			);
			$this->db->where($w_uwd);
			$q_uwd = $this->db->get("regular_schedule");
			$r_uwd = $q_uwd->row();
			
			if($q_uwd->num_rows > 0){ 
				$break = $r_uwd->break_in_min; 
				
				$total_hrs_inc_break = $r_uwd->total_work_hours + ($break / 60);
				$total_hrs_inc_break = $total_hrs_inc_break * 60;
				$work_start_time = $emp_schedule_date.' '.$r_uwd->work_start_time;
				$time_out_date = date('Y-m-d H:i:s', strtotime($work_start_time.' +'.$total_hrs_inc_break.' minutes'));
				$start_datetime_thres = date('Y-m-d H:i:s', strtotime($work_start_time.' +'.$r_uwd->latest_time_in_allowed.' minutes'));
				$work_end_time = $r_uwd->work_end_time;
				if(strtotime($time_in) > strtotime($start_datetime_thres)) {
					$start_datetime = $start_datetime_thres;
					$end_datetime = date('Y-m-d', strtotime($time_out_date)).' '.$work_end_time;
					$end_datetime = date('Y-m-d H:i:s', strtotime($end_datetime.' +'.$r_uwd->latest_time_in_allowed.' minutes'));
				} elseif(strtotime($work_start_time) <= strtotime($time_in) && strtotime($start_datetime_thres) >= strtotime($time_in)) {
					$diff = (strtotime($time_in) - strtotime($work_start_time)) / 60;
					$start_datetime = date('Y-m-d H:i:s', strtotime($work_start_time.' +'.$diff.' minutes'));
					$end_datetime = date('Y-m-d', strtotime($time_out_date)).' '.$work_end_time;
					$end_datetime = date('Y-m-d H:i:s', strtotime($end_datetime.' +'.$diff.' minutes'));
				} else {
					$end_datetime = date('Y-m-d', strtotime($time_out_date)).' '.$work_end_time;
					$end_datetime = date('Y-m-d H:i:s', strtotime($end_datetime));
					$start_datetime = date('Y-m-d H:i:s', strtotime($work_start_time));
				}
			} else {
				$start_datetime_thres = "";
				$start_datetime = "";
				$end_datetime = "";
			}
			// tardiness
			#$tardiness = $this->get_tardiness_import($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $work_schedule_id, $break);
			if(strtotime($time_in) > strtotime($start_datetime_thres)) {
				$tardiness = (strtotime($time_in) - strtotime($start_datetime)) / 60;
			} else {
				$tardiness = 0;
			}
			
			// undertime
			#$undertime = $this->get_undertime_import($emp_id, $comp_id, $time_in, $time_out, $lunch_out, $lunch_in, $work_schedule_id, $break);
			if(strtotime($time_out) > strtotime($end_datetime)) {
				$undertime = 0;
			} else {
				$undertime = (strtotime($end_datetime) - strtotime($time_out)) / 60;
			}
			
			// total hours worked
			#$total_hours_worked = $this->get_tot_hours_limit($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out, $work_schedule_id, $break);
			
			// total hours worked view
			$total_hours_worked_view = $this->get_tot_hours($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out, $hours_worked, $work_schedule_id);
			
			$get_attendance_total_work_hours_req =  $this->get_tot_hours_limit($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out, $work_schedule_id, $break, $emp_schedule_date);
			
			$get_attendance_total_work_hours = $this->get_attendance_total_work_hours($emp_id,$comp_id,date("Y-m-d",strtotime($emp_schedule_date)),$work_schedule_id);
		 	
			/** added: fritz - START **/
			$fritz_tardiness 		= $late_min + $overbreak_min;
			$undertime_to_hrs 		= $undertime / 60;
			$new_fritz_tardiness 	= $fritz_tardiness / 60;
			#$fritz_total_hours 		= $get_attendance_total_work_hours - $new_fritz_tardiness - $undertime_to_hrs;
			$fritz_total_hours 		= $get_attendance_total_work_hours_req;
			/** added: fritz - END **/
			
			$where_tot = array(
				"comp_id"=>$comp_id,
				"emp_id"=>$emp_id,
				"employee_time_in_id"=>$employee_time_in_id,
				"status" => "Active"
			);
			
			$this->db->where($where_tot);

			if($new_source != "") {
				$data = array(
						"date"								=> $emp_schedule_date,
						"time_in_status"					=> 'pending',
						"corrected"							=> 'Yes',
						"reason"							=> $reason,
						"absent_min" 						=> 0,
						"change_log_date_filed"				=> date("Y-m-d H:i:s"),
						"change_log_time_in"				=> $time_in,
						"change_log_lunch_out"				=> $lunch_out,
						"change_log_lunch_in"				=> $lunch_in,
						"change_log_time_out"				=> $time_out,
						"change_log_tardiness_min"			=> $fritz_tardiness,
						"change_log_undertime_min"			=> $undertime,
						"change_log_total_hours_required"	=> $fritz_total_hours,
						"change_log_total_hours"			=> $get_attendance_total_work_hours,
						$new_source							=> "Adjusted",
						"late_min" 							=> $late_min,
						"overbreak_min"						=> $overbreak_min
				);
			} else {
				$data = array(
						"date"								=> $emp_schedule_date,
						"time_in_status"					=> 'pending',
						"corrected"							=> 'Yes',
						"reason"							=> $reason,
						"absent_min" 						=> 0,
						"change_log_date_filed"				=> date("Y-m-d H:i:s"),
						"change_log_time_in"				=> $time_in,
						"change_log_lunch_out"				=> $lunch_out,
						"change_log_lunch_in"				=> $lunch_in,
						"change_log_time_out"				=> $time_out,
						"change_log_tardiness_min"			=> $fritz_tardiness,
						"change_log_undertime_min"			=> $undertime,
						"change_log_total_hours_required"	=> $fritz_total_hours,
						"change_log_total_hours"			=> $get_attendance_total_work_hours,
						"late_min" 							=> $late_min,
						"overbreak_min"						=> $overbreak_min
				);
			}
			
			$this->db->update('employee_time_in', $data);
			
			$new_w_arr = array(
					"comp_id"				=> $comp_id,
					"emp_id"				=> $emp_id,
					"employee_time_in_id" 	=> $employee_time_in_id
			);
			
			$this->db->where($new_w_arr);
			$que = $this->db->get("employee_time_in");
			$res = $que->row();
		}
		
		return ($res) ? $res : FALSE;
	}
	
	/**
	 * Update Employee Time In Logs
	 * @param unknown_type $emp_id
	 * @param unknown_type $employee_time_in_id
	 */
	public function get_total_hours_logs($comp_id, $emp_id, $employee_time_in_id, $reason, $time_in,$lunch_out,$lunch_in,$time_out, $hours_worked,$work_schedule_id,$emp_schedule_date=null){			
		
		if($time_in != NULL){
			// tardiness
			if($emp_schedule_date == null) {
				$day = date("l",strtotime($time_in));
			} else {
				$day = date("l",strtotime($emp_schedule_date));
			}
			
			$break = 0;
			$w_uwd = array(
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id,
					"days_of_work"=> $day,
					"status" => 'Active'
			);
			$this->db->where($w_uwd);
			$q_uwd = $this->db->get("regular_schedule");
			$r_uwd = $q_uwd->row();
			
			if($q_uwd->num_rows > 0){
				$break = $r_uwd->break_in_min;
			}else{
				$w_fh = array(
						"work_schedule_id"=>$work_schedule_id,
						"company_id"=>$comp_id
				);
				$this->db->where($w_fh);
				$q_fh = $this->db->get("flexible_hours");
				$r_fh = $q_fh->row();
				
				if($q_fh->num_rows() > 0){
					$break = $r_fh->duration_of_lunch_break_per_day;
						
				}
			}
			// tardiness
			$tardiness = $this->get_tardiness_import($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $work_schedule_id, $break);
			
			// undertime
			$undertime = $this->get_undertime_import($emp_id, $comp_id, $time_in, $time_out, $lunch_out, $lunch_in, $work_schedule_id, $break);
			
			// total hours worked
			$total_hours_worked = $this->get_tot_hours_limit($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out, $work_schedule_id, $break, $emp_schedule_date);
			
			// total hours worked view
			$total_hours_worked_view = $this->get_tot_hours($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out, $hours_worked, $work_schedule_id, $break);
			
			return $total_hours_worked;
		}else{
			return FALSE;	
		}
		
	}
	
	/****NEW CALCULATION HERE*****/
	public function get_break_time($com_id,$work_schedule_id){
		$w1 = array(
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$company_id
		);
		$this->db->where($w1);
		$q1 = $this->db->get("break_time");
		$r1 = $q1->row();
		
		return($r1)? $r1 : false;
	}
	
	/**
	 * Get tardiness
	 * @param unknown $schedule_block_id
	 * @param unknown $split_schedule_row
	 * @return boolean|number
	 */
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
		
			$to_time =  strtotime($r1->time_in);
			$time_inx = date('Y-m-d H:i:s',strtotime($this->get_starttime($r1->schedule_blocks_id, $r1->time_in)));				
			
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
	
	public function get_undertime($schedule_block_id,$split= array()){
		$w1 = array(
				"schedule_blocks_time_in_id"=>$schedule_block_id
		);
		$this->db->where($w1);
		$q1 = $this->db->get("schedule_blocks_time_in");
		$r1 = $q1->row();
		$minutes = 0;

		if($r1){
			$end = date('Y-m-d H:i:s',strtotime($this->get_endtime($r1->schedule_blocks_id, $r1->time_in)));
			$d_time_out = strtotime($end);
			$t_time_out = strtotime($r1->time_out);
			
			if($t_time_out < $d_time_out){
				$total_hours = $this->get_total_hours($schedule_block_id);
				$total_hours_req = $this->get_total_hours_req($schedule_block_id);
				$minutes = $this->total_hours_worked($end, $r1->time_out); 
				
			}
			else{
				$minutes = 0;
			}
			
		}else{
			return false;
		}
		return $minutes;
	}
	
	
	public function get_tardiness_with_breaks($schedule_block_id){
		
		$w1 = array(
				"schedule_blocks_time_in_id"=>$schedule_block_id
		);
		$this->db->where($w1);
		$q1 = $this->db->get("schedule_blocks_time_in");
		$r1 = $q1->row();
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
			$lunch_in = $r1->lunch_in;
			$lunch_out = $r1->lunch_out;
			$break_time =0;
			
			if($lunch_in != "" && $lunch_out !=""){
				$b_cal = $this->total_hours_worked($lunch_in, $lunch_out);
				$break_time = $this->convert_to_hours($b_cal);
			}
			
			$hours = $this->total_hours_worked($to, $from);
			
			$hours_render = $this->convert_to_hours($hours);
			
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
		$q1 		= $this->db->get("schedule_blocks_time_in");
		$r1 		= $q1->row();
		$hours		= 0;
		$w			= false;
		$minutes 	= 0;
		
		if($r1){
			$start 		= $this->get_starttime($r1->schedule_blocks_id,$r1->time_in);
			$end 		= $this->get_endtime($r1->schedule_blocks_id,$r1->time_in);
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
	
	
	public function get_time_list($emp_no,$work_schedule_id,$comp_id,$currentdate= null){
		$type 			= "";
		$check_emp_no 	= $this->login_screen_model->new_check_emp_info($emp_no,$comp_id);
		$w_date 		= array(
							"es.valid_from <="		=>	$currentdate,
							"es.until >="			=>	$currentdate
						);
		$this->db->where($w_date);
		
		$w_ws = array(
				"em.work_schedule_id"=>$work_schedule_id,
				"em.company_id"=>$comp_id,
				"em.emp_id" => $check_emp_no->emp_id
		);
		
		$this->db->where($w_ws);
		$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
		$q_ws = $this->edb->get("employee_sched_block AS em");
		$r_ws = $q_ws->result();
		
		if($q_ws->num_rows() > 0){
				$type = "split";		
		}
		$arrx = array(
				'time_in'	=> 'eti.time_in',
				'lunch_out' => 'eti.lunch_out',
				'lunch_in' 	=> 'eti.lunch_in',
				'time_out' 	=> 'eti.time_out'
		);
		$this->edb->select($arrx);
		$w = array(
				"a.payroll_cloud_id"	=> $emp_no,
				"a.user_type_id"		=> "5",
				"eti.status" 			=> "Active",
				"eti.comp_id" 			=> $comp_id
		);
		$this->edb->where($w);
		$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
		$this->db->order_by("eti.time_in","DESC");
		
		if($type == "split"){
			$q = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
			
			$r = $q->row();
			
			return ($q->num_rows() > 0) ? $q->row() : false;
		}
		else{
			$q = $this->edb->get("employee_time_in AS eti",1,0);
			
			$r = $q->row();
			
			#return ($q->num_rows() > 0 && $r->time_out == "")? $q->row() : false;
			return ($q->num_rows() > 0)? $q->row() : false;
		}	
		
	}
	
	/**
	 * display the list of time for today
	 * @param unknown $emp_no
	 * @param unknown $work_schedule_id
	 * @param unknown $comp_id
	 * @param string $currentdate
	 * @return boolean
	 */
	public function display_time_list($emp_no,$work_schedule_id,$comp_id,$currentdate= null){
	
		$type 			= "";
		$check_emp_no 	= $this->login_screen_model->new_check_emp_info($emp_no,$comp_id);
		
		$w_date = array(
				"es.valid_from <="	=> $currentdate,
				"es.until >="		=> $currentdate
		);
		$this->db->where($w_date);
	
		$w_ws = array(
				"em.work_schedule_id" 	=> $work_schedule_id,
				"em.company_id"			=> $comp_id,
				"em.emp_id" 			=> $check_emp_no->emp_id
		);
	
		$this->db->where($w_ws);
		$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
		$q_ws = $this->edb->get("employee_sched_block AS em");
		$r_ws = $q_ws->result();
	
		if($q_ws->num_rows() > 0){
			$type = "split";
		}
		
		$arrx = array(
				'time_in' 				=> 'eti.time_in',
				'lunch_out' 			=> 'eti.lunch_out',
				'lunch_in' 				=> 'eti.lunch_in',
				'time_out' 				=> 'eti.time_out',
				'employee_time_in_id' 	=> 'eti.employee_time_in_id',
				'emp_id' 				=> 'eti.emp_id'
		);
		$this->edb->select($arrx);
		$w = array(
				"a.payroll_cloud_id"	=> $emp_no,
				"a.user_type_id"		=> "5",
				"eti.status" 			=> "Active",
				"eti.comp_id" 			=> $comp_id
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
	
		return ($q->num_rows() > 0 )? $q->row() : false;
	}
	
	public function convert_to_hours($min){
		$h = date('H', mktime(0,$min));
		$m = date('i', mktime(0,$min));
		$m2 = ($m /60) * 100;
		$m2 = sprintf("%02d", $m2);
		$t = $h.".".$m2;
		return $t;
	}
	
	
	public function convert_to_min($min){
		$m = $min * 60;
		return $m;
	}
	
	public function is_pass_day($emp_no,$work_schedule_id,$comp_id){
		
		$w = array(
				"a.payroll_cloud_id"	=> $emp_no,
				"a.user_type_id"		=> "5",
				"eti.status" 			=> "Active"
		);
		$this->edb->where($w);
		$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
		$this->db->order_by("eti.time_in","DESC");
		$q 		= $this->edb->get("employee_time_in AS eti",1,0);
		$r 		= $q->row();
		
		$today 	= date('Y-m-d H:i:s');
		$number_of_breaks_per_day = 0;
		$w_uwd = array(
				"work_schedule_id"	=> $work_schedule_id,
				"company_id"		=> $comp_id,
				"status" 			=> 'Active'
				);
		$this->db->where($w_uwd);
		$q_uwd = $this->db->get("regular_schedule");
		$r_uwd = $q_uwd->row();
		
		if($q_uwd->num_rows() > 0){
			$work_start_time 	= $r_uwd->work_start_time;
			$work_end_time 		= $r_uwd->work_end_time;
			$total_work_hours 	= $r_uwd->total_work_hours;
			$break 				= $r_uwd->break_in_min;
		}
		
		if($break !=0){
			
			if($r->lunch_in !=""){
				$day 	= date('l',strtotime($r->time_in));
				$time 	= $r->time_in;
			}
			else if($r->lunch_out !=""){
				$day 	= date('l',strtotime($r->time_in));
				$time 	= $r->time_in;
			}
			else if($r->time_out != ""){
				$day 	= date('l');
				$time 	= $r->time_out;
			}
			else{
				$day 	= date('l',strtotime($r->time_in));
				$time 	= $r->time_in;
			}
		}
		
		$start_time = date('Y-m-d H:i:s',strtotime($work_start_time));
		
		$x = date('Y-m-d',strtotime($r->time_in));
		$s = date('Y-m-d',strtotime($r->time_in .' +1 day'));
		$w = date('Y-m-d H:i:s',strtotime($s. ' 00:00:00'));
		
	}
	
	
	/**
	 * Check Time Log
	 * @param unknown $date
	 * @param unknown $emp_no
	 * @param unknown $min_log
	 * @param unknown $work_schedule_id
	 * @param string $last_timeout
	 * @return boolean
	 */
	public function check_time_log($date,$emp_no,$min_log,$work_schedule_id,$last_timeout = false,$comp_id = 0){
		
		$current_date = date("Y-m-d H:i:s");
		$day = date('l',strtotime($date));
		
		// get employee information
		$arrx = array(
				'emp_id' 		=> 'e.emp_id',
				'company_id' 	=> 'e.company_id'
		);
		$this->edb->select($arrx);
		$w_emp = array(
				"a.payroll_cloud_id"	=> $emp_no,
				"a.user_type_id"		=> "5",
				"company_id" 			=> $comp_id
		);
		$this->edb->where($w_emp);
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$q_emp = $this->edb->get("employee AS e");
		$r_emp = $q_emp->row();
		
		$emp_id = $r_emp->emp_id;
		
		// CHECK WORK SCHEDULE
		$arrx2 = array(
				'work_scheduel_id'		=> 'eti.work_schedule_id',
				'time_in' 				=> 'eti.time_in',
				'time_out' 				=> 'eti.time_out',
				'date' 					=> 'eti.date',
				'employee_time_in_id'  	=> 'eti.employee_time_in_id'
		);
		$this->edb->select($arrx2);
		$w = array(
				"a.payroll_cloud_id"	=> $emp_no,
				"a.user_type_id"		=> "5",
				"eti.comp_id" 			=> $comp_id,
				"eti.status" 			=> "Active"
		);
		
		$this->edb->where($w);
		$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
		$this->db->order_by("eti.time_in","DESC");
		$q = $this->edb->get("employee_time_in AS eti",1,0);
		$r = $q->row();
		
		if(!$r){
			if($work_schedule_id == null){
				$work_schedule_id = $this->emp_work_schedule($emp_no,$comp_id);
			}
			$r = (object) array(
				'work_schedule_id' 		=> $work_schedule_id,
				'time_in' 				=> date('Y-m-d H:i:s'),
				'date' 					=> date('Y-m-d'),
				'employee_time_in_id' 	=> "",
			);
		}
		
		// check number of breaks
		$number_of_breaks_per_day = 0;
		$schedule ="";
		$start_time = "";
		
		# UNIFORM WORKING DAYS
		$arrx4 = array(
				'break_in_min',
				'work_start_time'
		);
		$this->edb->select($arrx4);
		$w_uwd = array(
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id,
				'days_of_work' => $day,
				"status" => "Active"
		);
		$this->edb->where($w_uwd);
		$q_uwd = $this->edb->get("regular_schedule");
		$r_uwd = $q_uwd->row();
		
		if($q_uwd->num_rows() > 0){
			$number_of_breaks_per_day = $r_uwd->break_in_min;
			$schedule="regular";
			$start_time = $r_uwd->work_start_time;
		}else{
			# FLEXIBLE HOURS
			$arrx5 = array(
					'duration_of_lunch_break_per_day'
			);
			$this->edb->select($arrx5);
			$w_fh 	= array(
					"work_schedule_id"	=> $work_schedule_id,
					"company_id"		=> $comp_id
					);
			$this->edb->where($w_fh);
			$q_fh = $this->edb->get("flexible_hours");
			$r_fh = $q_fh->row();
			
			if($q_fh->num_rows() > 0){
				$schedule					= "flexible";
				$number_of_breaks_per_day 	= $r_fh->duration_of_lunch_break_per_day;
			}
			else{
				$schedule="split";
				if($r){
					$eti = ($r->employee_time_in_id) ? $r->employee_time_in_id : 0;
					$s = array(
							'schedule_blocks_id'
					);
					$this->db->select($s);
					$w 	= array(
							"employee_time_in_id"	=> $eti,
							"comp_id"				=> $comp_id
					);
					$this->db->where($w);
					$this->db->order_by("schedule_blocks_time_in_id");
					$q = $this->db->get("schedule_blocks_time_in",1,0);
					$rx = $q->row();
					if($rx){
						$sbi = $rx->schedule_blocks_id;
						$s1 = array(
								'break_in_min'
						);
						$this->db->select($s1);
						$w1 	= array(
								"schedule_blocks_id"	=> $sbi,
								"company_id"			=> $comp_id
						);
						$this->db->where($w1);
						$q1 = $this->db->get("schedule_blocks",1,0);
						$rx1 = $q1->row();
						if($rx1){
							$number_of_breaks_per_day = ($rx1->break_in_min > 0) ? $rx1->break_in_min : '0';
						}
					}
				}
			}
		}

		$vx = $this->activate_nightmare_trap($comp_id, $emp_no);
		$workday = date("l",strtotime($r->date));
		
		if($vx){
			$workday = date("l",strtotime($vx['currentdate']));
		}
		
		// check if breaktime is 0
		if($number_of_breaks_per_day == 0 && $number_of_breaks_per_day!=NULL){
			// check employee time in
			$arrx7 = array(
					'time_in' 	=> 'eti.time_in',
					'time_out' 	=> 'eti.time_out',
					"date" 		=> 'eti.date'
			);
			$this->edb->select($arrx7);
			$w = array(
					"a.payroll_cloud_id"=> $emp_no,
					"a.user_type_id"	=> "5",
					"eti.comp_id" 		=> $comp_id,
					"eti.status" 		=> "Active"
			);
			$this->edb->where($w);
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
			$this->db->order_by("eti.time_in","DESC");
			$q = $this->edb->get("employee_time_in AS eti",1,0);
			$r2 = $q->row();
			
			if(!$r2){
				$r2 = (object) array(
						'time_in' 	=> "",
						'time_out' 	=> ""
				);
			}
			
			// check workday settings
			$workday_settings_start_time = $this->check_workday_settings_start_time($workday,$work_schedule_id,$comp_id);
			$workday_settings_end_time = $this->check_workday_settings_end_time($workday,$work_schedule_id,$comp_id);
			
			/*
			 * gamita ni para mobalik sa timein ang clock in kng nalimot mn gani ang pagtime out
			 * for regular schedule rani
			 */
			
			if($schedule=="regular"){	
				$refresh = (isset($vx['refresh'])) ? $vx['refresh'] : true;
				
			 	if(!$vx || $refresh === false){
					$change_date = date('Y-m-d',strtotime($r->date." +1 day"));
			
					$workday 						= date('l',strtotime($change_date)); 
					$work_schedule_id_go 			= $this->get_tomorrow_shift($emp_id, $comp_id, $change_date);
					$workday_settings_start_time_me = $this->check_workday_settings_start_time($workday,$work_schedule_id_go,$comp_id);
					$tomorrow_startime 				= date('Y-m-d H:i:s',strtotime($change_date. " ".$workday_settings_start_time_me));
					$tomorrow_startime 				= date('Y-m-d H:i:s',strtotime($tomorrow_startime) - 240 * 60);	
				}
				else{
					if($r->date == $vx['currentdate']){
						$change_date = date('Y-m-d',strtotime($vx['currentdate']." +1 day"));
					}else{
						$change_date = date('Y-m-d',strtotime($vx['currentdate']." -1 day"));
					}
					$workday 						= date('l',strtotime($change_date));
					$work_schedule_id_go 			= $this->get_tomorrow_shift($emp_id, $comp_id, $change_date);
					$workday_settings_start_time_me = $this->check_workday_settings_start_time($workday,$work_schedule_id_go,$comp_id);
					$tomorrow_startime 				= date('Y-m-d H:i:s',strtotime($change_date. " ".$workday_settings_start_time_me));
					$tomorrow_startime 				= date('Y-m-d H:i:s',strtotime($tomorrow_startime) - 240 * 60);
				}
			
				$your_date = date('Y-m-d');
				$datediff = $r->date - $your_date;
				$count_date = floor($datediff/(60*60*24));
				
				# Scen 1: get the length of days
				# if today has schedule and tomrrow has a schedule
				# return true;
				if($count_date == 0){
					
					# Scen 2: get the start time of next schedule
					# compare if not empty
					# return true
					if($workday_settings_start_time_me){
														
						$workday1 = date('l',strtotime($r->date));
						$ws1 = $this->get_tomorrow_shift($emp_id, $comp_id, $change_date);
						$wst1 = $this->check_workday_settings_start_time($workday1,$ws1,$comp_id);
						
						# Scen 3: compare the startime of next schedule and today schedule 
						# if not empty
						# return true
						if($workday_settings_start_time_me !="" && $wst1 !=""){
							$st = date('Y-m-d H:i:s',strtotime($r->date. " ". $wst1));
							$st = date('Y-m-d H:i:s',strtotime($st) - 240 * 60);
							
							# Scen 4: filter if current date is within the today schedule
							# and next schedule time											
							$row = $this->get_default_schedule($emp_id,$comp_id,"regular",$work_schedule_id,$date);
						
							if(($current_date >= $st && $current_date <=$tomorrow_startime)){
								return false;
							}else{						
								#Scene 6: if user schedule 12am - 4am
								if(!$refresh)
									return false;
									
								if($row)
									return true;
							}
						}else{
							
							#Scen 5: if user has a timeout 
							# return true;
							if($r->time_out != ""){
								return true;
							}
						}
					}
					else{
						# if next schedule is empty
						# if user time in in a next day (night shift schedule)
				        if($vx){
							$change_date = date('Y-m-d',strtotime($current_date." -1 day"));
				        }else 
				        	$change_date = $date;
				        
						$workday 						= date('l',strtotime($change_date));
						$work_schedule_id_go 			= $this->get_tomorrow_shift($emp_id, $comp_id, $change_date);
						$workday_settings_start_time_me = $this->check_workday_settings_start_time($workday,$work_schedule_id_go,$comp_id);
						$tomorrow_startime 				= date('Y-m-d H:i:s',strtotime($change_date. " ".$workday_settings_start_time_me));
						$tomorrow_startime 				= date('Y-m-d H:i:s',strtotime($tomorrow_startime) - 240 * 60);
						$work_schedule_id_go2 			= $this->get_tomorrow_shift($emp_id, $comp_id, $change_date);
						$workday_settings_end_time_me 	= $this->check_workday_settings_end_time($workday,$work_schedule_id_go2,$comp_id);
						$tomorrow_endtime 				= date('Y-m-d H:i:s',strtotime($change_date. " ".$workday_settings_end_time_me));
						$tomorrow_endtime 				= date('Y-m-d H:i:s',strtotime($tomorrow_endtime) + 600 * 60);
				
					
						if(date('A',strtotime($workday_settings_start_time_me)) == "PM" &&  date('A',strtotime($workday_settings_end_time_me)) == "AM"){
							$change_date = date('Y-m-d',strtotime($current_date." +1 day"));
							$tomorrow_endtime = date('Y-m-d H:i:s',strtotime($change_date. " ".$workday_settings_end_time_me));
							
						}
						
						# Scen 6: check if currentdate is within the start time and end time
						# of today schedule								
						if($current_date >= $tomorrow_startime && $current_date <= $tomorrow_endtime && $r->date == $date){
							
							return false;
						}else{
					
							return true;
						}
					}	
				}else{
					return true;
				}
			}
			elseif($schedule=="flexible"){
				$datediff 	= strtotime($current_date) - strtotime($r2->time_in);
				$length 	=  floor($datediff/(60*60*24));
				
				if($length >1 && $r2->time_in == ""){
					return false;						
				}
			
				$m 			= date('H:i:s',strtotime($r2->time_in));
				$current 	= date('Y-m-d H:i:s',strtotime($m));					
				$rx2 		= date('Y-m-d H:i:s', strtotime($r2->time_in) - 240 * 60);
				$one_day 	= date('Y-m-d H:i:s',strtotime($rx2. " +1 day"));
				
				if($current_date >= $r2->time_in &&  $current_date <= $one_day){
					return true;
				}else{
					return false;
				}
			}
			elseif($schedule=="split"){
				
			}
		}else{
			// check employee time in
			$current_date = date("Y-m-d H:i:s");
			$arrx6 = array(
					'time_in' 				=> 'eti.time_in',
					'payroll_group_id' 		=> 'epi.payroll_group_id',
					'date' 					=> 'eti.date',
					'time_out' 				=> 'eti.time_out',
					'employee_time_in_id' 	=> "eti.employee_time_in_id"
			);
			$this->edb->select($arrx6);
			$w = array(
				"a.payroll_cloud_id"=>$emp_no,
				"a.user_type_id"=>"5",
				"eti.status" => "Active",
				"eti.comp_id" => $comp_id
			);
			$this->edb->where($w);
			
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
			$this->db->order_by("eti.time_in","DESC");
			if($this->type == "split"){
				
				$q = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
			}else{
				$q = $this->edb->get("employee_time_in AS eti",1,0);
			}
			$r = $q->row();
			
			if($q->num_rows() == 0){
			
				return true;
			
			}else{
				$workday = date("l",strtotime($date));
				if($vx){
					$workday = date("l",strtotime($vx['currentdate']));
				}
				
				// get date time in to date time out
				
				$payroll_group_id = $r->payroll_group_id;
	
				// check rest day
				$check_rest_day = $this->check_rest_day($workday,$work_schedule_id,$comp_id);
				if($check_rest_day){
				
					$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
					// global where update data
	
					/*
					 * gamita ni para mobalik sa timein ang clock in kng nalimot mn gani ang pagtime out
					 * for regular schedule rani
					 */
					
					if($schedule=="regular"){
						$vx = $this->activate_nightmare_trap($comp_id, $emp_no);
						
						if(isset($vx['start_time'])!=null){
							$wst = $vx['start_time'];
						}
						
						$rx = date('Y-m-d H:i:s', strtotime($wst) - 240 * 60);
							
						if($current_date >= $rx ){
							
							if($rx >= $r->time_in || $current_date <=$rx) {
								return true;
								exit;
							}
						}
							
					}elseif($schedule=="flexible"){
							
						$datediff = strtotime($current_date) - strtotime($r->time_in);
						$length =  floor($datediff/(60*60*24));
							
						if($length >1 && $r->time_in == ""){				
							return false;
						}
					
						$m = date('H:i:s',strtotime($r->time_in));
						$current = date('Y-m-d H:i:s',strtotime($m));
						$rx2 = date('Y-m-d H:i:s', strtotime($r->time_in) - 240 * 60);
						$one_day = date('Y-m-d H:i:s',strtotime($rx2. " +1 day"));
							
					
						if($current_date >= $r->time_in &&  $current_date <= $one_day){
								
							return true;
						}else{
								
							return false;
						}
							
					}elseif($schedule=="split"){
						//echo $r->time_in;
					}
				}
	
				// check workday settings
				$workday_settings_start_time = $this->check_workday_settings_start_time($workday,$work_schedule_id,$comp_id);
				$workday_settings_end_time = $this->check_workday_settings_end_time($workday,$work_schedule_id,$comp_id);

	
				/*
				 * gamita ni para mobalik sa timein ang clock in kng nalimot mn gani ang pagtime out 
				 * for regular schedule rani
				 */
				
				if($schedule=="regular"){
					# schene 6: 12am- 10am
					$refresh = (isset($vx['refresh'])) ? $vx['refresh'] : true;
				 	if(!$vx || $refresh === false){
					//if(!$vx){
						
						$change_date = date('Y-m-d',strtotime($r->date." +1 day"));
				
						$workday = date('l',strtotime($change_date)); 
							
						$work_schedule_id_go = $this->get_tomorrow_shift($emp_id, $comp_id, $change_date);
						
						$workday_settings_start_time_me = $this->check_workday_settings_start_time($workday,$work_schedule_id_go,$comp_id);
							
						$tomorrow_startime = date('Y-m-d H:i:s',strtotime($change_date. " ".$workday_settings_start_time_me));
						$tomorrow_startime = date('Y-m-d H:i:s',strtotime($tomorrow_startime) - 240 * 60);	
						
					}else{
						
						if($r->date == $vx['currentdate']){
							$change_date = date('Y-m-d',strtotime($vx['currentdate']." +1 day"));
						}else{
							$change_date = date('Y-m-d',strtotime($vx['currentdate']." -1 day"));
						}
						
						//$workday = date('l',strtotime($change_date));
		
						$work_schedule_id_go = $this->get_tomorrow_shift($emp_id, $comp_id, $change_date);
						
						$workday_settings_start_time_me = $this->check_workday_settings_start_time($workday,$work_schedule_id_go,$comp_id);
							
						$tomorrow_startime = date('Y-m-d H:i:s',strtotime($change_date. " ".$workday_settings_start_time_me));
						$tomorrow_startime = date('Y-m-d H:i:s',strtotime($tomorrow_startime) - 240 * 60);
					}
					
						$your_date = date('Y-m-d');
						$datediff = $r->date - $your_date;
						$count_date = floor($datediff/(60*60*24));
						
						# Scen 1: get the length of days
						# if today has schedule and tomrrow has a schedule
						# return true;
						if($count_date == 0){
							
							# Scen 2: get the start time of next schedule
							# compare if not empty
							# return true
							
							if($workday_settings_start_time_me){
												
								$workday1 = date('l',strtotime($r->date));
								$ws1 = $this->get_tomorrow_shift($emp_id, $comp_id, $change_date);
								$wst1 = $this->check_workday_settings_start_time($workday1,$ws1,$comp_id);
								
								# Scen 3: compare the startime of next schedule and today schedule 
								# if not empty
								# return true
								if($workday_settings_start_time_me !="" && $wst1 !=""){
									$st = date('Y-m-d H:i:s',strtotime($r->date. " ". $wst1));
									$st = date('Y-m-d H:i:s',strtotime($st) - 240 * 60);
									
									# Scen 4: filter if current date is within the today schedule
									# and next schedule time											
									$row = $this->get_default_schedule($emp_id,$comp_id,"regular",$work_schedule_id,$date);
									
									if(($current_date >= $st && $current_date <=$tomorrow_startime)){
										
										return false;
									}else{						
										
										#Scene 6: if user schedule 12am - 4am
										if(!$refresh)
											
											return false;
										
										if($row)
											
											return true;
										
									}
								}else{
									
									#Scen 5: if user has a timeout 
									# return true;
									if($r->time_out != ""){
										
										return true;
									}
								}
								
								
							}else{		
								# if next schedule is empty
								# if user time in in a next day (night shift schedule)
						        if($vx){
									$change_date = date('Y-m-d',strtotime($current_date." -1 day"));
						        }else 
						        	$change_date = $date;
						
								$workday = date('l',strtotime($change_date));
								$work_schedule_id_go = $this->get_tomorrow_shift($emp_id, $comp_id, $change_date);
								$workday_settings_start_time_me = $this->check_workday_settings_start_time($workday,$work_schedule_id_go,$comp_id);
								
								$tomorrow_startime = date('Y-m-d H:i:s',strtotime($change_date. " ".$workday_settings_start_time_me));
								$tomorrow_startime = date('Y-m-d H:i:s',strtotime($tomorrow_startime) - 240 * 60);
						
								$work_schedule_id_go2 = $this->get_tomorrow_shift($emp_id, $comp_id, $change_date);
								$workday_settings_end_time_me = $this->check_workday_settings_end_time($workday,$work_schedule_id_go2,$comp_id);
								
								$tomorrow_endtime = date('Y-m-d H:i:s',strtotime($change_date. " ".$workday_settings_end_time_me));
								$tomorrow_endtime = date('Y-m-d H:i:s',strtotime($tomorrow_endtime) + 600 * 60);
						
							
								if(date('A',strtotime($workday_settings_start_time_me)) == "PM" &&  date('A',strtotime($workday_settings_end_time_me)) == "AM"){
									$change_date = date('Y-m-d',strtotime($current_date." +1 day"));
									
									$tomorrow_endtime = date('Y-m-d H:i:s',strtotime($change_date. " ".$workday_settings_end_time_me));
									
								}
								
								# Scen 6: check if currentdate is within the start time and end time
								# of today schedule								
								
								if($current_date >= $tomorrow_startime && $current_date <= $tomorrow_endtime && $r->date == $date){									
									
									return false;
								}else{		
													
									return true;
								}
							}
							
							
						}else{
							
							return true;
						}
						
			
				}elseif($schedule=="flexible"){
					
					$datediff = strtotime($current_date) - strtotime($r->time_in);
					$length =  floor($datediff/(60*60*24));
					
					if($length >1 && $r->time_in == ""){
					
						return false;						
					}
				
					$m = date('H:i:s',strtotime($r->time_in));
					$current = date('Y-m-d H:i:s',strtotime($m));					
					$rx2 = date('Y-m-d H:i:s', strtotime($r->time_in) - 240 * 60);
					$one_day = date('Y-m-d H:i:s',strtotime($rx2. " +1 day"));
					
					
					if($current_date >= $r->time_in &&  $current_date <= $one_day){
					
						return true;
					}else{
							
						return false;
					}
					
				}elseif($schedule=="split"){
					
					if($r){
						
						$night_date = $this->activate_nightmare_trap($comp_id, $emp_no);
						$currentdate = date('Y-m-d');
						if($night_date){
							$currentdate = $night_date['currentdate'];
						}
						
						if($r->date == $currentdate){
							

							$w = array(
									"employee_time_in_id"=>$r->employee_time_in_id,
									"eti.status" => "Active",
									"eti.comp_id" => $comp_id
							);
							$this->edb->where($w);
							$this->db->order_by("eti.time_in","DESC");
							$split_q = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
							$query_split = $split_q->row();
							
							
							if($query_split){
								$split = $this->new_get_splitinfo($emp_no, $comp_id, $work_schedule_id);							
							}
						}else{
							return true;
						}
					}									
				}
			}
		}
	}
	
	
	public function refresh_timein_split($emp_no ,$comp_id,$work_schedule_id){
		
		$arr = array();
		$check_emp_no = $this->login_screen_model->new_check_emp_info($emp_no,$comp_id);
		$currentdate = date('Y-m-d');
		$currenttime = date('Y-m-d H:i:s');
		$night = $this->activate_nightmare_trap($comp_id, $emp_no);
		if($night){
			$currentdate = $night['currentdate'];
		}
			
		$w_date = array(
				"es.valid_from <="		=>	$currentdate,
				"es.until >="			=>	$currentdate
		);
		$this->db->where($w_date);
		
		$w_ws = array(
				"em.work_schedule_id"=>$work_schedule_id,
				"em.company_id"=>$comp_id,
				"em.emp_id" => $check_emp_no->emp_id
		);
		$this->db->where($w_ws);
		$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
		$q_ws = $this->edb->get("employee_sched_block AS em");
		$r_ws = $q_ws->result();
		
		if($q_ws->num_rows() > 0){
						
			if($r_ws){
				$first_today = reset($r_ws);
				$last_today = end($r_ws);
					
				$start_last2 = $this->get_starttime($first_today->schedule_blocks_id,$currentdate,$first_today);
				$lastx = $this->get_endtime($last_today->schedule_blocks_id, $currentdate,$last_today);
				
				$start_date_now = date('Y-m-d H:i:s',strtotime($start_last2." -120 minute"));
				$s =  date('Y-m-d H:i:s',strtotime($start_last2." +60 minute"));
				
				$start_date_now = date('Y-m-d H:i:s',strtotime($start_last2." -120 minute"));
				$s =  date('Y-m-d H:i:s',strtotime($start_last2." +60 minute"));
				# kwaon ang date kng ang currenttime ni greater than sa grace time
				# gamit ni cya  scenario nga wla ka timeout sa last block o wla cta ka log sa last block
				if($currenttime>= $start_date_now && $currenttime <= $s){
					$arr['first'] = true;
				}else{
					$arr['first'] = false;
				}
			}
			
		
		}
		return $arr;
	}
	
	/**
	 * check the info of user default workschedule
	 */
	public function get_default_schedule($emp_id,$comp_id,$type="",$work_schedule_id ="",$date = "",$timeout = ""){
		
		$info = false;
		$date2 = date('Y-m-d');
		$current_time = date('Y-m-d H:i:s');

				
			
		$day = date('l',strtotime($date));
		$w_uwd = array(
				"work_schedule_id"	=> $work_schedule_id,
				"company_id"		=> $comp_id,
				"days_of_work" 		=> $day,
				"status" 			=> 'Active'
		);
		$this->edb->where($w_uwd);
		$arr4 = array(
				'work_schedule_name'=> 'work_schedule_name',
				'work_end_time' 	=> 'work_end_time',
				'work_start_time' 	=> 'work_start_time',
				'break_in_min' 		=> 'break_in_min',
				'total_work_hours' 	=> 'total_work_hours'
		);
		$this->edb->select($arr4);
		$q_uwd = $this->edb->get("regular_schedule");
		$r_uwd = $q_uwd->row();
		
		if($q_uwd->num_rows() > 0){
			$start_time = date("Y-m-d H:i:s",strtotime($date." ".$r_uwd->work_start_time. " -240 minutes"));
				
			if($current_time >= $start_time){
				return true;
			}
		}else{
			// FLEXIBLE HOURS
			$fw = array(
				"f.company_id"		=> $comp_id,
				"f.work_schedule_id"=> $work_schedule_id,
			);
			$this->db->where($fw);
			$arr3 = array(
					'latest_time_in_allowed' 	=> 'f.latest_time_in_allowed',
					'name' 						=> 'ws.name',
					'number_of_breaks_per_day' 	=> 'number_of_breaks_per_day',
					'total_hours_for_the_day' 	=> 'total_hours_for_the_day'
			);
			$this->edb->select($arr3);
			$this->edb->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
			$fq = $this->edb->get("flexible_hours AS f");
			$r_fh = $fq->row();
			if($fq->num_rows() > 0){
				$data = $r_fh;
		
				if($r_fh->latest_time_in_allowed != NULL || $r_fh->latest_time_in_allowed != ""){

					$start_time = date("Y-m-d H:i:s",strtotime($date." ".$r_fh->latest_time_in_allowed. " -240 minutes"));
					if($current_time >= $start_time){
						return true;
					}
				}else{
					return true;
				}
			}
		}
			
		return $info;
	}
	
	/**
	 * ugma nga schedule
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $date
	 * @return boolean
	 */
	public function get_tomorrow_shift($emp_id,$company_id,$date){
		$w_uwd = array(
				'company_id' => $company_id,
				'emp_id' => $emp_id,
				'valid_from <=' => $date,
				'until >=' => $date,
				"payroll_group_id" => 0
		);
		
		$this->db->where($w_uwd);
		$q_uwd = $this->db->get("employee_shifts_schedule");
		$x = $q_uwd->row();
		$xd = $q_uwd->result();

		if($x){
			
			return $x->work_schedule_id;
		}else{
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
	
	
	public function get_yesterday_shift($emp_id,$compnay_id,$date){

		$date = date('Y-m-d',strtotime($date. " -1 day"));
	}
	
	/**
	 * Get the workschedule yesterday 
	 * This will help you to specify the workschedule everyday
	 * @param unknown $emp_no
	 * @param unknown $check_company_id
	 * @param boolean $activate_sharingan = return value of the time in of the previous user log
	 * @return boolean
	 */
	
	public function filter_date_tim_in($emp_no,$check_company_id,$activate_sharingan = false,$activate_split = false,$sync_date =""){
		
		if($sync_date){
			$currentdate 	= date('Y-m-d',strtotime($sync_date));
			$current_date 	= $sync_date;
			$yesterday 		= date('Y-m-d', strtotime($current_date.' -1 day'));
		}else{		
			$currentdate 	= date('Y-m-d');
			$current_date 	= date('Y-m-d H:i:s');
			$yesterday 		= date('Y-m-d', strtotime(' -1 day'));
		}
		
		//check if yesterday has a login added to time in
		$w = array(
				"a.payroll_cloud_id"	=> $emp_no,
				"eti.date" 				=> $yesterday,
				"a.user_type_id"		=> "5",
				"eti.status" 			=> "Active"
		);
		$this->edb->where($w);
		$arr = array('time_in' 			=> 'eti.time_in',
				'time_out' 				=> 'eti.time_out',
				'work_schedule_id' 		=> 'eti.work_schedule_id',
		);
		$this->edb->select($arr);
		$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
		$this->db->order_by("eti.time_in","DESC");
		if($activate_split)
			$q = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
		else 
			$q = $this->edb->get("employee_time_in AS eti",1,0);
		
		$x = $q->row();
		

		if($q->num_rows() > 0){
		
			if($x->time_out!= ""){
				return true;
			}
			else{
				$emp_work_schedule_id = $this->login_screen_model->emp_work_schedule2($emp_no,$check_company_id,$yesterday);
				
				if($emp_work_schedule_id!=false){
					// return false if there is workchedule added yesterday schedule
					if($activate_sharingan){
						return $x->time_in;	
					}
					else{	
						
						$day = date('l',strtotime($currentdate));
						$emp_work_schedule_id2 = $this->login_screen_model->emp_work_schedule2($emp_no,$check_company_id,$currentdate);
						$w_uwd = array(
								"work_schedule_id"	=> $emp_work_schedule_id2 ,
								"company_id"		=> $check_company_id,
								"days_of_work" 		=> $day,
								"status" 			=> 'Active'
								);
						$this->db->where($w_uwd);
						$q_uwd = $this->db->get("regular_schedule");
						$r_uwd = $q_uwd->row();
						
						if($q_uwd->num_rows() > 0){
							
							$wst = $this->check_workday_settings_start_time(date("l"),$emp_work_schedule_id2,$check_company_id);
							$vx = $this->activate_nightmare_trap($check_company_id, $emp_no);
							
							if(isset($vx['start_time'])!=null){
								$wst = $vx['start_time'];
							}
							$rx = date('Y-m-d H:i:s', strtotime($wst) - 240 * 60);
								
							if($current_date >= $rx ){
								if($rx >= $x->time_in || $current_date <=$rx) {									
									return true;									
								}
							}
						}
						return false;
					}	
				}else{
					return true;
				}
			}
		}else{
			
			return true;
		}
	}
	
	/**
	 * Identifying if work type is Workshift
	 * @param unknown $work_scheduel_id
	 * @return boolean
	 */
	public function if_splitschedule($work_scheduel_id){
		$w = array(
				"work_schedule_id"=>$work_scheduel_id,
		);
		$this->edb->where($w);
		$q = $this->edb->get("work_schedule");
		$x = $q->row();
		if($x){
			if($x->work_type_name == "Workshift")
				return true;
		}
			return false;
	}
	
	/**
	 * check the last block of split schedule
	 * @param unknown $arr
	 * @param unknown $end
	 * @return boolean
	 */
	public function get_the_last_block($arr = array(),$end,$employee_timein_id = 0){
		
		$my_end = date('Y-m-d',strtotime($end));
		$get_last = count($arr);
		$x = 0;
	
		$check = array();
		
		$w = array(
				"employee_time_in_id"=>$employee_timein_id
		);
		$this->edb->where($w);
		$q = $this->edb->get("schedule_blocks_time_in");
		$result = $q->result();
	
		foreach($result as $row):
			/*foreach ($arr as $rx){
			$rowx = $this->get_blocks_list($rx->schedule_blocks_id);
			$end_time = date('Y-m-d H:i:s',strtotime($my_end. " ".$rowx->end_time));*/
				
			if($row->time_out !=""){	 					
					$x++;
			}
				
		endforeach;
		
		$get_last = $get_last - $x;
		
		if($get_last==1){
			return true;
		}
		return false;
	}
	
	/**
	 * Check if the end time of workschedule skipping the timein
	 * @param unknown $emp_no
	 * @param unknown $comp_id
	 * @param unknown $work_schedule_id
	 * @param unknown $employee_time_in_id
	 * @param unknown $emplogin = help to identify to calculate the all block and create new timein  
	 * @return boolean
	 */
	public function check_endtime_notempty($emp_no,$comp_id,$work_schedule_id,$employee_time_in_id,$time_out = NULL,$emplogin=false,$check_type = ""){
		
		$found = false;
		$current_time = date('Y-m-d H:i:s');
		$check_emp_no = $this->login_screen_model->new_check_emp_info($emp_no,$comp_id);
		$last_timein_sched = $this->filter_date_tim_in($emp_no, $comp_id,true);
		if($last_timein_sched === true){
			$currentdate = date('Y-m-d');
		}else{
			$currentdate = date('Y-m-d',strtotime($last_timein_sched));
			
		}
		
		$night = $this->activate_nightmare_trap($comp_id, $emp_no);
		if($night){
			$currentdate = $night['currentdate'];
		}
		
		$w = array(
				"employee_time_in_id"=>$employee_time_in_id,
				"eti.status" => "Active"
		);
		$this->edb->where($w);
		
		$elf_force = array(
				'schedule_blocks_id',
				'time_in',
				'time_out'
		);
		$this->edb->select($elf_force);
		$this->db->order_by("eti.time_in","ASC");
		$split_q = $this->edb->get("schedule_blocks_time_in AS eti");
		$query_split = $split_q->result();
		
		$arr = array('1997-05-04 00:00:00'); // Dont delete this
		$arr2 = array('1997-05-04 00:00:00'); // Dont delete this
		
		$w_date = array(
				"es.valid_from <="		=>	$currentdate,
				"es.until >="			=>	$currentdate
		);
		$this->db->where($w_date);
		
		$w_ws = array(
				
				"em.work_schedule_id"=>$work_schedule_id,
				"em.company_id"=>$comp_id,
				"em.emp_id" => $check_emp_no->emp_id
		);
		$this->db->where($w_ws);
		
		$elf_force2 = array(
				'schedule_block_id' => 'em.schedule_blocks_id'
		);
		$this->edb->select($elf_force2);
		$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
		$q_ws = $this->edb->get("employee_sched_block AS em");
		$r_ws = $q_ws->result();
		
		$x = 0;
		$last_query =  end($query_split);

		if($q_ws->num_rows() > 0 && $query_split){
			
			$x =1;			

			$yest = $this->yesterday_split_info($current_time, $check_emp_no->emp_id, $work_schedule_id, $comp_id); // get the last schedule
			
			
				$last_yest = end($yest); 
				$date_filter1 = date('Y-m-d',strtotime($last_yest['end_time']));
				$date_filter = date('Y-m-d H:i:s',strtotime($date_filter1. " 00:00:00"));
			
				if($current_time > $last_yest['end_time'] && $last_yest['end_time'] != $date_filter || ($last_query->schedule_blocks_id == $last_yest['schedule_block_id'] && $last_query->time_out !="")){
						$yest = $this->yesterday_split_info($current_time, $check_emp_no->emp_id, $work_schedule_id, $comp_id,false); // get the schedule today											
				}
			
				foreach($yest as $row){
					$arr[] = $row['end_time'];
				}
			
			$last_time =  $this->find_closest_end($arr, $current_time);
			$lastest = max($arr);
		   
			foreach($query_split as $split){
				 $start_time2 = $this->get_starttime($split->schedule_blocks_id,$split->time_in);
				 $end = $this->get_splitschedule_info($r_ws,$split->time_in);
				 $end_time2 = $this->get_endtime($split->schedule_blocks_id,$split->time_in);
			
				 $arr2[] = $end_time2;
				
				if($end['end_time'] == $lastest){
					
				  if(!$emplogin){ // mao ni gamiton para sa pagcheck kng na completo bah iya time o wla
					if($split->time_out !=""){
						return true;
					}
					else{
						return false;
					}	
				  }else{ // mao ni gamiton kng sa calculation nga kng angay nabang e calculate ang all block  	
				  	if($split->time_out !=""){
				  		return false;
				  	}
				  	else{
				  		return true;
				  	}
				  }
				}
			 }
			 
			 $lastest2 = end($arr2);
			 
			 
			 $first = reset($r_ws);
			 $last = max($r_ws);
			 $time_in = date('Y-m-d H:i:s');
			 $last_block = $this->get_endtime($last->schedule_blocks_id,$time_in,$last);
			 #last block
			 if($r_ws){
			 	 $last_sched_time = max($query_split);
				if($last_sched_time->schedule_blocks_id == $last->schedule_blocks_id && $check_type == "time out" && $last_sched_time->time_out ==""){			 	
			 		return true;
			 	}
			 }
		}
			return false;
		 
	}
	
	/**
	 * find the closest timein of the user for end time
	 * @param unknown $array
	 * @param unknown $date
	 * @return unknown
	 */
	public function find_closest_end($array, $date)
	{
	    
	    foreach($array as $day)
	    {	       
	            $interval[] = abs(strtotime($date) - strtotime($day));	      
	    }
	    asort($interval);
	    $closest = key($interval);
	
	  	return $array[$closest];
	}
	
	/**
	 * Display the list of blocks in split schedule
	 * @param unknown $currentdate
	 * @param unknown $emp_id
	 * @param unknown $work_schedule_id
	 * @param unknown $comp_id
	 * @return unknown|boolean
	 */
	public function list_of_blocks($currentdate,$emp_id,$work_schedule_id,$comp_id,$select = array()){
		// revised new split way of saving -- 06/05/17
		$w_date = array(
				"em.valid_from <="		=>	$currentdate,
				"em.until >="			=>	$currentdate
		);
		$this->db->where($w_date);
			
		$w_ws = array(
				"em.work_schedule_id"	=> $work_schedule_id,
				"em.company_id"			=> $comp_id,
				"em.emp_id" 			=> $emp_id
		);
		$this->db->where($w_ws);
		
		if($select){
			$this->db->select($select);
		}else{
			
		}
		
		//$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
		$q_ws = $this->db->get("employee_sched_block AS em");
		$r_ws = $q_ws->result();
		
		if($r_ws)
			return $r_ws;
		else
			return false;
	}

	public function list_of_blocks_todo($currentdate,$emp_id,$work_schedule_id,$comp_id,$select = array()){

		$w_date = array(
				"em.valid_from <="		=>	$currentdate,
				"em.until >="			=>	$currentdate
		);
		$this->db->where($w_date);
			
		$w_ws = array(
				"em.work_schedule_id"	=> $work_schedule_id,
				"em.company_id"			=> $comp_id,
				"em.emp_id" 			=> $emp_id
		);
		$this->db->where($w_ws);
		
		if($select){
			$this->edb->select($select);
		}else{
			
		}
		
		//$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
		$q_ws = $this->edb->get("employee_sched_block AS em");
		$r_ws = $q_ws->result();
		
		if($r_ws)
			return $r_ws;
		else
			return false;
	}
	
	/**
	 * this only work for call center or those people who work at night
	 * if the user time in on the next day 
	 */
	public function activate_nightmare_trap($comp_id,$emp_no,$work_schedule_id = "",$activate_no_schedule = false,$sync_date="",$sync_employee_time_in_id = ""){
		
		$data = array();
		$check_emp_no = $this->login_screen_model->new_check_emp_info($emp_no,$comp_id);
		
		if($sync_date){
			$currentdate = date('Y-m-d',strtotime($sync_date." -1 days"));
			$currenttime = $sync_date;
			
		}else{
			$currentdate = date('Y-m-d',strtotime("-1 days"));
			$currenttime = date('Y-m-d H:i:s');
		}
		
		if(isset($check_emp_no->emp_id)){
			$emp_id = $check_emp_no->emp_id;
		}
		else{
			$emp_id = 0;
		}
		
		$r_emp = $this->emp_work_schedule2($emp_id, $comp_id,$currentdate);
		
		if($r_emp || $activate_no_schedule){
			
			if($sync_employee_time_in_id!=""){
				$w = array(
						"a.payroll_cloud_id"		=> $emp_no,
						"a.user_type_id"			=> "5",
						"eti.employee_time_in_id" 	=> $sync_employee_time_in_id,
						"eti.status" 				=> "Active"
				);
			}
			else{
				$w = array(
						"a.payroll_cloud_id"=> $emp_no,
						"a.user_type_id"	=> "5",
						"eti.status" 		=> "Active",
						"eti.comp_id" 		=> $comp_id
				);
			}
			$this->edb->where($w);
			
			$xz = array(
					'time_in' 				=> 'eti.time_in',
					'time_out' 				=> 'eti.time_out',
					'date' 					=> 'eti.date',
					'employee_time_in_id' 	=> 'eti.employee_time_in_id'
			);
			$this->edb->select($xz);			
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
			$this->db->order_by("eti.time_in","DESC");
			$q = $this->edb->get("employee_time_in AS eti",1,0);
			$r = $q->row();
			
			if(!$r){
				
				if(!$activate_no_schedule){
					$work_schedule_id = $this->emp_work_schedule($emp_no,$comp_id);
				}
				$r = (object) array(
						'work_schedule_id' 		=> $work_schedule_id,
						'time_in' 				=> date('Y-m-d H:i:s'),
						'time_out' 				=> "",
						'date' 					=> date('Y-m-d'),
						'employee_time_in_id' 	=> 0
				);
				
			}
		
			//NO SCHEDULE
			if($activate_no_schedule){
				
			}
			
			$day 	= date('l',strtotime($currentdate));
			$w_uwd 	= array(
					"work_schedule_id"	=> $r_emp,
					"company_id"		=> $comp_id,
					"days_of_work" 		=> $day,
					"status" 			=> 'Active'
			);
			$this->db->where($w_uwd);
			
			$q_uwd = $this->db->get("regular_schedule");
			$r_uwd = $q_uwd->row();

			/**get the advance schedule **/
			// time 12am - 4am
			$mid_date = $currentdate;
			$advance_date = date('Y-m-d',strtotime($mid_date." +1 day"));
			$sched2 = $this->one_day_plus($mid_date,$r_emp, $comp_id,$emp_id);
			
			if($sched2){
				$start_time =  date('Y-m-d H:i:s', strtotime($advance_date." ".$sched2->work_start_time));
				$end_time 	=  date('Y-m-d H:i:s', strtotime($advance_date." ".$sched2->work_end_time));
				
				$wStarttime = $sched2->work_start_time;
				$mins = 240;
				if($sched2->latest_time_in_allowed){
					$mins = $sched2->latest_time_in_allowed + $mins;
					$wStarttime = date('H:i:s',strtotime($wStarttime. " +{$sched2->latest_time_in_allowed}  minutes"));
					$start_time = date('Y-m-d H:i:s',strtotime($advance_date." ".$wStarttime));
				}
				if(strtotime($wStarttime) >= strtotime("00:00:00") && strtotime($wStarttime ) <= strtotime("04:00:00")){
					
					$start_timez3 = date('Y-m-d H:is',strtotime($start_time." -{$mins} minutes"));
					
					if($currenttime >= $start_timez3){
						$data['currentdate'] 		= $advance_date;
						$data['work_schedule_id'] 	= $r_emp;
						$data['start_time'] 		= $start_timez3;
						$data['end_time'] 			= $end_time;
						$data['refresh'] 			= true;
						
						return $data;
					}else{
						$data['currentdate'] 		= date('Y-m-d');
						$data['work_schedule_id'] 	= $r_emp;
						$data['start_time'] 		= $start_timez3;
						$data['end_time'] 			= $end_time;
						$data['refresh'] 			= false;
						
						return $data;
					}
				}
			}
			
			if($q_uwd->num_rows() > 0){
				$start_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$r_uwd->work_start_time. " -120 minutes"));
				$end_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$r_uwd->work_end_time));
				$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
			

				$sched = $this->one_day_plus($currentdate,$r_emp, $comp_id,$emp_id); 

				// check if the next day is restday or dont have schedule
				// return the previous date				
				if($start_time>=$end_time && $currenttime>=$mid_night && $r->time_out == ""){
					$end_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$r_uwd->work_end_time ." +1 day"));

					if(!$sched){
						$data['currentdate'] 		= $currentdate;
						$data['work_schedule_id'] 	= $r_emp;
						$data['start_time'] 		= $start_time;
						$data['end_time'] 			= $end_time;
						
						return $data;
					}
				}

				
				if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM"  && $currenttime >= $mid_night){		
					
					$end_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$r_uwd->work_end_time ." +1 day"));
		
					if($sched){
						
						$esday 			= date('l',strtotime($currentdate.' +1 day'));
						$esdayx_starts 	= date('Y-m-d H:i:s',strtotime($esday. " ". $sched->work_start_time));
						
						if($sched->latest_time_in_allowed){
							$esdayx_starts = date('Y-m-d H:i:s',strtotime($esdayx_starts. "-{$sched->latest_time_in_allowed}  minute"));
						}
						
						$esdayx_start = date('Y-m-d H:is',strtotime($esdayx_starts."-240 minutes"));
						
					}
					
					// filter the start time of tomorrow shedule
					// if no start time return false
					$add_other_day = false;
					if(isset($esdayx_start)){
						
						if($currenttime <= $esdayx_start ){
							$add_other_day = true;
						}
					}
					$isTodayDate = false;
					
					if(!($currenttime > $end_time) || $r->time_out =="" ){
					
						if($sched){
							$today_date = $esdayx_start;
							
							if($currenttime > $today_date){
								$isTodayDate = true;	
							}
						}
					}else{
						$isTodayDate = true;
					}
					
				
					if($r->time_in >= $mid_night){					
						$time_in = date('Y-m-d',strtotime($r->time_in));											
					
						if(($r->date == $currentdate && $time_in > $currentdate && $r->time_out =="" && !$isTodayDate)  || $add_other_day ){
							$data['currentdate'] 		= $currentdate;
							$data['work_schedule_id'] 	= $r_emp;
							$data['start_time'] 		= $start_time;
							$data['end_time'] 			= $end_time;
						}
						else{
							// new employee, no log in employee
							if($r){
								
								$data['currentdate'] 		= date('Y-m-d');
								$data['work_schedule_id'] 	= $r_emp;
								$data['start_time'] 		= null;
							}
							else{
								$data['currentdate'] 		= $currentdate;
								$data['work_schedule_id'] 	= $r_emp;
								$data['start_time'] 		= null;
							}
						}
					}else{
						
						if($isTodayDate){
							return false;
						}
						else{		
							$data['currentdate'] 		= $currentdate;
							$data['work_schedule_id'] 	= $r_emp;
							$data['start_time'] 		= $start_time;
							$data['end_time'] 			= $end_time;
						}
					}
				}else{
					// if the user timeout in the other day
					// scene: timein: 1pm to 1am
					// compress
					// tomorrow date
					if($sched){
						$tomdate_set = date('Y-m-d',strtotime($currentdate.' +1 day'));					
						$tomdate =date('Y-m-d H:i:s',strtotime($tomdate_set." ".$sched->work_start_time. " -240 minutes"));
						
						if($currenttime >= $mid_night && $currenttime <=$tomdate && $r->time_out == ""){
							$data['currentdate'] = $currentdate;
							$data['work_schedule_id'] = $r_emp;
							$data['start_time'] = $start_time;
							$data['end_time'] = $end_time;
						}
					}
				}
			
			} //end regular
			else{
				// SPLIT SCHEDULE SETTINGS
				
				//$this->get_starttime($schedule_blocks_id,date('Y-m-d'));
				$w_date = array(
						"es.valid_from <="		=>	$currentdate,
						"es.until >="			=>	$currentdate
				);
				$this->db->where($w_date);
									
				
				$w_ws = array(
						//"payroll_group_id"=>$payroll_group,
						"em.work_schedule_id"=>$r_emp,
						"em.company_id"=>$comp_id,
						"em.emp_id" => $check_emp_no->emp_id
				);
				$this->db->where($w_ws);
				$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
				$q_ws = $this->edb->get("employee_sched_block AS em");
				$r_ws = $q_ws->result();
			
				if($q_ws->num_rows() > 0){
					
					$first = reset($r_ws);
					$lastx = end($r_ws);

					$first_time 				= $this->get_starttime($first->schedule_blocks_id,$currentdate,$first);
					$mid_night 					= date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
					$mid_night2 				= date('Y-m-d',strtotime($mid_night));
					$last_timex 				= $this->get_endtime($lastx->schedule_blocks_id,$currentdate,$lastx);
					$last_schedule_blocks_id 	= $lastx->schedule_blocks_id;
					
					if($first_time > $last_timex){
						$last_time = date('H:i:s',strtotime($last_timex));
						$last_time = date("Y-m-d H:i:s",strtotime($mid_night2. " ".$last_time));
					}
					else{
						$yest 						= $this->yesterday_split_info($currenttime, $emp_id, $r_emp, $comp_id,true);
						$last 						= end($yest);
						$last_time 					= $last['end_time'];
						$last_schedule_blocks_id 	= $last['schedule_block_id'];	
						$mid_night 					= date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00 +1 day"));
					}
					
					$wx = array(
							"sbti.employee_time_in_id" 	=> $r->employee_time_in_id,
							"sbti.status" 				=> "Active"
					);
					
					$this->edb->where($wx);
					$this->db->order_by("sbti.time_in","DESC");
					$qx = $this->edb->get("schedule_blocks_time_in AS sbti",1,0);
					$rx = $qx->row();

					$schedule_blocks_id = 0;
					$time_out 			= "";
					$gdate 				= "";
					if($rx){
						$schedule_blocks_id = $rx->schedule_blocks_id;
						$time_out 			= $rx->time_out;
						$gdate 				= $rx->date;
					}

					$mid_time = date('H:i:s',strtotime($last_timex));
					$today= date('Y-m-d');
						
					$w_date = array(
							"es.valid_from <="		=>	$today,
							"es.until >="			=>	$today
					);
					$this->db->where($w_date);
						
					
					$w_ws = array(
							"em.work_schedule_id"	=> $r_emp,
							"em.company_id"			=> $comp_id,
							"em.emp_id" 			=> $check_emp_no->emp_id
							);
					$this->db->where($w_ws);
					$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
					$q_wsx = $this->edb->get("employee_sched_block AS em");
					$r_wsx = $q_wsx->result();

					if(($currenttime > $mid_night && $currenttime <= $last_time) || ($last_schedule_blocks_id == $schedule_blocks_id && $time_out == "") ){
						$last_date = true;
						
						if($r_wsx){
							$first_today = reset($r_wsx);
							$last_today = end($r_wsx);
							
							$start_last2 = $this->get_starttime($first_today->schedule_blocks_id,$today,$first_today);
							$start_date_now = date('Y-m-d H:i:s',strtotime($start_last2." -240 minutes"));
							
							// kwaon ang date kng ang currenttime ni greater than sa grace time
							// gamit ni cya  scenario nga wla ka timeout sa last block o wla cta ka log sa last block
							if($currenttime>= $start_date_now){
								$data['currentdate'] = date('Y-m-d');
								$last_date = false;
							}
						}
						
						if($last_date){
							$data['currentdate'] = $currentdate;
						}
					}

					//last schedule block
					//user time in in a next day
				
					if($first->schedule_blocks_id == $lastx->schedule_blocks_id){

						$start_last = $this->get_starttime($first->schedule_blocks_id,$currentdate,$first);
						$end_last = $this->get_endtime($lastx->schedule_blocks_id,$currentdate,$lastx);
						
						if($r_wsx){
							$first_today = reset($r_wsx);
							$last_today = end($r_wsx);
								
							$start_last2 = $this->get_starttime($first_today->schedule_blocks_id,$today,$first_today);
							$start_date_now = date('Y-m-d H:i:s',strtotime($start_last2." -240 minutes"));
								
							// kwaon ang date kng ang currenttime ni greater than sa grace time
							// gamit ni cya  scenario nga wla ka timeout sa last block o wla cta ka log sa last block
							if($currenttime>= $start_date_now){
							
								return false;
							}
						}					
						$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
						
						// kng ang timein niya nilabang sa sunod adlaw
						// kng wla sd cya timeout sa iya previous log;
						// Scene 4 nightshift (see document)
						if($currenttime>=$mid_night && date('A',strtotime($start_last)) == "PM" && date('A',strtotime($end_last)) == "AM" ){
							$data['currentdate'] = $currentdate;
						}
					}
					
					//kng ang iya end time sa last block kai natunong sa midnight(00:00:00)
					if( !($r_wsx) && $time_out==""){
						$data['currentdate'] = $currentdate;
					}
				} // end split
				else{
					// FLEXIBLE HOURS
					$arrx5 = array(
							'duration_of_lunch_break_per_day',
							'latest_time_in_allowed',
							'total_hours_for_the_day'
							);
					$this->edb->select($arrx5);
					$w_fh = array(
							"work_schedule_id"	=> $r_emp,
							"company_id"		=> $comp_id
							);
					$this->edb->where($w_fh);
					$q_fh = $this->edb->get("flexible_hours");
					$r_fh = $q_fh->row();
				
					if($q_fh->num_rows() > 0){
							
						$number_of_breaks_per_day 	= $r_fh->duration_of_lunch_break_per_day;
						$total_hours 				= $r_fh->total_hours_for_the_day * 60;
						$latest_timein 				= date('Y-m-d H:i:s',strtotime($currentdate." ".$r_fh->latest_time_in_allowed));
						$end_time_check 			= date('H:i:s',strtotime($r_fh->latest_time_in_allowed. " +{$total_hours} minutes"));
						$end_time 					= date('Y-m-d H:i:s',strtotime($latest_timein. " +{$total_hours} minutes"));
						$mid_night 					= date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
							
						if($r_fh->latest_time_in_allowed > $end_time_check){
							$advance_date2 			= date('Y-m-d',strtotime($currentdate. " +1 day"));
							$sched2 				= $this->one_day_plus_flex($advance_date2, $comp_id,$emp_id);
							$second_latest_timein 	= false;
							
							if($sched2){
								$second_latest_timein_old 	= $sched2->start_time;
								$second_latest_timein 		= date('Y-m-d H:i:s',strtotime($second_latest_timein_old." -180 minutes"));
							}
							if( $currenttime>= $mid_night && $currenttime<=$second_latest_timein){
								$data['currentdate'] 		= $currentdate;
								$data['work_schedule_id'] 	= $r_emp;
								$data['start_time'] 		= $latest_timein;
								$data['end_time'] 			= $end_time;
							}
						}
					}
				}
			}
		}
		return $data;
	}
	
	public function activate_nightmare_trapv2($comp_id,$emp_no,$work_schedule_id = "",$activate_no_schedule = false,$sync_date="",$sync_employee_time_in_id = ""){
	
		$data = array();
		$check_emp_no = $this->login_screen_model->new_check_emp_info($emp_no,$comp_id);
	
		if($sync_date){
			$currentdate = date('Y-m-d',strtotime($sync_date." -1 days"));
			$currenttime = $sync_date;
				
		}else{
			$currentdate = date('Y-m-d',strtotime(" -1 days"));
			$currenttime = date('Y-m-d H:i:s');
		}
		
		if(isset($check_emp_no->emp_id)){
			$emp_id = $check_emp_no->emp_id;
		}
		else{
			$emp_id = 0;
		}
	
		$r_emp = $this->emp_work_schedule2($emp_id, $comp_id,$currentdate);
	
		if($r_emp || $activate_no_schedule){
			
			if($sync_employee_time_in_id!=""){
				
				$w = array(
						"a.payroll_cloud_id"		=> $emp_no,
						"a.user_type_id"			=> "5",
						"eti.employee_time_in_id" 	=> $sync_employee_time_in_id,
						"eti.status" 				=> "Active"
				);
			}
			else{
				$w = array(
						"a.payroll_cloud_id"=> $emp_no,
						"a.user_type_id"	=> "5",
						"eti.status" 		=> "Active",
						"eti.comp_id" 		=> $comp_id
				);
			}
			$this->edb->where($w);
				
			$xz = array(
					'time_in' 				=> 'eti.time_in',
					'time_out' 				=> 'eti.time_out',
					'date' 					=> 'eti.date',
					'employee_time_in_id' 	=> 'eti.employee_time_in_id'
			);
			$this->edb->select($xz);
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
			$this->db->order_by("eti.time_in","DESC");
			$q = $this->edb->get("employee_time_in AS eti",1,0);
			$r = $q->row();
			
			if(!$r){
				if(!$activate_no_schedule){
					$work_schedule_id = $this->emp_work_schedule($emp_no,$comp_id);
				}
				$r = (object) array(
						'work_schedule_id' 		=> $work_schedule_id,
						'time_in' 				=> date('Y-m-d H:i:s'),
						'time_out' 				=> "",
						'date' 					=> date('Y-m-d'),
						'employee_time_in_id' 	=> 0
				);
			}
	
			//NO SCHEDULE
			if($activate_no_schedule){
	
			}
				
			$day 	= date('l',strtotime($currentdate));
			$w_uwd 	= array(
					"work_schedule_id"	=> $r_emp,
					"company_id"		=> $comp_id,
					"days_of_work" 		=> $day,
					"status" 			=> 'Active'
			);
			$this->db->where($w_uwd);
				
			$q_uwd = $this->db->get("regular_schedule");
			$r_uwd = $q_uwd->row();
	
			/**get the advance schedule **/
			// time 12am - 4am
			$mid_date = date($currentdate);
			$advance_date = date('Y-m-d',strtotime($mid_date." +1 day"));

			
			$sched2 = $this->one_day_plus($mid_date,$r_emp, $comp_id,$emp_id);
			
			if($sched2){
				$start_time =  date('Y-m-d H:i:s', strtotime($advance_date." ".$sched2->work_start_time));
				$end_time =  date('Y-m-d H:i:s', strtotime($advance_date." ".$sched2->work_end_time));
	
				$wStarttime = $sched2->work_start_time;
				$mins = 240;
				if($sched2->latest_time_in_allowed){
					$mins = $sched2->latest_time_in_allowed + $mins;
					$wStarttime = date('H:i:s',strtotime($wStarttime. " +{$sched2->latest_time_in_allowed}  minutes"));
					$start_time = date('Y-m-d H:i:s',strtotime($advance_date." ".$wStarttime));
						
				}
	
				if(strtotime($wStarttime) >= strtotime("00:00:00") && strtotime($wStarttime ) <= strtotime("04:00:00")){
					
					$start_timez3 = date('Y-m-d H:is',strtotime($start_time." -{$mins} minutes"));
	
					if($currenttime >= $start_timez3){
						$data['currentdate'] 		= $advance_date;
						$data['work_schedule_id'] 	= $r_emp;
						$data['start_time'] 		= $start_timez3;
						$data['end_time'] 			= $end_time;
						$data['refresh'] 			= true;
	
						return $data;
					}else{
						$data['currentdate'] 		= date('Y-m-d');
						$data['work_schedule_id'] 	= $r_emp;
						$data['start_time'] 		= $start_timez3;
						$data['end_time'] 			= $end_time;
						$data['refresh'] 			= false;
	
						return $data;
					}
				}
			}
			
			if($q_uwd->num_rows() > 0){
	
				$start_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$r_uwd->work_start_time. " -240 minutes"));
				$end_time 	=  date('Y-m-d H:i:s', strtotime($currentdate." ".$r_uwd->work_end_time));
				$mid_night 	= date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
				
				$sched 		= $this->one_day_plus($currentdate,$r_emp, $comp_id,$emp_id);
	
				// check if the next day is restday or dont have schedule
				// return the previous date
				if($start_time>=$end_time && $currenttime>=$mid_night && $r->time_out == ""){
					$end_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$r_uwd->work_end_time ." +1 day"));
					
					if(!$sched){
						$data['currentdate'] 		= $currentdate;
						$data['work_schedule_id'] 	= $r_emp;
						$data['start_time'] 		= $start_time;
						$data['end_time'] 			= $end_time;
	
						return $data;
					}
				}
	
	
				if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM"  && $currenttime >= $mid_night){
					
					$end_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$r_uwd->work_end_time ." +1 day"));
	
					if($sched){
						$esday 			= date('l',strtotime($currentdate.' +1 day'));
						$esdayx_starts 	= date('Y-m-d H:i:s',strtotime($esday. " ". $sched->work_start_time));
	
						if($sched->latest_time_in_allowed){
							$esdayx_starts = date('Y-m-d H:i:s',strtotime($esdayx_starts. "-{$sched->latest_time_in_allowed}  minute"));
						}
	
						$esdayx_start = date('Y-m-d H:is',strtotime($esdayx_starts."-240 minutes"));
	
					}
						
					// filter the start time of tomorrow shedule
					// if no start time return false
					$add_other_day = false;
					if(isset($esdayx_start)){
	
						if($currenttime <= $esdayx_start ){
							$add_other_day = true;
						}
					}
					$isTodayDate = false;
						
					if(!($currenttime > $end_time) || $r->time_out =="" ){

						if($sched){
							$today_date = $esdayx_start;
							if($currenttime > $today_date){
								$isTodayDate = true;
							}
						}
					}else{
						$isTodayDate = true;
					}
						
					if($r->time_in >= $mid_night){
						$time_in = date('Y-m-d',strtotime($r->time_in));
							
						if(($r->date == $currentdate && $time_in > $currentdate && $r->time_out =="" && !$isTodayDate)  || $add_other_day ){
							$data['currentdate'] 		= $currentdate;
							$data['work_schedule_id'] 	= $r_emp;
							$data['start_time'] 		= $start_time;
							$data['end_time'] 			= $end_time;
						}
						else{
							// new employee, no log in employee
							if($r){
	
								$data['currentdate'] 		= date('Y-m-d');
								$data['work_schedule_id'] 	= $r_emp;
								$data['start_time'] 		= null;
							}
							else{
								$data['currentdate'] 		= $currentdate;
								$data['work_schedule_id'] 	= $r_emp;
								$data['start_time'] 		= null;
							}
						}
					}else{
	
						if($isTodayDate){
							return false;
						}
						else{
							$data['currentdate'] 		= $currentdate;
							$data['work_schedule_id'] 	= $r_emp;
							$data['start_time'] 		= $start_time;
							$data['end_time'] 			= $end_time;
						}
					}
				}else{
					// if the user timeout in the other day
					// scene: timein: 1pm to 1am
					// compress
					// tomorrow date
					if($sched){
						$tomdate_set = date('Y-m-d',strtotime($currentdate.' +1 day'));
						$tomdate =date('Y-m-d H:i:s',strtotime($tomdate_set." ".$sched->work_start_time. " -240 minutes"));
	
						if($currenttime >= $mid_night && $currenttime <=$tomdate && $r->time_out == ""){
							$data['currentdate'] = $currentdate;
							$data['work_schedule_id'] = $r_emp;
							$data['start_time'] = $start_time;
							$data['end_time'] = $end_time;
						}
					}
				}
					
			} //end regular
			else{
				// SPLIT SCHEDULE SETTINGS
	
				//$this->get_starttime($schedule_blocks_id,date('Y-m-d'));
				$w_date = array(
						"es.valid_from <="		=>	$currentdate,
						"es.until >="			=>	$currentdate
				);
				$this->db->where($w_date);
					
	
				$w_ws = array(
						//"payroll_group_id"=>$payroll_group,
						"em.work_schedule_id"=>$r_emp,
						"em.company_id"=>$comp_id,
						"em.emp_id" => $check_emp_no->emp_id
				);
				$this->db->where($w_ws);
				$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
				$q_ws = $this->edb->get("employee_sched_block AS em");
				$r_ws = $q_ws->result();
					
				if($q_ws->num_rows() > 0){
						
					$first = reset($r_ws);
					$lastx = end($r_ws);
	
					$first_time 				= $this->get_starttime($first->schedule_blocks_id,$currentdate,$first);
					$mid_night 					= date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
					$mid_night2 				= date('Y-m-d',strtotime($mid_night));
					$last_timex 				= $this->get_endtime($lastx->schedule_blocks_id,$currentdate,$lastx);
					$last_schedule_blocks_id 	= $lastx->schedule_blocks_id;
						
					if($first_time > $last_timex){
						$last_time = date('H:i:s',strtotime($last_timex));
						$last_time = date("Y-m-d H:i:s",strtotime($mid_night2. " ".$last_time));
					}
					else{
						$yest 						= $this->yesterday_split_info($currenttime, $emp_id, $r_emp, $comp_id,true);
						$last 						= end($yest);
						$last_time 					= $last['end_time'];
						$last_schedule_blocks_id 	= $last['schedule_block_id'];
						$mid_night 					= date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00 +1 day"));
					}
						
					$wx = array(
							"sbti.employee_time_in_id" 	=> $r->employee_time_in_id,
							"sbti.status" 				=> "Active"
					);
						
					$this->edb->where($wx);
					$this->db->order_by("sbti.time_in","DESC");
					$qx = $this->edb->get("schedule_blocks_time_in AS sbti",1,0);
					$rx = $qx->row();
	
					$schedule_blocks_id = 0;
					$time_out 			= "";
					$gdate 				= "";
					if($rx){
						$schedule_blocks_id = $rx->schedule_blocks_id;
						$time_out 			= $rx->time_out;
						$gdate 				= $rx->date;
					}
	
					$mid_time = date('H:i:s',strtotime($last_timex));
					$today= date('Y-m-d');
	
					$w_date = array(
							"es.valid_from <="		=>	$today,
							"es.until >="			=>	$today
					);
					$this->db->where($w_date);
	
						
					$w_ws = array(
							"em.work_schedule_id"	=> $r_emp,
							"em.company_id"			=> $comp_id,
							"em.emp_id" 			=> $check_emp_no->emp_id
					);
					$this->db->where($w_ws);
					$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
					$q_wsx = $this->edb->get("employee_sched_block AS em");
					$r_wsx = $q_wsx->result();
	
					if(($currenttime > $mid_night && $currenttime <= $last_time) || ($last_schedule_blocks_id == $schedule_blocks_id && $time_out == "") ){
						$last_date = true;
	
						if($r_wsx){
							$first_today = reset($r_wsx);
							$last_today = end($r_wsx);
								
							$start_last2 = $this->get_starttime($first_today->schedule_blocks_id,$today,$first_today);
							$start_date_now = date('Y-m-d H:i:s',strtotime($start_last2." -240 minutes"));
								
							// kwaon ang date kng ang currenttime ni greater than sa grace time
							// gamit ni cya  scenario nga wla ka timeout sa last block o wla cta ka log sa last block
							if($currenttime>= $start_date_now){
								$data['currentdate'] = date('Y-m-d');
								$last_date = false;
							}
						}
	
						if($last_date){
							$data['currentdate'] = $currentdate;
						}
					}
	
					//last schedule block
					//user time in in a next day
	
					if($first->schedule_blocks_id == $lastx->schedule_blocks_id){
	
						$start_last = $this->get_starttime($first->schedule_blocks_id,$currentdate,$first);
						$end_last = $this->get_endtime($lastx->schedule_blocks_id,$currentdate,$lastx);
	
						if($r_wsx){
							$first_today = reset($r_wsx);
							$last_today = end($r_wsx);
	
							$start_last2 = $this->get_starttime($first_today->schedule_blocks_id,$today,$first_today);
							$start_date_now = date('Y-m-d H:i:s',strtotime($start_last2." -240 minutes"));
	
							// kwaon ang date kng ang currenttime ni greater than sa grace time
							// gamit ni cya  scenario nga wla ka timeout sa last block o wla cta ka log sa last block
							if($currenttime>= $start_date_now){
									
								return false;
							}
						}
						$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
	
						// kng ang timein niya nilabang sa sunod adlaw
						// kng wla sd cya timeout sa iya previous log;
						// Scene 4 nightshift (see document)
						if($currenttime>=$mid_night && date('A',strtotime($start_last)) == "PM" && date('A',strtotime($end_last)) == "AM" ){
							$data['currentdate'] = $currentdate;
						}
					}
						
					//kng ang iya end time sa last block kai natunong sa midnight(00:00:00)
					if( !($r_wsx) && $time_out==""){
						$data['currentdate'] = $currentdate;
					}
				} // end split
				else{
					// FLEXIBLE HOURS
					$arrx5 = array(
							'duration_of_lunch_break_per_day',
							'latest_time_in_allowed',
							'total_hours_for_the_day'
					);
					$this->edb->select($arrx5);
					$w_fh = array(
							"work_schedule_id"	=> $r_emp,
							"company_id"		=> $comp_id
					);
					$this->edb->where($w_fh);
					$q_fh = $this->edb->get("flexible_hours");
					$r_fh = $q_fh->row();
	
					if($q_fh->num_rows() > 0){
							
						$number_of_breaks_per_day 	= $r_fh->duration_of_lunch_break_per_day;
						$total_hours 				= $r_fh->total_hours_for_the_day * 60;
						$latest_timein 				= date('Y-m-d H:i:s',strtotime($currentdate." ".$r_fh->latest_time_in_allowed));
						$end_time_check 			= date('H:i:s',strtotime($r_fh->latest_time_in_allowed. " +{$total_hours} minutes"));
						$end_time 					= date('Y-m-d H:i:s',strtotime($latest_timein. " +{$total_hours} minutes"));
						$mid_night 					= date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
							
						if($r_fh->latest_time_in_allowed > $end_time_check){
							$advance_date2 			= date('Y-m-d',strtotime($currentdate. " +1 day"));
							$sched2 				= $this->one_day_plus_flex($advance_date2, $comp_id,$emp_id);
							$second_latest_timein 	= false;
								
							if($sched2){
								$second_latest_timein_old 	= $sched2->start_time;
								$second_latest_timein 		= date('Y-m-d H:i:s',strtotime($second_latest_timein_old." -180 minutes"));
							}
							if( $currenttime>= $mid_night && $currenttime<=$second_latest_timein){
								$data['currentdate'] 		= $currentdate;
								$data['work_schedule_id'] 	= $r_emp;
								$data['start_time'] 		= $latest_timein;
								$data['end_time'] 			= $end_time;
							}
						}
					}
				}
			}
		}
	
		return $data;
	}
	
	public function one_day_plus_flex($advance_date, $comp_id,$emp_id){
	
		$arr = array();
		$r_emp = $this->emp_work_schedule2($emp_id, $comp_id,$advance_date);
		$w_fh = array(
				"work_schedule_id"=>$r_emp,
				"company_id"=>$comp_id
		);
		$this->edb->where($w_fh);
		$q_fh = $this->edb->get("flexible_hours");
		$r_fh = $q_fh->row();
		
		if($r_fh){
		
			$latest = $r_fh->latest_time_in_allowed;
			if($latest){
				$start = date('Y-m-d H:i:s',strtotime($advance_date." ".$latest));
				$hr = ($r_fh->total_hours_for_the_day * 60) + $r_fh->duration_of_lunch_break_per_day;
				$end = date('Y-m-d H:i:s',strtotime($start." +{$hr} minutes"));
				$arr = (object)array(
						'start_time' => $start,
						'end_time' => $end
				);
				return $arr; 
			}
			
		}
		
		return false;
	}
	
	/**
	 * Check Employee Work Schedule ID
	 * @param unknown_type $emp_id
	 * @param unknown_type $check_company_id
	 * @param unknown_type $currentdate
	 */
	public function emp_work_schedule2($emp_id,$check_company_id,$currentdate){
		// employee group id
		$s = array(
				"ess.work_schedule_id"
		);
		$w_date = array(
				"ess.valid_from <="		=>	$currentdate,
				"ess.until >="			=>	$currentdate
		);
		$this->db->where($w_date);
			
		$w_emp = array(
				"ess.emp_id"=>$emp_id,
				"ess.company_id"=>$check_company_id,
				"ess.status"=>"Active",
				"ess.payroll_group_id" => 0
		);
		$this->edb->select($s);
		$this->edb->where($w_emp);
		$q_emp = $this->edb->get("employee_shifts_schedule AS ess");
		$r_emp = $q_emp->row();
	
		if ($r_emp) {
			return $r_emp->work_schedule_id;
		}else{
				
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
	
	public function activate_nightmare_trap_upload($comp_id,$emp_no,$time_in,$time_out){


		$data = array();
		$check_emp_no = $this->login_screen_model->new_check_emp_info($emp_no,$comp_id);
		$date = date('Y-m-d',strtotime($time_in. " +1 day"));
		
		$currentdate = date('Y-m-d',strtotime($time_in." -1 days"));
		$currenttime =  date('Y-m-d H:i:s',strtotime($time_in));
		
		if(isset($check_emp_no->emp_id)){
			$emp_id = $check_emp_no->emp_id;
		}else{
			$emp_id = 0;
		}
		$r_emp = $this->emp_work_schedule2($emp_id, $comp_id,$currentdate);
		
		$today_work_schedule = $this->emp_work_schedule2($emp_id, $comp_id,$date);
		
		if($r_emp){
				
			
			$w = array(
					"a.payroll_cloud_id"=>$emp_no,
					"a.user_type_id"=>"5",
					"eti.status" => "Active"
			);
			
			$this->edb->where($w);
				
			$xz = array(
					'time_in' => 'eti.time_in',
					'time_out' => 'eti.time_out',
					'date' => 'eti.date',
					'employee_time_in_id' => 'eti.employee_time_in_id'
			);
			$this->edb->select($xz);
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
			$this->db->order_by("eti.time_in","DESC");
			$q = $this->edb->get("employee_time_in AS eti",1,0);
			$r = $q->row();
			
			if(!$r){
				$work_schedule_id = $this->emp_work_schedule($emp_no,$comp_id);
		
				$r = (object) array(
						'work_schedule_id' => $work_schedule_id,
						'time_in' => date('Y-m-d H:i:s'),
						'time_out' => "",
						'date' => date('Y-m-d'),
						'employee_time_in_id' => 0
				);
		
			}
		
			
				
			$day = date('l',strtotime($currentdate));
				
			$w_uwd = array(
					//"payroll_group_id"=>$payroll_group,
					"work_schedule_id"=>$r_emp,
					"company_id"=>$comp_id,
					"days_of_work" => $day,
					"status" => 'Active'
			);
			$this->db->where($w_uwd);
				
			$q_uwd = $this->db->get("regular_schedule");
			$r_uwd = $q_uwd->row();
			
			if($q_uwd->num_rows() > 0){
				
				$start_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$r_uwd->work_start_time));
				$end_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$r_uwd->work_end_time));
				$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
				
				$sched = $this->one_day_plus($currentdate,$r_emp, $comp_id,$emp_id);
				
				// check if the next day is restday or dont have schedule
				// return the previous date
				if($start_time>=$end_time && $currenttime>=$mid_night && $time_out == ""){
					$end_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$r_uwd->work_end_time ." +1 day"));
						
						
					if(!$sched){
						$data['current_date'] = $currentdate;
						$data['work_schedule_id'] = $r_emp;
						$data['start_time'] = $start_time;
						$data['end_time'] = $end_time;
				
						return $data;
					}
				}
				
				if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM"  && $currenttime > $mid_night){
				
					$end_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$r_uwd->work_end_time ." +1 day"));
						
					/**get the current schedule **/
				//	$sched = $this->one_day_plus($currentdate,$r_emp, $comp_id,$emp_no);
					/*if($currenttime >= $start_time && $currenttime <= $end_time){
						$data['current_date'] = $currentdate;
						$data['work_schedule_id'] = $r_emp;
						$data['start_time'] = $start_time;
					} */
					
					/**get the current schedule **/
					$sched = $this->one_day_plus($currentdate,$r_emp, $comp_id,$emp_id);
						
					$isTodayDate = false;
					if(!($currenttime > $end_time) || $time_out =="" ){
						
						if($sched){
							$today_date = date('Y-m-d H:i:s',strtotime($sched->work_start_time ." -2 hours"));					
							if($currenttime > $today_date || $time_in > $end_time){
								$isTodayDate = true;
							}
						}
					}else{
						$isTodayDate = true;
					}
						
					if($time_in >= $mid_night && $time_in <= $end_time){
						
							$data['current_date'] = $currentdate;
							$data['work_schedule_id'] = $r_emp;
							$data['start_time'] = $start_time;
							$data['end_time'] = $end_time;
						
					}else{

							return false;
						
					}
				}
					
			} //end regular
			else{
				
				$arrx4 = array(
						'duration_of_lunch_break_per_day',
						'total_hours_for_the_day',
						'latest_time_in_allowed'
				);
				$this->edb->select($arrx4);
				$w_fh = array(
						//"payroll_group_id"=>$payroll_group,
						"work_schedule_id"=>$r_emp,
						"company_id"=>$comp_id
				);
				$this->db->where($w_fh);
				$q_fh = $this->edb->get("flexible_hours");
				$r_fh = $q_fh->row();
				$twelve_am = false;
												

				if($q_fh->num_rows() > 0){
				
					$number_of_breaks_per_day = $r_fh->duration_of_lunch_break_per_day;
					//$total_hours_of_the_day = ($r_fh->total_hours_for_the_day * 60) - $number_of_breaks_per_day;
					$total_hours_of_the_day = ($r_fh->total_hours_for_the_day * 60);
					$x =  $total_hours_of_the_day / 2 ;
				
					if($r_fh->latest_time_in_allowed != NULL){
				
						
						
						$start_time = date('Y-m-d H:i:s',strtotime($currentdate." ".$r_fh->latest_time_in_allowed));
						$end_time = date('Y-m-d H:i:s',strtotime($start_time .' +'.$total_hours_of_the_day.' minutes'));
						$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
						
						
						
							
						if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM" && $currenttime > $mid_night){
							
							$isTodayDate = true;
						
							/*if($currenttime >= $mid_night && $currenttime <= $end_time){
								echo "dragon";
								$isTodayDate = true;
							}*/
							
							if(!($currenttime > $end_time) || $r->time_out =="" ){
								//$end_time2 = date('Y-m-d H:i:s',strtotime($end_time .' +1 day'));
								if($currenttime >= $start_time && $currenttime <= $end_time){
									$isTodayDate = false;
								}
							}
							
							if($isTodayDate){
									
								return false;
							}else{
									
								$data['current_date'] = $currentdate;
								$data['work_schedule_id'] = $r_emp;
								$data['start_time'] = $start_time;
								$data['end_time'] = $end_time;
							}
						}
					}
				
				
				}else{
				// SPLIT SCHEDULE SETTINGS
		
				//$this->get_starttime($schedule_blocks_id,date('Y-m-d'));
				$w_date = array(
				"es.valid_from <="		=>	$currentdate,
				"es.until >="			=>	$currentdate
				);
				$this->db->where($w_date);
					
		
				$w_ws = array(
						//"payroll_group_id"=>$payroll_group,
						"em.work_schedule_id"=>$r_emp,
						"em.company_id"=>$comp_id,
						"em.emp_id" => $check_emp_no->emp_id
				);
				$this->db->where($w_ws);
				$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
				$q_ws = $this->edb->get("employee_sched_block AS em");
				$r_ws = $q_ws->result();
		
				if($q_ws->num_rows() > 0){
						
					$first = reset($r_ws);
					$last = end($r_ws);
		
					$first_time = $this->get_starttime($first->schedule_blocks_id,$currentdate,$first);
					$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
					$mid_night2 = date('Y-m-d',strtotime($mid_night));
					$last_time = $this->get_endtime($last->schedule_blocks_id,$currentdate,$last);
					$last_schedule_blocks_id = $last->schedule_blocks_id;
						
					if($first_time > $last_time){
						$last_time = date('H:i:s',strtotime($last_time));
						$last_time = date("Y-m-d H:i:s",strtotime($mid_night2. " ".$last_time));
							
					}else{
						$yest = $this->yesterday_split_info($currenttime, $emp_id, $r_emp, $comp_id,true);
						$last = end($yest);
						$last_time = $last['end_time'];
						$last_schedule_blocks_id = $last['schedule_block_id'];
						$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00 +1 day"));
					}
						
					$wx = array(
							"sbti.employee_time_in_id" => $r->employee_time_in_id,
							"sbti.status" => "Active"
					);
						
					$this->edb->where($wx);
					$this->db->order_by("sbti.time_in","DESC");
					$qx = $this->edb->get("schedule_blocks_time_in AS sbti",1,0);
					$rx = $qx->row();
					$schedule_blocks_id =0;
					$time_out ="";
					if($rx){
						$schedule_blocks_id = $rx->schedule_blocks_id;
						$time_out = $rx->time_out;
		
					}
					
					if($currenttime > $mid_night && $currenttime <= $last_time || ($last_schedule_blocks_id == $schedule_blocks_id && $time_out == "")){
				
						$data['current_date'] = $currentdate;
						//eerror here
					}
					
				} //end split
		
			}
		 }
		}
		
		return $data;
		
	}
	
	/**
	 * Check if user has a tomorrow schedule
	 * @param unknown $currentdate
	 * @param unknown $work_schedule_id
	 * @param unknown $comp_id
	 * @param string $emp_no
	 * @return Ambigous <string, unknown>|boolean
	 */
	public function one_day_plus($currentdate,$work_schedule_id,$comp_id,$emp_id ="0"){
		
		$day 			= date('l',strtotime($currentdate.' +1 day'));
		$currentdate 	= date('Y-m-d',strtotime($currentdate." +1 day"));
		$check_schedule = $this->emp_work_schedule2($emp_id,$comp_id,$currentdate);
		if($check_schedule){
			$w_uwd = array(
					"work_schedule_id"=>$check_schedule,
					"company_id"=>$comp_id,
					"days_of_work" => $day,
					"status" => 'Active'
			);
			$this->db->where($w_uwd);
			$q_uwd = $this->edb->get("regular_schedule");
			$r_uwd = $q_uwd->row();
		
			return ($q_uwd->num_rows > 0) ? $r_uwd : "";
		}
		return false;
	}
	/**
	 * Import split schedule for add FUNCTION
	 * @param unknown $time_in
	 * @param unknown $time_out
	 * @param unknown $work_schedule_id
	 * @param unknown $comp_id
	 * @param unknown $emp_id
	 * @param unknown $lunch_in
	 * @param unknown $lunch_out
	 */

	public function import_split_sched($time_in,$time_out,$work_schedule_id,$comp_id,$emp_id,$lunch_in,$lunch_out,$log_screen = false,$half_day = false,$emp_no="",$gDate="",$source=""){
		
		if($source == ""){
			$source = "import";
		}
		$no_sched_yesterday = false;
		$calculate 			= false;
		$employee_time_in 	= false;
		$currentdate 		= date('Y-m-d',strtotime($time_in.' -1 day'));
		$today 				= true;
		$schedule_blocks = $this->list_of_blocks($currentdate,$emp_id,$work_schedule_id,$comp_id);
		// check for yesterday sched blocks
		if($schedule_blocks){
			$no_sched_yesterday = false;
			$first_sched 		= reset($schedule_blocks);
			$last_sched 		= max($schedule_blocks);
			$ystart 			= $this->get_starttime($first_sched->schedule_blocks_id,$currentdate,$first_sched);	
			$yend 				= $this->get_endtime($last_sched->schedule_blocks_id,$currentdate,$last_sched);
			$lastsched 			= $last_sched->schedule_blocks_id;
			
			if($ystart > $yend){
				$yend = date('Y-m-d H:i:s',strtotime($yend." +1 day"));
			}
			
			if($time_in >= $ystart && $time_in <= $yend){
				$today= false;
			}
		}
		else{
			// if no sched block get current date sched
			$no_sched_yesterday = true;
			$schedule_blocks 	= $this->list_of_blocks($gDate,$emp_id,$work_schedule_id,$comp_id);
			// check for yesterday sched blocks
			if($schedule_blocks){
				$first_sched 	= reset($schedule_blocks);
				$last_sched 	= max($schedule_blocks);
				$ystart 		= $this->get_starttime($first_sched->schedule_blocks_id,$currentdate,$first_sched);
				$yend 			= $this->get_endtime($last_sched->schedule_blocks_id,$currentdate,$last_sched);
				$lastsched 		= $last_sched->schedule_blocks_id;
					
				if($ystart > $yend){
					$yend = date('Y-m-d H:i:s',strtotime($yend." +1 day"));
				}
				
				if($time_in >= $ystart && $time_in <= $yend){
					$today= false;
				}
			}
		}
		// if no schedblocks yesterday and timein is not belong in the current shift
		if($no_sched_yesterday && !$today){
			$h 					= $this->total_hours_worked($time_out,$time_in);
			$holday_hour		= $this->convert_to_hours($h);
			$time_query_where	= array(
								"comp_id"	=> $comp_id,
								"emp_id"	=> $emp_id,
								"date"		=> $gDate,
								"status"	=> 'Active'
								);
			
			$this->db->where($time_query_where);
			$time_query 	= $this->edb->get('employee_time_in');
			$time_query_row = $time_query->row();
			$time_query->free_result();
			
			if($time_query_row) {
				$tq_update_field = array(
						"source"				=> $source,
						"time_in"				=> $time_in,
						"time_out"				=> $time_out,
						"work_schedule_id" 		=> $work_schedule_id,
						"total_hours"			=> $holday_hour,
						"total_hours_required"	=> $holday_hour
						);
			
				$this->db->where($time_query_where);
				$this->db->update('employee_time_in',$tq_update_field);
			}
			else{
				$date_insert = array(
						"comp_id"				=> $comp_id,
						"emp_id"				=> $emp_id,
						"date"					=> $gDate,
						"source"				=> $source,
						"time_in"				=> $time_in,
						"time_out"				=> $time_out,
						"work_schedule_id"		=> $work_schedule_id,
						"total_hours"			=> $holday_hour ,
						"total_hours_required"	=> $holday_hour
						);
				$this->db->insert('employee_time_in', $date_insert);
			}
			return true;
		}
		
		if($today){
			$currentdate 		= date('Y-m-d',strtotime($time_in));
			if($gDate){
				$currentdate = $gDate;
			}
			$schedule_blocks 	= $this->list_of_blocks($currentdate,$emp_id,$work_schedule_id,$comp_id);
			
			$first_sched 		= reset($schedule_blocks);
			$last_sched 		= max($schedule_blocks);
			$xstart 			= $this->get_starttime($first_sched->schedule_blocks_id,$time_in,$first_sched);	
			$end 				= $this->get_endtime($last_sched->schedule_blocks_id,$time_in,$last_sched);
			$lastsched 			= $last_sched->schedule_blocks_id;
			$start 				= '';
			$xend				= date('Y-m-d H:i:s',strtotime($end." -1 day"));
			
			//*** get the difference between the first start time and the last end time
			$total      = strtotime($xstart) - strtotime($xend);
			$hours      = floor($total / 60 / 60);
			$minutes    = floor(($total - ($hours * 60 * 60)) / 60);
			$ret 		= ($hours * 60) + $minutes;
			$total_gap  = ($ret < 0) ? '0' : $ret;
			$total_gap	= $total_gap/2;
			
			if($total_gap > 120){
				$total_gap = 120;
			}
			// 11pm - 3am / 8am - 12pm
			$start 		= date('Y-m-d H:i:s',strtotime($xstart." -".$total_gap." minutes"));
			if($start > $end){
				$end	= date('Y-m-d H:i:s',strtotime($end." +1 day"));
			}
			
			if($time_in >= $start && $time_in <= $end){
				$employee_time_in = true;
			}
		}
		
		if($gDate){
			$currentdate = $gDate;
		}
		else{
			$vx = $this->activate_nightmare_trap_upload($comp_id,$emp_no,$time_in,$time_out);
			
			if($vx){
				$currentdate = $vx['current_date'];
			}
		}
		
		$w 	= array(
			"eti.emp_id" 	=> $emp_id,
			"eti.comp_id" 	=> $comp_id,
			"eti.date" 		=> $currentdate,
			"eti.status" 	=> "Active"
			);
		
		$this->edb->where($w);
		$q 			= $this->edb->get("employee_time_in AS eti",1,0);
		$check 		= $q->row();
		
		$split_info = $this->new_get_splitinfo("",$comp_id,$work_schedule_id,$emp_id,$gDate,$time_in);
		
		if($split_info){
			if($split_info['schedule_blocks_id'] == $lastsched){
				$calculate = true;
			}
			$break 				=  $split_info['break_in_min'];
			$schedule_blocks_id = $split_info['schedule_blocks_id'];
			$start_time 		= $this->get_starttime($schedule_blocks_id,$time_in);	
			$end_time 			= $split_info['end_time'];
		}
		
		// use for compute undertime
		$endDate				= $gDate;
		if($split_info['first_block_start_time'] > $split_info['start_time']){
			$endDate = date('Y-m-d',strtotime($endDate." +1 day"));
		}
		else if($split_info['end_time'] < $split_info['start_time']){
			$endDate = date('Y-m-d',strtotime($endDate." +1 day"));
		}
		$endx 		= date('Y-m-d H:i:s',strtotime($endDate." ".$split_info['end_time']));
		$d_time_out = strtotime($endx);
		$t_time_out = strtotime($time_out);
		// enduse for compute undertime
		
		$first_block 		= reset($schedule_blocks);
		
		$start_first 		= $this->get_starttime($first_block->schedule_blocks_id,$time_in);
		$first_block_id 	= $first_block->schedule_blocks_id;
		$first_block_detail = $this->get_blocks_list($first_block_id);
		$first_block_detail_start_time = $first_block_detail->start_time;
		$startdate			= $currentdate;
		
		if($split_info['start_time'] < $first_block_detail_start_time){
			$startdate		= date("Y-m-d",strtotime($currentdate ."+ 1 day"));
		}
		
		$tardiness 			= 0;
		$undertime			= 0;
		$hours_worked		= 0;
		$total_hours_worked	= 0;
		$min_break 			= 0;
		$minutes 			= 0;
		$total_hours		= $split_info['total_hours_work_per_block'];
		$total_hours_min	= $total_hours * 60;
		$total_hours_req 	= $this->get_total_hours_req($split_info['schedule_blocks_id']);
		$start_time_block	= $startdate." ".$split_info['start_time'];
		
		if($check){
			$last_id = $check->employee_time_in_id;
			#check update area
			$wx = array(
					"sbti.employee_time_in_id" 	=>  $check->employee_time_in_id,
					"sbti.status" 				=> "Active"
			);
			
			$this->edb->where($wx);
			$qx 		= $this->edb->get("schedule_blocks_time_in AS sbti");	
			$split_res 	= $qx->result();
			if($qx){
				foreach($split_res as $split_time){
					if($split_time->schedule_blocks_id == $split_info['schedule_blocks_id']){
						//*** COMPUTE FOR TARDINESS
						if($time_in > $start_time_block){
							//*** get the difference between the end time and the timeout
							$late      	= strtotime($time_in) - strtotime($start_time_block);
							$hoursl     = floor($late / 60 / 60);
							$minutesl   = floor(($late - ($hoursl * 60 * 60)) / 60);
							$retl 		= ($hoursl * 60) + $minutesl;
							$late_min  	= ($retl < 0) ? '0' : $retl;
							$late_min	= ($late_min > $total_hours_min) ? $total_hours_min : $late_min;
						}
						else{
							$late_min 			= 0;
						}
						
						if($lunch_in && $lunch_out){
							$overbreak_min 	= $this->overbreak_min($comp_id,$gDate,$emp_id,$work_schedule_id,$lunch_out,"",$lunch_in);
						}
						else{
							$overbreak_min 	= 0;
						}
						
						$tardiness				= $late_min + $overbreak_min;
						
						//*** END COMPUTE FOR TARDINESS
					 	
					 	//*** COMPUTE FOR UNDERTIME
					 	if($t_time_out < $d_time_out){
					 		//*** get the difference between the end time and the timeout
							$unde      	= strtotime($endx) - strtotime($time_out);
							$hoursu     = floor($unde / 60 / 60);
							$minutesu   = floor(($unde - ($hoursu * 60 * 60)) / 60);
							$retu 		= ($hoursu * 60) + $minutesu;
							$undertime  = ($retu < 0) ? '0' : $retu;
							$undertime	= ($undertime > $total_hours_min) ? $total_hours_min : $undertime;
					 	}
					 	else{
					 		$undertime 			= 0;
					 	}
					 	//*** END COMPUTE FOR UNDERTIME
					 	
					 	//*** COMPUTE FOR total hours worked
					 	$rett0 = 0;
					 	$thw      				= strtotime($time_out) - strtotime($time_in);
					 	$hourst     			= floor($thw / 60 / 60);
					 	$minutest   			= floor(($thw - ($hourst * 60 * 60)) / 60);
					 	$rett1 					= ($hourst * 60) + $minutest;
					 	
					 	if($lunch_out && $lunch_in){
						 	$thw      			= strtotime($lunch_in) - strtotime($lunch_out);
						 	$hourst     		= floor($thw / 60 / 60);
						 	$minutest   		= floor(($thw - ($hourst * 60 * 60)) / 60);
						 	$rett0 				= ($hourst * 60) + $minutest;
					 	}
					 	
					 	$rett					= $rett1 - $rett0;
					 	$total_hours_worked  	= ($rett < 0) ? '0' : $rett/60;
					 	
					 	//*** END COMPUTE FOR total hours worked
					 	
					 	$split_where 	= array(
					 					"schedule_blocks_time_in_id"	=> $split_time->schedule_blocks_time_in_id,
					 					);
					 	
					 	if($log_screen){
					 		$tq_update_field = array(										
								"schedule_blocks_id" 	=> $split_info['schedule_blocks_id'],
								"source"				=> "import",
								"time_in"				=> $time_in,									
								"time_out"				=> $time_out,
								"undertime_min"			=> 0,
						 		"tardiness_min" 		=> 0,
								"total_hours"			=> 0,
					 			"late_min" 				=> 0,
					 			"overbreak_min" 		=> 0,
								"total_hours_required"	=> 0
							);
					 	}
					 	else{
						 	$tq_update_field = array(										
								"schedule_blocks_id" 	=> $split_info['schedule_blocks_id'],
								"source"				=> $source,
								"time_in"				=> $time_in,									
								"time_out"				=> $time_out,
								"undertime_min"			=> $undertime,
						 		"tardiness_min" 		=> $tardiness,
								"total_hours"			=> $total_hours,
						 		"late_min" 				=> $late_min,
						 		"overbreak_min" 		=> $overbreak_min,
								"total_hours_required"	=> $total_hours_worked,
						 		"source"				=> $source
							);
						 	
						 	if($split_info['break_in_min']){
						 		$tq_update_field["lunch_out"] = $lunch_out;
						 		$tq_update_field["lunch_in"] = $lunch_in;
						 	}
					 	}
					 	
						$this->db->where($split_where);
						$this->db->update('schedule_blocks_time_in',$tq_update_field);
						
						$this->up_date_current_time_in($last_id,$comp_id);
					}
					else{
						//*** COMPUTE FOR TARDINESS
						if($time_in > $start_time_block){
							//*** get the difference between the end time and the timeout
							$late      	= strtotime($time_in) - strtotime($start_time_block);
							$hoursl     = floor($late / 60 / 60);
							$minutesl   = floor(($late - ($hoursl * 60 * 60)) / 60);
							$retl 		= ($hoursl * 60) + $minutesl;
							$late_min  	= ($retl < 0) ? '0' : $retl;
							$late_min	= ($late_min > $total_hours_min) ? $total_hours_min : $late_min;
						}
						else{
							$late_min 			= 0;
						}
						
						if($lunch_in && $lunch_out){
							$overbreak_min 	= $this->overbreak_min($comp_id,$gDate,$emp_id,$work_schedule_id,$lunch_out,"",$lunch_in);
						}
						else{
							$overbreak_min 	= 0;
						}
						
						$tardiness				= $late_min + $overbreak_min;
						
						//*** END COMPUTE FOR TARDINESS
					 	
					 	//*** COMPUTE FOR UNDERTIME
					 	if($t_time_out < $d_time_out){
					 		//*** get the difference between the end time and the timeout
							$unde      	= strtotime($endx) - strtotime($time_out);
							$hoursu     = floor($unde / 60 / 60);
							$minutesu   = floor(($unde - ($hoursu * 60 * 60)) / 60);
							$retu 		= ($hoursu * 60) + $minutesu;
							$undertime  = ($retu < 0) ? '0' : $retu;
							$undertime	= ($undertime > $total_hours_min) ? $total_hours_min : $undertime;
					 	}
					 	else{
					 		$undertime 			= 0;
					 	}
					 	//*** END COMPUTE FOR UNDERTIME
					 	
					 	//*** COMPUTE FOR total hours worked
					 	$rett0 = 0;
					 	$thw      				= strtotime($time_out) - strtotime($time_in);
					 	$hourst     			= floor($thw / 60 / 60);
					 	$minutest   			= floor(($thw - ($hourst * 60 * 60)) / 60);
					 	$rett1 					= ($hourst * 60) + $minutest;
					 	
					 	if($lunch_out && $lunch_in){
						 	$thw      			= strtotime($lunch_in) - strtotime($lunch_out);
						 	$hourst     		= floor($thw / 60 / 60);
						 	$minutest   		= floor(($thw - ($hourst * 60 * 60)) / 60);
						 	$rett0 				= ($hourst * 60) + $minutest;
					 	}
					 	
					 	$rett					= $rett1 - $rett0;
					 	$total_hours_worked  	= ($rett < 0) ? '0' : $rett/60;
					 	
					 	//*** END COMPUTE FOR total hours worked
					 	
						if($log_screen){
							$date_insert2 	= array(
									"comp_id"				=> $comp_id,
									"emp_id"				=> $emp_id,
									"employee_time_in_id" 	=> $last_id,
									"schedule_blocks_id" 	=> $schedule_blocks_id,
									"date"					=> date('Y-m-d',strtotime($time_in)),
									"source"				=> "import",
									"time_in"				=> $time_in,
									"time_out"				=> $time_out,
									"tardiness_min"			=> 0,
									"undertime_min"			=> 0,
									"total_hours"			=> 0,
									"total_hours_required"	=> 0
							);
						}
						else{
							$date_insert2 = array(
									"comp_id"				=> $comp_id,
									"emp_id"				=> $emp_id,
									"employee_time_in_id" 	=> $last_id,
									"schedule_blocks_id" 	=> $schedule_blocks_id,
									"date"					=> date('Y-m-d',strtotime($time_in)),
									"source"				=> $source,
									"time_in"				=> $time_in,
									"time_out"				=> $time_out,
									"undertime_min"			=> $undertime,
									"late_min" 				=> $late_min,
									"tardiness_min"			=> $tardiness,
									"overbreak_min"			=> $overbreak_min,
									"total_hours"			=> $split_info['total_hours_work_per_block'],
									"total_hours_required"	=> $total_hours_worked
							);
						}
						
						if($break){
							$date_insert2['lunch_out'] 	= $lunch_out;
							$date_insert2['lunch_in'] 	= $lunch_in;
						}
						$this->db->insert('schedule_blocks_time_in', $date_insert2);
							
						$this->up_date_current_time_in($last_id,$comp_id);
					}
				}
			}
				
		}else{
			//*** COMPUTE FOR TARDINESS
			
			if($time_in > $start_time_block){
				//*** get the difference between the end time and the timeout
				$late      	= strtotime($time_in) - strtotime($start_time_block);
				$hoursl     = floor($late / 60 / 60);
				$minutesl   = floor(($late - ($hoursl * 60 * 60)) / 60);
				$retl 		= ($hoursl * 60) + $minutesl;
				$late_min  	= ($retl < 0) ? '0' : $retl;
				$late_min	= ($late_min > $total_hours_min) ? $total_hours_min : $late_min;
			}
			else{
				$late_min 			= 0;
			}
			
			if($lunch_in && $lunch_out){
				$overbreak_min 	= $this->overbreak_min($comp_id,$gDate,$emp_id,$work_schedule_id,$lunch_out,"",$lunch_in);
			}
			else{
				$overbreak_min 	= 0;
			}
			
			$tardiness				= $late_min + $overbreak_min;
			
			//*** END COMPUTE FOR TARDINESS
			
			//*** COMPUTE FOR UNDERTIME
			if($t_time_out < $d_time_out){
				//*** get the difference between the end time and the timeout
				$unde      	= strtotime($endx) - strtotime($time_out);
				$hoursu     = floor($unde / 60 / 60);
				$minutesu   = floor(($unde - ($hoursu * 60 * 60)) / 60);
				$retu 		= ($hoursu * 60) + $minutesu;
				$undertime  = ($retu < 0) ? '0' : $retu;
				$undertime	= ($undertime > $total_hours_min) ? $total_hours_min : $undertime;
			}
			else{
				$undertime 			= 0;
			}
			//*** END COMPUTE FOR UNDERTIME
				
			//*** COMPUTE FOR total hours worked
		 	$rett0 = 0;
		 	$thw      				= strtotime($time_out) - strtotime($time_in);
		 	$hourst     			= floor($thw / 60 / 60);
		 	$minutest   			= floor(($thw - ($hourst * 60 * 60)) / 60);
		 	$rett1 					= ($hourst * 60) + $minutest;
		 	
		 	if($lunch_out && $lunch_in){
			 	$thw      			= strtotime($lunch_in) - strtotime($lunch_out);
			 	$hourst     		= floor($thw / 60 / 60);
			 	$minutest   		= floor(($thw - ($hourst * 60 * 60)) / 60);
			 	$rett0 				= ($hourst * 60) + $minutest;
		 	}
		 	
		 	$rett					= $rett1 - $rett0;
		 	$total_hours_worked  	= ($rett < 0) ? '0' : $rett/60;
		 	
		 	//*** END COMPUTE FOR total hours worked
			
			if($employee_time_in){	
				$date_insert = array(
						"comp_id"			=> $comp_id,
						"emp_id"			=> $emp_id,
						"work_schedule_id" 	=> $work_schedule_id,
						"date"				=> $currentdate,				
						"source"			=> $source,
						"time_in"			=> $time_in,
						"total_hours"		=> $split_info['total_hour_block_sched']
					);
				 $this->db->insert('employee_time_in', $date_insert);
				 $last_id = $this->db->insert_id();
			}
			
			if($log_screen){
				$date_insert2 	= array(
								"comp_id"				=> $comp_id,
								"emp_id"				=> $emp_id,
								"employee_time_in_id" 	=> $last_id,
								"schedule_blocks_id" 	=> $schedule_blocks_id,
								"date"					=> date('Y-m-d',strtotime($time_in)),
								"source"				=> $source,
								"time_in"				=> $time_in,
								"time_out"				=> $time_out,
								"tardiness_min"			=> 0,
								"undertime_min"			=> 0,
								"total_hours"			=> 0,
								"total_hours_required"	=> 0
								);
			}
			else{
				$date_insert2 = array(
						"comp_id"				=> $comp_id,
						"emp_id"				=> $emp_id,
						"employee_time_in_id" 	=> $last_id,
						"schedule_blocks_id" 	=> $schedule_blocks_id,
						"date"					=> date('Y-m-d',strtotime($time_in)),
						"source"				=> $source,
						"time_in"				=> $time_in,								
						"time_out"				=> $time_out,
						"undertime_min"			=> $undertime,
						"late_min" 				=> $late_min,
						"tardiness_min"			=> $tardiness,
						"total_hours"			=> $split_info['total_hours_work_per_block'],
						"total_hours_required"	=> $total_hours_worked
				);								
			}
			
			if($break){
				$date_insert2['lunch_out'] = $lunch_out;
				$date_insert2['lunch_in'] = $lunch_in;
			}

			$this->db->insert('schedule_blocks_time_in', $date_insert2);
			$this->up_date_current_time_in($last_id,$comp_id);
		}
	}
	
	/**
	 * display the date and time of yesterday 
	 * if activate today is true, display today schedule
	 * @param unknown $time_in
	 * @param unknown $emp_id
	 * @param unknown $work_schedule_id
	 * @param unknown $comp_id
	 * @return multitype:multitype:string
	 */
	public function yesterday_split_info($time_in,$emp_id,$work_schedule_id,$comp_id,$activate_today =false){
		
		$arr = array();
		//for night schedule;
		$yesterday_m = date('Y-m-d',strtotime($time_in));
		if($activate_today)
			$yesterday = date('Y-m-d',strtotime($yesterday_m));
		else // for every schedule
			$yesterday = date('Y-m-d',strtotime($yesterday_m. " -1 day"));
		
		$select = array(
				'schedule_blocks_id' => 'em.schedule_blocks_id'
		);
		
		$yest_list = $this->list_of_blocks($yesterday,$emp_id,$work_schedule_id,$comp_id,$select); //zzz
		$mid_night = date('Y-m-d H:i:s',strtotime($yesterday." 24:00:00"));
		
		if($yest_list){
			$first_sched = reset($yest_list);
			$last_sched = max($yest_list);
			$ystart = $this->get_starttime($first_sched->schedule_blocks_id,$yesterday,$first_sched);
			$yend = $this->get_endtime($last_sched->schedule_blocks_id,$yesterday,$last_sched);
			$check_end = $this->get_starttime($last_sched->schedule_blocks_id,$yesterday,$last_sched);
			
			foreach ($yest_list as $row){
				
				$block = $this->get_blocks_list($row->schedule_blocks_id);		
				$start_f = date('Y-m-d H:i:s',strtotime($yesterday." ".$block->start_time));
				$end_f = date('Y-m-d H:i:s',strtotime($yesterday." ".$block->end_time));
				
				if($end_f <=$start_f)
					$end_f = date('Y-m-d H:i:s',strtotime($end_f. " +1 day"));
				
				if($start_f >= $ystart && $start_f <= $mid_night ){
					
					$arr[] = array(
							'start_time' => $start_f,
							'end_time' => $end_f,
							'work_schedule_id' => $work_schedule_id,
							'schedule_block_id' => $row->schedule_blocks_id
					);
				}else{
					
					$arr[] = array(
							'start_time' => date('Y-m-d H:i:s',strtotime($start_f." +1 day")),
							'end_time' => date('Y-m-d H:i:s',strtotime($end_f." +1 day")),
							'work_schedule_id' => $work_schedule_id,
							'schedule_block_id' => $row->schedule_blocks_id
					);
				}
			}
		}
		
		return $arr;
	}
	
	/**
	 * Get the workschedule info to display in timelogs
	 * @param unknown $emp_id
	 * @param unknown $check_company_id
	 */
	public function get_workschedule_info_for_no_workschedule($emp_id,$check_company_id,$date,$work_schedule_id = "",$activate = false){
		
		$where = array(
				'e.company_id'=>$check_company_id,
				'e.emp_id' => $emp_id
		);
		$this->db->where($where);
		$arr2 = array(
				'payroll_cloud_id' => 'a.payroll_cloud_id'
		);
		$this->edb->select($arr2);
		$this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
		$q = $this->edb->get('employee AS e');
		$result = $q->row();
        $data = "";
        $res = array();
		if($result){
			$emp_no = $result->payroll_cloud_id;
			
			$payroll_group_id = $this->emp_login->payroll_group_id($emp_no,$check_company_id);			
			
			$day = date('l',strtotime($date));
			$w_uwd = array(
					//"payroll_group_id"=>$payroll_group,
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$check_company_id,
					"days_of_work" => $day,
					"status" => 'Active'
			);
			$this->edb->where($w_uwd);
			$arr4 = array(
					'work_schedule_name' => 'work_schedule_name',
					'work_end_time' => 'work_end_time',
					'work_start_time' => 'work_start_time',
					'break_in_min' => 'break_in_min',
					'total_work_hours' => 'total_work_hours'
			);
			$this->edb->select($arr4);
			$q_uwd = $this->edb->get("regular_schedule");
			
			$r_uwd = $q_uwd->row();
			if($q_uwd->num_rows() > 0){
			    
			    if($activate){
			        $arr = array(
			            'start_time' => $r_uwd->work_start_time,
			            'end_time' => $r_uwd->work_end_time,
			            'break' => $r_uwd->break_in_min,
			            'total_hours' => $r_uwd->total_work_hours,
			            'name' => $r_uwd->work_schedule_name,
			            'type' => 1
			        );
			        return $arr;
			    }else{
			        
			        $data = time12hrs($r_uwd->work_start_time)."-".time12hrs($r_uwd->work_end_time)."<br>";
			        $data .= "break: ".$r_uwd->break_in_min." mins";
                    $data .= "<br> Total Hours: ".$r_uwd->total_work_hours;
                    $res = array(
                        'start_time' => time12hrs($r_uwd->work_start_time),
                        'end_time' => time12hrs($r_uwd->work_end_time),
                        'break' => $r_uwd->break_in_min,
                        'total_hours' => $r_uwd->total_work_hours
                    );
			    }
			}else{
				// FLEXIBLE HOURS
				$fw = array(
					"f.company_id"=>$check_company_id,
					"f.work_schedule_id"=>$work_schedule_id,
					//"f.status" => 'Active'
				);
				$this->db->where($fw);
				$arr3 = array(
						'latest_time_in_allowed' => 'f.latest_time_in_allowed',
						'name' => 'ws.name',
						'duration_of_lunch_break_per_day' => 'duration_of_lunch_break_per_day',
						'total_hours_for_the_day' => 'total_hours_for_the_day'
				);
				//$this->edb->select($arr3);
				$this->edb->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
				$fq = $this->edb->get("flexible_hours AS f");
				$r_fh = $fq->row();
				
				if($fq->num_rows() > 0){
					$data = $r_fh;
					$total_h = $r_fh->total_hours_for_the_day - ($r_fh->duration_of_lunch_break_per_day / 60);
					$total_h = number_format($total_h,2);
					if($activate){
					    
					    $arr = array(
					        'start_time' => $r_fh->latest_time_in_allowed,
					        'end_time' => "",
					        'break' => $r_fh->duration_of_lunch_break_per_day,
					        'total_hours' => $r_fh->total_hours_for_the_day,
					        'name' => '',
					        'type' => 2
					    );
					    
					    return $arr;
					}else{
					    if($r_fh->latest_time_in_allowed != NULL || $r_fh->latest_time_in_allowed != ""){
					        $data  = "Latest Timein: ".time12hrs($r_fh->latest_time_in_allowed) . " <br> ";
					        $data .= "break: ".$r_fh->duration_of_lunch_break_per_day. " mins";
                            $data .= "<br> Total hours: ". $total_h;
                            $res = array(
                                'start_time' => time12hrs($r_uwd->work_start_time),
                                'end_time' => time12hrs($r_uwd->work_end_time),
                                'break' => $r_uwd->break_in_min,
                                'total_hours' => $r_uwd->total_work_hours
                            );
					    }else{
					        //$data = $r_fh->name;
					        $data = "break: ".$r_fh->duration_of_lunch_break_per_day." mins";
                            $data .= "<br> Total hours: ". $total_h;
                            $res = array(
                                'start_time' => "",
                                'end_time' => "",
                                'break' => $r_fh->duration_of_lunch_break_per_day,
                                'total_hours' => $total_h
                            );
					    }
					}
				}
			}
			return $res;
		}
	}
	
	
	/**
	 * Check if user is time in of half his/her work schedule
	 * @param unknown $row_time_in
	 * @param unknown $work_schedule_id
	 * @param unknown $comp_id
	 * @param unknown $row_time_out
	 * @return boolean
	 */
	public function if_half_day($row_time_in,$work_schedule_id,$comp_id,$emp_no,$row_time_out = "",$employee_time_in_id = "",$emp_id="",$datex =""){
		
		$currentdate = date('Y-m-d');
		
		if($datex){
			$day = date("l",strtotime($datex));
		}else{
			$day = date("l",strtotime($row_time_in));
			$datex = date('Y-m-d',strtotime($row_time_in));
		}
		$uni_where = array(
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id,
				"days_of_work"=>$day,
				"status" => 'Active'
		);
		$this->db->where($uni_where);
		$sql = $this->db->get("regular_schedule");
		$row = $sql->row();
		
		if($row){
			
			if($row->latest_time_in_allowed != NULL){
				$payroll_sched_timein = date('H:i:s',strtotime($row->work_start_time) + ($row->latest_time_in_allowed * 60)) ;				
				$start_time = date('Y-m-d H:i:s',strtotime($datex. " ".$payroll_sched_timein));
			}else{
				$start_time = date('Y-m-d H:i:s',strtotime($datex. " ".$row->work_start_time));				
			}
			
			$end_time = date('Y-m-d H:i:s',strtotime($datex. " ".$row->work_end_time));
			//night shift
			$vx = $this->activate_nightmare_trap($comp_id,$emp_no);
			
			if($vx){
				$currentdate = $vx['currentdate'];
			}
			
			$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
				
			if(date("A",strtotime($row->work_start_time)) == "PM" && date("A",strtotime($row->work_end_time)) == "AM"){	
				$start_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$row->work_start_time));
				$end_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$row->work_end_time." +1 day"));
			}					
			
			$total      = strtotime($end_time) - strtotime($start_time);
			$hours      = floor($total / 60 / 60);
			$minutes    = round(($total - ($hours * 60 * 60)) / 60);
			$x =  (($hours * 60) + $minutes)/2 ;
			
			$half_date = date('Y-m-d H:i:s',strtotime($start_time. " +".$x. " minutes"));
	
			if($row_time_in >= $half_date){
				//tardinnes
				return 1;
			}else if($row_time_out <= $half_date){
				//undertime
				return 2;
			}else{
				return 0;
			}
			
		}else{
			$arrx4 = array(
				'duration_of_lunch_break_per_day',
				'total_hours_for_the_day',
				'latest_time_in_allowed'
			);
			$this->edb->select($arrx4);
			$w_fh = array(
					//"payroll_group_id"=>$payroll_group,
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id
			);
			$this->db->where($w_fh);
			$q_fh = $this->edb->get("flexible_hours");
			$r_fh = $q_fh->row();
		
			if($q_fh->num_rows() > 0){
				$number_of_breaks_per_day = $r_fh->duration_of_lunch_break_per_day;
				$total_hours_of_the_day = ($r_fh->total_hours_for_the_day * 60) + $number_of_breaks_per_day;
			
				$x =  $total_hours_of_the_day / 2 ;
				
				if($r_fh->latest_time_in_allowed != NULL){
					$start_time = date('Y-m-d H:i:s',strtotime($datex." ".$r_fh->latest_time_in_allowed));
					$end_time = date('Y-m-d H:i:s',strtotime($start_time .' +'.$total_hours_of_the_day.' minutes'));

				}else{
					
					$start_time = date('Y-m-d H:i:s',strtotime($row_time_out .' -'.$total_hours_of_the_day.' minutes'));
					$end_time = $row_time_out;
				}
					
				$currentdate =$datex;

				$half_date= date('Y-m-d H:i:s',strtotime($start_time. " +".$x. " minutes"));
				
				if($row_time_in >= $half_date){
					//tardinnes
					return 1;
				}else if($row_time_out <= $half_date){
					//undertime
					return 2;
				}else{
					return 0;
				}
			}else{
				$block_completed 		= 0;
				$block_not_completed 	= 0;
				$new_timein 			= false;
				// SPLIT SCHEDULE SETTINGS
				$val 					= $this->check_endtime_notempty($emp_no,$comp_id,$work_schedule_id,$employee_time_in_id); //xxxe
				$last_timein_sched 		= $this->filter_date_tim_in($emp_no, $comp_id,true);
				
				if($last_timein_sched === true || $val){
					$currentdate = date('Y-m-d',strtotime($row_time_in));
				}else{
					$currentdate = date('Y-m-d',strtotime($last_timein_sched));
				}
				
				//nightshift people
				$night = $this->activate_nightmare_trap($comp_id, $emp_no);
				
				if($night){
					$currentdate = $night['currentdate'];				
				}
				$w_date = array(
						"es.valid_from <="		=> $currentdate,
						"es.until >="			=> $currentdate
				);
				$this->db->where($w_date);
				$w_ws = array(
						"em.work_schedule_id"	=> $work_schedule_id,
						"em.company_id"			=> $comp_id,
						"em.emp_id" 			=> $emp_id
				);
				$this->db->where($w_ws);
				$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
				$q_ws = $this->edb->get("employee_sched_block AS em");
				$r_ws = $q_ws->result();

				if($q_ws->num_rows() > 0){
						
					$w = array(
							"employee_time_in_id"=>$employee_time_in_id,
							"eti.status" => "Active"
					);
					$this->edb->where($w);
					$this->db->order_by("eti.time_in","DESC");
					$split_q = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
					$query_split = $split_q->row();
				
					if($query_split){
							
						if($query_split->time_out !=""){
							$new_timein = true;
							$block_completed = $query_split->schedule_blocks_id;
						}else{
							$time_in = $query_split->time_in;
							$block_not_completed = $query_split->schedule_blocks_id;
						}
					}else{ // no data found, it is first block of split
						$read_first_split = true;
					}
				
					$night = $this->activate_nightmare_trap($comp_id, $emp_no);
					$split = $this->get_splitschedule_info($r_ws,$row_time_in,$night,$block_completed,$block_not_completed,$new_timein,$val,$night);
					
					if($split){
						$hours 		= $this->total_hours_worked($split['end_time'],$split['start_time']);
						$x 			=  $hours /2 ;
						$half_date 	= date('Y-m-d H:i:s',strtotime($split['start_time']. " +".$x. " minutes"));
						
						if($row_time_in >= $half_date){
							//tardinnes
							return 1;
						}else if($row_time_out <= $half_date){
							//undertime
							return 2;
						}else{
							return 0;
						}
					}
				}
			}
		}
		return false;
	}

	/**
	 * check if mac address exist in the setting
	 */
	public function mac_address_verification($val){
		$this->db->where('mac_address',$val);
		$q 		= $this->db->get('company_mac_address');
		$result = $q->row();
		
		return ($result) ? $result : false;
	}
	
	public function company_info($company_id){
		$this->db->where('company_id',$company_id);
		$q 		= $this->db->get('company');
		$result = $q->row();
		
		return ($result) ? $result : false;
	}
	
	public function get_last_timein($comp_id){
		$this->db->where('comp_id',$comp_id);
		$this->db->order_by("time_in","DESC");
		$q 		= $this->db->get('employee_time_in');
		$result = $q->row();
		
		if($result->time_in !=""){
			$time = $result->time_in;
		}elseif($result->lunch_out !=""){
			$time = $result->lunch_out;
		}elseif($result->lunch_in!=""){
			$time = $result->lunch_in;
		}elseif($result->time_out!=""){
			$time = $result->time_out;
		}
		
		return ($result) ? $time : false;
	}
	
	
	public function mac_activate($comp_id,$data){
		$this->db->where('company_id', $comp_id);
		$this->db->update('company_mac_address', $data);
	}
	
	public function mac_check_activate($company_id){
		$this->db->where('company_id',$company_id);
		$q = $this->db->get('company_mac_address');
		$result = $q->row();
		
		return ($result) ? $result: false;
	}
	
	public function is_leave($emp_id){
		$currentdate = date('Y-m-d H:i:s');
		$this->edb->where('emp_id',$emp_id);
		$w_emp = array(
				'date_start',
				'date_end'
		);
		
		$this->edb->select($w_emp);
		$q = $this->edb->get('employee_leaves_application');
		$result = $q->result();
		
		foreach($result as $row):
		
			if($row->date_start >= $currentdate && $currentdate <= $row->date_end){
				return true;
			}
		endforeach;
		
		return false;
	}
	
	public function get_company_address($comp_id){				
		$ip 	= get_ip();
		$w_emp 	= array(
				'name' => 'lo.name'
		);
		
		$this->edb->select($w_emp);
		
		$this->edb->where('eid.company_id',$comp_id);
		$this->edb->where('ip_address',$ip);
		$this->edb->join("location_and_offices AS lo","lo.location_and_offices_id = eid.location_and_offices_id","LEFT");
		$q = $this->edb->get('employee_ip_address AS eid');
		$result = $q->row();
		
		if($result)
			return $result->name;
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
				
	public function check_company_under_psa_id($company_id,$psa_id){
		

		$this->edb->where('company_id',$comp_id);
		$this->edb->where('payroll_system_account_id',$psa_id);
		$q = $this->edb->get('assigned_company');
		$result = $q->row();
		
		return ($result) ? true : false;
	}
		
	/***
	 * check if user has default scheduled or it has schedule added
	 * @param unknown $emp_id
	 * @param unknown $comp_id
	 * @return Ambigous <boolean, unknown>
	 */
	public function if_no_workschedule($emp_id,$comp_id){
			
		$w = array(
				"epi.emp_id"=>$emp_id,
				"epi.company_id"=>$comp_id
		);
		$this->edb->where($w);
	
		$arrx = array(
				'work_schedule_id' => 'pg.work_schedule_id'
		);
		$this->edb->select($arrx);
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$q = $this->edb->get("employee_payroll_information AS epi");
		$r = $q->row();
	
		return ($r) ?  $r->work_schedule_id : 0;
	}
		
		
	public function get_shift_project($id){
		
		$arrx = array(
				'project_name' => 'p.project_name'
		);
		$this->edb->select($arrx);
		
		$this->edb->where("schedule_blocks_id",$id);
		$this->edb->join("project AS p","p.project_id = sb.project_id","LEFT");
		$q = $this->edb->get("schedule_blocks AS sb");
		$r = $q->row();
		
		return ($r) ?  $r : false;
	}
		
	public function get_shift_location($id){	
		
		
		$arrx = array(
				'name' => 'lo.name'
		);
		$this->edb->select($arrx);
		
		$this->edb->where("schedule_blocks_id",$id);
		$this->edb->join("location_and_offices AS lo","lo.location_and_offices_id = sb.location_and_offices_id","LEFT");
		$q2 = $this->edb->get("schedule_blocks AS sb");
		$r2 = $q2->row();
		
		return ($r2) ?  $r2 : false;
	}
		
	/**
	 * Insert Time In Log (this for mobile)
	 * @param unknown_type $date
	 * @param unknown_type $emp_no
	 * @param unknown_type $min_log
	 * @param unknown_type $work_schedule_id
	 */
	public function insert_time_in_mobile($date,$emp_no,$min_log,$work_schedule_id,$check_type="",$source="mobile",$activate_no_schedule = false, $sync_date ="",$sync_employee_time_in_id ="",$comp_id = 0){
		
		// check the correct shift here
		$number_of_breaks_per_day		= 0;
		$barack_date_trap_exact_t_date 	= date('Y-m-d H:i:00');
		$barack_date_trap_exact_date 	= date('Y-m-d');
		
		// CHECK LAST PUNCH WORK SCHEDULE
		$last_timein = $this->check_last_sched_time_in($emp_no,$sync_employee_time_in_id,$comp_id);
		if($last_timein){
			$date_last_time = $last_timein->date;
		}
		// CHECK IF CURRENT TIME IS ALREADY IN THE WORKSCHEDULE OF CURRENT DAY
		$current_date_start = $this->check_current_date_start($emp_no,$barack_date_trap_exact_date,$comp_id);
		if($current_date_start){
			$date_comp = $barack_date_trap_exact_date." ".$current_date_start->work_start_time;
			$date_comp = date('Y-m-d H:i:00	',strtotime($date_comp ."-120 minutes"));
			
			// IF CURRENT TIME IS NOT BELONG TO THE CURRENT DATE SCHED THIS PUNCH IN IS FOR YESTERDAY SCHED
			if(strtotime($date_comp) > strtotime($barack_date_trap_exact_t_date)){
				$barack_date_trap_exact_date 	= date('Y-m-d',strtotime($barack_date_trap_exact_date ."-1 day"));
			}
		}
		// IF THE CURRENT DATE DONT HAVE SCHEDULE MEANS THIS DATE IS REST DAY AND ALREADY TIMEIN THIS MEANS THIS PUNCHIN BELONG TO YESTERDAY SCHED
		else if($check_type != "time in" && $date_last_time != $barack_date_trap_exact_date){
			$barack_date_trap_exact_date 	= date('Y-m-d',strtotime($barack_date_trap_exact_date ."-1 day"));
		}
		$barack_date_trap_exact_n_date 	= date('Y-m-d',strtotime($barack_date_trap_exact_date ."+1 day"));
		$global_rest_day				= false;
		$global_rest_n_day				= false;
		$rsched			 				= array();
		$rsched_next			 		= array();
		$ws 							= array(
												'work_type_name',
										);
		
		$current_date					= $barack_date_trap_exact_t_date;
		$kkey = konsum_key();
		// check current day
		$wb = array('work_schedule_id' => $work_schedule_id, 'status' => 'Active');
		$this->db->select($ws);
		$this->db->where($wb);
		$q = $this->db->get('work_schedule');
		$r = $q->row();
		
		if($r){
			$rsched = $this->check_shift_correct_sched($work_schedule_id,$r->work_type_name,$barack_date_trap_exact_date);
			if($rsched){
				$add_oneday_timein 	= date("Y-m-d",strtotime($barack_date_trap_exact_date." +1 day"))." ".$rsched->work_start_time;
			}
		}

		// check current next day
		$s = array(
				'ws.work_schedule_id',
				'ws.work_type_name',
				'e.emp_id',
			);
		$w = array( 
				'a.payroll_cloud_id' => $emp_no,
			);
		$w2 = array( 
				'e.company_id' 		=> $comp_id,
				'ess.valid_from' 	=> $barack_date_trap_exact_n_date,
			);
		
		$this->db->select($s);
		$this->db->where("AES_DECRYPT(`payroll_cloud_id`,'".$kkey."') = '".$emp_no."' ");
		$this->db->where($w2);
		$this->db->join('employee as e','e.emp_id = ess.emp_id');
		$this->db->join('accounts as a','a.account_id = e.account_id');
		$this->db->join('work_schedule as ws','ws.work_schedule_id = ess.work_schedule_id');
		$nxt = $this->db->get('employee_shifts_schedule as ess');
		$rnxt = $nxt->row();
		
		if($rnxt){
			$rsched_next = $this->check_shift_correct_sched($rnxt->work_schedule_id,$rnxt->work_type_name,$barack_date_trap_exact_n_date);
			$emp_id 	 = $rnxt->emp_id;
		}else{
			
			// if not found in employee_shifts_schedule get it from payroll_group
			$spg 	= array( 
					'ws.work_schedule_id',
					'ws.work_type_name',
					'e.emp_id',
					);
			$wpg 	= array(
					'a.payroll_cloud_id' => $emp_no,
					);
			$wpg2 	= array(
					'e.company_id' 		=> $comp_id,
					);
			$this->db->select($spg);
			$this->db->where("AES_DECRYPT(`payroll_cloud_id`,'".$kkey."') = '".$emp_no."' ");
			$this->db->where($wpg2);
			$this->db->join('employee as e','e.emp_id = epi.emp_id');
			$this->db->join('accounts as a','a.account_id = e.account_id');
			$this->db->join('payroll_group as pg','pg.payroll_group_id = epi.payroll_group_id');
			$this->db->join('work_schedule as ws','ws.work_schedule_id = pg.work_schedule_id');
			$nxt_pg = $this->db->get('employee_payroll_information as epi');
			$rnxt = $nxt_pg->row();
			
			if($rnxt){
				$rsched_next = $this->check_shift_correct_sched($rnxt->work_schedule_id,$rnxt->work_type_name,$barack_date_trap_exact_n_date);
				$emp_id 	 = $rnxt->emp_id;
			}
		}
		
		if(!$rsched){
			$global_rest_day = true;
		}
		
		if(!$rsched_next){
			
			$rsched_next	 = array();
			$global_rest_n_day = true;
		}
		
		if($rsched_next){
			$next_date 						= $barack_date_trap_exact_n_date." ".$rsched_next->work_start_time;
			$barack_date_trap_exact_nt_date = date("Y-m-d H:i:00",strtotime($next_date ."-120 minutes"));
			
			if(strtotime($barack_date_trap_exact_t_date) >= strtotime($barack_date_trap_exact_nt_date)){
				$rsched 						= $rsched_next;
				$global_rest_day 				= $global_rest_n_day;
				$barack_date_trap_exact_date 	= $barack_date_trap_exact_n_date;
				$work_schedule_id				= $rnxt->work_schedule_id;
			}
			
			$add_oneday_timein 	= date("Y-m-d",strtotime($barack_date_trap_exact_date." +1 day"))." ".$rsched_next->work_start_time;
		}
		
		$date 		= $barack_date_trap_exact_date;
		$comp_add 	= $this->get_company_address($comp_id);
		// regular schedule
		if($rsched){
			if($rsched->work_start_time){
				$number_of_breaks_per_day 	= $rsched->break_in_min;
				$shift_name 				= "regular schedule";
				$payroll_sched_timein 		= date('Y-m-d H:i:s',strtotime($date." ".$rsched->work_start_time)) ;
				$payroll_sched_timein_orig	= date('Y-m-d H:i:s',strtotime($date." ".$rsched->work_start_time)) ;
				
				if($rsched->latest_time_in_allowed != NULL || $rsched->latest_time_in_allowed != ""){
					$val 					= $rsched->latest_time_in_allowed;
					$threshold_min			= $rsched->latest_time_in_allowed;
					$payroll_sched_timein 	= date('Y-m-d H:i:s',strtotime($payroll_sched_timein  ." +{$val} minutes" )) ;
				}
				$current_date 				= $barack_date_trap_exact_t_date;
				$time_in 					= date("H:i:00",strtotime($current_date));
			}
			else{
				// SPLIT SCHEDULE SETTINGS
				$check_emp_no 		= $this->login_screen_model->new_check_emp_info($emp_no,$comp_id);
				$last_timein_sched 	= $this->filter_date_tim_in($emp_no, $comp_id,true);
	
				//this to identify if the last end time of split is not skipping to time in			
				$val = $this->check_endtime_notempty($emp_no,$comp_id,$work_schedule_id,$r->employee_time_in_id,$r->time_out);
				
				if($last_timein_sched === true || $val){
					$currentdate 	= date('Y-m-d');
					$current_date 	= date('Y-m-d H:i:s');
				}
				else{
					
					$currentdate 	= date('Y-m-d',strtotime($last_timein_sched));
					$current_date 	= date('Y-m-d H:i:s',strtotime($last_timein_sched));
				}
				
				$night = $this->activate_nightmare_trap($comp_id, $emp_no);
				if($night){
					$currentdate = $night['currentdate'];
				}
			 	
				$w_date = array(
						"es.valid_from <="		=>	$currentdate,
						"es.until >="			=>	$currentdate
				);
				$this->db->where($w_date);
				
				$w_ws = array(
						"em.work_schedule_id"	=> $work_schedule_id,
						"em.company_id"			=> $comp_id,
						"em.emp_id" 			=> $check_emp_no->emp_id
				);
				$this->db->where($w_ws);
				$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
				$q_ws = $this->edb->get("employee_sched_block AS em");
				$r_ws = $q_ws->result();
				
			  	if($q_ws->num_rows() > 0){
					$read_first_split =false;
					$w = array(
							"employee_time_in_id"	=> $r->employee_time_in_id,
							"eti.status" 			=> "Active"
					);
					$this->edb->where($w);
					$this->db->order_by("eti.time_in","DESC");
					$split_q 		= $this->edb->get("schedule_blocks_time_in AS eti",1,0);
					$query_split 	= $split_q->row();
					
					$night_date 	= $this->activate_nightmare_trap($comp_id, $emp_no);
					$currentdate 	= date('Y-m-d');
					if($night_date){
						$currentdate = $night_date['currentdate'];
					}
					
					if($currentdate == $r->date){
						$new_employee_timein = false;
					}
					else{
						$new_employee_timein = true;
					}
					$split_total_activate = false;
					
					//get the schedule of split;
					$split = $this->new_get_splitinfo($emp_no, $comp_id, $work_schedule_id);
					
					$this->type = "";
					if($split){
						$this->type = "split";
						$number_of_breaks_per_day 	= $split['break_in_min'];
						$this->schedule_blocks_id 	= $split['schedule_blocks_id'];
						$check_type 				= $split['clock_type'];
						$first_block_start_time 	= $split['first_block_start_time'];
						$shift_name 				= "split schedule";
						if($query_split){
							if($split['last_block'] == $query_split->schedule_blocks_id && $check_type == "time out"){
								$split_total_activate = true;
							}
						}
					}
				}else{
					// FLEXIBLE HOURS
					$w_fh 	= array(
							"work_schedule_id"	=> $work_schedule_id,
							"company_id"		=> $comp_id
							);
					$this->db->where($w_fh);
					$q_fh = $this->db->get("flexible_hours");
					$r_fh = $q_fh->row();
					if($q_fh->num_rows() > 0){
						$number_of_breaks_per_day 	= $r_fh->number_of_breaks_per_day;
						$shift_name 				= "flexible hours";	
					}
				}
			}
		}
		else{
			$arrt = array(
					'time_in'				=> 'eti.time_in',
					'lunch_out' 			=> 'eti.lunch_out',
					'lunch_in' 				=> 'eti.lunch_in',
					'time_out' 				=> 'eti.time_out',
					'late' 					=> 'eti.late_min',
					'overbreak' 			=> 'eti.overbreak_min',
					'tardiness' 			=> 'eti.tardiness_min',
					'employee_time_in_id' 	=> 'eti.employee_time_in_id',
					'date' 					=> 'eti.date',
					'payroll_group_id' 		=> 'epi.payroll_group_id'
			);
			
			$this->edb->select($arrt);
				
			if($sync_employee_time_in_id!=""){
				$w 	= array(
						"a.payroll_cloud_id"		=> $emp_no,
						"a.user_type_id"			=> "5",
						"eti.employee_time_in_id" 	=> $sync_employee_time_in_id,
						"eti.status" 				=> "Active",
						"eti.comp_id" 				=> $comp_id
				);
			}
			else{
				$w 	= array(
						"a.payroll_cloud_id"		=> $emp_no,
						"a.user_type_id"			=> "5",
						"eti.status" 				=> "Active",
						"eti.comp_id" 				=> $comp_id
				);
			}
				
			$this->edb->where($w);
				
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
			$this->db->order_by("eti.time_in","DESC");
				
			$q = $this->edb->get("employee_time_in AS eti",1,0);
			
			$r1 = $q->row();
			
			if($check_type == "time out" || $check_type == 'lunch out' || $check_type == "lunch in"){
				$where_update = array(
						"eti.emp_id"				=> $emp_id,
						"eti.comp_id"				=> $comp_id,
						"eti.employee_time_in_id"	=> $r1->employee_time_in_id,
						"eti.status"				=> "Active"
				);
			
				$get_diff = (strtotime($current_date) - strtotime($r1->time_in)) / 60;
				if($min_log < $get_diff){
					$total_h_r	= (total_min_between($current_date,$r1->time_in) / 60);
					$update_val = array("time_out"=>$current_date,"total_hours_required"=>$total_h_r,"total_hours"=>$total_h_r);
					$this->db->where($where_update);
					
					$update = $this->db->update("employee_time_in AS eti",$update_val);
				}
				
				
				$this->edb->where($where_update);
				$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
				$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
				$q2 = $this->edb->get("employee_time_in AS eti",1,0);
					
				return ($q2) ? $q2->row() : FALSE ;
				exit;
					
			}else if($check_type == "time in"){
				
				$get_diff 	= (strtotime($current_date) - strtotime($r1->time_out)) / 60;
				if($min_log < $get_diff){
					$val 	= array(
							"emp_id"			=> $emp_id,
							"comp_id"			=> $comp_id,
							"date"				=> $date,
							"time_in"			=> $current_date,
							"work_schedule_id" 	=> "-1",
							"source" 			=> $source,
							"location" 			=> $comp_add,
					);
					$insert = $this->db->insert("employee_time_in",$val);
					
					if($insert){
						$w2 	= array(
								"a.payroll_cloud_id"	=> $emp_no,
								"eti.date"				=> $date,
								"eti.status" 			=> "Active"
						);
						$this->edb->where($w2);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$this->db->order_by("eti.time_in","DESC");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							
						return ($q2) ? $q2->row() : FALSE ;
						exit;
					}else{
						$this->edb->where($where_update);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							
						return ($q2) ? $q2->row() : FALSE ;
						exit;
					}
				}
			}
		}
		//check employee on leave
		$onleave 	= check_leave_appliction($date,$emp_id,$comp_id);
		$ileave 	= 'no';
		if($onleave)
			$ileave = 'yes';
		
		
		// if workschedule has no break
		if(($number_of_breaks_per_day == 0 || $number_of_breaks_per_day == NULL)){
			
			// check employee time in
			/*
			$current_date 	= $barack_date_trap_exact_t_date;
			
			$arrz 			= array(
									'time_in'				=> 'eti.time_in',
									'lunch_out' 			=> 'eti.lunch_out',
									'lunch_in' 				=> 'eti.lunch_in',
									'time_out' 				=> 'eti.time_out',
									'late' 					=> 'eti.late_min',
									'overbreak' 			=> 'eti.overbreak_min',
									'tardiness' 			=> 'eti.tardiness_min',
									'employee_time_in_id' 	=> 'eti.employee_time_in_id',
									'date' 					=> 'eti.date',
									'payroll_group_id' 		=> 'epi.payroll_group_id'
							);
			
			if($this->type == "split"){
				$arrz['schedule_blocks_time_in_id'] = "eti.schedule_blocks_time_in_id";
			}
			$this->edb->select($arrz);
			
			if($sync_employee_time_in_id!=""){
				$w 	= array(
						"a.payroll_cloud_id"		=> $emp_no,
						"a.user_type_id"			=> "5",
						"eti.employee_time_in_id" 	=> $sync_employee_time_in_id,
						"eti.status" 				=> "Active",
						"eti.comp_id" 				=> $comp_id
				);
			}
			else{
				$w 	= array(
						"a.payroll_cloud_id"		=> $emp_no,
						"a.user_type_id"			=> "5",
						"eti.status" 				=> "Active",
						"eti.comp_id" 				=> $comp_id
				);
			}
			
			$this->edb->where($w);
			
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
			$this->db->order_by("eti.time_in","DESC");			
			
			if($this->type == "split"){
				$q = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
			}
			else{
				$q = $this->edb->get("employee_time_in AS eti",1,0);
			}
			*/
			$r = $last_timein;
			
			if($q->num_rows() == 0){

				/* CHECK TIME IN START */
				$time_in = date('H:i:s');
				$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id,$time_in,$this->schedule_blocks_id);
				
				if($this->type !="split"){

					if($wst != FALSE){
						// new start time
						$nwst = date("Y-m-d {$wst}");
						$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
					}
					
					//late min for early tardiness
					$late_min = $this->late_min($comp_id, $date, $emp_id, $work_schedule_id);
					
					// insert time in log
					$val = array(
						"emp_id"			=> $emp_id,
						"comp_id"			=> $comp_id,
						"date"				=> $date,
						"work_schedule_id" 	=> $this->work_schedule_id,
						"time_in"			=> $current_date,
						"late_min" 			=> $late_min,
						"source" 			=> $source,
						"flag_on_leave" 	=> $ileave
					);
					$insert = $this->db->insert("employee_time_in",$val);
					
					if($insert){
						$w2 = array(
							"a.payroll_cloud_id"=> $emp_no,
							"eti.date"			=> $date,
							"eti.status" 		=> "Active"
						);
						$this->edb->where($w2);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
		
						return ($q2) ? $q2->row() : FALSE ;
					}
				}
				else{
					$get_diff 		= 0;
					$eti		  	= "";
					$rto		  	= "";
					$ro		 		= "";
					$rl		 	 	= "";
					$rt		  		= "";
					$sbi	  		= "";
					if($r){
						$get_diff 	= (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
						$eti		= $r->employee_time_in_id;
						$sbi		= $r->schedule_blocks_time_in_id;
						$rt			= $r->time_in;
						$rl			= $r->lunch_out;
						$ro			= $r->lunch_in;
						$rto		= $r->time_out;
					}
					$arr = array(
							'emp_no' 				=> $emp_no,
							'current_date' 			=> $current_date,
							'emp_id'				=> $emp_id,
							'comp_id' 				=> $comp_id,
							'breaks' 				=> $number_of_breaks_per_day,
							'check_type' 			=> "time in",
							'min_log' 				=> $min_log,
							'get_diff' 				=> $get_diff,
							'employee_time_in_id' 	=> $eti,
							'work_schedule_id' 		=> $work_schedule_id,
							'block_id' 				=> $sbi,
							'schedule_blocks_id' 	=> $this->schedule_blocks_id,
							'time_in' 				=> $rt,
							'time_out' 				=> $rto,
							'lunch_in' 				=> $ro,
							'lunch_out' 			=> $rl,
							'new_timein' 			=> $new_timein,
							'timein_id' 			=> $timein_id,
							'new_employee_timein' 	=> $new_employee_timein
					);
					return $this->split_schedule_time_in($arr,$split_total_activate,"",$date,$source,$first_block_start_time);
				}
			}
			else{
				// get date time in to date time out
				$workday 			= date("l",strtotime($date));
				$payroll_group_id 	= 0;
	
				// check rest day
				$check_rest_day 	= $this->check_rest_day($workday,$work_schedule_id,$comp_id);
				if($check_rest_day){
					// global where update data
					$where_update = array(
									"eti.emp_id"				=> $emp_id,
									"eti.comp_id"				=> $comp_id,
									"eti.employee_time_in_id"	=> $r->employee_time_in_id,
									"eti.status" 				=> "Active"
									);
					
					if($check_type == "time out"){
						// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE FOR REST DAY
						$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
						if($min_log < $get_diff){
							$update_val = array("time_out"=>$current_date);
							$this->db->where($where_update);
							$update = $this->db->update("employee_time_in AS eti",$update_val);
						}
						
						$this->edb->where($where_update);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
						
						//holiday now
						$holiday = $this->company_holiday($date, $comp_id);
						
						if($holiday){
							$get_diff 	= (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
							$arr 		= array(
										'emp_no' 				=> $emp_no,
										'breaks' 				=> $number_of_breaks_per_day,
										'current_date' 			=> $current_date,
										'date' 					=> $date,
										'emp_id' 				=> $emp_id,
										'comp_id' 				=> $comp_id,
										'check_type' 			=> $check_type,
										'min_log' 				=> $min_log,
										'get_diff' 				=> $get_diff,
										'employee_time_in_id' 	=> $r->employee_time_in_id,
										'work_schedule_id' 		=> $work_schedule_id,
										'time_in' 				=> $r->time_in,
										'time_out' 				=> $r->time_out,
										'lunch_in' 				=> $r->lunch_in,
										'lunch_out' 			=> $r->lunch_out,
										'new_timein' 			=> $new_timein,
										'timein_id' 			=> $timein_id
										);
								
							return $this->holiday_time_in($arr);
						}
						
						// update total hours and total hours required rest day
						$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
						$update_timein_logs = array(
												"tardiness_min"			=> 0,
												"undertime_min"			=> 0,
												"total_hours"			=> $get_total_hours,
												"total_hours_required"	=> $get_total_hours
											);
						$this->db->where($where_update);
						$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
						
						return ($q2) ? $q2->row() : FALSE ;
						exit;
						
					}else if($check_type == "time in"){
						
						/* CHECK TIME IN START */
						$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id,$r->time_in);
						
						if($wst != FALSE){
							// new start time
							$nwst 					= date("Y-m-d {$wst}");
							$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
						}

						// insert time in value for rest day ============================================== >>> INSERT TIME IN VALUE FOR REST DAY
						$insert 	= FALSE;
						$get_diff 	= (strtotime($current_date) - strtotime($r->time_out)) / 60;
						if($min_log < $get_diff){
							$val 	= array(
										"emp_id"			=> $emp_id,
										"comp_id"			=> $comp_id,
										"date"				=> $date,
										"time_in"			=> $current_date,
										"work_schedule_id" 	=> -1,
										"source" 			=> $source,
										"location" 			=> $comp_add,
										"flag_on_leave" 	=> $ileave
									);
							$insert = $this->db->insert("employee_time_in",$val);	
						}
						
						if($insert){
							$w2 	= array(
										"a.payroll_cloud_id"=>$emp_no,
										"eti.date"			=>$date,
										"eti.status" 		=> "Active"
									);
							$this->edb->where($w2);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$this->db->order_by("eti.time_in","DESC");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
							exit;
						}else{
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
							exit;
						}
					}
				}
				
				if($this->type != "split"){
					$workday_settings_start_time = $this->check_workday_settings_start_time($workday,$work_schedule_id,$comp_id,$r->time_in); 
					$workday_settings_end_time = $this->check_workday_settings_end_time($workday,$work_schedule_id,$comp_id,$r->time_in);
					
					if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
						// for night shift time in and time out value for working day
						$check_bet_timein 	= date("Y-m-d")." ".$workday_settings_start_time;
						$check_bet_timeout 	= date("Y-m-d",strtotime("+1 day"))." ".$workday_settings_end_time;
					}else{
						// for day shift time in and time out value for working day
						$check_bet_timein 	= date("Y-m-d")." ".$workday_settings_start_time;
						$check_bet_timeout 	= date("Y-m-d")." ".$workday_settings_end_time;
					}
					
					// check between date time in to date time out
					$add_oneday_timein = date("Y-m-d",strtotime($r->time_in." +1 day"))." ".$workday_settings_start_time;
				}
				
				
				/**
				 * SPLIT SCHEDULE GENERATE
				 * EVERY DAY LOGIN
				 * SCHEDULE IS SPLIT INIT HERE THIS IS THE FIRST TIMEIN OCCUR AND SPLIT SCHED IS ALREADY IN DB
				 */
				
				if($this->type == 'split'){
					
					$get_diff = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
					$arr = array(
							'emp_no' 				=> $emp_no,
							'current_date' 			=> $current_date,
							'emp_id'				=> $emp_id,
							'comp_id' 				=> $comp_id,
							'breaks' 				=> $number_of_breaks_per_day,
							'check_type' 			=> $check_type,
							'min_log' 				=> $min_log,
							'get_diff' 				=> $get_diff,
							'employee_time_in_id' 	=> $r->employee_time_in_id,
							'work_schedule_id' 		=> $work_schedule_id,
							'block_id' 				=> $r->schedule_blocks_time_in_id,
							'schedule_blocks_id' 	=> $this->schedule_blocks_id,
							'time_in' 				=> $r->time_in,
							'time_out' 				=> $r->time_out,
							'lunch_in' 				=> $r->lunch_in,
							'lunch_out' 			=> $r->lunch_out,
							'new_timein' 			=> $new_timein,
							'timein_id' 			=> $timein_id,
							'new_employee_timein' 	=> $new_employee_timein
					
					);
					return $this->split_schedule_time_in($arr,$split_total_activate,"",$date,$source,$first_block_start_time);
				}
				
				if(strtotime($check_bet_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($add_oneday_timein." -2 hours")){
					
					
					// global where update data
					$where_update = array(
									"eti.emp_id"				=> $emp_id,
									"eti.comp_id"				=> $comp_id,
									"eti.employee_time_in_id"	=> $r->employee_time_in_id,
									"eti.status"				=> "Active"
									);
					
					if($check_type == "time out"){
						// update time out value for rest day =============== >>> UPDATE TIME OUT VALUE
						$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
						
						if($min_log < $get_diff){
							$update_val = array("time_out"=>$current_date);
							$this->db->where($where_update);
							$update = $this->db->update("employee_time_in AS eti",$update_val);
						}
						
						$this->edb->where($where_update);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
		
						// update total hours and total hours required rest day
						$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
						
						// tardiness and undertime value
						$update_tardiness = $this->get_tardiness_val($emp_id,$comp_id,$r->time_in,$work_schedule_id,$r->date);  //rrr
						$update_undertime = $this->get_undertime_val($emp_id,$comp_id,$r->time_in,$current_date,$work_schedule_id,$number_of_breaks_per_day,$date); 
						
						// check tardiness value
						$flag_tu = 0;
						
						$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r->time_in)), $emp_id,$work_schedule_id);
						$get_total_hours_worked = ($hours_worked / 2) + .5;
						
						//holiday now
						$holiday = $this->company_holiday($date, $comp_id);
						
						if($holiday){
							$get_diff = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
								
							$arr = array(
									'emp_no' 				=> $emp_no,
									'breaks' 				=> $number_of_breaks_per_day,
									'current_date' 			=> $current_date,
									'date' 					=> $date,
									'emp_id' 				=> $emp_id,
									'comp_id' 				=> $comp_id,
									'check_type' 			=> $check_type,
									'min_log' 				=> $min_log,
									'get_diff' 				=> $get_diff,
									'employee_time_in_id' 	=> $r->employee_time_in_id,
									'work_schedule_id' 		=> $work_schedule_id,
									'time_in' 				=> $r->time_in,
									'time_out' 				=> $r->time_out,
									'lunch_in' 				=> $r->lunch_in,
									'lunch_out' 			=> $r->lunch_out,
									'new_timein' 			=> $new_timein,
									'timein_id' 			=> $timein_id
							);
						
							return $this->holiday_time_in($arr);
						}
						
						$hw 				= $this->convert_to_min($hours_worked);
						$new_total_hours 	= $this->get_tot_hours($emp_id,$comp_id,$r->time_in,$r->lunch_out,$r->lunch_in,$current_date,$hours_worked,$work_schedule_id,$number_of_breaks_per_day); 

						$update_timein_logs = array(
												"tardiness_min"				=> $update_tardiness,
												"undertime_min"				=> $update_undertime,						
												"total_hours"				=> $hours_worked,
												"total_hours_required"		=> $get_total_hours,
												"flag_tardiness_undertime"	=> $flag_tu
											);
						
						$att = $this->calculate_attendance($comp_id,$r->time_in,$current_date);
						
						if($att){
							$total_hours_worked = $this->total_hours_worked($currentdate, $r->time_in);
							$total_hours_worked = $this->convert_to_hours($total_hours_worked);
							
							if($r->time_in >= $r->lunch_out){
								$update_timein_logs['lunch_in'] 	= null;	
								$update_timein_logs['lunch_out'] 	= null;
							}
							elseif($current_date <= $r->lunch_in){
								$update_timein_logs['lunch_in'] 	= null;
								$update_timein_logs['lunch_out'] 	= null;
							}
	
							$update_timein_logs['total_hours_required'] = $total_hours_worked;
							$update_timein_logs['absent_min'] 			= ($hours_worked - $total_hours_worked) * 60;
							$update_timein_logs['late_min'] 			= 0;
							$update_timein_logs['tardiness_min'] 		= 0;
							$update_timein_logs['undertime_min'] 		= 0;
						}
						
						$this->db->where($where_update);
						$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
						
						return ($q2) ? $q2->row() : FALSE ;
						
					}
					else if($check_type == "time in"){
						
						/* CHECK TIME IN START */
						$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id,$r->time_in);
						if($wst != FALSE){
							// new start time
							$nwst = date("Y-m-d {$wst}");
							$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
						}
						
						$late_min = $this->late_min($comp_id, $date, $emp_id, $work_schedule_id);
						
						// insert time in log ============================================= >>>> INSERT NEW TIME IN LOG SAME DATE
						$insert 	= FALSE;
						$get_diff 	= (strtotime($current_date) - strtotime($r->time_out)) / 60;
						if($min_log < $get_diff){
							$val 	= array(
										"emp_id"			=> $emp_id,
										"comp_id"			=> $comp_id,
										"date"				=> $date,
										"time_in"			=> $current_date,
										"work_schedule_id" 	=> $this->work_schedule_id,
										"source" 			=> $source,
										'late_min' 			=> $late_min,
										"location" 			=> $comp_add,
										"flag_on_leave" 	=> $ileave
									);
							$insert = $this->db->insert("employee_time_in",$val);	
						}
						
						if($insert){
							$w2 	= array(
										"a.payroll_cloud_id"=> $emp_no,
										"eti.date"			=> $date,
										"eti.status" 		=> "Active"
									);
							$this->edb->where($w2);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$this->db->order_by("eti.time_in","DESC");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			 
							return ($q2) ? $q2->row() : FALSE ;
						}else{
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
						}
					}
				}else{
					
					if(false){
						/* CHECK TIME IN START */
						$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id,$r->time_in);
						if($wst != FALSE){
							// new start time
							$nwst 					= date("Y-m-d {$wst}");
							$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
						}
						
						// insert time in log
						$val 	= array(
									"emp_id"			=> $r_emp->emp_id,
									"comp_id"			=> $r_emp->company_id,
									"date"				=> $date,
									"work_schedule_id" 	=> $this->work_schedule_id,
									"time_in"			=> $current_date
								);
						$insert = $this->db->insert("employee_time_in",$val);
						
						if($insert){
							$w2 	= array(
										"a.payroll_cloud_id"	=> $emp_no,
										"eti.date"				=> $date,
										"eti.status" 			=> "Active"
									);
							$this->edb->where($w2);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$this->db->order_by("eti.time_in","DESC");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
						}
					}else{
						// global where update data
						$where_update = array(
							"eti.emp_id"				=> $emp_id,
							"eti.comp_id"				=> $comp_id,
							"eti.employee_time_in_id"	=> $r->employee_time_in_id
						);
						
						// comment out by aldrin
						if($check_type == "time out"){
						  
							// update time out value ============================================== >>> UPDATE TIME OUT VALUE
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							if($min_log < $get_diff){
								$update_val = array("time_out"=>$current_date);
								$this->db->where($where_update);
								$update 	= $this->db->update("employee_time_in AS eti",$update_val);
							}
							
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							// update total hours and total hours required rest day
							$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
							
							// tardiness and undertime value
						
							$update_tardiness 	= $this->get_tardiness_val($emp_id,$comp_id,$r->time_in,$work_schedule_id,$r->date);  //zzz
							$update_undertime 	= $this->get_undertime_val($emp_id,$comp_id,$r->date,$current_date,$work_schedule_id,$number_of_breaks_per_day,$date);
						
							// check tardiness value
							$flag_tu 			= 0;
							$hours_worked 		= $this->get_hours_worked(date("Y-m-d",strtotime($r->date)), $emp_id,$work_schedule_id);
							
							$get_total_hours_worked = ($hours_worked / 2) + .5;
							
							//holiday now
							$holiday = $this->company_holiday($date, $comp_id);
							
							if($holiday){
								$get_diff 	= (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
								$arr 		= array(
												'emp_no' 				=>	$emp_no,
												'breaks' 				=> $number_of_breaks_per_day,
												'current_date' 			=> $current_date,
												'date' 					=>$date,
												'emp_id' 				=> $emp_id,
												'comp_id' 				=> $comp_id,
												'check_type' 			=> $check_type,
												'min_log' 				=> $min_log,
												'get_diff' 				=> $get_diff,
												'employee_time_in_id' 	=> $r->employee_time_in_id,
												'work_schedule_id' 		=> $work_schedule_id,
												'time_in' 				=> $r->time_in,
												'time_out' 				=> $r->time_out,
												'lunch_in' 				=> $r->lunch_in,
												'lunch_out' 			=> $r->lunch_out,
												'new_timein' 			=> $new_timein,
												'timein_id' 			=> $timein_id
											);
								return $this->holiday_time_in($arr);
							
							}
							// required hours worked only
							$new_total_hours 	= $this->get_tot_hours($emp_id,$comp_id,$r->time_in,$r->lunch_out,$r->lunch_in,$current_date,$hours_worked,$work_schedule_id,$number_of_breaks_per_day); //endx
							$update_timein_logs = array(
													"tardiness_min"				=> $update_tardiness,
													"undertime_min"				=> $update_undertime,
													"total_hours"				=> $hours_worked,
													"total_hours_required"		=> $get_total_hours,
													"flag_tardiness_undertime"	=> $flag_tu
												);
							
							//attendance settings
							$att = $this->calculate_attendance($comp_id,$r->time_in,$current_date);
							
							if($att){
								$total_hours_worked = $this->total_hours_worked($current_date, $r->time_in);
								$total_hours_worked = $this->convert_to_hours($total_hours_worked);
								if($r->time_in >= $r->lunch_out){
									$update_timein_logs['lunch_in'] 	= null;	
									$update_timein_logs['lunch_out'] 	= null;
								}
								elseif($current_date <= $r->lunch_in){
									$update_timein_logs['lunch_in'] 	= null;
									$update_timein_logs['lunch_out'] 	= null;
								}
								$update_timein_logs['total_hours_required'] = $total_hours_worked;
								$update_timein_logs['absent_min'] 			= ($hours_worked - $total_hours_worked) * 60;
								$update_timein_logs['late_min'] 			= 0;
								$update_timein_logs['tardiness_min'] 		= 0;
								$update_timein_logs['undertime_min'] 		= 0;
							}
							
							$this->db->where($where_update);
							$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
							
							return ($q2) ? $q2->row() : FALSE ;
							
						}
						else if ($check_type == "time in"){
							
							check_leave_appliction($date,$emp_id,$comp_id);
							
							/* CHECK TIME IN START */
							$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id,$r->time_in);
							
							if($wst != FALSE){
								// new start time
								$nwst = date("Y-m-d {$wst}");
								$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
							}
							
							$late_min 	= $this->late_min($comp_id, $date, $emp_id, $this->work_schedule_id);
							
							// insert time in log ================================================================ >>>> INSERT NEW TIME IN LOG SAME DATE
							$insert 	= FALSE;
							$get_diff 	= (strtotime($current_date) - strtotime($r->time_out)) / 60;
							
							if($min_log < $get_diff){
								$val = array(
									"emp_id"			=> $emp_id,
									"comp_id"			=> $comp_id,
									"date"				=> $date,
									"time_in"			=> $current_date,
									"work_schedule_id" 	=> $this->work_schedule_id,
									"source" 			=> $source,
									"late_min" 			=> $late_min,
									"location" 			=> $comp_add,
									"flag_on_leave" 	=> $ileave
								);
								$insert = $this->db->insert("employee_time_in",$val);	
							}
							
							if($insert){
								$w2 = array(
									"a.payroll_cloud_id"=> $emp_no,
									"eti.date"			=> $date,
									"eti.status" 		=> "Active"
								);
								$this->edb->where($w2);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$this->db->order_by("eti.time_in","DESC");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
				
								return ($q2) ? $q2->row() : FALSE ;
							}else{
								$this->edb->where($where_update);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
				
								return ($q2) ? $q2->row() : FALSE ;
							}
							
						}
					}
				}
			}
		}else{
			
			if($sync_date != ""){
				$current_date = $sync_date;
			}else{
			// check employee time in
				$current_date = $barack_date_trap_exact_t_date;
			}
			
			// CHECK WORK SCHEDULE
			/*
			$arrt = array(
					'time_in'				=> 'eti.time_in',
					'lunch_out' 			=> 'eti.lunch_out',
					'lunch_in' 				=> 'eti.lunch_in',
					'time_out' 				=> 'eti.time_out',
					'late' 					=> 'eti.late_min',
					'overbreak' 			=> 'eti.overbreak_min',
					'tardiness' 			=> 'eti.tardiness_min',
					'employee_time_in_id' 	=> 'eti.employee_time_in_id',
					'date' 					=> 'eti.date',
					'payroll_group_id' 		=> 'epi.payroll_group_id'
			);
			
			if($this->type == "split"){
				$arrt['schedule_blocks_time_in_id'] = 'eti.schedule_blocks_time_in_id';
			}
			$this->edb->select($arrt);
			
			if($sync_employee_time_in_id!=""){
				$w 	= array(
						"a.payroll_cloud_id"		=> $emp_no,
						"a.user_type_id"			=> "5",
						"eti.employee_time_in_id" 	=> $sync_employee_time_in_id,
						"eti.status" 				=> "Active",
						"eti.comp_id" 				=> $comp_id
					);
			}
			else{
				$w 	= array(
						"a.payroll_cloud_id"		=> $emp_no,
						"a.user_type_id"			=> "5",
						"eti.status" 				=> "Active",
						"eti.comp_id" 				=> $comp_id
					);
			}
			
			$this->edb->where($w);
			
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
			$this->db->order_by("eti.time_in","DESC");
			
			if($this->type == "split"){
				$q = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
			}
			else{
				$q = $this->edb->get("employee_time_in AS eti",1,0);
			}
			*/
			$r = $last_timein;
			
			if($q->num_rows() == 0){
				
				if($sync_date !=""){
					$time_in = date('H:i:s',strtotime($sync_date));
				}
				else{
					$time_in = date('H:i:s');				
				}
				
				if($this->type!='split'){
					if($check_type == "time in"){
						
						$timeIn = $this->only_for_timeIn_reg_sched($emp_id,$emp_no,$work_schedule_id,$comp_id,$current_date,$date,$time_in,$source,$comp_add,$ileave);
						return $timeIn;
					}
				}else{
					if($r){
						$timein_id = $r->employee_time_in_id;
					}
					// insert time in log
					if($new_employee_timein){
						$val = array(
								"emp_id"			=> $emp_id,
								"comp_id"			=> $comp_id,
								"date"				=> $date,
								"time_in"			=> $current_date,
								"work_schedule_id" 	=> $work_schedule_id,
								"source" 			=> $source,
								"location" 			=> $comp_add,
								"flag_on_leave" 	=> $ileave
						);
						$insert = $this->db->insert("employee_time_in",$val);
						$timein_id = $this->db->insert_id();
					}
						
					$val2 = array(
							"employee_time_in_id" 	=> $timein_id,
							"date"					=> $date,
							"time_in"				=> $current_date,
							"emp_id"				=> $emp_id,
							"comp_id"				=> $comp_id,
							"schedule_blocks_id" 	=> $this->schedule_blocks_id
					);
					$insert2 = $this->db->insert("schedule_blocks_time_in",$val2);
					
					if($insert2){
						$w2 = array(
								"a.payroll_cloud_id"=>$emp_no,
								"eti.date"=>$date
						);
						$this->edb->where($w2);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
					
						return ($q2) ? $q2->row() : FALSE ;
					}
				}
			}
			else{
				
				// get date time in to date time out
				$workday 			= date("l",strtotime($date));
				
				$payroll_group_id 	= $r->payroll_group_id;
				
				// check rest day
				//$check_rest_day 	= $this->check_rest_day($workday,$work_schedule_id,$comp_id);
				
				// this is improvized checking of rest day
				$check_rest_day		= $global_rest_day;
				
				//change code here - aldrin
				if($check_rest_day){
					// global where update data
					if($sync_employee_time_in_id!=""){
						$where_update 	= array(
											"eti.emp_id"				=> $emp_id,
											"eti.comp_id"				=> $comp_id,
											"eti.employee_time_in_id"	=> $sync_employee_time_in_id
										);
					}
					else{				
						$where_update 	= array(
											"eti.emp_id"				=> $emp_id,
											"eti.comp_id"				=> $comp_id,
											"eti.employee_time_in_id"	=> $r->employee_time_in_id
										);
					}
					
					if($check_type == "time out"){
						// not split shift
						if($this->type!='split'){
							// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE FOR REST DAY
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							if($min_log < $get_diff){
								$update_val = array("time_out"=>$current_date);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);
							}
							
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							
							// update total hours and total hours required rest day
							$get_total_hours 	= (strtotime($current_date) - strtotime($r->time_in)) / 3600;
							$update_timein_logs = array(
								"tardiness_min"			=> 0,
								"undertime_min"			=> 0,
								"total_hours"			=> $get_total_hours,
								"total_hours_required"	=> $get_total_hours
							);
							$this->db->where($where_update);
							$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
							
							return ($q2) ? $q2->row() : FALSE ;
							exit;
						}
						else{
							$where_update2 = array(
									"eti.emp_id"=>$emp_id,
									"eti.schedule_blocks_time_in_id"=>$r->schedule_blocks_time_in_id
							);
							
							// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE FOR REST DAY
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							
							if($min_log < $get_diff){
								$update_val = array("time_out"=>$current_date);
								$this->db->where($where_update2);
								$update = $this->db->update("schedule_blocks_time_in AS eti",$update_val);
							}
								
							$this->edb->where($where_update2);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("schedule_blocks_time_in AS eti",1,0);

							$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
							
							// get total hours in schedule blocks time in
							$final_total = $this->generate_total_hours($r->schedule_blocks_time_in_id, $get_total_hours, $r->employee_time_in_id);
							
							// update total hours and total hours required rest day
							$update_timein_logs = array(
									"tardiness_min"	=> 0,
									"undertime_min"	=> 0,
									"total_hours"	=> $final_total
							);
							$this->db->where($where_update);
							$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
								
							return ($q2) ? $q2->row() : FALSE ;
							exit;
						}
						
					}else if($check_type == "time in"){
						
						/* CHECK TIME IN START */
						$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id,$r->time_in);
						if($wst != FALSE){
							// new start time
							$nwst 					= date("Y-m-d {$wst}");
							$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
						}
						
						// insert time in value for rest day ============================================== >>> INSERT TIME IN VALUE FOR REST DAY
						$insert 	= FALSE;
						$get_diff 	= (strtotime($current_date) - strtotime($r->time_out)) / 60;

						// adding schedule block here
						if($this->type!='split'){
							
							if($min_log < $get_diff){
								$val 	= array(
											"emp_id"			=> $emp_id,
											"comp_id"			=> $comp_id,
											"date"				=> $date,
											"time_in"			=> $current_date,
											"work_schedule_id" 	=> -1,
											"source" 			=> $source,
											"location" 			=> $comp_add,
											"flag_on_leave" 	=> $ileave
										);
								$insert = $this->db->insert("employee_time_in",$val);	
							}
								
							if($insert){
								$w2 	= array(
											"a.payroll_cloud_id"	=> $emp_no,
											"eti.date"				=> $date,
											"eti.status" 			=> "Active"
										);
								$this->edb->where($w2);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$this->db->order_by("eti.time_in","DESC");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
				
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}else{
								$this->edb->where($where_update);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
				
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}
						}
						else{
							// split schedule here
							if($min_log < $get_diff){
								
								// indentify if time is split in the database
								$time_in = date('H:i:s');
								$split = $this->split_schedule_time($this->split_schedule_id, $time_in);
								 
								if(!$split){
									$val = array(
											"emp_id"			=> $emp_id,
											"comp_id"			=> $comp_id,
											"work_schedule_id" 	=> $this->work_schedule_id,
											"date"				=> $date
									);
									$insert = $this->db->insert("employee_time_in",$val);
									$timein_id = $this->db->insert_id();
								}else{
									$timein_id = $this->get_employee_timein_id($this->split_schedule_id, $current_date);
								}
								
								$val2 = array(
										"employee_time_in_id" => $timein_id,
										"date"=>$date,
										"time_in"=>$current_date,
										"emp_id"=>$emp_id,
										"comp_id"=>$comp_id,
										"split_schedule_id" => $this->split_schedule_id
								);
								$insert2 = $this->db->insert("schedule_blocks_time_in",$val2);
								
							}
								
							if($insert2){
								$w2 = array(
										"a.payroll_cloud_id"=>$emp_no,
										"sbti.date"=>$date
								);
								$this->edb->where($w2);
								$this->edb->select('sbti.time_in AS time_in,*');
								$this->edb->join("employee AS e","sbti.emp_id = e.emp_id","left");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","left");
								$this->db->order_by("sbti.time_in","DESC");
								$q2 = $this->edb->get("schedule_blocks_time_in AS sbti",1,0);
							
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}else{

								$this->edb->where($where_update);
								$this->edb->join("employee AS e","sbti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$q2 = $this->edb->get("schedule_blocks_time_in AS sbti",1,0);
							
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}
						}
					}
				}
				
				$flag_halfday = 0;
				
				/**
				 * SPLIT SCHEDULE MODIFICATIOIN HERE
				 * TAKE NOTE YOU aRe NOW ENTERING MY OWN PREMISES
				 * GOOD LUCK, HAVE FUN CODING
				 * 
				 * LOGS HERE IF SCHEDULE IS SPLIT, IF FIRST TIMEIN IN FIRST BLOCK IS ALREADY DONE THE REST OF PROCESS IS ALREADY HERE
				 */
				if($this->type == 'split'){
					$get_diff = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
					
					$arr = array(
						'emp_no' 				=> $emp_no,
						'current_date' 			=> $current_date, 
						'emp_id'				=> $emp_id, 
						'comp_id' 				=> $comp_id,
						'breaks' 				=> $number_of_breaks_per_day,
						'check_type' 			=> $check_type,
						'min_log' 				=> $min_log,
						'get_diff' 				=> $get_diff,
						'employee_time_in_id' 	=> $r->employee_time_in_id,
						'work_schedule_id' 		=> $work_schedule_id,
						'block_id' 				=> $r->schedule_blocks_time_in_id,
						'schedule_blocks_id' 	=> $this->schedule_blocks_id,
						'time_in' 				=> $r->time_in,	
						'time_out' 				=> $r->time_out,						
						'lunch_in' 				=> $r->lunch_in,
						'lunch_out' 			=> $r->lunch_out,
						'new_timein' 			=> $new_timein,
						'timein_id' 			=> $timein_id,
						'new_employee_timein' 	=> $new_employee_timein

					);			
					return $this->split_schedule_time_in($arr,$split_total_activate,"",$date,$source,$first_block_start_time);
				}
				
				
				// IF WHOLEDAY --- ==> lunch Out ===> lunch In ==> Time Out ===> IF CLOCKIN BEFORE THE NEXT SHIFT COME
				// check for double login
				
				$sc = array(
						'date'
					);
				$wc  = array(
						'date' 				=> $date,
						'emp_id' 			=> $emp_id, 
						'comp_id'			=> $comp_id, 
						'status'			=> 'Active'
				);
				$this->db->select($sc);
				$this->db->where($wc);
				$q = $this->db->get('employee_time_in');
				$rows = $q->num_rows();
				
				/// here trap if assumed
				$is_work = is_break_assumed($work_schedule_id);
				if($is_work && $check_type != "time in"){
					$check_type = "time out"; 
				}
				
				if(strtotime($current_date) <= strtotime($add_oneday_timein." -120 minutes") && $rows == 1){
					
					$hours_worked 		= $this->get_hours_worked($date, $emp_id, $work_schedule_id);
					// global where update data
					if($sync_employee_time_in_id !=""){
						$where_update 	= array(
										"eti.emp_id"				=> $emp_id,
										"eti.comp_id"				=> $comp_id,
										"eti.employee_time_in_id"	=> $sync_employee_time_in_id,
										"eti.status" 				=> "Active"
										);
					}
					else{
						$where_update 	= array(
										"eti.emp_id"				=> $emp_id,
										"eti.comp_id"				=> $comp_id,
										"eti.employee_time_in_id"	=> $r->employee_time_in_id,
										"eti.status"				=> "Active"
										);
					}
					
					if($check_type == "lunch out"){
						// update lunch out value ============ >>>> UPDATE LUNCH OUT VALUE
						if($this->type!="split"){
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							
							if($min_log < $get_diff){
								$update_val = array("lunch_out"=>$current_date);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);
							}
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
						
							return ($q2) ? $q2->row() : FALSE ;
						}
					}
					else if($check_type == "lunch in"){
						// update lunch in value =========== >>>> UPDATE LUNCH IN VALUE
						if($this->type!="split"){
							$get_diff 		= (strtotime($current_date) - strtotime($r->lunch_out)) / 60;
							$overbreak_min	= $this->overbreak_min($comp_id,$date,$emp_id,$work_schedule_id,$r->lunch_out);
							$late_min		= ($r) ? $r->late_min : 0;
							$tardiness_min	= $late_min + $overbreak_min;
							if($min_log < $get_diff){
								$update_val = array("lunch_in"=>$current_date,"overbreak_min" => $overbreak_min,"tardiness_min"=>$tardiness_min);
								$this->db->where($where_update);
								$update 	= $this->db->update("employee_time_in AS eti",$update_val);
							}
							
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
						}
					}
					else if($check_type == "time out"){
						
						// update time out value ======================== >>>> UPDATE TIME OUT VALUE
						$work_sched 					= get_workschedule_in_regular_sched($work_schedule_id,$date,$comp_id);
						$update 						= FALSE;
						$continue 						= false;
						$time_in_time 					= $r->time_in;
						$lunch_out_time_punch			= $r->lunch_out;
						$lunch_in_time_punch			= $r->lunch_in;
						$new_time_out_cur 				= $current_date;
						$new_time_out_cur_orig			= $current_date;
						$new_time_out_cur_orig_str		= strtotime($new_time_out_cur_orig);
						$tardiness_min					= $r->tardiness_min;
						$late_min						= $r->late_min;
						$overbreak_min					= $r->overbreak_min;
						$new_time_in_start_assumed		= $time_in_time;
						$payroll_sched_timein_orig_str 	= strtotime($payroll_sched_timein_orig);
						$time_in_time_str 				= strtotime($time_in_time);
						$payroll_sched_timein_str		= strtotime($payroll_sched_timein);
						$current_date_str				= strtotime($current_date);
						$total_hours_work_required		= 0;
						$undertime_break				= 0;
						$new_break						= $number_of_breaks_per_day;
						$new_undertime					= 0;
						$work_end_time					= "";
						$work_end_time_str				= 0;
						
						if($work_sched){
							//$work_sched 				= $work_sched->work_start_time;
							$total_hours_work_required 	= $work_sched->total_work_hours;
							$work_end_time 				= $date." ".$work_sched->work_end_time;
							$work_start_time 			= $date." ".$work_sched->work_start_time;
							$work_end_time_str			= strtotime($work_end_time);
							$work_start_time_str		= strtotime($work_start_time);
							if($work_end_time_str < $work_start_time_str){
								$work_end_time			= date("Y-m-d H:i:s",strtotime($work_end_time ."+ 1 day"));
							}
						}
						
						//*** SET A GLOBAL BOUNDARY FOR LUNCHOUT AND LUNCHIN ==> this work for regular schedule that capture break***/
						//**  get half of total hours worked required per schedule
						$total_hours_work_required_half 	=  $total_hours_work_required / 2;
						$total_hours_work_required_half 	=  ($total_hours_work_required_half >= 1) ? $total_hours_work_required_half : 0;
						$total_hours_work_required_half_min =  ($total_hours_work_required_half >= 1) ? ($total_hours_work_required_half*60) : 0;
						
						//** set assumed break without threshold for Init **/
						$lunch_out 		= date('Y-m-d H:i:s',strtotime($payroll_sched_timein_orig. " +{$total_hours_work_required_half_min} minutes"));
						$lunch_in		= date('Y-m-d H:i:s',strtotime($lunch_out. " +{$number_of_breaks_per_day} minutes"));
						$lunch_out_str 	= strtotime($lunch_out);
						$lunch_in_str 	= strtotime($lunch_in);
						$lunch_in_init 	= $lunch_in;
						
						if($is_work){
							$h 			= $is_work->assumed_breaks * 60;
							
							//** set assumed break without threshold for Init **/
							$lunch_out 		= date('Y-m-d H:i:s',strtotime($payroll_sched_timein_orig. " +{$h} minutes"));
							$lunch_in		= date('Y-m-d H:i:s',strtotime($lunch_out. " +{$number_of_breaks_per_day} minutes"));
							$lunch_out_str 	= strtotime($lunch_out);
							$lunch_in_str 	= strtotime($lunch_in);
							
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
									$lunch_out 			= date('Y-m-d H:i:s',strtotime($time_in_time. " +{$h} minutes"));
									$lunch_in			= date('Y-m-d H:i:s',strtotime($lunch_out. " +{$number_of_breaks_per_day} minutes"));
									
									$work_end_calculate = total_min_between($time_in_time, $payroll_sched_timein_orig);
									$work_end_time		=  date('Y-m-d H:i:s',strtotime($work_end_time. " +{$work_end_calculate} minutes"));
								}
							}
							
							//** if timeIn after the startTime And ThresHold but less than the (init) break **/
							if($threshold_min > 0){
								if(($lunch_out_str > $time_in_time_str) && ($payroll_sched_timein_str < $time_in_time_str)) {
									// LOut and LIn start Time plus ThresHold 
									$lunch_out 	= date('Y-m-d H:i:s',strtotime($time_in_time. " +{$h} minutes"));
									$lunch_in	= date('Y-m-d H:i:s',strtotime($lunch_out. " +{$number_of_breaks_per_day} minutes"));
									
									$work_end_time		=  date('Y-m-d H:i:s',strtotime($work_end_time. " +{$threshold_min} minutes"));
								}
							}
							
							//** if HALFDAY timeIn, thresHold dont effect anymore, Halfday time is set using the half of the totalHours (+) plus the startTime (as lunchOut assumed) plus break (as lunchIN assumed)**/
							//** timeIn between And after this assumed time will set that the employee is doing Halfday **/
							//** timeIn between
							if(($lunch_out_str <= $time_in_time_str) && ($lunch_in_str >= $time_in_time_str)) {
								// set the LOut and LIn to null and the timeiN start count on the assumed lunchIn since its assumed here to be break ==> amo ni amo gisabutan
								$new_time_in_start_assumed 	= $lunch_in;
								$lunch_out 					= null;
								$lunch_in 					= null;
							}
							//** timeIn after break
							if($lunch_in_str < $time_in_time_str) {
								// set the LOut and LIn to null and the timeiN
								$lunch_out 					= null;
								$lunch_in 					= null;
								$new_break 					= 0;
							}
							
							//*** if assumed breaks scenario regular schedule timeout ***//
							//**  init LOut & LIn
							if(($lunch_out != null) && ($lunch_in != null)){
								$lunch_out_new_str	= strtotime($lunch_out);
								$lunch_in_new_str	= strtotime($lunch_in);
							}else{
								$lunch_out_new_str	= $lunch_out_str;
								$lunch_in_new_str	= $lunch_in_str;
							}
							
							//** if timeout before break **//
							if($current_date_str < $lunch_out_new_str){
								$new_break 			= 0;
								$undertime_break	= $number_of_breaks_per_day;
								$new_time_out_cur	= $current_date;
								$lunch_out 			= null;
								$lunch_in 			= null;
							}
							//** if timeout between break **//
							if(($current_date_str >= $lunch_out_new_str) && ($current_date_str <= $lunch_in_new_str)){
								$new_break 			= 0;
								$new_time_out_cur	= $lunch_out;
								$undertime_break	= $number_of_breaks_per_day;
								$lunch_out 			= null;
								$lunch_in 			= null;
							}
							//** if timeout before the init lunchin this means he/she is doing halfday as we discus 2/17/17 5:00 PM **//
							if($new_time_out_cur_orig_str < $lunch_in_new_str){
								$new_break			= total_min_between($lunch_in_time_punch, $lunch_out_time_punch);
							}
							//** if timeout after break **//
							if($current_date_str > $lunch_in_new_str){
								$new_break 			= $number_of_breaks_per_day;
								//** timeIn after break
								if($lunch_in_str < $time_in_time_str) {
									$new_break 		= 0;
								}
								//** timeIn between break
								if($lunch_in_str >= $time_in_time_str && $lunch_out_str <= $time_in_time_str) {
									$new_break 		= 0;
								}
								$new_time_out_cur	= $current_date;
							}
							
							$total_hours_new 	= total_min_between($new_time_out_cur, $new_time_in_start_assumed);
							$total_hours_new_m	= $total_hours_new - $new_break;
							$total_hours_new_h	= $total_hours_new_m/60;
							
							$work_end_time_str	= strtotime($work_end_time);
							
							if($work_end_time_str > $current_date_str){
								$new_undertime	= total_min_between($work_end_time, $new_time_out_cur);
								$new_undertime	= $new_undertime - $undertime_break;
							}
							
							if($current_date <= $lunch_in){
								$continue 	= true;
							}
						}
						
						if($r->lunch_in){
							$get_diff = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
						}else{
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
						}			
						if($this->type!="split"){
							if($min_log < $get_diff || $continue){
								
								$update_val = array("time_out"=>$new_time_out_cur_orig);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);
							}
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							$r2 = $q2->row();
						}
						else{
							$get_diff = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
							if($min_log < $get_diff){
								$update_val = array("time_out"=>$current_date);
								$this->db->where($where_update);
								$update = $this->db->update("schedule_blocks_time_in AS eti",$update_val);
							}
								
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("schedule_blocks_time_in AS eti",1,0);
							$r2 = $q2->row();
						}
						
						$half_day 	= $this->if_half_day($r2->time_in, $work_schedule_id, $comp_id,$emp_no,$current_date,$r2->employee_time_in_id,$emp_id);
						
						//holiday now
						$holiday 	= $this->company_holiday($date, $comp_id);
						
						if($holiday){
							$get_diff 	= (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
							$arr 		= array(
										'emp_no' 				=> $emp_no,
										'current_date' 			=> $current_date,
										'break' 				=> $number_of_breaks_per_day,
										'date' 					=> $date,
										'emp_id' 				=> $emp_id,
										'comp_id' 				=> $comp_id,
										'check_type' 			=> $check_type,
										'min_log' 				=> $min_log,
										'get_diff' 				=> $get_diff,
										'employee_time_in_id' 	=> $r->employee_time_in_id,
										'work_schedule_id' 		=> $work_schedule_id,
										'time_in' 				=> $r->time_in,
										'time_out' 				=> $r->time_out,
										'lunch_in' 				=> $r->lunch_in,
										'lunch_out' 			=> $r->lunch_out,
										#'new_timein' 			=> $new_timein,
										#'timein_id' 			=> $timein_id
										);
						
							return $this->holiday_time_in($arr);
						}
						if($update){
							// update flag tardiness and undertime
							$flag_tu = 0;
							
							// check no. of timein row
							if($this->type != 'split'){
								$check_timein_row = $this->check_timein_row($emp_id, $comp_id, $current_date);
							}
							else{
								$check_timein_row = $this->split_check_timein_row($emp_id, $comp_id, $current_date);  // xx
							}
							
							if($check_timein_row){
								// update tardiness
								$update_tardiness 	= 0;
								
								// update undertime
								$update_undertime 	= 0;
								
								// update total hours
								$update_total_hours = 0;
							}
							else{								
								// update tardiness
								$update_tardiness 	= $this->get_tardiness_import($emp_id, $comp_id, $r2->time_in, $r2->lunch_out, $r2->lunch_in,$work_schedule_id,$number_of_breaks_per_day,$date,$half_day); //xxx
		
								// update undertime
								$update_undertime 	= $this->get_undertime_import($emp_id, $comp_id, $r2->time_in, $r2->time_out, $r2->lunch_out, $r2->lunch_in,$work_schedule_id,$number_of_breaks_per_day,$date);
							
								// update total hours 
								$hours_worked 		= $this->get_hours_worked($date, $emp_id, $work_schedule_id);
								$update_total_hours = $this->get_tot_hours_complete_logs($emp_id, $comp_id, $r2->time_in, $r2->lunch_out, $r2->lunch_in, $r2->time_out, $hours_worked,$work_schedule_id,$number_of_breaks_per_day,$date);
								
								// check tardiness value
								if(($r2->lunch_out == null && $r2->lunch_in == null) && !is_break_assumed($work_schedule_id)){
        							$update_tardiness = 0;
        						}
							}
							
							// update total hours required
							// $update_total_hours_required = $this->get_tot_hours_limit($emp_id, $comp_id, $r2->time_in, $r2->lunch_out, $r2->lunch_in, $r2->time_out,$work_schedule_id,$number_of_breaks_per_day);
							
							//*** TO CALCULATE FOR UNDERTIME ***//
							//** FIND WORK END if timeIn between the startTime And the thresHold **/
							if($threshold_min > 0){
								if(($payroll_sched_timein_orig_str <= $time_in_time_str) && ($payroll_sched_timein_str >= $time_in_time_str)) {
									// update workend start										
									$work_end_calculate = total_min_between($time_in_time, $payroll_sched_timein_orig);
									$work_end_time		=  date('Y-m-d H:i:s',strtotime($work_end_time. " +{$work_end_calculate} minutes"));
								}
							}
							
							//** FIND WORK END  if timeIn after the startTime And ThresHold but less than the (init) break **/
							if($threshold_min > 0){
								if(($lunch_out_str > $time_in_time_str) && ($payroll_sched_timein_str < $time_in_time_str)) {
									// update workend start
									$work_end_time		=  date('Y-m-d H:i:s',strtotime($work_end_time. " +{$threshold_min} minutes"));
									
								}
							}
							//** UNDER TIME **//
							$work_end_time_str 	= strtotime($work_end_time);
							
							if($work_end_time_str > $current_date_str){
								// if early clock
								if($lunch_in_time_punch == null || $lunch_out_time_punch == null){
									
									// time out during assumed break
									if($current_date_str < $lunch_in_str && $current_date_str >= $lunch_out_str){
										$new_undertime	= total_min_between($work_end_time, $lunch_in_init);
									}
									else if($current_date_str <= $lunch_out_str){
										$new_undertime	= total_min_between($work_end_time, $new_time_out_cur);
										$new_undertime	= $new_undertime - $number_of_breaks_per_day;
									}
									else{
										$new_undertime	= total_min_between($work_end_time, $new_time_out_cur);
									}
								}else{
									$new_undertime	= total_min_between($work_end_time, $new_time_out_cur);
									$new_undertime	= $new_undertime - $undertime_break;
								}
								
							}
							
							//** if timeout before the init lunchin this means he/she is doing halfday as we discus 2/17/17 5:00 PM **//
							if($new_time_out_cur_orig_str < $lunch_in_str){
								$new_break			= total_min_between($lunch_in_time_punch, $lunch_out_time_punch);
							}
							//** UPDATE TOTAL HOURS WORK**//
							$update_total_hours_required = total_min_between($new_time_out_cur, $time_in_time);
							$update_total_hours_required = $update_total_hours_required - ($new_break + $overbreak_min);
							
							//** TO HOURS **/
							$update_total_hours_required = $update_total_hours_required/60;
							
							// update employee time in logs
							$update_timein_logs = array(
												"undertime_min"				=> $new_undertime,
												"tardiness_min" 			=> $tardiness_min,
												"total_hours"				=> $hours_worked,
												"total_hours_required"		=> $update_total_hours_required,
												"flag_tardiness_undertime"	=> $flag_tu
												);
							
							//**** IF ASSUME BREAK OVERWIRTE EVERYTHING****//
							if($is_work){
								$update_timein_logs['lunch_in'] 			= $lunch_in;
								$update_timein_logs['lunch_out'] 			= $lunch_out;
								$update_timein_logs["total_hours_required"] = $total_hours_new_h;
								$update_timein_logs['absent_min'] 			= 0;
								$update_timein_logs['tardiness_min'] 		= $tardiness_min;
								$update_timein_logs['undertime_min'] 		= $new_undertime;
							}
							
							// ***** NEW COMPUTATION FOR ATTENDANCE SETTINGS
							$att = is_attendance_active($comp_id);
								
							if($att){
								if($update_total_hours_required <= $att){
									
									if($r2->time_in >= $r2->lunch_out){
										$update_timein_logs['lunch_out'] 	= null;
										$update_timein_logs['lunch_in'] 	= null;
									}
									elseif($current_date <= $r2->lunch_in){
										$update_timein_logs['lunch_out'] 	= null;
										$update_timein_logs['lunch_in'] 	= null;
									}
										
									$half_day_h = ($hours_worked / 2) * 60;
									if($late_min < $half_day_h){
										$update_timein_logs['late_min'] 		= $tardiness_min;
										$update_timein_logs['tardiness_min'] 	= $tardiness_min;
										$update_timein_logs['undertime_min'] 	= 0;
										$update_timein_logs['absent_min'] 		= (($hours_worked - $update_total_hours_required) * 60) - $tardiness_min;
									}
									else{
										$update_timein_logs['late_min'] 		= 0;
										$update_timein_logs['tardiness_min'] 	= 0;
										$update_timein_logs['undertime_min'] 	= $new_undertime;
										$update_timein_logs['absent_min'] 		= (($hours_worked - $update_total_hours_required) * 60) - $new_undertime;
									}
									$update_timein_logs['total_hours_required'] 	= $update_total_hours_required;
								}
							}
							
							//**** UPDATE HERE THEN END ***/
							
							$this->db->where($where_update);
							$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
						}
						
						return ($q2) ? $q2->row() : FALSE ;
						
					}else if($check_type == "time in"){
						$get_diff 	= (strtotime($current_date) - strtotime($r->time_out)) / 60;
						if($min_log < $get_diff){
							
							// $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
							// $get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
							
							$date_insert = array(
									"comp_id"					=> $comp_id,
									"emp_id"					=> $emp_id,
									"date"						=> $date,
									"time_in"					=> $current_date,
									"undertime_min"				=> 0,
									"tardiness_min" 			=> 0,
									"late_min" 					=> 0,
									"overbreak_min" 			=> 0,
									"work_schedule_id" 			=> "-2",
									"source" 					=> $source,
									"location" 					=> $comp_add,
									"flag_regular_or_excess" 	=> "excess",
							);
							
							$add_logs = $this->db->insert('employee_time_in', $date_insert);
							
							if($add_logs){
								$w2 	= array(
										"a.payroll_cloud_id"	=> $emp_no,
										"eti.date"				=> $date,
										"eti.status" 			=> "Active"
								);
								$this->edb->where($w2);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$this->db->order_by("eti.time_in","DESC");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}else{
								$this->edb->where($where_update);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}
						}
					}
				}
				else if($check_type == "time in" && $rows == 0){
					
					$timeIn = $this->only_for_timeIn_reg_sched($emp_id,$emp_no,$work_schedule_id,$comp_id,$current_date,$date,$time_in,$source,$comp_add,$ileave);
					return $timeIn;
				}
				//*** for new timeIn under the same shift (double timeIn) OR clockIn after the prev shift (miss timeOut)***//
				else{
					$where_update = array(
							"eti.emp_id"				=> $emp_id,
							"eti.comp_id"				=> $comp_id,
							"eti.employee_time_in_id"	=> $r->employee_time_in_id,
							"eti.status"				=> "Active"
					);
					if($check_type == "time out" || $check_type == 'lunch out' || $check_type == "lunch in"){
						
						$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
						if($min_log < $get_diff){
							$total_h_r	= (total_min_between($current_date,$r->time_in) / 60);
							$update_val = array("time_out"=>$current_date,"total_hours_required"=>$total_h_r,"total_hours"=>$total_h_r);
							$this->db->where($where_update);
							$update = $this->db->update("employee_time_in AS eti",$update_val);
						}
					
						$this->edb->where($where_update);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
					
						return ($q2) ? $q2->row() : FALSE ;
						exit;
					
					}else if($check_type == "time in"){
						$get_diff 	= (strtotime($current_date) - strtotime($r->time_out)) / 60;
						if($min_log < $get_diff){
							
							// $get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
							// $get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
							
							$date_insert = array(
									"comp_id"					=> $comp_id,
									"emp_id"					=> $emp_id,
									"date"						=> $date,
									"time_in"					=> $current_date,
									"undertime_min"				=> 0,
									"tardiness_min" 			=> 0,
									"late_min" 					=> 0,
									"overbreak_min" 			=> 0,
									"work_schedule_id" 			=> "-2",
									"source" 					=> $source,
									"location" 					=> $comp_add,
									"flag_regular_or_excess" 	=> "excess",
							);
							
							$add_logs = $this->db->insert('employee_time_in', $date_insert);
							if($insert){
								$w2 	= array(
										"a.payroll_cloud_id"	=> $emp_no,
										"eti.date"				=> $date,
										"eti.status" 			=> "Active"
								);
								$this->edb->where($w2);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$this->db->order_by("eti.time_in","DESC");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
									
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}else{	
								$this->edb->where($where_update);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}
						}
						else{
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
						
							return ($q2) ? $q2->row() : FALSE ;
							exit;
						}
					}
				}
			}
		}
	}
		
	public function new_get_splitinfo($emp_no,$comp_id,$work_schedule_id,$emp_id="",$gDate="",$time_in=""){
		
		//*** BARACK && KEITH NEW LOGIC GET SPLIT BLOCK SCHEDUL DEPENDING CURRENT TIME ***//
		//** First (current datetime) specify which block belong
		//** if block is specify, check if (cdt) [timein or timeOut] [lunchOut or lunchIn],
		//** if (cdt) is not belong to block find the nearest block,
		//** if block is specify, check if (cdt) timein or timeOut,
		
		$check_emp_no 			= $this->login_screen_model->new_check_emp_info($emp_no,$comp_id);
		$emp_id					= ($check_emp_no) ? $check_emp_no->emp_id : $emp_id;
		$current_date			= ($gDate) ? $gDate : date("Y-m-d");
		$current_date_str		= strtotime($current_date);
		$prev_date				= ($gDate) ? $gDate : date("Y-m-d");
		$current_time			= ($time_in) ? date("H:i:s", strtotime($time_in)) : date("H:i:s");
		$current_datetime		= ($time_in) ? $time_in : date("Y-m-d H:i:s");
		$current_datetime_str	= strtotime($current_datetime);
		$get_sched				= false;
		$schedule_blocks_id		= "";
		$break					= 0;
		$total_hour_block_sched	= 0;
		$arrx 					= array(
								'work_schedule_id',
								'employee_time_in_id',
								"date"
								);
		
		$this->db->select($arrx);
		$w 		= array(
				"emp_id"	=> $emp_id,
				"status" 	=> "Active",
				"comp_id" 	=> $comp_id
				);
		$this->db->where($w);
		$this->db->order_by("date","DESC");
		$q = $this->db->get("employee_time_in AS eti",1,0);
		$r = $q->row();
		
		if($r){
			// if wala pa sa db na save ang current date
			$prev_date 		= $r->date;
			$prev_date_str 	= strtotime($prev_date);
		}
		if($prev_date_str >= $current_date_str){
			$prev_date 		= $current_date;
			$prev_date_str 	= strtotime($prev_date);
		}
		if($prev_date_str == $current_date_str){
			// if naa na sa db ang current date
			$prev_date	= date('Y-m-d', strtotime($prev_date." -1 day"));
		}
		
		//*** DISPLAY ALL SPLIT BLOCK SCHEDULE ***//
		$arrSelect 	= array(
					"emp_schedule_block_id",
					"es.shifts_schedule_id",
					"sb.schedule_blocks_id",
					"em.emp_id",
					"valid_from",
					"until",
					"payroll_group_id",
					"status",
					"block_name",
					"start_time",
					"end_time",
					"break_in_min",
					"total_hours_work_per_block",
					"location_and_offices_id"
					);
			
		$w_date = array(
				"es.valid_from <="		=>	$current_date,
				"es.until >="			=>	$current_date
				);
		
		$this->db->select($arrSelect);
		$this->db->where($w_date);
		
		
		$w_ws 	= array(
				"em.work_schedule_id"	=> $work_schedule_id,
				"em.company_id"			=> $comp_id,
				"em.emp_id" 			=> $emp_id
				);
		
		$this->db->where($w_ws);
		$this->db->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
		$this->db->join("schedule_blocks AS sb","sb.schedule_blocks_id = em.schedule_blocks_id","LEFT");
		$q_ws = $this->db->get("employee_sched_block AS em");
		$r_ws = $q_ws->result();
		
		if($r_ws){
			//*** THIS IS TO CALCULATE FOR THE BOUNDARY OF THE SPLIT SCHED --> CREATE NEW LOGS
			$first 	= reset($r_ws);
			$last 	= end($r_ws);
		
			$first_block_start_time 	= $first->start_time;
			$first_block_start_time_str = strtotime($first->start_time);
			$last_block_end_time		= $last->end_time;
			$last_block_id				= $last->schedule_blocks_id;
			$last_block_end_time_str	= strtotime($last->end_time);
			
			$first_block_start_datetime		= date('Y-m-d H:i:s', strtotime($prev_date." ".$first_block_start_time." +1 day"));
			if($first_block_start_time_str > $last_block_end_time_str){
				$last_block_end_datetime	= date('Y-m-d H:i:s', strtotime($prev_date." ".$last_block_end_time." +1 day"));
			}
			else{
				$last_block_end_datetime	= date('Y-m-d H:i:s', strtotime($prev_date." ".$last_block_end_time));
			}
			
			//*** get the difference between the first start time and the last end time
			$total      = strtotime($first_block_start_datetime) - strtotime($last_block_end_datetime);
			$hours      = floor($total / 60 / 60);
			$minutes    = floor(($total - ($hours * 60 * 60)) / 60);
			$ret 		= ($hours * 60) + $minutes;
			$total_gap  = ($ret < 0) ? '0' : $ret;
			$total_gap	= $total_gap/2;
			if($total_gap > 120){
				$total_gap = 120;
			}
			$first_block_boundary_datetime 		= date('Y-m-d H:i:s',strtotime($first_block_start_datetime . "-".$total_gap." minutes"));
			$first_block_boundary_datetime_str 	= strtotime($first_block_boundary_datetime);
			
			if($first_block_boundary_datetime_str > $current_datetime_str){
				$current_date = $prev_date;
			}
			
			$current_date_str	= strtotime($current_date);
			$prev_end_time		= $last_block_end_datetime;
			
			$time_is_check 		= $this->check_if_date_is_time_in($emp_id,$current_date,$comp_id);
			$eti				= ($time_is_check) ? $time_is_check->employee_time_in_id : "";
			$schedule_blocks_id_prev	= "";
			$break_prev 				= "";
			$eti_prev					= "";
			$clock_type_prev			= "";
			$start_time_prev			= "";
			$end_time_prev				= "";
			$total_h_p_block_prev		= "";
			$block_name_prev			= "";
			$last_block_prev			= "";
			
			// get total hours in split blocks
			foreach ($r_ws AS $rws){
				$total_hour_block_sched = $total_hour_block_sched + $rws->total_hours_work_per_block;
			}
			
			foreach ($r_ws AS $rws){
				
				$prev_end_time_str	= strtotime($prev_end_time);
				$start_time 		= $rws->start_time;
				$end_time 			= $rws->end_time;
				$start_time_str 	= strtotime($start_time);
				$end_time_str 		= strtotime($end_time);
				
				if($first_block_start_time_str > $start_time_str){
					$current_date	= date('Y-m-d', strtotime($current_date." +1 day"));
				}
				
				$start_datetime	= date('Y-m-d H:i:s', strtotime($current_date." ".$start_time));
				
				if($start_time_str > $end_time_str){
					$end_datetime	= date('Y-m-d H:i:s', strtotime($current_date." ".$end_time." +1 day"));
				}else{
					$end_datetime	= date('Y-m-d H:i:s', strtotime($current_date." ".$end_time));
				}
				
				$start_datetime_str = strtotime($start_datetime);
				$end_datetime_str 	= strtotime($end_datetime);
				
				if(($current_datetime_str >= $start_datetime_str) && ($current_datetime_str <= $end_datetime_str)){
					
					$schedule_blocks_id = $rws->schedule_blocks_id;
					$break 				= $rws->break_in_min;
					$clock_type			= $this->check_clock_type($time_is_check,$emp_id,$comp_id,$eti,$schedule_blocks_id,$current_date,$break);
					$start_time			= $rws->start_time;
					$end_time			= $rws->end_time;
					$total_h_p_block	= $rws->total_hours_work_per_block;
					$block_name 		= $rws->block_name;
					$last_block			= $last_block_id;
					break;
				}
				
				else if(($current_datetime_str >= $prev_end_time_str) && ($current_datetime_str <= $start_datetime_str)){
					
					//
					$tx      	= strtotime($start_datetime) - strtotime($prev_end_time);
					$hx      	= floor($tx / 60 / 60);
					$mx		    = floor(($tx - ($hx * 60 * 60)) / 60);
					$retx 		= ($hx * 60) + $mx;
					$tgx  		= ($retx < 0) ? '0' : $retx;
					$tgx		= $tgx/2;
					if($tgx > 120){
						$tgx = 120;
					}
					$between_block_boundary		= date('Y-m-d H:i:s',strtotime($start_datetime. "-".$tgx." minutes"));
					$between_block_boundary_str = strtotime($between_block_boundary);
					
					if($between_block_boundary_str <= $current_datetime_str){
						$schedule_blocks_id 	= $rws->schedule_blocks_id;
						$break 							= $rws->break_in_min;
						$clock_type				= $this->check_clock_type($time_is_check,$emp_id,$comp_id,$eti,$schedule_blocks_id,$current_date,$break);
						$start_time				= $rws->start_time;
						$end_time				= $rws->end_time;
						$total_h_p_block		= $rws->total_hours_work_per_block;
						$block_name 			= $rws->block_name;
						$last_block				= $last_block_id;
					}
					else{
						$schedule_blocks_id 	= $schedule_blocks_id_prev;
						$break 					= $break_prev;
						$eti					= $eti_prev;
						$clock_type				= $this->check_clock_type($time_is_check,$emp_id,$comp_id,$eti_prev,$schedule_blocks_id_prev,$current_date,$break_prev);
						$start_time				= $start_time_prev;
						$end_time				= $end_time_prev;
						$total_h_p_block		= $total_h_p_block_prev;
						$block_name 			= $block_name_prev;
						$last_block				= $last_block_prev;
					}
					break;
				}
				else if(($current_datetime_str < $first_block_boundary_datetime_str) && ($last_block_id == $rws->schedule_blocks_id)){
					
					$schedule_blocks_id = $last_block_id;
					$break 				= $rws->break_in_min;
					$clock_type			= $this->check_clock_type($time_is_check,$emp_id,$comp_id,$eti,$schedule_blocks_id,$current_date,$break);
					$start_time			= $rws->start_time;
					$end_time			= $rws->end_time;
					$total_h_p_block	= $rws->total_hours_work_per_block;
					$block_name 		= $rws->block_name;
					$last_block			= $last_block_id;
					break;
				}
				
				$schedule_blocks_id_prev	= $rws->schedule_blocks_id;
				$break_prev 				= $rws->break_in_min;
				$eti_prev					= $eti;
				$start_time_prev			= $rws->start_time;
				$end_time_prev				= $rws->end_time;
				$total_h_p_block_prev		= $rws->total_hours_work_per_block;
				$block_name_prev 			= $rws->block_name;
				$last_block_prev			= $last_block_id;
				$prev_end_time				= $end_datetime;
				
			}
		}
		
		$return = array(
				'break_in_min' 					=> $break,
				'start_time' 					=> $start_time,
				'end_time' 						=> $end_time,
				'total_hours_work_per_block' 	=> $total_h_p_block,
				'block_name' 					=> $block_name,
				'schedule_blocks_id' 			=> $schedule_blocks_id,
				'last_block' 					=> $last_block,
				'clock_type' 					=> $clock_type,
				'first_block_start_time' 		=> $first_block_start_time,
				'total_hour_block_sched' 		=> $total_hour_block_sched,
				);
		return $return;
	}
	
	public function check_clock_type($time_is_check,$emp_id,$comp_id,$eti,$schedule_blocks_id,$current_date,$break){
		if($time_is_check){
			$sched_block = $this->check_if_date_is_sched_block($emp_id,$eti,$schedule_blocks_id,$comp_id,$current_date);
			if($sched_block){
				$time_in_val 	= $sched_block->time_in;
				$lunch_out_val 	= $sched_block->lunch_out;
				$lunch_in_val 	= $sched_block->lunch_in;
				$time_out_val 	= $sched_block->time_out;
				if($break > 0){
					if(!$lunch_out_val){
						$clock_type		= "lunch out";
					}
					else if(!$lunch_in_val){
						$clock_type		= "lunch in";
					}
					else if(!$time_out_val){
						$clock_type		= "time out";
					}
					else{
						$clock_type = "";
					}
				}else{
					$clock_type		= "time out";
				}
			}
			else{
				$clock_type		= "time in";
			}
		}else{
			$clock_type		= "time in";
		}
		return $clock_type;
	}
	
	public function check_if_date_is_time_in($emp_id,$date,$comp_id){
		$arrx 	= array(
				'time_in',
				'time_out',
				'date',
				'employee_time_in_id'
		);
		$this->db->select($arrx);
		$w 		= array(
				"emp_id"	=> $emp_id,
				"status" 	=> "Active",
				"date" 		=> $date,
				"comp_id" 	=> $comp_id
		);
		$this->db->where($w);
		$this->db->order_by("date","DESC");
		$q = $this->db->get("employee_time_in AS eti",1,0);
		$r = $q->row();
		return ($r) ? $r : false;
	}
	
	public function check_if_date_is_sched_block($emp_id,$eti,$sbi,$comp_id,$date){
		$arrx 	= array(
				'time_in',
				'lunch_out',
				'lunch_in',
				'time_out'
		);
	
		$this->db->select($arrx);
		$w 		= array(
				"emp_id"				=> $emp_id,
				"status" 				=> "Active",
				"schedule_blocks_id" 	=> $sbi,
				"employee_time_in_id" 	=> $eti,
				"date" 					=> $date,
				"comp_id" 				=> $comp_id
		);
		$this->db->where($w);
		$q = $this->db->get("schedule_blocks_time_in");
		$r = $q->row();
		return ($r) ? $r : false;
	}
	
	/**
	 * use in approving leave by using tardiness or undertime
	 * @param unknown $emp_id
	 * @param unknown $comp_id
	 * @param unknown $work_schedule_id
	 * @param unknown $currentdate
	 * pages
	 *   - absent page
	 *   - leave_change_time
	 */
	public function new_split_info_helper($emp_id,$comp_id,$work_schedule_id,$currentdate,$currenttime){

		$arrx = array(
				'work_schedule_id' 		=> 'eti.work_schedule_id',
				'employee_time_in_id'	=> 'eti.employee_time_in_id',
				"date" 					=> 'eti.date'
		);
		$this->edb->select($arrx);
		$w = array(
				"eti.emp_id"			=> $emp_id,
				"eti.status" 			=> "Active",
				"eti.comp_id" 			=> $comp_id,
				"eti.date" 				=> $currentdate
		);
		$this->edb->where($w);					
		$q = $this->edb->get("employee_time_in AS eti",1,0);
		$r = $q->row();
		
		$time_out = "";
		$split_sched_id = 0;
		
			
		$row_list = array();
		
		$w_date = array(
				"es.valid_from <="		=>	$currentdate,
				"es.until >="			=>	$currentdate
		);
		$this->db->where($w_date);
			
			
		$w_ws = array(
				"em.work_schedule_id"	=> $work_schedule_id,
				"em.company_id"			=> $comp_id,
				"em.emp_id" 			=> $emp_id
		);
		$this->db->where($w_ws);
		$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
		$q_ws = $this->edb->get("employee_sched_block AS em");
		$r_ws = $q_ws->result();
		
		if($r_ws){
			$first = reset($r_ws);
			$last = end($r_ws);
		
			$first_time = $this->get_starttime($first->schedule_blocks_id,$currentdate,$first);
			//$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
			$last_time = $this->get_endtime($last->schedule_blocks_id,$currentdate,$last);
		
			foreach($r_ws as $row){
				$rowx = $this->get_blocks_list($row->schedule_blocks_id);
				$start_time = date('Y-m-d H:i:s',strtotime($currentdate." ".$rowx->start_time));
				$end_time = date('Y-m-d H:i:s', strtotime($currentdate." ".$rowx->end_time));
				$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
					
				if($start_time >= $end_time){
					$end_time = date('Y-m-d H:i:s', strtotime($currentdate." ".$rowx->end_time." +1 day"));
					$row_list['break_in_min'] = $rowx->break_in_min;
					$row_list['start_time'] = $start_time;
					$row_list['end_time'] = $end_time;
					$row_list['total_hours_work_per_block'] = $rowx->total_hours_work_per_block;
					$row_list['block_name'] = $rowx->block_name;
					$row_list['schedule_blocks_id'] = $rowx->schedule_blocks_id;
					$row_list['last_block'] = $last->schedule_blocks_id;
		
					if($currenttime <= $start_time || ($currenttime >= $start_time && $currenttime <= $end_time)){
						return $row_list;
					}
				}else{
					
					if($currenttime >= $mid_night){
						$currentdate_next = date('Y-m-d',strtotime($mid_night));
						$start_time = date('Y-m-d H:i:s',strtotime($currentdate_next." ".$rowx->start_time));
						$end_time = date('Y-m-d H:i:s', strtotime($currentdate_next." ".$rowx->end_time));
					}
		
					$row_list['break_in_min'] = $rowx->break_in_min;
					$row_list['start_time'] = $start_time;
					$row_list['end_time'] = $end_time;
					$row_list['total_hours_work_per_block'] = $rowx->total_hours_work_per_block;
					$row_list['block_name'] = $rowx->block_name;
					$row_list['schedule_blocks_id'] = $rowx->schedule_blocks_id;
					$row_list['last_block'] = $last->schedule_blocks_id;
					
					
					if($currenttime <= $start_time || ($currenttime >=$start_time && $currenttime<=$end_time)){
						return $row_list;
					}
				}
			}
		}
		
	}
	
	/**
	 * use in approving leave by tardiness and undertime
	 * @param unknown $id
	 * @param unknown $overbreak
	 * @param unknown $tard
	 * @param unknown $hours
	 * @param unknown $change_time
	 * @param string $change
	 */
	public function update_employee_time_in($id,$overbreak,$tard,$hours,$change_time,$change = false){
		
		$where_update = array(
				"eti.employee_time_in_id"=>$id
		);
		
		if($change){
			$update_val = array(
					"tardiness_min" => $tard,
					"total_hours_required" => $hours,
					"time_in" => $change_time
			);
		}else{
			$update_val = array(
					"overbreak"=> $overbreak,
					"tardiness_min" => $tard,
					"total_hours_required" => $hours,
					"lunch_in" => $change_time
			);
		}
		$this->db->where($where_update);
		$update = $this->db->update("employee_time_in AS eti",$update_val);
	}
	
	public function update_employee_time_in_split($id,$overbreak,$tard,$hours,$change_time,$change = false,$employee_time_in_id,$work_schedule_id,$time_out){
	
		$where_update = array(
				"eti.schedule_blocks_time_in_id"=>$id
		);
	
		if($change){
			$update_val = array(
					"tardiness_min" => $tard,
					"total_hours_required" => $hours,
					"time_in" => $change_time
			);
		}else{
			$update_val = array(
					//"overbreak"=> $overbreak,
					"tardiness_min" => $tard,
					"total_hours_required" => $hours,
					"lunch_in" => $change_time
			);
		}
		$this->db->where($where_update);
		$update = $this->db->update("schedule_blocks_time_in AS eti",$update_val);
		
		
		
		$this->insert_into_employee_time_in($employee_time_in_id, $time_out,$work_schedule_id);
		
	}
		
	/** added: fritz - start **/
		
	public function get_attendance_total_work_hours($emp_id,$check_company_id,$date,$work_schedule_id = "",$activate = false,$break = false){
		$where = array(
				'e.company_id'=>$check_company_id,
				'e.emp_id' => $emp_id
		);
		$this->db->where($where);
		$arr2 = array(
				'payroll_cloud_id' => 'a.payroll_cloud_id'
		);
		$this->edb->select($arr2);
		$this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
		$q = $this->edb->get('employee AS e');
		$result = $q->row();
		$data = "";
		$data2 = "";
		if($result){
			$emp_no = $result->payroll_cloud_id;
	
			$payroll_group_id = $this->emp_login->payroll_group_id($emp_no,$check_company_id);
	
			$day = date('l',strtotime($date));
			$w_uwd = array(
					//"payroll_group_id"=>$payroll_group,
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$check_company_id,
					"days_of_work" => $day,
					"status" => 'Active'
			);
			$this->edb->where($w_uwd);
			$arr4 = array(
					'work_schedule_name' => 'work_schedule_name',
					'work_end_time' => 'work_end_time',
					'work_start_time' => 'work_start_time',
					'break_in_min' => 'break_in_min',
					'total_work_hours' => 'total_work_hours'
			);
			$this->edb->select($arr4);
			$q_uwd = $this->edb->get("regular_schedule");
			$r_uwd = $q_uwd->row();
			if($q_uwd->num_rows() > 0){
				$data = $r_uwd->total_work_hours;
			}else{// FLEXIBLE HOURS
				$fw = array(
						"f.company_id"=>$check_company_id,
						"f.work_schedule_id"=>$work_schedule_id,
						//"f.status" => 'Active'
				);
				$this->db->where($fw);
				$arr3 = array(
						'latest_time_in_allowed' => 'f.latest_time_in_allowed',
						'name' => 'ws.name',
						'duration_of_lunch_break_per_day' => 'duration_of_lunch_break_per_day',
						'total_hours_for_the_day' => 'total_hours_for_the_day'
				);
				//$this->edb->select($arr3);
				$this->edb->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
				$fq = $this->edb->get("flexible_hours AS f");
				$r_fh = $fq->row();
					
				if($fq->num_rows() > 0){
					$data 	= $r_fh->total_hours_for_the_day;
					if($break){
						$data 	= number_format(($r_fh->total_hours_for_the_day - ($r_fh->duration_of_lunch_break_per_day / 60)),2);
					}
				}
			}
				
			return $data;
		}
	}
	
	/***
	 * check if capture schedule in shift
	 */
	public function enable_capture($workschedule_id){
		$arr4 = array(
				'work_schedule_id' =>$workschedule_id,
				'break_rules' => 'capture'
		);
		$this->edb->where($arr4);
		$q_uwd = $this->edb->get("work_schedule");
	
		if($q_uwd->num_rows() > 0){
			return true;
		}
		return false;
	}
	
	//*** This is for global use for future.. and optimization
	
	public function global_timein_employee_checker($comp_id="",$emp_no="",$emp_id="",$gdate="",$workschedule_id=""){
		$error = true;
		if($emp_id){
			if(!$comp_id){
				$s 	= array(
						'e.company_id'
				);
				$w	= array(
						'e.emp_id' 	=> $emp_id,
						'e.status'	=> "Active"
				);
				$this->db->select($s);
				$this->db->where($w);
				$q = $this->db->get("employee AS e");
				$r = $q->row();
				if($r){
					$error = false;
					$comp_id = $r->company_id;
				}
			}
		}
		else if(is_numeric($comp_id) && $emp_no){
			$s 	= array(
					'e.emp_id'
				);
			$w	= array(
					'e.company_id' 			=> $comp_id,
					'a.payroll_cloud_id' 	=> $emp_no,
					'e.status'				=> "Active"
				);
			$this->edb->select($s);
			$this->edb->where($w);
			$this->edb->join("accounts AS a", "a.account_id = e.account_id");
			$q = $this->edb->get("employee AS e");
			$r = $q->row();
			if($r){
				$error = false;
				$emp_id = $r->emp_id;
			}
		}
		
		if(!$error){
			
			$return_data	= array();
			$current_date 	= date("Y-m-d");
			if($gdate){
				$current_date = $gdate;
			}
			
			if(!$workschedule_id){
				$s_ws 	= array(
						'work_schedule_id'
						);
				$w_ws	= array(
						'emp_id'		=> $emp_id,
						'valid_from >=' =>$current_date,
						'until <=' 		=>$current_date,
						'status'		=> "Active"
						);
				$this->db->select($s_ws);
				$this->db->where($w_ws);
				$q = $this->db->get("employee_shifts_schedule");
				$r = $q->row();
				
				if($r){
					$workschedule_id = $r->work_schedule_id;
				}else{
					$w_pg	= array(
							'epi.emp_id'	=> $emp_id,
							'pg.company_id'	=> $comp_id
							);
					$this->db->select($s_ws);
					$this->db->where($w_pg);
					$this->db->join("employee_payroll_information AS epi","epi.payroll_group_id = pg.payroll_group_id");
					$q1 = $this->db->get("payroll_group AS pg");
					$r1 = $q1->row();
					if($r1){
						$workschedule_id = $r1->work_schedule_id;
					}
					else{
						return false;
					}
				}
			}
			
			if($workschedule_id){
				$return_data['emp_id']				= $emp_id;
				$return_data['company_id'] 			= $comp_id;
				$return_data['work_scheduel_id'] 	= $workschedule_id;
				
				$s_wt 	= array(
						'work_type_name',
						'name',
						);
				$w_wt	= array(
						'work_schedule_id' 	=> $workschedule_id,
						'comp_id'			=> $comp_id,
						'status'			=> 'Active',
						);
				$this->db->select($s_wt);
				$this->db->where($w_wt);
				$q_wt 	= $this->db->get("work_schedule");
				$r_wt	= $q_wt->row();
				if($r_wt){
					$work_type = $r_wt->work_type_name;
					$work_name = $r_wt->name;
					
					$return_data['work_type'] 	= $work_type;
					$return_data['name'] 		= $work_name;
					
					if($work_type == "Uniform Working Days"){
						$day 	= date("l",strtotime($current_date));
						$s_reg 	= array(
								'work_start_time',
								'work_end_time',
								'total_work_hours',
								'break_in_min',
								'latest_time_in_allowed',
								);
						$w_reg	= array(
								'work_schedule_id'	=> $workschedule_id,
								'days_of_work'		=> $day,
								'company_id'		=> $comp_id,
								);
						$this->db->select($s_reg);
						$this->db->where($w_reg);
						$q_reg = $this->db->get("regular_schedule");
						$r_reg = $q_reg->row();
						
						if($r_reg){
							$work_start_time 		= $r_reg->work_start_time;
							$work_end_time 			= $r_reg->work_end_time;
							$total_work_hours 		= $r_reg->total_work_hours;
							$break_in_min 			= $r_reg->break_in_min;
							$latest_time_in_allowed = $r_reg->latest_time_in_allowed;
							
							$return_data['work_start_time'] 		= $work_start_time;
							$return_data['work_end_time'] 			= $work_end_time;
							$return_data['total_work_hours'] 		= $total_work_hours;
							$return_data['break_in_min'] 			= $break_in_min;
							$return_data['latest_time_in_allowed'] 	= $latest_time_in_allowed;
						}
						else{
							$s_rest = array(
									'rest_day_id',
									);
							$w_rest	= array(
									'work_schedule_id'	=> $workschedule_id,
									'rest_day'			=> $day,
									'company_id'		=> $comp_id,
									);
							$this->db->select($s_rest);
							$this->db->where($w_rest);
							$q_rest = $this->db->get("rest_day");
							$r_rest = $q_rest->row();
							if($r_rest){
								$return_data['rest_day_id'] = $r_rest->rest_day_id;
								$return_data['rest_day'] 	= $day;
							}
						}
					}
					else if($work_type == "Flexible Hours"){
						$s_flex = array(
								'not_required_login',
								'total_hours_for_the_day',
								'total_hours_for_the_week',
								'total_days_per_year',
								'latest_time_in_allowed',
								'duration_of_lunch_break_per_day',
								);
						$w_flex = array(
								'work_schedule_id' 	=> $workschedule_id,
								'company_id'		=> $comp_id
								);
						$this->db->select($s_flex);
						$this->db->where($w_flex);
						$q_flex = $this->db->get("flexible_hours");
					}
					else if($work_type == "Workshift"){
					
					}
					else{
						return $return_data;
					}
				}
				else{
					return false;
				}
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
		exit();
	}
		
	public function late_min($comp_id,$date,$emp_id,$work_schedule_id,$emp_no="",$time_in= ""){
		# use in upload
		if($time_in){
			$current_time = $time_in;
		}else{
			$current_time = date('Y-m-d H:i:s');
		}
		$day = date('l',strtotime($date));
		$w_uwd = array(
				"work_schedule_id"	=> $work_schedule_id,
				"company_id"		=> $comp_id,
				"days_of_work" 		=> $day,
				"status" => 'Active'
		);
		$this->edb->where($w_uwd);
		$arr4 = array(
				'work_schedule_name',
				'work_end_time',
				'work_start_time',
				'break_in_min',
				'total_work_hours',
				'latest_time_in_allowed'
		);
		$this->edb->select($arr4);
		$q_uwd = $this->edb->get("regular_schedule");
		$r_uwd = $q_uwd->row();
		
		if($q_uwd->num_rows() > 0){
			$start_time = date("Y-m-d H:i:s",strtotime($date." ".$r_uwd->work_start_time));
			if($r_uwd->latest_time_in_allowed){
				$start_time = date('Y-m-d H:i:s',strtotime($start_time." +{$r_uwd->latest_time_in_allowed} minutes"));				
			}
			
			$tardiness_set = $this->tardiness_settings($emp_id, $comp_id);
			if($tardiness_set){				
				$start_time = date('Y-m-d H:i:s',strtotime($start_time." +{$tardiness_set} minutes"));				
			}
			
			if($current_time > $start_time){
				$min = $this->total_hours_worked($current_time, $start_time);
				return $min;
			}
		}
		else{// FLEXIBLE HOURS
			$fw = array(
				"f.company_id"			=> $comp_id,
				"f.work_schedule_id"	=> $work_schedule_id,
				);
			$this->db->where($fw);
			$arr3 	= array(
					'latest_time_in_allowed' 	=> 'f.latest_time_in_allowed',
					'name' 						=> 'ws.name',
					'number_of_breaks_per_day' 	=> 'number_of_breaks_per_day',
					'total_hours_for_the_day' 	=> 'total_hours_for_the_day'
					);
			$this->edb->select($arr3);
			$this->edb->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
			$fq = $this->edb->get("flexible_hours AS f");
			$r_fh = $fq->row();
			if($fq->num_rows() > 0){
				$data = $r_fh;
		
				if($r_fh->latest_time_in_allowed){
		
					$start_time = date("Y-m-d H:i:s",strtotime($date." ".$r_fh->latest_time_in_allowed));
					if($current_time > $start_time){
						$min = $this->total_hours_worked($current_time, $start_time);
						return $min;
					}
				}
			}else{
				
				$w_date = array(
						"es.valid_from <="		=>	$date,
						"es.until >="			=>	$date
				);
				$this->db->where($w_date);
					
				$w_ws = array(
						"em.work_schedule_id"	=> $work_schedule_id,
						"em.company_id"			=> $comp_id,
						"em.emp_id" 			=> $emp_id
				);
				$this->db->where($w_ws);
				$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
				$q_ws = $this->edb->get("employee_sched_block AS em");
				$r_ws = $q_ws->result();
					
				if($q_ws->num_rows() > 0){
					$split = $this->new_get_splitinfo($emp_no, $comp_id, $work_schedule_id,$emp_id,$date,$time_in);
					if($split){
						if($current_time > $split['start_time']){
							$min = $this->total_hours_worked($current_time, $split['start_time']);
							return $min;
						}
					}
				}
			}
		}
		return 0;
	}
		
	public function overbreak_min($comp_id,$date,$emp_id,$work_schedule_id,$lunch_out,$emp_no="",$lunc_in =""){
		// if lunchout is empty
		if(!$lunch_out){
			return 0;
		}
		
		if($lunc_in){
			$current_time = $lunc_in;
		}else{
			$current_time = date('Y-m-d H:i:s');
		}
		$day = date('l',strtotime($date));
		$w_uwd = array(
				//"payroll_group_id"=>$payroll_group,
				//"work_schedule_id"=>$r_pg->work_schedule_id,
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id,
				"days_of_work" => $day,
				"status" => 'Active'
		);
		$this->edb->where($w_uwd);
		$arr4 = array(
				'work_schedule_name',
				'work_end_time',
				'work_start_time',
				'break_in_min',
				'total_work_hours',
				'latest_time_in_allowed'
		);
		$this->edb->select($arr4);
		$q_uwd = $this->edb->get("regular_schedule");
		$r_uwd = $q_uwd->row();
	
		if($q_uwd->num_rows() > 0){
			if($r_uwd->break_in_min != 0){
				
				$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$r_uwd->break_in_min} minutes"));
				
				if($current_time > $lunch_in){
					$min = $this->total_hours_worked($current_time, $lunch_in);
					return $min;
				}
			}
		}else{// FLEXIBLE HOURS
			$fw = array(
				"f.company_id"		=> $comp_id,
				"f.work_schedule_id"=> $work_schedule_id,
				);
			$this->db->where($fw);
			$arr3 	= array(
					'latest_time_in_allowed' 			=> 'f.latest_time_in_allowed',
					'name' 								=> 'ws.name',
					'duration_of_lunch_break_per_day' 	=> 'duration_of_lunch_break_per_day',
					'total_hours_for_the_day' 			=> 'total_hours_for_the_day'
					);
			$this->edb->select($arr3);
			$this->edb->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
			$fq = $this->edb->get("flexible_hours AS f");
			$r_fh = $fq->row();
			if($fq->num_rows() > 0){
	
				if($r_fh->duration_of_lunch_break_per_day){
					$lunch_in = date("Y-m-d H:i:s",strtotime($lunch_out." +{$r_fh->duration_of_lunch_break_per_day} minutes"));
					
					if($current_time > $lunch_in){
						$min = $this->total_hours_worked($current_time, $lunch_in);
						return $min;
					}
				}
				
			}else{
				
				$fw = array(
					"f.company_id"		=> $comp_id,
					"f.work_schedule_id"=> $work_schedule_id,
					);
				$this->db->where($fw);
				$arr3 	= array(
						'latest_time_in_allowed' 	=> 'f.latest_time_in_allowed',
						'name' 						=> 'ws.name',
						'number_of_breaks_per_day' 	=> 'number_of_breaks_per_day',
						'total_hours_for_the_day' 	=> 'total_hours_for_the_day'
						);
				$this->edb->select($arr3);
				$this->edb->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
				$fq = $this->edb->get("flexible_hours AS f");
				$r_fh = $fq->row();
				if($fq->num_rows() > 0){
					$data = $r_fh;
				
					if($r_fh->latest_time_in_allowed != NULL || $r_fh->latest_time_in_allowed != ""){
				
						$start_time = date("Y-m-d H:i:s",strtotime($date." ".$r_fh->latest_time_in_allowed));
						if($current_time > $start_time){
							$min = $this->total_hours_worked($current_time, $start_time);
							return $min;
						}
					}
				}
				else{
					$w_date = array(
							"es.valid_from <="		=>	$date,
							"es.until >="			=>	$date
					);
					$this->db->where($w_date);
						
					$w_ws 	= array(
							"em.work_schedule_id"	=> $work_schedule_id,
							"em.company_id"			=> $comp_id,
							"em.emp_id" 			=> $emp_id
							);
					
					$this->db->where($w_ws);
					$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
					$q_ws = $this->edb->get("employee_sched_block AS em");
					$r_ws = $q_ws->result();
						
					if($q_ws->num_rows() > 0){
						
						$split = $this->new_get_splitinfo($emp_no, $comp_id, $work_schedule_id,$emp_id,$date,$lunc_in);
							
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
		}
		return 0;
	}
	
	public function mobile_check($employee_time_in_id,$time_out,$emp_id,$company_id,$check_type){
		/* SEND NOTIFICATIONS */
		
		// save approval token
		
		$emp_time_id = $employee_time_in_id;
		$w = array(
				"eti.employee_time_in_id"=>$employee_time_in_id,
		);
		$this->edb->where($w);

		$this->db->order_by("eti.time_in","DESC");
		$q = $this->edb->get("employee_time_in AS eti",1,0);
		$r = $q->row();
		
		$w = array(
				"company_id"=>$company_id,
		);
		$this->edb->where($w);
		$q2 = $this->edb->get("assigned_company");
		$r2 = $q2->row();
		
		$psa_id = $r2->payroll_system_account_id;
		
		$str = 'abcdefghijk123456789';
		$shuffled = str_shuffle($str);
		
		// generate token level
		$str2 = 'ABCDEFG1234567890';
		$shuffled2 = str_shuffle($str2);
		$new_logs = array('ret_date'=> $time_out);
		$approver_id = $this->employee->get_approver_name_timein($emp_id,$company_id)->location_base_login_approval_grp;
		$newtimein_approver = $this->agm->get_approver_name_timein_location($emp_id,$company_id);
		
		$timein_info = $this->agm->timein_information($emp_time_id);
		
		$fullname = ucfirst($timein_info->first_name)." ".ucfirst($timein_info->last_name);
		$employee_details = get_employee_details_by_empid($emp_id);
		$hours_notification = get_notify_settings($employee_details->location_base_login_approval_grp, $company_id);
		
		if($check_type == "time out") {
			if($r->location_1 != NULL || $r->location_2 != NULL || $r->location_3 != NULL) {
				
				if(is_workflow_enabled($company_id)){
					if($newtimein_approver != FALSE){
						if($hours_notification){
							$newtimein_approver = $this->agm->get_approver_name_timein_location($emp_id,$company_id);
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
										$this->send_location_notification("", $new_logs, $shuffled, $employee_time_in_id, $company_id,$emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2, $appr_id);
										
										if($hours_notification->twitter_notification == "yes"){
												
										}
											
										if($hours_notification->message_board_notification == "yes"){
											$url = base_url()."approval/employee_time_in/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
											$next_appr_notif_message = "{$fullname} used app for clock-in and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
											send_to_message_board($psa_id, $appr_id, $emp_id, $company_id, $next_appr_notif_message, "system");
										}
											
										$lflag = 1;
											
									}else{
										// send without link
										$this->send_location_notification("", $new_logs, $shuffled, $employee_time_in_id, $company_id,$emp_id, $appr_email, $appr_name, "", "" , "", "");
										
										if($hours_notification->twitter_notification == "yes"){
												
										}
											
										if($hours_notification->message_board_notification == "yes"){
											$next_appr_notif_message = "{$fullname} used app for clock-in.";
											send_to_message_board($psa_id, $appr_id, $emp_id,$company_id, $next_appr_notif_message, "system");
										}
											
									}
								}
							}
						 	
							$save_token = array(
									"time_in_id"	=> $emp_time_id,
									"token"			=> $shuffled,
									"comp_id"		=> $company_id,
									"emp_id"		=> $emp_id,
									"approver_id"	=> $approver_id,
									"level"			=> $new_level,
									"token_level"	=> $shuffled2,
									"location"		=> "",
									"flag_add_logs" => 2
							);
							$save_token_q = $this->db->insert("approval_time_in",$save_token);
							$id = $this->db->insert_id();
							$timein_update = array('approval_time_in_id'=>$id);
							$this->db->where('employee_time_in_id', $emp_time_id);
							$this->db->update('employee_time_in',$timein_update);
							$appr_err="";
						 	
						}else{
							$save_token = array(
									"time_in_id"	=> $emp_time_id,
									"token"			=> $shuffled,
									"comp_id"		=> $company_id,
									"emp_id"		=> $emp_id,
									"approver_id"	=> $approver_id,
									"level"			=> 1,
									"token_level"	=> $shuffled2,
									"location"		=> "",
									"flag_add_logs" => 2
							);
							$save_token_q = $this->db->insert("approval_time_in",$save_token);
							$id = $this->db->insert_id();
							$timein_update = array('approval_time_in_id'=>$id);
							$this->db->where('employee_time_in_id', $emp_time_id);
							$this->db->update('employee_time_in',$timein_update);
							$appr_err = "";
						 	
						}
					}
				}
			}
		}
	}
	
	public function send_location_notification($location = NULL, $new_logs = NULL, $token = NULL, $employee_timein = NULL, $company_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "" , $withlink = "No", $level_token = "", $appr_id = ""){
			
		$emp_no = $this->employee->check_emp_no($emp_id,$company_id);
			
		$currentdate = date('Y-m-d');
		$vx = $this->activate_nightmare_trap($company_id,$emp_no);
	
		if($vx){
			$currentdate = $vx['currentdate'];
		}
	
		$currentdate1 = $currentdate;
			
		$work_schedule_id = $this->employee->emp_work_schedule($emp_id,$company_id,$currentdate1);
		$check_work_type = $this->employee->work_schedule_type($work_schedule_id, $company_id);
		if($check_work_type == 'Workshift') {
			$q = $this->employee->check_split_timein($emp_id,$company_id,$employee_timein);
		} else {
			$q = $this->employee->check_timein($emp_id,$company_id,$employee_timein);
		}
	
		if($q != FALSE){
			$fullname = $this->employee->get_employee_fullname($emp_id,$company_id);
			$date_applied = date('F d, Y', strtotime($q->change_log_date_filed));
				
			$font_name = "'Open Sans'";
			$link = '<a href="'.base_url().'approval/employee_time_in/index/'.$token.'/'.$level_token.'/1'.$appr_id.'0"><img src="'.base_url().'assets/theme_2015/images/images-emailer/btn-view-mobile-login.jpg" width="206" height="42" alt=" "></a>';
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
						<td style="width:150px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.date('F d, Y h:i A', strtotime($q->time_in)).'</td>
					</tr>
					<tr>
						<td style="width:150px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Location:</td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$location_1.'</td>
					</tr>
					<tr>
						<td style="width:150px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.date('F d, Y h:i A', strtotime($q->lunch_out)).'</td>
					</tr>
					<tr>
						<td style="width:150px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Location:</td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$location_2.'</td>
					</tr>
					<tr>
						<td style="width:150px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.date('F d, Y h:i A', strtotime($q->lunch_in)).'</td>
					</tr>
					<tr>
						<td style="width:150px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Location:</td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$location_3.'</td>
					</tr>
					<tr>
						<td style="width:150px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out: </td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.date('F d, Y h:i A', strtotime($new_logs['ret_date'])).'</td>
					</tr>
					<tr>
						<td style="width:150px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Location:</td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$location.'</td>
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
								        	<td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="'.(newsletter_logo($company_id)).'" height="62" alt=" "></td>
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
	
	public function tardiness_settings($emp_id,$comp_id){
		
		$w_uwd = array(
				"emp_id" => $emp_id,
				"company_id" => $comp_id
		);
		$this->edb->where($w_uwd);
		$arr4 = array(
				'rank_id',
				'payroll_group_id' 
		);
		$this->edb->select($arr4);
		$q_uwd = $this->edb->get("employee_payroll_information");
		$r_uwd = $q_uwd->row();
		
		if($r_uwd){
			
			$w_uwd = array(
					"comp_id" => $comp_id
			);
			$this->edb->where($w_uwd);
			$tard = $this->edb->get("tardiness_settings");
			$tard_qx = $tard->result();
			
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
	
	public function new_get_splitinfo_fritz($emp_no,$comp_id,$work_schedule_id,$emp_id="",$gDate="",$time_in=""){
	    
	    //*** BARACK NEW LOGIC GET SPLIT BLOCK SCHEDUL DEPENDING CURRENT TIME ***//
	    //** First (current datetime) specify which block belong
	    //** if block is specify, check if (cdt) [timein or timeOut] [lunchOut or lunchIn],
	    //** if (cdt) is not belong to block find the nearest block,
	    //** if block is specify, check if (cdt) timein or timeOut,
	    
	    $check_emp_no 			= $this->new_check_emp_info($emp_no,$comp_id);
	    $emp_id					= ($check_emp_no) ? $check_emp_no->emp_id : $emp_id;
	    $current_date			= ($gDate) ? $gDate : date("Y-m-d");
	    $current_date_str		= strtotime($current_date);
	    $prev_date				= ($gDate) ? $gDate : date("Y-m-d");
	    $prev_date_str 			= strtotime($prev_date);
	    $current_time			= ($time_in) ? date("H:i:s", strtotime($time_in)) : date("H:i:s");
	    $current_datetime		= ($time_in) ? $time_in : date("Y-m-d H:i:s");
	    $current_datetime_str	= strtotime($current_datetime);
	    $get_sched				= false;
	    $schedule_blocks_id		= "";
	    $break					= 0;
	    $total_hour_block_sched	= 0;
	    $arrx 					= array(
	        'work_schedule_id',
	        'employee_time_in_id',
	        "date"
	    );
	    
	    $this->db->select($arrx);
	    $w 		= array(
	        "emp_id"	=> $emp_id,
	        "status" 	=> "Active",
	        "comp_id" 	=> $comp_id
	    );
	    $this->db->where($w);
	    $this->db->order_by("date","DESC");
	    $q = $this->db->get("employee_time_in AS eti",1,0);
	    $r = $q->row();
	    
	    if($r){
	        // if wala pa sa db na save ang current date
	        $prev_date 		= $r->date;
	        $prev_date_str 	= strtotime($prev_date);
	    }
	    if($prev_date_str >= $current_date_str){
	        $prev_date 		= $current_date;
	        $prev_date_str 	= strtotime($prev_date);
	    }
	    if($prev_date_str == $current_date_str){
	        // if naa na sa db ang current date
	        $prev_date	= date('Y-m-d', strtotime($prev_date." -1 day"));
	    }
	    
	    //*** DISPLAY ALL SPLIT BLOCK SCHEDULE ***//
	    $break = 0;
	    $start_time = "";
	    $end_time = "";
	    $total_h_p_block = 0;
	    $block_name = "";
	    $schedule_blocks_id = "";
	    $last_block = "";
	    $clock_type = "";
	    $first_block_start_time = "";
	    $total_hour_block_sched = "";
	    
	    $arrSelect 	= array(
	        "emp_schedule_block_id",
	        // "es.shifts_schedule_id",
	        "sb.schedule_blocks_id",
	        "em.emp_id",
	        "valid_from",
	        "until",
	        //"payroll_group_id",
	        //"status",
	        "block_name",
	        "start_time",
	        "end_time",
	        "break_in_min",
	        "total_hours_work_per_block",
	        "location_and_offices_id"
	    );
	    
	    $w_date = array(
	        "em.valid_from <="		=>	date('Y-m-d', strtotime($current_date)),
	        "em.until >="			=>	date('Y-m-d', strtotime($current_date)),
	    );
	    
	    $this->db->select($arrSelect);
	    $this->db->where($w_date);
	    
	    
	    $w_ws 	= array(
	        "em.work_schedule_id"	=> $work_schedule_id,
	        "em.company_id"			=> $comp_id,
	        "em.emp_id" 			=> $emp_id
	    );
	    
	    $this->db->where($w_ws);
	    // $this->db->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
	    $this->db->join("schedule_blocks AS sb","sb.schedule_blocks_id = em.schedule_blocks_id","LEFT");
	    $q_ws = $this->db->get("employee_sched_block AS em");
	    $r_ws = $q_ws->result();
	    
	    if($r_ws){
	        //*** THIS IS TO CALCULATE FOR THE BOUNDARY OF THE SPLIT SCHED --> CREATE NEW LOGS
	        $first 	= reset($r_ws);
	        $last 	= end($r_ws);
	        
	        $first_block_start_time 	= $first->start_time;
	        $first_block_start_time_str = strtotime($first->start_time);
	        $last_block_end_time		= $last->end_time;
	        $last_block_id				= $last->schedule_blocks_id;
	        $last_block_end_time_str	= strtotime($last->end_time);
	        
	        $first_block_start_datetime		= date('Y-m-d H:i:s', strtotime($prev_date." ".$first_block_start_time." +1 day"));
	        if($first_block_start_time_str > $last_block_end_time_str){
	            $last_block_end_datetime	= date('Y-m-d H:i:s', strtotime($prev_date." ".$last_block_end_time." +1 day"));
	        }
	        else{
	            $last_block_end_datetime	= date('Y-m-d H:i:s', strtotime($prev_date." ".$last_block_end_time));
	        }
	        
	        //*** get the difference between the first start time and the last end time
	        $total      = strtotime($first_block_start_datetime) - strtotime($last_block_end_datetime);
	        $hours      = floor($total / 60 / 60);
	        $minutes    = floor(($total - ($hours * 60 * 60)) / 60);
	        $ret 		= ($hours * 60) + $minutes;
	        $total_gap  = ($ret < 0) ? '0' : $ret;
	        $total_gap	= $total_gap/2;
	        if($total_gap > 120){
	            $total_gap = 120;
	        }
	        $first_block_boundary_datetime 		= date('Y-m-d H:i:s',strtotime($first_block_start_datetime . "-".$total_gap." minutes"));
	        $first_block_boundary_datetime_str 	= strtotime($first_block_boundary_datetime);
	        
	        if($first_block_boundary_datetime_str > $current_datetime_str){
	            $current_date = $prev_date;
	        }
	        
	        $current_date_str	= strtotime($current_date);
	        $prev_end_time		= $last_block_end_datetime;
	        
	        $time_is_check 		= $this->check_if_date_is_time_in($emp_id,$current_date,$comp_id);
	        $eti				= ($time_is_check) ? $time_is_check->employee_time_in_id : "";
	        $schedule_blocks_id_prev	= "";
	        $break_prev 				= "";
	        $eti_prev					= "";
	        $clock_type_prev			= "";
	        $start_time_prev			= "";
	        $end_time_prev				= "";
	        $total_h_p_block_prev		= "";
	        $block_name_prev			= "";
	        $last_block_prev			= "";
	        
	        // get total hours in split blocks
	        foreach ($r_ws AS $rws){
	            $total_hour_block_sched = $total_hour_block_sched + $rws->total_hours_work_per_block;
	        }
	        
	        foreach ($r_ws AS $rws){
	            
	            $prev_end_time_str	= strtotime($prev_end_time);
	            $start_time 		= $rws->start_time;
	            $end_time 			= $rws->end_time;
	            $start_time_str 	= strtotime($start_time);
	            $end_time_str 		= strtotime($end_time);
	            
	            if($first_block_start_time_str > $start_time_str){
	                $current_date	= date('Y-m-d', strtotime($current_date." +1 day"));
	            }
	            
	            $start_datetime	= date('Y-m-d H:i:s', strtotime($current_date." ".$start_time));
	            
	            if($start_time_str > $end_time_str){
	                $end_datetime	= date('Y-m-d H:i:s', strtotime($current_date." ".$end_time." +1 day"));
	            }else{
	                $end_datetime	= date('Y-m-d H:i:s', strtotime($current_date." ".$end_time));
	            }
	            
	            $start_datetime_str = strtotime($start_datetime);
	            $end_datetime_str 	= strtotime($end_datetime);
	            
	            if(($current_datetime_str >= $start_datetime_str) && ($current_datetime_str <= $end_datetime_str)){
	                
	                $schedule_blocks_id = $rws->schedule_blocks_id;
	                $break 				= $rws->break_in_min;
	                $clock_type			= $this->check_clock_type($time_is_check,$emp_id,$comp_id,$eti,$schedule_blocks_id,$current_date,$break);
	                $start_time			= $rws->start_time;
	                $end_time			= $rws->end_time;
	                $total_h_p_block	= $rws->total_hours_work_per_block;
	                $block_name 		= $rws->block_name;
	                $last_block			= $last_block_id;
	                break;
	            }
	            
	            else if(($current_datetime_str >= $prev_end_time_str) && ($current_datetime_str <= $start_datetime_str)){
	                
	                //
	                $tx      	= strtotime($start_datetime) - strtotime($prev_end_time);
	                $hx      	= floor($tx / 60 / 60);
	                $mx		    = floor(($tx - ($hx * 60 * 60)) / 60);
	                $retx 		= ($hx * 60) + $mx;
	                $tgx  		= ($retx < 0) ? '0' : $retx;
	                $tgx		= $tgx/2;
	                if($tgx > 120){
	                    $tgx = 120;
	                }
	                $between_block_boundary		= date('Y-m-d H:i:s',strtotime($start_datetime. "-".$tgx." minutes"));
	                $between_block_boundary_str = strtotime($between_block_boundary);
	                
	                if($between_block_boundary_str <= $current_datetime_str){
	                    $schedule_blocks_id 	= $rws->schedule_blocks_id;
	                    $break 					= $rws->break_in_min;
	                    $clock_type				= $this->check_clock_type($time_is_check,$emp_id,$comp_id,$eti,$schedule_blocks_id,$current_date,$break);
	                    $start_time				= $rws->start_time;
	                    $end_time				= $rws->end_time;
	                    $total_h_p_block		= $rws->total_hours_work_per_block;
	                    $block_name 			= $rws->block_name;
	                    $last_block				= $last_block_id;
	                }
	                else{
	                    $schedule_blocks_id 	= $schedule_blocks_id_prev;
	                    $break 					= $break_prev;
	                    $eti					= $eti_prev;
	                    $clock_type				= $this->check_clock_type($time_is_check,$emp_id,$comp_id,$eti_prev,$schedule_blocks_id_prev,$current_date,$break_prev);
	                    $start_time				= $start_time_prev;
	                    $end_time				= $end_time_prev;
	                    $total_h_p_block		= $total_h_p_block_prev;
	                    $block_name 			= $block_name_prev;
	                    $last_block				= $last_block_prev;
	                }
	                break;
	            }
	            else if(($current_datetime_str < $first_block_boundary_datetime_str) && ($last_block_id == $rws->schedule_blocks_id)){
	                
	                $schedule_blocks_id = $last_block_id;
	                $break 				= $rws->break_in_min;
	                $clock_type			= $this->check_clock_type($time_is_check,$emp_id,$comp_id,$eti,$schedule_blocks_id,$current_date,$break);
	                $start_time			= $rws->start_time;
	                $end_time			= $rws->end_time;
	                $total_h_p_block	= $rws->total_hours_work_per_block;
	                $block_name 		= $rws->block_name;
	                $last_block			= $last_block_id;
	                break;
	            }
	            
	            $schedule_blocks_id_prev	= $rws->schedule_blocks_id;
	            $break_prev 				= $rws->break_in_min;
	            $eti_prev					= $eti;
	            $start_time_prev			= $rws->start_time;
	            $end_time_prev				= $rws->end_time;
	            $total_h_p_block_prev		= $rws->total_hours_work_per_block;
	            $block_name_prev 			= $rws->block_name;
	            $last_block_prev			= $last_block_id;
	            $prev_end_time				= $end_datetime;
	            
	        }
	    }
	    
	    $return = array(
	        'break_in_min' 					=> $break,
	        'start_time' 					=> $start_time,
	        'end_time' 						=> $end_time,
	        'total_hours_work_per_block' 	=> $total_h_p_block,
	        'block_name' 					=> $block_name,
	        'schedule_blocks_id' 			=> $schedule_blocks_id,
	        'last_block' 					=> $last_block,
	        'clock_type' 					=> $clock_type,
	        'first_block_start_time' 		=> $first_block_start_time,
	        'total_hour_block_sched' 		=> $total_hour_block_sched,
	    );
	    return $return;
	}
	
	public function overbreak_min_fritz($comp_id,$date,$emp_id,$work_schedule_id,$lunch_out,$emp_no="",$lunc_in =""){
	    // if lunchout is empty
	    if(!$lunch_out){
	        return 0;
	    }
	    
	    if($lunc_in){
	        $current_time = $lunc_in;
	    }else{
	        $current_time = date('Y-m-d H:i:s');
	    }
	    $day = date('l',strtotime($date));
	    $w_uwd = array(
	        //"payroll_group_id"=>$payroll_group,
	        //"work_schedule_id"=>$r_pg->work_schedule_id,
	        "work_schedule_id"=>$work_schedule_id,
	        "company_id"=>$comp_id,
	        "days_of_work" => $day,
	        "status" => 'Active'
	    );
	    $this->edb->where($w_uwd);
	    $arr4 = array(
	        'work_schedule_name',
	        'work_end_time',
	        'work_start_time',
	        'break_in_min',
	        'total_work_hours',
	        'latest_time_in_allowed'
	    );
	    $this->edb->select($arr4);
	    $q_uwd = $this->edb->get("regular_schedule");
	    $r_uwd = $q_uwd->row();
	    
	    if($q_uwd->num_rows() > 0){
	        if($r_uwd->break_in_min != 0){
	            
	            $lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$r_uwd->break_in_min} minutes"));
	            
	            if($current_time > $lunch_in){
	                $min = $this->total_hours_worked($current_time, $lunch_in);
	                return $min;
	            }
	        }
	    }else{// FLEXIBLE HOURS
	        $fw = array(
	            "f.company_id"		=> $comp_id,
	            "f.work_schedule_id"=> $work_schedule_id,
	        );
	        $this->db->where($fw);
	        $arr3 	= array(
	            'latest_time_in_allowed' 			=> 'f.latest_time_in_allowed',
	            'name' 								=> 'ws.name',
	            'duration_of_lunch_break_per_day' 	=> 'duration_of_lunch_break_per_day',
	            'total_hours_for_the_day' 			=> 'total_hours_for_the_day'
	        );
	        $this->edb->select($arr3);
	        $this->edb->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
	        $fq = $this->edb->get("flexible_hours AS f");
	        $r_fh = $fq->row();
	        if($fq->num_rows() > 0){
	            
	            if($r_fh->duration_of_lunch_break_per_day){
	                $lunch_in = date("Y-m-d H:i:s",strtotime($lunch_out." +{$r_fh->duration_of_lunch_break_per_day} minutes"));
	                
	                if($current_time > $lunch_in){
	                    $min = $this->total_hours_worked($current_time, $lunch_in);
	                    return $min;
	                }
	            }
	            
	        }else{
	            
	            $fw = array(
	                "f.company_id"		=> $comp_id,
	                "f.work_schedule_id"=> $work_schedule_id,
	            );
	            $this->db->where($fw);
	            $arr3 	= array(
	                'latest_time_in_allowed' 	=> 'f.latest_time_in_allowed',
	                'name' 						=> 'ws.name',
	                'number_of_breaks_per_day' 	=> 'number_of_breaks_per_day',
	                'total_hours_for_the_day' 	=> 'total_hours_for_the_day'
	            );
	            $this->edb->select($arr3);
	            $this->edb->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
	            $fq = $this->edb->get("flexible_hours AS f");
	            $r_fh = $fq->row();
	            if($fq->num_rows() > 0){
	                $data = $r_fh;
	                
	                if($r_fh->latest_time_in_allowed != NULL || $r_fh->latest_time_in_allowed != ""){
	                    
	                    $start_time = date("Y-m-d H:i:s",strtotime($date." ".$r_fh->latest_time_in_allowed));
	                    if($current_time > $start_time){
	                        $min = $this->total_hours_worked($current_time, $start_time);
	                        return $min;
	                    }
	                }
	            }
	            else{
	                $w_date = array(
	                    "em.valid_from <="		=>	$date,
	                    "em.until >="			=>	$date
	                );
	                $this->db->where($w_date);
	                
	                $w_ws 	= array(
	                    "em.work_schedule_id"	=> $work_schedule_id,
	                    "em.company_id"			=> $comp_id,
	                    "em.emp_id" 			=> $emp_id
	                );
	                
	                $this->db->where($w_ws);
	                // $this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
	                $q_ws = $this->db->get("employee_sched_block AS em");
	                $r_ws = $q_ws->result();
	                
	                if($q_ws->num_rows() > 0){
	                    
	                    $split = $this->new_get_splitinfo_fritz($emp_no, $comp_id, $work_schedule_id,$emp_id,$date,$lunc_in);
	                    
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
	    }
	    return 0;
	}
	
	public function get_split_time_list($emp_no,$work_schedule_id,$comp_id,$currentdate){
	    
	    $arrx = array(
	        'date'		=> 'eti.date',
	        'time_in'	=> 'eti.time_in',
	        'lunch_out' => 'eti.lunch_out',
	        'lunch_in' 	=> 'eti.lunch_in',
	        'time_out' 	=> 'eti.time_out'
	    );
	    $this->edb->select($arrx);
	    $w = array(
	        "a.payroll_cloud_id"	=> $emp_no,
	        "a.user_type_id"		=> "5",
	        "eti.status" 			=> "Active",
	        "eti.comp_id" 			=> $comp_id,
	        "eti.date"				=> $currentdate
	    );
	    $this->edb->where($w);
	    $this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
	    $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
	    $this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
	    $this->db->order_by("eti.time_in","ASC");
	    
	    $q = $this->edb->get("schedule_blocks_time_in AS eti");
	    
	    return ($q->num_rows() > 0) ? $q->result() : false;
	    
	}

	public function new_check_emp_info($emp_no,$comp_id){
		$s = array(
				'e.emp_id'
		);
		$w_emp = array(
				"a.payroll_cloud_id"=>$emp_no,
				
		);
		$w 	   = array(
				"a.user_type_id"=>"5",
				"e.company_id" => $comp_id,
		);
		$this->db->select($s);
		$this->edb->where($w_emp);
		$this->db->where($w);
		$this->db->join("accounts AS a","a.account_id = e.account_id","INNER");
		$q_emp = $this->edb->get("employee AS e");
		$q = $q_emp->row();
	
		return ($q_emp->num_rows() > 0) ? $q_emp->row() : FALSE ;
	}
}


/* End of file emp_login_model.php */
