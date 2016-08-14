<?php

namespace Omnipay\Payone\Message;

/**
 * Complete Authorize, shop mode, client payment gateway (AJAX card tokens or redirect).
 * There is nothing to "send" in this message. (Actually there is, to handle all the result methods and 3DS redirects)
 */

class ShopClientCompleteAuthorizeRequest extends AbstractRequest
{
    /**
     * The transaction result will be sent through query (GET) parameters.
     */
    public function getData()
    {
        return $this->httpRequest->query->all();
    }

    /**
     * Simple pass-through to the response object to parse the results.
     */
    public function sendData($data)
    {
        return $this->response = new ShopClientCompleteAuthorizeResponse($this, $data);
    }
}
