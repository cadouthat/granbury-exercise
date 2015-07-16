describe("cashRegApp", function() {
	var scope, expectedAPI;

	beforeEach(function() {
		module("cashRegApp");
		expectedAPI = "http://localhost/granbury/api/v1/";
	});

	describe('cashRegCtrl init', function () {

		var $httpBackend,
			httpController;

		beforeEach(inject(function ($rootScope, $controller, _$httpBackend_) {
			$httpBackend = _$httpBackend_;
			scope = $rootScope.$new();
			httpController = $controller('cashRegCtrl', {
				'$scope': scope
			});
		}));

		afterEach(function() {
			$httpBackend.verifyNoOutstandingExpectation();
			$httpBackend.verifyNoOutstandingRequest();
		});

		it('starts with a blank order', function () {
			$httpBackend.expectGET(expectedAPI + "items/").respond(200, {"status": "success"});
			$httpBackend.flush();
			expect(scope.view).toEqual("order");
			expect(scope.curOrder).toBeDefined();
			expect(scope.curOrder.orderId).toEqual(0);
			expect(scope.curOrder.subTotal).toEqual(0);
			expect(scope.curOrder.totalTax).toEqual(0);
			expect(scope.curOrder.grandTotal).toEqual(0);
			expect(scope.curOrder.lines).toEqual([]);
			expect(scope.curOrder.payNow).not.toBeDefined();
			expect(scope.curOrder.activeLine).not.toBeDefined();
		});

		it('detects HTTP failure', function () {
			$httpBackend.expectGET(expectedAPI + "items/").respond(404, {"status": "success"});
			$httpBackend.flush();
			expect(scope.errorMessage).toEqual("HTTP Failure");
		});

		it('detects API failure', function () {
			$httpBackend.expectGET(expectedAPI + "items/").respond(200, {"status": "failure", "message": "Phony Error"});
			$httpBackend.flush();
			expect(scope.errorMessage).toEqual("Phony Error");
		});
	});

	describe('cashRegCtrl order', function () {

		var $httpBackend,
			httpController,
			testItems;

		beforeEach(inject(function ($rootScope, $controller, _$httpBackend_) {
			$httpBackend = _$httpBackend_;
			testItems = [
				{"name": "A", "price": 199},
				{"name": "B", "price": 200},
				{"name": "C", "price": 3000}
			];
			$httpBackend.expectGET(expectedAPI + "items/").respond(200, {"status": "success", "items": testItems});
			scope = $rootScope.$new();
			httpController = $controller('cashRegCtrl', {
				'$scope': scope
			});
			$httpBackend.flush();
		}));

		afterEach(function() {
			$httpBackend.verifyNoOutstandingExpectation();
			$httpBackend.verifyNoOutstandingRequest();
		});

		it('gets the item list', function () {
			expect(scope.errorMessage).not.toBeDefined();
			expect(scope.menuItems).toEqual(testItems);
		});

		it('can create an order', function () {
			$httpBackend.expectPOST(expectedAPI + "orders/").respond(200, {"status": "success", "orderId": 123});
			scope.API_createOrder(undefined);
			$httpBackend.flush();
			expect(scope.curOrder.orderId).toEqual(123);
		});

		it('can add items', function () {
			var totals1 = {"subTotal": 1, "totalTax": 1, "grandTotal": 1};
			var totals2 = {"subTotal": 2, "totalTax": 2, "grandTotal": 2};
			var totals3 = {"subTotal": 3, "totalTax": 3, "grandTotal": 3};
			scope.addItem(testItems[0]);
			$httpBackend.expectPOST(expectedAPI + "orders/").respond(200, {"status": "success", "orderId": 123});
			$httpBackend.expectPOST(expectedAPI + "orders/123").respond(200, {"status": "success", "totals": totals1});
			$httpBackend.flush();
			scope.addItem(testItems[1]);
			$httpBackend.expectPOST(expectedAPI + "orders/123").respond(200, {"status": "success", "totals": totals2});
			$httpBackend.flush();
			scope.addItem(testItems[0]);
			$httpBackend.expectPOST(expectedAPI + "orders/123").respond(200, {"status": "success", "totals": totals3});
			$httpBackend.flush();
			expect(scope.curOrder.orderId).toEqual(123);
			expect(scope.curOrder.subTotal).toEqual(3);
			expect(scope.curOrder.totalTax).toEqual(3);
			expect(scope.curOrder.grandTotal).toEqual(3);
			expect(scope.curOrder.lines.length).toEqual(2);
		});

		it('can remove items', function () {
			var totals1 = {"subTotal": 1, "totalTax": 1, "grandTotal": 1};
			var totals2 = {"subTotal": 2, "totalTax": 2, "grandTotal": 2};
			var totals3 = {"subTotal": 1, "totalTax": 0, "grandTotal": 1};
			scope.addItem(testItems[0]);
			$httpBackend.expectPOST(expectedAPI + "orders/").respond(200, {"status": "success", "orderId": 123});
			$httpBackend.expectPOST(expectedAPI + "orders/123").respond(200, {"status": "success", "totals": totals1});
			$httpBackend.flush();
			scope.addItem(testItems[1]);
			$httpBackend.expectPOST(expectedAPI + "orders/123").respond(200, {"status": "success", "totals": totals2});
			$httpBackend.flush();
			scope.addItem(testItems[0]);
			$httpBackend.expectPOST(expectedAPI + "orders/123").respond(200, {"status": "success", "totals": totals2});
			$httpBackend.flush();
			scope.curOrder.activeLine = 0;
			scope.voidItem();
			$httpBackend.expectDELETE(expectedAPI + "orders/123/A").respond(200, {"status": "success", "totals": totals3});
			$httpBackend.flush();
			expect(scope.curOrder.orderId).toEqual(123);
			expect(scope.curOrder.subTotal).toEqual(1);
			expect(scope.curOrder.totalTax).toEqual(0);
			expect(scope.curOrder.grandTotal).toEqual(1);
			expect(scope.curOrder.lines.length).toEqual(1);
		});

		it('can accept tender', function () {
			var totals1 = {"subTotal": 1, "totalTax": 1, "grandTotal": 1};
			scope.addItem(testItems[0]);
			$httpBackend.expectPOST(expectedAPI + "orders/").respond(200, {"status": "success", "orderId": 123});
			$httpBackend.expectPOST(expectedAPI + "orders/123").respond(200, {"status": "success", "totals": totals1});
			$httpBackend.flush();
			scope.curOrder.payNow = {"tendered": 5.00, "changeDue": 4.00};
			scope.submitTender();
			$httpBackend.expectPOST(expectedAPI + "tenders/").respond(200, {"status": "success"});
			$httpBackend.expectPUT(expectedAPI + "orders/123").respond(200, {"status": "success", "orderNumber": 50});
			$httpBackend.flush();
			expect(scope.curOrder.payNow).toBeNull();
			expect(scope.curOrder.orderId).toEqual(123);
			expect(scope.curOrder.orderNumber).toEqual(50);
			expect(scope.curOrder.totalTendered).toEqual(500);
			expect(scope.curOrder.totalChange).toEqual(400);
		});
	});

	describe('cashRegCtrl order list', function () {

		var $httpBackend,
			httpController,
			testItems,
			testOrders;

		beforeEach(inject(function ($rootScope, $controller, _$httpBackend_) {
			$httpBackend = _$httpBackend_;
			testItems = [
				{"name": "A", "price": 199},
				{"name": "B", "price": 200},
				{"name": "C", "price": 3000}
			];
			testOrders = {"inProgress": [
				{"orderId": 123, "orderNumber": 0, "timestamp": "test", "subTotal": 100, "totalTax": 20, "grandTotal": 120, "totalTendered": 150, "totalChange": 30},
				{"orderId": 124, "orderNumber": 0, "timestamp": "test", "subTotal": 100, "totalTax": 20, "grandTotal": 100, "totalTendered": 0, "totalChange": 0}],
				"unpaid": [
				{"orderId": 125, "orderNumber": 0, "timestamp": "test", "subTotal": 100, "totalTax": 20, "grandTotal": 120, "totalTendered": 120, "totalChange": 30}],
				"paid": [
				{"orderId": 126, "orderNumber": 0, "timestamp": "test", "subTotal": 100, "totalTax": 20, "grandTotal": 120, "totalTendered": 150, "totalChange": 30}]
			};
			$httpBackend.expectGET(expectedAPI + "items/").respond(200, {"status": "success", "items": testItems});
			scope = $rootScope.$new();
			httpController = $controller('cashRegCtrl', {
				'$scope': scope
			});
			$httpBackend.flush();
		}));

		afterEach(function() {
			$httpBackend.verifyNoOutstandingExpectation();
			$httpBackend.verifyNoOutstandingRequest();
		});

		it('gets the order list', function () {
			scope.setView("orderList");
			$httpBackend.expectGET(expectedAPI + "orders/").respond(200, angular.extend({"status": "success"}, testOrders));
			$httpBackend.flush();
			expect(scope.orderList).toBeDefined();
			expect(scope.orderList.length).toEqual(4);
		});

		it('can view an order', function () {
			scope.setView("orderList");
			$httpBackend.expectGET(expectedAPI + "orders/").respond(200, angular.extend({"status": "success"}, testOrders));
			$httpBackend.flush();
			scope.selectOrder(scope.orderList[1]);
			$httpBackend.expectGET(expectedAPI + "items/").respond(200, {"status": "success", "items": testItems});
			$httpBackend.flush();
			expect(scope.view).toEqual("order");
			expect(scope.curOrder.orderId).toEqual(124);
		});
	});
});
