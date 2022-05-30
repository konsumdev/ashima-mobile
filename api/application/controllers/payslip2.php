<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Payslip extends CI_Controller{
	var $verify;
		
	public function __construct(){
	   parent::__construct();	
	   $this->load->model('konsumglobal_jmodel','jmodel');
	   $this->load->model('employee_model','employee');
	   $this->load->model('approval_model','approval');
	   $this->load->model('approval_group_model','agm');
	   $this->load->model('payroll_run_model','prm');
	   $this->load->model('employee_mobile_model','mobile');
	   $this->load->model('payroll_payslip_model_2', 'pp');
	   $this->load->model('payroll_payslip_model_3', 'pp3');
	   $this->load->model('payroll_run_model','prm');
	   $this->load->model('payroll_deductions_model','dcm');
	   $this->load->model("hoursworked_model","hwm");
	   $this->load->model('employee_pay_slip_model','epsm');
	  // $this->company_info = whose_company();
	  
	   $this->emp_id = $this->session->userdata('emp_id');
	   $this->company_id =$this->employee->check_company_id($this->emp_id);  
	   
	}	
	public function index(){
		
		#$year = date("Y");
		#@$year_selected = $this->input->post('theYear');
		#if($year_selected !="") $year = $year_selected;
		$page = $this->input->post('page');
		$limit = $this->input->post('limit');
		
		$this->per_page = 10;
		
		$get_data_payslip = $this->mobile->get_data_payslip($this->company_id,$this->emp_id,false,(($page-1) * $this->per_page),$limit);
		$total = ceil($this->mobile->get_data_payslip($this->company_id,$this->emp_id,true) / 10);
		
		$payroll = $this->hwm->get_payroll_period($this->company_id);
		
		$employee_total_loan_amount = 0;
		$adjustment_total_amount = 0;
		
		$payslip_res = array();
		$res = array();
		#if($get_data_payslip != null && $payroll){
		if($get_data_payslip != null){
			//$get_current_payslip ako nlng ni ggamit pra d nako ma replace hahaha
			foreach($get_data_payslip as $get_current_payslip){
				$payroll_run_details = $this->employee->payroll_run_details_where($this->emp_id,$this->company_id,$get_current_payslip->payroll_payslip_id);
				
				$pay_period = $payroll_run_details->payroll_date;
				$period_from = $payroll_run_details->period_from;
				$period_to = $payroll_run_details->period_to;			
				$payroll_date = $get_current_payslip->payroll_date;
				$generate_printable_payslip = $this->employee->generate_printable_payslip($get_current_payslip->payroll_payslip_id,$pay_period,$period_from,$period_to,$this->company_id);
				
				$draft_pay_run = $this->epsm->draft_pay_runs_groupby($this->company_id,$pay_period,$period_from,$period_to);
				$draft_pay_run_id = $draft_pay_run;
				$draft_pay_run_id_new = '';
				if($draft_pay_run_id){
					foreach ($draft_pay_run_id as $wew){
						$draft_pay_run_id_new = $wew->draft_pay_run_id;
					}
				}
			
				// EMPLOYEE PAYROLL INFORMATION
				$check_employee_payroll_information = $this->prm->check_employee_payroll_information($this->emp_id,$this->company_id);
				
				if($check_employee_payroll_information != FALSE){
						
					// EMPLOYEE PAYROLL GROUP ID
					$employee_payroll_group_id = $check_employee_payroll_information->payroll_group_id;
						
					// PERIOD TYPE
					$employee_period_type = $check_employee_payroll_information->period_type;
						
					// PAY RATE TYPE
					$employee_pay_rate_type = $check_employee_payroll_information->pay_rate_type;
						
				}
				
				// EMPLOYEE HOURLY RATE
				$emp_hourly_rate = $this->prm->new_hourly_rate($this->company_id,$this->emp_id,$employee_payroll_group_id,$payroll);
				
				$q1 = $this->dcm->get_payroll_calendar($this->company_id,$pay_period,$employee_payroll_group_id);
				
				// get adjustment (start here)
				$param_data = array(
						"payroll_period"=> $pay_period,
						"period_from"=> $period_from,
						"period_to"=> $period_to
				);
				$adjustment_total_workday = 0;
				$adjustment_workday1 = $this->prm->adjustment_workday($this->emp_id,$this->company_id,$param_data);
				$adj_workday = '0';
				if($adjustment_workday1 != FALSE){
					foreach($adjustment_workday1 as $row_awday1){
						$adjustment_total_workday += $row_awday1->amount;
					}
				
					if($adjustment_total_workday > 0) {
						$sub2 = '0';
				
						$adjustment_workday = $this->prm->adjustment_workday($this->emp_id,$this->company_id,$param_data);
						if($adjustment_workday != FALSE){
							foreach($adjustment_workday as $row_awday){
								$adjustment_total_amount += $$row_awday->amount;
								$sub2 = '1';
							}
						}
						$adj_workday = array(
								"workday" => $sub2,
								"amount" => number_format($adjustment_total_workday,2)
						);
							
					}
				}
				
				$adjustment_total_advance_payment1 = 0;
				$adjustment_advance_payment1 = $this->prm->adjustment_advance_payment($this->emp_id,$this->company_id,$param_data);
				$advance_payment = '0';
				if($adjustment_advance_payment1 != FALSE){
					foreach($adjustment_advance_payment1 as $row1){
						$adjustment_total_advance_payment1 += $row1->amount;
					}
					if($adjustment_total_advance_payment1 > 0) {
						$sub2 = '0';
				
						$adjustment_advance_payment = $this->prm->adjustment_advance_payment($this->emp_id,$this->company_id,$param_data);
						if($adjustment_advance_payment != FALSE){
							foreach($adjustment_advance_payment as $row){
								$adjustment_total_amount += $row->amount;
								$sub2 = '1';
				
							}
						}
				
						$advance_payment = array(
								"advance_payment" => $sub2,
								"amount" => number_format($adjustment_total_advance_payment1,2)
						);
				
					}
				}
				
				$adjustment_total_overtime1 = 0;
				$adjustment_overtime1 = $this->prm->adjustment_overtime($this->emp_id,$this->company_id,$param_data);
				$emp_ot = '0';
				if($adjustment_overtime1){
					foreach($adjustment_overtime1 as $row_ao1){
						$adjustment_total_overtime1 += $row_ao1->amount;
					}
						
					if($adjustment_total_overtime1 > 0) {
						$sub2 = '0';
						$adjustment_overtime = $this->prm->adjustment_overtime($this->emp_id,$this->company_id,$param_data);
						if($adjustment_overtime != FALSE){
							foreach($adjustment_overtime as $row_ao){
								$adjustment_total_amount += $row_ao->amount;
								$ao_date = ($row_ao->date != "") ? date("m/d/Y",strtotime($row_ao->date)) : "" ;
								$sub2 = "1";
								 
							}
						}
						
						$emp_ot = array(
								"employee_overtime" => $sub2,
								"amount" => number_format($adjustment_total_overtime1,2)
						);
					}
				}
				
				$adjustment_total_holiday1 = 0;
				$adjustment_holiday1 = $this->prm->adjustment_holiday($this->emp_id,$this->company_id,$param_data);
				$hol_prem = '0';
				if($adjustment_holiday1){
					foreach($adjustment_holiday1 as $row1){
						$adjustment_total_holiday1 += $row1->amount;
					}
					if($adjustment_total_holiday1 > 0) {
						$sub2 = '0';
				
						$adjustment_holiday = $this->prm->adjustment_holiday($this->emp_id,$this->company_id,$param_data);
						if($adjustment_holiday != FALSE){
							foreach($adjustment_holiday as $row){
								$adjustment_total_amount += $row->amount;
								$ao_date = ($row->date != "") ? date("m/d/Y",strtotime($row->date)) : "" ;
								$sub2 = "1";
				
							}
						}
						
						$hol_prem = array(
								"employee_holiday" => $sub2,
								"amount" => number_format($adjustment_total_holiday1,2)
						);
					}
				}
				
				$adjustment_total_night_differential1 = 0;
				$adjustment_night_differential1 = $this->prm->adjustment_night_differential($this->emp_id,$this->company_id,$param_data);
				$nyt_diff = '0';
				if($adjustment_night_differential1){
					foreach($adjustment_night_differential1 as $row1){
						$adjustment_total_amount += $row1->amount;
						$adjustment_total_night_differential1 += $row1->amount;
					}
				
					if($adjustment_total_night_differential1 > 0) {
						$sub2 = "0";
						
						$adjustment_night_differential = $this->prm->adjustment_night_differential($this->emp_id,$this->company_id,$param_data);
						$adjustment_night_differential_no_holiday_rate = $this->prm->adjustment_night_differential_no_holiday_rate($this->emp_id,$this->company_id,$param_data);
						if($adjustment_night_differential != FALSE){
							foreach($adjustment_night_differential as $row){
								$ao_date = ($row->date != "") ? date("m/d/Y",strtotime($row->date)) : "" ;
								#$sub2 = $row->hours_type_name;
								$sub2 = '1';
							}
						}else if($adjustment_night_differential_no_holiday_rate != FALSE){
							foreach($adjustment_night_differential_no_holiday_rate as $row){
								$ao_date = ($row->date != "") ? date("m/d/Y",strtotime($row->date)) : "" ;
								#$sub2 = "Employee Night Differential";
								$sub2 = '1';
							}
						}
						
						$nyt_diff = array(
								"employee_night_differential" => $sub2,
								"amount" => number_format($adjustment_total_night_differential1,2)
						);
					}
				}
				
				
				$adjustment_total_paid_leave1 = 0;
				$adjustment_paid_leave1 = $this->prm->adjustment_paid_leave($this->emp_id,$this->company_id,$param_data);
				$emp_paid_leave = '0';
				if($adjustment_paid_leave1){
					foreach($adjustment_paid_leave1 as $row1){
						$adjustment_total_paid_leave1 += $row1->amount;
					}
					if($adjustment_total_paid_leave1 > 0) {
						$sub2 = '1';
				
						$adjustment_paid_leave = $this->prm->adjustment_paid_leave($this->emp_id,$this->company_id,$param_data);
						if($adjustment_paid_leave != FALSE){
							foreach($adjustment_paid_leave as $row){
								$adjustment_total_amount += $row->amount;
								$ao_date = date("m/d/Y",strtotime($row->date));
								$sub2 = "1";
							}
						}
						
						$emp_paid_leave = array(
								"employee_paid_leave" => $sub2,
								"amount" => number_format($adjustment_total_paid_leave1,2)
						);
					}
				}
				
				$adjustment_total_allowances1 = 0;
				$adjustment_allowances1 = $this->prm->adjustment_allowances($this->emp_id,$this->company_id,$param_data);
				$adj_allow = '0';
				if($adjustment_allowances1) {
					foreach($adjustment_allowances1 as $row1){
						$adjustment_total_allowances1 += $row1->allowances_amount;
					}
				
					if($adjustment_total_allowances1 > 0) {
						$sub2 = "0";
						$adjustment_allowances = $this->prm->adjustment_allowances($this->emp_id,$this->company_id,$param_data);
						if($adjustment_allowances != FALSE){
							foreach($adjustment_allowances as $row){
								$adjustment_total_amount += $row->amount;
								$sub2 = "1";
							}
						}
						
						$adj_allow = array(
								"allowances" => $sub2,
								"amount" => number_format($adjustment_total_allowances1,2)
						);
					}
				}
				
				$adjustment_total_commission1 = 0;
				$adjustment_commission1 = $this->prm->adjustment_commission($this->emp_id,$this->company_id,$param_data);
				$adj_comm = '0';
				
				if($adjustment_commission1){
					foreach($adjustment_commission1 as $row1){
						$adjustment_total_commission1 += $row1->earnings_amount;
					}
					if($adjustment_total_commission1 > 0){
						$sub2 = "0";
				
						$adjustment_commission = $this->prm->adjustment_commission($this->emp_id,$this->company_id,$param_data);
						if($adjustment_commission != FALSE){
							foreach($adjustment_commission as $row){
								$adjustment_total_amount += $row->amount;
								$sub2 = "1";
							}
						}
				
						$adj_comm = array(
								"commission" => $sub2,
								"amount" => number_format($adjustment_total_commission1,2)
						);
					}
				}
				
				$adjustment_total_deminimis1 = 0;
				$adjustment_deminimis1 = $this->prm->adjustment_deminimis($this->emp_id,$this->company_id,$param_data);
				$adj_deminimis = '0';
				if($adjustment_deminimis1 != FALSE){
					foreach($adjustment_deminimis1 as $row1){
						$adjustment_total_deminimis1 += $row1->de_minimis_amount;
					}
					if($adjustment_total_deminimis1 > 0) {
						$sub2 = "0";
				
						$adjustment_deminimis = $this->prm->adjustment_deminimis($this->emp_id,$this->company_id,$param_data);
						if($adjustment_deminimis != FALSE){
							foreach($adjustment_deminimis as $row){
								$adjustment_total_amount += $row->amount;
								$sub2 = "1";
							}
						}
						
						$adj_deminimis = array(
								"de_minimis" => $sub2,
								"amount" => number_format($adjustment_total_deminimis1,2)
						);
					}
				}
				
				$adjustment_service_charge_amount1 = 0;
				$adjustment_service_charge1 = $this->prm->adjustment_service_charge($this->emp_id,$this->company_id,$param_data);
				$adj_serv_charge = '0';
				if($adjustment_service_charge1){
					foreach($adjustment_service_charge1 as $row1){
						$adjustment_service_charge_amount1 += $row1->amount;
					}
					if($adjustment_service_charge_amount1 > 0) {
						$sub2 = "0";
				
						$adjustment_service_charge = $this->prm->adjustment_service_charge($this->emp_id,$this->company_id,$param_data);
						if($adjustment_service_charge != FALSE){
							foreach($adjustment_service_charge as $row){
								$adjustment_total_amount += $row->amount;
								$sub2 = "1";
							}
						}
						
						$adj_serv_charge = array(
								"service_charge" => $sub2,
								"amount" => number_format($adjustment_service_charge_amount1,2)
						);
					}
				}
				
				$adjustment_hazard_pay_amount1 = 0;
				$adjustment_hazard_pay1 = $this->prm->adjustment_hazard_pay($this->emp_id,$this->company_id,$param_data);
				$adj_hazard = '0';
				if($adjustment_hazard_pay1 != FALSE){
					foreach($adjustment_hazard_pay_amount1 as $row1){
						$adjustment_hazard_pay_amount1 += $row1->amount;
					}
					if($adjustment_hazard_pay_amount1 > 0) {
						$sub2 = "0";
				
						$adjustment_hazard_pay = $this->prm->adjustment_hazard_pay($this->emp_id,$this->company_id,$param_data);
						if($adjustment_hazard_pay != FALSE){
							foreach($adjustment_hazard_pay as $row){
								$adjustment_total_amount += $row->amount;
								$sub2 = "1";
							}
						}
						
						$adj_hazard = array(
								"hazard_pay" => $sub2,
								"amount" => number_format($adjustment_hazard_pay_amount1,2)
						);
					}
				}
				
				$adjustment_other_earnings_amount1 = 0;
				$adjustment_other_earnings1 = $this->prm->adjustment_other_earnings($this->emp_id,$this->company_id,$param_data);
				$adj_other = '0';
				if($adjustment_other_earnings1 != FALSE){
					foreach($adjustment_other_earnings1 as $row_ashlite_ad1){
						$adjustment_other_earnings_amount1 += $row_ashlite_ad1->amount;
					}
					if($adjustment_other_earnings_amount1 > 0) {
						$sub2 = "0";
				
						$adjustment_other_earnings = $this->prm->adjustment_other_earnings($this->emp_id,$this->company_id,$param_data);
						if($adjustment_other_earnings != FALSE){
							foreach($adjustment_other_earnings as $row_ashlite_ad){
								$adjustment_total_amount += $row_ashlite_ad->amount;
								$sub2 = "1";
							}
						}
						
						$adj_other = array(
								"other_earnings" => $sub2,
								"amount" => number_format($adjustment_other_earnings_amount1,2)
						);
					}
				}
				
				$adjustment_total_amount = round($adjustment_total_amount,2);
				
				// get adjustment (end here)
				
				// get allowances (start here)
	
				// FIXED ALLOWANCES
				$allowances_flag_one_time_or_annualy = FALSE;
				$employee_allowances_to_display = 0;
				$remaining_taxable_allowances = 0;
				$allowance_max_non_taxable_income = 0;
				$total_fixed_allowances = 0; // taxable
				$total_fixed_allowances_nt = 0; // non taxable
				
				$allowances_settings = $this->prm->allowances_settings($this->company_id);
				
				$your_allowances_name = array();
				if($allowances_settings != FALSE){
					foreach($allowances_settings as $row_allowances_settings){
						$get_fixed_allowances = $this->prm->get_fixed_allowances($this->emp_id,$this->company_id,$row_allowances_settings->allowance_settings_id);
							
						if($get_fixed_allowances != FALSE){
							foreach($get_fixed_allowances as $row_fa){
								$allowance_name = $row_fa->name;
							}
							
							array_push($your_allowances_name,$allowance_name);
						}
					}
				
				}
				
				$your_allowances_amount = array();
				if($allowances_settings != FALSE){
					foreach($allowances_settings as $row_allowances_settings){
							
						$get_fixed_allowances = $this->prm->get_fixed_allowances($this->emp_id,$this->company_id,$row_allowances_settings->allowance_settings_id);
							
						if($get_fixed_allowances != FALSE){
				
							foreach($get_fixed_allowances as $row_fa){
									
								$fixed_allowance_amount = $row_fa->allowance_amount;
								$pay_out_schedule = $row_fa->pay_out_schedule;
								$check_taxable = $row_fa->taxable;
								$maximum_non_taxable_amount = $row_fa->maximum_non_taxable_amount;
									
								$allowance_variability_type = $row_fa->variability_type;
								$allowance_frequency = $row_fa->frequency;
								$allowance_daily_rate = $row_fa->daily_rate;
								$allowance_hourly_rate = $row_fa->hourly_rate;
								$allowance_applicable_daily_rates = $row_fa->applicable_daily_rates;
								$allowance_applicable_daily_rates_value = 0;
								$employee_entitled_to_allowance_for_absent = $row_fa->employee_entitled_to_allowance_for_absent;
									
								$start = $period_from;
								$end = $period_to;
								$check_employee_time_in = $this->prm->new_check_employee_time_in($this->company_id,$this->emp_id,$start,$end);
									#p($check_employee_time_in);
								// APPLICABLE TO DAILY RATES
								switch ($allowance_applicable_daily_rates) {
										
									case 'Regular':
										$check_daily_rate = $this->prm->check_daily_rate($this->company_id,"Regular");
										if($check_daily_rate != FALSE) $allowance_applicable_daily_rates_value = $check_daily_rate->pay_rate;
										break;
											
									case 'Special':
										$check_daily_rate = $this->prm->check_daily_rate($this->company_id,"Special Day");
										if($check_daily_rate != FALSE) $allowance_applicable_daily_rates_value = $check_daily_rate->pay_rate;
										break;
				
									case 'Legal':
										$check_daily_rate = $this->prm->check_daily_rate($this->company_id,"Holiday");
										if($check_daily_rate != FALSE) $allowance_applicable_daily_rates_value = $check_daily_rate->pay_rate;
										break;
											
									case 'Double Legal':
										$check_daily_rate = $this->prm->check_daily_rate($this->company_id,"Double Holiday");
										if($check_daily_rate != FALSE) $allowance_applicable_daily_rates_value = $check_daily_rate->pay_rate;
										break;
											
								}
									
								if (($pay_out_schedule == 'first payroll' || $pay_out_schedule == 'per payroll') && $q1['period'] == 1) {
									$flag_allowances = TRUE;
								} elseif (($pay_out_schedule == 'second payroll' || $pay_out_schedule == 'per payroll') && $q1['period'] == 2) {
									$flag_allowances = TRUE;
				
									// FOR FORTNIGHTLY
								} elseif (
										($pay_out_schedule == 'per payroll' && $employee_period_type == "Fortnightly")
										||
										($pay_out_schedule == 'last payroll' && $check_fortnightly_last_payroll != FALSE && $employee_period_type == "Fortnightly")
								) {
									$flag_allowances = TRUE;
				
								} else {
									$flag_allowances = FALSE;
								}
									
								if($flag_allowances){
				
									// VARIABLITY
									switch ($allowance_variability_type) {
				
										case 'no, just flat rate': // no, just flat rate
											$fixed_allowance_amount_paramater = $fixed_allowance_amount;
											break;
				
										case 'yes, daily rate percentage': // yes, daily rate percentage
											$fixed_allowance_amount_paramater = ($emp_hourly_rate * 8) * ($allowance_daily_rate / 100) * ($allowance_applicable_daily_rates_value / 100);
											break;
												
										case 'yes, hourly rate percentage': // yes, hourly rate percentage
											$fixed_allowance_amount_paramater = $emp_hourly_rate * ($allowance_hourly_rate / 100) * ($allowance_applicable_daily_rates_value / 100);
											break;
				
										default;
										$fixed_allowance_amount_paramater = 0;
										break;
				
									}
				
									$allowance_value_paramater = 0;
									$allow_total_absent = 0;
				
									// FREQUENCY
									switch ($allowance_frequency) {
				
										case 'per worked day':
											$timecounter = 0;
				
											if($employee_entitled_to_allowance_for_absent == "no"){ // employee not entitled to allowance for absent
												if($check_employee_time_in != FALSE){
													foreach($check_employee_time_in as $row_all_timein){
														$timecounter++;
													}
												}
											}else if($employee_entitled_to_allowance_for_absent == "yes"){ // employee entitled to allowance for absent
													
												$start = $period_from;
												$end = $period_to;
												$current = $start;
												while ($current <= $end) {
														
													/* CHECK WORK SCHEDULE */
													$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$current);
													$workday = $this->prm->get_workday($this->company_id,$work_schedule_id->work_schedule_id);
													if($workday != FALSE){
														// get weekday
														$weekday = date('l',strtotime($current));
														$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
				
														if(!$rest_day) $timecounter++;
													}
														
													$current = date('Y-m-d',strtotime($current.' +1 day'));
												}
													
											}
												
											$allowance_value_paramater = $timecounter;
											break;
				
										case 'per week':
											$timecounter = 0;
				
											if($employee_entitled_to_allowance_for_absent == "no"){ // employee not entitled to allowance for absent
												if($check_employee_time_in != FALSE){
													foreach($check_employee_time_in as $row_all_timein){
														$timecounter++;
													}
												}
											}else if($employee_entitled_to_allowance_for_absent == "yes"){ // employee entitled to allowance for absent
				
												$start = $period_from;
												$end = $period_to;
												$current = $start;
												while ($current <= $end) {
														
													/* CHECK WORK SCHEDULE */
													$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$current);
													$workday = $this->prm->get_workday($this->company_id,$work_schedule_id->work_schedule_id);
													if($workday != FALSE){
														// get weekday
														$weekday = date('l',strtotime($current));
														$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
				
														if(!$rest_day) $timecounter++;
													}
														
													$current = date('Y-m-d',strtotime($current.' +1 day'));
												}
				
											}
												
											$allowance_value_paramater = ($timecounter % 7); // 7 default
											break;
												
										case 'twice a month':
				
											$all_abs_cnt = 0;
											$twice_a_month_cnt = 0;
											$start = $period_from;
											$end = $period_to;
											$current = $start;
											while ($current <= $end) {
				
												/* CHECK WORK SCHEDULE */
												$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$current);
												$workday = $this->prm->get_workday($this->company_id,$work_schedule_id->work_schedule_id);
												if($workday != FALSE){
													// get weekday
													$weekday = date('l',strtotime($current));
													$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
				
													$allow_timein = $this->prm->time_in_regular_days($this->company_id,$this->emp_id,$current);
				
													// COUNTER
													if(!$rest_day) $twice_a_month_cnt++;
				
													// TOTAL ABSENCES FOR ALLOWANCES
													if(!$rest_day && !$allow_timein && $employee_entitled_to_allowance_for_absent == "no") $all_abs_cnt++;
												}
													
												$current = date('Y-m-d',strtotime($current.' +1 day'));
											}
				
											// $allow_total_absent = ($fixed_allowance_amount_paramater / $twice_a_month_cnt) * $all_abs_cnt;
				
											if($twice_a_month_cnt > 0){
												$allow_total_absent = ($fixed_allowance_amount_paramater / $twice_a_month_cnt) * $all_abs_cnt;
											}else{
												$allow_total_absent = 0;
											}
				
											$allowance_value_paramater = 1;
											break;
				
										case 'once a month':
											// check employee allowance
											$check_employee_allowance_once_a_month = $this->prm->check_employee_allowance_once_a_month($this->emp_id,($payroll) ? $payroll->payroll_period : "",$this->company_id);
											if(!$check_employee_allowance_once_a_month){
												// CURRENT PAY PERIOD
												$all_abs_cnt = 0;
												$twice_a_month_cnt = 0;
												$start = $period_from;
												$end = $period_to;
												$current = $start;
												while ($current <= $end) {
														
													/* CHECK WORK SCHEDULE */
													$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$current);
													$workday = $this->prm->get_workday($this->company_id,$work_schedule_id->work_schedule_id);
													if($workday != FALSE){
														// get weekday
														$weekday = date('l',strtotime($current));
														$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
				
														$allow_timein = $this->prm->time_in_regular_days($this->company_id,$this->emp_id,$current);
				
														// COUNTER
														if(!$rest_day) $twice_a_month_cnt++;
				
														// TOTAL ABSENCES FOR ALLOWANCES
														if(!$rest_day && !$allow_timein && $employee_entitled_to_allowance_for_absent == "no") $all_abs_cnt++;
													}
														
													$current = date('Y-m-d',strtotime($current.' +1 day'));
												}
				
												// PREVIOUS PAY PERIOD
												$check_previous_payroll_period = $this->prm->previous_payroll_period($employee_payroll_group_id,$this->company_id,$period_to);
												if($check_previous_payroll_period != FALSE){
													$allowance_cut_off_from = $check_previous_payroll_period->cut_off_from;
													$allowance_cut_off_to = $check_previous_payroll_period->cut_off_to;
													while ($allowance_cut_off_from <= $allowance_cut_off_to) {
															
														/* CHECK WORK SCHEDULE */
														$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$allowance_cut_off_from);
														$workday = $this->prm->get_workday($this->company_id,$work_schedule_id->work_schedule_id);
														if($workday != FALSE){
															// get weekday
															$weekday = date('l',strtotime($allowance_cut_off_from));
															$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
																
															$allow_timein = $this->prm->time_in_regular_days($this->company_id,$this->emp_id,$allowance_cut_off_from);
																
															// COUNTER
															if(!$rest_day) $twice_a_month_cnt++;
																
															// TOTAL ABSENCES FOR ALLOWANCES
															if(!$rest_day && !$allow_timein && $employee_entitled_to_allowance_for_absent == "no") $all_abs_cnt++;
														}
															
														$allowance_cut_off_from = date('Y-m-d',strtotime($allowance_cut_off_from.' +1 day'));
													}
												}
													
												// $allow_total_absent = ($fixed_allowance_amount_paramater / $twice_a_month_cnt) * $all_abs_cnt;
													
												if($twice_a_month_cnt > 0){
													$allow_total_absent = ($fixed_allowance_amount_paramater / $twice_a_month_cnt) * $all_abs_cnt;
												}else{
													$allow_total_absent = 0;
												}
													
												$allowance_value_paramater = 1;
											}
											break;
												
										case 'one time':
											// check allowance id
											$check_employee_allowances = $this->prm->check_employee_allowances($this->company_id,$this->emp_id,$row_allowances_settings->allowance_settings_id,$param_data);
											if($check_employee_allowances == 0){
												$all_abs_cnt = 0;
												$twice_a_month_cnt = 0;
												$start = $period_from;
												$end = $period_to;
												$current = $start;
												while ($current <= $end) {
														
													/* CHECK WORK SCHEDULE */
													$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$current);
													$workday = $this->prm->get_workday($this->company_id,$work_schedule_id->work_schedule_id);
													if($workday != FALSE){
														// get weekday
														$weekday = date('l',strtotime($current));
														$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
															
														$allow_timein = $this->prm->time_in_regular_days($this->company_id,$this->emp_id,$current);
															
														// COUNTER
														if(!$rest_day) $twice_a_month_cnt++;
															
														// TOTAL ABSENCES FOR ALLOWANCES
														if(!$rest_day && !$allow_timein && $employee_entitled_to_allowance_for_absent == "no") $all_abs_cnt++;
													}
				
													$current = date('Y-m-d',strtotime($current.' +1 day'));
												}
													
												// $allow_total_absent = ($fixed_allowance_amount_paramater / $twice_a_month_cnt) * $all_abs_cnt;
													
												if($twice_a_month_cnt > 0){
													$allow_total_absent = ($fixed_allowance_amount_paramater / $twice_a_month_cnt) * $all_abs_cnt;
												}else{
													$allow_total_absent = 0;
												}
													
												$allowance_value_paramater = 1;
													
												$allowances_flag_one_time_or_annualy = TRUE;
											}
											break;
				
										default;
										$allowance_value_paramater = 0;
										break;
				
									}
				
									// taxable allowances
									// if($row_fa->tax == "Not-Exempt"){ // taxable
									if($row_allowances_settings->tax == "Not-Exempt"){ // taxable
										$remaining_taxable_allowances += ($fixed_allowance_amount_paramater * $allowance_value_paramater) - $allow_total_absent;
										$total_fixed_allowances += ($fixed_allowance_amount_paramater * $allowance_value_paramater) - $allow_total_absent;
									}else{ // non taxable allowances
										$total_fixed_allowances_nt += ($fixed_allowance_amount_paramater * $allowance_value_paramater) - $allow_total_absent;
									}
				
									// employee allowances to disply
									$employee_allowances_to_display = ($fixed_allowance_amount_paramater * $allowance_value_paramater) - $allow_total_absent;
				
									}else{
										$employee_allowances_to_display = 0;
									}
				
									// TOTAL FIXED ALLOWANCES
									#print "<li>".number_format($employee_allowances_to_display,2)."</li>";
										
									/* // taxable allowances
									 $remaining_taxable_allowances += $fixed_allowance_amount;
									 total_fixed_allowances += $fixed_allowance_amount; */
									$allowance_amount = number_format($employee_allowances_to_display,2);
								}
								
								array_push($your_allowances_amount,$allowance_amount);
							}
						}
					}
					
					$your_allowances = array();
					foreach($your_allowances_name as $key=>$val){
						$allow = array($val,$your_allowances_amount[$key]);
						array_push($your_allowances, (object) $allow);
					}
					
					$total_employee_allowances = $total_fixed_allowances + $total_fixed_allowances_nt;
				
				// get allowances (end here)
				
				// get de minimis (start here)
				// EXCESS OF DE MINIMIS
				$excess_deminimis = $this->prm->excess_deminimis($this->company_id);
				$you_deminimis = array();
				$total_amount_de_minimis = 0;
				if($excess_deminimis != FALSE){
					foreach($excess_deminimis as $row_ex){
						// CHECK EMPLOYEE DE MINIMIS
						$employee_dm_amount = 0;
						$check_employee_dm = $this->prm->check_employee_deminimis($this->emp_id,$row_ex->deminimis_id,$this->company_id,$param_data);
						if($check_employee_dm !=  FALSE){
							foreach($check_employee_dm as $row_exc){
								$employee_dm_amount += $row_exc->excess + $row_exc->amount;
				
								if($row_exc->rules == "Add to Non Taxable Income") {
									/*print "<li>{$row_exc->description}</li>";
									print "<li>- Excess: {$row_exc->description}</li>";
									print "<li>".number_format($row_exc->amount,2)."</li>";
									print "<li>".number_format($row_exc->excess,2)."</li>";*/
									
									$total_amount_de_minimis += $row_exc->excess + $row_exc->amount;
									
									$deminimis_arr = array(
											"description" => $row_exc->description,
											"amount" => number_format($row_exc->amount,2),
											"excess_amount" => number_format($row_exc->excess,2)
									);
									
									array_push($you_deminimis,$deminimis_arr);
									
								}
							}
							#array_push($you_deminimis,$deminimis_arr);
						}
					}
				}
				
				// get de minimis (end here)
				
				// get commission (start here)
				$commission_flag_one_time = FALSE;
				$total_employee_commission = 0;
				
				$employee_commission = $this->prm->employee_commissions($this->emp_id,$this->company_id);
				$your_commission_name = array();
				if($employee_commission != FALSE){
					foreach($employee_commission as $row_commission){
						#print "<li>{$row_commission->commission_plan}</li>";
						array_push($your_commission_name,$row_commission->commission_plan); 
					}
				}
				
				// COMMISSIONS
				$employee_commission = $this->prm->employee_commissions($this->emp_id,$this->company_id);
					
				$your_commission_amount = array();
				if($employee_commission != FALSE){
					foreach($employee_commission as $row_commission){
						$commission = 0;
						
						// variable fields
						$commission_schedule = $row_commission->commission_schedule;
						$commission_pay_schedule = $row_commission->pay_schedule;
						$commission_schedule_date = $row_commission->schedule_date;
						$commission_scheme = $row_commission->commission_scheme;
						$commission_amount = $row_commission->amount;
						$commission_percentage = $row_commission->percentage;
						$commission_percentage_rate = $row_commission->percentage_rate;
							
						if($commission_schedule == "monthly"){
							// check commission on payroll payslip where payroll period is current
							$check_employee_payroll_commission = $this->prm->check_employee_payroll_commission($this->emp_id,$this->company_id,$pay_period,$period_from,$period_to);
							if(!$check_employee_payroll_commission || $commission_pay_schedule == 'per payroll'){
								if (($commission_pay_schedule == 'first payroll' || $commission_pay_schedule == 'per payroll') &&
										$q1['period'] == 1) {
				
											if($commission_scheme == "set amount"){ // check if set amount value
												$total_employee_commission += ($commission_amount > 0) ? $commission_amount : 0 ;
												$commission = ($commission_amount > 0) ? $commission_amount : 0 ;
											}else if($commission_scheme == "as percentage to"){ // check if as percentage to
												if($commission_percentage == "hourly rate"){ // for hourly percentage
													$total_employee_commission += $emp_hourly_rate * ($commission_percentage_rate / 100);
													$commission = $emp_hourly_rate * ($commission_percentage_rate / 100);
												}else if($commission_percentage == "daily rate"){ // for daily rate
													$total_employee_commission += ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
													$commission = ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
												}
											}
				
										} elseif (($commission_pay_schedule == 'second payroll' || $commission_pay_schedule == 'per payroll') &&
												$q1['period'] == 2) {
				
											if($commission_scheme == "set amount"){ // check if set amount value
												$total_employee_commission += ($commission_amount > 0) ? $commission_amount : 0 ;
												$commission = ($commission_amount > 0) ? $commission_amount : 0 ;
											}else if($commission_scheme == "as percentage to"){ // check if as percentage to
												if($commission_percentage == "hourly rate"){ // for hourly percentage
													$total_employee_commission += $emp_hourly_rate * ($commission_percentage_rate / 100);
													$commission = $emp_hourly_rate * ($commission_percentage_rate / 100);
												}else if($commission_percentage == "daily rate"){ // for daily rate
													$total_employee_commission += ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
													$commission = ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
												}
											}
										}
											
										// FOR FORTNIGHTLY
										elseif (
												($employee_period_type == "Fortnightly" && $commission_pay_schedule == 'per payroll')
												||
												($commission_pay_schedule == 'last payroll' && $check_fortnightly_last_payroll != FALSE && $employee_period_type == "Fortnightly")
										) {
				
											if($commission_scheme == "set amount"){ // check if set amount value
												$total_employee_commission += ($commission_amount > 0) ? $commission_amount : 0 ;
												$commission = ($commission_amount > 0) ? $commission_amount : 0 ;
											}else if($commission_scheme == "as percentage to"){ // check if as percentage to
												if($commission_percentage == "hourly rate"){ // for hourly percentage
													$total_employee_commission += $emp_hourly_rate * ($commission_percentage_rate / 100);
													$commission = $emp_hourly_rate * ($commission_percentage_rate / 100);
												}else if($commission_percentage == "daily rate"){ // for daily rate
													$total_employee_commission += ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
													$commission = ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
												}
											}
				
										}
											
							}
						}elseif($commission_schedule == "one-time"){
							if(strtotime($start) <= strtotime($commission_schedule_date) && strtotime($commission_schedule_date) <= strtotime($end)){
								if($commission_scheme == "set amount"){ // check if set amount value
									$total_employee_commission += ($commission_amount > 0) ? $commission_amount : 0 ;
									$commission = ($commission_amount > 0) ? $commission_amount : 0 ;
				
									$commission_flag_one_time = TRUE;
								}else if($commission_scheme == "as percentage to"){ // check if as percentage to
									if($commission_percentage == "hourly rate"){ // for hourly percentage
										$total_employee_commission += $emp_hourly_rate * ($commission_percentage_rate / 100);
										$commission = $emp_hourly_rate * ($commission_percentage_rate / 100);
											
										$commission_flag_one_time = TRUE;
									}else if($commission_percentage == "daily rate"){ // for daily rate
										$total_employee_commission += ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
										$commission = ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
											
										$commission_flag_one_time = TRUE;
									}
								}
							}
						}
							
						$commission = ($commission > 0) ? number_format($commission,2) : 0 ;
							
						#print "<li>{$commission}</li>";
						
						array_push($your_commission_amount,$commission);
					}
				}
				
				$your_commission = array();
				foreach($your_commission_name as $key=>$val){
					$comm = array($val,$your_commission_amount[$key]);
					array_push($your_commission, (object) $comm);
				}
				
				// TOTAL COMMISSIONS
				$total_employee_commission = ($total_employee_commission > 0) ? $total_employee_commission : 0 ;
					
				// get commission (end here)
				
				$regular_hours = 0;
				if($generate_printable_payslip){			
					 
					foreach($generate_printable_payslip as $gen_payslip){
						$reg_deduction = $gen_payslip->absences + $gen_payslip->tardiness_pay + $gen_payslip->undertime_pay;
						 
						$tmp_regular_hours = 0;
						$regular_hours = 0;
						$reg_hrs = $this->pp->get_regular_hours($this->emp_id,$get_current_payslip->payroll_payslip_id,$payroll_date,$period_from,$period_to,$this->company_id);
						if($reg_hrs) {
							foreach($reg_hrs as $row_reg_hrs){
								if($row_reg_hrs->flag_fortnightly == 1){
									foreach($reg_hrs as $row_reg_hrs){
										$tmp_regular_hours = $tmp_regular_hours + $row_reg_hrs->fortnightly_amount;
									}
								}else{
									$tmp_regular_hours = $gen_payslip->basic_pay;
								}
							}
						}else{
							$tmp_regular_hours = $gen_payslip->basic_pay;
						}
						$regular_hours =  $tmp_regular_hours - $reg_deduction;
					}
				}
				
				// get other earnings (start here)
				$earnings_first_column = array();
				$earnings_amount_column = array();
				$employee_other_earnings = 0;
				$total_amount_other_earnings = '0';
				
				// GET EMPLOYEE EARNINGS
				$employee_earnigs = $this->prm->employee_other_earnings_lite($this->emp_id,$this->company_id);
					
				if($employee_earnigs != FALSE){
						
					// PAYROLL DATE
					$payroll_date = date("Y-m-d",strtotime($this->input->get("pay_period"))); // date
				
					foreach($employee_earnigs as $row_dd_emp){
							
						// CHECK TOTAL AMOUNT FOR OTHER EARNINGS
						$earnings = $row_dd_emp->earning_id;
						$check_total_amount_other_earnings = $this->prm->check_total_amount_other_earnings_lite($this->emp_id,$this->company_id,$earnings,$param_data);
						$check_total_amount_other_earnings = ($check_total_amount_other_earnings > 0) ? $check_total_amount_other_earnings : 0 ;
							
						$dd_flag = 0;
						$dd_type = $row_dd_emp->type;
						$dd_priority = $row_dd_emp->priority;
						$dd_amount = $row_dd_emp->amount;
						$dd_sdate = $row_dd_emp->start_date;
						$dd_edate = $row_dd_emp->end_date;
							
						if($row_dd_emp->recurring == "No"){
							// $dd_flag = 1;
							if($check_total_amount_other_earnings == 0) $dd_flag = 1;
						}else{
							if($dd_type == "Interval" && $dd_sdate != "" && $dd_edate != ""){
								if(strtotime($dd_sdate) <= strtotime($payroll->payroll_period) && strtotime($payroll->payroll_period) <= strtotime($dd_edate)) $dd_flag = 1;
							}elseif($dd_type == "Occurence" && $dd_sdate != ""){
								if(strtotime($payroll->payroll_period) >= strtotime($dd_sdate)){
									// if($row_dd_emp->no_of_occurence > 0) $dd_flag = 1;
									// if($row_dd_emp->no_of_occurence > $check_total_amount_other_earnings) $dd_flag = 1; // 2 > 2 including current payroll
				
									if($row_dd_emp->earning_frequency == "Daily" || $row_dd_emp->earning_frequency == "Weekly"){
										$dd_flag = 1;
									}else{
										if($row_dd_emp->no_of_occurence > $check_total_amount_other_earnings) $dd_flag = 1; // 2 > 2 including current payroll
									}
				
								}
							}elseif($dd_type == ""){
								if(strtotime($payroll->payroll_period) >= strtotime($dd_sdate)){
									$dd_flag = 1;
								}
							}
						}
							
						// $dd_flag = ($dd_flag == 1 && ($dd_priority == "Every Payroll" || ($dd_priority == "First Payroll" && $q1_period == "1") || ($dd_priority == "Second Payroll" && $q1_period == "2"))) ? 1 : 0 ;
						if(
								(
										$dd_flag == 1 &&
										(
												$dd_priority == "Every Payroll"
												||
												($dd_priority == "First Payroll" && $q1_period == "1")
												||
												($dd_priority == "Second Payroll" && $q1_period == "2")
												||
												(($employee_period_type == "Fortnightly" && $dd_priority == 'Every Payroll')
														||
														($dd_priority == 'Last Payroll' && $check_fortnightly_last_payroll != FALSE && $employee_period_type == "Fortnightly"))
										)
								)
						){
							$dd_flag = 1;
						}else{
							$dd_flag = 0;
						}
							
						if($dd_flag == 1) {
				
							// CHECK IF EARNINGS IF TAXABLE
							// if($row_dd_emp->tax_exemption == "not_exempt") $employee_other_earnings_taxable += $dd_amount;
				
							if($row_dd_emp->recurring == "Yes"){
									
								// CHECK Earning Frequency
								$earning_frequency = $row_dd_emp->earning_frequency;
									
								$current = $start;
				
								$flag_to_add = TRUE;
								switch ($earning_frequency){
				
									case "Daily":
											
										// CHECK START DATE
										$earnings_start_date = (strtotime($current) <= strtotime($dd_sdate) && strtotime($dd_sdate) <= strtotime($end)) ? $dd_sdate : $current ;
										$emp_total_days_time_in = $this->prm->emp_total_days_time_in($this->company_id,$this->emp_id,$earnings_start_date,$end);
				
										// NO OF OCCURENCES
										$check_no_of_occurences_paid = $this->prm->check_no_of_occurences_paid($period_from,$period_to,$pay_period,$row_dd_emp->earning_id,$this->company_id,$this->emp_id);
										if($check_no_of_occurences_paid !=  FALSE){
											$earnings_no_occurences = $row_dd_emp->no_of_occurence - $check_no_of_occurences_paid;
											$earnings_no_occurences = ($earnings_no_occurences > 0) ? $earnings_no_occurences : 0 ;
										}else{
											$earnings_no_occurences = $row_dd_emp->no_of_occurence;
										}
											
										if($dd_type == "Occurence"){
											if($earnings_no_occurences > 0){
												// CHECK OCCURENCES
												if($row_dd_emp->recurring == "Yes" && $dd_type == "Occurence"){
													if($earnings_no_occurences <= $emp_total_days_time_in){
														$earnings_new_total_days_time_in = $earnings_no_occurences;
													}elseif($emp_total_days_time_in <= $earnings_no_occurences){
														$earnings_new_total_days_time_in = $emp_total_days_time_in;
													}
												}
				
												$dd_amount = $dd_amount * $earnings_new_total_days_time_in;
											}else{
												$flag_to_add = FALSE;
											}
										}else{
											$dd_amount = $dd_amount * $emp_total_days_time_in;
										}
											
										break;
				
									case "Weekly":
											
										$total_week = 0;
										for($var = 1; $var <= 4; $var++){
											$new_start_date = date("Y-m-d",strtotime($current." +{$var} week"));
											if(strtotime($current) <= strtotime($new_start_date) && strtotime($new_start_date) <= strtotime($end)){
												$total_week++;
											}
										}
											
										// NO OF OCCURENCES
										$check_no_of_occurences_paid = $this->prm->check_no_of_occurences_paid($period_from,$period_to,$pay_period,$row_dd_emp->earning_id,$this->company_id,$this->emp_id);
										if($check_no_of_occurences_paid !=  FALSE){
											$earnings_no_occurences = $row_dd_emp->no_of_occurence - $check_no_of_occurences_paid;
											$earnings_no_occurences = ($earnings_no_occurences > 0) ? $earnings_no_occurences : 0 ;
										}else{
											$earnings_no_occurences = $row_dd_emp->no_of_occurence;
										}
				
										if($earnings_no_occurences > 0){
											// CHECK OCCURENCES
											if($row_dd_emp->recurring == "Yes" && $dd_type == "Occurence"){
												if($earnings_no_occurences <= $total_week){
													$dd_amount = $dd_amount * $earnings_no_occurences;
												}elseif($total_week <= $earnings_no_occurences){
													$dd_amount = $dd_amount * $total_week;
												}
											}
										}else{
											$flag_to_add = FALSE;
										}
											
										break;
				
									case "Semi-Monthly":
											
										$flag_earnings_amount = FALSE;
										// for($var = 15; $var <= 1440; $var++){ // 15 days * 2 * 12 months * 4 years = 1440
										for($var = 0; $var <= 1440; $var = $var + 15){ // 15 days * 2 * 12 months * 4 years = 1440
											$new_start_date = date("Y-m-d",strtotime($dd_sdate." +{$var} days"));
											if(strtotime($current) <= strtotime($new_start_date) && strtotime($new_start_date) <= strtotime($end)){
												$dd_amount = $dd_amount;
												$flag_earnings_amount = TRUE;
											}
										}
											
										if($dd_priority == "First Payroll" || $dd_priority == "Second Payroll"){
											$dd_amount = $dd_amount * 2;
										}
											
										if(!$flag_earnings_amount) $dd_amount = 0;
											
										break;
											
									case "Monthly":
											
										$dd_amount = $dd_amount;
										break;
				
									case "Quarterly":
											
										$ear_year = date("Y",strtotime($payroll_date));
										$ear_month = date("m",strtotime($payroll_date));
										$ear_day = date("d",strtotime($dd_sdate));
											
										// 48 months or 4 year;
										// 4 is equal to every 4 months or quarterly
										for($var = 4; $var <= 48; $var = $var + 4){
											$new_start_date = date("Y-m-d",strtotime($dd_sdate." +{$var} months"));
											if(strtotime($current) <= strtotime($new_start_date) && strtotime($new_start_date) <= strtotime($end)){
												$dd_amount = $dd_amount;
											}
										}
											
										break;
											
									case "Semi-Annually":
											
										$ear_year = date("Y",strtotime($payroll_date));
										$ear_month = date("m",strtotime($payroll_date));
										$ear_day = date("d",strtotime($dd_sdate));
											
										// 48 months or 4 year;
										// 6 is equal to every 6 months or semi annual
										for($var = 6; $var <= 48; $var = $var + 6){
											$new_start_date = date("Y-m-d",strtotime($dd_sdate." +{$var} months"));
											if(strtotime($current) <= strtotime($new_start_date) && strtotime($new_start_date) <= strtotime($end)){
												$dd_amount = $dd_amount;
											}
										}
											
										break;
				
									case "Annually":
											
										$ear_year = date("Y",strtotime($payroll_date));
										$ear_month = date("m",strtotime($payroll_date));
										$ear_day = date("d",strtotime($dd_sdate));
				
										// 48 months or 4 year;
										$flag_earnings_amount = FALSE;
										for($var = 1; $var <= 4; $var++){
											$new_start_date = date("Y-m-d",strtotime($dd_sdate." +{$var} year"));
											if(strtotime($current) <= strtotime($new_start_date) && strtotime($new_start_date) <= strtotime($end)){
												$dd_amount = $dd_amount;
												$flag_earnings_amount = TRUE;
											}
										}
										if(!$flag_earnings_amount) $dd_amount = 0;
											
										break;
				
									default:
											
										$dd_amount = 0;
											
										break;
											
								}
							}
				
							if($row_dd_emp->tax_exemption == "Not Exempt"){
								$employee_other_earnings_taxable += $dd_amount;
							}else{
								$employee_other_earnings_non_taxable += $dd_amount;
							}
				
							$employee_other_earnings += $dd_amount;
				
							if($dd_amount > 0 && $flag_to_add){
								array_push($earnings_first_column,$row_dd_emp->name);
								array_push($earnings_amount_column,$dd_amount);
							}
				
						}
							
					}
						
				}
				
	
				
				$your_other_earnings_name = array();
				foreach($earnings_first_column as $key => $val){
					#print "<li>{$earnings_first_column[$key]}</li>";
					array_push($your_other_earnings_name,$earnings_first_column[$key]);
				}
				
				$your_other_earnings_amount = array();
				foreach($earnings_amount_column as $key => $val){
					#print "<li>".number_format($earnings_amount_column[$key],2)."</li>";
					array_push($your_other_earnings_amount,number_format($earnings_amount_column[$key],2));
				}
				
				$your_other_earnings = array();
				foreach($your_other_earnings_name as $key=>$val){
					$other = array($val,$your_other_earnings_amount[$key]);
					array_push($your_other_earnings, (object) $other);
				}
				
				// get other earnings (end here)
				
				//get Withholding Tax (Fixed)
				
				$wttax_fixed_amount = 0;
				$wttax_fixed_amount = $this->employee->wt_fixed($this->company_id, $this->emp_id, $pay_period, $period_from, $period_to);
				$tax_fixed_amount = 0;
				if($wttax_fixed_amount) {
					  $tax_fixed_amount = $wttax_fixed_amount;
				}
				
				// get basic pay
				// EMPLOYEE RATE
				$employee_rate = $payroll_run_details->rate;
				$employee_rate = ($employee_rate > 0) ? $employee_rate : 0 ;
					
				// EMPLOYEE BASIC PAY CHECK EMPLOYEE PERIOD TYPE
				$basic_pay = 0;
				$check_employee_period_type = $this->prm->check_employee_period_type($this->emp_id,$this->company_id);
				if($check_employee_period_type != FALSE){
				
					$employee_period_type = $check_employee_period_type->period_type;
					$employee_pay_rate_type = $check_employee_period_type->pay_rate_type;
				
					if(
							($employee_period_type == "Semi Monthly" && $employee_pay_rate_type == "By Hour")
							||
							($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Hour")
					){ // hourly
							
						$total_hours = 0;
							
						// get number of days
						$current = $period_from;
						$end = $period_to;
						while($current <= $end){
				
							$weekday = date('l',strtotime($current));
							$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$current);
							$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
				
							// check if not rest day
							if(!$rest_day){
								$check_employee_time_in = $this->prm->check_employee_time_in_by_hourly($this->company_id,$this->emp_id,$current);
								if($check_employee_time_in){
				
									foreach($check_employee_time_in as $row_timein_hourly){
										$workday = $this->prm->get_workday($this->company_id,$work_schedule_id->work_schedule_id);
										if($workday != FALSE){
											if($workday->workday_type == "Flexible Hours"){
												$total_hours += ($row_timein_hourly->total_hours_required > 0) ? $row_timein_hourly->total_hours_required : 0 ;
											}else{
												$total_hours += ($row_timein_hourly->total_hours > 0) ? $row_timein_hourly->total_hours : 0 ;
											}
										}
									}
				
								}
							}
				
							$current = date("Y-m-d",strtotime($current." +1 day"));
						}
							
						$basic_pay = $employee_rate * $total_hours;
							
					}else if(
							($employee_period_type == "Semi Monthly" && $employee_pay_rate_type == "By Day")
							||
							($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Day")
							||
							($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Month")
					){ // daily
							
						$total_days = 0;
							
						// get number of days
						$current = $period_from;
						$end = $period_to;
						while($current <= $end){
				
							$weekday = date('l',strtotime($current));
							$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$current);
							$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
				
							// check if not rest day
							if(!$rest_day){
								$check_employee_time_in = $this->prm->check_employee_time_in($this->company_id,$this->emp_id,$current);
								if($check_employee_time_in){
									$total_days++;
								}
							}
				
							$current = date("Y-m-d",strtotime($current." +1 day"));
						}
							
						$basic_pay = $employee_rate * $total_days; // add total days rendered
						$basic_pay += $total_amount_paid_leave; // add total leave amount
						// $basic_pay += $total_amount_render_for_daily_holiday; // add total holidays with greater than 100%
					}else if($employee_period_type == "Semi Monthly" && $employee_pay_rate_type == "By Month"){ // month
						$basic_pay = $employee_rate / 2;
					}else {
						$basic_pay = 0;
					}
				}
				
				$payroll_deminimis = $this->epsm->get_payroll_deminimis($this->emp_id,$draft_pay_run_id,$pay_period,$period_from,$period_to,$this->company_id);
				
				$new_total_amount_de_minimis = 0;
				$new_total_amount_de_minimis_ex = 0;
				$dem_arr = array();
				if($payroll_deminimis){
					foreach($payroll_deminimis as $row_payroll_deminimis){
						if($row_payroll_deminimis->rules == "Add to Non Taxable Income"){
							$type = $row_payroll_deminimis->description;
							$excess = $row_payroll_deminimis->description;
							$deminimis_amount = $row_payroll_deminimis->amount;
							$deminimis_excess = $row_payroll_deminimis->excess;
							
							$new_total_amount_de_minimis += $row_payroll_deminimis->amount;
							$new_total_amount_de_minimis_ex += $row_payroll_deminimis->excess;
										
							$dem_temp = array(
									"deminimis" => $type,
									"excess" => "- Excess : ".$excess,
									"deminimis_amnt" => $deminimis_amount,
									"excess_amnt" => $deminimis_excess
									
							);
							
							array_push($dem_arr,$dem_temp);
						}
					}
				}		
				
				$new_total_deminimis = 0;
				$new_total_deminimis = $new_total_amount_de_minimis + $new_total_amount_de_minimis_ex;
				#$new_total_deminimis = number_format($new_total_deminimis,2);
				
				$payroll_other_earnings = $this->epsm->get_payroll_earnings($this->emp_id,$draft_pay_run_id_new,$pay_period,$period_from,$period_to,$this->company_id);
				$other_ear_arr = array();
				$total_amount_other_earnings = '0.00';
				if($payroll_other_earnings){
					
					$tmps_this = 0;
					foreach($payroll_other_earnings as $row_payroll_other_earnings){
						$tmps_this += $row_payroll_other_earnings->pol_amount;
					}
					if($tmps_this == ""){
						$total_amount_other_earnings = '0.00';
					}else{
						$total_amount_other_earnings = $tmps_this;
					}
					 
					foreach($payroll_other_earnings as $row_other_earnings){
						$other_ear_arr_t = array(
								"other_earnings_name" => $row_other_earnings->pol_name,
								"amount" => $row_other_earnings->pol_amount
						);
						 
						array_push($other_ear_arr,$other_ear_arr_t);
					}
				}
				
				// EMPLOYEE TOTAL LOAN AMOUNT (GOVERNMENT LOANS & THIRD PARTY)
				$employee_total_loan_amount_t = 0;
					
				$third_party_loans = $this->prm->employee_loans($this->company_id);
				$third_party_loans_arr = array();
				if($third_party_loans != FALSE){
					foreach($third_party_loans as $row_third_party_loans){
						$get_employee_loans = $this->prm->get_employee_loans($this->emp_id,$row_third_party_loans->loan_type_id,$period_from,$period_to,$pay_period);
						if($get_employee_loans != FALSE){
				
							foreach($get_employee_loans as $row_emp_loans){
								if($row_emp_loans->installment > 0){
									$employee_total_loan_amount_t += $row_emp_loans->installment;
									$loan_type_name = $row_third_party_loans->loan_type_name;
									$installment = number_format( $row_emp_loans->installment,2);
								}else{
									$employee_total_loan_amount_t = 0;
									$loan_type_name = $row_third_party_loans->loan_type_name;
									$installment = 0;
								}
								
								$thrd_temp = array(
										"gov_loan_name" => $loan_type_name,
										"installment" => $installment
								);
								
								array_push($third_party_loans_arr,$thrd_temp);
							}		
						}
					}
				}
				
				// EMPLOYEE GOVERNMENT LOANS
				$employee_total_loan_amount_g = 0;
				
				$government_loans = $this->prm->employee_government_loans($this->company_id);
				$government_loans_arr = array();
				if($government_loans != FALSE){
					foreach($government_loans as $row_government_loans){
						$government_loans_amount = $this->prm->get_employee_government_loans($this->emp_id,$row_government_loans->loan_type_id,$period_from,$period_to,$pay_period);
						if($government_loans_amount != FALSE){
								
							$government_loans_amount_result = 0;
							foreach($government_loans_amount as $row_gl){
								if($row_gl->amount > 0){
									$employee_total_loan_amount_g += $row_gl->amount;
									$loan_type_name_g = $row_government_loans->loan_type_name;
									$amount = number_format($row_gl->amount,2);
								}
								
								$gov_temp = array(
										"gov_loan_name" => $loan_type_name_g,
										"amount" => $amount
								);
								
								array_push($government_loans_arr,$gov_temp);
							}
						}
					}
				}
				
				// OTHER DEDUCTIONS
				$deductions_other_deductions = $this->prm->deductions_other_deductions($this->company_id);
				$employee_other_deductions = 0;
				$deductions_other_deductions_arr = array();
				if($deductions_other_deductions != FALSE){
					foreach($deductions_other_deductions as $row_deductions_other_deductions){
						$other_deduction_amount = $this->prm->get_employee_other_deductions($this->emp_id,$row_deductions_other_deductions->deductions_other_deductions_id,$period_from,$period_to,$pay_period);
						if($other_deduction_amount != FALSE){
								
							$other_deduction_val = 0;
								
							foreach($other_deduction_amount as $row_oo){
								if($row_oo->amount > 0){
									$employee_other_deductions += $row_oo->amount;
									$name = $row_deductions_other_deductions->name;
									$amount = number_format($row_oo->amount,2);
								} else {
									$name = "";
									$amount = 0;
								}
								
								$eod_temp = array(
										"name" => $name,
										"amount" => $amount
								);
								
								array_push($deductions_other_deductions_arr,$eod_temp);
							}
				
						}
					}
				}
				
				$total_adjustment_amount = 0;
				
				// ABSENCES ADJUSTMENT
				$adjustment_absences_amount = 0;
				$adjustment_absences = $this->prm->adjustment_absences($this->emp_id,$this->company_id,$param_data);
				if($adjustment_absences != FALSE){
					foreach($adjustment_absences as $row){
						$date_format = ($row->date != "") ? date("m/d/Y",strtotime($row->date)) : "" ;
						$absences_amount = number_format($row->amount,2);
						
						$adjustment_absences_amount += $row->amount;
					}
				}
				
				// TARDINESS ADJUSTMENT
				$adjustment_tardiness_amount = 0;
				$adjustment_tardiness = $this->prm->adjustment_tardiness($this->emp_id,$this->company_id,$param_data);
				if($adjustment_tardiness != FALSE){
					foreach($adjustment_tardiness as $row){
						$date_format = ($row->date != "") ? date("m/d/Y",strtotime($row->date)) : "" ;
						$tardiness_amount = number_format($row->amount,2);
						
						$adjustment_tardiness_amount += $row->amount;
					}
				}
				
				// UNDERTIME ADJUSTMENT
				$adjustment_undertime_amount = 0;
				$adjustment_undertime = $this->prm->adjustment_undertime($this->emp_id,$this->company_id,$param_data);
				if($adjustment_undertime != FALSE){
					foreach($adjustment_undertime as $row){
						$date_format = ($row->date != "") ? date("m/d/Y",strtotime($row->date)) : "" ;
						$undertime_amount = number_format($row->amount,2);
						
						$adjustment_undertime_amount += $row->amount;
					}
				}
				
				// ADJUSTMENT FOR THIRD PARTY LOANS
				$adjustment_third_party_loans_amount = 0;
				$adjustment_third_party_loans = $this->prm->adjustment_third_party_loans($this->emp_id,$this->company_id,$param_data);
				$adjustment_third_party_loans_arr = array();
				if($adjustment_third_party_loans != FALSE){
					foreach($adjustment_third_party_loans as $row){
						$loan_type_name = $row->loan_type_name;
						$third_party_loans_amount = number_format($row->amount,2);
						
						$adjustment_third_party_loans_amount += $row->amount;
						
						$atpl_temp = array(
								"name" => $loan_type_name,
								"amount" => $third_party_loans_amount,
								'total_amount' => $adjustment_third_party_loans_amount
						);
						
						array_push($adjustment_third_party_loans_arr,$atpl_temp);
					}
				}
				
				// ADJUSTMENT FOR GOVERNMENT LOANS
				$adjustment_government_loans_amount = 0;
				$adjustment_government_loans = $this->prm->adjustment_government_loans($this->emp_id,$this->company_id,$param_data);
				$adjustment_government_loans_arr = array();
				if($adjustment_government_loans != FALSE){
					foreach($adjustment_government_loans as $row){
						$loan_type_name = $row->loan_type_name;
						$government_loans_amount = number_format($row->amount,2);
						
						$adjustment_government_loans_amount += $row->amount;
						
						$agl_temp = array(
								"name" => $loan_type_name,
								"amount" => $government_loans_amount,
								'total_amount' => $adjustment_government_loans_amount
						);
						
						array_push($adjustment_government_loans_arr,$agl_temp);
					}
				}
				
				// ADJUSTMENT FOR OTHER DEDUCTIONS
				$adjustment_other_deductions_amount = 0;
				$adjustment_other_deductions = $this->prm->adjustment_other_deductions($this->emp_id,$this->company_id,$param_data);
				$adjustment_other_deductions_arr = array();
				if($adjustment_other_deductions != FALSE){
					foreach($adjustment_other_deductions as $row){
						$deduction_type_name = $row->deduction_type_name;
						$other_deductions_amount = number_format($row->amount,2);
						
						$adjustment_other_deductions_amount += $row->amount;
						
						$aod_temp = array(
								"name" => $deduction_type_name,
								"amount" => $other_deductions_amount,
								"total_amount" => $adjustment_other_deductions_amount
						);
						
						array_push($adjustment_other_deductions_arr,$aod_temp);
					}
				}
				
				$payroll_deduction = $this->epsm->get_other_deduction($this->emp_id,$draft_pay_run_id,$pay_period,$period_from,$period_to,$this->company_id);
				$employee_other_deductions1 = 0;
				$payroll_deduction_arr = array();
				if($payroll_deduction){
					foreach($payroll_deduction as $row_payroll_deduction){
						$employee_other_deductions1 += $row_payroll_deduction->amount;
						$deduction_name = $row_payroll_deduction->deduction_name;
						$amount = number_format($row_payroll_deduction->amount,2);
				
						$pda_temp = array(
								"name" => $deduction_name,
								"amount" => $amount,
								"total_amount" => $employee_other_deductions1
						);
						
						array_push($payroll_deduction_arr,$pda_temp);
					}
				}
				
				$total_amnt_other_deduc = $adjustment_absences_amount + $adjustment_tardiness_amount + $adjustment_undertime_amount + 
					$adjustment_third_party_loans_amount + $adjustment_government_loans_amount +
					$adjustment_other_deductions_amount;
				
				$all_other_deductions = array(
						"absences" => $adjustment_absences_amount,
						"tardiness" => $adjustment_tardiness_amount,
						"undertime" => $adjustment_undertime_amount,
						"thrd_pt_loan" => $adjustment_third_party_loans_arr,
						"gov_loan" => $adjustment_government_loans_arr,
						"other_deduc" => $adjustment_other_deductions_arr,
						"employee_other_deductions" => $payroll_deduction_arr,
						"total_amnt_other_adj" => $total_amnt_other_deduc
				);
				
				// INSURANCE
				$payroll_insurance = $this->epsm->get_payroll_insurance($this->emp_id,$draft_pay_run_id,$pay_period,$period_from,$period_to,$this->company_id);
				$insurance_amount = 0;
				$insurance_display_amount2 = 0;
				$payroll_insurance_arr = array();
				if($payroll_insurance){
					foreach ($payroll_insurance as $row_insurance){
						$insurance_amount = number_format($row_insurance->amount,2);
						$insurance_type = $row_insurance->insurance_type;
				
						$insurance_display_amount2 += $row_insurance->amount;
				
						$pia_temp = array(
								"name" => $insurance_type,
								"amount" => $insurance_amount,
								"total_amount" => $insurance_display_amount2
						);
				
						array_push($payroll_insurance_arr,$pia_temp);
					}
				}
				
				if($get_current_payslip->service_charge_non_taxable == 0){
					$service_charge_allocation_amount = $get_current_payslip->service_charge_taxable;
				}else{
					$service_charge_allocation_amount = $get_current_payslip->service_charge_non_taxable;
				}
				
				$total_emp_other_deductions = $employee_other_deductions + $employee_other_deductions1;
				
				$total_deductions = 0;
				$total_deductions = $get_current_payslip->pp_withholding_tax + $get_current_payslip->pp_sss + $get_current_payslip->pp_philhealth + $get_current_payslip->pp_pagibig + 
								$employee_total_loan_amount_g + $employee_total_loan_amount_t + $tax_fixed_amount + $total_amnt_other_deduc + $total_emp_other_deductions + $insurance_display_amount2;
				$total_deductions = ($total_deductions > 0) ? number_format($total_deductions,2) : number_format(0,2) ;
				
				$total_earnings1 = $basic_pay - $get_current_payslip->absences - $get_current_payslip->tardiness_pay - $get_current_payslip->undertime_pay +
				   $get_current_payslip->overtime_pay +
				   $get_current_payslip->sunday_holiday +
				   $get_current_payslip->night_diff +
				   $adjustment_total_amount +
				   $total_employee_allowances +
				   $total_employee_commission +
				   $total_amount_de_minimis +
				   $service_charge_allocation_amount +
				  # $total_amount_hazard_pay_display +
					$adjustment_hazard_pay_amount1 +
				  # $total_amount_other_earnings;
					$total_amount_other_earnings +
					$employee_other_earnings +
					$new_total_deminimis;
					#$leave_conversion_total_amount_val_non_taxable;
				   $total_earnings1 = ($total_earnings1 > 0) ? number_format($total_earnings1,2) : number_format(0,2) ;
				
				  $new_regular_hours = $regular_hours - $get_current_payslip->paid_leave_amount;
				   
				  // TOTAL HOURS
				  $total_amount_for_regualr_hours = $basic_pay - $get_current_payslip->absences - $get_current_payslip->tardiness_pay - $get_current_payslip->undertime_pay - $get_current_payslip->paid_leave_amount;
				  $total_amount_for_regualr_hours = ($total_amount_for_regualr_hours > 0) ? number_format($total_amount_for_regualr_hours,2) : number_format(0,2) ;
				  
				$payslip = array(
						'period_from' => $get_current_payslip->period_from,
						'period_to' => $get_current_payslip->period_to,
						'payroll_date' => $get_current_payslip->payroll_date,
						'net_amount' => number_format($get_current_payslip->net_amount,2),
						'overtime_pay' => number_format($get_current_payslip->overtime_pay,2),
						'paid_leave_amount' => number_format($get_current_payslip->paid_leave_amount,2),
						'sunday_holiday' => number_format($get_current_payslip->sunday_holiday,2),
						'night_diff' => number_format($get_current_payslip->night_diff,2),
						'absences' => number_format($get_current_payslip->absences,2),
						'tardiness_pay' => number_format($get_current_payslip->tardiness_pay,2),
						'undertime_pay' => number_format($get_current_payslip->undertime_pay,2),
						'regular_hours' => number_format($new_regular_hours,2),
						'advance_payment' => $advance_payment,
						'workday' => $adj_workday,
						'employee_overtime' => $emp_ot,
						'employee_holiday' => $hol_prem,
						'employee_night_differential' => $nyt_diff,
						'employee_paid_leave' => $emp_paid_leave,
						'allowances' => $adj_allow,
						'commission' => $adj_comm,
						'adj_de_minimis' => $adj_deminimis,
						'de_minimis' => $dem_arr,
						'total_deminimis' => $new_total_deminimis,
						'service_charge' => $adj_serv_charge,
						'hazard_pay' => $adj_hazard,
						'adj_other_earnings' => $adj_other,
						'other_earnings' => $other_ear_arr,
						'total_adjustment_amount' => number_format($adjustment_total_amount,2),
						'your_allowances'=> $your_allowances,
						'total_allowance' => number_format($total_employee_allowances,2),
						'your_deminimis' => $you_deminimis,
						'total_deminimis' => number_format($new_total_deminimis,2),
						'your_commision' => $your_commission,
						'total_commission' => number_format($total_employee_commission,2),
						#'your_other-earnings' => $your_other_earnings,
						'your_other_earnings' => number_format($total_amount_other_earnings,2),
						'total_other_earnings' => number_format($employee_other_earnings,2),
						'pp_withholding_tax' => number_format($get_current_payslip->pp_withholding_tax,2),
						'pp_pagibig' => number_format($get_current_payslip->pp_pagibig,2),
						'pp_philhealth' => number_format($get_current_payslip->pp_philhealth,2),
						'pp_sss' => number_format($get_current_payslip->pp_sss,2),
						'tax_fixed_amount' => number_format($tax_fixed_amount,2),
						'total_earnings' => $total_earnings1,
						'total_amnt_thrd_pt_loan' => number_format($employee_total_loan_amount_t,2),
						'total_amnt_gov_loan' => number_format($employee_total_loan_amount_g,2),
						'thrd_pt_loan' => $third_party_loans_arr,
						'gov_loan' => $government_loans_arr,
						'total_emp_other_deductions' => number_format($total_emp_other_deductions,2),
						'deductions_other_deductions_arr' => $deductions_other_deductions_arr,
						'all_other_adj' => $all_other_deductions,
						'total_deductions' => $total_deductions,
						'total_amnt_insurance' => number_format($insurance_display_amount2,2),
						'insurance' => $payroll_insurance_arr,
						'total_amount_for_regualr_hours' => $total_amount_for_regualr_hours
						
				);
				
				array_push($payslip_res,$payslip);
			}
			
			$res = array(
					"result" => true,
					"page" => $page, 
					"numPages" => $limit,
					"total" => $total,
					"latest_payslip" => $payslip_res
			);
			
			echo json_encode($res);
		} else {
			$res = array(
					"result" => false
			);
			
			echo json_encode($res);
			#return false;
		}
		
		#echo json_encode($res);
	}
	
	public function payslip_v2 () {
	    $page = $this->input->post('page');
	    $limit = $this->input->post('limit');
	    
	    $this->per_page = 10;
	    
	    $get_data_payslip = $this->mobile->get_data_payslip($this->company_id,$this->emp_id,false,(($page-1) * $this->per_page),$limit);
	    $total = ceil($this->mobile->get_data_payslip($this->company_id,$this->emp_id,true) / 10);
	    // p($get_data_payslip);
	    if($get_data_payslip) {
	        echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "total" => $total,"list" => $get_data_payslip));
	        return false;
	    } else {
	        echo json_encode(array("result" => "0"));
	        return false;
	    }
	}
	
	public function payslip_detail_v2 () {
	    $flag_payslip = $this->input->post('flag_payslip');
	    $get_current_payslip = $this->mobile->get_new_latest_payslip($this->company_id,$this->emp_id);
	    
	    if($flag_payslip == 1) {
	        if($get_current_payslip) {
	            $payroll_payslip_id = $get_current_payslip->payroll_payslip_id;
	        } else {
	            $payroll_payslip_id = $this->input->post('payroll_payslip_id');
	        }
	        
	    } else {
	        $payroll_payslip_id = $this->input->post('payroll_payslip_id');
	    }
	    
	    if($payroll_payslip_id) {	    
        	    $payroll_run_details = $this->pp3->payroll_run_details_where($this->emp_id,$payroll_payslip_id,$this->company_id);
        	    
        	    $pay_period = $payroll_run_details->payroll_date;
        	    $payroll_date = $payroll_run_details->payroll_date;
        	    $period_from = $payroll_run_details->period_from;
        	    $period_to = $payroll_run_details->period_to;
        	    
        	    $draft_pay_run_id = $this->epsm->draft_pay_runs_groupby($this->company_id,$pay_period,$period_from,$period_to);
        	    
        	    $overtime_adj = $this->pp3->get_overtime_adjustment($payroll_date,$period_from,$period_to,$this->company_id);
        	    $holiday_adj = $this->pp3->get_holiday_adjustment($payroll_date,$period_from,$period_to,$this->company_id);
        	    $night_diff_adj = $this->pp3->get_night_diff_adjustment($payroll_date,$period_from,$period_to,$this->company_id);
        	    $paid_leave_adj = $this->pp3->get_paid_leave_adjustment($payroll_date,$period_from,$period_to,$this->company_id);
        	    $allowance_adj = $this->pp3->get_allowance_adjustment($payroll_date,$period_from,$period_to,$this->company_id);
        	    $commission_adj = $this->pp3->get_commission_adjustment($payroll_date,$period_from,$period_to,$this->company_id);
        	    $advance_payment_adj = $this->pp3->get_advance_payment_adjustment($payroll_date,$period_from,$period_to,$this->company_id);
        	    $deminimis_adj = $this->pp3->get_deminimis_adjustment($payroll_date,$period_from,$period_to,$this->company_id);
        	    $service_charge_adj = $this->pp3->get_service_charge_adjustment($payroll_date,$period_from,$period_to,$this->company_id);
        	    $hazard_pay_adj = $this->pp3->get_hazard_pay_adjustment($payroll_date,$period_from,$period_to,$this->company_id);
        	    $workday_adj = $this->pp3->get_workday_adjustment($payroll_date,$period_from,$period_to,$this->company_id);
        	    $payroll_allowances = $this->pp3->get_payroll_allowances($payroll_date,$period_from,$period_to,$this->company_id);
        	    $payroll_commission = $this->pp3->get_payroll_commission($payroll_date,$period_from,$period_to,$this->company_id);
        	    $payroll_deminimis = $this->pp3->get_payroll_deminimis($payroll_date,$period_from,$period_to,$this->company_id);
        	    $other_earnings = $this->pp3->get_other_earnings($payroll_date,$period_from,$period_to,$this->company_id);
        	    $payroll_insurance = $this->pp3->get_payroll_insurance_v2("","",$payroll_date,$period_from,$period_to,$this->company_id);
        	    $get_third_party_loans_deductions = $this->pp3->get_third_party_loans_deductions($payroll_date,$period_from,$period_to,$this->company_id);
        	    $get_government_loans_deductions = $this->pp3->get_government_loans_deductions($payroll_date,$period_from,$period_to,$this->company_id);
        	    $get_other_deductions = $this->pp3->get_other_deduction($payroll_date,$period_from,$period_to,$this->company_id);
        	    $absences_deductions = $this->pp3->get_absences_hours2($payroll_date,$period_from,$period_to,$this->company_id);
        	    $tardiness_deductions = $this->pp3->get_tardiness_hours2($payroll_date,$period_from,$period_to,$this->company_id);
        	    $undertime_deductions = $this->pp3->get_undertime_hours2($payroll_date,$period_from,$period_to,$this->company_id);
        	    $get_other_adjustment_deduction = $this->pp3->get_other_adjustment_deduction($payroll_date,$period_from,$period_to,$this->company_id);
        	    $get_other_third_party_loans_deductions = $this->pp3->get_other_adjustment_third_party_loans_deductions($payroll_date,$period_from,$period_to,$this->company_id);
        	    $get_other_government_loans_deductions = $this->pp3->get_other_government_loans_deductions($payroll_date,$period_from,$period_to,$this->company_id);
        	    $check_withholding_tax_fixed = $this->pp3->check_withholding_tax_fixed($payroll_date,$period_from,$period_to,$this->company_id);
        	    
        	    $basic_pay = $payroll_run_details ? $payroll_run_details->basic_pay : 0;
        	    $absences = $payroll_run_details ? $payroll_run_details->absences : 0;
        	    $tardiness_pay = $payroll_run_details ? $payroll_run_details->tardiness_pay : 0;
        	    $undertime_pay = $payroll_run_details ? $payroll_run_details->undertime_pay : 0;
        	    $paid_leave_amount = $payroll_run_details ? $payroll_run_details->paid_leave_amount : 0;
        	    $cola = $payroll_run_details ? $payroll_run_details->cola : 0;
        	    $leave_conversion_non_taxable = $payroll_run_details ? number_format($payroll_run_details->leave_conversion_non_taxable,2) : 0;
        	    $overtime_pay = $payroll_run_details ? $payroll_run_details->overtime_pay : 0;
        	    $sunday_holiday = $payroll_run_details ? $payroll_run_details->sunday_holiday : 0;
        	    $night_diff = $payroll_run_details ? $payroll_run_details->night_diff : 0;
        	    $paid_leave_amount = $payroll_run_details ? $payroll_run_details->paid_leave_amount : 0;
        	    $service_charge_taxable = $payroll_run_details ? $payroll_run_details->service_charge_taxable : 0;
        	    $service_charge_non_taxable = $payroll_run_details ? $payroll_run_details->service_charge_non_taxable : 0;
        	    $hazard_pay_taxable = $payroll_run_details ? $payroll_run_details->hazard_pay_taxable : 0;
        	    $hazard_pay_non_taxable = $payroll_run_details ? $payroll_run_details->hazard_pay_non_taxable : 0;
        	    $withholding_tax = $payroll_run_details ? $payroll_run_details->pp_withholding_tax : 0;
        	    $pp_sss = $payroll_run_details ? $payroll_run_details->pp_sss : 0;
        	    $pp_philhealth = $payroll_run_details ? $payroll_run_details->pp_philhealth : 0;
        	    $pp_pagibig = $payroll_run_details ? $payroll_run_details->pp_pagibig : 0;
        	    $voluntary_contributions = $payroll_run_details ? $payroll_run_details->voluntary_contributions : 0;
        	    $hdmf_modified = $payroll_run_details ? $payroll_run_details->hdmf_modified : 0;
        	    $net_pay = $payroll_run_details ? number_format($payroll_run_details->net_amount,2) : 0;
        	    
        	    $regular_hours_earnings = $basic_pay - $absences - $tardiness_pay - $undertime_pay - $paid_leave_amount;
        	    
        	    // get Adjustments
        	    $param_data = array(
        	        "payroll_period"=>$pay_period,
        	        "period_from"=>$period_from,
        	        "period_to"=>$period_to
        	    );
        	    
        	    $adjustment_total_amount = 0;
        	    $total_adjustment = 0;
        	    
        	    // get overtime - adjustment
        	    $tmp_overtime_adj = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$overtime_adj);
        	    $this_overtime_adj = ($tmp_overtime_adj != FALSE) ? $tmp_overtime_adj : FALSE ;
        	    $this_overtime_amount = 0;
        	    
        	    if($this_overtime_adj) {
        	        $tmp_this = 0;
        	        
        	        foreach ($this_overtime_adj as $row_overtime_adj) {
        	            $tmp_this += $row_overtime_adj->amount;
        	            $total_adjustment += $row_overtime_adj->amount;
        	        }
        	        
        	        if($tmp_this == ""){
        	            $this_overtime_amount = 0;
        	        }else{
        	            $this_overtime_amount = number_format($tmp_this,2);
        	        }
        	        
        	    }else{
        	        $this_overtime_amount = 0;
        	    }
        	    
        	    // get holiday - adjustment
        	    $tmp_holiday_adj = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$holiday_adj);
        	    $this_holiday_adj = ($tmp_holiday_adj != FALSE) ? $tmp_holiday_adj : FALSE ;
        	    $this_holiday_amount = 0;
        	    
        	    if($this_holiday_adj) {
        	        $tmp_this = 0;
        	        
        	        foreach ($this_holiday_adj as $row_holiday_adj) {
        	            $tmp_this += $row_holiday_adj->amount;
        	            $total_adjustment += $row_holiday_adj->amount;
        	        }
        	        
        	        if($tmp_this == ""){
        	            $this_holiday_amount = 0;
        	        }else{
        	            $this_holiday_amount = number_format($tmp_this,2);
        	        }
        	        
        	    }else{
        	        $this_holiday_amount = 0;
        	    }
        	    
        	    // get night diff - adjustment
        	    $tmp_night_diff_adj = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$night_diff_adj);
        	    $this_night_diff_adj = ($tmp_night_diff_adj != FALSE) ? $tmp_night_diff_adj : FALSE ;
        	    $this_night_diff_amount = 0;
        	    
        	    if($this_night_diff_adj) {
        	        $tmp_this = 0;
        	        
        	        foreach ($this_night_diff_adj as $row_night_diff_adj) {
        	            $tmp_this += $row_night_diff_adj->amount;
        	            $total_adjustment += $row_night_diff_adj->amount;
        	        }
        	        
        	        if($tmp_this == ""){
        	            $this_night_diff_amount = 0;
        	        }else{
        	            $this_night_diff_amount = number_format($tmp_this,2);
        	        }
        	        
        	    }else{
        	        $this_night_diff_amount = 0;
        	    }
        	    
        	    // get leaves - adjustment
        	    $tmp_paid_leave_adj = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$paid_leave_adj);
        	    $this_paid_leave_adj = ($tmp_paid_leave_adj != FALSE) ? $tmp_paid_leave_adj : FALSE ;
        	    $this_paid_leave_amount = 0;
        	    
        	    if($this_paid_leave_adj) {
        	        $tmp_this = 0;
        	        
        	        foreach ($this_paid_leave_adj as $row_paid_leave_adj) {
        	            $tmp_this += $row_paid_leave_adj->amount;
        	            $total_adjustment += $row_paid_leave_adj->amount;
        	        }
        	        
        	        if($tmp_this == ""){
        	            $this_paid_leave_amount = 0;
        	        }else{
        	            $this_paid_leave_amount = number_format($tmp_this,2);
        	        }
        	        
        	    }else{
        	        $this_paid_leave_amount = 0;
        	    }
        	    
        	    // get allowances - adjustment
        	    $tmp_allowance = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$allowance_adj);
        	    $this_allowance_adj = ($tmp_allowance != FALSE) ? $tmp_allowance : FALSE ;
        	    $this_allowance_amount = 0;
        	    
        	    if($this_allowance_adj) {
        	        $tmp_this = 0;
        	        
        	        foreach ($this_allowance_adj as $row_allowance_adj) {
        	            $tmp_this += $row_allowance_adj->allowances_amount;
        	            $total_adjustment += $row_allowance_adj->allowances_amount;
        	        }
        	        
        	        if($tmp_this == ""){
        	            $this_allowance_amount = 0;
        	        }else{
        	            $this_allowance_amount = number_format($tmp_this,2);
        	        }
        	        
        	    }else{
        	        $this_allowance_amount = 0;
        	    }
        	    
        	    // get commission - adjustments
        	    $tmp_commission_adj = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$commission_adj);
        	    $this_commission_adj = ($tmp_commission_adj != FALSE) ? $tmp_commission_adj : FALSE ;
        	    $this_commission_amount = 0;
        	    
        	    if($this_commission_adj) {
        	        $tmp_this = 0;
        	        
        	        foreach ($this_commission_adj as $row_commission_adj) {
        	            $tmp_this += $row_commission_adj->earnings_amount;
        	            $total_adjustment += $row_commission_adj->earnings_amount;
        	        }
        	        
        	        if($tmp_this == ""){
        	            $this_commission_amount = 0;
        	        }else{
        	            $this_commission_amount = number_format($tmp_this,2);
        	        }
        	        
        	    }else{
        	        $this_commission_amount = 0;
        	    }
        	    
        	    // get advance pay - adjustment
        	    $tmp_advance_payment_adj = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$advance_payment_adj);
        	    $this_advance_payment_adj = ($tmp_advance_payment_adj != FALSE) ? $tmp_advance_payment_adj : FALSE ;
        	    $this_advance_payment_amount = 0;
        	    
        	    if($this_advance_payment_adj) {
        	        $tmp_this = 0;
        	        
        	        foreach ($this_advance_payment_adj as $row_advance_payment_adj) {
        	            $tmp_this += $row_advance_payment_adj->amount;
        	            $total_adjustment += $row_advance_payment_adj->amount;
        	        }
        	        
        	        if($tmp_this == ""){
        	            $this_advance_payment_amount = 0;
        	        }else{
        	            $this_advance_payment_amount = number_format($tmp_this,2);
        	        }
        	        
        	    }else{
        	        $this_advance_payment_amount = 0;
        	    }
        	    
        	    // get de minimis - adjustment
        	    $tmp_deminimis_adj = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$deminimis_adj);
        	    $this_deminimis_adj = ($tmp_deminimis_adj != FALSE) ? $tmp_deminimis_adj : FALSE ;
        	    $this_deminimis_amount = 0;
        	    
        	    if($this_deminimis_adj) {
        	        $tmp_this = 0;
        	        
        	        foreach ($this_deminimis_adj as $row_deminimis_adj) {
        	            $tmp_this += $row_deminimis_adj->de_minimis_amount;
        	            $total_adjustment += $row_deminimis_adj->de_minimis_amount;
        	        }
        	        
        	        if($tmp_this == ""){
        	            $this_deminimis_amount = 0;
        	        }else{
        	            $this_deminimis_amount = number_format($tmp_this,2);
        	        }
        	        
        	    }else{
        	        $this_deminimis_amount = 0;
        	    }
        	    
        	    // get service charge - adjustments
        	    $tmp_service_charge_adj = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$service_charge_adj);
        	    $this_service_charge_adj = ($tmp_service_charge_adj != FALSE) ? $tmp_service_charge_adj : FALSE ;
        	    $this_service_charge_amount = 0;
        	    
        	    if($this_service_charge_adj) {
        	        $tmp_this = 0;
        	        
        	        foreach ($this_service_charge_adj as $row_service_charge_adj) {
        	            $tmp_this += $row_service_charge_adj->amount;
        	            $total_adjustment += $row_service_charge_adj->amount;
        	        }
        	        
        	        if($tmp_this == ""){
        	            $this_service_charge_amount = 0;
        	        }else{
        	            $this_service_charge_amount = number_format($tmp_this,2);
        	        }
        	        
        	    }else{
        	        $this_service_charge_amount = 0;
        	    }
        	    
        	    // get hazard pay - adjustment
        	    $tmp_hazard_pay_adj = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$hazard_pay_adj);
        	    $this_hazard_pay_adj = ($tmp_hazard_pay_adj != FALSE) ? $tmp_hazard_pay_adj : FALSE ;
        	    $this_hazard_pay_amount = 0;
        	    
        	    if($this_hazard_pay_adj) {
        	        $tmp_this = 0;
        	        
        	        foreach ($this_hazard_pay_adj as $row_hazard_pay_adj) {
        	            $tmp_this += $row_hazard_pay_adj->amount;
        	            $total_adjustment += $row_hazard_pay_adj->amount;
        	        }
        	        
        	        if($tmp_this == ""){
        	            $this_hazard_pay_amount = 0;
        	        }else{
        	            $this_hazard_pay_amount = number_format($tmp_this,2);
        	        }
        	        
        	    }else{
        	        $this_hazard_pay_amount = 0;
        	    }
        	    
        	    // get workday - adjustment
        	    $tmp_workday_adj = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$workday_adj);
        	    $this_workday_adj = ($tmp_workday_adj != FALSE) ? $tmp_workday_adj : FALSE ;
        	    $this_workday_amount = 0;
        	    
        	    if($this_workday_adj) {
        	        $tmp_this = 0;
        	        
        	        foreach ($this_workday_adj as $row_workday_adj) {
        	            $tmp_this += $row_workday_adj->amount;
        	            $total_adjustment += $row_workday_adj->amount;
        	        }
        	        
        	        if($tmp_this == ""){
        	            $this_workday_amount = 0;
        	        }else{
        	            $this_workday_amount = number_format($tmp_this,2);
        	        }
        	        
        	    }else{
        	        $this_workday_amount = 0;
        	    }
        	    
        	    // get allowances
        	    $allowance_res = array();
        	    $allowance_total_amnt = 0;
        	    if($payroll_allowances){
        	        $tmp_payroll_allowances = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$payroll_allowances);
        	        $this_payroll_allowances = ($tmp_payroll_allowances != FALSE) ? $tmp_payroll_allowances : FALSE ;
        	        if($this_payroll_allowances){
        	            foreach($this_payroll_allowances as $row_payroll_allowance){
        	                $allowance_description = $row_payroll_allowance->description;
        	                $allowance_amount = number_format($row_payroll_allowance->amount,2);
        	                $allowance_total_amnt += $row_payroll_allowance->amount;
        	                
        	                $temp = array(
        	                    "description" => $allowance_description,
        	                    "amount" => $allowance_amount
        	                );
        	                
        	                array_push($allowance_res, $temp);
        	            }
        	        }
        	    }
        	    
        	    // get commission
        	    $commission_res = array();
        	    $commission_total_amnt = 0;
        	    if($payroll_commission){
        	        $tmp_payroll_commission = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$payroll_commission);
        	        $this_payroll_commission = ($tmp_payroll_commission != FALSE) ? $tmp_payroll_commission : FALSE ;
        	        if($this_payroll_commission){
        	            foreach($this_payroll_commission as $row_payroll_commission){
        	                $commission_plan = $row_payroll_commission->commission_plan;
        	                $commission_amount = number_format($row_payroll_allowance->amount,2);
        	                $commission_total_amnt += $row_payroll_commission->amount;
        	                
        	                $temp = array(
        	                    "description" => $commission_plan,
        	                    "amount" => $commission_amount
        	                );
        	                
        	                array_push($commission_res, $temp);
        	            }
        	        }
        	    }
        	    
        	    // get de minimis
        	    $de_minimis_res = array();
        	    $de_minimis_total_amnt = 0;
        	    if($payroll_deminimis){
        	        $tmp_payroll_deminimis = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}", $payroll_deminimis);
        	        $this_payroll_deminimis = ($tmp_payroll_deminimis != FALSE) ? $tmp_payroll_deminimis : FALSE;
        	        if ($this_payroll_deminimis) {
        	            foreach ($this_payroll_deminimis as $row_payroll_deminimis) {
        	                if ($row_payroll_deminimis->rules == "Add to Non Taxable Income") {
        	                    $description = $row_payroll_deminimis->description;
        	                    $de_minimis_total_amnt += $row_payroll_deminimis->amount + $row_payroll_deminimis->excess;
        	                    
        	                    $temp = array(
        	                        "description" => $description,
        	                        "excess" => "- Excess : ".$description,
        	                        "description_amount" => $row_payroll_deminimis->amount,
        	                        "excess_amount" => $row_payroll_deminimis->excess
        	                    );
        	                    
        	                    array_push($de_minimis_res, $temp);
        	                    
        	                }
        	            }
        	        }
        	    }
        	    
        	    // get service charge
        	    if ($service_charge_taxable > 0 || $service_charge_non_taxable > 0) {
        	        
        	        if ($service_charge_non_taxable == "") {
        	            $this_service_charge_amount = $payroll_run_details ? number_format($payroll_run_details->service_charge_taxable, 2) : 0;
        	        } else {
        	            $this_service_charge_amount = $payroll_run_details ? number_format($payroll_run_details->service_charge_non_taxable, 2) : 0;
        	        }
        	    }
        	    
        	    // get hazard pay
        	    $final_hazard_pay = $hazard_pay_taxable + $hazard_pay_non_taxable;
        	    $final_hazard_pay = number_format($final_hazard_pay,2);
        	    
        	    // get other earnings
        	    $other_earnings_res = array();
        	    $other_earnings_total_amnt = 0;
        	    if($other_earnings){
        	        $tmp_other_earnings = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$other_earnings);
        	        $this_other_earnings = ($tmp_other_earnings != FALSE) ? $tmp_other_earnings : FALSE ;
        	        if($this_other_earnings){
        	            foreach($this_other_earnings as $row_other_earnings){
        	                #echo "<li>{$row_other_earnings->pol_name}</li>";
        	                $other_earnings_total_amnt += $row_other_earnings->pol_amount;
        	                $temp = array(
        	                    "description" => $row_other_earnings->pol_name,
        	                    "amount" => number_format($row_other_earnings->pol_amount,2)
        	                );
        	                
        	                array_push($de_minimis_res, $temp);
        	            }
        	        }
        	    }
        	    
        	    // get total earnings
        	    $total_earnings_amount = $regular_hours_earnings + $cola + $paid_leave_amount + $overtime_pay + $sunday_holiday + $night_diff + $total_adjustment + $allowance_total_amnt + $commission_total_amnt + 
        	                           $de_minimis_total_amnt + $this_service_charge_amount + $final_hazard_pay + $other_earnings_total_amnt;
        	    
        	    // get insurance
                $insurances_res = array();
                $insurances_total_amnt = 0;
                if($payroll_insurance){
                   $tmp_payroll_insurance = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$payroll_insurance);
                   $this_payroll_insurance = ($tmp_payroll_insurance != FALSE) ? $tmp_payroll_insurance : FALSE ;
                   
                   if($this_payroll_insurance){
                       foreach ($this_payroll_insurance as $row_payroll_insurance) {
                           $insurances_total_amnt += $row_payroll_insurance->amount;
                           
                           $temp = array(
                               "insurance_type" => $row_payroll_insurance->insurance_type,
                               "amount" => number_format($row_payroll_insurance->amount,2)
                           );
                           
                           array_push($insurances_res, $temp);
                       }
                   }
                }
                
                // get loans
                $third_pt_loans_res = array();
                $total_sum_loans = 0;
                $thrd_pt_installment = 0;
                if($get_third_party_loans_deductions){
                    $tmp_third_party_loans_deductions = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$get_third_party_loans_deductions);
                    $this_third_party_loans_deductions = ($tmp_third_party_loans_deductions != FALSE) ? $tmp_third_party_loans_deductions : FALSE ;
                    if($this_third_party_loans_deductions){
                        foreach($this_third_party_loans_deductions as $row_third_party_loans){
                            $total_sum_loans += $row_third_party_loans->installment;
                            $thrd_pt_installment += $row_third_party_loans->installment;
                            $third_party_loan_name = $row_third_party_loans->loan_type_name;
                            $third_party_loan_amount = number_format($row_third_party_loans->installment,2);
                            
                            $temp = array(
                                "loan_type_name" => $third_party_loan_name,
                                "installment" => $third_party_loan_amount
                            );
                            
                            array_push($third_pt_loans_res, $temp);
                        }
                    }
                }
                
                $gov_loans_res = array();
                $gov_installment = 0;
                if($get_government_loans_deductions){
                    $tmp_government_loans_deductions = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$get_government_loans_deductions);
                    $this_government_loans_deductions = ($tmp_government_loans_deductions != FALSE) ? $tmp_government_loans_deductions : FALSE ;
                    if($this_government_loans_deductions){
                        foreach($this_government_loans_deductions as $row_government_loans){
                            $total_sum_loans += $row_government_loans->amount;
                            $gov_installment += $row_government_loans->amount;
                            
                            $flag_emp_id = $row_government_loans->emp_id;
                            $flag_opening_balance = $row_government_loans->flag_opening_balance;
                            $flag_payroll_run_government_loan_id = $row_government_loans->payroll_run_government_loan_id;
                            
                            $get_government_loans_deductions2 = $this->pp3->get_government_loans_deductions_3($flag_payroll_run_government_loan_id,$flag_opening_balance,$flag_emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$this->company_id);
                            if($get_government_loans_deductions2){
                                foreach($get_government_loans_deductions2 as $row_government_loans2){
                                    $government_loan_name = $row_government_loans2->loan_type_name;
                                    $government_loan_amount = number_format($row_government_loans2->amount,2);
                   
                                    $temp = array(
                                        "loan_type_name" => $government_loan_name,
                                        "installment" => number_format($government_loan_amount,2)
                                    );
                                    
                                    array_push($gov_loans_res, $temp);
                                }
                            }
                        }
                    }
                }
                
                // get other deductions
                $other_deductions_res = array();
                $other_deductions_total_amnt = 0;
                $tmp_other_deductions = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$get_other_deductions);
                $this_other_deductions = ($tmp_other_deductions != FALSE) ? $tmp_other_deductions : FALSE ;
                if($this_other_deductions){
                    foreach ($this_other_deductions as $row_other_deductions) {
                        $other_deductions_total_amnt += $row_other_deductions->amount;
                        $other_deduction_name = $row_other_deductions->deduction_name;
                        $other_deduction_amount = number_format($row_deductions->amount,2);
                        
                        $temp = array(
                            "deduction_name" => $other_deduction_name,
                            "amount" => $other_deduction_amount
                        );
                        
                        array_push($other_deductions_res, $temp);
                    }
                }
                
                // get other adjustments
                $total_other_adjustment = 0;
                $total_other_adjustment_absences = 0;
                if($absences_deductions){
                    $tmp_absences = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$absences_deductions);
                    $this_absences = ($tmp_absences != FALSE) ? $tmp_absences : FALSE ;
                    if($this_absences){
                        foreach ($this_absences as $row_absences) {
                            $total_other_adjustment += $row_absences->amount;
                            $total_other_adjustment_absences = $row_absences->amount;
                        }
                    }
                }
                
                $total_other_adjustment_tardiness = 0;
                if($tardiness_deductions){
                    $tmp_tardiness = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$tardiness_deductions);
                    $this_tardiness = ($tmp_tardiness != FALSE) ? $tmp_tardiness : FALSE ;
                    if($this_tardiness){
                        foreach ($this_tardiness as $row_tardiness) {
                            $total_other_adjustment += $row_tardiness->amount;
                            $total_other_adjustment_tardiness = $row_tardiness->amount;
                        }
                    }
                }
                
                $total_other_adjustment_undertime = 0;
                if($undertime_deductions){
                    $tmp_undertime = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$undertime_deductions);
                    $this_undertime = ($tmp_undertime != FALSE) ? $tmp_undertime : FALSE ;
                    if($this_undertime){
                        foreach ($this_undertime as $row_undertime) {
                            $total_other_adjustment += $row_undertime->amount;
                            $total_other_adjustment_undertime = $row_undertime->amount;
                        }
                    }
                }
                
                $thrd_pt_loan_other_deductions_res = array();
                $total_other_adjustment_thrd_pt_loans = 0;
                if($get_other_third_party_loans_deductions){
                    $tmp_get_other_third_party_loans_deductions = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$get_other_third_party_loans_deductions);
                    $this_get_other_third_party_loans_deductions = ($tmp_get_other_third_party_loans_deductions != FALSE) ? $tmp_get_other_third_party_loans_deductions : FALSE ;
                    $total_other_adjustment_third_party_loans = 0;
                    if($this_get_other_third_party_loans_deductions){
                        foreach($this_get_other_third_party_loans_deductions as $row_other_third_party_loans){
                            $total_other_adjustment += $row_other_third_party_loans->amount;
                            $total_other_adjustment_thrd_pt_loans += $row_other_third_party_loans->amount;
                            $loan_type_name = $row_other_third_party_loans->loan_type_name;
                            
                            $temp = array(
                                "loan_type_name" => $loan_type_name,
                                "amount" => $row_other_third_party_loans->amount
                            );
                            
                            array_push($thrd_pt_loan_other_deductions_res, $temp);
                        }
                    }
                }
                
                $other_gov_loan_deductions_res = array();
                $total_other_adjustment_government_loans = 0;
                if($get_other_government_loans_deductions){
                    $tmp_get_other_government_loans_deductions = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$get_other_government_loans_deductions);
                    $this_get_other_government_loans_deductions = ($tmp_get_other_government_loans_deductions != FALSE) ? $tmp_get_other_government_loans_deductions : FALSE ;
                    $total_other_adjustment_government_loans = 0;
                    if($this_get_other_government_loans_deductions){
                        foreach($this_get_other_government_loans_deductions as $row_other_government_loans){
                            $total_other_adjustment += $row_other_government_loans->amount;
                            $total_other_adjustment_government_loans += $row_other_government_loans->amount;
                            $loan_type_name = $row_other_government_loans->loan_type_name;
                            
                            $temp = array(
                                "loan_type_name" => $loan_type_name,
                                "amount" => $row_other_government_loans->amount
                            );
                            
                            array_push($other_gov_loan_deductions_res, $temp);
                        }
                    }
                }
                
                $other_adj_deductions_res = array();
                $other_adjustments_total_amount = 0;
                if($get_other_adjustment_deduction){
                    $tmp_get_other_adjustment_deduction = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$get_other_adjustment_deduction);
                    $this_get_other_adjustment_deduction = ($tmp_get_other_adjustment_deduction != FALSE) ? $tmp_get_other_adjustment_deduction : FALSE ;
                    if($this_get_other_adjustment_deduction){
                        foreach ($this_get_other_adjustment_deduction as $row_adj_deductions) {
                            $total_other_adjustment += $row_adj_deductions->amount;
                            $other_adjustments_total_amount += number_format($row_adj_deductions->amount,2);
                            $amount = number_format($row_adj_deductions->amount,2);
                            $deduction_type_name = $row_adj_deductions->deduction_type_name;
                            
                            $temp = array(
                                "deduction_type_name" => $deduction_type_name,
                                "amount" => $amount
                            );
                            
                            array_push($other_adj_deductions_res, $temp);
                        }
                    }
                }
                
                $other_adjustments = array(
                    "other_adjustments_absences" => number_format($total_other_adjustment_absences,2),
                    "other_adjustments_tardiness" => number_format($total_other_adjustment_tardiness,2),
                    "other_adjustments_undertime" => number_format($total_other_adjustment_undertime,2),
                    "other_adjustments_thrd_pt_loans" => $thrd_pt_loan_other_deductions_res,
                    "other_adjustments_thrd_pt_loans_total_amount" => number_format($total_other_adjustment_thrd_pt_loans,2),
                    "other_adjustments_gov_loans" => $other_gov_loan_deductions_res,
                    "other_adjustments_gov_loans_total_amount" => number_format($total_other_adjustment_government_loans,2),
                    "other_adjustments" => $other_adj_deductions_res,
                    "other_adjustments_total_amount" => number_format($other_adjustments_total_amount,2),
                );
                
                // get withholding tax fixed
                $withholding_tax_fixed = 0;
                $tmp_check_withholding_tax_fixed = in_array_foreach_custom("{$this->emp_id}{$period_from}{$period_to}{$payroll_date}",$check_withholding_tax_fixed);
                $this_check_withholding_tax_fixed = ($tmp_check_withholding_tax_fixed != FALSE) ? $tmp_check_withholding_tax_fixed : FALSE ;
                $total_withholding_tax_fixed = 0;
                if($this_check_withholding_tax_fixed){
                    foreach($this_check_withholding_tax_fixed as $row_wttax){
                        $withholding_tax_fixed += $row_wttax->amount;
                    }
                }
                
                // get total deductions
                $total_deductions_amount = $pp_sss + $pp_philhealth + $pp_pagibig + $voluntary_contributions + $hdmf_modified + $withholding_tax + $withholding_tax_fixed + $insurances_total_amnt + $total_sum_loans +
                    $other_deductions_total_amnt + $total_other_adjustment;
        	    
        	    $adjustments_array = array(
        	        "adjustment_overtime" => ($this_overtime_amount > 0) ? number_format($this_overtime_amount,2) : 0,
        	        "adjustment_holiday" => ($this_holiday_amount > 0) ? number_format($this_holiday_amount,2) : 0,
        	        "adjustment_night_diff" => ($this_night_diff_amount > 0) ? number_format($this_night_diff_amount,2) : 0,
        	        "adjustment_leave" => ($this_paid_leave_amount > 0) ? $this_paid_leave_amount : 0,
        	        "adjustment_allowance" => ($this_allowance_amount > 0) ? number_format($this_allowance_amount,2) : 0,
        	        "adjustment_commission" => ($this_commission_amount > 0) ? number_format($this_commission_amount,2) : 0,
        	        "adjustment_advance_pay" => ($this_advance_payment_amount > 0) ? number_format($this_advance_payment_amount,2) : 0,
        	        "adjustment_de_minimis" => ($this_deminimis_amount > 0) ? number_format($this_deminimis_amount,2) : 0,
        	        "adjustment_service_charge" => ($this_service_charge_amount > 0) ? number_format($this_service_charge_amount,2) : 0,
        	        "adjustment_hazard_pay" => ($this_hazard_pay_amount > 0) ? number_format($this_hazard_pay_amount,2) : 0,
        	        "adjustment_workday" => ($this_workday_amount > 0) ? number_format($this_workday_amount,2) : 0
        	    );
        	    
        	    $payslip_result = array(
        	        "payroll_date" => $payroll_date,
        	        "period_from" => $period_from,
        	        "period_to" => $period_to,
        	        "net_pay" => $net_pay,
        	        "regular_hours" => ($regular_hours_earnings > 0) ? number_format($regular_hours_earnings,2) : 0,
        	        "hoursworked" => ($regular_hours_earnings > 0) ? number_format($regular_hours_earnings,2) : 0,
        	        "absences" => ($absences > 0) ? number_format($absences,2) : 0,
        	        "tardiness_pay" => ($tardiness_pay > 0) ? number_format($tardiness_pay,2) : 0,
        	        "undertime_pay" => ($undertime_pay > 0) ? number_format($undertime_pay,2) : 0,
        	        "cola" => ($cola > 0) ? number_format($cola,2) : 0,
        	        "paid_leave" => ($paid_leave_amount > 0) ? number_format($paid_leave_amount,2) : 0,
        	        "leave_conversion" => ($leave_conversion_non_taxable > 0) ? number_format($leave_conversion_non_taxable,2) : 0,
        	        "overtime_pay" => ($overtime_pay > 0) ? number_format($overtime_pay,2) : 0,
        	        "holiday_sunday" => ($sunday_holiday > 0) ? number_format($sunday_holiday,2) : 0,
        	        "night_diff" => ($night_diff > 0) ? number_format($night_diff,2) : 0,
        	        "adjustments" => $adjustments_array,
        	        "adjustment_total_amount" => ($total_adjustment > 0) ? number_format($total_adjustment,2) : 0,
        	        "allowances" => $allowance_res,
        	        "allowances_total_amount" => ($allowance_total_amnt > 0) ? number_format($allowance_total_amnt,2) : 0,
        	        "commissions" => $commission_res,
        	        "commissions_total_amount" => ($commission_total_amnt > 0) ? number_format($commission_total_amnt,2) : 0,
        	        "de_minimis" => $de_minimis_res,
        	        "de_minimis_total_amount" => ($de_minimis_total_amnt > 0) ? number_format($de_minimis_total_amnt,2) : 0,
        	        "service_charge" => ($this_service_charge_amount > 0) ? number_format($this_service_charge_amount,2) : 0,
        	        "hazard_pay" => ($final_hazard_pay > 0) ? number_format($final_hazard_pay,2) : 0,
        	        "other_earnings" => $other_earnings_res,
        	        "other_earnings_total_amount" => ($other_earnings_total_amnt > 0) ? number_format($other_earnings_total_amnt,2) : 0,
        	        "total_earnings" => ($total_earnings_amount > 0) ? number_format($total_earnings_amount,2) : 0,
        	        "withholding_tax" => ($withholding_tax > 0) ? number_format($withholding_tax,2) : 0,
        	        "sss" => ($pp_sss > 0) ? number_format($pp_sss,2) : 0,
        	        "phil_health" => ($pp_philhealth > 0) ? number_format($pp_philhealth,2) : 0,
        	        "pp_pagibig" => ($pp_pagibig > 0) ? number_format($pp_pagibig,2) : 0,
        	        "voluntary_contributions" => ($voluntary_contributions > 0) ? number_format($voluntary_contributions,2) : 0,
        	        "hdmf_modified" => ($hdmf_modified > 0) ? number_format($hdmf_modified,2) : 0,
        	        "insurances" => $insurances_res,
        	        "insurances_total_amount" => ($insurances_total_amnt > 0) ? number_format($insurances_total_amnt,2) : 0,
        	        "third_pt_loans" => $third_pt_loans_res,
        	        "third_pt_total_amount" => ($thrd_pt_installment > 0) ? number_format($thrd_pt_installment,2) : 0,
        	        "gov_loans" => $gov_loans_res,
        	        "gov_total_amount" => ($gov_installment > 0) ? number_format($gov_installment,2) : 0,
        	        "loans_total_amount" => ($total_sum_loans > 0) ? number_format($total_sum_loans,2) : 0,
        	        "other_deductions" => $other_deductions_res,
        	        "other_deductions_total_amount" => ($other_deductions_total_amnt > 0) ? number_format($other_deductions_total_amnt,2) : 0,
        	        "other_adustments" => $other_adjustments,
        	        "other_adjustment_total_amount" => ($total_other_adjustment > 0) ? number_format($total_other_adjustment,2) : 0,
        	        "withholding_tax_fixed" => ($withholding_tax_fixed > 0) ? number_format($withholding_tax_fixed,2) : 0,
        	        "total_deductions_amount" => ($total_deductions_amount > 0) ? number_format($total_deductions_amount,2) : 0
        	    );
        	    
        	    echo json_encode(array("result" => "1", "payslip" => $payslip_result));
        	    return false;
	    } else {
	        echo json_encode(array("result" => "0"));
	        return false;
	    }
	}
	
	public function get_latest_payslip() {
		$get_current_payslip = $this->mobile->get_current_payslip($this->company_id,$this->emp_id);
		$adjustment_total_amount = 0;
		$payroll = $this->hwm->get_payroll_period($this->company_id);
		#if($get_current_payslip && $payroll) {		
		$payroll = ($payroll) ? $payroll : false;
		if($get_current_payslip) {
			$payroll_run_details = $this->employee->payroll_run_details_where($this->emp_id,$this->company_id,$get_current_payslip->payroll_payslip_id);
			
			$pay_period = $payroll_run_details->payroll_date;
			$period_from = $payroll_run_details->period_from;
			$period_to = $payroll_run_details->period_to;			
			$payroll_date = $get_current_payslip->payroll_date;
			$generate_printable_payslip = $this->employee->generate_printable_payslip($get_current_payslip->payroll_payslip_id,$pay_period,$period_from,$period_to,$this->company_id);
		
			
			$draft_pay_run = $this->epsm->draft_pay_runs_groupby($this->company_id,$pay_period,$period_from,$period_to);
			$draft_pay_run_id = $draft_pay_run;
			$draft_pay_run_id_new = '';
			if($draft_pay_run_id){
				foreach ($draft_pay_run_id as $wew){
					$draft_pay_run_id_new = $wew->draft_pay_run_id;
				}
			}
			
			// EMPLOYEE PAYROLL INFORMATION
			$check_employee_payroll_information = $this->prm->check_employee_payroll_information($this->emp_id,$this->company_id);
			
			if($check_employee_payroll_information != FALSE){
					
				// EMPLOYEE PAYROLL GROUP ID
				$employee_payroll_group_id = $check_employee_payroll_information->payroll_group_id;
					
				// PERIOD TYPE
				$employee_period_type = $check_employee_payroll_information->period_type;
					
				// PAY RATE TYPE
				$employee_pay_rate_type = $check_employee_payroll_information->pay_rate_type;
					
			}
			
			$q1 = $this->dcm->get_payroll_calendar($this->company_id,$pay_period,$employee_payroll_group_id);
			
			// EMPLOYEE HOURLY RATE
			$emp_hourly_rate = $this->prm->new_hourly_rate($this->company_id,$this->emp_id,$employee_payroll_group_id,$payroll);
			
			// get adjustment (start here)
			$param_data = array(
					"payroll_period"=> $pay_period,
					"period_from"=> $period_from,
					"period_to"=> $period_to
			);
			$adjustment_total_workday = 0;
			$adjustment_workday1 = $this->prm->adjustment_workday($this->emp_id,$this->company_id,$param_data);
			$adj_workday = '0';
			if($adjustment_workday1 != FALSE){
				foreach($adjustment_workday1 as $row_awday1){
					$adjustment_total_workday += $row_awday1->amount;
				}
			
				if($adjustment_total_workday > 0) {
					$sub2 = '0';
			
					$adjustment_workday = $this->prm->adjustment_workday($this->emp_id,$this->company_id,$param_data);
					if($adjustment_workday != FALSE){
						foreach($adjustment_workday as $row_awday){
							$adjustment_total_amount += $row_awday->amount;
							$sub2 = '1';
						}
					}
					$adj_workday = array(
							"workday" => $sub2,
							"amount" => number_format($adjustment_total_workday,2)
					);
						
				}
			}
			
			$adjustment_total_advance_payment1 = 0;
			$adjustment_advance_payment1 = $this->prm->adjustment_advance_payment($this->emp_id,$this->company_id,$param_data);
			$advance_payment = '0';
			if($adjustment_advance_payment1 != FALSE){
				foreach($adjustment_advance_payment1 as $row1){
					$adjustment_total_advance_payment1 += $row1->amount;
				}
				if($adjustment_total_advance_payment1 > 0) {
					$sub2 = '0';
			
					$adjustment_advance_payment = $this->prm->adjustment_advance_payment($this->emp_id,$this->company_id,$param_data);
					if($adjustment_advance_payment != FALSE){
						foreach($adjustment_advance_payment as $row){
							$adjustment_total_amount += $row->amount;
							$sub2 = '1';
			
						}
					}
			
					$advance_payment = array(
							"advance_payment" => $sub2,
							"amount" => number_format($adjustment_total_advance_payment1,2)
					);
			
				}
			}
			
			$adjustment_total_overtime1 = 0;
			$adjustment_overtime1 = $this->prm->adjustment_overtime($this->emp_id,$this->company_id,$param_data);
			$emp_ot = '0';
			if($adjustment_overtime1){
				foreach($adjustment_overtime1 as $row_ao1){
					$adjustment_total_overtime1 += $row_ao1->amount;
				}
					
				if($adjustment_total_overtime1 > 0) {
					$sub2 = '0';
					$adjustment_overtime = $this->prm->adjustment_overtime($this->emp_id,$this->company_id,$param_data);
					if($adjustment_overtime != FALSE){
						foreach($adjustment_overtime as $row_ao){
							$adjustment_total_amount += $row_ao->amount;
							$ao_date = ($row_ao->date != "") ? date("m/d/Y",strtotime($row_ao->date)) : "" ;
							$sub2 = "1";
							 
						}
					}
					
					$emp_ot = array(
							"employee_overtime" => $sub2,
							"amount" => number_format($adjustment_total_overtime1,2)
					);
				}
			}
			
			$adjustment_total_holiday1 = 0;
			$adjustment_holiday1 = $this->prm->adjustment_holiday($this->emp_id,$this->company_id,$param_data);
			$hol_prem = '0';
			if($adjustment_holiday1){
				foreach($adjustment_holiday1 as $row1){
					$adjustment_total_holiday1 += $row1->amount;
				}
				if($adjustment_total_holiday1 > 0) {
					$sub2 = '0';
			
					$adjustment_holiday = $this->prm->adjustment_holiday($this->emp_id,$this->company_id,$param_data);
					if($adjustment_holiday != FALSE){
						foreach($adjustment_holiday as $row){
							$adjustment_total_amount += $row->amount;
							$ao_date = ($row->date != "") ? date("m/d/Y",strtotime($row->date)) : "" ;
							$sub2 = "1";
			
						}
					}
					
					$hol_prem = array(
							"employee_holiday" => $sub2,
							"amount" => number_format($adjustment_total_holiday1,2)
					);
				}
			}
			
			$adjustment_total_night_differential1 = 0;
			$adjustment_night_differential1 = $this->prm->adjustment_night_differential($this->emp_id,$this->company_id,$param_data);
			$nyt_diff = '0';
			if($adjustment_night_differential1){
				foreach($adjustment_night_differential1 as $row1){
					$adjustment_total_amount += $row1->amount;
					$adjustment_total_night_differential1 += $row1->amount;
				}
			
				if($adjustment_total_night_differential1 > 0) {
					$sub2 = "0";
					
					$adjustment_night_differential = $this->prm->adjustment_night_differential($this->emp_id,$this->company_id,$param_data);
					$adjustment_night_differential_no_holiday_rate = $this->prm->adjustment_night_differential_no_holiday_rate($this->emp_id,$this->company_id,$param_data);
					if($adjustment_night_differential != FALSE){
						foreach($adjustment_night_differential as $row){
							$ao_date = ($row->date != "") ? date("m/d/Y",strtotime($row->date)) : "" ;
							#$sub2 = $row->hours_type_name;
							$sub2 = '1';
						}
					}else if($adjustment_night_differential_no_holiday_rate != FALSE){
						foreach($adjustment_night_differential_no_holiday_rate as $row){
							$ao_date = ($row->date != "") ? date("m/d/Y",strtotime($row->date)) : "" ;
							#$sub2 = "Employee Night Differential";
							$sub2 = '1';
						}
					}
					
					$nyt_diff = array(
							"employee_night_differential" => $sub2,
							"amount" => number_format($adjustment_total_night_differential1,2)
					);
				}
			}
			
			
			$adjustment_total_paid_leave1 = 0;
			$adjustment_paid_leave1 = $this->prm->adjustment_paid_leave($this->emp_id,$this->company_id,$param_data);
			$emp_paid_leave = '0';
			if($adjustment_paid_leave1){
				foreach($adjustment_paid_leave1 as $row1){
					$adjustment_total_paid_leave1 += $row1->amount;
				}
				if($adjustment_total_paid_leave1 > 0) {
					$sub2 = '1';
			
					$adjustment_paid_leave = $this->prm->adjustment_paid_leave($this->emp_id,$this->company_id,$param_data);
					if($adjustment_paid_leave != FALSE){
						foreach($adjustment_paid_leave as $row){
							$adjustment_total_amount += $row->amount;
							$ao_date = date("m/d/Y",strtotime($row->date));
							$sub2 = "1";
						}
					}
					
					$emp_paid_leave = array(
							"employee_paid_leave" => $sub2,
							"amount" => number_format($adjustment_total_paid_leave1,2)
					);
				}
			}
			
			$adjustment_total_allowances1 = 0;
			$adjustment_allowances1 = $this->prm->adjustment_allowances($this->emp_id,$this->company_id,$param_data);
			$adj_allow = '0';
			if($adjustment_allowances1) {
				foreach($adjustment_allowances1 as $row1){
					$adjustment_total_allowances1 += $row1->allowances_amount;
				}
			
				if($adjustment_total_allowances1 > 0) {
					$sub2 = "0";
					$adjustment_allowances = $this->prm->adjustment_allowances($this->emp_id,$this->company_id,$param_data);
					if($adjustment_allowances != FALSE){
						foreach($adjustment_allowances as $row){
							$adjustment_total_amount += $row->amount;
							$sub2 = "1";
						}
					}
					
					$adj_allow = array(
							"allowances" => $sub2,
							"amount" => number_format($adjustment_total_allowances1,2)
					);
				}
			}
			
			$adjustment_total_commission1 = 0;
			$adjustment_commission1 = $this->prm->adjustment_commission($this->emp_id,$this->company_id,$param_data);
			$adj_comm = '0';
			
			if($adjustment_commission1){
				foreach($adjustment_commission1 as $row1){
					$adjustment_total_commission1 += $row1->earnings_amount;
				}
				if($adjustment_total_commission1 > 0){
					$sub2 = "0";
			
					$adjustment_commission = $this->prm->adjustment_commission($this->emp_id,$this->company_id,$param_data);
					if($adjustment_commission != FALSE){
						foreach($adjustment_commission as $row){
							$adjustment_total_amount += $row->amount;
							$sub2 = "1";
						}
					}
			
					$adj_comm = array(
							"commission" => $sub2,
							"amount" => number_format($adjustment_total_commission1,2)
					);
				}
			}
			
			$adjustment_total_deminimis1 = 0;
			$adjustment_deminimis1 = $this->prm->adjustment_deminimis($this->emp_id,$this->company_id,$param_data);
			$adj_deminimis = '0';
			if($adjustment_deminimis1 != FALSE){
				foreach($adjustment_deminimis1 as $row1){
					$adjustment_total_deminimis1 += $row1->de_minimis_amount;
				}
				if($adjustment_total_deminimis1 > 0) {
					$sub2 = "0";
			
					$adjustment_deminimis = $this->prm->adjustment_deminimis($this->emp_id,$this->company_id,$param_data);
					if($adjustment_deminimis != FALSE){
						foreach($adjustment_deminimis as $row){
							$adjustment_total_amount += $row->amount;
							$sub2 = "1";
						}
					}
					
					$adj_deminimis = array(
							"de_minimis" => $sub2,
							"amount" => number_format($adjustment_total_deminimis1,2)
					);
				}
			}
			
			$adjustment_service_charge_amount1 = 0;
			$adjustment_service_charge1 = $this->prm->adjustment_service_charge($this->emp_id,$this->company_id,$param_data);
			$adj_serv_charge = '0';
			if($adjustment_service_charge1){
				foreach($adjustment_service_charge1 as $row1){
					$adjustment_service_charge_amount1 += $row1->amount;
				}
				if($adjustment_service_charge_amount1 > 0) {
					$sub2 = "0";
			
					$adjustment_service_charge = $this->prm->adjustment_service_charge($this->emp_id,$this->company_id,$param_data);
					if($adjustment_service_charge != FALSE){
						foreach($adjustment_service_charge as $row){
							$adjustment_total_amount += $row->amount;
							$sub2 = "1";
						}
					}
					
					$adj_serv_charge = array(
							"service_charge" => $sub2,
							"amount" => number_format($adjustment_service_charge_amount1,2)
					);
				}
			}
			
			$adjustment_hazard_pay_amount1 = 0;
			$adjustment_hazard_pay1 = $this->prm->adjustment_hazard_pay($this->emp_id,$this->company_id,$param_data);
			$adj_hazard = '0';
			if($adjustment_hazard_pay1 != FALSE){
				foreach($adjustment_hazard_pay_amount1 as $row1){
					$adjustment_hazard_pay_amount1 += $row1->amount;
				}
				if($adjustment_hazard_pay_amount1 > 0) {
					$sub2 = "0";
			
					$adjustment_hazard_pay = $this->prm->adjustment_hazard_pay($this->emp_id,$this->company_id,$param_data);
					if($adjustment_hazard_pay != FALSE){
						foreach($adjustment_hazard_pay as $row){
							$adjustment_total_amount += $row->amount;
							$sub2 = "1";
						}
					}
					
					$adj_hazard = array(
							"hazard_pay" => $sub2,
							"amount" => number_format($adjustment_hazard_pay_amount1,2)
					);
				}
			}
			
			$adjustment_other_earnings_amount1 = 0;
			$adjustment_other_earnings1 = $this->prm->adjustment_other_earnings($this->emp_id,$this->company_id,$param_data);
			$adj_other = '0';
			if($adjustment_other_earnings1 != FALSE){
				foreach($adjustment_other_earnings1 as $row_ashlite_ad1){
					$adjustment_other_earnings_amount1 += $row_ashlite_ad1->amount;
				}
				if($adjustment_other_earnings_amount1 > 0) {
					$sub2 = "0";
			
					$adjustment_other_earnings = $this->prm->adjustment_other_earnings($this->emp_id,$this->company_id,$param_data);
					if($adjustment_other_earnings != FALSE){
						foreach($adjustment_other_earnings as $row_ashlite_ad){
							$adjustment_total_amount += $row_ashlite_ad->amount;
							$sub2 = "1";
						}
					}
					
					$adj_other = array(
							"other_earnings" => $sub2,
							"amount" => number_format($adjustment_other_earnings_amount1,2)
					);
				}
			}
			
			$adjustment_total_amount = round($adjustment_total_amount,2);
			
			// get adjustment (end here)
			
			// get allowances (start here)

			// FIXED ALLOWANCES
			$allowances_flag_one_time_or_annualy = FALSE;
			$employee_allowances_to_display = 0;
			$remaining_taxable_allowances = 0;
			$allowance_max_non_taxable_income = 0;
			$total_fixed_allowances = 0; // taxable
			$total_fixed_allowances_nt = 0; // non taxable
			
			$allowances_settings = $this->prm->allowances_settings($this->company_id);
			
			$your_allowances_name = array();
			if($allowances_settings != FALSE){
				foreach($allowances_settings as $row_allowances_settings){
					$get_fixed_allowances = $this->prm->get_fixed_allowances($this->emp_id,$this->company_id,$row_allowances_settings->allowance_settings_id);
						
					if($get_fixed_allowances != FALSE){
						foreach($get_fixed_allowances as $row_fa){
							$allowance_name = $row_fa->name;
						}
						
						array_push($your_allowances_name,$allowance_name);
					}
				}
			
			}
			
			$your_allowances_amount = array();
			if($allowances_settings != FALSE){
				foreach($allowances_settings as $row_allowances_settings){
						
					$get_fixed_allowances = $this->prm->get_fixed_allowances($this->emp_id,$this->company_id,$row_allowances_settings->allowance_settings_id);
						
					if($get_fixed_allowances != FALSE){
			
						foreach($get_fixed_allowances as $row_fa){
								
							$fixed_allowance_amount = $row_fa->allowance_amount;
							$pay_out_schedule = $row_fa->pay_out_schedule;
							$check_taxable = $row_fa->taxable;
							$maximum_non_taxable_amount = $row_fa->maximum_non_taxable_amount;
								
							$allowance_variability_type = $row_fa->variability_type;
							$allowance_frequency = $row_fa->frequency;
							$allowance_daily_rate = $row_fa->daily_rate;
							$allowance_hourly_rate = $row_fa->hourly_rate;
							$allowance_applicable_daily_rates = $row_fa->applicable_daily_rates;
							$allowance_applicable_daily_rates_value = 0;
							$employee_entitled_to_allowance_for_absent = $row_fa->employee_entitled_to_allowance_for_absent;
								
							$start = $period_from;
							$end = $period_to;
							$check_employee_time_in = $this->prm->new_check_employee_time_in($this->company_id,$this->emp_id,$start,$end);
								#p($check_employee_time_in);
							// APPLICABLE TO DAILY RATES
							switch ($allowance_applicable_daily_rates) {
									
								case 'Regular':
									$check_daily_rate = $this->prm->check_daily_rate($this->company_id,"Regular");
									if($check_daily_rate != FALSE) $allowance_applicable_daily_rates_value = $check_daily_rate->pay_rate;
									break;
										
								case 'Special':
									$check_daily_rate = $this->prm->check_daily_rate($this->company_id,"Special Day");
									if($check_daily_rate != FALSE) $allowance_applicable_daily_rates_value = $check_daily_rate->pay_rate;
									break;
			
								case 'Legal':
									$check_daily_rate = $this->prm->check_daily_rate($this->company_id,"Holiday");
									if($check_daily_rate != FALSE) $allowance_applicable_daily_rates_value = $check_daily_rate->pay_rate;
									break;
										
								case 'Double Legal':
									$check_daily_rate = $this->prm->check_daily_rate($this->company_id,"Double Holiday");
									if($check_daily_rate != FALSE) $allowance_applicable_daily_rates_value = $check_daily_rate->pay_rate;
									break;
										
							}
								
							if (($pay_out_schedule == 'first payroll' || $pay_out_schedule == 'per payroll') && $q1['period'] == 1) {
								$flag_allowances = TRUE;
							} elseif (($pay_out_schedule == 'second payroll' || $pay_out_schedule == 'per payroll') && $q1['period'] == 2) {
								$flag_allowances = TRUE;
			
								// FOR FORTNIGHTLY
							} elseif (
									($pay_out_schedule == 'per payroll' && $employee_period_type == "Fortnightly")
									||
									($pay_out_schedule == 'last payroll' && $check_fortnightly_last_payroll != FALSE && $employee_period_type == "Fortnightly")
							) {
								$flag_allowances = TRUE;
			
							} else {
								$flag_allowances = FALSE;
							}
								
							if($flag_allowances){
			
								// VARIABLITY
								switch ($allowance_variability_type) {
			
									case 'no, just flat rate': // no, just flat rate
										$fixed_allowance_amount_paramater = $fixed_allowance_amount;
										break;
			
									case 'yes, daily rate percentage': // yes, daily rate percentage
										$fixed_allowance_amount_paramater = ($emp_hourly_rate * 8) * ($allowance_daily_rate / 100) * ($allowance_applicable_daily_rates_value / 100);
										break;
											
									case 'yes, hourly rate percentage': // yes, hourly rate percentage
										$fixed_allowance_amount_paramater = $emp_hourly_rate * ($allowance_hourly_rate / 100) * ($allowance_applicable_daily_rates_value / 100);
										break;
			
									default;
									$fixed_allowance_amount_paramater = 0;
									break;
			
								}
			
								$allowance_value_paramater = 0;
								$allow_total_absent = 0;
			
								// FREQUENCY
								switch ($allowance_frequency) {
			
									case 'per worked day':
										$timecounter = 0;
			
										if($employee_entitled_to_allowance_for_absent == "no"){ // employee not entitled to allowance for absent
											if($check_employee_time_in != FALSE){
												foreach($check_employee_time_in as $row_all_timein){
													$timecounter++;
												}
											}
										}else if($employee_entitled_to_allowance_for_absent == "yes"){ // employee entitled to allowance for absent
												
											$start = $period_from;
											$end = $period_to;
											$current = $start;
											while ($current <= $end) {
													
												/* CHECK WORK SCHEDULE */
												$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$current);
												$workday = $this->prm->get_workday($this->company_id,$work_schedule_id->work_schedule_id);
												if($workday != FALSE){
													// get weekday
													$weekday = date('l',strtotime($current));
													$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
			
													if(!$rest_day) $timecounter++;
												}
													
												$current = date('Y-m-d',strtotime($current.' +1 day'));
											}
												
										}
											
										$allowance_value_paramater = $timecounter;
										break;
			
									case 'per week':
										$timecounter = 0;
			
										if($employee_entitled_to_allowance_for_absent == "no"){ // employee not entitled to allowance for absent
											if($check_employee_time_in != FALSE){
												foreach($check_employee_time_in as $row_all_timein){
													$timecounter++;
												}
											}
										}else if($employee_entitled_to_allowance_for_absent == "yes"){ // employee entitled to allowance for absent
			
											$start = $period_from;
											$end = $period_to;
											$current = $start;
											while ($current <= $end) {
													
												/* CHECK WORK SCHEDULE */
												$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$current);
												$workday = $this->prm->get_workday($this->company_id,$work_schedule_id->work_schedule_id);
												if($workday != FALSE){
													// get weekday
													$weekday = date('l',strtotime($current));
													$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
			
													if(!$rest_day) $timecounter++;
												}
													
												$current = date('Y-m-d',strtotime($current.' +1 day'));
											}
			
										}
											
										$allowance_value_paramater = ($timecounter % 7); // 7 default
										break;
											
									case 'twice a month':
			
										$all_abs_cnt = 0;
										$twice_a_month_cnt = 0;
										$start = $period_from;
										$end = $period_to;
										$current = $start;
										while ($current <= $end) {
			
											/* CHECK WORK SCHEDULE */
											$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$current);
											$workday = $this->prm->get_workday($this->company_id,$work_schedule_id->work_schedule_id);
											if($workday != FALSE){
												// get weekday
												$weekday = date('l',strtotime($current));
												$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
			
												$allow_timein = $this->prm->time_in_regular_days($this->company_id,$this->emp_id,$current);
			
												// COUNTER
												if(!$rest_day) $twice_a_month_cnt++;
			
												// TOTAL ABSENCES FOR ALLOWANCES
												if(!$rest_day && !$allow_timein && $employee_entitled_to_allowance_for_absent == "no") $all_abs_cnt++;
											}
												
											$current = date('Y-m-d',strtotime($current.' +1 day'));
										}
			
										// $allow_total_absent = ($fixed_allowance_amount_paramater / $twice_a_month_cnt) * $all_abs_cnt;
			
										if($twice_a_month_cnt > 0){
											$allow_total_absent = ($fixed_allowance_amount_paramater / $twice_a_month_cnt) * $all_abs_cnt;
										}else{
											$allow_total_absent = 0;
										}
			
										$allowance_value_paramater = 1;
										break;
			
									case 'once a month':
										// check employee allowance
										$check_employee_allowance_once_a_month = $this->prm->check_employee_allowance_once_a_month($this->emp_id,($payroll) ? $payroll->payroll_period : "",$this->company_id);
										if(!$check_employee_allowance_once_a_month){
											// CURRENT PAY PERIOD
											$all_abs_cnt = 0;
											$twice_a_month_cnt = 0;
											$start = $period_from;
											$end = $period_to;
											$current = $start;
											while ($current <= $end) {
													
												/* CHECK WORK SCHEDULE */
												$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$current);
												$workday = $this->prm->get_workday($this->company_id,$work_schedule_id->work_schedule_id);
												if($workday != FALSE){
													// get weekday
													$weekday = date('l',strtotime($current));
													$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
			
													$allow_timein = $this->prm->time_in_regular_days($this->company_id,$this->emp_id,$current);
			
													// COUNTER
													if(!$rest_day) $twice_a_month_cnt++;
			
													// TOTAL ABSENCES FOR ALLOWANCES
													if(!$rest_day && !$allow_timein && $employee_entitled_to_allowance_for_absent == "no") $all_abs_cnt++;
												}
													
												$current = date('Y-m-d',strtotime($current.' +1 day'));
											}
			
											// PREVIOUS PAY PERIOD
											$check_previous_payroll_period = $this->prm->previous_payroll_period($employee_payroll_group_id,$this->company_id,$period_to);
											if($check_previous_payroll_period != FALSE){
												$allowance_cut_off_from = $check_previous_payroll_period->cut_off_from;
												$allowance_cut_off_to = $check_previous_payroll_period->cut_off_to;
												while ($allowance_cut_off_from <= $allowance_cut_off_to) {
														
													/* CHECK WORK SCHEDULE */
													$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$allowance_cut_off_from);
													$workday = $this->prm->get_workday($this->company_id,$work_schedule_id->work_schedule_id);
													if($workday != FALSE){
														// get weekday
														$weekday = date('l',strtotime($allowance_cut_off_from));
														$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
															
														$allow_timein = $this->prm->time_in_regular_days($this->company_id,$this->emp_id,$allowance_cut_off_from);
															
														// COUNTER
														if(!$rest_day) $twice_a_month_cnt++;
															
														// TOTAL ABSENCES FOR ALLOWANCES
														if(!$rest_day && !$allow_timein && $employee_entitled_to_allowance_for_absent == "no") $all_abs_cnt++;
													}
														
													$allowance_cut_off_from = date('Y-m-d',strtotime($allowance_cut_off_from.' +1 day'));
												}
											}
												
											// $allow_total_absent = ($fixed_allowance_amount_paramater / $twice_a_month_cnt) * $all_abs_cnt;
												
											if($twice_a_month_cnt > 0){
												$allow_total_absent = ($fixed_allowance_amount_paramater / $twice_a_month_cnt) * $all_abs_cnt;
											}else{
												$allow_total_absent = 0;
											}
												
											$allowance_value_paramater = 1;
										}
										break;
											
									case 'one time':
										// check allowance id
										$check_employee_allowances = $this->prm->check_employee_allowances($this->company_id,$this->emp_id,$row_allowances_settings->allowance_settings_id,$param_data);
										if($check_employee_allowances == 0){
											$all_abs_cnt = 0;
											$twice_a_month_cnt = 0;
											$start = $period_from;
											$end = $period_to;
											$current = $start;
											while ($current <= $end) {
													
												/* CHECK WORK SCHEDULE */
												$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$current);
												$workday = $this->prm->get_workday($this->company_id,$work_schedule_id->work_schedule_id);
												if($workday != FALSE){
													// get weekday
													$weekday = date('l',strtotime($current));
													$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
														
													$allow_timein = $this->prm->time_in_regular_days($this->company_id,$this->emp_id,$current);
														
													// COUNTER
													if(!$rest_day) $twice_a_month_cnt++;
														
													// TOTAL ABSENCES FOR ALLOWANCES
													if(!$rest_day && !$allow_timein && $employee_entitled_to_allowance_for_absent == "no") $all_abs_cnt++;
												}
			
												$current = date('Y-m-d',strtotime($current.' +1 day'));
											}
												
											// $allow_total_absent = ($fixed_allowance_amount_paramater / $twice_a_month_cnt) * $all_abs_cnt;
												
											if($twice_a_month_cnt > 0){
												$allow_total_absent = ($fixed_allowance_amount_paramater / $twice_a_month_cnt) * $all_abs_cnt;
											}else{
												$allow_total_absent = 0;
											}
												
											$allowance_value_paramater = 1;
												
											$allowances_flag_one_time_or_annualy = TRUE;
										}
										break;
			
									default;
									$allowance_value_paramater = 0;
									break;
			
								}
			
								// taxable allowances
								// if($row_fa->tax == "Not-Exempt"){ // taxable
								if($row_allowances_settings->tax == "Not-Exempt"){ // taxable
									$remaining_taxable_allowances += ($fixed_allowance_amount_paramater * $allowance_value_paramater) - $allow_total_absent;
									$total_fixed_allowances += ($fixed_allowance_amount_paramater * $allowance_value_paramater) - $allow_total_absent;
								}else{ // non taxable allowances
									$total_fixed_allowances_nt += ($fixed_allowance_amount_paramater * $allowance_value_paramater) - $allow_total_absent;
								}
			
								// employee allowances to disply
								$employee_allowances_to_display = ($fixed_allowance_amount_paramater * $allowance_value_paramater) - $allow_total_absent;
			
								}else{
									$employee_allowances_to_display = 0;
								}
			
								// TOTAL FIXED ALLOWANCES
								#print "<li>".number_format($employee_allowances_to_display,2)."</li>";
									
								/* // taxable allowances
								 $remaining_taxable_allowances += $fixed_allowance_amount;
								 total_fixed_allowances += $fixed_allowance_amount; */
								$allowance_amount = number_format($employee_allowances_to_display,2);
							}
							
							array_push($your_allowances_amount,$allowance_amount);
						}
					}
				}
				
				$your_allowances = array();
				foreach($your_allowances_name as $key=>$val){
					$allow = array($val,$your_allowances_amount[$key]);
					array_push($your_allowances, (object) $allow);
				}
				
				$total_employee_allowances = $total_fixed_allowances + $total_fixed_allowances_nt;
			
			// get allowances (end here)
			
			// get de minimis (start here)
			// EXCESS OF DE MINIMIS
			$excess_deminimis = $this->prm->excess_deminimis($this->company_id);
			$you_deminimis = array();
			$total_amount_de_minimis = 0;
			if($excess_deminimis != FALSE){
				foreach($excess_deminimis as $row_ex){
					// CHECK EMPLOYEE DE MINIMIS
					$employee_dm_amount = 0;
					$check_employee_dm = $this->prm->check_employee_deminimis($this->emp_id,$row_ex->deminimis_id,$this->company_id,$param_data);
					if($check_employee_dm !=  FALSE){
						foreach($check_employee_dm as $row_exc){
							$employee_dm_amount += $row_exc->excess + $row_exc->amount;
			
							if($row_exc->rules == "Add to Non Taxable Income") {
								/*print "<li>{$row_exc->description}</li>";
								print "<li>- Excess: {$row_exc->description}</li>";
								print "<li>".number_format($row_exc->amount,2)."</li>";
								print "<li>".number_format($row_exc->excess,2)."</li>";*/
								
								$total_amount_de_minimis += $row_exc->excess + $row_exc->amount;
								
								$deminimis_arr = array(
										"description" => $row_exc->description,
										"amount" => number_format($row_exc->amount,2),
										"excess_amount" => number_format($row_exc->excess,2)
								);
								
								array_push($you_deminimis,$deminimis_arr);
								
							}
						}
						#array_push($you_deminimis,$deminimis_arr);
					}
				}
			}
			
			// get de minimis (end here)
			
			// get commission (start here)
			$commission_flag_one_time = FALSE;
			$total_employee_commission = 0;
			
			$employee_commission = $this->prm->employee_commissions($this->emp_id,$this->company_id);
			$your_commission_name = array();
			if($employee_commission != FALSE){
				foreach($employee_commission as $row_commission){
					#print "<li>{$row_commission->commission_plan}</li>";
					array_push($your_commission_name,$row_commission->commission_plan); 
				}
			}
			
			// COMMISSIONS
			$employee_commission = $this->prm->employee_commissions($this->emp_id,$this->company_id);
				
			$your_commission_amount = array();
			if($employee_commission != FALSE){
				foreach($employee_commission as $row_commission){
					$commission = 0;
					
					// variable fields
					$commission_schedule = $row_commission->commission_schedule;
					$commission_pay_schedule = $row_commission->pay_schedule;
					$commission_schedule_date = $row_commission->schedule_date;
					$commission_scheme = $row_commission->commission_scheme;
					$commission_amount = $row_commission->amount;
					$commission_percentage = $row_commission->percentage;
					$commission_percentage_rate = $row_commission->percentage_rate;
						
					if($commission_schedule == "monthly"){
						// check commission on payroll payslip where payroll period is current
						$check_employee_payroll_commission = $this->prm->check_employee_payroll_commission($this->emp_id,$this->company_id,$pay_period,$period_from,$period_to);
						if(!$check_employee_payroll_commission || $commission_pay_schedule == 'per payroll'){
							if (($commission_pay_schedule == 'first payroll' || $commission_pay_schedule == 'per payroll') &&
									$q1['period'] == 1) {
			
										if($commission_scheme == "set amount"){ // check if set amount value
											$total_employee_commission += ($commission_amount > 0) ? $commission_amount : 0 ;
											$commission = ($commission_amount > 0) ? $commission_amount : 0 ;
										}else if($commission_scheme == "as percentage to"){ // check if as percentage to
											if($commission_percentage == "hourly rate"){ // for hourly percentage
												$total_employee_commission += $emp_hourly_rate * ($commission_percentage_rate / 100);
												$commission = $emp_hourly_rate * ($commission_percentage_rate / 100);
											}else if($commission_percentage == "daily rate"){ // for daily rate
												$total_employee_commission += ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
												$commission = ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
											}
										}
			
									} elseif (($commission_pay_schedule == 'second payroll' || $commission_pay_schedule == 'per payroll') &&
											$q1['period'] == 2) {
			
										if($commission_scheme == "set amount"){ // check if set amount value
											$total_employee_commission += ($commission_amount > 0) ? $commission_amount : 0 ;
											$commission = ($commission_amount > 0) ? $commission_amount : 0 ;
										}else if($commission_scheme == "as percentage to"){ // check if as percentage to
											if($commission_percentage == "hourly rate"){ // for hourly percentage
												$total_employee_commission += $emp_hourly_rate * ($commission_percentage_rate / 100);
												$commission = $emp_hourly_rate * ($commission_percentage_rate / 100);
											}else if($commission_percentage == "daily rate"){ // for daily rate
												$total_employee_commission += ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
												$commission = ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
											}
										}
									}
										
									// FOR FORTNIGHTLY
									elseif (
											($employee_period_type == "Fortnightly" && $commission_pay_schedule == 'per payroll')
											||
											($commission_pay_schedule == 'last payroll' && $check_fortnightly_last_payroll != FALSE && $employee_period_type == "Fortnightly")
									) {
			
										if($commission_scheme == "set amount"){ // check if set amount value
											$total_employee_commission += ($commission_amount > 0) ? $commission_amount : 0 ;
											$commission = ($commission_amount > 0) ? $commission_amount : 0 ;
										}else if($commission_scheme == "as percentage to"){ // check if as percentage to
											if($commission_percentage == "hourly rate"){ // for hourly percentage
												$total_employee_commission += $emp_hourly_rate * ($commission_percentage_rate / 100);
												$commission = $emp_hourly_rate * ($commission_percentage_rate / 100);
											}else if($commission_percentage == "daily rate"){ // for daily rate
												$total_employee_commission += ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
												$commission = ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
											}
										}
			
									}
										
						}
					}elseif($commission_schedule == "one-time"){
						if(strtotime($start) <= strtotime($commission_schedule_date) && strtotime($commission_schedule_date) <= strtotime($end)){
							if($commission_scheme == "set amount"){ // check if set amount value
								$total_employee_commission += ($commission_amount > 0) ? $commission_amount : 0 ;
								$commission = ($commission_amount > 0) ? $commission_amount : 0 ;
			
								$commission_flag_one_time = TRUE;
							}else if($commission_scheme == "as percentage to"){ // check if as percentage to
								if($commission_percentage == "hourly rate"){ // for hourly percentage
									$total_employee_commission += $emp_hourly_rate * ($commission_percentage_rate / 100);
									$commission = $emp_hourly_rate * ($commission_percentage_rate / 100);
										
									$commission_flag_one_time = TRUE;
								}else if($commission_percentage == "daily rate"){ // for daily rate
									$total_employee_commission += ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
									$commission = ($emp_hourly_rate * 8) * ($commission_percentage_rate / 100);
										
									$commission_flag_one_time = TRUE;
								}
							}
						}
					}
						
					$commission = ($commission > 0) ? number_format($commission,2) : 0 ;
						
					#print "<li>{$commission}</li>";
					
					array_push($your_commission_amount,$commission);
				}
			}
			
			$your_commission = array();
			foreach($your_commission_name as $key=>$val){
				$comm = array($val,$your_commission_amount[$key]);
				array_push($your_commission, (object) $comm);
			}
			
			// TOTAL COMMISSIONS
			$total_employee_commission = ($total_employee_commission > 0) ? $total_employee_commission : 0 ;
				
			// get commission (end here)
			
			$regular_hours = 0;
			if($generate_printable_payslip){			
				 
				foreach($generate_printable_payslip as $gen_payslip){
					$reg_deduction = $gen_payslip->absences + $gen_payslip->tardiness_pay + $gen_payslip->undertime_pay;
					 
					$tmp_regular_hours = 0;
					$regular_hours = 0;
					$reg_hrs = $this->pp->get_regular_hours($this->emp_id,$get_current_payslip->payroll_payslip_id,$payroll_date,$period_from,$period_to,$this->company_id);
					if($reg_hrs) {
						foreach($reg_hrs as $row_reg_hrs){
							if($row_reg_hrs->flag_fortnightly == 1){
								foreach($reg_hrs as $row_reg_hrs){
									$tmp_regular_hours = $tmp_regular_hours + $row_reg_hrs->fortnightly_amount;
								}
							}else{
								$tmp_regular_hours = $gen_payslip->basic_pay;
							}
						}
					}else{
						$tmp_regular_hours = $gen_payslip->basic_pay;
					}
					$regular_hours =  $tmp_regular_hours - $reg_deduction;
				}
			}
			
			// get other earnings (start here)
			$earnings_first_column = array();
			$earnings_amount_column = array();
			$employee_other_earnings = 0;
			$total_amount_other_earnings = '0';
			
			// GET EMPLOYEE EARNINGS
			$employee_earnigs = $this->prm->employee_other_earnings_lite($this->emp_id,$this->company_id);
				
			if($employee_earnigs != FALSE){
					
				// PAYROLL DATE
				$payroll_date = date("Y-m-d",strtotime($this->input->get("pay_period"))); // date
			
				foreach($employee_earnigs as $row_dd_emp){
						
					// CHECK TOTAL AMOUNT FOR OTHER EARNINGS
					$earnings = $row_dd_emp->earning_id;
					$check_total_amount_other_earnings = $this->prm->check_total_amount_other_earnings_lite($this->emp_id,$this->company_id,$earnings,$param_data);
					$check_total_amount_other_earnings = ($check_total_amount_other_earnings > 0) ? $check_total_amount_other_earnings : 0 ;
						
					$dd_flag = 0;
					$dd_type = $row_dd_emp->type;
					$dd_priority = $row_dd_emp->priority;
					$dd_amount = $row_dd_emp->amount;
					$dd_sdate = $row_dd_emp->start_date;
					$dd_edate = $row_dd_emp->end_date;
						
					if($row_dd_emp->recurring == "No"){
						// $dd_flag = 1;
						if($check_total_amount_other_earnings == 0) $dd_flag = 1;
					}else{
						if($dd_type == "Interval" && $dd_sdate != "" && $dd_edate != ""){
							if(strtotime($dd_sdate) <= strtotime($payroll->payroll_period) && strtotime($payroll->payroll_period) <= strtotime($dd_edate)) $dd_flag = 1;
						}elseif($dd_type == "Occurence" && $dd_sdate != ""){
							if(strtotime($payroll->payroll_period) >= strtotime($dd_sdate)){
								// if($row_dd_emp->no_of_occurence > 0) $dd_flag = 1;
								// if($row_dd_emp->no_of_occurence > $check_total_amount_other_earnings) $dd_flag = 1; // 2 > 2 including current payroll
			
								if($row_dd_emp->earning_frequency == "Daily" || $row_dd_emp->earning_frequency == "Weekly"){
									$dd_flag = 1;
								}else{
									if($row_dd_emp->no_of_occurence > $check_total_amount_other_earnings) $dd_flag = 1; // 2 > 2 including current payroll
								}
			
							}
						}elseif($dd_type == ""){
							if(strtotime($payroll->payroll_period) >= strtotime($dd_sdate)){
								$dd_flag = 1;
							}
						}
					}
						
					// $dd_flag = ($dd_flag == 1 && ($dd_priority == "Every Payroll" || ($dd_priority == "First Payroll" && $q1_period == "1") || ($dd_priority == "Second Payroll" && $q1_period == "2"))) ? 1 : 0 ;
					if(
							(
									$dd_flag == 1 &&
									(
											$dd_priority == "Every Payroll"
											||
											($dd_priority == "First Payroll" && $q1_period == "1")
											||
											($dd_priority == "Second Payroll" && $q1_period == "2")
											||
											(($employee_period_type == "Fortnightly" && $dd_priority == 'Every Payroll')
													||
													($dd_priority == 'Last Payroll' && $check_fortnightly_last_payroll != FALSE && $employee_period_type == "Fortnightly"))
									)
							)
					){
						$dd_flag = 1;
					}else{
						$dd_flag = 0;
					}
						
					if($dd_flag == 1) {
			
						// CHECK IF EARNINGS IF TAXABLE
						// if($row_dd_emp->tax_exemption == "not_exempt") $employee_other_earnings_taxable += $dd_amount;
			
						if($row_dd_emp->recurring == "Yes"){
								
							// CHECK Earning Frequency
							$earning_frequency = $row_dd_emp->earning_frequency;
								
							$current = $start;
			
							$flag_to_add = TRUE;
							switch ($earning_frequency){
			
								case "Daily":
										
									// CHECK START DATE
									$earnings_start_date = (strtotime($current) <= strtotime($dd_sdate) && strtotime($dd_sdate) <= strtotime($end)) ? $dd_sdate : $current ;
									$emp_total_days_time_in = $this->prm->emp_total_days_time_in($this->company_id,$this->emp_id,$earnings_start_date,$end);
			
									// NO OF OCCURENCES
									$check_no_of_occurences_paid = $this->prm->check_no_of_occurences_paid($period_from,$period_to,$pay_period,$row_dd_emp->earning_id,$this->company_id,$this->emp_id);
									if($check_no_of_occurences_paid !=  FALSE){
										$earnings_no_occurences = $row_dd_emp->no_of_occurence - $check_no_of_occurences_paid;
										$earnings_no_occurences = ($earnings_no_occurences > 0) ? $earnings_no_occurences : 0 ;
									}else{
										$earnings_no_occurences = $row_dd_emp->no_of_occurence;
									}
										
									if($dd_type == "Occurence"){
										if($earnings_no_occurences > 0){
											// CHECK OCCURENCES
											if($row_dd_emp->recurring == "Yes" && $dd_type == "Occurence"){
												if($earnings_no_occurences <= $emp_total_days_time_in){
													$earnings_new_total_days_time_in = $earnings_no_occurences;
												}elseif($emp_total_days_time_in <= $earnings_no_occurences){
													$earnings_new_total_days_time_in = $emp_total_days_time_in;
												}
											}
			
											$dd_amount = $dd_amount * $earnings_new_total_days_time_in;
										}else{
											$flag_to_add = FALSE;
										}
									}else{
										$dd_amount = $dd_amount * $emp_total_days_time_in;
									}
										
									break;
			
								case "Weekly":
										
									$total_week = 0;
									for($var = 1; $var <= 4; $var++){
										$new_start_date = date("Y-m-d",strtotime($current." +{$var} week"));
										if(strtotime($current) <= strtotime($new_start_date) && strtotime($new_start_date) <= strtotime($end)){
											$total_week++;
										}
									}
										
									// NO OF OCCURENCES
									$check_no_of_occurences_paid = $this->prm->check_no_of_occurences_paid($period_from,$period_to,$pay_period,$row_dd_emp->earning_id,$this->company_id,$this->emp_id);
									if($check_no_of_occurences_paid !=  FALSE){
										$earnings_no_occurences = $row_dd_emp->no_of_occurence - $check_no_of_occurences_paid;
										$earnings_no_occurences = ($earnings_no_occurences > 0) ? $earnings_no_occurences : 0 ;
									}else{
										$earnings_no_occurences = $row_dd_emp->no_of_occurence;
									}
			
									if($earnings_no_occurences > 0){
										// CHECK OCCURENCES
										if($row_dd_emp->recurring == "Yes" && $dd_type == "Occurence"){
											if($earnings_no_occurences <= $total_week){
												$dd_amount = $dd_amount * $earnings_no_occurences;
											}elseif($total_week <= $earnings_no_occurences){
												$dd_amount = $dd_amount * $total_week;
											}
										}
									}else{
										$flag_to_add = FALSE;
									}
										
									break;
			
								case "Semi-Monthly":
										
									$flag_earnings_amount = FALSE;
									// for($var = 15; $var <= 1440; $var++){ // 15 days * 2 * 12 months * 4 years = 1440
									for($var = 0; $var <= 1440; $var = $var + 15){ // 15 days * 2 * 12 months * 4 years = 1440
										$new_start_date = date("Y-m-d",strtotime($dd_sdate." +{$var} days"));
										if(strtotime($current) <= strtotime($new_start_date) && strtotime($new_start_date) <= strtotime($end)){
											$dd_amount = $dd_amount;
											$flag_earnings_amount = TRUE;
										}
									}
										
									if($dd_priority == "First Payroll" || $dd_priority == "Second Payroll"){
										$dd_amount = $dd_amount * 2;
									}
										
									if(!$flag_earnings_amount) $dd_amount = 0;
										
									break;
										
								case "Monthly":
										
									$dd_amount = $dd_amount;
									break;
			
								case "Quarterly":
										
									$ear_year = date("Y",strtotime($payroll_date));
									$ear_month = date("m",strtotime($payroll_date));
									$ear_day = date("d",strtotime($dd_sdate));
										
									// 48 months or 4 year;
									// 4 is equal to every 4 months or quarterly
									for($var = 4; $var <= 48; $var = $var + 4){
										$new_start_date = date("Y-m-d",strtotime($dd_sdate." +{$var} months"));
										if(strtotime($current) <= strtotime($new_start_date) && strtotime($new_start_date) <= strtotime($end)){
											$dd_amount = $dd_amount;
										}
									}
										
									break;
										
								case "Semi-Annually":
										
									$ear_year = date("Y",strtotime($payroll_date));
									$ear_month = date("m",strtotime($payroll_date));
									$ear_day = date("d",strtotime($dd_sdate));
										
									// 48 months or 4 year;
									// 6 is equal to every 6 months or semi annual
									for($var = 6; $var <= 48; $var = $var + 6){
										$new_start_date = date("Y-m-d",strtotime($dd_sdate." +{$var} months"));
										if(strtotime($current) <= strtotime($new_start_date) && strtotime($new_start_date) <= strtotime($end)){
											$dd_amount = $dd_amount;
										}
									}
										
									break;
			
								case "Annually":
										
									$ear_year = date("Y",strtotime($payroll_date));
									$ear_month = date("m",strtotime($payroll_date));
									$ear_day = date("d",strtotime($dd_sdate));
			
									// 48 months or 4 year;
									$flag_earnings_amount = FALSE;
									for($var = 1; $var <= 4; $var++){
										$new_start_date = date("Y-m-d",strtotime($dd_sdate." +{$var} year"));
										if(strtotime($current) <= strtotime($new_start_date) && strtotime($new_start_date) <= strtotime($end)){
											$dd_amount = $dd_amount;
											$flag_earnings_amount = TRUE;
										}
									}
									if(!$flag_earnings_amount) $dd_amount = 0;
										
									break;
			
								default:
										
									$dd_amount = 0;
										
									break;
										
							}
						}
			
						if($row_dd_emp->tax_exemption == "Not Exempt"){
							$employee_other_earnings_taxable += $dd_amount;
						}else{
							$employee_other_earnings_non_taxable += $dd_amount;
						}
			
						$employee_other_earnings += $dd_amount;
			
						if($dd_amount > 0 && $flag_to_add){
							array_push($earnings_first_column,$row_dd_emp->name);
							array_push($earnings_amount_column,$dd_amount);
						}
			
					}
						
				}
					
			}
			

			
			$your_other_earnings_name = array();
			foreach($earnings_first_column as $key => $val){
				#print "<li>{$earnings_first_column[$key]}</li>";
				array_push($your_other_earnings_name,$earnings_first_column[$key]);
			}
			
			$your_other_earnings_amount = array();
			foreach($earnings_amount_column as $key => $val){
				#print "<li>".number_format($earnings_amount_column[$key],2)."</li>";
				array_push($your_other_earnings_amount,number_format($earnings_amount_column[$key],2));
			}
			
			$your_other_earnings = array();
			foreach($your_other_earnings_name as $key=>$val){
				$other = array($val,$your_other_earnings_amount[$key]);
				array_push($your_other_earnings, (object) $other);
			}
			
			// get other earnings (end here)
			
			//get Withholding Tax (Fixed)
			
			$wttax_fixed_amount = 0;
			$wttax_fixed_amount = $this->employee->wt_fixed($this->company_id, $this->emp_id, $pay_period, $period_from, $period_to);
			$tax_fixed_amount = 0;
			if($wttax_fixed_amount) {
				  $tax_fixed_amount = $wttax_fixed_amount;
			}
			
			// get basic pay
			// EMPLOYEE RATE
			$employee_rate = $payroll_run_details->rate;
			$employee_rate = ($employee_rate > 0) ? $employee_rate : 0 ;
				
			// EMPLOYEE BASIC PAY CHECK EMPLOYEE PERIOD TYPE
			$basic_pay = 0;
			$check_employee_period_type = $this->prm->check_employee_period_type($this->emp_id,$this->company_id);
			if($check_employee_period_type != FALSE){
			
				$employee_period_type = $check_employee_period_type->period_type;
				$employee_pay_rate_type = $check_employee_period_type->pay_rate_type;
			
				if(
						($employee_period_type == "Semi Monthly" && $employee_pay_rate_type == "By Hour")
						||
						($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Hour")
				){ // hourly
						
					$total_hours = 0;
						
					// get number of days
					$current = $period_from;
					$end = $period_to;
					while($current <= $end){
			
						$weekday = date('l',strtotime($current));
						$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$current);
						$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
			
						// check if not rest day
						if(!$rest_day){
							$check_employee_time_in = $this->prm->check_employee_time_in_by_hourly($this->company_id,$this->emp_id,$current);
							if($check_employee_time_in){
			
								foreach($check_employee_time_in as $row_timein_hourly){
									$workday = $this->prm->get_workday($this->company_id,$work_schedule_id->work_schedule_id);
									if($workday != FALSE){
										if($workday->workday_type == "Flexible Hours"){
											$total_hours += ($row_timein_hourly->total_hours_required > 0) ? $row_timein_hourly->total_hours_required : 0 ;
										}else{
											$total_hours += ($row_timein_hourly->total_hours > 0) ? $row_timein_hourly->total_hours : 0 ;
										}
									}
								}
			
							}
						}
			
						$current = date("Y-m-d",strtotime($current." +1 day"));
					}
						
					$basic_pay = $employee_rate * $total_hours;
						
				}else if(
						($employee_period_type == "Semi Monthly" && $employee_pay_rate_type == "By Day")
						||
						($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Day")
						||
						($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Month")
				){ // daily
						
					$total_days = 0;
						
					// get number of days
					$current = $period_from;
					$end = $period_to;
					while($current <= $end){
			
						$weekday = date('l',strtotime($current));
						$work_schedule_id = $this->prm->work_schedule_id($this->company_id,$this->emp_id,$current);
						$rest_day = $this->prm->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
			
						// check if not rest day
						if(!$rest_day){
							$check_employee_time_in = $this->prm->check_employee_time_in($this->company_id,$this->emp_id,$current);
							if($check_employee_time_in){
								$total_days++;
							}
						}
			
						$current = date("Y-m-d",strtotime($current." +1 day"));
					}
						
					$basic_pay = $employee_rate * $total_days; // add total days rendered
					$basic_pay += $total_amount_paid_leave; // add total leave amount
					// $basic_pay += $total_amount_render_for_daily_holiday; // add total holidays with greater than 100%
				}else if($employee_period_type == "Semi Monthly" && $employee_pay_rate_type == "By Month"){ // month
					$basic_pay = $employee_rate / 2;
				}else {
					$basic_pay = 0;
				}
			}
			
			if($get_current_payslip->service_charge_non_taxable == 0){
				$service_charge_allocation_amount = $get_current_payslip->service_charge_taxable;
			}else{
				$service_charge_allocation_amount = $get_current_payslip->service_charge_non_taxable;
			}
			
			$payroll_deminimis = $this->epsm->get_payroll_deminimis($this->emp_id,$draft_pay_run_id,$pay_period,$period_from,$period_to,$this->company_id);
			
			$new_total_amount_de_minimis = 0;
			$new_total_amount_de_minimis_ex = 0;
			$dem_arr = array();
			if($payroll_deminimis){
				foreach($payroll_deminimis as $row_payroll_deminimis){
					if($row_payroll_deminimis->rules == "Add to Non Taxable Income"){
						$type = $row_payroll_deminimis->description;
						$excess = $row_payroll_deminimis->description;
						$deminimis_amount = $row_payroll_deminimis->amount;
						$deminimis_excess = $row_payroll_deminimis->excess;
							
						$new_total_amount_de_minimis += $row_payroll_deminimis->amount;
						$new_total_amount_de_minimis_ex += $row_payroll_deminimis->excess;
			
						$dem_temp = array(
								"deminimis" => $type,
								"excess" => "- Excess : ".$excess,
								"deminimis_amnt" => $deminimis_amount,
								"excess_amnt" => $deminimis_excess
									
						);
							
						array_push($dem_arr,$dem_temp);
					}
				}
			}
			
			$new_total_deminimis = 0;
			$new_total_deminimis = $new_total_amount_de_minimis + $new_total_amount_de_minimis_ex;
			#$new_total_deminimis = number_format($new_total_deminimis,2);
			
			$payroll_other_earnings = $this->epsm->get_payroll_earnings($this->emp_id,$draft_pay_run_id_new,$pay_period,$period_from,$period_to,$this->company_id);
			$other_ear_arr = array();
			$total_amount_other_earnings = '0.00';
			if($payroll_other_earnings){
				
				$tmps_this = 0;
				foreach($payroll_other_earnings as $row_payroll_other_earnings){
					$tmps_this += $row_payroll_other_earnings->pol_amount;
				}
				if($tmps_this == ""){
					$total_amount_other_earnings = '0.00';
				}else{
					$total_amount_other_earnings = $tmps_this;
				}
				 
				foreach($payroll_other_earnings as $row_other_earnings){
					$other_ear_arr_t = array(
							"other_earnings_name" => $row_other_earnings->pol_name,
							"amount" => $row_other_earnings->pol_amount
					);
					 
					array_push($other_ear_arr,$other_ear_arr_t);
				}
			}	
		    
		    // EMPLOYEE TOTAL LOAN AMOUNT (GOVERNMENT LOANS & THIRD PARTY)
		    $employee_total_loan_amount_t = 0;
		    	
		    $third_party_loans = $this->prm->employee_loans($this->company_id);
		    $third_party_loans_arr = array();
		    if($third_party_loans != FALSE){
		    	foreach($third_party_loans as $row_third_party_loans){
		    		$get_employee_loans = $this->prm->get_employee_loans($this->emp_id,$row_third_party_loans->loan_type_id,$period_from,$period_to,$pay_period);
		    		if($get_employee_loans != FALSE){
		    
		    			foreach($get_employee_loans as $row_emp_loans){
		    				if($row_emp_loans->installment > 0){
		    					$employee_total_loan_amount_t += $row_emp_loans->installment;
		    					$loan_type_name = $row_third_party_loans->loan_type_name;
		    					$installment = number_format( $row_emp_loans->installment,2);
		    				}else{
		    					$employee_total_loan_amount_t = 0;
		    					$loan_type_name = $row_third_party_loans->loan_type_name;
		    					$installment = 0;
		    				}
		    
		    				$thrd_temp = array(
		    						"gov_loan_name" => $loan_type_name,
		    						"installment" => $installment
		    				);
		    
		    				array_push($third_party_loans_arr,$thrd_temp);
		    			}
		    		}
		    	}
		    }
		    
		    // EMPLOYEE GOVERNMENT LOANS
		    $employee_total_loan_amount_g = 0;
		    
		    $government_loans = $this->prm->employee_government_loans($this->company_id);
		    $government_loans_arr = array();
		    if($government_loans != FALSE){
		    	foreach($government_loans as $row_government_loans){
		    		$government_loans_amount = $this->prm->get_employee_government_loans($this->emp_id,$row_government_loans->loan_type_id,$period_from,$period_to,$pay_period);
		    		if($government_loans_amount != FALSE){
		    
		    			$government_loans_amount_result = 0;
		    			foreach($government_loans_amount as $row_gl){
		    				if($row_gl->amount > 0){
		    					$employee_total_loan_amount_g += $row_gl->amount;
		    					$loan_type_name_g = $row_government_loans->loan_type_name;
		    					$amount = number_format($row_gl->amount,2);
		    				}
		    
		    				$gov_temp = array(
		    						"gov_loan_name" => $loan_type_name_g,
		    						"amount" => $amount
		    				);
		    
		    				array_push($government_loans_arr,$gov_temp);
		    			}
		    		}
		    	}
		    }
		    
		    // OTHER DEDUCTIONS
		    $deductions_other_deductions = $this->prm->deductions_other_deductions($this->company_id);
		    $employee_other_deductions = 0;
		    $deductions_other_deductions_arr = array();
		    if($deductions_other_deductions != FALSE){
		    	foreach($deductions_other_deductions as $row_deductions_other_deductions){
		    		$other_deduction_amount = $this->prm->get_employee_other_deductions($this->emp_id,$row_deductions_other_deductions->deductions_other_deductions_id,$period_from,$period_to,$pay_period);
		    		if($other_deduction_amount != FALSE){
		    
		    			$other_deduction_val = 0;
		    
		    			foreach($other_deduction_amount as $row_oo){
		    				if($row_oo->amount > 0){
		    					$employee_other_deductions += $row_oo->amount;
		    					$name = $row_deductions_other_deductions->name;
		    					$amount = number_format($row_oo->amount,2);
		    				} else {
		    					$name = "";
		    					$amount = 0;
		    				}
		    
		    				$eod_temp = array(
		    						"name" => $name,
		    						"amount" => $amount
		    				);
		    
		    				array_push($deductions_other_deductions_arr,$eod_temp);
		    			}
		    
		    		}
		    	}
		    }
		    
		    $total_adjustment_amount = 0;
		    
		    // ABSENCES ADJUSTMENT
		    $adjustment_absences_amount = 0;
		    $adjustment_absences = $this->prm->adjustment_absences($this->emp_id,$this->company_id,$param_data);
		    if($adjustment_absences != FALSE){
		    	foreach($adjustment_absences as $row){
		    		$date_format = ($row->date != "") ? date("m/d/Y",strtotime($row->date)) : "" ;
		    		$absences_amount = number_format($row->amount,2);
		    
		    		$adjustment_absences_amount += $row->amount;
		    	}
		    }
		    
		    // TARDINESS ADJUSTMENT
		    $adjustment_tardiness_amount = 0;
		    $adjustment_tardiness = $this->prm->adjustment_tardiness($this->emp_id,$this->company_id,$param_data);
		    if($adjustment_tardiness != FALSE){
		    	foreach($adjustment_tardiness as $row){
		    		$date_format = ($row->date != "") ? date("m/d/Y",strtotime($row->date)) : "" ;
		    		$tardiness_amount = number_format($row->amount,2);
		    
		    		$adjustment_tardiness_amount += $row->amount;
		    	}
		    }
		    
		    // UNDERTIME ADJUSTMENT
		    $adjustment_undertime_amount = 0;
		    $adjustment_undertime = $this->prm->adjustment_undertime($this->emp_id,$this->company_id,$param_data);
		    if($adjustment_undertime != FALSE){
		    	foreach($adjustment_undertime as $row){
		    		$date_format = ($row->date != "") ? date("m/d/Y",strtotime($row->date)) : "" ;
		    		$undertime_amount = number_format($row->amount,2);
		    
		    		$adjustment_undertime_amount += $row->amount;
		    	}
		    }
		    
		    // ADJUSTMENT FOR THIRD PARTY LOANS
		    $adjustment_third_party_loans_amount = 0;
		    $adjustment_third_party_loans = $this->prm->adjustment_third_party_loans($this->emp_id,$this->company_id,$param_data);
		    $adjustment_third_party_loans_arr = array();
		    if($adjustment_third_party_loans != FALSE){
		    	foreach($adjustment_third_party_loans as $row){
		    		$loan_type_name = $row->loan_type_name;
		    		$third_party_loans_amount = number_format($row->amount,2);
		    
		    		$adjustment_third_party_loans_amount += $row->amount;
		    
		    		$atpl_temp = array(
		    				"name" => $loan_type_name,
		    				"amount" => $third_party_loans_amount,
		    				'total_amount' => $adjustment_third_party_loans_amount
		    		);
		    
		    		array_push($adjustment_third_party_loans_arr,$atpl_temp);
		    	}
		    }
		    
		    // ADJUSTMENT FOR GOVERNMENT LOANS
		    $adjustment_government_loans_amount = 0;
		    $adjustment_government_loans = $this->prm->adjustment_government_loans($this->emp_id,$this->company_id,$param_data);
		    $adjustment_government_loans_arr = array();
		    if($adjustment_government_loans != FALSE){
		    	foreach($adjustment_government_loans as $row){
		    		$loan_type_name = $row->loan_type_name;
		    		$government_loans_amount = number_format($row->amount,2);
		    
		    		$adjustment_government_loans_amount += $row->amount;
		    
		    		$agl_temp = array(
		    				"name" => $loan_type_name,
		    				"amount" => $government_loans_amount,
		    				'total_amount' => $adjustment_government_loans_amount
		    		);
		    
		    		array_push($adjustment_government_loans_arr,$agl_temp);
		    	}
		    }
		    
		    // ADJUSTMENT FOR OTHER DEDUCTIONS
		    $adjustment_other_deductions_amount = 0;
		    $adjustment_other_deductions = $this->prm->adjustment_other_deductions($this->emp_id,$this->company_id,$param_data);
		    $adjustment_other_deductions_arr = array();
		    if($adjustment_other_deductions != FALSE){
		    	foreach($adjustment_other_deductions as $row){
		    		$deduction_type_name = $row->deduction_type_name;
		    		$other_deductions_amount = number_format($row->amount,2);
		    
		    		$adjustment_other_deductions_amount += $row->amount;
		    
		    		$aod_temp = array(
		    				"name" => $deduction_type_name,
		    				"amount" => $other_deductions_amount,
		    				"total_amount" => $adjustment_other_deductions_amount
		    		);
		    
		    		array_push($adjustment_other_deductions_arr,$aod_temp);
		    	}
		    }
		    
		    $payroll_deduction = $this->epsm->get_other_deduction($this->emp_id,$draft_pay_run_id,$pay_period,$period_from,$period_to,$this->company_id);
		    $employee_other_deductions1 = 0;
		    $payroll_deduction_arr = array();
		    if($payroll_deduction){
		    	foreach($payroll_deduction as $row_payroll_deduction){
		    		$employee_other_deductions1 += $row_payroll_deduction->amount;
		    		$deduction_name = $row_payroll_deduction->deduction_name;
		    		$amount = number_format($row_payroll_deduction->amount,2);
		    
		    		$pda_temp = array(
		    				"name" => $deduction_name,
		    				"amount" => $amount,
		    				"total_amount" => $employee_other_deductions1
		    		);
		    
		    		array_push($payroll_deduction_arr,$pda_temp);
		    	}
		    }
		    
		    $total_amnt_other_deduc = $adjustment_absences_amount + $adjustment_tardiness_amount + $adjustment_undertime_amount +
		    $adjustment_third_party_loans_amount + $adjustment_government_loans_amount +
		    $adjustment_other_deductions_amount;
		    
		    $all_other_deductions = array(
		    		"absences" => $adjustment_absences_amount,
		    		"tardiness" => $adjustment_tardiness_amount,
		    		"undertime" => $adjustment_undertime_amount,
		    		"thrd_pt_loan" => $adjustment_third_party_loans_arr,
		    		"gov_loan" => $adjustment_government_loans_arr,
		    		"other_deduc" => $adjustment_other_deductions_arr,
		    		"employee_other_deductions" => $payroll_deduction_arr,
		    		"total_amnt_other_adj" => $total_amnt_other_deduc
		    );
		    
		    
		    // INSURANCE
		    $payroll_insurance = $this->epsm->get_payroll_insurance($this->emp_id,$draft_pay_run_id,$pay_period,$period_from,$period_to,$this->company_id);
		    $insurance_amount = 0;
		    $insurance_display_amount2 = 0;
		    $payroll_insurance_arr = array();
		    if($payroll_insurance){
		    	foreach ($payroll_insurance as $row_insurance){
		    		$insurance_amount = number_format($row_insurance->amount,2);
		    		$insurance_type = $row_insurance->insurance_type;
		    		
		    		$insurance_display_amount2 += $row_insurance->amount;
		    		
		    		$pia_temp = array(
		    				"name" => $insurance_type,
		    				"amount" => $insurance_amount,
		    				"total_amount" => $insurance_display_amount2
		    		);
		    		
		    		array_push($payroll_insurance_arr,$pia_temp);
		    	}
	    	}
		    	
		    
		    if($get_current_payslip->service_charge_non_taxable == 0){
		    	$service_charge_allocation_amount = $get_current_payslip->service_charge_taxable;
		    }else{
		    	$service_charge_allocation_amount = $get_current_payslip->service_charge_non_taxable;
		    }
		    
		    $total_emp_other_deductions = $employee_other_deductions + $employee_other_deductions1;
		    
		    $total_deductions = 0;
		    $total_deductions = $get_current_payslip->pp_withholding_tax + $get_current_payslip->pp_sss + $get_current_payslip->pp_philhealth + $get_current_payslip->pp_pagibig +
		    $employee_total_loan_amount_g + $employee_total_loan_amount_t + $tax_fixed_amount + $total_amnt_other_deduc + $total_emp_other_deductions + $insurance_display_amount2;
		    $total_deductions = ($total_deductions > 0) ? number_format($total_deductions,2) : number_format(0,2) ;
			
			$total_earnings1 = $basic_pay - $get_current_payslip->absences - $get_current_payslip->tardiness_pay - $get_current_payslip->undertime_pay +
			   $get_current_payslip->overtime_pay +
			   $get_current_payslip->sunday_holiday +
			   $get_current_payslip->night_diff +
			   $adjustment_total_amount +
			   $total_employee_allowances +
			   $total_employee_commission +
			   $total_amount_de_minimis +
			   $service_charge_allocation_amount +
			  # $total_amount_hazard_pay_display +
				$adjustment_hazard_pay_amount1 +
			  # $total_amount_other_earnings;
				$total_amount_other_earnings +
				$employee_other_earnings +
				$new_total_deminimis;
				#$leave_conversion_total_amount_val_non_taxable;
			   $total_earnings1 = ($total_earnings1 > 0) ? number_format($total_earnings1,2) : number_format(0,2) ;
			
			   $new_regular_hours = $regular_hours - $get_current_payslip->paid_leave_amount;
			   
			   // TOTAL HOURS
			   $total_amount_for_regualr_hours = $basic_pay - $get_current_payslip->absences - $get_current_payslip->tardiness_pay - $get_current_payslip->undertime_pay - $get_current_payslip->paid_leave_amount;
			   $total_amount_for_regualr_hours = ($total_amount_for_regualr_hours > 0) ? number_format($total_amount_for_regualr_hours,2) : number_format(0,2) ;
			$payslip = array(
					'period_from' => $get_current_payslip->period_from,
					'period_to' => $get_current_payslip->period_to,
					'payroll_date' => $get_current_payslip->payroll_date,
					'net_amount' => number_format($get_current_payslip->net_amount,2),
					'overtime_pay' => number_format($get_current_payslip->overtime_pay,2),
					'paid_leave_amount' => number_format($get_current_payslip->paid_leave_amount,2),
					'sunday_holiday' => number_format($get_current_payslip->sunday_holiday,2),
					'night_diff' => number_format($get_current_payslip->night_diff,2),
					'absences' => number_format($get_current_payslip->absences,2),
					'tardiness_pay' => number_format($get_current_payslip->tardiness_pay,2),
					'undertime_pay' => number_format($get_current_payslip->undertime_pay,2),
					'regular_hours' => number_format($new_regular_hours,2),
					'advance_payment' => $advance_payment,
					'workday' => $adj_workday,
					'employee_overtime' => $emp_ot,
					'employee_holiday' => $hol_prem,
					'employee_night_differential' => $nyt_diff,
					'employee_paid_leave' => $emp_paid_leave,
					'allowances' => $adj_allow,
					'commission' => $adj_comm,
					'adj_de_minimis' => $adj_deminimis,
					'de_minimis' => $dem_arr,
					'total_deminimis' => $new_total_deminimis,
					'service_charge' => $adj_serv_charge,
					'hazard_pay' => $adj_hazard,
					'adj_other_earnings' => $adj_other,
					'other_earnings' => $other_ear_arr,
					'total_adjustment_amount' => number_format($adjustment_total_amount,2),
					'your_allowances'=> $your_allowances,
					'total_allowance' => number_format($total_employee_allowances,2),
					'your_deminimis' => $you_deminimis,
					'total_deminimis' => number_format($new_total_deminimis,2),
					'your_commision' => $your_commission,
					'total_commission' => number_format($total_employee_commission,2),
					#'your_other-earnings' => $your_other_earnings,
					'your_other_earnings' => number_format($total_amount_other_earnings,2),
					'total_other_earnings' => number_format($employee_other_earnings,2),
					'pp_withholding_tax' => number_format($get_current_payslip->pp_withholding_tax,2),
					'pp_pagibig' => number_format($get_current_payslip->pp_pagibig,2),
					'pp_philhealth' => number_format($get_current_payslip->pp_philhealth,2),
					'pp_sss' => number_format($get_current_payslip->pp_sss,2),
					'tax_fixed_amount' => number_format($tax_fixed_amount,2),
					'total_earnings' => $total_earnings1,
					'total_amnt_thrd_pt_loan' => number_format($employee_total_loan_amount_t,2),
					'total_amnt_gov_loan' => number_format($employee_total_loan_amount_g,2),
					'thrd_pt_loan' => $third_party_loans_arr,
					'gov_loan' => $government_loans_arr,
					'total_emp_other_deductions' => number_format($total_emp_other_deductions,2),
					'deductions_other_deductions_arr' => $deductions_other_deductions_arr,
					'all_other_adj' => $all_other_deductions,
					'total_deductions' => $total_deductions,
					'total_amnt_insurance' => number_format($insurance_display_amount2,2),
					'insurance' => $payroll_insurance_arr,
					'total_amount_for_regualr_hours' => $total_amount_for_regualr_hours
			);
			
			$res = array(
					"result" => true,
					"latest_payslip" => $payslip
			);
			
			echo json_encode($res);
		} else {
			$res = array(
					"result" => false,
					"latest_payslip" => false
			);
				
			echo json_encode($res);
		}
	}
}