<?php

namespace Omnipay\Payone\Message;

/**
 * PAYONE Shop Void Request.
 * Returns a ShopCaptureResponse on sending.
 */

class ShopServerVoidRequest extends ShopServerCaptureRequest
{
    public function getData()
    {
        // Settling the account while taking nothing will void the authorization.
        $this->setAmount(0.00);
        $this->setSettleAccount(true);

        return parent::getData();
    }
}
