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
			default:
				$process = NULL;
				break;
		}

		$CI =& get_instance();
		
		$CI->db->join('approval_process AS ap','ag.approval_process_id = ap.approval_process_id','LEFT');
		if($process!= NULL){
			if($process == 'timein'){
				$CI->db->where('name','Timesheet Adjustment');
				$CI->db->or_where('name','Mobile Clock-in');
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
	function count_absences($emp_id, $comp_id){
		$CI =& get_instance();
		$CI->load->model('employee_work_schedule_model','ews');
		$CI->load->model('employee_model','employee');
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
										
									$count = $count + $work_schedule_info['work_schedule']['total_hours'];
										
								}
							}else{
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
		
		$period = next_pay_period($emp_id, $comp_id);
		if($period){
				$where = array(
						'date >=' => date('Y-m-d', strtotime($period->cut_off_from)),
						'date <=' => date('Y-m-d', strtotime($period->cut_off_to)),
						'tardiness_min >' => '0',
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
		}else{
			return  0;
		}
			
		
		
	}
	function count_tardiness($emp_id, $comp_id){
		$CI =& get_instance();
		$period = next_pay_period($emp_id, $comp_id);
		$count = 0;
		if($period){
			$date = date('Y-m-d', strtotime($period->cut_off_from));
			$today = date('Y-m-d');
			if(strtotime($today) > strtotime($period->cut_off_to)){
				$today = date('Y-m-d', strtotime($period->cut_off_to));
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
					$count = $count + $r->tardiness_min;
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
						'date <=' => date('Y-m-d', strtotime($period->cut_off_to)),
						'undertime_min >' => '0',
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
		}else{
			return 0;
		}
		
		
	}
	function count_undertime($emp_id, $comp_id){
		$CI =& get_instance();
		$period = next_pay_period($emp_id, $comp_id);
		$count = 0;
		if($period){
			$date = date('Y-m-d', strtotime($period->cut_off_from));
			$today = date('Y-m-d');
			if(strtotime($today) > strtotime($period->cut_off_to)){
				$today = date('Y-m-d', strtotime($period->cut_off_to));
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
					$count = $count + $r->undertime_min;
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
			$today = date('Y-m-d', strtotime($period->cut_off_from));
			$date = date('Y-m-d', strtotime('-1 day'));
			if(strtotime($date) > strtotime($period->cut_off_to)){
				$date = date('Y-m-d', strtotime($period->cut_off_to));
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
						}else{
							if(!$rest_day && !$check_holiday){
								$count = $count + 4;
							}
						}
					}
				}
				
				$date = date('Y-m-d', strtotime($date.' +1 day'));
			}
		}
		return $count;
		
		
		
	}
	function check_employee_work_schedule($flag_date,$emp_id,$company_id){
		$CI =& get_instance();
		$w = array(
				"emp_id"=>$emp_id,
				"company_id"=>$company_id,
				"valid_from"=>$flag_date,
				"until"=>$flag_date,
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
		#$period =  next_pay_period($emp_id, $comp_id);
		//$date = date('Y-m-d', strtotime($period->cut_off_from));
		$date = date('Y-m-d');
		$today = date('Y-m-d');
		$shift = array();
		#if($period){
			#while(strtotime($date) >= strtotime($today)){
				$work_sched_id = check_employee_work_schedule(date('Y-m-d', strtotime($date.' +1 day')), $emp_id, $comp_id)->work_schedule_id;
				if(!$work_sched_id){
					return false; break;
				}
				$rest_day = $CI->ews->get_rest_day($comp_id,$work_sched_id,date('l',strtotime(date('Y-m-d', strtotime($date.' +1 day')))));
				$check_holiday = $CI->employee->get_holiday_date(date('Y-m-d', strtotime($date.' +1 day')),$emp_id,$comp_id);
				if(!$rest_day && !$check_holiday){
					$work_schedule_info = $CI->ews->work_schedule_info($comp_id,$work_sched_id,date('l',strtotime(date('Y-m-d', strtotime($date.' +1 day')))));
					if(!$work_schedule_info) break;
					$leave = $CI->ews->check_employee_leave_application(date('Y-m-d', strtotime($date.' +1 day')), $emp_id);
					$leave_check = ($leave) ? TRUE: FALSE;
					if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
						if(strtotime(date('Y-m-d', strtotime($date.' +1 day'))) > strtotime($today)){
							$shift = array(
								'shift_name' => $work_schedule_info['work_schedule']['shift_name'],
								'shift_date' => date('Y-m-d', strtotime($date.' +1 day')),
								'start_time' => $work_schedule_info['work_schedule']['start_time'],
								'end_time'   => $work_schedule_info['work_schedule']['end_time'],
								'flexible'	 => $work_schedule_info['work_schedule']['flexible']
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
								$averate_working_hours_per_day = $payroll_calendar_working_days->averate_working_hours_per_day;
								return ($real_basic_pay * $averate_working_hours_per_day) * .25;
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
										$averate_working_hours_per_day = $payroll_calendar_working_days->averate_working_hours_per_day;
										return ($real_basic_pay * $averate_working_hours_per_day) * .25;
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
				$elh_field = array(
					'company_id'	=> $company_id,
					'leave_type_id'	=> $leave_type_id,
					'emp_id'		=> $emp_id,
					'previous_period_leave_balance'	=> $formula_results >=0 ? $formula_results :0 ,
					'date'			=> $today
				);
				esave("employee_leave_history",$elh_field);
				
			}else{
				$elh_field = array(
					'company_id'	=> $company_id,
					'leave_type_id'	=> $leave_type_id,
					'emp_id'		=> $emp_id,
					'previous_period_leave_balance'	=> $employee_remaining_leave_credits,
					'date'			=> $today
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
	
	/* added: fritz -> end here */
	
	
