<?php

namespace Omnipay\Payone\Message;

/**
* PAYONE Shop Authorize Request
*/

class ShopServerPurchaseRequest extends ShopServerAuthorizeRequest
{
    /**
     * The "request" parameter.
     */
    protected $request_code = 'authorization';

    protected function createResponse($data)
    {
        return $this->response = new ShopServerPurchaseResponse($this, $data);
    }
}
