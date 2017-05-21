<?php

namespace Omnipay\Payone\Message;

/**
 * Capture the incoming Transaction Status message from ONEPAY.
 */
 
use Omnipay\Common\Message\AbstractRequest as OmnipayAbstractRequest;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Payone\AbstractShopGateway;
use Omnipay\Common\Currency;
use DateTime;

class ShopTransactionStatusServerRequest extends OmnipayAbstractRequest implements NotificationInterface
{
    /**
     * Transaction status values.
     */
    const TRANSACTION_STATUS_COMPLETED  = 'completed';
    const TRANSACTION_STATUS_PENDING    = 'pending';

    /**
     * Event (txaction) values.
     */
    const EVENT_APPOINTED       = 'appointed';
    const EVENT_CAPTURE         = 'capture';
    const EVENT_UNDERPAID       = 'underpaid';
    const EVENT_PAID            = 'paid';
    const EVENT_CANCELATION     = 'cancelation';
    const EVENT_REFUND          = 'refund';
    const EVENT_DEBIT           = 'debit';
    const EVENT_REMINDER        = 'reminder';
    const EVENT_VAUTHORIZATION  = 'vauthorization';
    const EVENT_VSETTLEMENT     = 'vsettlement';
    const EVENT_TRANSFER        = 'transfer';
    const EVENT_INVOICE         = 'invoice';
    const EVENT_FAILED          = 'failed';

    protected $data;

    /**
     * No text messages to return.
     */
    public function getMessage()
    {
        return null;
    }

    /**
     * Get the POSTed data (since this is a ServerRequest).
     * There is no signature to check, so we trust what has been sent, hopefully
     * over a secure channel.
     */
    public function getData()
    {
        if (isset($this->data)) {
            return $this->data;
        }

        $data = $this->httpRequest->request->all();

        if ($this->getEncoding() == AbstractShopGateway::ENCODING_UTF8) {
            // We want UTF-8 back, so the ISO-8859 needs to be converted.

            array_walk($data, function (&$item) {
                if (!empty($item)) {
                    if (is_array($item)) {
                        $item = json_decode(utf8_encode(json_encode($item)), true);
                    } else {
                        $item = utf8_encode($item);
                    }
                }
            });
        }

        return $this->data = $data;
    }

    /**
     * Send an acknowledgement that we have successfully got the data.
     * Here we would also check any hashes of the data sent and raise appropriate
     * exceptions if the data does not look right.
     */
    public function sendData($data)
    {
        return $this->createResponse($data);
    }

    /**
     * The response is a very simple message for returning an acknowledgement to Payone.
     */
    protected function createResponse($data)
    {
        return $this->response = new ShopTransactionStatusServerResponse($this, $data);
    }

    /**
     * Check the hash against the data.
     * The hash is only md5 at this time, but may change to SHA2-384 at some future time.
     */
    public function isValid()
    {
        return $this->getKey() == hash('md5', $this->getPortalKey());
    }

    /**
     * Get a single data value from the ServerRequest data.
     */
    protected function getValue($name, $default = null)
    {
        $data = $this->getData();
        $value = array_key_exists($name, $data) ? $data[$name] : $default;

        return $value;
    }

    /**
     * The Portal Key is used to check the hash.
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
     * MD5 or SHA2-384
     */
    public function getKey()
    {
        return $this->getValue('key');
    }

    public function getPaymentPortalId()
    {
        return $this->getValue('portalid');
    }

    public function getSubAccountId()
    {
        return $this->getValue('aid');
    }

    public function getEvent()
    {
        return $this->getValue('txaction');
    }

    public function getAccessName()
    {
        return $this->getValue('accessname');
    }

    public function getAccessCode()
    {
        return $this->getValue('accesscode');
    }

    /**
     * Only relevant when a transaction is being notified, so can often be blank.
     * e.g. will not be set for a "capture" notification.
     */
    public function getTxStatus()
    {
        return $this->getValue('transaction_status');
    }

