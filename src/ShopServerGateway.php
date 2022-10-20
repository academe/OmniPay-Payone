<?php

namespace Omnipay\Payone;

/**
 * ONEPAY Shop (single payments) driver for Omnipay
 */
class ShopServerGateway extends AbstractShopGateway
{
    public function getName()
    {
        return 'PAYONE Shop Server';
    }

    /**
     * The authorization transaction.
     */
    public function authorize(array $parameters = [])
    {
        return $this->createRequest(Message\ShopServerAuthorizeRequest::class, $parameters);
    }

    /**
     * For handling a purchase (athorisation with capture).
     */
    public function purchase(array $parameters = [])
    {
        return $this->createRequest(Message\ShopServerPurchaseRequest::class, $parameters);
    }

    /**
     * For handling a capture action.
     */
    public function capture(array $parameters = [])
    {
        return $this->createRequest(Message\ShopServerCaptureRequest::class, $parameters);
    }

    /**
     * For handling a capture action.
     */
    public function refund(array $parameters = [])
    {
        return $this->createRequest(Message\ShopServerRefundRequest::class, $parameters);
    }

    /**
     * Check a credit card detail for "plausability" and get a card token in response.
     * This would normally be done client-side, but is available server side too for
     * development and testing.
     */
    public function creditCardCheck(array $parameters = [])
    {
        return $this->createRequest(Message\ShopServerCardCheckRequest::class, $parameters);
    }

    /**
     * Helper for managing Sepa Direct Debit Mandate
     */
    public function managemandate(array $parameters = [])
    {
        return $this->createRequest(Message\ShopServerManageMandateRequest::class, $parameters);
    }

    /**
     * For handling a void action.
     */
    public function void(array $parameters = [])
    {
        return $this->createRequest(Message\ShopServerVoidRequest::class, $parameters);
    }

    /**
     * Accept an incoming notification (a ServerRequest).
     * This API supports the notification responses as a suplement to the direct server responses.
     */
    public function acceptNotification(array $parameters = [])
    {
        return $this->createRequest(Message\ShopTransactionStatusServerRequest::class, $parameters);
    }
}
