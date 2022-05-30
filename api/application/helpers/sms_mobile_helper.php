<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
*	Helper : SMS NOTIFICATION helper 
*	Author : Christopher Cuizon <christophercuizons@gmail.com>
*	Usage  : SMS NOTIFICATION helper 
*/

 
	/**
	 * MAO NI MO CHECK KONG NAKA ACTIVE IMONG SMS NOTIFICATION
	 * ENTER SMS NOTIFICATIONS
	 */
	function enabled_sms_notificatioin(){
		$CI =& get_instance();
		$where = array(
			'psa_id' => $CI->session->userdata("psa_id"),
			'activate'=>'Active',
			'status'=>'Active'
		);
		$CI->edb->where($where);
		$query = $CI->edb->get('sms_notification');
		$row = $query->row();
		return $row;
	} 


	/**
	 * CHECK IF HE HAS A SMART TOKEN
	 * Enter description here ...
	 */
	function open_smart_connected_sms(){
		$CI =& get_instance();
		$where = array(
			'psa_id' => $CI->session->userdata("psa_id")
		);
		$CI->edb->where($where);
		$query = $CI->edb->get('smart_mobile_api');
		$row = $query->row();
		return $row;
	}
	
	function open_globe_token(){
		$CI =& get_instance();
		$where = array(
			'psa_id'=>$CI->session->userdata('psa_id')
		);
		$CI->edb->where($where);
		$query = $CI->edb->get('globe_mobile_api');
		$row = $query->row();
		if($row){ # CHECK IF OUR GLOBE SMS HAS BEEN FILL UP
			$token = get_curl_globe_token($row->organization_name,$row->text_id,$row->password,$row->app_id);
			return $token;
		}else{
			return false;
		}
	}
	
	/**
	 * SEND MOBILE SMS USING SMART COMMUNICATIN
	 * Enter description here ...
	 * @param string $token
	 * @param string $method
	 * @param number $number
	 * @param string $message
	 * @return object
	 */
	function open_send_smart_sms($token,$method="SENDSMS",$number,$message){
		require_once("./lib/nusoap.php");
		$client = new nusoap_client('https://ws.smartmessaging.com.ph/soap?wsdl', true);
		$parameters = array(
					array(
						'token' => $token
					)
		);
		$parameters = array(
			   array(
				   'token' 	=> 	$token,
				   'msisdn' 	=> $number,
				   'message' 	=> $message
			  )
		);
		$return = $client->call($method, $parameters);
		return $parameters;
	}
	
	function send_this_sms_to($company_id,$account_id){
		$globe_numbers = array(
			'63905','63906','63915','63926','63916','63917','639178','63926','63927','63935','63936','63937','63975','63973','63974','63977','63978','63979','63994','63996','63997','0817','63922',
			'63923'
		);
		$smart_numbers = array(
			'63907','63908','639639','63910','63912','63918','63919','63920','63921','63922','63923','63925','63932','63933','63934','63938','63939','63942','63943','63946','63947',
			'63948','63949','63989','63998','63999','63928','63948','63929'
		);
		
		$CI =& get_instance();
			
		# CHECK OUR ID FIRST VALIDATE THIS
			$where = array(
				"a.account_id" 	=> $account_id,
				"e.company_id"	=> $company_id,
				"e.status"		=> "Active",
				"a.deleted"		=> "0"	
			);
			$CI->edb->select("e.emp_id,a.email,a.login_mobile_number,e.first_name,e.last_name,e.middle_name,a.profile_image,a.payroll_system_account_id,e.company_id,a.account_id");
			$CI->edb->where($where);
			$CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$query = $CI->edb->get("employee AS e");
			$profile = $query->row();
			$query->free_result();
			
			if($profile) {
				$user_mobile_number = substr($profile->login_mobile_number,0,5);
					# CHECK IF HE HAS A SMART DATA SAVED ON THEIR SETTINGS
					
					#SMART CHECK
						$where_smart = array('psa_id' => $CI->session->userdata("psa_id"));
						$CI->edb->where($where_smart);
						$smart_data = $CI->edb->get('smart_mobile_api');
						$smart_row = $smart_data->row();
						$smart_data->free_result();
					# END SMART
					
					# GLOBE CHECK			
						$where_globe = array('psa_id'=>$CI->session->userdata('psa_id'));
						$this->edb->where($where_globe);
						$globe_data = $this->edb->get('globe_mobile_api');
						$globe_row = $globe_data->row();	
					# END GLOBE CHECK
					
					# GET OUR EMPLOYEES ACCOUNT FIELDS
						$first_name = "";
						$last_name = "";
						if($profile->first_name) $first_name = substr($profile->first_name,0,1);
						if($profile->last_name) $last_name = substr($profile->last_name,0,1);
						$send_password = date("YdHs").$first_name.$last_name;
						$send_verification_code = date("YdHs").$first_name.$last_name; 
					# END GET OUR EMPLOYESS ACCOUNT FIELDS
				
					
						$message = "You have been invited to Payroll System. Your password is ".$send_password;		
						
						if(in_array($user_mobile_number,$smart_numbers)) {
							if($smart_row) {
								if($profile->email =="") { #MAO NI MO CHECK KONG ANG EMPLOYEE WALA GALI EMAIL IMONG E RESET ANG PASSWORD
									sms_update_password($account_id,$CI->authentication->encrypt_password($send_password));
									$message = "You have been invited to Payroll System. Your password is ".$send_password;	
												
								} else { # TIMAILHAN NA KUNG ANG USER NAAY EMAIL VERIFICATION CODE LANG E SEND
									#$message = "Please verify your mobile number by entering this code {$send_verification_code} to payroll.konsum.ph";
									$message = "Please enter this code {$send_verification_code} to verify your mobile number. Log in to payroll.konsum.ph and click profile.";
									sms_update_verification_code($account_id,$send_verification_code);
									
								}		
								$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
							
								# ACTIVITY LOGS
								iactivity_logs($company_id,' has Invited a User using Mobile Services');
								# END ACTIVITY LOGS		
								echo json_encode(array("send_sms"=>"true",'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));	
								return false;		
							}else{
								if($globe_row){
									$token_globe = get_curl_globe_token($globe_row->organization_name,$globe_row->text_id,$globe_row->password,$globe_row->app_id);
									$message = "You have been invited to Payroll System. Your password is ".$send_password;	
									if($profile->email =="") { #MAO NI MO CHECK KONG ANG EMPLOYEE WALA GALI EMAIL IMONG E RESET ANG PASSWORD
											sms_update_password($account_id,$CI->authentication->encrypt_password($send_password));
											$message = urlencode("You have been invited to Payroll System. Your password is ".$send_password);	
									} else { # TIMAILHAN NA KUNG ANG USER NAAY EMAIL VERIFICATION CODE LANG E SEND	
											#$message = urlencode("Please verify your mobile number by entering this code {$send_verification_code} to payroll.konsum.ph");
											$message = urlencode("Please enter this code {$send_verification_code} to verify your mobile number. Log in to payroll.konsum.ph and click profile.");
											sms_update_verification_code($account_id,$send_verification_code);	
									}
									
									$recipients = $profile->login_mobile_number;						
									sms_httpGetWithErros('https://txtconnectlc.globe.com.ph/api/sendmessage?token='.$token_globe.'&recipients='.$recipients.'&message='.$message);	
									
									# ACTIVITY LOGS
										iactivity_logs($CI->company_info->company_id,' has Invited a User using Mobile Services');	
									# END ACTIVITY LOGS
									echo json_encode(array("send_sms"=>"true",'profile'=>$employee_check,'smsprovider'=>"globe"));
									return false;		
								}
							}
						}else if(in_array($user_mobile_number,$globe_numbers)) {	
							if($globe_row){
								$token_globe = get_curl_globe_token($globe_row->organization_name,$globe_row->text_id,$globe_row->password,$globe_row->app_id);
								$message = "You have been invited to Payroll System. Your password is ".$send_password;	
								if($profile->email =="") { #MAO NI MO CHECK KONG ANG EMPLOYEE WALA GALI EMAIL IMONG E RESET ANG PASSWORD
										sms_update_password($account_id,$CI->authentication->encrypt_password($send_password));
										$message = urlencode("You have been invited to Payroll System. Your password is ".$send_password);	
								} else { # TIMAILHAN NA KUNG ANG USER NAAY EMAIL VERIFICATION CODE LANG E SEND	
										#$message = urlencode("Please verify your mobile number by entering this code {$send_verification_code} to payroll.konsum.ph");
										$message = urlencode("Please enter this code {$send_verification_code} to verify your mobile number. Log in to payroll.konsum.ph and click profile.");
										sms_update_verification_code($account_id,$send_verification_code);	
								}
								
								$recipients = $profile->login_mobile_number;						
								sms_httpGetWithErros('https://txtconnectlc.globe.com.ph/api/sendmessage?token='.$token_globe.'&recipients='.$recipients.'&message='.$message);	
							
								# ACTIVITY LOGS
									iactivity_logs($CI->company_info->company_id,' has Invited a User using Mobile Services');	
								# END ACTIVITY LOGS		
								echo json_encode(array("send_sms"=>"true",'profile'=>$employee_check,'smsprovider'=>"globe"));
								return false;
							}else{
								if($smart_row) {
									if($profile->email =="") { #MAO NI MO CHECK KONG ANG EMPLOYEE WALA GALI EMAIL IMONG E RESET ANG PASSWORD
										sms_update_password($account_id,$CI->authentication->encrypt_password($send_password));
										$message = "You have been invited to Payroll System. Your password is ".$send_password;	
													
									} else { # TIMAILHAN NA KUNG ANG USER NAAY EMAIL VERIFICATION CODE LANG E SEND
										#$message = "Please verify your mobile number by entering this code {$send_verification_code} to payroll.konsum.ph";
										$message = "Please enter this code {$send_verification_code} to verify your mobile number. Log in to payroll.konsum.ph and click profile.";
										sms_update_verification_code($account_id,$send_verification_code);
										
									}		
									$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
									
									# ACTIVITY LOGS
										iactivity_logs($company_id,' has Invited a User using Mobile Services');
									# END ACTIVITY LOGS		
									echo json_encode(array("send_sms"=>"true",'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));	
									return false;		
								}
								
							}	
					}		
			}else{
				echo json_encode(array("send_sms"=>"false",'profile'=>'',"sms_process"=>'',"sms_provider"=>"noprovider"));	
				return false;
			}							
	}
	
	
		function send_this_sms_invitations($company_id,$account_id,$message=""){
		$globe_numbers = array(
			'63905','63906','63915','63926','63916','63917','639178','63926','63927','63935','63936','63937','63975','63973','63974','63977','63978','63979','63994','63996','63997','0817','63922',
			'63923'
		);
		$smart_numbers = array(
			'63907','63908','639639','63910','63912','63918','63919','63920','63921','63922','63923','63925','63932','63933','63934','63938','63939','63942','63943','63946','63947',
			'63948','63949','63989','63998','63999','63928','63948','63929'
		);
		
		$CI =& get_instance();
			
		# CHECK OUR ID FIRST VALIDATE THIS
			$where = array(
				"a.account_id" 	=> $account_id,
				"e.company_id"	=> $company_id,
				"e.status"		=> "Active",
				"a.deleted"		=> "0"	
			);
			$CI->edb->select("e.emp_id,a.email,a.login_mobile_number,e.first_name,e.last_name,e.middle_name,a.profile_image,a.payroll_system_account_id,e.company_id,a.account_id");
			$CI->edb->where($where);
			$CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$query = $CI->edb->get("employee AS e");
			$profile = $query->row();
			$query->free_result();
			
			if($profile) {
				$user_mobile_number = substr($profile->login_mobile_number,0,5);
					# CHECK IF HE HAS A SMART DATA SAVED ON THEIR SETTINGS
					
					#SMART CHECK
						$where_smart = array('psa_id' => $CI->session->userdata("psa_id"));
						$CI->edb->where($where_smart);
						$smart_data = $CI->edb->get('smart_mobile_api');
						$smart_row = $smart_data->row();
						$smart_data->free_result();
					# END SMART
					
					# GLOBE CHECK			
						$where_globe = array('psa_id'=>$CI->session->userdata('psa_id'));
						$this->edb->where($where_globe);
						$globe_data = $this->edb->get('globe_mobile_api');
						$globe_row = $globe_data->row();	
					# END GLOBE CHECK
					
					# GET OUR EMPLOYEES ACCOUNT FIELDS
						$first_name = "";
						$last_name = "";
						if($profile->first_name) $first_name = substr($profile->first_name,0,1);
						if($profile->last_name) $last_name = substr($profile->last_name,0,1);
						$send_password = date("YdHs").$first_name.$last_name;
						$send_verification_code = date("YdHs").$first_name.$last_name; 
					# END GET OUR EMPLOYESS ACCOUNT FIELDS
				
					
						$message = "You have been invited to Payroll System. Your password is ".$send_password;		
						
						if(in_array($user_mobile_number,$smart_numbers)) {
							if($smart_row) {
								if($profile->email =="") { #MAO NI MO CHECK KONG ANG EMPLOYEE WALA GALI EMAIL IMONG E RESET ANG PASSWORD
									#sms_update_password($account_id,$CI->authentication->encrypt_password($send_password));
									#$message = "You have been invited to Payroll System. Your password is ".$send_password;	
											
								} else { # TIMAILHAN NA KUNG ANG USER NAAY EMAIL VERIFICATION CODE LANG E SEND
									#$message = "Please verify your mobile number by entering this code {$send_verification_code} to payroll.konsum.ph";
									#$message = "Please enter this code {$send_verification_code} to verify your mobile number. Log in to payroll.konsum.ph and click profile.";
									sms_update_verification_code($account_id,$send_verification_code);
									
								}		
								$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
							
								# ACTIVITY LOGS
								iactivity_logs($company_id,' has Invited a User using Mobile Services');
								# END ACTIVITY LOGS		
								echo json_encode(array("send_sms"=>"true",'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));	
								return false;		
							}else{
								if($globe_row){
									$token_globe = get_curl_globe_token($globe_row->organization_name,$globe_row->text_id,$globe_row->password,$globe_row->app_id);
									$message = "You have been invited to Payroll System. Your password is ".$send_password;	
									if($profile->email =="") { #MAO NI MO CHECK KONG ANG EMPLOYEE WALA GALI EMAIL IMONG E RESET ANG PASSWORD
											sms_update_password($account_id,$CI->authentication->encrypt_password($send_password));
											#$message = urlencode("You have been invited to Payroll System. Your password is ".$send_password);	
									} else { # TIMAILHAN NA KUNG ANG USER NAAY EMAIL VERIFICATION CODE LANG E SEND	
											#$message = urlencode("Please verify your mobile number by entering this code {$send_verification_code} to payroll.konsum.ph");
											#$message = urlencode("Please enter this code {$send_verification_code} to verify your mobile number. Log in to payroll.konsum.ph and click profile.");
											sms_update_verification_code($account_id,$send_verification_code);	
									}
									
									$recipients = $profile->login_mobile_number;						
									sms_httpGetWithErros('https://txtconnectlc.globe.com.ph/api/sendmessage?token='.$token_globe.'&recipients='.$recipients.'&message='.$message);	
									
									# ACTIVITY LOGS
										iactivity_logs($CI->company_info->company_id,' has Invited a User using Mobile Services');	
									# END ACTIVITY LOGS
									echo json_encode(array("send_sms"=>"true",'profile'=>$employee_check,'smsprovider'=>"globe"));
									return false;		
								}
							}
						}else if(in_array($user_mobile_number,$globe_numbers)) {	
							if($globe_row){
								$token_globe = get_curl_globe_token($globe_row->organization_name,$globe_row->text_id,$globe_row->password,$globe_row->app_id);
								$message = "You have been invited to Payroll System. Your password is ".$send_password;	
								if($profile->email =="") { #MAO NI MO CHECK KONG ANG EMPLOYEE WALA GALI EMAIL IMONG E RESET ANG PASSWORD
										sms_update_password($account_id,$CI->authentication->encrypt_password($send_password));
										#$message = urlencode("You have been invited to Payroll System. Your password is ".$send_password);	
								} else { # TIMAILHAN NA KUNG ANG USER NAAY EMAIL VERIFICATION CODE LANG E SEND	
										#$message = urlencode("Please verify your mobile number by entering this code {$send_verification_code} to payroll.konsum.ph");
										#$message = urlencode("Please enter this code {$send_verification_code} to verify your mobile number. Log in to payroll.konsum.ph and click profile.");
										sms_update_verification_code($account_id,$send_verification_code);	
								}
								
								$recipients = $profile->login_mobile_number;						
								sms_httpGetWithErros('https://txtconnectlc.globe.com.ph/api/sendmessage?token='.$token_globe.'&recipients='.$recipients.'&message='.$message);	
							
								# ACTIVITY LOGS
									iactivity_logs($CI->company_info->company_id,' has Invited a User using Mobile Services');	
								# END ACTIVITY LOGS		
								echo json_encode(array("send_sms"=>"true",'profile'=>$employee_check,'smsprovider'=>"globe"));
								return false;
							}else{
								if($smart_row) {
									if($profile->email =="") { #MAO NI MO CHECK KONG ANG EMPLOYEE WALA GALI EMAIL IMONG E RESET ANG PASSWORD
										sms_update_password($account_id,$CI->authentication->encrypt_password($send_password));
										#$message = "You have been invited to Payroll System. Your password is ".$send_password;	
													
									} else { # TIMAILHAN NA KUNG ANG USER NAAY EMAIL VERIFICATION CODE LANG E SEND
										#$message = "Please verify your mobile number by entering this code {$send_verification_code} to payroll.konsum.ph";
										#$message = "Please enter this code {$send_verification_code} to verify your mobile number. Log in to payroll.konsum.ph and click profile.";
										sms_update_verification_code($account_id,$send_verification_code);
										
									}		
									$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
									
									# ACTIVITY LOGS
										iactivity_logs($company_id,' has Invited a User using Mobile Services');
									# END ACTIVITY LOGS		
									echo json_encode(array("send_sms"=>"true",'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));	
									return false;		
								}
								
							}	
					}		
			}else{
				echo json_encode(array("send_sms"=>"false",'profile'=>'',"sms_process"=>'',"sms_provider"=>"noprovider"));	
				return false;
			}							
	}
	
	
	
	
	function sms_update_password($account_id,$password){
		$CI =& get_instance();
		
		if(is_numeric($account_id)){
			$CI->edb->where(array('account_id'=>$account_id));
			$update_field = array(
				'password'=>$password,
			);
			$CI->edb->update("accounts",$update_field);
		}
	}
		
	function sms_update_verification_code($account_id,$verification_code){
		$CI =& get_instance();
		if(is_numeric($account_id)){
			$CI->edb->where(array('account_id'=>$account_id));
			$update_field = array(
				'verification_code'=>$verification_code,
			);
			$CI->edb->update("accounts",$update_field);
		}else{
			return false;
		}
	}
	
	function sms_httpGetWithErros($url)
	{
	    $ch = curl_init(); 
	 
	    curl_setopt($ch,CURLOPT_URL,$url);
	    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-Type:application/json', 'Expect:'));
		curl_setopt($ch, CURLOPT_TIMEOUT, 160);
	    $output=curl_exec($ch);
	 
	    if($output === false)
	    {
	        echo "Error Number:".curl_errno($ch)."<br>";
	        echo "Error String:".curl_error($ch);
	    }
	    curl_close($ch);
	    return $output;
	}
	
	function send_this_sms_global($company_id,$account_id,$message="",$psa_id=NULL,$json=false){
		$globe_numbers = array(
			'63905','63906','63915','63926','63916','63917','639178','63926','63927','63935','63936','63937','63975','63973','63974','63977','63978','63979','63994','63996','63997','0817','63922',
			'63923'
		);
		$smart_numbers = array(
			'63907','63908','639639','63910','63912','63918','63919','63920','63921','63922','63923','63925','63932','63933','63934','63938','63939','63942','63943','63946','63947',
			'63948','63949','63989','63998','63999','63928','63948','63929','63917'
		);
		
		$CI =& get_instance();
			
		# CHECK OUR ID FIRST VALIDATE THIS
			$where = array(
				"a.account_id" 	=> $account_id,
				"e.company_id"	=> $company_id,
				"e.status"		=> "Active",
				"a.deleted"		=> "0"	
			);
			$CI->edb->select("e.emp_id,a.email,a.login_mobile_number,e.first_name,e.last_name,e.middle_name,a.profile_image,a.payroll_system_account_id,e.company_id,a.account_id");
			$CI->edb->where($where);
			$CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$query = $CI->edb->get("employee AS e");
			$profile = $query->row();
			$query->free_result();
			$asa = '';
			if($psa_id ==""){
				$psa_id =  $CI->session->userdata("psa_id");
			}
			if($profile) {
				$user_mobile_number = substr($profile->login_mobile_number,0,5);
					# CHECK IF HE HAS A SMART DATA SAVED ON THEIR SETTINGS
					
					#SMART CHECK
						$where_smart = array('psa_id' =>$psa_id);
						$CI->edb->where($where_smart);
						$smart_data = $CI->edb->get('smart_mobile_api');
						$smart_row = $smart_data->row();
						$smart_data->free_result();
					
					# END SMART
					
					# GLOBE CHECK			
						$where_globe = array('psa_id'=>$psa_id);
						$CI->edb->where($where_globe);
						$globe_data = $CI->edb->get('globe_mobile_api');
						$globe_row = $globe_data->row();
						
					# END GLOBE CHECK
					
					# GET OUR EMPLOYEES ACCOUNT FIELDS
						$first_name = "";
						$last_name = "";
						if($profile->first_name) $first_name = substr($profile->first_name,0,1);
						if($profile->last_name) $last_name = substr($profile->last_name,0,1);
						$send_password = date("YdHs").$first_name.$last_name;
						$send_verification_code = date("YdHs").$first_name.$last_name; 
					# END GET OUR EMPLOYESS ACCOUNT FIELDS	
						
						if(in_array($user_mobile_number,$smart_numbers)) {
							if($smart_row) {
								$ret = "true";
								$asa = 'smart';
								if($profile->login_mobile_number  !==""){
									$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
									$ret = "true";
								}else{
									$ret = "false";
								}
								# ACTIVITY LOGS
								iactivity_logs($company_id,' has been using mobile transactions');
								# END ACTIVITY LOGS	
								if($json==true){	
									echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
									return false;		
								}else{
									return true;
								}							
							}else{						
								if($globe_row){
									$token_globe = get_curl_globe_token($globe_row->organization_name,$globe_row->text_id,$globe_row->password,$globe_row->app_id);
									$ret = "true";
									if($token_globe){	
										$asa = 'globe';
										$recipients = $profile->login_mobile_number;	
										if($recipients  !=="") {
											$ret = "true";
											sms_httpGetWithErros('https://txtconnectlc.globe.com.ph/api/sendmessage?token='.$token_globe.'&recipients='.$recipients.'&message='.$message);	
										}else{
											$ret = "false";
										}	
										# ACTIVITY LOGS
											iactivity_logs($company_id,' has been using mobile transactions');
										# END ACTIVITY LOGS
										if($json==true){	
											echo json_encode(array("send_sms"=>$ret,'profile'=>$employee_check,'smsprovider'=>"globe"));
											return false;		
										}else{
											return false;
										}
									}else{
										#  ADDED O CONDITIOHN
										$ret = "true";
										if($smart_row){
											$asa = 'smart';
											if($profile->login_mobile_number  !==""){
												$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
												$ret = "true";
											}else{
												$ret = "false";
											}
											# ACTIVITY LOGS
											iactivity_logs($company_id,' has been using mobile transactions');
											# END ACTIVITY LOGS	
											if($json==true){	
												echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
												return false;		
											}else{
												return true;
											}
										}
										# END CONDITION	
									}					
								}
							}
						}else if(in_array($user_mobile_number,$globe_numbers)) {	
							if($globe_row){
								
								$token_globe = get_curl_globe_token($globe_row->organization_name,$globe_row->text_id,$globe_row->password,$globe_row->app_id);
								if($token_globe){
									$recipients = $profile->login_mobile_number;
									$ret = "true";
									$asa = 'globe';
									if($recipients  !==""){						
										sms_httpGetWithErros('https://txtconnectlc.globe.com.ph/api/sendmessage?token='.$token_globe.'&recipients='.$recipients.'&message='.$message);
										$ret = "true";
									}else{
										$ret = "false";
									}
									# ACTIVITY LOGS
										iactivity_logs($company_id,' has been using mobile transactions');
									# END ACTIVITY LOGS		
									if($json==true){	
										echo json_encode(array("send_sms"=>$ret,'profile'=>$employee_check,'smsprovider'=>"globe"));
										return false;
									}else{
										return true;
									}
								}else{
									#  ADDED O CONDITIOHN
									$ret = "true";
									if($smart_row){
										if($profile->login_mobile_number  !==""){
											$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
											$ret = "true";
										}else{
											$ret = "false";
										}
										# ACTIVITY LOGS
										iactivity_logs($company_id,' has been using mobile transactions');
										# END ACTIVITY LOGS	
										if($json==true){	
											echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
											return false;		
										}else{
											return true;
										}
										$asa = 'smart';
									}
									# END CONDITION
								}		
							}else{	
								if($smart_row) {
										$ret = "true";
									if($profile->login_mobile_number !==""){
										$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
										$ret = "true";
									}else{
										$ret = "false";
									}
									# ACTIVITY LOGS
										iactivity_logs($company_id,' has been using mobile transactions');
									# END ACTIVITY LOGS		
									if($json==true){	
										echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
										return false;	
									}else{
										return true;
									}				
								}	
							}	
					}		
			}else{
				if($json==true){	
					echo json_encode(array("send_sms"=>"false",'profile'=>'',"sms_process"=>'',"sms_provider"=>"noprovider"));
					return false;
				}else{
					return true;
				}	
				
			}							
	}
	
	function get_notification_settings($company_id){
		$CI =& get_instance();
		if(is_numeric($company_id)){
			$where = array(
			//	'psa_id'	 => $CI->session->userdata("psa_id"),
				'company_id' => $company_id,
				'status'	 => 'Active'
			);
			$CI->edb->where($where);
			$query = $CI->edb->get('notification_settings');
			$row = $query->row();
			return $row;
		}
	}
	
	
	//FOR WORKFORCE NOTIFICATION SETTINGS
	function get_workforce_notification_settings($company_id){
		$CI =& get_instance();
		if(is_numeric($company_id)){
			$where = array(
					//	'psa_id'	 => $CI->session->userdata("psa_id"),
					'wns.company_id' => $company_id,
					'wns.via'	=> 'default',
					'wns.status'	 => 'Active'
			);
			$CI->edb->join('workforce_alerts_notification AS wan','wns.workforce_alerts_notification_id = wan.workforce_alerts_notification_id','LEFT');
			$CI->edb->where($where);
			$query = $CI->edb->get('workforce_notification_settings AS wns');
			$row = $query->row();
			return ($row) ? $row : FALSE;
		}
		
	}
	function get_workforce_alert_staff($company_id){
		$CI =& get_instance();
		if(is_numeric($company_id)){
			$where = array(
					//	'psa_id'	 => $CI->session->userdata("psa_id"),
					'wns.company_id' => $company_id,
					'wns.via'	=> 'notify staff after application',
					'wns.status'	 => 'Active'
			);
			$CI->edb->join('workforce_alerts_notification AS wan','wns.workforce_alerts_notification_id = wan.workforce_alerts_notification_id','LEFT');
			$CI->edb->where($where);
			$query = $CI->edb->get('workforce_notification_settings AS wns');
			$row = $query->row();
			return $row;
		}
	
	}
	
	function get_workforce_notification_level($alert_id){
		$CI =& get_instance();
		if(is_numeric($alert_id)){
			$where = array('workforce_alerts_notification_id'=>$alert_id);
			$CI->edb->where($where);
			$q = $CI->edb->get('workforce_notification_leveling');
			$r = $q->result();
			return ($r) ? $r : FALSE;
			
		}
	}
	function check_if_is_level($level, $alert_id){
		$CI =& get_instance();
		if(is_numeric($alert_id)){
			$where = array(
				'level'=>$level,
				'workforce_alerts_notification_id'=>$alert_id
			);
			$CI->edb->where($where);
			$q = $CI->edb->get('workforce_notification_leveling');
			$r = $q->row();
			return ($r) ? TRUE : FALSE;
			
		}
		
		//END OF WORKFORCE NOTIFICATION SETTINGS
		
		
	}

	// FOR HOURS NOTIFICATION SETTINGS
	/**
	 * gets the notification settings for hours
	 * @param int $company_id
	 * @return <object, boolean>
	 */
	function get_hours_notification_settings($company_id){
		$CI =& get_instance();
		if(is_numeric($company_id)){
			$where = array(
					//	'psa_id'	 => $CI->session->userdata("psa_id"),
					'hns.company_id' => $company_id,
					'hns.via'	=> 'default',
					'hns.status'	 => 'Active'
			);
			$CI->edb->join('hours_alerts_notification AS han','hns.hours_alerts_notification_id = han.hours_alerts_notification_id','LEFT');
			$CI->edb->where($where);
			$query = $CI->edb->get('hours_notification_settings AS hns');
			$row = $query->row();
			return ($row) ? $row : FALSE;
		}
	
	}
	/**
	 * get the alert settings of staffs in hours
	 * @param int $company_id
	 * @return object
	 */
	function get_hours_alert_staff($company_id){
		$CI =& get_instance();
		if(is_numeric($company_id)){
			$where = array(
					//	'psa_id'	 => $CI->session->userdata("psa_id"),
					'hns.company_id' => $company_id,
					'hns.via'	=> 'notify staff after application',
					'hns.status'	 => 'Active'
			);
			$CI->edb->join('hours_alerts_notification AS han','hns.hours_alerts_notification_id = han.hours_alerts_notification_id','LEFT');
			$CI->edb->where($where);
			$query = $CI->edb->get('hours_notification_settings AS hns');
			$row = $query->row();
			return $row;
		}
	
	}
	/**
	 * get
	 * @param unknown $alert_id
	 * @return Ambigous <boolean, unknown>
	 */
	function get_hours_notification_level($alert_id){
		$CI =& get_instance();
		if(is_numeric($alert_id)){
			$where = array('hours_alerts_notification_id'=>$alert_id);
			$CI->edb->where($where);
			$q = $CI->edb->get('hours_notification_leveling');
			$r = $q->result();
			return ($r) ? $r : FALSE;
				
		}
	}
	function check_if_is_level_hours($level, $alert_id){
		$CI =& get_instance();
		if(is_numeric($alert_id)){
			$where = array(
					'level'=>$level,
					'hours_alerts_notification_id'=>$alert_id
			);
			$CI->edb->where($where);
			$q = $CI->edb->get('hours_notification_leveling');
			$r = $q->row();
			return ($r) ? TRUE : FALSE;
				
		}
	}
	
	//FOR SHIFTS NOTIFICATION SETTINGS
	/**
	 * gets the notification settings for hours
	 * @param int $company_id
	 * @return <object, boolean>
	 */
	function get_shifts_notification_settings($company_id){
		$CI =& get_instance();
		if(is_numeric($company_id)){
			$where = array(
					//	'psa_id'	 => $CI->session->userdata("psa_id"),
					'sns.company_id' => $company_id,
					'sns.via'	=> 'default',
					'sns.status'	 => 'Active'
			);
			$CI->edb->join('shifts_alerts_notification AS san','sns.shifts_alerts_notification_id = san.shifts_alerts_notification_id','LEFT');
			$CI->edb->where($where);
			$query = $CI->edb->get('shifts_notification_settings AS sns');
			$row = $query->row();
			return ($row) ? $row : FALSE;
		}
	
	}
	/**
	 * get the alert settings of staffs in shifts
	 * @param unknown $company_id
	 * @return unknown
	 */
	function get_shifts_alert_staff($company_id){
		$CI =& get_instance();
		if(is_numeric($company_id)){
			$where = array(
					//	'psa_id'	 => $CI->session->userdata("psa_id"),
					'sns.company_id' => $company_id,
					'sns.via'	=> 'notify staff after application',
					'sns.status'	 => 'Active'
			);
			$CI->edb->join('shifts_alerts_notification AS san','sns.shifts_alerts_notification_id = san.shifts_alerts_notification_id','LEFT');
			$CI->edb->where($where);
			$query = $CI->edb->get('shifts_notification_settings AS sns');
			$row = $query->row();
			return $row;
		}
	
	}
	
	function get_shifts_notification_level($alert_id){
		$CI =& get_instance();
		if(is_numeric($alert_id)){
			$where = array('shifts_alerts_notification_id'=>$alert_id);
			$CI->edb->where($where);
			$q = $CI->edb->get('shifts_notification_leveling');
			$r = $q->result();
			return ($r) ? $r : FALSE;
				
		}
	}
	/**
	 * checks if the the level of the approver is checkd or not.
	 * @param unknown $level
	 * @param unknown $alert_id
	 * @return boolean
	 */
	function check_if_is_level_shifts($level, $alert_id){
		$CI =& get_instance();
		if(is_numeric($alert_id)){
			$where = array(
					'level'=>$level,
					'shifts_alerts_notification_id'=>$alert_id
			);
			$CI->edb->where($where);
			$q = $CI->edb->get('shifts_notification_leveling');
			$r = $q->row();
			return ($r) ? TRUE : FALSE;
				
		}
	}
	
	/**
	 * SEND MOBILE SMS TO  HR ONLY
	 * Enter description here ...
	 * @param int $account_id
	 * @param text $message
	 * @param int $psa_id
	 * @param json $json
	 */
	function send_this_sms_global_hr($account_id,$message="",$psa_id=NULL,$json=false){
		$globe_numbers = array(
			'63905','63906','63915','63926','63916','63917','639178','63926','63927','63935','63936','63937','63975','63973','63974','63977','63978','63979','63994','63996','63997','0817','63922',
			'63923'
		);
		$smart_numbers = array(
			'63907','63908','639639','63910','63912','63918','63919','63920','63921','63922','63923','63925','63932','63933','63934','63938','63939','63942','63943','63946','63947',
			'63948','63949','63989','63998','63999','63928','63948','63929','63917'
		);
		
		$CI =& get_instance();
			
		# CHECK OUR ID FIRST VALIDATE THIS
			$where = array(
				"a.account_id" 	=> $account_id,
				"e.status"		=> "Active",
				"a.deleted"		=> "0"	
			);
			$CI->edb->select("e.emp_id,a.email,a.login_mobile_number,e.first_name,e.last_name,e.middle_name,a.profile_image,a.payroll_system_account_id,e.company_id,a.account_id");
			$CI->edb->where($where);
			$CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$query = $CI->edb->get("employee AS e");
			$profile = $query->row();
			$query->free_result();
			$asa = '';
			if($psa_id ==""){
				$psa_id =  $CI->session->userdata("psa_id");
			}
			if($profile) {
				$user_mobile_number = substr($profile->login_mobile_number,0,5);
					# CHECK IF HE HAS A SMART DATA SAVED ON THEIR SETTINGS
					
					#SMART CHECK
						$where_smart = array('psa_id' =>$psa_id);
						$CI->edb->where($where_smart);
						$smart_data = $CI->edb->get('smart_mobile_api');
						$smart_row = $smart_data->row();
						$smart_data->free_result();
					
					# END SMART
					
					# GLOBE CHECK			
						$where_globe = array('psa_id'=>$psa_id);
						$CI->edb->where($where_globe);
						$globe_data = $CI->edb->get('globe_mobile_api');
						$globe_row = $globe_data->row();
						
					# END GLOBE CHECK
					
					# GET OUR EMPLOYEES ACCOUNT FIELDS
						$first_name = "";
						$last_name = "";
						if($profile->first_name) $first_name = substr($profile->first_name,0,1);
						if($profile->last_name) $last_name = substr($profile->last_name,0,1);
						$send_password = date("YdHs").$first_name.$last_name;
						$send_verification_code = date("YdHs").$first_name.$last_name; 
					# END GET OUR EMPLOYESS ACCOUNT FIELDS	
						
						if(in_array($user_mobile_number,$smart_numbers)) {
							if($smart_row) {
								$ret = "true";
								$asa = 'smart';
								if($profile->login_mobile_number  !==""){
									$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
									$ret = "true";
								}else{
									$ret = "false";
								}
								# ACTIVITY LOGS
								#iactivity_logs($company_id,' has been using mobile transactions');
								# END ACTIVITY LOGS	
								if($json==true){	
									echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
									return false;		
								}else{
									return true;
								}							
							}else{						
								if($globe_row){
									$token_globe = get_curl_globe_token($globe_row->organization_name,$globe_row->text_id,$globe_row->password,$globe_row->app_id);
									$ret = "true";
									if($token_globe){	
										$asa = 'globe';
										$recipients = $profile->login_mobile_number;	
										if($recipients  !=="") {
											$ret = "true";
											sms_httpGetWithErros('https://txtconnectlc.globe.com.ph/api/sendmessage?token='.$token_globe.'&recipients='.$recipients.'&message='.$message);	
										}else{
											$ret = "false";
										}	
										# ACTIVITY LOGS
									#		iactivity_logs($company_id,' has been using mobile transactions');
										# END ACTIVITY LOGS
										if($json==true){	
											echo json_encode(array("send_sms"=>$ret,'profile'=>$employee_check,'smsprovider'=>"globe"));
											return false;		
										}else{
											return false;
										}
									}else{
										#  ADDED O CONDITIOHN
										$ret = "true";
										if($smart_row){
											$asa = 'smart';
											if($profile->login_mobile_number  !==""){
												$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
												$ret = "true";
											}else{
												$ret = "false";
											}
											# ACTIVITY LOGS
										#	iactivity_logs($company_id,' has been using mobile transactions');
											# END ACTIVITY LOGS	
											if($json==true){	
												echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
												return false;		
											}else{
												return true;
											}
										}
										# END CONDITION	
									}					
								}
							}
						}else if(in_array($user_mobile_number,$globe_numbers)) {	
							if($globe_row){
								
								$token_globe = get_curl_globe_token($globe_row->organization_name,$globe_row->text_id,$globe_row->password,$globe_row->app_id);
								if($token_globe){
									$recipients = $profile->login_mobile_number;
									$ret = "true";
									$asa = 'globe';
									if($recipients  !==""){						
										sms_httpGetWithErros('https://txtconnectlc.globe.com.ph/api/sendmessage?token='.$token_globe.'&recipients='.$recipients.'&message='.$message);
										$ret = "true";
									}else{
										$ret = "false";
									}
									# ACTIVITY LOGS
									#	iactivity_logs($company_id,' has been using mobile transactions');
									# END ACTIVITY LOGS		
									if($json==true){	
										echo json_encode(array("send_sms"=>$ret,'profile'=>$employee_check,'smsprovider'=>"globe"));
										return false;
									}else{
										return true;
									}
								}else{
									#  ADDED O CONDITIOHN
									$ret = "true";
									if($smart_row){
										if($profile->login_mobile_number  !==""){
											$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
											$ret = "true";
										}else{
											$ret = "false";
										}
										# ACTIVITY LOGS
										#iactivity_logs($company_id,' has been using mobile transactions');
										# END ACTIVITY LOGS	
										if($json==true){	
											echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
											return false;		
										}else{
											return true;
										}
										$asa = 'smart';
									}
									# END CONDITION
								}		
							}else{	
								if($smart_row) {
										$ret = "true";
									if($profile->login_mobile_number !==""){
										$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
										$ret = "true";
									}else{
										$ret = "false";
									}
									# ACTIVITY LOGS
									#	iactivity_logs($company_id,' has been using mobile transactions');
									# END ACTIVITY LOGS		
									if($json==true){	
										echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
										return false;	
									}else{
										return true;
									}				
								}	
							}	
					}		
			}else{
				if($json==true){	
					echo json_encode(array("send_sms"=>"false",'profile'=>'',"sms_process"=>'',"sms_provider"=>"noprovider"));
					return false;
				}else{
					return true;
				}	
				
			}							
	}
	
	
	/*** 
	 * THIS IS THE LATEST SEND SMS VERSION USE THIS TO SEND TO ALL ACCOUNT ID
	 */
	/**
	 * SEND TO ALL GLOBAL ACCOUNT
	 * Enter description here ...
	 * @param int $account_id
	 * @param strng $message
	 * @param int $psa_id
	 * @param string $json
	 */
	function send_this_sms_global_account($account_id,$message="",$psa_id=NULL,$json=false){
		$globe_numbers = array(
			'63905','63906','63915','63926','63916','63917','639178','63926','63927','63935','63936','63937','63975','63973','63974','63977','63978','63979','63994','63996','63997','0817','63922',
			'63923'
		);
		$smart_numbers = array(
			'63907','63908','639639','63910','63912','63918','63919','63920','63921','63922','63923','63925','63932','63933','63934','63938','63939','63942','63943','63946','63947',
			'63948','63949','63989','63998','63999','63928','63948','63929','63917'
		);
		
		$CI =& get_instance();
			
		# CHECK OUR ID FIRST VALIDATE THIS
			$where = array(
				"a.account_id" 	=> $account_id,
				"e.status"		=> "Active",
				"a.deleted"		=> "0"	
			);
			$CI->edb->select("e.emp_id,a.email,a.login_mobile_number,e.first_name,e.last_name,e.middle_name,a.profile_image,a.payroll_system_account_id,e.company_id,a.account_id");
			$CI->edb->where($where);
			$CI->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$query = $CI->edb->get("employee AS e");
			$profile = $query->row();
			$query->free_result();
			$asa = '';
			if($psa_id ==""){
				$psa_id =  $CI->session->userdata("psa_id");
			}
			if($profile) {
				$user_mobile_number = substr($profile->login_mobile_number,0,5);
					# CHECK IF HE HAS A SMART DATA SAVED ON THEIR SETTINGS
					
					#SMART CHECK
						$where_smart = array('psa_id' =>$psa_id);
						$CI->edb->where($where_smart);
						$smart_data = $CI->edb->get('smart_mobile_api');
						$smart_row = $smart_data->row();
						$smart_data->free_result();
					
					# END SMART
					
					# GLOBE CHECK			
						$where_globe = array('psa_id'=>$psa_id);
						$CI->edb->where($where_globe);
						$globe_data = $CI->edb->get('globe_mobile_api');
						$globe_row = $globe_data->row();
						
					# END GLOBE CHECK
					
					# GET OUR EMPLOYEES ACCOUNT FIELDS
						$first_name = "";
						$last_name = "";
						if($profile->first_name) $first_name = substr($profile->first_name,0,1);
						if($profile->last_name) $last_name = substr($profile->last_name,0,1);
						$send_password = date("YdHs").$first_name.$last_name;
						$send_verification_code = date("YdHs").$first_name.$last_name; 
					# END GET OUR EMPLOYESS ACCOUNT FIELDS	
						
						if(in_array($user_mobile_number,$smart_numbers)) {
							if($smart_row) {
								$ret = "true";
								$asa = 'smart';
								if($profile->login_mobile_number  !==""){
									$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
									$ret = "true";
								}else{
									$ret = "false";
								}
								# ACTIVITY LOGS
								#iactivity_logs($company_id,' has been using mobile transactions');
								# END ACTIVITY LOGS	
								if($json==true){	
									echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
									return false;		
								}else{
									return true;
								}							
							}else{						
								if($globe_row){
									$token_globe = get_curl_globe_token($globe_row->organization_name,$globe_row->text_id,$globe_row->password,$globe_row->app_id);
									$ret = "true";
									if($token_globe){	
										$asa = 'globe';
										$recipients = $profile->login_mobile_number;	
										if($recipients  !=="") {
											$ret = "true";
											sms_httpGetWithErros('https://txtconnectlc.globe.com.ph/api/sendmessage?token='.$token_globe.'&recipients='.$recipients.'&message='.$message);	
										}else{
											$ret = "false";
										}	
										# ACTIVITY LOGS
									#		iactivity_logs($company_id,' has been using mobile transactions');
										# END ACTIVITY LOGS
										if($json==true){	
											echo json_encode(array("send_sms"=>$ret,'profile'=>$employee_check,'smsprovider'=>"globe"));
											return false;		
										}else{
											return false;
										}
									}else{
										#  ADDED O CONDITIOHN
										$ret = "true";
										if($smart_row){
											$asa = 'smart';
											if($profile->login_mobile_number  !==""){
												$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
												$ret = "true";
											}else{
												$ret = "false";
											}
											# ACTIVITY LOGS
										#	iactivity_logs($company_id,' has been using mobile transactions');
											# END ACTIVITY LOGS	
											if($json==true){	
												echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
												return false;		
											}else{
												return true;
											}
										}
										# END CONDITION	
									}					
								}
							}
						}else if(in_array($user_mobile_number,$globe_numbers)) {	
							if($globe_row){
								
								$token_globe = get_curl_globe_token($globe_row->organization_name,$globe_row->text_id,$globe_row->password,$globe_row->app_id);
								if($token_globe){
									$recipients = $profile->login_mobile_number;
									$ret = "true";
									$asa = 'globe';
									if($recipients  !==""){						
										sms_httpGetWithErros('https://txtconnectlc.globe.com.ph/api/sendmessage?token='.$token_globe.'&recipients='.$recipients.'&message='.$message);
										$ret = "true";
									}else{
										$ret = "false";
									}
									# ACTIVITY LOGS
									#	iactivity_logs($company_id,' has been using mobile transactions');
									# END ACTIVITY LOGS		
									if($json==true){	
										echo json_encode(array("send_sms"=>$ret,'profile'=>$employee_check,'smsprovider'=>"globe"));
										return false;
									}else{
										return true;
									}
								}else{
									#  ADDED O CONDITIOHN
									$ret = "true";
									if($smart_row){
										if($profile->login_mobile_number  !==""){
											$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
											$ret = "true";
										}else{
											$ret = "false";
										}
										# ACTIVITY LOGS
										#iactivity_logs($company_id,' has been using mobile transactions');
										# END ACTIVITY LOGS	
										if($json==true){	
											echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
											return false;		
										}else{
											return true;
										}
										$asa = 'smart';
									}
									# END CONDITION
								}		
							}else{	
								if($smart_row) {
										$ret = "true";
									if($profile->login_mobile_number !==""){
										$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
										$ret = "true";
									}else{
										$ret = "false";
									}
									# ACTIVITY LOGS
									#	iactivity_logs($company_id,' has been using mobile transactions');
									# END ACTIVITY LOGS		
									if($json==true){	
										echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
										return false;	
									}else{
										return true;
									}				
								}	
							}	
					}		
			}else{
				if($json==true){	
					echo json_encode(array("send_sms"=>"false",'profile'=>'',"sms_process"=>'',"sms_provider"=>"noprovider"));
					return false;
				}else{
					return true;
				}	
				
			}							
	}
	
	
	
	/*** VERSION #3 *************/
	
	/**
	 * SEND SMS WITHOUT HAVING ANY USER TYPE RIGHTS JUST OVERALL
	 * @param int $account_id
	 * @param string $message
	 * @param int $psa_id
	 * @param object $json
	 * @return json
	 */
	function isms_sender_global($account_id,$message="",$psa_id=NULL,$json=false){     
		$globe_numbers = array(
			'63905','63906','63915','63926','63916','63917','639178','63926','63927','63935','63936','63937','63975','63973','63974','63977','63978','63979','63994','63996','63997','0817','63922',
			'63923'
		);
		$smart_numbers = array(
			'63907','63908','639639','63910','63912','63918','63919','63920','63921','63922','63923','63925','63932','63933','63934','63938','63939','63942','63943','63946','63947',
			'63948','63949','63989','63998','63999','63928','63948','63929','63917'
		);
		$sms_process = array();
		$CI =& get_instance();
			
		# CHECK OUR ID FIRST VALIDATE THIS
		$where = array(
			"a.account_id" 	=> $account_id,
			"a.deleted"		=> "0"
		);
		
		#$CI->edb->select("e.emp_id,a.email,a.login_mobile_number,e.first_name,e.last_name,e.middle_name,a.profile_image,a.payroll_system_account_id,e.company_id,a.account_id");
		$CI->edb->select("e.emp_id,a.email,a.login_mobile_number,e.first_name,e.last_name,e.middle_name,a.profile_image,a.payroll_system_account_id,e.company_id,a.account_id,
				a.verified_status_2,a.flag_primary,a.login_mobile_number_2,a.flag_verified
		");
		#BOGO STARTs HERE 
		
		$CI->edb->where($where);
		$CI->edb->join("employee AS e","a.account_id = e.account_id","LEFT");
		$query = $CI->edb->get("accounts AS a");
		$profile = $query->row();
		$query->free_result();
		$asa = '';
				if($psa_id ==""){
					$psa_id =  $CI->session->userdata("psa_id");
				}
				if($profile) {
					############################ FUCK START HERER
					$flag_primary = $profile->flag_primary;
					$primary_mobile = $profile->login_mobile_number;
					
					if($flag_primary  == 'login_mobile_number_2'){
						$primary_mobile = $profile->login_mobile_number_2;
					}
					
					############################ END FUCK START HERE
					
					$user_mobile_number = substr($primary_mobile,0,5);
					# CHECK IF HE HAS A SMART DATA SAVED ON THEIR SETTINGS
						
					#SMART CHECK
						$where_smart = array('psa_id' =>$psa_id);
						$CI->edb->where($where_smart);
						$smart_data = $CI->edb->get('smart_mobile_api');
						$smart_row = $smart_data->row();
						$smart_data->free_result();
						
					# END SMART
						
					# GLOBE CHECK
						$where_globe = array('psa_id'=>$psa_id);
						$CI->edb->where($where_globe);
						$globe_data = $CI->edb->get('globe_mobile_api');
						$globe_row = $globe_data->row();
					# END GLOBE CHECK
						
					# GET OUR EMPLOYEES ACCOUNT FIELDS
					$first_name = "";
					$last_name = "";
					if($profile->first_name) $first_name = substr($profile->first_name,0,1);
					if($profile->last_name) $last_name = substr($profile->last_name,0,1);
					$send_password = date("YdHs").$first_name.$last_name;
					$send_verification_code = date("YdHs").$first_name.$last_name;
					# END GET OUR EMPLOYESS ACCOUNT FIELDS
	
					if(in_array($user_mobile_number,$smart_numbers)) {
						if($smart_row) {
							$ret = "true";
							$asa = 'smart';
							if($primary_mobile  !==""){
								$sms_process = open_send_smart_sms_with_errors($smart_row->token,'SENDSMS',$primary_mobile,$message);
								$ret = "true";
							}else{
								$ret = "false";
							}
							# ACTIVITY LOGS
							#iactivity_logs($company_id,' has been using mobile transactions');
							# END ACTIVITY LOGS
							if($json==true){
								echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart","sms_return"=>$sms_process));
								return false;
							}else{
								return array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart","sms_return"=>$sms_process);
							}
						}else{
							if($globe_row){
								$token_globe = get_curl_globe_token($globe_row->organization_name,$globe_row->text_id,$globe_row->password,$globe_row->app_id);
								$ret = "true";
								if($token_globe){
									$asa = 'globe';
									$recipients = $primary_mobile;
									if($recipients  !=="") {
										$ret = "true";
										$sms_process = sms_httpGetWithErros('https://txtconnectlc.globe.com.ph/api/sendmessage?token='.$token_globe.'&recipients='.$recipients.'&message='.$message);
									}else{
										$ret = "false";
									}
									# ACTIVITY LOGS
									#		iactivity_logs($company_id,' has been using mobile transactions');
									# END ACTIVITY LOGS
									if($json==true){
										echo json_encode(array("send_sms"=>$ret,'profile'=>$employee_check,'smsprovider'=>"globe","sms_return"=>$token_globe,"sms_return"=>$token_globe));
										return false;
									}else{
										return array("send_sms"=>$ret,'profile'=>$employee_check,'smsprovider'=>"globe","sms_return"=>$token_globe,"sms_return"=>$token_globe);
									}
								}else{
									#  ADDED O CONDITIOHN
									$ret = "true";
									if($smart_row){
										$asa = 'smart';
										if($primary_mobile  !==""){
											$sms_process = open_send_smart_sms_with_errors($smart_row->token,'SENDSMS',$primary_mobile,$message);
											$ret = "true";
										}else{
											$ret = "false";
										}
										# ACTIVITY LOGS
										#	iactivity_logs($company_id,' has been using mobile transactions');
										# END ACTIVITY LOGS
										if($json==true){
											echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart","sms_return"=>$sms_process));
											return false;
										}else{
											return array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart","sms_return"=>$sms_process);
										}
									}
									# END CONDITION
								}
							}
						}
					}else if(in_array($user_mobile_number,$globe_numbers)) {
						if($globe_row){
	
							$token_globe = get_curl_globe_token($globe_row->organization_name,$globe_row->text_id,$globe_row->password,$globe_row->app_id);
							if($token_globe){
								$recipients = $primary_mobile;
								$ret = "true";
								$asa = 'globe';
								if($recipients  !==""){
									sms_httpGetWithErros('https://txtconnectlc.globe.com.ph/api/sendmessage?token='.$token_globe.'&recipients='.$recipients.'&message='.$message);
									$ret = "true";
								}else{
									$ret = "false";
								}
								# ACTIVITY LOGS
								#	iactivity_logs($company_id,' has been using mobile transactions');
								# END ACTIVITY LOGS
								if($json==true){
									echo json_encode(array("send_sms"=>$ret,'profile'=>$employee_check,'smsprovider'=>"globe","sms_return"=>$token_globe,"sms_return"=>$token_globe));
									return false;
								}else{
									return array("send_sms"=>$ret,'profile'=>$employee_check,'smsprovider'=>"globe","sms_return"=>$token_globe,"sms_return"=>$token_globe);
								}
							}else{
								#  ADDED O CONDITIOHN
								$ret = "true";
								if($smart_row){
									if($primary_mobile  !==""){
										$sms_process = open_send_smart_sms_with_errors($smart_row->token,'SENDSMS',$primary_mobile,$message);
										$ret = "true";
									}else{
										$ret = "false";
									}
									# ACTIVITY LOGS
									#iactivity_logs($company_id,' has been using mobile transactions');
									# END ACTIVITY LOGS
									if($json==true){
										echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart","sms_return"=>$sms_process));
										return false;
									}else{
										return array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart","sms_return"=>$sms_process);
									}
									$asa = 'smart';
								}
								# END CONDITION
							}
						}else{
							if($smart_row) {
								$ret = "true";
								if($primary_mobile !==""){
									$sms_process = open_send_smart_sms_with_errors($smart_row->token,'SENDSMS',$primary_mobile,$message);
									$ret = "true";
								}else{
									$ret = "false";
								}
								# ACTIVITY LOGS
								#	iactivity_logs($company_id,' has been using mobile transactions');
								# END ACTIVITY LOGS
								if($json==true){
									echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart","sms_return"=>$sms_process));
									return false;
								}else{
									return array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart","sms_return"=>$sms_process);
								}
							}
						}
					}
				}else{
					
					if($json==true){
						echo json_encode(array("send_sms"=>"false",'profile'=>'',"sms_process"=>'',"sms_provider"=>"noprovider","sms_return"=>''));
						return false;
					}else{
						return array("send_sms"=>"false",'profile'=>'',"sms_process"=>'',"sms_provider"=>"noprovider","sms_return"=>'');
					}
	
				}
	}
	
	/**
	 * SEND MOBILE SMS USING SMART COMMUNICATIN
	 * Enter description here ...
	 * @param string $token
	 * @param string $method
	 * @param number $number
	 * @param string $message
	 * @return object
	 */
	function open_send_smart_sms_with_errors($token,$method="SENDSMS",$number,$message){
		require_once("./lib/nusoap.php");
		$client = new nusoap_client('https://ws.smartmessaging.com.ph/soap?wsdl', true);
		$parameters = array(
				array(
						'token' => $token
				)
		);
		$parameters = array(
				array(
						'token' 	=> 	$token,
						'msisdn' 	=> $number,
						'message' 	=> $message
				)
		);
		$return = $client->call($method, $parameters);
		return $return;
	}
	
	function send_this_sms_global2($account_id,$message="",$psa_id=NULL,$json=false){
		$globe_numbers = array(
				'63905','63906','63915','63926','63916','63917','639178','63926','63927','63935','63936','63937','63975','63973','63974','63977','63978','63979','63994','63996','63997','0817','63922',
				'63923'
		);
		$smart_numbers = array(
				'63907','63908','639639','63910','63912','63918','63919','63920','63921','63922','63923','63925','63932','63933','63934','63938','63939','63942','63943','63946','63947',
				'63948','63949','63989','63998','63999','63928','63948','63929','63917'
		);
	
		$CI =& get_instance();
			
		# CHECK OUR ID FIRST VALIDATE THIS
		$where = array(
		"a.account_id" 	=> $account_id,
		"e.status"		=> "Active",
				"a.deleted"		=> "0"
			);
				$CI->edb->select("e.emp_id,a.email,a.login_mobile_number,e.first_name,e.last_name,e.middle_name,a.profile_image,a.payroll_system_account_id,e.company_id,a.account_id");
				$CI->edb->where($where);
				$CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
				$query = $CI->edb->get("employee AS e");
				$profile = $query->row();
				$query->free_result();
				$asa = '';
				if($psa_id ==""){
				$psa_id =  $CI->session->userdata("psa_id");
				}
				if($profile) {
				$user_mobile_number = substr($profile->login_mobile_number,0,5);
				# CHECK IF HE HAS A SMART DATA SAVED ON THEIR SETTINGS
					
				#SMART CHECK
				$where_smart = array('psa_id' =>$psa_id);
				$CI->edb->where($where_smart);
				$smart_data = $CI->edb->get('smart_mobile_api');
				$smart_row = $smart_data->row();
					$smart_data->free_result();
						
					# END SMART
					
				# GLOBE CHECK
				$where_globe = array('psa_id'=>$psa_id);
				$CI->edb->where($where_globe);
					$globe_data = $CI->edb->get('globe_mobile_api');
						$globe_row = $globe_data->row();
	
							# END GLOBE CHECK
					
				# GET OUR EMPLOYEES ACCOUNT FIELDS
				$first_name = "";
				$last_name = "";
				if($profile->first_name) $first_name = substr($profile->first_name,0,1);
				if($profile->last_name) $last_name = substr($profile->last_name,0,1);
				$send_password = date("YdHs").$first_name.$last_name;
				$send_verification_code = date("YdHs").$first_name.$last_name;
				# END GET OUR EMPLOYESS ACCOUNT FIELDS
	
				if(in_array($user_mobile_number,$smart_numbers)) {
				if($smart_row) {
				$ret = "true";
				$asa = 'smart';
					if($profile->login_mobile_number  !==""){
					$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
					$ret = "true";
					}else{
					$ret = "false";
					}
	
					if($json==true){
							echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
									return false;
				}else{
					return true;
				}
							}else{
							if($globe_row){
							$token_globe = get_curl_globe_token($globe_row->organization_name,$globe_row->text_id,$globe_row->password,$globe_row->app_id);
							$ret = "true";
							if($token_globe){
							$asa = 'globe';
							$recipients = $profile->login_mobile_number;
								if($recipients  !=="") {
								$ret = "true";
								sms_httpGetWithErros('https://txtconnectlc.globe.com.ph/api/sendmessage?token='.$token_globe.'&recipients='.$recipients.'&message='.$message);
								}else{
									$ret = "false";
								}
									
											if($json==true){
								echo json_encode(array("send_sms"=>$ret,'profile'=>$employee_check,'smsprovider'=>"globe"));
								return false;
								}else{
										return false;
								}
								}else{
								#  ADDED O CONDITIOHN
								$ret = "true";
									if($smart_row){
									$asa = 'smart';
										if($profile->login_mobile_number  !==""){
										$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
										$ret = "true";
										}else{
												$ret = "false";
										}
											
										if($json==true){
				echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
				return false;
				}else{
				return true;
				}
				}
				# END CONDITION
									}
									}
									}
									}else if(in_array($user_mobile_number,$globe_numbers)) {
									if($globe_row){
	
									$token_globe = get_curl_globe_token($globe_row->organization_name,$globe_row->text_id,$globe_row->password,$globe_row->app_id);
									if($token_globe){
									$recipients = $profile->login_mobile_number;
									$ret = "true";
									$asa = 'globe';
									if($recipients  !==""){
									sms_httpGetWithErros('https://txtconnectlc.globe.com.ph/api/sendmessage?token='.$token_globe.'&recipients='.$recipients.'&message='.$message);
								$ret = "true";
								}else{
									$ret = "false";
									}
	
									if($json==true){
									echo json_encode(array("send_sms"=>$ret,'profile'=>$employee_check,'smsprovider'=>"globe"));
									return false;
									}else{
										return true;
									}
									}else{
											#  ADDED O CONDITIOHN
									$ret = "true";
									if($smart_row){
									if($profile->login_mobile_number  !==""){
										$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
											$ret = "true";
										}else{
										$ret = "false";
										}
	
										if($json==true){
										echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
										return false;
										}else{
										return true;
										}
										$asa = 'smart';
										}
										# END CONDITION
										}
										}else{
										if($smart_row) {
										$ret = "true";
										if($profile->login_mobile_number !==""){
										$sms_process = open_send_smart_sms($smart_row->token,'SENDSMS',$profile->login_mobile_number,$message);
										$ret = "true";
									}else{
									$ret = "false";
									}
	
									if($json==true){
									echo json_encode(array("send_sms"=>$ret,'profile'=>$profile,"sms_process"=>$sms_process,"sms_provider"=>"smart"));
									return false;
									}else{
											return true;
									}
									}
									}
									}
									}else{
									if($json==true){
									echo json_encode(array("send_sms"=>"false",'profile'=>'',"sms_process"=>'',"sms_provider"=>"noprovider"));
									return false;
									}else{
									return true;
									}
	
									}
	}
	
	/*** END OF VERSION #3 ******/
