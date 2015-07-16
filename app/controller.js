API_BASE = "http://localhost/granbury/api/v1/";

function cashRegCtrl($scope, $http)
{
	$scope.view = "order";

	$scope.curOrder = {
		"orderNumber": 0,
		"subTotal": 0,
		"totalTax": 0,
		"grandTotal": 0,
		"lines": []
	};

	$http.get(API_BASE + "items/").success(function(response) { $scope.menuItems = response.items; });

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

	$scope.padNum = function(n, d) {
		n = n.toString();
		while(n.length < d) n = "0" + n;
		return n;
	}

	$scope.addItem = function(item) {
		if(!$scope.curOrder.orderId)
		{
			$scope.busy = true;
			$http.post(API_BASE + "orders/").success(function(response){
				if(response.status != "success") alert(response.message);
				else
				{
					$scope.curOrder.orderId = response.orderId;
					$scope.addItem(item);
				}
				$scope.busy = false;
			}).error(function(){
				alert("HTTP Failure");
				$scope.busy = false;
			});
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
		$scope.API_updateLine($scope.curOrder.lines[ind].name, 0);
		$scope.curOrder.lines.splice(ind, 1);
		$scope.curOrder.activeLine = undefined;
	};
}

angular.module('cashRegApp', []).controller('cashRegCtrl', cashRegCtrl);
