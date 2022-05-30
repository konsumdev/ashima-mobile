<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
*	Helper : global helpers
*	Author : Christopher Cuizon <christophercuizons@gmail.com>
*	Usage  : Use for all
*/
	function notifications_ashima_email(){
		$default_email = 'notifications@createwebworks.com';
		if($_SERVER['SERVER_NAME'] == 'ashima.ph' || $_SERVER['SERVER_NAME'] == 'curlashima.ph' || get_domain(base_url()) == 'ashima.ph'){
			$default_email = 'notifications@ashima.ph';
		}
		return $default_email;
	}
	
	/**
	 * Defines csrf token name for global purposes only 
	 * @return csrf_token_name
	 */
	function itoken_name(){
		$CI =& get_instance();
		return $CI->config->item('csrf_token_name');
	}
	
	/**
	 * Defines the csrf cookie name
	 * @return csrf_cookie_name
	 * @example itoken_cookie() or javascript 
	 * @uses jQuery.token("< ? php echo itoken_cookie(); ? >");
	 */
	function itoken_cookie(){
		$CI =& get_instance();
		return $CI->config->item('csrf_cookie_name');
	}

	/**
	 * Checks users is admin
	 * @return object
	 */
	function check_user_admin(){
		$CI =& get_instance();
		$id = $CI->session->userdata('account_id');
		$query = $CI->db->get_where("konsum_admin",array("account_id"=>$id));
		$row = $query->row();
		return $row;
	}
	
	/**
	*  	creates a folder of every company 
	*  	@param int id
	*	@returns object 
	**/
	function create_comp_directory($id){
		$CI =& get_instance();
		$dir = "uploads/companies/";
		if($id !=""){
			if(!is_dir($dir.$id)) {
				$folder = array("folder"=>$id,"pdf"=>$id."/pdf","excel"=>$id."/excel");
				foreach($folder as $key){				
					mkdir($dir.$key,0755,true);
				}
			}	
		}else{
			return false;
		}
	}
	
	/**
	 * Photo upload for helper only
	 * @param string $path
	 * @param int $max_size
	 * @param int $max_width
	 * @param int $max_height
	 * @return object
	 */
	function photo_upload($path="./uploads/",$max_size= 1000,$max_width=2024,$max_height=2000){
		$CI =& get_instance();
		//TRIGGER AJAX CHANGE CHMODE Para choi
		if(is_dir($path)){
			@chmod("./uploads",0777);
			@chmod("./uploads/companies",0777);
			@chmod($path,0777);
		}
		$config['upload_path'] = $path;
		$config['allowed_types'] = 'gif|jpg|png|jpeg';
		$config['max_size']	= $max_size;
		$config['max_width']  = $max_width;
		$config['max_height']  = $max_height;
		$config['encrypt_name'] = TRUE;
		$CI->load->library('upload', $config);
		if(!$CI->upload->do_upload('upload')) {
			$error = array("status"=>"0",'error' => $CI->upload->display_errors(),'upload_data'=>'');
			/** added folder rights ANTI HACK **/
		#	chmod("./uploads",755);
		#	chmod("./uploads/companies",755);
		#	chmod($path,755);
			/** end added ANTI HACK **/
			return $error;
		} else {
			$data = array("status"=>"1",'error'=>'','upload_data' => $CI->upload->data());
		#	chmod("./uploads",0755);
		#	chmod("./uploads/companies",0755);
		#	chmod($path,0755);
			return $data;
		}
	}
	
	/**
	* Uploads a file within any types
	*	
	*
	*/
	function photo_upload_any($path="./uploads/",$max_size= 3000,$max_width=3024,$max_height=3000,$allowed_types = "csv"){
		$CI =& get_instance();
		//TRIGGER AJAX CHANGE CHMODE Para choi
		if(is_dir($path)){
		
		#		@chmod("./uploads/companies",0777);
	#		@chmod($path,777);
		}
		$config['upload_path'] = $path;
		$config['allowed_types'] = $allowed_types;
		$config['max_size']	= $max_size;
		$config['max_width']  = $max_width;
		$config['max_height']  = $max_height;
		$config['encrypt_name'] = TRUE;
		$CI->load->library('upload', $config);
		if(!$CI->upload->do_upload('upload')) {
			$error = array("status"=>"0",'error' => $CI->upload->display_errors(),'upload_data'=>'');
			/** added folder rights ANTI HACK **/
		#	@chmod("./uploads",0755);
		#	@chmod("./uploads/companies",0755);
		#	@chmod($path,755);
			/** end added ANTI HACK **/
			return $error;
		} else {
			$data = array("status"=>"1",'error'=>'','upload_data' => $CI->upload->data());
	#		@chmod("./uploads",0755);
	#		@chmod("./uploads/companies",0755);
	#		@chmod($path,755);
			return $data;
		}
	}
	
	
	/**
	 * Global remover of photos
	 * @param string $filename @example(christophercuizon@gmail.com)
	 * @param int $company_id @example (session company id)
	 * @param string $path_location @example (i have predefined ./upload/companies but you can change 
	 * @return boolean
	 */
	function remove_photo($filename,$company_id,$path_location="./uploads/companies/"){
		$upload_path = $path_location."/{$company_id}/";
		if($filename && $company_id){
			if(file_exists($upload_path.$filename)){
				$f = $upload_path.$filename;
				unlink($f);
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	/**
	 * Checks the date today  
	 * @return date
	 */
	function date_today(){
		return date("Y-m-d");
	}
	
	/**
	*	Check subdomains for validaties 
	*	@return object
	*/
	function subdomain_checker(){
		$CI =& get_instance();
		$subdomain = trim($CI->db->escape_str($CI->uri->segment(1)));
		$query = $CI->db->get_where("company",array("sub_domain"=>$subdomain,"status"=>"Active","deleted"=>"0"));
		$num_rows = $query->num_rows();
		$rows = $query->row();
		$query->free_result();
		return $num_rows ? $rows : false;
	}
	
	/**
	 * Message Empty String
	 * @return string
	 */
	function msg_empty(){
		$msg_emp = "No items on this table yet.";
		return $msg_emp;
	}
	
	/**
	 * DELETES COMPANY SESSION CLEAR THEM OUT
	 * return VOID
	 */
	function delete_company_session(){
		$CI =& get_instance();
		return $CI->session->unset_userdata("company_id");
	}
	
	/**
	 * Image check if valid otherwise restore default image
	 * validates image fetch
	 * @param string $image
	 * @param int $company_id
	 * @param string $no_image
	 * @return string
	 */
	function image_exist($image,$company_id,$no_image="/assets/theme_2015/images/img-user-avatar-dummy.jpg"){
		$image_val = "./uploads/companies/";
		if($image != ""){
			return (file_exists($image_val.$company_id."/".$image)) ? $image_val.$company_id."/".$image : $no_image;
		}else{
			return $no_image;
		}
	}
	
	
	function image_exist_company($image,$company_id,$no_image="/assets/theme_2015/images/img-company-logo-2.png"){
		$image_val = "./uploads/companies/";
		if($image != ""){
			return (file_exists($image_val.$company_id."/".$image)) ? $image_val.$company_id."/".$image : $no_image;
		}else{
			return $no_image;
		}
	}
	
	/**
	 * Checks whose company has been handled by the right 
	 * return object
	 */
	function whose_company(){
		$CI =& get_instance();
		$psa_id = $CI->session->userdata("psa_id");
		$company_name = trim($CI->db->escape_str($CI->uri->segment(1)));
		if(is_numeric($psa_id)){
			$where = array(
				'assigned_company.payroll_system_account_id' => $psa_id,
				'company.sub_domain'						 => $company_name
			);
			
			$CI->db->where($where);
			$CI->db->join('company','company.company_id = assigned_company.company_id','left');
			$query = $CI->db->get('assigned_company');
		
			$row = $query->row();
			return $row;
		}else{
			return false;
		}
	}
		
	function icompany_logo()
	{	
		$CI =& get_instance();
		
		if($CI->uri->segment(2) == 'company_setup' || $CI->uri->segment(2) == 'hr_setup' || $CI->uri->segment(2) == 'payroll_setup'){
			if($CI->session->userdata("company_id")){
				$CI->edb->where('company_id',$CI->session->userdata('company_id'));
				$q = $CI->edb->get('company');
				$res = $q->row();
				$q->free_result();
				return image_exist_company($res->company_logo,$res->company_id);
			}else{
				return '/assets/theme_2015/images/photo_not_available.png';
			}
		}else if($CI->uri->segment(1) == "admin"){
			return '/assets/theme_2013/images/img-logo2.jpg';
		}else{
			$company = whose_company();
			if($company){
				$CI->edb->where('company.sub_domain',$company->sub_domain);
				$CI->edb->join('company','company.company_id = assigned_company.company_id','left');
				$q = $CI->edb->get('assigned_company');
				$res = $q->row();
				$q->free_result();
				return image_exist_company($res->company_logo,$res->company_id);
			}else{
				return '/assets/theme_2015/images/photo_not_available.png';
			}
		}
	}
	
	/**
	 * Replaces spaces with underscore
	 * Enter description here ...
	 * @param string $text
	 */
	function replace_space($text) { 
	    $text = strtolower(htmlentities($text)); 
	    $text = str_replace(get_html_translation_table(), "_", $text);
	    $text = str_replace(" ", "_", $text);
	   	$text = str_replace("-", "_", $text);
	    $text = preg_replace("/[_]+/i", "_", $text);
	    return $text;
	}
	
	/**
	 * Prices amount global
	 * @param numeric $amount
	 */
	function iprice($amount){
		if($amount >= 0){
			return number_format($amount,2);
		}else{
			return false;
		}
	}
	
	/**
	 * Intelligent exports 
	 * This helper will help you achieve enumerous exports such as csv and xls 
	 * @version asherot
	 * @param string $contents
	 * @param enum $type (@example xls, csv)
	 * @param string $filename
	 * @return literature modules
	 */
	function module_literature($contents,$type,$filename="export"){
		if($type){
			switch($type):
				case "xls":
					header('Content-type: application/vnd.ms-excel');
				    header("Content-Disposition: attachment; filename={$filename}.xls");
				    header("Pragma: no-cache");
				    header("Expires: 0"); 
					$output = $contents;
					echo $output;
					return true;
				break;
				case "csv":
					$clean = str_replace("\t",",",$contents);
					header('Content-type: application/csv');
					header('Content-Disposition: attachment; filename='.$filename.'.csv');
					$output = $clean;
					echo $output;
					return true;
				break;
			endswitch;
		}else{
			return false;
		}
	}
	
	/**
	 * THIS WILL CHECK THE lEAVE APPlICATION DETAILS 
	 * USED FOR CHECKING EMPLOYEE IDS 
	 * @param int $employee_leaves_application_id
	 * @return object
	 */
	function check_leave_application($employee_leaves_application_id){
		$CI =& get_instance();
		if(is_numeric($employee_leaves_application_id)){
			$query = $CI->db->get_where("employee_leaves_application",array("employee_leaves_application_id"=>$employee_leaves_application_id));
			$row = $query->row();
			$query->free_result();
			return $row;
		}else{
			return false;
		}
	}
	
	/**
	 * CHECK OVERTIME APPLICATION DETAILS
	 * USED FOR CHECKING EMPLOYEE IDS
	 * @param int $overtime_id
	 */
	function check_overtime_application($overtime_id){
		$CI =& get_instance();
		if(is_numeric($overtime_id)){
			$query = $CI->db->get_where("employee_overtime_application",array("overtime_id"=>$overtime_id));
			$row = $query->row();
			$query->free_result();
			return $row;
		}else{
			return false;
		}
	}
	
	/**
	 * Employee Name
	 * @param unknown_type $emp_id
	 */
	function emp_name($emp_id){
		$CI =& get_instance();
		
		$where = array(
			'emp_id'  => $emp_id,
			'status'  => 'Active',
			'deleted' => '0'
		);
		$CI->edb->where($where);
		$sql = $CI->edb->get('employee');
		 
		$row = $sql->row();
		return ($sql->num_rows() > 0) ? ucwords($row->first_name)." ".ucwords($row->last_name) : "" ;
	}
	
	/**
	 * Cost Center 
	 * @param unknown_type $cost_center_id
	 */
	function cost_center($cost_center_id){
		$CI =& get_instance();
		$w = array(
			"status"=>"Active",
			"cost_center_id"=>$cost_center_id
		);
		$CI->db->where($w);
		$sql = $CI->db->get("cost_center");
		$row = $sql->row();
		return ($sql->num_rows() > 0) ? $row->description : "" ;
	}
	
	/**
	 * Deduction Payroll Group Name
	 * @param unknown_type $id
	 * @param unknown_type $field
	 */
	function deduction_payroll_group($id,$field){
		$CI =& get_instance();
		
		$w = array(
			"status"=>"Active",
			"payroll_group_id"=>$id
		);
		$CI->db->where($w);
		$sql = $CI->db->get("deductions_payroll_group");
		$row = $sql->row();
		return ($sql->num_rows() > 0) ? $row->$field : "" ;
	}
	
	/**
	 * Deduction Income Information
	 * @param unknown_type $id
	 * @param unknown_type $field
	 * @param unknown_type $income_val
	 */
	function deduction_income($income_val,$field,$comp_id){
		$CI =& get_instance();
		
		$w = array(
			"status"=>"Active",
			"comp_id"=>$comp_id,
			"income"=>$income_val
		);
		$CI->db->where($w);
		$sql = $CI->db->get("deductions_income");
		$row = $sql->row();
		
		return ($row) ? $row->$field : FALSE ;
	}
	
	/**
	 * Deduction Adjustments Information
	 * @param unknown_type $id
	 * @param unknown_type $field
	 * @param unknown_type $income_val
	 */
	function deduction_adjustments($val,$field,$comp_id){
		$CI =& get_instance();
		
		$w = array(
			"status"=>"Active",
			"adjustments"=>$val,
			"comp_id"=>$comp_id
		);
		$CI->db->where($w);
		$sql = $CI->db->get("deductions_adjustments");
		$row = $sql->row();
		
		return ($row) ? $row->$field : FALSE ;
	}
	
	function tokenize(){
		return random_string('alnum', 16);
	}
	
	/**
	*	THIS HELPER LET YOU SUBTRACT SPECIFIED TIME ONLY NO DATES!!
	*	@param string $time_first
	*	@param string $time_second
	*	@return time
	*/
	function isubtract_time($time_first,$time_second){
		$dawn = floor($time_first - $time_second);
		$dawn_hours = floor($dawn / 3600);
		$dawn_min = sprintf("%02d",floor(($dawn/60) % 60));
		$dawn_secons = sprintf("%02d",$dawn % 60);		
		$output = $dawn_hours.":".$dawn_min.":".$dawn_secons;
		return $output;
	}
	
	function isubtract_time_array($time_first,$time_second){
		$dawn = floor($time_first - $time_second);
		$dawn_hours = floor($dawn / 3600);
		$dawn_min = sprintf("%02d",floor(($dawn/60) % 60));
		$dawn_secons = sprintf("%02d",$dawn % 60);		
		$output = $dawn_hours.":".$dawn_min.":".$dawn_secons;
		$array = array("hours"=>$dawn_hours,"min"=>$dawn_min,"sec"=>$dawn_secons);
		return $array;
	}
	
	function convertToHoursMins($time, $format = '%d:%d') {
		settype($time, 'integer');
		if ($time < 1) {
			return;
		}
		$hours = floor($time / 60);
		$minutes = ($time % 60);
		return sprintf($format, $hours, $minutes);
	}
	
	function sum_the_time($time1, $time2) {
	  $times = array($time1, $time2);
	  $seconds = 0;
	  foreach ($times as $time)
	  {
		list($hour,$minute,$second) = explode(':', $time);
		$seconds += $hour*3600;
		$seconds += $minute*60;
		$seconds += $second;
	  }
	  $hours = floor($seconds/3600);
	  $seconds -= $hours*3600;
	  $minutes  = floor($seconds/60);
	  $seconds -= $minutes*60;
	  // return "{$hours}:{$minutes}:{$seconds}";
	  return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds); // Thanks to Patrick
	}

	function find_difference($start_date,$end_date){
		list($date,$time) = explode(' ',$start_date);
		if($time == NULL){$time = '00:00:00';}
		$startdate = explode("-",$date);
		$starttime = explode(":",$time);
	   
		list($date,$time) = explode(' ',$end_date);
		if($time == NULL){$time = '00:00:00';}
		$enddate = explode("-",$date);
		$endtime = explode(":",$time);
	   
		$secons_dif = mktime($endtime[0],$endtime[1],$endtime[2],$enddate[1],$enddate[2],$enddate[0]) -
	 
		mktime($starttime[0],$starttime[1],$starttime[2],$startdate[1],$startdate[2],$startdate[0]);
	 
		//Different can be returned in many formats
		//In Minutes: floor($secons_dif/60);
		//In Hours: floor($secons_dif/60/60);
		//In days: floor($secons_dif/60/60/24);
		//In weeks: floor($secons_dif/60/60/24/7;
		//In Months: floor($secons_dif/60/60/24/7/4);
		//In years: floor($secons_dif/365/60/24);
	   
		//We will return it in hours
		$difference = floor($secons_dif/60/60);
	   
		return $difference;
	}
	 
	function priority_options($options,$priority_deducations,$status){
		echo "<option value=''>Please Select</option>";
		foreach($options as $optval): 
			$selected = "";
			if($priority_deducations) {
					if($status == $optval){
						$selected ='selected="selected"';
					}
			}
			echo "<option value=\"{$optval}\" {$selected}>{$optval}</option>";
		endforeach;
	}
	
	function check_holiday_val($day,$emp_id,$comp_id,$payroll_group_id){
		$CI =& get_instance();
		$w = array(
			"rest_day"=>date("l",strtotime($day)),
			"company_id"=>$comp_id,
			"payroll_group_id"=>$payroll_group_id,
			"status"=>"Active"
		);
		$CI->db->where($w);
		$q = $CI->db->get('rest_day');
		return ($q->num_rows() > 0) ? TRUE : FALSE ;
	}
	
	/**
	 * Get Total Hours Worked
	 * @param unknown_type $time_in
	 * @param unknown_type $lunch_in
	 * @param unknown_type $lunch_out
	 * @param unknown_type $time_out
	 */
	# Import timesheet model nagamit ani
	function get_tot_hours($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$time_out,$hours_worked,$break=0){
		$CI =& get_instance();
		
		$where = array(
			"emp_id" => $emp_id
		);
		$CI->db->where($where);
		$sql_payroll_info = $CI->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		$payroll_group_id = $row_payroll_info->payroll_group_id;
		
		if($payroll_group_id == "" || $payroll_group_id == NULL){
			return 0;
		}else{
			// check if rest day
			$rest_day = check_holiday_val($time_in,$emp_id,$comp_id,$payroll_group_id);
			if($rest_day){
				$total = (strtotime($time_out) - strtotime($time_in)) / 3600;
			}else{
				
				// check time out for uniform working days
				$where_uw = array(
					"company_id"=>$comp_id,
					"payroll_group_id"=>$payroll_group_id,
					"days_of_work"=>date("l",strtotime($time_in))	
				);
				$CI->db->where($where_uw);
				$sql_uw = $CI->db->get("regular_schedule");
				
				$row_uw = $sql_uw->row();
				if($sql_uw->num_rows() > 0){
					
					// FOR TGG ABOVE
					
					/* $time_out_sec = date("H:i:s",strtotime($time_out));
					
					if(strtotime($row_uw->work_end_time) <= strtotime($time_out_sec)){
									
						$time_in_sec = date("H:i:s",strtotime($time_in));
						$total_hours_worked = (strtotime($row_uw->work_end_time) - strtotime($time_in_sec)) / 3600;
					}else{
						$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
					} */
					
					// FOR CALLCENTER
					
					$time_out_sec = date("H:i:s",strtotime($time_out));
					$time_out_date = date("Y-m-d",strtotime($time_out));
					$new_work_end_time = $time_out_date." ".$row_uw->work_end_time;
					if(strtotime($new_work_end_time) <= strtotime($time_out)){
						
						$time_in_sec = date("H:i:s",strtotime($time_in));
						$total_hours_worked = (strtotime($new_work_end_time) - strtotime($time_in)) / 3600;
						
					}else{
						$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
					}
					
				}else{
					// check time out for workshift
					$where_w = array(
						"company_id"=>$comp_id,
						"payroll_group_id"=>$payroll_group_id
					);
					$CI->db->where($where_w);
					$sql_w = $CI->db->get("split_schedule");
					$row_w = $sql_w->row();
					if($sql_w->num_rows() > 0){
						
						$time_out_sec = date("H:i:s",strtotime($time_out));
						$time_out_date = date("Y-m-d",strtotime($time_out));
						$new_work_end_time = $time_out_date." ".$row_w->end_time;
						
						if(strtotime($new_work_end_time) <= strtotime($time_out)){
										
							$time_in_sec = date("H:i:s",strtotime($time_in));
							$total_hours_worked = (strtotime($new_work_end_time) - strtotime($time_in)) / 3600;
						}else{
							$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
						}
						
					}else{
						// check time out for flexible hours
						$where_f = array(
							"company_id"=>$comp_id,
							"payroll_group_id"=>$payroll_group_id
						);
						$CI->db->where($where_f);
						$sql_f = $CI->db->get("flexible_hours");
						$row_f = $sql_f->row();
						if($sql_f->num_rows() > 0){
							$total_hours_worked = (strtotime($time_in . ' + '.$row_f->total_hours_for_the_day.' hour') - strtotime($time_in)) / 3600;
						}else{
							$total_hours_worked = 0;
						}
					}
				}
				
				$get_tardiness = (get_tardiness_breaktime($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$break)) / 60;
				//$breaktime_hours = add_breaktime($comp_id,$payroll_group_id,$time_in);
				$breaktime_hours = $break / 60;
				
				$total = $total_hours_worked - $get_tardiness - $breaktime_hours;
				if($total > $hours_worked){
					$total = $hours_worked;
				}
			}
			
			return ($total < 0) ? round(0,2) : round($total,2) ;
		}
	}
	
		/**
	 * Get Total Hours Worked
	 * @param unknown_type $time_in
	 * @param unknown_type $lunch_in
	 * @param unknown_type $lunch_out
	 * @param unknown_type $time_out
	 */
	function get_tot_hours_limit($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$time_out){
		$CI =& get_instance();
		$where = array(
			"emp_id" => $emp_id
		);
		$CI->db->where($where);
		$sql_payroll_info = $CI->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		$payroll_group_id = $row_payroll_info->payroll_group_id;
		
		if($payroll_group_id == "" || $payroll_group_id == NULL){
			return 0;
		}else{
			$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
			
			$get_tardiness = (get_tardiness_breaktime($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in)) / 60;
			$breaktime_hours = add_breaktime($comp_id,$payroll_group_id,$time_in);
			
			$total = $total_hours_worked - $get_tardiness - $breaktime_hours;
			
			// check if rest day
			$rest_day = check_holiday_val($time_in,$emp_id,$comp_id,$payroll_group_id);
			if($rest_day){
				$total = (strtotime($time_out) - strtotime($time_in)) / 3600;
			}
			
			return ($total < 0) ? round(0,2) : round($total,2);
		}
	}
	
	/**
	 * Get Total Hours Worked
	 * Import timesheet nagamit ani
	 * @param unknown_type $time_in
	 * @param unknown_type $lunch_in
	 * @param unknown_type $lunch_out
	 * @param unknown_type $time_out
	 */
	function get_tot_hours_limit2($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$time_out,$break=0){
		$CI =& get_instance();
		$where = array(
				"emp_id" => $emp_id
		);
		$CI->db->where($where);
		$sql_payroll_info = $CI->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		$payroll_group_id = $row_payroll_info->payroll_group_id;
	
		if($payroll_group_id == "" || $payroll_group_id == NULL){
			return 0;
		}else{
			$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
				
			$get_tardiness = (get_tardiness_breaktime($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$break)) / 60;
			//$breaktime_hours = add_breaktime($comp_id,$payroll_group_id,$time_in);
			$breaktime_hours = $break / 60;
			
			$total = $total_hours_worked - $get_tardiness - $breaktime_hours;
				
			// check if rest day
			$rest_day = check_holiday_val($time_in,$emp_id,$comp_id,$payroll_group_id);
			if($rest_day){
				$total = (strtotime($time_out) - strtotime($time_in)) / 3600;
			}
				
			return ($total < 0) ? round(0,2) : round($total,2);
		}
	}
	
	
	
	/**
	 * Get Grace Period
	 * @param unknown_type $comp_id
	 */
	function get_grace_peroid($comp_id){
		$CI =& get_instance();
		
		$w = array(
			"comp_id"=>$comp_id,
			"status"=>"Active"
		);
		$CI->db->where($w);
		$sql = $CI->db->get("tardiness_settings");
		$row = $sql->row();
		
		return ($row) ? $row->tarmin : 0 ;
	}
	
	/**
	 * Get Tardiness
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $date
	 * @param unknown_type $time_in
	 */
	function get_tardiness_import($emp_id,$comp_id,$time_in_import,$lunch_out,$lunch_in){
		
		$CI =& get_instance();
		
		$day = date("l",strtotime($time_in_import));
		
		$where = array(
			"emp_id" => $emp_id
		);
		$CI->db->where($where);
		$sql_payroll_info = $CI->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		$payroll_group_id = $row_payroll_info->payroll_group_id;
		$flag_workshift = 0;
		
		if($payroll_group_id == "" || $payroll_group_id == NULL){
			return 0;
		}else{
			// check if holiday
			$check_holiday = company_holiday($time_in_import,$comp_id);
			if($check_holiday) return 0;
			
			// check if rest day
			$rest_day = check_holiday_val($time_in_import,$emp_id,$comp_id,$payroll_group_id);
			if($rest_day){
				$min_late = 0;
				$min_late_breaktime = 0;
			}else{
				// rest day
				$rd_where = array(
					"company_id"=>$comp_id,
					"rest_day"=>$day,
					"payroll_group_id"=>$payroll_group_id
				);
				$CI->db->where($rd_where);
				$rest_day = $CI->db->get("rest_day");
				
				if($rest_day->num_rows() == 0){
					// uniform working days settings
					$uni_where = array(
						"payroll_group_id"=>$payroll_group_id,
						"company_id"=>$comp_id
					);
					$CI->db->where($uni_where);
					$sql = $CI->db->get("uniform_working_day_settings");
					$row = $sql->row();
					
					if($row && $row->allow_flexible_workhours != 0){
						$payroll_sched_timein = $row->latest_time_in_allowed;
					}else{				
						// uniform working days
						$uw_where = array(
							"payroll_group_id"=>$payroll_group_id,
							"company_id"=>$comp_id,
							"days_of_work"=>$day
						);
						$CI->db->where($uw_where);
						$sql_uniform_working_days = $CI->db->get("uniform_working_day");
						$row_uniform_working_days = $sql_uniform_working_days->row();
						
						if($row_uniform_working_days){
							$payroll_sched_timein = $row_uniform_working_days->work_start_time;
						}else{
							// flexible working days
							$fl_where = array(
								"payroll_group_id"=>$payroll_group_id,
								"company_id"=>$comp_id
							);
							$CI->db->where($fl_where);
							$sql_flexible_days = $CI->db->get("flexible_hours");
							$row_flexible_days = $sql_flexible_days->row();
							
							if($row_flexible_days){
								$payroll_sched_timein = $row_flexible_days->latest_time_in_allowed;
							}else{
								// workshift working days
								$ws_where = array(
									"payroll_group_id"=>$payroll_group_id,
									"company_id"=>$comp_id
								);
								$CI->db->where($ws_where);
								$sql_workshift = $CI->db->get("workshift");
								$row_workshift = $sql_workshift->row();
								
								if($row_workshift){
									$payroll_sched_timein = $row_workshift->start_time;
									$flag_workshift = 1;
								}else{
									$payroll_sched_timein = "00:00:00";
								}
							}
						}
					}
				}else{
					$payroll_sched_timein = "00:00:00";
				}
				
				if($payroll_sched_timein != "00:00:00" || $payroll_sched_timein != "" || $payroll_sched_timein != NULL){
					
					$time_in_original = $time_in_import;
					$time_in_import = date("H:i:s",strtotime($time_in_import));
				
					// for tardiness time in
					$time_x=(strtotime($time_in_import) - strtotime($payroll_sched_timein)) / 3600;

					// check time in and allowed time in
					$ti_val = date("A",strtotime($time_in_import));
					$at_val = date("A",strtotime($payroll_sched_timein));
					
					if($ti_val == "PM" && $at_val == "AM"){
						// for tardiness time in
						$time_date = date("Y-m-d",strtotime($time_in_original));
						$add_oneday_timein = date("Y-m-d",strtotime($time_date."+ 1 day"));
						$new_allowed_time = $add_oneday_timein." ".$payroll_sched_timein;
						
						$time_x=(strtotime($time_in_original) - strtotime($new_allowed_time)) / 3600;
					}
					
					if($time_x<0){
						if(abs($time_x) >= 12){
							// print $time_z= (24-(abs($time_x))) * 60 . " late ";
							$min_late = round((24-(abs($time_x))) * 60, 2);
						}else{
							$min_late = 0;
						}
					}else{
						// print $time_x * 60 . " late ";
						$min_late = round($time_x * 60, 2);
					}
				}else{
					$min_late = 0;
				}
				
				// for tardiness break
				
				// get uniform working days and workshift settings for break time
				$where_break = array(
					"company_id" => $comp_id,
					"payroll_group_id" => $payroll_group_id
				);
				$CI->db->where($where_break);
				
				if($flag_workshift == 0) $CI->db->where("workday",$day);
				
				$sql_break = $CI->db->get("break_time");
				$row_break = $sql_break->row();
				
				if($sql_break->num_rows() > 0){
					$breaktime_settings = strtotime($row_break->end_time) - strtotime($row_break->start_time);
					#return $row_break->end_time." ".$row_break->start_time;
				}else{
					// get flexible hours for break time
					$where_break_flex = array(
						"company_id" => $comp_id,
						"payroll_group_id" => $payroll_group_id
					);
					$CI->db->where($where_break_flex);
					$sql_break_flex = $CI->db->get("flexible_hours");
					$row_break_flex = $sql_break_flex->row();
					
					if($sql_break_flex->num_rows() > 0){
						$breaktime_settings = $row_break_flex->duration_of_lunch_break_per_day * 60; // convert to seconds
					} else{
						$breaktime_settings = 0;
					}
				}
				
				$breaktime_timein = strtotime($lunch_in) - strtotime($lunch_out);
				
				if($breaktime_timein > $breaktime_settings){
					$min_late_breaktime = ($breaktime_timein - $breaktime_settings) / 60;
				}else{
					$min_late_breaktime = 0;
				}
	
			}
			
			$min_late = ($min_late < 0) ? 0 : $min_late ;
			$min_late_breaktime = ($min_late_breaktime < 0) ? 0 : $min_late_breaktime;
			
			return $min_late + $min_late_breaktime;	
		}
	}
	
	/**
	 * Bago ni doh. 
	 * @param unknown $emp_id
	 * @param unknown $comp_id
	 * @param unknown $time_in_import
	 * @param unknown $lunch_out
	 * @param unknown $lunch_in
	 * @return number
	 */
		function get_tardiness_import2($emp_id,$comp_id,$time_in_import,$lunch_out,$lunch_in,$break=0){
		
			$CI =& get_instance();
			
			$day = date("l",strtotime($time_in_import));
			
			$where = array(
					"emp_id" => $emp_id
			);
			$CI->db->where($where);
			$sql_payroll_info = $CI->db->get("employee_payroll_information");
			$row_payroll_info = $sql_payroll_info->row();
			$payroll_group_id = $row_payroll_info->payroll_group_id;
			$flag_workshift = 0;
			
			if($payroll_group_id == "" || $payroll_group_id == NULL){
				return 0;
			}else{
				// check if holiday
				$check_holiday = company_holiday($time_in_import,$comp_id);
				if($check_holiday) return 0;
					
				// check if rest day
				$rest_day = check_holiday_val($time_in_import,$emp_id,$comp_id,$payroll_group_id);
				if($rest_day){
					$min_late = 0;
					$min_late_breaktime = 0;
				}else{
					// rest day
					$rd_where = array(
							"company_id"=>$comp_id,
							"rest_day"=>$day,
							"payroll_group_id"=>$payroll_group_id
					);
					$CI->db->where($rd_where);
					$rest_day = $CI->db->get("rest_day");
			
					if($rest_day->num_rows() == 0){
						// uniform working days settings
						$uni_where = array(
								"payroll_group_id"=>$payroll_group_id,
								"company_id"=>$comp_id
						);
						$CI->db->where($uni_where);
						$sql = $CI->db->get("regular_schedule");
						$row = $sql->row();
							
						if($row && ($row->latest_time_in_allowed != NULL)){
							$payroll_sched_timein = date('H:i:s',strtotime($row->work_start_time) + $row->latest_time_in_allowed * 60 ) ;
						}else{
							// uniform working days
							$uw_where = array(
									"payroll_group_id"=>$payroll_group_id,
									"company_id"=>$comp_id,
									"days_of_work"=>$day
							);
							$CI->db->where($uw_where);
							$sql_uniform_working_days = $CI->db->get("regular_schedule");
							$row_uniform_working_days = $sql_uniform_working_days->row();
			
							if($row_uniform_working_days){
								$payroll_sched_timein = $row_uniform_working_days->work_start_time;
							}else{
								// flexible working days
								$fl_where = array(
										"payroll_group_id"=>$payroll_group_id,
										"company_id"=>$comp_id
								);
								$CI->db->where($fl_where);
								$sql_flexible_days = $CI->db->get("flexible_hours");
								$row_flexible_days = $sql_flexible_days->row();
									
								if($row_flexible_days){
									$payroll_sched_timein = $row_flexible_days->latest_time_in_allowed;
								}else{									
										$payroll_sched_timein = "00:00:00";									
								}
							}
						}
					}else{
						$payroll_sched_timein = "00:00:00";
					}
			
					if($payroll_sched_timein != "00:00:00" || $payroll_sched_timein != "" || $payroll_sched_timein != NULL){
							
						$time_in_original = $time_in_import;
						$time_in_import = date("H:i:s",strtotime($time_in_import));
			
						// for tardiness time in
						$time_x=(strtotime($time_in_import) - strtotime($payroll_sched_timein)) / 3600;
			
						// check time in and allowed time in
						$ti_val = date("A",strtotime($time_in_import));
						$at_val = date("A",strtotime($payroll_sched_timein));
							
						if($ti_val == "PM" && $at_val == "AM"){
							// for tardiness time in
							$time_date = date("Y-m-d",strtotime($time_in_original));
							$add_oneday_timein = date("Y-m-d",strtotime($time_date."+ 1 day"));
							$new_allowed_time = $add_oneday_timein." ".$payroll_sched_timein;
			
							$time_x=(strtotime($time_in_original) - strtotime($new_allowed_time)) / 3600;
						}
							
						if($time_x<0){
							if(abs($time_x) >= 12){
								// print $time_z= (24-(abs($time_x))) * 60 . " late ";
								$min_late = round((24-(abs($time_x))) * 60, 2);
							}else{
								$min_late = 0;
							}
						}else{
							// print $time_x * 60 . " late ";
							$min_late = round($time_x * 60, 2);
						}
					}else{
						$min_late = 0;
					}
			
					// for tardiness break
			
					// get uniform working days and workshift settings for break time
					$where_break = array(
							"company_id" => $comp_id,
							"payroll_group_id" => $payroll_group_id
					);
					$CI->db->where($where_break);
			
					if($flag_workshift == 0) $CI->db->where("workday",$day);
			
					$sql_break = $CI->db->get("break_time");
					$row_break = $sql_break->row();
			
					if($break!=0){
						$breaktime_settings = $break;
						#return $row_break->end_time." ".$row_break->start_time;
					}else{
						// get flexible hours for break time
						$where_break_flex = array(
								"company_id" => $comp_id,
								"payroll_group_id" => $payroll_group_id
						);
						$CI->db->where($where_break_flex);
						$sql_break_flex = $CI->db->get("flexible_hours");
						$row_break_flex = $sql_break_flex->row();
							
						if($sql_break_flex->num_rows() > 0){
							$breaktime_settings = $row_break_flex->duration_of_lunch_break_per_day; // convert to seconds
						} else{
							$breaktime_settings = 0;
						}
					}
			
					$breaktime_timein = strtotime($lunch_in) - strtotime($lunch_out);
			
					if($breaktime_timein > $breaktime_settings){
						$min_late_breaktime = ($breaktime_timein - $breaktime_settings) / 60;
					}else{
						$min_late_breaktime = 0;
					}
			
				}
					
				$min_late = ($min_late < 0) ? 0 : $min_late ;
				$min_late_breaktime = ($min_late_breaktime < 0) ? 0 : $min_late_breaktime;
					
				return $min_late + $min_late_breaktime;
			}
	}
	/**
	 * Company Holiday
	 * @param unknown_type $time_in_import
	 * @param unknown_type $comp_id
	 */
	function company_holiday($time_in_import,$comp_id){
		$CI =& get_instance();
		$date = date("Y-m-d",strtotime($time_in_import));
		$w = array(
			"date"=>$date,
			"company_id"=>$comp_id,
			"status"=>"Active"
		);
		$CI->db->where($w);
		$q = $CI->db->get("holiday");
		$r = $q->row();
		return ($r) ? TRUE : FALSE ;
	}
	
	/**
	 * Check Tardiness for undertime only
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $date
	 * @param unknown_type $time_in
	 */
	function check_tardiness_import($emp_id,$comp_id,$time_in_import){
		
		$CI =& get_instance();
		
		$day = date("l",strtotime($time_in_import));
		
		$where = array(
			"emp_id" => $emp_id
		);
		$CI->db->where($where);
		$sql_payroll_info = $CI->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		$payroll_group_id = $row_payroll_info->payroll_group_id;
		
		if($payroll_group_id == "" || $payroll_group_id == NULL){
			return 0;
		}else{
			// check rest day
			$rest_day = check_holiday_val($time_in_import,$emp_id,$comp_id,$payroll_group_id);
			if($rest_day){
				$min_late = 0;
			}else{
				// rest day
				$rd_where = array(
					"company_id"=>$comp_id,
					"rest_day"=>$day,
					"payroll_group_id"=>$payroll_group_id
				);
				$CI->db->where($rd_where);
				$rest_day = $CI->db->get("rest_day");
				
				if($rest_day->num_rows() == 0){
					// uniform working days settings
					$uni_where = array(
						"payroll_group_id"=>$payroll_group_id,
						"company_id"=>$comp_id
					);
					$CI->db->where($uni_where);
					$sql = $CI->db->get("regular_schedule");
					$row = $sql->row();

					if($row && $row->allow_flexible_workhours != 0){
						$payroll_sched_timein = $row->latest_time_in_allowed;
					}else{				
						// uniform working days
						$uw_where = array(
							"payroll_group_id"=>$payroll_group_id,
							"company_id"=>$comp_id,
							"days_of_work"=>$day
						);
						$CI->db->where($uw_where);
						$sql_uniform_working_days = $CI->db->get("regular_schedule");
						$row_uniform_working_days = $sql_uniform_working_days->row();
						
						if($row_uniform_working_days){
							$payroll_sched_timein = $row_uniform_working_days->work_start_time;
						}else{
							// flexible working days
							$fl_where = array(
								"payroll_group_id"=>$payroll_group_id,
								"company_id"=>$comp_id
							);
							$CI->db->where($fl_where);
							$sql_flexible_days = $CI->db->get("flexible_hours");
							$row_flexible_days = $sql_flexible_days->row();
							
							if($row_flexible_days){
								$payroll_sched_timein = $row_flexible_days->latest_time_in_allowed;
							}else{
								// workshift working days
								$ws_where = array(
									"payroll_group_id"=>$payroll_group_id,
									"company_id"=>$comp_id
								);
								$CI->db->where($ws_where);
								$sql_workshift = $CI->db->get("split_schedule");
								$row_workshift = $sql_workshift->row();
								
								if($row_workshift){
									$payroll_sched_timein = $this->get_starttime($row_workshift->split_schedule_id,$time_in_import);
								}else{
									$payroll_sched_timein = "00:00:00";
								}
							}
						}
					}
				}else{
					$payroll_sched_timein = "00:00:00";
				}
				
				$time_in_import = date("H:i:s",strtotime($time_in_import));
				
				// for tardiness time in
				$time_x=(strtotime($time_in_import) - strtotime($payroll_sched_timein)) / 3600;	
			
				if($payroll_sched_timein != "00:00:00" || $payroll_sched_timein != "" || $payroll_sched_timein != NULL){
					if($time_x<0){
						if(abs($time_x) >= 12){
							// print $time_z= (24-(abs($time_x))) * 60 . " late ";
							$min_late = round((24-(abs($time_x))) * 60, 2);
						}else{
							$min_late = 0;
						}
					}else{
						// print $time_x * 60 . " late ";
						$min_late = round($time_x * 60, 2);
					}
				}else{
					$min_late = 0;
				}	
			}
			
			return ($min_late < 0) ? 0 : $min_late;	
		}
	}
	
	/**
	 * Get Tardiness for breaktime
	 * import timesheet nagamit
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $date
	 * @param unknown_type $time_in
	 */
	#import timesheet model nagamit ani
	function get_tardiness_breaktime($emp_id,$comp_id,$time_in_import,$lunch_out,$lunch_in,$break=0){
		
		$CI =& get_instance();
		
		$day = date("l",strtotime($time_in_import));
		
		$where = array(
			"emp_id" => $emp_id
		);
		$CI->db->where($where);
		$sql_payroll_info = $CI->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		$payroll_group_id = $row_payroll_info->payroll_group_id;
		$flag_workshift = 0;
		
		if($payroll_group_id == "" || $payroll_group_id == NULL){
			return 0;
		}else{
			// check rest day
			$rest_day = check_holiday_val($time_in_import,$emp_id,$comp_id,$payroll_group_id);
			if($rest_day){
				$min_late_breaktime = 0;
			}else{
				// rest day
				$rd_where = array(
					"company_id"=>$comp_id,
					"rest_day"=>$day,
					"payroll_group_id"=>$payroll_group_id
				);
				$CI->db->where($rd_where);
				$rest_day = $CI->db->get("rest_day");
				
				if($rest_day->num_rows() == 0){
					// uniform working days settings
					$uni_where = array(
						"payroll_group_id"=>$payroll_group_id,
						"company_id"=>$comp_id
					);
					$CI->db->where($uni_where);
					$sql = $CI->db->get("regular_schedule");
					$row = $sql->row();
					
					if($row && $row->allow_flexible_workhours != 0){
						$payroll_sched_timein = $row->latest_time_in_allowed;
					}else{				
						// uniform working days
						$uw_where = array(
							"payroll_group_id"=>$payroll_group_id,
							"company_id"=>$comp_id,
							"days_of_work"=>$day
						);
						$CI->db->where($uw_where);
						$sql_uniform_working_days = $CI->db->get("regular_schedule");
						$row_uniform_working_days = $sql_uniform_working_days->row();
						
						if($row_uniform_working_days){
							$payroll_sched_timein = $row_uniform_working_days->work_start_time;
						}else{
							// flexible working days
							$fl_where = array(
								"payroll_group_id"=>$payroll_group_id,
								"company_id"=>$comp_id
							);
							$CI->db->where($fl_where);
							$sql_flexible_days = $CI->db->get("flexible_hours");
							$row_flexible_days = $sql_flexible_days->row();
							
							if($row_flexible_days){
								$payroll_sched_timein = $row_flexible_days->latest_time_in_allowed;
							}else{
								// workshift working days
								$ws_where = array(
									"payroll_group_id"=>$payroll_group_id,
									"company_id"=>$comp_id
								);
								$CI->db->where($ws_where);
								$sql_workshift = $CI->db->get("split_schedule");
								$row_workshift = $sql_workshift->row();
								
								if($row_workshift){
									$payroll_sched_timein = $row_workshift->start_time;
									$flag_workshift = 1;
								}else{
									$payroll_sched_timein = "00:00:00";
								}
							}
						}
					}
				}else{
					$payroll_sched_timein = "00:00:00";
				}
				
				$time_in_import = date("H:i:s",strtotime($time_in_import));
				
				// for tardiness time in
				$time_x=(strtotime($time_in_import) - strtotime($payroll_sched_timein)) / 3600;	
			
				if($payroll_sched_timein != "00:00:00" || $payroll_sched_timein != "" || $payroll_sched_timein != NULL){
					if($time_x<0){
						if(abs($time_x) >= 12){
							// print $time_z= (24-(abs($time_x))) * 60 . " late ";
							$min_late = round((24-(abs($time_x))) * 60, 2);
						}else{
							$min_late = 0;
						}
					}else{
						// print $time_x * 60 . " late ";
						$min_late = round($time_x * 60, 2);
					}
				}else{
					$min_late = 0;
				}
				
				// for tardiness break
				
				// get uniform working days and workshift settings for break time
				$where_break = array(
					"company_id" => $comp_id,
					"payroll_group_id" => $payroll_group_id
				);
				$CI->db->where($where_break);
				if($flag_workshift == 0) $CI->db->where("workday",$day);
				$sql_break = $CI->db->get("break_time");
				$row_break = $sql_break->row();
				
				if($break){
					 $breaktime_settings = $break;
				}else{
					// get flexible hours for break time
					$where_break_flex = array(
						"company_id" => $comp_id,
						"payroll_group_id" => $payroll_group_id
					);
					$CI->db->where($where_break_flex);
					$sql_break_flex = $CI->db->get("flexible_hours");
					$row_break_flex = $sql_break_flex->row();
					
					if($sql_break_flex->num_rows() > 0){
						$breaktime_settings = $row_break_flex->duration_of_lunch_break_per_day; // convert to seconds
					}else{
						$breaktime_settings = 0;
					}
				}
				
				$breaktime_timein = strtotime($lunch_in) - strtotime($lunch_out);
				
				if($breaktime_timein > $breaktime_settings){
					$min_late_breaktime = ($breaktime_timein - $breaktime_settings) / 60;
				}else{
					$min_late_breaktime = 0;
				}	
			}
			
			return ($min_late_breaktime < 0) ? 0 : $min_late_breaktime ;	
		}
	}
	
	/**
	 * Add Breaktime for undertime
	 * @param unknown_type $comp
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $workday
	 */
	function add_breaktime($comp_id,$payroll_group_id,$workday){
		$CI =& get_instance();
		$flag_workshift = 0;
		
		// workshift working days
		$workshift_where = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$comp_id
		);
		$CI->db->where($workshift_where);
		$workshift_query = $CI->db->get("split_schedule");
		$workshift_row = $workshift_query->row();
		if($workshift_row) $flag_workshift = 1;
		
		$day = date("l",strtotime($workday));
		$where = array(
			"company_id" => $comp_id,
			"payroll_group_id" => $payroll_group_id
		);
		$CI->db->where($where);
		if($flag_workshift == 0) $CI->db->where("workday",$day); 
		$sql = $CI->db->get("break_time");
		
		// FOR UNIFORM WORKING DAYS
		if($sql->num_rows() > 0){
			$row_uniform = $sql->row();
			$breaktime = (strtotime($row_uniform->end_time) - strtotime($row_uniform->start_time)) / 3600;
		}else{
			$breaktime = 0;
		}
			
		return ($breaktime < 0) ? 0 : $breaktime ;
	}
	
	/**
	 * Get Undertime for import
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $date
	 * @param unknown_type $time_in
	 */
	function get_undertime_import($emp_id,$comp_id,$date_timein,$date_timeout,$lunch_out,$lunch_in){
		$CI =& get_instance();
		
		$day = date("l",strtotime($date_timein));
		$start_time = "";
		
		$where = array(
			"emp_id" => $emp_id
		);
		$CI->db->where($where);
		$sql_payroll_info = $CI->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		$payroll_group_id = $row_payroll_info->payroll_group_id;

		if($payroll_group_id == "" || $payroll_group_id == NULL){
			return 0;
		}else{
			
			// check if holiday
			$check_holiday = company_holiday($date_timein,$comp_id);
			if($check_holiday) return 0;
			
			// check rest day
			$rest_day = check_holiday_val($date_timein,$emp_id,$comp_id,$payroll_group_id);
			if($rest_day){
				$under_min_val = 0;
			}else{
				// rest day
				$rd_where = array(
					"company_id"=>$comp_id,
					"rest_day"=>$day,
					"payroll_group_id"=>$payroll_group_id
				);
				$CI->db->where($rd_where);
				$rest_day = $CI->db->get("rest_day");
				
				if($rest_day->num_rows() == 0){
					// uniform working days
					$uw_where = array(
						"payroll_group_id"=>$payroll_group_id,
						"company_id"=>$comp_id,
						"days_of_work"=>$day
					);
					$CI->db->where($uw_where);
					$sql_uniform_working_days = $CI->db->get("uniform_working_day");
					$row_uniform_working_days = $sql_uniform_working_days->row();
					
					if($row_uniform_working_days){
						$start_time = $row_uniform_working_days->work_start_time;
						$undertime_min = $row_uniform_working_days->work_end_time;
						$working_hours = $row_uniform_working_days->working_hours;
					}else{
						// flexible working days
						$fl_where = array(
							"payroll_group_id"=>$payroll_group_id,
							"company_id"=>$comp_id
						);
						$CI->db->where($fl_where);
						$sql_flexible_days = $CI->db->get("flexible_hours");
						$row_flexible_days = $sql_flexible_days->row();
						
						if($row_flexible_days){
							$CI->db->where("emp_id",$emp_id);
							$CI->db->order_by("date", "DESC");
							$CI->db->limit(1);
							$flexible_compute_time = $CI->db->get("employee_time_in");
							$row_flexible_compute_time = $flexible_compute_time->row();
							
							/* $flexible_compute_time = $CI->db->query("
								SELECT * FROM `employee_time_in` 
								WHERE emp_id = '{$emp_id}'
								ORDER BY date DESC
								LIMIT 1
							"); */
							
							if($row_flexible_compute_time){
								$time_in = explode(" ", $row_flexible_compute_time->time_in);;
								$flexible_work_end = $time_in[1];
								
								// flexible total hours per day
								$flx_where = array(
									"payroll_group_id"=>$payroll_group_id,
									"company_id"=>$comp_id
								);
								$CI->db->where($flx_where);
								$sql_flexible_working_days = $CI->db->get("flexible_hours");
								$row_flexible_working_days = $sql_flexible_working_days->row();
								
								if($row_flexible_working_days){
									$total_hours_for_the_day = $row_flexible_working_days->total_hours_for_the_day;
									$end_time = date("H:i:s",strtotime($flexible_work_end) + 60 * 60 * $total_hours_for_the_day);
									
									$start_time = $row_flexible_working_days->latest_time_in_allowed;
									$undertime_min =  $end_time;
									$working_hours = $row_flexible_working_days->total_hours_for_the_day;
								}else{
									$undertime_min =  "00:00:00";
								}
							}else{
								$undertime_min =  "00:00:00";
							}
							
						}else{
							// workshift working days
							$ws_where = array(
								"payroll_group_id"=>$payroll_group_id,
								"company_id"=>$comp_id
							);
							$CI->db->where($ws_where);
							$sql_workshift = $CI->db->get("workshift");
							$row_workshift = $sql_workshift->row();
							
							if($row_workshift){
								$undertime_min =  $row_workshift->end_time;
								$working_hours = $row_workshift->working_hours;
							}else{
								$undertime_min =  "00:00:00";
							}
						}
					}
				}else{
					$undertime_min = "00:00:00";
					$working_hours = 0;
				}
				
				$date_timeout_sec = date("H:i:s",strtotime($date_timeout));
				
				if($date_timein == "" && $date_timeout == "" && $lunch_out == "" && $lunch_in == ""){
					return 0;
				}
				
				if($start_time == ""){
					return 0;
				}
				
				// check PM and AM
				$check_endtime = date("A",strtotime($undertime_min));
				$check_timout = date("A",strtotime($date_timeout_sec));
				
				// callcenter trapping
				if($check_endtime == "AM" && $check_timout == "PM" && $check_timein == "PM"){
					$time_out_date = date("Y-m-d",strtotime($date_timeout_sec."+1 day"));
					$new_undertime_min = $time_out_date." ".$undertime_min;
					$under_min_val = (strtotime($new_undertime_min) - strtotime($date_timeout)) / 60;
				}else{
					if(strtotime($date_timeout_sec) <= strtotime($undertime_min)){
						$check_tardiness_import = check_tardiness_import($emp_id,$comp_id,$date_timein);
						if($check_tardiness_import == 0){
							if(strtotime($undertime_min) <= strtotime($date_timeout_sec)){							
								$under_min_val = (strtotime($undertime_min) - strtotime($date_timeout_sec)) / 60;
							}else{
								$breaktime_hours = add_breaktime($comp_id,$payroll_group_id,$date_timein); 
								$working_hours = $working_hours + $breaktime_hours;
								$date_timin_sec = date('H:i:s', strtotime($date_timein));
								
								$new_date_timein = (strtotime($start_time) <= strtotime($date_timin_sec)) ? $date_timein : $start_time ;
								$new_timeout_sec = date('H:i:s', strtotime($new_date_timein . ' + '.$working_hours.' hour'));
								$under_min_val = (strtotime($new_timeout_sec) - strtotime($date_timeout_sec)) / 60;
							}
						}else{
							$under_min_val = (strtotime($undertime_min) - strtotime($date_timeout_sec)) / 60;
						}
					}else{
						$under_min_val = 0;
					}
				}
			}
			
		// check total hours for workday
		$get_hours_worked_to_mins = get_hours_worked($day, $emp_id) * 60; //xxd
		
		if($get_hours_worked_to_mins < $under_min_val) return 0;
		
			
			return ($under_min_val < 0) ? 0 : $under_min_val ;	
		}
	}
	
	/**
	 * Get Undertime for import
	 * Nagamit ani kai ang import timesheet
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $date
	 * @param unknown_type $time_in
	 */
	function get_undertime_import2($emp_id,$comp_id,$date_timein,$date_timeout,$lunch_out,$lunch_in,$break=0){
		$CI =& get_instance();
	
		$day = date("l",strtotime($date_timein));
		$start_time = "";
	
		$where = array(
				"emp_id" => $emp_id
		);
		$CI->db->where($where);
		$sql_payroll_info = $CI->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		$payroll_group_id = $row_payroll_info->payroll_group_id;
	
		if($payroll_group_id == "" || $payroll_group_id == NULL){
			return 0;
		}else{
				
			// check if holiday
			$check_holiday = company_holiday($date_timein,$comp_id);
			if($check_holiday) return 0;
				
			// check rest day
			$rest_day = check_holiday_val($date_timein,$emp_id,$comp_id,$payroll_group_id);
			if($rest_day){
				$under_min_val = 0;
			}else{
				// rest day
				$rd_where = array(
						"company_id"=>$comp_id,
						"rest_day"=>$day,
						"payroll_group_id"=>$payroll_group_id
				);
				$CI->db->where($rd_where);
				$rest_day = $CI->db->get("rest_day");
	
				if($rest_day->num_rows() == 0){
					// uniform working days
					$uw_where = array(
							"payroll_group_id"=>$payroll_group_id,
							"company_id"=>$comp_id,
							"days_of_work"=>$day
					);
					$CI->db->where($uw_where);
					$sql_uniform_working_days = $CI->db->get("regular_schedule");
					$row_uniform_working_days = $sql_uniform_working_days->row();
						
					if($row_uniform_working_days){
						$start_time = $row_uniform_working_days->work_start_time;
						$undertime_min = $row_uniform_working_days->work_end_time;
						$working_hours = $row_uniform_working_days->total_work_hours;
					}else{
						// flexible working days
						$fl_where = array(
								"payroll_group_id"=>$payroll_group_id,
								"company_id"=>$comp_id
						);
						$CI->db->where($fl_where);
						$sql_flexible_days = $CI->db->get("flexible_hours");
						$row_flexible_days = $sql_flexible_days->row();
	
						if($row_flexible_days){
							$CI->db->where("emp_id",$emp_id);
							$CI->db->order_by("date", "DESC");
							$CI->db->limit(1);
							$flexible_compute_time = $CI->db->get("employee_time_in");
							$row_flexible_compute_time = $flexible_compute_time->row();
								
							/* $flexible_compute_time = $CI->db->query("
							 SELECT * FROM `employee_time_in`
							 WHERE emp_id = '{$emp_id}'
							 ORDER BY date DESC
							 LIMIT 1
							"); */
								
							if($row_flexible_compute_time){
								$time_in = explode(" ", $row_flexible_compute_time->time_in);;
								$flexible_work_end = $time_in[1];
	
								// flexible total hours per day
								$flx_where = array(
										"payroll_group_id"=>$payroll_group_id,
										"company_id"=>$comp_id
								);
								$CI->db->where($flx_where);
								$sql_flexible_working_days = $CI->db->get("flexible_hours");
								$row_flexible_working_days = $sql_flexible_working_days->row();
	
								if($row_flexible_working_days){
									$total_hours_for_the_day = $row_flexible_working_days->total_hours_for_the_day;
									$end_time = date("H:i:s",strtotime($flexible_work_end) + 60 * 60 * $total_hours_for_the_day);
										
									$start_time = $row_flexible_working_days->latest_time_in_allowed;
									$undertime_min =  $end_time;
									$working_hours = $row_flexible_working_days->total_hours_for_the_day;
								}else{
									$undertime_min =  "00:00:00";
								}
							}else{
								$undertime_min =  "00:00:00";
							}
								
						}else{
							// workshift working days
							$ws_where = array(
									"payroll_group_id"=>$payroll_group_id,
									"company_id"=>$comp_id
							);
							$CI->db->where($ws_where);
							$sql_workshift = $CI->db->get("split_schedule");
							$row_workshift = $sql_workshift->row();
								
							if($row_workshift){
								$undertime_min =  $row_workshift->end_time;
								$working_hours = $row_workshift->working_hours;
							}else{
								$undertime_min =  "00:00:00";
							}
						}
					}
				}else{
					$undertime_min = "00:00:00";
					$working_hours = 0;
				}
	
				$date_timeout_sec = date("H:i:s",strtotime($date_timeout));
	
				if($date_timein == "" && $date_timeout == "" && $lunch_out == "" && $lunch_in == ""){
					return 0;
				}
	
				if($start_time == ""){
					return 0;
				}
	
				// check PM and AM
				$check_endtime = date("A",strtotime($undertime_min));
				$check_timout = date("A",strtotime($date_timeout_sec));
	
				// callcenter trapping
				if($check_endtime == "AM" && $check_timout == "PM" && $check_timein == "PM"){
					$time_out_date = date("Y-m-d",strtotime($date_timeout_sec."+1 day"));
					$new_undertime_min = $time_out_date." ".$undertime_min;
					$under_min_val = (strtotime($new_undertime_min) - strtotime($date_timeout)) / 60;
				}else{
					if(strtotime($date_timeout_sec) <= strtotime($undertime_min)){
						$check_tardiness_import = check_tardiness_import($emp_id,$comp_id,$date_timein);
						if($check_tardiness_import == 0){
							if(strtotime($undertime_min) <= strtotime($date_timeout_sec)){
								$under_min_val = (strtotime($undertime_min) - strtotime($date_timeout_sec)) / 60;
							}else{
								///$breaktime_hours = add_breaktime($comp_id,$payroll_group_id,$date_timein);
								$breaktime_hours = $break;
								$working_hours = ($working_hours * 60) + $breaktime_hours;
								$date_timin_sec = date('H:i:s', strtotime($date_timein));
	
								$new_date_timein = (strtotime($start_time) <= strtotime($date_timin_sec)) ? $date_timein : $start_time ;
								$new_timeout_sec = date('H:i:s', strtotime($new_date_timein . ' + '.$working_hours.' minute'));
								$under_min_val = (strtotime($new_timeout_sec) - strtotime($date_timeout_sec)) / 60;
							}
						}else{
							$under_min_val = (strtotime($undertime_min) - strtotime($date_timeout_sec)) / 60;
						}
					}else{
						$under_min_val = 0;
					}
				}
			}
				
			// check total hours for workday
			$get_hours_worked_to_mins = get_hours_worked($day, $emp_id) * 60; //xxd
	
			if($get_hours_worked_to_mins < $under_min_val) return 0;
	
				
			return ($under_min_val < 0) ? 0 : $under_min_val ;
		}
	}
	
	function generate_token($length) {
		$key = '';
		$keys = array_merge(range(0, 9), range('a', 'z'));
		for ($i = 0; $i < $length; $i++) {
			$key .= $keys[array_rand($keys)];
		}
		return $key;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $comp_id
	 * @param unknown_type $payroll_group_id
	 */
	function get_regular_payrate($comp_id,$payroll_group_id){
		$CI =& get_instance();
		$where = array(
			"company_id"=>$comp_id,
			"hour_type_name"=>"Regular Day"
		);
		$CI->db->where($where);
		$sql = $CI->db->get("hours_type");
		
		if($sql->num_rows() > 0){
			$row = $sql->row();
			return $row->pay_rate;
		}else{
			return 0;
		}
	}

	/**
	*	IREAD CSV
	*	@param file $csv_file path
	*	@return array
	*/
	function iread_csv($csv_file){
		$file_path =  $csv_file;
		$arr=array();
		$c = array();
		$row = 0;
		if(file_exists($csv_file)) {
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
		}else{
			return false;
		}
	}
	
	function iread_csv_parents($csv_file){
		$file_path =  $csv_file;
		$arr=array();
		$c = array();
		$row = 0;
		if(file_exists($csv_file)) {
			if (($handle = fopen($file_path, "r")) !== FALSE) {
				while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
					$num = count($data);
					for ($c = 0; $c < $num; $c++) {
						if($row ==0) {
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
		}else{
			return false;
		}
	}
	
	/**
	 * For Leave Breaktime
	 * @param unknown_type $emp
	 * @param unknown_type $comp_id
	 */
	function for_leave_breaktime($emp_id,$comp_id,$workday){
		$CI =& get_instance();
		$where = array(
			"emp_id"=>$emp_id,
			"company_id"=>$comp_id
		);
		$CI->db->where($where);
		$sql = $CI->db->get("employee_payroll_information");
		$row_sql = $sql->row();
		$payroll_group_id = $row_sql->payroll_group_id;
		
		$where_workday = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$comp_id,
			"workday"=>$workday
		);
		$CI->db->where($where_workday);
		$sql_workday = $CI->db->get("break_time");
		$row_workday = $sql_workday->row();
		
		// for uniform working days and workshift
		if($sql_workday->num_rows() > 0){
			return $row_workday->working_hours;
		}
	}
	
	/**
	 * For Leave Breaktime Start Time
	 * @param unknown_type $emp
	 * @param unknown_type $comp_id
	 */
	function for_leave_breaktime_start_time($emp_id,$comp_id,$workday){
		$CI =& get_instance();
		$where = array(
			"emp_id"=>$emp_id,
			"company_id"=>$comp_id
		);
		$CI->db->where($where);
		$sql = $CI->db->get("employee_payroll_information");
		$row_sql = $sql->row();
		$payroll_group_id = $row_sql->payroll_group_id;
		
		// for uniform working days and workshift
		$where_workday = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$comp_id,
			"workday"=>$workday
		);
		$CI->db->where($where_workday);
		$sql_workday = $CI->db->get("break_time");
		$row_workday = $sql_workday->row();
		
		if($sql_workday->num_rows() > 0){
			return $row_workday->start_time;
		}else{
			// for flexible
			return "12:00:00";
		}
		
	}
	
	/**
	 * For Leave Breaktime End Time
	 * @param unknown_type $emp
	 * @param unknown_type $comp_id
	 */
	function for_leave_breaktime_end_time($emp_id,$comp_id,$workday){
		$CI =& get_instance();
		$where = array(
			"emp_id"=>$emp_id,
			"company_id"=>$comp_id
		);
		$CI->db->where($where);
		$sql = $CI->db->get("employee_payroll_information");
		$row_sql = $sql->row();
		$payroll_group_id = $row_sql->payroll_group_id;
		
		// for uniform working days and workshift
		$where_workday = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$comp_id,
			"workday"=>$workday
		);
		$CI->db->where($where_workday);
		$sql_workday = $CI->db->get("break_time");
		$row_workday = $sql_workday->row();
		
		if($sql_workday->num_rows() > 0){
			return $row_workday->end_time;
		}
		
		// for flexible
		$where_workday = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$comp_id
		);
		$CI->db->where($where_workday);
		$sql_workday = $CI->db->get("break_time");
		$row_workday = $sql_workday->row();
		
		if($sql_workday->num_rows() > 0){
			return $row_workday->end_time;
		}
	}
	
	/**
	 * For Leave Hoursworked
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 */
	function for_leave_hoursworked($emp_id,$comp_id,$workday){
		$CI =& get_instance();
		$where = array(
			"emp_id"=>$emp_id,
			"company_id"=>$comp_id
		);
		$CI->db->where($where);
		$sql = $CI->db->get("employee_payroll_information");
		$row_sql = $sql->row();
		$payroll_group_id = $row_sql->payroll_group_id;
		
		// for uniform working days and workshift
		$where_hw = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$comp_id,
			"days_of_work"=>$workday
		);
		
		$CI->db->where($where_hw);
		$sql_hw = $CI->db->get("uniform_working_day");
		$row_hw = $sql_hw->row();
		
		if($sql_hw->num_rows() > 0){
			return $row_hw->working_hours;
		}
		
		// for workshift
		$where_w = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$comp_id
		);
		
		$CI->db->where($where_w);
		$sql_w = $CI->db->get("workshift");
		$row_w = $sql_w->row();
		
		if($sql_w->num_rows() > 0){
			return $row_w->working_hours;
		}
		
		// for flexible
		$where_f = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$comp_id
		);
		
		$CI->db->where($where_f);
		$sql_f = $CI->db->get("flexible_hours");
		$row_f = $sql_f->row();
		
		if($sql_f->num_rows() > 0){
			return $row_f->total_hours_for_the_day;
		}
	}
	
	/**
	 * For Leave Hoursworked work start time
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 */
	function for_leave_hoursworked_work_start_time($emp_id,$comp_id){
		$CI =& get_instance();
		$where = array(
			"emp_id"=>$emp_id,
			"company_id"=>$comp_id
		);
		$CI->db->where($where);
		$sql = $CI->db->get("employee_payroll_information");
		$row_sql = $sql->row();
		$payroll_group_id = $row_sql->payroll_group_id;
		
		$where_hw_settings = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$comp_id,
		);
		
		$CI->db->where($where_hw_settings);
		$sql_hw_settings = $CI->db->get("uniform_working_day_settings");
		$row_hw_settings = $sql_hw_settings->row();
		
		// for uniform working days settings
		if($sql_hw_settings->num_rows() > 0){
			if($row_hw_settings->latest_time_in_allowed != "" || $row_hw_settings->latest_time_in_allowed != NULL){
				return $row_hw_settings->latest_time_in_allowed;
			}else{
				// for uniform working days and workshift
				$where_hw = array(
					"payroll_group_id"=>$payroll_group_id,
					"company_id"=>$comp_id,
				);
				
				$CI->db->where($where_hw);
				$sql_hw = $CI->db->get("uniform_working_day");
				$row_hw = $sql_hw->row();
				
				// for uniform working days and workshift
				if($sql_hw->num_rows() > 0){
					return $row_hw->work_start_time;
				}else{
					$CI->db->where($where_hw);
					$ws_q = $CI->db->get("workshift");
					$ws_r = $ws_q->row();
					return ($ws_r) ? $ws_r->start_time : FALSE ;
				}
			}
				
		}else{
			// for uniform working days and workshift
			$where_hw = array(
				"payroll_group_id"=>$payroll_group_id,
				"company_id"=>$comp_id,
			);
			
			$CI->db->where($where_hw);
			$sql_hw = $CI->db->get("uniform_working_day");
			$row_hw = $sql_hw->row();
			
			// for uniform working days and workshift
			if($sql_hw->num_rows() > 0){
				return $row_hw->work_start_time;
			}else{
				$CI->db->where($where_hw);
				$ws_q = $CI->db->get("workshift");
				$ws_r = $ws_q->row();
				return ($ws_r) ? $ws_r->start_time : FALSE ;
			}
		}
	}
	
	/**
	 * For Leave Hoursworked work end time
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 */
	function for_leave_hoursworked_work_end_time($emp_id,$comp_id){
		$CI =& get_instance();
		$where = array(
			"emp_id"=>$emp_id,
			"company_id"=>$comp_id
		);
		$CI->db->where($where);
		$sql = $CI->db->get("employee_payroll_information");
		$row_sql = $sql->row();
		$payroll_group_id = $row_sql->payroll_group_id;
		
		$where_hw = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$comp_id,
		);
		
		$CI->db->where($where_hw);
		$sql_hw = $CI->db->get("uniform_working_day");
		$row_hw = $sql_hw->row();
		
		// for uniform working days and workshift
		if($sql_hw->num_rows() > 0){
			return $row_hw->work_end_time;
		}
	}
			
	function icsv_delete_all($file_path){
		/*$unlink_csv = glob($file_path."*.csv");
		if($unlink_csv) {
			foreach($unlink_csv as $key):
				unlink($key);
			endforeach;
			return true;
		} else {
			return false;
		}*/
	}
	
	/**
	 * Calculate String
	 * @param unknown_type $mathString
	 */
	function calculate_string( $mathString )    {
	      $mathString = trim($mathString);     // trim white spaces
	      $mathString = preg_replace ('[^0-9\+-\*\/\(\) ]', '', $mathString);    // remove any non-numbers chars; exception for math operators
	   
	      $compute = create_function("", "return (" . $mathString . ");" );
	      return 0 + $compute();
	  }
	  

	function time_to_decimal($time) {
		$timeArr = explode(':', $time);
		$decTime = ($timeArr[0]*60) + ($timeArr[1]) + ($timeArr[2]/60);
		return $decTime;
	}
	
	function extension_checker($file){
		return end(explode(".",$file));
	}
	
	function array_pagination($map_array,$start,$qty,$per_page,$url,$uri_segment = 5,$image_array = FALSE){
		$CI =& get_instance();
		$qty = intval($qty);
		$start = $start;
		$valid_extension = array();
			if($image_array == true) {
				$extension_allowed = array("png","jpg","jpeg","gif"); // these are the allowable extensions only
				if($map_array){
					foreach($map_array as $key=>$val):
						if(extension_checker($val)){
							$check = strtolower(extension_checker($val)); // make str tolower
							if(in_array($check,$extension_allowed)){ // check valids 
								$valid_extension[] = $val;
							}
						}
					endforeach;
				}
			}
			
		if(!$start) $start = 0; 
			$data['gal']	= array_slice($valid_extension,$start,$qty); // these will slice each value in array
			$config = array(
						"base_url"			=> $url,
						"total_rows"		=> count($valid_extension),
						"per_page"		=> $per_page,
						"uri_segment"	=> $uri_segment,
						"first_link"			=> "First",
						"last_link"			=> "Last",
						"prev_link"			=> "Prev",
						"next_link"			=> "Next",
						"full_tag_open"	=> "<ul id=\"pagination\">",
						"full_tag_close"	=> "</ul>",
						"first_tag_open"	=> "<li>",
						"first_tag_close"	=> "</li>",
						"last_tag_open"	=> "<li>",
						"last_tag_close"	=> "</li>",
						"prev_tag_open"	=> "<li class=\"prev\">",
						"prev_tag_close"	=> "</li>",
						"next_tag_open"	=> "<li class=\"next\">",
						"next_tag_close"	=> "</li>",
						"cur_tag_open"	=> "<li class=\"active\"><a>",
						"cur_tag_close"	=> "</a></li>",
						"num_tag_open"	=> "<li>",
						"num_tag_close" => "</li>"
		);		
		$CI->pagination->initialize($config); // use codeigniter extension
		$data['pagi'] = $CI->pagination->create_links(); 
		return $data;
	}
	
	/**
	 * Get Employee Fullname
	 * @param unknown_type $emp_id
	 */
	function emp_fname($emp_id){
		$CI =& get_instance();
		$w = array(
			"emp_id"=>$emp_id
		);
		$CI->edb->where($w);
		$q = $CI->edb->get("employee");
		$r = $q->row();
		return ($q->num_rows() > 0) ? ucwords($r->first_name)." ".ucwords($r->last_name): FALSE ;
	}
	
	/**
	 * Get Hours Worked for workday
	 * @param unknown_type $workday
	 * @param unknown_type $emp_id
	 */
	function get_hours_worked($workday, $emp_id){
		$CI =& get_instance();
		
		$mininum_wage_rate = "";
		$working_hours = "";
		$no_of_days = "";
		$regular_hourly_rate = "";
		
		// check if workday is workshit schedule
		$CI->db->where("eti.emp_id",$emp_id);
		$CI->db->join("split_schedule AS w","w.company_id = w.company_id","LEFT");
	//	$CI->db->group_by("w.total_working_days_per_year");
		$sql_workshift = $CI->db->get("employee_time_in AS eti");
		$row_workshift = $sql_workshift->row();
		
		/* $sql_workshift = $CI->db->query("
			SELECT *
			FROM `employee_time_in` eti
			LEFT JOIN workshift_settings ws ON eti.comp_id = ws.company_id
			LEFT JOIN workshift w ON ws.company_id = w.company_id
			WHERE eti.emp_id = '{$emp_id}'
			GROUP BY ws.total_working_days_per_year
		"); */
		
		if($row_workshift){
			if($row_workshift->working_hours != ""){
				// for workshift
				$working_hours = $row_workshift->working_hours;
			}
		}
		
		// ==========================================
		
		// check if workday is flexible hours
		$CI->db->where("eti.emp_id",$emp_id);
		$CI->db->join("flexible_hours AS fh","eti.comp_id = fh.company_id","LEFT");
		$CI->db->group_by("fh.total_days_per_year");
		$sql_flexible_hours = $CI->db->get("employee_time_in AS eti");
		$row_flexible_hours = $sql_flexible_hours->row();
		
		/* $sql_flexible_hours = $CI->db->query("
			SELECT *
			FROM `employee_time_in` eti
			LEFT JOIN flexible_hours fh ON eti.comp_id = fh.company_id
			WHERE eti.emp_id = '{$emp_id}'
			GROUP BY fh.total_days_per_year
		"); */
		
		if($row_flexible_hours){
			if($row_flexible_hours->total_days_per_year != null){
				// for flexible hours
				$no_of_days = $row_flexible_hours->total_days_per_year / 12; // 12 = months
				$working_hours = $row_flexible_hours->total_hours_for_the_day;
			}
		}
		
		if($working_hours != "" && $no_of_days != ""){
			// get mininum wage rate for employee
			$w = array(
				"emp_id"=>$emp_id,
				"status"=>"Active"
			);
			$CI->edb->where($w);
			$sql_minimum_wage_rate = $CI->edb->get("basic_pay_adjustment");
			
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
		$ww = array(
			"eti.emp_id"=>$emp_id,
			"rs.days_of_work"=>$workday_val
		); 
		$CI->db->where($ww);
		
		$CI->db->join("work_schedule AS uwd","uwd.comp_id = eti.comp_id","LEFT");
		$CI->db->join("regular_schedule AS rs","rs.work_schedule_id = uwd.work_schedule_id","LEFT");
		$CI->db->group_by("rs.days_of_work");
		$sql_uniform_working_days = $CI->db->get("employee_time_in AS eti");
		$row_uniform_working_days = $sql_uniform_working_days->row();
		
		/* $sql_uniform_working_days = $CI->db->query("
			SELECT *
			FROM `employee_time_in` eti
			LEFT JOIN uniform_working_day_settings uwds ON eti.comp_id = uwds.company_id
			LEFT JOIN uniform_working_day uwd ON uwds.company_id = uwd.company_id
			WHERE eti.emp_id = '{$emp_id}'
			AND uwd.working_day = '{$workday_val}'
			GROUP BY uwd.working_day
		"); */
		
		// get number of days
		if($row_uniform_working_days){
			if( $row_uniform_working_days->total_work_hours != ""){
				// for uniform working day
				$working_hours = $row_uniform_working_days->total_work_hours;
			}
		}
		
		return $working_hours;
	}
	
	/**
	 * To be used as data of owner for limited user usage like (300 employees only)
	 * @return object
	 */
	function iowner_information(){
		$CI =& get_instance();
		$psa_id =  $CI->session->userdata('psa_id');
		if($psa_id){
			$where = array(
				'payroll_system_account_id' => $CI->session->userdata('psa_id'),
				'status'	=> 'Active'
			);
			$CI->edb->where($where);
			$query = $CI->edb->get('payroll_system_account');
			$row = $query->row();
			return $row;
		}else{
			return false;
		}
	}
	
	/**
	 * Count owner employees registered
	 * @return integer
	 */
	function iowner_limited_employees(){
		$CI =& get_instance();
		$size = iowner_information();
		return $size ? $size->no_of_employees : 0;
	}
	
	function iowner_employeescount(){
		$CI =& get_instance();
		if($CI->session->userdata('psa_id')){
			$where = array(
				'a.payroll_system_account_id'=>$CI->session->userdata('psa_id'),
				'a.deleted' => '0',
				'e.status'=>'Active',
				'e.deleted'=>'0',
				'a.user_type_id'=>'5',
				'ac.deleted'=>'0'
			);
			$CI->edb->where($where);
			
			$CI->edb->join('employee AS e','e.account_id = a.account_id','inner');
			$CI->edb->join('assigned_company AS ac','ac.company_id=e.company_id','INNER');
			$q = $CI->edb->get('accounts AS a');
			$row = $q->num_rows();
			return $row;
		}else{
			return 0;
		}
	}
	
	function emp_cash_advance_menu(){
		$CI =& get_instance();
		$company = whose_company();
		if($company){
			$where = array(
				"enable_disable" => "Enable",
				"company_id" => $company->company_id,
				"status" => "Active"
			);
			$CI->db->where($where);
			$query = $CI->db->get("cash_advance_settings");
			$row = $query->row();
			
			return ($row) ? true : false;
		}
		
	}
	
	/**
	 * Function to get the client IP address
	 */
	function get_ip() {
	   /* $ipaddress = '';
	    if (getenv('HTTP_CLIENT_IP'))
	        $ipaddress = getenv('HTTP_CLIENT_IP');
	    else if(getenv('HTTP_X_FORWARDED_FOR'))
	        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
	    else if(getenv('HTTP_X_FORWARDED'))
	        $ipaddress = getenv('HTTP_X_FORWARDED');
	    else if(getenv('HTTP_FORWARDED_FOR'))
	        $ipaddress = getenv('HTTP_FORWARDED_FOR');
	    else if(getenv('HTTP_FORWARDED'))
	       $ipaddress = getenv('HTTP_FORWARDED');
	    else if(getenv('REMOTE_ADDR'))
	        $ipaddress = getenv('REMOTE_ADDR');
	    else
	        $ipaddress = 'UNKNOWN';*/
	    if(!empty($_SERVER['REMOTE_ADDR'])){
	    	$ipaddress = $_SERVER['REMOTE_ADDR'];
	    	
		}elseif(!empty($_SERVER['HTTP_CLIENT_IP'])){
	    	$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	    	
	    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){	    	
	    	$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	    	
	    }else{
	    	$file = file_get_contents('http://ip6.me/');
	    	
	    	// Trim IP based on HTML formatting
	    	$pos = strpos( $file, '+3' ) + 3;
	    	$ip = substr( $file, $pos, strlen( $file ) );
	    	
	    	// Trim IP based on HTML formatting
	    	$pos = strpos( $ip, '</' );
	    	$ip = substr( $ip, 0, $pos );
	    	
	    	$ipaddress = $ip;
	    	
	    }
	     

	    return $ipaddress;
	}
	
	
	/**
	 * Function to get the client IP address
	 */
	function get_ip_new($type = 0) {
		
 						if(!empty($_SERVER['REMOTE_ADDR']) && $type ==1){
 							$ipaddress = $_SERVER['REMOTE_ADDR'];
 						}elseif(!empty($_SERVER['HTTP_CLIENT_IP']) && $type ==2){
 							$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
 						}elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $type ==3){
 							$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
 						}else{
 							$file = file_get_contents('http://ip6.me/');

 							// Trim IP based on HTML formatting
 							$pos = strpos( $file, '+3' ) + 3;
 							$ip = substr( $file, $pos, strlen( $file ) );

 							// Trim IP based on HTML formatting
 							$pos = strpos( $ip, '</' );
 							$ip = substr( $ip, 0, $pos );

 							$ipaddress = $ip;
 						}


 						return $ipaddress;
	}
	
	function get_ip_company($company_id){
		$CI =& get_instance();
		//$company_id = $CI->account_model->check_company_id($emp_id);
	
		$w = array(
				"company_id"=>$company_id,
				"category" => 0
		);
		$CI->db->where($w);
		$q = $CI->db->get("employee_ip_address");
		$r = $q->result();
	
		
		if($r){
			$count = 0;
			$count2 = 0;
			foreach($r as $d){
				
				$ip_get = (!empty($_SERVER['REMOTE_ADDR'])) ? explode('.',$_SERVER['REMOTE_ADDR']) : "00.00";
				$ip_no = explode('.',$d->ip_address);
				if($ip_no[0] == $ip_get[0] && $ip_no[1]==$ip_get[1]){
						
					$count++;
				}else{
					$file = file_get_contents('http://ip6.me/');
				
					// Trim IP based on HTML formatting
					$pos = strpos( $file, '+3' ) + 3;
					$ip = substr( $file, $pos, strlen( $file ) );
				
					// Trim IP based on HTML formatting
					$pos = strpos( $ip, '</' );
					$ip = substr( $ip, 0, $pos );
				
					$ipaddress = $ip;
					$ip_get = explode('.',$ipaddress);
					if($ip_no[0] == $ip_get[0] && $ip_no[1]==$ip_get[1]){
						$count2++;
					}
				}
			}
			
			foreach($r as $row){
					
				$ip_get = (!empty($_SERVER['HTTP_CLIENT_IP'])) ? explode('.',$_SERVER['HTTP_CLIENT_IP']) : "00.00" ;
				$ip_no = explode('.',$row->ip_address);
				if($ip_no[0] == $ip_get[0] && $ip_no[1]==$ip_get[1] )
					return true;
				else{
					$ip_get = (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) ? explode('.',$_SERVER['HTTP_X_FORWARDED_FOR']) : "00.00";
					if($ip_no[0] == $ip_get[0] && $ip_no[1]==$ip_get[1]){
						return true;
					}else{
	
						$ip_get = (!empty($_SERVER['REMOTE_ADDR'])) ? explode('.',$_SERVER['REMOTE_ADDR']) : "00.00";
						if(($ip_no[0] == $ip_get[0] && $ip_no[1]==$ip_get[1]) || $count > $count2){
							
							return true;
						}else{
							$file = file_get_contents('http://ip6.me/');
	    	
					    	// Trim IP based on HTML formatting
					    	$pos = strpos( $file, '+3' ) + 3;
					    	$ip = substr( $file, $pos, strlen( $file ) );
					    	
					    	// Trim IP based on HTML formatting
					    	$pos = strpos( $ip, '</' );
					    	$ip = substr( $ip, 0, $pos );
					    	
					    	$ipaddress = $ip;
					    	$ip_get = explode('.',$ipaddress);
							if(($ip_no[0] == $ip_get[0] && $ip_no[1]==$ip_get[1]) || $count2 > $count){
								return true;
							}
						}
					}
				}
			}
				
			return false;
		}
	}
	
	
	function get_ip_employee_old($company_id){
		$CI =& get_instance();
		//$company_id = $CI->account_model->check_company_id($emp_id);
	
		$w = array(
				"company_id"=>$company_id,
				"category" => 2
		);
		$CI->db->where($w);
		$q = $CI->db->get("employee_ip_address");
		$r = $q->result();
	
		
		if($r){
			$count = 0;
			$count2 = 0;
			foreach($r as $d){
				
				$ip_get = explode('.',$_SERVER['REMOTE_ADDR']);
				$ip_no = explode('.',$d->ip_address);
				if($ip_no[0] == $ip_get[0] && $ip_no[1]==$ip_get[1]){
						
					$count++;
				}else{
					$file = file_get_contents('http://ip6.me/');
				
					// Trim IP based on HTML formatting
					$pos = strpos( $file, '+3' ) + 3;
					$ip = substr( $file, $pos, strlen( $file ) );
				
					// Trim IP based on HTML formatting
					$pos = strpos( $ip, '</' );
					$ip = substr( $ip, 0, $pos );
				
					$ipaddress = $ip;
					$ip_get = explode('.',$ipaddress);
					if($ip_no[0] == $ip_get[0] && $ip_no[1]==$ip_get[1]){
						$count2++;
					}
				}
			}
			
			foreach($r as $row){
					
				$ip_get = (!empty($_SERVER['HTTP_CLIENT_IP'])) ? explode('.',$_SERVER['HTTP_CLIENT_IP']) : "00.00" ;
				$ip_no = explode('.',$row->ip_address);
				if($ip_no[0] == $ip_get[0] && $ip_no[1]==$ip_get[1] )
					return true;
				else{
					$ip_get = (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) ? explode('.',$_SERVER['HTTP_X_FORWARDED_FOR']) : "00.00";
					if($ip_no[0] == $ip_get[0] && $ip_no[1]==$ip_get[1]){
						return true;
					}else{
	
						$ip_get = (!empty($_SERVER['REMOTE_ADDR'])) ? explode('.',$_SERVER['REMOTE_ADDR']) : "00.00";
						if(($ip_no[0] == $ip_get[0] && $ip_no[1]==$ip_get[1]) || $count > $count2){
							
							return true;
						}else{
							$file = file_get_contents('http://ip6.me/');
	    	
					    	// Trim IP based on HTML formatting
					    	$pos = strpos( $file, '+3' ) + 3;
					    	$ip = substr( $file, $pos, strlen( $file ) );
					    	
					    	// Trim IP based on HTML formatting
					    	$pos = strpos( $ip, '</' );
					    	$ip = substr( $ip, 0, $pos );
					    	
					    	$ipaddress = $ip;
					    	$ip_get = explode('.',$ipaddress);
							if(($ip_no[0] == $ip_get[0] && $ip_no[1]==$ip_get[1]) || $count2 > $count){
								return true;
							}
						}
					}
				}
			}
				
			return false;
		}
	}
	
	/**
	 * Konsum Encryption Key
	 */
	function konsum_key(){
		#$str = "K0ns4mT3ch-2014";
		$str = "konsum";
		if($_SERVER['SERVER_NAME'] == 'ashima.ph'){
			$str = "K0ns4mT3ch_2015";
		}else if(get_domain(base_url()) == 'ashima.ph'){
			$str = "K0ns4mT3ch_2015";
		}
		return $str;
	}
	
	/**
	 * CHECK OUR DOMAIN
	 * @param unknown $url
	 * @return unknown|boolean
	 */
	function get_domain($url)
	{
		$urlobj=parse_url($url);
		$domain=$urlobj['host'];
		if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
			return $regs['domain'];
		}
		return false;
	}
	
	
	function ifb_authenticate($account_id,$company_id){
		$CI =& get_instance();
		$data['page_title'] = "Facebook Chat";
		require_once APPPATH.'third_party/facebook.php';
		### FB ACCOUNT 	
			$where_fb = array(
				'account_id'=>$account_id,
				'company_id'=> $company_id,
				'status'=> 'Active'
			);
			$CI->edb->where($where_fb);
			$q = $CI->edb->get('employee_facebook_accounts');
			$has_facebook = $q->row();
		## END FB ACCOUNT
			$where_social  = array(
				'account_id'=>$account_id
			);
			$CI->edb->where($where_social);
			$q2 = $CI->edb->get('social_media_accounts');
			$account = $q2->row();	
		# ACCUNT
		
		$data['check_user'] = "";
		$data['facebook'] = "";
		$data['error'] = "";
		$data['facebook_not_account_holder'] = FALSE;
		$data['facebook_account_holder'] = FALSE;
		$data['error'] = array();
		$data['groups'] = false;

		if($has_facebook) {
			$data['facebook'] = new Facebook(array(
					'appId' => $has_facebook->app_id,#'410175272498852',#
					'secret' =>$has_facebook->app_secret #'93ade03f3e846287c28580aedcd45c17'#$has_facebook->secret
			));
	
			try {	
				$data['check_user'] = $data['facebook']->getUser();
				$data['facebook_account_holder'] = $data['facebook']->api('/me');
				$api = $data['facebook']->api('me/groups');
				$data['groups']= $api['data'];
				#p($api);
				
			} catch (FacebookApiException $e) {
				
			}
			if($data['facebook_account_holder'] == FALSE){
				#p($data['facebook_account_holder']);
				# KONG WALAY NI LOGIN SA FB SO MO LgoIN NATO E REDIRECT LANG PARA CHOI
				$loginUrl = $data['facebook']->getLoginUrl(array(
				//	'scope' => 'user_groups,manage_pages,publish_actions,publish_stream,email'
				'scope' => 'user_groups,manage_pages,publish_actions,email'	
				));
				redirect($loginUrl);
			}else{
				# KONG NAKASULOD SIYA EVAlidate sa nato kong tagiya bani
				$email_fb = isset($data['facebook_account_holder']['email']) ? $data['facebook_account_holder']['email'] : '';
				if($email_fb !="") {
					if($account == false){
						$data['error'] = array(
								'Please setup your Facebook Email'#.$has_facebook->facebook_email,
						);
					}else{
						if($email_fb == $account->facebook){ #$has_facebook->facebook_email
							
						}else{
							$data['error'] = array(
								'Your Email account is invalid Please do logout and Login to your Email using '#.$has_facebook->facebook_email,
							);
						}
					}
				}else{
					$data['error'] = array(
							'Email Address is not for public'#.$has_facebook->facebook_email,
						);
				}
			}

			$result = true;
			if($data['groups'] == false){
				$result = false;
			}

			if(count($data['error'])>0){
				$result = false;	
			}	
			
			$CI->session->set_userdata('fb_login','1');
			return array('result'=>$result,'error'=>$data['error'],'all_groups'=>$data['groups']);
		}
	}
	
	function ifb_postgroup($account_id,$company_id,$submit=false,$group_id,$message){
		$CI =& get_instance();
		$data['page_title'] = "Facebook Chat";
		require_once APPPATH.'third_party/facebook.php';
		### FB ACCOUNT 	
			$where_fb = array(
				'account_id'=>$account_id,
				'company_id'=> $company_id,
				'status'=> 'Active'
			);
			$CI->edb->where($where_fb);
			$q = $CI->edb->get('employee_facebook_accounts');
			$has_facebook = $q->row();
		## END FB ACCOUNT
			$where_social  = array(
				'account_id'=>$account_id
			);
			$CI->edb->where($where_social);
			$q2 = $CI->edb->get('social_media_accounts');
			$account = $q2->row();	
		# ACCUNT
		
		$data['check_user'] = "";
		$data['facebook'] = "";
		$data['error'] = "";
		$data['facebook_not_account_holder'] = FALSE;
		$data['facebook_account_holder'] = FALSE;
		$data['error'] = array();
		$data['groups'] = false;
		$loginUrl = "";
		if($has_facebook) {
			$data['facebook'] = new Facebook(array(
					'appId' => $has_facebook->app_id,#'410175272498852',#
					'secret' =>$has_facebook->app_secret #'93ade03f3e846287c28580aedcd45c17'#$has_facebook->secret
			));
	
			try {	
				$data['check_user'] = $data['facebook']->getUser();
				$data['facebook_account_holder'] = $data['facebook']->api('/me');
				$api = $data['facebook']->api('me/groups');
				$data['groups']= $api['data'];
				#p($api);
				
			} catch (FacebookApiException $e) {
				
			}
			if($data['facebook_account_holder'] == FALSE){
				#p($data['facebook_account_holder']);
				# KONG WALAY NI LOGIN SA FB SO MO LgoIN NATO E REDIRECT LANG PARA CHOI
				$loginUrl = $data['facebook']->getLoginUrl(array(
					//'scope' => 'user_groups,manage_pages,publish_actions,publish_stream,email'
					'scope' => 'user_groups,manage_pages,publish_actions,email'		
				));
				
			}else{
				# KONG NAKASULOD SIYA EVAlidate sa nato kong tagiya bani
				$email_fb = isset($data['facebook_account_holder']['email']) ? $data['facebook_account_holder']['email'] : '';
				if($email_fb !="") {
					if($account == false){
						$data['error'] = array(
								'Please setup your Facebook Email'#.$has_facebook->facebook_email,
						);
					}else{
						if($email_fb == $account->facebook){ #$has_facebook->facebook_email
							
						}else{
							$data['error'] = array(
								'Your Email account is invalid Please do logout and Login to your Email using '#.$has_facebook->facebook_email,
							);
						}
					}
				}else{
					$data['error'] = array(
							'Email Address is not for public'#.$has_facebook->facebook_email,
					);
				}
			}
			$ret_groupres = '';
			if($data['groups']){
				if($submit==true){
					$group_id = $group_id;
					$message = $message;	
					$ret_groupres =  $data['facebook']->api($group_id.'/feed','POST',array("message" => $message));		
				}
			}	
			
			$result = true;
			if($data['groups'] == false){
				$result = false;
			}
			if($loginUrl ==''){
				
			}else{
				$data['error'] = "Login Facebook Needed";
				$result = false;
			}

			if(count($data['error'])>0){
				$result = false;	
			}	
			return array('result'=>$result,'error'=>$data['error'],'group'=>$ret_groupres,'all_groups'=>$data['groups'],'redirect_first'=>$loginUrl);
		}
	}
	
	 /**
	  * SETS validation error array
	  * Enter description here ...
	  * @param string $array
	  * @example validation_errors_array(validation_errors(" ",","))
	  */
	 function validation_errors_array($array){
	  $error = $array;
	  $clean = str_replace("\n","",$error);
	  $err = explode(",",$clean);
	  $error_array = array();
	   for($x = 0; $x < count($err) - 1; $x++){
	     array_push($error_array,trim($err[$x]));
	  }
	   return $error_array;
	 }
		
	/**
	 * SAmok kayo ang input radio na yes no gihimoan lng nako kay para wala nay hasol
	 * Enter description here ...
	 * @param unknown_type $val
	 */
	 function iyesno($val){
	 	return ($val) ? 'yes' : 'no';
	 }
	 
	 function owner_created($account_id){
	 	$CI =& get_instance();
	 	if(is_numeric($account_id)){
	 		$where = array(
	 			'account_id'=>$account_id,
	 			'deleted'=>'0'
	 		);
	 		$CI->db->where($where);
	 		$q = $CI->edb->get('accounts');
	 		$r = $q->row();
	 		if($r){
	 			switch($r->user_type_id){
	 				case "1": # ADmin
	 					
	 				break;
	 				case "2": #OWNER
	 					$where_owner = array(
	 						'account_id'=>$account_id
	 					);
	 					$CI->edb->where($where_owner);
	 					$qy = $CI->edb->get('company_owner');
	 					$rw = $qy->row();
	 					return $rw;
	 				break;
	 				case "3": #hr
	 					$where_emp = array(
	 						'account_id'=>$account_id
	 					);
	 					$CI->edb->where($where_emp);
	 					$qy = $CI->edb->get('employee');
	 					$rw = $qy->row();
	 					return $rw;
	 				break;
	 				case "4": #accountant
	 				
	 				break;
	 				case "5": #employee
	 					
	 				break;
	 			}
	 		}
	 		
	 	}else{
	 		return false;
	 	}
	 }
	 
	 
	 /**
	  * Count employees on the particular company
	  * Enter description here ...
	  * @param int $company_id
	  * @param int
	  */
	function icount_employees($company_id){
		$CI =& get_instance();
		$where = array(
			'e.company_id'=>$company_id,
			'e.status'=>'Active',
			'a.deleted'=>'0',
			'a.user_type_id'=>'5'
		);
		$CI->edb->where($where);
		$CI->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
		$q = $CI->edb->get('employee AS e');
		$row = $q->num_rows();
		return $row;
	}
	
	/**
	 * COUNT NATO ANG EMPLOYEES INACTIVE
	 * Enter description here ...
	 * @param int $company_id
	 */
	function icount_employees_noinactive($company_id){
		$CI =& get_instance();
		$where = array(
			'e.company_id'=>$company_id,
			'e.status'=>'Active',
			'epi.employee_status'=>'Active',
			'epi.status'=>'Active',
			'a.deleted'=>'0',
			'a.user_type_id'=>'5'
		);
		$CI->edb->where($where);
		$CI->edb->join('employee_payroll_information AS epi','epi.emp_id=e.emp_id','INNER');
		$CI->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
		$q = $CI->edb->get('employee AS e');
		$row = $q->num_rows();
		return $row;
	}
	
	
	
	/**
	 * encryption ofr base64
	 * Enter description here ...
	 * @param unknown_type $data
	 */
	function b_enc($data)
	{
	  return strtr(rtrim(base64_encode($data), '='), '+/', '-_');
	}
	
	/**
	 * decryption of base64
	 * Enter description here ...
	 * @param unknown_type $base64
	 */
	function b_dec($base64)
	{
	  return base64_decode(strtr($base64, '-_', '+/'));
	}
	
	/**
	 * GET PAYROLL SYSTEM ACCOuNT INFORMATION
	 * Enter description here ...
	 * @param int $psa_id
	 * @return object
	 */
	function iget_psa_info($psa_id){
		$CI =& get_instance();
		
		if($psa_id ==""){
			$psa_id = $CI->session->userdata('psa_id');
		}else{
			$psa_id = $psa_id;
		}
		$assign_where = array(
			'payroll_system_account_id'=>$psa_id,
			'status'=>'Active'
		);
		$CI->edb->where($assign_where);
		$q = $CI->edb->get('payroll_system_account');
		$r = $q->row();
		return $r;
	}
	
	/**
	 * create a company when subscribers plan are free then 
	 * Enter description here ...
	 * @param int $psa_id
	 * @return boolean
	 */
	function check_plan_subscribers($psa_id=""){
		$CI =& get_instance();
		
		if($psa_id ==""){
			$psa_id = $CI->session->userdata('psa_id');
		}else{
			$psa_id = $psa_id;
		}
		$assign_where = array(
			'p.payroll_system_account_id'=>$psa_id,
			'ac.deleted'=>'0'
		);
		$CI->edb->where($assign_where);
		$CI->edb->join('assigned_company AS ac','p.payroll_system_account_id=ac.payroll_system_account_id','INNER');
		$q = $CI->edb->get('payroll_system_account AS p');
		$r = $q->result();
		if($r == false){
			$psa_infos = iget_psa_info($psa_id);
			if($psa_infos){
				if($psa_infos->choose_plans_id == 1){ # FREE
					$comp_add_f = array(
						'company_name'=>$psa_infos->name,
						'sub_domain'=>trim(str_replace(" ","",$psa_infos->name.date("s"))),
						'subscription_date'=>date("Y-m-d H:i:s"),
						'status'=>'Active'
					);
					
					$add_company = $CI->edb->insert('company',$comp_add_f);
					$company_id = $CI->db->insert_id();
					if($company_id){
						create_default_department_and_position($company_id);
						$ac_field = array(
							'company_id'=>$company_id,
							'payroll_system_account_id'=>$psa_id
						);
						$CI->edb->insert('assigned_company',$ac_field);
					}
					return true;
				}else{
					return false;
				}
			}else{
				
				return false;
			}
		}else{
			return false;
		}
		
	}
	
	/**
	 * CHECK TABLE EMPTY ON SIMPLE QUERY
	 * Enter description here ...
	 * @param unknown_type $table
	 * @param unknown_type $company_id
	 */
	function is_table_empty($table,$company_id){
		$CI =& get_instance();
		$where = array(
			'company_id'=>$company_id,
		);
		$CI->edb->where($where);
		$q = $CI->edb->get($table);
		$row = $q->row();
		return $row;
	}
	
	/**
	 * CREATE DEPARTMENT DEFAULT RANKS POSITION EMPLOYMENT TYPE 
	 * Enter description here ...
	 * @param int $company_id
	 * @return boolean
	 */
	function create_default_department_and_position($company_id){
		if(is_numeric($company_id)) {
			
			$CI =& get_instance();
			$departments = array(
				'Marketing','Finance','Accounting','Creatives','Purchasing','Research and Development','IT','Logistics','Customer Service','Management'
			);
			$filler = array();
			$get_dept = is_table_empty('department',$company_id);
			if(!$get_dept){
				foreach($departments as $dk=>$dv){
					$dfield = array(
						'company_id'=>$company_id,
						'status'=>'Active',
						'department_name'=>$dv,
						'description'=>$dv,
						'created_by_account_id'=>$CI->session->userdata('account_id'),
						'created_date'=>date("Y-m-d H:i:s")
					);
					$CI->edb->insert('department',$dfield);
					$filler[] = $CI->db->last_query();
				}
			}
			
			$positions = array(
				'Accountant','Communications Specialist','Computer Engineer','Computer Programmer/Analyst',
				'Field Management Assistant','Field Office Supervisor','Information Management Specialist',
				'Operations Supervisor','Program Manager','Training Specialist','Team Leader','Technical Writer/Editor',
				'System Analyst','Call Center Agent','Customer Service Representatives'			
			);
			$get_pos = is_table_empty('position',$company_id);
			if(!$get_pos){
				foreach($positions as $pk=>$pv){
					$dfield = array(
						'company_id'=>$company_id,
						'status'=>'Active',
						'position_name'=>$pv,
						'description'=>$pv,
						'created_by_account_id'=>$CI->session->userdata('account_id'),
						'created_date'=>date("Y-m-d H:i:s")
					);
					$CI->db->insert('position',$dfield);
					$filler[] = $CI->db->last_query();
				}
			}
			
			$rank = array(
				'Managerial','Supervisor','Rank and File'
			);
			$get_rank = is_table_empty('rank',$company_id);
			if(!$get_rank){
				foreach($rank as $rk=>$rv){
					$rfield = array(
						'company_id'=>$company_id,
						'status'=>'Active',
						'rank_name'=>$rv,
						'description'=>'',
						'created_by_account_id'=>$CI->session->userdata('account_id'),
						'created_date'=>date("Y-m-d H:i:s")
					);
					$CI->edb->insert('rank',$rfield);
					$filler[] = $CI->db->last_query();
				}
			}
			$employment_type = array(
				'Full-time','Part-time','Casual','Contractual','Probationary','Apprenticeship','Project-Based'
			);		
			
			//changes for lite defaults
			if (iplan_is_lite()) {
    			$employment_type_entitle = array(
			        'Full-time'=>array('entitled_to_overtime'=>'no','entitled_to_holiday_pay'=>'no','entitled_to_night_differential'=>'no','entitled_to_rest_day'=>'no','entitled_to_deminimis'=>'no','entitled_to_basic_pay'=>'yes','entitled_to_leaves'=>'no','entitled_to_sss'=>'yes','entitled_to_philhealth'=>'yes','entitled_to_hdmf'=>'yes','entitled_to_thirteen_month_pay'=>'no'),
			        'Part-time'=>array('entitled_to_overtime'=>'no','entitled_to_holiday_pay'=>'no','entitled_to_night_differential'=>'no','entitled_to_rest_day'=>'no','entitled_to_deminimis'=>'no','entitled_to_basic_pay'=>'yes','entitled_to_leaves'=>'no','entitled_to_sss'=>'yes','entitled_to_philhealth'=>'yes','entitled_to_hdmf'=>'yes','entitled_to_thirteen_month_pay'=>'no'),
			        'Casual'=>array('entitled_to_overtime'=>'no','entitled_to_holiday_pay'=>'no','entitled_to_night_differential'=>'no','entitled_to_rest_day'=>'no','entitled_to_deminimis'=>'no','entitled_to_basic_pay'=>'yes','entitled_to_leaves'=>'no','entitled_to_sss'=>'yes','entitled_to_philhealth'=>'yes','entitled_to_hdmf'=>'yes','entitled_to_thirteen_month_pay'=>'no'),
			        'Contractual'=>array('entitled_to_overtime'=>'no','entitled_to_holiday_pay'=>'no','entitled_to_night_differential'=>'no','entitled_to_rest_day'=>'no','entitled_to_deminimis'=>'no','entitled_to_basic_pay'=>'yes','entitled_to_leaves'=>'no','entitled_to_sss'=>'yes','entitled_to_philhealth'=>'yes','entitled_to_hdmf'=>'yes','entitled_to_thirteen_month_pay'=>'no'),
			        'Probationary'=>array('entitled_to_overtime'=>'no','entitled_to_holiday_pay'=>'no','entitled_to_night_differential'=>'no','entitled_to_rest_day'=>'no','entitled_to_deminimis'=>'no','entitled_to_basic_pay'=>'no','entitled_to_leaves'=>'no','entitled_to_sss'=>'no','entitled_to_philhealth'=>'no','entitled_to_hdmf'=>'no','entitled_to_thirteen_month_pay'=>'no'),
			        'Apprenticeship'=>array('entitled_to_overtime'=>'no','entitled_to_holiday_pay'=>'no','entitled_to_night_differential'=>'no','entitled_to_rest_day'=>'no','entitled_to_deminimis'=>'no','entitled_to_basic_pay'=>'no','entitled_to_leaves'=>'no','entitled_to_sss'=>'no','entitled_to_philhealth'=>'no','entitled_to_hdmf'=>'no','entitled_to_thirteen_month_pay'=>'no'),
			        'Project-Based'=>array('entitled_to_overtime'=>'no','entitled_to_holiday_pay'=>'no','entitled_to_night_differential'=>'no','entitled_to_rest_day'=>'no','entitled_to_deminimis'=>'no','entitled_to_basic_pay'=>'yes','entitled_to_leaves'=>'no','entitled_to_sss'=>'yes','entitled_to_philhealth'=>'yes','entitled_to_hdmf'=>'yes','entitled_to_thirteen_month_pay'=>'no')
			    );
			} else {
			    $employment_type_entitle = array(
			        'Full-time'=>array('entitled_to_overtime'=>'yes','entitled_to_holiday_pay'=>'yes','entitled_to_night_differential'=>'yes','entitled_to_rest_day'=>'yes','entitled_to_deminimis'=>'yes','entitled_to_basic_pay'=>'yes','entitled_to_leaves'=>'yes','entitled_to_sss'=>'yes','entitled_to_philhealth'=>'yes','entitled_to_hdmf'=>'yes','entitled_to_thirteen_month_pay'=>'yes'),
			        'Part-time'=>array('entitled_to_overtime'=>'no','entitled_to_holiday_pay'=>'yes','entitled_to_night_differential'=>'yes','entitled_to_rest_day'=>'no','entitled_to_deminimis'=>'no','entitled_to_basic_pay'=>'yes','entitled_to_leaves'=>'no','entitled_to_sss'=>'yes','entitled_to_philhealth'=>'yes','entitled_to_hdmf'=>'yes','entitled_to_thirteen_month_pay'=>'yes'),
			        'Casual'=>array('entitled_to_overtime'=>'yes','entitled_to_holiday_pay'=>'yes','entitled_to_night_differential'=>'yes','entitled_to_rest_day'=>'yes','entitled_to_deminimis'=>'yes','entitled_to_basic_pay'=>'yes','entitled_to_leaves'=>'no','entitled_to_sss'=>'yes','entitled_to_philhealth'=>'yes','entitled_to_hdmf'=>'yes','entitled_to_thirteen_month_pay'=>'yes'),
			        'Contractual'=>array('entitled_to_overtime'=>'no','entitled_to_holiday_pay'=>'no','entitled_to_night_differential'=>'yes','entitled_to_rest_day'=>'yes','entitled_to_deminimis'=>'yes','entitled_to_basic_pay'=>'yes','entitled_to_leaves'=>'no','entitled_to_sss'=>'yes','entitled_to_philhealth'=>'yes','entitled_to_hdmf'=>'yes','entitled_to_thirteen_month_pay'=>'yes'),
			        'Probationary'=>array('entitled_to_overtime'=>'no','entitled_to_holiday_pay'=>'no','entitled_to_night_differential'=>'no','entitled_to_rest_day'=>'no','entitled_to_deminimis'=>'no','entitled_to_basic_pay'=>'no','entitled_to_leaves'=>'no','entitled_to_sss'=>'no','entitled_to_philhealth'=>'no','entitled_to_hdmf'=>'no','entitled_to_thirteen_month_pay'=>'no'),
			        'Apprenticeship'=>array('entitled_to_overtime'=>'no','entitled_to_holiday_pay'=>'no','entitled_to_night_differential'=>'no','entitled_to_rest_day'=>'no','entitled_to_deminimis'=>'no','entitled_to_basic_pay'=>'no','entitled_to_leaves'=>'no','entitled_to_sss'=>'no','entitled_to_philhealth'=>'no','entitled_to_hdmf'=>'no','entitled_to_thirteen_month_pay'=>'no'),
			        'Project-Based'=>array('entitled_to_overtime'=>'no','entitled_to_holiday_pay'=>'no','entitled_to_night_differential'=>'yes','entitled_to_rest_day'=>'yes','entitled_to_deminimis'=>'yes','entitled_to_basic_pay'=>'yes','entitled_to_leaves'=>'no','entitled_to_sss'=>'yes','entitled_to_philhealth'=>'yes','entitled_to_hdmf'=>'yes','entitled_to_thirteen_month_pay'=>'yes')
			    );
			}
			$get_emp_type = is_table_empty('employment_type',$company_id);
			if(!$get_emp_type){
				foreach($employment_type as $etk=>$etv){
					$etfield = array(
						'company_id'=>$company_id,
						'name'=>$etv, 
						'created_date'=>date("Y-m-d H:i:s"),
						'created_by_account_id'=>$CI->session->userdata('account_id'),
						'selected'=>'1',
						'description'=>$etv
					);
					
					if(isset($employment_type_entitle[$etv])){
						$idef_employ_type = $employment_type_entitle[$etv];
						foreach($idef_employ_type as $iet_k=>$iet_v){
							$etfield[$iet_k] = $iet_v;
						}
					}
					
					$CI->edb->insert('employment_type',$etfield);
					$filler[] = $CI->db->last_query();
				}
			}
			return true;
		}else{
			return false;
		}
	}
	/**
	 * CHECK TABLE EMPTY ON SIMPLE QUERY
	 * Enter description here ...
	 * @param unknown_type $table
	 * @param unknown_type $company_id
	 */
	function check_work_schedule($table, $work_name, $company_id){
		$CI =& get_instance();
		$where = array(
			'name'=>$work_name,
			'comp_id'=>$company_id
		);
		$CI->edb->where($where);
		$q = $CI->edb->get($table);
		$row = $q->row();
		return $row;
	}
	function get_work_schedule($table, $work_name, $company_id){
		$CI =& get_instance();
		$where = array(
				'name'=>$work_name,
				'comp_id'=>$company_id
		);
		$CI->edb->where($where);
		$q = $CI->edb->get($table);
		$row = $q->result();
		return $row;
	}
	
	/**
	 * Create Work Schedule Default
	 * @param int $company_id
	 */
	function add_default_work_schedule($company_id) {
		if(is_numeric($company_id)) {
			
			$CI =& get_instance();
			$work_schedule_name = array(
				'Regular Work Schedule', 'Compressed Work Schedule', 'Night Shift Schedule', 'Flexi Time Schedule', 'Split Shift'
			);
			$work_type_name = array(
				'Uniform Working Days', 'Uniform Working Days', 'Uniform Working Days', 'Flexible Hours', 'Workshift'
			);
			
			$rd = array('Saturday', 'Sunday');
			
			foreach( $work_schedule_name as $key=>$val ){
				$work_type = $work_type_name[$key];
				
				$get_emp_schedule = check_work_schedule('work_schedule', $val, $company_id);
				
				if( !$get_emp_schedule ) {
					
					$fields = array(
						"name" => $val,
						"work_type_name" => $work_type,
						"comp_id" => $company_id,
						"default" => "1",
						"status" => "Active"
					);
									
					$CI->edb->insert("work_schedule", $fields);
					$work_id = $CI->db->insert_id();
					
					if($work_id) {
						if($val == "Regular Work Schedule") {
							/*	ADD RESTDAY */
							$rest_day = array('Sunday');
							foreach ($rest_day as $key2=>$val2) {
								$fields_restday = array(
									"payroll_group_id" => "0",
									"rest_day" => $val2,
									"company_id" => $company_id,
									"work_schedule_id" => $work_id,
									"status" => "Active"
								);
								$CI->edb->insert("rest_day", $fields_restday);
								$filler[] = $CI->db->last_query();
							}
							/* ADD WORK DETAILS */
							
							$work_day = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
								
							foreach ($work_day as $key3=>$val3) {
								$fields_restday = array(
										"payroll_group_id" => "0",
										"work_schedule_id" => $work_id,
										"work_schedule_name" => $val,
										"days_of_work" => $val3,
										"work_start_time" => "08:00:00",
										"work_end_time" => ($val3 == "Saturday") ? "12:00:00" : "17:00:00",
										"total_work_hours" => ($val3 == "Saturday") ? "3" : "7",
										"company_id" => $company_id,
										"break_in_min" => ($val3 == "Saturday") ? "0" : "60",
										"latest_time_in_allowed" => "60",
										"status" => "Active"
								);
								$CI->edb->insert("regular_schedule", $fields_restday);
							
								$filler[] = $CI->db->last_query();
							
							}
							
						}else if($val == "Compressed Work Schedule") {
							/*	ADD RESTDAY */
							$rest_day = $rd;
							foreach ($rest_day as $key2=>$val2) {
								$fields_restday = array(
										"payroll_group_id" => "0",
										"rest_day" => $val2,
										"company_id" => $company_id,
										"work_schedule_id" => $work_id,
										"status" => "Active"
								);
								$CI->edb->insert("rest_day", $fields_restday);
								$filler[] = $CI->db->last_query();
							}
							/* ADD WORK DETAILS */
								
							$work_day = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday');
							
							foreach ($work_day as $key3=>$val3) {
								$fields_restday = array(
										"payroll_group_id" => "0",
										"work_schedule_id" => $work_id,
										"work_schedule_name" => $val,
										"days_of_work" => $val3,
										"work_start_time" => "08:00:00",
										"work_end_time" => "18:00:00",
										"total_work_hours" => "8",
										"company_id" => $company_id,
										"break_in_min" => "60",
										"latest_time_in_allowed" => "60",
										"status" => "Active"
								);
								$CI->edb->insert("regular_schedule", $fields_restday);
									
								$filler[] = $CI->db->last_query();
									
							}
						}else if($val == "Night Shift Schedule") {
							$rest_day = $rd;
							/*	ADD RESTDAY */
							foreach ($rest_day as $key2=>$val2) {
								$fields_restday = array(
									"payroll_group_id" => "0",
									"rest_day" => $val2,
									"company_id" => $company_id,
									"work_schedule_id" => $work_id,
									"status" => "Active"
								);
								$CI->edb->insert("rest_day", $fields_restday);
								$filler[] = $CI->db->last_query();
							}
							/* ADD WORK DETAILS */
							
							$work_day = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday');
								
							foreach ($work_day as $key3=>$val3) {
								$fields_restday = array(
									"payroll_group_id" => "0",
									"work_schedule_id" => $work_id,
									"work_schedule_name" => $val,
									"days_of_work" => $val3,
									"work_start_time" => "22:00:00",
									"work_end_time" => "06:00:00",
									"total_work_hours" => "6",
									"company_id" => $company_id,
									"break_in_min" => "60",
									"latest_time_in_allowed" => "60",
									"status" => "Active"
								);
								$CI->edb->insert("regular_schedule", $fields_restday);
									
								$filler[] = $CI->db->last_query();
									
							}
						}else if($val == "Flexi Time Schedule") {
							$rest_day = $rd;
							/*	ADD RESTDAY */
							foreach ($rest_day as $key2=>$val2) {
								$fields_restday = array(
										"payroll_group_id" => "0",
										"rest_day" => $val2,
										"company_id" => $company_id,
										"work_schedule_id" => $work_id,
										"status" => "Active"
								);
								$CI->edb->insert("rest_day", $fields_restday);
								$filler[] = $CI->db->last_query();
							}
							/* ADD WORK DETAILS */
							$fields_restday = array(
									"payroll_group_id" => "0",
									"not_required_login" => "0",
									"total_hours_for_the_day" => "8",
									"total_hours_for_the_week" => "40",
									"total_days_per_year" => "0",
									"latest_time_in_allowed" => "09:00:00",
									"number_of_breaks_per_day" => "0",
									"duration_of_lunch_break_per_day" => "0",
									"break1" => "0",
									"break2" => "0",
									"break3" => "0",
									"break4" => "0",
									"duration_of_short_break_per_day" => "0",
									"work_schedule_id" => $work_id,
									"company_id" => $company_id
							);
							$CI->edb->insert("flexible_hours", $fields_restday);
							$filler[] = $CI->db->last_query();
						}else if($val == "Split Shift") {
							//nothing
						}
					}
				}
			}
		}
	}
	
	/**
	 * THE WHO THESE GUY
	 * Enter description here ...
	 * @param int $account_id
	 * @param int $company_id
	 */
	function employee_who($account_id,$company_id){
		if(is_numeric($account_id)){
			$CI =& get_instance();
			$where = array(
				'a.account_id' => $account_id,
				'e.company_id'=>$company_id,
				'a.deleted'=>'0',
				'e.status'=>'Active'
			);
			$CI->edb->where($where);
			$CI->edb->join('social_media_accounts AS sma','sma.account_id=a.account_id','LEFT');
			$CI->edb->join('employee AS e','e.account_id=a.account_id','INNER');
			$q = $CI->edb->get('accounts AS a');
			$r = $q->row();
			return $r;
		}else{
			return false;
		}
	}
	

	/**
	 * GET emPLOEES DEPENDENT OR CHILD
	 * Enter description here ...
	 * @param int $account_id
	 * @param int $company_id
	 * @return object
	 */
	function employee_who_child($account_id,$company_id){
		if(is_numeric($account_id)){
			$CI =& get_instance();
			$where = array(
				'a.account_id' => $account_id,
				'e.company_id'=>$company_id
			);
			$CI->edb->where($where);
			$CI->edb->join('employee AS e','e.account_id=a.account_id','INNER');
			$q = $CI->edb->get('accounts AS a');
			$r = $q->row();
			if($r){
				$emp_id = $r->emp_id;
				$where_dep = array(
					'emp_id'=>$emp_id,
					'status'=>'Active',
					'company_id'=>$company_id
				);
				$CI->edb->where($where_dep);
				$qd = $CI->edb->get('employee_qualifid_dependents');
				$rd = $qd->result();
				return $rd;
			}else{
				return false;
			}			
		}else{
			return false;
		}
	}
	
	/**
	 * THE WHO THESE GUY
	 * Enter description here ...
	 * @param int $account_id
	 * @param int $company_id
	 */
	function employee_who_fulldetails($account_id,$company_id){
		if(is_numeric($account_id)){
			$CI =& get_instance();
			$where = array(
				'a.account_id' => $account_id,
				'e.company_id'=>$company_id
			);
			$CI->edb->where($where);
			
			$CI->edb->join('employee AS e','e.account_id=a.account_id','INNER');
			$CI->edb->join('employee_payroll_information AS epi','e.emp_id=epi.emp_id','LEFT');
			$CI->edb->join('position AS p','epi.position=p.position_id','LEFT');
			$CI->edb->join('payroll_group AS pg','epi.payroll_group_id=pg.payroll_group_id','LEFT');
			$CI->edb->join('employee_payroll_account_info AS epa','epa.emp_id=e.emp_id','LEFT');
			$CI->edb->join('rank AS r','r.rank_id=epi.rank_id','LEFT');		
			$CI->edb->join('cost_center AS cc','cc.cost_center_id=epi.cost_center','LEFT');	
			$q = $CI->edb->get('accounts AS a');
			$r = $q->row();
			return $r;
		}else{
			return false;
		}
	}
	
	function employee_who_fulldetails_all($company_id){
		if(is_numeric($company_id)){
			$CI =& get_instance();
			$where = array(
				'e.company_id'=>$company_id,
				'e.status'=>'Active'
			);
			$CI->edb->where($where);
			
			$CI->edb->join('accounts AS a','e.account_id=a.account_id','INNER');
			$CI->edb->join('employee_payroll_information AS epi','e.emp_id=epi.emp_id','LEFT');
			$CI->edb->join('position AS p','epi.position=p.position_id','LEFT');
			$CI->edb->join('payroll_group AS pg','epi.payroll_group_id=pg.payroll_group_id','LEFT');
			$CI->edb->join('employee_payroll_account_info AS epa','epa.emp_id=e.emp_id','LEFT');
			$CI->edb->join('rank AS r','r.rank_id=epi.rank_id','LEFT');		
			$CI->edb->join('cost_center AS cc','cc.cost_center_id=epi.cost_center','LEFT');	
			$q = $CI->edb->get('employee AS e');
			$r = $q->result();
			return $r;
		}else{
			return false;
		}
	}
	
	/**
	 * COUNT THE COMPANY OR LIST THEM BY DEFAULT THE SECOND PARAMATER ARE TRUE JUST PUT FALSE ON IT 
	 * Enter description here ...
	 * @param int $psa_id
	 * @param boolean $count
	 * @return object
	 */
	function count_company_under($psa_id="",$count=true){
		$CI =& get_instance();
		if($psa_id !==""){
			$psa_id = $psa_id;
		}else{
			$psa_id = $CI->session->userdata('psa_id');
		}
		$where = array(
			'ac.payroll_system_account_id'=>$psa_id,
			'ac.deleted'=>'0'
		);
		$CI->edb->where($where);
		$CI->edb->join('company AS c','c.company_id= ac.company_id','INNER');
		$q = $CI->edb->get('assigned_company AS ac');
		$r = $q->result();
		if($count == true){
			return $q->num_rows();
		}
		return $r;
	}
	
	
	function get_table_info($table,$where){
		$CI =& get_instance();
		if($table){
			$CI->edb->where($where);
			$q = $CI->edb->get($table);
			$r = $q->row();
			return $r;
		}else{
			return false;
		}
	}
	
	function get_table_info_all($table,$where){
		$CI =& get_instance();
		if($table){
			$CI->edb->where($where);
			$q = $CI->edb->get($table);
			$r = $q->result();
			return $r;
		}else{
			return false;
		}
	}
	
	/**
	 * SAVE THIS TABLE
	 * Enter description here ...
	 * @param string $tbl
	 * @param string $field
	 * @return save
	 */
	function esave($tbl,$field){
		$CI =& get_instance();
		$CI->edb->insert($tbl,$field);
	}
	
	/**
	 * UPDATE NI SIYA KAY 
	 * Enter description here ...
	 * @param unknown_type $tble
	 * @param unknown_type $field
	 * @param unknown_type $where
	 */
	function eupdate($tble,$field,$where){
		$CI =& get_instance();
		$CI->edb->where($where);
		$CI->edb->update($tble,$field);
	}
	/**
	 * SAVE THIS TABLE
	 * Enter description here ...
	 * @param string $tbl
	 * @param string $field
	 * @return save
	 */
	function edelete($tbl,$where){
		$CI =& get_instance();
		$CI->db->where($where);
		$CI->db->delete($tbl);
	}

	
	function etax_status($value){
		$ret = '';
		switch($value){
			case '-1':  $ret = 'Z - zero exemption'; break;
			case '0':  $ret = 'S/ME - Single or Married without qualified dependents'; break;
			case '1':  $ret = 'ME1 / S1 - single / married with 1 QD'; break;
			case '2':  $ret = 'ME2 / S2 - single / married with 2 QD'; break;
			case '3':  $ret = 'ME3 / S3 - single / married with 3 QD'; break;
			case '4':  $ret = 'ME4 / S4 - single / married with 4 QD'; break;
			case '5':  $ret = 'Special - 10'; break;
			case '6':  $ret = 'Special - 15'; break;
		}
		return $ret;
	}
	
	
	function employee_information_data($account_id,$company_id){
		if(is_numeric($account_id)){
			$CI =& get_instance();
			$where = array(
				'a.account_id' => $account_id,
				'e.company_id'=>$company_id,
				'e.status'=>'Active',
				'e.deleted'=>'0'
			);
			$CI->edb->where($where);
			
			$CI->edb->join('employee AS e','e.account_id=a.account_id','INNER');
			$CI->edb->join('employee_payroll_information AS epi','e.emp_id=epi.emp_id','LEFT');
			$CI->edb->join('employment_type AS et','epi.employment_type=et.emp_type_id','LEFT');
			$CI->edb->join('department AS d','d.dept_id=epi.department_id','LEFT');
			$CI->edb->join('position AS p','epi.position=p.position_id','LEFT');
			$CI->edb->join('payroll_group AS pg','epi.payroll_group_id=pg.payroll_group_id','LEFT');
			$CI->edb->join('employee_payroll_account_info AS epa','epa.emp_id=e.emp_id','LEFT');
			$CI->edb->join('rank AS r','r.rank_id=epi.rank_id','LEFT');		
			$CI->edb->join('cost_center AS cc','cc.cost_center_id=epi.cost_center','LEFT');	
			$CI->edb->join('social_media_accounts AS sma','sma.account_id=a.account_id','LEFT');
			$q = $CI->edb->get('accounts AS a');
			$r = $q->row();
			return $r;
		}else{
			return false;
		}
	}
	
	/**
	 * Newsletter Logo
	 
	function newsletter_logo($company_id, $nologo="assets/theme_2015/images/images-emailer/img-company-logo.png")
	{	
		$CI =& get_instance();
		$path = base_url()."uploads/companies/".$company_id."/";
		
		if(is_numeric($company_id)) {
			
			$wr = array(
				"company_id" => $company_id,
				"status" => "Active"	
			);
			$CI->db->where($wr);
			$q = $CI->db->get("company");
			$r = $q->row();
			
			if( $r->company_logo ) {
				$imgPath = $path.$r->company_logo; 
			}else{
				$imgPath = base_url().$nologo;
			}
			
			return $imgPath;
		}
		
	}
	*/
	
	/**
	 * Newsletter Logo Change
	 */
	function newsletter_logo($company_id, $nologo="assets/theme_2015/images/images-emailer/img-company-logo.png")
	{
		$CI =& get_instance();
		$path = base_url()."uploads/companies/".$company_id."/";
	
		if(is_numeric($company_id)) {
				
			$wr = array(
					"company_id" => $company_id,
					"status" => "Active"
			);
			$CI->db->where($wr);
			$q = $CI->db->get("company");
			$r = $q->row();
				
			if( $r->company_email_logo ) {
				$imgPath = $path.$r->company_email_logo;
			}else{
				$imgPath = base_url().$nologo;
			}
				
			return $imgPath;
		}
	
	}
	
	/**
	 * Thumbnail Image
	 */
	function thumb_pic($account_id,$company_id, $noImg ="/assets/theme_2015/images/img-user-avatar-dummy.jpg")
	{
		$CI =& get_instance();
		$path = base_url()."uploads/companies/".$company_id."/";
		
		
		if(is_numeric($account_id)) {
			
			$wr = array(
				"a.account_id" => $account_id	
			);
			$CI->edb->where($wr);
			$CI->edb->join("employee AS e","e.account_id = a.account_id","LEFT");
			$q = $CI->edb->get("accounts AS a");
			$r = $q->row();
			
			if($r->profile_image) {
				$imgPath = $path.$r->profile_image; 
			}else{
				//$imgPath = base_url().$noImg;
				$f_name = substr($r->first_name, 0,1);
				$last_name = substr($r->last_name, 0,1);
				$company_name = $CI->uri->segment(1);
				
				$imgPath = "/{$company_name}/avatar/default_image/img/30/93/88/{$f_name}{$last_name}.png";
				//$imgPath = $noImg;
			}
			
			return $imgPath;
		}
		
	}
	
	/**
	 * Parse URL   
	 */
	function parsing_url() {
		$url = $_SERVER['REQUEST_URI'];
		$r = explode('=',$url);
		if( count($r) == 2 ) {
			$r = array_filter($r);
			$r = array_merge($r, array());
			$r = preg_replace('/\?.*/', '', $r);
			$endofurl = $r[1];
		}else{
			$endofurl = FALSE;
		}
		return $endofurl;
	}
	
	
	function detail_thumb_pic($account_id,$company_id, $noImg ="/assets/theme_2015/images/img-user-avatar-dummy.jpg")
	{
		$CI =& get_instance();
		$path = base_url()."uploads/companies/".$company_id."/";
	
	
		if(is_numeric($account_id)) {
	
			$wr = array(
					"a.account_id" => $account_id
			);
			$CI->edb->where($wr);
			$CI->edb->join("employee AS e","e.account_id = a.account_id","LEFT");
			$q = $CI->edb->get("accounts AS a");
			$r = $q->row();
	
			if($r->profile_image) {
				$imgPath = $path.$r->profile_image;
			}else{
				//$imgPath = base_url().$noImg;
				$f_name = substr($r->first_name, 0,1);
				$last_name = substr($r->last_name, 0,1);
				$company_name = $CI->uri->segment(1);
	
				$imgPath = "/{$company_name}/avatar/default_image/img/35/100/100/{$f_name}{$last_name}.png";
				//$imgPath = $noImg;
			}
	
			return $imgPath;
		}
	
	}
	
	function employee_default_right_menu($account_id,$company_id, $noImg ="/assets/theme_2015/images/img-user-avatar-dummy.jpg")
	{
		$CI =& get_instance();
		$path = base_url()."uploads/companies/".$company_id."/";
	
	
		if(is_numeric($account_id)) {
	
			$wr = array(
					"a.account_id" => $account_id
			);
			$CI->edb->where($wr);
			$CI->edb->join("employee AS e","e.account_id = a.account_id","LEFT");
			$q = $CI->edb->get("accounts AS a");
			$r = $q->row();
	
			if($r->profile_image) {
				$imgPath = $path.$r->profile_image;
			}else{
				//$imgPath = base_url().$noImg;
				$f_name = substr($r->first_name, 0,1);
				$last_name = substr($r->last_name, 0,1);
				$company_name = $CI->uri->segment(1);
	
				$imgPath = "/{$company_name}/avatar/default_image/img/35/100/100/{$f_name}{$last_name}.png";
				//$imgPath = $noImg;
			}
	
			return $imgPath;
		}
	
	}
	
	
	function org_chart_default_img($account_id,$company_id,$noImg ="/assets/theme_2015/images/img-user-avatar-dummy.jpg")
	{
		$CI =& get_instance();
		$path = base_url()."uploads/companies/".$company_id."/";
		
		if(is_numeric($account_id)){
			$where = array(
				"a.account_id"=>$account_id
			);
			
			$CI->db->where($where);
			$CI->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","INNER");
			//$this->edb->join("position AS p","epi.position = p.position_id","INNER");
			$CI->edb->join("department AS d","d.dept_id = epi.department_id","INNER");
			$CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$q = $CI->edb->get("employee AS e");
			$r = $q->row();
			
			
			if($r->profile_image){
				$pathImg = $path.$r->profile_image;
			}else{
				$first_n = substr($r->first_name, 0,1);
				$last_n = substr($r->last_name, 0,1);
				$company_n = $CI->uri->segment(1);
				
				$pathImg = "/{$company_n}/avatar/default_image/img/35/100/100/{$first_n}{$last_n}.png";
				//$pathImg = $noImg;
			}
			return $pathImg;
		}
	}
	
	function check_holiday_year($company_id, $year) {
		
		if(is_numeric($company_id)) {
			
			$CI =& get_instance();
			$x = 0;
			
			$sel = array(
				"YEAR(h.date) AS year"
			);
			$where = array(
				"h.company_id" => $company_id
			);
			$CI->db->select($sel);
			$CI->db->where($where);
			$q = $CI->db->get("holiday AS h");
			
			$r = $q->result();
			
			if($r) {
				foreach ($r as $row) {
					if($row->year < $year) {
						$x = 1; //holiday schedule are not updated
					}
				}
			}
			return ($x == 1) ? 1 : 2;
		}
		
	}
	function remove_holiday_notification($psa_id, $recipient_account_id, $create_account_id, $company_id, $message, $via){
		$where = array(
			"psa_id" => $psa_id,
			"company_id" => $company_id,
			"creator_account_id" => 0,
			"message" => $message,
			"recipient_account_id" => $recipient_account_id
		);
		$check_exists = get_table_info("message_board",$where);
		
		if($check_exists) {
			$data = array(
				"psa_id" => $psa_id,
				"message_board_id" => $check_exists->message_board_id,
				"company_id" => $company_id
			);
			edelete("message_board", $data);
		}
	}
	
	function current_payroll_period($company_id) {
		$CI =& get_instance();
		$todays_date = date("Y-m-d");
		$where = array(
			'cut_off_from <='=> $todays_date,
			'first_payroll_date >='=> $todays_date,
			'company_id' => $company_id
		);
		$CI->db->where($where);
		$q = $CI->db->get("payroll_calendar");
		$r = $q->row();
		return ($r) ? $r : FALSE;
	}
	function total_shifts_summary($filter_summary="", $company_id) {
		
		$total = 0;
		$wk_id = array();
		$wk_id2 = array();
		$CI =& get_instance();
		$todays_date = date("Y-m-d");
		$total_def_sched = $to_default = $total_emp = $total_assigned = $total_assign_shifts = 0;
		$flag = NULL;
		
		if($filter_summary == "schedule_available") {
			
			$wr_sched = array(
				"comp_id" => $company_id,
				"flag_custom" => "0"
			);
			$CI->db->where($wr_sched);
			$q_sched = $CI->db->get("work_schedule");
			$query_sched = $q_sched->result();
			
			$total_work_schedule =  $q_sched->num_rows();
			
			//default work schedule
			if($query_sched) {
				/*
				foreach ($query_sched as $rows) {
					$wk_id[] = $rows->work_schedule_id;
				}
				*/
				//assigned shifts
				$sel_ess = array(
					"emp_id",
					"work_schedule_id"
				);
				
				$wr = array(
					"company_id" => $company_id,
					"payroll_group_id" => 0,
					"valid_from" => $todays_date
				);
				$CI->db->select($sel_ess);
				$CI->db->where($wr);
				
				$CI->db->group_by("work_schedule_id");
				//$CI->db->where_in("work_schedule_id", $wk_id);
				$q = $CI->db->get("employee_shifts_schedule");
				$query2 = $q->result();
				
				$total_assign_shifts = $q->num_rows();
				$total_assign_shifts = ($total_assign_shifts > 0) ? $total_assign_shifts: 0;
				
				if($query2) {
					
					foreach ($query2 as $rows2) {
						$wk_id[] = $rows2->work_schedule_id;
					}
					$flag = 1;
				}
				if($total_assign_shifts > 0) {
					
					$sel_epi = array(
						"epi.emp_id",
						"pg.work_schedule_id",
						"pg.payroll_group_id"
					);
					$wr_pg = array(
						"epi.status" => 'Active',
						"e.company_id" => $company_id,
						"epi.employee_status" => 'Active',
						'a.user_type_id' => '5'
					);
						
					$CI->db->select($sel_epi);
					$CI->db->where($wr_pg);
					if($flag == 1) {
						$CI->db->where_not_in("pg.work_schedule_id", $wk_id);
					}
					$CI->db->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
					$CI->db->join("employee_payroll_information AS epi", "epi.emp_id = e.emp_id", "LEFT");
					$CI->db->join("payroll_group AS pg", "pg.payroll_group_id = epi.payroll_group_id", "INNER");
					$CI->db->group_by("pg.work_schedule_id");
					$q_epi = $CI->db->get("employee AS e");
					
					$total_def_sched = $q_epi->num_rows();
					$total = $total_work_schedule - $total_assign_shifts;
					$total = $total - $total_def_sched;
					
				}else{
					
					$sel_epi = array(
						"epi.emp_id",
						"pg.work_schedule_id",
						"pg.payroll_group_id"
					);
					$wr_pg = array(
						"epi.status" => 'Active',
						"e.company_id" => $company_id,
						"epi.employee_status" => 'Active',
						'a.user_type_id' => '5'
					);
						
					$CI->db->select($sel_epi);
					$CI->db->where($wr_pg);
					if($flag == 1) {
						$CI->db->where_in("pg.work_schedule_id", $wk_id);
					}
					$CI->db->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
					$CI->db->join("employee_payroll_information AS epi", "epi.emp_id = e.emp_id", "LEFT");
					$CI->db->join("payroll_group AS pg", "pg.payroll_group_id = epi.payroll_group_id", "INNER");
					$CI->db->group_by("pg.work_schedule_id");
					$q_epi = $CI->db->get("employee AS e");
					
					$total_def_sched = $q_epi->num_rows();
					
					$total = $total_work_schedule - $total_assign_shifts;
					$total = $total - $total_def_sched;
				}
			}
			
		}
		
		if($filter_summary == "assigned_shifts") {
			
			$epi_emp_id = array();
			
			$sel_ess = array(
				"e.emp_id",
				"ess.work_schedule_id"
			);
				
			$wr = array(
				"e.company_id" => $company_id,
				"ess.payroll_group_id" => 0,
				"e.company_id" => $company_id,
				"ess.valid_from" => $todays_date,
				"epi.employee_status" => "Active",
				"a.user_type_id" => "5"
			);
			$CI->db->select($sel_ess);
			$CI->db->where($wr);
			$CI->db->group_by("emp_id");
			$CI->db->join("employee AS e", "e.emp_id = ess.emp_id", "LEFT");
			$CI->db->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
			$CI->db->join("employee_payroll_information AS epi", "epi.emp_id = e.emp_id", "LEFT");
			$q = $CI->db->get("employee_shifts_schedule AS ess");
			$query2 = $q->result();
			
			$total_assign_shifts = $q->num_rows();
			
			if($query2) {
				
				foreach ($query2 as $rows) {
					$epi_emp_id[] = $rows->emp_id;
				}
				$sel_epi = array(
					"epi.emp_id"
				);
				$wr_pg = array(
					"epi.status" => "Active",
					"e.company_id" => $company_id,
					"epi.employee_status" => "Active",
					"a.user_type_id" => "5"
				);
				$CI->db->select($sel_epi);
				$CI->db->where($wr_pg);
				$CI->db->where('pg.work_schedule_id is NOT NULL', NULL, FALSE);
				$CI->db->where_not_in("epi.emp_id", $epi_emp_id);
				$CI->db->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
				$CI->db->join("employee_payroll_information AS epi", "epi.emp_id = e.emp_id", "LEFT");
				$CI->db->join("payroll_group AS pg", "pg.payroll_group_id = epi.payroll_group_id", "INNER");
				$CI->db->group_by("epi.emp_id");
				$q_epi = $CI->db->get("employee AS e");
				$total_def_sched = $q_epi->num_rows();
				$total = $total_assign_shifts + $total_def_sched;

			}else{
				$sel_epi = array(
					"epi.emp_id"
				);
				$wr_pg = array(
					"epi.status" => 'Active',
					"e.company_id" => $company_id,
					"epi.employee_status" => 'Active',
					"a.user_type_id" => "5"
				);
				$CI->db->select($sel_epi);
				$CI->db->where($wr_pg);
				$CI->db->where('pg.work_schedule_id is NOT NULL', NULL, FALSE);
				$CI->db->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
				$CI->db->join("employee_payroll_information AS epi", "epi.emp_id = e.emp_id", "LEFT");
				$CI->db->join("payroll_group AS pg", "pg.payroll_group_id = epi.payroll_group_id");
				$CI->db->group_by("epi.emp_id");
				$q_epi =  $CI->db->get("employee AS e");
				$total = $q_epi->num_rows();
			}
			
		}
		
		if($filter_summary == "unassigned_shifts") {
			
			$emp_array = array();
			
			$sel = array(	
				"epi.emp_id"
			);
			$wr_epi = array(
				"epi.status" => 'Active',
				"e.company_id" => $company_id,
				"epi.employee_status" => 'Active',
				"a.user_type_id" => "5"
			);
			
			$CI->db->select($sel);
			$CI->db->where($wr_epi);
			
			$CI->db->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
			$CI->db->join("employee_payroll_information AS epi", "epi.emp_id = e.emp_id", "LEFT");
			$q_unass = $CI->db->get("employee AS e");
			$qr = $q_unass->result();
			
			$total_emp = $q_unass->num_rows();
			
			if($qr) {
				foreach($qr as $row) {
					$emp_array[] = $row->emp_id;
				}
				$sel_emps = array(
					"ess.emp_id",
					"ess.work_schedule_id"
				);
				$wr_ess = array(
					"ess.company_id" => $company_id,
					"ess.payroll_group_id" => 0,
					"ess.valid_from" => $todays_date
				);
				$CI->db->select($sel_emps);
				$CI->db->where_in("ess.emp_id", $emp_array);
				$CI->db->group_by("ess.emp_id");
				$getshifts = $CI->db->get("employee_shifts_schedule AS ess");
				$res = $getshifts->result();
				$total_assigned = $getshifts->num_rows();
				
				if($res) {
					
					$def_emp = array();
					
					foreach ($res as $row) {
						$def_emp[] = $row->emp_id;
					}
					
					$sel = array(
						"epi.emp_id",
						"pg.work_schedule_id"
					);
					
					$wr_epi = array(
						"epi.status" => 'Active',
						"epi.company_id" => $company_id,
						"epi.employee_status" => 'Active'
					);
						
					$CI->db->select($sel);
					$CI->db->where($wr_epi);
					$CI->db->where_not_in("epi.emp_id", $def_emp);
					$CI->db->where('pg.work_schedule_id is NOT NULL', NULL, FALSE);
					$CI->db->join("payroll_group AS pg", "pg.payroll_group_id = epi.payroll_group_id", "INNER");
					$get_epi = $CI->db->get("employee_payroll_information AS epi");
					$to_default = $get_epi->num_rows();
					
					$total = $total_assigned + $to_default;
					$total = $total_emp - $total;
				}else{
					$total = default_schedule_from_payroll_group($company_id);
				}

			}
			
		}
		return $total;
	}
	
	
	function default_schedule_from_payroll_group($company_id) {
		
		$total = 0;
		$CI =& get_instance();
		
		$sel_epi = array(
			"epi.emp_id"
		);
		$wr_pg = array(
			"epi.status" => 'Active',
			"e.company_id" => $company_id,
			"epi.employee_status" => 'Active',
			"a.user_type_id" => "5"
		);
		$CI->db->select($sel_epi);
		$CI->db->where($wr_pg);
		$CI->db->where('pg.work_schedule_id is NULL', NULL, FALSE);
		$CI->db->join("accounts AS a", "a.account_id = e.account_id", "LEFT");
		$CI->db->join("employee_payroll_information AS epi", "epi.emp_id = e.emp_id", "LEFT");
		$CI->db->join("payroll_group AS pg", "pg.payroll_group_id = epi.payroll_group_id", "INNER");
		$CI->db->group_by("epi.emp_id");
		$q_epi = $CI->db->get("employee AS e");
		$total = $q_epi->num_rows();
		
		return $total;
	}
	
	/**
	 * Check all employees that has no basic pay
	 * @param int $comp_id
	 * @param int $psa_id
	 * @param int $acc_id
	 */
	function check_employees_no_basic_pay($comp_id, $psa_id, $emp_id='', $acc_id)
	{
	    $CI =& get_instance();
	    $owner_acc = ($emp_id == '') ? $acc_id : '';
	    $employees = get_employees_no_basic_pay($comp_id);
	    $link = base_url().$CI->uri->segment(1)."/workforce/emp_basic_information/detail_basic_pay/";
	    $e_where = array(
	        "module" => "basic_pay",
	        "company_id" => $comp_id,
	        "emp_id" => ($emp_id != '') ? $emp_id : 0
	    );
	    $mb_employees = get_message_board_emp_who($e_where); # employees with no basic pay that are on message board tbl
	
	    $t_emp = count($employees);
	    $t_mb = count($mb_employees);
	    # check first if there are employees with no basic pay, else do nothing
	    if ($employees) {
	        # check if there are employees with no basic pay on the message board then insert, else compare to data in message board
	        if (!$mb_employees) {
	            foreach ($employees as $row) {
	                $err_msg = "You have an employee which doesn't have a basic pay. Please click on the <a href='{$link}".$row->account_id."' target='_blank'><strong>link</strong></a> to assign a basic pay to this employee.";
	                send_to_message_board($psa_id, $emp_id, $acc_id, $comp_id, $err_msg, "system", "error", $owner_acc, $row->emp_id, "basic_pay");
	            }
	        } else {
	            # loop through employees lacking basic pay and compare if this employee is not yet in message board then insert, else do nothing
	            
	            for ($i = 0; $i < $t_emp; $i++) {
	                $not_found = true;
	                for ($j = 0; $j < $t_mb; $j++) {
	                    if ($employees[$i]->emp_id == $mb_employees[$j]->emp_who ) {
	                        $not_found = false;
	                        break;
	                    }
	                }
	                if ($not_found) {
	                    $err_msg = "You have an employee which doesn't have a basic pay. Please click on the <a href='{$link}".$employees[$i]->account_id."' target='_blank'><strong>link</strong></a> to assign a basic pay to this employee.";
	                    send_to_message_board($psa_id, $emp_id, $acc_id, $comp_id, $err_msg, "system", "error", $owner_acc, $employees[$i]->emp_id, "basic_pay");
	                }
	            }
	        }
	    }
	}
	
	
	/**
	 * Check all employees that does not belong to any payroll group
	 * @param int $comp_id
	 * @param int $psa_id
	 * @param int $acc_id
	 */
	function check_employees_no_payroll_group($comp_id, $psa_id, $emp_id='', $acc_id)
	{
	    $CI =& get_instance();
	    $owner_acc = ($emp_id == '') ? $acc_id : '';
	    $employees = get_employees_no_payroll_group($comp_id);
	    $link = base_url().$CI->uri->segment(1)."/workforce/emp_basic_information/detail_basic_pay/";
	    $e_where = array(
	        "company_id" => $comp_id,
	        "module" => "payroll_group",
	        "emp_id" => ($emp_id != '') ? $emp_id : 0
	    );
	    $mb_employees = get_message_board_emp_who($e_where);
	    $t_emp = count($employees);
	    $t_mb = count($mb_employees);
	    if ($employees) {
	        if (!$mb_employees) {
	            foreach ($employees as $row) {
	                $err_msg = "You have an employee which doesn't belong to a payroll group. Please click on the <a href='{$link}".$row->account_id."' target='_blank'><strong>link</strong></a> to assign a payroll group to this employee.";
	                send_to_message_board($psa_id, $emp_id, $acc_id, $comp_id, $err_msg, "system", "error", $owner_acc, $row->emp_id, "payroll_group");
	            }
	        } else {	            
	            for ($i = 0; $i < $t_emp; $i++) {
	                $not_found = true;
	                for ($j = 0; $j < $t_mb; $j++) {
	                    if ($employees[$i]->emp_id == $mb_employees[$j]->emp_who ) {
	                        $not_found = false;
	                        break;
	                    }
	                }
	                if ($not_found) {
	                    $err_msg = "You have an employee which doesn't belong to a payroll group. Please click on the <a href='{$link}".$employees[$i]->account_id."' target='_blank'><strong>link</strong></a> to assign a payroll group to this employee.";
	                    send_to_message_board($psa_id, $emp_id, $acc_id, $comp_id, $err_msg, "system", "error", $owner_acc, $employees[$i]->emp_id, "payroll_group");
	                }
	            }
	        }
	         
	    }
	}
	
	/**
	 * Check employees with no bank account number yet the company has bank settings and payment methid is electonic transfer
	 * @param int $comp_id
	 * @param int $psa_id
	 * @param int $emp_id
	 * @param int $acc_id
	 */
	function check_employees_no_bank_account($comp_id, $psa_id, $emp_id='', $acc_id) {
	    $CI =& get_instance();
	    $owner_acc = ($emp_id == '') ? $acc_id : '';
	    $link = base_url().$CI->uri->segment(1)."/workforce/emp_basic_information/detail_basic_pay/";
	    $bank_where = array(
	        "comp_id" => $comp_id
	    );
	    $bank_exist = get_table_info("bank_settings",$bank_where);
	    $e_where = array(
	        "module" => "bank_account",
	        "company_id" => $comp_id,
	        "emp_id" => ($emp_id != '') ? $emp_id : 0
	    );
	    $mb_employees = get_message_board_emp_who($e_where);
	
	    if ($bank_exist) {
	        $employees = get_employees_no_bank_account($comp_id);
	        $t_emp = count($employees);
	        $t_mb = count($mb_employees);
	        if ($employees) {
	            if (!$mb_employees) {
	                foreach ($employees as $row) {
	                    $err_msg = "You have an employee which doesn't have a bank account number. Please click on the <a href='{$link}".$row->account_id."' target='_blank'><strong>link</strong></a> to assign a bank account number to this employee.";
	                    send_to_message_board($psa_id, $emp_id, $acc_id, $comp_id, $err_msg, "system", "error", $owner_acc, $row->emp_id, "bank_account");
	                }
	            } else {	                
	                for ($i = 0; $i < $t_emp; $i++) {
	                    $not_found = true;
	                    for ($j = 0; $j < $t_mb; $j++) {
	                        if ($employees[$i]->emp_id == $mb_employees[$j]->emp_who ) {
	                            $not_found = false;
	                            break;
	                        }
	                    }
	                    if ($not_found) {
	                        $err_msg = "You have an employee which doesn't have a bank account number. Please click on the <a href='{$link}".$employees[$i]->account_id."' target='_blank'><strong>link</strong></a> to assign a bank account number to this employee.";
	                        send_to_message_board($psa_id, $emp_id, $acc_id, $comp_id, $err_msg, "system", "error", $owner_acc, $employees[$i]->emp_id, "bank_account");
	                    }
	                }
	            }
	        }
	    }
	}
	
	/**
	 * Get employee who in message board
	 * @param array $where
	 * @return boolean|object
	 */
	function get_message_board_emp_who($where) {
	    if ($where) {
	        $CI =& get_instance();
	        $CI->db->select("emp_who");
	        $CI->db->where($where);
	        $q = $CI->db->get("message_board");
	        $r = $q->result();
	        return ($r) ? $r : false;
	    }
	}
	
	/**
	 * Get employees with no bank account number if payment methid is electronic transfer
	 * @param int $comp_id
	 * @return boolean|object
	 */
	function get_employees_no_bank_account($comp_id)
	{
	    $CI =& get_instance();
	    $key = konsum_key();
	    $where = array(
	        "employee.company_id" => $comp_id,
	        "employee_payroll_information.payment_method" => 'Electronic Transfer'
	    );
        # decrypt varbinary field
	    $w_string = "(AES_DECRYPT(employee_payroll_account_info.card_id,'".$key."') IS NULL OR AES_DECRYPT(employee_payroll_account_info.card_id,'".$key."') = '')";
	    $CI->db->select(array("employee.emp_id", "employee.account_id"));
	    $CI->db->where($where);
	    $CI->db->where($w_string);
	    $CI->db->join('employee_payroll_information', 'employee_payroll_information.emp_id = employee.emp_id', 'LEFT OUTER');
	    $CI->db->join('employee_payroll_account_info', 'employee_payroll_account_info.emp_id = employee.emp_id', 'LEFT OUTER');
	    $q = $CI->db->get("employee");
	    $r = $q->result();
	    return ($r) ? $r : false;
	}
	
	/**
	 * Get employees with no basic pay
	 * @param int $comp_id
	 * @return boolean|object
	 */
	function get_employees_no_basic_pay($comp_id)
	{
	    $CI =& get_instance();
	    $where = array(
	        "employee.company_id" => $comp_id
	    );
	    $w_string = "(basic_pay_adjustment.current_basic_pay IS NULL OR basic_pay_adjustment.current_basic_pay = '')";
	    $CI->db->select(array("employee.emp_id", "employee.account_id"));
	    $CI->db->where($where);
	    $CI->db->where($w_string);
	    $CI->db->join('basic_pay_adjustment', 'employee.emp_id = basic_pay_adjustment.emp_id', 'LEFT OUTER');
	    $q = $CI->db->get("employee");
	    $r = $q->result();
	    return ($r) ? $r : false;
	}
	
	/**
	 * Get employees with no payroll group
	 * @param int $comp_id
	 * @return boolean|object
	 */
	function get_employees_no_payroll_group($comp_id)
	{
	    $CI =& get_instance();
	    $where = array(
	        "employee.company_id" => $comp_id
	    );
	    $w_string = "(employee_payroll_information.payroll_group_id IS NULL OR employee_payroll_information.payroll_group_id = '')";
	    $CI->db->select(array("employee.emp_id", "employee.account_id"));
	    $CI->db->where($where);
	    $CI->db->where($w_string);
	    $CI->db->join('employee_payroll_information','employee.emp_id = employee_payroll_information.emp_id','LEFT OUTER');
	    $q = $CI->db->get("employee");
	    $r = $q->result();
	    return ($r) ? $r : false;
	}	
	
	/**
	 * Sets the message board notification as read
	 * @param int $psa_id
	 * @param int $acc_id
	 * @param int $emp_who
	 * @param string $module
	 */
	function mark_notification_done($psa_id, $acc_id, $emp_who, $module)
	{
	    $CI =& get_instance();
	    if (is_numeric($emp_who)) {
	        $message_board_id = '';
	        # check first if this employee is in message board
	        $where = array(
	        'emp_who' => $emp_who,
	        'module' => $module
	        );
	        $CI->db->select("message_board_id");
	        $CI->db->where($where);
	        $query = $CI->edb->get('message_board');
	        $row = $query->row();
	        $query->free_result();
	         
	        if ($row) {
	            $message_board_id = $row->message_board_id;
	            # then check if this notification is already read
	            $where_read = array(
	            "message_board_id" => $message_board_id,
	            'status' => 'Active'
	                );
	            $CI->db->where($where_read);
	            $query_read = $CI->edb->get('message_board_read');
	            $row_read = $query_read->row();
	            $query_read->free_result();
	            # update or insert
	            if ($row_read) {
	                $update_field = array(
	                    'message_board_id' => $message_board_id,
	                    'read' => '1',
	                    'date' => date("Y-m-d H:i:s")
	                );
	                $CI->db->where($where_read);
	                $CI->db->update('message_board_read',$update_field);
	            } else {
	                $field = array(
	                    'psa_id' 	=> $psa_id,
	                    'account_id' => $acc_id,
	                    'message_board_id' => $message_board_id,
	                    'date' => date("Y-m-d H:i:s")
	                );
	                $CI->db->insert('message_board_read',$field);
	            }
	        }
	    }
	}
	
	/**
	 * Remove notification errors for lacking infos of employee 
	 * @param int $psa_id
	 * @param int $acc_id
	 * @param int $emp_who
	 * @param int $module
	 */
	function remove_notification_emp_who($psa_id, $acc_id, $emp_who, $module)
	{
	    $CI =& get_instance();
	    if (is_numeric($emp_who)) {
	        $message_board_id = '';
	        # check first if this employee is in message board
	        $where = array(
    	        'emp_who' => $emp_who,
    	        'module' => $module
	        );
	        $CI->db->select("message_board_id");
	        $CI->db->where($where);
	        $query = $CI->edb->get('message_board');
	        $row = $query->row();
	        $query->free_result();
	    
	        if ($row) {
	            edelete('message_board', $where);
	        }
	    }
	}
	
	/**
	 * Send notification for employees with missing info(basic pay, payroll group, bank number)
	 * @param int $comp_id
	 * @param int $psa_id
	 * @param int $emp_id
	 * @param int $acc_id
	 */
	function push_notifications_employees_missing_data($comp_id, $psa_id, $emp_id, $acc_id)
	{
	    // send error notification to message board for employees with no basic pay
	    check_employees_no_basic_pay($comp_id, $psa_id, $emp_id, $acc_id);
	     
	    // send error notification to message board for employees with no payroll group
	    check_employees_no_payroll_group($comp_id, $psa_id, $emp_id, $acc_id);
	     
	    // send error notification to message board for employees with no bank account number
	    check_employees_no_bank_account($comp_id, $psa_id, $emp_id, $acc_id);
	    
	}
	/**
	 * Birthday Alerts and Anniversary Alerts
	 * @param unknown $comp_id
	 */
	
	function check_employee_birthdate($comp_id) {
		$CI =& get_instance();
		if (is_numeric($comp_id)) {
			
			$sel = array("e.emp_id", "e.dob");
			$sel2 = array("e.first_name", "e.last_name", "a.email");
			$w = array(
				'e.company_id' => $comp_id,
				'e.status'=> 'Active',
				'a.user_type_id'=> '5',
				'a.deleted'=> '0',
				'epi.employee_status'=> 'Active',
				'DATE_FORMAT(e.dob, "%m-%d") ='=> date("m-d")
			);
			$CI->db->select($sel);
			$CI->edb->select($sel2);
			$CI->db->where($w);
			$CI->edb->join('employee_payroll_information AS epi','epi.emp_id=e.emp_id','INNER');
			$CI->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
			$q = $CI->edb->get('employee AS e');
			$row = $q->result();
			$q->free_result();
			return ($row) ? $row : FALSE;
			
		}
	}
	function check_employee_anniversary($comp_id) {
		$CI =& get_instance();
		if (is_numeric($comp_id)) {
				
			$sel = array("e.emp_id","epi.date_hired");
			$sel2 = array("e.first_name", "e.last_name");
			$w = array(
					'e.company_id' => $comp_id,
					'e.status'=> 'Active',
					'a.user_type_id'=> '5',
					'a.deleted'=> '0',
					'epi.employee_status'=> 'Active',
					'DATE_FORMAT(epi.date_hired, "%m-%d") ='=> date("m-d")
			);
			$CI->db->select($sel);
			$CI->edb->select($sel2);
			$CI->db->where($w);
			$CI->edb->join('employee_payroll_information AS epi','epi.emp_id=e.emp_id','INNER');
			$CI->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
			$q = $CI->edb->get('employee AS e');
			$row = $q->result();
			$q->free_result();
			return ($row) ? $row : FALSE;
				
		}
	}
	function check_enabled_alert_notification($comp_id, $type="") {
		$check_exists = "";
		if($type == "birthday") {
			$where = array(
				"enable" => "yes",
				"company_id" => $comp_id
			);
			$check_exists = get_table_info("notification_birthday_settings",$where);
		}
		if($type == "anniversary") {
			$where = array(
				"enable" => "yes",
				"company_id" => $comp_id
			);
			$check_exists = get_table_info("notification_anniversary_settings",$where);
		}
		return ($check_exists) ? true : false;
	}
	function alert_notification_employee_birthday($comp_id, $psa_id,$account_id,$company_name) {
		
		$CI =& get_instance();
		$check_emp = check_employee_birthdate($comp_id);
		
		$del = array(
			"psa_id" => $psa_id,
			"company_id" => $comp_id,
			"module" => "birthday",
			'DATE_FORMAT(date, "%m-%d") <' => date("m-d")
		);
		$CI->db->delete("message_board",$del);
		
		if($check_emp) {
			
			$check_enabled = check_enabled_alert_notification($comp_id,"birthday");
			
			if($check_enabled) {
				
				foreach ($check_emp as $row) {
					
					$where = array(
						"psa_id" => $psa_id,
						"emp_who" => $row->emp_id,
						"company_id" => $comp_id,
						"module" => "birthday"
					);
					
					$check_exists = get_table_info("message_board",$where);
					
					if(!$check_exists) {
						$fname = $row->first_name;
						$last_name = $row->last_name;
						$msg_info = "Today is {$fname} {$last_name}'s birthday.";
						send_to_message_board($psa_id, $emp_id="", $account_id, $comp_id, $msg_info, "system", "information", $owner_acc="", $row->emp_id, "birthday");
						send_invitation_via_email($row->email,$comp_id,$company_name);
					}
					
				}
				
			}else{
				return false;
			}
			
		}else{
			return false;
		}
		
		
		
	}
	function alert_notification_employee_anniversary($comp_id, $psa_id,$account_id) {
	
		$CI =& get_instance();
		$check_emp = check_employee_anniversary($comp_id);
		
		$where = array(
			"psa_id" => $psa_id,
			"company_id" => $comp_id,
			"module" => "anniversary"
		);
		$CI->db->delete("message_board",$where);
		
		if($check_emp) {
			
			$check_enabled = check_enabled_alert_notification($comp_id,"anniversary");
			
			if($check_enabled) {
				
				foreach ($check_emp as $row) {
					
					$fname = $row->first_name;
					$last_name = $row->last_name;
					$msg_info = "Today is {$fname} {$last_name}'s anniversary.";
					send_to_message_board($psa_id, $emp_id="", $account_id, $comp_id, $msg_info, "system", "information", $owner_acc="", $row->emp_id, "anniversary");
					
				}
				
			}else{
				return false;
			}
			
		}else{
			return false;
		}
	
	}
	
	function send_invitation_via_email($email_address, $company_id, $company_name){
	
		$CI =& get_instance();
		
		$config['protocol'] = 'sendmail';
		$config['wordwrap'] = TRUE;
		$config['mailtype'] = 'html';
		$config['charset'] = 'utf-8';
	
		$CI->load->library('email',$config);
		$CI->email->initialize($config);
		$CI->email->set_newline("\r\n");
		$CI->email->from('notifications@ashima.ph', 'Ashima');
		$CI->email->to($email_address);
		$CI->email->subject('Birthday Greetings');
		$font_name = "'Open Sans'";
		
		$body = '
						<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
							"http://www.w3.org/TR/html4/loose.dtd">
							<html lang="en">
							<head>
							<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
							<meta name="format-detection" content="telephone=no">
							<title>Approval Request Payrun Notification</title>
							<style type="text/css">
							.ReadMsgBody {
								width: 100%;
								background-color: #ebebeb;
							}
							.ExternalClass {
								width: 100%;
								background-color: #ebebeb;
							}
							.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {
								line-height:100%;
							}
							body {
								-webkit-text-size-adjust:none;
								-ms-text-size-adjust:none;
								font-family:'.$font_name.', Arial, Helvetica, sans-serif;
							}
							body {
								margin:0;
								padding:0;
							}
							table {
								border-spacing:0;
							}
							table td {
								border-collapse:collapse;
							}
							.yshortcuts a {
								border-bottom: none !important;
							}
							</style>
							</head>
							<body>
										<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
				  <tr>
				    <td valign="top" style="background-color:#f2f2f2; text-align:center; padding:25px 0;"><a href="'.base_url().'"><img src="'.(newsletter_logo($company_id)).'" height="96" alt=" "></a></td>
				  </tr>
				  <tr>
				    <td style="padding:40px 0 50px;" valign="top" align="center"><table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
				        <tr>
				          <td valign="top" align="center"><table width="600px" style="width:600px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
				              <tr>
				                <td valign="top">
		
		
			
				                  <p style="font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:200; color:#2f3335; font-size:20px; margin:22px 0 45px; line-height:26px;text-align:center;">
									We hope this wonderful day will fill you with joy,<br>
									great surprises and lots of blessings.
									</p>
				                   <p style="font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#2f3335; font-size:30px; margin:0 0 20px; line-height:26px;text-align:center;">
									Happy Birthday from <strong>'.$company_name.'!</strong>
									</p>
			                  		<p style="text-align: center; margin: 74px 0px 35px;"><img width="97" height="97" alt=" " src="'.base_url().'/assets/theme_2015/images/bday_poppers.png"></p>
				              </tr>
				            </table></td>
				        </tr>
				      </table></td>
				  </tr>
				  <tr>
				    <td valign="top" align="center" style="background-color:#f2f2f2; padding:20px 0"><table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
				        <tr>
				          <td valign="top"><img src="'.base_url().'assets/theme_2015/images/newsletter/img-fb-twitter.jpg" alt=" " border="0" usemap="#Map"></td>
				          <td valign="middle" style="text-align:right;"><a href="#"><img src="'.base_url().'assets/theme_2015/images/newsletter/img-konsum-logo.png" alt=" "></a></td>
				        </tr>
				      </table></td>
				  </tr>
				</table>
				<map name="Map">
				  <area id="facebook-link" shape="rect" coords="-22,1,199,15" href="http://facebook.com/ashimaPH">
							  <area id="twitter-link" shape="rect" coords="1,15,275,42" href="http://twitter.com/ashimaPH">
				</map>
		
		</body>
		</html>
	';
	
		$CI->email->message($body);
		$email_check = $CI->email->send();
		$x = $CI->email->print_debugger();
		
	}
	
	function in_array_foreach_custom($needle, $haystack=array(), $strict = false) {
	    $holder = array();
	    if($haystack != null){
	        foreach ($haystack as $key => $item) {
	            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_foreach_custom($needle, $item, $strict))) {
	                array_push($holder, (object) $haystack[$key]);
	            }
	        }
	    }
	    return ($holder != null) ? (object) $holder : false;
    }
    
    function check_employee_leaves_status($params) {
    
        $CI =& get_instance();
        
        $array_leaves = array();
        
        $sel = array(
            "ea.emp_id",
            "ea.date_start",
            "ea.date_end",
            "ea.work_schedule_id",
            "ea.leave_type_id",
            "ea.shift_date",
            "l.leave_type",
            "ea.flag_parent",
            "ea.leave_application_status"
        );
        
        $wr = array(
            "ea.company_id" => $params["company_id"],
            "ea.shift_date >=" => $params["date_from"],
            "ea.shift_date <=" => $params["date_to"]
        );
        $CI->db->select($sel);
        $CI->db->where($wr);
        $CI->db->where_in("ea.leave_application_status",array("approve","pending"));
        if(count($params["emp_id"]) > 0) {
            $CI->db->where_in("emp_id",$params["emp_id"]);
        }
        $CI->db->join("leave_type AS l", "l.leave_type_id = ea.leave_type_id", "LEFT");
        $q = $CI->db->get("employee_leaves_application AS ea");
        
        $result = $q->result();
        $q->free_result();
        
        $html = "";
        
        if($result) {
            
            $flag_action = 0;
            
            if($params["source"] == "indi_update") {
                $flag_action = 1;
                $emp_id = $params["emp_id"];
                $full_name = emp_fname($emp_id);
            }
            if($params["source"] == "assign_shift") {
                $emp_id = $params["emp_id"];
                $c_emp = count($emp_id);
                if($c_emp == 1) {
                    $emp_id = $params["emp_id"][0];
                    $full_name = emp_fname($emp_id);
                    $flag_action= 1;
                }
            }
            if($params["source"] == "import_shifts") {
                $flag_action = 2;
            }
            
            $txt1 = "Oh snap! Shift Change has a conflict with an approved or a pending leave application.";
            $txt2 = "If you wish to continue with the shift change for the above date/s click on continue.<BR>Please advise your employees to manually cancel their leaves.";
            
            if($params["source"] == "request_schedule") {
                $flag_action = 3;
                $txt1 = "Uh oh... Looks like your shift change request has a conflict with an approved or a pending leave application.";
                $txt2 = "If you wish to continue with the shift change for the above date/s click on continue.  Please manually cancel your approved or pending leave.";
            }
            
            if($flag_action != 2) {
                
                $html .= "<div class='txt-msg-err'>{$txt1}</div><br>";
                $html .= "<div class='tbl-affected-emps'><table border='0'>";
                
                if($flag_action == 1) {
                    $html .= "<tr>";
                    $html .= "<td style='padding: 0px 0px 4px 2px;' colspan='2'><strong>Employee</strong>: {$full_name}</td>";
                    $html .= "</tr>";
                }
                // $html .= "<tr>";
                // if($flag_action == 0 && $flag_action != 3) {
                //     $html .= "<th>Employee</th>";
                // }
                // $html .= "<th>Leave Date</th>";
                // $html .= "<th>Leave Type</th>";
                // $html .= "<th>Status</th>";
                // $html .= "</tr>";
                foreach ($result as $row) {
                    
                    if($row->flag_parent != "yes") {
                        
                        $full_name = emp_fname($row->emp_id);
                        $html .= "<tr>";
                        if($flag_action == 0 && $flag_action != 3) {
                            $html .= "<td>{$full_name}</td></tr><tr>";
                        }
                        $html .= "<td>Leave Date: ".date("F d, Y",strtotime($row->shift_date))."</td></tr>";
                        $html .= "<tr><td>Leave Type: {$row->leave_type}</td></tr>";
                        $html .= "<tr><td>Status: ".ucfirst($row->leave_application_status)."</td>";
                        $html .= "</tr><tr></tr>";
                        
                    }
                    
                }
                $html .= "</table></div>";
                
                $html .= "<br><div>{$txt2}</div>";
                
                $array_leaves["message"] = $html;
                
            }else{
                
                $array_app_leave = array();
                
                foreach ($result as $row) {
                    
                    if($row->flag_parent != "yes") {
                        
                        $full_name = emp_fname($row->emp_id);
                        
                        $wr_arr = array(
                            "full_name" => $full_name,
                            "leave_date" => date("F d, Y",strtotime($row->shift_date)),
                            "leave_type" => $row->leave_type,
                            "leave_status" => ucfirst($row->leave_application_status),
                        );
                        array_push($array_app_leave,(object)$wr_arr);
                        
                    }
                }
                
                $array_leaves["affected_employees"] = $array_app_leave;
                
            }
            
        }
        return $array_leaves;
    }

    function check_employee_timein_logs($company_id,$array_emp_ids) {
    
        $CI =& get_instance();
        
        $array_logs = array();
        
        if(count($array_emp_ids) > 0) {
            $todays_date = date("Y-m-d");
            $select = array(
                "work_schedule_id",
                "emp_id",
                "date",
                "time_in",
                "time_out"
            );
            $wr_timein = array(
                "date" => $todays_date,
                "comp_id" => $company_id
            );
            $CI->db->select($select);
            $CI->db->where($wr_timein);
            $CI->db->where("time_out IS NULL");
            $CI->db->where_in("emp_id",$array_emp_ids);
            $query = $CI->db->get("employee_time_in");
            $result = $query->result();
            $query->free_result();
            
            if($result) {
                foreach ($result as $r) {
                    $wr_arr = array(
                        "emp_id" => $r->emp_id,
                        "date" => $r->date,
                        "custom_search" => "{$r->emp_id}-{$r->date}"
                    );
                    array_push($array_logs,$wr_arr);
                }
            }
            
            
        }
        return $array_logs;
    }
	
	/* End of file global_helper.php */
	/* Location: ./application/helpers/global_helper.php */
	
