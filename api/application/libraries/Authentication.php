<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Konsum Payroll Standard Authentication
 * 
 * This will secure every web page in every account who log-in.
 * All configuration settings are located in ./application/config/konsum_auth.php
 * This includes the login both admin and user, restrict to specific groups, and logging out.
 * 
 * @author Kris Edward Galanida
 *
 */
class Authentication {
	/**
     * Codeigniter Instance
     * @var codeigniter
     */
	protected $_CI;

	/**
	 * Constructor
	 */
	public function __construct()
	{
        $this->_CI =& get_instance();
		//$this->_CI->load->model('account_model');
		//$this->_CI->config->load('konsum_auth');
    }

    /**
     * Validate login for admin or user
     * @param string $user
     * @param string $pass
     * @param int $account_type
     */
    public function validate_login($user,$pass,$account_type)
    {
		// admin
		if ($account_type == 1) {
			$q = $this->_CI->account_model->get_admin_account($user,$pass);
			
			// if account exist
			if ($q) {
				$newdata = array(
                    'account_id'  	  => $q->account_id,
					'account_type_id' => $q->account_type_id,
					'logged_in'		  => TRUE
				);
				$this->_CI->session->set_userdata($newdata);
				redirect('/admin/dashboard');
			} else {
				redirect('/login/admin');
			}
		// user
		} else {
			$q = $this->_CI->account_model->get_account($user,$pass);
			
			if ($q) {
				
				$newdata = array(
                    'account_id'  	  => $q->account_id,
				    'account_type_id' => $q->account_type_id,
				    'psa_id'  		  => $q->payroll_system_account_id,
				    'user_type_id' 	  => $q->user_type_id,
				    'sub_domain' 	  => $q->sub_domain,
				    'company_name' 	  => $q->company_name,
				    'emp_id'	      => $q->emp_id,
					'logged_in'		  => TRUE
				);
				if ($q->user_type_id == 3 || $q->user_type_id == 2) {
					// redirect owner or hr
					$this->_CI->session->set_userdata($newdata);
					
					redirect("/{$q->sub_domain}/dashboard/company_list");
				} elseif ($q->user_type_id == 5) {
					// redirect employee
					$this->_CI->session->set_userdata($newdata);
					
					redirect("/{$q->company_subdomain}/employee/emp_time_in");
				} elseif ($q->user_type_id == 6) {
					
					//redirect("/{$q->company_subdomain}/log_in/emp");
					
					$check_static_ip_address = $this->_CI->account_model->check_static_ip_address($q->emp_id,get_ip());
					if($check_static_ip_address){
						$this->_CI->session->set_userdata($newdata);
						redirect("/company/log_in/emp");
					}else{
						$this->_CI->session->set_flashdata("error_denied","Your ip address is not registered in this company");
						redirect('/');
					}
				}
			} else {
				$this->_CI->session->set_flashdata("error_denied","The email or password is invalid");
				redirect('/');
			}
		}
    }
    
	/**
     * Validate login for admin or mobile
     * @param string $user
     * @param string $pass
     * @param int $account_type
     */
    public function validate_login_mobile($mobile,$pass,$account_type)
    {
		// user
		if($account_type == 2) {
			$q = $this->_CI->account_model->get_account_mobile($mobile,$pass);
			if ($q) {
				
				$newdata = array(
                    'account_id'  	  => $q->account_id,
				    'account_type_id' => $q->account_type_id,
				    'psa_id'  		  => $q->payroll_system_account_id,
				    'user_type_id' 	  => $q->user_type_id,
				    'sub_domain' 	  => $q->sub_domain,
				    'company_name' 	  => $q->company_name,
				    'emp_id'	      => $q->emp_id,
					'logged_in'		  => TRUE
				);
				if ($q->user_type_id == 3 || $q->user_type_id == 2) {
					// redirect owner or hr
					$this->_CI->session->set_userdata($newdata);
					
					redirect("/{$q->sub_domain}/dashboard/company_list");
				} elseif ($q->user_type_id == 5) {
					// redirect employee
					$this->_CI->session->set_userdata($newdata);
					
					redirect("/{$q->company_subdomain}/employee/emp_time_in");
				} elseif ($q->user_type_id == 6) {
					
					$check_static_ip_address = $this->_CI->account_model->check_static_ip_address($q->emp_id,get_ip());
					if($check_static_ip_address){
						$this->_CI->session->set_userdata($newdata);
						redirect("/company/log_in/emp");
					}else{
						$this->_CI->session->set_flashdata("error_denied","Your ip address is not registered in this company");
						redirect('/');
					}
					
				}
			} else {
				$this->_CI->session->set_flashdata("error_denied","The mobile or password is invalid");
				redirect('/');
			}
		}else{
			$this->_CI->session->set_flashdata("error_denied","The mobile or password is invalid");
			redirect('/');
		}
    }
    
