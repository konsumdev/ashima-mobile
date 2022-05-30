<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Account_model extends CI_Model {
	
	public function get_account($user,$pass) 
	{
		if ($user && $pass) {
			$select = array(
				'accounts.account_id',
				'accounts.account_type_id',
				'accounts.payroll_system_account_id',
				'accounts.user_type_id',
				'payroll_system_account.sub_domain',
				'company.company_name',
				'employee.emp_id',
				'accounts.enable_generic_privilege'
			);
			$where = array(
				'accounts.email' 			=> $user,
				'accounts.password' 		=> $pass,
				'accounts.account_type_id'  => 2,
				'accounts.deleted'			=> 0,
				#added
				'payroll_system_account.status'=>'Active'
				
			);
			$this->db->select("*");
			$this->edb->select($select);
			$this->db->select('company.sub_domain AS company_subdomain');
			$this->edb->where($where);
			$where2 = array(
					'payroll_system_account.choose_plans_id !='=>'5'
			);
			$this->db->where($where2);
			$this->edb->join('payroll_system_account','accounts.payroll_system_account_id = payroll_system_account.payroll_system_account_id','left');
			$this->edb->join('employee','accounts.account_id = employee.account_id','left');
			$this->edb->join('company','employee.company_id = company.company_id','left');
			
			$q = $this->edb->get('accounts');
			$result = $q->row();
			
			return ($result) ? $result : false;
			
		}
	} 
	
	/**
	 * Get Account for mobile
	 * @param unknown_type $mobile
	 * @param unknown_type $pass
	 */
	public function get_account_mobile($mobile,$pass) 
	{
		if ($mobile && $pass) {
			$konsum_key = konsum_key();
			$select = array(
				'accounts.account_id',
				'accounts.account_type_id',
				'accounts.payroll_system_account_id',
				'accounts.user_type_id',
				'payroll_system_account.sub_domain',
				'company.company_name',
				'employee.emp_id',
				'accounts.enable_generic_privilege'
			);
			
			$this->edb->select($select);
			$this->db->select("*");
			$this->db->select('company.sub_domain AS company_subdomain');
			$this->edb->join('payroll_system_account','accounts.payroll_system_account_id = payroll_system_account.payroll_system_account_id','left');
			$this->edb->join('employee','accounts.account_id = employee.account_id','left');
			$this->edb->join('company','employee.company_id = company.company_id','left');
			$this->db->where("
				AES_DECRYPT(accounts.login_mobile_number,'{$konsum_key}') = '".$mobile."'
    			AND accounts.account_type_id = '2' AND accounts.deleted ='0'
    			AND accounts.verified_status = 'verified' AND
    			payroll_system_account.status ='Active' AND
    			accounts.password = '".$pass."' AND flag_primary = 'login_mobile_number'
			    OR AES_DECRYPT(accounts.login_mobile_number_2,'{$konsum_key}') = '".$mobile."'
    			AND accounts.account_type_id = '2' AND accounts.deleted ='0'
    			AND accounts.verified_status_2 = 'verified' AND
    			payroll_system_account.status ='Active' AND
    			accounts.password = '".$pass."' AND flag_primary = 'login_mobile_number_2'", NULL, FALSE); // encrypt
			/** ADDED MAY 3 **/
			$where2 = array(
				'payroll_system_account.choose_plans_id !='=>'5'
			);
			$this->db->where($where2);
			/** END ADDED MAY 3 **/
			
			$q = $this->edb->get('accounts');
			$result = $q->row();
			
			if($result){
				if($result->user_type_id == 3){
					if($result->enable_generic_privilege == 'Inactive' || $result->enable_generic_privilege == ''){
						return false;
					}
				}
			}
			return ($result) ? $result : false;
		}
	}
	
	public function get_account_mobile_old_backup($mobile,$pass) // old name kay get_Account_mobile_old
	{
		if ($mobile && $pass) {
			$select = array(
					'accounts.account_id',
					'accounts.account_type_id',
					'accounts.payroll_system_account_id',
					'accounts.user_type_id',
					'payroll_system_account.sub_domain',
					'company.company_name',
					'employee.emp_id',
					'accounts.enable_generic_privilege'
			);
			$where = array(
					'accounts.login_mobile_number' 	=> $mobile,
					'accounts.password' 			=> $pass,
					'accounts.account_type_id'  	=> 2,
					'accounts.deleted'				=> 0,
					'accounts.verified_status'		=> 'verified',
					#added
					'payroll_system_account.status'=>'Active'
			);
			$this->edb->select($select);
			$this->db->select("*");
			$this->db->select('company.sub_domain AS company_subdomain');
			$this->edb->where($where);
			$this->edb->join('payroll_system_account','accounts.payroll_system_account_id = payroll_system_account.payroll_system_account_id','left');
			$this->edb->join('employee','accounts.account_id = employee.account_id','left');
			$this->edb->join('company','employee.company_id = company.company_id','left');
				
			$q = $this->edb->get('accounts');
			$result = $q->row();
				
			if($result){
				if($result->user_type_id == 3){
					if($result->enable_generic_privilege == 'Inactive' || $result->enable_generic_privilege == ''){
						return false;
					}
				}
			}
			return ($result) ? $result : false;
		}
	}
	
	
	public function get_admin_account($user,$pass)
	{
		$select = array(
			'account_id',
			'account_type_id'
		);
		
		$where = array(
			'email' 		  => $user,
			'password' 		  => $pass,
			'account_type_id' => 1
		);	
		$this->edb->where($where);
		$q = $this->edb->get('accounts');
		$result = $q->row();
		
		return ($result) ? $result : false;
	}
	
	public function check_employee($account_id)
	{
		$where = array(
			'account_id' => $account_id,
			'status'	 => 'Active'
		);		
		$this->edb->where($where);
		$q = $this->edb->get('employee');
		$result = $q->row();
		
		return ($result) ? $result : false;
	}
	
	public function get_company($subdomain)
	{
		$where = array(
			'sub_domain' => $subdomain,
			'status'	 => 'Active'
		);
		$this->db->where($where);
		$q = $this->db->get('company');
		$result = $q->row();
		
		return ($result) ? $result : false;
	}
	
	/**** CHRIS CODES ADDED ****/
	/**
	*	DASHBOARD ACCESS ENABLES YOU TO DEFINE IF THE PERSON BEHIND THIS DASHBOARD IS THE RIGHT PERSON
	*	@param int $psa_id ( payroll_system_account_id )
	*	@param string $sub_domain
	*	@return boolean
	*/
	public function dashboard_access($psa_id,$sub_domain)
	{
		if (is_numeric($psa_id) && $sub_domain !="") {
			$where = array(
				'payroll_system_account_id' => $psa_id,
				'sub_domain'				=> $sub_domain
			);
			$this->edb->where($where);
			$q = $this->edb->get('payroll_system_account');
			$result = $q->row();
			
			return $result;
		}else{
			return false;
		}
	}
	/**** END CHRIS CODES ****/
	
	
	/**
	 * This function checks account details whether its an company ownere, employee via account_id or employee via emp_id
	 * Check account details
	 * @param int $account_id ( if employee_via_emp_id is choosen then disregard $account_id to emp_id )
	 * @param string $user_type (@example company_owner,employee,employee_via_emp_id)
	 * @return object
	 */
	public function get_profile($account_id,$user_type="company_owner"){
		switch($user_type):
			case "company_owner":
				$where = array(
					'a.account_type_id' => '2',	
					'a.account_id'		=> $account_id
				);
				$this->edb->where($where);
				$this->edb->join('company_owner AS co','a.account_id = co.account_id','left');
				$query = $this->edb->get('accounts AS a');
				
				$row = $query->row();
				$query->free_result();
				return $row;
			break;
			case "employee":
				$this->edb->where('a.account_id',$account_id);
				$this->edb->join('employee AS e','e.account_id = a.account_id','left');
				$query = $this->edb->get('accounts AS a');
				
				$row = $query->row();
				$query->free_result();
				return $row;
			break;
			case "employee_via_emp_id": // CHECKING AN EMPLOYEE VIA EMP_ID INSTEAD OF ACCOuNT_ID
				$this->edb->where($where);
				$this->edb->join('employee AS e','e.account_id = a.account_id','left');
				$query = $this->edb->get('accounts AS a');
				
				$row = $query->row();
				$query->free_result();
				return $row;
			break;
		endswitch;
	}
	
	/**
	 * Check Static IP Address
	 * @param unknown_type $emp_id
	 * @param unknown_type $ip
	 */
	public function check_static_ip_address($emp_id,$ip){
		$company_id = $this->check_company_id($emp_id);
		
		if($company_id != FALSE){
			
			$ip_get = explode('.',$ip);
			$w = array(
				"company_id"=>$company_id,			
				"category" => 0	
			);
			$this->db->where($w);
			$q = $this->db->get("employee_ip_address");
			$r = $q->result();
			if($r){				
				foreach($r as $row){
					
					$ip_no = explode('.',$row->ip_address);
					if($ip_no[0] == $ip_get[0] && $ip_no[1]==$ip_get[1] )
						return true;
				}			
				
			}
				return false;
					
		}else{
			return TRUE;
		}
	}
	
	/**
	 * Check Company ID
	 * @param unknown_type $emp_id
	 */
	public function check_company_id($emp_id){
		$w = array(
			"e.emp_id"=>$emp_id,
		);
		$this->db->where("e.status","Active");
		$this->edb->where($w);
		$this->edb->join("company_approvers AS ca","e.account_id = ca.account_id","INNER");
		$this->edb->join("user_roles AS ur","ca.users_roles_id = ur.users_roles_id","INNER");
		$this->edb->join("privilege AS p","ur.users_roles_id = p.users_roles_id","INNER");
		$q = $this->edb->get("employee AS e");
		
		$r = $q->row();
		return ($r) ? $r->company_id : FALSE ;
	}
	
	##### ADDED OCT 30 , 2015
	
	/**
	 * VALIDATION BY SEPARATE
	 * @param unknown $mobile
	 * @param string $pass
	 */
	public function get_account_mobile_validity_separate($mobile,$pass="")
	{
		if ($mobile) {
			$konsum_key = konsum_key();
			$select = array(
					'accounts.account_id',
					'accounts.account_type_id',
					'accounts.payroll_system_account_id',
					'accounts.user_type_id',
					'payroll_system_account.sub_domain',
					'company.company_name',
					'employee.emp_id',
					'accounts.enable_generic_privilege'
			);
			$pasword_sql = '';
			if($pass !==""){
				$pasword_sql =" AND accounts.password ='".$pass."'";
			}
			$sql = "
				AES_DECRYPT(accounts.login_mobile_number,'{$konsum_key}') = '".$mobile."'
    			AND accounts.account_type_id = '2' AND accounts.deleted ='0'
    			AND accounts.verified_status = 'verified' AND
    			payroll_system_account.status ='Active' ".$pasword_sql." AND flag_primary ='login_mobile_number'
			    OR AES_DECRYPT(accounts.login_mobile_number_2,'{$konsum_key}') = '".$mobile."'
    			AND accounts.account_type_id = '2' AND accounts.deleted ='0'
    			AND accounts.verified_status_2 = 'verified' AND
    			payroll_system_account.status ='Active' ".$pasword_sql." AND flag_primary ='login_mobile_number2'
			";
			 
			$this->db->where($sql, NULL, FALSE); // encrypt
			$this->edb->select($select);
			$this->db->select('company.sub_domain AS company_subdomain');
			$this->edb->join('payroll_system_account','accounts.payroll_system_account_id = payroll_system_account.payroll_system_account_id','left');
			$this->edb->join('employee','accounts.account_id = employee.account_id','left');
			$this->edb->join('company','employee.company_id = company.company_id','left');
			$q = $this->edb->get('accounts');
			$result = $q->row();
			
			if($result){
				if($result->user_type_id == 3){
					if($result->enable_generic_privilege == 'Inactive' || $result->enable_generic_privilege == ''){
						return false;
					}
				}
			}
			
			return ($result) ? $result : false;
		}
	}
	
	/**
	 * VALIDATION BY SEPARATE
	 * @param unknown $mobile
	 * @param string $pass
	 */
	public function get_account_mobile_validity_separate_old_backup($mobile,$pass="") // mao ning get_account_mobile_validity_separate FEB 23,2016
	{
		if ($mobile) {
			$select = array(
					'accounts.account_id',
					'accounts.account_type_id',
					'accounts.payroll_system_account_id',
					'accounts.user_type_id',
					'payroll_system_account.sub_domain',
					'company.company_name',
					'employee.emp_id',
					'accounts.enable_generic_privilege'
			);
			$where = array(
					'accounts.login_mobile_number' 	=> $mobile,
					'accounts.account_type_id'  	=> 2,
					'accounts.deleted'				=> 0,
					'accounts.verified_status'		=> 'verified',
					#added
					'payroll_system_account.status'=>'Active'
			);
			if($pass !==""){
				$where['accounts.password'] = $pass;
			}
				
			$this->edb->select($select);
			$this->db->select('company.sub_domain AS company_subdomain');
			$this->edb->where($where);
			$this->edb->join('payroll_system_account','accounts.payroll_system_account_id = payroll_system_account.payroll_system_account_id','left');
			$this->edb->join('employee','accounts.account_id = employee.account_id','left');
			$this->edb->join('company','employee.company_id = company.company_id','left');
	
			$q = $this->edb->get('accounts');
			$result = $q->row();
				
			if($result){
				if($result->user_type_id == 3){
					if($result->enable_generic_privilege == 'Inactive' || $result->enable_generic_privilege == ''){
						return false;
					}
				}
			}
				
			return ($result) ? $result : false;
		}
	}
	
	// end mao ning get_account_mobile_validity_separate FEB 23,2016
	
	/**
	 * VALIDATION SEPARATE ACCOUNT 
	 * @param string $user
	 * @param string $pass
	 */
	public function get_account_validity_separate($user,$pass="")
	{
		if ($user) {
			$select = array(
					'accounts.account_id',
					'accounts.account_type_id',
					'accounts.payroll_system_account_id',
					'accounts.user_type_id',
					'payroll_system_account.sub_domain',
					'company.company_name',
					'employee.emp_id',
					'accounts.enable_generic_privilege'
			);
			$where = array(
					'accounts.email' 			=> $user,
					'accounts.account_type_id'  => 2,
					'accounts.deleted'			=> 0,
					#added
					'payroll_system_account.status'=>'Active'
			);
			if($pass !==""){
				$where['accounts.password'] = $pass;
			}
			$this->edb->select($select);
			$this->db->select('company.sub_domain AS company_subdomain');
			$this->edb->where($where);
			
			/** ADDED MAY 3 **/
			$where2 = array(
					'payroll_system_account.choose_plans_id !='=>'5'
			);
			$this->db->where($where2);
			/** END ADDED MAY 3 **/
			
			
			$this->edb->join('payroll_system_account','accounts.payroll_system_account_id = payroll_system_account.payroll_system_account_id','left');
			$this->edb->join('employee','accounts.account_id = employee.account_id','left');
			$this->edb->join('company','employee.company_id = company.company_id','left');	
			$q = $this->edb->get('accounts');
			$result = $q->row();
			
			if($result){
				if($result->user_type_id == 3){
					if($result->enable_generic_privilege == 'Inactive' || $result->enable_generic_privilege == ''){
						return false;
					}
				}
			}
			
			return ($result) ? $result : false;		
		}
	}
	
	/**
	 * FLAG ATTEMPT ON ACCOUNTS
	 * @param int $account_id
	 * @param int $attempts
	 */
	public function flag_attempt_on_accounts($user,$mobile=""){
		$attemp_account = false;
		if($user !==""){
			$attemp_account = $this->get_password_settings($user);
		}else if($mobile !==""){
			$attemp_account = $this->get_password_settings('',$mobile);
		}
			$return = array('success'=>0,'limit_attempts'=>0,'date_sentence'=>0,'block_type_by'=>'',"days_min_hours_diff"=>'');
			if($attemp_account){
				$account_id = $attemp_account->account_id;
				$login_attempted = $attemp_account->login_attempted;
				$login_attempts_date = $attemp_account->login_attempts_date;
				$strict_login_attempts = $attemp_account->login_attempts;
				$date_attempt = $attemp_account->login_attempts_date;
				$date_sentence_block = $attemp_account->date_sentence_block != NULL ? date("Y-m-d H:i:s",strtotime($attemp_account->date_sentence_block)) : '';
				
				$account_blck_duration_type = $attemp_account->account_blck_duration_type;
				$account_blck_duration_val = $attemp_account->account_blck_duration_val;
				$where = array(
					'deleted'=>'0',
					'account_id'=>$account_id
				);
				$field = array(
					
				);
				$date_now = strtotime(date("Y-m-d"));
				$date_now_full = date("Y-m-d H:i:s");
				$date_attempt_compare = strtotime(date("Y-m-d",strtotime($login_attempts_date)));
				$date_attempt = trim($attemp_account->login_attempts_date);
				
				if(is_numeric($account_id) && $account_id !=="") {
					$return['block_type_by'] = $account_blck_duration_type;
					
					if($date_sentence_block !=="" ){
						$date_sentence_strto = strtotime($date_sentence_block);
						$date_now_strto = strtotime($date_now_full);
						
						if($date_sentence_strto > $date_now_strto) {
							if($account_blck_duration_type == 'minutes'){
								$get_minus = ($date_sentence_strto - $date_now_strto) /60;
								$return['days_min_hours_diff'] = $get_minus;
							}else if($account_blck_duration_type == 'hours'){
								$hours = ($date_sentence_strto - $date_now_strto) / 60 /60;
								$return['days_min_hours_diff'] = $hours;
							}else if($account_blck_duration_type == 'days'){
								$get_days = ($date_sentence_strto - $date_now_strto) / (60*60*24);
								$return['days_min_hours_diff'] = $get_days;
							}
							$return['success'] = 0;
							$return['date_sentence'] = 1;
							$return['ggggg'] = '3';
							
							### RESET NATO ANG ATTEMPTS LANG GURO
							$field_u = array(
									'login_attempted'=>''
							);
							$this->db->where($where);
							$this->db->update('accounts',$field_u);
							#### RESET SA NATO ANG ATTEMPTS
							return $return;
						}else{
							$return['pantat'] = '1222';
							$field_u = array(
								'date_sentence_block'=>''
							);
							$this->db->where($where);
							$this->db->set('date_sentence_block', 'NULL', FALSE);
							$this->db->update('accounts',$field_u);
						}
					}
					
					###<<<<<<<<<<<<<<<<< LOGIN ATTEMPTS >>>>>>>>>>>>>>>>>>>>>>>>>>
					if($date_attempt == ''){ # KONG WALA GALi SUD ANG ATTEMPT DATE
						$field['login_attempted'] = 1;
						$field['login_attempts_date'] = date("Y-m-d H:i:s");
							
					}else if($date_attempt_compare == $date_now){ # KONG ANG DATE KAY SAME SILA 
						$count_attempt = $attemp_account->login_attempted;
						if($count_attempt == 0){
							$field['login_attempted'] = 1;
							$field['login_attempts_date'] = date("Y-m-d H:i:s");
						}else{
							$field['login_attempted'] = $count_attempt+1;
							$field['login_attempts_date'] = date("Y-m-d H:i:s");
						}
					}
					
					if($date_attempt_compare < $date_now){
						$field['login_attempted'] = 1;
						$field['login_attempts_date'] = date("Y-m-d H:i:s");
					}
					####<<<<<<<<<<<<<<<<<< END LOGIN ATTEMPLATES >>>>>>>>>>>>>>>>>>>>>>>>>>
					$flag_block_duration = 0; # FLAG NI NAKO KONG MO KAABOT NA GALI SIYA SA ATTEMPTS KAY IYANG E TRUE IF LA FALSE
					if(count($field)>0) { 
						if(isset($field['login_attempted']) && $field['login_attempted'] >=  $strict_login_attempts){
							$return['success'] = 0;
							$return['limit_attempts'] = 1;
							$this->session->set_userdata("blocked[".$account_id."]","false");
							$field_add = array();
							if($account_blck_duration_type){
								$todays_date = date("Y-m-d H:i:s");
								if($account_blck_duration_type == 'minutes'){
									$field_add['date_sentence_block'] = date("Y-m-d H:i:s",strtotime($todays_date."+".$account_blck_duration_val." mins"));
									$flag_block_duration = 1;
								}else if($account_blck_duration_type == 'hours'){
									$field_add['date_sentence_block'] = date("Y-m-d H:i:s",strtotime($todays_date."+".$account_blck_duration_val." hours"));
									$flag_block_duration = 1;
								}else if($account_blck_duration_type == 'days'){
									$field_add['date_sentence_block'] = date("Y-m-d H:i:s",strtotime($todays_date."+".$account_blck_duration_val." days"));
									$flag_block_duration = 1;
								}
							}
							eupdate('accounts',$field_add,$where);
							$return['h']= 'ssssss';
						}else{
							$return['success'] = 0;
							$return['limit_attempts'] = 0;
							if(count($field) > 0){
								$this->db->set('date_sentence_block', 'NULL', FALSE);
								eupdate('accounts',$field,$where);
								$this->session->set_userdata("blocked[".$account_id."]","false");
							}
						}
					}
				}
				
				
			/*	echo "<h1>where</h1>";
				p($where);
				echo "<h1>field</h1>";
				p($field);
				echo "<h1>all data</h1>";
				p($attemp_account);
			*/
				return $return;
			} else{
				return false;
			}
	}
	
	public function	date_flag_attempt_on_accounts($user,$mobile=""){
		$attemp_account = false;
		if($user !==""){
			$attemp_account = $this->get_password_settings($user);
		}else if($mobile !==""){
			$attemp_account = $this->get_password_settings('',$mobile);
		}
		$return = array('success'=>0,'limit_attempts'=>0,'date_sentence'=>0,'block_type_by'=>'',"days_min_hours_diff"=>'','reset_type'=>'','password_expired'=>'','count_expired_days'=>'');
		if($attemp_account){
			$account_id = $attemp_account->account_id;
			$login_attempted = $attemp_account->login_attempted;
			$login_attempts_date = $attemp_account->login_attempts_date;
			$strict_login_attempts = $attemp_account->login_attempts;
			$date_attempt = $attemp_account->login_attempts_date;
			$date_sentence_block = $attemp_account->date_sentence_block != NULL ? date("Y-m-d H:i:s",strtotime($attemp_account->date_sentence_block)) : '';
			$reset_type = $attemp_account->reset_type;
			$reset_type_value = $attemp_account->reset_type_value;
			$account_blck_duration_type = $attemp_account->account_blck_duration_type;
			$account_blck_duration_val = $attemp_account->account_blck_duration_val;
			$where = array(
					'deleted'=>'0',
					'account_id'=>$account_id
			);
			$field = array(
						
			);
			$date_now = strtotime(date("Y-m-d"));
			$date_now_full = date("Y-m-d H:i:s");
			$date_attempt_compare = strtotime(date("Y-m-d",strtotime($login_attempts_date)));
			$date_attempt = trim($attemp_account->login_attempts_date);
	
			if(is_numeric($account_id) && $account_id !=="") {
				$return['block_type_by'] = $account_blck_duration_type;
				$return['reset_type'] = $reset_type;
				$return['reset_type_value'] = $reset_type_value;
				if($date_sentence_block !=="" ){
					$date_sentence_strto = strtotime($date_sentence_block);
					$date_now_strto = strtotime($date_now_full);
					if($date_sentence_strto > $date_now_strto) {
						if($account_blck_duration_type == 'minutes'){
							$get_minus = ($date_sentence_strto - $date_now_strto) /60;
							$return['days_min_hours_diff'] = $get_minus;
						}else if($account_blck_duration_type == 'hours'){
							$hours = ($date_sentence_strto - $date_now_strto) / 60 /60;
							$return['days_min_hours_diff'] = $hours;
						}else if($account_blck_duration_type == 'days'){
							$get_days = ($date_sentence_strto - $date_now_strto) / (60*60*24);
							$return['days_min_hours_diff'] = $get_days;
						}
						$return['success'] = 0;
						$return['date_sentence'] = 1;
						$return['ggggg'] = '3';
						return $return;
					}else{
						$return['pantat'] = '1222';
					}
				}
				
				if($reset_type !==""){
					if($reset_type == '-1'){ # NEVER
						
					}else if($reset_type == '0'){ #30 Days
						if($attemp_account->password_changed_date !=="" && strlen($attemp_account->password_changed_date)>0){
							$password_changed_date =  idates_slash($attemp_account->password_changed_date);
							if($password_changed_date !=="" && $password_changed_date !==NULL && strlen($password_changed_date)>0){
								$check_date_today = strtotime(date("Y-m-d"));
								$strto_pcd = strtotime($password_changed_date);
								$result_minus_date = ($check_date_today - $strto_pcd) / (60*60*24);
								if($result_minus_date > 30){
									$return['password_expired'] = 1;
									$return['count_expired_days'] = $result_minus_date - 30;
								}
							}
						}
					}else if($reset_type == '1'){ #CUSTOM
						if($attemp_account->password_changed_date !=="" && strlen($attemp_account->password_changed_date)>0){
							$password_changed_date =  idates_slash($attemp_account->password_changed_date);
							if($password_changed_date !=="" || $password_changed_date !==NULL && strlen($password_changed_date)>0){
								$check_date_today = strtotime(date("Y-m-d"));
								$strto_pcd = strtotime($password_changed_date);
								$result_minus_date = ($check_date_today - $strto_pcd) / (60*60*24);
								if($reset_type_value !==""){
									if($result_minus_date > $reset_type_value){
										$return['password_expired'] = 1;
										$return['count_expired_days'] = $result_minus_date - $reset_type_value;
									}
								}
							}
						}
					}
				}
				
				return $return;
			}
		} else{
			return false;
		}
	}
	
	/**
	 * CHECK OF OUR PASSWORD SETTINGS
	 * @param string $email optional
	 * @param string $login_mobile optional
	 * @return object
	 */
	public function get_password_settings($email="",$login_mobile=""){
		$konsum_key = konsum_key();
		if($email !==""){
			$this->db->where("AES_DECRYPT(a.email,'{$konsum_key}') = '".$this->db->escape_str($email)."' AND a.deleted = '0'", NULL, FALSE); // encrypt
		}
		if($login_mobile !=="") {
			$this->db->where("
					AES_DECRYPT(a.login_mobile_number,'{$konsum_key}') = '".$this->db->escape_str($login_mobile)."' AND a.deleted = '0'
					OR AES_DECRYPT(a.login_mobile_number_2,'{$konsum_key}') = '".$this->db->escape_str($login_mobile)."' AND a.deleted = '0'
    		", NULL, FALSE); // encrypt
		}
		if($email !=="" || $login_mobile !==""){
			$select = array(
					'a.account_id','a.payroll_system_account_id',
					'a.login_attempted',
					'a.login_attempts_date',
					'a.date_sentence_block',
					'a.password_changed_date',
					'a.enable_generic_privilege',
					'a.user_type_id',
					'ss.security_settings_id',
					'ss.payroll_system_account_id',
					'ss.reset_type',
					'ss.reset_type_value',
					'ss.number_characters',
					'ss.login_attempts',
					'ss.account_blck_duration_type',
					'ss.account_blck_duration_val',
					'ss.allow_password_reuse',
					'ss.must_have_alpha',
					'ss.must_have_numeric',
					'ss.must_have_special',
					'ss.must_have_case',
					'ss.created_date',
					'ss.updated_date'
			);
			$this->edb->select($select);
			$this->edb->join('accounts AS a','ss.payroll_system_account_id= a.payroll_system_account_id','INNER');
			$q = $this->edb->get('security_settings AS ss');
			$r = $q->row();
			if($r){
				if($r->user_type_id == 3){
					if($r->enable_generic_privilege == 'Inactive' || $r->enable_generic_privilege == ''){
						return false;
					}
				}
			}
			return $r;
		}else{
			return false;
		}
	}
	
	
	/**
	 * CHECK OF OUR PASSWORD SETTINGS
	 * @param string $email optional
	 * @param string $login_mobile optional
	 * @return object
	 */
	public function get_password_settings_old($email="",$login_mobile=""){ ## FEB 23 , 2016 BACKUP 00----------------------------------------------------------------------------------
		$where = array(
				'a.deleted'=>'0'
		);
		if($email !==""){
			$where['a.email']= $this->db->escape_str($email);
		}
		if($login_mobile !==""){
			$where['a.login_mobile_number']=$this->db->escape_str($login_mobile);
		}
		if($email !=="" || $login_mobile !==""){
			$select = array(
					'a.account_id','a.payroll_system_account_id',
					'a.login_attempted',
					'a.login_attempts_date',
					'a.date_sentence_block',
					'a.password_changed_date',
					'a.enable_generic_privilege',
					'a.user_type_id',
					'ss.security_settings_id',
					'ss.payroll_system_account_id',
					'ss.reset_type',
					'ss.reset_type_value',
					'ss.number_characters',
					'ss.login_attempts',
					'ss.account_blck_duration_type',
					'ss.account_blck_duration_val',
					'ss.allow_password_reuse',
					'ss.must_have_alpha',
					'ss.must_have_numeric',
					'ss.must_have_special',
					'ss.must_have_case',
					'ss.created_date',
					'ss.updated_date'
			);
			$this->edb->select($select);
			$this->edb->where($where);
			$this->edb->join('accounts AS a','ss.payroll_system_account_id= a.payroll_system_account_id','INNER');
			$q = $this->edb->get('security_settings AS ss');
			$r = $q->row();
				
			if($r){
				if($r->user_type_id == 3){
					if($r->enable_generic_privilege == 'Inactive' || $r->enable_generic_privilege == ''){
						return false;
					}
				}
			}
				
			return $r;
		}else{
			return false;
		}
	}
	## FEB 23 , 2016 BACKUP 00----------------------------------------------------------------------------------
	
	
	##### END ADDED OCT 30 2015
	
	/**
	 * Get employment status of the employee
	 * If employee_status is "Active" return query result else return false.
	 * The employee status is used block "Inactive" employees from lgging in.
	 * Called by libraries/authentication/validate_login and  libraries/authentication/validate_login_mobile
	 * @requires: employee id
	 * @param unknown $emp_id
	 * @return boolean
	 * @added by: 47
	 */
	public function get_employee_status($emp_id)
	{
        $select = array(
            "entitled_to_overtime",
            "entitled_to_leaves",
            "employee_status"
        );
	    $this->db->select($select);
	    $this->db->where(array('emp_id'=>$emp_id));
	    $q = $this->db->get('employee_payroll_information');
	    $r = $q->row();
	    return ($r) ? $r : false;
	    /* if ($r->employee_status == 'Active') {
	       return true;
	    } else {
	        return false;
	    } */
	}
	
	/** FOR DEPED LOGIN MODULES **/
	
	/**
	 * DEPED ACCOUNT
	 * @param unknown $user
	 * @param unknown $pass
	 */
	public function get_account_deped($user,$pass)
	{
		if ($user && $pass) {
			$select = array(
					'accounts.account_id',
					'accounts.account_type_id',
					'accounts.payroll_system_account_id',
					'accounts.user_type_id',
					'payroll_system_account.sub_domain',
					'company.company_name',
					'employee.emp_id',
					'accounts.enable_generic_privilege'
			);
			$where = array(
					'accounts.email' 			=> $user,
					'accounts.password' 		=> $pass,
					'accounts.account_type_id'  => 2,
					'accounts.deleted'			=> 0,
					#added
					'payroll_system_account.status'=>'Active',
					'payroll_system_account.choose_plans_id'=>'5' # PARA SA DEPED
			);
			
			$this->db->select("*");
			$this->edb->select($select);
			$this->db->select('company.sub_domain AS company_subdomain');
			$this->edb->where($where);
			
			$this->edb->join('payroll_system_account','accounts.payroll_system_account_id = payroll_system_account.payroll_system_account_id','INNER');
			$this->edb->join('employee','accounts.account_id = employee.account_id','left');
			$this->edb->join('company','employee.company_id = company.company_id','left');
				
			$q = $this->edb->get('accounts');
			$result = $q->row();
				
			return ($result) ? $result : false;
				
		}
	}
	
	/**
	 * VALIDATION SEPARATE ACCOUNT
	 * @param string $user
	 * @param string $pass
	 */
	public function get_account_validity_separate_deped($user,$pass="")
	{
		if ($user) {
			$select = array(
					'accounts.account_id',
					'accounts.account_type_id',
					'accounts.payroll_system_account_id',
					'accounts.user_type_id',
					'payroll_system_account.sub_domain',
					'company.company_name',
					'employee.emp_id',
					'accounts.enable_generic_privilege'
			);
			$where = array(
					'accounts.email' 			=> $user,
					'accounts.account_type_id'  => 2,
					'accounts.deleted'			=> 0,
					#added
					'payroll_system_account.status'=>'Active',
					'payroll_system_account.choose_plans_id'=>'5' # PARA SA DEPED
			);
			if($pass !==""){
				$where['accounts.password'] = $pass;
			}
			$this->edb->select($select);
			$this->db->select('company.sub_domain AS company_subdomain');
			$this->edb->where($where);
			$this->edb->join('payroll_system_account','accounts.payroll_system_account_id = payroll_system_account.payroll_system_account_id','left');
			$this->edb->join('employee','accounts.account_id = employee.account_id','left');
			$this->edb->join('company','employee.company_id = company.company_id','left');
			$q = $this->edb->get('accounts');
			$result = $q->row();
				
			if($result){
				if($result->user_type_id == 3){
					if($result->enable_generic_privilege == 'Inactive' || $result->enable_generic_privilege == ''){
						return false;
					}
				}
			}
				
			return ($result) ? $result : false;
		}
	}
	
	/**
	 * Get Account for mobile
	 * @param unknown_type $mobile
	 * @param unknown_type $pass
	 */
	public function get_account_mobile_deped($mobile,$pass)
	{
		if ($mobile && $pass) {
			$konsum_key = konsum_key();
			$select = array(
					'accounts.account_id',
					'accounts.account_type_id',
					'accounts.payroll_system_account_id',
					'accounts.user_type_id',
					'payroll_system_account.sub_domain',
					'company.company_name',
					'employee.emp_id',
					'accounts.enable_generic_privilege'
			);
				
			$this->edb->select($select);
			$this->db->select("*");
			$this->db->select('company.sub_domain AS company_subdomain');
			$this->edb->join('payroll_system_account','accounts.payroll_system_account_id = payroll_system_account.payroll_system_account_id','left');
			$this->edb->join('employee','accounts.account_id = employee.account_id','left');
			$this->edb->join('company','employee.company_id = company.company_id','left');
			$this->db->where("
					AES_DECRYPT(accounts.login_mobile_number,'{$konsum_key}') = '".$mobile."'
    			AND accounts.account_type_id = '2' AND accounts.deleted ='0'
    			AND accounts.verified_status = 'verified' AND
    			payroll_system_account.status ='Active' AND
    			accounts.password = '".$pass."' AND flag_primary = 'login_mobile_number' AND payroll_system_account.choose_plans_id='5'
	    			OR AES_DECRYPT(accounts.login_mobile_number_2,'{$konsum_key}') = '".$mobile."'
    			AND accounts.account_type_id = '2' AND accounts.deleted ='0'
    			AND accounts.verified_status_2 = 'verified' AND
    			payroll_system_account.status ='Active' AND
    			accounts.password = '".$pass."' AND flag_primary = 'login_mobile_number_2'  AND payroll_system_account.choose_plans_id='5'", NULL, FALSE); // encrypt
			$q = $this->edb->get('accounts');
			$result = $q->row();
				
			if($result){
				if($result->user_type_id == 3){
					if($result->enable_generic_privilege == 'Inactive' || $result->enable_generic_privilege == ''){
						return false;
					}
				}
			}
			return ($result) ? $result : false;
		}
	}
	
	/**
	 * codeigntier constructor
	 * @param unknown $user
	 */
	public function codeigniter_constructor($user)
	{
		if ($user) {
			$select = array(
					'accounts.account_id',
					'accounts.account_type_id',
					'accounts.payroll_system_account_id',
					'accounts.user_type_id',
					'payroll_system_account.sub_domain',
					'company.company_name',
					'employee.emp_id',
					'accounts.enable_generic_privilege'
			);
			$where = array(
					'accounts.email' 			=> $user,
					'accounts.account_type_id'  => 2,
					'accounts.deleted'			=> 0,
					#added
					'payroll_system_account.status'=>'Active',
			);
				
			$this->db->select("*");
			$this->edb->select($select);
			$this->db->select('company.sub_domain AS company_subdomain');
			$this->edb->where($where);
				
			$this->edb->join('payroll_system_account','accounts.payroll_system_account_id = payroll_system_account.payroll_system_account_id','INNER');
			$this->edb->join('employee','accounts.account_id = employee.account_id','left');
			$this->edb->join('company','employee.company_id = company.company_id','left');
	
			$q = $this->edb->get('accounts');
			$result = $q->row();
	
			return ($result) ? $result : false;
	
		}
	}
	
	/**
	 * GET EMPLOYEE SUBDOMAIN
	 * @param unknown $account_id
	 * @return boolean
	 */
	public function get_employee_subdomain($account_id){
		if($account_id){
			$psa_id = $this->session->userdata('psa_id');
			$psa_where = array(
				'payroll_system_account_id'=> $psa_id,
				'status'=>'Active',
				'choose_plans_id'=>'5'
			);
			$get_plan = get_table_info('payroll_system_account',$psa_where);
			if($get_plan){
				$choose_plan = $get_plan->choose_plans_id;
				if($choose_plan == 5){
					$where = array(
						'e.status'		=> "Active",
						'e.account_id'	=> $account_id,
						'e.deleted'		=> '0',
						'c.status'		=> 'Active'
					);
					$this->edb->where($where);
					$this->edb->join('company AS c','c.company_id = e.company_id','INNER');
					$q = $this->edb->get('employee AS e');
					$r = $q->row();
					return $r ? $r->sub_domain : false;
				}
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	/** END FOR DEPED LOGIN MODULES **/
	
	
	/** BARAK FEATURE SEPTEMBER 22 2016 **/
	
	/**
	 * BARAK LOGIN CHECKER
	 * @param unknown $user
	 * @param unknown $pass
	 */
	public function get_account_barak_support($user,$pass)
	{
		if ($user && $pass) {
			$select = array(
				'accounts.account_id',
				'accounts.account_type_id',
				'accounts.payroll_system_account_id',
				'accounts.user_type_id', 
				'employee.emp_id',
				'accounts.enable_generic_privilege'
			);
			$where = array(
				'accounts.email' 			=> $this->db->escape_str($user),
				'accounts.password' 		=> $this->db->escape_str($pass),
				'accounts.account_type_id'  => 2,
				'accounts.deleted'			=> 0,
				'support'					=> 'yes'
			); 
			$this->edb->select($select); 
			$this->edb->where($where);  
			$this->edb->join('employee','accounts.account_id = employee.account_id','left'); 
			$q = $this->edb->get('accounts');
			$result = $q->row(); 
			return ($result) ? $result : false; 
		}
	}
	
	/**
	 * GET PAYROLL SYSTEM ACCOUNT ALL
	 */
	public function get_support_payroll_system_account(){
		$where = array(
			'bf.is_activated'=>'yes',
			'psa.status'=>'Active'
		);
		$this->db->where($where);
		$this->db->join('payroll_system_account AS psa','psa.payroll_system_account_id=bf.payroll_system_account_id','INNER');
		$q = $this->db->get('barak_feature AS bf');
		$r = $q->result();
		return $r;
	}

	
	/**
	 * VALIDATION SEPARATE ACCOUNT
	 * @param string $user
	 * @param string $pass
	 */
	public function get_account_validity_separate_barak_support($user,$pass="")
	{
		if ($user) { 
			$select = array(
					'accounts.account_id',
					'accounts.account_type_id',
					'accounts.payroll_system_account_id',
					'accounts.user_type_id',
					'employee.emp_id',
					'accounts.enable_generic_privilege'
			);
			$where = array(
					'accounts.email' 			=> $this->db->escape_str($user),
				
					'accounts.account_type_id'  => 2,
					'accounts.deleted'			=> 0,
					'support'					=> 'yes'
			); 
			
			if($pass !==""){
				$where['accounts.password'] = $this->db->escape_str($pass);
			} 
			$this->edb->select($select);
			$this->edb->where($where);
			$this->edb->join('employee','accounts.account_id = employee.account_id','left');
			$q = $this->edb->get('accounts');
			$result = $q->row();
			 
			return ($result) ? $result : false;
		}
	}
	
	/** END OF BARAK FEATURE SEPTERMBER 22 2016 oh yeah **/
	
}

?>