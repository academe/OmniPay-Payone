<?php

namespace Omnipay\Payone\Message;

/**
 * Shop Credit Card Response for the Client API.
 * This is used to help construct the AJAX form.
 */

use Omnipay\Common\Message\RedirectResponseInterface;

class ShopClientCardCheckResponse extends AbstractResponse implements RedirectResponseInterface
{
    public function isSuccessful()
    {
        return true;
    }

    /**
     * A "transparent redirect" indicates this result is used to build form,
     * one which in this case is submitted via AJAX.
     */
    public function isTransparentRedirect()
    {
        return true;
    }

    public function isRedirect()
    {
        return true;
    }

    /**
     * This data all becomes hidden fields in the credit card check form.
     */
    public function getRedirectData()
    {
        return $this->getData();
    }

    /**
     * Actually either POST or GET will work.
     * This is over a secure connection, so GET won't be cached.
     */
    public function getRedirectMethod()
    {
        return 'POST';
    }

    /**
     * The URL the form data will POST to.
     */
    public function getRedirectUrl()
    {
        return $this->request->getEndpoint();
    }
}
