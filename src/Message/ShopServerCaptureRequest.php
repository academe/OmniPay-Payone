<?php

namespace Omnipay\Payone\Message;

/**
* PAYONE Shop Capture Request
*/

class ShopServerCaptureRequest extends ShopServerAuthorizeRequest
{
    /**
     * Values for the settleAccount parameter.
     */
    const SETTLE_ACCOUNT_YES = 'yes';
    const SETTLE_ACCOUNT_NO = 'no';
    const SETTLE_ACCOUNT_AUTO = 'auto';

    /**
     * Values for the invoiceDeliveryMode parameter.
     */
    const INVOICE_DELIVERY_MODE_POST = 'M'; // aka Mail
    const INVOICE_DELIVERY_MODE_PDF = 'P';  // via email
    const INVOICE_DELIVERY_MODE_NONE = 'N'; // no delivery

    /**
     * The "request" parameter.
     */
    protected $request_code = 'capture';

    /**
     * Collect the data together to send to the Gateway.
     */
    public function getData()
    {
        $data = $this->getBaseData();

        $data['txid'] = $this->getTransactionReference();

        $sequence_number = $this->getSequenceNumber();
        if (isset($sequence_number)) {
            $data['sequencenumber'] = $sequence_number;
        }

        // Amount is in minor units.
        $data['amount'] = $this->getAmountInteger();

        // Currency is (i.e. has to be) ISO 4217
        $data['currency'] = $this->getCurrency();

        if ($this->getDescription()) {
            $data['narrative_text'] = substr($this->getDescription(), 0, 80);
        }

        if ($this->getSettleAccount()) {
            $data['settleaccount'] = $this->getSettleAccount();
        }

        if ($this->getDataItems()) {
            $data = array_merge($data, $this->getDataItems());
        }

        if ($this->getMerchantInvoiceId()) {
            $data['invoiceid'] = $this->getMerchantInvoiceId();
        }

        if ($this->getInvoiceDeliveryMode()) {
            $data['invoice_deliverymode'] = $this->getInvoiceDeliveryMode();
        }

        if ($this->getInvoiceDeliveryDate()) {
            $data['invoice_deliverydate'] = $this->getInvoiceDeliveryDate();
        }

        if ($this->getInvoiceDeliveryEndDate()) {
            $data['invoice_deliveryenddate'] = $this->getInvoiceDeliveryEndDate();
        }

        if ($this->getInvoiceAppendix()) {
            $data['invoiceappendix'] = $this->getInvoiceAppendix();
        }

        if ($this->getMandateId()) {
            $data['mandate_identification'] = $this->getMandateId();
        }

        return $data;
    }

    protected function createResponse($data)
    {
        return $this->response = new ShopServerCaptureResponse($this, $data);
    }

    /**
     * The sequence number is used to capture the total in smaller amounts.
     */
    public function setSequenceNumber($sequenceNumber)
    {
        if (!is_numeric($sequenceNumber)) {
            throw new InvalidRequestException('Sequence Number must be numeric.');
        }

        return $this->setParameter('sequenceNumber', $sequenceNumber);
    }

    public function getSequenceNumber()
    {
        return $this->getParameter('sequenceNumber');
    }


    /**
     * Sets whether you want to settle the account or not.
     */
    public function setSettleAccount($settleAccount)
    {
        // Allow tre/false/null for convenience.
        if ($settleAccount === true) {
            $settleAccount = static::SETTLE_ACCOUNT_YES;
        } elseif ($settleAccount === false) {
            $settleAccount = static::SETTLE_ACCOUNT_NO;
        } elseif (!isset($settleAccount)) {
            $settleAccount = static::SETTLE_ACCOUNT_AUTO;
        }

        if (
            $settleAccount != static::SETTLE_ACCOUNT_YES
            && $settleAccount != static::SETTLE_ACCOUNT_NO
            && $settleAccount != static::SETTLE_ACCOUNT_AUTO
        ) {
            throw new InvalidRequestException('Invalid value for settleAccount.');
        }

        return $this->setParameter('settleAccount', $settleAccount);
    }

    public function getSettleAccount()
    {
        return $this->getParameter('settleAccount');
    }
}
