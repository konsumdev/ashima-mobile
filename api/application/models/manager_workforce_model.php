<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Manager_workforce_model extends CI_Model {

    public function icount_employees_noinactive($company_id,$emp_id){
        $where = array(
            'e.company_id'			=> $company_id,
            'e.status'				=> 'Active',
            'epi.employee_status'	=> 'Active',
            'epi.status'			=> 'Active',
            'a.deleted'				=> '0',
            'a.user_type_id'		=> '5',
            'edrt.parent_emp_id' 	=> $emp_id
        );
        $this->edb->where($where);
        $this->edb->join('employee_payroll_information AS epi','epi.emp_id=e.emp_id','INNER');
        $this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        $q = $this->edb->get('employee AS e');
        $row = $q->num_rows();
        return $row;
    }

    public function count_terminated_employees($comp_id, $emp_id){
        $where = array(
            "et.company_id"			=> $comp_id,
            'edrt.parent_emp_id'    => $emp_id
        );
        $this->db->where($where);
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = et.emp_id","LEFT");
        $qry = $this->db->get("employee_termination AS et");
        $res = $qry->result();
        return $qry->num_rows();
    }

    public function count_new_hires_year_to_date($comp_id,$emp_id){
        $where = "et.date_hired BETWEEN DATE_FORMAT(NOW() ,'%Y-01-01') AND NOW() AND et.company_id = '{$comp_id}' AND edrt.parent_emp_id = '{$emp_id}'";
        $this->db->where($where);
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = et.emp_id","LEFT");
        $qry = $this->db->get("employee_payroll_information AS et");
        $res = $qry->result();
        return $qry->num_rows();
    }

    function icount_employees($company_id,$emp_id){
        $where = array(
            'e.company_id'			=> $company_id,
            'e.status'				=> 'Active',
            'a.deleted'				=> '0',
            'a.user_type_id'		=> '5',
            'edrt.parent_emp_id' 	=> $emp_id
        );
        $this->edb->where($where);
        $this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        $q = $this->edb->get('employee AS e');
        $row = $q->num_rows();
        return $row;
    }

}