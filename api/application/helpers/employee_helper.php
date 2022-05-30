<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
*	Helper : Employee helpers
*	Author : Jonathan Bangga <jonathanbangga@gmail.com>
*	Usage  : Employee Only
*/

	/**
	 * Employee Total Loan Balance
	 */
	function loan_balance($comp_id, $emp_id, $loan_no){
		$CI =& get_instance();
		// Total Loan Amount
			$total_loan_amount = $CI->employee->total_loan_amount($comp_id, $loan_no);
			if($total_loan_amount != FALSE){
				$amortization_sched_interest = 0;
				$amortization_sched_principal = 0;
				foreach($total_loan_amount as $row_amor){
					// Interest Total Amount for Amortization Schedule
					$amortization_sched_interest = $amortization_sched_interest + $row_amor->interest;
					
					// Principal Total Amount for Amortization Schedule
					$amortization_sched_principal = $amortization_sched_principal + $row_amor->principal;
				}
				#return $installment;
				// Installment for Amortization Schedule
				$installment = $amortization_sched_interest + $amortization_sched_principal;
			}else{
				return "No Amortization Schedule found";
				return false;
			}
		
			// 	Total Payment History
			$total_payment_history = $CI->employee->emp_payment_history($comp_id, $loan_no);
			if($total_payment_history != FALSE){
				$interest_val = 0;
            	$principal_val = 0;
				foreach($total_payment_history as $row){
					// Interest Total Amount
					$interest_val = $interest_val + $row->interest;
					
					// Principal Total Amount
					$principal_val = $principal_val + $row->principal;
				}
				
				// New Total Payment History
				$new_total_payment = $interest_val + $principal_val;
			}else{
				return "No Payment History found";
				return false;
			}
			
			#return $CI->db->last_query();
			$loan_balance = $installment - $new_total_payment; 
			// return $installment." - ".$new_total_payment." = ".$loan_balance;
			return number_format($loan_balance,2);
	}
	
	/**
	 * Check if user is an approver
	 * @param unknown $emp_id
	 */
	function check_if_approver($emp_id, $process= NULL){
		
		switch($process){
			case 'timein':
				$process = 'timein';
				break;
			case 'mobile':
				$process = 'Mobile Clock-in';
				break;
			case 'leave':
				$process= 'Leave';
				break;
			case 'overtime':
				$process = 'Overtime';
				break;
			case 'shifts':
				$process = 'Shifts';
				break;
			case 'payroll':
				$process = 'Payroll';
				break;
			case 'document':
				$process = 'Document';
				break;
			case 'termination':
				$process = 'Termination';
				break;
			case 'endOfYear':
				$process = 'End of Year';
				break;
			default:
				$process = NULL;
				break;
		}

		$CI =& get_instance();

		$CI->db->join('approval_process AS ap','ag.approval_process_id = ap.approval_process_id','LEFT');
		if($process!= NULL){
			if($process == 'timein'){
				$CI->db->where('name','Timesheet Adjustment');
				#$CI->db->or_where('name','Mobile Clock-in');
				$CI->db->or_where('name','Add Timesheet');
			}else{
				$CI->db->where('name',$process);
			}

		}
		$CI->db->where('ag.emp_id',$emp_id);

		$q = $CI->db->get('approval_groups AS ag');
		$r = $q->result();
		return ($r) ? TRUE: FALSE;
	
    }
    
    function is_approver($emp_id) {
        $CI =& get_instance();

		$CI->db->join('approval_process AS ap','ag.approval_process_id = ap.approval_process_id','LEFT');
		$CI->db->where('ag.emp_id',$emp_id);
        $CI->db->group_by('ap.name');
		$q = $CI->db->get('approval_groups AS ag');
		$r = $q->result();
		return ($r) ? $r : FALSE;
    }
    
    function get_employee_entitlements($emp_id){
        $CI =& get_instance();
        $select = array(
            "entitled_to_overtime",
            "entitled_to_leaves"
        );
        $where = array(
            "epi.emp_id" => $emp_id
        );
        $CI->db->select($select);
        $CI->db->where($where);
        $query = $CI->edb->get("employee_payroll_information AS epi");
        $emp_row = $query->row();
        return ($emp_row) ? $emp_row : FALSE;
    
    }

	function count_all_todo_pending_leave($emp_id, $company_id){
		$_CI =& get_instance();
		$konsum_key = konsum_key();
		$filter = filter_leave( $emp_id,$company_id);
		$where = array(
				"el.company_id" =>$company_id,
				"el.deleted" => '0',
				"ag.emp_id" => $emp_id,
				"el.leave_application_status"=>"pending"
		);
		$where2 = array(
				"al.level >=" => ""
		);
		$_CI->db->select('count(*) as val');
		$_CI->db->where($where2);
		$_CI->edb->where($where);
		if($filter != FALSE){
			$_CI->db->where("el.employee_leaves_application_id NOT IN ({$filter})");
		}
		$_CI->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
		$_CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
		$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$_CI->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
		$_CI->edb->join("approval_groups AS ag","agg.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
		$_CI->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
		$_CI->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
		$_CI->db->group_by('el.employee_leaves_application_id');
		$query = $_CI->edb->get("employee_leaves_application AS el");
		$row = $query->row();
			
		return ($row) ? $query->num_rows : 0;
	}
	
	function count_all_todo_pending_shifts($emp_id, $company_id){
		$_CI =& get_instance();
		$konsum_key = konsum_key();
		$filter = filter_shifts($emp_id, $company_id);
		$where = array(
				'ewsa.company_id'	=> $company_id,
				//'o.deleted'	=> '0',
				"ag.emp_id" => $emp_id,
				"ewsa.employee_work_schedule_status" => "pending"
		);
		$where2 = array(
				"aws.level !=" => ""
		);
		$_CI->db->select('count(*) as val');
		$_CI->edb->where($where);
		$_CI->db->where($where2);
		if($filter != FALSE){
			$_CI->db->where("ewsa.employee_work_schedule_application_id NOT IN ({$filter})");
		}
			
		$_CI->edb->join('employee AS e','e.emp_id = ewsa.emp_id','left');
		$_CI->edb->join('accounts AS a','a.account_id = e.account_id','left');
		$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
		$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$_CI->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
		$_CI->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
		$_CI->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
		$_CI->edb->join("approval_work_schedule AS aws","aws.employee_work_schedule_application_id = ewsa.employee_work_schedule_application_id","LEFT");
		$_CI->db->group_by("ewsa.employee_work_schedule_application_id");
		$query = $_CI->edb->get('employee_work_schedule_application AS ewsa');
		$row = $query->row();
		
		return ($row) ? $query->num_rows : 0;
	}
	
	function count_all_todo_pending_overtime($emp_id, $company_id){
		$_CI =& get_instance();
			$konsum_key = konsum_key();
			$filter = filter_overtime($emp_id, $company_id);
			$where = array(
				'o.company_id'	=> $company_id,
				'o.deleted'	=> '0',
				"ag.emp_id" => $emp_id,
				"o.overtime_status" => "pending" 
			);
			$where2 = array(
				"ao.level !=" => ""
			);
			$_CI->db->select('count(*) as val');
			$_CI->edb->where($where);
			$_CI->db->where($where2);
			if($filter != FALSE){
				$_CI->db->where("o.overtime_id NOT IN ({$filter})");
			}
			
			$_CI->edb->join('employee AS e','e.emp_id = o.emp_id','left');
			$_CI->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$_CI->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$_CI->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
			$_CI->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
			$_CI->edb->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
			$_CI->db->group_by("o.overtime_id");
			$query = $_CI->edb->get('employee_overtime_application AS o');	
			$row = $query->row();
			return ($row) ? $query->num_rows : 0;
	}
	function count_all_todo_pending_timein($emp_id, $company_id){
		$_CI =& get_instance();
		$konsum_key = konsum_key();
		$filter = filter_timein($emp_id, $company_id);
		$where = array(
				'ee.comp_id'   => $company_id,
				'ee.status'   => 'Active',
				'ee.corrected' => 'Yes',
				"ag.emp_id" => $emp_id,
				'ee.time_in_status' => 'pending'
		
		
		);
		$where2 = array(
				"at.level !=" => ""
		);
		
		$_CI->db->select('count(*) as val');
	
		$_CI->edb->where($where);
		$_CI->db->where($where2);
		if($filter != FALSE){
			$_CI->db->where("ee.employee_time_in_id NOT IN ({$filter})");
		}
			
		$_CI->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
		$_CI->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
		$_CI->edb->join('accounts AS a','a.account_id = e.account_id','left');
		$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$_CI->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
		$_CI->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
		$_CI->edb->join("approval_time_in AS at","at.approval_time_in_id= ee.approval_time_in_id","LEFT");
		$_CI->edb->join("work_schedule AS ws","ee.work_schedule_id = ws.work_schedule_id","LEFT");
		$_CI->db->group_by("employee_time_in_id");
		$query = $_CI->edb->get('employee_time_in AS ee');
		
		$row = $query->row();
		
		return ($row) ? $query->num_rows : 0;
	}
	
	function filter_leave($emp_id, $company_id){
		$_CI =& get_instance();
		$konsum_key = konsum_key();
		$where = array(
				"el.company_id" =>$company_id,
				"el.deleted" => '0',
				"ag.emp_id" => $emp_id,
				"el.leave_application_status"=>"pending"
		);
		$where2 = array(
				"al.level >=" => ""
		);
		
		$_CI->db->where($where2);
		$_CI->edb->where($where);
		$_CI->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
		$_CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
		$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$_CI->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
		$_CI->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
		$_CI->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
		$_CI->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
		$_CI->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
		$_CI->db->group_by('el.employee_leaves_application_id');
		$query = $_CI->edb->get("employee_leaves_application AS el");
		$result = $query->result();
		$arrs = array();
			
		if($result){
			$is_assigned = true;
		
			foreach($result as $key => $approvers){
					
				$leave_approval_grp = $approvers->leave_approval_grp;
				$level = $approvers->level;
				$check = check_assigned_group($leave_approval_grp, $level);
				
				//$is_done = $this->is_done($overtime_approval_grp, $level);
				if($check){
					//if(!$is_done){
		
				}
				else{
					array_push($arrs, $approvers->employee_leaves_application_id);
				}
			}
		}
		$string = implode(",", $arrs);
		return $string;
	}
	function filter_shifts($emp_id, $company_id){
		$_CI =& get_instance();
		$konsum_key = konsum_key();
		$where = array(
				'ewsa.company_id' => $company_id,
				//'o.deleted'	=> '0',
				"ag.emp_id" => $emp_id,
				"ewsa.employee_work_schedule_status" => "pending"
		);
		$where2 = array(
				"aws.level !=" => ""
		);
		$_CI->edb->where($where);
		$_CI->db->where($where2);
		$_CI->edb->join('employee AS e','e.emp_id = ewsa.emp_id','left');
		$_CI->edb->join('accounts AS a','a.account_id = e.account_id','left');
		$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
		$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$_CI->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
		$_CI->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
		$_CI->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
		$_CI->edb->join("approval_work_schedule AS aws","aws.employee_work_schedule_application_id = ewsa.employee_work_schedule_application_id","LEFT");
		$_CI->db->group_by("ewsa.employee_work_schedule_application_id");
		$query = $_CI->edb->get('employee_work_schedule_application AS ewsa');
		$result = $query->result();
	
		$arrs = array();
		if($result){
			$is_assigned = true;
			//$workforce_notification = get_workforce_notification_settings($company_id);
			foreach($result as $key => $approvers){
				$shift_approval_grp = $approvers->shedule_request_approval_grp;
				$level = $approvers->level;
				$check = check_assigned_group($shift_approval_grp, $level);
// 				if($workforce_notification->option == "choose level notification"){
// 					$is_assigned = check_if_is_level($level, $workforce_notification->workforce_alerts_notification_id);
// 				}
				//$is_done = $_CI->is_done($overtime_approval_grp, $level);
				if($is_assigned && $check){
					//if(!$is_done){
	
				}
				else{
					array_push($arrs, $approvers->employee_work_schedule_application_id);
				}
			}
		}
		$string = implode(",", $arrs);
		return $string;
	}
	function filter_overtime($emp_id, $company_id){
		$_CI =& get_instance();
		$konsum_key = konsum_key();
		$where = array(
				'o.company_id' => $company_id,
				'o.deleted'	=> '0',
				"ag.emp_id" => $emp_id,
				"o.overtime_status" => "pending"
		);
		$where2 = array(
				"ao.level !=" => ""
		);
		$_CI->edb->where($where);
		$_CI->db->where($where2);
		$_CI->edb->join('employee AS e','e.emp_id = o.emp_id','left');
		$_CI->edb->join('accounts AS a','a.account_id = e.account_id','left');
		$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
		$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$_CI->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
		$_CI->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
		$_CI->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
		$_CI->edb->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
		$_CI->db->group_by("o.overtime_id");
		$query = $_CI->edb->get('employee_overtime_application AS o');
		$result = $query->result();
		
		$arrs = array();
		if($result){
			$is_assigned = true;
			
			foreach($result as $key => $approvers){
				$overtime_approval_grp = $approvers->overtime_approval_grp;
				$level = $approvers->level;
				$check = check_assigned_group($overtime_approval_grp, $level);
				
				//$is_done = $_CI->is_done($overtime_approval_grp, $level);
				if($check){
					//if(!$is_done){
		
				}
				else{
					array_push($arrs, $approvers->overtime_id);
				}
			}
		}
		$string = implode(",", $arrs);
		return $string;
	}
	function filter_timein($emp_id, $company_id){
		$_CI =& get_instance();
		$konsum_key = konsum_key();
		$where = array(
				'ee.comp_id'   => $company_id,
				'ee.status'   => 'Active',
				'ee.corrected' => 'Yes',
				'ee.time_in_status' => 'pending'
		
		);
		$where2 = array(
				"at.level !=" => ""
		);
		
		
		$_CI->edb->where($where);
		$_CI->db->where($where2);
		$_CI->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
		$_CI->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
		$_CI->edb->join('accounts AS a','a.account_id = e.account_id','left');
		$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$_CI->edb->join("approval_time_in AS at","at.approval_time_in_id= ee.approval_time_in_id","LEFT");
		$_CI->db->group_by("employee_time_in_id");
		$query = $_CI->edb->get('employee_time_in AS ee');
		$result = $query->result();
		
		$arrs = array();
		if($result){
			$is_assigned = TRUE;
			
			foreach($result as $key => $approvers){
				if($approvers->flag_add_logs == 0){
					$appr_grp = $approvers->attendance_adjustment_approval_grp;
				}elseif($approvers->flag_add_logs == 1){
					$appr_grp = $approvers->add_logs_approval_grp;
				}elseif($approvers->flag_add_logs == 2){
					$appr_grp = $approvers->location_base_login_approval_grp;
				}
					
				$level = $approvers->level;
					
				$check = check_assigned_group($appr_grp, $level);
				//	echo $emp->employee_time_in_id.' - '. $emp->approval_time_in_id.' - '.$check.'</br>';
			
				if($check){
		
				}else{
					array_push($arrs, $approvers->employee_time_in_id);
				}
			}
				
		}
		$string = implode(",", $arrs);
		return $string;
	}
	function check_assigned_group($leave_appr_grp, $level){
		$CI =& get_instance();
		$where = array(
				"emp_id" => $CI->session->userdata("emp_id"),
				"level" => $level,
				"approval_groups_via_groups_id" => $leave_appr_grp
		);
		$CI->db->where($where);
		$query = $CI->db->get("approval_groups");
		$row = $query->row();
	
		return ($row) ? true : false;
	
	}
	function overtime_approval_level($emp_id){
		if(is_numeric($emp_id)){
			$this->db->where('ap.name','Overtime Approval Group');
			$this->db->where('ag.emp_id',$emp_id);
			$this->db->join('approval_process AS ap','ap.approval_process_id = ag.approval_process_id','LEFT');
			$q = $this->db->get('approval_groups AS ag');
			$r = $q->row();
	
			return ($r) ? $r->level : FALSE;
		}else{
			return false;
		}
	}
	
	function sms_shuffle_token(){
		return random_string('alnum', 8);
	}
	
	/**
	 * SEND INVITATIONS VIA SETF (Sms Email Twitter Facebook)
	 * Enter description here ...
	 * @param int $account_id
	 * @param int $psa_id
	 * @param boolean $return_html
	 * @param string  $custom_mobile_message if empty or add ur custom 
	 * @return object
	 */
	function send_setf_invitation_employee_old($account_id,$psa_id="",$return_html = false,$custom_mobile_message =""){
		$CI =& get_instance();
		if(is_numeric($account_id)){
			
			$update_where = array(
				'account_id'=>$account_id
			);
			$CI->edb->where($update_where);
			$verification_code = sms_shuffle_token();
			$gene_ipass = sms_shuffle_token();
			$field = array(
				"token"=>sms_shuffle_token(),
				'verification_code'=>$verification_code,
				'password'=>$CI->authentication->encrypt_password($gene_ipass),
				'verified_status'=>'verified'
			);
			$CI->edb->update('accounts',$field);
			
			$where = array(
				
				'a.account_id'=>$account_id,
				'a.deleted'=>'0',
				'e.status'=>'Active',
				'c.status'=>'Active'
			);
			if($psa_id == ""){
				$where['a.payroll_system_account_id'] = $CI->session->userdata('psa_id');
			}else{
				$where['a.payroll_system_account_id'] = $psa_id;
			}
			$CI->edb->where($where);
			
			$CI->edb->join('company AS c','c.company_id=e.company_id','LEFT');
			$CI->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
			$query = $CI->edb->get('employee AS e');
			#echo last_query();
			$row = $query->row();
			#p($row);
			#p($this->session->all_userdata());
			if($row){	
				
				if(enabled_sms_notificatioin()){
					#$message_default = "Please enter this code {$verification_code} to verify your mobile number. Log in to ".base_url()." and click profile.";
					$message_default = "You have been added to Ashima. To login, visit ".base_url().". Enter your mobile number; default password is {$gene_ipass}.";
					
					if($custom_mobile_message !==""){
						$message = $message;
					}else{
						$message = $message_default;
					}
					send_this_sms_global($row->company_id,$account_id,$message);
				}
											
				$all_company =  $row->company_name;
				$email_content = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
								"http://www.w3.org/TR/html4/loose.dtd">
								<html lang="en">
								<head>
								<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
								<meta name="format-detection" content="telephone=no">
								<title>E3</title>
								<style type="text/css">
								.ReadMsgBody {
									width: 100%;
									background-color: #ebebeb;
								}
								.ExternalClass {
									width: 100%;
									background-color: #ebebeb;
								}
								.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {
									line-height:100%;
								}
								body {
									-webkit-text-size-adjust:none;
									-ms-text-size-adjust:none;
									font-family:Open Sans, Arial, Helvetica, sans-serif;
								}
								body {
									margin:0;
									padding:0;
								}
								table {
									border-spacing:0;
								}
								table td {
									border-collapse:collapse;
								}
								.yshortcuts a {
									border-bottom: none !important;
								}
								</style>
								</head>
								<body>
								<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
								  <tr>
								    <td style="padding:30px 0 50px;" valign="top" align="center"><table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
								        
								        <tr>
								          <td valign="top" align="center"><table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
								              <tr>
								                <td valign="top" style="padding:40px 0 20px;"><h1 style="font-family:Open Sans, Arial, Helvetica, sans-serif; font-weight:400; color:#000; font-size:14px; margin:0 0 25px; line-height:22px;">Hello '.ucfirst($row->first_name)." ".ucfirst($row->last_name).'!</h1>
								                  <p style="font-family:Open Sans, Arial, Helvetica, sans-serif; font-weight:400; color:#000; font-size:12px; margin:0 0 18px; line-height:22px;"> You have been invited to join <span style="text-decoration:underline;">'.$all_company.'</span> Payroll, powered by Ashima.  
								                    To activate your account please click on the link below and  create your password:</p>
								                  <p style="font-family:Open Sans, Arial, Helvetica, sans-serif; font-weight:400; color:#1172ad; font-size:12px; margin:0 0 18px; line-height:22px;"><a style="text-decoration:none; color:#1172ad;" href="'.base_url("/users/credentials/change_pass/".$row->token).'"><span style="color:#1172ad !important;">'.base_url("/users/credentials/change_pass/".$row->token).'</span></a></p>
								                  <p style="font-family:Open Sans, Arial, Helvetica, sans-serif; font-weight:400; color:#000; font-size:12px; margin:0 0 18px; line-height:22px;">Best Regards,<br>
								                    Ashima Payroll Team </p>
								                  <p style="font-family:Open Sans, Arial, Helvetica, sans-serif; font-weight:400; color:#000; font-size:12px; margin:0 0 18px; line-height:22px;">----------------</p>
								                  <p style="font-family:Open Sans, Arial, Helvetica, sans-serif; font-weight:400; color:#000; font-size:12px; margin:0 0 18px; line-height:22px;">Have questions?</p></td>
								              </tr>
								            </table></td>
								        </tr>
								      </table></td>
								  </tr>
								  <tr>
								    <td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;"><table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
								        <tr>
								          <td valign="top" style="font-size:12px; font-family:Open Sans, Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; '.date("Y").' Konsum Technologies. All Rights Reserved.</td>
								          <td valign="top"><img src="'.base_url('/assets/theme_2015/images/newsletter/icon-newsletter-logo-footer.png').'" width="145" height="92" alt=" "></td>
								        </tr>
								      </table></td>
								  </tr>
								</table>
								</body>
								</html>';
				$CI->email->clear();
				$config['wordwrap'] = TRUE;
				$config['mailtype'] = 'html';
				$config['charset'] = 'utf-8';
				$CI->email->initialize($config);
				$CI->email->set_newline("\r\n");
				$CI->email->from('notifications@ashima.ph','Ashima Payroll');
				if($row->email !==""){
					$CI->email->to($row->email);
					$CI->email->subject('Account Completion');
					$CI->email->message($email_content);
					$email_check = $CI->email->send();	
					$x = $CI->email->print_debugger();
					if($return_html == true){
						return $email_content;
					}else{
						return $x;
					}
				}else{
					return false;
				}
			}
		}
		
	}
	
	//---------------------FOR EMPLOYEE DASHBOARD HELPERS------------------------//
	//--------------------BY: FIL <FilSandaloJr@gmail.com>----------------------//
	//      all the code bellow are used for the employee dashboard data        //
	/**
	 * this function is used form employee portals dashboard tiles to know the next pay
	 * @param int $comp_id
	 * @return object / boolean
	 */
	function next_pay_period($emp_id = "", $comp_id){
		$CI =& get_instance();
		$today = date('Y-m-d');
		
		$where = array(
			'cut_off_from <='=> $today,
			'first_payroll_date >='=> $today,
			'company_id' => $comp_id
		);
		$CI->db->where($where);
		$q = $CI->db->get('payroll_calendar');
		$r = $q->row();
		if($r){
			$date = $r->first_payroll_date;
			$payslips = get_payslips($emp_id, $comp_id);
			$flag = false;
			if($payslips){
				$count = 0;
				foreach($payslips as $pay){
					if(check_approval_date($pay->payroll_date, $pay->period_from, $pay->period_to, $comp_id)){
						if(date('Y-m-d', strtotime($pay->payroll_date)) == date('Y-m-d', strtotime($date))){
							
							$flag = true;
							$today = date('Y-m-d', strtotime($pay->payroll_date.' +1 day'));
							break;
						}
					}
				}
			}
			
			
			if($flag){
				$where1 = array(
						'cut_off_from <='=> $today,
						'first_payroll_date >='=> $today,
						'company_id' => $comp_id
				);
				$CI->db->where($where1);
				$q1 = $CI->db->get('payroll_calendar');
				$r1 = $q1->row();
				return ($r1) ? $r1 : FALSE;
			}else{
				return $r;
			}
		}else{
			return FALSE;
		}
	}
	function get_previous_pay($emp_id, $comp_id){
		$CI =& get_instance();
		$where = array(
				"emp_id"=>$emp_id,
				"company_id"=>$comp_id
		);
		$CI->edb->where($where);
		$CI->db->order_by('payroll_date','DESC');
		$q = $CI->edb->get("payroll_payslip");
		$r = $q->result();
		$result = FALSE;
		$flag = 0;
		if($r){
			foreach($r as $row){
				$check = check_approval_date($row->payroll_date, $row->period_from, $row->period_to, $comp_id);
				
				if($check ){
					if($flag == 0){
						$flag = 1;
						$result = $row->net_amount;
					}
				}
			}
			
			return ($result) ? $result : FALSE;
			
		}else{
			return FALSE;
		}
		
		
	}
	function get_payslips($emp_id, $comp_id){
		$CI =& get_instance();
		$where = array(
				"emp_id"=>$emp_id,
				"company_id"=>$comp_id
		);
		$CI->edb->where($where);
		$CI->db->order_by('payroll_date','DESC');
		$q = $CI->edb->get("payroll_payslip");
		$r = $q->result();
		
		return ($r) ? $r : FALSE;
				
		
		
	}
	function check_approval_date($payroll_date,$period_from,$period_to,$company_id){
		$CI =& get_instance();
		$w = array(
				"comp_id"=>$company_id,
				"approve_by_head"=>"Yes",
				"payroll_period"=>$payroll_date,
				"period_from"=>$period_from,
				"period_to"=>$period_to,
				"payroll_status"=>"approved",
				"generate_payslip"=>"Yes",
				"status"=>"Active"
		);
		$CI->db->where($w);
		$q = $CI->db->get("approval_payroll");
		return ($q->num_rows() > 0) ? TRUE : FALSE ;
	}
	
	/**
	 * GETS THE LISTS OF THE APPROVER OF AN LEAVE, OVERTIME, TIMESHEET, SHIFT APPLICATION
	 * @param int $application_id
	 * @param varchar $workflow_type
	 * @param varchar $approve
	 * @return object
	 */
	function workflow_approved_by_level($application_id, $workflow_type, $level="", $approve = ""){
		$CI =& get_instance();
		if($level != "") {
			$w = array(
					'application_id' => $application_id,
					'workflow_type' =>	$workflow_type,
					'workflow_level' => $level
			);
		} else {
			$w = array(
					'application_id' => $application_id,
					'workflow_type' =>	$workflow_type
			);
		}
		
		$CI->db->where($w);
		if($approve == "reject" || $approve == "rejected"){
			$CI->db->order_by('workflow_level','DESC');
			$q = $CI->db->get('workflow_approved_by');
			$r = $q->row();
		}else{
			$CI->db->order_by('workflow_level','ASC');
			$q = $CI->db->get('workflow_approved_by');
			
			$r = $q->result();
		}
	
		return ($r) ? $r : FALSE;
	
	}
	
	function count_absences($emp_id, $comp_id){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$CI->load->model('employee_model','employee');
		$period = next_pay_period($emp_id, $comp_id);
		$count = 0;
		if($period){
			$date = date('Y-m-d', strtotime($period->cut_off_from));
			$today = date('Y-m-d');
			if(strtotime($today) > strtotime($period->first_payroll_date)){
				$today = date('Y-m-d', strtotime($period->first_payroll_date));
			}
			#echo $date." - ".$today;
			while(strtotime($date) < strtotime($today)){
				$work_sched_id = check_employee_work_schedule($date, $emp_id, $comp_id)->work_schedule_id;
				if(!$work_sched_id){
					return false; break;
				}
				$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
				$check_holiday = $CI->employee->get_holiday_date($date,$emp_id,$comp_id);
				if(!$rest_day && !$check_holiday){
					$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
					$leave = $CI->ews->check_employee_leave_application($date, $emp_id);
					$leave_check = ($leave) ? TRUE: FALSE;
					if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
						/*$where = array(
								'date'=> $date,
								'emp_id' =>$emp_id,
								'status' => 'Active'
						);
						$CI->db->where($where);
						$q = $CI->db->get('employee_time_in');
						$r = $q->row();
						if(!$r){
							if($work_schedule_info['work_schedule']['flexible']){
								if($work_schedule_info['work_schedule']['login']){
										
									$count = $count + $work_schedule_info['work_schedule']['total_hours'];
										
								}
							}else{
								$count = $count + $work_schedule_info['work_schedule']['total_hours'];
							}
							
						}*/
						
						// fritz here
						$time_in = check_time_in_date($date, $emp_id);
						if(!$time_in){
							if(!$rest_day && !$check_holiday){
								//$count = $count + 1;
								$count = $count + $work_schedule_info['work_schedule']['total_hours'];
							}
						}
					}
				}
				$date = date('Y-m-d', strtotime($date.' +1 day'));
			}
		}
		return ($count == 0) ? $count : number_format(($count),2,'.',',');
	}
	function count_all_tardiness($emp_id, $comp_id){
		$CI =& get_instance();
		
		$CI->load->model('employee_model','employee');
		
		$period = next_pay_period($emp_id, $comp_id);
		if($period){
			$where = array(
					'date >=' => date('Y-m-d', strtotime($period->cut_off_from)),
					'date <=' => date('Y-m-d', strtotime($period->first_payroll_date)),
					'tardiness_min >' => '0',
					'emp_id' =>$emp_id,
					'status' => 'Active'
			);
			$CI->db->where($where);
			$q = $CI->db->get('employee_time_in');
			$r = $q->row();
			if($r){
				$check_employee_leave_application = $CI->employee->check_employee_leave_application($r->emp_id,$r->date);
				if($check_employee_leave_application["info"]["tardiness"] != "") {
					$under_with_leave = ($r->tardiness_min) - ($check_employee_leave_application["info"]["credited"] * 8) * 60;
						
				} else {
					$under_with_leave = $r->tardiness_min;
				}
			
				return $q->num_rows - count($under_with_leave);
			}else{
				return 0;
			}
		}else{
			return  0;
		}
			
		
		
	}
	function count_tardiness($emp_id, $comp_id){
		$CI =& get_instance();
		
		$CI->load->model('employee_model','employee');
		
		$period = next_pay_period($emp_id, $comp_id);
		$count = 0;
		if($period){
			$date = date('Y-m-d', strtotime($period->cut_off_from));
			$today = date('Y-m-d');
			if(strtotime($today) > strtotime($period->first_payroll_date)){
				$today = date('Y-m-d', strtotime($period->first_payroll_date));
			}
			while(strtotime($date) < strtotime($today)){
				$where = array(
						'date'=> $date,
						'emp_id' =>$emp_id,
						'status' => 'Active'
				);
				$CI->db->where($where);
				$q = $CI->db->get('employee_time_in');
				$r = $q->row();
				if($r){
					
					$check_employee_leave_application = $CI->employee->check_employee_leave_application($r->emp_id,$date);
					if($check_employee_leave_application["info"]["tardiness"] != "") {
						$tardi_with_leave = ($r->tardiness_min) - ($check_employee_leave_application["info"]["credited"] * 8) * 60;
					
					} else {
						$tardi_with_leave = $r->tardiness_min;
					}
					
					$count = $count + $tardi_with_leave;
				}
				$date = date('Y-m-d', strtotime($date.' +1 day'));
			}
		}
		return ($count == 0) ? $count : number_format(($count/60),2,'.',',');
	}
	function count_all_undertime($emp_id, $comp_id){
		$CI =& get_instance();
		
		$period = next_pay_period($emp_id, $comp_id);
		if($period){
				$where = array(
						'date >=' => date('Y-m-d', strtotime($period->cut_off_from)),
						'date <=' => date('Y-m-d', strtotime($period->first_payroll_date)),
						'undertime_min >' => '0',
						'emp_id' =>$emp_id,
						'status' => 'Active'
				);
				$CI->db->where($where);
				$q = $CI->db->get('employee_time_in');
				$r = $q->row();
				if($r){
					$check_employee_leave_application = $CI->employee->check_employee_leave_application($r->emp_id,$r->date);
					if($check_employee_leave_application["info"]["undertime"] != "") {
						$under_with_leave = ($r->undertime_min) - ($check_employee_leave_application["info"]["credited"] * 8) * 60;
					
					} else {
						$under_with_leave = $r->undertime_min;
					}
						
					return $q->num_rows - count($under_with_leave);
				}else{
					return 0;
				}
		}else{
			return 0;
		}
		
		
	}
	function count_undertime($emp_id, $comp_id){
		$CI =& get_instance();
		
		$CI->load->model('employee_model','employee');
		
		$period = next_pay_period($emp_id, $comp_id);
		$count = 0;
		if($period){
			$date = date('Y-m-d', strtotime($period->cut_off_from." -1 day"));
			$today = date('Y-m-d');
			/*if(strtotime($today) > strtotime($period->cut_off_to)){
				$today = date('Y-m-d', strtotime($period->cut_off_to));
			}*/
			
			if(strtotime($today) > strtotime($period->first_payroll_date)){
				$today = date('Y-m-d', strtotime($period->first_payroll_date));
			}
			
			while(strtotime($date) < strtotime($today)){
				$where = array(
						'date'=> $date,
						'emp_id' =>$emp_id,
						'status' => 'Active'
				);
				$CI->db->where($where);
				$q = $CI->db->get('employee_time_in');
				$r = $q->row();
				
				if($r){
					$check_employee_leave_application = $CI->employee->check_employee_leave_application($r->emp_id,$date);
					if($check_employee_leave_application["info"]["undertime"] != "") {
						$under_with_leave = ($r->undertime_min) - ($check_employee_leave_application["info"]["credited"] * 8) * 60;
						
					} else {
						$under_with_leave = $r->undertime_min;
					}
					
					$count = $count + $under_with_leave;
				}
				$date = date('Y-m-d', strtotime($date.' +1 day'));
			}
		}
		return ($count == 0) ? $count : number_format(($count / 60),2,'.',',');
	}
	function get_first_timesheet($emp_id, $comp_id){
		$CI =& get_instance();
		$count = 0;
		$w1  = array(
				'emp_id' =>$emp_id,
				'status' => 'Active'
		);
		$CI->db->where($w1);
		$CI->db->order_by('date','ASC');
		$q1 = $CI->db->get('employee_time_in');
		$r1 = $q1->row();
		if($r1){
			$date = date('Y-m-d', strtotime($r1->date));
		}else{
			$period = next_pay_period($emp_id, $comp_id);
			$count = 0;
			if($period){
				$date = date('Y-m-d', strtotime($period->cut_off_from));
			}else{
				$date = date('Y-m-d');
			}
		}
		return $date;
	}
	function all_missing_timesheet($emp_id, $comp_id){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$CI->load->model('employee_model','employee');
		$count = 0;
		$w1  = array(
				'emp_id' =>$emp_id,
				'status' => 'Active'
			);
		$CI->db->where($w1);
		$CI->db->order_by('date','ASC');
		$q1 = $CI->db->get('employee_time_in');
		$r1 = $q1->row();
		if($r1){
		$date = date('Y-m-d', strtotime($r1->date));
		}else{
			$period = next_pay_period($emp_id, $comp_id);
			$count = 0;
			if($period){
				$date = date('Y-m-d', strtotime($period->cut_off_from));
			}else{
				$date = date('Y-m-d');
			}
		}
		//$date = date('Y-m-d', strtotime($period->cut_off_from));
		$today = date('Y-m-d');
		while(strtotime($date) < strtotime($today)){
			$work_sched_id = check_employee_work_schedule($date, $emp_id, $comp_id)->work_schedule_id;
			if(!$work_sched_id){
				return false; break;
			}
			$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
			$check_holiday = $CI->employee->get_holiday_date($date,$emp_id,$comp_id);
			if(!$rest_day && !$check_holiday){
				$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
				$leave = $CI->ews->check_employee_leave_application($date, $emp_id);
				$leave_check = ($leave) ? TRUE: FALSE;
				if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
					$where = array(
							'date'=> $date,
							'emp_id' =>$emp_id,
							'status' => 'Active'
					);
					$CI->db->where($where);
					$q = $CI->db->get('employee_time_in');
					$r = $q->row();
					if(!$r){
						if($work_schedule_info['work_schedule']['flexible']){
							if($work_schedule_info['work_schedule']['login']){
									
								$count = $count + 1;
									
							}
						}else{
							$count = $count + 1;
						}
							
					}
				}
			}
			$date = date('Y-m-d', strtotime($date.' +1 day'));
		}
	
		return $count;
	}
	function missing_timesheet($emp_id, $comp_id){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$CI->load->model('employee_model','employee');
		$period = next_pay_period($emp_id, $comp_id);
		$count = 0;
		if($period){
			$today = date('Y-m-d', strtotime($period->cut_off_from." -1 day"));
			$date = date('Y-m-d', strtotime('-1 day'));
			if(strtotime($date) > strtotime($period->first_payroll_date)){
				$date = date('Y-m-d', strtotime($period->first_payroll_date));
			}
			
			while(strtotime($date) > strtotime($today)){
				$work_sched_id = check_employee_work_schedule($date, $emp_id, $comp_id)->work_schedule_id;
				if(!$work_sched_id){
					return false; break;
				}
				
				$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
				$check_holiday = $CI->employee->get_holiday_date($date,$emp_id,$comp_id);
				if(!$rest_day && !$check_holiday){
					$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
					$leave = $CI->ews->check_employee_leave_application($date, $emp_id);
					$leave_check = ($leave) ? TRUE: FALSE;
					
					if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
						/*$where = array(
								'date'=> $date,
								'emp_id' =>$emp_id,
								'status' => 'Active'
						);
						$CI->db->where($where);
						$q = $CI->db->get('employee_time_in');
						$r = $q->row();
						
						if(!$r){
							if($work_schedule_info['work_schedule']['flexible']){
								if($work_schedule_info['work_schedule']['login']){
									$count = $count + 1;
								}
							}else{
								$count = $count + 1;
							}							
						}*/
						
						// fritz here 
						$time_in = check_time_in_date($date, $emp_id);
						if(!$time_in){
							if(!$rest_day && !$check_holiday){
								$count = $count + 1;
							}
						}
					}
				}
				
				$date = date('Y-m-d', strtotime($date.' -1 day'));
			}
		}
		return $count;
	}
	function all_pending_timesheet($emp_id, $comp_id){
		$CI =& get_instance();
	
		$count = 0;
		$where1 = array(
				'time_in_status'=>'pending',
				'emp_id' =>$emp_id,
				'status' => 'Active'
		);
		
		$CI->db->where($where1);
		$q1 = $CI->db->get('employee_time_in');
		$r1 = $q1->row();
		return ($r1) ? $q1->num_rows : 0;
	
			
	}
	function pending_timesheet($emp_id, $comp_id){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$count = 0;
		$period = next_pay_period($emp_id, $comp_id);
		if($period){
			$date = date('Y-m-d', strtotime($period->cut_off_from));
			$today = date('Y-m-d');
			if(strtotime($today) > strtotime($period->first_payroll_date)){
				$today = date('Y-m-d', strtotime($period->first_payroll_date));
			}
			
			while(strtotime($date) < strtotime($today)){
				$work_sched_id = check_employee_work_schedule($date, $emp_id, $comp_id)->work_schedule_id;
				if(!$work_sched_id){
					return false; break;
				}
				$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
				if(!$rest_day){
					$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
					$leave = $CI->ews->check_employee_leave_application($date, $emp_id);
					$leave_check = ($leave) ? TRUE: FALSE;
					if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
						$where = array(
							'time_in_status'=>'pending',
							'date'=> $date,
							'emp_id' =>$emp_id,
							'status' => 'Active'
						);
						$CI->db->where($where);
						$q = $CI->db->get('employee_time_in');
						$r = $q->row();
						if($r){
							$count = $count + 1;	
						}
					}
				}
				$date = date('Y-m-d', strtotime($date.' +1 day'));
			}
		}
		return $count;
	}
	function all_rejected_timesheet($emp_id, $comp_id){
		$CI =& get_instance();
	
	
			
		$where = array(
				'time_in_status'=>'reject',
				'emp_id' =>$emp_id,
				'status' => 'Active'
		);
		$CI->db->where($where);
		$q = $CI->db->get('employee_time_in');
		$r = $q->row();
		if($r){
			return $q->num_rows;
		}else{
			return 0;
		}
			
	
	
	}
	function rejected_timesheet($emp_id, $comp_id){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$period = next_pay_period($emp_id, $comp_id);
		$count = 0;
		if($period){
		$date = date('Y-m-d', strtotime($period->cut_off_from));
		$today = date('Y-m-d');
		if(strtotime($today) > strtotime($period->cut_off_to)){
			$today = date('Y-m-d', strtotime($period->cut_off_to));
		}
			while(strtotime($date) < strtotime($today)){
				$work_sched_id = check_employee_work_schedule($date, $emp_id, $comp_id)->work_schedule_id;
				if(!$work_sched_id){
					return false; break;
				}
				$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
				if(!$rest_day){
					$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
					if(!$work_schedule_info) break;
					$leave = $CI->ews->check_employee_leave_application($date, $emp_id);
					$leave_check = ($leave) ? TRUE: FALSE;
					if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
						$where = array(
							'time_in_status'=>'reject',
							'date'=> $date,
							'emp_id' =>$emp_id,
							'status' => 'Active'
						);
						$CI->db->where($where);
						$q = $CI->db->get('employee_time_in');
						$r = $q->row();
						if($r){
							$count = $count + 1;	
						}
					}
				}
				$date = date('Y-m-d', strtotime($date.' +1 day'));
			}
		}
			return $count;
	}
	function count_missed_punches($emp_id, $comp_id){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$CI->load->model('employee_model','employee');
		
		$period =  next_pay_period($emp_id, $comp_id);
		$count = 0;
		$count_fritz = 0;
		if($period){
			#$date = date('Y-m-d', strtotime($period->cut_off_from));
			$date = date('Y-m-d', strtotime($period->cut_off_from.' -1 day'));
			$today = date('Y-m-d', strtotime('-1 day'));
			if(strtotime($today) > strtotime($period->cut_off_to)){
				$today = date('Y-m-d', strtotime($period->cut_off_to));
			}
			
			while(strtotime($date) < strtotime($today)){
				
				$work_sched_id = check_employee_work_schedule($date, $emp_id, $comp_id)->work_schedule_id;
				
				$is_break_assumed = is_break_assumed($work_sched_id);
				
				if(!$work_sched_id){
					return false; break;
				}
				$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
				$check_holiday = $CI->employee->get_holiday_date($date,$emp_id,$comp_id);
				
				$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
				
				if(!$work_schedule_info) break;
				$leave = $CI->ews->check_employee_leave_application($date, $emp_id);
				$leave_check = ($leave) ? TRUE: FALSE;
				if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
					
					$time_in = check_time_in_date($date, $emp_id);
					
					if($time_in){
						if(!$rest_day && !$check_holiday){
							if($time_in->time_in == NULL) $count = $count + 1;
							if(!$rest_day && !$check_holiday){
								if($work_schedule_info['work_schedule']['break_time'] != 0){
									if($time_in->lunch_out == NULL)  $count = $count + 1;
									if($time_in->lunch_in == NULL)  $count = $count + 1;
								}
							}
							if($time_in->time_out == NULL)  $count = $count + 1;
						}
						
						if($count > 0){
							if($time_in->time_in == NULL) $count_fritz = $count_fritz + 1;
							if(!$rest_day && !$check_holiday){
								#if($is_break_assumed) {
								if($work_schedule_info['work_schedule']['break_time'] != 0){
									if($is_break_assumed) {
										if($is_break_assumed->break_rules != "assumed") {
											if($time_in->lunch_out == NULL)  $count_fritz = $count_fritz + 1;
											if($time_in->lunch_in == NULL)  $count_fritz = $count_fritz + 1;
										}
									}
								}
								#}
							}
							
							if($time_in->time_out == NULL)  $count_fritz = $count_fritz + 1;
						}
					}
					/*$where = array(
						'date'=> date("Y-m-d", strtotime($date." +1 day")),
						'emp_id' =>$emp_id,
						'status' => 'Active'
					);
					$CI->db->where($where);
					$q = $CI->db->get('employee_time_in');
					$r = $q->row();
					if($r){
						if($r->time_in == NULL) $count = $count + 1;
						if(!$rest_day && !$check_holiday){
							if($is_break_assumed) {
								if($work_schedule_info['work_schedule']['break_time'] != 0){
									if($is_break_assumed->break_rules != "assumed") {
										if($r->lunch_out == NULL)  $count = $count + 1;
										if($r->lunch_in == NULL)  $count = $count + 1;
									}
								}
							}
							
						}
						
						if($r->time_out == NULL)  $count = $count + 1;
						
					}else{
						if($work_schedule_info['work_schedule']['flexible']){
							if($work_schedule_info['work_schedule']['login']){
								if(!$rest_day && !$check_holiday){
									$count = $count + 4;
								}
							}
						}
						//else{
						//	if(!$rest_day && !$check_holiday){
						//		$count = $count + 4;
						//	}
						//}
					}*/
				}
				
				$date = date('Y-m-d', strtotime($date.' +1 day'));
			}
		}
		return $count_fritz;
	}
	function check_employee_work_schedule($flag_date,$emp_id,$company_id){
		$CI =& get_instance();
		$w = array(
				"emp_id"=>$emp_id,
				"company_id"=>$company_id,
				"valid_from <="=>$flag_date,
				"until >="=>$flag_date,
				"status"=>"Active",
				"payroll_group_id" => 0
		);
		$CI->db->where($w);
		$q = $CI->db->get("employee_shifts_schedule");
		$r = $q->row();
		if($r){
			return $r;
		}else{
			$w = array(
					'epi.emp_id'=> $emp_id
			);
			$CI->db->where($w);
			$CI->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','LEFT');
			$q_pg = $CI->edb->get('employee_payroll_information AS epi');
			$r_pg = $q_pg->row();
	
			return ($r_pg) ? $r_pg: FALSE;
		}
	}
	
	function next_shift($emp_id, $comp_id){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$CI->load->model('employee_model','employee');
		$period =  next_pay_period($emp_id, $comp_id);
		//$date = date('Y-m-d', strtotime($period->cut_off_from));
		$date = date('Y-m-d');
		$today = date('Y-m-d');
		$shift = array();
		#if($period){
			#while(strtotime($date) >= strtotime($today)){
				$work_sched_id = check_employee_work_schedule(date('Y-m-d', strtotime($date.' +1 day')), $emp_id, $comp_id)->work_schedule_id;
				if(!$work_sched_id){
					return false; //break;
				}
				$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime(date('Y-m-d', strtotime($date.' +1 day')))));
				$check_holiday = $CI->employee->get_holiday_date(date('Y-m-d', strtotime($date.' +1 day')),$emp_id,$comp_id);
				if(!$rest_day && !$check_holiday){
					$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime(date('Y-m-d', strtotime($date.' +1 day')))));
					
					if(!$work_schedule_info) return false;//break;
					$leave = $CI->ews->check_employee_leave_application(date('Y-m-d', strtotime($date.' +1 day')), $emp_id);
					$leave_check = ($leave) ? TRUE: FALSE;
					if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
						if(strtotime(date('Y-m-d', strtotime($date.' +1 day'))) > strtotime($today)){
							$shift = array(
								'shift_name' => $work_schedule_info['work_schedule']['shift_name'],
								'shift_date' => date('Y-m-d', strtotime($date.' +1 day')),
								'start_time' => $work_schedule_info['work_schedule']['start_time'],
								'end_time'   => $work_schedule_info['work_schedule']['end_time'],
								'flexible'	 => $work_schedule_info['work_schedule']['flexible'],
								'required'	 => $work_schedule_info['work_schedule']['required']
							);
							//break;
						}
					}
				}
				#$date = date('Y-m-d', strtotime($date.' +1 day'));
			#}
		#}else{
			#return FALSE;
		#}
		return ($shift) ? $shift : FALSE;
	}
	function get_time_in($emp_id, $comp_id){
		$CI =& get_instance();
		$date = date('Y-m-d');
		$where = array(
				'date'=> $date,
				'emp_id' =>$emp_id
		);
		$CI->db->where($where);
		$q = $CI->db->get('employee_time_in');
		$r = $q->row();
		
		return ($r) ? $r : FALSE;
	}
	
	function check_if_have_break($emp_id, $comp_id,$date =""){
		
		if(!$date){
			$date = date('Y-m-d');
		}
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		
		$work_sched_id = check_employee_work_schedule($date, $emp_id, $comp_id)->work_schedule_id;
		$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
		if($rest_day){
			return FALSE;
		}else{
			
			$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
			if($work_schedule_info['work_schedule']['break_time']!=0){
				return TRUE;
			}else{
				return FALSE;
			}
		}
	}
	
	function check_if_required($emp_id, $comp_id,$date =""){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		
		if(!$date){
			$date = date('Y-m-d');
		}
		$work_sched_id = check_employee_work_schedule($date, $emp_id, $comp_id)->work_schedule_id;

		$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
		if($work_schedule_info['work_schedule']['login']==1){
			return TRUE;
		}else{
			
			$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
			if($rest_day){
				return TRUE;
			}
			
			return FALSE;
		}
		
	}
	
	function get_default_approver_by_row($company_id,$approval_process_id) {
	    $CI =& get_instance();
	    
	    $w = array(
	        "emp_id" 				=> "-99".$company_id,
	        "company_id"			=> $company_id,
	        "approval_process_id"	=> $approval_process_id
	    );
	    
	    $CI->db->where($w);
	    $sql = $CI->db->get("approval_groups");
	    $row = $sql->row();
	    
	    return ($row) ? $row : false;
	}
	
	function change_approver_to_default($emp_id,$company_id,$approval_grp,$account_id){
	    $CI =& get_instance();
	    
	    $w = array(
	        "emp_id" 		=> $emp_id,
	        "company_id"	=> $company_id,
	        "status" 		=> "Active"
	    );
	    
	    $CI->db->where($w);
	    $sql = $CI->db->get("employee_payroll_information");
	    $row = $sql->row();
	    
	    if($row){
	        if($approval_grp == "leave_approval_grp") {
	            $approval_groups_via_groups_id = $row->leave_approval_grp;
	            $name = "Leave";
	        } elseif ($approval_grp == "overtime_approval_grp") {
	            $approval_groups_via_groups_id = $row->overtime_approval_grp;
	            $name = "Overtime";
	        } elseif ($approval_grp == "location_base_login_approval_grp") {
	            $approval_groups_via_groups_id = $row->location_base_login_approval_grp;
	            $name = "Mobile Clock-in";
	        } elseif ($approval_grp == "attendance_adjustment_approval_grp") {
	            $approval_groups_via_groups_id = $row->attendance_adjustment_approval_grp;
	            $name = "Timesheet Adjustment";
	        } elseif ($approval_grp == "add_logs_approval_grp") {
	            $approval_groups_via_groups_id = $row->add_logs_approval_grp;
	            $name = "Add Timesheet";
	        } elseif ($approval_grp == "shedule_request_approval_grp") {
	            $approval_groups_via_groups_id = $row->shedule_request_approval_grp;
	            $name = "Shifts";
	        } elseif ($approval_grp == "document_approval_grp") {
	            $approval_groups_via_groups_id = $row->document_approval_grp;
	            $name = "Document";
	        }
	        
	        $w = array(
	            "ag.company_id"						=> $company_id,
	            "ag.approval_groups_via_groups_id"	=> $approval_groups_via_groups_id,
	            "ap.name"							=> $name
	        );
	        
	        $CI->db->where($w);
	        $CI->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
	        $CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
	        $CI->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
	        $CI->edb->join("approval_process AS ap","ag.approval_process_id = ap.approval_process_id","LEFT");
	        $CI->db->order_by("ag.level","ASC");
	        $q = $CI->edb->get("approval_groups AS ag");
	        $r = $q->result();
	        
	        $approver_all_active = true;
	        if($r) {
	            foreach ($r as $row1) {
	                if($row1->employee_status == "Inactive") {
	                    $approver_all_active = false;
	                }
	            }
	        }
	        
	        if($approver_all_active == FALSE) {
	            add_workflow_approval_default_group($company_id,$account_id);
	            
	            $w1 = array(
	                "ag.approval_groups_via_groups_id"	=> $approval_groups_via_groups_id,
	            );
	            
	            $CI->db->where($w1);
	            $CI->edb->join("approval_process AS ap","ap.approval_process_id = ag.approval_process_id","LEFT");
	            $CI->db->group_by("ag.approval_process_id");
	            $sql1 = $CI->db->get("approval_groups AS ag");
	            $row1 = $sql1->result();
	            
	            if($row1) {
	                $res = array();
	                foreach ($row1 as $r) {
	                    
	                    if($r->name == "Leave") {
	                        $approval_grp_field = "leave_approval_grp";
	                    } elseif($r->name == "Overtime") {
	                        $approval_grp_field = "overtime_approval_grp";
	                    } elseif($r->name == "Mobile Clock-in") {
	                        $approval_grp_field = "location_base_login_approval_grp";
	                    } elseif($r->name == "Timesheet Adjustment") {
	                        $approval_grp_field = "attendance_adjustment_approval_grp";
	                    } elseif($r->name == "Add Timesheet") {
	                        $approval_grp_field = "add_logs_approval_grp";
	                    } elseif($r->name == "Shifts") {
	                        $approval_grp_field = "shedule_request_approval_grp";
	                    } elseif($r->name == "Document") {
	                        $approval_grp_field = "document_approval_grp";
	                    } else {
	                        $approval_grp_field = "";
	                    }
	                    
	                    $get_default_approver_by_row = get_default_approver_by_row($company_id,$r->approval_process_id);
	                    
	                    if($get_default_approver_by_row) {
	                        $where = array(
	                            "emp_id" => $emp_id,
	                            "company_id" => $company_id
	                        );
	                        
	                        $data = array(
	                            $approval_grp_field => $get_default_approver_by_row->approval_groups_via_groups_id
	                        );
	                        
	                        eupdate('employee_payroll_information', $data, $where);
	                    }
	                }
	            }
	        }
	    }else{
	        return FALSE;
	    }
	}
	
	function approver_dont_need_to_assigned($company_id, $approval_process_name) {
	    $CI =& get_instance();
	    
	    $w1 = array(
	        "ag.company_id"	=> $company_id,
	        "ap.name"		=> $approval_process_name,
	        "agvg.status"	=> "Active"
	    );
	    
	    $CI->db->where($w1);
	    $CI->edb->join("approval_groups_via_groups AS agvg","agvg.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
	    $CI->edb->join("approval_process AS ap","ap.approval_process_id = ag.approval_process_id","LEFT");
	    $sql1 = $CI->edb->get("approval_groups AS ag");
	    $row1 = $sql1->row();
	    
	    if($row1) {
	        $select = array(
	            "ag.emp_id AS emp_id",
	            "a.account_id",
	            "ag.approval_groups_via_groups_id",
	            "ag.company_id",
	            "ap.name",
	            "ag.level",
	            "ag.approval_process_id",
	        );
	        
	        $eselect = array(
	            "e.first_name",
	            "e.last_name",
	            "a.email"
	        );
	        
	        $w = array(
	            "ag.company_id" => $row1->company_id,
	            "ag.approval_groups_via_groups_id" => $row1->approval_groups_via_groups_id,
	            "ap.name"=> "Document"
	        );
	        
	        $CI->db->select($select);
	        $CI->edb->select($eselect);
	        $CI->db->where($w);
	        $CI->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
	        $CI->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
	        $CI->edb->join("approval_process AS ap","ag.approval_process_id = ap.approval_process_id","LEFT");
	        $CI->db->order_by("ag.level","ASC");
	        $q = $CI->edb->get("approval_groups AS ag");
	        $r = $q->result();
	        
	        return ($r) ? $r : FALSE ;
	    } else {
	        return false;
	    }
	    
	    #return ($row1) ? $row1 : false;
	}
	
	function get_approvers_name_and_status($comp_id, $emp_id, $application_id, $application_type) {
	    $CI =& get_instance();
	    
	    if($application_type == "leave") {
	        $name = "Leave";
	        $where = array(
	            "comp_id" => $comp_id,
	            "emp_id" => $emp_id,
	            "leave_id" => $application_id
	        );
	        
	        $CI->db->where($where);
	        $q = $CI->edb->get("approval_leave");
	        
	        $r = $q->row();
	    } elseif($application_type == "attendance_adjustment" || $application_type == "add_logs" || $application_type == "mobile_clock_in") {
	        if($application_type == "add_logs") {
	            $name = "Add Timesheet";
	        } elseif($application_type == "attendance_adjustment") {
	            $name = "Timesheet Adjustment";
	        } elseif($application_type == "mobile_clock_in") {
	            $name = "Mobile Clock-in";
	        }
	        
	        $where = array(
	            "comp_id" => $comp_id,
	            "emp_id" => $emp_id,
	            "time_in_id" => $application_id
	        );
	        
	        $CI->db->where($where);
	        $q = $CI->edb->get("approval_time_in");
	        
	        $r = $q->row();
	    } elseif($application_type == "overtime") {
	        $name = "Overtime";
	        $where = array(
	            "comp_id" => $comp_id,
	            "emp_id" => $emp_id,
	            "overtime_id" => $application_id
	        );
	        
	        $CI->db->where($where);
	        $q = $CI->edb->get("approval_overtime");
	        
	        $r = $q->row();
	    }
	    
	    if($r) {
	        $sel = array(
	            "e.last_name",
	            "e.first_name"
	        );
	        
	        $s = array(
	            "ag.emp_id",
	            "ag.company_id",
	            "ag.level"
	        );
	        
	        $w = array(
	            "ag.company_id"=>$comp_id,
	            "ag.approval_groups_via_groups_id"=>$r->approver_id,
	            "ap.name"=> $name
	        );
	        
	        $CI->db->select($s);
	        $CI->edb->select($sel);
	        $CI->db->where($w);
	        $CI->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
	        $CI->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
	        $CI->edb->join("approval_process AS ap","ag.approval_process_id = ap.approval_process_id","LEFT");
	        $CI->db->order_by("ag.level","ASC");
	        $q1 = $CI->edb->get("approval_groups AS ag");
	        $r1 = $q1->result();
	        
	        return ($r1) ? $r1 : FALSE ;
	    } else {
	        return false;
	    }
	}
	
	
	
	
	//HELPERS FOR  REQUEST CHANGE WORK SCHEDULE
	
	/**
	 * gets the setttings of prior days a employee can request a change schedule
	 * @param int $comp_id
	 * @return number
	 */
	function schedule_request_days($comp_id){
		$CI =& get_instance();
		$CI->db->where('company_id',$comp_id);
		$q = $CI->db->get('schedule_request_settings');
		$r = $q->row();
		
		if($r){
			return $r->number_of_days;	
		}else{
			//default number of days
			return 5;
		}
	}
	
	function get_schedule($date, $emp_id, $comp_id){
		$CI =& get_instance();
		$date = date('Y-m-d', strtotime($date));
		$work_sched_id = check_employee_work_schedule($date, $emp_id, $comp_id)->work_schedule_id;
		if($work_sched_id){
			$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
			if($work_schedule_info){
				return $work_schedule_info['work_schedule'];
			}else{
				return FALSE;
			}
		}else{
			return FALSE;
		}
	}
	
	function get_pay_period($comp_id, $date){
		$CI =& get_instance();
		$today = date('Y-m-d', strtotime($date));
		$where = array(
				'cut_off_from <='=> $today,
				'cut_off_to >='=> $today,
				'company_id' => $comp_id
		);
		$CI->db->where($where);
		$q = $CI->db->get('payroll_calendar');
		$r = $q->row();
		return ($r) ? $r->first_payroll_date : FALSE;
	}
	function next_pay_period_via_date($comp_id, $date){
		$CI =& get_instance();
		$today = date('Y-m-d', strtotime($date));
		$where = array(
				'cut_off_from <='=> $today,
				'cut_off_to >='=> $today,
				'company_id' => $comp_id
		);
		$CI->db->where($where);
		$q = $CI->db->get('payroll_calendar');
		$r = $q->row();
		if($r){
			$where = array(
					"first_payroll_date >"=> $r->first_payroll_date,
					"company_id"=> $comp_id
					
			);
			$CI->db->where($where);
			$CI->db->order_by('first_payroll_date','ASC');
			$q2 = $CI->db->get('payroll_calendar');
			$r2 = $q2->row();
			
			return ($r2) ? $r2->first_payroll_date : FALSE;
		}
	}
	
	function check_time_in_date($date, $emp_id){
		$CI =& get_instance();
		$where = array(
				'date'=> $date,
				'emp_id' =>$emp_id,
				'status' => 'Active'
		);
		$CI->db->where($where);
		$q = $CI->db->get('employee_time_in');
		$r = $q->row();
		
		if($r){
			return $r;
		}else{
			return FALSE;
		}
	}
	function is_entitled_to_overtime($emp_id){
		$CI =& get_instance();
		$details = get_employee_details_by_empid($emp_id);
		if($details->entitled_to_overtime == "yes"){
			RETURN TRUE;
		}else{
			RETURN FALSE;
		}
	}
	function is_entitled_to_leave($emp_id){
		$CI =& get_instance();
		$details = get_employee_details_by_empid($emp_id);
		if($details->entitled_to_leaves == "yes"){
			RETURN TRUE;
		}else{
			RETURN FALSE;
		}
	}
	function is_entitled_to_deminimis($emp_id){
		$CI =& get_instance();
		$details = get_employee_details_by_empid($emp_id);
		if($details->entitled_to_deminimis == "yes"){
			RETURN TRUE;
		}else{
			RETURN FALSE;
		}
	}
	
	function is_service_charge_enabled($company_id){
		$CI =& get_instance();
		$where = array(
			'company_id' => $company_id,
			'status'	=> 'Active'
		);
		$CI->db->where($where);
		$q = $CI->db->get('service_charges');
		$r = $q->row();
		if($r){
			if($r->enabled == "yes"){
				RETURN TRUE;
			}else{
				RETURN FALSE;
			}
		}else{
			return FALSE;
		}
		
	}
	
	function check_timesheet_overtime($emp_id, $start_date , $end_date){
		$CI =& get_instance();
		$where = array(
				'date ' => date('Y-m-d', strtotime($start_date)),
				'emp_id' =>$emp_id,
				'status' => 'Active'
		);
		$CI->db->where($where);
		$q = $CI->db->get('employee_time_in');
		$r = $q->row();
		if($r){
			
			$start_date = date('Y-m-d h:i A', strtotime($start_date));
			$end_date = date('Y-m-d h:i A', strtotime($end_date));
			$t_start_date = date('Y-m-d h:i A', strtotime($r->time_in));
			$t_end_date = date('Y-m-d h:i A', strtotime($r->time_out));
			
			if(strtotime($start_date) < strtotime($t_start_date)){
				$where = array(
						'date ' => date('Y-m-d', strtotime($start_date.' -1 day')),
						'emp_id' =>$emp_id,
						'status' => 'Active'
				);
				$CI->db->where($where);
				$q = $CI->db->get('employee_time_in');
				$r = $q->row();
				
				$start_date = date('Y-m-d h:i A', strtotime($start_date));
				$end_date = date('Y-m-d h:i A', strtotime($end_date));
				$t_start_date = date('Y-m-d h:i A', strtotime($r->time_in));
				$t_end_date = date('Y-m-d h:i A', strtotime($r->time_out));
				
				if( strtotime($t_start_date) <= strtotime($start_date) ){
					if( strtotime($t_end_date) >= strtotime($end_date)){
						return TRUE;
					}else{
						return FALSE;
					}
				}else{
					return FALSE;
				}
				
			}else{
				if( strtotime($t_start_date) <= strtotime($start_date) ){
					if( strtotime($t_end_date) >= strtotime($end_date)){
						return TRUE;
					}else{
						return FALSE;
					}
				}else{
					return FALSE;
				}
			}
			
			
			
		}else{
			$where = array(
				"DATE_FORMAT(time_out,'%Y-%m-%d')" => date('Y-m-d', strtotime($start_date)),
				'emp_id' =>$emp_id,
				'status' => 'Active'
			);
			$CI->db->where($where);
			$q = $CI->db->get('employee_time_in');
			$r = $q->row();
			if($r){
				$start_date = date('Y-m-d h:i A', strtotime($start_date));
				$end_date = date('Y-m-d h:i A', strtotime($end_date));
				$t_start_date = date('Y-m-d h:i A', strtotime($r->time_in));
				$t_end_date = date('Y-m-d h:i A', strtotime($r->time_out));
				
				if( strtotime($t_start_date) <= strtotime($start_date) ){
					if( strtotime($t_end_date) >= strtotime($end_date)){
						return TRUE;
					}else{
						return FALSE;
					}
				}else{
					return FALSE;
				}
			
			}else{
				return FALSE;
			}
			
		}
		
	}
	
	function check_if_mobile_clock_in($time_in_id, $emp_id, $comp_id){
		$CI =& get_instance();
		$w = array(
				'time_in_id'=> $time_in_id,
				'emp_id'=>$emp_id,
				'comp_id'=> $comp_id,
				'status'=> 'Active'
		);
		$CI->db->where($w);
		
		$CI->db->order_by('approval_time_in_id','DESC');
		
		//$this->db->join('approval_time_in AS at','et.employee_time_in_id = at.time_in_id',"LEFT");
		$q = $CI->db->get('approval_time_in');
		$r = $q->row();
		if($r){
			
			if($r->flag_add_logs == 2){
				return TRUE;
			}else{
				return FALSE;
			}
			
		}else{
			return FALSE;
		}
		
	}
	/**
	 * GETS THE LISTS OF THE APPROVER OF AN LEAVE, OVERTIME, TIMESHEET, SHIFT APPLICATION
	 * @param int $application_id
	 * @param varchar $workflow_type
	 * @param varchar $approve
	 * @return object
	 */
	function workflow_approved_by($application_id, $workflow_type, $approve = ""){
		$CI =& get_instance();
		$w = array(
			'application_id' => $application_id,
			'workflow_type' =>	$workflow_type
		);
		$CI->db->where($w);
		if($approve == "reject" || $approve == "rejected"){
			$CI->db->order_by('workflow_level','DESC');
			$q = $CI->db->get('workflow_approved_by');
			$r = $q->row();
		}else{
			$CI->db->order_by('workflow_level','ASC');
			$q = $CI->db->get('workflow_approved_by');
			$r = $q->result();
		}
		
		return ($r) ? $r : FALSE;
		
	}
	
	

	
	/** AUTORESPONDER SA BRISTLEBACK **/
	/**
	 * SEND INVITATIONS VIA SETF (Sms Email Twitter Facebook) BRISTLE BACK AUTORESPONDERS
	 * Enter description here ...
	 * @param int $account_id
	 * @param int $psa_id
	 * @param boolean $return_html
	 * @param string  $custom_mobile_message if empty or add ur custom
	 * @return object
	 */
	function send_setf_invitation_employee($account_id,$psa_id="",$return_html = false,$custom_mobile_message =""){
		$CI =& get_instance();
		if(is_numeric($account_id)){
				
			$update_where = array(
					'account_id'=>$account_id
			);
			$CI->edb->where($update_where);
			$verification_code = sms_shuffle_token();
			$gene_ipass = sms_shuffle_token();
			$field = array(
					"token"=>sms_shuffle_token(),
					'verification_code'=>$verification_code,
					'password'=>$CI->authentication->encrypt_password($gene_ipass),
					'verified_status'=>'verified'
			);
			$CI->edb->update('accounts',$field);
				
			$where = array(
	
					'a.account_id'=>$account_id,
					'a.deleted'=>'0',
					'e.status'=>'Active',
					'c.status'=>'Active'
			);
			if($psa_id == ""){
				$where['a.payroll_system_account_id'] = $CI->session->userdata('psa_id');
			}else{
				$where['a.payroll_system_account_id'] = $psa_id;
			}
			$CI->edb->where($where);
				
			$CI->edb->join('company AS c','c.company_id=e.company_id','LEFT');
			$CI->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
			$query = $CI->edb->get('employee AS e');
			#echo last_query();
			$row = $query->row();
			#p($row);
			#p($this->session->all_userdata());
			if($row){
				$all_company = $row->company_name;
				if($row->email !==""){ ## KONG NAAY EMAIL DILI NATA MO SEND OG SMS KAY KALAS LOAD 
					if(enabled_sms_notificatioin()){
						#$message_default = "Please enter this code {$verification_code} to verify your mobile number. Log in to ".base_url()." and click profile.";
						#$message_default = "You have been invited to join ".$all_company.". To login, visit ".base_url('/login').". Enter your mobile number; default password is {$gene_ipass}.";
						$message_default = "You have been invited to join ".$all_company.".To login, enter your mobile number; default password is {$gene_ipass}.";
						if($custom_mobile_message !==""){
							$message = $message;
						}else{
							$message = $message_default;
						}
						send_this_sms_global($row->company_id,$account_id,$message);
					}
				} # END SMS NOT SEND IF HAVE EMAIL ADDRESS GETS?
				
				$email_content = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
							"http://www.w3.org/TR/html4/loose.dtd">
							<html lang="en">
							<head>
							<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
							<meta name="format-detection" content="telephone=no">
							<title>System Email 3</title>
							<style type="text/css">
							.ReadMsgBody {
								width: 100%;
								background-color: #ebebeb;
							}
							.ExternalClass {
								width: 100%;
								background-color: #ebebeb;
							}
							.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {
								line-height:100%;
							}
							body {
								-webkit-text-size-adjust:none;
								-ms-text-size-adjust:none;
								font-family:Open Sans, Arial, Helvetica, sans-serif;
							}
							body {
								margin:0;
								padding:0;
							}
							table {
								border-spacing:0;
							}
							table td {
								border-collapse:collapse;
							}
							.yshortcuts a {
								border-bottom: none !important;
							}
							</style>
							</head>
							<body>
							<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
							  <tr>
							    <td valign="top" style="background-color:#f2f2f2; text-align:center; padding:25px 0;"><a href="'.base_url().'"><img src="'.newsletter_logo($row->company_id).'" width="150" height="96" alt=" "></a></td>
							  </tr>
							  <tr>
							    <td style="padding:40px 0 50px;" valign="top" align="center"><table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
							        <tr>
							          <td valign="top" align="center"><table width="600px" style="width:600px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
							              <tr>
							                <td valign="top"><h1 style="font-family:Open Sans, Arial, Helvetica, sans-serif; font-weight:400; color:#2f3335; font-size:13px; margin:0 0 20px; line-height:22px;">Hello <strong>'.ucfirst($row->first_name).'</strong>,</h1>
							                  <p style="font-family:Open Sans, Arial, Helvetica, sans-serif; font-weight:400; color:#2f3335; font-size:13px; margin:0 0 10px; line-height:22px;">You have been invited to join <span style="text-decoration:underline;">'.$all_company.'</span> Employee Self Service Portal powered by Ashima.</p>
							                  <p style="font-family:Open Sans, Arial, Helvetica, sans-serif; font-weight:400; color:#2f3335; font-size:13px; margin:0 0 10px; line-height:22px;">To activate your account, please click on the link below and create your password:</p>
							                  <p style="font-family:Open Sans, Arial, Helvetica, sans-serif; font-weight:400; color:#2f3335; font-size:13px; margin:0 0 10px; line-height:22px;"><a style="text-decoration:none; color:#1172ad;" href="'.base_url("/users/credentials/change_pass/".$row->token).'"><span style="color:#1172ad !important;">'.base_url("/users/credentials/change_pass/".$row->token).'</span></a></p>
							                  <br>
							                  <br>
							                  <p style="font-family:Open Sans, Arial, Helvetica, sans-serif; font-weight:400; color:#2f3335; font-size:13px; margin:0 0 10px; line-height:18px;"> Kind Regards,<br>
							                    The Ashima Team</p>
							                  <br>
							                   <p style="font-family:Open Sans, Arial, Helvetica, sans-serif; font-weight:400; color:#2f3335; font-size:13px; margin:0 0 10px; line-height:18px;">Have questions?<br>
							                    Email us at <a style="color:#47b7e8; text-decoration:none;" href="mailto:ashima@konsum.ph"><span style="color:#47b7e8 !important;">ashima@konsum.ph</span></a> or contact us <a style="color:#47b7e8; text-decoration:none;" href="http://ashima.ph/ashima_web/pages/contact"><span style="color:#47b7e8 !important;">here</span></a>. Our customer support team is standing by ready to help.</p></td>
							              </tr>
							            </table></td>
							        </tr>
							      </table></td>
							  </tr>
							  <tr>
							    <td valign="top" align="center" style="background-color:#f2f2f2; padding:20px 0"><table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
							        <tr>
							          <td valign="top"><img src="'.base_url().'assets/theme_2015/images/newsletter/img-fb-twitter.jpg" alt=" " border="0" usemap="#Map"></td>
							          <td valign="middle" style="text-align:right;"><a href="http://www.konsumtech.ph/"><img src="'.base_url().'assets/theme_2015/images/newsletter/img-konsum-logo.png" alt=" "></a></td>
							        </tr>
							      </table></td>
							  </tr>
							</table>
							<map name="Map">
							  <area id="facebook-link" shape="rect" coords="-22,1,199,15" href="http://facebook.com/ashimaPH">
							  <area id="twitter-link" shape="rect" coords="1,15,275,42" href="http://twitter.com/ashimaPH">
							</map>
							</body>
							</html>';
				$CI->email->clear();
				$config['wordwrap'] = TRUE;
				$config['mailtype'] = 'html';
				$config['charset'] = 'utf-8';
				$CI->email->initialize($config);
				$CI->email->set_newline("\r\n");
				$CI->email->from('notifications@ashima.ph','Ashima Payroll');
				if($row->email !==""){
					$CI->email->to($row->email);
					$CI->email->subject('Account Completion');
					$CI->email->message($email_content);
					$email_check = $CI->email->send();
					$x = $CI->email->print_debugger();
					if($return_html == true){
						return $email_content;
					}else{
						return $x;
					}
				}else{
					return false;
				}
			}
		}
	
	}
	/** END AUTORESPONDER SA BRISTLEBACK **/
	
	/**
	 * GET EMPLOYEE DAILY RATES
	 * @param int $company_id
	 * @param int $account_id
	 * @return boolean or numbers
	 */
	function iemployee_daily_rate($company_id,$account_id,$get_total_workingdays = false){
		
		$CI =& get_instance();
		$where = array(
				'company_id'	=> $company_id,
				'status'		=> 'Active'
		);
		$payroll_calendar_working_days = get_table_info('payroll_calendar_working_days_settings', $where);
		$CI->db->where(
			array(
				'bpa.status'		=> 'Active',
				'e.status'			=> 'Active',
				'a.deleted'			=> '0',
				'a.account_id'		=> $account_id,
				'bpa.comp_id'	=> $company_id
			)
		);
		$CI->edb->select(
			array(
				'epi.rank_id',
				'a.account_id',
				'bpa.basic_pay_id',
				'bpa.emp_id',
				'bpa.comp_id',
				'bpa.current_basic_pay',
				'bpa.new_basic_pay',
				'bpa.effective_date',
				'bpa.adjustment_date',
				'bpa.reasons',
				'bpa.attachment',
				'bpa.status',
				'bpa.deleted',
				'epi.payroll_group_id',
				'epi.minimum_wage_earner'
			)
		);
		$CI->edb->join('employee AS e','e.account_id=a.account_id','INNER');
		$CI->edb->join('basic_pay_adjustment AS bpa','bpa.emp_id = e.emp_id','INNER');
		$CI->edb->join('employee_payroll_information AS epi','epi.emp_id=e.emp_id','INNER');
		$basic = $CI->edb->get('accounts AS a');
		$res_basic = $basic->row();
		
		if($payroll_calendar_working_days){
			if($payroll_calendar_working_days->enable_rank == 'No') {
				$total_working_days = $payroll_calendar_working_days  ? $payroll_calendar_working_days->working_days_in_a_year : 0;
				$total_wd_year = $total_working_days / 12;
				if($get_total_workingdays == true){
					return $total_working_days;
				}

				$real_salary = '';
				$total_daily_rate = 0;
				if($total_wd_year) {
					if($res_basic) {
						$real_basic_pay = $res_basic->current_basic_pay;
						$effective_date = idates_slash($res_basic->effective_date);
						$date_today = date("Y-m-d");
						if($effective_date && $effective_date !=="" && $effective_date !=="1970-01-01"){
							if($date_today >= $effective_date) {
								$real_basic_pay = $res_basic->new_basic_pay;
							}
						}
						$where_pg = array(
							'payroll_group_id'=>$res_basic->payroll_group_id,
							'status'=>'Active'
						);
						$get_payroll_group_schedule = get_table_info('payroll_group', $where_pg);
						
						if($get_payroll_group_schedule){
							if($get_payroll_group_schedule->pay_rate_type == 'By Month'){
								# KONG MONTHLY GALI MAO NI IYANG CALCULATION
								$total_daily_rate = $real_basic_pay / $total_wd_year;
								return $total_daily_rate ? number_format($total_daily_rate,2, '.', '') : 0;
							}else if($get_payroll_group_schedule->pay_rate_type == 'By Day'){
								# KONG BY DAY MAO SAD
								if($res_basic->minimum_wage_earner == 'yes'){ #  KONG BY DAY ATO SIYANG E CHECK SA Minimum wager earner ba siya
									$minimum_wage_earner_amount = 0;
									$where_mwds = array(
										'company_id'=>$company_id,
										'status'=>'Active'
									);
									$get_default_wage = get_table_info('minimum_wages_default_settings', $where_mwds);
									if($get_default_wage){
										$minimum_wage_earner_amount =  $get_default_wage->amount;
									}
									$where_mwss = array(
										'company_id'=>	$company_id,
										'status'=>'Active'
									);
									$get_wage_by_location = get_table_info('minimum_wages_settings', $where_mwss);
									if($get_wage_by_location){
										$where_company_loc = array(
											'company_id'=>$company_id,
											'status'=>'Active'
										);
										$company_location_base = get_table_info('company', $where_company_loc);
										if($company_location_base) { 
											$get_industry = $company_location_base->industry;
											switch($get_industry){
												case "Non-Agriculture":
													$minimum_wage_earner_amount = $get_wage_by_location->non_agriculture;
												break;
												case "Agriculture (plantation and non-plantation)":
													$minimum_wage_earner_amount = $get_wage_by_location->agriculture_plantation;
												break;
												case "Private Hospitals (bed capacity of 100 or less)":
													$minimum_wage_earner_amount = $get_wage_by_location->private_hospitals;
												break;
												case "Retail/Service Establishment (15 workers or less)":
													$minimum_wage_earner_amount = $get_wage_by_location->retail_and_service_establishments_with_morethan_ten_workers;
												break;
												case "manufacturing employing less than 10 workers":
													$minimum_wage_earner_amount = $get_wage_by_location->manufacturing_establishments;
												break;
											}
										}
									}
									return $minimum_wage_earner_amount;
								}else{
									return $real_basic_pay;
								}
							}else if($get_payroll_group_schedule->pay_rate_type == 'By Hour'){
								$averate_working_hours_per_day = $payroll_calendar_working_days->average_working_hours_per_day;
								#return ($real_basic_pay * $averate_working_hours_per_day) * .25;
								return ($real_basic_pay * $averate_working_hours_per_day);
							}
						}else{
							return 0; # temporary
						}
						### DAILY RATE 
						# $daily_rate = $real_basic_pay * .60; # TIMES SA DEMINIMIS DAILY PERCENTAGE
						
					} else {
						return 0; 
					}
				} else {
					return 0;
				}
			}elseif($payroll_calendar_working_days->enable_rank == 'Yes') {
				if($res_basic){
					$where = array(
						'status'=>'Active',
						'company_id'=>$company_id,
						'rank_id'=>$res_basic->rank_id
					);
					$rank_working_days = get_table_info('rank_working_days', $where);
					if($rank_working_days) {
						$total_working_days = $rank_working_days->total_working_days_in_a_year;
						$total_wd_year = $total_working_days / 12;
						if($get_total_workingdays == true){
							return $total_working_days;
						}
						$total_daily_rate = 0;
						if($total_wd_year) {
							if($res_basic) {
								$real_basic_pay = $res_basic->current_basic_pay;
								$effective_date = idates_slash($res_basic->effective_date);
								$date_today = date("Y-m-d");
								if($effective_date && $effective_date !=="" && $effective_date !=="1970-01-01"){
									if($date_today >= $effective_date) {
										$real_basic_pay = $res_basic->new_basic_pay;
									}
								}
								$where_pg = array(
										'payroll_group_id'=>$res_basic->payroll_group_id,
										'status'=>'Active'
								);
								$get_payroll_group_schedule = get_table_info('payroll_group', $where_pg);
								if($get_payroll_group_schedule){
									if($get_payroll_group_schedule->pay_rate_type == 'By Month'){
										$total_daily_rate = $real_basic_pay / $total_wd_year;
										return $total_daily_rate ? number_format($total_daily_rate,2, '.', '') : 0;
									}else if($get_payroll_group_schedule->pay_rate_type == 'By Day'){
										### BY RANK individual check the minimum wage
										if($res_basic->minimum_wage_earner == 'yes'){
											$minimum_wage_earner_amount = 0;
											$where_mwds = array(
												'company_id'=>$company_id,
												'status'=>'Active'
											);
											$get_default_wage = get_table_info('minimum_wages_default_settings', $where_mwds);
											if($get_default_wage){
												$minimum_wage_earner_amount =  $get_default_wage->amount;
											}
											$where_mwss = array(
												'company_id'=>	$company_id,
												'status'=>'Active'
											);
											$get_wage_by_location = get_table_info('minimum_wages_settings', $where_mwss);
											if($get_wage_by_location){
												$where_company_loc = array(
													'company_id'=> $company_id,
													'status'=> 'Active'
												);
												$company_location_base = get_table_info('company', $where_company_loc);
												if($company_location_base) {
													$get_industry = $company_location_base->industry;
													switch($get_industry){
														case "Non-Agriculture":
															$minimum_wage_earner_amount = $get_wage_by_location->non_agriculture;
														break;
														case "Agriculture (plantation and non-plantation)":
															$minimum_wage_earner_amount = $get_wage_by_location->agriculture_plantation;
														break;
														case "Private Hospitals (bed capacity of 100 or less)":
															$minimum_wage_earner_amount = $get_wage_by_location->private_hospitals;
														break;
														case "Retail/Service Establishment (15 workers or less)":
															$minimum_wage_earner_amount = $get_wage_by_location->retail_and_service_establishments_with_morethan_ten_workers;
														break;
														case "manufacturing employing less than 10 workers":
															$minimum_wage_earner_amount = $get_wage_by_location->manufacturing_establishments;
														break;
													}
												}
											}
											return $minimum_wage_earner_amount;
										}else{
											return $real_basic_pay;
										}
									}else if($get_payroll_group_schedule->pay_rate_type == 'By Hour'){
										$averate_working_hours_per_day = $payroll_calendar_working_days->average_working_hours_per_day;
										#return ($real_basic_pay * $averate_working_hours_per_day) * .25; backup lang
										return ($real_basic_pay * $averate_working_hours_per_day);
									}
								}else{
									return 0;# temporary
								}
								
							} else {
								return 0;
							}
						} else {
							return 0;
						}
					}else{
						return 0;
					}
				}else{
					return 0;
				}
			}
		} else {
			return 0;
		}
	}

	function deminimis_calculation($deminimis_id,$employee_daily_rates,$iplan,$iamount,$daily_rate,$rate_type,$credits,$company_id,$daily_rates){
		#$employee_daily_rates = iemployee_daily_rate($this->company_id, $cval->account_id);
		$CI =& get_instance();
		$flag_error = 0;
		$amount_erro_msg = "";
		$dailyrate_erro_msg = "";
		$total_exis = 0;
		$full_amount = 0;
		$deminimis_plan = "";
		$gov_define_amount = "";
		$deminimis_return = array();

		#############getting deminimis type##############
		$deminimis_type = "";
		$get_deminimis_type = array(
			'status'=>'Active',
			'deminimis_id'=>$deminimis_id,
			'company_id'=>$company_id,
		);
		$CI->db->where($get_deminimis_type);
		$demi_type = $CI->db->get("deminimis");
		if($demi_type->num_rows() > 0){
			$row_dt = $demi_type->row();
			$deminimis_type = $row_dt->deminimis_type;
		}
		###############################################

		if($deminimis_type=="Medical Cash Allowance"){
			if($iplan == "Monthly"){
				$amount_prescribe_by_law = "125";
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - 125);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Monthly";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
				}
				$deminimis_plan = "Monthly";
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Per Payroll"){
				$amount_prescribe_by_law = round(125 / 2);
				$gov_define_amount = $amount_prescribe_by_law;
				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Monthly";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
				}
				$deminimis_plan = "Per Payroll";
				//return $deminimis_return = array($iamount,$daily_rate,$full_amount,$gov_define_amount,$total_exis);
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "One-time"){
				$amount_prescribe_by_law = 750;
				$gov_define_amount = $amount_prescribe_by_law;
				if($rate_type =="Fixed Rate"){
					if($iamount > $amount_prescribe_by_law){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = "";
				}
				$deminimis_plan = "Annual";
				//return $deminimis_return = array($iamount,$daily_rate,$full_amount,$gov_define_amount,$total_exis);
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}
		}elseif($deminimis_type=="Rice Subsidy"){
			if($iplan == "Monthly"){
				$amount_prescribe_by_law ="1500"	;
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Monthly";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Monthly";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Per Payroll"){
				$amount_prescribe_by_law = (1500 / 2);
				$gov_define_amount = $amount_prescribe_by_law;
				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Per Payroll";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Per Payroll";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "One-time"){
				$amount_prescribe_by_law = (1500 * 12);
				$gov_define_amount = $amount_prescribe_by_law;
				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";

				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Annual";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}
		}elseif($deminimis_type=="Uniform Allowance"){
			if($iplan == "One-time"){
				$amount_prescribe_by_law = "5000";
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";

				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Annual";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Monthly"){
				$amount_prescribe_by_law = (5000 / 12);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Monthly";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Monthly";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Per Payroll"){
				$amount_prescribe_by_law = (5000 / 24);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Per Payroll";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Per Payroll";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}
		}elseif($deminimis_type=="Medical Assistance"){
			if($iplan == "One-time"){
				$amount_prescribe_by_law = "10000";
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";

				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Annual";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Monthly"){
				$amount_prescribe_by_law = (10000 / 12);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Monthly";

				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Monthly";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Per Payroll"){
				$amount_prescribe_by_law = (10000 / 24);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Per Payroll";

				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Per Payroll";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}
		}elseif($deminimis_type=="Laundry Allowance"){
			if($iplan == "Monthly"){
				$amount_prescribe_by_law = "300";
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Monthly";

				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Monthly";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Per Payroll"){
				$amount_prescribe_by_law = (300 / 2);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Per Payroll";

				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Per Payroll";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "One-time"){
				$amount_prescribe_by_law = (300 * 12);//"300";
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";

				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Annual";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}
		}elseif($deminimis_type=="Achievement Award"){
			if($iplan == "One-time"){
				$amount_prescribe_by_law = "10000";
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Annual";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Monthly"){
				$amount_prescribe_by_law = (10000 / 12);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Monthly";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Monthly";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Per Payroll"){
				$amount_prescribe_by_law = (10000 / 24);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Per Payroll";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Per Payroll";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}
		}elseif($deminimis_type=="Holiday Cash Gift"){
			if($iplan == "One-time"){
				$amount_prescribe_by_law = "5000";
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";

				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Annual";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Monthly"){
				$amount_prescribe_by_law = (5000 / 12);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Monthly";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Monthly";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Per Payroll"){
				$amount_prescribe_by_law = (5000 / 24);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Per Payroll";

				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Per Payroll";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}
		}elseif($deminimis_type=="Meal Allowance for Overtime and Night Shift"){
			$minimum_wage = "";
			$where = array(
				'status'=>'Active',
				'company_id'=>$company_id
			);
			$CI->db->where($where);
			$q = $CI->db->get("minimum_wages_default_settings");
			if($q->num_rows() > 0){
				$row = $q->row();
				$minimum_wage22 = $row->amount;
				$minimum_wage = $minimum_wage22;
			}else{
				$where2 = array(
					'status'=>'Active',
					'company_id'=>$company_id
				);
				$CI->db->where($where2);
				$q2 = $CI->db->get("minimum_wages_settings");

				if($q2->num_rows() > 0){
					$row2 = $q2->row();
					$minimum_wage33 = $row2->non_agriculture;
					$minimum_wage = $minimum_wage33;
				}
			}
			#############getting working days##############
			$total_working_days_in_a_year = "";
			$where_working_days = array(
				'status'=>'Active',
				'company_id'=>$company_id,
			);
			$CI->db->where($where_working_days);
			$working_d = $CI->db->get("payroll_calendar_working_days_settings");
			if($working_d->num_rows() > 0){
				$row_wd = $working_d->row();
				$total_working_days_in_a_year = $row_wd->working_days_in_a_year;
			}
			#####getting the 25% of daily minimum wage#####
			$custom_min_wage = ($minimum_wage * 0.25);
			######################end######################

			if($iplan == "One-time"){
				$amount_prescribe_by_law = ($custom_min_wage * $total_working_days_in_a_year);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Annual";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Monthly"){
				$working_days = round($total_working_days_in_a_year / 12);
				$amount_prescribe_by_law = ($custom_min_wage * $working_days);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Monthly";

				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Monthly";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Per Payroll"){
				$working_days = round(($total_working_days_in_a_year / 12) / 2);
				$amount_prescribe_by_law = ($custom_min_wage * $working_days);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Per Payroll";

				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $daily_rate;
					$deminimis_plan = "Per Payroll";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}

		}elseif($deminimis_type == "Monetized Used VL"){
			if($iplan == "One-time"){
				if($rate_type =="Fixed Rate"){

					$amount_prescribe_by_law = ($daily_rates * 10);
					$gov_define_amount = $amount_prescribe_by_law;
					if($iamount > $amount_prescribe_by_law){
						$total_exis = ($iamount - $amount_prescribe_by_law);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";

				}elseif($rate_type == "Credits"){
					$amount_prescribe_by_law = ($daily_rates * 10);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_emp_daily_rate = ($employee_daily_rates * $credits);

					if($final_emp_daily_rate > $amount_prescribe_by_law){
						$total_exis = ($final_emp_daily_rate - $amount_prescribe_by_law);
					}else{
						$total_exis = "0";
					}
					$full_amount = $final_emp_daily_rate;
					$deminimis_plan = "Annual";

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}
		}elseif($deminimis_type == "Monetized Unused VL"){
			if($iplan == "One-time"){
				if($rate_type =="Fixed Rate"){

					$amount_prescribe_by_law = ($daily_rates * 10);
					$gov_define_amount = $amount_prescribe_by_law;
					if($iamount > $amount_prescribe_by_law){
						$total_exis = ($iamount - $amount_prescribe_by_law);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";

				}elseif($rate_type == "Credits"){
					$amount_prescribe_by_law = ($daily_rates * 10);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_emp_daily_rate = ($employee_daily_rates * $credits);

					if($final_emp_daily_rate > $amount_prescribe_by_law){
						$total_exis = ($final_emp_daily_rate - $amount_prescribe_by_law);
					}else{
						$total_exis = "0";
					}
					$full_amount = $final_emp_daily_rate;
					$deminimis_plan = "Annual";

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}
		}
	}
	
	function deminimis_calculation_v2($deminimis_id,$employee_daily_rates,$iplan,$iamount,$daily_rate,$rate_type,$credits,$company_id,$daily_rates,$e_working_days = NULL, $final_emp_daily_rate,$payroll_schedule){
		#$employee_daily_rates = iemployee_daily_rate($this->company_id, $cval->account_id);
		$CI =& get_instance();
		$flag_error = 0;
		$amount_erro_msg = "";
		$dailyrate_erro_msg = "";
		$total_exis = 0;
		$full_amount = 0;
		$deminimis_plan = "";
		$gov_define_amount = "";
		$deminimis_return = array();
		
		#############getting deminimis type##############
		$deminimis_type = "";
		$get_deminimis_type = array(
			'status'=>'Active',
			'deminimis_id'=>$deminimis_id,
			'company_id'=>$company_id,
		);
		$CI->db->where($get_deminimis_type);
		$demi_type = $CI->db->get("deminimis");
		if($demi_type->num_rows() > 0){
			$row_dt = $demi_type->row();
			$deminimis_type = $row_dt->deminimis_type;
		}
		###############################################
		
		if($deminimis_type=="Medical Cash Allowance"){
			if($iplan == "Monthly"){
				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);

					$amount_prescribe_by_law = "125";
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				$deminimis_plan = "Monthly";
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Per Payroll"){
				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = 125;
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = (125 / 2);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_per_payroll;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				$deminimis_plan = "Per Payroll";
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "One-time"){
				$amount_prescribe_by_law = (750 * 2);
				$gov_define_amount = $amount_prescribe_by_law;

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * 1;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				$deminimis_plan = "Annual";
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Annually"){
				$amount_prescribe_by_law = (750 * 2);
				$gov_define_amount = $amount_prescribe_by_law;

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * 1;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				$deminimis_plan = "Annual";
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Daily"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = round(1500 / 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = round(1500 / 24);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_per_payroll;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}

				$deminimis_plan = "Annual";
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Weekly"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);
					$final_working_days_in_a_week = round($final_working_days_per_payroll / 2);

					$amount_prescribe_by_law = round(1500 / 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_week;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);
					$final_working_days_in_a_week = round($final_working_days_per_payroll / 2);

					$amount_prescribe_by_law = round(1500 / 24);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * ($final_working_days_in_a_month / $final_working_days_in_a_week);
					$final_employee_daily_rate = $final_emp_daily_rate * ($final_working_days_in_a_month / $final_working_days_in_a_week);

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $iamount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				$deminimis_plan = "Annual";
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}

		}elseif($deminimis_type=="Rice Subsidy"){
			if($iplan == "Monthly"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);

					$amount_prescribe_by_law = "1500";
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Per Payroll"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = "1500";
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = (1500 / 2);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_per_payroll;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "One-time"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$amount_prescribe_by_law = (1500 * 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * 1;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Annually"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$amount_prescribe_by_law = (1500 * 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * 1;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Weekly"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);
					$final_working_days_in_a_week = round($final_working_days_per_payroll / 2);

					$amount_prescribe_by_law = 1500;
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_week;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);
					$final_working_days_in_a_week = round($final_working_days_per_payroll / 2);

					$amount_prescribe_by_law = (1500 / 2);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * ($final_working_days_in_a_month / $final_working_days_in_a_week);
					$final_employee_daily_rate = $final_emp_daily_rate * ($final_working_days_in_a_month / $final_working_days_in_a_week);

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $iamount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Daily"){
				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = 1500;
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = round(1500 / 2);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_per_payroll;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}
		}elseif($deminimis_type=="Uniform Allowance"){
			if($iplan == "One-time"){
				$amount_prescribe_by_law = "5000";
				$gov_define_amount = $amount_prescribe_by_law;

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * 1;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Annually"){
				$amount_prescribe_by_law = "5000";
				$gov_define_amount = $amount_prescribe_by_law;

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * 1;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Monthly"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);

					$amount_prescribe_by_law = (5000 / 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Per Payroll"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$amount_prescribe_by_law = (5000 / 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount;
					$final_employee_daily_rate = $final_emp_daily_rate;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$amount_prescribe_by_law = (5000 / 24);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount;
					$final_employee_daily_rate = $final_emp_daily_rate;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Weekly"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);
					$final_working_days_in_a_week = round($final_working_days_per_payroll / 2);

					$amount_prescribe_by_law = (5000 / 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_week;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);
					$final_working_days_in_a_week = round($final_working_days_per_payroll / 2);

					$amount_prescribe_by_law = (5000 / 24);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * ($final_working_days_in_a_month / $final_working_days_in_a_week);
					$final_employee_daily_rate = $final_emp_daily_rate * ($final_working_days_in_a_month / $final_working_days_in_a_week);

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $iamount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Daily"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = (5000 / 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$tmp_amount_prescribe_by_law = (5000 / 12);
					$amount_prescribe_by_law = ($tmp_amount_prescribe_by_law / 2);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_per_payroll;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}

		}elseif($deminimis_type=="Medical Assistance"){
			if($iplan == "One-time"){
				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$amount_prescribe_by_law = "10000";
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * 1;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Annually"){
				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$amount_prescribe_by_law = "10000";
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * 1;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Monthly"){
				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);

					$amount_prescribe_by_law = (10000 / 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Per Payroll"){
				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = (10000 / 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = (10000 / 24);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_per_payroll;


					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Weekly"){
				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);
					$final_working_days_in_a_week = round($final_working_days_per_payroll / 2);

					$amount_prescribe_by_law = (10000 / 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_week;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);
					$final_working_days_in_a_week = round($final_working_days_per_payroll / 2);

					$amount_prescribe_by_law = (10000 / 24);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * ($final_working_days_in_a_month / $final_working_days_in_a_week);
					$final_employee_daily_rate = $final_emp_daily_rate * ($final_working_days_in_a_month / $final_working_days_in_a_week);

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $iamount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Daily"){
				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = (10000 / 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = (10000 / 24);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_per_payroll;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}

		}elseif($deminimis_type=="Laundry Allowance"){
			if($iplan == "Monthly"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);

					$amount_prescribe_by_law = "300";
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Per Payroll"){
				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = 300;
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = round(300 / 2);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_per_payroll;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "One-time"){
				$amount_prescribe_by_law = (300 * 12);
				$gov_define_amount = $amount_prescribe_by_law;

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * 1;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Annually"){
				$amount_prescribe_by_law = (300 * 12);
				$gov_define_amount = $amount_prescribe_by_law;

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * 1;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Weekly"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);
					$final_working_days_in_a_week = round($final_working_days_per_payroll / 2);

					$amount_prescribe_by_law = 300;
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_week;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);
					$final_working_days_in_a_week = round($final_working_days_per_payroll / 2);

					$amount_prescribe_by_law = round(300 / 2);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * ($final_working_days_in_a_month / $final_working_days_in_a_week);
					$final_employee_daily_rate = $final_emp_daily_rate * ($final_working_days_in_a_month / $final_working_days_in_a_week);

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $iamount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Daily"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = 300;
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = round(300 / 2);
					$gov_define_amount = round($amount_prescribe_by_law / $final_working_days_per_payroll);

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_per_payroll;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}

		}elseif($deminimis_type=="Achievement Award"){ ///no calculation yet
			if($iplan == "One-time"){
				$amount_prescribe_by_law = "10000";
				$gov_define_amount = $amount_prescribe_by_law;
				
				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = "";
					$deminimis_plan = "Annual";
				}elseif($rate_type == "Prorated By Days Worked" || $rate_type == "Prorated By Hours Worked"){
					$total_exis = "0";
					$full_amount = $iamount;
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Annually"){
				$amount_prescribe_by_law = "10000";
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = "";
					$deminimis_plan = "Annual";
				}elseif($rate_type == "Prorated By Days Worked" || $rate_type == "Prorated By Hours Worked"){
					$total_exis = "0";
					$full_amount = $iamount;
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Monthly"){
				$amount_prescribe_by_law = (10000 / 12);
				$gov_define_amount = $amount_prescribe_by_law;
				
				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Monthly";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = "";
					$deminimis_plan = "Monthly";
				}elseif($rate_type == "Prorated By Days Worked" || $rate_type == "Prorated By Hours Worked"){
					$total_exis = "0";
					$full_amount = $iamount;
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Per Payroll"){
				$amount_prescribe_by_law = (10000 / 24);
				$gov_define_amount = $amount_prescribe_by_law;
				
				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Per Payroll";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = "";
					$deminimis_plan = "Per Payroll";
				}elseif($rate_type == "Prorated By Days Worked" || $rate_type == "Prorated By Hours Worked"){
					$total_exis = "0";
					$full_amount = $iamount;
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Weekly"){
				$tmp_amount_prescribe_by_law = (10000 / 24);
				$amount_prescribe_by_law = ($tmp_amount_prescribe_by_law / 2);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Per Payroll";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = "";
					$deminimis_plan = "Per Payroll";
				}elseif($rate_type == "Prorated By Days Worked" || $rate_type == "Prorated By Hours Worked"){
					$total_exis = "0";
					$full_amount = $iamount;
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Daily"){
				$tmp_amount_prescribe_by_law = (10000 / 24);
				$amount_prescribe_by_law = ($tmp_amount_prescribe_by_law / 2);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Per Payroll";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = "";
					$deminimis_plan = "Per Payroll";
				}elseif($rate_type == "Prorated By Days Worked" || $rate_type == "Prorated By Hours Worked"){
					$total_exis = "0";
					$full_amount = $iamount;
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}
		}elseif($deminimis_type=="Holiday Cash Gift"){
			if($iplan == "One-time"){
				$amount_prescribe_by_law = "5000";
				$gov_define_amount = $amount_prescribe_by_law;

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * 1;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Annually"){
				$amount_prescribe_by_law = "5000";
				$gov_define_amount = $amount_prescribe_by_law;

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * 1;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Monthly"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);

					$amount_prescribe_by_law = (5000 / 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Per Payroll"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = (5000 / 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = (5000 / 24);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_per_payroll;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Weekly"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);
					$final_working_days_in_a_week = round($final_working_days_per_payroll / 2);

					$amount_prescribe_by_law = (5000 / 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_week;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);
					$final_working_days_in_a_week = round($final_working_days_per_payroll / 2);

					$amount_prescribe_by_law = (5000 / 24);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * ($final_working_days_in_a_month / $final_working_days_in_a_week);
					$final_employee_daily_rate = $final_emp_daily_rate * ($final_working_days_in_a_month / $final_working_days_in_a_week);

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $iamount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);

			}elseif($iplan == "Daily"){

				if($payroll_schedule == "First Payroll Of The Month" || $payroll_schedule == "Second Payroll Of The Month" || $payroll_schedule == "Last Payroll Of The Month"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = (5000 / 12);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_in_a_month;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";

					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}elseif($payroll_schedule == "Per Payroll - Split Equally"){
					$final_working_days_in_a_month = round($e_working_days / 12);
					$final_working_days_per_payroll = round($e_working_days / 24);

					$amount_prescribe_by_law = (5000 / 24);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_amount = $iamount * 1;
					$final_employee_daily_rate = $final_emp_daily_rate * $final_working_days_per_payroll;

					if($rate_type =="Fixed Rate"){
						if($final_amount > $gov_define_amount){
							$total_exis = ($final_amount - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_amount;
						$deminimis_plan = "Monthly";
					}elseif($rate_type == "Daily Rate"){
						if($final_employee_daily_rate > $gov_define_amount){
							$total_exis = ($final_employee_daily_rate - $gov_define_amount);
						}else{
							$total_exis = "0";
						}
						$full_amount = $final_employee_daily_rate;
					}elseif($rate_type == "Prorated By Days Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Days Worked";
					}elseif($rate_type == "Prorated By Hours Worked"){
						$total_exis = "0";
						$full_amount = $iamount;
						$deminimis_plan = "Prorated By Hours Worked";
					}

				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}
		}elseif($deminimis_type=="Meal Allowance for Overtime and Night Shift"){ //no calculation yet
			$minimum_wage = "";
			$where = array(
				'status'=>'Active',
				'company_id'=>$company_id
			);
			$CI->db->where($where);
			$q = $CI->db->get("minimum_wages_default_settings");
			if($q->num_rows() > 0){
				$row = $q->row();
				$minimum_wage22 = $row->amount;
				$minimum_wage = $minimum_wage22;
			}else{
				$where2 = array(
					'status'=>'Active',
					'company_id'=>$company_id
				);
				$CI->db->where($where2);
				$q2 = $CI->db->get("minimum_wages_settings");
				
				if($q2->num_rows() > 0){
					$row2 = $q2->row();
					$minimum_wage33 = $row2->non_agriculture;
					$minimum_wage = $minimum_wage33;
				}
			}
			#############getting working days##############
			$total_working_days_in_a_year = "";
			$where_working_days = array(
				'status'=>'Active',
				'company_id'=>$company_id,
			);
			$CI->db->where($where_working_days);
			$working_d = $CI->db->get("payroll_calendar_working_days_settings");
			if($working_d->num_rows() > 0){
				$row_wd = $working_d->row();
				$total_working_days_in_a_year = $row_wd->working_days_in_a_year;
			}
			#####getting the 25% of daily minimum wage#####
				$custom_min_wage = ($minimum_wage * 0.25);
			######################end######################
			
			if($iplan == "One-time"){
				$amount_prescribe_by_law = ($custom_min_wage * $total_working_days_in_a_year);
				$gov_define_amount = $amount_prescribe_by_law;
				
				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = "";
					$deminimis_plan = "Annual";
				}elseif($rate_type == "Prorated By Days Worked" || $rate_type == "Prorated By Hours Worked"){
					$total_exis = "0";
					$full_amount = $iamount;
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Annually"){
				$amount_prescribe_by_law = ($custom_min_wage * $total_working_days_in_a_year);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = "";
					$deminimis_plan = "Annual";
				}elseif($rate_type == "Prorated By Days Worked" || $rate_type == "Prorated By Hours Worked"){
					$total_exis = "0";
					$full_amount = $iamount;
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Monthly"){
				$working_days = round($total_working_days_in_a_year / 12);
				$amount_prescribe_by_law = ($custom_min_wage * $working_days);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Monthly";
					
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = "";
					$deminimis_plan = "Monthly";
				}elseif($rate_type == "Prorated By Days Worked" || $rate_type == "Prorated By Hours Worked"){
					$total_exis = "0";
					$full_amount = $iamount;
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Per Payroll"){
				$working_days = round(($total_working_days_in_a_year / 12) / 2);
				$amount_prescribe_by_law = ($custom_min_wage * $working_days);
				$gov_define_amount = $amount_prescribe_by_law;
				
				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Per Payroll";
						
				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = "";
					$deminimis_plan = "Per Payroll";
				}elseif($rate_type == "Prorated By Days Worked" || $rate_type == "Prorated By Hours Worked"){
					$total_exis = "0";
					$full_amount = $iamount;
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Weekly"){
				$tmp_working_days = round(($total_working_days_in_a_year / 12) / 24);
				$working_days = round($tmp_working_days / 2);
				$amount_prescribe_by_law = ($custom_min_wage * $working_days);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Per Payroll";

				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = "";
					$deminimis_plan = "Per Payroll";
				}elseif($rate_type == "Prorated By Days Worked" || $rate_type == "Prorated By Hours Worked"){
					$total_exis = "0";
					$full_amount = $iamount;
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}elseif($iplan == "Daily"){
				$tmp_working_days = round(($total_working_days_in_a_year / 12) / 24);
				$working_days = round($tmp_working_days / 2);
				$amount_prescribe_by_law = ($custom_min_wage * $working_days);
				$gov_define_amount = $amount_prescribe_by_law;

				if($rate_type =="Fixed Rate"){
					if($iamount > $gov_define_amount){
						$total_exis = ($iamount - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Per Payroll";

				}elseif($rate_type == "Daily Rate"){
					if($employee_daily_rates > $gov_define_amount){
						$total_exis = ($employee_daily_rates - $gov_define_amount);
					}else{
						$total_exis = "0";
					}
					$full_amount = "";
					$deminimis_plan = "Per Payroll";
				}elseif($rate_type == "Prorated By Days Worked" || $rate_type == "Prorated By Hours Worked"){
					$total_exis = "0";
					$full_amount = $iamount;
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}

		}elseif($deminimis_type == "Monetized Used VL"){ //no calculation yet
			if($iplan == "One-time" || $iplan == "Daily" || $iplan == "Weekly" || $iplan == "Semi-Annually" || $iplan == "Annually"){
				if($rate_type =="Fixed Rate"){

					$amount_prescribe_by_law = ($daily_rates * 10);
					$gov_define_amount = $amount_prescribe_by_law;
					if($iamount > $amount_prescribe_by_law){
						$total_exis = ($iamount - $amount_prescribe_by_law);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";

				}elseif($rate_type == "Credits"){
					$amount_prescribe_by_law = ($daily_rates * 10);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_emp_daily_rate = ($employee_daily_rates * $credits);

					if($final_emp_daily_rate > $amount_prescribe_by_law){
						$total_exis = ($final_emp_daily_rate - $amount_prescribe_by_law);
					}else{
						$total_exis = "0";
					}
					$full_amount = $final_emp_daily_rate;
					$deminimis_plan = "Annual";

				}elseif($rate_type == "Prorated By Days Worked" || $rate_type == "Prorated By Hours Worked"){
					$amount_prescribe_by_law = ($daily_rates * 10);
					$gov_define_amount = $amount_prescribe_by_law;
					if($iamount > $amount_prescribe_by_law){
						$total_exis = ($iamount - $amount_prescribe_by_law);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}
		}elseif($deminimis_type == "Monetized Unused VL"){ //no calculation yet
			if($iplan == "One-time" || $iplan == "Daily" || $iplan == "Weekly" || $iplan == "Semi-Annually" || $iplan == "Annually"){
				if($rate_type =="Fixed Rate"){

					$amount_prescribe_by_law = ($daily_rates * 10);
					$gov_define_amount = $amount_prescribe_by_law;
					if($iamount > $amount_prescribe_by_law){
						$total_exis = ($iamount - $amount_prescribe_by_law);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";

				}elseif($rate_type == "Credits"){
					$amount_prescribe_by_law = ($daily_rates * 10);
					$gov_define_amount = $amount_prescribe_by_law;

					$final_emp_daily_rate = ($employee_daily_rates * $credits);

					if($final_emp_daily_rate > $amount_prescribe_by_law){
						$total_exis = ($final_emp_daily_rate - $amount_prescribe_by_law);
					}else{
						$total_exis = "0";
					}
					$full_amount = $final_emp_daily_rate;
					$deminimis_plan = "Annual";

				}elseif($rate_type == "Prorated By Days Worked" || $rate_type == "Prorated By Hours Worked"){
					$amount_prescribe_by_law = ($daily_rates * 10);
					$gov_define_amount = $amount_prescribe_by_law;
					if($iamount > $amount_prescribe_by_law){
						$total_exis = ($iamount - $amount_prescribe_by_law);
					}else{
						$total_exis = "0";
					}
					$full_amount = $iamount;
					$deminimis_plan = "Annual";
				}
				return $deminimis_return = array("amount"=>$iamount,"daily_rate"=>$daily_rate,"credits"=>$credits,"full_amount"=>$full_amount,"gov_define_amount"=>$gov_define_amount,"total_exis"=>$total_exis);
			}
		}
	} 
	
	
	/**
	 * CONVERT TO CASH EMPLOYEE
	 * @param int $emp_id
	 * @param int $leave_type_id
	 * @param int $company_id
	 */
	function convert_to_cash_employeee($emp_id,$leave_type_id,$company_id,$from,$to){
		$CI =& get_instance();
		if($emp_id && $leave_type_id) {
			
			# GET OUR LEAVE TYPE DESCRIPTION
			$where_lt = array(
				'leave_type_id'	=> $leave_type_id,
				'company_id'	=> $company_id,
				'status'		=> 'Active'
			);
			$q_leave_type = get_table_info('leave_type', $where_lt);
			$leave_credits_accrual = $q_leave_type->leave_credits_accrual;
			
			# END GET OUR LEAVE TYPE DESCRIPTION
			
			$get_last_row_history = $CI->db->query("
				SELECT * from employee_leave_history where emp_id = '{$emp_id}' 
				AND leave_type_id = '{$leave_type_id}' AND status='Active' 
				AND date BETWEEN date('{$from}') AND date('{$to}')  
				order by employee_leave_history_id desc limit 1
			");
			
			$employee_last_row_date = $get_last_row_history->row();
			$from = date("Y-m-d",strtotime($from));
			$to = date("Y-m-d",strtotime($to));
			
			$where_el = array(
				'emp_id'		=> $emp_id,
				'leave_type_id'	=> $leave_type_id,
				'company_id' 	=> $company_id,
				'status'	 	=> 'Active'
			);
			$get_employee_leave = get_table_info('employee_leaves',$where_el);
			
		
			$remaining_balance = $get_employee_leave ? $get_employee_leave->remaining_leave_credits : '';
			if($employee_last_row_date) {
				$accrual_period = $q_leave_type->accrual_period; # FREQUENCY
				$leave_conversion_run = $q_leave_type->leave_conversion_run_every; # LEAVE CONVERSION
				
				$his_date = date("Y-m-d",strtotime($employee_last_row_date->date));
				
					if($accrual_period == 'annual' && $leave_conversion_run == 'annual') {
						
						if($from <= $his_date && $his_date <= $to) {
							
							$last_remaining_settings = $remaining_balance - $leave_credits_accrual;
							$last_remaining  =  $remaining_balance - $last_remaining_settings; 
								
							$field_uel = array(
									'previous_period_leave_balance'	=> $employee_last_row_date->previous_leave_credits,
									'emp_id'=>$emp_id,
									'leave_type_id'	 => $leave_type_id,
									'company_id' => $company_id,
									'status'	 => 'Active',
									'date'=>date("Y-m-d"),
									'scenario'=>'annual period  annual conversion'
							);
							esave("employee_leave_history",$field_uel);
								
							$el_up_where = array(
									'emp_id'=>$emp_id,
									'leave_type_id'	 => $leave_type_id,
									'company_id' => $company_id,
									'status'	 => 'Active'
							);
							$up_f = array(
									'remaining_leave_credits' => $last_remaining
							);
							eupdate('employee_leaves',$up_f,$el_up_where);
						}
						
					} else {
						
						#FIRST SCENERAIO if approved ang payrun schedule)
						if($from <= $his_date && $his_date <= $to) {
							
							$field_uel = array(
								'previous_period_leave_balance'	=> '0',# ATONG IBALIK SA SINUGDANAN5-1 0 zero siya kong nag pa run na ang convert to cash or forfeited
								'emp_id'=>$emp_id,
								'leave_type_id'	 => $leave_type_id,
								'company_id' => $company_id,
								'status'	 => 'Active',
								'date'=>date("Y-m-d"),
								'scenario'=>'first'
							);
							esave("employee_leave_history",$field_uel); 
							
							$el_up_where = array(
									'emp_id'=>$emp_id,
									'leave_type_id'	 => $leave_type_id,
									'company_id' => $company_id,
									'status'	 => 'Active'
							);
							$up_f = array(
									'remaining_leave_credits' => 0
							);
							eupdate('employee_leaves',$up_f,$el_up_where);
							
						}else{
							// May 26,2016 (if delayed pag approve sa payroll na abot nag 26)
							
							$last_remaining_settings = $remaining_balance - $leave_credits_accrual;
							$last_remaining  =  $remaining_balance - $last_remaining_settings;
							
							$field_uel = array(
								'previous_period_leave_balance'	=> $employee_last_row_date->previous_leave_credits,
								'emp_id'=>$emp_id,
								'leave_type_id'	 => $leave_type_id,
								'company_id' => $company_id,
								'status'	 => 'Active',
								'date'=>date("Y-m-d"),
									'scenario'=>'second last remaining'
							);
							esave("employee_leave_history",$field_uel);
							
							$el_up_where = array(
								'emp_id'=>$emp_id,
								'leave_type_id'	 => $leave_type_id,
								'company_id' => $company_id,
								'status'	 => 'Active'
							);
							$up_f = array(
								'remaining_leave_credits' => $last_remaining
							);
							eupdate('employee_leaves',$up_f,$el_up_where);
						}
					}	
				
			} else {
				
				/*
				$field_uel = array(
					'previous_period_leave_balance'	=> '0',# ATONG IBALIK SA SINUGDANAN5-1 0 zero siya kong nag pa run na ang convert to cash or forfeited
					'emp_id'=>$emp_id,
					'leave_type_id'	 => $leave_type_id,
					'company_id' => $company_id,
					'status'	 => 'Active',
					'date'=>date("Y-m-d")
				);
				esave("employee_leave_history",$field_uel);
				*/
				
			}
	    	// INGON DL SA LEAVE ACCRUED
		}
    	
	}
	
	/**
	 * ADD EMPLOYEEE HISTORY
	 * @param unknown $company_id
	 * @param unknown $leave_type_id
	 * @param unknown $emp_id
	 * @param unknown $employee_remaining_leave_credits
	 * @param unknown $today
	 */
	function add_employee_history($company_id,$leave_type_id,$emp_id,$employee_remaining_leave_credits,$today){
		if($leave_type_id !=="" && $emp_id !=="") {
			$_CI =& get_instance();
			
			$where = array(
				'leave_type_id'	=> $leave_type_id,
				'status'		=> 'Active',
				'emp_id'		=> $emp_id
			);
			$_CI->db->order_by('employee_leave_history_id','desc');
			$_CI->db->where($where);
			$q_history = $_CI->db->get('employee_leave_history',1);
			$q_history_row = $q_history->row();
			
			$previous_period_leave_balance = 0;
			$employee_remaining_leave_credits = $employee_remaining_leave_credits >= 0 ? $employee_remaining_leave_credits : 0;
			
			if($q_history_row) {
				
				$previous_period_leave_balance = $q_history_row->previous_period_leave_balance >=0 ? $q_history_row->previous_period_leave_balance : 0;	

				$formula_results = $previous_period_leave_balance - $employee_remaining_leave_credits;

				# updated nako pag july 6 2016 kay tanaw nako bug ni siya kay kong previous kay 6 nya - nimog 0 6 japon 
				
				$flag = 'no'; 
				/*
				if($previous_period_leave_balance > $employee_remaining_leave_credits){
					$formula_results = $employee_remaining_leave_credits - $previous_period_leave_balance;
					$flag = "emp leave credits $employee_remaining_leave_credits - previous $previous_period_leave_balance";
				}else{
					$formula_results = $previous_period_leave_balance - $employee_remaining_leave_credits;
					$flag = "emp previous $previous_period_leave_balance - employee leave credits $employee_remaining_leave_credits";
				}
				
				
				if($previous_period_leave_balance == 0){
					if($employee_remaining_leave_credits >= $previous_period_leave_balance){
						$formula_results = $employee_remaining_leave_credits - $previous_period_leave_balance;
						$flag = 'condition 1';
					}
				}
				
				if($employee_remaining_leave_credits <=0){
					$formula_results = 0;
					$flag = 'condition 2';
				}
				
				# end updated value
				
				$elh_field = array(
					'company_id'	=> $company_id,
					'leave_type_id'	=> $leave_type_id,
					'emp_id'		=> $emp_id,
					'previous_period_leave_balance'	=> $formula_results >=0 ? $formula_results :0 ,
					'date'			=> $today,
				
					'scenario'		=> 'module from approval leave model - Apply leave history prev('.$previous_period_leave_balance.')  -  remaining('.$employee_remaining_leave_credits.') flag ('.$flag.') total ('.$formula_results.')'
				);
				esave("employee_leave_history",$elh_field);
				*/
				
				$elh_field = array(
						'company_id'	=> $company_id,
						'leave_type_id'	=> $leave_type_id,
						'emp_id'		=> $emp_id,
						'previous_period_leave_balance'	=> ($employee_remaining_leave_credits > 0 ? $employee_remaining_leave_credits : 0),
						'date'			=> $today,
						'scenario'		=> 'GI KUHA RA NATO ANG REMAINING'
				);
				esave("employee_leave_history",$elh_field);
				
			}else{
				$elh_field = array(
					'company_id'	=> $company_id,
					'leave_type_id'	=> $leave_type_id,
					'emp_id'		=> $emp_id,
					'previous_period_leave_balance'	=> $employee_remaining_leave_credits,
					'date'			=> $today,
					'scenario'		=> 'module from approval leave model - Apply leave history2'
				);
				esave("employee_leave_history",$elh_field);
			}
		}
	}
	
	/* added: fritz -> start here */
	function count_all_todo_pending_split_timein($emp_id, $company_id){
		$_CI =& get_instance();
		$konsum_key = konsum_key();
		$filter = filter_split_timein($emp_id, $company_id);
		$where = array(
				'sbti.comp_id'   => $company_id,
				'sbti.status'   => 'Active',
				'sbti.corrected' => 'Yes',
				'sbti.time_in_status' => 'pending'
	
	
		);
		$where2 = array(
				"at.level !=" => ""
		);
	
		$_CI->db->select('count(*) as val');
	
		$_CI->edb->where($where);
		$_CI->db->where($where2);
		if($filter != FALSE){
			$_CI->db->where("sbti.schedule_blocks_time_in_id NOT IN ({$filter})");
		}
			
		$_CI->edb->join('employee AS e','e.emp_id = sbti.emp_id','left');
		$_CI->edb->join('employee_payroll_information AS epi','epi.emp_id = sbti.emp_id','left');
		$_CI->edb->join('accounts AS a','a.account_id = e.account_id','left');
		$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$_CI->edb->join("approval_time_in AS at","at.approval_time_in_id= sbti.approval_time_in_id","LEFT");
		$_CI->db->join("employee_sched_block AS esb","sbti.schedule_blocks_id = esb.schedule_blocks_id","LEFT");
		$_CI->db->join("schedule_blocks AS sb","sb.work_schedule_id = esb.work_schedule_id","LEFT");
		$_CI->db->join("work_schedule AS ws","ws.work_schedule_id = esb.work_schedule_id","LEFT");
		$_CI->db->group_by("schedule_blocks_time_in_id");
		$query = $_CI->edb->get('schedule_blocks_time_in AS sbti');
	
		$row = $query->row();
	
		return ($row) ? $query->num_rows : 0;
	}
	
	function filter_split_timein($emp_id, $company_id){
		$_CI =& get_instance();
		$konsum_key = konsum_key();
		$where = array(
				'sbti.comp_id'   => $company_id,
				'sbti.status'   => 'Active',
				'sbti.corrected' => 'Yes',
				'sbti.time_in_status' => 'pending'
	
		);
		$where2 = array(
				"at.level !=" => ""
		);
	
	
		$_CI->edb->where($where);
		$_CI->db->where($where2);
		$_CI->edb->join('employee AS e','e.emp_id = sbti.emp_id','left');
		$_CI->edb->join('employee_payroll_information AS epi','epi.emp_id = sbti.emp_id','left');
		$_CI->edb->join('accounts AS a','a.account_id = e.account_id','left');
		$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$_CI->edb->join("approval_time_in AS at","at.approval_time_in_id= sbti.approval_time_in_id","LEFT");
		$_CI->db->group_by("schedule_blocks_time_in_id");
		$query = $_CI->edb->get('schedule_blocks_time_in AS sbti');
		$result = $query->result();
	
		$arrs = array();
		if($result){
			$is_assigned = TRUE;
				
			foreach($result as $key => $approvers){
				if($approvers->flag_add_logs == 0){
					$appr_grp = $approvers->attendance_adjustment_approval_grp;
				}elseif($approvers->flag_add_logs == 1){
					$appr_grp = $approvers->add_logs_approval_grp;
				}elseif($approvers->flag_add_logs == 2){
					$appr_grp = $approvers->location_base_login_approval_grp;
				}
					
				$level = $approvers->level;
					
				$check = check_assigned_group($appr_grp, $level);
				//	echo $emp->employee_time_in_id.' - '. $emp->approval_time_in_id.' - '.$check.'</br>';
					
				if($check){
	
				}else{
					array_push($arrs, $approvers->schedule_blocks_time_in_id);
				}
			}
	
		}
		$string = implode(",", $arrs);
		return $string;
	}
	
	function no_shifts_assigned_list($company_id,$emp_id,$limit=1000000,$start=0) {
		$total = $total_ws = $total_active_emp = $total_assigned = 0;
		$work_schedule_available = array();
		$employee_assigned = array();
		
		$query_default_sched = manager_employee_default_schedule($company_id,$emp_id);
		$query_sched = manager_employee_custom_shifts($company_id,$emp_id);
		
		if($query_default_sched) {
			foreach ($query_default_sched as $rows) {
				$temp_arr = array(
						"emp_id" => $rows->emp_id
				);
				array_push($work_schedule_available, $rows->work_schedule_id);
				array_push($employee_assigned, $temp_arr);
			}
		}
		
		if($query_sched) {
			foreach ($query_sched as $rows) {
				$temp_arr = array(
						"emp_id" => $rows->emp_id
				);
				array_push($work_schedule_available, $rows->work_schedule_id);
				array_push($employee_assigned, $temp_arr);
			}
		}
		$manager_active_employees = manager_active_employees($company_id,$emp_id,$limit,$start);
		
		$new_res_arr = array();
		foreach($manager_active_employees as $mae){
			$temp_res_arr = array(
					"emp_id" => $mae->emp_id,
					#"last_name" => $mae->last_name,
					#"first_name" => $mae->first_name
			);
			
			array_push($new_res_arr, $temp_res_arr);
		}
		
		$new_test = $employee_assigned;
		
		foreach($new_res_arr as $aV){
			$aTmp1[] = $aV['emp_id'];
		}
		
		foreach($new_test as $aV){
			$aTmp2[] = $aV['emp_id'];
		}
		
  		$arr = array_diff($aTmp1,$aTmp2);
  		
  		$result = array();
  		foreach ($arr as $row) {
  			$employees_no_shifts = employees_no_shifts($company_id,$emp_id,$row);
  			
  			if($employees_no_shifts) {
  				$temp_res = array(
  						"emp_id" => $employees_no_shifts->emp_id,
						"last_name" => $employees_no_shifts->last_name,
						"first_name" => $employees_no_shifts->first_name,
  						"company_id" => $employees_no_shifts->company_id,
  						"payroll_cloud_id" => $employees_no_shifts->payroll_cloud_id,
  						"account_id" => $employees_no_shifts->account_id
  				);
  				
  				array_push($result, (object) $temp_res);
  			}
  		}
  		
		return $result;
	}
	
	function manager_active_employees($company_id,$emp_id,$limit = 1000000, $start = 0) {
	
		$CI =& get_instance();
		$wr_epi = array(
				"epi.status" => 'Active',
				"e.company_id" => $company_id,
				"epi.employee_status" => 'Active',
				"a.user_type_id" => "5",
				"edrt.parent_emp_id" => $emp_id
		);
		
		$sel = array(
				"e.emp_id",
				#"e.last_name",
				#"e.first_name",
		);
		
		$CI->edb->select($sel);
		
		$CI->db->where($wr_epi);
		$CI->db->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
		$CI->db->join("employee_payroll_information AS epi", "epi.emp_id = e.emp_id", "LEFT");
		$CI->db->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
	
		$q_unass = $CI->edb->get("employee AS e",$limit,$start);
		$qr = $q_unass->result();
		return ($qr) ? $qr : false;
	
	}
	
	function employees_no_shifts($company_id,$manage_emp_id,$emp_id) {
	
		$CI =& get_instance();
		$wr_epi = array(
				"epi.status" => 'Active',
				"e.company_id" => $company_id,
				"epi.employee_status" => 'Active',
				"a.user_type_id" => "5",
				"edrt.parent_emp_id" => $manage_emp_id,
				"e.emp_id" => $emp_id
		);
	
		$sel = array(
				"e.emp_id",
				"e.last_name",
				"e.first_name",
				"a.account_id",
				"e.company_id",
				"a.payroll_cloud_id"
		);
	
		$CI->edb->select($sel);
	
		$CI->db->where($wr_epi);
		$CI->db->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
		$CI->db->join("employee_payroll_information AS epi", "epi.emp_id = e.emp_id", "LEFT");
		$CI->db->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
	
		$q_unass = $CI->edb->get("employee AS e");
		$qr = $q_unass->row();
		return ($qr) ? $qr : false;
	
	}
	
	/**
	 * Count employees on the particular company
	 * Enter description here ...
	 * @param int $company_id
	 * @param int
	 */
	function icount_employees_for_manager($company_id,$emp_id){
		$CI =& get_instance();
		$where = array(
				'e.company_id'=>$company_id,
				'e.status'=>'Active',
				'a.deleted'=>'0',
				'a.user_type_id'=>'5',
				'epi.employee_status'=>'Active',
				"edrt.parent_emp_id" => $emp_id,
						
		);
		$CI->edb->where($where);
		$CI->edb->join('employee_payroll_information AS epi','epi.emp_id=e.emp_id','INNER');
		$CI->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","INNER");
		$CI->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
		$q = $CI->edb->get('employee AS e');
		#last_query();
		$row = $q->num_rows();
		
		return $row;
	}
	
	function missed_punched_for_manager($emp_id,$company_id, $sDate = "", $eDate = "") {
		$_CI =& get_instance();
		
		$_CI->load->model('employee_model','employee');
		$_CI->load->model('emp_manager_dashboard_model','emdm');
		$_CI->load->model('employee_work_schedule_model','ews');
		
		if($sDate == "" && $eDate == "") {
			$sDate = date("Y-m-d");
			$eDate = date("Y-m-d");
		}
		
		$dateRange = dateRange($sDate, $eDate);
		
		$new_res = array();
		foreach ($dateRange as $new_date) {
			$get_all_employees = $_CI->emdm->get_all_employees($emp_id, $company_id);
			if($get_all_employees){
				foreach($get_all_employees as $row){
					#if(manager_count_missed_punches($row->emp_id, $row->company_id) > 0){
						$period = next_pay_period($row->emp_id, $row->company_id);
						if($period){
							//$today = date('Y-m-d', strtotime('-1 day'));
							$date = date('Y-m-d', strtotime($new_date)); //date('Y-m-d', strtotime('-1 day')); 
			
							$count = 0;
							$work_sched_id = check_employee_work_schedule($date, $row->emp_id, $row->company_id)->work_schedule_id;
							$rest_day = $_CI->ews->get_rest_day($row->company_id,$work_sched_id,date('l',strtotime($date)));
							$check_holiday = $_CI->employee->get_holiday_date($date,$row->emp_id,$row->company_id);
								
							$work_schedule_info = $_CI->ews->work_schedule_info($row->company_id,$work_sched_id,date('l',strtotime($date)));
							$leave = $_CI->ews->check_employee_leave_application($date, $row->emp_id);
							$leave_check = ($leave) ? TRUE: FALSE;
							if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
								$time_in = check_time_in_date($date, $row->emp_id);
								if($time_in){
									if(!$rest_day && !$check_holiday){
										if($time_in->time_in == NULL) $count = $count + 1;
										if(!$rest_day && !$check_holiday){
											if($work_schedule_info['work_schedule']['break_time'] != 0){
												if($time_in->lunch_out == NULL)  $count = $count + 1;
												if($time_in->lunch_in == NULL)  $count = $count + 1;
											}
										}
										if($time_in->time_out == NULL)  $count = $count + 1;
									}
			
									if($count > 0){
										$result = array(
												"emp_id"				=> $row->emp_id,
												"account_id" 			=> $row->account_id,
												"company_id" 			=> $row->company_id,
												"first_name" 			=> $row->first_name,
												"last_name" 			=> $row->last_name,
												"payroll_cloud_id"		=> $row->payroll_cloud_id,
												"date" 					=> $date,
												"time_in"				=> $time_in->time_in,
												"lunch_out"				=> $time_in->lunch_out,
												"lunch_in"				=> $time_in->lunch_in,
												"time_out"				=> $time_in->time_out,
												"tardiness_min"			=> $time_in->tardiness_min,
												"undertime_min"			=> $time_in->undertime_min,
												"total_hours"			=> $time_in->total_hours,
												"total_hours_required"	=> $time_in->total_hours_required,
												"profile_image" 		=> $row->profile_image,
												"base_url"				=> base_url()
										);
											
										array_push($new_res, $result);
											
									}
								}
								/*else{
									if(!$rest_day && !$check_holiday){
										$result = array(
												"emp_id"			=> $row->emp_id,
												"account_id" 		=> $row->account_id,
												"company_id" 		=> $row->company_id,
												"first_name" 		=> $row->first_name,
												"last_name" 		=> $row->last_name,
												"payroll_cloud_id"	=> $row->payroll_cloud_id,
												"date" 				=> $date,
												"time_in"				=> "",
												"lunch_out"				=> "",
												"lunch_in"				=> "",
												"time_out"				=> "",
												"tardiness_min"			=> "",
												"undertime_min"			=> "",
												"total_hours"			=> 0,
												"total_hours_required"	=> 0
										);
											
										array_push($new_res, $result);											
									}
								}*/
							}
						}
					#}
				}
			}
		}
		
		return $new_res;
	}
	
	function missing_logs_for_manager($emp_id,$company_id, $sDate = "", $eDate = "") {
		$_CI =& get_instance();
		
		$_CI->load->model('employee_model','employee');
		$_CI->load->model('emp_manager_dashboard_model','emdm');
		$_CI->load->model('employee_work_schedule_model','ews');
		
		if($sDate == "" && $eDate == "") {
			$sDate = date("Y-m-d");
			$eDate = date("Y-m-d");
		}
		
		$dateRange = dateRange($sDate, $eDate);
		
		$new_res = array();
		foreach ($dateRange as $new_date) {
			$get_all_employees = $_CI->emdm->get_all_employees($emp_id, $company_id);
			if($get_all_employees) {
				foreach ($get_all_employees as $row) {
					
					$date = date('Y-m-d', strtotime($new_date)); //date('Y-m-d', strtotime('-1 day'));
					$count = 0;
					$work_sched_id = $_CI->employee->check_employee_work_schedule($date, $row->emp_id, $row->company_id)->work_schedule_id;
					$rest_day = $_CI->ews->get_rest_day($row->company_id,$work_sched_id,date('l',strtotime($date)));
					$check_holiday = $_CI->employee->get_holiday_date($date,$row->emp_id,$row->company_id);
					$work_schedule_info = $_CI->ews->work_schedule_info($row->company_id,$work_sched_id,date('l',strtotime($date)));
					$leave = $_CI->ews->check_employee_leave_application($date, $row->emp_id);
					$leave_check = ($leave) ? TRUE: FALSE;
					
					if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
						#p($work_schedule_info);
						$time_in = check_time_in_date($date, $row->emp_id);
							
						if(!$time_in){
							 if(!$rest_day && !$check_holiday){
							 	$result = array(
							 			"account_id" 		=> $row->account_id,
							 			"emp_id" 			=> $row->emp_id,
							 			"company_id" 		=> $row->company_id,
							 			"first_name" 		=> $row->first_name,
							 			"last_name" 		=> $row->last_name,
							 			"payroll_cloud_id"	=> $row->payroll_cloud_id,
							 			"date" 				=> $date,
							 			"profile_image" 	=> $row->profile_image
							 	);
							 	
							 	array_push($new_res, $result);
							 }
						
						}
					}
				}
			}
		}
		
		return $new_res;
		
	}
	
	function dateRange($first, $last, $step = '+1 day', $format = 'Y-m-d' ) { 
	
	    $dates = array();
	    $current = strtotime($first);
	    $last = strtotime($last);
	
	    while( $current <= $last ) { 
	
	        $dates[] = date($format, $current);
	        $current = strtotime($step, $current);
	    }
	
	    return $dates;
	}
	
	function check_if_timein_is_required($emp_id,$company_id) {
		$_CI =& get_instance();
		
		$w = array(
				"emp_id" => $emp_id,
				"company_id" => $company_id
		);
	
		$_CI->db->where($w);
		$q = $_CI->edb->get("employee_payroll_information");
		$r = $q->row();
	
		return ($r) ? $r->timesheet_required : FALSE ;
	}
	
	function if_not_required_to_Login($emp_id,$company_id) {
		$_CI =& get_instance();
		
		$_CI->load->model('employee_model','employee');
		$_CI->load->model('emp_login_model','elm');
		
		$emp_no = $_CI->employee->check_emp_no($emp_id,$company_id);
		
		$currentdate = date('Y-m-d');
		$vx = $_CI->elm->activate_nightmare_trap($company_id,$emp_no);
		
		if($vx){
			$currentdate = $vx['currentdate'];
		}
		
		$work_schedule_id = $_CI->employee->emp_work_schedule($emp_id,$company_id,$currentdate);
		
		$w = array(	
				"ess.emp_id" => $emp_id,
				"ess.company_id" => $company_id,
				"fh.work_schedule_id" => $work_schedule_id,
				"ess.valid_from" => $currentdate,
				"ess.until" => $currentdate,
				"fh.payroll_group_id" => 0,
				"fh.not_required_login" => 1
		);
		
		$_CI->db->where($w);
		$_CI->db->join('employee_shifts_schedule AS ess','ess.work_schedule_id = fh.work_schedule_id','LEFT');
		$q = $_CI->db->get("flexible_hours AS fh");
		$r = $q->row();
		
		if($r) {
			return ($r) ? $r : FALSE ;
		} else {
			$w1 = array(
					"epi.emp_id" => $emp_id,
					"pg.company_id" => $company_id,
					"fh.work_schedule_id" => $work_schedule_id,
					"pg.status" => "Active",
					"fh.not_required_login" => 1
			);
			
			$_CI->db->where($w1);
			$_CI->db->join('flexible_hours AS fh','fh.work_schedule_id = pg.work_schedule_id','LEFT');
			$_CI->db->join('employee_payroll_information AS epi','epi.payroll_group_id = pg.payroll_group_id','LEFT');
			$q1 = $_CI->db->get("payroll_group AS pg");
			$r1 = $q1->row();
			
			return ($r1) ? $r1 : FALSE ;
		}
		
	}
	
	function next_shift_v2($emp_id, $comp_id,$check_employee_work_schedule_v2,$work_schedule_id_pg){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$CI->load->model('employee_model','employee');
		$period = next_pay_period_v2($emp_id, $comp_id);
		//$date = date('Y-m-d', strtotime($period->cut_off_from));
		$date = date('Y-m-d');
		$today = date('Y-m-d');
		$shift = array();
		
		if($period){
			/*$date = date('Y-m-d', strtotime($period->cut_off_from." -1 day"));
			$today = date('Y-m-d', strtotime('-1 day'));
			
			if(strtotime($today) > strtotime($period->cut_off_to)){
				$today = date('Y-m-d', strtotime($period->cut_off_to));
			}*/
			
			#$cut_off_date = dateRange($date, $today);
			
			#foreach ($cut_off_date as $all_date) {
				#$date = $all_date;
				$work_sched_id1 = in_array_custom("date-{$date}", $check_employee_work_schedule_v2);
				if($work_sched_id1) {
					$work_sched_id = $work_sched_id1->work_schedule_id;
				} else {
					$work_sched_id = $work_schedule_id_pg;
				}
				if(!$work_sched_id){
					// return false; break;
				}
				$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime(date('Y-m-d', strtotime($date.' +1 day')))));
				$check_holiday = $CI->employee->get_holiday_date(date('Y-m-d', strtotime($date.' +1 day')),$emp_id,$comp_id);
				if(!$rest_day && !$check_holiday){
					$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime(date('Y-m-d', strtotime($date.' +1 day')))));
						
					if(!$work_schedule_info) return false;//break;
					$leave = $CI->ews->check_employee_leave_application(date('Y-m-d', strtotime($date.' +1 day')), $emp_id);
					$leave_check = ($leave) ? TRUE: FALSE;
					if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
						if(strtotime(date('Y-m-d', strtotime($date.' +1 day'))) > strtotime($today)){
							$shift = array(
									'shift_name' => $work_schedule_info['work_schedule']['shift_name'],
									'shift_date' => date('Y-m-d', strtotime($date.' +1 day')),
									'start_time' => $work_schedule_info['work_schedule']['start_time'],
									'end_time'   => $work_schedule_info['work_schedule']['end_time'],
									'flexible'	 => $work_schedule_info['work_schedule']['flexible'],
									'required'	 => $work_schedule_info['work_schedule']['required']
							);
							//break;
						}
					}
				}
			#}
		}
		return ($shift) ? $shift : FALSE;
	}
	
	function count_missed_punches_v2($emp_id, $comp_id,$check_employee_work_schedule_v2,$work_schedule_id_pg){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$CI->load->model('employee_model','employee');
	
		$period =  next_pay_period_v2($emp_id, $comp_id);
		$count = 0;
		$count_fritz = 0;
		if($period){
			$date = date('Y-m-d', strtotime($period->cut_off_from." -1 day"));
			$today = date('Y-m-d', strtotime('-1 day'));
			
			if(strtotime($today) > strtotime($period->cut_off_to)){
				$today = date('Y-m-d', strtotime($period->cut_off_to));
			}
			
			$cut_off_date = dateRange($date, $today);
			
			foreach ($cut_off_date as $all_date) {
				$date = $all_date;
				$work_sched_id1 = in_array_custom("date-{$date}", $check_employee_work_schedule_v2);
				if($work_sched_id1) {
					$work_sched_id = $work_sched_id1->work_schedule_id;
				} else {
					$work_sched_id = $work_schedule_id_pg;
				}
				
				$is_break_assumed = is_break_assumed($work_sched_id);
	
				if(!$work_sched_id){
					return false; break;
				}
				$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
				$check_holiday = $CI->employee->get_holiday_date($date,$emp_id,$comp_id);
	
				$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
				if(!$work_schedule_info) break;
				$leave = $CI->ews->check_employee_leave_application($date, $emp_id);
				$leave_check = ($leave) ? TRUE: FALSE;
				if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
					$time_in = check_time_in_date_v2($date, $emp_id);
						
					if($time_in){
						if(!$rest_day && !$check_holiday){
							if($time_in->time_in == NULL) $count = $count + 1;
							if(!$rest_day && !$check_holiday){
								if($work_schedule_info['work_schedule']['break_time'] != 0){
									if($time_in->lunch_out == NULL)  $count = $count + 1;
									if($time_in->lunch_in == NULL)  $count = $count + 1;
								}
							}
							if($time_in->time_out == NULL)  $count = $count + 1;
						}
	
						if($count > 0){
							if($time_in->time_in == NULL) $count_fritz = $count_fritz + 1;
							if(!$rest_day && !$check_holiday){
								#if($is_break_assumed) {
								if($work_schedule_info['work_schedule']['break_time'] != 0){
									if($is_break_assumed) {
										if($is_break_assumed->break_rules != "assumed") {
											if($time_in->lunch_out == NULL)  $count_fritz = $count_fritz + 1;
											if($time_in->lunch_in == NULL)  $count_fritz = $count_fritz + 1;
										}
									}
								}
								#}
								}
									
							if($time_in->time_out == NULL)  $count_fritz = $count_fritz + 1;
						}
					}
				}
	
				#$date = date('Y-m-d', strtotime($date.' +1 day'));
			}
		}
		return $count_fritz;
	}
	
	function rejected_timesheet_v2($emp_id, $comp_id,$check_employee_work_schedule_v2,$work_schedule_id_pg){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$period = next_pay_period_v2($emp_id, $comp_id);
		$count = 0;
		if($period){
			$date = date('Y-m-d', strtotime($period->cut_off_from));
			$today = date('Y-m-d');
			if(strtotime($today) > strtotime($period->cut_off_to)){
				$today = date('Y-m-d', strtotime($period->cut_off_to));
			}
			
			$cut_off_date = dateRange($date, $today);
				
			foreach ($cut_off_date as $all_date) {
				$date = $all_date;
				$work_sched_id1 = in_array_custom("date-{$date}", $check_employee_work_schedule_v2);
				if($work_sched_id1) {
					$work_sched_id = $work_sched_id1->work_schedule_id;
				} else {
					$work_sched_id = $work_schedule_id_pg;
				}
				if(!$work_sched_id){
					return false; break;
				}
				$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
				if(!$rest_day){
					$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
					if(!$work_schedule_info) break;
					$leave = $CI->ews->check_employee_leave_application($date, $emp_id);
					$leave_check = ($leave) ? TRUE: FALSE;
					if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
						$where = array(
								'time_in_status'=>'reject',
								'date'=> $date,
								'emp_id' =>$emp_id,
								'status' => 'Active'
						);
						#$CI->db->select('count(*)');
						$CI->db->where($where);
						$q = $CI->db->get('employee_time_in');
						$r = $q->row();
						if($r){
							$count = $count + 1;
						}
					}
				}
				#$date = date('Y-m-d', strtotime($date.' +1 day'));
			}
		}
		return $count;
	}
	
	function pending_timesheet_v2($emp_id, $comp_id,$check_employee_work_schedule_v2,$work_schedule_id_pg){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$count = 0;
		$period = next_pay_period($emp_id, $comp_id);
		
		if($period){
			$date = date('Y-m-d', strtotime($period->cut_off_from));
			$today = date('Y-m-d');
			if(strtotime($today) > strtotime($period->first_payroll_date)){
				$today = date('Y-m-d', strtotime($period->first_payroll_date));
			}
				
			$cut_off_date = dateRange($date, $today);
				
			foreach ($cut_off_date as $all_date) {
				$date = $all_date;
				$work_sched_id1 = in_array_custom("date-{$date}", $check_employee_work_schedule_v2);
				if($work_sched_id1) {
					$work_sched_id = $work_sched_id1->work_schedule_id;
				} else {
					$work_sched_id = $work_schedule_id_pg;
				}
				if(!$work_sched_id){
					return false; break;
				}
				$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
				if(!$rest_day){
					$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
					$leave = $CI->ews->check_employee_leave_application($date, $emp_id);
					$leave_check = ($leave) ? TRUE: FALSE;
					if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){	
						$where = array(
								'time_in_status'=>'pending',
								'date'=> $date,
								'emp_id' =>$emp_id,
								'status' => 'Active'
						);
						#$CI->db->select('count(*)');
						$CI->db->where($where);
						$q = $CI->db->get('employee_time_in');
						$r = $q->row();
						if($r){
							$count = $count + 1;
						}
					}
				}
				#$date = date('Y-m-d', strtotime($date.' +1 day'));
			}
		}
		return $count;
	}
	
	function missing_timesheet_v2($emp_id, $comp_id,$check_employee_work_schedule_v2,$work_schedule_id_pg){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$CI->load->model('employee_model','employee');
		$period = next_pay_period_v2($emp_id, $comp_id);
		$count = 0;
		$get_holiday_date_v2 = $CI->employee->get_holiday_date_v2($emp_id,$comp_id);
		$get_rest_day_v2 = $CI->employee->get_rest_day_v2($comp_id);
		
		if($period){
			$today = date('Y-m-d', strtotime($period->cut_off_from." -1 day"));
			$date = date('Y-m-d', strtotime('-1 day'));
			if(strtotime($date) > strtotime($period->first_payroll_date)){
				$date = date('Y-m-d', strtotime($period->first_payroll_date));
			}
				
			$cut_off_date = dateRange($today,$date);
			
			foreach ($cut_off_date as $all_date) {
				$date = $all_date;
				
				$work_sched_id1 = in_array_custom("date-{$date}", $check_employee_work_schedule_v2);
				if($work_sched_id1) {
					$work_sched_id = $work_sched_id1->work_schedule_id;
				} else {
					$work_sched_id = $work_schedule_id_pg;
				}
								
				if(!$work_sched_id){
					return false; break;
				}
	
				$rest_day_date = date('l',strtotime($date));
				$rest_day = in_array_custom("date-{$rest_day_date}{$work_sched_id}", $get_rest_day_v2); //$CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
				$check_holiday = in_array_custom("date-{$date}", $get_holiday_date_v2); //$CI->employee->get_holiday_date($date,$emp_id,$comp_id);
				if(!$rest_day && !$check_holiday){
					$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
					$leave = $CI->ews->check_employee_leave_application($date, $emp_id);
					$leave_check = ($leave) ? TRUE: FALSE;
						
					if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
	
						// fritz here
						$time_in = check_time_in_date_v2($date, $emp_id);
						if(!$time_in){
							if(!$rest_day && !$check_holiday){
								$count = $count + 1;
							}
						}
					}
				}
	
				#$date = date('Y-m-d', strtotime($date.' -1 day'));
			}
		}
		return $count;
	}
	
	function count_absences_v2($emp_id, $comp_id,$check_employee_work_schedule_v2,$work_schedule_id_pg){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$CI->load->model('employee_model','employee');
		$period = next_pay_period_v2($emp_id, $comp_id);
		
		$count = 0;
		if($period){
			$date = date('Y-m-d', strtotime($period->cut_off_from));
			$today1 = date('Y-m-d');
			$today = date('Y-m-d', strtotime($today1." -1 day"));
			if(strtotime($today) > strtotime($period->first_payroll_date)){
				$today = date('Y-m-d', strtotime($period->first_payroll_date));
			}
				
			$cut_off_date = dateRange($date, $today);
			
			foreach ($cut_off_date as $all_date) {
				$date = $all_date;
				$work_sched_id1 = in_array_custom("date-{$date}", $check_employee_work_schedule_v2);
				if($work_sched_id1) {
					$work_sched_id = $work_sched_id1->work_schedule_id;
				} else {
					$work_sched_id = $work_schedule_id_pg;
				}
				
				if(!$work_sched_id){
					return false; break;
				}
				$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
				$check_holiday = $CI->employee->get_holiday_date($date,$emp_id,$comp_id);
				if(!$rest_day && !$check_holiday){
					$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
					$leave = $CI->ews->check_employee_leave_application($date, $emp_id);
					$leave_check = ($leave) ? TRUE: FALSE;
					if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
	
						// fritz here
						$time_in = check_time_in_date_v2($date, $emp_id);
						if(!$time_in){
							if(!$rest_day && !$check_holiday){
								//$count = $count + 1;
								$count = $count + $work_schedule_info['work_schedule']['total_hours'];
							}
						}
					}
				}
				#$date = date('Y-m-d', strtotime($date.' +1 day'));
					
			}
		}
		return ($count == 0) ? $count : number_format(($count),2,'.',',');
	}
	
	function count_tardiness_v2($emp_id, $comp_id){
		$CI =& get_instance();
	
		$CI->load->model('employee_model','employee');
	
		$period = next_pay_period_v2($emp_id, $comp_id);
		$count = 0;
		if($period){
			$date = date('Y-m-d', strtotime($period->cut_off_from));
			$today = date('Y-m-d');
			if(strtotime($today) > strtotime($period->first_payroll_date)){
				$today = date('Y-m-d', strtotime($period->first_payroll_date));
			}
			
			$cut_off_date = dateRange($date, $today);
			
			foreach ($cut_off_date as $all_date) {
				$date = $all_date;
				$r = check_time_in_date_v2($date, $emp_id);
				/*$where = array(
						'date'=> $date,
						'emp_id' =>$emp_id,
						'status' => 'Active'
				);
				$CI->db->where($where);
				$q = $CI->db->get('employee_time_in');
				$r = $q->row();*/
				if($r){
						
					$check_employee_leave_application = $CI->employee->check_employee_leave_application($r->emp_id,$date);
					
					if($check_employee_leave_application) {
						if($check_employee_leave_application["info"]["tardiness"] != "") {
							$tardi_with_leave = ($r->tardiness_min) - ($check_employee_leave_application["info"]["credited"] * 8) * 60;
								
						} else {
							$tardi_with_leave = $r->tardiness_min;
						}
					} else {
						$tardi_with_leave = $r->tardiness_min;
					}
					
						
					$count = $count + $tardi_with_leave;
				}
			}
		}
		return ($count == 0) ? $count : number_format(($count/60),2,'.',',');
	}
	
	function count_undertime_v2($emp_id, $comp_id){
		$CI =& get_instance();
	
		$CI->load->model('employee_model','employee');
	
		$period = next_pay_period_v2($emp_id, $comp_id);
		$count = 0;
		if($period){
			$date = date('Y-m-d', strtotime($period->cut_off_from." -1 day"));
			$today = date('Y-m-d');
			/*if(strtotime($today) > strtotime($period->cut_off_to)){
			 $today = date('Y-m-d', strtotime($period->cut_off_to));
			}*/
				
			if(strtotime($today) > strtotime($period->first_payroll_date)){
				$today = date('Y-m-d', strtotime($period->first_payroll_date));
			}
				
			$cut_off_date = dateRange($date, $today);
			
			foreach ($cut_off_date as $all_date) {
				$date = $all_date;
				$r = check_time_in_date_v2($date, $emp_id);
				/*$where = array(
						'date'=> $date,
						'emp_id' =>$emp_id,
						'status' => 'Active'
				);
				$CI->db->where($where);
				$q = $CI->db->get('employee_time_in');
				$r = $q->row();*/
				
				if($r){
					
					$check_employee_leave_application = $CI->employee->check_employee_leave_application($r->emp_id,$date);
					if($check_employee_leave_application) {
						if($check_employee_leave_application["info"]["undertime"] != "") {
							$under_with_leave = ($r->undertime_min) - ($check_employee_leave_application["info"]["credited"] * 8) * 60;
						
						} else {
							$under_with_leave = $r->undertime_min;
						}
					} else {
						$under_with_leave = $r->undertime_min;
					}
					
					$count = $count + $under_with_leave;
				}
				#$date = date('Y-m-d', strtotime($date.' +1 day'));
			}
		}
		return ($count == 0) ? $count : number_format(($count / 60),2,'.',',');
	}
	
	function get_payslips_v2($emp_id, $comp_id){
		$CI =& get_instance();
		$hw_array = array();
		
		$where = array(
				"emp_id"=>$emp_id,
				"company_id"=>$comp_id
		);
		$CI->edb->where($where);
		$CI->db->order_by('payroll_date','DESC');
		$q = $CI->edb->get("payroll_payslip");
		$r = $q->result();
	
		if($r) {
			 foreach($r as $row){
		 		$wd = array(
		 				"payroll_date" => $row->payroll_date,
		 				"period_from" => $row->period_from,
		 				"period_to" => $row->period_to,
		 				"company_id" => $row->company_id,
		 				"payroll_payslip_id" => $row->payroll_payslip_id
		 		);
		 	
		 		array_push($hw_array,$wd);
			 }
			 
			 return $hw_array;
		} else {
			return false;
		}
	}
	
	function check_employee_work_schedule_else($emp_id) {
		$CI =& get_instance();
		$res = array();
		
		$s = array(
				"pg.work_schedule_id"
		);
			
		$w = array(
				'epi.emp_id'=> $emp_id
		);
			
		$CI->db->select($s);
		$CI->db->where($w);
		$CI->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','LEFT');
		$q_pg = $CI->edb->get('employee_payroll_information AS epi');
		$r_pg = $q_pg->row();
			
		return ($r_pg) ? $r_pg: FALSE;
	}
	
	function check_employee_work_schedule_v2($emp_id,$company_id){
		$CI =& get_instance();
		$res = array();
		$s = array(
				"work_schedule_id",
				"valid_from",
				"until"
		);
		
		$w = array(
				"emp_id"=>$emp_id,
				"company_id"=>$company_id,
				#"valid_from <="=>$flag_date,
				#"until >="=>$flag_date,
				"status"=>"Active",
				"payroll_group_id" => 0
		);
		
		$CI->db->select($s);
		$CI->db->where($w);
		$q = $CI->db->get("employee_shifts_schedule");
		$r = $q->result();
		if($r){
			foreach ($r as $row) {
				$temp = array(
						"work_schedule_id" => $row->work_schedule_id,
						"valid_from" => $row->valid_from,
						"until"	=> $row->until,
						"filter_query" => "date-{$row->valid_from}"
				);
				
				array_push($res, $temp);
			}
			return $res;
		}
	}
	
	function check_time_in_date_v2($date, $emp_id){
		$CI =& get_instance();
		
		$where = array(
				'date'=> $date,
				'emp_id' =>$emp_id,
				'status' => 'Active'
		);
		$CI->db->where($where);
		$q = $CI->db->get('employee_time_in');
		$r = $q->row();
		
		return ($r) ? $r : false;
	}
	
	function next_pay_period_v2($emp_id = "", $comp_id){
		$CI =& get_instance();
		$today = date('Y-m-d');
		$get_payslips = get_payslips_v2($emp_id, $comp_id);
		$sel = array(
				"cut_off_from",
				"first_payroll_date",
				"cut_off_to"
		);
		
		$where = array(
				'cut_off_from <='=> $today,
				'first_payroll_date >='=> $today,
				'company_id' => $comp_id
		);
		
		$CI->db->select($sel);
		$CI->db->where($where);
		$q = $CI->db->get('payroll_calendar');
		$r = $q->row();
		
		if($r){
			$date = $r->first_payroll_date;
			$payslips = in_array_custom("",$get_payslips);
			
			$flag = false;
			if($payslips){
				$count = 0;
				foreach($payslips as $pay){
					$payroll_date = $pay->payroll_date;
					$period_from = $pay->period_from;
					$period_to = $pay->period_to;
					
					$check_approval_date = in_array_custom("",check_approval_date($payroll_date, $period_from, $period_to, $comp_id));
					if($check_approval_date){
						if(date('Y-m-d', strtotime($pay->payroll_date)) == date('Y-m-d', strtotime($date))){
							$flag = true;
							$today = date('Y-m-d', strtotime($pay->payroll_date.' +1 day'));
							break;
						}
					}
				}
			}
				
				
			if($flag){
				$where1 = array(
						'cut_off_from <='=> $today,
						'first_payroll_date >='=> $today,
						'company_id' => $comp_id
				);
				$CI->db->where($where1);
				$q1 = $CI->db->get('payroll_calendar');
				$r1 = $q1->row();
				
				return ($r1) ? $r1 : FALSE;
			}else{
				
				return $r;
			}
		}else{
			return FALSE;
		}
	}
	
	/* added: fritz -> end here */

	/*added by priest*/
	function manager_employee_expired_work_schedule($company_id,$emp_id,$limit = 10000) {

		$CI =& get_instance();

		$sel = array("ess.emp_id","a.account_id", 'MAX(ess.until) AS last_date', "DATE_ADD(MAX(ess.until), INTERVAL -7 DAY) AS date_to_notify");
		$sel2 = array(
			'*'
		);
		$wr = array(
			"ess.company_id" => $company_id,
			"ess.payroll_group_id" => "0",
			"edrt.parent_emp_id" => $emp_id
		);
		$CI->db->select($sel);
		$CI->edb->select($sel2);
		$CI->db->where($wr);
		$CI->edb->join("employee AS e", "e.emp_id = ess.emp_id", "LEFT");
		$CI->edb->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
		$CI->db->join("work_schedule AS ws", "ws.work_schedule_id = ess.work_schedule_id", "LEFT");
		$CI->db->join("employee_details_reports_to AS edrt","edrt.emp_id = ess.emp_id","LEFT");
		$CI->db->group_by("ess.emp_id","ess.emp_id");
		$query = $CI->edb->get("employee_shifts_schedule AS ess",$limit);
		$row = $query->result();
		return ($row) ? $row : false;

	}

	function manager_employee_default_schedule($company_id,$emp_id) {

		$CI =& get_instance();
		$sel_epi = array(
			"epi.emp_id",
			"pg.work_schedule_id",
			"pg.payroll_group_id"
		);
		$wr_pg = array(
			"epi.status" => 'Active',
			"e.company_id" => $company_id,
			"epi.employee_status" => 'Active',
			'a.user_type_id' => '5',
			"edrt.parent_emp_id" => $emp_id
		);

		$CI->db->select($sel_epi);
		$CI->db->where($wr_pg);
		$CI->db->where("pg.work_schedule_id IS NOT NULL AND epi.payroll_group_id IS NOT NULL");
		$CI->db->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
		$CI->db->join("employee_payroll_information AS epi", "epi.emp_id = e.emp_id", "LEFT");
		$CI->db->join("payroll_group AS pg", "pg.payroll_group_id = epi.payroll_group_id", "INNER");
		$CI->db->join("work_schedule AS ws", "ws.work_schedule_id = pg.work_schedule_id", "LEFT");
		$CI->db->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
		$CI->db->group_by("e.emp_id");
		$q_epi = $CI->db->get("employee AS e");
		$query_sched = $q_epi->result();

		return ($q_epi->num_rows() > 0) ? $query_sched : false;

	}

	function manager_employee_custom_shifts($company_id,$emp_id) {

		$CI =& get_instance();
		$todays_date = date("Y-m-d");
		$wr = array(
			"e.company_id" => $company_id,
			"ess.payroll_group_id" => 0,
			"ess.valid_from" => $todays_date,
			"epi.employee_status" => "Active",
			"a.user_type_id" => "5",
			"edrt.parent_emp_id" => $emp_id
		);
		$sel = array("ess.work_schedule_id", "ess.emp_id");
		$CI->db->select($sel);
		$CI->db->where($wr);
		$CI->db->join("employee AS e", "e.emp_id = ess.emp_id", "LEFT");
		$CI->db->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
		$CI->db->join("employee_payroll_information AS epi", "epi.emp_id = e.emp_id", "LEFT");
		$CI->db->join("employee_details_reports_to AS edrt","edrt.emp_id = ess.emp_id","LEFT");
		$CI->db->group_by("ess.emp_id");
		$q = $CI->db->get("employee_shifts_schedule AS ess");
		$query_shifts = $q->result();
		return ($q->num_rows() > 0) ? $query_shifts : false;

	}

	function manager_total_active_employees($company_id,$emp_id) {

		$CI =& get_instance();
		$wr_epi = array(
			"epi.status" => 'Active',
			"e.company_id" => $company_id,
			"epi.employee_status" => 'Active',
			"a.user_type_id" => "5",
			"edrt.parent_emp_id" => $emp_id
		);
		$CI->db->select("count(*) as total");
		$CI->db->where($wr_epi);
		$CI->db->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
		$CI->db->join("employee_payroll_information AS epi", "epi.emp_id = e.emp_id", "LEFT");
		$CI->db->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");

		$q_unass = $CI->db->get("employee AS e");
		$qr = $q_unass->row();
		return ($qr) ? $qr->total : "0";

	}
	/*added by priest end here*/
	
	/** BOGARRT **/
	
	/**
	 * HAS FOR NIGHTLY
	 * @param int $emp_id
	 * @param int $company_id
	 * @return number
	 */
	function is_fortnightly($emp_id,$company_id){
		$_CI =& get_instance();
		if($emp_id){
			
			
			
			$where = array(
				'e.status'=>'Active',
				'epi.status'=>'Active',
				'pg.status'=>'Active',
				'e.company_id'=>$company_id,
				'e.emp_id'=>$emp_id,
				#'pc.status'=>'Active'
				'pg.period_type'=>'Fortnightly',
				'epi.entitled_to_deminimis'=>'yes'
			);
			$select = array(
				'e.emp_id','pg.period_type'
			);
		
			$_CI->edb->select($select);
			$_CI->edb->where($where);
			$_CI->edb->join('employee_payroll_information AS epi','epi.emp_id = e.emp_id','INNER');
	
			$_CI->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','INNER');
			#$_CI->edb->join('payroll_calendar AS pc','pc.payroll_group_id= pg.payroll_group_id','INNER');
				
			$q = $_CI->edb->get('employee AS e');
			$r = $q->result();
			return $r ? true :  false;
			
			# ATO LANG SA E OFF
			#return false;
			
		}else{
			return 0;
		}
		
	}

	function check_for_fortnightly($emp_id,$company_id){
		$_CI =& get_instance();
		if($emp_id){
			$where = array(
				'e.status'=>'Active',
				'epi.status'=>'Active',
				'pg.status'=>'Active',
				'e.company_id'=>$company_id,
				'e.emp_id'=>$emp_id,
				'pg.period_type'=>'Fortnightly'
			);
			$select = array(
				'e.emp_id','pg.period_type'
			);

			$_CI->edb->select($select);
			$_CI->edb->where($where);
			$_CI->edb->join('employee_payroll_information AS epi','epi.emp_id= e.emp_id','INNER');
			$_CI->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','INNER');
			$q = $_CI->edb->get('employee AS e');
			$r = $q->result();
			return $r ? true :  false;
		}else{
			return 0;
		}

	}
	
	/**
	 * GET EMPLOYEE ENTITLEMENTS
	 * @param ints $emp_id
	 */
	function employee_entitlements($emp_id,$company_id){
		$_CI =& get_instance();
		if($emp_id){
			$where = array(
				'e.status'=>'Active',
				'epi.status'=>'Active',
				'e.company_id'=>$company_id,
				'e.emp_id'=>$emp_id
			);
			$_CI->edb->where($where);
			$select = array(
					'e.emp_id','pg.period_type'
			);
			$_CI->edb->join('employee_payroll_information AS epi','epi.emp_id= e.emp_id','INNER');
			$q = $_CI->edb->get('employee AS e');
			$r = $q->row();
			return $r;
		}else{
			return false;
		}
	}
	
	/**
	 * HAS DEMINIS APPLICABLE FOR
	 * @param int $emp_id
	 * @param int $deminimis_id
	 * @param int $company_id
	 * @return boolean
	 */
	function has_deminis_applicable_for($emp_id,$deminimis_id,$company_id){
		$_CI =& get_instance();
		$where_d = array(
			'deminimis_id'=>$deminimis_id,
			'company_id'=>$company_id,
			'status'=>'Active'
		);
		$_CI->db->where($where_d);
		$q_dem = $_CI->db->get('deminimis');
		$q_row = $q_dem->row();
		$q_dem->free_result();
		if($q_row){
			$applicable_for = $q_row->applicable_for;
			
			$where_epi = array(
				'e.status'=>'Active',
				'e.company_id'=>$company_id,
				'e.emp_id'=>$emp_id
			);
			
			$_CI->edb->where($where_epi);
			$select = array(
				'e.emp_id',
				'epi.rank_id',
				'epi.payroll_group_id',
				'epi.employment_type',
				'epi.department_id'
			);
			$_CI->edb->select($select);
			$_CI->edb->join('employee_payroll_information AS epi','epi.emp_id = e.emp_id','LEFT');
			$q_employee = $_CI->edb->get('employee AS e');
			$employee = $q_employee->row();
			#echo $_CI->db->last_query()."<br /><br />";
			$q_employee->free_result();
			$is_applicable = false;
			$trigger = '';
			$dtype = $q_row->deminimis_type;
			
				
			if($applicable_for =='All Employee'){
				$is_applicable = true;
				
				$trigger = 'all employee type('.$dtype.")<br />";
			}
				$where_cat = array(
					'deminimis_id'=>$deminimis_id,
					'status'=>'Active'
				);
				$deminimis_settings = get_table_info_all('deminimis_settings_applicablefor_category', $where_cat);
				
				if($deminimis_settings){
					
					foreach($deminimis_settings as $ds_k=>$ds_v){
						
						$applicable_for_id = $ds_v->applicable_for_id;
						
						if($applicable_for == 'Payroll Group'){
							if($applicable_for_id == $employee->payroll_group_id){
								$is_applicable = true;
							}
							
							$trigger = 'payroll group ('.$applicable_for_id.') payroll group ('.$employee->payroll_group_id.') type'.$dtype.'<br />';
						}
						if($applicable_for == 'Rank'){
							if($applicable_for_id == $employee->rank_id){
								$is_applicable = true;
							}
							$trigger =  'rank group ('.$applicable_for_id.') rank group ('.$employee->rank_id.')<br />';
						}
						
						if($applicable_for == 'Department'){
							if($applicable_for_id == $employee->department_id){
								$is_applicable = true;
							}
							$trigger =  'Department group ('.$applicable_for_id.') Department group ('.$employee->department_id.'  type'.$dtype.')<br />';
						}
						
						if($applicable_for == 'Employment Type'){
							if($applicable_for_id == $employee->employment_type){
								$is_applicable = true;
							}
							
							$trigger =  'Employment Type group ('.$applicable_for_id.') Employment Type group ('.$employee->employment_type.'  type'.$dtype.')<br />';
						}
						
					}
				}
				#echo "test".$trigger;
			return $is_applicable;
		}
	}
	
	/**
	 * 
	 * @param unknown $company_id
	 * @return number
	 */
	function deminis_v2_is_enable(){
		$company = whose_company();
		if($company) {
			$company_id = $company->company_id;
			$_CI =& get_instance();
			$where_d = array(
				'company_id'=>$company_id,
				'status'=>'Active',
				'effective_start_date !='=>''
			);
			$_CI->db->where($where_d);
			$q_dem = $_CI->db->get('deminimis');
			$q_row = $q_dem->row();
			$q_dem->free_result();
			return $q_row ? 1 : 0;
		}else{
			return 0;
		}
	}
	
	
	/** END BOGART **/
	
	/** Start - Fritz **/
	function check_if_employee_manager($emp_id, $company_id) {
		$CI =& get_instance();
		if($emp_id) {
			$where = array(
					"parent_emp_id" => $emp_id,
					"company_id" => $company_id
			);
			
			$CI->db->where($where);
			$q = $CI->db->get('employee_details_reports_to');
			$row = $q->result();
			$q->free_result();
			return $row ? true : false;
		} else {
			return false;
		}
	}
	
	function get_all_employee_direct_reports($emp_id, $company_id, $limit = 1000000) {
		$CI =& get_instance();
		
		if($emp_id) {
			$sel = array(
					'a.profile_image',
					'a.account_id'
			);
			
			$where = array(
					"edrt.parent_emp_id" => $emp_id,
					"edrt.company_id" => $company_id
			);
			
			$CI->db->select($sel);
			$CI->db->where($where);
			$CI->db->join("employee AS e","e.emp_id = edrt.emp_id","INNER");
			$CI->db->join("accounts AS a","a.account_id = e.account_id","INNER");
			$q = $CI->db->get('employee_details_reports_to AS edrt', $limit);
			$row = $q->result();
			$q->free_result();
			return ($row) ? $row : false;
		} else {
			return false;
		}
	}
	
	
	/**
	 * Thumbnail Image
	 */
	function thumb_pic_direct_reports($account_id,$company_id, $noImg ="/assets/theme_2015/images/img-user-avatar-dummy.jpg")
	{
		$CI =& get_instance();
		$path = base_url()."uploads/companies/".$company_id."/";
	
	
		if(is_numeric($account_id)) {
				
			$wr = array(
					"a.account_id" => $account_id
			);
			$CI->edb->where($wr);
			$CI->edb->join("employee AS e","e.account_id = a.account_id","LEFT");
			$q = $CI->edb->get("accounts AS a");
			$r = $q->row();
				
			if($r->profile_image) {
				$imgPath = $path.$r->profile_image;
			}else{
				//$imgPath = base_url().$noImg;
				$f_name = substr($r->first_name, 0,1);
				$last_name = substr($r->last_name, 0,1);
				$company_name = $CI->uri->segment(1);
	
				$imgPath = "/{$company_name}/avatar/default_image/img/10/26/26/{$f_name}{$last_name}.png";
				//$imgPath = $noImg;
			}
				
			return $imgPath;
		}
	
	}
	
	
	function emp_manager_total_shifts_summary($filter="", $company_id, $emp_id) {
		
		$total = $total_ws = $total_active_emp = $total_assigned = 0;
		$work_schedule_available = array();
		$employee_assigned = array();
		
		$query_default_sched = manager_employee_default_schedule($company_id,$emp_id);
		$query_sched = manager_employee_custom_shifts($company_id,$emp_id);
		
		if($query_default_sched) {
			foreach ($query_default_sched as $rows) {
				array_push($work_schedule_available, $rows->work_schedule_id);
				array_push($employee_assigned, $rows->emp_id);
			}
		}
		
		if($query_sched) {
			foreach ($query_sched as $rows) {
				array_push($work_schedule_available, $rows->work_schedule_id);
				array_push($employee_assigned, $rows->emp_id);
			}
		}
		
		if($filter == "schedule_available") {
			$total_ws = total_work_schedule($company_id);
			$total = $total_ws - count(array_unique($work_schedule_available));
		}
		
		if($filter == "assigned_shifts") {
			$total = count(array_unique($employee_assigned));		
		}
		
		if($filter == "unassigned_shifts") {
			$total_active_emp = manager_total_active_employees($company_id,$emp_id);
			$total_assigned = count(array_unique($employee_assigned));
			$total = $total_active_emp - $total_assigned;
		}
		
		return $total;
		
	}
	
	
	function manager_count_absent_employees($company_id,$emp_id){
		$_CI =& get_instance();
		$today = date("Y-m-d");
		// regular schedule
		$where = array(
				"e.company_id" => $company_id,
				"e.status" => "Active",
				"epi.employee_status" => "Active",
				"epi.timesheet_required" => "yes",
				"epi.status" => "Active",
				"edrt.parent_emp_id" => $emp_id
		);
		$_CI->db->where($where);
		$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
		$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$_CI->edb->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
		$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$_CI->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$_CI->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
		$_CI->db->group_by("e.emp_id");
		$query = $_CI->edb->get("employee AS e");
		$result = $query->result();
		$counter = 0;
	
		if($result){
			foreach($result as $row){
				$emp_id = $row->emp_id;
				$custom_sched = check_employee_custom_schedule($row->emp_id);
				$work_schedule_id = ($custom_sched) ? $custom_sched->work_schedule_id : $row->work_schedule_id ;
				$workday = get_workday($company_id,$work_schedule_id);
					
				if($workday != FALSE){
					switch ($workday->workday_type) {
						case 'Uniform Working Days':
							$w = array(
							"company_id" => $company_id,
							"work_schedule_id" => $work_schedule_id,
							//"work_start_time <= " => date("H:i:s"),
							//"work_end_time >= " => date("H:i:s"),
							"days_of_work" => date("l")
							);
							$_CI->db->where("((work_start_time <= '".date("H:i:s")."' AND work_end_time >= '".date("H:i:s")."')
							OR
							(
								(DATE_FORMAT(work_start_time, '".date("Y-m-d")." %H:%i:%s') <= '".date("Y-m-d H:i:s")."' AND DATE_FORMAT(work_end_time, '".date("Y-m-d",strtotime("+1 day"))." %H:%i:%s') >= '".date("Y-m-d H:i:s")."')
								OR
								(DATE_FORMAT(work_start_time, '".date("Y-m-d",strtotime("-1 day"))." %H:%i:%s') <= '".date("Y-m-d H:i:s")."' AND DATE_FORMAT(work_end_time, '".date("Y-m-d")." %H:%i:%s') >= '".date("Y-m-d H:i:s")."')
							)
						)");
							$_CI->db->where($w);
							$query = $_CI->db->get("regular_schedule");
							$r = $query->row();
	
							if($r){
								$date = $today;
								$is_night_shift = false;
								if(date("A", strtotime($r->work_start_time)) == "PM" && date("A", strtotime($r->work_end_time)) == "AM"){
									$date = date("Y-m-d",strtotime("{$today} -1 day"));
									$is_night_shift = true;
								}
								$w = array(
										"emp_id" => $emp_id,
										"date" => date("Y-m-d"),
										"status" => "Active"
								);
								$_CI->db->where($w);
								$time_in = $_CI->db->get("employee_time_in");
								$time_in_row = $time_in->row();
	
								if(!$time_in_row){
									$curr_y = date("Y");
									$curr_m = date("m");
									$curr_d = date("d");
									$curr_dt = date("Y-m-d H:i:s");
									$start_time = date("Y-m-d H:i:s",strtotime("{$curr_y}-{$curr_m}-{$curr_d} {$r->work_start_time}"));
									$end_time = date("Y-m-d H:i:s",strtotime("{$curr_y}-{$curr_m}-{$curr_d} {$r->work_end_time}"));
	
									if($is_night_shift && date("H:i:s",strtotime($start_time)) <= date("H:i:s",strtotime($curr_dt))){
										$curr_dt = date("Y-m-d H:i:s",strtotime("{$curr_dt} -1 day"));
									}
									if($is_night_shift){
										$start_time = date("Y-m-d H:i:s",strtotime("{$start_time} -1 day"));
									}
	
									if($start_time != "" && $end_time != ""){
										$grace = " +{$r->latest_time_in_allowed} minutes";
											
										if(date("Y-m-d H:i:s",strtotime($start_time."".$grace)) <= date("Y-m-d H:i:s",strtotime($curr_dt)) && date("Y-m-d H:i:s",strtotime($curr_dt)) <= date("Y-m-d H:i:s",strtotime($end_time))){
											if(!check_employee_on_leave($row->emp_id)){
												$counter++;
											}
										}
									}
								}
							}
								
							break;
						case 'Flexible Hours':
							$w = array(
							"not_required_login" => 0,
							"company_id" => $company_id,
							"work_schedule_id" => $work_schedule_id
							);
							$_CI->db->where($w);
							$_CI->db->where("latest_time_in_allowed IS NOT NULL");
							$q = $_CI->db->get("flexible_hours");
							$r = $q->row();
	
							if($r){
								$emp_id = $row->emp_id;
								$w = array(
										"emp_id" => $emp_id,
										"date" => date("Y-m-d"),
										"status" => "Active"
								);
								$_CI->db->where($w);
								$time_in = $_CI->db->get("employee_time_in");
								$time_in_row = $time_in->row();
	
								if(!$time_in_row){
									$start_time = $r->latest_time_in_allowed;
									$end_time = date("H:i:s",strtotime($start_time." +{$r->total_hours_for_the_day} hours"));
									if($start_time != "" && $end_time != ""){
										if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
											if(!check_employee_on_leave($row->emp_id)){
												$counter++;
											}
										}
									}
								}
							}
							break;
						case 'Workshift':
							$w = array(
							"company_id" => $company_id,
							"work_schedule_id" => $work_schedule_id
							);
							$_CI->db->where($w);
							$q = $_CI->db->get("schedule_blocks");
							$r = $q->row();
								
							if($r){
								$emp_id = $row->emp_id;
								$w = array(
										"emp_id" => $emp_id,
										"date" => date("Y-m-d"),
										"status" => "Active"
								);
								$_CI->db->where($w);
								$time_in = $_CI->db->get("employee_time_in");
								$time_in_row = $time_in->row();
	
								if(!$time_in_row){
									$start_time = $r->start_time;
									$end_time = $r->end_time;
									if($start_time != "" && $end_time != ""){
										if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
											if(!check_employee_on_leave($row->emp_id)){
												$counter++;
											}
										}
									}
								}
							}
							break;
					}
				}
	
			}
		}
	
		return $counter;
	
	}
	
	
	function manager_count_employee_timeins($company_id,$emp_id){
		$_CI =& get_instance();
		$counter = 0;
		$today = date("Y-m-d");
		$yesterday = date("Y-m-d",strtotime("{$today} -1 day"));
	
		$where = array(
				"eti.comp_id" => $company_id,
				'edrt.parent_emp_id' => $emp_id
		);
		$_CI->db->where("(date = '{$today}' OR date = '{$yesterday}') AND time_out IS NULL");
		$_CI->db->where($where);
		$_CI->db->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");
		$query = $_CI->db->get("employee_time_in AS eti");
		$result = $query->result();
	
		if($result){
			foreach ($result as $row){
				$work_schedule_id = $row->work_schedule_id;
				if($work_schedule_id != "" || $work_schedule_id != 0){
					$workday = get_workday($company_id, $work_schedule_id);
					if($workday){
						if($workday->workday_type == "Uniform Working Days"){
							if(strtotime($row->date) == strtotime($today)){
								$counter = $counter + 1;
							}
							else{
								$w = array(
										"work_schedule_id" => $work_schedule_id,
										"days_of_work" => date("l",strtotime($row->date))
								);
								$_CI->db->where($w);
								$query = $_CI->db->get("regular_schedule");
								$r = $query->row();
								if($r){
									if(date("A",strtotime($r->work_start_time)) == "PM" && date("A",strtotime($r->work_end_time)) == "AM"){
										if(strtotime(date("H:i:s")) <= strtotime($r->work_end_time)){
	
											$counter = $counter + 1;
										}
									}
								}
							}
						}
						elseif ($workday->workday_type == "Flexible Hours"){
							if(strtotime($row->date) == strtotime($today)){
								$counter = $counter + 1;
							}
							else{
								$w = array(
										"work_schedule_id" => $work_schedule_id
								);
								$_CI->db->where($w);
								$query = $_CI->db->get("flexible_hours");
								$r = $query->row();
								if($r){
									if($r->not_required_login != 1){
										$start_time = date("H:i:s",strtotime($r->latest_time_in_allowed));
										$end_time = date("H:i:s",strtotime("{$start_time} +{$r->total_hours_for_the_day} hours"));
	
										if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM"){
											if(strtotime(date("H:i:s")) <= strtotime($end_time)){
												$counter = $counter + 1;
											}
										}
									}
								}
							}
						}
						elseif ($workday->workday_type == "Workshift"){
							if(strtotime($row->date) == strtotime($today)){
								$counter = $counter + 1;
							}
							else{
								$w = array(
										"work_schedule_id" => $work_schedule_id
								);
								$_CI->db->where($w);
								$query = $_CI->db->get("schedule_blocks");
								$r = $query->row();
								if($r){
									$start_time = date("H:i:s",strtotime($r->start_time));
									$end_time = date("H:i:s",strtotime($r->end_time));
	
									if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM"){
										if(strtotime(date("H:i:s")) <= strtotime($end_time)){
											$counter = $counter + 1;
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return $counter;
	}
	
	/*function manager_total_active_employees($company_id,$emp_id) {
	
		$CI =& get_instance();
		$wr_epi = array(
				"epi.status" => 'Active',
				"e.company_id" => $company_id,
				"epi.employee_status" => 'Active',
				"a.user_type_id" => "5",
				"edrt.parent_emp_id" => $emp_id
		);
		$CI->db->select("count(*) as total");
		$CI->db->where($wr_epi);
		$CI->db->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
		$CI->db->join("employee_payroll_information AS epi", "epi.emp_id = e.emp_id", "LEFT");
		$CI->db->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
		
		$q_unass = $CI->db->get("employee AS e");
		$qr = $q_unass->row();
		return ($qr) ? $qr->total : "0";
	
	}*/
	
	function no_shifts_assigned($company_id,$emp_id) {
	
		$total = $total_ws = $total_active_emp = $total_assigned = 0;
		$work_schedule_available = array();
		$employee_assigned = array();
	
		$query_default_sched = manager_employee_default_schedule($company_id,$emp_id);
		$query_sched = manager_employee_custom_shifts($company_id,$emp_id);
		
		if($query_default_sched) {
			foreach ($query_default_sched as $rows) {
				array_push($work_schedule_available, $rows->work_schedule_id);
				array_push($employee_assigned, $rows->emp_id);
			}
		}
	
		if($query_sched) {
			foreach ($query_sched as $rows) {
				array_push($work_schedule_available, $rows->work_schedule_id);
				array_push($employee_assigned, $rows->emp_id);
			}
		}
	
		#if($filter == "unassigned_shifts") {
			$total_active_emp = manager_total_active_employees($company_id,$emp_id);
			$total_assigned = count(array_unique($employee_assigned));
			$total = $total_active_emp - $total_assigned;
		#}
	
		return $total;
	
	}
	
	/*function manager_employee_custom_shifts($company_id,$emp_id) {
	
		$CI =& get_instance();
		$todays_date = date("Y-m-d");
		$wr = array(
				"e.company_id" => $company_id,
				"ess.payroll_group_id" => 0,
				"ess.valid_from" => $todays_date,
				"epi.employee_status" => "Active",
				"a.user_type_id" => "5",
				"edrt.parent_emp_id" => $emp_id
		);
		$sel = array("ess.work_schedule_id", "ess.emp_id");
		$CI->db->select($sel);
		$CI->db->where($wr);
		$CI->db->join("employee AS e", "e.emp_id = ess.emp_id", "LEFT");
		$CI->db->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
		$CI->db->join("employee_payroll_information AS epi", "epi.emp_id = e.emp_id", "LEFT");
		$CI->db->join("employee_details_reports_to AS edrt","edrt.emp_id = ess.emp_id","LEFT");
		$CI->db->group_by("ess.emp_id");
		$q = $CI->db->get("employee_shifts_schedule AS ess");
		$query_shifts = $q->result();
		return ($q->num_rows() > 0) ? $query_shifts : false;
	
	}*/
	
	/*function manager_employee_default_schedule($company_id,$emp_id) {
	
		$CI =& get_instance();
		$sel_epi = array(
				"epi.emp_id",
				"pg.work_schedule_id",
				"pg.payroll_group_id"
		);
		$wr_pg = array(
				"epi.status" => 'Active',
				"e.company_id" => $company_id,
				"epi.employee_status" => 'Active',
				'a.user_type_id' => '5',
				"edrt.parent_emp_id" => $emp_id
		);
	
		$CI->db->select($sel_epi);
		$CI->db->where($wr_pg);
		$CI->db->where("pg.work_schedule_id IS NOT NULL AND epi.payroll_group_id IS NOT NULL");
		$CI->db->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
		$CI->db->join("employee_payroll_information AS epi", "epi.emp_id = e.emp_id", "LEFT");
		$CI->db->join("payroll_group AS pg", "pg.payroll_group_id = epi.payroll_group_id", "INNER");
		$CI->db->join("work_schedule AS ws", "ws.work_schedule_id = pg.work_schedule_id", "LEFT");
		$CI->db->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
		$CI->db->group_by("e.emp_id");
		$q_epi = $CI->db->get("employee AS e");
		$query_sched = $q_epi->result();
	
		return ($q_epi->num_rows() > 0) ? $query_sched : false;
	
	}*/
	
	function count_missing_logs($emp_id, $comp_id) {
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$CI->load->model('employee_model','employee');
		$period = next_pay_period($emp_id, $comp_id);
		$count = 0;
		if($period){
			$today = date('Y-m-d', strtotime('-1 day'));
			$date = date('Y-m-d', strtotime('-1 day'));
			/*if(strtotime($date) > strtotime($period->cut_off_to)){
				$date = date('Y-m-d', strtotime($period->cut_off_to));
			}*/
			#while(strtotime($date) > strtotime($today)){
				$work_sched_id = check_employee_work_schedule($date, $emp_id, $comp_id)->work_schedule_id;
				if(!$work_sched_id){
					return false; break;
				}
				$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
				$check_holiday = $CI->employee->get_holiday_date($date,$emp_id,$comp_id);
				if(!$rest_day && !$check_holiday){
					$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
					$leave = $CI->ews->check_employee_leave_application($date, $emp_id);
					$leave_check = ($leave) ? TRUE: FALSE;
					if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
						$where = array(
								'date'=> $date,
								'emp_id' =>$emp_id,
								'status' => 'Active'
						);
						$CI->db->where($where);
						$q = $CI->db->get('employee_time_in');
						$r = $q->row();
						
						if(!$r){
							if($work_schedule_info['work_schedule']['flexible']){
								if($work_schedule_info['work_schedule']['login']){
									
									$count = $count + 1;
									
								}
							}else{
								$count = $count + 1;
							}
							
						}
					}
				}
				#$date = date('Y-m-d', strtotime($date.' -1 day'));
			#}
		}
		return $count;
	}
	
	function manager_count_missed_punches($emp_id, $comp_id){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$CI->load->model('employee_model','employee');
	
		$period =  next_pay_period($emp_id, $comp_id);
		$count = 0;
		if($period){
			$date = date('Y-m-d', strtotime('-1 day'));
			$today = date('Y-m-d', strtotime('-1 day'));
			/*if(strtotime($today) == strtotime($date)){
				$today = date('Y-m-d', strtotime($date));
			}*/
			if(date('l', strtotime($today)) == 'Sunday') {
				$today = date('Y-m-d', strtotime('-2 day'));
			}
			
			#while(strtotime($date) >= strtotime($today)){
				$work_sched_id = check_employee_work_schedule($date, $emp_id, $comp_id)->work_schedule_id;
				if(!$work_sched_id){
					return false; break;
				}
				$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
				$check_holiday = $CI->employee->get_holiday_date($date,$emp_id,$comp_id);
	
				$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
				if(!$work_schedule_info) break;
				$leave = $CI->ews->check_employee_leave_application($date, $emp_id);
				$leave_check = ($leave) ? TRUE: FALSE;
				if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
						
					$where = array(
							'date'=> $date,
							'emp_id' =>$emp_id,
							'status' => 'Active'
					);
					$CI->db->where($where);
					$q = $CI->db->get('employee_time_in');
					$r = $q->row();
					if($r){
						if($r->time_in == NULL) $count = $count + 1;
						if(!$rest_day && !$check_holiday){
							if($work_schedule_info['work_schedule']['break_time'] != 0){
								if($r->lunch_out == NULL)  $count = $count + 1;
								if($r->lunch_in == NULL)  $count = $count + 1;
							}
						}
	
							
						if($r->time_out == NULL)  $count = $count + 1;
	
					}else{
						if($work_schedule_info['work_schedule']['flexible']){
							if($work_schedule_info['work_schedule']['login']){
								if(!$rest_day && !$check_holiday){
									$count = $count + 4;
								}
							}
						}
						/*else{
							if(!$rest_day && !$check_holiday){
								$count = $count + 4;
							}
						}*/
					}
				}
	
				#$date = date('Y-m-d', strtotime($date.' +1 day'));
			#}
		}
		return $count;
	
	
	
	}

	function manager_count_missed_punches_v2($emp_id, $comp_id,$check_employee_work_schedule_v2,$work_schedule_id_pg){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$CI->load->model('employee_model','employee');

		$period =  next_pay_period_v2($emp_id, $comp_id);
		$count = 0;
		$get_holiday_date_v2 = $CI->employee->get_holiday_date_v2($emp_id,$comp_id);
		$get_rest_day_v2 = $CI->employee->get_rest_day_v2($comp_id);

		if($period){
			$date = date('Y-m-d', strtotime('-1 day'));
			$today = date('Y-m-d', strtotime('-1 day'));
			/*if(strtotime($today) == strtotime($date)){
					$today = date('Y-m-d', strtotime($date));
				}*/
			if(date('l', strtotime($today)) == 'Sunday') {
				$today = date('Y-m-d', strtotime('-2 day'));
			}

			#while(strtotime($date) >= strtotime($today)){
			#$work_sched_id = check_employee_work_schedule($date, $emp_id, $comp_id)->work_schedule_id;

			$work_sched_id1 = in_array_custom("date-{$date}", $check_employee_work_schedule_v2);
			if($work_sched_id1) {
				$work_sched_id = $work_sched_id1->work_schedule_id;
			} else {
				$work_sched_id = $work_schedule_id_pg;
			}

			if(!$work_sched_id){
				return false; //break;
			}

			$rest_day_date = date('l',strtotime($date));
			$rest_day = in_array_custom("date-{$rest_day_date}{$work_sched_id}", $get_rest_day_v2); //$CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime($date)));
			$check_holiday = in_array_custom("date-{$date}", $get_holiday_date_v2); //$CI->employee->get_holiday_date($date,$emp_id,$comp_id);

			$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime($date)));
			if(!$work_schedule_info) return false;//break;
			$leave = $CI->ews->check_employee_leave_application($date, $emp_id);
			$leave_check = ($leave) ? TRUE: FALSE;

			if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){

				/*$where = array(
								'date'=> $date,
								'emp_id' =>$emp_id,
								'status' => 'Active'
						);
						$CI->db->where($where);
						$q = $CI->db->get('employee_time_in');
						$r = $q->row();*/
				$r =  check_time_in_date_v2($date, $emp_id);
				if($r){
					if($r->time_in == NULL) $count = $count + 1;
					if(!$rest_day && !$check_holiday){
						if($work_schedule_info['work_schedule']['break_time'] != 0){
							if($r->lunch_out == NULL)  $count = $count + 1;
							if($r->lunch_in == NULL)  $count = $count + 1;
						}
					}


					if($r->time_out == NULL)  $count = $count + 1;

				}else{
					if($work_schedule_info['work_schedule']['flexible']){
						if($work_schedule_info['work_schedule']['login']){
							if(!$rest_day && !$check_holiday){
								$count = $count + 4;
							}
						}
					}
					/*else{
								if(!$rest_day && !$check_holiday){
									$count = $count + 4;
								}
							}*/
				}
			}

			#$date = date('Y-m-d', strtotime($date.' +1 day'));
			#}
		}
		return $count;



	}
	
	/**
	 * GET employee informations
	 * Enter description here ...
	 * @param int $company_id
	 * @param int $emp_id
	 * @param int $dept_id
	 * @param int $position_id
	 * @return object
	 */
	function get_employee_info_data($company_id,$emp_id) {
		$CI =& get_instance();
		if(is_numeric($company_id) && is_numeric($emp_id)){
			$where = array(
					'epi.emp_id'=>$emp_id,
					//	'd.dept_id'=>$dept_id,
					//	'p.position_id'=>$position_id,
					//	'd.status'=>'Active',
					//	'p.status'=>'Active',
					'epi.status'=>'Active',
			);
			$CI->db->where($where);
			$CI->edb->join('position AS p','epi.position=p.position_id','left');
			$CI->edb->join('department AS d','epi.department_id=d.dept_id','left');
			$q = $CI->edb->get('employee_payroll_information AS epi');
			$row = $q->row();
			return $row;
		}else{
			return false;
		}
	}
	
	/*function manager_employee_expired_work_schedule($company_id,$emp_id,$limit = 10000) {
	
		$CI =& get_instance();
	
		$sel = array("ess.emp_id","a.account_id", 'MAX(ess.until) AS last_date', "DATE_ADD(MAX(ess.until), INTERVAL -7 DAY) AS date_to_notify");
		$sel2 = array(
				'*'
		);
		$wr = array(
				"ess.company_id" => $company_id,
				"ess.payroll_group_id" => "0",
				"edrt.parent_emp_id" => $emp_id
		);
		$CI->db->select($sel);
		$CI->edb->select($sel2);
		$CI->db->where($wr);
		$CI->edb->join("employee AS e", "e.emp_id = ess.emp_id", "LEFT");
		$CI->edb->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
		$CI->db->join("work_schedule AS ws", "ws.work_schedule_id = ess.work_schedule_id", "LEFT");
		$CI->db->join("employee_details_reports_to AS edrt","edrt.emp_id = ess.emp_id","LEFT");
		$CI->db->group_by("ess.emp_id","ess.emp_id");
		$query = $CI->edb->get("employee_shifts_schedule AS ess",$limit);
		$row = $query->result();
		return ($row) ? $row : false;
	
	}*/
	
	/** End - Fritz **/
	
	
	
	/** CRON JOB LEAVE HELPER **/
	
	/**
	 * EMPLOYEE NEXT ANNUAL START
	 * @param date $today
	 * @param date $date_hired
	 * @return boolean
	 */
	function employee_next_annual_start($today,$date_hired) { 
		$today_specific = $today;
		$today = date("Y-m-d",strtotime($today_specific));
		$year = date("Y",strtotime($today_specific));
		$e_datehired = $date_hired;
		$date_hired = date("Y-m-d",strtotime($e_datehired));
		$hired_year = date("Y",strtotime($date_hired));
		$start_of_year_accrual = date("Y-m-d",strtotime("{$hired_year}-1-1 +1year"));
		 
		$next_of_year_accrual = '';
		$flag_annual = '';
		 
		if(strtotime($today) >= strtotime($start_of_year_accrual)) {
			$flag_annual = "<p> ang today ($today) > ($start_of_year_accrual)</p>";
			$next_of_year_accrual = date("Y-m-d",strtotime(date("Y",strtotime($today))."-1-1 +1year"));
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
	
	/**
	 * SEMI ANNUAL FOR SEMI ANNUALS
	 * @param unknown $today
	 * @param unknown $employee_date_hired
	 */
	function employee_next_semi_annual_beginning($today,$employee_date_hired){
		#first scenario
		#$today_specific ="2016-8-11"; #"2014-7-31";
		#second scenario
		#$today_specific ="2016-3-30"; #"2014-7-31";
		#third scenario
		#$today_specific ="2016-7-1"; #"2014-7-31";
		#fourth scenario
		#$today_specific ="2016-11-12"; #"2014-7-31";
		 
		$today_specific = $today;
		
		$today = date("Y-m-d",strtotime($today_specific));
		$year = date("Y",strtotime($today_specific));
		
		#first scnenario date hired
		#$e_datehired = "2014-2-19";
		#second scnenario date hired
		#$e_datehired = "2014-8-28";
		#third scnenario date hired
		#$e_datehired = "2013-11-11";
		#fourth scenario
		#$e_datehired = "2016-7-12";
		 
		$e_datehired = $employee_date_hired;
		 
		$date_hired = date("Y-m-d",strtotime($e_datehired));
		$hired_year = date("Y",strtotime($date_hired));
		$start_of_year_accrual = date("Y-m-d",strtotime('first day of +1 month',strtotime( $date_hired )));
		
		$next_of_year_accrual = '';
		$flag_annual = '';
		$first_beginning = '';
		$second_beginning = '';
		if(strtotime($today) > strtotime($start_of_year_accrual)) { 
			$get_first_beginning = date("Y-m-d",strtotime("+5 month",strtotime($year."-".date("m-d",strtotime($start_of_year_accrual))))); 
			$next_of_year_accrual = date("Y-m-d",strtotime($year."-".date("m",strtotime($get_first_beginning))."-1 +1 month")); # PLUS 1month kon gmag second na siya 
			$first_beginning = $next_of_year_accrual;
			$second_date = date("Y-m-d",strtotime($year."-".date("m",strtotime($next_of_year_accrual))."-1 +6 month"));
			$second_beginning = $second_date; 
			/*
			 if(strtotime($today) <= strtotime($first_beginning)) {
			  
			 $next_of_year_accrual = $first_beginning;
			 }else if(strtotime($today) <= strtotime($second_beginning)){
			 $next_of_year_accrual = $second_beginning;
			  
			 }
			 */
			$flag_con = ''; 
			if(strtotime($second_beginning) >= strtotime($today)){
				$next_of_year_accrual = $second_beginning;
				$flag_con = 'second';
			} 
			if(strtotime($first_beginning) >= strtotime($today)){
				$next_of_year_accrual = $first_beginning;
				$flag_con = 'first';
			} 
			/*
			 if(strtotime($today) >= strtotime($first_beginning)) {
			 $next_of_year_accrual = $first_beginning;
			 $flag_con = 'first';
			 }else if(strtotime($today) >= strtotime($second_beginning)){
			 $next_of_year_accrual = $second_beginning;
			 $flag_con = 'second';
			 }
			 */
		
			$flag_annual = "<p> ang today ($today) > ($start_of_year_accrual) <br />
			next year ($next_of_year_accrual) read next(".idates($next_of_year_accrual).")<Br />
			First Beggining condition ($today) <= $first_beginning <Br />
			Second Beginning condition ($today) <= $second_beginning <br />
			By Numbers today (".strtotime($today).") <br />
    							By First Beginning Number (".strtotime($first_beginning).") <br />
    							By Second Beginning Number (".strtotime($second_beginning).") <br />
		    							Condition fall ($flag_con)
		    							</p>";
		
		}
		 
		$is_today = "<p>
			What today is {$today} <br />
			human read (".idates($today).")<br />
			Date hired ($date_hired) <br />
			Start of Accrual Date ($start_of_year_accrual),<br />
			Next Accrual Date ($next_of_year_accrual)  <br />
			First Beginning ($first_beginning) <br />
			Second Beginning ($second_beginning)  <br />
		</p>";
		#echo $is_today;
		#echo $flag_annual;
		
		$result = array(
				'today'=>$today,
				'date_hired'=>$hired_year,
				'result_all'=>$is_today,
				'result_all_annual'=>$flag_annual,
				'start_of_accrual'=>$start_of_year_accrual,
				'next_accrual_date'=>$next_of_year_accrual,
				'first_begin'=>$next_of_year_accrual,
				'second_begin'=>$second_beginning
		);
		return $result;
	}
	
	/**
	 * LEAVE ENDING OF SEMI ANNUAL
	 * @param unknown $today
	 * @param unknown $employee_date_hired
	 */
	function employee_next_semi_annual_ending($today,$employee_date_hired){
		#first scenario
		#$today_specific ="2016-8-11"; #"2014-7-31";
		#second scenario
		#$today_specific ="2016-3-30"; #"2014-7-31";
		##third scenario
		#$today_specific ="2016-5-11"; #"2014-7-31";
		#fourth scenario
		#$today_specific ="2016-11-12"; #"2014-7-31";
		$today_specific = $today;
		$today = date("Y-m-d",strtotime($today_specific));
		$year = date("Y",strtotime($today_specific));
		 
		#first scnenario date hired
		#$e_datehired = "2014-2-19";
		#second scnenario date hired
		#$e_datehired = "2014-8-28";
		#third scnenario date hired
		#$e_datehired = "2013-11-13";
		#fourth scenario
		$e_datehired = $employee_date_hired;
		
		$date_hired = date("Y-m-d",strtotime($e_datehired));
		$hired_year = date("Y",strtotime($date_hired));
		$start_of_year_accrual = date("Y-m-d",strtotime('last day of +6 month',strtotime( $date_hired )));
		$next_of_year_accrual = '';
		$flag_annual = '';
		$first_beginning = '';
		$second_beginning = '';
		if(strtotime($today) >= strtotime($start_of_year_accrual)) {
		
			$first_beginning = date("Y-m-d",strtotime($year."-".date("m-d",strtotime($start_of_year_accrual))));
			$second_date =  date("Y-m-d",strtotime("last day of +6 month",strtotime($year."-".date("m",strtotime($start_of_year_accrual))."-1")));
			$second_beginning  = $second_date;
			$flag_con = '';
			 
			if(strtotime($second_beginning) >= strtotime($today)){
				$next_of_year_accrual = $second_beginning;
				$flag_con = 'second';
			}
			 
			if(strtotime($first_beginning) >= strtotime($today)){
				$next_of_year_accrual = $first_beginning;
				$flag_con = 'first';
			}
		
			 
			$flag_annual = "<p> ang today ($today) > ($start_of_year_accrual) <br />
				next year ($next_of_year_accrual) read next(".idates($next_of_year_accrual).")<Br />
				First Beggining condition ($today) <= $first_beginning <Br />
				Second Beginning condition ($today) <= $second_beginning <br />
				By Numbers today (".strtotime($today).") <br />
    			By First Beginning Number (".strtotime($first_beginning).") <br />
    			By Second Beginning Number (".strtotime($second_beginning).") <br />
		    	Condition fall ($flag_con)
		    </p>";
			 
		}
		
		$is_today = "<p>
			What today is {$today} <br />
			human read (".idates($today).")<br />
			Date hired ($date_hired) <br />
			Start of Accrual Date ($start_of_year_accrual),<br />
			Next Accrual Date ($next_of_year_accrual)  <br />
			Start Accrual Ending First ($first_beginning) <br />
			Start Accrual Ending Second ($second_beginning)  <br />
		</p>";
		
		$result = array(
				'today'=>$today,
				'date_hired'=>$employee_date_hired,
				'result_all'=>$is_today,
				'result_all_annual'=>$flag_annual,
				'start_of_accrual'=>$start_of_year_accrual,
				'next_accrual_date'=>$next_of_year_accrual,
				'ending_first'=>$first_beginning,
				'ending_second'=>$second_beginning
		);
		return $result;
		
	}
	
	/**
	 * FOR LEAVES BEGINNING
	 * @param date $today
	 * @param date $employee_date_hire
	 * @return object
	 */
	function employee_quarterly_beginning($today,$employee_date_hire){
		# scenario 1
		#$today_specific = "2016-8-11";
		#$date_hired = "2014-2-19";
		 
		# scenerio 2
		#$today_specific = "2016-3-30";
		#$date_hired = "2014-8-28";
		
		# scenerio 3
		#$today_specific = "2015-5-11";
		#$date_hired = "2013-11-11";
		 
		# scenerio 4
		#$today_specific = "2016-6-1";
		#$date_hired = "2015-3-1";
		
		$today_specific = $today;
		$date_hired = $employee_date_hire;
		
		$today = date("Y-m-d",strtotime($today_specific));
		$year = date("Y",strtotime($today));
		$employee_hire = date("Y-m-d",strtotime($date_hired));
		$hired_year = date("Y",strtotime($employee_hire));
		 
		$start_or_accrual_date = date("Y-m-d",strtotime("first day of +1month",strtotime($employee_hire))); 
		
		$first_quarterly = '';
		$second_quarterly = '';
		$third_quarterly = '';
		$fourth_quarterly = '';
		 
		if(strtotime($today) >= strtotime($start_or_accrual_date)){
			$first_set = date("Y-m-d",strtotime("first day of +3 month",strtotime(date("Y-m-d",strtotime($start_or_accrual_date)))));
			$first_quarterly = date("Y-m-d",strtotime($year."-".date("m-d",strtotime($first_set))));
		
			$sec_set = date("Y-m-d",strtotime("first day of +3 month",strtotime(date("Y-m-d",strtotime($first_set)))));
			$second_quarterly = date("Y-m-d",strtotime($year."-".date("m-d",strtotime($sec_set))));
		
			$third_set = date("Y-m-d",strtotime("first day of +3 month",strtotime(date("Y-m-d",strtotime($sec_set)))));
			$third_quarterly = date("Y-m-d",strtotime($year."-".date("m-d",strtotime($third_set))));
		
			$fourth_set = date("Y-m-d",strtotime("first day of +3 month",strtotime(date("Y-m-d",strtotime($third_set)))));
			$fourth_quarterly = date("Y-m-d",strtotime($year."-".date("m-d",strtotime($fourth_set))));
			 
		}
		 
		$next_accrual_date = '';
		$date_condition = '';
		 
		$date_quarter = array();
		$date_quarter[] = $first_quarterly;
		$date_quarter[] = $second_quarterly;
		$date_quarter[] = $third_quarterly;
		$date_quarter[] = $fourth_quarterly;
		
		usort($date_quarter,"sort_be");
		if($date_quarter){
			foreach($date_quarter as $key=>$val){
				if(strtotime($val) >= strtotime($today)){
					$next_accrual_date = $val;
					break;
				}
			}
		}
		 
		$info = "<p>
			Employee Today ($today) <br />
			Today Year ($year) <br />
			Date Hired ($employee_hire) <br />
			Hired Year ($hired_year) <br />
			Start of Accrual Date ($start_or_accrual_date) <br />
			Next Accrual DAte ($next_accrual_date) = ".idates($next_accrual_date)."<br />
			First Quarterly ($first_quarterly)  = ".idates($first_quarterly)."<br />
			Second Quarterly ($second_quarterly) =  ".idates($second_quarterly)." <br />
			Third Quarterly ($third_quarterly)  =  ".idates($third_quarterly)."<br />
			Fourth Quarterly ($fourth_quarterly) = ".idates($fourth_quarterly)."<br />
    	</p>
    	";
		$result = array(
				'today'=>$today,
				'date_hired'=>$employee_date_hire,
				'result_all'=>$info,
				'start_of_accrual'=>$start_or_accrual_date,
				'next_accrual_date'=>$next_accrual_date,
				'first_quarter'=>$first_quarterly,
				'second_quarter'=>$second_quarterly,
				'third_quarter'=>$third_quarterly,
				'fourth_quarter'=>$fourth_quarterly
		);
		return $result;	
	}
	
	/**
	 * FOR ENDING QUARTERLY
	 * @param date $today
	 * @param date $emp_hire
	 * @return object
	 */
	function employee_quarterly_ending($today,$emp_hire){
		$today_specific = $today;
		$date_hired_employee = $emp_hire;
		#scenario 1
		#$today_specific = "2016-8-11";
		#$date_hired_employee = "2014-2-14";
		#scenario 2
		#$today_specific = "2016-3-30";
		#$date_hired_employee = "2014-8-28";
		 
		#scenario 3
		#$today_specific = "2016-5-11";
		#$date_hired_employee = "2013-11-11";
		 
		#scenario 4
		#$today_specific = "2016-6-1";
		#$date_hired_employee = "2015-3-1";
		 
		$date_hired = date("Y-m-d",strtotime($date_hired_employee));
		$today = date("Y-m-d",strtotime($today_specific));
		$year = date("Y",strtotime($today));
		$hired_year = date("Y",strtotime($date_hired));
		 
		$start_of_accrual_date = date("Y-m-d",strtotime("last day of +3 month",strtotime($date_hired)));
		$first_quarter = '';
		$second_quarter = '';
		$third_quarter = '';
		$fourth_quarter = '';
		 
		if(strtotime($today) >= strtotime($start_of_accrual_date)){
		
			$first_set= date("Y-m-d",strtotime("last day of +3 month",strtotime($start_of_accrual_date)));
			$first_quarter = date("Y-m-d",strtotime($year."-".date("m-d",strtotime($first_set))));
		
			$second_set = date("Y-m-d",strtotime("last day of +3 month",strtotime($first_quarter)));
			$second_quarter =  date("Y-m-d",strtotime($year."-".date("m-d",strtotime($second_set))));
		
			$third_set = date("Y-m-d",strtotime("last day of +3 month",strtotime($second_quarter)));
			$third_quarter = date("Y-m-d",strtotime($year."-".date("m-d",strtotime($third_set))));
		
			$fourth_set = date("Y-m-d",strtotime("last day of +3 month",strtotime($third_quarter)));
			$fourth_quarter = date("Y-m-d",strtotime($year."-".date("m-d",strtotime($fourth_set))));
		
		}
		 
		$date_quarters = array(); # we must create an array to be sorted to next
		$date_quarters[] = $first_quarter;
		$date_quarters[] = $second_quarter;
		$date_quarters[] = $third_quarter;
		$date_quarters[] = $fourth_quarter;
		 
		usort($date_quarters,"sort_be");
		 
		$next_accrual = '';
		$fall_condition = '';
		if($date_quarters){
			foreach($date_quarters as $key=>$val){
				if(strtotime($val)>=strtotime($today)){
					$next_accrual = $val;
					$fall_condition = $key;
					break;
				}
			}
		}
		  
		$info = "
		<p>
		Today ($today) <br />
		Date Hired ($date_hired) <br />
		Start of Accrual Date ($start_of_accrual_date) <br />
		Next Accrual ($next_accrual)<br />
		First Quarter ($first_quarter) <br />
		Second Quarter ($second_quarter) <br />
		Third Quarter ($third_quarter) <br />
		Fourth Quarter ($fourth_quarter) <br />
		Condtion Fall ($fall_condition)
		</p>
		";
		 
		#echo $info;
		
		$result = array(
				'today'=>$today,
				'date_hired'=>$emp_hire,
				'result_all'=>$info,
				'start_of_accrual'=>$start_of_accrual_date,
				'next_accrual_date'=>$next_accrual,
				'first_quarter'=>$first_quarter,
				'second_quarter'=>$second_quarter,
				'third_quarter'=>$third_quarter,
				'fourth_quarter'=>$fourth_quarter
		);
		return $result;
	}

	/**
	 * SORT FUNCTIONALITIES
	 * @param array $a
	 * @param array $b
	 * @return object to boolean
	 */
	function sort_be($a,$b){
		return strcmp($a, $b);
	}
	
	
	/** END HELPER CRON JOB LEAVE **/

	function enable_default_workflow_notification_settings($company_id){
		$_CI =& get_instance();
		$where = array(
			'company_id'=>$company_id
		);
		$_CI->db->where($where);
		$this_query = $_CI->db->get("approval_settings");
		if($this_query->num_rows() > 0){
			#do nothing
		}else{
			$a_field5 = array(
				'company_id'=>$company_id,
				'status'=>"Active",
				'disabled_application_status'=>"",
			);
			esave('approval_settings',$a_field5);
		}
	}
	
	function check_missed_punches($date, $emp_id){
	    $CI =& get_instance();
	    
	    $where = array(
	        'et.date'=> $date,
	        'et.emp_id' =>$emp_id,
	        'et.status' => 'Active'
	    );
	    
	    $select = array(
	        #'*',
	        'et.date AS date',
	        'et.time_in AS time_in',
	        'et.lunch_in AS lunch_in',
	        'et.lunch_out AS lunch_out',
	        'et.comp_id AS company_id',
	        'et.employee_time_in_id AS employee_time_in_id',
	        'et.corrected AS corrected',
	        'et.reason AS reason',
	        'et.work_schedule_id AS work_schedule_id',
	        'et.time_in_status AS time_in_status',
	        'ws.work_type_name AS work_type_name',
	        'et.tardiness_min AS tardiness_min',
	        'et.undertime_min AS undertime_min',
	        'et.employee_time_in_id AS time_inId',
	        'et.time_out AS time_out',
	        'et.location_1 AS location_1',
	        'et.total_hours_required AS total_hours',
	        'et.total_hours AS total_hours_required',
	        'ws.name AS work_sched_name',
	        'et.flag_halfday AS flag_halfday',
	        'et.notes AS notes',
	        'epi.location_base_login_approval_grp AS locationBasedApp',
	        'epi.add_logs_approval_grp AS add_logs_approval_grp',
	        'epi.attendance_adjustment_approval_grp AS attendance_adjustment_approval_grp',
	        'et.source',
	        'et.last_source',
	        'et.location',
	        'ws.break_rules',
	        'ws.name',
	        'et.absent_min',
	        'et.late_min',
	        'et.overbreak_min',
	        'ws.break_rules',
	        'et.change_log_date_filed'
	    );
	    
	    $CI->db->select($select);
	    $CI->db->where($where);
	    $CI->edb->join("employee_payroll_information AS epi","epi.emp_id = et.emp_id","LEFT");
	    $CI->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
	    $CI->edb->join("work_schedule AS ws","ws.work_schedule_id = et.work_schedule_id","LEFT");
	    $q = $CI->db->get('employee_time_in AS et');
	    $r = $q->row();
	    
	    return ($r) ? $r : false;
    }
    
    function employee_mobile_clockguard($emp_id) {
        if (!$emp_id) {
            return false;
        }

        $CI =& get_instance();
        $sel = array(
            'employee_mobile_clockin',
            'employee_mobile_face_id',
            'employee_mobile_require_face_id'
        );
        $whr = array(
            'emp_id' => $emp_id
        );
        $CI->db->select($sel);
        $CI->db->where($whr);
        $q = $CI->db->get('employee_payroll_information');
        $r = $q->row();
        return ($r) ? $r : false;
    }
	

    // function get_schedule_settings_by_workschedule_id($workschedule_id,$company_id,$workday){
    //     $CI =& get_instance();
        
    //     $sel = array(
    //         "*",
    //         "rs.break_1 AS break_1_in_min",
    //         "rs.break_2 AS break_2_in_min"
    //     );
        
    //     $where = array(
    //         "ws.comp_id"           => $company_id,
    //         "ws.work_schedule_id"  => $workschedule_id,
    //         "ws.status"            => "Active",
    //         "rs.days_of_work"      => $workday
    //     );
        
    //     $CI->db->select($sel);
    //     $CI->db->where($where);
    //     $CI->db->join("regular_schedule AS rs","rs.work_schedule_id = ws.work_schedule_id","LEFT");
    //     $q = $CI->db->get("work_schedule AS ws");
    //     $r = $q->row();
        
    //     return ($r) ? $r : false;
    // }

    // function tardiness_rule_migrated_v3($company_id,$last_t_worksched_id){
    //     $get_work_schedule = get_work_schedule_migrated($company_id);
        
    //     $arr    = false;
    //     $r      = in_array_foreach_custom("worksched_migrate_1_1",$get_work_schedule);
    //     $r1     = in_array_custom("worksched_id_{$last_t_worksched_id}",$get_work_schedule);
    //     $cat_id = "";
        
    //     if($r1){
    //         $cat_id = $r1->category_id;
    //     }
    //     if(!$cat_id){
    //         $cat_id = $last_t_worksched_id;
    //     }
        
    //     if($r){
    //         foreach ($r as $key => $value) {
    //             if($value->work_schedule_id == $cat_id){
    //                 $arr = true;
    //                 break;
    //             }
    //         }
    //     }
    //     return $arr;
    // }

    // function check_if_enable_breaks_on_holiday($comp_id,$work_schedule_id,$split=false,$schedule_block_id=null){
    //     $CI =& get_instance();
        
    //     if($split) {
    //         $w = array(
    //             "work_schedule_id" => $work_schedule_id,
    //             "company_id" => $comp_id,
    //             "schedule_blocks_id" => $schedule_block_id
    //         );
            
    //         $CI->db->where($w);
    //         $q = $CI->db->get('schedule_blocks');
    //         $r = $q->row();
            
    //         if($r) {
    //             if($r->enable_breaks_on_holiday == "yes") {
    //                 return TRUE;
    //             } else {
    //                 return FALSE;
    //             }
    //         } else {
    //             return FALSE;
    //         }
    //     } else {
    //         $w = array(
    //             "work_schedule_id" => $work_schedule_id,
    //             "comp_id" => $comp_id
    //         );
            
    //         $CI->db->where($w);
    //         $q = $CI->db->get('work_schedule');
    //         $r = $q->row();
            
    //         if($r) {
    //             if($r->enable_breaks_on_holiday == "yes") {
    //                 return TRUE;
    //             } else {
    //                 return FALSE;
    //             }
    //         } else {
    //             return FALSE;
    //         }
    //     }
        
    // }
	
