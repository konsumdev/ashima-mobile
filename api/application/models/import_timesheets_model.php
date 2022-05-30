<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 * Import_timesheets_model model
 * @author Christopher Cuizon
 *
 */
class Import_timesheets_model extends CI_Model {
		
	/**
	*	Get employee credentials if valid
	*	@param int $emp_id
	*	@param int $company_id
	*	@return 
	*/
	public function get_employee_credentials($payroll_cloud_id,$company_id,$last_name,$middle_name=NULL,$first_name,$project="",$location=""){
		if(is_numeric($company_id)){
			$where = array(
				'a.payroll_cloud_id' => trim($payroll_cloud_id),
				'e.company_id'		 => $company_id,
				'a.user_type_id'	 => '5',
				//'e.first_name'		 => $first_name,
				//'e.last_name'		 => $last_name,
				'e.status'			 => 'Active'
			);
			

			$this->edb->where($where);
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$query = $this->edb->get('employee AS e');
			$row = $query->row();
			$query->free_result();
			
			return $row;
		}else{
			return false;
		}
	}

	
	public function get_break_flex($comp_id,$work_schedule_id){
		$where_break_flex = array(
				"company_id" => $comp_id,
				"work_schedule_id" => $work_schedule_id,
		);
		
		$this->db->where($where_break_flex);
		$sql_break_flex = $this->db->get("flexible_hours");
		$row_break_flex = $sql_break_flex->row();
		
		if($sql_break_flex->num_rows() > 0){
			$breaktime_settings = $row_break_flex->duration_of_lunch_break_per_day; // convert to seconds
		} else{
			$breaktime_settings = 0;
		}	
		
		return $breaktime_settings;
	}
	
	/**
	*	CHECK EMPLOYEE CONFLICT
	*	@param int $payroll_group_id
	*	@param int $company_id
	*	@param string $_FILES
	*	@return array
	*/
	public function import($company_id,$csv) {
		if($company_id) {
			$file=fopen($csv,"r") or die("Exit") ;
			$row_start = 0;
			$conflict = array();
			$toboy = array();
			while(!feof($file)):
				$read_csv = fgetcsv($file);
				if($row_start > 0) {		
					if($read_csv[0] !="") {
						$payroll_cloud_id = $this->db->escape_str($read_csv[0]);
						$last_name = $this->db->escape_str($read_csv[1]);
						$middle_name = $this->db->escape_str($read_csv[2]);
						$first_name = $this->db->escape_str($read_csv[3]);
						$time_in = $this->db->escape_str($read_csv[4]);
						$lunch_out = $this->db->escape_str($read_csv[5]);
						$lunch_in = $this->db->escape_str($read_csv[6]);
						$time_out = $this->db->escape_str($read_csv[7]);		
						$check = $this->get_employee_credentials($read_csv[0],$company_id,$last_name,$middle_name,$first_name);
							if($lunch_out == "" &&  $lunch_in =="") {
								if($check) {
									$emp_id = $check->emp_id;
									$date = idate_convert($time_in);
									$check_timeins = $this->check_employee_timeins($company_id,$emp_id,$date); # CHECK IF TIME IN IS AVAILABLE
									# CODES GLOBAL FOR SAVE AMBOT 
									# line of sh code
										$hours_worked = $this->get_hours_worked(idate_convert($time_in),$emp_id);
										$total_hourswork = 0;
								
										$total_hourswork = $this->login_screen_model->get_tot_hours($emp_id,$company_id,idate_convert_full($time_in),idate_convert_full($time_out),$hours_worked);
										$total_hours_required = 0;
										$get_total_hours = (strtotime(idate_convert_full($time_out)) - strtotime(idate_convert_full($time_in))) / 3600;	
										$total_hours_required = $get_total_hours;
										# end line of SH
										
										# NEW CODE tardiness computation
											$update_tardiness = 0;
											$update_undertime = 0;
											$flag_tu = 0;
										
										
												# TARDINESS UPDATED CODES 
												// check number of breaks
													$employee_informations = $this->check_payroll_information($company_id,$emp_id);
													$number_of_breaks_per_day = 0;
													if($employee_informations) {
														# UNIFORM WORKING DAYS
															$w_uwd = array(
																"payroll_group_id"=>$employee_informations->payroll_group_id,
																"company_id"=>$company_id	
															);
															$this->db->where($w_uwd);
															$q_uwd = $this->db->get("uniform_working_day_settings");
															$r_uwd = $q_uwd->row();
															if($q_uwd->num_rows() > 0){
																$number_of_breaks_per_day = $r_uwd->number_of_breaks_per_day;
															}else{
																# WORKSHIFT SETTINGS
																$w_ws = array(
																	"payroll_group_id"=>$employee_informations->payroll_group_id,
																	"company_id"=>$company_id
																);
																$this->db->where($w_ws);
																$q_ws = $this->db->get("workshift_settings");
																$r_ws = $q_ws->row();
																if($q_ws->num_rows() > 0){
																	$number_of_breaks_per_day = $r_ws->number_of_breaks_per_shift;
																}else{
																	# FLEXIBLE HOURS
																	$w_fh = array(
																		"payroll_group_id"=>$employee_informations->payroll_group_id,
																		"company_id"=>$company_id
																	);
																	$this->db->where($w_fh);
																	$q_fh = $this->db->get("flexible_hours");
																	$r_fh = $q_fh->row();
																	if($q_fh->num_rows() > 0){
																		$number_of_breaks_per_day = $r_fh->number_of_breaks_per_day;
																	}
																}
															}

															// check if breaktime != 0
															if($number_of_breaks_per_day != 0 && $lunch_out == "" && $lunch_in == ""){
																$update_tardiness =  0;
																$update_undertime = 0;
															}else if($number_of_breaks_per_day == 0 && $lunch_out == "" && $lunch_in == ""){
																$update_tardiness = $this->login_screen_model->get_tardiness_val($emp_id,$company_id,idate_convert_full($time_in));
																$update_undertime = $this->login_screen_model->get_undertime_val($emp_id,$company_id,idate_convert_full($time_in),idate_convert_full($time_out));
															}
													}
											# END TARDINESS UPDATED CODES 
										
											$get_total_hours_worked = ($hours_worked / 2) + .5;
											if($total_hourswork <= $get_total_hours_worked && $total_hourswork !=0){
												$update_tardiness = 0;
												$update_undertime = 0;
												$flag_tu = 1;
											}
											
										# END NEW CODE	
									# END CODES GLOBAL FOR SAVE AMBOT 
									
									
									if($check_timeins) { # CHECK TIMEINS CODE HERE	
										# UPDATE CODES 
										$timein_field = array(
											"date"				=> idate_convert($time_in),
											"time_in"		=> idate_convert_full($time_in),
											"time_out"		=> idate_convert_full($time_out),
											"source"		=> "import",
											"total_hours" => $total_hourswork,
											"total_hours_required" => $total_hours_required,
											"tardiness_min"	=>$update_tardiness,
											"undertime_min" =>$update_undertime,
											"flag_tardiness_undertime"=>$flag_tu
										);		
										if($check_timeins->corrected == 'Yes') { # if corrected yes do not update 
										
										} else { 
											$toboy[] = idate_convert_full($time_out);
											$toboy[] = $timein_field;
											$where_timein_field = array(
												"comp_id" => $company_id,
												"emp_id"	=> $check_timeins->emp_id,
												"employee_time_in_id" => $check_timeins->employee_time_in_id
											);						
											$this->update_field("employee_time_in",$timein_field,$where_timein_field);
										}
										# END UPDATEDS
										
									} else {
									
										$save_timeinfield = array(
											"date"			=> idate_convert($time_in),
											"time_in"		=> idate_convert_full($time_in),
											"time_out"		=> idate_convert_full($time_out), 
											"source"		=> "import",
											"comp_id"		=> $company_id,
											"emp_id"		=> $emp_id,
											"total_hours" => $total_hourswork,
											"total_hours_required" => $total_hours_required,
											"tardiness_min"	=> $update_tardiness,
											"undertime_min" => $update_undertime,
											"flag_tardiness_undertime"=>$flag_tu
										);
										$toboy[] = idate_convert_full($time_out);
									
										$toboy[] = $save_timeinfield;
										$account = $this->save_field("employee_time_in",$save_timeinfield);						
									}
									
								}		
								
							} else {
						
								if($check) {
									$emp_id = $check->emp_id;
									$date = idate_convert($time_in);
									$check_timeins = $this->check_employee_timeins($company_id,$emp_id,$date); # CHECK IF TIME IN IS AVAILABLE
									if($check_timeins) {	
										$hours_worked = $this->get_hours_worked(idate_convert($time_in),$emp_id);
										$total_hourswork = 0;
										
										$total_hourswork = get_tot_hours($emp_id,$company_id,idate_convert_full($time_in),idate_convert_full($lunch_out),idate_convert_full($lunch_in),idate_convert_full($time_out),$hours_worked);
										$total_hours_required = 0;
										$total_hours_required = get_tot_hours_limit($emp_id,$company_id,idate_convert_full($time_in),idate_convert_full($lunch_out),idate_convert_full($lunch_in),idate_convert_full($time_out));
										$tardiness_timein = date("H:i:s",strtotime($time_in));
										$undertime_timeout = idate_convert_full($time_out);
										
										# NEW CODE tardiness computation
											$update_tardiness =  get_tardiness_import($emp_id,$company_id,idate_convert_full($time_in),date("H:i:s",strtotime($lunch_out)),date("H:i:s",strtotime($lunch_in)));
											$update_undertime = get_undertime_import($emp_id,$company_id,idate_convert_full($time_in),idate_convert_full($time_out),idate_convert_full($lunch_out),idate_convert_full($lunch_in));
											$flag_tu = 0;
										
										
											# TARDINESS UPDATED CODES 
												// check number of breaks
													$employee_informations = $this->check_payroll_information($company_id,$emp_id);
													$number_of_breaks_per_day = 0;
													if($employee_informations) {
														# UNIFORM WORKING DAYS
															$w_uwd = array(
																"payroll_group_id"=>$employee_informations->payroll_group_id,
																"company_id"=>$company_id	
															);
															$this->db->where($w_uwd);
															$q_uwd = $this->db->get("uniform_working_day_settings");
															$r_uwd = $q_uwd->row();
															if($q_uwd->num_rows() > 0){
																$number_of_breaks_per_day = $r_uwd->number_of_breaks_per_day;
															}else{
																# WORKSHIFT SETTINGS
																$w_ws = array(
																	"payroll_group_id"=>$employee_informations->payroll_group_id,
																	"company_id"=>$company_id
																);
																$this->db->where($w_ws);
																$q_ws = $this->db->get("workshift_settings");
																$r_ws = $q_ws->row();
																if($q_ws->num_rows() > 0){
																	$number_of_breaks_per_day = $r_ws->number_of_breaks_per_shift;
																}else{
																	# FLEXIBLE HOURS
																	$w_fh = array(
																		"payroll_group_id"=>$employee_informations->payroll_group_id,
																		"company_id"=>$company_id
																	);
																	$this->db->where($w_fh);
																	$q_fh = $this->db->get("flexible_hours");
																	$r_fh = $q_fh->row();
																	if($q_fh->num_rows() > 0){
																		$number_of_breaks_per_day = $r_fh->number_of_breaks_per_day;
																	}
																}
															}

															// check if breaktime != 0
															if($number_of_breaks_per_day != 0 && $lunch_out == "" && $lunch_in == ""){
																$update_tardiness =  0;
																$update_undertime = 0;
															}else if($number_of_breaks_per_day == 0 && $lunch_out == "" && $lunch_in == ""){
																$update_tardiness = $this->login_screen_model->get_tardiness_val($emp_id,$company_id,idate_convert_full($time_in));
																$update_undertime = $this->login_screen_model->get_undertime_val($emp_id,$company_id,idate_convert_full($time_in),idate_convert_full($time_out));
															}
													}
											# END TARDINESS UPDATED CODES 
										
											$get_total_hours_worked = ($hours_worked / 2) + .5;
											if($total_hourswork <= $get_total_hours_worked && $total_hourswork !=0){
												$update_tardiness = 0;
												$update_undertime = 0;
												$flag_tu = 1;
											}
											
										# END NEW CODE	
									
										$timein_field = array(
											"date"			=> idate_convert($time_in),
											"time_in"		=> idate_convert_full($time_in),
											"lunch_out"	=> idate_convert_full($lunch_out),
											"lunch_in"		=> idate_convert_full($lunch_in),
											"time_out"		=> idate_convert_full($time_out),
											"source"		=> "import",
											"total_hours" => $total_hourswork,
											"total_hours_required" => $total_hours_required,
										#	"tardiness_min"	=>  get_tardiness_import($emp_id,$company_id,idate_convert_full($time_in),date("H:i:s",strtotime($lunch_out)),date("H:i:s",strtotime($lunch_in))),
										#	"undertime_min" =>  get_undertime_import($emp_id,$company_id,idate_convert_full($time_in),idate_convert_full($time_out),idate_convert_full($lunch_out),idate_convert_full($lunch_in))
											"tardiness_min"	=>$update_tardiness,
											"undertime_min" =>$update_undertime,
											"flag_tardiness_undertime"=>$flag_tu
										);		
										if($check_timeins->corrected == 'Yes') { # if corrected yes do not update 
										
										} else { 
											$toboy[] = idate_convert_full($time_out);
											$toboy[] = $timein_field;
											$where_timein_field = array(
												"comp_id" => $company_id,
												"emp_id"	=> $check_timeins->emp_id,
												"employee_time_in_id" => $check_timeins->employee_time_in_id
											);						
											$this->update_field("employee_time_in",$timein_field,$where_timein_field);
										}
									} else {
										$hours_worked = $this->get_hours_worked(idate_convert($time_in),$emp_id);
										$total_hourswork = 0;
										$total_hours_required = 0;
										$total_hourswork = get_tot_hours($emp_id,$company_id,idate_convert_full($time_in),idate_convert_full($lunch_out),idate_convert_full($lunch_in),idate_convert_full($time_out),$hours_worked);
										
										$total_hours_required =	get_tot_hours_limit($emp_id,$company_id,idate_convert_full($time_in),idate_convert_full($lunch_out),idate_convert_full($lunch_in),idate_convert_full($time_out));
										$tardiness_timein = date("H:i:s",strtotime($time_in));
										$undertime_timeout = idate_convert_full($time_out);
									
										# NEW CODE tardiness computation
											$update_tardiness =  get_tardiness_import($emp_id,$company_id,idate_convert_full($time_in),date("H:i:s",strtotime($lunch_out)),date("H:i:s",strtotime($lunch_in)));
											$update_undertime = get_undertime_import($emp_id,$company_id,idate_convert_full($time_in),idate_convert_full($time_out),idate_convert_full($lunch_out),idate_convert_full($lunch_in));
											$flag_tu = 0;
										
											$get_total_hours_worked = ($hours_worked / 2) + .5;
											if($total_hourswork <= $get_total_hours_worked && $total_hourswork !=0){
												$update_tardiness = 0;
												$update_undertime = 0;
												$flag_tu = 1;
											}
												# TARDINESS UPDATED CODES 
												// check number of breaks
													$employee_informations = $this->check_payroll_information($company_id,$emp_id);
													$number_of_breaks_per_day = 0;
													if($employee_informations) {
														# UNIFORM WORKING DAYS
															$w_uwd = array(
																"payroll_group_id"=>$employee_informations->payroll_group_id,
																"company_id"=>$company_id	
															);
															$this->db->where($w_uwd);
															$q_uwd = $this->db->get("uniform_working_day_settings");
															$r_uwd = $q_uwd->row();
															if($q_uwd->num_rows() > 0){
																$number_of_breaks_per_day = $r_uwd->number_of_breaks_per_day;
															}else{
																# WORKSHIFT SETTINGS
																$w_ws = array(
																	"payroll_group_id"=>$employee_informations->payroll_group_id,
																	"company_id"=>$company_id
																);
																$this->db->where($w_ws);
																$q_ws = $this->db->get("workshift_settings");
																$r_ws = $q_ws->row();
																if($q_ws->num_rows() > 0){
																	$number_of_breaks_per_day = $r_ws->number_of_breaks_per_shift;
																}else{
																	# FLEXIBLE HOURS
																	$w_fh = array(
																		"payroll_group_id"=>$employee_informations->payroll_group_id,
																		"company_id"=>$company_id
																	);
																	$this->db->where($w_fh);
																	$q_fh = $this->db->get("flexible_hours");
																	$r_fh = $q_fh->row();
																	if($q_fh->num_rows() > 0){
																		$number_of_breaks_per_day = $r_fh->number_of_breaks_per_day;
																	}
																}
															}

															// check if breaktime != 0
															if($number_of_breaks_per_day != 0 && $lunch_out == "" && $lunch_in == ""){
																$update_tardiness =  0;
																$update_undertime = 0;
															}else if($number_of_breaks_per_day == 0 && $lunch_out == "" && $lunch_in == ""){
																$update_tardiness = $this->login_screen_model->get_tardiness_val($emp_id,$company_id,idate_convert_full($time_in));
																$update_undertime = $this->login_screen_model->get_undertime_val($emp_id,$company_id,idate_convert_full($time_in),idate_convert_full($time_out));
															}
													}
											# END TARDINESS UPDATED CODES 
											
										# END NEW CODE
										
										$save_timeinfield = array(
											"date"			=> idate_convert($time_in),
											"time_in"		=> idate_convert_full($time_in),
											"lunch_out" 	=> idate_convert_full($lunch_out),
											"lunch_in"		=> idate_convert_full($lunch_in),
											"time_out"		=> idate_convert_full($time_out), 
											"source"		=> "import",
											"comp_id"		=> $company_id,
											"emp_id"		=> $emp_id,
											"total_hours" => $total_hourswork,
											"total_hours_required" => $total_hours_required,
										//	"tardiness_min"	=> get_tardiness_import($emp_id,$company_id,idate_convert_full($time_in),date("H:i:s",strtotime($lunch_out)),date("H:i:s",strtotime($lunch_in))),
										//	"undertime_min" => get_undertime_import($emp_id,$company_id,idate_convert_full($time_in),idate_convert_full($time_out),idate_convert_full($lunch_out),idate_convert_full($lunch_in))
											"tardiness_min"	=> $update_tardiness,
											"undertime_min" => $update_undertime,
											"flag_tardiness_undertime"=>$flag_tu
										);
										$toboy[] = idate_convert_full($time_out);
									
										$toboy[] = $save_timeinfield;
										$account = $this->save_field("employee_time_in",$save_timeinfield);						
									}			
								} else {
								
								}
						}			
					}	
				}
				$row_start++;
			endwhile;
			fclose($file);
			return $toboy;
		} else {
			return false;
		}
	}
	
	
	public function validate_import($company_id,$csv) {
		if($company_id) {
			$file=fopen($csv,"r") or die("Exit") ;
			$row_start = 0;
			$conflict = array();
			$field_empty = array();
			while(!feof($file)):
				$read_csv = fgetcsv($file);	
				if($row_start > 0) {		
					$payroll_cloud_id = $this->db->escape_str(trim($read_csv[0]));
					$last_name = $this->db->escape_str(trim($read_csv[1]));
					$middle_name = $this->db->escape_str(trim($read_csv[2]));
					$first_name = $this->db->escape_str(trim($read_csv[3]));
					$time_in = $this->db->escape_str(trim($read_csv[4]));
					$lunch_out = $this->db->escape_str(trim($read_csv[5]));
					$lunch_in = $this->db->escape_str(trim($read_csv[6]));
					$time_out = $this->db->escape_str(trim($read_csv[7]));
					$date = idate_convert($time_in);			
					if($payroll_cloud_id !="" && $last_name !="" && $first_name !="" && $time_in  !="" && $lunch_out !="" && $lunch_in !="" &&  $time_out  !="") {
						$check = $this->get_employee_credentials($read_csv[0],$company_id,$last_name,$middle_name,$first_name);
						if($check) {
							$emp_id = $check->emp_id;
							$check_timeins = $this->check_employee_timeins($company_id,$emp_id,$date); # CHECK IF TIME IN IS AVAILABLE
							if($check_timeins) {
								$conflict[] = $check->first_name.' '.$check->last_name.' '.date("m/d/Y",strtotime($check_timeins->date));
							}	
						} else {
							$conflict[] = "Employee doesnt exist (".$payroll_cloud_id ." ".utf8_decode($first_name).' '.utf8_decode($last_name).' '.date("m/d/Y",strtotime($time_in)).")";
						}
					} else if(is_null($last_name)){
						echo "ilastname"; 
					}			
				}
				$row_start++;
			endwhile;
			fclose($file);
			return array("conflict"=>$conflict,"missing"=>$field_empty);
		} else {
			return false;
		}
	}
	
	
	public function test_validate($company_id,$csv) {
		if($company_id) {
			$file=fopen($csv,"r") or die("Exit") ;
			$row_start = 0;
			$conflict = array();
			$field_empty = array();
			$row_sheet= 2;
			$error = array();
			$count = 0;
			while(!feof($file)):
				$read_csv = fgetcsv($file);	
				$count = count($file);
				if($row_start > 0) {		
					$payroll_cloud_id = $this->db->escape_str(trim($read_csv[0]));
					$last_name = $this->db->escape_str(trim($read_csv[1]));
					$middle_name = $this->db->escape_str(trim($read_csv[2]));
					$first_name = $this->db->escape_str(trim($read_csv[3]));
					$time_in = $this->db->escape_str(trim($read_csv[4]));
					$lunch_out = $this->db->escape_str(trim($read_csv[5]));
					$lunch_in = $this->db->escape_str(trim($read_csv[6]));
					$time_out = $this->db->escape_str(trim($read_csv[7]));
					$date = idate_convert($time_in);			
						$row_error = $row_sheet++;
						if($payroll_cloud_id == "") $error[] = "Missing Employee ID on line Row [{$row_error}]";
						if($last_name == "") $error[] = "Missing Last Name on line Row [{$row_error}]";
						if($first_name == "") $error[] = "Missing First Name on line Row [{$row_error}]";
						if($time_in == "") $error[] = "Missing Time In on line Row [{$row_error}]";
						if($lunch_out == "") $error[] = "Missing Lunch Out on line Row [{$row_error}]";
						if($lunch_in == "") $error[] = "Missing Lunch In on line Row [{$row_error}]";
						if($time_out == "") $error[] = "Missing Time Out on line Row [{$row_error}]";		
						$check = $this->get_employee_credentials($read_csv[0],$company_id,$last_name,$middle_name,$first_name);
						if($check) {
							$emp_id = $check->emp_id;
							$check_timeins = $this->check_employee_timeins($company_id,$emp_id,$date); # CHECK IF TIME IN IS AVAILABLE
							if($check_timeins) {
								$conflict[] = $check->first_name.' '.$check->last_name.' '.date("m/d/Y",strtotime($check_timeins->date));
							}	
						} else {
							$conflict[] = "Employee doesnt exist (".$payroll_cloud_id ." ".$first_name.' '.$last_name.' '.date("m/d/Y",strtotime($time_in)).")";
						}
				}
				$row_start++;
			endwhile;
			fclose($file);
			return array("conflict"=>$conflict,"missing"=>$error,"count"=>$count,"new_while"=>$count,"row"=>$row_start);
		} else {
			return false;
		}
	}
	
	/**
	*	CHECK PAYROLL INFORMATION
	*	@param int $company 
	*	@param int $emp_id
	*	@return object
	*/
	public function check_payroll_information($company_id,$emp_id) {
		if(is_numeric($company_id) && is_numeric($emp_id)){
			$where = array(
					"company_id" => $company_id,
					"emp_id" => $emp_id
			);
			$this->db->where($where);
			$query = $this->db->get("employee_payroll_information");
			$row = $query->row();
			return $row;
		}else{
			return false;
		}
	}
	
	
	/**
	* Some instances we have not deleted csv on this section to further protection
	*	@param directory $file_path
	*	@return boolean
	*/
	public function delete_unwanted_csv($file_path){
		$unlink_csv = glob($file_path."*.csv");
		if($unlink_csv) {
			foreach($unlink_csv as $key):
				unlink($key);
			endforeach;
			return true;
		} else {
			return false;
		}
	}
	
	
	
	
	/**
	*  Save field
	*	@param string $table
	*	@param array $where
	* 	@return integer
	*/
	public function save_field($table,$where){
		$this->edb->insert($table,$where);
		return $this->db->insert_id();
	}
	
	/**
	*	Updates the databse and returns if valid
	*	@param string $table
	*	@param array $field
	*	@param array $field
	*	@return boolean
	*/
	public function update_field($table,$field,$where) {
		$this->edb->where($where);
		$this->edb->update($table,$field);
		return $this->db->affected_rows();
	}
	
