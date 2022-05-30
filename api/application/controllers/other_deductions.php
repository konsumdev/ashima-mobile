<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Other_deductions extends CI_Controller{
		
	public function __construct(){
	   parent::__construct();
	   $this->load->model('employee_model','employee');
	   
	   $this->emp_id = $this->session->userdata('emp_id');
	   $this->company_id =$this->employee->check_company_id($this->emp_id);  
	   
	}	
	public function index() {
	    $page = $this->input->post('page');
	    $limit = $this->input->post('limit');
	    
	    $this->per_page = 10;
	    
	    $result_temp = $this->employee->other_deduction_summary($this->emp_id,$this->company_id,false, $limit, (($page-1) * $this->per_page));
	    
	    $result = array();
	    
	    if($result_temp) {
	        $flag = 0;
	        $flag_id = "";
	        $cont = '';
	        foreach($result_temp as $row) {
	            $get_other_deduction_to_date = $this->employee->get_other_deduction_to_date($row->emp_id,$row->company_id,$row->employee_deduction_id);
	            $get_other_deduction_to_date = number_format($get_other_deduction_to_date,2,'.','');
	            $amount = number_format($row->amount,2,'.','');
	            $employee_deduction_id = $row->employee_deduction_id;
	            
	            if($cont == ''){
	                $cont = $row->employee_deduction_id;
	            }
	            
	            if($cont !=""){
	                if($cont == $row->employee_deduction_id){
	                    $flag_content = 0;
	                }else{
	                    $cont = $row->employee_deduction_id;
	                    $flag_content = 1;
	                }
	            }
	            
	            if($flag_content == 1){
	                $cont = "";
	                $flag_id = "";
	                $flag = 0;
	            }
	            
	            $temp = array(
	                "deduction_name" => $row->deduction_name,
	                "amount" => $amount,
	                "payroll_period" => $row->payroll_period,
	                "period_from" => $row->period_from,
	                "period_to" => $row->period_to,
	                "deduction_id" => $row->deduction_id,
	                "employee_deduction_id" => $row->employee_deduction_id,
	                "emp_id" => $row->emp_id,
	                "company_id" => $row->company_id,
	                "total" => $get_other_deduction_to_date
	            );
	            
	            
	            if($flag_id == ''){
	                $flag_id = $employee_deduction_id;
	                $amount = 0;
	            }
	            
	            if($flag == 0) {
	                $total = $get_other_deduction_to_date - $amount;
	                $flag = $total;
	            } else {
	                $total = $flag - $amount;
	                $flag = $total;
	            }
	            
	            $temp['amount_to_date'] = number_format($total,2,'.','');
	            
	            array_push($result, (object) $temp);
	        }
	    }
	    
	    $total = ceil($this->employee->other_deduction_summary($this->emp_id,$this->company_id,true) / 10);
	    
	   # $result1 = array_slice($result, (($page-1) * $this->per_page), $limit,TRUE);
	    
	    if($result) {
	        echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "total" => $total,"list" => $result));
	        return false;
	    } else {
	        echo json_encode(array("result" => "0"));
	        return false;
	    }
		
	}
}