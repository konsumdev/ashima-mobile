<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Profile extends CI_Controller{
	var $verify;
		
	public function __construct(){
	   parent::__construct();	
	   $this->load->model('konsumglobal_jmodel','jmodel');
	   $this->load->model('employee_model','employee');
	   $this->load->model('approval_model','approval');
	   $this->load->model('approval_group_model','agm');
	   $this->load->model('employee_mobile_model','mobile');
	   
	   $this->company_info = whose_company();
	  
	   $this->emp_id = $this->session->userdata('emp_id');
	   $this->company_id =$this->employee->check_company_id($this->emp_id);  
	   $this->account_id = $this->session->userdata('account_id');
       
       $this->load->library('ciqrcode');
	}	
	public function index(){
		
		$get_emp_employment_history = $this->mobile->get_emp_employment_history($this->emp_id,$this->company_id);
		$dependents = $this->employee->get_dependents($this->emp_id);
		$get_profile_information = $this->mobile->get_profile_information($this->emp_id);
		
		// get basic pay
		$period = next_pay_period($this->emp_id, $this->company_id);
		//$prof_info
		$period = array(
				'period_from'=> $period->cut_off_from,
				'period_to' => $period->cut_off_to
		);
		
		$emp_basic_pay = $this->mobile->emp_basic_pay($this->company_id,$this->emp_id,$period);
		if (is_numeric($emp_basic_pay)) {
			$basic_pay_salary = ($emp_basic_pay) ? number_format($emp_basic_pay,2) : "0.00";
		} else {
			$basic_pay_salary = $emp_basic_pay;
		}
		
		
		// get hourly rate
		$pay_settings = $this->mobile->get_working_days_settings($this->company_id);
		$basic_pay = $this->employee->get_basic_pay($this->emp_id,$this->company_id);
		
		if($pay_settings){
			$days_in_a_year = $pay_settings->working_days_in_a_year;
			$hours_per_day = $pay_settings->average_working_hours_per_day;
			if($hours_per_day == 0 || $hours_per_day == null) {
				$new_hours_per_day = 8;
			} else {
				$new_hours_per_day = $hours_per_day;
			}
			 
			$total_days = $days_in_a_year / 12;
			if($basic_pay){
				if($basic_pay->effective_date == ""){
					$bsc_pay = $basic_pay->current_basic_pay;
				}else{
					$bsc_pay = $basic_pay->new_basic_pay;
				}
		
				$total_daily_rate = $bsc_pay / $total_days;
				$suma_total = $total_daily_rate / $new_hours_per_day;
				$hourly_rate = round($suma_total,2);
			} else {
				$hourly_rate = "0.00";
			}
		} else {
			$hourly_rate = "0.00";
		}
		
		// get back accoutn no
		$employee_fulldetails = employee_who_fulldetails($this->account_id, $this->company_id);
		$bank_acnt_no = $employee_fulldetails ? $employee_fulldetails->card_id : '~';
		
		$compensation = array(
				'bank_account_no' => $bank_acnt_no,
				'hourly_rate' => $hourly_rate,
				'basic_pay' => $basic_pay_salary
		);
		
		// get compensation history
		$compensacion_historia = $this->mobile->get_employee_compensation_history($this->company_id, $this->emp_id);
		$compensation_arr = array();
		
		if ($compensacion_historia) {
			$prev_pay = 0;
			
			foreach ($compensacion_historia as $ch) {
				$prev_pay = $ch->current_basic_pay != '' ? $ch->current_basic_pay : 0;
				
				$temp_comp_arr = array(
						'compensation_history_id' => $ch->compensation_history_id,
						'current_basic_pay' => is_numeric($ch->current_basic_pay) ? number_format($ch->current_basic_pay, 2) : $ch->current_basic_pay,
						'date_changed' => $ch->date_changed,
						'old_basic_pay' => is_numeric($ch->old_basic_pay) ? number_format($ch->old_basic_pay,2) : $ch->old_basic_pay,
						'effective_date' => $ch->effective_date,
						'adjustment_date' => $ch->adjustment_date,
						'adjustment_details' => $ch->adjustment_details,
						'previous_basic_pay' => number_format($prev_pay, 2)
				);
				
				array_push($compensation_arr, $temp_comp_arr);
				
        	}
	    }
	    
		echo json_encode(
				array(
						"profile" => $get_profile_information, 
						"dependents" => $dependents, 
						"employment_history" => $get_emp_employment_history, 
						"compensation" => $compensation, 
						"compensation_history" => $compensation_arr
						
				)
			);
		
		return false;
		
	}
    
    function my_qr() {
        
        $ses_data = $this->session->all_userdata();
        $cloud_id = $this->session->userdata('cloud_id');

        if ($cloud_id) {
            echo json_encode(
				array(
                    "cloud_id" => $cloud_id,
				)
			);
		
		    return false;
        } else if ($ses_data) {
            echo json_encode(
				array(
                    "cloud_id" => $ses_data["cloud_id"],
				)
			);
		
		    return false;
        } else {
            echo json_encode(
				array(
                    "cloud_id" => "Scan QR Code",
				)
			);
		
		    return false;
        }
        
    }

    function get_clockguard_settings($comp_id) {
        $where = array(
			"company_id" => $comp_id
		);
		$this->db->where($where);

		$query = $this->db->get("clock_guard_settings");
		$result = $query->row();

		if ($result) {
			return $result;
		} else {
			return false;
		}
    }
    
    function maketh($cloud_id, $company) {

        // $ses_data = $this->session->all_userdata();
        // if ( ! $ses_data) {
        //     return false;
        // }

        // $cloud_id = $ses_data["cloud_id"];
        // $company = $ses_data["comp_id"];

        if (!$cloud_id || !$company) {
            return false;
        }

        $kiosk_settings = $this->get_clockguard_settings($company);
        
		$data = ''.$cloud_id;
		$size = '200x200';
        $logo = '';
        if ($kiosk_settings) {
            if (isset($kiosk_settings->enable_kiosk_qr_logo)) {
                if ($kiosk_settings->enable_kiosk_qr_logo == 'enable') {
                    if (isset($kiosk_settings->kiosk_qr_company_logo)) {
                        $filename = (isset($kiosk_settings->kiosk_qr_company_logo)) ? $kiosk_settings->kiosk_qr_company_logo : '';
                        $logo = ($filename) ? base_url().'uploads/companies/'.$company.'/'.$filename : '';
                    }                    
                }
            }       
        }

		$arrContextOptions=array(
		    "ssl"=>array(
		        "verify_peer"=>false,
		        "verify_peer_name"=>false,
		    ),
        );
        
        if (!$data) {
            return false;
        }

		$response = file_get_contents("https://chart.googleapis.com/chart?cht=qr&chl=".$data."&chs=".$size."&&chld=H|2", false, stream_context_create($arrContextOptions));

		$QR = imagecreatefromstring($response);
		if(false){
        // if($logo){
            $check_logo = @file_get_contents($logo);
            if ($check_logo === FALSE) {
                // means diretory does not exist
            } else {                
                $logo = imagecreatefromstring(file_get_contents($logo));

                imagecolortransparent($logo , imagecolorallocatealpha($logo , 0, 0, 0, 127));
                imagealphablending($logo , false);
                imagesavealpha($logo , true);

                $QR_width = imagesx($QR);
                $QR_height = imagesy($QR);

                $logo_width = imagesx($logo);
                $logo_height = imagesy($logo);

                // Scale logo to fit in the QR Code
                $logo_qr_width = $QR_width/3;
                $scale = $logo_width/$logo_qr_width;
                $logo_qr_height = $logo_height/$scale;

                imagecopyresampled($QR, $logo, $QR_width/3, $QR_height/3, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
            }	    
		}
		imagepng($QR);
		imagedestroy($QR);
	}

    function get_qr() {

        $ses_data = $this->session->all_userdata();
        if ($ses_data) {
            $contents = $ses_data["cloud_id"];
                                                
            $params['data'] = $contents;
            $params['level'] = 'H';
            $params['size'] = 6;

            $this->ciqrcode->generate($params);
        }        
    }

	public function update_basic_info() {
		$orig_primary = $this->input->post("origPrimary");
		$psa_id = $this->input->post("payroll_system_account_id");
		$login_mobile_number = $this->input->post("primary_mobile_number");
		$login_mobile_number_2 = $this->input->post("secondary_mobile_number");
		$pri_mob = $login_mobile_number;
		# Update Mobile numbers
		
		$where_accounts = array(
			'account_id' => $this->account_id,
			'payroll_system_account_id' => $psa_id
		);
		if (strcmp(trim($orig_primary), trim($pri_mob)) != 0) {
			$sms_msg = "Your primary mobile number has been changed. To verify your new primary mobile number, login to your portal using this mobile number and your existing password.";
			isms_sender_global($this->account_id, $sms_msg, NULL, false);
		}
		
		$splice63_1 = substr($login_mobile_number, 0,2);
		$splice63_2 = substr($login_mobile_number_2, 0,2);
		if ($splice63_1 === '09') {
			$login_mobile_number = substr_replace($login_mobile_number,'63',0,1);
		}
		if ($splice63_2 === '09') {
			$login_mobile_number_2 = substr_replace($login_mobile_number_2,'63',0,1);
		}

		if ((strlen($login_mobile_number) != 12) || (strlen($login_mobile_number_2) != 12)) {
			echo json_encode(array("result" => 0, "msg" => "Please provide a valid mobile number."));
			return false;
		}
		
		$accounts_field = array(
				'login_mobile_number'=>$login_mobile_number,
				'login_mobile_number_2' =>$login_mobile_number_2,
				'telephone_number' => $this->input->post("telephone_number")
		);
		
		$mobile_change = eupdate('accounts',$accounts_field,$where_accounts);
		
		$fields = array(
				"gender"				=> $this->db->escape_str($this->input->post("gender")),
				"marital_status"		=> $this->db->escape_str($this->input->post("marital_status")),
				"address"          		=> $this->db->escape_str($this->input->post("address")),
				"citizenship_status"	=> $this->db->escape_str($this->input->post("citizenship_status")),
				"state"          		=> $this->db->escape_str($this->input->post("state")),
				"city"          		=> $this->db->escape_str($this->input->post("city")),
				"dob" 					=> $this->db->escape_str($this->input->post("dob"))
		);
		
		$where = array("emp_id"=>$this->emp_id);
		$update_company = $this->employee->update_fields("employee",$fields,$where);
		
		if($update_company > 0) {
			echo json_encode(array("result" => 1, "msg" => "Information Successfully Saved"));
			return false;
		} else {
			echo json_encode(array("result" => 0, "msg" => "No changes made."));
			return false;
		}
	}
	
	public function reports_to(){
		$get_selected_reports_to = $this->mobile->get_selected_reports_to($this->emp_id,$this->company_id);
		if($get_selected_reports_to){
			echo json_encode($get_selected_reports_to);
			return false;
		} else {
			return false;
		}
	}
	
	public function change_password(){
		$emp_id = $this->emp_id;
		$post = $this->input->post();
		$old_password = $post['old_password'];
		$new_password = $post['new_password'];
		
		$np = $this->authentication->encrypt_password($new_password);
		$op = $this->authentication->encrypt_password($old_password);
		$data = array(
				'password'=> $np
		);
		$this->db->select('account_id');
		$this->db->from('employee');
		$this->db->where('emp_id',$emp_id);
		$query = $this->db->get();
		$result = $query->row();
		$account_id = $result->account_id;
		
		
		$where = array('password'=> $op,'account_id'=>$account_id);
		
		$this->edb->where($where);
		$this->edb->update('accounts',$data);
		
		if($this->db->affected_rows() == "1"){
			$temp = array('result' => 1);
		
		}else{
			$temp = array('result' => 0);
		}
		
		echo json_encode($temp);
	}
	public function verify(){
		$post = $this->input->post();
	
		$verification_code = $post['verification_code'];
		#$verification_code =$this->uri->segment(4);
		if($verification_code){
			$veri = $this->check_verification_code($verification_code);	
			if($veri){	
				$vcode = $this->update_verification_code($verification_code);
				$result = array(
						'result'=>1
				);
				echo json_encode($result);
				return false;
			}else{
				$result = array(
						'result'=>0
				);
				echo json_encode($result);
				return false;
			}
		}else{
			$result = array(
					'result'=>0
			);
			echo json_encode($result);
			return false;
		}
	}
	
	/**
	 * UPDATED VERIFICATION CODE
	 * CHECK VERIFICATION CODE
	 * @param int $verification_code
	 */
	public function update_verification_code($verification_code){
		if($verification_code){
			$where = array(
					'verification_code' => $verification_code
			);
			$this->edb->where($where);
			$field = array(
					'verified_status' => 'verified',
					'verification_code' => date("His")
			);
			$this->edb->update('accounts',$field);
		}else{
			return false;
		}
	}
	
	public function check_verification_code($verification_code){
		if($verification_code){
			$where = array(
					'a.deleted'	=>'0',
					'e.status'	=>'Active',
					'a.verification_code' =>$verification_code
			);
			$this->edb->where($where);
			$this->edb->join("employee AS e",'e.account_id=a.account_id','INNER');
			$query = $this->edb->get("accounts AS a");
			$row = $query->row();
			return $row;
		}else{
			return false;
		}
	}
	
	# new compensation profile page of employee
	public function compensation_history()
	{
		$prof_info = employee_who($this->account_id,$this->company_id);
		$compensacion_historia = $this->mobile->get_employee_compensation_history($this->company_id, $prof_info->emp_id);
		
		if($compensacion_historia) {
			echo json_encode($compensacion_historia);
			return false;
		} else {
			return false;
		}
		
	}
	
	public function employment_history(){
		
		$employee_fulldetails = employee_who_fulldetails($this->account_id, $this->company_id);
		$employee_payroll_info_id = $employee_fulldetails->employee_payroll_information_id;
		
		$emp_history = $this->mobile->get_employee_employment_history($this->emp_id,$employee_payroll_info_id,$this->company_id);
		
		if($emp_history) {
			echo json_encode($emp_history);
			return false;
		} else {
			return false;
		}
	}
	
	public function get_direct_to_employee() {
        $emp_id = $this->emp_id;
        $comp_id = $this->company_id;
        
        $get_employee_direct_reports = get_all_employee_direct_reports_v2($emp_id,$comp_id, 5);
	    // $get_employee_direct_reports = get_all_employee_direct_reports($emp_id,$comp_id, 5);
        // $get_employee_direct_reports_all = get_all_employee_direct_reports($emp_id,$comp_id);
        
        if ($get_employee_direct_reports) {
            $new_get_employee_direct_reports = array();
            foreach ($get_employee_direct_reports as $row) {

                if ($row->profile_image) {
                    array_push(
                        $new_get_employee_direct_reports,
                        array(
                            'account_id' => $row->account_id,
                            'profile_image' => base_url()."uploads/companies/".$comp_id."/".$row->profile_image
                        )
                    );
                } else {
                    array_push(
                        $new_get_employee_direct_reports,
                        array(
                            'account_id' => $row->account_id,
                            'profile_image' => ""
                        )
                    );
                }                
            }
            $get_employee_direct_reports = $new_get_employee_direct_reports;
        }
        
	    if($get_employee_direct_reports) {
	        echo json_encode(array("result" => "1", "employees" => $get_employee_direct_reports));
	        return false;
	    } else {
	        echo json_encode(array("result" => "0", "employees" => "", "count" => 0));
	        return false;
	    }
    }
    
    
	
}