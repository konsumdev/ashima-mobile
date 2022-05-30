<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cash_advance extends CI_Controller{
	var $verify;
		
	public function __construct(){
	   parent::__construct();	
	   $this->load->model('konsumglobal_jmodel','jmodel');
	   $this->load->model('employee_model','employee');
	   $this->load->model('approval_model','approval');
	   $this->load->model('emp_cash_advance_model',"ecam");
	   $this->load->model('approval_group_model','agm');
	   
	  // $this->company_info = whose_company();
	  
	   $this->emp_id = $this->session->userdata('emp_id');
	   $this->company_id =$this->employee->check_company_id($this->emp_id);  
	   
	}
	public function index(){
		$this->db->where("ca.emp_id",$this->emp_id);
		$this->db->join("cash_advance_payment_schedule AS caps","caps.cash_advance_payment_schedule_id = ca.cash_advance_payment_schedule_id","LEFT");
		$this->db->join("cash_advance_payment_terms AS capt","capt.cash_advance_payment_terms_id = ca.cash_advance_payment_terms_id","LEFT");
		$query = $this->db->get("cash_advance AS ca");
		$result = $query->result();
		
		echo json_encode($result);
	}
	public function check_enabled(){
		$emp_id = $this->emp_id;
		if(!empty($emp_id)){
			$this->edb->where("cas.company_id",$emp_id);
			//	$this->edb->join("employee AS emp","emp.company_id = cas.company_id","LEFT");
			$query = $this->db->get("cash_advance_settings as cas");
			$row = $query->row();
				
			if($row){
				$check = ($row->enable_disable == "Enable") ? 1 : 0;
				$ca_settings = array(
						"result" => 0
				);
				echo json_encode($ca_settings);
			}
		}
		else{
			return 0;
		}
	}
	
	public function payment_schedule(){
		$emp_id = $this->emp_id;
		$payment_schedule = array();
		
		if(!empty($emp_id)){
			$this->edb->where("emp.emp_id",$emp_id);
			$this->edb->join("cash_advance_payment_schedule AS caps","caps.cash_advance_payment_schedule_id = capss.cash_advance_payment_schedule_id","LEFT");
			$this->edb->join("employee AS emp","emp.company_id = capss.company_id","LEFT");
			$query = $this->edb->get("cash_advance_payment_schedule_selected AS capss");
			$result = $query->result();

			if($result){
				
				foreach($result as $res){
					$arrs = array(
						"payment_schedule_id" => $res->cash_advance_payment_schedule_id,
						"payment_schedule" =>  $res->cash_advance_payment_schedule
					);
					array_push($payment_schedule,$arrs);
				}
				
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
		echo json_encode($payment_schedule);
	}
	public function payment_terms(){
		$emp_id = $this->emp_id;
		$payment_terms = array();
		
		if(!empty($emp_id)){
			$this->edb->where("emp.emp_id",$emp_id);
			$this->edb->join("cash_advance_payment_terms AS capt","capt.cash_advance_payment_terms_id = capts.cash_advance_payment_terms_id","LEFT");
			$this->edb->join("employee AS emp","emp.company_id = capt.company_id","LEFT");
			$query = $this->edb->get("cash_advance_payment_terms_selected AS capts");
			$result = $query->result();
				
			if($result){
				
				foreach($result as $res){
					$arrs = array(
							"payment_terms_id" => $res->cash_advance_payment_terms_id,
							"payment_terms" =>  $res->cash_advance_payment_terms
					);
					array_push($payment_terms,$arrs);
				}
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
		echo json_encode($payment_terms);
	}
	public function avail_cashadvance(){
		$post = $this->input->post();
		$gedate = date("Y-m-d H:i:s");
		$amount = $post['amount'];
		$terms = $post['terms'];
		$desired = $post['schedule'];
		$proposal = $post['proposal'];
		$empID = $this->emp_id;
		$companyID = $this->company_id;
		//$data = array();
	
		$for_token = date("YmdHis").$empID;
		$token = md5($for_token);
		$app_date = date("Y-m-d H:i:s",strtotime($gedate));
		$emp_ca_data = array(
				"application_date" => $app_date,
				"amount" => $amount,
				"emp_id" => $empID,
				"cash_advance_payment_schedule_id" => $desired,
				"cash_advance_payment_terms_id" => $terms,
				"company_id" =>$companyID,
				"ca_token" => $token
		);
	
	
		if($this->db->insert("cash_advance",$emp_ca_data)){
			$finance = $this->approval->finance_info($companyID);
			$human_resource = $this->approval->hr_info($companyID);
			$approver = $this->ecam->get_approver_name($empID,$companyID);
			$approver_name = ucwords($approver->first_name)." ".ucwords($approver->last_name);
			$approver_email = $approver->email;
			//$app_date = date("m/d/Y", strtotime($app_date));
			$app_token = $token;
	
			$send_appr_test = "";
	
			if($this->send_approver_notif_cash_adv($empID, $approver_name, $approver_email, $token, $app_date, $amount,$companyID)){
				$send_appr_test = "ok";
				if($finance){
					foreach($finance as $fi){
						$fi_fullname = ucwords($fi->first_name)." ".ucwords($fi->last_name);
						$fi_email = $fi->email;
						$this->send_notif_cash_advance($empID, $fi_fullname, $fi_email, $app_date, $amount,$companyID);
					}
				}
					
				if($human_resource){
					foreach ($human_resource as $hr){
						$hr_fullname = ucwords($hr->first_name)." ".ucwords($hr->last_name);
						$hr_email = $hr->email;
						$this->send_notif_cash_advance($empID, $hr_fullname, $hr_email, $app_date, $amount,$companyID);
					}
				}
			}
	
// 			$tr = array(
// 					"result" => 1,
// 					"approver_token" => $app_token,
// 					"send_appr_test" => $send_appr_test,
// 					"wee" =>  $emp_ca_data,
// 					"error_message" => "Cash Advance application successful"
// 			);
			$tr = array(
					"result" => 1
			);
			echo json_encode($tr);
		}else
		{
			$data = array(
					"result" => 0
			);
			echo json_encode($data);
		}
	
	
	
	}
	
	public function send_approver_notif_cash_adv($emp_id = NULL, $appr_name = NULL, $appr_email = NULL, $token = NULL, $app_date = NULL, $amount = NULL,$companyID = NULL){
		if($emp_id != NULL && $appr_name != NULL && $appr_email != NULL && $token != NULL && $app_date != NULL && $amount != NULL ){
				
			$emp_name = $this->ecam->get_employee_fullname($emp_id,$companyID);
				
			$config['protocol'] = 'sendmail';
			$config['wordwrap'] = TRUE;
			$config['mailtype'] = 'html';
			$config['charset'] = 'utf-8';
				
			$this->load->library('email',$config);
			$this->email->initialize($config);
			$this->email->set_newline("\r\n");
			$this->email->from('payroll@konsum.ph','Konsum Payroll System');
			$this->email->to($appr_email);
				
			$this->email->subject('Cash Advance Application - '.$emp_name);
				
			$this->email->message(
					'
				<html>
					<head>
						<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
					</head>
					<body>
						<table>
							<tbody>
								<tr>
									<td>
										Hi '.$appr_name.',
									</td>
								</tr>
								<tr><td>&nbsp;</td></tr>
								<tr><td>New cash advance application has been filed. Details below:</td></tr>
	
								<tr><td>&nbsp;</td></tr>
								<tr><td>Requested By: '.$emp_name.'</td></tr>
	
								<tr><td>&nbsp;</td></tr>
								<tr><td>Date Applied: '.$app_date.'</td></tr>
	
								<tr><td>&nbsp;</td></tr>
								<tr><td>Amount: '.$amount.'</td></tr>
	
								<tr><td>&nbsp;</td></tr>
								<tr><td>Remarks:</td></tr>
	
								<tr><td>&nbsp;</td></tr>
								<tr><td>Click <a href="'.base_url().'approval/cash_advance_approval/approver_approval/?catoken='.$token.'">here</a> to view the cash advance application.</td></tr>
								<tr><td>&nbsp;</td></tr>
								<tr><td>&nbsp;</td></tr>
								<tr>
									<td>
										Thank you,<br />Konsum Payroll System
									</td>
								</tr>
							</tbody>
						</table>
					</body>
				</html>
				'
			);
			if($this->email->send()){
				return TRUE;
			}
			else{
				return FALSE;
			}
		}
		else{
			show_error("Invalid parameter 1");
		}
	}
	
	public function send_notif_cash_advance($emp_id = NULL, $hr_name = NULL, $hr_email = NULL, $app_date = NULL, $amount = NULL,$companyID = NULL){
		if($emp_id != NULL && $hr_name != NULL && $hr_email != NULL && $app_date != NULL && $amount != NULL ){
				
			$emp_name = $this->ecam->get_employee_fullname($emp_id,$companyID);
				
			$config['protocol'] = 'sendmail';
			$config['wordwrap'] = TRUE;
			$config['mailtype'] = 'html';
			$config['charset'] = 'utf-8';
				
			$this->load->library('email',$config);
			$this->email->initialize($config);
			$this->email->set_newline("\r\n");
			$this->email->from('payroll@konsum.ph','Konsum Payroll System ');
			$this->email->to($hr_email);
				
			$this->email->subject('Cash Advance Application - '.$emp_name);
				
			$this->email->message(
					'
				<html>
					<head>
						<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
					</head>
					<body>
						<table>
							<tbody>
								<tr>
									<td>
										Hi '.$hr_name.',
									</td>
								</tr>
								<tr><td>&nbsp;</td></tr>
								<tr><td>New cash advance application has been filed. Details below:</td></tr>
	
								<tr><td>&nbsp;</td></tr>
								<tr><td>Requested By: '.$emp_name.'</td></tr>
	
								<tr><td>&nbsp;</td></tr>
								<tr><td>Date Applied: '.$app_date.'</td></tr>
	
								<tr><td>&nbsp;</td></tr>
								<tr><td>Amount: '.$amount.'</td></tr>
	
								<tr><td>&nbsp;</td></tr>
								<tr><td>&nbsp;</td></tr>
								<tr>
									<td>
										Thank you,<br />Konsum Payroll System
									</td>
								</tr>
							</tbody>
						</table>
					</body>
				</html>
				'
			);
			if($this->email->send()){
				return TRUE;
			}
			else{
				return FALSE;
			}
		}
		else{
			show_error("Invalid parameter 2");
		}
	}
}