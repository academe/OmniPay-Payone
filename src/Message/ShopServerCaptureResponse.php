<?php

namespace Omnipay\Payone\Message;

/**
 * Shop Payment Response
 */

class ShopServerCaptureResponse extends ShopServerAuthorizeResponse
{
    /**
     * Status codes for capture only.
     */

    const STATUS_APPROVED = 'APPROVED';

    /**
     * Success if the transaction has completed.
     * Only rresponses are APPROVED or ERROR, so isRedirect() will always be
     * false without overriding.
     */
    public function isSuccessful()
    {
        return $this->getStatus() == static::STATUS_APPROVED;
    }

    /**
     * Indicates whether the account has been settled by this payment.
     */
    public function isSettled()
    {
        $settled_text = isset($data['settleaccount']) ? $data['settleaccount'] : null;

        return strtolower($settled_text) === 'yes';
    }
}
