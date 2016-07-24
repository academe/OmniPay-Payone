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

### Shop and Access API Versions

A payment portal is set up on PAYONE as one of two versions:

* Shop
* Access

The `Shop` portal version is used for one-off payments. The `Access` portal version is used for subscriptions,
invoicing, continuous renewals and services. Some of the payment methods are available just to the `Shop` version
and some are available just to the `Access` version. Some methods are available to both versions, but accept
slightly different sets of parameters.

For now, this package will deal with the `Shop` version only. However, the naming of classes and services will
allow for `Access` version methods to be added later if required.

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

If you do not use the extended `Item` then default values will be substituted (`"000000"` for the `id` and `0` for the
`vat` figure).

## The Shop API Gateway

Create a gateway object to access the Server API Shop version methods:

~~~php
$gateway = Omnipay\Omnipay::create('Payone_Shop');

// Merchant Account ID
$gateway->setMerchantId(12345);
// Merchant Portal ID
$gateway->setPortalId(1234567);
// Sub-Acount ID
$gateway->setSubAccountId(56789);
// True to use test mode.
$gateway->setTestMode(true);
// Default language is "en" and determines the language of forms and error messages.
$gateway->setOLanguage("de");
// Currency as ISO 4217 three-character code
$gateway->setCurrency('EUR');
~~~

### Server API Authorize Payment

PAYONE calls this "pre-authorization". It authoprizes a payment to be captured later.

To create an authorization request:

~~~php
$request = $gateway->authorize([
    // Unique merchant site transaction ID
    'transactionId' => 'ABC123',
    // Amount as decimal.
    'amount' => 0.00,
    // Pre-shared secret key used for hashing and authentication.
    'portalKey' => 'Ab12Cd34Ef56Gh78',
    // Card and personal details.
    'card' => $card,
    // Optional card type override.
    //'cardType' => 'V',
    // The ItemBag/Cart
    'items' => $items,
]);
~~~

The driver will attempt to work out the card type from the card number, but if it fails or
you are using a card type not yet supported by the driver or by OmniPay, then you can supply
your own card type letter.

The `$card` details are populated like any other standard OmniPay card, with one exception detailed
below. You can supply the details as an array or as a `\Omnipay\Common\CreditCard` object.

These four fields normally define the details for a credit card:

~~~php
[
    ...
    'number' => '4111111111111111',
    'expiryYear' => '2020',
    'expiryMonth' => '12',
    'cvv' => '123',
];
~~~

PAYONE will also accept a "pseudo-card" number. This is a temporary token supplied by another
process (e.g. a "creditcardcheck") and used in place of a card. If supplying a pseudo-card number,
leave the remaining card fields blank or null. The gateway driver will then treat the card number
as a pseudo-card:

~~~php
[
    ...
    'number' => '4111111111111111',
    'expiryYear' => null,
    'expiryMonth' => null,
    'cvv' => null,
];
~~~

Also to note about the card data is that countries must be supplied as ISO 3166 tw-letter codes:

~~~php
    'billingCountry' => 'US',
~~~

and states must be supplied as ISO 3166-2 sub-division codes (various formats, depending on the country):

~~~php
    'billingCountry' => 'US',
    'billingState' => 'AL',
~~~

Send this request to PAYONE to get the response:

~~~php
$response = $request->send();
~~~

The standard OmniPay documentation shows how to handle the response. In addition, in the event
of an error, there will be the normal loggable error message, and an error message that is safe
to put in front of an end user:

~~~php
if (!$response->isSuccessful()) {
    echo $response->getMessage();
    // e.g. "Expiry date invalid, incorrect or in the past"
    echo $response->getCustomerMessage();
    // e.g. "Invalid card expiry date. Please verify your card data."
}
~~~

### Server API Purchase

PAYONE calls this "authorization". It authoprizes and captures a payment immediately.

It is used and responses in the same way as `authorize`. The request message is created:

~~~php
$request = $gateway->purchase([...]);
~~~

### Server API Capture

Once a payment has been authorised, it may be captured. This is done using this minimal message:

~~~php
$request = $gateway->capture([
    // The reference for the original authorize transaction.
    'transactionReference' => '123456789',
    // Amount as decimal.
    'amount' => 1.23,
    // Pre-shared secret key used for authentication, if not already set on $gateway.
    'portalKey' => 'Ab12Cd34Ef56Gh78',
]);
~~~

That will capture the amount specified and settle the account. If you want to leave the
account open for capturing the total in multiple stages, then specify for the account to
be left unsettled:

~~~php
    'sedquenceNumber' => $sequence,
    'settleAccount' => false,
~~~

The sequence number starts at 1 for the first capture, and must be incremented for each
subsequent capture.

### Server API Void

To void an authorized payment:

~~~php
$request = $gateway->void([
    // The reference for the original authorize transaction.
    'transactionReference' => '123456789',
    // Amount as decimal.
    'amount' => 1.23,
    // Pre-shared secret key used for authentication, if not already set on $gateway.
    'portalKey' => 'Ab12Cd34Ef56Gh78',
]);
$rssponse = $request->send();
~~~

The `void` method will response with a `ShopCaptureResponse` response when sent to ONEPAY.

## Notification Callback

