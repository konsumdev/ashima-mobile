<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Database active records with AES Encryption/Decryption.
 * The configuration settings are located ./application/config/edb.php
 * 
 * This is just a replication of the database active records but added ang AES Encryption/Decryption.
 * 
 * Note: It is best to specify the field names so the query will not create a very long line.  
 *   
 * @author Kris Edward Galanida
 *
 */
class Edb {
	/**
	 * Codeigniter instance
	 * @var codeigniter
	 */
	protected $_CI;
	protected $_config;
	
	// active records
	var $arr_select   = array();
	var $arr_distinct = array();
	var $arr_where	  = array();
	var $arr_set	  = array();
	var $arr_join	  = array();
	var $arr_table	  = array();
	
	var $data_types	  = array();
	var $edata_types  = array(); 
		
	var $encrypt 	  = array();
	var $non_encrypt  = array();
	
	/**
	 * Constructor
	 * @param array $config
	 */
	public function __construct($config)
	{
		$this->_CI =& get_instance();
		$this->_CI->load->database();
		
		$this->_config = $config;
		
		$this->data_types = $this->_config['non_encrypt'];
		$this->edata_types = $this->_config['encrypt'];
	}
	
	/**
	 * Flush all variables
	 */
	public function reset()
	{
		$this->arr_select   = array();
		$this->arr_distinct = array();
		$this->arr_where    = array();
		$this->arr_set	    = array();
		$this->arr_join	    = array();
		$this->arr_table    = array();
		$this->encrypt	    = array();
		$this->non_encrypt  = array();
	}
	
	/**
	 * Select
	 * 
	 * Generates the SELECT decrypted value of the query
	 *
	 * @param	string
	 * @param	boolean
	 * @return	object
	 */
	public function select($select = '*')
	{
		if ($this->_config['enabled'] == TRUE) {
			if (is_string($select)) {
				$select = explode(',', $select);
			}
			
			foreach ($select as $val) {
				$val = trim($val);
	
				if ($val != '') {
					$this->arr_select[] = trim($val);
				}
			}
		} else {
			$this->_CI->db->select($select);
		}
	}
	
	/**
	 * Select Distinct
	 * 
	 * Generates the SELECT DISTINCT decrypted value of the query
	 *
	 * @param	string
	 * @param	boolean
	 * @return	object
	 */
	public function distinct($select = '*')
	{
		if ($this->_config['enabled'] == TRUE) {
			if (is_string($select)) {
				$select = explode(',', $select);
			}
			
			foreach ($select as $val) {
				$val = trim($val);
	
				if ($val != '') {
					$this->arr_distinct[] = trim($val);
				}
			}
		} else {
			$this->_CI->db->distinct($select);
		}
	}
	
	/**
	 * Where
	 * 
	 * Generates the WHERE portion with encrypted value of the query. Separates
	 * multiple calls with AND
	 *
	 * @param	mixed
	 * @param	mixed
	 * @return	object
	 */
	public function where($key, $value = NULL)
	{
		if ($this->_config['enabled'] == TRUE) {
			if (!is_array($key)) {
				$key = array($key => $value);
			}
			
			foreach ($key as $k => $v) {
				$this->arr_where[$k] = trim($v);	
			}
		} else {
			$this->_CI->db->where($key,$value);
		}
	}
	
	/**
	 * Or Where
	 * 
	 * Generates the OR WHERE portion value of the query.
	 * 
	 * @param mixed $key
	 * @param string $value
	 */
	public function or_where($key, $value = NULL)
	{
		$this->_CI->db->or_where($key,$value);
	}
	
	
	/**
	 * Where_not_in
	 *
	 * Generates a WHERE field NOT IN ('item', 'item') SQL query joined
	 * with AND if appropriate
	 *
	 * @param	string	The field to search
	 * @param	array	The values searched on
	 * @return	object
	 */
	public function where_not_in($key = NULL, $values = NULL)
	{
		$this->_CI->db->where_not_in($key, $values);
	}

	/**
	 * The "set" function.  Allows key/value pairs to be set for inserting or updating with encrypted values
	 *
	 * @param	mixed
	 * @param	string
	 * @param	boolean
	 * @return	object
	 */
	public function set($key, $value = '')
	{
		if ($this->_config['enabled'] == TRUE) {
			if ( ! is_array($key)) {
				$key = array($key => $value);
			}
	
			foreach ($key as $k => $v) {
				$this->arr_set[$k] = trim($v); 
			}
	
			return $this;
		} else {
			$this->_CI->db->set($key,$value);
		}
	}
	
