**This repository is very much in development but should be finised shortly. The notes below will be converted to proper documentation.**

# Omnipay: [PAYONE](https://www.payone.de/)

**PAYONE driver for the Omnipay PHP payment processing library**

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic, multi-gateway payment
processing library for PHP 5.3+. This package implements PAYONE support for Omnipay.

![Alt text](docs/PAYONE_Logo_480.png?raw=true "PAYONE")

## Installation

Omnipay is installed via [Composer](http://getcomposer.org/). To install, simply add it
to your `composer.json` file:

```json
{
    "require": {
        "aimeos/omnipay-payone": "~2.0"
    }
}
```

While in development, it can be obtained from this repository:

```json
{
    "require": {
        "aimeos/omnipay-payone": "dev-master"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/academe/OmniPay-Payone.git"
        }
    ]
}
```

And run composer to update your dependencies:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar update

## Basic Usage

The following gateways are provided by this package:

* Payone_Shop
* Payone_ShopFrontend

For general usage instructions, please see the main [Omnipay](https://github.com/thephpleague/omnipay)
repository. You will find more specific details can be found below.

### Gateway Background

The [PAYONE API](https://www.payone.de/en/platform-integration/interfaces/) has three main access points
of interest to e-commerce:

* **Server API** - for interacting directly with server without user intervention.
* **Front end** - for delivering hosted credit card (CC) forms to the user.
* Client API - for interacting with a JavaScript front end.

This package is interested in just the first two; Server API for capturing authorized payments and the Front end
for setting up CC forms. You can also do authorisations and make payments using the Server API, so long as
you are fully aware of the PCI implications.

The Front end API also has a notification handler for receiving the payment results and captured user information
from the PAYONE servers.

### Extended Items (Order Lines)

The PAYONE API supports two additional item properties that must be completed (`id` and `vat`). Since the OmniPay v2
`Item` object cannot accept custom property values, this has been extended. The extended `Item` class can be found here:

    \Omnipay\Payone\Extend\Item

Creating an `Item` uses these fields:

~~~php
$lines[] = new \Omnipay\Payone\Extend\Item([
    'id' => '{merchant-site-stock-ID}',
    'name' => '{product-name}',
    'quantity' => 2,
    'price' => 123,
    'vat' => 20,
]);
~~~

The `price` is supplied in *minor currency units* as an integer. These are passed as supplied direct to the gateway.
Note that OmniPay does not specify, parse nor validate the price units of an Item, so we make the decision
on how to handle it here. *This may change if the units can be clarified.*

The items are then added to the `ItemBag` in the normal way:

~~~php
$items = new \Omnipay\Common\ItemBag($lines);
~~~

It does not appear tha the item prices are validated on the PAYONE servers. Some gateways will reject a payment
if the cart items do not exactly add up to the order total. PAYONE appears to treat these items as information only.

======

## Development Notes

Some development notes yet to be incorporated into the code or documentation:

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

It looks like basket items are mandatory (using the Frontend mode), and each item must have a stock ID and a VAT record.

