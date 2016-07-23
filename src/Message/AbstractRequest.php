<?php

namespace Omnipay\Payone\Message;

/**
 * PAYONE Abstract Request.
 */

use Omnipay\Common\Message\AbstractRequest as OmnipayAbstractRequest;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Payone\ShopGateway;
use Omnipay\Common\CreditCard;
use Omnipay\Omnipay;
use Guzzle\Http\Url;

abstract class AbstractRequest extends OmnipayAbstractRequest
{
    /**
     * The "clearingtype" parameter.
     * Only cc (credit card) is supported at this time.
     */
    protected $clearingtype = 'cc';

    /**
     * The "request" parameter.
     */
    protected $request_code = 'undefined';

    /**
     * Maps card brand names to "cardtype" letters.
     * null cards are unsupported.
     * non-constant cards are an extension.
     * Note: Switch is now Maestro UK
     */
    protected static $cardtypes = array(
        CreditCard::BRAND_VISA => 'V',
        CreditCard::BRAND_MASTERCARD => 'M',
        CreditCard::BRAND_DISCOVER => 'C',
        CreditCard::BRAND_AMEX => 'A',
        CreditCard::BRAND_DINERS_CLUB => 'D',
        CreditCard::BRAND_JCB => 'J',
        CreditCard::BRAND_SWITCH => 'U',
        CreditCard::BRAND_SOLO => null,
        CreditCard::BRAND_DANKORT => null,
        CreditCard::BRAND_MAESTRO => 'O', // International
        //CreditCard::BRAND_FORBRUGSFORENINGEN => null, // No details available
        CreditCard::BRAND_LASER => null,
        'discover' => 'C',
        'cartebleue' => 'B',
    );

    /**
     * A list of countries for which state codes may be given.
     */
    protected $countries_with_states = array(
        'US', 'CA', 'CN', 'JP', 'MX', 'BR', 'AR', 'ID', 'TH', 'IN'
    );

    /**
     * Hash a string using the chosen method.
     */
    protected function hashString($string, $key = '')
    {
        // The key is concatenated to the string for md5.
        if ($this->getHashMethod() == ShopGateway::HASH_MD5) {
            return strtolower(md5($string . $key));
        }

        // The key is a separate parameter for SHA2 384
        if ($this->getHashMethod() == ShopGateway::HASH_SHA2_384) {
            return strtolower(hash_hmac('sha384', $string, $key));
        }

        throw new InvalidRequestException('Unknown hashing method supplied.');
    }

    /**
     * Base data required for all transactions.
     */
    protected function getBaseData()
    {
        $data = array();

        $data['request'] = $this->request_code;

        $data['mid'] = $this->getMerchantId();
        $data['portalid'] = $this->getPortalId();

        // Can alternatively use MD5 or SHA2-384, Status-Hash as MD5.
        // TODO: support SHA2-384 through as an olption.
        // Must be lower case.
        $data['key'] = $this->hashString($this->getPortalKey());

        $data['api_version'] = ShopGateway::API_VERSION;

        $data['mode'] = (bool)$this->getTestMode() ? ShopGateway::MODE_TEST : ShopGateway::MODE_LIVE;

        $data['encoding'] = $this->getEncoding();
        $data['language'] = $this->getLanguage();

        return $data;
    }

