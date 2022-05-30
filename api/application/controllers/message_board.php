<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Message_board extends CI_Controller{
	var $verify;
		
	public function __construct(){
	   parent::__construct();	
	   $this->load->model('konsumglobal_jmodel','jmodel');
	   $this->load->model('employee_model','employee');
	   $this->load->model('approval_model','approval');
	   $this->load->model('approval_group_model','agm');
	   $this->load->model('employee_notifications_model','noti');
	   
	  // $this->company_info = whose_company();
	  	
	   $this->emp_id = $this->session->userdata('emp_id');
	   $this->company_id =$this->employee->check_company_id($this->emp_id);  
	   
	}
	public function count_unread(){
		$emp_id = $this->emp_id;
		$psa_id = $this->session->userdata('psa_id');
		$company_id = $this->company_id;
		
		$where = array(
				'mb.psa_id' => $psa_id,
				'mb.status' => 'Active',
				'mb.emp_id'	=> ''
		);
		$this->db->select('*');
		$this->db->order_by('mb.date','DESC');
		$this->db->where($where);
		$this->db->or_where('mb.emp_id',$emp_id);
		$this->db->join('message_board_read as mbr','mb.message_board_id = mbr.message_board_id','LEFT');
		$q = $this->edb->get('message_board AS mb');
		$result = $q->result();
		$counter = 0;
		foreach($result as $res){
			if($res->message_board_read_id != NULL){
				$counter++;
			}
		}
		echo json_encode(array('count'=> $this->noti->ajax_counter_notification()));
	}
	public function index(){
		
		$emp_id = $this->emp_id;
		$psa_id = $this->session->userdata('psa_id');
		$company_id = $this->company_id;
		
		$where = array(
				'mb.psa_id' => $psa_id,
				'mb.status' => 'Active',
				'mb.emp_id'	=> ''
		);
		$this->db->select('*');
		$this->db->order_by('mb.date','DESC');
		$this->db->where($where);
		$this->db->or_where('mb.emp_id',$emp_id);
		$this->db->join('message_board_read as mbr','mb.message_board_id = mbr.message_board_id','LEFT');
		$q = $this->edb->get('message_board AS mb');
		
		echo json_encode($q->result());
	}
	public function mark_read(){
		$message_board_id = $this->input->post('message_board_id');
		if($message_board_id != ""){
			$account_id = $this->noti->ajax_read_notification($message_board_id);
			$result = array(
					'result'=>1,
					'error'=>'false'
			);
			echo json_encode($result);
			return false;
		}else{
			$result = array(
					'result'=>0,
					'error' => 'invalid ID'
			);
			echo json_encode($result);
			return false;
		}
	}
}