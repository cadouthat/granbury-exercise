<?php
/*
Miscellaneous helpers for Granbury API
*/

//API status helpers
function api_failure($message = NULL)
{
	$obj = array();
	$obj["status"] = "failure";
	if(!is_null($message)) $obj["message"] = $message;
	echo json_encode($obj);
	exit(0);
}
function api_success($result = NULL)
{
	$obj = array();
	$obj["status"] = "success";
	if(!is_null($result)) $obj = array_merge($obj, $result);
	echo json_encode($obj);
	exit(0);
}

//Check file upload status
function upload_attempted($field)
{
	if(!isset($_FILES[$field])) return false;
	return ($_FILES[$field]["error"] != UPLOAD_ERR_NO_FILE);
}
function upload_okay($field)
{
	if($_FILES[$field]["error"] != 0) return false;
	if($_FILES[$field]["size"] == 0) return false;
	if(!strlen($_FILES[$field]['tmp_name']) || !file_exists($_FILES[$field]['tmp_name'])) return false;
	return true;
}

//Convert decimal price (USD) to cents
function price_to_cents($price)
{
	//Trim dollar sign (optional)
	if(substr($price, 0, 1) == "$") $price = substr($price, 1);
	//Split at decimal
	$parts = explode(".", $price);
	//Must have 2 parts
	if(count($parts) != 2) return 0;
	//Parts must be numeric
	if(!is_numeric($parts[0]) || !is_numeric($parts[1])) return 0;
	//Precision must be 2
	if(strlen($parts[1]) != 2) return 0;
	return intval($parts[0]) * 100 + intval($parts[1]);
}
//Convert cents to decimal price (USD)
function cents_to_price($cents)
{
	$cents = intval($cents);
	//Separate dollars and remaining cents
	$dollars = $cents / 100;
	$cents = $cents % 100;
	//Format with $ and 2 decimals
	return sprintf("$%d.%02d", $dollars, $cents);
}

//Build a PHP array (AoS style) from MySQL results (empty array indicates failure or no results)
function query_array($q)
{
	global $mysql;
	$result = mysqli_query($mysql, $q);
	if($result === FALSE || $result === TRUE)
	{
		return array();
	}
	$list = array();
	if(mysqli_num_rows($result))
	{
		while($row = mysqli_fetch_assoc($result))
		{
			$list[] = $row;
		}
	}
	mysqli_free_result($result);
	return $list;
}
//Attempt MySQL insert from associative array
function insert_assoc($table, $item)
{
	global $mysql;
	//Build column and value lists
	$cols = "";
	$vals = "";
	foreach($item as $k => $v)
	{
		if(strlen($cols)) $cols .= ", ";
		if(strlen($vals)) $vals .= ", ";
		$cols .= mysqli_real_escape_string($mysql, strval($k));
		if(is_int($v))
		{
			//Integers can be raw
			$vals .= $v;
		}
		else
		{
			//Everything else: stringify, escape, and quote
			$vals .= "\"".mysqli_real_escape_string($mysql, strval($v))."\"";
		}
	}
	//Build query string
	$q = "INSERT INTO `{$table}` ({$cols}) VALUES ({$vals})";
	//Execute (returns TRUE or FALSE)
	return mysqli_query($mysql, $q);
}
?>