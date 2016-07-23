<?php

namespace Omnipay\Payone\Message;

/**
* PAYONE Shop Authorize Request
*/

class ShopAuthorizeRequest extends AbstractRequest
{
    /**
     * The "request" parameter.
     */
    protected $request_code = 'preauthorization';

    /**
     * Collect the data together to send to the Gateway.
     */
    public function getData()
    {
        $data = $this->getBaseData();

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

        if ($this->getDescription()) {
            $data['narrative_text'] = substr($this->getDescription(), 0, 80);
        }

        if ($this->getVatNumber()) {
            $data['vatid'] = $this->getVatNumber();
        }

        // Shipping details.

        $data += $this->getDataShipping();

        return $data;
    }

    protected function createResponse($data)
    {
        return $this->response = new ShopAuthorizeResponse($this, $data);
    }
}
