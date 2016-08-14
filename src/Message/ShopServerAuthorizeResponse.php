<?php

namespace Omnipay\Payone\Message;

/**
 * Shop Authorize Response
 */

use Omnipay\Common\Message\RedirectResponseInterface;

class ShopServerAuthorizeResponse extends AbstractResponse implements RedirectResponseInterface
{
    /**
     * Success if the transaction has completed, and does not require a redirect.
     * CHECKME: should we include PENDING as a success state?
     */
    public function isSuccessful()
    {
        return $this->getStatus() == static::STATUS_APPROVED;
    }

    /**
     * The transaction will be PENDING if it is with the 2nd payment processor.
     * We will hear later what the final outcome is through a transaction status callback message.
     */
    public function isPending()
    {
        return $this->getStatus() == static::STATUS_PENDING;
    }

    /**
     * Indicate whether a 3D Secure redirect is required.
     */
    public function isRedirect()
    {
        return $this->getStatus() == static::STATUS_REDIRECT;
    }

    /**
     * 3D Secure requires a POST redirect.
     * 3D Secure works using a GET redirect in test mode, but requires POST for most
     * live banks.
     */
    public function getRedirectMethod()
    {
        return 'POST';
    }

    /**
     * 3D Secure redirect URL.
     */
    public function getRedirectUrl()
    {
        return @$this->data['redirecturl'] ?: null;
    }

    /**
     * 3D Secure data for when redirecting.
     */
    public function getRedirectData()
    {
        return [];
    }

    /**
     * The PAYONE transaction process ID (N..12).
     */
    public function getTransactionReference()
    {
        return isset($this->data['txid']) ? $this->data['txid'] : null;
    }

    /**
     * The PAYONE debtor ID.
     */
    public function getDebtorId()
    {
        return isset($this->data['userid']) ? $this->data['userid'] : null;
    }

    /**
     * Set if AVS has been ordered.
     */
    public function getAvdResult()
    {
        return isset($this->data['protect_result_avs']) ? $this->data['protect_result_avs'] : null;
    }
}
