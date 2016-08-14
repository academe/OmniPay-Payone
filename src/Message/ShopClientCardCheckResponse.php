<?php

namespace Omnipay\Payone\Message;

/**
 * Shop Credit Card Response for the Client API.
 * This is used to help construct the AJAX form.
 */

use Omnipay\Common\Message\RedirectResponseInterface;

class ShopClientCardCheckResponse extends AbstractResponse
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
}
