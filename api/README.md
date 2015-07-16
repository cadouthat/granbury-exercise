Granbury API Overview
=====================

All API responses are JSON objects and contain "status" ("success" or "failure"). Failure responses have an optional "message" attribute, and will not contain any additional information.

Items API
---------

  * /api/v1/items/
    * GET - List all items. Returns **items: [{name, price}, ...]**
    * POST - Add items from CSV file (name, price). The first row is ignored. Requires *"csv"* (multipart/form-data file)

Orders API
----------

  * /api/v1/orders/
    * GET - List all orders. Returns **inProgress: [{orderId, orderNumber, timestamp, subTotal, totalTax, grandTotal, totalTendered, totalChange}, ...], paid: [...], unpaid: [...]**
    * POST - Create a new (empty) order. Returns **orderId**
  * /api/v1/orders/(orderId)
    * PUT - Submit order. Returns **orderNumber**
    * POST - Add (or update) order line. Requires *"itemName", "qty"* (JSON). Returns **totals: {subTotal, totalTax, grandTotal}**
  * /api/v1/orders/(orderId)/(itemName)
    * DELETE - Remove order line. Returns **totals** (see above)

Tenders API
-----------

  * /api/v1/tenders/
    * POST - Submit a new payment record. Requires *"orderId", "amountTendered", "changeGiven"* (JSON)
