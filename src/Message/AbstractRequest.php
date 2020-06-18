<?php

namespace Omnipay\Payone\Message;

/**
 * PAYONE Abstract Request.
 */

use Omnipay\Common\Message\AbstractRequest as OmnipayAbstractRequest;
use Omnipay\Payone\Extend\ItemInterface as ExtendItemInterface;
use Omnipay\Payone\Extend\Item as ExtendItem;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Payone\AbstractShopGateway;
use Omnipay\Common\CreditCard;
//use Omnipay\Common\Currency;
use Money\Currency;
use Omnipay\Omnipay;
use Guzzle\Http\Url;

use Omnipay\Payone\Traits\HasGatewayParams;

abstract class AbstractRequest extends OmnipayAbstractRequest
{
    use HasGatewayParams;

    /**
     * Item types ("it") in the basket.
     */

    // Goods
    const ITEM_TYPE_GOODS = 'goods';
    // Shipping charges
    const ITEM_TYPE_SHIPMENT = 'shipment';
    // Handling fee
    // "handling" only available after assignment by BillSAFE
    const ITEM_TYPE_HANDLING = 'handling';
    // Voucher / discount
    const ITEM_TYPE_VOUCHER = 'voucher';

    /**
     * The list of raw data fields that must be hashed to protect from
     * manipulation.
     * There seems to be no one complete list defined anywhere, so these
     * field names come from a number of sources.
     */
    protected $hash_fields = array(
        // From the SDK
        'mid',
        'amount',
        'productid',
        'aid',
        'currency',
        'accessname',
        'portalid',
        'due_time',
        'accesscode',
        'mode',
        'storecarddata',
        'access_expiretime',
        'request',
        'checktype',
        'access_canceltime',
        'responsetype',
        'addresschecktype',
        'access_starttime',
        'reference',
        'consumerscoretype',
        'access_period',
        'userid',
        'invoiceid',
        'access_aboperiod',
        'customerid',
        'invoiceappendix',
        'access_price',
        'param',
        'invoice_deliverymode',
        'access_aboprice',
        'narrative_text',
        'eci',
        'access_vat',
        'successurl',
        'settleperiod',
        'errorurl',
        'settletime',
        'backurl',
        'vaccountname',
        'exiturl',
        'vreference',
        'clearingtype',
        'encoding',
        //
        // Listed in documentation only, either in a dedicated list or
        // or marked as requiring a hash in the field tables.
        'amount_recurring',
        'period_length_recurring',
        'period_unit_recurring',
        //
        'amount_trail',
        'period_length_trail',
        'period_unit_trail',
        //
        'api_version',
        'display_name',
        'display_address',
        'autosubmit',
        'targetwindow',
        'frontend_description',
        //
        'booking_date',
        'document_date',
        'ecommercemode',
        'getusertoken',
        'mandate_identification',
        'settleaccount',
        //
        'invoice_deliverydate',
        'invoice_deliveryenddate',
        //
        // Cart items, where [x] matches a wildcard.
        'pr[x]',
        'id[x]',
        'it[x]',
        'ti[x]',
        'de[x]',
        'va[x]',
        'no[x]',
        //
        'pr_recurring[x]',
        'id_recurring[x]',
        'ti_recurring[x]',
        'va_recurring[x]',
        'no_recurring[x]',
        'de_recurring[x]',
        //
        'pr_trail[x]',
        'id_trail[x]',
        'ti_trail[x]',
        'de_trail[x]',
        'va_trail[x]',
        'no_trail[x]',
    );

