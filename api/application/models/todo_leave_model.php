<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Approve LEave model for approving overtime , leaves , loans
 *
 * @category Model
 * @version 1.0
 * @author Filadelfo Jr. Sandalo <filsandalojr@gmail.com>
 */
	class Todo_leave_model extends CI_Model {
		
		/**
		 * CHECKS APPLICATION LEAVE FOR EVERY COMPANY
		 * @param int $company_id
		 * @return object
		 */
		public function leave_list($emp_id, $company_id)
		{
			if ( ! is_numeric($company_id)) {
				return false;
			}

			$where = array(
				"el.company_id" =>$company_id,
				"el.deleted" => '0',
				"ag.emp_id" => $emp_id,
				'epi.employee_status' => 'Active',
			    "el.leave_application_status"=>"pending",
			    "el.flag_payroll_correction" => "no",
			    "al.level !=" => ""
			    
			);

			$select = array(
				#"*",
				#"pg.name AS pg_name",
				"empl.remaining_leave_credits AS remaining_c",
			    "epi.leave_approval_grp",
			    "al.level",
			    "el.employee_leaves_application_id",
			    "el.leave_application_status",
			    "al.approve_by_hr",
			    "el.emp_id",
			    "el.company_id",
			    "el.shift_date",
			    "el.date_filed",
			    "lt.leave_type_id",
			    "el.date_start",
			    "el.date_end",
			    "el.total_leave_requested",
			    "el.leave_cedits_for_untitled",
			    "el.reasons",
			    "el.required_file_documents",
			    "el.for_resend_auto_rejected_id",
			    "el.cancellable",
				"el.previous_credits",
				"lt.leave_type",
				"lt.leave_units",
				"empl.leave_credits"
			);
			
			$select1 = array(
			    "a.payroll_cloud_id",
			    "e.first_name",
				"e.last_name",
				"a.profile_image"
			);
			
			$this->db->select($select);
			$this->edb->select($select1);
			$this->edb->where($where);
			
			$this->edb->join("employee AS e","e.emp_id = el.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			
			$this->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
			$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND al.level = ag.level","LEFT");
			$this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
			$this->edb->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
			$this->edb->join("employee_leaves AS empl","empl.leave_type_id = el.leave_type_id and empl.emp_id = e.emp_id","LEFT");
			$this->db->group_by('el.employee_leaves_application_id');
			
			$this->db->order_by("date_filed","DESC");
			
			$query = $this->edb->get("employee_leaves_application AS el");

			$result = $query->result();
			$query->free_result();
			
			return ($result) ? $result : false;
				
		}
		public function leave_list_count($emp_id, $company_id, $search =""){
			if(is_numeric($company_id)){
			$konsum_key = konsum_key();
			$level = $this->leave_approval_level($emp_id);
			$filter = $this->not_in_search_leave( $emp_id,$company_id, $search);
			$where = array(
				"el.company_id" =>$company_id,
				"el.deleted" => '0',
     			"ag.emp_id" => $emp_id,
				"el.leave_application_status"=>"pending"
			);		
			$where2 = array(
				"al.level >=" => ""
			);
			if($search != "" && $search != "all"){
				$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
			}	
			$this->db->select('count(*) as val');
			$this->db->where($where2);
			$this->edb->where($where);
			if($filter != FALSE){
				$this->db->where("el.employee_leaves_application_id NOT IN ({$filter})");
			}
			$this->edb->join("employee AS e","e.emp_id = el.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    		$this->edb->join("approval_groups AS ag","agg.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
    		$this->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
    		$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
    		$this->db->group_by('el.employee_leaves_application_id');
			$query = $this->edb->get("employee_leaves_application AS el");
			$row = $query->row();
			
			return ($row) ? $query->num_rows : 0;
		}else{
					return false;
				}
		
					
		}
		
		public function not_in_search_leave($emp_id, $company_id, $search ="", $flag_payroll_correction="")
		{
			if(is_numeric($company_id)){
				if($flag_payroll_correction == "current") {
			        $payroll_correction = "no";
			    } elseif ($flag_payroll_correction == "late") {
			        $payroll_correction = "yes";
			    } else {
			        $payroll_correction = "no";
			    }

	            $where = array(
					"el.company_id" =>$company_id,
					"el.deleted" => '0',
					"ag.emp_id" => $emp_id,
					"el.leave_application_status"=>"pending",
				    "al.level >=" => "",
				    "el.flag_payroll_correction" => $payroll_correction
				);
				
				$select = array(
				    "epi.leave_approval_grp",
				    "el.employee_leaves_application_id",
				    "al.level",
				);
				
				$this->db->select($select);
				$this->db->where($where);
				
				$this->db->join("employee_payroll_information AS epi","epi.emp_id = el.emp_id","LEFT");
				$this->db->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
				$this->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
				$this->db->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
				$this->db->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
				$this->db->group_by('el.employee_leaves_application_id');
				$query = $this->db->get("employee_leaves_application AS el");
				$result = $query->result();
				
				$arrs = array();
				if($result){
					$is_assigned = true;
					$workforce_notification = get_workforce_notification_settings($company_id);
					foreach($result as $key => $approvers){

						$leave_approval_grp = $approvers->leave_approval_grp;
						$level = $approvers->level;
						$check = $this->check_assigned_leave($leave_approval_grp, $level);
						if($workforce_notification->option == "choose level notification"){
							$is_assigned = check_if_is_level($level, $workforce_notification->workforce_alerts_notification_id);
						}
						
						if($is_assigned && $check){

						} else {
							array_push($arrs, $approvers->employee_leaves_application_id);
						}
					}
				}
				
				$string = implode(",", $arrs);
				return $string;
			}else{
				return false;
			}					
		}

		public function is_done($leave_appr_grp, $level, $emp = NULL)
		{
	        $where = array(
	            "emp_id" => ($emp != NULL) ? $emp : $this->session->userdata("emp_id"),
	            "level <" => $level,
	            "approval_groups_via_groups_id" => $leave_appr_grp
	        );
	        $this->db->where($where);
	        $query = $this->db->get("approval_groups");
	        $row = $query->row();

	        return ($row) ? true : false;
	    }

		public function get_leave_last_level($emp_id){
			
			$where = array(
				'epi.emp_id' => $emp_id	
			);
			$this->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_groups AS ag","agg.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
			$this->db->order_by("ag.approval_group_id","desc");
			$q = $this->edb->get("employee_payroll_information AS epi",1,0);
			$r = $q->row(); 
			return ($r)? $r->level:FALSE;
		}

		public function checkleave_employee_leaves_application($company_id,$employee_leaves_application_id) {
			if(is_numeric($company_id) && is_numeric($employee_leaves_application_id)) {
				$where = array(
						"company_id"	=> $company_id,
						"employee_leaves_application_id" => $this->db->escape_str($employee_leaves_application_id),
						"status"			=> "Active"
				);
				$this->db->select("employee_leaves_application_id,emp_id,leave_type_id,total_leave_requested,date_start,date_end");
				$query = $this->db->get_where("employee_leaves_application",$where);
				$row = $query->row();
				$query->free_result();
				return $row;
			} else {
				return false;
			}
		}
		
		public function leave_approval_level($emp_id){
			if(is_numeric($emp_id)){
				$this->db->where('ap.name','Leave Approval Group');
				$this->db->where('ag.emp_id',$emp_id);
				$this->db->join('approval_process AS ap','ap.approval_process_id = ag.approval_process_id','LEFT');
				$q = $this->db->get('approval_groups AS ag');
				$r = $q->row();
				
				return ($r) ? $r->level : FALSE;
			}else{
				return false;
			}
		}
		public function check_assigned_leave($leave_appr_grp, $level){
			$where = array(
					"emp_id" => $this->session->userdata("emp_id"),
					"level" => $level,
					"approval_groups_via_groups_id" => $leave_appr_grp
			);
			$this->db->where($where);
			$query = $this->db->get("approval_groups");
			$row = $query->row();
		
			return ($row) ? true : false;
		}

		/** added: fritz - Start **/

		public function leave_list_edit($company_id,$limit,$start,$date_from,$date_to,$emp_id , $count = false,$all=false){
			if(is_numeric($company_id)){
				$konsum_key = konsum_key();
				$start = intval($start);
				$limit = intval($limit);

				$this->db->order_by('ela.date_filed','DESC');

				if($all){
					$this->session->set_userdata('get_leave_id', "");
					$where = array(
						'ela.company_id'				=> $company_id,
						'ela.status'					=> 'Active',
						'ela.emp_id'					=> $emp_id,
						'ela.leave_application_status'	=> 'pending'
					);

					if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
						$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
					}
				}else{

					$where = array(
						'ela.company_id'				=> $company_id,
						'ela.status'   					=> 'Active',
						'ela.leave_application_status'	=> 'pending'
					);
					$filter = $this->session->userdata('get_leave_id');

					if($filter){
						$string = implode(",", $filter);
						$this->db->where("ela.employee_leaves_application_id IN ({$string})");
					}
				}

				$this->edb->where($where);
				$arr = array(
					"*",
					"pg.name AS pg_name",
					"empl.remaining_leave_credits AS remaining_c"
				);
				$this->edb->select($arr);

				$this->edb->join("employee AS e","e.emp_id = ela.emp_id","LEFT");
				$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
				$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
				$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
				$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
				$this->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
				$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
				$this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
				$this->edb->join("approval_leave AS al","al.leave_id = ela.employee_leaves_application_id","LEFT");
				$this->edb->join("leave_type AS lt","lt.leave_type_id = ela.leave_type_id","LEFT");
				$this->edb->join("employee_leaves AS empl","empl.leave_type_id = ela.leave_type_id and empl.emp_id = e.emp_id","LEFT");
				$this->db->group_by('ela.employee_leaves_application_id');

				if($count){
					$query = $this->edb->get('employee_leaves_application AS ela');
					$result = $query->num_rows();
					$query->free_result();
				}else{
					$query = $this->edb->get('employee_leaves_application AS ela',$limit,$start);
					$result = $query->result();
					$query->free_result();

				}
				return $result;
			}else{
				return false;
			}

		}

		public function get_leaves_child($leaves_id) {
			$where = array(
				'leaves_id' => $leaves_id,
				'status'	=> 'Active'
			);

			$this->db->where($where);

			$q = $this->db->get('employee_leaves_application');
			$r = $q->result();

			return ($r) ? $r : false;
		}
		
		public function leave_information($val,$flag_payroll_correction = "current"){	    
		    $select = array(
		        "el.employee_leaves_application_id",
		        "el.emp_id",
		        "el.company_id",
		        "el.emp_id",
		        "el.shift_date",
		        "al.level",
		        "el.work_schedule_id",
		        "el.leave_type_id",
		        "el.reasons",
		        "el.shift_date",
		        "el.date_start",
		        "el.date_end",
		        "el.date_return",
		        "el.date_filed",
		        "el.note",
		        "el.approved_by_head",
		        "el.duration",
		        "el.total_leave_requested",
		        "el.leave_cedits_for_untitled",
		        "el.leave_application_status",
		        "el.attachments",
		        "el.approval_date",
		        "el.leaves_id",
		        "el.flag_parent",
		        "el.credited",
		        "el.non_credited",
		        "el.remaining_credits",
		        "el.timestamp_paid_leave",
		        "el.required_file_documents",
		        "el.cancel_reason",
		        "el.date_cancel",
		        "el.status",
		        "el.deleted",
		        "el.approver_account_id",
		        "el.existing_leave_used_to_date",
		        "el.previous_credits",
		        "lt.leave_type",
		        "el.for_resend_auto_rejected_id",
		        "el.exclude_lunch_break",
		        "el.leave_request_type",
		        "el.no_of_hours"
		    );
		    
		    $select1 = array(
		        "e.last_name",
		        "e.first_name"
		    );
		    
		    if($flag_payroll_correction == "late") {
		        $w = array(
		            "el.employee_leaves_application_id" => $val
		        );
		    } else {
		        $w = array(
		            "el.employee_leaves_application_id" => $val,
		            "el.status" => "Active"
		        );
		    }
		    
		    $this->db->select($select);
		    $this->edb->select($select1);
		    $this->db->where($w);
		    $this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
		    $this->db->join("approval_leave AS al","el.employee_leaves_application_id = al.leave_id","LEFT");
		    $this->edb->join("employee AS e","e.emp_id = el.emp_id","LEFT");
		    $q = $this->edb->get("employee_leaves_application AS el");
		    $r = $q->row();
		    
		    return ($r) ? $r : FALSE ;
		}

		/** added: fritz - End **/

		/*added preitst for current and late*/
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

		public function get_payroll_admin_hr($psa_id){
			$select = array(
				"a.account_id",
				"e.emp_id"
			);
			$eselect = array(
				"e.first_name",
				"e.last_name",
				"a.email"
			);
			$where = array(
				"a.payroll_system_account_id" => $psa_id,
				"a.enable_generic_privilege" => "Active",
				"a.user_type_id" => "3"
			);
			$this->db->select($select);
			$this->edb->select($eselect);
			$this->db->where($where);
			$this->edb->join("employee AS e","e.account_id = a.account_id","INNER");
			$query = $this->edb->get("accounts AS a");
			$result = $query->result();

			return ($result) ? $result : false;
		}
		/*end of preist added functions*/
		
		public function get_all_leave_type($comp_id){
		    $res = array();
		    $date = date("Y-m-d");	    
		    
		    $w = array(
		        "company_id"=>$comp_id,
		    );
		    
		    $this->db->where($w);
		    $q = $this->db->get("leave_type");
		    $row = $q->result();
		    
		    if($row) {
		        foreach ($row AS $r) {
		            $wd 	= array(
		                "leave_type_id" => $r->leave_type_id,
		                "leave_type" => $r->leave_type,
		                "custom_search" => "leave_type_id-{$r->leave_type_id}",
		            );
		            array_push($res,$wd);
		        }
		    }
		    return $res;
		}
		
		public function get_leave_apps_child($emp_id,$company_id,$employee_leave_application_id) {
		    $w = array(
		        "emp_id" => $emp_id,
		        "company_id" => $company_id,
		        "leaves_id" => $employee_leave_application_id,
		        "status" => "Active"
		    );
		    
		    $this->db->where($w);
		    $q = $this->db->get("employee_leaves_application");
		    $result = $q->result();
		    
		    return ($result) ? $result : false;
		}
	}
	
	
	