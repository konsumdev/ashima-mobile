<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Employee leaves Model 
 *
 * @category Controller
 * @version 1.0
 * @author Christopher Cuizon <christophercuizons@gmail.com>
 */
	class employee_leaves_model extends CI_Model {
		
		public function __construct() {
			parent::__construct();
		}
		
		public function test(){
			echo 'sdfsdaaaaaaaaaaaaaaaaaa';
		}
		
		public function edit_delete_void($emp_id,$comp_id,$gDate){
			$gDate 			= date("Y-m-d",strtotime($gDate));
			$return_void 	= false;
			$stat_v1 		= "";
			$w = array(
					'prc.emp_id' 			=> $emp_id,
					'prc.company_id' 		=> $comp_id,
					'prc.period_from <=' 	=> $gDate,
					'prc.period_to >='  	=> $gDate,
					'prc.status'			=> 'Active'
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
						'epi.emp_id' 			=> $emp_id,
						'dpr.company_id' 		=> $comp_id,
						'dpr.period_from <=' 	=> $gDate,
						'dpr.period_to >='  	=> $gDate,
						'dpr.status'			=> 'Active'
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
		
		/**
		 * GET LEAVE TYPE OF THEM
		 * @param unknown $company_id
		 * @param unknown $employment_type
		 */
		public function get_leave_type($company_id){
			$w = array(
				"lt.company_id"=>$company_id,
				#"app.name"=>$employment_type,
			#	'app.type'=>'employment_type'
			);
			$this->db->where($w);
		
		#	$this->db->join('leave_type_settings_leave_type_applicable_for AS app','app.leave_type_id = lt.leave_type_id','INNER');
			$q = $this->db->get("leave_type AS lt");
			$r = $q->result();
			return ($r) ? $r: FALSE;
		}
		
		public function get_leave_type_full($company_id){
			$w = array(
					"lt.company_id"=>$company_id,
					#"app.name"=>$employment_type,
					#	'app.type'=>'employment_type'
			);
			$select = array(
					'lt.leave_type_id','lt.leave_type'
			);
			$this->db->select($select);
			$this->db->where($w);
			$this->db->join('leave_type_settings_leave_type_applicable_for AS app','app.leave_type_id = lt.leave_type_id','INNER');
			$this->db->join('leave_entitlements_settings AS les','les.leave_type_id = lt.leave_type_id','LEFT');
			$this->db->group_by('lt.leave_type_id');
			$q = $this->db->get("leave_type AS lt");
			
			$r = $q->result();
			return ($r) ? $r: FALSE;
		}
		
		/**
		 * GET LEAVE APPLICABLE FOR
		 * @param int $company_id
		 * @param int $leave_type_id
		 */
		public function get_leave_applicable_for($company_id,$leave_type_id){
			$where = array(
				'leave_type_id'=>$leave_type_id,
				'company_id'=>$company_id,
				'status'=>'Status'
			);
			$this->db->where($where);
			$q = $this->db->get('leave_type_settings_leave_type_applicable_for');
			$r = $q->result();
			return $r;
		}
		
		/**
		 * GET EMPLOYEES DATA
		 * @param int $emp_id
		 * @param int $company_id
		 */
		public function get_employees_data($company_id,$emp_id){
			if($emp_id){
				$select = array(
					'e.emp_id',
					'epi.employment_type',
					'e.gender',
					'e.marital_status'	
				);
				$this->db->select($select);
				$where = array(
					'a.user_type_id'=>5,
					'a.deleted'=>'0',
					'e.status'=>'Active',
					'epi.status'=>'Active',
					'e.emp_id'=>$emp_id,
					'epi.company_id'=>$company_id
				);
				$this->edb->where($where);
				$this->edb->join('employee AS e','e.account_id=a.account_id','INNER');
				$this->db->join('employee_payroll_information AS epi','e.emp_id=epi.emp_id','INNER');
				$q = $this->edb->get('accounts AS a');
				$r = $q->row();
				return $r;
			}else{
				return false;
			}
		}
		
		/**
	 * Employee Leave Information
	 * @param unknown_type $comp_id
	 */
		public function emp_leave($sort_by,$employee_id_name="",$limit, $start, $comp_id,$display_number = false,$order_by="")
		{
			$s = array(
					'accounts.account_id','employee.first_name','employee.last_name','employee.middle_name','accounts.payroll_cloud_id',	
					"employee_leaves.leaves_id",
					"employee_leaves.emp_id",
					"employee_leaves.rank_id",
					"employee_leaves.leave_type_id",
					"employee_leaves.leave_credits",
					"employee_leaves.remaining_leave_credits",
					"employee_leaves.previous_leave_credits",
					"employee_leaves.as_of",
					"employee_leaves.company_id",
					"employee_leaves.leave_accrue",
					"employee_leaves.years_of_service",
					"employee_leaves.created_date",
					"employee_leaves.updated_date",
					"employee_leaves.created_by_account_id",
					"employee_leaves.updated_by_account_id",
					"employee_leaves.`status`",
					"employee_leaves.paid_leave",
					"employee_leaves.what_happen_to_unused_leave",
					"employee_leaves.provide_half_day_option",
					"employee_leaves.allow_to_apply_leaves_beyond_limit",
					"employee_leaves.exclude_holidays",
					"employee_leaves.exclude_weekends",
					"employee_leaves.consecutive_days_after_weekend_holiday",
					"employee_leaves.num_consecutive_days_after_weekend_holiday",
					"employee_leaves.days_before_leave_application",
					"employee_leaves.num_days_before_leave_application",
					"employee_leaves.consecutive_days_allowed",
					"employee_leaves.num_consecutive_days_allowed",
					"employee_leaves.as_of_date_created",
					"employee_leaves.effective_date",
					"lt.leave_type AS leave_type","lt.required_documents AS required_documents",
					"r.rank_name","p.name AS payroll_group_name",
					"lt.accrual_period AS accrual_period",
				#	"employee_leaves.start_of_accrual",
				#	"employee_leaves.start_of_accrual_day",
					"lt.start_of_accrual", #added ni kuha lang ta data sa leave setting snot sa employee
					"lt.start_of_accrual_day", #added ni kuha lang ta data sa leave setting snot sa employee
					"ep.date_hired", #added ni kuha lang ta data sa leave setting snot sa employee
					"lt.effective_start_date_by", #added ni kuha lang ta data sa leave setting snot sa employee
					"lt.effective_start_date", #added ni kuha lang ta data sa leave setting snot sa employee
					"lt.required_documents", #added ni kuha lang ta data sa leave setting snot sa employee
					"lt.start_of_accrual",
					"lt.start_of_accrual_day",
					"lt.accrual_period",
					"lt.paid_leave",
					'lt.leave_units',
					'lt.accrual_schedule',
					'lt.start_of_accrual_month',
					'employee_leaves.existing_leave_used_to_date'
			);
			$this->edb->select($s);
			
			$select2 = array('lt.created_date AS lt_created_date');
			$this->db->select($select2);
			
			$where = array(
				'employee_leaves.company_id' => $comp_id,
				'employee.status'			 => 'Active',
				'accounts.user_type_id' => '5'
			);
			
			if($employee_id_name == ''){
				$where["ep.employee_status"] = "Active";
			}
			
			$this->edb->where($where);
			$this->db->where('employee_leaves.status','Active');
			# $this->edb->join('rank AS r','r.rank_id = employee_leaves.rank_id','INNER'); #comment out kay sauna ra ni
			$this->edb->join('leave_type AS lt','lt.leave_type_id = employee_leaves.leave_type_id','INNER');
			#$this->edb->join('leave_entitlements_settings AS les','les.leave_type_id = employee_leaves.leave_type_id AND les.rank_id = employee_leaves.rank_id','INNER');
			$this->edb->join('employee','employee.emp_id = employee_leaves.emp_id','INNER');
			$this->edb->join('accounts','accounts.account_id = employee.account_id','INNER');
	
			$this->edb->join("employee_payroll_information AS ep","ep.emp_id = employee.emp_id","INNER");
			$this->edb->join("payroll_group AS p","p.payroll_group_id = ep.payroll_group_id","LEFT");
			
			## ADDED JAN 4 2016 fix bug 
			/** updated ranks september 7 2016 **/
			$this->edb->join('rank AS r','r.rank_id = ep.rank_id','INNER');
			/** updated ranks end **/
			$this->db->group_by('employee_leaves.leaves_id');
			## END ADDED fix bug
			$konsum_key = konsum_key();
			if($employee_id_name !==""){
				$employee_id_name = $this->db->escape_like_str(stripslashes(clean_input($employee_id_name)));
				$where2  = array(
					'employee.company_id' => $comp_id,
					'employee.status'	  => 'Active',
					'accounts.user_type_id' => '5',
					'accounts.deleted'=>'0',
					'employee.status'=>'Active',
					'employee.deleted'=>'0'
				);
				$this->db->where($where2);
				$this->db->where("CONVERT(CONCAT(AES_DECRYPT(employee.first_name,'{$konsum_key}'),' ',AES_DECRYPT(employee.last_name,'{$konsum_key}')) USING latin1) LIKE '%".$employee_id_name."%'", NULL, FALSE); // encrypt
				$this->db->or_where("CONVERT(AES_DECRYPT(accounts.payroll_cloud_id,'{$konsum_key}') USING latin1) = ",$employee_id_name);
				$this->db->where($where2);
			}
	
			if($sort_by != ""){
				if($order_by == ''){
					$order_by = 'asc';
				}else{
					if($order_by == 'desc'){
						$order_by = 'desc';
					}
				}
	
				if($sort_by == "first_name"){
					$sort_by = "employee.first_name";
					$this->edb->order_by($sort_by,$order_by);
				}elseif($sort_by == "payroll_group"){
					$sort_by = "p.name";
					$this->db->order_by($sort_by,$order_by);
				}elseif($sort_by == "rank_name"){
					$sort_by = "r.rank_name";
					$this->db->order_by($sort_by,$order_by);
				}elseif($sort_by == "leave_type"){
					$sort_by = "lt.leave_type_id";
					$this->db->order_by($sort_by,$order_by);
				}else if($sort_by == "last_name"){
					$sort_by = "employee.last_name";
					$this->edb->order_by($sort_by,$order_by);
				}
			}else{
				$this->edb->order_by('employee.last_name','asc');
			}
	
			$sql = false;
			if($display_number == true){
				$sql = $this->edb->get('employee_leaves');
			}else{
				$sql = $this->edb->get('employee_leaves',$limit,$start);
				
			}
	
			if($sql->num_rows() > 0){
				if($display_number == true){
					return $sql->num_rows();
				}
	
				$results = $sql->result();
				$sql->free_result();
				
				return $results;
			}else{
				return FALSE;
			}
		
		}
		
		/**
		 * CHECK LEAVE TYPE
		 * @param unknown $company_id
		 * @param string $rank_id
		 * @param unknown $employment_type
		 * @param string $gender
		 */
		public function check_valid_leave_type($company_id,$emp_id,$rank_id ="",$employment_type,$gender=""){
			$where = array(
				'a.deleted'=>'0',
				'e.status'=>'Active',
				'epi.status'=>'Active',
				'a.user_type_id'=>5,
				'epi.company_id'=>$company_id,
				'e.emp_id'=>$emp_id
			);
			
			if(count($rank_id)>0){
				//$where['epi.rank_id'] = $rank_id;
				$this->db->where_in('epi.rank_id',$rank_id);
			}
			
			if($employment_type){
				$this->db->where_in('epi.employment_type',$employment_type);
			}
			
			if(count($gender)>0){
				$this->db->where_in('e.gender',$gender);
			}
			$this->db->where($where);
			$this->edb->join('employee AS e','e.account_id = a.account_id','INNER');
			$this->edb->join('employee_payroll_information AS epi','epi.emp_id = e.emp_id','INNER');
			$q = $this->edb->get('accounts AS a');
			$r = $q->row();
			return $r;
			
		}
		
		
		public function check_employment_type($company_id,$leave_type_id){
			if($leave_type_id){
				$employment_type = array();
				$where1 = array(
						'leave_type_id'=>$leave_type_id,
						'type'=>'employment_type',
						'company_id'=>$company_id
				);
				$this->db->where($where1);
				$q = $this->db->get('leave_type_settings_leave_type_applicable_for');
				$r = $q->result();
				if($r){
					foreach($r as $rk=>$rv){
						$employment_type[] = $rv->name;
					}
				}
				return $employment_type;
			}
		}
		
		public function check_gender($company_id,$leave_type_id){
			if($leave_type_id){
				$gender = array();
				$where2 = array(
						'leave_type_id'=>$leave_type_id,
						'type'=>'gender',
						'company_id'=>$company_id
				);
				$this->db->where($where2);
				$q2 = $this->db->get('leave_type_settings_leave_type_applicable_for');
				$r2 = $q2->result();
				if($r2){
					foreach($r2 as $r2k=>$r2v){
						$gender[] = $r2v->name;
					}
				}
				return $gender;
			}
		}
		
		public function check_rank($company_id,$leave_type_id){
			if($leave_type_id){
				$rank = array();
				$where3 = array(
						'leave_type_id'=>$leave_type_id,
						'company_id'=>$company_id
				);
				$this->db->where($where3);
				$q3 = $this->db->get('leave_entitlements_settings');
				$r3 = $q3->result();
				if($r3){
					foreach($r3 as $r3k=>$r3v){
						$rank[] = $r3v->rank_id;
					}
				}
				return $rank;
			}
		}
		
		/**
		 * USAGE FOR EMPLOYEE LEAVE TYPE OPTION ONLY
		 * @param int $company_id
		 * @param int $emp_id
		 * @return string $option string
		 */
		public function option_leave_type($company_id,$emp_id){
			$leave_type = $this->get_leave_type_full($company_id);
			$option = ''; 
			if($leave_type)
			{
				foreach($leave_type as $k=>$v)
				{
					### NOW CHECK OUR LEAVE SETTINGS VALUES
					$employment_type = array();
					$gender = array();
					$rank = array();
					if($v->leave_type_id){
						// employment type array(1,3,4,)
						$employment_type = $this->check_employment_type($this->company_id,$v->leave_type_id);
						// for gender
						$gender = $this->check_gender($this->company_id,$v->leave_type_id);
						// rank
						$rank = $this->check_rank($this->company_id,$v->leave_type_id);
					}
					$check_lt = $this->check_valid_leave_type($this->company_id,$emp_id,$rank,$employment_type,$gender);
					if($check_lt){
						$option .='<option value="'.$v->leave_type_id.'">'.$v->leave_type.'</option>';
					}
				}
			}
			$optionsel = '';
			if($option == ''){
				$option =''; //'<option value="">No Leave Type</option>';
			}else{
				$optionsel ='<option value="">Select</option>';
				
			}
			
			return $optionsel.$option;
		}
		
		public function option_leave_type_array($company_id,$emp_id){
			$leave_type = $this->get_leave_type_full($company_id);
			$option = array();
			if($leave_type)
			{
				foreach($leave_type as $k=>$v)
				{
					### NOW CHECK OUR LEAVE SETTINGS VALUES
					$employment_type = array();
					$gender = array();
					$rank = array();
					if($v->leave_type_id){
						// employment type
						$employment_type = $this->check_employment_type($this->company_id,$v->leave_type_id);
						// for gender
						$gender = $this->check_gender($this->company_id,$v->leave_type_id);
						// rank
						$rank = $this->check_rank($this->company_id,$v->leave_type_id);
					}
					$check_lt = $this->check_valid_leave_type($this->company_id,$emp_id,$rank,$employment_type,$gender);
					if($check_lt){
						//$option .='<option value="'.$v->leave_type_id.'">'.$v->leave_type.'</option>';
						$option[$v->leave_type_id] = $v->leave_type;
					}
				}
			}
			
			return $option;
		}

		public function ajax_get_leave_entitlements($leave_type_id,$rank_id){
			$where = array('company_id'=>$this->company_id,'leave_type_id'=>$leave_type_id,'rank_id'=>$rank_id,'status'=>'Active');
			$select = array(
					'leave_type_id','leave_credits','no_of_leaves_credits_to_accrued','maximum_days_of_leave_per_application'
			);
			#$this->edb->select($select);
			$this->db->where($where);
			$sql = $this->edb->get('leave_entitlements_settings');
			$r = $sql->row();
			return $r;
		}
		
	
		public function ajax_get_leave_type($company_id,$leave_type_id){
			$w = array(
					"lt.company_id"=>$company_id,
					'lt.leave_type_id'=>$leave_type_id
			);
			$this->db->where($w);
		
			#	$this->db->join('leave_type_settings_leave_type_applicable_for AS app','app.leave_type_id = lt.leave_type_id','INNER');
			$q = $this->db->get("leave_type AS lt");
			$r = $q->row();
			return $r;
		}
		
		/* Leave Type
		* @param unknown_type $rank
		* @param unknown_type $company_id
		*/
		public function leave_type_settings($rank,$company_id){
			$w = array(
					"les.rank_id"=>$rank,
					"les.company_id"=>$company_id
			);
			$this->db->where($w);
			$this->db->join("leave_entitlements_settings AS les","les.leave_type_id = lt.leave_type_id","LEFT");
			$q = $this->db->get("leave_type AS lt");
			$r = $q->result();
			return ($r) ? $r: FALSE;
		}
		
		
		
		/**
		 * Employee Information Leave
		 * @param unknown_type $company_id
		 * @param unknown_type $account_id
		 * @param unknown_type $sort_by
		 */
		public function emp_info_leave($company_id,$account_id,$sort_by,$limit,$start,$count=false,$order_by=''){
	
			$s = array(
				"employee_leaves.leaves_id",
				"employee_leaves.emp_id",
				"employee_leaves.rank_id",
				"employee_leaves.leave_type_id",
				"employee_leaves.leave_credits",
				"employee_leaves.remaining_leave_credits",
				"employee_leaves.previous_leave_credits",
				"employee_leaves.as_of",
				"employee_leaves.company_id",
				"employee_leaves.leave_accrue",
				"employee_leaves.years_of_service",
				"employee_leaves.created_date",
				"employee_leaves.updated_date",
				"employee_leaves.created_by_account_id",
				"employee_leaves.updated_by_account_id",
				"employee_leaves.`status`",
			#	"employee_leaves.paid_leave",
				"employee_leaves.what_happen_to_unused_leave",
				"employee_leaves.provide_half_day_option",
				"employee_leaves.allow_to_apply_leaves_beyond_limit",
				"employee_leaves.exclude_holidays",
				"employee_leaves.exclude_weekends",
				"employee_leaves.consecutive_days_after_weekend_holiday",
				"employee_leaves.num_consecutive_days_after_weekend_holiday",
				"employee_leaves.days_before_leave_application",
				"employee_leaves.num_days_before_leave_application",
				"employee_leaves.consecutive_days_allowed",
				"employee_leaves.num_consecutive_days_allowed",
				"employee_leaves.as_of_date_created",
				"employee_leaves.effective_date",
				"lt.leave_type",
				"employee_leaves.effective_date",
			#	"employee_leaves.effective_start_date_by", # SA LEAVE SETTINGS TA KOHA ANI
			#	"employee_leaves.effective_start_date", # LEAVE SETTING STA KOHA ANI
					"lt.effective_start_date_by",
					"lt.effective_start_date",
				"lt.required_documents",
			#	"employee_leaves.start_of_accrual",  #hide na sad kay wala na pud gamit daw
			#	"employee_leaves.start_of_accrual_day" #hide na sad kay wala na pud gamit daw
					"lt.start_of_accrual",
					"lt.start_of_accrual_day",
					"lt.accrual_period",
					"ep.date_hired",
					"lt.effective_start_date_by",
					"lt.paid_leave",
					'lt.leave_units',
					'lt.accrual_schedule',
					'lt.start_of_accrual_month',
					'employee_leaves.existing_leave_used_to_date'
					
			);
			$select2 = array('lt.created_date AS lt_created_date');
			$this->db->select($select2);
			$where = array(
				'employee_leaves.company_id' => $company_id,
				'accounts.account_id'	=> $account_id,
				'employee.status'		=> 'Active',
				'accounts.user_type_id' => '5',
				'employee_leaves.status'=>'Active',
				'lt.status'=>'Active'
			);
			$this->edb->where($where);
			/*$this->edb->join('rank AS r','r.rank_id = employee_leaves.rank_id','INNER');*/
			$this->edb->join('leave_type AS lt','lt.leave_type_id = employee_leaves.leave_type_id','INNER');
			#$this->edb->join('leave_entitlements_settings AS les','les.leave_type_id = employee_leaves.leave_type_id AND les.rank_id = employee_leaves.rank_id','INNER');
			$this->edb->join('employee','employee.emp_id = employee_leaves.emp_id','INNER');
			$this->edb->join('accounts','accounts.account_id = employee.account_id','INNER');
			$this->edb->join('leave_type','leave_type.leave_type_id = employee_leaves.leave_type_id','INNER');
			$this->edb->join("employee_payroll_information AS ep","ep.emp_id = employee.emp_id","INNER");
			/* updated sep 7 2016 */
			$this->edb->join('rank AS r','r.rank_id = ep.rank_id','INNER');
			/* end updated sep 7 2016 */
			$this->edb->select($s);
			$this->db->group_by('employee_leaves.leaves_id');
		
			if($order_by == ''){
				$order_by = 'asc';
			}else{
				if($order_by == 'desc'){
					$order_by = 'desc';
				}
			}
			
			if($sort_by != ""){
				if($sort_by == "name"){
					$sort_by = "employee.first_name";
					$this->edb->order_by($sort_by,$order_by);
				}elseif($sort_by == "payroll_group"){
					$sort_by = "p.name";
					$this->db->order_by($sort_by,$order_by);
				}elseif($sort_by == "rank_name"){
					$sort_by = "r.rank_name";
					$this->db->order_by($sort_by,$order_by);
				}elseif($sort_by == "leave_type"){
					$sort_by = "lt.leave_type";
					$this->db->order_by($sort_by,$order_by);
				}elseif($sort_by == "les.leave_credits"){
					$sort_by = "les.leave_credits";
					$this->db->order_by($sort_by,$order_by);
				}elseif($sort_by == 'balance'){
					$sort_by = "employee_leaves.remaining_leave_credits";
					$this->db->order_by($sort_by,$order_by);
				}elseif($sort_by == 'years_of_service'){
					$sort_by = "employee_leaves.years_of_service";
					$this->db->order_by($sort_by,$order_by);
				}elseif($sort_by == 'leave_accrue'){
					$sort_by = "employee_leaves.leave_accrue";
					$this->db->order_by($sort_by,$order_by);
				}elseif($sort_by == 'as_of'){
					$sort_by = "employee_leaves.as_of";
					$this->db->order_by($sort_by,$order_by);
				}
			}
		
			$q = '';
			if($count == false){
				$q = $this->edb->get('employee_leaves',$limit,$start);
				$r = $q->result();
			
				return $r;
			}else{
				$q = $this->edb->get('employee_leaves');
				$r = $q->num_rows();
				return $r;
			}
		
		}
		
		/**
		 * Get Employee List
		 * @param unknown_type $company_id
		 */
		public function get_employees($company_id){
			$w = array(
				"e.company_id"=>$company_id,
				"a.user_type_id"=>"5",
				"e.status"=>"Active"
			);
			$this->db->where($w);
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$q = $this->edb->get("employee AS e");
			$r = $q->result();
			return ($r) ? $r : FALSE ;
		}
		
		
		public function pack_auto_reset_sheets($company_id,$date_compare = "2015-1-12"){
			if($company_id){
				$where = array(
					'company_id'=>$company_id,
					'status'=>'Active'
				);
				$leave_shit = get_table_info_all('leave_type', $where);
				if($leave_shit) {
					foreach($leave_shit as $k=>$v):
						$entitlement_type = $v->entitlement_type;
						$last_years_leave_balance = 3; #$get_employee_leaves->last_years_leave_balance;
						$accrual_schedule = $v->accrual_schedule;
						
						if($v->what_happen_to_unused_leave == 'convert to cash'){
							$leave_type_id = $v->leave_type_id;
							$where_el = array(
								'leave_type_id'	=>$leave_type_id,
								'company_id'	=> $company_id,
								'status'		=> 'Active'
							);
							$get_employee_leaves = get_table_info('employee_leaves', $where_el);
							if($get_employee_leaves) {
								$balance = $get_employee_leaves->remaining_leave_credits;
								$leave_credits_per_year = $v->leave_credits_per_year;
								if($entitlement_type == 'yearly') { ## YEARLY
									$year = date("Y",strtotime($date_compare));
									$as_of_year = date("Y",strtotime($get_employee_leaves->as_of));
									$as_of = $get_employee_leaves->as_of;
									if($year > $as_of_year) {
										$year_plus = $as_of_year + 1;
										$field_uel = array(
											'as_of'						=> date("Y-m-d",strtotime($year_plus."-1-1")),
											'remaining_leave_credits'	=> $leave_credits_per_year,
											'leave_credits'				=> $leave_credits_per_year,
											'leave_accrue'				=> ''
										);
										$where_uel = array(
										'leave_type_id'		=> $leave_type_id,
											'company_id'	=> $company_id,
											'status'		=> 'Active'
										);
										eupdate("employee_leaves",$field_uel,$where_uel);
									}
								}elseif($entitlement_type == 'accrual') { ### ACCRUAL 
									$lt_leave_credits_accrual = $v->leave_credits_accrual;
									$accrual_period = $v->accrual_period; # by_weekly, by_monthly, by_quarter, by_bi_yearly, by_yearly
									$eremaining_leave_credits = $get_employee_leaves->remaining_leave_credits;
									
									$as_of = date("Y-m-d",strtotime($get_employee_leaves->as_of));
									$next_as_of_period = '';
									$today = date("Y-m-d");
									
									if($accrual_schedule == 'accrue_at_beginning') {
										if($accrual_period == 'by_weekly'){
											$as_of = date("Y-m-d",strtotime($as_of."+7 days"));
										}elseif($accrual_period == 'by_monthly'){
											$as_of = date("Y-m-d",strtotime($as_of."+31 days"));
										}elseif($accrual_period == 'by_quarter'){
											$as_of = date("Y-m-d",strtotime($as_of."+3 months"));
										}elseif($accrual_period == 'by_bi_yearly'){
											$as_of = date("Y-m-d",strtotime($as_of."+2 years"));
										}elseif($accrual_period == 'by_yearly'){
											$as_of = date("Y-m-d",strtotime($as_of."+1 years"));;
										}
									}else if($accrual_schedule =='accrue_at_end') {
										if($accrual_period == 'by_weekly'){
											$as_of = date("Y-m-d",strtotime($as_of."+13 days"));
										}elseif($accrual_period == 'by_monthly'){
											$as_of = date("Y-m-d",strtotime($as_of."+61 days"));
										}elseif($accrual_period == 'by_quarter'){
											$as_of = date("Y-m-d",strtotime($as_of."+6 months"));
										}elseif($accrual_period == 'by_bi_yearly'){
											$as_of = date("Y-m-d",strtotime($as_of."+4 years"));
										}elseif($accrual_period == 'by_yearly'){
											$as_of = date("Y-m-d",strtotime($as_of."+2 years"));;
										}
									}
									
									$leave_accrue = $get_employee_leaves->leave_accrue;
									$accrue_plus_credits = $lt_leave_credits_accrual + $leave_accrue; 
									$el_leave_credits = $get_employee_leaves->leave_credits;
									if($today == $as_of) { ## KONG ANG TODAY OG AS OF KAY SAME OG DATE MAG PUNO NATA SA ACCRUED
										$field_uel = array(
											'as_of'			=> $as_of,
											#'remaining_leave_credits'=> '55',
											#'leave_credits'=>$leave_credits_per_year,
											'leave_accrue'	=> $accrue_plus_credits,
											'remaining_leave_credits'=>$eremaining_leave_credits + $el_leave_credits
										);
										$where_uel = array(
											'leave_type_id'	=> $leave_type_id,
											'company_id'	=> $company_id,
											'status'		=> 'Active'
										);
										eupdate("employee_leaves",$field_uel,$where_uel);
									}
									#echo last_query();
								}
							}
						#################################################### ACCRUED !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!@@@@@@@@@@@@@@@@@@@@@@@@
						}else if($v->what_happen_to_unused_leave == 'accrue to next period'){ ##################### ACCRUED !!!!!!!!!!!!!!!!!!!!!
							$leave_type_id = $v->leave_type_id;
							$where_el = array(
									'leave_type_id'	=>$leave_type_id,
									'company_id'	=> $company_id,
									'status'		=> 'Active'
							);
							$get_employee_leaves = get_table_info('employee_leaves', $where_el);
							if($get_employee_leaves) {
								$balance = $get_employee_leaves->remaining_leave_credits;
								$leave_credits_per_year = $v->leave_credits_per_year;
								
								if($entitlement_type == 'yearly') { ## YEARLY
									$year = date("Y",strtotime($date_compare));
									$as_of_year = date("Y",strtotime($get_employee_leaves->as_of));
									$as_of = $get_employee_leaves->as_of;
									$employee_leave_accrue = $get_employee_leaves->leave_accrue;
									$balance_leave = $get_employee_leaves->remaining_leave_credits;
									
									if($year > $as_of_year) {
										$year_plus = $as_of_year + 1;
										$field_uel = array(
												'as_of'						=> date("Y-m-d",strtotime($year_plus."-1-1")),
												'remaining_leave_credits'	=> $balance_leave + $leave_credits_per_year,
												'leave_credits'				=> $leave_credits_per_year,
												'leave_accrue'				=> $leave_credits_per_year + $employee_leave_accrue
										);
										$where_uel = array(
												'leave_type_id'	=> $leave_type_id,
												'company_id'	=> $company_id,
												'status'		=> 'Active'
										);
										eupdate("employee_leaves",$field_uel,$where_uel);
									}
								}elseif($entitlement_type == 'accrual') { ### ACCRUAL
									$lt_leave_credits_accrual = $v->leave_credits_accrual;
									$accrual_period = $v->accrual_period; # by_weekly, by_monthly, by_quarter, by_bi_yearly, by_yearly
									$eremaining_leave_credits = $get_employee_leaves->remaining_leave_credits;
									$as_of = date("Y-m-d",strtotime($get_employee_leaves->as_of));
									$next_as_of_period = '';
									$today = date("Y-m-d");
										
									if($accrual_schedule == 'accrue_at_beginning') {
										if($accrual_period == 'by_weekly'){
											$as_of = date("Y-m-d",strtotime($as_of."+7 days"));
										}elseif($accrual_period == 'by_monthly'){
											$as_of = date("Y-m-d",strtotime($as_of."+31 days"));
										}elseif($accrual_period == 'by_quarter'){
											$as_of = date("Y-m-d",strtotime($as_of."+3 months"));
										}elseif($accrual_period == 'by_bi_yearly'){
											$as_of = date("Y-m-d",strtotime($as_of."+2 years"));
										}elseif($accrual_period == 'by_yearly'){
											$as_of = date("Y-m-d",strtotime($as_of."+1 years"));
										}
									}else if($accrual_schedule =='accrue_at_end') {
										if($accrual_period == 'by_weekly'){
											$as_of = date("Y-m-d",strtotime($as_of."+13 days"));
										}elseif($accrual_period == 'by_monthly'){
											$as_of = date("Y-m-d",strtotime($as_of."+61 days"));
										}elseif($accrual_period == 'by_quarter'){
											$as_of = date("Y-m-d",strtotime($as_of."+6 months"));
										}elseif($accrual_period == 'by_bi_yearly'){
											$as_of = date("Y-m-d",strtotime($as_of."+4 years"));
										}elseif($accrual_period == 'by_yearly'){
											$as_of = date("Y-m-d",strtotime($as_of."+2 years"));
										}
									}
										
									$leave_accrue = $get_employee_leaves->leave_accrue;
									$accrue_plus_credits = $lt_leave_credits_accrual + $leave_accrue;
									$el_leave_credits = $get_employee_leaves->leave_credits;
									if($today == $as_of) { ## KONG ANG TODAY OG AS OF KAY SAME OG DATE MAG PUNO NATA SA ACCRUED
										$field_uel = array(
											'as_of'			=> $as_of,
											#'remaining_leave_credits'=> '55',
											#'leave_credits'=>$leave_credits_per_year,
											'leave_accrue'	=> $accrue_plus_credits,
											'remaining_leave_credits'=>$eremaining_leave_credits + $el_leave_credits
										);
										$where_uel = array(
											'leave_type_id'	=> $leave_type_id,
											'company_id'	=> $company_id,
											'status'		=> 'Active'
										);
										eupdate("employee_leaves",$field_uel,$where_uel);
									}
									#echo last_query();
								}
							}
						}
					endforeach;
				}
			}else{
				return false;
			}
		}
		
		public function s($company_id){
			$where = array(
				'company_id'=>$company_id	
			);
			$emp_leaves = get_table_info_all('employee_leaves', $where);
			if($emp_leaves){
				foreach($emp_leaves as $k=>$v){
					$where_his = array(
						'leaves_id'=>$v->leaves_id,
						'emp_id'=>$v->emp_id,
						'company_id'=>$company_id
					);
					$get_history = get_table_info('employee_leaves_history', $where_his);
					if(!$get_history){
						$field_his = array(
							'leaves_id'=>$v->leaves_id,
							'emp_id'=>$v->emp_id,
							'rank_id'=>$v->rank_id,
							'leave_type_id'=>$v->leave_type_id,
							'leave_credits'=>$v->leave_credits,
							'remaining_leave_credits'=>$v->remaining_leave_credits,
							'previous_leave_credits'=>$v->previous_leave_credits,
							'as_of'=>$v->as_of,
							'company_id'=>$company_id,
							'leave_accrue'=>$v->leave_accrue,
							'years_of_service'=>$v->years_of_service,
							'created_date'=>$v->created_date,
							'updated_date'=>$v->updated_date,
							'created_by_account_id'=>$v->created_by_account_id,
							'updated_by_account_id'=>$v->updated_by_account_id,
							'status'=>$v->status,
							'paid_leave'=>$v->paid_leave,
							'what_happen_to_unused_leave'=>$v->what_happen_to_unused_leave,
							'provide_half_day_option'=>$v->provide_half_day_option,
							'allow_to_apply_leaves_beyond_limit'=>$v->allow_to_apply_leaves_beyond_limit,
							'exclude_holidays'=>$v->exclude_holidays,
							'exclude_weekends'=>$v->exclude_weekends,
							'consecutive_days_after_weekend_holiday'=>$v->consecutive_days_after_weekend_holiday,
							'num_consecutive_days_after_weekend_holiday'=>$v->num_consecutive_days_after_weekend_holiday,
							'days_before_leave_application'=>$v->days_before_leave_application,
							'num_days_before_leave_application'=>$v->num_days_before_leave_application,
							'consecutive_days_allowed'=>$v->consecutive_days_allowed,
							'num_consecutive_days_allowed'=>$v->num_consecutive_days_allowed,
							'last_years_leave_balance'=>$v->last_years_leave_balance,
							'last_years_date'=>$v->last_years_date
						);
						esave('employee_leaves_history',$field_his);
					}
				}
			}
		}
		
		
		/** VERSION #3 START HERE **/
		
		/**
		 * GET OUR PENDING APPROVAL THIS LEAVE YEAR
		 * @param int $leave_type_id
		 * @param int $employee_id
		 * @return object
		 */
		public function get_pending_approval_leaves($leave_type_id,$employee_id,$type="pending"){
			$year = date("Y"); #YEAR TODAY
			#$year = '2014';
			if($leave_type_id && $employee_id){
				$where = array(
					'emp_id'=>$employee_id,
					'company_id'=>$this->company_id,
					'status'=>'Active',
					'year(date_filed)'=>$year,
					'leave_type_id'=>$leave_type_id
				);
				
				if($type == 'pending'){
					$where['leave_application_status'] = 'pending';
				}else if($type =='approve'){
					$where['leave_application_status'] = 'approve';
				}
				
				$this->db->where($where);
				$select = array(
					'sum(total_leave_requested) AS total_request'
				);
				$this->db->select($select);
				$q = $this->db->get('employee_leaves_application');
				$result = $q->row();
				return $result;
			}else{
				return false;
			}
		}
		
		
		public function update_employee_leaves($company_id,$emp_id,$leave_type_id,$total_leave_requested,$employee_leaves_application_id) {
			if(is_numeric($company_id) && is_numeric($emp_id)) {
				# get employee leaves first
				$query= $this->db->get_where("employee_leaves",array("company_id"=>$company_id,"leave_type_id"=>$leave_type_id,"emp_id"=>$emp_id,"status"=>"Active"));
				$row = $query->row();
				$query->free_result();
				if($row) {
					$leave_credits = $row->leave_credits; 				# LEAVE CREDITS
					$previous_credits = $row->previous_leave_credits; 	# PREVIOUS CREDITS
					$remaining_credits = $row->remaining_leave_credits;	# REMAINING CREDITS
					#$check_remaining = $remaining_credits == NULL || $remaining_credits ="" ? $leave_credits : $remaining_credits;
					$leave_type_info = $this->get_leave_type_info($leave_type_id, $company_id);
						
					$check_remaining = $remaining_credits == NULL ?  $leave_type_info->leave_credits : $remaining_credits;
						
					$remaining = floatval($check_remaining) - floatval($total_leave_requested);
					$check_previous_credits = $previous_credits !="" ? $previous_credits : $leave_credits;
		
					$result_previous = floatval($check_previous_credits) - floatval($total_leave_requested);
					if($result_previous < 0) { # IF LESS THAN SA PREVIOUS
						$result_previous = floatval($check_previous_credits);
					}
					$credited_value  = 0;
					$non_credited = 0;
					# get credited
					if($check_remaining > 0) { #remaining > 3
						#gikuha total leave kong 5 , employee - 3 remainig - 4
						$credited_value_formula = floatval($check_remaining) - floatval($total_leave_requested);
						if($credited_value_formula <= 0){
							$credited_value = floatval($check_remaining);
							$non_credited = abs($credited_value_formula);
						}else{
							$credited_value = floatval($total_leave_requested);
							$non_credited = 0;
						}
					}else{
						$non_credited = $total_leave_requested;
					}
					# end credited
					$where = array(
					"company_id" 		=> $company_id,
					"emp_id"				=> $emp_id,
					"leave_type_id" 	=> $leave_type_id
					);
					$field = array(
							"remaining_leave_credits"	=> $remaining,
							"previous_leave_credits"		=> $result_previous
					);
					$this->db->update("employee_leaves",$field,$where);
						
					// check payroll period >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
					$check_payroll_period = $this->check_payroll_period($company_id);
						
					$idates_now = idates_now();
					if($check_payroll_period != FALSE){
						$start_date = $this->check_leave_application($employee_leaves_application_id);
						$payroll_period = $check_payroll_period->payroll_period;
						$period_from = $check_payroll_period->period_from;
						$period_to = $check_payroll_period->period_to;
						$datenow = date("Y-m-d");
		
						if(strtotime($period_from) <= strtotime($start_date) && strtotime($start_date) <= strtotime($payroll_period)){
							if(strtotime($period_to) < strtotime($datenow) && strtotime($datenow) <= strtotime($payroll_period)) $idates_now = $period_to." ".date("H:i:s");
						}
					}
					// end check payroll period >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						
					# UPDATES THE EMPLOYEE LEAVE APPLICATIONS
					$fields_ela = array(
						"leave_application_status" => "approve",
						"approval_date"	=>  $idates_now,
						"credited"	=> $credited_value,
						"non_credited"=> $non_credited
					);
					$where_ela = array(
							"employee_leaves_application_id"=>$employee_leaves_application_id,
							"company_id"=>$company_id
					);
					$this->update_field("employee_leaves_application",$fields_ela,$where_ela);
					# END UPDATES THE EMPLOYEE LEAVE APPLICATIONS
					$we = number_format($check_remaining,3) .'-'. number_format($total_leave_requested,3);
					return array("sumsa"=> $check_remaining."- ".$total_leave_requested."REMAINING".$remaining_credits."remaining val".$remaining."credited".$credited_value."calculation".$we);
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		
		/**
		 * Update fields
		 * @param string $database
		 * @param array $field
		 * @param array $where
		 * @return boolean
		 */
		public function update_field($database,$field,$where){
			$this->db->where($where);
			$this->db->update($database,$field);
			return $this->db->affected_rows();
		}
		
		/**
		 * Check leave applications
		 * @param unknown_type $employee_leaves_application_id
		 */
		public function check_leave_application($employee_leaves_application_id){
			$w = array("employee_leaves_application_id"=>$employee_leaves_application_id);
			$this->db->where($w);
			$q = $this->db->get("employee_leaves_application");
			$r = $q->row();
			return ($q->num_rows() > 0) ? $r->date_start : FALSE ;
		}
		
		/**
		 * Check Payroll Period
		 * @param unknown_type $val
		 */
		public function check_payroll_period($company_id){
			$ww = array("company_id"=>$company_id);
			$this->db->where($ww);
			$qq = $this->db->get("payroll_period");
			$rr = $qq->row();
			return ($qq->num_rows() > 0) ? $rr : FALSE ;
		}
		
		/**
		 * GET LEAVE TYPE INFOrmations
		 * @param int $leave_type_id
		 * @param int $company_id
		 * @return object
		 */
		public function get_leave_type_info($leave_type_id, $company_id){
			$where = array(
					"leave_type_id" => $leave_type_id,
					"company_id" => $company_id
			);
			$this->db->where($where);
			$query = $this->db->get("leave_type");
			$row = $query->row();
			return ($row) ? $row : false;
		}
		
		/**
		 * DELETE EMPLOYEE LEAVES
		 * @param int $emp_id
		 * @param int $leaves_id
		 * @return boolean
		 */
		public function delete_employee_leaves($emp_id,$leaves_id){
			if($leaves_id){
				foreach($leaves_id as $key=>$val){
					
					$where = array(
							'emp_id'=>$emp_id,
							'leaves_id'=>$val,
							'company_id'=>$this->company_id
					);
					
					$q = get_table_info('employee_leaves', $where);
					if($q){
						$leave_type_id = $q->leave_type_id;
						$where2 = array(
							'emp_id'=>$emp_id,
							'leave_type_id'=>$leave_type_id,
								'company_id'=>$this->company_id
						);
							
						$this->db->delete('employee_leave_history',$where2);
						
						$where = array(
							'emp_id'=>$emp_id,
							'leaves_id'=>$val,
							'company_id'=>$this->company_id
						);
						edelete('employee_leaves',$where);
					}
				}
				return true;
			}else{
				return false;
			}
		}
		
		public function employee_leave_apps($emp_id,$sort_by,$limit,$start,$count=false,$order_by=''){
			$where = array(
				'ela.emp_id'=>$emp_id,
				'ela.status'=>'Active',
				'ela.company_id'=>$this->company_id,
				'lt.status'=>'Active',
				
			);
			$this->db->where('ela.flag_parent IS NOT NULL');
			$where_not_pending = array(
					'ela.leave_application_status !='=>'pending'
			);
			$this->db->where($where_not_pending);
			$this->edb->where($where);
			if($order_by == ''){
				$order_by = 'asc';
			}else{
				if($order_by == 'desc'){
					$order_by = 'desc';
				}
			}
			
			if($sort_by != ""){
				$sort_array = array('lt.leave_type','ela.date_filed','ela.date_start','ela.total_leave_requested'
				);
				if(in_array($sort_by,$sort_array)){
					$this->db->order_by($sort_by,$order_by);
				}
			}else{
				$this->db->order_by('ela.date_filed','desc');
			}
			$this->db->group_by('ela.employee_leaves_application_id');
			$this->edb->join('leave_type AS lt','lt.leave_type_id = ela.leave_type_id','INNER');
			if($count == false){
				$q = $this->edb->get('employee_leaves_application AS ela',$limit,$start);
				$r = $q->result();
				return $r;
			}else{
				$q = $this->edb->get('employee_leaves_application AS ela');
				$r = $q->num_rows();
				return $r;
			}
		}
		
		/**
		 * DELETE EMPLOYEE LEAVES
		 * @param int $emp_id
		 * @param int $leaves_id
		 * @return boolean
		 */
		public function delete_employee_leaves_applied($emp_id,$employee_leaves_application_id){
			if($employee_leaves_application_id){
				foreach($employee_leaves_application_id as $key=>$val){
					
					$history_can_delete = $this->history_can_be_deleted($emp_id,$val);
					if($history_can_delete){
						## ADDIOTIONAL EDELETE ANG EMPLOYEE LEAVES 7 1 2016
						$where = array(
							'emp_id'=>$emp_id,
							'employee_leaves_application_id'=>$val,
							'company_id'=>$this->company_id,
							'leave_application_status'=>'approve'
						);
						$this->db->where($where);
						$get_ela= $this->db->get('employee_leaves_application');
						$check_emp_leave_app = $get_ela->row();
						
						$x = $get_ela->free_result();
						## GET APPLICATION LEAVE IF IS VALID
						if($check_emp_leave_app){
							
							// add payroll cron
							payroll_cronjob_helper($type='leave_application', $check_emp_leave_app->shift_date, $emp_id, $this->company_id); 
							$credited = $check_emp_leave_app->credited;
							$leave_type_id = $check_emp_leave_app->leave_type_id;
							$total_leave_requested = $check_emp_leave_app->total_leave_requested !='' ? number_format($check_emp_leave_app->total_leave_requested,3, '.', '') : '';
						
							if($credited > 0){
						
								$gelhl = $this->get_employee_leave_history_latest($emp_id,$leave_type_id);
								
								if($gelhl){
								//	echo '<br />na igo sa history';
									$prev = $gelhl->previous_period_leave_balance ? $gelhl->previous_period_leave_balance : 0;
									$total_previous_period_leave_balance = $prev + $credited;
									$euli_elh = array(
											'emp_id'=>$emp_id,
											'company_id'=>$this->company_id,
											'date'=>date("Y-m-d"),
											'status'=>'Active',
											'leave_type_id'=>$leave_type_id,
											'previous_period_leave_balance'=>$total_previous_period_leave_balance,
											'scenario'=>'module from workforce leave - gi uli na pud ang prev('.$prev.') + credited ('.$credited.')',
									);
									esave('employee_leave_history',$euli_elh);
								}
								
								$el_orign_leaves = array(
									'leave_type_id'	=> $leave_type_id,
									'company_id'	=> $this->company_id,
									'emp_id'		=> $emp_id,
									'status'		=> 'Active'
								);
								$get_employee_leaves = get_table_info('employee_leaves',$el_orign_leaves);
								if($get_employee_leaves) {
									$el_remaining_leaves = $get_employee_leaves->remaining_leave_credits !='' ? number_format($get_employee_leaves->remaining_leave_credits,3, '.', '') : '';
									$field_el = array(
										'remaining_leave_credits'=>$total_leave_requested + $el_remaining_leaves
									);
									$this->db->where($el_orign_leaves);
									$this->db->update('employee_leaves',$field_el);
								
								}
							}else{
								# $tr = '1.5'; total requested
								# $rb = '-6'; nya e uli ang remaining balance -6
								# total result kay  -4.5
								
								$el_orign_leaves = array(
									'leave_type_id'	=> $leave_type_id,
									'company_id'	=> $this->company_id,
									'emp_id'		=> $emp_id,
									'status'		=> 'Active'
								); 
								
								$get_employee_leaves = get_table_info('employee_leaves',$el_orign_leaves);
								
								if($get_employee_leaves) {
									$el_remaining_leaves = $get_employee_leaves->remaining_leave_credits !='' ? number_format($get_employee_leaves->remaining_leave_credits,3, '.', '') : '';
									$field_el = array(
										'remaining_leave_credits'=> $el_remaining_leaves + $total_leave_requested 
									);
									$this->db->where($el_orign_leaves);
									$this->db->update('employee_leaves',$field_el);
								}
							}
						}
						## END ADDITIOANAL DELETE EMPLOYEE LEAVES 7 1 2016
						
						$where = array(
								'emp_id'=>$emp_id,
								'employee_leaves_application_id'=>$val,
								'company_id'=>$this->company_id
						);
						edelete('employee_leaves_application',$where);
						
						$where_child = array(
								'emp_id'=>$emp_id,
								'leaves_id'=>$val,
								'company_id'=>$this->company_id
						);
						edelete('employee_leaves_application',$where_child);
				
					}
					
					/** 
					 	kong rejected gali august 31 2016
					*/
					$where_rej = array(
						'emp_id'=>$emp_id,
						'employee_leaves_application_id'=>$val,
						'company_id'=>$this->company_id,
						'leave_application_status'=>'reject'
					);
					$this->db->where($where_rej);
					$get_ela_rej = $this->db->get('employee_leaves_application');
					$check_emp_leave_app_rej = $get_ela_rej->row();
						
					$x = $get_ela_rej->free_result();
					## GET APPLICATION LEAVE IF IS VALID
					if($check_emp_leave_app_rej){
						$where_irej = array(
							'emp_id'=>$emp_id,
							'employee_leaves_application_id'=>$val,
							'company_id'=>$this->company_id
						);
						edelete('employee_leaves_application',$where_irej);
						
						$where_child_irej = array(
							'emp_id'=>$emp_id,
							'leaves_id'=>$val,
							'company_id'=>$this->company_id
						);
						edelete('employee_leaves_application',$where_child_irej);
					}
					/**  end august 31 2016 */
				}
				return true;
			}else{
				return false;
			}
		}
		
		
		
		/**
		 * LISTING TO LEAVE HISTORY
		 * @param unknown $sort_by
		 * @param unknown $limit
		 * @param unknown $start
		 * @param string $count
		 * @param string $order_by
		 * @return unknown
		 *												
		 */
		public function employee_leave_apps_list($sort_by,$limit,$start,$count=false,$order_by='',$name="",$date_start="",$date_end=""){
			$where = array(
					'ela.status'=>'Active',
					'ela.company_id'=>$this->company_id,
					'lt.status'=>'Active',
				/*	'ela.leave_application_status'=>'approve',*/
					'e.status'=>'Active',
					'a.deleted'=>'0',
					'a.user_type_id' => '5'
			);
			
			if($name == ""){
				$where["ep.employee_status"] = "Active";
			}
			
			$this->edb->where($where);
			
			if($date_start !="" && $date_end !=""){
				$where_dates = array(
						"CAST(ela.date_start as date) >="=>date("Y-m-d",strtotime($date_start)),
						"CAST(ela.date_end as date) <="=>date("Y-m-d",strtotime($date_end))
				);
				$this->db->where($where_dates);
			}
			
			
			/** listing of employee leaves **/
			$where_none_pending = array(
					'ela.leave_application_status !='=>'pending'
			);
			$this->db->where($where_none_pending);
			
			/** end of listing of employee leaves **/
			
			
			$this->db->where('ela.flag_parent IS NOT NULL'); 
			if($order_by == ''){
				$order_by = 'asc';
			}else{
				if($order_by == 'desc'){
					$order_by = 'desc';
				}
			}
		
			$konsum_key = konsum_key();
			if($name !==""){
				$name = str_replace("_"," ",$name);
				$name = $this->db->escape_like_str(stripslashes(clean_input($name)));
				if($name !==''){
					$where2  = array(
							'e.company_id' => $this->company_id,
							'e.status'	  => 'Active',
							'a.user_type_id' => '5',
							'a.deleted'=>'0',
							'e.status'=>'Active',
							'e.deleted'=>'0'
					);
					$this->db->where($where2);
					$this->db->where("CONVERT(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) USING latin1) LIKE '%".$name."%'", NULL, FALSE); // encrypt
					$this->db->or_where("CONVERT(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') USING latin1) =",$name);
					$this->db->where($where2);
				}
			}
				
			if($sort_by != ""){
				$sort_array = array('lt.leave_type','ela.date_filed','ela.date_start','ela.total_leave_requested',"e.first_name","e.last_name"
				);
				if(in_array($sort_by,$sort_array)){
					$this->db->order_by($sort_by,$order_by);
				}else{
					$this->db->order_by('ela.date_filed','desc');
				}
			}else{
				$this->db->order_by('ela.date_filed','desc');
			}
			$this->db->group_by('ela.employee_leaves_application_id');
			$this->edb->join('leave_type AS lt','lt.leave_type_id = ela.leave_type_id','INNER');
			if($count == false){
				
				$this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
				$this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
				$this->edb->join("employee_payroll_information AS ep","ep.emp_id = e.emp_id","INNER");
				$q = $this->edb->get('employee_leaves_application AS ela',$limit,$start);
				$r = $q->result();
				return $r;
			}else{
				$this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
				$this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
				$this->edb->join("employee_payroll_information AS ep","ep.emp_id = e.emp_id","INNER");
				$q = $this->edb->get('employee_leaves_application AS ela');
				
				$r = $q->num_rows();
				return $r;
			}
		}
		
		/**
		 * DELETE EMPLOYEE LEAVES
		 * @param int $emp_id
		 * @param int $leaves_id
		 * @return boolean
		 */
		public function delete_employee_leaves_applied_list($employee_leaves_application_id){
		
			if($employee_leaves_application_id){
				foreach($employee_leaves_application_id as $key=>$val){ 
					$ela_where = array(
						'employee_leaves_application_id'=>$val,
						'company_id'=>$this->company_id
					);
					$ela_details = get_table_info('employee_leaves_application',$ela_where);
					if($ela_details){
						$emp_id = $ela_details->emp_id;
					
						$history_can_delete = $this->history_can_be_deleted($emp_id,$val);
						if($history_can_delete){
							## ADDIOTIONAL EDELETE ANG EMPLOYEE LEAVES 7 1 2016
							$where = array(
								'emp_id'=>$emp_id,
								'employee_leaves_application_id'=>$val,
								'company_id'=>$this->company_id,
								'leave_application_status'=>'approve'
							);
							$this->db->where($where);
							$get_ela= $this->db->get('employee_leaves_application');
							$check_emp_leave_app = $get_ela->row();
								
							$x = $get_ela->free_result();
							## GET APPLICATION LEAVE IF IS VALID
							if($check_emp_leave_app){
								$credited = $check_emp_leave_app->credited;
								$leave_type_id = $check_emp_leave_app->leave_type_id;
								$total_leave_requested = $check_emp_leave_app->total_leave_requested !='' ? number_format($check_emp_leave_app->total_leave_requested,3, '.', '') : '';
								if($credited > 0){
									$el_orign_leaves = array(
											'leave_type_id'	=> $leave_type_id,
											'company_id'	=> $this->company_id,
											'emp_id'		=> $emp_id,
											'status'		=> 'Active'
									);
									$get_employee_leaves = get_table_info('employee_leaves',$el_orign_leaves);
									if($get_employee_leaves) {
										$el_remaining_leaves = $get_employee_leaves->remaining_leave_credits !='' ? number_format($get_employee_leaves->remaining_leave_credits,3, '.', '') : '';
										$field_el = array(
												'remaining_leave_credits'=>$total_leave_requested + $el_remaining_leaves
										);
										$this->db->where($el_orign_leaves);
										$this->db->update('employee_leaves',$field_el);
									
									}
								}
							}
							## END ADDITIOANAL DELETE EMPLOYEE LEAVES 7 1 2016
						
							$where = array(
								'emp_id'=>$emp_id,
								'employee_leaves_application_id'=>$val,
								'company_id'=>$this->company_id
							);
							edelete('employee_leaves_application',$where);
						
							$where_child = array(
								'emp_id'=>$emp_id,
								'leaves_id'=>$val,
								'company_id'=>$this->company_id
							);
							edelete('employee_leaves_application',$where_child);
						}
						
						/**
						 kong rejected gali august 31 2016
						 */
						$where_rej = array(
								'emp_id'=>$emp_id,
								'employee_leaves_application_id'=>$val,
								'company_id'=>$this->company_id,
								'leave_application_status'=>'reject'
						);
						$this->db->where($where_rej);
						$get_ela_rej = $this->db->get('employee_leaves_application');
						$check_emp_leave_app_rej = $get_ela_rej->row();
						
						$x = $get_ela_rej->free_result();
						## GET APPLICATION LEAVE IF IS VALID
						if($check_emp_leave_app_rej){
							$where_irej = array(
									'emp_id'=>$emp_id,
									'employee_leaves_application_id'=>$val,
									'company_id'=>$this->company_id
							);
							edelete('employee_leaves_application',$where_irej);
						
							$where_child_irej = array(
									'emp_id'=>$emp_id,
									'leaves_id'=>$val,
									'company_id'=>$this->company_id
							);
							edelete('employee_leaves_application',$where_child_irej);
						}
						/**  end august 31 2016 */
					}
					
					
				}
				return true;
			}else{
				return false;
			}
		}
		
		/**
		 * GET FUCKING LEAVE TYPE LISTINGSSHIT
		 * @param unknown $company_id
		 * @param unknown $leave_type_id
		 */
		public function ajax_get_leave_type_listing($company_id,$leave_type_id){
			$w = array(
					"lt.company_id"=>$company_id,
					'lt.leave_type_id'=>$leave_type_id
			);
			$this->db->where($w);
			$q = $this->db->get("leave_type AS lt");
			$r = $q->row();
			return $r;
		}
		
		/**
		 * MAO NI MO DISPLAY SA LEAVE DATA
		 * @param unknown $account_id
		 * @param unknown $leaves_id
		 */
		public function detail_leave_employee_data($emp_id,$leaves_id){
			if(is_numeric($emp_id) && is_numeric($leaves_id)){
				$where = array(
					'el.emp_id'=>$emp_id,
					'el.company_id'=>$this->company_id,
					'el.leaves_id'=>$leaves_id,
					'el.status'=>'Active',
					'lt.status'=>'Active'
				);
				$this->edb->where($where);
				
				
				$s = array(
						"el.leaves_id",
						"el.emp_id",
						"el.rank_id",
						"el.leave_type_id",
						"el.leave_credits",
						"el.remaining_leave_credits",
						"el.previous_leave_credits",
						"el.as_of",
						"el.company_id",
						"el.leave_accrue",
						"el.years_of_service",
						"el.created_date",
						"el.updated_date",
						"el.created_by_account_id",
						"el.updated_by_account_id",
						"el.`status`",
					#	"el.paid_leave",
						"el.what_happen_to_unused_leave",
						"el.provide_half_day_option",
						"el.allow_to_apply_leaves_beyond_limit",
						"el.exclude_holidays",
						"el.exclude_weekends",
						"el.consecutive_days_after_weekend_holiday",
						"el.num_consecutive_days_after_weekend_holiday",
						"el.days_before_leave_application",
						"el.num_days_before_leave_application",
						"el.consecutive_days_allowed",
						"el.num_consecutive_days_allowed",
						"el.as_of_date_created",
						"el.effective_date",
						"lt.leave_type",
						"el.effective_date",
						#	"employee_leaves.effective_start_date_by", # SA LEAVE SETTINGS TA KOHA ANI
						#	"employee_leaves.effective_start_date", # LEAVE SETTING STA KOHA ANI
						"el.effective_start_date_by",
						"el.effective_start_date",
						"lt.required_documents",
						#	"employee_leaves.start_of_accrual",  #hide na sad kay wala na pud gamit daw
						#	"employee_leaves.start_of_accrual_day" #hide na sad kay wala na pud gamit daw
						"lt.start_of_accrual",
						"lt.start_of_accrual_day",
						"lt.accrual_period",
						"ep.date_hired",
						"lt.paid_leave",
						'lt.leave_units',
						'lt.accrual_schedule',
						'lt.start_of_accrual_month',
						'el.existing_leave_used_to_date'
				);
				$select2 = array('lt.created_date AS lt_created_date');
				$this->db->select($select2);
				$this->edb->select($s);
				$this->edb->join('leave_type AS lt','lt.leave_type_id = el.leave_type_id','INNER');
				$this->edb->join("employee_payroll_information AS ep","ep.emp_id = el.emp_id","INNER");
				$this->edb->join("employee AS e","ep.emp_id = e.emp_id","INNER");
				$q = $this->edb->get('employee_leaves AS el');
				$r = $q->row();
				return $r;
			}else{
				return false;
			}
		}
		
		/** END VERSION #3 **/
		/** LEAVE HISTORY **/
		/**
		 * EMPLOYEE WHOSE ON LEAVE
		 * @param string $count
		 * @param string $today
		 * @param string $emp_id
		 * @param string $sort_by
		 * @param string $order_by
		 * @param string $start
		 * @param string $limit
		 */
		public function employee_whose_on_leave($count=false,$today ='',$emp_id = '',$sort_by='',$order_by='',$start = null,$limit= null){
			if($today == ''){
				$today = date("Y-m-d");
			}
			$where = array(
					'ela.status'=>'Active',
					'ela.company_id'=>$this->company_id,
					'lt.status'=>'Active',
					'ela.leave_application_status'=>'approve',
					'e.status'=>'Active',
					'a.deleted'=>'0',
					'a.user_type_id' => '5'
			);
			$this->edb->where($where);
			if($order_by == ''){
				$order_by = 'asc';
			}else{
				if($order_by == 'desc'){
					$order_by = 'desc';
				}
			}
			if($emp_id !==''){
				$where['ela.emp_id'] = $emp_id;
			}
		
			$konsum_key = konsum_key();
			$sql ="date('".$today."') BETWEEN date(ela.date_start) AND date(ela.date_end)";
			$this->db->where($sql);
			if($sort_by != ""){
				$sort_array = array('lt.leave_type','ela.date_filed','ela.date_start','ela.total_leave_requested',"e.first_name","e.last_name"
				);
				if(in_array($sort_by,$sort_array)){
					$this->db->order_by($sort_by,$order_by);
				}else{
					$this->db->order_by('ela.date_filed','asc');
				}
			}
			$this->db->group_by('ela.emp_id');
			$this->edb->join('leave_type AS lt','lt.leave_type_id = ela.leave_type_id','INNER');
			$array = array(
				'e.first_name','e.last_name','epi.payroll_group_id','a.profile_image','a.account_id','e.emp_id','epi.department_id'
			);
			$this->edb->select($array);
			$this->db->where('ela.flag_parent IS NOT NULL');
			if($count == false){
				
				$this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
				$this->edb->join('employee_payroll_information AS epi','e.emp_id=epi.emp_id','LEFT');
				$this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
				$q = $this->edb->get('employee_leaves_application AS ela',$limit,$start);
				$r = $q->result();
				return $r;
			}else{
				$this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
				$this->edb->join('employee_payroll_information AS epi','e.emp_id=epi.emp_id','LEFT');
				$this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
				$q = $this->edb->get('employee_leaves_application AS ela');
				$r = $q->num_rows();
				return $r;
			}
		}
		/** END LEAVE  HISTORY **/
		
		/**
		 * GET LEAVE USED TO DATE
		 * @param unknown $emp_id
		 * @param unknown $leave_type_id
		 */
		public function get_leave_used_to_date($emp_id,$leave_type_id,$year){
			if($emp_id){
				$where = array(
					'company_id'	=>$this->company_id,
					'emp_id'		=>$emp_id,
					"leave_type_id"	=>$leave_type_id,
					"leave_application_status"	=>'approve',
					"year(date_start)"=>$year
				);
				$this->db->select('sum(credited) as sum_credited');
				$this->db->where($where);
				$this->db->where("(flag_parent = 'yes' OR flag_parent = 'no')");
				$q_leave = $this->edb->get('employee_leaves_application');
			
				$q_row = $q_leave->row();
				$q_num_row = $q_leave->num_rows();
				$num_credited = 0;
				if($q_row){
					if($q_row->sum_credited > 0){
						$num_credited = number_format($q_row->sum_credited,3,".","");
					}
				}
				return $q_num_row ? $num_credited : 0;
			}else{
				return false;
			}
		}
		
		/**
		 * GET LEAVE PAYABLE CREDITS TO DATE
		 * @param int $emp_id
		 * @param int $leave_type_id
		 * @param year $year
		 * @return integer
		 */
		public function get_leave_payable_credits_to_date($emp_id,$leave_type_id,$year){
			if($emp_id){
				$where_lt = array(
					'leave_type_id'=>$leave_type_id,
					'status'=>'Active'
				);
				$leave_q = get_table_info('leave_type', $where_lt);
				if($leave_q){
					$what_happen_to_unused_leave = $leave_q->what_happen_to_unused_leave;
					
					if($what_happen_to_unused_leave == 'do nothing'){
						return 0;
					}else if($what_happen_to_unused_leave == 'accrue to next period'){	
						return 0;
					}else{
						if($leave_q->requires_leave_credits == 'yes'){
							$where = array(
								'company_id'	=>$this->company_id,
								'emp_id'		=>$emp_id,
								"leave_type_id"	=>$leave_type_id
									#		"year(date)"=>$year
							);
							$this->db->order_by('employee_leave_history_id','desc');
							$this->db->where($where);
							$q_leave = $this->edb->get('employee_leave_history AS elh',1);
							$q_row = $q_leave->row();
							$q_num_row = $q_leave->num_rows();
							return $q_row ? $q_row->previous_period_leave_balance : 0;
						}else{
							return "~";
						}
					}
				}
			}else{
				return false;
			}
		}
		
		/**
		 * EMPLOYEE LEAVE DETAILS INFO
		 * @param unknown $account_id
		 */
		public function employee_leave_details_info($account_id){
			$where = array(
				'e.status'=>'Active',
				'epi.status'=>'Active',
				'a.deleted'=>'0',
				'e.deleted'=>'0',
				'e.account_id'=>$account_id,
				'e.company_id'=>$this->company_id			
			);
			$select = array(
				'e.emp_id',
				'a.account_id',
				'e.company_id',
				'epi.date_hired'
			);
			$this->edb->select($select);
			$this->edb->where($where);
			$this->edb->join('employee_payroll_information AS epi','epi.emp_id=e.emp_id','INNER');
			$this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
			$q = $this->edb->get('employee AS e');
			$r = $q->row();
			return $r;
		}
	
		/**
		 * HAS EMPLOYEE LEAVE BUT CANCEL IT SO YOU  MUST RETURN THE LEAVES
		 * @param int $emp_id
		 * @param int $employee_leaves_application_id
		 */
		public function employee_leave_history_delete_restore($emp_id,$employee_leaves_application_id){
			if(is_numeric($company_id) && is_numeric($employee_leaves_application_id)) {
				$where = array(
					"company_id"		=> $company_id,
					"employee_leaves_application_id" => $this->db->escape_str($employee_leaves_application_id),
					"status"			=> "Active",
					"leave_application_status" => "approve"
				);
				$this->edb->where($where);
				$q = $this->edb->get('employee_leaves_application AS ela');
				$r = $q->row();
				$q->free_result();
				if($r) {
					
					$date_start = date("Y-m-d",strtotime($r->date_start));
					$date_end = date("Y-m-d",strtotime($r->date_end));
					
					$sql = 'SELECT * from draft_pay_runs AS dpr LEFT JOIN payroll_run_custom AS prc ON prc.draft_pay_run_id = dpr.draft_pay_run_id
							WHERE  dpr.view_status = "Closed" AND  prc.emp_id = "'.$emp_id.'" AND  dpr.company_id = "'.$this->company_id.'" 
							AND dpr.period_from <= "'.$date_start.'" AND 
							dpr.period_to >= "'.$date_end.'"';
					
					$credited_value = $r->credited_value;
					$non_credited = $r->non_credited;
					$leave_type_id = $r->$leave_type_id;
					
					$el_where = array(
						"company_id" 		=> $this->company_id,
						"emp_id"			=> $emp_id,
						"leave_type_id" 	=> $leave_type_id
					);
					$this->edb->where($el_where);
					$q_el = $this->edb->get('employee_leaves');
					$r_row = $q_el->row();
					$q_el->free_result();
					
					if($r_row) { 
						$remaining = $r_row->remaining + $credited_value;
						$result_previous = $r_row->result_previous + $leave_type_id; 
						$where = array(
							"company_id" 		=> $this->company_id,
							"emp_id"			=> $emp_id,
							"leave_type_id" 	=> $leave_type_id
						);
						$field = array(
							"remaining_leave_credits"	=> $remaining,
							"previous_leave_credits"	=> $result_previous
						);
						$this->db->update("employee_leaves",$field,$where);
						
					}
				}
			}	
		}
		
		/**
		 * HISTORY CAN BE DELETED
		 * @param int $emp_id
		 * @param int $employee_leaves_application_id
		 */
		public function history_can_be_deleted($emp_id,$employee_leaves_application_id){
			if(is_numeric($this->company_id) && is_numeric($employee_leaves_application_id)) {
				$where = array(
					"company_id"		=> $this->company_id,
					"employee_leaves_application_id" => $this->db->escape_str($employee_leaves_application_id),
					"status"			=> "Active",
					"leave_application_status !=" => "pending"
				);
				$this->db->where($where);
				$q = $this->edb->get('employee_leaves_application AS ela');
				$r = $q->row();
				$q->free_result();
				#echo last_query()." employee LEaves<Br /><br />";
				if($r) {
					$date_start = date("Y-m-d",strtotime($r->date_start));
					$date_end = date("Y-m-d",strtotime($r->date_end));
					
					/** SCENARIO 1 **/
					$dp_where = array(
						'dpr.view_status'	=>'Closed',
						'prc.emp_id'		=>$emp_id,
						'dpr.company_id'	=>$this->company_id
					#	'dpr.period_from <='=>$date_start,
					#	'dpr.period_to >='	=>$date_end
					);
					$this->db->select(array('dpr.view_status','dpr.period_from','dpr.period_to'));
					$this->db->where($dp_where);
					$this->db->join('payroll_run_custom AS prc','prc.draft_pay_run_id = dpr.draft_pay_run_id','LEFT');
					
					$q_drft = $this->db->get('draft_pay_runs AS dpr');
					$scene1 = $q_drft->result();
					$q_drft->free_result();
					
					$scene1_flag = false;
					if($scene1){
						foreach($scene1 as $k1){
							#$emp_schedule1 = range(strtotime($k1->period_from),strtotime($k1->period_to)); 
							#p($emp_schedule1);
							#$emp_logs1= range(strtotime($date_start),strtotime($date_end));
							#$intersect1 = array_intersect($emp_schedule1, $emp_logs1);
							#if($intersect1) $scene1_flag = TRUE; break;
							
							#if(strtotime($k1->period_from) >= strtotime($date_start) || strtotime($k1->period_to) <= strtotime($date_end)){
							#	$scene2_flag = TRUE; break;
							#}
							
							
							#if((strtotime($k1->period_from) >= strtotime($date_start) && strtotime($date_start) <= strtotime($k1->period_from)) || (strtotime($k1->period_to) <= strtotime($date_end) && strtotime($date_end) <= strtotime($k1->period_to))){
							#	$scene1_flag = TRUE; break;
							#}
							 
							$flag_date_array = array();
							$pfrom = $k1->period_from;
							$pto = $k1->period_to;
							
							while(strtotime($pfrom)<=strtotime($pto)){
								
								$flag_date_array[date("Y-m-d",strtotime($pfrom))] = $pfrom;
							
								if(isset($flag_date_array[$date_start])){ 
									$scene1_flag = true;
									break;
								} 
							
								if(isset($flag_date_array[$date_end])){
									$scene1_flag = true;
									break;
								} 
								$pfrom = date("Y-m-d",strtotime("+1 day",strtotime($pfrom)));
							} 
							
						}
					} 
					
					/** END SCENARIO 1 **/
					#echo last_query()." employee LEaves 1<Br /><br />";
					
					/** SCENARIO 2 **/
					$dbp_where2 = array(
						'dpr.view_status'	=>'Closed',
						'pp.emp_id'			=>$emp_id,
						'dpr.company_id'	=>$this->company_id
					#	'dpr.period_from <='=>$date_start,
					#	'dpr.period_to >='	=>$date_end
					);
					$this->db->where($dbp_where2);
					$this->db->select(array('dpr.company_id','dpr.period_from','dpr.period_to'));
					$this->db->join('payroll_payslip AS pp','pp.payroll_group_id = dpr.payroll_group_id AND pp.payroll_date = dpr.pay_period','LEFT');
					
					$q_drft2 = $this->db->get('draft_pay_runs AS dpr');
					$scene2 = $q_drft2->result();
					$q_drft2->free_result();
					#echo last_query()." employee LEaves2<Br /><br />";
					/** END SCENARIO 2 **/
					
					$scene2_flag = false;
					if($scene2){
						foreach($scene2 as $k2){ 
							#$emp_schedule = range(strtotime($k2->period_from), strtotime($k2->period_to));
							#$emp_logs = range(strtotime($date_start), strtotime($date_end));
							#$intersect = array_intersect($emp_schedule, $emp_logs);
							#if($intersect) $scene2_flag = TRUE; break;  
							
							#if(strtotime($k2->period_from) >= strtotime($date_start) || strtotime($date_end) <= strtotime($k2->period_to)){
							#	  $scene2_flag = TRUE; break;
							#}
							
							#if((strtotime($k2->period_from) >= strtotime($date_start) && strtotime($date_start) <= strtotime($k2->period_from)) || (strtotime($k2->period_to) <= strtotime($date_end) && strtotime($date_end) <= strtotime($k2->period_to))){
							#	$scene2_flag = TRUE; break;
							#}
							
							$flag_date_array2 = array();
							$pfrom2 = $k2->period_from;
							$pto2 = $k2->period_to;
								
							while(strtotime($pfrom2)<=strtotime($pto2)){ 
								$flag_date_array2[date("Y-m-d",strtotime($pfrom2))] = $pfrom2; 
								if(isset($flag_date_array2[$date_start])){
									$scene2_flag = true;
									break;
								} 
								if(isset($flag_date_array2[$date_end])){
									$scene2_flag = true;
									break;
								} 
								$pfrom2 = date("Y-m-d",strtotime("+1 day",strtotime($pfrom2)));
							} 
						}
					}
					
					if($scene1_flag){ # CONDITION 1
						#echo '1';
						return 0;
					}else if($scene2_flag){
						#echo '2';
						return 0;
					}else{
						return 1;
					}
				}else{
					return 0;
				}
			}else{
				return 0;
			}
		}
		
		
		/**
		 * HISTORY CAN BE DELETED
		 * @param int $emp_id
		 * @param int $employee_leaves_application_id
		 */
		public function history_can_be_reset_back($emp_id,$employee_leaves_application_id){
			if(is_numeric($this->company_id) && is_numeric($employee_leaves_application_id)) {
				$where = array(
						"company_id"		=> $this->company_id,
						"employee_leaves_application_id" => $this->db->escape_str($employee_leaves_application_id),
						"status"			=> "Active",
						"leave_application_status" => "approve"
				);
				$this->db->where($where);
				$q = $this->edb->get('employee_leaves_application AS ela');
				$r = $q->row();
				$q->free_result();
				#echo last_query()." employee LEaves<Br /><br />";
				if($r) {
					$date_start = date("Y-m-d",strtotime($r->date_start));
					$date_end = date("Y-m-d",strtotime($r->date_end));
						
					/** SCENARIO 1 **/
					$dp_where = array(
							'dpr.view_status'	=>'Closed',
							'prc.emp_id'		=>$emp_id,
							'dpr.company_id'	=>$this->company_id,
							'dpr.period_from <='=>$date_start,
							'dpr.period_to >='	=>$date_end
					);
					$this->db->where($dp_where);
					$this->db->join('payroll_run_custom AS prc','prc.draft_pay_run_id = dpr.draft_pay_run_id','LEFT');
					$q_drft = $this->db->get('draft_pay_runs AS dpr');
					$scene1 = $q_drft->row();
					echo last_query()." employee LEaves1<Br /><br />";
					$q_drft->free_result();
					/** END SCENARIO 1 **/
					#echo last_query()." employee LEaves 1<Br /><br />";
						
					/** SCENARIO 2 **/
					$dbp_where2 = array(
					'dpr.view_status'	=>'Closed',
					'pp.emp_id'			=>$emp_id,
					'dpr.company_id'	=>$this->company_id,
					'dpr.period_from <='=>$date_start,
					'dpr.period_to >='	=>$date_end
					);
					$this->db->where($dbp_where2);
					$this->db->join('payroll_payslip AS pp','pp.payroll_group_id = dpr.payroll_group_id','LEFT');
					$q_drft2 = $this->db->get('draft_pay_runs AS dpr');
					$scene2 = $q_drft2->row();
					echo last_query()." employee LEaves2<Br /><br />";
					/** END SCENARIO 2 **/
						
					if($scene1){ # CONDITION 1
						return 0;
					}else if($scene2){
						return 0;
					}else{
						$credited = $r->credited;
						$non_credited = $r->non_credited;
					}
				}else{
					return 0;
				}
			}else{
				return 0;
			}
		}
		
		/** added july 6 2016 **/
		/**
		 * GET LATEST EMPLOYEE HISTORY VALUE
		 * @param int $emp_id
		 * @param int $leave_type_id
		 * return object
		 */
		public function get_employee_leave_history_latest($emp_id,$leave_type_id){
			$where = array(
				'company_id'	=>$this->company_id,
				'emp_id'		=>$emp_id,
				'leave_type_id'	=>$leave_type_id
			);
			$this->db->where($where);
			$this->db->order_by('employee_leave_history_id','desc');
			$get = $this->db->get('employee_leave_history',1);
			$r = $get->row();
			return $r;
		}
		/** end  july 6 2016 **/
		
		
		/** added sep 20 2016 **/
		public function option_leave_type_existing($company_id,$emp_id){
			if(!is_numeric($emp_id)){
				return false;
			}
			$leave_type = $this->get_leave_type_full($company_id);
			$option = '';
			
			$leave_type_array_name = array();
			if($leave_type)
			{
				foreach($leave_type as $k=>$v)
				{
					### NOW CHECK OUR LEAVE SETTINGS VALUES
					$employment_type = array();
					$gender = array();
					$rank = array();
					if($v->leave_type_id){
						// employment type array(1,3,4,)
						$employment_type = $this->check_employment_type($this->company_id,$v->leave_type_id);
						// for gender
						$gender = $this->check_gender($this->company_id,$v->leave_type_id);
						// rank
						$rank = $this->check_rank($this->company_id,$v->leave_type_id);
					}
					$check_lt = $this->check_valid_leave_type($this->company_id,$emp_id,$rank,$employment_type,$gender);
					if($check_lt){  
						$where = array(
								'leave_type_id'=>$v->leave_type_id,
								'emp_id'=>$emp_id,
								'status'=>'Active'
						);
						$get_leave = get_table_info('employee_leaves', $where);
						if(!$get_leave){ 
							$leave_type_array_name[$v->leave_type_id] = $v->leave_type;
						}
					}
				}
			}
			
			return $leave_type_array_name;
		}
		
		
		/**
		 * generated accrual start
		 * @param string $accrual_schedule
		 * @param string $accrual_period
		 * @param int $emp_id
		 */
		public function generated_accrual_start($eval,$emp_id){
			if(is_numeric($emp_id) && $eval){
			$today_is = strtotime(date("Y-m-d"));
			$today = date("Y-m-d");
			$original = '';
			$day = $eval->start_of_accrual_day; 
			$date_start_of_accrual = '';
			$ewhere = array(
				'status'=>'Active',
				'emp_id'=>$emp_id,
				'company_id'=>$this->company_id
			);
			$epi  = get_table_info('employee_payroll_information',$ewhere);
			
			$date_hired =  '';
			if($epi){
				$date_hired  = $epi->date_hired;
			}
			/** end august 9 2016 **/
			 
			if($eval->accrual_schedule == 'Beginning of Accrual Period'){
				$st_month = date("m",strtotime($date_hired)); # KUHAON NATO ANG MONTH SA PAG HIRED NIYA
				$st_year = date("Y",strtotime($date_hired)); # KUHAON NATO ANG YEAR SA PAG HIRED NIYA
				if($eval->accrual_period == 'monthly'){
					$overall_year = date("Y-m-d",strtotime($st_year."-".$st_month."-1 +1 month"));
					 
					/** august 9 2016 **/
					$original = $overall_year;
					$generated_month = date("Y-m-d",strtotime(date("Y-m")."-1"));
					if($today_is > strtotime($overall_year)){
						if(strtotime($generated_month) <= $today_is){
							$overall_year = date("Y-m-d",strtotime($generated_month."+1 month"));
						}else{
							$overall_year = $generated_month;
						}
					}
					/** end august 9 2016 **/
					 
					$date_start_of_accrual = idates($overall_year);
				}else if($eval->accrual_period == 'quarterly'){
					$quarterly_emp = employee_quarterly_beginning($today,$date_hired);
					if($quarterly_emp){
						$original = $quarterly_emp['start_of_accrual'];
						$overall_year = $quarterly_emp['next_accrual_date'];
					}
					$date_start_of_accrual = idates($overall_year);
				}else if($eval->accrual_period == 'semi_annual'){
					$year = date("Y");
					$first_semi_annual_date = date("Y-m-d",strtotime($date_hired."+6 months"));
					$semi_beginnings = employee_next_semi_annual_beginning($today,$date_hired);
					 
					if($semi_beginnings){
						$original = $semi_beginnings['start_of_accrual'];
						$first_semi_annual_date = $semi_beginnings['next_accrual_date'];
					}
					$date_start_of_accrual = idates($first_semi_annual_date);
				}
				 
			}else if($eval->accrual_schedule == 'End of Accrual Period'){
				$st_month = date("m",strtotime($date_hired)); # KUHAON NATO ANG MONTH SA PAG HIRED NIYA
				$st_year = date("Y",strtotime($date_hired)); # KUHAON NATO ANG YEAR SA PAG HIRED NIYA
				if($eval->accrual_period == 'monthly'){
					$overall_year = date("Y-m-d",strtotime($st_year."-".$st_month."-1 +1 month"));
					 
					/** august 9 2016 **/
					$original = $overall_year;
					$last_end_month = date("t",strtotime($today_is));
					$generated_month = date("Y-m-d",strtotime(date("Y-m")."-{$last_end_month}"));
					if($today_is > strtotime($overall_year)){
						if(strtotime($generated_month) <= $today_is){
							$overall_year = date("Y-m-d",strtotime($generated_month."+1 month"));
						}else{
							$overall_year = $generated_month;
						}
					}
					/* end august 9 2016 **/
					 
					$date_start_of_accrual = idates($overall_year);
				}else if($eval->accrual_period == 'quarterly'){
					$st_quarterly_month = date("m",strtotime($date_hired));
			
					$ending_quar = employee_quarterly_ending($today,$date_hired);
					 
					$overall_year = "";
					if($ending_quar){
						$original = $ending_quar['start_of_accrual'];
						$overall_year = $ending_quar['next_accrual_date'];
			
					}
					$date_start_of_accrual = idates($overall_year);
				}else if($eval->accrual_period == 'semi_annual'){
					$ending = employee_next_semi_annual_ending($today,$date_hired);
					if($ending){
						$original = $ending['start_of_accrual'];
						$first_semi_annual_date = $ending['next_accrual_date'];
					}
			
					 
					$date_start_of_accrual = idates($first_semi_annual_date);
				}
			}else if($eval->accrual_schedule == 'Specific Date'){
				$spec_month = $eval->start_of_accrual_month;
				 
				$create_end_year = date("Y",strtotime($eval->created_date));
				$specific_dates = $create_end_year."-".$spec_month."-".$day;
				 
				/*** added on aug 9 **/
				$original = $specific_dates;
			
				$date_generated_specific = date("Y-m-d",strtotime($specific_dates));
				if($today_is > strtotime($date_generated_specific)){
					if(strtotime($date_generated_specific) <= $today_is){
						$specific_dates = date("Y-m-d",strtotime(date("Y-m")."-".$day."+1 month"));
					}else{
						$specific_dates = $specific_dates;
					}
				}
				/*** end added on aug 9 **/
				 
				$date_start_of_accrual = idates($specific_dates);
			}
			 
			if($eval->accrual_period == 'anniversary'){
				$anniv = date("Y-m-d",strtotime($date_hired."+1 year"));
				/* august 9 **/
				$original = $anniv;
				$y = date("Y");
				$date_hired_md = date("m-d",strtotime($date_hired));
				$per_annual = date("Y-m-d",strtotime($y."-".$date_hired_md));
			
				if($today_is >  strtotime($per_annual)){
					$per_annual = date("Y-m-d",strtotime($y."-".$date_hired_md."+1 year"));
				}
				/* end august 9 **/
				$date_start_of_accrual = idates($per_annual);
			
			}
			 
			if($eval->accrual_period == 'annual'){
				$next_annual = employee_next_annual_start($today = date("Y-m-d"),$date_hired);
			
				if($next_annual){
					$original = $next_annual['start_of_accrual'];
					$date_start_of_accrual = idates($next_annual['next_accrual_date']);
				}
			}
			 
			
			 
			/** END ADDED **/
			
			if(strtotime($original) == strtotime(date("Y-m-d",strtotime($date_start_of_accrual)))){
			
			}else{
				if($date_start_of_accrual !="~"){
					if($date_start_of_accrual !=''){
					#	echo '<span style="color:#1172ad;font-size:10px;display:block;"> Next (';
					#	echo $date_start_of_accrual;
					#	echo ')</span>';
					}
				}else{
					 
					#echo "~";
				}
			}
			
			
			return array('start_of_accrual'=>$original,'next_accrual'=>$date_start_of_accrual);
			}
			
		}
		
		function employee_next_annual_start_new($today,$date_hired) {
		    $today_specific = $today;
		    $today = date("Y-m-d",strtotime($today_specific));
		    $year = date("Y",strtotime($today_specific));
		    $e_datehired = $date_hired;
		    $date_hired = date("Y-m-d",strtotime($e_datehired));
		    $hired_year = date("Y",strtotime($date_hired));
		    $start_of_year_accrual = date("Y-m-d",strtotime("{$hired_year}-12-31 +1year"));
		    
		    $next_of_year_accrual = '';
		    $flag_annual = '';
		    
		    if(strtotime($today) >= strtotime($start_of_year_accrual)) {
		        $flag_annual = "<p> ang today ($today) > ($start_of_year_accrual)</p>";
		        //$next_of_year_accrual = date("Y-m-d",strtotime(date("Y",strtotime($today))."-12-31 +1year")); // old code ang accrual
		        $next_of_year_accrual = date("Y-m-d",strtotime(date("Y",strtotime($today))."-01-01 +1year"));
		    }
		    
		    $next_month_first_day = date("Y-m-d",strtotime($date_hired));
		    $is_today = "<p>
			What today is {$today} <br />
			human read (".idates($today).")<br />
			Date hired ($date_hired) <br />
			Start of Accrual Date ($start_of_year_accrual),<br />
			Next Accrual Date ($next_of_year_accrual)
			</p>";
		    
		    #echo $is_today;
		    #echo $flag_annual;
		    $result = array(
		        'today'=>$today,
		        'date_hired'=>$hired_year,
		        'result_all'=>$is_today,
		        'result_all_annual'=>$flag_annual,
		        'start_of_accrual'=>$start_of_year_accrual,
		        'next_accrual_date'=>$next_of_year_accrual
		    );
		    return $result;
		}
		
		/** end added sep 20 2016 **/
		
		
	}
/* End of file employee_leaves_model.php */
/* Location: ./application/controllers/company/Approvers_model.php */