    /**
     * Translate the ONEPAY status values to OmniPay status values.
     */
    public function getTransactionStatus()
    {
        // The "appointed" event with no transation status will be sent as
        // the result of a 3D Secure result.
        // handle missing transaction status responses by PAYONE
        if (
            $this->getTxStatus() == static::TRANSACTION_STATUS_COMPLETED
            || $this->getEvent() == static::EVENT_APPOINTED
            || $this->getEvent() == static::EVENT_PAID
            || $this->getEvent() == static::EVENT_INVOICE
        ) {
            return static::STATUS_COMPLETED;
        }

        // handle missing transaction status responses by PAYONE
        if ($this->getEvent() == static::EVENT_CAPTURE
            || $this->getEvent() == static::EVENT_UNDERPAID
            || $this->getEvent() == static::EVENT_REFUND
            || $this->getEvent() == static::EVENT_DEBIT
            || $this->getEvent() == static::EVENT_REMINDER
            || $this->getEvent() == static::EVENT_VAUTHORIZATION
            || $this->getEvent() == static::EVENT_VSETTLEMENT
            || $this->getEvent() == static::EVENT_TRANSFER
        ) {
            return static::STATUS_PENDING;
        }

        return static::STATUS_FAILED;
    }

    /**
     * The merchant site identifier.
     */
    public function getTransactionId()
    {
        return $this->getValue('reference');
    }

    /**
     * The PAYONE gateway identifier.
     */
    public function getTransactionReference()
    {
        return $this->getValue('txid');
    }

    public function getNotifyVersion()
    {
        return $this->getValue('notify_version');
    }

    /**
     * 
     */
    protected function getParam()
    {
        return $this->getValue('param');
    }

    public function getMode()
    {
        return $this->getValue('mode');
    }

    public function getSequenceNumber()
    {
        return $this->getValue('sequencenumber');
    }

    public function getClearingType()
    {
        return $this->getValue('clearingtype');
    }

    public function getWalletType()
    {
        return $this->getValue('wallettype');
    }

    public function getTxTimestamp()
    {
        return $this->getValue('txtime');
    }

    /**
     * @returns DateTime
     * The timezone will be according to your local settings, and should
     * be corectly offset from the UTC Unix timestamp.
     */
    public function getTxTime()
    {
        $date = new DateTime();
        return $date->setTimestamp($this->getTxTimestamp());
    }

    public function getCompany()
    {
        return $this->getValue('company');
    }

    /**
     * The raw ISO currency code.
     */
    public function getCurrency()
    {
        return $this->getValue('currency');
    }

    /**
     * The currency object to help convert to minor units.
     */
    public function getCurrencyObject()
    {
        return Currency::find($this->getCurrency());
    }

    public function getDebtorId()
    {
        return $this->getValue('userid');
    }

    public function getCustomerId()
    {
        return $this->getValue('customerid');
    }

    // CC payment process additional parameters

    /**
     * Using the OmniPay convention name "Number".
     * e.g. "411111xxxxxx1111"
     */
    public function getNumber()
    {
        return $this->getValue('cardpan');
    }

    public function getNumberLastFour()
    {
        return substr($this->getNumber(), -4, 4) ?: null;
    }

    /**
     * Single-letter.
     * See Omnipay\Payone\Message\AbstractRequest::getCardTypes() for a mapping to
     * the OmniPay brand names.
     */
    public function getCardType()
    {
        return $this->getValue('cardtype');
    }

