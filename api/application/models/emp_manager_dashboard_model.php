<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Employee Manager Dashboard Model 
 *
 * @category model
 * @version 1.0
 * @author John Fritz
 */
class Emp_manager_dashboard_model extends CI_Model {
    
    public function get_employee_headcount($company_id,$emp_id){
        $emp_where = array(
                "epi.company_id" => $company_id,
                "epi.status" => "Active",
                'edrt.parent_emp_id'    => $emp_id
        );
        $this->db->where($emp_where);
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
        $query = $this->db->get("employee_payroll_information AS epi");
        $row = $query->result();

        return ($row) ? count($row) : 0;
    }
    
    /**
     * get out of office
     * @param unknown $company_id
     */
    public function team_on_leave($company_id,$emp_id,$limit = 10000){
        $now = date("Y-m-d");
        $where = array(
                "ela.company_id"                => $company_id,
                "ela.leave_application_status"  => "approve",
                "ela.status"                    => "Active",
                "edrt.parent_emp_id"            => $emp_id
        );
        
        $this->db->where($where);
        $this->db->where("(`ela`.`flag_parent` = 'no' OR `ela`.`flag_parent` IS NULL)");
        $this->edb->join("leave_type AS lt","lt.leave_type_id = ela.leave_type_id","LEFT");
        $this->edb->join("employee AS e","e.emp_id = ela.emp_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
        $this->db->order_by("ela.date_start","DESC");
        $this->db->group_by("ela.date_start");
        $query = $this->edb->get("employee_leaves_application AS ela",$limit);
        $result = $query->result();
        
        return ($result) ? $result : FALSE;
    }
    
    /**
     * Employee Tardiness
     * @param unknown $company_id
     */
    public function employee_tardiness($company_id,$emp_id,$limit = 10000){ 
        // VERSION 1.4.2
        $now = date("Y-m-d");
        $w = array(
            "e.company_id"=>$company_id,
            "e.status"=>"Active",
            "epi.status"=>"Active",
            "eti.tardiness_min > "=> 0,
            "eti.date"=> $now,
            "edrt.parent_emp_id" => $emp_id
                
        );
        
        $this->db->where($w);
        $this->db->select("epi.payroll_group_id AS pg_id",FALSE);
        $this->edb->join("employee AS e","e.emp_id = eti.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");
        $this->db->order_by("eti.tardiness_min","DESC");
        $q = $this->edb->get("employee_time_in AS eti",$limit);
        $r = $q->result();
        
        return ($r) ? $r : FALSE ;
    
    }
    
    /**
     * Get Current Payroll Period
     * @param unknown $company_id
     */
    public function get_current_payroll_period($company_id){
        $now = date("Y-m-d");
        $pc_where = array(
                "cut_off_from <=" => $now,
                "cut_off_to >=" => $now,
                "company_id" => $company_id
        );
        $this->db->where($pc_where);
        $pc_query = $this->db->get("payroll_calendar");
        $pc_row = $pc_query->row();
        return ($pc_row) ? $pc_row : FALSE ;
    }
    
    /**
     * Get Employee List Time In
     * @param unknown cut_off_from
     * @param unknown $cut_off_to
     * @param unknown $company_id
     */
    public function get_employee_list($cut_off_from,$cut_off_to,$company_id,$emp_id,$params=NULL){
    
        $sort_array = array(
                "e.last_name",
                "d.department_name",
                "p.position_name"
        );
    
        // FOR TARDY EMPLOYEE LISTING FLAG
        if($params != NULL){
            if(isset($params["flag_tardy_view"]) != ""){
                $this->db->where("(eti.overbreak_min > 0 OR eti.late_min > 0)");
            }
        }
    
        $w = array(
                "epi.company_id" => $company_id,
                "epi.status"=>"Active",
                "e.status"=>"Active",
                "eti.date >= "=>$cut_off_from,
                "eti.date <= "=>$cut_off_to,
                "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($w);
    
        $this->db->where("eti.time_in IS NOT NULL");
        $this->db->where("eti.time_out IS NOT NULL");
        $this->db->where("(eti.time_in_status = 'approved' OR eti.time_in_status IS NULL)");
    
        $this->edb->join("employee AS e","eti.emp_id = e.emp_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
        
        if($params != NULL){
            if($params["sort_by"] != ""){
                if(in_array($params["sort_by"], $sort_array)){
                    if($params["sort_by"] == "e.last_name"){
                        $this->edb->order_by($params["sort_by"],$params["sort"]);
                    }
                    else{
                        $this->db->order_by($params["sort_by"],$params["sort"]);
                    }
                }
            }
        }
    
        $q = $this->edb->get("employee_time_in AS eti");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }
        
    /**
     * check employee time in early
     * @param unknown $work_schedule_id
     * @param unknown $res_start_time
     * @param unknown $res_end_time
     * @param unknown $company_id
     * @param string $params
     */
    public function check_employee_time_in_early($work_schedule_id,$res_start_time,$res_end_time,$company_id,$emp_id,$params=NULL){
        $konsum_key = konsum_key();
        
        $sort_array = array(
            "e.last_name",
            "d.department_name",
            "p.position_name"
        );
        
        $w = array(
            "epi.company_id" => $company_id,
            "epi.status"=>"Active",
            "e.status"=>"Active",
            "eti.time_in >= "=>$res_start_time,
            "eti.time_in <= "=>$res_end_time,
            "eti.work_schedule_id"=>$work_schedule_id,
            "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($w);
        
        if($params != NULL){
            if($params["search"] != ""){
                $search = $params["search"];
                $this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
            }
        }
        
        #$this->db->where("eti.time_in IS NOT NULL");
        #$this->db->where("eti.time_out IS NOT NULL");
        #$this->db->where("(eti.time_in_status = 'approved' OR eti.time_in_status IS NULL)");
        
        $this->edb->join("employee AS e","eti.emp_id = e.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");
        
        if($params != NULL){
            if($params["sort_by"] != ""){
                if(in_array($params["sort_by"], $sort_array)){
                    if($params["sort_by"] == "e.last_name"){
                        $this->edb->order_by($params["sort_by"],$params["sort"]);
                    }
                    else{
                        $this->db->order_by($params["sort_by"],$params["sort"]);
                    }
                }
            }else{
                $this->db->order_by("eti.time_in","ASC");
            }
        }else{
            $this->db->order_by("eti.time_in","ASC");
        }
        
        // $q = $this->edb->get("employee_time_in AS eti",3);
        $q = $this->edb->get("employee_time_in AS eti");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }
    
    /**
     * Work Schedule Start Time, End Time
     * @param unknown $current
     * @param unknown $company_id
     */
    public function check_employee_work_schedule($from,$to,$company_id,$params = NULL){
    
        $array = array();
        $time = date("H:i:s");
        
        // REGULAR SCHEDULE
        #$this->db->select("work_start_time,work_end_time,total_work_hours");
        
        if($params != NULL){
            if($params["work_schedule_id"] != "" && $params["work_schedule_id"] != "All"){
                $work_schedule_where = array(
                    "work_schedule_id" => $params["work_schedule_id"]
                );
                $this->db->where($work_schedule_where);
            }
        }
        $w = array(
            "company_id"=>$company_id,
            "total_work_hours > "=>0,
            "days_of_work"=>date("l",strtotime($from)),
            "status"=>"Active",
            "work_start_time <= "=>$time,
            "work_end_time >= "=>$time
            #"work_start_time <= "=>$time
        );
        $this->db->where($w);
        $this->db->order_by("work_start_time","ASC");
        $q = $this->db->get("regular_schedule");
        $r = $q->result();
        
        if($r){
            foreach($r as $row){
                $schedule = array(
                    "start_time"=>$row->work_start_time,
                    "end_time"=>$row->work_end_time,
                    "total_work_hours"=>$row->total_work_hours,
                    "work_schedule_id"=>$row->work_schedule_id
                );
                
                array_push($array, $schedule);
            }
        }
    
        // SPLIT SCHEDULE
        #$this->db->select("start_time,end_time,total_hours_work_per_block");
        if($params != NULL){
            if($params["work_schedule_id"] != "" && $params["work_schedule_id"] != "All"){
                $work_schedule_where = array(
                    "work_schedule_id" => $params["work_schedule_id"]
                );
                $this->db->where($work_schedule_where);
            }
        }
        $w = array(
            "company_id"=>$company_id,
            "total_hours_work_per_block > "=>0,
            "start_time <= "=>$time,
            "end_time >= "=>$time
            #"start_time <= "=>$time
        );
        $this->db->where($w);
        $this->db->order_by("start_time","ASC");
        $q = $this->db->get("schedule_blocks");
        $r = $q->result();
        if($r){
            foreach($r as $row){
                $schedule = array(
                    "start_time"=>$row->start_time,
                    "end_time"=>$row->end_time,
                    "total_work_hours"=>$row->total_hours_work_per_block,
                    "work_schedule_id"=>$row->work_schedule_id
                );
            
                array_push($array, $schedule);
            }
        }
    
        // FLEXIBLE SCHEDULE
        #$this->db->select("latest_time_in_allowed AS start_time, ADDTIME(latest_time_in_allowed,CONCAT(total_hours_for_the_day,':00:00')) AS end_time, total_hours_for_the_day",FALSE);
        if($params != NULL){
            if($params["work_schedule_id"] != "" && $params["work_schedule_id"] != "All"){
                $work_schedule_where = array(
                        "work_schedule_id" => $params["work_schedule_id"]
                );
                $this->db->where($work_schedule_where);
            }
        }
        $w = array(
            "company_id"=>$company_id,
            "total_hours_for_the_day > "=>0,
            #"latest_time_in_allowed <= "=>$time,
            #"latest_time_in_allowed >= "=>$time = date("H:i:s",strtotime("+10 hours")) // max 10 hours
            "latest_time_in_allowed <= "=>$time
        );
        $this->db->where($w);
        $this->db->where("latest_time_in_allowed IS NOT NULL");
        $this->db->order_by("latest_time_in_allowed","ASC");
        $q = $this->db->get("flexible_hours");
        $r = $q->result();
        if($r){
            
            foreach($r as $row){
                if(strtotime($row->latest_time_in_allowed) <= strtotime($time) && strtotime($row->latest_time_in_allowed." +{$row->total_hours_for_the_day} hours") >= strtotime($time)){
                    $schedule = array(
                        "start_time"=>$row->latest_time_in_allowed,
                        "end_time"=> date("H:i:s",strtotime($row->latest_time_in_allowed." +{$row->total_hours_for_the_day} hours")),
                        "total_work_hours"=>$row->total_hours_for_the_day,
                        "work_schedule_id"=>$row->work_schedule_id
                    );
                        
                    array_push($array, $schedule);
                }
            }
            
        }
    
        return $array;
    
    }
    
    /**
     * Get uniform working day
     * @param int $company_id
     * @param int $work_schedule_id
     * @param string $weekday
     */
    public function get_uniform_working_day($company_id,$work_schedule_id,$weekday)
    {
        $where = array(
                'company_id'       => $company_id,
                "work_schedule_id"=>$work_schedule_id,
                #'working_day'     => $weekday
                'days_of_work'     => $weekday
        );
        $this->db->where($where);
        #$q = $this->db->get('uniform_working_day');
        $q = $this->db->get('regular_schedule');
        $result = $q->row();
        $q->free_result();
        return ($result) ? $result : false;
    }
    
    /**
     * Get flexible hour
     * @param int $company_id
     * @param int $work_schedule_id
     */
    public function get_flexible_hour($company_id,$work_schedule_id)
    {
        $where = array(
                'company_id'       => $company_id,
                "work_schedule_id"=>$work_schedule_id
        );
        $this->db->where($where);
        $q = $this->db->get('flexible_hours');
        $result = $q->row();
        $q->free_result();
        return ($result) ? $result : false;
    }
    
    /**
     * Get workshift
     * @param int $company_id
     * @param int $work_schedule_id
     */
    public function get_workshift($company_id,$work_schedule_id)
    {
        $s = array(
                "*","sb.total_hours_work_per_block AS total_work_hours"
        );
        $this->db->select($s);
        $where = array(
                'ss.company_id'        => $company_id,
                "ss.work_schedule_id"=>$work_schedule_id
        );
        $this->db->where($where);
        $this->db->join("schedule_blocks AS sb","sb.work_schedule_id = ss.work_schedule_id","LEFT");
        $q = $this->db->get('split_schedule AS ss');
        $result = $q->row();
        $q->free_result();
        return ($result) ? $result : false;
    }
    
    /**
     * Get workday
     * @param int $company_id
     * @param int $work_schedule_id
     */
    public function get_workday($company_id,$work_schedule_id)
    {
        $s = array("*","work_type_name AS workday_type");
        $this->db->select($s);
        $where = array(
                'comp_id'      => $company_id,
                "work_schedule_id"=>$work_schedule_id
        );
        $this->db->where($where);
        $q = $this->db->get('work_schedule');
        $result = $q->row();
        $q->free_result();
        return ($result) ? $result : false;
    }
    
    /**
     * Work Schedule ID
     * @param unknown_type $company_id
     * @param unknown_type $emp_id
     * @param unknown_type $date
     */
    public function work_schedule_id($company_id,$emp_id,$date=NULL){
    
        $s = array(
            "work_schedule_id"
        );
        $this->db->select($s);
    
        $w_date = array(
            "valid_from <="     =>  $date,
            "until >="          =>  $date
        );
        if($date != NULL) $this->db->where($w_date);
    
        $w = array(
            "emp_id"=>$emp_id,
            "company_id"=>$company_id,
            "status"=>"Active"
        );
        $this->db->where($w);
        $q = $this->db->get("employee_shifts_schedule");
        $r = $q->row();
        $q->free_result();
    
        if($r){
            // split scheduling
            return $r;
        }else{
                
            $s = array(
                "work_schedule_id"
            );
            $this->db->select($s);
                
            // default work scheduling
            $w = array(
                "epi.emp_id"=>$emp_id,
                "epi.company_id"=>$company_id,
                "epi.status"=>"Active"
            );
            $this->db->where($w);
            $this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
            $q = $this->db->get("employee_payroll_information AS epi");
            $r = $q->row();
            $q->free_result();
            return ($r) ? $r : FALSE ;
        }
    }
    
    /**
     * Employee Tardiness Listing
     * @param unknown $company_id
     */
    public function employee_tardiness_listing_counter($company_id, $emp_id, $search = ""){
        $konsum_key = konsum_key();
    
        $now = date("Y-m-d");
    
        $this->db->select("COUNT(*) as total");
    
        $w = array(
                "e.company_id"=>$company_id,
                "e.status"=>"Active",
                "epi.status"=>"Active",
                "eti.tardiness_min > "=> 0,
                "eti.date"=> $now,
                "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($w);
            
        if($search != "" && $search != "all"){
            $this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
        }
            
        $this->db->select("epi.payroll_group_id AS pg_id",FALSE);
        $this->edb->join("employee AS e","e.emp_id = eti.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");
        $q = $this->edb->get("employee_time_in AS eti");
        $r = $q->row();
        return ($r) ? $r->total : FALSE ;
    }
    
    /**
     * Employee Tardiness Listing
     * @param unknown $company_id
     */
    public function employee_tardiness_listing($company_id, $emp_id, $limit = 10, $start = 0, $search = "", $sort_by = "", $sort = "ASC"){
        $konsum_key = konsum_key();
        $sort_array = array(
            "e.last_name",
            "d.department_name",
            "p.position_name"
        );
        
        $now = date("Y-m-d");
        
        $w = array(
            "e.company_id"=>$company_id,
            "e.status"=>"Active",
            "epi.status"=>"Active",
            "eti.tardiness_min > "=> 0,
            "eti.date"=> $now,
            "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($w);
            
        if($search != "" && $search != "all"){
            $this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
        }
            
        $this->db->select("epi.payroll_group_id AS pg_id",FALSE);
            
        $this->edb->join("employee AS e","e.emp_id = eti.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");
        
        if($sort_by != ""){
            if(in_array($sort_by, $sort_array)){
                if($sort_by == "e.last_name"){
                    $this->edb->order_by($sort_by,$sort);
                }
                else{
                    $this->db->order_by($sort_by,$sort);
                }
            }
        }
        else{
            $this->edb->order_by("e.last_name","ASC");
        }
            
        $this->db->order_by("eti.tardiness_min","ASC");
            
        $q = $this->edb->get("employee_time_in AS eti",$limit,$start);
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }
    
    /**
     * check employee for employee tardy
     * @param unknown $work_schedule_id
     * @param unknown $res_start_time
     * @param unknown $res_end_time
     * @param unknown $company_id
     * @param string $params
     */
    public function check_employee_for_tardy($work_schedule_id,$res_start_time,$res_end_time,$company_id,$emp_id,$params=NULL){
        $konsum_key = konsum_key();
    
        $sort_array = array(
            "e.last_name",
            "d.department_name",
            "p.position_name"
        );
    
        $w = array(
            "epi.company_id" => $company_id,
            "epi.status"=>"Active",
            "e.status"=>"Active",
            "eti.time_in >= "=>$res_start_time,
            "eti.time_in <= "=>$res_end_time,
            "eti.work_schedule_id"=>$work_schedule_id,
            "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($w);
    
        if($params != NULL){
            if($params["search"] != ""){
                $search = $params["search"];
                $this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
            }
        }
    
        $this->edb->join("employee AS e","eti.emp_id = e.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");
    
        if($params != NULL){
            if($params["sort_by"] != ""){
                if(in_array($params["sort_by"], $sort_array)){
                    if($params["sort_by"] == "e.last_name"){
                        $this->edb->order_by($params["sort_by"],$params["sort"]);
                    }
                    else{
                        $this->db->order_by($params["sort_by"],$params["sort"]);
                    }
                }
            }else{
                $this->db->order_by("eti.time_in","ASC");
            }
        }else{
            $this->db->order_by("eti.time_in","ASC");
        }

        $q = $this->edb->get("employee_time_in AS eti");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }
    
    /**
     * Get Grace Period
     * @param unknown_type $emp_id
     * @param unknown_type $comp_id
     */
    public function get_grace_period($emp_id,$comp_id){
        $w = array(
                "epi.company_id"=>$comp_id,
                "epi.status"=>"Active",
                "ts.comp_id"=>$comp_id,
                "ts.status"=>"Active"
        );
        $this->db->where($w);
        $this->db->join("employee_payroll_information AS epi","epi.rank_id = ts.rank_id","LEFT");
        $sql = $this->db->get("tardiness_settings AS ts");
        $row = $sql->row();
        $sql->free_result();
        // return ($row) ? $row->tarmin : 30 ; // company wide 30 minutes default
    
        if($row){
            return $row->tarmin;
        }else{
            $w = array(
                    "comp_id"=>$comp_id,
                    "default"=>1,
                    "status"=>"Active"
            );
            $this->db->where($w);
            $sql = $this->db->get("tardiness_settings");
            $row = $sql->row();
            return ($row) ? $row->tarmin : 0 ;
        }
    }
    
    /**
     * Employee Time In List with sorting
     * @param unknown_type $comp_id
     * @param unknown_type $emp_id
     */
    public function time_in_list_sorted($comp_id, $emp_id, $type){
        $period = next_pay_period($emp_id, $comp_id);
        $w = array(
                'eti.date >=' => date('Y-m-d', strtotime($period->cut_off_from)),
                'eti.date <=' => date('Y-m-d', strtotime($period->cut_off_to)),
                "edrt.parent_emp_id" => $emp_id,
                'eti.comp_id'=> $comp_id,
                'eti.status'=> 'Active'
        );
        $this->db->where($w);
        if($type == 'pending' || $type == 'reject'){
            $this->db->where('eti.time_in_status',$type);
        }
        if($type == 'undertime'){
            $u_where = array('eti.undertime_min >'=> 0);
            $this->db->where($u_where);
        }
        if($type == 'tardiness'){
            $t_where = array('eti.tardiness_min >'=> 0);
            $this->db->where($t_where);
        }
        $this->edb->join("employee AS e","e.emp_id = eti.emp_id","LEFT");
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");
        
        $this->db->order_by('eti.date','DESC');
        $sql = $this->db->get('employee_time_in AS eti');
            
        if($sql->num_rows() > 0){
            $results = $sql->result();
            $sql->free_result();
            //last_query();
            return $results;
        }else{
            return FALSE;
        }
    }
    
    public function get_all_employees($emp_id, $company_id,$limit = 10000) {
        $where = array(
                "edrt.parent_emp_id" => $emp_id,
                "e.company_id" => $company_id,
                "e.status" => "Active"
        );
        
        $this->edb->where($where);
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $q = $this->edb->get("employee AS e",$limit);
        $r = $q->result();
        
        return ($r) ? $r : FALSE;
    }
    
    public function get_employee_shifts_sched($emp_id,$company_id) {
        $where = array(
                "emp_id" => $emp_id,
                "company_id" => $company_id,
                "status" => "Active",
                "payroll_group_id" => 0
        );
        
        $this->edb->where($where);
        $this->db->order_by("valid_from","DESC");
        $q = $this->edb->get("employee_shifts_schedule");
        $r = $q->row();
        #last_query();
        return ($r) ? $r : FALSE;
    }
    
    /**
     * get closest schedule of the current time
     * @param unknown $company_id
     */
    public function get_current_schedule($emp_id,$company_id){
        $schedule = array();
        $result = $this->get_schedule_for_tile($emp_id,$company_id);

        /*$where = array(
            "comp_id" => $this->company_id
        );
        $this->db->where($where);
        $query = $this->db->get("work_schedule");
        $result = $query->result();*/
        
        
        
        if($result){
            foreach ($result as $row){
                $work_type = $row->work_type_name;
                $work_schedule_id = $row->work_schedule_id;
                $work_schedule_name = $row->name;
                switch ($work_type){
                    case "Uniform Working Days" :
                        $w = array(
                            "company_id" => $company_id,
                            "work_schedule_id" => $work_schedule_id,
                            //"work_start_time <=" => date("H:i:s"),
                            //"work_end_time >=" => date("H:i:s"),
                            "days_of_work" => date("l")
                        );
                        
                        $this->db->where($w);
                        $q = $this->db->get("regular_schedule");
                        $r = $q->result();
                        if($r){
                            foreach ($r as $reg){
                                $from_date = date("Y-m-d");
                                $to_date = date("Y-m-d");
                                    
                                if(date("A", strtotime($reg->work_start_time)) == "PM" && date("A", strtotime($reg->work_end_time)) == "AM" && date("A") == "AM"){
                                    $from_date = date("Y-m-d",strtotime("-1 day"));
                                }
                                
                                $start = date("Y-m-d H:i:s",strtotime("{$from_date} {$reg->work_start_time}"));
                                $end = date("Y-m-d H:i:s",strtotime("{$to_date} {$reg->work_end_time}"));
                                
                                $ars = array(
                                    "name" => $work_schedule_name,
                                    "start" => $start,
                                    "end" => $end,
                                    "work_schedule_id" => $work_schedule_id
                                );
                                if(date("Y-m-d H:i:s",strtotime($ars["start"])) <= date("Y-m-d H:i:s")){
                                    array_push($schedule, (object) $ars);
                                }
                            }
                        }
                        
                    break;
                    case "Flexible Hours" :
                        /* $this->db->select("*","latest_time_in_allowed AS start_time, ADDTIME(latest_time_in_allowed,CONCAT(total_hours_for_the_day,':00:00')) AS end_time",FALSE);
                            $time = date("H:i:s");
                            $start_time_man_date = date("Y-m-d");
                            $end_time_man_date = date("Y-m-d",strtotime("+1 day"));
                            
                            // ADD
                            $this->db->where("(
                                    (
                                        latest_time_in_allowed <= '{$time}'
                                        AND
                                        ADDTIME(latest_time_in_allowed,CONCAT(total_hours_for_the_day,':00:00')) >= '{$time}'
                                    )
                                        OR
                                    (
                                        DATE_FORMAT(latest_time_in_allowed, '{$start_time_man_date} %H:%i:%s') <= '{$start_time_man_date} {$time}'
                                        AND
                                        DATE_FORMAT(ADDTIME(latest_time_in_allowed,CONCAT(total_hours_for_the_day,':00:00')), '{$end_time_man_date} %H:%i:%s') >= '{$start_time_man_date} {$time}'
                                        AND
                                        DATE_FORMAT(latest_time_in_allowed, '%p') = 'PM'
                                        AND
                                        DATE_FORMAT(ADDTIME(latest_time_in_allowed,CONCAT(total_hours_for_the_day,':00:00')), '%p') = 'AM'
                                    )
                                )
                            "); */
                        
                        $this->db->select("*,latest_time_in_allowed AS start_time, ADDTIME(latest_time_in_allowed,CONCAT(total_hours_for_the_day,':00:00')) AS end_time",FALSE);
                        
                        $w = array(
                            "company_id" => $company_id,
                            "work_schedule_id" => $work_schedule_id,
                            "not_required_login" => "0",
                            //"latest_time_in_allowed <=" => date("H:i:s")
                        );
                        $this->db->where($w);
                        
                        $q = $this->db->get("flexible_hours");
                        $r = $q->result();
                        if($r){
                            
                            /* foreach ($r as $flex){
                                $ars = array(
                                    "name" => $work_schedule_name,
                                    "start" => $flex->latest_time_in_allowed,
                                    "end" => $flex->end_time,
                                    "work_schedule_id" => $flex->work_schedule_id
                                );
                                array_push($schedule, (object) $ars);
                            } */
                            
                            foreach ($r as $flex){
                                $from_date = date("Y-m-d");
                                $to_date = date("Y-m-d");
                                
                                $n_end_tal = $flex->total_hours_for_the_day;
                                $n_hour = floor($n_end_tal);
                                $n_min = round(60*($n_end_tal-$n_hour));
                                
                                $start = date("Y-m-d H:i:s",strtotime("{$from_date} {$flex->latest_time_in_allowed}"));
                                $time_end = date("H:i:s",strtotime("{$start} +{$n_hour} hours +{$n_min} minute"));
                                
                                if(date("A", strtotime($start)) == "PM" && date("A", strtotime($time_end)) == "AM" && date("A") == "AM"){
                                    $from_date = date("Y-m-d",strtotime("-1 day"));
                                    $start = date("Y-m-d H:i:s",strtotime("{$from_date} {$flex->latest_time_in_allowed}"));
                                }
                                
                                $end = date("Y-m-d H:i:s",strtotime("{$to_date} {$time_end}"));
                                $ars = array(
                                    "name" => $work_schedule_name,
                                    "start" => $start,
                                    "end" => $end,
                                    // "end" => date("H:i:s",strtotime("{$start} +{$flex->total_hours_for_the_day} hours")),
                                    #"end" => $flex->end_time,
                                    "work_schedule_id" => $work_schedule_id
                                );
                                if(date("Y-m-d h:i:s") < $end){
                                    if(date("Y-m-d H:i:s",strtotime($ars["start"])) <= date("Y-m-d H:i:s")){
                                        array_push($schedule, (object) $ars);
                                    }
                                }
                            }
                        }
                        
                    break;
                    case "Workshift" :
                        
                        
                    break;
                }
            }
        }
        
        // sort to get closest start time
        usort($schedule, function($a, $b){
            return strcmp($b->start, $a->start);
        });
        
        return (count($schedule) > 0) ? $schedule[0] : false;
    }
    
    /**
     * get employee list count
     * @param unknown $company_id
     */
    public function get_employee_list_count($company_id, $emp_id, $search = "", $filter){
        $konsum_key = konsum_key();
        $where = array(
            "e.company_id" => $company_id,
            "e.status" => "Active",
            "epi.employee_status" => "Active",
            "epi.timesheet_required" => "yes",
            "epi.status" => "Active",
            "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($where);
        $this->db->where_in("e.emp_id",$this->filter_employee2($company_id, $emp_id, $search, $filter));
        if($search != "" && $search != "all"){
            $this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
        }
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
        $this->edb->join("regular_schedule AS rs","rs.work_schedule_id = ws.work_schedule_id","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        $this->db->group_by("e.emp_id");
        $query = $this->edb->get("employee AS e");
        $result = $query->result();
        
        return ($result) ? $query->num_rows() : 0;
    }
    
    /**
     * get employee list with filters
     * @param unknown $company_id
     */
    public function get_employee_list_noshow($company_id, $emp_id, $limit = 10, $start = 0, $search = "", $sort_by = "", $sort = "ASC", $filter){
        $konsum_key = konsum_key();
        $sort_array = array(
            "e.last_name",
            "d.department_name",
            "p.position_name"
        );
        $where = array(
            "e.company_id" => $company_id,
            "e.status" => "Active",
            "epi.employee_status" => "Active",
            "epi.timesheet_required" => "yes",
            "epi.status" => "Active",
            "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($where);
        $this->db->where_in("e.emp_id",$this->filter_employee2($company_id, $emp_id, $search, $filter));
        if($search != "" && $search != "all"){
            $this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
        }
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
        $this->edb->join("regular_schedule AS rs","rs.work_schedule_id = ws.work_schedule_id","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("employee_shifts_schedule AS ess","ess.emp_id = e.emp_id","LEFT");
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        $this->db->select(array("epi.payroll_group_id AS pg_id","pg.work_schedule_id AS work_sched_id"));
        $this->edb->select(array("*","e.emp_id AS emp_id"));
        if($sort_by != ""){
            if(in_array($sort_by, $sort_array)){
                if($sort_by == "e.last_name"){
                    $this->edb->order_by($sort_by,$sort);
                }
                else{
                    $this->db->order_by($sort_by,$sort);
                }
            }
        }
        else{
            $this->edb->order_by("e.last_name","ASC");
        }
        $this->db->group_by("e.emp_id");
        $query = $this->edb->get("employee AS e",$limit, $start);
        $result = $query->result();
        
        return ($result) ? $result : false;
    }
    
    /**
     * returns array of emp id of absent employee
     * @param unknown $company_id
     */
    public function filter_employee2($company_id, $emp_id, $search = "", $filter){
        $filter_work_schedule_id = $filter["work_schedule_id"];
        $today = date("Y-m-d");
        $where = array(
            "e.company_id" => $company_id,
            "e.status" => "Active",
            "epi.employee_status" => "Active",
            "epi.status" => "Active",
            "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($where);
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        $this->db->group_by("e.emp_id");
        $query = $this->edb->get("employee AS e");
        $result = $query->result();
        $filter_emp_id = array();
    
        if($result){
            foreach($result as $row){
                $emp_id = $row->emp_id;
                $custom_sched = check_employee_custom_schedule($row->emp_id);
                $work_schedule_id = ($custom_sched) ? $custom_sched->work_schedule_id : $row->work_schedule_id;
                $workday = get_workday($company_id,$work_schedule_id);
                
                if($workday != FALSE){
                    switch ($workday->workday_type) {
                        case 'Uniform Working Days':
                            $w = array(
                                "company_id" => $company_id,
                                "work_schedule_id" => $work_schedule_id,
                                ///"work_start_time <= " => date("H:i:s"),
                                ///"work_end_time >= " => date("H:i:s"),
                                "days_of_work" => date("l")
                            );
                            $this->db->where("((work_start_time <= '".date("H:i:s")."' AND work_end_time >= '".date("H:i:s")."')
                                OR
                                (
                                    (DATE_FORMAT(work_start_time, '".date("Y-m-d")." %H:%i:%s') <= '".date("Y-m-d H:i:s")."' AND DATE_FORMAT(work_end_time, '".date("Y-m-d",strtotime("+1 day"))." %H:%i:%s') >= '".date("Y-m-d H:i:s")."')
                                    OR
                                    (DATE_FORMAT(work_start_time, '".date("Y-m-d",strtotime("-1 day"))." %H:%i:%s') <= '".date("Y-m-d H:i:s")."' AND DATE_FORMAT(work_end_time, '".date("Y-m-d")." %H:%i:%s') >= '".date("Y-m-d H:i:s")."')
                                )
                            )");
                            $this->db->where($w);
                            $query = $this->db->get("regular_schedule");
                            $r = $query->row();
                            if($r){
                                $date = $today;
                                $is_night_shift = false;
                                //echo date("A", strtotime($r->work_start_time))." == PM && ".date("A", strtotime($r->work_end_time))." == AM<br>";
                                if(date("A", strtotime($r->work_start_time)) == "PM" && date("A", strtotime($r->work_end_time)) == "AM"){
                                    $date = date("Y-m-d",strtotime("{$today} -1 day"));
                                    $is_night_shift = true;
                                }
                                $w = array(
                                    "emp_id" => $emp_id,
                                    "date" => $date,
                                    "status" => "Active"
                                );
                                $this->db->where($w);
                                $time_in = $this->db->get("employee_time_in");
                                $time_in_row = $time_in->row();
                                //last_query();
                                if(!$time_in_row){
                                    //echo $emp_id."<br>";
                                    $curr_y = date("Y");
                                    $curr_m = date("m");
                                    $curr_d = date("d");
                                    $curr_dt = date("Y-m-d H:i:s");
                                    $start_time = date("Y-m-d H:i:s",strtotime("{$curr_y}-{$curr_m}-{$curr_d} {$r->work_start_time}"));
                                    $end_time = date("Y-m-d H:i:s",strtotime("{$curr_y}-{$curr_m}-{$curr_d} {$r->work_end_time}"));
                                    
                                    if($is_night_shift && date("H:i:s",strtotime($start_time)) <= date("H:i:s",strtotime($curr_dt))){
                                        $curr_dt = date("Y-m-d H:i:s",strtotime("{$curr_dt} -1 day"));
                                    }
                                    if($is_night_shift){
                                        $start_time = date("Y-m-d H:i:s",strtotime("{$start_time} -1 day"));
                                    }
                                    
                                    
                                    if($start_time != "" && $end_time != ""){
                                        $grace = " +{$r->latest_time_in_allowed} minutes";
                                        //echo date("H:i:s",strtotime($start_time."".$grace))."<=".date("H:i:s")."&&.".date("H:i:s")."<=".date("H:i:s",strtotime($end_time))."<br>";
                                        //if(strtotime($start_time."".$grace) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
                                        //echo date("Y-m-d H:i:s",strtotime($start_time."".$grace))." <= ";
                                        //echo date("Y-m-d H:i:s",strtotime($curr_dt))." && ";
                                        //echo date("Y-m-d H:i:s",strtotime($curr_dt))." <= ";
                                        //echo date("Y-m-d H:i:s",strtotime($end_time))."<br>";
                                        
                                        if(date("Y-m-d H:i:s",strtotime($start_time."".$grace)) <= date("Y-m-d H:i:s",strtotime($curr_dt)) && date("Y-m-d H:i:s",strtotime($curr_dt)) <= date("Y-m-d H:i:s",strtotime($end_time))){
                                            if(!check_employee_on_leave($row->emp_id)){
                                                //array_push($filter_emp_id, $row->emp_id);
                                                if($filter_work_schedule_id != ""){
                                                    if($work_schedule_id == $filter_work_schedule_id){
                                                        array_push($filter_emp_id, $row->emp_id);
                                                    }
                                                }
                                                else{
                                                    array_push($filter_emp_id, $row->emp_id);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
    
                            break;
                        case 'Flexible Hours':
                                
                            $w = array(
                                "not_required_login"=>0,
                                "company_id" => $company_id,
                                "work_schedule_id"=>$work_schedule_id
                            );
                            $this->db->where($w);
                            $this->db->where("latest_time_in_allowed IS NOT NULL");
                            $q = $this->db->get("flexible_hours");
                            $r = $q->row();
                                
                            if($r){
                                $emp_id = $row->emp_id;
                                $w = array(
                                        "emp_id" => $emp_id,
                                        "date" => date("Y-m-d"),
                                        "status" => "Active"
                                );
                                $this->db->where($w);
                                $time_in = $this->db->get("employee_time_in");
                                $time_in_row = $time_in->row();
    
                                if(!$time_in_row){
                                    $start_time = $r->latest_time_in_allowed;
                                    $end_time = date("H:i:s",strtotime($start_time." +{$r->total_hours_for_the_day} hours"));
                                    if($start_time != "" && $end_time != ""){
                                        if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
                                            if(!check_employee_on_leave($row->emp_id)){
                                                //array_push($filter_emp_id, $row->emp_id);
                                                if($filter_work_schedule_id != ""){
                                                    if($work_schedule_id == $filter_work_schedule_id){
                                                        array_push($filter_emp_id, $row->emp_id);
                                                    }
                                                }
                                                else{
                                                    array_push($filter_emp_id, $row->emp_id);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                                
                            break;
                                
                        case 'Workshift':
                                
                            $w = array(
                            "company_id" => $company_id,
                            "work_schedule_id"=>$work_schedule_id
                            );
                            $this->db->where($w);
                            $q = $this->db->get("schedule_blocks");
                            $r = $q->row();
    
                            if($r){
                                $emp_id = $row->emp_id;
                                $w = array(
                                        "emp_id" => $emp_id,
                                        "date" => date("Y-m-d"),
                                        "status" => "Active"
                                );
                                $this->db->where($w);
                                $time_in = $this->db->get("employee_time_in");
                                $time_in_row = $time_in->row();
    
                                if(!$time_in_row){
                                    $start_time = $r->start_time;
                                    $end_time = $r->end_time;
                                    if($start_time != "" && $end_time != ""){
                                        if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
                                            if(!check_employee_on_leave($row->emp_id)){
                                                //array_push($filter_emp_id, $row->emp_id);
                                                //array_push($filter_emp_id, $row->emp_id);
                                                if($filter_work_schedule_id != ""){
                                                    if($work_schedule_id == $filter_work_schedule_id){
                                                        array_push($filter_emp_id, $row->emp_id);
                                                    }
                                                }
                                                else{
                                                    array_push($filter_emp_id, $row->emp_id);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
    
                            break;
                    }
                }
    
            }
        }
        return ($filter_emp_id) ? $filter_emp_id : false;
    }
    
    /**
     * get all shifts
     * @param unknown $company_id
     */
    public function get_shifts($company_id){
        $sched = array();
        $where = array(
                "rs.company_id" => $company_id
        );
        $this->db->where($where);
        $this->db->join("work_schedule AS ws","ws.work_schedule_id = rs.work_schedule_id","INNER");
        $this->db->group_by("rs.work_schedule_id");
        $query = $this->db->get("regular_schedule AS rs");
        $result = $query->result();
    
        if($result){
            foreach ($result as $rs){
                $res = array(
                        "work_schedule_id" => $rs->work_schedule_id,
                        "name" => $rs->name,
                        "start_time" => $rs->work_start_time,
                        "end_time" => $rs->work_end_time
                );
                array_push($sched, (object) $res);
            }
        }
    
        $where2 = array(
                "fh.company_id" => $company_id
        );
        $this->db->where($where2);
        $this->db->join("work_schedule AS ws","ws.work_schedule_id = fh.work_schedule_id","INNER");
        $this->db->group_by("fh.work_schedule_id");
        $query2 = $this->db->get("flexible_hours AS fh");
        $result2 = $query2->result();
    
        if($result2){
            foreach ($result2 as $rs){
                $res = array(
                        "work_schedule_id" => $rs->work_schedule_id,
                        "name" => ($rs->name == "") ? "Flexi Time Shift - Default" : $rs->name,
                        "start_time" => $rs->latest_time_in_allowed,
                        "end_time" => date("H:i:s",strtotime($rs->latest_time_in_allowed." +".$rs->total_hours_for_the_day." hours"))
                );
                array_push($sched, (object) $res);
            }
        }
    
        $where3 = array(
            "sb.company_id" => $company_id
        );
        $this->db->where($where3);
        $this->db->join("work_schedule AS ws","ws.work_schedule_id = sb.work_schedule_id","INNER");
        $this->db->group_by("sb.work_schedule_id");
        $query3 = $this->db->get("schedule_blocks AS sb");
        $result3 = $query3->result();
    
        if($result3){
            foreach ($result3 as $rs){
                $res = array(
                    "work_schedule_id" => $rs->work_schedule_id,
                    "name" => $rs->block_name,
                    "start_time" => $rs->start_time,
                    "end_time" => $rs->end_time
                );
                array_push($sched, (object) $res);
            }
        }
    
        return (object) $sched;
    }
    
    /**
     * count number of days of employee absent in the current pay period
     * @param unknown $emp_id
     * @param unknown $company_id
     */
    public function employee_absent_count($emp_id, $company_id){
        $counter = 0;
        $now = date("Y-m-d");
        $yesterday = date("Y-m-d",strtotime("{$now} -1 day"));
        
        $where = array(
            "pc.company_id" => $company_id,
            "pc.cut_off_from <=" => $yesterday,
            "pc.cut_off_to >=" => $yesterday,
            "epi.emp_id" => $emp_id
        );
        
        $this->db->where($where);
        $this->db->join("payroll_group AS pg","pg.period_type = pc.pay_schedule","LEFT");
        $this->db->join("employee_payroll_information AS epi","epi.payroll_group_id = pg.payroll_group_id","LEFT");
        $this->db->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
        #$this->db->join("regular_schedule AS rs","rs.work_schedule_id = ws.work_schedule_id","LEFT");
        $this->db->select("* , pg.work_schedule_id AS work_sched_id");
        $query = $this->db->get("payroll_calendar AS pc");
        $pc_row = $query->row();
        
        if($pc_row){
            $from = $pc_row->cut_off_from;
            while (strtotime($from) < strtotime($now)){
                $w = array(
                    "emp_id" => $emp_id,
                    "date" => $from,
                    "status" => "Active"
                );
                $this->db->where($w);
                $time_in = $this->db->get("employee_time_in");
                $time_in_row = $time_in->row();
                
                if(!$time_in_row){
                    $custom_sched = $this->check_if_custom_schedule($emp_id, $from);
                    $work_schedule_id = ($custom_sched) ? $custom_sched->work_schedule_id : $pc_row->work_sched_id ;
                    $workday = get_workday($company_id, $work_schedule_id);
                    
                    $weekday = date('l',strtotime($from));
                    $rest_day = $this->get_rest_day($company_id, $work_schedule_id, $weekday);
                    $holiday = $this->get_holiday($company_id, $from);
                    
                    if($workday != FALSE){
                        if($workday->workday_type == "Flexible Hours"){
                            $w = array(
                                    "not_required_login" => 0,
                                    "company_id" => $company_id,
                                    "work_schedule_id" => $work_schedule_id
                            );
                            $this->db->where($w);
                            $this->db->where("latest_time_in_allowed IS NOT NULL");
                            $q = $this->db->get("flexible_hours");
                            $r = $q->row();
                            if($r){
                                if(!check_employee_on_leave($emp_id) && !$rest_day && !$holiday){
                                    $counter = $counter + 1;
                                }
                            }
                        }
                        else{
                            // regular schedules
                            if(!check_employee_on_leave($emp_id) && !$rest_day && !$holiday){
                                $counter = $counter + 1;
                                //echo "emp_id : {$emp_id} - ".date("Y-m-d",strtotime($start))."<br>";
                            }
                        }
                    }
                }
                $from = date ("Y-m-d", strtotime("{$from} +1 day"));
            }
        }
        return $counter;
    }
    
    function check_if_custom_schedule($emp_id, $date){
        $CI =& get_instance();
        $where = array(
                "emp_id" => $emp_id,
                "valid_from <=" => date("Y-m-d",strtotime($date)),
                "until >=" => date("Y-m-d",strtotime($date)),
                "payroll_group_id" => 0
        );
        $this->db->where($where);
        $query = $this->db->get("employee_shifts_schedule");
        $row = $query->row();
    
        return ($row) ? $row : false;
    }
    
    /**
     * check rest day (from : payrun_model.php)
     * @param unknown $company_id
     * @param unknown $work_schedule_id
     * @param unknown $weekday
     */
    public function get_rest_day($company_id, $work_schedule_id, $weekday){
        $where = array(
                'company_id' => $company_id,
                "work_schedule_id" => $work_schedule_id,
                'rest_day' => $weekday,
                'status' => 'Active'
        );
        $this->db->where($where);
        $q = $this->db->get('rest_day');
        $result = $q->row();
    
        return ($result) ? $result : false;
    }
    
    /**
     * check holiday (from : payrun_model.php)
     * @param unknown $company_id
     * @param unknown $date
     */
    public function get_holiday($company_id, $date){
        $where = array(
                'holiday.company_id' => $company_id,
                'MONTH(holiday.date)' => date("m",strtotime($date)),
                'DAY(holiday.date)'  => date("d",strtotime($date)),
                'holiday.status'  => 'Active'
        );
        $this->db->where($where);
        $this->db->join('hours_type','hours_type.hour_type_id = holiday.hour_type_id','left');
        $this->db->join('overtime_type','overtime_type.hour_type_id = holiday.hour_type_id','left');
        $q = $this->db->get('holiday');
        $r = $q->row();
        if($r){
            return ($r->date_type == "fixed" || ($r->date_type == "movable" && $r->date == $date)) ? $r : FALSE ;
        }
        else{
            return FALSE;
        }
    }
    
    /**
     *
     * get employee clocked in count
     * @param unknown $company_id
     * @param string $search
     * @return number
     */
    public function get_employee_clocked_in_count($company_id, $emp_id, $search = "", $filter){
        $konsum_key = konsum_key();
        $today = date("Y-m-d");
        $filter_emp_id = $this->filter_employee($company_id,$filter);
        $yesterday = date("Y-m-d",strtotime("{$today} -1 day"));
        
        $where = array(
            "eti.comp_id" => $company_id,
            "edrt.parent_emp_id" => $emp_id
        );
        if($search != "" && $search != "all"){
            $this->db->where("
                (convert(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') using latin1) LIKE '%".$search."%' OR
                convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$search."%')", NULL, FALSE);
        }
        $this->edb->where($where);
        $this->db->where("(eti.date = '{$today}' OR eti.date = '{$yesterday}') AND time_out IS NULL");
        $this->db->where_in("eti.employee_time_in_id",$filter_emp_id);
        $this->edb->join("employee AS e","e.emp_id = eti.emp_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
        $this->edb->join("regular_schedule AS rs","rs.work_schedule_id = ws.work_schedule_id","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");
        $this->db->group_by("eti.employee_time_in_id");
        $query = $this->edb->get("employee_time_in AS eti");
        $result = $query->result();
    
        return ($result) ? $query->num_rows() : 0;
    }
    
    public function filter_employee($company_id, $filter = array()){
        $arrs = array();
        $today = date("Y-m-d");
        $yesterday = date("Y-m-d",strtotime("{$today} -1 day"));
        $filter_work_sched_id = $filter["work_schedule_id"];
        
        $where = array(
            "comp_id" => $company_id
        );
        if($filter_work_sched_id != ""){
            $this->db->where(array("work_schedule_id" => $filter_work_sched_id));
        }
        $this->db->where("(date = '{$today}' OR date = '{$yesterday}') AND time_out IS NULL");
        $query = $this->db->get("employee_time_in");
        $result = $query->result();
        
        if($result){
            foreach ($result as $row){
                $work_schedule_id = $row->work_schedule_id;
                if($work_schedule_id != "" || $work_schedule_id != 0){
                    $workday = get_workday($company_id, $work_schedule_id);
                    
                    if($workday != FALSE){
                        if($workday->workday_type == "Uniform Working Days"){
                            if(strtotime($row->date) == strtotime($today)){
                                array_push($arrs, $row->employee_time_in_id);
                            }
                            else{
                                $w = array(
                                    "work_schedule_id" => $work_schedule_id,
                                    "days_of_work" => date("l",strtotime($row->date))
                                );
                                $this->db->where($w);
                                $query = $this->db->get("regular_schedule");
                                $r = $query->row();
                                if($r){
                                    if(date("A",strtotime($r->work_start_time)) == "PM" && date("A",strtotime($r->work_end_time)) == "AM"){
                                        if(strtotime(date("H:i:s")) <= strtotime($r->work_end_time)){
                                            array_push($arrs, $row->employee_time_in_id);
                                        }
                                    }
                                }
                            }
                        }
                        elseif ($workday->workday_type == "Flexible Hours"){
                            if(strtotime($row->date) == strtotime($today)){
                                array_push($arrs, $row->employee_time_in_id);
                            }
                            else{
                                $w = array(
                                    "work_schedule_id" => $work_schedule_id
                                );
                                $this->db->where($w);
                                $query = $this->db->get("flexible_hours");
                                $r = $query->row();
                                if($r){
                                    if($r->not_required_login != 1){
                                        $start_time = date("H:i:s",strtotime($r->latest_time_in_allowed));
                                        $end_time = date("H:i:s",strtotime("{$start_time} +{$r->total_hours_for_the_day} hours"));
                                        
                                        if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM"){
                                            if(strtotime(date("H:i:s")) <= strtotime($end_time)){
                                                array_push($arrs, $row->employee_time_in_id);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        elseif ($workday->workday_type == "Workshift"){
                            if(strtotime($row->date) == strtotime($today)){
                                array_push($arrs, $row->employee_time_in_id);
                            }
                            else{
                                $w = array(
                                    "work_schedule_id" => $work_schedule_id
                                );
                                $this->db->where($w);
                                $query = $this->db->get("schedule_blocks");
                                $r = $query->row();
                                if($r){
                                    $start_time = date("H:i:s",strtotime($r->start_time));
                                    $end_time = date("H:i:s",strtotime($r->end_time));
                        
                                    if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM"){
                                        if(strtotime(date("H:i:s")) <= strtotime($end_time)){
                                            array_push($arrs, $row->employee_time_in_id);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return (count($arrs) > 0) ? $arrs : array("");
    }
    
    /**
     * employee clocked in
     * @param unknown $company_id
     * @param string $search
     * @param string $sort_by
     * @param string $sort
     * @param unknown $filter
     * @return Ambigous <boolean, unknown>
     */
    public function get_employees_clocked_in($company_id, $emp_id, $search = "",$sort_by = "", $sort = "ASC", $filter){
        $konsum_key = konsum_key();
        $today = date("Y-m-d");
        $filter_emp_id = $this->filter_employee($company_id, $filter);
        $yesterday = date("Y-m-d",strtotime("{$today} -1 day"));
    
        $sort_array = array(
            "e.last_name",
            "d.department_name",
            "p.position_name"
        );
        $where = array(
            "eti.comp_id" => $company_id,
            "edrt.parent_emp_id" => $emp_id
        );
        if($search != "" && $search != "all"){
            $this->db->where("
                (convert(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') using latin1) LIKE '%".$search."%' OR 
                convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$search."%')", NULL, FALSE);
        }
        $this->edb->where($where);
        $this->db->where("(eti.date = '{$today}' OR eti.date = '{$yesterday}') AND time_out IS NULL");
        $this->edb->select(array("*"));
        $this->db->where_in("eti.employee_time_in_id",$filter_emp_id);
        $this->db->select(array("pg.payroll_group_id AS pg_id","eti.work_schedule_id AS ws_id"));
        $this->edb->join("employee AS e","e.emp_id = eti.emp_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("employee_shifts_schedule AS ess","ess.emp_id = e.emp_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        $this->db->group_by("eti.employee_time_in_id");
        if($sort_by != ""){
            if(in_array($sort_by, $sort_array)){
                if($sort_by == "e.last_name"){
                    $this->edb->order_by($sort_by,$sort);
                }
                else{
                    $this->db->order_by($sort_by,$sort);
                }
            }
        }
        else{
            $this->edb->order_by("e.last_name","ASC");
        }
        $query = $this->edb->get("employee_time_in AS eti");
        $result = $query->result();
    
        return ($result) ? $result : false;
    }
    
    public function get_upcoming_holiday($company_id){
        $arrs = array();
        // get all fixed holidays
        $where = array(
                "company_id" => $company_id,
                "date_type" => "fixed"
        );
        $this->db->where($where);
        $this->db->order_by("date","ASC");
        $query = $this->db->get("holiday");
        $result = $query->result();
    
        if($result){
            foreach($result as $row){
                $year = date("Y");
                $m = date("m",strtotime($row->date));
                $d = date("d",strtotime($row->date));
    
                if(intval($m) < intval(date("m")) || (intval($m) <= intval(date("m")) && intval($d) <= intval(date("d")))){
                    $year = $year + 1;
                }
                $md = date("m-d",strtotime($row->date));
                $holiday = array(
                        "date" => date("Y-m-d",strtotime("{$year}-{$md}")),
                        "name" => $row->holiday_name
                );
                array_push($arrs, (object) $holiday);
            }
        }
    
        // get all movable holidays
        $where2 = array(
                "company_id" => $company_id,
                "date_type" => "movable",
                "date >" => date("Y-m-d")
        );
        $this->db->where($where2);
        $this->db->order_by("date","ASC");
        $query2 = $this->db->get("holiday");
        $result2 = $query2->result();
    
        if($result2){
            foreach($result2 as $row){
                $year = date("Y");
                $md = date("m-d",strtotime($row->date));
                $holiday = array(
                        "date" => date("Y-m-d",strtotime("{$year}-{$md}")),
                        "name" => $row->holiday_name
                );
                array_push($arrs, (object) $holiday);
            }
        }
    
        // sort holidays
        usort($arrs, function($a, $b){
            return strcmp($a->date, $b->date);
        });
    
        return (count($arrs) > 0) ? $arrs[0] : false;
    }
    
    /**
     * Getting Active Added Work Schedule
     * @param unknown_type $company_id
     */
    public function work_schedule_select($comp_id){
        $w = array(
                "flag_custom"=>"0",
                "default"=>"1",
                "comp_id"=>$comp_id,
                "status"=>"Active"
        );
        $this->db->where($w);
        $this->db->order_by("work_schedule_id","ASC");
        $q = $this->db->get("work_schedule");
        $r = $q->result();
        return ($r) ? $r : FALSE;
    }
    
    public function suboption_workschedule($company_id, $limit, $start = 0) {
        $w = array(
                "flag_custom"=>"0",
                "comp_id"=>$company_id,
                "status"=>"Active"
        );
        $this->db->where($w);
        $this->db->order_by("work_type_name","ASC");
        $q = $this->db->get("work_schedule",$limit,$start);
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }
    
    public function cnt_suboption_workschedule($company_id,$limit = 10000) {
        $w = array(
                "flag_custom"=>"0",
                "comp_id"=>$company_id,
                "status"=>"Active"
        );
        $this->db->where($w);
        $this->db->order_by("work_type_name","ASC");
        $q = $this->db->get("work_schedule",$limit);
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }
    
    public function get_all_workschedule($work_sched_id,$company_id) {
        $w = array(
                "flag_custom"=>"0",
                "comp_id"=>$company_id,
                "status"=>"Active",
                "work_schedule_id" => $work_sched_id
        );
        $this->db->where($w);
        $this->db->order_by("work_type_name","ASC");
        $q = $this->db->get("work_schedule");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }
    
    public function count_sched_by_work_id($work_sched_id, $comp_id, $emp_id) {
        $where = array(
            'ess.work_schedule_id' => $work_sched_id,
            'ess.company_id' => $comp_id,
            'ess.payroll_group_id' => 0,
            "edrt.parent_emp_id" => $emp_id,
            "valid_from" => date('Y-m-d'),
            "until" => date('Y-m-d')
        );
        
        $this->db->where($where);
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = ess.emp_id","LEFT");
        $this->db->group_by('ess.emp_id');
        $q = $this->db->get("employee_shifts_schedule AS ess");
        $r = $q->result();
        
        return ($r) ? count($r) : 0 ;
    }
    
    public function count_sched_by_payroll_grp_id($emp_id,$company_id,$work_sched_id) {
    
        $employees = $this->get_all_employees($emp_id, $company_id);
    
        if($employees) {
            foreach ($employees AS $e) {
                $where = array(
                        'epi.emp_id' => $e->emp_id,
                        'epi.company_id' => $e->company_id,
                        'epi.employee_status' => 'Active',
                        "edrt.parent_emp_id" => $emp_id
                );
    
                $this->db->where($where);
                $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
                $q = $this->edb->get('employee_payroll_information AS epi');
                $r = $q->row();
    
                if($r->payroll_group_id != 0 || $r->payroll_group_id != NULL) {
                    $where1 = array(
                            'work_schedule_id' => $work_sched_id,
                            'company_id' => $r->company_id,
                            'status' => 'Active'
                    );
                        
                    $this->db->where($where1);
                    $q1 = $this->db->get('payroll_group');
                    $r1 = $q1->result();
                        
                    return ($r1) ? count($r1) : 0 ;
                }
            }
        }
    
    }
    
    public function included_shifts_ending_emp($emp_id, $company_id,$limit = 10000) {
        
        $manager_employee_default_schedule = manager_employee_default_schedule($company_id,$emp_id);
        $array_id = array();
        if($manager_employee_default_schedule) {
            foreach ($manager_employee_default_schedule as $man) {
                $new_emp_id = $man->emp_id;
                #$array_id[$new_emp_id] = $new_emp_id;
                array_push($array_id,$new_emp_id);
            }
        }

        $where = array(
                "edrt.parent_emp_id" => $emp_id,
                "e.company_id" => $company_id,
                "e.status" => "Active"
        );
    
        $this->edb->where($where);
        $this->db->where_not_in('e.emp_id', $array_id);
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $q = $this->edb->get("employee AS e",$limit);
        $r = $q->result();
        
        return ($r) ? $r : FALSE;
    }
    
    /**
     * Get Uniform Days for Working Days Listing
     * @param unknown_type $work_schedule
     * @param unknown_type $comp_id
     */
    public function get_regular_schedule($work_schedule,$comp_id){
        $w = array(
                "work_schedule_id"=>$work_schedule,
                "company_id"=>$comp_id,
                "status"=>"Active"
        );
        $this->db->where($w);
        $q = $this->db->get("regular_schedule");
        $r = $q->row();
        return ($r) ? $r : "" ;
    }
    
    /**
     * Get Uniform Days for Working Days Listing
     * @param unknown_type $workingday
     * @param unknown_type $work_schedule
     * @param unknown_type $comp_id
     */
    public function get_uw_list($working_day,$work_schedule,$comp_id){
        $w = array(
                "days_of_work"=>$working_day,
                "work_schedule_id"=>$work_schedule,
                "company_id"=>$comp_id,
                "status"=>"Active"
        );
        $this->db->where($w);
        $q = $this->db->get("regular_schedule");
        $r = $q->row();
        return ($r) ? $r : "" ;
    }
    
    /**
     * Retrieving an item from Schedule Blocks
     * @param unknown_type $work_id
     * @param unknown_type $company_id
     */
    public function split_schedule_blocks($work_id, $company_id) {
        $w = array(
                "work_schedule_id" => $work_id,
                "company_id" => $company_id,
        );
        $this->db->where($w);
        $q = $this->db->get("schedule_blocks");
        $r = $q->result();
    
        return ($r) ? $r : FALSE ;
    }
    
    /**
     * Check Flexible Hours
     * @param unknown_type $work_schedule
     * @param unknown_type $comp_id
     */
    public function check_flx_info($work_schedule,$comp_id){
        $w = array(
                "work_schedule_id"=>$work_schedule,
                "company_id"=>$comp_id
        );
        $this->db->where($w);
        $q = $this->db->get("flexible_hours");
        $r = $q->row();
        return ($r) ? $r : FALSE ;
    }
    
    /**
     * Retrieving an item from Work Schedule
     * @param unknown_type $work_id
     * @param unknown_type $company_id
     */
    public function work_schedule_info($work_id, $company_id) {
        $w = array(
                "work_schedule_id" => $work_id,
                "comp_id" => $company_id,
        );
        $this->db->where($w);
        $q = $this->db->get("work_schedule");
        $r = $q->row();
    
        return ($r) ? $r : FALSE ;
    }
    
    /**
     * undertime counter
     * @param unknown $company_id
     */
    public function count_clocked_in($company_id, $emp_id, $work_schedule_id){
        $counter = 0;
    
        $get_date = $this->input->get("date");
        $get_time = $this->input->get("time");
    
        $current_time = ($get_time == "") ? date("H:i:s") : date("H:i:s",strtotime($get_time));
        $today = ($get_date == "") ? date("Y-m-d") : date("Y-m-d",strtotime($get_date));
        $yesterday = date("Y-m-d",strtotime("{$today} -1 day"));
    
        $where = array(
            "eti.comp_id" => $company_id,
            "eti.work_schedule_id" => $work_schedule_id,
            "edrt.parent_emp_id" => $emp_id
        );
    
        $this->db->where($where);
        $this->db->where("(eti.date = '{$today}' OR eti.date = '{$yesterday}') AND eti.time_out IS NULL");
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");
        $query = $this->db->get("employee_time_in AS eti");
        $result = $query->result();
        
        if($result){
            foreach ($result as $row){
                $work_schedule_id = $row->work_schedule_id;
                if($work_schedule_id != "" || $work_schedule_id != 0){
                    $workday = get_workday($company_id, $work_schedule_id);
    
                    if($workday != FALSE){
                        if($workday->workday_type == "Uniform Working Days"){
                            $w = array(
                                "work_schedule_id" => $work_schedule_id,
                                "days_of_work" => date("l",strtotime($row->date))
                            );
                            $this->db->where($w);
                            $query = $this->db->get("regular_schedule");
                            $r = $query->row();
                            if($r){
                                // same date
                                if(strtotime($row->date) == strtotime($today)){
                                    if(date("A",strtotime($r->work_end_time)) == "PM") {
                                        // ends this day
                                        $end_time = date("Y-m-d H:i:s",strtotime("{$today} {$r->work_end_time}"));
                                    }
                                    else{
                                        // ends next day
                                        $end_time = date("Y-m-d H:i:s",strtotime("{$today} {$r->work_end_time} +1 day"));
                                    }
    
                                    if(date("Y-m-d H:i:s",strtotime("{$today} {$current_time}")) <= $end_time){
                                        $counter = $counter + 1;
                                    }
                                }
                                    
                                // next day 12:01AM
                                if(date("A",strtotime($r->work_start_time)) == "PM" && date("A",strtotime($r->work_end_time)) == "AM" && date("A",strtotime($current_time)) == "AM"){
                                    if(strtotime($current_time) <= strtotime($r->work_end_time)){
                                        $counter = $counter + 1;
                                    }
                                }
                            }
                        }
                        elseif ($workday->workday_type == "Flexible Hours"){
                            $w = array(
                                "work_schedule_id" => $work_schedule_id
                            );
                            $this->db->where($w);
                            $query = $this->db->get("flexible_hours");
                            $r = $query->row();
                            if($r){
                                if($r->not_required_login != 1){
                                    $hours_per_day = $r->total_hours_for_the_day;
                                    $hours = floor($hours_per_day);
                                    $mins = round(60 * ($hours_per_day - $hours));
    
                                    $start_time = date("H:i:s",strtotime($r->latest_time_in_allowed));
                                    $end_time = date("H:i:s",strtotime("{$start_time} +{$hours} hours +{$mins} minutes"));
    
                                    // same date
                                    if(strtotime($row->date) == strtotime($today)){
                                        if(date("A",strtotime($end_time)) == "PM") {
                                            // ends this day
                                            $end_time = date("Y-m-d H:i:s",strtotime("{$today} {$end_time}"));
                                        }
                                        else{
                                            // ends next day
                                            $end_time = date("Y-m-d H:i:s",strtotime("{$today} {$end_time} +1 day"));
                                        }
                                            
                                        if(date("Y-m-d H:i:s",strtotime("{$today} {$current_time}")) <= $end_time){
                                            $counter = $counter + 1;
                                        }
                                    }
    
                                    // next day 12:01AM
                                    if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM" && date("A",strtotime($current_time)) == "AM"){
                                        if(strtotime($current_time) <= strtotime($end_time)){
                                            $counter = $counter + 1;
                                        }
                                    }
                                }
                            }
                        }
                        elseif ($workday->workday_type == "Workshift"){
                            $w = array(
                                "work_schedule_id" => $work_schedule_id
                            );
                            $this->db->where($w);
                            $query = $this->db->get("schedule_blocks");
                            $r = $query->row();
                            if($r){
                                $start_time = date("H:i:s",strtotime($r->start_time));
                                $end_time = date("H:i:s",strtotime($r->end_time));
                                    
                                // same date
                                if(strtotime($row->date) == strtotime($today)){
                                    if(date("A",strtotime($end_time)) == "PM") {
                                        $end_time = date("Y-m-d H:i:s",strtotime($end_time." -1 day"));
                                    }
                                    else{
                                        $end_time = date("Y-m-d H:i:s",strtotime($end_time));
                                    }
                                        
                                    if(date("Y-m-d H:i:s",strtotime("{$today} {$current_time}")) <= $end_time){
                                        $counter = $counter + 1;
                                    }
                                }
                                    
                                // next day 12:01AM
                                if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM" && date("A",strtotime($current_time)) == "AM"){
                                    if(strtotime($current_time) <= strtotime($end_time)){
                                        $counter = $counter + 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $counter;
    }
    
    /**
     * no show counter
     */
    public function count_no_show($company_id, $emp_id, $work_schedule_id){
        $counter = 0;
        $filter_work_schedule_id = $work_schedule_id;
        $today = date("Y-m-d");
        
        $where = array(
            "e.company_id" => $company_id,
            "e.status" => "Active",
            "epi.employee_status" => "Active",
            "epi.timesheet_required" => "yes",
            "epi.status" => "Active",
            "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($where);
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        $this->db->group_by("e.emp_id");
        $query = $this->edb->get("employee AS e");
        $result = $query->result();
        
        if($result){
            foreach($result as $row){
                $emp_id = $row->emp_id;
                $custom_sched = check_employee_custom_schedule($row->emp_id);
                $work_schedule_id = ($custom_sched) ? $custom_sched->work_schedule_id : $row->work_schedule_id;
                $workday = get_workday($company_id, $work_schedule_id);
        
                if($workday != FALSE){
                    if($workday->workday_type == "Uniform Working Days"){
                        $w = array(
                            "company_id" => $company_id,
                            "work_schedule_id" => $work_schedule_id,
                            "days_of_work" => date("l")
                        );
                        $this->db->where("((work_start_time <= '".date("H:i:s")."' AND work_end_time >= '".date("H:i:s")."')
                                OR
                                (
                                    (DATE_FORMAT(work_start_time, '".date("Y-m-d")." %H:%i:%s') <= '".date("Y-m-d H:i:s")."' AND DATE_FORMAT(work_end_time, '".date("Y-m-d",strtotime("+1 day"))." %H:%i:%s') >= '".date("Y-m-d H:i:s")."')
                                    OR
                                    (DATE_FORMAT(work_start_time, '".date("Y-m-d",strtotime("-1 day"))." %H:%i:%s') <= '".date("Y-m-d H:i:s")."' AND DATE_FORMAT(work_end_time, '".date("Y-m-d")." %H:%i:%s') >= '".date("Y-m-d H:i:s")."')
                                )
                            )");
                        $this->db->where($w);
                        $query = $this->db->get("regular_schedule");
                        $r = $query->row();
                        if($r){
                            $date = $today;
                            $is_night_shift = false;
                            if(date("A", strtotime($r->work_start_time)) == "PM" && date("A", strtotime($r->work_end_time)) == "AM"){
                                $date = date("Y-m-d",strtotime("{$today} -1 day"));
                                $is_night_shift = true;
                            }
                            $w = array(
                                "emp_id" => $emp_id,
                                "date" => $date,
                                "status" => "Active"
                            );
                            $this->db->where($w);
                            $time_in = $this->db->get("employee_time_in");
                            $time_in_row = $time_in->row();
                            if(!$time_in_row){
                                $curr_y = date("Y");
                                $curr_m = date("m");
                                $curr_d = date("d");
                                $curr_dt = date("Y-m-d H:i:s");
                                $start_time = date("Y-m-d H:i:s",strtotime("{$curr_y}-{$curr_m}-{$curr_d} {$r->work_start_time}"));
                                $end_time = date("Y-m-d H:i:s",strtotime("{$curr_y}-{$curr_m}-{$curr_d} {$r->work_end_time}"));
                        
                                if($is_night_shift && date("H:i:s",strtotime($start_time)) <= date("H:i:s",strtotime($curr_dt))){
                                    $curr_dt = date("Y-m-d H:i:s",strtotime("{$curr_dt} -1 day"));
                                }
                                if($is_night_shift){
                                    $start_time = date("Y-m-d H:i:s",strtotime("{$start_time} -1 day"));
                                }
                        
                        
                                if($start_time != "" && $end_time != ""){
                                    $grace = " +{$r->latest_time_in_allowed} minutes";
                                    if(date("Y-m-d H:i:s",strtotime($start_time."".$grace)) <= date("Y-m-d H:i:s",strtotime($curr_dt)) && date("Y-m-d H:i:s",strtotime($curr_dt)) <= date("Y-m-d H:i:s",strtotime($end_time))){
                                        if(!check_employee_on_leave($row->emp_id)){
                                            if($filter_work_schedule_id != ""){
                                                if($work_schedule_id == $filter_work_schedule_id){
                                                    $counter = $counter + 1;
                                                }
                                            }
                                            else{
                                                $counter = $counter + 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    else if($workday->workday_type == "Flexible Hours"){
                        $w = array(
                            "not_required_login"=>0,
                            "company_id" => $company_id,
                            "work_schedule_id"=>$work_schedule_id
                        );
                        $this->db->where($w);
                        $this->db->where("latest_time_in_allowed IS NOT NULL");
                        $q = $this->db->get("flexible_hours");
                        $r = $q->row();
                        
                        if($r){
                            $emp_id = $row->emp_id;
                            $w = array(
                                "emp_id" => $emp_id,
                                "date" => date("Y-m-d"),
                                "status" => "Active"
                            );
                            $this->db->where($w);
                            $time_in = $this->db->get("employee_time_in");
                            $time_in_row = $time_in->row();
                        
                            if(!$time_in_row){
                                $hours_per_day = $r->total_hours_for_the_day;
                                $hours = floor($hours_per_day);
                                $mins = round(60 * ($hours_per_day - $hours));
                        
                                $start_time = $r->latest_time_in_allowed;
                                $end_time = date("H:i:s",strtotime($start_time." +{$hours} hours +{$mins} minutes"));
                                if($start_time != "" && $end_time != ""){
                                    if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
                                        if(!check_employee_on_leave($row->emp_id)){
                                            if($filter_work_schedule_id != ""){
                                                if($work_schedule_id == $filter_work_schedule_id){
                                                    $counter = $counter + 1;
                                                }
                                            }
                                            else{
                                                $counter = $counter + 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    else if($workday->workday_type == "Workshift"){
                        $w = array(
                            "company_id" => $company_id,
                            "work_schedule_id" => $work_schedule_id
                        );
                        $this->db->where($w);
                        $q = $this->db->get("schedule_blocks");
                        $r = $q->row();
                        
                        if($r){
                            $emp_id = $row->emp_id;
                            $w = array(
                                "emp_id" => $emp_id,
                                "date" => date("Y-m-d"),
                                "status" => "Active"
                            );
                            $this->db->where($w);
                            $time_in = $this->db->get("employee_time_in");
                            $time_in_row = $time_in->row();
                        
                            if(!$time_in_row){
                                $start_time = $r->start_time;
                                $end_time = $r->end_time;
                                if($start_time != "" && $end_time != ""){
                                    if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
                                        if(!check_employee_on_leave($row->emp_id)){
                                            if($filter_work_schedule_id != ""){
                                                if($work_schedule_id == $filter_work_schedule_id){
                                                    $counter = $counter + 1;
                                                }
                                            }
                                            else{
                                                $counter = $counter + 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
        
            }
        }
        return $counter;
    }
    
    public function get_date_for_missed_punch($work_sched_id, $company_id) {

        $w = array(
                "work_schedule_id" => $work_sched_id,
                "company_id" => $company_id,
                "status"=> "Active",
                "valid_from <" => date('Y-m-d')
        );
        
        $this->db->where($w);
        $q1 = $this->db->get("employee_shifts_schedule");
        $r1 = $q1->result();
        
        if($r1) {
            return $r1;
        } else {
            $w1 = array(
                    "work_schedule_id" => $work_sched_id,
                    "company_id" => $company_id,
                    "status"=> "Active",
            );
            
            $this->db->where($w1);
            $q = $this->db->get("rest_day");
            $r = $q->result();
            
            if($r) {
                foreach ($r as $row) {
                    
                }           
            }
            
        }
    }
    
    public function get_schedule_for_tile($emp_id,$company_id) {
        $result = array();
        $where = array(
                "ess.company_id" => $company_id,
                "ess.status" => "Active",
                "ess.valid_from" => date('Y-m-d'),
                "ess.until" => date('Y-m-d'),
                "edrt.parent_emp_id" => $emp_id,
                "ess.payroll_group_id" => 0
        );
        
        $this->db->where($where);
        $this->db->group_by("ess.work_schedule_id");
        $this->db->join("work_schedule AS ws","ws.work_schedule_id = ess.work_schedule_id","LEFT");
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = ess.emp_id","LEFT");
        $q = $this->db->get("employee_shifts_schedule AS ess");
        $r = $q->result();
        
        
        
        if($r) {
            /*foreach ($r as $zzz) {
                $temp_array = array(
                        "work_schedule_id" => $zzz->work_schedule_id,
                        "emp_id" => $zzz->emp_id
                );
                
                array_push($result, $temp_array);
            }*/
            return ($r) ? $r : FALSE;
        } else {
            $emp_where = array(
                    "epi.company_id" => $company_id,
                    "epi.status" => "Active",
                    'edrt.parent_emp_id' => $emp_id
            );
            $this->db->where($emp_where);
            $this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
            $this->db->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
            $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
            $this->db->group_by("pg.work_schedule_id");
            $query = $this->db->get("employee_payroll_information AS epi");
            $row = $query->result();
            
            if($row) {
                foreach ($r as $zz) {
                    $temp_array = array(
                            "work_schedule_id" => $zz->work_schedule_id,
                            "emp_id" => $zz->emp_id
                    );
                
                    array_push($result, $temp_array);
                }
            }
            return ($row) ? $row : FALSE;
        }
        
        
        #return ($result) ? $result : FALSE;
        
        
    }
    
    /**
     * get work schedule names and format
     * @param unknown $company_id
     * @param unknown $category_id
     */
    public function get_work_names($company_id, $category_id, $name = ""){
        $work_schedule_name = "";
        $w = array(
            "work_schedule_id" => $category_id,
            "comp_id" => $company_id
        );
        $this->db->where($w);
        $query = $this->db->get("work_schedule");
        $row = $query->row();
    
        if($row){
            if($row->name == "Compressed Work Schedule"){
                $work_schedule_name = "CW - {$name}";
            }
            elseif ($row->name == "Regular Work Schedule"){
                $work_schedule_name = "RW - {$name}";
            }
            elseif ($row->name == "Flexi Time Schedule"){
                $work_schedule_name = "FT - {$name}";
            }
            elseif ($row->name == "Night Shift Schedule"){
                $work_schedule_name = "NS - {$name}";
            }
            elseif ($row->name == "Split Shift"){
                $work_schedule_name = "SS - {$name}";
            }
        }
    
        return $work_schedule_name;
    }
    
    /**
     * get out of office
     * @param unknown $company_id
     */
    public function out_of_office($company_id,$emp_id){
        $now = date("Y-m-d");
        $where = array(
                "ela.company_id" => $company_id,
                "ela.leave_application_status" => "approve",
                "ela.status" => "Active",
                "DATE(ela.date_start) <=" => $now,
                "DATE(ela.date_end) >=" => $now,
                "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($where);
        $this->edb->join("leave_type AS lt","lt.leave_type_id = ela.leave_type_id","LEFT");
        $this->edb->join("employee AS e","e.emp_id = ela.emp_id","LEFT");
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
        $query = $this->edb->get("employee_leaves_application AS ela");
        $result = $query->result();
    
        return ($result) ? array($result, $query->num_rows()) : array(false, 0);
    }
    
    public function get_employee_on_leave_count($company_id,$emp_id,$search = ""){
        $konsum_key = konsum_key();
        $where = array(
                "ela.company_id" => $company_id,
                "ela.leave_application_status" => "approve",
                "ela.status" => "Active",
                "DATE(ela.date_start) <=" => date("Y-m-d"),
                "DATE(ela.date_end) >=" => date("Y-m-d"),
                "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($where);
        if($search != "" && $search != "all"){
            $this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
        }
        $this->db->where("ela.flag_parent IS NOT NULL");
        $this->edb->join("employee AS e","e.emp_id = ela.emp_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("leave_type AS lt","lt.leave_type_id = ela.leave_type_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
        $query = $this->edb->get("employee_leaves_application AS ela");
        $result = $query->result();
    
        return ($result) ? $query->num_rows() : false;
    }
    
    public function get_employee_on_leave($company_id,$emp_id,$limit = 10,$start = 0,$search = "",$sort_by = "", $sort = "ASC"){
        $konsum_key = konsum_key();
        $sort_array = array(
                "e.last_name",
                "d.department_name",
                "p.position_name",
                "ela.date_start",
                "ela.date_end",
                "lt.leave_type"
        );
        $where = array(
                "ela.company_id" => $company_id,
                "ela.leave_application_status" => "approve",
                "ela.status" => "Active",
                "DATE(ela.date_start) <=" => date("Y-m-d"),
                "DATE(ela.date_end) >=" => date("Y-m-d"),
                "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($where);
        if($search != "" && $search != "all"){
            $this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
        }
        $this->db->where("ela.flag_parent IS NOT NULL");
        $this->edb->join("employee AS e","e.emp_id = ela.emp_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("leave_type AS lt","lt.leave_type_id = ela.leave_type_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
        if($sort_by != ""){
            if(in_array($sort_by, $sort_array)){
                if($sort_by == "e.last_name"){
                    $this->edb->order_by($sort_by,$sort);
                }
                else{
                    $this->db->order_by($sort_by,$sort);
                }
            }
        }
        else{
            $this->edb->order_by("e.last_name","ASC");
        }
        $query = $this->edb->get("employee_leaves_application AS ela",$limit,$start);
        $result = $query->result();
    
        return ($result) ? $result : false;
    }
    
    // optimization
    
    public function get_employee_headcount_v2($company_id,$emp_id){
        $emp_where = array(
                "epi.company_id" => $company_id,
                "epi.status" => "Active",
                'edrt.parent_emp_id'    => $emp_id
        );
        $this->db->select('count(*) AS total_head');
        $this->db->where($emp_where);
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
        $query = $this->db->get("employee_payroll_information AS epi");
        $row = $query->row();
        
        return ($row) ? $row->total_head : 0;
    }
    
    public function get_schedule_for_tile_v2($emp_id,$company_id) {
        $result = array();
        $s = array(
                'ws.work_type_name',
                'ws.work_schedule_id',
                'ws.name'
        );
        $where = array(
                "ess.company_id" => $company_id,
                "ess.status" => "Active",
                "ess.valid_from" => date('Y-m-d'),
                "ess.until" => date('Y-m-d'),
                "edrt.parent_emp_id" => $emp_id,
                "ess.payroll_group_id" => 0
        );
        $this->db->select($s);
        $this->db->where($where);
        $this->db->group_by("ess.work_schedule_id");
        $this->db->join("work_schedule AS ws","ws.work_schedule_id = ess.work_schedule_id","LEFT");
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = ess.emp_id","LEFT");
        $q = $this->db->get("employee_shifts_schedule AS ess");
        $r = $q->result();
        
        if($r) {
            return ($r) ? $r : FALSE;
        } else {
            $emp_where = array(
                    "epi.company_id" => $company_id,
                    "epi.status" => "Active",
                    'edrt.parent_emp_id' => $emp_id
            );
            $this->db->where($emp_where);
            $this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
            $this->db->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
            $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
            $this->db->group_by("pg.work_schedule_id");
            $query = $this->db->get("employee_payroll_information AS epi");
            $row = $query->result();
                
            if($row) {
                foreach ($r as $zz) {
                    $temp_array = array(
                            "work_schedule_id" => $zz->work_schedule_id,
                            "emp_id" => $zz->emp_id
                    );
    
                    array_push($result, $temp_array);
                }
            }
            return ($row) ? $row : FALSE;
        }
    
    }
    
    public function get_current_uniform_sched($company_id) {
        $res = array();
        
        $s = array(
                'work_start_time',
                'work_end_time',
                'work_schedule_id',
                'days_of_work',         
                'company_id',
        );
        
        $w = array(
                "company_id" => $company_id,
        );
        
        $this->db->where($w);
        $q = $this->db->get("regular_schedule");
        $r = $q->result();
        
        if($r){
            foreach ($r as $row) {
                $temp = array(
                        "work_start_time" => $row->work_start_time,
                        "work_end_time" => $row->work_end_time,
                        "work_schedule_id" => $row->work_schedule_id,
                        "days_of_work" => $row->days_of_work,
                        "company_id" => $row->company_id,
                        "filter_query" => "work_schedule_id-{$row->work_schedule_id}{$row->days_of_work}"
                );
        
                array_push($res, $temp);
            }
            return $res;
        }
    }
    
    public function get_current_flex_sched($company_id) {
        $res = array();
        
        $this->db->select("*,latest_time_in_allowed AS start_time, ADDTIME(latest_time_in_allowed,CONCAT(total_hours_for_the_day,':00:00')) AS end_time",FALSE);
        
        $w = array(
                "company_id" => $company_id,
                "not_required_login" => "0",
        );
        $this->db->where($w);
        
        $q = $this->db->get("flexible_hours");
        $r = $q->result();
        
        if($r){
            foreach ($r as $row) {
                $temp = array(
                        "total_hours_for_the_day" => $row->total_hours_for_the_day,
                        "latest_time_in_allowed" => $row->latest_time_in_allowed,
                        "work_schedule_id" => $row->work_schedule_id,
                        "company_id" => $row->company_id,
                        "not_required_login" => $row->not_required_login,
                        "filter_query" => "work_schedule_id-{$row->work_schedule_id}"
                );
        
                array_push($res, $temp);
            }
            return $res;
        }
    }
    
    public function get_current_schedule_v2($emp_id,$company_id,$get_current_uniform_sched,$get_current_flex_sched){
        $schedule = array();
        $result = $this->get_schedule_for_tile_v2($emp_id,$company_id);
        
        if($result){
            foreach ($result as $row){
                $work_type = $row->work_type_name;
                $work_schedule_id = $row->work_schedule_id;
                $work_schedule_name = $row->name;
                switch ($work_type){
                    case "Uniform Working Days" :
                        $days_of_work = date("l");
                        
                        $r = in_array_foreach_custom("work_schedule_id-{$work_schedule_id}{$days_of_work}", $get_current_uniform_sched);
                        
                        if($r){
                            foreach ($r as $reg){
                                $from_date = date("Y-m-d");
                                $to_date = date("Y-m-d");
                                    
                                if(date("A", strtotime($reg->work_start_time)) == "PM" && date("A", strtotime($reg->work_end_time)) == "AM" && date("A") == "AM"){
                                    $from_date = date("Y-m-d",strtotime("-1 day"));
                                }
                                
                                $start = date("Y-m-d H:i:s",strtotime("{$from_date} {$reg->work_start_time}"));
                                $end = date("Y-m-d H:i:s",strtotime("{$to_date} {$reg->work_end_time}"));
                                
                                $ars = array(
                                    "name" => $work_schedule_name,
                                    "start" => $start,
                                    "end" => $end,
                                    "work_schedule_id" => $work_schedule_id
                                );
                                if(date("Y-m-d H:i:s",strtotime($ars["start"])) <= date("Y-m-d H:i:s")){
                                    array_push($schedule, (object) $ars);
                                }
                            }
                        }
                        
                    break;
                    case "Flexible Hours" :
                        
                        $r = in_array_foreach_custom("work_schedule_id-{$work_schedule_id}", $get_current_flex_sched);
                        
                        if($r){
                            
                            foreach ($r as $flex){
                                $from_date = date("Y-m-d");
                                $to_date = date("Y-m-d");
                                
                                $n_end_tal = $flex->total_hours_for_the_day;
                                $n_hour = floor($n_end_tal);
                                $n_min = round(60*($n_end_tal-$n_hour));
                                
                                $start = date("Y-m-d H:i:s",strtotime("{$from_date} {$flex->latest_time_in_allowed}"));
                                $time_end = date("H:i:s",strtotime("{$start} +{$n_hour} hours +{$n_min} minute"));
                                
                                if(date("A", strtotime($start)) == "PM" && date("A", strtotime($time_end)) == "AM" && date("A") == "AM"){
                                    $from_date = date("Y-m-d",strtotime("-1 day"));
                                    $start = date("Y-m-d H:i:s",strtotime("{$from_date} {$flex->latest_time_in_allowed}"));
                                }
                                
                                $end = date("Y-m-d H:i:s",strtotime("{$to_date} {$time_end}"));
                                $ars = array(
                                    "name" => $work_schedule_name,
                                    "start" => $start,
                                    "end" => $end,
                                    "work_schedule_id" => $work_schedule_id
                                );
                                if(date("Y-m-d h:i:s") < $end){
                                    if(date("Y-m-d H:i:s",strtotime($ars["start"])) <= date("Y-m-d H:i:s")){
                                        array_push($schedule, (object) $ars);
                                    }
                                }
                            }
                        }
                        
                    break;
                    case "Workshift" :
                        
                        
                    break;
                }
            }
        }
        
        // sort to get closest start time
        usort($schedule, function($a, $b){
            return strcmp($b->start, $a->start);
        });
        
        return (count($schedule) > 0) ? $schedule[0] : false;
    }
    
    public function count_clocked_in_v2($company_id, $emp_id, $work_schedule_id,$get_workday_v2,$get_current_uniform_sched,$get_current_flex_sched){
        $counter = 0;
        
        $get_date = $this->input->get("date");
        $get_time = $this->input->get("time");
    
        $current_time = ($get_time == "") ? date("H:i:s") : date("H:i:s",strtotime($get_time));
        $today = ($get_date == "") ? date("Y-m-d") : date("Y-m-d",strtotime($get_date));
        $yesterday = date("Y-m-d",strtotime("{$today} -1 day"));
    
        $where = array(
            "eti.comp_id" => $company_id,
            "eti.work_schedule_id" => $work_schedule_id,
            "edrt.parent_emp_id" => $emp_id
        );
    
        $this->db->where($where);
        $this->db->where("(eti.date = '{$today}' OR eti.date = '{$yesterday}') AND eti.time_out IS NULL");
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");
        $query = $this->db->get("employee_time_in AS eti");
        $result = $query->result();
        
        if($result){
            foreach ($result as $row){
                $work_schedule_id = $row->work_schedule_id;
                if($work_schedule_id != "" || $work_schedule_id != 0){
                    $workday = in_array_custom("work_schedule_id-{$work_schedule_id}", $get_workday_v2); //get_workday($company_id, $work_schedule_id);
    
                    if($workday != FALSE){
                        if($workday->workday_type == "Uniform Working Days"){
                            $days_of_work = date("l",strtotime($row->date));
                            $r = in_array_custom("work_schedule_id-{$row->work_schedule_id}{$days_of_work}", $get_current_uniform_sched);
                            
                            if($r){
                                // same date
                                if(strtotime($row->date) == strtotime($today)){
                                    if(date("A",strtotime($r->work_end_time)) == "PM") {
                                        // ends this day
                                        $end_time = date("Y-m-d H:i:s",strtotime("{$today} {$r->work_end_time}"));
                                    }
                                    else{
                                        // ends next day
                                        $end_time = date("Y-m-d H:i:s",strtotime("{$today} {$r->work_end_time} +1 day"));
                                    }
    
                                    if(date("Y-m-d H:i:s",strtotime("{$today} {$current_time}")) <= $end_time){
                                        $counter = $counter + 1;
                                    }
                                }
                                    
                                // next day 12:01AM
                                if(date("A",strtotime($r->work_start_time)) == "PM" && date("A",strtotime($r->work_end_time)) == "AM" && date("A",strtotime($current_time)) == "AM"){
                                    if(strtotime($current_time) <= strtotime($r->work_end_time)){
                                        $counter = $counter + 1;
                                    }
                                }
                            }
                        }
                        elseif ($workday->workday_type == "Flexible Hours"){
                            $r = in_array_custom("work_schedule_id-{$work_schedule_id}", $get_current_flex_sched);
                            
                            if($r){
                                if($r->not_required_login != 1){
                                    $hours_per_day = $r->total_hours_for_the_day;
                                    $hours = floor($hours_per_day);
                                    $mins = round(60 * ($hours_per_day - $hours));
    
                                    $start_time = date("H:i:s",strtotime($r->latest_time_in_allowed));
                                    $end_time = date("H:i:s",strtotime("{$start_time} +{$hours} hours +{$mins} minutes"));
    
                                    // same date
                                    if(strtotime($row->date) == strtotime($today)){
                                        if(date("A",strtotime($end_time)) == "PM") {
                                            // ends this day
                                            $end_time = date("Y-m-d H:i:s",strtotime("{$today} {$end_time}"));
                                        }
                                        else{
                                            // ends next day
                                            $end_time = date("Y-m-d H:i:s",strtotime("{$today} {$end_time} +1 day"));
                                        }
                                            
                                        if(date("Y-m-d H:i:s",strtotime("{$today} {$current_time}")) <= $end_time){
                                            $counter = $counter + 1;
                                        }
                                    }
    
                                    // next day 12:01AM
                                    if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM" && date("A",strtotime($current_time)) == "AM"){
                                        if(strtotime($current_time) <= strtotime($end_time)){
                                            $counter = $counter + 1;
                                        }
                                    }
                                }
                            }
                        }
                        elseif ($workday->workday_type == "Workshift"){
                            $w = array(
                                "work_schedule_id" => $work_schedule_id
                            );
                            $this->db->where($w);
                            $query = $this->db->get("schedule_blocks");
                            $r = $query->row();
                            if($r){
                                $start_time = date("H:i:s",strtotime($r->start_time));
                                $end_time = date("H:i:s",strtotime($r->end_time));
                                    
                                // same date
                                if(strtotime($row->date) == strtotime($today)){
                                    if(date("A",strtotime($end_time)) == "PM") {
                                        $end_time = date("Y-m-d H:i:s",strtotime($end_time." -1 day"));
                                    }
                                    else{
                                        $end_time = date("Y-m-d H:i:s",strtotime($end_time));
                                    }
                                        
                                    if(date("Y-m-d H:i:s",strtotime("{$today} {$current_time}")) <= $end_time){
                                        $counter = $counter + 1;
                                    }
                                }
                                    
                                // next day 12:01AM
                                if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM" && date("A",strtotime($current_time)) == "AM"){
                                    if(strtotime($current_time) <= strtotime($end_time)){
                                        $counter = $counter + 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $counter;
    }
    
    public function count_no_show_for_uniform($company_id) {
        $res = array();
        
        $s = array(
                "work_start_time",
                "work_end_time",
                "company_id",
                "days_of_work",
                "work_schedule_id",
                "latest_time_in_allowed"
        );
        
        $w = array(
                "company_id" => $company_id,
                "days_of_work" => date("l")
        );
        $this->db->where("((work_start_time <= '".date("H:i:s")."' AND work_end_time >= '".date("H:i:s")."')
                                OR
                                (
                                    (DATE_FORMAT(work_start_time, '".date("Y-m-d")." %H:%i:%s') <= '".date("Y-m-d H:i:s")."' AND DATE_FORMAT(work_end_time, '".date("Y-m-d",strtotime("+1 day"))." %H:%i:%s') >= '".date("Y-m-d H:i:s")."')
                                    OR
                                    (DATE_FORMAT(work_start_time, '".date("Y-m-d",strtotime("-1 day"))." %H:%i:%s') <= '".date("Y-m-d H:i:s")."' AND DATE_FORMAT(work_end_time, '".date("Y-m-d")." %H:%i:%s') >= '".date("Y-m-d H:i:s")."')
                                )
                            )");
        $this->db->select($s);
        $this->db->where($w);
        $query = $this->db->get("regular_schedule");
        $r = $query->result();
        
        if($r){
            foreach ($r as $row) {
                $temp = array(
                        "work_start_time" => $row->work_start_time,
                        "work_end_time" => $row->work_end_time,
                        "company_id" => $row->company_id,
                        "days_of_work" => $row->days_of_work,
                        "work_schedule_id" => $row->work_schedule_id,
                        "latest_time_in_allowed" => $row->latest_time_in_allowed,
                        "filter_query" => "work_schedule_id-{$row->work_schedule_id}"
                );
        
                array_push($res, $temp);
            }
            return $res;
        }
    }
    
    public function count_no_show_v2($company_id, $emp_id, $work_schedule_id,$get_workday_v2,$get_current_flex_sched){
        $counter = 0;
        $filter_work_schedule_id = $work_schedule_id;
        $today = date("Y-m-d");
        
        $where = array(
            "e.company_id" => $company_id,
            "e.status" => "Active",
            "epi.employee_status" => "Active",
            "epi.timesheet_required" => "yes",
            "epi.status" => "Active",
            "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($where);
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        $this->db->group_by("e.emp_id");
        $query = $this->edb->get("employee AS e");
        $result = $query->result();
        
        if($result){
            $check_timein_date_v2 = check_timein_date_v2($company_id);
            $count_no_show_for_uniform = $this->count_no_show_for_uniform($company_id);
            $check_employee_custom_schedule_v2 = check_employee_custom_schedule_v2($company_id);
            
            foreach($result as $row){
                $emp_id = $row->emp_id;
                $custom_sched = in_array_custom("emp_id-{$row->emp_id}", $check_employee_custom_schedule_v2);//check_employee_custom_schedule($row->emp_id);
                $work_schedule_id = ($custom_sched) ? $custom_sched->work_schedule_id : $row->work_schedule_id;
                $workday = in_array_custom("work_schedule_id-{$work_schedule_id}", $get_workday_v2); //get_workday($company_id, $work_schedule_id);
        
                if($workday != FALSE){
                    if($workday->workday_type == "Uniform Working Days"){
                        $r = in_array_custom("work_schedule_id-{$work_schedule_id}", $count_no_show_for_uniform);
                        
                        if($r){
                            $date = $today;
                            $is_night_shift = false;
                            if(date("A", strtotime($r->work_start_time)) == "PM" && date("A", strtotime($r->work_end_time)) == "AM"){
                                $date = date("Y-m-d",strtotime("{$today} -1 day"));
                                $is_night_shift = true;
                            }
                            
                            $time_in_row = in_array_custom("emp_id-{$emp_id}{$date}", $check_timein_date_v2);
                            
                            if(!$time_in_row){
                                $curr_y = date("Y");
                                $curr_m = date("m");
                                $curr_d = date("d");
                                $curr_dt = date("Y-m-d H:i:s");
                                $start_time = date("Y-m-d H:i:s",strtotime("{$curr_y}-{$curr_m}-{$curr_d} {$r->work_start_time}"));
                                $end_time = date("Y-m-d H:i:s",strtotime("{$curr_y}-{$curr_m}-{$curr_d} {$r->work_end_time}"));
                        
                                if($is_night_shift && date("H:i:s",strtotime($start_time)) <= date("H:i:s",strtotime($curr_dt))){
                                    $curr_dt = date("Y-m-d H:i:s",strtotime("{$curr_dt} -1 day"));
                                }
                                if($is_night_shift){
                                    $start_time = date("Y-m-d H:i:s",strtotime("{$start_time} -1 day"));
                                }
                        
                        
                                if($start_time != "" && $end_time != ""){
                                    $grace = " +{$r->latest_time_in_allowed} minutes";
                                    if(date("Y-m-d H:i:s",strtotime($start_time."".$grace)) <= date("Y-m-d H:i:s",strtotime($curr_dt)) && date("Y-m-d H:i:s",strtotime($curr_dt)) <= date("Y-m-d H:i:s",strtotime($end_time))){
                                        if(!check_employee_on_leave($row->emp_id)){
                                            if($filter_work_schedule_id != ""){
                                                if($work_schedule_id == $filter_work_schedule_id){
                                                    $counter = $counter + 1;
                                                }
                                            }
                                            else{
                                                $counter = $counter + 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    else if($workday->workday_type == "Flexible Hours"){
                        /*$w = array(
                            "not_required_login"=>0,
                            "company_id" => $company_id,
                            "work_schedule_id"=>$work_schedule_id
                        );
                        $this->db->where($w);
                        $this->db->where("latest_time_in_allowed IS NOT NULL");
                        $q = $this->db->get("flexible_hours");
                        $r = $q->row();*/
                        $r = in_array_custom("work_schedule_id-{$work_schedule_id}", $get_current_flex_sched);
                        if($r){
                            if($r->latest_time_in_allowed != null) {
                                $emp_id = $row->emp_id;
                                /*$w = array(
                                        "emp_id" => $emp_id,
                                        "date" => date("Y-m-d"),
                                        "status" => "Active"
                                );
                                $this->db->where($w);
                                $time_in = $this->db->get("employee_time_in");
                                $time_in_row = $time_in->row();*/
                                $date = date("Y-m-d");
                                $time_in_row = in_array_custom("emp_id-{$emp_id}{$date}", $check_timein_date_v2);
                                if(!$time_in_row){
                                    $hours_per_day = $r->total_hours_for_the_day;
                                    $hours = floor($hours_per_day);
                                    $mins = round(60 * ($hours_per_day - $hours));
                                
                                    $start_time = $r->latest_time_in_allowed;
                                    $end_time = date("H:i:s",strtotime($start_time." +{$hours} hours +{$mins} minutes"));
                                    if($start_time != "" && $end_time != ""){
                                        if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
                                            if(!check_employee_on_leave($row->emp_id)){
                                                if($filter_work_schedule_id != ""){
                                                    if($work_schedule_id == $filter_work_schedule_id){
                                                        $counter = $counter + 1;
                                                    }
                                                }
                                                else{
                                                    $counter = $counter + 1;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    else if($workday->workday_type == "Workshift"){
                        $w = array(
                            "company_id" => $company_id,
                            "work_schedule_id" => $work_schedule_id
                        );
                        $this->db->where($w);
                        $q = $this->db->get("schedule_blocks");
                        $r = $q->row();
                        
                        if($r){
                            $emp_id = $row->emp_id;
                            $w = array(
                                "emp_id" => $emp_id,
                                "date" => date("Y-m-d"),
                                "status" => "Active"
                            );
                            $this->db->where($w);
                            $time_in = $this->db->get("employee_time_in");
                            $time_in_row = $time_in->row();
                        
                            if(!$time_in_row){
                                $start_time = $r->start_time;
                                $end_time = $r->end_time;
                                if($start_time != "" && $end_time != ""){
                                    if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
                                        if(!check_employee_on_leave($row->emp_id)){
                                            if($filter_work_schedule_id != ""){
                                                if($work_schedule_id == $filter_work_schedule_id){
                                                    $counter = $counter + 1;
                                                }
                                            }
                                            else{
                                                $counter = $counter + 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
        
            }
        }
        return $counter;
    }
    
    public function count_sched_by_work_id_v2($comp_id, $emp_id) {
        $res = array();
        $where = array(
                'ess.company_id' => $comp_id,
                'ess.payroll_group_id' => 0,
                "edrt.parent_emp_id" => $emp_id,
                "ess.valid_from" => date('Y-m-d'),
                "ess.until" => date('Y-m-d')
        );
    
        $this->db->where($where);
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = ess.emp_id","LEFT");
        $this->db->group_by('ess.emp_id');
        $q = $this->db->get("employee_shifts_schedule AS ess");
        $r = $q->result();
    
        if($r) {
            foreach ($r as $row) {
                $temp = array(
                        "work_schedule_id" => $row->work_schedule_id,
                        "company_id" => $row->company_id,
                        "payroll_group_id" => $row->payroll_group_id,
                        "parent_emp_id" => $row->parent_emp_id,
                        "valid_from" => $row->valid_from,
                        "until" => $row->until,
                        "filter_query" => "work_schedule_id-{$row->work_schedule_id}"
                );
            
                array_push($res, $temp);
            }
            return $res;
        }
        #return ($r) ? count($r) : 0 ;
    }
    
    public function get_employee_manager_id($emp_id,$company_id) {
        $res = array();
        $where = array(
                'epi.company_id' => $company_id,
                'epi.employee_status' => 'Active',
                "edrt.parent_emp_id" => $emp_id
        );
        
        $this->db->where($where);
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
        $q = $this->edb->get('employee_payroll_information AS epi');
        $r = $q->result();
        if($r) {
            foreach ($r as $row) {
                $temp = array(
                        "company_id" => $row->company_id,
                        "employee_status" => $row->employee_status,
                        "parent_emp_id" => $row->parent_emp_id,
                        "emp_id" => $row->emp_id,
                        "payroll_group_id" => $row->payroll_group_id,
                        "filter_query" => "emp_id-{$row->emp_id}"
                );
                    
                array_push($res, $temp);
            }
            return $res;
        }
    }
    
    public function count_sched_by_payroll_grp_id_v2($emp_id,$work_sched_id,$employees) {
        $get_employee_manager_id = $this->get_employee_manager_id($emp_id,$this->company_id);
        if($employees) {
            foreach ($employees AS $e) {
                /*$where = array(
                        'epi.emp_id' => $e->emp_id,
                        'epi.company_id' => $e->company_id,
                        'epi.employee_status' => 'Active',
                        "edrt.parent_emp_id" => $emp_id
                );
    
                $this->db->where($where);
                $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
                $q = $this->edb->get('employee_payroll_information AS epi');
                $r = $q->row();*/
                $r = in_array_custom("emp_id-{$e->emp_id}", $get_employee_manager_id);
    
                if($r->payroll_group_id != 0 || $r->payroll_group_id != NULL) {
                    $where1 = array(
                            'work_schedule_id' => $work_sched_id,
                            'company_id' => $r->company_id,
                            'status' => 'Active'
                    );
                        
                    $this->db->where($where1);
                    $q1 = $this->db->get('payroll_group');
                    $r1 = $q1->result();
                        
                    return ($r1) ? count($r1) : 0 ;
                }
            }
        }
    
    }
    
    public function get_employee_list_v2($cut_off_from,$cut_off_to,$company_id,$emp_id,$params=NULL){
    
        $sort_array = array(
                "e.last_name",
                "d.department_name",
                "p.position_name"
        );
    
        // FOR TARDY EMPLOYEE LISTING FLAG
        if($params != NULL){
            if(isset($params["flag_tardy_view"]) != ""){
                $this->db->where("(eti.overbreak_min > 0 OR eti.late_min > 0)");
            }
        }
        
        $s = array(
                "eti.employee_time_in_id"
        );
    
        $w = array(
                "epi.company_id" => $company_id,
                "epi.status"=>"Active",
                "e.status"=>"Active",
                "eti.date >= "=>$cut_off_from,
                "eti.date <= "=>$cut_off_to,
                "edrt.parent_emp_id" => $emp_id
        );
        
        $this->db->select($s);
        $this->db->where($w);
    
        $this->db->where("eti.time_in IS NOT NULL");
        $this->db->where("eti.time_out IS NOT NULL");
        $this->db->where("(eti.time_in_status = 'approved' OR eti.time_in_status IS NULL)");
    
        $this->edb->join("employee AS e","eti.emp_id = e.emp_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
    
        if($params != NULL){
            if($params["sort_by"] != ""){
                if(in_array($params["sort_by"], $sort_array)){
                    if($params["sort_by"] == "e.last_name"){
                        $this->edb->order_by($params["sort_by"],$params["sort"]);
                    }
                    else{
                        $this->db->order_by($params["sort_by"],$params["sort"]);
                    }
                }
            }
        }
    
        $q = $this->edb->get("employee_time_in AS eti");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }
    
    public function get_all_employees_v2($emp_id, $company_id,$limit = 10000) {
        $s = array(
                "e.emp_id",
                "e.company_id",
                "a.account_id",
                "e.first_name",
                "e.last_name"
        );
        
        $where = array(
                "edrt.parent_emp_id" => $emp_id,
                "e.company_id" => $company_id,
                "e.status" => "Active"
        );
    
        $this->edb->select($s);
        $this->edb->where($where);
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $q = $this->edb->get("employee AS e",$limit);
        $r = $q->result();
    
        return ($r) ? $r : FALSE;
    }
}