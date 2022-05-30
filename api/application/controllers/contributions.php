<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Contributions extends CI_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('employee_model','employee');
        $this->load->model("employee_mobile_model","mobile");
        
        $this->emp_id = $this->session->userdata('emp_id');
        $this->company_id =$this->employee->check_company_id($this->emp_id);
        
    }
    
    public function index() {
        
    }
    
    public function sss() {
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        
        $this->per_page = 10;
        
        $result_temp = $this->employee->get_emp_contributions($this->company_id,$this->emp_id,false,"sss",(($page-1) * $this->per_page),$limit);
        $result = array();
        
        if($result_temp) {
            foreach($result_temp as $row) {
                $get_emp_contributions_to_date = $this->employee->get_emp_contributions_to_date($this->company_id,$this->emp_id, $row->pay_date);
                
                $temp = array(
                    "pay_date" => $row->pay_date,
                    "pp_sss" => $row->pp_sss,
                    "pp_sss_to_date" => number_format($get_emp_contributions_to_date['pp_sss'],2,'.',',')
                );
                
                array_push($result,$temp);
            }
        }
        
        $total = ceil($this->employee->get_emp_contributions($this->company_id,$this->emp_id,true,"sss") / 10);
        
        if($result) {
            echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "total" => $total,"list" => $result));
            return false;
        } else {
            echo json_encode(array("result" => "0"));
            return false;
        }
    }
    
    public function sss_summary_head () {
        $sss_contributions_box = $this->employee->contributions_box($this->company_id, $this->emp_id,"sss");
        $get_emp_contributions_count = $this->employee->get_emp_contributions_count($this->company_id,$this->emp_id,"sss");
        
        if($sss_contributions_box) {
            $res = array(
                "sss_date" => $sss_contributions_box['sss_date'],
                "my_SSS_contribution" => number_format($sss_contributions_box['my_SSS_contribution'],2),
                "my_SSS_contribution_count" => ($get_emp_contributions_count) ? $get_emp_contributions_count : 0,
                "pp_sss_employer" => number_format($sss_contributions_box['pp_sss_employer'],2)
            );
            
            echo json_encode(array("result" => "1", "summary" => $res));
            return false;
        } else {
            echo json_encode(array("result" => "0"));
            return false;
        }
    }
    
    public function phic() {
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        
        $this->per_page = 10;
        
        $result_temp = $this->employee->get_emp_contributions($this->company_id,$this->emp_id,false,"phic",(($page-1) * $this->per_page),$limit);
        $result = array();
        
        if($result_temp) {
            foreach($result_temp as $row) {
                $get_emp_contributions_to_date = $this->employee->get_emp_contributions_to_date($this->company_id,$this->emp_id, $row->pay_date);
                
                $temp = array(
                    "pay_date" => $row->pay_date,
                    "pp_philhealth" => $row->pp_philhealth,
                    "pp_philhealth_to_date" => number_format($get_emp_contributions_to_date['pp_philhealth'],2,'.',',')
                );
                
                array_push($result,$temp);
            }
        }
        
        $total = ceil($this->employee->get_emp_contributions($this->company_id,$this->emp_id,true,"phic") / 10);
        
        if($result) {
            echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "total" => $total,"list" => $result));
            return false;
        } else {
            echo json_encode(array("result" => "0"));
            return false;
        }
    }
    
    public function phic_summary_head () {
        $sss_contributions_box = $this->employee->contributions_box($this->company_id, $this->emp_id,"phic");
        $get_emp_contributions_count = $this->employee->get_emp_contributions_count($this->company_id,$this->emp_id,"phic");
        
        if($sss_contributions_box) {
            $res = array(
                "philhealth_date" => $sss_contributions_box['philhealth_date'],
                "my_PHIC_contribution" => number_format($sss_contributions_box['my_PHIC_contribution'],2),
                "my_PHIC_contribution_count" => ($get_emp_contributions_count) ? $get_emp_contributions_count : 0,
                "pp_philhealth_employer" => number_format($sss_contributions_box['pp_philhealth_employer'],2)
            );
            
            echo json_encode(array("result" => "1", "summary" => $res));
            return false;
        } else {
            echo json_encode(array("result" => "0"));
            return false;
        }
    }
    
    public function hdmfm() {
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        
        $this->per_page = 10;
        
        $result_temp = $this->employee->get_emp_contributions($this->company_id,$this->emp_id,false,"hdmf",(($page-1) * $this->per_page),$limit);
        $result = array();
        
        if($result_temp) {
            foreach($result_temp as $row) {
                $get_emp_contributions_to_date = $this->employee->get_emp_contributions_to_date($this->company_id,$this->emp_id, $row->pay_date);
                
                $temp = array(
                    "pay_date" => $row->pay_date,
                    "pp_pagibig" => $row->pp_pagibig,
                    "pp_pagibig_to_date" => number_format($get_emp_contributions_to_date['pp_pagibig'],2)
                );
                
                array_push($result,$temp);
            }
        }
        
        $total = ceil($this->employee->get_emp_contributions($this->company_id,$this->emp_id,true,"hdmf") / 10);
        
        if($result) {
            echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "total" => $total,"list" => $result));
            return false;
        } else {
            echo json_encode(array("result" => "0"));
            return false;
        }
    }
    
    public function hdmfm_summary_head () {
        $sss_contributions_box = $this->employee->contributions_box($this->company_id, $this->emp_id,"hdmf");
        $get_emp_contributions_count = $this->employee->get_emp_contributions_count($this->company_id,$this->emp_id,"hdmf");
        
        if($sss_contributions_box) {
            $res = array(
                "hdmf_date" => $sss_contributions_box['hdmf_date'],
                "my_HDMF_contribution" => number_format($sss_contributions_box['my_HDMF_contribution'],2),
                "my_HDMF_contribution_count" => ($get_emp_contributions_count) ? $get_emp_contributions_count : 0,
                "pp_hdmf_employer" => number_format($sss_contributions_box['pp_hdmf_employer'],2)
            );
            
            echo json_encode(array("result" => "1", "summary" => $res));
            return false;
        } else {
            echo json_encode(array("result" => "0"));
            return false;
        }
    }
    
    public function hdmfv() {
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        
        $this->per_page = 10;
        
        $result = $this->employee->get_contribution_history($this->company_id, $this->emp_id, (($page-1) * $this->per_page),$limit);
        
        if($result) {
            echo json_encode(array("result" => "1", "hdmfv" => $result));
            return false;
        } else {
            echo json_encode(array("result" => "0"));
            return false;
        }
    }
}