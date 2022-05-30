<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller{
	var $verify;
		
	public function __construct(){
	   parent::__construct();	
	  //$this->load->model("loginmodel","lm");  
	   $this->load->model('account_model');
	   $this->load->library('parser');
	   $this->load->library('email');
	   $this->load->library('session');
	   
	}

	public function index(){

        // $post = file_get_contents('php://input');	
        
        // if (!isset($post)) {
        //     return false;
        // }

        // $postData = json_decode($post);
        
		// @$email = trim($post["email"]," ");
		$email = $this->input->post('email');
        $pass = $this->input->post('pass');
        
        $version_checker = $this->input->post('version');
        if ($version_checker) {
            if ($version_checker != '3') {
                $result = array('result' => 0, 'msg' => "Please update your app to the latest version.");
                echo json_encode($result);
                return false;
            }            
        } else {
            $result = array('result' => 0, 'msg' => "Please update your app to the latest version.");
            echo json_encode($result);
            return false;
        }

		if (!trim($email) || !trim($pass)) {
            $result = array('result' => 0, 'msg' => "Invalid login details.");
            echo json_encode($result);
            return false;
		}

		// $pass = $this->authentication->encrypt_password(trim($post["pass"]," "));
		$pass = $this->authentication->encrypt_password(trim($pass));
		
		$konsum_key = konsum_key();
		
		if ($email && $pass) {
			
			$where = array(
				//'accounts.email' 			=> $email,
				'accounts.password' 		=> $pass,
				'accounts.account_type_id'  => 2,
				'accounts.deleted'			=> 0
			);
				
		}

		$noemail_checker = $this->noemail_checker($email);
		if($noemail_checker){
			$payroll_cloud_id = substr($email,5,200);
			$where = array( 
					'accounts.payroll_cloud_id' 	=> $this->db->escape_str($payroll_cloud_id),
					'accounts.password' 			=> $this->db->escape_str($pass),
					'accounts.account_type_id'  	=> 2,
					'accounts.deleted'				=> 0,
					'company.company_id'			=> $noemail_checker->company_id,
			);
		}

		$email = strtolower($email);

		$select = array(
				'accounts.account_id',
				'accounts.account_type_id',
				'accounts.payroll_system_account_id',
				'accounts.user_type_id',
                'employee.emp_id',
                'employee.company_id',
                'accounts.payroll_cloud_id',
                'accounts.email',
                'accounts.profile_image',
                'employee.first_name',
                'employee.last_name'
		);
			
		$this->edb->select($select);
		$email = strtolower($email);

		if ( ! $noemail_checker) {
		
			$this->db->where("( LOWER(CONVERT(AES_DECRYPT(accounts.email, '{$konsum_key}') USING latin1)) = '{$email}' OR ((accounts.login_mobile_number = AES_ENCRYPT('{$email}', '{$konsum_key}') AND flag_primary = 'login_mobile_number') OR (accounts.login_mobile_number_2 = AES_ENCRYPT('{$email}', '{$konsum_key}') AND flag_primary = 'login_mobile_number_2')))", NULL, FALSE);
		}
		$this->edb->where($where);
		
		$this->edb->join('employee','accounts.account_id = employee.account_id','left');
		if ($noemail_checker) {
			$this->edb->join('company','employee.company_id = company.company_id','left');
		}
		$q = $this->edb->get("accounts");
		
		$r = $q->row();
		
		if($r){
			if($r->user_type_id == 5) {
				
				$is_active = $this->account_model->get_employee_status($r->emp_id);
				if ($is_active) {
					if ($is_active->employee_status == 'Active') {
						
						$newdata = array(
								'account_id'  	  => $r->account_id,
								'account_type_id' => $r->account_type_id,
								'psa_id'  		  => $r->payroll_system_account_id,
								'user_type_id' 	  => $r->user_type_id,
                                'emp_id'	      => $r->emp_id,
                                'dp'              => $r->profile_image,
								'logged_in'		  => TRUE
						);
                        
                        $approver = false;
                        $time_in = false;
						$overtime = false;
						$leave =  false;
						$shifts =  false;
                        $mobile = false;
                        
                        $is_approver = is_approver($r->emp_id);
                        
                        if ($is_approver) {
                            $approver = true;                            
                            foreach ($is_approver as $is_apr) {
                                if ($is_apr->name == 'Add Timesheet' || $is_apr->name == 'Timesheet Adjustment') {
                                    $time_in = true;
                                }
                                if ($is_apr->name == 'Overtime') {
                                    $overtime = true;
                                }
                                if ($is_apr->name == 'Leave') {
                                    $leave = true;
                                }
                                if ($is_apr->name == 'Shifts') {
                                    $shifts = true;
                                }
                                if ($is_apr->name == 'Mobile Clock-in') {
                                    $mobile = true;
                                }
                            }
                            
                        }
                        
                        $entitled_ot = ($is_active->entitled_to_overtime == "yes") ? true : false;
                        $entitled_lv = ($is_active->entitled_to_leaves == "yes") ? true : false;
                        						
						$result = array(
                                'result'	    => 1,
                                'account_type_id' => $r->account_type_id,
                                'user_type_id' 	  => $r->user_type_id,
                                'logged_in'		  => TRUE,
                                'is_approver' 	=> ($approver),
                                'timein_app'	=> ($time_in),
                                'overtime_app'	=> ($overtime),
                                'leave_app'		=> ($leave),
                                'shifts_app'	=> ($shifts),
                                'mobile_app'	=> ($mobile),
                                'entitle_ot'    => ($entitled_ot),
                                'entitle_lv'    => ($entitled_lv),
                                'psa_id'        => $r->payroll_system_account_id,
                                'account_id'    => $r->account_id,
                                'emp_id'        => $r->emp_id,
                                'comp_id'       => $r->company_id,
                                'cloud_id'      => $r->payroll_cloud_id,
                                'email'         => $r->email,
                                'profile'       => $r->profile_image,
                                "fname"         => ($r->first_name) ? ucfirst($r->first_name) : "",
                                "lname"         => ($r->last_name) ? ucfirst($r->last_name) : ""
                        );
                        $this->session->set_userdata($result);
						
						
					} else {
						#$this->_CI->session->set_flashdata("globalwarn_invalid","<div class='a-msg-error'>This account has been deactivated.</div>");
						#redirect('/login');
						$result = array('result' => 0, 'msg' => "This account has been deactivated.");
					}
				}
				
				
			} else {
				$result = array('result' => 0, 'msg' => "Only Employee Accounts can log-in to this app.");
			}
			
			
		}else{
			$result = array('result' => 0, 'msg' => "The login details you entered don't match.");
        }
        // p($_SESSION);
		echo json_encode($result);
	}
	
	public function noemail_checker($post){
		$post = $this->db->escape_str($post);
		if (strpos($post, '*') !== false){
			$aste =  substr($post,0,1);
			$dash =  substr($post,4,1);
			$login_key = substr($post,1,3);
			$payroll_cloud_id = substr($post,5,200);
			
			if($aste == "*" && $dash =="-"){
				
				$where_c = array(
					"login_key"	=> $login_key
				);
				$this->db->where($where_c);
				$select = array(
					"login_key", "status", "company_id"
				);
				$this->db->select($select);
				$check_our_company_id = $this->db->get("company");
				$qr = $check_our_company_id->row();
				$check_our_company_id->free_result();
				if($qr){
					return $qr;
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
    
    public function get_session_data() {
        if ($this->session->userdata('emp_id')) {

            $ses_data = $this->session->all_userdata();

            echo json_encode(array("result" => "1", "msg" => "Session found.", "session" => $ses_data));
            return false;
        } else {
            echo json_encode(array("result" => "0", "msg" => "No session, logout user."));
            return false;
        }
    }
	
	public function logout(){
		$this->authentication->logout();
		echo json_encode(array('result'=>1));
	}
	
	public function forget_password(){
		$post = $this->input->post();
		$email = $post['email'];
		
		if($email){
			$email_checker  = $this->check_email($email);
			if($email_checker){

				$where = array('email'=> $email);
				$this->edb->where($where);
				$query = $this->edb->get("accounts");
				$invitations = $query->row();
				$update_where = array('email'=>$email);
				$this->edb->where($update_where);
				$password = tokenize();
				$field = array("password"=>$this->authentication->encrypt_password($password));
				$this->edb->update('accounts',$field);
				$update_check = $this->db->affected_rows();
				 
				if($update_check){
					$mail_true = $this->invite_email($invitations->account_id,$password);
					if($mail_true){
						$result = array(
							'result' =>1
						);
					}
				}
				echo json_encode($result);
				return false;
			} else {
				$result = array(
						'result' =>0
				);
		
				echo json_encode($result);
				return false;
			}
		}
	
	}
	public function check_email($email){
		if($email){
			$where = array(
					'email'=>$email
			);
			$this->edb->where($where);
			$query = $this->edb->get("accounts");
			$row = $query->row();
			if($row){
				return TRUE;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	public function invite_email($account_id,$password){
		$account_id = intval($account_id);
		$profile = $this->check_account_id($account_id);
		
		$account_id = intval($account_id);
		$profile = $this->check_account_id($account_id);
		
		
		if($profile){
			$name = $profile->first_name;
				
			$data = array(
					"title"				=> "Invitations",
					"page_content" 		=> "Invitations",
					"token"				=> $profile->token,
					"page_title"		=> $profile->email,
					"email_address"		=> $profile->email,
					"full_name"			=> ucfirst($name),
					"base_url"			=> base_url(),
					"admin"				=> "Payroll Team"
			);
		
			//$content = $this->parser->parse("email_forgot_password_view",$data);
			
			$content = '<html>
						  <head>
						      <title>{page_title}</title>
						  </head>
						  <body style=\"margin:0 auto;\">
						      <div style=\"width:600px;margin:0 auto;\">
							     <h3>Hi '.$data['full_name'].',</h3>
							     <p>You requested us to retreive your password.  Please find below instructions on how to reset your password.</p>
							     <p>Your email address : '.$data['email_address'].'</p>
							     <p>
								    Your new password is '.$password.'
							     </p>
	
							     <p>
							         Sincerely,<br />
							         Ashima
							     </p>
						      </div>
						  </body>
 						</html>';
			
			$this->email->clear();
			$config['wordwrap'] = TRUE;
			$config['mailtype'] = 'html';
			$config['charset'] = 'utf-8';
			
			$this->email->initialize($config);
			$this->email->set_newline("\r\n");
			$this->email->from(notifications_ashima_email(), 'Ashima Account Recovery');
			$this->email->to($profile->email);
			$this->email->subject('Reset Your Ashima Password');
			$this->email->message($content);
			
			$email_check = $this->email->send();
				
			return true;
		}else{
			return false;
		}
	
	}
	function check_account_id($account_id){
	
		$where = array(
				"account_id" =>$account_id,
				"deleted"=>"0"
		);
		$this->edb->where($where);
		$query = $this->edb->get("accounts");
		$row = $query->row();
		$query->free_result();
		
		if($row){
		    $profile_row = false;
			switch($row->user_type_id){
				case "1": #ADMIN
					$where_admin = array(
        					"a.account_id"=> $account_id,
        					"ka.status"=>"Active",
        					"a.deleted"=>"0",
        					"a.user_type_id"=>"1"
					);
					
        				$this->edb->where($where_admin);
        
        				$this->edb->join("accounts AS a","a.account_id = ka.account_id","INNER");
        				$query_admin = $this->edb->get("konsum_admin AS ka");
        				$profile_row = $query_admin->row();
        				$query_admin->free_result();
        				break;
        				
				case "2": #owner
					$where_owner = array(
        					"a.account_id"=> $account_id,
        					"a.deleted"=>"0",
        					"a.user_type_id"=>"2"
					);
					
					$this->edb->where($where_owner);
					$select = array("a.account_id","co.first_name",",a.token","co.last_name","co.middle_name","a.email","a.user_type_id","co.owner_name");
					$this->edb->select($select);
					$this->edb->join("accounts AS a","a.account_id = co.account_id","INNER");
					$query_owner = $this->edb->get("company_owner AS co");
					$profile_row = $query_owner->row();
					$query_owner->free_result();
					break;
					
				case "3": #hr
			        $where_hr = array(
    					   "a.account_id"=> $account_id,
    					   "e.status"=>"Active",
    					   "a.deleted"=>"0",
    					   "a.user_type_id"=>"3"
				    );
					   
				    $this->edb->where($where_hr);
				    $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
				    $this->edb->select("a.account_id,e.first_name,e.last_name,a.email,e.emp_id,a.user_type_id,a.token");
				    $query_hr = $this->edb->get("employee AS e");
				    $profile_row = $query_hr->row();
				    $query_hr->free_result();
				    break;
						
				case "4": #accountant
					$where_accnt = array(
        					"a.account_id"=> $account_id,
        					"e.status"=>"Active",
        					"a.deleted"=>"0",
        					"a.user_type_id"=>"4"
					);
        					$this->edb->where($where_accnt);
        					$this->edb->select("a.account_id,e.first_name,e.last_name,a.email,e.emp_id,a.user_type_id,a.token");
        					$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
        					$query_accnt = $this->edb->get("employee AS e");
        					$profile_row = $query_accnt->row();
        					$query_accnt->free_result();
        					break;
        					
				case "5": #employee
				    $where_employee = array(
						"a.account_id"=> $account_id,
						"e.status"=>"Active",
						"a.deleted"=>"0",
						"a.user_type_id"=>"5"
				    );
				    
					$this->edb->where($where_employee);
					$this->edb->select("a.account_id,e.first_name,e.last_name,a.email,e.emp_id,a.user_type_id,a.token");
					$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
					$query_employee = $this->edb->get("employee AS e");
					$profile_row = $query_employee->row();
					$query_employee	->free_result();
					break;
			}
			
			return $profile_row;
		} else {
			return false;
		}
	}
	
	public function change_login_background() {

	    $image_day = array(
	        "bgLogin_4.jpg","bgLogin.png", "bgLogin_1.jpg","bgLogin_2.jpg","bgLogin_3.jpg","bgLogin_4.jpg","bgLogin_1.jpg","bgLogin_2.jpg"
	    );
	    
	    $image_night = array(
	        "bgLogin_8.jpg","bgLogin_5.jpg","bgLogin_6.jpg","bgLogin_7.jpg","bgLogin_8.jpg","bgLogin_5.jpg","bgLogin_6.jpg","bgLogin_7.jpg"
	    );
	    
	    $today = date("Y-m-d H:i:s");
	    $today_day = date("Y-m-d");
	    $today_start =  date("Y-m-d H:i:s",strtotime($today_day." 18:00:00"));
	    $today_end =  date("Y-m-d H:i:s",strtotime($today_day." 6:00:00 +1 day"));
	    
	    if(strtotime($today) >= strtotime($today_start) && strtotime($today) <= strtotime($today_end)){
	        $n = date("N");
	        
	        if($n <= 5){
	            $images_final = $image_night[$n+1];
	        }
	        
	        $night_days = date("N",strtotime(date("Y-m-d")));
	        if( $night_days == '6' ||  $night_days == '7'){
	            
	            $images_final = $image_night[array_rand($image_night)];
	        }
	    }else{
	        $images_final = $image_day[date("N")];
	    }
	    
	    if($images_final) {
	        echo json_encode(array("result" => "1", "bg_image" => $images_final));
	        return false;
	    } else {
	        echo json_encode(array("result" => "0"));
	        return false;
	    }
	    
	}

}