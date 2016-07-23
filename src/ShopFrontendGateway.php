<?php

namespace Omnipay\Payone;

/**
 * ONEPAY Shop (single payments) driver for Omnipay
 */

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\AbstractGateway;

class ShopFrontendGateway extends ShopGateway
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
}
