<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
*	Helper : Activity logs helper 
*	Author : Aldrin Cantero <aldrin.cantero@gmail.com>
*	Usage  : For activity logs
*/

function shorter_time($time){
	
	return date('H:i',strtotime($time));
}

function time12hrs($time){
	$mi = ($time !="") ? date('g:i a',strtotime($time)) : "00:00:00";
    return $mi;
}

function imagex($comp_id,$image){
	return "./uploads/companies/9".$comp_id."/" . $image;
}

function dash_date($date){
	return date('m-d-Y',strtotime($date));
}


function get_latest_timesheet($emp_id,$comp_id){
	$_CI =& get_instance();
	$where = array(
			"eti.comp_id" =>$comp_id,
			"eti.emp_id" => $emp_id,
			"eti.deleted" => '0'
	);

	$_CI->edb->where($where);
	$_CI->edb->join("employee AS e","e.emp_id = eti.emp_id","left");
	$_CI->db->order_by("eti.date","DESC");
	$query = $_CI->edb->get("employee_time_in AS eti");

	$row = $query->row();

	return ($row) ? $row : false;
}

 function count_timesheet($company_id){
 	$_CI =& get_instance();
	if(is_numeric($company_id)){

		$where = array(
				'ee.comp_id'   => $company_id,
				'ee.deleted'   => '0',
				'ee.time_in_status' => 'pending',
				'ee.corrected' => 'Yes',
		);

			
		$_CI->db->where($where);

		$query = $_CI->edb->get('employee_time_in AS ee');
		$result = $query->num_rows();
		$query->free_result();
		return $result;
	}else{
		return false;
	}
}

/* -----------[ START TIME ENTRY OPTION ] ---------------*/
function enable_manual_upload($company_id){
	$_CI =& get_instance();
	
	$_CI->edb->where("company_id",$company_id);
	$query = $_CI->edb->get("clock_guard_settings");
	$row = $query->row();
	if($row){
		$manual = $row->manual_upload;
		
		return ($manual=="enable") ? true : false;
	}
	return false;	
}
  

function enable_clock_in_kiosk($company_id){
	$_CI =& get_instance();
	$_CI->edb->where("company_id",$company_id);
	$query = $_CI->edb->get("clock_guard_settings");
	$row = $query->row();
	
	if($row){
		$manual = $row->settings;
		
		return ($manual=="Enable") ? true : false;
	}
	
	return false;
}

function enable_clock_in_kiosk_photo($company_id){
	$_CI =& get_instance();
	$_CI->edb->where("company_id",$company_id);
	$query = $_CI->edb->get("clock_guard_settings");
	$row = $query->row();
	
	if($row){
		$check = $row->enable_clockin_with_photo;
	
		return ($check =="enable") ? true : false;
	}
	return false;
}

function enable_desktop_clock_with_photo($company_id){
	$_CI =& get_instance();
	$_CI->edb->where("company_id",$company_id);
	$query = $_CI->edb->get("clock_guard_settings");
	$row = $query->row();
	if($row){
		$check = $row->desktop_clockin_with_photo;
		return ($check =="enable") ? true : false;
	}
	
	return false;
}

function enable_powered_beacon(){
	$_CI =& get_instance();
	$_CI->edb->where("company_id",$company_id);
	$query = $_CI->edb->get("clock_guard_settings");
	$row = $query->row();
	
	if($row){
		$check = $row->powered_beacon_clockin;
		
		return ($check =="enable") ? true : false;
	}
	return false;
}

function enable_mobile_clock(){
	$_CI =& get_instance();
	$_CI->edb->where("company_id",$company_id);
	$query = $_CI->edb->get("clock_guard_settings");
	$row = $query->row();
	
	if($row){
		$check = $row->mobile_clockin;
		
		return ($check =="enable") ? true : false;
	}
	
	return false;
	
}


function enable_clock_mac_address($company_id){
	$_CI =& get_instance();
	$_CI->edb->where("company_id",$company_id);
	$query = $_CI->edb->get("clock_guard_settings");
	$row = $query->row();

	if($row){
	$check = $row->clockin_mac_address;

	return ($check =="enable") ? true : false;
	}
	
	return false;

}

function enable_clock_mac_address_photo($company_id){
	$_CI =& get_instance();
	$_CI->edb->where("company_id",$company_id);
	$query = $_CI->edb->get("clock_guard_settings");
	$row = $query->row();

	if($row){
		$check = $row->mac_address_camera;
	
		return ($check =="enable") ? true : false;
		
	}
	
	return false;

}

/**
 * Only one user can use the computer
 * @param unknown $company_id
 */
function clockin_user($company_id){
	$_CI =& get_instance();
	$_CI->edb->where("company_id",$company_id);
	$query = $_CI->edb->get("clock_guard_settings");
	$row = $query->row();
	
	$check = $row->clockin_user;
	
	return ($check =="1") ? true : false;
}
/**
 * check if camera enable in ip address
 * @param string $ip
 * @return boolean
 */
function check_enable_camera($ip,$company_id){
	$_CI =& get_instance();
	
		$w = array(
				"company_id"=>$company_id,
				"ip_address" => $ip,
				"activate_photo" => 1
		);
		$_CI->db->where($w);
		$q = $_CI->db->get("employee_ip_address");
		$r = $q->row();
		if($r){
			//$ip_address = $r->ip_address;
			//return ($ip_address == $ip) ? TRUE : FALSE ;
			return true;
		}else{
			return false;
		}
	
}

function is_company_active($company_id){
	$_CI =& get_instance();
	
	$select = array(
			"psa.account_status"
	);
	$_CI->db->select($select);
	$w = array(
			"company_id"=>$company_id
	);
	$_CI->db->where($w);
	$_CI->db->join("payroll_system_account as psa","psa.payroll_system_account_id = ac.payroll_system_account_id","left");
	$q = $_CI->db->get("assigned_company as ac");
	$r = $q->row();

	if($r){
		if($r->account_status == "suspended" || $r->account_status == "disconnected"){
			return true;
		}else 
			return false;
	}
}

function is_attendance_active($company_id){
	$_CI =& get_instance();
	
	$_CI->db->where("company_id",$company_id);
	$query2 = $_CI->db->get("attendance_settings");
	$row2 = $query2->row();

	if($row2){
		if($row2->status=="enabled")
			return $row2->hours;
	}else{
			
		$data2 = array(
				'hours' => 0,
				'company_id' => $company_id
		);
			
		$_CI->db->insert('attendance_settings', $data2);
	}

	return false;
}

/* -----------[ END TIME ENTRY OPTION ] ---------------*/

/* ---------------------------- [ NENE <reyneil_27@yahoo.com> VERSION 3 START ] ---------------------------- */

function empty_search(){
	return "No Results Found";
}

function empty_query() {
	return "No Data Found";
}

function todo_detail_latest_leave($emp_id, $company_id){
	$_CI =& get_instance();
	$where = array(
			"el.company_id" =>$company_id,
			"e.emp_id" => $emp_id,
			"el.deleted" => '0'
	);

	$_CI->edb->where($where);
	$_CI->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
	$_CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
	$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
	$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
	$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
	$_CI->db->order_by("el.date_filed","DESC");
	$query = $_CI->edb->get("employee_leaves_application AS el");
	$row = $query->row();

	return ($row) ? $row : false;
}

function todo_detail_latest_overtime($emp_id, $company_id){
	$_CI =& get_instance();
	$where = array(
			"o.company_id"	=> $company_id,
			"e.emp_id" => $emp_id,
			"o.deleted"	=> "0",
	);

	$_CI->db->where($where);
	$_CI->edb->join('employee AS e','e.emp_id = o.emp_id','LEFT');
	$_CI->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
	$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
	$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
	$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
	$_CI->db->order_by("o.overtime_date_applied","DESC");
	$query = $_CI->edb->get('employee_overtime_application AS o');
	$row = $query->row();

	return ($row) ? $row : false;
}

function todo_detail_latest_expense($emp_id, $company_id){
	$_CI =& get_instance();
	$where = array(
		"ex.company_id" => $company_id,
		"ex.status" => "Active",
		"ex.emp_id" => $emp_id
	);
	$_CI->db->where($where);
	$_CI->edb->join("employee AS e","e.emp_id = ex.emp_id","INNER");
	$_CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
	$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
	$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
	$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
	$_CI->db->order_by("ex.expense_date","DESC");
	$query = $_CI->edb->get("expenses AS ex");
	$row = $query->row();

	return ($row) ? $row : false;
}

function counter_all_pending_leaves($company_id){
	$q = count_leave($company_id);
	$for_pagi = 0;
	if($q){
		foreach ($q as $approvers){
			$leave_approval_grp = $approvers->leave_approval_grp;
			$level = $approvers->level;
			$check = check_assigned_leave($leave_approval_grp, $level, $company_id);
			if($check){
				$for_pagi++;
			}
		}
	}
	return (is_workflow_enabled($company_id)) ? $for_pagi : "0";
}

function approval_level($emp_id){
	$_CI =& get_instance();
	if(is_numeric($emp_id)){
		$_CI->db->where('ap.name','Leave Approval Group');
		$_CI->db->where('ag.emp_id',$emp_id);
		$_CI->db->join('approval_process AS ap','ap.approval_process_id = ag.approval_process_id','LEFT');
		$q = $_CI->db->get('approval_groups AS ag');
		$r = $q->row();
		
		return ($r) ? $r->level : FALSE;
	}else{
		return false;
	}
}

function count_leave($company_id){
	$_CI =& get_instance();
	$approver_emp_id = ($_CI->session->userdata("user_type_id") == 2) ? "-99{$company_id}" : $_CI->session->userdata("emp_id");
	$where = array(
		"el.company_id" => $company_id,
		"el.deleted" => '0',
		"ag.emp_id" => $approver_emp_id,
		"el.leave_application_status" => "pending"
	);		
	$where2 = array(
		"al.level !=" => ""
	);
	$select = array(
		"*"
	);
	$_CI->db->select($select);
	$_CI->db->where($where2);
	$_CI->edb->where($where);
	$_CI->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
	$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
	$_CI->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
	$_CI->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
    $_CI->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
	$_CI->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
	$_CI->db->group_by("el.employee_leaves_application_id");
	$query = $_CI->edb->get("employee_leaves_application AS el");
	$result = $query->result();
	
	return ($result) ? $result : false;
}

function check_assigned_leave($leave_appr_grp, $level, $company_id){
	$_CI =& get_instance();
	$approver_emp_id = ($_CI->session->userdata("user_type_id") == 2) ? "-99{$company_id}" : $_CI->session->userdata("emp_id");
	$where = array(
		"emp_id" => $approver_emp_id,
		"level" => $level,
		"approval_groups_via_groups_id" => $leave_appr_grp
	);
	$_CI->db->where($where);
	$query = $_CI->db->get("approval_groups");
	$row = $query->row();
	
	return ($row) ? true : false;
}
	
function is_done_leave($leave_appr_grp, $level){
	$_CI =& get_instance();
	$where = array(
		"emp_id" => $_CI->session->userdata("emp_id"),
		"level <" => $level,
		"approval_groups_via_groups_id" => $leave_appr_grp
	);
	$_CI->db->where($where);
	$query = $_CI->db->get("approval_groups");
	$row = $query->row();
	
	return ($row) ? true : false;
}

function owner_counter_all_pending_leaves($company_id){
	/* $_CI =& get_instance();
	$where = array(
		"el.company_id" =>$company_id,
		"el.deleted" => '0',
		"el.leave_application_status" => "pending"
	);		
	$_CI->edb->where($where);
	$_CI->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
	$_CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
	$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
	$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
	$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
	$_CI->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    $_CI->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
    $_CI->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
    $_CI->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
    $_CI->db->group_by("el.employee_leaves_application_id");
	$query = $_CI->edb->get("employee_leaves_application AS el");
	$result = $query->result();
	
	return ($result) ? $query->num_rows() : 0; */
	return counter_all_pending_leaves($company_id);
}

