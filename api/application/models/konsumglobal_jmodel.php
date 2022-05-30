<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Konsumglobal jmodel Model
 *
 * @category Model
 * @version 1.0
 * @author Jonathan Bangga <jonathanbangga@gmail.com>
 */
class Konsumglobal_jmodel extends CI_Model {
	
	/**
	 * Insert data
	 * @param unknown_type $table_name
	 * @param unknown_type $data
	 */
	public function insert_data($table_name,$data){
		$insert = $this->edb->insert($table_name,$data);
		if($insert){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Get maximum id
	 * @param field_name
	 * @param tbl_name
	 * @return maximum id
	 */
	public function maxid($field_name,$tbl_name){
		$this->db->select_max($field_name);
		$query = $this->db->get($this->db->dbprefix($tbl_name));
		$result = $query->result(); 
		if($query->num_rows() > 0){
			$query->free_result();
			foreach($result as $row){
				$maxid = $row->$field_name + 1;
			}
			return $maxid;
		}else{
			$maxid = 1;
			return $maxid;
		}
	}
	
	/**
	 * Display data
	 * @param table_name
	 * @return data result
	 */
	public function display_data($table_name){
		$this->db->where('status','Active');
		$query = $this->db->get($this->db->dbprefix($table_name));
		$result = $query->result();
		$query->free_result();
		return $result;
	}
	
	/**
	 * Delete data information
	 * @param tble_name
	 * @param field_name
	 * @param field_val
	 * @return true
	 */
	public function delete_data($tble_name,$field_name,$field_val){
		$this->db->where($field_name,$this->db->escape_like_str($field_val));
		$this->db->delete($tble_name);
		return true;
	}
	
	/**
	 * Update data value
	 * @param tble_name
	 * @param data_update
	 * @param field_val
	 * @param field_name
	 * @return boolean Returns true if query has a data value otherwise false
	 */
	public function update_data($tble_name,$data_update,$field_val,$field_name){
		$this->edb->where($field_name,$field_val);
		$query = $this->edb->update($tble_name,$data_update);
		
		if($query){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Display data where
	 * @param table_name
	 * @return data result
	 */
	public function display_data_where($table_name,$where_field,$where_val){
		$this->edb->where($where_field,$where_val);
		$query = $this->edb->get($this->db->dbprefix($table_name));
		$result = $query->row();
		$query->free_result();
		return $result;
	}
	
	/**
	 * Display data where for listing result
	 * @param table_name
	 * @return data result
	 */
	public function display_data_where_result($table_name,$data)
	{
		$this->edb->where($data);
		$query = $this->edb->get($table_name);
		$result = $query->result();
		$query->free_result();
		return $result;
	}
	
}
	
/* End of file konsumglobal_jmodel.php */
/* Location: ./application/controllers/hr/konsumglobal_jmodel.php */