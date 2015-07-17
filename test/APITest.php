<?php
/*
Granbury API unit testing
*/

define("API_TEST", true);

class APITest extends PHPUnit_Framework_TestCase
{
	public function testGetItemList()
	{
		global $_JSON, $API_TEST_RESULT;
		//Get item list (expecting 20 items from sample DB)
		$_SERVER['REQUEST_METHOD'] = "GET";
		$_GET = array("api" => "v1", "collection" => "items");
		$_JSON = array();
		include("../api/api.php");
		$this->assertTrue(isset($API_TEST_RESULT));
		$this->assertTrue(isset($API_TEST_RESULT["status"]));
		$this->assertEquals("success", $API_TEST_RESULT["status"]);
		$this->assertEquals(20, count($API_TEST_RESULT["items"]));
	}

	public function testOrder()
	{
		global $_JSON, $API_TEST_RESULT;
		//Create order
		$_SERVER['REQUEST_METHOD'] = "POST";
		$_GET = array("api" => "v1", "collection" => "orders");
		$_JSON = array();
		include("../api/api.php");
		$this->assertTrue(isset($API_TEST_RESULT));
		$this->assertTrue(isset($API_TEST_RESULT["status"]));
		$this->assertEquals("success", $API_TEST_RESULT["status"]);
		$this->assertTrue(isset($API_TEST_RESULT["orderId"]));
		$this->assertTrue($API_TEST_RESULT["orderId"] > 0);

		//Add item
		$_GET['item'] = $API_TEST_RESULT["orderId"];
		$_JSON = array("itemName" => "Root Beer", "qty" => 2);
		unset($API_TEST_RESULT);
		include("../api/api.php");
		$this->assertTrue(isset($API_TEST_RESULT));
		$this->assertTrue(isset($API_TEST_RESULT["status"]));
		$this->assertEquals("success", $API_TEST_RESULT["status"]);

		//Add different item
		$_JSON = array("itemName" => "Side Salad", "qty" => 3);
		unset($API_TEST_RESULT);
		include("../api/api.php");
		$this->assertTrue(isset($API_TEST_RESULT));
		$this->assertTrue(isset($API_TEST_RESULT["status"]));
		$this->assertEquals("success", $API_TEST_RESULT["status"]);

		//Update item
		$_JSON = array("itemName" => "Root Beer", "qty" => 4);
		unset($API_TEST_RESULT);
		include("../api/api.php");
		$this->assertTrue(isset($API_TEST_RESULT));
		$this->assertTrue(isset($API_TEST_RESULT["status"]));
		$this->assertEquals("success", $API_TEST_RESULT["status"]);

		//Remove different item
		$_SERVER['REQUEST_METHOD'] = "DELETE";
		$_GET['subitem'] = urlencode("Side Salad");
		$_JSON = array();
		unset($API_TEST_RESULT);
		include("../api/api.php");
		$this->assertTrue(isset($API_TEST_RESULT));
		$this->assertTrue(isset($API_TEST_RESULT["status"]));
		$this->assertEquals("success", $API_TEST_RESULT["status"]);
		$this->assertTrue(isset($API_TEST_RESULT["totals"]));
		//Verify final totals
		$totals = $API_TEST_RESULT["totals"];
		$this->assertTrue(isset($totals["subTotal"]));
		$this->assertTrue(isset($totals["totalTax"]));
		$this->assertTrue(isset($totals["grandTotal"]));
		$this->assertEquals(400, $totals["subTotal"]);
		$this->assertEquals($totals["subTotal"] + $totals["totalTax"], $totals["grandTotal"]);
	}

