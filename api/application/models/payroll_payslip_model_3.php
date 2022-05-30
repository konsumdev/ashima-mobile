<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Payroll_payslip_model_3 extends CI_Model {

    public function count_printable_payslips($draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $emp_id = array();
        $where1 = array(
            "draft_pay_run_id"=>$draft_pay_run_id,
            "company_id" => $company_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "status"=>"Active",
        );
        $this->db->where($where1);
        $query1 = $this->db->get("payroll_run_custom");
        if($query1->num_rows() > 0){

            $w = array(
                "prc.company_id"=>$company_id,
                "prc.draft_pay_run_id"=>$draft_pay_run_id,
                "prc.period_from >="=>$period_from,
                "prc.period_to <="=>$period_to,
                "prc.payroll_period"=>$payroll_date,
                "epi.company_id"=>$company_id,
                "epi.status"=>"Active",
                "epi.deleted"=>"0",
                "pp.period_from >="=>$period_from,
                "pp.period_to <="=>$period_to,
                "pp.payroll_date"=>$payroll_date,
                "e.company_id"=>$company_id,
                "e.status"=>"Active",
                "e.deleted"=>"0",
                "exc.exclude"=>"0",
            );

            $sel = array(
                "lao.name AS lao_name"
            );
            $this->edb->select("*");
            $this->db->select($sel);

            $this->db->where($w);
            $this->db->order_by("e.emp_id", "asc");
            $this->db->group_by("pp.emp_id");
            $this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER");
            $this->edb->join("position AS pst","epi.position = pst.position_id","LEFT");
            $this->edb->join("department AS dpt","epi.department_id = dpt.dept_id","LEFT");
            $this->edb->join("location_and_offices AS lao","epi.location_and_offices_id = lao.location_and_offices_id","LEFT");
            $this->edb->join("payroll_group AS pg","epi.payroll_group_id = pg.payroll_group_id","INNER");
            $this->edb->join("employee AS e","epi.emp_id = e.emp_id","INNER");
            $this->edb->join("exclude_list AS exc","e.emp_id = exc.emp_id","INNER");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
            $this->edb->join("payroll_run_custom AS prc","pp.emp_id = prc.emp_id","INNER");
            $this->edb->join("company AS cp","epi.company_id = cp.company_id","INNER");
            $q = $this->edb->get("payroll_payslip AS pp");
            $r = $q->result();
            if($r){
                foreach($r as $row){
                    array_push($emp_id,$row->emp_id);
                }
            }
        }else{
            $where = array(
                "draft_pay_run_id"=>$draft_pay_run_id,
                "company_id" => $company_id,
                "period_from"=>$period_from,
                "period_to"=>$period_to,
                "pay_period"=>$payroll_date,
                "status"=>"Active",
            );
            $this->db->where($where);
            $query = $this->db->get("draft_pay_runs");
            if($query->num_rows() > 0){
                $row = $query->row();
                $token = $row->token;

                $get_this_pgroup = array(
                    "dpr.company_id"=>$company_id,
                    "dpr.status"=>"Active",
                    "dpr.period_from >="=>$period_from,
                    "dpr.period_to <="=>$period_to,
                    "dpr.pay_period"=>$payroll_date,
                    "dpr.token"=>$token,
                    "epi.company_id"=>$company_id,
                    "epi.status"=>"Active",
                    "epi.deleted"=>"0",
                    "pp.period_from >="=>$period_from,
                    "pp.period_to <="=>$period_to,
                    "pp.payroll_date"=>$payroll_date,
                    "e.company_id"=>$company_id,
                    "e.status"=>"Active",
                    "e.deleted"=>"0",
                    "exc.exclude"=>"0",
                );

                $sel = array(
                    "lao.name AS lao_name"
                );
                $this->edb->select("*");
                $this->db->select($sel);

                $this->db->where($get_this_pgroup);
                $this->db->order_by("e.emp_id", "asc");
                $this->db->group_by("pp.emp_id");
                $this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER");
                $this->edb->join("position AS pst","epi.position = pst.position_id","LEFT");
                $this->edb->join("department AS dpt","epi.department_id = dpt.dept_id","LEFT");
                $this->edb->join("location_and_offices AS lao","epi.location_and_offices_id = lao.location_and_offices_id","LEFT");
                $this->edb->join("payroll_group AS pg","epi.payroll_group_id = pg.payroll_group_id","INNER");
                $this->edb->join("employee AS e","epi.emp_id = e.emp_id","INNER");
                $this->edb->join("exclude_list AS exc","e.emp_id = exc.emp_id","INNER");
                $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
                $this->edb->join("draft_pay_runs AS dpr","dpr.payroll_group_id = pp.payroll_group_id","INNER");
                $this->edb->join("company AS cp","epi.company_id = cp.company_id","INNER");
                $q = $this->edb->get("payroll_payslip AS pp");
                $r = $q->result();
                if($r){
                    foreach($r as $row){
                        array_push($emp_id,$row->emp_id);
                    }
                }
            }
        }

        $tmp_arr = array_unique($emp_id);
        return count($tmp_arr);
    }

    public function generate_printable_payslip($draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id,$limit="",$start="",$flag="false"){
        $where1 = array(
            "draft_pay_run_id"=>$draft_pay_run_id,
            "company_id" => $company_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "status"=>"Active",
        );
        $this->db->where($where1);
        $query1 = $this->db->get("payroll_run_custom");
        if($query1->num_rows() > 0){

            $w = array(
                "prc.company_id"=>$company_id,
                "prc.draft_pay_run_id"=>$draft_pay_run_id,
                "prc.period_from >="=>$period_from,
                "prc.period_to <="=>$period_to,
                "prc.payroll_period"=>$payroll_date,
                "epi.company_id"=>$company_id,
                "epi.status"=>"Active",
                "epi.deleted"=>"0",
                "pp.period_from >="=>$period_from,
                "pp.period_to <="=>$period_to,
                "pp.payroll_date"=>$payroll_date,
                "e.company_id"=>$company_id,
                "e.status"=>"Active",
                "e.deleted"=>"0",
            );

            $sel = array(
                "lao.name AS lao_name"
            );
            $this->edb->select("*");
            $this->db->select($sel);

            $this->db->where($w);
            $this->db->order_by("e.emp_id", "asc");
            $this->db->group_by("pp.emp_id");
            $this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER");
            $this->edb->join("position AS pst","epi.position = pst.position_id","LEFT");
            $this->edb->join("department AS dpt","epi.department_id = dpt.dept_id","LEFT");
            $this->edb->join("location_and_offices AS lao","epi.location_and_offices_id = lao.location_and_offices_id","LEFT");
            $this->edb->join("payroll_group AS pg","epi.payroll_group_id = pg.payroll_group_id","INNER");
            $this->edb->join("employee AS e","epi.emp_id = e.emp_id","INNER");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
            $this->edb->join("payroll_run_custom AS prc","pp.emp_id = prc.emp_id","INNER");
            $this->edb->join("company AS cp","epi.company_id = cp.company_id","INNER");

            if($flag == "false"){
                $q = $this->edb->get("payroll_payslip AS pp");
            }else{
                $q = $this->edb->get("payroll_payslip AS pp",$limit,$start);
            }

            $r = $q->result();
            return ($r) ? $r : FALSE ;
        }else{
            $where = array(
                "draft_pay_run_id"=>$draft_pay_run_id,
                "company_id" => $company_id,
                "period_from"=>$period_from,
                "period_to"=>$period_to,
                "pay_period"=>$payroll_date,
                "status"=>"Active",
            );
            $this->db->where($where);
            $query = $this->db->get("draft_pay_runs");
            if($query->num_rows() > 0){
                $row = $query->row();
                $token = $row->token;

                $get_this_pgroup = array(
                    "dpr.company_id"=>$company_id,
                    "dpr.status"=>"Active",
                    "dpr.period_from >="=>$period_from,
                    "dpr.period_to <="=>$period_to,
                    "dpr.pay_period"=>$payroll_date,
                    "dpr.token"=>$token,
                    "epi.company_id"=>$company_id,
                    "epi.status"=>"Active",
                    "epi.deleted"=>"0",
                    "pp.period_from >="=>$period_from,
                    "pp.period_to <="=>$period_to,
                    "pp.payroll_date"=>$payroll_date,
                    "e.company_id"=>$company_id,
                    "e.status"=>"Active",
                    "e.deleted"=>"0",
                );

                $sel = array(
                    "lao.name AS lao_name"
                );
                $this->edb->select("*");
                $this->db->select($sel);

                $this->db->where($get_this_pgroup);
                $this->db->order_by("e.emp_id", "asc");
                $this->db->group_by("pp.emp_id");
                $this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER");
                $this->edb->join("position AS pst","epi.position = pst.position_id","LEFT");
                $this->edb->join("department AS dpt","epi.department_id = dpt.dept_id","LEFT");
                $this->edb->join("location_and_offices AS lao","epi.location_and_offices_id = lao.location_and_offices_id","LEFT");
                $this->edb->join("payroll_group AS pg","epi.payroll_group_id = pg.payroll_group_id","INNER");
                $this->edb->join("employee AS e","epi.emp_id = e.emp_id","INNER");
                $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
                $this->edb->join("draft_pay_runs AS dpr","dpr.payroll_group_id = pp.payroll_group_id","INNER");
                $this->edb->join("company AS cp","epi.company_id = cp.company_id","INNER");

                if($flag == "false"){
                    $q = $this->edb->get("payroll_payslip AS pp");
                }else{
                    $q = $this->edb->get("payroll_payslip AS pp",$limit,$start);
                }

                $r = $q->result();
                return ($r) ? $r : FALSE ;
            }
        }
    }

    public function get_absences_hours($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_employee_hours");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_employee_hour_id" => $row->payroll_employee_hour_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "payroll_period" => $row->payroll_period,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "hours" => $row->hours,
                    "type" => $row->type,
                    "rate" => $row->rate,
                    "flag_fortnightly" => $row->flag_fortnightly,
                    "fortnightly_amount" => $row->fortnightly_amount,
                    "amount" => $row->amount,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}{$row->type}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_absences_hours2($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_absences");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_adjustment_absences_id" => $row->payroll_carry_over_adjustment_absences_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "payroll_period" => $row->payroll_period,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "total_hours" => $row->total_hours,
                    "date" => $row->date,
                    "amount" => $row->amount,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result, $wd);

            }
        }

        return $result;
    }

    public function get_tardiness_hours($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "type"=>"tardiness",
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_employee_hours");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_tardiness_hours2($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_tardiness");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_adjustment_tardiness_id" => $row->payroll_carry_over_adjustment_tardiness_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "payroll_period" => $row->payroll_period,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "total_hours" => $row->total_hours,
                    "date" => $row->date,
                    "amount" => $row->amount,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_undertime_hours($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "type"=>"undertime",
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_employee_hours");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_undertime_hours2($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_undertime");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_adjustment_undertime_id" => $row->payroll_carry_over_adjustment_undertime_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "payroll_period" => $row->payroll_period,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "total_hours" => $row->total_hours,
                    "date" => $row->date,
                    "amount" => $row->amount,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_overtime_hours($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "type"=>"overtime",
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_employee_hours");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_paid_leave_hours($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "type"=>"paid_leave",
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_employee_hours");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_holiday_hours($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "type"=>"holiday",
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_employee_hours");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_night_diff_hours($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "type"=>"night_differential",
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_employee_hours");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_regular_hours($payroll_date,$period_from,$period_to,$company_id){
        $hw_array = array();
        $w = array(
            "period_from" => $period_from,
            "period_to" => $period_to,
            "payroll_period" => $payroll_date,
            "company_id" => $company_id,
            "status" => "Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_employee_hours");
        $r = $q->result();

        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_employee_hour_id" => $row->payroll_employee_hour_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "payroll_period" => $row->payroll_period,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "hours" => $row->hours,
                    "type" => $row->type,
                    "rate" => $row->rate,
                    "flag_fortnightly" => $row->flag_fortnightly,
                    "fortnightly_amount" => $row->fortnightly_amount,
                    "amount" => $row->amount,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}{$row->type}",
                );

                array_push($hw_array,$wd);

            }
        }

        return $hw_array;
    }

    public function get_overtime_adjustment($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_overtime");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_adjustment_overtime_id" => $row->payroll_carry_over_adjustment_overtime_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "date" => $row->date,
                    "total_hours" => $row->total_hours,
                    "hours_type_id" => $row->hours_type_id,
                    "hours_type_name" => $row->hours_type_name,
                    "amount" => $row->amount,
                    "payroll_period" => $row->payroll_period,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_holiday_adjustment($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_holiday");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_adjustment_holiday_id" => $row->payroll_carry_over_adjustment_holiday_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "hours_type_id" => $row->hours_type_id,
                    "hours_type_name" => $row->hours_type_name,
                    "date" => $row->date,
                    "total_hours" => $row->total_hours,
                    "amount" => $row->amount,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_night_diff_adjustment($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_night_differential");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_adjustment_night_differential_id" => $row->payroll_carry_over_adjustment_night_differential_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "hours_type_id" => $row->hours_type_id,
                    "hours_type_name" => $row->hours_type_name,
                    "date" => $row->date,
                    "total_hours" => $row->total_hours,
                    "amount" => $row->amount,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_paid_leave_adjustment($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_paid_leave");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_adjustment_paid_leave_id" => $row->payroll_carry_over_adjustment_paid_leave_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "total_hours" => $row->total_hours,
                    "date" => $row->date,
                    "amount" => $row->amount,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_workday_adjustment($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_workday");
        $r = $q->result();
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_workday_id" => $row->payroll_carry_over_workday_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "rate" => $row->rate,
                    "total_hours" => $row->total_hours,
                    "amount" => $row->amount,
                    "status" => $row->status,
                    "date" => $row->date,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_allowance_adjustment($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "payroll_carry_over_allowances.company_id"=>$company_id,
            "payroll_carry_over_allowances.payroll_period"=>$payroll_date,
            "payroll_carry_over_allowances.period_from"=>$period_from,
            "payroll_carry_over_allowances.period_to"=>$period_to,
            "payroll_carry_over_allowances.status"=>"Active",
        );
        $this->db->where($w);
        $this->edb->join("allowance_settings","allowance_settings.allowance_settings_id = payroll_carry_over_allowances.allowance_settings_id","LEFT");
        $q = $this->edb->get("payroll_carry_over_allowances");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_allowance_id" => $row->payroll_carry_over_allowance_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "allowance_settings_id" => $row->allowance_settings_id,
                    "name" => $row->name,
                    "allowances_amount" => $row->allowances_amount,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_commission_adjustment($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "payroll_carry_over_earnings.period_from"=>$period_from,
            "payroll_carry_over_earnings.period_to"=>$period_to,
            "payroll_carry_over_earnings.payroll_period"=>$payroll_date,
            "payroll_carry_over_earnings.company_id"=>$company_id,
            "payroll_carry_over_earnings.status"=>"Active",
        );
        $this->db->where($w);
        $this->edb->join("commission_settings","commission_settings.commission_settings_id = payroll_carry_over_earnings.earning_id","LEFT");
        $q = $this->edb->get("payroll_carry_over_earnings");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_earning_id" => $row->payroll_carry_over_earning_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "earning_id" => $row->earning_id,
                    "commission_plan" => $row->commission_plan,
                    "earnings_amount" => $row->earnings_amount,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_advance_payment_adjustment($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_advance_payment");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_advance_payment_id" => $row->payroll_carry_over_advance_payment_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "amount" => $row->amount,
                    "deducted_period" => $row->deducted_period,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_deminimis_adjustment($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_de_minimis");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_de_minimis_id" => $row->payroll_carry_over_de_minimis_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "total_hours" => $row->total_hours,
                    "de_minimis_id" => $row->de_minimis_id,
                    "de_minimis_type" => $row->de_minimis_type,
                    "de_minimis_amount" => $row->de_minimis_amount,
                    "de_minimis_amount_taxable" => $row->de_minimis_amount_taxable,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_service_charge_adjustment($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_service_charge");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_adjustment_service_charge_id" => $row->payroll_carry_over_adjustment_service_charge_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "amount" => $row->amount,
                    "location" => $row->location,
                    "tax_exception" => $row->tax_exception,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_hazard_pay_adjustment($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_hazard_pay");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_adjustment_hazard_pay_id" => $row->payroll_carry_over_adjustment_hazard_pay_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "hazard_pay_name" => $row->hazard_pay_name,
                    "amount" => $row->amount,
                    "tax_exception" => $row->tax_exception,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_payroll_allowances($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->db->get("payroll_allowances");
        $r = $q->result();
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_allowances_id" => $row->payroll_allowances_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "description" => $row->description,
                    "amount" => $row->amount,
                    "max_non_taxable_amount" => $row->max_non_taxable_amount,
                    "allowance_id" => $row->allowance_id,
                    "taxable" => $row->taxable,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_payroll_commission($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "pc.period_from"=>$period_from,
            "pc.period_to"=>$period_to,
            "pc.payroll_period"=>$payroll_date,
            "pc.company_id"=>$company_id,
            "pc.status"=>"Active",
            "cs.company_id"=>$company_id,
            "cs.status"=>"Active",
        );
        $this->db->where($w);
        $this->db->join("payroll_commission AS pc","cs.commission_settings_id = pc.commission_settings_id","INNER");
        $q = $this->db->get("commission_settings AS cs");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_commission_id" => $row->payroll_commission_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "amount" => $row->amount,
                    "commission_settings_id" => $row->commission_settings_id,
                    "commission_plan" => $row->commission_plan,
                    "description" => $row->description,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_payroll_deminimis($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_de_minimis");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_de_minimis_id" => $row->payroll_de_minimis_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "description" => $row->description,
                    "amount" => $row->amount,
                    "excess" => $row->excess,
                    "rules" => $row->rules,
                    "max_non_taxable_amount" => $row->max_non_taxable_amount,
                    "de_minimis_id" => $row->de_minimis_id,
                    "rules_for_excess" => $row->rules_for_excess,
                    "type" => $row->type,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_other_deduction($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "pdcl.company_id"=>$company_id,
            "pdcl.period_from"=>$period_from,
            "pdcl.period_to"=>$period_to,
            "pdcl.payroll_period"=>$payroll_date,
            "pdcl.status"=>"Active",
        );

        $this->db->where($w);
        $q = $this->db->get("payroll_for_other_deductions AS pdcl");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_for_other_deductions_id" => $row->payroll_for_other_deductions_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "deduction_name" => $row->deduction_name,
                    "amount" => $row->amount,
                    "deduction_id" => $row->deduction_id,
                    "employee_deduction_id" => $row->employee_deduction_id,
                    "type" => $row->type,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_deduction2($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "company_id"=>$company_id,
            //"pdcl.period_from"=>$period_from,
            //"pdcl.period_to"=>$period_to,
            //"pdcl.payroll_period"=>$payroll_date,
            "status"=>"Active",
            "flag_ashima"=>"lite",
        );
        $this->db->where($w);
        $q = $this->db->get("employee_deductions");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_other_earnings($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "pol.period_from"=>$period_from,
            "pol.period_to"=>$period_to,
            "pol.payroll_period"=>$payroll_date,
            "pol.company_id"=>$company_id,
            "pol.status"=>"Active",
            "eel.company_id"=>$company_id,
            "eel.status"=>"Active",
        );

        $sel = array(
            "pol.amount AS pol_amount",
            "eoe.name AS pol_name"
        );
        $this->db->select("*");
        $this->db->select($sel);

        $this->db->where($w);
        $this->db->join("earnings_other_earnings AS eoe","eoe.earnings_other_earnings_id = pol.earnings_other_earnings_id","INNER");
        $this->db->join("employee_earnings_lite AS eel","pol.employee_earning_id = eel.earning_id","INNER");
        $q = $this->db->get("payroll_other_earnings_lite AS pol");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_other_earnings_lite_id" => $row->payroll_other_earnings_lite_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "employee_earning_id" => $row->employee_earning_id,
                    "pol_amount" => $row->pol_amount,
                    "pol_name" => $row->pol_name,
                    "tax_exemption" => $row->tax_exemption,
                    "total_occurences_daily_and_weekly" => $row->total_occurences_daily_and_weekly,
                    "earnings_other_earnings_id" => $row->earnings_other_earnings_id,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_adjustment_other_earnings($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->db->get("payroll_carry_over_adjustment_other_earnings");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_adjustment_other_earnings_v2($payroll_date,$period_from,$period_to,$company_id){
        $result = array();

        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->db->get("payroll_carry_over_adjustment_other_earnings");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_adjustment_other_earning_id" => $row->payroll_carry_over_adjustment_other_earning_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "name" => $row->name,
                    "amount" => $row->amount,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function withholding_tax($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>"207",
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_date"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->db->get("payroll_payslip");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_other_adjustment_deduction($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->db->get("payroll_carry_over_adjustment_other_deduction");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_adjustment_other_deduction_id" => $row->payroll_carry_over_adjustment_other_deduction_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "deduction_type_name" => $row->deduction_type_name,
                    "deduction_other_deduction_id" => $row->deduction_other_deduction_id,
                    "amount" => $row->amount,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_third_party_loans_deductions($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "prl.period_from"=>$period_from,
            "prl.period_to"=>$period_to,
            "prl.payroll_date"=>$payroll_date,
            "prl.company_id"=>$company_id,
            "lt.company_id"=>$company_id,
            "lt.status"=>"Active",
        );
        $this->db->where($w);
        $this->db->join("loan_type AS lt","prl.loan_type_id = lt.loan_type_id","INNER");
        $q = $this->db->get("payroll_run_loans AS prl");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_run_loan_id" => $row->payroll_run_loan_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_date,
                    "loan_type_id" => $row->loan_type_id,
                    "installment" => $row->installment,
                    "loan_type_name" => $row->loan_type_name,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_date}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_third_party_loans_summary($company_id){
        $result = array();
        $w = array(
            "ld.company_id"=>$company_id,
            "ld.status"=>"Active",
            "lt.company_id"=>$company_id,
            "lt.status"=>"Active",
        );

        $sel = array(
            "ld.principal_amount AS t_principal_amount",
            "ld.loan_term AS t_loan_term",
        );
        $this->db->select("*");
        $this->db->select($sel);
        $this->db->group_by("ld.loan_type_id");
        $this->db->where($w);
        $this->db->join("loan_type AS lt","ld.loan_type_id = lt.loan_type_id","INNER");
        $q = $this->db->get("loans_deductions AS ld");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    #"period_from" => $row->period_from,
                    #"period_to" => $row->period_to,
                    #"payroll_period" => $row->payroll_period,
                    "first_remittance_date" => $row->first_remittance_date,
                    "remittance_scheme" => $row->remittance_scheme,
                    "loan_deduction_id" => $row->loan_deduction_id,
                    "loan_type_id" => $row->loan_type_id,
                    "loan_type_name" => $row->loan_type_name,
                    "t_principal_amount" => $row->t_principal_amount,
                    "custom_search" => "{$row->emp_id}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function count_third_party_loans_summary($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id,$first_remittance_date,$loan_type_id){
        $w = array(
            "emp_id"=>$emp_id,
            "payroll_date <="=>$payroll_date,
            "payroll_date >="=>$first_remittance_date,
            "company_id"=>$company_id,
            "loan_type_id"=>$loan_type_id,
        );
        $this->db->where($w);
        $q = $this->db->get("payroll_run_loans");
        $r = $q->num_rows();
        return ($r) ? $r : FALSE ;
    }

    public function count_third_party_loans_summary2($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id,$first_remittance_date,$loan_deduction_id){
        $w = array(
            "deduction_id"=>$loan_deduction_id,
        );
        $this->db->where($w);
        $q = $this->db->get("amortization_schedule");
        $r = $q->num_rows();
        return ($r) ? $r : FALSE ;
    }

    public function get_third_party_loans_amortization_schedule($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id,$first_remittance_date,$loan_deduction_id){
        $w = array(
            "deduction_id"=>$loan_deduction_id,
        );
        $this->db->where($w);
        $this->db->order_by("amortization_schedule_id","desc");
        $this->db->limit(1);
        $q = $this->db->get("amortization_schedule");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function count_third_party_loans_summary3($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id,$first_remittance_date,$loan_type_id){
        $w = array(
            "emp_id"=>$emp_id,
            "payroll_date <="=>$payroll_date,
            "payroll_date >="=>$first_remittance_date,
            "company_id"=>$company_id,
            "loan_type_id"=>$loan_type_id,
        );
        $this->db->where($w);
        $q = $this->db->get("payroll_run_loans");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_government_loans_deductions($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $where = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($where);
        $q = $this->db->get("payroll_run_government_loans");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_run_government_loan_id" => $row->payroll_run_government_loan_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "loan_deduction_id" => $row->loan_deduction_id,
                    "flag_opening_balance" => $row->flag_opening_balance,
                    "amount" => $row->amount,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function count_government_loans_deductions($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id,$first_remittance_date,$loan_deduction_id ){
        $where = array(
            "emp_id"=>$emp_id,
            "payroll_period <="=>$payroll_date,
            "payroll_period >="=>$first_remittance_date,
            "loan_deduction_id"=>$loan_deduction_id,
            "flag_opening_balance"=>'0',
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($where);
        $q = $this->db->get("payroll_run_government_loans");
        $r = $q->num_rows();

        return ($r) ? $r : FALSE ;
    }

    public function count_government_loans_deductions2($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id,$first_remittance_date,$loan_deduction_id ){
        $where = array(
            "emp_id"=>$emp_id,
            "payroll_period <="=>$payroll_date,
            "payroll_period >="=>$first_remittance_date,
            "loan_deduction_id"=>$loan_deduction_id,
            "flag_opening_balance"=>'0',
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($where);
        $q = $this->db->get("payroll_run_government_loans");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_government_loans_summary($company_id){
        $result = array();
        $w = array(
            "gld.company_id"=>$company_id,
            "gld.status"=>"Active",
            "prgl.flag_opening_balance"=>"0",
            "prgl.status"=>"Active",
            "gld.remittance_due !=" => "0"
        );

        $sel = array(
            "gld.principal_amount AS gov_principal_amount",
            "gld.loan_term AS gov_loan_term",
            "gld.remittance_scheme AS gov_remittance_scheme",
            "gld.remittance_due AS remittance_due",
        );
        $this->db->select("*");
        $this->db->select($sel);
        $this->db->where($w);
       # $this->db->group_by("gld.loan_type_id");
        $this->db->join("government_loans AS gl","gld.loan_type_id = gl.loan_type_id","INNER");
        $this->db->join("payroll_run_government_loans AS prgl","gld.loan_deduction_id = prgl.loan_deduction_id","INNER");
        $q = $this->db->get("gov_loans_deduction AS gld");
        $r = $q->result();
        #last_query();
        #exit;
        if($r){
            foreach($r as $row){

                $wd = array(
                    "loan_deduction_id" => $row->loan_deduction_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    #"period_from" => $row->period_from,
                    #"period_to" => $row->period_to,
                    #"payroll_period" => $row->payroll_period,

                    "first_remittance_date" => $row->first_remittance_date,
                    "gov_loan_term" => $row->gov_loan_term,
                    "gov_remittance_scheme" => $row->gov_remittance_scheme,
                    "gov_principal_amount" => $row->gov_principal_amount,
                    "loan_type_name" => $row->loan_type_name,
                    "loan_type_id" => $row->loan_type_id,
                    "remittance_due" => $row->remittance_due,

                    "custom_search" => "{$row->emp_id}{$row->payroll_period}{$row->period_from}{$row->period_to}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_government_loans_deductions2($flag_payroll_run_government_loan_id,$flag_opening_balance,$payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        if($flag_opening_balance == "1"){
            $w = array(
                "prgl.payroll_run_government_loan_id"=>$flag_payroll_run_government_loan_id,
                "prgl.period_from"=>$period_from,
                "prgl.period_to"=>$period_to,
                "prgl.payroll_period"=>$payroll_date,
                "prgl.company_id"=>$company_id,
                "prgl.status"=>"Active",
                "prgl.flag_opening_balance"=>"1",
                "gl.company_id"=>$company_id,
                "gl.status"=>"Active",
            );
            $this->db->where($w);
            $this->db->join("government_loans AS gl","prgl.loan_deduction_id = gl.loan_type_id","LEFT");
            $q = $this->db->get("payroll_run_government_loans AS prgl");
            $r = $q->result();
            if($r){
                foreach($r as $row){

                    $wd = array(
                        "payroll_run_government_loan_id" => $row->payroll_run_government_loan_id,
                        "emp_id" => $row->emp_id,
                        "company_id" => $row->company_id,
                        "period_from" => $row->period_from,
                        "period_to" => $row->period_to,
                        "payroll_period" => $row->payroll_period,
                        "loan_deduction_id" => $row->loan_deduction_id,
                        "flag_opening_balance" => $row->flag_opening_balance,
                        "amount" => $row->amount,
                        "status" => $row->status,
                        "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                    );

                    array_push($result,$wd);

                }
            }

        }elseif($flag_opening_balance == "0"){
            $w = array(
                "prgl.payroll_run_government_loan_id"=>$flag_payroll_run_government_loan_id,
                "prgl.period_from"=>$period_from,
                "prgl.period_to"=>$period_to,
                "prgl.payroll_period"=>$payroll_date,
                "prgl.company_id"=>$company_id,
                "prgl.status"=>"Active",
                "gl.company_id"=>$company_id,
                "gl.status"=>"Active",
                "gld.company_id"=>$company_id,
                "gld.status"=>"Active",
            );
            $this->db->where($w);
            $this->db->join("gov_loans_deduction AS gld","prgl.loan_deduction_id = gld.loan_deduction_id","LEFT");
            $this->db->join("government_loans AS gl","gld.loan_type_id = gl.loan_type_id","LEFT");
            $q = $this->edb->get("payroll_run_government_loans AS prgl");
            $r = $q->result();
            if($r){
                foreach($r as $row){

                    $wd = array(
                        "payroll_run_government_loan_id" => $row->payroll_run_government_loan_id,
                        "emp_id" => $row->emp_id,
                        "company_id" => $row->company_id,
                        "period_from" => $row->period_from,
                        "period_to" => $row->period_to,
                        "payroll_period" => $row->payroll_period,
                        "loan_deduction_id" => $row->loan_deduction_id,
                        "flag_opening_balance" => $row->flag_opening_balance,
                        "amount" => $row->amount,
                        "status" => $row->status,
                        "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                    );

                    array_push($result,$wd);

                }
            }
        }

        return $result;
    }

    public function get_government_loans_deductions_3($flag_payroll_run_government_loan_id,$flag_opening_balance,$emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        if($flag_opening_balance == "1"){
            $w = array(
                "prgl.payroll_run_government_loan_id"=>$flag_payroll_run_government_loan_id,
                "prgl.emp_id"=>$emp_id,
                "prgl.period_from"=>$period_from,
                "prgl.period_to"=>$period_to,
                "prgl.payroll_period"=>$payroll_date,
                "prgl.company_id"=>$company_id,
                "prgl.status"=>"Active",
                "prgl.flag_opening_balance"=>"1",
                "gl.company_id"=>$company_id,
                "gl.status"=>"Active",
            );
            $this->db->where($w);
            $this->db->join("government_loans AS gl","prgl.loan_deduction_id = gl.loan_type_id","INNER");
            $q = $this->db->get("payroll_run_government_loans AS prgl");
            $r = $q->result();
            return ($r) ? $r : FALSE ;

        }elseif($flag_opening_balance == "0"){
            $w = array(
                "prgl.payroll_run_government_loan_id"=>$flag_payroll_run_government_loan_id,
                "prgl.emp_id"=>$emp_id,
                "prgl.period_from"=>$period_from,
                "prgl.period_to"=>$period_to,
                "prgl.payroll_period"=>$payroll_date,
                "prgl.company_id"=>$company_id,
                "prgl.status"=>"Active",
                "gl.company_id"=>$company_id,
                "gl.status"=>"Active",
                "gld.company_id"=>$company_id,
                "gld.status"=>"Active",
            );
            $this->db->where($w);
            $this->db->join("gov_loans_deduction AS gld","prgl.loan_deduction_id = gld.loan_deduction_id","INNER");
            $this->db->join("government_loans AS gl","gld.loan_type_id = gl.loan_type_id","INNER");
            $q = $this->db->get("payroll_run_government_loans AS prgl");
            $r = $q->result();
            return ($r) ? $r : FALSE ;
        }
    }

    public function get_other_adjustment_third_party_loans_deductions($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "prl.period_from"=>$period_from,
            "prl.period_to"=>$period_to,
            "prl.payroll_period"=>$payroll_date,
            "prl.company_id"=>$company_id,
            "prl.status"=>"Active",
            "lt.company_id"=>$company_id,
            "lt.status"=>"Active",
        );
        $this->db->where($w);
        $this->db->join("loan_type AS lt","prl.loan_type_id = lt.loan_type_id","INNER");
        $q = $this->db->get("payroll_carry_over_adjustment_third_party_loan AS prl");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_adjustment_third_party_loan_id" => $row->payroll_carry_over_adjustment_third_party_loan_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "loan_type_name" => $row->loan_type_name,
                    "loan_type_id" => $row->loan_type_id,
                    "amount" => $row->amount,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_other_government_loans_deductions($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "prgl.period_from"=>$period_from,
            "prgl.period_to"=>$period_to,
            "prgl.payroll_period"=>$payroll_date,
            "prgl.company_id"=>$company_id,
            "prgl.status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->db->get("payroll_carry_over_adjustment_government_loan AS prgl");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_carry_over_adjustment_government_loan_id" => $row->payroll_carry_over_adjustment_government_loan_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "loan_type_name" => $row->loan_type_name,
                    "loan_type_id" => $row->loan_type_id,
                    "amount" => $row->amount,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_withholding_tax_fixed($emp_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "company_id"=>$company_id,
            "payroll_period"=>$payroll_date,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->db->get("payroll_wttax_fixed");
        $r = $q->row();
        return ($r) ? $r : FALSE ;
    }

    public function check_withholding_tax_fixed($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "company_id"=>$company_id,
            "payroll_period"=>$payroll_date,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->db->get("payroll_wttax_fixed");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_wttax_fixed_id" => $row->payroll_wttax_fixed_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "amount" => $row->amount,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_payroll_insurance($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "pi.emp_id"=>$emp_id,
            "pi.period_from"=>$period_from,
            "pi.period_to"=>$period_to,
            "pi.payroll_period"=>$payroll_date,
            "pi.company_id"=>$company_id,
        );
        $this->db->where($w);
        $this->db->join("employee_insurance AS ei","ei.employee_insurance_id = pi.employee_insurance_id","LEFT");
        $q = $this->db->get("payroll_insurance AS pi");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_payroll_insurance_v2($emp_id = "",$draft_pay_run_id = "",$payroll_date,$period_from,$period_to,$company_id){
        $result = array();
    	$w = array(
    			"pi.period_from"=>$period_from,
    			"pi.period_to"=>$period_to,
    			"pi.payroll_period"=>$payroll_date,
    			"pi.company_id"=>$company_id,
    			"pi.status"=>"Active",
    			#"ei.status"=>"Active",
    	);
    	$this->db->where($w);
    	$this->db->join("employee_insurance AS ei","ei.employee_insurance_id = pi.employee_insurance_id","LEFT");
    	$q = $this->db->get("payroll_insurance AS pi");
    	$r = $q->result();
        if($r){
            foreach($r as $row){
                $wd = array(
                    "payroll_insurance_id" => $row->payroll_insurance_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "payroll_period" => $row->payroll_period,
                    "employee_insurance_id" => $row->employee_insurance_id,
                    "tax_exempt" => $row->tax_exempt,
                    "employer_amount" => $row->employer_amount,
                    "amount" => $row->amount,
                    "insurance_type" => $row->insurance_type,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );
                array_push($result,$wd);
            }
        }

        return $result;
    }

    public function count_printable_payslips_2($draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id,$limit="",$start=""){
        $emp_id = array();
        $where1 = array(
            "draft_pay_run_id"=>$draft_pay_run_id,
            "company_id" => $company_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "status"=>"Active",
        );
        $this->db->where($where1);
        $query1 = $this->db->get("payroll_run_custom");
        if($query1->num_rows() > 0){

            $w = array(
                "prc.company_id"=>$company_id,
                "prc.draft_pay_run_id"=>$draft_pay_run_id,
                "prc.period_from >="=>$period_from,
                "prc.period_to <="=>$period_to,
                "prc.payroll_period"=>$payroll_date,
                "epi.company_id"=>$company_id,
                "epi.status"=>"Active",
                "epi.deleted"=>"0",
                "pp.period_from >="=>$period_from,
                "pp.period_to <="=>$period_to,
                "pp.payroll_date"=>$payroll_date,
                "e.company_id"=>$company_id,
                "e.status"=>"Active",
                "e.deleted"=>"0",
            );

            $sel = array(
                "lao.name AS lao_name"
            );
            $this->edb->select("*");
            $this->db->select($sel);

            $this->db->where($w);
            $this->db->order_by("e.emp_id", "asc");
            $this->db->group_by("pp.emp_id");
            $this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER");
            $this->edb->join("position AS pst","epi.position = pst.position_id","LEFT");
            $this->edb->join("department AS dpt","epi.department_id = dpt.dept_id","LEFT");
            $this->edb->join("location_and_offices AS lao","epi.location_and_offices_id = lao.location_and_offices_id","LEFT");
            $this->edb->join("payroll_group AS pg","epi.payroll_group_id = pg.payroll_group_id","INNER");
            $this->edb->join("employee AS e","epi.emp_id = e.emp_id","INNER");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
            $this->edb->join("payroll_run_custom AS prc","pp.emp_id = prc.emp_id","INNER");
            $this->edb->join("company AS cp","epi.company_id = cp.company_id","INNER");
            $q = $this->edb->get("payroll_payslip AS pp",$limit,$start);
            $r = $q->result();
            if($r){
                foreach($r as $row){
                    array_push($emp_id,$row->emp_id);
                }
            }
        }else{
            $where = array(
                "draft_pay_run_id"=>$draft_pay_run_id,
                "company_id" => $company_id,
                "period_from"=>$period_from,
                "period_to"=>$period_to,
                "pay_period"=>$payroll_date,
                "status"=>"Active",
            );
            $this->db->where($where);
            $query = $this->db->get("draft_pay_runs");
            if($query->num_rows() > 0){
                $row = $query->row();
                $token = $row->token;

                $get_this_pgroup = array(
                    "dpr.company_id"=>$company_id,
                    "dpr.status"=>"Active",
                    "dpr.period_from >="=>$period_from,
                    "dpr.period_to <="=>$period_to,
                    "dpr.pay_period"=>$payroll_date,
                    "dpr.token"=>$token,
                    "epi.company_id"=>$company_id,
                    "epi.status"=>"Active",
                    "epi.deleted"=>"0",
                    "pp.period_from >="=>$period_from,
                    "pp.period_to <="=>$period_to,
                    "pp.payroll_date"=>$payroll_date,
                    "e.company_id"=>$company_id,
                    "e.status"=>"Active",
                    "e.deleted"=>"0",
                );

                $sel = array(
                    "lao.name AS lao_name"
                );
                $this->edb->select("*");
                $this->db->select($sel);

                $this->db->where($get_this_pgroup);
                $this->db->order_by("e.emp_id", "asc");
                $this->db->group_by("pp.emp_id");
                $this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER");
                $this->edb->join("position AS pst","epi.position = pst.position_id","LEFT");
                $this->edb->join("department AS dpt","epi.department_id = dpt.dept_id","LEFT");
                $this->edb->join("location_and_offices AS lao","epi.location_and_offices_id = lao.location_and_offices_id","LEFT");
                $this->edb->join("payroll_group AS pg","epi.payroll_group_id = pg.payroll_group_id","INNER");
                $this->edb->join("employee AS e","epi.emp_id = e.emp_id","INNER");
                $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
                $this->edb->join("draft_pay_runs AS dpr","dpr.payroll_group_id = pp.payroll_group_id","INNER");
                $this->edb->join("company AS cp","epi.company_id = cp.company_id","INNER");
                $q = $this->edb->get("payroll_payslip AS pp",$limit,$start);
               /* $q->num_rows();
                return $q->num_rows();*/
                $r = $q->result();
                if($r){
                    foreach($r as $row){
                        array_push($emp_id,$row->emp_id);
                    }
                }
            }
        }

        $tmp_arr = array_unique($emp_id);
        return count($tmp_arr);
    }

    public function loan_summary($emp_id,$payroll_date,$company_id){
        $s = array(
            "ld.loan_deduction_id",
            "prl.payroll_date",
            "ams.payroll_date",
            "ld.beginning_balance AS beginning_balance",
            "ld.loan_term AS loan_term",
            "lt.loan_type_name AS loan_type_name",
            "prl.installment AS installment"
        );
        $this->db->select($s);
        $w = array(
            "prl.emp_id"=>$emp_id,
            "ld.emp_id"=>$emp_id,
            "prl.payroll_date"=>date("Y-m-d",strtotime($payroll_date)),
            "ams.payroll_date"=>date("m/d/Y",strtotime($payroll_date))
        );
        $this->db->where($w);
        $this->db->join("loans_deductions AS ld","prl.loan_type_id = ld.loan_type_id","LEFT");
        $this->db->join("amortization_schedule AS ams","ld.loan_deduction_id = ams.deduction_id","LEFT");
        $this->db->join("loan_type AS lt","lt.loan_type_id = ld.loan_type_id","LEFT");
        $q = $this->db->get("payroll_run_loans AS prl");
        $r = $q->row();
        return ($r) ? $r : FALSE ;
    }

    public function new_total_payment_third_party_loans($loan_deduction_id,$payroll_date,$type){
        if($type == "counter"){
            $this->db->select("COUNT(*) AS total");
        }else{
            $this->db->select("SUM(principal + interest) AS sum_up");
        }

        $w = array(
            "deduction_id"=>$loan_deduction_id
        );
        $this->db->where($w);
        $this->db->where("(STR_TO_DATE(payroll_date,'%m/%d/%Y') <= '{$payroll_date}')"); // %m/%d/%Y value ni sa field nga na format na daan
        $q = $this->db->get("amortization_schedule");
        $r = $q->row();
        if($r != FALSE){
            if($type == "counter"){
                return $r->total;
            }else{
                return $r->sum_up;
            }
        }else{
            return 0;
        }
    }

    public function get_employee_government_loans($emp_id,$id,$period_from,$period_to,$payroll_period){
        $w = array(
            "prg.emp_id"=>$emp_id,
            "gld.loan_type_id"=>$id,
            "prg.period_from"=>$period_from,
            "prg.period_to"=>$period_to,
            "prg.payroll_period"=>$payroll_period,
            "prg.flag_opening_balance"=>0
        );
        $this->db->where($w);
        $this->db->join("gov_loans_deduction AS gld","gld.loan_deduction_id = prg.loan_deduction_id","LEFT"); // INNER TO LEFT JOIN
        $q = $this->db->get("payroll_run_government_loans AS prg");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_payslip($draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id,$emp_id){
        $where1 = array(
            "company_id" => $company_id,
            "draft_pay_run_id"=>$draft_pay_run_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "emp_id"=>$emp_id,
            "status"=>"Active",
        );
        $this->db->where($where1);
        $query1 = $this->db->get("payroll_run_custom");
        if($query1->num_rows() > 0){
            $w = array(
                "prc.company_id"=>$company_id,
                "prc.draft_pay_run_id"=>$draft_pay_run_id,
                "prc.period_from >="=>$period_from,
                "prc.period_to <="=>$period_to,
                "prc.payroll_period"=>$payroll_date,
                "epi.company_id"=>$company_id,
                "epi.status"=>"Active",
                "epi.deleted"=>"0",
                "pp.period_from >="=>$period_from,
                "pp.period_to <="=>$period_to,
                "pp.payroll_date"=>$payroll_date,
                "pp.emp_id"=>$emp_id,
                "e.company_id"=>$company_id,
                "e.status"=>"Active",
                "e.deleted"=>"0",
            );

            $sel = array(
                "lao.name AS lao_name"
            );
            $this->edb->select("*");
            $this->db->select($sel);

            $this->db->where($w);
            $this->db->order_by("e.emp_id", "asc");
            $this->db->group_by("pp.emp_id");
            $this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","LEFT");
            $this->edb->join("position AS pst","epi.position = pst.position_id","LEFT");
            $this->edb->join("department AS dpt","epi.department_id = dpt.dept_id","LEFT");
            $this->edb->join("location_and_offices AS lao","epi.location_and_offices_id = lao.location_and_offices_id","LEFT");
            $this->edb->join("payroll_group AS pg","epi.payroll_group_id = pg.payroll_group_id","LEFT");
            $this->edb->join("employee AS e","epi.emp_id = e.emp_id","LEFT");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
            $this->edb->join("payroll_run_custom AS prc","pp.emp_id = prc.emp_id","LEFT");
            $this->edb->join("company AS cp","epi.company_id = cp.company_id","LEFT");
            $q = $this->edb->get("payroll_payslip AS pp");
            $r = $q->row();
            return ($r) ? $r : FALSE ;
        }else{
            $get_this_pgroup = array(
                "dpr.company_id"=>$company_id,
                "dpr.status"=>"Active",
                "dpr.period_from >="=>$period_from,
                "dpr.period_to <="=>$period_to,
                "dpr.pay_period"=>$payroll_date,
                "epi.company_id"=>$company_id,
                "epi.status"=>"Active",
                "epi.deleted"=>"0",
                "pp.period_from >="=>$period_from,
                "pp.period_to <="=>$period_to,
                "pp.payroll_date"=>$payroll_date,
                "pp.emp_id"=>$emp_id,
                "e.company_id"=>$company_id,
                "e.status"=>"Active",
                "e.deleted"=>"0",
            );

            $sel = array(
                "lao.name AS lao_name"
            );

            $this->edb->select("*");
            $this->db->select($sel);

            $this->db->where($get_this_pgroup);
            $this->db->order_by("e.emp_id", "asc");
            $this->db->group_by("pp.emp_id");
            $this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","LEFT");
            $this->edb->join("position AS pst","epi.position = pst.position_id","LEFT");
            $this->edb->join("department AS dpt","epi.department_id = dpt.dept_id","LEFT");
            $this->edb->join("location_and_offices AS lao","epi.location_and_offices_id = lao.location_and_offices_id","LEFT");
            $this->edb->join("payroll_group AS pg","epi.payroll_group_id = pg.payroll_group_id","LEFT");
            $this->edb->join("employee AS e","epi.emp_id = e.emp_id","LEFT");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
            $this->edb->join("draft_pay_runs AS dpr","dpr.payroll_group_id = pp.payroll_group_id","LEFT");
            $this->edb->join("company AS cp","epi.company_id = cp.company_id","LEFT");
            $q = $this->edb->get("payroll_payslip AS pp");
            $r = $q->row();
            return ($r) ? $r : FALSE ;
        }
    }

    public function get_data_payslip($company_id,$emp_id,$limit,$start,$from="",$to=""){
        $payroll_dates = $this->get_payslips_payperiod($emp_id, $company_id);

        $where = array(
            "e.company_id" => $company_id,
            "e.status" => 'Active',
            "a.deleted" => '0',
            "pp.emp_id" => $emp_id,
            "pp.flag_prev_emp_income" => '0',
            "pp.status" => 'Active'
        );

        if($payroll_dates){
            $this->db->where_in("pp.payroll_date", $payroll_dates);
        }

        if($from != "" && $to != ""){
            $ft = array(
                "pp.payroll_date >=" => date("Y-m-d",strtotime($from)),
                "pp.payroll_date <=" => date("Y-m-d",strtotime($to))
            );
            $this->db->where($ft);
        }

        $this->edb->where($where);
        $this->edb->join('employee AS e','e.emp_id = pp.emp_id','INNER');
        $this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
        $this->db->order_by("pp.payroll_date","DESC");
        $query = $this->edb->get('payroll_payslip AS pp',$limit,$start);
        $result = $query->result();
        return ($result) ? $result : FALSE ;

    }

    public function get_payslips_payperiod($emp_id, $company_id){
        $arrs = array();

        // PAYROLL CUSTOM
        $w_1 = array(
            "prc.company_id" => $company_id,
            "prc.emp_id" => $emp_id,
            "prc.status" => "Active",
            "ap.generate_payslip" => "Yes"
        );
        $this->db->where($w_1);
        $this->db->where("dpr.view_status = 'Closed'");
        $this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","INNER");
        $this->db->join("approval_payroll AS ap","ap.token = dpr.token","INNER");
        $q_1 = $this->db->get("payroll_run_custom AS prc");
        $r_1 = $q_1->result();
        if($r_1){
            foreach($r_1 as $row){
                array_push($arrs, $row->payroll_period);
            }
        }

        // BY PAYROLL GROUP
        $w = array(
            "dpr.company_id" => $company_id,
            "pp.emp_id" => $emp_id,
            "pp.status" => "Active",
            "dpr.status" => "Active",
            "ap.generate_payslip" => "Yes"
        );
        $this->db->where($w);
        $this->db->where("dpr.view_status = 'Closed'");
        $this->db->join("draft_pay_runs AS dpr","pp.payroll_group_id = dpr.payroll_group_id && dpr.pay_period = pp.payroll_date","INNER");
        $this->db->join("approval_payroll AS ap","ap.token = dpr.token","INNER");
        $q = $this->db->get("payroll_payslip AS pp");
        $r = $q->result();
        if($r){
            foreach($r as $row){
                array_push($arrs, $row->payroll_date);
            }
        }
        return $arrs;
    }

    public function get_info_download($ex_id = "", $comp_id, $from = "", $to = "", $emp_id = ""){
        $payroll_dates = $this->get_payslips_payperiod($emp_id, $comp_id);

        $where = array(
            "pp.company_id" => $comp_id,
            "pp.emp_id" => $emp_id
        );
        if($payroll_dates != null){
            $this->db->where_in("pp.payroll_date", $payroll_dates);
        }
        if($ex_id != "all"){
            $this->db->where_in("pp.payroll_payslip_id", $ex_id);
        }

        if($from != "" && $to != ""){
            $ft = array(
                "pp.payroll_date >=" => date("Y-m-d",strtotime($from)),
                "pp.payroll_date <=" => date("Y-m-d",strtotime($to))
            );
            $this->db->where($ft);
        };

        $this->edb->where($where);
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","left");
        $this->edb->join("employee AS e","epi.emp_id = e.emp_id","left");
        $this->edb->join("company AS c","e.company_id = c.company_id","left");
        $this->edb->join("accounts AS a","e.account_id = a.account_id","left");
        $this->edb->join("basic_pay_adjustment AS b","e.emp_id = b.emp_id","left");
        $this->edb->join("position AS p","p.position_id = epi.position","left");
        $this->db->order_by("payroll_date","desc");
        $query = $this->edb->get("payroll_payslip AS pp");
        $result = $query->result();

        return ($result) ? $result : false;
    }

    public function employee_allowances_settings($company_id){
        $w = array(
            "company_id"=>$company_id,
            "status"=>"Active"
        );
        $this->db->where($w);
        $q = $this->db->get("allowance_settings");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function employee_commission_settings($company_id){
        $w = array(
            "company_id"=>$company_id,
            "status"=>"Active"
        );
        $this->db->where($w);
        $q = $this->db->get("commission_settings");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function excess_deminimis($company_id){
        $w = array(
            "company_id"=>$company_id,
            "status"=>"Active"
        );
        $this->db->where($w);
        $q = $this->db->get("deminimis");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_employee_government_loans_payroll_payslip($emp_id,$company_id,$payroll_period,$period_from,$period_to){
        $this->db->select("SUM(amount) AS total");
        $w = array(
            'emp_id'=>$emp_id,
            'payroll_period'=>$payroll_period,
            'period_from'=>$period_from,
            'period_to'=>$period_to,
            'company_id'=>$company_id
        );
        $this->db->where($w);
        $q = $this->db->get("payroll_carry_over_adjustment_government_loan");
        $r = $q->row();
        return ($r) ? $r->total : FALSE ;
    }

    public function employee_loans($comp_id){
        $w = array(
            "company_id"=>$comp_id,
            "status"=>"Active"
        );
        $this->db->where($w);
        $q = $this->db->get("loan_type");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function deductions_other_deductions($company_id){
        $w = array(
            "comp_id"=>$company_id,
            "status"=>"Active"
        );
        $this->db->where($w);
        $q = $this->db->get('deductions_other_deductions');
        $result = $q->result();
        $q->free_result();
        return ($result) ? $result : false;
    }
    public function employee_government_loans($company_id){
        $w = array(
            "company_id"=>$company_id,
            "status"=>"Active"
        );
        $this->db->where($w);
        $q = $this->db->get('government_loans');
        $result = $q->result();
        return ($result) ? $result : false;
    }

    public function emp_info($emp_id,$comp_id){
        $where = array(
            'employee.emp_id' => $emp_id,
            'employee.company_id' => $comp_id,
            'employee.status'	  => 'Active'
        );
        $this->edb->where($where);
        $this->edb->join('accounts','employee.account_id = accounts.account_id','left');
        $this->edb->join('employee_payroll_information AS epi','epi.emp_id = employee.emp_id','left');
        $this->edb->join('position AS p','p.position_id = epi.position','left');
        $this->edb->join('department AS d','d.dept_id = epi.department_id','left');
        $sql = $this->edb->get('employee');
        $row = $sql->row();
        $sql->free_result();
        return ($row) ? $row : FALSE ;
    }

    public function download_payroll_details($emp_id,$comp_id,$payroll){
        $where = array(
            "emp_id"=>$emp_id,
            "company_id"=>$comp_id,
            "period_from"=>$payroll["period_from"],
            "period_to"=>$payroll["period_to"],
            "payroll_date"=>$payroll["payroll_period"]
        );
        $this->edb->where($where);
        $sql = $this->edb->get("payroll_payslip");
        $r = $sql->row();
        $sql->free_result();
        return ($r) ? $r : FALSE ;
    }

    public function check_draft_pay_run_token($draft_pay_run_id,$company_id){
        $w = array(
            "company_id"=>$company_id,
            "draft_pay_run_id"=>$draft_pay_run_id,
            "status"=>"Active"
        );
        $this->db->where($w);
        $q = $this->db->get("draft_pay_runs");
        $r = $q->row();
        $q->free_result();
        return ($r) ? $r->token : "" ;
    }

    public function check_payroll_status($draft_pay_run_id,$company_id){
        $w = array(
            "draft_pay_run_id"=>$draft_pay_run_id,
            "company_id"=>$company_id,
            "status"=>"Active"
        );
        $this->db->where($w);
        $q = $this->db->get("draft_pay_runs");
        $r = $q->row();
        $q->free_result();
        return ($r) ? $r : FALSE ;
    }

    public function payroll_run_details_where($emp_id,$id,$comp_id){
        $where = array(
            "pp.payroll_payslip_id"=>$id,
            "pp.emp_id"=>$emp_id,
            "pp.company_id"=>$comp_id
        );

        /*$sel = array(
            "lao.name AS lao_name",
            "pp.payroll_group_id AS payroll_group_id",
        );

        $this->edb->select("*");*/
        $sel = array(
            "lao.name AS lao_name",
            "pp.payroll_group_id AS payroll_group_id",
        );
        $this->db->select($sel);

        $this->edb->where($where);
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","LEFT");
        $this->edb->join("position AS pst","epi.position = pst.position_id","LEFT");
        $this->edb->join("department AS dpt","epi.department_id = dpt.dept_id","LEFT");
        $this->edb->join("location_and_offices AS lao","epi.location_and_offices_id = lao.location_and_offices_id","LEFT");
        $this->edb->join("payroll_group AS pg","epi.payroll_group_id = pg.payroll_group_id","LEFT");
        $this->edb->join("employee AS e","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("company AS cp","epi.company_id = cp.company_id","LEFT");
        $sql = $this->edb->get("payroll_payslip AS pp");
        return ($sql->num_rows() > 0) ? $sql->row() : false;
    }

    public function get_employee_hourly_rate($company_id,$emp_id,$basic_pay){
        $final_basic_pay = ($basic_pay * 2);
        #############getting total working days##############
        $total_working_days_in_a_year = "";
        $average_working_hours_per_day = "";
        $where_working_days = array(
            'status'=>'Active',
            'company_id'=>$company_id
        );
        $this->db->where($where_working_days);
        $working_d = $this->db->get("payroll_calendar_working_days_settings");
        if($working_d->num_rows() > 0){
            $row_wd = $working_d->row();
            $total_working_days_in_a_year = $row_wd->working_days_in_a_year;
            $average_working_hours_per_day = $row_wd->average_working_hours_per_day;
        }


        #######get payroll details#####
        $employee_period_type = "";
        $employee_pay_rate_type = "";

        $where = array(
            "epi.emp_id"=>$emp_id,
            "epi.company_id"=>$company_id
        );

        $this->edb->where($where);

        $this->edb->join("employee AS e","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","epi.payroll_group_id = pg.payroll_group_id","LEFT");
        $query = $this->edb->get("employee_payroll_information AS epi");
        $result = $query->row();
        if($result){
            $employee_period_type = $result->period_type;
            $employee_pay_rate_type = $result->pay_rate_type;
        }
        #######get payroll details#####

        if(
            ($employee_period_type == "Semi Monthly" && $employee_pay_rate_type == "By Hour")
            ||
            ($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Hour")
        ){ // hourly
            return ($total_working_days_in_a_year > 0) ? $basic_pay : 0 ;
        }else if(
            ($employee_period_type == "Semi Monthly" && $employee_pay_rate_type == "By Day")
            ||
            ($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Day")
        ){ // daily
            // return ($day_per_year > 0) ? ($bp / 8) : 0 ;
            return ($total_working_days_in_a_year > 0) ? ($basic_pay / $average_working_hours_per_day) : 0 ;
        }else if(
            ($employee_period_type == "Semi Monthly" && $employee_pay_rate_type == "By Month")
            ||
            ($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Month")
        ){ // month
            // return ($day_per_year > 0) ? ($bp / $day_per_year / 8) : 0 ;
            $final_total_working_days = $total_working_days_in_a_year / 12;
            #$final_hourly_rate =($final_basic_pay / $final_total_working_days / $average_working_hours_per_day) ;
            if ($average_working_hours_per_day != "") {
                $final_hourly_rate =($final_basic_pay / $final_total_working_days / $average_working_hours_per_day) ;
            } else {
                $final_hourly_rate =($final_basic_pay / $final_total_working_days) ;
            }

            return ($final_total_working_days > 0) ? $final_hourly_rate : 0 ;
        }else{
            return 0;
        }
    }

    public function get_leave_conversion($payroll_date,$period_from,$period_to,$company_id){
        $result = array();
        $w = array(
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active"
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_run_monetize_unused_leave");
        $r = $q->result();
        if($r){
            foreach($r as $row){

                $wd = array(
                    "payroll_run_monetize_unused_leave_id" => $row->payroll_run_monetize_unused_leave_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "credits" => $row->credits,
                    "amount" => $row->amount,
                    "taxable" => $row->taxable,
                    "payroll_period" => $row->payroll_period,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "leave_type_name" => $row->leave_type_name,
                    "flag_monetized_unused_leave" => $row->flag_monetized_unused_leave,
                    "status" => $row->status,
                    "custom_search" => "{$row->emp_id}{$row->period_from}{$row->period_to}{$row->payroll_period}",
                );

                array_push($result,$wd);

            }
        }

        return $result;
    }

    public function get_excluded_employee($payroll_date,$period_from,$period_to){
        $result = array();

        $w = array(
            "payroll_period"=>$payroll_date,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "status"=>"Active",
        );
        $this->db->where($w);
        $this_query = $this->db->get("exclude_list");
        $this_result = $this_query->result();
        if($this_result){
            foreach($this_result as $row){

                $data = array(
                    "exclude_list_id" => $row->exclude_list_id,
                    "emp_id" => $row->emp_id,
                    "company_id" => $row->company_id,
                    "exclude" => $row->exclude,
                    "on_hold" => $row->on_hold,
                    "reason" => $row->reason,
                    "payroll_period" => $row->payroll_period,
                    "period_from" => $row->period_from,
                    "period_to" => $row->period_to,
                    "status" => $row->status,
                    "filter" => "filter_{$row->emp_id}",
                );

                array_push($result, $data);
            }
        }

        return ($result) ? $result : false;
    }

    public function get_payslip_settings($company_id){##
        $w = array(
            "company_id"=>$company_id,
            "status"=>"Active"
        );
        $this->db->where($w);
        $q = $this->db->get("payslip_settings");
        $r = $q->row();
        return ($r) ? $r : FALSE ;
    }

    public function get_paygroup_settings($payroll_group_id,$company_id){
        $w = array(
            "payroll_group_id"=>$payroll_group_id,
            "company_id"=>$company_id,
            "status"=>"Active"
        );
        $this->db->where($w);
        $q = $this->db->get("payroll_group");
        $r = $q->row();
        return ($r) ? $r : FALSE ;
    }

    public function new_hourly_rate_custom($row, $employee_basic_pay, $period, $emp_hourly_rate_where_array) {

        // check if employee salary is set
        $basic_pay = $row;

        $day_per_year = 0;
        $bp = 0;

        if (! $basic_pay) {
            return false;
        }

        // Get Average Working Hours Per Day
        $average_working_hours_per_day = $emp_hourly_rate_where_array ['average_working_hours_per_day'];

        $bp = $employee_basic_pay;
        // check new basic pay
        /*$edate = date ( 'Y-m-d', strtotime ( $basic_pay->effective_date ) );
        if (is_object ( $period )) {
            $pfrom = date ( 'Y-m-d', strtotime ( $period->period_from ) );
            $pto = date ( 'Y-m-d', strtotime ( $period->period_to ) );
        } elseif (is_array ( $period )) {
            $pfrom = date ( 'Y-m-d', strtotime ( $period ['period_from'] ) );
            $pto = date ( 'Y-m-d', strtotime ( $period ['period_to'] ) );
        }

        if ($basic_pay->effective_date) {
            if (($pfrom <= $edate && $pto >= $edate) || $edate <= $pfrom) {
                $bp = $basic_pay->new_basic_pay;
            } else {
                $bp = $basic_pay->current_basic_pay;
            }
        } else {
            $bp = $basic_pay->current_basic_pay;
        }*/

        // get total days per year
        $day_per_year = $emp_hourly_rate_where_array ['day_per_year'];

        // PERIOD TYPE AND PAY RATE TYPE
        $employee_period_type = $row['period_type'];
        $employee_pay_rate_type = $row['pay_rate_type'];

        // get hourly rate
        if (($employee_period_type == "Semi Monthly" && $employee_pay_rate_type == "By Hour") || ($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Hour") || ($employee_period_type == "Weekly" && $employee_pay_rate_type == "By Hour")) { // hourly
            return ($day_per_year > 0) ? $bp : 0;
        } else if (($employee_period_type == "Semi Monthly" && $employee_pay_rate_type == "By Day") || ($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Day") || ($employee_period_type == "Weekly" && $employee_pay_rate_type == "By Day")) { // daily
            // return ($day_per_year > 0) ? ($bp / 8) : 0 ;
            return ($day_per_year > 0) ? ($bp / $average_working_hours_per_day) : 0;
        } else if (($employee_period_type == "Semi Monthly" && $employee_pay_rate_type == "By Month") || ($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Month") || ($employee_period_type == "Monthly" && $employee_pay_rate_type == "By Month")) { // month
            // return ($day_per_year > 0) ? ($bp / $day_per_year / 8) : 0 ;
            // return ($day_per_year > 0) ? ($bp / $day_per_year / $average_working_hours_per_day) : 0;

            if(isset($emp_hourly_rate_where_array["flag_total_days_per_cutoff"]) && $employee_period_type == "Semi Monthly"){
                return ($day_per_year > 0) ? ( ($bp / 2) / $emp_hourly_rate_where_array["total_days_per_cutoff"] / $average_working_hours_per_day) : 0;
            }else{
                return ($day_per_year > 0) ? ($bp / $day_per_year / $average_working_hours_per_day) : 0;
            }

        } else {
            return 0;
        }
    }

    public function rank_total_working_days_in_a_year_custom($company_id) {
        $ret_array = array ();
        $w = array (
            // "epi.emp_id"=>$emp_id,
            "epi.status" => "Active",
            "epi.company_id" => $company_id
        );
        $s = array (
            #"*",
            "epi.emp_id AS emp_id",
            "pcs.working_days_in_a_year AS total_working_days_in_a_year"
        );
        $this->db->select ( $s );
        $this->db->where ( $w );
        $this->db->where ( "pcs.enable_rank", "No" );
        $this->db->join ( "payroll_calendar_working_days_settings AS pcs", "pcs.company_id = epi.company_id", "LEFT" );
        $q = $this->db->get ( "employee_payroll_information AS epi" );
        $r = $q->result ();
        if ($r) {

            // COMPANY WIDE (DEFUALT VALUE FOR ALL COMPANY)
            foreach ( $r as $row ) {
                $val_array = array (
                    'emp_id' => "emp_id_{$row->emp_id}",
                    'total_working_days_in_a_year' => $row->total_working_days_in_a_year
                );

                array_push ( $ret_array, $val_array );
            }
        } else {
            // WORKING DAYS DEPENDING ON RANK
            $s = array(
                "epi.emp_id AS emp_id",
                "rwd.total_working_days_in_a_year"
            );
            $this->db->select($s);
            $this->db->where ( $w );
            $this->db->join ( "rank_working_days AS rwd", "rwd.rank_id = epi.rank_id", "INNER" );
            $q = $this->db->get ( "employee_payroll_information AS epi" );
            $r = $q->result ();
            if ($r) {
                foreach ( $r as $row ) {
                    $val_array = array (
                        'emp_id' => "emp_id_{$row->emp_id}",
                        'total_working_days_in_a_year' => $row->total_working_days_in_a_year
                    );

                    array_push ( $ret_array, $val_array );
                }
            }
        }
        return $ret_array;
    }

    public function average_working_hours_per_day($company_id) {
        $w = array (
            "company_id" => $company_id,
            "status" => "Active"
        )
            // "average_working_hours_per_day != " => NULL
        ;
        $this->db->where ( $w );
        $this->db->where ( "average_working_hours_per_day IS NOT NULL" );
        $q = $this->db->get ( "payroll_calendar_working_days_settings" );
        $r = $q->row ();
        $average_working_hours_per_day = ($r) ? $r->average_working_hours_per_day : 8;
        return $average_working_hours_per_day;
    }

    public function get_breakdown_pay_details($comp_id,$emp_id,$period_from,$period_to){
        $w = array(
            "company_id" => $comp_id,
            "emp_id" => $emp_id,
            "period_from" => $period_from,
            "period_to" => $period_to,
            "status" => "Active"
        );

        $this->db->where($w);
        $q = $this->db->get("payroll_cronjob");
        $r = $q->row();

        return ($r) ? $r : false;
    }

    /*added by pope the great (09-13-2018)*/
    public function get_draft_payslip_last_recalculated_per_employee($company_id,$emp_id,$draft_pay_run_id){
        $w = array(
            "company_id"=>$company_id,
            "emp_id"=>$emp_id,
            "draft_pay_run_id"=>$draft_pay_run_id,
            "status"=> "Active"
        );

        $this->db->where($w);
        $this->db->order_by("payroll_last_recalculated_counter_id", "DESC");
        $this->db->limit(1);
        $q = $this->db->get("payroll_last_recalculated_counter");
        $r = $q->row();
        return ($r) ? $r : FALSE ;
    }

    public function get_draft_payslip_last_recalculated_per_payroll_group($company_id,$draft_pay_run_id){
        $w = array(
            "company_id"=>$company_id,
            #"emp_id"=> "",
            "draft_pay_run_id"=>$draft_pay_run_id,
            "status"=> "Active"
        );

        $this->db->where($w);


        $this->db->where('emp_id IS NULL', null, false);


        $this->db->order_by("payroll_last_recalculated_counter_id", "DESC");
        $this->db->limit(1);
        $q = $this->db->get("payroll_last_recalculated_counter");
        $r = $q->row();
        return ($r) ? $r : FALSE ;
    }
}