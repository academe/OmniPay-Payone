<?php

namespace Omnipay\Payone\Message;

/**
* PAYONE Shop Void Request
*/

class ShopVoidRequest extends ShopCaptureRequest
{
    public function getData()
    {
        // Settling the account while taking noting will void the authorization.
        $this->setAmount(0.00);
        $this->setSettleAccount(true);

        return parent::getData();
    }
}