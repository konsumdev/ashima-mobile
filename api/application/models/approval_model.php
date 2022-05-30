<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	/**
	 * Approval Model
	 *
	 * @category Model
	 * @version 1.0
	 * @author Jonathan Bangga <jonathanbangga@gmail.com>
	 */
	class Approval_model extends CI_Model {
		public function get_employee_time_in($val, $token){
			if(is_numeric($val)){
					
				$where = array(
						'ee.employee_time_in_id'   => $val,
						'ti.token'	=> $token
				);
					
				$this->db->where($where);
				$this->edb->join('company AS c','c.company_id = ee.comp_id','left');
				$this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
				$this->edb->join("approval_time_in AS ti","ti.time_in_id = ee.employee_time_in_id","LEFT");
				$this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
				$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
				$query = $this->edb->get('employee_time_in AS ee');
				//$query = $this->edb->get('employee_time_in AS ee');
				$result = $query->row();
				$query->free_result();
				return $result;
			}else{
				return false;
			}
		}
		/**
		 * Get Leave Information
		 * @param unknown_type $leave_id
		 */
		public function leave_info($leave_id){
			$w = array(
				"al.token"=>$leave_id,
				"al.status"=>"Active"
			);
			$this->edb->where($w);
			$this->edb->join("employee_leaves_application AS ela","ela.employee_leaves_application_id = al.leave_id","left");
			$this->edb->join("leave_type AS lt","lt.leave_type_id = ela.leave_type_id","left");
			$this->edb->join("employee AS e","e.emp_id = ela.emp_id","left");
			$q = $this->edb->get("approval_leave AS al");
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		/**
		 * Get Overtime Information
		 * @param unknown_type $overtime_id
		 */
		public function overtime_info($overtime_id){
			$w = array(
				"ao.token"=>$overtime_id,
				"ao.status"=>"Active"
			);
			$this->edb->where($w);
			$this->edb->join("employee_overtime_application AS eoa","eoa.overtime_id = ao.overtime_id","left");
			$this->edb->join("employee AS e","e.emp_id = eoa.emp_id","left");
			$q = $this->edb->get("approval_overtime AS ao");
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		/**
		 * Get Shifts Information
		 * @param unknown_type $overtime_id
		 */
		public function shifts_info($token){
			$w = array(
					"aws.token"=>$token,
					"aws.status"=>"Active"
			);
			$this->edb->where($w);
			$this->edb->join("employee_work_schedule_application AS ewsa","ewsa.employee_work_schedule_application_id = aws.employee_work_schedule_application_id","left");
			$this->edb->join("employee AS e","e.emp_id = ewsa.emp_id","left");
			$q = $this->edb->get("approval_work_schedule AS aws");
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		/**
		 * Get Time In Information
		 * @param unknown_type $leave_id
		 */
		public function time_info($token){
			$w = array(
				"at.token"=>$token,
				"at.status"=>"Active"
			);
			$select = array('*');
			$select2 = array('at.location AS a_location');
			$this->edb->select($select);
			$this->db->select($select2);
			$this->edb->where($w);
			$this->edb->join("employee_time_in AS eti","eti.employee_time_in_id = at.time_in_id","left");
			$this->edb->join("employee AS e","e.emp_id = eti.emp_id","left");
			$this->db->order_by("at.approval_time_in_id", "DESC");
			$q = $this->edb->get("approval_time_in AS at");

			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		/**
		 * Get Token from Approval Leave
		 * @param unknown_type $leave_ids
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function get_token($leave_ids,$comp_id,$emp_id){
			$w = array(
				"leave_id"=>$leave_ids,
				"comp_id"=>$comp_id,
				"emp_id"=>$emp_id,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("approval_leave");
			$row = $q->row();
			return ($q->num_rows() > 0) ? $row->token : "" ;
		}
		
		/**
		 * Get Note from Employee Leave Application
		 * @param unknown_type $leave_id
		 */
		public function get_note($leave_id){
			$w = array(
				"employee_leaves_application_id"=>$leave_id,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("employee_leaves_application");
			$row = $q->row();
			return ($q->num_rows() > 0) ? $row->note : "" ;
		}
		
		/**
		 * Get Note from Employee Overtime Application
		 * @param unknown_type $overtime_id
		 */
		public function get_overtime_notes($overtime_id){
			$w = array(
				"overtime_id"=>$overtime_id,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("employee_overtime_application");
			$row = $q->row();
			return ($q->num_rows() > 0) ? $row->notes : "" ;
		}
		
		/**
		 * Get Note from Employee Shift Application
		 * @param unknown_type $employee_work_schedule_application_id
		 */
		public function get_shifts_notes($employee_work_schedule_application_id){
			$w = array(
					"employee_work_schedule_application_id"=>$employee_work_schedule_application_id,
					"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("employee_work_schedule_application");
			$row = $q->row();
			return ($q->num_rows() > 0) ? $row->note : "" ;
		}
		
		/**
		 * Get Note from Employee Time Ins
		 * @param unknown_type $id
		 */
		public function get_note_timein($id){
			$w = array(
				"employee_time_in_id"=>$id
			);
			$this->db->where($w);
			$q = $this->db->get("employee_time_in");
			$row = $q->row();
			return ($q->num_rows() > 0) ? $row->notes : "" ;
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
			
			// $no_image = "/assets/theme_2013/images/photo_not_available.png";
			$no_image = "/assets/theme_2015/images/img-company-logo-2.png";
			
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
		 * Get HR informatio
		 * @param unknown_type $comp_id
		 */
		public function hr_info($comp_id){
			$w = array(
				"p.company_id"=>$comp_id,
				"u.roles"=>"hr"
			);
			$this->edb->where($w);
			//$this->edb->join("accounts AS a","a.account_id = ca.account_id","INNER");
			$this->edb->join("employee AS e","e.account_id = a.account_id","INNER");
			$this->edb->join("company_approvers AS ca","a.account_id = ca.account_id","INNER");
			$this->edb->join("user_roles AS u","u.users_roles_id = ca.users_roles_id","INNER");
			$this->edb->join("privilege AS p","u.users_roles_id = p.users_roles_id","INNER");
			$q = $this->edb->get("accounts AS a");
			
			return ($q->num_rows() > 0) ? $q->result() : FALSE ;
		}
		
		/**
		 * Get Finance information		 
		 * @param unknown_type $comp_id
		 */
		public function finance_info($comp_id){
			/* $w = array(
				"ca.company_id"=>$comp_id,
				"u.roles"=>"finance"
			);
			$this->edb->where($w);
			$this->edb->join("accounts AS a","a.account_id = ca.account_id","INNER");
			$this->edb->join("employee AS e","e.account_id = a.account_id","INNER");
			$this->edb->join("user_roles AS u","u.users_roles_id = ca.users_roles_id","INNER");
			$q = $this->edb->get("company_approvers AS ca");
			
			return ($q->num_rows() > 0) ? $q->result() : FALSE ;
			*/
			
			$w = array(
				"pr.company_id" => $comp_id,
				"u.roles" => "finance",
				"e.status" => "Active",
				"pr.status" => "Active"
			);
			$this->edb->where($w);
			$this->edb->join("company_approvers AS ca","ca.users_roles_id = pr.users_roles_id","INNER");
			$this->edb->join("user_roles AS u","u.users_roles_id = ca.users_roles_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = ca.account_id","INNER");
			$this->edb->join("employee AS e","e.account_id = a.account_id","INNER");
			
			#$this->edb->join("user_roles AS u","u.users_roles_id = pr.users_roles_id","INNER");
			#$this->edb->join("accounts AS a","a.payroll_system_account_id = u.payroll_system_account_id","INNER");
			#$this->edb->join("employee AS e","e.account_id = a.account_id","INNER");
			$q = $this->edb->get("privilege AS pr");
			
			return ($q->num_rows() > 0) ? $q->result() : FALSE ;
		}
		
		/**
		 * Get Overtime Infomation
		 * @param unknown_type $overtime_ids
		 * @param unknown_type $comp_id
		 */
		public function overtime_information($overtime_ids,$comp_id){
			$w = array(
				"eoa.overtime_id"=>$overtime_ids,
				"eoa.company_id"=>$comp_id
			);
			$this->edb->where($w);
			$this->edb->join("employee AS e","e.emp_id = eoa.emp_id","left");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","left");
			$q = $this->edb->get("employee_overtime_application AS eoa");
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		/**
		 * Check Token Approval Payroll
		 * @param unknown_type $company_id
		 * @param unknown_type $token
		 */
		public function check_token_payroll($token){
			$w2 = array(
				"token"=>$token,
				"status"=>"Active"
			);
			$this->db->where($w2);
			$q2 = $this->db->get("approval_payroll");
			return ($q2->num_rows() > 0) ? $q2->row() : false ;
		}
		
		/**
		 * View Master List
		 * @param unknown_type $company_id
		 */
		public function view_master_list($limit, $start, $company_id,$payroll_period,$period_from,$period_to,$params=NULL){
			
			$konsum_key = konsum_key();
			
			// check payroll group
			$w1 = array(
				"company_id"=>$company_id
			);
			$this->db->where($w1);
			$q1 = $this->db->get("payroll_period");
			$r = $q1->row();
			
			if($params["q"] != ""){
				$this->db->where("
						pp.company_id = '{$company_id}' AND pp.payroll_date = '{$payroll_period}' AND pp.period_from = '{$period_from}'
						AND pp.period_to = '{$period_to}' AND
						(convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$params['q']."%')
						OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$params['q']."%'
				", NULL, FALSE); // encrypt
			}
			
			$w2 = array(
				"pp.company_id"=>$company_id,
				"pp.payroll_date"=>$payroll_period,
				"pp.period_from"=>$period_from,
				"pp.period_to"=>$period_to,
				"pp.status"=>"Active"
			);
			$this->edb->where($w2);
			$this->edb->join("employee AS e","e.emp_id = pp.emp_id","left");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","left");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			// $this->edb->order_by("e.last_name","ASC");
			
			if($params["sort_by"] != "" && $params["sort"] != ""){
				if($params["sort_by"] == "e.last_name" || $params["sort_by"] == "a.payroll_cloud_id"){
					$this->edb->order_by($params["sort_by"],$params["sort"]);
				}else{
					$this->db->order_by($params["sort_by"],$params["sort"]);
				}
			}else if($params["sort_by"] != "" && $params["sort"] == ""){
				if($params["sort_by"] == "e.last_name" || $params["sort_by"] == "a.payroll_cloud_id"){
					$this->edb->order_by($params["sort_by"],"ASC");
				}else{
					$this->db->order_by($params["sort_by"],"ASC");
				}
			}else{
				$this->edb->order_by("e.last_name","ASC");
			}
			
			$q2 = $this->edb->get("payroll_payslip AS pp",$limit,$start);
			return ($q2->num_rows() > 0) ? $q2->result(): FALSE ;
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
		 * View Master List Counter
		 * @param unknown_type $company_id
		 */
		public function view_master_list_counter($company_id,$payroll_period,$period_from,$period_to,$params=NULL){
			
			$konsum_key = konsum_key();
			
			// check payroll group
			$w1 = array(
				"company_id"=>$company_id
			);
			$this->db->where($w1);
			$q1 = $this->db->get("payroll_period");
			$r = $q1->row();
			
			if($params["q"] != ""){
				$this->db->where("
						pp.company_id = '{$company_id}' AND pp.payroll_date = '{$payroll_period}' AND pp.period_from = '{$period_from}'
						AND pp.period_to = '{$period_to}' AND
						(convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$params['q']."%')
						OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$params['q']."%'
				", NULL, FALSE); // encrypt
			}
			
			$w2 = array(
				"pp.company_id"=>$company_id,
				"pp.payroll_date"=>$payroll_period,
				"pp.period_from"=>$period_from,
				"pp.period_to"=>$period_to,
				"pp.status"=>"Active"
			);
			$this->db->select("COUNT(pp.payroll_payslip_id) AS total");
			$this->edb->where($w2);
			$this->edb->join("employee AS e","e.emp_id = pp.emp_id","left");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","left");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->order_by("e.last_name","ASC");
			$q2 = $this->edb->get("payroll_payslip AS pp");
			$r2 = $q2->row();
			return ($q2->num_rows() > 0) ? $r2->total : FALSE ;
		}
		
		/**
		 * Total Amount
		 * @param unknown_type $company_id
		 */
		public function total_amount($company_id,$payroll_period,$period_from,$period_to){
			// check payroll group
			$w1 = array(
				"company_id"=>$company_id
			);
			$this->db->where($w1);
			$q1 = $this->db->get("payroll_period");
			$r = $q1->row();
			
			$w2 = array(
				"pp.company_id"=>$company_id,
				"pp.payroll_date"=>$payroll_period,
				"pp.period_from"=>$period_from,
				"pp.period_to"=>$period_to,
				"pp.status"=>"Active"
			);
			$this->edb->where($w2);
			$this->edb->join("employee AS e","e.emp_id = pp.emp_id","left");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","left");
			$this->edb->order_by("e.last_name","ASC");
			$q2 = $this->edb->get("payroll_payslip AS pp");
			return ($q2->num_rows() > 0) ? $q2->result(): FALSE ;
		}
		
		/**
		 * Get Other Deduction Amount
		 * @param unknown_type $row_odd_name
		 * @param unknown_type $row_emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $row_odd_pay_coverage_from
		 * @param unknown_type $row_odd_pay_coverage_to
		 */
		public function check_odd_amount($row_odd_name,$comp_id,$row_odd_pay_coverage_from,$row_odd_pay_coverage_to,$limit,$start){
			$w = array(
				"deduction_name"=>$row_odd_name,
				"company_id"=>$comp_id,
				"period_from"=>$row_odd_pay_coverage_from,
				"period_to"=>$row_odd_pay_coverage_to,
				"status"=>"Active"
			);
			$this->db->where($w);
			if($limit == "" && $start == ""){
				$q = $this->db->get("payroll_for_other_deductions");
			}else{
				$q = $this->db->get("payroll_for_other_deductions",$limit,$start);
			}
			
			$r = $q->result();
			if($q->num_rows() > 0){
				$t = 0;
				foreach($r as $row){
					$t += $row->amount;
				}
				return $t;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Check nearest payroll period
		 * @param unknown_type $payroll_group_id
		 * @param unknown_type $company_id
		 */
		public function check_npp($payroll_group_id,$company_id){
			$date = date("Y-m-d");
			$w = array(
				"payroll_group_id"=>$payroll_group_id,
				"company_id"=>$company_id
			);
			$this->db->where($w);
			//$this->db->where('cut_off_to <= "'.$date.'"');
			$this->db->where('cut_off_from <= "'.$date.'"');
			$this->db->where('first_payroll_date >= "'.$date.'"');
			$this->db->order_by("first_payroll_date","ASC");
			$q = $this->db->get("payroll_calendar");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
		/**
		 * Employee Payroll Group Id
		 * @param unknown_type $emp_id
		 * @param unknown_type $company_id
		 */
		public function emp_payroll_group_id($emp_id,$company_id){
			$w = array(
				"emp_id"=>$emp_id,
				"company_id"=>$company_id,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("employee_payroll_information");
			$r = $q->row();
			return ($r) ? $r->payroll_group_id : FALSE ;
		}
		
		public function cash_advance_details($token){
			$where = array(
				"ca.ca_token" => $token,
				"ca.status" => "Active"
			);
			$this->edb->where($where);
			$this->edb->join("employee AS emp","emp.emp_id = ca.emp_id","LEFT");
			$this->edb->join("company AS comp","comp.company_id = ca.company_id","LEFT");
			$this->edb->join("cash_advance_payment_terms AS capt","capt.cash_advance_payment_terms_id = ca.cash_advance_payment_terms_id","LEFT");
			$this->edb->join("cash_advance_payment_schedule AS caps","caps.cash_advance_payment_schedule_id = ca.cash_advance_payment_schedule_id","LEFT");
			$this->edb->join("employee_payroll_information AS empi","empi.emp_id = emp.emp_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = empi.payroll_group_id","LEFT");
			$this->edb->join("accounts AS acc","acc.account_id = emp.account_id","LEFT");
			$query = $this->edb->get("cash_advance AS ca");
			$row = $query->row();
			
			return ($row) ? $row : false;
		}
		
		public function ca_payment_start($comp_id, $emp_id, $pay_start_date){
			$where = array(
				"empi.emp_id" => $emp_id,
				"empi.company_id" => $comp_id
			);
			$this->db->where($where);
			$this->db->where("pc.cut_off_from <=",$pay_start_date);
			$this->db->where("pc.cut_off_to >=",$pay_start_date);
			$this->db->join("employee_payroll_information AS empi","empi.payroll_group_id = pc.payroll_group_id","LEFT");
			$query = $this->db->get("payroll_calendar As pc");
			$row = $query->row();
			
			return ($row) ? $row->cut_off_from : false;
		}
		
		public function deduction_cash_advance_id($comp_id){
			$where = array(
				"comp_id" => $comp_id,
				"name" => "Cash Advance"
			);
			$this->db->where($where);
			$query = $this->db->get("deductions_other_deductions");
			$row = $query->row();
			
			return ($row ) ? $row->deductions_other_deductions_id : false; 
		}
		
		public function payment_terms($comp_id,$id){
			$where = array(
				"cash_advance_payment_terms_id" => $id,
				"company_id" => $comp_id,
				"status" => "Active"
			);
			$this->db->where($where);
			$query = $this->db->get("cash_advance_payment_terms");
			$row = $query->row();
			
			return ($row) ? $row->number_of_months : false; 
		}
		
		public function ca_get_approver($emp_id){
			$w = array(
				'emp_id'=>$emp_id,
				'status'=>'Active'
			);
			$this->edb->where($w);
			$q = $this->edb->get("employee");
			$row = $q->row();
			return ($q->num_rows() > 0) ? ucwords($row->first_name)." ".ucwords($row->last_name): FALSE ;
		}
		
		public function get_approver_name($emp_id,$comp_id){
			$w = array(
				'epi.emp_id'=>$emp_id,
				'epi.company_id'=>$comp_id
			);
			$this->edb->where($w);
			$this->edb->join("employee AS e","epi.leave_approval_grp = e.emp_id","left");
			$this->edb->join("accounts AS a","e.account_id = a.account_id","left");
			$q = $this->edb->get("employee_payroll_information AS epi");
			return ($q) ? $q->row() : FALSE ;
		}
		
		/**
		 * Check Payslip
		 * @param unknown_type $emp_id
		 * @param unknown_type $payroll_period
		 */
		public function check_payroll_payslip($emp_id,$payroll_period){
			$w = array(
				"emp_id"=>$emp_id,
				"payroll_date"=>$payroll_period,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("payroll_payslip");
			$r = $q->row();
			return ($r) ? TRUE : FALSE ;
		}
		
		/**
		 * Insert Message Board
		 * @param unknown_type $email
		 * @param unknown_type $message_str
		 */
		public function insert_message_board($email,$message_str){
			$w = array(
				"a.email"=>$email,
				"e.status"=>"Active"
			);
			$this->edb->where($w);
			$this->edb->join("employee AS e","e.account_id = a.account_id","LEFT");
			$q = $this->edb->get("accounts AS a");
			$r = $q->row();
			if($r){
				$emp_id = $r->emp_id;
				$psa_id = $r->payroll_system_account_id;
				$date = date("Y-m-d H:i:s");
				$val = array(
					"psa_id"=>$psa_id,
					"emp_id"=>$emp_id,
					"message"=>$message_str,
					"via"=>"system",
					"date"=>$date
				);
				$insert = $this->db->insert("message_board",$val);
				return ($insert) ? TRUE : FALSE ;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Check Time In
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $employee_timein
		 */
		public function check_timein($emp_id,$comp_id,$employee_timein){
			$w = array(
				"eti.emp_id"=>$emp_id,
				"eti.comp_id"=>$comp_id,
				"eti.employee_time_in_id"=>$employee_timein
			);
			$this->db->where($w);
			$this->db->join("approval_time_in AS ati","ati.time_in_id = eti.employee_time_in_id","LEFT");
			$q = $this->db->get("employee_time_in AS eti");
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		/**
		 * Get Time Ins Information
		 * @param unknown_type $timein_id
		 * @param unknown_type $comp_id
		 */
		public function timeins_info($timein_id,$comp_id){
			$s = array(
				'et.change_log_date_filed',
				'et.change_log_time_in',
				'et.change_log_lunch_out',
				'et.change_log_lunch_in',
				'et.change_log_time_out',
				'a.email'
			);
			$w = array(
				'et.employee_time_in_id'=>$timein_id,
				'et.comp_id'=>$comp_id
			);
			$this->edb->where($w);
			$this->edb->join("employee AS e","e.emp_id = et.emp_id","left");
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$this->edb->join("approval_time_in AS ati","ati.time_in_id = et.employee_time_in_id","LEFT");
			$q = $this->edb->get("employee_time_in AS et");
			$result = $q->row();
			
			return ($q->num_rows() > 0) ? $result : FALSE ;
		}
		/**
		 * generates new token
		 */
		public function generate_shifts_level_token($new_level, $shifts_id){
			$str2 = 'ABCDEFG1234567890';
			$shuffled2 = str_shuffle($str2);
		
			$update = array(
					"level" => $new_level,
					"token_level" => $shuffled2
			);
			$where = array(
					"employee_work_schedule_application_id" => $shifts_id
			);
		
			$this->db->where($where);
			$update_approval_leave_token = $this->db->update("approval_work_schedule",$update);
		
			return ($update_approval_leave_token) ? $shuffled2 : false;
		}
		
		public function get_shifts_last_level($emp_id, $company_id){
			$this->db->where("emp_id",$emp_id);
			$sql = $this->db->get("employee_payroll_information");
			$row = $sql->row();
			if($row){
				$leave_approval_grp = $row->shedule_request_approval_grp;
				$w = array(
						"ag.company_id"=>$company_id,
						"ag.approval_groups_via_groups_id"=>$leave_approval_grp
				);
				$this->db->where($w);
				$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
				$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
				$this->db->order_by("ag.level","DESC");
				$q = $this->edb->get("approval_groups AS ag",1);
				$r = $q->row();
				return ($r) ? $r->level : FALSE ;
			}else{
				return FALSE;
			}
		}
		
		/** added: fritz **/
		
		/**
		 * Get Schedule Block Time In Information
		 * @param unknown_type $leave_id
		 */
		
		public function split_time_info($token,$schedule_blocks_time_in_id){
			$w = array(
					"sbti.schedule_blocks_time_in_id" 	=> $schedule_blocks_time_in_id,
					"ati.token"							=> $token,
					"ati.status"						=> "Active"
			);
			$select = array('*');
			$select2 = array(
					'ati.location AS a_location'
			);
			$this->edb->select($select);
			$this->db->select($select2);
			$this->edb->where($w);
			$this->edb->join("schedule_blocks_time_in AS sbti","sbti.schedule_blocks_time_in_id = ati.split_time_in_id","left");
			$this->edb->join("employee AS e","e.emp_id = sbti.emp_id","left");
			$this->db->order_by("ati.approval_time_in_id", "DESC");
			$q = $this->edb->get("approval_time_in AS ati");
		
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		public function get_employee_split_time_in($val, $token){
			if(is_numeric($val)){
		
				$where = array(
						'sbti.schedule_blocks_time_in_id' 	=> $val,
						'ti.token'							=> $token
				);
		
				$this->db->where($where);
				$this->edb->join('company AS c','c.company_id = sbti.comp_id','left');
				$this->edb->join('employee AS e','e.emp_id = sbti.emp_id','left');
				$this->edb->join("approval_time_in AS ti","ti.time_in_id = sbti.employee_time_in_id","LEFT");
				$this->edb->join('employee_payroll_information AS epi','epi.emp_id = sbti.emp_id','left');
				$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
				$query = $this->edb->get('schedule_blocks_time_in AS sbti');
				//$query = $this->edb->get('employee_time_in AS ee');
				$result = $query->row();
				$query->free_result();
				return $result;
			}else{
				return false;
			}
		}
		
		/**
		 * Check Split Time In
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $employee_timein
		 */
		public function check_split_timein($emp_id,$comp_id,$employee_timein){
			$w = array(
					"emp_id"=>$emp_id,
					"comp_id"=>$comp_id,
					"schedule_blocks_time_in_id"=>$employee_timein
			);
			$this->db->where($w);
			$q = $this->db->get("schedule_blocks_time_in");
			$r = $q->row();
				
			if ($r) {
				return ($q->num_rows() > 0) ? $q->row() : FALSE ;
			} else {
				$w = array(
						"emp_id"=>$emp_id,
						"comp_id"=>$comp_id,
						"employee_time_in_id"=>$employee_timein
				);
				$this->db->where($w);
				$q = $this->db->get("employee_time_in");
				return ($q->num_rows() > 0) ? $q->row() : FALSE ;
			}
		
		}
		
		/**
		 * Get Time In Information
		 * @param unknown_type $leave_id
		 */
		public function time_info_mobile($token){
			$w = array(
					"at.token"=>$token,
					"at.status"=>"Active"
			);
			$select = array(
					'eti.time_in',
					'eti.location_1',
					'eti.location_2',
					'eti.location_3',
					'eti.location_4',
					'eti.lunch_out',
					'eti.lunch_in',
					'eti.time_out',
					'e.first_name',
					'e.last_name',
					'eti.employee_time_in_id',
					'at.approve_by_head',
					'at.approve_by_hr',
					'eti.time_in_status',
					'at.flag_add_logs'
			);
			$select2 = array('at.location AS a_location');
			$this->db->select($select);
			$this->db->select($select2);
			$this->edb->where($w);
			$this->edb->join("employee_time_in AS eti","eti.employee_time_in_id = at.time_in_id","left");
			$this->edb->join("employee AS e","e.emp_id = eti.emp_id","left");
			$this->db->order_by("at.approval_time_in_id", "DESC");
			$q = $this->edb->get("approval_time_in AS at");
		
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
				
		}
		
		/** end **/
		
	}