function counter_all_pending_overtimes($company_id){
	$q = count_ot($company_id);
	$for_pagi = 0;
	if($q){
		foreach ($q as $approvers){
			$overtime_approval_grp = $approvers->overtime_approval_grp;
			$level = $approvers->level;
			$check = check_assigned_overtime($overtime_approval_grp, $level, $company_id);
			if($check){
				$for_pagi++;
			}
		}
	}
	return (is_workflow_enabled($company_id)) ? $for_pagi : "0";
}
function counter_all_payroll_approver_level_counter($emp_id,$company_id){
	$_CI =& get_instance();
	$w = array(
			"company_id"=>$company_id,
			"name"=>"Payroll"
	);
	$_CI->db->where($w);
	$q = $_CI->db->get("approval_process");
	$r = $q->row();
	if($r){

		$approval_process_id = $r->approval_process_id;
		$w = array(
				"ag.approval_process_id"=>$approval_process_id,
				"ag.emp_id"=>$emp_id, //
				"ag.company_id"=>$company_id
		);
		$_CI->db->where($w);
		$_CI->db->join("approval_groups_via_groups AS agv","agv.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
		$q = $_CI->db->get("approval_groups AS ag");
		$r = $q->row();

		if($r){
			// payroll approval
			$_CI->db->select("COUNT(*) AS total");
			$w = array(
					"level <= " => $r->level, //
					"level <= " => 2,
					"comp_id"=>$company_id,
					"payroll_status"=>"pending",
					"status"=>"Active"
							);
							$_CI->db->where($w);
							$q = $_CI->db->get("approval_payroll");
							$r = $q->row();
							return ($r) ? $r->total : FALSE ;
							}else{
							return FALSE;
	}
	
	// return ($r) ? $r : FALSE ;
	}else{
		return FALSE;
	}
}
function counter_all_pending_employee_work_schedule_application($company_id){
	$q = count_pending_employee_work_schedule_application($company_id);
	$for_pagi = 0;
	if($q){
		foreach ($q as $approvers){
			$work_schedule_approval_grp = $approvers->shedule_request_approval_grp;
			$level = $approvers->level;
			$check = check_assigned_work_schedule($work_schedule_approval_grp, $level, $company_id);
			if($check){
				$for_pagi++;
			}
		}
	}
	return (is_workflow_enabled($company_id)) ? $for_pagi : "0";
}
function approval_overtime($emp_id){
	$_CI =& get_instance();
	if(is_numeric($emp_id)){
		$_CI->db->where('ap.name','Overtime Approval Group');
		$_CI->db->where('ag.emp_id',$emp_id);
		$_CI->db->join('approval_process AS ap','ap.approval_process_id = ag.approval_process_id','LEFT');
		$q = $_CI->db->get('approval_groups AS ag');
		$r = $q->row();
		
		return ($r) ? $r->level : FALSE;
	}else{
		return false;
	}
}

function count_ot($company_id){
	$_CI =& get_instance();
	$approver_emp_id = ($_CI->session->userdata("user_type_id") == 2) ? "-99{$company_id}" : $_CI->session->userdata("emp_id");
	$where = array(
		'o.company_id' => $company_id,
		'o.deleted'	=> '0',
		"ag.emp_id" => $approver_emp_id,
		"o.overtime_status" => "pending" 
	);
	$where2 = array(
		"ao.level !=" => ""
	);
	$select = array(
		"*"
	);
	$_CI->db->select($select);
	$_CI->edb->where($where);
	$_CI->db->where($where2);
	$_CI->edb->join('employee AS e','e.emp_id = o.emp_id','left');
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
	
	return ($result) ? $result : false;
}
function count_pending_employee_work_schedule_application($company_id){
	$_CI =& get_instance();
	$approver_emp_id = ($_CI->session->userdata("user_type_id") == 2) ? "-99{$company_id}" : $_CI->session->userdata("emp_id");
	$where = array(
			'wsa.company_id' => $company_id,
			"ag.emp_id" => $approver_emp_id,
			"wsa.employee_work_schedule_status" => "pending"
	);
	$where2 = array(
			"ws.level !=" => ""
	);
	$select = array(
			"ws.approver_id",
			"epi.shedule_request_approval_grp",
			"ws.level"
	);
	$select2 = array(
			"e.first_name"
	);
	$_CI->db->select($select);
	$_CI->edb->select($select2);
	$_CI->db->where($where);
	$_CI->db->where($where2);
	$_CI->edb->join('employee AS e','e.emp_id = wsa.emp_id','left');
	$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
	$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
	$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
	$_CI->edb->join("approval_groups_via_groups AS agg","epi.shedule_request_approval_grp = agg.approval_groups_via_groups_id","LEFT");
	$_CI->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
	$_CI->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
	$_CI->edb->join("approval_work_schedule AS ws","ws.employee_work_schedule_application_id = wsa.employee_work_schedule_application_id","LEFT");
	$_CI->db->group_by("wsa.employee_work_schedule_application_id");
	$query = $_CI->edb->get('employee_work_schedule_application AS wsa');
	$result = $query->result();
	return ($result) ? $result : false;
}
function check_assigned_overtime($overtime_appr_grp, $level, $company_id){
	$_CI =& get_instance();
	$approver_emp_id = ($_CI->session->userdata("user_type_id") == 2) ? "-99{$company_id}" : $_CI->session->userdata("emp_id");
	$where = array(
		"emp_id" => $approver_emp_id,
		"level" => $level,
		"approval_groups_via_groups_id" => $overtime_appr_grp
	);
	$_CI->db->where($where);
	$query = $_CI->db->get("approval_groups");
	$row = $query->row();
	
	return ($row) ? true : false;
}
function check_assigned_work_schedule($shifts_appr_grp, $level, $company_id){
	$_CI =& get_instance();
	$approver_emp_id = ($_CI->session->userdata("user_type_id") == 2) ? "-99{$company_id}" : $_CI->session->userdata("emp_id");
	$where = array(
			"emp_id" => $approver_emp_id,
			"level" => $level,
			"approval_groups_via_groups_id" => $shifts_appr_grp
	);
	$_CI->db->where($where);
	$query = $_CI->db->get("approval_groups");
	$row = $query->row();

	return ($row) ? true : false;
}

function is_done_ot($overtime_appr_grp, $level){
	$_CI =& get_instance();
	$where = array(
		"emp_id" => $_CI->session->userdata("emp_id"),
		"level <" => $level,
		"approval_groups_via_groups_id" => $overtime_appr_grp
	);
	$_CI->db->where($where);
	$query = $_CI->db->get("approval_groups");
	$row = $query->row();
	
	return ($row) ? true : false;
}

function owner_counter_all_pending_overtimes($company_id){
	/* $_CI =& get_instance();
	$where = array(
		'o.company_id' => $company_id,
		'o.deleted'	=> '0',
		"o.overtime_status" => "pending" 
	);
	$_CI->edb->where($where);
	$_CI->edb->join('employee AS e','e.emp_id = o.emp_id','left');
	$_CI->edb->join('accounts AS a','a.account_id = e.account_id','left');
	$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
	$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
	$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
	$_CI->db->order_by("o.overtime_date_applied","DESC");
	$_CI->db->order_by("o.overtime_id","DESC");
	$_CI->db->group_by("o.overtime_id");

	$query = $_CI->edb->get('employee_overtime_application AS o');
	$result = $query->result();
	
	return ($result) ? $query->num_rows() : 0; */
	return counter_all_pending_overtimes($company_id);
}

function counter_all_pending_expenses($company_id){
	$_CI =& get_instance();
	$where = array(
		"ex.company_id" => $company_id,
		"ex.status" => "Active",
		"ex.expense_status" => "pending"
	);
	$_CI->db->select('count(*) as counter');
	$_CI->db->where($where);
	$_CI->edb->join("employee AS e","e.emp_id = ex.emp_id","INNER");
	$_CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
	$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
	$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
	$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
	$query = $_CI->edb->get("expenses AS ex");	
	$row = $query->row();
	
	return ($row) ? $row->counter : 0;
}

function get_employee_details($account_id){
	$_CI =& get_instance();
	$name = "";
	$where = array(
		"account_id" => $account_id
	);
	$_CI->db->where($where);
	$query = $_CI->edb->get("employee");
	$emp_row = $query->row();
	if($emp_row){
		$name = ($emp_row) ? $emp_row->first_name." ".$emp_row->last_name : "~";
	}
	else{
		$_CI->db->where($where);
		$query = $_CI->edb->get("company_owner");
		$own_row = $query->row();
		
		$name = ($own_row) ? $own_row->first_name." ".$own_row->last_name : "~";
	}
	return $name;
}
	
function get_ranks_data($company_id){
	$where = array(
		"company_id" => $company_id,
		"status" => "Active"
	);
	$result = get_table_info_all("rank", $where);
	
	return ($result) ? $result : false;
}

function get_work_schedule_data($company_id){
	$where = array(
		"comp_id" => $company_id,
		"status" => "Active"
	);
	$result = get_table_info_all("work_schedule", $where);
	
	return ($result) ? $result : false;
}

function get_payroll_group_data($company_id){
	$where = array(
		"company_id" => $company_id,
		"status" => "Active"
	);
	$result = get_table_info_all("payroll_group", $where);
	
	return ($result) ? $result : false;
}


function set_default_payroll_groups($company_id){
	if(!get_payroll_group_data($company_id)){
		$rank = get_ranks_data($company_id);
		$work_schedule = get_work_schedule_data($company_id);
		$number = 0;
			
		foreach ($work_schedule as $ws){
			foreach ($rank as $r){
				$number++;
				$name = "SM".str_pad($number,2,0,STR_PAD_LEFT)."-".$ws->name."-".$r->rank_name;
				$data = array(
					"name" => $name,
					"payroll_group_description" => "Default",
					"period_type" => "Semi Monthly",
					"pay_rate_type" => "By Month",
					"company_id" => $company_id,
					"work_schedule_id" => $ws->work_schedule_id,
					"rank_id" => $r->rank_id
				);
				esave("payroll_group", $data);
			}
		}
	}
}
		
function send_to_message_board($psa_id, $emp_id = "", $from_account_id, $company_id, $message, $via, $type = "information", $owner_account_id = "", $emp_who = "", $module = "", $link = "", $exclude=""){
	     
	$data = array(
		"psa_id" => $psa_id,
		"emp_id" => $emp_id,
		"creator_account_id" => $from_account_id,
		"recipient_account_id" => $owner_account_id,
		"emp_who" => $emp_who,
		"company_id" => $company_id,
		"type" => $type,
		"module" => $module,
		"link" => $link,
		"message" => $message,
		"via" => $via,
		"type" => $type,
		"role_status" => ($exclude != "") ? $exclude : "1",
		"date" => date("Y-m-d H:i:s")
	);
	esave("message_board", $data);
}

function post_to_message_board($psa_id, $recipient_account_id, $from_account_id, $company_id, $message, $via){
	
	$where = array(
		"emp_id" => 0,
		"creator_account_id" => $from_account_id,	
		"company_id" => $company_id,
		"message" => $message
	);
	$check_exists = get_table_info("message_board",$where);
	
	if(!$check_exists) {
		$data = array(
			"psa_id" => $psa_id,
			"recipient_account_id" => $recipient_account_id,
			"creator_account_id" => $from_account_id,
			"company_id" => $company_id,
			"link" => "",
			"message" => $message,
			"via" => $via,
			"date" => date("Y-m-d H:i:s")
		);
		esave("message_board", $data);
	}
	
}

function count_employees_on_leave($company_id){
	$_CI =& get_instance();
	$where = array(
		"company_id" => $company_id,
		"leave_application_status" => "approve",
		"status" => "Active",
		"DATE(date_start) <=" => date("Y-m-d"),
		"DATE(date_end) >=" => date("Y-m-d")
	);
	$_CI->db->where($where);
	$_CI->db->where("flag_parent IS NOT NULL");
	$query = $_CI->db->get("employee_leaves_application");
	$result = $query->result();
	
	return ($result) ? $query->num_rows() : 0;
}

function count_employee_timeins($company_id){
	$_CI =& get_instance();
	$counter = 0;
	$today = date("Y-m-d");
	$yesterday = date("Y-m-d",strtotime("{$today} -1 day"));
	
	$where = array(
		"comp_id" => $company_id
	);
	$_CI->db->where("(date = '{$today}' OR date = '{$yesterday}') AND time_out IS NULL");
	$_CI->db->where($where);
	$query = $_CI->db->get("employee_time_in");
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
									$hours_per_day = $r->total_hours_for_the_day;
									$hours = floor($hours_per_day);
									$mins = round(60 * ($hours_per_day - $hours));
									
									$start_time = date("H:i:s",strtotime($r->latest_time_in_allowed));
									$end_time = date("H:i:s",strtotime("{$start_time} +{$hours} hours +{$mins} minutes"));
		
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
			if($work_schedule_id == "-1"){
				$counter = $counter + 1;
			}
		}
	}
	return $counter;
}

function count_absent_emp($company_id){
	$_CI =& get_instance();
	$_CI->load->model('dashboard/absent_model','am');
	$counter = 0;
	$today = date("Y-m-d");
	
	$date1 = date("Y-m-d",strtotime("{$today} -1 day"));
	$date2 = date("Y-m-d",strtotime("{$today} -1 day"));
	
	$where = array(
		"e.company_id" => $company_id,
		"e.status" => "Active",
		"epi.employee_status" => "Active",
		"epi.timesheet_required" => "yes",
		"epi.status" => "Active",
	);
	$_CI->db->where($where);
	$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
	$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
	$_CI->edb->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
	$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
	$_CI->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
	$_CI->db->group_by("e.emp_id");
	$query = $_CI->edb->get("employee AS e");
	$result = $query->result();
	$filter_emp_id = array();
	
	if($result){
		foreach($result as $indx => $row){
			$start = $date1;
			while($start <= $date2){
				$emp_id = $row->emp_id;
				$w = array(
					"emp_id" => $emp_id,
					"date" => $start,
					"status" => "Active"
				);
				$_CI->db->where($w);
				$time_in = $_CI->db->get("employee_time_in");
				$time_in_row = $time_in->row();
					
				if(!$time_in_row){
					$custom_sched = $_CI->am->check_if_custom_schedule($emp_id, $start);
					$work_schedule_id = ($custom_sched) ? $custom_sched->work_schedule_id : $row->work_schedule_id ;
					
					// regular schedules
					$weekday = date('l',strtotime($start));
					$rest_day = $_CI->am->get_rest_day($company_id, $work_schedule_id, $weekday);
					$holiday = $_CI->am->get_holiday($company_id, $start);
	
					if(!check_employee_on_leave($row->emp_id) && !$rest_day && !$holiday){
						if(!in_array($row->emp_id,$filter_emp_id)){
							array_push($filter_emp_id, $row->emp_id);
							$counter = $counter + 1;
						}
					}
	
					// flexible schedule
					$workday = get_workday($company_id, $work_schedule_id);
	
					if($workday != FALSE){
						if($workday->workday_type == "Flexible Hours"){
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
								if(!check_employee_on_leave($row->emp_id) && !$rest_day && !$holiday){
									if(!in_array($row->emp_id,$filter_emp_id)){
										array_push($filter_emp_id, $row->emp_id);
										$counter = $counter + 1;
									}
								}
							}
						}
					}
				}
				$start = date("Y-m-d",strtotime("{$start} +1 day"));
			}
		}
	}
	return $counter;
}

/**
 * Get workday
 * @param int $company_id
 * @param int $work_schedule_id
 */
function get_workday($company_id,$work_schedule_id)
{
	$_CI =& get_instance();
	$s = array("*","work_type_name AS workday_type");
	$_CI->db->select($s);
	$where = array(
			'comp_id' 	   => $company_id,
			"work_schedule_id"=>$work_schedule_id
	);
	$_CI->db->where($where);
	$q = $_CI->db->get('work_schedule');
	$result = $q->row();
	return ($result) ? $result : false;
}

