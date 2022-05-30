<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Manager_overtimes_model extends CI_Model {


    public function all_overtimes_list($company_id,$emp_id,$num_rows=false,$page="",$limit="",$date_from="",$date_to=""){
        $final_result = array();
        if(is_numeric($company_id)){

            $select_ot = array(
                "employee_overtime_application.overtime_id",
                "employee.emp_id",
                "employee.first_name",
                "employee.last_name",
                "employee_overtime_application.overtime_date_applied",
                "employee_overtime_application.overtime_from",
                "employee_overtime_application.overtime_to",
                "employee_overtime_application.start_time",
                "employee_overtime_application.end_time",
                "employee_overtime_application.no_of_hours",
                "employee_overtime_application.company_id",
                "employee_overtime_application.reason",
                "employee_overtime_application.notes",
                "employee_overtime_application.approval_date",
                "employee_overtime_application.overtime_status",
                "employee_payroll_information.payroll_group_id",
                "employee_payroll_information.department_id",
                "accounts.payroll_cloud_id",
                "accounts.profile_image",
                "accounts.account_id",
            );
            $select_ot_2 = array(
                "employee.account_id as acc_id",
                // "department.department_name as dept_name",
                // "payroll_group.name as pgname"
            );
            $this->edb->select($select_ot);
            $this->db->select($select_ot_2);

            $where = array(
                "employee_overtime_application.company_id" => $company_id,
                "employee_overtime_application.status" => 'Active',
                "employee_overtime_application.deleted" => '0',
                "employee.status !=" => 'Inactive',
                "edrt.parent_emp_id"    => $emp_id
            );

            if ($date_from) {
                $this->db->where('employee_overtime_application.overtime_date_applied', $date_from);
            }

            $this->db->where($where);
            $this->db->order_by('employee_overtime_application.overtime_id','DESC');
            $this->db->join('employee','employee.emp_id = employee_overtime_application.emp_id','left');
            $this->db->join('employee_payroll_information','employee_payroll_information.emp_id = employee_overtime_application.emp_id','left');
            // $this->db->join('payroll_group','payroll_group.payroll_group_id = employee_payroll_information.payroll_group_id','left');
            // $this->db->join('department','department.dept_id = employee_payroll_information.department_id','left');
            $this->db->join('accounts','accounts.account_id = employee.account_id','left');
            $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = employee.emp_id","LEFT");

            if($num_rows == true) {
                $query = $this->edb->get('employee_overtime_application');
                return $query->num_rows();
            }else{
                // $query = $this->edb->get('employee_overtime_application',$limit,$page);
                $query = $this->edb->get('employee_overtime_application');
                $result = $query->result();
                
                if($result){
                    foreach($result as $row){
                        $temp_res = array(
                            "emp_id" => $row->emp_id,
                            "company_id" => $company_id,
                            "account_id" => $row->account_id,
                            "first_name" => $row->first_name,
                            "last_name" => $row->last_name,
                            "payroll_cloud_id" => $row->payroll_cloud_id,
                            "profile_image" => $row->profile_image,
                            "no_of_hours" => $row->no_of_hours,
                            "full_name" => $row->first_name.' '.$row->last_name,
                            "date_applied" => $row->overtime_date_applied,
                            "overtime_status" => $row->overtime_status,
                            "from" => ($row->start_time) ? idates($row->overtime_from).' '.date("h:i a", strtotime($row->start_time)) : '',
                            "to" => ($row->end_time) ? idates($row->overtime_to).' '.date("h:i a", strtotime($row->end_time)) : '',
                            "base_url" => base_url()
                        );
                        array_push($final_result, $temp_res);
                    }
                }
                return ($final_result) ? $final_result : FALSE;
            }

        }else{
            return false;

        }
    }
}