	/**
     * Restricts a page for the particular group.
     * @param string $group The group that is allowed to access
     * @return mixed Returns true if user has access otherwise
     *  show 404 error.
     */
    public function restrict($group=null,$except=FALSE)
    {
    	// add
    	$user_type_id = $this->_CI->session->userdata('user_type_id');
    	if($user_type_id == 6) show_error('You have no rights to access this page.');
    	
    	$key_group = $this->_CI->config->item('konsum_groups');
    	
    	if (!array_key_exists($group, $key_group)) {
    		if (ENVIRONMENT !== 'production') {
    			show_error('Invalid group');
    		} else {
    			show_404();
    		}
    	}
    	
        $sess_group = $this->_CI->session->userdata('user_type_id');
        $active_group = $key_group[$group];
        
        if (!$sess_group || !$active_group) {
        	redirect('/');
        }

        if ($group == NULL) { // Unrestricted
            return true;
        }
        
        // Check if currently login user have access.
        if (!$except && $sess_group == $active_group) {
            // allow access
            return true;
        } elseif ($except && $sess_group != $active_group) {
        	// allow access
        	return true;
        } else {
            // access denied.
        	if (ENVIRONMENT !== 'production') {
        		if ($except) {
        			show_error($group.' cannot access this page');
        		} else {
        			show_error('Only '.$group.' can access this page');	
        		}
    		} else {
    			show_404();
    		}
        }
    }
    
    /**
     * Only employee group can access
     */
    public function restrict_employee()
    {
    	// add
    	$user_type_id = $this->_CI->session->userdata('user_type_id');
    	if($user_type_id == 6) show_error('You have no rights to access this page.');
    	
    	$subdomain = $this->_CI->uri->segment(1);
    	$account_id = $this->_CI->session->userdata('account_id');
    	
    	if (!$subdomain) {
    		if (ENVIRONMENT !== 'production') {
    			show_error('No subdomain selected');
    		} else {
    			show_404();
    		}
    	}
        
        if (!$account_id) {
        	redirect('/');
        }
    	
    	$employee = $this->_CI->account_model->check_employee($account_id);
    	$company  = $this->_CI->account_model->get_company($subdomain);
    	
    	if (!$company) {
    		if (ENVIRONMENT !== 'production') {
    			show_error('No company exists');
    		} else {
    			show_404();
    		}
    	} 
    	
    	if ($company->company_id == $employee->company_id) {
    		// allow access
    		return true;
    	} else {
    		if (ENVIRONMENT !== 'production') {
    			show_error('This employee is not register to this company');
    		} else {
    			show_404();
    		}
    	}
    }
    
	/**
	 * Not sure if this is being used
	 */
	public function check_if_logged_in()
	{
		$account = $this->_CI->session->userdata('account_id');
		$account_type_id = $this->_CI->session->userdata("account_type_id");
		$user_type_id = $this->_CI->session->userdata("user_type_id");
		$uri_admin = $this->_CI->uri->segment(1);
		
		# added by christopher cuizon updated on jc code
		if ($account=="") redirect('/login/access_denied');
		switch($account_type_id):
			case "1": // admin
				return ($uri_admin == 'admin') ? true : redirect('/');
			break;
			case "2": // user
				if($uri_admin == 'admin') redirect('/'); #wtf dong you are not invited
			break;			
		endswitch;	
		# this functions enable to check if the person is trying to play our security defenses therefore we must check if the data is really valid and let her in if its true otherwise then redirect to access denied
		if($this->_CI->uri->segment(2) == "dashboard" && $this->_CI->uri->segment(3) == "company_list"){
			$letme_in = $this->_CI->account_model->dashboard_access($this->_CI->session->userdata('psa_id'),trim($this->_CI->uri->segment(1)));
			(!$letme_in) ? redirect('/') : '';
		} 
	}
	
	/**
	 * Logout
	 */
	public function logout()
	{
		$account_type_id = $this->_CI->session->userdata('account_type_id');
		if($account_type_id==1){
			$this->destroy_session();
			//redirect('/login/admin');
		}else{
			$this->destroy_session();
			//redirect('/');
		}	
	}
	
	/**
	 * Destroy session for sure verification
	 */
	public function destroy_session()
	{
		$this->_CI->session->unset_userdata('account_id');
		$this->_CI->session->unset_userdata('account_type_id');
		$this->_CI->session->unset_userdata("user_type_id");
		$this->_CI->session->unset_userdata("psa_id");
		$this->_CI->session->unset_userdata("company_id");
		$this->_CI->session->sess_destroy();
	}
	
	/**
	 * Encrypt password. MD5 Encryption type is used in this system
	 * @param string $password
	 */
	public function encrypt_password($password)
	{
		return md5($password.$this->_CI->config->item('encryption_key'));
	}
	
}

/* End of file Authentication.php */
/* Location: ./application/libraries/Authentication.php */