<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    /**
     *	Helper : application emailer helper version 1
     *	Author : John Fritz Marquez <fritzified.gamer@gmail.com>
     *	Usage  : Approval and application emailer Only
     */

    function emp_leave_app_notification($token = NULL, $leave_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $next_approver = "", $who = "" , $withlink = "No", $level_token = "",$appr_id = ""){
        $CI =& get_instance();
        $CI->load->model('approval_group_model','agm');
        $CI->load->model('approve_leave_model','leave');
        $CI->load->model('employee_model','employee');
        $CI->load->model('employee_leaves_model','elmod');
        
        $leave_information = $CI->agm->leave_information($leave_ids);
        if($leave_information != FALSE){
            $fullname           = ucfirst($leave_information->first_name)." ".ucfirst($leave_information->last_name);
            $date_applied       = date("F d, Y",strtotime($leave_information->date_filed));
            $leave_type         = $leave_information->leave_type;
            $concat_start_date  = date("D, d-M-y | h:i A",strtotime($leave_information->date_start));
            $concat_end_date    = date("D, d-M-y | h:i A",strtotime($leave_information->date_end));
            $subject_date       = date("d M",strtotime($leave_information->shift_date));
            $reason             = $leave_information->reasons;
            $attachm            = $leave_information->required_file_documents;
            $leave_request_type = ($leave_information->leave_request_type == "" || $leave_information->leave_request_type == null) ? "Full Day" : $leave_information->leave_request_type;
            $exclude_lunch_hour = $leave_information->exclude_lunch_break;
            
            $check_work_type = $CI->employee->work_schedule_type($leave_information->work_schedule_id, $comp_id);
            if ($check_work_type == "Flexible Hours") {
                $concat_start_date  = date("D, d-M-y",strtotime($leave_information->date_start));
                $concat_end_date    = date("D, d-M-y",strtotime($leave_information->date_end));
            }
            
            $req = "";
            if ($attachm) {
                $attachm = explode(";", $attachm);
                foreach ($attachm as $akey=>$aval) {
                    $base64_comp_id = base64_encode($comp_id);
                    $base64_comp_id = str_replace("=", "", $base64_comp_id);
                    #if (file_exists('/uploads/companies/'.$this->company_id.'/'.$aval)) {
                    $req .= anchor(base_url()."download_leave_docs/leave_required_docs/fd/".$aval."/".$base64_comp_id,"Download File",array("class"=>"download_this"));
                    #}
                }
            }
            
            $eff_date                   = $CI->leave->get_leave_eff_date($leave_information->leave_type_id,$leave_information->company_id,$leave_information->emp_id,"effective_date");
            $effective_start_date_by    = $CI->leave->get_leave_restriction($leave_information->leave_type_id,'effective_start_date_by');
            $effective_start_date       = $CI->leave->get_leave_restriction($leave_information->leave_type_id,'effective_start_date');
            $leave_credits_display      = $CI->employee->leave_credits_display($comp_id,$emp_id,$leave_information->leave_type_id);
            $paid_leave                 = $CI->leave->get_leave_restriction($leave_information->leave_type_id,'paid_leave');
            
            if($effective_start_date_by != null && $effective_start_date != null) {
                if(date('Y-m-d', strtotime($leave_information->date_start)) >= $eff_date) {
                    $total_leave_request = $leave_information->total_leave_requested;
                } else {
                    $total_leave_request = $leave_information->leave_cedits_for_untitled;
                }
            } else {
                $total_leave_request = $leave_information->total_leave_requested;
            }
            
            $current_leave_balance = "";
            $used_to_date = "";
            $my_leave_units = "days";
            if($leave_credits_display) {
                $remaining_leave_credits = "";
                
                if($leave_credits_display) {
                    if($paid_leave == "no" && $leave_credits_display->leave_credits == 0) {
                        $current_leave_balance = "This leave type does not require credits.";
                    } else {
                        $remaining_leave_credits = ($leave_credits_display->remaining_leave_credits != "") ? $leave_credits_display->remaining_leave_credits : "" ;
                        $my_leave_units = $leave_credits_display->leave_units;
                        $current_leave_balance = $remaining_leave_credits.' '.$my_leave_units;
                    }
                }
                
                $year = date("Y");
                $leave_used_todate = $CI->elmod->get_leave_used_to_date($leave_information->emp_id,$leave_information->leave_type_id,$year);
                $used_to_date = $leave_used_todate ?  number_format($leave_used_todate,1,'.','') : 0;
            }
            
            $font_name  = "'Open Sans'";
            $link       = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="'.base_url().'approval/leave/index/'.$token.'/'.$level_token.'/1'.$appr_id.'0">Approve/Reject Leave Application</a>';
            
            // get who approved this application and what level
            $who_approve_tracker = "";
            $res_approver = array();
            $workflow_approvers = get_who_approve_n_reject($leave_ids, 'leave');
            
            if($workflow_approvers) {
                foreach ($workflow_approvers as $r) {
                    $workflow_level = ordinal_suffix($r->workflow_level);
                    
                    if($r->approver_id == "-99{$comp_id}"){
                        $owner_approver = get_approver_owner_info($comp_id);
                        $appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                    } else {
                        $appr_name = ucwords($r->first_name." ".$r->last_name);
                    }
                    
                    $name_of_approver = '<tr>
                                            <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"></td>
                                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">
                                                This request has been approved on the '.$workflow_level.' level by '.$appr_name.'
                                            </td>
                                        </tr>';
                    $res_approver[] = $name_of_approver;
                }
            }
            
            if($res_approver) {
                $who_approve_tracker = implode(" ", $res_approver);
            }
            
            $leave_approval_list = "";
            if($who == "Approver" && $next_approver != "") { // next approver
                $waiting = '<h2 style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">It&rsquo;s your turn, '.$approver_full_name.'.</h2><br>
                            <p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">'.$fullname.' request for '.$leave_type.' and is pending your approval.</p>';
                
                $subject = $fullname."'s ".$leave_type." on ".$subject_date." is pending your approval";
                
                $leave_approval_list = '<tr>
                                            <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Leave Approval</td>
                                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;"></td>
                                        </tr>
                                        '.$who_approve_tracker.'';
                
            } else { // first approver/after filling application
                $waiting = '<h2 style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi '.$approver_full_name.',</h2><br>
                            <p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">'.$fullname.' has applied for '.$total_leave_request.' '.$my_leave_units.' of '.$leave_type.', your approval is required.</p>';
                #$link = '';
                
                $subject = $subject_date.' Leave Application submitted by '.$fullname;
            }
            
            if($withlink == "No"){
                $link = '';
                
            }
            
            $download_link = "";
            if($req != "") {
                $download_link .= '<tr>
                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Required Document:</td>
                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$req.'</td>
                                </tr>
                                <tr>
                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"></td>
                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#FF0000; padding-bottom:18px;">
                                        Note: The attached file is intended for approval purposes.
                                        This file will remain available for you to download for approximately 30 days,
                                        after which it will be deleted to make way for other people&acute;s file upload requests.
                                        Please make sure you download the attached file and save it on your file repository/storage.
                                    </td>
                                </tr>';
            }
            
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            
            $CI->load->library('email',$config);
            $CI->email->initialize($config);
            $CI->email->set_newline("\r\n");
            $CI->email->from(notifications_ashima_email(),'Ashima');
            $CI->email->to($email);
            $CI->email->subject($subject);
            
            $CI->email->message('
                <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
                <html lang="en">
                    <head>
                        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                        <meta name="format-detection" content="telephone=no">
                        <title>Leave Application</title>
                        <style type="text/css">
                            .ReadMsgBody {width: 100%; background-color: #ebebeb;}
                            .ExternalClass {width: 100%; background-color: #ebebeb;}
                            .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
                            body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
                            body {margin:0;padding:0;}
                            table {border-spacing:0;}
                            table td {border-collapse:collapse;}
                            .yshortcuts a {border-bottom: none !important;}
                        </style>
                    </head>
                    <body>
                        <table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td style="padding:30px 0 50px;" valign="top" align="center">
                                    <table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="'.(newsletter_logo($comp_id)).'" height="62" alt=" "></td>
                                        </tr>
                                        <tr>
                                            <td valign="top" align="center">
                                                <table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
                                                    <tr>
                                                        <td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
                                                            <table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
                                                                <tr>
                                                                    <td valign="top">
                                                                        '.$waiting.'
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" style="padding-top:25px;">
                                                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                
                                                                <tr>
                                                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Leave Type:</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$leave_type.'</td>
                                                                </tr>

                                                                <tr>
                                                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Leave Request Type:</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$leave_request_type.'</td>
                                                                </tr>

                                                                <tr>
                                                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Leave Start Date:</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$concat_start_date.'</td>
                                                                </tr>
                                                                <tr>
                                                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Leave End Date</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$concat_end_date.'</td>
                                                                </tr>
                                                                <tr>
                                                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Half Day Leave (exclude lunch hour):</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$exclude_lunch_hour.'</td>
                                                                </tr>
                                                                <tr>
                                                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Total Requested Leave:</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$total_leave_request.' '.$my_leave_units.'</td>
                                                                </tr>
                                                                <tr>
                                                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Reason:</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$reason.'</td>
                                                                </tr>
                
                                                                <tr>
                                                                    <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.date("D, d-M-y",strtotime($date_applied)).'</td>
                                                                </tr>

                                                                <tr>
                                                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px; font-style: italic;color: #888; text-decoration: underline;" valign="top">'.$leave_type.'</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px; font-style: italic;color: #888;"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px; font-style: italic;color: #888;" valign="top">Leave Used:</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px; font-style: italic;color: #888;">'.$used_to_date.'</td>
                                                                </tr>
                                                                <tr>
                                                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px; font-style: italic;color: #888;" valign="top">Leave Balance:</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px; font-style: italic;color: #888;">'.$current_leave_balance.'</td>
                                                                </tr>
                
                                                                '.$download_link.'
                
                                                                '.$leave_approval_list.'
                                                                <tr>
                                                                    <td>&nbsp;</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">
                                                                        '.$link.'
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
                                    <table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; '.date('Y').' Konsum Technologies. All Rights Reserved.</td>
                                            <td valign="top"><img src="'.base_url().'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </body>
                </html>
                ');
            
            if($CI->email->send()){
                return true;
            }else{
                return false;
            }
        } else {
            show_error("Invalid token");
        }
    }

    function emp_leave_notify_staff($leave_ids = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "" , $withlink = "No", $level_token = "",$appr_id = "", $status = "Approved", $notify_admin = ""){
        $CI =& get_instance();
        $CI->load->model("employee/todo_leave_model","todo_leave");
        $CI->load->model("workforce/approve_leave_model","leave");
        $CI->load->model('employee/employee_model','employee');
        $CI->load->model('workforce/employee_leaves_model','elmod');
        
        $leave_information = $CI->todo_leave->leave_information($leave_ids,"late");
        if($leave_information != FALSE){
            $fullname           = ucfirst($leave_information->first_name)." ".ucfirst($leave_information->last_name);
            $date_applied       = date("F d, Y",strtotime($leave_information->date_filed));
            $leave_type         = $leave_information->leave_type;
            $concat_start_date  = date("D, d-M-y",strtotime($leave_information->date_start));
            $concat_end_date    = date("D, d-M-y",strtotime($leave_information->date_end));
            $subject_date       = date("d M",strtotime($leave_information->shift_date));
            $reason             = $leave_information->reasons;
            
            $eff_date                   = $CI->leave->get_leave_eff_date($leave_information->leave_type_id,$leave_information->company_id,$leave_information->emp_id,"effective_date");
            $effective_start_date_by    = $CI->leave->get_leave_restriction($leave_information->leave_type_id,'effective_start_date_by');
            $effective_start_date       = $CI->leave->get_leave_restriction($leave_information->leave_type_id,'effective_start_date');
            $leave_credits_display      = $CI->employee->leave_credits_display($comp_id,$emp_id,$leave_information->leave_type_id);
            $paid_leave                 = $CI->leave->get_leave_restriction($leave_information->leave_type_id,'paid_leave');
            
            if($effective_start_date_by != null && $effective_start_date != null) {
                if(date('Y-m-d', strtotime($leave_information->date_start)) >= $eff_date) {
                    $total_leave_request = $leave_information->total_leave_requested;
                } else {
                    $total_leave_request = $leave_information->leave_cedits_for_untitled;
                }
            } else {
                $total_leave_request = $leave_information->total_leave_requested;
            }
            
            $my_leave_units = "days";
            if($leave_credits_display) {
                $remaining_leave_credits = "";
                $my_leave_units = "";
                
                if($leave_credits_display) {
                    if($paid_leave == "no" && $leave_credits_display->leave_credits == 0) {
                        $current_leave_balance = "This leave type does not require credits.";
                    } else {
                        $remaining_leave_credits = ($leave_credits_display->remaining_leave_credits != "") ? $leave_credits_display->remaining_leave_credits : "" ;
                        $my_leave_units = $leave_credits_display->leave_units;
                        $current_leave_balance = $remaining_leave_credits.' '.$my_leave_units;
                    }
                }
                
                $year = date("Y");
                $leave_used_todate = $CI->elmod->get_leave_used_to_date($leave_information->emp_id,$leave_information->leave_type_id,$year);
                $used_to_date = $leave_used_todate ?  number_format($leave_used_todate,1,'.','') : 0;
            }
            
            if($who == "Approver"){
                if($withlink == "No"){
                    $waiting = "";
                }
            } else {
                $pieces = explode("/", $approver_full_name);
                $approver_full_name = $pieces[0];
                
                if($who =="last"){
                    #$waiting = "";
                }elseif($who != "not"){
                    if($pieces[1]){
                        $current = $pieces[1];
                    }
                }
            }
            
            // get who approved this application and what level
            $who_approve        = $last_approver;
            $res_approver       = array();
            $workflow_approvers = get_who_approve_n_reject($leave_ids, 'leave');
            $la_count           = count($workflow_approvers);
            $la_no              = 0;
            
            if($workflow_approvers) {
                foreach ($workflow_approvers as $r) {
                    $la_no++;
                    if($r->approver_id == "-99{$comp_id}"){
                        $owner_approver = get_approver_owner_info($comp_id);
                        $appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                    } else {
                        $appr_name = ucwords($r->first_name." ".$r->last_name);
                    }
                    
                    $name_of_approver = $appr_name.' (L'.$r->workflow_level.') |';
                    if($la_count == $la_no) {
                        $name_of_approver = $appr_name.' (L'.$r->workflow_level.')';
                    }
                    
                    $res_approver[] = $name_of_approver;
                }
            }
            
            if($res_approver) {
                $who_approve = implode(" ", $res_approver);
            }
            
            $font_name = "'Open Sans'";
            
            if($status == "Rejected") {
                $body_p = '<p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Sorry, '.ucfirst($leave_information->first_name).'.</p><br>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">
                            Your '.$total_leave_request.' '.$my_leave_units.' '.$leave_type.' on '.$concat_start_date.' to '.$concat_end_date.' has been denied by '.$last_approver.'.
                        </p><br><br>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Remarks :</p><br>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">'.$leave_information->note.'</p>';
                
                $subject = $subject_date.' Leave Request - Denied';
            } else {
                if($notify_admin == "Yes") {
                    $body_p = '<p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Heads Up!</p><br>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">
                            '.$fullname.'&rsquo;s '.$total_leave_request.' '.$my_leave_units.' '.$leave_type.' on '.$concat_start_date.' to '.$concat_end_date.' has been '.lcfirst($status).' by '.$who_approve.'.
                        </p>';
                    
                    $subject = $fullname.' '.$subject_date.' Leave Request - '.$status;
                } else {
                    $body_p = '<h2 style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi '.$approver_full_name.',</h2>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Yay! Your '.$leave_type.' has been approved!</p><br>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">
                            Your '.$total_leave_request.' '.$my_leave_units.' '.$leave_type.' on '.$concat_start_date.' to '.$concat_end_date.' has been '.lcfirst($status).' by '.$who_approve.'.
                        </p><br><br>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Remarks :</p><br>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">'.$leave_information->note.'</p>';
                    
                    $subject = $subject_date.' Leave Request - '.$status;
                }
            }
            
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            
            $CI->load->library('email',$config);
            $CI->email->initialize($config);
            $CI->email->set_newline("\r\n");
            $CI->email->from(notifications_ashima_email(),'Ashima');
            $CI->email->to($email);
            $CI->email->subject($subject);
            
            $CI->email->message('
    				<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
                			<html lang="en">
                				<head>
                					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                					<meta name="format-detection" content="telephone=no">
                					<title>Leave Application</title>
                					<style type="text/css">
                						.ReadMsgBody {width: 100%; background-color: #ebebeb;}
                						.ExternalClass {width: 100%; background-color: #ebebeb;}
                						.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
                						body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
                						body {margin:0;padding:0;}
                						table {border-spacing:0;}
                						table td {border-collapse:collapse;}
                						.yshortcuts a {border-bottom: none !important;}
                					</style>
                				</head>
                				<body>
                					<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
                						<tr>
                							<td style="padding:30px 0 50px;" valign="top" align="center">
                								<table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
                									<tr>
                							        	<td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="'.(newsletter_logo($comp_id)).'" height="62" alt=" "></td>
                							        </tr>
                									<tr>
                										<td valign="top" align="center">
                											<table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
                												<tr>
                													<td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
                														<table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
                															<tr>
                																<td valign="top">
                                                                                    '.$body_p.'
                																</td>
                															</tr>
                														</table>
                													</td>
                												</tr>
                
                											</table>
                										</td>
                									</tr>
                								</table>
                							</td>
                						</tr>
                						<tr>
                							<td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
                								<table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
                									<tr>
                										<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; '.date('Y').' Konsum Technologies. All Rights Reserved.</td>
                										<td valign="top"><img src="'.base_url().'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
                									</tr>
                								</table>
                							</td>
                						</tr>
                					</table>
                				</body>
            			     </html>
        			     ');
            
            if($CI->email->send()){
                return true;
            }else{
                return false;
            }
        } else {
            show_error("Invalid token");
        }
    }
    
    function disbursement_notify_staff($disbursement_application_id,$company_id,$email = NULL, $approver_full_name = "") {
        /*$CI =& get_instance();
        $CI->load->model("workforce/approve_disbursement_model","disburse");
        
        $get_disbursement_application_row = $CI->disburse->get_disbursement_application_row($company_id,$disbursement_application_id);
        
        if($get_disbursement_application_row){
            $disbursement_id = $disbursement_application_id;
            $disbursement_notes = $get_disbursement_application_row->remarks;
            $font_name = "'Open Sans'";
            $subject = "Oh no! Disbursement was rejected.";
            
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            
            $CI->load->library('email',$config);
            $CI->email->initialize($config);
            $CI->email->set_newline("\r\n");
            $CI->email->from(notifications_ashima_email(),'Ashima');
            $CI->email->to($email);
            $CI->email->subject($subject);
            $font_name = "'Open Sans'";
            $CI->email->message('
				<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
				<html lang="en">
					<head>
						<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
						<meta name="format-detection" content="telephone=no">
						<title>Disbursement Approval</title>
						<style type="text/css">
							.ReadMsgBody {width: 100%; background-color: #ebebeb;}
							.ExternalClass {width: 100%; background-color: #ebebeb;}
							.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
							body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
							body {margin:0;padding:0;}
							table {border-spacing:0;}
							table td {border-collapse:collapse;}
							.yshortcuts a {border-bottom: none !important;}
						</style>
					</head>
					<body>
						<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
							<tr>
								<td style="padding:30px 0 50px;" valign="top" align="center">
									<table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
										<tr>
								            	<td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="'.(newsletter_logo($company_id)).'" height="62" alt=" "></td>
								        </tr>
										<tr>
											<td valign="top" align="center">
												<table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
													<tr>
														<td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
															<table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
																<tr>
																	<td valign="top">
																		<h1 style="font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; font-size:14px; margin:0 0 25px; line-height:22px;">Hi '.$approver_full_name.',</h1>
																		<p style="font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; font-size:12px; margin:0 0 18px; line-height:22px;">
                                                                            Disbursement Run Request No. '.$disbursement_id.' was rejected by '.$approver_full_name.'. Please correct any issues noted below :
                                                                            </p>
                                                                        <p style="font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; font-size:12px; margin:0 0 10px; line-height:22px; font-weight:600;">Notes:</p>
																	</td>
																</tr>
                                                                <tr>
                                    					                <td valign="top" style="padding-bottom:30px;"><table width="500" style="width:500px; margin-left:40px;" border="0" cellspacing="0" cellpadding="0">
                                    					                    <tr>
                                    					                      <td valign="top"><img style="display:block;" src="'.base_url().'assets/theme_2015/newsletter/images/img-quotehead.jpg" width="500" height="27" alt=" "></td>
                                    					                    </tr>
                                    					                    <tr>
                                    					                      <td valign="top" style="background-color:#f2f2f2; padding:0 17px;">
                                    											<p style="font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; font-size:12px; margin:0 0 18px; line-height:18px;">
                                    												'.$disbursement_notes.'
                                    											</p></td>
                                    					                    </tr>
                                    					                  </table></td>
                            					                   </tr>
															</table>
														</td>
													</tr>
                                                    
												</table>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
									<table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
										<tr>
											<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; '.date('Y').' Konsum Technologies. All Rights Reserved.</td>
											<td valign="top"><img src="'.base_url().'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</body>
				</html>
			');
            
            if($CI->email->send()){
                return true;
            }else{
                return false;
            }
        }
        else{
            show_error("Invalid token");
        }*/
    }
    
    function emp_open_shift_timesheet_app_notification($token, $approval_os_id, $comp_id, $emp_id, $level_token, $email, $approver_full_name, $next_approver = "", $who = "" , $withlink = "No",$appr_id = ""){
        $CI =& get_instance();
        $CI->load->model("employee/todo_timein_model","todo_timein");
        $CI->load->model('employee/open_shift_timesheet_model','ostm');
        
        if($approval_os_id) {
            $get_approval_open_shift_timein = $CI->ostm->get_approval_open_shift_timein($approval_os_id);
            
            if($get_approval_open_shift_timein) {
            
                $emp = get_employee_details_by_empid($emp_id);
                if($emp) {
                    $fullname = ucfirst($emp->first_name)." ".ucfirst($emp->last_name);
                    $subject_date       = "";
                    
                    $font_name  = "'Open Sans'";
                    $link       = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="'.base_url().'approval/open_shift_timesheet/index/'.$token.'/'.$level_token.'/1'.$appr_id.'0?os_todo=1&approval_id='.$approval_os_id.'&emp_id='.$emp_id.'&last_approver='.$get_approval_open_shift_timein->last_approver.'&comp_id='.$comp_id.'&lv='.$get_approval_open_shift_timein->level.'">Click here to view timesheet</a>';
                                        
                    if($who == "Approver" && $next_approver != "") { // next approver
                        $waiting = '<h2 style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">It&rsquo;s your turn, '.$approver_full_name.'.</h2><br>
                            <p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">'.$fullname.' request for timesheet adjustment and is pending your approval.</p>';
                        
                        $subject = $fullname."'s timesheet adjustment is pending your approval";
                        
                    } else { // first approver/after filling application
                        $waiting = '<h2 style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi '.$approver_full_name.',</h2><br>
                            <p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">'.$fullname.' has applied for timesheet adjustment, your approval is required.</p>';
                        #$link = '';
                        
                        $subject = $subject_date.' Timesheet Adjustment submitted by '.$fullname;
                    }
                    
                    if($withlink == "No"){
                        $link = '';
                    }
                    
                    
                    $config['protocol'] = 'sendmail';
                    $config['wordwrap'] = TRUE;
                    $config['mailtype'] = 'html';
                    $config['charset'] = 'utf-8';
                    
                    $CI->load->library('email',$config);
                    $CI->email->initialize($config);
                    $CI->email->set_newline("\r\n");
                    $CI->email->from(notifications_ashima_email(),'Ashima');
                    $CI->email->to($email);
                    $CI->email->subject($subject);
                    
                    $CI->email->message('
                <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
                <html lang="en">
                    <head>
                        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                        <meta name="format-detection" content="telephone=no">
                        <title>Leave Application</title>
                        <style type="text/css">
                            .ReadMsgBody {width: 100%; background-color: #ebebeb;}
                            .ExternalClass {width: 100%; background-color: #ebebeb;}
                            .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
                            body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
                            body {margin:0;padding:0;}
                            table {border-spacing:0;}
                            table td {border-collapse:collapse;}
                            .yshortcuts a {border-bottom: none !important;}
                        </style>
                    </head>
                    <body>
                        <table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td style="padding:30px 0 50px;" valign="top" align="center">
                                    <table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="'.(newsletter_logo($comp_id)).'" height="62" alt=" "></td>
                                        </tr>
                                        <tr>
                                            <td valign="top" align="center">
                                                <table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
                                                    <tr>
                                                        <td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
                                                            <table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
                                                                <tr>
                                                                    <td valign="top">
                                                                        '.$waiting.'
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" style="padding-top:25px;">
                                                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                                <tr>
                                                                    <td>&nbsp;</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">
                                                                        '.$link.'
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                        
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
                                    <table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; '.date('Y').' Konsum Technologies. All Rights Reserved.</td>
                                            <td valign="top"><img src="'.base_url().'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </body>
                </html>
                ');
                    
                    if($CI->email->send()){
                        return true;
                    }else{
                        return false;
                    }
                } else {
                    show_error("Invalid token");
                }
            }
        }
    }
    
    function emp_open_shift_timesheet_notify_staff($approval_os_id, $comp_id, $emp_id, $email, $approver_full_name,$notify_admin="") {
        $CI =& get_instance();
        $CI->load->model("employee/todo_timein_model","todo_timein");
        $CI->load->model('employee/open_shift_timesheet_model','ostm');
        
        if($approval_os_id) {
            $get_approval_open_shift_timein = $CI->ostm->get_approval_open_shift_timein($approval_os_id);
            
            if($get_approval_open_shift_timein) {
                
                $emp = get_employee_details_by_empid($emp_id);
                if($emp) {
                    $fullname = ucfirst($emp->first_name)." ".ucfirst($emp->last_name);
                    $subject_date       = "";
                    $status = $get_approval_open_shift_timein->application_status;
                    
                    $font_name  = "'Open Sans'";
                    $link       = '';                    
                    
                    if($status == "rejected") {
                        $body_p = '<p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Sorry, '.ucfirst($emp->first_name).'.</p><br>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">
                            Your attendance adjustment has been rejected by '.$approver_full_name.'.
                        </p>';
                        
                        $subject = "Oh no! Your attendance adjustment was rejected.";
                    } else {
                        if($notify_admin == "Yes") {
                            $body_p = '<p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Heads Up!</p><br>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">
                            '.$fullname.'&rsquo;s attendance adjustment has been approved by '.$approver_full_name.'.
                        </p>';
                            
                            $subject = "Attendance Adjustment by {$fullname}'s has been approved";
                        } else {
                            $body_p = '<h2 style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi '.$fullname.',</h2>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Yay! Your attendance adjustment has been approved by '.$approver_full_name.'!</p>';
                            
                            $subject = 'Yay! Your attendance adjustment request was approved';
                        }
                    }
                    
                    $config['protocol'] = 'sendmail';
                    $config['wordwrap'] = TRUE;
                    $config['mailtype'] = 'html';
                    $config['charset'] = 'utf-8';
                    
                    $CI->load->library('email',$config);
                    $CI->email->initialize($config);
                    $CI->email->set_newline("\r\n");
                    $CI->email->from(notifications_ashima_email(),'Ashima');
                    $CI->email->to($email);
                    $CI->email->subject($subject);
                    
                    $CI->email->message('
    				<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
                			<html lang="en">
                				<head>
                					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                					<meta name="format-detection" content="telephone=no">
                					<title>Leave Application</title>
                					<style type="text/css">
                						.ReadMsgBody {width: 100%; background-color: #ebebeb;}
                						.ExternalClass {width: 100%; background-color: #ebebeb;}
                						.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
                						body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
                						body {margin:0;padding:0;}
                						table {border-spacing:0;}
                						table td {border-collapse:collapse;}
                						.yshortcuts a {border-bottom: none !important;}
                					</style>
                				</head>
                				<body>
                					<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
                						<tr>
                							<td style="padding:30px 0 50px;" valign="top" align="center">
                								<table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
                									<tr>
                							        	<td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="'.(newsletter_logo($comp_id)).'" height="62" alt=" "></td>
                							        </tr>
                									<tr>
                										<td valign="top" align="center">
                											<table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
                												<tr>
                													<td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
                														<table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
                															<tr>
                																<td valign="top">
                                                                                    '.$body_p.'
                																</td>
                															</tr>
                														</table>
                													</td>
                												</tr>
                        
                											</table>
                										</td>
                									</tr>
                								</table>
                							</td>
                						</tr>
                						<tr>
                							<td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
                								<table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
                									<tr>
                										<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; '.date('Y').' Konsum Technologies. All Rights Reserved.</td>
                										<td valign="top"><img src="'.base_url().'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
                									</tr>
                								</table>
                							</td>
                						</tr>
                					</table>
                				</body>
            			     </html>
        			     ');
                    
                    if($CI->email->send()){
                        return true;
                    }else{
                        return false;
                    }
                } else {
                    show_error("Invalid token");
                }
            }
        }
    }
    
    function timesheet_auto_reject($ids, $comp_id = NULL, $email = NULL, $tardiness_rule_migrated_v3 = false, $rejection_type="ppa") {
        $CI =& get_instance();
        $CI->load->model("employee/todo_timein_model","todo_timein");
        
        $timein_information = $CI->todo_timein->get_employee_time_in($ids);
            
        if($timein_information != FALSE){
            $flag = $timein_information->flag_add_logs;
            $hours_cat = "";
            
            $check_break_1_in_min = false;
            $check_break_2_in_min = false;
            
            $fullname = ucfirst($timein_information->first_name)." ".ucfirst($timein_information->last_name);
            
            $break_1_start_date_time = "none";
            $break_1_end_date_time   = "none";
            $break_2_start_date_time = "none";
            $break_2_end_date_time   = "none";
            
            $new_break_1_start_date_time = "none";
            $new_break_1_end_date_time   = "none";
            $new_break_2_start_date_time = "none";
            $new_break_2_end_date_time   = "none";
            $shift_date = $timein_information->date;
            $auto_reject_title_date = idates($shift_date);
            $para_content = "";
            
            if($flag==0){
                $date_applied = date("F d, Y",strtotime($timein_information->change_log_date_filed));
                $time_in = ($timein_information->time_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->time_in)) : "none";
                $lunch_out = ($timein_information->lunch_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->lunch_out)) : "none";
                $lunch_in = ($timein_information->lunch_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->lunch_in)) : "none";
                $time_out = ($timein_information->time_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->time_out)) : "none";
                
                $new_time_in = ($timein_information->change_log_time_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_time_in)) : "none";
                $new_lunch_out = ($timein_information->change_log_lunch_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_lunch_in)) : "none";
                $new_lunch_in = ($timein_information->change_log_lunch_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_lunch_out)) : "none";
                $new_time_out = ($timein_information->change_log_time_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->change_log_time_out)) : "none";
                
                $hours_cat = "Attendance Adjustment";
                $title_line = "Attendance Adjustment";
                $subject = "Auto Rejected : {$auto_reject_title_date} {$title_line}.";
                
                
                if($rejection_type == "ppa") {
                    $para_content = "Oh snap! Your {$auto_reject_title_date} Attendance Adjustment request has been auto-rejected due to time lapse.
                                The pay run has been closed while this request remains pending the approval of your manager.
                                Please coordinate directly with your manager/HR for further instructions or if you have any questions.";
                } else {
                    $para_content = "Oh snap! Your {$auto_reject_title_date} Attendance Adjustment request has been auto-rejected due to missing logs.
                                Please refile again.";
                }
            }elseif($flag==1){
                $date_applied = date("F d, Y",strtotime($timein_information->change_log_date_filed));
                $time_in = ($timein_information->time_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->time_in)) : "none";
                $lunch_out = ($timein_information->lunch_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->lunch_out)) : "none";
                $lunch_in = ($timein_information->lunch_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->lunch_in)) : "none";
                $time_out = ($timein_information->time_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->time_out)) : "none";
                
                $hours_cat = "Attendance Logs";
                $title_line = "New Timesheet";
                $subject = "Auto Rejected : {$auto_reject_title_date} {$title_line}.";
                
                if($rejection_type == "ppa") {
                    $para_content = "Oh snap! Your {$auto_reject_title_date} New Timesheet Submission has been auto-rejected due to time lapse.
                                The pay run has been closed while this request remains pending the approval of your manager.
                                Please coordinate directly with your manager/HR for further instructions or if you have any questions.";
                } else {
                    $para_content = "Oh snap! Your {$auto_reject_title_date} New Timesheet Submission has been auto-rejected due to missing logs.
                                Please refile again";
                }
                
            }elseif($flag==2){
                $date_applied = date("F d, Y",strtotime($timein_information->change_log_date_filed));
                $time_in = ($timein_information->time_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->time_in)) : "none";
                $lunch_out = ($timein_information->lunch_in != NULL) ? date("F d, Y h:i A",strtotime($timein_information->lunch_in)) : "none";
                $lunch_in = ($timein_information->lunch_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->lunch_out)) : "none";
                $time_out = ($timein_information->time_out != NULL) ? date("F d, Y h:i A",strtotime($timein_information->time_out)) : "none";
                
                $hours_cat = "Location Base Login";
                $title_line = "Location Base Login";
                $subject = "Auto Rejected : {$auto_reject_title_date} {$title_line}.";
                
                if($rejection_type == "ppa") {
                    $para_content = "Oh snap! Your {$auto_reject_title_date} Mobile Timesheet Submission has been auto-rejected due to time lapse.
                                The pay run has been closed while this request remains pending the approval of your manager.
                                Please coordinate directly with your manager/HR for further instructions or if you have any questions.";
                } else {
                    $para_content = "Oh snap! Your {$auto_reject_title_date} Mobile Timesheet Submission has been auto-rejected due to missing logs.
                                Please refile again";
                }
            }
            
            $total_hours = $timein_information->total_hours;
            $total_hours_required = $timein_information->total_hours_required;
            $tardiness_min = $timein_information->tardiness_min;
            $undertime_min = $timein_information->undertime_min;
            $reason = $timein_information->reason;
            $font_name = "'Open Sans'";
            
            $message_body_additional_break_add = "";
            $message_body_additional_break_change = "";
            
            if($tardiness_rule_migrated_v3) {
                $message_body_additional_break_add1 = "";
                $message_body_additional_break_add2 = "";
                
                if ($check_break_1_in_min) {
                    $message_body_additional_break_add1 = '
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">ADDITIONAL BREAKS</td>
        						<td>
								<hr style="color: #ccc !important;margin-top: -10px !important;">
        						</td>
        					</tr>
                                
    						<tr>
    							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">First Break Out:</td>
    							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$break_1_start_date_time.'</td>
    						</tr>
    						<tr>
    							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">First Break In:</td>
    							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$break_1_end_date_time.'</td>
    						</tr>
    				    ';
                }
                
                if ($check_break_2_in_min) {
                    $message_body_additional_break_add2 = '
    						<tr>
    							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Second Break Out:</td>
    							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$break_2_start_date_time.'</td>
    						</tr>
    						<tr>
    							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Second Break In:</td>
    							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$break_2_end_date_time.'</td>
    						</tr>
    				    ';
                }
                
                $message_body_additional_break_add = $message_body_additional_break_add1.' '.$message_body_additional_break_add2;
                
                $message_body_additional_break_change1 = "";
                $message_body_additional_break_change2 = "";
                
                if ($check_break_1_in_min) {
                    $message_body_additional_break_change1 = '
                        <tr>
                            <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">ADDITIONAL BREAKS</td>
        						<td>
								<hr style="color: #ccc !important;margin-top: -10px !important;">
        						</td>
        					</tr>
                                
    						<tr>
    							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">First Break Out:</td>
    							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_break_1_start_date_time.'</td>
    						</tr>
    						<tr>
    							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">First Break In:</td>
    							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_break_1_end_date_time.'</td>
    						</tr>
    				    ';
                }
                
                if ($check_break_2_in_min) {
                    $message_body_additional_break_change2 = '
    						<tr>
    							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Second Break Out:</td>
    							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_break_2_start_date_time.'</td>
    						</tr>
    						<tr>
    							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Second Break In:</td>
    							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_break_2_end_date_time.'</td>
    						</tr>
    				    ';
                }
                
                $message_body_additional_break_change = $message_body_additional_break_change1.' '.$message_body_additional_break_change2;
            }
            
            if($flag == 0){
                $message_body = '
						<tr>
							<td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"> Applicant:</td>
							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$fullname.'</td>
						</tr>
						<tr>
							<td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date_applied.'</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">&nbsp;</td>
							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;"><strong>CHANGE FROM:</strong></td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_in.'</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_out.'</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_in.'</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_out.'</td>
						</tr>
							    
                        '.$message_body_additional_break_add.'
                            
						<tr>
							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">&nbsp;</td>
							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;"><strong>CHANGE TO:</strong></td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_time_in.'</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_lunch_out.'</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_lunch_in.'</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$new_time_out.'</td>
						</tr>
							    
                        '.$message_body_additional_break_change.'
                            
						<tr>
							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Reason:</td>
							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$timein_information->reason.'</td>
						</tr>
						<tr>
							<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
							<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$timein_information->notes.'</td>
						</tr>
				';
            }else{
                $message_body = '
					<tr>
						<td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"> Applicant:</td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$fullname.'</td>
					</tr>
					<tr>
						<td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date_applied.'</td>
					</tr>
					<tr>
						<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_in.'</td>
					</tr>
					<tr>
						<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_out.'</td>
					</tr>
					<tr>
						<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_in.'</td>
					</tr>
					<tr>
						<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_out.'</td>
					</tr>
						    
                    '.$message_body_additional_break_add.'
                        
					<tr>
						<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Remarks:</td>
						<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$timein_information->notes.'</td>
					</tr>
				';
                
            }
            
            $link = '';
            
            $font_name = "'Open Sans'";
            
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            
            $CI->load->library('email',$config);
            $CI->email->initialize($config);
            $CI->email->set_newline("\r\n");
            $CI->email->from(notifications_ashima_email(),'Ashima');
            $CI->email->to($email);
            $CI->email->subject($subject);
            $font_name = "'Open Sans'";
            $CI->email->message('
			<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
			<html lang="en">
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
					<meta name="format-detection" content="telephone=no">
					<title>'.$hours_cat.'</title>
					<style type="text/css">
						.ReadMsgBody {width: 100%; background-color: #ebebeb;}
						.ExternalClass {width: 100%; background-color: #ebebeb;}
						.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
						body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
						body {margin:0;padding:0;}
						table {border-spacing:0;}
						table td {border-collapse:collapse;}
						.yshortcuts a {border-bottom: none !important;}
					</style>
				</head>
				<body>
					<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td style="padding:30px 0 50px;" valign="top" align="center">
								<table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
									<tr>
							        	<td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="'.(newsletter_logo($comp_id)).'" height="62" alt=" "></td>
							        </tr>
									<tr>
										<td valign="top" align="center">
											<table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
												<tr>
													<td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
														<table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
															<tr>
																<td valign="top">
																	<h2 style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi '.$fullname.',</h2>
																	<p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0; text-trasnform:capitalized">'.$para_content.'</p>
																</td>
															</tr>
														</table>
													</td>
												</tr>
												<tr>
													<td valign="top" style="padding-top:25px;">
														<table width="100%" border="0" cellspacing="0" cellpadding="0">
															'.$message_body.'
															<tr>
																<td>&nbsp;</td>
																<td valign="top">
																	'.$link.'
																</td>
															</tr>
														</table>
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
								<table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; '.date('Y').' Konsum Technologies. All Rights Reserved.</td>
										<td valign="top"><img src="'.base_url().'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</body>
			</html>
		');
            if($CI->email->send()){
                return true;
            }else{
                return false;
            }
        }
        else{
            show_error("Invalid token");
        }
        
    }
    
    function emp_allowance_app_notification($token = NULL, $allowance_id = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $next_approver = "", $who = "" , $withlink = "No", $level_token = "",$appr_id = ""){
        $CI =& get_instance();
        $CI->load->model('employee/employee_v2_model','employee_v2');
        $CI->load->model('employee/employee_model','employee');
        
        $allowance_information = $CI->employee_v2->allowance_information($allowance_id);
        if($allowance_information != FALSE){
            $ws_id              = $CI->employee->emp_work_schedule($emp_id,$comp_id,date("Y-m-d", strtotime($allowance_information->application_date)));
            $check_time_in_date = check_time_in_date_v2(date("Y-m-d", strtotime($allowance_information->application_date)), $emp_id);
            
            $font_name          = "'Open Sans'";
            $fullname           = ucfirst($allowance_information->first_name)." ".ucfirst($allowance_information->last_name);
            $date_applied       = date("F d, Y",strtotime($allowance_information->date_filed));
            $allowance_name     = $allowance_information->name;
            $allowance_amnt     = $allowance_information->allowance_amount;
            $reason             = $allowance_information->reason;
            $shift_date         = idates($check_time_in_date->date);
            $time_in            = idates($check_time_in_date->time_in).' '.time12hrs($check_time_in_date->time_in);
            $lunch_out          = ($check_time_in_date->lunch_out == null) ? "" : idates($check_time_in_date->lunch_out).' '.time12hrs($check_time_in_date->lunch_out);
            $lunch_in           = ($check_time_in_date->lunch_in == null) ? "" : idates($check_time_in_date->lunch_in).' '.time12hrs($check_time_in_date->lunch_in);
            $time_out           = idates($check_time_in_date->time_out).' '.time12hrs($check_time_in_date->time_out);
            $hrs_worked         = $check_time_in_date->total_hours_required;
            $date_filed         = $allowance_information->date_filed;
            $subject_date       = date("d M",strtotime($allowance_information->application_date));
            
            $timesheet_info     = '<tr>
                                        <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Shift Date:</td>
                                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$shift_date.'</td>
                                    </tr>
    
                                    <tr>
                                        <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
                                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_in.'</td>
                                    </tr>
    
                                    <tr>
                                        <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
                                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_out.'</td>
                                    </tr>
    
                                    <tr>
                                        <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
                                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_in.'</td>
                                    </tr>

                                    <tr>
                                        <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
                                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_out.'</td>
                                    </tr>

                                    <tr>
                                        <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Hours Worked:</td>
                                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$hrs_worked.'</td>
                                    </tr>';
 
            
            
            $link       = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="'.base_url().'approval/allowance/index/'.$allowance_id.'/'.$token.'/'.$level_token.'/1'.$appr_id.'0">Approve/Reject Allowance Application</a>';
            
            // get who approved this application and what level
            $who_approve_tracker = "";
            $res_approver = array();
            $workflow_approvers = get_who_approve_n_reject($allowance_id, 'allowance');
            
            if($workflow_approvers) {
                foreach ($workflow_approvers as $r) {
                    $workflow_level = ordinal_suffix($r->workflow_level);
                    
                    if($r->approver_id == "-99{$comp_id}"){
                        $owner_approver = get_approver_owner_info($comp_id);
                        $appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                    } else {
                        $appr_name = ucwords($r->first_name." ".$r->last_name);
                    }
                    
                    $name_of_approver = '<tr>
                                            <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top"></td>
                                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">
                                                This request has been approved on the '.$workflow_level.' level by '.$appr_name.'
                                            </td>
                                        </tr>';
                    $res_approver[] = $name_of_approver;
                }
            }
            
            if($res_approver) {
                $who_approve_tracker = implode(" ", $res_approver);
            }
            
            $allowance_approval_list = "";
            if($who == "Approver" && $next_approver != "") { // next approver
                $waiting = '<h2 style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">It&rsquo;s your turn, '.$approver_full_name.'.</h2><br>
                            <p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">'.$fullname.' request for '.$allowance_name.' (allowance) with the amount of '.$allowance_amnt.' and is pending your approval.</p>';
                
                $subject = $fullname."'s ".$allowance_name." (allowance) on ".$subject_date." is pending your approval";
                
                $allowance_approval_list = '<tr>
                                            <td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Allowance Approval</td>
                                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;"></td>
                                        </tr>
                                        '.$who_approve_tracker.'';
                
            } else { // first approver/after filling application
                $waiting = '<h2 style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Dear '.$approver_full_name.',</h2><br>
                            <p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">'.$fullname.' has asked you to approve the following</p>';
                #$link = '';
                
                $subject = 'Approval Needed : '.$allowance_name.' (Allowance) request for '.$fullname;
            }
            
            if($withlink == "No"){
                $link = '';
            }
            
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            
            $CI->load->library('email',$config);
            $CI->email->initialize($config);
            $CI->email->set_newline("\r\n");
            $CI->email->from(notifications_ashima_email(),'Ashima');
            $CI->email->to($email);
            $CI->email->subject($subject);
            
            $CI->email->message('
                <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
                <html lang="en">
                    <head>
                        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                        <meta name="format-detection" content="telephone=no">
                        <title>Allowance Application</title>
                        <style type="text/css">
                            .ReadMsgBody {width: 100%; background-color: #ebebeb;}
                            .ExternalClass {width: 100%; background-color: #ebebeb;}
                            .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
                            body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
                            body {margin:0;padding:0;}
                            table {border-spacing:0;}
                            table td {border-collapse:collapse;}
                            .yshortcuts a {border-bottom: none !important;}
                        </style>
                    </head>
                    <body>
                        <table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td style="padding:30px 0 50px;" valign="top" align="center">
                                    <table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="'.(newsletter_logo($comp_id)).'" height="62" alt=" "></td>
                                        </tr>
                                        <tr>
                                            <td valign="top" align="center">
                                                <table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
                                                    <tr>
                                                        <td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
                                                            <table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
                                                                <tr>
                                                                    <td valign="top">
                                                                        '.$waiting.'
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" style="padding-top:25px;">
                                                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                
                                                                <tr>
                                                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Allowance Type:</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$allowance_name.'</td>
                                                                </tr>
                
                                                                <tr>
                                                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Allowance Amount:</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$allowance_amnt.'</td>
                                                                </tr>

                                                                '.$timesheet_info.'

                                                                <tr>
                                                                    <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Filed:</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.idates($date_filed).' '.time12hrs($date_filed).'</td>
                                                                </tr>
                
                                                                '.$allowance_approval_list.'

                                                                <tr>
                                                                    <td>&nbsp;</td>
                                                                    <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">
                                                                        '.$link.'
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
                                    <table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; '.date('Y').' Konsum Technologies. All Rights Reserved.</td>
                                            <td valign="top"><img src="'.base_url().'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </body>
                </html>
            ');
            
            if($CI->email->send()){
                return true;
            }else{
                return false;
            }
        } else {
            show_error("Invalid token");
        }
    }
    
    
    function emp_allowance_notify_staff($allowance_id = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "" , $withlink = "No", $level_token = "",$appr_id = "", $status = "Approved", $notify_admin = ""){
        $CI =& get_instance();        
        $CI->load->model('employee/employee_v2_model','employee_v2');
        $CI->load->model('employee/employee_model','employee');
        
        $allowance_information = $CI->employee_v2->allowance_information($allowance_id);
        if($allowance_information != FALSE){
            $ws_id              = $CI->employee->emp_work_schedule($emp_id,$comp_id,date("Y-m-d", strtotime($allowance_information->application_date)));
            $check_time_in_date = check_time_in_date_v2(date("Y-m-d", strtotime($allowance_information->application_date)), $emp_id);
            
            $font_name          = "'Open Sans'";
            $fullname           = ucfirst($allowance_information->first_name)." ".ucfirst($allowance_information->last_name);
            $date_applied       = date("F d, Y",strtotime($allowance_information->date_filed));
            $allowance_name     = $allowance_information->name;
            $allowance_amnt     = $allowance_information->allowance_amount;
            $reason             = $allowance_information->reason;
            $shift_date         = idates($check_time_in_date->date);
            $time_in            = idates($check_time_in_date->time_in).' '.time12hrs($check_time_in_date->time_in);
            $lunch_out          = ($check_time_in_date->lunch_out == null) ? "" : idates($check_time_in_date->lunch_out).' '.time12hrs($check_time_in_date->lunch_out);
            $lunch_in           = ($check_time_in_date->lunch_in == null) ? "" : idates($check_time_in_date->lunch_in).' '.time12hrs($check_time_in_date->lunch_in);
            $time_out           = idates($check_time_in_date->time_out).' '.time12hrs($check_time_in_date->time_out);
            $hrs_worked         = $check_time_in_date->total_hours_required;
            $date_filed         = $allowance_information->date_filed;
            $subject_date       = date("d M",strtotime($allowance_information->application_date));
            
            $timesheet_info     = '<tr>
                                        <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Shift Date:</td>
                                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$shift_date.'</td>
                                    </tr>
                                            
                                    <tr>
                                        <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time In:</td>
                                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_in.'</td>
                                    </tr>
                                            
                                    <tr>
                                        <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch Out:</td>
                                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_out.'</td>
                                    </tr>
                                            
                                    <tr>
                                        <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Lunch In:</td>
                                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$lunch_in.'</td>
                                    </tr>
                                            
                                    <tr>
                                        <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time Out:</td>
                                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$time_out.'</td>
                                    </tr>
                                            
                                    <tr>
                                        <td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Hours Worked:</td>
                                        <td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$hrs_worked.'</td>
                                    </tr>';
            
            if($who == "Approver"){
                if($withlink == "No"){
                    $waiting = "";
                }
            } else {
                $pieces = explode("/", $approver_full_name);
                $approver_full_name = $pieces[0];
                
                if($who =="last"){
                    #$waiting = "";
                }elseif($who != "not"){
                    if($pieces[1]){
                        $current = $pieces[1];
                    }
                }
            }
            
            // get who approved this application and what level
            $who_approve        = $last_approver;
            $res_approver       = array();
            $workflow_approvers = get_who_approve_n_reject($allowance_id, 'allowance');
            $la_count           = count($workflow_approvers);
            $la_no              = 0;
            
            if($workflow_approvers) {
                foreach ($workflow_approvers as $r) {
                    $la_no++;
                    if($r->approver_id == "-99{$comp_id}"){
                        $owner_approver = get_approver_owner_info($comp_id);
                        $appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                    } else {
                        $appr_name = ucwords($r->first_name." ".$r->last_name);
                    }
                    
                    $name_of_approver = $appr_name.' (L'.$r->workflow_level.') |';
                    if($la_count == $la_no) {
                        $name_of_approver = $appr_name.' (L'.$r->workflow_level.')';
                    }
                    
                    $res_approver[] = $name_of_approver;
                }
            }
            
            if($res_approver) {
                $who_approve = implode(" ", $res_approver);
            }
            
            $font_name = "'Open Sans'";
            
            if($status == "Rejected") {
                $body_p = '<p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Sorry, '.ucfirst($allowance_information->first_name).'.</p><br>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">
                            Your '.$allowance_name.' (allowance) application with the amount of '.$allowance_amnt.' has been denied by '.$last_approver.'.
                        </p><br><br>
    
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Application Date : '.idates($allowance_information->application_date).'</p><br>

                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Remarks :</p><br>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">'.$allowance_information->remarks.'</p>';
                
                $subject = $subject_date.' Allowance Application - Denied';
            } else {
                if($notify_admin == "Yes") {
                    $body_p = '<p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Heads Up!</p><br>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">
                            '.$fullname.'&rsquo;s '.$allowance_name.' (allowance) application with the amount of '.$allowance_amnt.' has been '.lcfirst($status).' by '.$who_approve.'.
                        </p>';
                    
                    $subject = $fullname.' '.$subject_date.' Allowance Application - '.$status;
                } else {
                    $body_p = '<h2 style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi '.$approver_full_name.',</h2>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Yay! Your '.$allowance_name.' (allowance)  has been approved!</p><br>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">
                            Your '.$allowance_name.' (allowance) application with the amount of '.$allowance_amnt.' has been '.lcfirst($status).' by '.$who_approve.'.
                        </p><br><br>

                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Application Date : '.idates($allowance_information->application_date).'</p><br>

                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">Remarks :</p><br>
                        <p style="font-size:14px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">'.$allowance_information->remarks.'</p>';
                    
                    $subject = $subject_date.' Allowance Application - '.$status;
                }
            }
            
            $config['protocol'] = 'sendmail';
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            
            $CI->load->library('email',$config);
            $CI->email->initialize($config);
            $CI->email->set_newline("\r\n");
            $CI->email->from(notifications_ashima_email(),'Ashima');
            $CI->email->to($email);
            $CI->email->subject($subject);
            
            $CI->email->message('
    				<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
                			<html lang="en">
                				<head>
                					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                					<meta name="format-detection" content="telephone=no">
                					<title>Allowance Application</title>
                					<style type="text/css">
                						.ReadMsgBody {width: 100%; background-color: #ebebeb;}
                						.ExternalClass {width: 100%; background-color: #ebebeb;}
                						.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
                						body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
                						body {margin:0;padding:0;}
                						table {border-spacing:0;}
                						table td {border-collapse:collapse;}
                						.yshortcuts a {border-bottom: none !important;}
                					</style>
                				</head>
                				<body>
                					<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
                						<tr>
                							<td style="padding:30px 0 50px;" valign="top" align="center">
                								<table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
                									<tr>
                							        	<td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="'.(newsletter_logo($comp_id)).'" height="62" alt=" "></td>
                							        </tr>
                									<tr>
                										<td valign="top" align="center">
                											<table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
                												<tr>
                													<td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
                														<table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
                															<tr>
                																<td valign="top">
                                                                                    '.$body_p.'
                																</td>
                															</tr>
                														</table>
                													</td>
                												</tr>
                
                											</table>
                										</td>
                									</tr>
                								</table>
                							</td>
                						</tr>
                						<tr>
                							<td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
                								<table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
                									<tr>
                										<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; '.date('Y').' Konsum Technologies. All Rights Reserved.</td>
                										<td valign="top"><img src="'.base_url().'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
                									</tr>
                								</table>
                							</td>
                						</tr>
                					</table>
                				</body>
            			     </html>
        			     ');
            
            if($CI->email->send()){
                return true;
            }else{
                return false;
            }
        } else {
            show_error("Invalid token");
        }
    }
 