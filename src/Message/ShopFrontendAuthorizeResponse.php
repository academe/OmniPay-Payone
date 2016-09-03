<?php

namespace Omnipay\Payone\Message;

/**
 * Shop Authorize Response.
 * Covers the Classic and Iframe redirection, both using the GET and POST methods.
 */

use Omnipay\Common\Message\RedirectResponseInterface;

class ShopFrontendAuthorizeResponse extends AbstractResponse implements RedirectResponseInterface
{
    /**
     * The chosen endpoint (Classic by default).
     */
    protected $endpoint = 'https://secure.pay1.de/frontend/';

    /**
     * The chosen redieect method (GET by default).
     */
    protected $redirectMethod = 'GET';

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
     * The method chosen externally.
     */
    public function getRedirectMethod()
    {
        return $this->redirectMethod;
    }

    /**
     * Redirect URL can be long GET or short POST.
     */
    public function getRedirectUrl()
    {
        if ($this->getRedirectMethod() == 'GET') {
            return $this->getEndpoint() . '?' . http_build_query($this->getRedirectData(), '', '&');
        } else {
            return $this->getEndpoint();
        }
    }

    /**
     * Data for the URL or form, including the hash.
     * We could pass the endpoint and redirectMethod in via the data,
     * then strip them out here.
     */
    public function getRedirectData()
    {
        return $this->getData();
    }

    public function setEndpoint($endpoint)
    {
        return $this->endpoint = $endpoint;
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }

    public function setRedirectMethod($value)
    {
        $this->redirectMethod = $value;
    }
}