	/**
	 * Get Hours Worked for workday
	 * @param unknown_type $workday
	 * @param unknown_type $emp_id
	 */
	public function get_hours_worked($workday, $emp_id){
		$CI =& get_instance();
		
		$mininum_wage_rate = "";
		$working_hours = "";
		$no_of_days = "";
		$regular_hourly_rate = "";
		
		// check if workday is workshit schedule
		$sql_workshift = $CI->db->query("
			SELECT *
			FROM `employee_time_in` eti
			LEFT JOIN workshift_settings ws ON eti.comp_id = ws.company_id
			LEFT JOIN workshift w ON ws.company_id = w.company_id
			WHERE eti.emp_id = '{$emp_id}'
			GROUP BY ws.total_working_days_per_year
			
		");
		
		$row_workshift = $sql_workshift->row();
		if($sql_workshift->num_rows() > 0){
			$sql_workshift->free_result();
			
			if($row_workshift->total_working_days_per_year != null || $row_workshift->working_hours != ""){
				// for workshift
				$no_of_days = $row_workshift->total_working_days_per_year / 12; // 12 = months
				$working_hours = $row_workshift->working_hours;
			}
		}
		
		// ==========================================
		
		// check if workday is flexible hours
		$sql_flexible_hours = $CI->db->query("
			SELECT *
			FROM `employee_time_in` eti
			LEFT JOIN flexible_hours fh ON eti.comp_id = fh.company_id
			WHERE eti.emp_id = '{$emp_id}'
			GROUP BY fh.total_days_per_year
		");
		
		$row_flexible_hours = $sql_flexible_hours->row();
		if($sql_flexible_hours->num_rows() > 0){
			$sql_flexible_hours->free_result();
			
			if($row_flexible_hours->total_days_per_year != null){
				// for flexible hours
				$no_of_days = $row_flexible_hours->total_days_per_year / 12; // 12 = months
				$working_hours = $row_flexible_hours->total_hours_for_the_day;
			}
		}
		
		if($working_hours != "" && $no_of_days != ""){
			// get mininum wage rate for employee
			$where = array(
				'emp_id'  => $emp_id,
				'status'  => 'Active',
				'deleted' => '0'
			);
			$this->edb->where($where);
			$sql_minimum_wage_rate = $this->edb->get('basic_pay_adjustment');
			
			$row_minimum_wage_rate = $sql_minimum_wage_rate->row();
			if($sql_minimum_wage_rate->num_rows() > 0){
				$sql_minimum_wage_rate->free_result();
				$effective_date = strtotime(date("Y-m-d",strtotime($row_minimum_wage_rate->effective_date)));
				$current_date = strtotime(date("Y-m-d"));
				
				if($row_minimum_wage_rate->new_basic_pay == NULL || $row_minimum_wage_rate->effective_date == NULL || $row_minimum_wage_rate->adjustment_date == NULL){
					$current_basic_pay = $row_minimum_wage_rate->current_basic_pay;
				}else{
					if($effective_date > $current_date){
						$current_basic_pay = $row_minimum_wage_rate->current_basic_pay;
					}else{
						$current_basic_pay = $row_minimum_wage_rate->new_basic_pay;
					}
				}
				
				$mininum_wage_rate = $current_basic_pay / $no_of_days;
			}else{
				$current_basic_pay = 0;
				$mininum_wage_rate = 0;
			}
			
			// get regular hours type
		
			$regular_hourly_rate = number_format($mininum_wage_rate, 2) / number_format($working_hours);
		}
		
		$workday_val = date("l",strtotime($workday));
					
		// check if workday is uniform working days, get the total working days per year
		$sql_uniform_working_days = $CI->db->query("
			SELECT *
			FROM `employee_time_in` eti
			LEFT JOIN uniform_working_day_settings uwds ON eti.comp_id = uwds.company_id
			LEFT JOIN uniform_working_day uwd ON uwds.company_id = uwd.company_id
			WHERE eti.emp_id = '{$emp_id}'
			AND uwd.working_day = '{$workday_val}'
			GROUP BY uwd.working_day
		");
		
		// get number of days
		$row_uniform_working_days = $sql_uniform_working_days->row();
		if($sql_uniform_working_days->num_rows() > 0){
			$sql_uniform_working_days->free_result();
			
			if($row_uniform_working_days->total_working_days_per_year != null || $row_uniform_working_days->working_hours != ""){
				// for uniform working days
				$no_of_days = $row_uniform_working_days->total_working_days_per_year / 12; // 12 = months
				$working_hours = $row_uniform_working_days->working_hours;
			}
		}
		
		return $working_hours;
	}
		
	
	
	/**
	*	Checks employee timesins if conflicts
	*	@param int $company_id
	*	@param int $emp_id
	*	@param date $date	
	*	@return boolean
	*/
	public function check_employee_timeins($company_id,$emp_id,$date) {
		if(is_numeric($company_id)) {
			$query = $this->db->query("SELECT  * FROM employee_time_in WHERE (time_in_status ='approved' OR time_in_status IS NULL) && status = 'Active' && flag_regular_or_excess = 'regular' && comp_id = '{$this->db->escape_str($company_id)}' AND date='{$this->db->escape_str($date)}' AND emp_id='{$this->db->escape_str($emp_id)}' AND status = 'Active'");
			$row = $query->row();
			$query->free_result();
			return $row;
		} else {
			return false;
		}
	}
	public function check_employee_timeinsv2($company_id,$emp_id,$date) {
		if(is_numeric($company_id)) {
			$query = $this->db->query("SELECT  * FROM employee_time_in WHERE (time_in_status ='pending' OR time_in_status ='approved' OR time_in_status IS NULL) && status = 'Active' && flag_regular_or_excess = 'regular' && comp_id = '{$this->db->escape_str($company_id)}' AND date='{$this->db->escape_str($date)}' AND emp_id='{$this->db->escape_str($emp_id)}' AND status = 'Active'");
			$row = $query->row();
			$query->free_result();
			return $row;
		} else {
			return false;
		}
	}

	/**
	*	Checks employee timesins if conflicts in split schedule
	*	@param int $company_id
	*	@param int $emp_id
	*	@param date $date	
	*	@return boolean
	*/
	public function check_employee_timeins_split($company_id,$emp_id,$date) {
		if(is_numeric($company_id)) {
			$query = $this->db->query("SELECT  * FROM schedule_blocks_time_in WHERE comp_id = '{$this->db->escape_str($company_id)}' AND status = 'Active' AND date='{$this->db->escape_str($date)}' AND emp_id='{$this->db->escape_str($emp_id)}' AND status = 'Active'");
			$row = $query->row();
			$query->free_result();
			return $row;
		} else {
			return false;
		}
	}
	
	/**
	*	READ CSV CONTENTS FOR VALIDATIONS PURPOSES
	*	@param int $company_id
	*	@param string $csv_file (e.i test.csv)
	*	@return object
	*/
	public function read_csv_contents($csv_file){
		$file_path =  $csv_file;
		$arr=array();
		$c = array();
		$row = 0;
		if (($handle = fopen($file_path, "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
				$num = count($data);
				for ($c = 0; $c < $num; $c++) {
					if($row !=0) {
						$arr[$row][$c]= trim($data[$c]);	
					}
				}
				$row++;
			}
			fclose($handle);
			return $arr;
		}else{
			return false;
		}
	}
	
	/***
	 * mao ni mo filter sa mga data sa import
	 * @param unknown $company_id
	 * @param unknown $csv_file_or_input
	 * @return multitype:boolean multitype:string  |multitype:string boolean
	 */
	public function error_checking_read_csv($company_id,$csv_file_or_input){
		$error = array();
		$errorx = array();
			$read_csv = $this->timeins->read_csv_contents($csv_file_or_input);
			$row_start = 2;
			$date_mix = array();
			$conflict = array();
			$list_arr = array();
		
			if($read_csv) {
				$num =  count($read_csv);
				$trimmedArray = array();
			
				if($num > 1){
					foreach($read_csv as $key=>$val){						
						$emp_id = str_replace("'", "", $val[0]);
						$last_name = $val[1];
						$middle_name = $val[2];
						$first_name = $val[3];
						$time_in = $val[5];
						if(trim($emp_id,",") !="" || $last_name !="" || $first_name !="" || $time_in !=""){
							$trimmedArray[] = $val;
							//echo "weee";
						}
												
					}
				
					$read_csv = $trimmedArray;
				}
				
				$keyx  = 0;
				$ego = true;
				foreach($read_csv as $key=>$val){
					$allow=true;
					$emp_id = str_replace("'", "", $val[0]);
					$keyx++;
					$last_name = $val[1];
					$middle_name = $val[2];
					$first_name = $val[3];
					$time_in = $val[5];
					$gDate = date('Y-m-d',strtotime($val[4]));
					$time_in_time = $val[6];
					$lunch_out = $val[7];
					$lunch_out_time = $val[8];
					$lunch_in = $val[9];
					$lunch_in_time = $val[10];
					$time_out = $val[11];
					$time_out_time = isset($val[12]) ?  $val[12] : "";
					$row_where =$row_start++;
					
					if(!(strstr($time_in, '/'))){
						$error[] = "Important: Please note that we have a new format for uploading timesheets.";
						$list_arr[] = $keyx;
						$ego = false;
					}else{
					$fullname = $first_name.$last_name;
					
					$time_in =date('Y-m-d H:i:s',strtotime($time_in." ". $time_in_time));
					$lunch_out =date('Y-m-d H:i:s',strtotime($lunch_out." ".$lunch_out_time));
					$lunch_in=date('Y-m-d H:i:s',strtotime($lunch_in." ".$lunch_in_time));
					$time_out=date('Y-m-d H:i:s',strtotime($time_out." ".$time_out_time));
					if($time_in =="" || $time_in_time==""){
						$time_in = null;
					}
					if($lunch_out =="" || $lunch_out_time==""){
						$lunch_out = null;
					}
					if($lunch_in =="" || $lunch_in_time==""){
						$lunch_in = null;
					}
					if($time_out =="" || $time_out_time==""){
						$time_out = null;
					}
					
					if(!((trim($emp_id,",") !="" && $fullname == "") || (trim($emp_id,",") =="" && $fullname != "")) ){
						if(trim($emp_id,",") ==""){
							$conflict[]  = "The employee does not have an ID in Row [$row_where]";
							$list_arr[] = $keyx;
							$ego = false;
							
						}
						if($last_name ==""){
							$conflict[]  = "The employee does not have a last name in row [$row_where]";
							$list_arr[] = $keyx;
							$ego = false;
							
						}
						if($first_name ==""){
							$conflict[]  ="The employee does not have a first name in row [$row_where]";
							$list_arr[] = $keyx;
							$ego = false;						
						}											
					}
							
					if(trim($emp_id,",") ==""  && ($last_name !="" && $first_name !="")){
						
						$emp_id = $this->get_employee_id($this->company_info->company_id, $last_name, $first_name); //xd
						
					}
					
					if($time_in ==""){
						$error[]  =$first_name." ".$last_name." ".$time_out." has empty cell on row wd [$row_where]";
						$list_arr[] = $keyx;
						$ego = false;
					}
					
					if($gDate == ""){
						$error[]  = $first_name." ".$last_name." ".$time_out." has empty cell on row  q[$row_where]";
						$list_arr[] = $keyx;
						$ego = false;
					}
					/*if($time_out ==""){
						$error[]  =$first_name." ".$last_name." ".$time_in." has empty cell on row [$row_where]";
						$list_arr[] = $keyx;
						$ego = false;
					}*/
					
					/*if(($time_in_time == "" || $time_out_time == "") || ($lunch_in_time == "" && $lunch_in !="") || 
							($lunch_out_time == "" && $lunch_out !="")){
						$error[]  =$first_name." ".$last_name." ".$time_in." has empty cell on row [$row_where]";
						$list_arr[] = $keyx;
						$ego = false;
					}*/				
					
					
					#($lunch_out =="") ? 	$error[]  = "Missing Employee Lunch Out in Row [$row_where]" : "";
					#($lunch_in =="") ?   	$error[]  ="Missing Employee Lunch In in Row [$row_where]" : "";
					/*if($time_out =="") { 	
						$error[]  ="The employee does not have a time out in row [$row_where]";
						$list_arr[] = $keyx;
						$allow = false;
						$ego = false;
					}*/
					#if($time_in && $lunch_out && $lunch_in && $time_out) {
				
					
						if($time_in !="" && $lunch_out !="" && $lunch_in !="" && $time_out !="") {
							
							$time_in2 =$time_in;
							$lunch_out2 =$lunch_out;
							$lunch_in2 =$lunch_in;
							$time_out2 =$time_out;
							if($lunch_in2 < $lunch_out2){
								$error[] = "Lunch Out Is Greater than Lunch In Row xxxx [".$row_where."] ";
								$list_arr[] = $keyx;
								$ego = false;
								
							}	
							if($lunch_in2 >$time_out2){
								$error[] = "Lunch In Is Greater than Time Out in Row [".$row_where."] ";
								$list_arr[] = $keyx;
								$ego = false;
							}	
							
							if($lunch_out2 >$time_out2){
								$error[] = "Lunch Out Is Greater than Time Out in Row [".$row_where."] ";
								$list_arr[] = $keyx;
								$ego = false;
							}
							
							if($time_in2 > $time_out2){ 
								
								$error[] = "Time In Is Greater than Time Out in Row [".$row_where."] ";
								$list_arr[] = $keyx;
								$ego = false;
							}
														
						}
						if($time_in && $time_out && $lunch_out =="" && $lunch_in =="") {
						
							if(idate_convert($time_in) >idate_convert($time_out)){
								$error[] = "Time In Is Greater than Time Out in Rowx [".$row_where."]";
								$list_arr[] = $keyx;
								$allow = false;
								$ego = false;
							}
						}
					
					//if($emp_id !="" && $last_name !="" && $first_name !="" && $time_in !="") {
					if(true){
						
						
						$date = idate_convert($time_in);
						
						$check = $this->get_employee_credentials($emp_id,$this->company_info->company_id,$last_name,$middle_name,$first_name);
						
						//aldrin added here
						
						
						
						
						if($check) {
																		
							$get_emp_id = $check->emp_id;
							
							$employee_timein_date = date("Y-m-d",strtotime($date)); // ccc
							
							//adde aldrin here
							$employee_timein_date2 = date('Y-m-d',strtotime($date. " -1 day"));
							$work_schedule_id = $this->emp_work_schedule($get_emp_id,$this->company_info->company_id,$employee_timein_date2);
							$previous_split_sched = $this->elm->yesterday_split_info($time_in,$get_emp_id,$work_schedule_id,$this->company_info->company_id);
							//	p($previous_split_sched);
							$child= true;
							if($previous_split_sched){
								$last_sched = max($previous_split_sched);
								$time_inx = date('Y-m-d H:i:s',strtotime($time_in));
								
								if( $time_inx <= $last_sched['end_time']){
									$yesterday_m = date('Y-m-d',strtotime($time_in));
									$employee_timein_date = date('Y-m-d',strtotime($yesterday_m. " -1 day"));	
									$date = date('Y-m-d',strtotime($yesterday_m. " -1 day"));
									$child = false;
								}
							}
							
							//$vx = $this->elm->activate_nightmare_trap_upload($this->company_info->company_id,$emp_id,$time_in,$time_out);
								
							//if($vx){
							//	$date = $vx['current_date'];
							
							//}
							$date = $gDate;
							$employee_timein_date = $gDate;
							
							$check_timeins = $this->check_employee_timeins($this->company_info->company_id,$get_emp_id,$date); # CHECK IF TIME IN IS AVAILABLE
							$workschedule = $this->emp_work_schedule($get_emp_id,$this->company_info->company_id,$employee_timein_date);
							
							$split = $this->check_if_split_schedule($get_emp_id,$this->company_info->company_id,$workschedule, $date,$time_in,$time_out);
							if(!$split){
								if($check_timeins){
									$conflict[] = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($check_timeins->date))." already exists in the database on row [$row_where]";									
									$allow = false;
									$date_mix[] = $keyx;
								}
							}else{
								
								$split_timeins = $this->check_employee_timeins_split($this->company_info->company_id,$get_emp_id,$date);
								
								if($check_timeins && $child){  
									$conflict[] = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($check_timeins->date))." already exists in the database on row [$row_where]";
									$ego = false;
								}
								
								/*if($split_timeins && $tar){  
									$conflict[] = ucwords(strtolower($check->first_name)).' '.$date.' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($split_timeins->date))." is already existed in Row [$row_where]";
									
								}*/
							}
							
							if($workschedule==false && $allow){

								$w = $this->if_no_workschedule($get_emp_id,$company_id);
								//last_query();
								if($w == 0|| $w == NULL || $w == ""){
									$error[] = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($date))." is not assigned to a Payroll group row [$row_where]. Please assign a Payroll Group on Workforce";
									$list_arr[] = $keyx;
									$ego = false;
								}/*else{
								
									$conflict[] = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($date))." doesn't have workschedule in row [$row_where]. Please assign a workschedule on Shifts";
									$date_mix[] = $keyx;
								}*/

								
							}
							
						} else {
							$error[] = "Employee doesn't exist (".$emp_id ." ".strtoupper($first_name).' '.strtoupper($last_name).") in row [$row_where]";
							$list_arr[] = $keyx;
							$ego = false;
							
						}
						
					}
					} #end check date
				/**
				 * disable continue button if no errors
				 */
					if($ego)
						$errorx[] =  $keyx;
					else
						$ego = true;
				
				}//end foreach
				
				$list_arr = array_unique($list_arr);
				
				/**
				 * disable continue button if no errors
				 */
				$list_err = array_unique($errorx);
				$d =0;
				
				$num_csv = count($read_csv);
				$continue = false;
				for($x = 1; $x <= $num_csv;$x++){
					foreach($list_err as $rox){
						if($rox == $x){
							$d = 1;
						}
					}
				}
				
				if($d == 1){
					$continue = true;
				}
				/**end*/
		
				$this->session->set_userdata('date_mix',$date_mix);
				$this->session->set_userdata('list_conflict',$list_arr);
				return array("error"=>$error,"overwrites"=>$conflict,"valid_csv"=>true,"continue" => $continue);
			}else{
				return array("error"=>"","overwrites"=>"","valid_csv"=>false);
			}
	}
	

	/***
	 * check if user has default scheduled or it has schedule added 
	 * @param unknown $emp_id
	 * @param unknown $comp_id
	 * @return Ambigous <boolean, unknown>
	 */
	public function if_no_workschedule($emp_id,$comp_id){
			
		$w = array(
				"epi.emp_id"=>$emp_id,
				"epi.company_id"=>$comp_id
		);
		$this->edb->where($w);
		
		$arrx = array(
				'work_schedule_id' => 'pg.work_schedule_id'
		);
		$this->edb->select($arrx);
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$q = $this->edb->get("employee_payroll_information AS epi");
		$r = $q->row();
		
		return ($r) ?  $r->work_schedule_id : 0;
	}

	/**
	 *	Get employee credentials by name
	 *	@param int $emp_id
	 *	@param int $company_id
	 *	@return
	 */
	public function get_employee_id($company_id,$last_name,$first_name){
		if(is_numeric($company_id)){
			$where = array(
					'e.company_id'		 => $company_id,				
					'e.status'			 => 'Active'
			);
			if($last_name != "")
				$where['e.last_name'] = $last_name;
		
			if($first_name !="")
				$where['e.first_name'] = $first_name;
			
			$arr = array(
					'payroll_cloud_id' => 'a.payroll_cloud_id'
			);
			$this->edb->select($arr);
			$this->edb->where($where);
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$query = $this->edb->get('employee AS e');
			$row = $query->row();
			
			#capitalize the name of the employee
			if(!$row){				
				$row = $this->get_employee_id($company_id, ucfirst($last_name), ucfirst($first_name));
				$row = (object)array(
						'payroll_cloud_id' => $row
				);				
			}			
			
			return ($row)? $row->payroll_cloud_id : false;
			
		}else{
			return false;
		}
	}


	/**
	 * filter for add timesheet and edit timesheets
	 * @param unknown $company_id
	 * @param unknown $data_list
	 * @return multitype:boolean multitype:string  |multitype:string boolean
	 */
	public function create_new_check($company_id,$data_list = array(),$source=""){
		$error = array();
		$row_start = 1;
			$conflict = array();
			$list_arr = array();
			$date_mix = array();
			
		if($data_list) {
			foreach($data_list as $key=>$val):	
			$allow = true;		
			$emp_id = $val[0];			
			$time_in = $val[1];
			$lunch_out = $val[2];
			$lunch_in = $val[3];
			$time_out = $val[4];
			$last_name = $val[6];
			$first_name = $val[5];
			$emp_no = $val[7];
			$gDate = isset($val[8]) ? $val[8] : "";
			$middle_name = "";
			$row_where =$row_start++;
					if(trim($emp_id,",") ==""){
						$error[]  = "The employee does not have an ID in Row [$row_where]";
						$list_arr[] = $key;
					}
					if($last_name ==""){
						$error[]  = "The employee does not have a last name in row [$row_where]";
						$list_arr[] = $key;
					}
					if($first_name ==""){
						$error[]  ="The employee does not have a first name in row [$row_where]";
						$list_arr[] = $key;
					}					
					if($time_in ==""){
						$error[]  ="The employee does not have a time-in in row [$row_where]";
						$list_arr[] = $key;
					}
					if($time_out ==""){
						$error[]  ="The employee does not have a time-out in row [$row_where]";
						$list_arr[] = $key;
					}
					if($time_in !="" && $lunch_out !="" && $lunch_in !="" && $time_out !="") {
						
						if($time_out =="") {
							$conflict[]  ="The employee does not have a time out in row [$row_where]";
							$list_arr[] = $key;
							$allow = false;
						}
						
						$time_in2 =date('Y-m-d H:i:s',strtotime($time_in));
						$lunch_out2 =date('Y-m-d H:i:s',strtotime($lunch_out));
						$lunch_in2 =date('Y-m-d H:i:s',strtotime($lunch_in));
						$time_out2 =date('Y-m-d H:i:s',strtotime($time_out));
						if($lunch_in2 < $lunch_out2){
							$error[] = "Lunch Out Is Greater than Lunch In Row [".$row_where."] ";
							$list_arr[] = $key;
						}	
						if($lunch_in2 >$time_out2){
							$error[] = "Lunch In Is Greater than Time Out in Row [".$row_where."] ";
							$list_arr[] = $key;
						}	
						
						if($lunch_out2 >$time_out2){
							$error[] = "Lunch Out Is Greater than Time Out in Row [".$row_where."] ";
							$list_arr[] = $key;
						}
						if($time_in2 > $time_out2){ 
							
							$error[] = "Time In Is Greater than Time Out in Row [".$row_where."] ";
							$list_arr[] = $key;
						}
					}
						
						
					if($time_in && $time_out && $lunch_out =="" && $lunch_in =="") {
						if(idate_convert($time_in) >idate_convert($time_out)){
							$error[] = "Time In Is Greater than Time Out in Rowx [".$row_where."]";
							$list_arr[] = $key;
							$allow=false;
						}
						
						$time_in2 =date('Y-m-d H:i:s',strtotime($time_in));
						$time_out2 =date('Y-m-d H:i:s',strtotime($time_out));
						if($time_in2 > $time_out2){
						
							$error[] = "Time In Is Greater than Time Out in Row [".$row_where."] ";
							$list_arr[] = $key;
							$allow=false;
						}
					}
					
					if($emp_id !="" && $last_name !="" && $first_name !="" && $time_in !="") {
						$date = idate_convert($time_in);
						//$emp_no = $this->convert_emp_id_to_emp_no($emp_id);
						$check = $this->get_employee_credentials($emp_no,$company_id,$last_name,$middle_name,$first_name);
						//aldrin added here
						if($check) {
																		
							$get_emp_id = $check->emp_id;
							
							if($gDate){
								$employee_timein_date = date('Y-m-d',strtotime($gDate));
							}else{
								$employee_timein_date = date("Y-m-d",strtotime($date)); // ccc
								
								$vx = $this->elm->activate_nightmare_trap_upload($company_id,$emp_no,$time_in,$time_out);
								
								if($vx){
									$employee_timein_date = $vx['current_date'];
								
								}
							}
							
							//adde aldrin here
							$employee_timein_date2 = date('Y-m-d',strtotime($date. " -1 day"));
							$work_schedule_id = $this->emp_work_schedule($get_emp_id,$company_id,$employee_timein_date2);
							$previous_split_sched = $this->elm->yesterday_split_info($time_in,$get_emp_id,$work_schedule_id,$company_id);
							//	p($previous_split_sched);
							$child= true;
							if($previous_split_sched){
								$last_sched = max($previous_split_sched);
								$time_inx = date('Y-m-d H:i:s',strtotime($time_in));
								
								if( $time_inx <= $last_sched['end_time']){
									$yesterday_m 			= date('Y-m-d',strtotime($time_in));
									$employee_timein_date 	= date('Y-m-d',strtotime($yesterday_m. " -1 day"));	
									$date 					= date('Y-m-d',strtotime($yesterday_m. " -1 day"));
									$child 					= false;
								}
							}
							
							$check_timeins 	= $this->check_employee_timeinsv2($company_id,$get_emp_id,$employee_timein_date); # CHECK IF TIME IN IS AVAILABLE
							
							$workschedule 	= $this->emp_work_schedule($get_emp_id,$company_id,$employee_timein_date);
							$split = $this->check_if_split_schedule($get_emp_id,$company_id,$workschedule, $date,$time_in,$time_out);
							
							$void = $this->import->edit_delete_void2($get_emp_id,$company_id,$gDate);
							if(!$split){
								if($check_timeins){
									$time_in 	= date("Y-m-d H:i:s",strtotime($time_in));
									$time_out 	= date("Y-m-d H:i:s",strtotime($time_out));
									$t_status 	= $check_timeins->time_in_status;
									$t_hours 	= $check_timeins->total_hours_required;
									if($source == "Dashboard"){
										if($t_status == "pending"){
											$error[] 	= "This employee has pending timesheet on this date. This needs to be approve first before adding a new one. Row [$row_where]";
											$list_arr[] = $key;
											$allow 		= false;
										}
										else if($t_hours <= 0){
											$error[] 	= "The timesheet of this employee for this date has zero hours worked. This needs to be adjusted first before adding a new timesheet. Row [$row_where]";
											$list_arr[] = $key;
											$allow 		= false;
										}
										else{
											if((strtotime($check_timeins->time_in) <= strtotime($time_in) && strtotime($time_in) <= strtotime($check_timeins->time_out)) || (strtotime($check_timeins->time_in) <= strtotime($time_out) && strtotime($time_out) <= strtotime($check_timeins->time_out)) ){
												$error[] 	= "This timesheet has overlapped an existing timesheet. Please check you hours and try again. Row [$row_where]";
												$list_arr[] = $key;
												$allow 		= false;
											}
											else{
												if(((strtotime($check_timeins->time_in) >= strtotime($time_in) && strtotime($time_out) >= strtotime($check_timeins->time_in)) && (strtotime($check_timeins->time_out) >= strtotime($time_in) && strtotime($time_out) >= strtotime($check_timeins->time_out)))){
													$error[] 	= "This timesheet has overlapped an existing timesheet. Please check you hours and try again. Row [$row_where]";
													$list_arr[] = $key;
													$allow 		= false;
												}
												else{
													if(!$void){
														$conflict[] = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($check_timeins->date))." already exists in the database on row [$row_where]";
														$date_mix[] = $key;
														$allow = false;
													}else{
														foreach ($void AS $v){
															$stat_v = $v->view_status;
															if($stat_v == "Waiting for approval"){
																$f = $v->period_from;
																$t = $v->period_to;
																$error[] = "Row [$row_where]. Timesheets for ".date("d-M-Y",strtotime($f))." to ".date("d-M-Y",strtotime($t))." are locked for payroll processing.
																			When timesheets are locked it is not possible to initiate any time related process affecting the payroll period.
																			Therefore if you need to submit any absence or attendance adjustments it is recommended to seek approval from your HR
																			or manager for this transaction online.";
																$list_arr[] = $key;
																$allow 		= false;
															}else if($stat_v == "Closed"){
																$error[] = 	"Row [$row_where]. When payroll is closed it is not possible to initiate any time related process affecting this
																			payroll . Therefore if you need to submit any absence or attendance adjustments it is recommended to seek approval from
																			your HR or manager for this transaction offline.";
																$list_arr[] = $key;
																$allow 		= false;
															}
														}
													}
												}
											}
										}
									}else{
										if(!$void){
											$conflict[] = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($check_timeins->date))." already exists in the database on row [$row_where]";
											$date_mix[] = $key;
											$allow = false;
										}else{
											foreach ($void AS $v){
												$stat_v = $v->view_status;
												if($stat_v == "Waiting for approval"){
													$f = $v->period_from;
													$t = $v->period_to;
													$error[] = "Row [$row_where]. Timesheets for ".date("d-M-Y",strtotime($f))." to ".date("d-M-Y",strtotime($t))." are locked for payroll processing.
																		When timesheets are locked it is not possible to initiate any time related process affecting the payroll period.
																		Therefore if you need to submit any absence or attendance adjustments it is recommended to seek approval from your HR
																		or manager for this transaction online.";
												}else if($stat_v == "Closed"){
													$error[] = 	"Row [$row_where]. When payroll is closed it is not possible to initiate any time related process affecting this
																payroll . Therefore if you need to submit any absence or attendance adjustments it is recommended to seek approval from
																your HR or manager for this transaction offline.";
													$list_arr[] = $key;
													$allow 		= false;
												}
											}
										}
									}
								}
							}else{
								$split_timeins = $this->check_employee_timeins_split($company_id,$get_emp_id,$date);
								if($check_timeins && $child){
									if(!$void){
										$conflict[] = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($check_timeins->date))." already exists in the database on row [$row_where]";
									}
								}
							}
							
							if($workschedule==false && $allow){
							
								//$w = $this->if_no_workschedule($get_emp_id,$company_id);
								$w = $this->emp_work_schedule($get_emp_id,$company_id,$employee_timein_date2);
								if($w == 0 || $w == NULL || $w == ""){
									$error[] = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($date))." is not assigned to a Payroll group row [$row_where]. Please assign a Payroll Group on Workforce";
									$list_arr[] = $key;
								}else{
								
									$conflict[] = ucwords(strtolower($check->first_name)).' '.strtoupper($check->last_name).' '.date("m/d/Y",strtotime($date))." doesn't have workschedule in row [$row_where]. Please assign a workschedule on Shifts";
									$date_mix[] = $key;
								}
							}
							
						} else {
							$error[] = "Employee doesn't exist (".$emp_no.$company_id ." ".strtoupper($first_name).' '.strtoupper($last_name).") in row [$row_where]";
							$list_arr[] = $key;
						}
					
					}
				endforeach;
				
				$list_arr = array_unique($list_arr);
				$this->session->set_userdata('list_conflict2',$list_arr);
				$this->session->set_userdata('date_mix',$date_mix);
				return array("error"=>$error,"overwrites"=>$conflict,"valid_csv"=>true);
		}else{
			return array("error"=>"","overwrites"=>"","valid_csv"=>false);
		}
	}
	
	public function edit_delete_void2($emp_id,$comp_id,$gDate){
		$gDate = date("Y-m-d",strtotime($gDate));
		$return_void = false;
		$return_r = "";
		$w = array(
			'prc.emp_id' 			=> $emp_id,
			'prc.company_id' 		=> $comp_id,
			'prc.period_from <=' 	=> $gDate,
			'prc.period_to >='  	=> $gDate, 
			'prc.status'			=> 'Active'
		);
		$s = array(
			'dpr.view_status',
			'prc.period_from',
			'prc.period_to'
		);
		$this->db->select($s);
		$this->db->where($w);
		$this->db->join("draft_pay_runs as dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id");
		$q = $this->db->get('payroll_run_custom as prc');
		$r = $q->result();
		if($r){
			foreach($r as $r1){
				$stat_v = $r1->view_status;
				if($stat_v == "Waiting for approval" || $stat_v == "Closed"){
					$return_void = true;
					$return_r	 = $r;
				}
			}
		}else{
			$w1 = array(
				'epi.emp_id' 			=> $emp_id,
				'dpr.company_id' 		=> $comp_id,
				'dpr.period_from <=' 	=> $gDate,
				'dpr.period_to >='  	=> $gDate,
				'dpr.status'			=> 'Active'
			);
			$s1 = array(
				'dpr.view_status',
				'dpr.view_status',
				'dpr.view_status'
			);
			$this->db->select($s1);
			$this->db->where($w1);
			$this->db->join("draft_pay_runs as dpr","dpr.payroll_group_id = epi.payroll_group_id");
			$q1 = $this->db->get('employee_payroll_information as epi');
			$r1 = $q1->result();
			if($r1){
				foreach($r1 as $r1x){
					$stat_v = $r1x->view_status;
					if($stat_v == "Waiting for approval" || $stat_v == "Closed"){
						$return_void = true;
						$return_r	 = $r1;
					}
				}
			}
		}
		
		if($return_void){
			return $return_r;
		}else{
			return false;
		}
	}
	
	/**
		=== NOTES edit_delete_void() function ===
		this function is used to avoid editing and deleting timesheet when a pay run is being process
		waiting for approval - admin and employee cant add, edit and delete timesheet
		closed				 - admin can now delete timesheet(only on hours, not on master list) but can't edit and add timesheet
							 - employee can now add timesheet for retro something....
	**/	
	public function edit_delete_void($emp_id,$comp_id,$gDate){
		$gDate 			= date("Y-m-d",strtotime($gDate));
		$return_void 	= false;
		$stat_v1 		= "";
		$w = array(
				'prc.emp_id' 			=> $emp_id,
				'prc.company_id' 		=> $comp_id,
				'prc.period_from <=' 	=> $gDate,
				'prc.period_to >='  	=> $gDate,
				'prc.status'			=> 'Active'
		);
		$s = array(
				'dpr.view_status'
		);
		$this->db->select($s);
		$this->db->where($w);
		$this->db->join("draft_pay_runs as dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id");
		$q = $this->db->get('payroll_run_custom as prc');
		$r = $q->result();
		if($r){
			foreach($r as $r1){
				$stat_v = $r1->view_status;
				if($stat_v == "Waiting for approval" || $stat_v == "Closed"){
					$return_void = true;
				}
			}
		}else{
			$w1 = array(
					'epi.emp_id' 			=> $emp_id,
					'dpr.company_id' 		=> $comp_id,
					'dpr.period_from <=' 	=> $gDate,
					'dpr.period_to >='  	=> $gDate,
					'dpr.status'			=> 'Active'
			);
			$s1 = array(
					'dpr.view_status'
			);
			$this->db->select($s1);
			$this->db->where($w1);
			$this->db->join("draft_pay_runs as dpr","dpr.payroll_group_id = epi.payroll_group_id");
			$q1 = $this->db->get('employee_payroll_information as epi');
			$r1 = $q1->result();
			if($r1){
				foreach($r1 as $r1x){
					$stat_v = $r1x->view_status;
					if($stat_v == "Waiting for approval" || $stat_v == "Closed"){
						$return_void = true;
					}
				}
			}
		}
	
		if($return_void){
			return $stat_v;
		}else{
			return false;
		}
	}
	/** barack import **/
	/**
	 * 
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param unknown $time_in
	 * @param unknown $lunch_out
	 * @param unknown $lunch_in
	 * @param unknown $time_out
	 * @param string $source
	 * @param string $emp_no
	 * @param string $gDate came from edit timesheets
	 * @param array $more_setting - use in recalculating hours
	 */
	public function import_new($company_id,$emp_id,$time_in,$lunch_out,$lunch_in,$time_out,$source="",$emp_no = "",$gDate="",$more_setting = array(),$real_employee_time_in_id = 0) {
		
		$void = $this->edit_delete_void($emp_id,$company_id,$gDate);
		if($void == "Waiting for approval"){
			return false;
			exit();
		}
		
		if($source == ""){
			$source = "import";
		}
		
		if($more_setting){
			$work_schedule_id 		= $more_setting['work_schedule_id'];	
			$this->work_schedule_id = $more_setting['work_schedule_id'];
			$work_schedule_id2 		= $more_setting['work_schedule_id'];
		}
		$time_in_excel 		= $time_in;
		$lunch_out_excel 	= $lunch_out;
		$lunch_in_excel 	= $lunch_in;
		$time_out_excel 	= $time_out;
		
	//	$employee_timein_date = $time_in;
	
		$employee_timein_date 	= date("Y-m-d",strtotime($time_in)); // date("Y-m-d")
		$lunch_out_date 		= ($lunch_out == "") ? "" : date("Y-m-d",strtotime($lunch_out));
		$lunch_in_date 			= ($lunch_in == "") ? "" : date("Y-m-d",strtotime($lunch_in));
		$time_out_date 			= ($time_out == "") ? "" : date("Y-m-d",strtotime($time_out));
		
		$time_in_hr 	= date("H",strtotime($time_in_excel)); // date("H")
		$time_in_min 	= date("i",strtotime($time_in_excel)); // date("i")
		$time_in_ampm 	= date("A",strtotime($time_in_excel)); // date("A")
		
		$lunch_out_hr 	= date("H",strtotime($lunch_out_excel)); 
		$lunch_out_min 	= date("i",strtotime($lunch_out_excel));
		$lunch_out_ampm = date("A",strtotime($lunch_out_excel)); 
		
		$lunch_in_hr 	= date("H",strtotime($lunch_in_excel)); 
		$lunch_in_min 	= date("i",strtotime($lunch_in_excel)); 
		$lunch_in_ampm 	= date("A",strtotime($lunch_in_excel)); 
		
		$time_out_hr 	= date("H",strtotime($time_out_excel)); 
		$time_out_min 	= date("i",strtotime($time_out_excel)); 
		$time_out_ampm 	= date("A",strtotime($time_out_excel)); 

		
		$log_error = false;
		
		$new_time_in = date('Y-m-d H:i:s',strtotime($time_in));
		$new_lunch_out 	= ($lunch_out == "" || $lunch_out == " ") ? NULL : date('Y-m-d H:i:s',strtotime($lunch_out));
		$new_lunch_in 	= ($lunch_in =="" || $lunch_in == " ") ? NULL : date('Y-m-d H:i:s',strtotime($lunch_in));		
		$new_time_out 	= ($time_out=="" || $time_out == " ") ? NULL : date('Y-m-d H:i:s',strtotime($time_out));				
		
		if($new_time_out < $new_time_in || $new_time_out == "" ){
			$log_error = true;
		}
		
		if(($new_lunch_out!="" && $new_lunch_in=="") || ($new_lunch_out=="" && $new_lunch_in!="")) {
			$log_error = true;
			
		}
		if($new_time_in > $new_time_out){
			$new_time_out = null;
			$log_error = true;
		}
		$reason 		='';
		$flag_halfday 	= 0;
		
		if ($emp_id){
			$payroll_group 	= $this->employee->get_payroll_group($emp_id);
			$emp_no 		= $this->convert_emp_id_to_emp_no($emp_id);
			
			if($gDate){
				$currentdate = date('Y-m-d',strtotime($gDate));
				$employee_timein_date = $currentdate;
				$gDate = date('Y-m-d',strtotime($gDate));
			}else{
				/***
				 * this is not useful now because the user can add date to the form
				 * You can delete it, if you want
				 */
				$vx 			= $this->elm->activate_nightmare_trap_upload($company_id,$emp_no,$new_time_in,$new_time_out);
				$currentdate 	= date('Y-m-d',strtotime($new_time_in));
			    
				if($vx){
					$currentdate = $vx['current_date'];
					$employee_timein_date = $currentdate;
				}
			}
			$currentdate_orig_date_from_input = $currentdate;
			# get the list of split info
			$get_split 				= $this->identify_split_info($time_in,$time_out, $emp_id, $company_id);
			$employee_timein_date2 	= date('Y-m-d',strtotime($currentdate. " -1 day"));
			# more setting is empty
			if(!$more_setting){
				$this->work_schedule_id = $this->emp_work_schedule($emp_id,$company_id,$currentdate);
				$work_schedule_id 		= $this->work_schedule_id;
				$work_schedule_id2 		= $this->emp_work_schedule($emp_id,$company_id,$employee_timein_date2);
			}
			
			$previous_split_sched = $this->elm->yesterday_split_info($time_in,$emp_id,$work_schedule_id2,$company_id);
			if($previous_split_sched){
				$last_sched = max($previous_split_sched);
				if(date('Y-m-d H:i:s',strtotime($time_in)) <= $last_sched['end_time']){
					$yesterday_m = date('Y-m-d',strtotime($time_in));
					$currentdate = date('Y-m-d',strtotime($yesterday_m. " -1 day"));
					
					if(!$more_setting){
						$this->work_schedule_id = $work_schedule_id;
					}
				}
			}
			
			#check if splist schedule
			#check if date is split
			$split = $this->check_if_split_schedule($emp_id,$company_id,$this->work_schedule_id, $currentdate,$time_in,$time_out);	
			
			/** if workshedule is not split schedule ***/
			if(!$split){
				// CHECK IF UNIFORM WORKINGS DAYS/WORKSHIFT OR FLEXIBLE HOURS
				// HOLIDAY
				if($this->work_schedule_id != FALSE){
					/* EMPLOYEE WORK SCHEDULE */
					$check_rest_day = $this->elm->check_rest_day(date("l",strtotime($currentdate)),$this->work_schedule_id,$company_id);
				}else{
					$check_rest_day = $this->employee->check_rest_day(date("l",strtotime($currentdate)),$payroll_group,$company_id);
				}
				
				if($this->work_schedule_id != FALSE){
					/* EMPLOYEE WORK SCHEDULE */
					$check_work_schedule_flex = $this->employee->check_work_schedule_flex($this->work_schedule_id,$company_id,"work_schedule_id");
				}else{
					$check_work_schedule_flex = $this->employee->check_work_schedule_flex($payroll_group,$company_id,"payroll_group_id");
				}
				
				//check_work_schedule_flex
			
			}else{
				$check_rest_day = false;
				$check_work_schedule_flex = false;
			}
			
			if($get_split){
					$this->work_schedule_id = $get_split['work_schedule_id'];					
			}
			
			#holiday now
			$holiday = $this->elm->company_holiday($currentdate, $company_id);

			#check employee on leave
			$onleave 	= check_leave_appliction($currentdate,$emp_id,$company_id);
			$ileave 	= 'no';
			if($onleave){
				$ileave = 'yes';
			}
			
			if($check_work_schedule_flex){
				
				$assumed_breaks_flex_b 	= false;
				$flex_rules 			= $this->get_flex_rules($company_id,$work_schedule_id);
				
				if($flex_rules){
					$break_rules 	= $flex_rules->break_rules;
					$assumed_breaks = $flex_rules->assumed_breaks;
					$assumed_breaks = $assumed_breaks * 60;
								
					if($break_rules == "assumed"){
						$predict_new_time_out = strtotime($new_time_out);
						
						$number_of_breaks_b = $this->elmf->check_break_time_flex($this->work_schedule_id,$company_id,false);
						$predict_luch_outxb = strtotime($new_time_in."+ ".$assumed_breaks." minutes");
						$predict_luch_inxb = strtotime($predict_luch_outxb."+ ".$number_of_breaks_b." minutes");
						
						$predict_new_time_out = strtotime($new_time_out);
						$number_of_breaks_b = $this->elmf->check_break_time_flex($this->work_schedule_id,$company_id,false);
						$predict_luch_outxb = strtotime($new_time_in."+ ".$assumed_breaks." minutes");
						$predict_luch_outxb =  date("Y-m-d H:i:s",$predict_luch_outxb);
						$predict_luch_inxb 	= strtotime($predict_luch_outxb."+ ".$number_of_breaks_b." minutes");
						$predict_luch_inxb1 =  date("Y-m-d H:i:s",$predict_luch_inxb);
						
						$new_lunch_out 	= $predict_luch_outxb;
						$new_lunch_in	= $predict_luch_inxb1;
						
						if($predict_new_time_out <= $predict_luch_inxb){
							$new_lunch_out = null;
							$new_lunch_in = null;
						}
						
						$assumed_breaks_flex_b = true;
					}
					else if($break_rules == "disable"){
						
					}
				}
				
				$late_min 		= $this->elm->late_min($company_id,$currentdate,$emp_id,$work_schedule_id,"",$new_time_in);
				$overbreak_min 	= $this->elm->overbreak_min($company_id,$currentdate,$emp_id,$work_schedule_id,$new_lunch_out,"",$new_lunch_in);
				
				if($assumed_breaks_flex_b){
					$overbreak_min = 0;
				}
				
				/* FLEXIBLE HOURS */
				# restday
				if($check_rest_day){
					$tardiness = 0; 
					$undertime = 0;
					
					// update total hours and total hours required rest day
					$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
					if($real_employee_time_in_id){
						$time_query_where = array(
								'employee_time_in_id' 	=> $real_employee_time_in_id,
								"status"    			=> "Active"
						);
					}else{
						$time_query_where = array(
							"comp_id"	=> $company_id,
							"emp_id"	=> $emp_id,
							"date"		=> date("Y-m-d",strtotime($employee_timein_date)),
							"status"    => "Active"
						);
					}
					$this->db->where($time_query_where);
					$time_query = $this->edb->get('employee_time_in');
					$time_query_row = $time_query->row();
					$time_query->free_result();
					
					//we check the time in if have value if not well  just update
					if($time_query_row) {
						if($log_error){
							$date_insert = array(
									"comp_id"			=> $company_id,
									"emp_id"			=> $emp_id,
									"date"				=> $currentdate ,
									"time_in"			=> $new_time_in,
									"time_out"			=> $new_time_out,
									"work_schedule_id" 	=> -1,
									"total_hours"		=> 0 ,
									"total_hours_required"=> 0,
									"undertime_min"		=> 0,
									"tardiness_min"		=> 0
							);
						}
						else{
							$tq_update_field = array(
								"time_in"=>$new_time_in,
								"time_out"=>$new_time_out,
								"tardiness_min"=>$tardiness,
								"work_schedule_id" => -1,
								"undertime_min"=>$undertime,
								"total_hours"=>$get_total_hours,
								"total_hours_required"=>$get_total_hours,
								"change_log_date_filed" => date('Y-m-d H:i:s')
							);
						}
						if($time_query_row->source!=""){
							$tq_update_field['last_source'] = $source;
						}
						$this->db->where($time_query_where);
						$this->db->update('employee_time_in',$tq_update_field);
						
					}else{
						if($log_error){
							$date_insert = array(
								"source"=>$source,
								"comp_id"=>$company_id,
								"emp_id"=>$emp_id,
								"date"=>$currentdate,
								"time_in"=>$new_time_in,
								"time_out"=>$new_time_out,
								"tardiness_min"=>0,
								"undertime_min"=>0,
								"work_schedule_id" => -1,
								"total_hours"=>0,
								"flag_time_in" => 0,
								"total_hours_required"=>0
							);
						}else{
							$date_insert = array(
								"source"=>$source,
								"comp_id"=>$company_id,
								"date" => $currentdate,
								"emp_id"=>$emp_id,
								"time_in"=>$new_time_in,
								"time_out"=>$new_time_out,
								"tardiness_min"=>$tardiness,
								"undertime_min"=>$undertime,
								"work_schedule_id" => -1,
								"total_hours"=>$get_total_hours,
								"flag_time_in" => 0,
								"total_hours_required"=>$get_total_hours
							);
						}
						$add_logs = $this->db->insert('employee_time_in', $date_insert);
					}
				}
				else if($holiday){
					$h 			 = $this->elm->total_hours_worked($new_time_out,$new_time_in);
					$holday_hour = $this->elm->convert_to_hours($h);
					
					if($real_employee_time_in_id){
						$time_query_where = array(
								'employee_time_in_id' 	=> $real_employee_time_in_id,
								"status"    			=> "Active"
						);
					}
					else{
						$time_query_where = array(
							"comp_id"	=> $company_id,
							"emp_id"	=> $emp_id,
							"date"		=> date("Y-m-d",strtotime($employee_timein_date)),
							"status"    => "Active"
						);
					}
						
					$this->db->where($time_query_where);
					$time_query = $this->edb->get('employee_time_in');
					$time_query_row = $time_query->row();
					$time_query->free_result();
					
					if($time_query_row) {
						if($log_error){
							$date_insert = array(
									"comp_id"=>$company_id,
									"emp_id"=>$emp_id,
									"date"=>$currentdate ,
									"time_in"=>$new_time_in,
									"time_out"=>$new_time_out,
									"work_schedule_id"=>$work_schedule_id,
									"total_hours"=>0 ,
									"total_hours_required"=>0,
									"late_min" => 0,
									"tardiness_min" => 0,
									"undertime_min" => 0
							);
						}else{
						
						$tq_update_field = array(
								"time_in"=>$new_time_in,
								"time_out"=>$new_time_out,
								"work_schedule_id" => $work_schedule_id,
								"total_hours"=>$holday_hour,
								"total_hours_required"=>$holday_hour,
								"change_log_date_filed" => date('Y-m-d H:i:s'),
								"late_min" => 0,
								"tardiness_min" => 0,
								"undertime_min" => 0
						);
						}
						
						if($time_query_row->source!=""){
							$tq_update_field['last_source'] = $source;
						}
						$this->db->where($time_query_where);
						$this->db->update('employee_time_in',$tq_update_field);
					}else{
						
						if($log_error){
							$date_insert = array(
									"comp_id"=>$company_id,
									"emp_id"=>$emp_id,
									"date"=>$currentdate ,
									"source"=>$source,
									"time_in"=>$new_time_in,
									"time_out"=>$new_time_out,
									"work_schedule_id"=>$work_schedule_id,
									"total_hours"=>0 ,
									"total_hours_required"=>0,
									"late_min" => 0,
									"tardiness_min" => 0,
									"undertime_min" => 0
							);
						}else{
							$date_insert = array(
									"comp_id"=>$company_id,
									"emp_id"=>$emp_id,
									"date"=>$currentdate ,
									"source"=>$source,
									"time_in"=>$new_time_in,
									"time_out"=>$new_time_out,
									"work_schedule_id"=>$work_schedule_id,
									"total_hours"=>$holday_hour ,
									"total_hours_required"=>$holday_hour,
									"late_min" => 0,
									"tardiness_min" => 0,
									"undertime_min" => 0
							);
						}
						$add_logs = $this->db->insert('employee_time_in', $date_insert);
					}
				}
				else{
					
					/* WHOLE DAY */
					$half_day 				= $this->elm->if_half_day($new_time_in, $work_schedule_id, $company_id,$emp_no,$new_time_out,0,$emp_id,$currentdate);
					$get_hoursworked 		= $this->elmf->get_hoursworked($this->work_schedule_id,$company_id)->total_hours_for_the_day;
					$get_hoursworked_mins 	= $get_hoursworked * 60;
					// check workday settings
					$workday_settings_start_time = date("H:i:s",strtotime($new_time_in));
					$workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked_mins} minutes"));
					
					if($this->work_schedule_id != FALSE){
						#with work schedule 
						$flex_rules = $this->get_flex_rules($company_id,$work_schedule_id);
						if($flex_rules){
							$break_rules 	= $flex_rules->break_rules;
							$assumed_breaks = $flex_rules->assumed_breaks;
							$assumed_breaks = $assumed_breaks * 60;
							if($break_rules == "assumed"){
								$new_time_in;
								$predict_new_time_out 	= strtotime($new_time_out);
								
								$number_of_breaks_b 	= $this->elmf->check_break_time_flex($this->work_schedule_id,$company_id,false);
								$predict_luch_outxb 	= strtotime($new_time_in."+ ".$assumed_breaks." minutes");
								$predict_luch_inxb 		= strtotime($predict_luch_outxb."+ ".$number_of_breaks_b." minutes");
								
								$predict_new_time_out 	= strtotime($new_time_out);
								$number_of_breaks_b 	= $this->elmf->check_break_time_flex($this->work_schedule_id,$company_id,false);
								$predict_luch_outxb 	= strtotime($new_time_in."+ ".$assumed_breaks." minutes");
								$predict_luch_outxb 	=  date("Y-m-d H:i:s",$predict_luch_outxb);
								$predict_luch_inxb 		= strtotime($predict_luch_outxb."+ ".$number_of_breaks_b." minutes");
								$predict_luch_inxb1 	=  date("Y-m-d H:i:s",$predict_luch_inxb);
								
								$new_lunch_out 			= $predict_luch_outxb;
								$new_lunch_in			= $predict_luch_inxb1;
								
								if($predict_new_time_out <= $predict_luch_inxb){
									$new_lunch_out = null;
									$new_lunch_in = null;
								}
							}
							else if($break_rules == "disable"){
						
							}
						}
						
						$check_latest_timein_allowed = $this->elmf->check_lastest_timein_allowed($this->work_schedule_id,$company_id);
						
						#not required login
						if(!$check_latest_timein_allowed){
							
							$number_of_breaks_per_day = $this->elmf->check_break_time_flex($this->work_schedule_id,$company_id,false);

							if($number_of_breaks_per_day == 0){
								// update total hours and total hours required rest day
								$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
	
								// update total tardiness
								$update_tardiness = 0;
								
								// update undertime
								$update_undertime = 0;
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $emp_id, $this->work_schedule_id);
								$minx = $hours_worked * 60;
								$workday_settings_end_time = date("Y-m-d H:i:s",strtotime($new_time_in." +{$minx} minutes"));
								
								if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
									$update_undertime = $this->total_hours_worked($workday_settings_end_time,$new_time_out);
								}
								
								// check tardiness value
								$flag_tu 		= 0;
								$hours_worked 	= $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $emp_id, $this->work_schedule_id);
								$absent_min 	= $this->elm->absent_minutes_calc($work_schedule_id,$company_id,$new_time_in,$new_time_out,$emp_no,$update_undertime,$update_tardiness,true,$currentdate);
								
								if($absent_min){
								
									if($absent_min->half_day!=0){
										$break =0;
										$total_hours_worked1 = $this->elm->total_hours_worked($time_out, $time_in);
										$get_total_hours = $this->elm->convert_to_hours($total_hours_worked1);
								
										$new_lunch_out = null;
										$new_lunch_in = null;
										if($absent_min->half_day == 1){
											$half_day = 1;
											$update_tardiness = $absent_min->tardiness;
										}
										if($absent_min->half_day == 2){
											$half_day= 2;
											$update_undertime = $absent_min->undertime;
										}
									}
								}
								// required hours worked only
								$new_total_hours = $this->elmf->get_tot_hours($emp_id,$company_id,$new_time_in,$new_time_out,$hours_worked,$this->work_schedule_id);
								
								
								
								if($real_employee_time_in_id){
									$time_query_where = array(
											'employee_time_in_id' => $real_employee_time_in_id,
											"status"    => "Active"
									);
								}else{
									$time_query_where = array(
											"comp_id"	=> $company_id,
											"emp_id"	=> $emp_id,
											"date"		=> date("Y-m-d",strtotime($employee_timein_date)),
											"status"    => "Active"
									);
								}

								$this->db->where(" (time_in_status ='approved' OR time_in_status IS NULL) ",NULL,FALSE);
								$this->db->where($time_query_where);
								$time_query = $this->edb->get('employee_time_in');
								$time_query_row = $time_query->row();
								$time_query->free_result();
								
								if($time_query_row) {
									if($source == "updated" || $source == "recalculated" || $source == "import"){
										if($time_query_row->flag_regular_or_excess == "excess"){
											$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
											$get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
											$tq_update_field = array(
													"comp_id"					=> $company_id,
													"emp_id"					=> $emp_id,
													"date"						=> $currentdate,
													"time_in"					=> $new_time_in,
													"time_out"					=> $new_time_out,
													"undertime_min"				=> 0,
													"tardiness_min" 			=> 0,
													"late_min" 					=> 0,
													"overbreak_min" 			=> 0,
													"absent_min" 				=> 0,
													"work_schedule_id" 			=> "-2",
													"total_hours"				=> $get_total_hours,
													"total_hours_required"		=> $get_total_hours,
													"flag_regular_or_excess" 	=> "excess",
											);
											if($time_query_row->source!=""){
												$tq_update_field['last_source'] = $source;
											}else{
												$tq_update_field['last_source'] = 'dashboard';
											}
										}else{
											if($log_error){
												$tq_update_field = array(
														"time_in"=>$new_time_in,
														"time_out"=>$new_time_out,
														"undertime_min"=>0,
														"tardiness_min" => 0,
														"work_schedule_id" => $work_schedule_id,
														"total_hours"=>0,
														"absent_min" => 0,
														"lunch_in" => null,
														"lunch_out" => null,
														"total_hours_required"=>0
												);
												
											}else{
												
												$tq_update_field = array(
													"time_in"=>$new_time_in,
													"time_out"=>$new_time_out,
													"undertime_min" => $update_undertime,
													"tardiness_min" => $update_tardiness,
													"work_schedule_id" => $work_schedule_id,
													"total_hours"=>$get_hoursworked,
													"absent_min" => 0,
													"lunch_in" => null,
													"lunch_out" => null,
													"overbreak_min" => null,
													"late_min" =>0,
													"total_hours_required"=>$get_total_hours,
													"change_log_date_filed" => date('Y-m-d H:i:s'),
													"flag_on_leave" => $ileave
												);	
												
											}
											
											if($time_query_row->source!=""){
												$tq_update_field['last_source'] = $source;
											}
											
											#attendance settings
											$att = $this->elm->calculate_attendance($company_id,$new_time_in,$new_time_out);
											
											if($att){
												$total_hours_worked = $this->elm->total_hours_worked($new_time_out, $new_time_in);
												$total_hours_worked = $this->elm->convert_to_hours($total_hours_worked);
												$tq_update_field['lunch_in'] = null;
												$tq_update_field['lunch_out'] = null;
												$tq_update_field['total_hours_required'] = $total_hours_worked;
												$tq_update_field['absent_min'] = ($get_hoursworked - $total_hours_worked) * 60;
												$tq_update_field['late_min'] = 0;
												$tq_update_field['tardiness_min'] = 0;
												$tq_update_field['undertime_min'] = 0;
												
												
											}
										}
										$this->db->where($time_query_where);
										$this->db->update('employee_time_in',$tq_update_field);
									}else{
										$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
										$get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
										
										$date_insert = array(
												"source"					=> 'dashboard',
												"comp_id"					=> $company_id,
												"emp_id"					=> $emp_id,
												"date"						=> $currentdate,
												"time_in"					=> $new_time_in,
												"time_out"					=> $new_time_out,
												"undertime_min"				=> 0,
												"tardiness_min" 			=> 0,
												"late_min" 					=> 0,
												"overbreak_min" 			=> 0,
												"work_schedule_id" 			=> "-2",
												"total_hours"				=> $get_total_hours,
												"total_hours_required"		=> $get_total_hours,
												"flag_regular_or_excess" 	=> "excess",
										);
										$add_logs = $this->db->insert('employee_time_in', $date_insert);
									}
								}else{
									
									if($log_error){
										$date_insert = array(
												"comp_id"=>$company_id,
												"emp_id"=>$emp_id,
												"date"=>$currentdate,
												"time_in"=>$new_time_in,
												"time_out"=>$new_time_out,
												"tardiness_min"=> 0,
												"undertime_min"=> 0,
												"total_hours"=> 0,
												"absent_min" => 0,
												"total_hours_required"=>0
										);
									}else{
										$date_insert = array(
											"comp_id"				=> $company_id,
											"emp_id"				=> $emp_id,
											"source"	 			=> $source,
											"date"					=> $currentdate,
											"time_in"				=> $new_time_in,
											"time_out"				=> $new_time_out,
											"undertime_min"			=> $update_undertime,
											"tardiness_min" 		=> $update_tardiness,
											"work_schedule_id" 		=> $work_schedule_id,
											"total_hours"			=> $get_hoursworked,
											"total_hours_required"	=> $get_total_hours,
											"flag_on_leave" 		=> $ileave
										);		
										
										#attendance settings
										$att = $this->elm->calculate_attendance($company_id,$new_time_in,$new_time_out);
										
										if($att){
											$total_hours_worked = $this->elm->total_hours_worked($new_time_out, $new_time_in);
											$total_hours_worked = $this->elm->convert_to_hours($total_hours_worked);
											$date_insert['lunch_in'] = null;
											$date_insert['lunch_out'] = null;
											$date_insert['total_hours_required'] = $total_hours_worked;
											$date_insert['absent_min'] = ($get_hoursworked - $total_hours_worked) * 60;
											$date_insert['late_min'] = 0;
											$date_insert['tardiness_min'] = 0;
											$date_insert['undertime_min'] = 0;
										}
									}
								$add_logs = $this->db->insert('employee_time_in', $date_insert);
								}
							}else{
								$update_absent_min_x 	= 0;
								// update tardiness for timein
								$tardiness_timein 		= 0;
								
								// update tardiness for break time
								$update_tardiness_break_time 		= 0;
								$duration_of_lunch_break_per_day 	= $this->elmf->duration_of_lunch_break_per_day($emp_id, $company_id, $this->work_schedule_id);
								$lunch_break 						= $duration_of_lunch_break_per_day;
								$lunch_break_min 					= $duration_of_lunch_break_per_day / 60;
								$tardiness_a 						= (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
								
								if($duration_of_lunch_break_per_day < $tardiness_a){
									$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
								}
								
								$break =0;
								if($half_day==0){
									$break = ($duration_of_lunch_break_per_day / 60);
									$workday_settings_end_time = date("H:i:s",strtotime($workday_settings_end_time." +{$duration_of_lunch_break_per_day} minutes"));
								}
								
								$get_hoursworked = $get_hoursworked - $lunch_break_min;
								// this to correct total hours but i dont know if its correct :(
								$date_a 	= new DateTime($new_time_in);
								$date_b 	= new DateTime($new_time_out);
								$interval 	= date_diff($date_a,$date_b);
								$hour_bx 	= $interval->format('%h');
								$min_bx  	= $interval->format('%i');
								$min_bx		= $min_bx / 60;
								$hours_worked_new = $hour_bx + $min_bx;
								
								// trap if halfday
								// update total hours
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $emp_id, $this->work_schedule_id);
								$hours_worked = $hours_worked - $lunch_break_min;
								$update_total_hours = $hours_worked_new - $lunch_break_min;
								
								// update total tardiness
								$update_tardiness = $tardiness_timein + $update_tardiness_break_time;
								
								// update undertime
								$update_undertime = 0;
								if($update_total_hours < $hours_worked){
									$update_undertime = ($hours_worked - $update_total_hours) * 60;
								}
								
								if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
									$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $emp_id, $this->work_schedule_id);
									$hours_worked_mins = $hours_worked * 60;
									$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
								}
								
								if($hours_worked_new < $hours_worked){								
									$as = $this->get_attendance_settings($company_id);									
									if($as){
										$as_sethour = $as->hours;
										if(is_numeric($as_sethour)){
											if($hours_worked_new < $hours_worked){
												$update_undertime = 0;
												$update_absent_min_x =  ($hours_worked - $as_sethour) * 60;
												
												$update_undertime 	 =  ($hours_worked - ($hours_worked_new + $as_sethour)) * 60;
												if($update_undertime < 0){
													$update_undertime = 0;
												}
											}
										}
									}
									else{
										if($hours_worked_new < $hours_worked){
											$update_undertime = ($hours_worked - $hours_worked_new) * 60;
										}
									}
									
									$new_break = 0;
									$get_half = $get_hoursworked / 2;
									if($lunch_break_min > 0){
										$new_break = $lunch_break_min;
									}
									$get_half = $get_half + $new_break;
									
									if(!$assumed_breaks_flex_b){
										if($hours_worked_new <= $get_half){
											$new_lunch_out 		= NULL;
											$new_lunch_in  		= NULL;
											$lunch_break_min 	= 0;
										}
									}
								}
								
								// minus break
								/** --BARACK FLEX CORRECTION-- **/
								
								$tox = date('Y-m-d H:i',strtotime($new_time_out));
								$fromx = date('Y-m-d H:i',strtotime($new_time_in));
								$totalx      = strtotime($tox) - strtotime($fromx);
								$hoursx      = floor($totalx / 60 / 60);
								$minutesx    = floor(($totalx - ($hoursx * 60 * 60)) / 60);
								$hours_worked_new1 = (($hoursx * 60) + $minutesx)/60;
								
								$update_total_hours = $hours_worked_new1 - $lunch_break_min;
								
								//check undertime
								if($get_hoursworked > $update_total_hours){
									$update_undertime = ($get_hoursworked - $update_total_hours) * 60;
								}
								
								// calculate total hours break if assume
								if($flex_rules){
									$break_rules 	= $flex_rules->break_rules;
									$assumed_breaks = $flex_rules->assumed_breaks;
									$assumed_breaks = $assumed_breaks * 60;
								
									if($break_rules == "assumed"){
										$predict_new_time_out = strtotime($new_time_out);
										$number_of_breaks_b = $this->elmf->check_break_time_flex($this->work_schedule_id,$company_id,false);
										$predict_luch_outxb = strtotime($new_time_in."+ ".$assumed_breaks." minutes");
										$predict_luch_outxb1 =  date("Y-m-d H:i:s",$predict_luch_outxb);
										$predict_luch_inxb = strtotime($predict_luch_outxb1."+ ".$number_of_breaks_b." minutes");
										$predict_luch_inxb1 =  date("Y-m-d H:i:s",$predict_luch_inxb);
										
										if($predict_new_time_out <= $predict_luch_inxb){
											
											if($predict_new_time_out <= $predict_luch_outxb){
												$tox = $new_time_out;
											}else{
												$tox = $predict_luch_outxb1;
											}
											$fromx = date('Y-m-d H:i',strtotime($new_time_in));
											$totalx      = strtotime($tox) - strtotime($fromx);
											$hoursx      = floor($totalx / 60 / 60);
											$minutesx    = floor(($totalx - ($hoursx * 60 * 60)) / 60);
											$hours_worked_new1 = (($hoursx * 60) + $minutesx)/60;
												
											$update_total_hours = $hours_worked_new1;
											
											if($get_hoursworked > $update_total_hours){
												$update_undertime = ($get_hoursworked - $update_total_hours) * 60;
											}
										}
									}
									else if($break_rules == "capture"){
										// $time_in;$lunch_out;$lunch_in;$time_out;
										$time_in 				= date('Y-m-d H:i',strtotime($time_in));
										$lunch_out 				= date('Y-m-d H:i',strtotime($lunch_out));
										$lunch_in 				= date('Y-m-d H:i',strtotime($lunch_in));
										$time_out 				= date('Y-m-d H:i',strtotime($time_out));
										
										$shift_hour_worked 		= $get_hoursworked;
										$shift_half_worked 		= $shift_hour_worked/2;
										$shift_half_worked_min 	= $shift_half_worked * 60;
										$update_total_hours_required 	= "";
										$late_min						= 0;
										$overbreak_min					= 0;
										$break_for_break				= 0;
										$update_undertime				= 0;
									
										$fromx 							= date('Y-m-d H:i',strtotime($time_in));
										$tox 							= date('Y-m-d H:i',strtotime($time_out));
										$totalx      					= strtotime($tox) - strtotime($fromx);
										$hoursx      					= floor($totalx / 60 / 60);
										$minutesx    					= floor(($totalx - ($hoursx * 60 * 60)) / 60);
										$update_total_hours 			= (($hoursx * 60) + $minutesx)/60;
										
										$assumed_capture_lunch_out 	= date("Y-m-d H:i:s",strtotime($time_in ."+ ".$shift_half_worked_min." minutes"));
										$assumed_capture_lunch_in 	= date("Y-m-d H:i:s",strtotime($assumed_capture_lunch_out ."+ ".$duration_of_lunch_break_per_day." minutes"));
										
										if($lunch_in > $lunch_out){
											$break_for_break = $duration_of_lunch_break_per_day;
											$fromo 				= date('Y-m-d H:i',strtotime($lunch_out));
											$too 				= date('Y-m-d H:i',strtotime($lunch_in));
											$totalo      		= strtotime($too) - strtotime($fromo);
											$hourso      		= floor($totalo / 60 / 60);
											$minuteso    		= floor(($totalo - ($hourso * 60 * 60)) / 60);
											$actual_break		= (($hourso * 60) + $minuteso);
											if($actual_break > $break_for_break){
												$overbreak_min  = $actual_break - $break_for_break;
											}
										}
										else{
											// if timeOut before assumed capture break
											if(strtotime($assumed_capture_lunch_out) >= strtotime($time_out)){
												$break_for_break = 0;
											}
											// if timeOut between break
											else if(strtotime($assumed_capture_lunch_out) < strtotime($time_out) && strtotime($assumed_capture_lunch_in) >= strtotime($time_out)){
												$fromb 							= date('Y-m-d H:i',strtotime($time_out));
												$tob 							= date('Y-m-d H:i',strtotime($assumed_capture_lunch_in));
												$totalb      					= strtotime($tob) - strtotime($fromb);
												$hoursb      					= floor($totalb / 60 / 60);
												$minutesb    					= floor(($totalb - ($hoursb * 60 * 60)) / 60);
												$minus_for_break 				= (($hoursb * 60) + $minutesb);
												//$break_for_break				= $duration_of_lunch_break_per_day - $minus_for_break;
												$break_for_break 				= $duration_of_lunch_break_per_day;
											}
											// timein after assumed capture break
											else if(strtotime($assumed_capture_lunch_in) <= strtotime($time_out)){
												$break_for_break = 0;
											}
											// if timeOut after assumed capture break
											else{
												$break_for_break = $duration_of_lunch_break_per_day;
											}
										}
										// TOTAL HOURS WORKED
										$update_total_hours = $update_total_hours - ($break_for_break/60);
										// TARDINESS
										$update_tardiness = $late_min + $overbreak_min;
										// UNDERTIME
										if(($shift_hour_worked * 60) > (($update_total_hours_required * 60) + $update_tardiness)){
											$update_undertime = (($shift_hour_worked * 60) - (($update_total_hours * 60) + $update_tardiness));
										}
									}
								}
								
								// *** CHECKER
								/**
								 p($break_for_break);
								 p($assumed_capture_lunch_out);
								 p($assumed_capture_lunch_in);
								 p($shift_half_worked);
								 p($shift_hour_worked);
								 p($update_total_hours);
								 p($update_undertime);
								 p($update_tardiness);
								 exit("here");
								**/
								 // *** END CHECKER **/
								
								
								/** -- END BARACK FLEX CORRECTION-- **/
								
								// check tardiness value
								$get_total_hours_worked = ($hours_worked / 2) + .5;
								if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0 && $half_day == 0){
									$update_tardiness = 0;
									$flag_tu = 1;
								}								
								
								// update total hours required
								$update_total_hours_required = ((strtotime($new_time_out) - strtotime($new_time_in)) / 3600) - ($update_tardiness / 60) - $break;
								
								// if value is less then 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness 	= 0;
								if($update_undertime < 0) $update_undertime 	= 0;
								if($update_total_hours < 0) $update_total_hours = 0;
								if($update_total_hours_required < 0) $update_total_hours_required = 0;
								
								if($real_employee_time_in_id){
									$time_query_where = array(
											'employee_time_in_id' => $real_employee_time_in_id,
											"status"    => "Active"
									);
								}else{
										
									$time_query_where = array(
										"comp_id"	=> $company_id,
										"emp_id"	=> $emp_id,
										"date"		=> date("Y-m-d",strtotime($employee_timein_date)),
										"status"    => "Active"
									);
								}
								
								
								$this->db->where(" (time_in_status ='approved' OR time_in_status IS NULL) ",NULL,FALSE);
								$this->db->where($time_query_where);
								$time_query = $this->edb->get('employee_time_in');
								$time_query_row = $time_query->row();
								$time_query->free_result();
								
								if($time_query_row) {
									if($source == "updated" || $source == "recalculated" || $source == "import"){
										if($time_query_row->flag_regular_or_excess == "excess"){
											$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
											$get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
											$tq_update_field = array(
													"comp_id"					=> $company_id,
													"emp_id"					=> $emp_id,
													"date"						=> $currentdate,
													"time_in"					=> $new_time_in,
													"time_out"					=> $new_time_out,
													"undertime_min"				=> 0,
													"tardiness_min" 			=> 0,
													"late_min" 					=> 0,
													"overbreak_min" 			=> 0,
													"absent_min" 				=> 0,
													"work_schedule_id" 			=> "-2",
													"total_hours"				=> $get_total_hours,
													"total_hours_required"		=> $get_total_hours,
													"flag_regular_or_excess" 	=> "excess",
											);
											if($time_query_row->source!=""){
												$tq_update_field['last_source'] = $source;
											}else{
												$tq_update_field['last_source'] = 'dashboard';
											}
										}else{
											if($log_error){
												$tq_update_field 	= array(
																	"time_in"				=> $new_time_in,
																	"lunch_out"				=> $new_lunch_out,
																	"lunch_in"				=> $new_lunch_in,
																	"time_out"				=> $new_time_out,
																	"undertime_min"			=> 0,
																	"tardiness_min" 		=> 0,
																	"work_schedule_id" 		=> $work_schedule_id,
																	"total_hours"			=> 0,
																	"late_min" 				=> 0,
																	"overbreak_min" 		=> 0,
																	"total_hours_required"	=> 0
																	);
											}
											else{
												$tq_update_field 	= array(
																	"time_in"				=> $new_time_in,
																	"lunch_out"				=> $new_lunch_out,
																	"lunch_in"				=> $new_lunch_in,
																	"time_out"				=> $new_time_out,
																	"undertime_min"			=> $update_undertime,
																	"tardiness_min" 		=> $update_tardiness,
																	"absent_min" 			=> 0,
																	"work_schedule_id" 		=> $work_schedule_id,
																	"late_min" 				=> $late_min,
																	"overbreak_min" 		=> $overbreak_min,
																	"total_hours"			=> $get_hoursworked,
																	"total_hours_required"	=> $update_total_hours,
																	"change_log_date_filed" => date('Y-m-d H:i:s'),
																	"flag_on_leave" 		=> $ileave
																	);
											}
										
											if($time_query_row->source!=""){
												$tq_update_field['last_source'] = $source;
											}
											
											// ***** NEW COMPUTATION FOR ATTENDANCE SETTINGS
											$att = is_attendance_active($company_id);
											/****/
											if($att){
												if($update_total_hours <= $att){
													if($time_in >= $lunch_out){
														$tq_update_field['lunch_out'] 	= null;
														$tq_update_field['lunch_in'] 	= null;
													}
													elseif($time_out <= $lunch_in){
														$tq_update_field['lunch_out'] 	= null;
														$tq_update_field['lunch_in'] 	= null;
													}
											
													$half_day_h = ($hours_worked / 2) * 60;
													if($update_undertime > $late_min){
														$tq_update_field['late_min'] 		= $late_min;
														$tq_update_field['tardiness_min'] 	= $update_tardiness;
														$tq_update_field['undertime_min'] 	= 0;
														$tq_update_field['absent_min'] 		= (($get_hoursworked - $update_total_hours) * 60) - $update_tardiness;
													}
													else{
														$tq_update_field['late_min'] 		= 0;
														$tq_update_field['tardiness_min'] 	= 0;
														$tq_update_field['undertime_min'] 	= $update_undertime;
														$tq_update_field['absent_min'] 		= (($get_hoursworked - $update_total_hours) * 60) - $update_undertime;
													}
													$date_insert['total_hours_required'] 	= $update_total_hours;
												}
											}
										}
										$this->db->where($time_query_where);
										$this->db->update('employee_time_in',$tq_update_field);
									}else{
										$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
										$get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
										
										$date_insert = array(
												"source"					=> 'dashboard',
												"comp_id"					=> $company_id,
												"emp_id"					=> $emp_id,
												"date"						=> $currentdate,
												"time_in"					=> $new_time_in,
												"time_out"					=> $new_time_out,
												"undertime_min"				=> 0,
												"tardiness_min" 			=> 0,
												"late_min" 					=> 0,
												"overbreak_min" 			=> 0,
												"work_schedule_id" 			=> "-2",
												"total_hours"				=> $get_total_hours,
												"total_hours_required"		=> $get_total_hours,
												"flag_regular_or_excess" 	=> "excess",
										);
										$add_logs = $this->db->insert('employee_time_in', $date_insert);
									}
								}else{
									if($log_error){
										$date_insert 	= array(
														"source"				=> $source,
														"comp_id"				=> $company_id,
														"emp_id"				=> $emp_id,
														"date"					=> $currentdate_orig_date_from_input,
														"time_in"				=> $new_time_in,
														"lunch_out"				=> $new_lunch_out,
														"lunch_in"				=> $new_lunch_in,
														"time_out"				=> $new_time_out,
														"tardiness_min"			=> 0,
														"undertime_min"			=> 0,
														"absent_min" 			=> 0,
														"late_min" 				=> 0,
														"overbreak_min" 		=> 0,
														"total_hours" 			=> $hours_worked,
														"total_hours_required"	=> 0
														);
									}
									else{
										$date_insert 	= array(
														"source"				=> $source,
														"comp_id"				=> $company_id,
														"emp_id"				=> $emp_id,
														"date"					=> $currentdate_orig_date_from_input,
														"time_in"				=> $new_time_in,
														"lunch_out"				=> $new_lunch_out,
														"lunch_in"				=> $new_lunch_in,
														"time_out"				=> $new_time_out,
														"undertime_min"			=> $update_undertime,
														"tardiness_min" 		=> $update_tardiness,
														"absent_min" 			=> 0,
														"work_schedule_id" 		=> $work_schedule_id,
														"late_min" 				=> $late_min,
														"overbreak_min" 		=> $overbreak_min,
														"total_hours" 			=> $hours_worked,
														"total_hours_required"	=> $update_total_hours,
														"flag_on_leave" 		=> $ileave
														);
										
										// ***** NEW COMPUTATION FOR ATTENDANCE SETTINGS
										$att = is_attendance_active($company_id);
										/****/
										if($att){
											if($update_total_hours <= $att){
												if($time_in >= $lunch_out){
													$date_insert['lunch_out'] 	= null;
													$date_insert['lunch_in'] 	= null;
												}
												elseif($time_out <= $lunch_in){
													$date_insert['lunch_out'] 	= null;
													$date_insert['lunch_in'] 	= null;
												}
													
												$half_day_h = ($hours_worked / 2) * 60;
												if($update_undertime > $late_min){
													$date_insert['late_min'] 		= $late_min;
													$date_insert['tardiness_min'] 	= $update_tardiness;
													$date_insert['undertime_min'] 	= 0;
													$date_insert['absent_min'] 		= (($hours_worked - $update_total_hours) * 60) - $update_tardiness;
												}
												else{
													$date_insert['late_min'] 		= 0;
													$tq_update_field['tardiness_min'] 	= 0;
													$date_insert['undertime_min'] 	= $update_undertime;
													$date_insert['absent_min'] 		= (($hours_worked - $update_total_hours) * 60) - $update_undertime;
												}
												$date_insert['total_hours_required'] 	= $update_total_hours;
											}
										}
										
									}
									
									$add_logs = $this->db->insert('employee_time_in', $date_insert);
								}
							}
						}else{
							# with latest timein
							$number_of_breaks_per_day = $this->elmf->check_break_time_flex($this->work_schedule_id,$company_id);
							$workday_settings_start_time = date("H:i:s",strtotime($check_latest_timein_allowed->latest_time_in_allowed));

							$g = ($get_hoursworked * 60);
							$workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$g} minute"));
							$workday_settings_start_time2 = date("Y-m-d H:i:s",strtotime($currentdate." ".$check_latest_timein_allowed->latest_time_in_allowed));
							if($new_time_in < $workday_settings_start_time2){
								$workday_settings_start_time2 = $new_time_in;
							}
							
							$workday_settings_end_time2 = date("Y-m-d H:i:s",strtotime($workday_settings_start_time2." +{$g} minute"));
							
							if($number_of_breaks_per_day == 0){
								
								// update total hours and total hours required rest day
								$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
								
								if($new_time_in !="" && $new_lunch_out=="" && $new_lunch_in=="" && $new_time_out!=""){
									$log_error = false;
								}
								// update tardiness for timein
								$tardiness_timein = 0;
								if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
									if(date("A",strtotime($new_time_in)) == "AM"){
										// add one day for time in log
										$new_start_timein = date("Y-m-d",strtotime($new_time_in." -1 day"));
										$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
										
										$tardiness_set = $this->elm->tardiness_settings($emp_id, $company_id);
										
										if($tardiness_set){
											$new_start_timein = date('Y-m-d H:i:s',strtotime($new_start_timein." +{$tardiness_set} minutes"));
										}
										
										if(strtotime($new_start_timein) < strtotime($new_time_in)){
											$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;			
										}
									}
								}else{
									
									$new_start_timein = $currentdate." ".$workday_settings_start_time;
									
									$tardiness_set = $this->elm->tardiness_settings($emp_id, $company_id);
										
									if($tardiness_set){
										$new_start_timein = date('Y-m-d H:i:s',strtotime($new_start_timein." +{$tardiness_set} minutes"));
									}
									
									if($new_time_in > $new_start_timein ){
										$tardiness_timein = $this->total_hours_worked($new_time_in,$new_start_timein);	
										
									}
									
								}
	
								// update total tardiness
								$update_tardiness = $tardiness_timein;
								
								// update undertime
								$update_undertime = 0;
								if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
									$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $emp_id, $this->work_schedule_id);
									$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
									
								}
							
								if( $new_time_out < $workday_settings_end_time2){
									
									$update_undertime = $this->total_hours_worked($workday_settings_end_time2, $new_time_out);
									
									//echo $update_undertime;
								}
								
								// check tardiness value
								$flag_tu = 0;
								
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $emp_id, $this->work_schedule_id);
								$get_total_hours_worked = ($hours_worked / 2) + .5;
							
								// required hours worked only
								$new_total_hours = $this->elmf->get_tot_hours($emp_id,$company_id,$new_time_in,$new_time_out,$hours_worked,$this->work_schedule_id);
								
								// if value is less than 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($new_total_hours < 0) $new_total_hours = 0;
								if($get_total_hours < 0) $get_total_hours = 0;										
																
								
								if($real_employee_time_in_id){
									$time_query_where = array(
											'employee_time_in_id' => $real_employee_time_in_id,
											"status"    => "Active"
									);
								}else{							
									
									$time_query_where = array(
											"comp_id"	=> $company_id,
											"emp_id"	=> $emp_id,
											"date"		=> date("Y-m-d",strtotime($employee_timein_date)),
											"status"    => "Active"
									);
								}
								
								$this->db->where(" (time_in_status ='approved' OR time_in_status IS NULL) ",NULL,FALSE);
								$this->db->where($time_query_where);
								$time_query = $this->edb->get('employee_time_in');
								$time_query_row = $time_query->row();
								$time_query->free_result();
								
								if($time_query_row){
									if($source == "updated" || $source == "recalculated" || $source == "import"){
										
										if($time_query_row->flag_regular_or_excess == "excess"){
											$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
											$get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
											$tq_update_field = array(
													"comp_id"					=> $company_id,
													"emp_id"					=> $emp_id,
													"date"						=> $currentdate,
													"time_in"					=> $new_time_in,
													"time_out"					=> $new_time_out,
													"undertime_min"				=> 0,
													"tardiness_min" 			=> 0,
													"late_min" 					=> 0,
													"overbreak_min" 			=> 0,
													"absent_min" 				=> 0,
													"work_schedule_id" 			=> "-2",
													"total_hours"				=> $get_total_hours,
													"total_hours_required"		=> $get_total_hours,
													"flag_regular_or_excess" 	=> "excess",
											);
											if($time_query_row->source!=""){
												$tq_update_field['last_source'] = $source;
											}else{
												$tq_update_field['last_source'] = 'dashboard';
											}
										}else{
											if($log_error){
												$tq_update_field = array(
														"time_in"=>$new_time_in,
														"time_out"=>$new_time_out,
														"undertime_min"=>0,
														"tardiness_min" => 0,
														"work_schedule_id" => $work_schedule_id,
														"late_min" => 0,
														"overbreak_min" => 0,
														"total_hours"=>0,
														"total_hours_required"=>0
												);
											}else{
												$tq_update_field = array(
													"time_in"=>$new_time_in,
													"time_out"=>$new_time_out,
													"lunch_in" => null,
													"lunch_out" => null,
													"undertime_min"=>$update_undertime,
													"tardiness_min" => $update_tardiness,
													"work_schedule_id" => $work_schedule_id,
													"late_min" => $late_min,
													"overbreak_min" => $overbreak_min,									
													"total_hours"=>$get_hoursworked,
													"total_hours_required"=>$get_total_hours,
													"change_log_date_filed" => date('Y-m-d H:i:s'),
													"flag_on_leave" => $ileave
												);
											}
											
											if($time_query_row->source!=""){
												$tq_update_field['last_source'] = $source;
											}
											
											#attendance settings
											$att = $this->elm->calculate_attendance($company_id,$new_time_in,$new_time_out);
											
											if($att){
												$total_hours_worked = $this->elm->total_hours_worked($new_time_out, $new_time_in);
												$total_hours_worked = $this->elm->convert_to_hours($total_hours_worked);
												$tq_update_field['lunch_in'] = null;
												$tq_update_field['lunch_out'] = null;
												$tq_update_field['total_hours_required'] = $total_hours_worked;
												$tq_update_field['absent_min'] = ($get_hoursworked - $total_hours_worked) * 60;
												$tq_update_field['late_min'] = 0;
												$tq_update_field['tardiness_min'] = 0;
												$tq_update_field['undertime_min'] = 0;
											}
										}
										
										$this->db->where($time_query_where);
										$this->db->update('employee_time_in',$tq_update_field);
										
									}else{
										$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
										$get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
										
										$date_insert = array(
											"source"					=> 'dashboard',
											"comp_id"					=> $company_id,
											"emp_id"					=> $emp_id,
											"date"						=> $currentdate,
											"time_in"					=> $new_time_in,
											"time_out"					=> $new_time_out,
											"undertime_min"				=> 0,
											"tardiness_min" 			=> 0,
											"late_min" 					=> 0,
											"overbreak_min" 			=> 0,
											"work_schedule_id" 			=> "-2",
											"total_hours"				=> $get_total_hours,
											"total_hours_required"		=> $get_total_hours,
											"flag_regular_or_excess" 	=> "excess",
										);
										$add_logs = $this->db->insert('employee_time_in', $date_insert);
									}
								}else{
									if($log_error){
										$date_insert = array(
												"source"=>$source,
												"comp_id"=>$company_id,
												"emp_id"=>$emp_id,
												"date"=>$currentdate,
												"time_in"=>$new_time_in,
												"time_out"=>$new_time_out,
												"tardiness_min"=>0,
												"undertime_min"=>0,
												"late_min" => 0,
												"overbreak_min" => 0,
												"total_hours"=>0,
												"total_hours_required"=>0
										);
									}
									else{
										$date_insert = array(
											"source"=>$source,
											"comp_id"=>$company_id,
											"emp_id"=>$emp_id,
											"date"=>$currentdate,
											"time_in"=>$new_time_in,
											"time_out"=>$new_time_out,
											"undertime_min"=>$update_undertime,
											"tardiness_min" => $update_tardiness,
											"late_min" => $late_min,
											"overbreak_min" => $overbreak_min,
											"work_schedule_id" => $work_schedule_id,
											"total_hours"=>$get_hoursworked,
											"total_hours_required"=>$get_total_hours,
											"flag_on_leave" => $ileave
										);
										
										#attendance settings
										$att = $this->elm->calculate_attendance($company_id,$new_time_in,$new_time_out);
										
										if($att){
											$total_hours_worked = $this->elm->total_hours_worked($new_time_out, $new_time_in);
											$total_hours_worked = $this->elm->convert_to_hours($total_hours_worked);
											$date_insert['lunch_in'] = null;
											$date_insert['lunch_out'] = null;
											$date_insert['total_hours_required'] = $total_hours_worked;
											$date_insert['absent_min'] = ($get_hoursworked - $total_hours_worked) * 60;
											$date_insert['late_min'] = 0;
											$date_insert['tardiness_min'] = 0;
											$date_insert['undertime_min'] = 0;
										}
									}
									
									$add_logs = $this->db->insert('employee_time_in', $date_insert);
								}
								
								// end no break;		
							}else{
								// update tardiness for timein
								$half_day = 0;
								$vx = $this->elm->activate_nightmare_trap_upload($company_id,$emp_no,$new_time_in,$new_time_out);
								$end_time = date('Y-m-d H:i:s',strtotime($currentdate." ".$workday_settings_end_time));
								
								if($vx){
									$currentdate = $vx['current_date'];	
									$end_time = $vx['end_time'];
								
								}
								$tardiness_timein = 0;
								
								if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
									if(date("A",strtotime($new_time_in)) == "AM"){
										
										// add one day for time in log
										$new_start_timein = date("Y-m-d",strtotime($new_time_in." -1 day"));
										$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
										
										$tardiness_set = $this->elm->tardiness_settings($emp_id, $company_id);
										
										if($tardiness_set){
											$new_start_timein = date('Y-m-d H:i:s',strtotime($new_start_timein." +{$tardiness_set} minutes"));
										}
										
										if(strtotime($new_start_timein) < strtotime($new_time_in)){
											$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;			
										}
										
										
									}else{
										
										$end_time = date('Y-m-d H:i:s',strtotime($currentdate." ".$workday_settings_end_time));
										$end_time = date('Y-m-d H:i:s',strtotime($end_time." +1 day"));
									}
								}
									
								$new_start_timein = date("Y-m-d",strtotime($currentdate));
								$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
								$new_start_timein_orig = $new_start_timein;
								
								// get tardiness settings ----> mausab ni puhon ----> grace period ra ni karon
								$tardiness_set = $this->elm->tardiness_settings($emp_id, $company_id);
								if($tardiness_set){
									// grace period is here ---> create new latest timein ---> current latest + grace period as what we discuss
									$new_start_timein = date('Y-m-d H:i:s',strtotime($new_start_timein." +{$tardiness_set} minutes"));
								}
								
								// compute tardiness here --> late
								if($new_time_in > $new_start_timein ){
									$tardiness_timein = $this->total_hours_worked($new_time_in, $new_start_timein);		
									$late_min		  = $tardiness_timein;
								}
								
								// update tardiness for break time
								$update_tardiness_break_time = 0;
								
								// get break time
								$duration_of_lunch_break_per_day = $this->get_break_flex($company_id, $this->work_schedule_id);
								
								// compute total breaktime
								$tardiness_a = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
								
								// if assume break
								if($assumed_breaks_flex_b){
									$tardiness_a = $duration_of_lunch_break_per_day;
								}
								
								// if break hour allowed is less than '<' total break time
								if($duration_of_lunch_break_per_day < $tardiness_a){
									// compute for over break
									$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
								}
								
								// update total tardiness initial late '+' over break
								$update_tardiness = $tardiness_timein + $update_tardiness_break_time;
								
								// update undertime
								$update_undertime = 0;

								if($new_time_out < $end_time){
									//echo $end_time . "xxxx".$new_time_out;
									$start_timez =  date('Y-m-d H:i:s',strtotime($currentdate. " ". $workday_settings_start_time));
									if($new_time_in < $start_timez){
										$end_time = date('Y-m-d H:i:s',strtotime($new_time_in. " +{$g} minute"));										
									}
									$update_undertime = $this->elm->total_hours_worked($end_time, $new_time_out);
									
								}	
							
								// update total hours
								$hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $emp_id, $this->work_schedule_id);
								
								// convert break min to hour
								$break = $duration_of_lunch_break_per_day / 60;
								// update total hour --> total hours worked minus '-' tardiness, undertime and break
								$update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - $break;
								
								// check tardiness value
								$get_total_hours_worked = ($hours_worked / 2) + .5;
								
								// check if halfday
								$absent_min = $this->elm->absent_minutes_calc($work_schedule_id,$company_id,$new_time_in,$new_time_out,$emp_no,$update_undertime,$update_tardiness,true,$currentdate,$assumed_breaks_flex_b);
															 
								// update total hours required
								
								$total_hours_worked1 = ($this->elm->total_hours_worked($time_out, $time_in) - ($break * 60) ) - $update_tardiness_break_time;
								$update_total_hours_required= $this->elm->convert_to_hours($total_hours_worked1);
								
								if($absent_min){
									if($absent_min->half_day!=0){
										$break =0;
										$total_hours_worked1 = $this->elm->total_hours_worked($time_out, $time_in);
										$update_total_hours_required= $this->elm->convert_to_hours($total_hours_worked1);
										$new_lunch_out = null;
										$new_lunch_in = null;
										if($absent_min->half_day == 1){
											$half_day = 1;
											$update_tardiness = $absent_min->tardiness;
										}
										if($absent_min->half_day == 2){
											$half_day= 2;
											$update_undertime = $absent_min->undertime;
										}
									}
								}
								
								// if value is less than 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($update_total_hours < 0) $update_total_hours = 0;
								if($update_total_hours_required < 0) $update_total_hours_required = 0;
								
								/** --BARACK FLEX CORRECTION-- **/
								
								//check undertime --> overwrite current undertime
								$get_hoursworked1z = $get_hoursworked - ($duration_of_lunch_break_per_day/60);
								$update_total_hours_required1z = $update_total_hours_required + ($update_tardiness/60);
								
								// if required hours worked is less than hours worked plus '+' tardiness
								if($get_hoursworked1z > $update_total_hours_required1z){
									$update_undertime = ($get_hoursworked1z - $update_total_hours_required1z) * 60;
								}
								// if flex sched break is assume disregard all ur prev computation 
								if($flex_rules){
									$break_rules 	= $flex_rules->break_rules;
									$assumed_breaks = $flex_rules->assumed_breaks;
									$assumed_breaks = $assumed_breaks * 60;
									
									//check grace period
									$grace_period = 0;
									if($tardiness_set){
										$grace_period = $tardiness_set/60;
									}
									
									if($break_rules == "assumed"){
										$new_start_timeinb 		= strtotime($new_start_timein_orig);
										$new_time_inb 			= strtotime($new_time_in);
										
										$predict_new_time_out 	= strtotime($new_time_out);
										$number_of_breaks_b 	= $this->elmf->check_break_time_flex($this->work_schedule_id,$company_id,false);
										
										if($new_time_inb <= $new_start_timeinb){	// ---> timein before latest time
											$predict_luch_outxb 	= strtotime($new_time_in."+ ".$assumed_breaks." minutes");
										}
										else{										// ---> timein after latest time
											$predict_luch_outxb 	= strtotime($new_start_timein_orig."+ ".$assumed_breaks." minutes");
										}
										$predict_luch_outxb1 	=  date("Y-m-d H:i:s",$predict_luch_outxb);
										$predict_luch_inxb 		= strtotime($predict_luch_outxb1."+ ".$number_of_breaks_b." minutes");
										$predict_luch_inxb1 	=  date("Y-m-d H:i:s",$predict_luch_inxb);
										
										if($new_time_inb < $predict_luch_outxb){ // ---> assume timein before break
											$new_lunch_out 	= $predict_luch_outxb1;
											$new_lunch_in	= $predict_luch_inxb1;
											if($predict_new_time_out <= $predict_luch_inxb){ // ---> assume timeout before break
												
												$new_lunch_out 	= null;
												$new_lunch_in	= null;												
												if($predict_new_time_out <= $predict_luch_outxb){
													$tox = $new_time_out;
												}else{
													$tox = $predict_luch_outxb1;
												}
												
												$fromx 				= date('Y-m-d H:i',strtotime($new_time_in));
												$totalx      		= strtotime($tox) - strtotime($fromx);
												$hoursx      		= floor($totalx / 60 / 60);
												$minutesx    		= floor(($totalx - ($hoursx * 60 * 60)) / 60);
												$hours_worked_new1 	= (($hoursx * 60) + $minutesx)/60;
												$update_total_hours_required = $hours_worked_new1;
												
												// check tardiness --> if timein before latest timein tardiness should is zero
												if($new_time_inb <= $new_start_timeinb){
													$late_min			= 0;
													$update_tardiness 	= 0;
													$grace_period 		= 0;
												}
												else{
													$late_min			= $this->total_hours_worked($new_time_in, $new_start_timein);
													$update_tardiness 	= $late_min;
												}
												
												//check undertime
												$get_hoursworked1z = $get_hoursworked - ($duration_of_lunch_break_per_day/60);
												$update_total_hours_required1z = $update_total_hours_required + ($update_tardiness/60) + $grace_period;
												if($get_hoursworked1z > $update_total_hours_required1z){
													$update_undertime = ($get_hoursworked1z - $update_total_hours_required1z) * 60;
												}else{
													$update_undertime = 0;
												}
											}else{
												//check undertime
												$get_hoursworked1z = $get_hoursworked - ($duration_of_lunch_break_per_day/60);
												$update_total_hours_required1z = $update_total_hours_required + ($update_tardiness/60) + $grace_period;
												if($get_hoursworked1z > $update_total_hours_required1z){
													$update_undertime = ($get_hoursworked1z - $update_total_hours_required1z) * 60;
												}else{
													$update_undertime = 0;
												}
											}
										}else{									// ---> assume timein after or between break
											
											$new_lunch_out 	= null;
											$new_lunch_in	= null;
											if($new_time_inb <= $predict_luch_inxb){ // ----> timein before break end
												$fromx 		= date('Y-m-d H:i',strtotime($predict_luch_inxb1));
												$late_min	= $this->total_hours_worked($predict_luch_inxb1, $new_start_timein);	
											}
											else{									// ----> timein after break end
												$fromx = date('Y-m-d H:i',strtotime($new_time_in));
												$late_min	= $this->total_hours_worked($new_time_in, $new_start_timein);
											}
											
											$tox 				= date('Y-m-d H:i',strtotime($new_time_out));
											$totalx      		= strtotime($tox) - strtotime($fromx);
											$hoursx      		= floor($totalx / 60 / 60);
											$minutesx    		= floor(($totalx - ($hoursx * 60 * 60)) / 60);
											$hours_worked_new1 	= (($hoursx * 60) + $minutesx)/60;
											
											$update_total_hours_required = $hours_worked_new1;
											// update tardiness halfday noon
											$late_min 						= $late_min - $duration_of_lunch_break_per_day;
											$update_tardiness 				= $late_min;
											
											//check undertime
											$get_hoursworked1z 				= $get_hoursworked - ($duration_of_lunch_break_per_day/60);
											$update_total_hours_required1z 	= $update_total_hours_required + ($update_tardiness/60) + $grace_period;
											if($get_hoursworked1z > $update_total_hours_required1z){
												$update_undertime = ($get_hoursworked1z - $update_total_hours_required1z) * 60;
											}else{
												$update_undertime = 0;
											}
										}
									}else if($break_rules == "capture"){
										// $new_start_timein_orig;
										// $time_in;$lunch_out;$lunch_in;$time_out;
										$shift_hour_worked = $get_hoursworked - ($duration_of_lunch_break_per_day/60);
										$shift_half_worked = $shift_hour_worked/2;
										$shift_half_worked_min = $shift_half_worked * 60;
										$update_total_hours_required 	= "";
										$update_tardiness				= 0;
										$late_min						= 0;
										$overbreak_min					= 0;
										$break_for_break				= 0;
										$update_undertime				= 0;
										
										$fromx 							= date('Y-m-d H:i',strtotime($time_in));
										$tox 							= date('Y-m-d H:i',strtotime($time_out));
										$totalx      					= strtotime($tox) - strtotime($fromx);
										$hoursx      					= floor($totalx / 60 / 60);
										$minutesx    					= floor(($totalx - ($hoursx * 60 * 60)) / 60);
										$update_total_hours_required 	= (($hoursx * 60) + $minutesx)/60;
										
										// if timein before latest timeIn 
										if(strtotime($time_in) < strtotime($new_start_timein_orig)){
											$assumed_capture_lunch_out 	= date("Y-m-d H:i:s",strtotime($time_in ."+ ".$shift_half_worked_min." minutes"));
											$assumed_capture_lunch_in 	= date("Y-m-d H:i:s",strtotime($assumed_capture_lunch_out ."+ ".$duration_of_lunch_break_per_day." minutes"));
										}
										// if timein on or after latest timeIn
										else if (strtotime($time_in) >= strtotime($new_start_timein_orig)){
											$assumed_capture_lunch_out 	= date("Y-m-d H:i:s",strtotime($new_start_timein_orig ."+ ".$shift_half_worked_min." minutes"));
											$assumed_capture_lunch_in 	= date("Y-m-d H:i:s",strtotime($assumed_capture_lunch_out ."+ ".$duration_of_lunch_break_per_day." minutes"));
											if(strtotime($time_in) > strtotime($new_start_timein_orig)){
												$froml 							= date('Y-m-d H:i',strtotime($new_start_timein_orig));
												$tol 							= date('Y-m-d H:i',strtotime($time_in));
												$totall      					= strtotime($tol) - strtotime($froml);
												$hoursl      					= floor($totall / 60 / 60);
												$minutesl    					= floor(($totall - ($hoursl * 60 * 60)) / 60);
												$late_min 						= (($hoursl * 60) + $minutesl);
											}
										}
										
										if($lunch_in > $lunch_out){
											$break_for_break = $duration_of_lunch_break_per_day;
											
											$fromo 				= date('Y-m-d H:i',strtotime($lunch_out));
											$too 				= date('Y-m-d H:i',strtotime($lunch_in));
											$totalo      		= strtotime($too) - strtotime($fromo);
											$hourso      		= floor($totalo / 60 / 60);
											$minuteso    		= floor(($totalo - ($hourso * 60 * 60)) / 60);
											$actual_break		= (($hourso * 60) + $minuteso);
											if($actual_break > $break_for_break){
												$overbreak_min  = $actual_break - $break_for_break;
											}
										}
										else{
											// if timeOut before assumed capture break
											if(strtotime($assumed_capture_lunch_out) >= strtotime($time_out)){
												$break_for_break = 0;
											}
											// if timeOut between break
											else if(strtotime($assumed_capture_lunch_out) < strtotime($time_out) && strtotime($assumed_capture_lunch_in) >= strtotime($time_out)){
												$fromb 							= date('Y-m-d H:i',strtotime($time_out));
												$tob 							= date('Y-m-d H:i',strtotime($assumed_capture_lunch_in));
												$totalb      					= strtotime($tob) - strtotime($fromb);
												$hoursb      					= floor($totalb / 60 / 60);
												$minutesb    					= floor(($totalb - ($hoursb * 60 * 60)) / 60);
												$minus_for_break 				= (($hoursb * 60) + $minutesb);
												$break_for_break 				= $duration_of_lunch_break_per_day;
											}
											// timein after assumed capture break
											else if(strtotime($assumed_capture_lunch_in) <= strtotime($time_out) && strtotime($assumed_capture_lunch_out) > strtotime($time_out)){
												$break_for_break = 0;
											}
											// if timeOut after assumed capture break
											else{
												$break_for_break = $duration_of_lunch_break_per_day;
											}
										}
										// total hours worked
										$update_total_hours_required = $update_total_hours_required - ($break_for_break/60);
										// TARDINESS
										$update_tardiness = $late_min + $overbreak_min;
										// UNDERTIME
										if(($shift_hour_worked * 60) > (($update_total_hours_required * 60) + $update_tardiness)){
											$update_undertime = (($shift_hour_worked * 60) - (($update_total_hours_required * 60) + $update_tardiness));
										}
									}
								}
								/** -- END BARACK FLEX CORRECTION-- **/
								if($real_employee_time_in_id){
									$time_query_where = array(
											'employee_time_in_id' => $real_employee_time_in_id,
											"status"    => "Active"
									);
								}else{
									$time_query_where = array(
											"comp_id"	=> $company_id,
											"emp_id"	=> $emp_id,
											"date"		=> $currentdate,
											"status"    => "Active"
									);
								}
								$this->db->where(" (time_in_status ='approved' OR time_in_status IS NULL) ",NULL,FALSE);
								$this->db->where($time_query_where);
								$time_query = $this->edb->get('employee_time_in');
								$time_query_row = $time_query->row();
								$time_query->free_result();
								
								// *** CHECKER
								/**
								p($break_for_break);
								p($new_start_timein_orig);
								p($assumed_capture_lunch_out);
								p($shift_half_worked);
								p($shift_hour_worked);
								p($update_total_hours_required);
								p($update_undertime);
								p($update_tardiness);
								p($get_hoursworked - ($duration_of_lunch_break_per_day/60));
								exit("here");
								**/
								// *** END CHECKER **/
								
								if($time_query_row) {
									if($source == "updated" || $source == "recalculated" || $source == "import"){
										if($time_query_row->flag_regular_or_excess == "excess"){
											$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
											$get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
											$tq_update_field = array(
													"comp_id"					=> $company_id,
													"emp_id"					=> $emp_id,
													"date"						=> $currentdate,
													"time_in"					=> $new_time_in,
													"time_out"					=> $new_time_out,
													"undertime_min"				=> 0,
													"tardiness_min" 			=> 0,
													"late_min" 					=> 0,
													"overbreak_min" 			=> 0,
													"absent_min" 				=> 0,
													"work_schedule_id" 			=> "-2",
													"total_hours"				=> $get_total_hours,
													"total_hours_required"		=> $get_total_hours,
													"flag_regular_or_excess" 	=> "excess",
											);
											if($time_query_row->source!=""){
												$tq_update_field['last_source'] = $source;
											}else{
												$tq_update_field['last_source'] = 'dashboard';
											}
										}else{
											if($log_error){
												$tq_update_field = array(
														"time_in"=>$new_time_in,
														"lunch_out"=>$new_lunch_out,
														"lunch_in"=>$new_lunch_in,
														"time_out"=>$new_time_out,
														"undertime_min"=>0,
														"date"=>$currentdate,
														"tardiness_min" => 0,
														"late_min" =>0,
														"overbreak_min" => 0,
														"work_schedule_id" => $work_schedule_id,
														"total_hours"=>0,
														"total_hours_required"=>0
												);
													
											}else{
												$tq_update_field = array(
													"time_in"				=>$new_time_in,
													"lunch_out"				=>$new_lunch_out,
													"lunch_in"				=>$new_lunch_in,
													"time_out"				=>$new_time_out,
													"undertime_min"			=>$update_undertime,
													"date"					=>$currentdate,
													"tardiness_min" 		=> $update_tardiness,
													"late_min" 				=> $late_min,
													"absent_min" 			=> "0",
													"overbreak_min" 		=> $overbreak_min,
													"work_schedule_id" 		=> $work_schedule_id,
													"total_hours"			=>$get_hoursworked - ($duration_of_lunch_break_per_day/60),
													"total_hours_required"	=>$update_total_hours_required,
													"change_log_date_filed" => date('Y-m-d H:i:s'),
													"flag_on_leave" 		=> $ileave
												);
											
												// ***** NEW COMPUTATION FOR ATTENDANCE SETTINGS
												$att = is_attendance_active($company_id);
												/****/
												if($att){
													if($update_total_hours_required <= $att){
														if($time_in >= $lunch_out){
															$tq_update_field['lunch_out'] 	= null;
															$tq_update_field['lunch_in'] 	= null;
														}
														elseif($time_out <= $lunch_in){
															$tq_update_field['lunch_out'] 	= null;
															$tq_update_field['lunch_in'] 	= null;
														}
														
														$half_day_h = ($hours_worked / 2) * 60;
														if($update_undertime > $late_min){
															$tq_update_field['late_min'] 		= $late_min;
															$tq_update_field['tardiness_min'] 	= $update_tardiness;
															$tq_update_field['undertime_min'] 	= 0;
															$tq_update_field['absent_min'] 		= ((($get_hoursworked - ($duration_of_lunch_break_per_day/60)) - $update_total_hours_required) * 60) - $update_tardiness;
														}
														else{
															$tq_update_field['late_min'] 		= 0;
															$tq_update_field['tardiness_min'] 	= 0;
															$tq_update_field['undertime_min'] 	= $update_undertime;
															$tq_update_field['absent_min'] 		= ((($get_hoursworked - ($duration_of_lunch_break_per_day/60)) - $update_total_hours_required) * 60) - $update_undertime;
														}
														$date_insert['total_hours_required'] 	= $update_total_hours_required;
													}
												}
											}
											
											if($time_query_row->source!=""){
												$tq_update_field['last_source'] = $source;
											}
										}
										$this->db->where($time_query_where);
										$this->db->update('employee_time_in',$tq_update_field);
									}else{
										$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
										$get_total_hours = ($get_total_hours < 0) ? 0 : $get_total_hours;
									
										$date_insert = array(
												"source"					=> 'dashboard',
												"comp_id"					=> $company_id,
												"emp_id"					=> $emp_id,
												"date"						=> $currentdate,
												"time_in"					=> $new_time_in,
												"time_out"					=> $new_time_out,
												"undertime_min"				=> 0,
												"tardiness_min" 			=> 0,
												"late_min" 					=> 0,
												"overbreak_min" 			=> 0,
												"work_schedule_id" 			=> "-2",
												"total_hours"				=> $get_total_hours,
												"total_hours_required"		=> $get_total_hours,
												"flag_regular_or_excess" 	=> "excess",
										);
										$add_logs = $this->db->insert('employee_time_in', $date_insert);
									}
									
								}else{
									if($log_error){
										$date_insert = array(
												"source"=>$source,
												"comp_id"=>$company_id,
												"emp_id"=>$emp_id,
												"date"=>$currentdate,
												"time_in"=>$new_time_in,
												"lunch_out"=>$new_lunch_out,
												"lunch_in"=>$new_lunch_in,
												"time_out"=>$new_time_out,
												"tardiness_min"=>0,
												"undertime_min"=>0,
												"late_min" => 0,
												"overbreak_min" => 0,
												"total_hours"=>0,
												"total_hours_required"=>0
										);
									}else{
										
										$date_insert = array(
											"source"				=>$source,
											"comp_id"				=>$company_id,
											"emp_id"				=>$emp_id,
											"date"					=>$currentdate,
											"time_in"				=>$new_time_in,
											"lunch_out"				=>$new_lunch_out,
											"lunch_in"				=>$new_lunch_in,
											"time_out"				=>$new_time_out,
											"undertime_min"			=>$update_undertime,
											"tardiness_min" 		=> $update_tardiness,
											"late_min" 				=> $late_min,
											"absent_min" 			=> "0",
											"overbreak_min" 		=> $overbreak_min,
											"work_schedule_id" 		=> $work_schedule_id,
											"total_hours"			=>$get_hoursworked - ($duration_of_lunch_break_per_day/60),
											"total_hours_required"	=>$update_total_hours_required,
											"flag_on_leave" 		=> $ileave
										);
										
										
										// ***** NEW COMPUTATION FOR ATTENDANCE SETTINGS
										$att = is_attendance_active($company_id);
										/****/
										if($att){
											if($update_total_hours_required <= $att){
												if($time_in >= $lunch_out){
													$date_insert['lunch_out'] 	= null;
													$date_insert['lunch_in'] 	= null;
												}
												elseif($time_out <= $lunch_in){
													$date_insert['lunch_out'] 	= null;
													$date_insert['lunch_in'] 	= null;
												}
												
												$half_day_h = ($hours_worked / 2) * 60;
												if($update_undertime > $late_min){
													$date_insert['late_min'] 		= $late_min;
													$date_insert['tardiness_min'] 	= $update_tardiness;
													$date_insert['undertime_min'] 	= 0;
													$date_insert['absent_min'] 		= ((($get_hoursworked - ($duration_of_lunch_break_per_day/60)) - $update_total_hours_required) * 60) - $update_tardiness;
												}
												else{
													$date_insert['late_min'] 		= 0;
													$date_insert['tardiness_min'] 	= 0;
													$date_insert['undertime_min'] 	= $update_undertime;
													$date_insert['absent_min'] 		= ((($get_hoursworked - ($duration_of_lunch_break_per_day/60)) - $update_total_hours_required) * 60) - $update_undertime;
												}
												$date_insert['total_hours_required'] 	= $update_total_hours_required;
											}
										}
									}
									
									$add_logs = $this->db->insert('employee_time_in', $date_insert);
								}
							}
						}
					}else{
						/* PAYROLL GROUP ID */
						$check_latest_timein_allowed = $this->emp_login->check_lastest_timein_allowed($payroll_group,$company_id);
						if(!$check_latest_timein_allowed){
							
							$number_of_breaks_per_day = $this->elmf->check_break_time_flex_payroll_group($payroll_group,$company_id);
							if($number_of_breaks_per_day == 0){
								
								// update total hours and total hours required rest day
								$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
	
								// update total tardiness
								$update_tardiness = 0;
								
								// update undertime
								$update_undertime = 0;
								if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
									$hours_worked = $this->emp_login->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $emp_id);
									$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
								}
								if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
									$new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
									$update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
								}
								
								// check tardiness value
								$flag_tu = 0;
								
								$hours_worked = $this->emp_login->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $emp_id);
								$get_total_hours_worked = ($hours_worked / 2) + .5;
								if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0 && !$half_day){
									$update_tardiness = 0;
									$update_undertime = 0;
									$flag_tu = 1;
								}
								
								// required hours worked only
								$new_total_hours = $this->emp_login->get_tot_hours($emp_id,$company_id,$new_time_in,$new_time_out,$hours_worked);
								
								// if value is less then 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($new_total_hours < 0) $new_total_hours = 0;
								if($get_total_hours < 0) $get_total_hours = 0;
										
								$update_timein_logs = array(
									"source"=>$source,
									"tardiness_min"=>$update_tardiness,
									"undertime_min"=>$update_undertime,
									"total_hours"=>$new_total_hours,
									"total_hours_required"=>$get_total_hours,
									"flag_tardiness_undertime"=>$flag_tu
								);
								
								
								if($real_employee_time_in_id){
									$time_query_where = array(
											'employee_time_in_id' => $real_employee_time_in_id,
											"status"    => "Active"
									);
								}else{
									$time_query_where = array(
											"comp_id"	=> $company_id,
											"emp_id"	=> $emp_id,
											"date"		=> date("Y-m-d",strtotime($employee_timein_date)),
											"status"    => "Active"
									);
								}
										
								$this->db->where(" (time_in_status ='approved' OR time_in_status IS NULL) ",NULL,FALSE);
								$this->db->where($time_query_where);
								$time_query 	= $this->edb->get('employee_time_in');
								$time_query_row = $time_query->row();
								$time_query->free_result();
										
								if($time_query_row) {
									if($log_error){
										$tq_update_field = array(
												"source"=>$source,
												"time_in"=>$new_time_in,
												"time_out"=>$new_time_out,
												"undertime_min"=>0,
												"tardiness_min" => 0,
												"work_schedule_id" => $work_schedule_id,
												"total_hours"=>0,
												"late_min" => 0,
												"lunch_in" => null,
												"lunch_out"  => null,
												"overbreak_min" => 0,
												"total_hours_required"=>0
										);
									}else{
										$tq_update_field = array(
											"time_in"=>$new_time_in,
											"time_out"=>$new_time_out,
											"undertime_min"=>$update_undertime,
											"tardiness_min" => $update_tardiness,
											"late_min" => $late_min,
											"overbreak_min" => $overbreak_min,
											"work_schedule_id" => $work_schedule_id,
											"total_hours"=>$new_total_hours,
											"lunch_in" => null,
											"lunch_out"  => null,
											"total_hours_required"=>$get_total_hours,
											"change_log_date_filed" => date('Y-m-d H:i:s'),
											"flag_on_leave" => $ileave
										);
									}
									
									if($time_query_row->source!=""){
										$tq_update_field['last_source'] = $source;
									}
									
									#attendance settings
									$att = $this->elm->calculate_attendance($company_id,$new_time_in,$new_time_out);
									
									if($att){
										$total_hours_worked = $this->elm->total_hours_worked($new_time_out, $new_time_in);
										$total_hours_worked = $this->elm->convert_to_hours($total_hours_worked);
										$tq_update_field['lunch_in'] = null;
										$tq_update_field['lunch_out'] = null;
										$tq_update_field['total_hours_required'] = $total_hours_worked;
										$tq_update_field['absent_min'] = ($new_total_hours - $total_hours_worked) * 60;
										$tq_update_field['late_min'] = 0;
										$tq_update_field['tardiness_min'] = 0;
										$tq_update_field['undertime_min'] = 0;
									}
									$this->db->where($time_query_where);
									$this->db->update('employee_time_in',$tq_update_field);
											
								}else{	
									if($log_error){
										$date_insert = array(
												"source"=>$source,
												"comp_id"=>$company_id,
												"emp_id"=>$emp_id,
												"date"=>$currentdate,
												"time_in"=>$new_time_in,
												"time_out"=>$new_time_out,
												"tardiness_min"=>0,
												"undertime_min"=>0,
												"late_min" => 0,
												"overbreak_min" => 0,
												"total_hours"=>0,
												"total_hours_required"=>0
										);
									}else{
										$date_insert = array(
											"source"=>$source,
											"comp_id"=>$company_id,
											"emp_id"=>$emp_id,
											"date"=>$currentdate,
											"time_in"=>$new_time_in,
											"time_out"=>$new_time_out,
											"undertime_min"=>$update_undertime,
											"tardiness_min" => $update_tardiness,
											"late_min" => $late_min,
											"overbreak_min" => $overbreak_min,
											"work_schedule_id" => $work_schedule_id,
											"total_hours"=>$new_total_hours,
											"total_hours_required"=>$get_total_hours,
											"flag_on_leave" => $ileave
										);
									
										#attendance settings
										$att = $this->elm->calculate_attendance($company_id,$new_time_in,$new_time_out);
										
										if($att){
											$total_hours_worked = $this->elm->total_hours_worked($new_time_out, $new_time_in);
											$total_hours_worked = $this->elm->convert_to_hours($total_hours_worked);
											$date_insert['lunch_in'] = null;
											$date_insert['lunch_out'] = null;
											$date_insert['total_hours_required'] = $total_hours_worked;
											$date_insert['absent_min'] = ($new_total_hours - $total_hours_worked) * 60;
											$date_insert['late_min'] = 0;
											$date_insert['tardiness_min'] = 0;
											$date_insert['undertime_min'] = 0;
										}
									}
									$add_logs = $this->db->insert('employee_time_in', $date_insert);
								}
							}else{
								
								// update tardiness for timein
								$tardiness_timein = 0;
								
								// update tardiness for break time
								$update_tardiness_break_time = 0;
								$duration_of_lunch_break_per_day = $this->emp_login->duration_of_lunch_break_per_day($emp_id, $company_id);
								$tardiness_a = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
								if($duration_of_lunch_break_per_day < $tardiness_a){
									$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
								}
	
								// update total tardiness
								$update_tardiness = $tardiness_timein + $update_tardiness_break_time;
								
								// update undertime
								$update_undertime = 0;
								if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
									$hours_worked = $this->emp_login->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $emp_id);
									$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
								}
								if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
									$new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
									$update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
								}
								
								// update total hours
								$hours_worked = $this->emp_login->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $emp_id);
								$update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - ($duration_of_lunch_break_per_day / 60);
								
								// check tardiness value
								$get_total_hours_worked = ($hours_worked / 2) + .5;
								if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
									$update_tardiness = 0;
									$update_undertime = 0;
									$flag_tu = 1;
								}
								
								// update total hours required
								$update_total_hours_required = ((strtotime($new_time_out) - strtotime($new_time_in)) / 3600) - ($update_tardiness / 60) - ($duration_of_lunch_break_per_day / 60);
								
								// if value is less then 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($update_total_hours < 0) $update_total_hours = 0;
								if($update_total_hours_required < 0) $update_total_hours_required = 0;
								
								if($real_employee_time_in_id){
									$time_query_where = array(
											'employee_time_in_id' => $real_employee_time_in_id,
											"status"    => "Active"
									);
								}else{
									$time_query_where = array(
										"comp_id"	=> $company_id,
										"emp_id"	=> $emp_id,
										"date"		=> date("Y-m-d",strtotime($employee_timein_date)),
										"status"    => "Active"
									);
								}
								
								$this->db->where(" (time_in_status ='approved' OR time_in_status IS NULL) ",NULL,FALSE);
								$this->db->where($time_query_where);
								$time_query = $this->edb->get('employee_time_in');
								$time_query_row = $time_query->row();
								$time_query->free_result();
								
								if($time_query_row) {
								
									if($log_error){
										$tq_update_field = array(
												"time_in"=>$new_time_in,
												"lunch_out"=>$new_lunch_out,
												"lunch_in"=>$new_lunch_in,
												"time_out"=>$new_time_out,
												"undertime_min"=>0,
												"tardiness_min" => 0,
												"late_min" => 0,
												"overbreak_min" => 0,
												"work_schedule_id" => $work_schedule_id,
												"total_hours"=>0,
												"total_hours_required"=>0
										);
									}else{
									$tq_update_field = array(
										"time_in"=>$new_time_in,
										"lunch_out"=>$new_lunch_out,
										"lunch_in"=>$new_lunch_in,
										"time_out"=>$new_time_out,
										"undertime_min"=>$update_undertime,
										"tardiness_min" => $update_tardiness,
										"late_min" => $late_min,
										"overbreak_min" => $overbreak_min,
										"work_schedule_id" => $work_schedule_id,
										"total_hours"=>$update_total_hours,
										"total_hours_required"=>$update_total_hours_required,
										"change_log_date_filed" => date('Y-m-d H:i:s'),
										"flag_on_leave" => $ileave
									);
									
									}
									
									if($time_query_row->source!=""){
										$tq_update_field['last_source'] = $source;
									}
									
									
										#attendance settings
									$att = $this->elm->calculate_attendance($company_id,$new_time_in,$new_time_out);
									
									if($att){
										$total_hours_worked 						= $this->elm->total_hours_worked($new_time_out, $new_time_in);
										$total_hours_worked 						= $this->elm->convert_to_hours($total_hours_worked);
										$tq_update_field['lunch_in'] 				= null;
										$tq_update_field['lunch_out'] 				= null;
										$tq_update_field['total_hours_required'] 	= $total_hours_worked;
										$tq_update_field['absent_min'] 				= ($update_total_hours - $total_hours_worked) * 60;
										$tq_update_field['late_min'] 				= 0;
										$tq_update_field['tardiness_min'] 			= 0;
										$tq_update_field['undertime_min'] 			= 0;
									}
									
									$this->db->where($time_query_where);
									$this->db->update('employee_time_in',$tq_update_field);
									
								}
								else{
									if($log_error){
										$date_insert = array(
													"source"				=> $source,
													"comp_id"				=> $company_id,
													"emp_id"				=> $emp_id,
													"date"					=> $currentdate,
													"time_in"				=> $new_time_in,
													"lunch_out"				=> $new_lunch_out,
													"lunch_in"				=> $new_lunch_in,
													"time_out"				=> $new_time_out,
													"tardiness_min"			=> 0,
													"undertime_min"			=> 0,
													"late_min" 				=> 0,
													"overbreak_min" 		=> 0,
													"total_hours"			=> 0,
													"total_hours_required"	=> 0
													);
									}else{
										$date_insert = array(
											"source"=>$source,
											"comp_id"=>$company_id,
											"emp_id"=>$emp_id,
											"date"=>$currentdate,
											"time_in"=>$new_time_in,
											"lunch_out"=>$new_lunch_out,
											"lunch_in"=>$new_lunch_in,
											"time_out"=>$new_time_out,
											"undertime_min"=>$update_undertime,
											"tardiness_min" => $update_tardiness,
											"late_min" => $late_min,
											"overbreak_min" => $overbreak_min,
											"work_schedule_id" => $work_schedule_id,
											"total_hours"=>$update_total_hours,
											"total_hours_required"=>$update_total_hours_required,
											"flag_on_leave" => $ileave
										
										);
										
										#attendance settings
										$att = $this->elm->calculate_attendance($company_id,$new_time_in,$new_time_out);
										
										if($att){
											$total_hours_worked = $this->elm->total_hours_worked($new_time_out, $new_time_in);
											$total_hours_worked = $this->elm->convert_to_hours($total_hours_worked);
											$date_insert['lunch_in'] = null;
											$date_insert['lunch_out'] = null;
											$date_insert['total_hours_required'] = $total_hours_worked;
											$date_insert['absent_min'] = ($update_total_hours - $total_hours_worked) * 60;
											$date_insert['late_min'] = 0;
											$date_insert['tardiness_min'] = 0;
											$date_insert['undertime_min'] = 0;
										}
									}
									$add_logs = $this->db->insert('employee_time_in', $date_insert);
								}
							}
							
						}else{
							$number_of_breaks_per_day = $this->elmf->check_break_time_flex_payroll_group($payroll_group,$company_id);
							if($number_of_breaks_per_day == 0){
								
								// update total hours and total hours required rest day
								$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
								
								// update tardiness for timein
								$tardiness_timein = 0;
								if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
									if(date("A",strtotime($new_time_in)) == "AM"){
										// add one day for time in log
										$new_start_timein = date("Y-m-d",strtotime($new_time_in." -1 day"));
										$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
										
										$tardiness_set = $this->elm->tardiness_settings($emp_id, $company_id);
										
										if($tardiness_set){
											$new_start_timein = date('Y-m-d H:i:s',strtotime($new_start_timein." +{$tardiness_set} minutes"));
										}
										
										if(strtotime($new_start_timein) < strtotime($new_time_in)){
											$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;			
										}
									}
								}else{
									$new_start_timein = date("Y-m-d",strtotime($new_time_in));
									$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
									

									$tardiness_set = $this->elm->tardiness_settings($emp_id, $company_id);
									
									if($tardiness_set){
										$new_start_timein = date('Y-m-d H:i:s',strtotime($new_start_timein." +{$tardiness_set} minutes"));
									}
									
									if(strtotime($new_start_timein) < strtotime($new_time_in)){
										$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;			
									}
								}
	
								// update total tardiness
								$update_tardiness = $tardiness_timein;
								
								// update undertime
								$update_undertime = 0;
								if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
									$hours_worked = $this->emp_login->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $emp_id);
									$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
								}
								if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
									$new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
									$update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
								}
								
								// check tardiness value
								$flag_tu = 0;
								
								$hours_worked = $this->emp_login->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $emp_id);
								$get_total_hours_worked = ($hours_worked / 2) + .5;
								if($get_total_hours <= $get_total_hours_worked && $get_total_hours !=  0 && !$half_day){
									$update_tardiness = 0;
									$update_undertime = 0;
									$flag_tu = 1;
								}
								
								// required hours worked only
								$new_total_hours = $this->emp_login->get_tot_hours($emp_id,$company_id,$new_time_in,$new_time_out,$hours_worked);
								
								// if value is less than 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($new_total_hours < 0) $new_total_hours 	= 0;
								if($get_total_hours < 0) $get_total_hours 	= 0;
									
								if($real_employee_time_in_id){
									$time_query_where = array(
											'employee_time_in_id' 	=> $real_employee_time_in_id,
											"status"    			=> "Active"
											);
								}else{
									$time_query_where = array(
										"comp_id"	=> $company_id,
										"emp_id"	=> $emp_id,
										"date"		=> date("Y-m-d",strtotime($employee_timein_date)),
										"status"    => "Active"
									);
								}
								
								$this->db->where(" (time_in_status ='approved' OR time_in_status IS NULL) ",NULL,FALSE);
								$this->db->where($time_query_where);
								$time_query = $this->edb->get('employee_time_in');
								$time_query_row = $time_query->row();
								$time_query->free_result();
								
								if($time_query_row) {
									
									if($log_error){
										$tq_update_field = array(
												"time_in"=>$new_time_in,
												"time_out"=>$new_time_out,
												"undertime_min"=>0,
												"tardiness_min" => 0,
												"late_min" => 0,
												"overbreak_min" => 0,
												"work_schedule_id" => $work_schedule_id,
												"total_hours"=>0,
												"lunch_in" => null,
												"lunch_out"  => null,
												"total_hours_required"=>0
										);
										
									}else{
											$tq_update_field = array(
												"time_in"=>$new_time_in,
												"time_out"=>$new_time_out,
												"undertime_min"=>$update_undertime,
												"tardiness_min" => $update_tardiness,
												"late_min" => $late_min,
												"overbreak_min" => $overbreak_min,														
												"work_schedule_id" => $work_schedule_id,
												"total_hours"=>$new_total_hours,
												"lunch_in" => null,
												"lunch_out"  => null,
												"total_hours_required"=>$get_total_hours,
												"change_log_date_filed" => date('Y-m-d H:i:s'),
												"flag_on_leave" => $ileave
											);
									}
									
									if($time_query_row->source!=""){
										$tq_update_field['last_source'] = $source;
									}
										
									#attendance settings
									$att = $this->elm->calculate_attendance($company_id,$new_time_in,$new_time_out);
									
									if($att){
										$total_hours_worked = $this->elm->total_hours_worked($new_time_out, $new_time_in);
										$total_hours_worked = $this->elm->convert_to_hours($total_hours_worked);
										$tq_update_field['lunch_in'] = null;
										$tq_update_field['lunch_out'] = null;
										$tq_update_field['total_hours_required'] = $total_hours_worked;
										$tq_update_field['absent_min'] = ($new_total_hours - $total_hours_worked) * 60;
										$tq_update_field['late_min'] = 0;
										$tq_update_field['tardiness_min'] = 0;
										$tq_update_field['undertime_min'] = 0;
									}
									
									$this->db->where($time_query_where);
									$this->db->update('employee_time_in',$tq_update_field);
									
								}else{
									
									if($log_error){
										$date_insert = array(
												"source"=>$source,
												"comp_id"=>$company_id,
												"emp_id"=>$emp_id,
												"date"=>$currentdate,
												"time_in"=>$new_time_in,
												"time_out"=>$new_time_out,
												"tardiness_min"=>0,
												"undertime_min"=>0,
												"late_min" => 0,
												"overbreak_min" => 0,
												"total_hours"=>0,
												"total_hours_required"=>0
										);
									}else{
										$date_insert = array(
											"source"=>$source,
											"comp_id"=>$company_id,
											"emp_id"=>$emp_id,
											"date"=>$currentdate,
											"time_in"=>$new_time_in,
											"time_out"=>$new_time_out,
											"undertime_min"=>$update_undertime,
											"tardiness_min" => $update_tardiness,
											"late_min" => $late_min,
											"overbreak_min" => $overbreak_min,
											"work_schedule_id" => $work_schedule_id,
											"total_hours"=>$new_total_hours,
											"total_hours_required"=>$get_total_hours,
											"flag_on_leave" => $ileave
										);
										
										#attendance settings
										$att = $this->elm->calculate_attendance($company_id,$new_time_in,$new_time_out);
										
										if($att){
											$total_hours_worked = $this->elm->total_hours_worked($new_time_out, $new_time_in);
											$total_hours_worked = $this->elm->convert_to_hours($total_hours_worked);
											$date_insert['lunch_in'] = null;
											$date_insert['lunch_out'] = null;
											$date_insert['total_hours_required'] = $total_hours_worked;
											$date_insert['absent_min'] = ($new_total_hours - $total_hours_worked) * 60;
											$date_insert['late_min'] = 0;
											$date_insert['tardiness_min'] = 0;
											$date_insert['undertime_min'] = 0;
										}
									}
									
									$add_logs = $this->db->insert('employee_time_in', $date_insert);
								}
								
							}else{
								
								// update tardiness for timein
								$tardiness_timein = 0;
								if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
									if(date("A",strtotime($new_time_in)) == "AM"){
										// add one day for time in log
										$new_start_timein = date("Y-m-d",strtotime($new_time_in." -1 day"));
										$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
										

										$tardiness_set = $this->elm->tardiness_settings($emp_id, $company_id);
											
										if($tardiness_set){
											$new_start_timein = date('Y-m-d H:i:s',strtotime($new_start_timein." +{$tardiness_set} minutes"));
										}
										
										if(strtotime($new_start_timein) < strtotime($new_time_in)){
											$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;			
										}
									}
								}else{
									$new_start_timein = date("Y-m-d",strtotime($new_time_in));
									$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
									

									$tardiness_set = $this->elm->tardiness_settings($emp_id, $company_id);
										
									if($tardiness_set){
										$new_start_timein = date('Y-m-d H:i:s',strtotime($new_start_timein." +{$tardiness_set} minutes"));
									}
									
									if(strtotime($new_start_timein) < strtotime($new_time_in)){
										$tardiness_timein = (strtotime($new_time_in) - strtotime($new_start_timein)) / 60;			
									}
								}
								
								// update tardiness for break time
								$update_tardiness_break_time = 0;
								$duration_of_lunch_break_per_day = $this->emp_login->duration_of_lunch_break_per_day($emp_id, $company_id);
								$tardiness_a = (strtotime($new_lunch_in) - strtotime($new_lunch_out)) / 60;
								if($duration_of_lunch_break_per_day < $tardiness_a){
									$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
								}
	
								// update total tardiness
								$update_tardiness = $tardiness_timein + $update_tardiness_break_time;
								
								// update undertime
								$update_undertime = 0;
								if(strtotime(date("H:i:s",strtotime($new_time_in))) < strtotime($workday_settings_start_time)){
									$hours_worked = $this->emp_login->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $emp_id);
									$workday_settings_end_time = date("H:i:s",strtotime($new_time_in." +{$hours_worked} hour"));
								}
								if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
									$new_end_time = date("Y-m-d",strtotime($new_time_out))." ".$workday_settings_end_time;
									$update_undertime = (strtotime($new_end_time) - strtotime($new_time_out)) / 60;
								}
								
								// update total hours
								$hours_worked = $this->emp_login->get_hours_worked(date("Y-m-d",strtotime($new_time_in)), $emp_id);
								$update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - ($duration_of_lunch_break_per_day / 60);
								
								// check tardiness value
								$get_total_hours_worked = ($hours_worked / 2) + .5;
								if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0 && $half_day == 0){
									$update_tardiness = 0;
									$update_undertime = 0;
									$flag_tu = 1;
								}
								
								// update total hours required
								$update_total_hours_required = ((strtotime($new_time_out) - strtotime($new_time_in)) / 3600) - ($update_tardiness / 60) - ($duration_of_lunch_break_per_day / 60);
								
								// if value is less than 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($update_total_hours < 0) $update_total_hours = 0;
								if($update_total_hours_required < 0) $update_total_hours_required = 0;
	
								if($real_employee_time_in_id){
									$time_query_where = array(
											'employee_time_in_id' 	=> $real_employee_time_in_id,
											"status"    			=> "Active"
									);
								}else{
									$time_query_where = array(
												"comp_id"	=> $company_id,
												"emp_id"	=> $emp_id,
												"date"		=> date("Y-m-d",strtotime($employee_timein_date)),
												"status"    => "Active"
									);
								}
								
								$this->db->where(" (time_in_status ='approved' OR time_in_status IS NULL) ",NULL,FALSE);
								$this->db->where($time_query_where);
								$time_query = $this->edb->get('employee_time_in');
								$time_query_row = $time_query->row();
								$time_query->free_result();
								
								if($time_query_row) {
									
									if($log_error){
										$tq_update_field = array(
												"time_in"=>$new_time_in,
												"lunch_out"=>$new_lunch_out,
												"lunch_in"=>$new_lunch_in,
												"time_out"=>$new_time_out,
												"undertime_min"=>0,
												"tardiness_min" =>0,
												"late_min" =>0,
												"overbreak_min" => 0,
												"work_schedule_id" => $work_schedule_id,
												"total_hours"=>0,
												"total_hours_required"=>0
													
										);
									}
									else{
										$tq_update_field = array(
											"time_in"=>$new_time_in,
											"lunch_out"=>$new_lunch_out,
											"lunch_in"=>$new_lunch_in,
											"time_out"=>$new_time_out,												
											"undertime_min"=>$update_undertime,
											"tardiness_min" => $update_tardiness,
											"late_min" => $late_min,
											"overbreak_min" => $overbreak_min,
											"work_schedule_id" => $work_schedule_id,
											"total_hours"=>$update_total_hours,
											"total_hours_required"=>$update_total_hours_required,
											"change_log_date_filed" => date('Y-m-d H:i:s'),
											"flag_on_leave" => $ileave
											);
									}
									if($time_query_row->source!=""){
										$tq_update_field['last_source'] = $source;
									}
									
									#attendance settings
									$att = $this->elm->calculate_attendance($company_id,$new_time_in,$new_time_out);
									
									if($att){
										$total_hours_worked = $this->elm->total_hours_worked($new_time_out, $new_time_in);
										$total_hours_worked = $this->elm->convert_to_hours($total_hours_worked);
										$tq_update_field['lunch_in'] = null;
										$tq_update_field['lunch_out'] = null;
										$tq_update_field['total_hours_required'] = $total_hours_worked;
										$tq_update_field['absent_min'] = ($update_total_hours- $total_hours_worked) * 60;
										$tq_update_field['late_min'] = 0;
										$tq_update_field['tardiness_min'] = 0;
										$tq_update_field['undertime_min'] = 0;
									}
									
									$this->db->where($time_query_where);
									$this->db->update('employee_time_in',$tq_update_field);
									
								}
								else{
									if($log_error){
										$date_insert = array(
												"source"=>$source,
												"comp_id"=>$company_id,
												"emp_id"=>$emp_id,
												"date"=>$currentdate,
												"time_in"=>$new_time_in,
												"lunch_out"=>$new_lunch_out,
												"lunch_in"=>$new_lunch_in,
												"time_out"=>$new_time_out,
												"tardiness_min"=>0,
												"undertime_min"=>0,
												"late_min" => 0,
												"overbreak_min" => 0,
												"total_hours"=>0,
												"flag_time_in" => 0,
												"total_hours_required"=>0
										);
									}else{
										$date_insert = array(
											"source"=>$source,
											"comp_id"=>$company_id,
											"emp_id"=>$emp_id,
											"date"=>$currentdate,
											"time_in"=>$new_time_in,
											"lunch_out"=>$new_lunch_out,
											"lunch_in"=>$new_lunch_in,
											"time_out"=>$new_time_out,													
											"undertime_min"=>$update_undertime,
											"tardiness_min" => $update_tardiness,
											"late_min" => $late_min,
											"overbreak_min" => $overbreak_min,
											"work_schedule_id" => $work_schedule_id,
											"total_hours"=>$update_total_hours,
											"flag_time_in" => 0,
											"total_hours_required"=>$update_total_hours_required,
											"flag_on_leave" => $ileave
										);
										
										#attendance settings
										$att = $this->elm->calculate_attendance($company_id,$new_time_in,$new_time_out);
										
										if($att){
											$total_hours_worked = $this->elm->total_hours_worked($new_time_out, $new_time_in);
											$total_hours_worked = $this->elm->convert_to_hours($total_hours_worked);
											$date_insert['lunch_in'] = null;
											$date_insert['lunch_out'] = null;
											$date_insert['total_hours_required'] = $total_hours_worked;
											$date_insert['absent_min'] = ($update_total_hours - $total_hours_worked) * 60;
											$date_insert['late_min'] = 0;
											$date_insert['tardiness_min'] = 0;
											$date_insert['undertime_min'] = 0;
										}
									}
									
									$add_logs = $this->db->insert('employee_time_in', $date_insert);
								}
							}
						}
					}
				}
				#end flex schedule
			}else{
				/* Regular Schedule */								
				if($check_rest_day){
					
					$tardiness = 0; 
					$undertime = 0;
					
					// update total hours and total hours required rest day
					$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
	
					if($real_employee_time_in_id){
						$time_query_where = array(
								'employee_time_in_id' 	=> $real_employee_time_in_id,
								"status"    			=> "Active"
						);
					}else{
						$time_query_where = array(
							"comp_id"	=> $company_id,
							"emp_id"	=> $emp_id,
							"date"		=> date("Y-m-d",strtotime($employee_timein_date)),
							"status"    => "Active"
						);
					}
					$this->db->where($time_query_where);
					$time_query = $this->edb->get('employee_time_in');
					$time_query_row = $time_query->row();
					$time_query->free_result();
					
					if($time_query_row ) {
						if($log_error){
							$tq_update_field = array(
									"time_in"				=> $new_time_in,
									"time_out"				=> $new_time_out,
									"tardiness_min"			=> $tardiness,
									"undertime_min"			=> $undertime,
									"total_hours"			=> 0,
									"work_schedule_id" 		=> -1,
									"total_hours_required"	=> 0
							);
						}else{
							$tq_update_field = array(
								"time_in"				=>$new_time_in,
								"time_out"				=>$new_time_out,
								"tardiness_min"			=>$tardiness,
								"undertime_min"			=>$undertime,
								"total_hours"			=>$get_total_hours,
								"work_schedule_id" 		=> -1,
								"total_hours_required"	=>$get_total_hours,
								"change_log_date_filed" => date('Y-m-d H:i:s')
							);
						}
						
						if($time_query_row->source!=""){
							$tq_update_field['last_source'] = $source;
						}
						
						$this->db->where($time_query_where);
						$this->db->update('employee_time_in',$tq_update_field);
				
					}else{
					
						if($log_error){
							$date_insert = array(
									"source"=>$source,
									"comp_id"=>$company_id,
									"emp_id"=>$emp_id,
									"date"=>$currentdate,
									"time_in"=>$new_time_in,
									"time_out"=>$new_time_out,
									"tardiness_min"=>0,
									"work_schedule_id" => -1,
									"undertime_min"=>0,
									"total_hours"=>0,
									"total_hours_required"=>0
							);
						}else{
							$date_insert = array(
								"source"=>$source,
								"comp_id"=>$company_id,
								"emp_id"=>$emp_id,
								"date"=>$currentdate,
								"time_in"=>$new_time_in,
								"time_out"=>$new_time_out,
								"tardiness_min"=>$tardiness,
								"work_schedule_id" => -1,
								"undertime_min"=>$undertime,
								"total_hours"=>$get_total_hours,
								"total_hours_required"=>$get_total_hours
							);
						}
						$add_logs = $this->db->insert('employee_time_in', $date_insert);
					}
					
				}else if($holiday){
					$h 				= $this->elm->total_hours_worked($new_time_out,$new_time_in);
					$holday_hour	= $this->elm->convert_to_hours($h);
					
					if($real_employee_time_in_id){
						$time_query_where 	= array(
											'employee_time_in_id' 	=> $real_employee_time_in_id,
											"status"    			=> "Active"
											);
					}else{
						$time_query_where = array(
							"comp_id"	=> $company_id,
							"emp_id"	=> $emp_id,
							"date"		=> date("Y-m-d",strtotime($employee_timein_date)),
							"status"    => "Active"
						);
					}
						
					$this->db->where($time_query_where);
					$time_query = $this->edb->get('employee_time_in');
					$time_query_row = $time_query->row();
					$time_query->free_result();
					
					if($time_query_row) {
						if($log_error){
							$tq_update_field = array(
									"time_in"				=> $new_time_in,
									"time_out"				=> $new_time_out,
									"work_schedule_id" 		=> $work_schedule_id,
									"total_hours"			=> 0,
									"total_hours_required"	=> 0,
									"late_min" 				=> 0,
									"tardiness_min" 		=> 0,
									"undertime_min" 		=> 0
									);
						}else{
							$tq_update_field = array(
									"time_in"=>$new_time_in,
									"time_out"=>$new_time_out,
									"work_schedule_id" => $work_schedule_id,
									"total_hours"=>$holday_hour,
									"total_hours_required"=>$holday_hour,
									"change_log_date_filed" => date('Y-m-d H:i:s'),
									"late_min" => 0,
									"tardiness_min" => 0,
									"undertime_min" => 0
							);
						}
						
						if($time_query_row->source!=""){
							$tq_update_field['last_source'] = $source;
						}
						
						$this->db->where($time_query_where);
						$this->db->update('employee_time_in',$tq_update_field);
					}else{
						if($log_error){
							$date_insert = array(
									"source"=>$source,
									"comp_id"=>$company_id,
									"emp_id"=>$emp_id,
									"date"=>date("Y-m-d",strtotime($new_time_in)),
									"time_in"=>$new_time_in,
									"time_out"=>$new_time_out,
									"tardiness_min"=>0,
									"undertime_min"=>0,
									"total_hours"=>0,
									"total_hours_required"=>0,
									"late_min" => 0
							);
						}else{
							$date_insert = array(
									"comp_id"=>$company_id,
									"emp_id"=>$emp_id,
									"date"=>$currentdate ,
									"source"=>$source,
									"time_in"=>$new_time_in,
									"time_out"=>$new_time_out,
									"work_schedule_id"=>$work_schedule_id,
									"total_hours"=>$holday_hour ,
									"total_hours_required"=>$holday_hour,
									"late_min" => 0,
									"tardiness_min" => 0,
									"undertime_min" => 0
							);
							
							$add_logs = $this->db->insert('employee_time_in', $date_insert);
						}
					}
					
				}else if($new_lunch_out == NULL && $new_lunch_in == NULL && $flag_halfday == 1 && !$split){ // dont use this
					
					$flag_halfday 			= 0;
					$flag_undertime 		= 0;
					$tardiness 				= 0;
					$undertime 				= 0;
					$total_hours 			= 0;
					$total_hours_required 	= 0;
					$payroll_group 			= $this->employee->get_payroll_group($emp_id);
					$day = date('l',strtotime($time_in_excel));
					// CHECK IF WORKING HOURS IF FLEXIBLE
				
					if($this->work_schedule_id != FALSE){
						/* EMPLOYEE WORK SCHEDULE */
						$check_hours_flexible = $this->elm->check_hours_flex($company_id,$this->work_schedule_id);
						//$check_breaktime = $this->elm->check_breaktime($company_id,$this->work_schedule_id);
						$check_breaktime = $this->check_breaktime2($company_id,$this->work_schedule_id);
					}else{
						$check_hours_flexible = $this->employee->check_hours_flex($company_id,$payroll_group);
						$check_breaktime = $this->check_breaktime2($company_id,$payroll_group);	
					}
					
					$workday = date("l",strtotime($new_time_in));
					
					// check workday settings
					if($this->work_schedule_id != FALSE){
						/* EMPLOYEE WORK SCHEDULE */
						$workday_settings_start_time = $this->elm->check_workday_settings_start_time($workday,$this->work_schedule_id,$company_id);
						$workday_settings_end_time = $this->elm->check_workday_settings_end_time($workday,$this->work_schedule_id,$company_id);
					}else{
						$workday_settings_start_time = $this->employee->check_workday_settings_start_time($workday,$payroll_group,$company_id);
						$workday_settings_end_time = $this->employee->check_workday_settings_end_time($workday,$payroll_group,$company_id);	
					}
		
					if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
						
						// for night shift time in and time out value for working day
						$check_bet_timein = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
						$check_bet_timeout = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$workday_settings_end_time;
					}else{
						
						// for day shift time in and time out value for working day
						$check_bet_timein = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
						$check_bet_timeout = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_end_time;
					}
					
					// check between date time in to date time out
					$add_oneday_timein = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$workday_settings_start_time;
					
					if($this->work_schedule_id != FALSE){
						/* EMPLOYEE WORK SCHEDULE */
						$wd_start = $this->elm->get_workday_sched_start($company_id,$this->work_schedule_id);
						$wd_end = $this->elm->get_end_time($company_id,$this->work_schedule_id);
					}else{
						$wd_start = $this->employee->get_workday_sched_start($company_id,$payroll_group);
						$wd_end = $this->employee->get_end_time($company_id,$payroll_group);	
					}
					
					if($check_breaktime != FALSE){
						$b_st = $check_breaktime->start_time;
						$b_et = $check_breaktime->end_time;
						$now_date = date("Y-m-d H:i:s",strtotime($new_time_out));
						$now_time = date("H:i:s",strtotime($new_time_out));
						
						// FLAG
						if((strtotime($check_bet_timein) <= strtotime($new_time_in) && strtotime($new_time_in) <= strtotime($add_oneday_timein)) && (date("A",strtotime($wd_start)) != "PM" && date("A",strtotime($wd_end)) != "AM")){ 
							// FOR DAY SHIFT
							if(date("A",strtotime($b_et)) == date("A",strtotime($new_time_in)) || strtotime($b_et) <= strtotime(date("H:i:s",strtotime($new_time_in)))){
								$flag_halfday = 1; // FOR HALFDAY AFTERNOON
							}elseif(strtotime(date("H:i:s",strtotime($new_time_out))) <= strtotime($b_st)){
								$flag_halfday = 2; // FOR HALFDAY MORNING
							}
							//print "{$new_time_out} - {$b_st}";
						}else{
							// FOR NIGHT SHIFT
							$new_date_timein = date("Y-m-d H:i:s",strtotime($check_bet_timein." -1 day"));
							$new_date_timeout = date("Y-m-d",strtotime($new_time_in))." ".date("H:i:s",strtotime($add_oneday_timein));
							//if(strtotime($new_date_timein) <= strtotime($new_time_in) && strtotime($new_time_in) <= strtotime($new_date_timeout)) $flag_halfday = 1;
							
							if(date("A",strtotime($new_time_in)) == "AM"){
								if(strtotime(date("Y-m-d",strtotime($new_time_in))." ".$b_et) <= strtotime($new_date_timeout) && strtotime(date("Y-m-d",strtotime($new_time_in))) <= strtotime($new_date_timeout)){
									$flag_halfday = 1;	
								}
							}else{
								if(strtotime(date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$b_et) >= strtotime($new_time_in) && strtotime(date("Y-m-d",strtotime($new_time_in))) <= strtotime($new_date_timeout)){
									$flag_halfday = 2;
								}
							}
						}
					}else{
						// show_error("Payroll set up for break time is empty.");
						/* FOR UNIFORM WORKING DAYS AND WORKSHIFT ZERO BREAK TIME */
						$flag_halfday = 3;
					}
					// HALFDAY AFTERNOON
					if($flag_halfday == 1){
						if(date("A",strtotime($wd_start)) != "PM" && date("A",strtotime($wd_end)) != "AM"){ // DAY SHIFT TRAPPING
							
							// FOR TARDINESS 										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
							if($check_hours_flexible != FALSE){
								$tardiness_a = (strtotime($b_st) - strtotime($check_hours_flexible)) / 60; // time start - breaktime end time (tardiness for start time)
							}else{
								$tardiness_a = (strtotime($b_st) - strtotime($workday_settings_start_time)) / 60; // time start - breaktime end time (tardiness for start time)
							}
							
							$tardiness_b = (strtotime($b_et) < strtotime(date("H:i:s",strtotime($new_time_in)))) ? (strtotime(date("H:i:s",strtotime($new_time_in))) - strtotime($b_et)) / 60 : 0 ; // tardiness for time in breaktime
								
							$tardiness = $tardiness_a + $tardiness_b;
							
							// GET END TIME FOR TIME OUT
							if($this->work_schedule_id != FALSE){
								$get_end_time = $this->elm->get_end_time($company_id,$this->work_schedule_id);
							}else{
								$get_end_time = $this->employee->get_end_time($company_id,$payroll_group);	
							}
							
							// FOR UNDERTIME 											>>>>>>>>>>>>>>>>>>>>>>>>>>>>
							if($get_end_time != FALSE){
								if(strtotime($now_time) < strtotime($get_end_time)) $undertime = (strtotime($get_end_time) - strtotime($now_time)) / 60;
							}else{
								show_error("Payroll set up for break time is empty.");
							}
							
						}else{ // NIGHT SHIFT TRAPPING
							$new_end_date = date("Y-m-d",strtotime(date("Y-m-d",strtotime($new_time_out))." -1 day"))." ".$b_et;
							$now_date = date("Y-m-d H:i:s",strtotime($new_time_out));
							
							$new_breaktime_start = date("Y-m-d",strtotime($new_time_out))." ".$b_st;
							
							if($check_hours_flexible != FALSE){
								$tardiness_a = (strtotime($new_breaktime_start) - strtotime(date("Y-m-d",strtotime($new_date_timein))." ".$check_hours_flexible)) / 60; // time start - breaktime end time (tardiness for start time)
							}else{
								$tardiness_a = (strtotime($new_breaktime_start) - strtotime($new_date_timein)) / 60; // time start - breaktime end time (tardiness for start time)
							}
							
							$tardiness_b = (strtotime($b_et) < strtotime(date("H:i:s",strtotime($new_time_in)))) ? (strtotime(date("H:i:s",strtotime($new_time_in))) - strtotime($b_et)) / 60 : 0 ; // tardiness for time in breaktime
							
							$tardiness = $tardiness_b;
							
							// GET END TIME FOR TIME OUT
							if($this->work_schedule_id != FALSE){
								$get_end_time = $this->elm->get_end_time($company_id,$this->work_schedule_id);
							}else{
								$get_end_time = $this->employee->get_end_time($company_id,$payroll_group);	
							}
							
							// FOR UNDERTIME 											>>>>>>>>>>>>>>>>>>>>>>>>>>>>
							if($get_end_time != FALSE){
								if(strtotime($now_time) < strtotime($get_end_time)) $undertime = (strtotime($get_end_time) - strtotime($now_time)) / 60;
							}else{
								show_error("Payroll set up for break time is empty.");
							}
						}
						
						// FOR TOTAL HOURS										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						if($undertime == 0){
							$total_hours = 	(strtotime($get_end_time) - strtotime(date("H:i:s",strtotime($new_time_in)))) / 3600;
						}else{
							$total_hours = 	(strtotime($now_time) - strtotime(date("H:i:s",strtotime($new_time_in)))) / 3600;
						}
						
						// FOR TOTAL HOURS REQUIRED								>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						$total_hours_required = (strtotime($now_time) - strtotime(date("H:i:s",strtotime($new_time_in)))) / 3600;
						
					}
					elseif($flag_halfday == 2){
						$undertime_a = 0;
						$undertime_b = 0;
						
						if(date("A",strtotime($wd_start)) != "PM" && date("A",strtotime($wd_end)) != "AM"){ // DAY SHIFT TRAPPING
							
							// FOR UNDERTIME 										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
							$undertime_a = (strtotime($workday_settings_end_time) - strtotime($b_et)) / 60; // time start - breaktime end time (tardiness for start time)
							
							// FOR UNDERTIME										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
							if(strtotime($now_time) < strtotime($b_st)) $undertime_b = (strtotime($b_st) - strtotime($now_time)) / 60;
	
							$undertime = $undertime_a + $undertime_b;
							
							if($this->work_schedule_id != FALSE){
								// GET END TIME FOR TIME OUT
								$get_end_time = $this->elm->get_end_time($company_id,$this->work_schedule_id);
								
								// GET LATEST TIME IN ALLOWED VAL
								$get_latest_timein_allowed_val = $this->employee->get_latest_timein_allowed_val($company_id,$this->work_schedule_id,"work_schedule_id");
							}else{
								// GET END TIME FOR TIME OUT
								$get_end_time = $this->employee->get_end_time($company_id,$payroll_group);
	
								// GET LATEST TIME IN ALLOWED VAL
								$get_latest_timein_allowed_val = $this->employee->get_latest_timein_allowed_val($company_id,$payroll_group,"payroll_group_id");
							}
							
							$st_for_tardiness = (!$get_latest_timein_allowed_val) ? $b_st : $get_latest_timein_allowed_val ;
							
							// FOR TARDINESS 											>>>>>>>>>>>>>>>>>>>>>>>>>>>>
							if($get_end_time != FALSE){
								if(strtotime($st_for_tardiness) < strtotime(date("H:i:s",strtotime($new_time_in)))) $tardiness = (strtotime($b_et) - strtotime(date("H:i:s",strtotime($new_time_out)))) / 60;
							}else{
								show_error("Payroll set up for break time is empty.");
							}
							
							// calculate total hours
							$th = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
							$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$emp_id);
							
							// new undertime calculation
							$undertime = (($hw - $th) * 60) - $tardiness;
							
						}else{ // NIGHT SHIFT TRAPPING
							
							// FOR TARDINESS										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
							if($check_hours_flexible != FALSE){
								$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$check_hours_flexible;
								$tardiness_b = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
							}else{
								$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
								$tardiness_b = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
							}
							
							$tardiness = $tardiness_b;
							
							// GET END TIME FOR TIME OUT
							if($this->work_schedule_id != FALSE){
								$get_end_time = $this->elm->get_end_time($company_id,$this->work_schedule_id);
							}else{
								$get_end_time = $this->employee->get_end_time($company_id,$payroll_group);	
							}
							
							/* version 1.0 */
							
							// FOR UNDERTIME 											>>>>>>>>>>>>>>>>>>>>>>>>>>>>
							if($get_end_time != FALSE){
								$u_add_oneday_timein = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$b_st;
								if(strtotime($new_time_out) < strtotime($u_add_oneday_timein)) $undertime = (strtotime($u_add_oneday_timein) - strtotime($new_time_out)) / 60;
							}else{
								//show_error("Payroll set up for break time is empty.");
							}
							
							/* version 1.1 */
							
							// calculate total hours
							$th = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
							$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$emp_id);
							
							// new undertime calculation
							$undertime = (($hw - $th) * 60) - $tardiness;
						}
						
						// FOR TOTAL HOURS										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						if($undertime == 0){
							$u_add_oneday_timein = date("Y-m-d",strtotime($new_time_in." +1 day"))." ".$b_st;
							$total_hours = 	(strtotime($u_add_oneday_timein) - strtotime($new_time_in)) / 3600;
						}else{
							$total_hours = 	(strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
						}
						
						// FOR TOTAL HOURS REQUIRED								>>>>>>>>>>>>>>>>>>>>>>>>>>>>
						$total_hours_required = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
						
					}else if($flag_halfday == 3){
						
						$get_different = ($this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$emp_id)) / 2;
						$get_between = date("Y-m-d H:i:s",strtotime(date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time." + {$get_different} hour"));
						
						/* DAY SHIFT TRAPPING */
	
						if(strtotime($get_between) < strtotime($new_time_in)){
							
							/* for afternoon */
							
							// get undertime
							if(strtotime($new_time_out) < strtotime($workday_settings_end_time)){
								$undertime = (strtotime($workday_settings_end_time) - strtotime($new_time_out)) / 60;
							}
							
							// calculate total hours
							$newtimein = (strtotime($new_time_in) < strtotime($get_between)) ? $get_between : $new_time_in ;
							$th = (strtotime($new_time_out) - strtotime($newtimein)) / 3600;
							$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$emp_id);
							
							// new tardiness calculation
							$tardiness = (($hw - $th) * 60) - $undertime;
							
							// total hours
							$total_hours = 	(strtotime($new_time_out) - strtotime($newtimein)) / 3600;
							$total_hours_required = (strtotime($new_time_out) - strtotime($newtimein)) / 3600;
							
						}else{
	
							/* for morning */
							
							// get tardiness
							if(strtotime($workday_settings_start_time) < strtotime($new_time_in)){
								$tardiness = (strtotime($new_time_in) - strtotime($workday_settings_start_time)) / 60;
							}
							
							if($check_hours_flexible != FALSE){
								$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$check_hours_flexible;
								$tardiness = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
							}else{
								$f2_new_breaktime_start = date("Y-m-d",strtotime($new_time_in))." ".$workday_settings_start_time;
								$tardiness = (strtotime($f2_new_breaktime_start) < strtotime($new_time_in)) ? (strtotime($new_time_in) - strtotime($f2_new_breaktime_start)) / 60 : 0 ; // tardiness for time in breaktime
							}
							
							// calculate total hours
							$newtimeout = (strtotime($get_between) < strtotime($new_time_out)) ? $get_between : $new_time_out ;
							$th = (strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
							$hw = $this->employee->new_hoursworked(date("Y-m-d",strtotime($new_time_in)),$emp_id);
							
							// new tardiness calculation
							$undertime = (($hw - $th) * 60) - $tardiness;
							
							// total hours
							$total_hours = 	(strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
							$total_hours_required = (strtotime($newtimeout) - strtotime($new_time_in)) / 3600;
							
						}
					}
					
					// update date halfday for callcenter
					$new_date = date("Y-m-d",strtotime($new_time_in." -1 day"));
					if($this->work_schedule_id != FALSE){
						$check_workday = $this->elm->halfday_check_workday($this->work_schedule_id,$company_id,$new_date);
					}else{
						$check_workday = $this->employee->halfday_check_workday($payroll_group,$company_id,$new_date);	
					}
					
					$payroll_period = $this->employee->get_payroll_period($company_id);
					if($check_workday){
						// minus 1 day
						$period_to = $payroll_period->period_to;
						$date_halfday = (strtotime($period_to) == strtotime($new_date)) ? $period_to : NULL ;
					}else{
						$date_halfday = NULL;
					}
					
				
						if($real_employee_time_in_id){
							$time_query_where = array(
									'employee_time_in_id' 	=> $real_employee_time_in_id, 
									"status" 				=> "Active",
							);
						}else{
							$time_query_where = array(
									"comp_id"	=> $company_id,
									"emp_id"	=> $emp_id,
									"status" 	=> "Active",
									"date"		=> date("Y-m-d",strtotime($employee_timein_date))
							);
						}
							
						$this->db->where($time_query_where);
						$time_query = $this->edb->get('employee_time_in');
						$time_query_row = $time_query->row();
						$time_query->free_result();
						
						if($time_query_row) {
							
							if($log_error){
								$tq_update_field = array(
										"source"=>$source,
										"time_in"=>$new_time_in,
										"time_out"=>$new_time_out,
										"tardiness_min"=>$tardiness,
										"undertime_min"=>$undertime,
										"total_hours"=>0,
										"total_hours_required"=>0,
										"flag_halfday"=>1
								);
							}
							else{
								$tq_update_field = array(
											"source"				=> $source,
											"time_in"				=> $new_time_in,
											"time_out"				=> $new_time_out,
											"tardiness_min"			=> $tardiness,
											"undertime_min"			=> $undertime,
											"total_hours"			=> $total_hours,
											"total_hours_required"	=> $total_hours_required,
											"flag_halfday"			=> 1
											);
							}
							$this->db->where($time_query_where);
							$this->db->update('employee_time_in',$tq_update_field);
							
						}else{
							
							if($log_error){
								$date_insert = array(
										"source"				=> $source,
										"comp_id"				=> $company_id,
										"emp_id"				=> $emp_id,
										"date"					=> date("Y-m-d",strtotime($new_time_in)),
										"time_in"				=> $new_time_in,
										"time_out"				=> $new_time_out,
										"tardiness_min"			=> 0,
										"undertime_min"			=> 0,
										"total_hours"			=> 0,
										"total_hours_required"	=> 0,
										"flag_halfday"			=> 1
										);
							}else{
								$date_insert = array(
										"source"				=> $source,
										"comp_id"				=> $company_id,
										"emp_id"				=> $emp_id,
										"date"					=> date("Y-m-d",strtotime($new_time_in)),
										"time_in"				=> $new_time_in,
										"time_out"				=> $new_time_out,
										"tardiness_min"			=> $tardiness,
										"undertime_min"			=> $undertime,
										"total_hours"			=> $total_hours,
										"total_hours_required"	=> $total_hours_required,
										"flag_halfday"			=> 1
										);
							}
							$add_logs = $this->db->insert('employee_time_in', $date_insert);
						}
					
				}else{
					if($get_split){
						$currentdate = $get_split['current_date'];						
					}						
					//SPLIT AREA
					$split = $this->check_if_split_schedule($emp_id,$company_id,$this->work_schedule_id, $currentdate,$time_in,$time_out);
				
					$day = date('l',strtotime($currentdate));
					$split_break_time = false;
					if(!$split){
						// check employee work schedule
						if($this->work_schedule_id != FALSE){
							// check break time
							$check_break_time = $this->check_break_time2($this->work_schedule_id,$company_id,$day);	
						}else{
							$emp_no =$this->convert_emp_id_to_emp_no($emp_id);
							
							$payroll_group_info = $this->emp_login->payroll_group_info($emp_no,$company_id);
							if($payroll_group_info){
								$check_break_time = $this->check_break_time2($payroll_group_info->work_schedule_id,$company_id,$day); //ddd
							}
						}
					}else{
						$check_break_time = true;
											
						if($new_lunch_in =="" && $new_lunch_out == ""){
							$split_break_time = true;
						}
					}
					# no break in workschedule in regular schedule
					if(!$check_break_time){ // ZERO VALUE FOR BREAK TIME
						
						// updabte total hours and total hours required rest day
						$get_total_hours = (strtotime($new_time_out) - strtotime($new_time_in)) / 3600;
						
						if($this->work_schedule_id != FALSE){
							
							/* EMPLOYEE WORK SCHEDULE */
							
							// tardiness and undertime value
							$update_tardiness = $this->elm->get_tardiness_val($emp_id,$company_id,$new_time_in,$this->work_schedule_id,$currentdate);
							
							$update_undertime = $this->elm->get_undertime_val($emp_id,$company_id,$new_time_in,$new_time_out,$this->work_schedule_id,$check_break_time,$currentdate);
							
							// hours worked
							$hours_worked = $this->elm->get_hours_worked($currentdate, $emp_id, $this->work_schedule_id);
						}else{
							
							// tardiness and undertime value
							$update_tardiness = $this->lsm->get_tardiness_val($emp_id,$company_id,$new_time_in);
							$update_undertime = $this->lsm->get_undertime_val($emp_id,$company_id,$new_time_in,$new_time_out);
							
							// hours worked
							$hours_worked = $this->lsm->get_hours_worked($currentdate, $emp_id);
						}
						
						$tMinutes = $this->elm->total_hours_worked($new_time_out, $new_time_in);
						$tHours = $this->elm->convert_to_hours($tMinutes);
						// check tardiness value
						$flag_tu = 0;
						
						
						
						// required hours worked only
						if($this->work_schedule_id != FALSE){
							/* EMPLOYEE WORK SCHEDULE */
							$new_total_hours = $this->elm->get_tot_hours($emp_id,$company_id,$new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out,$hours_worked,$this->work_schedule_id,$check_break_time);
						}else{
							$new_total_hours = $this->lsm->get_tot_hours($emp_id,$company_id,$new_time_in,$new_time_out,$hours_worked);	
						}
						
						if($real_employee_time_in_id){
							$time_query_where 	= array(
												'employee_time_in_id' 	=> $real_employee_time_in_id,
												"status"    			=> "Active"
												);
						}
						else{
							$time_query_where 	= array(
												"comp_id"	=> $company_id,
												"emp_id"	=> $emp_id,
												"status" 	=> "Active",
												"date"		=> date("Y-m-d",strtotime($currentdate)),
												"status"    => "Active"
												);
						}
						$this->db->where(" (time_in_status ='approved' OR time_in_status IS NULL) ",NULL,FALSE);
						$this->db->where($time_query_where);
						$time_query 	= $this->edb->get('employee_time_in');
						$time_query_row = $time_query->row();
						$time_query->free_result();
						$hours_worked 	= $this->new_hoursworked($this->work_schedule_id,$company_id,$currentdate);
						
						/**
						 * absent min work after admin enable attendance settings
						 */
						$absent_min = $this->elm->absent_minutes_calc($this->work_schedule_id,$company_id,$new_time_in,$new_time_out,$emp_no,$update_undertime,$update_tardiness,true,$currentdate);
						// total hours worked
						// this will use for import timesheet and add timesheet
						$half_day = 0;
						$arr = (array) $absent_min;
						if(count($arr)!= 0){
								if($absent_min->half_day!=0){
								$break =0;
								$total_hours_worked1 = $this->elm->total_hours_worked($time_out, $time_in);
								$new_total_hours = $this->elm->convert_to_hours($total_hours_worked1);
						
								if($absent_min->half_day == 1){
									$half_day = 1;
									$update_tardiness = $absent_min->tardiness;
								}
								if($absent_min->half_day == 2){
									$half_day= 2;
									$update_undertime = $absent_min->undertime;
								}
							}
						}
						#check early tardiness
						$late_min = $this->elm->late_min($company_id,$currentdate,$emp_id,$work_schedule_id,"",$new_time_in);
						if($this->work_schedule_id != FALSE){
							if($time_query_row) {
								if($log_error){
									$tq_update_field = array(
													"time_in"				=> $new_time_in,
													"time_out"				=> $new_time_out,
													"tardiness_min"			=> $update_tardiness,
													"undertime_min"			=> $update_undertime,
													"total_hours"			=> 0,
													"work_schedule_id" 		=> 0,
													"total_hours_required" 	=> 0,
													"overbreak_min" 		=> 0,
													"flag_halfday"			=> 1,
													"date" 					=> $currentdate,
													"late_min" 				=> 0
													);
								}else{
									$tq_update_field = array(
													"time_in"				=> $new_time_in,
													"time_out"				=> $new_time_out,
													"lunch_out"				=> null,
													"lunch_in"				=> null,
													"tardiness_min"			=> $update_tardiness,
													"undertime_min"			=> $update_undertime,
													"total_hours"			=> $hours_worked,
													"overbreak_min" 		=> 0,
													"work_schedule_id" 		=> $work_schedule_id,
													"total_hours_required" 	=> $tHours,
													"flag_halfday"			=>1,
													"date" 					=> $currentdate,
													"late_min" 				=> $late_min,
													"change_log_date_filed" => date('Y-m-d H:i:s'),
													"flag_on_leave" 		=> $ileave
													);
								
									if($half_day == 1){
										$tq_update_field["tardiness_min"] = 0;
										$tq_update_field["absent_min"] = $update_tardiness;
									}
									else if($half_day == 2){ #undertime
										$tq_update_field["undertime_min"] = 0;
										$tq_update_field["absent_min"] = $update_undertime;
									}
									else{
										$tq_update_field["absent_min"] = 0;
									}
								}
								
								if($time_query_row->source!=""){
									$tq_update_field['last_source'] = $source;
								}
								
									// attendance settings
									// absent min work after attendance
									$att = $this->elm->calculate_attendance($company_id,$new_time_in,$new_time_out);
									
									if($att){
										$total_hours_worked 						= $this->elm->total_hours_worked($new_time_out, $new_time_in);
										$total_hours_worked 						= $this->elm->convert_to_hours($total_hours_worked);
										$tq_update_field['lunch_in'] 				= null;
										$tq_update_field['lunch_out'] 				= null;
										$tq_update_field['total_hours_required'] 	= $total_hours_worked;
										$tq_update_field['absent_min'] 				= ($hours_worked - $total_hours_worked) * 60;
										$tq_update_field['late_min'] 				= 0;
										$tq_update_field['tardiness_min'] 			= 0;
										$tq_update_field['undertime_min'] 			= 0;
									}
								
								$this->db->where($time_query_where);
								$this->db->update('employee_time_in',$tq_update_field);
								
							}else{
							
								
								if($log_error){
									$date_insert = array(
												"source"					=> $source,
												"comp_id"					=> $company_id,
												"emp_id"					=> $emp_id,
												"date"						=> $currentdate,
												"time_in"					=> $new_time_in,
												"time_out"					=> $new_time_out,
												"tardiness_min"				=> 0,
												"undertime_min"				=> 0,
												"work_schedule_id" 			=> 0,
												"late_min" 					=> 0,
												"total_hours"				=> 0,
												"total_hours_required"		=> 0,
												"flag_tardiness_undertime"	=> 0,
												"flag_halfday"				=> 1
												);
								}else{
									
									$date_insert = array(
												"source"					=> $source,	
												"comp_id"					=> $company_id,
												"emp_id"					=> $emp_id,
												"date"						=> $currentdate,
												"time_in"					=> $new_time_in,
												"time_out"					=> $new_time_out,
												"tardiness_min"				=> $update_tardiness,
												"undertime_min"				=> $update_undertime,
												"late_min" 					=> $late_min,
												"total_hours"				=> $hours_worked,
												"total_hours_required"		=> $tHours,
												"work_schedule_id" 			=> $work_schedule_id,
												"flag_tardiness_undertime"	=> $flag_tu,
												"flag_halfday"				=> 1,
												"flag_on_leave" 			=> $ileave
												);
									
									if($half_day == 1){
										$date_insert["tardiness_min"] 	= 0;
										$date_insert["absent_min"] 		= $update_tardiness;
									}
									else if($half_day == 2){
										$date_insert["undertime_min"]  	= 0;
										$date_insert["absent_min"] 		= $update_undertime; 
									}
									else{
										$date_insert["absent_min"] 		= 0;
									}
									
									//attendance settings
									$att = $this->elm->calculate_attendance($company_id,$new_time_in,$new_time_out);
									if($att){
										$total_hours_worked = $this->elm->total_hours_worked($new_time_out, $new_time_in);
										$total_hours_worked = $this->elm->convert_to_hours($total_hours_worked);
										$date_insert['lunch_in'] = null;
										$date_insert['lunch_out'] = null;
										$date_insert['total_hours_required'] = $total_hours_worked;
										$date_insert['absent_min'] = ($hours_worked - $total_hours_worked) * 60;
										$date_insert['late_min'] = 0;
										$date_insert['tardiness_min'] = 0;
										$date_insert['undertime_min'] = 0;
									}
								}
								$add_logs = $this->db->insert('employee_time_in', $date_insert);
							}
						}
					}
					else{
						if(!$split){
							$hours_worked = $this->new_hoursworked($this->work_schedule_id,$company_id,$currentdate);
						}
						else{
							$hours_worked = $split['total_hours_work_per_block'];
						}
						
						if($this->work_schedule_id != FALSE){
							$add_logs = $this->elm->import_add_logs($company_id, $emp_id, $reason, $new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out, $hours_worked, $this->work_schedule_id,$check_break_time,$split,$log_error,$source,$emp_no,$gDate,$real_employee_time_in_id);
						}
						else{
							$add_logs = $this->import_add_logs($company_id, $emp_id, $reason, $new_time_in,$new_lunch_out,$new_lunch_in,$new_time_out, $hours_worked,$check_break_time,$source);	
						}
					}
				}
			}
		}
	}
	
	/**
	 * Get Hours Worked for workday
	 * @param unknown_type $workday
	 * @param unknown_type $emp_id
	 */
	public function new_hoursworked($work_schedule_id,$comp_id,$workday){
		
		$total_hours = 0;
		$day = date('l',strtotime($workday));
		$w_uwd = array(
				//"payroll_group_id"=>$payroll_group,
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id,
				"days_of_work" => $day
		);
		$this->db->where($w_uwd);
		$q_uwd = $this->db->get("regular_schedule");
		$r_uwd = $q_uwd->row();
		if($q_uwd->num_rows() > 0){
			$total_hours = $r_uwd->total_work_hours;
		}else{
			# WORKSHIFT SETTINGS
			$w_ws = array(
			//"payroll_group_id"=>$payroll_group,
			"work_schedule_id"=>$work_schedule_id,
			"company_id"=>$comp_id,
			);
			$this->db->where($w_ws);
			$q_ws = $this->db->get("split_schedule");
			$r_ws = $q_ws->row();
			if($q_ws->num_rows() > 0){
				$total_hours = $r_ws->total_work_hours;
			}else{
				# FLEXIBLE HOURS
				$w_fh = array(
				//"payroll_group_id"=>$payroll_group,
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id
				);
				$this->db->where($w_fh);
				$q_fh = $this->db->get("flexible_hours");
				$r_fh = $q_fh->row();
				if($q_fh->num_rows() > 0){
					$total_hours = $r_fh->total_hours_for_the_day;
				}
			}
		}
		
		
		if($total_hours!=0 && $total_hours!=NULL)
			return $total_hours;
		else 
			return false;
	}
	
	public function save_import($company_id,$csv){
		$con = $this->session->userdata('list_conflict');
		$date_mix = $this->session->userdata('date_mix');
		if($company_id) {
			$file=fopen($csv,"r") or die("Exit") ;
			$row_start = 0;
			$conflict = array();
			$toboy = array();
			$num = 1;
			
			
			while(!feof($file)):
				$read_csv = fgetcsv($file);
	
				if($row_start > 0) {
					
					//if($read_csv[0] !="") {
					
						$payroll_cloud_id = str_replace("'", "", $read_csv[0]);
						$last_name = $this->db->escape_str($read_csv[1]);
						$middle_name = $this->db->escape_str($read_csv[2]);
						$first_name = $this->db->escape_str($read_csv[3]);
						$gDate = $read_csv[4];
						$time_in = $read_csv[5];
						$time_in_time = $read_csv[6];
						$lunch_out = $read_csv[7];
						$lunch_out_time = $read_csv[8];
						$lunch_in = $read_csv[9];
						$lunch_in_time = $read_csv[10];
						$time_out = $read_csv[11];
						$time_out_time = $read_csv[12];
						//$payroll_group_name = $this->db->escape_str($read_csv[8]);
						if($payroll_cloud_id ==""  && ($last_name !="" && $first_name !="")){
							$payroll_cloud_id = $this->get_employee_id($this->company_info->company_id, $last_name, $first_name);
						
						}
					
						$check = $this->get_employee_credentials($payroll_cloud_id,$company_id,$last_name,$middle_name,$first_name);
						$good = true;
					
						#dont import if the schedule has an error
						#except for no schedule, it has a default workschedule
						if(isset($con)){
							if(is_array($con)){
								foreach($con as $rows){									
									if($rows == $num){
										$good = false;										
									}
								}
							}
						}
						
						if($date_mix){
							foreach($date_mix as $allow){
								if($allow == $num){
									$good = true;
								}
							}
						}
						
						if($check && $good){
							$time_in = date('Y-m-d H:i:s',strtotime($time_in." ".$time_in_time));
							$lunch_out = date('Y-m-d H:i:s',strtotime($lunch_out." " .$lunch_out_time));
							$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_in." " .$lunch_in_time));
							$time_out = date('Y-m-d H:i:s',strtotime($time_out." " .$time_out_time));
							
							if($time_in =="" || $time_in_time==""){
								$time_in = null;
							}
							if($lunch_out =="" || $lunch_out_time==""){
								$lunch_out = null;
							}
							if($lunch_in =="" || $lunch_in_time==""){
								$lunch_in = null;
							}
							if($time_out =="" || $time_out_time==""){
								$time_out = null;
							}
							$this->import_new($company_id,$check->emp_id,$time_in,$lunch_out,$lunch_in,$time_out,"import",$payroll_cloud_id,$gDate);
						}
					//}
					$num++;
				}
				$row_start++;
				
			endwhile;
			fclose($file);
			$con = $this->session->set_userdata('list_conflict','');
			$this->session->set_userdata('date_mix','');
			return $toboy;
		}
	}
	
	public function save_create_new($company_id,$data_entry= array(),$source=""){
		$con = $this->session->userdata('list_conflict2');
		$date_mix = $this->session->userdata('date_mix');
		if($company_id) {
			$row_start = 0;
			$conflict = array();
			$toboy = array();
			$num = 0;
			if($data_entry) {
				foreach($data_entry as $row) {
					$payroll_cloud_id = $this->db->escape_str($row[7]);
					$time_in = $row[1];
					$lunch_out = $row[2];
					$lunch_in = $row[3];
					$time_out = $row[4];
					$last_name = $row[6];
					$first_name = $row[5];
					$gDate = isset($row[8]) ? $row[8] : "" ;
					$employee_time_in_id = isset($row[9]) ? $row[9] : "";
					$middle_name = "";
					//$payroll_group_name = $this->db->escape_str($read_csv[8]);
					$check = $this->get_employee_credentials($row[7],$company_id,$last_name,$middle_name,$first_name);
					
					$good = true;
					
					if($con){
						foreach($con as $rows){
								
								if($rows == $num){
									$good = false;								
								}
						}
					}
					if($date_mix){
						foreach($date_mix as $allow){
							if($allow == $num){
								$good = true;
								
							}
						}
					}
					if($check && $good){
						if($time_in ==""){
							$time_in = null;
						}
						if($lunch_out =="" || $lunch_out =="  "){
							$lunch_out = null;
						}
						if($lunch_in =="" || $lunch_in =="  "){
							$lunch_in = null;
						}
						if($time_out ==""){
							$time_out = null;
						}
						
						$this->import_new($company_id,$row[0],$time_in,$lunch_out,$lunch_in,$time_out,$source,"",$gDate,"",$employee_time_in_id);
						$toboy[] = $this->db->insert_id();
						
					}else{
						$toboy = array();
					}
					$num++;
				}
			}
			$this->session->set_userdata('list_conflict2','');
			$this->session->set_userdata('date_mix','');
			return $toboy;
		}
	}
	
	public function get_last_id($id){
		$this->db->where('comp_id',$id);
		$this->db->order_by("eti.time_in","DESC");
		$q = $this->edb->get("employee_time_in AS eti",1,0);
		$r = $q->row();
		
		
		return ($q->num_rows()>0)? $r->employee_time_in_id : false;
	}
	
	
	public function test(){
		echo 'atay';
	}
	
	/**
	 * Check Company Break Time
	 * Mao ni ako ge add mga bro.
	 * Musheka
	 * @param unknown_type $emp_work_schedule_id
	 * @param unknown_type $company_id
	 */
	public function check_break_time2($work_schedule_id,$comp_id,$day){
		
		$number_of_breaks_per_day = 0;
		$w_uwd = array(
				//"payroll_group_id"=>$payroll_group,
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id,
				"days_of_work" => $day
		);
		$this->db->where($w_uwd);
		$q_uwd = $this->db->get("regular_schedule");
		$r_uwd = $q_uwd->row();
		//p($r_uwd);
		if($q_uwd->num_rows() > 0){
			$number_of_breaks_per_day = $r_uwd->break_in_min;
			$shift_name = "regular schedule";
	
		}else{
			# WORKSHIFT SETTINGS
			$w_ws = array(
			//"payroll_group_id"=>$payroll_group,
			"work_schedule_id"=>$work_schedule_id,
			"company_id"=>$comp_id,
			);
			$this->db->where($w_ws);
			$q_ws = $this->db->get("split_schedule");
			$r_ws = $q_ws->row();
			if($q_ws->num_rows() > 0){
				$number_of_breaks_per_day = $r_ws->number_of_breaks_per_shift;
				$this->type = "split";
				$this->split_schedule_id = $r_ws->split_schedule_id;
				$shift_name ="split schedule";
			}else{
				# FLEXIBLE HOURS
				$w_fh = array(
				//"payroll_group_id"=>$payroll_group,
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id
				);
				$this->db->where($w_fh);
				$q_fh = $this->db->get("flexible_hours");
				$r_fh = $q_fh->row();
				if($q_fh->num_rows() > 0){
					$number_of_breaks_per_day = $r_fh->duration_of_lunch_break_per_day;
					$shift_name = "flexible hours";
				}
			}
		}
		
		
		if($number_of_breaks_per_day!=0 && $number_of_breaks_per_day!=NULL)
			return $number_of_breaks_per_day;
		else 
			return false;
	}
	

	/**
	 * Add Employee Time In Logs - IMPORT
	 * @param unknown_type $emp_id
	 */
	public function import_add_logs($comp_id, $emp_id, $reason, $time_in,$lunch_out,$lunch_in,$time_out, $hours_worked,$break=0,$source =""){
			if($source!="system"){
				$source = "import";
			}
		if($time_in != NULL){
			// tardiness
			$tardiness = get_tardiness_import2($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in,$break);
	
			// undertime
			$undertime = get_undertime_import2($emp_id, $comp_id, $time_in, $time_out, $lunch_out, $lunch_in,$break);
	
			// total hours worked
			$total_hours_worked = get_tot_hours_limit2($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out,$break);
	
			// total hours worked view
			$total_hours_worked_view = get_tot_hours($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out, $hours_worked,$break);
	
			$date_insert = array(
					"comp_id"=>$comp_id,
					"emp_id"=>$emp_id,
					"date"=>date("Y-m-d",strtotime($time_in)),
					"source"=>$source,
					"time_in"=>$time_in,
					"lunch_out"=>$lunch_out,
					"lunch_in"=>$lunch_in,
					"time_out"=>$time_out,
					"tardiness_min"=>$tardiness,
					"undertime_min"=>$undertime,
					"total_hours"=>$hours_worked,
					"flag_time_in"=>0,
					"total_hours_required"=>$total_hours_worked
			);
			$this->db->insert('employee_time_in', $date_insert);
		}
			
		return TRUE;
	}
	
	/**
	 * Check Employee Work Schedule ID
	 * @param unknown_type $emp_id
	 * @param unknown_type $check_company_id
	 * @param unknown_type $currentdate
	 */
	public function emp_work_schedule($emp_id,$check_company_id,$currentdate){
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
	
	/**
	 * 
	 * Check if split schedule added 
	 * @param unknown_type $emp_no
	 * @param unknown_type $comp_id
	 * @param unknown_type $work_schedule_id
	 * @param unknown_type $currentdate
	 */
	public function check_if_split_schedule($emp_id,$comp_id,$work_schedule_id,$currentdate,$time_in ="",$time_out=""){
		
		    $data = array();
		    $new_timein = false;
		    $block_completed = 0;
		    $time_in = date('Y-m-d H:i:s',strtotime($time_in));
		    $block_not_completed = 0;
 			
			
			//$this->get_starttime($schedule_blocks_id,date('Y-m-d'));
			$w_date = array(
					"es.valid_from <="		=>	$currentdate,
 					"es.until >="			=>	$currentdate
 			);
			$this->db->where($w_date);
			
			
 			$w_ws = array(
 					//"payroll_group_id"=>$payroll_group,
 					"em.work_schedule_id"=>$work_schedule_id,
 					"em.company_id"=>$comp_id,
 					"em.emp_id" => $emp_id
 			);
 			$this->db->where($w_ws);
 			$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
 			$q_ws = $this->edb->get("employee_sched_block AS em");
			$r_ws = $q_ws->result();
			
			
			if($q_ws->num_rows() > 0){
			
							
				
				$split = $this->elm->get_splitschedule_info_new($time_in,$work_schedule_id,$emp_id,$comp_id);
	
				if($split){				
					$data = $split;
				}			

			}
			
			$day = date('l',strtotime($time_in));
		
			$w_uwd = array(
					//"payroll_group_id"=>$payroll_group,
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id,
					"days_of_work" => $day,
					"status" => 'Active'
			);
			$this->db->where($w_uwd);
			$q_uwd = $this->db->get("regular_schedule");
			$r_uwd = $q_uwd->row();
			
			if($q_uwd->num_rows() > 0){
				return false;
			}
			
			//p($data);
			return $data;
	}
	
	/**
	 * 
	 * filter if the time in is split schedule area
	 * @param unknown_type $result
	 * @param unknown_type $time_in
	 */
	public function get_splitschedule_info($result,$time_in = null){
		
		$date_time = date('Y-m-d',strtotime($time_in));
		$arr = array();
		$row_list = array();
			

		foreach($result as $rx):
				
				$row = $this->elm->get_blocks_list($rx->schedule_blocks_id);		
				
				$start_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->start_time));
				$end_time = date('Y-m-d H:i:s', strtotime($date_time." ".$row->end_time));
				if($row->end_time <= $row->start_time){
				
					$end_time = date('Y-m-d H:i:s',strtotime($date_time." ".$row->end_time . " +1 day"));
				
				}
				
				$row_list['break_in_min'] = $row->break_in_min;
				$row_list['start_time'] = $start_time;
				$row_list['end_time'] = $end_time;
				$row_list['total_hours_work_per_block'] = $row->total_hours_work_per_block;
				$row_list['block_name'] = $row->block_name;
				$row_list['schedule_blocks_id'] = $row->schedule_blocks_id;
				
				if($time_in >= $start_time && $time_in <= $end_time){
					return $row_list;
										
				}else{					
					$arr[] = $row_list;					
				}							
		endforeach;
		 
		foreach($arr as $key => $row2):

			if($time_in <= $row2['start_time']){
				return $arr[$key];
				break;
			}
		endforeach;
		
		return false;
	}
	
	
	public function identify_split_info($time_in,$time_out,$emp_id,$comp_id,$gDate=""){
		
		$data = array();
		
		$yesterday_m = $gDate;
		$yesterday = date('Y-m-d',strtotime($yesterday_m. " -1 day"));
		$work_schedule_id = $this->employee->emp_work_schedule($emp_id,$comp_id,$yesterday);
			
		$split = $this->check_if_split_schedule($emp_id,$comp_id,$work_schedule_id, $yesterday ,$time_in,$time_out);
		
		if($split){
			
			$yest_list = $this->elm->list_of_blocks($yesterday,$emp_id,$work_schedule_id,$comp_id);
			$mid_night = date('Y-m-d H:i:s',strtotime($yesterday." 24:00:00"));
		
			if($yest_list){
				$first_sched = reset($yest_list);
				$last_sched = max($yest_list);
				$ystart = $this->elm->get_starttime($first_sched->schedule_blocks_id,$time_in);	
				$yend = $this->elm->get_endtime($last_sched->schedule_blocks_id,$time_in);
				
				foreach($yest_list  as $rx){
									
					$row = $this->elm->get_blocks_list($rx->schedule_blocks_id);						
					$start_time = date('Y-m-d H:i:s',strtotime($yesterday." ".$row->start_time));
					$end_time = date('Y-m-d H:i:s', strtotime($yesterday." ".$row->end_time));
											
					if($ystart >= $yend && $time_in >= $mid_night){ // nightshift schedule					
						$new = date('H:i:s',strtotime($ystart));
						$firstx = date('Y-m-d H:i:s',strtotime($yesterday." ".$new));
						
						if($start_time >= $firstx && $start_time <=$mid_night){						
							
							$data['work_schedule_id'] = $work_schedule_id;
							$data['current_date'] = $yesterday;
							$data['time_in'] = $time_in;		
						}
					}				
				}
								
			}
	
		}
		return $data;
	 }
	 
	 public function convert_emp_id_to_emp_no($emp_id){
	 	
	 	$this->db->where('e.emp_id',$emp_id);
	 	
	 	$this->edb->join('accounts AS a','a.account_id = e.account_id',"INNER");
	 	$query = $this->edb->get('employee AS e');
	 	
	 	$result = $query->row();

	 	return ($query->num_rows() > 0)? $result->payroll_cloud_id : false;
	 }
	 
	 public function total_hours_worked($to,$from){
	 	$total      = strtotime($to) - strtotime($from);
	 	$hours      = floor($total / 60 / 60);
	 	$minutes    = floor(($total - ($hours * 60 * 60)) / 60);
	 	return  ($hours * 60) + $minutes;
	 }
	 

	 /**
	  * use in updating shift and calculate hours according employee schedule
	  * @param unknown $emp_id
	  * @param unknown $date_from
	  * @param unknown $date_to
	  * @param unknown $workschedule_id
	  * @param unknown $comp_id
	  */
	 public function timesheet_update($emp_id,$date_from,$date_to,$comp_id){
	 	
	 	$date_range = $this->date_range($date_from, $date_to,'+1 day','Y-m-d');
	 	
	 	foreach($date_range as $key => $date){
		 	$w = array(
		 			"eti.emp_id" => $emp_id,
		 			"eti.status" => "Active",
		 			"eti.comp_id" => $comp_id,
		 			"eti.date" => $date
		 	);
		 	
		 	$this->edb->where($w);
		 	$q = $this->edb->get("employee_time_in AS eti",1,0);
		 	$r = $q->row();
		 	
		 	if($r){		 		
		 		$emp_no =$this->convert_emp_id_to_emp_no($emp_id);				
		 		$this->import_new($comp_id,$emp_id,$r->time_in,$r->lunch_out,$r->lunch_in,$r->time_out,"change",$emp_no,$date);
		 	}	 			 		
	 	}
	 }
	 
	 
	 public function calculate_hours_by_shift($work_schedule_id,$comp_id){
	 	
	 	
	 	$w = array(
	 			"eti.status" => "Active",
	 			"eti.comp_id" => $comp_id,
	 			"eti.work_schedule_id"=>$work_schedule_id,
	 			"eti.time_out" => null
	 	);
	 	
	 	$this->edb->where($w);
	 	$q = $this->edb->get("employee_time_in AS eti");
	 	$result = $q->result();
	 	
	 	if($result){
		 	foreach($result as $r){
		 		$emp_no =$this->convert_emp_id_to_emp_no($r->emp_id);
		 		$this->import_new($comp_id,$r->emp_id,$r->time_in,$r->lunch_out,$r->lunch_in,$r->time_out,$r->source,$emp_no,$r->date);
		 	}
	 	}
	 }
	 
	 
	 /**
	  * Creating date collection between two dates
	  *
	  * <code>
	  * <?php
	  * # Example 1
	  * date_range("2014-01-01", "2014-01-20", "+1 day", "m/d/Y");
	  *
	  * # Example 2. you can use even time
	  * date_range("01:00:00", "23:00:00", "+1 hour", "H:i:s");
	  * </code>
	  *
	  * @author Ali OYGUR <alioygur@gmail.com>
	  * @param string since any date, time or datetime format
	  * @param string until any date, time or datetime format
	  * @param string step
	  * @param string date of output format
	  * @return array
	  */
	 function date_range($first, $last, $step = '+1 day', $output_format = 'd/m/Y' ) {
	 
	 	$dates = array();
	 	$current = strtotime($first);
	 	$last = strtotime($last);
	 
	 	while( $current <= $last ) {
	 
	 		$dates[] = date($output_format, $current);
	 		$current = strtotime($step, $current);
	 	}
	 
	 	return $dates;
	 }
	 
	 function get_flex_rules($company_id,$work_schedule_id){
	 	$w = array(
	 			'work_schedule_id' 	=> $work_schedule_id,
	 			'comp_id'		=> $company_id	
	 	);
	 	$this->db->where($w);
	 	$q = $this->db->get('work_schedule');
	 	$row = $q->row();
	 	return ($row) ? $row : false;
	 }
	 
	 function get_attendance_settings($comp_id){
	 	$w = array('company_id' => $comp_id,'status' => 'enabled');
	 	$this->db->where($w);
	 	$q = $this->db->get('attendance_settings');
	 	$r = $q->row();
	 	return ($r) ? $r : false;
	 }
	 
}
/* End of file */