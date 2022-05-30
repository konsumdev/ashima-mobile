<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Payroll_payslip_model_2 extends CI_Model {

    public function generate_printable_payslip($draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
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
            $q = $this->edb->get("payroll_payslip AS pp");
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
                $q = $this->edb->get("payroll_payslip AS pp");
                $r = $q->result();
                return ($r) ? $r : FALSE ;
            }
        }

        /*$where = array(
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
            $this->db->where($get_this_pgroup);
            $this->db->order_by("e.emp_id", "asc");
            $this->db->group_by("pp.emp_id");
            $this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER");
            $this->edb->join("payroll_group AS pg","epi.payroll_group_id = pg.payroll_group_id","INNER");
            $this->edb->join("employee AS e","epi.emp_id = e.emp_id","INNER");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
            $this->edb->join("draft_pay_runs AS dpr","dpr.payroll_group_id = pp.payroll_group_id","INNER");
            $q = $this->edb->get("payroll_payslip AS pp");
            $r = $q->result();
            return ($r) ? $r : FALSE ;
        }else{
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
            $this->db->where($w);
            $this->db->order_by("e.emp_id", "asc");
            $this->db->group_by("pp.emp_id");
            $this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER");
            $this->edb->join("payroll_group AS pg","epi.payroll_group_id = pg.payroll_group_id","INNER");
            $this->edb->join("employee AS e","epi.emp_id = e.emp_id","INNER");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
            $this->edb->join("payroll_run_custom AS prc","pp.emp_id = prc.emp_id","INNER");
            $q = $this->edb->get("payroll_payslip AS pp");
            $r = $q->result();
            return ($r) ? $r : FALSE ;
        }*/
    }

    public function get_absences_hours($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "type"=>"absences",
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_employee_hours");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_absences_hours2($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            //"type"=>"absences",
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_absences");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
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

    public function get_tardiness_hours2($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_tardiness");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
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

    public function get_undertime_hours2($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_undertime");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
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

    public function get_regular_hours($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "type"=>"hoursworked",
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_employee_hours");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_overtime_adjustment($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_overtime");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_holiday_adjustment($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_holiday");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_night_diff_adjustment($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_night_differential");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_paid_leave_adjustment($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_paid_leave");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_workday_adjustment($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_workday");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_allowance_adjustment($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "company_id"=>$company_id,
            "payroll_period"=>$payroll_date,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_allowances");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_commission_adjustment($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_earnings");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_advance_payment_adjustment($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_advance_payment");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_deminimis_adjustment($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_de_minimis");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }
    
    public function get_service_charge_adjustment($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
    	$w = array(
    		"emp_id"=>$emp_id,
    		"period_from"=>$period_from,
    		"period_to"=>$period_to,
    		"payroll_period"=>$payroll_date,
    		"company_id"=>$company_id,
    		"status"=>"Active",
    	);
    	$this->db->where($w);
    	$q = $this->edb->get("payroll_carry_over_adjustment_service_charge");
    	$r = $q->result();
    	return ($r) ? $r : FALSE ;
    }

    public function get_hazard_pay_adjustment($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($w);
        $q = $this->edb->get("payroll_carry_over_adjustment_hazard_pay");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }
    
    public function get_payroll_allowances($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
    	$w = array(
    		"emp_id"=>$emp_id,
    		"period_from"=>$period_from,
    		"period_to"=>$period_to,
    		"payroll_period"=>$payroll_date,
    		"company_id"=>$company_id,
    	);
    	$this->db->where($w);
    	$q = $this->db->get("payroll_allowances");
    	$r = $q->result();
    	return ($r) ? $r : FALSE ;
    }
    
    public function get_payroll_commission($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
    	$w = array(
    		"pc.emp_id"=>$emp_id,
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
    	return ($r) ? $r : FALSE ;
    }
    
    public function get_payroll_deminimis($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
    	$w = array(
    		"emp_id"=>$emp_id,
    		"period_from"=>$period_from,
    		"period_to"=>$period_to,
    		"payroll_period"=>$payroll_date,
    		"company_id"=>$company_id,
    		#"status"=>"Active",
    	);
    	$this->db->where($w);
    	$q = $this->edb->get("payroll_de_minimis");
    	$r = $q->result();
    	return ($r) ? $r : FALSE ;
    }

    public function get_other_deduction($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){//this is for ashima engage
        $w = array(
            "pdcl.emp_id"=>$emp_id,
            "pdcl.company_id"=>$company_id,
            "pdcl.period_from"=>$period_from,
            "pdcl.period_to"=>$period_to,
            "pdcl.payroll_period"=>$payroll_date,
            "pdcl.status"=>"Active",
        );
        /*$ww = array(
            "*",
            "lt.name",
        );
        $this->db->select($ww);*/
        $this->db->where($w);
        //$this->db->group_by("pdcl.deduction_id");
        //$this->db->join("deductions_other_deductions AS lt","pdcl.deduction_id = lt.deductions_other_deductions_id","INNER");
        $q = $this->db->get("payroll_for_other_deductions AS pdcl");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
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

    public function get_other_earnings($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        /*$w = array(
            "pol.emp_id"=>$emp_id,
            "pol.period_from"=>$period_from,
            "pol.period_to"=>$period_to,
            "pol.payroll_period"=>$payroll_date,
            "pol.company_id"=>$company_id,
            "pol.status"=>"Active",
            "eel.company_id"=>$company_id,
            "eel.status"=>"Active",
        );
        #$this->db->where($w);

        $sel = array(
            "pol.amount AS pol_amount"
        );
        $this->db->select($sel);
        $this->db->where($w);

        $this->db->join("employee_earnings_lite AS eel","pol.employee_earning_id = eel.earning_id","INNER");
        $q = $this->db->get("payroll_other_earnings_lite AS pol");
        $r = $q->result();
        return ($r) ? $r : FALSE ;*/
        $w = array(
            "pol.emp_id"=>$emp_id,
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
            "eel.name AS pol_name"
        );
        $this->db->select("*");
        $this->db->select($sel);

        $this->db->where($w);
        $this->db->join("employee_earnings_lite AS eel","pol.employee_earning_id = eel.earning_id","INNER");
        $q = $this->db->get("payroll_other_earnings_lite AS pol");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
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
    
    public function get_other_adjustment_deduction($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
    	$w = array(
    		"emp_id"=>$emp_id,
    		"period_from"=>$period_from,
    		"period_to"=>$period_to,
    		"payroll_period"=>$payroll_date,
    		"company_id"=>$company_id,
    		"status"=>"Active",
    	);
    	$this->db->where($w);
    	$q = $this->db->get("payroll_carry_over_adjustment_other_deduction");
    	$r = $q->result();
    	return ($r) ? $r : FALSE ;
    }
    
    public function get_third_party_loans_deductions($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
    	$w = array(
    		"prl.emp_id"=>$emp_id,
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
    	return ($r) ? $r : FALSE ;
    }

    public function get_third_party_loans_summary($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "ld.emp_id"=>$emp_id,
            "ld.company_id"=>$company_id,
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
        return ($r) ? $r : FALSE ;
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
    
    public function get_government_loans_deductions($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $where = array(
            "emp_id"=>$emp_id,
            "period_from"=>$period_from,
            "period_to"=>$period_to,
            "payroll_period"=>$payroll_date,
            "company_id"=>$company_id,
            "status"=>"Active",
        );
        $this->db->where($where);
        #$this->db->group_by("flag_opening_balance");
        $q = $this->db->get("payroll_run_government_loans");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
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

    public function get_government_loans_summary($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
        $w = array(
            "gld.company_id"=>$company_id,
            "gld.status"=>"Active",
            "gld.emp_id"=>$emp_id,
            "prgl.flag_opening_balance"=>"0",
            "prgl.status"=>"Active",
        	"gld.remittance_due !=" => "0"
        );

        $sel = array(
            "gld.principal_amount AS gov_principal_amount",
            "gld.loan_term AS gov_loan_term",
            "gld.remittance_scheme AS gov_remittance_scheme",
        );
        $this->db->select("*");
        $this->db->select($sel);
        $this->db->where($w);
        $this->db->group_by("gld.loan_type_id");
        $this->db->join("government_loans AS gl","gld.loan_type_id = gl.loan_type_id","INNER");
        $this->db->join("payroll_run_government_loans AS prgl","gld.loan_deduction_id = prgl.loan_deduction_id","INNER");
        $q = $this->db->get("gov_loans_deduction AS gld");
        $r = $q->result();
        
        return ($r) ? $r : FALSE ;
    }

    public function get_government_loans_deductions2($flag_payroll_run_government_loan_id,$flag_opening_balance,$emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
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
    
    public function get_other_adjustment_third_party_loans_deductions($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
    	$w = array(
    		"prl.emp_id"=>$emp_id,
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
    	return ($r) ? $r : FALSE ;
    }
    
    public function get_other_government_loans_deductions($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
    	$w = array(
    		"prgl.emp_id"=>$emp_id,
    		"prgl.period_from"=>$period_from,
    		"prgl.period_to"=>$period_to,
    		"prgl.payroll_period"=>$payroll_date,
    		"prgl.company_id"=>$company_id,
    		"prgl.status"=>"Active",
    		#"gl.company_id"=>$company_id,
    		#"gl.status"=>"Active",
    		#"gld.company_id"=>$company_id,
    		#"gld.status"=>"Active",
    	);
    	$this->db->where($w);
    	#$this->db->join("gov_loans_deduction AS gld","prgl.loan_deduction_id = gld.loan_deduction_id","INNER");
    	#$this->db->join("government_loans AS gl","gld.loan_type_id = gl.loan_type_id","INNER");
    	$q = $this->db->get("payroll_carry_over_adjustment_government_loan AS prgl");
    	$r = $q->result();
    	return ($r) ? $r : FALSE ;
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

    public function check_withholding_tax_fixed($emp_id,$payroll_date,$period_from,$period_to,$company_id){
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
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }
}