    /**
     * Collect the personal data to send to the Gateway.
     */
    public function getDataPersonal()
    {
        $data = array();

        if ($this->getCustomerId()) {
            $data['customerid'] = $this->getCustomerId();
        }

        if ($this->getDebtorId()) {
            $data['userid'] = $this->getDebtorId();
        }

        if ($card = $this->getCard()) {
            // PAYONE has both "title" and "salutation".
            // I'm not sure if this distinction (between gender/marital status salutation
            // and professional title is common in Germany, but OmnIPay does not cater for it.

            if ($card->getBillingTitle()) {
                $data['salutation'] = $card->getTitle();
            }

            if ($card->getBillingFirstName()) {
                $data['firstname'] = $card->getFirstName();
            }

            if ($card->getBillingLastName()) {
                $data['lastname'] = $card->getLastName();
            }

            if ($card->getBillingCompany()) {
                $data['company'] = $card->getCompany();
            }

            if ($card->getBillingAddress1()) {
                $data['street'] = $card->getBillingAddress1();
            }

            if ($card->getBillingAddress2()) {
                $data['addressaddition'] = $card->getBillingAddress2();
            }

            if ($card->getBillingPostcode()) {
                $data['zip'] = $card->getBillingPostcode();
            }

            if ($card->getBillingCity()) {
                $data['city'] = $card->getBillingCity();
            }

            // NOTE: this must be supplied as a ISO 3166 code, and not a country name.

            if ($card->getBillingCountry()) {
                // Some very dirty validation.
                if (!preg_match('/^[A-Z]{2}$/', $card->getBillingCountry())) {
                    throw new InvalidRequestException('Billing country must be an ISO-3166 two-digit code.');
                }

                $data['country'] = $card->getBillingCountry();
            }

            // NOTE: this must be supplied as a ISO 3166-2 subdivisions, and not a state name.
            // Only set for countries: US, CA, CN, JP, MX, BR, AR, ID, TH, IN

            if ($card->getBillingState() && in_array($card->getBillingCountry(), $this->countries_with_states)) {
                // Some very dirty validation.
                // 1, 2 or 3 upper-case letters, or two digits.
                if (!preg_match('/^([A-Z]{1,3}|[0-9]{2})$/', $card->getBillingState())) {
                    throw new InvalidRequestException('Billing state must be an ISO-3166-2 subdivision code.');
                }

                $data['state'] = $card->getBillingState();
            }

            // OmniPay does not distinguish between a billing and a shipping email.
            // We may need to add the shipping email as a separate parameter.

            if ($card->getEmail()) {
                $data['email'] = $card->getEmail();
            }

            if ($card->getBillingPhone()) {
                $data['telephonenumber'] = $card->getBillingPhone();
            }

            if ($card->getBirthday()) {
                // Format: YYYYMMDD
                $data['birthday'] = $card->getBirthday('Ymd');
            }

            $gender = $card->getGender();
            if ($gender == 'm' || $gender == 'f') {
                // "m" or "f"
                $data['gender'] = $card->getGender();
            }
        }

        // ONEPAY supports IPv4 only, so we will filter out IPv6 formats.

        if ($this->getClientIp() && preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $this->getClientIp())) {
            $data['ip'] = $this->getClientIp();
        }

