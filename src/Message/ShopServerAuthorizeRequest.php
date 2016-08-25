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

        return $data;
    }

    protected function createResponse($data)
    {
        return $this->response = new ShopServerAuthorizeResponse($this, $data);
    }
}
