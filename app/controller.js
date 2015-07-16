API_BASE = "http://localhost/granbury/api/v1/";

function cashRegCtrl($scope, $http)
{
	$scope.API_listItems = function() {
		$http.get(API_BASE + "items/").success(function(response) {
			if(response.status != "success") alert(response.message);
			else $scope.menuItems = response.items;
		}).error(function(){
			alert("HTTP Failure");
		});
	};

	$scope.API_addItems = function() {
		var buildForm = new FormData();
		buildForm.append('csv', $scope.importFile);
		$http.post(API_BASE + "items/", buildForm, {
			transformRequest: angular.identity,
			headers: {'Content-Type': undefined}
		}).success(function(response) {
			if(response.status != "success") alert(response.message);
			else alert("Import successful");
		}).error(function(){
			alert("HTTP Failure");
		});
	};

	$scope.API_listOrders = function() {
		$http.get(API_BASE + "orders/").success(function(response) {
			if(response.status != "success") alert(response.message);
			else
			{
				for(var i = 0; i < response.inProgress.length; i++) response.inProgress[i].status = "In Progress";
				for(var i = 0; i < response.paid.length; i++) response.paid[i].status = "Paid";
				for(var i = 0; i < response.unpaid.length; i++) response.unpaid[i].status = "Unpaid";
				$scope.orderList = response.inProgress.concat(response.paid).concat(response.unpaid);
			}
		}).error(function(){
			alert("HTTP Failure");
		});
	};

	$scope.API_createOrder = function(pendingItem) {
		$scope.busy = true;
		$http.post(API_BASE + "orders/").success(function(response){
			if(response.status != "success") alert(response.message);
			else
			{
				$scope.curOrder.orderId = response.orderId;
				if(pendingItem) $scope.addItem(pendingItem);
			}
			$scope.busy = false;
		}).error(function(){
			alert("HTTP Failure");
			$scope.busy = false;
		});
	};

	$scope.API_updateLine = function(name, qty) {
		$scope.busy = true;
		$http.post(API_BASE + "orders/" + $scope.curOrder.orderId, {
			"itemName": name,
			"qty": qty
		}).success(function(response){
			if(response.status != "success") alert(response.message);
			else
			{
				angular.extend($scope.curOrder, response.totals);
			}
			$scope.busy = false;
		}).error(function(){
			alert("HTTP Failure");
			$scope.busy = false;
		});
	};

	$scope.API_deleteLine = function(name) {
		$scope.busy = true;
		$http.delete(API_BASE + "orders/" + $scope.curOrder.orderId + "/" + name
		).success(function(response){
			if(response.status != "success") alert(response.message);
			else
			{
				angular.extend($scope.curOrder, response.totals);
			}
			$scope.busy = false;
		}).error(function(){
			alert("HTTP Failure");
			$scope.busy = false;
		});
	};

	$scope.API_submitOrder = function() {
		$scope.busy = true;
		$http.put(API_BASE + "orders/" + $scope.curOrder.orderId
		).success(function(response){
			if(response.status != "success") alert(response.message);
			else
			{
				$scope.curOrder.orderNumber = response.orderNumber;
			}
			$scope.busy = false;
		}).error(function(){
			alert("HTTP Failure");
			$scope.busy = false;
		});
	};

	$scope.API_submitTender = function(amountTendered, changeGiven) {
		$scope.busy = true;
		$http.post(API_BASE + "tenders/", {
			"orderId": $scope.curOrder.orderId,
			"amountTendered": amountTendered,
			"changeGiven": changeGiven
		}).success(function(response){
			if(response.status != "success") alert(response.message);
			else
			{
				$scope.curOrder.totalTendered = ($scope.curOrder.totalTendered || 0) + amountTendered;
				$scope.curOrder.totalChange = ($scope.curOrder.totalChange || 0) + changeGiven;
				if($scope.curOrder.orderNumber <= 0) $scope.API_submitOrder();
			}
			$scope.busy = false;
		}).error(function(){
			alert("HTTP Failure");
			$scope.busy = false;
		});
	};

	$scope.padNum = function(n, d) {
		n = n.toString();
		while(n.length < d) n = "0" + n;
		return n;
	}

	$scope.updateChangeDue = function() {
		$scope.curOrder.payNow.changeDue = Math.max(0, $scope.curOrder.payNow.tendered - $scope.curOrder.grandTotal / 100);
	};

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

	$scope.addItem = function(item) {
		if(!$scope.curOrder.orderId)
		{
			$scope.API_createOrder(item);
			return;
		}
		var exIndex = -1;
		for(var i = 0; i < $scope.curOrder.lines.length; i++)
		{
			if(item.name == $scope.curOrder.lines[i].name)
			{
				exIndex = i;
				break;
			}
		}
		var qty = (exIndex < 0) ? 1 : ($scope.curOrder.lines[exIndex].qty + 1);
		var extPrice = qty * item.price;
		if(exIndex < 0)
		{
			$scope.curOrder.lines.push({
				"name": item.name,
				"qty": qty,
				"extendedPrice": extPrice
			});
		}
		else
		{
			$scope.curOrder.lines[exIndex].qty = qty;
			$scope.curOrder.lines[exIndex].extendedPrice = extPrice;
		}
		$scope.API_updateLine(item.name, qty);
	};

	$scope.voidItem = function() {
		var ind = $scope.curOrder.activeLine;
		$scope.API_deleteLine($scope.curOrder.lines[ind].name);
		$scope.curOrder.lines.splice(ind, 1);
		$scope.curOrder.activeLine = undefined;
	};

	$scope.submitTender = function() {
		var tendered = Math.round($scope.curOrder.payNow.tendered * 100);
		var changeDue = Math.round($scope.curOrder.payNow.changeDue * 100);
		$scope.API_submitTender(tendered, changeDue);
		$scope.curOrder.payNow = null;
	};

	$scope.doOrderToggle = function(type) {
		var state = !$scope.orderToggle[type];
		if(type == "All")
		{
			$scope.orderToggle["All"] = state;
			$scope.orderToggle["In Progress"] = state;
			$scope.orderToggle["Unpaid"] = state;
			$scope.orderToggle["Paid"] = state;
		}
		else
		{
			$scope.orderToggle[type] = state;
			$scope.orderToggle["All"] = ($scope.orderToggle["In Progress"] &&
				$scope.orderToggle["Unpaid"] &&
				$scope.orderToggle["Paid"]);
		}
	};

	$scope.selectOrder = function(order) {
		$scope.setView('order');
		$scope.curOrder = order;
		$scope.curOrder.lines = [];
	};

	$scope.setView('order');
}

angular.module('cashRegApp', [])
	.controller('cashRegCtrl', cashRegCtrl)
	.directive('fileInput', ['$parse', function ($parse) {
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
