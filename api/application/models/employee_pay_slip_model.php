<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Employee Pay Slip Model
 *
 * @category Model
 * @version 1.0
 * @author Ret Karlo Ferrolino
 * 
 */
class Employee_pay_slip_model extends CI_Model {
	/**
	 * get employee payslips
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param unknown $limit
	 * @param unknown $start
	 * @param string $from
	 * @param string $to
	 */
	public function get_data_payslip($company_id,$emp_id,$limit,$start, $from = "", $to = "")
	{
		$payroll_dates = $this->get_payslips_payperiod($emp_id, $company_id);
		
		$where = array(
			"e.company_id" => $company_id,
			"e.status" => 'Active',
			"a.deleted" => '0',
			"pp.emp_id" => $emp_id,
			"pp.flag_prev_emp_income" => '0'
		);
		
		if($payroll_dates != null){
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
		
		return $result;
	}
	
	/**
	 * get employee payslips count
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param string $from
	 * @param string $to
	 */
	public function get_data_payslip_count($company_id,$emp_id, $from = "", $to = "")
	{
		$payroll_dates = $this->get_payslips_payperiod($emp_id, $company_id);
		
		$where = array(
			"e.company_id" => $company_id,
			"e.status" => 'Active',
			"a.deleted" => '0',
			"pp.emp_id" => $emp_id,
			"pp.flag_prev_emp_income" => '0'
		);
		
		if($payroll_dates != null){
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
		$query = $this->edb->get('payroll_payslip AS pp');
		$result = $query->num_rows();
		
		return $result;
	}
	
	public function get_data_pay_open_bal($company_id,$emp_id,$limit,$start){
		$where = array(
			"e.company_id" => $company_id,
			"e.status" => 'Active',
			"a.deleted" => '0',
			"pp.emp_id" => $emp_id,
			"pp.flag_prev_emp_income !=" => '0'
		);
		$query = $this->edb->where($where);
		$this->edb->join('employee AS e','e.account_id=a.account_id','inner');
		$this->edb->join('payroll_payslip AS pp','e.emp_id=pp.emp_id','inner');
		$this->db->order_by('pp.payroll_date','DESC');
		$query = $this->edb->get('accounts AS a',$limit,$start);
		$result = $query->result();
		
		return $result;
	}
	public function get_detail_pay_open_bal($id,$company_id,$emp_id){
		$where = array(
				"e.company_id" => $company_id,
				"e.status" => 'Active',
				"a.deleted" => '0',
				"pp.emp_id" => $emp_id,
				"pp.flag_prev_emp_income" => '1',
				"pp.payroll_payslip_id" => $id
		);
		$query = $this->edb->where($where);
		$this->edb->join('employee AS e','e.account_id=a.account_id','inner');
		$this->edb->join('payroll_payslip AS pp','e.emp_id=pp.emp_id','inner');
		$query = $this->edb->get('accounts AS a');
		$result = $query->result();
		return $result;
	}
	public function get_pay_com($company_id,$emp_id,$pp,$pf,$pt){
		$where = array(
				"pc.company_id" => $company_id,
				"pc.emp_id" => $emp_id,
				"pc.payroll_period" => $pp,
				"pc.period_from" => $pf,
				"pc.period_to" => $pt
		);
		$query = $this->edb->where($where);
		$query = $this->edb->get('payroll_commission AS pc');
		$result = $query->result();
		return $result;
	}
	public function get_gov_loans($company_id,$emp_id,$pp,$pf,$pt){
		$where = array(
				"prgl.company_id" => $company_id,
				"prgl.emp_id" => $emp_id,
				"prgl.payroll_period" => $pp,
				"prgl.period_from" => $pf,
				"prgl.period_to" => $pt,
				"prgl.flag_opening_balance" => "1"
		);
		$this->db->where($where);
		$this->db->join('government_loans AS gl','gl.loan_type_id = prgl.loan_deduction_id');
		$query = $this->db->get('payroll_run_government_loans AS prgl');
		$result = $query->result();
		return $result;
	}
	public function get_third_party_loans($company_id,$emp_id,$pp,$pf,$pt){
		$where = array(
				"prl.company_id" => $company_id,
				"prl.emp_id" => $emp_id,
				"prl.payroll_date" => $pp,
				"prl.period_from" => $pf,
				"prl.period_to" => $pt
		);
		$this->db->where($where);
		$this->db->join('loan_type AS tl','tl.loan_type_id = prl.loan_type_id');
		$query = $this->db->get('payroll_run_loans AS prl');
		$result = $query->result();
		return $result;
	}
	public function get_other_deduct($company_id,$emp_id,$pp,$pf,$pt){
		$where = array(
				"prdl.company_id" => $company_id,
				"prdl.emp_id" => $emp_id,
				"prdl.payroll_period" => $pp,
				"prdl.period_from" => $pf,
				"prdl.period_to" => $pt
		);
		$this->db->where($where);
		$query = $this->db->get('payroll_for_other_deductions AS prdl');
		$result = $query->result();
		return $result;
	}
	public function get_data_other_deductions($company_id,$emp_id,$limit,$start)
	{
		$where = array(
				"e.company_id" => $company_id,
				"e.status" => 'Active',
				"a.deleted" => '0',	
		);
		$query = $this->edb->where($where);
		$this->edb->join('employee AS e','e.account_id=a.account_id','inner');
		$this->edb->join('payroll_run_other_deductions AS prod','e.emp_id=prod.emp_id','inner');
		$query = $this->edb->get('accounts AS a',$limit,$start);
		$result = $query->result();
		return $result;
	}
	
	/**
	 * Check Payslip ID
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $id
	 */
	public function check_payslip_id($emp_id,$comp_id,$id)
	{
		$where = array(
				"e.company_id" => $comp_id,
				"payroll_payslip_id"=>$id,
				"e.status" => 'Active',
				"a.deleted" => '0',
				"pp.emp_id" => $emp_id,
				
		);
		$query = $this->edb->where($where);
		$this->edb->join('employee AS e','e.account_id=a.account_id','inner');
		$this->edb->join('payroll_payslip AS pp','e.emp_id=pp.emp_id','inner');
		$query = $this->edb->get('accounts AS a');
		$result = $query->result();
			return $result;
	
	}
	
	/**
	 * Get New Other Deductions
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 */
	public function new_other_deductions($company_id,$emp_id,$payroll_period,$period_from,$period_to){
		$w = array(
				"emp_id"=>$emp_id,
				"company_id"=>$company_id,
				"payroll_period"=>$payroll_period,
				"period_from"=>$period_from,
				"period_to"=>$period_to,
				"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_for_other_deductions");
		return ($q->num_rows() > 0) ? $q->result() : FALSE ;
	}
	
	/**
	 * Check Payroll Status
	 * @param unknown $emp_id
	 * @param unknown $payroll_group_id
	 * @param unknown $payroll_date
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function check_payroll_status($company_id,$emp_id,$payroll_group_id,$payroll_date,$period_from,$period_to){
		$w = array(
			"prc.emp_id"=>$emp_id,
			"prc.company_id"=>$company_id,
			"prc.payroll_period"=>$payroll_date,
			"prc.period_from"=>$period_from,
			"prc.period_to"=>$period_to,
			"prc.status"=>"Active",
			"dpr.view_status"=>"Closed"
		);	
		$this->db->where($w);
		$this->db->join("draft_pay_runs as dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
		$q = $this->db->get("payroll_run_custom AS prc");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Payroll Status
	 * @param unknown $emp_id
	 * @param unknown $payroll_group_id
	 * @param unknown $payroll_date
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function check_payroll_status_by_payroll_group($company_id,$emp_id,$payroll_group_id,$payroll_date,$period_from,$period_to){
		$w = array(
			"dpr.payroll_group_id"=>$payroll_group_id,
			"dpr.company_id"=>$company_id,
			"dpr.pay_period"=>$payroll_date,
			"dpr.period_from"=>$period_from,
			"dpr.period_to"=>$period_to,
			"dpr.status"=>"Active",
			"dpr.view_status"=>"Closed"
		);
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs as dpr");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	public function generate_payslip_yes($draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id){
		$where = array(
			"dpr.pay_period"=>$payroll_date,
			"dpr.period_from"=>$period_from,
			"dpr.period_to"=>$period_to,
			"dpr.company_id"=>$company_id,	
			"dpr.status"=>"Active",
			"ap.generate_payslip"=>"Yes"	
		);
		$this->db->where($where);
		$this->edb->join("approval_payroll AS ap","dpr.token = ap.token","INNER");
		$q = $this->edb->get("draft_pay_runs AS dpr");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	public function generate_printable_workforce_payslip($draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id,$emp_id){
		$where1 = array(
				"prc.draft_pay_run_id"=>$draft_pay_run_id,
				"prc.company_id" => $company_id,
				"prc.period_from"=>$period_from,
				"prc.period_to"=>$period_to,
				"prc.payroll_period"=>$payroll_date,
				"prc.emp_id" =>$emp_id,
				"prc.status"=>"Active",
				"ap.generate_payslip"=>"Yes"
		);
		$this->db->where($where1);
		$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id=prc.draft_pay_run_id","INNER");
		$this->db->join("approval_payroll AS ap","ap.token=dpr.token","INNER");
		$query1 = $this->db->get("payroll_run_custom AS prc");
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
				"pp.emp_id"=>$emp_id,
			);
		
			$sel = array(
					"lao.name AS lao_name",
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
					"dpr.draft_pay_run_id"=>$draft_pay_run_id,
					"dpr.company_id" => $company_id,
					"dpr.period_from"=>$period_from,
					"dpr.period_to"=>$period_to,
					"dpr.pay_period"=>$payroll_date,
					"dpr.status"=>"Active",
					"ap.generate_payslip"=>"Yes",
			);
			$this->db->where($where);
			$this->db->join("approval_payroll AS ap","ap.token=dpr.token","INNER");
			$query = $this->db->get("draft_pay_runs AS dpr");
			if($query->num_rows() > 0){
				$row = $query->row();
				$token = $row->token;
		
				$get_this_pgroup = array(
						"dpr.company_id"=>$company_id,
						"dpr.status"=>"Active",
						"dpr.period_from >="=>$period_from,
						"dpr.period_to <="=>$period_to,
						"dpr.pay_period"=>$payroll_date,
						"pp.emp_id"=>$emp_id,
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
 	}

 	# for getting the year to date pay
 	public function get_data_payslip_by_period_NEW($company_id, $emp_id)
 	{
 		$key = konsum_key();
 		$payroll_dates = $this->get_payslips_payperiod($emp_id, $company_id);
 	    $first_day_year = date("Y-01-01");
 	    $today = date("Y-m-d");
 	    $where = array(
 	        "company_id" => $company_id,
 	        "emp_id" => $emp_id
 	    );
 	    $sel = 'SUM(AES_DECRYPT(net_amount, "'.$key.'")) as total';
 	    $this->db->select($sel, false, false);
 	    $this->db->where($where);
 	    if ($payroll_dates) {
 	    	$this->db->where_in("payroll_date", $payroll_dates);
 	    }
 	    //$this->db->where("(pay_date >= '$first_day_year' AND pay_date <= '$today')");
 	    $this->db->order_by("pay_date","desc");
 	    $query = $this->db->get('payroll_payslip');
 	    $result = $query->row();
 	    return ($result) ? $result : false;
 	
 	}
 	
 	public function get_data_payslip_by_period($company_id, $emp_id)
 	{
 		$first_day_year = date("Y-01-01");
 		$today = date("Y-m-d");
 		$where = array(
 			"company_id" => $company_id,
 			"emp_id" => $emp_id
 		);
 		$select = array(
 			"payroll_payslip_id",
 			"pay_date",
 			"period_from",
 			"period_to",
 			"net_amount",
 			"rate"
 		);
 		$this->edb->select($select);
 		$this->db->where($where);
 		$this->db->where("(pay_date >= '$first_day_year' AND pay_date <= '$today')");
 		$this->db->order_by("pay_date","desc");
 		$query = $this->edb->get('payroll_payslip');
 		$result = $query->result();
 		return ($result) ? $result : false;
 	
 	}
 	
 	public function get_payroll_deminimis($emp_id,$draft_pay_run_id,$pay_period,$period_from,$period_to,$company_id){
 		$w = array(
 				"emp_id"=>$emp_id,
 				"period_from"=>$period_from,
 				"period_to"=>$period_to,
 				"payroll_period"=>$pay_period,
 				"company_id"=>$company_id,
 				#"status"=>"Active",
 		);
 		$this->db->where($w);
 		$q = $this->edb->get("payroll_de_minimis");
 		$r = $q->result();
 		return ($r) ? $r : FALSE ;
 	}
 	
 	public function draft_pay_runs_groupby($company_id,$pay_period,$period_from,$period_to){
 		$w = array(
 				"company_id" => $company_id,
 				"pay_period" => $pay_period,
 				"period_from"=>$period_from,
 				"period_to"=> $period_to
 		);
 	
 		$this->db->where($w);
 		$this->db->group_by("draft_pay_run_id");
 		$r = $this->edb->get("draft_pay_runs");
 		$t = $r->result();
 		return ($t) ? $t : FALSE ;
 	}
 	
 	public function get_payroll_earnings_old($emp_id,$draft_pay_run_id,$pay_period,$period_from,$period_to,$company_id){
 		$w = array(
 				"emp_id"=>$emp_id,
 				"period_from"=>$period_from,
 				"period_to"=>$period_to,
 				"payroll_period"=>$pay_period,
 				"company_id"=>$company_id,
 				#"status"=>"Active",
 		);
 		$this->db->where($w);
 		$q = $this->edb->get("payroll_other_earnings_lite");
 		$r = $q->result();
 		return ($r) ? $r : FALSE ;
 	}
 	
	public function get_payroll_earnings($emp_id,$draft_pay_run_id,$pay_period,$period_from,$period_to,$company_id){
 		 $w = array(
            "pol.emp_id"=>$emp_id,
            "pol.period_from"=>$period_from,
            "pol.period_to"=>$period_to,
            "pol.payroll_period"=>$pay_period,
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
        return ($r) ? $r : FALSE ;
 	}
 	
	public function get_draft_payrun_id($emp_id,$pay_period,$period_from,$period_to,$company_id){
 		$where1 = array(
				"prc.company_id" => $company_id,
				"prc.period_from"=>$period_from,
				"prc.period_to"=>$period_to,
				"prc.payroll_period"=>$pay_period,
				"prc.emp_id" =>$emp_id,
				"prc.status"=>"Active",
		);
		$this->db->where($where1);
		$query1 = $this->db->get("payroll_run_custom AS prc");
		if($query1->num_rows() > 0){
			$w = array(
				"prc.company_id"=>$company_id,
				"prc.period_from >="=>$period_from,
				"prc.period_to <="=>$period_to,
				"prc.payroll_period"=>$pay_period,
				"epi.company_id"=>$company_id,
				"epi.status"=>"Active",
				"epi.deleted"=>"0",
				"pp.period_from >="=>$period_from,
				"pp.period_to <="=>$period_to,
				"pp.payroll_date"=>$pay_period,
				"pp.emp_id"=>$emp_id,
			);
			$this->db->select("* , prc.draft_pay_run_id AS pay_run_id_new");
			$this->db->where($w);
			$this->db->group_by("pp.emp_id");
			$this->db->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER");
			$this->db->join("payroll_run_custom AS prc","pp.emp_id = prc.emp_id","INNER");
			$this->db->join("company AS cp","epi.company_id = cp.company_id","INNER");
			$q = $this->edb->get("payroll_payslip AS pp");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}else{
			/* $where = array(
					"dpr.company_id" => $company_id,
					"dpr.period_from"=>$period_from,
					"dpr.period_to"=>$period_to,
					"dpr.pay_period"=>$pay_period,
					"dpr.status"=>"Active",
					"ap.generate_payslip"=>"Yes",
			);
			$this->db->where($where);
			$this->db->join("approval_payroll AS ap","ap.token=dpr.token","INNER");
			$query = $this->db->get("draft_pay_runs AS dpr");
			if($query->num_rows() > 0){
				$row = $query->row();
				$token = $row->token;
				$get_this_pgroup = array(
						"dpr.company_id"=>$company_id,
						"dpr.status"=>"Active",
						"dpr.period_from >="=>$period_from,
						"dpr.period_to <="=>$period_to,
						"dpr.pay_period"=>$pay_period,
						"epi.company_id"=>$company_id,
						"epi.status"=>"Active",
						"epi.deleted"=>"0",
						"pp.emp_id"=>$emp_id,
						"pp.period_from >="=>$period_from,
						"pp.period_to <="=>$period_to,
						"pp.payroll_date"=>$pay_period
				);
				$this->db->select("*");
				$this->db->where($get_this_pgroup);
				$this->db->group_by("pp.emp_id");
				$this->db->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER");
				$this->db->join("draft_pay_runs AS dpr","dpr.payroll_group_id = pp.payroll_group_id","INNER");
				$this->db->join("company AS cp","epi.company_id = cp.company_id","INNER");
				$q = $this->db->get("payroll_payslip AS pp");
				$r = $q->row();
				return ($r) ? $r : FALSE ; */
				
				$get_this_pgroup = array(
						"dpr.company_id"=>$company_id,
						"dpr.status"=>"Active",
						"dpr.period_from >="=>$period_from,
						"dpr.period_to <="=>$period_to,
						"dpr.pay_period"=>$pay_period,
						"epi.company_id"=>$company_id,
						"epi.status"=>"Active",
						"epi.deleted"=>"0",
						"pp.emp_id"=>$emp_id,
						"pp.period_from >="=>$period_from,
						"pp.period_to <="=>$period_to,
						"pp.payroll_date"=>$pay_period,
						"dpr.status"=>"Active",
						"ap.generate_payslip"=>"Yes"
				);
				$this->db->select("*, dpr.draft_pay_run_id AS pay_run_id_new");
				$this->db->where($get_this_pgroup);
				//$this->db->group_by("pp.emp_id");
				$this->db->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER");
				$this->db->join("draft_pay_runs AS dpr","dpr.payroll_group_id = pp.payroll_group_id","INNER");
				$this->db->join("approval_payroll AS ap","ap.token=dpr.token","INNER");
				$this->db->join("company AS cp","epi.company_id = cp.company_id","INNER");
				$q = $this->db->get("payroll_payslip AS pp");
				//last_query();
				$r = $q->row(); 
				return ($r) ? $r : FALSE ;
			}
 	}		
 	
