<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Manager_tardiness extends CI_Controller{
    var $verify;

    public function __construct(){
        parent::__construct();
        $this->load->model('Manager_tardiness_model','mtm');
        $this->load->model('employee_model','employee');

        $this->company_info = whose_company();
        $this->emp_id = $this->session->userdata('emp_id');
        $this->company_id =$this->employee->check_company_id($this->emp_id);
        $this->account_id = $this->session->userdata('account_id');

    }

    public function all_tardiness_list(){
        $page = $this->input->post('page');
        $limit = $this->input->post('limit');
        $reqDate = $this->input->post('reqDate');
        $todate = date("Y-m-d");
        if ($reqDate) {
            $todate = date("Y-m-d", strtotime($reqDate));
        }
        //$todate = date("Y-m-d");
        $this->per_page = 10;

        $get_all_tardiness_list = $this->mtm->all_tardiness_list($this->company_id,$this->emp_id,false,(($page-1) * $this->per_page),$limit,$todate);
        $total = ceil($this->mtm->all_tardiness_list($this->company_id,$this->emp_id,true,'','',$todate) / 10);
        if($get_all_tardiness_list){
            echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "current_date" => date("d-M-y"), "total" => $total,"all_tardiness_res" => $get_all_tardiness_list));
            return false;
        }else{
            echo json_encode(array("result" => "0", "current_date" => date("d-M-y")));
            return false;
        }
    }
}