	/**
	 * Join
	 *
	 * Generates the JOIN portion of the query.
	 * WARNING! Do not join field with encrypt values
	 *
	 * @param	string
	 * @param	string	the join condition
	 * @param	string	the type of join
	 * @return	object
	 */
	public function join($table, $cond, $type = '')
	{
		if ($this->_config['enabled'] == TRUE) {
			// check alias define
			if (preg_match('/\\sAS\\s/', $table)) {
				$t = explode(' AS ', $table);
			} elseif (preg_match('/\\s/', $table)) {
				$t = explode(' ', $table);
			} else {
				$t = array($table,$table);
			}
			$this->arr_table[$t[0]] = $t[1];
			
			$this->arr_join[] = $t[0];
		}
		$this->_CI->db->join($table,$cond,$type);
	}
	
	/**
	 * GROUP BY
	 *
	 * @param	string
	 * @return	object
	 */
	public function group_by($by)
	{
		if (is_string($by))
		{
			$by = explode(',', $by);
		}

		foreach ($by as $val)
		{
			$this->_CI->db->group_by($by);
		}
	}
	
	/**
	 * Sets the ORDER BY value
	 *
	 * @param	string
	 * @param	string	direction: asc or desc
	 * @return	object
	 */
	public function order_by($orderby, $direction = '')
	{
		if ($this->_config['enabled'] == TRUE) {
			$orderby = 'AES_DECRYPT('.$orderby.',"'.$this->_config['secret_key'].'")';
			$this->_CI->db->order_by($orderby,$direction,FALSE);
		} else {
			$this->_CI->db->order_by($orderby,$direction);
		}	
	}
	
	/**
	 * Like
	 *
	 * Generates a %LIKE% portion of the query. Separates
	 * multiple calls with AND
	 *
	 * @param	mixed
	 * @param	mixed
	 * @return	object
	 */
	public function like_concat($field1, $field2, $match = '')
	{
		if ($this->_config['enabled'] == TRUE) {
			$field1 = $this->decrypt($field1);
			$field2 = $this->decrypt($field2);
			$this->_CI->db->like('CONCAT('.$field1.'," ",'.$field2.')', $match, 'AND ', 'both', NULL, FALSE);
		} else {
			$this->_CI->db->like('CONCAT('.$field1.'," ",'.$field2.')', $match);
		}
	}
	
	/**
	 * Insert
	 *
	 * Compiles an insert string and runs the query
	 *
	 * @param	string	the table to insert data into
	 * @param	array	an associative array of insert values
	 * @return	object
	 */
	function insert($table = '', $set = NULL)
	{
		if ($table == '') {
			return $this->_CI->db->display_error('db_must_set_table');
		}
		
		if ($this->_config['enabled'] == TRUE) {
			if ($set) {
				$this->set($set);
			}
			
			// Getting all the datatypes of the table
			$q = $this->_CI->db->query('DESCRIBE '.$table);
			$r = $q->result();
			
			foreach ($r as $k => $v) {
				$type = explode('(', $v->Type);
				if (in_array($type[0], $this->data_types)) {
					$this->non_encrypt[] = $v->Field;
				} else {
					$this->encrypt[] = $v->Field;
				}
			}
			
			// set
			foreach ($this->arr_set as $field => $value) {
				if (in_array($field, $this->non_encrypt)) {
					$this->_CI->db->set($field,$value);
				} else {
					$value = $this->encrypt($value);
					$this->_CI->db->set($field,trim($value),FALSE);
				}
			}
			
			$this->reset();
			return $this->_CI->db->insert($table);
		} else {
			return $this->_CI->db->insert($table,$set);
		}
	}
	
	/**
	 * Update
	 *
	 * Compiles an update string and runs the query
	 *
	 * @param	string	the table to retrieve the results from
	 * @param	array	an associative array of update values
	 * @param	mixed	the where clause
	 * @return	object
	 */
	public function update($table = '', $set = NULL)
	{
		if ($table == '') {
			return $this->_CI->db->display_error('db_must_set_table');
		}
		
		if ($this->_config['enabled'] == TRUE) {
			if ($set) {
				$this->set($set);
			}
			
			// Getting all the datatypes of the table
			$q = $this->_CI->db->query('DESCRIBE '.$table);
			$r = $q->result();
			
			foreach ($r as $k => $v) {
				$type = explode('(', $v->Type);
				if (in_array($type[0], $this->data_types)) {
					$this->non_encrypt[] = $v->Field;
				} else {
					$this->encrypt[] = $v->Field;
				}
			}
			
			// set
			foreach ($this->arr_set as $field => $value) {
				if (in_array($field, $this->non_encrypt)) {
					$this->_CI->db->set($field,$value);
				} else {
					$value = $this->encrypt($value);
					$this->_CI->db->set($field,$value,FALSE);
				}
			}
			
			// where
			$tmp = array();
			if ($this->arr_where) {
				foreach ($this->arr_where as $field => $value) {
					$field1 = $field;
					
					if (preg_match('/\./', $field)) {
						$tmp = explode('.', $field);
						$field1 = $tmp[1];
					}
					
					if (in_array($field1, $this->non_encrypt)) {
						$this->_CI->db->where($field,$value);
					} else {
						$value = $this->encrypt($value);
						$this->_CI->db->where($field,$value,FALSE);
					}
				}
			}
	
			$this->reset();
			return $this->_CI->db->update($table);
		} else {
			return $this->_CI->db->update($table,$set);
		}
	}
	
