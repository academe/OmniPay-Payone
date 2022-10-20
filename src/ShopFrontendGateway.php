<?php

namespace Omnipay\Payone;

/**
 * ONEPAY Shop (single payments) Frontend (hosted form redirect or iframe) driver for Omnipay
 */
class ShopFrontendGateway extends AbstractShopGateway
{
    protected $endpoint = 'https://secure.pay1.de/frontend/';

    public function getName()
    {
        return 'PAYONE Shop Frontend';
    }

    /**
     * The authorization transaction.
     */
    public function authorize(array $parameters = [])
    {
        return $this->createRequest(Message\ShopFrontendAuthorizeRequest::class, $parameters);
    }

    /**
     * The purchase transaction.
     */
    public function purchase(array $parameters = [])
    {
        return $this->createRequest(Message\ShopFrontendPurchaseRequest::class, $parameters);
    }

    /**
     * Helper for generating the hidden fields in a credit card tokenisation AJAX form.
     */
    public function creditCardCheck(array $parameters = [])
    {
        return $this->createRequest(Message\ShopClientCardCheckRequest::class, $parameters);
    }

    /**
     * Accept an incoming notification (a ServerRequest).
     * This API supports the notification responses only.
     */
    public function acceptNotification(array $parameters = [])
    {
        return $this->createRequest(Message\ShopTransactionStatusServerRequest::class, $parameters);
    }
}
