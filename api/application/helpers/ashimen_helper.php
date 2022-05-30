<?php if (!defined('BASEPATH'))exit('No direct script access allowed');

/**
*  return array
*  @param string value - convert this to array
*  @param string option - select only r(print_r) and v(var_dump)
**/
function ashiment_print($value,$option){
 	
	echo "<pre>";
	 	if($option = "r"):
			print_r($value);
		elseif($option = "v"):
 			var_dump($value);
		else:
			echo "invalid option";
 		endif;
   echo "</pre>";
}

/**
*  return ip address
**/
function getIP(){
	
	if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
      $ip= $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
      $ip= $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function format_date($date){
	$mdate = date("M d, Y H:m",strtotime($date));
	return $mdate;
}
function database_date($date)
{
	return date("Y-m-d",strtotime($date));
}
function date_with_time($date)
{
	return date("Y-m-d H:i:s",strtotime($date));
}
