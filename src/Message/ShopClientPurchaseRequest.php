<?php

namespace Omnipay\Payone\Message;

/**
 * Purchase, shop mode, client payment gateway (AJAX card tokens or redirect).
 */

class ShopClientPurchaseRequest extends ShopClientAuthorizeRequest
{
    /**
     * The "request" parameter.
     */
    protected $request_code = 'authorization';
}