function count_absent_employees($company_id){
	$_CI =& get_instance();
	$today = date("Y-m-d");
	// regular schedule
	$where = array(
		"e.company_id" => $company_id,
		"e.status" => "Active",
		"epi.employee_status" => "Active",
		"epi.timesheet_required" => "yes",
		"epi.status" => "Active"
	);
	$_CI->db->where($where);
	$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
	$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
	$_CI->edb->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
	$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
	$_CI->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
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
								$hours_per_day = $r->total_hours_for_the_day;
								$hours = floor($hours_per_day);
								$mins = round(60 * ($hours_per_day - $hours));
								
								$start_time = $r->latest_time_in_allowed;
								$end_time = date("H:i:s",strtotime($start_time." +{$hours} hours +{$mins} minutes"));
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

function count_absent_employees2($company_id){
	$_CI =& get_instance();
	// regular schedule
	$where = array(
		"ws.work_type_name"=>"Uniform Working Days",
		"e.company_id" => $company_id,
		"rs.work_start_time <= " => date("H:i:s"),
		"rs.work_end_time >= " => date("H:i:s"),
		"rs.days_of_work" => date("l"),
		"e.status" => "Active",
		"epi.employee_status" => "Active",
		"epi.status" => "Active"
	);
	$_CI->db->where($where);
	$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
	$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
	$_CI->edb->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
	$_CI->edb->join("regular_schedule AS rs","rs.work_schedule_id = ws.work_schedule_id","LEFT");
	$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
	$_CI->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
	$_CI->db->group_by("e.emp_id");
	$query = $_CI->edb->get("employee AS e");
	$result = $query->result();
	
	$counter = 0;
	
	if($result){
		foreach($result as $row){
			$custom_sched = check_employee_custom_schedule($row->emp_id);
			
			if($custom_sched){
				$work_sched_id = $custom_sched->work_schedule_id;
					
				$w = array(
					"work_schedule_id" => $work_sched_id,
					"work_start_time <= " => date("H:i:s"),
					"work_end_time >= " => date("H:i:s"),
					"days_of_work" => date("l")
				);
				$query = $_CI->db->get("regular_schedule");
				$r = $query->row();
					
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
						$start_time = $r->work_start_time;
						$end_time = $r->work_end_time;
						if($start_time != "" && $end_time != ""){
							if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
								if(!check_employee_on_leave($row->emp_id)){
									$counter++;
								}
							}
						}
					}
				}
			}
			else{
				$emp_id = $row->emp_id;
				$w = array(
						"emp_id"=>$emp_id,
						"date"=>date("Y-m-d"),
						"status"=>"Active"
				);
				$_CI->db->where($w);
				$time_in = $_CI->db->get("employee_time_in");
				$time_in_row = $time_in->row();
				if(!$time_in_row){
					$start_time = $row->work_start_time;
					$end_time = $row->work_end_time;
					if($start_time != "" && $end_time != ""){
						if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
							if(!check_employee_on_leave($row->emp_id)){
								$counter++;
							}
						}
					}
				}
			}
		}
	}
	
	return $counter;
}

function check_employee_custom_schedule($emp_id){
	$CI =& get_instance();
	$where = array(
		"emp_id" => $emp_id,
		"valid_from <=" => date("Y-m-d"),
		"until >=" => date("Y-m-d"),
		"payroll_group_id" => 0
	);
	$CI->db->where($where);
	$query = $CI->db->get("employee_shifts_schedule");
	$row = $query->row();

	return ($row) ? $row : false;
}

function check_employee_on_leave($emp_id){
	$CI =& get_instance();
	$where = array(
			"ela.emp_id" => $emp_id,
			"ela.credited >" => 0,
			"DATE(ela.date_start) <=" => date("Y-m-d"),
			"DATE(ela.date_end) >=" => date("Y-m-d")
	);
	$CI->db->where($where);
	$query = $CI->db->get("employee_leaves_application AS ela");
	$row = $query->row();
	
	return ($row) ? true : false;
}


