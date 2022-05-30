<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Manager_tardiness_model extends CI_Model {


    public function all_tardiness_list($company_id,$emp_id,$num_rows=false,$page="",$limit="",$date_from="",$date_to=""){
        $final_result = array();
        if(is_numeric($company_id)){

            $select = array(
                'e.first_name',
                'e.last_name',
                // 'pos.position_name',
                'a.profile_image',
                'a.account_id',
                'eti.emp_id',
                'eti.time_in_status',
                'eti.change_log_tardiness_min',
                'a.payroll_cloud_id',
                // 'dep.department_name',
                'eti.employee_time_in_id',
                'eti.comp_id',
                'eti.date',
                'eti.work_schedule_id',
                'epi.department_id',
                // 'pg.period_type',
                'eti.time_in',
                'eti.change_log_tardiness_min',
                'eti.tardiness_min',
                'eti.employee_time_in_id',
            );

            $this->edb->select($select);
            // $this->db->select('pg.name AS payrollgroup');

            $where = array(
                'eti.comp_id' => $company_id,
                'eti.tardiness_min >' => 0,
                'epi.employee_status'   => 'Active',
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
            // $this->edb->join('position AS pos','pos.position_id = epi.position','INNER');
            // $this->edb->join('department AS dep','dep.dept_id = epi.department_id','INNER');
            // $this->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','INNER');
            $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");

            if($num_rows == true) {
                $query = $this->edb->get('employee_time_in AS eti');
                return $query->num_rows();
            }else{                
                // $query = $this->edb->get('employee_time_in AS eti',$limit,$page);
                $query = $this->edb->get('employee_time_in AS eti');
                $result = $query->result();
                // last_query();
                if($result){
                    foreach($result as $row){
                        if($row->tardiness_min <= 0){
                            $total_tardiness = 0;

                        }else{
                            if ($row->time_in_status == "approved") {
                                $total_tardiness = number_format($row->change_log_tardiness_min / 60, 2);
                            }
                            else {
                                $total_tardiness = number_format($row->tardiness_min / 60, 2);
                            }
                        }

                        $temp_res = array(
                            "emp_id" => $row->emp_id,
                            "company_id" => $company_id,
                            "account_id" => $row->account_id,
                            "first_name" => $row->first_name,
                            "last_name" => $row->last_name,
                            "payroll_cloud_id" => $row->payroll_cloud_id,
                            "profile_image" => $row->profile_image,
                            "total_tardiness" => $total_tardiness,
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

    public function employee_tardiness_schedule_min($arr = array()){
        $currentdate 		 = $arr['date'];
        $comp_id 			 = $arr['comp_id'];
        $emp_id 			 = $arr['emp_id'];
        $emp_no 			 = $arr['emp_no'];
        $time_in 			 = $arr['time_in'];
        $employee_time_in_id = $arr['employee_time_in_id'];
        $tard				 = 0;
        $work_schedule_id	 = 0;

        $wsi= $this->emp_work_schedule2($emp_id, $comp_id,$currentdate);

        if($wsi){
            $work_schedule_id = $wsi;
        }

        $day 	= date('l',strtotime($currentdate));

        $arrx 	= array(
            'work_start_time',
            'latest_time_in_allowed'
        );
        $this->edb->select($arrx);

        $w_uwd = array(
            "work_schedule_id"  =>$work_schedule_id,
            "company_id"		=>$comp_id,
            "days_of_work" 		=> $day
        );

        $this->edb->where($w_uwd);
        $q_uwd = $this->edb->get("regular_schedule");
        $r_uwd = $q_uwd->row();

        if($q_uwd->num_rows() > 0){
            $start_time =date('Y-m-d H:i:s',strtotime($currentdate." ".$r_uwd->work_start_time));
            $latest = $r_uwd->latest_time_in_allowed;

            if($latest){
                $start_time = date('Y-m-d H:i:s',strtotime($start_time." +{$r_uwd->latest_time_in_allowed} minutes"));
            }
            if($time_in > $start_time){
                $min = $this->total_hours_worked($time_in, $start_time);
                $tard = $min;

                return $tard;
            }
        }
        else{
            $w = array(
                "eti.employee_time_in_id"=>$employee_time_in_id,
                "eti.status" => "Active"
            );
            $this->edb->where($w);
            $this->db->order_by("eti.time_in","ASC");
            $split_q 		= $this->edb->get("schedule_blocks_time_in AS eti");
            $query_split 	= $split_q->result();

            if($query_split){
                $check = 0;
                foreach($query_split as $row){

                    $sp 	= $this->get_blocks_list($row->schedule_blocks_id);
                    $time 	= date("H:i:s",strtotime($row->time_in));

                    if($time > $sp->start_time){
                        $min = $this->total_hours_worked($time, $sp->start_time);
                        $tard1 = $min;

                        $tard = $tard + $tard1;
                        //$check++;
                    }

                    if($sp->start_time > $sp->end_time){
                        if($time > $sp->start_time || $time < $sp->end_time){
                            $min = $this->total_hours_worked($time, $sp->start_time);
                            $tard1 = $min;

                            $tard = $tard + $tard1;
                            //$check++;
                        }
                    }
                }
                return $tard;
            }
            else{
                # FLEXIBLE HOURS
                $w_fh 	= array(
                    "work_schedule_id"	=>$work_schedule_id,
                    "company_id"		=>$comp_id
                );
                $this->db->where($w_fh);
                $q_fh = $this->db->get("flexible_hours");
                $r_fh = $q_fh->row();

                if($q_fh->num_rows() > 0){
                    $latest = $r_fh->latest_time_in_allowed;
                    if($latest){
                        $dx = date('Y-m-d H:i:s',strtotime($currentdate." ".$latest));
                        if($time_in > $dx){
                            $min = $this->total_hours_worked($time_in, $dx);
                            $tard = $min;
                            return $tard;
                        }
                    }
                }
            }
        }

        return $tard;
    }

    public function emp_work_schedule2($emp_id,$check_company_id,$currentdate){
        // employee group id
        $s = array(
            "ess.work_schedule_id"
        );
        $w_date = array(
            "ess.valid_from <="		=>	$currentdate,
            "ess.until >="			=>	$currentdate
        );
        $this->db->where($w_date);

        $w_emp = array(
            "ess.emp_id"=>$emp_id,
            "ess.company_id"=>$check_company_id,
            "ess.status"=>"Active",
            "ess.payroll_group_id" => 0
        );
        $this->edb->select($s);
        $this->edb->where($w_emp);
        $q_emp = $this->edb->get("employee_shifts_schedule AS ess");
        $r_emp = $q_emp->row();

        if ($r_emp) {
            return $r_emp->work_schedule_id;
        }else{

            $w = array(
                'epi.emp_id'=> $emp_id
            );
            $this->db->where($w);
            $this->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','LEFT');
            $q_pg = $this->edb->get('employee_payroll_information AS epi');
            $r_pg = $q_pg->row();

            return ($r_pg) ? $r_pg->work_schedule_id : FALSE;
        }
    }

    public function total_hours_worked($to,$from){

        $to = date('Y-m-d H:i',strtotime($to));
        $from = date('Y-m-d H:i',strtotime($from));
        $total      = strtotime($to) - strtotime($from);
        $hours      = floor($total / 60 / 60);
        $minutes    = floor(($total - ($hours * 60 * 60)) / 60);
        return  ($hours * 60) + $minutes;
    }

    public function get_blocks_list($schedule_blocks_id){

        $this->edb->where('schedule_blocks_id',$schedule_blocks_id);
        $arr = array(
            'schedule_blocks_id',
            'work_schedule_id',
            'company_id',
            'block_name',
            'start_time',
            'end_time',
            'break_in_min',
            'total_hours_work_per_block'
        );
        $this->edb->select($arr);
        $q3 = $this->edb->get("schedule_blocks");
        $result = $q3->row();

        return $result;
    }
}