<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Employee Model
 *
 * @category Model
 * @version 1.0
 * @author Christopher Cuizon <christophercuizons@gwapo.com>
 */
	class Employee_notifications_model extends CI_Model {
		
	var $pagi;
		public function __construct(){
			parent::__construct();	
			$this->pagi = 10;
		}
		

		
		/**
		 * CHECK IF WE HAVE READ
		 * Enter description here ...
		 * @param int $message_board_id
		 */
		public function already_read($message_board_id){
			if(is_numeric($message_board_id)){
				$where = array(
					'psa_id' 			=> $this->session->userdata('psa_id'),
					'account_id'		=> $this->session->userdata('account_id'),
					'message_board_id'	=> $message_board_id,
					'status'			=> 'Active'
				);		
				$this->db->where($where);
				$q = $this->edb->get('message_board_read');
				$row = $q->row();
				return $row;
			}
		}
		
		/**
		 * IF WE READ THIS SO WE MARK IT AS READ IF WE ALREADY READ THESE then JUST UPDATE
		 * SAVING READ MESSAGES
		 * @param int $message_board_id
		 * @return boolean
		 */
		public function ajax_read_notification($message_board_id) {	
			if(is_numeric($message_board_id)) {
				// WE should check first if already read this message
				$where = array(
					'psa_id' 			=> $this->session->userdata('psa_id'),
					'account_id'		=> $this->session->userdata('account_id'),
					'message_board_id'	=> $message_board_id,
					'status'			=> 'Active'
				);		
				$this->db->where($where);
				$query = $this->edb->get('message_board_read');
				$row = $query->row();
				$query->free_result();		
				if($row == false) {
					$field = array(
						'psa_id' 			=> $this->session->userdata('psa_id'),
						'account_id'		=>$this->session->userdata('account_id'),
						'message_board_id'	=> $message_board_id,
						'date'				=> date("Y-m-d H:i:s")
					);	
					$this->edb->insert('message_board_read',$field);
				} else {
					$update_field = array(
						'read'	=> '1',
						'date'	=> date("Y-m-d H:i:s")
					);
					$this->edb->where($where);
					$this->edb->update('message_board_read',$update_field);
				}
			}else{
				return false;
			}
		}
		
		public function ajax_counter_notification(){
			$psa_id = $this->session->userdata('psa_id');
			$emp_id = $this->session->userdata('emp_id');
			
			if(isset($psa_id)){
				$where = array(
					'mb.psa_id' => $this->session->userdata('psa_id'),
					'mb.status' => 'Active',
					'mb.via' => 'system',
					'mb.emp_id'	=> $emp_id
				);
				$this->db->where($where);	
				$q = $this->edb->get('message_board AS mb');
				$result = $q->result();
				$ctr = 0;
				$overall = count($result);
				
				if($result){
					foreach($result as $key=>$val){
						
						$isread = $this->already_read($val->message_board_id);
						if($isread){
							$ctr++;
						}
					}
					
					$ctr_result = $overall - $ctr;
					if($ctr_result <=0){
						return 0;
					}else{
						return $ctr_result;
					}
				}		
			}else{
				return 0;
			}
		}
		
		public function ajax_overall_count($pagi){
			$psa_id = $this->session->userdata('psa_id');
			if(isset($psa_id)){
				$where = array(
					'mb.psa_id' => $this->session->userdata('psa_id'),
					'mb.status' => 'Active',
					//'mb.via' => 'system',
					'mb.emp_id'	=> ''
				);
				$this->db->where($where);
				$this->db->or_where('mb.emp_id',$this->session->userdata('emp_id'));			
				$q = $this->edb->get('message_board AS mb');
				$result = $q->result();
				$ctr = 0;
				$overall = count($result);
				if($overall > 10){
					$ctr =  $overall / $pagi;
				}
				return intval($ctr);
			}
		}
		
		public function total_rows(){
			$psa_id = $this->session->userdata('psa_id');
			if(isset($psa_id)){
				$where = array(
						"mb.psa_id" => $this->session->userdata('psa_id'),
						"mb.status" => "Active",
						"mb.via !=" => "system",
						"mb.emp_id" => 0
				);
				$this->db->where($where);
				$q = $this->edb->get('message_board AS mb');
				$result = $q->num_rows();
				return $result;
			}
		}
		
		public function get_notifications_pagi($limit,$start){
			$psa_id = $this->session->userdata('psa_id');
			if(isset($psa_id)){
				$where = array(
						"mb.psa_id" => $this->session->userdata('psa_id'),
						"mb.status" => "Active",
						"mb.via !=" => "system",
						"mb.emp_id" => 0
				);
				$this->db->order_by('mb.date','DESC');
				$this->db->where($where);
				$q = $this->edb->get('message_board AS mb',$limit,$start);
				$result = $q->result();
				return $result;
			}else{
				return false;
			}
		}
		
		
		public function get_individual_emp_notifcations($limit,$start,$emp_id){
			$psa_id = $this->session->userdata('psa_id');
			if(isset($psa_id)){
				$where = array(
						"mb.psa_id" => $this->session->userdata('psa_id'),
						"mb.status" => "Active",
						"mb.via " => "system",
						"mb.emp_id" => $emp_id
				);
				$this->db->order_by('mb.date','DESC');
				$this->db->where($where);
				$q = $this->edb->get('message_board AS mb',$limit,$start);
				$result = $q->result();
				return $result;
			}else{
				return false;
			}
		}
		
		public function get_individual_emp_notifcations_count($emp_id){
			$psa_id = $this->session->userdata('psa_id');
			if(isset($psa_id)){
				$where = array(
						"mb.psa_id" => $this->session->userdata('psa_id'),
						"mb.status" => "Active",
						"mb.via " => "system",
						"mb.emp_id" => $emp_id
				);
				$this->db->select("count(*) AS counter");
				$this->db->order_by('mb.date','DESC');
				$this->db->where($where);
				$q = $this->edb->get('message_board AS mb');
				$row = $q->row();
				return ($row) ? $row->counter : 0;
			}else{
				return false;
			}
		}
		
		public function get_badege_unread_count($emp_id){
			$psa_id = $this->session->userdata('psa_id');
			if(isset($psa_id)){
				$where = array(
						"mb.psa_id" => $this->session->userdata('psa_id'),
						"mb.status" => "Active",
						"mb.via " => "system",
						"mb.emp_id" => $emp_id
				);
				$this->db->select("count(*) AS counter");
				$this->db->order_by('mb.date','DESC');
				$this->db->where($where);
				$q = $this->edb->get('message_board AS mb');
				$row = $q->row();
				return ($row) ? $row->counter : 0;
			}else{
				return false;
			}
		}
		
	}