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
        "academe/omnipay-payone": "~2.0"
    }
}
```

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

And run composer to update your dependencies:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar update

## Basic Usage

The following gateways are provided by this package:

* Payone_ShopServer
* Payone_ShopFrontend

For general usage instructions, please see the main [Omnipay](https://github.com/thephpleague/omnipay)
repository. You will find more specific details can be found below.

### Gateway Background

The [PAYONE API](https://www.payone.de/en/platform-integration/interfaces/) has three main access points
of interest to e-commerce:

* **Server API** - for interacting directly with server without user intervention.
* **Frontend API** - for delivering hosted credit card (CC) forms to the user.
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
    'vat' => 20, // Optional
]);
~~~

The `price` can be supplied in *minor currency units* or *major currency units*.
The following `Item` prices are equivalent in dollars or Euros (currencies with
two decimal places):

* 123
* 1.23
* "123"
* "1.23"

The items are then added to the `ItemBag` in the normal way as an array of objects:

~~~php
$items = new \Omnipay\Common\ItemBag($lines);
~~~

The total price of the `ItemBag` does not appear to need to add up to the order total
for the `Shop Server API` methods. It MUST however sum to the order total for the `Shop Frontend`
methods.

If you do not use the extended `Item` then default values will be substituted (`"000000"` for the `id` and `null` for the
`vat` figure). If you do not supply any items at all for the `Shop Frontend` methods, then a
default item for the full price will be created automatically.

## The Shop Server API Gateway

Create a gateway object to access the Server API Shop version methods:

~~~php
$gateway = Omnipay\Omnipay::create('Payone_ShopServer');

// Merchant Account ID
$gateway->setMerchantId(12345);
// Merchant Portal ID
$gateway->setPortalId(1234567);
// Sub-Acount ID
$gateway->setSubAccountId(56789);
// True to use test mode.
$gateway->setTestMode(true);
// Default language is "en" and determines the language of forms and error messages.
$gateway->setLanguage("de");
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
    // Optional ecommerce mode declares risk
    'ecommerceMode' => '3dsecure',
]);
~~~

The driver will attempt to work out the card type from the card number, but if it fails or
you are using a card type not yet supported by the driver or by OmniPay, then you can supply
your own card type letter.

The `$card` details are populated like any other standard OmniPay card, with one exception detailed
below. You can supply the details as an array or as a `\Omnipay\Common\CreditCard` object.

The `ecommerceMode` overrides the 3D Secure configuration in the portal. Values include `internet`
to turn off 3D Secure, `3dsecure` to turn on 3D Secure and `moto` for telephone and email payments.
Note: when capturing an authorized payment, the *same ecommerceMode must be used* or the capture
will be rejected. However, it looks like PAYONE may actually wrap the bank's 3D Secure form on its
own site, because it provides no additional POST data to send.

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

The return URL and cancel URL (when using 3D Secure) are normally set in the account settings,
but can be overridden here:

~~~php
    // Return URL on successful authorisation.
    'returnUrl' => '...',
    // Return URL on failure to authorise the payment.
    'errorUrl' => '...',
    // Return URL if the user choses to cancel the authorisation.
    'cancelUrl' => '...',
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

### Server API Credit Card Check

This method will validate the details of a credit card are *plausible* and optionally
tokenize the card details for use in other methods. The Credit Card Check method is
available both for Server direct requests, and for AJAX calls on the Client side.

The request is set up like this:

~~~php
$gateway = Omnipay\Omnipay::create('Payone_ShopServer');
$gateway->setSubAccountId(12345);
$gateway->setTestMode(true); // Or false for production.
$gateway->setMerchantId(67890);
$gateway->setPortalId(3456789);
$gateway->setPortalKey('secret-key');

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
~~~

If the credit card details are plausible, then the response wilb be successful:

~~~php
$response->isSuccessful();
// true
~~~

If the response is not successful, then details will be available in `getCode()`,
`getMessage()` and `getCustomerMessage()`.

If the response is successful and `storeCard` is `TRUE` then two additional items
of data will be available:

~~~php
// The tokenised card:
$token = $response->getToken();
// e.g. 4100000227987220

// The truncated card number:
$response->getCardNumber()
// e.g. 401200XXXXXX1112
~~~

In any API that requires credit card details, you can substitute the details with
the token, for example:

~~~php
$request = $gateway->authorize([
    'card' => [
        'number' => $token
    ],
    ...
~~~

Normally the token will come from the web client (AJAX in the browser) but this
Server API can be used during development and testing with test cards.

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
$gateway = Omnipay\Omnipay::create('Payone_ShopServer');

// The portal key must be provided.
// This will be used to verify the hash sent with the transaction status notification.
$gateway->setPortalKey('Ab12Cd34Ef56Gh78');

$server_request = $gateway->acceptNotification();

// The raw data sent is available:
$data = $server_request->getData();

// Provides the result of the hash verification.
// If the hash is not verified then the data cannot be trusted.
$server_request->isValid();
~~~

Individual data items can also be extracted from the server request (see list below).

Once the data is saved to the local application, respond to the remote gateway to
indicate that you have received the notification:

~~~php
$server_response = $server_request->send();
// Your application will exit on the next command by default.
// You can prevent that by passiong in `false` as a single parameter, but
// do make sure no further stdout is issued.
$server_response->acknowledge(); // or send()
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

3D Secure incolves a vlsit to the authorisaing bank. However, PAYONE will wrap that visit
up into a page that it controls (the page will contain an iframe). This means the result
if a 3D Secure password is needed, will still be sent to the merchant site through the
same notification URL as any non-3D Secure transaction.

## The Shop Front End API Gateway

The Front End gateway supports hosted payment forms, taking either just credit card or
bank details, or full personal details too. The forms are hosted on the PAYONE site,
can be customised, and can be either presented to the end user in an iframe, or
the end user can be fully redirected to the form.

~~~php
// Set up the Front End gateway.
$gateway = Omnipay\Omnipay::create('Payone_ShopFrontend');
~~~

### Front End Authorize

The Front End API methods are encapsulated into a separate gateway class:

~~~php
$gateway = Omnipay\Omnipay::create('Payone_ShopFrontend');

// Merchant Portal ID
$gateway->setPortalId(1234567);
// Sub-Acount ID
$gateway->setSubAccountId(56789);
// True to use test mode.
$gateway->setTestMode(true);
// Default language is "en" and determines the language of forms and error messages.
$gateway->setLanguage("de");
// Currency as ISO 4217 three-character code
$gateway->setCurrency('EUR');
// The pre-shared secret, used for hashing.
$gateway->setPortalKey('Ab12Cd34Ef56Gh78');
~~~

Sending an authorization involves setting up the request message:

~~~php
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
~~~

The `accessMethod` will be `"classic"` or `"iframe"`, defaulting to "classic".
The `redirectMethod` will be `"GET"` or `"POST"`, defaulting to "POST".

The `items` are optional, but if you
do not supply at least one item, then a dummy item will be created for you; the cart is
mandatory for the Frontend API, unlike the Server API.

The `card` billing details can be used to pre-populate the payment form.
If the personal details have been checked and known to be valid (another API is able to
do that) then the name and address fields can be hidden on the payment form using
`'showName' => false` and `'showAddress' => false`.

Note that it may not be possible to override the URLs as shown above. It may be
possible to set these URLs *only* if not defined in the account settings. The
documentation is not entirely clear on this.

The response message (from OmniPay) for performing the next action is:

~~~php
$response = $request->send();
~~~

The response will be a redirect response, either GET or POST, according to the `redirectMethod`
parameter.

You can retrieve the GET URL and redirect in your application, or leave OmniPay to do the redirect:

~~~php
// Get the URL.
$url = $response->getRedirectUrl();

// Just do the redirect.
$response->redirect();
~~~

For the `POST` redirectMethod, again, you can just let OmniPay do the redirect,
but you will probably want to build your own form and `target` it at an iframe
in the page. The two things you need to build the form is the target URL, and the form items.
The form items are supplied as name/value pairs.

~~~php
// This form needs to be set to auto-submit.
echo '<form action="' . $response->getRedirectUrl() . '" method="POST" target="target-iframe">';
foreach($response->getRedirectData() as $name => $value) {
    echo '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
}
echo '</form>';

echo '<iframe name="target-iframe" width="400" height="650"></iframe>';
~~~

After the user has completed their details on the PAYONE site, a notification of the result
will be sent back to your merchant site, and then the user will be returned to either the 
"success" page or the "failure" page. No data will be carried with that redirect, so the
transaction details must be retained in the session.

### Front End Purchase

Works the same as Front End Authorize, but will require a separate Server API Capture.


## The Shop Client API Gateway

The Shop Client gateway handles payments using client AJAX calls or forms on the merchant
site that are POSTed direct to the PAYONE gateway.

~~~php
// Set up the Client gateway.
$gateway = Omnipay\Omnipay::create('Payone_ShopClient');
~~~


# References

* https://github.com/fjbender/simple-php-integration  
  A write-up showing how the ONEPAY intergratino works.
  Some great background information.












======

## Development Notes

Some development notes yet to be incorporated into the code or documentation:

* Other transaction types to support on the "Shop" API: refund, vauthorization, creditcardcheck (AJAX API?), 3dscheck, addresscheck
* Other gateway types exist: JavaScript, one-click purchasing.

## Hosted iframe Mode

* JS to include on page: https://secure.pay1.de/client-api/js/v1/payone_hosted_min.js
* This JS includes "classic" client-API mode functions supported by "AJAX mode" and "Redirect mode" (also worth following up)
* config.cardtype defines available card types (from list in package)
* See Platform_Client_API.pdf A few good examples are listed of the front-end markup and JS.
