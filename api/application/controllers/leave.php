<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Leave extends CI_Controller{
	var $verify;
		
	public function __construct(){
	   parent::__construct();	
	   $this->load->model('konsumglobal_jmodel','jmodel');
	   $this->load->model('employee_model','employee');
	   $this->load->model('approval_model','approval');
	   $this->load->model('approval_group_model','agm');
	   $this->load->model('todo_leave_model','todo_leave');
	   $this->load->model('approve_leave_model','leave');
	   $this->load->model('employee_work_schedule_model','ews');
	   $this->load->model('payroll_run_model','prm');
	   $this->load->model('employee_mobile_model','mobile');
	   $this->load->model('emp_login_model','elm');
	   $this->load->model("import_timesheets_model","import");
       $this->load->model('login_model','emp_login');
       $this->load->model('employee_v2_model','employee_v2');
	   
	  // $this->company_info = whose_company();
	  
	   $this->emp_id = $this->session->userdata('emp_id');
	   $this->company_id =$this->employee->check_company_id($this->emp_id);
	   $this->account_id = $this->session->userdata('account_id');
	}
	
	public function index(){
		$page = $this->input->post('page');
		$limit = $this->input->post('limit');
		$status = $this->input->post('lv_status');
		
		$this->per_page = 10;
		
		$get_employee_leaves_list = $this->mobile->get_employee_leaves_list($this->emp_id,$this->company_id,false,$status,(($page-1) * $this->per_page),$limit);
        $all = $this->mobile->get_employee_leaves_list($this->emp_id,$this->company_id,true,$status);
        $total = ceil($all / 10);
		if($get_employee_leaves_list){
			echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "total" => $total,"list" => $get_employee_leaves_list));
			return false;
		}else{
			echo json_encode(array("result" => "0"));
			return false;
		}

	}
	
	public function leave_correction(){
	    $page = $this->input->post('page');
	    $limit = $this->input->post('limit');
	    
	    $this->per_page = 10;
	    
	    $get_employee_leaves_list = $this->mobile->get_employee_leaves_list_correction($this->emp_id,$this->company_id,false,(($page-1) * $this->per_page),$limit);
	    $total = ceil($this->mobile->get_employee_leaves_list_correction($this->emp_id,$this->company_id,true) / 10);
	    
	    if($get_employee_leaves_list){
	        echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "total" => $total,"list" => $get_employee_leaves_list));
	        return false;
	    }else{
	        echo json_encode(array("result" => "0"));
	        return false;
	    }
	    
	}
	
	public function server_date() {
		echo json_encode(array("server_date"=> date('Y-m-d')));
	}
	
	public function get_leave_approvers(){
		$leave_approver = $this->agm->get_approver_name_leave($this->emp_id,$this->company_id);
		
			echo json_encode($leave_approver);
	
	}
	public function get_leave_credits()
	{
		$leave_type = $this->employee->leave_type($this->company_id,$this->emp_id);
		$array =  array();
		if($leave_type){
			foreach($leave_type as $lt){
				$temp = array(
						'type'=> $lt->leave_type,
						'remaining_c' => ($lt->remaining_leave_credits != "") ? $lt->remaining_leave_credits : $lt->leave_credits,
						'leave_credits' => ($lt->leave_credits != "") ? $lt->leave_credits : 0
				);
				array_push($array, $temp);
				//echo '<td>'.(($row_l->remaining_leave_credits != "") ? $row_l->remaining_leave_credits : $row_l->leave_credits) .'</td>';
				
			}
			echo json_encode($array);
		}else{
			return FALSE;
		}
	}	

	public function get_leave_docs(){
		$date = date("Y-m-d");
		$leave_type_id = $this->input->post('leave_type');
		$w = array(
				"lt.leave_type_id" => $leave_type_id,
				"el.company_id"=>$this->company_id,
				"el.emp_id"=>$this->emp_id,
				"el.as_of <= "=>$date,
				"el.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("leave_type lt","lt.leave_type_id = el.leave_type_id","left");
		$q = $this->db->get("employee_leaves AS el");
		$r = $q->result();
	
		//last_query();
	
		echo json_encode($r);
	}
	
	function leave_doughnut(){
		$leave_array = array();
		$leave_type_id = $this->input->post('leave_type');
		$leave_credits = $this->employee->leave_credits_for_doughnut($this->company_id,$this->emp_id,$leave_type_id);
		
		/*if($leave_credits){
			foreach ($leave_credits as $lc){
				$pending_leave = 0;
				$pending = $this->employee->pending_remaining_credits($this->company_id,$this->emp_id);
				if($pending){
					foreach ($pending as $p_app){
						if($p_app->leave_type_id == $lc->leave_type_id){
							///$total_leave_request = ($p_app->total_leave_requested) ? $p_app->total_leave_requested : 0;
							$pending_leave = $pending_leave + $p_app->total_leave_requested;
						}
					}
				}
				$indi = array(
						"leaves_id" => $lc->leaves_id,
						"emp_id" => $lc->emp_id,
						"leave_type_id" => $lc->leave_type_id,
						"a_leave_credits" => $lc->a_leave_credits,
						"remaining_leave_credits" => $lc->remaining_leave_credits,
						"leave_type" => $lc->leave_type,
						"pending_leaves" => $pending_leave,
						"a_leave_units" => $lc->a_leave_units
				);
				array_push($leave_array,$indi);
			}
		}*/
	
		echo json_encode($leave_credits);
		return false;
	
	}
	
	public function cancel_leave() {
		$employee_leave_application_id = $this->input->post('employee_leaves_application_id');
		$cancel_reason = $this->input->post('cancel_reason');
			
		$get_employee_leave_application = $this->employee->get_employee_leave_application($employee_leave_application_id,$this->emp_id,$this->company_id);
		$leave_info = $this->agm->leave_information($employee_leave_application_id);
			
		$employee_details = get_employee_details_by_empid($this->emp_id);
			
		#$this->form_validation->set_rules("employee_leaves_application_id", 'Employee Leaves ID', 'trim|required|xss_clean');
		#$this->form_validation->set_rules("cancel_reason", 'Cancel Reason', 'required|xss_clean');
			
		if ($cancel_reason && $employee_leave_application_id){
			if($get_employee_leave_application) {
				$get_credits_from_emp_leaves = $this->employee->get_credits_from_emp_leaves($get_employee_leave_application->leave_type_id,$this->emp_id,$this->company_id);
					
				$my_remaining_credits = 0;
				$my_previous_credits = 0;
					
				if($get_credits_from_emp_leaves){
					$my_remaining_credits = $get_credits_from_emp_leaves->remaining_leave_credits;
					$my_previous_credits = $get_credits_from_emp_leaves->previous_leave_credits;
				}
					
				$my_credits_now = $get_employee_leave_application->total_leave_requested;
				$new_remaining_credits = $my_remaining_credits + $my_credits_now;
					
				/** update employee leaves remaining credits back to previous one if status is approve **/
				if($get_employee_leave_application->leave_application_status == "approve") {
					$data1 = array(
							"remaining_leave_credits" => round($new_remaining_credits,3),
							"previous_leave_credits" => $my_remaining_credits
					);
	
					$where1 = array(
							"emp_id" => $this->emp_id,
							"leave_type_id" => $get_employee_leave_application->leave_type_id
					);
						
					eupdate('employee_leaves', $data1, $where1);
	
					update_time_in_on_leave($get_employee_leave_application->date_start,$get_employee_leave_application->date_end,$this->emp_id,$this->company_id);
				}
					
					
				/** update employee application to cancel status **/
					
				// cancel the parent
				$data = array(
						"date_cancel" => date('Y-m-d H:i:s'),
						"leave_application_status" => "cancelled",
						"cancel_reason" => $cancel_reason
				);
	
				$where = array(
						"employee_leaves_application_id" => $employee_leave_application_id,
						"status" => "Active"
				);
					
				eupdate('employee_leaves_application', $data, $where);
					
				// cancel the child if dli baog.. :D
				$data1 = array(
						"date_cancel" => date('Y-m-d H:i:s'),
						"leave_application_status" => "cancelled",
						"cancel_reason" => $cancel_reason
				);
					
				$where1 = array(
						"leaves_id" => $employee_leave_application_id,
						"status" => "Active"
				);
	
				eupdate('employee_leaves_application', $data1, $where1);
					
				/** send notification email for your approvers **/
					
				$your_approvers = $this->agm->get_approver_name_leave($this->emp_id,$this->company_id);
				if($your_approvers) {
					foreach ($your_approvers as $approvers){
						$appovers_id = ($approvers->emp_id) ? $approvers->emp_id : "-99{$this->company_id}";
						$get_approval_group_via_groups_owner = $this->agm->get_approval_group_via_groups_owner($approvers->approval_process_id, $approvers->company_id, $approvers->approval_groups_via_groups_id,$appovers_id);
	
						if($get_approval_group_via_groups_owner->emp_id == "-99{$this->company_id}"){
							$owner_approver = get_approver_owner_info($this->company_id);
							$appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
							$appr_account_id = $owner_approver->account_id;
							$appr_email = $owner_approver->email;
							$appr_id = "-99{$this->company_id}";
						} else {
							$appr_name = ucwords($approvers->first_name." ".$approvers->last_name);
							$appr_account_id = $approvers->account_id;
							$appr_email = $approvers->email;
							$appr_id = $approvers->emp_id;
						}
						$this->send_leave_cancellation_notif($employee_leave_application_id, $this->company_id, $leave_info->emp_id, $appr_email, $appr_name, "", "Approver",$appr_id);
					}
				}
					
				/** send notification email for yourself as employee who cancelled this leave application  **/
				$your_email = $employee_details->email;
				$your_name = ucwords($employee_details->first_name." ".$employee_details->last_name);
				$your_emp_id = $this->emp_id;
				$this->send_leave_cancellation_notif($employee_leave_application_id, $this->company_id, $this->emp_id, $your_email, $your_name, "", "Approver",$your_emp_id);
			}
	
	
			$result = array(
					'result' => 1,
					'error' => false,
			);
	
			echo json_encode($result);
			return false;
		} else {
			$result = array(
					'result' => 0,
					'error' => true,
					"employee_leaves_application_id" => "No Employee Leaves Application ID",
					"cancel_reason" => "Reason is required",
					"leave_type_id" => "No leave type ID",
					"total_leave_requested" => "Total Leave Requested is empty"
			);
	
			echo json_encode($result);
			return false;
		}
		
	}
	
	public function can_cancel_or_not() {
		$employee_leaves_application_id = $this->input->post('employee_leaves_application_id');
		$date_end = $this->input->post('date_end');
		$leave_application_status = $this->input->post('leave_application_status');
		$cancel = $this->employee->leaves_can_be_cancel($this->emp_id,$employee_leaves_application_id);
		
		$date_now = date('Y-m-d h:i s');
		$leave_end_date = date('Y-m-d h:i s', strtotime($date_end));
		$cancel_leave_by_admin = $this->employee->cancel_leave_by_admin($this->emp_id,$employee_leaves_application_id);
		
		if($employee_leaves_application_id && $date_end && $leave_application_status) {
			if($cancel) {
				if($leave_application_status == "cancelled" || $leave_application_status == "reject") {
					$can_cancel = false;
					$cancel_by_admin = false;
				} else {
					if($leave_end_date > $date_now) {
						$can_cancel = true;
						$cancel_by_admin = false;
					} else {
						if($cancel_leave_by_admin) {
							$can_cancel = false;
							$cancel_by_admin = true;
						} elseif ($leave_application_status == "pending") {
							$can_cancel = true;
							$cancel_by_admin = false;
						} else {
							$can_cancel = false;
							$cancel_by_admin = false;
						}
					}
				}
			} else {
				$can_cancel = false;
				$cancel_by_admin = false;
			}
			
			echo json_encode(array("can_cancel" => $can_cancel, "cancel_by_admin" => $cancel_by_admin));
		} else {
			return false;
		}
		
	}
	
	public function can_apply_leave_or_not() {
		$leave_type_id = $this->input->post('leave_type_id');
		$leave_credits = $this->input->post('leave_credits');
		$remaining_leave_credits = $this->input->post('remaining_leave_credits');
		
		$paid_leave 				= $this->leave->get_leave_restriction($leave_type_id,'paid_leave');
		$apply_limit_rest			= $this->leave->get_leave_restriction($leave_type_id,'allow_to_apply_leaves_beyond_limit');
		$eff_date 					= $this->leave->get_leave_eff_date($leave_type_id,$this->company_id,$this->emp_id,"effective_date");
		$effective_start_date_by	= $this->leave->get_leave_restriction($leave_type_id,'effective_start_date_by');
		$effective_start_date 		= $this->leave->get_leave_restriction($leave_type_id,'effective_start_date');
		#$entitlements 				= employee_entitlements($this->emp_id,$this->company_id);
		#$entitlements_flag = false;
		#if($entitlements){
			#if($entitlements->entitled_to_leaves == 'yes'){
		if($leave_type_id && $leave_credits && $remaining_leave_credits) {
			if($effective_start_date_by != null && $effective_start_date != null) {
				if($eff_date > date('Y-m-d', strtotime(date('Y-m-d')))) {
					$apply_disble = true;
				} else {
					if($paid_leave == 'no' && $leave_credits == 0) {
						$apply_disble = false;
					} elseif ($remaining_leave_credits <= 0 && $apply_limit_rest == 'yes') {
						$apply_disble = false;
					} elseif ($remaining_leave_credits <= 0 && $apply_limit_rest == 'no') {
						$apply_disble = true;
					} else {
						$apply_disble = false;
					}
				}
			} else {
				if($paid_leave == 'no' && $leave_credits == 0) {
					$apply_disble = false;
				} elseif ($remaining_leave_credits <= 0 && $apply_limit_rest == 'yes') {
					$apply_disble = false;
				} elseif ($remaining_leave_credits < 0 && $apply_limit_rest == 'no') {
					$apply_disble = true;
				} else {
					$apply_disble = false;
				}
			}
		} else {
			$apply_disble = false;
		}
		
			#} else {
			#	$entitlements_flag = false;
			#}
		#} else {
		#	$entitlements_flag = false;
		#}
		
		echo json_encode(array("apply_disble" => $apply_disble));
		return false;
    }

    public function get_emp_leave_det($leave_type_id) {
        $this->db->where(array(
            "leave_type_id" => $leave_type_id,
            "status"=>"Active"
        ));
        $q = $this->db->get("employee_leaves");
        $r = $q->row();

        return ($r) ? $r : false;
    }

    public function check_eff_date($leave_type_id) {
		// $leave_type_id = $this->input->post('leave_type_id');
		// $leave_credits = $this->input->post('leave_credits');
        // $remaining_leave_credits = $this->input->post('remaining_leave_credits');
        
        $lv_d = $this->get_emp_leave_det($leave_type_id);
        if (!$lv_d) {
            return true;
        }

        $leave_credits = $lv_d->leave_credits;
        $remaining_leave_credits = $lv_d->remaining_leave_credits;
		
		$paid_leave 				= $this->leave->get_leave_restriction($leave_type_id,'paid_leave');
		$apply_limit_rest			= $this->leave->get_leave_restriction($leave_type_id,'allow_to_apply_leaves_beyond_limit');
		$eff_date 					= $this->leave->get_leave_eff_date($leave_type_id,$this->company_id,$this->emp_id,"effective_date");
		$effective_start_date_by	= $this->leave->get_leave_restriction($leave_type_id,'effective_start_date_by');
		$effective_start_date 		= $this->leave->get_leave_restriction($leave_type_id,'effective_start_date');
		
		if($leave_type_id && $leave_credits && $remaining_leave_credits) {
			if($effective_start_date_by != null && $effective_start_date != null) {
				if($eff_date > date('Y-m-d', strtotime(date('Y-m-d')))) {
					$apply_disble = true;
				} else {
					if($paid_leave == 'no' && $leave_credits == 0) {
						$apply_disble = false;
					} elseif ($remaining_leave_credits <= 0 && $apply_limit_rest == 'yes') {
						$apply_disble = false;
					} elseif ($remaining_leave_credits <= 0 && $apply_limit_rest == 'no') {
						$apply_disble = true;
					} else {
						$apply_disble = false;
					}
				}
			} else {
				if($paid_leave == 'no' && $leave_credits == 0) {
					$apply_disble = false;
				} elseif ($remaining_leave_credits <= 0 && $apply_limit_rest == 'yes') {
					$apply_disble = false;
				} elseif ($remaining_leave_credits < 0 && $apply_limit_rest == 'no') {
					$apply_disble = true;
				} else {
					$apply_disble = false;
				}
			}
		} else {
			$apply_disble = false;
		}
		
		return $apply_disble;
    }
    
    function check_emp_leave_entilement($emp_id,$company_id){
		
		if($emp_id){
			$where = array(
				'epi.status'=>'Active',
				'epi.status'=>'Active',
				'epi.company_id'=>$company_id,
				'epi.emp_id'=>$emp_id
            );
            $this->db->select('entitled_to_leaves');
			$this->db->where($where);
			// $this->db->join('employee_payroll_information AS epi','epi.emp_id= e.emp_id','INNER');
			$q = $this->db->get('employee_payroll_information AS epi');
			$r = $q->row();
			return $r;
		}else{
			return false;
		}
	}
	
	public function get_leave_type(){
        $date = date("Y-m-d");
        $sel = array(
            "leaves_id",
            "emp_id",
            "el.leave_type_id",
            "leave_type",
            "leave_units",
            "leave_credits",
            "remaining_leave_credits",
            "lt.required_documents"
        );
		$w = array(
				"el.company_id"=>$this->company_id,
				"el.emp_id"=>$this->emp_id,
				"el.as_of <= "=>$date,
				"el.status"=>"Active"
        );
        $this->db->select($sel);
        $this->db->where($w);
        $this->db->where("(lt.flag_is_ml > 1 OR lt.flag_is_ml IS NULL)");
		$this->db->join("leave_type lt","lt.leave_type_id = el.leave_type_id","left");
		$q = $this->db->get("employee_leaves AS el");
		$r = $q->result();
		
        $entitlements = $this->check_emp_leave_entilement($this->emp_id,$this->company_id);
		if($entitlements){
			if($entitlements->entitled_to_leaves == 'yes'){
                echo json_encode($r);
                return false;
			} else {
				// echo json_encode(array("entitled_to_leaves" => false));
				return false;
			}
		} else {
			// echo json_encode(array("entitled_to_leaves" => false));
			return false;
		}
		
	}
	
	public function get_pending_and_approve_to_date() {
		$leave_type_id = $this->input->post('leave_type_id');
		
		$approval_pending = $this->mobile->get_pending_approval_leaves($leave_type_id,$this->emp_id);
		$ap_days = 0;
		
		if($approval_pending){
			$ap_days =  $approval_pending->total_request !='' ? $approval_pending->total_request : '0';
		}
		
		$approval_approve = $this->mobile->get_pending_approval_leaves($leave_type_id,$this->emp_id,"approve");
		 
		$approve_value = 0;
		 
		if($approval_approve){
			$approval_approve_days =  $approval_approve->total_request !='' ? $approval_approve->total_request : '0';
			$approve_value = $approval_approve_days;
		}
		
		echo json_encode(array("approval_pending" => $ap_days, "approve_value" => $approve_value));
		return false;
	}
	
	public function get_approvers_name_and_status() {
		$employee_leaves_application_id = $this->input->post('employee_leaves_application_id');
		$leave_application_status = $this->input->post('leave_application_status');
		
		$leave_info = $this->agm->leave_information($employee_leaves_application_id);
		$leave_approver = get_approvers_name_and_status($this->company_id, $this->emp_id, $employee_leaves_application_id, "leave"); // $this->agm->get_approver_name_leave($leave_info->emp_id,$leave_info->company_id);
		$workflow_approvers = workflow_approved_by_level($employee_leaves_application_id, 'leave');
		$x = count($workflow_approvers);
		$res = array();
		
		if($leave_approver) {
		    $auto_approve = false;
			foreach ($leave_approver as $la) {
			    if($la->emp_id == "-99{$this->company_id}"){
			        $owner_approver = get_approver_owner_info($this->company_id);
			        $appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
			    } else {
			        $appr_name = ucwords($la->first_name." ".$la->last_name);
			    }
			    
				if($leave_application_status == "reject") {				    
					$last_level = $this->leave->get_leave_last_level($leave_info->emp_id, $leave_info->company_id);
					if($workflow_approvers){
						if($x > $la->level) {
						    $name = $appr_name.' - (Approved)';
						} elseif ($x == $last_level) {
						    $name = $appr_name.' - (Rejected)';
						} elseif($x < $la->level) {
						    $name = $appr_name.' - (Rejected)';
						} else {
						    $name = $appr_name.' - (Rejected)';
						}
					} else {
					    $name = "";
					    $auto_approve = true;
					}
				} else {
					if($workflow_approvers) {
						if($leave_application_status == "cancelled") {
							$name = "";
							$auto_approve = true;
						} else {
							foreach ($workflow_approvers as $wa) {
								if($wa->workflow_level == $la->level) {
								    $name = $appr_name.' - (Approved)';
								} else if($leave_application_status == "pending") {
								    $name = $appr_name.' - ('.$leave_application_status.')';
								} else {
									$name = "";
								}
							}
						}
					} else {
						if($leave_application_status == "pending") {
						    $name = $appr_name.' - ('.$leave_application_status.')';
						} else {
							$name = "";
							$auto_approve = true;
						}
					}
				}
				
				if($auto_approve) {
				    if($leave_application_status == "approve") $name = "approved";
				    elseif($leave_application_status == "reject") $name = "rejected";
				    else $name = $leave_application_status;
				}
				
				$app = array(
						"name" => $name
				);
				
				array_push($res,(object)$app);
			}
			
			
		} else {
		    if($leave_application_status == "approve") $name = "approved";
		    elseif($leave_application_status == "reject") $name = "rejected";
		    else $name = $leave_application_status;
		    
		    $app = array(
		        "name" => $name
		    );
		    
		    array_push($res,(object)$app);
		}
		
		echo json_encode($res);
		return false;
	}
	
	// na buwag na ni sya function ky kani na function ky existing nmn ako nlng g.reuse
	public function get_shift_schedule_info() {
	    $work_schedule_id = $this->input->post('work_schedule_id');
	    $shift_date = $this->input->post('shift_date');
	    $shift_date = date('Y-m-d', strtotime($shift_date));
	    
	    $get_shift_via_payroll_grp = $this->employee->get_shift_via_payroll_grp($this->emp_id);
	    
	    if($work_schedule_id != null) {
	        $split = $this->elm->check_workday($work_schedule_id,$this->company_id);
	        $check_holiday = $this->employee->get_holiday_schedule($shift_date,$this->emp_id,$this->company_id);
	        
	        if($split){
	            if($split->work_type_name == "Workshift"){
	                #echo "Split Shift";
	                $shift_name = "Split Shift";
	            } elseif($work_schedule_id == -2) {
	                #echo "Excess Hours";
	                $shift_name = "Excess Hours";
	            } else {
	                $workday = date('l',strtotime($shift_date));
	                $restday = false;
	                
	                if($work_schedule_id == 0 || $work_schedule_id == ""){
	                    $wsi = $this->import->emp_work_schedule($this->emp_id,$this->company_info->company_id,$shift_date);
	                }else{
	                    $wsi = $work_schedule_id;
	                }
	                
	                $restday = $this->elm->check_rest_day($workday,$wsi,$this->company_id);
	                
	                if($work_schedule_id == -1 || $restday){
	                    #echo "Rest Day";
	                    $shift_name = "Rest Day";
	                }else{
	                    $work_schedule_custom = NULL;
	                    $shift_name = NULL;
	                    $abrv = null;
	                    $employee_shift_schedule = $this->employee->assigned_work_schedule($this->company_id,$this->emp_id,$shift_date);
	                    
	                    /* CHECK BACKGROUND COLOR */
	                    if($employee_shift_schedule) {
	                        $shift_name = $employee_shift_schedule->name;
	                        $work_schedule_custom = $employee_shift_schedule->category_id;
	                    }else{
	                        /* DEFAULT WORK SCHEDULE VIA PAYROLL GROUP */
	                        $payroll_group_id = ($get_shift_via_payroll_grp) ? $get_shift_via_payroll_grp->payroll_group_id : FALSE;
	                        if($payroll_group_id){
	                            $get_work_schedule_default = $this->employee->assigned_work_schedule_via_payroll_group($payroll_group_id,$this->company_id);
	                            
	                            if($get_work_schedule_default) {
	                                $shift_name = $get_work_schedule_default->name;
	                                $work_schedule_custom = $get_work_schedule_default->category_id;
	                            }
	                        }
	                    }
	                    
	                    $sub_work_name = "";
	                    if($work_schedule_custom != NULL) {
	                        $cwr = array(
	                            "work_schedule_id" => $work_schedule_custom,
	                            "comp_id" => $this->company_id
	                        );
	                        $cust_workname = get_table_info("work_schedule",$cwr);
	                        
	                        $sub_work_name = " - ".$shift_name;
	                        $shift_name = $cust_workname->name;
	                    }
	                    
	                    if($shift_name == "Regular Work Schedule") {
	                        $abrv = "RW".$sub_work_name;
	                    }
	                    if($shift_name == "Compressed Work Schedule") {
	                        $abrv = "CW".$sub_work_name;
	                    }
	                    if($shift_name == "Night Shift Schedule") {
	                        $abrv = "NS".$sub_work_name;
	                    }
	                    if($shift_name == "Flexi Time Schedule") {
	                        $abrv = "FT".$sub_work_name;
	                    }
	                    
	                    $date = $shift_date ;
	                    $current_monday = date("Y-m-d",strtotime($date." monday this week"));
	                    $time_name = "~";
	                    $shift="";
	                    $tree = false;
	                    for($c=0;$c<7;$c++){
	                        $date_calendar = date("M-d D",strtotime($current_monday." +{$c} day"));
	                        $date_counter = date("Y-m-d",strtotime($current_monday." +{$c} day"));
	                        $work_schedule_id = $this->employee->todo_work_schedule_id($this->company_id,$this->emp_id,$date_counter);
	                        
	                        if($work_schedule_id):
	                        if($date==$work_schedule_id->valid_from){
	                            $weekday = date('l',strtotime($date_calendar));
	                            
	                            if($work_schedule_id){
	                                $work_schedule_info = $this->employee->todo_work_schedule_info2($this->company_id,$work_schedule_id,$weekday,$shift_date,$this->emp_id);
	                                
	                            }else
	                                $work_schedule_info = $this->employee->todo_work_schedule_info2($this->company_id,$work_schedule_id->work_schedule_id,$weekday,$shift_date,$this->emp_id);
	                                
	                                if(!isset($work_schedule_info["work_schedule"])){
	                                    $tree= true;
	                                }else{
	                                    $time_name = time12hrs($work_schedule_info["work_schedule"]["start_time"]) . " - ". time12hrs($work_schedule_info["work_schedule"]["end_time"]) ;
	                                    $shift = $work_schedule_info['work_schedule']['shift_name'];
	                                }
	                                break;
	                        }
	                        endif;
	                    }
	                    
	                    if($check_holiday) {
	                        #echo $check_holiday->holiday_name.'<br>';
	                        #echo $check_holiday->hour_type_name;
	                        $shift_name = $check_holiday->holiday_name.' ('.$check_holiday->hour_type_name.')';
	                    } else {
	                        if($tree){
	                            foreach ($work_schedule_info as $row1):
	                            #echo time12hrs($row1['start_time']) . " - ". time12hrs($row1['end_time'])."<br>";
	                            #echo $row1['shift_name']."<br>";
	                            $shift_name = time12hrs($row1['start_time']) . " - ". time12hrs($row1['end_time'])."<br>".$row1['shift_name'];
	                            endforeach;
	                        }else{
	                            
	                            $sure = false;
	                            if($work_schedule_id == 0 || $work_schedule_id == ""){
	                                $work_schedule_idx = $this->import->emp_work_schedule($this->emp_id,$this->company_id,$shift_date);
	                            }else{
	                                $work_schedule_idx = $work_schedule_id;
	                            }
	                            
	                            $no_schedule = $this->elm->get_workschedule_info_for_no_workschedule($this->emp_id,$this->company_id,$shift_date,$work_schedule_idx);
	                            
	                            if($shift == "" || $no_schedule ){
	                                #echo $abrv."<br>".$no_schedule;
	                                $shift_name = $abrv."<br>".$no_schedule;
	                            }else{
	                               # echo $time_name."<br>";
	                                #echo $shift;
	                                $shift_name = $time_name."<br>".$shift;
	                            }
	                        }
	                    }
	                } #end first if
	            }
	        } else {
	            $workday = date('l',strtotime($shift_date));
	            $restday = false;
	            
	            if($work_schedule_id == 0 || $work_schedule_id == ""){
	                $wsi = $this->import->emp_work_schedule($this->emp_id,$this->company_info->company_id,$shift_date);
	            }else{
	                $wsi = $work_schedule_id;
	            }
	            
	            $restday = $this->elm->check_rest_day($workday,$wsi,$this->company_id);
	            
	            if($check_holiday) {
	                #echo $check_holiday->holiday_name.'<br>';
	                #echo $check_holiday->hour_type_name;
	                $shift_name = $check_holiday->holiday_name.' ('.$check_holiday->hour_type_name.')';
	            } else {
	                if($work_schedule_id == -1 || $restday){
	                    #echo "Rest Day";
	                    $shift_name = "Rest Day";
	                } elseif($work_schedule_id == -2){
	                    #echo "Excess Hours";
	                    $shift_name = "Excess Hours";
	                } else {
	                    #echo "Schedule not Available";
	                    $shift_name = "Schedule not Available";
	                }
	            }
	        }
	        
	        $result = array(
	            'result' => 1,
	            'error' => true,
	            'shift_name' => $shift_name
	        );
	        
	        echo json_encode($result);
	        return false;
	    }
	}
	
	public function if_payroll_is_locked() {
	    // check if payroll is locked or closed
	    $shift_date = $this->input->post('shift_date');
	    $locked = "";
	    
	    if($shift_date) {
	        $void = $this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($shift_date)));
	        
	        if($void == "Waiting for approval"){
	            $locked = "Leaves locked for payroll processing.";
	        } elseif ($void == "Closed") {
	            $locked = "The leave you are submitting is part of a closed payroll. Your request will be routed to the appropriate approvers for consideration and approval.";
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
                $result = array(
	                'result' => 1,
	                'error' => false,
	                'err_msg' => ""
	            );
	            
	            echo json_encode($result);
	            return false;
            }
	    }
	}
	
	public function apply_leave(){
		
		$this->emp_id = $this->emp_id;
		/*if(!$this->company_id){
			print json_encode(array("result"=>5, "company_id"=> $this->company_id));
			exit;
		}*/
		//$post = $this->input->post();
			
		$flag = $this->input->post('flag');
			
		$shift_date = $this->input->post('shift_date');
		$shift_date = date('Y-m-d', strtotime($shift_date));
		$start_date = $this->input->post('start_date');
		$end_date = $this->input->post('end_date');
		$return_date = $this->input->post('return_date');
		$leave_type = $this->input->post('leave_type');
		$reason = $this->input->post('reason');
		$previous_credits = $this->input->post('previous_credits');
		
		$concat_start_date = date('Y-m-d H:i:s', strtotime($start_date));
		$concat_end_date = date('Y-m-d H:i:s', strtotime($end_date));
		$concat_return_date = date('Y-m-d H:i:s', strtotime($return_date));
		
		$concat_start_datetime = date("H:i:s",strtotime($concat_start_date));
		
		$concat_end_datetime = date("H:i:s",strtotime($concat_end_date));
		
		$concat_return_datetime = date("H:i:s",strtotime($concat_return_date));
		$cont_tlr_hidden = $this->input->post('cont_tlr_hidden');
		
		$schedule_blocks_id = $this->input->post('schedule_blocks_id');
	
		if($start_date == "" || $end_date == "" || $return_date=="" || $leave_type== "" ){
			echo json_encode(array('result' => 0, 'msg'=> 'Fields should not be empty'));
			return false;
			//exit;
		}
		
		// check leave restriction
		$halfday_rest                         = $this->leave->get_leave_restriction($leave_type,'provide_half_day_option');
		$apply_limit_rest                     = $this->leave->get_leave_restriction($leave_type,'allow_to_apply_leaves_beyond_limit');
		$exclude_holidays                     = $this->leave->get_leave_restriction($leave_type,'exclude_holidays');
		$num_days_b4_leave                    = $this->leave->get_leave_restriction($leave_type,'num_days_before_leave_application');
		$days_b4_leave                        = $this->leave->get_leave_restriction($leave_type,'days_before_leave_application');
		$num_cons_days                        = $this->leave->get_leave_restriction($leave_type,'num_consecutive_days_allowed');
		$cons_days                            = $this->leave->get_leave_restriction($leave_type,'consecutive_days_allowed');
		$num_cons_days_week_hol               = $this->leave->get_leave_restriction($leave_type,'num_consecutive_days_after_weekend_holiday');
		$cons_days_week_hol                   = $this->leave->get_leave_restriction($leave_type,'consecutive_days_after_weekend_holiday');
		$required_documents                   = $this->leave->get_leave_restriction($leave_type,'required_documents');
		$exclude_rest_days                    = $this->leave->get_leave_restriction($leave_type,'exclude_rest_days');
		$paid_leave                           = $this->leave->get_leave_restriction($leave_type,'paid_leave');
		$eff_date                             = $this->leave->get_leave_eff_date($leave_type,$this->company_id,$this->emp_id,"effective_date");
		$effective_start_date_by              = $this->leave->get_leave_restriction($leave_type,'effective_start_date_by');
		$effective_start_date                 = $this->leave->get_leave_restriction($leave_type,'effective_start_date');
		$leave_units                          = $this->leave->get_leave_restriction($leave_type,'leave_units');
		$what_happen_to_unused_leave          = $this->leave->get_leave_restriction($leave_type,'what_happen_to_unused_leave');
		$leave_conversion_run_every           = $this->leave->get_leave_restriction($leave_type,'leave_conversion_run_every');
		$carry_over_schedule_specific_month   = $this->leave->get_leave_restriction($leave_type,'carry_over_schedule_specific_month');
		$carry_over_schedule_specific_day     = $this->leave->get_leave_restriction($leave_type,'carry_over_schedule_specific_day');
		
		$per_day_credit = $this->prm->average_working_hours_per_day($this->company_id);
		
		$check_existing_leave_applied = $this->employee->check_existing_leave_applied($this->emp_id, $this->company_id, $concat_start_date, $concat_end_date);
		
		if($flag == 1){
			/* VIEW TOTAL LEAVE REQUESTED */
			/* CHECK EMPLOYEE WORK SCHEDULE */		
			$start_date = date("Y-m-d",strtotime($start_date));
		
			$currentdate = $start_date;
			$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$currentdate);
			$tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($this->company_id,$work_schedule_id);
			
			$is_work = is_break_assumed($work_schedule_id);
			if($tardiness_rule_migrated_v3) {
			    $is_work = false;
			}
			
			$check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($start_date)));
						
			if($work_schedule_id != FALSE){				
				// check workday
				$check_workday = $this->employee->check_workday_ws($this->emp_id,$this->company_id,$work_schedule_id);
				$start_date_req = date("l",strtotime($start_date));
				$check_regular = $this->employee->check_regular_ws($this->emp_id,$this->company_id,$work_schedule_id,$start_date_req);
				$check_workshift = $this->employee->check_workshift_ws($this->emp_id,$this->company_id,$work_schedule_id);
				
				$leave_units = $this->leave->get_leave_restriction($leave_type,'leave_units');
				$date1 = $concat_start_date;
				$date2 = $concat_end_date;
				$date3_return_date = $concat_return_date;
						
				if($check_workday != FALSE){
					$total_leave_request = 0;
					$tl1 = 0;
					$tl2 = 0;
					$fl_bet = 0;
							
					// less than 24 hours
					$fl_time = $check_workday->latest_time_in_allowed;
					$duration_of_lunch_break_per_day = $check_workday->duration_of_lunch_break_per_day / 60;
					$fl_hours_worked = $check_workday->total_hours_for_the_day - $duration_of_lunch_break_per_day;
					$date1 = $concat_start_date;
					$date2 = $concat_end_date;
					$date3_return_date = $concat_return_date;
					
					$req_hours_work = $fl_hours_worked;
					
					if($check_workday->not_required_login == "0"){
					    // for end date
					    $fl_latest_time_in_allowed = date("Y-m-d", strtotime($concat_start_date)).' '.$check_workday->latest_time_in_allowed;
					    
					    if(strtotime($concat_start_date) > strtotime($fl_latest_time_in_allowed)) {
					        $fl_time = $check_workday->latest_time_in_allowed;
					    } else {
					        $fl_time = date("H:i:s", strtotime($concat_start_date));
					    }
					}else{
						$fl_time = date("H:i:s", strtotime($date1));
					}
					
					$fl_mins_worked = $check_workday->total_hours_for_the_day * 60;
					
					$fl_time_date = date("Y-m-d", strtotime($concat_end_date)).' '.$fl_time;
					$fl_latest_time_out_allowed = date("Y-m-d H:i:s", strtotime($fl_time_date.' +'.$fl_mins_worked.' minutes'));
					
					if(strtotime($concat_end_date) < strtotime($fl_latest_time_out_allowed)) {
					    $fl_time_end = date("H:i:s", strtotime($concat_end_date));
					} else {
					    $fl_time_end = date("H:i:s", strtotime($fl_time_date.' +'.$fl_mins_worked.' minutes'));
					}
					
					
					$date_timein = date("H:i:s",strtotime($date1));
					$date_timeout = date("H:i:s",strtotime($date2));
					$d3_sec = date("H:i:s",strtotime($date3_return_date));
					$check_hours = strtotime(date("Y-m-d H:i:s",strtotime($date2))) - strtotime(date("Y-m-d H:i:s",strtotime($date1)));
					$total_leave_hours = $check_hours / 3600;
					$total_hours = $check_hours / 3600 / 24;
					
					if($is_work) {
					    if($check_break_time_for_assumed) {
					        $add_date = $start_date.' '.$check_break_time_for_assumed->latest_time_in_allowed;
					        $h = $is_work->assumed_breaks * 60;
					        $lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
					        $lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_workday->duration_of_lunch_break_per_day} minutes"));
					        
					        if(strtotime($concat_end_date) >= strtotime($lunch_out) && strtotime($concat_end_date) <= strtotime($lunch_in)) {
					            $date_timeout = date("H:i:s",strtotime($lunch_in));
					            $minus_break = $duration_of_lunch_break_per_day;
					        } elseif(strtotime($concat_start_date) >= strtotime($lunch_out) && strtotime($concat_start_date) <= strtotime($lunch_in)) {
					            $date_timein = date("H:i:s",strtotime($lunch_out));
					            $minus_break = $duration_of_lunch_break_per_day;
					        } elseif (strtotime($lunch_out) > strtotime($concat_start_date) && strtotime($concat_end_date) >= strtotime($lunch_in)) {
					            $minus_break = $duration_of_lunch_break_per_day;
					        } else {
					            $minus_break = 0;
					        }
					    } else {
					        $minus_break = 0;
					    }
					} else {
					    $minus_break = $duration_of_lunch_break_per_day;
					}
					
					if($total_hours < 1){
						$cb_date2 = date("A",strtotime($date2)); // if AM CALLCENTER
						$cb_date1 = date("A",strtotime($date1)); // if PM CALLCENTER
						
						$is_holiday = $this->employee->get_holiday_date($date1,$this->emp_id,$this->company_id);
						
						// exclude holiday
						if($exclude_holidays != 'yes'){
							$is_holiday = false;
						}
								
 						if(!$is_holiday){
 							if($cb_date1 == "PM") {
								$cb_date1_bogart = date("A",strtotime($date_timein));
								$cb_date2_bogart = date("A",strtotime($date_timeout));
								
								if($cb_date1_bogart == "PM" && $cb_date2_bogart == "PM") {
									#$d1 = ((strtotime($date_timeout) - strtotime($date_timein)) / 3600) - $minus_break;
								    $d1 = ((strtotime($fl_time_end) - strtotime($date_timein)) / 3600) - $minus_break;
								} else {
									$cb_date1_bogart1 = date("A",strtotime($date1));
									if($cb_date1_bogart == "PM") {
										$d1 = ((strtotime($date2) - strtotime($date1)) / 3600) - $minus_break;
									}
								}
								
							} else {
								#$d1 = ((strtotime($date_timeout) - strtotime($date_timein)) / 3600) - $minus_break;
							    $d1 = ((strtotime($fl_time_end) - strtotime($date_timein)) / 3600) - $minus_break;
							}
					
							$total_leave_request =(($d1 >= $fl_hours_worked) ? $fl_hours_worked : $d1);
							
							$hours_total = $total_leave_request;
							$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($date1)));
							$check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id);
							$duration = ($hours_total / $check_for_working_hours);
							$duration = number_format(round($duration,2),'2','.',',');
						}
						
						if($leave_units == 'days') {
							$total_leave_request = $total_leave_request / $fl_hours_worked;
						} else {
							$total_leave_request = $total_leave_request / $per_day_credit;
						}
					}else{
						$cb_date2 = date("A",strtotime($date2)); // if AM CALLCENTER
						$cb_date1 = date("A",strtotime($date1)); // if PM CALLCENTER
						#$d2 = (strtotime($date_timeout) - strtotime($fl_time)) / 3600;
						$d2 = ((strtotime($fl_time_end) - strtotime($date_timein)) / 3600) - $minus_break;
						
						$tl1 = ($d2 >= $fl_hours_worked) ? $fl_hours_worked : $d2;
						
						/*if($cb_date2 == "AM" && $cb_date1 == "PM"){
							$check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
						}else{
							$check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
						}*/
								
						$check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
						
						if(round($check_total_days) > 0){
						    $tl2 = 0;
						    for($cnt=1;$cnt<=$check_total_days;$cnt++){
						        $work_sched_id = check_employee_work_schedule(date("Y-m-d",strtotime($date1." +".$cnt." day")), $this->emp_id, $this->company_id)->work_schedule_id;
						        $is_holiday_tl2 = $this->employee->get_holiday_date(date("Y-m-d",strtotime($date1." +".$cnt." day")),$this->emp_id,$this->company_id);
						        $rest_day = $this->ews->get_rest_day($this->company_id,$work_sched_id,date("l",strtotime($date1." +".$cnt." day")));
						        
						        // exclude holiday
						        if($exclude_holidays != 'yes'){
						            $is_holiday_tl2 = false;
						        }
						        
						        if(!$is_holiday_tl2 && !$rest_day){
						            $check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1." +".$cnt." day")),$work_schedule_id);
						            $tl2 += $check_for_working_hours;
						            #echo $tl2."***";
						        }
						    }
						    $tl2 = $tl2;
						    #p($tl2);
						}
							
						if($leave_units == 'days') {
						    #p(array($total_leave_request,$tl1,$tl2,$fl_bet,$fl_hours_worked));
						    $total_leave_request = ($total_leave_request + $tl1 + $tl2 + $fl_bet) / $fl_hours_worked;
						    
						    $hours_total = $total_leave_request * $fl_hours_worked;
						} else {
							$total_leave_request = ($total_leave_request + $tl1 + $tl2 + $fl_bet) / $per_day_credit;
							$hours_total = $total_leave_request * $per_day_credit;
						}
						
						$minus_hours_total = $hours_total;
						$check_total_days2 = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
						$duration = 0;
							
						if(round($check_total_days2) > 0){
							for($cnt=0;$cnt<=$check_total_days2;$cnt++){
								$is_holiday_tl3 = $this->employee->get_holiday_date(date("Y-m-d",strtotime($date1." +".$cnt." day")),$this->emp_id,$this->company_id);
								$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($date1." +".$cnt." day")));
								$rest_day2 = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($date1." +".$cnt." day")));
						
								// exclude holiday
								if($exclude_holidays != 'yes'){
									$is_holiday_tl3 = false;
								}
									
								if(!$is_holiday_tl3 && !$rest_day2){
									$check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1." +".$cnt." day")),$work_schedule_id);
									if($minus_hours_total > $check_for_working_hours){
										$minus_hours_total = $minus_hours_total - $check_for_working_hours;
											
										$duration = $duration + 1;
									}else{
										$duration = $duration + ($minus_hours_total / $check_for_working_hours);
									}
								}
							}
								
						}
						$duration = number_format(round($duration,2),'2','.',',');
					}
				}elseif($check_regular != FALSE){
					foreach($check_regular as $cr){
						$req_hours_work = $cr->total_work_hours;
					}
					
					// for uniform working days, workshift
					// less than 24 hours
					$date1 = $concat_start_date;
					$date2 = $concat_end_date;
					$date3_return_date = $concat_return_date;
					
					$date_timein = date("H:i:s",strtotime($date1));
					$date_timeout = date("H:i:s",strtotime($date2));
					
					$check_hours = strtotime(date("Y-m-d H:i:s",strtotime($date2))) - strtotime(date("Y-m-d H:i:s",strtotime($date1)));
					$total_leave_hours = $check_hours / 3600;
					$total_hours = $check_hours / 3600 / 24;
					
					$total_leave_request = 0;
					$tl1 = 0;
					$tl2 = 0;
					$tl3 = 0;
					$tl4 = 0;
					
					// check parameter
					$check_date = strtotime(date("Y-m-d",strtotime($date1)));
					$hours_worked = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id);
					$hours_worked_break = $this->employee->for_leave_hoursworked_break($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id);
					$check_start_time = $this->employee->for_leave_breaktime_start_time_ws($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id, $concat_start_datetime);
					$check_end_time = $this->employee->for_leave_breaktime_end_time_ws($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id, $concat_start_datetime);
					
					#$work_start_time = $this->employee->for_leave_hoursworked_work_start_time_ws($this->emp_id,$this->company_id,$work_schedule_id);
					$work_start_time = $this->employee->for_leave_hoursworked_work_start_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,$concat_start_date);
					$work_end_time = $this->employee->for_leave_hoursworked_work_end_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,$concat_start_date);
					#$work_end_time = $this->employee->for_leave_hoursworked_work_end_time_ws($this->emp_id,$this->company_id,$work_schedule_id,$end_date);
					#$work_end_time = $this->employee->for_leave_hoursworked_work_end_time_ws($this->emp_id,$this->company_id,$work_schedule_id,$start_date);
					
					$check_date_and_time_in = $this->employee->check_date_and_time_in($start_date, $this->emp_id, $this->company_id);

					if($tardiness_rule_migrated_v3) {
					    if($check_break_time_for_assumed) {
					        $get_schedule_settings = get_schedule_settings_by_workschedule_id($work_schedule_id,$this->company_id,date("l", strtotime($shift_date)));
					        $grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
					        
					        $add_datex = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$grace." minutes"));
					        $add_datey = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
					        
					        $work_start_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
					        $work_end_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time));
					        
					        if(strtotime($concat_start_date) > strtotime($add_datex)) {
					            
					            if($check_date_and_time_in) {
					                if (strtotime($check_date_and_time_in->time_in) >= strtotime($add_datey)) {
					                    $start_new = $check_date_and_time_in->time_in;
					                    $start_new_diff = (strtotime($add_datey) - strtotime($check_date_and_time_in->time_in)) / 60;
					                    
					                    if($start_new_diff < 0){
					                        $start_new_diff = (strtotime($check_date_and_time_in->time_in) - strtotime($add_datey)) / 60;
					                    }
					                    
					                    $add_date = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$start_new_diff." minutes"));
					                    $new_end_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
					                    
					                    if(strtotime($new_end_date) >= strtotime($concat_end_date)) {
					                        $date2 = $concat_end_date;
					                        $work_end_time = date('H:i:s', strtotime($concat_end_date));
					                    } else {
					                        $date2 = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
					                        $work_end_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
					                    }
					                }
					            } else {
					                $date2 = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time));
					                $add_date = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
					            }
					        } else {
					            
					            if (strtotime($add_datey) >= strtotime($concat_start_date)) {
					                $add_date = $shift_date.' '.$check_break_time_for_assumed->work_start_time;
					            } elseif (strtotime($concat_start_date) >= strtotime($add_datey)) {
					                $start_new = $concat_start_date;
					                $start_new_diff = (strtotime($add_datey) - strtotime($concat_start_date)) / 60;
					                if($start_new_diff < 0){
					                    $start_new_diff = (strtotime($concat_start_date) - strtotime($add_datey)) / 60;
					                }
					                $add_date = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$start_new_diff." minutes"));
					            }
					        }
					        
					        $h = $hours_worked / 2;
					        $h = $h * 60;
					        
					        $hours_worked_break_l = 0;
					        $hours_worked_break_b1 = 0;
					        $hours_worked_break_b2 = 0;
					        # p($get_schedule_settings);
					        if($get_schedule_settings->enable_lunch_break == "yes") {
					            if($get_schedule_settings->break_schedule_1 == "fixed") {
					                if($get_schedule_settings->break_type_1 == "unpaid") {
					                    $break_started_after_in_mins = $get_schedule_settings->break_started_after * 60;
					                    $lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins} minutes"));
					                    $lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$get_schedule_settings->break_in_min} minutes"));
					                    
					                    if(strtotime($concat_start_date) < strtotime($lunch_out) && strtotime($concat_start_date) > strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                    } elseif(strtotime($concat_start_date) <= strtotime($lunch_out) && strtotime($concat_end_date) >= strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                    } elseif(strtotime($concat_start_date) >= strtotime($lunch_out) && strtotime($concat_start_date) < strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                    } elseif(strtotime($concat_end_date) > strtotime($lunch_out) && strtotime($concat_end_date) < strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                        $date2 = $lunch_in;
					                    } else {
					                        $hours_worked_break_l = 0;
					                    }
					                }
					            } elseif($get_schedule_settings->break_schedule_1 == "flexi" || $get_schedule_settings->track_break_1 == "no") {
					                if($get_schedule_settings->break_type_1 == "unpaid") {
					                    $total_hrs_of_break_mins = $get_schedule_settings->total_work_hours;
					                    $break_started_after_in_mins = ($total_hrs_of_break_mins / 2) * 3600;
					                    
					                    $lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins} seconds"));
					                    $lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$get_schedule_settings->break_in_min} minutes"));
					                    
					                    if(strtotime($concat_start_date) < strtotime($lunch_out) && strtotime($concat_start_date) > strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                    } elseif(strtotime($concat_start_date) <= strtotime($lunch_out) && strtotime($concat_end_date) >= strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                    } elseif(strtotime($concat_start_date) >= strtotime($lunch_out) && strtotime($concat_start_date) < strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                    } elseif(strtotime($concat_end_date) > strtotime($lunch_out) && strtotime($concat_end_date) < strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                        $date2 = $lunch_in;
					                    } else {
					                        $hours_worked_break_l = 0;
					                    }
					                }
					            }
					        }
					        
					        if($get_schedule_settings->enable_additional_breaks == "yes") {
					            if($get_schedule_settings->break_schedule_2 == "fixed") {
					                if($get_schedule_settings->break_type_2 == "unpaid") {
					                    if($get_schedule_settings->num_of_additional_breaks > 0) {
					                        if($get_schedule_settings->additional_break_started_after_1 != "") {
					                            $break_started_after_in_mins = $get_schedule_settings->additional_break_started_after_1 * 60;
					                            $break_1 = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins} minutes"));
					                            $break_1_end = date('Y-m-d H:i:s',strtotime($break_1. " +{$get_schedule_settings->break_1_in_min} minutes"));
					                            
					                            if(strtotime($concat_start_date) < strtotime($break_1) && strtotime($concat_start_date) > strtotime($break_1_end)) {
					                                $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                            } elseif(strtotime($concat_start_date) <= strtotime($break_1) && strtotime($concat_end_date) >= strtotime($break_1_end)) {
					                                $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                            } elseif(strtotime($concat_start_date) >= strtotime($break_1) && strtotime($concat_start_date) < strtotime($break_1_end)) {
					                                $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                            } else {
					                                $hours_worked_break_b1 = 0;
					                            }
					                        }
					                        
					                        if($get_schedule_settings->additional_break_started_after_2 != "") {
					                            $break_started_after_in_mins = $get_schedule_settings->additional_break_started_after_2 * 60;
					                            $break_2 = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins} minutes"));
					                            $break_2_end = date('Y-m-d H:i:s',strtotime($break_2. " +{$get_schedule_settings->break_2_in_min} minutes"));
					                            
					                            if(strtotime($concat_start_date) < strtotime($break_2) && strtotime($concat_start_date) > strtotime($break_2_end)) {
					                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
					                            } elseif(strtotime($concat_start_date) <= strtotime($break_2) && strtotime($concat_end_date) >= strtotime($break_2_end)) {
					                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
					                            } elseif(strtotime($concat_start_date) >= strtotime($break_2) && strtotime($concat_start_date) < strtotime($break_2_end)) {
					                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
					                            } else {
					                                $hours_worked_break_b2 = 0;
					                            }
					                        }
					                    }
					                }
					            } elseif($get_schedule_settings->break_schedule_2 == "flexi" || $get_schedule_settings->track_break_2 == "no") {
					                if($get_schedule_settings->break_type_2 == "unpaid") {
					                    if($get_schedule_settings->num_of_additional_breaks > 0) {
					                        // first half
					                        if($get_schedule_settings->break_1_in_min != "") {
					                            if($get_schedule_settings->num_of_additional_breaks == 1) {
					                                $total_hrs_of_break_mins = $get_schedule_settings->total_work_hours;
					                                $break_started_after_in_mins = $total_hrs_of_break_mins / 2;
					                                $break_started_after_in_mins_half = ($break_started_after_in_mins / 2) * 3600;
					                                $break_started_after_in_mins_lunch = ($total_hrs_of_break_mins / 2) * 3600;
					                                
					                                // get the lunch in to come up the time of 2nd break time
					                                $lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins_lunch} seconds"));
					                                $lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$get_schedule_settings->break_in_min} minutes"));
					                                
					                                // 2nd break time
					                                $start_break_1 = date('Y-m-d H:i:s',strtotime($lunch_in. " +{$break_started_after_in_mins_half} seconds"));
					                                $end_break_1 = date('Y-m-d H:i:s',strtotime($start_break_1. " +{$get_schedule_settings->break_1_in_min} minutes"));
					                                
					                                if(strtotime($concat_start_date) < strtotime($start_break_1) && strtotime($concat_start_date) > strtotime($end_break_1)) {
					                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                                } elseif(strtotime($concat_start_date) <= strtotime($start_break_1) && strtotime($concat_end_date) >= strtotime($end_break_1)) {
					                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                                } elseif(strtotime($concat_start_date) >= strtotime($start_break_1) && strtotime($concat_start_date) < strtotime($end_break_1)) {
					                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                                } else {
					                                    $hours_worked_break_b1 = 0;
					                                }
					                            } else {
					                                $total_hrs_of_break_mins = $get_schedule_settings->total_work_hours;
					                                $break_started_after_in_mins = $total_hrs_of_break_mins / 2;
					                                $break_started_after_in_mins_half = ($break_started_after_in_mins / 2) * 3600;
					                                
					                                // first break time
					                                $start_break_1 = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins_half} seconds"));
					                                $end_break_1 = date('Y-m-d H:i:s',strtotime($start_break_1. " +{$get_schedule_settings->break_1_in_min} minutes"));
					                                
					                                if(strtotime($concat_start_date) < strtotime($start_break_1) && strtotime($concat_start_date) > strtotime($end_break_1)) {
					                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                                } elseif(strtotime($concat_start_date) <= strtotime($start_break_1) && strtotime($concat_end_date) >= strtotime($end_break_1)) {
					                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                                } elseif(strtotime($concat_start_date) >= strtotime($start_break_1) && strtotime($concat_start_date) < strtotime($end_break_1)) {
					                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                                } else {
					                                    $hours_worked_break_b1 = 0;
					                                }
					                            }
					                        }
					                        
					                        // second half
					                        if($get_schedule_settings->break_2_in_min != "") {
					                            $total_hrs_of_break_mins = $get_schedule_settings->total_work_hours;
					                            $break_started_after_in_mins = $total_hrs_of_break_mins / 2;
					                            $break_started_after_in_mins_half = ($break_started_after_in_mins / 2) * 3600;
					                            $break_started_after_in_mins_lunch = ($total_hrs_of_break_mins / 2) * 3600;
					                            
					                            // get the lunch in to come up the time of 2nd break time
					                            $lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins_lunch} seconds"));
					                            $lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$get_schedule_settings->break_in_min} minutes"));
					                            
					                            // 2nd break time
					                            $start_break_2 = date('Y-m-d H:i:s',strtotime($lunch_in. " +{$break_started_after_in_mins_half} seconds"));
					                            $end_break_2 = date('Y-m-d H:i:s',strtotime($start_break_2. " +{$get_schedule_settings->break_2_in_min} minutes"));
					                            
					                            if(strtotime($concat_start_date) < strtotime($start_break_2) && strtotime($concat_start_date) > strtotime($end_break_2)) {
					                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
					                            } elseif(strtotime($concat_start_date) <= strtotime($start_break_2) && strtotime($concat_end_date) >= strtotime($end_break_2)) {
					                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
					                            } elseif(strtotime($concat_start_date) >= strtotime($start_break_2) && strtotime($concat_start_date) < strtotime($end_break_2)) {
					                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
					                            } else {
					                                $hours_worked_break_b2 = 0;
					                            }
					                        }
					                    }
					                }
					            }
					        }
					        $hours_worked_break = $hours_worked_break_l + $hours_worked_break_b1 + $hours_worked_break_b2;
					    }
					}
					
					if($total_hours < 1){
						// check for holiday
						$is_holiday = $this->employee->get_holiday_date($start_date,$this->emp_id,$this->company_id);
						
						// exclude holiday
						if($exclude_holidays != 'yes'){
							$is_holiday = false;
						}
						
						$new_start_break = "";
						$new_end_break = "";
						if($is_holiday == FALSE){
							if($is_work) {
								if($check_break_time_for_assumed) {
									$grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
									
									$add_datex = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$grace." minutes"));
									$add_datey = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
									if(strtotime($concat_start_date) > strtotime($add_datex)) {
									    $work_start_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
									    $work_end_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time));
									    
										if($check_date_and_time_in) {
											if (strtotime($check_date_and_time_in->time_in) >= strtotime($add_datey)) {
												$start_new = $check_date_and_time_in->time_in;
												$start_new_diff = (strtotime($add_datey) - strtotime($check_date_and_time_in->time_in)) / 60;
												if($start_new_diff < 0){
													$start_new_diff = (strtotime($check_date_and_time_in->time_in) - strtotime($add_datey)) / 60;
												}
												$add_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$start_new_diff." minutes"));
												$new_end_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
												
												if(strtotime($new_end_date) >= strtotime($concat_end_date)) { 
													$date2 = $concat_end_date;
													$work_end_time = date('H:i:s', strtotime($concat_end_date));
												} else {
													$date2 = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
													$work_end_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
												}
											}
										} else {
											$add_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$grace." minutes"));
										}
									} else {
										if (strtotime($add_datey) >= strtotime($concat_start_date)) {
											$add_date = $start_date.' '.$check_break_time_for_assumed->work_start_time;
										} elseif (strtotime($concat_start_date) >= strtotime($add_datey)) {
											$start_new = $concat_start_date;
											$start_new_diff = (strtotime($add_datey) - strtotime($concat_start_date)) / 60;
											if($start_new_diff < 0){
												$start_new_diff = (strtotime($concat_start_date) - strtotime($add_datey)) / 60;
											}
											$add_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$start_new_diff." minutes"));
										}
									}
									
									$h = $is_work->assumed_breaks * 60;
									$lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
									$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_break_time_for_assumed->break_in_min} minutes"));
									
									if(strtotime($concat_end_date) >= strtotime($lunch_out) && strtotime($concat_end_date) <= strtotime($lunch_in)) {
									    $hours_worked_break = 0;
									    $date2 = $lunch_out;
									    $check_end_time = date('H:i:s', strtotime($lunch_in)); // $lunch_in
									} elseif(strtotime($concat_start_date) >= strtotime($lunch_out) && strtotime($concat_start_date) <= strtotime($lunch_in)) {
									    $hours_worked_break = 0;
									    $date1 = $lunch_in;
									    
									    $check_end_time = date('H:i:s', strtotime($lunch_in)); // $lunch_in
									    #$date_timein = $lunch_out;
									} elseif(strtotime($concat_end_date) < strtotime($lunch_out)) {
										$hours_worked_break = 0;
									} else {
										$hours_worked_break = $hours_worked_break;
									}
								}
							} else {
							    if($check_break_time_for_assumed && !$tardiness_rule_migrated_v3) {
							        
									$grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
										
									$add_datex = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$grace." minutes"));
									$add_datey = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
									if(strtotime($concat_start_date) > strtotime($add_datex)) {
									    $work_start_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
									    $work_end_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time));
										if($check_date_and_time_in) {
											if (strtotime($check_date_and_time_in->time_in) >= strtotime($add_datey)) {
												$start_new = $check_date_and_time_in->time_in;
												$start_new_diff = (strtotime($add_datey) - strtotime($check_date_and_time_in->time_in)) / 60;
												if($start_new_diff < 0){
													$start_new_diff = (strtotime($check_date_and_time_in->time_in) - strtotime($add_datey)) / 60;
												}
												$add_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$start_new_diff." minutes"));
												$new_end_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
												
												if(strtotime($new_end_date) >= strtotime($concat_end_date)) {
													$date2 = $concat_end_date;
													$work_end_time = date('H:i:s', strtotime($concat_end_date));
												} else {
													$date2 = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
													$work_end_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
												}
											}
										} else {
											$date2 = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$grace." minutes"));
											$add_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$grace." minutes"));
										}
									} else {
										if (strtotime($add_datey) >= strtotime($concat_start_date)) {
											$add_date = $start_date.' '.$check_break_time_for_assumed->work_start_time;
										} elseif (strtotime($concat_start_date) >= strtotime($add_datey)) {
											$start_new = $concat_start_date;
											$start_new_diff = (strtotime($add_datey) - strtotime($concat_start_date)) / 60;
											if($start_new_diff < 0){
												$start_new_diff = (strtotime($concat_start_date) - strtotime($add_datey)) / 60;
											}
											$add_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$start_new_diff." minutes"));
										}
									}
									
									$h = $hours_worked / 2;
									$h = $h * 60;
									$lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
									$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_break_time_for_assumed->break_in_min} minutes"));
																					
									if(strtotime($concat_end_date) >= strtotime($lunch_out) && strtotime($concat_end_date) <= strtotime($lunch_in)) {
									    $hours_worked_break = 0;
									    $date2 = $lunch_out;
									    $check_end_time = date('H:i:s', strtotime($lunch_in)); // $lunch_in;
									} elseif(strtotime($concat_start_date) >= strtotime($lunch_out) && strtotime($concat_start_date) <= strtotime($lunch_in)) {
									    $hours_worked_break = 0;
									    $date1 = $lunch_in;
									    #$date2 = $lunch_out;
									    $check_end_time = date('H:i:s', strtotime($lunch_in)); // $lunch_in;
									    $date_timein = $lunch_out;
									} elseif(strtotime($concat_end_date) < strtotime($lunch_out)) { 
										$hours_worked_break = 0;
									} else {
										#$date1 = $lunch_in;
										$hours_worked_break = $hours_worked_break;
									}
								}
							}
							
							// for regular days
							$new_check_end_time = $shift_date.' '.$check_end_time;
							
							if(strtotime($date2) >= strtotime($concat_end_date)) { // ako ni g.trap, idont know nganu g.ingnani pani pro libug na kaau esubay sa code lol
							    $date2 = $concat_end_date;
							}
							
							if(strtotime($new_check_end_time) <= strtotime($concat_end_date)){
								$date_for_timein = date("Y-m-d",strtotime($date_timein));
								$date_for_timein_plus_one_day = date("Y-m-d",strtotime("+1 day".$date_timein));
								
								$date_timein_am_pm = date("A",strtotime($date_timein));
								$date_timein_am_pm_plus_one_day = date("A",strtotime($check_end_time));
								
								if($date_timein_am_pm == "PM" && $date_timein_am_pm_plus_one_day == "AM"){
								    if(strtotime($date_for_timein." ".$date_timein) < strtotime($date_for_timein_plus_one_day." ".$check_end_time)){
								        if($tardiness_rule_migrated_v3) {
								            if($leave_units == 'days') {
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $hours_worked;
								            } else {
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $per_day_credit;
								            }
								        } else {
								            if($leave_units == 'days') {
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id)) / $hours_worked;
								            } else {
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id)) / $per_day_credit;
								            }
								        }
								        
								    }else{
										if(strtotime($date_timeout) <= strtotime($work_end_time)){
											if($leave_units == 'days') {
												$total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600) / $hours_worked;
											} else {
												$total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600) / $per_day_credit;
											}
										}else{
											if($leave_units == 'days') {
												$total_leave_request = ((strtotime($work_end_time) - strtotime($date_timein)) / 3600) / $hours_worked;
											} else {
												$total_leave_request = ((strtotime($work_end_time) - strtotime($date_timein)) / 3600) / $per_day_credit;
											}
										}
									}
								}else{
								    if(strtotime($date_timein) < strtotime($check_end_time)){
								        
								        if($tardiness_rule_migrated_v3) {
								            if($leave_units == 'days') {
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $hours_worked;
								            } else {
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $per_day_credit;
								            }
								        } else {
								            if($leave_units == 'days') {
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id)) / $hours_worked;
								            } else {
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id)) / $per_day_credit;
								            }
								        }
								    }else{
								        if(strtotime($date_timeout) <= strtotime($work_end_time)){
								            if($tardiness_rule_migrated_v3) {
								                if($leave_units == 'days') {
								                    $total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600 - ($hours_worked_break / 60)) / $hours_worked;
								                } else {
								                    $total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600 - ($hours_worked_break / 60)) / $per_day_credit;
								                }
								            } else {
								                if($leave_units == 'days') {
								                    $total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600) / $hours_worked;
								                } else {
								                    $total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600) / $per_day_credit;
								                }
								            }
								            
								        }else{
								            if($leave_units == 'days') {
								                $total_leave_request = ((strtotime($work_end_time) - strtotime($date_timein)) / 3600) / $hours_worked;
								                #echo $work_end_time.' - '.$date_timein.' - '.$hours_worked.'<br>';
								            } else {
								                $total_leave_request = ((strtotime($work_end_time) - strtotime($date_timein)) / 3600) / $per_day_credit;
								            }
								        }
									}
								}
							}else{
							    if($tardiness_rule_migrated_v3) {
							        if($leave_units == 'days') {
							            $hour_break = floor($hours_worked_break / 60);
							            $hous_and_break = $hours_worked + $hour_break;
							            $total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600 - ($hours_worked_break / 60)) / $hous_and_break;
							        } else {
							            $total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600 - ($hours_worked_break / 60)) / $per_day_credit;
							        }
							    } else {
							        if($leave_units == 'days') {
							            $hour_break = floor($hours_worked_break / 60);
							            $hous_and_break = $hours_worked + $hour_break;
							            $total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600) / $hous_and_break;
							        } else {
							            $total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600) / $per_day_credit;
							        }
							    }
							}
							
							if($leave_units == 'days') {
								$hours_total = $total_leave_request * $hours_worked;
							} else {
								$hours_total = $total_leave_request * $per_day_credit;
							}
								
							$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($date1)));
							$check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id);
							$duration = ($hours_total / $check_for_working_hours);
								
							$duration = number_format(round($duration,2),'2','.',',');
						}
					}else{
						$cb_date2 = date("A",strtotime($date2)); // if AM CALLCENTER
						$cb_date1 = date("A",strtotime($date1)); // if PM CALLCENTER
						
						$date1_date = date("Y-m-d",strtotime($date1));
						$date1_sec = date("H:i:s",strtotime($date1));
						
						$date2_date = date("Y-m-d",strtotime($date2));
						$date2_sec = date("H:i:s",strtotime($date2));
						
						$date3_date = date("Y-m-d",strtotime($date3_return_date));
						$date3_sec = date("H:i:s",strtotime($date3_return_date));
						
						// total leave for start date
						$date1_date_add_oneday = date("Y-m-d",strtotime($date1_date." +1 day"));
						$is_holiday_tl1 = $this->employee->get_holiday_date($date1_date,$this->emp_id,$this->company_id);
						// exclude holiday
						if($exclude_holidays != 'yes'){
							$is_holiday_tl1 = false;
						}
						
						if(!$is_holiday_tl1){
							if($cb_date2 == "AM" && $cb_date1 == "PM"){
							    if(strtotime($date1) <= strtotime($date1_date_add_oneday." ".$check_start_time)){
							        if($tardiness_rule_migrated_v3) {
							            if($leave_units == 'days') {
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date1_date_add_oneday." ".$work_end_time,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $hours_worked;
							            
							            } else {
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date1_date_add_oneday." ".$work_end_time,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $per_day_credit;
							            }
							        } else {
							            if($leave_units == 'days') {
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date1_date_add_oneday." ".$work_end_time,$hours_worked,$work_schedule_id)) / $hours_worked;
							            } else {
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date1_date_add_oneday." ".$work_end_time,$hours_worked,$work_schedule_id)) / $per_day_credit;
							            }
							        }
							    }elseif(strtotime($date1_sec) <= strtotime($work_end_time)){
							        if($tardiness_rule_migrated_v3) {
							            if($leave_units == 'days') {
							                $tl1 = ((strtotime($work_end_time) - strtotime($date1_sec)) / 3600 - ($hours_worked_break / 60)) / $hours_worked;
							            } else {
							                $tl1 = ((strtotime($work_end_time) - strtotime($date1_sec)) / 3600 - ($hours_worked_break / 60)) / $per_day_credit;
							            }
							        } else {
							            if($leave_units == 'days') {
							                $tl1 = ((strtotime($work_end_time) - strtotime($date1_sec)) / 3600) / $hours_worked;
							            } else {
							                $tl1 = ((strtotime($work_end_time) - strtotime($date1_sec)) / 3600) / $per_day_credit;
							            }
							        }
							    }
							}else{
							    
							    $date2 = date("Y-m-d", strtotime($shift_date))." ".$work_end_time;
							    
							    if(strtotime($date2) >= strtotime($concat_end_date)) { // ako ni g.trap, idont know nganu g.ingnani pani pro libug na kaau esubay sa code lol
							        $date2 = $concat_end_date;
							    }
							    
							    if(strtotime($date1_sec) <= strtotime($check_start_time)){
							        if($tardiness_rule_migrated_v3) {
							            if($leave_units == 'days') {
							                #$tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date1_date." ".$work_end_time,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $hours_worked;
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $hours_worked;
							            
							            } else {
							                #$tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date1_date." ".$work_end_time,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $per_day_credit;
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $per_day_credit;
							            }
							        } else {
							            if($leave_units == 'days') {
							                #$tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date1_date." ".$work_end_time,$hours_worked,$work_schedule_id)) / $hours_worked;
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id)) / $hours_worked;
							            } else {
							                #$tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date1_date." ".$work_end_time,$hours_worked,$work_schedule_id)) / $per_day_credit;
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id)) / $per_day_credit;
							            }
							        }
							        
							    }elseif(strtotime($date1_sec) <= strtotime($work_end_time)){
							        if($tardiness_rule_migrated_v3) {
							            if($leave_units == 'days') {
							                $tl1 = ((strtotime($work_end_time) - strtotime($date1_sec)) / 3600 - ($hours_worked_break / 60)) / $hours_worked;
							            } else {
							                $tl1 = ((strtotime($work_end_time) - strtotime($date1_sec)) / 3600 - ($hours_worked_break / 60)) / $per_day_credit;
							            }
							        } else {
							            if($leave_units == 'days') {
							                $tl1 = ((strtotime($work_end_time) - strtotime($date1_sec)) / 3600) / $hours_worked;
							            } else {
							                $tl1 = ((strtotime($work_end_time) - strtotime($date1_sec)) / 3600) / $per_day_credit;
							            }
							        }
							        
							    }
							}
						}
						
						// total leave for end date
						$is_holiday_tl2 = $this->employee->get_holiday_date($date2_date,$this->emp_id,$this->company_id);
						
						// exclude holiday
						if($exclude_holidays != 'yes'){
							$is_holiday_tl2 = false;
						}
						
						if(!$is_holiday_tl2){
							$hours_worked2 = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date2)),$work_schedule_id);
							$check_end_time2 = $this->employee->for_leave_breaktime_end_time_ws($this->emp_id,$this->company_id,date("l",strtotime($date2)),$work_schedule_id, $concat_start_datetime);
							$new_work_start_time = $work_start_time;
							$new_st = $this->employee->get_start_time($this->emp_id,$this->company_id,$work_schedule_id,"work_schedule");
							$new_latest_timein_allowed = $this->employee->new_latest_timein_allowed($this->emp_id,$this->company_id,$work_schedule_id,"work_schedule");
							
							if($new_latest_timein_allowed){ // if latest time in is true
								if(strtotime($concat_start_datetime) < strtotime($new_st)){
									$new_work_start_time = $new_st;
								}elseif(strtotime($new_st) <= strtotime($concat_start_datetime) && strtotime($concat_start_datetime) <= strtotime($new_latest_timein_allowed)){
									$new_work_start_time = $concat_start_datetime;
								}elseif(strtotime($concat_start_datetime) > strtotime($new_latest_timein_allowed)){
								    $new_work_start_time = $work_start_time;
								}
							}
							
							if(strtotime($check_end_time2) <= strtotime($date2_sec)){
							    $date2 = date("Y-m-d",strtotime($concat_end_date))." ".date("H:i:s", strtotime($date2));
							    if(strtotime($date2) >= strtotime($concat_end_date)) { // ako ni g.trap, idont know nganu g.ingnani pani pro libug na kaau esubay sa code lol
							        $date2 = $concat_end_date;
							    }
							    
							    if($tardiness_rule_migrated_v3) {
							        if($leave_units == 'days') {
							            #$tl2 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date2_date." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id,$hours_worked_break,true)) / $hours_worked;
							            $tl2 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,date("Y-m-d",strtotime($concat_end_date))." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id,$hours_worked_break,true)) / $hours_worked;
							        
							        } else {
							            #$tl2 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date2_date." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id,$hours_worked_break,true)) / $per_day_credit;
							            $tl2 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,date("Y-m-d",strtotime($concat_end_date))." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id,$hours_worked_break,true)) / $per_day_credit;
							        }
							    } else {
							        if($leave_units == 'days') {
							            #$tl2 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date2_date." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id)) / $hours_worked;
							            $tl2 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,date("Y-m-d",strtotime($concat_end_date))." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id)) / $hours_worked;
							        } else {
							            #$tl2 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date2_date." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id)) / $per_day_credit;
							            $tl2 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,date("Y-m-d",strtotime($concat_end_date))." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id)) / $per_day_credit;
							        }
							    }
							}elseif(strtotime($date2_sec) >= strtotime($new_work_start_time)){
								if($leave_units == 'days') {
									$tl2 = ((strtotime($date2_sec) - strtotime($new_work_start_time)) / 3600) / $hours_worked;
								} else {
									$tl2 = ((strtotime($date2_sec) - strtotime($new_work_start_time)) / 3600) / $per_day_credit;
								}
							}
						}
						
						// total between dates							
						if($cb_date2 == "AM" && $cb_date1 == "PM"){
							$check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);								
						}else{
							$check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24) - 1;
						
						}
						
						if(round($check_total_days) > 0){
							$tl3 = 0;
							for($cnt=1;$cnt<=$check_total_days;$cnt++){
								$is_holiday_tl3 = $this->employee->get_holiday_date(date("Y-m-d",strtotime($date1." +".$cnt." day")),$this->emp_id,$this->company_id);
								// exclude holiday
								if($exclude_holidays != 'yes'){
									$is_holiday_tl3 = false;
								}
								
								if(!$is_holiday_tl3){
									$check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1." +".$cnt." day")),$work_schedule_id);
									$tl3 += $check_for_working_hours;
								
								}
							}
							
							if($leave_units == 'days') {
								$tl3 = $tl3 / $hours_worked;
							
							} else {
								$tl3 = $tl3 / $per_day_credit;
							}
						}
						
						// total return date
						$date3_date_add_oneday = date("Y-m-d",strtotime($date3_date." +1 day"));
						$is_holiday_tl4 = $this->employee->get_holiday_date($date3_date,$this->emp_id,$this->company_id);
						
						// exclude holiday
						if($exclude_holidays != 'yes'){
							$is_holiday_tl4 = false;
						}
						
						if(!$is_holiday_tl4){
							if($cb_date2 == "AM" && $cb_date1 == "PM"){
								if(strtotime($date3_return_date) <= strtotime($date3_date_add_oneday." ".$check_start_time)){
									$total_return_date = ((strtotime($date3_return_date) - strtotime($date3_date_add_oneday." ".$work_start_time)) / 3600) / $per_day_credit;
									if($total_return_date >= 0){
										$tl4 = ((strtotime($date3_return_date) - strtotime($date3_date_add_oneday." ".$work_start_time)) / 3600) / $per_day_credit;
									}
								}else{
									$return_breaktime = (strtotime($check_end_time) - strtotime($check_start_time)) / 3600;
									$tl4 = (((strtotime($date3_return_date) - strtotime($date3_date_add_oneday." ".$work_start_time)) / 3600) - $return_breaktime) / $per_day_credit;
								}
							}else{
								if(strtotime($date3_sec) <= strtotime($check_start_time)){
									$total_return_date = ((strtotime($date3_sec) - strtotime($work_start_time)) / 3600) / $per_day_credit;
									if($total_return_date >= 0){
										$tl4 = ((strtotime($date3_sec) - strtotime($work_start_time)) / 3600) / $per_day_credit;
									}
								}else{
									$return_breaktime = (strtotime($check_end_time) - strtotime($check_start_time)) / 3600;
									$tl4 = (((strtotime($date3_sec) - strtotime($work_start_time)) / 3600) - $return_breaktime) / $per_day_credit;
								}
							}
						}
						
						#$total_leave_request = $tl1 + $tl2 + $tl3; //+$tl4;
						
						if($leave_units == 'days') {
							if($cb_date1 == "PM") {
								if($cb_date2 == "PM" && $cb_date1 == "PM"){
									$total_leave_request = $tl1 + $tl2 + $tl3;
								} else {
									$total_leave_request = $tl1 + $tl3;
								
								}
							} else {
								$total_leave_request = $tl1 + $tl2 + $tl3;
							}
							
							$hours_total = $total_leave_request * $hours_worked;
						} else {
							
							if($cb_date1 == "PM") {
								if($cb_date2 == "PM" && $cb_date1 == "PM"){
									$total_leave_request = $tl1 + $tl2 + $tl3;
								} else {
									$total_leave_request = $tl1 + $tl3;
								}
							} else {
								$total_leave_request = $tl1 + $tl2 + $tl3;
							}
							
							$hours_total = $total_leave_request * $per_day_credit;
						}
						
						$minus_hours_total = $hours_total;
						$check_total_days2 = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
						$duration = 0;
						
						if(round($check_total_days2) > 0){
								
							for($cnt=0;$cnt<=$check_total_days2;$cnt++){
						
								$is_holiday_tl3 = $this->employee->get_holiday_date(date("Y-m-d",strtotime($date1." +".$cnt." day")),$this->emp_id,$this->company_id);
								$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($date1." +".$cnt." day")));
								$rest_day2 = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($date1." +".$cnt." day")));
								
								// exclude holiday
								if($exclude_holidays != 'yes'){
									$is_holiday_tl3 = false;
								}
								
								
								if(!$is_holiday_tl3 && !$rest_day2){
									$check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1." +".$cnt." day")),$work_schedule_id);
									
									if($minus_hours_total > $check_for_working_hours){
										$minus_hours_total = $minus_hours_total - $check_for_working_hours;
										$duration = $duration + 1;
									}else{
										$duration = $duration + ($minus_hours_total / $check_for_working_hours);
									}	
								}
							}
						}
						
						$duration = number_format(round($duration,2),'2','.',',');
					}
				}elseif($check_workshift != FALSE){
				    //sorry fil ako g.delete tnan split na code nmu ahahah
				    if($schedule_blocks_id == "all") {
				        #p(dateRange($start_date, $end_date));
				        $check_if_24_hrs = (strtotime($concat_end_date) - strtotime($concat_start_date)) / 3600 / 24;
				        if($check_if_24_hrs < 1) {
				            $end_date = date("Y-m-d", strtotime($end_date.' -1 day'));
				        }
				        
				        $range_applied = dateRange($start_date, $end_date);
				        
				        $total_leave_request = 0;
				        
				        foreach ($range_applied as $date) {
				            $work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date('Y-m-d', strtotime($date)));
				            $get_split_block_name = $this->employee->list_of_blocks(date('Y-m-d', strtotime($date)),$this->emp_id,$work_schedule_id,$this->company_id);
				            
				            $total_hours_work_all_block = 0;
				            if($get_split_block_name) {
				                foreach ($get_split_block_name as $row) {
				                    $total_hours_work_all_block += $row->total_hours_work_per_block;
				                }
				                
				                if($leave_units == 'days') {
				                    $overall_total_hrs = $total_hours_work_all_block / $total_hours_work_all_block;
				                } else {
				                    $overall_total_hrs = $total_hours_work_all_block / $per_day_credit;
				                }
				                
				                $total_leave_request += $overall_total_hrs;
				            }
				        }
				    } else {
				        $get_split_time_by_schedule_blocks_id = $this->employee->get_split_time_by_schedule_blocks_id($schedule_blocks_id,$this->company_id);
				        $break_in_min = 0;
				        if($get_split_time_by_schedule_blocks_id) {
				            $break_in_min = $get_split_time_by_schedule_blocks_id->break_in_min;
				        }
				        
				        $hours_worked = $this->employee->total_hours_for_all_split_blocks($start_date,$this->emp_id,$work_schedule_id,$this->company_id);
				        
				        $total_hrs = (strtotime($concat_end_date) - strtotime($concat_start_date)) / 3600 - ($break_in_min / 60);
				        
				        if($leave_units == 'days') {
				            $total_leave_request = $total_hrs / $hours_worked;
				        } else {
				            $total_leave_request = $total_hrs / $per_day_credit;
				        }
				    }
				}
						
			}
		
			print json_encode(array("total"=>number_format($total_leave_request,3)));
			return false;
		
		}
		elseif($flag == 2){
		    $employee_details = get_employee_details_by_empid($this->emp_id);
		    
		    $no_approver_msg_locked = "Payroll for the period affected is locked. No new leave requests can be accepted. Please reach out to your HR or payroll manager to discuss options for this request.";
		    $no_approver_msg_closed = "Payroll for the period affected is closed. No new leave requests can be accepted. Please reach out to your HR or payroll manager to discuss options for this request.";
		    $void = $this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($shift_date)));
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
		                'msg' => $locked,
		            );
		            
		            echo json_encode($result);
		            return false;
		        }
		    }
			
			if($check_existing_leave_applied) {
				if($check_existing_leave_applied->leave_application_status == 'approve') {
					$leave_application_status = 'Approved';
				} else {
					$leave_application_status = $check_existing_leave_applied->leave_application_status;
				}
					
				$leave_type = $this->employee->leave_type($this->company_id,$this->emp_id,$check_existing_leave_applied->leave_type_id);
			
				$leave_type_name = "";
				if($leave_type) {
					foreach ($leave_type as $hatch) {
						$leave_type_name = $hatch->leave_type;
					}
				}
					
				$result = array(
						'result' => 3,
						'existing_leave' => true,
						'leave_type' => ucwords($leave_type_name),
						'date_filed'=>idates($check_existing_leave_applied->date_filed),
						'date_start'=>date('d-M-y h:i A', strtotime($check_existing_leave_applied->date_start)),
						'date_end'=>date('d-M-y h:i A', strtotime($check_existing_leave_applied->date_end)),
						'leave_application_status'=>$leave_application_status,
						'err_msg' => 'You already have an existing leave filed for this date and time.'
				);
				echo json_encode($result);
				return false;
			}
			
			/* TOTAL LEAVE REQUESTED */
		
			/* CHECK EMPLOYEE WORK SCHEDULE */
			//$work_schedule_id = $this->employee->work_schedule_id($company_id,$emp_id);
			$currentdate = date("Y-m-d",strtotime($start_date));
			$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$currentdate);
			
			//$work_schedule_id = $this->employee->work_schedule_id($company_id,$emp_id);
			if(strtotime($start_date) > strtotime($end_date)){
				$result = array(
						'result'=>0,
						'msg'=>"Start Date must not be greater than End date"
				);
				echo json_encode($result);
				return false;
			}elseif(strtotime($start_date) == strtotime($end_date)){
				if(strtotime($concat_start_datetime) > strtotime($concat_end_datetime)){
					$result = array(
							'result'=>0,
							'msg'=>"Start Time must not be greater than End Time"
					);
					echo json_encode($result);
					return false;
				}elseif ($start_date != '' && $end_date != '') {
					if($concat_start_date == $concat_end_date){
						$result = array(
								'result'=>0,
								'msg'=>"Please, check your leave dates"
						);
						 
						echo json_encode($result);
						return false;
					}
				}
			}
			
			// check shift date that only one gap of start date or whatsoever
			$shift_date_plus_1_day = date("Y-m-d", strtotime($shift_date." +1 day"));
			if(strtotime($shift_date_plus_1_day) < strtotime($start_date)) {
			    $result = array(
			        'result'=>0,
			        'msg'=>"Please, check your leave shift date."
			    );
			    
			    echo json_encode($result);
			    return false;
			} elseif (strtotime($shift_date) > strtotime($start_date)) {
			    $result = array(
			        'result'=>0,
			        'msg'=>"Shift Date must be less than or equal to Start Date."
			    );
			    
			    echo json_encode($result);
			    return false;
			}
			
			// trap the return date
			if(strtotime($start_date) > strtotime($return_date)) {
			    $result = array(
			        'result'=>0,
			        'msg'=>"Return Date must be greater than Start date"
			    );
			    echo json_encode($result);
			    return false;
			} elseif (strtotime($end_date) > strtotime($return_date)) {
			    $result = array(
			        'result'=>0,
			        'msg'=>"Return Date must be greater than End date"
			    );
			    echo json_encode($result);
			    return false;
			}
			
			// Check : Number of days before which the leave application should be submitted
			$date_now = date('Y-m-d');
			$exact_date_to_apply = date('Y-m-d', strtotime($num_days_b4_leave.' day', strtotime($date_now)));
			$start_date_to_apply = date('Y-m-d', strtotime($start_date));
			
			if($num_days_b4_leave != 0) {
				if($start_date_to_apply < $exact_date_to_apply) {
					$result = array(
							'result' => 0,
							'msg'    => "You do not meet the required number of days for the leave application to be filed"
					);
			
					echo json_encode($result);
					return false;
						
				}
			}
			
			//Check : Maximum number of consecutive days of leave allowed
			$lv_start = $concat_start_date;
			$lv_end = $concat_end_date;
			$total_lv = 0;
			$current_date = $start_date;
			$per_day_credit = $this->prm->average_working_hours_per_day($this->company_id);
			
			$chk_total_days = ((strtotime($lv_end) - strtotime($lv_start)) / 3600 / 24);
				
			if(round($chk_total_days) > 0){ 							
 				if($cons_days == 'yes') {
 					if($num_cons_days != null || $num_cons_days != 0){
 						if($num_cons_days < $cont_tlr_hidden) {
 							$result = array(
 									'result'=>0,
 									'error'=>true,
 									'msg'=>"You have exceeded the number of consecutive days of leaves allowed by your company."
 							);
 								
 							echo json_encode($result);
 							return false;
 						}
 					}
 				}
			}
			
			// warning message if the employee file leave with in the holiday.
			$is_holiday = $this->employee->get_holiday_date($shift_date,$this->emp_id,$this->company_id);
			if($is_holiday) {
			    $result = array(
			        'result' => 0,
			        'msg' => 'The leave date you are trying to file is a holiday.'
			    );
			    echo json_encode($result);
			    return false;
			}
			
			// check for leaves limits(2) per day
			$check_greater_than_2_leaves = $this->employee->check_greater_than_2_leaves($this->emp_id, $this->company_id, date('Y-m-d', strtotime($shift_date)));
			
			if($check_greater_than_2_leaves >= 2) {
			    $result = array(
			        'result'=> 0,
			        'msg'=> "You can only file up to two leaves per day."
			    );
			    
			    echo json_encode($result);
			    return false;
			}
			
			// check for leaves within the calendar year only
			if($what_happen_to_unused_leave == "convert to cash" || $what_happen_to_unused_leave == "do nothing") {
			    $get_employee_details_by_empid = get_employee_details_by_empid($this->emp_id);
			    
			    $conversion_sched = "";
			    $this_year = date('Y');
			    if($leave_conversion_run_every == "annual") {
			        $conversion_sched = $this_year.'-12-31';
			        $conversion_sched = date('Y-m-d', strtotime($conversion_sched));
			        
			        $temp_conversion_sched = date('m-d', strtotime($conversion_sched));
			        $concat_start_date_year = date('Y', strtotime($concat_start_date)).'-'.$temp_conversion_sched;
			        $new_conversion_sched = date('Y-m-d', strtotime($concat_start_date_year));
			    } elseif ($leave_conversion_run_every == "anniversary") {
			        $date_hired = $get_employee_details_by_empid->date_hired;
			        $conversion_sched = $this_year.'-'.date('m-d', strtotime($date_hired));
			        $conversion_sched = date('Y-m-d', strtotime($conversion_sched));
			        
			        $temp_conversion_sched = date('m-d', strtotime($conversion_sched));
			        $concat_start_date_year = date('Y', strtotime($concat_end_date)).'-'.$temp_conversion_sched;
			        $new_conversion_sched = date('Y-m-d', strtotime($concat_start_date_year));
			    } elseif ($leave_conversion_run_every == "specific date") {
			        $conversion_sched = $this_year.'-'.$carry_over_schedule_specific_month.'-'.$carry_over_schedule_specific_day;
			        $conversion_sched = date('Y-m-d', strtotime($conversion_sched));
			        
			        $temp_conversion_sched = date('m-d', strtotime($conversion_sched));
			        $concat_start_date_year = date('Y', strtotime($concat_end_date)).'-'.$temp_conversion_sched;
			        $new_conversion_sched = date('Y-m-d', strtotime($concat_start_date_year));
			    }
			    
			    $concat_start_date_new = date('Y-m-d', strtotime($concat_start_date));
			    $concat_end_date_new = date('Y-m-d', strtotime($concat_end_date));
			    
			    if(strtotime($concat_start_date_new) < strtotime($conversion_sched) && strtotime($concat_end_date_new) > strtotime($conversion_sched)) {
			        $msg_date = date('M d, Y', strtotime($conversion_sched));
			        $result = array(
			            'error'=>true,
			            'ereturn_date'=>"Your leave conversion is on {$msg_date}. You cannot apply leaves that span across this date. <br>Please apply separately."
			        );
			        
			        echo json_encode($result);
			        return false;
			    } elseif (strtotime($concat_start_date_new) < strtotime($new_conversion_sched) && strtotime($concat_end_date_new) > strtotime($new_conversion_sched)) {
			        $msg_date = date('M d, Y', strtotime($new_conversion_sched));
			        $result = array(
			            'error'=>true,
			            'ereturn_date'=>"Your leave conversion is on {$msg_date}. You cannot apply leaves that span across this date. <br>Please apply separately."
			        );
			        
			        echo json_encode($result);
			        return false;
			    }
			}
			
			$tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($this->company_id,$work_schedule_id);
			
			$is_work = is_break_assumed($work_schedule_id);
			if($tardiness_rule_migrated_v3) {
			    $is_work = false;
			}
			
			$check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($start_date)));
			#$check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($shift_date)));
			
			
			$void = $this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($shift_date)));
			#$void = "Closed";
			
			if($void == "Waiting for approval"){
			    $flag_payroll_correction = "yes";
			    $disabled_btn = true;
			} elseif ($void == "Closed") {
			    $flag_payroll_correction = "yes";
			} else {
			    $flag_payroll_correction = "no";
			}
			
			if($work_schedule_id != FALSE){
			    // if one of the approver is inactive the approver group will automatically change to default (owner)
			    change_approver_to_default($this->emp_id,$this->company_id,"leave_approval_grp",$this->account_id);
			    
				/* WORK SCHEDULE ID */
				
				// check workday
				$check_workday = $this->employee->check_workday_ws($this->emp_id,$this->company_id,$work_schedule_id);
				
				$start_date_req = date("l",strtotime($start_date));
				$check_regular = $this->employee->check_regular_ws($this->emp_id,$this->company_id,$work_schedule_id,$start_date_req);
				$check_workshift = $this->employee->check_workshift_ws($this->emp_id,$this->company_id,$work_schedule_id);
				
				$date1 = $concat_start_date;
				$date2 = $concat_end_date;
				$date3_return_date = $concat_return_date;
				
				if($check_workday != FALSE){
				
					$total_leave_request = 0;
					$tl1 = 0;
					$tl2 = 0;
					$fl_bet = 0;
					
					// less than 24 hours
					$fl_time = $check_workday->latest_time_in_allowed;
					$duration_of_lunch_break_per_day = $check_workday->duration_of_lunch_break_per_day / 60;
					$fl_hours_worked = $check_workday->total_hours_for_the_day - $duration_of_lunch_break_per_day;
					$date1 = $concat_start_date;
					$date2 = $concat_end_date;
					$date3_return_date = $concat_return_date;
					
					$req_hours_work = $fl_hours_worked;
								
					if($check_workday->not_required_login == "0"){
					    // for end date
					    #$fl_time = $check_workday->latest_time_in_allowed;
					    $fl_latest_time_in_allowed = date("Y-m-d", strtotime($concat_start_date)).' '.$check_workday->latest_time_in_allowed;
					    if(strtotime($concat_start_date) > strtotime($fl_latest_time_in_allowed)) {
					        $fl_time = $check_workday->latest_time_in_allowed;
					    } else {
					        $fl_time = date("H:i:s", strtotime($concat_start_date));
					    }
					}else{
						$fl_time = date("H:i:s", strtotime($date1));
					}
					
					$fl_mins_worked = $check_workday->total_hours_for_the_day * 60;
					
					$fl_time_date = date("Y-m-d", strtotime($concat_end_date)).' '.$fl_time;
					$fl_latest_time_out_allowed = date("Y-m-d H:i:s", strtotime($fl_time_date.' +'.$fl_mins_worked.' minutes'));
					
					if(strtotime($concat_end_date) < strtotime($fl_latest_time_out_allowed)) {
					    $fl_time_end = date("H:i:s", strtotime($concat_end_date));
					} else {
					    $fl_time_end = date("H:i:s", strtotime($fl_time_date.' +'.$fl_mins_worked.' minutes'));
					}
					
					$date_timein = date("H:i:s",strtotime($date1));
					$date_timeout = date("H:i:s",strtotime($date2));
					$d3_sec = date("H:i:s",strtotime($date3_return_date));
					$check_hours = strtotime(date("Y-m-d H:i:s",strtotime($date2))) - strtotime(date("Y-m-d H:i:s",strtotime($date1)));
					$total_leave_hours = $check_hours / 3600;
					$total_hours = $check_hours / 3600 / 24;
					
					if($is_work) {
					    if($check_break_time_for_assumed) {
					        $add_date = $start_date.' '.$check_break_time_for_assumed->latest_time_in_allowed;
					        $h = $is_work->assumed_breaks * 60;
					        $lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
					        $lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_workday->duration_of_lunch_break_per_day} minutes"));
					        
					        if(strtotime($concat_end_date) >= strtotime($lunch_out) && strtotime($concat_end_date) <= strtotime($lunch_in)) {
					            $date_timeout = date("H:i:s",strtotime($lunch_in));
					            $minus_break = $duration_of_lunch_break_per_day;
					        } elseif(strtotime($concat_start_date) >= strtotime($lunch_out) && strtotime($concat_start_date) <= strtotime($lunch_in)) {
					            $date_timein = date("H:i:s",strtotime($lunch_out));
					            $minus_break = $duration_of_lunch_break_per_day;
					        } elseif (strtotime($lunch_out) > strtotime($concat_start_date) && strtotime($concat_end_date) >= strtotime($lunch_in)) {
					            $minus_break = $duration_of_lunch_break_per_day;
					        } else {
					            $minus_break = 0;
					        }
					    } else {
					        $minus_break = 0;
					    }
					} else {
					    $minus_break = $duration_of_lunch_break_per_day;
					}
					
					if($total_hours < 1){
						$cb_date2 = date("A",strtotime($date2)); // if AM CALLCENTER
						$cb_date1 = date("A",strtotime($date1)); // if PM CALLCENTER
						
						$is_holiday = $this->employee->get_holiday_date($date1,$this->emp_id,$this->company_id);
						
						// exclude holiday
						if($exclude_holidays != 'yes'){
							$is_holiday = false;
						}
						
						if(!$is_holiday){
							#$d1 = (strtotime($date_timeout) - strtotime($date_timein)) / 3600;
							if($cb_date1 == "PM") {
								$cb_date1_bogart = date("A",strtotime($date_timein));
								$cb_date2_bogart = date("A",strtotime($date_timeout));
								
								if($cb_date1_bogart == "PM" && $cb_date2_bogart == "PM") {
									#$d1 = (strtotime($date_timeout) - strtotime($date_timein)) / 3600 - $minus_break;
								    $d1 = ((strtotime($fl_time_end) - strtotime($date_timein)) / 3600) - $minus_break;
								} else {
									$cb_date1_bogart1 = date("A",strtotime($date1));
									if($cb_date1_bogart == "PM") {
										$d1 = (strtotime($date2) - strtotime($date1)) / 3600 - $minus_break;
									}
								}
							} else {
								#$d1 = (strtotime($date_timeout) - strtotime($date_timein)) / 3600 - $minus_break;
							    $d1 = ((strtotime($fl_time_end) - strtotime($date_timein)) / 3600) - $minus_break;
							}
							
							$total_leave_request =( ($d1 >= $fl_hours_worked) ? $fl_hours_worked : $d1 );
							
							$hours_total = $total_leave_request;
							$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($date1)));
							$check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id);
							$duration = ($hours_total / $check_for_working_hours);
							$duration = number_format(round($duration,2),'2','.',',');
						}
						
						if($leave_units == 'days') {
							$total_leave_request = $total_leave_request / $fl_hours_worked;
						} else {
							$total_leave_request = $total_leave_request / $per_day_credit;
						}
					}else{
						$cb_date2 = date("A",strtotime($date2)); // if AM CALLCENTER
						$cb_date1 = date("A",strtotime($date1)); // if PM CALLCENTER
						#$d2 = (strtotime($date_timeout) - strtotime($fl_time)) / 3600;
						$d2 = ((strtotime($fl_time_end) - strtotime($date_timein)) / 3600) - $minus_break;
					
						$tl1 = ($d2 >= $fl_hours_worked) ? $fl_hours_worked : $d2;
						
						/*if($cb_date2 == "AM" && $cb_date1 == "PM"){
							$check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
						}else{
							$check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
						}*/
						
						$check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
								
						if(round($check_total_days) > 0){
							$tl2 = 0;
							for($cnt=1;$cnt<=$check_total_days;$cnt++){
								$work_sched_id = check_employee_work_schedule(date("Y-m-d",strtotime($date1." +".$cnt." day")), $this->emp_id, $this->company_id)->work_schedule_id;
								$is_holiday_tl2 = $this->employee->get_holiday_date(date("Y-m-d",strtotime($date1." +".$cnt." day")),$this->emp_id,$this->company_id);
								$rest_day = $this->ews->get_rest_day($this->company_id,$work_sched_id,date("l",strtotime($date1." +".$cnt." day")));
								
								// exclude holiday
								if($exclude_holidays != 'yes'){
									$is_holiday_tl2 = false;
								}
								
								if(!$is_holiday_tl2 && !$rest_day){
									$check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1." +".$cnt." day")),$work_schedule_id);
									$tl2 += $check_for_working_hours;
								}
							}
							$tl2 = $tl2;
						}
									
						if($leave_units == 'days') {
							$total_leave_request = ($total_leave_request + $tl1 + $tl2 + $fl_bet) / $fl_hours_worked;
							$hours_total = $total_leave_request * $fl_hours_worked;
						} else {
							$total_leave_request = ($total_leave_request + $tl1 + $tl2 + $fl_bet) / $per_day_credit;
							$hours_total = $total_leave_request * $per_day_credit;
						}
									
						$minus_hours_total = $hours_total;
						$check_total_days2 = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
						$duration = 0;
								
						if(round($check_total_days2) > 0){
							for($cnt=0;$cnt<=$check_total_days2;$cnt++){
								$is_holiday_tl3 = $this->employee->get_holiday_date(date("Y-m-d",strtotime($date1." +".$cnt." day")),$this->emp_id,$this->company_id);
								$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($date1." +".$cnt." day")));
								$rest_day2 = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($date1." +".$cnt." day")));
								
								// exclude holiday
								if($exclude_holidays != 'yes'){
									$is_holiday_tl3 = false;
								}
								
								if(!$is_holiday_tl3 && !$rest_day2){
									$check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1." +".$cnt." day")),$work_schedule_id);
									if($minus_hours_total > $check_for_working_hours){
										$minus_hours_total = $minus_hours_total - $check_for_working_hours;
										$duration = $duration + 1;
									}else{
										$duration = $duration + ($minus_hours_total / $check_for_working_hours);
									}
								}
							}
						}
						$duration = number_format(round($duration,2),'2','.',',');
					}
				}elseif($check_regular != FALSE){
					foreach($check_regular as $cr){
						$req_hours_work = $cr->total_work_hours;
					}
					
					// for uniform working days, workshift
					// less than 24 hours
					$date1 = $concat_start_date;
					$date2 = $concat_end_date;
					$date3_return_date = $concat_return_date;
					
					$date_timein = date("H:i:s",strtotime($date1));
					$date_timeout = date("H:i:s",strtotime($date2));
					$check_hours = strtotime(date("Y-m-d H:i:s",strtotime($date2))) - strtotime(date("Y-m-d H:i:s",strtotime($date1)));
					$total_leave_hours = $check_hours / 3600;
					$total_hours = $check_hours / 3600 / 24;
					
					$total_leave_request = 0;
					$tl1 = 0;
					$tl2 = 0;
					$tl3 = 0;
					$tl4 = 0;
					
					// check parameter
					$check_date = strtotime(date("Y-m-d",strtotime($date1)));
					$hours_worked = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id);
					$hours_worked_break = $this->employee->for_leave_hoursworked_break($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id);
					$check_start_time = $this->employee->for_leave_breaktime_start_time_ws($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id, $concat_start_datetime);
					$check_end_time = $this->employee->for_leave_breaktime_end_time_ws($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id, $concat_start_datetime);
										
					#$work_start_time = $this->employee->for_leave_hoursworked_work_start_time_ws($this->emp_id,$this->company_id,$work_schedule_id);
					#$work_end_time = $this->employee->for_leave_hoursworked_work_end_time_ws($this->emp_id,$this->company_id,$work_schedule_id,$end_date);
					#$work_end_time = $this->employee->for_leave_hoursworked_work_end_time_ws($this->emp_id,$this->company_id,$work_schedule_id,$start_date);
					
					$work_start_time = $this->employee->for_leave_hoursworked_work_start_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,$concat_start_date);
					$work_end_time = $this->employee->for_leave_hoursworked_work_end_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,$concat_start_date);
					
					$check_date_and_time_in = $this->employee->check_date_and_time_in($shift_date, $this->emp_id, $this->company_id);
					
					if($tardiness_rule_migrated_v3) {
					    if($check_break_time_for_assumed) {
					        $get_schedule_settings = get_schedule_settings_by_workschedule_id($work_schedule_id,$this->company_id,date("l", strtotime($shift_date)));
					        $grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
					        
					        $add_datex = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$grace." minutes"));
					        $add_datey = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
					        if(strtotime($concat_start_date) > strtotime($add_datex)) {
					            $work_start_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
					            $work_end_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time));
					            
					            if($check_date_and_time_in) {
					                if (strtotime($check_date_and_time_in->time_in) >= strtotime($add_datey)) {
					                    $start_new = $check_date_and_time_in->time_in;
					                    $start_new_diff = (strtotime($add_datey) - strtotime($check_date_and_time_in->time_in)) / 60;
					                    
					                    if($start_new_diff < 0){
					                        $start_new_diff = (strtotime($check_date_and_time_in->time_in) - strtotime($add_datey)) / 60;
					                    }
					                    
					                    $add_date = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$start_new_diff." minutes"));
					                    $new_end_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
					                    
					                    if(strtotime($new_end_date) >= strtotime($concat_end_date)) {
					                        $date2 = $concat_end_date;
					                        $work_end_time = date('H:i:s', strtotime($concat_end_date));
					                    } else {
					                        $date2 = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
					                        $work_end_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
					                    }
					                }
					            } else {
					                $date2 = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time));
					                $add_date = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
					            }
					        } else {
					            if (strtotime($add_datey) >= strtotime($concat_start_date)) {
					                $add_date = $shift_date.' '.$check_break_time_for_assumed->work_start_time;
					            } elseif (strtotime($concat_start_date) >= strtotime($add_datey)) {
					                $start_new = $concat_start_date;
					                $start_new_diff = (strtotime($add_datey) - strtotime($concat_start_date)) / 60;
					                if($start_new_diff < 0){
					                    $start_new_diff = (strtotime($concat_start_date) - strtotime($add_datey)) / 60;
					                }
					                $add_date = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$start_new_diff." minutes"));
					            }
					        }
					        
					        $h = $hours_worked / 2;
					        $h = $h * 60;
					        
					        $hours_worked_break_l = 0;
					        $hours_worked_break_b1 = 0;
					        $hours_worked_break_b2 = 0;
					        # p($get_schedule_settings);
					        if($get_schedule_settings->enable_lunch_break == "yes") {
					            if($get_schedule_settings->break_schedule_1 == "fixed") {
					                if($get_schedule_settings->break_type_1 == "unpaid") {
					                    $break_started_after_in_mins = $get_schedule_settings->break_started_after * 60;
					                    $lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins} minutes"));
					                    $lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$get_schedule_settings->break_in_min} minutes"));
					                    
					                    if(strtotime($concat_start_date) < strtotime($lunch_out) && strtotime($concat_start_date) > strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                    } elseif(strtotime($concat_start_date) <= strtotime($lunch_out) && strtotime($concat_end_date) >= strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                    } elseif(strtotime($concat_start_date) >= strtotime($lunch_out) && strtotime($concat_start_date) < strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                    } elseif(strtotime($concat_end_date) > strtotime($lunch_out) && strtotime($concat_end_date) < strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                        $date2 = $lunch_in;
					                    } else {
					                        $hours_worked_break_l = 0;
					                    }
					                }
					            } elseif($get_schedule_settings->break_schedule_1 == "flexi" || $get_schedule_settings->track_break_1 == "no") {
					                if($get_schedule_settings->break_type_1 == "unpaid") {
					                    $total_hrs_of_break_mins = $get_schedule_settings->total_work_hours;
					                    $break_started_after_in_mins = ($total_hrs_of_break_mins / 2) * 3600;
					                    
					                    $lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins} seconds"));
					                    $lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$get_schedule_settings->break_in_min} minutes"));
					                    
					                    if(strtotime($concat_start_date) < strtotime($lunch_out) && strtotime($concat_start_date) > strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                    } elseif(strtotime($concat_start_date) <= strtotime($lunch_out) && strtotime($concat_end_date) >= strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                    } elseif(strtotime($concat_start_date) >= strtotime($lunch_out) && strtotime($concat_start_date) < strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                    } elseif(strtotime($concat_end_date) > strtotime($lunch_out) && strtotime($concat_end_date) < strtotime($lunch_in)) {
					                        $hours_worked_break_l = $hours_worked_break;
					                        $date2 = $lunch_in;
					                    } else {
					                        $hours_worked_break_l = 0;
					                    }
					                }
					            }
					        }
					        
					        if($get_schedule_settings->enable_additional_breaks == "yes") {
					            if($get_schedule_settings->break_schedule_2 == "fixed") {
					                if($get_schedule_settings->break_type_2 == "unpaid") {
					                    if($get_schedule_settings->num_of_additional_breaks > 0) {
					                        if($get_schedule_settings->additional_break_started_after_1 != "") {
					                            $break_started_after_in_mins = $get_schedule_settings->additional_break_started_after_1 * 60;
					                            $break_1 = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins} minutes"));
					                            $break_1_end = date('Y-m-d H:i:s',strtotime($break_1. " +{$get_schedule_settings->break_1_in_min} minutes"));
					                            
					                            if(strtotime($concat_start_date) < strtotime($break_1) && strtotime($concat_start_date) > strtotime($break_1_end)) {
					                                $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                            } elseif(strtotime($concat_start_date) <= strtotime($break_1) && strtotime($concat_end_date) >= strtotime($break_1_end)) {
					                                $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                            } elseif(strtotime($concat_start_date) >= strtotime($break_1) && strtotime($concat_start_date) < strtotime($break_1_end)) {
					                                $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                            } else {
					                                $hours_worked_break_b1 = 0;
					                            }
					                        }
					                        
					                        if($get_schedule_settings->additional_break_started_after_2 != "") {
					                            $break_started_after_in_mins = $get_schedule_settings->additional_break_started_after_2 * 60;
					                            $break_2 = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins} minutes"));
					                            $break_2_end = date('Y-m-d H:i:s',strtotime($break_2. " +{$get_schedule_settings->break_2_in_min} minutes"));
					                            
					                            if(strtotime($concat_start_date) < strtotime($break_2) && strtotime($concat_start_date) > strtotime($break_2_end)) {
					                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
					                            } elseif(strtotime($concat_start_date) <= strtotime($break_2) && strtotime($concat_end_date) >= strtotime($break_2_end)) {
					                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
					                            } elseif(strtotime($concat_start_date) >= strtotime($break_2) && strtotime($concat_start_date) < strtotime($break_2_end)) {
					                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
					                            } else {
					                                $hours_worked_break_b2 = 0;
					                            }
					                        }
					                    }
					                }
					            } elseif($get_schedule_settings->break_schedule_2 == "flexi" || $get_schedule_settings->track_break_2 == "no") {
					                if($get_schedule_settings->break_type_2 == "unpaid") {
					                    if($get_schedule_settings->num_of_additional_breaks > 0) {
					                        // first half
					                        if($get_schedule_settings->break_1_in_min != "") {
					                            if($get_schedule_settings->num_of_additional_breaks == 1) {
					                                $total_hrs_of_break_mins = $get_schedule_settings->total_work_hours;
					                                $break_started_after_in_mins = $total_hrs_of_break_mins / 2;
					                                $break_started_after_in_mins_half = ($break_started_after_in_mins / 2) * 3600;
					                                $break_started_after_in_mins_lunch = ($total_hrs_of_break_mins / 2) * 3600;
					                                
					                                // get the lunch in to come up the time of 2nd break time
					                                $lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins_lunch} seconds"));
					                                $lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$get_schedule_settings->break_in_min} minutes"));
					                                
					                                // 2nd break time
					                                $start_break_1 = date('Y-m-d H:i:s',strtotime($lunch_in. " +{$break_started_after_in_mins_half} seconds"));
					                                $end_break_1 = date('Y-m-d H:i:s',strtotime($start_break_1. " +{$get_schedule_settings->break_1_in_min} minutes"));
					                                
					                                if(strtotime($concat_start_date) < strtotime($start_break_1) && strtotime($concat_start_date) > strtotime($end_break_1)) {
					                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                                } elseif(strtotime($concat_start_date) <= strtotime($start_break_1) && strtotime($concat_end_date) >= strtotime($end_break_1)) {
					                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                                } elseif(strtotime($concat_start_date) >= strtotime($start_break_1) && strtotime($concat_start_date) < strtotime($end_break_1)) {
					                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                                } else {
					                                    $hours_worked_break_b1 = 0;
					                                }
					                            } else {
					                                $total_hrs_of_break_mins = $get_schedule_settings->total_work_hours;
					                                $break_started_after_in_mins = $total_hrs_of_break_mins / 2;
					                                $break_started_after_in_mins_half = ($break_started_after_in_mins / 2) * 3600;
					                                
					                                // first break time
					                                $start_break_1 = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins_half} seconds"));
					                                $end_break_1 = date('Y-m-d H:i:s',strtotime($start_break_1. " +{$get_schedule_settings->break_1_in_min} minutes"));
					                                
					                                if(strtotime($concat_start_date) < strtotime($start_break_1) && strtotime($concat_start_date) > strtotime($end_break_1)) {
					                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                                } elseif(strtotime($concat_start_date) <= strtotime($start_break_1) && strtotime($concat_end_date) >= strtotime($end_break_1)) {
					                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                                } elseif(strtotime($concat_start_date) >= strtotime($start_break_1) && strtotime($concat_start_date) < strtotime($end_break_1)) {
					                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
					                                } else {
					                                    $hours_worked_break_b1 = 0;
					                                }
					                            }
					                        }
					                        
					                        // second half
					                        if($get_schedule_settings->break_2_in_min != "") {
					                            $total_hrs_of_break_mins = $get_schedule_settings->total_work_hours;
					                            $break_started_after_in_mins = $total_hrs_of_break_mins / 2;
					                            $break_started_after_in_mins_half = ($break_started_after_in_mins / 2) * 3600;
					                            $break_started_after_in_mins_lunch = ($total_hrs_of_break_mins / 2) * 3600;
					                            
					                            // get the lunch in to come up the time of 2nd break time
					                            $lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins_lunch} seconds"));
					                            $lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$get_schedule_settings->break_in_min} minutes"));
					                            
					                            // 2nd break time
					                            $start_break_2 = date('Y-m-d H:i:s',strtotime($lunch_in. " +{$break_started_after_in_mins_half} seconds"));
					                            $end_break_2 = date('Y-m-d H:i:s',strtotime($start_break_2. " +{$get_schedule_settings->break_2_in_min} minutes"));
					                            
					                            if(strtotime($concat_start_date) < strtotime($start_break_2) && strtotime($concat_start_date) > strtotime($end_break_2)) {
					                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
					                            } elseif(strtotime($concat_start_date) <= strtotime($start_break_2) && strtotime($concat_end_date) >= strtotime($end_break_2)) {
					                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
					                            } elseif(strtotime($concat_start_date) >= strtotime($start_break_2) && strtotime($concat_start_date) < strtotime($end_break_2)) {
					                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
					                            } else {
					                                $hours_worked_break_b2 = 0;
					                            }
					                        }
					                    }
					                }
					            }
					        }
					        $hours_worked_break = $hours_worked_break_l + $hours_worked_break_b1 + $hours_worked_break_b2;
					    }
					}
		
					if($total_hours < 1){
						if($is_work) {
							if($check_break_time_for_assumed) {
								$grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
								
								$add_datex = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$grace." minutes"));
								$add_datey = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
								if(strtotime($concat_start_date) > strtotime($add_datex)) {
								    $work_start_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
								    $work_end_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time));
								    
									if($check_date_and_time_in) {
										if (strtotime($check_date_and_time_in->time_in) >= strtotime($add_datey)) {
											$start_new = $check_date_and_time_in->time_in;
											$start_new_diff = (strtotime($add_datey) - strtotime($check_date_and_time_in->time_in)) / 60;
											if($start_new_diff < 0){
												$start_new_diff = (strtotime($check_date_and_time_in->time_in) - strtotime($add_datey)) / 60;
											}
											$add_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$start_new_diff." minutes"));
											$new_end_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
											
											if(strtotime($new_end_date) >= strtotime($concat_end_date)) { 
												$date2 = $concat_end_date;
												$work_end_time = date('H:i:s', strtotime($concat_end_date));
											} else {
												$date2 = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
												$work_end_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
											}
										}
									} else {
										$add_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$grace." minutes"));
									}
								} else {
									if (strtotime($add_datey) >= strtotime($concat_start_date)) {
										$add_date = $start_date.' '.$check_break_time_for_assumed->work_start_time;
									} elseif (strtotime($concat_start_date) >= strtotime($add_datey)) {
										$start_new = $concat_start_date;
										$start_new_diff = (strtotime($add_datey) - strtotime($concat_start_date)) / 60;
										if($start_new_diff < 0){
											$start_new_diff = (strtotime($concat_start_date) - strtotime($add_datey)) / 60;
										}
										$add_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$start_new_diff." minutes"));
										#echo $add_date;
									}
									
								}
								
								$h = $is_work->assumed_breaks * 60;
								$lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
								$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_break_time_for_assumed->break_in_min} minutes"));
								
								if(strtotime($concat_end_date) >= strtotime($lunch_out) && strtotime($concat_end_date) <= strtotime($lunch_in)) {
								    $hours_worked_break = 0;
								    $date2 = $lunch_out;
								    $check_end_time = date('H:i:s', strtotime($lunch_in)); //$lunch_in;
								} elseif(strtotime($concat_start_date) >= strtotime($lunch_out) && strtotime($concat_start_date) <= strtotime($lunch_in)) {
								    $hours_worked_break = 0;
								    $date1 = $lunch_in;
								    $check_end_time = date('H:i:s', strtotime($lunch_in)); //$lunch_in;
								    #$date_timein = $lunch_out;
								} elseif(strtotime($concat_end_date) < strtotime($lunch_out)) {
									$hours_worked_break = 0;
								} else {
									$hours_worked_break = $hours_worked_break;
								}
							}
						} else {
						    if($check_break_time_for_assumed && !$tardiness_rule_migrated_v3) {
								$grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
							
								$add_datex = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$grace." minutes"));
								$add_datey = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
								if(strtotime($concat_start_date) > strtotime($add_datex)) {
								    $work_start_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
								    $work_end_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time));
								    
									if($check_date_and_time_in) {
										if (strtotime($check_date_and_time_in->time_in) >= strtotime($add_datey)) {
											$start_new = $check_date_and_time_in->time_in;
											$start_new_diff = (strtotime($add_datey) - strtotime($check_date_and_time_in->time_in)) / 60;
											if($start_new_diff < 0){
												$start_new_diff = (strtotime($check_date_and_time_in->time_in) - strtotime($add_datey)) / 60;
											}
											$add_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$start_new_diff." minutes"));
											$new_end_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
							
											if(strtotime($new_end_date) >= strtotime($concat_end_date)) {
												$date2 = $concat_end_date;
												$work_end_time = date('H:i:s', strtotime($concat_end_date));
											} else {
												$date2 = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
												$work_end_time = date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
											}
										}
									} else {
										$date2 = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$grace." minutes"));
										$add_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$grace." minutes"));
									}
								} else {
									if (strtotime($add_datey) >= strtotime($concat_start_date)) {
										$add_date = $start_date.' '.$check_break_time_for_assumed->work_start_time;
									} elseif (strtotime($concat_start_date) >= strtotime($add_datey)) {
										$start_new = $concat_start_date;
										$start_new_diff = (strtotime($add_datey) - strtotime($concat_start_date)) / 60;
										if($start_new_diff < 0){
											$start_new_diff = (strtotime($concat_start_date) - strtotime($add_datey)) / 60;
										}
										$add_date = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$start_new_diff." minutes"));
									}
								}
									
								$h = $hours_worked / 2;
								$h = $h * 60;
								$lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
								$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_break_time_for_assumed->break_in_min} minutes"));
									
								if(strtotime($concat_end_date) >= strtotime($lunch_out) && strtotime($concat_end_date) <= strtotime($lunch_in)) {
								    $hours_worked_break = 0;
								    $date2 = $lunch_out;
								    $check_end_time = date('H:i:s', strtotime($lunch_in)); //$lunch_in;
								} elseif(strtotime($concat_start_date) >= strtotime($lunch_out) && strtotime($concat_start_date) <= strtotime($lunch_in)) {
								    $hours_worked_break = 0;
								    $date1 = $lunch_in;
								    #$date2 = $lunch_out;
								    $check_end_time = date('H:i:s', strtotime($lunch_in)); //$lunch_in;
								    $date_timein = $lunch_out;
								} elseif(strtotime($concat_end_date) < strtotime($lunch_out)) {
									$hours_worked_break = 0;
								} else {
									#$date1 = $lunch_in;
									$hours_worked_break = $hours_worked_break;
								}
							}
						}
						
						// check for holiday
						$is_holiday = $this->employee->get_holiday_date($shift_date,$this->emp_id,$this->company_id);
						
						// exclude holiday
						if($exclude_holidays != 'yes'){
							$is_holiday = false;
						}
									
						if($is_holiday == FALSE){
							// for regular days
						    $new_check_end_time = $shift_date.' '.$check_end_time;
						    
						    if(strtotime($date2) >= strtotime($concat_end_date)) { // ako ni g.trap, idont know nganu g.ingnani pani pro libug na kaau esubay sa code lol
						        $date2 = $concat_end_date;
						    }
						    
						    if(strtotime($new_check_end_time) <= strtotime($concat_end_date)){
								$date_for_timein = date("Y-m-d",strtotime($date_timein));
								$date_for_timein_plus_one_day = date("Y-m-d",strtotime("+1 day".$date_timein));
								
								$date_timein_am_pm = date("A",strtotime($date_timein));
								$date_timein_am_pm_plus_one_day = date("A",strtotime($check_end_time));
								
								if($date_timein_am_pm == "PM" && $date_timein_am_pm_plus_one_day == "AM"){
								    if(strtotime($date_for_timein." ".$date_timein) < strtotime($date_for_timein_plus_one_day." ".$check_end_time)){
								        if($tardiness_rule_migrated_v3) {
								            if($leave_units == 'days') {
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $hours_worked;
								            } else {
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $per_day_credit;
								            }
								        } else {
								            if($leave_units == 'days') {
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id)) / $hours_worked;
								            } else {
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id)) / $per_day_credit;
								            }
								        }
								        
								    }else{
										if(strtotime($date_timeout) <= strtotime($work_end_time)){
											if($leave_units == 'days') {
												$total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600) / $hours_worked;
											} else {
												$total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600) / $per_day_credit;
											}
										}else{
											if($leave_units == 'days') {
												$total_leave_request = ((strtotime($work_end_time) - strtotime($date_timein)) / 3600) / $hours_worked;
											} else {
												$total_leave_request = ((strtotime($work_end_time) - strtotime($date_timein)) / 3600) / $per_day_credit;
											}
										}
									}
								}else{
								    if(strtotime($date_timein) < strtotime($check_end_time)){
								        if($tardiness_rule_migrated_v3) {
								            if($leave_units == 'days') {
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $hours_worked;
								            } else {
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $per_day_credit;
								            }
								        } else {
								            if($leave_units == 'days') {
								                #$total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id)) / $hours_worked;
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$shift_date.' '.$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id)) / $hours_worked;
								            } else {
								                #$total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id)) / $per_day_credit;
								                $total_leave_request = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$shift_date.' '.$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id)) / $hours_worked;
								            }
								        }
								    }else{
										if(strtotime($date_timeout) <= strtotime($work_end_time)){
											if($leave_units == 'days') {
												$total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600) / $hours_worked;
											} else {
												$total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600) / $per_day_credit;
											}
										}else{
											if($leave_units == 'days') {
												$total_leave_request = ((strtotime($work_end_time) - strtotime($date_timein)) / 3600) / $hours_worked;
											} else {
												$total_leave_request = ((strtotime($work_end_time) - strtotime($date_timein)) / 3600) / $per_day_credit;
											}
										}
									}
								}
						    }else{
						        if($tardiness_rule_migrated_v3) {
						            if($leave_units == 'days') {
						                $hour_break = floor($hours_worked_break / 60);
						                $hous_and_break = $hours_worked + $hour_break;
						                $total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600 - ($hours_worked_break / 60)) / $hous_and_break;
						            } else {
						                $total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600 - ($hours_worked_break / 60)) / $per_day_credit;
						            }
						        } else {
						            if($leave_units == 'days') {
						                $hour_break = floor($hours_worked_break / 60);
						                $hous_and_break = $hours_worked + $hour_break;
						                $total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600) / $hous_and_break;
						            } else {
						                $total_leave_request = ((strtotime($date2) - strtotime($date1)) / 3600) / $per_day_credit;
						            }
						        }
						    }
						    
							if($leave_units == 'days') {
								$hours_total = $total_leave_request * $hours_worked;
							} else {
								$hours_total = $total_leave_request * $per_day_credit;
							}
								
							$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($date1)));
							$check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id);
							$duration = ($hours_total / $check_for_working_hours);
								
							$duration = number_format(round($duration,2),'2','.',',');
						}
					}else{
						$cb_date2 = date("A",strtotime($date2)); // if AM CALLCENTER
						$cb_date1 = date("A",strtotime($date1)); // if PM CALLCENTER
						
						$date1_date = date("Y-m-d",strtotime($date1));
						$date1_sec = date("H:i:s",strtotime($date1));
						
						$date2_date = date("Y-m-d",strtotime($date2));
						$date2_sec = date("H:i:s",strtotime($date2));
						
						$date3_date = date("Y-m-d",strtotime($date3_return_date));
						$date3_sec = date("H:i:s",strtotime($date3_return_date));
						
						// total leave for start date
						$date1_date_add_oneday = date("Y-m-d",strtotime($date1_date." +1 day"));
						$is_holiday_tl1 = $this->employee->get_holiday_date($date1_date,$this->emp_id,$this->company_id);
						// exclude holiday
						if($exclude_holidays != 'yes'){
							$is_holiday_tl1 = false;
						}
									
						if(!$is_holiday_tl1){
							if($cb_date2 == "AM" && $cb_date1 == "PM"){
							    if(strtotime($date1) <= strtotime($date1_date_add_oneday." ".$check_start_time)){
							        if($tardiness_rule_migrated_v3) {
							            if($leave_units == 'days') {
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date1_date_add_oneday." ".$work_end_time,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $hours_worked;
							            } else {
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date1_date_add_oneday." ".$work_end_time,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $per_day_credit;
							            }
							        } else {
							            if($leave_units == 'days') {
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date1_date_add_oneday." ".$work_end_time,$hours_worked,$work_schedule_id)) / $hours_worked;
							            } else {
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date1_date_add_oneday." ".$work_end_time,$hours_worked,$work_schedule_id)) / $per_day_credit;
							            }
							        }
							        
							    }elseif(strtotime($date1_sec) <= strtotime($work_end_time)){
							        if($tardiness_rule_migrated_v3) {
							            if($leave_units == 'days') {
							                $tl1 = ((strtotime($work_end_time) - strtotime($date1_sec)) / 3600 - ($hours_worked_break / 60)) / $hours_worked;
							            } else {
							                $tl1 = ((strtotime($work_end_time) - strtotime($date1_sec)) / 3600 - ($hours_worked_break / 60)) / $per_day_credit;
							            }
							        } else {
							            if($leave_units == 'days') {
							                $tl1 = ((strtotime($work_end_time) - strtotime($date1_sec)) / 3600) / $hours_worked;
							            } else {
							                $tl1 = ((strtotime($work_end_time) - strtotime($date1_sec)) / 3600) / $per_day_credit;
							            }
							        }
							    }
							}else{
							    $date2 = date("Y-m-d", strtotime($shift_date))." ".$work_end_time;
							    if(strtotime($date2) >= strtotime($concat_end_date)) { // ako ni g.trap, idont know nganu g.ingnani pani pro libug na kaau esubay sa code lol
							        $date2 = $concat_end_date;
							    }
							    
							    if(strtotime($date1_sec) <= strtotime($check_start_time)){
							        if($tardiness_rule_migrated_v3) {
							            if($leave_units == 'days') {
							                #$tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$shift_date." ".$work_end_time,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $hours_worked;
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $hours_worked;
							            } else {
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id,$hours_worked_break,true)) / $per_day_credit;
							            }
							        } else {
							            if($leave_units == 'days') {
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id)) / $hours_worked;
							            } else {
							                $tl1 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date1,$check_start_time,$check_end_time,$date2,$hours_worked,$work_schedule_id)) / $per_day_credit;
							            }
							        }
							    }elseif(strtotime($date1_sec) <= strtotime($work_end_time)){
							        if($leave_units == 'days') {
							            $tl1 = ((strtotime($work_end_time) - strtotime($date1_sec)) / 3600) / $hours_worked;
							        } else {
							            $tl1 = ((strtotime($work_end_time) - strtotime($date1_sec)) / 3600) / $per_day_credit;
							        }
							    }
							}
						}
									
						// total leave for end date
						$is_holiday_tl2 = $this->employee->get_holiday_date($date2_date,$this->emp_id,$this->company_id);
						// exclude holiday
						if($exclude_holidays != 'yes'){
							$is_holiday_tl2 = false;
						}
									
						if(!$is_holiday_tl2){
							$hours_worked2 = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date2)),$work_schedule_id);
							$check_end_time2 = $this->employee->for_leave_breaktime_end_time_ws($this->emp_id,$this->company_id,date("l",strtotime($date2)),$work_schedule_id, $concat_start_datetime);
							
							$new_work_start_time = $work_start_time;
							$new_st = $this->employee->get_start_time($this->emp_id,$this->company_id,$work_schedule_id,"work_schedule");
							$new_latest_timein_allowed = $this->employee->new_latest_timein_allowed($this->emp_id,$this->company_id,$work_schedule_id,"work_schedule");
							
							if($new_latest_timein_allowed){ // if latest time in is true
							    if(strtotime($concat_start_datetime) < strtotime($new_st)){
							        $new_work_start_time = $new_st;
							    }elseif(strtotime($new_st) <= strtotime($concat_start_datetime) && strtotime($concat_start_datetime) <= strtotime($new_latest_timein_allowed)){
							        $new_work_start_time = $concat_start_datetime;
							    }elseif(strtotime($concat_start_datetime) > strtotime($new_latest_timein_allowed)){
							        #$new_work_start_time = $new_latest_timein_allowed;
							        $new_work_start_time = $work_start_time;
							    }
							}
							
							if(strtotime($check_end_time2) <= strtotime($date2_sec)){
							    $date2 = date("Y-m-d",strtotime($concat_end_date))." ".date("H:i:s", strtotime($date2));
							    if(strtotime($date2) >= strtotime($concat_end_date)) { // ako ni g.trap, idont know nganu g.ingnani pani pro libug na kaau esubay sa code lol
							        $date2 = $concat_end_date;
							    }
							    
							    if($tardiness_rule_migrated_v3) {
							        if($leave_units == 'days') {
							            #$tl2 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date2_date." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id,$hours_worked_break,true)) / $hours_worked;
							            $tl2 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,date("Y-m-d",strtotime($concat_end_date))." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id,$hours_worked_break,true)) / $hours_worked;
							        } else {
							            #$tl2 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date2_date." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id,$hours_worked_break,true)) / $per_day_credit;
							            $tl2 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,date("Y-m-d",strtotime($concat_end_date))." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id,$hours_worked_break,true)) / $per_day_credit;
							        }
							    } else {
							        if($leave_units == 'days') {
							            #$tl2= ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date2_date." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id)) / $hours_worked;
							            $tl2 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,date("Y-m-d",strtotime($concat_end_date))." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id)) / $hours_worked;
							        } else {
							            #$tl2= ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,$date2_date." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id)) / $per_day_credit;
							            $tl2 = ($this->employee->get_tot_hours_ws($this->emp_id,$this->company_id,date("Y-m-d",strtotime($concat_end_date))." ".$new_work_start_time,$check_start_time,$check_end_time,$date2,$hours_worked2,$work_schedule_id)) / $per_day_credit;
							        }
							    }
							}elseif(strtotime($date2_sec) >= strtotime($new_work_start_time)){
								if($leave_units == 'days') {
									$tl2 = ((strtotime($date2_sec) - strtotime($new_work_start_time)) / 3600) / $hours_worked;
								} else {
									$tl2 = ((strtotime($date2_sec) - strtotime($new_work_start_time)) / 3600) / $per_day_credit;
								}
							}
						}
									
						// total between dates							
						if($cb_date2 == "AM" && $cb_date1 == "PM"){
							$check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);								
						}else{
							$check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24) - 1;
						}
									
						if(round($check_total_days) > 0){
							$tl3 = 0;
							for($cnt=1;$cnt<=$check_total_days;$cnt++){
								$is_holiday_tl3 = $this->employee->get_holiday_date(date("Y-m-d",strtotime($date1." +".$cnt." day")),$this->emp_id,$this->company_id);
								// exclude holiday
								if($exclude_holidays != 'yes'){
									$is_holiday_tl3 = false;
								}
								
								if(!$is_holiday_tl3){
									$check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1." +".$cnt." day")),$work_schedule_id);
									$tl3 += $check_for_working_hours;
								}
							}
							
							if($leave_units == 'days') {
								$tl3 = $tl3 / $hours_worked;
							} else {
								$tl3 = $tl3 / $per_day_credit;
							}
						}
									
						// total return date
						$date3_date_add_oneday = date("Y-m-d",strtotime($date3_date." +1 day"));
						$is_holiday_tl4 = $this->employee->get_holiday_date($date3_date,$this->emp_id,$this->company_id);
						// exclude holiday
						if($exclude_holidays != 'yes'){
							$is_holiday_tl4 = false;
						}
						
						if(!$is_holiday_tl4){
							if($cb_date2 == "AM" && $cb_date1 == "PM"){
								if(strtotime($date3_return_date) <= strtotime($date3_date_add_oneday." ".$check_start_time)){
									$total_return_date = ((strtotime($date3_return_date) - strtotime($date3_date_add_oneday." ".$work_start_time)) / 3600) / $per_day_credit;
									if($total_return_date >= 0){
										$tl4 = ((strtotime($date3_return_date) - strtotime($date3_date_add_oneday." ".$work_start_time)) / 3600) / $per_day_credit;
									}
								}else{
									$return_breaktime = (strtotime($check_end_time) - strtotime($check_start_time)) / 3600;
									$tl4 = (((strtotime($date3_return_date) - strtotime($date3_date_add_oneday." ".$work_start_time)) / 3600) - $return_breaktime) / $per_day_credit;
								}
							}else{
								if(strtotime($date3_sec) <= strtotime($check_start_time)){
									$total_return_date = ((strtotime($date3_sec) - strtotime($work_start_time)) / 3600) / $per_day_credit;
									if($total_return_date >= 0){
										$tl4 = ((strtotime($date3_sec) - strtotime($work_start_time)) / 3600) / $per_day_credit;
									}
								}else{
									$return_breaktime = (strtotime($check_end_time) - strtotime($check_start_time)) / 3600;
									$tl4 = (((strtotime($date3_sec) - strtotime($work_start_time)) / 3600) - $return_breaktime) / $per_day_credit;
								}
							}
						}
						
						#$total_leave_request = $tl1 + $tl2 + $tl3; //+$tl4;
									
						if($leave_units == 'days') {
							if($cb_date1 == "PM") {
								if($cb_date2 == "PM" && $cb_date1 == "PM"){
									$total_leave_request = $tl1 + $tl2 + $tl3;
								} else {
									$total_leave_request = $tl1 + $tl3;
								}
							} else {
								$total_leave_request = $tl1 + $tl2 + $tl3;
							}
							$hours_total = $total_leave_request * $hours_worked;
						} else {
							if($cb_date1 == "PM") {
								if($cb_date2 == "PM" && $cb_date1 == "PM"){
									$total_leave_request = $tl1 + $tl2 + $tl3;
								} else {
									$total_leave_request = $tl1 + $tl3;
								}
							} else {
								$total_leave_request = $tl1 + $tl2 + $tl3;
							}
							$hours_total = $total_leave_request * $per_day_credit;
						}
									
						$minus_hours_total = $hours_total;
						$check_total_days2 = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
						$duration = 0;
									
						if(round($check_total_days2) > 0){
							for($cnt=0;$cnt<=$check_total_days2;$cnt++){
								$is_holiday_tl3 = $this->employee->get_holiday_date(date("Y-m-d",strtotime($date1." +".$cnt." day")),$this->emp_id,$this->company_id);
								$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($date1." +".$cnt." day")));
								$rest_day2 = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($date1." +".$cnt." day")));
								
								// exclude holiday
								if($exclude_holidays != 'yes'){
									$is_holiday_tl3 = false;
								}
								
								if(!$is_holiday_tl3 && !$rest_day2){
									$check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1." +".$cnt." day")),$work_schedule_id);
									if($minus_hours_total > $check_for_working_hours){
										$minus_hours_total = $minus_hours_total - $check_for_working_hours;
						
										$duration = $duration + 1;
									}else{
										$duration = $duration + ($minus_hours_total / $check_for_working_hours);
									}
								}
							}
						}
						$duration = number_format(round($duration,2),'2','.',',');
					}
				}elseif($check_workshift != FALSE){
				    $result = array(
				        'result'=>0,
				        'msg'=>"Sorry app does not support split schedules yet. Please use the desktop browser instead."
				    );
				    echo json_encode($result);
				    return false;
				    exit();
				    //sorry fil ako g.delete tnan split na code nmu ahahah
				    if($schedule_blocks_id == "all") {
				        $check_if_24_hrs = (strtotime($concat_end_date) - strtotime($concat_start_date)) / 3600 / 24;
				        if($check_if_24_hrs < 1) {
				            $end_date = date("Y-m-d", strtotime($end_date.' -1 day'));
				        }
				        
				        $range_applied = dateRange($start_date, $end_date);
				        
				        $total_leave_request = 0;
				        $total_hours_work_all_block = 0;
				        $overall_total_hrs = 0;
				        foreach ($range_applied as $date) {
				            $work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date('Y-m-d', strtotime($date)));
				            $get_split_block_name = $this->employee->list_of_blocks(date('Y-m-d', strtotime($date)),$this->emp_id,$work_schedule_id,$this->company_id);
				            
				            if($get_split_block_name) {
				                foreach ($get_split_block_name as $row) {
				                    $total_hours_work_all_block += $row->total_hours_work_per_block;
				                }
				                
				                if($leave_units == 'days') {
				                    $overall_total_hrs = $total_hours_work_all_block / $total_hours_work_all_block;
				                    $total_leave_request += $overall_total_hrs;
				                } else {
				                    $overall_total_hrs = $total_hours_work_all_block / $per_day_credit;
				                    $total_leave_request = $overall_total_hrs;
				                }
				                
				                $overall_total_hrs = $overall_total_hrs;
				                $split_first_sched = reset($get_split_block_name);
				                $split_last_sched = max($get_split_block_name);
				            }
				            
				        }
				        
				        #$fl_time = date("H:i:s", strtotime($split_last_sched->start_time));
				        #$date_timeout = date("H:i:s", strtotime($concat_end_date));
				        $fl_hours_worked = ($get_split_block_name) ? $split_first_sched->total_hours_work_per_block + $split_last_sched->total_hours_work_per_block : 0;
				        $last_block_hours = ($get_split_block_name) ? $split_first_sched->total_hours_work_per_block + $split_last_sched->total_hours_work_per_block : 0;
				    } else {
				        $get_split_time_by_schedule_blocks_id = $this->employee->get_split_time_by_schedule_blocks_id($schedule_blocks_id,$this->company_id);
				        $break_in_min = 0;
				        if($get_split_time_by_schedule_blocks_id) {
				            $break_in_min = $get_split_time_by_schedule_blocks_id->break_in_min;
				        }
				        
				        $hours_worked = $this->employee->total_hours_for_all_split_blocks($start_date,$this->emp_id,$work_schedule_id,$this->company_id);
				        
				        $total_hrs = (strtotime($concat_end_date) - strtotime($concat_start_date)) / 3600 - ($break_in_min / 60);
				        
				        if($leave_units == 'days') {
				            $total_leave_request = $total_hrs / $hours_worked;
				        } else {
				            $total_leave_request = $total_hrs / $per_day_credit;
				        }
				        
				        $fl_hours_worked = $hours_worked;
				        $last_block_hours = $hours_worked;
				        #$fl_time = date("H:i:s", strtotime($concat_start_date));
				        #$date_timeout = date("H:i:s", strtotime($concat_end_date));
				    }
				    
				    $duration = number_format(round($total_leave_request,2),'2','.',',');
				}
			}
			
			// check restriction halfday is not allowed
			$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($concat_start_date)));
			$req_hours_work = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($concat_start_date)),$work_schedule_id) / 2;
			
			$err_mess = 0;
			$limit_res = '';
			$hd_res = '';
			if($halfday_rest != 'yes'){
				if($total_leave_hours < $req_hours_work){
					$hd_res = "Half day leave has been diabled by your company. You cannot apply for half day leave.";
					echo json_encode(array(
							'result'	=> 0,
							'msg'		=> $hd_res
					));
					
					exit();
				}
			}
			
			if($paid_leave == 'yes') {
				if($apply_limit_rest == 'no'){
					$check_leave_balance = $this->employee->leave_type($this->company_id,$this->emp_id,$leave_type);
						
					if($check_leave_balance){
						foreach($check_leave_balance as $clb){
							$remaining = $clb->remaining_leave_credits;
							if($remaining == ''){
								$remaining = $clb->leave_credits;
							}
						}
						if($total_leave_request > $remaining){
							$limit_res = "You cannot apply leaves beyond the allowed limit alloted by your company.";
							echo json_encode(array(
									'result'	=> 0,
									'msg'		=> $limit_res
							));
							
							exit();
						}
					}
				}
			}
			
			if($effective_start_date_by != null && $effective_start_date != null) {
				if(date('Y-m-d', strtotime($concat_start_date)) >= $eff_date) {
					$total_leave_request_save = number_format($total_leave_request,'3','.',',');
					$leave_cedits_for_untitled = 0;
				} else {
					$total_leave_request_save = 0;
					$leave_cedits_for_untitled = number_format($total_leave_request,'3','.',',');
				}
			} else {
				$total_leave_request_save = number_format($total_leave_request,'3','.',',');
				$leave_cedits_for_untitled = 0;
			}
	
			$save_employee_leave = array(
			    "shift_date"                 => $shift_date,
			    "work_schedule_id"           => $work_schedule_id,
			    "company_id"                 => $this->company_id,
			    "emp_id"                     => $this->emp_id,
			    "date_filed"                 => date("Y-m-d"),
			    "leave_type_id"              => $leave_type,
			    "reasons"                    => $reason,
			    "date_start"                 => $concat_start_date,
			    "date_end"                   => $concat_end_date,
			    "date_return"                => $concat_return_date,
			    "total_leave_requested"      => $total_leave_request_save,
			    "leave_cedits_for_untitled"  => $leave_cedits_for_untitled,
			    "duration"                   => $duration,
			    "note"                       => "",
			    "leave_application_status"   => "pending",
			    "attachments"                => "",
			    "previous_credits"           => $previous_credits,
			    "flag_payroll_correction"    => $flag_payroll_correction
			);
	
			$name = ucwords($this->employee->get_approver_name($this->emp_id,$this->company_id)->first_name);
			$email = $this->employee->get_approver_name($this->emp_id,$this->company_id)->email; 
	
			// save employee leave application
			$insert_employee_leave = $this->jmodel->insert_data('employee_leaves_application',$save_employee_leave);

			// view last row for leave application
			$view_last_row_leave_application = $this->employee->last_row_leave_app($this->emp_id,$this->company_id,$leave_type);
			
			// leave V3 ni fil - start
			$leave_credits = $this->employee->leave_type($this->company_id,$this->emp_id, $leave_type);
			foreach ($leave_credits as $lc){
				$leave_credits = ($lc->remaining_leave_credits != "") ? $lc->remaining_leave_credits : $lc->leave_credits;
			}
			
			if($check_workday != FALSE){
				if($check_workday->not_required_login == "0"){
					// for end date
					#$fl_time = $check_workday->latest_time_in_allowed;
					
				    $fl_latest_time_in_allowed = date("Y-m-d", strtotime($concat_start_date)).' '.$check_workday->latest_time_in_allowed;
				    
				    if(strtotime($concat_start_date) > strtotime($fl_latest_time_in_allowed)) {
				        $fl_time = $check_workday->latest_time_in_allowed;
				    } else {
				        $fl_time = date("H:i:s", strtotime($concat_start_date));
				    }
				}else{
					$fl_time = date("H:i:s", strtotime($date1));
				}
				
				$fl_mins_worked = $check_workday->total_hours_for_the_day * 60;
				
				$fl_time_date = date("Y-m-d", strtotime($concat_end_date)).' '.$fl_time;
				$fl_latest_time_out_allowed = date("Y-m-d H:i:s", strtotime($fl_time_date.' +'.$fl_mins_worked.' minutes'));
				
				if(strtotime($concat_end_date) < strtotime($fl_latest_time_out_allowed)) {
				    $fl_time_end = date("H:i:s", strtotime($concat_end_date));
				} else {
				    $fl_time_end = date("H:i:s", strtotime($fl_time_date.' +'.$fl_mins_worked.' minutes'));
				}
				
				$duration_of_lunch_break_per_day_c = $check_workday->duration_of_lunch_break_per_day;
				$duration_of_lunch_break_per_day_c = ($duration_of_lunch_break_per_day_c) ? $duration_of_lunch_break_per_day_c / 60 : 0;
				$fl_hours_worked = $check_workday->total_hours_for_the_day - $duration_of_lunch_break_per_day_c;
				
				$duration_of_lunch_break_per_day = $check_workday->duration_of_lunch_break_per_day / 60;
				
				if($is_work) {
				    if($check_break_time_for_assumed) {
				        $add_date = $start_date.' '.$check_break_time_for_assumed->latest_time_in_allowed;
				        $h = $is_work->assumed_breaks * 60;
				        $lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
				        $lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_workday->duration_of_lunch_break_per_day} minutes"));
				        
				        if(strtotime($concat_end_date) >= strtotime($lunch_out) && strtotime($concat_end_date) <= strtotime($lunch_in)) {
				            $date_timeout = date("H:i:s",strtotime($lunch_in));
				            $minus_break = $duration_of_lunch_break_per_day;
				        } elseif(strtotime($concat_start_date) >= strtotime($lunch_out) && strtotime($concat_start_date) <= strtotime($lunch_in)) {
				            $date_timein = date("H:i:s",strtotime($lunch_out));
				            $minus_break = $duration_of_lunch_break_per_day;
				        } elseif (strtotime($lunch_out) > strtotime($concat_start_date) && strtotime($concat_end_date) >= strtotime($lunch_in)) {
				            $minus_break = $duration_of_lunch_break_per_day;
				        } else {
				            $minus_break = 0;
				        }
				    } else {
				        $minus_break = 0;
				    }
				} else {
				    $minus_break = $duration_of_lunch_break_per_day;
				}
				
			}elseif($check_regular != FALSE){
				$new_st = $this->employee->get_start_time($this->emp_id,$this->company_id,$work_schedule_id,"work_schedule");
				$new_latest_timein_allowed = $this->employee->new_latest_timein_allowed($this->emp_id,$this->company_id,$work_schedule_id,"work_schedule");
				$fl_time = $this->employee->for_leave_hoursworked_work_start_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,$concat_start_date); //$this->employee->for_leave_hoursworked_work_start_time_ws($this->emp_id,$this->company_id,$work_schedule_id);
			
				if($new_latest_timein_allowed){ // if latest time in is true
					if(strtotime($concat_start_datetime) < strtotime($new_st)){
						$fl_time = $new_st;
					}elseif(strtotime($new_st) <= strtotime($concat_start_datetime) && strtotime($concat_start_datetime) <= strtotime($new_latest_timein_allowed)){
						$fl_time = $concat_start_datetime;
					}elseif(strtotime($concat_start_datetime) > strtotime($new_latest_timein_allowed)){
						$fl_time = $new_latest_timein_allowed;
					}
				}
				
				$date_timeout = $this->employee->for_leave_hoursworked_work_end_time_ws($this->emp_id,$this->company_id,$work_schedule_id);
				
				$_timeout = date("H:i:s",strtotime($date2));
				$_timein = date("H:i:s",strtotime($date1));
				$fl_hours_worked = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id);
				$date_timeout = ($date_timeout >= $_timeout) ? $_timeout : $date_timeout;
				
				$fl_time_end = $date_timeout;
				
				$minus_break = 0;
			}
				
			$cb_date2 = date("A",strtotime($date2)); // if AM CALLCENTER
			$cb_date1 = date("A",strtotime($date1)); // if PM CALLCENTER
			
			if($cb_date2 == "AM" && $cb_date1 == "PM"){
				$check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
			}else{
				$check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
			}
				
			if(round($check_total_days) > 0){
				$tl2 = 0;
				$credited = 0;
				$non_credited = 0;
				for($cnt=1;$cnt<=$check_total_days;$cnt++){
					$temp_date = date("Y-m-d",strtotime($date1." +".$cnt." day"));
					$work_sched_id = check_employee_work_schedule(date("Y-m-d",strtotime($temp_date." -1 day")), $this->emp_id, $this->company_id)->work_schedule_id;
					$is_holiday_tl2 = $this->employee->get_holiday_date(date("Y-m-d",strtotime($temp_date." -1 day")),$this->emp_id,$this->company_id);
					$rest_day = $this->ews->get_rest_day($this->company_id,$work_sched_id,date("l",strtotime($temp_date." -1 day")));
			
					// exclude holiday
					if($exclude_holidays != 'yes'){
						$is_holiday_tl2 = false;
					}
			
					if(!$is_holiday_tl2 && !$rest_day){
					    if($check_workshift != FALSE){
					        $work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date('Y-m-d', strtotime($temp_date." -1 day")));
					        $get_split_block_name = $this->employee->list_of_blocks(date('Y-m-d', strtotime($temp_date." -1 day")),$this->emp_id,$work_schedule_id,$this->company_id);
					        
					        $total_leave_request = 0;
					        $total_hours_work_all_block = 0;
					        $overall_total_hrs = 0;
					        
					        if($get_split_block_name) {
					            foreach ($get_split_block_name as $row) {
					                $total_hours_work_all_block += $row->total_hours_work_per_block;
					            }
					            
					            if($leave_units == 'days') {
					                $overall_total_hrs = $total_hours_work_all_block / $total_hours_work_all_block;
					            } else {
					                $overall_total_hrs = $total_hours_work_all_block / $per_day_credit;
					            }
					        }
					        
					        $used_credits = $overall_total_hrs;
					        $tl2 += $used_credits;
					    } else {
					        $check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($temp_date." -1 day")),$work_schedule_id);
					        $tl2 += $check_for_working_hours;
					        
					        if($leave_units == "days"){
					            $used_credits = $check_for_working_hours / $check_for_working_hours;
					        }else{
					            $used_credits = $check_for_working_hours / $per_day_credit;
					        }
					    }
						
						if($effective_start_date_by != null && $effective_start_date != null) {
							if(date('Y-m-d', strtotime($concat_start_date)) >= $eff_date) {
								if($used_credits > $leave_credits){
									$credited = $leave_credits;
									$non_credited = $used_credits - $leave_credits;
								}elseif($used_credits <= $leave_credits){
									$credited = $used_credits;
								}
							} else {
								$credited = 0;
								$non_credited = 0;
							}
						} else {
							if($used_credits > $leave_credits){
								$credited = $leave_credits;
								$non_credited = $used_credits - $leave_credits;
							}elseif($used_credits <= $leave_credits){
								$credited = $used_credits;
							}
						}
							
						$each_leave = array(
						    "shift_date"                 => date("Y-m-d",strtotime($temp_date." -1 day")),
						    "work_schedule_id"           => $work_schedule_id,
						    "company_id"                 => $this->company_id,
						    "emp_id"                     => $this->emp_id,
						    "leave_type_id"              => $leave_type,
						    "reasons"                    => $reason,
						    "date_start"                 => date("Y-m-d",strtotime($temp_date." -1 day")),
						    "date_end"                   => date("Y-m-d",strtotime($temp_date." -1 day")),
						    "leave_application_status"   => "pending",
						    "credited"                   => $credited,
						    "non_credited"               => $non_credited,
						    "leaves_id"                  => $view_last_row_leave_application->employee_leaves_application_id,
						    "previous_credits"           => $previous_credits,
						    "flag_payroll_correction"    => $flag_payroll_correction
						    
						);
						
						$this->db->insert("employee_leaves_application", $each_leave);
						$leave_credits = $leave_credits - $used_credits;
					}						
				}
				
				if($check_workshift != FALSE){
				    $tl1 = $last_block_hours;
				    if(date("A",strtotime($date2)) == 'PM') {
				        $last_date = date("Y-m-d",strtotime($date2));
				    } else {
				        $last_date = date("Y-m-d",strtotime($date2." -1 day"));
				    }
				} else {
				    #$d2 = (strtotime($date_timeout) - strtotime($fl_time)) / 3600;
				    $d2 = (strtotime($fl_time_end) - strtotime($fl_time)) / 3600 - $minus_break;
				    
				    if(date("A",strtotime($date2)) == 'PM') {
				        $tl1 = ($d2 >= $fl_hours_worked) ? $fl_hours_worked : $d2 ;
				        $last_date = date("Y-m-d",strtotime($date2));
				    } else {
				        $last_date = date("Y-m-d",strtotime($date2." -1 day"));
				        $tl1 = ($d2 >= $fl_hours_worked) ? $d2 : $fl_hours_worked;
				    }
				}
				
				if($leave_units == "days"){
					$used_credits = $tl1 / $fl_hours_worked;
				}else{
					$used_credits = $tl1 / $per_day_credit;
				}
					
				if($effective_start_date_by != null && $effective_start_date != null) {
					if(date('Y-m-d', strtotime($concat_start_date)) >= $eff_date) {
						if($used_credits > $leave_credits){
							$credited = $leave_credits;
							$non_credited = $used_credits - $leave_credits;
						}elseif($used_credits <= $leave_credits){
							$credited =  $used_credits;
						}
					} else {
						$credited = 0;
						$non_credited = 0;
					}
				} else {
					if($used_credits > $leave_credits){
						$credited = $leave_credits;
						$non_credited = $used_credits - $leave_credits;
					}elseif($used_credits <= $leave_credits){
						$credited =  $used_credits;
					}
				}
				
				$each_leave = array(
				    "shift_date"               => date("Y-m-d",strtotime($last_date)),
				    "work_schedule_id"         => $work_schedule_id,
				    "company_id"               => $this->company_id,
				    "emp_id"                   => $this->emp_id,
				    "leave_type_id"            => $leave_type,
				    "reasons"                  => $reason,
				    "date_start"               => $last_date,
				    "date_end"                 => $last_date,
				    "leave_application_status" => "pending",
				    "credited"                 => $credited,
				    "non_credited"             => $non_credited,
				    "leaves_id"                => $view_last_row_leave_application->employee_leaves_application_id,
				    "previous_credits"         => $previous_credits,
				    "flag_payroll_correction"  => $flag_payroll_correction
				    
				);
				
				$this->db->insert("employee_leaves_application", $each_leave);
				$update_data = array('flag_parent'=>'yes');
				$this->db->where('employee_leaves_application_id',$view_last_row_leave_application->employee_leaves_application_id);
				$this->db->update("employee_leaves_application",$update_data);
			}else{
				$credited = 0;
				$non_credited = 0;
				
				$update_data = array('leaves_id'=>$view_last_row_leave_application->employee_leaves_application_id);
				$this->db->where('employee_leaves_application_id',$view_last_row_leave_application->employee_leaves_application_id);
				$this->db->update("employee_leaves_application",$update_data);
					
				$update_data2 = array('flag_parent'=>'no');
				$this->db->where('employee_leaves_application_id',$view_last_row_leave_application->employee_leaves_application_id);
				$this->db->update("employee_leaves_application",$update_data2);
			
			}
			
			//start_suwat_suwat
			$date1 = $concat_start_date;
			$date2 = $concat_end_date;
			$check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
			
			if(round($check_total_days) > 0){
				$work_sched_id = check_employee_work_schedule(date("Y-m-d",strtotime($date1)), $this->emp_id, $this->company_id)->work_schedule_id;
				$is_holiday_tl2 = $this->employee->get_holiday_date(date("Y-m-d",strtotime($date1)),$this->emp_id,$this->company_id);
				$rest_day = $this->ews->get_rest_day($this->company_id,$work_sched_id,date("l",strtotime($date1)));

				$tl2 = 0;
				for($cnt=1;$cnt<=$check_total_days;$cnt++){
					$work_sched_id = check_employee_work_schedule(date("Y-m-d",strtotime($date1." +".$cnt." day")), $this->emp_id, $this->company_id)->work_schedule_id;
					$is_holiday_tl2 = $this->employee->get_holiday_date(date("Y-m-d",strtotime($date1." +".$cnt." day")),$this->emp_id,$this->company_id);
					$rest_day = $this->ews->get_rest_day($this->company_id,$work_sched_id,date("l",strtotime($date1." +".$cnt." day")));
					
					// exclude holiday
					if($exclude_holidays != 'yes'){
						$is_holiday_tl2 = false;
					}
					
					if(!$is_holiday_tl2 && !$rest_day){
						$check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1." +".$cnt." day")),$work_schedule_id);
						$credit = $check_for_working_hours / $per_day_credit;
					}
						
				}
			}
			//end_suwat_suwat
			
			// send email notification to approver
			$leave_info = $this->agm->leave_information($view_last_row_leave_application->employee_leaves_application_id);
			$leave_approver = $this->agm->get_approver_name_leave($this->emp_id,$this->company_id);
			$fullname = ucfirst($leave_info->first_name)." ".ucfirst($leave_info->last_name);
			$psa_id = $this->session->userdata('psa_id');
			$val = $view_last_row_leave_application->employee_leaves_application_id;
			$str = 'abcdefghijk123456789';
			$shuffled = str_shuffle($str);
			
			// generate token level
			$str2 = 'ABCDEFG1234567890';
			$shuffled2 = str_shuffle($str2);
			
			$get_approval_settings_disable_status = $this->employee->get_approval_settings_disable_status($this->company_id);
			
			$approver_id = $this->employee->get_approver_name($this->emp_id,$this->company_id)->leave_approval_grp;
			if($approver_id == "" || $approver_id == 0) {
			    // Employee with no approver will use default workflow approval
			    add_workflow_approval_default_group($this->company_id,$this->account_id); // create default if dont have any
			    $approver_id = get_app_default_approver($this->company_id,"Leave")->approval_groups_via_groups_id;
			}
			
			$workforce_notification = get_notify_settings($approver_id, $this->company_id);
			
			if($approver_id) {
				if(is_workflow_enabled($this->company_id)){
					if($leave_approver){
					    $last_level = 1; //$this->leave->get_leave_last_level($leave_info->emp_id, $leave_info->company_id);
					    $new_level = 1;
					    $lflag = 0;
					    
						// without leveling
						if($workforce_notification){
							foreach ($leave_approver as $la){
								$appovers_id = ($la->emp_id) ? $la->emp_id : "-99{$this->company_id}";
								$get_approval_group_via_groups_owner = $this->agm->get_approval_group_via_groups_owner($la->approval_process_id, $la->company_id, $la->approval_groups_via_groups_id,$appovers_id);
								
								if($get_approval_group_via_groups_owner->emp_id == "-99{$this->company_id}"){
									$owner_approver = get_approver_owner_info($this->company_id);
									$appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
									$appr_account_id = $owner_approver->account_id;
									$appr_email = $owner_approver->email;
									$appr_id = "-99{$this->company_id}";
								} else {
									$appr_name = ucwords($la->first_name." ".$la->last_name);
									$appr_account_id = $la->account_id;
									$appr_email = $la->email;
									$appr_id = $la->emp_id;
								}
								
								if($la->level == $new_level){
									// send with link
									$this->send_leave_notifcation($shuffled, $view_last_row_leave_application->employee_leaves_application_id, $this->company_id, $leave_info->emp_id, $appr_email, $appr_name, "", "Approver", "Yes", $shuffled2, $appr_id);
									
									if($workforce_notification->sms_notification == "yes"){
										$url = base_url()."approval/leave/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
										$sms_message = "Click {$url} to approve {$fullname}'s leave.";
										send_this_sms_global($this->company_id, $appr_account_id,$sms_message,$psa_id,false);
									}
									if($workforce_notification->twitter_notification == "yes"){
										$check_twitter_acount = $this->agm->check_twitter_acount($appr_account_id);
										if($check_twitter_acount){
											$token = $this->leave->get_token($val, $this->company_id, $leave_info->emp_id);
											$url = base_url()."approval/leave/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
											$message = "A leave application has been filed by {$fullname} and is now waiting for your approval. Click this link {$url} to approve.";
											$recipient_account = $check_twitter_acount->twitter;
											$this->tweetontwitter($this->emp_id,$message,$recipient_account);
										}
									}
									if($workforce_notification->facebook_notification == "yes"){
										// coming soon
									}
									if($workforce_notification->message_board_notification == "yes"){
										$token = $this->leave->get_token($val, $this->company_id, $leave_info->emp_id);
										$url = base_url()."approval/leave/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
										$next_appr_notif_message = "A leave application below has been filed by {$fullname} and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
										send_to_message_board($psa_id, $appr_id, $this->emp_id, $this->company_id, $next_appr_notif_message, "system","warning");
									}
									
								}else{
									// send without link
									$this->send_leave_notifcation($shuffled, $view_last_row_leave_application->employee_leaves_application_id, $this->company_id, $leave_info->emp_id, $appr_email, $appr_name, "", "", "", "","");
									if($workforce_notification->sms_notification == "yes"){
										$sms_message = "A leave application has been filed by {$fullname}.";
										send_this_sms_global($this->company_id, $appr_account_id,$sms_message,$psa_id,false);
									}
									if($workforce_notification->twitter_notification == "yes"){
										$check_twitter_acount = $this->agm->check_twitter_acount($appr_account_id);
										if($check_twitter_acount){
											$message = "A leave application has been filed by {$fullname}.";
											$recipient_account = $check_twitter_acount->twitter;
											$this->tweetontwitter($this->emp_id,$message,$recipient_account);
										}
									}
									if($workforce_notification->facebook_notification == "yes"){
										// coming soon
									}
									if($workforce_notification->message_board_notification == "yes"){
										$next_appr_notif_message = "A leave application has been filed by {$fullname}.";
										send_to_message_board($psa_id, $appr_id, $this->emp_id,$this->company_id, $next_appr_notif_message, "system","warning");
									}
								}
							}
							
							################################ notify payroll admin start ################################
							if($workforce_notification->notify_payroll_admin == "yes"){
							    // HRs
							    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($psa_id);
							    if($payroll_admin_hr){
							        foreach ($payroll_admin_hr as $pahr){
							            $pahr_email = $pahr->email;
							            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);
							            
							            $this->send_leave_notifcation($shuffled, $view_last_row_leave_application->employee_leaves_application_id, $this->company_id, $leave_info->emp_id, $pahr_email, $pahr_name, "", "", "", "","");
							        }
							    }
							    
							    // Owner
							    $pa_owner = get_approver_owner_info($this->company_id);
							    if($pa_owner){
							        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
							        $pa_owner_email = $pa_owner->email;
							        $pa_owner_account_id = $pa_owner->account_id;
							        
							        $this->send_leave_notifcation($shuffled, $view_last_row_leave_application->employee_leaves_application_id, $this->company_id, $leave_info->emp_id, $pa_owner_email, $pa_owner_name, "", "", "", "","");
							    }
							}
							################################ notify payroll admin end ################################
						}
						
						$save_token = array(
						    "leave_id"               => $view_last_row_leave_application->employee_leaves_application_id,
						    "token"                  => $shuffled,
						    "comp_id"                => $this->company_id,
						    "emp_id"                 => $this->emp_id,
						    "approver_id"            => $approver_id,
						    "level"                  => $new_level,
						    "token_level"            => $shuffled2,
						    "date_approved_level"    => date('Y-m-d H:i:s'),
						    "date_reminder_level"    => date('Y-m-d H:i:s')
						);
						
						$save_token_q = $this->db->insert("approval_leave",$save_token);
						
						if($insert_employee_leave ){
							$result = array(
								'result'	=> 1,
								'error'		=> false,
								'msg'		=> "Your leave application has been submitted and pending approval"
							);
							echo json_encode($result);
							return false;
						}
					}else{
						$new_level = 1;
							
						$save_token = array(
						    "leave_id"               => $view_last_row_leave_application->employee_leaves_application_id,
						    "token"                  => $shuffled,
						    "comp_id"                => $this->company_id,
						    "emp_id"                 => $this->emp_id,
						    "approver_id"            => $approver_id,
						    "level"                  => $new_level,
						    "token_level"            => $shuffled2,
						    "date_approved_level"    => date('Y-m-d H:i:s'),
						    "date_reminder_level"    => date('Y-m-d H:i:s')
						);
							
						$save_token_q = $this->db->insert("approval_leave",$save_token);
						$approver_error = "Your leave application has been submitted and pending approval";
						$result = array(
								'result'	=> 1,
								'error'		=> false,
								'msg'		=> $approver_error
						);
						echo json_encode($result);
						return false;
					}
					
				} else {
					if($get_approval_settings_disable_status->status == "Inactive") {
						$value1 = array(
								"approve_by_hr" => "Yes",
								"approve_by_head" => "Yes"
						);
						$w1 = array(
								"leave_id" => $view_last_row_leave_application->employee_leaves_application_id,
								"comp_id" => $this->company_id
						);
						$this->db->where($w1);
						$this->db->update("approval_leave",$value1);
						$this->leave->update_employee_leaves($this->company_id, $this->emp_id, $leave_type, floatval($view_last_row_leave_application->total_leave_requested), $val);
						
						$result = array(
								'result'	=> 1,
								'error'		=> false,
								'msg'		=> "Your leave application has been submitted."
						);
						
						echo json_encode($result);
						return false;
					}
				}
			}else{
			    // gi delete ni ky g.pausab nsd ni donna, wala na dapat auto approve.. (Employee with no approver will use default workflow approval)
			    /*$value1 = array(
			     "approve_by_hr" => "Yes",
			     "approve_by_head" => "Yes"
			     );
			     $w1 = array(
			     "leave_id" => $view_last_row_leave_application->employee_leaves_application_id,
			     "comp_id" => $this->company_id
			     );
			     $this->db->where($w1);
			     $this->db->update("approval_leave",$value1);
			     $this->leave->update_employee_leaves($this->company_id, $this->emp_id, $leave_type, floatval($view_last_row_leave_application->total_leave_requested), $val);
			     
			     $result = array(
			     'error'             => false,
			     'approver_error'    => ""
			     );
			     
			     echo json_encode($result);
			     return false;*/
			}

 		}
	}
	
	public function check_work_schedule () {
	    $employee_timein_date = date("Y-m-d",strtotime($this->input->post('shift_date')));
	    
	    // get the first and last blocks 
	    $work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$employee_timein_date);
	    $get_split_block_name = $this->employee->list_of_blocks($employee_timein_date,$this->emp_id,$work_schedule_id,$this->company_id);
	    $approver_id = $this->employee->get_approver_name($this->emp_id,$this->company_id)->leave_approval_grp;
	    
	    $this->form_validation->set_rules("shift_date", 'Shift Date', 'trim|required|xss_clean');
	    
	    if ($this->form_validation->run()==true){
	        $html = "";
	        $html1 = "";
	        
	        $locked = "&nbsp;";
	        $void = $this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($employee_timein_date)));
	        $disabled_btn = false;
	        $no_approver_msg_locked = "Payroll for the period affected is locked. No new leave requests can be accepted. Please reach out to your HR or payroll manager to discuss options for this request.";
	        $no_approver_msg_closed = "Payroll for the period affected is closed. No new leave requests can be accepted. Please reach out to your HR or payroll manager to discuss options for this request.";
	        
	        if($approver_id) {
	            if(is_workflow_enabled($this->company_id)) {
	                if($void == "Waiting for approval"){
	                    $locked = "<p style='color: #EE6D54;'><strong>Warning</strong> : Leaves locked for payroll processing.<p>";
	                } elseif ($void == "Closed") {
	                    $locked = "<p style='color: #EE6D54;'><strong>Warning</strong> : The leave you are submitting is part of a closed payroll. Your request will be routed to the appropriate approvers for consideration and approval.<p>";
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
	        
	        $sched_blocks_res = array();
	        if($get_split_block_name) {
	            $sched_blocks_res[0] = array(
	                "schedule_blocks_id" => "all",
	                "block_name" => "Whole Blocks"
	            );
	            
	            foreach ($get_split_block_name as $sb) {
	                if($sb != FALSE){
	                    $sched_blocks_temp = array(
	                        "schedule_blocks_id" => $sb->schedule_blocks_id,
	                        "block_name" => $sb->block_name
	                    );
	                    
	                    array_push($sched_blocks_res, $sched_blocks_temp);
	                }
	                
	            }
	            
	            echo json_encode(array(
                    "error" => false,
                    "sched_blocks" => $sched_blocks_res,
                    "etime_out_date" => $locked,
                    "submit_btn" => $disabled_btn
                ));
                
                return false;
	        } else {
	            echo json_encode(array(
	                "error" => true,
	                "sched_blocks" => "",
	                "etime_out_date" => $locked,
	                "submit_btn" => $disabled_btn
	            ));
	            return false;
	        }
	    }
	    
	}

		
	/**
	 * Send notification to Group Approvers
	 */
	public function send_leave_notifcation($token = NULL, $leave_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "" , $withlink = "No", $level_token = "", $appr_id = ""){
		$leave_information = $this->agm->leave_information($leave_ids);
	
		if($leave_information != FALSE){
			$fullname = ucfirst($leave_information->first_name)." ".ucfirst($leave_information->last_name);
			$date_applied = date("F d, Y",strtotime($leave_information->date_filed));
			$leave_type = $leave_information->leave_type;
			$concat_start_date = date("F d, Y | h:i A",strtotime($leave_information->date_start));
			$concat_end_date = date("F d, Y | h:i A",strtotime($leave_information->date_end));
			$concat_return_date = date("F d, Y | h:i A",strtotime($leave_information->date_return));
			$total_leave_request = $leave_information->total_leave_requested;
			$reason = $leave_information->reasons;
			
			$attachm = $leave_information->required_file_documents;
            $req = "";
            if ($attachm) {
                $attachm = explode(";", $attachm);
                foreach ($attachm as $akey=>$aval) {
                    $base64_comp_id = base64_encode($this->company_id);
                    $base64_comp_id = str_replace("=", "", $base64_comp_id);
                    #if (file_exists('/uploads/companies/'.$this->company_id.'/'.$aval)) {
                        $req .= anchor(base_url()."download_leave_docs/leave_required_docs/fd/".$aval."/".$base64_comp_id,"Download File",array("class"=>"download_this"));
                    #}
                }
            }
				
			$link = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="'.base_url().'approval/leave/index/'.$token.'/'.$level_token.'/1'.$appr_id.'0">View Leave Application</a>';
			if($who == "Approver"){
				if($withlink == "No"){
					$link = '';
				}
			}else{
				$link = "";
			}
			
			$font_name = "'Open Sans'";

			$download_link = "";
            if($req != "") {
                $download_link .= '<tr>
                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Required Document:</td>
                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$req.'</td>
                                </tr>';
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
			$this->email->subject('Leave Application - '.$fullname);
		
			$this->email->message('
		<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
		<html lang="en">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
				<meta name="format-detection" content="telephone=no">
				<title>Leave Application</title>
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
																<p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">New leave application has been filed. Details below:</p>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>
												<td valign="top" style="padding-top:25px;">
													<table width="100%" border="0" cellspacing="0" cellpadding="0">
														<tr>
															<td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
															<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date_applied.'</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Leave Type:</td>
															<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$leave_type.'</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Reason:</td>
															<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$reason.'</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Start Date:</td>
															<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$concat_start_date.'</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">End Date</td>
															<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$concat_end_date.'</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Return Date:</td>
															<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$concat_return_date.'</td>
														</tr>
														<tr>
															<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Total Requested Leave:</td>
															<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$total_leave_request.' Day(s)</td>
														</tr>

														'.$download_link.'
														
														<tr>
															<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
															<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:40px;"></td>
														</tr>
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
									<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; 2015 Konsum Technologies. All Rights Reserved.</td>
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
			//show_error("Invalid token");
			echo json_encode(array("result" => 0,"msg" => "Invalid token."));
			return false;
		}
	}
		
		
		/**
		 * Send notification to HR
		 */
		public function send_noti_hr($emp_id=NULL,$date_applied=NULL,$leave_type=NULL,$concat_start_date=NULL,
			$concat_end_date=NULL,$concat_return_date=NULL,$total_leave_request=NULL,$reason=NULL,$email=NULL,$name=NULL,$shuffled=NULL,$hr_fullname,$hr_email){
				
			if($emp_id != NULL && $date_applied != NULL && $leave_type != NULL && $concat_start_date != NULL &&
					$concat_end_date != NULL && $concat_return_date != NULL && $reason != NULL && $email != NULL && $name != NULL && $shuffled != NULL){
		
				$employee_fullname = $this->agm->get_employee_fullname($emp_id,$this->company_id);
				$leave_type_name = $this->employee->leave_type_name($leave_type,$this->company_id);
		
				$new_date_applied = date("F d, Y",strtotime($date_applied));
				$new_concat_start_date = date("F d, Y h:i A",strtotime($concat_start_date));
				$new_concat_end_date = date("F d, Y h:i A",strtotime($concat_end_date));
				$new_concat_return_date = date("F d, Y h:i A",strtotime($concat_return_date));
		
				$config['protocol'] = 'sendmail';
				$config['wordwrap'] = TRUE;
				$config['mailtype'] = 'html';
				$config['charset'] = 'utf-8';
		
				$this->load->library('email',$config);
				$this->email->initialize($config);
				$this->email->set_newline("\r\n");
				$this->email->from('payroll@konsum.ph','Konsum Payroll System ');
				$this->email->to($hr_email);
		
				$this->email->subject($leave_type_name.' Application - '.$employee_fullname);
		
				$message_str = 'A new leave application is requested by '.$employee_fullname.' and is now waiting for head approval.';
				$insert_message_board = $this->approval->insert_message_board($hr_email,$message_str);
		
				$this->email->message(
						'
					<html>
						<head>
							<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
						</head>
						<body>
							<table>
								<tbody>
									<tr>
										<td>
											Hi '.$hr_fullname.',
										</td>
									</tr>
									<tr><td>&nbsp;</td></tr>
									<tr><td>A new leave application is requested by '.$employee_fullname.' and is now waiting for head approval.</td></tr>
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>Date Applied: '.$new_date_applied.'</td></tr>
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>Leave Type: '.$leave_type_name.'</td></tr>
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>Reason for Requesting Leave: '.$reason.'</td></tr>
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>Start Date: '.$new_concat_start_date.'</td></tr>
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>End Date: '.$new_concat_end_date.'</td></tr>
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>Return Date: '.$new_concat_return_date.'</td></tr>
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>Status: Pending Head Approval</td></tr>
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>Total Requested Leave: '.$total_leave_request.' Day(s)</td></tr>
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>&nbsp;</td></tr>
									<tr>
										<td>
											Thank you,<br />Konsum Payroll System
										</td>
									</tr>
								</tbody>
							</table>
						</body>
					</html>
					'
				);
		
				#<tr><td>&nbsp;</td></tr>
				#<tr><td>Click <a href="'.base_url().'approve_hr/leave/index/'.$shuffled.'">here</a> to view the leave application.</td></tr>
				#<tr><td>&nbsp;</td></tr>
		
				if($this->email->send()){
				return TRUE;
			}else{
				return FALSE;
			}
				
		}else{
			//show_error("Invalid parameter");
			echo json_encode(array("result"=>2,"error_msg"=>"- Invalid parameter 1."));
			return false;
		}
		}
	public function send_leave_cancellation_notif($leave_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "" ,$appr_id = "") {
			$leave_information = $this->agm->leave_information($leave_ids);
		
			if($leave_information != FALSE){
				$fullname = ucfirst($leave_information->first_name)." ".ucfirst($leave_information->last_name);
				$date_applied = date("F d, Y",strtotime($leave_information->date_filed));
				$leave_type = $leave_information->leave_type;
				$concat_start_date = date("F d, Y | h:i A",strtotime($leave_information->date_start));
				$concat_end_date = date("F d, Y | h:i A",strtotime($leave_information->date_end));
				$concat_return_date = date("F d, Y | h:i A",strtotime($leave_information->date_return));
				
				$reason = $leave_information->cancel_reason;
				$date_cancelled = date("F d, Y | h:i A",strtotime($leave_information->date_cancel));
				
				$font_name = "'Open Sans'";
				$config['protocol'] = 'sendmail';
				$config['wordwrap'] = TRUE;
				$config['mailtype'] = 'html';
				$config['charset'] = 'utf-8';
		
				$this->load->library('email',$config);
				$this->email->initialize($config);
				$this->email->set_newline("\r\n");
				$this->email->from(notifications_ashima_email(),'Ashima');
				$this->email->to($email);
				$this->email->subject('Leave Application Cancelled - '.$fullname);
				
				if($appr_id == $this->emp_id) {
					$description = '<p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Your leave request has been cancelled.</p>';
				} else {
					$description = '<p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">'.$fullname.' has cancelled the following leave request:</p>';
				}				
			
				$this->email->message('
			<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
			<html lang="en">
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
					<meta name="format-detection" content="telephone=no">
					<title>Leave Application Cancelled</title>
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
																	'.$description.'
																</td>
															</tr>
														</table>
													</td>
												</tr>
												<tr>
													<td valign="top" style="padding-top:25px;">
														<table width="100%" border="0" cellspacing="0" cellpadding="0">
															<tr>
																<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Leave Type:</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$leave_type.'</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Start Date:</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$concat_start_date.'</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">End Date</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$concat_end_date.'</td>
															</tr>
															<tr>
																<td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date_applied.'</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Reason:</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$reason.'</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Cancelled:</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date_cancelled.'</td>
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
        
    public function get_work_schedule() {
        $emp_ids 	= array($this->emp_id);

        $data['check_workday'] = $this->employee_v2->check_workday($this->company_id);
        $data['all_sched_flex']          = $this->employee_v2->all_sched_flex_in($this->company_id);
        $data['all_sched_reg']           = $this->employee_v2->get_comp_reg_sched($this->company_id);

        $leave_type = $this->input->post('leave_type');
        $employee_timein_date = date("Y-m-d",strtotime($this->input->post('start_date')));
        $end_date = date("Y-m-d",strtotime($this->input->post('end_date')));
        $flexi_hrs = $this->input->post('flexi_hrs');
        $if_partial = $this->input->post('if_partial');
        $if_half_day = $this->input->post('if_half_day');
        $what_half_day = "";
        
        if($if_half_day == "yes") {
            $what_half_day = $this->input->post('what_half_day');
        }
        
        // get the first and last blocks
        $work_schedule_id           = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$employee_timein_date);
        $check_work_type            = $this->employee->work_schedule_type($work_schedule_id, $this->company_id);
        $get_split_block_name       = $this->employee->list_of_blocks($employee_timein_date,$this->emp_id,$work_schedule_id,$this->company_id);
        $approver_id                = $this->employee->get_approver_name($this->emp_id,$this->company_id)->leave_approval_grp;
        $exclude_holidays           = $this->leave->get_leave_restriction($leave_type,'exclude_holidays');
        $disabled_btn               = false;
        $locked                     = "&nbsp;";
        $holiday_end_date           = ($end_date == "" || $end_date == null || $end_date == "1970-01-01") ? $employee_timein_date : $end_date;
        $data['holidays']           = $this->employee->check_is_date_holidayv2($this->company_id,$employee_timein_date,$holiday_end_date);
        $data['shift_schedules']    = $this->employee_v2->assigned_work_schedule($this->company_id,date("Y-m-d",strtotime($employee_timein_date.' -1 day')),$end_date,$emp_ids);
        $exclude_rest_days          = $this->leave->get_leave_restriction($leave_type,'exclude_rest_days');
        $if_NS                      = false;
        $for_start                  = $employee_timein_date;
        $holiday_name               = "";
        $get_open_shift_leave = $this->employee_v2->get_open_shift_leave($work_schedule_id, $this->company_id);

        $emp_ids = array($this->emp_id);
        $data['check_sched_split_flex']  = $this->employee_v2->check_this_sched($this->company_id,$employee_timein_date,$end_date,$emp_ids);
        $data['all_sched_block_time_in'] = $this->employee_v2->all_sched_block_time_in($this->company_id,$employee_timein_date,$end_date,$emp_ids);
        $data['emp_work_schedule']       = $this->employee_v2->emp_work_schedule($this->company_id,$employee_timein_date,$end_date,$emp_ids);
        
        $threshold_mins = 0;
        if($for_start){
            $flexi_hrs_to_mins = $flexi_hrs * 60;
            
            $employee_shift_schedule = in_array_custom($this->emp_id."-".$for_start,$data['shift_schedules']);
            $work_schedule_custom_id = "";
            $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($for_start)));
            
            $date = $for_start;
            if ($rest_day && $if_partial == "yes") {
                $work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d", strtotime($employee_timein_date.' -1 day')));
                $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($employee_timein_date.' -1 day')));
                $for_start = date("Y-m-d",strtotime($for_start.' -1 day'));
                $date = $for_start;
            }
            
            if($employee_shift_schedule) {
                $work_schedule_custom_id = $employee_shift_schedule->work_schedule_id;
            }
            
            $current_monday     = date("Y-m-d",strtotime($date." monday this week"));
            $time_name          = "~";
            $shift              = "";
            $starttime          = "";
            $endtime            = "";
            $totalhours         = 0;
            $full_flex          = false;
            $valid_flexi_date   = "";
            $eendtime           = "";
            $estarttime         = "";
            $is_hol             = false;
            
            $split = in_array_custom("wsi-{$work_schedule_id}",$data['check_workday']);
            if($split){
                if($split->work_type_name == "Workshift"){
                    $time_name = "Split Shift";
                } elseif ($get_open_shift_leave) {
                    $time_name = "Your shift on this day is open shift";
                    $totalhours_mins = $get_open_shift_leave->total_hrs_per_day * 60;
                    $totalhours = $get_open_shift_leave->total_hrs_per_day;
                    $starttime  = $for_start.' 08:00:00';
                    $endtime    = $for_start.' '.date("H:i:s", strtotime($starttime.' +'.$totalhours_mins.' hours'));
                    $full_flex = true;
                    
                    if($flexi_hrs_to_mins < 1) {
                        $mins_filed = 0;
                    } else {
                        $mins_filed = $flexi_hrs_to_mins / $totalhours_mins;
                    }
                    
                    $mins_filed = ceil($mins_filed);
                    
                    if($mins_filed <= 1) {
                        $valid_flexi_date = date("Y-m-d", strtotime($for_start));
                    } else {
                        $days_filed = $mins_filed - 1;
                        $get_flexi_end_date = date("Y-m-d", strtotime($for_start." +{$days_filed} days"));
                        
                        $valid_flexi_date = $get_flexi_end_date;
                    }
                } else {
                    $work_schedule_id1   = in_array_custom($this->emp_id."-".$date,$data['shift_schedules']);
                    if($work_schedule_id1){
                        if($date == $work_schedule_id1->valid_from){
                            $weekday = date('l',strtotime($date));
                            
                            if($work_schedule_id){
                                $fritz_work_schedule_id = $work_schedule_id;
                                #$work_schedule_info = $this->employee_v2->work_schedule_info2($this->company_id,$work_schedule_id,$weekday,$for_start,$this->emp_id,0,$data['check_sched_split_flex'],$data['all_sched_block_time_in'],$data['all_sched_flex']);
                            } else {
                                #$work_schedule_info = $this->employee_v2->work_schedule_info2($this->company_id,$work_schedule_id1->work_schedule_id,$weekday,$for_start,$this->emp_id,0,$data['check_sched_split_flex'],$data['all_sched_block_time_in'],$data['all_sched_flex']);
                                $fritz_work_schedule_id = $work_schedule_id1->work_schedule_id;
                            }
                            
                            $work_schedule_info = $this->employee_v2->work_schedule_info2($this->company_id,$fritz_work_schedule_id,$weekday,$for_start,$this->emp_id,0,$data['check_sched_split_flex'],$data['all_sched_block_time_in'],$data['all_sched_flex']);
                            #p($work_schedule_info);
                            if($work_schedule_info){                                        
                                $sstarttime = date("A",strtotime(time12hrs($work_schedule_info["work_schedule"]["start_time"])));
                                $eendtime = date("A",strtotime(time12hrs($work_schedule_info["work_schedule"]["end_time"])));
                                
                                $efor_start = $for_start;
                                if($sstarttime == "PM" && $eendtime == "AM") {
                                    $efor_start = date("Y-m-d", strtotime($for_start." +1 day"));
                                }
                                
                                $starttime_new = $for_start.' '.time12hrs($work_schedule_info["work_schedule"]["start_time"]);
                                $endtime_new =  $efor_start.' '.time12hrs($work_schedule_info["work_schedule"]["end_time"]);
                                $total_hours_half_min = ($work_schedule_info["work_schedule"]["total_hours"] / 2) * 60;
                               
                                if($what_half_day == "second_half") {
                                    $starttime_new = date("Y-m-d H:i:s", strtotime($endtime_new." -".$total_hours_half_min." minutes"));
                                } elseif($what_half_day == "first_half") {
                                    $endtime_new = date("Y-m-d H:i:s", strtotime($starttime_new." +".$total_hours_half_min." minutes"));
                                }
                                    
                                $starttime1 = time12hrs($work_schedule_info["work_schedule"]["start_time"]);
                                $starttime  = $starttime_new;
                                $endtime1   = time12hrs($work_schedule_info["work_schedule"]["end_time"]);
                                $endtime    = $endtime_new;
                                $totalhours = $work_schedule_info["work_schedule"]["total_hours"];
                                $time_name  = "Your shift on this day starts at: {$starttime1} and ends at {$endtime1}";
                                $threshold_mins = $work_schedule_info["work_schedule"]["threshold_mins"];
                                #p($starttime);
                            }
                        }
                    }
                    
                    $sure = false;
                    if($work_schedule_id == 0 || $work_schedule_id == ""){
                        $work_schedule_idz = in_array_custom("emp_id-{$this->emp_id}-{$for_start}",$data['emp_work_schedule']);
                        if($work_schedule_idz){
                            $work_schedule_idx = $work_schedule_idz->work_schedule_id;
                        } else {
                            $work_schedule_idy = in_array_custom("emp_id-{$this->emp_id}",$data['emp_payroll_info_wsid']);
                            if($work_schedule_idy){
                                $work_schedule_idx = $work_schedule_idy->work_schedule_id;
                            }
                        }
                    } else {
                        $work_schedule_idx = $work_schedule_id;
                    }
                    
                    $work_schedule_idx 	= ($work_schedule_custom_id != "") ? $work_schedule_custom_id : $work_schedule_idx;
                    $no_schedule		= $this->employee_v2->get_workschedule_info_for_no_workschedule($this->company_id,$for_start,$work_schedule_idx,true,$data['all_sched_flex'],$data['all_sched_reg']);
                    
                    $for_start_m_d = date("m-d", strtotime($for_start));
                    $is_holiday 		= in_array_custom("date-{$for_start_m_d}",$data['holidays']);
                    
                    if($is_holiday){
                        #if($is_holiday->repeat_type == "no"){
                        if($is_holiday->date_type == "fixed") {
                            #$cur_year = date("Y");
                            #$hol_year = date("Y",strtotime($date));
                            
                            $app_m_d = date("m-d",strtotime($for_start));
                            $hol_m_d = date("m-d",strtotime($is_holiday->date));
                            
                            if($app_m_d == $hol_m_d){
                                #if($cur_year == $hol_year){
                                $is_hol = true;
                            } else {
                                $is_hol = false;
                            }
                        } else {
                            $is_hol = true;
                        }
                    }
                    
                    if($is_hol){
                        $holiday_name = $is_holiday->holiday_name.' ('.$is_holiday->hour_type_name.')';
                    }
                    
                    if($shift == "" || $no_schedule ){
                        if($no_schedule){
                            if($no_schedule["end_time"] == "") {
                                if($no_schedule["start_time"] == "" || $no_schedule["start_time"] == null) {
                                    $full_flex = true;
                                }
                                
                                $starttime  = $for_start.' '.$no_schedule["start_time"];
                                #$starttime  = date("Y-m-d h:i A", strtotime($starttime));
                                $br_hr      = $no_schedule["break"] / 60;
                                $hr_to_min  = ($no_schedule["total_hours"] * 60) - $no_schedule["break"];
                                $totalhours = $no_schedule["total_hours"] - $br_hr;
                                #$endtime  = date("Y-m-d h:i A", strtotime($starttime));
                                #$starttime    = date("Y-m-d", strtotime($starttime)).' '.date("h:i A", strtotime($starttime." -{$hr_to_min} minutes"));
                                $endtime    = date("Y-m-d", strtotime($starttime)).' '.date("h:i A", strtotime($starttime." -{$hr_to_min} minutes"));
                                
                                if($flexi_hrs_to_mins < 1) {
                                    $mins_filed = 0;
                                } else {
                                    $mins_filed = $flexi_hrs_to_mins / $hr_to_min;
                                }
                                
                                $mins_filed = ceil($mins_filed);
                                
                                if($mins_filed <= 1) {
                                    $valid_flexi_date = date("Y-m-d", strtotime($for_start));
                                } else {
                                    $days_filed = $mins_filed - 1;
                                    $get_flexi_end_date = date("Y-m-d", strtotime($for_start." +{$days_filed} days"));
                                    $rest_n_holiday = true;
                                    
                                    while($rest_n_holiday){
                                        #$is_holiday = $this->employee->get_holiday_date(date('Y-m-d', strtotime($get_flexi_end_date)),$this->emp_id,$this->company_id);
                                        $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($get_flexi_end_date)));
                                        $holiday_date = date('Y-m-d', strtotime($get_flexi_end_date));
                                        $holiday_date_m_d = date("m-d", strtotime($holiday_date));
                                        $is_holiday_q = in_array_custom("date-{$holiday_date_m_d}",$data['holidays']);
                                        $is_holiday = false;
                                        
                                        // exclude holiday
                                        if($is_holiday_q){
                                            #if($is_holiday_q->repeat_type == "no"){
                                            if($is_holiday_q->date_type == "fixed") {
                                                #$cur_year = date("Y");
                                                #$hol_year = date("Y",strtotime($date));
                                                
                                                $app_m_d = date("m-d",strtotime($holiday_date));
                                                $hol_m_d = date("m-d",strtotime($is_holiday_q->date));
                                                
                                                if($app_m_d == $hol_m_d){
                                                    #if($cur_year == $hol_year){
                                                    $is_hol = true;
                                                } else {
                                                    $is_hol = false;
                                                }
                                            } else {
                                                $is_hol = true;
                                            }
                                        } else {
                                            $is_hol = false;
                                        }
                                        
                                        if($is_hol) {
                                            if($is_holiday_q) {
                                                if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "yes"){
                                                    $is_holiday = true;
                                                } else {
                                                    if($is_holiday_q->hour_type_name == "Special Holiday") {
                                                        // exclude Special holiday only
                                                        if($exclude_holidays == 'yes' && $exclude_regular_holidays == "no" && $exclude_special_holidays == "yes"){
                                                            $is_holiday = true;
                                                        }
                                                    } elseif($is_holiday_q->hour_type_name == "Regular Holiday") {
                                                        // exclude Regular holiday only
                                                        if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "no"){
                                                            $is_holiday = true;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        
                                        if($rest_day || $is_holiday){
                                            $get_flexi_end_date = date('Y-m-d', strtotime($get_flexi_end_date.' +1 day'));
                                        } else {
                                            $rest_n_holiday = false;
                                        }
                                    }
                                    
                                    $valid_flexi_date = $get_flexi_end_date;
                                }
                                
                                $time_name = "Your shift on this day requires {$totalhours} hours of work";
                                
                                if($full_flex) {
                                    $time_name = "Your shift on this day is flex";
                                }
                                
                            } else {
                                $sstarttime = date("A",strtotime(time12hrs($no_schedule["start_time"])));
                                $eendtime = date("A",strtotime(time12hrs($no_schedule["end_time"])));
                                
                                $efor_start = $for_start;
                                if($sstarttime == "PM" && $eendtime == "AM") {
                                    $efor_start = date("Y-m-d", strtotime($for_start." +1 day"));
                                }
                                
                                $starttime_new = $for_start.' '.time12hrs($no_schedule["start_time"]);
                                $endtime_new =  $efor_start.' '.time12hrs($no_schedule["end_time"]);
                                $total_hours_half_min = ($no_schedule["total_hours"] / 2) * 60;
                                
                                if($what_half_day == "second_half") {
                                    $starttime_new = date("Y-m-d H:i:s", strtotime($endtime_new." -".$total_hours_half_min." minutes"));
                                } elseif($what_half_day == "first_half") {
                                    $endtime_new = date("Y-m-d H:i:s", strtotime($starttime_new." +".$total_hours_half_min." minutes"));
                                }
                                
                                $starttime1 = time12hrs($no_schedule["start_time"]);
                                $starttime = $starttime_new;
                                $endtime1 = time12hrs($no_schedule["end_time"]);
                                $endtime = $endtime_new;
                                $totalhours = $no_schedule["total_hours"];
                                $time_name = "Your shift on this day starts at: {$starttime1} and ends at {$endtime1}";
                                $threshold_mins = $no_schedule["threshold_mins"];
                            }
                        }
                    } else {
                        $time_name = $time_name;
                    }
                }
            } elseif ($get_open_shift_leave) {
                $time_name = "Your shift on this day is open shift";
                $totalhours_mins = $get_open_shift_leave->total_hrs_per_day * 60;
                $totalhours = $get_open_shift_leave->total_hrs_per_day;
                $starttime  = $for_start.' 08:00:00';
                $endtime    = $for_start.' '.date("H:i:s", strtotime($starttime.' +'.$totalhours_mins.' hours'));
                $full_flex = true;
                
                if($flexi_hrs_to_mins < 1) {
                    $mins_filed = 0;
                } else {
                    $mins_filed = $flexi_hrs_to_mins / $totalhours_mins;
                }
                
                $mins_filed = ceil($mins_filed);
                
                if($mins_filed <= 1) {
                    $valid_flexi_date = date("Y-m-d", strtotime($for_start));
                } else {
                    $days_filed = $mins_filed - 1;
                    $get_flexi_end_date = date("Y-m-d", strtotime($for_start." +{$days_filed} days"));
                    
                    $valid_flexi_date = $get_flexi_end_date;
                }
            } else {
                $work_schedule_id1   = in_array_custom($this->emp_id."-".$for_start,$data['shift_schedules']);
                if($work_schedule_id1){
                    if($date == $work_schedule_id1->valid_from){
                        $weekday = date('l',strtotime($for_start));
                        
                        if($work_schedule_id){
                            $work_schedule_info = $this->employee_v2->work_schedule_info2($this->company_id,$work_schedule_id,$weekday,$for_start,$this->emp_id,0,$data['check_sched_split_flex'],$data['all_sched_block_time_in'],$data['all_sched_flex']);
                        } else {
                            $work_schedule_info = $this->employee_v2->work_schedule_info2($this->company_id,$work_schedule_id1->work_schedule_id,$weekday,$for_start,$this->emp_id,0,$data['check_sched_split_flex'],$data['all_sched_block_time_in'],$data['all_sched_flex']);
                        }
                        
                        if($work_schedule_info){
                            $sstarttime = date("A",strtotime(time12hrs($work_schedule_info["work_schedule"]["start_time"])));
                            $eendtime = date("A",strtotime(time12hrs($work_schedule_info["work_schedule"]["end_time"])));
                            
                            $efor_start = $for_start;
                            if($sstarttime == "PM" && $eendtime == "AM") {
                                $efor_start = date("Y-m-d", strtotime($for_start." +1 day"));
                            }
                            
                            $starttime_new = $for_start.' '.time12hrs($work_schedule_info["work_schedule"]["start_time"]);
                            $endtime_new =  $efor_start.' '.time12hrs($work_schedule_info["work_schedule"]["end_time"]);
                            $total_hours_half_min = ($work_schedule_info["work_schedule"]["total_hours"] / 2) * 60;
                            
                            if($what_half_day == "second_half") {
                                $starttime_new = date("Y-m-d H:i:s", strtotime($endtime_new." -".$total_hours_half_min." minutes"));
                            } elseif($what_half_day == "first_half") {
                                $endtime_new = date("Y-m-d H:i:s", strtotime($starttime_new." +".$total_hours_half_min." minutes"));
                            }
                            
                            $starttime1 = time12hrs($work_schedule_info["work_schedule"]["start_time"]);
                            $starttime  = $starttime_new;
                            $endtime1   = time12hrs($work_schedule_info["work_schedule"]["end_time"]);
                            $endtime    = $endtime_new;
                            $totalhours = $work_schedule_info["work_schedule"]["total_hours"];
                            $time_name  = "Your shift on this day starts at: {$starttime1} and ends at {$endtime1}";
                            $threshold_mins = $work_schedule_info["work_schedule"]["threshold_mins"];
                        }
                    }
                }
                
                $sure = false;
                $work_schedule_idx = $work_schedule_id;
                
                $work_schedule_idx 	= ($work_schedule_custom_id != "") ? $work_schedule_custom_id : $work_schedule_idx;
                $no_schedule		= $this->employee_v2->get_workschedule_info_for_no_workschedule($this->company_id,$for_start,$work_schedule_idx,true,$data['all_sched_flex'],$data['all_sched_reg']);
                
                $for_start_m_d = date("m-d", strtotime($for_start));
                $is_holiday 		= in_array_custom("date-{$for_start_m_d}",$data['holidays']);
                
                if($is_holiday){
                    #if($is_holiday->repeat_type == "no"){
                    if($is_holiday->date_type == "fixed") {
                        #$cur_year = date("Y");
                        #$hol_year = date("Y",strtotime($date));
                        
                        $app_m_d = date("m-d",strtotime($for_start));
                        $hol_m_d = date("m-d",strtotime($is_holiday->date));
                        
                        if($app_m_d == $hol_m_d){
                            #if($cur_year == $hol_year){
                            $is_hol = true;
                        } else {
                            $is_hol = false;
                        }
                    } else {
                        $is_hol = true;
                    }
                }
                
                if($is_hol){
                    $holiday_name = $is_holiday->holiday_name.'('.$is_holiday->hour_type_name.')';
                }
                #else {
                if($shift == "" || $no_schedule ){
                    if($no_schedule){
                        if($no_schedule["end_time"] == "") {
                            if($no_schedule["start_time"] == "") {
                                $full_flex = true;
                            }
                            
                            $starttime_temp = $for_start.' '.$no_schedule["start_time"];
                            #$starttime      = date("Y-m-d h:i A", strtotime($starttime_temp));
                            $br_hr          = $no_schedule["break"] / 60;
                            $hr_to_min      = ($no_schedule["total_hours"] * 60) - $no_schedule["break"];
                            $totalhours     = $no_schedule["total_hours"] - $br_hr;
                            $endtime      = date("Y-m-d h:i A", strtotime($starttime_temp));
                            $starttime        = date("Y-m-d", strtotime($starttime_temp)).' '.date("h:i A", strtotime($starttime_temp." -{$hr_to_min} minutes"));
                            
                            if($flexi_hrs_to_mins < 1) {
                                $mins_filed = 0;
                            } else {
                                $mins_filed = $flexi_hrs_to_mins / $hr_to_min;
                            }
                            
                            $mins_filed     = ceil($mins_filed);
                            
                            if($mins_filed <= 1) {
                                $valid_flexi_date = date("Y-m-d", strtotime($for_start));
                            } else {
                                $days_filed = $mins_filed - 1;
                                $get_flexi_end_date = date("Y-m-d", strtotime($for_start." +{$days_filed} days"));
                                $rest_n_holiday = true;
                                
                                while($rest_n_holiday){
                                    #$is_holiday = $this->employee->get_holiday_date(date('Y-m-d', strtotime($get_flexi_end_date)),$this->emp_id,$this->company_id);
                                    $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($get_flexi_end_date)));
                                    $holiday_date = date('Y-m-d', strtotime($get_flexi_end_date));
                                    
                                    $holiday_date_m_d = date("m-d", strtotime($holiday_date));
                                    $is_holiday_q = in_array_custom("date-{$holiday_date_m_d}",$data['holidays']);
                                    $is_holiday = false;
                                    
                                    // exclude holiday
                                    if($is_holiday_q){
                                        #if($is_holiday_q->repeat_type == "no"){
                                        if($is_holiday_q->date_type == "fixed") {
                                            #$cur_year = date("Y");
                                            #$hol_year = date("Y",strtotime($date));
                                            
                                            $app_m_d = date("m-d",strtotime($holiday_date));
                                            $hol_m_d = date("m-d",strtotime($is_holiday_q->date));
                                            
                                            if($app_m_d == $hol_m_d){
                                                #if($cur_year == $hol_year){
                                                $is_hol = true;
                                            } else {
                                                $is_hol = false;
                                            }
                                        } else {
                                            $is_hol = true;
                                        }
                                    } else {
                                        $is_hol = false;
                                    }
                                    
                                    if($is_hol) {
                                        if($is_holiday_q) {
                                            if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "yes"){
                                                $is_holiday = true;
                                            } else {
                                                if($is_holiday_q->hour_type_name == "Special Holiday") {
                                                    // exclude Special holiday only
                                                    if($exclude_holidays == 'yes' && $exclude_regular_holidays == "no" && $exclude_special_holidays == "yes"){
                                                        $is_holiday = true;
                                                    }
                                                } elseif($is_holiday_q->hour_type_name == "Regular Holiday") {
                                                    // exclude Regular holiday only
                                                    if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "no"){
                                                        $is_holiday = true;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
                                    if($rest_day || $is_holiday){
                                        $get_flexi_end_date = date('Y-m-d', strtotime($get_flexi_end_date.' +1 day'));
                                    } else {
                                        $rest_n_holiday = false;
                                    }
                                }
                                
                                $valid_flexi_date = $get_flexi_end_date;
                            }
                            
                            #$time_name = "Your shift on this day requires {$no_schedule["total_hours"]} hours of work";
                            $time_name = "Your shift on this day requires {$totalhours} hours of work";
                            
                            if($full_flex) {
                                $time_name = "Your shift on this day is flex";
                            }
                        } else {
                            $sstarttime = date("A",strtotime(time12hrs($no_schedule["start_time"])));
                            $eendtime = date("A",strtotime(time12hrs($no_schedule["end_time"])));
                            
                            $efor_start = $for_start;
                            if($sstarttime == "PM" && $eendtime == "AM") {
                                $efor_start = date("Y-m-d", strtotime($for_start." +1 day"));
                            }
                            
                            $starttime_new = $for_start.' '.time12hrs($no_schedule["start_time"]);
                            $endtime_new =  $efor_start.' '.time12hrs($no_schedule["end_time"]);
                            $total_hours_half_min = ($no_schedule["total_hours"] / 2) * 60;
                            
                            if($what_half_day == "second_half") {
                                $starttime_new = date("Y-m-d H:i:s", strtotime($endtime_new." -".$total_hours_half_min." minutes"));
                            } elseif($what_half_day == "first_half") {
                                $endtime_new = date("Y-m-d H:i:s", strtotime($starttime_new." +".$total_hours_half_min." minutes"));
                            }
                            
                            $starttime1 = time12hrs($no_schedule["start_time"]);
                            $starttime  = $starttime_new;
                            $endtime1   = time12hrs($no_schedule["end_time"]);
                            $endtime    = $endtime_new;
                            $totalhours = $no_schedule["total_hours"];
                            $time_name  = "Your shift on this day starts at: {$starttime1} and ends at {$endtime1}";
                            $threshold_mins = $no_schedule["threshold_mins"];
                        }
                    }
                } else {
                    $time_name = $time_name;
                }
                #}
            }
            
            if ($rest_day) {
                $eendtime = date("Y-m-d", strtotime($for_start)).' '.$eendtime;
                $time_name = "Rest Day";
            }
            
            
            $NS_start_time = ($starttime) ? date("m/d/Y h:i A", strtotime($starttime)) : date("m/d/Y h:i A", strtotime($for_start));
            $NS_end_time = date("m/d/Y h:i A", strtotime($endtime));
            
            if ((date("A", strtotime($NS_end_time)) == "AM" && date("A", strtotime($NS_start_time)) == "PM")) {
                if ($if_partial == "yes") {
                    $if_NS = true;
                }
            }
            
            if($is_hol) {
                if($is_holiday) {
                    if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "yes"){
                        if ($if_partial == "yes") {
                            $disabled_btn = false;
                        } else {
                            $disabled_btn = true;
                            $locked = "You can not apply for a leave on a Holiday.";
                        }
                    } else {
                        if($is_holiday->hour_type_name == "Special Holiday") {
                            // exclude regular holiday only
                            if($exclude_holidays == 'yes' && $exclude_regular_holidays == "no" && $exclude_special_holidays == "yes"){
                                if ($if_partial == "yes") {
                                    $disabled_btn = false;
                                } else {
                                    $disabled_btn = true;
                                    $locked = "You can not apply for a leave on a Special Holiday.";
                                }
                            }
                        }
                        
                        if($is_holiday->hour_type_name == "Regular Holiday") {
                            // exclude regular holiday only
                            if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "no"){
                                if ($if_partial == "yes") {
                                    $disabled_btn = false;
                                } else {
                                    $disabled_btn = true;
                                    $locked = "You can not apply for a leave on a Regular Holiday.";
                                }
                            }
                        }
                    }
                    
                }
            }
            
            if ($time_name == "Rest Day") {
                if($exclude_rest_days == 'yes'){
                    if ($if_partial == "yes") {
                        $disabled_btn = false;
                    } else {
                        $locked = "You can not apply for a leave on a Rest Day.";
                        $disabled_btn = true;
                    }
                }
            }
        } else {
            $time_name = "";
        }
        
        $e_check_if_24_end = $end_date.' '.date("H:i:s", strtotime($endtime));
        $e_check_if_24_hrs_ttl = (strtotime($e_check_if_24_end) - strtotime($starttime)) / 3600 / 24;
        
        if ($e_check_if_24_hrs_ttl <= 1) {
            $for_end = date("Y-m-d", strtotime($for_start));
        } else {
            $for_end = ($end_date == "1970-01-01" || $end_date == "") ? date("Y-m-d", strtotime($endtime)) : $end_date;
        }

        $full_flex_end = false;
        $eholiday_name = "";
        
        if($for_end != "1970-01-01"){
            $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($for_end)));
            
            $ework_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$for_end);
            $employee_shift_schedule = in_array_custom($this->emp_id."-".$for_end,$data['shift_schedules']);
            $work_schedule_custom_id = "";
            
            if($employee_shift_schedule) {
                $work_schedule_custom_id = $employee_shift_schedule->work_schedule_id;
            }
            
            $date           = $for_end ;
            $current_monday = date("Y-m-d",strtotime($date." monday this week"));
            $etime_name     = "~";
            $shift          = "";
            $estarttime     = "";
            $eendtime       = "";
            $totalhours     = 0;
            
            $split = in_array_custom("wsi-{$ework_schedule_id}",$data['check_workday']);
            if($split){
                if($split->work_type_name == "Workshift"){
                    $etime_name = "Split Shift";
                } elseif ($get_open_shift_leave) {
                    $etime_name = "Your shift on this day is open shift";
                    $totalhours_mins = $get_open_shift_leave->total_hrs_per_day * 60;
                    $totalhours = $get_open_shift_leave->total_hrs_per_day;
                    $estarttime  = $for_end.' 08:00:00';
                    $eendtime    = $for_end.' '.date("H:i:s", strtotime($estarttime.' +'.$totalhours_mins.' minutes'));
                    $full_flex = true;
                    #$valid_flexi_date = $for_start;
                } else {
                    $work_schedule_id1   = in_array_custom($this->emp_id."-".$date,$data['shift_schedules']);
                    if($work_schedule_id1){
                        if($date == $work_schedule_id1->valid_from){
                            $weekday = date('l',strtotime($date));
                            
                            if($ework_schedule_id){
                                $work_schedule_info = $this->employee_v2->work_schedule_info2($this->company_id,$ework_schedule_id,$weekday,$for_end,$this->emp_id,0,$data['check_sched_split_flex'],$data['all_sched_block_time_in'],$data['all_sched_flex']);
                            } else {
                                $work_schedule_info = $this->employee_v2->work_schedule_info2($this->company_id,$work_schedule_id1->work_schedule_id,$weekday,$for_end,$this->emp_id,0,$data['check_sched_split_flex'],$data['all_sched_block_time_in'],$data['all_sched_flex']);
                            }
                            
                            if($work_schedule_info){
                                $starttime_new = $for_end.' '.time12hrs($work_schedule_info["work_schedule"]["start_time"]);
                                $endtime_new =  $for_end.' '.time12hrs($work_schedule_info["work_schedule"]["end_time"]);
                                $total_hours_half_min = ($work_schedule_info["work_schedule"]["total_hours"] / 2) * 60;
                                
                                if($what_half_day == "second_half") {
                                    $starttime_new = date("Y-m-d H:i:s", strtotime($endtime_new." -".$total_hours_half_min." minutes"));
                                } elseif($what_half_day == "first_half") {
                                    $endtime_new = date("Y-m-d H:i:s", strtotime($starttime_new." +".$total_hours_half_min." minutes"));
                                }
                                
                                $starttime1 = time12hrs($work_schedule_info["work_schedule"]["start_time"]);
                                $estarttime = $starttime_new;
                                $endtime1   = time12hrs($work_schedule_info["work_schedule"]["end_time"]);
                                $eendtime   = $endtime_new;
                                $totalhours = $work_schedule_info["work_schedule"]["total_hours"];
                                $etime_name = "Your shift on this day starts at: {$starttime1} and ends at {$endtime1}";
                                $threshold_mins = $work_schedule_info["work_schedule"]["threshold_mins"];
                            }
                        }
                    }
                    
                    $sure = false;
                    if($ework_schedule_id == 0 || $ework_schedule_id == ""){
                        $work_schedule_idz = in_array_custom("emp_id-{$this->emp_id}-{$for_end}",$data['emp_work_schedule']);
                        if($work_schedule_idz){
                            $work_schedule_idx = $work_schedule_idz->work_schedule_id;
                        } else {
                            $work_schedule_idy = in_array_custom("emp_id-{$this->emp_id}",$data['emp_payroll_info_wsid']);
                            if($work_schedule_idy){
                                $work_schedule_idx = $work_schedule_idy->work_schedule_id;
                            }
                        }
                    } else {
                        $work_schedule_idx = $ework_schedule_id;
                    }
                    
                    $work_schedule_idx 	= ($work_schedule_custom_id != "") ? $work_schedule_custom_id : $work_schedule_idx;
                    
                    $no_schedule		= $this->employee_v2->get_workschedule_info_for_no_workschedule($this->company_id,$for_end,$work_schedule_idx,true,$data['all_sched_flex'],$data['all_sched_reg']);
                    
                    $for_end_m_d = date("m-d", strtotime($for_end));
                    $is_holiday 		= in_array_custom("date-{$for_end_m_d}",$data['holidays']);
                    $is_hol 			= false;
                    #p($for_end);
                    if($is_holiday){
                        #if($is_holiday->repeat_type == "no"){
                        if($is_holiday->date_type == "fixed") {
                            #$cur_year = date("Y");
                            #$hol_year = date("Y",strtotime($date));
                            
                            $app_m_d = date("m-d",strtotime($for_end));
                            $hol_m_d = date("m-d",strtotime($is_holiday->date));
                            
                            if($app_m_d == $hol_m_d){
                                #if($cur_year == $hol_year){
                                $is_hol = true;
                            } else {
                                $is_hol = false;
                            }
                        } else {
                            $is_hol = true;
                        }
                    }
                    
                    if($is_hol){
                        $eholiday_name = $is_holiday->holiday_name.' ('.$is_holiday->hour_type_name.')';
                    }
                    
                    #else {
                    if($shift == "" || $no_schedule ){
                        if($no_schedule){
                            if($no_schedule["end_time"] == "") {
                                if($no_schedule["start_time"] == "") {
                                    $full_flex_end = true;
                                }
                                
                                $starttime1 = $for_end.' '.$no_schedule["start_time"];
                                # $estarttime = date("Y-m-d h:i A", strtotime($starttime1));
                                $br_hr      = $no_schedule["break"] / 60;
                                $hr_to_min  = ($no_schedule["total_hours"] * 60) - $no_schedule["break"];
                                $totalhours = $no_schedule["total_hours"] - $br_hr;
                                $eendtime = date("Y-m-d h:i A", strtotime($starttime1));
                                $estarttime   = date("Y-m-d", strtotime($starttime1)).' '.date("h:i A", strtotime($starttime1." -{$hr_to_min} minutes"));
                                
                                $etime_name = "Your shift on this day requires {$totalhours} hours of work";
                                
                                if($full_flex_end) {
                                    $etime_name = "Your shift on this day is flex";
                                }
                            } else {
                                $starttime_new = $for_end.' '.time12hrs($no_schedule["start_time"]);
                                $endtime_new =  $for_end.' '.time12hrs($no_schedule["end_time"]);
                                $total_hours_half_min = ($no_schedule["total_hours"] / 2) * 60;
                                
                                if($what_half_day == "second_half") {
                                    $starttime_new = date("Y-m-d H:i:s", strtotime($endtime_new." -".$total_hours_half_min." minutes"));
                                } elseif($what_half_day == "first_half") {
                                    $endtime_new = date("Y-m-d H:i:s", strtotime($starttime_new." +".$total_hours_half_min." minutes"));
                                }
                                
                                $starttime1 = time12hrs($no_schedule["start_time"]);
                                $estarttime = $starttime_new;
                                $endtime1   = time12hrs($no_schedule["end_time"]);
                                $eendtime   = $endtime_new;
                                $totalhours = $no_schedule["total_hours"];
                                $etime_name = "Your shift on this day starts at: {$starttime1} and ends at {$endtime1}";
                                $threshold_mins = $no_schedule["threshold_mins"];
                            }
                        }
                    } else {
                        $etime_name = $etime_name;
                    }
                    #}
                }
            } elseif ($get_open_shift_leave) {
                $etime_name = "Your shift on this day is open shift";
                $totalhours_mins = $get_open_shift_leave->total_hrs_per_day * 60;
                $totalhours = $get_open_shift_leave->total_hrs_per_day;
                $estarttime  = $for_end.' 08:00:00';
                $eendtime    = $for_end.' '.date("H:i:s", strtotime($estarttime.' +'.$totalhours_mins.' minutes'));
                $full_flex = true;
                #$valid_flexi_date = $for_start;
            } else {
                $work_schedule_id1   = in_array_custom($this->emp_id."-".$for_end,$data['shift_schedules']);
                if($work_schedule_id1){
                    if($date == $work_schedule_id1->valid_from){
                        $weekday = date('l',strtotime($for_end));
                        
                        if($ework_schedule_id){
                            $work_schedule_info = $this->employee_v2->work_schedule_info2($this->company_id,$ework_schedule_id,$weekday,$for_end,$this->emp_id,0,$data['check_sched_split_flex'],$data['all_sched_block_time_in'],$data['all_sched_flex']);
                        } else {
                            $work_schedule_info = $this->employee_v2->work_schedule_info2($this->company_id,$work_schedule_id1->work_schedule_id,$weekday,$for_end,$this->emp_id,0,$data['check_sched_split_flex'],$data['all_sched_block_time_in'],$data['all_sched_flex']);
                        }
                        
                        if($work_schedule_info){
                            $starttime_new = $for_end.' '.time12hrs($work_schedule_info["work_schedule"]["start_time"]);
                            $endtime_new =  $for_end.' '.time12hrs($work_schedule_info["work_schedule"]["end_time"]);
                            $total_hours_half_min = ($work_schedule_info["work_schedule"]["total_hours"] / 2) * 60;
                            
                            if($what_half_day == "second_half") {
                                $starttime_new = date("Y-m-d H:i:s", strtotime($endtime_new." -".$total_hours_half_min." minutes"));
                            } elseif($what_half_day == "first_half") {
                                $endtime_new = date("Y-m-d H:i:s", strtotime($starttime_new." +".$total_hours_half_min." minutes"));
                            }
                            
                            $starttime1 = time12hrs($work_schedule_info["work_schedule"]["start_time"]);
                            $estarttime = $starttime_new;
                            $endtime1   = time12hrs($work_schedule_info["work_schedule"]["end_time"]);
                            $eendtime   = $endtime_new;
                            $totalhours = $work_schedule_info["work_schedule"]["total_hours"];
                            $etime_name = "Your shift on this day starts at: {$starttime1} and ends at {$endtime1}";
                            $threshold_mins = $work_schedule_info["work_schedule"]["threshold_mins"];
                        }
                    }
                }
                
                $sure = false;
                $work_schedule_idx = $ework_schedule_id;
                
                $work_schedule_idx  = ($work_schedule_custom_id != "") ? $work_schedule_custom_id : $work_schedule_idx;
                $no_schedule        = $this->employee_v2->get_workschedule_info_for_no_workschedule($this->company_id,$for_end,$work_schedule_idx,true,$data['all_sched_flex'],$data['all_sched_reg']);
                
                $for_end_m_d = date("m-d", strtotime($for_end));
                $is_holiday         = in_array_custom("date-{$for_end_m_d}",$data['holidays']);
                $is_hol             = false;
                
                if($is_holiday){
                    #if($is_holiday->repeat_type == "no"){
                    if($is_holiday->date_type == "fixed") {
                        #$cur_year = date("Y");
                        #$hol_year = date("Y",strtotime($date));
                        
                        $app_m_d = date("m-d",strtotime($for_end));
                        $hol_m_d = date("m-d",strtotime($is_holiday->date));
                        
                        if($app_m_d == $hol_m_d){
                            #if($cur_year == $hol_year){
                            $is_hol = true;
                        } else {
                            $is_hol = false;
                        }
                    } else {
                        $is_hol = true;
                    }
                }
                
                if($is_hol){
                    $eholiday_name = $is_holiday->holiday_name.' ('.$is_holiday->hour_type_name.')';
                }
                
                if($shift == "" || $no_schedule ){
                    if($no_schedule){
                        if($no_schedule["end_time"] == "") {
                            if($no_schedule["start_time"] == "") {
                                $full_flex_end = true;
                            }
                            $starttime_temp = $for_end.' '.$no_schedule["start_time"];
                            #$estarttime     = date("Y-m-d h:i A", strtotime($starttime_temp));
                            $br_hr          = $no_schedule["break"] / 60;
                            $hr_to_min      = ($no_schedule["total_hours"] * 60) - $no_schedule["break"];
                            $totalhours     = $no_schedule["total_hours"] - $br_hr;
                            $eendtime     = date("Y-m-d h:i A", strtotime($starttime_temp));
                            $estarttime       = date("Y-m-d", strtotime($starttime_temp)).' '.date("h:i A", strtotime($starttime_temp." -{$hr_to_min} minutes"));
                            
                            
                            $etime_name = "Your shift on this day requires {$totalhours} hours of work";
                            
                            if($full_flex_end) {
                                $etime_name = "Your shift on this day is flex";
                            }
                        } else {
                            $starttime_new = $for_end.' '.time12hrs($no_schedule["start_time"]);
                            $endtime_new =  $for_end.' '.time12hrs($no_schedule["end_time"]);
                            $total_hours_half_min = ($no_schedule["total_hours"] / 2) * 60;
                            
                            if($what_half_day == "second_half") {
                                $starttime_new = date("Y-m-d H:i:s", strtotime($endtime_new." -".$total_hours_half_min." minutes"));
                            } elseif($what_half_day == "first_half") {
                                $endtime_new = date("Y-m-d H:i:s", strtotime($starttime_new." +".$total_hours_half_min." minutes"));
                            }
                            
                            $starttime1 = time12hrs($no_schedule["start_time"]);
                            $estarttime = $starttime_new;
                            $endtime1   = time12hrs($no_schedule["end_time"]);
                            $eendtime   = $endtime_new;
                            $totalhours = $no_schedule["total_hours"];
                            $etime_name = "Your shift on this day starts at: {$starttime1} and ends at {$endtime1}";
                            $threshold_mins = $no_schedule["threshold_mins"];
                        }
                    }
                } else {
                    $etime_name = $etime_name;
                }
            }
            
            if ($rest_day) {
                $eendtime = date("Y-m-d", strtotime($for_end)).' '.date("H:i:s", strtotime($endtime));
                $etime_name = "Rest Day";
            }
            
            $e_check_if_24_hrs_ttl = (strtotime($endtime) - strtotime($starttime)) / 3600 / 24;
            
            if ($e_check_if_24_hrs_ttl <= 1) {
                $efor_end_ampm = date("A", strtotime($starttime));
                $efor_start_ampm = date("A", strtotime($endtime));
                
                if ($efor_end_ampm == "PM" && $efor_start_ampm == "AM") {
                    // do nothing
                } else {
                    if($is_hol) {
                        if($is_holiday) {
                            if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "yes"){
                                if ($if_partial == "yes") {
                                    $disabled_btn = false;
                                } else {
                                    $disabled_btn = true;
                                    $locked = "You can not apply for a leave on a Holiday.";
                                }
                            } else {
                                if($is_holiday->hour_type_name == "Special Holiday") {
                                    // exclude regular holiday only
                                    if($exclude_holidays == 'yes' && $exclude_regular_holidays == "no" && $exclude_special_holidays == "yes"){
                                        if ($if_partial == "yes") {
                                            $disabled_btn = false;
                                        } else {
                                            $disabled_btn = true;
                                            $locked = "You can not apply for a leave on a Special Holiday.";
                                        }
                                    }
                                }
                                
                                if($is_holiday->hour_type_name == "Regular Holiday") {
                                    // exclude regular holiday only
                                    if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "no"){
                                        if ($if_partial == "yes") {
                                            $disabled_btn = false;
                                        } else {
                                            $disabled_btn = true;
                                            $locked = "You can not apply for a leave on a Regular Holiday.";
                                        }
                                    }
                                }
                            }
                            
                        }
                    }
                    
                    if ($etime_name == "Rest Day") {
                        if($exclude_rest_days == 'yes'){
                            if ($if_partial == "yes") {
                                $disabled_btn = false;
                            } else {
                                $locked = "You can not apply for a leave on a Rest Day.";
                                $disabled_btn = true;
                            }
                        }
                    }
                }
            } else {
                if($is_hol) {
                    if($is_holiday) {
                        if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "yes"){
                            if ($if_partial == "yes") {
                                $disabled_btn = false;
                            } else {
                                $disabled_btn = true;
                                $locked = "You can not apply for a leave on a Holiday.";
                            }
                        } else {
                            if($is_holiday->hour_type_name == "Special Holiday") {
                                // exclude regular holiday only
                                if($exclude_holidays == 'yes' && $exclude_regular_holidays == "no" && $exclude_special_holidays == "yes"){
                                    if ($if_partial == "yes") {
                                        $disabled_btn = false;
                                    } else {
                                        $disabled_btn = true;
                                        $locked = "You can not apply for a leave on a Special Holiday.";
                                    }
                                }
                            }
                            
                            if($is_holiday->hour_type_name == "Regular Holiday") {
                                // exclude regular holiday only
                                if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "no"){
                                    if ($if_partial == "yes") {
                                        $disabled_btn = false;
                                    } else {
                                        $disabled_btn = true;
                                        $locked = "You can not apply for a leave on a Regular Holiday.";
                                    }
                                }
                            }
                        }
                        
                    }
                }
                
                if ($etime_name == "Rest Day") {
                    if($exclude_rest_days == 'yes'){
                        if ($if_partial == "yes") {
                            $disabled_btn = false;
                        } else {
                            $locked = "You can not apply for a leave on a Rest Day.";
                            $disabled_btn = true;
                        }
                    }
                    
                }
            }
        } else {
            $etime_name = "";
            if($time_name == "Rest Day") {
                $etime_name = "Rest Day";
            }
            
        }
        
        // if start date is flexi
        if ($valid_flexi_date != "1970-01-01" || $valid_flexi_date != "" || $valid_flexi_date != null) {
            if (strtotime($valid_flexi_date) >= strtotime($for_end)) {
                $for_end = $valid_flexi_date;
            }
        }
        
        $check_if_24_hrs_ttl = (strtotime($for_end) - strtotime($for_start)) / 3600 / 24;
        $date_range_filed = dateRange($for_start, $for_end);
        if ($check_if_24_hrs_ttl <= 1) {
            $for_end_ampm = date("A", strtotime($starttime));
            $for_start_ampm = date("A", strtotime($endtime));
            
            if ($for_end_ampm == "PM" && $for_start_ampm == "AM") {
                $date_range_filed = dateRange($for_start, $for_start);
            }
        }
        
        if (count($date_range_filed) > 1) {
            $date_range_filed_count = count($date_range_filed);
            $count = 0;
            foreach ($date_range_filed as $drf) {
                $ws_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$drf);
                $cwt = $this->employee->work_schedule_type($ws_id, $this->company_id);
                $rest_day = $this->ews->get_rest_day($this->company_id,$ws_id,date("l",strtotime($drf)));
                
                $drf_m_d = date("m-d", strtotime($drf));
                $is_holiday = in_array_custom("date-{$drf_m_d}",$data['holidays']);
                #p($is_holiday);
                $count++;
                
                if($work_schedule_id != $ws_id) {
                    $locked = "<p style='color: #EE6D54; font-size: 12px;'><strong>Warning</strong> :
                            You leave application crosses two different shifts.
                            This will cause a mismatch on your total hours of leave applied if you proceed.
                            Please apply two separate leaves to cover for the period where you have two different shifts assigned.<p>";
                }
                
                if($is_holiday){
                    #if($is_holiday->repeat_type == "no"){
                    if($is_holiday->date_type == "fixed") {
                        #$cur_year = date("Y");
                        #$hol_year = date("Y",strtotime($drf));
                        
                        $app_m_d = date("m-d",strtotime($drf));
                        $hol_m_d = date("m-d",strtotime($is_holiday->date));
                        
                        if($app_m_d == $hol_m_d){
                            #if($cur_year == $hol_year){
                            $is_hol = true;
                        } else {
                            $is_hol = false;
                        }
                    } else {
                        $is_hol = true;
                    }
                }
                
                if($is_hol){
                    if($is_holiday) {
                        // exclude special and regular holiday only
                        if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "yes"){
                            $disabled_btn = true;
                            $locked = "There is a conflict with the leave dates chosen. You can not apply for a leave on a Holiday.";
                        } else {
                            if($is_holiday->hour_type_name == "Special Holiday") {
                                // exclude regular holiday only
                                if($exclude_holidays == 'yes' && $exclude_regular_holidays == "no" && $exclude_special_holidays == "yes"){
                                    $disabled_btn = true;
                                    $locked = "There is a conflict with the leave dates chosen. You can not apply for a leave on a Special Holiday.";
                                }
                            }
                            
                            if($is_holiday->hour_type_name == "Regular Holiday") {
                                // exclude regular holiday only
                                if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "no"){
                                    $disabled_btn = true;
                                    $locked = "There is a conflict with the leave dates chosen. You can not apply for a leave on a Regular Holiday.";
                                }
                            }
                        }
                        
                    }
                    
                }
                
                if($rest_day){
                    if ($exclude_rest_days == 'yes') {
                        if ($date_range_filed_count != $count) {
                            $disabled_btn = true;
                            $locked = "There is a conflict with the leave dates chosen. You can not apply for a leave on a Rest Day.";
                        }
                        
                    }
                    
                }
                
                if (!$rest_day) {
                    if ($check_work_type != $cwt) {
                        $disabled_btn = true;
                        $locked = "There is a conflict with the shift assigned for the leave dates chosen.
                                    You can only apply for multi-day leaves if the shift assigned for that period are similar.
                                    Please send request to change your shift on these dates so you will be able to proceed.";
                    }
                }
            }
        }
        
        $html = "";
        $html1 = "";

        // trap the effectiveness of leave
        if($for_end == "1970-01-01") {
            $eff_date_range = dateRange($employee_timein_date, $employee_timein_date);
        } else {
            $eff_date_range = dateRange($for_start, $for_end);
        }
        
        if($eff_date_range) {
            foreach ($eff_date_range as $chk_drf) {
                $eff_date = $this->leave->get_leave_eff_date($leave_type,$this->company_id,$this->emp_id,"effective_date");
                $effective_start_date_by = $this->leave->get_leave_restriction($leave_type,'effective_start_date_by');
                $effective_start_date = $this->leave->get_leave_restriction($leave_type,'effective_start_date');
                
                if($effective_start_date_by != null && $effective_start_date != null) {
                    if(date('Y-m-d', strtotime($chk_drf)) < $eff_date) {
                        $locked = "The leave dates applied occur prior to your leave eligibility date. You will not be able to use your paid leave credits for this application.";
                        $disabled_btn = true;
                    }
                }
            }
        }

        // write warning if there's a threshold
        $threshold_message = "";
        if($threshold_mins != 0 || $threshold_mins != "") {
            $threshold_message = "naa threshold.";
        }
            
        if($get_split_block_name) {
            foreach ($get_split_block_name as $sb) {
                if($sb != FALSE){
                    $html1 .= "
                            <option value='".$sb->schedule_blocks_id."' name='schedule_blocks_id'>".$sb->block_name."</option>
                            ";
                }
            }
            
            $html .= "
                    <tr class='sched_blocks_appended split_flag' split_flag='1'>
                        <td>
                            <label class='margin-top-9'>Schedule Block</label>
                        </td>
                        <td colspan='2'>
                            <div class='select-bungot' style='margin-bottom: 15px;'>
                                <select name='schedule_blocks_id' class='select-custom schedule_blocks_id'>
                                    <option value='' name='schedule_blocks_id'></option>
                                    <option value='all' name='schedule_blocks_id'>Whole Blocks</option>
                                    {$html1}
                                </select>
                            </div>
                        </td>
                    </tr>
                    
                    <tr class='sched_blocks_appended'>
                        <td colspan='3'><span class='form-error' id='esched_blocks_id_err'></span></td>
                    </tr>
                    ";
                                    
            echo json_encode(array(
                "error" => false,
                "sched_blocks" => $html,
                "etime_out_date" => $locked,
                "work_sched_type" => ($check_work_type) ? $check_work_type : "",
                "start_time" => ($starttime) ? date("m/d/Y H:i:s", strtotime($starttime)) : date("m/d/Y H:i:s", strtotime($for_start)),
                "end_time" => date("m/d/Y H:i:s", strtotime($endtime)),
                "estart_time" => ($estarttime) ? date("m/d/Y H:i:s", strtotime($estarttime)) : date("m/d/Y H:i:s", strtotime($for_start)),
                "eend_time" => date("m/d/Y H:i:s", strtotime($eendtime)),
                "total_hrs" => $totalhours,
                "your_shift" => ($holiday_name != "") ? $holiday_name : $time_name,
                "eyour_shift" => ($eholiday_name != "") ? $eholiday_name : $etime_name,
                "submit_btn" => $disabled_btn,
                "full_flex" => $full_flex,
                "valid_flexi_enddate" => date("m/d/Y", strtotime($valid_flexi_date)),
                "if_NS" => $if_NS,
                "threshold_message" => $threshold_message,
                "asdsad" => $date_range_filed
            ));
            
            return false;
        } else {
            
            echo json_encode(array(
                "error" => true,
                "sched_blocks" => "",
                "etime_out_date" => $locked,
                "your_shift" => ($holiday_name != "") ? $holiday_name : $time_name,
                "eyour_shift" => ($eholiday_name != "") ? $eholiday_name : $etime_name,
                "work_sched_type" => ($check_work_type) ? $check_work_type : "",
                "start_time" => ($starttime) ? date("m/d/Y H:i:s", strtotime($starttime)) : date("m/d/Y H:i:s", strtotime($for_start)),
                "end_time" => date("m/d/Y H:i:s", strtotime($endtime)),
                "estart_time" => ($estarttime) ? date("m/d/Y H:i:s", strtotime($estarttime)) : date("m/d/Y H:i:s", strtotime($for_start)),
                "eend_time" => date("m/d/Y H:i:s", strtotime($eendtime)),
                "total_hrs" => $totalhours,
                "submit_btn" => $disabled_btn,
                "full_flex" => $full_flex,
                "valid_flexi_enddate" => date("m/d/Y", strtotime($valid_flexi_date)),
                "if_NS" => $if_NS,
                "threshold_message" => $threshold_message,
                "asdsad" => $date_range_filed
            ));
            return false;
        }
    
    }

    public function get_total_leaves() {

        $leave_type        = $this->input->post('leave_type');
        $start_date        = date("Y-m-d",strtotime($this->input->post('start_date')));
        $start_time        = date("H:i:s",strtotime($this->input->post('start_time')));
        $shift_date        = $start_date; //date("Y-m-d",strtotime($this->input->post('shift_date')));
        $end_date          = date("Y-m-d",strtotime($this->input->post('end_date')));
        $end_time          = date("H:i:s",strtotime($this->input->post('end_time')));
        $no_of_hrs_flexi   = $this->input->post('flexi_hrs');
        $lunch_hr_required = $this->input->post('lunch_hr_required');
        $if_partial        = $this->input->post('if_partial');
        $if_NS             = $this->input->post('if_NS');
        
        $concat_start_datetime  = $start_time; //date("H:i:s",strtotime($start_date_hr.":".$start_date_min." ".$start_date_sec));
        $concat_start_date      = $start_date." ".$concat_start_datetime;
        $concat_end_datetime    = $end_time; //date("H:i:s",strtotime($end_date_hr.":".$end_date_min." ".$end_date_sec));
        $concat_end_date        = $end_date." ".$concat_end_datetime;
        
        $schedule_blocks_id    = $this->input->post('schedule_blocks_id');
        
        $currentdate       = $start_date;
        $work_schedule_id  = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$currentdate);
        
        $exclude_holidays  = $this->leave->get_leave_restriction($leave_type,'exclude_holidays');
        $exclude_rest_days = $this->leave->get_leave_restriction($leave_type,'exclude_rest_days');
        $leave_units       = $this->leave->get_leave_restriction($leave_type,'leave_units');
        $per_day_credit    = $this->prm->average_working_hours_per_day($this->company_id);
        
        $total_leave_request = 0;
        $tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($this->company_id,$work_schedule_id);
        
        $is_work = is_break_assumed($work_schedule_id);
        if($tardiness_rule_migrated_v3) {
            $is_work = false;
        }
        if($work_schedule_id != FALSE){
            $get_open_shift_leave = $this->employee_v2->get_open_shift_leave($work_schedule_id, $this->company_id);
            $check_work_type = $this->employee->work_schedule_type($work_schedule_id, $this->company_id);
            $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($start_date)));
            $get_current_shift = $this->employee->get_current_shift($work_schedule_id, $this->company_id, date("Y-m-d",strtotime($start_date)), $this->emp_id);
            if ($rest_day) {
                if ($if_NS == "yes" && $if_partial == "yes") {
                    $work_schedule_idx  = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($start_date.' -1 day')));
                    $get_current_shift = $this->employee->get_current_shift($work_schedule_idx, $this->company_id, date("Y-m-d",strtotime($start_date.' -1 day')), $this->emp_id);
                }
            }
            
            $shift_date_end = false;
            $ur_start = false;
            $ur_end = false;
            if ($get_current_shift) {
                foreach ($get_current_shift as $zz) {
                    if($zz->work_type_name == "Flexible Hours") {
                        $ur_start = date("A",strtotime($zz->latest_time_in_allowed));
                        
                        $total_hours_for_the_day = $zz->total_hours_for_the_day * 60;
                        $latest_time_out_allowed = date("H:i:s",strtotime($zz->latest_time_in_allowed.' +'.$total_hours_for_the_day.' minutes'));
                        $boundary_end_to_new_start = $latest_time_out_allowed;
                        $the_new_start = date("H:i:s", strtotime($concat_start_date));
                        
                        $ur_end = date("A",strtotime($latest_time_out_allowed));
                        
                        $input_start_datetime = date("A",strtotime($concat_start_date));
                        $input_end_datetime = date("A",strtotime($concat_end_date));
                        
                        if($ur_start == "PM" && $ur_end == "AM") {
                            if(strtotime($boundary_end_to_new_start) < strtotime($the_new_start)) {
                                $shift_date = date("Y-m-d", strtotime($start_date));
                            } else {
                                if($input_start_datetime == "AM" && $input_end_datetime == "AM") {
                                    $shift_date = date("Y-m-d", strtotime($start_date." -1 day"));
                                } else {
                                    if(strtotime($boundary_end_to_new_start) >= strtotime($the_new_start)){
                                        $shift_date = $start_date;
                                    }
                                }
                            }
                        }
                    } else {
                        $ur_start = date("A",strtotime($zz->start));
                        $ur_end = date("A",strtotime($zz->end));
                        
                        $input_start_datetime = date("A",strtotime($concat_start_date));
                        $input_end_datetime = date("A",strtotime($concat_end_date));
                        
                        if($ur_start == "PM" && $ur_end == "AM") {
                            if($input_start_datetime == "AM" && $input_end_datetime == "AM") {
                                $shift_date = date("Y-m-d", strtotime($start_date." -1 day"));
                            }
                        }
                    }
                }
            }
            
            $check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($start_date)));

            // check workday
            $check_workday = $this->employee->check_workday_ws($this->emp_id,$this->company_id,$work_schedule_id);
            $start_date_req = date("l",strtotime($start_date));
            $end_date_req = date("l",strtotime($end_date));
            $check_regular = $this->employee->check_regular_ws($this->emp_id,$this->company_id,$work_schedule_id,$start_date_req);
            $check_workshift = $this->employee->check_workshift_ws($this->emp_id,$this->company_id,$work_schedule_id);
            
            $leave_units = $this->leave->get_leave_restriction($leave_type,'leave_units');
            $date1 = $concat_start_date;
            $date2 = $concat_end_date;
            
            $work_schedule_idx  = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$end_date);
            #$rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($start_date)));
            if ($rest_day) {
                $check_workday = $this->employee->check_workday_ws($this->emp_id,$this->company_id,$work_schedule_idx);
                $check_regular = $this->employee->check_regular_ws($this->emp_id,$this->company_id,$work_schedule_idx,$end_date_req);
                $check_workshift = $this->employee->check_workshift_ws($this->emp_id,$this->company_id,$work_schedule_idx);
                $check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_idx,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($end_date)));
                
                if ($if_NS == "yes" && $if_partial == "yes") {
                    $work_schedule_id_minus1  = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($start_date.' -1 day')));
                    #$rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id_minus1,date("l",strtotime($start_date.' -1 day')));
                    
                    #$check_workday = $this->employee->check_workday_ws($this->emp_id,$this->company_id,$work_schedule_id_minus1);
                    $check_regular = $this->employee->check_regular_ws($this->emp_id,$this->company_id,$work_schedule_id_minus1,date("l",strtotime($start_date.' -1 day')));
                    $check_workshift = $this->employee->check_workshift_ws($this->emp_id,$this->company_id,$work_schedule_id_minus1);
                    /*$check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_id_minus1,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($start_date.' -1 day')));
                    $get_schedule_settings  = get_schedule_settings_by_workschedule_id($work_schedule_id,$this->company_id,date("l", strtotime($start_date.' -1 day')));*/
                    #p($check_regular);
                }
            }
            
            if($check_workday != FALSE){
                $total_leave_request = 0;
                
                // less than 24 hours
                $fl_time = $check_workday->latest_time_in_allowed;
                $duration_of_lunch_break_per_day = $check_workday->duration_of_lunch_break_per_day / 60;
                $fl_hours_worked = $check_workday->total_hours_for_the_day - $duration_of_lunch_break_per_day;
                
                $total_leave_request = 0;
                $total_leave_request = $no_of_hrs_flexi;
                if($no_of_hrs_flexi) {
                    if($leave_units == 'days') {
                        $total_leave_request = $no_of_hrs_flexi / $fl_hours_worked;
                    } else {
                        $total_leave_request = $no_of_hrs_flexi / $per_day_credit;
                    }
                } else {
                    
                    /*if ($fl_time != "" || $fl_time != null) {
                        $new_endtime = $start_date.' '.$fl_time;
                        $fl_hours_worked_mins = $fl_hours_worked * 60;
                        $new_starttime = date("Y-m-d H:i:s", strtotime($new_endtime.' -'.$fl_hours_worked_mins.' minutes'));
                        
                        $i_start_datetime = date("A",strtotime($new_starttime));
                        $i_end_datetime = date("A",strtotime($new_endtime));
                        if($i_start_datetime == "PM" && $i_end_datetime == "AM") {
                            $end_date = date("Y-m-d", strtotime($end_date.' -1 day'));
                        }
                    }*/
                    
                    $days_filed = dateRange($start_date, $end_date);
                    $total_days_filed = 0;
                    if($days_filed) {
                        foreach ($days_filed as $date) {
                            $is_holiday = $this->employee->get_holiday_date($date,$this->emp_id,$this->company_id);
                            $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($date)));
                            
                            // exclude holiday
                            if($exclude_holidays != 'yes'){
                                $is_holiday = false;
                            }
                            
                            // exclude rest day
                            #if($exclude_rest_days != 'yes'){
                            #    $rest_day = true;
                            #}
                            
                            if(!$is_holiday && !$rest_day){
                                if($rest_day && $exclude_rest_days == 'yes'){
                                #if($exclude_rest_days != 'yes'){
                                    $total_days_filed += 0;
                                } else {
                                    $total_days_filed += 1;
                                   
                                }
                                
                            }
                        }
                    }
                    
                    $total_days_filed_credited = $total_days_filed * $fl_hours_worked;
                    if($leave_units == 'days') {
                        $total_leave_request = $total_days_filed_credited / $fl_hours_worked;
                    } else {
                        $total_leave_request = $total_days_filed_credited / $per_day_credit;
                    }
                    
                }
                
            } elseif ($get_open_shift_leave){
                $total_leave_request = 0;
                
                // less than 24 hours
                $fl_hours_worked = $get_open_shift_leave->total_hrs_per_day;
                
                $total_leave_request = 0;
                $total_leave_request = $no_of_hrs_flexi;
                if($no_of_hrs_flexi) {
                    if($leave_units == 'days') {
                        $total_leave_request = $no_of_hrs_flexi / $fl_hours_worked;
                    } else {
                        $total_leave_request = $no_of_hrs_flexi / $per_day_credit;
                    }
                } else {
                    $days_filed = dateRange($start_date, $end_date);
                    $total_days_filed = 0;
                    if($days_filed) {
                        foreach ($days_filed as $date) {
                            $is_holiday = $this->employee->get_holiday_date($date,$this->emp_id,$this->company_id);
                            
                            // exclude holiday
                            if($exclude_holidays != 'yes'){
                                $is_holiday = false;
                            }
                            
                            if(!$is_holiday){
                                $total_days_filed += 1;
                            }
                        }
                    }
                    
                    $total_days_filed_credited = $total_days_filed * $fl_hours_worked;
                    if($leave_units == 'days') {
                        $total_leave_request = $total_days_filed_credited / $fl_hours_worked;
                    } else {
                        $total_leave_request = $total_days_filed_credited / $per_day_credit;
                    }
                    
                }
                
            } elseif($check_regular != FALSE){
                foreach($check_regular as $cr){
                    $req_hours_work = $cr->total_work_hours;
                }
                
                // for uniform working days, workshift
                // less than 24 hours
                $date1 = $concat_start_date;
                $date2 = $concat_end_date;
                
                $date_timein    = date("H:i:s",strtotime($date1));
                $date_timeout   = date("H:i:s",strtotime($date2));
                
                $check_hours        = strtotime(date("Y-m-d H:i:s",strtotime($date2))) - strtotime(date("Y-m-d H:i:s",strtotime($date1)));
                $total_leave_hours  = $check_hours / 3600;
                $total_hours        = $check_hours / 3600 / 24;
                
                $total_leave_request    = 0;
                $tl1                    = 0;
                
                // check parameter
                $check_date             = strtotime(date("Y-m-d",strtotime($date1)));
                $hours_worked           = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id);
                $hours_worked_break     = $this->employee->for_leave_hoursworked_break($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id);
                $work_start_time        = $this->employee->for_leave_hoursworked_work_start_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,$concat_start_date);
                $work_end_time          = $this->employee->for_leave_hoursworked_work_end_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,$concat_start_date);
                
                $check_date_and_time_in = $this->employee->check_date_and_time_in($start_date, $this->emp_id, $this->company_id);
                $get_schedule_settings  = get_schedule_settings_by_workschedule_id($work_schedule_id,$this->company_id,date("l", strtotime($shift_date)));
                $check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($shift_date)));
                
                if($tardiness_rule_migrated_v3) {
                    if($check_break_time_for_assumed) {
                        
                        $grace              = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
                        $add_datex          = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$grace." minutes"));
                        $add_datey          = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
                        $work_start_time    = date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
                        #$work_end_time      = date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time));
                        
                        if(strtotime($concat_start_date) > strtotime($add_datex)) {
                            if($check_date_and_time_in) {
                                if (strtotime($check_date_and_time_in->time_in) >= strtotime($add_datey)) {
                                    $start_new      = $check_date_and_time_in->time_in;
                                    $start_new_diff = (strtotime($add_datey) - strtotime($check_date_and_time_in->time_in)) / 60;
                                    
                                    if($start_new_diff < 0){
                                        $start_new_diff = (strtotime($check_date_and_time_in->time_in) - strtotime($add_datey)) / 60;
                                    }
                                    
                                    $add_date       = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$start_new_diff." minutes"));
                                    $new_end_date   = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
                                    
                                    if(strtotime($new_end_date) >= strtotime($concat_end_date)) {
                                        $date2          = $concat_end_date;
                                        #$work_end_time  = date('H:i:s', strtotime($concat_end_date));
                                    } else {
                                        $date2          = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
                                        #$work_end_time  = date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
                                    }
                                }
                            } else {
                                $date2      = $end_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time));
                                $add_date   = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time));
                                
                            }
                        } elseif (strtotime($concat_start_date) >= strtotime($add_datey) && strtotime($concat_start_date) <= strtotime($add_datex)) {
                            $start_new      = $concat_start_date;
                            $start_new_diff = (strtotime($add_datey) - strtotime($concat_start_date)) / 60;
                            
                            if($start_new_diff < 0){
                                $start_new_diff = (strtotime($concat_start_date) - strtotime($add_datey)) / 60;
                            }
                            
                            $add_date       = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$start_new_diff." minutes"));
                            $new_end_date   = $start_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_end_time." +".$start_new_diff." minutes"));
                            
                            #$work_end_time = date('H:i:s', strtotime($concat_end_date));
                        } else {
                            if (strtotime($add_datey) >= strtotime($concat_start_date)) {
                                $add_date = $shift_date.' '.$check_break_time_for_assumed->work_start_time;
                            } elseif (strtotime($concat_start_date) >= strtotime($add_datey)) {
                                $start_new      = $concat_start_date;
                                $start_new_diff = (strtotime($add_datey) - strtotime($concat_start_date)) / 60;
                                
                                if($start_new_diff < 0){
                                    $start_new_diff = (strtotime($concat_start_date) - strtotime($add_datey)) / 60;
                                }
                                
                                $add_date = $shift_date.' '.date('H:i:s', strtotime($check_break_time_for_assumed->work_start_time." +".$start_new_diff." minutes"));
                            }
                        }
                        
                        $h = $hours_worked / 2;
                        $h = $h * 60;
                        
                        $hours_worked_break_l   = 0;
                        $hours_worked_break_b1  = 0;
                        $hours_worked_break_b2  = 0;
                        
                        if($get_schedule_settings->enable_lunch_break == "yes") {
                            if($get_schedule_settings->break_schedule_1 == "fixed") {
                                if($get_schedule_settings->break_type_1 == "unpaid") {
                                    $break_started_after_in_mins = $get_schedule_settings->break_started_after * 60;
                                    
                                    $lunch_out  = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins} minutes"));
                                    $lunch_in   = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$get_schedule_settings->break_in_min} minutes"));
                                    
                                    if(strtotime($concat_start_date) < strtotime($lunch_out) && strtotime($concat_start_date) > strtotime($lunch_in)) {
                                        $hours_worked_break_l = $hours_worked_break;
                                    } elseif(strtotime($concat_start_date) <= strtotime($lunch_out) && strtotime($concat_end_date) >= strtotime($lunch_in)) {
                                        $hours_worked_break_l = $hours_worked_break;
                                    } elseif(strtotime($concat_start_date) >= strtotime($lunch_out) && strtotime($concat_start_date) < strtotime($lunch_in)) {
                                        $hours_worked_break_l = $hours_worked_break;
                                    } elseif(strtotime($concat_end_date) >= strtotime($lunch_out) && strtotime($concat_end_date) <= strtotime($lunch_in)) {
                                        $hours_worked_break_l = $hours_worked_break;
                                        $date2 = $lunch_in;
                                    } else {
                                        $hours_worked_break_l = 0;
                                    }
                                }
                            } elseif($get_schedule_settings->break_schedule_1 == "flexi" || $get_schedule_settings->track_break_1 == "no") {
                                if($get_schedule_settings->break_type_1 == "unpaid") {
                                    $total_hrs_of_break_mins = $get_schedule_settings->total_work_hours;
                                    $break_started_after_in_mins = ($total_hrs_of_break_mins / 2) * 3600;
                                    
                                    $lunch_out  = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins} seconds"));
                                    $lunch_in   = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$get_schedule_settings->break_in_min} minutes"));
                                    
                                    if(strtotime($concat_start_date) < strtotime($lunch_out) && strtotime($concat_start_date) > strtotime($lunch_in)) {
                                        $hours_worked_break_l = $hours_worked_break;
                                    } elseif(strtotime($concat_start_date) <= strtotime($lunch_out) && strtotime($concat_end_date) >= strtotime($lunch_in)) {
                                        $hours_worked_break_l = $hours_worked_break;
                                    } elseif(strtotime($concat_start_date) >= strtotime($lunch_out) && strtotime($concat_start_date) < strtotime($lunch_in)) {
                                        $hours_worked_break_l = $hours_worked_break;
                                    } elseif(strtotime($concat_end_date) >= strtotime($lunch_out) && strtotime($concat_end_date) <= strtotime($lunch_in)) {
                                        $hours_worked_break_l = $hours_worked_break;
                                        $date2 = $lunch_in;
                                    } else {
                                        $hours_worked_break_l = 0;
                                    }
                                }
                            }
                        }
                        
                        if($get_schedule_settings->enable_additional_breaks == "yes") {
                            if($get_schedule_settings->break_schedule_2 == "fixed") {
                                if($get_schedule_settings->break_type_2 == "unpaid") {
                                    if($get_schedule_settings->num_of_additional_breaks > 0) {
                                        if($get_schedule_settings->additional_break_started_after_1 != "") {
                                            $break_started_after_in_mins = $get_schedule_settings->additional_break_started_after_1 * 60;
                                            
                                            $break_1        = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins} minutes"));
                                            $break_1_end    = date('Y-m-d H:i:s',strtotime($break_1. " +{$get_schedule_settings->break_1_in_min} minutes"));
                                            
                                            if(strtotime($concat_start_date) < strtotime($break_1) && strtotime($concat_start_date) > strtotime($break_1_end)) {
                                                $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
                                            } elseif(strtotime($concat_start_date) <= strtotime($break_1) && strtotime($concat_end_date) >= strtotime($break_1_end)) {
                                                $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
                                            } elseif(strtotime($concat_start_date) >= strtotime($break_1) && strtotime($concat_start_date) < strtotime($break_1_end)) {
                                                $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
                                            } else {
                                                $hours_worked_break_b1 = 0;
                                            }
                                        }
                                        
                                        if($get_schedule_settings->additional_break_started_after_2 != "") {
                                            $break_started_after_in_mins = $get_schedule_settings->additional_break_started_after_2 * 60;
                                            
                                            $break_2        = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins} minutes"));
                                            $break_2_end    = date('Y-m-d H:i:s',strtotime($break_2. " +{$get_schedule_settings->break_2_in_min} minutes"));
                                            
                                            if(strtotime($concat_start_date) < strtotime($break_2) && strtotime($concat_start_date) > strtotime($break_2_end)) {
                                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
                                            } elseif(strtotime($concat_start_date) <= strtotime($break_2) && strtotime($concat_end_date) >= strtotime($break_2_end)) {
                                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
                                            } elseif(strtotime($concat_start_date) >= strtotime($break_2) && strtotime($concat_start_date) < strtotime($break_2_end)) {
                                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
                                            } else {
                                                $hours_worked_break_b2 = 0;
                                            }
                                        }
                                    }
                                }
                            } elseif($get_schedule_settings->break_schedule_2 == "flexi" || $get_schedule_settings->track_break_2 == "no") {
                                if($get_schedule_settings->break_type_2 == "unpaid") {
                                    if($get_schedule_settings->num_of_additional_breaks > 0) {
                                        // first half
                                        if($get_schedule_settings->break_1_in_min != "") {
                                            if($get_schedule_settings->num_of_additional_breaks == 1) {
                                                $total_hrs_of_break_mins            = $get_schedule_settings->total_work_hours;
                                                $break_started_after_in_mins        = $total_hrs_of_break_mins / 2;
                                                $break_started_after_in_mins_half   = ($break_started_after_in_mins / 2) * 3600;
                                                $break_started_after_in_mins_lunch  = ($total_hrs_of_break_mins / 2) * 3600;
                                                
                                                // get the lunch in to come up the time of 2nd break time
                                                $lunch_out  = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins_lunch} seconds"));
                                                $lunch_in   = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$get_schedule_settings->break_in_min} minutes"));
                                                
                                                // 2nd break time
                                                $start_break_1  = date('Y-m-d H:i:s',strtotime($lunch_in. " +{$break_started_after_in_mins_half} seconds"));
                                                $end_break_1    = date('Y-m-d H:i:s',strtotime($start_break_1. " +{$get_schedule_settings->break_1_in_min} minutes"));
                                                
                                                if(strtotime($concat_start_date) < strtotime($start_break_1) && strtotime($concat_start_date) > strtotime($end_break_1)) {
                                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
                                                } elseif(strtotime($concat_start_date) <= strtotime($start_break_1) && strtotime($concat_end_date) >= strtotime($end_break_1)) {
                                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
                                                } elseif(strtotime($concat_start_date) >= strtotime($start_break_1) && strtotime($concat_start_date) < strtotime($end_break_1)) {
                                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
                                                } else {
                                                    $hours_worked_break_b1 = 0;
                                                }
                                            } else {
                                                $total_hrs_of_break_mins            = $get_schedule_settings->total_work_hours;
                                                $break_started_after_in_mins        = $total_hrs_of_break_mins / 2;
                                                $break_started_after_in_mins_half   = ($break_started_after_in_mins / 2) * 3600;
                                                
                                                // first break time
                                                $start_break_1  = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins_half} seconds"));
                                                $end_break_1    = date('Y-m-d H:i:s',strtotime($start_break_1. " +{$get_schedule_settings->break_1_in_min} minutes"));
                                                
                                                if(strtotime($concat_start_date) < strtotime($start_break_1) && strtotime($concat_start_date) > strtotime($end_break_1)) {
                                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
                                                } elseif(strtotime($concat_start_date) <= strtotime($start_break_1) && strtotime($concat_end_date) >= strtotime($end_break_1)) {
                                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
                                                } elseif(strtotime($concat_start_date) >= strtotime($start_break_1) && strtotime($concat_start_date) < strtotime($end_break_1)) {
                                                    $hours_worked_break_b1 = $get_schedule_settings->break_1_in_min;
                                                } else {
                                                    $hours_worked_break_b1 = 0;
                                                }
                                            }
                                        }
                                        
                                        // second half
                                        if($get_schedule_settings->break_2_in_min != "") {
                                            $total_hrs_of_break_mins            = $get_schedule_settings->total_work_hours;
                                            $break_started_after_in_mins        = $total_hrs_of_break_mins / 2;
                                            $break_started_after_in_mins_half   = ($break_started_after_in_mins / 2) * 3600;
                                            $break_started_after_in_mins_lunch  = ($total_hrs_of_break_mins / 2) * 3600;
                                            
                                            // get the lunch in to come up the time of 2nd break time
                                            $lunch_out  = date('Y-m-d H:i:s',strtotime($add_date. " +{$break_started_after_in_mins_lunch} seconds"));
                                            $lunch_in   = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$get_schedule_settings->break_in_min} minutes"));
                                            
                                            // 2nd break time
                                            $start_break_2  = date('Y-m-d H:i:s',strtotime($lunch_in. " +{$break_started_after_in_mins_half} seconds"));
                                            $end_break_2    = date('Y-m-d H:i:s',strtotime($start_break_2. " +{$get_schedule_settings->break_2_in_min} minutes"));
                                            
                                            if(strtotime($concat_start_date) < strtotime($start_break_2) && strtotime($concat_start_date) > strtotime($end_break_2)) {
                                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
                                            } elseif(strtotime($concat_start_date) <= strtotime($start_break_2) && strtotime($concat_end_date) >= strtotime($end_break_2)) {
                                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
                                            } elseif(strtotime($concat_start_date) >= strtotime($start_break_2) && strtotime($concat_start_date) < strtotime($end_break_2)) {
                                                $hours_worked_break_b2 = $get_schedule_settings->break_2_in_min;
                                            } else {
                                                $hours_worked_break_b2 = 0;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        $hours_worked_break = $hours_worked_break_l + $hours_worked_break_b1 + $hours_worked_break_b2;
                    }
                }
                
                $end_shift_date     = date("Y-m-d", strtotime($concat_end_date));
                $applied_date       = dateRange($shift_date, $end_shift_date);
                
                if($ur_start && $ur_end) {
                    if($ur_start == "PM" && $ur_end == "AM") {
                        $applied_date       = dateRange(date("Y-m-d", strtotime($concat_start_date)), $end_shift_date);
                    }
                }
                
                $check_hours        = strtotime(date("Y-m-d H:i:s",strtotime($concat_end_date))) - strtotime(date("Y-m-d H:i:s",strtotime($concat_start_date)));
                $total_leave_hours  = $check_hours / 3600;
                $total_hours        = $check_hours / 3600 / 24;
                
                if($total_hours < 1) {
                    // total leave for start date
                    $date1_date_add_oneday  = date("Y-m-d",strtotime($shift_date));
                    $is_holiday_tl1         = $this->employee->get_holiday_date($shift_date,$this->emp_id,$this->company_id);
                    #$rest_day               = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($shift_date)));
                    #p($rest_day);
                    if ($rest_day) {
                        /*$hours_worked           = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date2)),$work_schedule_idx);
                        $hours_worked_break     = $this->employee->for_leave_hoursworked_break($this->emp_id,$this->company_id,date("l",strtotime($date2)),$work_schedule_idx);
                        $work_start_time        = $this->employee->for_leave_hoursworked_work_start_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_idx,$concat_end_date);
                        $work_end_time          = $this->employee->for_leave_hoursworked_work_end_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_idx,$concat_end_date);
                        $get_schedule_settings  = get_schedule_settings_by_workschedule_id($work_schedule_idx,$this->company_id,date("l", strtotime($end_date)));*/
                       
                        if ($if_NS == "yes" && $if_partial == "yes") {
                            $work_schedule_id_minus1  = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($start_date.' -1 day')));
                            $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id_minus1,date("l",strtotime($start_date.' -1 day')));
                            #$shift_date = $start_date = date("Y-m-d", strtotime($shift_date.' -1 day'));
                            
                            $hours_worked           = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($start_date.' -1 day')),$work_schedule_id_minus1);
                            $hours_worked_break     = $this->employee->for_leave_hoursworked_break($this->emp_id,$this->company_id,date("l",strtotime($start_date.' -1 day')),$work_schedule_id_minus1);
                            $work_start_time        = $this->employee->for_leave_hoursworked_work_start_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id_minus1,$shift_date.' '.date("H:i:s", strtotime($concat_start_date)));
                            $work_end_time          = $this->employee->for_leave_hoursworked_work_end_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id_minus1,$shift_date.' '.date("H:i:s", strtotime($concat_start_date)));
                            $check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_id_minus1,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($start_date.' -1 day')));
                            $get_schedule_settings  = get_schedule_settings_by_workschedule_id($work_schedule_id,$this->company_id,date("l", strtotime($start_date.' -1 day')));
                            #p($work_start_time.' '.$start_date.' '.date("H:i:s", strtotime($concat_start_date)));
                            #$date1_date_add_oneday  = date("Y-m-d",strtotime($shift_date.' -1 days'));
                            
                            $work_schedule_id = $work_schedule_id_minus1;
                            $date1 = date("Y-m-d", strtotime($date1.' -1 day'));
                            
                        }
                    }
                    
                    // exclude holiday
                    if($exclude_holidays != 'yes'){
                        $is_holiday_tl1 = false;
                    }
                    
                    // exclude rest day
                    #if($exclude_rest_days != 'yes'){
                    #    $rest_day = true;
                    #}
                    if($ur_start && $ur_end) {
                        $input_start_datetime   = date("A",strtotime($concat_start_date));
                        $input_end_datetime     = date("A",strtotime($concat_end_date));
                        
                        if($ur_start == "PM" && $ur_end == "AM") {
                            $shift_date_end = date("Y-m-d", strtotime($shift_date." +1 day"));
                            
                            if($input_start_datetime == "AM" && $input_end_datetime == "AM") {
                                $shift_date_end = date("Y-m-d", strtotime($shift_date." +1 day"));
                            }
                        }
                    }
                    
                    if($shift_date_end) {
                        $date1_date_add_oneday = $shift_date_end;
                    }
                    #p($rest_day);
                    if(!$is_holiday_tl1 && !$rest_day){
                        if(strtotime($shift_date) < strtotime($start_date)) {
                            $new_shift_date = $start_date;
                        } else {
                            $new_shift_date = $shift_date;
                        }
                            
                        $start_br_down_date = $new_shift_date.' '.date("H:i:s", strtotime($concat_start_date));
                        $for_inout_break    = $shift_date.' '.date("H:i:s", strtotime($concat_start_date));
                        
                        if(strtotime($date1_date_add_oneday." ".$work_end_time) > strtotime($concat_end_date)) {
                            $end_br_down_date = $concat_end_date;
                        } else {#echo $work_end_time."asds";
                            $end_br_down_date = $date1_date_add_oneday." ".$work_end_time;
                        }
                        
                        $check_start_time   = $this->employee_v2->get_leave_breaktime($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id, $for_inout_break, $end_br_down_date);
                        $check_end_time     = $this->employee_v2->get_leave_breaktime($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id, $for_inout_break, $end_br_down_date,false);
                        
                        if($rest_day && $exclude_rest_days == 'yes'){
                            $tl1 += 0;
                        } else {
                            if($leave_units == 'days') {
                                #p($start_br_down_date."---".$check_start_time."---".$check_end_time."---".$end_br_down_date);
                                #p($this->employee_v2->get_tot_hours_ws_v3($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$hours_worked,$work_schedule_id,$shift_date,$lunch_hr_required));
                                $tl1 += ($this->employee_v2->get_tot_hours_ws_v3($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$hours_worked,$work_schedule_id,$shift_date,$lunch_hr_required)) / $hours_worked;
                                #$tl1 += ($this->employee_v2->get_tot_hours_ws_v2($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$hours_worked,$work_schedule_id,$hours_worked_break,true, $shift_date)) / $hours_worked;
                            } else {
                                $tl1 += ($this->employee_v2->get_tot_hours_ws_v3($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$hours_worked,$work_schedule_id,$shift_date,$lunch_hr_required)) / $per_day_credit;
                                #$tl1 += ($this->employee_v2->get_tot_hours_ws_v2($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$hours_worked,$work_schedule_id,$hours_worked_break,true, $shift_date)) / $per_day_credit;
                            }
                        }
                    }
                } else {
                    
                    if($applied_date) {
                        $date_count = 1;
                        $date_total = count($applied_date);
                        
                        foreach ($applied_date as $breakdown_date) {
                            $last_date_of_leave = false;
                            
                            if($breakdown_date == date("Y-m-d", strtotime($concat_end_date))) {
                                $leave_br_down_date = $breakdown_date.' '.date("H:i:s", strtotime($concat_start_date));
                                
                                if(strtotime($leave_br_down_date) >= strtotime($concat_end_date)){
                                    $last_date_of_leave = true;
                                }
                            }
                            
                            // total leave for start date
                            $date1_date_add_oneday = date("Y-m-d",strtotime($breakdown_date));                                        
                            $is_holiday_tl1 = $this->employee->get_holiday_date($breakdown_date,$this->emp_id,$this->company_id);
                            $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($breakdown_date)));
                            
                            // exclude holiday
                            if($exclude_holidays != 'yes'){
                                $is_holiday_tl1 = false;
                            }
                            
                            // exclude rest day
                            #if($exclude_rest_days != 'yes'){
                             #   $rest_day = false;
                            #}
                            
                            if($ur_start && $ur_end) {
                                $input_start_datetime   = date("A",strtotime($concat_start_date));
                                $input_end_datetime     = date("A",strtotime($concat_end_date));
                                
                                if($ur_start == "PM" && $ur_end == "AM") {
                                    $date1_date_add_oneday = date("Y-m-d", strtotime($breakdown_date." +1 day"));
                                }
                            }
                            if(!$is_holiday_tl1 && !$rest_day){
                                
                                if(!$last_date_of_leave){
                                    if($date_count == 1) {
                                        #p($date_total);
                                        $new_shift_date = $shift_date;
                                        if($date_total == 1) {
                                            $work_end_time = date("H:i:s", strtotime($concat_end_date));
                                        }
                                        
                                        $start_br_down_date = $breakdown_date.' '.date("H:i:s", strtotime($work_start_time));
                                    } else {
                                        $date_cnt       = $date_count - 1;
                                        $new_shift_date = date("Y-m-d", strtotime($shift_date." +".$date_cnt." days"));
                                    }
                                    
                                    if($ur_start == "PM" && $ur_end == "AM") { // for night shift
                                        $start_br_down_date = $breakdown_date.' '.date("H:i:s", strtotime($work_start_time));
                                        $end_br_down_date = $date1_date_add_oneday." ".$work_end_time;
                                        
                                        if($date_count == 1) {
                                            if (date("A", strtotime($concat_start_date)) == "AM" && date("A", strtotime($work_end_time)) == "AM") {
                                                $start_br_down_date = date("Y-m-d", strtotime($breakdown_date.' -1 day')).' '.date("H:i:s", strtotime($work_start_time));
                                                $end_br_down_date = $breakdown_date." ".$work_end_time;
                                            }
                                           
                                        } elseif ($date_count >= 1) {
                                            #$start_br_down_date = date("Y-m-d", strtotime($breakdown_date.' -1 day')).' '.date("H:i:s", strtotime($work_start_time));
                                            $start_br_down_date = date("Y-m-d", strtotime($breakdown_date)).' '.date("H:i:s", strtotime($work_start_time));
                                            $end_br_down_date = $date1_date_add_oneday." ".$work_end_time;
                                           # p(date("Y-m-d", strtotime($breakdown_date.' -1 day')));
                                        }
                                        
                                        if(strtotime($start_br_down_date) < strtotime($concat_start_date)) {
                                            $start_br_down_date = $concat_start_date;
                                        }
                                        
                                        if(strtotime($end_br_down_date) > strtotime($concat_end_date)) {
                                            $end_br_down_date = $concat_end_date;
                                        }
                                        
                                        $check_start_time   = $this->employee_v2->get_leave_breaktime($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id, $start_br_down_date, $end_br_down_date);
                                        $check_end_time     = $this->employee_v2->get_leave_breaktime($this->emp_id,$this->company_id,date("l",strtotime($date1)),$work_schedule_id, $start_br_down_date, $end_br_down_date,false);
                                        
                                        if($rest_day && $exclude_rest_days == 'yes'){
                                            $tl1 += 0;
                                        } else {
                                            if($leave_units == 'days') {
                                                #p($start_br_down_date."---".$check_start_time."---".$check_end_time."---".$date1_date_add_oneday." ".$work_end_time);
                                                $tl1 += ($this->employee_v2->get_tot_hours_ws_v2($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$hours_worked,$work_schedule_id,$hours_worked_break,true, $new_shift_date)) / $hours_worked;
                                            } else {
                                                $tl1 += ($this->employee_v2->get_tot_hours_ws_v2($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$hours_worked,$work_schedule_id,$hours_worked_break,true, $new_shift_date)) / $per_day_credit;
                                            }
                                        }
                                        
                                    } else {
                                        $start_br_down_date = $breakdown_date.' '.date("H:i:s", strtotime($work_start_time));
                                        $end_br_down_date = $date1_date_add_oneday." ".$work_end_time;
                                        #p($start_br_down_date.' '.$end_br_down_date);
                                        if(strtotime($start_br_down_date) < strtotime($concat_start_date)) {
                                            $start_br_down_date = $concat_start_date;
                                        }
                                        
                                        if(strtotime($end_br_down_date) > strtotime($concat_end_date)) {
                                            $end_br_down_date = $concat_end_date;
                                        }
                                        
                                        $check_start_time   = $this->employee_v2->get_leave_breaktime($this->emp_id,$this->company_id,date("l",strtotime($breakdown_date)),$work_schedule_id, $start_br_down_date, $end_br_down_date);
                                        $check_end_time     = $this->employee_v2->get_leave_breaktime($this->emp_id,$this->company_id,date("l",strtotime($breakdown_date)),$work_schedule_id, $start_br_down_date, $end_br_down_date,false);
                                        #p($breakdown_date.' '.$start_br_down_date.' '.$end_br_down_date);
                                        if($rest_day && $exclude_rest_days == 'yes'){
                                            $tl1 += 0;
                                           
                                        } else {
                                            if($leave_units == 'days') {
                                                #p($start_br_down_date."---".$check_start_time."---".$check_end_time."---".$end_br_down_date);
                                                $tl1 += ($this->employee_v2->get_tot_hours_ws_v2($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$hours_worked,$work_schedule_id,$hours_worked_break,true, $new_shift_date)) / $hours_worked;
                                            } else {
                                                $tl1 += ($this->employee_v2->get_tot_hours_ws_v2($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$hours_worked,$work_schedule_id,$hours_worked_break,true, $new_shift_date)) / $per_day_credit;
                                            }
                                        }
                                        
                                    }
                                    
                                    $date_count += 1;
                                }
                            }
                        }
                    }
                }
                
                $total_leave_request = $tl1;
                
                if($leave_units == 'days') {
                    $hours_total = $total_leave_request * $hours_worked;
                } else {
                    $hours_total = $total_leave_request * $per_day_credit;
                }
            }elseif($check_workshift != FALSE){
                //sorry fil ako g.delete tnan split na code nmu ahahah                            
                if($schedule_blocks_id == "all") {
                    $check_if_24_hrs = (strtotime($concat_end_date) - strtotime($concat_start_date)) / 3600 / 24;
                    if($check_if_24_hrs < 1) {
                        $end_date = date("Y-m-d", strtotime($end_date.' -1 day'));
                    }
                    
                    $range_applied = dateRange($start_date, $end_date);
                    
                    $total_leave_request = 0;
                    
                    foreach ($range_applied as $date) {
                        $work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date('Y-m-d', strtotime($date)));
                        $get_split_block_name = $this->employee->list_of_blocks(date('Y-m-d', strtotime($date)),$this->emp_id,$work_schedule_id,$this->company_id);
                        
                        $total_hours_work_all_block = 0;
                        if($get_split_block_name) {
                            foreach ($get_split_block_name as $row) {
                                $total_hours_work_all_block += $row->total_hours_work_per_block;
                            }
                            
                            if($leave_units == 'days') {
                                $overall_total_hrs = $total_hours_work_all_block / $total_hours_work_all_block;
                            } else {
                                $overall_total_hrs = $total_hours_work_all_block / $per_day_credit;
                            }
                            
                            $total_leave_request += $overall_total_hrs;
                        }
                    }
                } else {
                    $get_split_time_by_schedule_blocks_id = $this->employee->get_split_time_by_schedule_blocks_id($schedule_blocks_id,$this->company_id);
                    $break_in_min = 0;
                    if($get_split_time_by_schedule_blocks_id) {
                        $break_in_min = $get_split_time_by_schedule_blocks_id->break_in_min;
                    }
                    
                    $hours_worked = $this->employee->total_hours_for_all_split_blocks($start_date,$this->emp_id,$work_schedule_id,$this->company_id);
                    
                    $total_hrs = (strtotime($concat_end_date) - strtotime($concat_start_date)) / 3600 - ($break_in_min / 60);
                    
                    if($leave_units == 'days') {
                        $total_leave_request = $total_hrs / $hours_worked;
                    } else {
                        $total_leave_request = $total_hrs / $per_day_credit;
                    }
                }
            }
            
        }
        
        echo number_format($total_leave_request,'3','.',','); //number_format($total_leave_request,3);
        return false;
    
    }

    function submit_leaves() {
        $what_portal = "employee";
        $leave_app_status = "pending";

        $start_date         = date("Y-m-d",strtotime($this->input->post('start_date')));
        
        $result = array(
            'error'=>true,
            'err_msg'=>"Leave application is temporarily unavailable on mobile app. You may file your leave on the browser instead.",
        );
        echo json_encode($result);
        return false;
        
        // $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id, "leave",$this->emp_id, date("Y-m-d", strtotime($start_date)));
        $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id, "leave");
        if($get_lock_payroll_process_settings) {
            if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                $result = array(
                    'error'=>true,
                    'err_msg'=>$get_lock_payroll_process_settings->application_error,
                );
                echo json_encode($result);
                return false;
            } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                $result = array(
                    'error'=>true,
                    'err_msg'=>$get_lock_payroll_process_settings->ts_app_recalculation_err_msg,
                );
                echo json_encode($result);
                return false;
            } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                $result = array(
                    'error'=>true,
                    'err_msg'=>$get_lock_payroll_process_settings->py_app_recalculation_err_msg,
                );
                echo json_encode($result);
                return false;
            }
        }

		$this->emp_id = $this->emp_id;
					
		$flag = $this->input->post('flag');
			
        $leave_type = $this->input->post('leave_type');
        $reason             = $this->input->post('reason');
        
        $start_time         = date("h:i A",strtotime($this->input->post('start_time')));
        
        $end_date           = date("Y-m-d",strtotime($this->input->post('end_date')));
        $end_time           = date("h:i A",strtotime($this->input->post('end_time')));
        
        $cont_tlr_hidden    = $this->input->post('cont_tlr_hidden');
        $previous_credits   = $this->input->post('previous_credits');
        $schedule_blocks_id = $this->input->post('schedule_blocks_id');
        
        $no_lunch_hrs       = $this->input->post('lunch_hr_required');
        $leave_request_type = $this->input->post('leave_request_type');

        $ef_date = $this->check_eff_date($leave_type);
        if ($ef_date) {
            $result = array(
                'error'=>true,
                'err_msg'=>'You are not yet eligible to use this leave.',
            );
            echo json_encode($result);
            return false;
        }


        $this->my_location = $this->employee_v2->get_location_of_emp($this->company_id,$this->emp_id)->location_and_offices_id;
        
        $lunch_hr_required = "no";
        if ($no_lunch_hrs == "1") {
            $lunch_hr_required = "yes";
        }
        
        if($start_date) {
            $ws_id              = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$start_date);
            $check_workday      = $this->employee->check_workday_ws($this->emp_id,$this->company_id,$ws_id);
            $start_date_req     = date("l",strtotime($start_date));
            $check_regular      = $this->employee->check_regular_ws($this->emp_id,$this->company_id,$ws_id,$start_date_req);
            $check_workshift    = $this->employee->check_workshift_ws($this->emp_id,$this->company_id,$ws_id);
        	$get_open_shift_leave = $this->employee_v2->get_open_shift_leave($ws_id, $this->company_id);
        } else {
            $result = array(
                'error'=>true,
                'err_msg'=>"Start Date is required."
            );
            echo json_encode($result);
            return false;
        }
        
        if($end_date == "" || $end_date == "1970-01-01"){
            $result = array(
                'error'=>true,
                'err_msg'=>"End Date is required."
            );
            echo json_encode($result);
            return false;
        }

        $please_yaw_ka_zero = is_numeric($cont_tlr_hidden);
                    
        if ($please_yaw_ka_zero) {
            if ($cont_tlr_hidden <= 0) {
                $result = array(
                    'error'=>true,
                    'err_msg'=>"Your total leave requested is zero."
                );
                echo json_encode($result);
                return false;
            }
        } else {
            $result = array(
                'error'=>true,
                'err_msg'=>"Please wait for the form to complete processing your request and try again."
            );
            echo json_encode($result);
            return false;
        }

        // $point_something = explode(".", $cont_tlr_hidden);
        // $point_something_val = $point_something[0] + .50;

        // if($this->company_id == "206") {
        //     if($cont_tlr_hidden > $point_something_val) {
        //         $result = array(
        //             'error'=>true,
        //             'etlr'=>"Your leave setting allows partial leave of atleast .5 leave in a fraction."
        //         );
        //         echo json_encode($result);
        //         return false;
        //     }
        // }
        
        $this->form_validation->set_rules("leave_type", 'Leave Type', 'trim|required|xss_clean');
        $this->form_validation->set_rules("reason", 'Reason', 'trim|required|xss_clean');
        $this->form_validation->set_rules("start_date", 'Start Date', 'trim|required|xss_clean');
        $this->form_validation->set_rules("end_date", 'End Date', 'trim|required|xss_clean');
        
        if(!$check_workday) {
            $this->form_validation->set_rules("start_time", 'Start Time', 'trim|required|xss_clean');
            $this->form_validation->set_rules("end_time", 'End Time', 'trim|required|xss_clean');
        }
        
        $this->form_validation->set_rules('upload_numbers','Attachments','callback_attachment_check');
        $this->form_validation->set_rules("cont_tlr_hidden", 'Total Leave Requested', 'trim|required|xss_clean');
        $this->form_validation->set_error_delimiters('', '');
        
        if (true){
            $concat_start_datetime  = date("H:i:s",strtotime($start_time));
            $concat_start_date      = $start_date." ".$concat_start_datetime;
            $concat_end_datetime    = date("H:i:s",strtotime($end_time));
            $concat_end_date        = $end_date." ".$concat_end_datetime;
            
            $get_current_shift      = $this->employee->get_current_shift($ws_id, $this->company_id, $start_date, $this->emp_id);

            $shift_date = date("Y-m-d",strtotime($start_date)); // input mani ang shift date nya g.pakuha man.. mao start ako g.default, dghan nmn nggamit sa $shift_date
            $check_work_type = $this->employee->work_schedule_type($ws_id, $this->company_id);
            
            if ($get_current_shift) { // gi reuse nlng ni nako na code ni fil..
                foreach ($get_current_shift as $zz) {
                    if($zz->work_type_name == "Flexible Hours") {
                        $ur_start = date("A",strtotime($zz->latest_time_in_allowed));
                        
                        $total_hours_for_the_day = $zz->total_hours_for_the_day * 60;
                        $latest_time_out_allowed = date("H:i:s",strtotime($zz->latest_time_in_allowed.' +'.$total_hours_for_the_day.' minutes'));
                        $boundary_end_to_new_start = $latest_time_out_allowed;
                        $the_new_start = date("H:i:s", strtotime($concat_start_date));
                        
                        $ur_end = date("A",strtotime($latest_time_out_allowed));
                        
                        $input_start_datetime = date("A",strtotime($concat_start_date));
                        $input_end_datetime = date("A",strtotime($concat_end_date));
                        
                        if($ur_start == "PM" && $ur_end == "AM") {
                            if(strtotime($boundary_end_to_new_start) < strtotime($the_new_start)) {
                                $shift_date = date("Y-m-d", strtotime($start_date));
                            } else {
                                if($input_start_datetime == "AM" && $input_end_datetime == "AM") {
                                    $shift_date = date("Y-m-d", strtotime($start_date." -1 day"));
                                } else {
                                    if(strtotime($boundary_end_to_new_start) >= strtotime($the_new_start)){
                                        $shift_date = $start_date;
                                    }
                                }
                            }
                        }
                        
                        $lunch_hr_required = "yes";
                    } else {
                        $ur_start = date("A",strtotime($zz->start));
                        $ur_end = date("A",strtotime($zz->end));
                        
                        $input_start_datetime = date("A",strtotime($concat_start_date));
                        $input_end_datetime = date("A",strtotime($concat_end_date));
                        
                        if($ur_start == "PM" && $ur_end == "AM") {
                            if($input_start_datetime == "AM" && $input_end_datetime == "AM") {
                                $shift_date = date("Y-m-d", strtotime($start_date." -1 day"));
                            }
                        }
                        
                        // no timesheet required trapping : can file within a shift only
                        $shift_start_date = $start_date." ".$zz->start;
                        $shift_end_date = $end_date." ".$zz->end;
                        
                        if (check_if_timein_is_required($this->emp_id,$this->company_id) == "no") {
                            if (strtotime($concat_start_date) < strtotime($shift_start_date)) {
                                $result = array(
                                    'error' => true,
                                    'eend_date' => 'You can only file a leave within your shift.'
                                );
                                echo json_encode($result);
                                return false;
                            } elseif (strtotime($shift_end_date) < strtotime($concat_end_date)) {
                                $result = array(
                                    'error' => true,
                                    'eend_date' => 'You can only file a leave within your shift.'
                                );
                                echo json_encode($result);
                                return false;
                            }
                        }
                    }
                }
            } else {
                $incase_rd = $this->ews->get_rest_day($this->company_id,$ws_id,date("l",strtotime($start_date)));
                if($incase_rd && $leave_request_type == "Partial Day") {
                    $shift_date = date("Y-m-d", strtotime($start_date." -1 day"));
                }
            }
            
            $is_flexi = false;
            if($check_work_type == "Flexible Hours"){
                $is_flexi = true;
            }
            
            $check_existing_leave_applied = $this->employee->check_existing_leave_applied($this->emp_id, $this->company_id, $concat_start_date, $concat_end_date);
            if($check_existing_leave_applied) {
                if(!$is_flexi) {
                    if($check_existing_leave_applied->leave_application_status == 'approve') {
                        $leave_application_status = 'Approved';
                    } else {
                        $leave_application_status = $check_existing_leave_applied->leave_application_status;
                    }
                    
                    $leave_type = $this->employee->leave_type($this->company_id,$this->emp_id,$check_existing_leave_applied->leave_type_id);
                    
                    $leave_type_name = "";
                    if($leave_type) {
                        foreach ($leave_type as $hatch) {
                            $leave_type_name = $hatch->leave_type;
                        }
                    }
                    
                    $date_start_res = date('d-M-y h:i A', strtotime($check_existing_leave_applied->date_start));
                    $date_end_res = date('d-M-y h:i A', strtotime($check_existing_leave_applied->date_end));
                    /*if ($is_flexi) {
                     $date_start_res = date('d-M-y', strtotime($check_existing_leave_applied->date_start));
                     $date_end_res = date('d-M-y', strtotime($check_existing_leave_applied->date_end));
                     }*/
                    
                    $result = array(
                        'error' => true,
                        'existing_leave' => true,
                        'leave_type' => ucwords($leave_type_name),
                        'date_filed' => idates($check_existing_leave_applied->date_filed),
                        'date_start' => $date_start_res,
                        'date_end' => $date_end_res,
                        'leave_application_status' => $leave_application_status,
                        'err_msg' => 'You already have an existing leave filed for this date and time.'
                    );
                    echo json_encode($result);
                    return false;
                }
            }
            
            // check leave restriction
            $halfday_rest                       = $this->leave->get_leave_restriction($leave_type,'provide_half_day_option');
            $apply_limit_rest                   = $this->leave->get_leave_restriction($leave_type,'allow_to_apply_leaves_beyond_limit');
            $exclude_holidays                   = $this->leave->get_leave_restriction($leave_type,'exclude_holidays');
            $num_days_b4_leave                  = $this->leave->get_leave_restriction($leave_type,'num_days_before_leave_application');
            $days_b4_leave                      = $this->leave->get_leave_restriction($leave_type,'days_before_leave_application');
            $num_cons_days                      = $this->leave->get_leave_restriction($leave_type,'num_consecutive_days_allowed');
            $cons_days                          = $this->leave->get_leave_restriction($leave_type,'consecutive_days_allowed');
            $num_cons_days_week_hol             = $this->leave->get_leave_restriction($leave_type,'num_consecutive_days_after_weekend_holiday');
            $cons_days_week_hol                 = $this->leave->get_leave_restriction($leave_type,'consecutive_days_after_weekend_holiday');
            $required_documents                 = $this->leave->get_leave_restriction($leave_type,'required_documents');
            $exclude_rest_days                  = $this->leave->get_leave_restriction($leave_type,'exclude_rest_days');
            $paid_leave                         = $this->leave->get_leave_restriction($leave_type,'paid_leave');
            $effective_start_date_by            = $this->leave->get_leave_restriction($leave_type,'effective_start_date_by');
            $effective_start_date               = $this->leave->get_leave_restriction($leave_type,'effective_start_date');
            $leave_units                        = $this->leave->get_leave_restriction($leave_type,'leave_units');
            $what_happen_to_unused_leave        = $this->leave->get_leave_restriction($leave_type,'what_happen_to_unused_leave');
            $leave_conversion_run_every         = $this->leave->get_leave_restriction($leave_type,'leave_conversion_run_every');
            $carry_over_schedule_specific_month = $this->leave->get_leave_restriction($leave_type,'carry_over_schedule_specific_month');
            $carry_over_schedule_specific_day   = $this->leave->get_leave_restriction($leave_type,'carry_over_schedule_specific_day');
            $allow_negative_borrow_hours        = $this->leave->get_leave_restriction($leave_type,'allow_negative_borrow_hours');
            $allow_negative_borrow_unearned     = $this->leave->get_leave_restriction($leave_type,'allow_negative_borrow_unearned');
            $exclude_regular_holidays           = $this->leave->get_leave_restriction($leave_type,'exclude_regular_holidays');
            $exclude_special_holidays           = $this->leave->get_leave_restriction($leave_type,'exclude_special_holidays');
            
            $partial_days_type                  = $this->leave->get_leave_restriction($leave_type,'partial_days_type');
            $no_min_hours_allowed               = $this->leave->get_leave_restriction($leave_type,'no_min_hours_allowed');
            $no_duration_hours                  = $this->leave->get_leave_restriction($leave_type,'no_duration_hours');
            
            $eff_date                           = $this->leave->get_leave_eff_date($leave_type,$this->company_id,$this->emp_id,"effective_date");
            
            /* CHECK EMPLOYEE WORK SCHEDULE */
            if(strtotime($start_date) > strtotime($end_date)){
                $result = array(
                    'error'=>true,
                    'err_msg'=>"The end date you entered occurs before the start date."
                );
                echo json_encode($result);
                return false;
            }elseif(strtotime($start_date) == strtotime($end_date)){
                if(!$check_workday) {
                    if(strtotime($concat_start_datetime) > strtotime($concat_end_datetime)){
                        $result = array(
                            'error' => true,
                            'err_msg' => "The end time you entered occurs before the start time of your leave."
                        );
                        echo json_encode($result);
                        return false;
                    }
                }
            }
            
            // Check : Number of days before which the leave application should be submitted
            $date_now = date('Y-m-d');
            $exact_date_to_apply = date('Y-m-d', strtotime($num_days_b4_leave.' day', strtotime($date_now)));
            $start_date_to_apply = date('Y-m-d', strtotime($start_date));
            
            if($num_days_b4_leave != 0) {
                if($start_date_to_apply < $exact_date_to_apply) {
                    $result = array(
                            'error'=>true,
                            'err_msg'=>"You do not meet the required number of days for the leave application to be filed."
                    );
            
                    echo json_encode($result);
                    return false;
                }
            }
            
            //Check : Maximum number of consecutive days of leave allowed
            $lv_start = $concat_start_date;
            $lv_end = $concat_end_date;
            $total_lv = 0;
            $current_date       = $start_date;
            $per_day_credit     = $this->prm->average_working_hours_per_day($this->company_id);
            $work_schedule_id   = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$current_date);
            $work_start_time    = $this->employee->for_leave_hoursworked_work_start_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,$concat_start_date,$leave_request_type);
            $work_end_time      = $this->employee->for_leave_hoursworked_work_end_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,$concat_start_date);
            
            $chk_total_days = ((strtotime($lv_end) - strtotime($lv_start)) / 3600 / 24);
            
            if(round($chk_total_days) > 0){
                if($cons_days == 'yes') {
                    if($num_cons_days != null || $num_cons_days != 0){
                        if($num_cons_days < $cont_tlr_hidden) {
                            $result = array(
                                    'error'=>true,
                                    'err_msg'=>"You have exceeded the number of consecutive days of leaves allowed by your company."
                            );
                    
                            echo json_encode($result);
                            return false;
                        }
                    }
                }
            }
                        
            // check for leaves limits(2) per day
            $check_greater_than_2_leaves = $this->employee->check_greater_than_2_leaves($this->emp_id, $this->company_id, $shift_date);
            
            if($check_greater_than_2_leaves >= 2) {
                $result = array(
                        'error'=>true,
                        'err_msg'=> "You can only file up to two leaves per day."
                );
                    
                echo json_encode($result);
                return false;
            }
            
            // check for leaves within the calendar year only
            if($paid_leave == 'yes') {
                if($what_happen_to_unused_leave == "convert to cash" || $what_happen_to_unused_leave == "do nothing") {
                    $get_employee_details_by_empid = get_employee_details_by_empid($this->emp_id);
                    
                    $conversion_sched = "";
                    $this_year = date('Y');
                    if($leave_conversion_run_every == "annual") {
                        $conversion_sched = $this_year.'-12-31';
                        $conversion_sched = date('Y-m-d', strtotime($conversion_sched));
                        
                        $temp_conversion_sched = date('m-d', strtotime($conversion_sched));
                        $concat_start_date_year = date('Y', strtotime($concat_start_date)).'-'.$temp_conversion_sched;
                        $new_conversion_sched = date('Y-m-d', strtotime($concat_start_date_year));
                    } elseif ($leave_conversion_run_every == "anniversary") {
                        $date_hired = $get_employee_details_by_empid->date_hired;
                        $conversion_sched = $this_year.'-'.date('m-d', strtotime($date_hired));
                        $conversion_sched = date('Y-m-d', strtotime($conversion_sched));
                        
                        $temp_conversion_sched = date('m-d', strtotime($conversion_sched));
                        $concat_start_date_year = date('Y', strtotime($concat_end_date)).'-'.$temp_conversion_sched;
                        $new_conversion_sched = date('Y-m-d', strtotime($concat_start_date_year));
                    } elseif ($leave_conversion_run_every == "specific date") {
                        $conversion_sched = $this_year.'-'.$carry_over_schedule_specific_month.'-'.$carry_over_schedule_specific_day;
                        $conversion_sched = date('Y-m-d', strtotime($conversion_sched));
                        
                        $temp_conversion_sched = date('m-d', strtotime($conversion_sched));
                        $concat_start_date_year = date('Y', strtotime($concat_end_date)).'-'.$temp_conversion_sched;
                        $new_conversion_sched = date('Y-m-d', strtotime($concat_start_date_year));
                    }
                    
                    $concat_start_date_new = date('Y-m-d', strtotime($concat_start_date));
                    $concat_end_date_new = date('Y-m-d', strtotime($concat_end_date));
                    
                    if(strtotime($concat_start_date_new) < strtotime($conversion_sched) && strtotime($concat_end_date_new) > strtotime($conversion_sched)) {
                        $msg_date = date('M d, Y', strtotime($conversion_sched));
                        $result = array(
                            'error'=>true,
                            'etlr'=>"Your leave conversion is on {$msg_date}. You cannot apply leaves that span across this date. <br>Please apply separately."
                        );
                        
                        echo json_encode($result);
                        return false;
                    } elseif (strtotime($concat_start_date_new) < strtotime($new_conversion_sched) && strtotime($concat_end_date_new) > strtotime($new_conversion_sched)) {
                        $msg_date = date('M d, Y', strtotime($new_conversion_sched));
                        $result = array(
                            'error'=>true,
                            'etlr'=>"Your leave conversion is on {$msg_date}. You cannot apply leaves that span across this date. <br>Please apply separately."
                        );
                        
                        echo json_encode($result);
                        return false;
                    }
                }
            }
            
            $check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($start_date)));
            $tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($this->company_id,$work_schedule_id);
            $is_work = is_break_assumed($work_schedule_id);
            if($tardiness_rule_migrated_v3) {
                $is_work = false;
            }
            
            $void = $this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($shift_date)));
            
            if($void == "Waiting for approval"){
                $flag_payroll_correction = "yes";
                $disabled_btn = true;
            } elseif ($void == "Closed") {
                $flag_payroll_correction = "yes";
            } else {
                $flag_payroll_correction = "no";
            }
            
            // if one of the approver is inactive the approver group will automatically change to default (owner)
            change_approver_to_default($this->emp_id,$this->company_id,"leave_approval_grp",$this->account_id);
            
            $date1 = $concat_start_date;
            $date2 = $concat_end_date;
            
            $date_timein        = date("H:i:s",strtotime($date1));
            $date_timeout       = date("H:i:s",strtotime($date2));
            $check_hours        = strtotime(date("Y-m-d H:i:s",strtotime($date2))) - strtotime(date("Y-m-d H:i:s",strtotime($date1)));
            $total_leave_hours  = $check_hours / 3600;
            $total_hours        = $check_hours / 3600 / 24;
            
            $total_leave_request    = $cont_tlr_hidden;
            $duration               = number_format(round($total_leave_request,2),'2','.',',');
            
            // check restriction halfday is not allowed
            $work_schedule_id   = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($concat_start_date)));
            $req_hours_work     = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($concat_start_date)),$work_schedule_id) / 2;
            
            $err_mess   = 0;
            $limit_res  = '';
            $hd_res     = '';
            
            if($halfday_rest == 'yes'){
                if($partial_days_type == 'duration_hours') {
                    $point_something = explode(".", $cont_tlr_hidden);
                    #$point_something_val = $point_something[0] + $no_duration_hours;
                    $point_something_val = "0.".$point_something[1];
                    $no_duration_hours = number_format($no_duration_hours,3);
                    
                    // manual kaau :D change nlng if naa ky idea lain hahah na mas saun
                    $duration_pass = false;
                    if($no_duration_hours == 0.75) { // same rani ug scenario or outcome
                        if($point_something_val == 0.750){
                            $duration_pass = true;
                        } elseif ($cont_tlr_hidden >= 1 && $point_something_val == 0.500) {
                            $duration_pass = true;
                        } elseif ($cont_tlr_hidden >= 1 && $point_something_val == 0.250) {
                            $duration_pass = true;
                        } elseif ($cont_tlr_hidden >= 1 && $point_something_val == 0.000) {
                            $duration_pass = true;
                        }
                    } elseif ($no_duration_hours == 0.500) {
                        if ($point_something_val == 0.500) {
                            $duration_pass = true;
                        } elseif ($point_something_val == 0.000) {
                            $duration_pass = true;
                        }
                    } elseif ($no_duration_hours == 0.250) {
                        if($point_something_val == 0.750){
                            $duration_pass = true;
                        } elseif ($point_something_val == 0.500) {
                            $duration_pass = true;
                        } elseif ($point_something_val == 0.250) {
                            $duration_pass = true;
                        } elseif ($point_something_val == 0.000) {
                            $duration_pass = true;
                        }
                    }
                    
                    if(!$duration_pass) {
                        $result = array(
                            'error'=>true,
                            'etlr'=>"Your leave setting allows partial leave of atleast ".$no_duration_hours." leave in a fraction."
                        );
                        echo json_encode($result);
                        return false;
                    }
                } else {
                    if($leave_request_type == "Partial Day") {
                        $no_of_hrs_filed = $cont_tlr_hidden * 8;
                        if($no_min_hours_allowed > $no_of_hrs_filed) {
                            $result = array(
                                'error'=>true,
                                'etlr'=>"Your leave setting allows partial leave of atleast ".$no_min_hours_allowed." hours minimum."
                            );
                            echo json_encode($result);
                            return false;
                        }
                    }
                }
            } else {
                if($leave_request_type == "Partial Day") {
                    $hd_res     = "Partial day leave has been disabled by your company. You cannot apply for partial day leave.";
                    $err_mess   = $err_mess + 1;
                }
            }

            $get_all_pending_leave = $this->employee->get_all_pending_leave($this->company_id,$this->emp_id,$leave_type);
            
            if($paid_leave == 'yes') {
                if($apply_limit_rest == 'no'){
                    $check_leave_balance = $this->employee->leave_type($this->company_id,$this->emp_id,$leave_type);
                    
                    if($check_leave_balance){
                        foreach($check_leave_balance as $clb){
                            $remaining = $clb->remaining_leave_credits;
                            if($remaining == ''){
                                $remaining = $clb->leave_credits;
                            }
                        }
                        
                        $get_all_pending_leave = $this->employee->get_all_pending_leave($this->company_id,$this->emp_id,$leave_type);
                        $pending_credits = 0;
                        if ($get_all_pending_leave) {
                            foreach ($get_all_pending_leave as $gapl) {
                                $pending_credits += $gapl->total_leave_requested;
                            }
                        }
                        
                        $remaining = $remaining - $pending_credits;
                       # p($remaining);exit();
                        if($total_leave_request > $remaining){
                            $limit_res = "You cannot apply leaves beyond the allowed limit alloted by your company.";
                            $err_mess = $err_mess + 1;
                        }
                    }
                }
            }
            
            if($err_mess != 0){
                $err = array("error"=>true,"err_msg"=>$hd_res,"err_msg"=>$limit_res);
                echo json_encode($err);
            }
            
            if($effective_start_date_by != null && $effective_start_date != null) {
                if(date('Y-m-d', strtotime($concat_start_date)) >= $eff_date) {
                    $total_leave_request_save = $total_leave_request;
                    $leave_cedits_for_untitled = 0;
                } else {
                    $total_leave_request_save = 0;
                    $leave_cedits_for_untitled = $total_leave_request;
                }
            } else {
                $total_leave_request_save = $total_leave_request;
                $leave_cedits_for_untitled = 0;
            }
            
            $leave_credits = $this->employee->leave_type($this->company_id,$this->emp_id, $leave_type);
            if ($leave_credits) {
	            foreach ($leave_credits as $lc){
	                $leave_credits = ($lc->remaining_leave_credits != "") ? $lc->remaining_leave_credits : $lc->leave_credits;
	            }
	        }
            
            if ($allow_negative_borrow_unearned == "yes") {
                $leave_credits = $leave_credits + $allow_negative_borrow_hours;
                
                if ($total_leave_request > $leave_credits) {
                    $result = array(
                        'error'=>true,
                        'etlr'=>"Oh no! you can only borrow {$allow_negative_borrow_hours} {$leave_units} from leaves that you have yet to accrue/earn."
                    );
                    
                    echo json_encode($result);
                    return false;
                }
            }

            if ($please_yaw_ka_zero) {
                if ($cont_tlr_hidden <= 0) {
                    $result = array(
                        'error'=>true,
                        'etlr'=>"Your total leave requested is zero."
                    );
                    echo json_encode($result);
                    return false;
                }
            } else {
                $result = array(
                    'error'=>true,
                    'etlr'=>"Please wait for the form to complete processing your request and try again."
                );
                echo json_encode($result);
                return false;
            }
            
            
            $save_employee_leave = array(
                "shift_date"                 => $shift_date,
                "work_schedule_id"           => $work_schedule_id,
                "company_id"                 => $this->company_id,
                "emp_id"                     => $this->emp_id,
                "date_filed"                 => date("Y-m-d"),
                "leave_type_id"              => $leave_type,
                "reasons"                    => $reason,
                "date_start"                 => $concat_start_date,
                "date_end"                   => $concat_end_date,
                "total_leave_requested"      => $total_leave_request_save,
                "leave_cedits_for_untitled"  => $leave_cedits_for_untitled,
                "duration"                   => $duration,
                "note"                       => "",
                "leave_application_status"   => "pending",
                "attachments"                => "",
                "previous_credits"           => $previous_credits,
                "flag_payroll_correction"    => $flag_payroll_correction,
                "exclude_lunch_break"        => $lunch_hr_required,
                "leave_request_type"         => ($leave_request_type == "") ? null: $leave_request_type,
                "status"					 => "Active"
            );
            
            $upload_attachment = $this->input->post('upload_attachment');
            if($upload_attachment){
                $save_employee_leave['required_file_documents'] = $upload_attachment ?  implode(";",$upload_attachment) : '';
            }

            $name = ucwords($this->employee->get_approver_name($this->emp_id,$this->company_id)->first_name);
            $email = $this->employee->get_approver_name($this->emp_id,$this->company_id)->email; 

            // save employee leave application
            // $insert_employee_leave = $this->jmodel->insert_data('employee_leaves_application',$save_employee_leave);
            $this->db->insert('employee_leaves_application', $save_employee_leave);
            // esave('employee_leaves_application',$save_employee_leave);
            $insert_employee_leave = $this->db->insert_id();
            
            // view last row for leave application
            $view_last_row_leave_application = $this->employee->last_row_leave_app($this->emp_id,$this->company_id,$leave_type);
            $check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
            $check_is_date_holidayv2 = $this->employee_v2->check_is_date_holidayv2($this->company_id);
                        
            if($check_workday != FALSE){ // calculate credited and non credited for flexi only
                $credited = 0;
                $non_credited = 0;
                
             	if(strtotime($start_date) == strtotime($end_date)) { // for one day
                 	if($effective_start_date_by != null && $effective_start_date != null) {
                 		if(date('Y-m-d', strtotime($start_date)) >= $eff_date) {
                         	if($cont_tlr_hidden > $leave_credits){
                             	$credited = $leave_credits;
                             	$non_credited = $cont_tlr_hidden - $leave_credits;
                         	}elseif($cont_tlr_hidden <= $leave_credits){
                             	$credited = $cont_tlr_hidden;
                         	}
                     	} else {
                         	$credited = 0;
                         	$non_credited = 0;
                     	}
                 	} else {
                     	if($cont_tlr_hidden > $leave_credits){
                         	$credited = $leave_credits;
                         	$non_credited = $cont_tlr_hidden - $leave_credits;
                     	}elseif($cont_tlr_hidden <= $leave_credits){
                         	$credited = $cont_tlr_hidden;
                     	}
                 	}

                 	if($credited < 0) {
                        $credited = 0;
                    }
                    
                    $no_of_hours = 8; // if credits, not days ang unit.
                    if($leave_units == "days"){
                        $no_of_hours = $credited * $fl_hours_worked;
                    }
                     
                     $update_data2 = array(
                         'flag_parent' => 'no',
                         "credited" => $credited,
                         "non_credited" => $non_credited,
                         "no_of_hours" => $no_of_hours
                     );
                     
                     $this->db->where('employee_leaves_application_id',$view_last_row_leave_application->employee_leaves_application_id);
                     $this->db->update("employee_leaves_application",$update_data2);
             	} else { // for multiple day
                     
                     // less than 24 hours
	                $fl_time = $check_workday->latest_time_in_allowed;
                    $duration_of_lunch_break_per_day = $check_workday->duration_of_lunch_break_per_day / 60;
                    $fl_hours_worked = $check_workday->total_hours_for_the_day - $duration_of_lunch_break_per_day;
                    
                    $days_filed = dateRange($start_date, $end_date);
                    $days_filed_cnt = count($days_filed);
                    $days_cnt = 0;
                    $used_credits_total = 0;
					if($days_filed) {
                        foreach ($days_filed as $date) {
                            $days_cnt++;
                            
                            $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($date)));
                            
                            // exclude holiday
                            $date_m_d = date("m-d", strtotime($date));
                            
                            $is_holiday_q = in_array_custom("date-{$date_m_d}",$check_is_date_holidayv2);
                            $is_holiday = false;
                            $is_hol_temp = false;
                            
                            // exclude holiday
                            if($is_holiday_q){
                                if($is_holiday_q->date_type == "fixed") {
                                    $app_m_d = date("m-d",strtotime($date));
                                    $hol_m_d = date("m-d",strtotime($is_holiday_q->date));
                                    
                                    if($app_m_d == $hol_m_d){
                                        $is_hol_temp = true;
                                    } else {
                                        $is_hol_temp = false;
                                    }
                                } else {
                                    $is_hol_temp = true;
                                }
                                
                                if($is_hol_temp) {
                                    $proceed1 = false;
                                    
                                    if($this->my_location != 0 || $this->my_location != null) {
                                        if($is_holiday_q->locations != "" || $is_holiday_q->locations != null) {
                                            $x = explode(",", $is_holiday_q->locations);
                                            foreach ($x as $loc) {
                                                if($loc == $this->my_location) {
                                                    $proceed1 = true;
                                                }
                                            }
                                        }
                                    }
                                    
                                    if($is_holiday_q->scope == "local" && !$proceed1) {
                                        $is_hol = FALSE;
                                    } else {
                                        $is_hol = TRUE;
                                    }
                                    
                                }
                            } else {
                                $is_hol = false;
                            }
                            
                            if($is_hol) {
                                $is_hol = holiday_leave_approval_settings($this->company_id);
                            }
                            
                            if($is_hol) {
                                if($is_holiday_q) {
                                    if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "yes"){
                                        $is_holiday = true;
                                    } else {
                                        if($is_holiday_q->hour_type_name == "Special Holiday") {
                                            // exclude Special holiday only
                                            if($exclude_holidays == 'yes' && $exclude_regular_holidays == "no" && $exclude_special_holidays == "yes"){
                                                $is_holiday = true;
                                            }
                                        } elseif($is_holiday_q->hour_type_name == "Regular Holiday") {
                                            // exclude Regular holiday only
                                            if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "no"){
                                                $is_holiday = true;
                                            }
                                        }
                                    }
                                }
                            }
                            
                            if(!$is_holiday && !$rest_day){
                                $check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date)),$work_schedule_id);
                                
                                if($days_filed_cnt == $days_cnt) { // for last loop
                                    if($leave_credits > 0) {
                                        $leave_credits = $leave_credits;
                                    } else {
                                        $used_credits = $cont_tlr_hidden - $used_credits_total;
                                        $leave_credits = 0;
                                    }
                                    
                                    if($effective_start_date_by != null && $effective_start_date != null) {
                                        if(date('Y-m-d', strtotime($start_date)) >= $eff_date) {
                                            if($used_credits > $leave_credits){
                                                $credited = $leave_credits;
                                                $non_credited = $used_credits - $leave_credits;
                                            }elseif($used_credits <= $leave_credits){
                                                $credited = $used_credits;
                                                $non_credited = 0;
                                            }
                                        } else {
                                            $credited = 0;
                                            $non_credited = 0;
                                        }
                                    } else {
                                        if($used_credits > $leave_credits){
                                            $credited = $leave_credits;
                                            $non_credited = $used_credits - $leave_credits;
                                        }elseif($used_credits <= $leave_credits){
                                            $credited = $used_credits;
                                        }
                                    }
                                    
                                } else {
                                    if($leave_units == "days"){
                                        $used_credits = $check_for_working_hours / $check_for_working_hours;
                                    }else{
                                        $used_credits = $check_for_working_hours / $per_day_credit;
                                    }
                                    
                                    if($effective_start_date_by != null && $effective_start_date != null) {
                                        if(date('Y-m-d', strtotime($start_date)) >= $eff_date) {
                                            if($used_credits > $leave_credits){
                                                $credited = $leave_credits;
                                                $non_credited = $used_credits - $leave_credits;
                                            }elseif($used_credits <= $leave_credits){
                                                $credited = $used_credits;
                                            }
                                        } else {
                                            $credited = 0;
                                            $non_credited = 0;
                                        }
                                    } else {
                                        if($used_credits > $leave_credits){
                                            $credited = $leave_credits;
                                            $non_credited = $used_credits - $leave_credits;
                                        }elseif($used_credits <= $leave_credits){
                                            $credited = $used_credits;
                                        }
                                    }
                                    
                                    $leave_credits = $leave_credits - $credited;
                                    $used_credits_total = $used_credits_total + $used_credits;
                                }
                                
                                if($credited < 0) {
                                    $credited = 0;
                                }
                                
                                $no_of_hours = 8; // if credits, not days ang unit.
                                if($leave_units == "days"){
                                    $no_of_hours = $credited * $fl_hours_worked;
                                }
                                
                                $each_leave = array(
                                    "shift_date"                 => date("Y-m-d",strtotime($date)),
                                    "work_schedule_id"           => $work_schedule_id,
                                    "company_id"                 => $this->company_id,
                                    "emp_id"                     => $this->emp_id,
                                    "leave_type_id"              => $leave_type,
                                    "reasons"                    => $reason,
                                    "date_start"                 => date("Y-m-d",strtotime($date)),
                                    "date_end"                   => date("Y-m-d",strtotime($date)),
                                    "leave_application_status"   => $leave_app_status,
                                    "credited"                   => $credited,
                                    "non_credited"               => $non_credited,
                                    "leaves_id"                  => $view_last_row_leave_application->employee_leaves_application_id,
                                    "previous_credits"           => $previous_credits,
                                    "flag_payroll_correction"    => $flag_payroll_correction,
                                    "no_of_hours"                => $no_of_hours,
                                    "status"					 => "Active"
                                );
                                
                                $this->db->insert("employee_leaves_application", $each_leave);
                            }
                        }
                        
                        $update_data = array('flag_parent'=>'yes');
                        $this->db->where('employee_leaves_application_id',$view_last_row_leave_application->employee_leaves_application_id);
                        $this->db->update("employee_leaves_application",$update_data);
                    }
                }
            } else { // calculate credited and non credited for uniform working sched and split only
                #p($check_total_days);
                if($check_total_days > 1){
                    // gi balik nako ugtwag na code para ni sa night shift na d nako ma prevent ang pagcheck sa partial day bisan d sya partial.
                    $work_start_time    = $this->employee->for_leave_hoursworked_work_start_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,$concat_start_date);
                    $check_total_days = round($check_total_days);
                    $tl2 = 0;
                    $credited = 0;
                    $non_credited = 0;
                    $single_date_applied = true;
                    $temp_total_used_leave1 = 0;
                    $last_date = date("Y-m-d",strtotime($date2));
                    $last_date_not_rd = date("Y-m-d",strtotime($date2));
                    
                    for($cnt=1;$cnt<=$check_total_days;$cnt++){ // for range date : calculate the credits from first - 2nd to the last, exclude the last date
                        $temp_date = date("Y-m-d",strtotime($shift_date." +".$cnt." day"));
                        $work_sched_id = check_employee_work_schedule(date("Y-m-d",strtotime($temp_date." -1 day")), $this->emp_id, $this->company_id)->work_schedule_id;
                        $rest_day = $this->ews->get_rest_day($this->company_id,$work_sched_id,date("l",strtotime($temp_date." -1 day")));
                        
                        $starttime_ampm = date("A", strtotime($date1));
                        $endtime_ampm = date("A", strtotime($date2));
                        
                        // exclude holiday
                        $for_holiday_date = date("Y-m-d",strtotime($temp_date." -1 day"));
                        $for_holiday_date_m_d = date("m-d", strtotime($for_holiday_date));
                        $is_holiday_q = in_array_custom("date-{$for_holiday_date_m_d}",$check_is_date_holidayv2);
                        $is_holiday_tl2 = false;
                        $is_hol_temp = false;
                        
                        // exclude holiday
                        if($is_holiday_q){
                            if($is_holiday_q->date_type == "fixed") {
                                $app_m_d = date("m-d",strtotime($for_holiday_date));
                                $hol_m_d = date("m-d",strtotime($is_holiday_q->date));
                                
                                if($app_m_d == $hol_m_d){
                                    $is_hol_temp = true;
                                } else {
                                    $is_hol_temp = false;
                                }
                            } else {
                                $is_hol_temp = true;
                            }
                            
                            if($is_hol_temp) {
                                $proceed1 = false;
                                
                                if($this->my_location != 0 || $this->my_location != null) {
                                    if($is_holiday_q->locations != "" || $is_holiday_q->locations != null) {
                                        $x = explode(",", $is_holiday_q->locations);
                                        foreach ($x as $loc) {
                                            if($loc == $this->my_location) {
                                                $proceed1 = true;
                                            }
                                        }
                                    }
                                }
                                
                                if($is_holiday_q->scope == "local" && !$proceed1) {
                                    $is_hol = FALSE;
                                } else {
                                    $is_hol = TRUE;
                                }
                                
                            }
                        } else {
                            $is_hol = false;
                        }
                        
                        if($is_hol) {
                            $is_hol = holiday_leave_approval_settings($this->company_id);
                        }
                        
                        if($is_hol) {
                            if($is_holiday_q) {
                                if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "yes"){
                                    $is_holiday_tl2 = true;
                                } else {
                                    if($is_holiday_q->hour_type_name == "Special Holiday") {
                                        // exclude Special holiday only
                                        if($exclude_holidays == 'yes' && $exclude_regular_holidays == "no" && $exclude_special_holidays == "yes"){
                                            $is_holiday_tl2 = true;
                                        }
                                    } elseif($is_holiday_q->hour_type_name == "Regular Holiday") {
                                        // exclude Regular holiday only
                                        if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "no"){
                                            $is_holiday_tl2 = true;
                                        }
                                    }
                                }
                            }
                        }
                        
                        $date_start = $work_start_time;
                        $date_end = $work_end_time;
                        

                        if(!$is_holiday_tl2 && !$rest_day){

                            $check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($temp_date." -1 day")),$work_schedule_id);
                            if($get_open_shift_leave) {
                                $check_for_working_hours = 8;
                            }
                            
                            $tl2 += $check_for_working_hours;
                            
                            if($cnt == 1) { // calculate the first date
                                $date_start = $start_time;
                                if(strtotime($shift_date) < strtotime($start_date)) { // if applied on 2nd half
                                    $start_br_down_date = $shift_date.' '.date("H:i:s", strtotime($work_start_time));
                                    $end_br_down_date = $start_date." ".$work_end_time;
                                    
                                    $new_concat_start_date = $start_date.' '.date("H:i:s", strtotime($concat_start_date));
                                    if(strtotime($start_br_down_date) < strtotime($new_concat_start_date)) {
                                        $start_br_down_date = $start_date.' '.date("H:i:s", strtotime($concat_start_date));
                                    }
                                    
                                    $new_concat_end_date = $start_date.' '.date("H:i:s", strtotime($concat_end_date));
                                    if(strtotime($end_br_down_date) > strtotime($new_concat_end_date)) {
                                        $end_br_down_date = $start_date.' '.date("H:i:s", strtotime($concat_end_date));
                                    }
                                    
                                    $check_start_time   = $this->employee_v2->get_leave_breaktime($this->emp_id,$this->company_id,date("l",strtotime($shift_date)),$work_schedule_id, $start_br_down_date, $end_br_down_date);
                                    $check_end_time     = $this->employee_v2->get_leave_breaktime($this->emp_id,$this->company_id,date("l",strtotime($shift_date)),$work_schedule_id, $start_br_down_date, $end_br_down_date,false);
                                    if($leave_units == 'days') {
                                        #$used_credits = ($this->employee_v2->get_tot_hours_ws_v2($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$check_for_working_hours,$work_schedule_id,0,false, $shift_date)) / $check_for_working_hours;
                                        $used_credits = ($this->employee_v2->get_tot_hours_ws_v3($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$hours_worked,$work_schedule_id,$shift_date,$lunch_hr_required)) / $hours_worked;
                                    } else {
                                        #$used_credits = ($this->employee_v2->get_tot_hours_ws_v2($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$check_for_working_hours,$work_schedule_id,0,false, $shift_date)) / $per_day_credit;
                                        $used_credits = ($this->employee_v2->get_tot_hours_ws_v3($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$hours_worked,$work_schedule_id,$shift_date,$lunch_hr_required)) / $per_day_credit;
                                    }
                                    
                                } else {
                                    if($leave_units == "days"){
                                        $used_credits = $check_for_working_hours / $check_for_working_hours;
                                    }else{
                                        $used_credits = $check_for_working_hours / $per_day_credit;
                                    }
                                }
                            } else {
                                if($leave_units == "days"){
                                    $used_credits = $check_for_working_hours / $check_for_working_hours;
                                }else{
                                    $used_credits = $check_for_working_hours / $per_day_credit;
                                }
                            }
                            
                            
                            if($effective_start_date_by != null && $effective_start_date != null) {
                                if(date('Y-m-d', strtotime($concat_start_date)) >= $eff_date) {
                                    if($used_credits > $leave_credits){
                                        $credited = $leave_credits;
                                        $non_credited = $used_credits - $leave_credits;
                                    }elseif($used_credits <= $leave_credits){
                                        $credited = $used_credits;
                                    }
                                } else {
                                    $credited = 0;
                                    $non_credited = 0;
                                }
                            } else {
                                if($used_credits > $leave_credits){
                                    $credited = $leave_credits;
                                    $non_credited = $used_credits - $leave_credits;
                                }elseif($used_credits <= $leave_credits){
                                    $credited = $used_credits;
                                }
                            }
                            
                            if($credited < 0) {
                                $credited = 0;
                            }
                            
                            $no_of_hours = $credited * 8; // if credits, not days ang unit.
                            if($leave_units == "days"){
                                $no_of_hours = $credited * $check_for_working_hours;
                            }
                            
                            $date_start_save = date("Y-m-d",strtotime($temp_date." -1 day")).' '.date("H:i:s", strtotime($date_start));
                            $date_end_save = date("Y-m-d",strtotime($temp_date." -1 day")).' '.date("H:i:s", strtotime($date_end));
                            
                            if ($starttime_ampm == "PM" && $endtime_ampm == "AM") {
                                $date_start_save = date("Y-m-d",strtotime($temp_date." -1 day")).' '.date("H:i:s", strtotime($date_start));
                                $date_end_save = date("Y-m-d",strtotime($temp_date)).' '.date("H:i:s", strtotime($date_end));
                            }
                            
                            $each_leave = array(
                                "shift_date"                 => date("Y-m-d",strtotime($temp_date." -1 day")),
                                "work_schedule_id"           => $work_schedule_id,
                                "company_id"                 => $this->company_id,
                                "emp_id"                     => $this->emp_id,
                                "leave_type_id"              => $leave_type,
                                "reasons"                    => $reason,
                                "date_start"                 => $date_start_save,
                                "date_end"                   => $date_end_save,
                                "leave_application_status"   => $leave_app_status,
                                "credited"                   => $credited,
                                "non_credited"               => $non_credited,
                                "leaves_id"                  => $view_last_row_leave_application->employee_leaves_application_id,
                                "previous_credits"           => $previous_credits,
                                "flag_payroll_correction"    => $flag_payroll_correction,
                                "no_of_hours"                => $no_of_hours,
                                "status"					 => "Active"
                            );
                            
                            $this->db->insert("employee_leaves_application", $each_leave);
                            $leave_credits = $leave_credits - $used_credits;
                            $temp_total_used_leave = $leave_credits - $used_credits;
                            $temp_total_used_leave1 += $credited;
                            $single_date_applied = false;
                            
                            $last_date_not_rd = $temp_date;
                        }
                    }
                    
                    if ($single_date_applied) {
                        $date_start = $start_time;
                        $date_end = $end_time;
                    } else {
                        if (strtotime($work_end_time) > strtotime($end_time)) {
                            $date_end = $end_time;
                        }
                        
                        if (strtotime($work_start_time) < strtotime($start_time)) {
                            $date_start = $work_start_time;
                        } else {
                            $date_start = $start_time;
                        }
                    }
                    
                    // this is for end date ky sometimes ang end date ky lahi ug schedule
                    $fl_hours_worked = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($concat_end_date)),$work_schedule_id);
                    if($check_workshift != FALSE){
                        $tl1 = $last_block_hours;
                        
                        if(date("A",strtotime($work_start_time)) == 'PM' && date("A",strtotime($work_end_time)) == 'AM') {
                            if (date("A",strtotime($date1)) == 'AM' && date("A",strtotime($date2)) == 'AM') {
                                $last_date = date("Y-m-d",strtotime($date2." -1 day"));
                            }
                        }
                        
                        $shift_date_last_save = date("Y-m-d",strtotime($last_date));
                        $date_start_last_save = $last_date.' '.date("H:i:s", strtotime($date_start));
                        $date_end_last_save = $last_date.' '.date("H:i:s", strtotime($date_end));
                        
                        if($leave_units == "days"){
                            $used_credits = $tl1 / $fl_hours_worked;
                        }else{
                            $used_credits = $tl1 / $per_day_credit;
                        }
                    } else {
                        $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($start_date)));
                        $work_schedule_idx  = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$end_date);
                        $rest_dayx = $this->ews->get_rest_day($this->company_id,$work_schedule_idx,date("l",strtotime($end_date)));
                        
                        if ($rest_day) {
                            $last_date = date("Y-m-d",strtotime($end_date));
                        } elseif ($rest_dayx) {
                            $last_date_not_rd_1 = date("Y-m-d", strtotime($last_date_not_rd.' +1 day'));
                            $work_schedule_idy  = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$last_date_not_rd_1);
                            $rest_dayy = $this->ews->get_rest_day($this->company_id,$work_schedule_idy,date("l",strtotime($last_date_not_rd_1)));
                            
                            if ($rest_dayy) {
                                #$last_date = $last_date_not_rd;
                            } else {
                                $last_date = date("Y-m-d", strtotime($last_date_not_rd.' +1 day'));
                            }
                        }
                        
                        $shift_date_last_save = date("Y-m-d",strtotime($last_date));
                        $date_start_last_save = $last_date.' '.date("H:i:s", strtotime($date_start));
                        $date_end_last_save = $last_date.' '.date("H:i:s", strtotime($date_end));
                        
                        if(date("A",strtotime($work_start_time)) == 'PM' && date("A",strtotime($work_end_time)) == 'AM') {
                            $shift_date_last_save = date("Y-m-d",strtotime($last_date.' -1 day'));
                            $date_start_last_save = date("Y-m-d", strtotime($last_date.' -1 day')).' '.date("H:i:s", strtotime($date_start));
                            $date_end_last_save = $last_date.' '.date("H:i:s", strtotime($date_end));
                        }
                        
                        if($single_date_applied) { // calculate the solo filed leave
                            $used_credits = $cont_tlr_hidden;
                        } else { // calculate the last date of the if range date applied
                            $used_credits = $cont_tlr_hidden - $temp_total_used_leave1;
                        }
                    }
                    
                    if($effective_start_date_by != null && $effective_start_date != null) {
                        if(date('Y-m-d', strtotime($concat_start_date)) >= $eff_date) {
                            if($used_credits > $leave_credits){
                                $credited = $leave_credits;
                                $non_credited = $used_credits - $leave_credits;
                            }elseif($used_credits <= $leave_credits){
                                $credited =  $used_credits;
                            }
                        } else {
                            $credited = 0;
                            $non_credited = 0;
                        }
                    } else {
                        if($used_credits > $leave_credits){
                            $credited = $leave_credits;
                            $non_credited = $used_credits - $leave_credits;
                        }elseif($used_credits <= $leave_credits){
                            $credited =  $used_credits;
                        }
                    }
                    
                    if($credited < 0) {
                        $credited = 0;
                    }
                    
                    $no_of_hours = $credited * 8; // if credits, not days ang unit.
                    if($leave_units == "days"){
                        $check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($shift_date_last_save)),$work_schedule_id);
                        $no_of_hours = $credited * $check_for_working_hours;
                    }
                    
                    $each_leave = array(
                        "shift_date"               => $shift_date_last_save,
                        "work_schedule_id"         => $work_schedule_id,
                        "company_id"               => $this->company_id,
                        "emp_id"                   => $this->emp_id,
                        "leave_type_id"            => $leave_type,
                        "reasons"                  => $reason,
                        "date_start"               => $date_start_last_save,
                        "date_end"                 => $date_end_last_save,
                        "leave_application_status" => $leave_app_status,
                        "credited"                 => $credited,
                        "non_credited"             => $non_credited,
                        "leaves_id"                => $view_last_row_leave_application->employee_leaves_application_id,
                        "previous_credits"         => $previous_credits,
                        "flag_payroll_correction"  => $flag_payroll_correction,
                        "no_of_hours"              => $no_of_hours,
                        "status"				   => "Active"
                    );
                    #p($each_leave);
                    #fuck you
                    $this->db->insert("employee_leaves_application", $each_leave);
                    $update_data = array('flag_parent'=>'yes');
                    $this->db->where('employee_leaves_application_id',$view_last_row_leave_application->employee_leaves_application_id);
                    $this->db->update("employee_leaves_application",$update_data);#fuck you
                } else {
                    #echo "dasdsa";
                    $check_total_days = round($check_total_days);
                    $tl2 = 0;
                    $credited = 0;
                    $non_credited = 0;
                    $single_date_applied = true;
                    
                    // this is for end date ky sometimes ang end date ky lahi ug schedule
                    $fl_hours_worked = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($concat_end_date)),$work_schedule_id);
                    if($check_workshift != FALSE){
                        $tl1 = $last_block_hours;
                        $last_date = date("Y-m-d",strtotime($date2));
                        
                        if(date("A",strtotime($work_start_time)) == 'PM' && date("A",strtotime($work_end_time)) == 'AM') {
                            if (date("A",strtotime($date1)) == 'AM' && date("A",strtotime($date2)) == 'AM') {
                                $last_date = date("Y-m-d",strtotime($date2." -1 day"));
                            }
                        }
                        
                        if($leave_units == "days"){
                            $used_credits = $tl1 / $fl_hours_worked;
                        }else{
                            $used_credits = $tl1 / $per_day_credit;
                        }
                    } else {
                        $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($start_date)));
                        $work_schedule_idx  = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$end_date);
                        $rest_dayx = $this->ews->get_rest_day($this->company_id,$work_schedule_idx,date("l",strtotime($end_date)));
                        $last_date = date("Y-m-d",strtotime($date2));
                        
                        if ($rest_day) {
                            $work_schedule_id   = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($current_date.' -1 day')));
                            $rest_dayx = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($start_date.' -1 day')));
                            
                            if (!$rest_dayx) {
                                $work_start_time    = $this->employee->for_leave_hoursworked_work_start_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,date("Y-m-d",strtotime($concat_start_date.' -1 day')));
                                $work_end_time      = $this->employee->for_leave_hoursworked_work_end_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,date("Y-m-d",strtotime($concat_start_date.' -1 day')));
                            }
                        }
                        
                        if ($rest_day) {
                            $last_date = date("Y-m-d",strtotime($end_date));
                        } elseif ($rest_dayx) {
                            $last_date = date("Y-m-d",strtotime($start_date));
                        }
                        
                        if(date("A",strtotime($work_start_time)) == 'PM' && date("A",strtotime($work_end_time)) == 'AM') {
                            if (date("A",strtotime($date1)) == 'AM' && date("A",strtotime($date2)) == 'AM') {
                                $last_date = date("Y-m-d",strtotime($date2." -1 day"));
                            } else {
                                $last_date = date("Y-m-d",strtotime($date1));
                            }
                        }
                        
                        $used_credits = $cont_tlr_hidden;
                    }
                    
                    if($effective_start_date_by != null && $effective_start_date != null) {
                        if(date('Y-m-d', strtotime($concat_start_date)) >= $eff_date) {
                            if($used_credits > $leave_credits){
                                $credited = $leave_credits;
                                $non_credited = $used_credits - $leave_credits;
                            }elseif($used_credits <= $leave_credits){
                                $credited =  $used_credits;
                            }
                        } else {
                            $credited = 0;
                            $non_credited = 0;
                        }
                    } else {
                        if($used_credits > $leave_credits){
                            $credited = $leave_credits;
                            $non_credited = $used_credits - $leave_credits;
                        }elseif($used_credits <= $leave_credits){
                            $credited =  $used_credits;
                        }
                    }
                    
                    if($credited < 0) {
                        $credited = 0;
                    }
                    
                    if ($single_date_applied) {
                        $date_start = $start_time;
                        $date_end = $end_time;
                    } else {
                        if (strtotime($work_end_time) > strtotime($end_time)) {
                            $date_end = $end_time;
                        }
                        
                        if (strtotime($work_start_time) < strtotime($start_time)) {
                            $date_start = $work_start_time;
                        } else {
                            $date_start = $start_time;
                        }
                    }
                    
                    $no_of_hours = $credited * 8; // if credits, not days ang unit.
                    if($leave_units == "days"){
                        $check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($last_date)),$work_schedule_id);
                        $no_of_hours = $credited * $check_for_working_hours;
                    }
                    
                    $update_data2 = array(
                        "shift_date" => date("Y-m-d",strtotime($shift_date)),
                        'flag_parent' => 'no',
                        "credited" => $credited,
                        "non_credited" => $non_credited,
                        "no_of_hours" => $credited * 8
                        
                    );
                    
                    $this->db->where('employee_leaves_application_id',$view_last_row_leave_application->employee_leaves_application_id);
                    $this->db->update("employee_leaves_application",$update_data2);
                }
            }
            
            // send email notification to approver
            $leave_info     = $this->agm->leave_information($view_last_row_leave_application->employee_leaves_application_id);
            $void_v2 = $this->employee_v2->check_payroll_lock_closed($leave_info->emp_id,$leave_info->company_id,date("Y-m-d", strtotime($leave_info->shift_date)));
            
            // $void_v2 returns false of "Closes" or "Waiting for approval"
            if($void_v2 == "Closed" && $leave_info) {
            // if($void_v2 && $leave_info) {
                $date_insert = array(
                    "employee_leaves_application_id" => $leave_info->employee_leaves_application_id,
                    "work_schedule_id" => $leave_info->work_schedule_id,
                    "emp_id" => $leave_info->emp_id,
                    "company_id" => $leave_info->company_id,
                    "date_filed" => $leave_info->date_filed,
                    "leave_type_id" => $leave_info->leave_type_id,
                    "reasons" => $leave_info->reasons,
                    "shift_date" => $leave_info->shift_date,
                    "date_start" => $leave_info->date_start,
                    "date_end" => $leave_info->date_end,
                    "date_filed" => $leave_info->date_filed,
                    "note" => $leave_info->note,
                    "total_leave_requested" => $leave_info->total_leave_requested,
                    "leave_application_status" => $leave_app_status,
                    "leaves_id" => $leave_info->leaves_id,
                    "flag_parent" => $leave_info->flag_parent,
                    "credited" => $leave_info->credited,
                    "required_file_documents" => $leave_info->required_file_documents,
                    "status" => $leave_info->status,
                    "approver_account_id" => $leave_info->approver_account_id,
                    "previous_credits" => $leave_info->previous_credits,
                    "exclude_lunch_break" => $leave_info->exclude_lunch_break,
                    "leave_request_type" => $leave_info->leave_request_type,
                    "no_of_hours" => $leave_info->credited * 8
                );
                
                $this->db->insert('leaves_close_payroll', $date_insert);
                $id = $this->db->insert_id();
                if($leave_info->flag_parent == "yes") {
                    $get_leave_apps_child = $this->todo_leave->get_leave_apps_child($leave_info->emp_id,$leave_info->company_id,$leave_info->employee_leaves_application_id);
                    
                    if($get_leave_apps_child) {
                        foreach ($get_leave_apps_child as $glac) {
                            $date_insert1 = array(
                                "employee_leaves_application_id" => $glac->employee_leaves_application_id,
                                "work_schedule_id" => $glac->work_schedule_id,
                                "emp_id" => $glac->emp_id,
                                "company_id" => $glac->company_id,
                                "date_filed" => $glac->date_filed,
                                "leave_type_id" => $glac->leave_type_id,
                                "reasons" => $glac->reasons,
                                "shift_date" => $glac->shift_date,
                                "date_start" => $glac->date_start,
                                "date_end" => $glac->date_end,
                                "date_filed" => $glac->date_filed,
                                "note" => $leave_info->note,
                                "total_leave_requested" => $glac->total_leave_requested,
                                "leave_application_status" => $leave_app_status,
                                "leaves_id" => $glac->leaves_id,
                                "flag_parent" => $glac->flag_parent,
                                "credited" => $glac->credited,
                                "required_file_documents" => $glac->required_file_documents,
                                "status" => $glac->status,
                                "approver_account_id" => $glac->approver_account_id,
                                "previous_credits" => $glac->previous_credits,
                                "exclude_lunch_break" => $glac->exclude_lunch_break,
                                "leave_request_type" => $glac->leave_request_type,
                                "leaves_id" => $glac->leaves_id,
                                "no_of_hours" => $glac->credited * 8
                            );
                            
                            $this->db->insert('leaves_close_payroll', $date_insert1);
                        }
                    }
                }
                
                // update for_resend_auto_rejected_id
                $fields = array(
                    "for_resend_auto_rejected_id" => $id,
                );
                
                $where1 = array(
                    "employee_leaves_application_id"=>$leave_info->employee_leaves_application_id,
                    "company_id"=>$leave_info->company_id,
                );
                
                $this->db->where($where1);
                $this->db->update("employee_leaves_application",$fields);
                
                // update also the child : for_resend_auto_rejected_id
                if($leave_info->flag_parent == "yes") {
                    $child_where = array(
                        "leaves_id"=>$leave_info->employee_leaves_application_id,
                        "company_id"=>$leave_info->company_id,
                        "emp_id" => $leave_info->emp_id,
                    );
                    
                    $this->db->where($child_where);
                    $this->db->update("employee_leaves_application",$fields);
                }
                
            }
            
            $val = $view_last_row_leave_application->employee_leaves_application_id;
            if($what_portal == "employee") {
                // send email notification to approver
                $leave_approver = $this->agm->get_approver_name_leave($this->emp_id,$this->company_id);
                $fullname       = ucfirst($leave_info->first_name)." ".ucfirst($leave_info->last_name);
                $psa_id         = $this->session->userdata('psa_id');
                
                $str            = 'abcdefghijk123456789';
                $shuffled       = str_shuffle($str);
                
                // generate token level
                $str2 = 'ABCDEFG1234567890';
                $shuffled2 = str_shuffle($str2);
                
                $get_approval_settings_disable_status = $this->employee->get_approval_settings_disable_status($this->company_id);
                
                $approver_id = $this->employee->get_approver_name($this->emp_id,$this->company_id)->leave_approval_grp;
                if($approver_id == "" || $approver_id == 0) {
                    // Employee with no approver will use default workflow approval
                    add_workflow_approval_default_group($this->company_id,$this->account_id); // create default if dont have any
                    $approver_id = get_app_default_approver($this->company_id,"Leave")->approval_groups_via_groups_id;
                }
                
                $workforce_notification = get_notify_settings($approver_id, $this->company_id);
                if($approver_id) {
                    if(is_workflow_enabled($this->company_id)){
                        if($leave_approver){
                            $last_level = 1; //$this->leave->get_leave_last_level($leave_info->emp_id, $leave_info->company_id);
                            $new_level = 1;
                            $lflag = 0;
                            
                            // without leveling
                            if($workforce_notification){
                                foreach ($leave_approver as $la){
                                    $appovers_id = ($la->emp_id) ? $la->emp_id : "-99{$this->company_id}";
                                    $get_approval_group_via_groups_owner = $this->agm->get_approval_group_via_groups_owner($la->approval_process_id, $la->company_id, $la->approval_groups_via_groups_id,$appovers_id);
                                    
                                    if($get_approval_group_via_groups_owner->emp_id == "-99{$this->company_id}"){
                                        $owner_approver = get_approver_owner_info($this->company_id);
                                        $appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                                        $appr_account_id = $owner_approver->account_id;
                                        $appr_email = $owner_approver->email;
                                        $appr_id = "-99{$this->company_id}";
                                    } else {
                                        $appr_name = ucwords($la->first_name." ".$la->last_name);
                                        $appr_account_id = $la->account_id;
                                        $appr_email = $la->email;
                                        $appr_id = $la->emp_id;
                                    }
                                    
                                    if($la->level == $new_level){
                                        
                                        ###check email settings if enabled###
                                        if($la->ns_leave_email_flag == "yes"){
                                            // send with link
                                            emp_leave_app_notification($shuffled, $view_last_row_leave_application->employee_leaves_application_id, $this->company_id, $leave_info->emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2,$appr_id);
                                        }
                                        ###end checking email settings if enabled###
                                        
                                        if($workforce_notification->sms_notification == "yes"){
                                            $url = base_url()."approval/leave/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
                                            $sms_message = "Click {$url} to approve {$fullname}'s leave.";
                                            send_this_sms_global($this->company_id, $appr_account_id,$sms_message,$psa_id,false);
                                        }
                                        
                                        if($workforce_notification->twitter_notification == "yes"){
                                            $check_twitter_acount = $this->agm->check_twitter_acount($appr_account_id);
                                            if($check_twitter_acount){
                                                $token = $this->leave->get_token($val, $this->company_id, $leave_info->emp_id);
                                                $url = base_url()."approval/leave/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
                                                $message = "A leave application has been filed by {$fullname} and is now waiting for your approval. Click this link {$url} to approve.";
                                                $recipient_account = $check_twitter_acount->twitter;
                                                $this->tweetontwitter($this->emp_id,$message,$recipient_account);
                                            }
                                        }
                                        
                                        if($workforce_notification->facebook_notification == "yes"){
                                            // coming soon
                                        }
                                        
                                        if($workforce_notification->message_board_notification == "yes"){
                                            $token = $this->leave->get_token($val, $this->company_id, $leave_info->emp_id);
                                            $url = base_url()."approval/leave/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
                                            $next_appr_notif_message = "A leave application below has been filed by {$fullname} and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
                                            send_to_message_board($psa_id, $appr_id, $this->emp_id, $this->company_id, $next_appr_notif_message, "system","warning");
                                        }
                                    }
                                }
                            }
                            
                            $save_token = array(
                                "leave_id"               => $view_last_row_leave_application->employee_leaves_application_id,
                                "token"                  => $shuffled,
                                "comp_id"                => $this->company_id,
                                "emp_id"                 => $this->emp_id,
                                "approver_id"            => $approver_id,
                                "level"                  => $new_level,
                                "token_level"            => $shuffled2,
                                "date_approved_level"    => date('Y-m-d H:i:s'),
                                "date_reminder_level"    => date('Y-m-d H:i:s')
                            );
                            
                            $flag_if_insert_suceess = $this->db->insert("approval_leave",$save_token);

                            if(!$flag_if_insert_suceess) {
                                $this->db->insert("approval_leave",$save_token);
                            }
                            
                            $result = array(
                                'error'             => false,
                                'approver_error'    => "",
                                'err_msg'			=> ''
                            );
                            echo json_encode($result);
                            return false;
                        }else{
                            $new_level = 1;
                            
                            $save_token = array(
                                "leave_id"               => $view_last_row_leave_application->employee_leaves_application_id,
                                "token"                  => $shuffled,
                                "comp_id"                => $this->company_id,
                                "emp_id"                 => $this->emp_id,
                                "approver_id"            => $approver_id,
                                "level"                  => $new_level,
                                "token_level"            => $shuffled2,
                                "date_approved_level"    => date('Y-m-d H:i:s'),
                                "date_reminder_level"    => date('Y-m-d H:i:s')
                            );
                            
                            $save_token_q = $this->db->insert("approval_leave",$save_token);
                            
                            $result = array(
                                'error'             => false,
                                'approver_error'    => "",
                                'err_msg'			=> ''
                            );
                            
                            echo json_encode($result);
                            return false;
                        }
                    } else {
                        if($get_approval_settings_disable_status->status == "Inactive") {
                            $value1 = array(
                                "approve_by_hr" => "Yes",
                                "approve_by_head" => "Yes"
                            );
                            $w1 = array(
                                "leave_id" => $view_last_row_leave_application->employee_leaves_application_id,
                                "comp_id" => $this->company_id
                            );
                            $this->db->where($w1);
                            $this->db->update("approval_leave",$value1);
                            $this->leave->new_update_employee_leaves($this->company_id, $this->emp_id, $leave_type, floatval($view_last_row_leave_application->total_leave_requested), $val);
                            
                            $result = array(
                                'error'             => false,
                                'approver_error'    => "",
                                'err_msg'			=> ''
                            );
                            
                            echo json_encode($result);
                            return false;
                        }
                    }
                }else{
                    $result = array(
                     	'error'             => true,
                     	'approver_error'    => "",
                     	'err_msg'			=> 'Unable to notify your approver.'
                 	);
                     
                 	echo json_encode($result);
                 	return false;
                }
                // save token to approval leave
            } else {
            	$this->leave->new_update_employee_leaves($this->company_id, $this->emp_id, $leave_type, floatval($view_last_row_leave_application->total_leave_requested), $val);
                            
                $result = array(
                    'error'             => false,
                    'approver_error'    => "",
                    'err_msg'			=> ''
                );
                
                echo json_encode($result);
                return false;
            }
            
        }else{
            $shift_err  = "";
            $start_err  = "";
            $end_err    = "";
            #$return_err = "";
            $att_err    = form_error('upload_numbers');
            
            if(form_error('shift_date') !=""){
                $shift_err = 'The Date Field is Required';
            }
            
            if(form_error('start_date')!=""){
                if(form_error('start_date_hr') != "" || form_error('start_date_min') != "" || form_error('start_date_sec') != ""){
                    $start_err = 'The Start Date and Time Field is Required';
                }else{
                    $start_err = 'The Start Date Field is Required';
                }
            }
            
            if(form_error('end_date')!=""){
                
                if(form_error('end_date_hr') != "" || form_error('end_date_min') != "" || form_error('end_date_sec') != ""){
                    $end_err = 'The End Date and Time Field is Required';
                }else{
                    $end_err = 'The End Date Field is Required';
                }
            }
            
            $result = array(
                'error'         => true,
                'eshift_date'   => $shift_err,
                'eleave_type'   => form_error('leave_type'),
                'ereason'       => "This field is required.",
                'estart_date'   => $start_err,
                'eend_date'     => $end_err,
                'eatt'          => $att_err,
                'err_msg'       => "Please fill out the form correctly."
            );
            
            echo json_encode($result);
            return false;
        }

        $result = array(
            'error'             => true,
            'approver_error'    => "",
            'err_msg'			=> "App leave is unable to handle this request. Please use the web instead."
        );
        
        echo json_encode($result);
        return false;
    
    }

    function submit_leaves_debug() {
        $what_portal = "employee";
        $leave_app_status = "pending";
        $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id, "leave");
        if($get_lock_payroll_process_settings) {
            if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                $result = array(
                    'error'=>true,
                    'err_msg'=>$get_lock_payroll_process_settings->application_error,
                );
                echo json_encode($result);
                return false;
            } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                $result = array(
                    'error'=>true,
                    'err_msg'=>$get_lock_payroll_process_settings->ts_app_recalculation_err_msg,
                );
                echo json_encode($result);
                return false;
            } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                $result = array(
                    'error'=>true,
                    'err_msg'=>$get_lock_payroll_process_settings->py_app_recalculation_err_msg,
                );
                echo json_encode($result);
                return false;
            }
        }

		$this->emp_id = $this->emp_id;
					
		$flag = $this->input->post('flag');
			
        $leave_type = $this->input->post('leave_type');
        $reason             = $this->input->post('reason');
        $start_date         = date("Y-m-d",strtotime($this->input->post('start_date')));
        $start_time         = date("h:i A",strtotime($this->input->post('start_time')));
        
        $end_date           = date("Y-m-d",strtotime($this->input->post('end_date')));
        $end_time           = date("h:i A",strtotime($this->input->post('end_time')));
        
        $cont_tlr_hidden    = $this->input->post('cont_tlr_hidden');
        $previous_credits   = $this->input->post('previous_credits');
        $schedule_blocks_id = $this->input->post('schedule_blocks_id');
        
        $no_lunch_hrs       = $this->input->post('lunch_hr_required');
        $leave_request_type = $this->input->post('leave_request_type');

		
		$this->emp_id = "2032";
		$this->company_id = "62";
					
		$flag = $this->input->post('flag');
			
        $leave_type 		= "327";
        $reason             = "qwertysz";
        $start_date         = date("Y-m-d",strtotime("2020-03-30"));
        $start_time         = date("h:i A",strtotime("08:00 AM"));
        
        $end_date           = date("Y-m-d",strtotime("2020-04-01"));
        $end_time           = date("h:i A",strtotime("05:00 PM"));
        
        $cont_tlr_hidden    = "1";
        $previous_credits   = "20";
        $schedule_blocks_id = $this->input->post('schedule_blocks_id');
        
        $no_lunch_hrs       = "0";
        $leave_request_type = "";

        $this->my_location = $this->employee_v2->get_location_of_emp($this->company_id,$this->emp_id)->location_and_offices_id;
        
        $lunch_hr_required = "no";
        if ($no_lunch_hrs == "1") {
            $lunch_hr_required = "yes";
        }
        
        if($start_date) {
            $ws_id              = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$start_date);
            $check_workday      = $this->employee->check_workday_ws($this->emp_id,$this->company_id,$ws_id);
            $start_date_req     = date("l",strtotime($start_date));
            $check_regular      = $this->employee->check_regular_ws($this->emp_id,$this->company_id,$ws_id,$start_date_req);
            $check_workshift    = $this->employee->check_workshift_ws($this->emp_id,$this->company_id,$ws_id);
        	$get_open_shift_leave = $this->employee_v2->get_open_shift_leave($ws_id, $this->company_id);
        } else {
            $result = array(
                'error'=>true,
                'err_msg'=>"Start Date is required."
            );
            echo json_encode($result);
            return false;
        }
        
        if($end_date == "" || $end_date == "1970-01-01"){
            $result = array(
                'error'=>true,
                'err_msg'=>"End Date is required."
            );
            echo json_encode($result);
            return false;
        }

        $please_yaw_ka_zero = is_numeric($cont_tlr_hidden);
                    
        if ($please_yaw_ka_zero) {
            if ($cont_tlr_hidden <= 0) {
                $result = array(
                    'error'=>true,
                    'err_msg'=>"Your total leave requested is zero."
                );
                echo json_encode($result);
                return false;
            }
        } else {
            $result = array(
                'error'=>true,
                'err_msg'=>"Please wait for the form to complete processing your request and try again."
            );
            echo json_encode($result);
            return false;
        }

        // $point_something = explode(".", $cont_tlr_hidden);
        // $point_something_val = $point_something[0] + .50;

        // if($this->company_id == "206") {
        //     if($cont_tlr_hidden > $point_something_val) {
        //         $result = array(
        //             'error'=>true,
        //             'etlr'=>"Your leave setting allows partial leave of atleast .5 leave in a fraction."
        //         );
        //         echo json_encode($result);
        //         return false;
        //     }
        // }
        
        // $this->form_validation->set_rules("leave_type", 'Leave Type', 'trim|required|xss_clean');
        // $this->form_validation->set_rules("reason", 'Reason', 'trim|required|xss_clean');
        // $this->form_validation->set_rules("start_date", 'Start Date', 'trim|required|xss_clean');
        // $this->form_validation->set_rules("end_date", 'End Date', 'trim|required|xss_clean');
        
        // if(!$check_workday) {
        //     $this->form_validation->set_rules("start_time", 'Start Time', 'trim|required|xss_clean');
        //     $this->form_validation->set_rules("end_time", 'End Time', 'trim|required|xss_clean');
        // }
        
        // $this->form_validation->set_rules('upload_numbers','Attachments','callback_attachment_check');
        // $this->form_validation->set_rules("cont_tlr_hidden", 'Total Leave Requested', 'trim|required|xss_clean');
        // $this->form_validation->set_error_delimiters('', '');
        
        if (true){
            $concat_start_datetime  = date("H:i:s",strtotime($start_time));
            $concat_start_date      = $start_date." ".$concat_start_datetime;
            $concat_end_datetime    = date("H:i:s",strtotime($end_time));
            $concat_end_date        = $end_date." ".$concat_end_datetime;
            
            $get_current_shift      = $this->employee->get_current_shift($ws_id, $this->company_id, $start_date, $this->emp_id);

            $shift_date = date("Y-m-d",strtotime($start_date)); // input mani ang shift date nya g.pakuha man.. mao start ako g.default, dghan nmn nggamit sa $shift_date
            $check_work_type = $this->employee->work_schedule_type($ws_id, $this->company_id);
            
            if ($get_current_shift) { // gi reuse nlng ni nako na code ni fil..
                foreach ($get_current_shift as $zz) {
                    if($zz->work_type_name == "Flexible Hours") {
                        $ur_start = date("A",strtotime($zz->latest_time_in_allowed));
                        
                        $total_hours_for_the_day = $zz->total_hours_for_the_day * 60;
                        $latest_time_out_allowed = date("H:i:s",strtotime($zz->latest_time_in_allowed.' +'.$total_hours_for_the_day.' minutes'));
                        $boundary_end_to_new_start = $latest_time_out_allowed;
                        $the_new_start = date("H:i:s", strtotime($concat_start_date));
                        
                        $ur_end = date("A",strtotime($latest_time_out_allowed));
                        
                        $input_start_datetime = date("A",strtotime($concat_start_date));
                        $input_end_datetime = date("A",strtotime($concat_end_date));
                        
                        if($ur_start == "PM" && $ur_end == "AM") {
                            if(strtotime($boundary_end_to_new_start) < strtotime($the_new_start)) {
                                $shift_date = date("Y-m-d", strtotime($start_date));
                            } else {
                                if($input_start_datetime == "AM" && $input_end_datetime == "AM") {
                                    $shift_date = date("Y-m-d", strtotime($start_date." -1 day"));
                                } else {
                                    if(strtotime($boundary_end_to_new_start) >= strtotime($the_new_start)){
                                        $shift_date = $start_date;
                                    }
                                }
                            }
                        }
                        
                        $lunch_hr_required = "yes";
                    } else {
                        $ur_start = date("A",strtotime($zz->start));
                        $ur_end = date("A",strtotime($zz->end));
                        
                        $input_start_datetime = date("A",strtotime($concat_start_date));
                        $input_end_datetime = date("A",strtotime($concat_end_date));
                        
                        if($ur_start == "PM" && $ur_end == "AM") {
                            if($input_start_datetime == "AM" && $input_end_datetime == "AM") {
                                $shift_date = date("Y-m-d", strtotime($start_date." -1 day"));
                            }
                        }
                        
                        // no timesheet required trapping : can file within a shift only
                        $shift_start_date = $start_date." ".$zz->start;
                        $shift_end_date = $end_date." ".$zz->end;
                        
                        if (check_if_timein_is_required($this->emp_id,$this->company_id) == "no") {
                            if (strtotime($concat_start_date) < strtotime($shift_start_date)) {
                                $result = array(
                                    'error' => true,
                                    'eend_date' => 'You can only file a leave within your shift.'
                                );
                                echo json_encode($result);
                                return false;
                            } elseif (strtotime($shift_end_date) < strtotime($concat_end_date)) {
                                $result = array(
                                    'error' => true,
                                    'eend_date' => 'You can only file a leave within your shift.'
                                );
                                echo json_encode($result);
                                return false;
                            }
                        }
                    }
                }
            } else {
                $incase_rd = $this->ews->get_rest_day($this->company_id,$ws_id,date("l",strtotime($start_date)));
                if($incase_rd && $leave_request_type == "Partial Day") {
                    $shift_date = date("Y-m-d", strtotime($start_date." -1 day"));
                }
            }
            
            $is_flexi = false;
            if($check_work_type == "Flexible Hours"){
                $is_flexi = true;
            }
            
            $check_existing_leave_applied = $this->employee->check_existing_leave_applied($this->emp_id, $this->company_id, $concat_start_date, $concat_end_date);
            if($check_existing_leave_applied) {
                if(!$is_flexi) {
                    if($check_existing_leave_applied->leave_application_status == 'approve') {
                        $leave_application_status = 'Approved';
                    } else {
                        $leave_application_status = $check_existing_leave_applied->leave_application_status;
                    }
                    
                    $leave_type = $this->employee->leave_type($this->company_id,$this->emp_id,$check_existing_leave_applied->leave_type_id);
                    
                    $leave_type_name = "";
                    if($leave_type) {
                        foreach ($leave_type as $hatch) {
                            $leave_type_name = $hatch->leave_type;
                        }
                    }
                    
                    $date_start_res = date('d-M-y h:i A', strtotime($check_existing_leave_applied->date_start));
                    $date_end_res = date('d-M-y h:i A', strtotime($check_existing_leave_applied->date_end));
                    /*if ($is_flexi) {
                     $date_start_res = date('d-M-y', strtotime($check_existing_leave_applied->date_start));
                     $date_end_res = date('d-M-y', strtotime($check_existing_leave_applied->date_end));
                     }*/
                    
                    $result = array(
                        'error' => true,
                        'existing_leave' => true,
                        'leave_type' => ucwords($leave_type_name),
                        'date_filed' => idates($check_existing_leave_applied->date_filed),
                        'date_start' => $date_start_res,
                        'date_end' => $date_end_res,
                        'leave_application_status' => $leave_application_status,
                        'err_msg' => 'You already have an existing leave filed for this date and time.'
                    );
                    echo json_encode($result);
                    return false;
                }
            }
            
            // check leave restriction
            $halfday_rest                       = $this->leave->get_leave_restriction($leave_type,'provide_half_day_option');
            $apply_limit_rest                   = $this->leave->get_leave_restriction($leave_type,'allow_to_apply_leaves_beyond_limit');
            $exclude_holidays                   = $this->leave->get_leave_restriction($leave_type,'exclude_holidays');
            $num_days_b4_leave                  = $this->leave->get_leave_restriction($leave_type,'num_days_before_leave_application');
            $days_b4_leave                      = $this->leave->get_leave_restriction($leave_type,'days_before_leave_application');
            $num_cons_days                      = $this->leave->get_leave_restriction($leave_type,'num_consecutive_days_allowed');
            $cons_days                          = $this->leave->get_leave_restriction($leave_type,'consecutive_days_allowed');
            $num_cons_days_week_hol             = $this->leave->get_leave_restriction($leave_type,'num_consecutive_days_after_weekend_holiday');
            $cons_days_week_hol                 = $this->leave->get_leave_restriction($leave_type,'consecutive_days_after_weekend_holiday');
            $required_documents                 = $this->leave->get_leave_restriction($leave_type,'required_documents');
            $exclude_rest_days                  = $this->leave->get_leave_restriction($leave_type,'exclude_rest_days');
            $paid_leave                         = $this->leave->get_leave_restriction($leave_type,'paid_leave');
            $effective_start_date_by            = $this->leave->get_leave_restriction($leave_type,'effective_start_date_by');
            $effective_start_date               = $this->leave->get_leave_restriction($leave_type,'effective_start_date');
            $leave_units                        = $this->leave->get_leave_restriction($leave_type,'leave_units');
            $what_happen_to_unused_leave        = $this->leave->get_leave_restriction($leave_type,'what_happen_to_unused_leave');
            $leave_conversion_run_every         = $this->leave->get_leave_restriction($leave_type,'leave_conversion_run_every');
            $carry_over_schedule_specific_month = $this->leave->get_leave_restriction($leave_type,'carry_over_schedule_specific_month');
            $carry_over_schedule_specific_day   = $this->leave->get_leave_restriction($leave_type,'carry_over_schedule_specific_day');
            $allow_negative_borrow_hours        = $this->leave->get_leave_restriction($leave_type,'allow_negative_borrow_hours');
            $allow_negative_borrow_unearned     = $this->leave->get_leave_restriction($leave_type,'allow_negative_borrow_unearned');
            $exclude_regular_holidays           = $this->leave->get_leave_restriction($leave_type,'exclude_regular_holidays');
            $exclude_special_holidays           = $this->leave->get_leave_restriction($leave_type,'exclude_special_holidays');
            
            $partial_days_type                  = $this->leave->get_leave_restriction($leave_type,'partial_days_type');
            $no_min_hours_allowed               = $this->leave->get_leave_restriction($leave_type,'no_min_hours_allowed');
            $no_duration_hours                  = $this->leave->get_leave_restriction($leave_type,'no_duration_hours');
            
            $eff_date                           = $this->leave->get_leave_eff_date($leave_type,$this->company_id,$this->emp_id,"effective_date");
            
            /* CHECK EMPLOYEE WORK SCHEDULE */
            if(strtotime($start_date) > strtotime($end_date)){
                $result = array(
                    'error'=>true,
                    'err_msg'=>"The end date you entered occurs before the start date."
                );
                echo json_encode($result);
                return false;
            }elseif(strtotime($start_date) == strtotime($end_date)){
                if(!$check_workday) {
                    if(strtotime($concat_start_datetime) > strtotime($concat_end_datetime)){
                        $result = array(
                            'error' => true,
                            'err_msg' => "The end time you entered occurs before the start time of your leave."
                        );
                        echo json_encode($result);
                        return false;
                    }
                }
            }
            
            // Check : Number of days before which the leave application should be submitted
            $date_now = date('Y-m-d');
            $exact_date_to_apply = date('Y-m-d', strtotime($num_days_b4_leave.' day', strtotime($date_now)));
            $start_date_to_apply = date('Y-m-d', strtotime($start_date));
            
            if($num_days_b4_leave != 0) {
                if($start_date_to_apply < $exact_date_to_apply) {
                    $result = array(
                            'error'=>true,
                            'err_msg'=>"You do not meet the required number of days for the leave application to be filed."
                    );
            
                    echo json_encode($result);
                    return false;
                }
            }
            
            //Check : Maximum number of consecutive days of leave allowed
            $lv_start = $concat_start_date;
            $lv_end = $concat_end_date;
            $total_lv = 0;
            $current_date       = $start_date;
            $per_day_credit     = $this->prm->average_working_hours_per_day($this->company_id);
            $work_schedule_id   = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$current_date);
            $work_start_time    = $this->employee->for_leave_hoursworked_work_start_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,$concat_start_date,$leave_request_type);
            $work_end_time      = $this->employee->for_leave_hoursworked_work_end_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,$concat_start_date);
            
            $chk_total_days = ((strtotime($lv_end) - strtotime($lv_start)) / 3600 / 24);
            
            if(round($chk_total_days) > 0){
                if($cons_days == 'yes') {
                    if($num_cons_days != null || $num_cons_days != 0){
                        if($num_cons_days < $cont_tlr_hidden) {
                            $result = array(
                                    'error'=>true,
                                    'err_msg'=>"You have exceeded the number of consecutive days of leaves allowed by your company."
                            );
                    
                            echo json_encode($result);
                            return false;
                        }
                    }
                }
            }
                        
            // check for leaves limits(2) per day
            $check_greater_than_2_leaves = $this->employee->check_greater_than_2_leaves($this->emp_id, $this->company_id, $shift_date);
            
            if($check_greater_than_2_leaves >= 2) {
                $result = array(
                        'error'=>true,
                        'err_msg'=> "You can only file up to two leaves per day."
                );
                    
                echo json_encode($result);
                return false;
            }
            
            // check for leaves within the calendar year only
            if($paid_leave == 'yes') {
                if($what_happen_to_unused_leave == "convert to cash" || $what_happen_to_unused_leave == "do nothing") {
                    $get_employee_details_by_empid = get_employee_details_by_empid($this->emp_id);
                    
                    $conversion_sched = "";
                    $this_year = date('Y');
                    if($leave_conversion_run_every == "annual") {
                        $conversion_sched = $this_year.'-12-31';
                        $conversion_sched = date('Y-m-d', strtotime($conversion_sched));
                        
                        $temp_conversion_sched = date('m-d', strtotime($conversion_sched));
                        $concat_start_date_year = date('Y', strtotime($concat_start_date)).'-'.$temp_conversion_sched;
                        $new_conversion_sched = date('Y-m-d', strtotime($concat_start_date_year));
                    } elseif ($leave_conversion_run_every == "anniversary") {
                        $date_hired = $get_employee_details_by_empid->date_hired;
                        $conversion_sched = $this_year.'-'.date('m-d', strtotime($date_hired));
                        $conversion_sched = date('Y-m-d', strtotime($conversion_sched));
                        
                        $temp_conversion_sched = date('m-d', strtotime($conversion_sched));
                        $concat_start_date_year = date('Y', strtotime($concat_end_date)).'-'.$temp_conversion_sched;
                        $new_conversion_sched = date('Y-m-d', strtotime($concat_start_date_year));
                    } elseif ($leave_conversion_run_every == "specific date") {
                        $conversion_sched = $this_year.'-'.$carry_over_schedule_specific_month.'-'.$carry_over_schedule_specific_day;
                        $conversion_sched = date('Y-m-d', strtotime($conversion_sched));
                        
                        $temp_conversion_sched = date('m-d', strtotime($conversion_sched));
                        $concat_start_date_year = date('Y', strtotime($concat_end_date)).'-'.$temp_conversion_sched;
                        $new_conversion_sched = date('Y-m-d', strtotime($concat_start_date_year));
                    }
                    
                    $concat_start_date_new = date('Y-m-d', strtotime($concat_start_date));
                    $concat_end_date_new = date('Y-m-d', strtotime($concat_end_date));
                    
                    if(strtotime($concat_start_date_new) < strtotime($conversion_sched) && strtotime($concat_end_date_new) > strtotime($conversion_sched)) {
                        $msg_date = date('M d, Y', strtotime($conversion_sched));
                        $result = array(
                            'error'=>true,
                            'etlr'=>"Your leave conversion is on {$msg_date}. You cannot apply leaves that span across this date. <br>Please apply separately."
                        );
                        
                        echo json_encode($result);
                        return false;
                    } elseif (strtotime($concat_start_date_new) < strtotime($new_conversion_sched) && strtotime($concat_end_date_new) > strtotime($new_conversion_sched)) {
                        $msg_date = date('M d, Y', strtotime($new_conversion_sched));
                        $result = array(
                            'error'=>true,
                            'etlr'=>"Your leave conversion is on {$msg_date}. You cannot apply leaves that span across this date. <br>Please apply separately."
                        );
                        
                        echo json_encode($result);
                        return false;
                    }
                }
            }
            
            $check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($start_date)));
            $tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($this->company_id,$work_schedule_id);
            $is_work = is_break_assumed($work_schedule_id);
            if($tardiness_rule_migrated_v3) {
                $is_work = false;
            }
            
            $void = $this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($shift_date)));
            
            if($void == "Waiting for approval"){
                $flag_payroll_correction = "yes";
                $disabled_btn = true;
            } elseif ($void == "Closed") {
                $flag_payroll_correction = "yes";
            } else {
                $flag_payroll_correction = "no";
            }
            
            // if one of the approver is inactive the approver group will automatically change to default (owner)
            change_approver_to_default($this->emp_id,$this->company_id,"leave_approval_grp",$this->account_id);
            
            $date1 = $concat_start_date;
            $date2 = $concat_end_date;
            
            $date_timein        = date("H:i:s",strtotime($date1));
            $date_timeout       = date("H:i:s",strtotime($date2));
            $check_hours        = strtotime(date("Y-m-d H:i:s",strtotime($date2))) - strtotime(date("Y-m-d H:i:s",strtotime($date1)));
            $total_leave_hours  = $check_hours / 3600;
            $total_hours        = $check_hours / 3600 / 24;
            
            $total_leave_request    = $cont_tlr_hidden;
            $duration               = number_format(round($total_leave_request,2),'2','.',',');
            
            // check restriction halfday is not allowed
            $work_schedule_id   = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($concat_start_date)));
            $req_hours_work     = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($concat_start_date)),$work_schedule_id) / 2;
            
            $err_mess   = 0;
            $limit_res  = '';
            $hd_res     = '';
            
            if($halfday_rest == 'yes'){
                if($partial_days_type == 'duration_hours') {
                    $point_something = explode(".", $cont_tlr_hidden);
                    #$point_something_val = $point_something[0] + $no_duration_hours;
                    $point_something_val = "0.".$point_something[1];
                    $no_duration_hours = number_format($no_duration_hours,3);
                    
                    // manual kaau :D change nlng if naa ky idea lain hahah na mas saun
                    $duration_pass = false;
                    if($no_duration_hours == 0.75) { // same rani ug scenario or outcome
                        if($point_something_val == 0.750){
                            $duration_pass = true;
                        } elseif ($cont_tlr_hidden >= 1 && $point_something_val == 0.500) {
                            $duration_pass = true;
                        } elseif ($cont_tlr_hidden >= 1 && $point_something_val == 0.250) {
                            $duration_pass = true;
                        } elseif ($cont_tlr_hidden >= 1 && $point_something_val == 0.000) {
                            $duration_pass = true;
                        }
                    } elseif ($no_duration_hours == 0.500) {
                        if ($point_something_val == 0.500) {
                            $duration_pass = true;
                        } elseif ($point_something_val == 0.000) {
                            $duration_pass = true;
                        }
                    } elseif ($no_duration_hours == 0.250) {
                        if($point_something_val == 0.750){
                            $duration_pass = true;
                        } elseif ($point_something_val == 0.500) {
                            $duration_pass = true;
                        } elseif ($point_something_val == 0.250) {
                            $duration_pass = true;
                        } elseif ($point_something_val == 0.000) {
                            $duration_pass = true;
                        }
                    }
                    
                    if(!$duration_pass) {
                        $result = array(
                            'error'=>true,
                            'etlr'=>"Your leave setting allows partial leave of atleast ".$no_duration_hours." leave in a fraction."
                        );
                        echo json_encode($result);
                        return false;
                    }
                } else {
                    if($leave_request_type == "Partial Day") {
                        $no_of_hrs_filed = $cont_tlr_hidden * 8;
                        if($no_min_hours_allowed > $no_of_hrs_filed) {
                            $result = array(
                                'error'=>true,
                                'etlr'=>"Your leave setting allows partial leave of atleast ".$no_min_hours_allowed." hours minimum."
                            );
                            echo json_encode($result);
                            return false;
                        }
                    }
                }
            } else {
                if($leave_request_type == "Partial Day") {
                    $hd_res     = "Partial day leave has been disabled by your company. You cannot apply for partial day leave.";
                    $err_mess   = $err_mess + 1;
                }
            }

            $get_all_pending_leave = $this->employee->get_all_pending_leave($this->company_id,$this->emp_id,$leave_type);
            
            if($paid_leave == 'yes') {
                if($apply_limit_rest == 'no'){
                    $check_leave_balance = $this->employee->leave_type($this->company_id,$this->emp_id,$leave_type);
                    
                    if($check_leave_balance){
                        foreach($check_leave_balance as $clb){
                            $remaining = $clb->remaining_leave_credits;
                            if($remaining == ''){
                                $remaining = $clb->leave_credits;
                            }
                        }
                        
                        $get_all_pending_leave = $this->employee->get_all_pending_leave($this->company_id,$this->emp_id,$leave_type);
                        $pending_credits = 0;
                        if ($get_all_pending_leave) {
                            foreach ($get_all_pending_leave as $gapl) {
                                $pending_credits += $gapl->total_leave_requested;
                            }
                        }
                        
                        $remaining = $remaining - $pending_credits;
                       # p($remaining);exit();
                        if($total_leave_request > $remaining){
                            $limit_res = "You cannot apply leaves beyond the allowed limit alloted by your company.";
                            $err_mess = $err_mess + 1;
                        }
                    }
                }
            }
            
            if($err_mess != 0){
                $err = array("error"=>true,"err_msg"=>$hd_res,"err_msg"=>$limit_res);
                echo json_encode($err);
            }
            
            if($effective_start_date_by != null && $effective_start_date != null) {
                if(date('Y-m-d', strtotime($concat_start_date)) >= $eff_date) {
                    $total_leave_request_save = $total_leave_request;
                    $leave_cedits_for_untitled = 0;
                } else {
                    $total_leave_request_save = 0;
                    $leave_cedits_for_untitled = $total_leave_request;
                }
            } else {
                $total_leave_request_save = $total_leave_request;
                $leave_cedits_for_untitled = 0;
            }
            
            $leave_credits = $this->employee->leave_type($this->company_id,$this->emp_id, $leave_type);
            if ($leave_credits) {
	            foreach ($leave_credits as $lc){
	                $leave_credits = ($lc->remaining_leave_credits != "") ? $lc->remaining_leave_credits : $lc->leave_credits;
	            }
	        }
            
            if ($allow_negative_borrow_unearned == "yes") {
                $leave_credits = $leave_credits + $allow_negative_borrow_hours;
                
                if ($total_leave_request > $leave_credits) {
                    $result = array(
                        'error'=>true,
                        'etlr'=>"Oh no! you can only borrow {$allow_negative_borrow_hours} {$leave_units} from leaves that you have yet to accrue/earn."
                    );
                    
                    echo json_encode($result);
                    return false;
                }
            }

            if ($please_yaw_ka_zero) {
                if ($cont_tlr_hidden <= 0) {
                    $result = array(
                        'error'=>true,
                        'etlr'=>"Your total leave requested is zero."
                    );
                    echo json_encode($result);
                    return false;
                }
            } else {
                $result = array(
                    'error'=>true,
                    'etlr'=>"Please wait for the form to complete processing your request and try again."
                );
                echo json_encode($result);
                return false;
            }
            
            
            $save_employee_leave = array(
                "shift_date"                 => $shift_date,
                "work_schedule_id"           => $work_schedule_id,
                "company_id"                 => $this->company_id,
                "emp_id"                     => $this->emp_id,
                "date_filed"                 => date("Y-m-d"),
                "leave_type_id"              => $leave_type,
                "reasons"                    => $reason,
                "date_start"                 => $concat_start_date,
                "date_end"                   => $concat_end_date,
                "total_leave_requested"      => $total_leave_request_save,
                "leave_cedits_for_untitled"  => $leave_cedits_for_untitled,
                "duration"                   => $duration,
                "note"                       => "",
                "leave_application_status"   => "pending",
                "attachments"                => "",
                "previous_credits"           => $previous_credits,
                "flag_payroll_correction"    => $flag_payroll_correction,
                "exclude_lunch_break"        => $lunch_hr_required,
                "leave_request_type"         => ($leave_request_type == "") ? null: $leave_request_type,
                "status"					     => "Active"
            );
            
            $upload_attachment = $this->input->post('upload_attachment');
            if($upload_attachment){
                $save_employee_leave['required_file_documents'] = $upload_attachment ?  implode(";",$upload_attachment) : '';
            }

            $name = ucwords($this->employee->get_approver_name($this->emp_id,$this->company_id)->first_name);
            $email = $this->employee->get_approver_name($this->emp_id,$this->company_id)->email; 

            // save employee leave application
            $this->db->insert('employee_leaves_application', $save_employee_leave);
            // esave('employee_leaves_application',$save_employee_leave);
            $insert_employee_leave = $this->db->insert_id();
            
            // $insert_employee_leave = $this->jmodel->insert_data('employee_leaves_application',$save_employee_leave);
            
            // view last row for leave application
            $view_last_row_leave_application = $this->employee->last_row_leave_app($this->emp_id,$this->company_id,$leave_type);
            $check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
            $check_is_date_holidayv2 = $this->employee_v2->check_is_date_holidayv2($this->company_id);
            
            if($check_workday != FALSE){ // calculate credited and non credited for flexi only
                $credited = 0;
                $non_credited = 0;
                
             	if(strtotime($start_date) == strtotime($end_date)) { // for one day
                 	if($effective_start_date_by != null && $effective_start_date != null) {
                 		if(date('Y-m-d', strtotime($start_date)) >= $eff_date) {
                         	if($cont_tlr_hidden > $leave_credits){
                             	$credited = $leave_credits;
                             	$non_credited = $cont_tlr_hidden - $leave_credits;
                         	}elseif($cont_tlr_hidden <= $leave_credits){
                             	$credited = $cont_tlr_hidden;
                         	}
                     	} else {
                         	$credited = 0;
                         	$non_credited = 0;
                     	}
                 	} else {
                     	if($cont_tlr_hidden > $leave_credits){
                         	$credited = $leave_credits;
                         	$non_credited = $cont_tlr_hidden - $leave_credits;
                     	}elseif($cont_tlr_hidden <= $leave_credits){
                         	$credited = $cont_tlr_hidden;
                     	}
                 	}

                 	if($credited < 0) {
                        $credited = 0;
                    }
                    
                    $no_of_hours = 8; // if credits, not days ang unit.
                    if($leave_units == "days"){
                        $no_of_hours = $credited * $fl_hours_worked;
                    }
                     
                     $update_data2 = array(
                         'flag_parent' => 'no',
                         "credited" => $credited,
                         "non_credited" => $non_credited,
                         "no_of_hours" => $no_of_hours
                     );
                     
                     $this->db->where('employee_leaves_application_id',$view_last_row_leave_application->employee_leaves_application_id);
                     $this->db->update("employee_leaves_application",$update_data2);
             	} else { // for multiple day
                     
                     // less than 24 hours
	                $fl_time = $check_workday->latest_time_in_allowed;
                    $duration_of_lunch_break_per_day = $check_workday->duration_of_lunch_break_per_day / 60;
                    $fl_hours_worked = $check_workday->total_hours_for_the_day - $duration_of_lunch_break_per_day;
                    
                    $days_filed = dateRange($start_date, $end_date);
                    $days_filed_cnt = count($days_filed);
                    $days_cnt = 0;
                    $used_credits_total = 0;
					if($days_filed) {
                        foreach ($days_filed as $date) {
                            $days_cnt++;
                            
                            $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($date)));
                            
                            // exclude holiday
                            $date_m_d = date("m-d", strtotime($date));
                            
                            $is_holiday_q = in_array_custom("date-{$date_m_d}",$check_is_date_holidayv2);
                            $is_holiday = false;
                            $is_hol_temp = false;
                            
                            // exclude holiday
                            if($is_holiday_q){
                                if($is_holiday_q->date_type == "fixed") {
                                    $app_m_d = date("m-d",strtotime($date));
                                    $hol_m_d = date("m-d",strtotime($is_holiday_q->date));
                                    
                                    if($app_m_d == $hol_m_d){
                                        $is_hol_temp = true;
                                    } else {
                                        $is_hol_temp = false;
                                    }
                                } else {
                                    $is_hol_temp = true;
                                }
                                
                                if($is_hol_temp) {
                                    $proceed1 = false;
                                    
                                    if($this->my_location != 0 || $this->my_location != null) {
                                        if($is_holiday_q->locations != "" || $is_holiday_q->locations != null) {
                                            $x = explode(",", $is_holiday_q->locations);
                                            foreach ($x as $loc) {
                                                if($loc == $this->my_location) {
                                                    $proceed1 = true;
                                                }
                                            }
                                        }
                                    }
                                    
                                    if($is_holiday_q->scope == "local" && !$proceed1) {
                                        $is_hol = FALSE;
                                    } else {
                                        $is_hol = TRUE;
                                    }
                                    
                                }
                            } else {
                                $is_hol = false;
                            }
                            
                            if($is_hol) {
                                $is_hol = holiday_leave_approval_settings($this->company_id);
                            }
                            
                            if($is_hol) {
                                if($is_holiday_q) {
                                    if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "yes"){
                                        $is_holiday = true;
                                    } else {
                                        if($is_holiday_q->hour_type_name == "Special Holiday") {
                                            // exclude Special holiday only
                                            if($exclude_holidays == 'yes' && $exclude_regular_holidays == "no" && $exclude_special_holidays == "yes"){
                                                $is_holiday = true;
                                            }
                                        } elseif($is_holiday_q->hour_type_name == "Regular Holiday") {
                                            // exclude Regular holiday only
                                            if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "no"){
                                                $is_holiday = true;
                                            }
                                        }
                                    }
                                }
                            }
                            
                            if(!$is_holiday && !$rest_day){
                                $check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date)),$work_schedule_id);
                                
                                if($days_filed_cnt == $days_cnt) { // for last loop
                                    if($leave_credits > 0) {
                                        $leave_credits = $leave_credits;
                                    } else {
                                        $used_credits = $cont_tlr_hidden - $used_credits_total;
                                        $leave_credits = 0;
                                    }
                                    
                                    if($effective_start_date_by != null && $effective_start_date != null) {
                                        if(date('Y-m-d', strtotime($start_date)) >= $eff_date) {
                                            if($used_credits > $leave_credits){
                                                $credited = $leave_credits;
                                                $non_credited = $used_credits - $leave_credits;
                                            }elseif($used_credits <= $leave_credits){
                                                $credited = $used_credits;
                                                $non_credited = 0;
                                            }
                                        } else {
                                            $credited = 0;
                                            $non_credited = 0;
                                        }
                                    } else {
                                        if($used_credits > $leave_credits){
                                            $credited = $leave_credits;
                                            $non_credited = $used_credits - $leave_credits;
                                        }elseif($used_credits <= $leave_credits){
                                            $credited = $used_credits;
                                        }
                                    }
                                    
                                } else {
                                    if($leave_units == "days"){
                                        $used_credits = $check_for_working_hours / $check_for_working_hours;
                                    }else{
                                        $used_credits = $check_for_working_hours / $per_day_credit;
                                    }
                                    
                                    if($effective_start_date_by != null && $effective_start_date != null) {
                                        if(date('Y-m-d', strtotime($start_date)) >= $eff_date) {
                                            if($used_credits > $leave_credits){
                                                $credited = $leave_credits;
                                                $non_credited = $used_credits - $leave_credits;
                                            }elseif($used_credits <= $leave_credits){
                                                $credited = $used_credits;
                                            }
                                        } else {
                                            $credited = 0;
                                            $non_credited = 0;
                                        }
                                    } else {
                                        if($used_credits > $leave_credits){
                                            $credited = $leave_credits;
                                            $non_credited = $used_credits - $leave_credits;
                                        }elseif($used_credits <= $leave_credits){
                                            $credited = $used_credits;
                                        }
                                    }
                                    
                                    $leave_credits = $leave_credits - $credited;
                                    $used_credits_total = $used_credits_total + $used_credits;
                                }
                                
                                if($credited < 0) {
                                    $credited = 0;
                                }
                                
                                $no_of_hours = 8; // if credits, not days ang unit.
                                if($leave_units == "days"){
                                    $no_of_hours = $credited * $fl_hours_worked;
                                }
                                
                                $each_leave = array(
                                    "shift_date"                 => date("Y-m-d",strtotime($date)),
                                    "work_schedule_id"           => $work_schedule_id,
                                    "company_id"                 => $this->company_id,
                                    "emp_id"                     => $this->emp_id,
                                    "leave_type_id"              => $leave_type,
                                    "reasons"                    => $reason,
                                    "date_start"                 => date("Y-m-d",strtotime($date)),
                                    "date_end"                   => date("Y-m-d",strtotime($date)),
                                    "leave_application_status"   => $leave_app_status,
                                    "credited"                   => $credited,
                                    "non_credited"               => $non_credited,
                                    "leaves_id"                  => $view_last_row_leave_application->employee_leaves_application_id,
                                    "previous_credits"           => $previous_credits,
                                    "flag_payroll_correction"    => $flag_payroll_correction,
                                    "no_of_hours"                => $no_of_hours,
                                    "status"					     => "Active"
                                );
                                
                                $this->db->insert("employee_leaves_application", $each_leave);
                            }
                        }
                        
                        $update_data = array('flag_parent'=>'yes');
                        $this->db->where('employee_leaves_application_id',$view_last_row_leave_application->employee_leaves_application_id);
                        $this->db->update("employee_leaves_application",$update_data);
                    }
                }
            } else { // calculate credited and non credited for uniform working sched and split only
                #p($check_total_days);
                if($check_total_days > 1){
                    // gi balik nako ugtwag na code para ni sa night shift na d nako ma prevent ang pagcheck sa partial day bisan d sya partial.
                    $work_start_time    = $this->employee->for_leave_hoursworked_work_start_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,$concat_start_date);
                    $check_total_days = round($check_total_days);
                    $tl2 = 0;
                    $credited = 0;
                    $non_credited = 0;
                    $single_date_applied = true;
                    $temp_total_used_leave1 = 0;
                    $last_date = date("Y-m-d",strtotime($date2));
                    $last_date_not_rd = date("Y-m-d",strtotime($date2));
                    
                    for($cnt=1;$cnt<=$check_total_days;$cnt++){ // for range date : calculate the credits from first - 2nd to the last, exclude the last date
                        $temp_date = date("Y-m-d",strtotime($shift_date." +".$cnt." day"));
                        $work_sched_id = check_employee_work_schedule(date("Y-m-d",strtotime($temp_date." -1 day")), $this->emp_id, $this->company_id)->work_schedule_id;
                        $rest_day = $this->ews->get_rest_day($this->company_id,$work_sched_id,date("l",strtotime($temp_date." -1 day")));
                        
                        $starttime_ampm = date("A", strtotime($date1));
                        $endtime_ampm = date("A", strtotime($date2));
                        
                        // exclude holiday
                        $for_holiday_date = date("Y-m-d",strtotime($temp_date." -1 day"));
                        $for_holiday_date_m_d = date("m-d", strtotime($for_holiday_date));
                        $is_holiday_q = in_array_custom("date-{$for_holiday_date_m_d}",$check_is_date_holidayv2);
                        $is_holiday_tl2 = false;
                        $is_hol_temp = false;
                        
                        // exclude holiday
                        if($is_holiday_q){
                            if($is_holiday_q->date_type == "fixed") {
                                $app_m_d = date("m-d",strtotime($for_holiday_date));
                                $hol_m_d = date("m-d",strtotime($is_holiday_q->date));
                                
                                if($app_m_d == $hol_m_d){
                                    $is_hol_temp = true;
                                } else {
                                    $is_hol_temp = false;
                                }
                            } else {
                                $is_hol_temp = true;
                            }
                            
                            if($is_hol_temp) {
                                $proceed1 = false;
                                
                                if($this->my_location != 0 || $this->my_location != null) {
                                    if($is_holiday_q->locations != "" || $is_holiday_q->locations != null) {
                                        $x = explode(",", $is_holiday_q->locations);
                                        foreach ($x as $loc) {
                                            if($loc == $this->my_location) {
                                                $proceed1 = true;
                                            }
                                        }
                                    }
                                }
                                
                                if($is_holiday_q->scope == "local" && !$proceed1) {
                                    $is_hol = FALSE;
                                } else {
                                    $is_hol = TRUE;
                                }
                                
                            }
                        } else {
                            $is_hol = false;
                        }
                        
                        if($is_hol) {
                            $is_hol = holiday_leave_approval_settings($this->company_id);
                        }
                        
                        if($is_hol) {
                            if($is_holiday_q) {
                                if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "yes"){
                                    $is_holiday_tl2 = true;
                                } else {
                                    if($is_holiday_q->hour_type_name == "Special Holiday") {
                                        // exclude Special holiday only
                                        if($exclude_holidays == 'yes' && $exclude_regular_holidays == "no" && $exclude_special_holidays == "yes"){
                                            $is_holiday_tl2 = true;
                                        }
                                    } elseif($is_holiday_q->hour_type_name == "Regular Holiday") {
                                        // exclude Regular holiday only
                                        if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "no"){
                                            $is_holiday_tl2 = true;
                                        }
                                    }
                                }
                            }
                        }
                        
                        $date_start = $work_start_time;
                        $date_end = $work_end_time;
                        

                        if(!$is_holiday_tl2 && !$rest_day){

                            $check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($temp_date." -1 day")),$work_schedule_id);
                            if($get_open_shift_leave) {
                                $check_for_working_hours = 8;
                            }
                            
                            $tl2 += $check_for_working_hours;
                            
                            if($cnt == 1) { // calculate the first date
                                $date_start = $start_time;
                                if(strtotime($shift_date) < strtotime($start_date)) { // if applied on 2nd half
                                    $start_br_down_date = $shift_date.' '.date("H:i:s", strtotime($work_start_time));
                                    $end_br_down_date = $start_date." ".$work_end_time;
                                    
                                    $new_concat_start_date = $start_date.' '.date("H:i:s", strtotime($concat_start_date));
                                    if(strtotime($start_br_down_date) < strtotime($new_concat_start_date)) {
                                        $start_br_down_date = $start_date.' '.date("H:i:s", strtotime($concat_start_date));
                                    }
                                    
                                    $new_concat_end_date = $start_date.' '.date("H:i:s", strtotime($concat_end_date));
                                    if(strtotime($end_br_down_date) > strtotime($new_concat_end_date)) {
                                        $end_br_down_date = $start_date.' '.date("H:i:s", strtotime($concat_end_date));
                                    }
                                    
                                    $check_start_time   = $this->employee_v2->get_leave_breaktime($this->emp_id,$this->company_id,date("l",strtotime($shift_date)),$work_schedule_id, $start_br_down_date, $end_br_down_date);
                                    $check_end_time     = $this->employee_v2->get_leave_breaktime($this->emp_id,$this->company_id,date("l",strtotime($shift_date)),$work_schedule_id, $start_br_down_date, $end_br_down_date,false);
                                    if($leave_units == 'days') {
                                        #$used_credits = ($this->employee_v2->get_tot_hours_ws_v2($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$check_for_working_hours,$work_schedule_id,0,false, $shift_date)) / $check_for_working_hours;
                                        $used_credits = ($this->employee_v2->get_tot_hours_ws_v3($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$hours_worked,$work_schedule_id,$shift_date,$lunch_hr_required)) / $hours_worked;
                                    } else {
                                        #$used_credits = ($this->employee_v2->get_tot_hours_ws_v2($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$check_for_working_hours,$work_schedule_id,0,false, $shift_date)) / $per_day_credit;
                                        $used_credits = ($this->employee_v2->get_tot_hours_ws_v3($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$hours_worked,$work_schedule_id,$shift_date,$lunch_hr_required)) / $per_day_credit;
                                    }
                                    
                                } else {
                                    if($leave_units == "days"){
                                        $used_credits = $check_for_working_hours / $check_for_working_hours;
                                    }else{
                                        $used_credits = $check_for_working_hours / $per_day_credit;
                                    }
                                }
                            } else {
                                if($leave_units == "days"){
                                    $used_credits = $check_for_working_hours / $check_for_working_hours;
                                }else{
                                    $used_credits = $check_for_working_hours / $per_day_credit;
                                }
                            }
                            
                            
                            if($effective_start_date_by != null && $effective_start_date != null) {
                                if(date('Y-m-d', strtotime($concat_start_date)) >= $eff_date) {
                                    if($used_credits > $leave_credits){
                                        $credited = $leave_credits;
                                        $non_credited = $used_credits - $leave_credits;
                                    }elseif($used_credits <= $leave_credits){
                                        $credited = $used_credits;
                                    }
                                } else {
                                    $credited = 0;
                                    $non_credited = 0;
                                }
                            } else {
                                if($used_credits > $leave_credits){
                                    $credited = $leave_credits;
                                    $non_credited = $used_credits - $leave_credits;
                                }elseif($used_credits <= $leave_credits){
                                    $credited = $used_credits;
                                }
                            }
                            
                            if($credited < 0) {
                                $credited = 0;
                            }
                            
                            $no_of_hours = $credited * 8; // if credits, not days ang unit.
                            if($leave_units == "days"){
                                $no_of_hours = $credited * $check_for_working_hours;
                            }
                            
                            $date_start_save = date("Y-m-d",strtotime($temp_date." -1 day")).' '.date("H:i:s", strtotime($date_start));
                            $date_end_save = date("Y-m-d",strtotime($temp_date." -1 day")).' '.date("H:i:s", strtotime($date_end));
                            
                            if ($starttime_ampm == "PM" && $endtime_ampm == "AM") {
                                $date_start_save = date("Y-m-d",strtotime($temp_date." -1 day")).' '.date("H:i:s", strtotime($date_start));
                                $date_end_save = date("Y-m-d",strtotime($temp_date)).' '.date("H:i:s", strtotime($date_end));
                            }
                            
                            $each_leave = array(
                                "shift_date"                 => date("Y-m-d",strtotime($temp_date." -1 day")),
                                "work_schedule_id"           => $work_schedule_id,
                                "company_id"                 => $this->company_id,
                                "emp_id"                     => $this->emp_id,
                                "leave_type_id"              => $leave_type,
                                "reasons"                    => $reason,
                                "date_start"                 => $date_start_save,
                                "date_end"                   => $date_end_save,
                                "leave_application_status"   => $leave_app_status,
                                "credited"                   => $credited,
                                "non_credited"               => $non_credited,
                                "leaves_id"                  => $view_last_row_leave_application->employee_leaves_application_id,
                                "previous_credits"           => $previous_credits,
                                "flag_payroll_correction"    => $flag_payroll_correction,
                                "no_of_hours"                => $no_of_hours,
                                "status"					   => "Active"
                            );
                            
                            $this->db->insert("employee_leaves_application", $each_leave);
                            $leave_credits = $leave_credits - $used_credits;
                            $temp_total_used_leave = $leave_credits - $used_credits;
                            $temp_total_used_leave1 += $credited;
                            $single_date_applied = false;
                            
                            $last_date_not_rd = $temp_date;
                        }
                    }
                    
                    if ($single_date_applied) {
                        $date_start = $start_time;
                        $date_end = $end_time;
                    } else {
                        if (strtotime($work_end_time) > strtotime($end_time)) {
                            $date_end = $end_time;
                        }
                        
                        if (strtotime($work_start_time) < strtotime($start_time)) {
                            $date_start = $work_start_time;
                        } else {
                            $date_start = $start_time;
                        }
                    }
                    
                    // this is for end date ky sometimes ang end date ky lahi ug schedule
                    $fl_hours_worked = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($concat_end_date)),$work_schedule_id);
                    if($check_workshift != FALSE){
                        $tl1 = $last_block_hours;
                        
                        if(date("A",strtotime($work_start_time)) == 'PM' && date("A",strtotime($work_end_time)) == 'AM') {
                            if (date("A",strtotime($date1)) == 'AM' && date("A",strtotime($date2)) == 'AM') {
                                $last_date = date("Y-m-d",strtotime($date2." -1 day"));
                            }
                        }
                        
                        $shift_date_last_save = date("Y-m-d",strtotime($last_date));
                        $date_start_last_save = $last_date.' '.date("H:i:s", strtotime($date_start));
                        $date_end_last_save = $last_date.' '.date("H:i:s", strtotime($date_end));
                        
                        if($leave_units == "days"){
                            $used_credits = $tl1 / $fl_hours_worked;
                        }else{
                            $used_credits = $tl1 / $per_day_credit;
                        }
                    } else {
                        $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($start_date)));
                        $work_schedule_idx  = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$end_date);
                        $rest_dayx = $this->ews->get_rest_day($this->company_id,$work_schedule_idx,date("l",strtotime($end_date)));
                        
                        if ($rest_day) {
                            $last_date = date("Y-m-d",strtotime($end_date));
                        } elseif ($rest_dayx) {
                            $last_date_not_rd_1 = date("Y-m-d", strtotime($last_date_not_rd.' +1 day'));
                            $work_schedule_idy  = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$last_date_not_rd_1);
                            $rest_dayy = $this->ews->get_rest_day($this->company_id,$work_schedule_idy,date("l",strtotime($last_date_not_rd_1)));
                            
                            if ($rest_dayy) {
                                #$last_date = $last_date_not_rd;
                            } else {
                                $last_date = date("Y-m-d", strtotime($last_date_not_rd.' +1 day'));
                            }
                        }
                        
                        $shift_date_last_save = date("Y-m-d",strtotime($last_date));
                        $date_start_last_save = $last_date.' '.date("H:i:s", strtotime($date_start));
                        $date_end_last_save = $last_date.' '.date("H:i:s", strtotime($date_end));
                        
                        if(date("A",strtotime($work_start_time)) == 'PM' && date("A",strtotime($work_end_time)) == 'AM') {
                            $shift_date_last_save = date("Y-m-d",strtotime($last_date.' -1 day'));
                            $date_start_last_save = date("Y-m-d", strtotime($last_date.' -1 day')).' '.date("H:i:s", strtotime($date_start));
                            $date_end_last_save = $last_date.' '.date("H:i:s", strtotime($date_end));
                        }
                        
                        if($single_date_applied) { // calculate the solo filed leave
                            $used_credits = $cont_tlr_hidden;
                        } else { // calculate the last date of the if range date applied
                            $used_credits = $cont_tlr_hidden - $temp_total_used_leave1;
                        }
                    }
                    
                    if($effective_start_date_by != null && $effective_start_date != null) {
                        if(date('Y-m-d', strtotime($concat_start_date)) >= $eff_date) {
                            if($used_credits > $leave_credits){
                                $credited = $leave_credits;
                                $non_credited = $used_credits - $leave_credits;
                            }elseif($used_credits <= $leave_credits){
                                $credited =  $used_credits;
                            }
                        } else {
                            $credited = 0;
                            $non_credited = 0;
                        }
                    } else {
                        if($used_credits > $leave_credits){
                            $credited = $leave_credits;
                            $non_credited = $used_credits - $leave_credits;
                        }elseif($used_credits <= $leave_credits){
                            $credited =  $used_credits;
                        }
                    }
                    
                    if($credited < 0) {
                        $credited = 0;
                    }
                    
                    $no_of_hours = $credited * 8; // if credits, not days ang unit.
                    if($leave_units == "days"){
                        $check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($shift_date_last_save)),$work_schedule_id);
                        $no_of_hours = $credited * $check_for_working_hours;
                    }
                    
                    $each_leave = array(
                        "shift_date"               => $shift_date_last_save,
                        "work_schedule_id"         => $work_schedule_id,
                        "company_id"               => $this->company_id,
                        "emp_id"                   => $this->emp_id,
                        "leave_type_id"            => $leave_type,
                        "reasons"                  => $reason,
                        "date_start"               => $date_start_last_save,
                        "date_end"                 => $date_end_last_save,
                        "leave_application_status" => $leave_app_status,
                        "credited"                 => $credited,
                        "non_credited"             => $non_credited,
                        "leaves_id"                => $view_last_row_leave_application->employee_leaves_application_id,
                        "previous_credits"         => $previous_credits,
                        "flag_payroll_correction"  => $flag_payroll_correction,
                        "no_of_hours"              => $no_of_hours,
                        "status"					   => "Active"
                    );
                    #p($each_leave);
                    #fuck you
                    $this->db->insert("employee_leaves_application", $each_leave);
                    $update_data = array('flag_parent'=>'yes');
                    $this->db->where('employee_leaves_application_id',$view_last_row_leave_application->employee_leaves_application_id);
                    $this->db->update("employee_leaves_application",$update_data);#fuck you
                } else {
                    #echo "dasdsa";
                    $check_total_days = round($check_total_days);
                    $tl2 = 0;
                    $credited = 0;
                    $non_credited = 0;
                    $single_date_applied = true;
                    
                    // this is for end date ky sometimes ang end date ky lahi ug schedule
                    $fl_hours_worked = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($concat_end_date)),$work_schedule_id);
                    if($check_workshift != FALSE){
                        $tl1 = $last_block_hours;
                        $last_date = date("Y-m-d",strtotime($date2));
                        
                        if(date("A",strtotime($work_start_time)) == 'PM' && date("A",strtotime($work_end_time)) == 'AM') {
                            if (date("A",strtotime($date1)) == 'AM' && date("A",strtotime($date2)) == 'AM') {
                                $last_date = date("Y-m-d",strtotime($date2." -1 day"));
                            }
                        }
                        
                        if($leave_units == "days"){
                            $used_credits = $tl1 / $fl_hours_worked;
                        }else{
                            $used_credits = $tl1 / $per_day_credit;
                        }
                    } else {
                        $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($start_date)));
                        $work_schedule_idx  = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$end_date);
                        $rest_dayx = $this->ews->get_rest_day($this->company_id,$work_schedule_idx,date("l",strtotime($end_date)));
                        $last_date = date("Y-m-d",strtotime($date2));
                        
                        if ($rest_day) {
                            $work_schedule_id   = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($current_date.' -1 day')));
                            $rest_dayx = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($start_date.' -1 day')));
                            
                            if (!$rest_dayx) {
                                $work_start_time    = $this->employee->for_leave_hoursworked_work_start_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,date("Y-m-d",strtotime($concat_start_date.' -1 day')));
                                $work_end_time      = $this->employee->for_leave_hoursworked_work_end_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,date("Y-m-d",strtotime($concat_start_date.' -1 day')));
                            }
                        }
                        
                        if ($rest_day) {
                            $last_date = date("Y-m-d",strtotime($end_date));
                        } elseif ($rest_dayx) {
                            $last_date = date("Y-m-d",strtotime($start_date));
                        }
                        
                        if(date("A",strtotime($work_start_time)) == 'PM' && date("A",strtotime($work_end_time)) == 'AM') {
                            if (date("A",strtotime($date1)) == 'AM' && date("A",strtotime($date2)) == 'AM') {
                                $last_date = date("Y-m-d",strtotime($date2." -1 day"));
                            } else {
                                $last_date = date("Y-m-d",strtotime($date1));
                            }
                        }
                        
                        $used_credits = $cont_tlr_hidden;
                    }
                    
                    if($effective_start_date_by != null && $effective_start_date != null) {
                        if(date('Y-m-d', strtotime($concat_start_date)) >= $eff_date) {
                            if($used_credits > $leave_credits){
                                $credited = $leave_credits;
                                $non_credited = $used_credits - $leave_credits;
                            }elseif($used_credits <= $leave_credits){
                                $credited =  $used_credits;
                            }
                        } else {
                            $credited = 0;
                            $non_credited = 0;
                        }
                    } else {
                        if($used_credits > $leave_credits){
                            $credited = $leave_credits;
                            $non_credited = $used_credits - $leave_credits;
                        }elseif($used_credits <= $leave_credits){
                            $credited =  $used_credits;
                        }
                    }
                    
                    if($credited < 0) {
                        $credited = 0;
                    }
                    
                    if ($single_date_applied) {
                        $date_start = $start_time;
                        $date_end = $end_time;
                    } else {
                        if (strtotime($work_end_time) > strtotime($end_time)) {
                            $date_end = $end_time;
                        }
                        
                        if (strtotime($work_start_time) < strtotime($start_time)) {
                            $date_start = $work_start_time;
                        } else {
                            $date_start = $start_time;
                        }
                    }
                    
                    $no_of_hours = $credited * 8; // if credits, not days ang unit.
                    if($leave_units == "days"){
                        $check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($last_date)),$work_schedule_id);
                        $no_of_hours = $credited * $check_for_working_hours;
                    }
                    
                    $update_data2 = array(
                        "shift_date" => date("Y-m-d",strtotime($shift_date)),
                        'flag_parent' => 'no',
                        "credited" => $credited,
                        "non_credited" => $non_credited,
                        "no_of_hours" => $credited * 8
                        
                    );
                    
                    $this->db->where('employee_leaves_application_id',$view_last_row_leave_application->employee_leaves_application_id);
                    $this->db->update("employee_leaves_application",$update_data2);
                }
            }
            
            // send email notification to approver

            $leave_info     = $this->agm->leave_information($view_last_row_leave_application->employee_leaves_application_id);
            
            // p($leave_info);

            $void_v2 = $this->employee_v2->check_payroll_lock_closed($leave_info->emp_id,$leave_info->company_id,date("Y-m-d", strtotime($leave_info->shift_date)));
                    
            // $void_v2 returns false of "Closes" or "Waiting for approval"
            // if($void_v2 == "Closed" && $leave_info) {
            // if($void_v2 && $leave_info) {
            if($void_v2 == "Closed" && $leave_info) {
                $date_insert = array(
                    "employee_leaves_application_id" => $leave_info->employee_leaves_application_id,
                    "work_schedule_id" => $leave_info->work_schedule_id,
                    "emp_id" => $leave_info->emp_id,
                    "company_id" => $leave_info->company_id,
                    "date_filed" => $leave_info->date_filed,
                    "leave_type_id" => $leave_info->leave_type_id,
                    "reasons" => $leave_info->reasons,
                    "shift_date" => $leave_info->shift_date,
                    "date_start" => $leave_info->date_start,
                    "date_end" => $leave_info->date_end,
                    "date_filed" => $leave_info->date_filed,
                    "note" => $leave_info->note,
                    "total_leave_requested" => $leave_info->total_leave_requested,
                    "leave_application_status" => $leave_app_status,
                    "leaves_id" => $leave_info->leaves_id,
                    "flag_parent" => $leave_info->flag_parent,
                    "credited" => $leave_info->credited,
                    "required_file_documents" => $leave_info->required_file_documents,
                    "status" => $leave_info->status,
                    "approver_account_id" => $leave_info->approver_account_id,
                    "previous_credits" => $leave_info->previous_credits,
                    "exclude_lunch_break" => $leave_info->exclude_lunch_break,
                    "leave_request_type" => $leave_info->leave_request_type,
                    "no_of_hours" => $leave_info->credited * 8
                );
                
                $this->db->insert('leaves_close_payroll', $date_insert);
                $id = $this->db->insert_id();
                if($leave_info->flag_parent == "yes") {
                    $get_leave_apps_child = $this->todo_leave->get_leave_apps_child($leave_info->emp_id,$leave_info->company_id,$leave_info->employee_leaves_application_id);
                    
                    if($get_leave_apps_child) {
                        foreach ($get_leave_apps_child as $glac) {
                            $date_insert1 = array(
                                "employee_leaves_application_id" => $glac->employee_leaves_application_id,
                                "work_schedule_id" => $glac->work_schedule_id,
                                "emp_id" => $glac->emp_id,
                                "company_id" => $glac->company_id,
                                "date_filed" => $glac->date_filed,
                                "leave_type_id" => $glac->leave_type_id,
                                "reasons" => $glac->reasons,
                                "shift_date" => $glac->shift_date,
                                "date_start" => $glac->date_start,
                                "date_end" => $glac->date_end,
                                "date_filed" => $glac->date_filed,
                                "note" => $leave_info->note,
                                "total_leave_requested" => $glac->total_leave_requested,
                                "leave_application_status" => $leave_app_status,
                                "leaves_id" => $glac->leaves_id,
                                "flag_parent" => $glac->flag_parent,
                                "credited" => $glac->credited,
                                "required_file_documents" => $glac->required_file_documents,
                                "status" => $glac->status,
                                "approver_account_id" => $glac->approver_account_id,
                                "previous_credits" => $glac->previous_credits,
                                "exclude_lunch_break" => $glac->exclude_lunch_break,
                                "leave_request_type" => $glac->leave_request_type,
                                "leaves_id" => $glac->leaves_id,
                                "no_of_hours" => $glac->credited * 8
                            );
                            
                            $this->db->insert('leaves_close_payroll', $date_insert1);
                        }
                    }
                }
                
                // update for_resend_auto_rejected_id
                $fields = array(
                    "for_resend_auto_rejected_id" => $id,
                );
                
                $where1 = array(
                    "employee_leaves_application_id"=>$leave_info->employee_leaves_application_id,
                    "company_id"=>$leave_info->company_id,
                );
                
                $this->db->where($where1);
                $this->db->update("employee_leaves_application",$fields);
                
                // update also the child : for_resend_auto_rejected_id
                if($leave_info->flag_parent == "yes") {
                    $child_where = array(
                        "leaves_id"=>$leave_info->employee_leaves_application_id,
                        "company_id"=>$leave_info->company_id,
                        "emp_id" => $leave_info->emp_id,
                    );
                    
                    $this->db->where($child_where);
                    $this->db->update("employee_leaves_application",$fields);
                }
                
            }
            
            $val = $view_last_row_leave_application->employee_leaves_application_id;
            if($what_portal == "employee") {
                // send email notification to approver
                $leave_approver = $this->agm->get_approver_name_leave($this->emp_id,$this->company_id);
                $fullname       = ucfirst($leave_info->first_name)." ".ucfirst($leave_info->last_name);
                $psa_id         = $this->session->userdata('psa_id');
                
                $str            = 'abcdefghijk123456789';
                $shuffled       = str_shuffle($str);
                
                // generate token level
                $str2 = 'ABCDEFG1234567890';
                $shuffled2 = str_shuffle($str2);
                
                $get_approval_settings_disable_status = $this->employee->get_approval_settings_disable_status($this->company_id);
                
                $approver_id = $this->employee->get_approver_name($this->emp_id,$this->company_id)->leave_approval_grp;
                if($approver_id == "" || $approver_id == 0) {
                    // Employee with no approver will use default workflow approval
                    add_workflow_approval_default_group($this->company_id,$this->account_id); // create default if dont have any
                    $approver_id = get_app_default_approver($this->company_id,"Leave")->approval_groups_via_groups_id;
                }
                
                $workforce_notification = get_notify_settings($approver_id, $this->company_id);
                if($approver_id) {
                    if(is_workflow_enabled($this->company_id)){
                        if($leave_approver){
                            $last_level = 1; //$this->leave->get_leave_last_level($leave_info->emp_id, $leave_info->company_id);
                            $new_level = 1;
                            $lflag = 0;
                            
                            // without leveling
                            if($workforce_notification){
                                foreach ($leave_approver as $la){
                                    $appovers_id = ($la->emp_id) ? $la->emp_id : "-99{$this->company_id}";
                                    $get_approval_group_via_groups_owner = $this->agm->get_approval_group_via_groups_owner($la->approval_process_id, $la->company_id, $la->approval_groups_via_groups_id,$appovers_id);
                                    
                                    if($get_approval_group_via_groups_owner->emp_id == "-99{$this->company_id}"){
                                        $owner_approver = get_approver_owner_info($this->company_id);
                                        $appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                                        $appr_account_id = $owner_approver->account_id;
                                        $appr_email = $owner_approver->email;
                                        $appr_id = "-99{$this->company_id}";
                                    } else {
                                        $appr_name = ucwords($la->first_name." ".$la->last_name);
                                        $appr_account_id = $la->account_id;
                                        $appr_email = $la->email;
                                        $appr_id = $la->emp_id;
                                    }
                                    
                                    if($la->level == $new_level){
                                        
                                        ###check email settings if enabled###
                                        if($la->ns_leave_email_flag == "yes"){
                                            // send with link
                                            emp_leave_app_notification($shuffled, $view_last_row_leave_application->employee_leaves_application_id, $this->company_id, $leave_info->emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2,$appr_id);
                                        }
                                        ###end checking email settings if enabled###
                                        
                                        if($workforce_notification->sms_notification == "yes"){
                                            $url = base_url()."approval/leave/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
                                            $sms_message = "Click {$url} to approve {$fullname}'s leave.";
                                            send_this_sms_global($this->company_id, $appr_account_id,$sms_message,$psa_id,false);
                                        }
                                        
                                        if($workforce_notification->twitter_notification == "yes"){
                                            $check_twitter_acount = $this->agm->check_twitter_acount($appr_account_id);
                                            if($check_twitter_acount){
                                                $token = $this->leave->get_token($val, $this->company_id, $leave_info->emp_id);
                                                $url = base_url()."approval/leave/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
                                                $message = "A leave application has been filed by {$fullname} and is now waiting for your approval. Click this link {$url} to approve.";
                                                $recipient_account = $check_twitter_acount->twitter;
                                                $this->tweetontwitter($this->emp_id,$message,$recipient_account);
                                            }
                                        }
                                        
                                        if($workforce_notification->facebook_notification == "yes"){
                                            // coming soon
                                        }
                                        
                                        if($workforce_notification->message_board_notification == "yes"){
                                            $token = $this->leave->get_token($val, $this->company_id, $leave_info->emp_id);
                                            $url = base_url()."approval/leave/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
                                            $next_appr_notif_message = "A leave application below has been filed by {$fullname} and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
                                            send_to_message_board($psa_id, $appr_id, $this->emp_id, $this->company_id, $next_appr_notif_message, "system","warning");
                                        }
                                    }
                                }
                            }
                            
                            $save_token = array(
                                "leave_id"               => $view_last_row_leave_application->employee_leaves_application_id,
                                "token"                  => $shuffled,
                                "comp_id"                => $this->company_id,
                                "emp_id"                 => $this->emp_id,
                                "approver_id"            => $approver_id,
                                "level"                  => $new_level,
                                "token_level"            => $shuffled2,
                                "date_approved_level"    => date('Y-m-d H:i:s'),
                                "date_reminder_level"    => date('Y-m-d H:i:s')
                            );
                            
                            $flag_if_insert_suceess = $this->db->insert("approval_leave",$save_token);

                            if(!$flag_if_insert_suceess) {
                                $this->db->insert("approval_leave",$save_token);
                            }
                            
                            $result = array(
                                'error'             => false,
                                'approver_error'    => "",
                                'err_msg'			=> ''
                            );
                            echo json_encode($result);
                            return false;
                        }else{
                            $new_level = 1;
                            
                            $save_token = array(
                                "leave_id"               => $view_last_row_leave_application->employee_leaves_application_id,
                                "token"                  => $shuffled,
                                "comp_id"                => $this->company_id,
                                "emp_id"                 => $this->emp_id,
                                "approver_id"            => $approver_id,
                                "level"                  => $new_level,
                                "token_level"            => $shuffled2,
                                "date_approved_level"    => date('Y-m-d H:i:s'),
                                "date_reminder_level"    => date('Y-m-d H:i:s')
                            );
                            
                            $save_token_q = $this->db->insert("approval_leave",$save_token);
                            
                            $result = array(
                                'error'             => false,
                                'approver_error'    => "",
                                'err_msg'			=> ''
                            );
                            
                            echo json_encode($result);
                            return false;
                        }
                    } else {
                        if($get_approval_settings_disable_status->status == "Inactive") {
                            $value1 = array(
                                "approve_by_hr" => "Yes",
                                "approve_by_head" => "Yes"
                            );
                            $w1 = array(
                                "leave_id" => $view_last_row_leave_application->employee_leaves_application_id,
                                "comp_id" => $this->company_id
                            );
                            $this->db->where($w1);
                            $this->db->update("approval_leave",$value1);
                            $this->leave->new_update_employee_leaves($this->company_id, $this->emp_id, $leave_type, floatval($view_last_row_leave_application->total_leave_requested), $val);
                            
                            $result = array(
                                'error'             => false,
                                'approver_error'    => "",
                                'err_msg'			=> ''
                            );
                            
                            echo json_encode($result);
                            return false;
                        }
                    }
                }else{
                    $result = array(
                     	'error'             => true,
                     	'approver_error'    => "",
                     	'err_msg'			=> 'Unable to notify your approver.'
                 	);
                     
                 	echo json_encode($result);
                 	return false;
                }
                // save token to approval leave
            } else {
            	$this->leave->new_update_employee_leaves($this->company_id, $this->emp_id, $leave_type, floatval($view_last_row_leave_application->total_leave_requested), $val);
                            
                $result = array(
                    'error'             => false,
                    'approver_error'    => "",
                    'err_msg'			=> ''
                );
                
                echo json_encode($result);
                return false;
            }
            
        }else{
            $shift_err  = "";
            $start_err  = "";
            $end_err    = "";
            #$return_err = "";
            $att_err    = form_error('upload_numbers');
            
            if(form_error('shift_date') !=""){
                $shift_err = 'The Date Field is Required';
            }
            
            if(form_error('start_date')!=""){
                if(form_error('start_date_hr') != "" || form_error('start_date_min') != "" || form_error('start_date_sec') != ""){
                    $start_err = 'The Start Date and Time Field is Required';
                }else{
                    $start_err = 'The Start Date Field is Required';
                }
            }
            
            if(form_error('end_date')!=""){
                
                if(form_error('end_date_hr') != "" || form_error('end_date_min') != "" || form_error('end_date_sec') != ""){
                    $end_err = 'The End Date and Time Field is Required';
                }else{
                    $end_err = 'The End Date Field is Required';
                }
            }
            
            $result = array(
                'error'         => true,
                'eshift_date'   => $shift_err,
                'eleave_type'   => form_error('leave_type'),
                'ereason'       => "This field is required.",
                'estart_date'   => $start_err,
                'eend_date'     => $end_err,
                'eatt'          => $att_err,
                'err_msg'       => "Please fill out the form correctly."
            );
            
            echo json_encode($result);
            return false;
        }

        $result = array(
            'error'             => true,
            'approver_error'    => "",
            'err_msg'			=> "App leave is unable to handle this request. Please use the web instead."
        );
        
        echo json_encode($result);
        return false;
    
    }

    function submit_leaves_test() {
    	$what_portal = "employee";
        $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id, "leave");
        if($get_lock_payroll_process_settings) {
            if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                $result = array(
                    'error'=>true,
                    'err_msg'=>$get_lock_payroll_process_settings->application_error,
                );
                echo json_encode($result);
                return false;
            } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                $result = array(
                    'error'=>true,
                    'err_msg'=>$get_lock_payroll_process_settings->ts_app_recalculation_err_msg,
                );
                echo json_encode($result);
                return false;
            } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                $result = array(
                    'error'=>true,
                    'err_msg'=>$get_lock_payroll_process_settings->py_app_recalculation_err_msg,
                );
                echo json_encode($result);
                return false;
            }
        }

        $stop = $this->input->get("quibs");
		
		$this->emp_id = "34386";
		$this->company_id = "28";
					
		$flag = $this->input->post('flag');
			
        $leave_type 		= "42594";
        $reason             = "qwertysz";
        $start_date         = date("Y-m-d",strtotime("2020-02-08"));
        $start_time         = date("h:i A",strtotime("08:00 AM"));
        
        $end_date           = date("Y-m-d",strtotime("2020-02-08"));
        $end_time           = date("h:i A",strtotime("05:00 PM"));
        
        $cont_tlr_hidden    = "1";
        $previous_credits   = "20";
        $schedule_blocks_id = $this->input->post('schedule_blocks_id');
        
        $no_lunch_hrs       = "0";
        $leave_request_type = "";
        
        $lunch_hr_required = "no";
        if ($no_lunch_hrs == "1") {
            $lunch_hr_required = "yes";
        }
        
        if($start_date) {
            $ws_id              = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$start_date);
            $check_workday      = $this->employee->check_workday_ws($this->emp_id,$this->company_id,$ws_id);
            $start_date_req     = date("l",strtotime($start_date));
            $check_regular      = $this->employee->check_regular_ws($this->emp_id,$this->company_id,$ws_id,$start_date_req);
            $check_workshift    = $this->employee->check_workshift_ws($this->emp_id,$this->company_id,$ws_id);
        	$get_open_shift_leave = $this->employee_v2->get_open_shift_leave($ws_id, $this->company_id);
        } else {
            $result = array(
                'error'=>true,
                'err_msg'=>"Start Date is required."
            );
            echo json_encode($result);
            return false;
        }
        
        if($end_date == "" || $end_date == "1970-01-01"){
            $result = array(
                'error'=>true,
                'err_msg'=>"End Date is required."
            );
            echo json_encode($result);
            return false;
        }

        $please_yaw_ka_zero = is_numeric($cont_tlr_hidden);
                    
        if ($please_yaw_ka_zero) {
            if ($cont_tlr_hidden <= 0) {
                $result = array(
                    'error'=>true,
                    'err_msg'=>"Your total leave requested is zero."
                );
                echo json_encode($result);
                return false;
            }
        } else {
            $result = array(
                'error'=>true,
                'err_msg'=>"Please wait for the form to complete processing your request and try again."
            );
            echo json_encode($result);
            return false;
        }

        // $point_something = explode(".", $cont_tlr_hidden);
        // $point_something_val = $point_something[0] + .50;

        // if($this->company_id == "206") {
        //     if($cont_tlr_hidden > $point_something_val) {
        //         $result = array(
        //             'error'=>true,
        //             'etlr'=>"Your leave setting allows partial leave of atleast .5 leave in a fraction."
        //         );
        //         echo json_encode($result);
        //         return false;
        //     }
        // }
        
        // $this->form_validation->set_rules("leave_type", 'Leave Type', 'trim|required|xss_clean');
        // $this->form_validation->set_rules("reason", 'Reason', 'trim|required|xss_clean');
        // $this->form_validation->set_rules("start_date", 'Start Date', 'trim|required|xss_clean');
        // $this->form_validation->set_rules("end_date", 'End Date', 'trim|required|xss_clean');
        
        // if(!$check_workday) {
        //     $this->form_validation->set_rules("start_time", 'Start Time', 'trim|required|xss_clean');
        //     $this->form_validation->set_rules("end_time", 'End Time', 'trim|required|xss_clean');
        // }
        
        // $this->form_validation->set_rules('upload_numbers','Attachments','callback_attachment_check');
        // $this->form_validation->set_rules("cont_tlr_hidden", 'Total Leave Requested', 'trim|required|xss_clean');
        // $this->form_validation->set_error_delimiters('', '');
        
        if (true){
            $concat_start_datetime  = date("H:i:s",strtotime($start_time));
            $concat_start_date      = $start_date." ".$concat_start_datetime;
            $concat_end_datetime    = date("H:i:s",strtotime($end_time));
            $concat_end_date        = $end_date." ".$concat_end_datetime;
            
            $get_current_shift      = $this->employee->get_current_shift($ws_id, $this->company_id, $start_date, $this->emp_id);

            $shift_date = date("Y-m-d",strtotime($start_date)); // input mani ang shift date nya g.pakuha man.. mao start ako g.default, dghan nmn nggamit sa $shift_date
            $check_work_type = $this->employee->work_schedule_type($ws_id, $this->company_id);

            if ($stop == "1") {
            	p("1");
            	exit();
            }
            
            if ($get_current_shift) { // gi reuse nlng ni nako na code ni fil..
                foreach ($get_current_shift as $zz) {
                    if($zz->work_type_name == "Flexible Hours") {
                        $ur_start = date("A",strtotime($zz->latest_time_in_allowed));
                        
                        $total_hours_for_the_day = $zz->total_hours_for_the_day * 60;
                        $latest_time_out_allowed = date("H:i:s",strtotime($zz->latest_time_in_allowed.' +'.$total_hours_for_the_day.' minutes'));
                        $boundary_end_to_new_start = $latest_time_out_allowed;
                        $the_new_start = date("H:i:s", strtotime($concat_start_date));
                        
                        $ur_end = date("A",strtotime($latest_time_out_allowed));
                        
                        $input_start_datetime = date("A",strtotime($concat_start_date));
                        $input_end_datetime = date("A",strtotime($concat_end_date));
                        
                        if($ur_start == "PM" && $ur_end == "AM") {
                            if(strtotime($boundary_end_to_new_start) < strtotime($the_new_start)) {
                                $shift_date = date("Y-m-d", strtotime($start_date));
                            } else {
                                if($input_start_datetime == "AM" && $input_end_datetime == "AM") {
                                    $shift_date = date("Y-m-d", strtotime($start_date." -1 day"));
                                } else {
                                    if(strtotime($boundary_end_to_new_start) >= strtotime($the_new_start)){
                                        $shift_date = $start_date;
                                    }
                                }
                            }
                        }
                        
                        $lunch_hr_required = "yes";
                    } else {
                        $ur_start = date("A",strtotime($zz->start));
                        $ur_end = date("A",strtotime($zz->end));
                        
                        $input_start_datetime = date("A",strtotime($concat_start_date));
                        $input_end_datetime = date("A",strtotime($concat_end_date));
                        
                        if($ur_start == "PM" && $ur_end == "AM") {
                            if($input_start_datetime == "AM" && $input_end_datetime == "AM") {
                                $shift_date = date("Y-m-d", strtotime($start_date." -1 day"));
                            }
                        }
                        
                        // no timesheet required trapping : can file within a shift only
                        $shift_start_date = $start_date." ".$zz->start;
                        $shift_end_date = $end_date." ".$zz->end;
                        
                        if (check_if_timein_is_required($this->emp_id,$this->company_id) == "no") {
                            if (strtotime($concat_start_date) < strtotime($shift_start_date)) {
                                $result = array(
                                    'error' => true,
                                    'eend_date' => 'You can only file a leave within your shift.'
                                );
                                echo json_encode($result);
                                return false;
                            } elseif (strtotime($shift_end_date) < strtotime($concat_end_date)) {
                                $result = array(
                                    'error' => true,
                                    'eend_date' => 'You can only file a leave within your shift.'
                                );
                                echo json_encode($result);
                                return false;
                            }
                        }
                    }
                }
            }

            if ($stop == "2") {
            	p("2");
            	exit();
            }
            
            $is_flexi = false;
            if($check_work_type == "Flexible Hours"){
                $is_flexi = true;
            }
            
            $check_existing_leave_applied = $this->employee->check_existing_leave_applied($this->emp_id, $this->company_id, $concat_start_date, $concat_end_date);
            if($check_existing_leave_applied) {
                if(!$is_flexi) {
                    if($check_existing_leave_applied->leave_application_status == 'approve') {
                        $leave_application_status = 'Approved';
                    } else {
                        $leave_application_status = $check_existing_leave_applied->leave_application_status;
                    }
                    
                    $leave_type = $this->employee->leave_type($this->company_id,$this->emp_id,$check_existing_leave_applied->leave_type_id);
                    
                    $leave_type_name = "";
                    if($leave_type) {
                        foreach ($leave_type as $hatch) {
                            $leave_type_name = $hatch->leave_type;
                        }
                    }
                    
                    $date_start_res = date('d-M-y h:i A', strtotime($check_existing_leave_applied->date_start));
                    $date_end_res = date('d-M-y h:i A', strtotime($check_existing_leave_applied->date_end));
                    /*if ($is_flexi) {
                     $date_start_res = date('d-M-y', strtotime($check_existing_leave_applied->date_start));
                     $date_end_res = date('d-M-y', strtotime($check_existing_leave_applied->date_end));
                     }*/
                    
                    $result = array(
                        'error' => true,
                        'existing_leave' => true,
                        'leave_type' => ucwords($leave_type_name),
                        'date_filed' => idates($check_existing_leave_applied->date_filed),
                        'date_start' => $date_start_res,
                        'date_end' => $date_end_res,
                        'leave_application_status' => $leave_application_status,
                        'err_msg' => 'You already have an existing leave filed for this date and time.'
                    );
                    echo json_encode($result);
                    return false;
                }
            }

            if ($stop == "3") {
            	p("3");
            	exit();
            }
            
            // check leave restriction
            $halfday_rest                       = $this->leave->get_leave_restriction($leave_type,'provide_half_day_option');
            $apply_limit_rest                   = $this->leave->get_leave_restriction($leave_type,'allow_to_apply_leaves_beyond_limit');
            $exclude_holidays                   = $this->leave->get_leave_restriction($leave_type,'exclude_holidays');
            $num_days_b4_leave                  = $this->leave->get_leave_restriction($leave_type,'num_days_before_leave_application');
            $days_b4_leave                      = $this->leave->get_leave_restriction($leave_type,'days_before_leave_application');
            $num_cons_days                      = $this->leave->get_leave_restriction($leave_type,'num_consecutive_days_allowed');
            $cons_days                          = $this->leave->get_leave_restriction($leave_type,'consecutive_days_allowed');
            $num_cons_days_week_hol             = $this->leave->get_leave_restriction($leave_type,'num_consecutive_days_after_weekend_holiday');
            $cons_days_week_hol                 = $this->leave->get_leave_restriction($leave_type,'consecutive_days_after_weekend_holiday');
            $required_documents                 = $this->leave->get_leave_restriction($leave_type,'required_documents');
            $exclude_rest_days                  = $this->leave->get_leave_restriction($leave_type,'exclude_rest_days');
            $paid_leave                         = $this->leave->get_leave_restriction($leave_type,'paid_leave');
            $effective_start_date_by            = $this->leave->get_leave_restriction($leave_type,'effective_start_date_by');
            $effective_start_date               = $this->leave->get_leave_restriction($leave_type,'effective_start_date');
            $leave_units                        = $this->leave->get_leave_restriction($leave_type,'leave_units');
            $what_happen_to_unused_leave        = $this->leave->get_leave_restriction($leave_type,'what_happen_to_unused_leave');
            $leave_conversion_run_every         = $this->leave->get_leave_restriction($leave_type,'leave_conversion_run_every');
            $carry_over_schedule_specific_month = $this->leave->get_leave_restriction($leave_type,'carry_over_schedule_specific_month');
            $carry_over_schedule_specific_day   = $this->leave->get_leave_restriction($leave_type,'carry_over_schedule_specific_day');
            $allow_negative_borrow_hours        = $this->leave->get_leave_restriction($leave_type,'allow_negative_borrow_hours');
            $allow_negative_borrow_unearned     = $this->leave->get_leave_restriction($leave_type,'allow_negative_borrow_unearned');
            $exclude_regular_holidays           = $this->leave->get_leave_restriction($leave_type,'exclude_regular_holidays');
            $exclude_special_holidays           = $this->leave->get_leave_restriction($leave_type,'exclude_special_holidays');
            
            $partial_days_type                  = $this->leave->get_leave_restriction($leave_type,'partial_days_type');
            $no_min_hours_allowed               = $this->leave->get_leave_restriction($leave_type,'no_min_hours_allowed');
            $no_duration_hours                  = $this->leave->get_leave_restriction($leave_type,'no_duration_hours');
            

            $eff_date                           = $this->leave->get_leave_eff_date($leave_type,$this->company_id,$this->emp_id,"effective_date");
            
            if ($stop == "4") {
            	p("4");
            	exit();
            }

            /* CHECK EMPLOYEE WORK SCHEDULE */
            if(strtotime($start_date) > strtotime($end_date)){
                $result = array(
                    'error'=>true,
                    'err_msg'=>"The end date you entered occurs before the start date."
                );
                echo json_encode($result);
                return false;
            }elseif(strtotime($start_date) == strtotime($end_date)){
                if(!$check_workday) {
                    if(strtotime($concat_start_datetime) > strtotime($concat_end_datetime)){
                        $result = array(
                            'error' => true,
                            'err_msg' => "The end time you entered occurs before the start time of your leave."
                        );
                        echo json_encode($result);
                        return false;
                    }
                }
            }
            
            // Check : Number of days before which the leave application should be submitted
            $date_now = date('Y-m-d');
            $exact_date_to_apply = date('Y-m-d', strtotime($num_days_b4_leave.' day', strtotime($date_now)));
            $start_date_to_apply = date('Y-m-d', strtotime($start_date));
            
            if($num_days_b4_leave != 0) {
                if($start_date_to_apply < $exact_date_to_apply) {
                    $result = array(
                            'error'=>true,
                            'err_msg'=>"You do not meet the required number of days for the leave application to be filed."
                    );
            
                    echo json_encode($result);
                    return false;
                }
            }
            
            //Check : Maximum number of consecutive days of leave allowed
            $lv_start = $concat_start_date;
            $lv_end = $concat_end_date;
            $total_lv = 0;
            $current_date       = $start_date;
            $per_day_credit     = $this->prm->average_working_hours_per_day($this->company_id);
            $work_schedule_id   = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$current_date);
            $work_start_time    = $this->employee->for_leave_hoursworked_work_start_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,$concat_start_date,$leave_request_type);
            $work_end_time      = $this->employee->for_leave_hoursworked_work_end_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,$concat_start_date);
            
            $chk_total_days = ((strtotime($lv_end) - strtotime($lv_start)) / 3600 / 24);

            if ($stop == "5") {
            	p("5");
            	exit();
            }
            
            if(round($chk_total_days) > 0){
                if($cons_days == 'yes') {
                    if($num_cons_days != null || $num_cons_days != 0){
                        if($num_cons_days < $cont_tlr_hidden) {
                            $result = array(
                                    'error'=>true,
                                    'err_msg'=>"You have exceeded the number of consecutive days of leaves allowed by your company."
                            );
                    
                            echo json_encode($result);
                            return false;
                        }
                    }
                }
            }
                        
            // check for leaves limits(2) per day
            $check_greater_than_2_leaves = $this->employee->check_greater_than_2_leaves($this->emp_id, $this->company_id, $shift_date);
            
            if($check_greater_than_2_leaves >= 2) {
                $result = array(
                        'error'=>true,
                        'err_msg'=> "You can only file up to two leaves per day."
                );
                    
                echo json_encode($result);
                return false;
            }

            if ($stop == "6") {
            	p("6");
            	exit();
            }
            
            // check for leaves within the calendar year only
            if($what_happen_to_unused_leave == "convert to cash" || $what_happen_to_unused_leave == "do nothing") {
                $get_employee_details_by_empid = get_employee_details_by_empid($this->emp_id);
                
                $conversion_sched = "";
                $this_year = date('Y');
                if($leave_conversion_run_every == "annual") {
                    $conversion_sched = $this_year.'-12-31';
                    $conversion_sched = date('Y-m-d', strtotime($conversion_sched));
                    
                    $temp_conversion_sched = date('m-d', strtotime($conversion_sched));
                    $concat_start_date_year = date('Y', strtotime($concat_start_date)).'-'.$temp_conversion_sched;
                    $new_conversion_sched = date('Y-m-d', strtotime($concat_start_date_year));
                } elseif ($leave_conversion_run_every == "anniversary") {
                    $date_hired = $get_employee_details_by_empid->date_hired;
                    $conversion_sched = $this_year.'-'.date('m-d', strtotime($date_hired));
                    $conversion_sched = date('Y-m-d', strtotime($conversion_sched));
                    
                    $temp_conversion_sched = date('m-d', strtotime($conversion_sched));
                    $concat_start_date_year = date('Y', strtotime($concat_end_date)).'-'.$temp_conversion_sched;
                    $new_conversion_sched = date('Y-m-d', strtotime($concat_start_date_year));
                } elseif ($leave_conversion_run_every == "specific date") {
                    $conversion_sched = $this_year.'-'.$carry_over_schedule_specific_month.'-'.$carry_over_schedule_specific_day;
                    $conversion_sched = date('Y-m-d', strtotime($conversion_sched));
                    
                    $temp_conversion_sched = date('m-d', strtotime($conversion_sched));
                    $concat_start_date_year = date('Y', strtotime($concat_end_date)).'-'.$temp_conversion_sched;
                    $new_conversion_sched = date('Y-m-d', strtotime($concat_start_date_year));
                }
                
                $concat_start_date_new = date('Y-m-d', strtotime($concat_start_date));
                $concat_end_date_new = date('Y-m-d', strtotime($concat_end_date));
                
                if(strtotime($concat_start_date_new) < strtotime($conversion_sched) && strtotime($concat_end_date_new) > strtotime($conversion_sched)) {
                    $msg_date = date('M d, Y', strtotime($conversion_sched));
                    $result = array(
                        'error'=>true,
                        'err_msg'=>"Your leave conversion is on {$msg_date}. You cannot apply leaves that span across this date. <br>Please apply separately."
                    );
                        
                    echo json_encode($result);
                    return false;
                } elseif (strtotime($concat_start_date_new) < strtotime($new_conversion_sched) && strtotime($concat_end_date_new) > strtotime($new_conversion_sched)) {
                    $msg_date = date('M d, Y', strtotime($new_conversion_sched));
                    $result = array(
                            'error'=>true,
                            'err_msg'=>"Your leave conversion is on {$msg_date}. You cannot apply leaves that span across this date. <br>Please apply separately."
                    );
                        
                    echo json_encode($result);
                    return false;
                }
            }

            if ($stop == "7") {
            	p("7");
            	exit();
            }
            
            $check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($start_date)));
            $tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($this->company_id,$work_schedule_id);
            $is_work = is_break_assumed($work_schedule_id);
            if($tardiness_rule_migrated_v3) {
                $is_work = false;
            }

            if ($stop == "8") {
            	p("8");
            	exit();
            }
            
            $void = $this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($shift_date)));
            
            if ($stop == "9") {
            	p("9");
            	exit();
            }

            if($void == "Waiting for approval"){
                $flag_payroll_correction = "yes";
                $disabled_btn = true;
            } elseif ($void == "Closed") {
                $flag_payroll_correction = "yes";
            } else {
                $flag_payroll_correction = "no";
            }
            
            // if one of the approver is inactive the approver group will automatically change to default (owner)
            change_approver_to_default($this->emp_id,$this->company_id,"leave_approval_grp",$this->account_id);

            if ($stop == "10") {
            	p("10");
            	exit();
            }
            
            $date1 = $concat_start_date;
            $date2 = $concat_end_date;
            
            $date_timein        = date("H:i:s",strtotime($date1));
            $date_timeout       = date("H:i:s",strtotime($date2));
            $check_hours        = strtotime(date("Y-m-d H:i:s",strtotime($date2))) - strtotime(date("Y-m-d H:i:s",strtotime($date1)));
            $total_leave_hours  = $check_hours / 3600;
            $total_hours        = $check_hours / 3600 / 24;
            
            $total_leave_request    = $cont_tlr_hidden;
            $duration               = number_format(round($total_leave_request,2),'2','.',',');
            
            // check restriction halfday is not allowed
            $work_schedule_id   = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($concat_start_date)));
            $req_hours_work     = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($concat_start_date)),$work_schedule_id) / 2;
            
            if ($stop == "11") {
            	p("11");
            	exit();
            }

            $err_mess   = 0;
            $limit_res  = '';
            $hd_res     = '';
            
            if($halfday_rest == 'yes'){
                if($partial_days_type == 'duration_hours') {
                    $point_something = explode(".", $cont_tlr_hidden);
                    #$point_something_val = $point_something[0] + $no_duration_hours;
                    $point_something_val = "0.".$point_something[1];
                    $no_duration_hours = number_format($no_duration_hours,3);
                    
                    // manual kaau :D change nlng if naa ky idea lain hahah na mas saun
                    $duration_pass = false;
                    if($no_duration_hours == 0.75) { // same rani ug scenario or outcome
                        if($point_something_val == 0.750){
                            $duration_pass = true;
                        } elseif ($cont_tlr_hidden >= 1 && $point_something_val == 0.500) {
                            $duration_pass = true;
                        } elseif ($cont_tlr_hidden >= 1 && $point_something_val == 0.250) {
                            $duration_pass = true;
                        } elseif ($cont_tlr_hidden >= 1 && $point_something_val == 0.000) {
                            $duration_pass = true;
                        }
                    } elseif ($no_duration_hours == 0.500) {
                        if ($point_something_val == 0.500) {
                            $duration_pass = true;
                        } elseif ($point_something_val == 0.000) {
                            $duration_pass = true;
                        }
                    } elseif ($no_duration_hours == 0.250) {
                        if($point_something_val == 0.750){
                            $duration_pass = true;
                        } elseif ($point_something_val == 0.500) {
                            $duration_pass = true;
                        } elseif ($point_something_val == 0.250) {
                            $duration_pass = true;
                        } elseif ($point_something_val == 0.000) {
                            $duration_pass = true;
                        }
                    }
                    
                    if(!$duration_pass) {
                        $result = array(
                            'error'=>true,
                            'etlr'=>"Your leave setting allows partial leave of atleast ".$no_duration_hours." leave in a fraction."
                        );
                        echo json_encode($result);
                        return false;
                    }
                } else {
                    if($leave_request_type == "Partial Day") {
                        $no_of_hrs_filed = $cont_tlr_hidden * 8;
                        if($no_min_hours_allowed > $no_of_hrs_filed) {
                            $result = array(
                                'error'=>true,
                                'etlr'=>"Your leave setting allows partial leave of atleast ".$no_min_hours_allowed." hours minimum."
                            );
                            echo json_encode($result);
                            return false;
                        }
                    }
                }
            } else {
                if($leave_request_type == "Partial Day") {
                    $hd_res     = "Partial day leave has been disabled by your company. You cannot apply for partial day leave.";
                    $err_mess   = $err_mess + 1;
                }
            }

            $get_all_pending_leave = $this->employee->get_all_pending_leave($this->company_id,$this->emp_id,$leave_type);

            if ($stop == "12") {
            	p("12");
            	exit();
            }
            
            if($paid_leave == 'yes') {
                if($apply_limit_rest == 'no'){
                    $check_leave_balance = $this->employee->leave_type($this->company_id,$this->emp_id,$leave_type);
                    
                    if($check_leave_balance){
                        foreach($check_leave_balance as $clb){
                            $remaining = $clb->remaining_leave_credits;
                            if($remaining == ''){
                                $remaining = $clb->leave_credits;
                            }
                        }
                        
                        $get_all_pending_leave = $this->employee->get_all_pending_leave($this->company_id,$this->emp_id,$leave_type);
                        $pending_credits = 0;
                        if ($get_all_pending_leave) {
                            foreach ($get_all_pending_leave as $gapl) {
                                $pending_credits += $gapl->total_leave_requested;
                            }
                        }
                        
                        $remaining = $remaining - $pending_credits;
                       # p($remaining);exit();
                        if($total_leave_request > $remaining){
                            $limit_res = "You cannot apply leaves beyond the allowed limit alloted by your company.";
                            $err_mess = $err_mess + 1;
                        }
                    }
                }
            }

            if ($stop == "13") {
            	p("13");
            	exit();
            }
            
            if($err_mess != 0){
                $err = array("error"=>true,"err_msg"=>$hd_res,"err_msg"=>$limit_res);
                echo json_encode($err);
            }
            
            if($effective_start_date_by != null && $effective_start_date != null) {
                if(date('Y-m-d', strtotime($concat_start_date)) >= $eff_date) {
                    $total_leave_request_save = $total_leave_request;
                    $leave_cedits_for_untitled = 0;
                } else {
                    $total_leave_request_save = 0;
                    $leave_cedits_for_untitled = $total_leave_request;
                }
            } else {
                $total_leave_request_save = $total_leave_request;
                $leave_cedits_for_untitled = 0;
            }
            
            $leave_credits = $this->employee->leave_type($this->company_id,$this->emp_id, $leave_type);
            if ($leave_credits) {
	            foreach ($leave_credits as $lc){
	                $leave_credits = ($lc->remaining_leave_credits != "") ? $lc->remaining_leave_credits : $lc->leave_credits;
	            }
	        }

            if ($stop == "14") {
            	p("14");
            	exit();
            }
            
            if ($allow_negative_borrow_unearned == "yes") {
                $leave_credits = $leave_credits + $allow_negative_borrow_hours;
                
                if ($total_leave_request > $leave_credits) {
                    $result = array(
                        'error'=>true,
                        'etlr'=>"Oh no! you can only borrow {$allow_negative_borrow_hours} {$leave_units} from leaves that you have yet to accrue/earn."
                    );
                    
                    echo json_encode($result);
                    return false;
                }
            }

            if ($please_yaw_ka_zero) {
                if ($cont_tlr_hidden <= 0) {
                    $result = array(
                        'error'=>true,
                        'etlr'=>"Your total leave requested is zero."
                    );
                    echo json_encode($result);
                    return false;
                }
            } else {
                $result = array(
                    'error'=>true,
                    'etlr'=>"Please wait for the form to complete processing your request and try again."
                );
                echo json_encode($result);
                return false;
            }
            
            
            $save_employee_leave = array(
                "shift_date"                 => $shift_date,
                "work_schedule_id"           => $work_schedule_id,
                "company_id"                 => $this->company_id,
                "emp_id"                     => $this->emp_id,
                "date_filed"                 => date("Y-m-d"),
                "leave_type_id"              => $leave_type,
                "reasons"                    => $reason,
                "date_start"                 => $concat_start_date,
                "date_end"                   => $concat_end_date,
                "total_leave_requested"      => $total_leave_request_save,
                "leave_cedits_for_untitled"  => $leave_cedits_for_untitled,
                "duration"                   => $duration,
                "note"                       => "",
                "leave_application_status"   => "pending",
                "attachments"                => "",
                "previous_credits"           => $previous_credits,
                "flag_payroll_correction"    => $flag_payroll_correction,
                "exclude_lunch_break"        => $lunch_hr_required,
                "leave_request_type"         => ($leave_request_type == "") ? null: $leave_request_type
            );
            
            $upload_attachment = $this->input->post('upload_attachment');
            if($upload_attachment){
                $save_employee_leave['required_file_documents'] = $upload_attachment ?  implode(";",$upload_attachment) : '';
            }

            $name = ucwords($this->employee->get_approver_name($this->emp_id,$this->company_id)->first_name);
            $email = $this->employee->get_approver_name($this->emp_id,$this->company_id)->email; 

            if ($stop == "15") {
            	p("15");
            	exit();
            }

            // save employee leave application
            $insert_employee_leave = $this->jmodel->insert_data('employee_leaves_application',$save_employee_leave);
            
            // view last row for leave application
            $view_last_row_leave_application = $this->employee->last_row_leave_app($this->emp_id,$this->company_id,$leave_type);
            $check_total_days = ((strtotime($date2) - strtotime($date1)) / 3600 / 24);
            $check_is_date_holidayv2 = $this->employee_v2->check_is_date_holidayv2($this->company_id);

            if ($stop == "16") {
            	p("16");
            	exit();
            }
                        
            if($check_workday != FALSE){ // calculate credited and non credited for flexi only
                $credited = 0;
                $non_credited = 0;
                
             	if(strtotime($start_date) == strtotime($end_date)) { // for one day
                 	if($effective_start_date_by != null && $effective_start_date != null) {
                 		if(date('Y-m-d', strtotime($start_date)) >= $eff_date) {
                         	if($cont_tlr_hidden > $leave_credits){
                             	$credited = $leave_credits;
                             	$non_credited = $cont_tlr_hidden - $leave_credits;
                         	}elseif($cont_tlr_hidden <= $leave_credits){
                             	$credited = $cont_tlr_hidden;
                         	}
                     	} else {
                         	$credited = 0;
                         	$non_credited = 0;
                     	}
                 	} else {
                     	if($cont_tlr_hidden > $leave_credits){
                         	$credited = $leave_credits;
                         	$non_credited = $cont_tlr_hidden - $leave_credits;
                     	}elseif($cont_tlr_hidden <= $leave_credits){
                         	$credited = $cont_tlr_hidden;
                     	}
                 	}

                 	if($credited < 0) {
                        $credited = 0;
                    }
                    
                    $no_of_hours = 8; // if credits, not days ang unit.
                    if($leave_units == "days"){
                        $no_of_hours = $credited * $fl_hours_worked;
                    }
                     
                     $update_data2 = array(
                         'flag_parent' => 'no',
                         "credited" => $credited,
                         "non_credited" => $non_credited,
                         "no_of_hours" => $no_of_hours
                     );
                     
                     $this->db->where('employee_leaves_application_id',$view_last_row_leave_application->employee_leaves_application_id);
                     $this->db->update("employee_leaves_application",$update_data2);
             	} else { // for multiple day
                     
                     // less than 24 hours
	                $fl_time = $check_workday->latest_time_in_allowed;
	                $duration_of_lunch_break_per_day = $check_workday->duration_of_lunch_break_per_day / 60;
	                $fl_hours_worked = $check_workday->total_hours_for_the_day - $duration_of_lunch_break_per_day;
                 
                                     
					$days_filed = dateRange($start_date, $end_date);
					$days_filed_cnt = count($days_filed);
					$days_cnt = 0;
					$used_credits_total = 0;
					if($days_filed) {
                        foreach ($days_filed as $date) {
                         	$days_cnt++;
                            $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($date)));
                             
                         	$date_m_d = date("m-d", strtotime($date));
                                    
                            $is_holiday_q = in_array_custom("date-{$date_m_d}",$check_is_date_holidayv2);
                            $is_holiday = false;
                            $is_hol_temp = false;
                            
                            // exclude holiday
                            if($is_holiday_q){
                                if($is_holiday_q->date_type == "fixed") {                                            
                                    $app_m_d = date("m-d",strtotime($date));
                                    $hol_m_d = date("m-d",strtotime($is_holiday_q->date));
                                    
                                    if($app_m_d == $hol_m_d){
                                        $is_hol_temp = true;
                                    } else {
                                        $is_hol_temp = false;
                                    }
                                } else {
                                    $is_hol_temp = true;
                                }
                                
                                if($is_hol_temp) {
                                    $proceed1 = false;
                                    
                                    if($this->my_location != 0 || $this->my_location != null) {
                                        if($is_holiday_q->locations != "" || $is_holiday_q->locations != null) {
                                            $x = explode(",", $is_holiday_q->locations);
                                            foreach ($x as $loc) {
                                                if($loc == $this->my_location) {
                                                    $proceed1 = true;
                                                }
                                            }
                                        }
                                    }
                                    
                                    if($is_holiday_q->scope == "local" && !$proceed1) {
                                        $is_hol = FALSE;
                                    } else {
                                        $is_hol = TRUE;
                                    }
                                    
                                }
                            } else {
                                $is_hol = false;
                            }
                            
                            if($is_hol) {
                                $is_hol = holiday_leave_approval_settings($this->company_id);
                            }
                            
                            if($is_hol) {
                                if($is_holiday_q) {
                                    if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "yes"){
                                        $is_holiday = true;
                                    } else {
                                        if($is_holiday_q->hour_type_name == "Special Holiday") {
                                            // exclude Special holiday only
                                            if($exclude_holidays == 'yes' && $exclude_regular_holidays == "no" && $exclude_special_holidays == "yes"){
                                                $is_holiday = true;
                                            }
                                        } elseif($is_holiday_q->hour_type_name == "Regular Holiday") {
                                            // exclude Regular holiday only
                                            if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "no"){
                                                $is_holiday = true;
                                            }
                                        }
                                    }
                                }
                            }
                             
                             if(!$is_holiday && !$rest_day){
                                 $check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($date)),$work_schedule_id);
                                 
                                 if($days_filed_cnt == $days_cnt) { // for last loop
                                     if($leave_credits > 0) {
                                         $leave_credits = $leave_credits;
                                     } else {
                                         $used_credits = $cont_tlr_hidden - $used_credits_total;
                                         $leave_credits = 0;
                                     }
                                     
                                     if($effective_start_date_by != null && $effective_start_date != null) {
                                         if(date('Y-m-d', strtotime($start_date)) >= $eff_date) {
                                             if($used_credits > $leave_credits){
                                                 $credited = $leave_credits;
                                                 $non_credited = $used_credits - $leave_credits;
                                             }elseif($used_credits <= $leave_credits){
                                                 $credited = $used_credits;
                                                 $non_credited = 0;
                                             }
                                         } else {
                                             $credited = 0;
                                             $non_credited = 0;
                                         }
                                     } else {
                                         if($used_credits > $leave_credits){
                                             $credited = $leave_credits;
                                             $non_credited = $used_credits - $leave_credits;
                                         }elseif($used_credits <= $leave_credits){
                                             $credited = $used_credits;
                                         }
                                     }
                                     
                                 } else {
                                     if($leave_units == "days"){
                                         $used_credits = $check_for_working_hours / $check_for_working_hours;
                                     }else{
                                         $used_credits = $check_for_working_hours / $per_day_credit;
                                     }
                                     
                                     if($effective_start_date_by != null && $effective_start_date != null) {
                                         if(date('Y-m-d', strtotime($start_date)) >= $eff_date) {
                                             if($used_credits > $leave_credits){
                                                 $credited = $leave_credits;
                                                 $non_credited = $used_credits - $leave_credits;
                                             }elseif($used_credits <= $leave_credits){
                                                 $credited = $used_credits;
                                             }
                                         } else {
                                             $credited = 0;
                                             $non_credited = 0;
                                         }
                                     } else {
                                         if($used_credits > $leave_credits){
                                             $credited = $leave_credits;
                                             $non_credited = $used_credits - $leave_credits;
                                         }elseif($used_credits <= $leave_credits){
                                             $credited = $used_credits;
                                         }
                                     }
                                     
                                     $leave_credits = $leave_credits - $credited;
                                     $used_credits_total = $used_credits_total + $used_credits;                                     
                                 }

                             	if($credited < 0) {
                                    $credited = 0;
                                }

                                $no_of_hours = 8; // if credits, not days ang unit.
                                if($leave_units == "days"){
                                    $no_of_hours = $credited * $fl_hours_worked;
                                }
                                 
                                 $each_leave = array(
                                     "shift_date"                 => date("Y-m-d",strtotime($date)),
                                     "work_schedule_id"           => $work_schedule_id,
                                     "company_id"                 => $this->company_id,
                                     "emp_id"                     => $this->emp_id,
                                     "leave_type_id"              => $leave_type,
                                     "reasons"                    => $reason,
                                     "date_start"                 => date("Y-m-d",strtotime($date)),
                                     "date_end"                   => date("Y-m-d",strtotime($date)),
                                     "leave_application_status"   => "pending",
                                     "credited"                   => $credited,
                                     "non_credited"               => $non_credited,
                                     "leaves_id"                  => $view_last_row_leave_application->employee_leaves_application_id,
                                     "previous_credits"           => $previous_credits,
                                     "flag_payroll_correction"    => $flag_payroll_correction,
                                     "no_of_hours"                => $no_of_hours
                                 );
                                 
                                 $this->db->insert("employee_leaves_application", $each_leave);
                             }
                         }
                         
                         $update_data = array('flag_parent'=>'yes');
                         $this->db->where('employee_leaves_application_id',$view_last_row_leave_application->employee_leaves_application_id);
                         $this->db->update("employee_leaves_application",$update_data);
                     }
                 }
            } else { // calculate credited and non credited for uniform working sched and split only
                #p($check_total_days);
                if($check_total_days > 1){
                    #$check_total_days = round($check_total_days - 1);
                    $check_total_days = round($check_total_days);
                    $tl2 = 0;
                    $credited = 0;
                    $non_credited = 0;
                    $single_date_applied = true;
                    $temp_total_used_leave1 = 0;
                    $last_date = date("Y-m-d",strtotime($date2));  
                    $last_date_not_rd = date("Y-m-d",strtotime($date2));  
                    
                    for($cnt=1;$cnt<=$check_total_days;$cnt++){ // for range date : calculate the credits from first - 2nd to the last, exclude the last date
                        $temp_date = date("Y-m-d",strtotime($shift_date." +".$cnt." day"));
                        $work_sched_id = check_employee_work_schedule(date("Y-m-d",strtotime($temp_date." -1 day")), $this->emp_id, $this->company_id)->work_schedule_id;
                        $rest_day = $this->ews->get_rest_day($this->company_id,$work_sched_id,date("l",strtotime($temp_date." -1 day")));
                        
                        $starttime_ampm = date("A", strtotime($date1));
                        $endtime_ampm = date("A", strtotime($date2));
                        
                        // exclude holiday
                        $for_holiday_date = date("Y-m-d",strtotime($temp_date." -1 day"));
                        $for_holiday_date_m_d = date("m-d", strtotime($for_holiday_date));
                        $is_holiday_q = in_array_custom("date-{$for_holiday_date_m_d}",$check_is_date_holidayv2);
                        $is_holiday_tl2 = false;
                        $is_hol_temp = false;
                        
                        // exclude holiday
                        if($is_holiday_q){
                            if($is_holiday_q->date_type == "fixed") {
                                $app_m_d = date("m-d",strtotime($for_holiday_date));
                                $hol_m_d = date("m-d",strtotime($is_holiday_q->date));
                                
                                if($app_m_d == $hol_m_d){
                                    $is_hol_temp = true;
                                } else {
                                    $is_hol_temp = false;
                                }
                            } else {
                                $is_hol_temp = true;
                            }
                            
                            if($is_hol_temp) {
                                $proceed1 = false;
                                
                                if($this->my_location != 0 || $this->my_location != null) {
                                    if($is_holiday_q->locations != "" || $is_holiday_q->locations != null) {
                                        $x = explode(",", $is_holiday_q->locations);
                                        foreach ($x as $loc) {
                                            if($loc == $this->my_location) {
                                                $proceed1 = true;
                                            }
                                        }
                                    }
                                }
                                
                                if($is_holiday_q->scope == "local" && !$proceed1) {
                                    $is_hol = FALSE;
                                } else {
                                    $is_hol = TRUE;
                                }
                                
                            }
                        } else {
                            $is_hol = false;
                        }
                        
                        if($is_hol) {
                            $is_hol = holiday_leave_approval_settings($this->company_id);
                        }
                        
                        if($is_hol) {
                            if($is_holiday_q) {
                                if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "yes"){
                                    $is_holiday_tl2 = true;
                                } else {
                                    if($is_holiday_q->hour_type_name == "Special Holiday") {
                                        // exclude Special holiday only
                                        if($exclude_holidays == 'yes' && $exclude_regular_holidays == "no" && $exclude_special_holidays == "yes"){
                                            $is_holiday_tl2 = true;
                                        }
                                    } elseif($is_holiday_q->hour_type_name == "Regular Holiday") {
                                        // exclude Regular holiday only
                                        if($exclude_holidays == 'yes' && $exclude_regular_holidays == "yes" && $exclude_special_holidays == "no"){
                                            $is_holiday_tl2 = true;
                                        }
                                    }
                                }
                            }
                        }
                        
                        $date_start = $work_start_time;
                        $date_end = $work_end_time;
                        
                        if(!$is_holiday_tl2 && !$rest_day){
                            $check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($temp_date." -1 day")),$work_schedule_id);
                            $tl2 += $check_for_working_hours;
                                
                            if($cnt == 1) { // calculate the first date
                                $date_start = $start_time;
                                if(strtotime($shift_date) < strtotime($start_date)) { // if applied on 2nd half
                                    $start_br_down_date = $shift_date.' '.date("H:i:s", strtotime($work_start_time));
                                    $end_br_down_date = $start_date." ".$work_end_time;
                                    
                                    $new_concat_start_date = $start_date.' '.date("H:i:s", strtotime($concat_start_date));
                                    if(strtotime($start_br_down_date) < strtotime($new_concat_start_date)) {
                                        $start_br_down_date = $start_date.' '.date("H:i:s", strtotime($concat_start_date));
                                    }
                                    
                                    $new_concat_end_date = $start_date.' '.date("H:i:s", strtotime($concat_end_date));
                                    if(strtotime($end_br_down_date) > strtotime($new_concat_end_date)) {
                                        $end_br_down_date = $start_date.' '.date("H:i:s", strtotime($concat_end_date));
                                    }
                                    
                                    $check_start_time   = $this->employee_v2->get_leave_breaktime($this->emp_id,$this->company_id,date("l",strtotime($shift_date)),$work_schedule_id, $start_br_down_date, $end_br_down_date);
                                    $check_end_time     = $this->employee_v2->get_leave_breaktime($this->emp_id,$this->company_id,date("l",strtotime($shift_date)),$work_schedule_id, $start_br_down_date, $end_br_down_date,false);
                                    if($leave_units == 'days') {
                                        #$used_credits = ($this->employee_v2->get_tot_hours_ws_v2($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$check_for_working_hours,$work_schedule_id,0,false, $shift_date)) / $check_for_working_hours;
                                        $used_credits = ($this->employee_v2->get_tot_hours_ws_v3($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$hours_worked,$work_schedule_id,$shift_date,$lunch_hr_required)) / $hours_worked;
                                    } else {
                                        #$used_credits = ($this->employee_v2->get_tot_hours_ws_v2($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$check_for_working_hours,$work_schedule_id,0,false, $shift_date)) / $per_day_credit;
                                        $used_credits = ($this->employee_v2->get_tot_hours_ws_v3($this->emp_id,$this->company_id,$start_br_down_date,$check_start_time,$check_end_time,$end_br_down_date,$hours_worked,$work_schedule_id,$shift_date,$lunch_hr_required)) / $per_day_credit;
                                    }
                                    
                                } else {
                                    if($leave_units == "days"){
                                        $used_credits = $check_for_working_hours / $check_for_working_hours;
                                    }else{
                                        $used_credits = $check_for_working_hours / $per_day_credit;
                                    }
                                }
                            } else {
                                if($leave_units == "days"){
                                    $used_credits = $check_for_working_hours / $check_for_working_hours;
                                }else{
                                    $used_credits = $check_for_working_hours / $per_day_credit;
                                }
                            }
                            
                            
                            if($effective_start_date_by != null && $effective_start_date != null) {
                                if(date('Y-m-d', strtotime($concat_start_date)) >= $eff_date) {
                                    if($used_credits > $leave_credits){
                                        $credited = $leave_credits;
                                        $non_credited = $used_credits - $leave_credits;
                                    }elseif($used_credits <= $leave_credits){
                                        $credited = $used_credits;
                                    }
                                } else {
                                    $credited = 0;
                                    $non_credited = 0;
                                }
                            } else {
                                if($used_credits > $leave_credits){
                                    $credited = $leave_credits;
                                    $non_credited = $used_credits - $leave_credits;
                                }elseif($used_credits <= $leave_credits){
                                    $credited = $used_credits;
                                }
                            }
                            
                            if($credited < 0) {
                                $credited = 0;
                            }
                            
                            $no_of_hours = $credited * 8; // if credits, not days ang unit.
                            if($leave_units == "days"){
                                $no_of_hours = $credited * $check_for_working_hours;
                            }
                            
                            $date_start_save = date("Y-m-d",strtotime($temp_date." -1 day")).' '.date("H:i:s", strtotime($date_start));
                            $date_end_save = date("Y-m-d",strtotime($temp_date." -1 day")).' '.date("H:i:s", strtotime($date_end));
                            
                            if ($starttime_ampm == "PM" && $endtime_ampm == "AM") {
                                $date_start_save = date("Y-m-d",strtotime($temp_date." -1 day")).' '.date("H:i:s", strtotime($date_start));
                                $date_end_save = date("Y-m-d",strtotime($temp_date)).' '.date("H:i:s", strtotime($date_end));
                            }
                            
                            $each_leave = array(
                                "shift_date"                 => date("Y-m-d",strtotime($temp_date." -1 day")),
                                "work_schedule_id"           => $work_schedule_id,
                                "company_id"                 => $this->company_id,
                                "emp_id"                     => $this->emp_id,
                                "leave_type_id"              => $leave_type,
                                "reasons"                    => $reason,
                                "date_start"                 => $date_start_save,
                                "date_end"                   => $date_end_save,
                                "leave_application_status"   => $leave_app_status,
                                "credited"                   => $credited,
                                "non_credited"               => $non_credited,
                                "leaves_id"                  => $view_last_row_leave_application->employee_leaves_application_id,
                                "previous_credits"           => $previous_credits,
                                "flag_payroll_correction"    => $flag_payroll_correction,
                                "no_of_hours"                => $no_of_hours
                                
                            );
                            
                            $this->db->insert("employee_leaves_application", $each_leave);
                            $leave_credits = $leave_credits - $used_credits;
                            $temp_total_used_leave = $leave_credits - $used_credits;
                            $temp_total_used_leave1 += $credited;
                            $single_date_applied = false;
                            
                            $last_date_not_rd = $temp_date;
                        }
                    }
                    
                    if ($single_date_applied) {
                        $date_start = $start_time;
                        $date_end = $end_time;
                    } else {
                        if (strtotime($work_end_time) > strtotime($end_time)) {
                            $date_end = $end_time;
                        }
                        
                        if (strtotime($work_start_time) < strtotime($start_time)) {
                            $date_start = $work_start_time;
                        } else {
                            $date_start = $start_time;
                        }
                    }
                    
                    // this is for end date ky sometimes ang end date ky lahi ug schedule
                    $fl_hours_worked = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($concat_end_date)),$work_schedule_id);
                    if($check_workshift != FALSE){
                        $tl1 = $last_block_hours;
                        
                        if(date("A",strtotime($work_start_time)) == 'PM' && date("A",strtotime($work_end_time)) == 'AM') {
                            if (date("A",strtotime($date1)) == 'AM' && date("A",strtotime($date2)) == 'AM') {
                                $last_date = date("Y-m-d",strtotime($date2." -1 day"));
                            }
                        }
                        
                        $shift_date_last_save = date("Y-m-d",strtotime($last_date));
                        $date_start_last_save = $last_date.' '.date("H:i:s", strtotime($date_start));
                        $date_end_last_save = $last_date.' '.date("H:i:s", strtotime($date_end));
                        
                        if($leave_units == "days"){
                            $used_credits = $tl1 / $fl_hours_worked;
                        }else{
                            $used_credits = $tl1 / $per_day_credit;
                        }
                    } else {
                        $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($start_date)));
                        $work_schedule_idx  = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$end_date);
                        $rest_dayx = $this->ews->get_rest_day($this->company_id,$work_schedule_idx,date("l",strtotime($end_date)));
                              
                        if ($rest_day) {
                            $last_date = date("Y-m-d",strtotime($end_date));
                        } elseif ($rest_dayx) {
                            $last_date_not_rd_1 = date("Y-m-d", strtotime($last_date_not_rd.' +1 day'));
                            $work_schedule_idy  = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$last_date_not_rd_1);
                            $rest_dayy = $this->ews->get_rest_day($this->company_id,$work_schedule_idy,date("l",strtotime($last_date_not_rd_1)));
                            
                            if ($rest_dayy) {
                                #$last_date = $last_date_not_rd;
                            } else {
                                $last_date = date("Y-m-d", strtotime($last_date_not_rd.' +1 day'));
                            }
                        }
                        
                        
                        $shift_date_last_save = date("Y-m-d",strtotime($last_date));
                        $date_start_last_save = $last_date.' '.date("H:i:s", strtotime($date_start));
                        $date_end_last_save = $last_date.' '.date("H:i:s", strtotime($date_end));
                        
                        if(date("A",strtotime($work_start_time)) == 'PM' && date("A",strtotime($work_end_time)) == 'AM') {
                            $shift_date_last_save = date("Y-m-d",strtotime($last_date.' -1 day'));
                            $date_start_last_save = date("Y-m-d", strtotime($last_date.' -1 day')).' '.date("H:i:s", strtotime($date_start));
                            $date_end_last_save = $last_date.' '.date("H:i:s", strtotime($date_end));
                        }
                        
                        if($single_date_applied) { // calculate the solo filed leave
                            $used_credits = $cont_tlr_hidden;
                        } else { // calculate the last date of the if range date applied        
                            $used_credits = $cont_tlr_hidden - $temp_total_used_leave1;
                        }
                    }
                    
                    if($effective_start_date_by != null && $effective_start_date != null) {
                        if(date('Y-m-d', strtotime($concat_start_date)) >= $eff_date) {
                            if($used_credits > $leave_credits){
                                $credited = $leave_credits;
                                $non_credited = $used_credits - $leave_credits;
                            }elseif($used_credits <= $leave_credits){
                                $credited =  $used_credits;
                            }
                        } else { 
                            $credited = 0;
                            $non_credited = 0;
                        }
                    } else {
                        if($used_credits > $leave_credits){
                            $credited = $leave_credits;
                            $non_credited = $used_credits - $leave_credits;
                        }elseif($used_credits <= $leave_credits){
                            $credited =  $used_credits;
                        }
                    }
                    
                    if($credited < 0) {
                        $credited = 0;
                    }

                    $no_of_hours = $credited * 8; // if credits, not days ang unit.
                    if($leave_units == "days"){
                        $check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($shift_date_last_save)),$work_schedule_id);
                        $no_of_hours = $credited * $check_for_working_hours;
                    }
                    
                    $each_leave = array(
                        "shift_date"               => $shift_date_last_save,
                        "work_schedule_id"         => $work_schedule_id,
                        "company_id"               => $this->company_id,
                        "emp_id"                   => $this->emp_id,
                        "leave_type_id"            => $leave_type,
                        "reasons"                  => $reason,
                        "date_start"               => $date_start_last_save,
                        "date_end"                 => $date_end_last_save,
                        "leave_application_status" => "pending",
                        "credited"                 => $credited,
                        "non_credited"             => $non_credited,
                        "leaves_id"                => $view_last_row_leave_application->employee_leaves_application_id,
                        "previous_credits"         => $previous_credits,
                        "flag_payroll_correction"  => $flag_payroll_correction,
                        "no_of_hours"              => $no_of_hours
                        
                    );
                    #p($each_leave);
                    $this->db->insert("employee_leaves_application", $each_leave);
                    $update_data = array('flag_parent'=>'yes');
                    $this->db->where('employee_leaves_application_id',$view_last_row_leave_application->employee_leaves_application_id);
                    $this->db->update("employee_leaves_application",$update_data);
                } else {
                    
                    $check_total_days = round($check_total_days);
                    $tl2 = 0;
                    $credited = 0;
                    $non_credited = 0;
                    $single_date_applied = true;
                    
                    // this is for end date ky sometimes ang end date ky lahi ug schedule
                    $fl_hours_worked = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($concat_end_date)),$work_schedule_id);
                    if($check_workshift != FALSE){
                        $tl1 = $last_block_hours;
                        $last_date = date("Y-m-d",strtotime($date2));
                        
                        if(date("A",strtotime($work_start_time)) == 'PM' && date("A",strtotime($work_end_time)) == 'AM') {
                            if (date("A",strtotime($date1)) == 'AM' && date("A",strtotime($date2)) == 'AM') {
                                $last_date = date("Y-m-d",strtotime($date2." -1 day"));
                            }
                        }

                        if($leave_units == "days"){
                            $used_credits = $tl1 / $fl_hours_worked;
                        }else{
                            $used_credits = $tl1 / $per_day_credit;
                        }
                    } else {
                        $rest_day = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($start_date)));
                        $work_schedule_idx  = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$end_date);
                        $rest_dayx = $this->ews->get_rest_day($this->company_id,$work_schedule_idx,date("l",strtotime($end_date)));
                        $last_date = date("Y-m-d",strtotime($date2));
                        
                        if ($rest_day) {
                            $work_schedule_id   = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date("Y-m-d",strtotime($current_date.' -1 day')));
                            $rest_dayx = $this->ews->get_rest_day($this->company_id,$work_schedule_id,date("l",strtotime($start_date.' -1 day')));
                            
                            if (!$rest_dayx) {
                                $work_start_time    = $this->employee->for_leave_hoursworked_work_start_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,date("Y-m-d",strtotime($concat_start_date.' -1 day')));
                                $work_end_time      = $this->employee->for_leave_hoursworked_work_end_time_ws_v2($this->emp_id,$this->company_id,$work_schedule_id,date("Y-m-d",strtotime($concat_start_date.' -1 day')));
                            }
                        }
                        
                        if ($rest_day) {
                            $last_date = date("Y-m-d",strtotime($end_date));
                        } elseif ($rest_dayx) {
                            $last_date = date("Y-m-d",strtotime($start_date));
                        }
                        
                        if(date("A",strtotime($work_start_time)) == 'PM' && date("A",strtotime($work_end_time)) == 'AM') {
                            if (date("A",strtotime($date1)) == 'AM' && date("A",strtotime($date2)) == 'AM') {
                                $last_date = date("Y-m-d",strtotime($date2." -1 day"));
                            } else {
                                $last_date = date("Y-m-d",strtotime($date1));
                            }
                        }
                        
                        $used_credits = $cont_tlr_hidden;
                    }
                    
                    if($effective_start_date_by != null && $effective_start_date != null) {
                        if(date('Y-m-d', strtotime($concat_start_date)) >= $eff_date) {
                            if($used_credits > $leave_credits){
                                $credited = $leave_credits;
                                $non_credited = $used_credits - $leave_credits;
                            }elseif($used_credits <= $leave_credits){
                                $credited =  $used_credits;
                            }
                        } else {
                            $credited = 0;
                            $non_credited = 0;
                        }
                    } else {
                        if($used_credits > $leave_credits){
                            $credited = $leave_credits;
                            $non_credited = $used_credits - $leave_credits;
                        }elseif($used_credits <= $leave_credits){
                            $credited =  $used_credits;
                        }
                    }
                    
                    if($credited < 0) {
                        $credited = 0;
                    }
                    
                    if ($single_date_applied) {
                        $date_start = $start_time;
                        $date_end = $end_time;
                    } else {
                        if (strtotime($work_end_time) > strtotime($end_time)) {
                            $date_end = $end_time;
                        }
                        
                        if (strtotime($work_start_time) < strtotime($start_time)) {
                            $date_start = $work_start_time;
                        } else {
                            $date_start = $start_time;
                        }
                    }

                    $no_of_hours = $credited * 8; // if credits, not days ang unit.
                    if($leave_units == "days"){
                        $check_for_working_hours = $this->employee->for_leave_hoursworked_ws($this->emp_id,$this->company_id,date("l",strtotime($last_date)),$work_schedule_id);
                        $no_of_hours = $credited * $check_for_working_hours;
                    }
                   
                    $update_data2 = array(
                        "shift_date" => date("Y-m-d",strtotime($last_date)),
                        'flag_parent' => 'no',
                        "credited" => $credited,
                        "non_credited" => $non_credited,
                        
                    );
                    
                    $this->db->where('employee_leaves_application_id',$view_last_row_leave_application->employee_leaves_application_id);
                    $this->db->update("employee_leaves_application",$update_data2);
                }
            }

            if ($stop == "17") {
            	p("17");
            	exit();
            }
            
            // send email notification to approver
            $leave_info     = $this->agm->leave_information($view_last_row_leave_application->employee_leaves_application_id);
            $void_v2 = $this->employee_v2->check_payroll_lock_closed($leave_info->emp_id,$leave_info->company_id,date("Y-m-d", strtotime($leave_info->shift_date)));
                    
            if ($stop == "18") {
            	p("18");
            	exit();
            }

            // if($void_v2 == "Closed" && $leave_info) {
            // $void_v2 returns false of "Closes" or "Waiting for approval"
            if($void_v2 == "Closed" && $leave_info) {
            // if($void_v2 && $leave_info) {
                $date_insert = array(
                    "employee_leaves_application_id" => $leave_info->employee_leaves_application_id,
                    "work_schedule_id" => $leave_info->work_schedule_id,
                    "emp_id" => $leave_info->emp_id,
                    "company_id" => $leave_info->company_id,
                    "date_filed" => $leave_info->date_filed,
                    "leave_type_id" => $leave_info->leave_type_id,
                    "reasons" => $leave_info->reasons,
                    "shift_date" => $leave_info->shift_date,
                    "date_start" => $leave_info->date_start,
                    "date_end" => $leave_info->date_end,
                    "date_filed" => $leave_info->date_filed,
                    "note" => $leave_info->note,
                    "total_leave_requested" => $leave_info->total_leave_requested,
                    "leave_application_status" => $leave_app_status,
                    "leaves_id" => $leave_info->leaves_id,
                    "flag_parent" => $leave_info->flag_parent,
                    "credited" => $leave_info->credited,
                    "required_file_documents" => $leave_info->required_file_documents,
                    "status" => $leave_info->status,
                    "approver_account_id" => $leave_info->approver_account_id,
                    "previous_credits" => $leave_info->previous_credits,
                    "exclude_lunch_break" => $leave_info->exclude_lunch_break,
                    "leave_request_type" => $leave_info->leave_request_type,
                    "no_of_hours" => $leave_info->credited * 8
                );
                
                $this->db->insert('leaves_close_payroll', $date_insert);
                $id = $this->db->insert_id();
                if($leave_info->flag_parent == "yes") {
                    $get_leave_apps_child = $this->todo_leave->get_leave_apps_child($leave_info->emp_id,$leave_info->company_id,$leave_info->employee_leaves_application_id);
                    
                    if($get_leave_apps_child) {
                        foreach ($get_leave_apps_child as $glac) {
                            $date_insert1 = array(
                                "employee_leaves_application_id" => $glac->employee_leaves_application_id,
                                "work_schedule_id" => $glac->work_schedule_id,
                                "emp_id" => $glac->emp_id,
                                "company_id" => $glac->company_id,
                                "date_filed" => $glac->date_filed,
                                "leave_type_id" => $glac->leave_type_id,
                                "reasons" => $glac->reasons,
                                "shift_date" => $glac->shift_date,
                                "date_start" => $glac->date_start,
                                "date_end" => $glac->date_end,
                                "date_filed" => $glac->date_filed,
                                "note" => $leave_info->note,
                                "total_leave_requested" => $glac->total_leave_requested,
                                "leave_application_status" => $leave_app_status,
                                "leaves_id" => $glac->leaves_id,
                                "flag_parent" => $glac->flag_parent,
                                "credited" => $glac->credited,
                                "required_file_documents" => $glac->required_file_documents,
                                "status" => $glac->status,
                                "approver_account_id" => $glac->approver_account_id,
                                "previous_credits" => $glac->previous_credits,
                                "exclude_lunch_break" => $glac->exclude_lunch_break,
                                "leave_request_type" => $glac->leave_request_type,
                                "leaves_id" => $glac->leaves_id,
                                "no_of_hours" => $glac->credited * 8
                            );
                            
                            $this->db->insert('leaves_close_payroll', $date_insert1);
                        }
                    }
                }
                
                // update for_resend_auto_rejected_id
                $fields = array(
                    "for_resend_auto_rejected_id" => $id,
                );
                
                $where1 = array(
                    "employee_leaves_application_id"=>$leave_info->employee_leaves_application_id,
                    "company_id"=>$leave_info->company_id,
                );
                
                $this->db->where($where1);
                $this->db->update("employee_leaves_application",$fields);
                
                // update also the child : for_resend_auto_rejected_id
                if($leave_info->flag_parent == "yes") {
                    $child_where = array(
                        "leaves_id"=>$leave_info->employee_leaves_application_id,
                        "company_id"=>$leave_info->company_id,
                        "emp_id" => $leave_info->emp_id,
                    );
                    
                    $this->db->where($child_where);
                    $this->db->update("employee_leaves_application",$fields);
                }
                
            }

            if ($stop == "19") {
            	p("19");
            	exit();
            }
            
            $val = $view_last_row_leave_application->employee_leaves_application_id;
            if($what_portal == "employee") {
                // send email notification to approver
                $leave_approver = $this->agm->get_approver_name_leave($this->emp_id,$this->company_id);
                $fullname       = ucfirst($leave_info->first_name)." ".ucfirst($leave_info->last_name);
                $psa_id         = $this->session->userdata('psa_id');
                
                $str            = 'abcdefghijk123456789';
                $shuffled       = str_shuffle($str);
                
                // generate token level
                $str2 = 'ABCDEFG1234567890';
                $shuffled2 = str_shuffle($str2);

                if ($stop == "20") {
                	p("20");
	            	exit();
	            }
                
                $get_approval_settings_disable_status = $this->employee->get_approval_settings_disable_status($this->company_id);
                
                if ($stop == "21") {
                	p("21");
	            	exit();
	            }

                $approver_id = $this->employee->get_approver_name($this->emp_id,$this->company_id)->leave_approval_grp;
                if($approver_id == "" || $approver_id == 0) {
                    // Employee with no approver will use default workflow approval
                    add_workflow_approval_default_group($this->company_id,$this->account_id); // create default if dont have any
                    $approver_id = get_app_default_approver($this->company_id,"Leave")->approval_groups_via_groups_id;
                }

                if ($stop == "22") {
                	p($approver_id);
                	p("22");
	            	exit();
	            }
                
                $workforce_notification = get_notify_settings($approver_id, $this->company_id);

                if ($stop == "23") {
                	p($workforce_notification);
                	p("23");
	            	exit();
	            }

                if($approver_id) {
                    if(is_workflow_enabled($this->company_id)){

                    	if ($stop == "25") {
                        	p("25");
			            	exit();
			            }	
                        if($leave_approver){
                            $last_level = 1; //$this->leave->get_leave_last_level($leave_info->emp_id, $leave_info->company_id);
                            $new_level = 1;
                            $lflag = 0;
                            
                            // without leveling
                            if($workforce_notification){
                                foreach ($leave_approver as $la){
                                    $appovers_id = ($la->emp_id) ? $la->emp_id : "-99{$this->company_id}";
                                    $get_approval_group_via_groups_owner = $this->agm->get_approval_group_via_groups_owner($la->approval_process_id, $la->company_id, $la->approval_groups_via_groups_id,$appovers_id);
                                    
                                    if($get_approval_group_via_groups_owner->emp_id == "-99{$this->company_id}"){
                                        $owner_approver = get_approver_owner_info($this->company_id);
                                        $appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                                        $appr_account_id = $owner_approver->account_id;
                                        $appr_email = $owner_approver->email;
                                        $appr_id = "-99{$this->company_id}";
                                    } else {
                                        $appr_name = ucwords($la->first_name." ".$la->last_name);
                                        $appr_account_id = $la->account_id;
                                        $appr_email = $la->email;
                                        $appr_id = $la->emp_id;
                                    }
                                    
                                    if($la->level == $new_level){
                                        
                                        ###check email settings if enabled###
                                        if($la->ns_leave_email_flag == "yes"){
                                            // send with link
                                            emp_leave_app_notification($shuffled, $view_last_row_leave_application->employee_leaves_application_id, $this->company_id, $leave_info->emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2,$appr_id);
                                        }
                                        ###end checking email settings if enabled###
                                        
                                        if($workforce_notification->sms_notification == "yes"){
                                            $url = base_url()."approval/leave/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
                                            $sms_message = "Click {$url} to approve {$fullname}'s leave.";
                                            send_this_sms_global($this->company_id, $appr_account_id,$sms_message,$psa_id,false);
                                        }
                                        
                                        if($workforce_notification->twitter_notification == "yes"){
                                            $check_twitter_acount = $this->agm->check_twitter_acount($appr_account_id);
                                            if($check_twitter_acount){
                                                $token = $this->leave->get_token($val, $this->company_id, $leave_info->emp_id);
                                                $url = base_url()."approval/leave/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
                                                $message = "A leave application has been filed by {$fullname} and is now waiting for your approval. Click this link {$url} to approve.";
                                                $recipient_account = $check_twitter_acount->twitter;
                                                $this->tweetontwitter($this->emp_id,$message,$recipient_account);
                                            }
                                        }
                                        
                                        if($workforce_notification->facebook_notification == "yes"){
                                            // coming soon
                                        }
                                        
                                        if($workforce_notification->message_board_notification == "yes"){
                                            $token = $this->leave->get_token($val, $this->company_id, $leave_info->emp_id);
                                            $url = base_url()."approval/leave/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
                                            $next_appr_notif_message = "A leave application below has been filed by {$fullname} and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
                                            send_to_message_board($psa_id, $appr_id, $this->emp_id, $this->company_id, $next_appr_notif_message, "system","warning");
                                        }
                                    }
                                }
                            }

                            if ($stop == "24") {
                            	p("24");
				            	exit();
				            }
                            
                            $save_token = array(
                                "leave_id"               => $view_last_row_leave_application->employee_leaves_application_id,
                                "token"                  => $shuffled,
                                "comp_id"                => $this->company_id,
                                "emp_id"                 => $this->emp_id,
                                "approver_id"            => $approver_id,
                                "level"                  => $new_level,
                                "token_level"            => $shuffled2,
                                "date_approved_level"    => date('Y-m-d H:i:s'),
                                "date_reminder_level"    => date('Y-m-d H:i:s')
                            );
                            
                            $save_token_q = $this->db->insert("approval_leave",$save_token);
                            
                            if($insert_employee_leave ){
                                $result = array(
                                    'error'             => false,
                                    'approver_error'    => ""
                                );
                                echo json_encode($result);
                                return false;
                            }
                        }else{
                            $new_level = 1;
                            
                            $save_token = array(
                                "leave_id"               => $view_last_row_leave_application->employee_leaves_application_id,
                                "token"                  => $shuffled,
                                "comp_id"                => $this->company_id,
                                "emp_id"                 => $this->emp_id,
                                "approver_id"            => $approver_id,
                                "level"                  => $new_level,
                                "token_level"            => $shuffled2,
                                "date_approved_level"    => date('Y-m-d H:i:s'),
                                "date_reminder_level"    => date('Y-m-d H:i:s')
                            );
                            
                            $save_token_q = $this->db->insert("approval_leave",$save_token);
                            
                            $result = array(
                                'error'             => false,
                                'approver_error'    => ""
                            );
                            
                            echo json_encode($result);
                            return false;
                        }
                    } else {
                        if($get_approval_settings_disable_status->status == "Inactive") {
                            $value1 = array(
                                "approve_by_hr" => "Yes",
                                "approve_by_head" => "Yes"
                            );
                            $w1 = array(
                                "leave_id" => $view_last_row_leave_application->employee_leaves_application_id,
                                "comp_id" => $this->company_id
                            );
                            $this->db->where($w1);
                            $this->db->update("approval_leave",$value1);
                            $this->leave->new_update_employee_leaves($this->company_id, $this->emp_id, $leave_type, floatval($view_last_row_leave_application->total_leave_requested), $val);
                            
                            $result = array(
                                'error'             => false,
                                'approver_error'    => ""
                            );
                            
                            echo json_encode($result);
                            return false;
                        }
                    }
                }else{
                    // gi delete ni ky g.pausab nsd ni donna, wala na dapat auto approve.. (Employee with no approver will use default workflow approval)
                    /*$value1 = array(
                     "approve_by_hr" => "Yes",
                     "approve_by_head" => "Yes"
                     );
                     $w1 = array(
                     "leave_id" => $view_last_row_leave_application->employee_leaves_application_id,
                     "comp_id" => $this->company_id
                     );
                     $this->db->where($w1);
                     $this->db->update("approval_leave",$value1);
                     $this->leave->update_employee_leaves($this->company_id, $this->emp_id, $leave_type, floatval($view_last_row_leave_application->total_leave_requested), $val);
                     
                     $result = array(
                     'error'             => false,
                     'approver_error'    => ""
                     );
                     
                     echo json_encode($result);
                     return false;*/
                }
                // save token to approval leave
            } else {
            	$this->leave->new_update_employee_leaves($this->company_id, $this->emp_id, $leave_type, floatval($view_last_row_leave_application->total_leave_requested), $val);
                            
                $result = array(
                    'error'             => false,
                    'approver_error'    => ""
                );
                
                echo json_encode($result);
                return false;
            }

            
        }else{
            $shift_err  = "";
            $start_err  = "";
            $end_err    = "";
            #$return_err = "";
            $att_err    = form_error('upload_numbers');
            
            if(form_error('shift_date') !=""){
                $shift_err = 'The Date Field is Required';
            }
            
            if(form_error('start_date')!=""){
                if(form_error('start_date_hr') != "" || form_error('start_date_min') != "" || form_error('start_date_sec') != ""){
                    $start_err = 'The Start Date and Time Field is Required';
                }else{
                    $start_err = 'The Start Date Field is Required';
                }
            }
            
            if(form_error('end_date')!=""){
                
                if(form_error('end_date_hr') != "" || form_error('end_date_min') != "" || form_error('end_date_sec') != ""){
                    $end_err = 'The End Date and Time Field is Required';
                }else{
                    $end_err = 'The End Date Field is Required';
                }
            }
            
            $result = array(
                'error'         => true,
                'eshift_date'   => $shift_err,
                'eleave_type'   => form_error('leave_type'),
                'ereason'       => "This field is required.",
                'estart_date'   => $start_err,
                'eend_date'     => $end_err,
                'eatt'          => $att_err,
                'err_msg'       => "Please fill out the form correctly."
            );
            
            echo json_encode($result);
            return false;
        }

        $result = array(
            'error'             => true,
            'approver_error'    => "",
            'err_msg'			=> "App leave is unable to handle this request. Please use the web instead."
        );
        
        echo json_encode($result);
        return false;
    
    }
}