<?php

namespace Omnipay\Payone\Message;

/**
* PAYONE Shop Authorize Request
*/

use Omnipay\Payone\AbstractShopGateway;

class ShopServerAuthorizeRequest extends AbstractRequest
{
    /**
     * The "request" parameter.
     */
    protected $request_code = 'preauthorization';

    /**
     * Base data required for all Server transactions.
     */
    protected function getBaseData()
    {
        $data = array(
            'request' => $this->request_code,
            'mid' => $this->getMerchantId(),
            'portalid' => $this->getPortalId(),
            'api_version' => AbstractShopGateway::API_VERSION,
            // Only md5 is used to encode the key for the Server API (no hashing is
            // needed over the secure server-to-server connection).
            'key' => md5($this->getPortalKey()),
            'mode' => (bool)$this->getTestMode()
                ? AbstractShopGateway::MODE_TEST
                : AbstractShopGateway::MODE_LIVE,
            'encoding' => $this->getEncoding(),
            'language' => $this->getLanguage(),
        );

        return $data;
    }

    /**
     * Collect the data together to send to the Gateway.
     */
    public function getData()
    {
        $data = $this->getBaseData();

        $data['clearingtype'] = $this->getClearingType();

        $data['aid'] = $this->getSubAccountId();

        // CC details
        $data += $this->getDataCard();

        // Merchant site reference.
        $data['reference'] = $this->getTransactionId();

        // Amount is in minor units.
        $data['amount'] = $this->getAmountInteger();

        // Currency is (i.e. has to be) ISO 4217
        $data['currency'] = $this->getCurrency();

        // Personal data.
        $data += $this->getDataPersonal();

        if ($this->getParam() !== null) {
            $data['param'] = $this->getParam();
        }

        if ($this->getDescription()) {
            $data['narrative_text'] = substr($this->getDescription(), 0, 80);
        }

        if ($this->getVatNumber()) {
            $data['vatid'] = $this->getVatNumber();
        }

        // URL orverrides.
        $data += $this->getDataUrl();

        // Shipping details.
        $data += $this->getDataShipping();

        // Items/Cart details
        $data += $this->getDataItems();

        if ($this->getMerchantInvoiceId()) {
            $data['invoiceid'] = $this->getMerchantInvoiceId();
        }

        if ($this->getInvoiceDeliveryMode()) {
            $data['invoice_deliverymode'] = $this->getInvoiceDeliveryMode();
        }

        if ($this->getInvoiceDeliveryDate()) {
            $data['invoice_deliverydate'] = $this->getInvoiceDeliveryDate();
        }

        if ($this->getInvoiceAppendix()) {
            $data['invoiceappendix'] = $this->getInvoiceAppendix();
        }

        if ($this->getWalletType()) {
            $data['wallettype'] = $this->getWalletType();
        }

        if ($this->getOnlinebankTransferType()) {
            $data['onlinebanktransfertype'] = $this->getOnlinebankTransferType();
        }

        if ($this->getBankCountry()) {
            $data['bankcountry'] = $this->getBankCountry();
        }

        if ($this->getIban()) {
            $data['iban'] = $this->getIban();
        }

        if ($this->getMandateId()) {
            $data['mandate_identification'] = $this->getMandateId();
        }

        return $data;
    }

    protected function createResponse($data)
    {
        return $this->response = new ShopServerAuthorizeResponse($this, $data);
    }

    public function setInvoiceDeliveryMode($deliveryMode)
    {
        return $this->setParameter('invoiceDeliveryMode', $deliveryMode);
    }

    public function getInvoiceDeliveryMode()
    {
        return $this->getParameter('invoiceDeliveryMode');
    }

    public function setInvoiceDeliveryDate($deliveryDate)
    {
        return $this->setParameter('invoiceDeliveryDate', $deliveryDate);
    }

    public function getInvoiceDeliveryDate()
    {
        return $this->getParameter('invoiceDeliveryDate');
    }

    public function setInvoiceAppendix($invoiceAppendix)
    {
        return $this->setParameter('invoiceAppendix', $invoiceAppendix);
    }

    public function getInvoiceAppendix()
    {
        return $this->getParameter('invoiceAppendix');
    }

    public function setMerchantInvoiceId($invoiceId)
    {
        return $this->setParameter('merchantInvoiceId', $invoiceId);
    }

    public function getMerchantInvoiceId()
    {
        return $this->getParameter('merchantInvoiceId');
    }

    public function setWalletType($walletType)
    {
        return $this->setParameter('walletType', $walletType);
    }

    public function getWalletType()
    {
        return $this->getParameter('walletType');
    }

    public function setOnlinebankTransferType($onlinebankTransferType)
    {
        return $this->setParameter('onlinebankTransferType', $onlinebankTransferType);
    }

    public function getOnlinebankTransferType()
    {
        return $this->getParameter('onlinebankTransferType');
    }

    public function setBankCountry($bankCountry)
    {
        return $this->setParameter('bankCountry', $bankCountry);
    }

    public function getBankCountry()
    {
        return $this->getParameter('bankCountry');
    }

    public function setIban($iban)
    {
        return $this->setParameter('iban', $iban);
    }

    public function getIban()
    {
        return $this->getParameter('iban');
    }

    public function getMandateId()
    {
        return $this->getParameter('mandateId');
    }

    public function setMandateId($id)
    {
        return $this->setParameter('mandateId', $id);
    }
}
