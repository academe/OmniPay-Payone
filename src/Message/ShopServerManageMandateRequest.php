<?php

namespace Omnipay\Payone\Message;

/**
* PAYONE Shop Authorize Request
*/

class ShopServerManageMandateRequest extends ShopServerAuthorizeRequest
{
    /**
     * The "request" parameter.
     */
    protected $request_code = 'managemandate';

    protected function createResponse($data)
    {
        return $this->response = new ShopServerManageMandateResponse($this, $data);
    }
}
