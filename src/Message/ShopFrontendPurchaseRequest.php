<?php

namespace Omnipay\Payone\Message;

/**
 * Purchase, shop mode, classic payment page (user is sent to
 * the PAYONE site).
 */
class ShopFrontendPurchaseRequest extends ShopFrontendAuthorizeRequest
{
    /**
     * The "request" parameter.
     */
    protected $request_code = 'authorization';
}
