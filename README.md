[![GitHub license](https://img.shields.io/badge/license-GPL-blue.svg)](https://raw.githubusercontent.com/academe/OmniPay-Payone/master/LICENSE.md)
[![Packagist](https://img.shields.io/packagist/v/academe/omnipay-payone.svg?maxAge=2592000)](https://packagist.org/packages/academe/omnipay-payone)
[![GitHub issues](https://img.shields.io/github/issues/academe/OmniPay-Payone.svg)](https://github.com/academe/OmniPay-Payone/issues)
[![Build Status](https://travis-ci.org/academe/OmniPay-Payone.svg?branch=master)](https://travis-ci.org/academe/OmniPay-Payone)

Table of Contents
=================

  * [Table of Contents](#table-of-contents)
  * [Omnipay: <a href="https://www.payone.de/">PAYONE</a>](#omnipay-payone)
    * [Installation](#installation)
    * [Basic Usage](#basic-usage)
      * [Gateway Background](#gateway-background)
      * [Shop and Access API Versions](#shop-and-access-api-versions)
      * [Extended Items (Order Lines)](#extended-items-order-lines)
    * [The Shop Server API Gateway](#the-shop-server-api-gateway)
      * [Server API Authorize Payment](#server-api-authorize-payment)
      * [Server API Purchase](#server-api-purchase)
      * [Server API Capture](#server-api-capture)
      * [Server API Void](#server-api-void)
      * [Server API Credit Card Check](#server-api-credit-card-check)
    * [The Shop Front End API Gateway](#the-shop-front-end-api-gateway)
      * [Front End Authorize](#front-end-authorize)
      * [Front End Purchase](#front-end-purchase)
    * [The Shop Client API Gateway](#the-shop-client-api-gateway)
      * [Client API Credit Card Check](#client-api-credit-card-check)
      * [Client API Authorize](#client-api-authorize)
        * [Client completeAuthorize](#client-completeauthorize)
      * [Client API Purchase](#client-api-purchase)
    * [Notification Callback](#notification-callback)
      * [completeAuthorize and completePurchase Methods](#completeauthorize-and-completepurchase-methods)
  * [References](#references)

# Omnipay: [PAYONE](https://www.payone.de/)

**PAYONE driver for the Omnipay PHP payment processing library**

Written to specication:

* *TECHNICAL REFERENCE PAYONE Platform Channel Client API* 1.28 (2016-05-09)
* *TECHNICAL REFERENCE PAYONE Platform Channel Server API* 2.84 (2016-05-09)
* *TECHNICAL REFERENCE PAYONE Platform Frontend* 2.40 (2016-05-09)

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic, multi-gateway payment
processing library for PHP 5.3+. This package implements PAYONE support for
[OmniPay](https://github.com/thephpleague/omnipay).

![Alt text](docs/PAYONE_Logo_480.png?raw=true "PAYONE")

## Installation

Omnipay is installed via [Composer](http://getcomposer.org/). To install, add it
to your `composer.json` file:

```json
{
    "require": {
        "academe/omnipay-payone": "~2.0"
    }
}
```

or direct from [packagist](https://packagist.org/packages/academe/omnipay-payone)

    composer require "academe/omnipay-payone: ~2.0"

While in development, it can be obtained from this repository:

```json
{
    "require": {
        "academe/omnipay-payone": "dev-master"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/academe/OmniPay-Payone.git"
        }
    ]
}
```

or

    composer require "academe/omnipay-payone: dev-master"

And run composer to update your dependencies:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar update

## Basic Usage

The following gateways are provided by this package:

* Payone_ShopServer
* Payone_ShopFrontend
* Payone_ShopClient

For general usage instructions, please see the main [Omnipay](https://github.com/thephpleague/omnipay)
repository. You will find more specific details can be found below.

### Gateway Background

The [PAYONE API](https://www.payone.de/en/platform-integration/interfaces/) has three main access points
of interest to e-commerce:

* **Server API** - for interacting directly with server without user intervention.
* **Frontend API** - for delivering hosted credit card (CC) forms to the user (iframe or redirect).
* **Client API** - for interacting with a JavaScript front end.

The Server API is mainly for capturing authorized payments and the Front end
is for setting up CC forms. The Client side is for supporting AJAX in the browser.
You can also do authorisations and make payments using the Server API, so long as
you are fully aware of the PCI implications.

The Server API also has a notification handler for receiving the payment results and captured user information
from the PAYONE servers.

You will most likely be using a mix of Server, Frontend and Client functions as they complement each other.

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

The PAYONE API supports two additional cart item properties that must be completed (`id` and `vat`). Since the core OmniPay v2
`Item` object cannot accept custom property values, this has been extended. The extended `Item` class can be found here:

    \Omnipay\Payone\Extend\Item

Creating an `Item` uses these fields:

```php
$lines[] = new \Omnipay\Payone\Extend\Item([
    'id' => '{merchant-site-stock-ID}',
    'name' => '{product-name}',
    'quantity' => 2,
    'price' => 123,
    'vat' => 20, // Optional
    // Used but optional for clearingType = \Omnipay\Payone\AbstractShopGateway::CLEARING_TYPE_WLT
    // and walletType = \Omnipay\Payone\AbstractShopGateway::WALLET_TYPE_PPE
    'itemType' => \Omnipay\Payone\Extend\ItemInterface::ITEM_TYPE_GOODS,
]);
```

The `price` can be supplied in *minor currency units* or *major currency units*.
The following `Item` prices are equivalent in dollars or Euros (currencies with
two decimal places):

* 123
* 1.23
* "123"
* "1.23"

The `vat` value is the VAT rate, expressed as a percentage (%)
or as a [basis point](https://en.wikipedia.org/wiki/Basis_point) (‱).
The rules are as follows:

* Any integer up to 99 will be interpreted as a percentage.
* Any integer 100 to 9999 will be interpreted as a basis point.

The items are then added to the `ItemBag` in the normal way as an array of objects:

```php
$items = new \Omnipay\Common\ItemBag($lines);
```

The total price of the `ItemBag` does not appear to need to add up to the order total
for the `Shop Server API` methods when clearing by credit card.
It MUST however sum to the order total for the `Shop Frontend`
methods, and it must sum correctly when using the CLEARING_TYPE_WLT clearing type.

If you do not use the extended `Item` then default values will be substituted (`"000000"` for the `id` and `null` for the
`vat` figure). If you do not supply any items at all for the `Shop Frontend` methods, then a
default item for the full price will be created automatically.
The `Shop Frontend` *must* have a basket of at least one item, which is why this driver
will create a default item if you do not supply a basket.

## The Shop Server API Gateway

Create a gateway object to access the Server API Shop version methods:

```php
$gateway = Omnipay\Omnipay::create('Payone_ShopServer');

// Merchant Account ID
$gateway->setMerchantId(12345);
// Merchant Portal ID
$gateway->setPortalId(1234567);
// Sub-Account ID
$gateway->setSubAccountId(56789);
// True to use test mode.
$gateway->setTestMode(true);
// Default language is "en" and determines the language of forms and error messages.
$gateway->setLanguage("de");
// Currency as ISO 4217 three-character code
$gateway->setCurrency('EUR');
```

### Server API Authorize Payment

PAYONE calls this "pre-authorization". It authorizes a payment to be captured later.

To create an authorization request:

```php
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
    // Optional ecommerce mode declares risk
    'ecommerceMode' => '3dsecure',
]);
```

The driver will attempt to work out the card type from the card number, but if it fails or
you are using a card type not yet supported by the driver or by OmniPay, then you can supply
your own card type letter.

The `$card` details are populated like any other standard OmniPay card, with one exception detailed
below. You can supply the details as an array or as a `\Omnipay\Common\CreditCard` object.

The `ecommerceMode` overrides the 3D Secure configuration set in the portal. Values include `internet`
to turn off 3D Secure, `3dsecure` to turn on 3D Secure and `moto` for telephone and email payments.
Note: when capturing an authorized payment, the *same ecommerceMode must be used* or the capture
will be rejected. However, PAYONE will wrap the bank's 3D Secure form on its
own site, because it provides no additional POST data to send.

These four fields normally define the details for a credit card:

```php
[
    ...
    'number' => '4111111111111111',
    'expiryYear' => '2020',
    'expiryMonth' => '12',
    'cvv' => '123',
];
```

PAYONE will also accept a "pseudo-card" number. This is a temporary token supplied by another
process (e.g. a "creditcardcheck") and used in place of a card. If supplying a pseudo-card number,
leave the remaining card fields blank or null. The gateway driver will then treat the card number
as a pseudo-card:

```php
[
    ...
    // A pseudo-card number.
    'number' => '4111111111111111',
    // Other card details left as null.
    'expiryYear' => null,
    'expiryMonth' => null,
    'cvv' => null,
];
```

It is strongly recommended to only work with pseudo card numbers through the `Server` API channel,
to reduce potential PCI DSS issues. A pseudo card number should be obtained through the `Client` API
channel using the "hosted iFrame" functionality.

Also to note about the card data is that countries must be supplied as ISO 3166 two-letter codes:

```php
    'billingCountry' => 'US',
```

and states must be supplied as ISO 3166-2 sub-division codes (various formats, depending on the country):

```php
    'billingCountry' => 'US',
    'billingState' => 'AL',
```

The return URL and cancel URL (when using 3D Secure) are normally set in the account settings,
but can be overridden here:

```php
    // Return URL on successful authorisation.
    'returnUrl' => '...',
    // Return URL on failure to authorise the payment.
    'errorUrl' => '...',
    // Return URL if the user choses to cancel the authorisation.
    'cancelUrl' => '...',
```

Send this request to PAYONE to get the response:

```php
$response = $request->send();
```

The standard OmniPay documentation shows how to handle the response. In addition, in the event
of an error, there will be the normal loggable error message, and a separate error message that is safe
to put in front of an end user:

```php
if (!$response->isSuccessful()) {
    echo $response->getMessage();
    // e.g. "Expiry date invalid, incorrect or in the past"
    echo $response->getCustomerMessage();
    // e.g. "Invalid card expiry date. Please verify your card data."
}
```

### Server API Purchase

PAYONE calls this "authorization". It authorizes and captures a payment immediately.

It is used and responds in the same way as `authorize`. The request message is created like this:

```php
$request = $gateway->purchase([...]);
```

### Server API Capture

Once a payment has been authorised, it may be captured. This is done using this minimal message:

```php
$request = $gateway->capture([
    // The reference for the original authorize transaction.
    'transactionReference' => '123456789',
    // Amount as decimal.
    'amount' => 1.23,
    // Pre-shared secret key used for authentication, if not already set on $gateway.
    'portalKey' => 'Ab12Cd34Ef56Gh78',
]);
```

That will capture the amount specified and settle the account. If you want to leave the
account open for capturing the total in multiple stages, then specify for the account to
be left unsettled:

```php
    'sequenceNumber' => $sequence,
    'settleAccount' => false,
```

The sequence number starts at 1 for the first capture, and must be incremented for each
subsequent capture. It should be taken from the [Notification Callback](#notification-callback),
see below.

For invoicing module some additional parameters have to be provided:

```php
$lines[] = new \Omnipay\Payone\Extend\Item([
    'id' => '{merchant-site-stock-ID}',
    'name' => '{product-name}',
    'itemType' => 'goods', // Available types: goods, shipping etc.
    'quantity' => 2,
    'price' => 123,
    'vat' => 20, // Optional
]);

$items = new ItemBag($lines);
```

And in capture request:

```php
    'items' => $items,
    'sequenceNumber' => 1,
    'settleAccount' => false,
    'invoiceid' => 1,
    'invoiceDeliveryMode' => 'P', // PDF, for others look into documentation
    'invoiceDeliveryDate' => date('Ymd'),
    'invoiceAppendix' => 'This is your invoice appendix'
```
Note that the email field in card details has to be passed to authorize method in pre-authorization step since it's
the email that will be used by Payone to send invoice to customer.

### Server API Void

To void an authorized payment:

```php
$request = $gateway->void([
    // The reference for the original authorize transaction.
    'transactionReference' => '123456789',
    // Amount as decimal.
    'amount' => 1.23,
    // Pre-shared secret key used for authentication, if not already set on $gateway.
    'portalKey' => 'Ab12Cd34Ef56Gh78',
]);
$response = $request->send();
```

The `void` method will response with a `ShopCaptureResponse` response when sent to PAYONE.

### Server API Credit Card Check

This method will check that the details of a credit card are *plausible* and optionally
tokenize the card details for use in other methods. The Credit Card Check method is
available both for Server direct requests, and for AJAX calls on the Client side.

The request is set up like this:

```php
$gateway = Omnipay\Omnipay::create('Payone_ShopServer');
$gateway->setSubAccountId(12345);
$gateway->setTestMode(true); // Or false for production.
$gateway->setMerchantId(67890);
$gateway->setPortalId(3456789);
$gateway->setPortalKey('Ab12Cd34Ef56Gh78');

$request = $gateway->creditCardCheck([
    'card' => [
        'number' => '4012001037141112',
        'expiryYear' => '2020',
        'expiryMonth' => '12',
        'cvv' => '123',
    ],
    'storeCard' => true,
]);

$response = $request->send();
```

If the credit card details are plausible, then the response will be successful:

```php
$response->isSuccessful();
// true
```

If the response is not successful, then details will be available in `getCode()`,
`getMessage()` and `getCustomerMessage()`.

If the response is successful and `storeCard` is `TRUE` then two additional items
of data will be available:

```php
// The tokenised card:
$token = $response->getToken();
// e.g. 4100000227987220

// The truncated card number:
$response->getCardNumber()
// e.g. 401200XXXXXX1112
```

In any API that requires credit card details, you can substitute the details with
the token, for example:

```php
$request = $gateway->authorize([
    'card' => [
        'number' => $token
    ],
    ...
```

Normally the token will come from the web client (AJAX in the browser) but this
Server API can be used during development and testing with test cards.

## The Shop Front End API Gateway

The Front End gateway supports hosted payment forms, taking either just credit card or
bank details, or full personal details too. The forms are hosted on the PAYONE site,
can be customised, and can be either presented to the end user in an iframe, or
the end user can be fully redirected to the remote form.

```php
// Set up the Front End gateway.
$gateway = Omnipay\Omnipay::create('Payone_ShopFrontend');
```

### Front End Authorize

The Front End API methods are encapsulated into a separate gateway class:

```php
$gateway = Omnipay\Omnipay::create('Payone_ShopFrontend');

// Merchant Portal ID
$gateway->setPortalId(1234567);
// Sub-Account ID
$gateway->setSubAccountId(56789);
// True to use test mode.
$gateway->setTestMode(true);
// Default language is "en" and determines the language of forms and error messages.
$gateway->setLanguage("de");
// Currency as ISO 4217 three-character code
$gateway->setCurrency('EUR');
// The pre-shared secret, used for hashing.
$gateway->setPortalKey('Ab12Cd34Ef56Gh78');
// The default for this gateway is HASH_MD5 for legacy applications, but the hash
// method recommended by PAYONE is HASH_SHA2_384,
$gateway->setHashMethod($gateway::HASH_SHA2_384);
```

Sending an authorization involves setting up the request message:

```php
$transactionId = {merchant-site-transaction-ID}

$request = $gateway->authorize([
    'transactionId' => $transactionId,
    'amount' => 3.99,
    'accessMethod' => 'iframe',
    'redirectMethod' => 'POST',
    'items' => $items,
    'card' => [
        'firstName' => 'Firstname',
        'billingAddress1' => 'Street Name',
        ...
    ],
    // Any of these optional URLs can override those set in the account settings:
    'returnUrl' => '...',
    'errorUrl' => '...',
    'cancelUrl' => '...',
]);
```

The `accessMethod` will be `"classic"` or `"iframe"`; default is `ShopFrontendAuthorizeRequest::ACCESS_METHOD_CLASSIC`
The `redirectMethod` will be `"GET"` or `"POST"`; default is `ShopFrontendAuthorizeRequest::REDIRECT_METHOD_GET`

The `items` are optional, but if you
do not supply at least one item, then a default item will be created for you; the cart is
mandatory for the Frontend API, unlike the Server API.

The `card` billing details can be used to pre-populate the payment form.
If the personal details have been checked and known to be valid (another API is able to
do that) then the name and address fields can be hidden on the payment form using
`'showName' => false` and `'showAddress' => false`.

Note that it may not be possible to override the URLs as shown above. It may be
possible to set these URLs *only* if not defined in the account settings. The
documentation is not entirely clear on this.

The response message (from OmniPay) for performing the next action is:

```php
$response = $request->send();
```

The response will be a redirect response, either GET or POST, according to the `redirectMethod`
parameter.

You can retrieve the GET URL and redirect in your application, or leave OmniPay to do the redirect:

```php
// Get the URL.
$url = $response->getRedirectUrl();

// Just do the redirect using the methods in OmniPay core.
$response->redirect();
```

For the `POST` redirectMethod, again, you can just let OmniPay do the redirect,
but you will probably want to build your own form and `target` it at an iframe
in the page. The two things you need to build the form is the target URL, and the form items.
The form items are supplied as name/value pairs.

```php
// This form needs JavaScript to auto-submit on page load.
echo '<form action="' . $response->getRedirectUrl() . '" method="POST" target="target-iframe">';
foreach($response->getRedirectData() as $name => $value) {
    echo '<input type="hidden" name="'.$name.'" value="'.htmlspecialchars($value).'" />';
}
echo '</form>';

// The autp-submitted form, tagetting at this iframe, will generate the
// remote credit card payment form within this iframe.
echo '<iframe name="target-iframe" width="400" height="650"></iframe>';
```

On return from the remote gateway, if using the iframe, you will need to break out
of the iframe to get to the final page in your merchant site. The PAYONE API does have
iframe-busting functionality built in. Set the `setTargetWindow()` on your authorize request
to tell the gateway where to take the user.
Accepted values are given in `ShopFrontendAuthorizeRequest::TARGET_WINDOW_*`.

Note that this driver does not attempt to generate HTML forms. It will instead give you the
data for creating your own HTML forms.

After the user has completed their details on the PAYONE site, a notification of the result
will be sent back to your merchant site, and then the user will be returned to either the
"success" page or the "failure" page. No data will be carried with that redirect, so the
transaction details must be retained in the session to match up with the results in
the notification back-channel.

### Front End Purchase

Works the same as Front End Authorize, but will require a separate `Server` API Capture.

## The Shop Client API Gateway

The Shop Client gateway handles payments using client AJAX calls or forms on the merchant
site that are POSTed direct to the PAYONE gateway.

```php
// Set up the Client gateway.
$gateway = Omnipay\Omnipay::create('Payone_ShopClient');
```

### Client API Credit Card Check

This is similar to the Server API Credit Card Check, and is set up in a similar way.
No credit card details are passed to it however, as that is handled on the client.

```php
$gateway = Omnipay\Omnipay::create('Payone_ShopClient');
$gateway->setSubAccountId(12345);
$gateway->setTestMode(true); // Or false for production.
$gateway->setMerchantId(67890);
$gateway->setPortalId(3456789);
$gateway->setPortalKey('Ab12Cd34Ef56Gh78');
$gateway->setHashMethod($gateway::HASH_SHA2_384);

$request = $gateway->creditCardCheck();
$response = $request->send();
```

This provides the following data to feed into your client:

```php
// The endpoint used to check the card details - GET or POST can be used.
$endpoint = $response->getRedirectUrl();

// The additional data that must be included with the card data.
// This will be an array that can be JSON encoded for the client JavaScript to use:
$data = $response->getRedirectData();
```

Then on the client you need to provide the credit card fields in a non-submitting
form (form items with no `name` attributes):

* cardpan - credit card number
* cardexpiredate - YYMM
* cardtype - e.g. V for Visa
* cardcvc2
* cardissuenumber - for UK Maestro only

These values will be constructed by your client code, then submitted to the end point.
The `cardexpiredate` for example, could be two drop-down lists concatenated.
The `cardtype` could be a drop-down list, or a client library could set it automatically
by matching card number patterns.

The result will be a JSON response something like this:

```json
{
    "status" : "VALID",
    "pseudocardpan" : "4100000228091881",
    "truncatedcardpan" : "401200XXXXXX1112",
    "cardtype" : "V",
    "cardexpiredate" : "2012"
}
```

Handling that data is out of scope for OmniPay, but the most important value here is
the `pseudocardpan` which can be used in any server API call in place of the real credit card
number (e.g. the Shop Server Authorize method).

The official PAYONE documentation explains further how this works, and provides
sample client code fragments.

It is recommended to use the "hosted iFrame" mode of capturing credit card data. It is out
of the scope of OmniPay and described in more detail [here](https://github.com/fjbender/simple-php-integration#credit-card-payments).

### Client API Authorize

There are two main modes the client authorize operates in:

* **REDIRECT** - The user POSTs directly to the PAYONE gateway, enters 3D Secure details
  there if necessary, then is sent back to your site.
* **JSON** - The user stays on your site initially while the authorisation is requested via
  AJAX. The client may then send the user to PAYONE to enter their 3D Secure password if
  required, but if not, then results can be posted directly back to the merchant site
  without the user leaving.

The REDIRECT mode supports the building of a complete payment form on the merchant site that
POSTs `direct` to the PAYONE gateway. The result of the authorisation will be POSTed by
PAYONE to the Notification handler. The gateway will also return the success status
to the merchant site with the user when they are directed back **so long as 3D Secure is
not being used**. It is important to note that if 3D Secure is used and the end user is
redirected to enter their 3D Secure password, then they will be returned to your site's
success/failure/cancel URL with *no* data, so the merchant site must save enough details
in the session to pick up the authorisation results sent via the `Notification` back-channel handler.

The AJAX mode is set up the same way, but all the details are POSTed via AJAX rather then
as a standard browser form. The result comes back as a JSON response, which may include a
3D Secure redirect, or may just contain the authorisation result.

Setting up the message is much the same as other methods. It is the same for
both the REDIRECT and the JSON response types:

```php
$gateway = Omnipay\Omnipay::create('Payone_ShopClient');
$gateway->setSubAccountId(12345);
$gateway->setTestMode(true); // Or false for production.
$gateway->setMerchantId(67890);
$gateway->setPortalId(3456789);
$gateway->setPortalKey('Ab12Cd34Ef56Gh78');
// The default for this gateway is HASH_MD5 for legacy applications, but the hash
// method recommended gby PAYONE is HASH_SHA2_384,
$gateway->setHashMethod($gateway::HASH_SHA2_384);
// Set up the response type - redirect the user or do an AJAX call.
$gateway->setResponseType($gateway::RETURN_TYPE_REDIRECT);
//$gateway->setResponseType($gateway::RETURN_TYPE_JSON);

$request = $gateway->authorize([
    'transactionId' => $transactionId, // Merchant site ID (or a nonce for it)
    'amount' => 9.99, // Major units
    'currency' => 'EUR',
    '3dSecure' => false, // or true
    'items' => $items, // Array or ItemBag of Items or Exten\Items
    // Where to send the user in authorisation suucess or falure.
    'returnUrl' => $returnUrl,
    'errorUrl' => $errorUrl,
    // Where to send the user on cancellation in 3D Secure form.
    'cancelUrl' => $cancelUrl,
]);
$response = $request->send();
```

The `$response` now contains the details needed for either hidden fields
in the client-side direct POST form, or for the AJAX call. These details are:

```php
// Array of name/value pairs
$params = $response->getRedirectData();

// The destination endpoint.
$endpoint = $response->getRedirectUrl();
```

In addition to `$params` you need to include the following data provided by
the end user:

* lastname - mandatory
* country - ISO3166 e.g. GB
* cardpan
* cardexpiredate YYMM
* cardtype - e.g. V
* cardcvc2
* cardissuenumber - UK Maestro only
* cardholder - optional

If your redirectMethod was REDIRECT, then all this information will be put into a form that
the user submits. The form will POST directly to PAYONE. What happens next will depend on
whether 3D Secure has been turned on and is availa to the card used.

* If 3D Secure is available, PAYONE will present the end user with a 3D Secure password form.
  The user will then be returned to the site with **no results**. The results will be send to
  the `Notification` handler where the merchant site must fetch them using the merchant site
  `transactionId`.
* If 3D Secure is NOT available, then the card will be validated and the user returned to the
  merchant site **with the results** as GET parameters. The result cannot be trusted as it is
  not signed, but can be useful in the flow. The result can be captured using the
  `completeAuthorize` message (see below).

If your `responseType` was JSON (`ShopClientGateway::RETURN_TYPE_REDIRECT`), then the merchant site client page is expected to POST the data
using AJAX. The return will be a JSON message detailing the result, which can be a success, failure,
or a redirect for 3D Secure. Handling that response is out of scope for OmniPay, but the PAYONE
documentation provides some examples and some handy scripts.

#### Client completeAuthorize

This can be used to parse the resturn data from the server request (i.e. the data the user brings
back with them):

```php
$gateway = Omnipay\Omnipay::create('Payone_ShopClient');

$server_request = $gateway->completeAuthorize();
$server_response = $server_request->send();
```

The `$server_response` can give you a number of standardised items:

```php
// The raw data
$server_response->getData();

// The authorisation success state:
$server_response->isSuccessful();

// The redirect status and URL (we would not expect to see this for a REDIRECT response
// type as the redirect has already been processed on the client side:
$server_response->isRedirect();
$server_response->getRedirectUrl()

// If there are errors, then there will be a system message and a user-safe message:
$server_response->getMessage();
$server_response->getCustomerMessage();
```

### Client API Purchase

This works the same way as *Client API Authorize* but uses the `purchase` method instead.

You would still use `completeAuthorize` with the purchase API.



## Notification Callback

For most - if not all - transactions, PAYONE will send details of that transaction to your
notification URL. This URL is specified in the PAYONE account configuration. For most of the
Server API methods it is a convenience. For the Frontend methods it is essential, being the
only way of getting a notification that a transaction has completed.

The notification comes from IP address 185.60.20.0/24 (185.60.20.1 to 185.60.20.254).
This driver does not make any attempt to validate that.

Your application must respond to the notification within ten seconds, because when a Frontend
hosted form is used, the user will be waiting on the PAYONE site for the acknowledgement - just
save the data to storage and end.

The notification Server Request (i.e. *incoming* request to your server) is captured/handled by the
`completeStatus` class created using the standard OmniPay `acceptNotification()` gateway method.

```php
$gateway = Omnipay\Omnipay::create('Payone_ShopServer');

// The portal key must be provided.
// This will be used to verify the hash sent with the transaction status notification.
// PAYONE will send an MD5 hash at all times. This is subject to change and will support
// the option to use a SHA2-384 hash eventually.
$gateway->setPortalKey('Ab12Cd34Ef56Gh78');

$server_request = $gateway->acceptNotification();

// The raw data sent is available:
$data = $server_request->getData();

// Provides the result of the hash verification.
// If the hash is not verified then the data cannot be trusted.
$server_request->isValid();
```

So long as the notification is valid, you can also get the status of the transaction.
The following table lists the way this driver maps the status and and the event (the `txaction`)
to Omnipay's overall status values ("-" neans "anything"):

| transaction_status | txaction | Overall transaction status | Notes |
| ------------------ | -------- | -------------------------- | ----- |
| completed | - | STATUS_COMPLETED | |
| - | appointed | STATUS_COMPLETED | |
| - | paid | STATUS_COMPLETED | |
| - | invoice | STATUS_COMPLETED | |
| - | capture | STATUS_PENDING | |
| - | underpaid | STATUS_PENDING | |
| - | refund | STATUS_PENDING | |
| - | debit | STATUS_PENDING | |
| - | reminder | STATUS_PENDING | |
| - | vauthorization | STATUS_PENDING | |
| - | vsettlement | STATUS_PENDING | |
| - | transfer | STATUS_PENDING | |
| - | cancelation | STATUS_FAILED | |
| - | failed | STATUS_FAILED | |
| - | - | STATUS_FAILED | |

Individual data items can also be extracted from the server request (see list below).

Once the data is saved to the local application, respond to the remote gateway to
indicate that you have received the notification:

```php
$server_response = $server_request->send();
// Your application will exit on the next command by default.
// You can prevent that by passiong in `false` as a single parameter, but
// do make sure no further stdout is issued.
$server_response->acknowledge(); // or $server_response->send()
```

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
* getClearingType() - 'cc' for credit card; 'wlt' and walletType = 'PPE' for PayPal Express
* getWalletType() - 'PPE' for PayPal Express
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
* getShippingFirstName()
* getShippingLastName()
* getShippingName()
* getShippingStreet()
* getShippingAddress1() - alias of getShippingStreet()
* getShippingCity()
* getShippingPostcode()
* getShippingCountry() - ISO code
* getEmail()
* getPrice() - decimal in major currency units
* getPriceInteger() - integer in minor currency units
* getBalance() - decimal in major currency units
* getBalanceInteger() - integer in minor currency units
* getReceivable() - decimal in major currency units
* getReceivableInteger() - integer in minor currency units

### completeAuthorize and completePurchase Methods

Although the Frontend purchase and authorize take the user offsite (either in full screen
mode or in an iframe), no data is returned with the user coming back to the site.
as a consequence, the `completeAuthorize` and `completePurchase` methods are not needed.

3D Secure involves a visit to the authorising bank. However, PAYONE will wrap that visit
up into a page that it hosts (the page will contain an iframe). This means the result,
if a 3D Secure password is needed, will still be sent to the merchant site through the
same notification URL as any non-3D Secure transaction. One advantage is that your merchant
site does not need to mess around with `PAReq`/`PARes` parameters and suchlike from the end banks.

# References

* https://github.com/fjbender/simple-php-integration  
  A write-up showing how the PAYONE integration works.
  Some great background information.
* https://github.com/ekalinin/github-markdown-toc  
  Tool to generate the Table of Contents in this README.

