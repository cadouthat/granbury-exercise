API_BASE = "http://localhost/granbury/api/v1/";

function cashRegCtrl($scope, $http)
{
	//Request latest item list
	$scope.API_listItems = function() {
		$http.get(API_BASE + "items/").success(function(response) {
			if(response.status != "success") alert(response.message);
			else $scope.menuItems = response.items; //Replace menu
		}).error(function(){
			alert("HTTP Failure");
		});
	};

	//Upload item CSV
	$scope.API_addItems = function() {
		var buildForm = new FormData();
		buildForm.append('csv', $scope.importFile);
		$http.post(API_BASE + "items/", buildForm, {
			transformRequest: angular.identity,
			headers: {'Content-Type': undefined}
		}).success(function(response) {
			if(response.status != "success") alert(response.message);
			else alert("Import successful"); //Simple success notification
		}).error(function(){
			alert("HTTP Failure");
		});
	};

	//Request latest order list
	$scope.API_listOrders = function() {
		$http.get(API_BASE + "orders/").success(function(response) {
			if(response.status != "success") alert(response.message);
			else
			{
				//Merge into single array with status
				for(var i = 0; i < response.inProgress.length; i++) response.inProgress[i].status = "In Progress";
				for(var i = 0; i < response.paid.length; i++) response.paid[i].status = "Paid";
				for(var i = 0; i < response.unpaid.length; i++) response.unpaid[i].status = "Unpaid";
				$scope.orderList = response.inProgress.concat(response.paid).concat(response.unpaid);
			}
		}).error(function(){
			alert("HTTP Failure");
		});
	};

	//Request a new (empty) order
	$scope.API_createOrder = function(pendingItem) {
		//The busy flag will disable input during requests
		//	NOTE: Is this reliable enough?
		$scope.busy = true;
		$http.post(API_BASE + "orders/").success(function(response){
			$scope.busy = false;
			if(response.status != "success") alert(response.message);
			else
			{
				//Assign new order ID to current order
				//	NOTE: needs safety check?
				$scope.curOrder.orderId = response.orderId;
				//Finally, add pending item
				if(pendingItem) $scope.addItem(pendingItem);
			}
		}).error(function(){
			$scope.busy = false;
			alert("HTTP Failure");
		});
	};

	//Update an order line
	$scope.API_updateLine = function(name, qty) {
		$scope.busy = true;
		//Post item name and new quantity
		$http.post(API_BASE + "orders/" + $scope.curOrder.orderId, {
			"itemName": name,
			"qty": qty
		}).success(function(response){
			if(response.status != "success") alert(response.message);
			else
			{
				//Replace the order totals with those in the response
				angular.extend($scope.curOrder, response.totals);
			}
			$scope.busy = false;
		}).error(function(){
			alert("HTTP Failure");
			$scope.busy = false;
		});
	};

	//Remove an order line
	$scope.API_deleteLine = function(name) {
		$scope.busy = true;
		//Delete order-line URI
		$http.delete(API_BASE + "orders/" + $scope.curOrder.orderId + "/" + encodeURIComponent(encodeURIComponent(name))
		).success(function(response){
			if(response.status != "success") alert(response.message);
			else
			{
				//Replace the order totals with those in the response
				angular.extend($scope.curOrder, response.totals);
			}
			$scope.busy = false;
		}).error(function(){
			alert("HTTP Failure");
			$scope.busy = false;
		});
	};

	//Request an order be submitted and assigned an order number
	$scope.API_submitOrder = function() {
		$scope.busy = true;
		$http.put(API_BASE + "orders/" + $scope.curOrder.orderId
		).success(function(response){
			if(response.status != "success") alert(response.message);
			else
			{
				//Assign order number to current order
				//	NOTE: needs safety check?
				$scope.curOrder.orderNumber = response.orderNumber;
			}
			$scope.busy = false;
		}).error(function(){
			alert("HTTP Failure");
			$scope.busy = false;
		});
	};

	//Submit a new tender record
	$scope.API_submitTender = function(amountTendered, changeGiven) {
		$scope.busy = true;
		//Post order ID, amount, and change
		$http.post(API_BASE + "tenders/", {
			"orderId": $scope.curOrder.orderId,
			"amountTendered": amountTendered,
			"changeGiven": changeGiven
		}).success(function(response){
			$scope.busy = false;
			if(response.status != "success") alert(response.message);
			else
			{
				//Update the order tender totals
				$scope.curOrder.totalTendered = ($scope.curOrder.totalTendered || 0) + amountTendered;
				$scope.curOrder.totalChange = ($scope.curOrder.totalChange || 0) + changeGiven;
				//Now that payment has been made, submit the order
				//	NOTE: Depending on API rules, submission may need to happen before payment?
				if($scope.curOrder.orderNumber <= 0) $scope.API_submitOrder();
			}
		}).error(function(){
			$scope.busy = false;
			alert("HTTP Failure");
		});
	};

	//Pad number with 0s
	$scope.padNum = function(n, d) {
		n = n.toString();
		while(n.length < d) n = "0" + n;
		return n;
	}

	//Re-calculate change due in tender dialog
	$scope.updateChangeDue = function() {
		$scope.curOrder.payNow.changeDue = Math.max(0, $scope.curOrder.payNow.tendered - $scope.curOrder.grandTotal / 100);
	};

	//Switch to a different view and reset
	$scope.setView = function(v) {
		switch(v)
		{
			case "order":
				$scope.curOrder = {
					"orderNumber": 0,
					"subTotal": 0,
					"totalTax": 0,
					"grandTotal": 0,
					"lines": []
				};
				$scope.API_listItems();
				break;

			case "orderList":
				$scope.API_listOrders();
				$scope.orderToggle = {
					"All": true,
					"In Progress": true,
					"Paid": true,
					"Unpaid": true
				};
				break;
		}
		$scope.view = v;
	};

	//Add an item to current order
	$scope.addItem = function(item) {
		//If the order does not exist, create one first
		if(!$scope.curOrder.orderId)
		{
			//Add the item after creating
			$scope.API_createOrder(item);
			return;
		}
		//Find existing order line for item
		var exIndex = -1;
		for(var i = 0; i < $scope.curOrder.lines.length; i++)
		{
			if(item.name == $scope.curOrder.lines[i].name)
			{
				exIndex = i;
				break;
			}
		}
		//Get new quantity (add 1 if present)
		var qty = (exIndex < 0) ? 1 : ($scope.curOrder.lines[exIndex].qty + 1);
		//Calculate price from stored item price
		var extPrice = qty * item.price;
		if(exIndex < 0)
		{
			//Add new line
			$scope.curOrder.lines.push({
				"name": item.name,
				"qty": qty,
				"extendedPrice": extPrice
			});
		}
		else
		{
			//Update existing line
			$scope.curOrder.lines[exIndex].qty = qty;
			$scope.curOrder.lines[exIndex].extendedPrice = extPrice;
		}
		//Call update API with new qty
		$scope.API_updateLine(item.name, qty);
	};

	//Remove highlighted item from order
	$scope.voidItem = function() {
		//Get selected index
		//	NOTE: needs safety check?
		var ind = $scope.curOrder.activeLine;
		//Call delete API and remove stored line
		$scope.API_deleteLine($scope.curOrder.lines[ind].name);
		$scope.curOrder.lines.splice(ind, 1);
		$scope.curOrder.activeLine = undefined;
	};

	//Submit a tender record
	$scope.submitTender = function() {
		//Get values as cents
		var tendered = Math.round($scope.curOrder.payNow.tendered * 100);
		var changeDue = Math.round($scope.curOrder.payNow.changeDue * 100);
		//Call tender API
		$scope.API_submitTender(tendered, changeDue);
		//Reset and close payment dialog
		$scope.curOrder.payNow = null;
	};

	//Flip order list status toggles
	$scope.doOrderToggle = function(type) {
		//Get new value
		var state = !$scope.orderToggle[type];
		if(type == "All")
		{
			//Set all toggles to new value
			$scope.orderToggle["All"] = state;
			$scope.orderToggle["In Progress"] = state;
			$scope.orderToggle["Unpaid"] = state;
			$scope.orderToggle["Paid"] = state;
		}
		else
		{
			//Flip specific status
			$scope.orderToggle[type] = state;
			//Update "All" toggle
			$scope.orderToggle["All"] = ($scope.orderToggle["In Progress"] &&
				$scope.orderToggle["Unpaid"] &&
				$scope.orderToggle["Paid"]);
		}
	};

	//Open the order view for a specific order
	$scope.selectOrder = function(order) {
		$scope.setView('order');
		//Replace current order with copied order
		$scope.curOrder = angular.extend({"lines": []}, order);
	};

	//Default to order view (empty order)
	$scope.setView('order');
}

angular.module('cashRegApp', [])
	.controller('cashRegCtrl', cashRegCtrl)
	.directive('fileInput', ['$parse', function ($parse) { //Directive for accessing file inputs
		return {
			link: function(scope, element, attr) {
				var setTarget = $parse(attr.fileInput).assign;
				element.bind('change', function() {
					scope.$apply(function() {
						setTarget(scope, element[0].files[0]);
					});
				});
			}
		};
	}]);
