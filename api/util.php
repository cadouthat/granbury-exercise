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
	if(API_TEST)
	{
		global $API_TEST_RESULT;
		$API_TEST_RESULT = $obj;
	}
	else
	{
		echo json_encode($obj);
		exit(0);
	}
	return 1;
}
function api_dbfailure()
{
	return api_failure(API_DEBUG ? mysqli_error($mysql) : "Database error");
}
function api_success($result = NULL)
{
	$obj = array();
	$obj["status"] = "success";
	if(!is_null($result)) $obj = array_merge($obj, $result);
	if(API_TEST)
	{
		global $API_TEST_RESULT;
		$API_TEST_RESULT = $obj;
	}
	else
	{
		echo json_encode($obj);
		exit(0);
	}
	return 0;
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

//Verify that a string represents an unsigned integer
function is_uint($str)
{
	return preg_match('/^[0-9]+$/', $str);
}
//Convert decimal price (USD) to cents, negative result indicates failure
function price_to_cents($price)
{
	//Trim dollar sign (optional)
	if(substr($price, 0, 1) == "$") $price = substr($price, 1);
	//Split at decimal
	$parts = explode(".", $price);
	//Must have 2 parts
	if(count($parts) != 2) return -1;
	//Parts must be numeric
	if(!is_uint($parts[0]) || !is_uint($parts[1])) return -1;
	//Precision must be 2
	if(strlen($parts[1]) != 2) return -1;
	return intval($parts[0]) * 100 + intval($parts[1]);
}

//Build a PHP array (AoS style) from MySQL results (empty array indicates failure or no results)
function db_fetch_all($q)
{
	global $mysql;
	$result = mysqli_query($mysql, $q);
	if($result === FALSE || $result === TRUE) return array();
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
//Fetch a single result from a query (requires exactly 1 result)
function db_fetch($q)
{
	global $mysql;
	$result = mysqli_query($mysql, $q);
	if($result === FALSE || $result === TRUE) return NULL;
	$item = NULL;
	if(mysqli_num_rows($result) == 1)
	{
		$item = mysqli_fetch_assoc($result);
	}
	mysqli_free_result($result);
	return $item;
}
//Attempt MySQL insert from associative array
function db_insert($table, $item)
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
			$vals .= "'".mysqli_real_escape_string($mysql, strval($v))."'";
		}
	}
	//Build query string
	$q = "INSERT INTO `{$table}` ({$cols}) VALUES ({$vals})";
	//Execute (returns TRUE or FALSE)
	return mysqli_query($mysql, $q);
}
?>