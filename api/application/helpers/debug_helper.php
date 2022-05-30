<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
*	Helper : company helpers for owner only
*	Author : Christopher Cuizon <christophercuizons@gmail.com>
*	Usage  : Usage for company/
*/

if ( ! function_exists('vardump'))
{
    function vardump($var)
    {
        echo "<pre>";
        echo htmlentities(var_export($var,TRUE));
        echo "</pre>";
    }
}

if ( ! function_exists('showall_session'))
{
	function showall_session()
	{
		$CI =& get_instance();
		$CI->load->library('session');
		
		vardump($CI->session->all_userdata());
	}
}

if ( ! function_exists('last_query'))
{
	function last_query()
	{
		$CI =&  get_instance();
		$CI->load->database();

		echo $CI->db->last_query();
	}
}

if ( ! function_exists('kdate'))
{
	function kdate($var)
	{	
		return date('Y-m-d',strtotime($var));
	}
}

