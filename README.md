
Some development notes:

* The PAYONE Platform and its connected systems are designed for IP addresses Version 4.
* IP ranges: 213.178.72.196, 213.178.72.197, 217.70.200.0/24, 185.60.20.0/24
* A "Pseudo card number" is supported by PAYONE, which appears to be a saved token system.
* Maybe the "Pseudo card number" is for a JavaScript tokenisation approac, so WILL need to be supported.
* The 3D Secure process needs to be fully implemnented.
* When sending 3D Secure details, do we need to leave off the personal details?
* The currency is ISO 4217
* The countty is ISO 3166
* The state is ISO 3166-2 and only for countries: US, CA, CN, JP, MX, BR, AR, ID, TH, IN
* Other transaction types to support on the "Shop" API: refund, vauthorization, creditcardcheck (AJAX API?), 3dscheck, addresscheck
* The "Access" API is not supported at all yet, with some share transaction types and some unique to that API, e.g. customer,
  access, contract and invoice management.
* The PAYONE server may send transaction status messages to the merchant server (from 185.60.20.0/24) for ALL transactions.
  * It would be nice to have a handler for incoming messages. It is quite a long and flexible message.
  * It needs a TSOK response to stop it repeating every six hours. This response is different for different interface types, e.g. SSOK.
  * They are also always ISO-8859-1 encoded, which is a little crazy, but optional conversion would be good.
  * Some of these messages inform the merchant site of reversals or cancellation of transactions, so it is important
  to include them.
  * Some of these messages seem to include credit card numbers, which does not seem right.
  * We need to handle these to find out what happens to PENDING transactions.
* The "param" parameter looks like a custom field tat can be used for details like invoice ID. We should support it.
* Other gateway types exist: iframe, "classic" (remote redirect?), JavaScript, one-click purchasing.
* A basket is supported with array parameters.

## Hosted iframe Mode

JS to include on page: https://secure.pay1.de/client-api/js/v1/payone_hosted_min.js

This JS includes "classic" client-API mode functions supported by "AJAX mode" and "Redirect mode" (also worth following up)

config.cardtype defines available card types (from list in package)

A hash is used in this process, based on all the parameters used to set up the iframe form.
Either md5($fields) or hash_hmac("sha384", $fields)

See Platform_Client_API.pdf A few good examples are listed of the front-end markup and JS.

URLs

* Server API URL: https://api.pay1.de/post-gateway/
* URL classic: https://secure.pay1.de/frontend/
* iframe URL: https://frontend.pay1.de/frontend/v2/
* Client API URL (AJAX?): https://secure.pay1.de/client-api/

It looks like basket items are mandatory, and each item must have a stock ID and a VAT record.

