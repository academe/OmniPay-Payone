<?php

namespace Omnipay\Payone\Message;

/**
 * Shop Authorize Response - "iframe" front end response.
 */

use Omnipay\Common\Message\RedirectResponseInterface;

class ShopFrontendIframeAuthorizeResponse extends ShopFrontendClassicAuthorizeResponse
{
    /**
     * The Classic endpoint - send the user here, leaving the merchant site.
     */
    protected $endpoint = 'https://frontend.pay1.de/frontend/v2/';

    /**
     * The URL will contain everything that is needed.
     */
    public function getRedirectMethod()
    {
        return 'POST';
    }

    /**
     * Class redirect URL.
     */
    public function getRedirectUrl()
    {
        return $this->endpoint;
    }

    /**
     * 3D Secure data for when redirecting.
     */
    public function getRedirectData()
    {
        return $this->getData();
    }
}

