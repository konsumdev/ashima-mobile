<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
*	Helper : Activity logs helper 
*	Author : Christopher Cuizon <christophercuizons@gmail.com>
*	Usage  : For activity logs
*/
	function ibody(){
		$CI =& get_instance();
		$url_responsive = array(
			'approval/overtime',
			'approval/time_in'
		);	
		$first[] = $CI->uri->segment(1)."/overtime";
		$first[] = $CI->uri->segment(1)."/time_in";
		$body_id = '';
		foreach($first as $fk){
			if(in_array($fk,$url_responsive)){
				$body_id ='id="bodyRes"';
			}		
		}
		return $body_id;
	}	

	/**
	 * ADD meta
	 * Enter description here ...
	 */
	function imeta(){
		$CI =& get_instance();
		$meta = '<meta name="viewport" content="width=1396">';
		$url_responsive = array(
			'approval/overtime',
			'approval/time_in'
		);	
		
		$first[] = $CI->uri->segment(1)."/overtime";
		$first[] = $CI->uri->segment(1)."/time_in";
		foreach($first as $fk){
			if(in_array($fk,$url_responsive)){
				$meta = '<meta name="viewport" content="width=device-width, initial-scale=1">';
			}		
		}
		return $meta;
	}
	
	/**
	*	Adds activity logs for all actions 
	*	@param string $name
	*	@param int $company_id
	*	@return integer
	*/
	function add_activity($name,$company_id,$des,$module_name){
		$CI =& get_instance();
		$fields = array(
					"name" 	=> $CI->db->escape_str($name),
					"date"	=> idates_now(),
					"company_id"=> $CI->db->escape_str($company_id),
					"account_id" => $CI->session->userdata("account_id"),
					"description" => $CI->db->escape_str($des),
					"module_name" => $CI->db->escape_str($module_name)
			);
		$CI->db->insert("activity_logs",$fields);	
		return $CI->db->insert_id();
	}
		

	/**
	*	Displays Only Ymd
	*	@param dates  date
	*	@return string
	*/
	function idates($str) {
		//$dates = date("F d, Y",strtotime($date));
		//return $dates;
		return ($str == "0000-00-00" || $str == "01/01/1970" || $str =="1970-01-01" || $str == "") ? null : date("d-M-y",strtotime($str));
		//return ($str == "0000-00-00" || $str == "01/01/1970" || $str =="1970-01-01" || $str == "") ? null : date("F d, Y",strtotime($str));
	}
	
	function idates_noyr($str) {
	    return ($str == "0000-00-00" || $str == "01/01/1970" || $str =="1970-01-01" || $str == "") ? null : date("d-M",strtotime($str));
	}
	
	function idates_filter($str){
		return ($str == "0000-00-00" || $str == "01/01/1970" || $str =="1970-01-01" || $str == "") ? null : date("F d, Y",strtotime($str));
	}
	
	/**
	 * Checks the dates and time
	 * @param date $date
	 * @return dates
	 */
	function idates_time($date) {
		$dates = date("Y-m-d H:i:s",strtotime($date));
		return $dates;
	}
	
	function idate_dmy($str){
		return ($str == "0000-00-00" || $str == "01/01/1970" || $str =="1970-01-01" || $str == "") ? null : date("m/d/Y",strtotime($str));
	}
	
	/**
	 * defines the time only to example 9:59:02 pm
	 * @param time $time
	 * @return dates
	 */
	function time_only($time){
		return date("H:i:s",strtotime($time));
	}
	
	/**
	 * Checks dates now
	 * @return dates
	 */
	function idates_now() {
		return date("Y-m-d H:i:s");
	}
	
	/**
	 * checks the date on Y-m-d format only
	 * @param date $str
	 * @return dates
	 */
	function idates_only($str){
		return date("Y-m-d",strtotime($str));
	}
	
	/**
	 * DISPLAYS DATES IN SLASH FORMAT
	 * activates dates slash
	 * @param string $str
	 * @return dates
	 */
	function idates_slash($str){
	#	return ($str == "0000-00-00" || $str == "01/01/1970" || $str =="1970-01-01") ? null : date("d/m/Y",strtotime($str));
	return ($str == "0000-00-00" || $str == "01/01/1970" || $str =="1970-01-01") ? null : date("Y-m-d",strtotime($str));
	}
	
	/**
	 * CLEANS THE DATE WHEN IT THE FORMAT IS SLASH THEN THIS 
	 * FUNCTIONS WILL AUTOMATICALLY CONVERTS  SLASH TO DASHSES
	 * @param string $str
	 * @param string $type (@example date,date-time)
	 * @return if date then @format will be Y-m-d else date-time then Y-m-d H:i:s 
	 */
	function date_clean($str,$type = "date"){
		if($str  == "0000-00-00" || $str == "01/01/1970" || $str =="1970-01-01"){
			return null;			
		}else{
			switch($type):
				case "date":
					//$str = date("d/m/Y",strtotime(str_replace("/","-",$str)));
					return  date("Y-m-d",strtotime($str));
				break;
				case "date_time":
				//	$str =  str_replace("/","-",$str);
					return idates_time($str);
				break;
				case "date_only":	
					$str = date("Y-m-d",strtotime($str));
					if($str  == "0000-00-00" || $str == "01/01/1970" || $str =="1970-01-01"){
						return '';
					}else{
						return date("m/d/Y",strtotime($str));
					}
				break;
			endswitch;	
		}
	}
	
	/**
	 * This is the shortcut for the pre functionalities because so taas hehe
	 * @param object $array
	 * @example p(array("b","c","d"));
	 * @return object
	 */
	function p($array) {
		echo "<pre>";
		print_r($array);
		echo "</pre>";	
	}
	
	/**
	*	init pagination
	*	@param string uri
	*	@param int $total_rowss
	*	@param int $per_page
	*	@param int $segment
	*	@return object
	*/
	function init_pagination($uri,$total_rows,$per_page=10,$segment=4){
       	$ci                          =& get_instance();
       	$config['per_page']          = $per_page;
       	$config['uri_segment']       = $segment;
       	$config['base_url']          = base_url().$uri;
       	$config['total_rows']        = $total_rows;
       	$config['use_page_numbers']  = TRUE;
       	$config['prev_link'] = 'Previous';
		$config['next_link'] = 'Next';	    
	    $config['full_tag_open'] = '<ul id="pagination">';
		$config['full_tag_close'] = '</ul>';
		$config['first_tag_open'] = '<li>';
		$config['first_tag_close'] = '</li>';
		$config['last_tag_open'] = '<li>';
		$config['last_tag_close'] = '</li>';
		$config['prev_tag_open'] = '<li class="prev">';
		$config['prev_tag_close'] = '</li>';
		$config['next_tag_open'] = '<li class="next">';
		$config['next_tag_close'] = '</li>';
		$config['cur_tag_open'] = '<li class="active"><a class="btn">';
		$config['cur_tag_close'] = '</a></li>';
		$config['num_tag_open'] = '<li>';
		$config['num_tag_close'] = '</li>';
       	$ci->pagination->initialize($config);
       return $config;
   }
   
   
   /**
	*	init pagination
	*	@param string uri
	*	@param int $total_rowss
	*	@param int $per_page
	*	@param int $segment
	*	@return object
	*/
	function init_pagination2($uri,$total_rows,$per_page=10,$segment=4){
       	$ci                          =& get_instance();
       	$config['per_page']          = $per_page;
       	$config['uri_segment']       = $segment;
       	$config['base_url']          = base_url().$uri;
       	$config['total_rows']        = $total_rows;
       	$config['use_page_numbers']  = TRUE;
       	$config['prev_link'] = '';
		$config['next_link'] = '';	    
		$config['last_link'] = '';
		$config['first_link'] = '';
	    $config['full_tag_open'] = '<ul id="pagination">';
		$config['full_tag_close'] = '</ul>';
		$config['first_tag_open'] = '<li><div class="pagin icon-ashima-arrow-left-first">';
		$config['first_tag_close'] = '</div></li>';
		$config['last_tag_open'] = '<li><div class="pagin icon-ashima-arrow-left-last">';
		$config['last_tag_close'] = '</div></li>';
		$config['prev_tag_open'] = '<li class="pagin-prev"><div class="pagin icon-ashima-arrow-left">';
		$config['prev_tag_close'] = '</div></li>';
		$config['next_tag_open'] = '<li class="pagin-next"><div class="pagin icon-ashima-arrow-right">';
		$config['next_tag_close'] = '</div></li>';
		$config['cur_tag_open'] = '<li class="active"><a>';
		$config['cur_tag_close'] = '</a></li>';
		$config['num_tag_open'] = '<li>';
		$config['num_tag_close'] = '</li>';
       	$ci->pagination->initialize($config);
       return $config;
   }
   
   
   /**
	*	init pagination for multiple
	*	@param string uri
	*	@param int $total_rowss
	*	@param int $per_page
	*	@param int $segment
	*	@return object
	*/
	function init_pagination3($uri,$total_rows,$per_page=10,$segment=4){
       	$ci                          =& get_instance();
       	$config2['per_page']          = $per_page;
       	$config2['uri_segment']       = $segment;
       	$config2['base_url']          = base_url().$uri;
       	$config2['total_rows']        = $total_rows;
       	$config2['use_page_numbers']  = TRUE;
       	$config2['prev_link'] = '';
		$config2['next_link'] = '';	    
		$config2['last_link'] = '';
		$config2['first_link'] = '';
	    $config2['full_tag_open'] = '<ul id="pagination">';
		$config2['full_tag_close'] = '</ul>';
		$config2['first_tag_open'] = '<li><div class="pagin icon-ashima-arrow-left-first">';
		$config2['first_tag_close'] = '</div></li>';
		$config2['last_tag_open'] = '<li><div class="pagin icon-ashima-arrow-left-last">';
		$config2['last_tag_close'] = '</div></li>';
		$config2['prev_tag_open'] = '<li class="pagin-prev"><div class="pagin icon-ashima-arrow-left">';
		$config2['prev_tag_close'] = '</div></li>';
		$config2['next_tag_open'] = '<li class="pagin-next"><div class="pagin icon-ashima-arrow-right">';
		$config2['next_tag_close'] = '</div></li>';
		$config2['cur_tag_open'] = '<li class="active"><a>';
		$config2['cur_tag_close'] = '</a></li>';
		$config2['num_tag_open'] = '<li>';
		$config2['num_tag_close'] = '</li>';
       	$ci->pagination->initialize($config2);
       return $config2;
   }

   /**
    * Idates Convert
    * @param unknown_type $str
    */
	function idate_convert($str){
		$lastdate = strtotime(date("m/d/Y",strtotime($str)));
		return date("Y-m-d",$lastdate);
	}

	function idate_new($str){
		$lastdate = strtotime(date("m/d/Y",strtotime($str)));
		return date("m/d/Y",$lastdate);
	}
   
	function idate_convert_full($str) {
		return date("Y-m-d H:i:s",strtotime(date("m/d/Y H:i:s",strtotime($str))));
	}
	
	function imodule_account(){
		$CI =& get_instance();
		$where = array(
			"account_id" => $CI->session->userdata("account_id"),
			"deleted"=>"0"
		);
		$CI->edb->where($where);
		$query = $CI->edb->get("accounts");
		$row = $query->row();
		$query->free_result();
		if($row){
			
			$profile_row = false;
			switch($row->user_type_id){
				case "1": #ADMIN
					$where_admin = array(
						"a.account_id"=> $CI->session->userdata("account_id"),
						"ka.status"=>"Active",
						"a.deleted"=>"0",
						"a.user_type_id"=>"1"
					);
					$CI->edb->where($where_admin);
				
					$CI->edb->join("accounts AS a","a.account_id = ka.account_id","INNER");
					$query_admin = $CI->db->get("konsum_admin AS ka");
					$profile_row = $query_admin->row();
					$query_admin->free_result();
				break;
				case "2": #owner
					$where_owner = array(
						"a.account_id"=> $CI->session->userdata("account_id"),
						"a.deleted"=>"0",
						"a.user_type_id"=>"2"
					);
					$CI->edb->where($where_owner);
					$select = array("a.account_id","co.first_name","co.last_name","co.middle_name","a.email","a.user_type_id","co.owner_name");
					$CI->edb->select($select);
					$CI->edb->join("accounts AS a","a.account_id = co.account_id","INNER");
					$query_owner = $CI->edb->get("company_owner AS co");
					$profile_row = $query_owner->row();
					$query_owner->free_result();
				break;
				case "3": #hr
					$where_hr = array(
						"a.account_id"=> $CI->session->userdata("account_id"),
						"e.status"=>"Active",
						"a.deleted"=>"0",
						"a.user_type_id"=>"3"
					);
					$CI->edb->where($where_hr);
				
					$CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
					$CI->edb->select("a.account_id,e.first_name,e.last_name,a.email,e.emp_id,a.user_type_id");
					$query_hr = $CI->edb->get("employee AS e");
					$profile_row = $query_hr->row();
					$query_hr->free_result();
				break;
				case "4": #accountant
					$where_accnt = array(
						"a.account_id"=> $CI->session->userdata("account_id"),
						"e.status"=>"Active",
						"a.deleted"=>"0",
						"a.user_type_id"=>"4"
					);
					$CI->edb->where($where_accnt);
					$CI->edb->select("a.account_id,e.first_name,e.last_name,a.email,e.emp_id,a.user_type_id");
					$CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
					$query_accnt = $CI->edb->get("employee AS e");
					$profile_row = $query_accnt->row();
					$query_accnt->free_result();
				break;
				case "5": #employee
					$where_employee = array(
						"a.account_id"=> $CI->session->userdata("account_id"),
						"e.status"=>"Active",
						"a.deleted"=>"0",
						"a.user_type_id"=>"5"
					);
					$CI->edb->where($where_employee);
					$CI->edb->select("a.account_id,e.first_name,e.last_name,a.email,e.emp_id,a.user_type_id");
					$CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
					$query_employee = $CI->edb->get("employee AS e");
					$profile_row = $query_employee->row();
					$query_employee	->free_result();
				break;
			}		
			return $profile_row;
		}else{
			return false;
		}		
	}
	
	/**
	 * CHECK THE EMPLOYEES INFORMATIONS
	 * THESE WILL US CHECK THE EMPLOYEES INFORMATION
	 * @param unknown_type $emp_id
	 */
	function imodule_employee($emp_id,$company_id) {
		$CI =& get_instance();
		if(is_numeric($emp_id)){
			$where_employee = array(
				"e.emp_id"=> $emp_id,
				"e.status"=>"Active",
				"a.deleted"=>"0",
				"a.user_type_id"=>"5",
				"e.company_id"=>$company_id
			);
			$CI->edb->where($where_employee);
			$CI->edb->select("a.account_id,e.first_name,e.last_name,a.email,e.emp_id,a.user_type_id");
			$CI->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$query_employee = $CI->edb->get("employee AS e");
			$profile_row = $query_employee->row();
			$query_employee	->free_result();
			return $profile_row;
		}else{
			return false;
		}
	}
	
	/**
	 * GET THE USERS FULL NAME
	 * THIS WILL TRIGGER THE NAME OF THE LOGIN CREDENTIALS 
	 * @param int $company_id
	 * @return string fullname
	 */
	function imodule_fullname($company_id){
		$log_user = imodule_account();
		$fullname = "";
		if($log_user){
			if($log_user->user_type_id == 2){
				if($log_user->first_name !="" || trim($log_user->first_name) !=""){
					$fullname = $log_user->last_name.", ".$log_user->first_name;
				}else{
					$fullname = $log_user->owner_name !="" ? $log_user->owner_name : "Owner";
				}
			}else{
				$fullname = $log_user->last_name.", ".$log_user->first_name;
			}
		}
		return $fullname;
	}
	
	/**
	 * ESCAPE THE DATA IF NAKA KITA OG SINGLE QUOTE OR DOUBLE QUOTE
	 * 
	 * @param string $str
	 * @return string
	 */
	function iclean($str){
		return $str ? preg_replace("/[^a-zA-Z0-9]+/", " ", html_entity_decode($str)) : "";
	}
	
	function get_employee_name_for_activity($emp_id){
		$CI =& get_instance();
		$select = array(
				'first_name',
				'last_name'
			);
		$CI->edb->select($select);
		$CI->db->where('emp_id',$emp_id);
		$query = $CI->edb->get("employee");
		return $query->row();
	} 
	function get_data_activity($table, $where, $select){
		$CI =& get_instance();
		$CI->edb->select($select);
		$CI->db->where($where);
		$query = $CI->edb->get($table);
		return $query->row();
	}
	function iactivity_logs($company_id,$str,$module_name=""){
		$CI =& get_instance();
		# ACTIVITY LOGS	
		if($str){
			$fullname = imodule_fullname($company_id);
			$lang_add = sprintf(lang("global_logs"),$fullname);
			add_activity($lang_add,$company_id,$str,$module_name);
			
			//subscribers activity logs
			
			$fields = array(
					"name" 	=> $CI->db->escape_str($fullname),
					"date"	=> idates_now(),
					"time"	=> idates_now(),
					"company_id"=> $CI->db->escape_str($company_id),
					"account_id" => $CI->session->userdata("account_id"),
					"description" => $CI->db->escape_str($str),
					"module_name" => $CI->db->escape_str($module_name),
					"payroll_system_account_id" => $CI->session->userdata("psa_id")
			);
			$CI->db->insert("subscribers_activity_logs",$fields);
		}
		# END ACTIVITY LOGS		
	}
	
	function idate_mdy($str){
		$lastdate = strtotime(date("m/d/Y",strtotime($str)));
		return date("Y-m-d",$lastdate);
	}
	
	/** user logs **/
	/**
	 * SETS USER LOGS FOR HR OR OWNER
	 * Enter description here ...
	 * @param int $account_id
	 * @param string $msg
	 * @param int $company_id
	 * @param int $psa_id
	 */
	function iuser_logs($account_id,$msg,$company_id,$psa_id){
		$CI =& get_instance();
		if($psa_id ==""){
			$psa_id = $CI->session->userdata('psa_id');
		}
		if($account_id ==""){
			$account_id = $CI->session->userdata('account_id');
		}
		if(is_numeric($company_id)){
			$insert_field = array(
					"payroll_system_account_id" => $psa_id,
					"account_id" => $account_id,
					"company_id"=>$company_id,
					"description" => $CI->db->escape_str($msg),
					"date"	=> date("Y-m-d H:i:s"),
					"status" => "Active"
			);
			$CI->edb->insert("user_activity_logs",$insert_field);
		}
	}
	
    /** end user logs **/
    function add_workflow_approval_default_group($company_id, $creator_acc_id)
    {
        $_CI =& get_instance();
            
        # list of all approval processes
        $approval_processes_names = array(
            'Leave',
            'Overtime',
            'Add Timesheet',
            'Timesheet Adjustment',
            'Payroll',
            'Shifts',
            'Mobile Clock-in',
            'Termination',
            'Document',
            'End of Year'
        );
        // GET SAVED APPROVAL PROCESSES
        $get_saved_apn = get_table_column('approval_process', 'name', array('company_id'=>$company_id));
        $saved_apn = array();
        if ($get_saved_apn)
        {
            foreach ($get_saved_apn as $saved_apn_r) 
            {
                array_push($saved_apn, $saved_apn_r->name);
            }
        }
        
        // Checker if na save naba default approval processes
        $has_complete_default_app_procs = array_intersect($approval_processes_names, $saved_apn) == $approval_processes_names;

        // If complete na ang default approval processes, proceed
        // else, add the lacking approval processes
        if ( ! $has_complete_default_app_procs )
            {
                foreach ($approval_processes_names as $apn_def_r) {
                if (!in_array($apn_def_r, $saved_apn))
                {
                    $save_field = array(
                    "name" => $apn_def_r,
                    "company_id" => $company_id
                );
                $_CI->db->insert("approval_process",$save_field);
                }
            }
            }

            // At this point naa na mga default approval process
            // Next, we'll check if naa na default approval groups
            $get_agvg_w = array(
                'ag.company_id' => $company_id,
                'ag.emp_id' => '-99'.trim($company_id)
            );
            $get_agvg_s = array(
                'ag.approval_group_id',
                'ag.approval_process_id',
                'ag.emp_id',
                'ag.approval_groups_via_groups_id',
                'ap.name',
                'avg.name AS agvg_name'
            );
            $_CI->db->select($get_agvg_s);
            $_CI->db->where($get_agvg_w);
            $_CI->db->where_in('ap.name', $approval_processes_names);
            $_CI->db->join('approval_process AS ap', 'ap.approval_process_id = ag.approval_process_id', 'INNER');
            $_CI->db->join('approval_groups_via_groups AS avg', 'avg.approval_groups_via_groups_id = ag.approval_groups_via_groups_id', 'INNER');
            $_CI->db->group_by('ag.approval_process_id');
            $get_agvg_q = $_CI->db->get('approval_groups AS ag');
            $get_agvg_r = $get_agvg_q->result();
            $get_agvg_q->free_result();
            $get_agvg = array();

            if ($get_agvg_r)
            {
                foreach ($get_agvg_r as $get_agvg_row) 
                {
                    array_push($get_agvg, $get_agvg_row->approval_process_id);
                }
            }

            $all_def_process_w = array(
            'company_id' => $company_id
        );
        $all_def_process_s = array(
            'approval_process.approval_process_id',
                'approval_process.name',
                'approval_process.company_id'
            );
            $_CI->db->select($all_def_process_s);
        $_CI->db->where($all_def_process_w);
        $_CI->db->where_in('name', $approval_processes_names);
        $qr = $_CI->db->get('approval_process');
        $all_def_process = $qr->result();
        $qr->free_result();

        // Checkpoint, if equal pasabot complete na ang defaults nga na save so return
        // else, add the default approval groups
        if (count($all_def_process) == count($get_agvg))
        {
            return FALSE;
        }

        // If kaabot ari na part, meaning naay mga defaults nga wala pa ma save, so insert11
        // If naay bag o approval process ma add and naay existing, igo nalang e insert ang bag-o
        if ($all_def_process)
        {
            foreach ($all_def_process as $row) 
            {
                if (! in_array($row->approval_process_id, $get_agvg))
                {
                    # add approval_groups_via_groups
                $field_agvg = array(
                'company_id' 								=> $company_id,
                'approval_levels' 					=> '1',
                'status' 										=> 'Active',
                'created_by_account_id' 		=> $creator_acc_id,
                'created_date'							=> date("Y-m-d H:i:s"),
                'enable_due_date' 					=> 'no',
                'escalation_path' 					=> 'no',
                'email_notification' 				=> 'yes',
                'sms_notification' 					=> 'no',
                'twitter_notification' 			=> 'no',
                'message_board_notification'=> 'yes',
                'notify_staff' 							=> 'no',
                'notify_payroll_admin' 			=> 'no',
                'enable_advance_settings' 	=> 'no'
                );
                
                if ($row->name == 'Leave') {
                    $field_agvg['name'] = 'Leave Approvers';
                    $field_agvg['description'] = 'All Leave requests will be routed and approved by the assigned approvers.';

                } elseif ($row->name == 'Overtime') {
                    $field_agvg['name'] = 'Overtime Approvers';
                    $field_agvg['description'] = 'All Overtime requests will be routed and approved by the assigned approvers.';

                } elseif ($row->name == 'Timesheet Adjustment') {
                    $field_agvg['name'] = 'Timesheet Adjustment Approvers';
                    $field_agvg['description'] = 'All Timesheets adjustment will be routed and approved by the assigned approvers.';

                } elseif ($row->name == 'Add Timesheet') {
                    $field_agvg['name'] = 'Timesheet Approvers';
                    $field_agvg['description'] = 'All Timesheets will be routed and approved by the assigned approvers.';

                } elseif ($row->name == 'Mobile Clock-in') {
                    $field_agvg['name'] = 'Mobile Clock In Approvers';
                    $field_agvg['description'] = 'All Mobile Clock in requests will be routed and approved by the assigned approvers.';

                } elseif ($row->name == 'Payroll') {
                    $field_agvg['name'] = 'Payroll Approvers';
                    $field_agvg['description'] = 'All Payroll requests will be routed and approved by the assigned payroll approver.';

                } elseif ($row->name == 'Shifts') {
                    $field_agvg['name'] = 'Shifts Approvers';
                    $field_agvg['description'] = 'All New and Shift Adjustment requests will be routed and approved by the assigned approvers.';

                } elseif ($row->name == 'Termination') {
                        $field_agvg['name'] = 'Termination Approvers';
                    $field_agvg['description'] = 'All Termination requests will be routed and approved by the assigned approvers.';

                } elseif ($row->name == 'Document') {
                        $field_agvg['name'] = 'Document Approvers';
                    $field_agvg['description'] = 'All Document requests will be routed and approved by the assigned approvers.';

                } elseif ($row->name == 'End of Year') {
                        $field_agvg['name'] = 'End of Year Approvers';
                    $field_agvg['description'] = 'All End of Year requests will be routed and approved by the assigned approvers.';
                }
                
                // First save the Approval Groups vis Groups
                esave('approval_groups_via_groups', $field_agvg);
                $agvg_id_2 = $_CI->db->insert_id();
                
                // Save to Approval Groups table
                $field_ag_2 = array(
                'approval_process_id' => $row->approval_process_id,
                'emp_id' 							=> '-99'.trim($company_id),
                'level' 							=> 1,
                'company_id' 					=> $company_id,
                'approval_groups_via_groups_id' => $agvg_id_2
                );
                esave('approval_groups', $field_ag_2);
                }
            }
        }

        return false;
    }
	