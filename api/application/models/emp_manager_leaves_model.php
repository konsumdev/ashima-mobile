<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Employee leaves Model 
 *
 * @category Controller
 * @version 1.0
 * @author John fritz Marquez
 */
    class emp_manager_leaves_model extends CI_Model {
        
        public function __construct() {
            parent::__construct();
        }

        public function get_employee_leave_list($emp_id, $comp_id, $page="", $limit="", $num_rows=false)
        {
            $s = array(
                'accounts.account_id','employee.first_name','employee.last_name','employee.middle_name','accounts.payroll_cloud_id',
                "accounts.profile_image",
                "employee_leaves.leaves_id",
                "employee_leaves.emp_id",
                "employee_leaves.rank_id",
                "employee_leaves.leave_type_id",
                "employee_leaves.leave_credits",
                "employee_leaves.remaining_leave_credits",
                "employee_leaves.previous_leave_credits",
                "employee_leaves.as_of",
                "employee_leaves.company_id",
                "employee_leaves.leave_accrue",
                "employee_leaves.years_of_service",
                "employee_leaves.created_date",
                "employee_leaves.updated_date",
                "employee_leaves.created_by_account_id",
                "employee_leaves.updated_by_account_id",
                "employee_leaves.status",
                "employee_leaves.paid_leave",
                "employee_leaves.what_happen_to_unused_leave",
                "employee_leaves.provide_half_day_option",
                "employee_leaves.allow_to_apply_leaves_beyond_limit",
                "employee_leaves.exclude_holidays",
                "employee_leaves.exclude_weekends",
                "employee_leaves.consecutive_days_after_weekend_holiday",
                "employee_leaves.num_consecutive_days_after_weekend_holiday",
                "employee_leaves.days_before_leave_application",
                "employee_leaves.num_days_before_leave_application",
                "employee_leaves.consecutive_days_allowed",
                "employee_leaves.num_consecutive_days_allowed",
                "employee_leaves.as_of_date_created",
                "employee_leaves.effective_date",
                "employee_leaves.earned_leaves",
                "employee_leaves.earned_leaves_enrollment_date",
                "employee_leaves.carried_forward_leaves",
                "employee_leaves.adjustment_leave",
                "employee_leaves.adjustments_description",
                "lt.leave_type AS leave_type","lt.required_documents AS required_documents",
                "r.rank_name","p.name AS payroll_group_name",
                "lt.accrual_period AS accrual_period",
                #   "employee_leaves.start_of_accrual",
                #   "employee_leaves.start_of_accrual_day",
                "lt.start_of_accrual", #added ni kuha lang ta data sa leave setting snot sa employee
                "lt.start_of_accrual_day", #added ni kuha lang ta data sa leave setting snot sa employee
                "ep.date_hired", #added ni kuha lang ta data sa leave setting snot sa employee
                "lt.effective_start_date_by", #added ni kuha lang ta data sa leave setting snot sa employee
                "lt.effective_start_date", #added ni kuha lang ta data sa leave setting snot sa employee
                "lt.required_documents", #added ni kuha lang ta data sa leave setting snot sa employee
                "lt.start_of_accrual",
                "lt.start_of_accrual_day",
                "lt.accrual_period",
                "lt.paid_leave",
                'lt.leave_units',
                'lt.accrual_schedule',
                'lt.start_of_accrual_month',
                'employee_leaves.existing_leave_used_to_date', // mao ni ang for used_leaves sauna na
                "employee_leaves.used_leaves_enrollment_date", // date for used leave when its enrolled
                "employee_leaves.carry_forward_expiry"
            );
            $this->db->_protect_identifiers=false;
            $this->edb->select($s);
            $select2 = array('lt.created_date AS lt_created_date','lt.carry_forward_expiry AS lt_carry_forward_expiry');
            $this->db->select($select2);
            $where = array(
                'employee_leaves.company_id' => $comp_id,
                'employee.status'            => 'Active',
                'accounts.user_type_id' => '5',
                'edrt.parent_emp_id'            => $emp_id
            );
           
            $where["ep.employee_status"] = "Active";
            
            $this->edb->where($where);
            $this->db->where('employee_leaves.status','Active');
            $this->edb->join('leave_type AS lt','lt.leave_type_id = employee_leaves.leave_type_id','INNER');
            $this->edb->join('employee','employee.emp_id = employee_leaves.emp_id','INNER');
            $this->edb->join('accounts','accounts.account_id = employee.account_id','INNER');
            $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = employee_leaves.emp_id","LEFT");
            $this->edb->join("employee_payroll_information AS ep","ep.emp_id = employee.emp_id","INNER");
            $this->edb->join("payroll_group AS p","p.payroll_group_id = ep.payroll_group_id","LEFT");
            $this->edb->join('rank AS r','r.rank_id = ep.rank_id','INNER');
            $this->db->group_by('employee_leaves.leaves_id');
            $konsum_key = konsum_key();

            $this->db->order_by('AES_DECRYPT(employee.last_name, "'.$konsum_key.'")', 'asc');

            if($num_rows == true){
                $sql = $this->edb->get('employee_leaves');
            }else{
                $sql = $this->edb->get('employee_leaves',$limit,$page);
            }
            if($sql->num_rows() > 0){
                if($num_rows == true){
                    return $sql->num_rows();
                }
                $results = $sql->result();
                $sql->free_result();

                if ($results) {
                    $arz = array();
                    $year = date("Y");
                    $leaves_used2date = $this->get_leave_used_to_date_all($year);
                    // p($leaves_used2date);
                    foreach ($results as $row) {
                        // $use_leaves = in_array_custom($row->emp_id."_".$row->leave_type_id, $leaves_used2date);
                        $use_leaves = $this->get_leave_used_to_date($row->emp_id,$row->leave_type_id,$year);
                        // p($use_leaves);
                        $use_leaves = ($use_leaves) ? floatval($use_leaves) : 0;
                        $ul = ($row->existing_leave_used_to_date) ? floatval($row->existing_leave_used_to_date) : 0;
                        $t = array(
                            'account_id' => $row->account_id,
                            'first_name' => $row->first_name,
                            'last_name' => $row->last_name,
                            'middle_name' => $row->middle_name,
                            'payroll_cloud_id' => $row->payroll_cloud_id,
                            "leaves_id" => $row->leaves_id,
                            "emp_id" => $row->emp_id,
                            "leave_type_id" => $row->leave_type_id,
                            "leave_credits" => $row->leave_credits,
                            "remaining_leave_credits" => $row->remaining_leave_credits,
                            "previous_leave_credits" => $row->previous_leave_credits,
                            "company_id" => $row->company_id,
                            "leave_type" => $row->leave_type,
                            'leave_units' => $row->leave_units,
                            'used_leaves' => $use_leaves + $ul,
                            'leave_type_id' => $row->leave_type_id,
                            'profile_image' => $row->profile_image,
                            'base_url' => base_url(),
                            'paid_leave' => $row->paid_leave,
                            'required_documents' => $row->required_documents,
                            'num_days_before_leave_application' => $row->num_days_before_leave_application,
                        );
                        array_push($arz, $t);
                    }
                    return $arz;
                }

                return false;
            }else{
                return FALSE;
            }
        }

        /**
         * GET LEAVE USED TO DATE
         * @param unknown $emp_id
         * @param unknown $leave_type_id
         */
        public function get_leave_used_to_date_all($year){
            if($year){
                $where_elh = array(
                    'company_id'    => $this->company_id,
                    // 'emp_id'        => $emp_id,
                    // "leave_type_id" => $leave_type_id,
                    'type'          => 'Conversion Resetted'
                );
                $this->db->where($where_elh);
                $this->db->order_by("date","desc");
                $leave_history_accrueded = $this->db->get("employee_leave_history",1);
                $elh_row = $leave_history_accrueded->row();
            
                $leave_history_accrueded->free_result();
                
                $date_betweens = "";
                $where = array(
                        'company_id'    =>$this->company_id,
                        // 'emp_id'        =>$emp_id,
                        // "leave_type_id" =>$leave_type_id,
                        "leave_application_status"  =>'approve',
                        "year(date_start)"=>$year
                );
                if($elh_row){
                    if($elh_row->type == 'Conversion Resetted') {
                        $where = array(
                            'company_id'    =>$this->company_id,
                            // 'emp_id'        =>$emp_id,
                            // "leave_type_id" =>$leave_type_id,
                            "leave_application_status"  =>'approve',
                            "date_start >=" =>date("Y-m-d",strtotime($elh_row->when_date))
                        );
                    }else{
                        
                    }
                }
                
                $this->db->select('sum(credited) as sum_credited');
                $this->db->where($where);
                $this->db->where("(flag_parent = 'yes' OR flag_parent = 'no')");
                $q_leave = $this->edb->get('employee_leaves_application');
                $q_row = $q_leave->result();
                $q_num_row = $q_leave->num_rows();
                $num_credited = 0;
                
                if ($q_row) {
                    $rt = array();
                    foreach ($q_row as $row) {
                        if($row->sum_credited > 0){
                            $num_credited = number_format($row->sum_credited,3,".","");
                        }
                        $t = array(
                            'emp_id' => $row->emp_id,
                            'leave_type_id' => $row->leave_type_id,
                            'num_credited' => $num_credited,
                            'q' => $row->emp_id."_".$row->leave_type_id
                        );
                        array_push($rt, $t);
                    }
                    return $rt;
                }

                return false;
            }else{
                return false;
            }
        }
        
        /**
         * Employee Leave Information
         * @param unknown_type $comp_id
         */
        public function emp_leave($sort_by,$employee_id_name="",$limit, $start, $comp_id, $emp_id, $display_number = false,$order_by="")
        {
            $s = array(
                    'accounts.account_id','employee.first_name','employee.last_name','employee.middle_name','accounts.payroll_cloud_id',    
                    "employee_leaves.leaves_id",
                    "employee_leaves.emp_id",
                    "employee_leaves.rank_id",
                    "employee_leaves.leave_type_id",
                    "employee_leaves.leave_credits",
                    "employee_leaves.remaining_leave_credits",
                    "employee_leaves.previous_leave_credits",
                    "employee_leaves.as_of",
                    "employee_leaves.company_id",
                    "employee_leaves.leave_accrue",
                    "employee_leaves.years_of_service",
                    "employee_leaves.created_date",
                    "employee_leaves.updated_date",
                    "employee_leaves.created_by_account_id",
                    "employee_leaves.updated_by_account_id",
                    "employee_leaves.`status`",
                    "employee_leaves.paid_leave",
                    "employee_leaves.what_happen_to_unused_leave",
                    "employee_leaves.provide_half_day_option",
                    "employee_leaves.allow_to_apply_leaves_beyond_limit",
                    "employee_leaves.exclude_holidays",
                    "employee_leaves.exclude_weekends",
                    "employee_leaves.consecutive_days_after_weekend_holiday",
                    "employee_leaves.num_consecutive_days_after_weekend_holiday",
                    "employee_leaves.days_before_leave_application",
                    "employee_leaves.num_days_before_leave_application",
                    "employee_leaves.consecutive_days_allowed",
                    "employee_leaves.num_consecutive_days_allowed",
                    "employee_leaves.as_of_date_created",
                    "employee_leaves.effective_date",
                    "lt.leave_type AS leave_type","lt.required_documents AS required_documents",
                    "r.rank_name","p.name AS payroll_group_name",
                    "lt.accrual_period AS accrual_period",
                    "lt.start_of_accrual", #added ni kuha lang ta data sa leave setting snot sa employee
                    "lt.start_of_accrual_day", #added ni kuha lang ta data sa leave setting snot sa employee
                    "ep.date_hired", #added ni kuha lang ta data sa leave setting snot sa employee
                    "lt.effective_start_date_by", #added ni kuha lang ta data sa leave setting snot sa employee
                    "lt.effective_start_date", #added ni kuha lang ta data sa leave setting snot sa employee
                    "lt.required_documents" #added ni kuha lang ta data sa leave setting snot sa employee
            );
            $this->edb->select($s);
            $where = array(
                'employee_leaves.company_id'    => $comp_id,
                'employee.status'               => 'Active',
                'employee.deleted'              => '0',
                'accounts.deleted'              => '0',
                'accounts.user_type_id'         => '5',
                'ep.employee_status'            => 'Active',
                'edrt.parent_emp_id'            => $emp_id
            );
            $this->edb->where($where);
            $this->db->where('employee_leaves.status','Active');
            $this->edb->join('rank AS r','r.rank_id = employee_leaves.rank_id','INNER');
            $this->edb->join('leave_type AS lt','lt.leave_type_id = employee_leaves.leave_type_id','INNER');
            #$this->edb->join('leave_entitlements_settings AS les','les.leave_type_id = employee_leaves.leave_type_id AND les.rank_id = employee_leaves.rank_id','INNER');
            $this->edb->join('employee','employee.emp_id = employee_leaves.emp_id','INNER');
            $this->edb->join('accounts','accounts.account_id = employee.account_id','INNER');
            $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = employee_leaves.emp_id","LEFT");
    
            $this->edb->join("employee_payroll_information AS ep","ep.emp_id = employee.emp_id","INNER");
            $this->edb->join("payroll_group AS p","p.payroll_group_id = ep.payroll_group_id","LEFT");
            ## ADDED JAN 4 2016 fix bug 
            $this->db->group_by('employee_leaves.leaves_id');
            ## END ADDED fix bug
            $konsum_key = konsum_key();
            if($employee_id_name !==""){
                $employee_id_name = $this->db->escape_like_str(stripslashes(clean_input($employee_id_name)));
                $where2  = array(
                    'employee.company_id' => $comp_id,
                    'employee.status'     => 'Active',
                    'accounts.user_type_id' => '5',
                    'accounts.deleted'=>'0',
                    'employee.deleted'=>'0'
                );
                $this->db->where($where2);
                $this->db->where("CONVERT(CONCAT(AES_DECRYPT(employee.first_name,'{$konsum_key}'),' ',AES_DECRYPT(employee.last_name,'{$konsum_key}')) USING latin1) LIKE '%".$employee_id_name."%'", NULL, FALSE); // encrypt
                $this->db->or_where("CONVERT(AES_DECRYPT(accounts.payroll_cloud_id,'{$konsum_key}') USING latin1) = ",$employee_id_name);
                $this->db->where($where2);
            }
    
            if($sort_by != ""){
                if($order_by == ''){
                    $order_by = 'asc';
                }else{
                    if($order_by == 'desc'){
                        $order_by = 'desc';
                    }
                }
    
                if($sort_by == "first_name"){
                    $sort_by = "employee.first_name";
                    $this->edb->order_by($sort_by,$order_by);
                }elseif($sort_by == "payroll_group"){
                    $sort_by = "p.name";
                    $this->db->order_by($sort_by,$order_by);
                }elseif($sort_by == "rank_name"){
                    $sort_by = "r.rank_name";
                    $this->db->order_by($sort_by,$order_by);
                }elseif($sort_by == "leave_type"){
                    $sort_by = "lt.leave_type_id";
                    $this->db->order_by($sort_by,$order_by);
                }else if($sort_by == "last_name"){
                    $sort_by = "employee.last_name";
                    $this->edb->order_by($sort_by,$order_by);
                }
            }else{
                $this->edb->order_by('employee.last_name','asc');
            }
    
            $sql = false;
            if($display_number == true){
                $sql = $this->edb->get('employee_leaves');
            }else{
                $sql = $this->edb->get('employee_leaves',$limit,$start);
                
            }
    
            if($sql->num_rows() > 0){
                if($display_number == true){
                    return $sql->num_rows();
                }
    
                $results = $sql->result();
                $sql->free_result();
                return $results;
            }else{
                return FALSE;
            }
        
        }
        
        public function emp_leave_v2($sort_by,$employee_id_name="",$limit, $start, $comp_id,$emp_id, $display_number = false,$order_by="")
        {
            $s = array(
                'accounts.account_id','employee.first_name','employee.last_name','employee.middle_name','accounts.payroll_cloud_id',
                "employee_leaves.leaves_id",
                "employee_leaves.emp_id",
                "employee_leaves.rank_id",
                "employee_leaves.leave_type_id",
                "employee_leaves.leave_credits",
                "employee_leaves.remaining_leave_credits",
                "employee_leaves.previous_leave_credits",
                "employee_leaves.as_of",
                "employee_leaves.company_id",
                "employee_leaves.leave_accrue",
                "employee_leaves.years_of_service",
                "employee_leaves.created_date",
                "employee_leaves.updated_date",
                "employee_leaves.created_by_account_id",
                "employee_leaves.updated_by_account_id",
                "employee_leaves.`status`",
                "employee_leaves.paid_leave",
                "employee_leaves.what_happen_to_unused_leave",
                "employee_leaves.provide_half_day_option",
                "employee_leaves.allow_to_apply_leaves_beyond_limit",
                "employee_leaves.exclude_holidays",
                "employee_leaves.exclude_weekends",
                "employee_leaves.consecutive_days_after_weekend_holiday",
                "employee_leaves.num_consecutive_days_after_weekend_holiday",
                "employee_leaves.days_before_leave_application",
                "employee_leaves.num_days_before_leave_application",
                "employee_leaves.consecutive_days_allowed",
                "employee_leaves.num_consecutive_days_allowed",
                "employee_leaves.as_of_date_created",
                "employee_leaves.effective_date",
                "employee_leaves.earned_leaves",
                "employee_leaves.earned_leaves_enrollment_date",
                "employee_leaves.carried_forward_leaves",
                "employee_leaves.adjustment_leave",
                "employee_leaves.adjustments_description",
                "lt.leave_type AS leave_type","lt.required_documents AS required_documents",
                "r.rank_name","p.name AS payroll_group_name",
                "lt.accrual_period AS accrual_period",
                #   "employee_leaves.start_of_accrual",
                #   "employee_leaves.start_of_accrual_day",
                "lt.start_of_accrual", #added ni kuha lang ta data sa leave setting snot sa employee
                "lt.start_of_accrual_day", #added ni kuha lang ta data sa leave setting snot sa employee
                "ep.date_hired", #added ni kuha lang ta data sa leave setting snot sa employee
                "lt.effective_start_date_by", #added ni kuha lang ta data sa leave setting snot sa employee
                "lt.effective_start_date", #added ni kuha lang ta data sa leave setting snot sa employee
                "lt.required_documents", #added ni kuha lang ta data sa leave setting snot sa employee
                "lt.start_of_accrual",
                "lt.start_of_accrual_day",
                "lt.accrual_period",
                "lt.paid_leave",
                'lt.leave_units',
                'lt.accrual_schedule',
                'lt.start_of_accrual_month',
                'employee_leaves.existing_leave_used_to_date', // mao ni ang for used_leaves sauna na
                "employee_leaves.used_leaves_enrollment_date", // date for used leave when its enrolled
                "employee_leaves.carry_forward_expiry"
            );
            $this->edb->select($s);
            $select2 = array('lt.created_date AS lt_created_date','lt.carry_forward_expiry AS lt_carry_forward_expiry');
            $this->db->select($select2);
            $where = array(
                'employee_leaves.company_id' => $comp_id,
                'employee.status'            => 'Active',
                'accounts.user_type_id' => '5',
                'edrt.parent_emp_id'            => $emp_id
            );
            if($employee_id_name == ''){
                $where["ep.employee_status"] = "Active";
            }
            $this->edb->where($where);
            $this->db->where('employee_leaves.status','Active');
            $this->edb->join('leave_type AS lt','lt.leave_type_id = employee_leaves.leave_type_id','INNER');
            $this->edb->join('employee','employee.emp_id = employee_leaves.emp_id','INNER');
            $this->edb->join('accounts','accounts.account_id = employee.account_id','INNER');
            $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = employee_leaves.emp_id","LEFT");
            $this->edb->join("employee_payroll_information AS ep","ep.emp_id = employee.emp_id","INNER");
            $this->edb->join("payroll_group AS p","p.payroll_group_id = ep.payroll_group_id","LEFT");
            $this->edb->join('rank AS r','r.rank_id = ep.rank_id','INNER');
            $this->db->group_by('employee_leaves.leaves_id');
            $konsum_key = konsum_key();
            if($employee_id_name !==""){
                $employee_id_name = $this->db->escape_like_str(stripslashes(clean_input($employee_id_name)));
                $where2  = array(
                    'employee.company_id' => $comp_id,
                    'employee.status'     => 'Active',
                    'accounts.user_type_id' => '5',
                    'accounts.deleted'=>'0',
                    'employee.status'=>'Active',
                    'employee.deleted'=>'0'
                );
                $this->db->where($where2);
                $this->db->where("CONVERT(CONCAT(AES_DECRYPT(employee.first_name,'{$konsum_key}'),' ',AES_DECRYPT(employee.last_name,'{$konsum_key}')) USING latin1) LIKE '%".$employee_id_name."%'", NULL, FALSE); // encrypt
                $this->db->or_where("CONVERT(AES_DECRYPT(accounts.payroll_cloud_id,'{$konsum_key}') USING latin1) = ",$employee_id_name);
                $this->db->where($where2);
            }
            
            if($sort_by != ""){
                if($order_by == ''){
                    $order_by = 'asc';
                }else{
                    if($order_by == 'desc'){
                        $order_by = 'desc';
                    }
                }
                if($sort_by == "first_name"){
                    $sort_by = "employee.first_name";
                    $this->edb->order_by($sort_by,$order_by);
                }elseif($sort_by == "payroll_group"){
                    $sort_by = "p.name";
                    $this->db->order_by($sort_by,$order_by);
                }elseif($sort_by == "rank_name"){
                    $sort_by = "r.rank_name";
                    $this->db->order_by($sort_by,$order_by);
                }elseif($sort_by == "leave_type"){
                    $sort_by = "lt.leave_type_id";
                    $this->db->order_by($sort_by,$order_by);
                }else if($sort_by == "last_name"){
                    $sort_by = "employee.last_name";
                    $this->edb->order_by($sort_by,$order_by);
                }
            }else{
                $this->edb->order_by('employee.last_name','asc');
            }
            $sql = false;
            if($display_number == true){
                $sql = $this->edb->get('employee_leaves');
            }else{
                $sql = $this->edb->get('employee_leaves',$limit,$start);
            }
            if($sql->num_rows() > 0){
                if($display_number == true){
                    return $sql->num_rows();
                }
                $results = $sql->result();
                $sql->free_result();
                return $results;
            }else{
                return FALSE;
            }
        }
        /**
         * Listing of All Leave History under this manager
         */
        public function get_manager_leave_history($emp_id, $comp_id, $page="", $limit="", $num_rows=false, $emp_id)
        {
            
            $select = array(
                'a.account_id',
                'e.first_name',
                'e.last_name',
                'e.middle_name',
                'a.payroll_cloud_id',
                'a.profile_image',
                'employee_leaves_application_id',
                'e.company_id',
                'e.emp_id',
                'a.account_id',
                'date_start',
                'date_end',
                'date_filed',
                'note',
                'duration',
                'total_leave_requested',
                'leave_application_status',
                'leave_units',
                'flag_parent',
                'leave_type',
                'shift_date',
                'date_return',
                'reasons',
                'note'
            );
            $where = array(
                    'ela.status'=>'Active',
                    'ela.company_id'=>$this->company_id,
                    'lt.status'=>'Active',
                    // 'ela.leave_application_status'=>'approve',
                    'e.status'=>'Active',
                    'e.deleted'=>'0',
                    'a.deleted'=>'0',
                    'a.user_type_id' => '5',
                    'edrt.parent_emp_id' => $emp_id
            );
            $this->edb->select($select);
            $this->edb->where($where);
            $this->db->where('ela.flag_parent IS NOT NULL');
            $this->db->order_by('ela.date_filed','desc');

            $this->db->group_by('ela.employee_leaves_application_id');
            $this->edb->join('leave_type AS lt','lt.leave_type_id = ela.leave_type_id','INNER');

            if($num_rows == false){
                
                $this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
                $this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
                $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
                $q = $this->edb->get('employee_leaves_application AS ela',$limit,$page);
                $r = $q->result();
                return $r;
            }else{
                $this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
                $this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
                $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
                $q = $this->edb->get('employee_leaves_application AS ela');
                
                $r = $q->num_rows();
                return $r;
            }
        }

        /**
         * Listing of Approved Leave History
         */
        public function get_manager_leave_history_approve($emp_id, $comp_id, $page="", $limit="", $num_rows=false, $emp_id)
        {
            $select = array(
                'a.account_id',
                'e.first_name',
                'e.last_name',
                'e.middle_name',
                'a.payroll_cloud_id',
                'a.profile_image',
                'employee_leaves_application_id',
                'e.company_id',
                'e.emp_id',
                'a.account_id',
                'date_start',
                'date_end',
                'date_filed',
                'note',
                'duration',
                'total_leave_requested',
                'leave_application_status',
                'leave_units',
                'flag_parent',
                'leave_type',
                'shift_date',
                'date_return',
                'reasons',
                'note'
            );
            
            $where = array(
                    'ela.status'=>'Active',
                    'ela.company_id'=>$this->company_id,
                    'lt.status'=>'Active',
                    'ela.leave_application_status'=>'approve',
                    'e.status'=>'Active',
                    'e.deleted'=>'0',
                    'a.deleted'=>'0',
                    'a.user_type_id' => '5',
                    'edrt.parent_emp_id' => $emp_id
            );
            $this->edb->select($select);
            $this->edb->where($where);
            $this->db->where('ela.flag_parent IS NOT NULL');
            $this->db->order_by('ela.date_filed','desc');

            $this->db->group_by('ela.employee_leaves_application_id');
            $this->edb->join('leave_type AS lt','lt.leave_type_id = ela.leave_type_id','INNER');

            if($num_rows == false){
                
                $this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
                $this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
                $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
                $q = $this->edb->get('employee_leaves_application AS ela',$limit,$page);
                $r = $q->result();
                return $r;
            }else{
                $this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
                $this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
                $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
                $q = $this->edb->get('employee_leaves_application AS ela');
                
                $r = $q->num_rows();
                return $r;
            }
        }

        /**
         * Listing of Pending Leave History
         */
        public function get_manager_leave_history_pending($emp_id, $comp_id, $page="", $limit="", $num_rows=false, $emp_id)
        {
            $select = array(
                'a.account_id',
                'e.first_name',
                'e.last_name',
                'e.middle_name',
                'a.payroll_cloud_id',
                'a.profile_image',
                'employee_leaves_application_id',
                'e.company_id',
                'e.emp_id',
                'a.account_id',
                'date_start',
                'date_end',
                'date_filed',
                'note',
                'duration',
                'total_leave_requested',
                'leave_application_status',
                'leave_units',
                'flag_parent',
                'leave_type',
                'shift_date',
                'date_return',
                'reasons',
                'note'
            );
            $where = array(
            );
            $where = array(
                    'ela.status'=>'Active',
                    'ela.company_id'=>$this->company_id,
                    'lt.status'=>'Active',
                    'ela.leave_application_status'=>'pending',
                    'e.status'=>'Active',
                    'e.deleted'=>'0',
                    'a.deleted'=>'0',
                    'a.user_type_id' => '5',
                    'edrt.parent_emp_id' => $emp_id
            );
            $this->edb->select($select);
            $this->edb->where($where);
            $this->db->where('ela.flag_parent IS NOT NULL');
            $this->db->order_by('ela.date_filed','desc');

            $this->db->group_by('ela.employee_leaves_application_id');
            $this->edb->join('leave_type AS lt','lt.leave_type_id = ela.leave_type_id','INNER');

            if($num_rows == false){
                
                $this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
                $this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
                $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
                $q = $this->edb->get('employee_leaves_application AS ela',$limit,$page);
                $r = $q->result();
                return $r;
            }else{
                $this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
                $this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
                $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
                $q = $this->edb->get('employee_leaves_application AS ela');
                
                $r = $q->num_rows();
                return $r;
            }
        }

        /**
         * Listing of Pending Leave History
         */
        public function get_manager_leave_history_reject($emp_id, $comp_id, $page="", $limit="", $num_rows=false, $emp_id)
        {
            $select = array(
                'a.account_id',
                'e.first_name',
                'e.last_name',
                'e.middle_name',
                'a.payroll_cloud_id',
                'a.profile_image',
                'employee_leaves_application_id',
                'e.company_id',
                'e.emp_id',
                'a.account_id',
                'date_start',
                'date_end',
                'date_filed',
                'note',
                'duration',
                'total_leave_requested',
                'leave_application_status',
                'leave_units',
                'flag_parent',
                'leave_type',
                'shift_date',
                'date_return',
                'reasons',
                'note'
            );
            $where = array(
            );
            $where = array(
                    'ela.status'=>'Active',
                    'ela.company_id'=>$this->company_id,
                    'lt.status'=>'Active',
                    'ela.leave_application_status'=>'reject',
                    'e.status'=>'Active',
                    'e.deleted'=>'0',
                    'a.deleted'=>'0',
                    'a.user_type_id' => '5',
                    'edrt.parent_emp_id' => $emp_id
            );
            $this->edb->select($select);
            $this->edb->where($where);
            $this->db->where('ela.flag_parent IS NOT NULL');
            $this->db->order_by('ela.date_filed','desc');

            $this->db->group_by('ela.employee_leaves_application_id');
            $this->edb->join('leave_type AS lt','lt.leave_type_id = ela.leave_type_id','INNER');

            if($num_rows == false){
                
                $this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
                $this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
                $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
                $q = $this->edb->get('employee_leaves_application AS ela',$limit,$page);
                $r = $q->result();
                return $r;
            }else{
                $this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
                $this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
                $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
                $q = $this->edb->get('employee_leaves_application AS ela');
                
                $r = $q->num_rows();
                return $r;
            }
        }
        
        /**
         * LISTING TO LEAVE HISTORY
         * @param unknown $sort_by
         * @param unknown $limit
         * @param unknown $start
         * @param string $count
         * @param string $order_by
         * @return unknown
         *
         */
        public function employee_leave_apps_list($sort_by,$limit,$start,$count=false,$order_by='',$name="",$emp_id){
            $where = array(
                    'ela.status'=>'Active',
                    'ela.company_id'=>$this->company_id,
                    'lt.status'=>'Active',
                    'ela.leave_application_status'=>'approve',
                    'e.status'=>'Active',
                    'e.deleted'=>'0',
                    'a.deleted'=>'0',
                    'a.user_type_id' => '5',
                    'edrt.parent_emp_id' => $emp_id
            );
            $this->edb->where($where);
            $this->db->where('ela.flag_parent IS NOT NULL'); 
            if($order_by == ''){
                $order_by = 'asc';
            }else{
                if($order_by == 'desc'){
                    $order_by = 'desc';
                }
            }
        
            $konsum_key = konsum_key();
            if($name !==""){
                $name = str_replace("_"," ",$name);
                $name = $this->db->escape_like_str(stripslashes(clean_input($name)));
                if($name !==''){
                    $where2  = array(
                            'e.company_id' => $this->company_id,
                            'e.status'    => 'Active',
                            'a.user_type_id' => '5',
                            'a.deleted'=>'0',
                            'e.status'=>'Active',
                            'e.deleted'=>'0',
                            'edrt.parent_emp_id' => $emp_id
                    );
                    $this->db->where($where2);
                    $this->db->where("CONVERT(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) USING latin1) LIKE '%".$name."%'", NULL, FALSE); // encrypt
                    $this->db->or_where("CONVERT(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') USING latin1) =",$name);
                    $this->db->where($where2);
                }
            }
                
            if($sort_by != ""){
                $sort_array = array('lt.leave_type','ela.date_filed','ela.date_start','ela.total_leave_requested',"e.first_name","e.last_name"
                );
                if(in_array($sort_by,$sort_array)){
                    $this->db->order_by($sort_by,$order_by);
                }else{
                    $this->db->order_by('ela.date_filed','desc');
                }
            }else{
                $this->db->order_by('ela.date_filed','desc');
            }
            $this->db->group_by('ela.employee_leaves_application_id');
            $this->edb->join('leave_type AS lt','lt.leave_type_id = ela.leave_type_id','INNER');
            if($count == false){
                
                $this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
                $this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
                $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
                $q = $this->edb->get('employee_leaves_application AS ela',$limit,$start);
                $r = $q->result();
                return $r;
            }else{
                $this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
                $this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
                $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
                $q = $this->edb->get('employee_leaves_application AS ela');
                
                $r = $q->num_rows();
                return $r;
            }
        }
        
        /**
         * GET LEAVE USED TO DATE
         * @param unknown $emp_id
         * @param unknown $leave_type_id
         */
        public function get_leave_used_to_date($emp_id,$leave_type_id,$year){
            if($emp_id){
                $where = array(
                        'company_id'    =>$this->company_id,
                        'emp_id'        =>$emp_id,
                        "leave_type_id" =>$leave_type_id,
                        "leave_application_status"  =>'approve',
                        "year(date_start)"=>$year
                );
                $this->db->select('sum(credited) as sum_credited');
                $this->db->where($where);
                $this->db->where("(flag_parent = 'yes' OR flag_parent = 'no')");
                $q_leave = $this->edb->get('employee_leaves_application');
                    
                $q_row = $q_leave->row();
                $q_num_row = $q_leave->num_rows();
                $num_credited = 0;
                if($q_row){
                    if($q_row->sum_credited > 0){
                        $num_credited = number_format($q_row->sum_credited,2,".","");
                    }
                }
                return $q_num_row ? $num_credited : 0;
            }else{
                return false;
            }
        }
        
        public function emp_info_leave($company_id,$account_id,$sort_by,$limit,$start,$count=false,$order_by=''){
            $s = array(
                "employee_leaves.leaves_id",
                "employee_leaves.emp_id",
                "employee_leaves.rank_id",
                "employee_leaves.leave_type_id",
                "employee_leaves.leave_credits",
                "employee_leaves.remaining_leave_credits",
                "employee_leaves.previous_leave_credits",
                "employee_leaves.as_of",
                "employee_leaves.company_id",
                "employee_leaves.leave_accrue",
                "employee_leaves.years_of_service",
                "employee_leaves.created_date",
                "employee_leaves.updated_date",
                "employee_leaves.created_by_account_id",
                "employee_leaves.updated_by_account_id",
                "employee_leaves.`status`",
                "employee_leaves.what_happen_to_unused_leave",
                "employee_leaves.provide_half_day_option",
                "employee_leaves.allow_to_apply_leaves_beyond_limit",
                "employee_leaves.exclude_holidays",
                "employee_leaves.exclude_weekends",
                "employee_leaves.consecutive_days_after_weekend_holiday",
                "employee_leaves.num_consecutive_days_after_weekend_holiday",
                "employee_leaves.days_before_leave_application",
                "employee_leaves.num_days_before_leave_application",
                "employee_leaves.consecutive_days_allowed",
                "employee_leaves.num_consecutive_days_allowed",
                "employee_leaves.as_of_date_created",
                "employee_leaves.effective_date",
                "lt.leave_type",
                "employee_leaves.effective_date",
                "lt.effective_start_date_by",
                "lt.effective_start_date",
                "lt.required_documents",
                "lt.start_of_accrual",
                "lt.start_of_accrual_day",
                "lt.accrual_period",
                "ep.date_hired",
                "lt.effective_start_date_by",
                "lt.paid_leave",
                'lt.leave_units'
            );
            
            $where = array(
                'employee_leaves.company_id' => $company_id,
                'accounts.account_id'   => $account_id,
                'employee.status'       => 'Active',
                'accounts.user_type_id' => '5',
                'employee_leaves.status'=>'Active',
                'lt.status'=>'Active'
            );
            $this->edb->where($where);
            $this->edb->join('rank AS r','r.rank_id = employee_leaves.rank_id','INNER');
            $this->edb->join('leave_type AS lt','lt.leave_type_id = employee_leaves.leave_type_id','INNER');
            #$this->edb->join('leave_entitlements_settings AS les','les.leave_type_id = employee_leaves.leave_type_id AND les.rank_id = employee_leaves.rank_id','INNER');
            $this->edb->join('employee','employee.emp_id = employee_leaves.emp_id','INNER');
            $this->edb->join('accounts','accounts.account_id = employee.account_id','INNER');
            $this->edb->join('leave_type','leave_type.leave_type_id = employee_leaves.leave_type_id','INNER');
            $this->edb->join("employee_payroll_information AS ep","ep.emp_id = employee.emp_id","INNER");
            $this->edb->select($s);
            $this->db->group_by('employee_leaves.leaves_id');
        
            if($order_by == ''){
                $order_by = 'asc';
            }else{
                if($order_by == 'desc'){
                    $order_by = 'desc';
                }
            }
            
            if($sort_by != ""){
                if($sort_by == "name"){
                    $sort_by = "employee.first_name";
                    $this->edb->order_by($sort_by,$order_by);
                }elseif($sort_by == "payroll_group"){
                    $sort_by = "p.name";
                    $this->db->order_by($sort_by,$order_by);
                }elseif($sort_by == "rank_name"){
                    $sort_by = "r.rank_name";
                    $this->db->order_by($sort_by,$order_by);
                }elseif($sort_by == "leave_type"){
                    $sort_by = "lt.leave_type";
                    $this->db->order_by($sort_by,$order_by);
                }elseif($sort_by == "les.leave_credits"){
                    $sort_by = "les.leave_credits";
                    $this->db->order_by($sort_by,$order_by);
                }elseif($sort_by == 'balance'){
                    $sort_by = "employee_leaves.remaining_leave_credits";
                    $this->db->order_by($sort_by,$order_by);
                }elseif($sort_by == 'years_of_service'){
                    $sort_by = "employee_leaves.years_of_service";
                    $this->db->order_by($sort_by,$order_by);
                }elseif($sort_by == 'leave_accrue'){
                    $sort_by = "employee_leaves.leave_accrue";
                    $this->db->order_by($sort_by,$order_by);
                }elseif($sort_by == 'as_of'){
                    $sort_by = "employee_leaves.as_of";
                    $this->db->order_by($sort_by,$order_by);
                }
            }
        
            $q = '';
            if($count == false){
                $q = $this->edb->get('employee_leaves',$limit,$start);
                $r = $q->result();
                return $r;
            }else{
                $q = $this->edb->get('employee_leaves');
                $r = $q->num_rows();
                return $r;
            }
        
        }
        
        /**
         * EMPLOYEE LEAVE DETAILS INFO
         * @param unknown $account_id
         */
        public function employee_leave_details_info($account_id){
            $where = array(
                    'e.status'=>'Active',
                    'epi.status'=>'Active',
                    'a.deleted'=>'0',
                    'e.deleted'=>'0',
                    'e.account_id'=>$account_id,
                    'e.company_id'=>$this->company_id
            );
            $select = array(
                    'e.emp_id',
                    'a.account_id',
                    'e.company_id',
                    'epi.date_hired'
            );
            $this->edb->select($select);
            $this->edb->where($where);
            $this->edb->join('employee_payroll_information AS epi','epi.emp_id=e.emp_id','INNER');
            $this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
            $q = $this->edb->get('employee AS e');
            $r = $q->row();
            return $r;
        }
        
        public function option_leave_type_array($company_id,$emp_id){
            $leave_type = $this->get_leave_type_full($company_id);
            $option = array();
            if($leave_type)
            {
                foreach($leave_type as $k=>$v)
                {
                    ### NOW CHECK OUR LEAVE SETTINGS VALUES
                    $employment_type = array();
                    $gender = array();
                    $rank = array();
                    if($v->leave_type_id){
                        // employment type
                        $employment_type = $this->check_employment_type($this->company_id,$v->leave_type_id);
                        // for gender
                        $gender = $this->check_gender($this->company_id,$v->leave_type_id);
                        // rank
                        $rank = $this->check_rank($this->company_id,$v->leave_type_id);
                    }
                    $check_lt = $this->check_valid_leave_type($this->company_id,$emp_id,$rank,$employment_type,$gender);
                    if($check_lt){
                        //$option .='<option value="'.$v->leave_type_id.'">'.$v->leave_type.'</option>';
                        $option[$v->leave_type_id] = $v->leave_type;
                    }
                }
            }
            
            return $option;
        }
        
        public function get_leave_type_full($company_id){
            $w = array(
                    "lt.company_id"=>$company_id,
                    #"app.name"=>$employment_type,
                    #   'app.type'=>'employment_type'
            );
            $select = array(
                    'lt.leave_type_id','lt.leave_type'
            );
            $this->db->select($select);
            $this->db->where($w);
            $this->db->join('leave_type_settings_leave_type_applicable_for AS app','app.leave_type_id = lt.leave_type_id','INNER');
            $this->db->join('leave_entitlements_settings AS les','les.leave_type_id = lt.leave_type_id','LEFT');
            $this->db->group_by('lt.leave_type_id');
            $q = $this->db->get("leave_type AS lt");
                
            $r = $q->result();
            return ($r) ? $r: FALSE;
        }
        
        public function check_employment_type($company_id,$leave_type_id){
            if($leave_type_id){
                $employment_type = array();
                $where1 = array(
                        'leave_type_id'=>$leave_type_id,
                        'type'=>'employment_type',
                        'company_id'=>$company_id
                );
                $this->db->where($where1);
                $q = $this->db->get('leave_type_settings_leave_type_applicable_for');
                $r = $q->result();
                if($r){
                    foreach($r as $rk=>$rv){
                        $employment_type[] = $rv->name;
                    }
                }
                return $employment_type;
            }
        }
        
        public function check_gender($company_id,$leave_type_id){
            if($leave_type_id){
                $gender = array();
                $where2 = array(
                        'leave_type_id'=>$leave_type_id,
                        'type'=>'gender',
                        'company_id'=>$company_id
                );
                $this->db->where($where2);
                $q2 = $this->db->get('leave_type_settings_leave_type_applicable_for');
                $r2 = $q2->result();
                if($r2){
                    foreach($r2 as $r2k=>$r2v){
                        $gender[] = $r2v->name;
                    }
                }
                return $gender;
            }
        }
        
        public function check_rank($company_id,$leave_type_id){
            if($leave_type_id){
                $rank = array();
                $where3 = array(
                        'leave_type_id'=>$leave_type_id,
                        'company_id'=>$company_id
                );
                $this->db->where($where3);
                $q3 = $this->db->get('leave_entitlements_settings');
                $r3 = $q3->result();
                if($r3){
                    foreach($r3 as $r3k=>$r3v){
                        $rank[] = $r3v->rank_id;
                    }
                }
                return $rank;
            }
        }
        
        public function check_valid_leave_type($company_id,$emp_id,$rank_id ="",$employment_type,$gender=""){
            $where = array(
                    'a.deleted'=>'0',
                    'e.status'=>'Active',
                    'epi.status'=>'Active',
                    'a.user_type_id'=>5,
                    'epi.company_id'=>$company_id,
                    'e.emp_id'=>$emp_id
            );
                
            if(count($rank_id)>0){
                //$where['epi.rank_id'] = $rank_id;
                $this->db->where_in('epi.rank_id',$rank_id);
            }
                
            if($employment_type){
                $this->db->where_in('epi.employment_type',$employment_type);
            }
                
            if(count($gender)>0){
                $this->db->where_in('e.gender',$gender);
            }
            $this->db->where($where);
            $this->edb->join('employee AS e','e.account_id = a.account_id','INNER');
            $this->edb->join('employee_payroll_information AS epi','epi.emp_id = e.emp_id','INNER');
            $q = $this->edb->get('accounts AS a');
            $r = $q->row();
            return $r;
                
        }
        
        public function employee_leave_apps($emp_id,$sort_by,$limit,$start,$count=false,$order_by=''){
            $where = array(
                    'ela.emp_id'=>$emp_id,
                    'ela.status'=>'Active',
                    'ela.company_id'=>$this->company_id,
                    'lt.status'=>'Active',
                    'ela.leave_application_status'=>'approve'
            );
            $this->db->where('ela.flag_parent IS NOT NULL');
            $this->edb->where($where);
            if($order_by == ''){
                $order_by = 'asc';
            }else{
                if($order_by == 'desc'){
                    $order_by = 'desc';
                }
            }
                
            if($sort_by != ""){
                $sort_array = array('lt.leave_type','ela.date_filed','ela.date_start','ela.total_leave_requested'
                );
                if(in_array($sort_by,$sort_array)){
                    $this->db->order_by($sort_by,$order_by);
                }
            }else{
                $this->db->order_by('ela.date_filed','desc');
            }
            $this->db->group_by('ela.employee_leaves_application_id');
            $this->edb->join('leave_type AS lt','lt.leave_type_id = ela.leave_type_id','INNER');
            if($count == false){
                $q = $this->edb->get('employee_leaves_application AS ela',$limit,$start);
                $r = $q->result();
                return $r;
            }else{
                $q = $this->edb->get('employee_leaves_application AS ela');
                $r = $q->num_rows();
                return $r;
            }
        }
        
        /**
         * GET OUR PENDING APPROVAL THIS LEAVE YEAR
         * @param int $leave_type_id
         * @param int $employee_id
         * @return object
         */
        public function get_pending_approval_leaves($leave_type_id,$employee_id,$type="pending"){
            $year = date("Y"); #YEAR TODAY
            #$year = '2014';
            if($leave_type_id && $employee_id){
                $where = array(
                        'emp_id'=>$employee_id,
                        'company_id'=>$this->company_id,
                        'status'=>'Active',
                        'year(date_filed)'=>$year,
                        'leave_type_id'=>$leave_type_id
                );
        
                if($type == 'pending'){
                    $where['leave_application_status'] = 'pending';
                }else if($type =='approve'){
                    $where['leave_application_status'] = 'approve';
                }
        
                $this->db->where($where);
                $select = array(
                        'sum(total_leave_requested) AS total_request'
                );
                $this->db->select($select);
                $q = $this->db->get('employee_leaves_application');
                $result = $q->row();
                return $result;
            }else{
                return false;
            }
        }
        
        /**
         * GET LEAVE PAYABLE CREDITS TO DATE
         * @param int $emp_id
         * @param int $leave_type_id
         * @param year $year
         * @return integer
         */
        public function get_leave_payable_credits_to_date($emp_id,$leave_type_id,$year){
            if($emp_id){
                $where_lt = array(
                        'leave_type_id'=>$leave_type_id,
                        'status'=>'Active'
                );
                $leave_q = get_table_info('leave_type', $where_lt);
                if($leave_q){
                    $what_happen_to_unused_leave = $leave_q->what_happen_to_unused_leave;
                        
                    if($what_happen_to_unused_leave == 'do nothing'){
                        return 0;
                    }else if($what_happen_to_unused_leave == 'accrue to next period'){
                        return 0;
                    }else{
                        $where = array(
                                'company_id'    =>$this->company_id,
                                'emp_id'        =>$emp_id,
                                "leave_type_id" =>$leave_type_id
                                #       "year(date)"=>$year
                        );
                        $this->db->order_by('employee_leave_history_id','desc');
                        $this->db->where($where);
                        $q_leave = $this->edb->get('employee_leave_history AS elh',1);
                        $q_row = $q_leave->row();
                        $q_num_row = $q_leave->num_rows();
                        return $q_row ? $q_row->previous_period_leave_balance : 0;
                    }
                }
            }else{
                return false;
            }
        }
        
        /**
         * HISTORY CAN BE DELETED
         * @param int $emp_id
         * @param int $employee_leaves_application_id
         */
        public function history_can_be_deleted($emp_id,$employee_leaves_application_id){
            if(is_numeric($this->company_id) && is_numeric($employee_leaves_application_id)) {
                $where = array(
                    "company_id"        => $this->company_id,
                    "employee_leaves_application_id" => $this->db->escape_str($employee_leaves_application_id),
                    "status"            => "Active",
                    "leave_application_status" => "approve"
                );
                $this->db->where($where);
                $q = $this->edb->get('employee_leaves_application AS ela');
                $r = $q->row();
                $q->free_result();
                #echo last_query()." employee LEaves<Br /><br />";
                if($r) {
                    $date_start = date("Y-m-d",strtotime($r->date_start));
                    $date_end = date("Y-m-d",strtotime($r->date_end));
                    
                    /** SCENARIO 1 **/
                    $dp_where = array(
                        'dpr.view_status'   =>'Closed',
                        'prc.emp_id'        =>$emp_id,
                        'dpr.company_id'    =>$this->company_id,
                        'dpr.period_from <='=>$date_start,
                        'dpr.period_to >='  =>$date_end
                    );
                    $this->db->where($dp_where);
                    $this->db->join('payroll_run_custom AS prc','prc.draft_pay_run_id = dpr.draft_pay_run_id','LEFT');
                    $q_drft = $this->db->get('draft_pay_runs AS dpr');
                    $scene1 = $q_drft->row();
                    $q_drft->free_result();
                    /** END SCENARIO 1 **/
                    #echo last_query()." employee LEaves 1<Br /><br />";
                    
                    /** SCENARIO 2 **/
                    $dbp_where2 = array(
                        'dpr.view_status'   =>'Closed',
                        'pp.emp_id'         =>$emp_id,
                        'dpr.company_id'    =>$this->company_id,
                        'dpr.period_from <='=>$date_start,
                        'dpr.period_to >='  =>$date_end
                    );
                    $this->db->where($dbp_where2);
                    $this->db->join('payroll_payslip AS pp','pp.payroll_group_id = dpr.payroll_group_id','LEFT');
                    $q_drft2 = $this->db->get('draft_pay_runs AS dpr');
                    $scene2 = $q_drft2->row();
                    #echo last_query()." employee LEaves2<Br /><br />";
                    /** END SCENARIO 2 **/
                    
                    if($scene1){ # CONDITION 1
                        #echo '1';
                        return 0;
                    }else if($scene2){
                        #echo '2';
                        return 0;
                    }else{
                        return 1;
                    }
                }else{
                    return 0;
                }
            }else{
                return 0;
            }
        }
        
        /**
         * MAO NI MO DISPLAY SA LEAVE DATA
         * @param unknown $account_id
         * @param unknown $leaves_id
         */
        public function detail_leave_employee_data($emp_id,$leaves_id){
            if(is_numeric($emp_id) && is_numeric($leaves_id)){
                $where = array(
                    'el.emp_id'=>$emp_id,
                    'el.company_id'=>$this->company_id,
                    'el.leaves_id'=>$leaves_id,
                    'el.status'=>'Active',
                    'lt.status'=>'Active'
                );
                $this->edb->where($where);
                
                
                $s = array(
                        "el.leaves_id",
                        "el.emp_id",
                        "el.rank_id",
                        "el.leave_type_id",
                        "el.leave_credits",
                        "el.remaining_leave_credits",
                        "el.previous_leave_credits",
                        "el.as_of",
                        "el.company_id",
                        "el.leave_accrue",
                        "el.years_of_service",
                        "el.created_date",
                        "el.updated_date",
                        "el.created_by_account_id",
                        "el.updated_by_account_id",
                        "el.`status`",
                    #   "el.paid_leave",
                        "el.what_happen_to_unused_leave",
                        "el.provide_half_day_option",
                        "el.allow_to_apply_leaves_beyond_limit",
                        "el.exclude_holidays",
                        "el.exclude_weekends",
                        "el.consecutive_days_after_weekend_holiday",
                        "el.num_consecutive_days_after_weekend_holiday",
                        "el.days_before_leave_application",
                        "el.num_days_before_leave_application",
                        "el.consecutive_days_allowed",
                        "el.num_consecutive_days_allowed",
                        "el.as_of_date_created",
                        "el.effective_date",
                        "lt.leave_type",
                        "el.effective_date",
                        #   "employee_leaves.effective_start_date_by", # SA LEAVE SETTINGS TA KOHA ANI
                        #   "employee_leaves.effective_start_date", # LEAVE SETTING STA KOHA ANI
                        "el.effective_start_date_by",
                        "el.effective_start_date",
                        "lt.required_documents",
                        #   "employee_leaves.start_of_accrual",  #hide na sad kay wala na pud gamit daw
                        #   "employee_leaves.start_of_accrual_day" #hide na sad kay wala na pud gamit daw
                        "lt.start_of_accrual",
                        "lt.start_of_accrual_day",
                        "lt.accrual_period",
                        "ep.date_hired",
                        "lt.paid_leave",
                        'lt.leave_units'
                );
                $this->edb->select($s);
                $this->edb->join('leave_type AS lt','lt.leave_type_id = el.leave_type_id','INNER');
                $this->edb->join("employee_payroll_information AS ep","ep.emp_id = el.emp_id","INNER");
                $this->edb->join("employee AS e","ep.emp_id = e.emp_id","INNER");
                $q = $this->edb->get('employee_leaves AS el');
                $r = $q->row();
                return $r;
            }else{
                return false;
            }
        }
        
        /**
         * EMPLOYEE WHOSE ON LEAVE
         * @param string $count
         * @param string $today
         * @param string $emp_id
         * @param string $sort_by
         * @param string $order_by
         * @param string $start
         * @param string $limit
         */
        public function employee_whose_on_leave($count=false,$today ='',$manager_emp_id,$emp_id = '',$sort_by='',$order_by='',$start = null,$limit= null){
            if($today == ''){
                $today = date("Y-m-d");
            }
            $where = array(
                    'ela.status'=>'Active',
                    'ela.company_id'=>$this->company_id,
                    'lt.status'=>'Active',
                    'ela.leave_application_status'=>'approve',
                    'e.status'=>'Active',
                    'e.deleted'=>'0',
                    'a.deleted'=>'0',
                    'a.user_type_id' => '5',
                    'edrt.parent_emp_id' => $manager_emp_id
            );
            $this->edb->where($where);
            if($order_by == ''){
                $order_by = 'asc';
            }else{
                if($order_by == 'desc'){
                    $order_by = 'desc';
                }
            }
            if($emp_id !==''){
                $where['ela.emp_id'] = $emp_id;
            }
        
            $konsum_key = konsum_key();
            $sql ="date('".$today."') BETWEEN date(ela.date_start) AND date(ela.date_end)";
            $this->db->where($sql);
            if($sort_by != ""){
                $sort_array = array('lt.leave_type','ela.date_filed','ela.date_start','ela.total_leave_requested',"e.first_name","e.last_name"
                );
                if(in_array($sort_by,$sort_array)){
                    $this->db->order_by($sort_by,$order_by);
                }else{
                    $this->db->order_by('ela.date_filed','asc');
                }
            }
            $this->db->group_by('ela.emp_id');
            $this->edb->join('leave_type AS lt','lt.leave_type_id = ela.leave_type_id','INNER');
            $array = array(
                'e.first_name','e.last_name','epi.payroll_group_id','a.profile_image','a.account_id','e.emp_id','epi.department_id'
            );
            $this->edb->select($array);
            $this->db->where('ela.flag_parent IS NOT NULL');
            if($count == false){
                
                $this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
                $this->edb->join('employee_payroll_information AS epi','e.emp_id=epi.emp_id','LEFT');
                $this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
                $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
                $q = $this->edb->get('employee_leaves_application AS ela',$limit,$start);
                $r = $q->result();
                
                return $r;
            }else{
                $this->edb->join('employee AS e','e.emp_id = ela.emp_id','INNER');
                $this->edb->join('employee_payroll_information AS epi','e.emp_id=epi.emp_id','LEFT');
                $this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
                $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
                $q = $this->edb->get('employee_leaves_application AS ela');
                $r = $q->num_rows();
                
                return $r;
            }
        }
    }