    /**
     * Default ID for the auto-created Item if none are supplied.
     * If you don't want to use this default, then make sure you always
     * pass an ItemBag into the transaction.
     */
    protected $defaultItemId = '000000';

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
        CreditCard::BRAND_VISA          => 'V',
        CreditCard::BRAND_MASTERCARD    => 'M',
        CreditCard::BRAND_DISCOVER      => 'C',
        CreditCard::BRAND_AMEX          => 'A',
        CreditCard::BRAND_DINERS_CLUB   => 'D',
        CreditCard::BRAND_JCB           => 'J',
        CreditCard::BRAND_SWITCH        => 'U',
        CreditCard::BRAND_SOLO          => null,
        CreditCard::BRAND_DANKORT       => null,
        CreditCard::BRAND_MAESTRO       => 'O', // International
        //CreditCard::BRAND_FORBRUGSFORENINGEN => null, // No details available
        CreditCard::BRAND_LASER         => null,
        'cartebleue'                    => 'B',
    );

    /**
     * The credit card e-commerce mode.
     * internet = disabled 3D Secure
     * 3dsecure = enables 3D Secure where cards support it
     * moto = mail or telephone (card not present)
     */
    const ECOMMERCE_MODE_INTERNET   = 'internet';
    const ECOMMERCE_MODE_3DSECURE   = '3dsecure';
    const ECOMMERCE_MODE_MOTO       = 'moto';

    /**
     * A list of countries for which state codes may be given.
     */
    protected $countries_with_states = array(
        'US', 'CA', 'CN', 'JP', 'MX', 'BR', 'AR', 'ID', 'TH', 'IN',
    );

    /**
     * Filter an array of data to just return fields that need to be hashed.
     */
    protected function filterHashFields($data)
    {
        foreach ($data as $key => $value) {
            // If the key is an array element then normalise it, e.g. pr[1] => pr[x]
            if (strpos($key, '[')) {
                $normalised_key = preg_replace('/\[[0-9]*\]/', '[x]', $key);
            } else {
                $normalised_key = $key;
            }

            // If the normalised key is not in the list of hashable keys,
            // then remove that element from the supplied data.
            if (! in_array($normalised_key, $this->hash_fields)) {
                unset($data[$key]);
            }
        }

        // Return the data array with non-hashable elements removed.
        return $data;
    }

    /**
     * Hash an array prior to sending it.
     * Only hashable fields will be included in the hash calculation.
     */
    protected function hashArray($data)
    {
        // Filter the array using the list of hashable fields.
        $hash_data = $this->filterHashFields($data);

        // Sort the data alphanbetically by key.
        ksort($hash_data, SORT_NATURAL);

        return $this->hashString(implode('', $hash_data));
    }

    /**
     * Hash a string using the chosen method (hashMethod) and supplied key (portalKey).
     */
    protected function hashString($string)
    {
        $key = $this->getPortalKey();

        // The key is concatenated to the string for md5.
        if ($this->getHashMethod() == AbstractShopGateway::HASH_MD5) {
            return strtolower(md5($string . $key));
        }

        // The key is a separate parameter for SHA2 384
        if ($this->getHashMethod() == AbstractShopGateway::HASH_SHA2_384) {
            return strtolower(hash_hmac('sha384', $string, $key));
        }

        throw new InvalidRequestException('Unknown hashing method.');
    }

    /**
     * Collect the personal data to send to the Gateway.
     */
    public function getDataPersonal(array $data = [])
    {
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

        $ipv4match = preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $this->getClientIp());
        if ($this->getClientIp() && $ipv4match) {
            $data['ip'] = $this->getClientIp();
        }

        return $data;
    }

    /**
     * Collect the shipping data to send to the Gateway.
     */
    public function getDataShipping(array $data = [])
    {
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
    public function getDataCard(array $data = [])
    {
        if ($card = $this->getCard()) {
            // If only the card number is set, and not the expiry year, month or CVV, then
            // treat this card number as a Pseudo card PAN.
            // A Pseudo card PAN is a card+expiry+CVV that has been tokenised.

            // Each will be a different value if not set: 0, null and "" (sigh). But all will be empty.
            // The transaction gets full card details OR a pseudocardpan.
            // Please note that the month will be (int)0 if not set while the year will be null. See:
            // https://github.com/thephpleague/omnipay-common/issues/29
            // It is also assumed that an empty ('000') CVV is valid for a card, so we compare to null.

            $expiryYear = $card->getExpiryYear();
            $expiryMonth = $card->getExpiryMonth();

            if (empty($expiryYear) && empty($expiryMonth) && $card->getCvv() === null) {
                $data['pseudocardpan'] = $card->getNumber();
            } elseif ($card->getNumber()) {
                if ($this->getEcommerceMode()) {
                    $data['ecommercemode'] = $this->getEcommerceMode();
                }

                $data['cardpan'] = $card->getNumber();

                $data['cardtype'] = $this->getCardType();

                // Format: YYMM
                $data['cardexpiredate'] = $card->getExpiryDate('ym');

                // The card holder name is defined by OmniPay as the billing first name and
                // last name concatenated.

                if ($card->getName()) {
                    $data['cardholder'] = $card->getName();
                }

                $cvv = $card->getCvv();

                if (! empty($cvv)) {
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
     * Collect URL overrides.
     */
    public function getDataUrl(array $data = [])
    {
        // For when authentication passes.

        $successUrl = $this->getSuccessUrl();
        if (! empty($successUrl)) {
            $data['successurl'] = $this->getSuccessUrl();
        }

        // For when authentication fails.

        $errorUrl = $this->getErrorUrl();
        if (! empty($errorUrl)) {
            $data['errorurl'] = $this->getErrorUrl();
        }

        // For when the user cancels payment on the remote gateway site.

        $cancelUrl = $this->getCancelUrl();
        if (! empty($cancelUrl)) {
            $data['backurl'] = $this->getCancelUrl();
        }

        return $data;
    }

    /**
     * Collect the items/cart/basket data to send to the Gateway.
     */
    public function getDataItems(array $data = [])
    {
        // Each item must be contingously numbered, starting from 1.
        $item_count = 0;

        $items = $this->getItems();
        if (! empty($items)) {
            foreach ($this->getItems() as $item) {
                $item_count++;

                if ($item instanceof ExtendItemInterface) {
                    $id = $item->getId();
                    $vat = $item->getVat();
                    $price = $item->getPriceInteger($this->getCurrency());
                    $item_type = $item->getItemType();
                } else {
                    $id = $this->defaultItemId;
                    $vat = null;
                    $price = ExtendItem::convertPriceInteger($item->getPrice(), $this->getCurrency());
                    $item_type = null;
                }

                $data['id['.$item_count.']'] = $id;
                $data['pr['.$item_count.']'] = $price;
                $data['no['.$item_count.']'] = $item->getQuantity();
                $data['de['.$item_count.']'] = $item->getName();

                if (isset($vat)) {
                    $data['va['.$item_count.']'] = $vat;
                }

                // For BSV / KLV / KLS financingtype.
                // Values see ITEM_TYPE_*
                if (isset($item_type)) {
                    $data['it['.$item_count.']'] = $item_type;
                }
            }
        }

        return $data;
    }

    /**
     * The response to sending the request is a text list of name=value pairs.
     * The output data is a mix of the sent data with the received data appended.
     */
    public function sendData($data)
    {
        $httpResponse = $this->httpClient->request(
            'POST',
            $this->getEndpoint(),
            [
                "Content-Type" => "application/x-www-form-urlencoded",
            ],
            http_build_query($data)
        );

        // CURL_SSLVERSION_TLSv1_2 for libcurl < 7.35
        //$httpRequest->getCurlOptions()->set(CURLOPT_SSLVERSION, 6);
        //$httpResponse = $httpRequest->send();

        // The body returned will be text of multiple lines, each containing {name}={value}
        // TODO: also check $httpResponse->getContentType() to make sure we are getting a
        // sane response and not "application/javascript" or something else.
        // The normal response seems to be "text/plain; charset=UTF-8".

        $body = (string)$httpResponse->getBody();

        // Experiments show the lines are separated by \n only.
        // The documentation does not specify this to be the case, so we will split
        // on \r too just to be safe.

        $lines = preg_split('/[\n\r]+/', trim($body));

        foreach ($lines as $line) {
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
                'Unrecognised card type "%s".',
                $cardType
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
            // Issue #27 the addSupportedBrand() method was introduced in omnipay/common 2.4,
            // so this check is necessary to support down to 2.0

            if (method_exists($card, 'addSupportedBrand')) {
                $card->addSupportedBrand(
                    'discover',
                    '^6('
                    . '?:011\d{12}'
                    . '|5\d{14}'
                    . '|4[4-9]\d{13}'
                    . '|22(?:1(?:2[6-9]|[3-9]\d)|[2-8]\d{2}|9(?:[01]\d|2[0-5]))\d{10}'
                    . ')$'
                );

                // No regex found for Carte Bleue cards.
                //$card->addSupportedBrand('cartebleue', '/^ unknown $/');
            }

            $brand_name = $card->getBrand();
            $card_types = $this->getCardTypes();

            if ($brand_name && isset($card_types[$brand_name])) {
                $cardType = $card_types[$brand_name];
            }
        }

        return $cardType;
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

    /**
     * The success URL (optional).
     */
    public function setSuccessUrl($successUrl)
    {
        return $this->setParameter('successUrl', $successUrl);
    }

    public function getSuccessUrl()
    {
        return $this->getParameter('successUrl');
    }

    /**
     * The return URL, an alias for returnUrl().
     */
    public function setReturnUrl($returnUrl)
    {
        return $this->setSuccessUrl($returnUrl);
    }

    public function getReturnUrl()
    {
        return $this->getSuccessUrl();
    }

    /**
     * The error URL (optional).
     */
    public function setErrorUrl($errorUrl)
    {
        return $this->setParameter('errorUrl', $errorUrl);
    }

    public function getErrorUrl()
    {
        return $this->getParameter('errorUrl');
    }

    /**
     * The back (cancel) URL (optional).
     */
    public function setCancelUrl($cancelUrl)
    {
        return $this->setParameter('cancelUrl', $cancelUrl);
    }

    public function getCancelUrl()
    {
        return $this->getParameter('cancelUrl');
    }

    /**
     * The merchant site invoice ID (optional).
     */
    public function setInvoiceId($value)
    {
        return $this->setParameter('invoiceId', $value);
    }

    public function getInvoiceId()
    {
        return $this->getParameter('invoiceId');
    }

    /**
     * The financing type for clearing type AbstractShopGateway::CLEARING_TYPE_FNC
     */
    public function setFinancingtype($financingtype)
    {
        return $this->setParameter('financingtype', $financingtype);
    }

    public function getFinancingtype()
    {
        return $this->getParameter('financingtype');
    }

    /**
     * The custom "param" field.
     */
    public function setParam($value)
    {
        return $this->setParameter('param', $value);
    }

    public function getParam()
    {
        return $this->getParameter('param');
    }

    /**
     * The ecommerce mode - relates to the risk of the card transaction
     * being fraudulent.
     */
    public function setEcommerceMode($ecommerceMode)
    {
        if (isset($ecommerceMode)
            && $ecommerceMode != static::ECOMMERCE_MODE_INTERNET
            && $ecommerceMode != static::ECOMMERCE_MODE_3DSECURE
            && $ecommerceMode != static::ECOMMERCE_MODE_MOTO
        ) {
            throw new InvalidRequestException('ecommerceMode is invalid.');
        }

        return $this->setParameter('ecommerceMode', $ecommerceMode);
    }

    public function getEcommerceMode()
    {
        return $this->getParameter('ecommerceMode');
    }

    /**
     * An alternative way to set the 3D Secure mode.
     *
     * @param boolean $value True/False to enforce 3D Secure on/off
     */
    public function set3dSecure($value)
    {
        if ((bool)$value === true) {
            $this->setEcommerceMode(static::ECOMMERCE_MODE_3DSECURE);
        }

        if ((bool)$value === false) {
            $this->setEcommerceMode(static::ECOMMERCE_MODE_INTERNET);
        }
    }

    public function get3dSecure()
    {
        return $this->getEcommerceMode() == static::ECOMMERCE_MODE_3DSECURE;
    }

    public function getRequestCode()
    {
        return $this->request_code;
    }
}
