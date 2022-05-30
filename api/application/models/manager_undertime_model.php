<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Manager_undertime_model extends CI_Model {


    public function all_undertime_list($company_id,$emp_id,$num_rows=false,$page="",$limit="",$date_from="",$date_to=""){
        $final_result = array();
        if(is_numeric($company_id)){
            $select = array(
                'eti.undertime_min',
                'e.first_name',
                'e.last_name',
                'pos.position_name',
                'a.profile_image',
                'a.account_id',
                'eti.emp_id',
                'a.payroll_cloud_id',
                'dep.department_name',
                'eti.employee_time_in_id',
                'eti.comp_id',
                'eti.date',
                'eti.work_schedule_id',
                'epi.department_id',
                'pg.period_type'
            );

            $this->edb->select($select);
            $this->db->select('pg.name AS payrollgroup');

            $where = array(
                'eti.comp_id' => $company_id,
                'eti.undertime_min >' => 0,
                'edrt.parent_emp_id'    => $emp_id
            );

            if ($date_from) {
                $this->db->where('eti.date', $date_from);
            }

            $this->db->where($where);
            $this->db->order_by('eti.date','DESC');
            $this->edb->join('employee AS e','e.emp_id = eti.emp_id',"INNER");
            $this->edb->join('accounts AS a','a.account_id = e.account_id',"INNER");
            $this->edb->join('employee_payroll_information AS epi','epi.emp_id = eti.emp_id','left');
            $this->edb->join('position AS pos','pos.position_id = epi.position','LEFT');
            $this->edb->join('department AS dep','dep.dept_id = epi.department_id','LEFT');
            $this->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','LEFT');
            $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");

            if($num_rows == true) {
                $query = $this->edb->get('employee_time_in AS eti');
                return $query->num_rows();
            }else{
                //$query = $this->edb->get('employee_time_in AS eti',$limit,$page);
                $query = $this->edb->get('employee_time_in AS eti');
                $result = $query->result();
                // last_query();
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
                            "total_undertime" => ($row->undertime_min !== "" && $row->undertime_min > 0) ? number_format($row->undertime_min/60,2) : "0",
                            "base_url" => base_url(),
                            "date" => $row->date,
                            "full_name" => $row->first_name.' '.$row->last_name,
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