<?php (defined('BASEPATH')) OR exit('No direct script access allowed');
/**
 * Edb Config
 *
 * @author Kris Edward Galanida 
 */

/**
 * Set to TRUE will enable all encryption/decryption.
 * Set to FALSE will disable all encryption/decryption.
 * 
 * @var boolean
 */
$config['enabled'] = TRUE;

/**
 * This is the secret key for the encryption/decryption
 * 
 * @var string
 */
//$config['secret_key'] = 'madonnagwapa';
$config['secret_key'] = 'konsum';
#$config['secret_key'] = 'K0ns4mT3ch_2015';

/**
 * List of data types that cannot be encrypted 
 * Note: Do not change this
 * 
 * @var array
 */
$config['non_encrypt'] = array('int','date','enum','datetime','timestamp','time','text','varchar','float','tinyint','decimal');

/**
 * List of data types that will be encrypted
 * Note: Do not change this unless you know how this works
 * 
 * @var array
 */
$config['encrypt'] = array('varbinary');

/* End of file edb.php */
/* Location: ./application/config/edb.php */