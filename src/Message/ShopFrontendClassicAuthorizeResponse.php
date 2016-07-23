<?php

namespace Omnipay\Payone\Message;

/**
 * Shop Authorize Response - "classic" front end response.
 */

use Omnipay\Common\Message\RedirectResponseInterface;

class ShopFrontendClassicAuthorizeResponse extends AbstractResponse implements RedirectResponseInterface
{
    /**
     * The Classic endpoint - send the user here, leaving the merchant site.
     */
    protected $endpoint = 'https://secure.pay1.de/frontend/';

    /**
     * Not yet "successful" as user needs to be sent to PAYONE site.
     */
    public function isSuccessful()
    {
        return false;
    }

    /**
     * A redirect is needed.
     */
    public function isRedirect()
    {
        return true;
    }

    /**
     * The URL will contain everything that is needed.
     */
    public function getRedirectMethod()
    {
        return 'GET';
    }

    /**
     * Class redirect URL.
     */
    public function getRedirectUrl()
    {
        return $this->endpoint . '?' . http_build_query($this->getRedirectData(), '', '&');
    }

    /**
     * 3D Secure data for when redirecting.
     */
    public function getRedirectData()
    {
        return $this->getData();
    }
}

