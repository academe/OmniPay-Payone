<?php

namespace Omnipay\Payone\Message;

/**
 * Shop Authorize Response for the Client API
 */

use Omnipay\Common\Message\RedirectResponseInterface;

class ShopClientCompleteAuthorizeResponse extends AbstractResponse implements RedirectResponseInterface
{
    public function getData()
    {
        return $this->data;
    }

    public function getTransactionReference()
    {
        return $this->getDataItem('txid');
    }

    /**
     * The raw status code.
     * Values: APPROVED / REDIRECT / ERROR / PENDING
     */
    public function getStatus()
    {
        return $this->getDataItem('status');
    }

    /**
     * The numeric error code, set only when the status is ERROR
     */
    public function getCode()
    {
        return $this->getDataItem('errorcode');
    }

    public function getDebtorId()
    {
        return $this->getDataItem('userid');
    }

    public function getMessage()
    {
        return $this->getDataItem('errormessage');
    }

    public function getCustomerMessage()
    {
        return $this->getDataItem('customermessage');
    }

    public function getAvs()
    {
        return $this->getDataItem('protect_result_avs');
    }

    /**
     * Set if status is REDIRECT
     */
    public function getRedirectUrl()
    {
        return $this->getDataItem('redirecturl');
    }

    /**
     * 3D Secure is always POST to an end bank, but GET for PAYONE since the
     * 3D Secure forms get wrapped by PAYONE.
     * Note: the application will only need to perform the redirect if an AJAX
     * return has been requested.
     */
    public function getRedirectMethod()
    {
        return 'GET';
    }

    /**
     * There is no redirect data - it is all in the URL.
     */
    public function getRedirectData()
    {
        return array();
    }

    public function isRedirect()
    {
        return $this->getStatus() === static::STATUS_REDIRECT;
    }

    public function isSuccessful()
    {
        return $this->getStatus() === static::STATUS_APPROVED
            || $this->getStatus() === static::STATUS_PENDING;
    }
}