        return $data;
    }

    /**
     * Collect the shipping data to send to the Gateway.
     */
    public function getDataShipping()
    {
        $data = array();

        if ($card = $this->getCard()) {
            if ($card->getShippingFirstName()) {
                $data['shipping_firstname'] = $card->getShippingFirstName();
            }

            if ($card->getShippingLastName()) {
                $data['shipping_lastname'] = $card->getShippingLastName();
            }

            if ($card->getShippingCompany()) {
                $data['shipping_company'] = $card->getShippingCompany();
            }

            // PAYONE only captures one shipping line.

            if ($card->getShippingAddress1()) {
                $data['shipping_street'] = $card->getShippingAddress1();
            }

            if ($card->getShippingPostcode()) {
                $data['shipping_zip'] = $card->getShippingPostcode();
            }

            if ($card->getShippingCity()) {
                $data['shipping_city'] = $card->getShippingCity();
            }

            // NOTE: this must be supplied as a ISO 3166 code, and not a country name.

            if ($card->getShippingCountry()) {
                // Some very dirty validation.
                if (!preg_match('/^[A-Z]{2}$/', $card->getShippingCountry())) {
                    throw new InvalidRequestException('Shipping country must be an ISO-3166 two-digit code.');
                }

                $data['shipping_country'] = $card->getShippingCountry();
            }

            // NOTE: this must be supplied as a ISO 3166-2 subdivisions, and not a state name.
            // Only set for countries: US, CA, CN, JP, MX, BR, AR, ID, TH, IN

            if ($card->getShippingState() && in_array($card->getShippingCountry(), $this->countries_with_states)) {
                // Some very dirty validation.
                if (!preg_match('/^([A-Z]{1,3}|[0-9]{2})$/', $card->getShippingState())) {
                    throw new InvalidRequestException('Shipping state must be an ISO-3166-2 subdivision code.');
                }

                $data['shipping_state'] = $card->getShippingState();
            }
        }

        return $data;
    }

    /**
     * Collect the credit card data to send to the Gateway.
     */
    public function getDataCard()
    {
        $data = array();

        // CHECKME: does the "ecommercemode" need to be set? Values "internet" and "3dsecure".
        // The "pseudocardpan" can replace all these other CC details (a saved token, I guess)

        $data['clearingtype'] = $this->getClearingType();

        if ($card = $this->getCard()) {
            // If only the card number is set, and not the expiry year, month or CVV, then
            // treat this card number as a Pseudo card PAN.
            // A Pseudo card PAN is a card+expiry+CVV that has been tokenised.

            // Each will be a different value if not set: 0, null and "" (sigh). But all will be empty.
            // The transaction gets full card details OR a pseudocardpan.
            // Please note that the month will be (int)0 if not set while the year will be null. See:
            // https://github.com/thephpleague/omnipay-common/issues/29
            // It is also assumed that an empty ('000') CVV is valid for a card, so we compare to null.

            if (empty($card->getExpiryYear()) && empty($card->getExpiryMonth()) && $card->getCvv() === null) {
                $data['pseudocardpan'] = $card->getNumber();
            } else {
                $data['cardpan'] = $card->getNumber();

                $data['cardtype'] = $this->getCardType();

                // Format: YYMM
                $data['cardexpiredate'] = $card->getExpiryDate('ym');

                // The card holder name is defined by OmniPay as the billing first name and
                // last name concatenated.

                $data['cardholder'] = $card->getName();

                if (!empty($card->getCvv())) {
                    $data['cardcvc2'] = $card->getCvv();
                }

                // Issue number may be '00'.
                // Used for UK Maestro/Switch.

                $issue_number = $card->getIssueNumber();
                if (isset($issue_number)) {
                    $data['cardissuenumber'] = $issue_number;
                }
            }
        }

        return $data;
    }

    /**
     * Collect the items/cart/basket data to send to the Gateway.
     */
    public function getDataItems()
    {
        $data = [];

        // Each item must be contingously numbered, starting from 1.
        $item_count = 0;

        foreach($this->getItems() as $item) {
            $item_count++;

            if (method_exists($item, 'getId')) {
                $id = $item->getId();
            } else {
                $id = $this->defaultItemId;
            }

            if (method_exists($item, 'getVat')) {
                $vat = $item->getVat();
            } else {
                $vat = 0;
            }

            // We are ASSUMING here that the price is in minor units.
            // Since there is no validation or parsing of the Item
            // price, we really cannot know for sure whether it contains
            // €100 or 100c

            $data['id['.$item_count.']'] = $id;
            $data['pr['.$item_count.']'] = $item->getPrice();
            $data['no['.$item_count.']'] = $item->getQuantity();
            $data['de['.$item_count.']'] = $item->getName();
            $data['va['.$item_count.']'] = $vat;
        }

        return $data;
    }

    /**
     * The response to sending the request is a text list of name=value pairs.
     * The output data is a mix of the sent data with the received data appended.
     */
    public function sendData($data)
    {
        $httpRequest = $this->httpClient->post($this->getEndpoint(), null, $data);
        // CURL_SSLVERSION_TLSv1_2 for libcurl < 7.35
        $httpRequest->getCurlOptions()->set(CURLOPT_SSLVERSION, 6);
        $httpResponse = $httpRequest->send();

        // The body returned will be text of multiple lines, each containing {name}={value}
        // CHECKME: what is the encoding we get back? I suspect it may always be ISO 8859-1

        $body = (string)$httpResponse->getBody();

        // Experiments show the lines are separated by \n only.
        // The documentation does not specify this to be the case, so we will split
        // on \r too just to be safe.

        $lines = preg_split('/[\n\r]+/', trim($body));

        foreach($lines as $line) {
            // We won't make too many assumptions about the validity of the data.
            // This will also skip blank lines, which can happen between the system
            // error message and the user error message.

            if (strpos($line, '=') === false) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $data[$name] = $value;
        }

        return $this->createResponse($data);
    }

    /**
     * The Merchant ID is always needed.
     */
    public function setMerchantId($merchantId)
    {
        if (!is_numeric($merchantId)) {
            throw new InvalidRequestException('Merchant Account ID must be numeric.');
        }

        return $this->setParameter('merchantId', $merchantId);
    }

    public function getMerchantId()
    {
        return $this->getParameter('merchantId');
    }

    /**
     * The Portal ID is always needed.
     */
    public function setPortalId($portalId)
    {
        if (!is_numeric($portalId)) {
            throw new InvalidRequestException('Payment Portal ID must be numeric.');
        }

        return $this->setParameter('portalId', $portalId);
    }

    public function getPortalId()
    {
        return $this->getParameter('portalId');
    }

    /**
     * The Portal Key is always needed.
     */
    public function setPortalKey($portalKey)
    {
        return $this->setParameter('portalKey', $portalKey);
    }

    public function getPortalKey()
    {
        return $this->getParameter('portalKey');
    }

    /**
     * The Endpoint will only need to be changed if instructructed.
     */
    public function setEndpoint($endpoint)
    {
        return $this->setParameter('endpoint', $endpoint);
    }

    public function getEndpoint()
    {
        return $this->getParameter('endpoint');
    }

    /**
     * The hash method to use in a number of places.
     * The PAYONE account must be configured with the hash method to be used.
     */
    public function setHashMethod($hashMethod)
    {
        return $this->setParameter('hashMethod', $hashMethod);
    }

    public function getHashMethod()
    {
        return $this->getParameter('hashMethod');
    }

    /**
     * The default encoding is ISO-5559-1 in the API.
     * We don't want to encourage that, so will set UTF-8 as the default in this gateway.
     */
    public function setEncoding($encoding)
    {
        if ($encoding != ShopGateway::ENCODING_UTF8 && $encoding != ShopGateway::ENCODING_ISO8859) {
            throw new InvalidRequestException(sprintf(
                'Encoding invalid. Must be "%s" or "%s".',
                ShopGateway::ENCODING_UTF8,
                ShopGateway::ENCODING_ISO8859
            ));
        }

        return $this->setParameter('encoding', $encoding);
    }

    public function getEncoding()
    {
        return $this->getParameter('encoding');
    }

    /**
     * The language sets the language used in the customermessage results..
     */
    public function setLanguage($language)
    {
        return $this->setParameter('language', $language);
    }

    public function getLanguage()
    {
        return $this->getParameter('language');
    }

    /**
     * Get the card type letter for the identified brand.
     */
    public static function getCardTypes()
    {
        return static::$cardtypes;
    }

    /**
     * The cardType can be set explicitly, or left unset and derived automatically.
     */
    public function setCardType($cardType)
    {
        // Validate
        if (!in_array($cardType, $this->getCardTypes())) {
            throw new InvalidRequestException(sprintf(
                'Unrecognised card type "%s".', $cardType
            ));
        }

        return $this->setParameter('cardType', $cardType);
    }

    /**
     * Get the card type letter.
     */
    public function getCardType(CreditCard $card = null)
    {
        // Has a cardType already been set manually?
        $cardType = $this->getParameter('cardType');

        if (!isset($cardType)) {
            // No card type supplied, so we will try to derive it.

            $card = $this->getCard();

            // Extend the supported card types.

            // See http://stackoverflow.com/questions/13500648/regex-for-discover-credit-card
            $card->addSupportedBrand(
                'discover',
                '^6(?:011\d{12}|5\d{14}|4[4-9]\d{13}|22(?:1(?:2[6-9]|[3-9]\d)|[2-8]\d{2}|9(?:[01]\d|2[0-5]))\d{10})$'
            );

            // No regex found for Carte Bleue cards.
            //$card->addSupportedBrand('cartebleue', '/^ unknown $/');

            $brand_name = $card->getBrand();
            $card_types = $this->getCardTypes();

            if ($brand_name && isset($card_types[$brand_name])) {
                $cardType = $card_types[$brand_name];
            }
        }

        return $cardType;
    }

    /**
     * The Sub Account ID is needed for each transaction.
     */
    public function setSubAccountId($subAccountId)
    {
        if (!is_numeric($subAccountId)) {
            throw new InvalidRequestException('Sub Account ID must be numeric.');
        }

        return $this->setParameter('subAccountId', $subAccountId);
    }

    public function getSubAccountId()
    {
        return $this->getParameter('subAccountId');
    }

    /**
     * The customer ID is an optional merchant site identifier for the customer.
     */
    public function setCustomerId($customerId)
    {
        return $this->setParameter('customerId', $customerId);
    }

    public function getCustomerId()
    {
        return $this->getParameter('customerId');
    }

    /**
     * The Debtor ID is a PAYONE reference.
     */
    public function setDebtorId($debtorId)
    {
        if (!is_numeric($debtorId)) {
            throw new InvalidRequestException('Debtor ID must be numeric.');
        }

        return $this->setParameter('debtorId', $debtorId);
    }

    public function getDebtorId()
    {
        return $this->getParameter('debtorId');
    }

    /**
     * The VAT number (optional).
     */
    public function setVatNumber($vatNumber)
    {
        return $this->setParameter('vatNumber', $vatNumber);
    }

    public function getVatNumber()
    {
        return $this->getParameter('vatNumber');
    }

    public function getRequestCode()
    {
        return $this->request_code;
    }

    public function getClearingType()
    {
        return $this->clearingtype;
    }
}