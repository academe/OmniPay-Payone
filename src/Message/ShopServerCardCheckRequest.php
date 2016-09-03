<?php

namespace Omnipay\Payone\Message;

/**
* PAYONE Shop Server Credit Card Check Request
*/

class ShopServerCardCheckRequest extends ShopServerAuthorizeRequest
{
    /**
     * The "request" parameter.
     */
    protected $request_code = 'creditcardcheck';

    public function getData()
    {
        $data = $this->getBaseData();

        // We don't need this one.
        unset($data['clearingtype']);

        $data['aid'] = $this->getSubAccountId();

        if ($this->getLanguage()) {
            $data['language'] = $this->getLanguage();
        }

        $data['storecarddata'] = $this->getStoreCard() ? 'yes' : 'no';

        $data += $this->getDataCard();

        return $data;
    }

    public function createResponse($data)
    {
        return $this->response = new ShopServerCardCheckResponse($this, $data);
    }

    /**
     * Flag to indicate whether the card details should be stored for reuse.
     */
    public function setStoreCard($value)
    {
        return $this->setParameter('storeCard', $value);
    }

    public function getStoreCard()
    {
        return $this->getParameter('storeCard');
    }
}