function work_schedule_id($company_id, $emp_id, $date=NULL){
	$_CI =& get_instance();
	$w_date = array(
		"valid_from <="		=>	$date,
		"until >="			=>	$date
	);
	if($date != NULL) $_CI->db->where($w_date);
	
	$w = array(
		"emp_id"=>$emp_id,
		"company_id"=>$company_id,
		"status"=>"Active"
	);
	$_CI->db->where($w);
	$q = $_CI->db->get("employee_shifts_schedule");
	$r = $q->row();
	
	if($r){
		// split scheduling
		return $r;
	}else{
		// default work scheduling
		$w = array(
			"epi.emp_id"=>$emp_id,
			"epi.company_id"=>$company_id,
			"epi.status"=>"Active"
		);
		$_CI->db->where($w);
		$_CI->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$q = $_CI->db->get("employee_payroll_information AS epi");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
}

/**
 * check if workflow is enabled/disabled
 * @param unknown $company_id
 * @return boolean
 */
function is_workflow_enabled($company_id){
	$_CI =& get_instance();
	$where = array(
		"company_id" => $company_id,
		"status" => "Active"
	);
	$_CI->db->where($where);
	$query = $_CI->db->get("approval_settings");
	$row = $query->row();
	
	return ($row) ? true : false;
}

/**
 * get notification settings
 * @param unknown $approval_groups_via_groups_id
 * @param unknown $company_id
 * @return boolean
 */
function get_notify_settings($approval_groups_via_groups_id,$company_id){
	$_CI =& get_instance();
	$where = array(
		"company_id" => $company_id,
		"approval_groups_via_groups_id" => $approval_groups_via_groups_id,
		"status" => "Active"
	);
	$_CI->db->where($where);
	$query = $_CI->db->get("approval_groups_via_groups");
	$row = $query->row();
	
	return ($row) ? $row : false;
}

/**
 * check if approvers turn to approve application
 */
function get_current_approver_level($approval_groups_via_groups_id,$emp_id,$company_id){
	$_CI =& get_instance();
	$where = array(
		"company_id" => $company_id,
		"approval_groups_via_groups_id" => $approval_groups_via_groups_id,
		"emp_id" => $emp_id
	);
	$_CI->db->where($where);
	$query = $_CI->db->get("approval_groups");
	$row = $query->row();
	
	return ($row) ? $row->level : -1;
}

/**
 * get approvers emp id via account id
 * @param unknown $account_id
 * @return number
 */
function get_approver_emp_id_via_account_id($account_id){
	$_CI =& get_instance();
	$where = array(
		"account_id" => $account_id
	);
	$_CI->db->select("emp_id");
	$_CI->db->where($where);
	$query = $_CI->db->get("employee");
	$row = $query->row();
	
	return ($row) ? $row->emp_id : -1;
}

function get_next_approvers($approval_groups_via_groups_id, $level, $company_id){
	$_CI =& get_instance();
	$where = array(
		"ag.company_id" => $company_id,
		"ag.approval_groups_via_groups_id" => $approval_groups_via_groups_id,
		"ag.level" => $level
	);
	$_CI->db->where($where);
	$_CI->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
	$_CI->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
	$query = $_CI->edb->get("approval_groups AS ag");
	$result = $query->result();
	
	return ($result) ? $result : false;
}

function get_approver_owner_info($company_id){
	$_CI =& get_instance();
	$w = array(
		"accounts.user_type_id" => "2",
		"assigned_company.company_id" => $company_id
	);
	$_CI->edb->where($w);
	$_CI->edb->join("company_owner","accounts.account_id = company_owner.account_id","INNER");
	$_CI->edb->join("payroll_system_account","accounts.account_id = payroll_system_account.account_id","INNER");
	$_CI->edb->join("assigned_company","payroll_system_account.payroll_system_account_id = assigned_company.payroll_system_account_id","INNER");
	$_CI->edb->join("company","assigned_company.company_id = company.company_id","INNER");
	$q = $_CI->edb->get("accounts");
	$row = $q->row();
	
	return($row) ? $row : false;
}

/**
 * message board notification for pending overtime approval
 * @param unknown $company_id
 * @param unknown $emp_id
 * @param unknown $psa_id
 * @param unknown $domain
 */
function notify_approver_overtime_pending($company_id, $emp_id, $account_id,$psa_id, $domain,$user_roles_id=""){
	$_CI =& get_instance();
	clear_old_notify($emp_id, "is_notified_overtime_approval");
	
	$status_privilege = check_assigned_user_role($emp_id, $company_id,$user_roles_id,$account_id, "approval_overtime");
	
	if(!check_notify_approval($emp_id, "is_notified_overtime_approval") && counter_all_pending_overtimes($company_id) > 0){
		// message board notification
		$message_board_data = array(
			"emp_id" => $emp_id,
			"psa_id" => $psa_id,
			"company_id" => $company_id,
			"message" => "You have employee(s) which has pending <a href='/{$domain}/workforce/approve_overtime/lists' target='_blank'><strong>overtime approval</strong></a>.",
			"via" => "system",
			"module" => "todo_overtime",
			"date" => date("Y-m-d H:i:s"),
			"type" => "warning",
			"link" => "is_notified_overtime_approval",
			"role_status" => $status_privilege
		);
		$insert = $_CI->db->insert("message_board", $message_board_data);
	}
}

/**
 * message board notification for pending leave approval
 * @param unknown $company_id
 * @param unknown $emp_id
 * @param unknown $psa_id
 * @param unknown $domain
 */
function notify_approver_leave_pending($company_id, $emp_id,$account_id,$psa_id, $domain,$user_roles_id=""){
	$_CI =& get_instance();
	clear_old_notify($emp_id, "is_notified_leave_approval");
	$status_privilege = check_assigned_user_role($emp_id, $company_id,$user_roles_id,$account_id, "approval_leaves");
	if(!check_notify_approval($emp_id, "is_notified_leave_approval") && counter_all_pending_leaves($company_id) > 0){
		// message board notification
		$message_board_data = array(
			"emp_id" => $emp_id,
			"psa_id" => $psa_id,
			"company_id" => $company_id,
			"message" => "You have employee(s) which has pending <a href='/{$domain}/workforce/approve_leave/lists' target='_blank'><strong>leave approval</strong></a>.",
			"via" => "system",
			"date" => date("Y-m-d H:i:s"),
			"type" => "warning",
			"module" => "todo_leave",
			"link" => "is_notified_leave_approval",
			"role_status" => $status_privilege
		);
		$insert = $_CI->db->insert("message_board", $message_board_data);
	}
}

/**
 * clear notification
 * @param unknown $emp_id
 * @param string $link
 */
function clear_old_notify($emp_id, $link = "is_notified_overtime_approval"){
	$_CI =& get_instance();
	$where = array(
		"emp_id" => $emp_id,
		"link" => $link
	);
	$_CI->db->where($where);
	$delete = $_CI->db->delete("message_board");
	
	return ($delete) ? true : false;
}

/**
 * check if is notified on current date
 * @param unknown $emp_id
 * @param string $link
 * @return boolean
 */
function check_notify_approval($emp_id, $link = "is_notified_overtime_approval"){
	$_CI =& get_instance();
	$where = array(
		"emp_id" => $emp_id,
		"link" => $link,
		"DATE(date)" => date("Y-m-d")
	);
	$_CI->db->where($where);
	$query = $_CI->db->get("message_board");
	$row = $query->row();
	
	return ($row) ? true : false;
}

/**
 * check if night shift differential settings v2 updated
 * @param unknown $company_id
 * @return boolean
 */
function is_nsd_settings_updated($company_id){
	$_CI =& get_instance();
	$where = array(
		"company_id" => $company_id,
		"flag_updated" => "1"
	);
	$_CI->db->where($where);
	$query = $_CI->db->get("nightshift_differential_settings");
	$row = $query->row();
	
	return ($row) ? true : false;
}

/**
 * generate tracking transaction number
 * @param unknown $id
 * @return string
 */
function generate_track_transaction_number($id){
	$date = date("Y-m");
	$padd = str_pad($id, 7, '0', STR_PAD_LEFT);
	$tracking_number = "{$date}-$padd";
	
	return $tracking_number;
}

/**
 * track settings
 * @param unknown $company_id
 * @return Ambigous <boolean, unknown>
 */
function track_settings_enabled(){
	$company = whose_company();
	$status = false;
	
	$CI =& get_instance();
	$where = array(
		"company_id" => $company->company_id,
		"status" => "Active"
	);
	$CI->db->where($where);
	$query = $CI->db->get("track_settings");
	$row = $query->row();
	if($row){
		if($row->enable == "yes"){
			$status = true;
		}
	}
	
	return $status;
}

/**
 * check if user is signatory
 * @param unknown $account_id
 */
function is_track_signatory(){
	$company = whose_company();
	
	$CI =& get_instance();
	$where = array(
		"company_id" => $company->company_id,
		"account_id" => $CI->session->userdata('account_id'),
		"status" => "Active"
	);
	$CI->db->where($where);
	$query = $CI->db->get("track_document_signatories");
	$row = $query->row();
	
	return ($row) ? true : false;
	
}

/**
 * generate default contribution calculation
 * @param unknown $company_id
 */
function default_contribution_calculation($company_id){
	$CI =& get_instance();
	$has = get_table_info("contribution_calculation_settings", array("company_id" => $company_id, "status" => "Active"));
	if(!$has){
		$data = array(
			"sss_contribution_table" => "yes",
			"sss_fixed_rate" => "no",
			"philhealth_contribution_table" => "yes",
			"philhealth_fixed_rate" => "no",
			"hdmf_fixed_hundred" => "yes",
			"hdmf_percent_base_pay" => "no",
			"hdmf_percent_base_pay_fixed_hundred" => "no",
			"company_id" => $company_id
		);
		$insert = $CI->db->insert("contribution_calculation_settings", $data);
		
		return ($insert) ? true : false;
	}
}
/* ---------------------------- [ NENE <reyneil_27@yahoo.com> VERSION 3 END ] ---------------------------- */





/* ---------------------------- [ FIL <filsandalojr@gmail.com> VERSION 3 START ] ---------------------------- */


function get_employee_details_by_empid($emp_id){
	$CI =& get_instance();
	$name = "";
	$where = array(
			"e.emp_id" => $emp_id
	);
	$CI->db->where($where);
	$CI->edb->join('accounts AS a','e.account_id = a.account_id','LEFT');
	$CI->edb->join('employee_payroll_information AS epi','e.emp_id = epi.emp_id','LEFT');
	$query = $CI->edb->get("employee AS e");
	$emp_row = $query->row();
	return ($emp_row) ? $emp_row : FALSE;

}

function get_owner($psa_id){
	$CI =& get_instance();
	$CI->db->where('psa.payroll_system_account_id',$psa_id);
	$CI->edb->join('company_owner AS co','psa.account_id = co.account_id','LEFT');
	$q = $CI->edb->get('payroll_system_account AS psa');
	$r = $q->row();
	return ($r) ? $r->first_name.' '.$r->last_name : FALSE;
}

function check_ip_exist($ip, $comp_id){
	$CI =& get_instance();
	$where = array(
		'ip_address' => $ip,
		'company_id' => $comp_id	
	);
	$CI->db->where('company_id',$comp_id);
	$q1 = $CI->db->get('employee_ip_address');
	
	if($q1->num_rows > 0){
		$CI->db->where($where);
		$q = $CI->db->get('employee_ip_address');
		$r = $q->row();
		return ($q->num_rows > 0) ? TRUE : FALSE;
	}else{
		return TRUE;
	}
}

/* ---------------------------- [ FIL <filsandalojr@gmail.com> VERSION 3 END ] ---------------------------- */
/**
 * Default Pay rate
 * @param company_id, data from hours_type table
 * Checks hours_type table if no data exist for corresponding company_id then inserts default
 * values to hours_type, overtimme_type, and nightshift_differential_for_premium tables
 */
function default_pay_rate($c_id) {

	$CI =& get_instance();
	
	$where = array(
			"ht.company_id" => $c_id,
			"ht.status" => "Active"
	);
	$CI->db->where($where);
	$query = $CI->edb->get("hours_type AS ht");
	$result = $query->result();
	
	if(!$result){
		$workingday_type = array(
				"Regular" => 100,
				"Rest Day" => 130,
				"Special Day" => 130,
				"Holiday" => 200,
				"Double Holiday" => 300,
				"Special Day falling on a Rest Day" => 150,
				"Holiday falling on a Rest Day" => 260,
				"Double Holiday falling on a Rest Day" => 390
		);
	
		$def = 0;
		foreach ($workingday_type as $wdt => $wdt_val)
		{
			$def = ($wdt == "Regular" || $wdt == "Rest Day") ? $def=1 : $def=0;
			$array = array(
					"hour_type_name" => $CI->db->escape_str($wdt),
					"pay_rate"	=> $CI->db->escape_str($wdt_val),
					"company_id" => $CI->db->escape_str($c_id),
					"default" => $def
			);
			$CI->db->insert("hours_type",$array);
			$insert_id = $CI->db->insert_id();
	
			switch ($wdt) {
				case "Regular":
					$ot_rate = 25;
					$ns_rate = 100;
					break;
				case "Rest Day":
				case "Special Day":
					$ot_rate = 30;
					$ns_rate = 110;
					break;
				case "Holiday":
					$ot_rate = 30;
					$ns_rate = 110;
					break;
				case "Double Holiday":
					$ot_rate = 30;
					$ns_rate = 110;
					break;
				case "Special Day falling on a Rest Day":
					$ot_rate = 30;
					$ns_rate = 110;
					break;
				case "Holiday falling on a Rest Day":
					$ot_rate = 30;
					$ns_rate = 110;
					break;
				case "Double Holiday falling on a Rest Day":
					$ot_rate = 30;
					$ns_rate = 110;
					break;
				default:
					$ot_rate = 0;
					$ns_rate = 0;
			}//switch
				
			$val_ht = array(
					"flag_add_prem"=>"1",
					"hours_type_id"=>$insert_id,
					"pay_rate"=>$ns_rate,
					"company_id"=>$c_id
			);
				
			$data = array(
					"hour_type_id" => $insert_id,
					"ot_rate" => $ot_rate,
					"company_id" => $c_id,
			);
				
			$CI->db->insert("overtime_type", $data);
			$CI->db->insert("nightshift_differential_for_premium",$val_ht);
		}//foreach
		redirect($CI->uri->uri_string());
	}//if
	
	
}

/**
 * Default Pay rate v2
 * @param company_id, data from hours_type table
 * Checks hours_type table if no data exist for corresponding company_id then inserts default
 * values to hours_type, overtimme_type, and nightshift_differential_for_premium tables
 */
function default_pay_rate_v2($c_id) {
	$CI =& get_instance();

	$where = array(
			"ht.company_id" => $c_id,
			"ht.status" => "Active"
	);
	$CI->db->where($where);
	$query = $CI->edb->get("hours_type AS ht");
	$result = $query->result();

	if(!$result){
		// save default not working value
		$workingday_type = array(
				"Regular"			=> 100,
				"Rest Day"			=> 130,
				"Special Holiday" 	=> 100,
				"Regular Holiday"	=> 100,
				"Double Holiday"	=> 200,
					
		);

		// save default working value
		$working = array(
				"Regular" 			=> "",
				"Rest Day"			=> "",
				"Special Holiday"	=> 130,
				"Regular Holiday"	=> 200,
				"Double Holiday"	=> 300

		);

		// save default rest day value
		$rest_day = array(
				"Regular" 			=> "",
				"Rest Day"			=> "",
				"Special Holiday" 	=> 150,
				"Regular Holiday"	=> 260,
				"Double Holiday"	=> 390

		);

		// save default description
		$description = array(
				"Regular" 			=> "Not Working/Working on a Regular Workday",
				"Rest Day"			=> "Working on Rest Day",
				"Special Holiday" 	=> "Not Working/Working on a Regular Workday/Rest Day",
				"Regular Holiday"	=> "Not Working/Working on a Regular Workday/Rest Day",
				"Double Holiday"	=> "Not Working/Working on a Regular Workday/Rest Day"

		);

		// save default hourly value
		// 		$hourly = array(
		// 			"Regular" 			=> "",
		// 			"Rest Day"			=> "",
		// 			"Special Holiday" 	=> 0,
		// 			"Regular Holiday"	=> 100,
		// 			"Double Holiday"	=> ""

		// 		);

		// save default daily value
		$daily = array(
				"Regular" 			=> "",
				"Rest Day"			=> "",
				"Special Holiday" 	=> 0,
				"Regular Holiday"	=> 100,
				"Double Holiday"	=> ""

		);

		$def = 0;
		foreach ($workingday_type as $wdt => $wdt_val)
		{
			$def = ($wdt == "Regular" || $wdt == "Rest Day") ? $def=1 : $def=0;
			$array = array(
					"hour_type_name"	=> $CI->db->escape_str($wdt),
					"pay_rate"			=> $CI->db->escape_str($wdt_val),
					"company_id" 		=> $CI->db->escape_str($c_id),
					"default" 			=> $def,
					"working" 			=> $working[$wdt],
					"rest_day" 			=> $rest_day[$wdt],
					//"hourly" => $hourly[$wdt],
					"daily"				=> $daily[$wdt],
					"description" 		=> $description[$wdt],
					'flag_update'		=> '1'
			);
			$CI->db->insert("hours_type",$array);
			$insert_id = $CI->db->insert_id();

			switch ($wdt) {
				case "Regular":
					$ot_rate = 25;
					$ns_rate = 25;
					break;
				case "Rest Day":
					$ot_rate = 30;
					$ns_rate = 30;
					break;
				case "Special Holiday":
					$ot_rate = 0;
					$ns_rate = 0;
					break;
				case "Regular Holiday":
					$ot_rate = 0;
					$ns_rate = 0;
					break;
				case "Double Holiday":
					$ot_rate = 30;
					$ns_rate = 30;
					break;
				default:
					$ot_rate = 0;
					$ns_rate = 0;
			}//switch
				
			$val_ht = array(
					"flag_add_prem"=>"1",
					"hours_type_id"=>$insert_id,
					"pay_rate"=>$ns_rate,
					"company_id"=>$c_id
			);
				
			$data = array(
					"hour_type_id" => $insert_id,
					"ot_rate" => $ot_rate,
					"company_id" => $c_id,
			);
				
			$CI->db->insert("overtime_type", $data);
			$CI->db->insert("nightshift_differential_for_premium",$val_ht);
		}//foreach
		redirect($CI->uri->uri_string());
	}//if

}//function

/** 
 * Default Pay rate v3
 * @param company_id, data from hours_type table
 * Checks hours_type table if no data exist for corresponding company_id then inserts default
 * values to hours_type, overtimme_type, and nightshift_differential_for_premium tables
 */
function default_pay_rate_v3($c_id) {
	$CI =& get_instance();
	
	$where = array(
			"ht.company_id" => $c_id,
			"ht.status" => "Active"
		);
	$CI->db->where($where);
	$query = $CI->edb->get("hours_type AS ht");
	$result = $query->result();
		
	if(!$result){
		// save default not working value
		$workingday_type = array(
			"Regular"			=> 0,
			"Rest Day"			=> 100,
			"Special Holiday" 	=> 100,
			"Regular Holiday"	=> 100,
			"Double Holiday"	=> 200,
			
		);
		
		// save default working value
		$working = array(
			"Regular" 			=> 100,
			"Rest Day"			=> 130,
			"Special Holiday"	=> 130,
			"Regular Holiday"	=> 200,
			"Double Holiday"	=> 300
		
		);
		
		// save default rest day value
		$rest_day = array(
			"Regular" 			=> 0,
			"Rest Day"			=> 0,
			"Special Holiday" 	=> 150,
			"Regular Holiday"	=> 230,
			"Double Holiday"	=> 390
		
		);
		
		// save default description
		$description = array(
			"Regular" 			=> "Rates on a regular workday (a non holiday).",
			"Rest Day"			=> "Rates when employee is on a rest day (paid day off) as well as if he works on a rest day",
			"Special Holiday" 	=> "Rates during a special non working day.",
			"Regular Holiday"	=> "Rates during a regular holiday day.",
			"Double Holiday"	=> "Rates when two or more holidays happen to land on the same day."
				
		);
		
		// save default hourly value
// 		$hourly = array(
// 			"Regular" 			=> "",
// 			"Rest Day"			=> "",
// 			"Special Holiday" 	=> 0,
// 			"Regular Holiday"	=> 100,
// 			"Double Holiday"	=> ""
		
// 		);
		
		// save default daily value for non working
		$daily = array(
			"Regular" 			=> 0,
			"Rest Day"			=> 0,
			"Special Holiday" 	=> 0,
			"Regular Holiday"	=> 100,
			"Double Holiday"	=> 0
		
		);
		
		// save default daily value for working
		$daily1 = array(
				"Regular" 			=> 0,
				"Rest Day"			=> 0,
				"Special Holiday" 	=> 130,
				"Regular Holiday"	=> 200,
				"Double Holiday"	=> 0
		
		);
		
		$def = 0;
		foreach ($workingday_type as $wdt => $wdt_val) 
		{
			$def = ($wdt == "Regular" || $wdt == "Rest Day") ? $def=1 : $def=0;
			$array = array(
				"hour_type_name"	=> $CI->db->escape_str($wdt),
				"pay_rate"			=> $CI->db->escape_str($wdt_val),
				"company_id" 		=> $CI->db->escape_str($c_id),
				"default" 			=> $def,
				"working" 			=> $working[$wdt],
				"working_daily" 	=> $daily1[$wdt],
				"rest_day" 			=> $rest_day[$wdt],
				//"hourly" => $hourly[$wdt],
				"daily"				=> $daily[$wdt],
				"description" 		=> $description[$wdt],
				'flag_update'		=> '2'
			);
			$CI->db->insert("hours_type",$array);
			$insert_id = $CI->db->insert_id();
	
			switch ($wdt) {
			    case "Regular":
			        $ot_rate = 25;
			        $ns_rate = 10;
			        break;
			   case "Rest Day":
			    	$ot_rate = 30;
			    	$ns_rate = 10;
			    	break;
			    case "Special Holiday":
			      	$ot_rate = 30;
			    	$ns_rate = 10;
			        break;
			    case "Regular Holiday":
			        $ot_rate = 30;
			    	$ns_rate = 10;
			        break;
			    case "Double Holiday":
			       	$ot_rate = 30;
			    	$ns_rate = 10;
			        break;
			    default:
			        $ot_rate = 30;
			    	$ns_rate = 10;
			}//switch
			
			$val_ht = array(
				"flag_add_prem"	=> "1",
				"hours_type_id"	=> $insert_id,
				"pay_rate"		=> $ns_rate,
				"company_id"	=> $c_id
			);
			
			$data = array(
				"hour_type_id"	=> $insert_id,
				"ot_rate"		=> $ot_rate,
				"company_id" 	=> $c_id,
			);
			
			$CI->db->insert("overtime_type", $data);		
			$CI->db->insert("nightshift_differential_for_premium",$val_ht);
		}//foreach
		redirect($CI->uri->uri_string());
	}//if	
	
}//function

/**
 * Default Pay rate v3
 * @param company_id, data from hours_type table
 * Checks hours_type table if no data exist for corresponding company_id then inserts default
 * values to hours_type, overtimme_type, and nightshift_differential_for_premium tables
 */
function default_pay_rate_v4($c_id) {
	$CI =& get_instance();

	$where = array(
			"ht.company_id" => $c_id,
			"ht.status" => "Active"
	);
	$CI->db->where($where);
	$query = $CI->edb->get("hours_type AS ht");
	$result = $query->result();

	if(!$result){
		// save default not working value
		$workingday_type = array(
				"Regular"			=> 0,
				"Rest Day"			=> 1,
				"Special Holiday" 	=> 1,
				"Regular Holiday"	=> 1,
				"Double Holiday"	=> 2,
					
		);

		// save default working value
		$working = array(
				"Regular" 			=> 1,
				"Rest Day"			=> 1.3,
				"Special Holiday"	=> 1.3,
				"Regular Holiday"	=> 2,
				"Double Holiday"	=> 3

		);

		// save default rest day value
		$rest_day = array(
				"Regular" 			=> 0,
				"Rest Day"			=> 0,
				"Special Holiday" 	=> 1.5,
				"Regular Holiday"	=> 2.6,
				"Double Holiday"	=> 3.9

		);

		// save default description
		$description = array(
				"Regular" 			=> "Rates on a regular workday (a non holiday).",
				"Rest Day"			=> "Rates when employee is on a rest day (paid day off) as well as if he works on a rest day",
				"Special Holiday" 	=> "Rates during a special non working day.",
				"Regular Holiday"	=> "Rates during a regular holiday day.",
				"Double Holiday"	=> "Rates when two or more holidays happen to land on the same day."

		);

		// save default hourly value
		// 		$hourly = array(
		// 			"Regular" 			=> "",
		// 			"Rest Day"			=> "",
		// 			"Special Holiday" 	=> 0,
		// 			"Regular Holiday"	=> 100,
		// 			"Double Holiday"	=> ""

		// 		);

		// save default daily value for non working
		$daily = array(
				"Regular" 			=> 0,
				"Rest Day"			=> 0,
				"Special Holiday" 	=> 0,
				"Regular Holiday"	=> 1,
				"Double Holiday"	=> 0

		);

		// save default daily value for working
		$daily1 = array(
				"Regular" 			=> 0,
				"Rest Day"			=> 0,
				"Special Holiday" 	=> 1.3,
				"Regular Holiday"	=> 2,
				"Double Holiday"	=> 0

		);

		$def = 0;
		foreach ($workingday_type as $wdt => $wdt_val)
		{
			$def = ($wdt == "Regular" || $wdt == "Rest Day") ? $def=1 : $def=0;
			$array = array(
					"hour_type_name"	=> $CI->db->escape_str($wdt),
					"pay_rate"			=> $CI->db->escape_str($wdt_val),
					"company_id" 		=> $CI->db->escape_str($c_id),
					"default" 			=> $def,
					"working" 			=> $working[$wdt],
					"working_daily" 	=> $daily1[$wdt],
					"rest_day" 			=> $rest_day[$wdt],
					//"hourly" 			=> $hourly[$wdt],
					"daily"				=> $daily[$wdt],
					"description" 		=> $description[$wdt],
					'flag_update'		=> '3'
			);
			$CI->db->insert("hours_type",$array);
			$insert_id = $CI->db->insert_id();

			switch ($wdt) {
				case "Regular":
					$ot_rate = 1.25;
					$ns_rate = 1.1;
					break;
				case "Rest Day":
					$ot_rate = 1.3;
					$ns_rate = 1.1;
					break;
				case "Special Holiday":
					$ot_rate = 1.3;
					$ns_rate = 1.1;
					break;
				case "Regular Holiday":
					$ot_rate = 1.3;
					$ns_rate = 1.1;
					break;
				case "Double Holiday":
					$ot_rate = 1.3;
					$ns_rate = 1.1;
					break;
				default:
					$ot_rate = 1.3;
					$ns_rate = 1.1;
			}//switch
				
			$val_ht = array(
					"flag_add_prem"	=> "1",
					"hours_type_id"	=> $insert_id,
					"pay_rate"		=> $ns_rate,
					"company_id"	=> $c_id
			);
				
			$data = array(
					"hour_type_id"	=> $insert_id,
					"ot_rate"		=> $ot_rate,
					"company_id" 	=> $c_id,
			);
				
			$CI->db->insert("overtime_type", $data);
			$CI->db->insert("nightshift_differential_for_premium",$val_ht);
		}//foreach
		redirect($CI->uri->uri_string());
	}//if

}

function check_pay_rate_flag_update($company_id){
	$CI =& get_instance();
	$sel = array(
			'ht.flag_update'
	);
	$where = array(
			"ht.company_id" => $company_id,
			"ht.status" => "Active"
	);
	$CI->db->where($where);
	$CI->db->where($where);
	$query = $CI->edb->get("hours_type AS ht");
	$result = $query->result();

	return ($result) ? $result : false;
}

function check_pay_rate_flag_update_row($company_id){
	$CI =& get_instance();
	$sel = array(
			'ht.flag_update'
	);
	$where = array(
			"ht.company_id" => $company_id,
			"ht.status" => "Active"
	);
	$CI->db->where($where);
	$CI->db->where($where);
	$query = $CI->edb->get("hours_type AS ht");
	$result = $query->row();

	return ($result) ? $result->flag_update : false;
}

/**aldrin here**/
function not_in_search_timein_gold($emp_id, $company_id,$search="",$count = false,$cron = false){
	if(is_numeric($company_id)){
		$_CI =& get_instance();
		$konsum_key = konsum_key();
		$level = timein_approval_level_gold($emp_id);
		
		$where = array(
				'ee.comp_id'   => $company_id,
				'ee.status'   => 'Active',
				'ee.corrected' => 'Yes',
				'ee.time_in_status' => 'pending'

		);
		$where2 = array(
				"ati.level !=" => ""
		);


		$_CI->db->where($where);
		$_CI->db->where($where2);

		$_CI->edb->join("employee AS e","e.emp_id = ee.emp_id","INNER");
		$_CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$_CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
		$_CI->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$_CI->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$_CI->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
		$_CI->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
		$_CI->edb->join("approval_time_in AS ati","ati.time_in_id = ee.employee_time_in_id","LEFT");
		$_CI->db->group_by("ee.employee_time_in_id");
		$query = $_CI->edb->get("employee_time_in AS ee");
		$result = $query->result();

		$arrs = array();
		if($result){
			$is_assigned = TRUE;
			$hours_notification = get_hours_notification_settings_gold($company_id);
			if($count){
			foreach($result as $key => $approvers){
				if($approvers->flag_add_logs == 0){
					$appr_grp = $approvers->attendance_adjustment_approval_grp;
				}elseif($approvers->flag_add_logs == 1){
					$appr_grp = $approvers->add_logs_approval_grp;
				}elseif($approvers->flag_add_logs == 2){
					$appr_grp = $approvers->location_base_login_approval_grp;
				}
					
				$level = $approvers->level;
					
				$check = check_assigned_hours_gold($appr_grp, $level,$emp_id,$cron);
		
				if($check && $is_assigned){

				}else{
					array_push($arrs, $approvers->employee_time_in_id);
				}
			}
			}else{
			
				foreach($result as $key => $approvers){
					if($approvers->flag_add_logs == 0){
						$appr_grp = $approvers->attendance_adjustment_approval_grp;
					}elseif($approvers->flag_add_logs == 1){
						$appr_grp = $approvers->add_logs_approval_grp;
					}elseif($approvers->flag_add_logs == 2){
						$appr_grp = $approvers->location_base_login_approval_grp;
					}
					$level = $approvers->level;
					if($count){
						$is_done = check_assigned_hours_gold($appr_grp, $level,$emp_id,$cron);
					}else{
						$is_done = is_done_timein($appr_grp, $level, $emp_id,$count);
					}
					if($is_done){
						array_push($arrs, $approvers->employee_time_in_id);
					}
				
				}
			}
				
		}
		$string = implode(",", $arrs);
		return $string;
		//return 0;
	}else{
		return false;
	}
}

function not_activate(){
	$msg = "Your Ashima account was temporarily suspended.  Please contact your Ashima Account Manager at <span>support@konsum.ph</span> or call us at <span>+63 32 479 9999</span> for further assistance.";
		
		
	$html = "<div class='alert_deactive'><h3>Account Suspended</h3>".$msg."</div>";
	return $html;
}

function is_timein_exist($emp_id,$date){
	
	$_CI =& get_instance();
	
	$where = array(
			'ee.date'   => $date,
			'ee.emp_id' => $emp_id,
			'ee.status'   => 'Active',
			'ee.time_in_status !=' => 'reject'	
	);
	
	$_CI->db->where($where);
	$query = $_CI->edb->get("employee_time_in AS ee");
	$result = $query->row();
	
	return ($result) ? true : false;
}

/**
 * gets the notification settings for hours
 * @param int $company_id
 * @return <object, boolean>
 */
function get_hours_notification_settings_gold($company_id){
	$CI =& get_instance();
	if(is_numeric($company_id)){
		$where = array(
				//	'psa_id'	 => $CI->session->userdata("psa_id"),
				'hns.company_id' => $company_id,
				'hns.via'	=> 'default',
				'hns.status'	 => 'Active'
		);
		$CI->edb->join('hours_alerts_notification AS han','hns.hours_alerts_notification_id = han.hours_alerts_notification_id','LEFT');
		$CI->db->where($where);
		$query = $CI->edb->get('hours_notification_settings AS hns');
		$row = $query->row();
		return ($row) ? $row : FALSE;
	}

}
function check_if_is_level_hours_gold($level, $alert_id){
	$CI =& get_instance();
	if(is_numeric($alert_id)){
		$where = array(
				'level'=>$level,
				'hours_alerts_notification_id'=>$alert_id
		);
		$CI->db->where($where);
		$q = $CI->edb->get('hours_notification_leveling');
		$r = $q->row();
		return ($r) ? TRUE : FALSE;

	}
}


function  counter_all_pending_timein($company_id){

			if(is_numeric($company_id)){
				$_CI =& get_instance();
				$konsum_key = konsum_key();
				$search = "";
				$emp_id = ($_CI->session->userdata("user_type_id") == 2) ? "-99{$company_id}" : $_CI->session->userdata("emp_id");
				$filter = not_in_search_timein_gold($emp_id, $company_id, $search,true);
				//$level = $this->timein_approval_level($this->session->userdata('emp_id'));
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
		
				if($search != "" && $search != "all"){
 					$_CI->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
 				}
				
				$_CI->db->where($where);
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
		
				$row = $query->num_rows();
	
				return (is_workflow_enabled($company_id)) ? $row : "0";
			}else{
				return 0;
			}
	
}
/**
 *
 * @param int $overtime_appr_grp
 * @param int $level
 * @return boolean
 */
function timein_approval_level_gold($emp_id){
	if(is_numeric($emp_id)){
		$_CI =& get_instance();
		$_CI->db->where('ap.name','Time In Approval Group');
		$_CI->db->where('ag.emp_id',$emp_id);
		$_CI->edb->join('approval_process AS ap','ap.approval_process_id = ag.approval_process_id','LEFT');
		$q = $_CI->edb->get('approval_groups AS ag');
		$r = $q->row();

		return ($r) ? $r->level : FALSE;
	}else{
		return false;
	}
}

/***
 * if disable workflow
 * approve all pending timesheets
 */
function approve_all_timesheets($date,$company_id, $status = ""){

	$month = date('Y-m-d',strtotime($date.' -1 month'));
	
	$_CI =& get_instance();
	
	$arr = array(
			'date(eti.change_log_date_filed) >=' => $month,
			'eti.comp_id' => $company_id
	);
	$_CI->db->where($arr);
	$_CI->db->where(" (eti.time_in_status ='pending' OR eti.split_status='pending') ",NULL,FALSE);
	$_CI->edb->join('approval_time_in AS ati','ati.time_in_id = eti.employee_time_in_id','LEFT');
	$q = $_CI->edb->get('employee_time_in AS eti');
	$r = $q->result();
	
	
	if($r){
		foreach($r as $row){
	
			if($row->flag_add_logs){
				$concat_time_in = ($row->change_log_time_in!=NULL)? date("Y-m-d H:i:s",strtotime($row->change_log_time_in)) : NULL;
				$concat_lunch_in =  ($row->change_log_lunch_in!=NULL)? date("Y-m-d H:i:s",strtotime($row->change_log_lunch_in)) : NULL;
				$concat_lunch_out =  ($row->change_log_lunch_out!=NULL)? date("Y-m-d H:i:s",strtotime($row->change_log_lunch_out)) : NULL;
				$concat_time_out =  ($row->change_log_time_out!=NULL)? date("Y-m-d H:i:s",strtotime($row->change_log_time_out)): NULL;
			}elseif($row->flag_add_logs==1){
				$concat_time_in = ($row->time_in!=NULL)? date("Y-m-d H:i:s",strtotime($row->time_in)) : NULL;
				$concat_lunch_in =  ($row->lunch_in!=NULL)? date("Y-m-d H:i:s",strtotime($row->lunch_in)) : NULL;
				$concat_lunch_out =  ($row->lunch_out!=NULL)? date("Y-m-d H:i:s",strtotime($row->lunch_out)) : NULL;
				$concat_time_out =  ($row->time_out!=NULL)? date("Y-m-d H:i:s",strtotime($row->time_out)): NULL;
			}elseif($row->flag_add_logs==2){
				$concat_time_in = ($row->time_in!=NULL)? date("Y-m-d H:i:s",strtotime($row->time_in)) : NULL;
				$concat_lunch_in =  ($row->lunch_in!=NULL)? date("Y-m-d H:i:s",strtotime($row->lunch_in)) : NULL;
				$concat_lunch_out =  ($row->lunch_out!=NULL)? date("Y-m-d H:i:s",strtotime($row->lunch_out)) : NULL;
				$concat_time_out =  ($row->time_out!=NULL)? date("Y-m-d H:i:s",strtotime($row->time_out)): NULL;
			}
			
			
			if($status=="approved"){
				$fieldsx = array(
						"approve_by_hr" => "Yes",
						"approve_by_head" => "Yes"
				);					
				$w1 = array(
						"time_in_id" => $row->employee_time_in_id,
						"comp_id" => $company_id,
						"approve_by_hr" => "No",
						"approve_by_head" => "No",
						"approval_time_in_id" => $row->approval_time_in_id
				);
				$_CI->db->where($w1);
				
				$update = $_CI->db->update("approval_time_in",$fieldsx);
			
			
				$fields = array(
						"time_in_status" 	=> "approved",
						"corrected"			=> "Yes",
						"time_in"			=> $concat_time_in,
						"lunch_out"			=> $concat_lunch_out,
						"lunch_in"			=> $concat_lunch_in,
						"time_out"			=> $concat_time_out,
						"total_hours"		=> $row->change_log_total_hours,
						"total_hours_required" => $row->change_log_total_hours_required,
						"tardiness_min"	=> $row->change_log_tardiness_min,
						"undertime_min"		=> $row->change_log_undertime_min,
						"date"=> $row->date,
						"split_status" => null
				);
				$where = array(
						"employee_time_in_id"=>$row->employee_time_in_id,
						"comp_id"=> $company_id
				);
				$_CI->db->where($where);
				$_CI->db->update("employee_time_in",$fields);
				
				
				
				$arr = array(
						"employee_time_in_id"=>$row->employee_time_in_id,
						"time_in_status" => "pending"
				);
				$_CI->db->where($arr);
				$_CI->edb->join('approval_time_in AS ati','ati.split_time_in_id = sbi.schedule_blocks_time_in_id','LEFT');
				$q = $_CI->edb->get('schedule_blocks_time_in AS sbi');
				$r = $q->result();
				
				if($r){
					
					foreach($r as $split):
						
						if($split->flag_add_logs){
							$concat_time_in = ($split->change_log_time_in!=NULL)? date("Y-m-d H:i:s",strtotime($split->change_log_time_in)) : NULL;
							$concat_lunch_in =  ($split->change_log_lunch_in!=NULL)? date("Y-m-d H:i:s",strtotime($split->change_log_lunch_in)) : NULL;
							$concat_lunch_out =  ($split->change_log_lunch_out!=NULL)? date("Y-m-d H:i:s",strtotime($split->change_log_lunch_out)) : NULL;
							$concat_time_out =  ($split->change_log_time_out!=NULL)? date("Y-m-d H:i:s",strtotime($split->change_log_time_out)): NULL;
						}elseif($split->flag_add_logs==1){
							$concat_time_in = ($split->time_in!=NULL)? date("Y-m-d H:i:s",strtotime($split->time_in)) : NULL;
							$concat_lunch_in =  ($split->lunch_in!=NULL)? date("Y-m-d H:i:s",strtotime($split->lunch_in)) : NULL;
							$concat_lunch_out =  ($split->lunch_out!=NULL)? date("Y-m-d H:i:s",strtotime($split->lunch_out)) : NULL;
							$concat_time_out =  ($split->time_out!=NULL)? date("Y-m-d H:i:s",strtotime($split->time_out)): NULL;
						}elseif($split->flag_add_logs==2){
							$concat_time_in = ($split->time_in!=NULL)? date("Y-m-d H:i:s",strtotime($split->time_in)) : NULL;
							$concat_lunch_in =  ($split->lunch_in!=NULL)? date("Y-m-d H:i:s",strtotime($split->lunch_in)) : NULL;
							$concat_lunch_out =  ($split->lunch_out!=NULL)? date("Y-m-d H:i:s",strtotime($split->lunch_out)) : NULL;
							$concat_time_out =  ($split->time_out!=NULL)? date("Y-m-d H:i:s",strtotime($split->time_out)): NULL;
						}
						
						$fields = array(
								"time_in_status" 	=> "approved",
								"corrected"			=> "Yes",
								"time_in"			=> $concat_time_in,
								"lunch_out"			=> $concat_lunch_out,
								"lunch_in"			=> $concat_lunch_in,
								"time_out"			=> $concat_time_out,
								"total_hours"		=> $split->change_log_total_hours,
								"total_hours_required" => $split->change_log_total_hours_required,
								"tardiness_min"	=> $split->change_log_tardiness_min,
								"undertime_min"		=> $split->change_log_undertime_min,
								"date"=> date("Y-m-d",strtotime($concat_time_in))
						);
						$where = array(
								"schedule_blocks_time_in_id"=>$split->schedule_blocks_time_in_id,
						);
						$_CI->db->where($where);
						$_CI->db->update("schedule_blocks_time_in",$fields);
						
					endforeach;
				}
				
			}else if($status=="reject"){
				$fields = array(
						"time_in_status" 	=> "reject",
						"corrected"			=> "Yes",
						"split_status" => null
				);
				$where = array(
						"employee_time_in_id"=>$row->employee_time_in_id,
						"comp_id"=> $company_id
				);
				$_CI->db->where($where);
				$_CI->db->update("employee_time_in",$fields);
				
				$arr = array(
						"employee_time_in_id"=>$row->employee_time_in_id,
						"time_in_status" => "pending"
				);
				$_CI->db->where($arr);
				$_CI->edb->join('approval_time_in AS ati','ati.split_time_in_id = sbi.schedule_blocks_time_in_id','LEFT');
				$q = $_CI->edb->get('schedule_blocks_time_in AS sbi');
				$r = $q->result();
				
				if($r){
						
					foreach($r as $split):
				
					
						$where = array(
								"schedule_blocks_time_in_id"=>$split->schedule_blocks_time_in_id,
						);
						
						$fields2 = array(
								"time_in_status" 	=> "reject",
								"corrected"			=> "Yes"
						);
						$_CI->db->where($where);
						$_CI->db->update("schedule_blocks_time_in",$fields2);
				
					endforeach;
				}
			}
		}
	}
}

function check_user_workflow(){
	
	
	$company_info = whose_company();

	$if_enable = is_workflow_enabled($company_info->company_id);
		
	return $if_enable;
}

/**
 * check if the employees time in adjustment is under the approvers approval group
 * @param unknown $hours_appr_grp
 * @param unknown $level
 * @return boolean
 */
function check_assigned_hours_gold($hours_appr_grp, $level,$emp_id = 0,$cron = false){
	$_CI =& get_instance();
	

	$where = array(
			"emp_id" => $emp_id,
			"level" => $level,
			"approval_groups_via_groups_id" => $hours_appr_grp
	);

	$_CI->db->where($where);
	$query = $_CI->db->get("approval_groups");
	$row = $query->row();

	return ($row) ? true : false;
}
/**
 * check if employee leave is done
 * @param unknown $leave_appr_grp
 * @param unknown $level
 * @param string $emp
 */
function is_done_timein($leave_appr_grp, $level, $emp = NULL,$count =false){
	$_CI =& get_instance();
	
	$where = array(
			"emp_id" => ($emp != NULL) ? $emp : $_CI->session->userdata("emp_id"),
			"level <" => $level,
			"approval_groups_via_groups_id" => $leave_appr_grp
	);

	$_CI->db->where($where);
	$query = $_CI->db->get("approval_groups");
	$row = $query->row();

	return ($row) ? true : false;

}


/**
 * This is use for cronjob hr approval in advance settings
 */
function cron_job_change($timein_id){
	$_CI =& get_instance();
	
	$tq_update_field = array(
		'approve_datetime' => date('Y-m-d H:i:s'),
		'notify_datetime' => date('Y-m-d H:i:s')					
	);
	
	$_CI->db->where('timein_id',$timein_id);
	$_CI->db->update('cronjob_log',$tq_update_field);
	

}

function delete_approve_timein($timein_id){
	$_CI =& get_instance();

	$_CI->db->where('time_in_id',$timein_id);
	$query = $_CI->db->get("approval_time_in");
	$row = $query->row();

	if($row){

		$_CI->db->delete('approval_time_in', array('time_in_id' => $timein_id));
	}

}

function is_break_assumed($work_schedule_id){
	$_CI =& get_instance();
	
	$where = array(
			'work_schedule_id' => $work_schedule_id
	);
	$_CI->db->where($where);
	$query = $_CI->db->get("work_schedule");
	$row = $query->row();
	
	if($row){
		if($row->break_rules == "assumed"){
			return $row;
		}
	}
	
	return false;
}

function total_min_between($to,$from){
	$to = date('Y-m-d H:i',strtotime($to));
	$from = date('Y-m-d H:i',strtotime($from));
	$total      = strtotime($to) - strtotime($from);
	$hours      = floor($total / 60 / 60);
	$minutes    = floor(($total - ($hours * 60 * 60)) / 60);
	$ret 		= ($hours * 60) + $minutes;
	return ($ret < 0) ? '0' : $ret;
}

function get_workschedule_in_regular_sched($work_schedule_id,$date,$comp_id){
	$_CI =& get_instance();
	$day1 = date('l',strtotime($date));
	$where = array(
			'work_schedule_id' => $work_schedule_id,
			'days_of_work' => $day1,
			'company_id' => $comp_id,
	);
	$_CI->db->where($where);
	$query = $_CI->db->get("regular_schedule");
	$row = $query->row();
	
	return ($row) ? $row : false;
	
	return false;
}

/** Fritz - Start **/


/**
 * message board notification for pending leave approval
 * @param unknown $company_id
 * @param unknown $emp_id
 * @param unknown $psa_id
 * @param unknown $domain
 */
function notify_approver_leave_pending_emp($company_id, $emp_id, $psa_id, $domain){
	$_CI =& get_instance();
	clear_old_notify($emp_id, "is_notified_leave_approval");
	if(!check_notify_approval($emp_id, "is_notified_leave_approval") &&  count_all_todo_pending_leave($emp_id,$company_id) > 0){
		// message board notification
		$message_board_data = array(
				"emp_id" => $emp_id,
				"psa_id" => $psa_id,
				"company_id" => $company_id,
				"message" => "You have employee(s) which has pending <a href='/{$domain}/employee/emp_todo_leave/lists' target='_blank'><strong>leave approval</strong></a>.",
				"via" => "system",
				"module" => "todo_leave",
				"date" => date("Y-m-d H:i:s"),
				"type" => "warning",
				"link" => "is_notified_leave_approval"
						);
		$insert = $_CI->db->insert("message_board", $message_board_data);
	}
}

/**
 * message board notification for pending overtime approval
 * @param unknown $company_id
 * @param unknown $emp_id
 * @param unknown $psa_id
 * @param unknown $domain
 */
function notify_approver_overtime_pending_emp($company_id, $emp_id, $psa_id, $domain){
	$_CI =& get_instance();
	clear_old_notify($emp_id, "is_notified_overtime_approval");
	if(!check_notify_approval($emp_id, "is_notified_overtime_approval") && count_all_todo_pending_overtime($emp_id,$company_id) > 0){
		// message board notification
		$message_board_data = array(
				"emp_id" => $emp_id,
				"psa_id" => $psa_id,
				"company_id" => $company_id,
				"message" => "You have employee(s) which has pending <a href='/{$domain}/employee/emp_todo_overtime/lists' target='_blank'><strong>overtime approval</strong></a>.",
				"via" => "system",
				"date" => date("Y-m-d H:i:s"),
				"type" => "warning",
				"link" => "is_notified_overtime_approval"
		);
		$insert = $_CI->db->insert("message_board", $message_board_data);
	}
}


/**
 * message board notification for pending timesheets approval
 * @param unknown $company_id
 * @param unknown $emp_id
 * @param unknown $psa_id
 * @param unknown $domain
 */
function notify_approver_timesheet_pending_emp($company_id, $emp_id, $psa_id, $domain){
	$_CI =& get_instance();
	clear_old_notify($emp_id, "is_notified_timesheet_approval");
	$count_timesheet = count_all_todo_pending_timein($emp_id,$company_id) + count_all_todo_pending_split_timein($emp_id,$company_id);
	if(!check_notify_approval($emp_id, "is_notified_timesheet_approval") && $count_timesheet > 0){
		// message board notification
		$message_board_data = array(
				"emp_id" => $emp_id,
				"psa_id" => $psa_id,
				"company_id" => $company_id,
				"message" => "You have employee(s) which has pending <a href='/{$domain}/employee/emp_todo_timein/lists' target='_blank'><strong>timesheets approval</strong></a>.",
				"via" => "system",
				"date" => date("Y-m-d H:i:s"),
				"type" => "warning",
				"module" => "todo_timesheet",
				"link" => "is_notified_timesheet_approval"
		);
		$insert = $_CI->db->insert("message_board", $message_board_data);
	}
}


/**
 * message board notification for pending shifts approval
 * @param unknown $company_id
 * @param unknown $emp_id
 * @param unknown $psa_id
 * @param unknown $domain
 */
function notify_approver_shift_pending_emp($company_id, $emp_id, $psa_id, $domain){
	$_CI =& get_instance();
	clear_old_notify($emp_id, "is_notified_shift_approval");
	if(!check_notify_approval($emp_id, "is_notified_shift_approval") && count_all_todo_pending_shifts($emp_id,$company_id) > 0){
		// message board notification
		$message_board_data = array(
				"emp_id" => $emp_id,
				"psa_id" => $psa_id,
				"company_id" => $company_id,
				"message" => "You have employee(s) which has pending <a href='/{$domain}/employee/emp_todo_shift_request/lists' target='_blank'><strong>shifts request approval</strong></a>.",
				"via" => "system",
				"module" => "todo_shifts",
				"date" => date("Y-m-d H:i:s"),
				"type" => "warning",
				"link" => "is_notified_shift_approval"
		);
		$insert = $_CI->db->insert("message_board", $message_board_data);
	}
}

/**
 * Get the schedule of time every day
 * @param unknown $date
 * @param unknown $emp_id
 * @param unknown $comp_id
 * @param unknown $work_schedule_id
 * @param string $time_in
 * @param string $employee_time_in_id
 * @return StdClass|string[][]|unknown[][]|boolean
 */
function workschedule_info($date,$emp_id,$comp_id,$work_schedule_id,$time_in="",$time_in_log = ""){
	$_CI =& get_instance();
	
	$day = date('l',strtotime($date));
	$info = array();
	$w_uwd = array(
			//"payroll_group_id"=>$payroll_group,
			"work_schedule_id"=>$work_schedule_id,
			"company_id"=>$comp_id,
			"days_of_work" => $day,
			"status" => 'Active'
	);
	$_CI->edb->where($w_uwd);
	$q_uwd = $_CI->edb->get("regular_schedule");
	$r_uwd = $q_uwd->row();
	
	if($q_uwd->num_rows() > 0){
	
		$start_time = date('Y-m-d H:i:s',strtotime($date." ".$r_uwd->work_start_time));
		#use this to check within grace period
		$start_time_ex = date('Y-m-d H:i:s',strtotime($date." ".$r_uwd->work_start_time));
		$end_time = date('Y-m-d H:i:s',strtotime($date." ".$r_uwd->work_end_time));
		
		$latest = $r_uwd->latest_time_in_allowed;
		$er = true;
		if($latest){
			$start = date('H:i:s',strtotime($r_uwd->work_start_time." +{$latest} minutes"));
			$start_time = date('Y-m-d H:i:s',strtotime($date." ".$start));
			
		
			#login withing grace period
			if($time_in_log>= $start_time_ex && $time_in_log <= $start_time){
				$work = round(($r_uwd->total_work_hours * 60) + $r_uwd->break_in_min);
				$end_time = date('Y-m-d H:i:s',strtotime($time_in_log." +{$work} minutes"));
				$er = false;
			}
		}
		
		#night shift
		if(date("A",strtotime($r_uwd->work_start_time)) == "PM" && date("A",strtotime($r_uwd->work_end_time)) == "AM" && $er){	
			$end_time = date('Y-m-d H:i:s',strtotime($end_time." +1 day"));
		}
		
				
		$info = (object)array(
			"start_time" => $start_time,
			"end_time" => $end_time,
			"break" => $r_uwd->break_in_min
		);
		
		return $info;
	}else{
		
		# FLEXIBLE HOURS

		$w_fh = array(
				//"payroll_group_id"=>$payroll_group,
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id
		);
		$_CI->db->where($w_fh);
		$q_fh = $_CI->edb->get("flexible_hours");
		$r_fh = $q_fh->row();
		if($q_fh->num_rows() > 0){
			$number_of_breaks_per_day = $r_fh->duration_of_lunch_break_per_day;
			$schedule="flex";

			$latest = $r_fh->latest_time_in_allowed;
			
			if($latest){
				$start_time = date('Y-m-d H:i:s',strtotime($date." ".$latest));
				$end_num = $r_fh->total_hours_for_the_day * 60;
				$end_time = date('Y-m-d H:i:s',strtotime($start_time." +{$end_num} minutes"));
				
				$info = (object)array(
						"start_time" => $start_time,
						"end_time" => $end_time,
						"break" => $r_fh->duration_of_lunch_break_per_day
				);
									
			}else{
												
				$end_num = $r_uwd->total_hours_for_the_day * 60;
				$end_time = date('Y-m-d H:i:s',strtotime($time_in." +{$end_num} minutes"));
				
				$info = (object)array(
						"start_time" => $start_time,
						"end_time" => $end_time,
						"break" => $r_fh->duration_of_lunch_break_per_day
				);
			}
			
			
			
			return $info;
			
		}else{
			#split info
			

			$w_date = array(
					"es.valid_from <="		=>  $date,
					"es.until >="			=>	$date
			);
			$_CI->db->where($w_date);
				
			$w_ws = array(
					"em.work_schedule_id"=>$work_schedule_id,
					"em.company_id"=>$comp_id,
					"em.emp_id" => $emp_id
			);
			$_CI->db->where($w_ws);
			$_CI->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
			$q_ws =	$_CI->edb->get("employee_sched_block AS em");
			$r_ws = $q_ws->result();
			
			$_CI->load->model('employee_login_model/emp_login_model','emp');
			if($q_ws->num_rows() > 0){
				$arr = array();
				foreach ($r_ws as $row){
					$rowx = $_CI->emp->get_blocks_list($row->schedule_blocks_id);
					$start_time = date('Y-m-d H:i:s',strtotime($date." ".$rowx->start_time));
					$end_time = date('Y-m-d H:i:s', strtotime($date." ".$rowx->end_time));
					$break = $rowx->break_in_min;
					$arr[] = array(
						'start_time' => $start_time,
						'end_time' => $end_time,
						'break' => $break
					);
				}
				
				$info = $arr;
				
				return $info;
			}
			
		}
	}
	return false;
}


function leave_change_time($start_time ,$end_time,$emp_id, $comp_id){
	$_CI =& get_instance();
	$_CI->load->model('employee_login_model/emp_login_model','emp');
	
	$vx = check_date_schedule($start_time ,$emp_id, $comp_id);
	$date = date('Y-m-d',strtotime($start_time));
	
	if($vx){
		$date = $vx['currentdate'];
	}
	
	
	$w = array(
			"eti.date" => $date,
			"eti.emp_id" => $emp_id,
			"eti.status" => "Active",
			"eti.comp_id" => $comp_id
	);
	
	$_CI->edb->where($w);
	$q = $_CI->edb->get("employee_time_in AS eti",1,0);
	$r = $q->row();
	
	$work_schedule_id = $_CI->emp->emp_work_schedule2($emp_id,$comp_id,$date);
	
	if($r){
		$day = date('l',strtotime($date));
		$w_uwd = array(
				//"payroll_group_id"=>$payroll_group,
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id,
				"days_of_work" => $day,
				"status" => 'Active'
		);
		$_CI->db->where($w_uwd);
			
		$q_uwd = $_CI->db->get("regular_schedule");
		$r_uwd = $q_uwd->row();
		
		if($r_uwd){
			
			$regular = workschedule_info($date,$emp_id,$comp_id,$work_schedule_id,$start_time,$r->time_in);
		
			if($regular){
			
				$tard = $r->tardiness_min;
				if($tard){
					
					#lunch tardiness
					if($end_time >= $r->lunch_out && $end_time <= $r->lunch_in){		
						$st = date('Y-m-d H:i:s',strtotime($r->lunch_out. " +{$regular->break} minutes"));
						
						if($st > $start_time){
							$start_time = $st;
						}
						
						$tar = $_CI->emp->total_hours_worked($end_time,$start_time);
						$overbreak = $r->overbreak - $tar;
						$tardinnes  = $r->tardiness_min - $tar; 
						if($tardinnes < 0){
							$tardinnes= 0;
						}
						$hours_work = $r->total_hours_required + ($tar / 60);
						$lunch_in = $start_time;
						
						$_CI->emp->update_employee_time_in($r->employee_time_in_id,$overbreak,$tardinnes,$hours_work,$lunch_in);
					}
					
					#morning tardiness
					if($end_time >= $regular->start_time && $end_time <= $r->time_in){
						
						$st = $regular->start_time;
						if($st > $start_time){
							$start_time = $st;
						}
						$tar = $_CI->emp->total_hours_worked($end_time,$start_time);
						$tardinnes  = $r->tardiness_min - $tar;
						if($tardinnes < 0){
							$tardinnes= 0;
						}
						$hours_work = $r->total_hours_required + ($tar / 60);
						$time_in = $start_time;			
						
						$_CI->emp->update_employee_time_in($r->employee_time_in_id,0,$tardinnes,$hours_work,$time_in,true);
					}
					
					
				}
				
				$under = $r->undertime_min;
				//echo $under;
				if($under){
						#undertime
						
						if($start_time >= $r->time_out && $start_time <= $regular->end_time){
							
							$end = $regular->end_time;
							
							if($end_time < $end){
								$end = $end_time;
							}
							
							$underx = $_CI->emp->total_hours_worked($end,$start_time);
							$undertime = $under - $underx;
							
							if($undertime < 0){
								$undertime= 0;
							}
							$hours_work = $r->total_hours_required + ($underx / 60);
							
							
							$where_update = array(
									"eti.employee_time_in_id"=>$r->employee_time_in_id
							);
												
							$update_val = array(
									"undertime_min"=> $undertime,
									"total_hours_required" => $hours_work,
									"time_out" => $end_time
							);
							
							$_CI->db->where($where_update);
							$update = $_CI->db->update("employee_time_in AS eti",$update_val);
						}
				}
				
				$absent = $r->absent_min;
				if($absent){
					
					if($start_time >= $r->time_out && $start_time <= $regular->end_time){
				
						$underx = ($_CI->emp->total_hours_worked($end_time,$start_time)) - $regular->break ;
						
						$undertime = $absent - $underx;
							
						if($undertime < 0){
							$undertime= 0;
						}
						$hours_work = ($r->total_hours_required + ($underx / 60));
							
							
						$where_update = array(
								"eti.employee_time_in_id"=>$r->employee_time_in_id
						);
					
						$update_val = array(
								"undertime_min"=> $undertime,
								"total_hours_required" => $hours_work,
								"time_out" => $end_time,
								"absent_min"  => 0
						);
							
						$_CI->db->where($where_update);
						$update = $_CI->db->update("employee_time_in AS eti",$update_val);
					}
					
					
					if($end_time >= $regular->start_time && $end_time <= $r->time_in){
						$st = $regular->start_time;
						if($st > $start_time){
							$start_time = $st;
						}
						$tar = $_CI->emp->total_hours_worked($end_time,$start_time);
						$tardinnes  = $absent - ($tar + $regular->break);
						
						if($end_time == $r->time_in){
							$tar = $_CI->emp->total_hours_worked($end_time,$start_time);
							$tardinnes  = $absent - ($tar + $regular->break);
							$hours_work = $r->total_hours_required + (($tar / 60) - ($regular->break/60));
						}else{
							$tar = $_CI->emp->total_hours_worked($end_time,$start_time);
							
							$tardinnes  = $absent - $tar;
							
							$hours_work = $r->total_hours_required + ($tar / 60);
						}
						
						if($tardinnes < 0){
							$tardinnes= 0;
						}
					
						$time_in = $start_time;
					
						$where_update = array(
								"eti.employee_time_in_id"=>$r->employee_time_in_id
						);
						$update_val = array(
								"tardiness_min"=> $tardinnes,
								"total_hours_required" => $hours_work,
								"time_in" => $time_in,
								"absent_min"  => 0
						);
							
						$_CI->db->where($where_update);
						$update = $_CI->db->update("employee_time_in AS eti",$update_val);
					}
				}
			}
		}else{
			
			#flexible			
			$w_fh = array(
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id
			);
			$_CI->db->where($w_fh);
			$q_fh = $_CI->edb->get("flexible_hours");
			$r_fh = $q_fh->row();
			
			if($r_fh){
				
				$flex = workschedule_info($date,$emp_id,$comp_id,$work_schedule_id,$start_time);
			
				if($flex){
					$tard = $r->tardiness_min;
					if($tard){
							
						if($end_time >=$r->lunch_out && $end_time <= $r->lunch_in){
							$st = date('Y-m-d H:i:s',strtotime($r->lunch_out. " +{$flex->break} minutes"));
					
							if($st > $start_time){
								$start_time = $st;
							}
					
							$tar = $_CI->emp->total_hours_worked($end_time,$start_time);
							$overbreak = $r->overbreak - $tar;
							$tardinnes  = $r->tardiness_min - $tar;
							if($tardinnes < 0){
								$tardinnes= 0;
							}
							$hours_work = $r->total_hours_required + ($tar / 60);
							$lunch_in = $start_time;
					
							$_CI->emp->update_employee_time_in($r->employee_time_in_id,$overbreak,$tardinnes,$hours_work,$lunch_in);
						}
							
						if($end_time >=$flex->start_time && $end_time <= $r->time_in){
							$st = $flex->start_time;
							if($st > $start_time){
								$start_time = $st;
							}
							$tar = $_CI->emp->total_hours_worked($end_time,$start_time);
							$tardinnes  = $r->tardiness_min - $tar;
							if($tardinnes < 0){
								$tardinnes= 0;
							}
							$hours_work = $r->total_hours_required + ($tar / 60);
							$time_in = $start_time;
					
							$_CI->emp->update_employee_time_in($r->employee_time_in_id,0,$tardinnes,$hours_work,$time_in,true);
						}
					}
					
					$under = $r->undertime_min;
					//echo $under;
					if($under){
						#undertime
					
						if($start_time == $r->time_out){
								
							$underx = $_CI->emp->total_hours_worked($flex->end_time,$start_time);
							$undertime = $under - $underx;
								
							if($undertime < 0){
								$undertime= 0;
							}
							$hours_work = $r->total_hours_required + ($underx / 60);
								
								
							$where_update = array(
									"eti.employee_time_in_id"=>$r->employee_time_in_id
							);
					
							$update_val = array(
									"undertime_min"=> $undertime,
									"total_hours_required" => $hours_work,
									"time_out" => $end_time
							);
								
							$_CI->db->where($where_update);
							$update = $_CI->db->update("employee_time_in AS eti",$update_val);
						}
					}
					
					$absent = $r->absent_min;
					if($absent){
						
						#halfday afternoon
						if($start_time >= $r->time_out && $start_time <= $flex->end_time){
				
							
							//if($start_time == $r->time_out){
								$underx = ($_CI->emp->total_hours_worked($end_time,$start_time)) - $flex->break ;					
								$undertime = $absent - $underx;
							//}
							
							if($undertime < 0){
								$undertime= 0;
							}
							$hours_work = ($r->total_hours_required + ($underx / 60));
								
								
							$where_update = array(
									"eti.employee_time_in_id"=>$r->employee_time_in_id
							);
								
							$update_val = array(
									"undertime_min"=> $undertime,
									"total_hours_required" => $hours_work,
									"time_out" => $end_time,
									"absent_min"  => 0
							);
								
							$_CI->db->where($where_update);
							$update = $_CI->db->update("employee_time_in AS eti",$update_val);
						}
							
						#halfday morning
						if( $end_time >= $flex->start_time && $end_time <= $r->time_in){
							$st = $flex->start_time;
							if($st > $start_time){
								$start_time = $st;
							}
							
							if($end_time == $r->time_in){
								$tar = $_CI->emp->total_hours_worked($end_time,$start_time);							
								$tardinnes  = $absent - ($tar + $flex->break);
								$hours_work = $r->total_hours_required + (($tar / 60) - ($flex->break/60));
							}else{
								$tar = $_CI->emp->total_hours_worked($end_time,$start_time);								
								$tardinnes  = $absent - $tar;
								$hours_work = $r->total_hours_required + ($tar / 60);
							}
							
							if($tardinnes < 0){
								$tardinnes= 0;
							}
																											
							$time_in = $start_time;
								
							$where_update = array(
									"eti.employee_time_in_id"=>$r->employee_time_in_id
							);
							$update_val = array(
									"tardiness_min"=> $tardinnes,
									"total_hours_required" => $hours_work,
									"time_in" => $time_in,
									"absent_min"  => 0
							);
								
							$_CI->db->where($where_update);
							$update = $_CI->db->update("employee_time_in AS eti",$update_val);
						}
					}
				}
			}else{
				
				#start split
				$split = $_CI->emp->new_split_info_helper($emp_id,$comp_id,$work_schedule_id,$date,$start_time);
			
				if($split){
										
					$w = array(
							"eti.emp_id"			=> $emp_id,
							"eti.status" 			=> "Active",
							"eti.comp_id" 			=> $comp_id,
							"eti.date" 				=> $date
					);
					$_CI->edb->where($w);
					$q = $_CI->edb->get("employee_time_in AS eti",1,0);
					$r = $q->row();
					
					if($r){
						if($r->date == $date){
							$w = array(
									"eti.employee_time_in_id"=>$r->employee_time_in_id,
									"eti.status" => "Active"
							);
							$_CI->edb->where($w);
							$split_q = $_CI->edb->get("schedule_blocks_time_in AS eti");
							$query_split = $split_q->result();
								
							if($query_split){
								
								foreach($query_split as $rowx){
								if($split['schedule_blocks_id'] == $rowx->schedule_blocks_id){
									
									if($rowx->lunch_out =="" && $rowx->lunch_in==""){
										$rowx->lunch_out = false;
										$rowx->lunch_in = false;
									}
									
									$tard = $rowx->tardiness_min;
									
									if($tard){
										
										#lunch tardiness
										if($end_time >= $rowx->lunch_out && $end_time <= $rowx->lunch_in){
																						
											$st = date('Y-m-d H:i:s',strtotime($rowx->lunch_out. " +{$split['break_in_min']} minutes"));
									
											if($st > $start_time){
												$start_time = $st;
											}
									
											$tar = $_CI->emp->total_hours_worked($end_time,$start_time);
											//$overbreak = $rowx->overbreak - $tar;
											$tardinnes  =$rowx->tardiness_min - $tar;
											if($tardinnes < 0){
												$tardinnes= 0;
											}
											$hours_work = $rowx->total_hours_required + ($tar / 60);
											$lunch_in = $start_time;
									
											$_CI->emp->update_employee_time_in_split($rowx->schedule_blocks_time_in_id,0,$tardinnes,$hours_work,$lunch_in,false,$r->employee_time_in_id,$work_schedule_id,$r->time_out);
										}
											
									
									#morning tardiness
									if($end_time >= $split['start_time'] && $end_time <= $rowx->time_in){
										
										$st =$split['start_time'];
										if($st > $start_time){
											$start_time = $st;
										}
										$tar = $_CI->emp->total_hours_worked($end_time,$start_time);
										$tardinnes  = $rowx->tardiness_min - $tar;
										if($tardinnes < 0){
											$tardinnes= 0;
										}
										$hours_work = $rowx->total_hours_required + ($tar / 60);
										$time_in = $start_time;			
										
										$_CI->emp->update_employee_time_in_split($rowx->schedule_blocks_time_in_id,0,$tardinnes,$hours_work,$time_in,true,$r->employee_time_in_id,$work_schedule_id,$r->time_out);
									}
											
											
									}
									
									
									$under = $rowx->undertime_min;
									#undertime
									if($under){																	
										if($start_time >= $rowx->time_out && $start_time <= $split['end_time']){
												
											$end = $split['end_time'];
												
											if($end_time < $end){
												$end = $end_time;
											}
												
											$underx = $_CI->emp->total_hours_worked($end,$start_time);
											$undertime = $under - $underx;
												
											if($undertime < 0){
												$undertime= 0;
											}
											$hours_work = $rowx->total_hours_required + ($underx / 60);
												
												
											$where_update = array(
													"eti.schedule_blocks_time_in_id"=>$rowx->schedule_blocks_time_in_id
											);
									
											$update_val = array(
													"undertime_min"=> $undertime,
													"total_hours_required" => $hours_work,
													"time_out" => $end_time
											);
												
											$_CI->db->where($where_update);
											$update = $_CI->db->update("schedule_blocks_time_in AS eti",$update_val);
											
											 $_CI->emp->insert_into_employee_time_in($r->employee_time_in_id, $r->time_out,$work_schedule_id);
										}
									} #end undertime
								}
							} #end foreach
							}
						}
					}
				}
				
			}
		}
	}
	
	return false;
}

function check_date_schedule($start ,$emp_id, $comp_id){
		$_CI =& get_instance();
		$_CI->load->model('employee_login_model/emp_login_model','emp');
		$data = array();
	
		$currentdate = date('Y-m-d',strtotime($start." -1 days"));
		$currenttime = $start;
			
				
		$r_emp = $_CI->emp->emp_work_schedule2($emp_id, $comp_id,$currentdate);
		
		if($r_emp){
						
			$w = array(
			"eti.date" => $currentdate,
			"eti.emp_id" => $emp_id,
			"eti.status" => "Active",
			"eti.comp_id" => $comp_id
			);
			
			$_CI->edb->where($w);
			$q = $_CI->edb->get("employee_time_in AS eti",1,0);
			$r = $q->row();
			
			
			
			if($r){
			$day = date('l',strtotime($currentdate));
			$w_uwd = array(
					//"payroll_group_id"=>$payroll_group,
					"work_schedule_id"=>$r_emp,
					"company_id"=>$comp_id,
					"days_of_work" => $day,
					"status" => 'Active'
			);
			$_CI->db->where($w_uwd);
			
			$q_uwd = $_CI->db->get("regular_schedule");
			$r_uwd = $q_uwd->row();
			

			if($r_uwd){
				
				$start_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$r_uwd->work_start_time));
				$end_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$r_uwd->work_end_time));
				$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
			

				$sched = $_CI->emp->one_day_plus($currentdate,$r_emp, $comp_id,$emp_id); 
				
				
				if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM"  && $currenttime >= $mid_night){		
					
					$end_time =  date('Y-m-d H:i:s', strtotime($currentdate." ".$r_uwd->work_end_time ." +1 day"));
	
					if($sched){
						
						$esday = date('l',strtotime($currentdate.' +1 day'));
						$esdayx_starts = date('Y-m-d H:i:s',strtotime($esday. " ". $sched->work_start_time));
						
						if($sched->latest_time_in_allowed){
							$esdayx_starts = date('Y-m-d H:i:s',strtotime($esdayx_starts. "-{$sched->latest_time_in_allowed}  minute"));
						}
						
						$esdayx_start = date('Y-m-d H:is',strtotime($esdayx_starts."-120 minute"));
						
					}
					
					# filter the start time of tomorrow shedule
					# if no start time return false
					$add_other_day = false;
					if(isset($esdayx_start)){
						
						if($currenttime <= $esdayx_start ){
							$add_other_day = true;
						}
					}
					
					$isTodayDate = false;
					
					
					if($sched){
						//$today_date = date('Y-m-d H:i:s',strtotime($sched->work_start_time ." -2 hours"));
						$today_date = $esdayx_start;
						if($currenttime > $today_date){
							$isTodayDate = true;	
						}
					}
					
					
				
					if($r->time_in >= $mid_night){					
						$time_in = date('Y-m-d',strtotime($r->time_in));											
					
						if(($r->date == $currentdate && $time_in > $currentdate && !$isTodayDate)  || $add_other_day ){
							
							$data['currentdate'] = $currentdate;
							$data['work_schedule_id'] = $r_emp;
							$data['start_time'] = $start_time;
							$data['end_time'] = $end_time;
						}else{
							// new employee, no log in employee
						
							if($r){
								
								$data['currentdate'] = date('Y-m-d',strtotime($start));
								$data['work_schedule_id'] = $r_emp;
								$data['start_time'] = null;
							}else{
						
								$data['currentdate'] = $currentdate;
								$data['work_schedule_id'] = $r_emp;
								$data['start_time'] = null;
							}
						}
					}else{
						
						if($isTodayDate){
							
							return false;
						}else{
							
						
							$data['currentdate'] = $currentdate;
							$data['work_schedule_id'] = $r_emp;
							$data['start_time'] = $start_time;
							$data['end_time'] = $end_time;
						}
					}
				}
			
			} #end regular
			else{
					# FLEXIBLE HOURS
					$arrx5 = array(
					'duration_of_lunch_break_per_day',
					'latest_time_in_allowed',
					'total_hours_for_the_day'
							);
					$_CI->edb->select($arrx5);
					$w_fh = array(
							"work_schedule_id"=>$r_emp,
							"company_id"=>$comp_id
					);
					$_CI->edb->where($w_fh);
					$q_fh = $_CI->edb->get("flexible_hours");
					$r_fh = $q_fh->row();
				
					if($q_fh->num_rows() > 0){
							
						$number_of_breaks_per_day = $r_fh->duration_of_lunch_break_per_day;
						$total_hours = $r_fh->total_hours_for_the_day * 60;
						$latest_timein = date('Y-m-d H:i:s',strtotime($currentdate." ".$r_fh->latest_time_in_allowed));
						$end_time_check = date('H:i:s',strtotime($r_fh->latest_time_in_allowed. " +{$total_hours} minutes"));
						$end_time = date('Y-m-d H:i:s',strtotime($latest_timein. " +{$total_hours} minutes"));
						$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
				
							
						if($r_fh->latest_time_in_allowed > $end_time_check){
				
							$advance_date2 = date('Y-m-d',strtotime($currentdate. " +1 day"));
							$sched2 = $_CI->emp->one_day_plus_flex($advance_date2, $comp_id,$emp_id);
							$second_latest_timein = false;
							if($sched2){
								$second_latest_timein_old = $sched2->start_time;
								$second_latest_timein = date('Y-m-d H:i:s',strtotime($second_latest_timein_old." -180 minutes"));
							}
				
							if( $currenttime>= $mid_night && $currenttime<=$second_latest_timein){
								$data['currentdate'] = $currentdate;
								$data['work_schedule_id'] = $r_emp;
								$data['start_time'] = $latest_timein;
								$data['end_time'] = $end_time;
							}
						}
					}else{

						# SPLIT SCHEDULE SETTINGS
						
						//$this->get_starttime($schedule_blocks_id,date('Y-m-d'));
						$w_date = array(
						"es.valid_from <="		=>	$currentdate,
						"es.until >="			=>	$currentdate
						);
						$_CI->db->where($w_date);
							
						
						$w_ws = array(
								//"payroll_group_id"=>$payroll_group,
								"em.work_schedule_id"=>$r_emp,
								"em.company_id"=>$comp_id,
								"em.emp_id" => $check_emp_no->emp_id
						);
						$_CI->db->where($w_ws);
						$_CI->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
						$q_ws = $_CI->edb->get("employee_sched_block AS em");
						$r_ws = $q_ws->result();
							
						if($q_ws->num_rows() > 0){
								
							$first = reset($r_ws);
							$lastx = end($r_ws);
						
							$first_time = $_CI->emp->get_starttime($first->schedule_blocks_id,$currentdate,$first);
							$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
							$mid_night2 = date('Y-m-d',strtotime($mid_night));
							$last_timex = $_CI->emp->get_endtime($lastx->schedule_blocks_id,$currentdate,$lastx);
							$last_schedule_blocks_id = $lastx->schedule_blocks_id;
								
							if($first_time > $last_timex){
								$last_time = date('H:i:s',strtotime($last_timex));
								$last_time = date("Y-m-d H:i:s",strtotime($mid_night2. " ".$last_time));
						
							}else{
								$yest = $_CI->emp->yesterday_split_info($currenttime, $emp_id, $r_emp, $comp_id,true);
								$last = end($yest);
								$last_time = $last['end_time'];
								$last_schedule_blocks_id = $last['schedule_block_id'];
								$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00 +1 day"));
							}
								
							$wx = array(
									"sbti.employee_time_in_id" => $r->employee_time_in_id,
									"sbti.status" => "Active"
							);
								
							$_CI->edb->where($wx);
							$_CI->db->order_by("sbti.time_in","DESC");
							$qx = $_CI->edb->get("schedule_blocks_time_in AS sbti",1,0);
							$rx = $qx->row();
						
							$schedule_blocks_id =0;
							$time_out ="";
							$gdate = "";
							if($rx){
								$schedule_blocks_id = $rx->schedule_blocks_id;
								$time_out = $rx->time_out;
								$gdate = $rx->date;
							}
						
							$mid_time = date('H:i:s',strtotime($last_timex));
							$today= date('Y-m-d');
						
							$w_date = array(
									"es.valid_from <="		=>	$today,
									"es.until >="			=>	$today
							);
							$_CI->db->where($w_date);
						
								
							$w_ws = array(
									//"payroll_group_id"=>$payroll_group,
									"em.work_schedule_id"=>$r_emp,
									"em.company_id"=>$comp_id,
									"em.emp_id" => $check_emp_no->emp_id
							);
							$_CI->db->where($w_ws);
							$_CI->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
							$q_wsx = $_CI->edb->get("employee_sched_block AS em");
							$r_wsx = $q_wsx->result();
						
							if(($currenttime > $mid_night && $currenttime <= $last_time) || ($last_schedule_blocks_id == $schedule_blocks_id && $time_out == "") ){
						
						
						
								$last_date = true;
								if($r_wsx){
									$first_today = reset($r_wsx);
									$last_today = end($r_wsx);
										
									$start_last2 = $_CI->emp->get_starttime($first_today->schedule_blocks_id,$today,$first_today);
									$start_date_now = date('Y-m-d H:i:s',strtotime($start_last2." -120 minute"));
										
									# kwaon ang date kng ang currenttime ni greater than sa grace time
									# gamit ni cya  scenario nga wla ka timeout sa last block o wla cta ka log sa last block
									if($currenttime>= $start_date_now){
										$data['currentdate'] = date('Y-m-d');
										$last_date = false;
									}
								}
						
								if($last_date){
									$data['currentdate'] = $currentdate;
								}
								//eerror here
									
							}
						
						
							#one scedule block added everyday
							/*if($last_schedule_blocks_id == $schedule_blocks_id && !($currenttime > $mid_night && $currenttime <= $last_time)){
							 $data['currentdate'] = date('Y-m-d');
							 return $data;
							 }*/
								
							#last schedule block
							# user time in in a next day
						
							if($first->schedule_blocks_id == $lastx->schedule_blocks_id){
						
								$start_last = $_CI->emp->get_starttime($first->schedule_blocks_id,$currentdate,$first);
								$end_last = $_CI->emp->get_endtime($lastx->schedule_blocks_id,$currentdate,$lastx);
						
								if($r_wsx){
									$first_today = reset($r_wsx);
									$last_today = end($r_wsx);
						
									$start_last2 = $_CI->emp->get_starttime($first_today->schedule_blocks_id,$today,$first_today);
									$start_date_now = date('Y-m-d H:i:s',strtotime($start_last2." -120 minute"));
						
									# kwaon ang date kng ang currenttime ni greater than sa grace time
									# gamit ni cya  scenario nga wla ka timeout sa last block o wla cta ka log sa last block
									if($currenttime>= $start_date_now){
											
										return false;
									}
								}
								/*done*/
								$mid_night = date('Y-m-d H:i:s',strtotime($currentdate." 24:00:00"));
						
								#kng ang timein niya nilabang sa sunod adlaw
								#kng wla sd cya timeout sa iya previous log;
								# Scene 4 nightshift (see document)
								if($currenttime>=$mid_night && date('A',strtotime($start_last)) == "PM" && date('A',strtotime($end_last)) == "AM" ){
										
									$data['currentdate'] = $currentdate;
										
								}
							}
						
							#kng ang iya end time sa last block kai natunong sa midnight(00:00:00)
							//echo $mid_time." ".$last_timex;
						
							if( !($r_wsx) && $time_out==""){
									
								$data['currentdate'] = $currentdate;
						
							}
								
								
								
						} #end split						
					}
				}
		 	}
		}
		
		return $data;
	}
	
	function get_customer_accnt_no($psa_id){
		$_CI =& get_instance();
		$where = array(
				"payroll_system_account_id" =>$psa_id
		);
	
		$_CI->db->where($where);
		$query = $_CI->db->get("payroll_system_account");
	
		$row = $query->row();
	
		return ($row) ? $row->ashima_billing_customer_id : "00000000000000XX";
	}
	
	function get_user_type($type_id) {
		$_CI =& get_instance();
		$where = array(
				"user_type_id" =>$type_id
		);
		
		$_CI->db->where($where);
		$query = $_CI->db->get("user_type");
		
		$row = $query->row();
		
		return ($row) ? $row->user_type : "UNKNOWN";
	}
	
	function manager_count_employees_on_leave($company_id,$emp_id){
		$_CI =& get_instance();
		$where = array(
				"ela.company_id" => $company_id,
				"ela.leave_application_status" => "approve",
				"ela.status" => "Active",
				"DATE(ela.date_start) <=" => date("Y-m-d"),
				"DATE(ela.date_end) >=" => date("Y-m-d"),
				"edrt.parent_emp_id" => $emp_id
		);
		$_CI->db->where($where);
		$_CI->db->where("flag_parent IS NOT NULL");
		$_CI->db->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
		$query = $_CI->db->get("employee_leaves_application AS ela");
		$result = $query->result();
	
		return ($result) ? $query->num_rows() : 0;
	}
	
	/**
	 * generate default contribution calculation
	 * @param unknown $company_id
	 */
	function default_workforce_contribution($company_id){
		$CI =& get_instance();
		$where = array(
			"company_id" => $company_id,
			"status" => "Active"
		);
		$CI->db->where($where);
		$query = $CI->db->get("employee_payroll_information");
		$result = $query->result();
		if($result){
			foreach ($result as $rt){
				$flag_sss = "";
				$flag_ph = "";
				$flag_hdmf = "";
				$data = array(
				);
				if(trim($rt->flag_sss) == "" || trim($rt->flag_sss) == NULL || empty($rt->flag_sss) || strlen($rt->flag_sss) == 0){
					$flag_sss = "table";
					
					$data["flag_sss"] = $flag_sss;
				}
				if(trim($rt->flag_philhealth) == "" || trim($rt->flag_philhealth) == NULL || empty($rt->flag_philhealth) || strlen($rt->flag_philhealth) == 0){
					$flag_ph = "table";
					$data["flag_philhealth"] = $flag_ph;
				}
				if(trim($rt->flag_hdmf) == "" || trim($rt->flag_hdmf) == NULL || empty($rt->flag_hdmf) || strlen($rt->flag_hdmf) == 0){
					$flag_hdmf = "fixed";
					$data["flag_hdmf"] = $flag_hdmf;
				}
				if($data){
					$where2 = array(
							"company_id" => $company_id,
							"employee_payroll_information_id" => $rt->employee_payroll_information_id,
							"status" => "Active"
					);
					$CI->db->where($where2);
					$update = $CI->db->update("employee_payroll_information", $data,$where2);
				}
			}
		}
	}

/** Fritz - end **/
