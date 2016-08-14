<?php

namespace Omnipay\Payone\Message;

/**
 * Shop Server Check Credit Card Response
 */

class ShopServerCardCheckResponse extends AbstractResponse
{
    /**
     * Expected status values.
     */
    const STATUS_VALID      = 'VALID';
    const STATUS_INVALID    = 'INVALID';
    const STATUS_ERROR      = 'ERROR';

    public function isSuccessful()
    {
        return $this->getStatus() == static::STATUS_VALID;
    }

    /**
     * The pseudocardpan, descibed in OmniPay as the "token".
     */
    public function getToken()
    {
        return $this->getDataItem('pseudocardpan', null);
    }

    /**
     * The truncated card number, e.g. 401200XXXXXX1112.
     */
    public function getCardNumber()
    {
        return $this->getDataItem('truncatedcardpan', null);
    }
}