	/**
	 * Get
	 *
	 * Compiles the select statement based on the other functions called
	 * and runs the query
	 *
	 * @param	string	the table
	 * @param	string	the limit clause
	 * @param	string	the offset clause
	 * @return	object
	 */
	public function get($table, $limit = null, $offset = null)
	{
		if ($this->_config['enabled'] == TRUE) {
			if (preg_match('/\\sAS\\s/', $table)) {
				$tmp = explode(' AS ', $table);
				$ptable = $tmp[0];
			} else {
				$ptable = $table;
			}
			
			// Getting all the datatypes of the table
			$q = $this->_CI->db->query('DESCRIBE '.$ptable);
			$r = $q->result();
			
			foreach ($r as $k => $v) {
				$type = explode('(', $v->Type);
				if (in_array($type[0], $this->data_types)) {
					$this->non_encrypt[] = $v->Field;
				} else {
					$this->encrypt[] = $v->Field;
				}
			}
			
			// check if join
			if ($this->arr_join) {
				foreach ($this->arr_join as $jtable) {

					// Getting all the datatypes of the table
					$jq = $this->_CI->db->query('DESCRIBE '.$jtable);
					$jr[$jtable] = $jq->result();
					
					foreach ($jr[$jtable] as $k => $v) {
						$type = explode('(', $v->Type);
						if (in_array($type[0], $this->data_types)) {
							$this->non_encrypt[] = $v->Field;
						} else {
							$this->encrypt[] = $v->Field;
						}
					}
				}
			}
			
			$this->check_alias($table);
			
			// select
			if ($this->arr_distinct) {
				foreach ($this->arr_distinct as $val) {
					if ($val == '*') {
						// for primary table
						foreach ($r as $k => $v) {
							if (in_array($v->Field, $this->non_encrypt)) {
								$this->_CI->db->distinct($this->arr_table[$ptable].'.'.$v->Field.' AS '.$v->Field);
							} else {
								$this->_CI->db->distinct('AES_DECRYPT('.$this->arr_table[$ptable].'.'.$v->Field.',"'.$this->_config['secret_key'].'") AS '.$v->Field,FALSE);
							}
						}
						
						if ($this->arr_join) {
							// for join table
							foreach ($this->arr_join as $jtable) {
								foreach ($jr[$jtable] as $k => $v) {
									if (in_array($v->Field, $this->non_encrypt)) {
										$this->_CI->db->distinct($this->arr_table[$jtable].'.'.$v->Field.' AS '.$v->Field);
									} else {
										$this->_CI->db->distinct('AES_DECRYPT('.$this->arr_table[$jtable].'.'.$v->Field.',"'.$this->_config['secret_key'].'") AS '.$v->Field,FALSE);
									}
								}
							}
						}
					} else {
						if (preg_match('/\./', $val)) {
							$tmp = explode('.', $val);
							$field = $tmp[1];
						} else {
							$field = $val;
						}
						
						if (preg_match('/\\sAS\\s/', $val)) {
							$tmp = explode(' AS ', $val);
							$val = $tmp[0];
							$field = $tmp[1];
						}
						
						if (in_array($field, $this->non_encrypt)) {
							$this->_CI->db->distinct($val);
						} else {
							$this->_CI->db->distinct('AES_DECRYPT('.$val.',"'.$this->_config['secret_key'].'") AS '.$field,FALSE);
						}
					}
				}
			} elseif ($this->arr_select) {	// For specifying tables
				foreach ($this->arr_select as $val) {
					if ($val == '*') {
						// for primary table
						foreach ($r as $k => $v) {
							if (in_array($v->Field, $this->non_encrypt)) {
								$this->_CI->db->select($this->arr_table[$ptable].'.'.$v->Field.' AS `'.$v->Field.'`');
							} else {
								$this->_CI->db->select('AES_DECRYPT('.$this->arr_table[$ptable].'.'.$v->Field.',"'.$this->_config['secret_key'].'") AS `'.$v->Field.'`',FALSE);
							}
						}
						
						if ($this->arr_join) {
							// for join table
							foreach ($this->arr_join as $jtable) {
								foreach ($jr[$jtable] as $k => $v) {
									if (in_array($v->Field, $this->non_encrypt)) {
										$this->_CI->db->select($this->arr_table[$jtable].'.'.$v->Field.' AS `'.$v->Field.'`');
									} else {
										$this->_CI->db->select('AES_DECRYPT('.$this->arr_table[$jtable].'.'.$v->Field.',"'.$this->_config['secret_key'].'") AS `'.$v->Field.'`',FALSE);
									}
								}
							}
						}
					} else {
						if (preg_match('/\./', $val)) {
							$tmp = explode('.', $val);
							$field = $tmp[1];
						} else {
							$field = $val;
						}
						
						if (preg_match('/\\sAS\\s/', $val)) {
							$tmp = explode(' AS ', $val);
							$val = $tmp[0];
							$field = $tmp[1];
						}
						
						if (in_array($field, $this->non_encrypt)) {
							$this->_CI->db->select($val);
						} else {
							$this->_CI->db->select('AES_DECRYPT('.$val.',"'.$this->_config['secret_key'].'") AS '.$field,FALSE);
						}
					}
				}
			} else {	// SELECT is *
				// for primary table
				foreach ($r as $k => $v) {
					if (in_array($v->Field, $this->non_encrypt)) {
						$this->_CI->db->select($this->arr_table[$ptable].'.'.$v->Field.' AS `'.$v->Field.'`');
					} else {
						$this->_CI->db->select('AES_DECRYPT('.$this->arr_table[$ptable].'.'.$v->Field.',"'.$this->_config['secret_key'].'") AS `'.$v->Field.'`',FALSE);
					}
				}
				
				if ($this->arr_join) {
					// for join table
					
					foreach ($this->arr_join as $jtable) {
						foreach ($jr[$jtable] as $k => $v) {
							if (in_array($v->Field, $this->non_encrypt)) {
								$this->_CI->db->select($this->arr_table[$jtable].'.'.$v->Field.' AS `'.$v->Field.'`');
							} else {
								$this->_CI->db->select('AES_DECRYPT('.$this->arr_table[$jtable].'.'.$v->Field.',"'.$this->_config['secret_key'].'") AS `'.$v->Field.'`',FALSE);
							}
						}
					}
				}
				
			}
			
			// where
			$tmp = array();
			if ($this->arr_where) {
				foreach ($this->arr_where as $field => $value) {
					$field1 = $field;
					
					if (preg_match('/\./', $field)) {
						$tmp = explode('.', $field);
						$field1 = $tmp[1];
					}
					
					if (in_array($field1, $this->non_encrypt)) {
						$this->_CI->db->where($field,$value);
					} else {
						$this->_CI->db->where($field,'AES_ENCRYPT("'.$value.'","'.$this->_config['secret_key'].'")',FALSE);
					}
				}
			}
			
			$this->reset();
			return $this->_CI->db->get($table,$limit,$offset);
		} else {
			return $this->_CI->db->get($table,$limit,$offset);
		}
	}
	
	/**
	 * Check Alias
	 * 
	 * Check alias specified for the table
	 * 
	 * @param string $table
	 */
	public function check_alias($table)
	{
		// check alias define
		if (preg_match('/\\sAS\\s/', $table)) {
			$t = explode(' AS ', $table);
		} elseif (preg_match('/\\s/', $table)) {
			$t = explode(' ', $table);
		} else {
			$t = array($table,$table);
		}
		$this->arr_table[$t[0]] = $t[1];
	}
	
	/**
	 * Decrypt
	 * 
	 * Decrypt the value
	 * 
	 * @param string $var
	 */
	public function decrypt($var)
	{
		return 'AES_DECRYPT('.$var.',"'.$this->_config['secret_key'].'")';
	}
	
	/**
	 * Encrypt
	 * 
	 * Encrypt the value
	 * 
	 * @param string $var
	 */
	public function encrypt($var)
	{
		return 'AES_ENCRYPT("'.$var.'","'.$this->_config['secret_key'].'")';
	}
	
}

/* End of file Edb.php */
/* Location: ./application/libraries/edb.php */