For most - if not all - transactions, PAYONE will send details of that transaction to your
notification URL. This URL is specified in the PAYONE account configuration. For most of the
Server API methods it is a convenience. For the Frontend methods it is essential, being the
only way of getting a notification that a transaction has completed.

The notification comes from IP address 185.60.20.0/24 (185.60.20.1 to 185.60.20.254).
This driver does not make any attempt to validate that.

Your application must response to the notification within ten seconds, because when a Frontend
hosted form is used, the user will be waiting on the PAYONE site for the asknowledgement - just
save the data to storage and end.

The notification Server Request (i.e. *incoming* request to your server) is captured by the
`completeStatus`

~~~php
$gateway = Omnipay\Omnipay::create('Payone_Shop');

$server_request = $gateway->acceptNotification();

// The raw data sent is available:
$data = $server_request->getData();
~~~

Individual data items can also be extracted from the server request (see list below).

Once the data is saved, respond to the remote gateway to indicate that you have received
the notification:

~~~php
$server_response = $server_request->send();
// Your application will exit on the next command by default.
// You can prevent that by passiong in `false` as a single parameter, but
// do make sure no further stdout is issued.
$server_response->acknowledge();
~~~

List of $server_request data methods:

* getPaymentPortalKey() - MD5 or SHA2 384, depending on portal account configuration
* getPaymentPortalId()
* getSubAccountId()
* getEvent() - the name of the reason the notification was sent.
  Includes "appointed", "capture", "paid", "underpaid", "cancelation", "refund", "debit", "reminder", "vauthorization", "vsettlement", "transfer", "invoice", "failed"
* getAccessName()
* getAccessCode()
* getTxStatus() - the raw status code
* getTransactionStatus() - OmniPay transaction status codes
* getTransactionId()
* getTransactionReference()
* getNotifyVersion()
* getParam()
* getMode() - test or live
* getSequenceNumber()
* getClearingType() - should always be 'cc'
* getTxTimestamp() - raw unix timestamp
* getTxTime() - timestamp as a \DateTime object
* getCompany()
* getCurrency() -  ISO three-letter code
* getCurrencyObject() - as an OmniPay `Currency` object
* getDebtorId()
* getCustomerId()
* getNumber() - the CC number with hidden middle digits e.g. 411111xxxxxx1111"
* getNumberLastFour() - e.g. "1111"
* getCardType() - PAYONE single-letter code, e.g. "V", "M", "D".
* getBrand() - OmniPay name for the card type, e.g. "visa", "mastercard", "diners".
* getExpireDate() - YYMM format, as supplied
* getExpireDateObject() - expiry date returned as a \DateTime object
* getCardholder() - documented, but seems to be blank most of the time
* getFirstName()
* getLastName()
* getName()
* getStreet()
* getAddress1() - alias of getStreet()
* getCity()
* getPostcode()
* getCountry() - ISO code
* getEmail()
* getPrice() - decimal in major currency units
* getPriceInteger() - integer in minor currency units
* getBalance() - decimal in major currency units
* getBalanceInteger() - integer in minor currency units
* getReceivable() - decimal in major currency units
* getReceivableInteger() - integer in minor currency units

(TODO: Additional fields to be added. TODO: Encoding to be taken into account.)

### completeAuthorize and completePurchase Methods

Although the Frontend purchase and authorize take the user offsite (either in full screen
mode or in an iframe), no data is returned with the user coming back to the site.
as a consequence, the `completeAuthorize` and `completePurchase` methods are not needed.

CHECKME: it is not yet enturely clear how 3D Secure flows in this gateway when using the
Server API methods only, so it may turn out some additional "complete" handling is needed
for that when more is known.


======

## Development Notes

Some development notes yet to be incorporated into the code or documentation:

* The PAYONE Platform and its connected systems are designed for IP addresses Version 4.
* IP ranges: 213.178.72.196, 213.178.72.197, 217.70.200.0/24, 185.60.20.0/24
* The 3D Secure process needs to be fully implemnented.
* When sending 3D Secure details, do we need to leave off the personal details or resend everything again?
* The country is ISO 3166
* Other transaction types to support on the "Shop" API: refund, vauthorization, creditcardcheck (AJAX API?), 3dscheck, addresscheck
* Notifications are also always ISO-8859-1 encoded, which is a little crazy, but optional conversion would be good.
* The response to notifications: "SSOK" for SessionStatus Access portal version and "TSOK" for the TransactionStatus
  Shop portal version.
* Other gateway types exist: iframe, "classic" (remote redirect?), JavaScript, one-click purchasing.

## Hosted iframe Mode

JS to include on page: https://secure.pay1.de/client-api/js/v1/payone_hosted_min.js

This JS includes "classic" client-API mode functions supported by "AJAX mode" and "Redirect mode" (also worth following up)

config.cardtype defines available card types (from list in package)

See Platform_Client_API.pdf A few good examples are listed of the front-end markup and JS.

URLs

* Server API URL: https://api.pay1.de/post-gateway/
* URL classic: https://secure.pay1.de/frontend/
* iframe URL: https://frontend.pay1.de/frontend/v2/
* Client API URL (AJAX?): https://secure.pay1.de/client-api/

It looks like basket items are mandatory (using the Frontend mode), and each item must have a stock ID and a VAT record.

