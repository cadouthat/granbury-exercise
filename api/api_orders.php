<?php
/*
Handles /v1/orders/ requests
*/

//Calculate totals for a given order
function order_totals($orderId)
{
	//Extra safety check
	$orderId = intval($orderId);
	//Total all line items
	$subTotal = 0;
	$lines = db_fetch_all("SELECT extendedPrice FROM `orderlineitem` WHERE orderId = {$orderId}");
	foreach($lines as $line)
	{
		$subTotal += intval($line['extendedPrice']);
	}
	//Apply all taxes
	//	NOTE: is this the intended purpose of multiple tax rates?
	//		Should tax be calculated per-item or per-order?
	//		Should tax be rounded or floored?
	$totalTax = 0;
	$taxRates = db_fetch_all("SELECT rate FROM `salestaxrate`");
	foreach($taxRates as $taxRate)
	{
		//Get fixed point tax amount (thousandth-cents)
		$tax_fixed = intval($taxRate['rate']) * $subTotal;
		//Round to cents (remove 3 digits)
		for($i = 0; $i < 3; $i++)
		{
			//Store last digit and truncate
			$rem = $tax_fixed % 10;
			$tax_fixed /= 10;
			//Round up if needed
			if($rem >= 5) $tax_fixed++;
		}
		$totalTax += $tax_fixed;
	}
	$grandTotal = $subTotal + $totalTax;
	return array("subTotal" => $subTotal, "totalTax" => $totalTax, "grandTotal" => $grandTotal);
}

function processOrders($item)
{
	global $mysql, $_JSON;
	if(is_null($item))
	{
		//Collection requests
		switch($_SERVER['REQUEST_METHOD'])
		{
			case "GET": //List orders
				//Query in-progress orders
				$inProgress = db_fetch_all("SELECT o.* FROM `order` AS o ".
					"WHERE o.orderNumber = 0 ".
					"ORDER BY o.timestamp DESC");
				//Query submitted orders
				$submitted = db_fetch_all("SELECT o.*, ".
					"COALESCE(SUM(t.amountTendered), 0) AS totalTendered, ".
					"COALESCE(SUM(t.changeGiven), 0) AS totalChange ".
					"FROM `order` AS o ".
					"LEFT JOIN `tenderrecord` AS t ON t.orderId = o.orderId ".
					"WHERE o.orderNumber <> 0 ".
					"GROUP BY o.orderId ".
					"ORDER BY o.timestamp DESC");
				//Sort submitted orders into paid and unpaid
				$paid = array();
				$unpaid = array();
				foreach($submitted as $order)
				{
					$tendered = intval($order['totalTendered']);
					$change = intval($order['totalChange']);
					$total = intval($order['grandTotal']);
					if($tendered - $change >= $total)
					{
						$paid[] = $order;
					}
					else
					{
						$unpaid[] = $order;
					}
				}
				api_success(array("inProgress" => $inProgress, "paid" => $paid, "unpaid" => $unpaid));
				break;

			case "POST": //Create order
				//Insert empty order and return ID
				if(!db_insert("order", array())) api_dbfailure();
				api_success(array("orderId" => mysqli_insert_id($mysql)));
				break;

			default:
				api_failure("Invalid operation");
				break;
		}
	}
	else
	{
		//Force orderId to integer for query safety
		$orderId = intval($item);
		//Query existing order
		$order = db_fetch("SELECT orderNumber FROM `order` WHERE orderId = {$orderId}");
		if(is_null($order)) api_failure("Order does not exist");
		//Item requests
		switch($_SERVER['REQUEST_METHOD'])
		{
			case "PUT": //Submit order
				//If order is already submitted, no action is needed
				$orderNumber = intval($order['orderNumber']);
				if($orderNumber <= 0)
				{
					//Atomic counter store/update
					if(!mysqli_query($mysql, "UPDATE apivars SET value = MOD((@cur_value := value), 100) + 1 WHERE name = 'next_order'"))
					{
						api_dbfailure();
					}
					$result = db_fetch("SELECT @cur_value AS v");
					if(is_null($result))
					{
						api_dbfailure();
					}
					$orderNumber = intval($result['v']);
					//Update order number
					if(!mysqli_query($mysql, "UPDATE `order` SET orderNumber = {$orderNumber} WHERE orderId = {$orderId}"))
					{
						api_dbfailure();
					}
				}
				api_success(array("orderNumber" => $orderNumber));
				break;

			case "POST": //Add/update/remove line item
				//Validate arguments
				if(!isset($_JSON['itemName'])) api_failure("Requires 'itemName'");
				if(!isset($_JSON['qty'])) api_failure("Requires 'qty'");
				if(!is_numeric($_JSON['qty'])) api_failure("Invalid value for 'qty'");
				//Query existing line item
				$name_safe = mysqli_real_escape_string($mysql, $_JSON['itemName']);
				$line_where = " WHERE orderId = {$orderId} AND itemName = '{$name_safe}'";
				$line = db_fetch("SELECT qty, price FROM `orderlineitem`".$line_where);
				//Determine action for quantity
				$qty = intval($_JSON['qty']);
				if($qty > 0) //Insert/update line item
				{
					if(is_null($line))
					{
						//Query current item price
						$itemPrice = db_fetch("SELECT price FROM `item` WHERE name = '{$name_safe}'");
						if(is_null($itemPrice)) api_failure("Item not found");
						$price = intval($itemPrice['price']);
						//Insert line item
						$line = array();
						$line['orderId'] = $orderId;
						$line['itemName'] = $_JSON['itemName'];
						$line['qty'] = $qty;
						$line['price'] = $price;
						$line['extendedPrice'] = $qty * $price;
						if(!db_insert("orderlineitem", $line)) api_dbfailure();
					}
					else
					{
						//Update line item
						$price = intval($line['price']);
						$ext_price = $qty * $price;
						if(!mysqli_query($mysql, "UPDATE `orderlineitem` SET qty = {$qty}, extendedPrice = {$ext_price}".$line_where))
						{
							api_dbfailure();
						}
					}
				}
				else //Remove line item (zero quantity)
				{
					if(!is_null($line))
					{
						//Delete
						if(!mysqli_query($mysql, "DELETE FROM `orderlineitem`".$line_where))
						{
							api_dbfailure();
						}
					}
					//NOTE: Is failure appropriate if nothing was removed?
				}
				//Calculate new totals
				$totals = order_totals($orderId);
				//Update totals in database
				$q = "UPDATE `order` SET ";
				$q .= "subTotal = ".$totals['subTotal'].", ";
				$q .= "totalTax = ".$totals['totalTax'].", ";
				$q .= "grandTotal = ".$totals['grandTotal']." ";
				$q .= "WHERE orderId = ".$orderId;
				if(!mysqli_query($mysql, $q)) api_dbfailure();
				api_success(array("totals" => $totals));
				break;

			default:
				api_failure("Invalid operation");
				break;
		}
	}
}

?>