	public function testPayment()
	{
		global $_JSON, $API_TEST_RESULT;
		//Check starting totals
		$_SERVER['REQUEST_METHOD'] = "GET";
		$_GET = array("api" => "v1", "collection" => "orders");
		$_JSON = array();
		unset($API_TEST_RESULT);
		include("../api/api.php");
		$this->assertTrue(isset($API_TEST_RESULT));
		$this->assertTrue(isset($API_TEST_RESULT["status"]));
		$this->assertEquals("success", $API_TEST_RESULT["status"]);
		$this->assertTrue(isset($API_TEST_RESULT["inProgress"]));
		$this->assertTrue(isset($API_TEST_RESULT["paid"]));
		$this->assertTrue(isset($API_TEST_RESULT["unpaid"]));
		$orig_inProgress = count($API_TEST_RESULT["inProgress"]);
		$orig_paid = count($API_TEST_RESULT["paid"]);
		$orig_unpaid = count($API_TEST_RESULT["unpaid"]);

		//Create order
		$_SERVER['REQUEST_METHOD'] = "POST";
		$_GET = array("api" => "v1", "collection" => "orders");
		$_JSON = array();
		unset($API_TEST_RESULT);
		include("../api/api.php");
		$this->assertTrue(isset($API_TEST_RESULT));
		$this->assertTrue(isset($API_TEST_RESULT["status"]));
		$this->assertEquals("success", $API_TEST_RESULT["status"]);
		$this->assertTrue(isset($API_TEST_RESULT["orderId"]));
		$this->assertTrue($API_TEST_RESULT["orderId"] > 0);
		$orderId = $API_TEST_RESULT["orderId"];

		//Add item
		$_GET["item"] = $orderId;
		$_JSON = array("itemName" => "Root Beer", "qty" => 2);
		unset($API_TEST_RESULT);
		include("../api/api.php");
		$this->assertTrue(isset($API_TEST_RESULT));
		$this->assertTrue(isset($API_TEST_RESULT["status"]));
		$this->assertEquals("success", $API_TEST_RESULT["status"]);

		//Check totals (in progress)
		$_SERVER['REQUEST_METHOD'] = "GET";
		$_GET = array("api" => "v1", "collection" => "orders");
		$_JSON = array();
		unset($API_TEST_RESULT);
		include("../api/api.php");
		$this->assertTrue(isset($API_TEST_RESULT));
		$this->assertTrue(isset($API_TEST_RESULT["status"]));
		$this->assertEquals("success", $API_TEST_RESULT["status"]);
		$this->assertTrue(isset($API_TEST_RESULT["inProgress"]));
		$this->assertTrue(isset($API_TEST_RESULT["paid"]));
		$this->assertTrue(isset($API_TEST_RESULT["unpaid"]));
		$this->assertEquals($orig_inProgress + 1, count($API_TEST_RESULT["inProgress"]));
		$this->assertEquals($orig_unpaid, count($API_TEST_RESULT["unpaid"]));
		$this->assertEquals($orig_paid, count($API_TEST_RESULT["paid"]));

		//Submit order
		$_SERVER['REQUEST_METHOD'] = "PUT";
		$_GET["item"] = $orderId;
		$_JSON = array();
		unset($API_TEST_RESULT);
		include("../api/api.php");
		$this->assertTrue(isset($API_TEST_RESULT));
		$this->assertTrue(isset($API_TEST_RESULT["status"]));
		$this->assertEquals("success", $API_TEST_RESULT["status"]);
		$this->assertTrue(isset($API_TEST_RESULT["orderNumber"]));
		$n = intval($API_TEST_RESULT["orderNumber"]);
		$this->assertTrue($n > 0 && $n <= 100);

		//Check totals (unpaid)
		$_SERVER['REQUEST_METHOD'] = "GET";
		$_GET = array("api" => "v1", "collection" => "orders");
		$_JSON = array();
		unset($API_TEST_RESULT);
		include("../api/api.php");
		$this->assertTrue(isset($API_TEST_RESULT));
		$this->assertTrue(isset($API_TEST_RESULT["status"]));
		$this->assertEquals("success", $API_TEST_RESULT["status"]);
		$this->assertTrue(isset($API_TEST_RESULT["inProgress"]));
		$this->assertTrue(isset($API_TEST_RESULT["paid"]));
		$this->assertTrue(isset($API_TEST_RESULT["unpaid"]));
		$this->assertEquals($orig_inProgress, count($API_TEST_RESULT["inProgress"]));
		$this->assertEquals($orig_unpaid + 1, count($API_TEST_RESULT["unpaid"]));
		$this->assertEquals($orig_paid, count($API_TEST_RESULT["paid"]));

		//Make payment
		$_SERVER['REQUEST_METHOD'] = "POST";
		$_GET["collection"] = "tenders";
		$_JSON = array("orderId" => $orderId, "amountTendered" => 500, "changeGiven" => 300);
		unset($API_TEST_RESULT);
		include("../api/api.php");
		$this->assertTrue(isset($API_TEST_RESULT));
		$this->assertTrue(isset($API_TEST_RESULT["status"]));
		$this->assertEquals("success", $API_TEST_RESULT["status"]);

		//Check totals (paid)
		$_SERVER['REQUEST_METHOD'] = "GET";
		$_GET = array("api" => "v1", "collection" => "orders");
		$_JSON = array();
		unset($API_TEST_RESULT);
		include("../api/api.php");
		$this->assertTrue(isset($API_TEST_RESULT));
		$this->assertTrue(isset($API_TEST_RESULT["status"]));
		$this->assertEquals("success", $API_TEST_RESULT["status"]);
		$this->assertTrue(isset($API_TEST_RESULT["inProgress"]));
		$this->assertTrue(isset($API_TEST_RESULT["paid"]));
		$this->assertTrue(isset($API_TEST_RESULT["unpaid"]));
		$this->assertEquals($orig_inProgress, count($API_TEST_RESULT["inProgress"]));
		$this->assertEquals($orig_unpaid, count($API_TEST_RESULT["unpaid"]));
		$this->assertEquals($orig_paid + 1, count($API_TEST_RESULT["paid"]));
	}
}

?>