<?php

namespace Omnipay\Payone;

/**
 * ONEPAY Shop (single payments) driver for Omnipay
 */

use Omnipay\Common\Exception\InvalidRequestException;

class ShopServerGateway extends AbstractShopGateway
{
    public function getName()
    {
        return 'PAYONE Shop Server';
    }

    /**
     * The authorization transaction.
     */
    public function authorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopServerAuthorizeRequest', $parameters);
    }

    /**
     * For handling a purchase (athorisation with capture).
     */
    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopServerPurchaseRequest', $parameters);
    }

    /**
     * For handling a capture action.
     */
    public function capture(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopServerCaptureRequest', $parameters);
    }

    /**
     * For handling a void action.
     */
    public function void(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopServerVoidRequest', $parameters);
    }

    /**
     * Accept an incoming notification (a ServerRequest).
     */
    public function acceptNotification(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopTransactionStatusServerRequest', $parameters);
    }
}
