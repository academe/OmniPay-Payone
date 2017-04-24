<?php

namespace Omnipay\Payone;

/**
 * ONEPAY Shop (single payments) Frontend (hosted form redirect or iframe) driver for Omnipay
 */

use Omnipay\Common\Exception\InvalidRequestException;

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
    public function authorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopFrontendAuthorizeRequest', $parameters);
    }

    /**
     * The purchase transaction.
     */
    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopFrontendPurchaseRequest', $parameters);
    }

    /**
     * Accept an incoming notification (a ServerRequest).
     * This API supports the notification responses only.
     */
    public function acceptNotification(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopTransactionStatusServerRequest', $parameters);
    }
}
