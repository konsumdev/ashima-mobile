<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Manager_workforce extends CI_Controller{
    var $verify;

    public function __construct(){
        parent::__construct();
        $this->load->model('manager_workforce_model','mw');
        $this->load->model('employee_model','employee');

        $this->company_info = whose_company();

        $this->emp_id = $this->session->userdata('emp_id');
        $this->company_id =$this->employee->check_company_id($this->emp_id);
        $this->account_id = $this->session->userdata('account_id');

    }

    public function personal_details_counter(){
        $active_emp = $this->mw->icount_employees_noinactive($this->company_id,$this->emp_id);
        $term_emp = $this->mw->count_terminated_employees($this->company_id,$this->emp_id);
        $new_hires = $this->mw->count_new_hires_year_to_date($this->company_id,$this->emp_id);
        $count_all_employees = $this->mw->icount_employees($this->company_id,$this->emp_id);

        $res = array(
            "result" => "1",
            "active_emp" => $active_emp,
            "term_emp" => $term_emp,
            "new_hires" => $new_hires,
            "count_all_employees" => $count_all_employees,
        );
        echo json_encode($res);
    }



}