<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Calendar extends CI_Controller{
	var $verify;
		
	public function __construct(){
	   parent::__construct();	
	  //$this->load->model("loginmodel","lm");  
	  $this->load->model('konsumglobal_jmodel','jmodel');
	  $this->load->model('employee_model','employee');
	  $this->load->model('approval_model','approval');
	  $this->load->model('approval_group_model','agm');
	   
	  // $this->company_info = whose_company();
	    
	  $this->emp_id = $this->session->userdata('emp_id');
	  $this->company_id =$this->employee->check_company_id($this->emp_id);
	}	
	public function index(){
		$where = array(
			'emp_id'=>$this->emp_id,
			'comp_id'=>$this->company_id
		);
		$this->db->where($where);
		$this->db->order_by('date','desc');
		$q = $this->db->get('employee_time_in');
		$r = $q->result();
		
		echo json_encode($r);
	}
	public function daily_view(){
		$post = $this->input->post();
		$date;
	}
}