<?php

namespace Omnipay\Payone\Message;

/**
* PAYONE Shop Authorize Request
*/

class ShopPurchaseRequest extends ShopAuthorizeRequest
{
    /**
     * The "request" parameter.
     */
    protected $request_code = 'authorization';

    protected function createResponse($data)
    {
        return $this->response = new ShopPurchaseResponse($this, $data);
    }
}