 	public function get_draft_payrun_id_v2($emp_id,$pay_period,$period_from,$period_to,$company_id){
 		$where1 = array(
 				"prc.company_id" => $company_id,
 				"prc.period_from"=>$period_from,
 				"prc.period_to"=>$period_to,
 				"prc.payroll_period"=>$pay_period,
 				"prc.emp_id" =>$emp_id,
 				"prc.status"=>"Active",
 				"ap.generate_payslip"=>"Yes"
 		);
 		$this->db->where($where1);
 		$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id=prc.draft_pay_run_id","INNER");
 		$this->db->join("approval_payroll AS ap","ap.token=dpr.token","INNER");
 		$query1 = $this->db->get("payroll_run_custom AS prc");
 		if($query1->num_rows() > 0){
 	
 			$w = array(
 					"prc.company_id"=>$company_id,
 					"prc.period_from >="=>$period_from,
 					"prc.period_to <="=>$period_to,
 					"prc.payroll_period"=>$pay_period,
 					"epi.company_id"=>$company_id,
 					"epi.status"=>"Active",
 					"epi.deleted"=>"0",
 					"pp.period_from >="=>$period_from,
 					"pp.period_to <="=>$period_to,
 					"pp.payroll_date"=>$pay_period,
 					"e.company_id"=>$company_id,
 					"e.status"=>"Active",
 					"e.deleted"=>"0",
 					"pp.emp_id"=>$emp_id,
 			);
 	
 			$sel = array(
 					"lao.name AS lao_name",
 			);
 			$this->db->select("*");
 			$this->db->select($sel);
 			$this->db->where($w);
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
 			$r = $q->row();
 			return ($r) ? $r : FALSE ;
 		}else{
 			$where = array(
 					"dpr.company_id" => $company_id,
 					"dpr.period_from"=>$period_from,
 					"dpr.period_to"=>$period_to,
 					"dpr.pay_period"=>$pay_period,
 					"dpr.status"=>"Active",
 					"ap.generate_payslip"=>"Yes",
 			);
 			$this->db->where($where);
 			$this->db->join("approval_payroll AS ap","ap.token=dpr.token","INNER");
 			$query = $this->db->get("draft_pay_runs AS dpr");
 			if($query->num_rows() > 0){
 				$row = $query->row();
 				$token = $row->token;
 	
 				$get_this_pgroup = array(
 						"dpr.company_id"=>$company_id,
 						"dpr.status"=>"Active",
 						"dpr.period_from >="=>$period_from,
 						"dpr.period_to <="=>$period_to,
 						"dpr.pay_period"=>$pay_period,
 						"pp.emp_id"=>$emp_id,
 						"epi.company_id"=>$company_id,
 						"epi.status"=>"Active",
 						"epi.deleted"=>"0",
 						"pp.period_from >="=>$period_from,
 						"pp.period_to <="=>$period_to,
 						"pp.payroll_date"=>$pay_period,
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
 				last_query();
 				$r = $q->row();
 				return ($r) ? $r : FALSE ;
 			}
 		}
 	}
 	
 	public function get_wttax_fixed($company_id,$emp_id,$pay_period,$period_from,$period_to){
 		$where = array(
 				"company_id" => $company_id,
 				"emp_id" => $emp_id,
 				"payroll_period" => $pay_period,
 				"period_from" => $period_from,
 				"period_to" => $period_to,
 				"status" => "Active"
 		);
 		$this->db->where($where);
 		$query = $this->db->get('payroll_wttax_fixed');
 		$r = $query->row();
		return ($r) ? $r : FALSE ;
 	}
 	
 	public function check_generated_payslip($pay_period,$period_from,$period_to,$company_id,$emp_id,$payroll_group_id){
 		$w = array(
 				"prc.payroll_period"=>$pay_period,
 				"prc.period_from"=>$period_from,
 				"prc.period_to"=>$period_to,
 				"prc.company_id"=>$company_id,
 				"prc.emp_id"=>$emp_id,
 				"prc.status"=>"Active",
 				"dpr.view_status"=>"Closed",
 				"ap.generate_payslip"=>"Yes"
 		);
 		$this->db->where($w);
 		$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
 		$this->db->join("approval_payroll AS ap","ap.token = dpr.token","LEFT");
 		$q = $this->db->get("payroll_run_custom AS prc");
 		$r = $q->row();
 		#last_query();
 		if($r){
 			return $r;
 		}else{
 			$w = array(
 					"dpr.pay_period"=>$pay_period,
 					"dpr.period_from"=>$period_from,
 					"dpr.period_to"=>$period_to,
 					"dpr.company_id"=>$company_id,
 					"dpr.payroll_group_id"=>$payroll_group_id,
 					"dpr.status"=>"Active",
 					"dpr.view_status"=>"Closed",
 					"ap.generate_payslip"=>"Yes"
 			);
 	
 			$this->db->where($w);
 			$this->db->join("approval_payroll AS ap","ap.token = dpr.token","LEFT");
 			$q = $this->db->get("draft_pay_runs AS dpr");
 			$r = $q->row();
 			return ($r) ? $r : FALSE ;
 		}
 			
 		#return ($q->num_rows() > 0) ? TRUE : FALSE ;
 	}
 	
 	public function get_other_deduction($emp_id,$draft_pay_run_id,$pay_period,$period_from,$period_to,$company_id){//this is for ashima engage
 		$w = array(
 				"emp_id"=>$emp_id,
 				"company_id"=>$company_id,
 				"period_from"=>$period_from,
 				"period_to"=>$period_to,
 				"payroll_period"=>$pay_period,
 				"status"=>"Active",
 		);
 		$this->db->where($w);
 		$q = $this->db->get("payroll_for_other_deductions");
 		$r = $q->result();
 		return ($r) ? $r : FALSE ;
 	}
 	
 	public function get_working_days_settings($company_id){
 		$where = array(
 				"company_id" => $company_id
 		);
 		$this->db->where($where);
 		$query = $this->db->get("payroll_calendar_working_days_settings");
 		$row = $query->row();
 	
 		return ($row) ? $row : false;
 	}
 	
 	public function get_basic_pay($emp_id,$comp_id){
 		$where = array(
 				"emp_id" => $emp_id,
 				"comp_id" => $comp_id
 		);
 		$this->edb->where($where);
 		$query = $this->edb->get("basic_pay_adjustment");
 		$row = $query->row();
 	
 		return ($row) ? $row : false;
 	}
 	
 	public function get_payroll_payslip_emp_id($pp_id,$comp_id){
 		$where = array(
 				"payroll_payslip_id" => $pp_id,
 				"company_id" => $comp_id
 		);
 		$this->edb->where($where);
 		$query = $this->edb->get("payroll_payslip");
 		$row = $query->result();
 	
 		return ($row) ? $row : false;
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
 	
 	public function filter_payroll_history_v2($emp_id,$comp_id,$from,$to){
 		$w2 = array(
 				"pp.emp_id"=>$emp_id,
 				"ap.comp_id"=>$comp_id,
 				"ap.approve_by_head"=>"Yes",
 				"ap.payroll_status"=>"approved",
 				"ap.generate_payslip"=>"Yes",
 				"ap.status"=>"Active",
 				"ap.period_from" => $from,
 				"ap.period_to" => $to
 		);
 		$this->edb->where($w2);
 		$this->edb->join("payroll_payslip AS pp","ap.payroll_period = pp.payroll_date","LEFT");
 		$sql2 = $this->edb->get("approval_payroll AS ap");
 		$result = $sql2->result();
 		
 		return ($result) ? $result : false ;
 	}
 	
 	public function get_payslips_payperiod($emp_id, $company_id){
 		$arrs = array();
 	
 		// PAYROLL CUSTOM
 		$w = array(
 			"prc.company_id" => $company_id,
 			"prc.emp_id" => $emp_id,
 			"prc.status" => "Active",
 			"ap.generate_payslip" => "Yes"
 		);
 		$this->db->where($w);
 		$this->db->where("dpr.view_status = 'Closed'");
 		$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
 		$this->db->join("approval_payroll AS ap","ap.token = dpr.token","LEFT");
 		$q = $this->db->get("payroll_run_custom AS prc");
 		$r = $q->result();
 	
 		if($r){
 			foreach($r as $row){
 				array_push($arrs, $row->payroll_period);
 			}
 		}
 	
 		// BY PAYROLL GROUP
 		$w = array(
 			"dpr.company_id" => $company_id,
 			"pp.emp_id" => $emp_id,
 			"dpr.status" => "Active",
 			"ap.generate_payslip" => "Yes"
 		);
 		$this->db->where($w);
 		$this->db->where("dpr.view_status = 'Closed'");
 		$this->db->join("draft_pay_runs AS dpr","pp.payroll_group_id = dpr.payroll_group_id && dpr.pay_period = pp.payroll_date","LEFT");
 		$this->db->join("approval_payroll AS ap","ap.token = dpr.token","LEFT");
 		$q = $this->db->get("payroll_payslip AS pp");
 		$r = $q->result();
 	
 		if($r){
 			foreach($r as $row){
 				array_push($arrs, $row->payroll_date);
 			}
 		}
 		return $arrs;
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
}