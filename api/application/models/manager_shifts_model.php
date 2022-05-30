<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Manager Shifts Model
 * Model for manager employee directory
 * @category model
 * @version 1.0
 * @author 47
 */
class Manager_shifts_model extends CI_Model
{
    public function get_employee_list($company_id, $parent_emp_id)
    {
        $konsum_key = konsum_key();
        $this->db->_protect_identifiers=false;
        $select = array(
            'accounts.payroll_cloud_id',
            'employee.last_name',
            'employee.first_name',
            'employee.emp_id',
            'employee.account_id',
            'accounts.profile_image',
            'employee.company_id',
            // 'accounts.email',
            // 'accounts.login_mobile_number',
            //'position.position_name',
            // 'employee.address',
            // 'accounts.login_mobile_number_2',
            // 'accounts.telephone_number',
            // 'employee.middle_name',
            // 'employee.nickname',
            // 'employee.dob',
            // 'employee.gender',
            // 'employee.marital_status',
            // 'employee.citizenship_status',
            'employee_payroll_information.employee_status',
            // 'rank.rank_name',
            // 'department.department_name',
            // 'cost_center.cost_center_code',
            // 'location_and_offices.name',
            'employee_payroll_information.date_hired',
            'employee_payroll_information.payroll_group_id'
        );
        
        $where = array(
            'employee.company_id' => $company_id,
            'employee.status'     => 'Active',
            'accounts.user_type_id' => '5',
            'accounts.deleted'=>'0',
            'employee.status'=>'Active',
            'employee.deleted'=>'0',
            'employee_payroll_information.employee_status' => 'Active',
            'edrt.parent_emp_id' => $parent_emp_id
        );
        
        $konsum_key = konsum_key();
        $this->edb->select($select);
        $this->db->where($where);
        
        $this->edb->join('accounts','accounts.account_id = employee.account_id','INNER');
        $this->db->join('employee_details_reports_to AS edrt', 'edrt.emp_id = employee.emp_id', 'LEFT');
        $this->edb->join('employee_payroll_information','employee_payroll_information.emp_id = employee.emp_id','left');
        //$this->edb->join('department','department.dept_id = employee_payroll_information.department_id','left');
        //$this->edb->join('position', 'position.position_id = employee_payroll_information.position', 'left');
        //$this->edb->join('rank', 'rank.rank_id = employee_payroll_information.rank_id', 'left');
        //$this->edb->join('cost_center', 'cost_center.cost_center_id = employee_payroll_information.cost_center', 'left');
        //$this->edb->join('location_and_offices', 'location_and_offices.location_and_offices_id = employee_payroll_information.location_and_offices_id', 'left');
        $this->db->order_by('CONVERT(UPPER(AES_DECRYPT(employee.last_name, "'.$konsum_key.'")) using latin1)', 'ASC');
        $q = $this->edb->get('employee');
        $result = $q->result();
        return $result;
    }
}
