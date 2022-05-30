<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Employee Cash Advance Model
 *
 * @category Model
 * @version 1.0
 * @author reyneill
 *
 */
class Emp_social_media_model extends CI_Model {
	
	public function __construct(){
		parent::__construct();
		$this->load->library('twitteroauth');
	}
	
	public function social_tweet($biyat,$account_id){
		$where = array('account_id'=>$account_id);
		$this->edb->where($where);
		$check = $this->edb->get('employee_twitter_accounts');
		$row = $check->row();
		p($row);
		if($row) {			
			$consumer_key = $row->consumer_key;
			$consumer_secret =$row->consumer_secret;
			$oauth_access_token  = $row->oauth_access_token_secret;
			$oauth_access_token_secret  = $row->oauth_access_token;
			if($consumer_key !="" && $consumer_secret !="" && $oauth_access_token !="" && $oauth_access_token_secret !="") {
				echo 'test';
				$this->connection = $this->twitteroauth->create($consumer_key,$consumer_secret,$oauth_access_token,$oauth_access_token_secret);
				$data['verify'] = $this->connection->get('account/verify_credentials');
					if($this->connection) {
						$message = array(
							'status' => $biyat
						);
						$result = $this->connection->post('statuses/update',$message);
						p($this->connection);
						p($data['verify']);
						return true;
					}else{
						return false;
					}		
			}else{
				return false;
			}	
		}else{
			return false;
		}
	}
	
	public function tweet_account($account_id){
		$where = array('account_id'=>$account_id);
		$this->edb->where($where);
		$check = $this->edb->get('employee_twitter_accounts');
		$row = $check->row();
		return $row;
	}
	
	public function fb_account($account_id,$company_id){
		if(is_numeric($account_id) && is_numeric($company_id)){
			$where = array(
				'account_id'=>$account_id,
				'company_id'=> $company_id,
				'status'=> 'Active'
			);
			$this->edb->where($where);
			$q = $this->edb->get('employee_facebook_accounts');
			$r = $q->row();
			return $r;
		}else{
			return false;
		}
	}
	
	public function tw_account($account_id,$company_id){
		$where = array('account_id'=>$account_id,'company_id'=>$company_id,'status'=>'Active');
		$this->edb->where($where);
		$check = $this->edb->get('employee_twitter_accounts');
		$row = $check->row();
		return $row;
	}
	
	
	public function get_account_facebook($account_id){
		if(is_numeric($account_id)){
			$where  = array(
				'account_id'=>$account_id
			);
			$this->edb->where($where);
			$q = $this->edb->get('social_media_accounts');
			$row = $q->row();
			return $row;
		}else{
			return false;
		}
	}
	
	
	
}