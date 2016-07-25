<?php

namespace Omnipay\Payone;

/**
 * ONEPAY Shop (single payments) driver for Omnipay
 */

use Omnipay\Common\Exception\InvalidRequestException;

class ShopGateway extends AbstractShopGateway
{
    public function getName()
    {
        return 'PAYONE Shop API';
    }

    /**
     * The authorization transaction.
     */
    public function authorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopAuthorizeRequest', $parameters);
    }

    /**
     * For handling a purchase (athorisation with capture).
     */
    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopPurchaseRequest', $parameters);
    }

    /**
     * For handling a capture action.
     */
    public function capture(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopCaptureRequest', $parameters);
    }

    /**
     * Accept an incoming notification (a ServerRequest).
     */
    public function acceptNotification(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopTransactionStatusServerRequest', $parameters);
    }

    /**
     * For handling a void action.
     */
    public function void(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopVoidRequest', $parameters);
    }

    //
    // Below: TODO
    //

    /**
     * For handling a refund action.
     */
    public function DISABLED_refund(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopRefundRequest', $parameters);
    }

    /**
     * To fetch a single transaction.
     */
    public function DISABLED_fetchTransaction(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopFetchTransactionRequest', $parameters);
    }
}
