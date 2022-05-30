<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Manager_timesheets extends CI_Controller{
    var $verify;

    public function __construct(){
        parent::__construct();
        $this->load->model('manager_timesheets_model','mtm');
        $this->load->model('employee_model','employee');
        $this->load->model('approval_group_model','agm');
        $this->load->model("approve_timeins_model","timeins");

        //$this->company_info = whose_company();
        $this->emp_id = $this->session->userdata('emp_id');
        $this->company_id =$this->employee->check_company_id($this->emp_id);
        $this->account_id = $this->session->userdata('account_id');

    }

    public function all_timesheet_list(){
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        $this->per_page = 10;

        $get_all_timesheet_list = $this->mtm->all_timesheet_list($this->company_id,$this->emp_id,false,(($page-1) * $this->per_page),$limit);
        
        $total = ceil($this->mtm->all_timesheet_list($this->company_id,$this->emp_id,true) / 10);

        if($get_all_timesheet_list){
            echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "current_date" => date("d-M-y"), "total" => $total,"all_timesheet_res" => $get_all_timesheet_list));
            return false;
        }else{
            echo json_encode(array("result" => "0"));
            return false;
        }
    }

    public function all_current_timesheet_list(){
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        $reqDate = $this->input->post('reqDate');
        $todate = date("Y-m-d");
        if ($reqDate) {
            $todate = date("Y-m-d", strtotime($reqDate));
        }
        $this->per_page = 10;

        $get_all_current_timesheet_list = $this->mtm->all_current_timesheet_list($this->company_id,$this->emp_id,false,(($page-1) * $this->per_page),$limit,$todate);
        $total = ceil($this->mtm->all_current_timesheet_list($this->company_id,$this->emp_id,true) / 10);

        if($get_all_current_timesheet_list){
            echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "current_date" => date("d-M-y"), "total" => $total,"all_current_timesheet_res" => $get_all_current_timesheet_list));
            return false;
        }else{
            echo json_encode(array("result" => "0"));
            return false;
        }
    }

    public function get_approvers_name_and_status() {
        $employee_time_in_id = $this->input->post('employee_time_in_id');
        $last_source = $this->input->post('last_source');
        $time_in_status = $this->input->post('time_in_status');
        $change_log_date_filed = $this->input->post('change_log_date_filed');
        $source = $this->input->post('source');
        
        $time_in_info = $this->employee->emp_time_in_information($employee_time_in_id);
        $res = array();
        #if($row->last_source == "Adjusted") {
        if($last_source == null && $time_in_status == null && $change_log_date_filed != null || $last_source == "Adjusted") {
            $time_in_approver = $this->agm->get_approver_name_timein_change_logs($time_in_info->emp_id,$time_in_info->company_id);
            
            $numItems = count($time_in_approver);
            $i = 0;
            $workflow_approvers = workflow_approved_by_level($employee_time_in_id, 'attendance adjustment');
            $emp_timein = $this->employee->get_current_approver($employee_time_in_id);
            
            if($time_in_approver) {
                foreach ($time_in_approver as $la) {
                    $last_level = $this->timeins->get_timein_last_hours($this->emp_id, $this->company_id,"0");
                    if($last_source == null && $time_in_status == null && $change_log_date_filed != null || $time_in_status == "reject") {
                        if($workflow_approvers) {
                            if($emp_timein) {
                                if($emp_timein->level == $la->level) {
                                    #echo $la->first_name.' '.$la->last_name.' - (Rejected)</br>';
                                    $name = $la->first_name.' '.$la->last_name.' - (Rejected)';
                                } else if($emp_timein->level > $la->level) {
                                    #echo $la->first_name.' '.$la->last_name.' - (Approved)</br>';
                                    $name = $la->first_name.' '.$la->last_name.' - (Approved)';
                                } else {
                                    #echo $la->first_name.' '.$la->last_name.' - (Rejected)</br>';
                                    $name = $la->first_name.' '.$la->last_name.' - (Rejected)';
                                }
                            }
                        }
                    } else {
                        if($workflow_approvers) {
                            if($emp_timein) {
                                if($emp_timein->level == $la->level) {
                                    #echo $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')</br>';
                                    $name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
                                } else if($emp_timein->level > $la->level) {
                                    #echo $la->first_name.' '.$la->last_name.' - (Approved)</br>';
                                    $name = $la->first_name.' '.$la->last_name.' - (Approved)';
                                } else {
                                    #echo $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')</br>';
                                    $name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
                                }
                            }
                            
                        } else {
                            if($time_in_status == "pending") {
                                #echo $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')</br>';
                                $name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
                            } else {
                                $name = "";
                            }
                        }
                    }
                    
                    $app = array(
                            "name" => $name
                    );
                    
                    array_push($res,(object)$app);
                }
                
                echo json_encode($res);
                return false;
            } else {
                return false;
            }
        } else {
            if($source == "EP") {
                $change_time_in_approver = $this->agm->get_approver_name_timein_add_logs($time_in_info->emp_id,$time_in_info->company_id);
                
                $numItems = count($change_time_in_approver);
                $i = 0;
                $workflow_approvers = workflow_approved_by_level($employee_time_in_id, 'add timesheet');
                $x = count($workflow_approvers);
                if($change_time_in_approver) {
                    foreach ($change_time_in_approver as $la) {
                        $last_level = $this->timeins->get_timein_last_hours($this->emp_id, $this->company_id,"1");
                        if($time_in_status == "reject") {
                            if($workflow_approvers) {
                                if($x > $la->level) {
                                    #echo $la->first_name.' '.$la->last_name.' - (Approved)</br>';
                                    $name =  $la->first_name.' '.$la->last_name.' - (Approved)';
                                } elseif ($x == $last_level) {
                                    #echo $la->first_name.' '.$la->last_name.' - (Rejected)</br>';
                                    $name = $la->first_name.' '.$la->last_name.' - (Rejected)';
                                } elseif($x < $la->level) {
                                    #echo $la->first_name.' '.$la->last_name.' - (Rejected)</br>';
                                    $name = $la->first_name.' '.$la->last_name.' - (Rejected)';
                                } else {
                                    #echo $la->first_name.' '.$la->last_name.' - (Rejected)</br>';
                                    $name = $la->first_name.' '.$la->last_name.' - (Rejected)';
                                }
                            }
                        } else {
                            if($workflow_approvers) {
                                foreach ($workflow_approvers as $wa) {
                                    if($wa->workflow_level == $la->level) {
                                        if($time_in_status == "pending") {
                                            #echo $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')</br>';
                                            $name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
                                        } else {
                                            #echo $la->first_name.' '.$la->last_name.' - (Approved)</br>';
                                            $name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
                                        }
                                    }else if($time_in_status == "pending") {
                                        #echo $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')</br>';
                                        $name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
                                    } else {
                                        #echo "";
                                        $name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
                                    }
                                }
                            } else {
                                if($time_in_status == "pending") {
                                    #echo $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')</br>';
                                    $name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
                                } else {
                                    #echo "";
                                    $name = "";
                                }
                            }
                        }
                        
                        $app = array(
                                "name" => $name
                        );
                        
                        array_push($res,(object)$app);
                    }
                    
                    echo json_encode($res);
                    return false;
                } else {
                    return false;
                }
            } else {
                if($source == "mobile") {
                    $change_time_in_approver = $this->agm->get_approver_name_timein_location($time_in_info->emp_id,$time_in_info->company_id);
                    
                    $i = 0;
                    $workflow_approvers = workflow_approved_by_level($employee_time_in_id, 'mobile clock in');
                    $x = count($workflow_approvers);
                    if($change_time_in_approver) {
                        foreach ($change_time_in_approver as $la) {
                            $time_in_status = $time_in_status;
                            
                            if($time_in_status == 'approve') {
                                $time_in_status = "Approved";
                            } elseif ($time_in_status == 'reject') {
                                $time_in_status = "Rejected";
                            }
                            
                            $name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
                            
                            $app = array(
                                    "name" => $name
                            );
                            
                            array_push($res,(object)$app);
                            
                        }
                        
                        echo json_encode($res);
                        return false;
                    }
                }
            }
        }
    }   

}