<?php

namespace Omnipay\Payone\Message;

/**
 * Capture the incoming Transaction Status message from ONEPAY.
 */
 
use Omnipay\Common\Message\AbstractRequest as OmnipayAbstractRequest;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Common\Currency;
use DateTime;

class ShopCompleteTxnStatusServerRequest extends OmnipayAbstractRequest implements NotificationInterface
{
    /**
     * Transaction status values.
     */
    const TRANSACTION_STATUS_COMPLETE = 'completed';
    const TRANSACTION_STATUS_PENDING = 'pending';

    /**
     * Clearing type values.
     */
    // Debit payment
    const CLEARING_TYPE_ELV = 'elv';
    // Credit card
    const CLEARING_TYPE_CC = 'cc';
    // Prepayment
    const CLEARING_TYPE_VOR = 'vor';
    // Invoice
    const CLEARING_TYPE_REC = 'rec';
    // Cash on delivery
    const CLEARING_TYPE_COD = 'cod';
    // Online bank transfer
    const CLEARING_TYPE_SB = 'sb';
    // e-Wallet
    const CLEARING_TYPE_WLT = 'wlt';
    // Financing
    const CLEARING_TYPE_FNC = 'fnc';

    /**
     * Event (txaction) values.
     */
    const EVENT_APPOINTED = 'appointed';
    const EVENT_CAPTURE = 'capture';
    const EVENT_UNDERPAID = 'underpaid';
    const EVENT_PAID = 'paid';
    const EVENT_CANCELATION = 'cancelation';
    const EVENT_REFUND = 'refund';
    const EVENT_DEBIT = 'debit';
    const EVENT_REMINDER = 'reminder';
    const EVENT_VAUTHORIZATION = 'vauthorization';
    const EVENT_VSETTLEMENT = 'vsettlement';
    const EVENT_TRANSFER = 'transfer';
    const EVENT_INVOICE = 'invoice';
    const EVENT_FAILED = 'failed';

    protected $data;

    /**
     * Returns just the response message.
     */
    public function getMessage()
    {
        return 'TBC';
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

return $this->data = array
(
    'key' => '92c9fc714a0464b4f8733414cfb41bf4',
    'txaction' => 'appointed',
    'portalid' => '2024178',
    'aid' => '33683',
    'clearingtype' => 'cc',
    'notify_version' => '7.4',
    'txtime' => '1469193345',
    'currency' => 'EUR',
    'userid' => '86017070',
    'accessname' => '',
    'accesscode' => '',
    'param' => '',
    'mode' => 'test',
    'price' => '3.99',
    'txid' => '196486455',
    'reference' => 'M53717315',
    'sequencenumber' => '0',
    'company' => 'AAAAAA',
    'firstname' => 'Jason',
    'lastname' => 'Judge',
    'street' => '123 Street Name',
    'zip' => 'NE262NP',
    'city' => 'tttttt',
    'email' => 'jason.judge@academe.co.uk',
    'country' => 'GB',
    'cardexpiredate' => '2012',
    'cardtype' => 'V',
    'cardpan' => '411111xxxxxx1111',
    'transaction_status' => 'completed',
    'balance' => '0.00',
    'receivable' => '0.00',
);
        return $this->data = $this->httpRequest->request->all();
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
        return $this->response = new ShopCompleteTxnStatusServerResponse($this, $data);
    }

    /**
     * Get a single data value from the ServerRequest data.
     */
    protected function getValue($name, $default = null)
    {
        $data = $this->getData();
        return array_key_exists($name, $data) ? $data[$name] : $default;
    }

    /**
     * MD5 or SHA2-384
     */
    public function getPaymentPortalKey()
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

    public function getTxStatus()
    {
        return $this->getValue('transaction_status');
    }

    /**
     * Translate the ONEPAY status values to OmniPay status values.
     */
    public function getTransactionStatus()
    {
        if ($this->getTxStatus() == static::TRANSACTION_STATUS_COMPLETE) {
            return static::STATUS_COMPLETED;
        }

        if ($this->getTxStatus() == static::TRANSACTION_STATUS_PENDING) {
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

    public function getTxTimestamp()
    {
        return $this->getValue('txtime');
    }

    public function getCompany()
    {
        return $this->getValue('company');
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
        foreach($brands as $name => $code) {
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

    // TODO: delivery data (name, address)
    // TODO: payment process
}