    /**
     * Get the card type as an Omnipay card brand name.
     * e.g. "visa" for "V".
     */
    public function getBrand()
    {
        $brands = AbstractRequest::getCardTypes();

        $type = $this->getCardType();
        foreach ($brands as $name => $code) {
            if ($type == $code) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Raw YYMM format (note the year is first).
     */
    public function getExpireDate()
    {
        return $this->getValue('cardexpiredate');
    }

    /**
     * Card expire date as a DateTime.
     * The last day of the month, end of the day (with a resolution of one second).
     */
    public function getExpireDateObject()
    {
        return \DateTime::createFromFormat('ym', $this->getExpireDate())
            ->modify('last day of this month')
            ->setTime(23, 59, 59);
    }

    /**
     * Not always present.
     */
    public function getCardholder()
    {
        return $this->getValue('cardholder');
    }

    /**
     * 
     */
    public function getFirstName()
    {
        return $this->getValue('firstname');
    }

    /**
     * 
     */
    public function getLastName()
    {
        return $this->getValue('lastname');
    }

    /**
     * Name built like cardholer name.
     */
    public function getName()
    {
        return trim($this->getFirstName() . ' ' . $this->getLastName());
    }

    /**
     * 
     */
    public function getStreet()
    {
        return $this->getValue('street');
    }

    /**
     * 
     */
    public function getAddress1()
    {
        return $this->getStreet();
    }

    /**
     * 
     */
    public function getCity()
    {
        return $this->getValue('city');
    }

    /**
     * 
     */
    public function getPostcode()
    {
        return $this->getValue('zip');
    }

    /**
     * ISO 2-digit Code
     */
    public function getCountry()
    {
        return $this->getValue('country');
    }

    /**
     * 
     */
    public function getEmail()
    {
        return $this->getValue('email');
    }

    /**
     * Convert a decimal amount to an integer, using the currency to
     * determine the scale factor.
     */
    protected function convertAmountInteger($amount)
    {
        return (int) round(
            $amount * pow(10, $this->getCurrencyObject()->getDecimals())
        );
    }

    /**
     * Raw amount, in major currency units.
     */
    public function getPrice()
    {
        return $this->getValue('price');
    }

    /**
     * In minor currency units.
     */
    public function getPriceInteger()
    {
        return $this->convertAmountInteger($this->getValue('price'));
    }

    /**
     * Alias for OmniPay consistency.
     * In minor currency units.
     */
    public function getAmountInteger()
    {
        return $this->getPriceInteger();
    }

    /**
     * Raw amount, in major currency units.
     */
    public function getBalance()
    {
        return $this->getValue('balance');
    }

    /**
     * Raw amount, in major currency units.
     */
    public function getBalanceInteger()
    {
        return $this->convertAmountInteger($this->getValue('balance'));
    }

    /**
     * Raw amount, in major currency units.
     */
    public function getReceivable()
    {
        return $this->getValue('receivable');
    }

    /**
     * Raw amount, in major currency units.
     */
    public function getReceivableInteger()
    {
        return $this->convertAmountInteger($this->getValue('receivable'));
    }

    /**
     * The encoding that we want to get back, i.e. that the merchant site uses.
     */
    public function setEncoding($encoding)
    {
        if ($encoding != AbstractShopGateway::ENCODING_UTF8 && $encoding != AbstractShopGateway::ENCODING_ISO8859) {
            throw new InvalidRequestException(sprintf(
                'Encoding invalid. Must be "%s" or "%s".',
                AbstractShopGateway::ENCODING_UTF8,
                AbstractShopGateway::ENCODING_ISO8859
            ));
        }

        return $this->setParameter('encoding', $encoding);
    }

    public function getEncoding()
    {
        // Default to UTF-8 as that is what most merchant sites will be using these days.
        return $this->getParameter('encoding') ?: AbstractShopGateway::ENCODING_UTF8;
    }

    // Delivery data (name, address)

    /**
     * 
     */
    public function getShippingFirstName()
    {
        return $this->getValue('shipping_firstname');
    }

    /**
     * 
     */
    public function getShippingLastName()
    {
        return $this->getValue('shipping_lastname');
    }

    /**
     * 
     */
    public function getShippingCompany()
    {
        return $this->getValue('shipping_company');
    }

    /**
     * Name built like cardholer name.
     */
    public function getShippingName()
    {
        return trim($this->getShippingFirstName() . ' ' . $this->getShippingLastName());
    }

    /**
     * 
     */
    public function getShippingStreet()
    {
        return $this->getValue('shipping_street');
    }

    /**
     * 
     */
    public function getShippingAddress1()
    {
        return $this->getShippingStreet();
    }

    /**
     * 
     */
    public function getShippingCity()
    {
        return $this->getValue('shipping_city');
    }

    /**
     * 
     */
    public function getShippingPostcode()
    {
        return $this->getValue('shipping_zip');
    }

    /**
     * ISO 2-digit Code
     */
    public function getShippingCountry()
    {
        return $this->getValue('shipping_country');
    }

    // TODO: payment process
}
