<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Manager Shifts
 * Controller for manager shifts
 * @category controller
 * @version 1.0
 * @author 47
 */
class Manager_leaves extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        $this->load->model('employee_model','employee');
        $this->load->model('emp_manager_leaves_model','mlm');
        $this->load->model('employee_mobile_model','mobile');
        $this->load->model('approval_group_model','agm');
        $this->load->model('approve_leave_model','leave');
        
        $this->company_info = whose_company();
        $this->emp_id       = $this->session->userdata('emp_id');
        $this->company_id   = $this->employee->check_company_id($this->emp_id);
        $this->account_id   = $this->session->userdata('account_id');
        $this->psa_id       = $this->session->userdata('psa_id');
    }

    public function leave_balance_list()
    {
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        $status = $this->input->post('lv_status');
        
        $this->per_page = 10;

        $all_list = $this->mlm->get_employee_leave_list($this->emp_id,$this->company_id, "", "", true);

        $leave_list = $this->mlm->get_employee_leave_list($this->emp_id,$this->company_id, (($page-1) * $this->per_page),$limit, false);

        $total_list = ceil($all_list / 10);

        echo json_encode(
            array(
                "result" => ($leave_list) ? "1" : "0",
                "page" => $page,
                "numPages" => $limit,
                "total" => $total_list,
                "list" => $leave_list,
                "total_count" => $all_list
            )
        );
    }

    public function get_leave_type(){
        $date = date("Y-m-d");
        $w = array(
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
        $entitlements = employee_entitlements($this->emp_id,$this->company_id);
        if($entitlements){
            if($entitlements->entitled_to_leaves == 'yes'){
                echo json_encode($r);
            } else {
                echo json_encode(array("entitled_to_leaves" => false));
                return false;
            }
        } else {
            echo json_encode(array("entitled_to_leaves" => false));
            return false;
        }
        
    }

    public function leave_history_list()
    {
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');

        if(!is_numeric($page)) {
            $page = 1;
        }

        $this->per_page = 10;
        
        $all_list = $this->mlm->get_manager_leave_history($this->emp_id,$this->company_id, "", "", true, $this->emp_id);
        $leave_list = $this->mlm->get_manager_leave_history($this->emp_id,$this->company_id, (($page-1) * $this->per_page),$limit, false, $this->emp_id);
        
        $total_list = ceil($all_list / 10);

        echo json_encode(
            array(
                "result" => ($leave_list) ? "1" : "0",
                "page" => $page,
                "numPages" => $limit,
                "total" => $total_list,
                "list" => $leave_list,
                "total_count" => $all_list,
                "base_url" => base_url()
            )
        );
    }

    public function leave_history_approve()
    {
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');

        if(!is_numeric($page)) {
            $page = 1;
        }

        $this->per_page = 10;

        $all_list = $this->mlm->get_manager_leave_history_approve($this->emp_id,$this->company_id, "", "", true, $this->emp_id);
        $leave_list = $this->mlm->get_manager_leave_history_approve($this->emp_id,$this->company_id, (($page-1) * $this->per_page),$limit, false, $this->emp_id);
        
        $total_list = ceil($all_list / 10);

        echo json_encode(
            array(
                "result" => ($leave_list) ? "1" : "0",
                "page" => $page,
                "numPages" => $limit,
                "total" => $total_list,
                "list" => $leave_list,
                "total_count" => $all_list,
                "base_url" => base_url()
            )
        );
    }

    public function leave_history_pending()
    {
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');

        if(!is_numeric($page)) {
            $page = 1;
        }

        $this->per_page = 10;

        $all_list = $this->mlm->get_manager_leave_history_pending($this->emp_id,$this->company_id, "", "", true, $this->emp_id);
        $leave_list = $this->mlm->get_manager_leave_history_pending($this->emp_id,$this->company_id, (($page-1) * $this->per_page),$limit, false, $this->emp_id);
        
        $total_list = ceil($all_list / 10);

        echo json_encode(
            array(
                "result" => ($leave_list) ? "1" : "0",
                "page" => $page,
                "numPages" => $limit,
                "total" => $total_list,
                "list" => $leave_list,
                "total_count" => $all_list,
                "base_url" => base_url()
            )
        );
    }

    function leave_history_reject()
    {
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');

        if(!is_numeric($page)) {
            $page = 1;
        }
        
        $this->per_page = 10;

        $all_list = $this->mlm->get_manager_leave_history_reject($this->emp_id,$this->company_id, "", "", true, $this->emp_id);
        $leave_list = $this->mlm->get_manager_leave_history_reject($this->emp_id,$this->company_id, (($page-1) * $this->per_page),$limit, false, $this->emp_id);
        
        $total_list = ceil($all_list / 10);

        echo json_encode(
            array(
                "result" => ($leave_list) ? "1" : "0",
                "page" => $page,
                "numPages" => $limit,
                "total" => $total_list,
                "list" => $leave_list,
                "total_count" => $all_list,
                "base_url" => base_url()
            )
        );
    }

    function leave_doughnut(){
        $leave_array = array();
        $leave_type_id = $this->input->post('leave_type');
        $emp_id = $this->input->post('emp_id');
        $leave_credits = $this->employee->leave_credits_for_doughnut($this->company_id,$emp_id,$leave_type_id);
        
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

    public function get_pending_and_approve_to_date() {
        $leave_type_id = $this->input->post('leave_type_id');
        $emp_id = $this->input->post('emp_id');
        
        $approval_pending = $this->mobile->get_pending_approval_leaves($leave_type_id,$emp_id);
        $ap_days = 0;
        
        if($approval_pending){
            $ap_days =  $approval_pending->total_request !='' ? $approval_pending->total_request : '0';
        }
        
        $approval_approve = $this->mobile->get_pending_approval_leaves($leave_type_id,$emp_id,"approve");
         
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
}